"""Ajustar los pasos de una sesión y volver a la del coach, sin IA (Beta 3.0, F7).

Decisión del responsable (2026-10-10): ajustar los pasos y añadir desde plantillas, 1 a 1
con lo que va al reloj y sin IA. En v1, carrera, bici, caminata y natación; la fuerza y las
sesiones combinadas no.

El editor trabaja con ELEMENTOS, no con el árbol de Garmin:

    paso    {"tipo": "paso", step_type, duracion: {tipo, valor}, objetivo: {tipo, zona, bajo, alto}}
    serie   {"tipo": "serie", repeticiones, trabajo: <paso>, recuperacion: <paso> | None}
    bloque  {"tipo": "bloque", indice, repeticiones}  -- una repetición con otra forma (tres pasos
            dentro, o una repetición dentro de otra): sólo se cambian las repeticiones.

`aplanar` y `expandir` son inversas en lo que no cambia: un elemento igual al del coach vuelve
a ser EXACTAMENTE su paso, con su texto y todo lo demás. Lo que cambió se reescribe desde los
campos (`rendering/describir_paso.py`) y se valida con el mismo guardián de unidades que un
paso escrito por la IA (`WorkoutStep._check_shape_and_units`): un ritmo en minutos en vez de
segundos no llega al reloj aunque lo escriba el atleta.

Los avisos no impiden (como en mover): calidad sin ritmo, un ritmo fuera de sus propias
bandas, una serie sin recuperación, ±30 % de la sesión que escribió el coach.
"""

from __future__ import annotations

import copy
import json
import re
from typing import Any

from services.ai.edicion_de_sesion import EdicionNoPermitida, _dia_para_el_atleta, leer_clave

DEPORTES_QUE_SE_AJUSTAN = ("run", "bike", "swim", "walk")
_CAMPOS_EXTRA = ("stroke", "equipment", "drill", "leg_sport", "exercise_name")
_MAX_SEGUNDOS_POR_PASO = 6 * 3600
_MAX_METROS_POR_PASO = 100_000
_CALIDAD_CON_RITMO = ("tempo_threshold", "vo2max", "repeticiones")


# --- De pasos a elementos y de vuelta -------------------------------------------------

def _a_elemento(p: dict) -> dict[str, Any]:
    return {
        "step_type": p.get("step_type") or "main",
        "duracion": {"tipo": p.get("duration_type"), "valor": p.get("duration_value")},
        "objetivo": {"tipo": p.get("target_type") or "none", "zona": p.get("target_zone"),
                     "bajo": p.get("target_low"), "alto": p.get("target_high")},
        "extra": {k: p[k] for k in _CAMPOS_EXTRA if p.get(k)},
    }


def aplanar(dia: dict) -> list[dict[str, Any]]:
    """Los pasos de un día como elementos del editor."""
    salida = []
    for i, p in enumerate(dia.get("steps") or []):
        hijos = p.get("substeps") or []
        if not hijos and (p.get("repeat_count") or 1) <= 1:
            salida.append({"tipo": "paso", **_a_elemento(p), "descripcion": p.get("description", "")})
        elif not hijos:
            salida.append({"tipo": "serie", "repeticiones": p.get("repeat_count"), "trabajo": _a_elemento(p),
                           "recuperacion": None, "descripcion": p.get("description", "")})
        elif (any(h.get("substeps") for h in hijos) or len(hijos) > 2
              or (len(hijos) == 2 and hijos[1].get("step_type") not in ("recovery", "rest"))):
            salida.append({"tipo": "bloque", "indice": i, "repeticiones": p.get("repeat_count"),
                           "descripcion": p.get("description", "")})
        else:
            salida.append({"tipo": "serie", "repeticiones": p.get("repeat_count"), "trabajo": _a_elemento(hijos[0]),
                           "recuperacion": _a_elemento(hijos[1]) if len(hijos) == 2 else None,
                           "descripcion": p.get("description", "")})
    return salida


def _normal(v):
    """Los números como números: el plan llega de PHP con `600.0` y vuelve del navegador con
    `600`, y compararlos como texto reescribía los pasos que el atleta no tocó (prueba en el
    navegador del 2026-10-11: «10' calentamiento en Z1–Z2» pasaba a «10' de calentamiento»)."""
    if isinstance(v, bool) or v is None:
        return v
    # PHP decodifica `{}` como lista vacía y la devuelve como `[]`: vacío es vacío.
    if isinstance(v, (dict, list)) and not v:
        return None
    if isinstance(v, (int, float)):
        return int(v) if float(v).is_integer() else round(float(v), 3)
    if isinstance(v, dict):
        return {k: _normal(x) for k, x in v.items()}
    if isinstance(v, list):
        return [_normal(x) for x in v]
    return v


def _firma(elemento: dict) -> str:
    """Lo que cuenta para saber si un elemento cambió: todo menos los textos."""
    limpio = {k: v for k, v in (elemento or {}).items() if k not in ("descripcion", "nombre", "nota")}
    return json.dumps(_normal(limpio), sort_keys=True, default=str)


# Lo que el atleta escribe de un paso (2026-10-23: «una serie nueva sale como "100 m" a secas: no se puede
# elegir el drill ni poner crol, patada o una nota»). Va al final del texto del paso, que es lo que se lee en la
# app y en el reloj; la duración y el objetivo siguen saliendo de los campos.
_MAX_NOTA = 120


def _con_nota(paso: dict, nota: str) -> dict:
    nota = " ".join(str(nota or "").split())[:_MAX_NOTA]
    if not nota:
        return paso
    texto = paso.get("description") or ""
    if nota.lower() not in texto.lower():
        paso["description"] = f"{texto} · {nota}" if texto else nota
    # En una serie, el hijo de trabajo también: es lo que el reloj enseña en cada repetición.
    hijos = paso.get("substeps") or []
    if hijos:
        trabajo = hijos[0]
        texto_h = trabajo.get("description") or ""
        if nota.lower() not in texto_h.lower():
            trabajo["description"] = f"{texto_h} · {nota}" if texto_h else nota
    return paso


def _numero(v, nombre: str) -> float | None:
    if v in (None, ""):
        return None
    try:
        return float(v)
    except (TypeError, ValueError) as e:
        raise EdicionNoPermitida(f"{nombre} no es un número.") from e


def _a_paso(e: dict, deporte: str, orden: int) -> dict[str, Any]:
    from services.ai.rendering import describir_paso

    dur = e.get("duracion") or {}
    tipo = dur.get("tipo")
    if tipo not in ("time", "distance", "lap_button"):
        raise EdicionNoPermitida("Cada paso termina por tiempo, por distancia o al pulsar vuelta.")
    valor = None if tipo == "lap_button" else _numero(dur.get("valor"), "La duración")
    if tipo != "lap_button":
        tope = _MAX_SEGUNDOS_POR_PASO if tipo == "time" else _MAX_METROS_POR_PASO
        if not valor or valor <= 0 or valor > tope:
            raise EdicionNoPermitida("Una duración tiene que ser mayor que cero y la de un paso, no la de una semana.")
    obj = e.get("objetivo") or {}
    t = obj.get("tipo") or "none"
    if deporte == "swim" or t not in ("hr_zone", "pace", "power"):
        t = "none"  # en natación el reloj no lleva objetivo (workout_builder)
    paso = {"step_order": orden, "step_type": e.get("step_type") or "main", "duration_type": tipo,
            "duration_value": valor, "repeat_count": 1, "target_type": t,
            "target_zone": None, "target_low": None, "target_high": None, **(e.get("extra") or {})}
    if t == "hr_zone" or (t == "power" and obj.get("zona") and not obj.get("bajo")):
        zona = _numero(obj.get("zona"), "La zona")
        if not zona or not 1 <= zona <= 5:
            raise EdicionNoPermitida("La zona va de 1 a 5.")
        paso["target_zone"] = int(zona)
    elif t in ("pace", "power"):
        bajo, alto = _numero(obj.get("bajo"), "El objetivo"), _numero(obj.get("alto"), "El objetivo")
        if not bajo or not alto:
            raise EdicionNoPermitida("Un objetivo de ritmo o de potencia necesita sus dos extremos.")
        paso["target_low"], paso["target_high"] = min(bajo, alto), max(bajo, alto)
    paso["description"] = describir_paso.paso(paso, deporte)
    return paso


def _textos_de_las_series(originales: list[dict]) -> dict[str, str]:
    """El texto del coach de cada trabajo y cada recuperación, por su firma.

    Cambiar 3×3' por 2×3' no puede borrar «3' trote muy suave en Z1» de una recuperación
    que no se tocó: el texto se reescribe sólo en lo que cambió.
    """
    textos = {}
    for p in originales:
        for h in p.get("substeps") or []:
            if not h.get("substeps"):
                textos.setdefault(_firma({k: v for k, v in _a_elemento(h).items() if k != "step_type"}),
                                  h.get("description", ""))
    return textos


def _sin_tipo(e: dict) -> str:
    return _firma({k: v for k, v in (e or {}).items() if k != "step_type"})


# El estilo, el material y el tipo de nado (2026-10-23). Cambiarlos no puede borrar el texto del coach: en el
# reloj real del atleta 2, poner pull buoy a «4x100m crol a 1:47–1:53/100m (2 largos)» dejó «100 m crol con
# pull buoy» y se perdió el ritmo. Si sólo cambia esto, el texto del coach se queda y se le añade lo nuevo.
_NADO = ("stroke", "equipment", "drill")


def _quitar_nado(x):
    if not isinstance(x, dict):
        return x
    salida = dict(x)
    if isinstance(salida.get("extra"), dict):
        salida["extra"] = {k: v for k, v in salida["extra"].items() if k not in _NADO}
    for k in ("trabajo", "recuperacion"):
        if isinstance(salida.get(k), dict):
            salida[k] = _quitar_nado(salida[k])
    return salida


def _poner_nado(paso: dict, extra: dict | None) -> None:
    for k in _NADO:
        v = (extra or {}).get(k) if isinstance(extra, dict) else None
        if v:
            paso[k] = v
        else:
            paso.pop(k, None)


def _con_nado(texto: str, paso: dict) -> str:
    from services.ai.rendering import describir_paso

    # Sólo lo que el texto todavía no dice («crol» ya estaba en «100m crol a 1:47…»).
    partes = [describir_paso._TIPO_DE_NADO.get((paso.get("drill") or "").lower(), ""),
              describir_paso._ESTILO.get((paso.get("stroke") or "").lower(), ""),
              describir_paso._MATERIAL.get((paso.get("equipment") or "").lower(), "")]
    palabras = " ".join(p for p in partes if p and p.lower() not in (texto or "").lower())
    if not palabras:
        return texto
    return f"{texto} · {palabras}" if texto else palabras


def _serie(e: dict, deporte: str, orden: int, textos: dict[str, str] | None = None,
           texto_de_la_serie: str = "") -> dict[str, Any]:
    from services.ai.rendering import describir_paso

    textos = textos or {}
    n = int(_numero(e.get("repeticiones"), "Las repeticiones") or 0)
    if n < 2:
        raise EdicionNoPermitida("Una serie necesita al menos 2 repeticiones; si es una, ponla como paso.")
    t = e.get("trabajo") or {}
    trabajo = _a_paso({**t, "step_type": t.get("step_type") if t.get("step_type") in ("interval", "main") else "interval"},
                      deporte, 1)
    rec = e.get("recuperacion")
    if not rec:
        return {**trabajo, "step_order": orden, "repeat_count": n,
                "description": describir_paso.serie(n, trabajo, None, deporte)}
    recuperacion = _a_paso({**rec, "step_type": "recovery"}, deporte, 2)
    trabajo_igual, rec_igual = _sin_tipo(t) in textos, _sin_tipo(rec) in textos
    if trabajo_igual:
        trabajo["description"] = textos[_sin_tipo(t)]
    if rec_igual:
        recuperacion["description"] = textos[_sin_tipo(rec)]
    # Sólo cambiaron las repeticiones: la línea del coach con el número nuevo.
    if trabajo_igual and rec_igual and re.match(r"^\s*\d+\s*[x×]", texto_de_la_serie or ""):
        linea = re.sub(r"^\s*\d+\s*[x×]", f"{n}x", texto_de_la_serie)
    else:
        linea = describir_paso.serie(n, trabajo, recuperacion, deporte)
    bloque = {"step_order": orden, "step_type": "repeat", "repeat_count": n, "substeps": [trabajo, recuperacion],
              "description": linea}
    if trabajo.get("leg_sport"):
        bloque["leg_sport"] = trabajo["leg_sport"]
    return bloque


def _bloque(e: dict, originales: list[dict], orden: int) -> dict[str, Any]:
    idx = e.get("indice")
    if not isinstance(idx, int) or not 0 <= idx < len(originales):
        raise EdicionNoPermitida("No encuentro ese bloque de la sesión.")
    n = int(_numero(e.get("repeticiones"), "Las repeticiones") or 0)
    if n < 2:
        raise EdicionNoPermitida("Un bloque de repeticiones necesita al menos 2.")
    paso = copy.deepcopy(originales[idx])
    if n != paso.get("repeat_count"):
        texto = paso.get("description") or ""
        paso["description"] = (re.sub(r"^\s*\d+\s*[x×]", f"{n}x", texto) if re.match(r"^\s*\d+\s*[x×]", texto)
                               else f"{n}x · {texto}")
        paso["repeat_count"] = n
    return paso


def expandir(elementos: list[dict], dia_original: dict) -> list[dict[str, Any]]:
    """Los elementos del editor como pasos guardables. Lo que no cambió, intacto."""
    from services.ai.langgraph.schemas.plan_outputs import WorkoutStep

    deporte = dia_original.get("sport") or ""
    originales = dia_original.get("steps") or []
    planos = aplanar(dia_original)
    firmas = [_firma(p) for p in planos]
    sin_nado = [_firma(_quitar_nado(p)) for p in planos]
    textos = _textos_de_las_series(originales)
    pasos = []
    for i, e in enumerate(elementos or []):
        orden = i + 1
        tipo = (e or {}).get("tipo")
        if tipo != "bloque" and _firma(e) in firmas:
            paso = copy.deepcopy(originales[firmas.index(_firma(e))])
        elif tipo in ("paso", "serie") and deporte == "swim" and _firma(_quitar_nado(e)) in sin_nado:
            # Sólo cambió el estilo, el material o el tipo de nado: el paso del coach, con eso puesto.
            paso = copy.deepcopy(originales[sin_nado.index(_firma(_quitar_nado(e)))])
            hijos = paso.get("substeps") or []
            if tipo == "paso":
                _poner_nado(paso, e.get("extra"))
                paso["description"] = _con_nado(paso.get("description") or "", paso)
            elif hijos:
                _poner_nado(hijos[0], (e.get("trabajo") or {}).get("extra"))
                hijos[0]["description"] = _con_nado(hijos[0].get("description") or "", hijos[0])
                paso["description"] = _con_nado(paso.get("description") or "", hijos[0])
            else:
                _poner_nado(paso, (e.get("trabajo") or {}).get("extra"))
                paso["description"] = _con_nado(paso.get("description") or "", paso)
        elif tipo == "paso":
            paso = _a_paso(e, deporte, orden)
        elif tipo == "serie":
            texto = planos[i].get("descripcion", "") if i < len(planos) and planos[i].get("tipo") == "serie" else ""
            paso = _serie(e, deporte, orden, textos, texto)
        elif tipo == "bloque":
            paso = _bloque(e, originales, orden)
        else:
            raise EdicionNoPermitida("No entiendo uno de los pasos.")
        paso = _con_nota(paso, (e or {}).get("nota"))
        paso["step_order"] = orden
        try:
            WorkoutStep.model_validate(paso)
        except ValueError as ex:
            raise EdicionNoPermitida(f"El paso {orden} no es válido: {_motivo(ex)}") from ex
        pasos.append(paso)
    return pasos


def _motivo(ex: Exception) -> str:
    """El motivo de pydantic sin su envoltorio técnico («Value error, …  [type=…]»)."""
    texto = str(ex)
    m = re.search(r"Value error,\s*(.+?)(?:\s*\[type=|$)", texto, re.S)
    motivo = (m.group(1) if m else texto.splitlines()[0]).strip()
    if "fuera del rango razonable" in motivo and "segundos por kilómetro" in motivo:
        return "un ritmo se escribe en minutos y segundos por kilómetro (por ejemplo, 5:20)."
    return motivo[:200]


# --- Avisos ---------------------------------------------------------------------------

def _minutos(pasos: list[dict]) -> float | None:
    from services.ai.langgraph.nodes.weekly_planner_node import _minutos_de_los_pasos
    from services.ai.langgraph.schemas.plan_outputs import WorkoutStep

    return _minutos_de_los_pasos([WorkoutStep.model_validate(p) for p in pasos])


def _avisos(dia: dict, elementos: list[dict], minutos_antes: float | None, minutos_ahora: float | None,
            prescripcion: dict | None) -> list[str]:
    from services.ai.prescription import format_pace

    avisos = []
    deporte = dia.get("sport")
    trabajos = []
    for e in elementos:
        if e.get("tipo") == "paso" and e.get("step_type") not in ("warmup", "cooldown", "recovery", "rest"):
            trabajos.append(e)
        elif e.get("tipo") == "serie":
            trabajos.append(e.get("trabajo") or {})
            if deporte in ("run", "bike") and not e.get("recuperacion"):
                avisos.append("Hay una serie sin recuperación: el reloj encadenará las repeticiones sin pausa.")
    con_ritmo = [t for t in trabajos if (t.get("objetivo") or {}).get("tipo") == "pace"]
    if deporte == "run" and dia.get("intensity_class") in _CALIDAD_CON_RITMO and not con_ritmo:
        avisos.append("Es una sesión de calidad y ningún paso lleva ritmo: el reloj no te dirá si vas rápido.")
    bandas = [b for b in ((prescripcion or {}).get("pace_bands") or {}).values()
              if isinstance(b, (list, tuple)) and len(b) == 2 and all(b)]
    if deporte == "run" and bandas:
        rapido, lento = min(b[0] for b in bandas), max(b[1] for b in bandas)
        for t in con_ritmo:
            o = t["objetivo"]
            if o.get("bajo") and float(o["bajo"]) < rapido * 0.95:
                avisos.append(f"Un ritmo de {format_pace(float(o['bajo']))}/km es más rápido que el más rápido de tus "
                              f"ritmos de entrenamiento ({format_pace(rapido)}/km).")
            if o.get("alto") and float(o["alto"]) > lento * 1.05:
                avisos.append(f"Un ritmo de {format_pace(float(o['alto']))}/km es más lento que tu ritmo de "
                              f"recuperación ({format_pace(lento)}/km).")
    if minutos_antes and minutos_ahora:
        cambio = (minutos_ahora - minutos_antes) / minutos_antes
        if abs(cambio) > 0.30:
            avisos.append(f"Queda un {abs(cambio) * 100:.0f} % más {'larga' if cambio > 0 else 'corta'} que la que te "
                          f"escribió el coach ({minutos_antes:.0f}' → {minutos_ahora:.0f}').")
    return list(dict.fromkeys(avisos))


# --- Las tres operaciones -------------------------------------------------------------

def _dia_por_clave(plan: dict, clave: str) -> dict:
    fecha, franja = leer_clave(clave)
    for w in plan.get("weeks") or []:
        for d in w.get("days") or []:
            if d.get("date") == fecha and (d.get("session_slot") or "unica") == franja and not d.get("is_rest"):
                return d
    raise EdicionNoPermitida("No encuentro esa sesión en tu plan.")


def _es_fuerza(dia: dict) -> bool:
    """La fuerza con su bloque de ejercicios (2026-10-23): se ajusta con `ajuste_de_fuerza`."""
    return dia.get("sport") == "strength" and isinstance(dia.get("strength_block"), dict)


def _se_puede_ajustar(dia: dict, hoy: str) -> None:
    if dia.get("date", "") < hoy:
        raise EdicionNoPermitida("Lo que ya pasó se queda como está.")
    if dia.get("intensity_class") == "race_day":
        raise EdicionNoPermitida("El día de tu carrera va hasta pulsar vuelta: no se ajusta desde aquí.")
    if _es_fuerza(dia):
        return
    if dia.get("sport") not in DEPORTES_QUE_SE_AJUSTAN:
        raise EdicionNoPermitida("Las sesiones combinadas todavía no se ajustan desde aquí.")
    if dia.get("garmin_exportable") is False:
        raise EdicionNoPermitida("Esta sesión va por instrucciones escritas, no por pasos del reloj.")


def editor(plan: dict, clave: str, *, hoy: str, prescripcion: dict | None = None) -> dict[str, Any]:
    """Lo que necesita la pantalla para ajustar una sesión: sus elementos y las plantillas."""
    from services.ai import ajuste_de_fuerza
    from services.ai.plantillas_de_paso import plantillas

    dia = _dia_por_clave(plan, clave)
    _se_puede_ajustar(dia, hoy)
    if _es_fuerza(dia):
        return ajuste_de_fuerza.editor(dia)
    return {"deporte": dia.get("sport"), "elementos": aplanar(dia),
            "plantillas": plantillas(dia.get("sport"), prescripcion), "minutos": _minutos(dia.get("steps") or [])}


def ajustar(plan: dict, clave: str, elementos: list[dict], *, hoy: str, prescripcion: dict | None = None,
            original: dict | None = None, grupos: list[dict] | None = None) -> dict[str, Any]:
    """Cambia los pasos de una sesión. `original` es la del coach (para el aviso de ±30 %).

    En la fuerza, `grupos` (los del editor de fuerza) en vez de `elementos`.
    """
    nuevo = copy.deepcopy(plan or {})
    dia = _dia_por_clave(nuevo, clave)
    _se_puede_ajustar(dia, hoy)
    if _es_fuerza(dia):
        from services.ai import ajuste_de_fuerza
        from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload

        r = ajuste_de_fuerza.ajustar(dia, grupos or [], original)
        WeeklyPlanPayload.model_validate(nuevo)
        return {"plan": nuevo, "cambios_registro": {}, "dias_tocados": [dia["date"]], **r}
    if not elementos:
        raise EdicionNoPermitida("Una sesión necesita al menos un paso. Si no la vas a hacer, muévela.")
    base = original or dia
    antes = _minutos(base.get("steps") or []) or base.get("total_duration_minutes")
    dia["steps"] = expandir(elementos, dia)
    ahora = _minutos(dia["steps"])
    if ahora is not None:
        dia["total_duration_minutes"] = round(ahora, 1)
    dia["edited"] = {**(dia.get("edited") or {}), "adjusted": True, "by": "atleta"}

    from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload
    WeeklyPlanPayload.model_validate(nuevo)
    cuanto = f" ({antes:.0f}' → {ahora:.0f}')" if antes and ahora and round(antes) != round(ahora) else ""
    return {"plan": nuevo, "cambios_registro": {}, "dias_tocados": [dia["date"]],
            "avisos": _avisos(dia, elementos, antes, ahora, prescripcion),
            "resumen": f"«{dia.get('focus') or 'La sesión'}» del {_dia_para_el_atleta(dia['date'])} queda ajustada{cuanto}."}


_CAMPOS_DE_POSICION = ("date", "day_name_es", "session_slot")


def revertir(plan: dict, clave: str, original: dict | None, *, hoy: str) -> dict[str, Any]:
    """Vuelve a los pasos y textos que escribió el coach.

    El DÍA se queda donde está: si el atleta la movió, volver al día es «Mover». Nada se
    borra del registro de cambios (PHP marca `reverted_at`).
    """
    if not isinstance(original, dict) or not original:
        raise EdicionNoPermitida("Esta sesión no se ha cambiado: ya es la que te escribió el coach.")
    nuevo = copy.deepcopy(plan or {})
    dia = _dia_por_clave(nuevo, clave)
    if dia.get("date", "") < hoy:
        raise EdicionNoPermitida("Lo que ya pasó se queda como está.")
    movida = (dia.get("edited") or {}).get("moved_from")
    posicion = {k: dia.get(k) for k in _CAMPOS_DE_POSICION}
    dia.clear()
    dia.update(copy.deepcopy(original))
    dia.update(posicion)
    dia.pop("edited", None)
    if movida and movida != dia["date"]:
        dia["edited"] = {"moved_from": movida, "by": "atleta"}

    from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload
    WeeklyPlanPayload.model_validate(nuevo)
    return {"plan": nuevo, "cambios_registro": {}, "dias_tocados": [dia["date"]], "avisos": [],
            "resumen": f"«{dia.get('focus') or 'La sesión'}» vuelve a ser la que te escribió el coach."}
