import json
from enum import Enum
from typing import Literal

from pydantic import BaseModel, ConfigDict, Field, field_validator, model_validator
from pydantic.json_schema import SkipJsonSchema

from .agent_outputs import Question

# Plausible bounds for an explicit target range, used to catch a unit mix-up
# before it reaches Garmin. They are deliberately generous -- the point is to
# reject "5.75" (minutes per km) or "2.9" (metres per second) written into a
# field that means SECONDS per km, not to second-guess a coach's judgement.
_PACE_SEC_PER_KM_BOUNDS = (100.0, 1200.0)  # 1:40/km (world record pace) .. 20:00/km
_HR_BPM_BOUNDS = (30.0, 230.0)
_POWER_WATTS_BOUNDS = (1.0, 2000.0)

_TARGET_BOUNDS = {
    "pace": (_PACE_SEC_PER_KM_BOUNDS, "segundos por kilómetro"),
    "hr_zone": (_HR_BPM_BOUNDS, "pulsaciones por minuto"),
    "power": (_POWER_WATTS_BOUNDS, "watts"),
}


# What KIND of session a day is. This exists so the deterministic rules can
# know what the coach INTENDED instead of guessing it from keywords in the
# session's prose -- keyword-sniffing classified a strides set, a walk/run
# block and an easy run whose purpose happened to contain the word "ritmo" all
# as "quality work that must carry a pace target", which is exactly how a rule
# meant to protect the athlete ends up narrowing what the coach may prescribe.
#
# It governs VALIDATION STRICTNESS ONLY. It never limits what a session can
# contain: the structure of the steps and the session's own description stay
# completely free. "other" is a deliberate escape hatch -- an unusual session
# that fits none of these should use it rather than be forced into a class
# that does not describe it, and modality enforcement then stands down.
SessionSlot = Literal["unica", "am", "pm", "s3", "s4"]

#: El orden de las claves. Fuente unica: la usan el validador, el renderizador
#: de la hoja impresa, el chat del coach y el constructor de Garmin.
SLOT_ORDER: tuple[str, ...] = ("unica", "am", "pm", "s3", "s4")


def slot_ordinal(slot: str | None) -> int:
    """1 para la primera sesion del dia, 2 para la segunda... 0 si es unica."""
    s = (slot or "unica").lower()
    return SLOT_ORDER.index(s) if s in SLOT_ORDER else 0


def slot_suffix(slot: str | None) -> str:
    """Lo que se le pega al NOMBRE del entreno en Garmin para distinguirlo.

    Decia " (AM)" y " (PM)", y era falso justo donde mas se nota: el atleta que
    corre Y nada el mismo viernes de 05:00 a 07:00 veia en su reloj una sesion
    llamada "(AM)" y otra "(PM)", las dos a las cinco de la manana. La segunda
    no es de la tarde: es la segunda. El ordinal dice lo unico que la clave
    significo siempre, y no se contradice con el horario real.
    """
    n = slot_ordinal(slot)
    return f" ({n}ª)" if n else ""
"""Cuál de las sesiones del día es esta.

Es una CLAVE de identidad, no una hora del reloj. Lo fue siempre -- el
validador ya asignaba "am" al primer bloque declarado y "pm" al segundo por
ORDEN, no por la hora -- pero los nombres decían otra cosa, y eso acabó
importando: un atleta que corre y nada el mismo miércoles a las 5:30 vio su
plan con una sola sesión, le preguntó al coach por qué, y el coach le contestó
con tres razones de entrenamiento inventadas. La razón real era que su perfil
declaraba UN bloque ese día, y un bloque valía por una sesión.

Ahora un bloque puede contener varias sesiones (ver `block_index`), así que
hacen falta más de dos claves. Los tres primeros valores se conservan tal cual
porque identifican sesiones en planes ya guardados y en el registro de
adherencia; `s3`/`s4` continúan la serie. La hora real que le corresponde al
atleta se muestra desde el bloque, nunca desde este campo.
"""

IntensityClass = Literal[
    "rest",
    "recovery",
    "easy",
    "long_run",
    # Pace (running) / power (bike) is the right modality for these three:
    # chasing heart rate on the first reps over-exerts the athlete before HR
    # has caught up, which is the defect this whole app was built to fix.
    "tempo_threshold",
    "vo2max",
    "repeticiones",
    # Short, sharp, by FEEL: strides, accelerations, hill sprints, drills.
    # Prescribing a pace band for a 15-second stride is meaningless, and
    # demanding one is how "6x20\" relajado y rapido" gets mangled into a
    # paced interval session.
    "neuromuscular",
    # Run/walk and other deliberately-easy interval structures -- common for
    # return-to-run and for athletes managing a joint. Structurally intervals,
    # but the whole point is that they stay EASY: forcing a quality pace band
    # on them turns base building into a hard session.
    "walk_run",
    # Aerobic work in a second sport (bike/swim) to add volume without impact.
    "cross_training",
    # v7, montaña. Tres estímulos que en asfalto no existen y que un plan de
    # trail sin ellos no es un plan de trail:
    #
    # - `hill_repeats`: series EN CUESTA, prescritas por tiempo de subida y
    #   esfuerzo (nunca por ritmo: el ritmo lo pone la pendiente). Desarrollan
    #   potencia aeróbica y fuerza específica.
    # - `downhill_technique`: bajada en terreno técnico. Lo que se entrena es
    #   la pisada, la vista y la tolerancia excéntrica del cuádriceps, que es
    #   lo que revienta a la gente en carrera. Es una sesión de HABILIDAD, y
    #   pedirle un ritmo objetivo la destruye.
    # - `hike_run`: caminar-correr en pendiente fuerte. Caminar rápido en un
    #   20% es más eficiente que trotarlo, y saber CUÁNDO cambiar es una
    #   destreza que se entrena. No es una versión descafeinada de correr.
    "hill_repeats",
    "downhill_technique",
    "hike_run",
    "strength_freeform",
    "race_day",
    "other",
]

# Classes where a pace (run) or power (bike) target is genuinely the correct
# modality, and its absence is a real defect rather than a coaching choice.
#
# Las tres clases de montaña (`hill_repeats`, `downhill_technique`,
# `hike_run`) están FUERA a propósito, y no por descuido: en una subida del
# 15% el ritmo de alguien en forma y el de alguien reventado son el mismo
# número, así que exigir min/km ahí es la misma clase de error que exigir
# pulso en unas series de pista -- el error con el que empezó este proyecto,
# visto desde el otro lado. Se prescriben por esfuerzo, tiempo de subida y
# desnivel. Ver TRAIL_GUIDANCE en weekly_planner_node.py.
PACE_TARGETED_CLASSES = frozenset({"tempo_threshold", "vo2max", "repeticiones"})

# Clases de montaña. Un plan de trail que no las contiene no es un plan de
# trail: es un plan de asfalto que se corre en el monte.
TRAIL_CLASSES = frozenset({"hill_repeats", "downhill_technique", "hike_run"})


# What the session IS, in a coach's vocabulary -- as opposed to `sport`, which
# is what Garmin's API can be told (v5 Track 5).
#
# The athlete's own framing: "a veces limitamos al sistema a acoplarse a como
# podria escribirse el entreno en Garmin, pero este no debe ser un limitante,
# este debe ser un tema de traduccion como ultimo paso". Before this existed,
# the only vocabulary in the plan was Garmin's -- run/bike/swim/strength/walk/
# brick/rest -- so a coach who wanted to prescribe plyometrics, rehab, prehab,
# mobility or a technique session had no field to say so and had to disguise it
# as "strength". The plan is designed in these terms; exporting to Garmin is a
# lossy translation that happens afterwards and never decides what may be
# prescribed.
Discipline = Literal[
    "carrera",
    # v7. Montaña es carrera, y a la vez no lo es: lo que la define no es la
    # distancia sino el desnivel y el terreno, y eso cambia la MODALIDAD con la
    # que se prescribe. En una subida del 15% el ritmo de alguien en forma y el
    # de alguien reventado son el mismo número, así que prescribir min/km en
    # montaña es la misma clase de error que prescribir por pulso unas series
    # de pista -- el error con el que empezó este proyecto, en su otra cara.
    # Se prescribe por esfuerzo, por tiempo de subida y por desnivel.
    "trail",
    "bici",
    "natacion",
    "fuerza",
    "pliometria",
    "rehab",
    "prehab",
    "movilidad",
    "tecnica",
    "brick",
    "test",
    "descanso",
    "otro",
]

# Disciplines Garmin has no shape that preserves. A plyometric or rehab session
# encoded as a generic "strength" workout arrives on the watch as a duration
# with no content -- worse than not sending it, because the athlete then
# believes the watch has the session. These are handed over as written
# instructions instead, and nothing is dropped silently.
#
# `tecnica` and `test` are deliberately NOT here: a technique swim exports as a
# swim with lap-button steps, and a 5K time trial is a run. Being unable to
# encode the drill NAMES is not the same as being unable to encode the session.
DISCIPLINES_WITHOUT_FAITHFUL_GARMIN_FORM = frozenset({
    "pliometria", "rehab", "prehab", "movilidad",
})

# `brick` salió de esa lista en v7. Garmin SÍ tiene una forma que lo preserva
# -- un entreno `multi_sport` con un tramo ordenado por deporte -- y dejó de
# ser una incógnita cuando el atleta creó uno en su cuenta y se leyó de vuelta
# (ver services/garmin/garmin_vocabulary.py y build_multisport_workout).
#
# Pero sólo se puede exportar si sus pasos DICEN a qué tramo pertenecen. Un
# brick cuyos pasos no lo dicen no es un brick que se pueda encodificar, y
# sigue yendo escrito -- que es lo que hacía antes, y es correcto.
def _brick_has_legs(steps) -> bool:
    deportes = {
        (getattr(paso, "leg_sport", None) or "").strip().lower()
        for paso in (steps or [])
    }
    return len(deportes - {""}) >= 2


def is_garmin_exportable(discipline: str | None, sport: str, is_rest: bool, steps=None,
                         strength_block=None) -> bool:
    """Whether this session survives the trip to a Garmin workout intact.

    Deliberately deterministic and computed in code, not asked of the model:
    what the session IS is a coaching judgement, but whether our exporter can
    carry it is a fact about our exporter.

    ## Dos reglas que se quedaron viejas, vistas subiendo una semana real

    El 2026-09-11 el atleta subió su semana y llegaron 8 de 10. Las dos que no:

    - **La carrera tras la bici del domingo, con `discipline="brick"`.** Esta
      función sólo exportaba un brick si traía tramos de multideporte
      (`leg_sport`). Pero desde la v1.1 un brick son DOS sesiones de un solo
      deporte y lo que las encadena va en el texto, así que una carrera
      etiquetada brick no iba a traer tramos nunca: se quedaba fuera siempre.
      Ahora sólo el `sport="brick"` de los planes viejos pasa por esa regla.
    - **La fuerza del jueves, con `discipline="prehab"`.** La lista de abajo
      dice que esas disciplinas "llegan al reloj como una duración sin
      contenido", y era verdad antes de `_strength_steps_from_block`
      (workout_builder.py), que convierte cada ejercicio en un bloque de series
      con su descanso. Una sesión de fuerza con su desglose SÍ tiene forma fiel,
      se llame prehab, rehab o pliometría.
    """
    if is_rest or sport == "rest":
        return False
    if sport == "brick":
        return _brick_has_legs(steps)
    if sport == "strength" and getattr(strength_block, "exercises", None):
        return True
    if (discipline or "") == "brick":
        return True  # una sesión de un solo deporte; el encadenado va en su texto
    return (discipline or "") not in DISCIPLINES_WITHOUT_FAITHFUL_GARMIN_FORM


class TargetType(str, Enum):
    NONE = "none"
    HR_ZONE = "hr_zone"
    PACE = "pace"
    POWER = "power"


class WorkoutStep(BaseModel):
    """One step of a workout, or a repeat block containing several steps.

    A step with `substeps` is a REPEAT BLOCK: it repeats everything inside it
    `repeat_count` times. This is the only way to express the shape almost
    every quality session actually has -- "4 x (3 minutes hard + 2 minutes
    easy)". Before repeat blocks existed, work and recovery could only be
    written as two sibling steps each marked "repeat 4 times", which Garmin
    faithfully turned into four hard reps back to back followed by four easy
    ones: an athlete would start the second rep the instant the first ended.
    """

    step_order: int = Field(..., description="1-indexed order within the workout (or within a repeat block)")
    step_type: Literal["warmup", "main", "interval", "recovery", "cooldown", "rest", "repeat"] = Field(
        ..., description="Use \"repeat\" together with `substeps` for a repeat block."
    )
    duration_type: Literal["time", "distance", "lap_button", "reps"] | None = Field(
        None, description="How this step ends. Leave empty on a repeat block (it ends after its iterations)."
    )
    duration_value: float | None = Field(
        None,
        description=(
            "Seconds (duration_type=time), meters (duration_type=distance), or rep count per "
            "set (duration_type=reps, strength exercises only). Empty on a repeat block."
        ),
    )
    repeat_count: int = Field(
        1,
        description=(
            "How many times to repeat. On a repeat block (with `substeps`) this is the number of "
            "rounds. On a plain strength step (duration_type=reps) this is the number of SETS. "
            "1 means no repetition."
        ),
    )
    substeps: list["WorkoutStep"] = Field(
        default_factory=list,
        description=(
            "The steps inside a repeat block, in order, repeated together `repeat_count` times. "
            "Use this for EVERY interval session so the recovery lives inside the repeat next to "
            "the work: e.g. repeat_count=4 with substeps [3' at threshold pace, 2' easy jog Z1] "
            "means 4x(3' hard + 2' easy). Each substep carries its own target, so repetitions may "
            "differ from one another (progressive or descending sets). Empty for a normal step."
        ),
    )
    # --- Natación: campos NATIVOS de Garmin (v7) --------------------------
    #
    # Hasta ahora el estilo y el material viajaban dentro de `description`
    # porque este repo tiene la regla de no adivinar los identificadores de
    # Garmin, que devuelve 400 ante cualquier valor fuera de su enum. Ya no se
    # adivinan: los valores válidos se leyeron de un entreno real del atleta
    # (ver services/garmin/garmin_vocabulary.py). Lo que no esté en esa lista
    # se omite al subir y la descripción sigue haciendo el trabajo, así que
    # equivocarse aquí nunca empeora lo que había.
    stroke: str | None = Field(
        None,
        description=(
            "SÓLO natación. Estilo del paso: 'free' (crol), 'backstroke' (espalda), "
            "'breaststroke' (pecho), 'fly' (mariposa), 'individual_medley' (estilos), "
            "'individual_medley_by_round' (un estilo por ronda), 'mixed' (alterna dentro del "
            "bloque), 'any_stroke' (el atleta elige). Vacío si no aplica."
        ),
    )
    equipment: str | None = Field(
        None,
        description=(
            "SÓLO natación. Material: 'pull_buoy', 'paddles' (palas), 'fins' (aletas), "
            "'kickboard' (tabla), 'snorkel'. Vacío si el paso se hace sin material."
        ),
    )
    drill: str | None = Field(
        None,
        description=(
            "SÓLO natación. 'kick' (sólo piernas), 'pull' (sólo brazos), 'drill' (técnica). "
            "Vacío en un paso de nado completo."
        ),
    )
    # --- Brick: a qué tramo pertenece este paso (v7) ----------------------
    # Sólo en días `sport="brick"`. Garmin guarda un multideporte como TRAMOS
    # ORDENADOS, cada uno con su propio deporte y sus propios pasos -- leído
    # del entreno 1679387475 de la cuenta del atleta y confirmado en la app
    # del reloj, que los lista como "tramos de la sesión de entrenamiento".
    # No hay pasos de transición: el tramo siguiente empieza donde acaba el
    # anterior, que es exactamente lo que un brick ES.
    leg_sport: str | None = Field(
        None,
        description=(
            "SÓLO en días brick: el deporte de este paso ('swim', 'bike', 'run'). Los pasos "
            "consecutivos del mismo deporte forman un tramo. Vacío en cualquier otro día."
        ),
    )

    # --- Fuerza: los rellena el CÓDIGO, no el modelo ----------------------
    # Se derivan de `StrengthBlock` al construir el entreno de Garmin
    # (workout_builder._strength_steps_from_block). Están aquí porque el paso
    # es lo que viaja al reloj, no porque haya que pedírselos a nadie.
    garmin_category: str | None = Field(
        None, description="Rellenado por el código desde strength_block. No lo escribas."
    )
    garmin_exercise: str | None = Field(
        None, description="Rellenado por el código desde strength_block. No lo escribas."
    )
    target_type: TargetType = TargetType.NONE
    target_zone: int | None = Field(None, description="Zone number 1-5, used with target_type hr_zone/pace/power")
    target_low: float | None = Field(
        None,
        description=(
            "Explicit range low, instead of a zone number. UNITS: SECONDS PER KILOMETRE for pace "
            "(e.g. 340 for 5:40/km -- never minutes, never m/s), bpm for hr_zone, watts for power."
        ),
    )
    target_high: float | None = Field(
        None,
        description=(
            "Explicit range high, same units as target_low. For pace this is the SLOWER end "
            "(larger seconds), e.g. target_low=340, target_high=355 for a 5:40-5:55/km range."
        ),
    )
    description: str = Field(..., description="Human-readable notation, e.g. \"4x(3' a 5:40-5:55/km, 2' rec)\"")
    exercise_name: str | None = Field(
        None,
        description=(
            "Strength exercise name, e.g. 'Sentadilla trasera' -- set together with duration_type="
            "reps for a strength set. Garmin only recognizes a small fixed set of its own exercise "
            "names, so this is for structured display (not sent to Garmin as-is); the human-readable "
            "name should also be in `description`."
        ),
    )

    @property
    def is_repeat_block(self) -> bool:
        return bool(self.substeps)

    def walk(self):
        """Yield this step and every step nested under it, depth-first."""
        yield self
        for substep in self.substeps:
            yield from substep.walk()

    @model_validator(mode="after")
    def _check_shape_and_units(self) -> "WorkoutStep":
        if self.substeps:
            if self.repeat_count < 2:
                raise ValueError(
                    "un bloque con `substeps` es una repetición y necesita repeat_count >= 2; "
                    "si sólo va una vez, pon los pasos directamente sin envolverlos"
                )
        elif self.step_type == "repeat":
            raise ValueError('step_type="repeat" requiere `substeps` con los pasos a repetir')
        elif self.duration_type is None:
            raise ValueError("un paso normal necesita `duration_type`")

        # Unit guard on explicit ranges. A pace written as "5.75" (minutes) or
        # "2.9" (m/s) is not a judgement call, it's a unit error, and it would
        # otherwise reach Garmin as a workout no human could run.
        bounds = _TARGET_BOUNDS.get(self.target_type.value)
        if bounds:
            (low_bound, high_bound), unit_es = bounds
            for field_name in ("target_low", "target_high"):
                value = getattr(self, field_name)
                if value is not None and not (low_bound <= value <= high_bound):
                    raise ValueError(
                        f"{field_name}={value} está fuera del rango razonable "
                        f"({low_bound:.0f}-{high_bound:.0f}) para un objetivo de "
                        f"{self.target_type.value}, que se expresa en {unit_es}"
                    )
        return self


def _sin_historia_en_el_esquema(schema: dict) -> None:
    """El docstring de la clase se queda en el código, no en el esquema del modelo.

    Pydantic convierte el docstring de una clase en la `description` de su
    JSON Schema, y ese esquema viaja entero en cada llamada del codificador.
    Medido en la semana de la atleta 3 del 2026-09-14: 4.327 de los 16.142
    caracteres del esquema eran la historia de desarrollo de tres clases
    (fallos de Gemini, costes de reparaciones) sentada al lado de las reglas
    de las series que el modelo sí tenía que leer. Las instrucciones para el
    modelo van en las `description` de los campos y en el prompt.
    """
    schema.pop("description", None)


class StrengthExercise(BaseModel):
    """Un ejercicio de la sesión de fuerza, escrito para hacerse.

    v7. Hasta ahora la fuerza era deliberadamente LIBRE: el plan daba el foco
    y los minutos, y el atleta elegía los ejercicios. Eso venía de v4 Track A,
    y su motivo era bueno -- el modelo fallaba 12 de 12 veces intentando meter
    series y repeticiones en el esquema de Garmin, y cada fallo costaba una
    reparación.

    Lo que cambió no es el modelo sino el destino: **esto NO va a Garmin**.
    La fuerza se escribe para leerla en el móvil, en el gimnasio, entre serie
    y serie. Liberada de tener que caber en el formato del reloj, la
    estructura deja de ser el problema que era y pasa a ser lo que un
    entrenador daría de verdad.

    `video_key` está reservado para la fase 2 (vídeos de referencia). Se deja
    escrito ahora para que el modelo empiece a emitir un identificador estable
    del ejercicio desde ya: rellenarlo después contra un catálogo es fácil,
    reconstruirlo a posteriori de la prosa no lo es.
    """

    model_config = ConfigDict(json_schema_extra=_sin_historia_en_el_esquema)

    name: str = Field(..., description="Nombre del ejercicio, en español. Ej: 'Peso muerto rumano unipodal'")
    sets: int = Field(..., description="Número de series")
    reps: str = Field(
        ...,
        description=(
            "Repeticiones por serie, como texto: '8-10', '12 por lado', '30 s'. "
            "Texto y no número porque un isométrico se mide en tiempo y un "
            "unilateral se cuenta por lado."
        ),
    )
    load: str = Field(
        "",
        description=(
            "Carga o intensidad: 'peso corporal', '12 kg por mancuerna', 'RPE 7', "
            "'banda media'. Si no lo sabes, di con qué criterio elegirla."
        ),
    )
    rest_seconds: int | None = Field(None, description="Descanso entre series, en segundos")
    cues: str = Field(
        "",
        description=(
            "Una frase de ejecución: qué vigilar para hacerlo bien o para no "
            "hacerse daño. Ej: 'columna neutra, la cadera va atrás, no abajo'."
        ),
    )
    targets: str = Field("", description="Qué trabaja. Ej: 'glúteo medio, isquiotibiales'")
    video_key: str = Field(
        "",
        description=(
            "Identificador estable del ejercicio en snake_case, para asociarle "
            "un vídeo más adelante. Ej: 'peso_muerto_rumano_unipodal'."
        ),
    )
    # v7. El identificador de GARMIN, que no es el de arriba y no debe
    # confundirse con él.
    #
    # Durante un tiempo hubo tres descripciones del mismo dato y las tres
    # decían cosas distintas: el prompt pedía `garmin_exercise`, que no
    # existía; el esquema sólo ofrecía `video_key`, descrito como una clave en
    # castellano para vídeos futuros; y el constructor leía `video_key` y lo
    # buscaba en el enum inglés de Garmin. El modelo obedece al ESQUEMA, así
    # que escribía 'sentadilla_bulgara', no coincidía con nada, y al reloj sólo
    # llegaba la categoría. Un prompt y un esquema que se contradicen los
    # resuelve el modelo a favor del esquema, siempre.
    garmin_exercise: str = Field(
        "",
        description=(
            "El identificador de Garmin del ejercicio, EXACTAMENTE como aparece en el "
            "catálogo que se te da (mayúsculas y guiones bajos, ej. 'BULGARIAN_SPLIT_SQUAT'). "
            "Es lo que hace que el reloj muestre el ejercicio con su nombre en vez de con "
            "la categoría genérica. Elígelo sólo si es realmente ese movimiento; si el que "
            "prescribes no está en el catálogo, déjalo vacío: nunca deformes la sesión para "
            "que encaje en la lista."
        ),
    )
    # v7. Lo que hace que la sesión de fuerza llegue al RELOJ con sus
    # ejercicios dentro, en vez de como un bloque de 45 minutos en blanco.
    # Garmin acepta la categoría siempre; el nombre exacto sólo si está en su
    # enum, y si no lo está lo descarta en silencio (medido: de catorce
    # nombres enviados, siete desaparecieron). Por eso aquí se pide la
    # CATEGORÍA, que es lo que nunca se pierde.
    garmin_category: str = Field(
        "",
        description=(
            "La familia del ejercicio, para que Garmin lo muestre con nombre en el reloj. "
            "Elige una: SQUAT, DEADLIFT, LUNGE, HIP_RAISE, CALF_RAISE, BENCH_PRESS, PUSH_UP, "
            "PULL_UP, ROW, SHOULDER_PRESS, CURL, TRICEPS_EXTENSION, PLANK, CARRY, SLED, CARDIO. "
            "Usa la que mejor describa el patrón de movimiento (una sentadilla búlgara es SQUAT, "
            "un puente de glúteo es HIP_RAISE, una plancha es PLANK). Vacío sólo si ninguna encaja."
        ),
    )
    # Lo que cambió esta semana respecto a su programa, en una etiqueta corta
    # ("−1 serie", "nuevo", "en vez de Aperturas") y su porqué. Lo escribe el
    # código del paso de fuerza, fuera del esquema del modelo; ver `StrengthBlock`.
    change: SkipJsonSchema[str] = ""
    change_reason: SkipJsonSchema[str] = ""
    # El id del ejercicio en SU programa confirmado, puesto por el código. Es lo que
    # sigue al MISMO ejercicio semana a semana en su registro aunque cambie de
    # nombre; hasta el 2026-09-23 no existía y el registro guardaba el id vacío.
    # Vacío en un ejercicio sustituido o añadido: ése es otro ejercicio.
    id: SkipJsonSchema[str] = ""


class StrengthGroup(BaseModel):
    """Ejercicios que se hacen SEGUIDOS, ronda a ronda: una superserie o un circuito.

    Beta 2.1. Fernando (2026-09-17) hace su rutina Upper/Lower con dos o tres
    ejercicios "en simultáneo" y 2' de descanso entre rondas, y el plan no tenía
    dónde escribirlo: en las semanas archivadas de José y Jessie la decisión
    escribía "Bloque A (2 rondas)" o "Circuito x3" y el codificador lo aplanaba
    en series sueltas, porque `exercises` era una lista sin grupos.

    Las series van SÓLO en cada ejercicio, para que un grupo no pueda decir una
    cosa y sus ejercicios otra. Un grupo de un ejercicio es una serie normal.
    """

    id: str = ""
    label: str = ""
    rest_between_rounds_s: int | None = None
    rest_between_exercises_s: int | None = None
    exercises: list[StrengthExercise] = Field(default_factory=list)


class StrengthBlock(BaseModel):
    """La sesión de fuerza completa: calentamiento, trabajo y vuelta a la calma."""

    warmup: str = Field("", description="Calentamiento, en una o dos frases")
    exercises: list[StrengthExercise] = Field(
        default_factory=list,
        description="Los ejercicios, en el orden en que se hacen",
    )
    # Los grupos los escribe el CÓDIGO (el paso de fuerza de la Beta 2.1), no el
    # codificador: `SkipJsonSchema` los deja fuera del esquema que viaja al
    # modelo, así que el prompt del codificador y el de la reparación no cambian
    # ni un carácter. Cuando hay grupos, `exercises` se rellena desde ellos y
    # todo lector que ya existía (reloj, chat, hoja, plan anterior) sigue
    # funcionando. Un plan guardado sin grupos se lee como un grupo por
    # ejercicio (`grupos_de_fuerza`).
    groups: SkipJsonSchema[list[StrengthGroup]] = Field(default_factory=list)
    # De qué sesión del programa del atleta salió esta (Beta 2.1, S5). También lo
    # escribe el código y también queda fuera del esquema del modelo: sirve para
    # continuar la rotación donde la dejó la semana anterior, en vez de repetir
    # siempre las primeras sesiones cuando su tabla da menos días que sesiones
    # tiene su rutina. Vacío en todo lo que no venga de una rutina confirmada.
    routine_session_id: SkipJsonSchema[str] = ""
    # Lo que el atleta ve de un vistazo en su día, escrito por el CÓDIGO (fuera del
    # esquema del modelo). Hasta el 2026-09-23 la dosis, el motivo de cada cambio y
    # el aviso de colocación iban juntos en `notes`: un párrafo de ~20 líneas en el
    # móvil que el responsable describió como "texto pesado que nadie lee". Ahora
    # cada cosa va en su sitio y la pantalla enseña una etiqueta, con el porqué a un
    # toque. Un plan guardado antes no los tiene y se ve como se veía.
    session_name: SkipJsonSchema[str] = ""
    dose: SkipJsonSchema[str] = ""
    dose_reason: SkipJsonSchema[str] = ""
    placement_note: SkipJsonSchema[str] = ""
    skipped: SkipJsonSchema[list[dict]] = Field(default_factory=list)
    cooldown: str = Field("", description="Vuelta a la calma o movilidad final, en una frase")
    notes: str = Field(
        "",
        description=(
            "Ajustes para este atleta en concreto: qué sustituir si algo le "
            "molesta, qué material hace falta. Vacío si no hay nada que decir."
        ),
    )

    @model_validator(mode="after")
    def _ejercicios_desde_los_grupos(self):
        """Con grupos, la lista plana es SU aplanado, en orden: una sola verdad."""
        if self.groups:
            self.exercises = [ejercicio for grupo in self.groups for ejercicio in grupo.exercises]
        return self


def _letra_de_grupo(indice: int) -> str:
    """A, B, ... Z, AA, AB: la etiqueta de un grupo por su posición."""
    letras = ""
    indice += 1
    while indice:
        indice, resto = divmod(indice - 1, 26)
        letras = chr(65 + resto) + letras
    return letras


def grupos_de_fuerza(bloque) -> list[StrengthGroup]:
    """La sesión de fuerza como grupos, venga como venga.

    Con `groups`, ésos (con su etiqueta, A, B..., si no la traen). Sin ellos —
    todo plan guardado antes de la Beta 2.1 —, un grupo por ejercicio con el
    descanso entre series de ese ejercicio: la misma sesión, leída igual.
    """
    if bloque is None:
        return []
    grupos = list(getattr(bloque, "groups", None) or [])
    if grupos:
        return [g if g.label else g.model_copy(update={"label": _letra_de_grupo(i)}) for i, g in enumerate(grupos)]
    return [
        StrengthGroup(label=_letra_de_grupo(i), rest_between_rounds_s=getattr(e, "rest_seconds", None), exercises=[e])
        for i, e in enumerate(getattr(bloque, "exercises", None) or [])
    ]


class WorkoutDay(BaseModel):
    date: str = Field(..., description="YYYY-MM-DD")
    day_name_es: str
    is_rest: bool
    sport: Literal["run", "bike", "swim", "strength", "walk", "brick", "rest"]
    focus: str = Field(..., description="1-2 words, e.g. 'VO2max'")
    purpose: str = Field(..., description="One short sentence")
    adaptation: str = Field(..., description="\"If tired: ...\" guidance")
    steps: list[WorkoutStep] = Field(default_factory=list)
    # v7: la sesión de fuerza, desglosada. Va aparte de `steps` a propósito:
    # `steps` existe para viajar a Garmin y esto NO viaja -- se lee en el
    # móvil, en el gimnasio. Mezclarlos habría obligado a torcer el esquema
    # del reloj para que aceptara series y repeticiones, que es exactamente
    # lo que v4 Track A dejó de intentar tras fallar 12 de 12 veces.
    strength_block: StrengthBlock | None = Field(
        None,
        description=(
            "SOLO en días de sport='strength': el desglose de la sesión "
            "(calentamiento, ejercicios con series/repeticiones/carga/descanso, "
            "vuelta a la calma). En cualquier otro deporte va vacío."
        ),
    )
    total_duration_minutes: float | None = None
    # Carried through from the decision layer's DayIntent so the deterministic
    # rules can read what the coach INTENDED rather than infer it from prose.
    # Optional: plans stored before this existed simply have None, and the
    # validator falls back to its old keyword heuristic for those.
    intensity_class: IntensityClass | None = Field(
        None,
        description="What kind of session this is (copied from the training decision, not re-derived).",
    )
    # Copied from the decision layer's DayIntent, never re-derived (same
    # contract as intensity_class above).
    discipline: Discipline | None = Field(
        None, description="What the session is in coaching terms; `sport` is only how it exports."
    )
    garmin_exportable: bool = Field(
        True,
        description=(
            "False when Garmin has no shape that preserves this session. Computed in code from the "
            "discipline (is_garmin_exportable), never asked of the model."
        ),
    )
    manual_instructions: str = Field(
        "",
        description=(
            "For a session the watch cannot hold: the whole session written out for the athlete to run "
            "from their phone or a sheet of paper. Never a placeholder -- if this is set, it is the ONLY "
            "form of the session the athlete will ever see."
        ),
    )
    session_slot: SessionSlot = Field(
        "unica",
        description=(
            "Identificador de ESTA sesión dentro del día. 'unica' cuando el día tiene una sola; "
            "'am', 'pm', 's3', 's4' en orden cronológico cuando tiene varias. Es una clave, no una "
            "hora: la hora sale del bloque al que la sesión pertenece (block_index)."
        ),
    )
    block_index: int | None = Field(
        None,
        description=(
            "A cuál de los bloques de disponibilidad declarados de ese día pertenece la sesión, "
            "empezando en 0. UN BLOQUE PUEDE CONTENER VARIAS SESIONES si las horas alcanzan: un "
            "atleta con un bloque de 05:30 a 07:00 puede correr 45' y nadar 40' dentro de él, y "
            "eso son dos sesiones con el mismo block_index. Vacío en días de descanso o cuando el "
            "atleta declaró su disponibilidad con el formato antiguo de horas sueltas."
        ),
    )
    # Lo que el ATLETA cambió desde la app (Beta 3.0, F6-F7): `moved_from` (la fecha que le
    # dio el coach), `by`. Lo escribe el código (services/ai/edicion_de_sesion.py), nunca un
    # modelo: `SkipJsonSchema` lo deja fuera de todo esquema que viaja a la IA.
    edited: SkipJsonSchema[dict | None] = None


class WeekBlock(BaseModel):
    week_number: int = Field(..., description="1-4")
    days: list[WorkoutDay]
    # Copied verbatim from the decision layer's WeekIntentContent by
    # weekly_planner_node -- NOT something the formatting call retypes. The
    # reasoning is captured where the reasoning actually happened.
    week_rationale: str = Field(
        "",
        description="Why this week looks the way it does, in the athlete's language.",
    )
    # Lo que el atleta le contesto al coach AL GENERAR ESTA SEMANA.
    #
    # Las del macrociclo viven en su informe; las de la semana no vivian en
    # ningun sitio, asi que la generacion siguiente volvia a preguntar lo
    # mismo -- medido el 2026-09-11: las tres preguntas de la semana 2 eran las
    # tres de la semana 1, veinte minutos despues. Viajan con la semana, y se
    # van con ella cuando se borra. No las escribe el modelo.
    #
    # Fuera del JSON Schema a propósito. Lo rellena el código, y un
    # `list[dict[str, str]]` es un objeto con `additionalProperties` que el modo
    # estricto de OpenAI rechaza con un 400 ("Extra required key
    # 'coach_answers'"). Ese 400 tumbaba TODAS las reparaciones tier-2 de la
    # semana, que corren en gpt-5 con este esquema, sin que nada lo dijera:
    # semana del atleta 2 del 2026-09-13, dos intentos, cero reparaciones.
    # Verificado contra la API real: con SkipJsonSchema la llamada pasa.
    coach_answers: SkipJsonSchema[list[dict[str, str]]] = Field(
        default_factory=list,
        description="Preguntas del coach y respuestas del atleta en esta generacion. Lo rellena el codigo.",
    )
    # Si la semana es alta (o muy alta) contra el historial del propio atleta, lo que él
    # ve en su semana: por qué, con los números, y si es por lo que pidió. Lo escribe el
    # código (services/ai/carga_segura.py), nunca el modelo. "" en una semana normal.
    load_warning: SkipJsonSchema[str] = ""


class FormattedWorkoutStep(BaseModel):
    """A step as the Workout Formatter must write it: FLAT, and always runnable.

    ## What the model kept getting wrong, three times over

    Against one real archived prompt, gemini-3.5-flash failed the same session
    (a 5x400m VO2max set) in three different ways as each was closed off:

    1. It omitted `steps` entirely, on all nine days -- because
       `WorkoutDay.steps` had a default and so was not `required`.
    2. Given `steps` as required, it wrote `{"step_type": "repeat",
       "repeat_count": 5, "description": "5x(400m a ritmo VO2max, 2' rec)"}`
       with no `substeps` -- and kept writing it with the validator's own error
       handed back to it.
    3. Given no `"repeat"` option, it wrote the same flat step with no
       `duration_type` at all.

    The pattern is the same every time, and it is not stupidity: **a coach
    writes a repetition as ONE line.** "5x400m con 2' de recuperación" is one
    thought. The nested `substeps` tree is a fact about Garmin's file format,
    not about coaching, and every round of fighting it cost a tier-2 repair
    ($0.1093 of a $0.3319 week) that rebuilt the entire plan.

    ## So this step is flat, and code builds the tree

    The model writes the line a coach would say: the work step, how many times
    it repeats, and how long the recovery inside each repetition is.
    `expand_to_workout_steps()` turns that into the nested `WorkoutStep`
    structure the builder, the validator and the templates all expect.

    Same principle as the decision/formatter split (v4) and as "Garmin is a
    translation step, not a design limit" (v5), pushed one level further down:
    **the model expresses intent, code does the encoding.** An invalid state
    that cannot be written needs no rule, no retry and no repair.

    `duration_type` is required here for the same reason `steps` is: a step
    that cannot be executed is not a step. In the base `WorkoutStep` it stays
    optional, because a repeat block legitimately has none -- but the model
    never writes one of those any more.

    The one shape this cannot express is a repeat whose repetitions differ
    from one another (a descending set). That was already handled by writing
    them as separate flat steps, which a flat list does natively.
    """

    model_config = ConfigDict(json_schema_extra=_sin_historia_en_el_esquema)

    step_order: int = Field(..., description="Orden dentro del entrenamiento, empezando en 1")
    step_type: Literal["warmup", "main", "interval", "recovery", "cooldown", "rest"] = Field(
        ..., description="Qué es este paso.",
    )
    duration_type: Literal["time", "distance", "lap_button", "reps"] = Field(
        ..., description="Cómo termina este paso. Obligatorio: todo paso tiene que poder ejecutarse.",
    )
    duration_value: float | None = Field(
        None,
        description=(
            "Segundos (duration_type=time), metros (duration_type=distance), o repeticiones por serie "
            "(duration_type=reps, sólo fuerza)."
        ),
    )
    repeat_count: int = Field(
        1, description="Cuántas veces se repite este paso. 1 = no se repite.",
    )
    target_type: TargetType = TargetType.NONE
    target_zone: int | None = Field(None, description="Zona 1-5, con target_type hr_zone")
    target_low: float | None = Field(
        None,
        description=(
            "Extremo rápido de un rango explícito. UNIDADES: SEGUNDOS POR KILÓMETRO para ritmo "
            "(340 = 5:40/km — nunca minutos, nunca m/s), ppm para FC, watts para potencia."
        ),
    )
    target_high: float | None = Field(
        None, description="Extremo lento del rango, mismas unidades que target_low.",
    )
    description: str = Field(..., description="Notación compacta, p. ej. \"5x(3' a 5:40-5:55/km, 2' rec)\"")
    exercise_name: str | None = Field(None, description="Nombre del ejercicio de fuerza, si aplica.")
    # --- Natación: los campos nativos de Garmin ---------------------------
    #
    # Estaban en `WorkoutStep` (el árbol interno) y NO aquí, que es el esquema
    # que el modelo escribe de verdad. Así que el prompt le pedía el estilo y
    # el material y su esquema de salida no tenía dónde ponerlos: dos modelos
    # distintos los omitieron los dos, y se leyó como que el codificador
    # ignoraba las instrucciones.
    #
    # Es exactamente lo que este archivo lleva documentando desde v6: **una
    # restricción que el prompt pide y el esquema no admite la resuelve el
    # modelo a favor del esquema, siempre.**
    stroke: str | None = Field(
        None,
        description=(
            "SÓLO natación. Estilo: 'free' (crol), 'backstroke' (espalda), 'breaststroke' (pecho), "
            "'fly' (mariposa), 'individual_medley' (estilos), 'mixed' (alterna dentro del bloque), "
            "'any_stroke' (elige el atleta). Vacío si no aplica."
        ),
    )
    equipment: str | None = Field(
        None,
        description=(
            "SÓLO natación. Material: 'pull_buoy', 'paddles', 'fins', 'kickboard', 'snorkel'. "
            "Vacío si el paso se hace sin material."
        ),
    )
    drill: str | None = Field(
        None,
        description=(
            "SÓLO natación. 'kick' (sólo piernas), 'pull' (sólo brazos), 'drill' (técnica). "
            "Vacío en un paso de nado completo."
        ),
    )
    leg_sport: str | None = Field(
        None,
        description=(
            "SÓLO en un día brick: a qué tramo pertenece este paso — 'swim', 'bike' o 'run'. "
            "Los pasos seguidos del mismo deporte forman un tramo, y el orden es el orden en que "
            "se hacen. Vacío en cualquier otro día."
        ),
    )

    # No `substeps`. See the class docstring: nesting is what the model kept
    # failing to write, and it is a fact about Garmin's format, not about
    # coaching. Code builds it.
    #
    # La recuperación con SU unidad, igual que el trabajo. Hasta el 2026-09-15
    # era `recovery_seconds` y nada más, así que "6x400 m con 200 m de trote"
    # no se podía escribir: la semana de la atleta 3 del 2026-09-14 llegó al
    # validador con `recovery_seconds=200` y `recovery_description="200m trote
    # muy suave Z1"` -- el codificador sabía que eran metros y los metió en el
    # único campo que había --, y la del atleta 2 con "25 m suave" nadando
    # como 25 s. Una restricción que el esquema no admite la resuelve el modelo
    # a favor del esquema, siempre.
    recovery_duration_type: Literal["time", "distance", "lap_button"] | None = Field(
        None,
        description=(
            "Sólo con `repeat_count` >= 2: cómo termina la recuperación DENTRO de cada repetición, en la "
            "unidad en que está escrita. «2' de trote» = 'time'; «200 m de trote» o «25 m suave» = "
            "'distance'; «hasta que recuperes» = 'lap_button'. Sin recuperación, las repeticiones salen "
            "pegadas una detrás de otra."
        ),
    )
    recovery_duration_value: float | None = Field(
        None,
        description=(
            "Segundos si recovery_duration_type='time' (2' = 120), METROS si es 'distance' (200 m = 200). "
            "Vacío con 'lap_button'. Nunca conviertas metros en segundos: el reloj corta donde le digas."
        ),
    )
    recovery_target_zone: int | None = Field(
        None, description="Zona de FC (1-5) de esa recuperación. Por defecto Z1.",
    )
    recovery_description: str = Field(
        "", description="Cómo se hace esa recuperación, p. ej. \"2' trote suave Z1\".",
    )

    @model_validator(mode="before")
    @classmethod
    def _recuperacion_en_segundos_de_antes(cls, datos):
        """`recovery_seconds` se sigue leyendo: es `time` con ese valor.

        No está en el esquema -- el modelo ya no lo ve --, pero las pruebas y
        cualquier réplica de un prompt archivado antes del 2026-09-15 lo traen.
        """
        if isinstance(datos, dict) and "recovery_seconds" in datos:
            datos = dict(datos)
            segundos = datos.pop("recovery_seconds")
            if segundos and not datos.get("recovery_duration_type"):
                datos["recovery_duration_type"] = "time"
                datos["recovery_duration_value"] = segundos
        return datos

    def _con_recuperacion(self) -> bool:
        if self.repeat_count < 2 or not self.recovery_duration_type:
            return False
        return self.recovery_duration_type == "lap_button" or bool(self.recovery_duration_value)

    def _work_description(self) -> str:
        """El trabajo de UNA repetición, sacado de la línea del entrenador.

        El coach escribe la serie entera en una línea -- "5x(3min a
        5:12-5:20/km, 2min rec)" -- y esa línea es la correcta para el bloque
        de repetición. Ponerla también en el paso hijo, que es lo que se hacía,
        deja al atleta leyendo "5x(3min...)" en el reloj mientras corre la
        repetición 3 de 5, y hace que el plan impreso muestre la serie dos
        veces seguidas como si fueran diez.

        Se quita el envoltorio "Nx(...)" y la cláusula de recuperación, que ya
        vive en su propio paso. Si la línea no tiene esa forma se devuelve tal
        cual: perder el matiz que escribió el entrenador ("progresivos rápido
        y suelto") sería peor que repetir la cabecera.

        **También sin paréntesis** (auditoría del 2026-09-24): en natación la
        línea se escribe "8x50m técnica con 20\" pausa" y "6x200m a
        1:43-1:47/100m, 30\" pausa", y en carrera la recuperación se llama
        "3' trote". Nada de eso se quitaba, así que la semana del atleta 2 del
        2026-10-07 enseñaba "8× · 8x50m técnica con 20\" pausa", que se lee
        como 64 × 50 (el reloj sí recibía 8 × 50 m).
        """
        import re

        texto = (self.description or "").strip()
        envoltorio = re.match(r"^\s*\d+\s*[x×]\s*\((.+)\)\s*$", texto, re.DOTALL)
        sin_parentesis = re.match(r"^\s*\d+\s*[x×]\s*(?=\d)(.+)$", texto, re.DOTALL)
        if envoltorio:
            dentro = envoltorio.group(1).strip()
        elif sin_parentesis:
            dentro = sin_parentesis.group(1).strip()
        else:
            return texto
        recuperacion = r"(?:rec\w*|pausa|descanso|trote|camina\w*|parado)"
        # La recuperación va detrás de una coma o un "+" ...
        sin_recuperacion = re.sub(rf"\s*[,+]\s*[^,+]*\b{recuperacion}\b[^,+]*$", "", dentro, flags=re.IGNORECASE)
        # ... o detrás de "con": "50m técnica con 20\" pausa".
        sin_recuperacion = re.sub(rf"\s+con\s+[^,+]*\b{recuperacion}\b[^,+]*$", "", sin_recuperacion, flags=re.IGNORECASE)
        # ... o es la última cláusula y empieza por la duración EXACTA de la recuperación,
        # aunque no la llame así: "3x(12' en Z3, 6' en Z2)" con 360 s de recuperación
        # enseñaba "12' en Z3, 6' en Z2" en el paso de trabajo y "6' en Z2" debajo
        # (semana del atleta 2 del 2026-10-11, vista por el responsable el 2026-09-24).
        for forma in self._formas_de_la_recuperacion():
            partido = re.match(rf"^(.*?\S)\s*(?:[,+]|\bcon\b)\s*{re.escape(forma)}(?![\d.,]).*$", sin_recuperacion,
                               re.DOTALL | re.IGNORECASE)
            if partido:
                sin_recuperacion = partido.group(1)
                break
        return (sin_recuperacion or dentro).strip()

    def _formas_de_la_recuperacion(self) -> list[str]:
        """Cómo se escribe la recuperación de esta serie: 360 s -> "6'", "6 min"; 200 m -> "200 m"."""
        valor = self.recovery_duration_value
        if not valor or self.recovery_duration_type not in ("time", "distance"):
            return []
        v = int(round(float(valor)))
        if self.recovery_duration_type == "distance":
            return [f"{v} m", f"{v}m"]
        formas = [f'{v}"', f"{v}″", f"{v} s", f"{v}s", f"{v} seg"]
        if v % 60 == 0:
            m = v // 60
            formas = [f"{m}'", f"{m}’", f"{m}′", f"{m} min", f"{m}min"] + formas
        return formas

    def expand(self) -> WorkoutStep:
        """Turn one coach's line into the nested step the rest of the app expects.

        `repeat_count=5` on a 400m step with a 200 m recovery becomes a repeat
        block holding [400m, 200 m recovery] -- the shape the Garmin builder,
        every validator rule and both templates already read. Without a
        declared recovery it stays a plain repeated step, which is what the
        builder already did with `repeat_count > 1` anyway.
        """
        con_recuperacion = self._con_recuperacion()
        work = WorkoutStep(
            step_order=1 if con_recuperacion else self.step_order,
            step_type=self.step_type,
            duration_type=self.duration_type,
            duration_value=self.duration_value,
            repeat_count=1 if con_recuperacion else self.repeat_count,
            target_type=self.target_type,
            target_zone=self.target_zone,
            target_low=self.target_low,
            target_high=self.target_high,
            # Sólo el trabajo. La línea completa se queda en el bloque de
            # repetición, que es donde significa algo -- ver _work_description.
            description=self._work_description() if con_recuperacion else self.description,
            exercise_name=self.exercise_name,
            leg_sport=self.leg_sport,
            # El trabajo lleva el estilo y el material; la recuperación en la
            # pared no nada de ninguna forma.
            stroke=self.stroke,
            equipment=self.equipment,
            drill=self.drill,
        )
        if not con_recuperacion:
            return work

        tipo = self.recovery_duration_type
        valor = None if tipo == "lap_button" else float(self.recovery_duration_value)
        por_defecto = {
            "time": lambda: f"{int(valor) // 60}' de recuperación" if valor >= 60 else f"{int(valor)}\" de recuperación",
            "distance": lambda: f"{int(valor)} m de recuperación",
            "lap_button": lambda: "Recupera y pulsa vuelta cuando estés listo",
        }[tipo]
        recovery = WorkoutStep(
            step_order=2,
            step_type="recovery",
            duration_type=tipo,
            leg_sport=self.leg_sport,
            duration_value=valor,
            target_type=TargetType.HR_ZONE,
            target_zone=self.recovery_target_zone or 1,
            description=self.recovery_description or por_defecto(),
        )
        return WorkoutStep(
            step_order=self.step_order,
            step_type="repeat",
            repeat_count=self.repeat_count,
            substeps=[work, recovery],
            description=self.description,
            # El bloque de repetición también pertenece a su tramo: sin esto,
            # una serie dentro de un brick no sabría en qué deporte va.
            leg_sport=self.leg_sport,
        )


def expand_formatted_steps(steps: list["FormattedWorkoutStep"]) -> list[WorkoutStep]:
    """The flat list a coach wrote, as the nested tree Garmin needs.

    Un paso de 0 m o 0 s no es un paso: Haiku convirtió en uno la línea "Total 2.500 m" de
    una natación (réplica del 2026-09-24, semana del atleta 2 del 2026-09-30), y en el reloj
    habría sido un paso vacío al final. Se descarta aquí, que es donde el código construye
    lo que viaja a Garmin; el botón de vuelta sí es un paso aunque no tenga valor.
    """
    steps = [s for s in steps if s.duration_type in ("lap_button", "reps") or (s.duration_value or 0) > 0]
    expanded = [step.expand() for step in steps]
    for order, step in enumerate(expanded, start=1):
        step.step_order = order
    return expanded


class FormattedWorkoutDay(WorkoutDay):
    """A day as the Workout Formatter must return it: `steps` is REQUIRED.

    v6. Measured on a real run, from the archived prompts: the formatting call
    returned **all nine days with no `steps` key at all**, spending 11,909
    output tokens to emit 4,973 characters of JSON -- almost all of it
    thinking. The parse succeeded, because `WorkoutDay.steps` carries a
    `default_factory=list` and is therefore absent from the schema's
    `required` list. So the model was told, in the prompt, that steps were
    mandatory, and told by the schema that they were optional. It believed the
    schema.

    The deterministic repair then rebuilt every one of those days: **$0.1093 of
    the week's $0.3319, 33% of the cost**, spent on a second call doing the
    first one's entire job. Worse than the money, it meant the safety net was
    writing the plan -- and a safety net that runs every time is not a safety
    net, it is the generator with a worse prompt.

    A rest day still legitimately has `steps: []`; what changes is that the
    model must SAY so rather than stay silent. The base `WorkoutDay` keeps its
    default, because every other construction site (the validator's
    deterministic fallbacks, stored plans, the printable views) legitimately
    builds a day without passing steps.
    """

    model_config = ConfigDict(json_schema_extra=_sin_historia_en_el_esquema)

    # Sin "brick", igual que en la capa de decision: se lee pero no se
    # escribe. Heredarlo de `WorkoutDay` dejaria el hueco abierto justo en
    # el esquema que el proveedor le impone al modelo, que es el unico sitio
    # donde una prohibicion se cumple sola.
    sport: Literal["run", "bike", "swim", "strength", "walk", "rest"]

    # Texto que la DECISIÓN ya escribió: el código lo copia después
    # (weekly_planner_node._apply_discipline). Medido en la semana del atleta 2 del
    # 2026-09-23: el 62 % de lo que escribía el codificador (3.540 de 5.710
    # caracteres, con el racional) eran estas copias, pagadas a precio de salida.
    purpose: SkipJsonSchema[str] = ""
    adaptation: SkipJsonSchema[str] = ""

    steps: list[FormattedWorkoutStep] = Field(
        ...,
        description=(
            "OBLIGATORIO en cada día. Los pasos del entrenamiento. Un día de descanso lleva una lista "
            "vacía []; un día de entrenamiento SIN pasos es una salida inválida."
        ),
    )


class FormattedWeekBlock(WeekBlock):
    """What the formatting call returns, before code builds the repeat trees."""

    # Lo copia el código desde la decisión (ya lo hacía, y el codificador lo escribía igual).
    week_rationale: SkipJsonSchema[str] = ""
    days: list[FormattedWorkoutDay]

    @field_validator("days", mode="before")
    @classmethod
    def _dias_escritos_como_texto(cls, valor):
        """Una lista de días que llega como TEXTO con JSON dentro se lee como lista.

        Semana real del atleta 2 (2026-09-13, traza 15-08-07): el codificador
        devolvió `days` como la cadena '[\\n  {\\n    "date": "2026-...'. Contenido
        correcto, envoltorio equivocado -- un fallo conocido de las llamadas con
        esquema forzado cuando la estructura es grande y anidada. Pydantic lo
        rechazó entero ("Input should be a valid list"), se reintentó con el
        error delante y al final se cayó al esquema flexible: dos llamadas
        pagadas de más por una comilla.

        Sólo se convierte si el texto ES una lista JSON. Cualquier otra cosa se
        devuelve tal cual y falla como antes: esto quita un envoltorio, no
        adivina contenido. No cambia el JSON Schema que ve el proveedor.
        """
        if isinstance(valor, str):
            try:
                leido = json.loads(valor)
            except ValueError:
                return valor
            if isinstance(leido, list):
                return leido
        return valor

    def to_week_block(self) -> WeekBlock:
        """The plain `WeekBlock` the rest of the app stores, validates and uploads.

        This is where the flat lines the model wrote become the nested repeat
        blocks Garmin needs -- deterministically, in code, exactly once.
        """
        return WeekBlock(
            week_number=self.week_number,
            week_rationale=self.week_rationale,
            days=[
                WorkoutDay(
                    **{k: v for k, v in day.model_dump().items() if k != "steps"},
                    steps=expand_formatted_steps(day.steps),
                )
                for day in self.days
            ],
        )


class WeeklyPlanPayload(BaseModel):
    athlete_name: str
    start_date: str = Field(..., description="YYYY-MM-DD of the first day covered")
    zones_table: str = Field(..., description="Freeform descriptive block of the athlete's training zones")
    weeks: list[WeekBlock]


class WeeklyPlannerOutputs(BaseModel):
    """Mirrors ExpertOutputBase's list[Question] | X union so the tool-calling
    and HITL-question-collection machinery (MasterOrchestrator._collect_questions,
    which checks hasattr(result, "output")) keeps working unchanged."""

    output: list[Question] | WeeklyPlanPayload = Field(
        ...,
        description="EITHER questions for HITL OR the complete structured 4-week plan",
    )


class DayIntent(BaseModel):
    """One day's COACHING DECISION -- what to train and why, in plain
    language. Deliberately NOT the Garmin schema (no step_type/duration_type/
    target_type/units): weekly_planner_node.py splits generation into this
    decision step and a separate formatting step, so a single LLM call is
    never simultaneously making the coaching call AND encoding it into a
    schema with unit/modality rules. See TRAINING_DECISION_USER_PROMPT and
    WORKOUT_FORMATTER_USER_PROMPT."""

    date: str = Field(..., description="YYYY-MM-DD")
    day_name_es: str
    # Iba sin descripción, y eso es lo que costó el fallo.
    #
    # El prompt explica en tres viñetas que un bloque de 05:00-07:00 con
    # `run, swim` admite las dos sesiones. El modelo recibió esa tabla y esa
    # nota -- comprobado en el prompt archivado -- y aun así puso sólo
    # natación, dos generaciones seguidas, justificándose con una regla que
    # nadie escribió: "una sola entrada en días de un bloque".
    #
    # La causa está aquí: veía un campo llamado `session_slot` con los valores
    # `am` / `pm` y ni una línea que dijera qué son. Leídos como HORAS del día,
    # dos sesiones dentro de una misma mañana son imposibles: las dos serían
    # `am`. Son CLAVES, y esa frase es la que faltaba.
    #
    # Un prompt y un esquema que no dicen lo mismo los resuelve el modelo a
    # favor del esquema; un esquema que calla también decide, con la forma.
    session_slot: SessionSlot = Field(
        "unica",
        description=(
            "Identificador de ESTA sesión dentro del día, en orden cronológico: 'am', 'pm', 's3', "
            "'s4'. 'unica' SÓLO si el día tiene una sola sesión. **Es una clave, no una hora del "
            "día**: dos sesiones dentro del MISMO bloque de la mañana son 'am' y 'pm' (la primera y "
            "la segunda), no una sola porque las dos caigan antes del mediodía. Correr 45' y nadar "
            "40' en un bloque de 05:00-07:00 son 'am' y 'pm' con el mismo block_index."
        ),
    )
    block_index: int | None = Field(
        None,
        description=(
            "A cuál de los bloques de disponibilidad de ese día pertenece la sesión, empezando en 0. "
            "Un bloque admite VARIAS sesiones si las horas alcanzan (correr 45' y nadar 40' en un "
            "bloque de 1.5h son dos sesiones con el mismo block_index)."
        ),
    )
    is_rest: bool
    # v1.1: SIN "brick". Se lee, no se escribe.
    #
    # Un multideporte no es compatible con varios relojes: el atleta lo sube y
    # le llega a medias o no le llega. Y no hace falta -- un brick de bici y
    # carrera son DOS sesiones el mismo dia, que es como el atleta las hace de
    # todas formas y como cualquier reloj las entiende. Lo que hay que decirle
    # es que las encadene, y eso cabe en el texto.
    #
    # `WorkoutDay` SI lo conserva a proposito: los planes que ya estan guardados
    # tienen bricks dentro, y quitarlo de ahi haria que un atleta de la beta no
    # pudiera ni abrir su semana ni volver a subirla. Se deja de emitir sin
    # romper nada de lo emitido.
    sport: Literal["run", "bike", "swim", "strength", "walk", "rest"]
    focus: str = Field(..., description="1-2 words, e.g. 'VO2max'")
    purpose: str = Field(..., description="One short sentence: why this session, today, for this athlete")
    # La proporción de una molestia leve, AQUÍ y no sólo en el prompt: el campo
    # es obligatorio en cada sesión, y el modelo lo rellenaba con la molestia
    # declarada en todas (psoas 1/10 en 7 de 10 sesiones, dos veces seguidas el
    # 2026-09-14, con el bloque de molestias pidiendo "dos o tres").
    adaptation: str = Field(..., description=(
        "\"If tired: ...\" guidance for THIS session: what to change if sleep, fatigue, time or the day's "
        "readiness do not cooperate. A mild declared niggle (pain 1-2/10) belongs here only in the two or three "
        "sessions that load it most; in the others, adapt to something else."
    ))
    total_duration_minutes: float | None = None
    intensity_class: IntensityClass = Field(
        ...,
        description=(
            "What KIND of session this is. Drives which target modality applies, nothing else -- it never "
            "limits what the session may contain. Use 'other' for anything genuinely unusual rather than "
            "forcing a session into a class that does not fit."
        ),
    )
    session_sketch: str = Field(
        "",
        description=(
            "Plain-language description of the session structure, the way a coach would say it out loud -- "
            "e.g. '10min calentamiento Z1 con 4x20s progresivos y 60s suave, 6x400m a 5:05-5:15/km con 200m "
            "de trote entre series, 10min enfriamiento Z1'. Every set says how many repetitions, ONE "
            "duration or distance per repetition, and its recovery with its unit (seconds, minutes or "
            "metres). Ranges are for paces and effort, never for durations: the formatting pass copies "
            "numbers, it cannot choose them. Write the blocks IN ORDER, each with its own minutes or metres, "
            "adding up to total_duration_minutes: never 'complete the rest', 'the remaining time' or a set "
            "inside a longer block ('120' Z2 with 4x5' inside') -- write '20' Z2, 4x(5' cadencia baja, 5' "
            "normal), 60' Z2, 10' Z1' instead. The formatting pass does not subtract. "
            "No JSON, no field names. Empty for rest days."
        ),
    )
    discipline: Discipline = Field(
        ...,
        description=(
            "What this session IS, in coaching terms -- NOT limited to what Garmin can encode. Plyometrics, "
            "rehab, prehab, mobility and technique work are first-class sessions here. Prescribe what the "
            "athlete needs; whether it reaches the watch is handled afterwards and is never a reason to "
            "avoid a session."
        ),
    )
    execution_cues: str = Field(
        "",
        description=(
            "How to execute it well, in one or two sentences: what to feel, what to watch, what a good "
            "rep looks like. Matters most for the sessions a watch cannot describe."
        ),
    )
    equipment: str = Field(
        "",
        description="Anything needed beyond shoes: bands, box, pull buoy, treadmill, turbo. Empty if none.",
    )


class WeekIntentContent(BaseModel):
    week_number: int = Field(..., description="1-4")
    days: list[DayIntent]
    week_rationale: str = Field(
        "",
        description=(
            "In Spanish, addressed to the athlete: why this week looks the way it does. One plain "
            "sentence with the decision, then 3-5 lines starting with '- ' with what was weighed. Must "
            "cite the actual numbers that drove it (their real recovery values, adherence to the "
            "previous week, the prescribed volume, how close a competition is, the season phase), each "
            "said in words the athlete understands, rather than generic coaching language. Written "
            "where the decisions were actually made, so it reflects the real reasoning and not a "
            "summary invented afterwards."
        ),
    )


class TrainingDecisionOutputs(BaseModel):
    """Mirrors WeeklyPlannerOutputs' HITL union so the same question-routing
    machinery (MasterOrchestrator._collect_questions) keeps working unchanged
    for this earlier decision step."""

    output: list[Question] | WeekIntentContent = Field(
        ...,
        description="EITHER questions for HITL OR the week's day-by-day coaching decisions",
    )


class TrainingDecisionNoHitl(BaseModel):
    """Lo mismo SIN la rama de preguntas, para cuando el HITL esta apagado.

    ## El fallo que evita, con los numeros

    Generacion REAL del atleta 2 (2026-09-10, tier `standard`). El log del
    worker cuenta la historia entera en cuatro lineas:

        Weekly planner node: HITL disabled
        Configuring LLM for role workout with model gpt-5.2
        POST https://api.openai.com/v1/responses "HTTP/1.1 200 OK"   (11 s)
        Week generation workflow complete: $0.1153 (76768 tokens, 8 node calls)

    El modelo devolvio `list[Question]` en vez de la semana. El nodo lo leyo
    como preguntas de HITL, salio antes de tiempo -- y con `errors: []`, asi
    que aguas arriba solo quedaba un `weekly_plan` que no era un
    `WeeklyPlanPayload` y ningun motivo que dar. Dos de tres generaciones
    seguidas murieron asi: pagadas, sin plan y sin rastro.

    Lo absurdo es que el HITL estaba APAGADO. El prompt no lleva las
    instrucciones de HITL en ese caso (`get_hitl_instructions` solo se anade si
    esta encendido), pero el ESQUEMA seguia ofreciendo la rama. Un prompt y un
    esquema que no dicen lo mismo los resuelve el modelo a favor del esquema,
    siempre -- es la regla que este repo ya tenia escrita para el formateador,
    de donde la union se quito por esto mismo.

    La solucion es la de la casa: **un esquema donde el estado invalido no se
    pueda escribir.** Si nadie va a leer las preguntas, no se le ofrece
    hacerlas.
    """

    output: WeekIntentContent = Field(
        ...,
        description="The week's day-by-day coaching decisions",
    )


class SeasonPhase(BaseModel):
    name: str = Field(..., description="e.g. 'Base', 'Build', 'Peak', 'Taper'")
    start_week: int = Field(..., description="1-indexed week number, relative to season_start_date")
    end_week: int = Field(..., description="Inclusive; must be >= start_week")
    # v1.2: la CARGA es la moneda del plan y las horas una referencia de
    # agenda. Opcional para que un macrociclo guardado antes siga validando al
    # leerse; el prompt la pide siempre. Ver services/ai/weekly_load.py.
    weekly_load_target: float | None = Field(
        None,
        description="Weekly training load for a NORMAL week of this phase, in Garmin training-load units "
        "(the same scale as the athlete's chronic load given in the prompt). Anchor it to the real chronic "
        "load; race/taper weeks are handled week by week and not averaged in.",
    )
    weekly_hours_target: float | None = Field(
        None,
        description="Rough weekly hours this phase is expected to take, as an agenda reference only. The "
        "phase is defined by its load and its key sessions, not by this number.",
    )
    deload_weeks: list[int] = Field(
        default_factory=list,
        description="Week numbers (relative to the whole season, matching start_week/end_week's scale) "
        "within this phase that are deload/recovery weeks (reduced volume, ~40% less than surrounding weeks).",
    )
    intensity_emphasis: str = Field(..., description="One short sentence on the phase's intensity focus")
    # Beta 2.1, S7. Qué busca la FUERZA en esta fase, cuando el atleta dijo para
    # qué la hace. Opcional y sin vocabulario cerrado a propósito: un macrociclo
    # guardado antes sigue validando, y quien no contestó no recibe ninguna
    # palabra sobre gimnasio en su plan. No prescribe ejercicios ni sesiones: eso
    # sale de su programa (services/ai/langgraph/nodes/paso_de_fuerza.py).
    strength_quality: str | None = Field(
        None,
        description=(
            "What the gym work is FOR in this phase, in one short phrase and in the athlete's own terms "
            "(e.g. 'fuerza máxima con la barra que ya domina', 'mantener lo ganado', 'hipertrofia sin tocar "
            "la carrera'). Only when the athlete said what they train strength for; leave empty otherwise, "
            "and never turn it into a session count."
        ),
    )
    # v1.2.2. Cómo se reparte la intensidad en la fase y en qué se concentra.
    #
    # Hasta aquí la fase decía su carga y una frase de énfasis, y nada impedía
    # que cada semana metiera calidad "porque es una semana". Los cuatro campos
    # son opcionales para que un macrociclo guardado antes siga validando al
    # leerse. Sin `ge`/`le`/`max_length` a propósito: toda restricción tiene que
    # poder expresarse en el JSON Schema que ve el proveedor, o el modelo no la
    # puede obedecer; se acotan en código al leerse (compute_mesocycle_position).
    # Ver services/ai/intensity_distribution.py.
    intensity_distribution: Literal["piramidal", "polarizado", "umbral", "alta_intensidad"] | None = Field(
        None,
        description=(
            "How this phase spreads intensity, in the 3-zone model (below the first lactate threshold, "
            "between the two, above the second). 'piramidal': most training easy, more between the thresholds "
            "than above. 'polarizado': most easy, more above the second threshold than between. 'umbral': the "
            "zone between the thresholds dominates. 'alta_intensidad': a large share above the second threshold. "
            "None of them is right for everyone: choose from this athlete's goal, history and available hours."
        ),
    )
    hard_session_share_max: float | None = Field(
        None,
        description=(
            "CEILING, never a target: the largest share of this phase's endurance sessions that may sit above "
            "the first threshold (threshold or high), as a fraction between 0 and 1 -- e.g. 0.2 means at most "
            "about 2 of every 10. A week may have fewer, or none."
        ),
    )
    focus: list[str] = Field(
        default_factory=list,
        description=(
            "At most TWO capacities this phase develops, in the coach's words (e.g. 'base aerobica', "
            "'umbral de carrera'). A phase that develops everything develops nothing."
        ),
    )
    maintains: str = Field(
        "",
        description="What is kept with a minimum dose while the phase concentrates on its focus. One short sentence.",
    )


class SeasonConstraint(BaseModel):
    """One standing recommendation or limitation for this athlete, with dates.

    Plan Maestro v5 Track 3. Constraints like "cycling as the primary volume
    driver to protect the knee", "flat terrain only" or "keep ACWR under 1.2"
    used to live only inside `SeasonPlanContent.markdown`. Two consequences:
    the weekly planner had to find them in prose, and -- worse -- a constraint
    born from a knee niggle that resolved in June still governed the plan in
    November, because nothing about it had an expiry or a review.

    Deliberately NOT enforced by a validator rule. There is no per-constraint
    check anywhere: writing one rule per constraint is exactly the guardrail
    pattern this project avoids, because it would let a stale sentence veto a
    coaching decision. The only deterministic part is date filtering (see
    services/ai/season_constraints.py); the rest is making the constraint
    unambiguous for the planner and visible to the athlete.
    """

    id: str = Field(..., description="Short stable slug, e.g. 'rodilla-impacto'. Reused across re-evaluations.")
    statement: str = Field(..., description="The constraint in one sentence, in Spanish, addressed to the coach.")
    rationale: str = Field(..., description="Why it exists, citing the datum that produced it.")
    kind: Literal["carga", "terreno", "sesgo_deporte", "tecnica", "medica", "logistica", "otra"]
    strength: Literal["recomendacion", "limitacion"] = Field(
        ...,
        description=(
            "'limitacion' for something that must be respected (a declared injury, no pool access); "
            "'recomendacion' for a judgement call the coach may override with a reason. Never a 'rule'."
        ),
    )
    applies_from: str = Field(..., description="YYYY-MM-DD — the constraint is inert before this date.")
    review_on: str = Field(
        ...,
        description=(
            "YYYY-MM-DD — when this should be looked at again. Past this date it is shown as expired and "
            "downgraded, never silently dropped: a stale constraint must stop governing, but a medical "
            "limitation must not vanish because a date passed."
        ),
    )
    # Sin `tamizaje`: el cuestionario de aptitud fisica se retiro en la v1.2
    # (Database/017). Dejarlo en la lista le ofrecia al modelo un origen que ya
    # no existe, y un origen que se puede nombrar es un origen que se puede
    # inventar.
    source: str = Field(..., description="Where it came from: experto_fisiologia | lesion_declarada | adherencia | atleta")
    status: Literal["vigente", "resuelta", "modificada"] = "vigente"
    resolution_note: str = Field(
        "",
        description="On a re-evaluation: why this became 'resuelta' or what changed in 'modificada'.",
    )


class SeasonPlanContent(BaseModel):
    markdown: str = Field(..., description="The narrative season plan report, same content as before this schema existed")
    phases: list[SeasonPhase]
    season_start_date: str = Field(..., description="YYYY-MM-DD — anchor date week 1 starts from, for computing 'current week N' on every later weekly-plan generation without re-guessing it")
    constraints: list[SeasonConstraint] = Field(
        default_factory=list,
        description=(
            "Standing recommendations and limitations for THIS athlete, each with its own dates and "
            "source. Empty is a valid answer -- invent nothing. Anything qualitative that should shape "
            "the weekly plans (sport bias, terrain, load ceilings, technique focus, medical caution) "
            "belongs here as well as in the markdown, so the weekly planner sees it unambiguously "
            "instead of having to find it in prose."
        ),
    )


class SeasonPlannerOutputs(BaseModel):
    """Mirrors WeeklyPlannerOutputs' list[Question] | X union — same HITL contract."""

    output: list[Question] | SeasonPlanContent = Field(
        ...,
        description="EITHER questions for HITL OR the complete structured season plan (markdown + phases)",
    )
