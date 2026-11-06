from typing import Annotated, Any

from langgraph.graph import MessagesState
from services.ai.langgraph.schemas import (
    ActivityExpertOutputs,
    MetricsExpertOutputs,
    PhysiologyExpertOutputs,
    WeeklyPlannerOutputs,
)


class TrainingAnalysisState(MessagesState):
    user_id: str
    athlete_name: str
    garmin_data: dict[str, Any]
    analysis_context: str
    planning_context: str

    competitions: list[dict[str, Any]]
    current_date: dict[str, str]
    week_dates: list[dict[str, str]]
    style_guide: str
    plotting_enabled: bool
    hitl_enabled: bool
    skip_synthesis: bool
    # Qué secciones de los expertos se van a LEER en esta generación (ver
    # expert_outputs.salida_para). None = todas, como siempre.
    receptores_de_expertos: list[str] | None

    metrics_summary: str | None
    physiology_summary: str | None
    activity_summary: str | None

    metrics_outputs: MetricsExpertOutputs | None
    activity_outputs: ActivityExpertOutputs | None
    physiology_outputs: PhysiologyExpertOutputs | None

    synthesis_result: str | None

    season_plan: str | None
    weekly_plan: WeeklyPlannerOutputs | None
    weekly_plan_validation: dict[str, Any] | None
    # The decision layer's WeekIntentContent, one per generated week, BEFORE
    # any Garmin encoding -- the actual coaching call and its `week_rationale`.
    # Kept in state purely so it can be archived (services/ai/reasoning_archive.py):
    # it used to be a local in weekly_planner_node and died with the process,
    # which meant the most interesting artefact of a paid run was the one thing
    # nobody could read afterwards.
    week_intents: list[dict[str, Any]] | None
    availability_constraints: dict[str, Any] | None
    # The athlete's real numbers -- HR zones as configured on their own watch,
    # threshold pace, and the pace band per intensity class. Built in
    # services/ai/prescription.py from GarminData, rendered into the weekly
    # planner's prompt and used by the validator to check prescribed paces are
    # physiologically sane. Without this the planner sees only expert prose and
    # cannot prescribe a pace at all.
    prescription: dict[str, Any] | None
    # What the athlete actually did with the plan they were last given
    # (services/ai/adherence.py). Lets the coach respond to reality
    # instead of prescribing into a void.
    adherence: dict[str, Any] | None

    synthesis_complete: Annotated[bool, lambda x, y: x or y]
    season_plan_complete: Annotated[bool, lambda x, y: x or y]

    analysis_html: str | None
    planning_html: str | None
    plot_resolution_stats: dict[str, Any] | None

    plots: Annotated[list[dict], lambda x, y: x + y]
    plot_storage_data: Annotated[dict[str, dict], lambda x, y: {**x, **y}]
    costs: Annotated[list[dict], lambda x, y: x + y]
    errors: Annotated[list[str], lambda x, y: x + y]
    tool_usage: Annotated[dict[str, int], lambda x, y: {**x, **y}]

    available_plots: Annotated[list[str], lambda x, y: x + y]
    execution_id: str

    # Agent-specific HITL message storage (with append reducer)
    metrics_expert_messages: Annotated[list, lambda x, y: (x or []) + y]
    activity_expert_messages: Annotated[list, lambda x, y: (x or []) + y]
    physiology_expert_messages: Annotated[list, lambda x, y: (x or []) + y]
    season_planner_messages: Annotated[list, lambda x, y: (x or []) + y]
    weekly_planner_messages: Annotated[list, lambda x, y: (x or []) + y]
    # Lo que el coach le pregunto al atleta en ESTA generacion y lo que
    # contesto: [{pregunta, por_que, respuesta}]. El macrociclo lo escribe en
    # su propio informe (services/ai/preguntas_del_coach.py::seccion_de_respuestas).
    respuestas_al_coach: Annotated[list, lambda x, y: (x or []) + y]


def create_initial_state(
    user_id: str,
    athlete_name: str,
    garmin_data: dict[str, Any],
    analysis_context: str = "",
    planning_context: str = "",
    competitions: list[dict[str, Any]] | None = None,
    current_date: dict[str, str] | None = None,
    week_dates: list[dict[str, str]] | None = None,
    style_guide: str = "",
    execution_id: str = "",
    plotting_enabled: bool = False,
    hitl_enabled: bool = True,
    skip_synthesis: bool = False,
    availability_constraints: dict[str, Any] | None = None,
    prescription: dict[str, Any] | None = None,
    adherence: dict[str, Any] | None = None,
) -> TrainingAnalysisState:
    return TrainingAnalysisState(
        user_id=user_id,
        athlete_name=athlete_name,
        garmin_data=garmin_data,
        analysis_context=analysis_context,
        planning_context=planning_context,
        competitions=competitions or [],
        current_date=current_date or {},
        week_dates=week_dates or [],
        style_guide=style_guide,
        plotting_enabled=plotting_enabled,
        hitl_enabled=hitl_enabled,
        skip_synthesis=skip_synthesis,
        receptores_de_expertos=None,
        execution_id=execution_id,
        metrics_summary=None,
        physiology_summary=None,
        activity_summary=None,
        metrics_outputs=None,
        activity_outputs=None,
        physiology_outputs=None,
        synthesis_result=None,
        season_plan=None,
        weekly_plan=None,
        weekly_plan_validation=None,
        week_intents=None,
        availability_constraints=availability_constraints,
        prescription=prescription,
        adherence=adherence,
        synthesis_complete=False,
        season_plan_complete=False,
        analysis_html=None,
        planning_html=None,
        plot_resolution_stats=None,
        plots=[],
        plot_storage_data={},
        costs=[],
        errors=[],
        tool_usage={},
        available_plots=[],
        metrics_expert_messages=[],
        activity_expert_messages=[],
        physiology_expert_messages=[],
        season_planner_messages=[],
        weekly_planner_messages=[],
        respuestas_al_coach=[],
        messages=[],
    )
