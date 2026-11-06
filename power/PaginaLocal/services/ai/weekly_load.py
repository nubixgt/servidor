"""La carga de la semana: la moneda con la que se planifica, en vez de las horas.

## Por qué las horas no sirven como moneda

Una hora no vale lo mismo en cada deporte ni a cada intensidad. Medido en la
cuenta real del atleta 2, 12 semanas hasta el 2026-09-11, con la carga que
Garmin asigna a cada actividad (EPOC, la misma que el atleta ve en su reloj):

    bici suave          ~6 de carga por hora
    natación suave     ~34
    carrera suave      ~52
    carrera de calidad ~82
    natación de calidad ~87

Una salida en bici de 244 min cargó 104; unas series de 56 min cargaron 90. Y
semana a semana, las horas explicaban poco de la carga (correlación 0,6): la
semana de más carga del trimestre (451) fue de 5,5 h, y la de más horas
(9,5 h) cargó 318. Un plan que reparte "6 horas" puede ser una semana de
descanso o una semana brutal según de qué estén hechas.

Un entrenador planifica por CARGA y por CALIDAD: cuántas sesiones clave, de
qué tipo, y cuánta carga total tolera el atleta frente a la que ya absorbe.
Las horas son una restricción de agenda, no un objetivo.

## Por qué la carga de Garmin y no un TSS nuestro

Es la regla de `load_state.py`: cada número de estado es uno que asignó
Garmin. El atleta ve "carga aguda 297" en su reloj; un TSS calculado aquí
sería una segunda escala para lo mismo y el mismo desacuerdo que ya costó una
explicación imposible con los dos ACWR.

## Lo que este módulo hace y lo que no

Calibra, con el historial REAL del atleta, cuánta carga le cuesta una hora de
cada tipo de sesión, y con eso estima la carga de una semana planificada. Es
una estimación: la carga real depende del pulso del día. Se abstiene cuando no
hay historial suficiente para un tipo de sesión, en vez de inventarle una tasa.
"""

from __future__ import annotations

import statistics
from datetime import date, datetime, timedelta
from typing import Any

from services.ai.load_state import _canonical_sport

# Las clases de intensidad del esquema, en los tres grupos que cuestan
# distinto. El grupo de "calidad" es el de las sesiones clave de un entrenador.
CLASES_DE_CALIDAD = frozenset({
    "tempo_threshold", "vo2max", "repeticiones", "hill_repeats", "race_day",
})
CLASES_DE_FUERZA = frozenset({"strength_freeform"})

# Una sesión del historial cuenta como de calidad cuando al menos el 40 % de
# sus minutos los contó Garmin como vigorosos. La intensidad la dice Garmin, no
# un umbral de pulso nuestro -- mismo criterio de fondo que
# `training_habits._es_vigorosa`.
#
# El 40 % sale de la cuenta real, 12 semanas de carreras. La primera versión
# aceptaba también "15 minutos vigorosos" en cualquier sesión, y metía en
# calidad 50 carreras de 63: una tirada larga de 155' con 20' vigorosos contaba
# como series. La tasa de calidad se diluía hasta ~68/h y una sesión de VO2max
# de 40' se estimaba en 45 de carga, cuando las dos reales del atleta cargaron
# 97/h (8x2', 25 de 56' vigorosos) y 84/h (4x3', 34 de 56'). Con el 40 %:
# calidad ~96/h (n=17) y suave ~37/h (n=16). Las etiquetas de efecto de Garmin
# (`trainingEffectLabel`) se probaron y separan peor: natación "RECOVERY" entre
# 24 y 102/h, carrera "AEROBIC_BASE" entre 27 y 110.
_FRACCION_VIGOROSA = 0.40

# Con menos sesiones que esto no hay tasa propia: una sola salida no dice
# cuánto carga ese tipo de sesión a este atleta.
MIN_MUESTRAS = 2

_NOMBRE = {"run": "carrera", "bike": "bici", "swim": "natación", "strength": "fuerza"}


def _fecha(actividad: Any) -> date | None:
    crudo = str(getattr(actividad, "start_time", "") or "").replace("T", " ")[:10]
    try:
        return datetime.strptime(crudo, "%Y-%m-%d").date()
    except ValueError:
        return None


def _resumen(actividad: Any) -> Any:
    return getattr(actividad, "summary", None)


def _minutos(actividad: Any) -> float:
    try:
        return max(0.0, float(getattr(_resumen(actividad), "duration", 0) or 0) / 60.0)
    except (TypeError, ValueError):
        return 0.0


def _carga(actividad: Any) -> float | None:
    try:
        valor = getattr(_resumen(actividad), "activity_training_load", None)
        return float(valor) if valor is not None else None
    except (TypeError, ValueError):
        return None


def _es_de_calidad(actividad: Any, minutos: float) -> bool:
    try:
        vig = float(getattr(_resumen(actividad), "vigorous_intensity_minutes", 0) or 0)
    except (TypeError, ValueError):
        vig = 0.0
    return minutos > 0 and vig / minutos >= _FRACCION_VIGOROSA


def grupo_de_clase(intensity_class: str | None) -> str:
    clase = str(intensity_class or "").strip().lower()
    if clase in CLASES_DE_FUERZA:
        return "fuerza"
    return "calidad" if clase in CLASES_DE_CALIDAD else "suave"


def carga_semanal_real(actividades: Any, hoy: date, dias: int) -> float | None:
    """Carga media por semana en los últimos `dias`, sumando la de Garmin."""
    total, hubo = 0.0, False
    desde = hoy - timedelta(days=dias)
    for a in actividades or []:
        f = _fecha(a)
        carga = _carga(a)
        if f and desde < f <= hoy and carga is not None:
            total += carga
            hubo = True
    return round(total / (dias / 7.0)) if hubo else None


def tasas_de_carga(actividades: Any, hoy: date | None = None, dias: int = 180,
                   es_dura: Any = None) -> dict[str, dict[str, Any]]:
    """Carga por hora de este atleta, por deporte y tipo de sesión.

    Devuelve `{"run:suave": {"por_hora": 52, "n": 16}, ..., "fuerza": {...}}`.
    La fuerza va por SESIÓN, no por hora: su carga de Garmin apenas depende de
    cuánto dure, y en el historial real cada sesión cargó entre 3 y 17.

    `es_dura(actividad) -> bool | None` es la clasificación de la app
    (`intensity_distribution.es_sesion_dura`): cuando responde, manda; cuando
    devuelve None se usa el 40 % de minutos vigorosos de Garmin de abajo. Sin
    ella, los VO2max de un atleta cuyo pulso llega tarde entraban en la tasa
    SUAVE y la estimación de su semana salía corrida.
    """
    hoy = hoy or date.today()
    desde = hoy - timedelta(days=dias)
    muestras: dict[str, list[float]] = {}
    for a in actividades or []:
        f = _fecha(a)
        carga = _carga(a)
        minutos = _minutos(a)
        if not f or not (desde < f <= hoy) or carga is None or minutos < 15:
            continue
        deporte = _canonical_sport(getattr(a, "activity_type", None))
        if deporte == "strength":
            muestras.setdefault("fuerza", []).append(carga)
            continue
        if deporte not in ("run", "bike", "swim"):
            continue
        veredicto = es_dura(a) if es_dura else None
        dura = _es_de_calidad(a, minutos) if veredicto is None else veredicto
        grupo = "calidad" if dura else "suave"
        muestras.setdefault(f"{deporte}:{grupo}", []).append(carga / (minutos / 60.0))

    tasas = {}
    for clave, valores in muestras.items():
        if len(valores) >= MIN_MUESTRAS:
            tasas[clave] = {"por_hora" if clave != "fuerza" else "por_sesion":
                            round(statistics.median(valores)), "n": len(valores)}
    return tasas


def estimar_carga(sport: str | None, intensity_class: str | None, minutos: float | None,
                  tasas: dict[str, dict[str, Any]]) -> float | None:
    """Carga estimada de UNA sesión planificada, o None si no hay con qué.

    Sin tasa propia para esa combinación no se inventa una: se usa la del
    mismo deporte en el otro grupo sólo si es la de calidad para una suave
    (sobreestima, que es el lado seguro de un techo), y si no, None.
    """
    grupo = grupo_de_clase(intensity_class)
    if grupo == "fuerza" or sport == "strength":
        t = tasas.get("fuerza")
        return float(t["por_sesion"]) if t else None
    if sport not in ("run", "bike", "swim") or not minutos:
        return None
    t = tasas.get(f"{sport}:{grupo}") or (tasas.get(f"{sport}:calidad") if grupo == "suave" else None)
    return round(t["por_hora"] * float(minutos) / 60.0, 1) if t else None


def carga_de_la_semana(dias: list[Any], tasas: dict[str, dict[str, Any]]) -> dict[str, Any]:
    """Suma estimada de una semana planificada y qué parte no se pudo estimar."""
    total, sin_tasa = 0.0, []
    for d in dias or []:
        if getattr(d, "is_rest", False):
            continue
        valor = estimar_carga(getattr(d, "sport", None), getattr(d, "intensity_class", None),
                              getattr(d, "total_duration_minutes", None), tasas)
        if valor is None:
            sin_tasa.append(str(getattr(d, "date", "")))
        else:
            total += valor
    return {"carga": round(total), "sin_tasa": sin_tasa}


# --- guardar y reusar entre generaciones -------------------------------------
#
# Mismo patron que `training_habits.guardar_habitos`: el macrociclo baja 180
# dias de actividades y la semana 30, y una tasa calibrada con 4 salidas en bici
# vale menos que una con 12. Se guarda la del macrociclo y la semana se queda,
# tipo por tipo, con la que tenga MAS muestras.

FICHERO_TASAS = "load_rates.json"


def guardar_tasas(storage_dir: Any, tasas: dict[str, dict[str, Any]], hoy: date | None = None) -> None:
    import json
    from pathlib import Path

    try:
        (Path(storage_dir) / FICHERO_TASAS).write_text(json.dumps(
            {"hasta": (hoy or date.today()).isoformat(), "tasas": tasas}, ensure_ascii=False, indent=2,
        ), encoding="utf-8")
    except (OSError, TypeError, ValueError):
        import logging
        logging.getLogger(__name__).warning("No se pudieron guardar las tasas de carga", exc_info=True)


def combinar_con_guardadas(storage_dir: Any, tasas: dict[str, dict[str, Any]],
                           hoy: date | None = None, dias_que_valen: int = 42) -> dict[str, dict[str, Any]]:
    """Las tasas de esta generacion, mejoradas con las guardadas si tienen mas muestras."""
    import json
    from pathlib import Path

    try:
        datos = json.loads((Path(storage_dir) / FICHERO_TASAS).read_text(encoding="utf-8"))
        edad = ((hoy or date.today()) - date.fromisoformat(str(datos["hasta"]))).days
        guardadas = datos.get("tasas") or {}
    except (OSError, ValueError, KeyError, TypeError):
        return tasas
    if edad < 0 or edad > dias_que_valen:
        return tasas
    salida = dict(tasas)
    for clave, valor in guardadas.items():
        if not isinstance(valor, dict):
            continue
        if clave not in salida or int(valor.get("n") or 0) > int(salida[clave].get("n") or 0):
            salida[clave] = valor
    return salida
