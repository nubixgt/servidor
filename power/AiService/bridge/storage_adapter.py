"""Materialise the athlete's MySQL data as the files PaginaLocal expects to
find in a profile's storage directory. Called at the start of every request,
into that request's throwaway storage dir.

## La regla, y por qué es una regla

**MySQL manda. Este directorio es un ESPEJO, no un almacén.** Lo que MySQL no
tiene, aquí no puede quedar.

Hasta v7.2 cada `write_*` era un no-op cuando el dato venía vacío -- `if
weekly_plan:` y a otra cosa -- con la intención de "no pisar lo que hay". Pero
lo que hay no es un respaldo: es la corrida anterior. Así que borrar la semana
en la app la dejaba viva en el disco del servicio, y de ahí salían tres cosas,
todas vistas:

  1. La ventana de generación leía esa semana fantasma, veía fechas futuras y
     contestaba "todavía no toca" a un atleta que acababa de borrarlo todo.
     Ir a archivados no ayudaba: eso está en MySQL, no en este fichero.
  2. La adherencia se medía contra un plan que el atleta borró.
  3. El coach respondía sobre una semana que ya no existe.

El directorio es de usar y tirar (`_runtime/storage/u<id>`), así que borrar
aquí no pierde nada que MySQL no tenga.
"""
from __future__ import annotations

import json
import logging
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)


def _write_json(path: Path, data: Any) -> None:
    path.write_text(json.dumps(data, indent=2, ensure_ascii=False), encoding="utf-8")


def _borrar(storage_dir: Path, nombre: str) -> None:
    """Quita el fichero de la corrida anterior. Ausente ya es el estado bueno."""
    try:
        (Path(storage_dir) / nombre).unlink(missing_ok=True)
    except OSError:
        logger.warning("No se pudo limpiar %s del espejo", nombre, exc_info=True)


def write_notebook(storage_dir: Path, entries: list[dict[str, Any]]) -> None:
    """notebook.json — PaginaLocal's load_notebook accepts {"entries": [...]}.
    Our PHP NotebookEntryEntity::toArray() already has the right keys
    (id, text, category, quote, source, status, created_at, updated_at,
    retired_at, retired_reason)."""
    if not entries:
        return _borrar(storage_dir, "notebook.json")
    _write_json(storage_dir / "notebook.json", {"entries": entries})


def write_checkins(storage_dir: Path, checkins: list[dict[str, Any]]) -> None:
    """checkins.json — a plain list, oldest first. Keys match
    PaginaLocal's normalize_checkin output (date, sleep, fatigue, stress,
    soreness, motivation, niggle_location, niggle_pain, last_week_note,
    week_note, unavailable_dates, open_dates, week_ahead, hooper_total, recorded_at)."""
    if not checkins:
        return _borrar(storage_dir, "checkins.json")
    ordered = sorted(checkins, key=lambda c: str(c.get("date") or ""))
    _write_json(storage_dir / "checkins.json", ordered)


def write_season_plan(storage_dir: Path, markdown: str | None, phases: Any = None,
                      week_start_day: str | None = None) -> None:
    if markdown:
        (storage_dir / "season_plan.md").write_text(markdown, encoding="utf-8")
    else:
        _borrar(storage_dir, "season_plan.md")
    if phases:
        # PaginaLocal reads season_phases.json as {"phases": [...]} or a bare list.
        payload = phases if isinstance(phases, dict) else {"phases": phases}
        # Las semanas del macrociclo son las del atleta: su arranque se alinea al
        # día en que empieza su semana. Al LEER, para que los macrociclos ya
        # guardados se corrijan sin tocar la base. Ver
        # PaginaLocal/services/ai/semanas_del_macrociclo.py.
        from services.ai.semanas_del_macrociclo import con_arranque_de_sus_semanas

        payload = con_arranque_de_sus_semanas(payload, week_start_day)
        _write_json(storage_dir / "season_phases.json", payload)
    else:
        _borrar(storage_dir, "season_phases.json")


def write_weekly_plan(storage_dir: Path, weekly_plan: dict[str, Any] | None) -> None:
    """El caso que motivó la regla de arriba: una semana borrada que seguía
    bloqueando la generación de la siguiente."""
    if not weekly_plan or not (weekly_plan.get("weeks") or []):
        return _borrar(storage_dir, "weekly_plan.json")
    _write_json(storage_dir / "weekly_plan.json", weekly_plan)


def write_summary(storage_dir: Path, summary: dict[str, Any] | None) -> None:
    if not summary:
        return _borrar(storage_dir, "summary.json")
    _write_json(storage_dir / "summary.json", summary)


def read_back(storage_dir: Path) -> dict[str, Any]:
    """Everything a generation run may have written, for the PHP side to store."""
    def _json(name: str) -> Any:
        p = storage_dir / name
        if not p.exists():
            return None
        try:
            return json.loads(p.read_text(encoding="utf-8"))
        except (OSError, json.JSONDecodeError):
            return None

    def _text(name: str) -> str | None:
        p = storage_dir / name
        return p.read_text(encoding="utf-8") if p.exists() else None

    return {
        "season_plan_md": _text("season_plan.md"),
        "season_phases": _json("season_phases.json"),
        "weekly_plan": _json("weekly_plan.json"),
        "summary": _json("summary.json"),
        "analysis_html": _text("analysis.html"),
        "planning_html": _text("planning.html"),
    }
