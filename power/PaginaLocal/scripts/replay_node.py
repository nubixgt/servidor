"""Banco de pruebas de modelos: vuelve a correr UN nodo ya archivado.

Plan Maestro v6 Track 1. Responde "¿qué modelo escribe mejor esta semana para
ESTE atleta?" por el costo de una llamada, sin tocar Garmin y sin sobrescribir
ningún plan.

Cómo funciona: cada generación archiva el prompt exacto que recibió cada
agente (`services/ai/prompt_capture.py`). Esto lo vuelve a enviar tal cual a
otro modelo. Todo lo demás queda congelado -- los mismos datos de Garmin, la
misma fecha, el mismo check-in -- así que la única diferencia entre dos
réplicas es el modelo.

    # qué llamadas hay archivadas
    py scripts/replay_node.py --profile "Jose Ines Castillo" --list

    # replicar la decisión semanal con dos modelos y comparar
    py scripts/replay_node.py --profile "Jose Ines Castillo" \
        --call weekly_planner --model gpt-5 --model gemini-3.5-flash

    # ver la comparación otra vez, sin volver a gastar
    py scripts/replay_node.py --profile "Jose Ines Castillo" --compare

Antes de gastar un centavo imprime el costo estimado y pide confirmación;
`--yes` la salta (para correrlo desde un script).
"""

from __future__ import annotations

import argparse
import asyncio
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

# La consola de Windows llega en cp1252 y revienta con cualquier caracter fuera
# de ella. Paso: una flecha en un mensaje de progreso tiro el banco de pruebas
# entero con UnicodeEncodeError -- justo antes de la llamada, asi que no costo
# dinero, pero si costo la corrida. Una herramienta de diagnostico que se cae
# por como imprime no diagnostica nada.
for _flujo in (sys.stdout, sys.stderr):
    try:
        _flujo.reconfigure(encoding="utf-8", errors="replace")
    except (AttributeError, ValueError):
        pass

from services.ai.ai_settings import AgentRole  # noqa: E402
from services.ai.model_config import ModelSelector  # noqa: E402
from services.ai.reasoning_archive import list_reasoning_entries  # noqa: E402
from services.ai.replay import (  # noqa: E402
    available_calls,
    build_comparison,
    estimate_cost_usd,
    load_replays,
    original_record,
    replay_call,
    resolve_call,
)
from services.ai.utils.plan_storage import sanitize_storage_name  # noqa: E402

STORAGE_ROOT = Path(__file__).resolve().parents[1] / "data" / "storage"


def resolve_storage_dir(profile: str) -> Path:
    """Accepts the athlete's name or the directory name; they differ."""
    candidates = [STORAGE_ROOT / profile, STORAGE_ROOT / sanitize_storage_name(profile)]
    # Both spellings can exist side by side (an old bug wrote to the
    # unsanitized one -- see sanitize_storage_name's docstring), so prefer the
    # one that actually holds archived generations over the empty leftover.
    for candidate in candidates:
        if (candidate / "reasoning").is_dir():
            return candidate
    for candidate in candidates:
        if candidate.is_dir():
            return candidate
    existing = sorted(p.name for p in STORAGE_ROOT.iterdir() if p.is_dir()) if STORAGE_ROOT.is_dir() else []
    raise SystemExit(
        f"No encuentro el perfil '{profile}' en {STORAGE_ROOT}.\nPerfiles disponibles: {', '.join(existing) or '(ninguno)'}"
    )


def resolve_entry(storage_dir: Path, entry: str | None) -> str:
    entries = list_reasoning_entries(storage_dir)
    if not entries:
        raise SystemExit(f"El perfil no tiene generaciones archivadas en {storage_dir / 'reasoning'}.")
    if entry in (None, "latest"):
        return entries[0]["id"]
    for candidate in entries:
        if candidate["id"] == entry:
            return entry
    raise SystemExit(
        f"No existe la generación '{entry}'. Archivadas: {', '.join(c['id'] for c in entries[:10])}"
    )


def cmd_list(storage_dir: Path, entry_id: str) -> int:
    calls = available_calls(storage_dir, entry_id)
    print(f"Generación {entry_id} — {len(calls)} llamada(s) archivada(s)\n")
    if not calls:
        print("Esta generación es anterior al archivado de prompts (v6 Track 1),")
        print("o su captura falló. Genera una semana nueva para poder replicarla.")
        return 1
    header = f"{'#':>3}  {'nodo':<22} {'modelo':<24} {'salida':<26} {'chars':>8} {'tok in':>8} {'tok out':>8}"
    print(header)
    print("-" * len(header))
    for call in calls:
        usage = call.get("usage") or {}
        print(
            f"{call.get('index', 0):>3}  {str(call.get('node')):<22} {str(call.get('model')):<24} "
            f"{str(call.get('output_schema') or '(texto libre)'):<26} {call.get('prompt_chars') or 0:>8} "
            f"{usage.get('input_tokens') or 0:>8} {usage.get('output_tokens') or 0:>8}"
        )
    return 0


def _print_comparison(records: list[dict]) -> None:
    if not records:
        print("No hay réplicas guardadas todavía.")
        return
    table = build_comparison(records)
    width = 34
    print(f"\nComparación — {table['entry_id']} / {table['call_id']}\n")
    for row in table["rows"]:
        cells = []
        for value in row["values"]:
            if isinstance(value, float):
                # Money needs four decimals to be readable at these sizes; a
                # latency of "7.7900 s" just looks like a spreadsheet.
                text = f"{value:.4f}" if "USD" in row["label"] else f"{value:g}"
            elif isinstance(value, dict):
                text = ", ".join(f"{k}:{v}" for k, v in value.items()) or "-"
            elif value is None:
                text = "-"
            else:
                text = str(value)
            cells.append(text[: width - 1].ljust(width))
        print(f"{row['label']:<26}" + "".join(cells))
    print()
    for record in records:
        unsupported = (
            ((record.get("summary") or {}).get("rationale_numbers") or {}).get("unsupported") or []
        )
        if unsupported:
            print(f"{record.get('replay_model')}: números citados que NO estaban en el prompt")
            for item in unsupported:
                print(f"   {item['literal']:>10}  ...{item['context']}...")
            print()


async def cmd_replay(storage_dir: Path, entry_id: str, selector: str, models: list[str], role: AgentRole, assume_yes: bool) -> int:
    index_entry = resolve_call(storage_dir, entry_id, selector)
    if index_entry is None:
        print(f"No hay ninguna llamada '{selector}' en {entry_id}. Usa --list para ver cuáles hay.")
        return 1

    from services.ai.reasoning_archive import load_prompt_call

    call = load_prompt_call(storage_dir, entry_id, index_entry["call_id"])
    if call is None:
        print(f"No se pudo leer la llamada {index_entry['call_id']}.")
        return 1

    print(f"Llamada: {index_entry['call_id']}")
    print(f"  nodo         : {call.get('node')}")
    print(f"  modelo real  : {call.get('model')}")
    print(f"  salida       : {(call.get('output_schema') or {}).get('name') or '(texto libre)'}")
    print(f"  prompt       : {call.get('prompt_chars') or 0:,} caracteres\n")

    total = 0.0
    unknown_price = False
    for model_key in models:
        estimate = estimate_cost_usd(call, model_key)
        if estimate["usd"] is None:
            unknown_price = True
            print(f"  {model_key:<22} costo desconocido — {estimate['note']}")
        else:
            total += estimate["usd"]
            print(
                f"  {model_key:<22} ~${estimate['usd']:.4f}  "
                f"({estimate['input_tokens']:,} in / {estimate['output_tokens']:,} out estimados)"
            )
    print(f"\n  Estimado total: ~${total:.4f}" + ("  (+ modelos sin precio conocido)" if unknown_price else ""))

    if not assume_yes:
        answer = input("\n¿Ejecutar? [s/N] ").strip().lower()
        if answer not in ("s", "si", "sí", "y", "yes"):
            print("Cancelado. No se gastó nada.")
            return 0

    # Row zero, free: what the model that really ran actually answered. Every
    # comparison should start from what happened, and it is already on disk.
    baseline = original_record(storage_dir, entry_id, index_entry["call_id"])
    records = [baseline] if baseline else []
    if baseline:
        print(f"\nBase (sin costo): {baseline['replay_model']}")
    for model_key in models:
        print(f"\n→ Replicando con {model_key}...")
        record = await replay_call(storage_dir, entry_id, index_entry["call_id"], model_key, role=role)
        if record.get("error"):
            print(f"   FALLÓ: {record['error']}")
        else:
            print(
                f"   ${record['cost']['cost_usd']:.4f} · {record['cost']['input_tokens']:,} in / "
                f"{record['cost']['output_tokens']:,} out · {record['latency_seconds']}s"
            )
        records.append(record)

    _print_comparison(records)
    real_total = sum(
        (r.get("cost") or {}).get("cost_usd", 0.0) for r in records if r is not baseline
    )
    print(f"Costo real de esta comparación: ${real_total:.4f}")
    print(f"Guardado en {storage_dir / 'replays'}")
    return 0


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(
        description="Vuelve a correr un nodo archivado con otro modelo y compara.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    parser.add_argument("--profile", required=True, help="Nombre del atleta o de su carpeta en data/storage")
    parser.add_argument("--entry", default="latest", help="Generación archivada (ISO) o 'latest'")
    parser.add_argument("--list", action="store_true", help="Lista las llamadas archivadas y sale")
    parser.add_argument("--call", help="call_id, nombre de nodo (p. ej. weekly_planner) o índice")
    parser.add_argument(
        "--model", action="append", default=[],
        help="Modelo a probar; repetible. Claves de model_config.CONFIGURATIONS",
    )
    parser.add_argument("--role", default="workout", help="Rol para la configuración del cliente (por defecto workout)")
    parser.add_argument("--compare", action="store_true", help="Sólo muestra réplicas ya guardadas")
    parser.add_argument("--yes", action="store_true", help="No pedir confirmación antes de gastar")
    parser.add_argument("--json", action="store_true", help="Salida en JSON en vez de tabla")
    args = parser.parse_args(argv)

    storage_dir = resolve_storage_dir(args.profile)
    entry_id = resolve_entry(storage_dir, args.entry)

    if args.list:
        return cmd_list(storage_dir, entry_id)

    if args.compare:
        call_id = None
        if args.call:
            index_entry = resolve_call(storage_dir, entry_id, args.call)
            call_id = index_entry["call_id"] if index_entry else args.call
        records = load_replays(storage_dir, entry_id, call_id)
        baseline = original_record(storage_dir, entry_id, call_id or "weekly_planner")
        if baseline:
            records = [baseline] + records
        if args.json:
            print(json.dumps(build_comparison(records), indent=2, ensure_ascii=False))
        else:
            _print_comparison(records)
        return 0

    if not args.call or not args.model:
        parser.error("hace falta --call y al menos un --model (o usa --list / --compare)")

    unknown = [m for m in args.model if m not in ModelSelector.CONFIGURATIONS]
    if unknown:
        parser.error(
            f"modelo(s) desconocido(s): {', '.join(unknown)}.\n"
            f"Disponibles: {', '.join(sorted(ModelSelector.CONFIGURATIONS))}"
        )

    try:
        role = AgentRole(args.role)
    except ValueError:
        parser.error(f"rol desconocido '{args.role}'. Disponibles: {', '.join(r.value for r in AgentRole)}")

    return asyncio.run(cmd_replay(storage_dir, entry_id, args.call, args.model, role, args.yes))


if __name__ == "__main__":
    raise SystemExit(main())
