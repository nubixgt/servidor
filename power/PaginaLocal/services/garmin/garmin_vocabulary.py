"""Los valores que Garmin acepta de verdad, leídos de entrenos reales.

## Por qué existe este archivo

Garmin devuelve 400 ante cualquier valor fuera de sus enums, y — peor — a veces
**acepta la petición y descarta el campo en silencio**. Este repo tiene desde v5
la regla de no adivinar esos identificadores, y por eso hasta ahora el estilo de
natación y el nombre del ejercicio de fuerza viajaban dentro de la descripción
del paso en vez de en sus campos nativos.

Ya no hace falta adivinar. El atleta creó entrenos de referencia en su cuenta y
se leyeron de vuelta con la API. Todo lo que hay aquí **se leyó de un entreno
real**, y cada bloque dice de cuál.

## La lección que enseñó el catálogo de fuerza

En el entreno `1679360985` se mandaron catorce ejercicios con nombre. Al releerlo,
**siete conservaron `exerciseName` y siete lo perdieron**, quedándose sólo con
`category`. Ni un error, ni un aviso: Garmin guardó el entreno y tiró el nombre
que no reconocía.

De ahí las dos reglas de este módulo:

1. **`category` va siempre.** Sobrevivió en 14 de 14, y sola ya le da al atleta
   un bloque con nombre en el reloj.
2. **`exerciseName` va sólo si está en la lista verificada.** Un nombre inventado
   no rompe nada — desaparece — pero deja al atleta con un ejercicio genérico
   donde el plan decía "peso muerto rumano unipodal", que es justo el fallo
   silencioso que este proyecto persigue.

**Lo que esta lista NO hace, y esta nota decía que sí.** Aquí se afirmaba que
`exerciseName` "enciende la animación del ejercicio en el reloj", y que cada
nombre añadido era un vídeo gratis. **Nunca se verificó.** Lo único comprobado
contra la cuenta real es que el nombre SOBREVIVE al viaje de ida y vuelta de la
API — siete de catorce, que es de donde salen las dos reglas de arriba. En la
beta de septiembre de 2026 el atleta miró el reloj y la animación no aparecía.

Así que el valor de esta lista es el que se midió y ninguno más: que el atleta
lea en la muñeca "peso muerto rumano unipodal" y no un bloque genérico. Los
vídeos de los ejercicios hay que producirlos aparte — ver el catálogo de
movimientos de la v1.1 y el campo `video_key` del esquema, que existe para eso.

Regla general de este módulo, ya que estamos: **una hipótesis no se escribe con
la misma voz que una medición.** Ésta llevaba meses aquí leyéndose como un
hecho verificado.

Lo mismo vale para natación: los estilos, el material y los tipos de drill de
abajo son los que volvieron intactos, ni uno más.
"""

from __future__ import annotations

# ---------------------------------------------------------------------------
# Natación. Leídos del entreno multideporte 1679387475, que los recorre todos.
# ---------------------------------------------------------------------------

# Estos tres campos son OBJETOS, no cadenas.
#
# Se mandaron como cadenas -- `"strokeType": "free"` -- y Garmin respondió
# **500**: la subida de natación entera se cayó. El resumen que devuelve el MCP
# los aplana a texto (`"stroke_type": "free"`) y de ahí salió el error; el
# payload de verdad los trae con su id, igual que `stepType`, `endCondition` y
# `targetType`, que este constructor lleva tratando así desde el principio.
#
# Los ids son los del entreno 1679387475, leídos del JSON crudo con
# `get_workout_by_id`. Ninguno se dedujo: `fly` es 5 y no 4, y `free` es 6 --
# la numeración tiene huecos, así que contarlos habría dado valores falsos que
# Garmin acepta y muestran otro estilo.
SWIM_STROKES: dict[str, dict[str, object]] = {
    "any_stroke": {"strokeTypeId": 1, "strokeTypeKey": "any_stroke", "displayOrder": 1},
    "backstroke": {"strokeTypeId": 2, "strokeTypeKey": "backstroke", "displayOrder": 2},
    "breaststroke": {"strokeTypeId": 3, "strokeTypeKey": "breaststroke", "displayOrder": 3},
    "fly": {"strokeTypeId": 5, "strokeTypeKey": "fly", "displayOrder": 5},
    "free": {"strokeTypeId": 6, "strokeTypeKey": "free", "displayOrder": 6},
    "individual_medley": {"strokeTypeId": 7, "strokeTypeKey": "individual_medley", "displayOrder": 7},
    "mixed": {"strokeTypeId": 8, "strokeTypeKey": "mixed", "displayOrder": 8},
    "individual_medley_by_round": {
        "strokeTypeId": 9, "strokeTypeKey": "individual_medley_by_round", "displayOrder": 9},
    "reverse_individual_medley_by_round": {
        "strokeTypeId": 10, "strokeTypeKey": "reverse_individual_medley_by_round", "displayOrder": 10},
}

# `equipmentType`. No hay "sin material": simplemente se omite el campo.
SWIM_EQUIPMENT: dict[str, dict[str, object]] = {
    "fins": {"equipmentTypeId": 1, "equipmentTypeKey": "fins", "displayOrder": 1},
    "kickboard": {"equipmentTypeId": 2, "equipmentTypeKey": "kickboard", "displayOrder": 2},
    "paddles": {"equipmentTypeId": 3, "equipmentTypeKey": "paddles", "displayOrder": 3},
    "pull_buoy": {"equipmentTypeId": 4, "equipmentTypeKey": "pull_buoy", "displayOrder": 4},
    "snorkel": {"equipmentTypeId": 5, "equipmentTypeKey": "snorkel", "displayOrder": 5},
}

# `drillType`. `kick` es sólo piernas, `pull` sólo brazos, `drill` es técnica.
SWIM_DRILLS: dict[str, dict[str, object]] = {
    "kick": {"drillTypeId": 1, "drillTypeKey": "kick", "displayOrder": 1},
    "pull": {"drillTypeId": 2, "drillTypeKey": "pull", "displayOrder": 2},
    "drill": {"drillTypeId": 3, "drillTypeKey": "drill", "displayOrder": 3},
}

# El deporte de un entreno con varios tramos. **Es 10, no 9.** Se mandó 9 --
# deducido de la lista de deportes -- y Garmin acepto la subida y la guardo
# como un entreno de un solo deporte: el brick del atleta llego al reloj con el
# tramo de bici y sin el de carrera, sin un solo error. La clase de fallo que
# este repo persigue: la peticion no falla, el resultado esta mal.
MULTISPORT_TYPE = {"sportTypeId": 10, "sportTypeKey": "multi_sport", "displayOrder": 5}


# ---------------------------------------------------------------------------
# Fuerza. Categorías leídas de 1679360985 (las catorce sobrevivieron) más las
# que aparecen en los entrenos propios de Garmin 1679371824 y 1679372460.
# ---------------------------------------------------------------------------

STRENGTH_CATEGORIES = frozenset({
    "SQUAT", "DEADLIFT", "BENCH_PRESS", "PULL_UP", "CURL", "SHOULDER_PRESS",
    "ROW", "PUSH_UP", "CARRY", "SLED", "TRICEPS_EXTENSION", "PLANK", "LUNGE",
    "CARDIO", "HIP_RAISE", "CALF_RAISE",
    # De los entrenos de pilates y movilidad (1679373107, 1679373845). Son
    # justo las familias que un atleta de resistencia usa en prehab y core, y
    # que antes no tenian donde caer.
    "CORE", "MOVE", "POSE", "HYPEREXTENSION", "WARM_UP",
    # Beta 2.1, gimnasio (2026-09-17): leídas de vuelta del entreno de
    # verificación 1701096031 en la cuenta del atleta 2, con su permiso; las
    # cinco conservaron categoría y nombre.
    "LATERAL_RAISE", "CRUNCH", "FLYE", "LEG_CURL", "HIP_STABILITY",
})

# `exerciseName` que volvieron INTACTOS de un entreno real. Agrupados por su
# categoría porque Garmin exige las dos cosas juntas y una pareja incoherente
# es otra forma de perder el nombre.
#
# Procedencia:
#   (a) 1679360985 — catálogo del atleta: los siete que sobrevivieron.
#   (b) 1679371824 "Día de piernas" y 1679372460 "Entreno todoterreno", los dos
#       creados por Garmin, así que cada nombre suyo es válido por definición.
VERIFIED_EXERCISES: dict[str, frozenset[str]] = {
    "SQUAT": frozenset({
        "BARBELL_BACK_SQUAT", "DUMBBELL_SPLIT_SQUAT", "DUMBBELL_STEP_UP",
        "GOBLET_SQUAT", "SQUAT",
        "PILATES_PLIE_SQUATS_PARALLEL_TURNED_OUT_FLAT_AND_HEELS",
        "RELEVE_STRAIGHT_LEG_AND_KNEE_BENT_WITH_ONE_LEG_VARIATION",
    }),
    "DEADLIFT": frozenset({"BARBELL_DEADLIFT", "BARBELL_STRAIGHT_LEG_DEADLIFT"}),
    "LUNGE": frozenset({
        "ALTERNATING_DUMBBELL_LUNGE", "OVERHEAD_DUMBBELL_LUNGE", "SIDE_LUNGE_JUMP_OFF",
    }),
    # Gluteo medio y abductores: exactamente lo que la app prescribe para una
    # molestia de ingle o de rodilla, y ahora con animacion en el reloj.
    "HIP_RAISE": frozenset({
        "BARBELL_HIP_THRUST_ON_FLOOR", "CLAMS", "LEG_LIFT",
        "LEG_LIFT_IN_EXTERNAL_ROTATION", "LEG_CIRCLES",
    }),
    "CALF_RAISE": frozenset({"STANDING_BARBELL_CALF_RAISE"}),
    "BENCH_PRESS": frozenset({"BARBELL_BENCH_PRESS"}),
    "PULL_UP": frozenset({"PULL_UP"}),
    "CURL": frozenset({"DUMBBELL_BICEPS_CURL"}),
    "SHOULDER_PRESS": frozenset({
        "DUMBBELL_SHOULDER_PRESS", "ALTERNATING_DUMBBELL_SHOULDER_PRESS",
    }),
    "ROW": frozenset({"BENT_OVER_ROW_WITH_DUMBELL", "RENEGADE_ROW"}),
    "PUSH_UP": frozenset({"SPIDERMAN_PUSH_UP", "T_PUSH_UP", "PILATES_PUSHUP"}),
    "CARRY": frozenset({"FARMERS_CARRY"}),
    "PLANK": frozenset({
        "CROSS_BODY_MOUNTAIN_CLIMBER", "SIDE_PLANK_WITH_LEG_LIFT",
        "BRIDGE_ONE_LEG_BRIDGE",
    }),
    "CARDIO": frozenset({"SQUAT_JACKS"}),
    # Core y movilidad, de 1679373107 (pilates) y 1679373845 (columna).
    "CORE": frozenset({
        "ROLL_UP", "THE_HUNDRED", "ROLLING", "SINGLE_LEG_STRETCH",
        "DOUBLE_LEG_STRETCH", "CRISS_CROSS", "SCISSORS", "LOWER_LIFT", "SWAN",
        "SWIMMING", "BICYCLE", "CAT_COW", "ARM_AND_LEG_EXTENSION_ON_KNEES",
    }),
    "MOVE": frozenset({
        "LEG_STRETCH_WITH_BAND", "LEG_STRETCH_WITH_LEG_CIRCLES", "FROG_WITH_BAND",
        "SPINAL_TWIST", "HAMSTRING_CURL", "LUNGES_WITH_KNEE_LIFT", "HIP_STRETCH",
        "STANDING_SIDE_STRETCH",
    }),
    "POSE": frozenset({"BRIDGE", "CHILDS", "THREAD_THE_NEEDLE"}),
    "HYPEREXTENSION": frozenset({"COBRA"}),
    "WARM_UP": frozenset({"SHOULDER_SWEEPS", "HALF_KNEELING_ARM_ROTATION"}),
}

# Beta 2.1: los ejercicios de GIMNASIO de la rutina de Fernando y de una rutina
# Upper/Lower normal. Candidatos sacados del catálogo público de Garmin
# (connect.garmin.com/web-data/exercises/Exercises.json) y verificados subiendo
# el entreno 1701096031 a la cuenta del atleta 2 y leyéndolo de vuelta
# (2026-09-17, con su permiso; se borró después): 20 de 20 conservaron nombre y
# categoría. Garmin NO tiene extensión de cuádriceps ni abductor/aductor de
# MÁQUINA: esos van sin nombre, con la descripción. BARBELL_HACK_SQUAT es con
# barra; que sirva para una hack squat de máquina lo decide quien mapea, no esta
# lista, que sólo dice qué nombres sobreviven.
_GIMNASIO_VERIFICADO_1701096031 = {
    "BENCH_PRESS": {"INCLINE_DUMBBELL_BENCH_PRESS"},
    "LATERAL_RAISE": {"DUMBBELL_LATERAL_RAISE"},
    "CRUNCH": {"CRUNCH"},
    "FLYE": {"DUMBBELL_FLYE"},
    "ROW": {"SEATED_CABLE_ROW", "T_BAR_ROW", "FACE_PULL"},
    "TRICEPS_EXTENSION": {"TRICEPS_PRESSDOWN", "ROPE_PRESSDOWN"},
    "SHOULDER_PRESS": {"MILITARY_PRESS"},
    "CURL": {"EZ_BAR_PREACHER_CURL", "CABLE_BICEPS_CURL"},
    "PULL_UP": {"LAT_PULLDOWN"},
    "LEG_CURL": {"LEG_CURL"},
    "SQUAT": {"LEG_PRESS", "BARBELL_HACK_SQUAT"},
    "DEADLIFT": {"ROMANIAN_DEADLIFT"},
    "CALF_RAISE": {"STANDING_CALF_RAISE"},
    "HIP_STABILITY": {"STANDING_HIP_ABDUCTION", "STANDING_ADDUCTION"},
}
for _categoria, _nombres in _GIMNASIO_VERIFICADO_1701096031.items():
    VERIFIED_EXERCISES[_categoria] = VERIFIED_EXERCISES.get(_categoria, frozenset()) | frozenset(_nombres)


def normalise_category(value: str | None) -> str | None:
    """La categoría en la forma que Garmin espera, o None si no la reconoce.

    None NO es un fallo: la sesión sigue subiendo, sólo que como un bloque de
    repeticiones sin nombre. Es preferible a mandar una categoría inventada,
    porque el paso ya lleva el nombre real del ejercicio en su descripción.
    """
    clave = (value or "").strip().upper().replace(" ", "_").replace("-", "_")
    return clave if clave in STRENGTH_CATEGORIES else None


def verified_exercise(category: str | None, name: str | None) -> str | None:
    """El `exerciseName` sólo si esa pareja categoría+nombre se ha visto volver.

    Mandar uno no verificado no rompe la subida -- Garmin lo tira sin avisar --
    pero el atleta acaba con un ejercicio genérico donde su plan decía otra
    cosa. Preferimos no mandarlo y que la descripción haga el trabajo.
    """
    if not category or not name:
        return None
    limpio = name.strip().upper().replace(" ", "_").replace("-", "_")
    return limpio if limpio in VERIFIED_EXERCISES.get(category, frozenset()) else None


def swim_fields(stroke: str | None, equipment: str | None, drill: str | None) -> dict:
    """Los campos nativos de un paso de natación, sólo con valores verificados.

    Devuelve los OBJETOS que Garmin espera, no cadenas: mandarlos como texto
    devuelve 500 y tumba la subida entera de la sesión.

    Lo que no se reconoce se omite en vez de mandarse: el estilo y el material
    siguen viajando en la descripción del paso, que es donde han estado hasta
    ahora y que Garmin siempre acepta.
    """
    salida: dict[str, dict] = {}
    for valor, tabla, campo in (
        (stroke, SWIM_STROKES, "strokeType"),
        (equipment, SWIM_EQUIPMENT, "equipmentType"),
        (drill, SWIM_DRILLS, "drillType"),
    ):
        entrada = tabla.get((valor or "").strip().lower())
        if entrada:
            salida[campo] = dict(entrada)
    return salida


def render_exercise_catalogue() -> str:
    """El catálogo de ejercicios con animación, para el prompt del codificador.

    Se genera DESDE el diccionario en vez de escribirse aparte: dos listas que
    puedan divergir devolverían un ejercicio genérico donde el plan pedía uno
    concreto, que es exactamente la clase de fallo silencioso que este archivo
    existe para cerrar.

    Es datos de referencia, no una decisión más que pedirle al modelo: buscar
    "sentadilla búlgara" en una lista es una traducción, y el modelo ya sabe
    qué es una sentadilla búlgara. Por eso cuesta tokens y no calidad.
    """
    lineas = []
    for categoria in sorted(VERIFIED_EXERCISES):
        nombres = ", ".join(sorted(VERIFIED_EXERCISES[categoria]))
        lineas.append(f"  {categoria}: {nombres}")
    return "\n".join(lineas)
