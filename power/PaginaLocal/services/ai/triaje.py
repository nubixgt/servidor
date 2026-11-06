"""El origen probable de una consulta de ayuda (Beta 3.0, F3). Sin IA.

Cuando un atleta dice «mi reloj no muestra lo mismo» o «esta semana no tiene sentido», el
fallo puede estar en cinco sitios muy distintos, y cada uno se arregla en otro lado:

  perfil        lo que declaró no le deja a la IA hacerlo bien (rejilla vacía, una carrera
                en un día de descanso, el perfil a medias)
  garmin        el reloj lleva otra versión de la sesión, o la subida falló
  codificacion  la IA decidió una cosa y el codificador escribió otra (minutos, deporte o
                calidad distintos): el caso de la bici de 6 × 1' que llegó al reloj como
                6 × 50' (332' por 70', banco del codificador del 2026-09-24)
  decision_ia   «no tiene sentido» sin nada de lo anterior: el criterio de la IA
  app           la pantalla falló (errores del navegador justo antes)

Esto PROPONE, no decide: el administrador ve el origen y todas las señales que lo
sostienen. Si no hay ninguna, se dice `desconocido` antes que adivinar.
"""

from __future__ import annotations

from typing import Any

CATEGORIAS = {
    "plan": "Mi plan no tiene sentido",
    "reloj": "Mi reloj no muestra lo mismo",
    "app": "La app falla",
    "datos": "Mis datos o mi perfil",
    "otra": "Otra",
}

ORIGENES = ("perfil", "garmin", "codificacion", "decision_ia", "app", "desconocido")

# Más de esto entre lo que decidió la IA y lo que lleva el reloj ya no es redondeo.
TOLERANCIA_DE_MINUTOS = 0.15

# El orden en que se mira cada señal, según lo que el atleta dice que le pasa.
_PRIORIDAD = {
    "plan": ("codificacion", "perfil", "garmin", "app"),
    "reloj": ("garmin", "codificacion", "perfil", "app"),
    "app": ("app", "garmin", "perfil", "codificacion"),
    "datos": ("perfil", "garmin", "app", "codificacion"),
    "otra": ("perfil", "garmin", "codificacion", "app"),
}

_CALIDAD = {"tempo_threshold", "vo2max", "repeticiones", "hill_repeats", "neuromuscular", "race_day"}


def _senales_de_perfil(php: dict[str, Any]) -> list[str]:
    """Sólo lo que de verdad le impide a la IA hacerlo bien.

    Una carrera en un día de descanso de su rejilla NO es una señal: desde el 2026-10-09 el
    día de una carrera está disponible para ella (Hugo; ver 02). Y no tener la contraseña
    guardada tampoco: la escribe al generar. Las dos van en el diagnóstico como información.
    """
    s = []
    if php.get("rejilla_vacia"):
        s.append("No tiene rejilla de deportes por día: la IA no sabe qué quiere entrenar cada día.")
    pct = php.get("perfil_pct")
    if isinstance(pct, (int, float)) and pct < 80:
        s.append(f"El perfil está al {int(pct)} %.")
    return s


def _senales_de_garmin(diag: dict[str, Any]) -> list[str]:
    s = []
    reloj = (diag.get("sesion") or {}).get("reloj") or {}
    if reloj.get("estado") == "desactualizado":
        s.append("El reloj lleva otra versión de esta sesión: la de la app cambió después de subirla.")
    elif reloj.get("estado") == "sin_subir" and diag.get("categoria") == "reloj":
        s.append("Esta sesión no se subió al reloj.")
    for t in diag.get("trabajos") or []:
        if t.get("kind") == "garmin-upload" and t.get("estado") in ("error", "colgado"):
            s.append(f"La subida a Garmin del {t.get('inicio') or '?'} terminó en {t.get('estado')}.")
            break
    return s


def _senales_de_codificacion(diag: dict[str, Any]) -> list[str]:
    sesion = diag.get("sesion") or {}
    decidido, codificado = sesion.get("decidido") or {}, sesion.get("codificado") or {}
    if not decidido or not codificado:
        return []
    s = []
    if decidido.get("sport") and codificado.get("sport") and decidido["sport"] != codificado["sport"]:
        s.append(f"La IA decidió {decidido['sport']} y el plan lleva {codificado['sport']}.")
    a = decidido.get("total_duration_minutes")
    b = codificado.get("minutos_de_los_pasos")
    if isinstance(a, (int, float)) and isinstance(b, (int, float)) and a > 0:
        if abs(b - a) / a > TOLERANCIA_DE_MINUTOS:
            s.append(f"La IA decidió {a:.0f} min y los pasos del reloj suman {b:.0f} min.")
    ca, cb = decidido.get("intensity_class"), codificado.get("intensity_class")
    if ca and cb and (ca in _CALIDAD) != (cb in _CALIDAD):
        s.append(f"La IA decidió una sesión «{ca}» y el plan lleva «{cb}».")
    return s


def _senales_de_app(diag: dict[str, Any]) -> list[str]:
    errores = diag.get("errores_del_navegador") or []
    return [f"{len(errores)} error(es) del navegador antes de escribir."] if errores else []


def proponer(diag: dict[str, Any]) -> dict[str, Any]:
    """`{origen, motivos: {origen: [señales]}}` a partir del diagnóstico ya armado."""
    categoria = diag.get("categoria") if diag.get("categoria") in CATEGORIAS else "otra"
    senales = {
        "perfil": _senales_de_perfil(diag.get("php") or {}),
        "garmin": _senales_de_garmin(diag),
        "codificacion": _senales_de_codificacion(diag),
        "app": _senales_de_app(diag),
    }
    motivos = {k: v for k, v in senales.items() if v}
    origen = next((o for o in _PRIORIDAD[categoria] if senales[o]), None)
    if origen is None and categoria == "plan":
        origen, motivos = "decision_ia", {
            "decision_ia": ["Dice que el plan no tiene sentido y no hay ninguna señal de perfil, reloj ni codificación."]}
    origen = origen or "desconocido"
    # La cola del administrador (ColaDeAyuda.vue) sólo sabe pintar estos.
    assert origen in ORIGENES, origen
    return {"origen": origen, "motivos": motivos}
