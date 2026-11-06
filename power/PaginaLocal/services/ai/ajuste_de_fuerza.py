"""Ajustar una sesión de fuerza ya generada, sin IA (decisión del responsable, 2026-10-23).

«Editar entreno de fuerza cuando ya está generado: agregar uno de los del catálogo, cambiar uno por otro o
agregarlo al final.» Y: «si lo edita en su semana, sólo se edita en la semana; si lo edita desde el módulo de
fuerza, se edita permanente». Esto es lo primero: cambia ESA sesión de ESA semana. Su programa no se toca.

El editor trabaja con GRUPOS (los de `StrengthBlock.groups`: ejercicios seguidos, ronda a ronda) y cada
ejercicio con lo que se lee en el gimnasio: series, repeticiones (texto: «8-10», «30 s», «12 por lado»),
carga, descanso y una pista. Un ejercicio del coach que no cambia vuelve EXACTAMENTE como estaba (con su id,
que sigue al mismo ejercicio en su registro); uno que se ajusta conserva su id; uno nuevo o cambiado sale del
catálogo (`exercise_catalog`), con su nombre de Garmin si lo tiene verificado, y sin id: es otro ejercicio.

Los avisos no impiden, como al ajustar una carrera: una sesión que queda un 30 % más larga o más corta.
"""

from __future__ import annotations

import copy
import json
from typing import Any

from services.ai.edicion_de_sesion import EdicionNoPermitida, _dia_para_el_atleta

_MAX_GRUPOS = 12
_MAX_POR_GRUPO = 6
_MAX_EJERCICIOS = 30
# Lo que dura una serie mientras se hace, para estimar los minutos (no va al reloj: la fuerza va por
# repeticiones). Sólo se usa en proporción a la sesión del coach.
_TRABAJO_POR_SERIE_S = 45
_DESCANSO_POR_DEFECTO_S = 90
_EDITABLES = ("sets", "reps", "load", "rest_seconds", "cues")
# Cómo empieza el motivo de lo que cambió el ATLETA (el paso de fuerza del coach escribe sus propias etiquetas).
_POR_TI = ("Lo cambiaste tú", "Lo añadiste tú", "Lo ajustaste tú")


def _texto(v, tope: int) -> str:
    return " ".join(str(v or "").split())[:tope]


def _entero(v, nombre: str, minimo: int, maximo: int, vacio_vale: bool = False) -> int | None:
    if v in (None, "") and vacio_vale:
        return None
    try:
        n = int(float(v))
    except (TypeError, ValueError) as e:
        raise EdicionNoPermitida(f"{nombre} no es un número.") from e
    if not minimo <= n <= maximo:
        raise EdicionNoPermitida(f"{nombre} va de {minimo} a {maximo}.")
    return n


def _grupos_del_bloque(bloque: dict) -> list[dict]:
    """Los grupos de un bloque guardado, venga como venga (un plan sin grupos: uno por ejercicio)."""
    grupos = [g for g in (bloque.get("groups") or []) if isinstance(g, dict)]
    if grupos:
        return grupos
    return [{"id": f"g{i + 1}", "label": "", "rest_between_rounds_s": e.get("rest_seconds"), "exercises": [e]}
            for i, e in enumerate(bloque.get("exercises") or []) if isinstance(e, dict)]


def _slug(ejercicio: dict) -> str | None:
    from services.ai.exercise_catalog import resolver

    return resolver(ejercicio.get("name"), ejercicio.get("video_key"))


def _al_editor(e: dict, clave: str) -> dict[str, Any]:
    return {"clave": clave, "id": e.get("id") or "", "nombre": e.get("name") or "", "slug": _slug(e),
            "series": e.get("sets"), "reps": e.get("reps") or "", "carga": e.get("load") or "",
            "descanso": e.get("rest_seconds"), "pista": e.get("cues") or "", "origen": "coach",
            "cambio": e.get("change") or "", "por_ti": str(e.get("change_reason") or "").startswith(_POR_TI)}


def editor(dia: dict) -> dict[str, Any]:
    """La sesión de fuerza como la edita la pantalla: grupos con sus ejercicios."""
    bloque = dia.get("strength_block") or {}
    grupos = []
    for gi, g in enumerate(_grupos_del_bloque(bloque)):
        grupos.append({
            "id": g.get("id") or f"g{gi + 1}", "descanso_rondas": g.get("rest_between_rounds_s"),
            "descanso_ejercicios": g.get("rest_between_exercises_s"),
            "ejercicios": [_al_editor(e, f"{gi}.{ei}") for ei, e in enumerate(g.get("exercises") or [])
                           if isinstance(e, dict)],
        })
    return {"deporte": "strength", "grupos": grupos, "minutos": dia.get("total_duration_minutes"),
            "calentamiento": bloque.get("warmup") or "", "vuelta_a_la_calma": bloque.get("cooldown") or ""}


def _del_catalogo(slug: str | None) -> tuple[str, str] | None:
    """(nombre, familia) de un ejercicio del catálogo de fuerza o movilidad, o None."""
    from services.ai.exercise_catalog import EJERCICIOS

    fila = EJERCICIOS.get(slug or "")
    if not fila or fila[3] not in ("fuerza", "movilidad"):
        return None
    return fila[1], fila[4]


def _nuevo(e: dict, en_vez_de: str) -> dict[str, Any]:
    """Un ejercicio del catálogo, puesto por el atleta."""
    from services.ai.exercise_catalog import garmin_de

    catalogo = _del_catalogo(e.get("slug"))
    if catalogo is None:
        raise EdicionNoPermitida("Ese ejercicio no está en el catálogo: elígelo de la lista.")
    nombre, familia = catalogo
    categoria, garmin = garmin_de(nombre)
    return {
        "name": nombre, "sets": 1, "reps": "", "load": "", "rest_seconds": None, "cues": "",
        "targets": familia, "video_key": e.get("slug"), "garmin_category": categoria or "",
        "garmin_exercise": garmin or "", "id": "",
        "change": f"en vez de {en_vez_de}" if en_vez_de else "añadido por ti",
        "change_reason": "Lo cambiaste tú en esta semana." if en_vez_de else "Lo añadiste tú en esta semana.",
    }


def _ejercicio(e: dict, originales: dict[str, dict]) -> dict[str, Any]:
    if not isinstance(e, dict):
        raise EdicionNoPermitida("No entiendo uno de los ejercicios.")
    base = originales.get(str(e.get("clave") or "")) if e.get("origen") == "coach" else None
    if base is not None:
        salida = copy.deepcopy(base)
    else:
        salida = _nuevo(e, _texto(e.get("en_vez_de"), 60))
    valores = {
        "sets": _entero(e.get("series"), "Las series", 1, 10),
        "reps": _texto(e.get("reps"), 60),
        "load": _texto(e.get("carga"), 60),
        "rest_seconds": _entero(e.get("descanso"), "El descanso", 0, 600, vacio_vale=True),
        "cues": _texto(e.get("pista"), 200),
    }
    if not valores["reps"]:
        raise EdicionNoPermitida(f"Escribe las repeticiones de «{salida['name']}» (por ejemplo 8-10, 30 s o 12 por lado).")
    if base is not None:
        cambio = any(_igual(base.get(k)) != _igual(v) for k, v in valores.items())
        if not cambio:
            return salida  # el del coach, tal cual
        salida["change"] = "ajustado por ti"
        salida["change_reason"] = "Lo ajustaste tú en esta semana."
    salida.update(valores)
    return salida


def _igual(v):
    if v in (None, ""):
        return None
    if isinstance(v, (int, float)) or (isinstance(v, str) and v.strip().isdigit()):
        return int(float(v))
    return " ".join(str(v).split())


def _segundos(grupos: list[dict]) -> float:
    """Lo que dura el trabajo de la sesión, aproximado (rondas × ejercicios + descansos)."""
    total = 0.0
    for g in grupos:
        ejercicios = g.get("exercises") or []
        if not ejercicios:
            continue
        rondas = max(int(e.get("sets") or 1) for e in ejercicios)
        entre = g.get("rest_between_exercises_s") or 0
        descanso = g.get("rest_between_rounds_s")
        if descanso is None:
            descanso = max((e.get("rest_seconds") or _DESCANSO_POR_DEFECTO_S) for e in ejercicios)
        total += rondas * (len(ejercicios) * _TRABAJO_POR_SERIE_S + (len(ejercicios) - 1) * entre + descanso)
    return total


def construir(grupos_del_editor: list[dict], bloque_original: dict | None) -> list[dict]:
    """Los grupos del editor como `StrengthBlock.groups`. Lo que no cambió, intacto."""
    from services.ai.langgraph.schemas.plan_outputs import _letra_de_grupo

    if not isinstance(grupos_del_editor, list) or not grupos_del_editor:
        raise EdicionNoPermitida("Una sesión de fuerza necesita al menos un ejercicio.")
    if len(grupos_del_editor) > _MAX_GRUPOS:
        raise EdicionNoPermitida(f"Como mucho {_MAX_GRUPOS} grupos en una sesión.")
    originales_g = _grupos_del_bloque(bloque_original or {})
    originales = {f"{gi}.{ei}": e for gi, g in enumerate(originales_g)
                  for ei, e in enumerate(g.get("exercises") or []) if isinstance(e, dict)}
    salida, total = [], 0
    for gi, g in enumerate(grupos_del_editor):
        ejercicios = [x for x in (g or {}).get("ejercicios") or [] if x]
        if not ejercicios:
            continue
        if len(ejercicios) > _MAX_POR_GRUPO:
            raise EdicionNoPermitida(f"Como mucho {_MAX_POR_GRUPO} ejercicios seguidos en un grupo.")
        total += len(ejercicios)
        salida.append({
            "id": str((g or {}).get("id") or f"g{gi + 1}")[:24], "label": _letra_de_grupo(len(salida)),
            "rest_between_rounds_s": _entero((g or {}).get("descanso_rondas"), "El descanso entre rondas", 0, 600,
                                             vacio_vale=True),
            "rest_between_exercises_s": _entero((g or {}).get("descanso_ejercicios"), "El descanso entre ejercicios",
                                                0, 300, vacio_vale=True),
            "exercises": [_ejercicio(e, originales) for e in ejercicios],
        })
    if not salida:
        raise EdicionNoPermitida("Una sesión de fuerza necesita al menos un ejercicio.")
    if total > _MAX_EJERCICIOS:
        raise EdicionNoPermitida(f"Como mucho {_MAX_EJERCICIOS} ejercicios en una sesión.")
    return salida


def _minutos(base_minutos: float | None, grupos_antes: list[dict], grupos_ahora: list[dict]) -> float | None:
    antes, ahora = _segundos(grupos_antes), _segundos(grupos_ahora)
    if base_minutos and antes:
        return round(float(base_minutos) * ahora / antes, 1)
    return round(10 + ahora / 60, 1) if ahora else base_minutos


def aplicar(dia: dict, grupos_del_editor: list[dict], original: dict | None = None) -> dict[str, Any]:
    """Cambia la sesión de fuerza `dia` (en su sitio). Devuelve los minutos de antes y de ahora."""
    bloque = copy.deepcopy(dia.get("strength_block") or {})
    base = original or dia
    grupos_antes = _grupos_del_bloque(base.get("strength_block") or {})
    grupos = construir(grupos_del_editor, dia.get("strength_block") or {})
    bloque["groups"] = grupos
    bloque["exercises"] = [e for g in grupos for e in g["exercises"]]
    dia["strength_block"] = bloque
    antes = base.get("total_duration_minutes")
    ahora = _minutos(antes, grupos_antes, grupos)
    if ahora:
        dia["total_duration_minutes"] = ahora
        # El paso que guarda la sesión en el plan (la fuerza va al reloj desde su bloque, por repeticiones).
        for p in dia.get("steps") or []:
            if p.get("duration_type") == "time" and len(dia["steps"]) == 1:
                p["duration_value"] = round(ahora * 60)
    return {"antes": antes, "ahora": ahora}


def _lo_que_cambiaste(grupos_coach: list[dict], grupos: list[dict]) -> list[str]:
    """Lo que el atleta cambió respecto a la del coach, en frases cortas para el resumen.

    Las etiquetas del atleta se reconocen por su motivo: el paso de fuerza del coach también escribe «en vez
    de …» cuando él cambia un ejercicio, y eso no lo hizo el atleta.
    """
    ahora = [e for g in grupos for e in g.get("exercises") or []]
    frases, reemplazados = [], set()
    for e in ahora:
        if not str(e.get("change_reason") or "").startswith(_POR_TI):
            continue
        c = str(e.get("change") or "")
        if c.startswith("en vez de "):
            reemplazados.add(c[len("en vez de "):])
            frases.append(f"{c[len('en vez de '):]} → {e['name']}")
        elif c == "añadido por ti":
            frases.append(f"añadiste {e['name']}")
        else:
            frases.append(f"ajustaste {e['name']}")
    ids = [e.get("id") for e in ahora if e.get("id")]
    del_coach = [e for g in grupos_coach for e in g.get("exercises") or [] if isinstance(e, dict) and e.get("id")]
    for e in del_coach:
        if e["id"] not in ids and e.get("name") not in reemplazados:
            frases.append(f"quitaste {e['name']}")
    quedan = [e["id"] for e in del_coach if e["id"] in ids]
    if quedan != [i for i in ids if i in quedan]:
        frases.append("cambiaste el orden")
    return frases


def ajustar(dia: dict, grupos_del_editor: list[dict], original: dict | None = None) -> dict[str, Any]:
    """Ajusta la fuerza de esta semana; el día lleva `edited.adjusted`. Avisos que no impiden."""
    grupos_coach = copy.deepcopy(_grupos_del_bloque((original or dia).get("strength_block") or {}))
    cuanto = aplicar(dia, grupos_del_editor, original)
    dia["edited"] = {**(dia.get("edited") or {}), "adjusted": True, "by": "atleta"}
    avisos = []
    antes, ahora = cuanto["antes"], cuanto["ahora"]
    if antes and ahora and abs(ahora - antes) / antes > 0.30:
        avisos.append(f"Queda un {abs(ahora - antes) / antes * 100:.0f} % más {'larga' if ahora > antes else 'corta'} "
                      f"que la que te escribió el coach (~{antes:.0f}' → ~{ahora:.0f}').")
    sin_nombre = [e["name"] for g in dia["strength_block"]["groups"] for e in g["exercises"]
                  if e.get("change") and not e.get("garmin_exercise") and not e.get("id")]
    if sin_nombre:
        avisos.append(f"Tu reloj no tiene en su lista {', '.join(sin_nombre[:3])}{'…' if len(sin_nombre) > 3 else ''}: "
                      "lo verás como un paso con su nombre escrito y sus repeticiones.")
    cuanto_txt = f" (~{antes:.0f}' → ~{ahora:.0f}')" if antes and ahora and round(antes) != round(ahora) else ""
    frases = _lo_que_cambiaste(grupos_coach, dia["strength_block"]["groups"])
    que = f": {'; '.join(frases[:4])}{'…' if len(frases) > 4 else ''}" if frases else ""
    return {"avisos": avisos,
            "resumen": f"«{dia.get('focus') or 'La fuerza'}» del {_dia_para_el_atleta(dia['date'])} queda ajustada"
                       f"{cuanto_txt}{que}."}


def firma(bloque: dict | None) -> str:
    """Lo que el reloj lleva de una fuerza: para saber si cambió (upload_ledger.huella)."""
    grupos = _grupos_del_bloque(bloque or {})
    return json.dumps([[g.get("rest_between_rounds_s"), g.get("rest_between_exercises_s"),
                        [[e.get("name"), e.get("sets"), e.get("reps"), e.get("rest_seconds"), e.get("load")]
                         for e in g.get("exercises") or [] if isinstance(e, dict)]] for g in grupos],
                      ensure_ascii=False, sort_keys=True, default=str)
