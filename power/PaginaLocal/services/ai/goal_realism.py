"""Frame a declared race goal against what the athlete's own data predicts.

Plan Maestro v5 Track 2b.

The athlete put it plainly: a recreational runner doing 5K in 28 minutes who
says they want to go to the Olympics should get judgement, not compliance. A
real coach laughs, and then offers a target that is actually reachable.

Nothing was missing to do this. `get_race_predictions` was already extracted
(data_extractor.py) and already rendered into the prescription card, and each
competition already carried a structured `target_time`. What was missing was a
cable: **the prescription was only ever injected into the WEEKLY planner**, so
the one agent that evaluates season goals evaluated them blind.

## What this module is, and is not

It is a **framing block**, not a gate. There is no rule here that rejects a
goal, downgrades it, or refuses to plan. The agent decides; this only makes
sure it decides with the athlete's real predicted times in front of it, in the
same spirit as the recovery block in services/ai/recovery_state.py.

The reference improvement rate below is the one number that could turn into a
false authority, so it is deliberately coarse, labelled as a rough reference,
and stated with its own exceptions attached. Endurance improvement has no
single canonical rate: a beginner off a very low base can far exceed it, a
well-trained athlete rarely reaches it, and returning from a layoff is a
different curve entirely. The block says all of that to the model, and says
explicitly that the model may disagree with the framing when the athlete's
data justifies it.
"""

from __future__ import annotations

import re
from datetime import date, datetime
from typing import Any

from services.ai.race_distances import (
    RACE_FORMATS,
    describe,
    is_elevation_driven,
    is_multisport,
    legs_of,
    total_metres,
)

# Distances Garmin publishes a current-fitness prediction for. A goal at any
# other distance can still be planned for -- it just has no direct reference
# to be compared against, which the block says rather than guessing.
GARMIN_PREDICTED_DISTANCES = ("5K", "10K", "half_marathon", "marathon")

# Las etiquetas salen del catálogo (`services/ai/race_distances.py`), que
# desde v7 cubre las disciplinas que esta app sirve de verdad: además de
# asfalto, trail, natación en piscina y en aguas abiertas, ciclismo, triatlón,
# duatlón y acuatlón. Antes había cuatro distancias de correr, y un triatleta
# que escribía "Triatlón Standard" veía su carrera principal evaluada como
# "sin referencia".
DISTANCE_LABELS_ES = {clave: fmt["label"] for clave, fmt in RACE_FORMATS.items()}

# Official distances, for turning a target time into the average pace it
# actually demands. That pace, next to the athlete's own bands, is the most
# concrete thing this module can hand the agent -- and it is a fact, not a
# verdict.
# Sólo para las de UN deporte: convertir un tiempo objetivo en el ritmo que
# exige no significa nada en un triatlón, donde el tiempo incluye tres
# disciplinas y dos transiciones.
_DISTANCE_METRES = {
    clave: total_metres(clave)
    for clave in RACE_FORMATS
    if not is_multisport(clave) and total_metres(clave)
}

# v6 Track 4. The `distance` dropdown was added in v5 Track 2, so every
# competition saved before it has an empty one -- which is why goal realism
# reported "sin_referencia" for all three of the athlete's races and the
# season planner called a 5:00/km target "ambitious" without ever comparing it
# to anything.
#
# Rather than leave that data dead, the distance is read out of what the
# athlete already wrote: the race type, or the race's own name ("5k
# Monterrico"). It is a derivation from their words, never an invention, and
# the block says when it was inferred so it can be corrected.
#
# ORDER MATTERS, most specific first: "media maratón" contains "maratón", and
# this codebase has already shipped one classification bug of exactly that
# shape (see plan_validator.py -- "ritmo de maratón" matching "ritmo" first
# and being prescribed as threshold work).
#
# v7: además de las cuatro de asfalto, los formatos de las demás disciplinas
# que esta app sirve. El orden sigue siendo la regla entera -- "medio ironman"
# contiene "ironman", "triatlón cross" contiene "triatlón", y "trail maratón"
# contiene "maratón". Cada bloque va antes que aquel cuyas palabras contiene.
_DISTANCE_PATTERNS: tuple[tuple[str, str], ...] = (
    # Triatlón y familia, antes que las distancias sueltas de sus nombres.
    ("tri_70_3", r"70\.?3|medio\s*iron|half\s*iron|ironman\s*70"),
    ("tri_ironman", r"140\.?6|iron\s*-?\s*man"),
    ("tri_cross", r"xterra|tri\w*\s*cross|cross\s*tri"),
    ("tri_supersprint", r"super\s*-?\s*sprint"),
    ("tri_sprint", r"tri\w*\s*sprint|sprint\s*\w*tri"),
    ("tri_olympic", r"ol[ií]mpic|olympic|standard|est[áa]ndar|triatl[oó]n|triathlon|\btri\b"),
    ("duathlon_standard", r"duatl[oó]n\s*(standard|est[áa]ndar|ol[ií]mpic)"),
    ("duathlon_sprint", r"duatl[oó]n|duathlon"),
    ("aquathlon", r"acuatl[oó]n|aquathlon"),
    ("swimrun", r"swim\s*-?\s*run"),
    # Montaña, antes que las distancias de asfalto de sus nombres.
    ("vertical_km", r"kil[oó]metro\s*vertical|\bkv\b|vertical\s*k"),
    ("trail_ultra_100m", r"100\s*mill|utmb"),
    ("trail_ultra_80", r"(?<!\d)(80|100)\s*-?\s*k\w*[\s\w]{0,12}(trail|monta|ultra)|ultra[\s\w]{0,12}(80|100)\s*-?\s*k"),
    ("trail_ultra_50", r"(?<!\d)50\s*-?\s*k\w*[\s\w]{0,12}(trail|monta|ultra)|ultra\s*-?\s*trail|\bultra\b"),
    ("skyrace", r"skyrace|sky\s*runn"),
    ("trail_marathon", r"(trail|monta[nñ]a|sendero)[\s\w]{0,15}marat|marat[oó]?n?[\s\w]{0,15}(trail|monta[nñ]a)"),
    ("trail_short", r"\btrail\b|monta[nñ]a|sendero"),
    # Aguas abiertas y piscina, antes que ciclismo (comparten cifras).
    ("ow_10000", r"(?<!\d)10\s*-?\s*k[\s\w]{0,18}(aguas|open\s*water|traves[íi]a)|traves[íi]a[\s\w]{0,10}10"),
    ("ow_5000", r"(?<!\d)5\s*-?\s*k[\s\w]{0,18}(aguas|open\s*water|traves[íi]a)|traves[íi]a[\s\w]{0,10}5"),
    ("ow_3000", r"(?<!\d)3\s*-?\s*k[\s\w]{0,18}(aguas|open\s*water|traves[íi]a)"),
    ("ow_1500", r"aguas\s*abiertas|open\s*water|traves[íi]a"),
    ("swim_1500", r"1500\s*m?[\s\w]{0,10}(nataci|swim|libre|crol)|(nataci|swim)[\s\w]{0,10}1500"),
    ("swim_800", r"800\s*m?[\s\w]{0,10}(nataci|swim|libre|crol)|(nataci|swim)[\s\w]{0,10}800"),
    ("swim_400", r"400\s*m?[\s\w]{0,10}(nataci|swim|libre|crol)|(nataci|swim)[\s\w]{0,10}400"),
    # Ciclismo.
    ("bike_mtb_maraton", r"(mtb|btt)[\s\w]{0,12}marat|xcm"),
    ("bike_mtb_xco", r"\bxco\b|\bmtb\b|\bbtt\b"),
    ("bike_gran_fondo", r"gran\s*fondo|granfondo"),
    ("bike_tt_40", r"(contrarreloj|crono|time\s*trial)[\s\w]{0,12}40|40\s*-?\s*k[\s\w]{0,12}(contrarreloj|crono)"),
    ("bike_tt_20", r"contrarreloj|crono\b|time\s*trial"),
    ("bike_100", r"(?<!\d)100\s*-?\s*k[\s\w]{0,12}(bici|ciclis|bike)|marcha\s*ciclista"),
    ("obstacle", r"spartan|obst[áa]culo|\bocr\b|tough\s*mudder"),
    # Asfalto. Al final: sus cifras aparecen dentro de casi todo lo de arriba.
    ("half_marathon", r"media\s*marat|medio\s*marat|half\s*-?\s*marath|\bhalf\b|(?<!\d)21[.,]?\d?\s*-?\s*k"),
    ("marathon", r"marat[oó]n|marathon|(?<!\d)42[.,]?\d?\s*-?\s*k"),
    ("100K", r"(?<!\d)100\s*-?\s*k"),
    ("50K", r"(?<!\d)50\s*-?\s*k"),
    ("15K", r"(?<!\d)15\s*-?\s*k"),
    ("10K", r"(?<!\d)10\s*-?\s*k"),
    ("5K", r"(?<!\d)5\s*-?\s*k"),
)


def infer_distance(competition: dict) -> tuple[str | None, str]:
    """The goal's distance, and where it came from.

    Returns `(key, "declarada" | "deducida" | "desconocida")`. A declared
    distance always wins; only when the athlete left it empty do we read their
    race type and race name.
    """
    declared = str(competition.get("distance") or "").strip()
    if declared:
        # Returned even when it is not one of the four Garmin predicts: an
        # ultra, a triathlon leg or an open-water swim is a DECLARED distance
        # with no reference, which is a different problem from a distance
        # nobody can identify -- and only the second one is the athlete's to
        # fix.
        return declared, "declarada"

    # El NOMBRE primero, el tipo de carrera después, y cada uno por separado.
    #
    # Antes se juntaban los tres campos en un solo texto, y eso resucitó la
    # trampa de orden que este módulo lleva documentando desde v6, sólo que
    # entre campos en vez de entre palabras: el perfil real del atleta tiene
    # "5k Monterrico" con `race_type: "Olympic"` -- un valor heredado de un
    # desplegable viejo que nunca eligió a conciencia -- y "Olympic" casaba con
    # el triatlón olímpico antes de que "5k" llegara a mirarse. Su carrera de
    # cinco kilómetros se habría evaluado como un triatlón.
    #
    # El nombre lo escribió el atleta para ESTA carrera; el tipo es una
    # etiqueta genérica. Cuando discrepan, gana el nombre.
    for field in ("name", "race_type"):
        haystack = str(competition.get(field) or "").lower()
        if not haystack:
            continue
        for key, pattern in _DISTANCE_PATTERNS:
            if re.search(pattern, haystack):
                return key, "deducida"
    return None, "desconocida"

# Rough reference for how fast a recreational endurance athlete's race times
# improve with consistent training. NOT a law and not a prediction for this
# athlete -- see the module docstring. It exists so "22% faster in 14 weeks"
# can be put next to something, instead of being evaluated against whatever
# the model happens to remember.
_REFERENCE_MONTHLY_GAIN_PCT = 1.0

_VERDICTS_ES = {
    "conservador": "más lento que su predicción actual — hay margen para apuntar más alto",
    "alcanzable": "dentro de lo que suele lograrse en ese plazo",
    "exigente": "exigente pero no descabellado",
    "muy_exigente": "muy exigente: requiere una mejora bastante por encima de lo habitual",
    "fuera_de_rango": "fuera del rango de lo que suele lograrse en ese plazo",
    "sin_referencia": "sin predicción de Garmin para esa distancia, no hay referencia directa",
}


# Más rápido que esto no va nadie en ninguna disciplina que sirve la app: la
# contrarreloj de élite en bici ronda los 55 km/h, ~65 s/km. Un tiempo de dos
# partes que, leído como MM:SS, exigiría ir más rápido, no es MM:SS.
_RITMO_IMPOSIBLE_S_POR_KM = 60

# Metros de desnivel positivo por km a partir de los que una predicción en llano
# deja de ser referencia para la meta. Ver `assess_goal`.
_DESNIVEL_QUE_CAMBIA_LA_CARRERA = 20.0


def parse_time_to_seconds(text: Any, distance_key: str | None = None) -> int | None:
    """Accept HH:MM:SS, MM:SS, and H:MM:SS. Returns None for anything else.

    Deliberately strict: a target_time we cannot parse must read as "not
    declared", never as a number we invented from a partial match.

    ## Dos partes se leen con la distancia en la mano

    El atleta Fernando escribió "3:30" para su maratón. Leído como MM:SS son
    210 segundos: su informe dijo que la meta estaba "fuera del rango de lo que
    suele lograrse" (98,4 % de mejora sobre una predicción de 3:33:02) y que
    exigía un ritmo de "0:05/km", y el macrociclo tuvo que contradecir a su
    propio bloque. Con la distancia, una lectura MM:SS que pediría un ritmo
    imposible en cualquier deporte es H:MM. Sin distancia, se lee como antes.
    """
    if text is None:
        return None
    raw = str(text).strip()
    if not raw or not re.fullmatch(r"\d{1,2}(:\d{1,2}){1,2}", raw):
        return None

    parts = [int(p) for p in raw.split(":")]
    if len(parts) == 3:
        hours, minutes, seconds = parts
    else:
        hours, (minutes, seconds) = 0, parts
        try:
            metros = total_metres(distance_key) if distance_key else None
        except (KeyError, TypeError, ValueError):
            metros = None
        if metros and (minutes * 60 + seconds) / (metros / 1000.0) < _RITMO_IMPOSIBLE_S_POR_KM:
            hours, minutes, seconds = parts[0], parts[1], 0
    if minutes > 59 or seconds > 59:
        return None
    return hours * 3600 + minutes * 60 + seconds


def format_seconds(total: float | int | None) -> str:
    if total is None:
        return "—"
    total = int(round(total))
    hours, rest = divmod(total, 3600)
    minutes, seconds = divmod(rest, 60)
    return f"{hours}:{minutes:02d}:{seconds:02d}" if hours else f"{minutes}:{seconds:02d}"


def _parse_date(value: Any) -> date | None:
    try:
        return datetime.fromisoformat(str(value).strip()[:10]).date()
    except (ValueError, TypeError):
        return None


def _prediction_for(predictions: list, distance_key: str) -> dict | None:
    for prediction in predictions or []:
        data = prediction if isinstance(prediction, dict) else getattr(prediction, "__dict__", {})
        if data.get("distance_key") == distance_key:
            seconds = data.get("time_seconds")
            if seconds:
                return {"time_seconds": int(seconds), "distance_m": float(data.get("distance_m") or 0)}
    return None


def _verdict_for(required_pct: float, months: float) -> str:
    if required_pct <= 0:
        return "conservador"
    # `affordable` is what the reference rate alone would deliver over the time
    # available. A goal at or under it is by definition unremarkable, so that
    # is where "alcanzable" ends -- an earlier cut at half of it called 27:44
    # -> 27:00 over 14 weeks (0.8%/month, BELOW the reference) "exigente",
    # which is the framing being harsher than the evidence supports.
    affordable = _REFERENCE_MONTHLY_GAIN_PCT * max(months, 0.25)
    if required_pct <= affordable:
        return "alcanzable"
    if required_pct <= affordable * 1.5:
        return "exigente"
    if required_pct <= affordable * 2.5:
        return "muy_exigente"
    return "fuera_de_rango"


def _pace_context(target_seconds: int, distance_key: str | None, pace_bands: dict | None) -> dict | None:
    """The pace the goal actually demands, next to the athlete's own bands.

    Facts only. It says where the required pace sits among the bands this
    athlete's real threshold produced; it does not say whether that is
    reasonable, because how much weight that comparison deserves depends on
    how good the underlying data is -- which the agent is in a better position
    to judge than a rule here.
    """
    metres = _DISTANCE_METRES.get(distance_key or "")
    if not metres:
        return None
    # Cada deporte en su unidad, y las bandas (que son de CARRERA) sólo para correr. José, 2026-10-22: su
    # 400 m de natación en 6:40 llegaba al macrociclo y a la hoja como «16:40/km», comparado con sus bandas
    # de carrera; lo que exige es 1:40/100 m. Un multideporte no tiene un único ritmo.
    tramos = legs_of(distance_key)
    deporte = tramos[0][0] if len(tramos) == 1 else None
    if deporte == "swim":
        por_100 = target_seconds / (metres / 100.0)
        return {"sport": "swim", "unit": "/100 m", "required_pace_clock": format_seconds(por_100),
                "required_pace_sec_per_100m": round(por_100, 1),
                "faster_than_bands": [], "band_just_faster": None, "band_just_slower": None}
    if deporte == "bike":
        kmh = (metres / 1000.0) / (target_seconds / 3600.0)
        texto = f"{kmh:.1f}".replace(".", ",")
        return {"sport": "bike", "unit": " km/h", "required_pace_clock": texto, "required_speed_kmh": round(kmh, 1),
                "faster_than_bands": [], "band_just_faster": None, "band_just_slower": None}
    if deporte != "run":
        return None
    required = target_seconds / (metres / 1000.0)

    bands: list[tuple[str, float, float]] = []
    for key, bounds in (pace_bands or {}).items():
        try:
            fast, slow = float(bounds[0]), float(bounds[1])
        except (TypeError, ValueError, IndexError):
            continue
        if fast:
            bands.append((key, fast, slow))
    bands.sort(key=lambda b: b[1])  # fastest band first

    faster_than = [key for key, fast, _ in bands if required < fast]
    # Where the required pace actually SITS, which is the informative fact.
    # Listing every slower band it beats is technically true and useless: a
    # 5:00/km target is of course faster than the athlete's recovery jog.
    just_above = next(((k, f, s) for k, f, s in reversed(bands) if required >= f), None)
    just_below = next(((k, f, s) for k, f, s in bands if required < f), None)

    return {
        "sport": "run",
        "unit": "/km",
        "required_pace_sec_per_km": round(required, 1),
        "required_pace_clock": format_seconds(required),
        "faster_than_bands": faster_than,
        "band_just_faster": (
            {"key": just_above[0], "fast": just_above[1], "slow": just_above[2]} if just_above else None
        ),
        "band_just_slower": (
            {"key": just_below[0], "fast": just_below[1], "slow": just_below[2]} if just_below else None
        ),
    }


def _to_number(value: Any) -> float | None:
    try:
        num = float(value)
    except (TypeError, ValueError):
        return None
    return num or None


_RUN_LEG_TO_PREDICTION = {5000: "5K", 10000: "10K", 21097.5: "half_marathon", 42195: "marathon"}


def _leg_references(distance_key: str | None, predictions: list) -> list[dict[str, Any]]:
    """Lo que el reloj predice para el segmento de correr de una carrera
    multideporte, cuando ese segmento coincide con una distancia que predice.

    Es un HECHO, no un objetivo parcial: correr un 10K suelto y correr un 10K
    después de 40 km de bici no son lo mismo, y esta función no finge que lo
    sean. Sólo pone al lado el número que el atleta ya tiene.
    """
    salida = []
    for deporte, metros in legs_of(distance_key):
        if deporte != "run":
            continue
        clave = _RUN_LEG_TO_PREDICTION.get(metros)
        prediccion = _prediction_for(predictions, clave) if clave else None
        if prediccion:
            salida.append({
                "sport": "run", "metres": metros, "distance_key": clave,
                "predicted_seconds": prediccion["time_seconds"],
                "predicted_clock": format_seconds(prediccion["time_seconds"]),
                "note": "en fresco y suelto, no después de nadar y rodar",
            })
    return salida


def assess_goal(competition: dict, predictions: list, today: date | None = None,
                pace_bands: dict | None = None) -> dict | None:
    """One competition's goal against the athlete's predicted time.

    Returns None when there is nothing to assess (no target time, no date, or
    a date already past) rather than emitting a row that says nothing.
    """
    today = today or date.today()

    distance_key, distance_source = infer_distance(competition)
    target_seconds = parse_time_to_seconds(competition.get("target_time"), distance_key)
    race_date = _parse_date(competition.get("date"))
    if target_seconds is None or race_date is None or race_date < today:
        return None

    days_away = (race_date - today).days
    weeks = round(days_away / 7.0, 1)
    months = days_away / 30.44

    prediction = _prediction_for(predictions, distance_key) if distance_key else None

    assessment: dict[str, Any] = {
        "name": competition.get("name") or "Competencia",
        "date": race_date.isoformat(),
        "priority": competition.get("priority") or "B",
        "distance_key": distance_key,
        "distance_source": distance_source,
        "distance_label": DISTANCE_LABELS_ES.get(distance_key or "", distance_key),
        "raw_race_type": str(competition.get("race_type") or "").strip() or None,
        "weeks_available": weeks,
        "target_seconds": target_seconds,
        "target_clock": format_seconds(target_seconds),
        "pace_context": _pace_context(target_seconds, distance_key, pace_bands),
        # v7. Qué ES este formato, en sus segmentos. Un triatlón olímpico no es
        # "51.5 km": son 1500 m de natación, 40 km de bici y 10 km de carrera, y
        # esos tres números son lo único que permite decir algo útil sobre una
        # meta de 2:30. Guardar el formato como una distancia total tira
        # exactamente la información que lo hace evaluable.
        "format_description": describe(distance_key, competition.get("elevation_gain_m")),
        "legs": [{"sport": deporte, "metres": metros} for deporte, metros in legs_of(distance_key)],
        "is_multisport": is_multisport(distance_key),
        "elevation_gain_m": _to_number(competition.get("elevation_gain_m")),
        "elevation_driven": is_elevation_driven(distance_key),
    }

    # El segmento de CARRERA de una carrera multideporte sí tiene referencia:
    # el atleta tiene una predicción de Garmin para 10K y el segmento de correr
    # de su olímpico ES un 10K. Se reporta como el hecho que es.
    #
    # Deliberadamente NO se reparte el tiempo objetivo entre segmentos: hacerlo
    # exige suposiciones sobre transiciones y sobre el nivel relativo del
    # atleta en cada disciplina, y eso es un juicio. El reparto lo razona el
    # agente, que tiene el contexto entero.
    if assessment["is_multisport"]:
        assessment["leg_references"] = _leg_references(distance_key, predictions)

    # Con desnivel, una predicción en llano no es referencia, y el ritmo medio
    # en min/km tampoco. El macrociclo del atleta Hugo (2026-09-07) comparó su
    # UTG 21K de +1.500 m (meta 3:05) con su predicción de media maratón en
    # asfalto (1:45) y le dijo "más lento que su predicción — hay margen para
    # apuntar más alto", y a su 3760 Race de +700 m le puso "11:30/km" contra un
    # 10K de 44:31. Veinte metros por km son un 20 % de esfuerzo más en la
    # cuenta de km-esfuerzo del trail (100 m de D+ ≈ 1 km llano, ITRA): más que
    # cualquier margen con el que se juzga una meta.
    metros = _DISTANCE_METRES.get(distance_key or "")
    desnivel = assessment["elevation_gain_m"]
    m_por_km = desnivel / (metros / 1000.0) if desnivel and metros else None
    if assessment["elevation_driven"] or (m_por_km is not None and m_por_km >= _DESNIVEL_QUE_CAMBIA_LA_CARRERA):
        assessment.update({
            "pace_context": None,
            "verdict": "sin_referencia",
            "verdict_es": (f"con {round(desnivel)} m de desnivel, una predicción en llano no es referencia"
                           if desnivel else "carrera de montaña: una predicción en llano no es referencia"),
            "missing_reason": "desnivel",
            "predicted_seconds": None,
            "flat_prediction_clock": format_seconds(prediction["time_seconds"]) if prediction else None,
            "elevation_m_per_km": round(m_por_km) if m_por_km else None,
        })
        return assessment

    if not prediction:
        # Two different problems that used to render the same way. "We do not
        # know what distance this is" is fixable by the athlete in ten
        # seconds; "Garmin does not predict this distance" is not fixable at
        # all, and a swimmer or cyclist will always land there.
        assessment.update({
            "verdict": "sin_referencia",
            "verdict_es": _VERDICTS_ES["sin_referencia"],
            "missing_reason": "distancia_no_identificada" if distance_key is None else "sin_prediccion",
            "predicted_seconds": None,
        })
        return assessment

    predicted = prediction["time_seconds"]
    required_pct = (predicted - target_seconds) / predicted * 100.0
    verdict = _verdict_for(required_pct, months)

    # What the reference rate alone would put them at by race day. Offered as
    # a starting point for the agent to reason from, never as a prescription.
    reference_seconds = predicted * (1 - (_REFERENCE_MONTHLY_GAIN_PCT * months) / 100.0)

    assessment.update({
        "predicted_seconds": predicted,
        "predicted_clock": format_seconds(predicted),
        "required_improvement_pct": round(required_pct, 1),
        "required_improvement_pct_per_month": round(required_pct / months, 2) if months > 0 else None,
        "reference_monthly_gain_pct": _REFERENCE_MONTHLY_GAIN_PCT,
        "reference_seconds": int(reference_seconds),
        "reference_clock": format_seconds(reference_seconds),
        "verdict": verdict,
        "verdict_es": _VERDICTS_ES[verdict],
    })
    return assessment


def assess_goals(competitions: list, predictions: list, today: date | None = None,
                 pace_bands: dict | None = None) -> list[dict]:
    """Every upcoming competition that declared a target time, soonest first."""
    assessments = [
        assessment
        for competition in (competitions or [])
        if (assessment := assess_goal(
            competition if isinstance(competition, dict) else getattr(competition, "__dict__", {}),
            predictions, today, pace_bands,
        ))
    ]
    return sorted(assessments, key=lambda a: a["date"])


def competitions_missing_distance(competitions: list) -> list[str]:
    """Races with a target time whose distance nobody can work out.

    Surfaced in the UI so the athlete can fix in ten seconds the thing that
    silently disabled goal evaluation for their whole season.
    """
    missing: list[str] = []
    for competition in competitions or []:
        data = competition if isinstance(competition, dict) else getattr(competition, "__dict__", {})
        if parse_time_to_seconds(data.get("target_time")) is None:
            continue
        if infer_distance(data)[0] is None:
            missing.append(str(data.get("name") or "Competencia sin nombre"))
    return missing


def build_goal_evidence(prescription_state: dict | None, garmin_data: Any = None,
                        profile: dict | None = None) -> dict[str, Any]:
    """How much the athlete's predicted times are worth believing.

    v6 Track 4. A predicted time is only as good as what produced it: a watch
    with three weeks of data, a threshold pace derived from a prediction
    rather than measured, or an athlete returning from a layoff all make the
    prediction a weaker reference than it looks.

    This is handed over as evidence, not folded into a score. Deciding how
    much a thin data set should widen the uncertainty around a goal is a
    judgement about this athlete, and the agent has the whole picture --
    including their own history and what they say about themselves -- while a
    formula here would have a count of activities.
    """
    prescription_state = prescription_state or {}
    profile = profile or {}

    activities = []
    for attribute in ("all_activities", "recent_activities"):
        candidate = getattr(garmin_data, attribute, None) if garmin_data is not None else None
        if candidate:
            activities = candidate
            break

    dates = sorted(
        str(getattr(activity, "start_time", "") or "")[:10]
        for activity in activities
        if str(getattr(activity, "start_time", "") or "")
    )

    predictions = prescription_state.get("race_predictions") or []
    predicted_distances = [
        str((p if isinstance(p, dict) else getattr(p, "__dict__", {})).get("distance_key") or "")
        for p in predictions
    ]

    return {
        "activity_count": len(activities),
        "first_activity": dates[0] if dates else None,
        "last_activity": dates[-1] if dates else None,
        "predicted_distances": [d for d in predicted_distances if d],
        "threshold_pace_source": prescription_state.get("threshold_pace_source") or "none",
        "hr_zones_source": prescription_state.get("hr_zones_source") or "none",
        "self_declared_level": profile.get("training_age_level") or profile.get("experience_level"),
        "years_training": profile.get("years_training"),
        "warnings": list(prescription_state.get("warnings") or []),
    }


_THRESHOLD_SOURCE_ES = {
    "garmin_lt": "medido por el reloj",
    "garmin_ajustado": "el del reloj ajustado a un esfuerzo de ~1 hora con sus predicciones, no medido",
    "race_prediction": "derivado de una predicción de carrera, no medido",
    "manual": "introducido a mano por el atleta",
    "test": "medido en un test de umbral del atleta",
    "none": "no disponible",
}

_ZONES_SOURCE_ES = {
    "garmin": "las del propio reloj",
    "formula": "calculadas con una fórmula, no leídas del reloj",
    "none": "no disponibles",
}


def _render_pace_context(assessment: dict, pace_labels: dict[str, str] | None = None) -> list[str]:
    """The required pace next to the athlete's own bands. Facts, no reading."""
    context = assessment.get("pace_context")
    if not context:
        return []
    que = "Velocidad media" if context.get("sport") == "bike" else "Ritmo medio"
    lines = [
        f"- {que} que exige la meta: **{context['required_pace_clock']}{context.get('unit', '/km')}** sostenidos."
    ]

    def band(entry: dict | None) -> str | None:
        if not entry:
            return None
        label = (pace_labels or {}).get(entry["key"], entry["key"])
        return f"{label} ({format_seconds(entry['fast'])}–{format_seconds(entry['slow'])}/km)"

    faster, slower = band(context.get("band_just_faster")), band(context.get("band_just_slower"))
    if faster and slower:
        lines.append(f"- Ese ritmo cae **entre su banda de {faster} y su banda de {slower}**.")
    elif slower:
        lines.append(
            f"- Ese ritmo es **más rápido que su banda más rápida**, la de {slower}."
        )
    if faster or slower:
        lines.append(
            "  Las bandas salen de su umbral real; ver la ficha de prescripción para su origen."
        )
    return lines


def _render_evidence(evidence: dict | None) -> list[str]:
    """What the predictions above are actually built on."""
    if not evidence:
        return []
    lines = ["### Con cuántos datos se dice todo lo anterior", ""]

    count = evidence.get("activity_count")
    if count is not None:
        span = ""
        if evidence.get("first_activity") and evidence.get("last_activity"):
            span = f", entre {evidence['first_activity']} y {evidence['last_activity']}"
        lines.append(f"- Actividades analizadas: **{count}**{span}.")

    predicted = evidence.get("predicted_distances") or []
    if predicted:
        labels = [DISTANCE_LABELS_ES.get(d, d) for d in predicted]
        lines.append(f"- Garmin publica predicción para: {', '.join(labels)}.")
    else:
        lines.append(
            "- Garmin **no publica ninguna predicción de carrera** para este atleta. Las predicciones son "
            "de carrera a pie: un nadador o un ciclista nunca las tendrá, y tampoco quien lleve poco tiempo "
            "con el reloj."
        )

    threshold = _THRESHOLD_SOURCE_ES.get(str(evidence.get("threshold_pace_source")), "origen desconocido")
    lines.append(f"- Ritmo umbral: {threshold}.")
    zones = _ZONES_SOURCE_ES.get(str(evidence.get("hr_zones_source")), "origen desconocido")
    lines.append(f"- Zonas de FC: {zones}.")

    level = evidence.get("self_declared_level")
    years = evidence.get("years_training")
    if level or years:
        parts = []
        if level:
            parts.append(f"se declara **{level}**")
        if years:
            parts.append(f"{years} año(s) entrenando")
        lines.append(f"- El atleta {' y '.join(parts)}.")

    for warning in evidence.get("warnings") or []:
        lines.append(f"- ⚠️ {warning}")

    lines.append("")
    return lines


def _render_format(a: dict) -> list[str]:
    """Qué es esta carrera: sus segmentos, y su desnivel si lo tiene.

    Para una carrera multideporte añade la predicción del reloj para el
    SEGMENTO de correr, y dice explícitamente lo que ese número no es -- correr
    un 10K suelto y correr un 10K después de 40 km de bici no son lo mismo, y
    poner la predicción sin esa frase invita a restarla del objetivo como si lo
    fueran.
    """
    lines: list[str] = []
    if a.get("format_description"):
        lines.append(f"- Formato: {a['format_description']}")
    if a.get("elevation_driven") and not a.get("elevation_gain_m"):
        lines.append(
            "- ⚠️ Es una carrera de montaña y no hay desnivel declarado. Pídeselo: en montaña el desnivel "
            "define la carrera más que la distancia, y el ritmo en min/km deja de significar nada."
        )
    for ref in a.get("leg_references") or []:
        km = ref["metres"] / 1000
        lines.append(
            f"- Referencia del segmento de carrera ({km:g} km): Garmin le predice **{ref['predicted_clock']}** "
            f"— {ref['note']}. Es un dato para situarte, no un objetivo parcial: repartir el tiempo meta "
            "entre segmentos exige suposiciones sobre transiciones y sobre su nivel relativo en cada "
            "disciplina, y esa decisión es tuya."
        )
    return lines


def _ritmo_mensual(a: dict) -> str:
    """La mejora por mes, sólo cuando hay un mes que repartir.

    El 5K de la atleta 3 estaba a 6 días y el bloque decía "~21.49% por mes":
    una cifra que no significa nada -- nadie mejora un 21 % en un mes, ni lo
    necesita para un 4 % en una semana -- y que empuja a leer la meta como
    disparatada. Por debajo de un mes se dice el plazo, no un ritmo.
    """
    semanas = a.get("weeks_available") or 0
    por_mes = a.get("required_improvement_pct_per_month")
    if por_mes is None or semanas < 4.3:
        return f"(en {round(semanas * 7)} días: el plazo es demasiado corto para hablar de un ritmo mensual)"
    return f"(~{por_mes}% por mes)"


def render_goal_realism_block(assessments: list[dict], evidence: dict | None = None,
                              pace_labels: dict[str, str] | None = None) -> str:
    """The framing block injected into the season planner's prompt.

    Returns "" when no goal could be assessed, so an athlete with no declared
    target times costs no prompt tokens.
    """
    if not assessments:
        return ""

    lines: list[str] = [
        "## Metas declaradas frente a la condición actual del atleta",
        "",
        "Esto es **contexto para que juzgues, no un veredicto**. Ninguna meta está rechazada aquí, y "
        "ninguna conclusión está tomada: los números están para que decidas tú, pesando también con "
        "cuántos datos se dicen (ver el final del bloque).",
        "",
    ]

    for a in assessments:
        header = f"### {a['name']} — {a['date']} (prioridad {a['priority']}, en {a['weeks_available']} semanas)"
        lines.append(header)

        # v7: qué ES esta carrera, en sus segmentos y con su desnivel. Sin
        # esto, "Triatlón Standard" y "Ultra 50K" eran nombres sin contenido
        # para el planificador de temporada.
        lines.extend(_render_format(a))

        if a["verdict"] == "sin_referencia":
            distance = a["distance_label"] or "distancia sin identificar"
            lines.append(f"- Meta declarada: **{a['target_clock']}** ({distance})")
            if a.get("missing_reason") == "desnivel":
                desnivel = a.get("elevation_gain_m")
                llano = a.get("flat_prediction_clock")
                lines.append(
                    "- **Tiene desnivel"
                    + (f" ({round(desnivel)} m, ~{a['elevation_m_per_km']} m por km)" if desnivel and a.get(
                        "elevation_m_per_km") else f" ({round(desnivel)} m)" if desnivel else "")
                    + "**: una predicción en llano no dice cuánto tardará aquí"
                    + (f" (en asfalto, Garmin le predice {llano})" if llano else "")
                    + ", y un ritmo medio en min/km tampoco. No la compares con la meta: juzga la meta con su "
                    "experiencia en montaña, cuánto desnivel ha hecho en sus semanas y lo que observaron los "
                    "expertos, y di abiertamente si no puedes juzgarla."
                )
            elif a.get("missing_reason") == "distancia_no_identificada":
                race_type = a.get("raw_race_type")
                said = f' (dice «{race_type}»)' if race_type else ""
                lines.append(
                    "- **No se pudo identificar la distancia**: ni el campo de distancia, ni el tipo de "
                    f"carrera{said}, ni el nombre permiten deducirla. Sin distancia no hay con qué "
                    "comparar el tiempo objetivo. Dile al atleta que la ponga en su perfil."
                )
            else:
                lines.append(
                    "- Garmin no publica una predicción para esta distancia, así que no hay referencia "
                    "directa. Apóyate en el ritmo umbral, en la potencia si es en bici, y en lo que "
                    "observaron los expertos — y di abiertamente si no puedes juzgarlo."
                )
            lines.extend(_render_pace_context(a, pace_labels))
            lines.append("")
            continue

        if a.get("distance_source") == "deducida":
            lines.append(
                f"- ⚠️ La distancia ({a['distance_label']}) **se dedujo** del tipo o del nombre de la "
                "carrera; el atleta no la declaró. Si no cuadra, dilo."
            )
        lines.extend([
            f"- Predicción actual de Garmin ({a['distance_label']}): **{a['predicted_clock']}**",
            f"- Meta declarada: **{a['target_clock']}** → exige mejorar **{a['required_improvement_pct']}%** "
            f"{_ritmo_mensual(a)}",
        ])
        lines.extend(_render_pace_context(a, pace_labels))
        lines.extend([
            f"- Referencia orientativa: ~{a['reference_monthly_gain_pct']}%/mes para un atleta recreativo "
            f"entrenando con consistencia → pondría la marca cerca de **{a['reference_clock']}**",
            f"- Lectura orientativa: {a['verdict_es']}",
            "",
        ])

    lines.extend(_render_evidence(evidence))

    lines.extend([
        "### Qué hacer con esto",
        "- **Juzga tú, con todo lo que tienes.** Ni la referencia de mejora ni la lectura orientativa de "
        "arriba deciden nada: son un punto de partida para que razones con la predicción, el ritmo que "
        "exige la meta, las bandas reales del atleta, lo que observaron los expertos, su historial y lo "
        "que el propio atleta declara sobre su nivel.",
        "- **Pesa la fidelidad de los datos antes que los datos.** Una predicción construida sobre pocas "
        "semanas de reloj, sobre un umbral derivado en vez de medido, o sobre un atleta que volvió hace "
        "poco de un parón, es una referencia mucho más débil que la misma cifra con dos años de historial "
        "detrás. Cuando la evidencia sea fina, dilo y razona con más margen — no la trates como un hecho, "
        "ni tampoco la descartes.",
        "- La referencia de mejora es **orientativa y gruesa**, no una ley ni una predicción para este "
        "atleta. Un principiante desde una base muy baja la supera con facilidad; un atleta bien entrenado "
        "rara vez la alcanza; y quien vuelve de un parón sigue otra curva. **Puedes discrepar de esta "
        "lectura si los datos del atleta lo justifican — dilo y explica por qué.**",
        "- Si una meta no cuadra, **no la ignores ni la contradigas en silencio**: nómbrala, di con qué "
        "números no cuadra, y **propón un objetivo alcanzable** para esa misma carrera. Un entrenador real "
        "no acepta cualquier meta ni se limita a decir que no: ofrece la que sí.",
        "- Una meta conservadora también es información: si la marca declarada ya está por debajo de su "
        "condición actual, dilo y sugiere apuntar más alto.",
        "- La meta declarada **no cambia el plan por sí sola**. El macrociclo se construye sobre lo que el "
        "atleta puede absorber, no sobre lo que le gustaría correr.",
    ])

    return "\n".join(lines).strip()
