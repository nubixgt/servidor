"""Refrescar las gráficas a diario sin generar un plan y sin llamar a la IA.

## El problema que resuelve

Las series largas (VO2max, HRV, pulso en reposo, predicción de carrera, reparto
de carga) se archivaban **una vez por generación**, así que entre semana la
pantalla enseñaba números de hace días. El atleta lo dijo así: *"¿se pueden
actualizar diariamente sin análisis extra de IA?"*. Sí, y esa es exactamente la
diferencia entre una pantalla de resultados y un tablero.

## Lo que cuesta

**Cero IA.** No hay un modelo en este archivo, igual que en `charts.py`.

Y muy poco Garmin, porque se eligieron a mano las llamadas baratas:

| Llamada                          | Peticiones | Qué trae                          |
|----------------------------------|-----------|-----------------------------------|
| `get_training_status(hoy)`       | 1         | VO2max, ACWR de Garmin, reparto   |
| `get_race_predictions()`         | 1         | 5K/10K/media/maratón              |
| `get_recovery_indicators(...)`   | 2 por día | HRV nocturna, pulso en reposo     |

Las dos primeras son una petición cada una. La tercera cuesta dos por día, así
que **sólo se piden los días que faltan** desde la última foto: al día
siguiente son dos peticiones, no sesenta.

Lo que NO se refresca aquí es lo que exige la extracción cara: el detalle de
cada actividad con sus vueltas (dos minutos en una cuenta real), la adherencia
contra el plan, y el ACWR propio de esta app -- que se calcula sobre las cargas
de esas actividades. Se quedan con el valor de la última generación y la
pantalla no finge lo contrario.

## Por qué no mezcla los dos ACWR

Garmin publica el suyo dentro de `get_training_status` y esta app calcula el
propio con otro método. Refrescar sólo uno y dibujarlo en la misma línea que el
otro haría una serie con dos definiciones dentro, que es peor que una serie
vieja. Así que la foto ligera trae el de Garmin, la línea sigue siendo la de
esta app, y cada uno se dice con su nombre -- la regla que
`services/ai/load_state.py` fija desde v6.
"""

from __future__ import annotations

import json
import logging
import os
import re
from datetime import date, timedelta
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)

# Dónde viven las fotos ligeras. Aparte del archivo de razonamiento a
# propósito: una generación es un hecho con su coste y su trazabilidad; esto es
# una lectura del reloj. Mezclarlas haría que el historial de generaciones se
# llenara de carpetas que no generaron nada.
SNAPSHOT_DIRNAME = "fitness"

# Cuántos días de recuperación se piden como máximo en un refresco. Un atleta
# que no abre la app en dos meses no debe disparar ciento veinte peticiones de
# golpe: se traen los últimos treinta y el resto llega en la próxima
# generación, que sí hace la extracción larga.
MAX_BACKFILL_DAYS = 30

_DATE_RE = re.compile(r"^\d{4}-\d{2}-\d{2}$")

# Cuántos días de histórico se piden. Seis meses es lo que Garmin enseña por
# defecto en su propia gráfica de pronóstico, y cabe en UNA petición.
DIAS_DE_HISTORIAL = 180


def _dias_sin_carga(storage_dir: Path, hoy: date) -> list[str]:
    """Los días recientes de los que todavía no tenemos el ACWR de Garmin.

    Cada uno cuesta una petición, así que se piden sólo los que faltan y nunca
    más de `MAX_BACKFILL_DAYS`. Un atleta que vuelve tras dos meses recibe los
    últimos treinta y el resto se va completando solo en las visitas
    siguientes, en vez de disparar sesenta llamadas de golpe.
    """
    conocidos: set[str] = set()
    carpeta = _snapshot_dir(storage_dir)
    if carpeta.is_dir():
        for archivo in carpeta.glob("*.json"):
            try:
                datos = json.loads(archivo.read_text(encoding="utf-8"))
            except (OSError, json.JSONDecodeError):
                continue
            conocidos |= {p.get("date") for p in (datos.get("load_ratio_history") or [])}
            if datos.get("garmin_acwr") is not None and datos.get("as_of"):
                conocidos.add(datos["as_of"])
    faltan = [
        (hoy - timedelta(days=n)).isoformat()
        for n in range(MAX_BACKFILL_DAYS)
    ]
    return [d for d in faltan if d not in conocidos]


def _snapshot_dir(storage_dir: Path) -> Path:
    return Path(storage_dir) / SNAPSHOT_DIRNAME


def last_snapshot_date(storage_dir: Path) -> str | None:
    """La fecha de la foto más reciente, o None si no hay ninguna."""
    carpeta = _snapshot_dir(storage_dir)
    if not carpeta.is_dir():
        return None
    fechas = sorted(p.stem for p in carpeta.glob("*.json") if _DATE_RE.match(p.stem))
    return fechas[-1] if fechas else None


def is_stale(storage_dir: Path, today: date | None = None) -> bool:
    """Si la última foto no es de hoy.

    Se mira contra el DÍA, no contra una duración: un atleta que entrena por la
    mañana quiere ver el dato de esa mañana, y un umbral de "hace 24 horas" se
    lo daría a mediodía del día siguiente.
    """
    ultima = last_snapshot_date(storage_dir)
    return ultima != (today or date.today()).isoformat()


def _recovery_points(indicators: list) -> list[dict[str, Any]]:
    """Igual que en `fitness_series.py`, sobre el mismo objeto del extractor."""
    from services.ai.fitness_series import _recovery_points as construir

    class _Envoltorio:
        recovery_indicators = indicators
        long_term_vo2_max_trend = None
        vo2_max_history = None

    return construir(_Envoltorio())


def _vo2max_from_status(status: Any) -> list[dict[str, Any]]:
    """El VO2max de hoy, con la fecha que Garmin le pone.

    La fecha viene del payload y no de `today` a propósito: Garmin recalcula el
    VO2max sólo después de ciertas carreras, así que su `calendarDate` puede ser
    de hace días. Poner la de hoy inventaría un punto que no se midió hoy.
    """
    datos = getattr(status, "vo2_max", None) or {}
    if not isinstance(datos, dict):
        return {}
    valor = datos.get("value") or datos.get("vo2MaxValue")
    fecha = datos.get("date") or datos.get("calendarDate")
    try:
        return [{"date": str(fecha)[:10], "value": round(float(valor), 2)}] if valor and fecha else []
    except (TypeError, ValueError):
        return []


def build_light_snapshot(extractor, storage_dir: Path, today: date | None = None) -> dict[str, Any]:
    """Una foto de las series, con las llamadas baratas de Garmin.

    Tolerante bloque a bloque: si el reloj no da HRV, o la cuenta no tiene
    predicciones, ese trozo no aparece y los demás sí. Un refresco a medias es
    mejor que ninguno, y desde luego mejor que una excepción en la cara del
    atleta por un dato que su reloj no mide.
    """
    today = today or date.today()
    snapshot: dict[str, Any] = {"as_of": today.isoformat(), "source": "light_refresh"}

    try:
        status = extractor.get_training_status(today)
        vo2 = _vo2max_from_status(status)
        if vo2:
            snapshot["vo2max"] = vo2
        balance = getattr(status, "training_load_balance", None) or getattr(status, "load_balance", None)
        if balance:
            snapshot["load_balance"] = balance
        # La disponibilidad de la MANANA, no la ultima del dia: la de la
        # manana dice si se recupero durmiendo, y la de la tarde si ya esta
        # pagando la sesion de hoy. Para una tendencia diaria interesa la
        # primera, o dos dias iguales saldrian distintos segun a que hora se
        # refresco. Garmin NO publica historico de esto, asi que la serie solo
        # crece hacia delante -- ver readiness_trend en charts.py.
        disponibilidad = getattr(status, "training_readiness", None)
        if isinstance(disponibilidad, dict):
            manana = disponibilidad.get("morning") or disponibilidad.get("latest") or {}
            puntuacion = manana.get("score") if isinstance(manana, dict) else None
            if puntuacion is not None:
                snapshot["readiness"] = [{
                    "date": today.isoformat(),
                    "value": round(float(puntuacion)),
                    "level": manana.get("level"),
                }]
        # El ACWR de Garmin vive DENTRO de `acute_training_load`, no como
        # atributo suelto del estado.
        #
        # Esto leia `status.acute_chronic_workload_ratio`, que no existe en
        # ningun modelo del extractor, asi que `getattr` devolvia None todos
        # los dias y la clave nunca se guardaba. El dato SI estaba -- 0.90 con
        # estado OPTIMAL en la extraccion del mismo dia -- y por eso la
        # grafica de carga solo podia dibujar el ACWR nuestro, una foto por
        # generacion, mientras la de Garmin (la que el atleta ve en su reloj)
        # no tenia serie ninguna. Ver `_apply_training_status` en
        # services/ai/load_state.py, que es de donde sale el nombre bueno.
        carga = getattr(status, "acute_training_load", None)
        if isinstance(carga, dict):
            if carga.get("acwr") is not None:
                snapshot["garmin_acwr"] = carga["acwr"]
            if carga.get("acwr_status"):
                snapshot["garmin_acwr_status"] = carga["acwr_status"]
            # El rango de carga cronica que Garmin considera optimo PARA ESTE
            # atleta. Es lo que convierte la grafica en algo interpretable:
            # sin el, un 0.9 no dice si esta bien o mal.
            for clave in ("acute_load", "chronic_load", "chronic_optimal_min", "chronic_optimal_max"):
                if carga.get(clave) is not None:
                    snapshot[clave] = carga[clave]
    except Exception:
        logger.warning("No se pudo refrescar el estado de entrenamiento", exc_info=True)

    try:
        predicciones = extractor.get_race_predictions() or []
        filas = [
            {"distance_key": getattr(p, "distance_key", None),
             "time_seconds": getattr(p, "time_seconds", None)}
            for p in predicciones
        ]
        filas = [f for f in filas if f["distance_key"] and f["time_seconds"]]
        if filas:
            snapshot["race_predictions"] = filas
    except Exception:
        logger.warning("No se pudieron refrescar las predicciones de carrera", exc_info=True)

    # EL HISTÓRICO, que es lo que hace útil la gráfica de la meta.
    #
    # La foto de arriba es la predicción de HOY. Apilando una por día se
    # tardan semanas en tener una línea, y la del atleta llevaba dos puntos
    # iguales. Garmin guarda el histórico y lo dibuja en Connect ("Pronóstico
    # de carrera"): una sola petición lo trae entero, así que la gráfica nace
    # con meses de historia en vez de esperarlos.
    #
    # Va en la foto de HOY, no una por día: es la misma serie recogida cada
    # vez, y `merge_archived_series` deduplica por fecha ganando la más
    # reciente -- que es la que Garmin ya recalculó con las actividades
    # sincronizadas.
    try:
        historico = extractor.get_race_prediction_history(
            today - timedelta(days=DIAS_DE_HISTORIAL), today,
        )
        if historico:
            snapshot["race_prediction_history"] = historico
    except Exception:
        logger.warning("No se pudo traer el historico de pronosticos", exc_info=True)

    # Y el ACWR de los días que falten. Aquí sí es una petición por día: el
    # estado de entrenamiento se pide por fecha y Garmin no publica un rango.
    # Por eso se piden SÓLO los que faltan y se acotan a MAX_BACKFILL_DAYS.
    try:
        pendientes = _dias_sin_carga(storage_dir, today)
        if pendientes:
            ratios = extractor.get_load_ratio_history(pendientes)
            if ratios:
                snapshot["load_ratio_history"] = ratios
    except Exception:
        logger.warning("No se pudo traer el historico de carga", exc_info=True)

    # Sólo los días que faltan. Es la única llamada que escala con el tiempo,
    # así que es la única que hay que acotar.
    desde = today - timedelta(days=MAX_BACKFILL_DAYS)
    ultima = last_snapshot_date(storage_dir)
    if ultima:
        try:
            desde = max(desde, date.fromisoformat(ultima))
        except ValueError:
            pass
    try:
        indicadores = extractor.get_recovery_indicators(desde, today) or []
        puntos = _recovery_points(indicadores)
        if puntos:
            snapshot["recovery"] = puntos
    except Exception:
        logger.warning("No se pudieron refrescar los datos de recuperacion", exc_info=True)

    return snapshot


# Las claves que llevan un dato. Se listan en vez de contar el tamaño del
# diccionario: contar hacía que añadir un campo de metadatos cambiara
# silenciosamente qué se considera "una foto vacía".
DATA_KEYS = ("vo2max", "recovery", "race_predictions", "load_balance", "garmin_acwr",
             "race_prediction_history", "load_ratio_history")


def save_snapshot(storage_dir: Path, snapshot: dict[str, Any]) -> Path | None:
    """Guarda la foto del día. Una por día: refrescar dos veces la reemplaza en
    vez de acumular, porque son la misma lectura del mismo reloj.

    Una foto sin ningún dato NO se guarda: dejaría la fecha de hoy como último
    refresco y la pantalla diría "datos de hoy" sobre datos que no se leyeron.
    """
    if not snapshot or not snapshot.get("as_of"):
        return None
    if not any(snapshot.get(clave) for clave in DATA_KEYS):
        return None
    carpeta = _snapshot_dir(storage_dir)
    carpeta.mkdir(parents=True, exist_ok=True)
    destino = carpeta / f"{snapshot['as_of']}.json"
    tmp = destino.with_suffix(".json.tmp")
    tmp.write_text(json.dumps(snapshot, ensure_ascii=False, default=str), encoding="utf-8")
    os.replace(tmp, destino)
    return destino


def refresh(extractor, storage_dir: Path, today: date | None = None) -> dict[str, Any]:
    """Refresca y guarda. Devuelve qué se pudo traer y qué no.

    Lo que NO se refrescó se dice explícitamente en vez de omitirse: un tablero
    que enseña un número viejo sin decir que es viejo es peor que uno que no lo
    enseña.
    """
    snapshot = build_light_snapshot(extractor, Path(storage_dir), today)
    guardado = save_snapshot(Path(storage_dir), snapshot)
    return {
        "saved": bool(guardado),
        "as_of": snapshot.get("as_of"),
        "refreshed": [k for k in ("vo2max", "recovery", "race_predictions", "load_balance")
                      if snapshot.get(k)],
        "not_refreshed": [
            "adherencia y horas ejecutadas",
            "el ACWR que calcula esta app",
        ],
    }
