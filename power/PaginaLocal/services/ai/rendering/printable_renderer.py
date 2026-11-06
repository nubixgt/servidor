"""The plan as something the athlete can print and take with them.

Deliberately no PDF library. A print-optimised HTML page handed to the
browser's own print dialog gives selectable vector text, exact control over
the calendar layout, and identical output on any OS -- where reportlab would
mean laying out a calendar by hand and weasyprint would mean a GTK stack that
is painful to install on Windows. "Guardar como PDF" in the print dialog is
one click and produces a better-looking document than either.
"""

from __future__ import annotations

import logging
import re
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Any

from jinja2 import Environment, FileSystemLoader, select_autoescape
from markupsafe import Markup

from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload
from services.ai.prescription import format_pace

logger = logging.getLogger(__name__)

_env = Environment(
    loader=FileSystemLoader(str(Path(__file__).parent / "templates")),
    autoescape=select_autoescape(["html", "jinja2"]),
)

_SPORT_LABELS_ES = {"run": "Carrera", "bike": "Bici", "swim": "Natación", "strength": "Fuerza", "other": "General"}

_PACE_CLASS_LABELS_ES = {
    "repeticiones": "Repeticiones",
    "vo2max": "VO2max",
    "umbral_tempo": "Umbral / tempo",
    "maraton": "Maratón",
    "facil": "Fácil",
    "recuperacion": "Recuperación",
}

_POWER_CLASS_LABELS_ES = {
    "anaerobico": "Anaeróbico",
    "vo2max": "VO2max",
    "umbral": "Umbral (FTP)",
    "tempo": "Tempo",
    "facil": "Fondo",
    "recuperacion": "Recuperación",
}


_DISCIPLINE_LABELS_ES = {
    "carrera": "Carrera", "bici": "Bici", "natacion": "Natación", "fuerza": "Fuerza",
    "pliometria": "Pliometría", "rehab": "Rehab", "prehab": "Prehab", "movilidad": "Movilidad",
    "tecnica": "Técnica", "brick": "Brick", "test": "Test", "descanso": "Descanso", "otro": "Otro",
}


_REPETICION = re.compile(r"^\s*\d+\s*[x×]\s*\(?\s*|\s*\)?\s*$", re.IGNORECASE)


def _plano(texto: str | None) -> str:
    return " ".join(str(texto or "").replace("''", '"').replace("”", '"').lower().split())


def repeat_extra(step: Any) -> str:
    """Lo que la línea de una serie dice que NO dicen ya sus pasos. "" si nada.

    El codificador guarda la frase del entrenador ("4x(20'' alta cadencia en Z2)")
    en el bloque y reparte su trabajo y su recuperación en los pasos de dentro,
    así que pintar las dos cosas decía la misma serie dos veces seguidas: una
    en negrita como título y otra debajo como paso. La cabecera pasa a ser
    "4×", y la frase sólo se enseña si añade algo que los pasos no tienen.
    """
    cabecera = _plano(_REPETICION.sub("", str(getattr(step, "description", "") or "")))
    if not cabecera:
        return ""
    for hijo in getattr(step, "substeps", None) or []:
        texto = _plano(_REPETICION.sub("", str(getattr(hijo, "description", "") or "")))
        if texto and (texto in cabecera or cabecera in texto):
            return ""
    return str(getattr(step, "description", "") or "")


def _zone_key(sport: str | None) -> str:
    """The athlete's HR-zone table is keyed by the extractor's canonical sport."""
    sport = (sport or "").lower()
    if sport in ("run", "bike", "swim"):
        return sport
    return "other"


def step_target(step: Any, prescription: dict[str, Any] | None = None, sport: str | None = None) -> str:
    """The step's target, read from the STRUCTURED fields, not from its prose.

    v6 Track 7. The printed sheet used to show only `step.description`, so the
    pace band the app computed from the athlete's real threshold reached the
    page only if the model happened to repeat it in the text. The numbers were
    always there; nothing rendered them.
    """
    target_type = getattr(getattr(step, "target_type", None), "value", getattr(step, "target_type", None))
    low, high = getattr(step, "target_low", None), getattr(step, "target_high", None)

    if target_type == "pace" and low and high:
        return f"{format_pace(low)}–{format_pace(high)}/km"
    if target_type == "power":
        if low and high:
            return f"{low:.0f}–{high:.0f} W"
        zone = getattr(step, "target_zone", None)
        return f"Z{zone} potencia" if zone else ""
    if target_type == "hr_zone":
        zone = getattr(step, "target_zone", None)
        if not zone:
            return ""
        # Show the athlete's own bpm next to the zone number: "Z2" alone is
        # not something you can run to without the watch in front of you.
        #
        # BY SPORT, and this is not a detail: a swim Z2 is 112-129 bpm on this
        # athlete and a run Z2 is 140-153. The first draft of this function
        # took whichever sport came first in the dict and printed swim zones
        # on a running session -- the same mistake documented in CLAUDE.md as
        # already having shipped once through the extractor.
        by_sport = (prescription or {}).get("hr_zones_by_sport") or {}
        # En un brick el deporte del DIA es "brick", que no tiene tabla y cae
        # en "other". Pero cada paso sabe de que tramo es (`leg_sport`), y las
        # zonas de bici y de carrera no son las mismas en la mayoria de los
        # atletas: la FC maxima pedaleando suele ir por debajo de la de correr.
        # Sin esto, el tramo de carrera de un brick se imprime con la tabla
        # equivocada -- el mismo fallo que este bloque documenta haber
        # cometido ya una vez entre natacion y carrera.
        del_paso = getattr(step, "leg_sport", None)
        zones = by_sport.get(_zone_key(del_paso or sport)) or by_sport.get("other")
        if zones:
            bounds = zones.get(str(zone)) or zones.get(zone)
            if bounds:
                low_bpm, high_bpm = bounds
                return f"Z{zone} ({low_bpm}–{high_bpm} ppm)" if high_bpm else f"Z{zone} ({low_bpm}+ ppm)"
        # No table for this sport: the zone number alone is honest; inventing
        # bpm from another sport's table is not.
        return f"Z{zone}"
    return ""


def _step_duration(step: Any) -> str:
    kind = getattr(step, "duration_type", None)
    value = getattr(step, "duration_value", None)
    if not value:
        return ""
    if kind == "time":
        minutes, seconds = divmod(int(value), 60)
        return f"{minutes}'{seconds:02d}\"" if seconds else f"{minutes}'"
    if kind == "distance":
        return f"{value / 1000:.1f} km" if value >= 1000 else f"{value:.0f} m"
    if kind == "reps":
        return f"{value:.0f} reps"
    return "hasta pulsar vuelta" if kind == "lap_button" else ""


# Importado, no redefinido: tres copias del mismo orden son tres
# oportunidades de que una se quede atras. Ver SLOT_ORDER en el esquema.
from services.ai.langgraph.schemas.plan_outputs import SLOT_ORDER as _SLOT_ORDER


def slot_label(day: Any, availability: dict[str, Any] | None = None) -> str:
    """Cual de las sesiones del dia es esta, en la hoja impresa.

    Decia " AM" y " PM", y eso era falso justo en el caso que mas importa: un
    atleta que corre Y nada el mismo miercoles a las 5:30 veia su segunda
    sesion impresa como "PM". Los slots siempre fueron claves de orden. La
    hora, cuando se conoce la franja declarada, sale de ahi; si no, se dice el
    orden y nada mas -- que es poco, pero es cierto.
    """
    slot = getattr(day, "session_slot", None) or "unica"
    if slot == "unica":
        return ""
    idx = getattr(day, "block_index", None)
    bloques = ((availability or {}).get("days") or {}).get(getattr(day, "day_name_es", ""), {}).get("blocks") or []
    if idx is not None and 0 <= idx < len(bloques):
        b = bloques[idx]
        return f" {b['start']}-{b['end']}"
    orden = _SLOT_ORDER.index(slot) if slot in _SLOT_ORDER else 0
    return f" {orden}a ses." if orden else ""


def week_summary(week) -> dict[str, Any]:
    """What the athlete needs at a glance, the way Runna's plan overview does it.

    Sessions, total time, the split by discipline, and -- the part no
    competitor has to worry about -- how many sessions live on the watch and
    how many have to be read off this page.
    """
    sessions = [day for day in week.days if not day.is_rest]
    minutes = sum(day.total_duration_minutes or 0 for day in sessions)
    by_discipline: dict[str, float] = {}
    for day in sessions:
        key = _DISCIPLINE_LABELS_ES.get(day.discipline or "", (day.discipline or day.sport or "Otro").title())
        by_discipline[key] = by_discipline.get(key, 0.0) + (day.total_duration_minutes or 0)

    off_watch = [day for day in sessions if day.garmin_exportable is False]
    hours, remainder = divmod(int(round(minutes)), 60)
    return {
        "sessions": len(sessions),
        "rest_days": sum(1 for day in week.days if day.is_rest),
        "total": f"{hours}h {remainder:02d}m" if hours else f"{remainder}m",
        "by_discipline": sorted(by_discipline.items(), key=lambda kv: -kv[1]),
        "off_watch": len(off_watch),
    }


def _week_volume_label(week) -> str:
    """Total planned time for a week, and how many sessions it holds."""
    minutes = sum(day.total_duration_minutes or 0 for day in week.days if not day.is_rest)
    sessions = sum(1 for day in week.days if not day.is_rest)
    if not minutes:
        return f"{sessions} sesiones"
    hours, remainder = divmod(int(round(minutes)), 60)
    duration = f"{hours}h {remainder:02d}m" if hours else f"{remainder}m"
    return f"{sessions} sesiones · {duration}"


def _reference_tables(prescription: dict[str, Any] | None) -> dict[str, Any]:
    """The athlete's own zones and bands, so the printed sheet stands alone."""
    prescription = prescription or {}

    hr_zones: dict[str, list[tuple[int, str]]] = {}
    por_deporte = prescription.get("hr_zones_by_sport") or {}
    # Solo las tres de resistencia cuando existen. Garmin tambien devuelve una
    # tabla "strength" (y "other") que en la hoja salia como "Zonas de FC --
    # strength", en ingles y con los mismos numeros que la de carrera: una
    # quinta caja que no le decia nada nuevo al atleta.
    if any(k in por_deporte for k in ("run", "bike", "swim")):
        por_deporte = {k: v for k, v in por_deporte.items() if k in ("run", "bike", "swim")}
    for sport, zones in por_deporte.items():
        rows = []
        for zone in sorted(zones, key=int):
            low, high = zones[zone]
            rows.append((int(zone), f"{low}–{high} ppm" if high else f"{low}+ ppm"))
        if rows:
            hr_zones[sport] = rows

    pace_bands = [
        (_PACE_CLASS_LABELS_ES.get(key, key), f"{format_pace(band[0])}–{format_pace(band[1])}/km")
        for key, band in (prescription.get("pace_bands") or {}).items()
    ]
    power_bands = [
        (_POWER_CLASS_LABELS_ES.get(key, key), f"{band[0]}–{band[1]} W")
        for key, band in (prescription.get("power_bands") or {}).items()
    ]

    return {
        "hr_zones": hr_zones,
        "pace_bands": pace_bands,
        "power_bands": power_bands,
        "sport_labels": _SPORT_LABELS_ES,
    }


_MESES_ES = ("ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic")


def _fecha(iso: str) -> datetime | None:
    try:
        return datetime.strptime(str(iso)[:10], "%Y-%m-%d")
    except (ValueError, TypeError):
        return None


def rango_de_fechas(fechas: list[str]) -> str:
    """"12–18 sep 2026", "28 sep – 4 oct 2026". "" si no hay fechas legibles.

    Sustituye a "4 semanas desde el ...", que la hoja decía siempre aunque el
    plan fuera de una sola semana -- desde la v4 lo es casi siempre."""
    dias = sorted(d for d in (_fecha(f) for f in fechas) if d)
    if not dias:
        return ""
    a, b = dias[0], dias[-1]
    if a == b:
        return f"{a.day} {_MESES_ES[a.month - 1]} {a.year}"
    if (a.year, a.month) == (b.year, b.month):
        return f"{a.day}–{b.day} {_MESES_ES[b.month - 1]} {b.year}"
    izquierda = f"{a.day} {_MESES_ES[a.month - 1]}" + (f" {a.year}" if a.year != b.year else "")
    return f"{izquierda} – {b.day} {_MESES_ES[b.month - 1]} {b.year}"


def columnas_de_la_semana(week) -> list[dict[str, Any]]:
    """Una columna por FECHA, con sus sesiones apiladas en orden.

    La hoja pintaba una columna por SESION: un lunes de carrera y fuerza eran
    "LUN 1A SES." y "LUN 2A SES." lado a lado, y nueve sesiones en siete dias
    partian la semana en dos filas. En la nevera se lee por dias."""
    por_fecha: dict[str, list] = {}
    for day in week.days:
        por_fecha.setdefault(day.date, []).append(day)
    columnas = []
    for fecha in sorted(por_fecha):
        sesiones = sorted(
            por_fecha[fecha],
            key=lambda d: _SLOT_ORDER.index(d.session_slot) if d.session_slot in _SLOT_ORDER else 99,
        )
        d = _fecha(fecha)
        columnas.append({
            "fecha": fecha,
            "dia": (sesiones[0].day_name_es or "")[:3],
            "numero": d.day if d else "",
            "mes": _MESES_ES[d.month - 1] if d else "",
            "descanso": all(s.is_rest for s in sesiones),
            "sesiones": [s for s in sesiones if not s.is_rest] or sesiones,
        })
    return columnas


def semanas_por_vivir(payload: WeeklyPlanPayload, hoy: str) -> WeeklyPlanPayload:
    """Las semanas que no han terminado: la en curso y la siguiente, como la pantalla de Plan.

    La hoja imprimía TODAS las del plan (hasta 12, MAX_WEEKS_KEPT) bajo "Tu semana": la de José
    del 2026-09-24 salía como "Tu semana · 23 sep – 11 oct 2026" con tres (auditoría). Si todas
    pasaron, la última.
    """
    vivas = [w for w in payload.weeks if any((d.date or "") >= hoy for d in w.days)]
    return payload.model_copy(update={"weeks": vivas or payload.weeks[-1:]})


def _cabecera_de_la_semana(week) -> dict[str, Any]:
    """Fechas, sesiones, tiempo y días de descanso de UNA semana, para su hoja."""
    entrenos = [d for d in week.days if not d.is_rest]
    minutos = int(round(sum(d.total_duration_minutes or 0 for d in entrenos)))
    return {
        "range": rango_de_fechas([d.date for d in week.days]),
        "sessions": len(entrenos),
        "time": f"{minutos // 60}h{minutos % 60:02d}m" if minutos >= 60 else f"{minutos}'",
        "rest_days": len({d.date for d in week.days} - {d.date for d in entrenos}),
    }


def render_printable_plan(
    payload: WeeklyPlanPayload,
    prescription: dict[str, Any] | None = None,
    generated_at: str | None = None,
    availability: dict[str, Any] | None = None,
    hoy: str | None = None,
) -> str:
    payload = semanas_por_vivir(payload, hoy or datetime.now().strftime("%Y-%m-%d"))
    todas = [day for week in payload.weeks for day in week.days]
    entrenos = [day for day in todas if not day.is_rest]
    minutos = int(round(sum(day.total_duration_minutes or 0 for day in entrenos)))
    return _env.get_template("printable_plan.html.jinja2").render(
        payload=payload,
        # Una hoja por semana, cada una con su cabecera (el responsable, 2026-10-22).
        week_heads={week.week_number: _cabecera_de_la_semana(week) for week in payload.weeks},
        date_range=rango_de_fechas([day.date for day in todas]),
        week_columns={week.week_number: columnas_de_la_semana(week) for week in payload.weeks},
        totals={
            "sessions": len(entrenos),
            "time": f"{minutos // 60}h{minutos % 60:02d}m" if minutos >= 60 else f"{minutos}'",
            "rest_days": len({day.date for day in todas} - {day.date for day in entrenos}),
        },
        slot_label=lambda day: slot_label(day, availability),
        generated_at=rango_de_fechas([(generated_at or "")[:10]]),
        varias_semanas=len(payload.weeks) > 1,
        week_volumes={week.week_number: _week_volume_label(week) for week in payload.weeks},
        # v6 Track 7: everything the plan already knew and the sheet never
        # showed -- why the week looks like this, what each session is for,
        # the real pace/zone behind every step, and which sessions are not on
        # the watch.
        week_summaries={week.week_number: week_summary(week) for week in payload.weeks},
        step_target=lambda step, sport=None: step_target(step, prescription, sport),
        step_duration=_step_duration,
        repeat_extra=repeat_extra,
        discipline_labels=_DISCIPLINE_LABELS_ES,
        chip_sport=_DEPORTE_DE_LA_ETIQUETA,
        **_reference_tables(prescription),
    )


# El color de cada chip del reparto semanal, que llega con la etiqueta en
# español. "Técnica" puede ser de natación o de carrera: gris antes que mentir.
_DEPORTE_DE_LA_ETIQUETA = {
    "Carrera": "run", "Bici": "bike", "Natación": "swim", "Fuerza": "strength", "Pliometría": "strength",
    "Rehab": "strength", "Prehab": "strength", "Movilidad": "strength", "Brick": "other",
}


def _weeks_between(start_date: str | None, target: datetime | None = None) -> int | None:
    if not start_date:
        return None
    try:
        start = datetime.strptime(start_date, "%Y-%m-%d")
    except (ValueError, TypeError):
        return None
    return ((target or datetime.now()) - start).days // 7


def markdown_to_html(markdown_text: str) -> str:
    """The season narrative, for the printed sheet.

    Rendered with HTML disabled: the text comes from our own agent, but an LLM
    writing raw markup into a page we then print is not a thing worth allowing.
    """
    if not markdown_text:
        return ""
    try:
        from markdown_it import MarkdownIt

        # "gfm-like", not "commonmark": CommonMark has no tables, and the
        # synthesis prompt explicitly asks for the KPI block "in table format".
        # On the plain preset that arrived on the page as a literal row of
        # pipes -- the athlete's headline numbers rendered as punctuation.
        # `html` stays False: the text is written by our own agent, but an
        # agent emitting raw markup into a page we then print is not a thing
        # worth allowing.
        return MarkdownIt("gfm-like", {"html": False, "linkify": False}).render(markdown_text)
    except Exception:
        logger.warning("Could not render the season markdown; falling back to plain text", exc_info=True)
        return ""


def _cuando(fecha_iso: str | None, hoy: date | None = None) -> dict[str, str]:
    """«20 sep 2026» y cuánto falta, contado desde HOY (2026-10-22).

    La hoja decía «2026-09-20 · en 0 sem» de un 5K ya corrido, y «en 1.1 sem» de una carrera de hacía cinco
    días: lo segundo se contó al escribir el macrociclo y nunca se volvía a contar.
    """
    d = _fecha(fecha_iso or "")
    if not d:
        return {"fecha": fecha_iso or "—", "cuando": ""}
    d = d.date() if isinstance(d, datetime) else d
    hoy = hoy or datetime.now().date()
    dias = (d - hoy).days
    if dias < 0:
        cuando = "ya corrida"
    elif dias == 0:
        cuando = "hoy"
    elif dias < 7:
        cuando = f"en {dias} {'día' if dias == 1 else 'días'}"
    else:
        semanas = dias // 7
        cuando = f"en {semanas} {'semana' if semanas == 1 else 'semanas'}"
    return {"fecha": f"{d.day} {_MESES_ES[d.month - 1]} {d.year}", "cuando": cuando}


def render_printable_season(
    athlete_name: str,
    season_phases: dict[str, Any] | None,
    competitions: list[dict[str, Any]] | None = None,
    narrative_html: str = "",
    goals: list[dict[str, Any]] | None = None,
    prescription: dict[str, Any] | None = None,
) -> str:
    """v6 Track 7: the season sheet used to be the narrative and a strip of
    weeks. It now also carries what the plan was actually built against -- the
    declared goals next to what the athlete's current fitness predicts, the
    constraints in force with their dates, and the athlete's own zones and
    paces so the page stands alone on a fridge."""
    season_phases = season_phases or {}
    phases = season_phases.get("phases") or []
    season_start = season_phases.get("season_start_date")

    weeks_elapsed = _weeks_between(season_start)
    current_week = (weeks_elapsed + 1) if weeks_elapsed is not None and weeks_elapsed >= 0 else None

    # One cell per week of the season, marking deloads and where we are now,
    # so the shape of the block is visible at a glance rather than inferred
    # from a table of ranges.
    deload_weeks = {w for phase in phases for w in (phase.get("deload_weeks") or [])}
    last_week = max((phase.get("end_week") or 0) for phase in phases) if phases else 0

    # En que semana de la temporada cae cada carrera, para marcarla en la tira.
    inicio = _fecha(season_start) if season_start else None
    semana_de_carrera: dict[int, str] = {}
    for race in competitions or []:
        d = _fecha(race.get("date") or "")
        if inicio and d and d >= inicio:
            semana_de_carrera.setdefault((d - inicio).days // 7 + 1, race.get("name") or "Carrera")

    def _fase_de(n: int) -> int | None:
        for i, phase in enumerate(phases):
            if (phase.get("start_week") or 0) <= n <= (phase.get("end_week") or 0):
                return i
        return None

    season_strip = [
        {"number": n, "is_deload": n in deload_weeks, "is_current": n == current_week,
         "phase": _fase_de(n), "race": semana_de_carrera.get(n)}
        for n in range(1, last_week + 1)
    ]
    fase_actual = phases[_fase_de(current_week)] if current_week and _fase_de(current_week) is not None else None
    fin = (inicio + timedelta(weeks=last_week) - timedelta(days=1)) if inicio and last_week else None

    races = []
    for race in competitions or []:
        races.append({
            "name": race.get("name") or "Competición",
            "date": race.get("date") or "—",
            "priority": race.get("priority"),
            **_cuando(race.get("date")),
        })
    races.sort(key=lambda r: r["date"])
    # Las metas, con la misma fecha y el mismo «cuánto falta» que las competiciones.
    goals = [{**g, **_cuando(g.get("date"))} for g in (goals or [])]

    constraints = [
        {
            "text": c.get("text") or c.get("constraint") or "",
            "kind": c.get("kind") or c.get("type") or "",
            "until": c.get("until") or c.get("review_on") or c.get("expires_on") or "",
        }
        for c in (season_phases.get("constraints") or [])
        if (c.get("text") or c.get("constraint"))
    ]

    return _env.get_template("printable_season.html.jinja2").render(
        athlete_name=athlete_name,
        phases=phases,
        goals=goals,
        constraints=constraints,
        **_reference_tables(prescription),
        season_start_date=season_start,
        current_week=current_week,
        season_strip=season_strip,
        total_weeks=last_week,
        current_phase=fase_actual,
        season_range=rango_de_fechas([season_start, fin.strftime("%Y-%m-%d")]) if inicio and fin else "",
        competitions=races,
        # Already-sanitised HTML produced by markdown rendering upstream; the
        # season narrative is written by our own agent, never by a third party.
        narrative_html=Markup(narrative_html) if narrative_html else "",
    )


def render_analysis_report(
    athlete_name: str,
    synthesis_markdown: str,
    generated_at: str | None = None,
    headline_stats: list[dict[str, str]] | None = None,
    warnings: list[str] | None = None,
) -> str:
    """The athlete's analysis report, rendered deterministically.

    v5 Track 9. This document used to be produced by asking an LLM to "create a
    complete HTML document... clean CSS, responsive layout, professional
    typography" from the synthesis markdown. Three costs, all real: the design
    changed on every run so nothing could be maintained or trusted, a model
    call was spent per generation writing CSS, and it was the last surface
    still emitting English into a Spanish product.

    The content is still the synthesis agent's own writing. Only the
    presentation moved out of the model's hands.
    """
    return _env.get_template("analysis_report.html.jinja2").render(
        athlete_name=athlete_name or "Atleta",
        generated_at=rango_de_fechas([(generated_at or "")[:10]]),
        # Sanitised by markdown_to_html (html=False), and written by our own
        # agent -- never by a third party.
        analysis_html=Markup(markdown_to_html(synthesis_markdown)) if synthesis_markdown else "",
        headline_stats=headline_stats or [],
        warnings=warnings or [],
    )
