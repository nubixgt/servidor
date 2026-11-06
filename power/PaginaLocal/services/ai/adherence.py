"""What the athlete was prescribed versus what they actually did.

Why this module exists
----------------------
The coach could see what it had prescribed (services/ai/prescription.py and
the previous-plan block in the weekly planner prompt) but never what the
athlete had done with it. A plan that gets skipped every Thursday, or where
every "45 minutes easy" turns into 70 minutes, is telling the coach something
it had no way to hear -- so it kept prescribing into a void and the athlete
kept quietly diverging.

This matches each prescribed session against the athlete's real Garmin
activities and reports the result three ways: to the next generation's prompt
(so the plan responds to reality), to the UI (so the athlete sees their own
adherence), and to the printed plan.

Deliberately conservative about calling something "missed": a session with no
matching activity might be a watch that didn't sync, an activity logged under
an unexpected sport, or a run the athlete did on the treadmill at the gym.
Everything here is evidence for the coach to weigh, not a verdict.
"""

from __future__ import annotations

import logging
from dataclasses import dataclass, field
from datetime import UTC, date, datetime, timedelta, tzinfo
from typing import Any, Literal

from services.ai.prescription import _PACE_CLASS_MULTIPLIERS
from services.ai.ritmo_de_tramos import segundos_entre

logger = logging.getLogger(__name__)

Status = Literal["completed", "modified", "missed", "pending", "under_intensity"]

# Las clases de sesión en las que la INTENSIDAD es la sesión.
#
# Reportado por el atleta el 2026-09-04: *"tenía una sesión estructurada de
# VO2max, pero el atleta no la puso estructurada... aún así hizo una sesión
# justo como decía el plan pero en una sesión libre de running"*. Y al revés:
# alguien que sale a trotar suave el día del VO2max entrenó, pero no entrenó
# ESO. Emparejando sólo por fecha + deporte + duración, las dos cosas salían
# "completada", y el planificador de la semana siguiente leía que el estímulo
# de calidad se ejecutó cuando no se ejecutó -- que es justo el dato con el
# que decide si progresar la intensidad.
#
# SÓLO estas tres. Son las mismas que el prompt del planificador nombra como
# las que llevan objetivo de ritmo/potencia obligatorio. `hill_repeats` y
# `neuromuscular` quedan fuera a propósito: se prescriben por esfuerzo o por
# sensación, y en una cuesta o en una serie de 15 segundos la frecuencia
# cardiaca llega tarde. `race_day` también: una carrera larga puede correrse
# entera por debajo de umbral y sigue siendo la carrera.
_CLASES_DE_CALIDAD = ("vo2max", "tempo_threshold", "repeticiones")

# La zona a partir de la cual se considera que hubo trabajo de calidad.
#
# Se compara contra el PICO de la actividad, no contra la media: una sesión de
# series de verdad toca esta zona aunque el calentamiento, las recuperaciones
# y la vuelta a la calma le hundan la media. Usar la media marcaría como
# floja una sesión bien hecha, que es el error caro aquí -- el atleta que hizo
# su trabajo y lee que no lo hizo deja de creerse el resto del informe.
_ZONA_DE_CALIDAD = 4

# Pero el pulso llega TARDE en las series cortas, y el pico también puede no
# llegar. Seis meses del atleta 2: de 43 tramos llanos corridos más rápido que
# su umbral, en el 33 % ni el pico tocó Z4. Una sesión de calidad con el pico por
# debajo de Z4 NO se marca "sin la intensidad prescrita" si, en llano, pasó al
# menos `_SEGUNDOS_A_RITMO_DE_CALIDAD` a la banda de ritmo de su clase o más
# rápido. El ritmo sólo RESCATA, nunca acusa: el error caro aquí es el "no
# hiciste tu sesión" falso. Ver services/ai/ritmo_de_tramos.py.
_SEGUNDOS_A_RITMO_DE_CALIDAD = 120
_BANDA_DE_LA_CLASE = {"tempo_threshold": "umbral_tempo", "vo2max": "vo2max", "repeticiones": "repeticiones"}

# How far a session's real duration may drift from the prescription before it
# counts as changed rather than done. Athletes round to the nearest landmark,
# traffic light or lap of the park; a quarter of the session is generous
# enough that only a real difference in training decision shows up.
_DURATION_TOLERANCE = 0.25

# Cuantos dias puede correrse una sesion y seguir contando como hecha.
#
# La adherencia empareja por fecha + deporte, y nunca por el entreno
# programado en Garmin: un atleta que se sabe el plan de memoria y lo entrena
# sin abrir el entreno del reloj cuenta igual, que es como debe ser. Pero ese
# mismo atleta es justo el que mueve el martes al miercoles porque le llovio.
#
# Sin tolerancia, una sesion corrida un dia se contaba DOS VECES mal: la del
# martes como no realizada y la del miercoles como entrenamiento fuera del
# plan. Un entrenador no dice que no entrenaste; dice que lo moviste.
#
# Un dia y no mas: a dos dias ya no se distingue una sesion movida de otra
# distinta, y confundirlas ensuciaria el expediente que el macrociclo lee.
_DAY_SHIFT_TOLERANCE = 1

_STATUS_LABELS_ES = {
    "completed": "completada",
    "modified": "modificada",
    "missed": "no realizada",
    "pending": "pendiente",
    "under_intensity": "hecha, pero SIN la intensidad prescrita",
}


@dataclass
class SessionAdherence:
    date: str
    session_slot: str
    sport: str
    focus: str
    status: Status
    planned_minutes: float | None = None
    actual_minutes: float | None = None
    actual_distance_km: float | None = None
    actual_avg_hr: int | None = None
    activity_id: int | None = None
    activity_name: str | None = None
    # En que dia se entreno de verdad, cuando no fue el prescrito. None
    # significa que se hizo el dia que tocaba.
    done_on: str | None = None
    # Lo que la sesión IBA a ser, y con qué se comprobó si lo fue.
    #
    # `intensity_checked` distingue las dos formas de no estar marcada como
    # floja: "se comprobó y estuvo bien" y "no había con qué comprobarlo" --
    # una natación sin pulsómetro, una sesión de fuerza, un atleta cuyas zonas
    # no hemos podido leer del reloj. Dar las dos por buenas en silencio es
    # cómo un dato ausente se convierte en una afirmación.
    intensity_class: str | None = None
    actual_max_hr: int | None = None
    intensity_floor_hr: int | None = None
    intensity_checked: bool = False
    # El pico no llegó a Z4, pero el ritmo sí llegó al de la sesión.
    intensity_by_pace: bool = False
    # Se hizo con SU entreno del reloj (emparejada por workoutId, sin tolerancias):
    # los pasos que siguió fueron los prescritos. Ver `_desde_el_reloj`.
    desde_el_reloj: bool = False
    # Pendiente porque su reloj no ha subido nada desde ese día: no se sabe si la hizo.
    sin_sincronizar: bool = False

    @property
    def duration_ratio(self) -> float | None:
        if not self.planned_minutes or not self.actual_minutes:
            return None
        return self.actual_minutes / self.planned_minutes

    def describe(self) -> str:
        """One line, in the athlete's language, for the prompt and the UI."""
        label = _STATUS_LABELS_ES[self.status]
        head = f"{self.date} {self.sport} · {self.focus}: {label}"
        if self.status == "pending" and self.sin_sincronizar:
            return f"{head} (su reloj no ha sincronizado desde ese día: no se sabe todavía)"
        if self.status in ("missed", "pending"):
            return head

        parts = []
        if self.status == "under_intensity" and self.actual_max_hr and self.intensity_floor_hr:
            # El POR QUÉ, con los dos números. Sin ellos esto es una acusación:
            # el atleta que sí hizo su sesión tiene que poder ver de dónde sale
            # y contradecirla.
            parts.append(
                f"su pico fueron {self.actual_max_hr} ppm y esta sesión pedía llegar a "
                f"{self.intensity_floor_hr} ppm (Z{_ZONA_DE_CALIDAD}) al menos una vez"
            )
        if self.intensity_by_pace:
            # Se dice, para que nadie lea el pulso de esta sesión como un fallo.
            parts.append("su pulso no llegó a Z4, pero el ritmo sí llegó al de la sesión")
        if self.desde_el_reloj:
            # Con su entreno del reloj: siguió los pasos prescritos, que es lo
            # único que la adherencia por fecha y duración no puede afirmar.
            parts.append("con su entreno del reloj")
        if self.done_on:
            # Se dice. Un entrenador que ve "hecha" sin saber que fue otro dia
            # no puede notar que este atleta mueve sistematicamente el martes.
            parts.append(f"la hizo el {self.done_on}")
        if self.planned_minutes and self.actual_minutes:
            parts.append(f"{self.actual_minutes:.0f}' hechos de {self.planned_minutes:.0f}' previstos")
        elif self.actual_minutes:
            parts.append(f"{self.actual_minutes:.0f}'")
        if self.actual_distance_km:
            parts.append(f"{self.actual_distance_km:.1f} km")
        if self.actual_avg_hr:
            parts.append(f"{self.actual_avg_hr} ppm medias")
        return f"{head} ({', '.join(parts)})" if parts else head


@dataclass
class UnplannedActivity:
    date: str
    sport: str
    minutes: float | None
    name: str | None


@dataclass
class AdherenceReport:
    entries: list[SessionAdherence] = field(default_factory=list)
    unplanned: list[UnplannedActivity] = field(default_factory=list)
    plan_start_date: str | None = None

    @property
    def sin_sincronizar(self) -> list[SessionAdherence]:
        return [e for e in self.entries if e.sin_sincronizar]

    @property
    def evaluated(self) -> list[SessionAdherence]:
        """Sessions whose date has passed -- the only ones that can be judged."""
        return [e for e in self.entries if e.status != "pending"]

    @property
    def completed_count(self) -> int:
        return sum(1 for e in self.evaluated if e.status == "completed")

    @property
    def modified_count(self) -> int:
        return sum(1 for e in self.evaluated if e.status == "modified")

    @property
    def missed_count(self) -> int:
        return sum(1 for e in self.evaluated if e.status == "missed")

    @property
    def under_intensity_count(self) -> int:
        """Sesiones de calidad que se entrenaron en fácil."""
        return sum(1 for e in self.evaluated if e.status == "under_intensity")

    @property
    def adherence_pct(self) -> float | None:
        """Entrenada es entrenada; sólo la no realizada no cuenta.

        Una sesión hecha por debajo de su intensidad SÍ suma aquí: el atleta
        salió, hizo su tiempo y sostuvo el hábito, y este porcentaje mide
        exactamente eso. Lo que esa sesión no aporta es el ESTÍMULO DE
        CALIDAD, y eso se cuenta aparte -- en `quality_done` del expediente,
        que es el número con el que se decide si progresar la intensidad.
        Meter las dos cosas en un solo porcentaje es cómo se pierden las dos.
        """
        if not self.evaluated:
            return None
        trained = self.completed_count + self.modified_count + self.under_intensity_count
        return round(100.0 * trained / len(self.evaluated), 1)

    @property
    def executed_hours(self) -> float | None:
        """Horas REALMENTE entrenadas de las sesiones del plan.

        No existía, y el expediente la leía igual: `track_record.py` pedía
        `executed_hours` a un diccionario que nunca la trajo, así que cada
        semana quedaba anotada como "sin datos de ejecución" y la gráfica de
        prescrito-contra-hecho no podía aparecer jamás.

        Cuenta sólo lo que emparejó con una sesión prescrita. Lo entrenado
        fuera del plan se reporta aparte (`unplanned`) y sumarlo aquí haría
        que un atleta que se salta su plan y hace otra cosa apareciera
        cumpliéndolo.
        """
        minutos = [e.actual_minutes for e in self.entries if e.actual_minutes]
        return round(sum(minutos) / 60, 2) if minutos else None

    def to_state_dict(self) -> dict[str, Any]:
        return {
            "plan_start_date": self.plan_start_date,
            "adherence_pct": self.adherence_pct,
            "executed_hours": self.executed_hours,
            "completed": self.completed_count,
            "modified": self.modified_count,
            "missed": self.missed_count,
            "under_intensity": self.under_intensity_count,
            "evaluated": len(self.evaluated),
            "sessions": [
                {
                    "date": e.date, "session_slot": e.session_slot, "sport": e.sport,
                    "focus": e.focus, "status": e.status,
                    "planned_minutes": e.planned_minutes, "actual_minutes": e.actual_minutes,
                    "actual_distance_km": e.actual_distance_km, "actual_avg_hr": e.actual_avg_hr,
                    "activity_id": e.activity_id, "activity_name": e.activity_name,
                    "intensity_class": e.intensity_class,
                    "actual_max_hr": e.actual_max_hr,
                    "intensity_floor_hr": e.intensity_floor_hr,
                    "intensity_checked": e.intensity_checked,
                    # El día real, cuando no fue el prescrito. Viaja al
                    # expediente y a la UI: "hecha" sin decir que fue otro día
                    # esconde justo el patrón que un entrenador querría ver.
                    "done_on": e.done_on,
                    "sin_sincronizar": e.sin_sincronizar,
                }
                for e in self.entries
            ],
            "unplanned": [
                {"date": u.date, "sport": u.sport, "minutes": u.minutes, "name": u.name}
                for u in self.unplanned
            ],
        }


def _activity_date(activity) -> str | None:
    start = getattr(activity, "start_time", None)
    if not start or not isinstance(start, str):
        return None
    return start[:10]  # "2026-08-21 05:36:44" or ISO -- both start with the date


def _activity_minutes(activity) -> float | None:
    summary = getattr(activity, "summary", None)
    seconds = getattr(summary, "duration", None) if summary else None
    return round(seconds / 60.0, 1) if seconds else None


def deportes_que_valen(day) -> tuple[str, ...]:
    """Con que deporte(s) de Garmin puede casar esta sesion.

    No siempre es uno. Garmin no guarda "brick" en ningun sitio: un brick
    completado llega como un multideporte -- que `_canonical_sport` colapsa a
    "other", porque no es ni carrera ni bici -- o como las actividades sueltas
    de cada tramo. Buscando literalmente "brick" no se encuentra NUNCA, asi
    que cada brick que el atleta hiciera se contaba como no realizado y sus
    tramos aparecian ademas como entrenamiento fuera del plan. Eso entra tal
    cual en el prompt de la semana siguiente: el coach leeria que se salto el
    brick y le recortaria la progresion por algo que si hizo.

    El orden importa: primero el multideporte, que es UNA actividad con los
    dos tramos dentro, y solo despues los tramos sueltos.
    """
    if day.sport == "strength":
        # LAS DOS, y el orden no es casual. `_canonical_sport` ya reconoce la
        # fuerza como tal, asi que una sesion de fuerza de Garmin llega como
        # "strength". Pero "other" se queda: recoge todo lo que Garmin no
        # etiqueta como fuerza y el atleta si cuenta como tal -- una sesion de
        # gimnasio registrada como otra cosa, un tipo que no reconocemos.
        #
        # Quitar "other" de aqui al ensenarle fuerza al clasificador habria
        # marcado como NO REALIZADA toda sesion de fuerza que no llegue con esa
        # etiqueta exacta, y eso entra tal cual en el prompt de la semana
        # siguiente: el coach leeria que se salto la fuerza y le recortaria la
        # progresion por algo que si hizo. Un "no realizado" falso es peor que
        # un emparejamiento generoso.
        return ("strength", "other")
    if day.sport == "brick":
        return ("other", "bike", "run")
    if day.sport == "walk":
        # `walk` es un deporte que el esquema PRESCRIBE (WorkoutDay.sport lo
        # acepta y la UI lo pinta como "Caminata"), pero NADA canonicaliza a
        # "walk": `_canonical_sport` manda caminar y senderismo a "other" a
        # proposito, porque de que tabla de zonas les da Garmin no se puede
        # saber desde aqui. Las dos decisiones son correctas por separado y
        # juntas hacian que una caminata prescrita no pudiera emparejar con
        # ninguna actividad JAMAS: siempre "no realizada", hiciera lo que
        # hiciera el atleta.
        #
        # Mismo argumento que la fuerza dos lineas arriba: un deporte que esta
        # app prescribe tiene que saber reconocerlo cuando vuelve.
        return ("other",)
    return (day.sport,)


def _hay_actividad_de_su_duracion(by_day: dict, matched_ids: set, day: str,
                                  sport: str | tuple[str, ...], planned_minutes: float) -> bool:
    """Si ese día hay una actividad libre de ese deporte que dura lo prescrito (±25 %)."""
    for d in ((sport,) if isinstance(sport, str) else sport):
        for a in by_day.get((day, d), []):
            if getattr(a, "activity_id", None) in matched_ids:
                continue
            minutos = _activity_minutes(a) or 0
            if abs(minutos - planned_minutes) <= planned_minutes * _DURATION_TOLERANCE:
                return True
    return False


def _attach_activity(
    entry: SessionAdherence,
    by_day: dict,
    matched_ids: set,
    day: str,
    sport: str | tuple[str, ...],
    sumar_tramos: bool = False,
    zonas: Any | None = None,
    umbral_ritmo_s_km: float | None = None,
) -> bool:
    """Empareja la sesión con la actividad de ese día, si la hay.

    Devuelve si encontró una. Extraído para que las dos pasadas -- el día
    prescrito primero, los vecinos después -- compartan exactamente la misma
    lógica de elección y de lectura: una divergencia entre ellas produciría
    dos definiciones distintas de "completada" según qué día se entrenó.
    """
    deportes = (sport,) if isinstance(sport, str) else sport
    candidates, deporte_usado = [], None
    for d in deportes:
        candidates = [
            a for a in by_day.get((day, d), [])
            if getattr(a, "activity_id", None) not in matched_ids
        ]
        if candidates:
            deporte_usado = d
            break
    if not candidates:
        return False

    # La duración más cercana a lo prescrito, para que un doble sesión empareje
    # con criterio en vez de con la que llegó primero.
    if entry.planned_minutes:
        candidates.sort(key=lambda a: abs((_activity_minutes(a) or 0) - entry.planned_minutes))
    activity = candidates[0]
    matched_ids.add(getattr(activity, "activity_id", None))
    _leer_actividad(entry, activity)

    # Un brick es UNA sesion repartida en dos actividades cuando el atleta lo
    # graba por tramos. Quedarse con el primero convierte 60' de bici + 30' de
    # carrera en "hizo 60 de los 90 prescritos" -- una sesion clavada leida
    # como recortada, y el tramo que sobra contado ademas como entrenamiento
    # fuera del plan. Se suman, y los dos quedan reclamados.
    if sumar_tramos:
        for otro in deportes:
            if otro == deporte_usado:
                continue
            for a in by_day.get((day, otro), []):
                if getattr(a, "activity_id", None) in matched_ids:
                    continue
                matched_ids.add(getattr(a, "activity_id", None))
                entry.actual_minutes = round((entry.actual_minutes or 0) + (_activity_minutes(a) or 0), 1)
                res = getattr(a, "summary", None)
                extra_km = (getattr(res, "distance", None) or 0) / 1000.0 if res else 0
                if extra_km:
                    entry.actual_distance_km = round((entry.actual_distance_km or 0) + extra_km, 2)

    _dictaminar(entry, activity, zonas, umbral_ritmo_s_km)
    return True


def _leer_actividad(entry: SessionAdherence, activity: Any) -> None:
    """Lo que se entrenó: minutos, distancia, pulso medio. Compartido por las dos formas de emparejar."""
    summary = getattr(activity, "summary", None)
    entry.activity_id = getattr(activity, "activity_id", None)
    entry.activity_name = getattr(activity, "activity_name", None)
    entry.actual_minutes = _activity_minutes(activity)
    distance = getattr(summary, "distance", None) if summary else None
    entry.actual_distance_km = round(distance / 1000.0, 2) if distance else None
    entry.actual_avg_hr = getattr(summary, "average_hr", None) if summary else None


def _dictaminar(entry: SessionAdherence, activity: Any, zonas: Any | None, umbral_ritmo_s_km: float | None) -> None:
    """Completada o modificada por su duración, y si la calidad se entrenó en calidad."""
    summary = getattr(activity, "summary", None)
    entry.actual_max_hr = getattr(summary, "max_hr", None) if summary else None
    ratio = entry.duration_ratio
    entry.status = (
        "completed"
        if ratio is None or abs(ratio - 1.0) <= _DURATION_TOLERANCE
        else "modified"
    )
    _verificar_intensidad(entry, zonas, activity, umbral_ritmo_s_km)


def _llego_por_ritmo(entry: SessionAdherence, actividad: Any | None, umbral_ritmo_s_km: float | None) -> bool:
    """Si una carrera pasó en llano al menos 2' a la banda de su clase o más rápido."""
    banda = _BANDA_DE_LA_CLASE.get(entry.intensity_class or "")
    if entry.sport != "run" or not banda or not umbral_ritmo_s_km or actividad is None:
        return False
    borde_lento = float(umbral_ritmo_s_km) * _PACE_CLASS_MULTIPLIERS[banda][1]
    return segundos_entre(actividad, 0.0, borde_lento) >= _SEGUNDOS_A_RITMO_DE_CALIDAD


def _verificar_intensidad(
    entry: SessionAdherence,
    zonas: Any | None,
    actividad: Any | None = None,
    umbral_ritmo_s_km: float | None = None,
) -> None:
    """¿La sesión de calidad se entrenó en calidad?

    Sólo se pronuncia cuando la evidencia no admite discusión: la sesión era de
    las que EXISTEN por su intensidad, tenemos las zonas reales del atleta para
    ese deporte, tenemos el pico de pulsaciones de la actividad, y ese pico se
    quedó por debajo del suelo de Z4. Es decir: en toda la sesión no llegó ni
    una vez a la intensidad que la sesión pedía.

    Todo lo demás deja la sesión como estaba y `intensity_checked=False`. Una
    natación sin pulsómetro, una sesión de fuerza, un atleta al que todavía no
    le hemos podido leer las zonas del reloj: en ninguno de esos casos sabemos
    nada, y no saber no es lo mismo que saber que estuvo mal.

    No mira la MEDIA a propósito: el calentamiento, las recuperaciones y la
    vuelta a la calma hunden la media de una sesión de series bien hecha.
    """
    if (entry.intensity_class or "") not in _CLASES_DE_CALIDAD:
        return
    if zonas is None or not entry.actual_max_hr:
        return
    bounds = zonas.bounds(_ZONA_DE_CALIDAD, entry.sport)
    if not bounds or not bounds[0]:
        return

    entry.intensity_floor_hr = int(bounds[0])
    entry.intensity_checked = True
    if entry.actual_max_hr < entry.intensity_floor_hr:
        if _llego_por_ritmo(entry, actividad, umbral_ritmo_s_km):
            entry.intensity_by_pace = True
        else:
            entry.status = "under_intensity"


def _desde_el_reloj(activities: list | None, subidas: list[dict] | None,
                    plan: Any) -> dict[tuple[str, str], Any]:
    """Sesión (fecha, franja) -> la actividad que se hizo CON su entreno del reloj.

    Garmin guarda en cada actividad el `workoutId` del entreno programado con el
    que se hizo, y el registro de subidas dice a qué sesión corresponde cada
    `workoutId` que creamos. Con los dos, emparejar no es adivinar: el martes
    movido al viernes, el doble de 45' que se alargó a 70' y el rodaje que cayó
    en el día de otra sesión se leen exactos. Las tolerancias de fecha y duración
    de abajo quedan para lo que de verdad las necesita: el atleta que entrena la
    sesión sin abrir el entreno del reloj.
    """
    sesion_de = {}
    for e in subidas or []:
        if e.get("kind") or not e.get("workout_id"):
            continue  # el test de umbral no es una sesión del plan
        sesion_de[str(e["workout_id"])] = (str(e.get("date") or ""), str(e.get("session_slot") or "unica"))
    if not sesion_de:
        return {}
    del_plan = {(d.date, d.session_slot) for w in plan.weeks for d in w.days if not d.is_rest}
    salida: dict[tuple[str, str], Any] = {}
    for a in activities or []:
        clave = sesion_de.get(str(getattr(a, "workout_id", None) or ""))
        if clave in del_plan and clave not in salida:
            salida[clave] = a
    return salida


def primer_dia_sin_sincronizar(ultima_sincronizacion: str | None, zona: tzinfo | None = None) -> date | None:
    """El primer día cuyas actividades puede que su reloj todavía no haya subido: el de la última
    sincronización, en la hora del atleta (ese día no había terminado cuando subió).

    La hora de Garmin viene en UTC y el día de una sesión es el local del atleta. El worker pone la
    zona del atleta en su proceso (`AiService/worker.py`, `TZ`), así que sin `zona` se usa la hora
    local del proceso. Si se pasa un día de más como «no se sabe», se mide entero en la generación
    siguiente (`server.semana_a_completar`); lo contrario acusaría de no haber entrenado a quien sí
    lo hizo. None si no se sabe cuándo sincronizó: entonces no cambia nada.
    """
    if not ultima_sincronizacion:
        return None
    try:
        momento = datetime.fromisoformat(str(ultima_sincronizacion).replace("Z", "+00:00"))
    except ValueError:
        return None
    if momento.tzinfo is None:
        momento = momento.replace(tzinfo=UTC)
    return momento.astimezone(zona).date()


def build_adherence_report(
    plan: Any | None,
    activities: list | None,
    today: date | None = None,
    hr_zones: Any | None = None,
    umbral_ritmo_s_km: float | None = None,
    subidas: list[dict] | None = None,
    ultima_sincronizacion: str | None = None,
) -> AdherenceReport:
    """Match a WeeklyPlanPayload's sessions against real Garmin activities.

    `plan` is the plan the athlete was actually given (the previous
    generation), and `activities` their real recent activities. Sessions dated
    today or later are "pending" -- judging a session the athlete has not had
    the chance to do yet would be both wrong and discouraging.

    `subidas` is the upload ledger (services/garmin/upload_ledger.py): with it,
    a session done from its watch workout is matched exactly by `workoutId`
    before any date or duration tolerance is tried. See `_desde_el_reloj`.

    `ultima_sincronizacion` (José, 2026-10-19: «al generar semana si Connect no estaba
    sincronizado con el reloj, cuenta como que no se hicieron los entrenos»): una sesión sin
    actividad de un día que su reloj todavía no ha subido queda PENDIENTE, no «no realizada».
    Ver `primer_dia_sin_sincronizar`.
    """
    from services.garmin.data_extractor import DataExtractor

    report = AdherenceReport()
    if plan is None or not getattr(plan, "weeks", None):
        return report

    report.plan_start_date = getattr(plan, "start_date", None)
    today = today or date.today()
    sin_sincronizar_desde = primer_dia_sin_sincronizar(ultima_sincronizacion)

    # Index activities by (date, canonical sport). A day can hold several.
    by_day: dict[tuple[str, str], list] = {}
    for activity in activities or []:
        activity_date = _activity_date(activity)
        if not activity_date:
            continue
        sport = DataExtractor._canonical_sport(getattr(activity, "activity_type", None))
        by_day.setdefault((activity_date, sport), []).append(activity)

    matched_ids: set[int] = set()
    # Sesiones sin actividad su propio día, a la espera de la segunda pasada.
    pending_shift: list[tuple] = []

    # Primero lo exacto: lo que se hizo con el entreno del reloj. Se reclama
    # antes que nada para que ninguna tolerancia se lo pueda dar a otra sesión.
    exactas = _desde_el_reloj(activities, subidas, plan)
    for actividad in exactas.values():
        matched_ids.add(getattr(actividad, "activity_id", None))

    for week in plan.weeks:
        fechas_de_la_semana = []
        for d in week.days:
            try:
                fechas_de_la_semana.append(datetime.strptime(d.date, "%Y-%m-%d").date())
            except (ValueError, TypeError):
                continue
        semana = (min(fechas_de_la_semana), max(fechas_de_la_semana)) if fechas_de_la_semana else None
        for day in week.days:
            if day.is_rest:
                continue

            try:
                day_date = datetime.strptime(day.date, "%Y-%m-%d").date()
            except (ValueError, TypeError):
                continue

            entry = SessionAdherence(
                date=day.date, session_slot=day.session_slot, sport=day.sport,
                focus=day.focus or day.sport, status="pending",
                planned_minutes=day.total_duration_minutes,
                # Lo que la sesion IBA a ser. Sin esto no hay forma de saber
                # si la intensidad que se ejecuto era la que se pedia.
                intensity_class=getattr(day, "intensity_class", None),
            )

            if day_date >= today:
                report.entries.append(entry)
                continue

            # Strength sessions map onto Garmin's "other" bucket, which also
            # holds anything unrecognised -- accepted deliberately, since a
            # false "missed" is worse than a generous match.
            wanted_sport = deportes_que_valen(day)
            es_brick = day.sport == "brick"
            exacta = exactas.get((day.date, day.session_slot))
            if exacta is not None:
                # Hecha con SU entreno del reloj: sin tolerancias de día ni de
                # duración, y se dice que fue con el entreno.
                _leer_actividad(entry, exacta)
                _dictaminar(entry, exacta, hr_zones, umbral_ritmo_s_km)
                entry.desde_el_reloj = True
                if (_activity_date(exacta) or day.date) != day.date:
                    entry.done_on = _activity_date(exacta)
                report.entries.append(entry)
            elif _attach_activity(entry, by_day, matched_ids, day.date, wanted_sport, es_brick, hr_zones,
                                  umbral_ritmo_s_km):
                report.entries.append(entry)
            else:
                # Sin actividad ESE día. No se decide todavía: puede haberla a
                # un día de distancia, y buscarla ahora le robaría a la sesión
                # de al lado la actividad que sí era suya. Ver la segunda
                # pasada, más abajo.
                pending_shift.append((entry, day_date, wanted_sport, es_brick, semana))
                report.entries.append(entry)

    # --- segunda pasada: sesiones movidas de día -------------------------
    #
    # Sólo ahora, cuando cada sesión ya tiene lo que se entrenó SU día, se
    # miran los vecinos. Un atleta que se sabe el plan de memoria es
    # justamente el que mueve el martes al miércoles porque le llovió, y sin
    # esto esa sesión contaba dos veces mal: no realizada el martes, y
    # entrenamiento fuera del plan el miércoles.
    for entry, day_date, wanted_sport, es_brick, semana in pending_shift:
        for offset in range(1, _DAY_SHIFT_TOLERANCE + 1):
            for sign in (1, -1):
                neighbour = (day_date + timedelta(days=sign * offset)).isoformat()
                if _attach_activity(entry, by_day, matched_ids, neighbour, wanted_sport, es_brick, hr_zones,
                                    umbral_ritmo_s_km):
                    entry.done_on = neighbour
                    break
            if entry.done_on:
                break
        # --- tercera: movida a otro día DE SU MISMA SEMANA ---------------
        #
        # El atleta Fernando (2026-09-14): "hoy lunes no puedo entrenar, así
        # que muevo lo de hoy a mi día de descanso, el viernes". A cuatro días,
        # la sesión quedaba "no realizada" y el viernes como entrenamiento
        # fuera del plan, y ese expediente falso llegaba a la semana siguiente.
        #
        # A más de un día se distingue una sesión movida de otra distinta con
        # dos condiciones que no inventan nada: que caiga DENTRO de la semana
        # que la programó y que dure lo que la sesión prescrita (la misma
        # tolerancia de siempre). Una actividad ya reclamada por su propio día
        # nunca se roba, porque esta pasada va la última.
        if not entry.done_on and semana and entry.planned_minutes and not es_brick:
            inicio, fin = semana
            for offset in range(_DAY_SHIFT_TOLERANCE + 1, (fin - inicio).days + 1):
                for sign in (1, -1):
                    otro = day_date + timedelta(days=sign * offset)
                    if not (inicio <= otro <= fin) or otro >= today:
                        continue
                    if not _hay_actividad_de_su_duracion(by_day, matched_ids, otro.isoformat(), wanted_sport,
                                                         entry.planned_minutes):
                        continue
                    if _attach_activity(entry, by_day, matched_ids, otro.isoformat(), wanted_sport, es_brick,
                                        hr_zones, umbral_ritmo_s_km):
                        entry.done_on = otro.isoformat()
                        break
                if entry.done_on:
                    break
        if not entry.done_on:
            if sin_sincronizar_desde is not None and day_date >= sin_sincronizar_desde:
                # Su reloj no ha subido nada desde ese día: no se sabe si la hizo.
                entry.sin_sincronizar = True
            else:
                entry.status = "missed"

    # Anything the athlete trained that no session accounts for.
    for (activity_date, sport), day_activities in by_day.items():
        try:
            if datetime.strptime(activity_date, "%Y-%m-%d").date() >= today:
                continue
        except (ValueError, TypeError):
            continue
        if report.plan_start_date and activity_date < report.plan_start_date:
            continue
        for activity in day_activities:
            if getattr(activity, "activity_id", None) in matched_ids:
                continue
            report.unplanned.append(UnplannedActivity(
                date=activity_date, sport=sport,
                minutes=_activity_minutes(activity),
                name=getattr(activity, "activity_name", None),
            ))

    return report


def render_adherence_block(report: AdherenceReport) -> str:
    """The block injected into the weekly planner prompt.

    Framed as evidence, not as a scoreboard: the coach's job is to respond to
    what actually happened, and an athlete who keeps missing the same day
    needs the plan changed, not a lecture.
    """
    if not report.evaluated:
        return ""

    flojas = report.under_intensity_count
    lines = [
        "\n### Adherencia real — lo que el atleta HIZO con el plan anterior",
        f"De {len(report.evaluated)} sesiones que ya tocaban: {report.completed_count} completadas, "
        f"{report.modified_count} modificadas, {flojas} hechas sin la intensidad prescrita, "
        f"{report.missed_count} no realizadas ({report.adherence_pct:.0f}% de adherencia).",
    ]
    if flojas:
        lines.append(
            "**Una sesión «sin la intensidad prescrita» se entrenó pero no dio el estímulo**: era de "
            "calidad, su pico de pulsaciones no llegó ni una vez a Z4 y, en carrera, tampoco pasó 2' en llano "
            "a la banda de ritmo de su clase (el ritmo rescata una sesión cuyo pulso llega tarde; nunca "
            "acusa). Cuenta como entrenada para el "
            "volumen y NO cuenta como calidad hecha. Antes de progresar la intensidad, mira por qué: si "
            "se repite, la dosis dura que le estás poniendo no le está entrando, y subirla más es "
            "empujar contra algo que ya no responde."
        )

    for entry in report.evaluated:
        lines.append(f"- {entry.describe()}")
    sin_datos = report.sin_sincronizar
    if sin_datos:
        lines.append(
            f"{len(sin_datos)} sesión(es) más sin datos todavía: su reloj no ha sincronizado con Garmin "
            "desde ese día, así que NO se sabe si las hizo. No las cuentes como no realizadas ni como hechas: "
            + ", ".join(f"{e.date} {e.sport}" for e in sin_datos) + "."
        )

    if report.unplanned:
        lines.append("Ademas entreno por su cuenta, fuera del plan:")
        for extra in report.unplanned[:8]:
            duration = f" {extra.minutes:.0f}'" if extra.minutes else ""
            lines.append(f"- {extra.date} {extra.sport}{duration} ({extra.name or 'sin nombre'})")

    lines.append(
        "Usa esto como evidencia, no como reproche. Si el atleta falla sistematicamente el mismo dia de "
        "la semana, ese dia no le funciona: mueve la sesion en vez de volver a ponerla ahi. Si acorta "
        "siempre las sesiones largas, el problema es el tamano de la dosis, no su voluntad. Y si entrena "
        "de mas por su cuenta, tenlo en cuenta en la carga total antes de anadir mas volumen."
    )
    lines.append(
        "**Lo que esto NO comprueba**: que la ESTRUCTURA se ejecutara. Se empareja por fecha, deporte y "
        "duracion, y la intensidad solo se verifica en las sesiones de calidad y solo cuando hay "
        "pulsaciones y zonas reales del atleta (en carrera, tambien por el ritmo en llano, que solo puede "
        "rescatar la sesion). Que ponga «completada» significa que ese dia entreno ese "
        "deporte ese rato -- no que hiciera 5 series en vez de 4, ni que respetara los ritmos de cada "
        "paso. Si necesitas saberlo para una decision, dilo en `week_rationale` en vez de darlo por hecho."
    )
    return "\n".join(lines) + "\n"
