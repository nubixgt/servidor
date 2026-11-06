"""Siembra el libro de costos con lo que ya se gastó antes de que existiera.

v7. El libro (`core/usage_ledger.py`) nació el 2026-08-27, pero el gasto no:
para entonces ya había corridas reales archivadas, cada una con su costo
medido dentro de su propio `summary.json`. Empezar el libro en cero habría
hecho que el primer informe de costo por usuario dijera una cifra falsa —
más baja que la real, que es la peor dirección para equivocarse en una
contabilidad.

Este script lee lo que quedó archivado y lo escribe como líneas del libro:

  data/storage/<perfil>/history/<ISO>/summary.json   (generaciones archivadas)
  data/storage/<perfil>/summary.json                 (la generación vigente)
  data/storage/<perfil>/replays/*.json               (banco de pruebas)

**Es idempotente.** Cada línea sembrada lleva `detail="backfill:<origen>"`, y
una segunda corrida salta lo que ya está. Correrlo dos veces no duplica el
gasto — cosa que importa, porque un libro append-only no se puede corregir
borrando.

    py scripts/backfill_usage_ledger.py           # muestra lo que haría
    py scripts/backfill_usage_ledger.py --write   # lo escribe
"""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core import usage_ledger  # noqa: E402

STORAGE_ROOT = Path(__file__).resolve().parents[1] / "data" / "storage"


def _read(path: Path):
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return None


def _kind_of(summary: dict) -> str:
    return {
        "season": usage_ledger.KIND_SEASON,
        "week": usage_ledger.KIND_WEEK,
    }.get(summary.get("generation_kind") or "", usage_ledger.KIND_WEEK)


def collect(profile_dir: Path) -> list[dict]:
    """Todo lo cobrable que dejó rastro en el disco de un perfil."""
    found = []

    for path in sorted((profile_dir / "history").glob("*/summary.json")):
        summary = _read(path)
        if summary:
            found.append({
                "origin": f"history/{path.parent.name}",
                "ts": summary.get("analysis_date"),
                "kind": _kind_of(summary),
                "summary": summary,
            })

    current = _read(profile_dir / "summary.json")
    if current:
        found.append({
            "origin": "summary.json",
            "ts": current.get("analysis_date"),
            "kind": _kind_of(current),
            "summary": current,
        })

    # El banco de pruebas gasta dinero real y no es una generación: se anota
    # como su propio tipo para que no infle el costo de entrenar a un atleta.
    for path in sorted((profile_dir / "replays").glob("*.json")):
        replay = _read(path)
        if not replay:
            continue
        cost = replay.get("cost") or {}
        found.append({
            "origin": f"replays/{path.name}",
            "ts": replay.get("replayed_at"),
            "kind": usage_ledger.KIND_REPLAY,
            "summary": {
                "total_cost_usd": cost.get("cost_usd"),
                "total_tokens": cost.get("total_tokens"),
                "agents": [{
                    "name": replay.get("node") or "replay",
                    "cost_usd": cost.get("cost_usd"),
                    "tokens": cost.get("total_tokens"),
                    "input_tokens": cost.get("input_tokens"),
                    "output_tokens": cost.get("output_tokens"),
                    "models": [{"name": replay.get("replay_model")}],
                }],
            },
        })

    return found


def already_seeded() -> set[str]:
    return {
        str(record.get("detail"))
        for record in usage_ledger.read_all()
        if str(record.get("detail") or "").startswith("backfill:")
    }


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--write", action="store_true", help="Escribe de verdad (por defecto solo muestra)")
    args = parser.parse_args(argv)

    seeded = already_seeded()
    total, written, skipped = 0.0, 0, 0

    for profile_dir in sorted(p for p in STORAGE_ROOT.iterdir() if p.is_dir()):
        items = collect(profile_dir)
        if not items:
            continue
        print(f"\n{profile_dir.name}")
        for item in items:
            detail = f"backfill:{item['origin']}"
            cost = float(item["summary"].get("total_cost_usd") or 0.0)
            if detail in seeded:
                skipped += 1
                print(f"   ya estaba   {item['origin']}")
                continue
            total += cost
            written += 1
            print(f"   ${cost:>8.4f}  {usage_ledger.label_for_kind(item['kind']):<24} {item['origin']}")
            if args.write:
                # `ts` explícito: la fecha en que se gastó, no la de hoy, o
                # todo el histórico caería en el mes en curso.
                usage_ledger.record_run(
                    profile_dir.name,
                    item["kind"],
                    item["summary"],
                    detail=detail,
                    ts=item.get("ts"),
                )

    print(f"\n{'ESCRITO' if args.write else 'SIMULACRO'}: {written} línea(s), ${total:.4f}"
          f"{f', {skipped} ya estaban' if skipped else ''}")
    if not args.write and written:
        print("Vuelve a correrlo con --write para anotarlo.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
