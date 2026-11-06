from pathlib import Path

from jinja2 import Environment, FileSystemLoader, select_autoescape

from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload, slot_suffix

_TEMPLATES_DIR = Path(__file__).parent / "templates"
_env = Environment(
    loader=FileSystemLoader(str(_TEMPLATES_DIR)),
    autoescape=select_autoescape(["html", "jinja2"]),
)

_PLACEHOLDER_HTML = """<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Plan Semanal</title></head>
<body style="font-family:sans-serif;background:#0f1115;color:#e2e8f0;padding:2rem;">
<h1>Plan no disponible</h1>
<p>{message}</p>
</body></html>"""


def render_weekly_plan_html(payload: WeeklyPlanPayload | None) -> str:
    if payload is None or not isinstance(payload, WeeklyPlanPayload):
        return _PLACEHOLDER_HTML.format(
            message="El plan semanal aun no esta listo (puede estar esperando respuestas del atleta o haber fallado la generacion)."
        )

    template = _env.get_template("weekly_plan.html.jinja2")
    # Misma etiqueta que en el nombre del entreno de Garmin: si la plantilla
    # se hiciera la suya, volveria a divergir como divergio con (AM)/(PM).
    return template.render(payload=payload, slot_suffix=slot_suffix)
