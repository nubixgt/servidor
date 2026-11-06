"""Cómo respondió ESTE atleta a cada fase: lo que el macrociclo aprende de él.

## Por qué existe

El planificador de temporada elige un reparto de intensidad por fase --
piramidal, polarizado, umbral, alta intensidad -- con la evidencia de la
literatura y los datos de hoy. Lo que no sabía nunca era si lo que eligió LA VEZ
ANTERIOR le funcionó a este atleta. La literatura habla de promedios: polarizado
dio las mayores mejoras en atletas bien entrenados durante nueve semanas
(Stöggl & Sperlich 2014); en amateurs de medio Ironman fue mejor pasar más tiempo
entre umbrales (Sellés-Pérez 2019). Un atleta concreto puede ser cualquiera de
los dos.

Un entrenador de verdad aprende eso de su atleta: al cerrar un bloque mira qué
cambió. Este módulo hace exactamente eso con lo que ya se mide, sin una llamada
extra a Garmin ni a ningún modelo:

1. Al CERRAR cada semana, `marcadores_de_la_semana` guarda en el expediente una
   foto de unos pocos cientos de bytes: predicciones de carrera, ritmo umbral,
   umbral de lactato, VO2max, HRV y su base, pulso en reposo, cuántas sesiones
   fueron suaves / de umbral / altas (con la clasificación de 3 zonas y ritmo de
   `intensity_distribution.py`), la deriva cardíaca de las que se pudo medir, y
   la fase a la que pertenecía -- con el reparto y el techo que se planificaron.
2. `render_respuesta_a_las_fases` agrupa las semanas cerradas por fase y pone,
   para cada una, lo planificado, lo hecho y cómo se movieron los marcadores
   entre el principio y el final.

## Lo que NO hace, a propósito

- **No concluye.** Son asociaciones, no causas. Un 10K previsto que mejora puede
  venir de la fase, de un afinamiento, de una carrera, del fresco, o de que
  Garmin recalculó su VO2max -- que también depende del pulso. El bloque lo dice
  y se lo deja al planificador, que tiene el resto del contexto.
- **No habla de fases de una semana.** Con menos de `MIN_SEMANAS_POR_FASE`
  semanas cerradas una fase no dice nada, y se omite.
- **No habla sin marcadores.** Las semanas cerradas antes de que existiera esta
  foto no traen marcadores, y un atleta sin ninguna fase medida recibe cero
  tokens. No se rellena nada con valores de hoy.
- **No mezcla macrociclos.** Una fase se identifica por su nombre Y por la fecha
  de inicio de su macrociclo: dos «Base» de dos temporadas son dos fases.
"""

from __future__ import annotations

import logging
from datetime import date
from pathlib import Path
from typing import Any

from services.ai.aerobic_decoupling import medir_deriva
from services.ai.intensity_distribution import clasificar_sesion, referencias_de_prescripcion
from services.ai.track_record import read_all
from services.ai.weekly_load import _fecha

logger = logging.getLogger(__name__)

MIN_SEMANAS_POR_FASE = 2
MAX_FASES = 4

_DISTANCIAS = {"5K": "prediccion_5k_s", "10K": "prediccion_10k_s", "half_marathon": "prediccion_21k_s"}


def _num(valor: Any) -> float | None:
    try:
        return float(valor) if valor is not None else None
    except (TypeError, ValueError):
        return None


def _leer(obj: Any, clave: str) -> Any:
    return obj.get(clave) if isinstance(obj, dict) else getattr(obj, clave, None)


def _dia(iso: Any) -> date | None:
    try:
        return date.fromisoformat(str(iso)[:10])
    except (TypeError, ValueError):
        return None


def contar_sesiones(actividades: Any, zonas: Any, referencias: dict | None, desde: date, hasta: date) -> dict[str, int]:
    """Cuántas sesiones de [desde, hasta] fueron suaves, de umbral o altas."""
    cuenta = {"suave": 0, "umbral": 0, "alta": 0, "altas_por_ritmo": 0}
    for a in actividades or []:
        f = _fecha(a)
        if not f or not (desde <= f <= hasta):
            continue
        s = clasificar_sesion(a, zonas, referencias)
        if s is None:
            continue
        cuenta[s["clase"]] += 1
        if s["clase"] == "alta" and s["senal"] == "ritmo":
            cuenta["altas_por_ritmo"] += 1
    return cuenta


def marcadores_de_la_semana(
    garmin_data: Any,
    prescripcion: Any,
    recuperacion: Any,
    desde_iso: str,
    hasta_iso: str,
    fase: dict[str, Any] | None = None,
) -> dict[str, Any] | None:
    """La foto del atleta al cerrar la semana [desde, hasta]. None si no hay fechas."""
    desde, hasta = _dia(desde_iso), _dia(hasta_iso)
    if not desde or not hasta:
        return None

    predicciones = _leer(prescripcion, "race_predictions") or getattr(garmin_data, "race_predictions", None) or []
    marcadores: dict[str, Any] = {"fecha": hasta.isoformat()}
    for p in predicciones:
        clave = _DISTANCIAS.get(_leer(p, "distance_key"))
        segundos = _num(_leer(p, "time_seconds"))
        if clave and segundos:
            marcadores[clave] = round(segundos)

    referencias = referencias_de_prescripcion(prescripcion)
    if referencias.get("umbral_ritmo_s_km"):
        marcadores["umbral_ritmo_s_km"] = round(referencias["umbral_ritmo_s_km"], 1)
        marcadores["umbral_ritmo_fuente"] = _leer(prescripcion, "threshold_pace_source")
    if referencias.get("lthr"):
        marcadores["lthr"] = round(referencias["lthr"])

    perfil = getattr(garmin_data, "user_profile", None)
    fisiologia = getattr(garmin_data, "physiological_markers", None)
    vo2 = _num(getattr(perfil, "vo2max_running", None)) or _num(getattr(fisiologia, "vo2_max", None))
    if vo2:
        marcadores["vo2max"] = round(vo2, 1)

    if recuperacion is not None:
        for destino, origen in (("hrv_media_7d", "hrv_weekly_avg"), ("fc_reposo", "resting_heart_rate")):
            valor = _num(_leer(recuperacion, origen))
            if valor:
                marcadores[destino] = round(valor)
        baja, alta = _num(_leer(recuperacion, "hrv_baseline_low")), _num(_leer(recuperacion, "hrv_baseline_high"))
        if baja and alta:
            marcadores["hrv_base"] = [round(baja), round(alta)]

    actividades = getattr(garmin_data, "recent_activities", None) or []
    zonas = getattr(garmin_data, "garmin_hr_zones", None)
    marcadores["sesiones"] = contar_sesiones(actividades, zonas, referencias, desde, hasta)
    deriva = medir_deriva(actividades, zonas, hoy=hasta, dias=(hasta - desde).days + 1)
    marcadores["deriva"] = [
        {"fecha": m["fecha"], "deporte": m["deporte"], "minutos": m["minutos"], "deriva_pct": m["deriva_pct"]}
        for m in (deriva or {}).get("validas", [])
    ]
    if fase and fase.get("nombre"):
        marcadores["fase"] = fase
    return marcadores


# --- la lectura ---------------------------------------------------------------

def _grupos_por_fase(semanas: list[dict[str, Any]]) -> list[dict[str, Any]]:
    cerradas = sorted(
        (s for s in semanas if s.get("executed_hours") is not None and s.get("marcadores")),
        key=lambda s: s.get("from") or "",
    )
    grupos: list[dict[str, Any]] = []
    for s in cerradas:
        fase = (s["marcadores"].get("fase") or {})
        clave = (fase.get("inicio_macro"), fase.get("nombre")) if fase.get("nombre") else (None, None)
        if grupos and grupos[-1]["clave"] == clave:
            grupos[-1]["semanas"].append(s)
        else:
            grupos.append({"clave": clave, "fase": fase, "semanas": [s]})
    return grupos


def _mmss(segundos: float) -> str:
    segundos = int(round(abs(segundos)))
    horas, resto = divmod(segundos, 3600)
    minutos, seg = divmod(resto, 60)
    return f"{horas}:{minutos:02d}:{seg:02d}" if horas else f"{minutos}:{seg:02d}"


def _cambio_de_tiempo(etiqueta: str, antes: Any, despues: Any) -> str | None:
    a, d = _num(antes), _num(despues)
    if not a or not d:
        return None
    delta = d - a
    if abs(delta) < 1:
        # Lo que no cambió no se dice: una fila de "176 → 176" es ruido que
        # empuja lo que sí se movió fuera de la vista.
        return None
    sentido = "más rápido" if delta < 0 else "más lento"
    return f"{etiqueta} {_mmss(a)} → {_mmss(d)} ({_mmss(delta)} {sentido})"


def _cambio(etiqueta: str, antes: Any, despues: Any, unidad: str = "") -> str | None:
    a, d = _num(antes), _num(despues)
    if a is None or d is None or a == d:
        return None
    fmt =(lambda x: f"{x:.1f}".replace(".", ",")) if (a % 1 or d % 1) else (lambda x: f"{x:.0f}")
    return f"{etiqueta} {fmt(a)} → {fmt(d)}{unidad}"


def _deriva_media(semanas: list[dict[str, Any]]) -> tuple[float | None, int]:
    valores = [m["deriva_pct"] for s in semanas for m in (s["marcadores"].get("deriva") or [])
               if _num(m.get("deriva_pct")) is not None]
    return (sum(valores) / len(valores), len(valores)) if valores else (None, 0)


def render_respuesta_a_las_fases(storage_dir: Path) -> str:
    """El bloque para el planificador de temporada. "" sin fases medidas."""
    try:
        grupos = _grupos_por_fase(read_all(storage_dir))
    except Exception:  # noqa: BLE001 - leer el expediente nunca tumba una generación
        logger.exception("No se pudo leer el expediente para la respuesta a las fases")
        return ""

    medidas = []
    for i, g in enumerate(grupos):
        if not g["fase"].get("nombre") or len(g["semanas"]) < MIN_SEMANAS_POR_FASE:
            continue
        previo = grupos[i - 1] if i > 0 else None
        inicio = (previo["semanas"][-1] if previo else g["semanas"][0])["marcadores"]
        medidas.append((g, previo, inicio, g["semanas"][-1]["marcadores"]))
    if not medidas:
        return ""

    omitidas = max(0, len(medidas) - MAX_FASES)
    lineas = [
        "### Cómo respondió a cada fase (MEDIDO en su expediente: asociaciones, no causas)",
        "Cada fase cerrada, con lo que se planificó, lo que hizo de verdad y cómo cambiaron sus marcadores "
        "entre el cierre de la semana anterior a la fase y el final de la fase.",
    ]
    for g, previo, inicio, fin in medidas[-MAX_FASES:]:
        fase = g["fase"]
        semanas = g["semanas"]
        techo = _num(fase.get("techo"))
        plan = ", ".join(x for x in (
            fase.get("reparto"),
            f"techo {round(techo * 100)} %" if techo is not None else None,
        ) if x) or "sin reparto declarado"
        lineas.append(
            f"- Fase «{fase['nombre']}» ({plan}) · {len(semanas)} semanas cerradas, "
            f"del {semanas[0].get('from')} al {semanas[-1].get('to')}:"
        )

        sesiones = {"suave": 0, "umbral": 0, "alta": 0}
        for s in semanas:
            for k in sesiones:
                sesiones[k] += int((s["marcadores"].get("sesiones") or {}).get(k) or 0)
        total = sum(sesiones.values())
        horas_p = sum(_num(s.get("prescribed_hours")) or 0 for s in semanas)
        horas_h = sum(_num(s.get("executed_hours")) or 0 for s in semanas)
        cal_p = sum(int(s.get("quality_prescribed") or 0) for s in semanas)
        cal_h = sum(int(s.get("quality_done") or 0) for s in semanas)
        hecho = []
        if total:
            duras = sesiones["umbral"] + sesiones["alta"]
            hecho.append(
                f"{total} sesiones — {sesiones['suave']} suaves, {sesiones['umbral']} de umbral, "
                f"{sesiones['alta']} altas ({round(100 * duras / total)} % duras)"
            )
        if horas_p:
            hecho.append(f"{horas_h:.1f} h de {horas_p:.1f} prescritas".replace(".", ","))
        if cal_p:
            hecho.append(f"calidad {cal_h}/{cal_p}")
        if hecho:
            lineas.append("    hecho: " + "; ".join(hecho) + ".")

        cambios = [c for c in (
            _cambio_de_tiempo("10K previsto", inicio.get("prediccion_10k_s"), fin.get("prediccion_10k_s")),
            _cambio_de_tiempo("5K previsto", inicio.get("prediccion_5k_s"), fin.get("prediccion_5k_s"))
            if not inicio.get("prediccion_10k_s") else None,
            # Sólo si sale del MISMO sitio: pasar del umbral de Garmin al de un test, o
            # al de Garmin ajustado (2026-09-23), cambia el método, no la forma del atleta.
            _cambio_de_tiempo("ritmo umbral", inicio.get("umbral_ritmo_s_km"), fin.get("umbral_ritmo_s_km"))
            if inicio.get("umbral_ritmo_fuente") == fin.get("umbral_ritmo_fuente")
            else ("ritmo umbral: cambió de dónde sale, no se compara"
                  if inicio.get("umbral_ritmo_s_km") and fin.get("umbral_ritmo_s_km") else None),
            _cambio("umbral de lactato", inicio.get("lthr"), fin.get("lthr"), " ppm"),
            _cambio("VO2max", inicio.get("vo2max"), fin.get("vo2max")),
            _cambio("HRV de 7 días", inicio.get("hrv_media_7d"), fin.get("hrv_media_7d"), " ms"),
            _cambio("pulso en reposo", inicio.get("fc_reposo"), fin.get("fc_reposo"), " ppm"),
        ) if c]
        media, n = _deriva_media(semanas)
        if media is not None:
            texto = f"deriva media {media:.1f} % ({n} {'sesión' if n == 1 else 'sesiones'})".replace(".", ",")
            if previo:
                media_prev, n_prev = _deriva_media(previo["semanas"])
                if media_prev is not None:
                    texto += f", frente a {media_prev:.1f} % en la fase anterior".replace(".", ",")
            cambios.append(texto)
        lineas.append("    marcadores: " + (" · ".join(cambios) if cambios else "sin marcadores comparables") + ".")

    if omitidas:
        lineas.append(f"({omitidas} fase(s) anterior(es) no se muestran.)")
    lineas.append(
        "Cómo leer esto: es la evidencia de ESTE atleta, y pesa más que la de un promedio de la literatura "
        "cuando se repite entre fases. Pero son asociaciones: un cambio puede venir de la fase, de una carrera o "
        "un afinamiento, del calor, de una enfermedad, o de que Garmin recalculó su VO2max y sus predicciones "
        "(que también dependen del pulso). Con dos o tres semanas casi siempre es ruido; nombra lo que puede "
        "confundirlo antes de apoyarte en ello."
    )
    return "\n".join(lineas) + "\n"


def fase_de_la_semana(fases: dict[str, Any] | None, desde_iso: str, posicion: Any) -> dict[str, Any] | None:
    """La fase de una semana, en la forma que guardan los marcadores.

    `posicion` es lo que devuelve `compute_mesocycle_position` para `desde_iso`.
    """
    if not posicion or not posicion.get("phase_name"):
        return None
    return {
        "nombre": posicion.get("phase_name"),
        "reparto": posicion.get("intensity_distribution"),
        "techo": posicion.get("hard_session_share_max"),
        "semana": posicion.get("week_number"),
        "inicio_macro": (fases or {}).get("season_start_date"),
    }
