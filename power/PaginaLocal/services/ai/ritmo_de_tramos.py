"""Cuánto tiempo pasó una carrera dentro de un rango de ritmo, contado por tramos.

## Por qué hace falta, además del pulso

En series cortas el pulso llega tarde. Seis meses del atleta 2 (hasta el
2026-09-13): 43 tramos llanos de un minuto o más corridos MÁS RÁPIDO que su ritmo
umbral (5:52/km) y, en el 93 % de ellos, el pulso medio del tramo seguía por
debajo de Z4; en el 33 % ni el pico llegó. Sus intervalos de 60" del 22-jul, a
4:54/km, llevaban el pulso medio en 135-151. Leído sólo por pulso, ese atleta
"no hace intensidad".

Lo usan la distribución de intensidad (qué sesiones fueron duras) y la
adherencia (si una sesión de calidad se hizo en calidad), y vive aparte para que
esas dos no tengan que importarse la una a la otra.

## Cuándo el ritmo NO vota

- **En cinta.** La velocidad sale del acelerómetro de la muñeca: la sesión en
  cinta del atleta 2 del 2026-03-18 tiene tramos a 32:40/km y a 87:15/km.
- **En cuesta.** Por encima de `MAX_DESNIVEL_M_KM` el ritmo mide la pendiente.
- **En tramos de menos de un minuto.** Unos progresivos de 30" a 4:18/km son
  trabajo neuromuscular, no minutos por encima del umbral; y en tramos tan
  cortos el GPS salta.
- **Con ritmos imposibles**, por debajo de `RITMO_MINIMO_CREIBLE`: un salto del
  GPS no es una serie.

Devuelve 0 cuando no puede medir; quien llama decide que 0 no es "suave".
"""

from __future__ import annotations

from typing import Any

MIN_SEGUNDOS_TRAMO = 60
MAX_DESNIVEL_M_KM = 10.0
# 2:30/km: más rápido que cualquier atleta de esta app en un tramo de un minuto.
RITMO_MINIMO_CREIBLE = 150.0


def _num(valor: Any) -> float | None:
    try:
        return float(valor) if valor is not None else None
    except (TypeError, ValueError):
        return None


def en_cinta(actividad: Any) -> bool:
    tipo = str(getattr(actividad, "activity_type", "") or "").lower()
    return "treadmill" in tipo or "indoor" in tipo


def segundos_entre(actividad: Any, mas_rapido_que: float, hasta: float) -> float:
    """Segundos de tramos llanos con ritmo (s/km) en (`mas_rapido_que`, `hasta`].

    `mas_rapido_que=0` cuenta todo lo que sea igual o más rápido que `hasta`.
    """
    if en_cinta(actividad):
        return 0.0
    total = 0.0
    for tramo in getattr(actividad, "laps", None) or []:
        if not isinstance(tramo, dict):
            continue
        duracion = _num(tramo.get("duration_s")) or 0.0
        velocidad = _num(tramo.get("averageSpeed_ms")) or 0.0
        if duracion < MIN_SEGUNDOS_TRAMO or velocidad <= 0:
            continue
        km = (_num(tramo.get("distance_m")) or 0.0) / 1000.0
        if km > 0 and (_num(tramo.get("elevationGain_m")) or 0.0) / km > MAX_DESNIVEL_M_KM:
            continue
        ritmo = 1000.0 / velocidad
        if ritmo < RITMO_MINIMO_CREIBLE:
            continue
        if mas_rapido_que < ritmo <= hasta:
            total += duracion
    return total
