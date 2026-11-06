import json
import logging
from datetime import datetime

from langchain_core.callbacks.usage import get_usage_metadata_callback

from services.ai.ai_settings import AgentRole
from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState
from services.ai.langgraph.utils.output_helper import extract_expert_output
from services.ai.model_config import ModelSelector
from services.ai.number_audit import con_fecha_escrita
from services.ai.tools.plotting import PlotStorage
from services.ai.utils.retry_handler import AI_ANALYSIS_CONFIG, retry_with_backoff

from .node_base import create_cost_entry
from .prompt_components import get_conflict_policy, get_workflow_context
from .tool_calling_helper import extract_text_content, handle_tool_calling_in_node

logger = logging.getLogger(__name__)





SYNTHESIS_SYSTEM_PROMPT_BASE = """You are a performance integration specialist.
## Goal
Create comprehensive, actionable insights by synthesizing multiple data streams.
## Principles
- Integrate: Connect insights from metrics, activity, and physiology.
- Contextualize: Relate data to the athlete's history and goals.
- Simplify: Make complex relationships understandable.
- Honest: An unresolved disagreement between domains is a finding, not a gap to paper over."""

SYNTHESIS_PLOT_INSTRUCTIONS = """
## Plot Integration
- Include plot references as `[PLOT:plot_id]` in your text.
- These will become interactive charts."""

# Cómo se escribe el informe que lee el ATLETA.
#
# `style_guide` existía desde el principio en el estado y en este prompt, y
# nunca llegó nada: `create_initial_state` lo declara con "" por defecto y
# ninguna de sus cinco llamadas lo pasa. El bloque de estilo iba vacío, así que
# el tono del informe eran cuatro palabras en inglés ("Professional,
# evidence-based, encouraging") -- y la beta lo pedía con otras: "lenguaje para
# mortales". Es el fallo que más se repite en este repositorio: un campo que el
# consumidor nunca rellena y un valor por defecto que lo tapa.
#
# Vive aquí, y se usa cuando el estado llega vacío, en vez de enhebrarlo por
# cinco llamadas que tampoco lo rellenarían.
GUIA_DE_ESTILO_DEL_INFORME = """- Escribe en español, al atleta, de tú. Lo va a leer el atleta, no otro entrenador.
- **La conclusión antes que el método.** Cada sección empieza por lo que significa para el atleta y sigue con el
  dato que lo sostiene, nunca al revés.
- **Cada término técnico se explica la primera vez que aparece**, en media frase: carga crónica, ACWR, HRV,
  VO2max, umbral, zonas, deriva cardíaca. Si un término no le ayuda a decidir nada, no lo uses.
- Ningún párrafo de jerga sin un número detrás, y ningún número sin decir qué significa para el atleta.
- Frases cortas. Nada de "optimizar", "sinergia", "holístico" ni frases que valdrían para cualquier atleta.
- Cuando faltan datos, dilo llano ("estas dos semanas no hay datos de sueño") y no lo rellenes.
- El tono es el de un entrenador que acaba de revisar sus datos con el atleta: directo y honesto. Anima con hechos
  concretos de su entrenamiento, no con adjetivos.
- **Las recomendaciones valen mientras dure este informe**, que el atleta relee semanas después: qué señal
  vigilar y cómo leerla, qué anotar, qué hábito cuidar. **No prescriben sesiones, días, duraciones ni
  ritmos**: la semana la escribe otro paso con los datos de ese día, y un informe que receta sesiones acaba
  contradiciendo al plan que el atleta tiene al lado.
- **La fuerza de las palabras es la del dato.** Una señal de verdad preocupante se dice claro, con qué hacer.
  Una tendencia moderada se cuenta con su número contra su propia historia ("el sueño bajó de 83 a 77 desde
  junio") y con lo que significa, sin convertirla en alarma ni en regla innegociable. Ni exagerar ni callar.
- **Cita las cifras que puede encontrar en su reloj**: las de Garmin, en el bloque de carga. La app calcula su
  propio ACWR y su propia carga crónica en otra escala; si usas una de esas, di que es de la app. Nunca
  escribas el identificador crudo (`chronic_ewma`, `aerobic_high`): di lo que mide.
- El ACWR describe cómo cambió su carga, no un "riesgo de lesión" ni una carga "segura": el bloque de carga
  dice por qué. **No existe un rango óptimo de ACWR que puedas citar**: el rango que da Garmin (p. ej.
  "314–588") es para su CARGA AGUDA (0,8-1,5 veces su crónica), no un rango de ACWR ni de su crónica. No escribas "óptimo 0,8-1,3", "zona óptima" ni "sin riesgo de
  sobrecarga" junto a un ACWR: di cuánto subió o bajó su carga reciente frente a su base, y ya.
- **Una regla de "si pasa X, haz Y" sólo con los números de SU rango**, pidiendo varios días y una segunda
  señal, y con una respuesta proporcionada: bajar la intensidad antes que "descanso absoluto".
- **Una molestia que el atleta puntúa 1-2/10 se menciona una vez y como lo que es: leve y a vigilar.** No es
  "el principal riesgo", no se diagnostica, no se explica su biomecánica y no se le prometen efectos a nada
  ("reducirá drásticamente la molestia").
- **No afirmes mecanismos como certezas** ("el sueño es el único momento en que se reparan los tendones"). Si
  no está en sus datos ni en lo que dicen los expertos, no lo escribas."""

SYNTHESIS_USER_PROMPT_BASE = """Synthesize the expert analyses into a comprehensive athlete report.

## Inputs
### Metrics
```markdown
{metrics_result}
```
### Activity
```markdown
{activity_result}
```
### Physiology
```markdown
{physiology_result}
```
{load_state}
### Context
- Athlete: {athlete_name}
- Competitions: ```json {competitions} ```
- Date: ```json {current_date} ```

### Style guide — follow it: the athlete reads this report, not another coach
{style_guide}

## Task
1. **Integrate**: Connect load (metrics), execution (activity), and response (physiology).
2. **Reconcile**: Where the three domains disagree, apply the conflict policy from your system prompt —
   name the disagreement, weigh it by evidence, and say what would resolve it.
3. **Identify Patterns**: Spot trends in performance and adaptation.
4. **Synthesize**: Create a coherent story, not just a list of facts.

## Output Format
- **Executive Summary**: High-level status and key takeaways.
- **Key Performance Indicators**: Table format.
- **Deep Dive**: Structured sections with clear headings.
- **Señales en conflicto**: only if two domains genuinely disagree — what each one says, which reading the
  evidence favours and why, and what observation would settle it. Omit this section entirely if they agree.
- **Recommendations**: Brief and actionable.
- **Tone and language**: the style guide above."""


def construir_mensaje_de_usuario(state: TrainingAnalysisState, plotting_enabled: bool = False) -> str:
    """El mensaje que recibe el modelo de síntesis. Aparte para poder probarlo
    sin llamar a ningún modelo; ver GUIA_DE_ESTILO_DEL_INFORME."""
    return SYNTHESIS_USER_PROMPT_BASE.format(
        athlete_name=state["athlete_name"],
        metrics_result=extract_expert_output(state.get("metrics_outputs"), "for_synthesis"),
        activity_result=extract_expert_output(state.get("activity_outputs"), "for_synthesis"),
        physiology_result=extract_expert_output(state.get("physiology_outputs"), "for_synthesis"),
        # La carga con las cifras de su reloj y lo que el ACWR es y no es. Era el
        # único nodo que hablaba de carga sin este bloque, y rellenaba el hueco
        # con "riesgo de lesión" y un "óptimo 0.8-1.3" de su propia cosecha.
        load_state=(
            "### Carga según Garmin (las cifras que el atleta ve en su reloj)\n" + bloque_de_carga + "\n"
            if (bloque_de_carga := ((state.get("availability_constraints") or {}).get("load_state") or {})
                .get("block")) else ""
        ),
        competitions=json.dumps(state["competitions"], indent=2),
        current_date=json.dumps(con_fecha_escrita([state["current_date"]])[0], indent=2, ensure_ascii=False),
        style_guide=state.get("style_guide") or GUIA_DE_ESTILO_DEL_INFORME,
    ) + (SYNTHESIS_USER_PLOT_INSTRUCTIONS if plotting_enabled else "")

SYNTHESIS_USER_PLOT_INSTRUCTIONS = """
## Plot References
- Include each unique `[PLOT:plot_id]` EXACTLY ONCE.
- Do not duplicate references."""


async def synthesis_node(state: TrainingAnalysisState) -> dict[str, list | str]:
    logger.info("Starting synthesis node")

    try:
        plot_storage = PlotStorage(state["execution_id"])
        plotting_enabled = state.get("plotting_enabled", False)

        logger.info(
            "Synthesis node: Plotting %s - %s plot integration instructions",
            "enabled" if plotting_enabled else "disabled",
            "including" if plotting_enabled else "no",
        )

        agent_start_time = datetime.now()

        async def call_synthesis_analysis():
            return await handle_tool_calling_in_node(
                llm_with_tools=ModelSelector.get_llm(AgentRole.SYNTHESIS).bind_tools([]),
                messages=[
                    {"role": "system", "content": (
                        get_workflow_context("synthesis")
                        + SYNTHESIS_SYSTEM_PROMPT_BASE
                        + get_conflict_policy()
                        + (SYNTHESIS_PLOT_INSTRUCTIONS if plotting_enabled else "")
                    )},
                    {"role": "user", "content": construir_mensaje_de_usuario(state, plotting_enabled)},
                ],
                tools=[],
                max_iterations=3,
            )

        with get_usage_metadata_callback() as usage_cb:
            synthesis_result = await retry_with_backoff(
                call_synthesis_analysis, AI_ANALYSIS_CONFIG, "Synthesis Analysis with Tools"
            )

        execution_time = (datetime.now() - agent_start_time).total_seconds()
        logger.info("Synthesis analysis completed in %.2fs", execution_time)
        # El modelo devuelve un mensaje, no un texto (y GPT por la Responses API, en
        # bloques): con el `isinstance(str)` de antes este filtro no se aplicaba nunca.
        from services.ai.texto_del_atleta import sin_alfabetos_ajenos
        synthesis_result = sin_alfabetos_ajenos(extract_text_content(synthesis_result), "informe")

        return {
            "synthesis_result": synthesis_result,
            "synthesis_complete": True,
            "costs": [create_cost_entry("synthesis", execution_time, usage_cb.usage_metadata)],
            "available_plots": plot_storage.list_available_plots(),
        }

    except Exception as exc:
        logger.exception("Synthesis node failed")
        return {"errors": [f"Synthesis analysis failed: {exc!s}"]}
