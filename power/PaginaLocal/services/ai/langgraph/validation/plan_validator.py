import json
import logging
import re
from datetime import date, timedelta
from typing import Any

from services.ai.langgraph.schemas.plan_outputs import (
    PACE_TARGETED_CLASSES,
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)

logger = logging.getLogger(__name__)

# Violation types that a single deterministic edit (force the day to rest)
# can always resolve safely, without inventing a compliant workout.
# Cuantas sesiones puede llevar un dia. Cuatro no es un objetivo: es el techo
# que hace falta para que un triatleta pueda declarar nadar y correr en el
# bloque de la manana y rodar por la tarde sin que el validador lo tumbe.
MAX_SESSIONS_PER_DAY = 4

_TIER1_FIXABLE_TYPES = {"day_not_available", "sport_not_allowed"}


def _walk_steps(steps) -> list:
    """Every step in a day, including those nested inside repeat blocks.

    Repeat blocks (WorkoutStep.substeps) mean a day's steps are a tree, not a
    flat list; anything that inspects or rewrites steps has to descend into it
    or it will silently ignore the entire main set of every interval session.
    """
    out = []
    for step in steps or []:
        out.append(step)
        if step.substeps:
            out.extend(_walk_steps(step.substeps))
    return out


def _steps_duration_seconds(steps) -> float:
    """Total timed seconds, expanding repeat blocks by their iteration count."""
    total = 0.0
    for step in steps or []:
        rounds = max(step.repeat_count, 1)
        if step.substeps:
            total += _steps_duration_seconds(step.substeps) * rounds
        elif step.duration_type == "time":
            total += (step.duration_value or 0) * rounds
    return total


# Cuanto puede pasarse la suma de los pasos cronometrados del total declarado
# antes de considerarlo un error de unidades. Ver la regla `duration_mismatch`.
_TOLERANCIA_DURACION = 1.5
_HOLGURA_DURACION_MIN = 10.0


def _paso_mas_largo(steps) -> str:
    """El paso por tiempo mas largo, para poder senalarlo por su nombre.

    Un aviso que dice "algo no cuadra" obliga a releer la sesion entera; uno
    que dice cual es el paso y cuanto dura se arregla en una linea.
    """
    peor, peor_seg = None, 0.0
    for step in _walk_steps(steps):
        if step.substeps or step.duration_type != "time":
            continue
        seg = (step.duration_value or 0) * max(step.repeat_count, 1)
        if seg > peor_seg:
            peor, peor_seg = step, seg
    if peor is None:
        return "ninguno (no hay pasos por tiempo)"
    return f"'{peor.description}' con duration_value={peor.duration_value:.0f}s ({peor_seg / 60:.0f} min)"


def _day_duration_minutes(day) -> float:
    if day.total_duration_minutes is not None:
        return day.total_duration_minutes
    return _steps_duration_seconds(day.steps) / 60


# --- Coaching rules -------------------------------------------------------
# These check that a session will actually deliver the stimulus it claims to,
# which the availability rules above say nothing about. A plan can respect
# every hour budget and still be useless: intervals with the recovery moved to
# the end, or a threshold session prescribed by heart rate so the athlete
# over-exerts on the first repetitions chasing a bpm number their heart hasn't
# reached yet.

_QUALITY_FOCUS_KEYWORDS = (
    "tempo", "umbral", "threshold", "vo2", "interval", "intervalo", "serie",
    "fartlek", "cuesta", "hill", "ritmo", "velocidad", "speed", "pista", "track",
)
_WORK_STEP_TYPES = frozenset({"interval", "main"})
# Steps whose whole point is that the athlete stays easy. Prescribing these by
# pace fights the purpose: the athlete should be recovering, not hitting a number.
_EASY_STEP_TYPES = frozenset({"warmup", "cooldown", "recovery"})


def _is_work_step(step) -> bool:
    return (
        step.step_type in _WORK_STEP_TYPES
        and not step.substeps
        and step.duration_type in ("time", "distance", "lap_button")
    )


def _is_alternating_set(step) -> bool:
    """Whether a repeat block alternates two DIFFERENT efforts rather than
    repeating one.

    An over-under (2' at threshold / 2' at marathon pace), or a structured
    fartlek, is not the "no recovery between reps" defect: the athlete is
    meant to keep working through the easier half, and inserting a rest step
    would destroy the session's entire purpose. Distinguished by the targets
    actually differing between the block's work steps -- repeating the *same*
    effort back to back with nothing in between remains the real bug this
    rule was written for.
    """
    work = [s for s in (step.substeps or []) if _is_work_step(s)]
    if len(work) < 2:
        return False
    signatures = {(s.target_type, s.target_low, s.target_high, s.target_zone) for s in work}
    return len(signatures) > 1


_CINTA = ("cinta", "treadmill")


def _en_cinta(day) -> bool:
    """Si la sesión se corre en cinta (Beta 3.0, F10; Jessie, A11): ahí el ritmo lo marca la máquina y
    el reloj no lo mide bien bajo techo (la del 18-mar marcó 32:40/km), así que una calidad en cinta va
    por FC con la velocidad en el texto y NO se le exige objetivo de ritmo. Una cuesta «en cinta al 8%»
    tampoco lleva ritmo, pero ésa ya no es de las clases que lo piden."""
    texto = " ".join([str(day.focus or ""), str(day.purpose or "")]
                     + [str(s.description or "") for s in _walk_steps(day.steps or [])]).lower()
    return any(p in texto for p in _CINTA)


def _needs_pace_modality(day) -> bool:
    """Whether THIS session is one where a pace/power target is genuinely the
    right modality and its absence is a defect.

    Prefers what the coach DECLARED (WorkoutDay.intensity_class, carried
    through from the decision layer) over guessing from prose. The keyword
    heuristic below is only a fallback for plans generated before the class
    existed -- and it is deliberately being retired, because it swept in
    sessions that must NOT carry a pace band:

    - strides / hill sprints / drills, which are run by feel (a pace band for
      a 15-second stride is meaningless);
    - run/walk blocks, whose entire point is staying easy while structurally
      being intervals -- forcing a quality pace there turns base building into
      a hard session;
    - any easy run whose `purpose` merely happened to contain a word like
      "ritmo", which is common in ordinary Spanish coaching prose.

    Each of those is a session a good coach prescribes routinely, so a rule
    that mangles them is a rule that narrows the coach.
    """
    declared = getattr(day, "intensity_class", None)
    if declared:
        return declared in PACE_TARGETED_CLASSES

    focus_text = f"{day.focus or ''} {day.purpose or ''}".lower()
    if any(keyword in focus_text for keyword in _QUALITY_FOCUS_KEYWORDS):
        return True
    return any(
        step.substeps and any(_is_work_step(sub) for sub in step.substeps)
        for step in _walk_steps(day.steps)
    )


def _is_hard_session(day) -> bool:
    """Whether this session carries real intensity, for the taper-window rule.

    Deliberately NOT the same question as "does it need a pace target", even
    though today both resolve to the same classes. Notably `neuromuscular`
    (strides, short accelerations) is NOT hard in this sense: keeping strides
    through a taper is standard practice to stay sharp on tired legs, and a
    rule that stripped them out of race week would be actively bad coaching.

    Las series en cuesta (`hill_repeats`) SÍ son duras aunque no lleven ritmo: se
    prescriben por esfuerzo. Fuera de esta lista, 3x4' de subida a RPE 6 la víspera de un
    trail (Hugo, 2026-10-09) no las veía la regla de la víspera; el paso de fuerza ya las
    trataba como sesión clave (`paso_de_fuerza.SESIONES_CLAVE`).
    """
    declared = getattr(day, "intensity_class", None)
    if declared:
        return declared in PACE_TARGETED_CLASSES or declared == "hill_repeats"
    return _needs_pace_modality(day)


def _pace_extremes(prescription: dict[str, Any]) -> tuple[float, float] | None:
    """The fastest and slowest pace this athlete should ever be prescribed."""
    bands = prescription.get("pace_bands") or {}
    if not bands:
        return None
    fastest = min(band[0] for band in bands.values())
    slowest = max(band[1] for band in bands.values())
    return fastest, slowest


def _coaching_violations(day, prescription: dict[str, Any]) -> list[dict[str, Any]]:
    if day.is_rest or not day.steps:
        return []

    # A session Garmin cannot hold (plyometrics, rehab, prehab, mobility, a
    # brick) reaches the athlete as written instructions, not as steps on a
    # watch (v5 Track 5). Its `steps` are a structural approximation kept only
    # so the app can show a duration -- judging that approximation against
    # pace-band and recovery-step rules would flag defects in something nobody
    # will ever execute, and the repair prompt would then rewrite a session the
    # coach wrote deliberately. The whole point of the track is that Garmin's
    # expressiveness stops deciding what may be prescribed.
    if not getattr(day, "garmin_exportable", True):
        return []

    violations: list[dict[str, Any]] = []
    where = {"date": day.date, "session_slot": day.session_slot}
    all_steps = _walk_steps(day.steps)

    # Los minutos que dicen los pasos contra los minutos que dice el dia.
    #
    # No es una regla de entrenamiento: es un control de UNIDADES, y existe
    # porque el fallo llego al reloj de un atleta. La sesion de bici del
    # 2026-09-01 declaraba 45 minutos y su bloque decia, literalmente:
    #
    #     description "1' a 95-105 rpm Z2"   duration_value 1500
    #
    # Mil quinientos segundos son veinticinco minutos. Cinco repeticiones de
    # eso mas el calentamiento y el enfriamiento suman 160 minutos.
    #
    # Lo grave es que nadie podia verlo. La app imprime `description`, asi que
    # en el movil la sesion seguia siendo de 1'; Garmin ejecuta
    # `duration_value`, asi que en el reloj era de 25'. El atleta lo descubrio
    # de pie sobre la bici. Y las reglas de horas de este mismo fichero
    # tampoco chistaron, porque `_day_duration_minutes` prefiere el total
    # declarado cuando existe: se juzgaba el presupuesto contra los 45 minutos
    # que el modelo dijo, nunca contra los 160 que escribio.
    #
    # ## Por que solo se mira que sobre, y no que falte
    #
    # Solo se suman los pasos por TIEMPO. Un paso por distancia o por
    # repeticiones tambien dura, pero cuanto depende del atleta, asi que la
    # suma de los pasos cronometrados es una COTA INFERIOR de lo que la sesion
    # va a durar. Eso hace sano el sentido "sobra" -- si ya el tiempo medido
    # se pasa del total, es imposible que el total sea correcto -- y hace
    # insano el contrario: una sesion de natacion de 35' cuyos pasos por
    # tiempo suman 3.5' de descansos en la pared es perfectamente normal, y
    # avisar ahi seria ruido en cada sesion de piscina.
    #
    # El margen es ancho a proposito. Un desajuste de verdad es un error de
    # unidad -- 25x, 60x -- no un redondeo; pedirle al modelo que cuadre al
    # minuto costaria reparaciones sin arreglar nada.
    declarado = day.total_duration_minutes
    if declarado and declarado > 0:
        cronometrado = _steps_duration_seconds(day.steps) / 60
        if cronometrado > declarado * _TOLERANCIA_DURACION + _HOLGURA_DURACION_MIN:
            violations.append({
                **where, "type": "duration_mismatch",
                "detail": (
                    f"Los pasos de '{day.focus}' suman {cronometrado:.0f} minutos cronometrados "
                    f"pero el dia declara total_duration_minutes={declarado:.0f}. Casi siempre es "
                    "un `duration_value` en las unidades equivocadas: va en SEGUNDOS "
                    f"(1 minuto = 60), y el paso mas largo de esta sesion es "
                    f"{_paso_mas_largo(day.steps)}. Corrige el duration_value que no cuadre con "
                    "su propia `description`, o corrige el total; los dos tienen que decir lo mismo."
                ),
            })

    # A repeat block whose rounds have no recovery in them means the athlete
    # starts the next repetition the instant the previous one ends.
    for step in all_steps:
        if not step.substeps:
            continue
        has_work = any(_is_work_step(sub) for sub in step.substeps)
        has_recovery = any(sub.step_type in ("recovery", "rest") for sub in step.substeps)
        if has_work and not has_recovery and not _is_alternating_set(step):
            violations.append({
                **where, "type": "intervals_without_recovery",
                "detail": (
                    f"El bloque '{step.description}' repite {step.repeat_count} veces un esfuerzo "
                    "sin ningun paso de recuperacion dentro. Anade un paso de recuperacion "
                    "(step_type='recovery') dentro de los substeps, despues del esfuerzo."
                ),
            })

    work_steps = [s for s in all_steps if _is_work_step(s)]

    # Quality work must be prescribed by the modality the athlete can actually
    # execute: pace on foot, power on the bike. Only enforced when the data to
    # do so exists -- otherwise heart rate is the honest fallback, not an error.
    if (day.sport == "run" and _needs_pace_modality(day) and work_steps and prescription.get("pace_bands")
            and not _en_cinta(day)):
        if not any(s.target_type == TargetType.PACE for s in work_steps):
            violations.append({
                **where, "type": "quality_without_pace",
                "detail": (
                    f"'{day.focus}' es una sesion de calidad de carrera pero ningun bloque de trabajo "
                    "lleva objetivo de RITMO. En las primeras repeticiones la FC aun no ha subido a la "
                    "zona, asi que el atleta se sobre-esfuerza persiguiendola y arruina el estimulo. "
                    "Pon target_type='pace' con target_low/target_high en segundos por km, tomados de "
                    "la banda correspondiente de la Ficha de Prescripcion."
                ),
            })

    if day.sport == "bike" and _needs_pace_modality(day) and work_steps and prescription.get("power_bands"):
        if not any(s.target_type == TargetType.POWER for s in work_steps):
            violations.append({
                **where, "type": "quality_without_power",
                "detail": (
                    f"'{day.focus}' es una sesion de calidad de bici pero ningun bloque de trabajo lleva "
                    "objetivo de POTENCIA. Pon target_type='power' con los watts de la Ficha de Prescripcion."
                ),
            })

    for step in all_steps:
        if step.step_type in _EASY_STEP_TYPES and step.target_type == TargetType.PACE:
            violations.append({
                **where, "type": "easy_step_with_pace",
                "detail": (
                    f"El paso '{step.description}' ({step.step_type}) lleva objetivo de ritmo. "
                    "Calentamiento, enfriamiento y recuperacion van por ZONA DE FC "
                    "(target_type='hr_zone' + target_zone), que es la medida honesta de la carga "
                    "interna cuando el objetivo es ir suave."
                ),
            })

    # A pace outside everything this athlete could plausibly run is a data or
    # unit error reaching the watch as a workout nobody can execute.
    # Gated to running on purpose: pace_bands are derived from the athlete's
    # running threshold, so checking a swim step (seconds per 100m) or a bike
    # step against them compares two different units and flags a correct
    # session as impossible.
    extremes = _pace_extremes(prescription) if day.sport == "run" else None
    if extremes:
        fastest, slowest = extremes
        for step in all_steps:
            if step.target_type != TargetType.PACE:
                continue
            for value in (step.target_low, step.target_high):
                if value is None:
                    continue
                if value < fastest * 0.95 or value > slowest * 1.05:
                    violations.append({
                        **where, "type": "pace_out_of_range",
                        "detail": (
                            f"El paso '{step.description}' pide un ritmo de {value:.0f} s/km, fuera del "
                            f"rango que este atleta puede sostener ({fastest:.0f}-{slowest:.0f} s/km "
                            "segun la Ficha de Prescripcion). Ajustalo a la banda de su clase de intensidad."
                        ),
                    })
                    break

    return violations


# Orden cronológico de las claves de sesión. Es el respaldo para deducir a qué
# bloque pertenece una sesión cuando el plan no trae `block_index` -- planes
# guardados antes de que el campo existiera, y modelos que se lo dejan vacío.
# Importado, no redefinido: tres copias del mismo orden son tres
# oportunidades de que una se quede atras. Ver SLOT_ORDER en el esquema.
from services.ai.langgraph.schemas.plan_outputs import SLOT_ORDER as _SLOT_ORDER


def _block_of(day, blocks: list) -> int | None:
    """El bloque de disponibilidad al que pertenece esta sesión.

    Prefiere lo que el planificador DECLARÓ (`block_index`) sobre deducirlo del
    orden, por la misma razón que `_needs_pace_modality` prefiere
    `intensity_class` a adivinar de la prosa: la deducción sólo existe para lo
    que se guardó antes de que el campo existiera.
    """
    declarado = getattr(day, "block_index", None)
    if declarado is not None and 0 <= declarado < len(blocks):
        return declarado
    if not blocks:
        return None
    slot = getattr(day, "session_slot", "unica") or "unica"
    if slot == "unica":
        return 0
    try:
        idx = _SLOT_ORDER.index(slot) - 1
    except ValueError:
        return None
    return idx if idx < len(blocks) else None


def _sports_used(day) -> set[str]:
    """Qué deportes ocupa REALMENTE esta sesión, para juzgarla contra la franja.

    Casi siempre es `{day.sport}`. La excepción es el brick: su deporte es el
    compuesto, y lo que el atleta hace en esa ventana son sus TRAMOS. Comparar
    la palabra "brick" contra los deportes permitidos de la franja la rechaza
    siempre, por muy claro que el atleta haya marcado bici y carrera.

    Pasó, y costó la sesión entera: un domingo de 06:00 a 10:00 con `run,
    trail, bike` permitidos rechazó un brick de bici y carrera con
    "'brick' no esta en los deportes permitidos", y el día se convirtió en
    descanso -- llevándose por delante también las 1.8h que la semana tenía de
    más y dejándola en 4.25h de las 5.5h prescritas.
    """
    if day.sport != "brick":
        return {day.sport}
    tramos = {
        (getattr(paso, "leg_sport", None) or "").strip().lower()
        for paso in _walk_steps(day.steps)
    } - {""}
    # Un brick sin tramos declarados no se puede juzgar por sus partes; se
    # deja pasar la comprobación de deporte y es `is_garmin_exportable` quien
    # decide que va escrito en vez de al reloj.
    return tramos or set()


def _block_loads(days, blocks: list) -> dict[int, float]:
    """Minutos totales prescritos dentro de cada bloque de un día.

    UN BLOQUE PUEDE LLEVAR VARIAS SESIONES. Antes no podía: el presupuesto se
    comprobaba sesión a sesión contra el bloque que le tocaba por índice, así
    que dos sesiones en el mismo bloque habrían pasado las dos aunque juntas
    se salieran de la ventana. El atleta que corre Y nada de 05:30 a 07:00
    tenía que declarar dos bloques falsos para que el plan se lo permitiera, y
    cuando no lo hacía el plan le daba una sola sesión sin decir por qué.
    """
    carga: dict[int, float] = {}
    for day in days:
        if day.is_rest:
            continue
        idx = _block_of(day, blocks)
        if idx is None:
            continue
        carga[idx] = carga.get(idx, 0.0) + _day_duration_minutes(day)
    return carga


def _resolve_slot_budget(constraint: dict[str, Any], slot: str, occurrence_count: int) -> tuple[float | None, list | None]:
    """Compatibilidad: el presupuesto del bloque que le toca a este slot.

    Se conserva porque hay pruebas y llamadas que la usan con la firma vieja.
    La comprobación real de horas la hace ahora `_block_loads`, que suma todas
    las sesiones de un bloque en vez de mirarlas de una en una.
    """
    blocks = constraint.get("blocks") or []
    if occurrence_count > 1 and blocks:
        try:
            idx = _SLOT_ORDER.index(slot) - 1
        except ValueError:
            idx = None
        if idx is not None and 0 <= idx < len(blocks):
            block = blocks[idx]
            return block.get("hours"), block.get("sports")
    return constraint.get("hours"), constraint.get("sports")


# Plan Maestro v4 Track C: race-day/taper keywords used to check whether a
# session already acknowledges it's race-related, before falling back to a
# forced deterministic template. Kept separate from _QUALITY_FOCUS_KEYWORDS
# above -- a race day mentioning "carrera" isn't automatically a quality
# session in the coaching-rules sense, and vice versa.
_RACE_DAY_KEYWORDS = ("carrera", "competencia", "competición", "competicion", "race", "meta", "taper")


def _taper_window_days(priority) -> int:
    """How many days before a competition count as its taper window, by
    priority. A B-race (like the athlete's real 5K) still gets a short
    taper -- that's exactly the case the original bug missed. A C-race/
    unspecified priority gets none: low-stakes races don't warrant reshaping
    the week around them, only the race day itself must be honest about it."""
    priority = (priority or "").strip().lower()
    if priority in ("a", "alta", "high"):
        return 7
    if priority in ("b", "media", "medium"):
        return 3
    return 0


def _race_day_violations(
    all_days, competitions: list[dict[str, Any]], expected_dates: set[str],
) -> list[dict[str, Any]]:
    """Deterministic check: a day that lands ON a competition's date, or
    inside its taper window, must actually reflect that -- not read as a
    generic training day, and never be entirely absent (see
    _apply_race_day_fallback, which can synthesize one from scratch). These
    violations go through tier-2 LLM repair like everything else before any
    deterministic fallback -- a real taper depends on how much volume the
    week already carried, which the LLM judges better than a fixed shape.

    `expected_dates` gates the "date has NO WorkoutDay at all" branch to
    competitions whose date actually falls within THIS payload's own window
    (validate_weekly_plan's week_dates). Without this, Plan Maestro v4 Track
    B's move to one-real-week-at-a-time payloads turned this into "does this
    competition have a WorkoutDay in these 7 days" -- true for essentially
    every competition on the athlete's calendar when the payload is only a
    week wide. A verified real run generating the week of 2026-08-24 got
    three synthesized "race day" entries stapled onto it for races in
    September 2026 AND April/August 2027, burning two tier-2 repair rounds
    (~$0.50 of a $0.52 week) on violations that should never have existed.
    The other two checks below (a day that exists but doesn't mention the
    race; a full-intensity session inside the taper window) were never
    affected by this -- both only ever look at dates already present in
    `all_days`, which can't extend past the payload's own window."""
    violations: list[dict[str, Any]] = []
    days_by_date: dict[str, list] = {}
    for day in all_days:
        days_by_date.setdefault(day.date, []).append(day)

    for comp in competitions:
        comp_date = comp.get("date")
        if not comp_date:
            continue
        comp_name = (comp.get("name") or "").lower()

        if comp_date in expected_dates:
            race_day_entries = days_by_date.get(comp_date, [])
            if not race_day_entries:
                violations.append({
                    "date": comp_date, "session_slot": "unica", "type": "race_day_conflict",
                    "detail": (
                        f"El {comp_date} es el dia de tu competencia '{comp.get('name', '')}' pero el plan no "
                        "tiene ninguna sesion para esa fecha — necesita un dia de carrera/taper real."
                    ),
                })
            for day in race_day_entries:
                if day.is_rest:
                    violations.append({
                        "date": day.date, "session_slot": day.session_slot, "type": "race_day_conflict",
                        "detail": (
                            f"{day.day_name_es} es el dia de tu competencia '{comp.get('name', '')}' pero quedo "
                            "marcado como descanso — necesita ser un dia de carrera/taper real, nunca un dia vacio."
                        ),
                    })
                    continue
                focus_text = f"{day.focus or ''} {day.purpose or ''}".lower()
                mentions_race = any(kw in focus_text for kw in _RACE_DAY_KEYWORDS) or (
                    bool(comp_name) and comp_name in focus_text
                )
                if not mentions_race:
                    violations.append({
                        "date": day.date, "session_slot": day.session_slot, "type": "race_day_conflict",
                        "detail": (
                            f"{day.day_name_es} es el dia de tu competencia '{comp.get('name', '')}' pero la "
                            f"sesion no lo refleja (foco: '{day.focus}') — necesita ser un dia de carrera/taper, "
                            "no un entrenamiento generico."
                        ),
                    })

        if not _taper_window_days(comp.get("priority")):
            continue
        try:
            race_date = date.fromisoformat(comp_date)
        except ValueError:
            continue
        # LA VISPERA, y en el deporte de la carrera. Nada mas.
        #
        # La regla vieja prohibia cualquier sesion de calidad 3 dias antes de
        # una B y 7 antes de una A, en cualquier deporte. Eso no es un taper:
        # la evidencia (Mujika y Padilla, Bosquet 2007) dice que se mantiene la
        # intensidad y se recorta el VOLUMEN, y cuanto se recorta depende de lo
        # que la semana cargo -- criterio del entrenador, que recibe la
        # carrera y sus dias de afinamiento en el prompt.
        # El caso real, 2026-09-11: 6x100 m a CSS en la piscina el viernes 18,
        # dos dias antes del 5K de CARRERA del atleta 2. No carga las piernas y
        # el propio entrenador lo habia escrito asi ("sin cargar piernas"). La
        # regla lo marco, y la reparacion costo una llamada de 58k caracteres.
        # Lo que queda es lo indefendible: calidad en el deporte de la carrera
        # el dia antes. Las rectas y los activadores (neuromuscular) no cuentan.
        deportes = _deportes_de_la_carrera(comp)
        vispera = (race_date - timedelta(days=1)).isoformat()
        if vispera not in expected_dates:
            continue
        for day in days_by_date.get(vispera, []):
            if day.is_rest or not _is_hard_session(day):
                continue
            if deportes and (day.sport or "") not in deportes:
                continue
            violations.append({
                "date": day.date, "session_slot": day.session_slot, "type": "race_taper_conflict",
                "detail": (
                    f"{day.day_name_es} es la vispera de tu competencia '{comp.get('name', '')}' "
                    f"({comp_date}) y lleva una sesion de calidad del mismo deporte — la vispera se "
                    "llega fresco: soltar, rectas o activacion, no una sesion clave."
                ),
            })
    return violations


def _deportes_de_la_carrera(comp: dict[str, Any]) -> set[str]:
    """Los deportes que se compiten, sacados de la distancia del catalogo.

    Vacio si no se sabe, y entonces la regla de la vispera mira todos: ante
    la duda, se aplica igual que antes a la vispera y a nada mas."""
    from services.ai.race_distances import legs_of

    deportes = {sport for sport, _ in legs_of(comp.get("distance"))}
    if not deportes and comp.get("race_type") in ("run", "bike", "swim"):
        deportes = {comp["race_type"]}
    return deportes


# El grupo del catálogo (`race_distances`) cuando la distancia no trae tramos. "Otros" no dice nada.
_DEPORTES_DEL_GRUPO = {"carrera": {"run"}, "trail": {"run"}, "ciclismo": {"bike"}, "natación": {"swim"},
                       "aguas abiertas": {"swim"}, "triatlón": {"swim", "bike", "run"}}


def deportes_de_la_competicion(comp: dict[str, Any]) -> set[str]:
    """Los deportes de una carrera, de su distancia o, si no, de su grupo ("Carrera · 5K"). Vacío si no se sabe."""
    deportes = _deportes_de_la_carrera(comp)
    if deportes:
        return deportes
    grupo = str(comp.get("race_type") or "").split("·")[0].strip().lower()
    return set(_DEPORTES_DEL_GRUPO.get(grupo, set()))


def dias_de_carrera(competitions: list | None, fechas) -> dict[str, list[str]]:
    """Las fechas de `fechas` en las que el atleta compite, con los deportes de esa carrera.

    Hugo, 2026-10-09: su trail del domingo 11/10 y su tabla con el domingo de descanso (no
    entrena los domingos). El prompt decía a la vez "un día marcado como no disponible no se
    entrena" (bajo NEVER VIOLATE) y "el día de una carrera es `race_day`"; la comprobación de
    la decisión le devolvió la carrera ("ese día NO puede, muévela"), la semana puso una
    simulación de trail el sábado, la víspera, y la red del día de carrera escribió la
    competición el domingo para que la de disponibilidad la quitara: «Aquí iba «Dia de
    competencia», pero ese día no está disponible». Una carrera que el atleta apuntó en su
    calendario ES su disponibilidad de ese día: su tabla es su rutina, no su calendario de
    carreras (decisión del responsable).

    Lista vacía = no se sabe el deporte de la carrera: el día se abre sin limitar el deporte.
    """
    fechas = {str(f)[:10] for f in (fechas or [])}
    salida: dict[str, set[str]] = {}
    for comp in competitions or []:
        if not isinstance(comp, dict):
            continue
        fecha = str(comp.get("date") or "")[:10]
        if fecha in fechas:
            salida.setdefault(fecha, set()).update(deportes_de_la_competicion(comp))
    return {f: sorted(d) for f, d in sorted(salida.items())}


def _deporte_del_dia_de_carrera(comp: dict[str, Any], actual: str | None) -> str:
    """El deporte con el que el respaldo escribe el día de una carrera.

    Era "run" siempre: una travesía de natación o una contrarreloj en bici
    llegaban al reloj como una carrera a pie. Con un solo deporte, ése; en
    multideporte se conserva el del día si es de la carrera, y si no el del
    primer segmento. Sin saberlo, carrera a pie, como antes.
    """
    from services.ai.race_distances import legs_of

    deportes = _deportes_de_la_carrera(comp)
    if len(deportes) == 1:
        return next(iter(deportes))
    if actual in deportes:
        return actual
    tramos = legs_of(comp.get("distance"))
    return tramos[0][0] if tramos else "run"


def restriccion_del_dia(availability: dict[str, Any], day_name_es: str | None, fecha: str) -> dict[str, Any]:
    """Lo que se puede entrenar ESA fecha: su tabla, con lo que el check-in cambió para esta semana.

    Una sola lectura para el validador del plan y para la comprobación de la
    decisión (`intenciones_fuera_de_la_tabla`). Un día que el atleta marcó como
    que no puede (Database/022) no está disponible aunque su tabla lo tenga; uno
    que abrió (Database/024, en claves) está disponible con esos deportes; si está
    en las dos listas, gana el no. Un día abierto sin deportes no abre nada: una
    lista vacía de deportes permitidos se lee más abajo como "sin límite". Y el día de
    una carrera está disponible con los deportes de esa carrera (`dias_de_carrera`),
    salvo que ESTA semana haya dicho que no puede.
    """
    constraint = (availability.get("days") or {}).get(
        day_name_es, {"available": True, "hours": None, "sports": None})
    if fecha in set(availability.get("unavailable_dates") or []):
        return {**constraint, "available": False}
    # El día de una carrera está disponible para ESA carrera aunque su tabla lo tenga de
    # descanso (`dias_de_carrera`). Un espejo sin `race_dates` lo saca de sus competiciones.
    carreras = availability.get("race_dates")
    if carreras is None:
        carreras = dias_de_carrera(availability.get("competitions"), [fecha])
    if fecha in carreras:
        de_la_carrera = set(carreras[fecha] or [])
        # Ni las horas de su tabla: un maratón no cabe en el hueco de 1 h de un día de rutina.
        if not de_la_carrera:   # no se sabe su deporte: el día no limita ninguno
            return {**constraint, "available": True, "hours": None, "blocks": [], "sports": None}
        if not constraint.get("available", True):
            constraint = {"available": True, "hours": None, "sports": sorted(de_la_carrera), "blocks": []}
        else:
            deportes = constraint.get("sports")
            constraint = {**constraint, "hours": None, "blocks": [],
                          "sports": None if deportes is None else sorted(set(deportes) | de_la_carrera)}
    abiertos = set((availability.get("open_dates") or {}).get(fecha) or [])
    if abiertos:
        if not constraint.get("available", True):
            return {"available": True, "hours": None, "sports": sorted(abiertos), "blocks": []}
        if constraint.get("sports") is not None:
            return {**constraint, "blocks": [], "sports": sorted(set(constraint["sports"]) | abiertos)}
    return constraint


def intenciones_fuera_de_la_tabla(
    dias: list, availability: dict[str, Any], week_dates: list[dict[str, str]],
) -> list[dict[str, Any]]:
    """Las sesiones de la DECISIÓN que caen donde el atleta no puede, antes de codificarlas.

    José, 2026-09-15 (atleta 2, semana del 5K Monterrico): la decisión puso sus
    6x400 m el martes 15, y su tabla del martes sólo tiene Fuerza (fijo) y Bici
    (posible). Esto sólo se miraba DESPUÉS de codificar, donde la única salida
    determinista es convertir el día en descanso: la semana perdió su única sesión
    de calidad, con el título "5K pace" y el racional hablando todavía de ella.
    Quien tiene el criterio para moverla es quien la decidió.

    El nombre del día sale de `week_dates` (las fechas reales), no del texto del
    modelo, que puede equivocarse de día de la semana.
    """
    nombre_de = {d.get("date"): d.get("day_name_es") for d in week_dates}
    fuera: list[dict[str, Any]] = []
    for dia in dias:
        d = dia if isinstance(dia, dict) else dia.model_dump()
        fecha = d.get("date")
        if d.get("is_rest") or d.get("sport") in (None, "rest") or fecha not in nombre_de:
            continue
        nombre = nombre_de.get(fecha) or d.get("day_name_es")
        base = {"date": fecha, "day_name_es": nombre, "session_slot": d.get("session_slot"),
                "sport": d.get("sport"), "focus": d.get("focus"), "intensity_class": d.get("intensity_class")}
        constraint = restriccion_del_dia(availability, nombre, fecha)
        if not constraint.get("available", True):
            fuera.append({**base, "type": "day_not_available"})
            continue
        bloques = constraint.get("blocks") or []
        indice = d.get("block_index")
        permitidos = (bloques[indice].get("sports") if isinstance(indice, int) and 0 <= indice < len(bloques)
                      else constraint.get("sports"))
        if permitidos and d.get("sport") not in set(permitidos):
            fuera.append({**base, "type": "sport_not_allowed", "permitidos": sorted(permitidos)})
    return fuera


# "Total 2500 m", "Total: 3,5 km", "total 2.500 m".
_TOTAL_DECLARADO = re.compile(r"\btotal\s*:?\s*(\d+(?:[.,]\d+)?)\s*(km|m)\b", re.I)
# "2x(4x100 m + 200 m)": un bloque repetido entero.
_BLOQUE_REPETIDO = re.compile(r"(\d+)\s*[x×]\s*\(([^()]*)\)")
# "6x100 m", "400 m", "3,5 km". No "1:47/100" ni "(8 largos)": sin unidad no cuenta.
_TRAMO = re.compile(r"(?<![\d/:.,])(?:(\d+)\s*[x×]\s*)?(\d+(?:[.,]\d+)?)\s*(km|m)\b(?!\s*/)", re.I)


def _metros(numero: str, unidad: str) -> float:
    valor = float(numero.replace(".", "").replace(",", ".")) if unidad.lower() == "m" else \
        float(numero.replace(",", "."))
    return valor * (1000 if unidad.lower() == "km" else 1)


# Un paréntesis que no es un bloque repetido es una nota: "(8 largos)", "(400 m)" como
# subtotal, "(50 m pool)". Contarlo sumaba de más (2026-09-23: "Total 3.500 m (50 m
# pool)... 600 m (últimos 200 m algo más alegres)" se leía como 3.750).
_NOTA_ENTRE_PARENTESIS = re.compile(r"\([^()]*\)")


def _metros_del_texto(texto: str) -> float:
    total = 0.0
    for repeticiones, dentro in _BLOQUE_REPETIDO.findall(texto):
        total += int(repeticiones) * _metros_del_texto(dentro)
    resto = _NOTA_ENTRE_PARENTESIS.sub(" ", _BLOQUE_REPETIDO.sub(" ", texto))
    for rep, numero, unidad in _TRAMO.findall(resto):
        total += (int(rep) if rep else 1) * _metros(numero, unidad)
    return total


def totales_que_no_suman(dias: list, tolerancia: float = 0.05) -> list[dict[str, Any]]:
    """Las nataciones de la DECISIÓN cuyo "Total N m" no es lo que suman sus series.

    Atleta 2, 2026-09-23: pidió natación de "mínimo 2,5 km" y la decisión escribió
    "... 400 m + 4x50 m + 6x100 m + 4x200 m + 300 m. Total 2500 m" -- 2.300 m -- y
    un racional que decía "Sigo tu petición de natación (2,5 km y 3,5 km)". El
    codificador copia las series, así que al reloj llegaban 2.300. Es aritmética del
    modelo, y quien sabe si manda el total o las series es quien lo escribió: se le
    devuelve junto con la disponibilidad, en la misma vuelta.

    Sólo se mira lo que se puede leer sin dudar: una natación que declara su total y
    cuyas series llevan unidad, y **sólo cuando las series se quedan POR DEBAJO** del
    total -- que es el caso que perjudica al atleta. Leer texto libre cuenta de más
    ("últimos 200 m algo más alegres" dentro de un calentamiento de 600): en la semana
    del 2026-10-02 eso marcó una natación que sumaba bien y le costó una vuelta de más.
    """
    salida: list[dict[str, Any]] = []
    for dia in dias:
        d = dia if isinstance(dia, dict) else dia.model_dump()
        if d.get("sport") != "swim" or d.get("is_rest"):
            continue
        texto = d.get("session_sketch") or ""
        declarado = _TOTAL_DECLARADO.search(texto)
        if not declarado:
            continue
        total = _metros(declarado.group(1), declarado.group(2))
        series = _metros_del_texto(texto[:declarado.start()] + " " + texto[declarado.end():])
        if total > 0 and series > 0 and (total - series) / total > tolerancia:
            salida.append({"date": d.get("date"), "session_slot": d.get("session_slot"),
                           "focus": d.get("focus"), "declarado_m": round(total), "suman_m": round(series)})
    return salida


def validate_weekly_plan(
    payload: WeeklyPlanPayload,
    availability: dict[str, Any],
    week_dates: list[dict[str, str]],
) -> dict[str, Any]:
    """Deterministic (non-LLM) check of a generated plan against the athlete's
    day-by-day availability/equipment table AND against the coaching rules in
    _coaching_violations. Returns {"valid": bool, "violations": [...]}."""

    # The athlete's real numbers (services/ai/prescription.py), threaded in
    # alongside availability by weekly_planner_node. Empty for an athlete with
    # no Garmin data, in which case the modality rules stay quiet rather than
    # demanding a pace nobody can compute.
    prescription: dict[str, Any] = availability.get("prescription") or {}

    all_days = [day for week in payload.weeks for day in week.days]

    seen: dict[str, list] = {}
    for day in all_days:
        seen.setdefault(day.date, []).append(day)

    expected_dates = {d["date"] for d in week_dates}
    violations: list[dict[str, Any]] = []

    # `fecha` y no `date`: con `date` este bucle tapaba el `date` importado para
    # toda la función, y el primer código que lo usó más abajo (los días sin
    # entrenar del check-in, 2026-09-15) reventó con "'str' object has no
    # attribute 'fromisoformat'". Era el F402 de la línea base de ruff.
    for fecha in expected_dates:
        occurrences = seen.get(fecha, [])
        if not occurrences:
            # Detail deliberately includes the date itself: _translate_violation's
            # output is deduplicated by TEXT in the frontend banner via a plain
            # Counter-style read (see web_app/static/app.js's fetchCurrentPlan),
            # and this type's message used to be fully generic ("El plan no
            # incluye este dia") — every missing date produced byte-identical
            # text, so an athlete whose first draft was missing several
            # different days saw what looked like one repeated line instead of
            # a list of which days. Found during Fase D's end-to-end review
            # (a real generation's first draft was missing 12 different dates).
            violations.append({"date": fecha, "type": "missing_date", "detail": f"El plan no incluye el {fecha}"})
        elif len(occurrences) > MAX_SESSIONS_PER_DAY:
            violations.append({"date": fecha, "type": "duplicate_date",
                               "detail": f"{fecha}: {len(occurrences)} entradas para esta fecha (maximo {MAX_SESSIONS_PER_DAY} sesiones por dia)"})
        elif len(occurrences) > 1:
            slots = [o.session_slot for o in occurrences]
            if len(set(slots)) < len(slots) or "unica" in slots:
                violations.append({
                    "date": fecha, "type": "duplicate_date",
                    "detail": (f"{fecha}: {len(occurrences)} entradas para esta fecha pero session_slot no las distingue "
                               f"({sorted(slots)}) — cada sesion del dia necesita su propia clave, en orden: "
                               f"{', '.join(_SLOT_ORDER[1:len(occurrences) + 1])}"),
                })

    for extra_date in set(seen) - expected_dates:
        violations.append({"date": extra_date, "type": "unexpected_date", "detail": f"{extra_date} esta fuera de la ventana de 28 dias solicitada"})

    # Los días que el atleta marcó en el check-in como "esta semana no puedo"
    # (Database/022). Mismo trato que un día no disponible de su tabla, pero por
    # FECHA: la tabla es por día de la semana y vale para siempre.
    fechas_sin_entrenar = set(availability.get("unavailable_dates") or [])
    # El nombre del día sale de las fechas reales, no del texto del modelo: si
    # escribiera "Martes" en un miércoles, se juzgaría contra la tabla de otro día.
    nombre_de = {d["date"]: d.get("day_name_es") for d in week_dates}

    for day in all_days:
        if day.date not in expected_dates:
            continue  # already flagged as unexpected_date above

        nombre = nombre_de.get(day.date) or day.day_name_es
        constraint = restriccion_del_dia(availability, nombre, day.date)
        duration_min = _day_duration_minutes(day)

        if not constraint.get("available", True):
            if not day.is_rest or day.steps:
                violations.append({
                    "date": day.date,
                    "session_slot": day.session_slot,
                    "type": "day_not_available",
                    "detail": f"{nombre} esta marcado como no disponible pero el plan agenda '{day.sport}'",
                })
            continue

        if day.is_rest:
            continue

        if not day.steps:
            violations.append({
                "date": day.date,
                "session_slot": day.session_slot,
                "type": "missing_steps",
                "detail": f"'{day.sport}' en {day.day_name_es} ({day.session_slot}) no tiene steps — un dia de entrenamiento real necesita calentamiento/bloque principal/enfriamiento, no solo un titulo.",
            })

        blocks = constraint.get("blocks") or []
        idx = _block_of(day, blocks)
        block = blocks[idx] if idx is not None and idx < len(blocks) else None

        allowed_sports = block.get("sports") if block else constraint.get("sports")
        usados = _sports_used(day)
        no_permitidos = sorted(usados - set(allowed_sports)) if allowed_sports else []
        if no_permitidos:
            donde = f"{block['start']}-{block['end']}" if block else day.session_slot
            que = "', '".join(no_permitidos)
            violations.append({
                "date": day.date,
                "session_slot": day.session_slot,
                "type": "sport_not_allowed",
                "detail": f"'{que}' no esta en los deportes permitidos {allowed_sports} para {nombre} ({donde})",
            })

        # Las horas se juzgan POR BLOQUE, sumando todas las sesiones que caen
        # dentro. Antes se juzgaban sesion a sesion, asi que dos sesiones en la
        # misma ventana pasaban las dos aunque juntas no cupieran.
        if block is not None:
            usado = _block_loads(seen.get(day.date, []), blocks).get(idx, duration_min)
            tope = block.get("hours")
            if tope is not None and usado > tope * 60 * 1.10:
                cuantas = sum(1 for o in seen.get(day.date, [])
                              if not o.is_rest and _block_of(o, blocks) == idx)
                detalle = (f"{usado:.0f}min en {cuantas} sesion(es) exceden el bloque de "
                           f"{block['start']}-{block['end']} ({tope}h, +10% tolerancia) el {day.day_name_es}")
                violations.append({
                    "date": day.date, "session_slot": day.session_slot,
                    "type": "hours_exceeded", "detail": detalle,
                })
        else:
            hours_budget = constraint.get("hours")
            if hours_budget is not None and duration_min > hours_budget * 60 * 1.10:
                violations.append({
                    "date": day.date,
                    "session_slot": day.session_slot,
                    "type": "hours_exceeded",
                    "detail": f"{duration_min:.0f}min excede el presupuesto de {hours_budget}h (+10% tolerancia) para {day.day_name_es} ({day.session_slot})",
                })

        violations.extend(_coaching_violations(day, prescription))

    # ---- Lo que el atleta PIDIO y no aparece ---------------------------------
    #
    # La contraparte de `day_not_available`: aquella caza entrenar donde no se
    # puede, y esta caza NO entrenar donde se ha pedido. Existe porque hasta la
    # v1.1 el perfil solo sabia expresar permiso, y un atleta que queria nadar
    # los martes no tenia donde decirlo -- el modelo veia un hueco y decidia,
    # correctamente segun sus reglas, no usarlo.
    #
    # Solo se comprueban los `fijo`. Los `posible` NO: no usarlos es una
    # decision de entrenador perfectamente legitima, y convertirlos en
    # obligacion volveria a llenar la semana de huecos rellenados, que es el
    # fallo que _multi_block_note lleva documentando desde v7.2.
    #
    # NO es reparable en tier 1. `day_not_available` se arregla poniendo el dia
    # a descanso, que es determinista; anadir una sesion que falta exige decidir
    # duracion e intensidad, y eso es criterio. Va al prompt de reparacion.
    peticiones = availability.get("sport_days_fijos") or {}
    if peticiones:
        hechos: dict[str, set[str]] = {}
        for day in all_days:
            if day.date in expected_dates and not day.is_rest:
                hechos.setdefault(nombre_de.get(day.date) or day.day_name_es, set()).update(_sports_used(day))
        # Un FIJO no se exige el día que el atleta dijo que esta semana no puede.
        dias_sin_entrenar = set()
        for fecha in fechas_sin_entrenar & set(expected_dates):
            try:
                dias_sin_entrenar.add(("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo")[
                    date.fromisoformat(fecha).weekday()])
            except ValueError:
                continue
        # Ni el que no cae en este bloque. La primera semana puede empezar a mitad de
        # semana: la del atleta 2 del 2026-09-23 iba de miércoles a domingo, y se le
        # exigió la fuerza FIJA del lunes y del martes. Dos reparaciones por IA que no
        # podían cumplirse (~22.000 tokens de salida, más que la semana entera).
        dias_del_bloque = set()
        for fecha in expected_dates:
            try:
                dias_del_bloque.add(("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo")[
                    date.fromisoformat(fecha).weekday()])
            except ValueError:
                continue
        for dia_es, pedidos in peticiones.items():
            if dia_es in dias_sin_entrenar or (dias_del_bloque and dia_es not in dias_del_bloque):
                continue
            faltan = sorted(set(pedidos) - hechos.get(dia_es, set()))
            if faltan:
                violations.append({
                    "date": None,
                    "session_slot": None,
                    "type": "sport_requested_missing",
                    "detail": (
                        f"el atleta marco {', '.join(faltan)} como FIJO el {dia_es} y la semana no lo "
                        f"lleva. Ponlo, aunque sea corto y suave; si de verdad no debe ir, dilo en la "
                        f"`adaptation` de ese dia en vez de quitarlo en silencio."
                    ),
                })

    # EL VOLUMEN DE LA SEMANA NO ES UNA VIOLACION. Hubo aqui dos reglas --
    # `weekly_hours_exceeded` y, en la v1.2, `weekly_load_exceeded` -- que
    # juzgaban el total de la semana contra un presupuesto calculado y lo
    # hacian cumplir acortando sesiones o pagando un tier-2. Pero cuanto
    # entrena un atleta es la decision de entrenador por excelencia, y la toma
    # un modelo que tiene delante su historia y como respondio; el validador
    # solo tenia una estimacion con ruido (la carga por hora de una natacion
    # "suave" del atleta real va de 24 a 102). Este archivo valida lo que se
    # puede validar sin criterio: unidades, zonas, disponibilidad, carreras.
    #
    # Y NO QUEDA NINGUNA. La ultima era el guardarrail de ACWR: con el ACWR de
    # Garmin por encima de 1,5 ("el corte de alto riesgo de lesion" de Gabbett)
    # y una semana 1 al 85 % del presupuesto, la recortaba al 70-80 % y le
    # decia al atleta que su carga estaba "en zona de riesgo". Se retiro el
    # 2026-09-14: es un umbral de poblacion, el ACWR no tiene valor predictivo
    # de lesion que lo sostenga (Impellizzeri 2020) y el resto de la app ya lo
    # trata como descripcion, nunca como regla. Venia activado por defecto, asi
    # que tampoco era "una regla que pidio el atleta". Con el ACWR de 1,71 que
    # el atleta 2 tuvo el 2026-09-05, cualquier semana normal se habria
    # recortado y explicado con un riesgo que nadie puede medir.

    competitions = availability.get("competitions") or []
    if competitions:
        # Una carrera en un día que ESTA semana dijo que no puede no se exige: es lo que
        # dijo para esta semana, y exigirla la escribía para que la disponibilidad la quitara.
        cerradas = set(availability.get("unavailable_dates") or [])
        violations.extend(_race_day_violations(
            all_days, [c for c in competitions if str(c.get("date") or "")[:10] not in cerradas], expected_dates))

    return {"valid": len(violations) == 0, "violations": violations}


_TIPOS_DE_TRABAJO = frozenset({"main", "interval", "repeat"})

# "Z1-Z2", "Z2 a Z3", "Z1/Z2"... el texto nombra un RANGO de zonas.
_RANGO_DE_ZONAS = re.compile(
    r"\bZ([1-5])\s*(?:-|–|—|/|a|hasta)\s*Z?([1-5])\b",
    re.IGNORECASE,
)

# Los dos pasos que son una RAMPA, no un estado. Nunca llevan zona objetivo.
#
# ## Por que no "calentamiento siempre Z2, enfriamiento siempre Z1"
#
# Es la propuesta obvia y reintroduce exactamente la queja que este bloque
# existe para arreglar, en los dos extremos:
#
#   - Un calentamiento EMPIEZA en reposo. Con objetivo Z2, el reloj avisa
#     durante todo el principio, que es justo cuando el atleta esta haciendo
#     lo correcto: subir poco a poco.
#   - Un enfriamiento empieza donde acabo la ultima serie. Despues de 8x2' con
#     picos de 188 ppm, el pulso tarda minutos en bajar a Z1. Con objetivo Z1,
#     el reloj avisa durante esos minutos — y no hay nada que el atleta pueda
#     hacer para obedecerlo salvo pararse.
#
# El objetivo de Garmin no es un techo: avisa por ARRIBA y por ABAJO. En un
# tramo cuyo pulso esta cambiando a proposito, cualquier zona unica es un aviso
# garantizado en la mitad del tramo. Y el aviso no es gratis: es la razon por
# la que el atleta deja de mirar los avisos, incluidos los que si importan.
#
# Lo que el entrenador quiere decir en estos dos pasos — "entra suave", "baja
# tranquilo" — va en el texto, que Garmin muestra igual. Lo que se pierde al
# soltar la zona es una restriccion que nadie quiso poner.
# La recuperacion DENTRO de una serie es el mismo transitorio: 90" de trote
# despues de una repeticion fuerte se corren con el pulso todavia en Z3 o Z4,
# asi que un objetivo Z1 avisa durante toda la recuperacion.
_RAMPAS = frozenset({"warmup", "cooldown", "recovery"})


def _soltar_zona_en_rangos(payload: WeeklyPlanPayload) -> tuple[WeeklyPlanPayload, bool]:
    """Un paso que dice "Z1-Z2" no lleva zona objetivo. Ninguna.

    ## El fallo, con la queja del atleta

    Garmin no admite un objetivo de DOS zonas: `target_zone` es un entero. Asi
    que cuando el entrenador escribe "10' calentamiento muy facil, Z1-Z2", el
    codificador colapsa a una -- Z2, siempre la alta -- y el rango sobrevive
    solo en el texto.

    En la muñeca eso significa que **el reloj le avisa por bajar a Z1 durante
    su propio calentamiento**. Es literalmente la queja con la que empezo esto:
    "el reloj me regaña cuando bajo de Z2 en un rodaje suave".

    Medido en la generacion del 2026-09-11: SEIS pasos asi en una sola semana,
    todos calentamientos y enfriamientos, todos forzados a Z2.

    ## Por que soltar la zona en vez de elegir la baja

    Porque elegir Z1 seria el mismo error al reves: el atleta que sube a Z2 en
    una cuesta tampoco hizo nada mal. Lo que el entrenador quiso decir es "no
    fuerces", y eso en Garmin se dice con `target_type="none"` y el texto
    delante. El paso conserva su duracion, su descripcion y su sitio; lo unico
    que pierde es una restriccion que el entrenador nunca puso.

    Y se hace aqui y no en el prompt porque el prompt YA lo pide desde la v1.1
    ("un paso lleva UNA zona o ninguna") y el modelo lo incumple de todas
    formas. Una regla que el modelo puede desobedecer no es una garantia.
    """
    cambiado = False
    for week in payload.weeks:
        for day in week.days:
            for step in _walk_steps(day.steps or []):
                if step.target_type != "hr_zone" or step.target_zone is None:
                    continue
                if str(step.step_type or "").lower() in _RAMPAS:
                    # Un calentamiento o un enfriamiento, diga lo que diga su
                    # texto: ver _RAMPAS.
                    step.target_type = TargetType.NONE
                    step.target_zone = None
                    cambiado = True
                    continue
                m = _RANGO_DE_ZONAS.search(str(step.description or ""))
                # Solo si nombra DOS zonas distintas: "Z2-Z2" no es un rango.
                if not m or m.group(1) == m.group(2):
                    continue
                # El enum, no el texto: el modelo no valida al asignar, y un str
                # aqui soltaba dos avisos de Pydantic por paso en cada semana
                # (585 en los logs del 2026-09-14) que tapaban los de verdad.
                step.target_type = TargetType.NONE
                step.target_zone = None
                cambiado = True
    return payload, cambiado





_RITMO_EN_TEXTO = re.compile(r"(\d{1,2}):([0-5]\d)(?:\s*[–-]\s*(\d{1,2}):([0-5]\d))?\s*/\s*km")
_HOLGURA_RITMO_S = 5


def _ritmo_como_dice_el_paso(payload: WeeklyPlanPayload) -> tuple[WeeklyPlanPayload, list[str]]:
    """El objetivo de ritmo que va al reloj no puede contradecir el texto del paso.

    ## El fallo, en la semana de referencia del atleta 2 (2026-09-13)

    Viernes, dos días antes del 5K: el entrenador escribió "4×30" a ritmo 5K
    controlado (≈5:12/km, sin apretar)" y el codificador guardó el paso con
    esa misma descripción y un objetivo de **4:51-5:00/km**. El reloj le habría
    pitado por ir a 5:12 -- justo el ritmo que le pidieron -- y le habría
    empujado a correr más rápido de lo decidido en la última activación antes
    de competir. Ningún validador lo veía: los dos ritmos están dentro de lo que
    el atleta puede sostener.

    ## Por qué manda el texto

    La descripción es la intención del entrenador copiada de la decisión; el
    objetivo es su codificación. Cuando se contradicen, la codificación está
    mal. Sólo se corrige cuando el texto nombra UN ritmo o UN rango -- con dos
    ("1er km ~5:20, luego 5:12") no hay una sola lectura y no se toca -- y sólo
    en carrera, donde los ritmos son por kilómetro.
    """
    cambios: list[str] = []
    for week in payload.weeks:
        for day in week.days:
            if day.sport != "run":
                continue
            for step in _walk_steps(day.steps or []):
                if step.target_type != TargetType.PACE or step.target_low is None or step.target_high is None:
                    continue
                ritmos = _RITMO_EN_TEXTO.findall(str(step.description or ""))
                if len(ritmos) != 1:
                    continue
                m1, s1, m2, s2 = ritmos[0]
                a = int(m1) * 60 + int(s1)
                b = int(m2) * 60 + int(s2) if m2 else a
                rapido, lento = min(a, b), max(a, b)
                if a == b:
                    rapido, lento = a - 6, a + 6
                bajo, alto = sorted((float(step.target_low), float(step.target_high)))
                if bajo <= lento + _HOLGURA_RITMO_S and alto >= rapido - _HOLGURA_RITMO_S:
                    continue
                cambios.append(
                    f"{day.date} '{step.description}': objetivo {bajo:.0f}-{alto:.0f} s/km -> {rapido}-{lento} s/km"
                )
                step.target_low, step.target_high = float(rapido), float(lento)
    for cambio in cambios:
        logger.warning("Ritmo del reloj corregido para que diga lo que dice el paso: %s", cambio)
    return payload, cambios


# Un tiempo escrito en el paso: 1'20", 15-20", 45 seg, 3', 17-18', 30 min. Nunca
# un trozo de ritmo ("5:10-5:16/km"): el lookbehind descarta cifras pegadas a
# ':' y a otras cifras.
_TIEMPO_EN_TEXTO = re.compile(
    r"(?<![\d:.,])(\d{1,3})\s*['’′]\s*(\d{2})\s*[\"”″]?"
    r"|(?<![\d:.,])(\d{1,3})(?:\s*[–-]\s*(\d{1,3}))?\s*(?:[\"”″]|seg(?:undos?)?\b|s\b)"
    r"|(?<![\d:.,])(\d{1,3})(?:\s*[–-]\s*(\d{1,3}))?\s*(?:['’′]|min(?:utos?)?\b)"
)
_DISTANCIA_EN_TEXTO = re.compile(r"(?<![\d:.,])(\d{1,5}(?:[.,]\d+)?)\s*(km|m)\b(?!\s*/)")
_HOLGURA_DURACION = 0.2


# Las horas: "1 h 50 min", "1h50", "2 h", "1,5 h", "2 horas". Sin esto, de "~1 h 50 min a tope"
# sólo se leía "50 min" y la red puso 3.000 s en el paso de una carrera de 6.600 (prueba local
# del 2026-10-09, trail de 15 km): el reloj la habría dado por terminada a los 50'.
_HORAS_EN_TEXTO = re.compile(
    r"(?<![\d:.,])(\d{1,2}(?:[.,]\d)?)\s*(?:h|horas?)\b\.?"
    r"(?:\s*(?:y\s*)?(\d{1,2})\s*(?:min(?:utos?)?\b|['’′])?)?"
    r"|(?<![\d:.,])(\d{1,2})h(\d{2})\b",
    re.I,
)


def _segundos_escritos(texto: str) -> list[tuple[float, float]]:
    rangos = []
    for m in _HORAS_EN_TEXTO.finditer(texto):
        if m.group(3):
            s = int(m.group(3)) * 3600 + int(m.group(4)) * 60
        else:
            s = float(m.group(1).replace(",", ".")) * 3600 + (int(m.group(2)) * 60 if m.group(2) else 0)
        rangos.append((float(s), float(s)))
    # Lo que ya se leyó como horas no se vuelve a leer como minutos sueltos.
    texto = _HORAS_EN_TEXTO.sub(" ", texto)
    for m in _TIEMPO_EN_TEXTO.finditer(texto):
        if m.group(1):
            s = int(m.group(1)) * 60 + int(m.group(2))
            rangos.append((float(s), float(s)))
        elif m.group(3):
            a = int(m.group(3))
            rangos.append((float(a), float(int(m.group(4)) if m.group(4) else a)))
        else:
            a = int(m.group(5)) * 60
            rangos.append((float(a), float(int(m.group(6)) * 60 if m.group(6) else a)))
    return rangos


def _duracion_como_dice_el_paso(payload: WeeklyPlanPayload) -> tuple[WeeklyPlanPayload, list[str]]:
    """La duración que va al reloj no puede contradecir el texto del paso.

    ## El fallo, en la semana de la atleta 3 (2026-09-14, a 6 días de su 5K)

    El codificador escribió bien el texto y mal el número, dos veces en la
    misma semana, sin que ninguna regla lo viera:

    - "15-20\\" progresivos, acelerando suave" con `duration_value=90`: el
      miércoles y el calentamiento de la CARRERA le habrían pedido tres
      aceleraciones de minuto y medio en vez de tres de veinte segundos.
    - "200m trote muy suave Z1" con `duration_type="time"` y `200`: copió los
      metros en el campo de los segundos; 3'20" de trote entre series de 400.

    La semana del atleta 2 generada diez minutos antes codificó esos mismos dos
    pasos bien (20 s y 120 s): es un error intermitente del codificador, no del
    prompt, y por eso se arregla aquí.

    ## Qué se toca, y qué no

    Manda el texto, igual que en `_ritmo_como_dice_el_paso`, y sólo cuando
    tiene UNA lectura: un único tiempo (o rango) escrito. "20\\" rápido, 40\\"
    suave" tiene dos y no se toca. Si el número cae dentro del rango escrito,
    con un 20 % de holgura, tampoco. Los metros copiados como segundos sólo se
    reinterpretan cuando el número es EXACTAMENTE el de la distancia escrita y
    el texto no nombra ningún tiempo: 120 s para "200 m trote" es una
    aproximación razonable del entrenador y se respeta.
    """
    cambios: list[str] = []
    for week in payload.weeks:
        for day in week.days:
            if day.sport == "strength":
                continue
            for step in _walk_steps(day.steps or []):
                if step.substeps or step.duration_type != "time" or not step.duration_value:
                    continue
                texto = str(step.description or "")
                tiempos = _segundos_escritos(texto)
                valor = float(step.duration_value)
                if len(tiempos) == 1:
                    lo, hi = tiempos[0]
                    if lo <= 0 or lo * (1 - _HOLGURA_DURACION) - 2 <= valor <= hi * (1 + _HOLGURA_DURACION) + 2:
                        continue
                    nuevo = float(round((lo + hi) / 2))
                    cambios.append(f"{day.date} '{texto}': {valor:.0f} s -> {nuevo:.0f} s")
                    step.duration_value = nuevo
                elif not tiempos:
                    distancias = _DISTANCIA_EN_TEXTO.findall(texto)
                    if len(distancias) != 1:
                        continue
                    numero, unidad = distancias[0]
                    metros = float(numero.replace(",", ".")) * (1000 if unidad == "km" else 1)
                    if abs(valor - metros) >= 0.5:
                        continue
                    cambios.append(f"{day.date} '{texto}': {valor:.0f} s -> {metros:.0f} m")
                    step.duration_type = "distance"
                    step.duration_value = metros
    for cambio in cambios:
        logger.warning("Duración del reloj corregida para que diga lo que dice el paso: %s", cambio)
    return payload, cambios


def _recolocar_enfriamientos(payload: WeeklyPlanPayload) -> tuple[WeeklyPlanPayload, bool]:
    """El enfriamiento va AL FINAL. Siempre.

    ## El fallo, con la sesion real

    Generacion del atleta 2 del 2026-09-10, sesion de umbral del viernes:

        1 warmup    10'  Z1
        2 repeat x3      3x(6' a 5:46-6:08/km, 2' rec Z1)
        3 cooldown  10'  Z1
        4 main      15'  "Si te sientes suelto: 4x15\" strides..."

    Un bloque de TRABAJO despues del enfriamiento. En el reloj eso se ejecuta
    tal cual: el atleta enfria diez minutos y entonces el reloj le pide
    esfuerzos. Es exactamente el hueco que persigue este repo -- la forma es la
    de un entrenador (calentamiento, series, enfriamiento) y el entreno no se
    puede correr asi.

    ## Por que se arregla aqui y no con el modelo

    Porque no hay nada que decidir: el enfriamiento es, por definicion, lo
    ultimo. Mover el paso es deterministico y no cambia ni una dosis, mientras
    que mandarselo a la reparacion de tier-2 cuesta ~$0.11 y le da la
    oportunidad de reescribir una semana que por lo demas estaba bien.

    No toca nada mas: ni duraciones, ni objetivos, ni el numero de pasos.
    """
    cambiado = False
    for week in payload.weeks:
        for day in week.days:
            pasos = day.steps or []
            if len(pasos) < 2:
                continue
            enfriamientos = [s for s in pasos if s.step_type == "cooldown"]
            if not enfriamientos:
                continue
            resto = [s for s in pasos if s.step_type != "cooldown"]
            # Solo si de verdad hay trabajo DESPUES del ultimo enfriamiento.
            ultimo = max(i for i, s in enumerate(pasos) if s.step_type == "cooldown")
            if not any(s.step_type in _TIPOS_DE_TRABAJO for s in pasos[ultimo + 1:]):
                continue
            day.steps = resto + enfriamientos
            for i, s in enumerate(day.steps, start=1):
                s.step_order = i
            cambiado = True
    return payload, cambiado


def _apply_tier1_fixes(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
    force: bool = False,
) -> tuple[WeeklyPlanPayload, bool]:
    fixable_types = set(_TIER1_FIXABLE_TYPES)
    if force:
        fixable_types |= {"hours_exceeded"}

    # Match on (date, session_slot) when the violation names a slot, so
    # forcing a bad PM session to rest never wipes a perfectly valid AM
    # session on a two-a-day. Violations without a slot (or "unica") fix
    # every occurrence for that date — same as the old single-session behavior.
    fixable_pairs = {
        (v["date"], v.get("session_slot", "unica"))
        for v in violations if v["type"] in fixable_types and v.get("date")
    }
    if not fixable_pairs:
        return payload, False

    fixable_dates_any_slot = {date for date, slot in fixable_pairs if slot == "unica"}
    tipo_de = {(v["date"], v.get("session_slot", "unica")): v["type"]
               for v in violations if v["type"] in fixable_types and v.get("date")}
    motivo_de = {
        "day_not_available": "ese día no está disponible",
        "sport_not_allowed": "tu disponibilidad no tiene ese deporte ese día",
        "hours_exceeded": "no cabía en las horas de ese día",
    }

    changed = False
    for week in payload.weeks:
        notas = []
        for day in week.days:
            if day.date in fixable_dates_any_slot or (day.date, day.session_slot) in fixable_pairs:
                # Nada se quita en silencio. La semana de José del 2026-09-15 dejó
                # un descanso titulado "5K pace", con el propósito de las series y
                # un racional que las seguía contando.
                if not day.is_rest:
                    tipo = tipo_de.get((day.date, day.session_slot)) or tipo_de.get((day.date, "unica"))
                    motivo = motivo_de.get(tipo, "no cumplía tu disponibilidad")
                    quitado = day.focus or day.sport
                    day.focus = "Descanso"
                    day.purpose = f"Aquí iba «{quitado}», pero {motivo}, así que se quitó."
                    day.adaptation = ""
                    day.intensity_class = "rest"
                    day.discipline = "descanso"
                    notas.append(f"se quitó «{quitado}» del {(day.day_name_es or '').lower()} {day.date} "
                                 f"porque {motivo}")
                day.is_rest = True
                day.sport = "rest"
                day.steps = []
                day.total_duration_minutes = 0
                changed = True
        if notas:
            week.week_rationale = (f"{(week.week_rationale or '').rstrip()}\n\nNota: "
                                   + "; ".join(notas) + ".").strip()

    return payload, changed


def _fallback_steps_for_day(day) -> list[WorkoutStep]:
    """Last-resort deterministic single step derived from the day's own
    focus/duration — used only when tier-2 repair exhausts its attempts and
    a non-rest day still has steps=[]. See _apply_missing_steps_fallback.

    Sport-aware since Fase 4: a continuous Z2 time-based step (right for
    run/bike/swim/walk) makes no sense written on a "strength" day.

    Plan Maestro v4 Track A: strength is freeform by design (the athlete
    picks exercises/sets/reps, not the AI — see WEEKLY_PLANNER_USER_PROMPT),
    so this is no longer an apologetic last resort for strength — it IS the
    normal shape of a strength day's steps, reached directly without ever
    invoking tier-2 LLM repair (see the strength short-circuit in
    validate_and_repair_weekly_plan)."""
    duration = day.total_duration_minutes or 30
    if day.sport == "strength":
        return [WorkoutStep(
            step_order=1,
            step_type="main",
            duration_type="time",
            duration_value=duration * 60,
            repeat_count=1,
            target_type=TargetType.NONE,
            description=(
                f"{day.focus or 'Fuerza'} — estructura libre ({duration:.0f} min), "
                "series y repeticiones a criterio del atleta"
            ),
        )]
    return [WorkoutStep(
        step_order=1,
        step_type="main",
        duration_type="time",
        duration_value=duration * 60,
        repeat_count=1,
        target_type=TargetType.HR_ZONE,
        target_zone=2,
        description=(
            f"{duration:.0f}' continuo Z2 ({day.focus or day.sport}) — "
            "paso de respaldo generado automaticamente tras agotar los intentos de reparacion por IA"
        ),
    )]


# Which pace band a session belongs to, inferred from what it calls itself.
# ORDER MATTERS, most specific first. "maraton" has to be tested before the
# threshold keywords because "ritmo de maraton" contains "ritmo": matching it
# as threshold work would prescribe ~5:45/km where the athlete needed ~7:00/km,
# which is not a rounding error but a different session entirely.
_FOCUS_TO_PACE_CLASS = (
    (("repeticion", "repetición", "velocidad", "speed", "sprint", "recta"), "repeticiones"),
    (("maraton", "maratón", "marathon"), "maraton"),
    # Deliberately NOT matching the bare word "suave": it turns up inside hard
    # sessions too ("tempo suave"), and classifying one of those as recovery
    # would prescribe a jog where the athlete needed threshold work.
    (("recuperacion", "recuperación", "regenerativ"), "recuperacion"),
    (("vo2", "maxima", "máxima", "intervalo", "interval", "series", "pista", "track"), "vo2max"),
    (("tempo", "umbral", "threshold", "ritmo"), "umbral_tempo"),
)


# The declared session class -> the prescription's pace-band key. Only the
# classes that actually get a pace band appear here; the rest never reach this
# function (see _needs_pace_modality).
_INTENSITY_CLASS_TO_PACE_CLASS = {
    "tempo_threshold": "umbral_tempo",
    "vo2max": "vo2max",
    "repeticiones": "repeticiones",
    "long_run": "facil",
    "easy": "facil",
    "recovery": "recuperacion",
}


def _pace_class_for_day(day) -> str:
    """Which pace band this session should be prescribed from.

    Prefers the class the coach DECLARED over sniffing keywords, for the same
    reason as _needs_pace_modality: the decision layer already knows what the
    session is, and the keyword table has to resolve genuinely ambiguous
    Spanish ("ritmo de maratón" contains "ritmo") by careful ordering that a
    declared value makes unnecessary. The table below stays as the fallback
    for plans stored before intensity_class existed.
    """
    declared = getattr(day, "intensity_class", None)
    if declared and declared in _INTENSITY_CLASS_TO_PACE_CLASS:
        return _INTENSITY_CLASS_TO_PACE_CLASS[declared]

    focus_text = f"{day.focus or ''} {day.purpose or ''}".lower()
    for keywords, class_key in _FOCUS_TO_PACE_CLASS:
        if any(keyword in focus_text for keyword in keywords):
            return class_key
    # An unlabelled quality session is safest treated as threshold work: it is
    # the least aggressive of the hard classes, so guessing wrong here under-
    # doses rather than handing the athlete a session too fast to complete.
    return "umbral_tempo"


# La duracion que la propia descripcion del paso anuncia.
#
# La notacion del plan pone SIEMPRE el tiempo delante -- "10' calentamiento
# Z1", "1' a 95-105 rpm Z2", "20\" de descanso en el borde" -- asi que la
# primera cifra con marca de tiempo es la del paso. La condicion de no ir
# precedida de digito, dos puntos, coma o punto es la que impide leer el "59"
# de un ritmo "1:52-1:59/100m" como cincuenta y nueve minutos.
_MINUTOS_EN_TEXTO = re.compile(r"(?<![\d:.,])(\d{1,3})\s*(?:'|′|min\b|minutos\b)", re.IGNORECASE)
_SEGUNDOS_EN_TEXTO = re.compile(r"(?<![\d:.,])(\d{1,3})\s*(?:\"|″|s\b|seg\b|segundos\b)", re.IGNORECASE)


def _segundos_que_dice_la_descripcion(descripcion: str) -> float | None:
    """Cuantos segundos anuncia el texto del paso, o None si no lo dice."""
    texto = descripcion or ""
    minutos = _MINUTOS_EN_TEXTO.search(texto)
    segundos = _SEGUNDOS_EN_TEXTO.search(texto)
    # Gana el que aparezca antes: "20\" de descanso" es segundos, "10' suave"
    # es minutos, y una linea que mencione los dos empieza por el que manda.
    if minutos and (not segundos or minutos.start() < segundos.start()):
        return float(minutos.group(1)) * 60
    if segundos:
        return float(segundos.group(1))
    return None


def _apply_duration_fallback(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
) -> tuple[WeeklyPlanPayload, bool]:
    """Ultimo recurso para `duration_mismatch`, cuando la reparacion por IA no
    lo cuadro.

    ## Por que se puede arreglar sin inventar nada

    El paso lleva la misma sesion escrita dos veces: en `description`, para el
    atleta, y en `duration_value`, para el reloj. Cuando discrepan, una de las
    dos es un error de tecleo -- y la descripcion es la que el entrenador
    escribio y la que el atleta va a leer en el movil. Asi que se lee el tiempo
    que anuncia el texto y se le pone al paso.

    ## Y por que solo se acepta si CUADRA

    Leer una duracion de una frase es una heuristica, y una heuristica que se
    aplica a ciegas es como se estropea un plan que estaba bien. Aqui hay un
    arbitro: despues de corregir se vuelven a sumar los minutos, y el cambio
    solo se queda si la suma ya concuerda con el total declarado. Si no, se
    deshace entero.

    ## Cuando ni eso

    Si no hay nada legible en el texto, no se toca ni un paso: se corrige el
    TOTAL para que diga lo que los pasos van a durar de verdad. No arregla la
    sesion, pero deja de mentir -- y las reglas de horas, que hasta ahora
    creian el total declarado, pasan a ver los minutos reales y pueden hacer
    su trabajo (acortar, o en el peor caso cancelar el dia). Un plan honesto y
    largo es reparable; uno que dice 45 y ejecuta 160 no lo ve nadie.
    """
    marcados = {(v.get("date"), v.get("session_slot"))
                for v in violations if v["type"] == "duration_mismatch"}
    if not marcados:
        return payload, False

    cambiado = False
    for week in payload.weeks:
        for day in week.days:
            if (day.date, day.session_slot) not in marcados:
                continue
            declarado = day.total_duration_minutes or 0
            originales = {id(s): s.duration_value for s in _walk_steps(day.steps)}
            tocados = False
            for step in _walk_steps(day.steps):
                if step.substeps or step.duration_type != "time":
                    continue
                dice = _segundos_que_dice_la_descripcion(step.description)
                actual = step.duration_value or 0
                if dice is None or abs(dice - actual) <= max(30.0, actual * 0.2):
                    continue
                step.duration_value = dice
                tocados = True

            if tocados:
                ahora = _steps_duration_seconds(day.steps) / 60
                if ahora <= declarado * _TOLERANCIA_DURACION + _HOLGURA_DURACION_MIN:
                    logger.info(
                        "Duracion de %s (%s) corregida desde las descripciones: %.0f min",
                        day.date, day.session_slot, ahora)
                    cambiado = True
                    continue
                for step in _walk_steps(day.steps):
                    step.duration_value = originales[id(step)]

            real = _steps_duration_seconds(day.steps) / 60
            logger.warning(
                "No se pudo cuadrar la duracion de %s (%s); el total declarado pasa de "
                "%.0f a %.0f min para que refleje los pasos",
                day.date, day.session_slot, declarado, real)
            day.total_duration_minutes = round(real)
            cambiado = True

    return payload, cambiado


def _apply_coaching_fallbacks(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
    prescription: dict[str, Any],
) -> tuple[WeeklyPlanPayload, bool]:
    """Deterministic last resort for the coaching rules, after tier-2 repair.

    Every fix here is mechanical and conservative: it makes the session
    executable without inventing training intent. A plan that reaches Garmin
    with intervals back-to-back, or a threshold session the athlete has to
    pace by heart rate, is worse than one nudged into shape by a rule.
    """
    flagged = {
        (v.get("date"), v.get("session_slot"), v["type"])
        for v in violations
        if v["type"] in {
            "intervals_without_recovery", "quality_without_pace",
            "easy_step_with_pace", "pace_out_of_range",
        }
    }
    if not flagged:
        return payload, False

    bands = prescription.get("pace_bands") or {}
    extremes = _pace_extremes(prescription)
    changed = False

    def flagged_for(day, violation_type: str) -> bool:
        return (day.date, day.session_slot, violation_type) in flagged

    for week in payload.weeks:
        for day in week.days:
            if day.is_rest or not day.steps:
                continue

            # 1. Put a recovery inside any repeat block that has none.
            if flagged_for(day, "intervals_without_recovery"):
                for step in _walk_steps(day.steps):
                    if not step.substeps:
                        continue
                    if any(sub.step_type in ("recovery", "rest") for sub in step.substeps):
                        continue
                    work = next((sub for sub in step.substeps if _is_work_step(sub)), None)
                    if work is None:
                        continue
                    # Half the work duration, clamped to a sane 1-3 minutes.
                    work_seconds = work.duration_value if work.duration_type == "time" else 180
                    recovery_seconds = min(180.0, max(60.0, (work_seconds or 180) / 2))
                    # SIN zona: esto corre DESPUÉS de `_soltar_zona_en_rangos`, y
                    # una Z1 aquí le devolvía al atleta el aviso del reloj durante
                    # la recuperación, con el pulso todavía bajando de la serie.
                    # Es lo que ya pide el prompt de reparación de tier 2.
                    step.substeps.append(WorkoutStep(
                        step_order=len(step.substeps) + 1,
                        step_type="recovery",
                        duration_type="time",
                        duration_value=recovery_seconds,
                        target_type=TargetType.NONE,
                        description=(
                            f"{recovery_seconds / 60:.0f}' trote suave de recuperacion Z1 — "
                            "anadido automaticamente: el bloque repetia el esfuerzo sin descanso"
                        ),
                    ))
                    changed = True

            # 2. Move quality work onto the right pace band.
            if flagged_for(day, "quality_without_pace") and bands:
                band = bands.get(_pace_class_for_day(day))
                if band:
                    for step in _walk_steps(day.steps):
                        if _is_work_step(step) and step.target_type != TargetType.PACE:
                            step.target_type = TargetType.PACE
                            step.target_low, step.target_high = float(band[0]), float(band[1])
                            step.target_zone = None
                            changed = True

            # 3. Easy steps go back to heart rate.
            if flagged_for(day, "easy_step_with_pace"):
                for step in _walk_steps(day.steps):
                    if step.step_type in _EASY_STEP_TYPES and step.target_type == TargetType.PACE:
                        # SIN objetivo, no con una zona: si se pusiera Z1 o Z2
                        # aqui, esta reparacion desharia lo que
                        # `_soltar_zona_en_rangos` acaba de arreglar, y el
                        # reloj volveria a avisar en la rampa.
                        step.target_type = TargetType.NONE
                        step.target_zone = None
                        step.target_low = step.target_high = None
                        changed = True

            # 4. Clamp an unreachable pace back inside the athlete's range.
            if flagged_for(day, "pace_out_of_range") and extremes:
                fastest, slowest = extremes
                for step in _walk_steps(day.steps):
                    if step.target_type != TargetType.PACE:
                        continue
                    for attr in ("target_low", "target_high"):
                        value = getattr(step, attr)
                        if value is not None and not (fastest <= value <= slowest):
                            setattr(step, attr, min(max(value, fastest), slowest))
                            changed = True

    return payload, changed


def _apply_missing_steps_fallback(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
) -> tuple[WeeklyPlanPayload, bool]:
    """Last-resort fix for missing_steps once tier-2 repair has exhausted its
    attempts. Forcing the day to rest (like _apply_tier1_fixes force=True does
    for hours_exceeded) would silently cancel a session the athlete is
    expecting to see — instead synthesize one simple continuous step from the
    day's own focus/duration, so a non-rest day never reaches Garmin upload
    with an empty step list (the original production bug this validator
    exists to catch)."""
    fixable_pairs = {
        (v["date"], v.get("session_slot", "unica"))
        for v in violations if v["type"] == "missing_steps" and v.get("date")
    }
    if not fixable_pairs:
        return payload, False

    changed = False
    for week in payload.weeks:
        for day in week.days:
            if not day.steps and (day.date, day.session_slot) in fixable_pairs:
                day.steps = _fallback_steps_for_day(day)
                if day.total_duration_minutes is None:
                    fallback_step = day.steps[0]
                    # duration_value is seconds for a time-based fallback
                    # step, but reps for a strength one (see
                    # _fallback_steps_for_day) — dividing a rep count by 60
                    # produced a nonsensical ~0.2min total, found during
                    # Fase D's cross-phase review.
                    day.total_duration_minutes = (
                        fallback_step.duration_value / 60
                        if fallback_step.duration_type == "time"
                        else 20.0
                    )
                changed = True

    return payload, changed


def _apply_missing_date_fallback(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
    week_dates: list[dict[str, str]],
) -> tuple[WeeklyPlanPayload, bool]:
    """Last-resort fix for missing_date once tier-2 repair has exhausted its
    attempts: synthesize a rest-day WorkoutDay for any date that still never
    made it into the payload.

    Plan Maestro v4 Track D: missing_date used to have NO fallback at all --
    unlike missing_steps/hours_exceeded/day_not_available, which all force a
    safe default when repair can't fix them, a date the LLM failed to
    reconstruct just stayed absent from `payload.weeks` forever. In a real
    run, 6 of a week's 7 days (including the athlete's race day) vanished
    this way, and the UI's summary message claimed they'd been "forced to
    rest" when nothing had actually been forced -- the day simply didn't
    exist. A synthesized rest day the athlete can see and override is honest
    about what happened; an invisible gap in the calendar is not."""
    missing_dates = {v["date"] for v in violations if v["type"] == "missing_date" and v.get("date")}
    if not missing_dates:
        return payload, False

    day_name_by_date = {d["date"]: d.get("day_name_es", "") for d in week_dates}
    # week_dates is chunked into 7-date groups matching payload.weeks'
    # week_number 1..N by position (see weekly_planner_node.py's week_chunks)
    # -- use that to route each missing date to the week it actually belongs
    # to, since a date absent from the payload has no day of its own to read
    # a week_number off of.
    week_number_by_date: dict[str, int] = {}
    for index, entry in enumerate(week_dates):
        week_number_by_date[entry["date"]] = (index // 7) + 1

    weeks_by_number = {week.week_number: week for week in payload.weeks}
    changed = False
    for missing_date in sorted(missing_dates):
        week_number = week_number_by_date.get(missing_date, payload.weeks[0].week_number if payload.weeks else 1)
        week = weeks_by_number.get(week_number)
        new_day = WorkoutDay(
            date=missing_date,
            day_name_es=day_name_by_date.get(missing_date, ""),
            is_rest=True,
            sport="rest",
            focus="Descanso",
            purpose=(
                "Dia forzado a descanso: la IA no logro generar una sesion valida para esta fecha "
                "tras varios intentos de correccion."
            ),
            adaptation="Revisa este dia manualmente si esperabas una sesion entrenable.",
            steps=[],
            total_duration_minutes=0,
        )
        if week is None:
            weeks_by_number[week_number] = WeekBlock(week_number=week_number, days=[new_day])
        else:
            week.days.append(new_day)
        changed = True

    if not changed:
        return payload, False
    payload.weeks = [weeks_by_number[n] for n in sorted(weeks_by_number)]
    return payload, True


def _apply_duplicate_date_fallback(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
) -> tuple[WeeklyPlanPayload, bool]:
    """Last-resort fix for duplicate_date that survives repair: keep only the
    first occurrence for a flagged date and drop the rest.

    By construction (validate_weekly_plan), duplicate_date only fires when a
    date's occurrences are NOT validly distinguished (two identical slots,
    an "unica" mixed with another slot, or 3+ occurrences) -- a legitimate
    am/pm pair never reaches here. Dropping the extras never invents
    content; it just prevents an ambiguous/duplicate session from reaching
    Garmin for the same day."""
    flagged_dates = {v["date"] for v in violations if v["type"] == "duplicate_date" and v.get("date")}
    if not flagged_dates:
        return payload, False

    changed = False
    for week in payload.weeks:
        seen_dates: set[str] = set()
        kept_days = []
        for day in week.days:
            if day.date in flagged_dates:
                if day.date in seen_dates:
                    changed = True
                    continue
                seen_dates.add(day.date)
            kept_days.append(day)
        week.days = kept_days

    return payload, changed


def _anotar_redes(cambios: list[tuple[str, Any]]) -> None:
    """Las redes que corrigieron algo, al libro de redes (`core/redes_ledger.py`).

    `cambios` es [(red, lo que devolvió)]: una lista de cambios, un booleano o un número.
    Nunca lanza.
    """
    from core import redes_ledger

    for red, cambio in cambios:
        if not cambio:
            continue
        if isinstance(cambio, list):
            for detalle in cambio:
                redes_ledger.anotar(red, str(detalle))
        elif isinstance(cambio, bool):
            redes_ledger.anotar(red)
        else:
            for _ in range(int(cambio)):
                redes_ledger.anotar(red)


_PASOS_QUE_NO_SON_LA_CARRERA = ("warmup", "cooldown", "recovery", "rest")


def carrera_hasta_pulsar_vuelta(payload: WeeklyPlanPayload) -> tuple[WeeklyPlanPayload, list[str]]:
    """En un día de carrera, la carrera va al reloj «hasta pulsar vuelta», no por un tiempo.

    El responsable, 2026-10-09: el paso de la carrera duraba lo que el atleta estimó (el trail
    de prueba del atleta 2, "~1 h 50 min", 6.600 s). Si tarda más, el reloj pasa al enfriamiento
    en plena carrera; nadie sabe cuánto dura una carrera hasta que la termina. Es un contrato,
    no criterio: se aplica en código, como los grupos de fuerza.

    La carrera es el paso más largo del día que no es calentamiento, enfriamiento ni
    recuperación, fuera de los bloques de repetición (las rectas del calentamiento no lo son).
    Sin ninguno así, no se toca nada.

    **Y va sin objetivo** (el responsable, 2026-10-19): el plan de ritmos va en su texto. Con Haiku
    5.5 el 10K del atleta 2 («km 1-2 a 5:28-5:32, km 3-8 a 5:24-5:28, y a tope») salió una vez con
    5:24-5:32 para toda la carrera: el reloj habría pitado justo en el final a tope. Va en código y
    no en el prompt porque el prompt lo leería también para las sesiones de carrera a pie, que
    sí llevan ritmo; esto sólo toca el paso de la competición (`race_day`).
    """
    cambios: list[str] = []
    for week in payload.weeks:
        for day in week.days:
            if day.intensity_class != "race_day" or day.is_rest or day.sport == "strength":
                continue
            candidatos = [s for s in day.steps or []
                          if not s.substeps and (s.step_type or "") not in _PASOS_QUE_NO_SON_LA_CARRERA]
            if not candidatos:
                continue
            ya = [s for s in candidatos if s.duration_type == "lap_button"]
            if ya:
                # El codificador ya la escribió así (prueba del 2026-10-09): sólo falta que el
                # atleta lea qué hacer en la meta.
                carrera = ya[0]
            else:
                carrera = max(candidatos, key=lambda s: (s.duration_type == "distance", float(s.duration_value or 0)))
                cambios.append(f"{day.date} '{carrera.description}': {carrera.duration_type} "
                               f"{carrera.duration_value} -> hasta pulsar vuelta")
                carrera.duration_type = "lap_button"
                carrera.duration_value = None
            if (carrera.target_type or "none") != "none":
                cambios.append(f"{day.date} '{carrera.description}': objetivo {carrera.target_type} "
                               f"{carrera.target_low}-{carrera.target_high} -> sin objetivo (el plan va en el texto)")
                carrera.target_type = TargetType.NONE
                carrera.target_low = carrera.target_high = None
                carrera.target_zone = None
            if "vuelta" not in (carrera.description or "").lower():
                carrera.description = f"{carrera.description or 'Carrera'} — pulsa vuelta al cruzar la meta"
    for cambio in cambios:
        logger.info("Carrera al reloj hasta pulsar vuelta: %s", cambio)
    return payload, cambios


def _race_day_session_fields(comp: dict[str, Any]) -> tuple[str, list[WorkoutStep], float]:
    comp_name = comp.get("name") or "competencia"
    total_minutes = comp.get("estimated_duration_minutes") or 45
    steps = [
        WorkoutStep(
            step_order=1, step_type="warmup", duration_type="time", duration_value=600,
            repeat_count=1, target_type=TargetType.NONE,
            description="10' calentamiento suave antes de la carrera",
        ),
        # «Hasta pulsar vuelta»: la duración de una carrera no se sabe (ver carrera_hasta_pulsar_vuelta).
        WorkoutStep(
            step_order=2, step_type="main", duration_type="lap_button", repeat_count=1,
            target_type=TargetType.NONE,
            description=f"Carrera: {comp_name} — ejecuta tu pacing objetivo y pulsa vuelta al cruzar la meta",
        ),
        WorkoutStep(
            step_order=3, step_type="cooldown", duration_type="time", duration_value=300,
            repeat_count=1, target_type=TargetType.NONE,
            description="5' trote suave de vuelta a la calma",
        ),
    ]
    return comp_name, steps, total_minutes


def _apply_race_day_fallback(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
    competitions: list[dict[str, Any]],
    week_dates: list[dict[str, str]],
) -> tuple[WeeklyPlanPayload, bool]:
    """Last-resort deterministic fix for race_day_conflict/race_taper_conflict
    once tier-2 repair -- which already gets the competition dates in its own
    prompt via availability["competitions"] -- has exhausted its attempts.
    Intentionally the LAST thing tried, not the first: a real taper depends
    on how much volume the athlete was carrying that week, which an LLM with
    real data context judges far better than a fixed template.

    Verified-against-real-run fix: race_day_conflict can now fire for a date
    with NO WorkoutDay at all (see _race_day_violations), not just an
    existing-but-wrong one -- so this has to be able to synthesize the day
    from scratch, the same way _apply_missing_date_fallback does for an
    ordinary missing date, instead of only mutating days that already exist."""
    comps_by_date = {c["date"]: c for c in competitions if c.get("date")}
    race_day_dates = {v["date"] for v in violations if v["type"] == "race_day_conflict" and v.get("date")}
    taper_dates = {v["date"] for v in violations if v["type"] == "race_taper_conflict" and v.get("date")}
    if not race_day_dates and not taper_dates:
        return payload, False

    changed = False
    seen_race_dates: set[str] = set()
    for week in payload.weeks:
        for day in week.days:
            if day.date in race_day_dates:
                comp = comps_by_date.get(day.date, {})
                comp_name, steps, total_minutes = _race_day_session_fields(comp)
                day.is_rest = False
                day.sport = _deporte_del_dia_de_carrera(comp, day.sport)
                day.focus = "Dia de competencia"
                day.purpose = f"Dia de carrera: {comp_name}."
                day.adaptation = "Ejecuta tu plan de ritmo de carrera; si algo duele de verdad, para."
                day.total_duration_minutes = total_minutes
                day.steps = steps
                seen_race_dates.add(day.date)
                changed = True
            elif day.date in taper_dates:
                day.focus = "Taper"
                day.purpose = "Sesion reducida en la ventana de taper antes de tu competencia."
                day.adaptation = "Manten todo comodo; no busques ningun esfuerzo maximo esta semana."
                day.total_duration_minutes = min(day.total_duration_minutes or 30, 30)
                que = {"bike": "rodaje", "swim": "nado"}.get(day.sport or "", "trote")
                day.steps = [
                    WorkoutStep(
                        step_order=1, step_type="main", duration_type="time", duration_value=1200,
                        repeat_count=1, target_type=TargetType.HR_ZONE, target_zone=1,
                        description=f"20' {que} suave Z1 — taper, sin intensidad",
                    ),
                ]
                changed = True

    missing_race_dates = race_day_dates - seen_race_dates
    if missing_race_dates:
        day_name_by_date = {d["date"]: d.get("day_name_es", "") for d in week_dates}
        week_number_by_date: dict[str, int] = {}
        for index, entry in enumerate(week_dates):
            week_number_by_date[entry["date"]] = (index // 7) + 1
        weeks_by_number = {week.week_number: week for week in payload.weeks}

        for race_date in sorted(missing_race_dates):
            comp = comps_by_date.get(race_date, {})
            comp_name, steps, total_minutes = _race_day_session_fields(comp)
            new_day = WorkoutDay(
                date=race_date,
                day_name_es=day_name_by_date.get(race_date, ""),
                is_rest=False,
                sport=_deporte_del_dia_de_carrera(comp, None),
                focus="Dia de competencia",
                purpose=f"Dia de carrera: {comp_name}.",
                adaptation="Ejecuta tu plan de ritmo de carrera; si algo duele de verdad, para.",
                steps=steps,
                total_duration_minutes=total_minutes,
            )
            week_number = week_number_by_date.get(
                race_date, payload.weeks[0].week_number if payload.weeks else 1,
            )
            week = weeks_by_number.get(week_number)
            if week is None:
                weeks_by_number[week_number] = WeekBlock(week_number=week_number, days=[new_day])
            else:
                week.days.append(new_day)
            changed = True

        if weeks_by_number:
            payload.weeks = [weeks_by_number[n] for n in sorted(weeks_by_number)]

    return payload, changed


_TIER2_REPAIR_SYSTEM_PROMPT = """You are a deterministic plan-repair specialist. You are NOT writing a new
training plan — you are given one complete, already-generated WeeklyPlanPayload (potentially spanning several
weeks) plus a list of specific rule violations found in specific weeks/dates.

## Your ONLY job
Return the SAME WeeklyPlanPayload, with ONLY the flagged violations fixed. Every week, and every day within
every week, that was NOT flagged must come back byte-for-byte identical to the input you were given — same
dates, same sport, same steps, same everything. Never drop a week, merge weeks, shorten the plan, or
regenerate a day that was not flagged.

## How to fix each violation type
- sport_requested_missing: the athlete marked that sport as FIXED for that day. Add it. Keep it short
  and easy if the week's load is already high, and say so in that day's `adaptation` — but do not
  leave the day without it, and never drop another day to make room.
- day_not_available / sport_not_allowed: change that specific day to a rest day (is_rest=true, sport="rest",
  steps=[], total_duration_minutes=0).
- hours_exceeded: shorten that specific session (fewer/shorter steps, lower total_duration_minutes) to fit
  its budget.
- missing_steps: add real steps (warmup/main-set/cooldown structure) to that specific day — it cannot stay
  empty.
- duplicate_date / missing_date / unexpected_date: fix the day entries for that date so exactly the right
  number of entries exist, with the correct session_slot values.
- intervals_without_recovery: the repeat block repeats an effort with nothing easy between rounds, so the
  athlete starts the next repetition the instant the previous one ends. Add a recovery step INSIDE that
  block's `substeps`, after the work step (step_type="recovery", target_type="none" -- the athlete's heart
  rate is still coming down from the rep, so any zone target beeps at them, a
  duration appropriate to the effort — roughly half to equal the work duration for short intervals).
- quality_without_pace: a quality RUN session must carry a pace target on its work steps, not a heart-rate
  one. Set target_type="pace" with target_low/target_high in SECONDS PER KILOMETRE (e.g. 340 and 355 for
  5:40-5:55/km), using the band for that session's intensity class from the Prescription Card. Leave the
  warmup, cooldown and recovery steps with target_type="none".
- quality_without_power: same idea on the bike — the work steps need target_type="power" in watts.
- easy_step_with_pace: a warmup, cooldown or recovery step is prescribed by pace. Switch it to
  target_type="none" -- these three steps carry NO target at all, because the athlete's heart rate is
  moving through zones by design and any single zone makes the watch beep at them for doing it right --
  and
  clear target_low/target_high.
- pace_out_of_range: the prescribed pace is outside anything this athlete can sustain. Replace it with a
  range taken from the Prescription Card's band for that session's intensity class. Remember the unit is
  seconds per kilometre, never minutes and never metres per second.
- race_day_conflict: this date IS one of the athlete's competitions (see "competitions" in the availability
  constraints JSON) but the session doesn't reflect it. Rewrite it as an actual race-day session: name the
  race, a short warmup, then the race effort itself (or the specific pacing plan if you have enough context),
  then a cooldown. Judge the exact shape from how much volume the athlete was carrying that week — this is
  not a fixed template.
- race_taper_conflict: this date is the day before a competition and carries a quality session in the
  race's own sport. Turn it into what the eve of a race is for: short and easy, with strides or a few brief
  race-pace activations if you want them — never a key session.

Return the complete corrected WeeklyPlanPayload — every week, every day, not just the ones you changed."""


def _format_violation_line(v: dict[str, Any]) -> str:
    if v.get("date"):
        return f"- {v['date']}: {v['type']} — {v['detail']}"
    if v.get("week_number") is not None:
        return f"- Semana {v['week_number']} (violacion a nivel de semana, sin fecha especifica): {v['type']} — {v['detail']}"
    return f"- {v['type']} — {v['detail']}"


def _index_days_by_key(payload: WeeklyPlanPayload) -> dict[tuple[str, str], Any]:
    return {(day.date, day.session_slot): day for week in payload.weeks for day in week.days}


def _strength_missing_steps_violations(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
) -> list[dict[str, Any]]:
    """missing_steps violations whose day is sport=='strength'.

    Plan Maestro v4 Track A: strength structure is the athlete's call, not
    the AI's (see WEEKLY_PLANNER_USER_PROMPT) — sending an empty-steps
    strength day to tier-2 LLM repair was the single biggest source of wasted
    reparation cost/failures (12/12 strength days in a real Lite-tier run,
    one of which threw a Pydantic OutputParserException that killed a whole
    repair attempt). These go straight to the deterministic single-step
    fallback (_fallback_steps_for_day) instead, before _tier2_repair ever
    sees them."""
    days_by_key = _index_days_by_key(payload)
    out = []
    for v in violations:
        if v["type"] != "missing_steps" or not v.get("date"):
            continue
        day = days_by_key.get((v["date"], v.get("session_slot", "unica")))
        if day is not None and day.sport == "strength":
            out.append(v)
    return out


def _reconcile_repair(
    original: WeeklyPlanPayload,
    repaired: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
) -> WeeklyPlanPayload:
    """Take ONLY what tier-2 repair was actually asked to fix from its output;
    every other day comes from `original`, untouched, regardless of what the
    repair call echoed back for it.

    The repair prompt tells the model every unflagged day must come back
    "byte-for-byte identical" — but this call receives the FULL merged
    multi-week payload and is asked to return the FULL payload again, which is
    exactly the failure mode weekly_planner_node.py's per-week chunking exists
    to avoid (see its module docstring: asked to reproduce ~28 days of nested
    step detail in one completion, Claude silently returns `steps=[]` for most
    of them, even though top-level fields like date/sport still come through).
    The pre-existing anti-truncation check in `_tier2_repair` only verified
    that no WEEK or DATE went missing — a day whose `steps` got wiped while
    its date stayed put slipped straight through it, then got misdiagnosed by
    the next validation pass as a fresh `missing_steps` violation and,
    two repair rounds later, flattened into the generic one-step fallback.
    That is precisely what produced a plan where only the days spared by luck
    kept their real structure and everything else read "paso de respaldo".

    Reconciling by key instead of trusting the model's full reproduction
    removes that failure mode structurally: an unflagged day simply cannot
    be damaged by this call, because its content never came from the model.

    Plan Maestro v4 Track D: violations that don't name a `session_slot`
    (duplicate_date, missing_date, unexpected_date -- see validate_weekly_plan,
    they're date-only by construction) used to be matched against a
    fabricated `("date", "unica")` key that could never equal the real
    `"am"`/`"pm"` keys the days actually carry, so repair's fix for that date
    was always discarded and the original broken entries survived untouched
    (this is exactly how the 2026-09-14 duplicate-"pm" pair shipped broken).
    For those, match on DATE ALONE and swap in every day `repaired` has for
    that date, not a single keyed lookup.
    """
    flagged_slots = {(v["date"], v["session_slot"]) for v in violations if v.get("date") and v.get("session_slot")}
    flagged_dates_any_slot = {v["date"] for v in violations if v.get("date") and not v.get("session_slot")}
    flagged_weeks = {v["week_number"] for v in violations if v.get("week_number") is not None}
    repaired_by_key = _index_days_by_key(repaired)
    repaired_by_date: dict[str, list] = {}
    for week in repaired.weeks:
        for day in week.days:
            repaired_by_date.setdefault(day.date, []).append(day)
    repaired_weeks_by_number = {week.week_number: week for week in repaired.weeks}

    reconciled_weeks = []
    for week in original.weeks:
        if week.week_number in flagged_weeks:
            # Whole-week violation (the ACWR guardrail was the one that used
            # this, retired 2026-09-14): the fix redistributes volume across every day in
            # the week, so there is no single "flagged day" to key off. Trust
            # the repaired week as a unit, matched by week_number -- still a
            # 7-day blast radius if the model mangles it, not a 28-day one.
            repaired_week = repaired_weeks_by_number.get(week.week_number)
            if repaired_week is not None and not repaired_week.week_rationale:
                repaired_week = repaired_week.model_copy(update={"week_rationale": week.week_rationale})
            reconciled_weeks.append(repaired_week if repaired_week is not None else week)
            continue

        new_days = []
        handled_any_slot_dates: set[str] = set()
        for day in week.days:
            if day.date in flagged_dates_any_slot:
                if day.date in handled_any_slot_dates:
                    continue  # already emitted repaired's day(s) for this date once
                handled_any_slot_dates.add(day.date)
                replacement = repaired_by_date.get(day.date)
                new_days.extend(replacement if replacement else [day])
                continue

            key = (day.date, day.session_slot)
            if key in flagged_slots and key in repaired_by_key:
                new_days.append(repaired_by_key[key])
            else:
                new_days.append(day)
        # Una COPIA de la semana con sus dias nuevos, no un WeekBlock hecho de
        # cero: asi se perdia `week_rationale`, el "por que esta semana". Visto
        # el 2026-09-11 en la semana del atleta 2: una reparacion tier-2 por un
        # solo dia y el razonamiento llego vacio a MySQL, a la app y al PDF.
        reconciled_weeks.append(week.model_copy(update={"days": new_days}))

    return WeeklyPlanPayload(
        athlete_name=original.athlete_name,
        start_date=original.start_date,
        zones_table=repaired.zones_table or original.zones_table,
        weeks=reconciled_weeks,
    )


async def _tier2_repair(
    payload: WeeklyPlanPayload,
    violations: list[dict[str, Any]],
    llm_with_structure,
    availability: dict[str, Any],
) -> WeeklyPlanPayload:
    violation_lines = "\n".join(_format_violation_line(v) for v in violations)
    if not violation_lines:
        return payload

    # The prescription card is markdown meant to be read, not a JSON blob:
    # pulled out of the constraints dump so the repair model can actually use
    # the pace/power bands it needs to fix a modality violation.
    constraints = {k: v for k, v in availability.items() if k != "prescription"}
    prescription_card = (availability.get("prescription") or {}).get("card") or ""

    repair_messages = [
        {"role": "system", "content": _TIER2_REPAIR_SYSTEM_PROMPT},
        {
            "role": "user",
            "content": (
                "## Athlete availability constraints (JSON)\n"
                f"```json\n{json.dumps(constraints, indent=2, ensure_ascii=False)}\n```\n\n"
                + (f"{prescription_card}\n\n" if prescription_card else "")
                + "## Violations to fix\n"
                f"{violation_lines}\n\n"
                "## Plan to repair (WeeklyPlanPayload JSON)\n"
                f"```json\n{payload.model_dump_json()}\n```\n\n"
                "Return the complete corrected WeeklyPlanPayload, changing ONLY what's needed to resolve "
                "the violations listed above."
            ),
        },
    ]

    try:
        result = await llm_with_structure.ainvoke(repair_messages)
    except Exception:
        logger.exception("Tier-2 plan repair LLM call failed; keeping previous draft")
        return payload

    # Accepts either a wrapper with `.output` (WeeklyPlannerOutputs, the
    # historical shape) or a bare WeeklyPlanPayload -- callers are moving to
    # union-free output schemas because the `list[Question] | WeeklyPlanPayload`
    # union measurably degrades structured output (a real run failed here with
    # pydantic reporting errors for BOTH union branches at once), and repair
    # never needs the HITL branch.
    output = getattr(result, "output", result)
    if not isinstance(output, WeeklyPlanPayload) or not output.weeks:
        logger.warning("Tier-2 plan repair returned no usable payload (%s); keeping previous draft", type(output))
        return payload

    # Reconcile instead of trusting `output` wholesale -- see _reconcile_repair
    # for why. Only days/weeks actually named in `violations` are taken from
    # the model's response; everything else comes from `payload` untouched.
    reconciled = _reconcile_repair(payload, output, violations)

    flagged_slots = {(v["date"], v["session_slot"]) for v in violations if v.get("date") and v.get("session_slot")}
    flagged_dates_any_slot = {v["date"] for v in violations if v.get("date") and not v.get("session_slot")}
    flagged_weeks = {v["week_number"] for v in violations if v.get("week_number") is not None}
    repaired_by_key = _index_days_by_key(output)
    repaired_dates = {day.date for week in output.weeks for day in week.days}
    repaired_week_numbers = {week.week_number for week in output.weeks}
    missing_slots = flagged_slots - set(repaired_by_key)
    missing_dates = flagged_dates_any_slot - repaired_dates
    missing_weeks = flagged_weeks - repaired_week_numbers
    if missing_slots or missing_dates or missing_weeks:
        logger.warning(
            "Tier-2 plan repair didn't return %d of the flagged day(s)/week(s) (%s / %s / %s); "
            "those specific violations carry over to the next repair attempt",
            len(missing_slots) + len(missing_dates) + len(missing_weeks), missing_slots, missing_dates, missing_weeks,
        )

    return reconciled


_VIOLATION_PREFIX_ES = {
    "day_not_available": "Día marcado como no disponible pero la IA quiso entrenar igual — se ajustó a descanso",
    "sport_not_allowed": "Deporte no permitido para ese día/bloque — se ajustó a descanso",
    "sport_requested_missing": "Faltaba un deporte que marcaste como fijo y se le pidió a tu entrenador que lo pusiera",
    "hours_exceeded": "Una sesión excedía el tiempo disponible de su bloque",
    "missing_steps": "Un día quedó sin pasos detallados y se le pidió a la IA que los completara",
    "duplicate_date": "Conflicto de sesiones duplicadas en la misma fecha",
    "missing_date": "Faltaba un día dentro de la ventana de 28 días del plan",
    "unexpected_date": "Apareció una fecha fuera de la ventana de 28 días solicitada",
    # NO son las horas disponibles del perfil (este atleta tiene 14.25h) sino
    # el volumen PRESCRITO para esta semana, que es contra lo que se valida
    # desde v4 Track F. Decirlo mal hacía que el aviso pareciera un error de
    # la app -- "excede 5.5h" con 14.25h declaradas no se sostiene.
    "intervals_without_recovery": "Un bloque de repeticiones no tenía recuperación entre repeticiones — se añadió",
    "quality_without_pace": "Una sesión de calidad estaba prescrita por frecuencia cardiaca en vez de por ritmo",
    "quality_without_power": "Una sesión de calidad en bici no llevaba objetivo de potencia",
    "easy_step_with_pace": "Un calentamiento, enfriamiento o recuperación llevaba objetivo de ritmo en vez de zona de FC",
    "pace_out_of_range": "Un ritmo prescrito quedaba fuera de lo que puedes sostener según tus datos reales",
    "race_day_conflict": "El día de una competencia tenía una sesión genérica en vez de reflejar la carrera",
    "race_taper_conflict": "Una sesión de calidad a full intensidad caía en la ventana de taper antes de una competencia",
    "duration_mismatch": "Un entrenamiento duraba mucho más de lo que decía — el reloj lo habría ejecutado así",
}


def _translate_violation(v: dict[str, Any]) -> str:
    """v['detail'] is already a human-readable Spanish sentence built in
    validate_weekly_plan (day name, sport, budget) — just prefix it with a
    short plain-language label for what kind of issue it was, so the athlete
    sees "Día marcado como no disponible... : Lunes esta marcado como no
    disponible pero el plan agenda 'bike'" instead of a raw violation type."""
    prefix = _VIOLATION_PREFIX_ES.get(v["type"], v["type"])
    detail = v.get("detail", "")
    return f"{prefix}: {detail}" if detail else prefix


async def validate_and_repair_weekly_plan(
    payload: WeeklyPlanPayload,
    availability: dict[str, Any],
    week_dates: list[dict[str, str]],
    llm_with_structure,
    max_repair_attempts: int = 2,
) -> tuple[WeeklyPlanPayload, dict[str, Any]]:
    """Validate the plan and repair it until it complies (or we run out of
    attempts, at which point remaining violations are force-resolved — rest
    days for hours_exceeded, synthesized steps for missing_steps — so a
    violating plan never reaches Garmin upload silently)."""

    # Antes de validar nada: el enfriamiento al final. No es una violacion que
    # reparar, es una colocacion que corregir, y corregirla puede evitar que la
    # sesion entre siquiera en el circuito de reparaciones. Ver
    # _recolocar_enfriamientos.
    payload, enfriamientos = _recolocar_enfriamientos(payload)
    # Y un paso que dice "Z1-Z2" se queda SIN zona: el reloj no sabe decir dos,
    # y forzar la alta hace que le avise por bajar en su propio calentamiento.
    payload, zonas = _soltar_zona_en_rangos(payload)
    # Y un objetivo de ritmo que contradice el texto de su propio paso se
    # reencaja al texto. Ver _ritmo_como_dice_el_paso.
    payload, ritmos = _ritmo_como_dice_el_paso(payload)
    # Lo mismo con la duración: "15-20\" progresivos" no puede ir al reloj
    # como 90 s. Ver _duracion_como_dice_el_paso.
    payload, duraciones = _duracion_como_dice_el_paso(payload)
    # Y la carrera, hasta pulsar vuelta (se vuelve a aplicar en el nodo tras reparar).
    payload, carreras = carrera_hasta_pulsar_vuelta(payload)
    # Cada corrección queda en el libro de redes (Beta 3.0, F1): el worker.log muere
    # con su trabajo y "una red que no corrige nada se borra" necesita la cuenta.
    _anotar_redes([("enfriamiento_al_final", enfriamientos), ("zona_en_rangos", zonas),
                   ("ritmo_del_paso", ritmos), ("duracion_del_paso", duraciones),
                   ("carrera_hasta_pulsar_vuelta", carreras)])

    report = validate_weekly_plan(payload, availability, week_dates)
    if report["valid"]:
        return payload, {"repaired": False, "attempts": 0, "violations_found": 0, "errors": [], "violations_translated": []}

    initial_violations_translated = [_translate_violation(v) for v in report["violations"]]

    payload, tier1_changed = _apply_tier1_fixes(payload, report["violations"])
    report = validate_weekly_plan(payload, availability, week_dates)

    # Strength days short-circuit straight to the deterministic fallback —
    # never through tier-2 LLM repair. See _strength_missing_steps_violations.
    strength_fallback_applied = False
    strength_violations = _strength_missing_steps_violations(payload, report["violations"])
    if strength_violations:
        payload, strength_fallback_applied = _apply_missing_steps_fallback(payload, strength_violations)
        if strength_fallback_applied:
            report = validate_weekly_plan(payload, availability, week_dates)

    attempts = 0
    # Las reparaciones que de VERDAD devolvieron un plan. `_tier2_repair`
    # devuelve el mismo objeto cuando la llamada falla, y hasta el 2026-09-13
    # el mensaje al atleta contaba intentos como correcciones: la semana del
    # atleta 2 dijo "se corrigio automaticamente (2 intento(s) de correccion
    # por IA)" cuando las dos llamadas habian dado 400 contra OpenAI.
    reparaciones_aplicadas = 0
    while not report["valid"] and attempts < max_repair_attempts:
        attempts += 1
        logger.info("Weekly plan validation failed (%d violations); tier-2 repair attempt %d/%d",
                    len(report["violations"]), attempts, max_repair_attempts)
        reparado = await _tier2_repair(payload, report["violations"], llm_with_structure, availability)
        if reparado is not payload:
            reparaciones_aplicadas += 1
        payload = reparado
        payload, _ = _apply_tier1_fixes(payload, validate_weekly_plan(payload, availability, week_dates)["violations"])
        report = validate_weekly_plan(payload, availability, week_dates)

    fallback_applied = False
    if not report["valid"]:
        payload, fallback_applied = _apply_missing_steps_fallback(payload, report["violations"])
        if fallback_applied:
            report = validate_weekly_plan(payload, availability, week_dates)

    # Race day/taper is resolved BEFORE the generic missing_date fallback
    # below, on purpose: a competition date can be flagged race_day_conflict
    # while ALSO being absent from the payload (missing_date fires too, see
    # _race_day_violations), and the generic fallback has no idea it's
    # special -- it would just synthesize a plain rest day. Doing race/taper
    # first means the generic fallback below never even sees that date as
    # still missing.
    race_day_fallback_applied = False
    if not report["valid"]:
        payload, race_day_fallback_applied = _apply_race_day_fallback(
            payload, report["violations"], availability.get("competitions") or [], week_dates,
        )
        if race_day_fallback_applied:
            report = validate_weekly_plan(payload, availability, week_dates)

    missing_date_fallback_applied = False
    if not report["valid"]:
        payload, missing_date_fallback_applied = _apply_missing_date_fallback(payload, report["violations"], week_dates)
        if missing_date_fallback_applied:
            report = validate_weekly_plan(payload, availability, week_dates)

    duplicate_date_fallback_applied = False
    if not report["valid"]:
        payload, duplicate_date_fallback_applied = _apply_duplicate_date_fallback(payload, report["violations"])
        if duplicate_date_fallback_applied:
            report = validate_weekly_plan(payload, availability, week_dates)

    # Coaching rules get their own deterministic pass before the blunt
    # force-to-rest below: a session with intervals back to back, or threshold
    # work prescribed by heart rate, is fixable mechanically. Cancelling the
    # day would be a far bigger intervention than the problem warrants.
    coaching_fallback_applied = False
    if not report["valid"]:
        payload, coaching_fallback_applied = _apply_coaching_fallbacks(
            payload, report["violations"], availability.get("prescription") or {},
        )
        if coaching_fallback_applied:
            report = validate_weekly_plan(payload, availability, week_dates)

    # Antes que las reglas de horas, a proposito: si aqui se corrige el total
    # declarado de un dia, es ESE numero -- ya verdadero -- el que las reglas
    # de horas tienen que juzgar. Al reves lo juzgarian contra el que estaba mal.
    duration_fallback_applied = False
    if not report["valid"]:
        payload, duration_fallback_applied = _apply_duration_fallback(payload, report["violations"])
        if duration_fallback_applied:
            report = validate_weekly_plan(payload, availability, week_dates)

    forced = False
    if not report["valid"]:
        payload, forced = _apply_tier1_fixes(payload, report["violations"], force=True)
        report = validate_weekly_plan(payload, availability, week_dates)

    _anotar_redes([
        ("dia_a_descanso", tier1_changed), ("reparacion_ia", reparaciones_aplicadas),
        ("respaldo_sin_pasos", fallback_applied or strength_fallback_applied),
        ("respaldo_dia_de_carrera", race_day_fallback_applied),
        ("respaldo_fecha_faltante", missing_date_fallback_applied),
        ("respaldo_fecha_duplicada", duplicate_date_fallback_applied),
        ("respaldo_reglas_de_coaching", coaching_fallback_applied),
        ("respaldo_duracion", duration_fallback_applied), ("forzado_a_descanso", forced),
    ])

    # Plan Maestro v4 Track D: this used to close with ONE generic sentence
    # ("N violaciones restantes forzadas a descanso") regardless of what
    # actually happened to those violations — missing_date had NO fallback
    # at the time, so days that vanished from the payload were described as
    # "forced to rest" when nothing had been forced; they just weren't
    # there. Each stage now gets its own accurate line, built from what that
    # stage actually did, and `forced` (the only stage that truly forces
    # days to rest) gets a sentence that says exactly that and nothing more.
    errors = []
    if (
        tier1_changed or attempts > 0 or fallback_applied or strength_fallback_applied
        or missing_date_fallback_applied or duplicate_date_fallback_applied
        or race_day_fallback_applied or coaching_fallback_applied
        or duration_fallback_applied or forced
    ):
        if strength_fallback_applied:
            errors.append(
                "Una o mas sesiones de fuerza se completaron con enfoque y minutos totales; "
                "la estructura de series y repeticiones queda a tu criterio."
            )
        if duration_fallback_applied:
            errors.append(
                "Un entrenamiento declaraba una duracion y sus pasos sumaban otra muy distinta "
                "(un `duration_value` en unidades equivocadas); se corrigio para que el reloj "
                "ejecute lo mismo que dice el plan."
            )
        if coaching_fallback_applied:
            errors.append(
                "Se ajustaron automaticamente uno o mas entrenamientos para que cumplan las reglas de "
                "coaching: recuperacion dentro de los bloques de repeticiones, objetivo de ritmo en las "
                "sesiones de calidad y zona de FC en calentamientos y recuperaciones."
            )
        if fallback_applied:
            errors.append(
                "Uno o mas dias quedaron sin pasos detallados tras los intentos de correccion por IA; "
                "se les genero un paso de respaldo simple (continuo, Zona 2) para que el entrenamiento no llegue vacio a Garmin."
            )
        if missing_date_fallback_applied:
            errors.append(
                "Uno o mas dias no llegaron a generarse tras varios intentos de correccion por IA; "
                "se completaron como descanso para que no falte ningun dia del calendario (revisalos manualmente)."
            )
        if duplicate_date_fallback_applied:
            errors.append(
                "Una fecha tenia sesiones duplicadas que la IA no logro distinguir tras la correccion; "
                "se conservo solo la primera."
            )
        if race_day_fallback_applied:
            errors.append(
                "Un dia de competencia o su ventana de taper no reflejaba la carrera tras la correccion "
                "por IA; se completo con una plantilla de carrera/taper de respaldo."
            )
        if forced:
            errors.append(
                "Uno o mas dias se forzaron a descanso porque el dia no estaba disponible, el deporte no "
                "estaba permitido, o la sesion excedia tu tiempo disponible."
            )
        remaining = len(report["violations"])
        if attempts and not reparaciones_aplicadas:
            # Decir "corregido por IA" cuando la IA no corrigio nada es la
            # forma exacta del fallo que mas se repite en este repo.
            logger.error("Tier-2: %d intento(s) de reparacion y ninguno devolvio un plan", attempts)
            errors.append(
                "El plan generado no cumplia tus restricciones inicialmente; la correccion por IA no se pudo "
                "aplicar, asi que se ajusto con reglas automaticas"
                + (f" ({remaining} ajuste(s) pendiente(s))" if remaining else "")
                + "."
            )
        else:
            errors.append(
                f"El plan generado no cumplia tus restricciones inicialmente; se corrigio automaticamente "
                f"({reparaciones_aplicadas} correccion(es) por IA"
                + (f", {remaining} ajuste(s) menor(es) adicional(es)" if remaining else "")
                + ")."
            )

    return payload, {
        "repaired": True,
        "attempts": attempts,
        "violations_found": len(report["violations"]),
        "errors": errors,
        # Specific, human-readable lines for the frontend banner (see
        # web_app/static/app.js's fetchCurrentPlan) — what the AI originally
        # tried to do wrong and how it got fixed, not just a generic count.
        "violations_translated": initial_violations_translated,
    }
