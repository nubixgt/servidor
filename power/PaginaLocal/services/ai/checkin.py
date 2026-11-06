"""The athlete's own words, right before the week gets built.

Plan Maestro v5 Track 6.

Everything else the coach sees comes off a watch. The watch does not know that
last week was terrible at work, that a shoe rubbed a spot on the left achilles,
or that the athlete is travelling on Thursday. Since the plan is now built one
week at a time (v4 Track B), the moment right before generation is exactly when
asking is cheap and the answer is still true.

## What the instrument is

The four scored items are the **Hooper index** (Hooper-Mackinnon): sleep
quality, fatigue, stress and muscle soreness, each 1-7. It is the most widely
used brief wellness questionnaire in athlete monitoring, and its value comes
almost entirely from being repeated and read **against the same athlete's own
history** -- an absolute score of 16 means nothing without knowing whether this
person usually sits at 10 or at 20. Three more items cover what a coach would
actually ask and no instrument captures: a localised niggle, what got in the
way last week, and what the week ahead looks like.

## What this module deliberately does NOT do

There is **no automatic guardrail**. Nothing here downgrades a session, caps a
volume, or vetoes intensity because a number came back high. That is the same
decision, for the same reasons, as the one documented in
services/ai/recovery_state.py:

1. A self-reported score is only interpretable against this athlete's own
   baseline, and a new athlete has no baseline at all.
2. One check-in is one data point. Athletes fill these in tired, in a hurry,
   and inconsistently.
3. A rule that turned a bad Monday into a cancelled session would be the app
   overriding a coach's judgement about a specific athlete on the weakest
   evidence in the entire pipeline.

The model gets the numbers, framed honestly, and decides. Skipping the check-in
never blocks a generation.
"""

from __future__ import annotations

import json
import logging
from datetime import date, datetime
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)

CHECKIN_FILENAME = "checkins.json"

# Below this many previous check-ins there is no personal baseline worth
# quoting, and the block says so instead of averaging two numbers and calling
# it a trend.
_MIN_HISTORY_FOR_BASELINE = 3

# How many recent check-ins form the personal baseline. Roughly two months of
# weekly answers -- long enough to be stable, short enough that a genuine
# change in life circumstances moves it.
_BASELINE_WINDOW = 8

# The four Hooper items, all scored the same direction: 1 = best, 7 = worst.
# Keeping one direction for all four is what stops a rendered "5" meaning
# "good" in one row and "bad" in the next.
_HOOPER_ITEMS = {
    "sleep": "Calidad de sueño",
    "fatigue": "Fatiga",
    "stress": "Estrés",
    "soreness": "Dolor muscular",
}

_WEEK_AHEAD_ES = {
    "normal": "semana normal",
    "viaje": "viaje",
    "carga_laboral": "carga laboral alta",
    "poco_sueno": "poco sueño previsto",
}


def _clamp_score(value: Any, low: int, high: int) -> int | None:
    try:
        score = int(value)
    except (TypeError, ValueError):
        return None
    return score if low <= score <= high else None


def normalize_checkin(raw: dict, today: date | None = None) -> dict:
    """Clean one submitted check-in into what gets stored.

    Every field is optional. A check-in where the athlete answered two items
    and skipped the rest is still worth keeping -- dropping it for being
    incomplete would throw away the only self-reported data in the app.
    """
    raw = raw or {}
    normalized: dict[str, Any] = {
        "date": (today or date.today()).isoformat(),
        "recorded_at": datetime.now().isoformat(),
    }

    for key in _HOOPER_ITEMS:
        normalized[key] = _clamp_score(raw.get(key), 1, 7)

    normalized["motivation"] = _clamp_score(raw.get("motivation"), 1, 7)
    normalized["niggle_location"] = str(raw.get("niggle_location") or "").strip()
    normalized["niggle_pain"] = _clamp_score(raw.get("niggle_pain"), 0, 10)
    normalized["last_week_note"] = str(raw.get("last_week_note") or "").strip()
    normalized["week_note"] = str(raw.get("week_note") or "").strip()
    normalized["unavailable_dates"] = dias_sin_entrenar(raw)
    normalized["open_dates"] = dias_abiertos(raw)
    esperada = str(raw.get("week_as_expected") or "").strip()
    normalized["week_as_expected"] = esperada if esperada in _COMO_ESPERABA_ES else ""

    week_ahead = str(raw.get("week_ahead") or "").strip()
    normalized["week_ahead"] = week_ahead if week_ahead in _WEEK_AHEAD_ES else ""

    normalized["hooper_total"] = hooper_total(normalized)
    return normalized


_DIAS_ES = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]

# "¿La semana que cierras salió como esperabas?" (Database/016). El atleta lo
# contestaba en cada check-in, se guardaba en MySQL y NO salía de la entidad de
# PHP: ni el planificador ni el panel lo veían. Auditoría de cables, 2026-09-15.
_COMO_ESPERABA_ES = {
    "si": "sí, salió como esperaba",
    "a_medias": "a medias",
    "no": "no, no salió como esperaba",
}


def dias_sin_entrenar(checkin: dict | None) -> list[str]:
    """Las fechas en las que el atleta dijo que ESTA semana no puede entrenar.

    Pedido por el atleta Fernando el 2026-09-14 (Database/022). Fechas ISO
    válidas, sin repetir y ordenadas; la columna llega de MySQL como texto JSON
    o ya como lista, y cualquier otra cosa se descarta en vez de adivinarse.
    """
    valor = (checkin or {}).get("unavailable_dates")
    if isinstance(valor, str):
        try:
            valor = json.loads(valor)
        except ValueError:
            valor = [valor]
    fechas = set()
    for v in valor if isinstance(valor, list) else []:
        try:
            fechas.add(date.fromisoformat(str(v)[:10]).isoformat())
        except ValueError:
            continue
    return sorted(fechas)[:14]


def render_dias_sin_entrenar(fechas: list[str]) -> str:
    """La regla para el contexto de planificación, junto a la tabla de disponibilidad."""
    if not fechas:
        return ""
    dias = ", ".join(f"{_DIAS_ES[date.fromisoformat(f).weekday()]} {f}" for f in fechas)
    return (
        "[DÍAS SIN ENTRENAR ESTA SEMANA]\n"
        f"El atleta dijo en su check-in que esta semana NO puede entrenar: {dias}. Esos días son DESCANSO esta "
        "semana, aunque su tabla los tenga disponibles o con un deporte FIJO: no pongas ninguna sesión. Lo que no "
        "cabe no se amontona en los días de al lado; reparte con criterio o quítalo, y di en `week_rationale` qué "
        "hiciste."
    )


#: Los deportes con los que se abre un día: los de la rejilla del perfil, en su orden.
DEPORTES_QUE_SE_ABREN = ("Correr", "Trail", "Bici", "Nadar", "Fuerza")


def dias_abiertos(checkin: dict | None) -> dict[str, list[str]]:
    """Los días que su tabla no le da y que ESTA semana sí puede, con qué deportes.

    Jessie, 2026-09-15 (Database/024): no entrena los sábados y escribió en el
    check-in "mi activación para una carrera la suelo hacer el día sábado antes
    de la carrera, ligera". Su tabla tenía el sábado de descanso, así que la
    semana le dio "descanso completo como prefieres" y pasó la activación a una
    fuerza el jueves. Podía decir qué días NO podía; no qué día SÍ.

    `{"2026-09-19": ["Correr"]}`. Fechas ISO válidas y deportes de la rejilla;
    un día sin ningún deporte no se abre, y un día que además marcó como que no
    puede tampoco: gana el no.
    """
    valor = (checkin or {}).get("open_dates")
    if isinstance(valor, str):
        try:
            valor = json.loads(valor)
        except ValueError:
            valor = {}
    if not isinstance(valor, dict):
        return {}
    no_puede = set(dias_sin_entrenar(checkin))
    salida: dict[str, list[str]] = {}
    for clave, deportes in valor.items():
        try:
            fecha = date.fromisoformat(str(clave)[:10]).isoformat()
        except ValueError:
            continue
        marcados = set(deportes) if isinstance(deportes, list) else set()
        elegidos = [d for d in DEPORTES_QUE_SE_ABREN if d in marcados]
        if elegidos and fecha not in no_puede:
            salida[fecha] = elegidos
    return dict(sorted(salida.items())[:14])


def describir_dias_abiertos(abiertos: dict[str, list[str]]) -> str:
    """"sábado 2026-09-19 (Correr)", uno detrás de otro."""
    return ", ".join(
        f"{_DIAS_ES[date.fromisoformat(f).weekday()]} {f} ({', '.join(deportes)})"
        for f, deportes in sorted(abiertos.items())
    )


def render_dias_abiertos(abiertos: dict[str, list[str]]) -> str:
    """La regla para el contexto de planificación, junto a la tabla de disponibilidad."""
    if not abiertos:
        return ""
    return (
        "[DÍAS QUE ABRE ESTA SEMANA]\n"
        f"El atleta dijo en su check-in que esta semana SÍ puede entrenar: {describir_dias_abiertos(abiertos)}. "
        "Su tabla no le da esos días, y esta semana cuentan como POSIBLE con esos deportes y ningún otro: los "
        "usas o no con el mismo criterio que cualquier POSIBLE. Si su nota de la semana dice para qué lo abrió, "
        "eso es lo que pidió."
    )


def hooper_total(checkin: dict) -> int | None:
    """Sum of the four Hooper items, 4-28, higher = worse.

    None unless all four were answered: a partial sum is not comparable to a
    full one, and quietly returning it would corrupt every baseline built from
    it afterwards.
    """
    scores = [checkin.get(key) for key in _HOOPER_ITEMS]
    if any(score is None for score in scores):
        return None
    return sum(scores)


def has_content(checkin: dict | None) -> bool:
    """Whether a check-in says anything at all worth showing the coach."""
    if not checkin:
        return False
    return any([
        checkin.get("hooper_total") is not None,
        any(checkin.get(key) is not None for key in _HOOPER_ITEMS),
        checkin.get("motivation") is not None,
        checkin.get("niggle_location"),
        checkin.get("last_week_note"),
        checkin.get("week_ahead"),
        checkin.get("week_note"),
        dias_sin_entrenar(checkin),
        dias_abiertos(checkin),
        checkin.get("week_as_expected") in _COMO_ESPERABA_ES,
    ])


# --- persistence -----------------------------------------------------------

def load_checkins(storage_dir: Path) -> list[dict]:
    """Every check-in for a profile, oldest first. Never raises."""
    path = Path(storage_dir) / CHECKIN_FILENAME
    if not path.is_file():
        return []
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        logger.warning("Could not read check-ins: %s", exc)
        return []
    return data if isinstance(data, list) else []


def save_checkin(storage_dir: Path, checkin: dict) -> dict:
    """Append one check-in. Same-day answers replace each other.

    Replacing rather than appending within a day keeps the baseline from being
    skewed by an athlete who opened the form twice before generating.
    """
    storage_dir = Path(storage_dir)
    storage_dir.mkdir(parents=True, exist_ok=True)

    existing = [c for c in load_checkins(storage_dir) if c.get("date") != checkin.get("date")]
    existing.append(checkin)
    existing.sort(key=lambda c: str(c.get("date") or ""))

    (storage_dir / CHECKIN_FILENAME).write_text(
        json.dumps(existing, indent=2, ensure_ascii=False), encoding="utf-8"
    )
    return checkin


def latest_checkin(storage_dir: Path, max_age_days: int | None = None) -> dict | None:
    """El ultimo check-in; con `max_age_days`, solo si es de esos ultimos dias.

    La generacion de la semana lo presenta como "respondido justo antes de
    generar esta semana", y su molestia entra como queja ACTUAL. Si el atleta
    pulsaba "Saltar", llegaba el ultimo que hubiera, de hace semanas: una
    rodilla declarada una vez seguia produciendo contingencias de rodilla cada
    semana -- justo el sesgo de lesion que la v1.1 quiso quitar. Auditoria
    previa al merge, 2026-09-11.
    """
    checkins = load_checkins(storage_dir)
    if not checkins:
        return None
    ultimo = checkins[-1]
    if max_age_days is None:
        return ultimo
    from datetime import date, timedelta
    try:
        fecha = date.fromisoformat(str(ultimo.get("date") or "")[:10])
    except ValueError:
        return None
    return ultimo if fecha >= date.today() - timedelta(days=max_age_days) else None


# --- interpretation --------------------------------------------------------

def personal_baseline(history: list[dict]) -> dict | None:
    """This athlete's own usual Hooper total, from their recent check-ins.

    Returns None until there is enough history to mean anything. An absolute
    score is uninterpretable on its own -- 16 is a bad week for someone who
    usually reports 9 and a good one for someone who usually reports 22.
    """
    totals = [c["hooper_total"] for c in history if c.get("hooper_total") is not None]
    if len(totals) < _MIN_HISTORY_FOR_BASELINE:
        return None

    window = totals[-_BASELINE_WINDOW:]
    return {
        "mean": round(sum(window) / len(window), 1),
        "low": min(window),
        "high": max(window),
        "samples": len(window),
    }


def render_checkin_block(checkin: dict | None, history: list[dict] | None = None) -> str:
    """The check-in as prompt material, framed against the athlete's own history.

    Returns "" when there is no check-in, so skipping it costs nothing --
    neither tokens nor a generation.
    """
    if not has_content(checkin):
        return ""

    history = [c for c in (history or []) if c.get("date") != checkin.get("date")]
    baseline = personal_baseline(history)

    lines = [
        # Con su FECHA: `max_age_days` deja pasar uno de hace días, y el
        # encabezado decía "justo antes". La semana del atleta 2 del
        # 2026-09-13 leyó uno del 11 como el de hoy.
        f"## Check-in del atleta (respondido el {checkin.get('date') or 'sin fecha'})",
        "",
        "Autorreporte, no medición. Complementa los datos del reloj; no los sustituye ni los contradice "
        "por sí solo.",
        "",
    ]

    scored = [(label, checkin.get(key)) for key, label in _HOOPER_ITEMS.items()]
    scored = [(label, value) for label, value in scored if value is not None]
    if scored:
        lines.append("**Índice de Hooper** (escala 1-7 donde **7 es peor** en los cuatro):")
        lines.extend(f"- {label}: {value}/7" for label, value in scored)

    total = checkin.get("hooper_total")
    if total is not None:
        if baseline:
            delta = total - baseline["mean"]
            direction = "peor" if delta > 0 else ("mejor" if delta < 0 else "igual")
            lines.append(
                f"- **Total: {total}/28** frente a su propia media de {baseline['mean']} "
                f"(rango {baseline['low']}-{baseline['high']} en sus últimos {baseline['samples']} "
                f"check-ins) → {abs(round(delta, 1))} puntos {direction} que lo habitual **para él/ella**."
            )
        else:
            lines.append(
                f"- **Total: {total}/28**. Todavía no hay suficientes check-ins previos para comparar "
                "contra su propia línea base, así que este número **no se puede leer como alto o bajo "
                "todavía**. No lo interpretes contra ninguna referencia poblacional."
            )

    motivation = checkin.get("motivation")
    if motivation is not None:
        lines.append(f"- Ganas de entrenar: {motivation}/7 (aquí **7 es mucha gana**, dirección opuesta a las de arriba)")

    niggle = str(checkin.get("niggle_location") or "").strip()
    if niggle:
        pain = checkin.get("niggle_pain")
        pain_text = f", dolor {pain}/10" if pain is not None else ""
        lines.extend([
            "",
            f"**Molestia declarada**: {niggle}{pain_text}.",
            # v6 Track 3: what to DO about it now lives in one place, with the
            # health record's declared injuries and the gesture map -- see
            # services/ai/niggle_constraints.py. Saying it in two places said
            # it two different ways, and the weaker version was the one the
            # model followed: it wrote a knee contingency for a declared groin.
            "Qué hacer con ella está en «Molestias declaradas por el atleta», más abajo.",
        ])

    esperada = checkin.get("week_as_expected")
    if esperada in _COMO_ESPERABA_ES:
        lines.extend([
            "",
            f"**¿La semana que cierra salió como esperaba?** {_COMO_ESPERABA_ES[esperada]}. Es su lectura, no "
            "la adherencia medida: si las dos no dicen lo mismo, nómbralo.",
        ])

    last_week = str(checkin.get("last_week_note") or "").strip()
    if last_week:
        lines.extend(["", f"**Qué se atravesó la semana pasada**: {last_week}"])

    week_ahead = checkin.get("week_ahead")
    if week_ahead:
        lines.extend([
            "",
            f"**Cómo viene la semana**: {_WEEK_AHEAD_ES.get(week_ahead, week_ahead)}. "
            "Ajusta dónde caen las sesiones y qué días soportan carga, no sólo cuánta carga hay.",
        ])

    # Lo que el atleta quiere que se tenga en cuenta ESTA semana (Database/021):
    # una salida en grupo, una carrera familiar, una subida a un volcán. No es
    # una competición del macrociclo, y sin esto la semana se planificaba como
    # si no existiera -- una tirada larga el día después de tres horas de bici
    # que el atleta iba a hacer igual.
    #
    # Si cae en un día que su tabla marca como descanso, el validador lo
    # convertiría en descanso: por eso se pide encajarla alrededor y decirlo,
    # no programarla ahí.
    week_note = str(checkin.get("week_note") or "").strip()
    if week_note:
        lines.extend([
            "",
            f"**Lo que el atleta quiere que se tenga en cuenta esta semana** (sus palabras): «{week_note}»",
            "Es parte de su semana, no una nota al margen. Si es una actividad (una carrera popular, una salida "
            "en grupo, un evento), cuenta como entrenamiento: planifica ese día y los de alrededor con ella "
            "dentro, sin sumarle otra sesión que la duplique. Si cae en un día que su tabla marca como descanso "
            "o con un deporte que ese día no tiene, y no lo abrió esta semana, no la programes como sesión: "
            "encaja los días de alrededor contando con ella, y dile en `week_rationale` que ese día lo puede "
            "abrir en su check-in. Si es una restricción (un día que no puede, un viaje), respétala. Di en "
            "`week_rationale` cómo la encajaste, y si choca con la fase o con una carrera cercana, qué "
            "priorizaste.",
        ])

    sin_entrenar = dias_sin_entrenar(checkin)
    if sin_entrenar:
        dias = ", ".join(f"{_DIAS_ES[date.fromisoformat(f).weekday()]} {f}" for f in sin_entrenar)
        lines.extend([
            "",
            f"**Días que NO puede entrenar esta semana** (los marcó el atleta): {dias}. Son descanso; la regla "
            "va también en su disponibilidad y el validador la comprueba.",
        ])

    abiertos = dias_abiertos(checkin)
    if abiertos:
        lines.extend([
            "",
            f"**Días que abre esta semana** (su tabla no se los da): {describir_dias_abiertos(abiertos)}. Cuentan "
            "como POSIBLE con esos deportes; la regla va también en su disponibilidad y el validador la comprueba.",
        ])

    lines.extend([
        "",
        "**Cómo leerlo**: contra la propia historia de este atleta, nunca contra un umbral general. "
        "Un solo check-in malo **no justifica cambiar el plan por sí solo** — pésalo junto a la "
        "recuperación medida, la adherencia y lo que los expertos observaron. Si el autorreporte y los "
        "sensores se contradicen, aplica la política de conflictos: nombra la contradicción y dila en "
        "`week_rationale`.",
    ])

    return "\n".join(lines)


def render_lo_que_dijo(checkin: dict | None, history: list[dict] | None = None) -> str:
    """Lo que el atleta contestó en su check-in, SIN las instrucciones de planificar.

    Para las preguntas del coach y los tres expertos. Hasta el 2026-09-21 el
    check-in sólo llegaba a la decisión semanal: el atleta 2, en la Beta 2.0
    publicada, contestó en su check-in agujetas, molestia y "semana normal", y
    el coach le preguntó a continuación "¿cómo quedaste de piernas?" y "¿esta
    semana tienes algún viaje?". En los prompts archivados de la atleta 3
    (2026-09-15) su nota de la semana aparece sólo en la decisión.

    `render_checkin_block` le dice al planificador qué hacer con cada respuesta
    (`week_rationale`, el validador); aquí van sólo los hechos, porque estos
    pasos no escriben la semana. "" sin check-in: su prompt no cambia.
    """
    if not has_content(checkin):
        return ""
    history = [c for c in (history or []) if c.get("date") != checkin.get("date")]
    baseline = personal_baseline(history)
    lines = [
        f"## Check-in del atleta (respondido el {checkin.get('date') or 'sin fecha'})",
        "Autorreporte, no medición: se lee contra su propia historia, nunca contra un umbral general.",
    ]
    scored = [(label, checkin.get(key)) for key, label in _HOOPER_ITEMS.items()]
    scored = [(label, value) for label, value in scored if value is not None]
    if scored:
        lines.append("Índice de Hooper (1-7, **7 es peor**):")
        lines.extend(f"- {label}: {value}/7" for label, value in scored)
    total = checkin.get("hooper_total")
    if total is not None:
        if baseline:
            lines.append(f"- Total: {total}/28; su media es {baseline['mean']} "
                         f"(rango {baseline['low']}-{baseline['high']} en {baseline['samples']} check-ins).")
        else:
            lines.append(f"- Total: {total}/28; todavía sin check-ins previos con los que compararlo.")
    motivation = checkin.get("motivation")
    if motivation is not None:
        lines.append(f"- Ganas de entrenar: {motivation}/7 (aquí 7 es mucha gana).")
    niggle = str(checkin.get("niggle_location") or "").strip()
    if niggle:
        pain = checkin.get("niggle_pain")
        lines.append(f"- Molestia: {niggle}" + (f", dolor {pain}/10." if pain is not None else "."))
    else:
        # Que no escribió ninguna también es lo que dijo. Réplica sobre el prompt
        # real de la atleta 3 (2026-09-15, 0,01 USD): sin esta línea el coach le
        # seguía preguntando "¿estás 100 % sin molestias?".
        lines.append("- Molestia localizada: no escribió ninguna.")
    esperada = checkin.get("week_as_expected")
    if esperada in _COMO_ESPERABA_ES:
        lines.append(f"- ¿La semana que cierra salió como esperaba? {_COMO_ESPERABA_ES[esperada]}.")
    last_week = str(checkin.get("last_week_note") or "").strip()
    if last_week:
        lines.append(f"- Qué se atravesó la semana pasada: {last_week}")
    week_ahead = checkin.get("week_ahead")
    if week_ahead:
        lines.append(f"- Cómo viene la semana: {_WEEK_AHEAD_ES.get(week_ahead, week_ahead)}.")
    week_note = str(checkin.get("week_note") or "").strip()
    if week_note:
        lines.append(f"- Lo que quiere que se tenga en cuenta esta semana (sus palabras): «{week_note}»")
    sin_entrenar = dias_sin_entrenar(checkin)
    if sin_entrenar:
        lines.append("- Días que no puede entrenar esta semana: "
                     + ", ".join(f"{_DIAS_ES[date.fromisoformat(f).weekday()]} {f}" for f in sin_entrenar) + ".")
    abiertos = dias_abiertos(checkin)
    if abiertos:
        lines.append(f"- Días que abre esta semana: {describir_dias_abiertos(abiertos)}.")
    return "\n".join(lines)


def bloque_del_checkin(state: dict) -> str:
    """El check-in para añadir al final de un prompt: "\\n\\n" + lo que dijo, o "" sin check-in."""
    texto = ((state.get("availability_constraints") or {}).get("checkin") or {}).get("lo_que_dijo") or ""
    return f"\n\n{texto}" if texto else ""


def checkin_to_state(checkin: dict | None, history: list[dict] | None = None) -> dict:
    """Serialize for LangGraph state and for the archived reasoning trail."""
    if not has_content(checkin):
        return {"available": False, "block": "", "lo_que_dijo": ""}
    return {
        "available": True,
        "checkin": checkin,
        "baseline": personal_baseline([c for c in (history or []) if c.get("date") != checkin.get("date")]),
        "block": render_checkin_block(checkin, history),
        "lo_que_dijo": render_lo_que_dijo(checkin, history),
    }
