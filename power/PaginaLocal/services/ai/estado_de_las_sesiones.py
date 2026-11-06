"""¿Ya hice mi sesión? Un ✓ o una ✗ por sesión, desde Garmin y sin IA (el responsable, 2026-10-21).

«Que la app sepa que ya hiciste el entreno sin necesidad de IA: hoy ya nadé y que marque un cheque, o si
ya hizo la calidad. Hoy la IA lo ve al generar la próxima semana, y eso crea ansiedad.»

No es una regla nueva: es la MISMA lectura que la adherencia de la generación (`adherence.py`) --
emparejar por el entreno del reloj (`workoutId`), después por fecha, deporte y duración, con un día de
margen y la sesión movida dentro de su semana; la intensidad contra el suelo de Z4 de SUS zonas, rescatada
por el ritmo; y lo que su reloj todavía no subió queda pendiente, nunca «no hecho». Dos lecturas que
dijeran cosas distintas de la misma sesión serían peor que ninguna.

Lo único propio de aquí es HOY: la adherencia de la generación juzga sólo los días pasados; la pantalla
quiere el ✓ de lo que ya hizo hoy, y una sesión de hoy sin actividad todavía está a tiempo (pendiente, no ✗).
"""

from __future__ import annotations

from datetime import date, timedelta
from typing import Any

# Lo que ve el atleta, y nada más:
#   hecha           ✓  la hizo (también más corta o más larga: `modificada`)
#   sin_intensidad  ✓~ la hizo, pero el pulso no llegó a la intensidad de una sesión de calidad
#   no_hecha        ✗  su día ya pasó y su reloj, que sí sincronizó después, no tiene nada
#   sin_sincronizar ⋯  no se sabe: su reloj no ha subido nada desde ese día
#   pendiente          todavía no toca (o es hoy y aún no hay actividad)
ESTADOS = ("hecha", "sin_intensidad", "no_hecha", "sin_sincronizar", "pendiente")


def estados(plan: dict | None, actividades: list | None, hoy: date, *, desde: date | None = None,
            zonas: Any | None = None, umbral_ritmo_s_km: float | None = None,
            subidas: list[dict] | None = None, ultima_sincronizacion: str | None = None) -> dict[str, dict]:
    """`{"fecha|franja": {...}}` para las sesiones de las semanas que llegan hasta `desde` o después.

    `desde` deja fuera las semanas viejas del plan guardado (guarda hasta 12): medir contra ellas un mes
    de actividades marcaba «no hecha» lo que pasó antes de la ventana (la auditoría del 2026-09-11 lo
    encontró en la adherencia de la generación).
    """
    from services.ai.adherence import build_adherence_report
    from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload

    if not plan or not plan.get("weeks"):
        return {}
    try:
        payload = WeeklyPlanPayload.model_validate(plan)
    except Exception:  # noqa: BLE001 - un plan ilegible no tiene sesiones que marcar
        return {}
    corte = (desde or hoy - timedelta(days=13)).isoformat()
    semanas = [w for w in payload.weeks if any((d.date or "") >= corte for d in w.days)]
    if not semanas:
        return {}
    payload = payload.model_copy(update={"weeks": semanas})

    # Mañana como «hoy» para la adherencia: así se juzga también lo de hoy.
    informe = build_adherence_report(payload, actividades, today=hoy + timedelta(days=1), hr_zones=zonas,
                                     umbral_ritmo_s_km=umbral_ritmo_s_km, subidas=subidas,
                                     ultima_sincronizacion=ultima_sincronizacion)
    hoy_iso = hoy.isoformat()
    salida: dict[str, dict] = {}
    for e in informe.entries:
        if (e.date or "") < corte:
            continue
        if e.date > hoy_iso:
            estado = "pendiente"
        elif e.status in ("completed", "modified"):
            estado = "hecha"
        elif e.status == "under_intensity":
            estado = "sin_intensidad"
        elif e.status == "missed":
            estado = "pendiente" if e.date == hoy_iso else "no_hecha"
        else:
            estado = "sin_sincronizar" if e.sin_sincronizar and e.date != hoy_iso else "pendiente"
        salida[f"{e.date}|{e.session_slot or 'unica'}"] = {
            "estado": estado,
            "modificada": e.status == "modified",
            "minutos": round(e.actual_minutes) if e.actual_minutes else None,
            "km": round(e.actual_distance_km, 1) if e.actual_distance_km else None,
            "fc_media": e.actual_avg_hr,
            "otro_dia": e.done_on,
            "con_el_entreno": e.desde_el_reloj,
            "por_ritmo": e.intensity_by_pace,
            "actividad": e.activity_name,
        }
    return salida
