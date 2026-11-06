"""El texto de un paso que el ATLETA ajustó, escrito por el código (Beta 3.0, F7).

Cuando el atleta cambia «4×5'» por «3×5'» desde la app, el texto del coach ya no dice lo
que hace el reloj. Este módulo escribe el texto nuevo desde los campos estructurados, y
sólo para los pasos que cambiaron: los demás conservan lo que escribió el coach.

Dos contratos con el resto del código, cada uno con su prueba:

- **El bloque lleva la serie entera y el hijo sólo su trabajo** («3x(5' a 5:35-6:02/km,
  2' rec)» y debajo «5' a 5:35-6:02/km»): es lo que lee `_work_description`, y escribir la
  serie en el hijo era el «8× · 8x50m» que se leía como 64 × 50 (auditoría del 2026-09-24).
- **Las horas se escriben como horas** («1 h 50'»), que es lo que lee la red de duraciones
  (`plan_validator._duracion_como_dice_el_paso`, `_HORAS_EN_TEXTO`): «1 h 50 min» leído
  como 50 min mandó la carrera de 6.600 s al reloj como 3.000 (2026-10-09).
"""

from __future__ import annotations

from typing import Any

from services.ai.prescription import format_pace


def duracion(tipo: str | None, valor: float | None, deporte: str = "") -> str:
    if tipo == "lap_button":
        return "hasta pulsar vuelta"
    if tipo == "distance" and valor:
        v = float(valor)
        if deporte != "swim" and v >= 1000:
            km = v / 1000
            return f"{km:.1f} km".replace(".0 km", " km").replace(".", ",")
        return f"{int(round(v))} m"
    if tipo == "time" and valor:
        s = int(round(float(valor)))
        if s >= 3600:
            h, m = divmod(s // 60, 60)
            return f"{h} h {m}'" if m else f"{h} h"
        m, seg = divmod(s, 60)
        if not m:
            return f'{seg}"'
        return f"{m}'{seg}\"" if seg else f"{m}'"
    return ""


def objetivo(paso: dict[str, Any], deporte: str = "") -> str:
    tipo = paso.get("target_type") or "none"
    if deporte == "swim":
        return ""  # el reloj no lleva objetivo en natación (workout_builder)
    if tipo == "hr_zone" and paso.get("target_zone"):
        return f"en Z{int(paso['target_zone'])}"
    if tipo == "pace" and paso.get("target_low") and paso.get("target_high"):
        return f"a {format_pace(paso['target_low'])}-{format_pace(paso['target_high'])}/km"
    if tipo == "power":
        if paso.get("target_low") and paso.get("target_high"):
            return f"a {float(paso['target_low']):.0f}-{float(paso['target_high']):.0f} W"
        if paso.get("target_zone"):
            return f"en Z{int(paso['target_zone'])} de potencia"
    return ""


_ETIQUETA = {"warmup": "de calentamiento", "cooldown": "de enfriamiento", "recovery": "de recuperación",
             "rest": "de descanso"}

# Natación: el estilo, el material y si es patada, brazos o técnica, en palabras (los mismos valores que
# `garmin_vocabulary.swim_fields` manda al reloj como campos nativos).
_ESTILO = {"free": "crol", "backstroke": "espalda", "breaststroke": "braza", "fly": "mariposa",
           "individual_medley": "estilos", "individual_medley_by_round": "estilos por ronda",
           "mixed": "estilos mezclados", "any_stroke": "estilo libre"}
_TIPO_DE_NADO = {"kick": "patada", "pull": "brazos", "drill": "técnica"}
_MATERIAL = {"pull_buoy": "con pull buoy", "paddles": "con palas", "fins": "con aletas", "kickboard": "con tabla",
             "snorkel": "con tubo"}


def _de_natacion(p: dict[str, Any]) -> str:
    partes = [_TIPO_DE_NADO.get((p.get("drill") or "").lower(), ""), _ESTILO.get((p.get("stroke") or "").lower(), ""),
              _MATERIAL.get((p.get("equipment") or "").lower(), "")]
    return " ".join(x for x in partes if x)


def paso(p: dict[str, Any], deporte: str = "") -> str:
    """«10' de calentamiento en Z2», «400 m», «5' a 5:35-6:02/km», «100 m patada crol con tabla»."""
    partes = [duracion(p.get("duration_type"), p.get("duration_value"), deporte),
              _ETIQUETA.get(p.get("step_type") or "", ""),
              _de_natacion(p) if deporte == "swim" else "", objetivo(p, deporte)]
    texto = " ".join(x for x in partes if x).strip()
    return texto[:1].upper() + texto[1:] if texto.startswith("hasta") else texto


def serie(repeticiones: int, trabajo: dict[str, Any], recuperacion: dict[str, Any] | None, deporte: str = "") -> str:
    """«3x(5' a 5:35-6:02/km, 2' rec)»: la serie entera, para el bloque."""
    dentro = paso({**trabajo, "step_type": "interval"}, deporte)
    if recuperacion:
        dentro += f", {duracion(recuperacion.get('duration_type'), recuperacion.get('duration_value'), deporte)} rec"
    return f"{int(repeticiones)}x({dentro})"
