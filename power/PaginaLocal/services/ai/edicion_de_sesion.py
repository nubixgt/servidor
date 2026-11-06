"""Cambiar una sesión del plan desde la app, sin IA (Beta 3.0, F6 mover y F7 ajustar).

Hasta aquí la única forma de cambiar algo era regenerar la semana (~0,30 USD) o pedírselo
al coach. Lo más pedido es mover: «el martes no puedo, hazlo el jueves». Esto lo hace
sobre el plan guardado, en código, y dice lo que conviene saber sin impedirlo.

Decisiones del responsable (2026-10-10, ampliadas el 2026-10-21: «que se pueda recalendarizar
una sesión de cualquier semana activa, no importa si fue ayer»):

- **Cualquier sesión de una semana ACTIVA** (la que tiene hoy o una posterior ya escrita), también
  la de un día de esta semana que ya pasó («ayer no pude»), **a hoy o a un día posterior** de una
  semana activa, aunque sea la otra. Una semana que ya terminó no se toca: su expediente se cerró
  al generar la siguiente.
- **No se mueven** el día de una carrera (ni a él). Una sesión de un día pasado se avisa: si ya la
  hizo, moverla la deja sin emparejar con lo que hizo.
- **Destino ocupado**: se intercambian (por defecto) o queda como segunda sesión del día
  (`modo="doble"`, como mucho dos). Un destino de descanso es un intercambio con el
  descanso.
- **Avisa pero deja**: un día que su tabla no da para ese deporte, dos días de calidad
  seguidos, calidad la víspera de una carrera. Lo que el atleta decide es suyo.
- Los textos de la sesión no cambian; el día lleva `edited.moved_from` y la pantalla dice
  «Movida por ti desde el martes 13».

Trabaja sobre el plan TAL COMO SE GUARDA (diccionarios), para no perder ningún campo de un
plan viejo al reescribirlo, y valida el resultado con el esquema antes de devolverlo.
El reloj no se toca aquí: devuelve `cambios_registro` para `upload_ledger.mover`.
"""

from __future__ import annotations

import copy
from datetime import date, datetime
from typing import Any

DIAS_ES = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]
_DIAS_MINUSCULA = [d.lower() for d in DIAS_ES]
# Lo que cuenta como calidad para avisar de dos días seguidos. Sin `neuromuscular`: unas
# rectas no cansan como unas series, y en la semana real del atleta 2 (12-18 oct) avisaba
# de «calidad seguida» por las rectas del viernes junto a sus 3x3' del jueves.
_CALIDAD = {"tempo_threshold", "vo2max", "repeticiones", "hill_repeats", "race_day"}
# Qué fila de la rejilla del perfil (`sport_days`) da cada deporte del plan.
_FILAS_DE_LA_REJILLA = {"run": ("Correr", "Trail"), "bike": ("Bici",), "swim": ("Nadar",),
                        "strength": ("Fuerza",)}
_NOMBRE_DEL_DEPORTE = {"run": "correr", "bike": "bici", "swim": "nadar", "strength": "fuerza",
                       "walk": "caminar", "brick": "combinado"}


class EdicionNoPermitida(ValueError):
    """Lo que no se puede hacer, dicho para el atleta."""


def _fecha(valor: str) -> date:
    return datetime.strptime(str(valor), "%Y-%m-%d").date()


def _dia_para_el_atleta(fecha: str) -> str:
    d = _fecha(fecha)
    return f"{_DIAS_MINUSCULA[d.weekday()]} {d.day}"


def leer_clave(clave: str) -> tuple[str, str]:
    fecha, _, franja = str(clave or "").partition("|")
    try:
        _fecha(fecha)
    except ValueError as e:
        raise EdicionNoPermitida("No encuentro esa sesión en tu plan.") from e
    return fecha, franja or "unica"


def _es_calidad(dia: dict) -> bool:
    return not dia.get("is_rest") and dia.get("intensity_class") in _CALIDAD


def _orden(dia: dict) -> tuple:
    franjas = {"am": 0, "unica": 1, "pm": 2, "s3": 3, "s4": 4}
    return (dia.get("date", ""), franjas.get(dia.get("session_slot") or "unica", 9))


def _descanso(fecha: str, de_donde: str) -> dict:
    return {
        "date": fecha, "day_name_es": DIAS_ES[_fecha(fecha).weekday()], "is_rest": True, "sport": "rest",
        "focus": "Descanso", "purpose": f"Moviste tu sesión al {de_donde}: hoy queda libre.", "adaptation": "",
        "steps": [], "total_duration_minutes": 0, "session_slot": "unica",
        "edited": {"by": "atleta", "rest_from_move": True},
    }


def _poner_en(dia: dict, fecha: str, franja: str, origen: str) -> None:
    """La sesión `dia` pasa a `fecha|franja`, recordando de dónde venía la primera vez."""
    editado = dict(dia.get("edited") or {})
    primera = editado.get("moved_from") or origen
    if primera == fecha:
        editado.pop("moved_from", None)  # vuelve a su día: ya no está movida
    else:
        editado["moved_from"] = primera
    editado["by"] = "atleta"
    # De vuelta en su día y sin otro cambio, la sesión es otra vez la del coach: no queda un «by» suelto.
    if set(editado) == {"by"}:
        editado = None
    dia.update(date=fecha, session_slot=franja, day_name_es=DIAS_ES[_fecha(fecha).weekday()], edited=editado)


def _avisos(semana: list[dict], movidas: list[dict], rejilla: dict | None) -> list[str]:
    avisos = []
    por_fecha: dict[str, list[dict]] = {}
    for d in semana:
        por_fecha.setdefault(d["date"], []).append(d)
    for d in movidas:
        if d.get("is_rest"):
            continue
        fecha = _fecha(d["date"])
        nombre_dia = DIAS_ES[fecha.weekday()]
        filas = _FILAS_DE_LA_REJILLA.get(d.get("sport"), ())
        if rejilla and filas and not any((rejilla.get(f) or {}).get(nombre_dia) for f in filas):
            avisos.append(f"Tu tabla no tiene {_NOMBRE_DEL_DEPORTE.get(d['sport'], d['sport'])} el "
                          f"{nombre_dia.lower()}: lo decides tú, sólo te lo recordamos.")
        if _es_calidad(d) and d.get("intensity_class") != "race_day":
            for delta, cual in ((-1, "el día anterior"), (1, "el día siguiente")):
                vecino = date.fromordinal(fecha.toordinal() + delta).isoformat()
                otros = [x for x in por_fecha.get(vecino, []) if x is not d]
                if any(x.get("intensity_class") == "race_day" for x in otros) and delta == 1:
                    avisos.append("Queda una sesión de calidad la víspera de tu carrera: llegarás con menos chispa.")
                elif any(_es_calidad(x) for x in otros):
                    avisos.append(f"Quedan dos días de calidad seguidos ({cual} también lo es): "
                                  "la segunda suele salir peor.")
    return list(dict.fromkeys(avisos))


def _semanas_activas(semanas: list[dict], hoy: str) -> list[dict]:
    """Las semanas que no han terminado: la de hoy y las siguientes ya escritas."""
    return [w for w in semanas if any((d.get("date") or "") >= hoy for d in w.get("days") or [])]


def _clave(d: dict) -> tuple[str, str]:
    return d.get("date"), d.get("session_slot") or "unica"


def mover(plan: dict, origen: str, fecha_destino: str, *, hoy: str, modo: str = "intercambiar",
          rejilla: dict | None = None) -> dict[str, Any]:
    """Mueve la sesión `origen` ("fecha|franja") a `fecha_destino`, en su semana o en la otra activa.

    Devuelve `{plan, cambios_registro, avisos, dias_tocados, resumen}`. No toca el plan que
    recibe. Lanza `EdicionNoPermitida` con el motivo para el atleta.
    """
    if modo not in ("intercambiar", "doble"):
        raise EdicionNoPermitida("No sé hacer ese cambio.")
    fecha_origen, franja_origen = leer_clave(origen)
    try:
        destino = _fecha(fecha_destino).isoformat()
    except ValueError as e:
        raise EdicionNoPermitida("Esa fecha no es válida.") from e
    nuevo = copy.deepcopy(plan or {})
    activas = _semanas_activas(nuevo.get("weeks") or [], hoy)
    semana_o = next((w for w in nuevo.get("weeks") or []
                     if any(_clave(d) == (fecha_origen, franja_origen) for d in w.get("days") or [])), None)
    if semana_o is None:
        raise EdicionNoPermitida("No encuentro esa sesión en tu plan.")
    if not any(w is semana_o for w in activas):
        raise EdicionNoPermitida("Esa semana ya terminó: lo que pasó en ella se queda como está.")
    sesion = next(d for d in semana_o["days"] if _clave(d) == (fecha_origen, franja_origen))
    if sesion.get("is_rest"):
        raise EdicionNoPermitida("Un descanso no se mueve: mueve la sesión que quieres hacer ese día.")
    if sesion.get("intensity_class") == "race_day":
        raise EdicionNoPermitida("El día de tu carrera no se mueve desde aquí.")
    if destino == fecha_origen:
        raise EdicionNoPermitida("La sesión ya está ese día.")
    if destino < hoy:
        raise EdicionNoPermitida("Se mueve a hoy o a un día que viene: un día que ya pasó no se puede elegir.")
    semana_d = next((w for w in activas if any(d.get("date") == destino for d in w.get("days") or [])), None)
    if semana_d is None:
        raise EdicionNoPermitida("Ese día no está en tus semanas activas: elige uno de esta semana o de la siguiente.")

    dias_o, dias_d = semana_o["days"], semana_d["days"]
    en_destino = [d for d in dias_d if d.get("date") == destino and not d.get("is_rest")]
    if any(d.get("intensity_class") == "race_day" for d in en_destino):
        raise EdicionNoPermitida("Ese día es tu carrera: no se le añade ni se le cambia nada desde aquí.")

    def _llevar(dia: dict, de: list[dict], a: list[dict]) -> None:
        if de is not a:
            de[:] = [x for x in de if x is not dia]
            a.append(dia)

    cambios: dict[str, str] = {}
    tocadas: list[dict] = [sesion]
    clave_origen = f"{fecha_origen}|{franja_origen}"

    if not en_destino:
        # Al descanso: la sesión ocupa el día y el descanso se va.
        dias_d[:] = [d for d in dias_d if not (d.get("date") == destino and d.get("is_rest"))]
        _poner_en(sesion, destino, "unica", fecha_origen)
        _llevar(sesion, dias_o, dias_d)
        cambios[clave_origen] = f"{destino}|unica"
    elif modo == "intercambiar":
        if len(en_destino) > 1:
            raise EdicionNoPermitida("Ese día tiene dos sesiones: elige otro día para intercambiar.")
        otra = en_destino[0]
        franja_otra = otra.get("session_slot") or "unica"
        if fecha_origen < hoy:
            raise EdicionNoPermitida("Esa sesión era de un día que ya pasó: intercambiarla mandaría la otra al "
                                     "pasado. Elige «hacer las dos» o un día libre.")
        _poner_en(sesion, destino, franja_otra, fecha_origen)
        _poner_en(otra, fecha_origen, franja_origen, destino)
        _llevar(sesion, dias_o, dias_d)
        _llevar(otra, dias_d, dias_o)
        cambios[clave_origen] = f"{destino}|{franja_otra}"
        cambios[f"{destino}|{franja_otra}"] = clave_origen
        tocadas.append(otra)
    else:  # doble
        if len(en_destino) > 1:
            raise EdicionNoPermitida("Ese día ya tiene dos sesiones: como mucho dos por día.")
        otra = en_destino[0]
        franja_otra = otra.get("session_slot") or "unica"
        if franja_otra == "unica":
            otra["session_slot"] = "am"
            cambios[f"{destino}|unica"] = f"{destino}|am"
            tocadas.append(otra)
        franja_nueva = "pm" if (otra.get("session_slot") or "unica") != "pm" else "am"
        _poner_en(sesion, destino, franja_nueva, fecha_origen)
        _llevar(sesion, dias_o, dias_d)
        cambios[clave_origen] = f"{destino}|{franja_nueva}"

    # El día que se queda sin nada lleva su descanso: un hueco en la semana se lee como un fallo.
    if not any(d.get("date") == fecha_origen for d in dias_o):
        dias_o.append(_descanso(fecha_origen, _dia_para_el_atleta(destino)))
    dias_o.sort(key=_orden)
    dias_d.sort(key=_orden)

    from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload
    WeeklyPlanPayload.model_validate(nuevo)  # un plan que no valida no se guarda

    juntas = dias_o + ([] if dias_d is dias_o else dias_d)
    avisos = _avisos(juntas, tocadas, rejilla)
    if fecha_origen < hoy:
        avisos.insert(0, f"Era del {_dia_para_el_atleta(fecha_origen)}: si ya la hiciste, déjala donde está "
                         "(moverla la separa de lo que registró tu reloj).")
    nombre = sesion.get("focus") or _NOMBRE_DEL_DEPORTE.get(sesion.get("sport"), "la sesión")
    return {
        "plan": nuevo,
        "cambios_registro": cambios,
        "avisos": list(dict.fromkeys(avisos)),
        "dias_tocados": sorted({fecha_origen, destino}),
        "resumen": f"«{nombre}» pasa del {_dia_para_el_atleta(fecha_origen)} al {_dia_para_el_atleta(destino)}"
                   + (" (intercambiada)" if modo == "intercambiar" and len(tocadas) > 1 and en_destino else "")
                   + (" como segunda sesión del día" if modo == "doble" and en_destino else "") + ".",
    }
