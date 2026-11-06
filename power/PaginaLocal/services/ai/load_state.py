"""The athlete's real load and readiness numbers, handed to the planner intact.

Plan Maestro v6 Track 2.

## What went wrong without this

The first real generation told the athlete, in their own week's rationale:
*"permitir que tu ACWR baje de un elevado 1.47 y tu TSB de -20.6 retorne a
niveles seguros"*. The athlete opened Garmin, saw **1.10, `OPTIMAL`**, and had
no way to reconcile the two.

Track 1's replay bench established that the model did **not** invent 1.47. The
app computed it: `services/garmin/utils/training_metrics.py` derives
`acwr_7d28d` from the daily load series using **rolling averages** (7-day sum
over the 28-day mean), the metrics summarizer published it, and the metrics
expert escalated it. Garmin's own `dailyAcuteChronicWorkloadRatio` uses
**exponentially weighted averages** and reported 1.10 -- and it is from a
*different day*: 1.47 was the peak on race day, 1.10 is the state two days
later.

So the defect was never hallucination. **Two ACWRs coexist in this system, and
the athlete was shown the one that is not on their watch, with no method and
no date attached.**

## Which is why this module refuses to pick one

The obvious "fix" -- delete our own calculation and only show Garmin's -- would
destroy real information. The rolling-average series is what makes a *trend*
visible (0.32 → 0.90 → 1.31 → peak 1.47), and Garmin's single daily figure
cannot show that. Both are correct under their own definition.

So both go into the prompt, each labelled with its **method** and its **date**,
and the prompt is told they are not interchangeable. The model is then in a
position to write the only sentence that is actually true: *"tu ACWR llegó a
1.47 el día de la carrera y Garmin te marca 1.10 hoy"*.

## And why it states no thresholds of its own

Everything here is either a number Garmin measured, a status Garmin itself
assigned (`OPTIMAL`, `within`, `WELL_RECOVERED`), a range Garmin itself
published, or arithmetic over the athlete's own activities. This module invents
no safety band and no cutoff.

That is deliberate. ACWR's value as an injury *predictor* is contested in the
literature, and the ratio's behaviour depends heavily on the calculation method
-- the very disagreement this module exists to expose. It describes load. A
deterministic rule built on top of it would be a guardrail firing on a
contested signal, which is the failure mode this project has repeatedly found
worse than the problem.

## Sport neutrality

The effort history below is computed **per sport, from whatever sports the
athlete actually did**, and reports minutes and distance side by side. A
swimmer's 3,000 m and a cyclist's 90 km are summarised the same way a runner's
21 km is. Nothing here is keyed to running.
"""

from __future__ import annotations

import logging
from dataclasses import dataclass, field
from datetime import date
from typing import Any

logger = logging.getLogger(__name__)

# The window the effort history looks back over. Matches the chronic-load
# window used everywhere else (services/garmin/extraction_windows.py), so
# "recent" means the same thing in every part of a generation.
DEFAULT_EFFORT_WINDOW_DAYS = 28

_SPORT_ES = {"run": "carrera", "bike": "bici", "swim": "natación", "other": "otros"}

_BAND_ES = {
    "aerobic_low": "aeróbico bajo",
    "aerobic_high": "aeróbico alto",
    "anaerobic": "anaeróbico",
}

_STATUS_ES = {"below": "por debajo del objetivo", "within": "dentro del objetivo", "above": "por encima del objetivo"}


@dataclass
class SportEfforts:
    """What this athlete's longest sessions in one sport looked like.

    `previous_*` is the longest session EXCLUDING the longest one, which is
    what makes a step up in distance visible at all -- and no prompt in this
    app had it.

    Computing it rather than eyeballing it also corrects the eyeballing. The
    v6 audit read a 14-day activity list and concluded the athlete's 21.2 km
    race followed a longest run of 5.1 km, a 4x jump. Over the real 28-day
    window there is a 10.3 km trail run nine days earlier: the step is 2.1x.
    Still worth naming, and a different conversation.
    """

    sport: str
    sessions: int = 0
    total_minutes: float = 0.0
    longest_date: str | None = None
    longest_name: str | None = None
    longest_km: float | None = None
    longest_minutes: float | None = None
    previous_date: str | None = None
    previous_km: float | None = None
    previous_minutes: float | None = None

    @property
    def distance_jump(self) -> float | None:
        if self.longest_km and self.previous_km and self.previous_km > 0:
            return round(self.longest_km / self.previous_km, 1)
        return None

    @property
    def duration_jump(self) -> float | None:
        if self.longest_minutes and self.previous_minutes and self.previous_minutes > 0:
            return round(self.longest_minutes / self.previous_minutes, 1)
        return None


@dataclass
class LoadState:
    as_of: str = ""
    window_days: int = DEFAULT_EFFORT_WINDOW_DAYS

    # --- Garmin's own figures (exponentially weighted) ---
    garmin_acwr: float | None = None
    garmin_acwr_status: str | None = None
    garmin_acwr_percent: float | None = None
    # El día del que es la carga de Garmin: de madrugada se usa la de ayer (data_extractor.get_training_status).
    garmin_load_date: str | None = None
    acute_load: float | None = None
    chronic_load: float | None = None
    chronic_optimal_min: float | None = None
    chronic_optimal_max: float | None = None
    training_status_feedback: str | None = None
    fitness_trend: float | None = None
    fitness_trend_sport: str | None = None
    vo2_max: float | None = None

    # --- this app's own figures (rolling averages) ---
    own_acwr: float | None = None
    own_acwr_date: str | None = None
    own_acwr_peak: float | None = None
    own_acwr_peak_date: str | None = None
    own_tsb: float | None = None
    own_tsb_date: str | None = None

    # --- Garmin Load Focus, last 30 days ---
    load_balance: dict[str, dict[str, Any]] = field(default_factory=dict)
    load_balance_feedback: str | None = None

    # --- Garmin Training Readiness ---
    readiness_latest: dict[str, Any] | None = None
    readiness_morning: dict[str, Any] | None = None

    # --- computed from the athlete's own activities ---
    # The three most demanding sessions in the window, by Garmin's own
    # training-load score, newest-first ties broken by date. Three rather than
    # one: "the hardest session" and "the most recent hard session" are
    # different questions, and a taper decision needs both. On this athlete's
    # real data they were 17 days and 3 days apart respectively.
    top_efforts: list[dict[str, Any]] = field(default_factory=list)
    sports: list[SportEfforts] = field(default_factory=list)

    warnings: list[str] = field(default_factory=list)

    @property
    def hardest_effort(self) -> dict[str, Any] | None:
        return self.top_efforts[0] if self.top_efforts else None


# --- helpers ---------------------------------------------------------------

def _num(value: Any) -> float | None:
    try:
        if value is None or isinstance(value, bool):
            return None
        return float(value)
    except (TypeError, ValueError):
        return None


def _activity_date(activity: Any) -> str | None:
    raw = getattr(activity, "start_time", None)
    if not raw:
        return None
    text = str(raw)
    return text[:10] if len(text) >= 10 else None


def _days_between(earlier: str | None, later: str) -> int | None:
    if not earlier:
        return None
    try:
        return (date.fromisoformat(later) - date.fromisoformat(earlier)).days
    except ValueError:
        return None


def _canonical_sport(activity_type: str | None) -> str:
    """Reuses the extractor's mapping so a sport means one thing app-wide."""
    try:
        from services.garmin.data_extractor import TriathlonCoachDataExtractor

        return TriathlonCoachDataExtractor._canonical_sport(activity_type)
    except Exception:  # noqa: BLE001 - never let a helper import fail a build
        text = (activity_type or "").lower()
        if "swim" in text:
            return "swim"
        if any(token in text for token in ("cycl", "bik")):
            return "bike"
        if any(token in text for token in ("run", "treadmill")):
            return "run"
        return "other"


# --- building --------------------------------------------------------------

def _apply_training_status(state: LoadState, training_status: Any) -> None:
    load = getattr(training_status, "acute_training_load", None) or {}
    if not isinstance(load, dict):
        return
    state.garmin_acwr = _num(load.get("acwr"))
    state.garmin_acwr_status = load.get("acwr_status")
    state.garmin_acwr_percent = _num(load.get("acwr_percent"))
    state.garmin_load_date = load.get("load_date") if isinstance(load.get("load_date"), str) else None
    state.acute_load = _num(load.get("acute_load"))
    state.chronic_load = _num(load.get("chronic_load"))
    state.chronic_optimal_min = _num(load.get("chronic_optimal_min"))
    state.chronic_optimal_max = _num(load.get("chronic_optimal_max"))
    state.training_status_feedback = load.get("training_status_feedback")
    state.fitness_trend = _num(load.get("fitness_trend"))
    state.fitness_trend_sport = load.get("fitness_trend_sport")

    vo2 = getattr(training_status, "vo2_max", None) or {}
    if isinstance(vo2, dict):
        state.vo2_max = _num(vo2.get("value"))

    balance = getattr(training_status, "load_balance", None)
    if isinstance(balance, dict) and balance:
        state.load_balance = {k: v for k, v in balance.items() if k in _BAND_ES and isinstance(v, dict)}
        state.load_balance_feedback = balance.get("feedback")

    readiness = getattr(training_status, "training_readiness", None)
    if isinstance(readiness, dict) and readiness:
        state.readiness_latest = readiness.get("latest")
        state.readiness_morning = readiness.get("morning")


def _apply_own_series(state: LoadState, history: Any) -> None:
    """Read this app's rolling-average ACWR/TSB series.

    Kept alongside Garmin's own figure rather than instead of it: the series is
    the only thing that shows the RAMP, and the daily figure is the only thing
    the athlete can check on their watch. See the module docstring.
    """
    if not isinstance(history, list) or not history:
        return
    entries = [e for e in history if isinstance(e, dict) and e.get("date")]
    if not entries:
        return
    entries.sort(key=lambda e: str(e["date"]))

    for entry in reversed(entries):
        if state.own_acwr is None and _num(entry.get("acwr_7d28d")) is not None:
            state.own_acwr = _num(entry.get("acwr_7d28d"))
            state.own_acwr_date = str(entry["date"])
        if state.own_tsb is None and _num(entry.get("tsb")) is not None:
            state.own_tsb = _num(entry.get("tsb"))
            state.own_tsb_date = str(entry["date"])
        if state.own_acwr is not None and state.own_tsb is not None:
            break

    peak = max(
        (e for e in entries if _num(e.get("acwr_7d28d")) is not None),
        key=lambda e: _num(e.get("acwr_7d28d")),
        default=None,
    )
    if peak is not None:
        state.own_acwr_peak = _num(peak.get("acwr_7d28d"))
        state.own_acwr_peak_date = str(peak["date"])


def _apply_efforts(state: LoadState, activities: Any, today: str, window_days: int) -> None:
    if not isinstance(activities, list):
        return

    cutoff = None
    try:
        cutoff = date.fromisoformat(today).toordinal() - window_days
    except ValueError:
        pass

    by_sport: dict[str, list[dict[str, Any]]] = {}
    scored: list[dict[str, Any]] = []

    for activity in activities:
        day = _activity_date(activity)
        if not day:
            continue
        if cutoff is not None:
            try:
                if date.fromisoformat(day).toordinal() < cutoff:
                    continue
            except ValueError:
                continue
        summary = getattr(activity, "summary", None)
        distance = _num(getattr(summary, "distance", None)) if summary else None
        seconds = _num(getattr(summary, "duration", None)) if summary else None
        load = _num(getattr(summary, "activity_training_load", None)) if summary else None
        record = {
            "date": day,
            "name": getattr(activity, "activity_name", None),
            "activity_type": getattr(activity, "activity_type", None),
            "sport": _canonical_sport(getattr(activity, "activity_type", None)),
            "km": round(distance / 1000.0, 2) if distance else None,
            "minutes": round(seconds / 60.0, 1) if seconds else None,
            "load": load,
        }
        by_sport.setdefault(record["sport"], []).append(record)

        # Ranked by Garmin's own training-load score, which is comparable
        # ACROSS sports in a way distance and duration are not: a 45-minute
        # swim and a 90-minute ride can carry the same load, and a trail run
        # can outrank a longer road race.
        if load is not None:
            scored.append(record)

    scored.sort(key=lambda r: (r["load"], r["date"]), reverse=True)
    state.top_efforts = [
        {**record, "days_ago": _days_between(record["date"], today)} for record in scored[:3]
    ]

    for sport, records in sorted(by_sport.items()):
        efforts = SportEfforts(sport=sport, sessions=len(records))
        efforts.total_minutes = round(sum(r["minutes"] or 0 for r in records), 1)

        # Distance is the natural yardstick for the three endurance sports the
        # app plans; for everything else (strength, mobility, hiking) duration
        # is, and ranking those by distance would call a 2.3 km walk "longer"
        # than a 40-minute gym session. Falls back to duration whenever the
        # sport's own records lack distance anyway.
        keyed = [r for r in records if r["km"] is not None]
        use_distance = sport in ("run", "bike", "swim") and len(keyed) >= 2
        ordered = sorted(
            keyed if use_distance else records,
            key=lambda r: (r["km"] if use_distance else (r["minutes"] or 0)) or 0,
            reverse=True,
        )
        if ordered:
            top = ordered[0]
            efforts.longest_date = top["date"]
            efforts.longest_name = top["name"]
            efforts.longest_km = top["km"]
            efforts.longest_minutes = top["minutes"]
        if len(ordered) > 1:
            runner_up = ordered[1]
            efforts.previous_date = runner_up["date"]
            efforts.previous_km = runner_up["km"]
            efforts.previous_minutes = runner_up["minutes"]
        state.sports.append(efforts)


def build_load_state(
    garmin_data: Any,
    today: str | None = None,
    window_days: int = DEFAULT_EFFORT_WINDOW_DAYS,
) -> LoadState:
    """Assemble every load/readiness number the planner should be reasoning with.

    Reads only what the extraction already fetched -- Garmin's training status
    payload carries the ACWR, the training status, and the monthly load balance
    together, so none of this costs an extra API call.
    """
    state = LoadState(as_of=today or date.today().isoformat(), window_days=window_days)

    training_status = getattr(garmin_data, "training_status", None)
    if training_status is not None:
        _apply_training_status(state, training_status)
    else:
        state.warnings.append("Garmin no devolvió estado de entrenamiento para esta fecha.")

    _apply_own_series(state, getattr(garmin_data, "training_load_history", None))

    activities = getattr(garmin_data, "all_activities", None) or getattr(garmin_data, "recent_activities", None)
    _apply_efforts(state, activities, state.as_of, window_days)

    if state.garmin_acwr is None and state.own_acwr is None:
        state.warnings.append("No hay ningún ACWR disponible para esta fecha.")

    return state


# --- rendering -------------------------------------------------------------

def _fmt(value: float | None, digits: int = 0, suffix: str = "") -> str:
    if value is None:
        return "no disponible"
    text = f"{value:.{digits}f}" if digits else f"{value:.0f}"
    return f"{text}{suffix}"


def _readiness_line(label: str, reading: dict[str, Any] | None) -> str | None:
    if not isinstance(reading, dict) or not reading:
        return None
    parts = [f"- **{label}**"]
    when = reading.get("time")
    if when:
        parts[0] += f" ({when})"
    score = _num(reading.get("score"))
    level = reading.get("level")
    feedback = reading.get("feedback")
    parts.append(f": {_fmt(score)}")
    if level:
        parts.append(f" {level}")
    if feedback:
        parts.append(f" — {feedback}")
    recovery = _num(reading.get("recovery_time_hours"))
    if recovery is not None:
        parts.append(f". Recuperación pendiente: {recovery:.1f} h")
    return "".join(parts) + "."


def render_load_state(state: LoadState | None) -> str:
    """The prompt block. Empty string when there is nothing real to say.

    Sections with no data are omitted entirely rather than rendered full of
    "N/A" -- a prompt full of absent fields invites reasoning about absent
    data. Within a section that IS present, a missing field says so, because
    there "no disponible" and "cero" are different facts.
    """
    if state is None:
        return ""

    lines: list[str] = [f"\n### Estado de carga y disposición (Garmin, al {state.as_of})"]
    body_written = False

    # --- ACWR, both of them ---
    acwr_lines: list[str] = []
    if state.garmin_acwr is not None:
        detail = f"- **ACWR de Garmin** (media exponencial), {state.garmin_load_date or state.as_of}: **{state.garmin_acwr:.2f}**"
        if state.garmin_acwr_status:
            detail += f" — estado **{state.garmin_acwr_status}**"
        acwr_lines.append(detail + ".")
        loads = f"  Carga aguda {_fmt(state.acute_load)} · crónica {_fmt(state.chronic_load)}"
        if state.chronic_optimal_min is not None and state.chronic_optimal_max is not None:
            # Garmin los llama `minTrainingLoadChronic`/`maxTrainingLoadChronic`,
            # pero son el rango óptimo de la carga AGUDA, 0,8-1,5 veces la crónica:
            # atleta 3 el 2026-09-15, crónica 406 -> 325-609. Aquí decía "rango
            # crónico", y el informe le dijo "tu carga crónica está dentro del
            # rango óptimo... un nivel mínimo aceptable para competir".
            loads += (
                f" (rango óptimo de la carga AGUDA según Garmin, 0,8-1,5 veces la crónica: "
                f"{_fmt(state.chronic_optimal_min)}–{_fmt(state.chronic_optimal_max)})"
            )
        acwr_lines.append(loads + ".")
    if state.own_acwr is not None:
        own = (
            f"- **ACWR calculado por esta app** (media móvil 7 días ÷ promedio 28 días), "
            f"{state.own_acwr_date}: **{state.own_acwr:.2f}**"
        )
        if state.own_acwr_peak is not None and state.own_acwr_peak_date:
            own += f"; pico de **{state.own_acwr_peak:.2f}** el {state.own_acwr_peak_date}"
        acwr_lines.append(own + ".")
    if state.own_tsb is not None:
        acwr_lines.append(
            f"- **TSB calculado por esta app** (crónica − aguda), {state.own_tsb_date}: **{state.own_tsb:+.1f}**."
        )
    if acwr_lines:
        body_written = True
        lines.append("\n**Carga aguda y crónica**")
        lines.extend(acwr_lines)
        if state.garmin_acwr is not None and state.own_acwr is not None:
            lines.append(
                "- ⚠️ Las dos cifras de ACWR usan **métodos distintos** y pueden ser de **días distintos**; "
                "las dos son correctas bajo su propia definición y NO son intercambiables. Si citas una, "
                "di cuál es y de qué fecha. El atleta ve la de Garmin en su reloj."
            )
        lines.append(
            "- El ACWR **describe** la carga; su valor como predictor de lesión es discutido y depende del "
            "método de cálculo. Úsalo para explicar una decisión, nunca como una regla que la dicte."
        )

    # --- readiness ---
    readiness_lines = [
        line for line in (
            _readiness_line("Última lectura", state.readiness_latest),
            _readiness_line("Al despertar", state.readiness_morning),
        ) if line
    ]
    if readiness_lines:
        body_written = True
        lines.append("\n**Disposición para entrenar (Training Readiness de Garmin)**")
        lines.extend(readiness_lines)
        if len(readiness_lines) > 1:
            lines.append(
                "- La lectura cambia durante el día: baja tras entrenar y se recompone al dormir. "
                "La del despertar es la que refleja la recuperación nocturna."
            )

    # --- training status ---
    if state.training_status_feedback or state.fitness_trend is not None or state.vo2_max is not None:
        body_written = True
        lines.append("\n**Estado de entrenamiento**")
        if state.training_status_feedback:
            lines.append(f"- Garmin lo clasifica como **{state.training_status_feedback}**.")
        if state.fitness_trend is not None:
            sport = f" ({state.fitness_trend_sport})" if state.fitness_trend_sport else ""
            lines.append(f"- Tendencia de fitness{sport}: **{state.fitness_trend:+.0f}**.")
        if state.vo2_max is not None:
            lines.append(f"- VO2max: **{state.vo2_max:.1f}**.")

    # --- load balance ---
    if state.load_balance:
        body_written = True
        lines.append("\n**Reparto de carga del último mes (Load Focus de Garmin)**")
        for key in ("aerobic_low", "aerobic_high", "anaerobic"):
            band = state.load_balance.get(key)
            if not isinstance(band, dict):
                continue
            load = _num(band.get("load"))
            low = _num(band.get("target_min"))
            high = _num(band.get("target_max"))
            status = _STATUS_ES.get(str(band.get("status")), band.get("status") or "")
            line = f"- {_BAND_ES[key].capitalize()}: **{_fmt(load)}**"
            if low is not None and high is not None:
                line += f" (objetivo {_fmt(low)}–{_fmt(high)})"
            if status:
                line += f" — {status}"
            lines.append(line + ".")
        if state.load_balance_feedback:
            lines.append(f"- Lectura de Garmin sobre el reparto: {state.load_balance_feedback}.")
        # Esta lectura es de Garmin, y hereda sus supuestos: reparte cada sesión
        # entre "aeróbico bajo / alto / anaeróbico" por la intensidad relativa a
        # la FC máxima y las zonas que el atleta tenga configuradas. Con una FC
        # máxima estimada por fórmula -- lo más común -- un rodaje suave puede
        # contar como "alto" o unas series como "bajo". La v1.2 llegó a ordenar
        # desde el prompt "si le falta aeróbico alto, una fase de horas suaves es
        # la fase equivocada": convertir una etiqueta en una orden.
        lines.append(
            "- El reparto lo calcula Garmin con la FC máxima y las zonas del reloj, que a menudo están mal "
            "configuradas: un \"déficit\" aquí puede ser un error de zonas y no una carencia del atleta. "
            "Es una segunda opinión que se contrasta con lo que el atleta hace y con cómo responde; nunca "
            "decide por sí sola qué sesiones necesita."
        )

    # --- efforts ---
    effort_lines: list[str] = []
    if state.top_efforts:
        effort_lines.append(
            "- **Sesiones más exigentes** (por la carga que Garmin asignó a cada una, comparable entre deportes):"
        )
        for effort in state.top_efforts:
            name = effort.get("name") or effort.get("activity_type") or "sesión"
            detail = []
            if effort.get("km"):
                detail.append(f"{effort['km']:.1f} km")
            if effort.get("minutes"):
                detail.append(f"{effort['minutes']:.0f} min")
            if effort.get("load"):
                detail.append(f"carga {effort['load']:.0f}")
            ago = f", hace {effort['days_ago']} días" if effort.get("days_ago") is not None else ""
            effort_lines.append(
                f"    - {effort['date']}{ago} — «{name}» ({_SPORT_ES.get(effort['sport'], effort['sport'])})"
                + (f": {', '.join(detail)}" if detail else "")
            )

    for efforts in state.sports:
        if not efforts.longest_date:
            continue
        sport_es = _SPORT_ES.get(efforts.sport, efforts.sport)
        size = []
        if efforts.longest_km:
            size.append(f"{efforts.longest_km:.1f} km")
        if efforts.longest_minutes:
            size.append(f"{efforts.longest_minutes:.0f} min")
        line = (
            f"- {sport_es.capitalize()}: {efforts.sessions} sesión(es), {efforts.total_minutes:.0f} min en total. "
            f"La más larga fue {' / '.join(size) or 'sin datos de tamaño'} el {efforts.longest_date}"
        )
        if efforts.previous_date:
            prev = []
            if efforts.previous_km:
                prev.append(f"{efforts.previous_km:.1f} km")
            if efforts.previous_minutes:
                prev.append(f"{efforts.previous_minutes:.0f} min")
            line += f"; la siguiente más larga, {' / '.join(prev)} el {efforts.previous_date}"
            jump = efforts.distance_jump or efforts.duration_jump
            if jump and jump >= 1.5:
                line += f" (**un salto de {jump:g}×**)"
        effort_lines.append(line + ".")

    if effort_lines:
        body_written = True
        lines.append(f"\n**Historial de esfuerzos, últimos {state.window_days} días** (calculado sobre sus actividades reales)")
        lines.extend(effort_lines)
        lines.append(
            "- Esto **describe** lo que hizo, no dicta lo que puede hacer. Un salto grande reciente es "
            "contexto para dosificar, no una prohibición."
        )

    if state.warnings:
        lines.append("")
        lines.extend(f"- ⚠️ {warning}" for warning in state.warnings)
        body_written = True

    return "\n".join(lines) + "\n" if body_written else ""


def load_state_to_state(state: LoadState | None) -> dict[str, Any]:
    """Serialize for LangGraph state, which carries plain dicts."""
    if state is None:
        return {}
    return {
        "as_of": state.as_of,
        "garmin_acwr": state.garmin_acwr,
        "garmin_acwr_status": state.garmin_acwr_status,
        "garmin_load_date": state.garmin_load_date,
        "acute_load": state.acute_load,
        "chronic_load": state.chronic_load,
        "own_acwr": state.own_acwr,
        "own_acwr_peak": state.own_acwr_peak,
        "own_acwr_peak_date": state.own_acwr_peak_date,
        "own_tsb": state.own_tsb,
        "training_status_feedback": state.training_status_feedback,
        "fitness_trend": state.fitness_trend,
        "load_balance": state.load_balance,
        "load_balance_feedback": state.load_balance_feedback,
        "readiness_latest": state.readiness_latest,
        "readiness_morning": state.readiness_morning,
        "top_efforts": state.top_efforts,
        "sports": [
            {
                "sport": s.sport,
                "sessions": s.sessions,
                "total_minutes": s.total_minutes,
                "longest_date": s.longest_date,
                "longest_km": s.longest_km,
                "longest_minutes": s.longest_minutes,
                "previous_date": s.previous_date,
                "previous_km": s.previous_km,
                "previous_minutes": s.previous_minutes,
                "distance_jump": s.distance_jump,
                "duration_jump": s.duration_jump,
            }
            for s in state.sports
        ],
        "warnings": list(state.warnings),
        "block": render_load_state(state),
    }
