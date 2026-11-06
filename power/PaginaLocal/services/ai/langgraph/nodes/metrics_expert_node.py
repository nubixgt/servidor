import json
import logging
from datetime import datetime

from langchain_core.callbacks.usage import get_usage_metadata_callback

from services.ai.ai_settings import AgentRole
from services.ai.checkin import bloque_del_checkin
from services.ai.langgraph.schemas import MetricsExpertOutputs
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

METRICS_SYSTEM_PROMPT_BASE = """## Goal
Analyze training metrics and competition readiness with data-driven precision.
## Principles
- Analyze: Focus on load patterns, fitness trends, and readiness.
- Objectivity: Do not speculate beyond the data.
- Clarity: Explain complex relationships simply.
- Garmin's labels are a second opinion: Training Status, Load Focus / load balance ("AEROBIC_HIGH_SHORTAGE"
  and the like) are computed from HR zones and a max HR that are often misconfigured on the watch. Report
  them, but never turn one into a recommendation on its own; what the athlete actually did, and how the
  numbers moved after it, outranks the label.

## New Metrics Definitions (ACWR V2)

You are provided with "ACWR v2" metrics derived from daily training load (sum of activityTrainingLoad per day).

### EWMA metrics (smooth, responsive)
- **Acute EWMA (7d)**: short-term load (fatigue proxy).
- **Chronic EWMA (28d)**: longer-term load (fitness/preparedness proxy).
- **Shifted Chronic EWMA (t-7)**: chronic EWMA evaluated 7 days earlier (approximate uncoupling).
- **ACWR (EWMA shifted)**: Acute EWMA / Shifted Chronic EWMA. Use as a spike indicator, but note thresholds require calibration.
- **Risk Index**: ln(ACWR) (symmetric measure of “doubling vs halving”). Despite the name it does not measure
  injury risk: no ACWR-derived number predicts injury (Impellizzeri 2020). These ratios describe how load changed.
- **TSB**: Chronic EWMA - Acute EWMA (negative = accumulating fatigue).
- **Ramp Rate (7d)**: change in Chronic EWMA vs 7 days ago (detects fast load increases).
- **Monotony (7d)**: mean(daily load over last 7d) / SD(last 7d). High values indicate low variation.
- **Strain (7d)**: (total weekly load) x Monotony.

Note: Thresholds are heuristics and should be calibrated to the athlete and to the chosen ACWR definition.

### Rolling-sum metrics (Garmin-comparable scale)
These use 7-day rolling sums (closer to Garmin's magnitude, though Garmin may weight days differently):
- **Acute 7d Sum**: sum of daily loads over last 7 days (Garmin-like acute magnitude).
- **Chronic 28d Avg (of Acute 7d Sum)**: average of the last 28 values of Acute 7d Sum (smoothed baseline).
- **ACWR 7d/28d (coupled)**: Acute 7d Sum / Chronic 28d Avg.
- **ACWR 7d/28d (uncoupled)**: Acute 7d Sum / Chronic 28d Avg computed up to (t-7), excluding the most recent week (preferred for Garmin-like ACWR without coupling).
"""

def _bloque_de_reparto(state: dict) -> str:
    """El reparto de intensidad MEDIDO, para leer el Load Focus de Garmin contra él.

    v1.2.2. Este experto es el que lee el Load Focus y el Training Status de
    Garmin -- calculados con el pulso -- y no recibía ninguna medición propia. En
    el macrociclo real del atleta 2 (2026-09-13) escribió "AEROBIC_HIGH_SHORTAGE"
    y la semana lo convirtió en intención futura, sobre un atleta cuyo pulso llega
    tarde en las series. "" sin medición: cero tokens.
    """
    from services.ai.intensity_distribution import render_distribucion

    bloque = render_distribucion(
        ((state.get("availability_constraints") or {}).get("volume_baseline") or {}).get("intensity_distribution")
    )
    if not bloque:
        return ""
    return (
        "\n" + bloque
        + "Garmin's Load Focus and Training Status split intensity by heart rate. Read them against this measured "
        "distribution before calling anything a shortage, and say which one you trust and why.\n"
    )


def _bloque_de_procedencia(state: dict) -> str:
    """De dónde salen su umbral y sus zonas, para leer las etiquetas de Garmin contra eso.

    El Load Focus y el Training Status se calculan con las zonas y la FC máxima
    del reloj. Con las de fábrica, un rodaje sostenido cae en "Z4" y todo parece
    tempo -- el informe del atleta Hugo (2026-09-07) lo escribió así. "" si no
    hay nada que decir: cero tokens.
    """
    from services.ai.prescription import procedencia_de_umbrales

    texto = procedencia_de_umbrales(state.get("prescription"))
    return f"\n### De dónde salen su umbral y sus zonas\n{texto}\n" if texto else ""


METRICS_USER_PROMPT = """## Task
Analyze the metrics summary to identify patterns and trends.

## Constraints
- Focus on **global training metrics** (load, VO2max, status).
- Do NOT describe specific workouts (Activity Expert's job).
- Do NOT infer internal physiology (Physiology Expert's job).
- Focus on **how the training stimulus behaves over time**.

## Inputs
### Metrics Summary
{data}
{intensity_distribution}
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
- **Context**: This provides the **"Quantitative Backbone"** (load/stress reality) for the report.
- **Goal**: Provide the quantitative truth of training load.
- **Freedom**: Highlight load behavior, fitness trends, or important ratios.

### 2. `for_season_planner` (the macrocycle, up to the goal race)
- **Context**: This informs **"Load Architecture"** (ramp rates, how much load this athlete has absorbed) for the season.
- **Goal**: Provide high-level guidance on capacity and structural patterns.
- **Freedom**: What load this athlete has absorbed and how it moved — the ramps they handled well, the ones
  followed by a drop in their response, and how much room there is to build. Room to grow is as much a finding
  as a limit; state either only with this athlete's own history behind it.

### 3. `for_weekly_planner` (the next week)
- **Context**: The load context for the week being planned: where this athlete's load sits against what
  they have actually absorbed, and any spike worth avoiding.
- **Goal**: Say what the numbers show; the planner decides, with the athlete's history and response in front.
- **Freedom**: Push/pull signals and the evidence behind them. A limit is only worth stating when a real
  spike in this athlete's own history backs it.
- **CRITICAL**: Do NOT prescribe specific workouts. Provide load guidance."""

METRICS_FINAL_CHECKLIST = """
## Final Checklist
- Use Signals/Evidence/Implications/Uncertainty per receiver.
- Stay within metrics domain only.
- No prescriptions for specific workouts.
"""

async def metrics_expert_node(state: TrainingAnalysisState) -> dict[str, list | str | dict]:
    logger.info("Starting metrics expert analysis node")

    plot_storage = PlotStorage(state["execution_id"])
    plotting_enabled = state.get("plotting_enabled", False)
    hitl_enabled = state.get("hitl_enabled", True)

    logger.info(
        "Metrics expert: Plotting %s, HITL %s",
        "enabled" if plotting_enabled else "disabled",
        "enabled" if hitl_enabled else "disabled",
    )

    tools = configure_node_tools(
        agent_name="metrics",
        plot_storage=plot_storage,
        plotting_enabled=plotting_enabled,
    )

    system_prompt = (
        get_workflow_context("metrics")
        + METRICS_SYSTEM_PROMPT_BASE
        + (get_plotting_instructions("metrics") if plotting_enabled else "")
        + (get_hitl_instructions("metrics") if hitl_enabled else "")
        + METRICS_FINAL_CHECKLIST
    )

    base_llm = ModelSelector.get_llm(AgentRole.METRICS_EXPERT)

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
    output_schema = MetricsExpertOutputs if hitl_enabled else salida_para(state.get("receptores_de_expertos"))
    llm_with_structure = llm_with_tools.with_structured_output(output_schema)

    agent_start_time = datetime.now()

    async def call_metrics_with_tools():
        qa_messages = normalize_langchain_messages(state.get("metrics_expert_messages", []))

        base_messages = [
            {"role": "system", "content": system_prompt},
            {
                "role": "user",
                "content": METRICS_USER_PROMPT.format(
                    # `or`, no un valor por defecto: el estado nace con la clave
                    # a None, y un resumidor que falla la deja así -- el prompt
                    # decía literalmente "None".
                    data=state.get("metrics_summary") or "No metrics summary available",
                    intensity_distribution=_bloque_de_reparto(state) + _bloque_de_procedencia(state),
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
                call_metrics_with_tools, AI_ANALYSIS_CONFIG, "Metrics Agent with Tools"
            )
        logger.info("Metrics expert analysis completed")

        execution_time = (datetime.now() - agent_start_time).total_seconds()

        plots, plot_storage_data, available_plots = create_plot_entries("metrics", plot_storage)

        log_node_completion("Metrics expert analysis", execution_time, len(available_plots))

        return {
            "metrics_outputs": agent_output,
            "plots": plots,
            "plot_storage_data": plot_storage_data,
            "costs": [create_cost_entry("metrics", execution_time, usage_cb.usage_metadata)],
            "available_plots": available_plots,
        }

    return await execute_node_with_error_handling(
        node_name="Metrics expert analysis",
        node_function=node_execution,
        error_message_prefix="Metrics expert analysis failed",
    )
