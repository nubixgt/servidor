"""Un entreno que añade el atleta a un día, y quitarlo (decisión del responsable, 2026-10-23).

«Está bien si la agrega el atleta, ya es su responsabilidad: es lo mismo que salir a correr sin que lo
planificara el coach, parte de su propia decisión.» Así que no se le pide permiso a la carga ni a su tabla:
se le avisa (su tabla no da ese deporte ese día) y se hace.

- Hoy o un día que viene de una semana ACTIVA (como mover). Nunca el día de una carrera.
- Un día de descanso pasa a tener esa sesión; un día con sesiones gana otra (como mucho tres: la que había
  sola pasa a «am», igual que al mover con «hacer las dos»).
- El día lleva `edited.added`: la pantalla dice «La añadiste tú», sólo esas se pueden quitar, y «Enviar al
  reloj» la manda aunque nunca se subió.
- Resistencia: los mismos elementos del editor de pasos (`ajuste_de_sesion.expandir`). Fuerza: ejercicios del
  catálogo (`ajuste_de_fuerza.construir`).
"""

from __future__ import annotations

import copy
from typing import Any

from services.ai.edicion_de_sesion import (
    DIAS_ES,
    EdicionNoPermitida,
    _avisos,
    _clave,
    _dia_para_el_atleta,
    _fecha,
    _orden,
    _semanas_activas,
    leer_clave,
)

DEPORTES = ("run", "bike", "swim", "walk", "strength")
_TITULO = {"run": "Carrera", "bike": "Bici", "swim": "Natación", "walk": "Caminata", "strength": "Fuerza"}
_MAX_SESIONES_POR_DIA = 3
_SIGUIENTE_FRANJA = {1: "pm", 2: "s3"}


def nuevo_editor(deporte: str, prescripcion: dict | None) -> dict[str, Any]:
    """Con qué empieza el editor de un entreno nuevo: calentamiento, lo principal y enfriamiento."""
    from services.ai.plantillas_de_paso import plantillas

    if deporte not in DEPORTES:
        raise EdicionNoPermitida("Elige carrera, bici, natación, caminata o fuerza.")
    if deporte == "strength":
        return {"deporte": "strength", "grupos": [], "minutos": None, "calentamiento": "", "vuelta_a_la_calma": ""}
    todas = plantillas(deporte, prescripcion)
    principal = next((p for p in todas if p.get("nombre") in ("Rodaje suave", "Nado continuo", "Caminata")), None)
    inicio = [p for p in (todas[0] if len(todas) > 1 else None, principal, todas[-1] if len(todas) > 2 else None) if p]
    elementos = [{k: v for k, v in copy.deepcopy(p).items() if k != "nombre"} for p in inicio]
    return {"deporte": deporte, "elementos": elementos, "plantillas": todas, "minutos": None}


def _paso_de_fuerza(minutos: float, titulo: str) -> list[dict]:
    """El paso que guarda una fuerza en el plan (al reloj va desde su bloque, por repeticiones)."""
    return [{"step_order": 1, "step_type": "main", "duration_type": "time", "duration_value": round(minutos * 60),
             "repeat_count": 1, "target_type": "none", "description": f"{titulo} — {round(minutos)} min"}]


def anadir(plan: dict, fecha: str, deporte: str, *, hoy: str, elementos: list[dict] | None = None,
           grupos: list[dict] | None = None, titulo: str = "", rejilla: dict | None = None) -> dict[str, Any]:
    """Añade un entreno del atleta a `fecha`. Devuelve lo mismo que mover (`plan`, `cambios_registro`…)."""
    from services.ai import ajuste_de_fuerza
    from services.ai.ajuste_de_sesion import _minutos, expandir
    from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload

    if deporte not in DEPORTES:
        raise EdicionNoPermitida("Elige carrera, bici, natación, caminata o fuerza.")
    try:
        destino = _fecha(fecha).isoformat()
    except ValueError as e:
        raise EdicionNoPermitida("Esa fecha no es válida.") from e
    if destino < hoy:
        raise EdicionNoPermitida("Un entreno se añade a hoy o a un día que viene.")
    nuevo = copy.deepcopy(plan or {})
    semana = next((w for w in _semanas_activas(nuevo.get("weeks") or [], hoy)
                   if any(d.get("date") == destino for d in w.get("days") or [])), None)
    if semana is None:
        raise EdicionNoPermitida("Ese día no está en tus semanas activas: elige uno de esta semana o de la siguiente.")
    dias = semana["days"]
    en_el_dia = [d for d in dias if d.get("date") == destino and not d.get("is_rest")]
    if any(d.get("intensity_class") == "race_day" for d in en_el_dia):
        raise EdicionNoPermitida("Ese día es tu carrera: no se le añade nada desde aquí.")
    if len(en_el_dia) >= _MAX_SESIONES_POR_DIA:
        raise EdicionNoPermitida(f"Ese día ya tiene {len(en_el_dia)} sesiones: como mucho {_MAX_SESIONES_POR_DIA}.")

    titulo = " ".join(str(titulo or "").split())[:60] or _TITULO[deporte]
    dia: dict[str, Any] = {
        "date": destino, "day_name_es": DIAS_ES[_fecha(destino).weekday()], "is_rest": False, "sport": deporte,
        "focus": titulo, "purpose": "La añadiste tú.", "adaptation": "", "steps": [], "session_slot": "unica",
        "garmin_exportable": True, "edited": {"added": True, "by": "atleta"},
    }
    if deporte == "strength":
        bloque = {"warmup": "", "cooldown": "", "notes": "", "session_name": titulo}
        dia["strength_block"] = bloque
        ajuste_de_fuerza.aplicar(dia, grupos or [], None)
        minutos = dia.get("total_duration_minutes") or 30
        dia["steps"] = _paso_de_fuerza(minutos, titulo)
    else:
        if not elementos:
            raise EdicionNoPermitida("Un entreno necesita al menos un paso.")
        dia["steps"] = expandir(elementos, {"sport": deporte, "steps": []})
        minutos = _minutos(dia["steps"])
        if minutos is not None:
            dia["total_duration_minutes"] = round(minutos, 1)

    cambios: dict[str, str] = {}
    if not en_el_dia:
        dias[:] = [d for d in dias if d.get("date") != destino]  # el descanso se va
    else:
        if len(en_el_dia) == 1 and (en_el_dia[0].get("session_slot") or "unica") == "unica":
            en_el_dia[0]["session_slot"] = "am"
            cambios[f"{destino}|unica"] = f"{destino}|am"
        ocupadas = {d.get("session_slot") for d in en_el_dia}
        dia["session_slot"] = next(f for f in ("pm", "s3", "s4", "am") if f not in ocupadas)
    dias.append(dia)
    dias.sort(key=_orden)
    WeeklyPlanPayload.model_validate(nuevo)

    avisos = [a for a in _avisos(dias, [dia], rejilla) if "calidad" not in a]
    return {"plan": nuevo, "cambios_registro": cambios, "dias_tocados": [destino], "avisos": avisos,
            "clave": f"{destino}|{dia['session_slot']}",
            "resumen": f"«{titulo}» queda añadida el {_dia_para_el_atleta(destino)}."}


def quitar(plan: dict, clave: str, *, hoy: str) -> dict[str, Any]:
    """Quita un entreno que añadió el atleta. Los del coach no: ésos se mueven o se ajustan."""
    from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload

    fecha, franja = leer_clave(clave)
    if fecha < hoy:
        raise EdicionNoPermitida("Lo que ya pasó se queda como está.")
    nuevo = copy.deepcopy(plan or {})
    for w in nuevo.get("weeks") or []:
        dias = w.get("days") or []
        dia = next((d for d in dias if _clave(d) == (fecha, franja) and not d.get("is_rest")), None)
        if dia is None:
            continue
        if not (dia.get("edited") or {}).get("added"):
            raise EdicionNoPermitida("Sólo se quitan los entrenos que añadiste tú: los del coach se mueven o se ajustan.")
        dias[:] = [d for d in dias if d is not dia]
        cambios: dict[str, str] = {}
        quedan = [d for d in dias if d.get("date") == fecha and not d.get("is_rest")]
        if len(quedan) == 1 and quedan[0].get("session_slot") == "am":
            # Añadir la había pasado a «am»; vuelve a ser la única del día (y su entreno, a su clave).
            quedan[0]["session_slot"] = "unica"
            cambios[f"{fecha}|am"] = f"{fecha}|unica"
        if not any(d.get("date") == fecha for d in dias):
            dias.append({"date": fecha, "day_name_es": DIAS_ES[_fecha(fecha).weekday()], "is_rest": True,
                         "sport": "rest", "focus": "Descanso", "purpose": "Descanso.", "adaptation": "",
                         "steps": [], "total_duration_minutes": 0, "session_slot": "unica"})
        dias.sort(key=_orden)
        WeeklyPlanPayload.model_validate(nuevo)
        return {"plan": nuevo, "cambios_registro": cambios, "dias_tocados": [fecha], "avisos": [],
                "resumen": f"«{dia.get('focus') or 'El entreno'}» del {_dia_para_el_atleta(fecha)} ya no está en tu plan."}
    raise EdicionNoPermitida("No encuentro esa sesión en tu plan.")
