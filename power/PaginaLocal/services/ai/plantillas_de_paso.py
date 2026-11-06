"""Pasos que el atleta puede AÑADIR a una sesión desde la app (Beta 3.0, F7).

Cada plantilla lleva los valores del propio atleta: el ritmo de umbral o de VO2max sale de
sus bandas (`summary.prescription.pace_bands`, de su umbral) y la potencia de su FTP. Sin
esos datos el objetivo es por zona de pulso: **nunca un ritmo inventado** (01: abstenerse
antes que adivinar).

En natación no hay objetivo: el reloj no lo lleva (`workout_builder`, objetivos de natación
sin verificar), así que una plantilla que lo pusiera prometería algo que no pasa.

Devuelven ELEMENTOS del editor (`edicion_de_sesion.aplanar`), no pasos de Garmin: el editor
los expande al guardar y valida las unidades como cualquier otro paso.
"""

from __future__ import annotations

from typing import Any


def _paso(step_type: str, tipo: str, valor: float | None, objetivo: dict | None = None) -> dict[str, Any]:
    return {"step_type": step_type, "duracion": {"tipo": tipo, "valor": valor},
            "objetivo": objetivo or {"tipo": "none"}}


def _zona(n: int) -> dict[str, Any]:
    return {"tipo": "hr_zone", "zona": n}


def _ritmo(bandas: dict, clave: str) -> dict[str, Any] | None:
    banda = (bandas or {}).get(clave)
    if isinstance(banda, (list, tuple)) and len(banda) == 2 and all(banda):
        return {"tipo": "pace", "bajo": round(float(banda[0])), "alto": round(float(banda[1]))}
    return None


def _potencia(bandas: dict, clave: str) -> dict[str, Any] | None:
    banda = (bandas or {}).get(clave)
    if isinstance(banda, (list, tuple)) and len(banda) == 2 and all(banda):
        return {"tipo": "power", "bajo": round(float(banda[0])), "alto": round(float(banda[1]))}
    return None


def _serie(nombre: str, n: int, trabajo: dict, recuperacion: dict | None) -> dict[str, Any]:
    return {"nombre": nombre, "tipo": "serie", "repeticiones": n, "trabajo": trabajo, "recuperacion": recuperacion}


def plantillas(deporte: str, prescripcion: dict | None) -> list[dict[str, Any]]:
    """Las plantillas de ese deporte, con sus números. Lista vacía si el deporte no se edita."""
    p = prescripcion or {}
    if deporte == "run":
        bandas = p.get("pace_bands") or {}
        umbral = _ritmo(bandas, "umbral_tempo") or _zona(4)
        vo2 = _ritmo(bandas, "vo2max") or _zona(5)
        return [
            {"nombre": "Calentamiento", "tipo": "paso", **_paso("warmup", "time", 600, _zona(2))},
            {"nombre": "Rodaje suave", "tipo": "paso", **_paso("main", "time", 1200, _zona(2))},
            _serie("Series al umbral", 4, _paso("interval", "time", 300, umbral), _paso("recovery", "time", 120, _zona(1))),
            _serie("Series de VO2max", 5, _paso("interval", "time", 180, vo2), _paso("recovery", "time", 120, _zona(1))),
            _serie("Rectas", 6, _paso("interval", "time", 20), _paso("recovery", "time", 60, _zona(1))),
            {"nombre": "Enfriamiento", "tipo": "paso", **_paso("cooldown", "time", 600, _zona(1))},
        ]
    if deporte == "bike":
        bandas = p.get("power_bands") or {}
        umbral = _potencia(bandas, "umbral") or _zona(4)
        return [
            {"nombre": "Calentamiento", "tipo": "paso", **_paso("warmup", "time", 600, _zona(2))},
            {"nombre": "Rodaje suave", "tipo": "paso", **_paso("main", "time", 1800, _zona(2))},
            _serie("Series al umbral", 4, _paso("interval", "time", 300, umbral), _paso("recovery", "time", 180, _zona(1))),
            {"nombre": "Enfriamiento", "tipo": "paso", **_paso("cooldown", "time", 600, _zona(1))},
        ]
    if deporte == "swim":
        return [
            {"nombre": "Calentamiento", "tipo": "paso", **_paso("warmup", "distance", 200)},
            _serie("Series de 100 m", 8, _paso("interval", "distance", 100), _paso("recovery", "time", 20)),
            {"nombre": "Nado continuo", "tipo": "paso", **_paso("main", "distance", 400)},
            {"nombre": "Enfriamiento", "tipo": "paso", **_paso("cooldown", "distance", 100)},
        ]
    if deporte == "walk":
        return [{"nombre": "Caminata", "tipo": "paso", **_paso("main", "time", 1800, _zona(1))}]
    return []
