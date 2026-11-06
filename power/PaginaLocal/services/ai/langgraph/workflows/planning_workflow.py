import logging
from collections.abc import Callable
from datetime import datetime
from typing import Any, cast

from langgraph.checkpoint.memory import MemorySaver
from langgraph.graph import END, START, StateGraph

from services.ai.langgraph.config.langsmith_config import (
    LangSmithConfig,
    configure_langsmith_for_user,
)
from services.ai.langgraph.nodes.activity_expert_node import activity_expert_node
from services.ai.langgraph.nodes.activity_summarizer_node import activity_summarizer_node
from services.ai.langgraph.nodes.data_integration_node import data_integration_node
from services.ai.langgraph.nodes.formatter_node import formatter_node
from services.ai.langgraph.nodes.metrics_expert_node import metrics_expert_node
from services.ai.langgraph.nodes.metrics_summarizer_node import metrics_summarizer_node
from services.ai.langgraph.nodes.orchestrator_node import master_orchestrator_node
from services.ai.langgraph.nodes.physiology_expert_node import physiology_expert_node
from services.ai.langgraph.nodes.physiology_summarizer_node import physiology_summarizer_node
from services.ai.langgraph.nodes.plan_formatter_node import plan_formatter_node
from services.ai.langgraph.nodes.plot_resolution_node import plot_resolution_node
from services.ai.langgraph.nodes.season_planner_node import season_planner_node
from services.ai.langgraph.nodes.synthesis_node import synthesis_node
from services.ai.langgraph.nodes.weekly_planner_node import weekly_planner_node
from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState, create_initial_state
from services.ai.langgraph.utils.workflow_cost_tracker import WorkflowCostTracker
from services.ai.preguntas_del_coach import preguntas_del_coach_node
from services.ai.prompt_capture import PromptCapture

logger = logging.getLogger(__name__)


def create_planning_workflow():
    LangSmithConfig.setup_langsmith()

    workflow = StateGraph(TrainingAnalysisState)

    workflow.add_node("master_orchestrator", master_orchestrator_node)
    workflow.add_node("data_integration", data_integration_node)
    workflow.add_node("weekly_planner", weekly_planner_node)
    workflow.add_node("plan_formatter", plan_formatter_node)

    workflow.add_edge(START, "master_orchestrator")

    workflow.add_edge("master_orchestrator", "data_integration")
    workflow.add_edge("master_orchestrator", "plan_formatter")
    workflow.add_edge("master_orchestrator", "weekly_planner")

    workflow.add_edge("data_integration", "weekly_planner")
    workflow.add_edge("weekly_planner", "master_orchestrator")
    workflow.add_edge("plan_formatter", END)

    checkpointer = MemorySaver()
    app = workflow.compile(checkpointer=checkpointer)

    logger.info("Created complete LangGraph planning workflow with 4 agents")
    return app


async def run_weekly_planning(
    user_id: str,
    athlete_name: str,
    garmin_data: dict,
    planning_context: str = "",
    competitions: list | None = None,
    current_date: dict | None = None,
    week_dates: list | None = None,
    metrics_outputs=None,
    activity_outputs=None,
    physiology_outputs=None,
    plots: list | None = None,
    available_plots: list | None = None,
) -> dict:
    execution_id = f"{user_id}_{datetime.now().strftime('%Y%m%d_%H%M%S')}_planning"
    config = {"configurable": {"thread_id": execution_id}}

    initial_state = create_initial_state(
        user_id=user_id,
        athlete_name=athlete_name,
        garmin_data=garmin_data,
        planning_context=planning_context,
        competitions=competitions,
        current_date=current_date,
        week_dates=week_dates,
        execution_id=execution_id,
    )
    initial_state.update({
        "metrics_outputs": metrics_outputs,
        "activity_outputs": activity_outputs,
        "physiology_outputs": physiology_outputs,
        "plots": plots or [],
        "available_plots": available_plots or [],
    })

    async for chunk in create_planning_workflow().astream(
        initial_state,
        config=config,
        stream_mode="values",
    ):
        logger.info("Planning workflow step: %s", list(chunk.keys()) if chunk else "None")
        final_state = chunk

    return final_state


def create_analysis_and_season_workflow():
    """Analysis branch + season_planner, WITHOUT weekly_planner.

    Plan Maestro v4 Track B: the first "Generar Plan" click should only
    produce the macrocycle (season plan) -- the athlete then generates each
    week on demand via `create_week_generation_workflow` below, with fresh
    short-window data instead of every week being written up front against
    data that's stale by the time week 4 arrives. This is
    create_integrated_analysis_and_planning_workflow() with the
    weekly_planner/plan_formatter nodes and edges removed; everything else
    (the parallel analysis branch, the finalize barrier) is identical."""
    LangSmithConfig.setup_langsmith()

    workflow = StateGraph(TrainingAnalysisState)

    workflow.add_node("metrics_summarizer", metrics_summarizer_node)
    workflow.add_node("physiology_summarizer", physiology_summarizer_node)
    workflow.add_node("activity_summarizer", activity_summarizer_node)

    workflow.add_node("metrics_expert", metrics_expert_node)
    workflow.add_node("physiology_expert", physiology_expert_node)
    workflow.add_node("activity_expert", activity_expert_node)

    workflow.add_node("synthesis", synthesis_node)
    workflow.add_node("formatter", formatter_node)
    workflow.add_node("plot_resolution", plot_resolution_node)

    workflow.add_node("master_orchestrator", master_orchestrator_node)
    workflow.add_node("data_integration", data_integration_node)
    workflow.add_node("season_planner", season_planner_node)

    # Same defer=True barrier gotcha as the integrated workflow below -- MUST
    # return {}, never the full state (see that function's comment for why).
    workflow.add_node("finalize", lambda state: {}, defer=True)

    workflow.add_edge(START, "metrics_summarizer")
    workflow.add_edge(START, "physiology_summarizer")
    workflow.add_edge(START, "activity_summarizer")

    # El coach mira los tres resumenes y le pregunta al atleta lo que los
    # datos no dicen, UNA vez, antes de que los expertos analicen. Ver
    # services/ai/preguntas_del_coach.py -- y por que no se usa el relanzado
    # de expertos del orquestador.
    workflow.add_node("preguntas_del_coach", preguntas_del_coach_node)
    workflow.add_edge(["metrics_summarizer", "physiology_summarizer", "activity_summarizer"],
                      "preguntas_del_coach")
    workflow.add_edge("preguntas_del_coach", "metrics_expert")
    workflow.add_edge("preguntas_del_coach", "physiology_expert")
    workflow.add_edge("preguntas_del_coach", "activity_expert")

    workflow.add_edge(["metrics_expert", "physiology_expert", "activity_expert"], "master_orchestrator")
    # master_orchestrator routes via Command(goto=["synthesis", "data_integration"])
    # once experts finish -- no static edge out of it, see orchestrator_node.py.

    workflow.add_edge("synthesis", "formatter")
    workflow.add_edge("formatter", "plot_resolution")

    workflow.add_edge("data_integration", "season_planner")

    workflow.add_edge("plot_resolution", "finalize")
    workflow.add_edge("season_planner", "finalize")
    workflow.add_edge("finalize", END)

    checkpointer = MemorySaver()
    app = workflow.compile(checkpointer=checkpointer)
    logger.info(
        "Created analysis + season-only workflow (no weekly_planner): "
        "3 summarizers → 3 experts → [analysis branch || season_planner] → finalize"
    )
    return app


def create_week_generation_workflow():
    """Just enough graph to produce one week's WeeklyPlanPayload: the analysis
    branch (fresh, short-window data) feeding straight into weekly_planner.

    Deliberately skips season_planner (the season plan is loaded from disk and
    passed in pre-built via initial_state["season_plan"] -- see
    weekly_planner_node.py's extract_season_plan_text/_load_season_phases,
    both already designed to read from disk independent of graph state),
    skips synthesis/formatter/plot_resolution (no human-readable analysis
    report needed for a week-only refresh), and skips master_orchestrator
    (HITL is always disabled for this call path, so its only job -- routing
    HITL questions -- has nothing to do)."""
    LangSmithConfig.setup_langsmith()

    workflow = StateGraph(TrainingAnalysisState)

    workflow.add_node("metrics_summarizer", metrics_summarizer_node)
    workflow.add_node("physiology_summarizer", physiology_summarizer_node)
    workflow.add_node("activity_summarizer", activity_summarizer_node)

    workflow.add_node("metrics_expert", metrics_expert_node)
    workflow.add_node("physiology_expert", physiology_expert_node)
    workflow.add_node("activity_expert", activity_expert_node)

    workflow.add_node("weekly_planner", weekly_planner_node)
    workflow.add_node("plan_formatter", plan_formatter_node)

    workflow.add_edge(START, "metrics_summarizer")
    workflow.add_edge(START, "physiology_summarizer")
    workflow.add_edge(START, "activity_summarizer")

    # El coach mira los tres resumenes y le pregunta al atleta lo que los
    # datos no dicen, UNA vez, antes de que los expertos analicen. Ver
    # services/ai/preguntas_del_coach.py -- y por que no se usa el relanzado
    # de expertos del orquestador.
    workflow.add_node("preguntas_del_coach", preguntas_del_coach_node)
    workflow.add_edge(["metrics_summarizer", "physiology_summarizer", "activity_summarizer"],
                      "preguntas_del_coach")
    workflow.add_edge("preguntas_del_coach", "metrics_expert")
    workflow.add_edge("preguntas_del_coach", "physiology_expert")
    workflow.add_edge("preguntas_del_coach", "activity_expert")

    workflow.add_edge(["metrics_expert", "physiology_expert", "activity_expert"], "weekly_planner")
    workflow.add_edge("weekly_planner", "plan_formatter")
    workflow.add_edge("plan_formatter", END)

    checkpointer = MemorySaver()
    app = workflow.compile(checkpointer=checkpointer)
    logger.info(
        "Created week-generation workflow: 3 summarizers → 3 experts → weekly_planner → plan_formatter"
    )
    return app


def _build_cost_summary(final_state: dict[str, Any], execution) -> dict[str, Any]:
    """Shared by every run_* wrapper below (and the original integrated one) --
    sums each node's own real usage_metadata x model_config.py pricing (see
    node_base.create_cost_entry) instead of relying on LangSmith trace
    extraction, which reports $0.0000 whenever LANGSMITH_API_KEY isn't
    configured (it never is here)."""
    node_costs = final_state.get("costs", []) or []
    total_cost_usd = sum(c.get("cost_usd", 0.0) for c in node_costs)
    total_tokens = sum(c.get("tokens", 0) for c in node_costs)

    return {
        "total_cost_usd": total_cost_usd,
        "total_tokens": total_tokens,
        "total_execution_time_seconds": execution.execution_time_seconds,
        "agent_count": len(node_costs),
        "agents": [
            {
                "name": c.get("agent"),
                "cost_usd": c.get("cost_usd", 0.0),
                "tokens": c.get("tokens", 0),
                "input_tokens": c.get("input_tokens", 0),
                "output_tokens": c.get("output_tokens", 0),
                "execution_time_seconds": c.get("execution_time", 0.0),
                "models": c.get("models", []),
            }
            for c in node_costs
        ],
    }


async def run_analysis_and_season_planning(
    user_id: str,
    athlete_name: str,
    garmin_data: dict,
    analysis_context: str = "",
    planning_context: str = "",
    competitions: list | None = None,
    current_date: dict | None = None,
    on_progress: Callable[[str], None] | None = None,
    plotting_enabled: bool = False,
    hitl_enabled: bool = True,
    skip_synthesis: bool = False,
    availability_constraints: dict | None = None,
    prescription: dict | None = None,
    adherence: dict | None = None,
) -> dict:
    """Runs analysis + season_planner only (no weekly_planner) -- the first
    "Generar Plan" click under Plan Maestro v4 Track B.

    The last three arguments were added in v5 Track 2. Before that, the season
    planner -- the agent whose output frames every week that follows -- received
    only the athlete's name, the date, the competitions and expert prose. It set
    a weekly hours target per phase without ever seeing what the athlete
    actually trains, and evaluated race goals without ever seeing a predicted
    time. Every one of these values already existed; none of them had a wire."""
    execution_id = f"{user_id}_{datetime.now().strftime('%Y%m%d_%H%M%S')}_season"
    # Un proyecto de LangSmith POR ATLETA. Los constructores de grafo llaman a
    # `setup_langsmith()` sin nombre, o sea al proyecto por defecto, y con eso
    # las generaciones de todos los atletas caian en el mismo sitio: mirar una
    # corrida concreta obligaba a filtrar a mano entre las de todos. La funcion
    # por usuario existia desde el principio y no la llamaba nadie.
    #
    # Va aqui y no en el constructor porque es aqui donde se sabe de quien es
    # la generacion. Si no hay LANGSMITH_API_KEY no hace nada y devuelve False:
    # la observabilidad es opcional y su ausencia no puede costar una corrida.
    configure_langsmith_for_user(user_id, "season")
    cost_tracker = WorkflowCostTracker(f"garmin_ai_coach_{user_id}")
    prompt_capture = PromptCapture()

    estado_inicial = cast("dict[str, Any]", create_initial_state(
        user_id=user_id,
        athlete_name=athlete_name,
        garmin_data=garmin_data,
        analysis_context=analysis_context,
        planning_context=planning_context,
        competitions=competitions,
        current_date=current_date,
        execution_id=execution_id,
        plotting_enabled=plotting_enabled,
        hitl_enabled=hitl_enabled,
        skip_synthesis=skip_synthesis,
        availability_constraints=availability_constraints,
        prescription=prescription,
        adherence=adherence,
    ))
    # El macrociclo lee `for_season_planner` y el informe `for_synthesis`; nadie lee
    # `for_weekly_planner` aquí. Ver expert_outputs.salida_para.
    estado_inicial["receptores_de_expertos"] = (
        ["for_season_planner"] if skip_synthesis else ["for_synthesis", "for_season_planner"])
    final_state, execution = await cost_tracker.run_workflow_with_cost_tracking(
        create_analysis_and_season_workflow(),
        estado_inicial,
        execution_id,
        user_id,
        on_node_progress=on_progress,
        callbacks=[prompt_capture],
    )
    # v6 Track 1. Set on the final state (a plain dict by this point, after
    # streaming) rather than carried as a graph field, so it never touches the
    # accumulator reducers in TrainingAnalysisState -- see the finalize
    # barrier's comment for what re-merging an accumulated field costs.
    final_state["prompt_calls"] = prompt_capture.calls

    cost_summary = _build_cost_summary(final_state, execution)
    final_state["cost_summary"] = cost_summary
    final_state["execution_metadata"] = {
        "trace_id": execution.trace_id,
        "root_run_id": execution.root_run_id,
        "execution_time_seconds": execution.execution_time_seconds,
        "total_cost_usd": cost_summary["total_cost_usd"],
        "total_tokens": cost_summary["total_tokens"],
    }
    logger.info(
        "Season planning workflow complete for user %s: $%.4f (%d tokens, %d node calls)",
        user_id, cost_summary["total_cost_usd"], cost_summary["total_tokens"], len(final_state.get("costs", []) or []),
    )
    return final_state


async def run_week_generation(
    user_id: str,
    athlete_name: str,
    garmin_data: dict,
    planning_context: str = "",
    competitions: list | None = None,
    current_date: dict | None = None,
    week_dates: list | None = None,
    on_progress: Callable[[str], None] | None = None,
    availability_constraints: dict | None = None,
    prescription: dict | None = None,
    adherence: dict | None = None,
    season_plan: dict | None = None,
) -> dict:
    """Runs the analysis branch (fresh, short-window Garmin data) + a single
    week's weekly_planner call -- the "Generar Semana" button under Plan
    Maestro v4 Track B. `season_plan` should be `{"output": <season plan
    markdown text>}` (see extract_season_plan_text's plain-string case) --
    the season plan is loaded from disk by the caller, not regenerated here."""
    execution_id = f"{user_id}_{datetime.now().strftime('%Y%m%d_%H%M%S')}_week"
    configure_langsmith_for_user(user_id, "week")
    cost_tracker = WorkflowCostTracker(f"garmin_ai_coach_{user_id}")
    prompt_capture = PromptCapture()

    initial_state = cast("dict[str, Any]", create_initial_state(
        user_id=user_id,
        athlete_name=athlete_name,
        garmin_data=garmin_data,
        planning_context=planning_context,
        competitions=competitions,
        current_date=current_date,
        week_dates=week_dates,
        execution_id=execution_id,
        hitl_enabled=False,
        availability_constraints=availability_constraints,
        prescription=prescription,
        adherence=adherence,
    ))
    initial_state["season_plan"] = season_plan
    # En una semana sólo lee a los expertos la decisión semanal: ni síntesis ni
    # macrociclo. Ver expert_outputs.salida_para (2026-09-23, ~0,04 USD por semana).
    initial_state["receptores_de_expertos"] = ["for_weekly_planner"]

    final_state, execution = await cost_tracker.run_workflow_with_cost_tracking(
        create_week_generation_workflow(),
        initial_state,
        execution_id,
        user_id,
        on_node_progress=on_progress,
        callbacks=[prompt_capture],
    )
    # v6 Track 1. Set on the final state (a plain dict by this point, after
    # streaming) rather than carried as a graph field, so it never touches the
    # accumulator reducers in TrainingAnalysisState -- see the finalize
    # barrier's comment for what re-merging an accumulated field costs.
    final_state["prompt_calls"] = prompt_capture.calls

    cost_summary = _build_cost_summary(final_state, execution)
    final_state["cost_summary"] = cost_summary
    final_state["execution_metadata"] = {
        "trace_id": execution.trace_id,
        "root_run_id": execution.root_run_id,
        "execution_time_seconds": execution.execution_time_seconds,
        "total_cost_usd": cost_summary["total_cost_usd"],
        "total_tokens": cost_summary["total_tokens"],
    }
    logger.info(
        "Week generation workflow complete for user %s: $%.4f (%d tokens, %d node calls)",
        user_id, cost_summary["total_cost_usd"], cost_summary["total_tokens"], len(final_state.get("costs", []) or []),
    )
    return final_state


def create_integrated_analysis_and_planning_workflow():
    LangSmithConfig.setup_langsmith()

    workflow = StateGraph(TrainingAnalysisState)

    workflow.add_node("metrics_summarizer", metrics_summarizer_node)
    workflow.add_node("physiology_summarizer", physiology_summarizer_node)
    workflow.add_node("activity_summarizer", activity_summarizer_node)

    workflow.add_node("metrics_expert", metrics_expert_node)
    workflow.add_node("physiology_expert", physiology_expert_node)
    workflow.add_node("activity_expert", activity_expert_node)

    workflow.add_node("synthesis", synthesis_node)
    workflow.add_node("formatter", formatter_node)
    workflow.add_node("plot_resolution", plot_resolution_node)

    workflow.add_node("master_orchestrator", master_orchestrator_node)
    workflow.add_node("data_integration", data_integration_node)
    workflow.add_node("season_planner", season_planner_node)
    workflow.add_node("weekly_planner", weekly_planner_node)
    workflow.add_node("plan_formatter", plan_formatter_node)

    # defer=True makes this a barrier: it only runs once both the analysis
    # branch (plot_resolution) and the planning branch (plan_formatter) have
    # completed, purely to give both a common node before END. It must
    # return {} (no-op), NOT the full state: LangGraph treats a node's return
    # value as a state UPDATE, and every accumulator-reducer field in
    # TrainingAnalysisState (costs, errors, plots, available_plots, every
    # *_messages list -- see training_analysis_state.py's `x + y` reducers)
    # would have the CURRENT state's own value re-merged into itself, i.e.
    # silently doubled. Confirmed with an isolated repro during Fase D's
    # end-to-end review: a real generation reported exactly 2x the athlete's
    # actual API cost ($1.30 shown, ~$0.65 actually spent -- no LLM call ran
    # twice, only the reported number was wrong) because this line used to
    # be `lambda state: state`.
    workflow.add_node("finalize", lambda state: {}, defer=True)

    workflow.add_edge(START, "metrics_summarizer")
    workflow.add_edge(START, "physiology_summarizer")
    workflow.add_edge(START, "activity_summarizer")

    workflow.add_edge("metrics_summarizer", "metrics_expert")
    workflow.add_edge("physiology_summarizer", "physiology_expert")
    workflow.add_edge("activity_summarizer", "activity_expert")

    workflow.add_edge(["metrics_expert", "physiology_expert", "activity_expert"], "master_orchestrator")

    # Master orchestrator uses ONLY Command(goto=...) for dynamic routing
    # NO unconditional edges from orchestrator - it routes dynamically based on stage

    workflow.add_edge("synthesis", "formatter")
    workflow.add_edge("formatter", "plot_resolution")


    # Data integration → season planner → weekly planner → orchestrator
    # (sequential, not parallel: weekly_planner reads the season plan's phase
    # intent from state, so season_planner must complete first)
    workflow.add_edge("data_integration", "season_planner")
    workflow.add_edge("season_planner", "weekly_planner")
    workflow.add_edge("weekly_planner", "master_orchestrator")

    workflow.add_edge("plot_resolution", "finalize")
    workflow.add_edge("plan_formatter", "finalize")
    workflow.add_edge("finalize", END)

    checkpointer = MemorySaver()
    app = workflow.compile(checkpointer=checkpointer)
    logger.info(
        "Created integrated analysis + planning workflow with parallel architecture: "
        "3 summarizers → 3 experts → [analysis branch (synthesis/formatter/plots) || "
        "planning branch (data_integration → season_planner → weekly_planner → plan_formatter)] → finalize"
    )

    return app


async def run_complete_analysis_and_planning(
    user_id: str,
    athlete_name: str,
    garmin_data: dict,
    analysis_context: str = "",
    planning_context: str = "",
    competitions: list | None = None,
    current_date: dict | None = None,
    week_dates: list | None = None,
    on_progress: Callable[[str], None] | None = None,
    plotting_enabled: bool = False,
    hitl_enabled: bool = True,
    skip_synthesis: bool = False,
    availability_constraints: dict | None = None,
    prescription: dict | None = None,
    adherence: dict | None = None,
) -> dict:
    execution_id = f"{user_id}_{datetime.now().strftime('%Y%m%d_%H%M%S')}_complete"
    cost_tracker = WorkflowCostTracker(f"garmin_ai_coach_{user_id}")
    prompt_capture = PromptCapture()

    final_state, execution = await cost_tracker.run_workflow_with_cost_tracking(
        create_integrated_analysis_and_planning_workflow(),
        cast("dict[str, Any]", create_initial_state(
            user_id=user_id,
            athlete_name=athlete_name,
            garmin_data=garmin_data,
            analysis_context=analysis_context,
            planning_context=planning_context,
            competitions=competitions,
            current_date=current_date,
            week_dates=week_dates,
            execution_id=execution_id,
            plotting_enabled=plotting_enabled,
            hitl_enabled=hitl_enabled,
            skip_synthesis=skip_synthesis,
            availability_constraints=availability_constraints,
            prescription=prescription,
            adherence=adherence,
        )),
        execution_id,
        user_id,
        on_node_progress=on_progress,
        callbacks=[prompt_capture],
    )
    # v6 Track 1. Set on the final state (a plain dict by this point, after
    # streaming) rather than carried as a graph field, so it never touches the
    # accumulator reducers in TrainingAnalysisState -- see the finalize
    # barrier's comment for what re-merging an accumulated field costs.
    final_state["prompt_calls"] = prompt_capture.calls

    # Real per-generation cost: sum the "costs" entries each node appended to
    # state (see node_base.create_cost_entry), computed from that node's own
    # LLM call(s) actual usage_metadata x model_config.py's price table. This
    # replaces the old sole cost source (LangSmith trace extraction via
    # execution.cost_summary), which reported $0.0000 whenever
    # LANGSMITH_API_KEY wasn't configured -- which was always the case here,
    # so cost was always reported as zero regardless of how much a generation
    # actually spent.
    node_costs = final_state.get("costs", []) or []
    total_cost_usd = sum(c.get("cost_usd", 0.0) for c in node_costs)
    total_tokens = sum(c.get("tokens", 0) for c in node_costs)

    final_state["cost_summary"] = {
        "total_cost_usd": total_cost_usd,
        "total_tokens": total_tokens,
        "total_execution_time_seconds": execution.execution_time_seconds,
        "agent_count": len(node_costs),
        "agents": [
            {
                "name": c.get("agent"),
                "cost_usd": c.get("cost_usd", 0.0),
                "tokens": c.get("tokens", 0),
                "input_tokens": c.get("input_tokens", 0),
                "output_tokens": c.get("output_tokens", 0),
                "execution_time_seconds": c.get("execution_time", 0.0),
                "models": c.get("models", []),
            }
            for c in node_costs
        ],
    }
    final_state["execution_metadata"] = {
        "trace_id": execution.trace_id,
        "root_run_id": execution.root_run_id,
        "execution_time_seconds": execution.execution_time_seconds,
        "total_cost_usd": total_cost_usd,
        "total_tokens": total_tokens,
    }
    logger.info(
        "Workflow complete for user %s: $%.4f (%d tokens, %d node calls)",
        user_id, total_cost_usd, total_tokens, len(node_costs),
    )

    return final_state
