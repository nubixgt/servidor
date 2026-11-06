import logging
import uuid
from collections.abc import Callable
from dataclasses import dataclass
from datetime import datetime
from typing import Any

from .langsmith_cost_extractor import LangSmithCostExtractor, WorkflowCostSummary

logger = logging.getLogger(__name__)


@dataclass
class WorkflowExecution:
    trace_id: str
    root_run_id: str
    start_time: datetime
    end_time: datetime | None = None
    execution_time_seconds: float = 0.0
    cost_summary: WorkflowCostSummary | None = None


# Spanish, user-facing labels for each graph node's completion -- used to
# drive execution_status["status"] in web_app/server.py with what's actually
# happening instead of a handful of fixed timer-based percentages. Not every
# node needs an entry (deterministic/instant nodes like "finalize" don't add
# useful signal); nodes not listed here are silently ignored by the caller.
NODE_STATUS_LABELS_ES: dict[str, str] = {
    # Lo lee el atleta en la barra de progreso: con tildes (auditoría del 2026-09-24).
    "metrics_summarizer": "Resumiendo métricas de entrenamiento...",
    "physiology_summarizer": "Resumiendo datos fisiológicos...",
    "activity_summarizer": "Resumiendo actividades recientes...",
    "metrics_expert": "Analizando métricas de carga y forma...",
    "physiology_expert": "Analizando señales fisiológicas...",
    "activity_expert": "Analizando actividades recientes...",
    "data_integration": "Integrando datos para la planificación...",
    "season_planner": "Generando plan de temporada...",
    "weekly_planner": "Generando plan semanal...",
    "synthesis": "Sintetizando el informe de análisis...",
    "formatter": "Dando formato al informe...",
    "plot_resolution": "Preparando gráficos...",
    "plan_formatter": "Dando formato al plan de entrenamiento...",
}


class WorkflowCostTracker:

    def __init__(self, project_name: str = "garmin_ai_coach_analysis"):
        self.project_name = project_name
        self.cost_extractor = LangSmithCostExtractor()

    async def run_workflow_with_cost_tracking(
        self,
        workflow_app,
        initial_state: dict[str, Any],
        thread_id: str,
        user_id: str | None = None,
        on_node_progress: Callable[[str], None] | None = None,
        callbacks: list[Any] | None = None,
    ) -> tuple[dict[str, Any], WorkflowExecution]:

        root_run_id = uuid.uuid4()

        execution = WorkflowExecution(
            trace_id=str(root_run_id),  # For root runs, trace_id equals run_id
            root_run_id=str(root_run_id),
            start_time=datetime.now(),
        )

        final_state = dict(initial_state) if initial_state else {}

        try:
            logger.info("Starting workflow execution with deterministic root_run_id: %s", root_run_id)

            config = {
                "run_id": root_run_id,
                "run_name": "garmin_ai_coach_workflow",
                "tags": [
                    f"user:{user_id}" if user_id else "user:unknown",
                    "app:garmin_ai_coach",
                    f"thread:{thread_id}" if thread_id else "thread:none",
                ],
                "metadata": {
                    "user_id": user_id,
                    "thread_id": thread_id,
                    "project": self.project_name,
                },
            }

            if thread_id:
                config["configurable"] = {"thread_id": thread_id}

            # LangGraph propagates run-config callbacks down to every node and
            # every nested chain, which is how services/ai/prompt_capture.py
            # sees each agent's real prompt without a single node knowing it
            # exists -- including the plan validator's tier-2 repair calls.
            if callbacks:
                config["callbacks"] = list(callbacks)

            prev_lengths: dict[str, int | None] = {"analysis_html": None, "planning_html": None}

            # Three stream modes at once: "values" gives the full cumulative
            # state (used to build final_state, same as before); "updates"
            # yields {node_name: partial_state} right when each node finishes,
            # which is what lets us report real per-node progress instead of
            # fixed timer-based percentages; "custom" carries whatever a node
            # pushes via langgraph.config.get_stream_writer() mid-execution --
            # weekly_planner_node.py uses this to report "semana N de 4"
            # during its multi-call chunked generation, which a per-node
            # "updates" event alone can't see (the whole node is one event).
            async for stream_mode, chunk in workflow_app.astream(
                initial_state, config=config, stream_mode=["values", "updates", "custom"]
            ):
                if stream_mode == "values":
                    if not chunk:
                        continue
                    final_state = chunk
                    for _key in ("analysis_html", "planning_html"):
                        if chunk.get(_key):
                            curr_len = len(str(chunk[_key]))
                            if prev_lengths[_key] != curr_len:
                                logger.info("%s updated: %d chars", _key, curr_len)
                                prev_lengths[_key] = curr_len
                            else:
                                logger.debug("%s unchanged: %d chars", _key, curr_len)

                elif stream_mode == "updates" and on_node_progress and isinstance(chunk, dict):
                    for node_name in chunk:
                        label = NODE_STATUS_LABELS_ES.get(node_name)
                        if label:
                            on_node_progress(label)

                elif stream_mode == "custom" and on_node_progress and isinstance(chunk, dict):
                    status = chunk.get("status")
                    if status:
                        on_node_progress(status)

            logger.info(
                "Workflow execution complete with deterministic trace_id: %s",
                execution.trace_id,
            )

            execution.end_time = datetime.now()
            execution.execution_time_seconds = (
                execution.end_time - execution.start_time
            ).total_seconds()

            # Best-effort supplementary cost cross-check via LangSmith, kept
            # for whoever has LANGSMITH_API_KEY configured -- NOT the primary
            # cost source any more. The real per-generation cost is computed
            # from each node's own usage_metadata (see node_base.create_cost_entry)
            # and aggregated in planning_workflow.run_complete_analysis_and_planning
            # from final_state["costs"], because this LangSmith path silently
            # returns $0.0000 whenever the API key isn't set, which was always
            # the case here and was the whole "cost always shows zero" bug.
            try:
                execution.cost_summary = self.cost_extractor.extract_workflow_costs_by_trace(
                    execution.trace_id, execution.execution_time_seconds
                )
                execution.cost_summary.root_run_id = execution.root_run_id
            except Exception as e:
                logger.warning("LangSmith cost cross-check unavailable for trace %s: %s", execution.trace_id, e)
                execution.cost_summary = self.cost_extractor._zero_workflow_summary(execution.trace_id)

        except Exception as e:
            logger.error("Error in workflow execution: %s", e)
            execution.end_time = datetime.now()
            execution.execution_time_seconds = (
                execution.end_time - execution.start_time
            ).total_seconds()
            execution.cost_summary = self.cost_extractor._zero_workflow_summary(execution.trace_id)
            raise

        return final_state, execution
