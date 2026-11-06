"""Cuándo corrige algo cada red determinista, en un libro que sobrevive a los trabajos.

Beta 3.0, F1. Las redes deterministas (el ritmo o la duración que se reencajan al texto
del paso, la carrera hasta pulsar vuelta, los respaldos tras una reparación...) avisan en
el `worker.log` cuando corrigen algo. Ese log vive con su trabajo y el AiService lo borra
a los 3 días o a los 30 trabajos, así que la regla "una red que no corrige nada en dos
semanas de beta se borra" (01, Fase C) no tenía con qué medirse.

Este libro sigue el patrón del de costos (`usage_ledger`): `data/billing/redes.jsonl`,
fuera del árbol que se borra, sólo se le agregan líneas y nunca tumba una generación.
Cada línea es una corrección: `{ts, red, user_id, detalle}`. El panel de administración
lo cruza con cuántas generaciones hubo en la misma ventana para decir "0 correcciones en
N generaciones" -- la señal para retirar una red.
"""

from __future__ import annotations

import json
import logging
import os
import threading
from collections.abc import Iterator
from datetime import UTC, datetime, timedelta
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)

_PROJECT_ROOT = Path(__file__).resolve().parents[1]
REDES_PATH = _PROJECT_ROOT / "data" / "billing" / "redes.jsonl"

_write_lock = threading.Lock()

# Sólo escribe quien lo activa: el puente del AiService (bridge/paginalocal.py), que es
# donde corren las generaciones de verdad. Las pruebas llaman a las mismas redes miles de
# veces y no pueden llenar el libro real de correcciones que nunca pasaron.
ACTIVO = False

# Las redes que se miden, con lo que hacen dicho para el panel. Una red nueva entra aquí
# y en su punto de llamada; `resumen` enseña también las que no corrigieron nada.
REDES: dict[str, str] = {
    "ritmo_del_paso": "Reencaja el objetivo de ritmo al que dice el texto del paso",
    "duracion_del_paso": "Reencaja la duración del reloj a la que dice el texto del paso",
    "carrera_hasta_pulsar_vuelta": "Pone la carrera «hasta pulsar vuelta» en vez de un tiempo",
    "zona_en_rangos": "Quita la zona a un paso que dice dos («Z1-Z2»)",
    "enfriamiento_al_final": "Recoloca el enfriamiento al final de la sesión",
    "vuelta_de_la_decision": "Devuelve la decisión al modelo una vez (pagada)",
    "dia_a_descanso": "Convierte en descanso una sesión fuera de la disponibilidad",
    "reparacion_ia": "Reparación de la semana por IA (pagada)",
    "respaldo_sin_pasos": "Rellena un día que quedó sin pasos",
    "respaldo_dia_de_carrera": "Escribe el día de carrera que faltaba",
    "respaldo_fecha_faltante": "Añade un día que faltaba",
    "respaldo_fecha_duplicada": "Quita un día duplicado",
    "respaldo_reglas_de_coaching": "Arregla recuperaciones y objetivos por reglas",
    "respaldo_duracion": "Corrige un total que no cuadra con sus pasos",
    "forzado_a_descanso": "Fuerza a descanso lo que siguió sin arreglarse",
    "macrociclo_palabras_de_duda": "Aviso: el macrociclo usó «firme/provisional/preliminar»",
}


INICIO = "_inicio"


def _escribir(linea: dict[str, Any]) -> None:
    REDES_PATH.parent.mkdir(parents=True, exist_ok=True)
    with _write_lock:
        with open(REDES_PATH, "a", encoding="utf-8") as handle:
            handle.write(json.dumps(linea, ensure_ascii=False) + "\n")
            handle.flush()
            os.fsync(handle.fileno())


def activar() -> None:
    """Enciende el libro y, la primera vez, apunta desde cuándo se mide.

    Sin esa fecha el panel contaba las generaciones de todo el mes contra un libro que
    empezó hoy: "0 correcciones en 65 generaciones" de redes que nadie había medido, que
    es justo la señal para borrarlas (visto al estrenar el panel, 2026-10-11).
    """
    global ACTIVO
    ACTIVO = True
    try:
        if not REDES_PATH.exists():
            _escribir({"ts": datetime.now(UTC).isoformat(timespec="seconds"), "red": INICIO})
    except Exception:  # noqa: BLE001
        logger.exception("No se pudo iniciar el libro de redes")


def anotar(red: str, detalle: str = "", user_id: str | None = None) -> None:
    """Una corrección. Nunca lanza: medir no puede tumbar una generación."""
    if not ACTIVO:
        return
    try:
        from core.usage_ledger import _resolve_user_id

        _escribir({
            "ts": datetime.now(UTC).isoformat(timespec="seconds"),
            "red": str(red),
            "user_id": _resolve_user_id(user_id),
            "detalle": str(detalle or "")[:300],
        })
    except Exception:  # noqa: BLE001
        logger.exception("No se pudo anotar la red %s", red)


def midiendo_desde() -> datetime | None:
    """La primera línea del libro: antes de ella no se midió nada."""
    for linea in leer():
        try:
            ts = datetime.fromisoformat(str(linea.get("ts")))
        except ValueError:
            continue
        return ts if ts.tzinfo else ts.replace(tzinfo=UTC)
    return None


def leer() -> Iterator[dict[str, Any]]:
    if not REDES_PATH.exists():
        return
    with open(REDES_PATH, encoding="utf-8") as handle:
        for linea in handle:
            linea = linea.strip()
            if not linea:
                continue
            try:
                yield json.loads(linea)
            except json.JSONDecodeError:
                continue


def resumen(dias: int = 30, generaciones: int | None = None, ahora: datetime | None = None) -> list[dict[str, Any]]:
    """Por red: cuántas correcciones, a cuántos atletas, la última, y si no corrigió nada.

    `generaciones` es cuántas semanas y macrociclos hubo en la misma ventana y desde
    `midiendo_desde()` (lo da quien llama, desde el libro de costos); sin él no se puede
    decir "0 de N".
    """
    corte = (ahora or datetime.now(UTC)) - timedelta(days=dias)
    por_red: dict[str, dict[str, Any]] = {}
    for linea in leer():
        try:
            ts = datetime.fromisoformat(str(linea.get("ts")))
        except ValueError:
            continue
        if ts.tzinfo is None:
            ts = ts.replace(tzinfo=UTC)
        if ts < corte:
            continue
        red = str(linea.get("red") or "")
        if red.startswith("_"):  # marcas del libro (`_inicio`), no correcciones
            continue
        fila = por_red.setdefault(red, {"red": red, "correcciones": 0, "atletas": set(), "ultima": "",
                                        "ultimo_detalle": ""})
        fila["correcciones"] += 1
        if linea.get("user_id"):
            fila["atletas"].add(str(linea["user_id"]))
        if str(linea.get("ts")) >= fila["ultima"]:
            fila["ultima"] = str(linea.get("ts"))
            fila["ultimo_detalle"] = str(linea.get("detalle") or "")
    salida = []
    for red in list(REDES) + sorted(r for r in por_red if r not in REDES):
        fila = por_red.get(red, {"red": red, "correcciones": 0, "atletas": set(), "ultima": "", "ultimo_detalle": ""})
        salida.append({
            "red": red,
            "que_hace": REDES.get(red, ""),
            "correcciones": fila["correcciones"],
            "atletas": len(fila["atletas"]),
            "ultima": fila["ultima"],
            "ultimo_detalle": fila["ultimo_detalle"],
            "generaciones": generaciones,
            "sin_corregir_nada": fila["correcciones"] == 0 and bool(generaciones),
        })
    return salida
