"""Render the analysis report. No LLM call.

Plan Maestro v5 Track 9. This node used to send the synthesis markdown to a
model with "Create a complete HTML document... Clean CSS, responsive layout,
professional typography" and keep whatever came back. Three costs, all real:

- **The design changed every run.** Nothing could be maintained, reviewed or
  trusted, and the athlete got a differently-shaped document each time.
- **It cost a model call per generation to produce CSS**, on top of the call
  that produced the actual content.
- **It was the last surface still emitting English** into a Spanish product,
  because a formatting model reproduces the language of whatever it is
  imitating rather than the language of the athlete.

The content is unchanged -- it is still the synthesis agent's own writing. Only
the presentation moved into a Jinja2 template with the shared design system
(see templates/_report_style.html.jinja2), where it is deterministic and free.

The node is kept in the graph rather than removed: `plan_formatter_node` next
to it is deterministic too and stayed a node, the workflow shape is verified by
tests, and a node that no longer costs anything is not worth an edge rewrite.
"""

import logging
from datetime import datetime

from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState
from services.ai.rendering.printable_renderer import render_analysis_report

from .tool_calling_helper import extract_text_content

logger = logging.getLogger(__name__)


def _headline_stats(state: TrainingAnalysisState) -> list[dict[str, str]]:
    """The few numbers worth putting above the narrative.

    Only ones we actually have: a stat tile showing "—" tells the athlete
    nothing and takes the place of one that would have.
    """
    stats: list[dict[str, str]] = []

    prescription = state.get("prescription") or {}
    threshold = prescription.get("threshold_pace_sec_per_km")
    if threshold:
        minutes, seconds = divmod(int(round(threshold)), 60)
        # De dónde sale, dicho como es. Ponía "de tu Garmin" siempre, y el informe
        # del atleta Hugo (2026-09-07) decía "4:27/km de tu Garmin" justo encima
        # del aviso de que el umbral de Garmin se había descartado.
        origen = {
            "garmin_lt": "de tu Garmin",
            "garmin_ajustado": "ajustado desde el de tu Garmin",
            "test": "de tu test de umbral",
            "manual": "el que escribiste en tu perfil",
            "race_prediction": "estimado de tus predicciones de carrera",
        }.get(str(prescription.get("threshold_pace_source") or ""), "")
        stats.append({
            "label": "Ritmo umbral",
            "value": f"{minutes}:{seconds:02d}/km",
            "note": origen,
        })

    baseline = (state.get("availability_constraints") or {}).get("volume_baseline") or {}
    chronic = baseline.get("chronic_weekly_hours")
    if chronic:
        # "Volumen", no "carga": desde la v1.2 la carga es la de Garmin (EPOC) y
        # esto son horas. La tarjeta decía "Carga crónica 6.0 h/sem".
        stats.append({
            "label": "Volumen semanal",
            "value": f"{chronic} h/sem",
            "note": f"últimos {baseline.get('chronic_window_days', 28)} días",
        })

    adherence = state.get("adherence") or {}
    pct = adherence.get("adherence_pct")
    if pct is not None:
        stats.append({
            "label": "Adherencia",
            "value": f"{round(pct)}%",
            "note": "al plan anterior",
        })

    recovery = (state.get("availability_constraints") or {}).get("recovery") or {}
    hrv_status = recovery.get("hrv_status")
    if hrv_status:
        stats.append({"label": "Estado HRV", "value": str(hrv_status), "note": "según Garmin"})

    return stats


async def formatter_node(state: TrainingAnalysisState) -> dict[str, list | str]:
    logger.info("Starting analysis report renderer (deterministic, no LLM)")

    agent_start_time = datetime.now()

    try:
        synthesis_markdown = extract_text_content(state.get("synthesis_result", ""))

        analysis_html = render_analysis_report(
            athlete_name=state.get("athlete_name") or "Atleta",
            synthesis_markdown=synthesis_markdown,
            generated_at=(state.get("current_date") or {}).get("date"),
            headline_stats=_headline_stats(state),
            warnings=[w for w in ((state.get("prescription") or {}).get("warnings") or []) if w],
        )

        execution_time = (datetime.now() - agent_start_time).total_seconds()
        logger.info("Analysis report rendered in %.2fs", execution_time)

        return {
            "analysis_html": analysis_html,
            # No usage_metadata: there is no model call to account for. The
            # entry is kept so the report still lists every stage that ran.
            "costs": [{
                "agent": "formatter",
                "execution_time": execution_time,
                "timestamp": datetime.now().isoformat(),
            }],
        }

    except Exception as exc:
        logger.exception("Analysis report renderer failed")
        return {"errors": [f"Analysis report rendering failed: {exc!s}"]}
