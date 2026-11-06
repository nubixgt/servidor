import logging
from datetime import datetime

from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload
from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState
from services.ai.rendering.plan_renderer import render_weekly_plan_html

logger = logging.getLogger(__name__)


async def plan_formatter_node(state: TrainingAnalysisState) -> dict[str, list | str]:
    """Renders the structured weekly plan into a standalone HTML document.

    Deterministic (no LLM call): the plan is already structured data by the
    time it reaches this node, so reformatting it via a second LLM pass would
    just add cost and hallucination risk for zero benefit.
    """
    logger.info("Starting plan formatter node")

    agent_start_time = datetime.now()

    try:
        weekly_plan = state.get("weekly_plan")
        payload = None
        if weekly_plan is not None:
            output = getattr(weekly_plan, "output", None)
            if isinstance(output, WeeklyPlanPayload):
                payload = output

        planning_html = render_weekly_plan_html(payload)

        execution_time = (datetime.now() - agent_start_time).total_seconds()
        logger.info("Plan formatting completed in %.2fs", execution_time)

        return {
            "planning_html": planning_html,
            "costs": [
                {
                    "agent": "plan_formatter",
                    "execution_time": execution_time,
                    "timestamp": datetime.now().isoformat(),
                }
            ],
        }

    except Exception as exc:
        logger.exception("Plan formatter node failed")
        return {"errors": [f"Plan formatting failed: {exc!s}"]}
