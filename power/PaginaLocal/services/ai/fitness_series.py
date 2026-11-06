"""Las series largas de forma y recuperación, sacadas de lo que Garmin YA trajo.

## Por qué existe este archivo

Las gráficas de la app salían todas del expediente (`track_record.py`), que
guarda **una línea por semana**. Eso está bien para "lo que te mandé contra lo
que hiciste" -- una semana es la unidad de esa pregunta -- pero convierte
cualquier otra tendencia en una gráfica que tarda meses en tener forma. La
primera de ellas, la carga, se dibujó con un solo punto.

Y no hacía falta. `GarminData` ya trae, en **cada** generación y sin una sola
llamada extra:

- `long_term_vo2_max_trend`: 360 días muestreados cada 14 → ~26 puntos de
  VO2max de golpe, desde la primera vez que el atleta genera un plan.
- `vo2_max_history`: los valores del rango corto, más finos.
- `recovery_indicators`: una entrada **por día** con HRV nocturna, frecuencia
  cardiaca en reposo y puntuación de sueño.

Todo eso se extraía, se resumía en prosa y se tiraba. Este módulo lo recoge en
un archivo pequeño (`fitness_series.json`) que se archiva junto al resto del
razonamiento de esa generación, y `charts.py` lo une entre generaciones
deduplicando por fecha. Cada corrida aporta sus días; los solapados no se
duplican; la serie crece sola.

## Lo que NO hace

No llama a Garmin y no llama a ningún modelo: recibe el `GarminData` que la
generación ya tenía en la mano. Y no interpreta -- un VO2max de 42 no es
"bueno" ni "malo" aquí; es 42 el 14 de agosto.
"""

from __future__ import annotations

import logging
from typing import Any

logger = logging.getLogger(__name__)

# Cuántos días de recuperación se guardan por generación. La ventana de
# métricas son 56 días; guardar más de lo que se pidió sólo puede meter
# huecos. El archivo resultante ronda los 6 KB.
RECOVERY_DAYS_KEPT = 120


def _point(fecha: Any, valor: Any) -> dict[str, Any] | None:
    """Un punto sólo existe si tiene fecha Y valor. Ninguna de las dos se
    inventa: una serie con un valor sin fecha no se puede dibujar, y una fecha
    sin valor es un hueco, que es información distinta de un cero."""
    if not fecha or valor is None:
        return None
    try:
        return {"date": str(fecha)[:10], "value": round(float(valor), 2)}
    except (TypeError, ValueError):
        return None


def _vo2max_points(garmin_data) -> list[dict[str, Any]]:
    """VO2max de carrera, uniendo la tendencia larga con el historial corto.

    Se unen a propósito y en ese orden: la tendencia larga da el arco de un
    año muestreado cada dos semanas, el historial corto da el detalle reciente.
    Donde las dos traen la misma fecha gana la segunda, que es la más fina.
    """
    por_fecha: dict[str, dict[str, Any]] = {}
    for fuente in ("long_term_vo2_max_trend", "vo2_max_history"):
        datos = getattr(garmin_data, fuente, None) or {}
        for entrada in (datos.get("running") or []):
            punto = _point(entrada.get("date"), entrada.get("value"))
            if punto:
                por_fecha[punto["date"]] = punto
    return sorted(por_fecha.values(), key=lambda p: p["date"])


def _recovery_points(garmin_data) -> list[dict[str, Any]]:
    """HRV nocturna, pulso en reposo y sueño, un punto por día.

    Sale de `recovery_indicators`, que el extractor ya construye día a día. El
    bloque de recuperación que lee el planificador (`recovery_state.py`) usa
    sólo el valor de HOY y su rango base; la serie completa nunca se había
    mirado.
    """
    salida = []
    for indicador in (getattr(garmin_data, "recovery_indicators", None) or []):
        fecha = getattr(indicador, "date", None)
        sueno = getattr(indicador, "sleep", None) or {}
        if not fecha or not isinstance(sueno, dict):
            continue
        hrv = sueno.get("avg_overnight_hrv")
        rhr = sueno.get("resting_heart_rate")
        calidad = (sueno.get("quality") or {}).get("overall_score")
        # Horas dormidas y estrés medio del día. El extractor ya los traía en
        # el MISMO objeto -- `sleep.duration.total` y `stress.avg_level` -- y
        # aquí se descartaban: la puntuación de sueño se archivaba desde el
        # principio y ni siquiera esa se dibujaba.
        horas = (sueno.get("duration") or {}).get("total")
        estres = (getattr(indicador, "stress", None) or {}).get("avg_level")
        # Se sigue exigiendo HRV o pulso para dar el día por bueno: un día sin
        # ninguno de los dos es un día que el reloj no midió, y meterlo con
        # sólo el estrés dejaría huecos en las dos series principales.
        if hrv is None and rhr is None:
            continue
        salida.append({
            "date": str(fecha)[:10],
            "hrv": round(float(hrv), 1) if hrv is not None else None,
            "rhr": int(rhr) if rhr is not None else None,
            "sleep_score": int(calidad) if calidad is not None else None,
            "sleep_hours": round(float(horas), 2) if horas is not None else None,
            "stress": int(estres) if estres is not None else None,
        })
    salida.sort(key=lambda p: p["date"])
    return salida[-RECOVERY_DAYS_KEPT:]


def _weight_points(garmin_data) -> list[dict[str, Any]]:
    """El peso, un punto por pesada. **Ya viene en KILOS.**

    `BodyMetrics.weight` NO es el payload crudo de Garmin: el extractor ya lo
    procesó y devuelve `{"data": [{"date", "weight", "source"}], "average"}`
    con el peso convertido a kg (`data_extractor.get_body_metrics`, que hace el
    `/1000` de los gramos y lee `dateWeightList` por su cuenta).

    La primera versión de esta función leía `dateWeightList` y volvía a dividir
    entre mil. Sobre datos reales -- `{"date": "2026-08-05", "weight": 65,
    "source": "MANUAL"}` -- eso daba 0,065 kg, que el filtro de cordura de
    abajo descartaba en silencio: la serie salía vacía y parecía que el atleta
    no tenía pesajes. Los tenía, y son manuales.

    Es el fallo de unidades que este repo lleva persiguiendo desde el
    principio, esta vez por asumir la forma del dato en vez de mirarla.
    """
    metricas = getattr(garmin_data, "body_metrics", None)
    crudo = getattr(metricas, "weight", None) if metricas else None
    if not isinstance(crudo, dict):
        return []

    salida: list[dict[str, Any]] = []
    for fila in (crudo.get("data") or []):
        if not isinstance(fila, dict):
            continue
        fecha, kilos = fila.get("date"), fila.get("weight")
        if not fecha or kilos is None:
            continue
        try:
            kg = round(float(kilos), 2)
        except (TypeError, ValueError):
            continue
        # Sigue habiendo guarda de cordura, ahora con el rango correcto: un
        # valor fuera de aquí es un error de unidad, no un atleta. Es lo que
        # convirtió el fallo de arriba en una serie vacía en vez de en una
        # gráfica que decía que el atleta pesa 65 gramos.
        if not 25.0 <= kg <= 250.0:
            logger.warning("Pesaje fuera de rango, se descarta: %r kg el %s", kilos, fecha)
            continue
        salida.append({"date": str(fecha)[:10], "value": kg})

    salida.sort(key=lambda p: p["date"])
    return salida[-RECOVERY_DAYS_KEPT:]


def build_fitness_series(garmin_data, prescription_state: dict | None = None) -> dict[str, Any]:
    """La foto larga de esta generación, lista para archivar.

    Tolerante por diseño: un reloj que no mide HRV, o una cuenta sin historial
    de VO2max, son casos normales y no pueden romper una generación que el
    atleta ya pagó. Cada bloque que falte simplemente no aparece.
    """
    if garmin_data is None:
        return {}

    prescripcion = prescription_state or {}
    serie: dict[str, Any] = {}

    try:
        vo2 = _vo2max_points(garmin_data)
        if vo2:
            serie["vo2max"] = vo2
    except Exception:
        logger.exception("No se pudo construir la serie de VO2max")

    try:
        recuperacion = _recovery_points(garmin_data)
        if recuperacion:
            serie["recovery"] = recuperacion
    except Exception:
        logger.exception("No se pudo construir la serie de recuperacion")

    try:
        peso = _weight_points(garmin_data)
        if peso:
            serie["weight"] = peso
    except Exception:
        logger.exception("No se pudo construir la serie de peso")

    # La predicción de carrera es un valor de HOY, no una serie: Garmin la
    # recalcula contra la forma actual. La serie se construye entre
    # generaciones, en charts.py, uniendo la de cada archivo.
    predicciones = prescripcion.get("race_predictions") or []
    if predicciones:
        serie["race_predictions"] = [
            {"distance_key": p.get("distance_key"), "time_seconds": p.get("time_seconds")}
            for p in predicciones
            if p.get("distance_key") and p.get("time_seconds")
        ]

    return serie
