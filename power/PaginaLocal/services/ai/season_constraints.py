"""Which of the season plan's standing constraints apply to a given week.

Plan Maestro v5 Track 3.

"Cycling as the primary volume driver to protect the knee", "flat terrain
only", "keep ACWR under 1.2" -- these used to exist only as sentences inside
`SeasonPlanContent.markdown`. The weekly planner had to find them in prose, and
a constraint born from a knee niggle that resolved in June still governed the
plan in November, because nothing about it had a start, an expiry, or a review.

## What is deterministic here, and what is not

The **only** deterministic behaviour in this module is date filtering and
labelling. There is no rule that checks a plan against a constraint, and there
will not be one: writing a validator check per constraint is exactly the
guardrail pattern this project avoids, because it lets a sentence written six
weeks ago veto a coaching decision made against today's data. The constraint is
made unambiguous for the planner; no policeman is placed behind it.

## Why an expired constraint is downgraded rather than dropped

Silently dropping a constraint because a review date passed would quietly
delete a declared medical limitation. Silently keeping it forever is the bug
this track exists to fix. So an expired constraint is still shown, explicitly
labelled as overdue for review and demoted to a weak suggestion. Clearing a
constraint for real is done by the season planner marking it `resuelta` on a
re-evaluation -- a decision, made by an agent looking at fresh data, not a
side effect of the calendar.
"""

from __future__ import annotations

from datetime import date, datetime
from typing import Any

_KIND_LABELS_ES = {
    "carga": "Carga",
    "terreno": "Terreno",
    "sesgo_deporte": "Deporte",
    "tecnica": "Técnica",
    "medica": "Salud",
    "logistica": "Logística",
    "otra": "Otra",
}

_SOURCE_LABELS_ES = {
    "experto_fisiologia": "experto de fisiología",
    "experto_metricas": "experto de métricas",
    "experto_actividad": "experto de actividad",
    "lesion_declarada": "lesión declarada por el atleta",
    "adherencia": "adherencia al plan previo",
    "atleta": "el propio atleta",
}


def _as_dict(constraint: Any) -> dict:
    if isinstance(constraint, dict):
        return constraint
    if hasattr(constraint, "model_dump"):
        return constraint.model_dump()
    return getattr(constraint, "__dict__", {}) or {}


def _parse_date(value: Any) -> date | None:
    try:
        return datetime.fromisoformat(str(value).strip()[:10]).date()
    except (ValueError, TypeError):
        return None


def active_constraints(constraints: list, on_date: date | None = None) -> list[dict]:
    """The constraints in force on a given date, each tagged `expired`.

    Excluded: anything the season planner marked `resuelta`, and anything whose
    `applies_from` is still in the future. A constraint with an unparseable or
    missing `applies_from` is treated as already in force -- failing open on a
    date we cannot read is safer than making a declared limitation disappear.
    """
    on_date = on_date or date.today()
    out: list[dict] = []

    for raw in constraints or []:
        data = _as_dict(raw)
        if not data.get("statement"):
            continue
        if data.get("status") == "resuelta":
            continue

        applies_from = _parse_date(data.get("applies_from"))
        if applies_from and applies_from > on_date:
            continue

        review_on = _parse_date(data.get("review_on"))
        out.append({**data, "expired": bool(review_on and review_on < on_date)})

    # Limitations first, then still-current before overdue: the planner should
    # read what actually binds before it reads what is merely suggested.
    return sorted(out, key=lambda c: (c.get("strength") != "limitacion", c["expired"]))


def render_constraints_block(constraints: list, on_date: date | None = None) -> str:
    """The block injected into the weekly training-decision prompt.

    Returns "" when nothing applies, so a season plan with no constraints costs
    no prompt tokens rather than emitting a heading with nothing under it.
    """
    active = active_constraints(constraints, on_date)
    if not active:
        return ""

    lines = [
        "## Restricciones del plan de temporada vigentes esta semana",
        "",
        "Filtradas por fecha: sólo aparecen las que aplican hoy. Las que el plan de temporada marcó como "
        "resueltas ya no están aquí.",
        "",
    ]

    for c in active:
        kind = _KIND_LABELS_ES.get(c.get("kind", ""), "Otra")
        source = _SOURCE_LABELS_ES.get(c.get("source", ""), c.get("source") or "sin fuente")
        strength = c.get("strength", "recomendacion")

        if c["expired"]:
            tag = "⏳ VENCIDA (pasó su fecha de revisión)"
        elif strength == "limitacion":
            tag = "LIMITACIÓN — respétala"
        else:
            tag = "RECOMENDACIÓN — puedes apartarte con motivo"

        lines.append(f"- **[{kind}] {c['statement']}**")
        lines.append(f"  - {tag} · origen: {source} · revisar el {c.get('review_on', 'sin fecha')}")
        if c.get("rationale"):
            lines.append(f"  - Por qué: {c['rationale']}")

    lines.extend([
        "",
        "**Cómo usarlas**: una `limitación` se respeta; una `recomendación` puedes contradecirla si los "
        "datos de esta semana lo justifican, diciéndolo en `week_rationale`. Una **vencida** ya no manda: "
        "trátala como una pista débil, no como una regla, hasta que el plan de temporada se reevalúe. "
        "Nada de esto te obliga a poner una sesión concreta ni te limita los tipos de entreno que puedes "
        "prescribir.",
    ])
    return "\n".join(lines)


def render_constraints_for_review(constraints: list, on_date: date | None = None) -> str:
    """The previous season's constraints, handed back for re-evaluation.

    The season planner must return a verdict on each one rather than quietly
    re-emitting or dropping it -- that verdict is the only way a resolved
    niggle ever stops shaping the plan.
    """
    existing = [_as_dict(c) for c in (constraints or []) if _as_dict(c).get("statement")]
    if not existing:
        return ""

    on_date = on_date or date.today()
    lines = [
        "## Restricciones que declaraste en el plan anterior",
        "",
        "**Debes emitir un veredicto sobre cada una** y devolverla en `constraints` con su mismo `id`:",
        "- `vigente` — sigue aplicando; actualiza `review_on` si toca.",
        "- `resuelta` — ya no es un problema. Explica en `resolution_note` qué dato lo demuestra "
        "(la molestia desapareció del registro de salud, la adherencia mejoró, la carga se estabilizó). "
        "**Una restricción resuelta deja de llegarle al planificador semanal**, así que resolverla es la "
        "única forma de que deje de moldear el plan.",
        "- `modificada` — cambió de alcance o de fuerza. Escribe el enunciado nuevo y di en "
        "`resolution_note` qué cambió.",
        "",
        "Una que recorta, aplaza, contradice o compensa con otros deportes algo que el atleta PIDE de su "
        "entrenamiento (sesiones, un mínimo por sesión, la larga de un día) sólo porque hasta ahora hacía menos "
        "no es una restricción: márcala `resuelta` y di en `resolution_note` que es una petición suya. Si la "
        "limita una evidencia real (un dolor declarado, su recuperación persistentemente por debajo de su "
        "normal, un afinamiento), sigue `vigente` y el enunciado dice cuál.",
        "",
        "Omitir una la borra en silencio, que es justo lo que no queremos.",
        "",
    ]

    for c in existing:
        review_on = _parse_date(c.get("review_on"))
        overdue = " — **su revisión ya venció**" if review_on and review_on < on_date else ""
        lines.append(
            f"- `{c.get('id', 'sin-id')}` [{c.get('status', 'vigente')}] {c['statement']}"
            f" (revisar el {c.get('review_on', 'sin fecha')}{overdue})"
        )

    return "\n".join(lines)
