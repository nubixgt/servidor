import json
import logging
from datetime import datetime

from langchain_core.callbacks.usage import get_usage_metadata_callback

from services.ai.ai_settings import AgentRole
from services.ai.checkin import bloque_del_checkin
from services.ai.langgraph.schemas import ActivityExpertOutputs
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

ACTIVITY_EXPERT_SYSTEM_PROMPT_BASE = """## Goal
Interpret structured activity data to optimize workout progression patterns.
## Principles
- Precision: Detect subtle execution details.
- Pattern Recognition: Identify what works and what doesn't.
- Clarity: Cut through confusion with direct analysis."""

def _bloque_de_reparto(state: dict) -> str:
    """El reparto de intensidad MEDIDO: qué fueron de verdad sus sesiones.

    v1.2.2. Este experto juzga la ejecución sesión a sesión y no sabía cómo se
    reparten en conjunto, ni que en un atleta cuyo pulso llega tarde las series
    se leen por ritmo. "" sin medición: cero tokens.
    """
    from services.ai.intensity_distribution import render_distribucion

    bloque = render_distribucion(
        ((state.get("availability_constraints") or {}).get("volume_baseline") or {}).get("intensity_distribution")
    )
    return f"\n{bloque}" if bloque else ""


def _bloque_de_zonas(state: dict) -> str:
    """Sus zonas de pulso y sus bandas de ritmo, las reales.

    Este experto juzga la intensidad de cada sesión "por ritmo y pulso" y no
    tenía ni una ni otra referencia. En el macrociclo del atleta 2
    (2026-09-13) llamó "tempo/umbral" a una pista de 1600 m a 7:11/km, cuando
    su banda de tempo es 5:46-6:14 y 7:11 cae en la de fácil (6:49-7:38): el
    pulso sí estaba entre umbrales, el ritmo no, y sin sus bandas no podía verlo.
    La síntesis se lo repitió al atleta. "" sin prescripción: cero tokens.
    """
    from services.ai.prescription import procedencia_de_umbrales, render_zones_table

    tabla = render_zones_table(state.get("prescription"))
    if not tabla:
        return ""
    # De dónde sale cada número, porque de eso depende cuánto pesa. El título
    # decía "su umbral real" también cuando el umbral era una predicción y las
    # zonas las de fábrica, y el informe del atleta Hugo (2026-09-07) llamó
    # "tempo/umbral" a un 21K a 162 ppm porque su Z4 de fábrica empezaba en 157.
    procedencia = procedencia_de_umbrales(state.get("prescription"))
    return (
        "\n### Sus zonas y ritmos (los de su reloj)\n"
        f"{tabla}\n"
        + (f"{procedencia}\n" if procedencia else "")
        + "Un ritmo es tempo, umbral o VO2max sólo si cae en SU banda. Si el pulso dice una intensidad y el ritmo "
        "otra, dilo y di qué lo explicaría; no elijas una de las dos en silencio.\n"
    )


ACTIVITY_EXPERT_USER_PROMPT = """## Task
Interpret activity summaries to identify patterns and guidance.

## Constraints
- Focus on **session-level execution** (pace, power, HR, structure).
- Do NOT explain global load (Metrics Expert's job).
- Do NOT propose future schedules (Planner's job).
- Focus on **"what this specific workout does to the system"**.

## Inputs
### Activity Summary
{activity_summary}
{intensity_distribution}
{athlete_zones}
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
- **Context**: This feeds the **"Whole Athlete"** view (Summary & Synthesis).
- **Goal**: Provide a qualitative assessment of execution quality.
- **Freedom**: Highlight what matters most—execution patterns, progression quality, or consistency.

### 2. `for_season_planner` (the macrocycle, up to the goal race)
- **Context**: This informs **Long-Term Structural Decisions** (Macro-cycle).
- **Goal**: Identify which "building blocks" (workout types) are effective for this specific athlete.
- **Freedom**: Focus on success patterns and sequencing preferences.

### 3. `for_weekly_planner` (the next week)
- **Context**: This informs the week being planned: what this athlete's sessions look like and what they
  respond to.
- **Goal**: Provide actionable guidance for the next week.
- **Freedom**: define constraints, opportunities, and session load hints as needed.
- **CRITICAL**: Do NOT propose a schedule. Provide rules and building blocks."""

ACTIVITY_FINAL_CHECKLIST = """
## Final Checklist
- Use Signals/Evidence/Implications/Uncertainty per receiver.
- Stay within activity execution domain only.
- No schedule proposals.
"""


async def activity_expert_node(state: TrainingAnalysisState) -> dict[str, list | str | dict]:
    logger.info("Starting activity expert node")

    plot_storage = PlotStorage(state["execution_id"])
    plotting_enabled = state.get("plotting_enabled", False)
    hitl_enabled = state.get("hitl_enabled", True)

    logger.info(
        "Activity expert node: Plotting %s, HITL %s",
        "enabled" if plotting_enabled else "disabled",
        "enabled" if hitl_enabled else "disabled",
    )

    tools = configure_node_tools(
        agent_name="activity",
        plot_storage=plot_storage,
        plotting_enabled=plotting_enabled,
    )

    system_prompt = (
        get_workflow_context("activity")
        + ACTIVITY_EXPERT_SYSTEM_PROMPT_BASE
        + (get_plotting_instructions("activity") if plotting_enabled else "")
        + (get_hitl_instructions("activity") if hitl_enabled else "")
        + ACTIVITY_FINAL_CHECKLIST
    )

    base_llm = ModelSelector.get_llm(AgentRole.ACTIVITY_EXPERT)
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
    output_schema = ActivityExpertOutputs if hitl_enabled else salida_para(state.get("receptores_de_expertos"))
    llm_with_structure = llm_with_tools.with_structured_output(output_schema)

    agent_start_time = datetime.now()

    async def call_activity_expert():
        qa_messages = normalize_langchain_messages(state.get("activity_expert_messages", []))

        base_messages = [
            {"role": "system", "content": system_prompt},
            {
                "role": "user",
                "content": ACTIVITY_EXPERT_USER_PROMPT.format(
                    activity_summary=state.get("activity_summary") or "No activity summary available",
                    intensity_distribution=_bloque_de_reparto(state),
                    athlete_zones=_bloque_de_zonas(state),
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
                call_activity_expert, AI_ANALYSIS_CONFIG, "Activity Expert Analysis with Tools"
            )

        execution_time = (datetime.now() - agent_start_time).total_seconds()
        plots, plot_storage_data, available_plots = create_plot_entries("activity_expert", plot_storage)

        log_node_completion("Activity expert", execution_time, len(available_plots))

        return {
            "activity_outputs": agent_output,
            "plots": plots,
            "plot_storage_data": plot_storage_data,
            "costs": [create_cost_entry("activity_expert", execution_time, usage_cb.usage_metadata)],
            "available_plots": available_plots,
        }

    return await execute_node_with_error_handling(
        node_name="Activity expert",
        node_function=node_execution,
        error_message_prefix="Activity expert analysis failed",
    )
