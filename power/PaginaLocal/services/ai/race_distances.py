"""Las distancias de carrera que un atleta puede tener como meta.

## Por qué existe

El desplegable de distancia tenía cuatro opciones -- 5K, 10K, media, maratón --
y esta app no es una app de corredores de asfalto. Sirve a nadadores, nadadores
de aguas abiertas, ciclistas, triatletas, corredores de montaña y triatletas de
montaña. Un atleta que declaró "Triatlón Standard" con meta de 2:30:00 dejó el
campo vacío porque no había nada que elegir, y el planificador de temporada
recibió `sin_referencia` para su carrera principal: una meta que no se puede
evaluar es una meta que no dirige nada.

## Lo que este módulo es, y lo que NO es

Es un **catálogo de hechos**: qué distancia tiene cada formato, de qué segmentos
se compone y en qué disciplina cae. Nada más.

No dice si una meta es alcanzable -- eso lo hace `services/ai/goal_realism.py`,
y deliberadamente sin emitir un veredicto que el agente tenga que aceptar. Y no
dice qué entrenar para cada formato: eso es trabajo del planificador de
temporada, que tiene el contexto entero.

## Los segmentos importan

Un triatlón olímpico no es "51.5 km". Son 1500 m de natación, 40 km de bici y
10 km de carrera, y esos tres números son lo que permite decir algo útil sobre
una meta de 2:30: el atleta tiene una predicción de Garmin para 10K, y el
segmento de carrera de su meta ES un 10K. Guardar el formato como una sola
distancia total tira exactamente la información que lo hace evaluable.

Nada de esto reparte el tiempo objetivo entre los segmentos: repartirlo exige
suposiciones sobre transiciones y sobre el nivel relativo del atleta en cada
disciplina, y esas son un juicio, no un dato. Los segmentos se reportan; el
reparto lo razona el agente.
"""

from __future__ import annotations

from datetime import date, timedelta
from typing import Any

# Un formato de carrera:
#   label     lo que ve el atleta en el desplegable
#   group     el grupo del desplegable (y la disciplina dominante)
#   legs      [(deporte, metros), ...] en el orden en que se hacen
#   elevation True si el desnivel define la carrera más que la distancia
#
# `garmin_predicts` no se guarda aquí: lo decide `goal_realism`, que sabe qué
# predice el reloj. Duplicarlo serían dos listas que pueden divergir, que es
# exactamente cómo se coló el fallo de las clases de calidad.
RACE_FORMATS: dict[str, dict[str, Any]] = {
    # --- Carrera en asfalto ---------------------------------------------
    "5K": {"label": "5K", "group": "Carrera", "legs": [("run", 5000)]},
    "10K": {"label": "10K", "group": "Carrera", "legs": [("run", 10000)]},
    "15K": {"label": "15K", "group": "Carrera", "legs": [("run", 15000)]},
    "half_marathon": {"label": "Media maratón (21K)", "group": "Carrera",
                      "legs": [("run", 21097.5)]},
    "marathon": {"label": "Maratón (42K)", "group": "Carrera",
                 "legs": [("run", 42195)]},
    "50K": {"label": "50K", "group": "Carrera", "legs": [("run", 50000)]},
    "100K": {"label": "100K", "group": "Carrera", "legs": [("run", 100000)]},

    # --- Trail / montaña -------------------------------------------------
    # El desnivel manda sobre la distancia: un 21K con 1.800 m de desnivel
    # positivo se parece más a un maratón de asfalto que a una media, y el
    # ritmo en min/km deja de significar nada. Por eso `elevation: True`.
    "trail_short": {"label": "Trail corto (hasta 25K)", "group": "Trail",
                    "legs": [("run", 21000)], "elevation": True},
    "trail_marathon": {"label": "Trail maratón (25-45K)", "group": "Trail",
                       "legs": [("run", 42195)], "elevation": True},
    "trail_ultra_50": {"label": "Ultra trail 50K", "group": "Trail",
                       "legs": [("run", 50000)], "elevation": True},
    "trail_ultra_80": {"label": "Ultra trail 80-100K", "group": "Trail",
                       "legs": [("run", 80000)], "elevation": True},
    "trail_ultra_100m": {"label": "Ultra trail 100 millas", "group": "Trail",
                         "legs": [("run", 161000)], "elevation": True},
    "vertical_km": {"label": "Kilómetro vertical", "group": "Trail",
                    "legs": [("run", 5000)], "elevation": True},
    "skyrace": {"label": "Skyrace", "group": "Trail",
                "legs": [("run", 30000)], "elevation": True},

    # --- Natación en piscina ---------------------------------------------
    "swim_400": {"label": "400 m", "group": "Natación", "legs": [("swim", 400)]},
    "swim_800": {"label": "800 m", "group": "Natación", "legs": [("swim", 800)]},
    "swim_1500": {"label": "1500 m", "group": "Natación", "legs": [("swim", 1500)]},

    # --- Aguas abiertas ---------------------------------------------------
    "ow_1500": {"label": "1.5K aguas abiertas", "group": "Aguas abiertas",
                "legs": [("swim", 1500)]},
    "ow_3000": {"label": "3K aguas abiertas", "group": "Aguas abiertas",
                "legs": [("swim", 3000)]},
    "ow_5000": {"label": "5K aguas abiertas", "group": "Aguas abiertas",
                "legs": [("swim", 5000)]},
    "ow_10000": {"label": "10K aguas abiertas (maratón)", "group": "Aguas abiertas",
                 "legs": [("swim", 10000)]},

    # --- Ciclismo ---------------------------------------------------------
    "bike_tt_20": {"label": "Contrarreloj 20K", "group": "Ciclismo",
                   "legs": [("bike", 20000)]},
    "bike_tt_40": {"label": "Contrarreloj 40K", "group": "Ciclismo",
                   "legs": [("bike", 40000)]},
    "bike_100": {"label": "Marcha 100K", "group": "Ciclismo", "legs": [("bike", 100000)]},
    "bike_gran_fondo": {"label": "Gran fondo (140-170K)", "group": "Ciclismo",
                        "legs": [("bike", 160000)], "elevation": True},
    "bike_mtb_xco": {"label": "MTB cross-country (XCO)", "group": "Ciclismo",
                     "legs": [("bike", 30000)], "elevation": True},
    "bike_mtb_maraton": {"label": "MTB maratón (XCM)", "group": "Ciclismo",
                         "legs": [("bike", 80000)], "elevation": True},

    # --- Triatlón ---------------------------------------------------------
    "tri_supersprint": {"label": "Triatlón supersprint", "group": "Triatlón",
                        "legs": [("swim", 400), ("bike", 10000), ("run", 2500)]},
    "tri_sprint": {"label": "Triatlón sprint", "group": "Triatlón",
                   "legs": [("swim", 750), ("bike", 20000), ("run", 5000)]},
    "tri_olympic": {"label": "Triatlón olímpico / standard", "group": "Triatlón",
                    "legs": [("swim", 1500), ("bike", 40000), ("run", 10000)]},
    "tri_70_3": {"label": "Medio Ironman (70.3)", "group": "Triatlón",
                 "legs": [("swim", 1900), ("bike", 90000), ("run", 21097.5)]},
    "tri_ironman": {"label": "Ironman (140.6)", "group": "Triatlón",
                    "legs": [("swim", 3800), ("bike", 180000), ("run", 42195)]},
    "tri_cross": {"label": "Triatlón cross / XTERRA", "group": "Triatlón",
                  "legs": [("swim", 1500), ("bike", 30000), ("run", 10000)],
                  "elevation": True},

    # --- Duatlón y acuatlón ------------------------------------------------
    "duathlon_sprint": {"label": "Duatlón sprint", "group": "Duatlón / Acuatlón",
                        "legs": [("run", 5000), ("bike", 20000), ("run", 2500)]},
    "duathlon_standard": {"label": "Duatlón standard", "group": "Duatlón / Acuatlón",
                          "legs": [("run", 10000), ("bike", 40000), ("run", 5000)]},
    "aquathlon": {"label": "Acuatlón", "group": "Duatlón / Acuatlón",
                  "legs": [("swim", 1000), ("run", 5000)]},

    # --- Otros --------------------------------------------------------------
    "swimrun": {"label": "Swimrun", "group": "Otros",
                "legs": [("swim", 5000), ("run", 30000)]},
    "obstacle": {"label": "Carrera de obstáculos", "group": "Otros",
                 "legs": [("run", 15000)], "elevation": True},
}

# El orden de los grupos en el desplegable. Carrera primero porque es el caso
# más común, no porque sea el más importante.
GROUP_ORDER = ("Carrera", "Trail", "Triatlón", "Natación", "Aguas abiertas",
               "Ciclismo", "Duatlón / Acuatlón", "Otros")


def get_format(key: str | None) -> dict[str, Any] | None:
    return RACE_FORMATS.get((key or "").strip()) if key else None


def label_for(key: str | None) -> str:
    fmt = get_format(key)
    return fmt["label"] if fmt else (key or "")


def legs_of(key: str | None) -> list[tuple[str, float]]:
    fmt = get_format(key)
    return list(fmt["legs"]) if fmt else []


def is_multisport(key: str | None) -> bool:
    legs = legs_of(key)
    return len({sport for sport, _ in legs}) > 1


def is_elevation_driven(key: str | None) -> bool:
    """Si el desnivel define esta carrera más que su distancia.

    Es la razón por la que un plan de trail no se puede escribir en min/km: en
    una subida del 15% el ritmo de una persona en forma y el de una persona
    reventada son el mismo número. Lo que se puede prescribir es el esfuerzo y
    el desnivel; el ritmo es una consecuencia del terreno.
    """
    fmt = get_format(key)
    return bool(fmt and fmt.get("elevation"))


def leg_metres(key: str | None, sport: str) -> float | None:
    """Metros del segmento de un deporte, sumando si aparece varias veces
    (el duatlón corre dos veces)."""
    total = sum(m for s, m in legs_of(key) if s == sport)
    return total or None


def total_metres(key: str | None) -> float | None:
    legs = legs_of(key)
    return sum(m for _, m in legs) or None


_SPORT_ES = {"swim": "natación", "bike": "bici", "run": "carrera"}


def describe(key: str | None, elevation_gain_m: Any = None) -> str:
    """El formato en una línea, para un prompt o para la pantalla.

    Vacío si la clave no está en el catálogo -- una distancia que el atleta
    escribió a mano se respeta tal cual en otro sitio, y describir lo que no se
    conoce es inventar.
    """
    fmt = get_format(key)
    if not fmt:
        return ""
    partes = []
    for sport, metres in fmt["legs"]:
        unidad = f"{metres / 1000:g} km" if metres >= 1000 else f"{metres:g} m"
        partes.append(f"{unidad} de {_SPORT_ES.get(sport, sport)}")
    linea = f"{fmt['label']} ({' + '.join(partes)})"
    try:
        desnivel = float(elevation_gain_m) if elevation_gain_m not in (None, "", 0) else None
    except (TypeError, ValueError):
        desnivel = None
    if desnivel:
        linea += f", {desnivel:.0f} m de desnivel positivo"
    elif fmt.get("elevation"):
        linea += ", desnivel no declarado (pídeselo: en montaña define la carrera más que la distancia)"
    return linea


def options_for_ui() -> list[dict[str, Any]]:
    """El desplegable, agrupado. Un solo sitio del que sacarlo."""
    return [
        {
            "group": grupo,
            "options": [
                {"value": clave, "label": fmt["label"]}
                for clave, fmt in RACE_FORMATS.items() if fmt["group"] == grupo
            ],
        }
        for grupo in GROUP_ORDER
    ]


def normalizar_competicion(comp: dict[str, Any]) -> dict[str, Any]:
    """La carrera con su `race_type` sacado de la DISTANCIA, que es lo que el atleta eligio.

    `race_type` es una etiqueta de un desplegable que ya no existe: el alta
    nuevo elige la distancia del catalogo, y el frontend seguia rellenando
    `race_type: "Olympic"` por defecto en cada carrera nueva. Asi el 5k
    Monterrico y el 10K del atleta 2 llegaban al macrociclo del 2026-09-11 como
    "race_type": "Olympic", y el plan escribio "tomando como referencia el
    formato de Triatlon Distancia Olimpica (asumido a partir de la etiqueta
    'Olympic')" sobre dos carreras a pie.

    Cuando la distancia esta en el catalogo, manda ella. Cuando no, la
    etiqueta vieja se deja tal cual: no hay con que corregirla.
    """
    salida = dict(comp or {})
    formato = get_format(salida.get("distance"))
    if formato:
        salida["race_type"] = f"{formato['group']} · {formato['label']}"
    return salida


def solo_las_que_vienen(competiciones: list[dict], hoy: date | None = None, dias_atras: int = 3) -> list[dict]:
    """Las competiciones que todavia no han pasado, mas las de los ultimos `dias_atras` dias.

    Una carrera pasada no se filtraba en ningun sitio: el planificador seguia
    viendo el 5K de hace dos meses en su lista de objetivos, y el macrociclo
    podia periodizar hacia una fecha que ya fue. Se conservan tres dias hacia
    atras porque la semana que se genera el lunes despues de competir el
    domingo si tiene que saber que hubo carrera. Auditoria previa al merge,
    2026-09-11.

    La semana pasa su propio inicio y la ventana de `carrera_reciente` (2026-09-24): contados
    desde HOY y con tres dias, la semana generada el jueves despues de un maraton del domingo,
    o la segunda semana despues de el (14 dias faciles), ya no veia la carrera y se escribia
    sin nota de recuperacion.
    """
    corte = (hoy or date.today()) - timedelta(days=dias_atras)
    vivas = []
    for c in competiciones or []:
        fecha = str((c or {}).get("date") or "")[:10]
        try:
            if date.fromisoformat(fecha) >= corte:
                vivas.append(c)
        except ValueError:
            vivas.append(c)   # sin fecha legible: se conserva, no se adivina
    return vivas
