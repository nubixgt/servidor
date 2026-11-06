"""Deterministic Garmin Connect workout JSON builder.

No LLM ever touches this JSON — Calendarizar.py's old approach (an LLM
hand-writing this entire nested schema from scratch) is exactly what caused
hallucinated/broken uploads. Here the LLM only ever produces a structured
WorkoutDay/WorkoutStep (services/ai/langgraph/schemas/plan_outputs.py); this
module is pure, deterministic Python turning that into Garmin's exact JSON.

Every ID below was empirically confirmed against a live Garmin Connect
account (create -> read back -> verify -> delete), not guessed:
  sportType:        running=1, cycling=2, swimming=4, strength_training=5, walking=11
  stepType:         warmup=1, cooldown=2, interval=3, recovery=4, rest=5, repeat=6
  endCondition:     lap.button=1, time=2, distance=3, iterations=7, reps=10
  workoutTargetType: no.target=1, power.zone=2, heart.rate.zone=4,
                     speed.zone=5, pace.zone=6
                     (both 5 and 6 take m/s; see _TARGET_TYPES for which we
                     send and why)

Strength workouts (Fase 4) reuse the exact same ExecutableStepDTO/
RepeatGroupDTO shapes as endurance sports -- confirmed by creating a real
strength workout via garth directly and reading the raw API response back:
a "4 sets x 8 reps" exercise is a RepeatGroupDTO(numberOfIterations=4)
wrapping one ExecutableStepDTO with endCondition=reps/endConditionValue=8,
exactly how repeat_count already wraps endurance intervals (see
_build_workout_steps). No new step-building path was needed: reps just
became a fourth duration_type alongside time/distance/lap_button, and
repeat_count doubles as "sets" for these steps. v7: los campos `category`/`exerciseName` de Garmin YA se rellenan. Lo que
faltaba no era una forma de mapearlos sino saber cuales acepta, y eso dejo de
ser una adivinanza cuando el atleta creo entrenos de referencia en su cuenta y
se leyeron de vuelta -- ver services/garmin/garmin_vocabulary.py. La categoria
va siempre (sobrevivio en 14 de 14) y el nombre exacto solo si esta verificado;
lo que no se reconoce se omite y la descripcion sigue llevandolo, que es lo que
hacia antes.
"""

import logging
import re
from dataclasses import dataclass

from services.ai.langgraph.schemas.plan_outputs import (
    TargetType,
    WorkoutDay,
    WorkoutStep,
    slot_suffix,
)
from services.garmin.garmin_vocabulary import (
    MULTISPORT_TYPE,
    normalise_category,
    swim_fields,
    verified_exercise,
)

SPORT_TYPES = {
    "run": {"sportTypeId": 1, "sportTypeKey": "running"},
    "bike": {"sportTypeId": 2, "sportTypeKey": "cycling"},
    "swim": {"sportTypeId": 4, "sportTypeKey": "swimming"},
    "strength": {"sportTypeId": 5, "sportTypeKey": "strength_training"},
    "walk": {"sportTypeId": 11, "sportTypeKey": "walking"},
}

# Sports supported by this builder. "brick" (multi-sport-in-one-session,
# e.g. bike->run) is still intentionally out of scope: it needs Garmin's
# separate multi-sport workout format (multiple workoutSegments with
# different sportTypes plus a transition step), a genuinely different
# structure from every sport above, which all use ONE segment/sportType.
# A clean fast-follow once there's a real need, not silently-wrong output
# today -- see garmin_upload_service.py for the Spanish error athletes see
# when a plan schedules one.
SUPPORTED_SPORTS = frozenset(SPORT_TYPES)

# El deporte de un entreno con varios tramos. Sale del diccionario verificado
# (garmin_vocabulary.MULTISPORT_TYPE) y NO de aqui: se puso 9 a mano, deducido
# de la lista de deportes, y el id real es 10. Garmin acepto la subida y la
# guardo como un entreno de un solo deporte -- el brick llego al reloj con el
# tramo de bici y sin el de carrera, sin un solo error en el log.

_STEP_TYPES = {
    "warmup": {"stepTypeId": 1, "stepTypeKey": "warmup"},
    "cooldown": {"stepTypeId": 2, "stepTypeKey": "cooldown"},
    "interval": {"stepTypeId": 3, "stepTypeKey": "interval"},
    "main": {"stepTypeId": 3, "stepTypeKey": "interval"},  # Garmin has no distinct "main" step type
    "recovery": {"stepTypeId": 4, "stepTypeKey": "recovery"},
    "rest": {"stepTypeId": 5, "stepTypeKey": "rest"},
}
_REPEAT_STEP_TYPE = {"stepTypeId": 6, "stepTypeKey": "repeat"}

_END_CONDITIONS = {
    "time": {"conditionTypeId": 2, "conditionTypeKey": "time"},
    "distance": {"conditionTypeId": 3, "conditionTypeKey": "distance"},
    "lap_button": {"conditionTypeId": 1, "conditionTypeKey": "lap.button"},
    "reps": {"conditionTypeId": 10, "conditionTypeKey": "reps"},
}
_ITERATIONS_CONDITION = {"conditionTypeId": 7, "conditionTypeKey": "iterations"}

_TARGET_TYPES = {
    TargetType.NONE: {"workoutTargetTypeId": 1, "workoutTargetTypeKey": "no.target"},
    TargetType.POWER: {"workoutTargetTypeId": 2, "workoutTargetTypeKey": "power.zone"},
    TargetType.HR_ZONE: {"workoutTargetTypeId": 4, "workoutTargetTypeKey": "heart.rate.zone"},
    # Garmin has BOTH speed.zone (5) and pace.zone (6). Confirmed empirically:
    # it accepts either and stores whichever you send, unchanged, with the same
    # m/s values -- the difference is only how the athlete sees it. pace.zone
    # renders as "Ritmo 5:45 /km", which is how a runner reads a target;
    # speed.zone renders as a speed. The reference workout this app is being
    # measured against uses pace.zone, so this does too.
    TargetType.PACE: {"workoutTargetTypeId": 6, "workoutTargetTypeKey": "pace.zone"},
}
_NO_TARGET = _TARGET_TYPES[TargetType.NONE]


@dataclass
class AthleteZoneConfig:
    hr_zones: dict[int, tuple[float, float]] | None = None
    """zone number (1-5) -> (low_bpm, high_bpm)"""
    pace_zones: dict[int, tuple[float, float]] | None = None
    """zone number (1-5) -> (low_m_per_s, high_m_per_s) — Garmin speed.zone is m/s, not min/km"""
    ftp_watts: float | None = None
    power_zones: dict[int, tuple[float, float]] | None = None
    """zone number (1-5) -> (low_watts, high_watts)"""


class UnsupportedSportError(ValueError):
    pass


# %-of-threshold breakpoints (Friel-style for HR/pace, Coggan-style for power)
# used to derive 5 zones from a single threshold value, so the athlete only
# has to enter one number per metric instead of 5 hand-picked ranges. This is
# a reasonable approximation, not a precision claim.
_HR_ZONE_PCT = {1: (0.60, 0.81), 2: (0.81, 0.89), 3: (0.90, 0.93), 4: (0.94, 0.99), 5: (1.00, 1.10)}
_PACE_ZONE_PCT = {1: (0.70, 0.80), 2: (0.80, 0.88), 3: (0.88, 0.95), 4: (0.95, 1.02), 5: (1.02, 1.15)}
_POWER_ZONE_PCT = {1: (0.00, 0.55), 2: (0.56, 0.75), 3: (0.76, 0.90), 4: (0.91, 1.05), 5: (1.06, 1.20)}


def compute_zone_config(
    lactate_threshold_hr: float | None = None,
    lactate_threshold_pace_sec_per_km: float | None = None,
    ftp_watts: float | None = None,
    garmin_hr_zones: dict[int, tuple[int, int | None]] | None = None,
) -> AthleteZoneConfig:
    """Derives 5-zone HR/pace/power tables from a single threshold value each.
    Any threshold left as None simply leaves that zone map empty — callers
    (see _build_target) already degrade missing zone data to no.target
    instead of failing the upload.

    `garmin_hr_zones`, when given, are the athlete's OWN configured zones read
    back from Garmin (see DataExtractor.get_garmin_hr_zones) and always win
    over the formula below. They have to: for a real athlete with LTHR 176 the
    formula puts Z2 at 143-157 bpm while their watch uses 139-152, so a plan
    built on the formula prescribes a "Zone 2" run the athlete's own device
    disagrees with. Pass the table for the sport being built -- Garmin keeps a
    separate one per sport and they differ a lot (the same athlete's swimming
    Z2 is 112-129).

    Note the resulting `hr_zones` table is informational for most uploads:
    _build_target emits heart-rate targets as a NAMED zone so the watch applies
    the athlete's own boundaries itself. It's only consulted when a step asks
    for an explicit bpm range.
    """
    hr_zones = None
    if garmin_hr_zones:
        # The top zone is open-ended in Garmin's table; give it a finite upper
        # bound so it can still be expressed as a bpm range when one is needed.
        hr_zones = {
            zone: (float(low), float(high if high is not None else low + 20))
            for zone, (low, high) in garmin_hr_zones.items()
        }
    elif lactate_threshold_hr:
        hr_zones = {z: (round(lactate_threshold_hr * lo), round(lactate_threshold_hr * hi)) for z, (lo, hi) in _HR_ZONE_PCT.items()}

    pace_zones = None
    if lactate_threshold_pace_sec_per_km:
        threshold_speed_m_s = 1000.0 / lactate_threshold_pace_sec_per_km
        pace_zones = {z: (round(threshold_speed_m_s * lo, 3), round(threshold_speed_m_s * hi, 3)) for z, (lo, hi) in _PACE_ZONE_PCT.items()}

    power_zones = None
    if ftp_watts:
        power_zones = {z: (round(ftp_watts * lo), round(ftp_watts * hi)) for z, (lo, hi) in _POWER_ZONE_PCT.items()}

    return AthleteZoneConfig(hr_zones=hr_zones, pace_zones=pace_zones, ftp_watts=ftp_watts, power_zones=power_zones)


def pace_sec_per_km_to_m_per_s(sec_per_km: float) -> float:
    """The single place pace crosses into Garmin's unit system.

    Plans express pace the way athletes and coaches do -- seconds per
    kilometre, where a SMALLER number is faster. Garmin stores it as speed in
    metres per second, where a LARGER number is faster. Getting this backwards
    or skipping it produces a workout the watch accepts and no human could run.
    """
    if sec_per_km <= 0:
        raise ValueError(f"pace must be positive, got {sec_per_km} sec/km")
    return round(1000.0 / sec_per_km, 3)


def _build_target(step: WorkoutStep, zones: AthleteZoneConfig) -> dict:
    if step.target_type == TargetType.NONE:
        return {"targetType": dict(_NO_TARGET), "targetValueOne": None, "targetValueTwo": None, "zoneNumber": None}

    target_type_dict = dict(_TARGET_TYPES[step.target_type])

    # An explicit range always wins over a named-zone lookup.
    if step.target_low is not None and step.target_high is not None:
        low, high = step.target_low, step.target_high
        if step.target_type == TargetType.PACE:
            # target_low/target_high are documented (and prompted for) in
            # seconds per kilometre; Garmin needs m/s. This conversion used to
            # be missing entirely, so a 4:30/km target went up as "270 m/s".
            # Converting also flips which end is which: the slower pace (larger
            # sec/km) becomes the smaller speed, so targetValueOne stays the
            # slow bound the way Garmin expects.
            slow_bound = pace_sec_per_km_to_m_per_s(max(low, high))
            fast_bound = pace_sec_per_km_to_m_per_s(min(low, high))
            low, high = slow_bound, fast_bound
        return {"targetType": target_type_dict, "targetValueOne": low, "targetValueTwo": high, "zoneNumber": None}

    if step.target_type == TargetType.HR_ZONE and step.target_zone is not None:
        # Always prefer the NAMED zone for heart rate: Garmin then applies the
        # zone boundaries configured on the athlete's own account, instead of
        # us pushing a bpm range we derived. Sending a custom range is exactly
        # how this athlete ended up with a "Zone 2" starting at 143 bpm when
        # their watch starts Zone 2 at 139.
        return {"targetType": target_type_dict, "targetValueOne": None, "targetValueTwo": None, "zoneNumber": step.target_zone}

    # Pace/power zones resolve to explicit ranges (Garmin has no named pace
    # zone the way it has named HR zones). These tables are already stored in
    # Garmin's units -- m/s for pace, watts for power -- by compute_zone_config.
    if step.target_zone is not None:
        zone_map = {
            TargetType.PACE: zones.pace_zones,
            TargetType.POWER: zones.power_zones,
        }.get(step.target_type)
        if zone_map and step.target_zone in zone_map:
            low, high = zone_map[step.target_zone]
            return {"targetType": target_type_dict, "targetValueOne": low, "targetValueTwo": high, "zoneNumber": None}

    # No usable zone data for pace/power -> degrade to no.target rather than
    # fail the whole upload (see AthleteZoneConfig / web_app/server.py fallback).
    return {"targetType": dict(_NO_TARGET), "targetValueOne": None, "targetValueTwo": None, "zoneNumber": None}


def _build_executable_step(step: WorkoutStep, order: int, zones: AthleteZoneConfig, child_step_id: int | None, force_no_target: bool = False) -> dict:
    end_condition = dict(_END_CONDITIONS.get(step.duration_type or "time", _END_CONDITIONS["time"]))
    target = (
        {"targetType": dict(_NO_TARGET), "targetValueOne": None, "targetValueTwo": None, "zoneNumber": None}
        if force_no_target
        else _build_target(step, zones)
    )
    return {
        "type": "ExecutableStepDTO",
        "stepOrder": order,
        "stepType": dict(_STEP_TYPES.get(step.step_type, _STEP_TYPES["interval"])),
        "childStepId": child_step_id,
        "description": step.description,
        "endCondition": end_condition,
        "endConditionValue": step.duration_value,
        # Estilo, material y tipo de drill de natacion. Solo los valores que se
        # han visto volver de un entreno real; el resto se omite y la
        # descripcion sigue llevandolos. Ver garmin_vocabulary.py.
        **swim_fields(step.stroke, step.equipment, step.drill),
        **_strength_fields(step),
        **target,
    }


def _strength_fields(step: WorkoutStep) -> dict:
    """Categoria y nombre del ejercicio, cuando el paso viene de una sesion de
    fuerza desglosada.

    La categoria va siempre que se reconozca; el nombre solo si esa pareja se
    ha visto volver de un entreno real. Garmin descarta en silencio un nombre
    que no conoce -- medido: de catorce enviados, siete desaparecieron -- y un
    ejercicio generico donde el plan decia otra cosa es peor que no mandarlo,
    porque la descripcion del paso ya lleva el nombre de verdad.
    """
    categoria = normalise_category(step.garmin_category)
    if not categoria:
        return {}
    salida = {"category": categoria}
    nombre = verified_exercise(categoria, step.garmin_exercise)
    if nombre:
        salida["exerciseName"] = nombre
    return salida


def _repeat_group(step: WorkoutStep, order: int, child_id: int, children: list[dict]) -> dict:
    return {
        "type": "RepeatGroupDTO",
        "stepOrder": order,
        "stepType": dict(_REPEAT_STEP_TYPE),
        "childStepId": child_id,
        "numberOfIterations": step.repeat_count,
        # Garmin silently corrupts the repeat count if conditionTypeId is
        # omitted here, so _ITERATIONS_CONDITION always carries both halves.
        "endCondition": dict(_ITERATIONS_CONDITION),
        "endConditionValue": step.repeat_count,
        "workoutSteps": children,
    }


def _build_workout_steps(steps: list[WorkoutStep], zones: AthleteZoneConfig,
                         force_no_target: bool = False, first_order: int = 1) -> list[dict]:
    """Flattens a WorkoutStep tree into Garmin's workoutSteps array.

    Two things become RepeatGroupDTOs:
      * a step with `substeps` -- a real repeat block, e.g. 4x(3' hard + 2'
        easy). All of its children go inside the ONE group, which is what
        makes the recovery land between repetitions instead of after all of
        them.
      * a plain step with repeat_count > 1 -- the degenerate single-child
        case, still used for strength sets ("4 sets x 8 reps").

    Garmin requires globally sequential stepOrder across the whole flattened
    structure (a repeat group consumes one slot for itself plus one per
    descendant), so the LLM's step_order is treated as a hint and recomputed
    here. Repeat blocks may nest, so this walks recursively.
    """
    # `first_order` existe por el multideporte: el `stepOrder` de Garmin es
    # GLOBAL y corre a traves de los tramos (1-21 natacion, 22-25 bici, 26
    # carrera en el entreno de referencia 1679387475). Si el tramo de bici
    # empezara en 1 otra vez, Garmin guardaria los pasos pisados.
    counters = {"order": first_order, "child_id": 1}

    def build(step_list: list[WorkoutStep], child_step_id: int | None) -> list[dict]:
        result: list[dict] = []
        for step in step_list:
            if step.substeps:
                group_order = counters["order"]
                counters["order"] += 1
                group_child_id = counters["child_id"]
                counters["child_id"] += 1
                children = build(step.substeps, group_child_id)
                result.append(_repeat_group(step, group_order, group_child_id, children))
            elif step.repeat_count and step.repeat_count > 1:
                group_order = counters["order"]
                counters["order"] += 1
                group_child_id = counters["child_id"]
                counters["child_id"] += 1
                inner = _build_executable_step(
                    step, counters["order"], zones,
                    child_step_id=group_child_id, force_no_target=force_no_target,
                )
                counters["order"] += 1
                result.append(_repeat_group(step, group_order, group_child_id, [inner]))
            else:
                result.append(_build_executable_step(
                    step, counters["order"], zones,
                    child_step_id=child_step_id, force_no_target=force_no_target,
                ))
                counters["order"] += 1
        return result

    return build(steps, None)



# Cuánto dura una repetición cuando hay que estimar el tiempo de un ejercicio.
# Sólo se usa para el `description`, nunca para lo que se manda al reloj.
_SECONDS_PER_REP = 3.5


# Lo que dice que la serie no tiene un número fijo: la cierra el atleta.
_SIN_NUMERO_FIJO = re.compile(r"amrap|al fallo|hasta (?:el )?fallo|m[aá]xim[oa]s|las que puedas", re.I)
# La PRIMERA cantidad de la serie: un número o un rango, y su unidad si es un tiempo.
_CANTIDAD = re.compile(
    r"(\d+(?:[.,]\d+)?)(?:\s*(?:-|–|—|\ba\b|x)\s*(\d+(?:[.,]\d+)?))?"
    r"\s*(minutos?\b|min\b|'|\"|segundos?\b|seg\b|s\b)?",
    re.I,
)


def _reps_from_text(reps: str | None) -> tuple[str, float | None]:
    """Convierte las repeticiones escritas por el entrenador en algo que
    Garmin pueda terminar.

    Un `reps` de `StrengthExercise` es TEXTO a propósito -- "8-10",
    "12 por lado", "30 s" -- porque un isométrico se mide en tiempo y un
    unilateral se cuenta por lado, y forzar un número entero ahí obligaría al
    entrenador a mentir en uno de los dos casos.

    Devuelve `("reps", n)` o `("time", segundos)`. Cuando el texto no contiene
    ningún número utilizable devuelve `("lap_button", None)`: el atleta cierra
    la serie cuando termina, que es honesto y siempre ejecutable.

    **Manda la PRIMERA cantidad; un tempo o una pausa son anotaciones** (José,
    2026-10-19: «puso por tiempo 2 segundos, por lo que ni vi el entreno que
    tocaba»). Antes se buscaba un tiempo en CUALQUIER parte del texto y ganaba
    al número: «10 (2" arriba, 2" abajo)» subía como una serie de 2 s, y en las
    semanas guardadas en local también «12-15 con pausa 1" arriba» (1 s) y
    «8-10 por lado (3-4 s bajada)» (4 s). Lo de entre paréntesis no cuenta, y un
    texto con dos trabajos («10 dinámicos + 30" arriba») o sin número fijo
    («AMRAP dejando 2 reps») lo cierra el atleta: elegir uno sería mentirle.
    """
    texto = (reps or "").strip().lower()
    if not texto or _SIN_NUMERO_FIJO.search(texto):
        return "lap_button", None
    principal = re.sub(r"\([^)]*\)", " ", texto)
    # Dos trabajos en la misma serie (cada trozo empieza con su cantidad): ninguno solo es la serie.
    trozos = [t for t in re.split(r"\+|;|,(?!\d)", principal) if re.search(r"\d", t)]
    if len(trozos) > 1 and all(re.match(r"\s*\d", t) for t in trozos):
        return "lap_button", None
    m = _CANTIDAD.search(principal)
    if not m:
        return "lap_button", None
    valor = float((m.group(2) or m.group(1)).replace(",", "."))
    unidad = (m.group(3) or "").lower()
    if unidad in ("'", "min") or unidad.startswith("minuto"):
        return "time", valor * 60
    if unidad:
        return "time", valor
    # "8-10" -> 10. El extremo alto, no el bajo: el reloj marca cuándo PUEDE
    # terminar la serie, y cortarla en 8 le quitaría al atleta las dos
    # repeticiones que el entrenador sí le dio.
    return "reps", valor


def _strength_steps_from_block(day: WorkoutDay) -> list[WorkoutStep] | None:
    """La sesión de fuerza desglosada, como pasos que Garmin puede ejecutar.

    v7. Hasta ahora `steps` llevaba UN bloque en blanco de 45 minutos y el
    desglose vivía sólo en `strength_block`, que se lee en el móvil. Eso venía
    de que los identificadores de ejercicio de Garmin eran una incógnita; una
    vez leídos de entrenos reales, la traducción es mecánica y el atleta gana
    la sesión en la muñeca con sus series, sus descansos y el nombre de cada
    ejercicio.

    La forma es la que usan los propios entrenos de Garmin (leída de "Día de
    piernas", 1679371824): **un bloque de repetición por ejercicio**, con
    `iterations` = las series, conteniendo el ejercicio y su descanso. Encaja
    exactamente con lo que `StrengthExercise` ya guardaba.

    Devuelve None cuando no hay desglose, y entonces el camino de siempre
    (un bloque continuo) sigue funcionando sin cambios.
    """
    bloque = getattr(day, "strength_block", None)
    ejercicios = getattr(bloque, "exercises", None) if bloque else None
    if not ejercicios:
        return None

    pasos: list[WorkoutStep] = []
    orden = 1
    calentamiento = getattr(bloque, "warmup", "")
    if calentamiento:
        pasos.append(WorkoutStep(
            step_order=orden, step_type="warmup", duration_type="lap_button",
            description=f"Calentamiento: {calentamiento}"[:512],
        ))
        orden += 1

    grupos = [g for g in (getattr(bloque, "groups", None) or []) if getattr(g, "exercises", None)]
    if grupos:
        # Beta 2.1: superseries. Una ronda por grupo con sus ejercicios dentro.
        for paso in _pasos_por_grupos(grupos):
            paso.step_order = orden
            pasos.append(paso)
            orden += 1
    else:
        for ejercicio in ejercicios:
            series = max(1, int(getattr(ejercicio, "sets", 1) or 1))
            trabajo, detalle = _paso_de_ejercicio(ejercicio)
            hijos = [trabajo]
            descanso = getattr(ejercicio, "rest_seconds", None)
            if descanso:
                hijos.append(_paso_de_descanso(descanso, orden=2))
            if series < 2:
                # Una sola serie no es una repetición: `WorkoutStep` rechaza
                # `substeps` con repeat_count 1, y antes de la Beta 2.1 eso
                # tumbaba la subida de un "Farmer carry 1x40 m" con descanso.
                for hijo in hijos:
                    hijo.step_order = orden
                    pasos.append(hijo)
                    orden += 1
                continue
            pasos.append(WorkoutStep(
                step_order=orden, step_type="interval", repeat_count=series,
                description=f"{series}x {detalle}"[:512], substeps=hijos,
            ))
            orden += 1

    vuelta = getattr(bloque, "cooldown", "")
    if vuelta:
        pasos.append(WorkoutStep(
            step_order=orden, step_type="cooldown", duration_type="lap_button",
            description=f"Vuelta a la calma: {vuelta}"[:512],
        ))

    total = _contar_pasos(pasos)
    if total > MAX_PASOS_DE_UN_ENTRENO:
        # Garmin rechaza la subida ENTERA por encima de 50 pasos. Mejor que
        # perder el día: al reloj va el bloque de tiempo de `steps` y el
        # desglose sigue en la app y en la hoja impresa.
        logger.warning(
            "Fuerza del %s: %d pasos superan el tope de Garmin (%d); al reloj va el bloque de tiempo",
            getattr(day, "date", "?"), total, MAX_PASOS_DE_UN_ENTRENO,
        )
        return None
    return pasos


# Garmin rechaza la subida entera de un entreno con más de 50 pasos ("Workout
# exceeds maximum expected number of 50 steps", visto en integraciones de
# terceros). Se cuentan también los grupos de repetición: es la cuenta prudente.
MAX_PASOS_DE_UN_ENTRENO = 50

logger = logging.getLogger(__name__)


def _contar_pasos(pasos: list[WorkoutStep]) -> int:
    return sum(1 + _contar_pasos(list(p.substeps or [])) for p in pasos)


def _paso_de_ejercicio(ejercicio) -> tuple[WorkoutStep, str]:
    """El paso de trabajo de un ejercicio, y su detalle en texto."""
    tipo, valor = _reps_from_text(getattr(ejercicio, "reps", None))
    nombre = (getattr(ejercicio, "name", "") or "Ejercicio").strip()
    carga = (getattr(ejercicio, "load", "") or "").strip()
    detalle = f"{nombre} — {getattr(ejercicio, 'reps', '')}"
    if carga:
        detalle += f", {carga}"
    trabajo = WorkoutStep(
        step_order=1, step_type="interval", duration_type=tipo, duration_value=valor,
        description=detalle[:512],
        garmin_category=normalise_category(getattr(ejercicio, "garmin_category", None)),
        # `video_key` como respaldo: los planes generados antes de que
        # existiera `garmin_exercise` sólo tienen aquél, y algunos traen
        # ahí un identificador que sí coincide. `verified_exercise`
        # descarta lo que no reconozca, así que probar no puede colar un
        # ejercicio equivocado.
        garmin_exercise=(getattr(ejercicio, "garmin_exercise", None)
                         or getattr(ejercicio, "video_key", None)),
    )
    return trabajo, detalle


def _paso_de_descanso(segundos, orden: int) -> WorkoutStep:
    return WorkoutStep(
        step_order=orden, step_type="rest", duration_type="time",
        duration_value=float(segundos), description=f"Descanso {float(segundos):.0f}s",
    )


def _rondas_del_grupo(ejercicios: list) -> list[tuple[int, list]]:
    """Las rondas de una superserie cuyas series no son iguales.

    Garmin repite un grupo entero: no puede hacer 4 series de un ejercicio y 3
    del otro dentro de la misma ronda. Así que se parte: rondas = el menor
    número de series con todos, y otra con los que tienen más. La Upper A de
    Fernando (press 4, elevaciones 4, crunch 3) queda 3×(los tres) y 1×(press y
    elevaciones), que son exactamente sus series.
    """
    series_de = [max(1, int(getattr(e, "sets", 1) or 1)) for e in ejercicios]
    salida, hechas = [], 0
    for nivel in sorted(set(series_de)):
        activos = [e for e, s in zip(ejercicios, series_de, strict=True) if s >= nivel]
        salida.append((nivel - hechas, activos))
        hechas = nivel
    return salida


def _pasos_por_grupos(grupos: list) -> list[WorkoutStep]:
    """Una superserie como UN grupo de repetición con todos sus ejercicios dentro.

    El descanso entre ejercicios va entre ellos (no después del último) y el de
    entre rondas cierra la ronda; el reloj lo hace también tras la última, porque
    un grupo no puede saltarse un paso. Un grupo de un solo ejercicio sin descanso
    de ronda usa el suyo entre series, como un plan de siempre.
    """
    pasos: list[WorkoutStep] = []
    for grupo in grupos:
        for rondas, activos in _rondas_del_grupo(list(grupo.exercises)):
            hijos: list[WorkoutStep] = []
            nombres = []
            for i, ejercicio in enumerate(activos):
                trabajo, detalle = _paso_de_ejercicio(ejercicio)
                trabajo.step_order = len(hijos) + 1
                hijos.append(trabajo)
                nombres.append(detalle)
                if grupo.rest_between_exercises_s and i < len(activos) - 1:
                    hijos.append(_paso_de_descanso(grupo.rest_between_exercises_s, orden=len(hijos) + 1))
            descanso = grupo.rest_between_rounds_s or (
                getattr(activos[0], "rest_seconds", None) if len(activos) == 1 else None)
            if descanso:
                hijos.append(_paso_de_descanso(descanso, orden=len(hijos) + 1))
            pasos.extend(_envolver_en_rondas(hijos, rondas, grupo, nombres))
    return pasos


def _envolver_en_rondas(hijos: list[WorkoutStep], rondas: int, grupo, nombres: list[str]) -> list[WorkoutStep]:
    """Un grupo de repetición, o los pasos sueltos si sólo va una vez.

    `WorkoutStep` no admite una repetición de 1 (pide poner los pasos directamente),
    y la Upper A de Fernando tiene justo eso: la cuarta serie de press y
    elevaciones, que el crunch no lleva. El responsable la miró suelta en el
    entreno 1701100942 y la dio por buena (2026-09-17). Si algún día se prefiere
    enmarcarla como ronda, Garmin SÍ guarda una repetición de 1: el entreno de
    verificación 1701118922 volvió con `numberOfIterations` 1 y sus tres pasos
    dentro (borrado después).
    """
    if rondas < 2:
        return hijos
    etiqueta = f"{grupo.label} · " if getattr(grupo, "label", "") else ""
    return [WorkoutStep(
        step_order=1, step_type="interval", repeat_count=rondas,
        description=f"{etiqueta}{rondas}x {' + '.join(nombres)}"[:512], substeps=hijos,
    )]

def build_single_sport_workout(day: WorkoutDay, zones: AthleteZoneConfig) -> dict:
    if day.sport not in SUPPORTED_SPORTS:
        raise UnsupportedSportError(f"Sport '{day.sport}' is not supported by this builder yet")

    sport_type = dict(SPORT_TYPES[day.sport])
    # Swim targets aren't independently verified (different units, no
    # confirmed pace zone semantics for pool swimming) — always no.target.
    # Strength has no HR/pace/power zone concept at all (each step is
    # reps-based, see _END_CONDITIONS["reps"]) — same treatment.
    #
    # v7, sobre natación: lo que SÍ está verificado y ahora se usa es la
    # condición de fin por DISTANCIA (endCondition=3), así que una serie se
    # sube como `8x100m` de verdad en vez de como un bloque continuo de
    # tiempo. Lo que sigue sin verificarse contra la cuenta real son los
    # campos de ESTILO y MATERIAL (crol/espalda/estilos, pull-buoy, palas):
    # Garmin los tiene, pero sus identificadores devuelven 400 ante cualquier
    # valor fuera de su enum, exactamente como pasa con `exerciseName` en
    # fuerza. Así que el estilo y el material viajan en el `description` del
    # paso, que Garmin acepta siempre y el atleta lee en el reloj. Añadir los
    # campos nativos es un paso posterior, y sólo tras leer un entreno de
    # natación creado por Garmin del que copiar los identificadores reales.
    force_no_target = day.sport in ("swim", "strength")

    # v7. La sesion de fuerza llega al reloj CON SUS EJERCICIOS. Antes `steps`
    # llevaba un unico bloque en blanco de 45 minutos porque los
    # identificadores de ejercicio de Garmin eran una incognita; leidos de
    # entrenos reales, la traduccion desde `strength_block` es mecanica. Si no
    # hay desglose -- planes anteriores a v7, o una sesion que el coach dejo
    # libre -- se usa `steps` tal cual, como siempre.
    pasos = _strength_steps_from_block(day) if day.sport == "strength" else None

    sufijo = slot_suffix(day.session_slot)
    return {
        "workoutName": f"{day.focus or day.sport.title()} - {day.date}{sufijo}"[:100],
        "description": day.purpose or "",
        "sportType": sport_type,
        "workoutSegments": [
            {
                "segmentOrder": 1,
                "sportType": sport_type,
                "workoutSteps": _build_workout_steps(
                    pasos or day.steps, zones, force_no_target=force_no_target
                ),
            }
        ],
    }



# El orden en que se hacen los tramos de un triatlón, para ordenar un brick
# cuyos pasos vinieran mezclados. No es una regla de entrenamiento -- un brick
# de carrera y bici en ese orden es perfectamente válido -- sino el desempate
# cuando el modelo no dejó claro el orden. Se usa SÓLO si los pasos no vienen
# ya agrupados, porque el orden que escribió el entrenador manda.
_LEG_ORDER = {"swim": 0, "bike": 1, "run": 2, "walk": 3, "strength": 4}


def _brick_legs(steps: list[WorkoutStep]) -> list[tuple[str, list[WorkoutStep]]]:
    """Agrupa los pasos en tramos, respetando el orden en que vienen.

    Pasos SEGUIDOS del mismo deporte son un tramo. Que sea por adyacencia y no
    por deporte es deliberado: un triatlón tiene natación, bici, carrera... y
    puede volver a tener natación al final (el multideporte de referencia,
    1679387475, hace exactamente eso). Agrupar por deporte fundiría esos dos
    tramos de natación en uno y cambiaría la sesión.

    Un paso sin `leg_sport` hereda el del anterior: el modelo suele etiquetar
    el primero de cada tramo y no repetirlo en la recuperación.
    """
    tramos: list[tuple[str, list[WorkoutStep]]] = []
    actual: str | None = None
    for paso in steps:
        deporte = (paso.leg_sport or "").strip().lower() or actual
        if deporte not in SPORT_TYPES:
            deporte = actual
        if deporte is None:
            # Antes del primer paso etiquetado no hay tramo al que pertenecer.
            # Se deja para el final, cuando ya se sabe cuál abre la sesión.
            tramos.append(("", [paso]))
            continue
        if not tramos or tramos[-1][0] != deporte:
            tramos.append((deporte, [paso]))
        else:
            tramos[-1][1].append(paso)
        actual = deporte

    # Los pasos huérfanos del principio se pegan al primer tramo con deporte.
    primero = next((d for d, _ in tramos if d), None)
    if primero is None:
        return []
    salida: list[tuple[str, list[WorkoutStep]]] = []
    for deporte, pasos in tramos:
        destino = deporte or primero
        if salida and salida[-1][0] == destino:
            salida[-1][1].extend(pasos)
        else:
            salida.append((destino, list(pasos)))
    return salida


def build_multisport_workout(day: WorkoutDay, zones: AthleteZoneConfig) -> dict:
    """Un brick, como el multideporte que Garmin sí sabe guardar.

    v7. Hasta ahora un brick no llegaba al reloj: se entregaba escrito, porque
    la forma multideporte era una incógnita y este repo no adivina los
    formatos de Garmin. Dejó de serlo cuando el atleta creó uno en su cuenta
    (`1679387475`) y se leyó de vuelta.

    Lo que resultó ser: **tramos ordenados**, cada uno con su `sportType` y sus
    pasos, dentro de un `sportType` de nivel superior `multi_sport`. La app del
    reloj los enseña como "tramos de la sesión de entrenamiento". **No hay
    pasos de transición** -- ni en la API ni en la app -- porque el tramo
    siguiente empieza donde acaba el anterior, que es justo lo que un brick es
    y por lo que separarlo en dos sesiones lo destruye.

    El detalle que hay que respetar: `stepOrder` es GLOBAL y corre a través de
    los tramos (1-21 natación, 22-25 bici, 26 carrera en el de referencia), no
    reinicia en cada uno.
    """
    tramos = _brick_legs(day.steps)
    if len(tramos) < 2:
        # Un "brick" de un solo deporte no es un brick. Se rechaza aquí en vez
        # de subir una sesión multideporte con un tramo, que en el reloj se ve
        # como un entreno normal con un envoltorio raro.
        raise UnsupportedSportError(
            "Un brick necesita al menos dos tramos de deportes distintos; "
            f"estos pasos sólo describen {len(tramos)}"
        )

    segmentos = []
    siguiente = 1
    for orden, (deporte, pasos) in enumerate(tramos, start=1):
        construidos = _build_workout_steps(
            pasos, zones,
            force_no_target=deporte in ("swim", "strength"),
            first_order=siguiente,
        )
        siguiente += _count_orders(construidos)
        segmentos.append({
            "segmentOrder": orden,
            "sportType": dict(SPORT_TYPES[deporte]),
            "workoutSteps": construidos,
        })

    # Las transiciones van DESACTIVADAS, que es como venia el multideporte de
    # referencia (`isSessionTransitionEnabled: False`). Es lo correcto para un
    # brick: lo que se entrena es no parar entre tramos, y una pantalla de
    # transicion pidiendo confirmacion es justo la pausa que lo destruye.

    sufijo = slot_suffix(day.session_slot)
    return {
        "workoutName": f"{day.focus or 'Brick'} - {day.date}{sufijo}"[:100],
        "description": day.purpose or "",
        "sportType": dict(MULTISPORT_TYPE),
        "isSessionTransitionEnabled": False,
        "workoutSegments": segmentos,
    }


def _count_orders(steps: list[dict]) -> int:
    """Cuántos `stepOrder` consume una lista ya construida, contando los que
    van dentro de los bloques de repetición.

    Hace falta porque el orden es GLOBAL entre tramos: si el tramo de bici
    empezara en 1 otra vez, Garmin guardaría los pasos pisados.
    """
    total = 0
    for paso in steps:
        total += 1
        hijos = paso.get("workoutSteps")
        if hijos:
            total += _count_orders(hijos)
    return total

def build_garmin_workout(day: WorkoutDay, zones: AthleteZoneConfig) -> dict:
    if day.sport == "brick":
        return build_multisport_workout(day, zones)
    return build_single_sport_workout(day, zones)
