"""La deriva cardíaca en sesiones largas y sostenidas: la prueba medida de que lo suave funciona.

## Qué mide

En un esfuerzo aeróbico largo y constante, la relación entre lo que el atleta
produce (potencia o ritmo) y su pulso se degrada con el tiempo: el pulso sube
para sostener lo mismo. Cuánto se degrada entre la primera y la segunda mitad
es la deriva (decoupling, Pa:HR / Pw:HR). Una deriva baja en una sesión larga
se suele leer como buena resistencia aeróbica en ese esfuerzo; Friel usa ~5 %
como referencia. Es lo único que esta app podría darle al atleta sobre su base
aeróbica que no le da ya Garmin.

## Por qué casi todas las sesiones se descartan, con los números del atleta 2

Seis meses de su historial (179 actividades, 2026-03-15 a 2026-09-11,
traza `reasoning/2026-09-11T15-47-20`): de 24 sesiones de carrera o bici de 60
minutos o más, **sólo 3 sirven para medir**, las tres de carrera con potencia y
las tres por debajo del 5 % (4,6 · 1,6 · 2,7). Las otras 21, y por qué cada
regla de abajo existe:

- **5 tenían series** (algún tramo con pico en Z4). Un VO2max sale con +16 % y
  unas series de 5K con +24 %: comparar mitades de algo que sube y baja a
  propósito no mide nada.
- **5 eran de bici sin potenciómetro.** La velocidad en bici depende del
  terreno y del viento: la salida a Palín del 5-sep (244') sale con +35,6 % y el
  pulso subiendo de 115 a 142, y una de San Lucas (201') con +52 %. Eso es la
  ruta, no la forma.
- **8 no fueron sostenidas.** La tirada del 23-ago aceleró un 6,6 % en la
  segunda mitad con el pulso subiendo de 148 a 158, y su «deriva» sale en 0,4 %
  -- que se leería como una base excelente cuando fue una progresión. El corte
  de ±3 % sale de ahí: con ±5 % entraban tiradas que aceleraron casi un 5 % y
  daban un 0,1 % que tapa justo lo que se quiere ver.
- **3 tenían menos de 6 tramos**: dos mitades de dos tramos cada una no son
  mitades.

## Lo que NO hace

- No diagnostica. Una deriva alta puede ser calor, deshidratación, mala noche o
  un sensor; el bloque lo dice como referencia.
- No habla cuando no puede medir: sin ninguna sesión válida devuelve None y el
  prompt no recibe nada. Una sección que anuncia lo que falta invita a razonar
  sobre el hueco.
- No inventa tendencias: con menos de `MIN_PARA_TENDENCIA` sesiones válidas lo
  dice como puntos sueltos.
"""

from __future__ import annotations

from collections import Counter
from datetime import date, timedelta
from typing import Any

from services.ai.adherence import _ZONA_DE_CALIDAD
from services.ai.intensity_distribution import _zonas_del_deporte
from services.ai.load_state import _canonical_sport
from services.ai.weekly_load import _fecha, _minutos

MIN_MINUTOS = 60
MIN_TRAMOS = 6
# Cuánto puede cambiar la salida (potencia o velocidad) entre mitades para que
# la sesión cuente como sostenida. Ver el docstring: ±5 % dejaba pasar
# progresiones con una deriva engañosamente baja.
MAX_CAMBIO_DE_SALIDA_PCT = 3.0
# Por encima de esto, medir por ritmo mide la cuesta. Con potencia no aplica: la
# potencia de carrera ya incorpora la pendiente.
MAX_DESNIVEL_M_KM = 10.0
# La fracción del tiempo que tiene que llevar potencia para medir por potencia.
COBERTURA_DE_POTENCIA = 0.9
REFERENCIA_PCT = 5.0
MIN_PARA_TENDENCIA = 4

_NOMBRE = {"run": "carrera", "bike": "bici"}
_MOTIVOS = {
    "series": "con series",
    "sin_potencia": "de bici sin potencia",
    "desnivel": "con demasiado desnivel para medir por ritmo",
    "no_sostenido": "sin un esfuerzo sostenido",
    "pocos_tramos": "con pocos tramos",
    "sin_pulso": "sin pulso o sin zonas",
}


def _num(valor: Any) -> float | None:
    try:
        return float(valor) if valor is not None else None
    except (TypeError, ValueError):
        return None


def _media(tramos: list[dict], clave: str) -> float | None:
    """Media ponderada por duración, sólo de los tramos que traen esa clave."""
    peso = sum(t["_d"] for t in tramos if _num(t.get(clave)))
    if not peso:
        return None
    return sum(_num(t.get(clave)) * t["_d"] for t in tramos if _num(t.get(clave))) / peso


def medir_sesion(actividad: Any, zonas: Any) -> dict[str, Any] | None:
    """La deriva de una sesión, o por qué no se puede medir. None si ni siquiera
    es candidata (no es carrera ni bici, o dura menos de `MIN_MINUTOS`)."""
    deporte = _canonical_sport(getattr(actividad, "activity_type", None))
    minutos = _minutos(actividad)
    if deporte not in _NOMBRE or minutos < MIN_MINUTOS:
        return None
    fecha = _fecha(actividad)
    base = {"fecha": fecha.isoformat() if fecha else None, "deporte": deporte, "minutos": round(minutos)}

    zd = _zonas_del_deporte(zonas, deporte)
    suelo_alta = _num((zd.get(_ZONA_DE_CALIDAD) or (None,))[0])
    tramos = []
    for t in getattr(actividad, "laps", None) or []:
        if isinstance(t, dict) and _num(t.get("duration_s")) and _num(t.get("averageHR_bpm")):
            tramos.append({**t, "_d": _num(t["duration_s"])})
    if not suelo_alta or not tramos:
        return {**base, "valida": False, "motivo": "sin_pulso"}
    if any((_num(t.get("maxHR_bpm")) or 0) >= suelo_alta for t in tramos):
        return {**base, "valida": False, "motivo": "series"}
    if len(tramos) < MIN_TRAMOS:
        return {**base, "valida": False, "motivo": "pocos_tramos"}

    total = sum(t["_d"] for t in tramos)
    con_potencia = sum(t["_d"] for t in tramos if _num(t.get("averagePower_w")))
    por_potencia = con_potencia >= COBERTURA_DE_POTENCIA * total
    if deporte == "bike" and not por_potencia:
        return {**base, "valida": False, "motivo": "sin_potencia"}
    clave = "averagePower_w" if por_potencia else "averageSpeed_ms"
    if not por_potencia:
        distancia_km = sum(_num(t.get("distance_m")) or 0 for t in tramos) / 1000.0
        desnivel = sum(_num(t.get("elevationGain_m")) or 0 for t in tramos)
        if distancia_km > 0 and desnivel / distancia_km > MAX_DESNIVEL_M_KM:
            return {**base, "valida": False, "motivo": "desnivel"}

    # Mitades por tiempo: cada tramo va a la mitad donde cae su punto medio.
    primera, segunda, acumulado = [], [], 0.0
    for t in tramos:
        (primera if acumulado + t["_d"] / 2 <= total / 2 else segunda).append(t)
        acumulado += t["_d"]
    s1, s2 = _media(primera, clave), _media(segunda, clave)
    fc1, fc2 = _media(primera, "averageHR_bpm"), _media(segunda, "averageHR_bpm")
    if not (s1 and s2 and fc1 and fc2):
        return {**base, "valida": False, "motivo": "pocos_tramos"}
    cambio = 100.0 * (s2 - s1) / s1
    if abs(cambio) > MAX_CAMBIO_DE_SALIDA_PCT:
        return {**base, "valida": False, "motivo": "no_sostenido", "cambio_de_salida_pct": round(cambio, 1)}
    eficiencia_1, eficiencia_2 = s1 / fc1, s2 / fc2
    return {
        **base,
        "valida": True,
        "motivo": None,
        "medida": "potencia" if por_potencia else "ritmo",
        "deriva_pct": round(100.0 * (eficiencia_1 - eficiencia_2) / eficiencia_1, 1),
        "fc_1": round(fc1),
        "fc_2": round(fc2),
        "cambio_de_salida_pct": round(cambio, 1),
    }


def medir_deriva(actividades: Any, zonas: Any, hoy: date | None = None, dias: int | None = None) -> dict[str, Any] | None:
    """Todas las sesiones largas medibles, o None si no hay ni una válida."""
    hoy = hoy or date.today()
    desde = hoy - timedelta(days=dias) if dias else None
    validas, descartes, candidatas = [], Counter(), 0
    for a in actividades or []:
        f = _fecha(a)
        if not f or f > hoy or (desde and f <= desde):
            continue
        m = medir_sesion(a, zonas)
        if m is None:
            continue
        candidatas += 1
        if m["valida"]:
            validas.append(m)
        else:
            descartes[m["motivo"]] += 1
    if not validas:
        return None
    return {
        "validas": sorted(validas, key=lambda m: m["fecha"] or ""),
        "descartes": dict(descartes),
        "candidatas": candidatas,
    }


def _coma(valor: float) -> str:
    return f"{valor:.1f}".replace(".", ",")


def render_deriva(d: dict[str, Any] | None) -> str:
    """El bloque para el experto de fisiología. "" si no hay ninguna sesión válida."""
    if not d or not d.get("validas"):
        return ""
    lineas = [
        "\n### Deriva cardíaca en sesiones largas y sostenidas (MEDIDA; una referencia, no un diagnóstico)",
        f"Compara la eficiencia -- potencia o ritmo por pulsación -- de la primera mitad con la segunda, sólo en "
        f"sesiones de {MIN_MINUTOS}' o más, sin series, con un esfuerzo sostenido (la salida cambia menos de "
        f"{_coma(MAX_CAMBIO_DE_SALIDA_PCT)} % entre mitades) y, en bici, con potenciómetro. Por debajo de "
        f"~{_coma(REFERENCIA_PCT)} % se suele leer como buena resistencia aeróbica en ese esfuerzo; calor, "
        "hidratación, sueño o el sensor también la mueven.",
    ]
    for m in d["validas"]:
        lineas.append(
            f"- {m['fecha']} · {_NOMBRE[m['deporte']]} de {m['minutos']}' por {m['medida']}: deriva "
            f"{_coma(m['deriva_pct'])} % (pulso medio {m['fc_1']} → {m['fc_2']})."
        )
    descartes = d.get("descartes") or {}
    if descartes:
        partes = [f"{n} {_MOTIVOS.get(motivo, motivo)}" for motivo, n in sorted(descartes.items(), key=lambda x: -x[1])]
        lineas.append(
            f"- De {d['candidatas']} sesiones largas, no sirvieron para medir: " + ", ".join(partes) + "."
        )
    if len(d["validas"]) < MIN_PARA_TENDENCIA:
        lineas.append("- Son muy pocas para hablar de tendencia: léelas como puntos sueltos.")
    lineas.append(
        "Si alguna cambia tu lectura de su base aeróbica, dilo en `for_synthesis` con sus números: es la única "
        "medida de esa base que el atleta no ve ya en su reloj."
    )
    return "\n".join(lineas) + "\n"
