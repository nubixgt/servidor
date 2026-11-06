"""Cómo respondió el cuerpo del atleta a sus sesiones más exigentes.

## Por qué existe

Un entrenador no decide cuánta intensidad aguanta un atleta mirando una barra
de su reloj: mira cómo entrenó y cómo reaccionó. Ese cruce no lo hacía nadie en
el pipeline. Los expertos están partidos por dominio a propósito -- el de
actividad tiene prohibido hablar de recuperación y el de fisiología no ve las
sesiones --, así que "después de las series del martes su HRV cayó dos días"
no lo podía escribir ninguno de los tres. El planificador recibía las sesiones
por un lado y el HRV por otro, y ninguna frase que los uniera.

Mientras tanto, lo que sí le llegaba ya cruzado era la opinión de Garmin: el
Load Focus decía "le falta aeróbico alto y anaeróbico". Pero Garmin reparte la
carga según la FC máxima y las zonas del reloj, que a menudo están mal puestas,
y un atleta puede no tolerar más intensidad aunque la barra diga que le falta.
Lo que decide si puede con ella es cómo le sentó la última vez.

## Qué es y qué no

**Describe, no concluye.** Pone cada sesión exigente al lado de las mañanas
siguientes, contra la mediana PROPIA del atleta en la misma ventana -- nunca
contra un umbral de población. No dice "le sentó mal": un HRV bajo tiene muchas
causas (sueño, estrés, un viaje), y leerlo es trabajo del entrenador.

**Se abstiene cuando no hay con qué.** Sin serie diaria de HRV o pulso, o sin
sesiones exigentes en la ventana, devuelve "" y el prompt no recibe nada.
"""

from __future__ import annotations

import statistics
from datetime import date, datetime, timedelta
from typing import Any

from services.ai.load_state import _canonical_sport

# Cuántas sesiones se enseñan. Seis cubren dos o tres semanas de calidad, que es
# lo que hace falta para ver un patrón sin convertir el bloque en un listado.
MAX_SESIONES = 6

# Una sesión es "exigente" para ESTE atleta si su carga está en el cuartil alto
# de las suyas en la ventana, o si Garmin le contó al menos un 40 % de minutos
# vigorosos (mismo criterio que services/ai/weekly_load.py). Relativo al
# atleta a propósito: una sesión exigente para un principiante no lo es para
# otro, y un umbral fijo de carga lo ignoraría.
_FRACCION_VIGOROSA = 0.40

_NOMBRE = {"run": "carrera", "bike": "bici", "swim": "natación", "strength": "fuerza"}


def _fecha(actividad: Any) -> date | None:
    crudo = str(getattr(actividad, "start_time", "") or "").replace("T", " ")[:10]
    try:
        return datetime.strptime(crudo, "%Y-%m-%d").date()
    except ValueError:
        return None


def _num(valor: Any) -> float | None:
    try:
        return float(valor) if valor is not None else None
    except (TypeError, ValueError):
        return None


def _mediana(valores: list[float]) -> float | None:
    return statistics.median(valores) if len(valores) >= 5 else None


def _sesiones_exigentes(actividades: Any, desde: date, hasta: date, es_dura: Any = None) -> list[dict[str, Any]]:
    """Las de carga en su cuartil alto, o las de calidad.

    "De calidad" lo decide `es_dura` (la clasificación de la app) cuando puede, y
    si no, el 40 % de minutos vigorosos de Garmin. Con sólo lo segundo, el VO2max
    de un atleta cuyo pulso llega tarde no se ponía al lado de sus mañanas
    siguientes, y era justo la sesión cuya respuesta había que ver.
    """
    candidatas = []
    for a in actividades or []:
        f = _fecha(a)
        resumen = getattr(a, "summary", None)
        if not f or not (desde <= f <= hasta) or resumen is None:
            continue
        minutos = (_num(getattr(resumen, "duration", None)) or 0) / 60.0
        if minutos < 15:
            continue
        candidatas.append({
            "fecha": f,
            "deporte": _canonical_sport(getattr(a, "activity_type", None)),
            "nombre": str(getattr(a, "activity_name", "") or "").strip(),
            "minutos": round(minutos),
            "carga": _num(getattr(resumen, "activity_training_load", None)),
            "vig": _num(getattr(resumen, "vigorous_intensity_minutes", None)) or 0.0,
            "dura": es_dura(a) if es_dura else None,
        })
    cargas = sorted(c["carga"] for c in candidatas if c["carga"] is not None)
    corte = cargas[int(len(cargas) * 0.75)] if len(cargas) >= 4 else None
    exigentes = [
        c for c in candidatas
        if (corte is not None and c["carga"] is not None and c["carga"] >= corte)
        or (c["dura"] if c["dura"] is not None else c["vig"] / c["minutos"] >= _FRACCION_VIGOROSA)
    ]
    exigentes.sort(key=lambda c: c["fecha"], reverse=True)
    return exigentes[:MAX_SESIONES]


def build_training_response(garmin_data: Any, hoy: date | None = None) -> str:
    """El bloque del prompt, o "" si no hay con qué cruzar."""
    from services.ai.fitness_series import _recovery_points

    hoy = hoy or date.today()
    puntos = {p["date"]: p for p in _recovery_points(garmin_data)}
    if len(puntos) < 5:
        return ""
    fechas = sorted(puntos)
    desde = date.fromisoformat(fechas[0])
    hrv_med = _mediana([p["hrv"] for p in puntos.values() if p.get("hrv") is not None])
    rhr_med = _mediana([p["rhr"] for p in puntos.values() if p.get("rhr") is not None])
    if hrv_med is None and rhr_med is None:
        return ""

    actividades = getattr(garmin_data, "recent_activities", None) or []
    from services.ai.intensity_distribution import es_sesion_dura, referencias_de_prescripcion
    from services.ai.prescription import build_prescription

    zonas = getattr(garmin_data, "garmin_hr_zones", None)
    try:
        referencias = referencias_de_prescripcion(build_prescription(garmin_data, None))
    except Exception:  # noqa: BLE001 - sin referencias vota sólo el pulso
        referencias = None
    sesiones = _sesiones_exigentes(
        actividades, desde, hoy - timedelta(days=1),
        es_dura=lambda a: es_sesion_dura(a, zonas, referencias),
    )
    if not sesiones:
        return ""

    def mañana(f: date, dias: int) -> str:
        p = puntos.get((f + timedelta(days=dias)).isoformat())
        if not p:
            return "sin registro"
        piezas = []
        if p.get("hrv") is not None and hrv_med:
            piezas.append(f"HRV {p['hrv']:.0f} ({(p['hrv'] - hrv_med) / hrv_med * 100:+.0f}%)")
        if p.get("rhr") is not None and rhr_med:
            piezas.append(f"pulso en reposo {p['rhr']} ({p['rhr'] - rhr_med:+.0f})")
        if p.get("sleep_score") is not None:
            piezas.append(f"sueño {p['sleep_score']}")
        return ", ".join(piezas) or "sin registro"

    lineas = [
        "### Cómo respondió a sus sesiones más exigentes",
        f"Referencia propia en {len(puntos)} días medidos: HRV mediana "
        + (f"{hrv_med:.0f} ms" if hrv_med else "—")
        + ", pulso en reposo mediano " + (f"{rhr_med:.0f}" if rhr_med else "—")
        + ". Cada sesión, con las dos mañanas siguientes contra esa referencia:",
    ]
    # Una línea por DÍA, no por sesión. Las mañanas siguientes son del día, y
    # con dos sesiones el mismo día la primera versión repetía las mismas dos
    # mañanas dos veces -- en la traza real del 2026-09-11, la natación y las
    # series del 9-sep aparecían como dos respuestas cuando eran una.
    por_dia: dict[date, list[dict[str, Any]]] = {}
    for s in sesiones:
        por_dia.setdefault(s["fecha"], []).append(s)
    for dia in sorted(por_dia, reverse=True):
        piezas = []
        for s in por_dia[dia]:
            nombre = f" «{s['nombre']}»" if s["nombre"] else ""
            carga = f", carga {s['carga']:.0f}" if s["carga"] is not None else ""
            piezas.append(f"{_NOMBRE.get(s['deporte'], s['deporte'])}{nombre} {s['minutos']}'{carga}")
        lineas.append(
            f"- {dia.isoformat()}: {' + '.join(piezas)}. Mañana siguiente: {mañana(dia, 1)}. "
            f"A las 48 h: {mañana(dia, 2)}."
        )
    lineas.append(
        "Esto es lo que mira un entrenador para saber cuánta intensidad tolera este atleta y cuánto tarda "
        "en absorberla. No es un diagnóstico: una mala mañana tiene muchas causas, y un patrón que se "
        "repite pesa más que un día suelto."
    )
    return "\n".join(lineas)
