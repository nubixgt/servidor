import json
import logging
from datetime import datetime

from langchain_core.callbacks.usage import get_usage_metadata_callback

from services.ai.ai_settings import AgentRole
from services.ai.checkin import bloque_del_checkin
from services.ai.langgraph.schemas import PhysiologyExpertOutputs
from services.ai.langgraph.schemas.expert_outputs import nota_de_receptores, salida_para
from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState
from services.ai.langgraph.utils.message_helper import normalize_langchain_messages
from services.ai.model_config import ModelSelector
from services.ai.number_audit import con_fecha_escrita
from services.ai.tools.plotting import PlotStorage
from services.ai.utils.retry_handler import AI_ANALYSIS_CONFIG, retry_with_backoff

from .node_base import (
    configure_node_tools,
    create_cost_entry,
    create_plot_entries,
    execute_node_with_error_handling,
    log_node_completion,
)
from .prompt_components import (
    get_hitl_instructions,
    get_plotting_instructions,
    get_workflow_context,
)
from .tool_calling_helper import handle_tool_calling_in_node

logger = logging.getLogger(__name__)

PHYSIOLOGY_SYSTEM_PROMPT_BASE = """## Goal
Optimize recovery and adaptation through precise physiological analysis.
## Principles
- Holistic: View the body as an interconnected system.
- Temporal: Interpret signals across immediate and long-term timeframes.
- Actionable: Identify recovery windows and stress costs."""

# El manual que este nodo no tenía. El de métricas recibe treinta líneas
# definiendo ACWR v2, EWMA, monotonía y strain; éste recibía tres viñetas —- y
# es el dominio donde la interpretación es MÁS personal y más fácil de
# equivocar.
#
# Lo que lo destapó fue una salida real (generación del 2026-09-03): el experto
# escribió "HRV 59, RHR 50 y sueño 8.01h/score 86 — dentro de rango normal"
# sobre un prompt de 5.890 caracteres en el que la palabra `baseline` no
# aparecía ni una vez. Normal ¿contra qué? Contra una norma poblacional
# recordada, porque no le habíamos dado la suya. El rango propio de ese atleta
# era 56-83 ms; el de otro puede ser 30-45, y ahí se decide si su semana lleva
# calidad.
#
# La regla ya existía escrita en el prompt del planificador semanal ("read
# against THEIR OWN baseline -- never against a remembered population
# threshold"), pero está UN NODO MÁS ABAJO y no puede deshacer una lectura ya
# hecha: al planificador le llega el veredicto, no los datos crudos. Va aquí,
# donde de verdad se juzga.
PHYSIOLOGY_READING_RULES = """
## Cómo se lee una señal de recuperación
- **Siempre contra la base PROPIA de este atleta, nunca contra un umbral recordado.** Un HRV de 59 ms
  no es alto ni bajo por sí mismo: sólo significa algo comparado con el rango base de ESTE atleta,
  que recibes más abajo cuando Garmin lo ha calculado. **Nunca escribas "normal", "bueno" ni "bajo"
  sin decir normal contra qué**, y no inventes cortes absolutos — ni de HRV ("por debajo de 50 ms hay fatiga") ni de sueño, estrés o pulso
  en reposo ("noches de menos de 6 h", "estrés por encima de 30"):
  fuera de este atleta esos números no significan nada.
- **Si NO recibes su rango base, dilo.** Trata el número como tendencia contra sus propios días
  anteriores, bájale la `confidence`, y déjalo escrito en `Uncertainty`. Un hueco declarado es una
  lectura honesta; un hueco rellenado con una norma de población es una lectura falsa que además
  parece segura.
- **Una noche no es una señal.** Un valor aislado no sostiene ninguna conclusión: busca corroboración
  entre marcadores (HRV, frecuencia cardiaca en reposo, sueño, estrés) y a lo largo de varios días.
  Cuando los marcadores se contradigan entre ellos, dilo — es información, no un problema a esconder.
- **Distingue el dato de la etiqueta.** El estado que da Garmin (`BALANCED`, `UNBALANCED`, ...) ya está
  calculado contra su base personal, así que es una fuente distinta y más fuerte que tu propia lectura
  del número crudo. Si tú concluyes lo contrario que la etiqueta de Garmin, no la borres: cita las dos
  y di qué observación lo resolvería.
- **La ausencia de medición no es una medición.** Un día sin registro es un hueco, no un cero ni una
  noche mala."""

PHYSIOLOGY_USER_PROMPT = """## Task
Analyze the physiology summary to assess recovery and adaptation.

## Constraints
- Focus on **internal state** (HRV, sleep, RHR, stress).
- Do NOT re-derive load metrics (Metrics Expert's job).
- Do NOT redesign training structure (Planner's job).
- Focus on **how the body is handling stress**.

## Inputs
### Physiology Summary
{data}
{athlete_basics}{recovery_state}{aerobic_decoupling}
### Context
- Competitions: ```json {competitions} ```
- Date: ```json {current_date} ```
- **User Context**: ``` {analysis_context} ```

## Output Requirements
Produce 3 structured fields. For EACH field, use this internal layout:
- **Signals**: what changed (concise)
- **Evidence**: numbers + date ranges
- **Implications**: constraints/opportunities for this receiver
- **Uncertainty**: gaps/low coverage if any

And these four, which say how much your read should COUNT (added because three domains
routinely contradict each other and the consumer had no way to tell which claim outranked which):
- `confidence`: how well the DATA supports it — `alta` = directly measured, good coverage, recent;
  `baja` = inferred, sparse or stale. This is about the evidence, not about how sure you feel.
- `priority`: 1 = must shape this receiver's decision · 2 = worth weighing · 3 = context only.
  **Reserve 1 for the few things that genuinely change what the athlete should do.** Marking
  everything as 1 destroys the signal you are being asked to give.
- `horizon_days`: roughly how long this read holds before it needs re-checking. One night of data is a
  1-2 day read; a six-week trend is a 30+ day one. Leave empty if genuinely unclear.
- `conflicts_with`: if another domain is likely to claim the opposite, say so in one sentence and name
  what observation would settle it. Empty when there is no plausible contradiction. **Surfacing the
  disagreement is the point — never soften your own read to avoid one.**

**Important**: Tailor content for each consumer.

### 1. `for_synthesis` (Comprehensive Report)
- **Context**: This feeds the **"Whole Athlete"** view (Summary & Synthesis). It acts as the "Internal Body Check".
- **Goal**: Provide a qualitative assessment of recovery and adaptation.
- **Freedom**: Highlight recovery costs, adaptation status, or internal signals.

### 2. `for_season_planner` (the macrocycle, up to the goal race)
- **Context**: This informs **Long-Term Structural Decisions** (Macro-cycle).
- **Goal**: Inform the athlete's **"Absorptive Capacity"**.
- **Freedom**: How they absorbed hard blocks over months — how long they took to come back, what went with
  slow recoveries, and where there was room for more. Both directions.

### 3. `for_weekly_planner` (the next week)
- **Context**: This is the **readiness read** for the week: what the recovery signals allow, in both directions.
- **Goal**: Provide readiness guidance.
- **Freedom**: Speak in **readiness corridors** (e.g., "High readiness, go for overload" or "Sympathetic dominance, limit intensity").
  Any corridor or rule is anchored to THIS athlete's own ranges (their HRV range, their usual sleep and resting
  HR) — never a population cut-off such as a fixed sleep score, hours of sleep or stress level."""

PHYSIOLOGY_FINAL_CHECKLIST = """
## Final Checklist
- Use Signals/Evidence/Implications/Uncertainty per receiver.
- Stay within physiology domain only.
- No training structure redesign.
"""

async def physiology_expert_node(state: TrainingAnalysisState) -> dict[str, list | str | dict]:
    logger.info("Starting physiology expert analysis node")

    plot_storage = PlotStorage(state["execution_id"])
    plotting_enabled = state.get("plotting_enabled", False)
    hitl_enabled = state.get("hitl_enabled", True)

    logger.info(
        "Physiology expert: Plotting %s, HITL %s",
        "enabled" if plotting_enabled else "disabled",
        "enabled" if hitl_enabled else "disabled",
    )

    tools = configure_node_tools(
        agent_name="physiology",
        plot_storage=plot_storage,
        plotting_enabled=plotting_enabled,
    )

    system_prompt = (
        get_workflow_context("physiology")
        + PHYSIOLOGY_SYSTEM_PROMPT_BASE
        + PHYSIOLOGY_READING_RULES
        + (get_plotting_instructions("physiology") if plotting_enabled else "")
        + (get_hitl_instructions("physiology") if hitl_enabled else "")
        + PHYSIOLOGY_FINAL_CHECKLIST
    )

    base_llm = ModelSelector.get_llm(AgentRole.PHYSIOLOGY_EXPERT)
    llm_with_tools = base_llm.bind_tools(tools) if tools else base_llm
    # A union in a structured-output schema measurably degrades what comes back
    # -- the same finding that had the workout formatter returning empty step
    # lists for roughly half its days until v4 narrowed its schema. HITL is off
    # on the web app path, so bind the analysis shape directly there and keep
    # the union only where questions can actually be asked. Downstream is
    # already tolerant of both shapes: extract_expert_output falls through to
    # the object itself when there is no `.output`, and the orchestrator's
    # _collect_questions simply finds no questions on a bare ReceiverOutputs.
    # Sólo las secciones que esta generación va a leer (2026-09-23, ver salida_para).
    output_schema = PhysiologyExpertOutputs if hitl_enabled else salida_para(state.get("receptores_de_expertos"))
    llm_with_structure = llm_with_tools.with_structured_output(output_schema)

    agent_start_time = datetime.now()

    # Los dos bloques viajan por `availability_constraints`, el mismo canal
    # lateral del que ya tira el planificador semanal. Cadena vacía cuando no
    # hay dato -- ni sección ni "no disponible", porque una sección que anuncia
    # lo que falta invita a razonar sobre el hueco.
    constraints = state.get("availability_constraints") or {}
    recovery_block = (constraints.get("recovery") or {}).get("block") or ""
    basics_block = constraints.get("athlete_basics") or ""
    # v1.2.2: la deriva cardíaca de sus sesiones largas y sostenidas. "" cuando
    # ninguna sirve para medir, por la misma razón. Ver
    # services/ai/aerobic_decoupling.py.
    decoupling_block = (constraints.get("decoupling") or {}).get("block") or ""

    async def call_physiology_analysis():
        qa_messages = normalize_langchain_messages(state.get("physiology_expert_messages", []))

        base_messages = [
            {"role": "system", "content": system_prompt},
            {
                "role": "user",
                "content": PHYSIOLOGY_USER_PROMPT.format(
                    data=state.get("physiology_summary") or "No physiology summary available",
                    athlete_basics=basics_block,
                    recovery_state=recovery_block,
                    aerobic_decoupling=decoupling_block,
                    competitions=json.dumps(state["competitions"], indent=2),
                    current_date=json.dumps(con_fecha_escrita([state["current_date"]])[0], indent=2, ensure_ascii=False),
                    analysis_context=state["analysis_context"],
                )
                # Lo que el atleta dijo en su check-in de hoy (2026-09-21: sólo lo
                # leía la decisión semanal). "" sin check-in: el prompt no cambia.
                + bloque_del_checkin(state)
                + nota_de_receptores(None if hitl_enabled else state.get("receptores_de_expertos")),
            },
        ]

        return await handle_tool_calling_in_node(
            llm_with_tools=llm_with_structure,
            messages=base_messages + qa_messages,
            tools=tools,
            max_iterations=15,
        )

    async def node_execution():
        with get_usage_metadata_callback() as usage_cb:
            agent_output = await retry_with_backoff(
                call_physiology_analysis, AI_ANALYSIS_CONFIG, "Physiology Expert with Tools"
            )

        execution_time = (datetime.now() - agent_start_time).total_seconds()
        plots, plot_storage_data, available_plots = create_plot_entries("physiology", plot_storage)

        log_node_completion("Physiology expert analysis", execution_time, len(available_plots))

        return {
            "physiology_outputs": agent_output,
            "plots": plots,
            "plot_storage_data": plot_storage_data,
            "costs": [create_cost_entry("physiology", execution_time, usage_cb.usage_metadata)],
            "available_plots": available_plots,
        }

    return await execute_node_with_error_handling(
        node_name="Physiology expert analysis",
        node_function=node_execution,
        error_message_prefix="Physiology expert analysis failed",
    )
