"""Re-run one archived agent call against a different model, and compare.

Plan Maestro v6 Track 1. The track the rest of v6 depends on, because it is
what makes "should Pro use GPT-5 or Claude for the weekly planner?" a question
with an answer instead of a question with an opinion.

## The problem it solves

Comparing models by running the whole pipeline twice does not work:

- It costs a full generation each time (a real Lite week run: $0.22; the Pro
  tier as configured before v6: $0.89).
- **The inputs change between runs.** Fresh Garmin data, a different date, a
  new check-in, a different previous plan. Whatever difference you see, you
  cannot attribute it to the model.
- It writes to the athlete's real plan and can upload to their real watch.

A replay holds every input frozen: the exact messages the model was sent, byte
for byte, taken from the archive that `services/ai/prompt_capture.py` writes.
The only thing that changes is which model answers. It touches no Garmin
account, overwrites no plan, and costs one call.

## Why the comparison is deliberately sport-agnostic

This is a product for athletes who run, swim, cycle, or do all three. A bench
that scored a week by "kilometres run" would quietly declare the model that
writes more running the better model, and that bias would then steer the
choice of model for every triathlete and every swimmer using the app. So the
summary below aggregates by whatever sports and disciplines the week actually
contains, and reports minutes -- the one currency every discipline shares.

The one thing it does score across the board is **whether the coach's stated
reasons are checkable** (`services/ai/number_audit.py`), because that is a
property of good coaching in any sport.
"""

from __future__ import annotations

import importlib
import inspect
import json
import logging
import time
from datetime import datetime
from functools import lru_cache
from pathlib import Path
from typing import Any

from langchain_core.callbacks.usage import get_usage_metadata_callback
from pydantic import BaseModel, ValidationError

from services.ai.ai_settings import AgentRole
from services.ai.model_config import ModelSelector
from services.ai.number_audit import audit_summary
from services.ai.reasoning_archive import list_prompt_calls, load_prompt_call

logger = logging.getLogger(__name__)

REPLAYS_DIRNAME = "replays"


# --- reading the archive ---------------------------------------------------

def available_calls(storage_dir: Path, entry_id: str, node: str | None = None) -> list[dict[str, Any]]:
    """Captured calls in one archived generation, optionally filtered by node."""
    calls = list_prompt_calls(Path(storage_dir), entry_id)
    if node:
        calls = [c for c in calls if c.get("node") == node]
    return calls


def resolve_call(storage_dir: Path, entry_id: str, selector: str) -> dict[str, Any] | None:
    """Find a call by `call_id`, by node name, or by index.

    A node name is the ergonomic case (`--call weekly_planner`) and is
    ambiguous by design: that node makes several calls per run (one decision
    plus one formatting call per week chunk, plus any repairs). The FIRST
    match wins, which is the decision call -- the one worth replaying.
    """
    calls = list_prompt_calls(Path(storage_dir), entry_id)
    for call in calls:
        if call.get("call_id") == selector:
            return call
    for call in calls:
        if call.get("node") == selector:
            return call
    if selector.isdigit():
        index = int(selector)
        for call in calls:
            if call.get("index") == index:
                return call
    return None


# --- cost ------------------------------------------------------------------

def estimate_cost_usd(call: dict[str, Any], model_key: str) -> dict[str, Any]:
    """What replaying this call on `model_key` should cost, before running it.

    Input tokens come from the original call's own usage when it was recorded
    (exact), otherwise from a chars/4 approximation. Output tokens are assumed
    to match the original call's -- the closest available proxy, and the
    reason the estimate is reported as an estimate rather than a price.
    """
    usage = call.get("usage") or {}
    input_tokens = int(usage.get("input_tokens") or 0)
    if not input_tokens:
        input_tokens = int((call.get("prompt_chars") or 0) / 4)
    output_tokens = int(usage.get("output_tokens") or 0) or 2000

    rates = ModelSelector.MODEL_PRICING_USD_PER_MILLION.get(model_key)
    if rates is None:
        return {
            "input_tokens": input_tokens,
            "output_tokens": output_tokens,
            "usd": None,
            "note": f"'{model_key}' no tiene fila de precio en MODEL_PRICING_USD_PER_MILLION",
        }
    input_rate, output_rate = rates
    return {
        "input_tokens": input_tokens,
        "output_tokens": output_tokens,
        "usd": (input_tokens * input_rate + output_tokens * output_rate) / 1_000_000,
        "note": None,
    }


def _cost_from_usage(usage_metadata: dict[str, Any]) -> dict[str, Any]:
    total = {"cost_usd": 0.0, "input_tokens": 0, "output_tokens": 0, "total_tokens": 0, "models": []}
    for model_name, usage in (usage_metadata or {}).items():
        input_tokens = usage.get("input_tokens", 0) or 0
        output_tokens = usage.get("output_tokens", 0) or 0
        cost = ModelSelector.estimate_cost_usd(model_name, input_tokens, output_tokens)
        total["cost_usd"] += cost
        total["input_tokens"] += input_tokens
        total["output_tokens"] += output_tokens
        total["total_tokens"] += usage.get("total_tokens", input_tokens + output_tokens) or 0
        total["models"].append({"name": model_name, "cost_usd": cost})
    return total


# --- running ---------------------------------------------------------------

# --- would the pipeline actually accept this answer? -----------------------
#
# `with_structured_output` is handed a plain JSON Schema dict here, not the
# Pydantic class, because the archived schema has to be replayable on a
# provider that never saw the class. LangChain returns whatever the provider
# produced, unvalidated -- so a model can omit a REQUIRED field and the replay
# records `error: null`.
#
# That is not hypothetical. Benching the decision node, Claude Sonnet returned
# a `WeekIntentContent` holding `week_number` and an unusually good
# `week_rationale` -- and no `days` at all. A whole week with no sessions in
# it, scored as a success, at $0.0689. A bench that cannot tell that apart
# from a working answer will eventually recommend the model that fails most
# elegantly.
#
# So the replay re-validates with the SAME Pydantic model the pipeline uses.
# "Válido" here means exactly what it means in production, which is the only
# definition worth benchmarking against.

_SCHEMA_MODULES = ("plan_outputs", "expert_outputs", "agent_outputs")


@lru_cache(maxsize=1)
def _schema_registry() -> dict[str, type[BaseModel]]:
    """Every schema class the pipeline can ask a model for, by name.

    Built by scanning the schema modules rather than hard-coding a list, so a
    schema added later is validated by this bench without anyone remembering
    to register it here.
    """
    registry: dict[str, type[BaseModel]] = {}
    for module_name in _SCHEMA_MODULES:
        try:
            module = importlib.import_module(f"services.ai.langgraph.schemas.{module_name}")
        except ImportError:  # pragma: no cover - a schema module was renamed
            logger.warning("No se pudo importar el módulo de esquemas '%s'", module_name)
            continue
        for name, obj in vars(module).items():
            if inspect.isclass(obj) and issubclass(obj, BaseModel) and obj is not BaseModel:
                registry.setdefault(name, obj)
    return registry


def _describe_validation_error(exc: ValidationError) -> list[str]:
    """Pydantic's own errors, flattened to one readable line each.

    Kept in the provider-neutral form (`output.days: Field required`) because
    the point is to compare models, and every provider's raw error text says
    the same thing differently.
    """
    lines = []
    for error in exc.errors():
        location = ".".join(str(part) for part in error.get("loc", ())) or "(raíz)"
        lines.append(f"{location}: {error.get('msg', 'inválido')}")
    return lines


def validate_against_pipeline(schema_name: str | None, output: Any) -> dict[str, Any] | None:
    """Does this replayed answer satisfy the schema the pipeline enforces?

    Returns None when there is nothing to check (a free-text call, or a call
    that errored before producing anything) -- absent is not the same as
    passing, and the comparison table renders it as such.
    """
    if not schema_name or output is None:
        return None
    model = _schema_registry().get(schema_name)
    if model is None:
        return {
            "verdict": "sin comprobar",
            "ok": None,
            "schema": schema_name,
            "summary": f"No hay clase Pydantic llamada '{schema_name}'",
            "errors": [],
        }
    try:
        model.model_validate(output)
    except ValidationError as exc:
        errors = _describe_validation_error(exc)
        # The first few carry the diagnosis; a malformed week can raise
        # hundreds and none of them add anything after the first handful.
        shown = errors[:5]
        summary = "; ".join(shown)
        if len(errors) > len(shown):
            summary += f" (+{len(errors) - len(shown)} más)"
        return {
            "verdict": f"NO — {len(errors)} error(es)",
            "ok": False,
            "schema": schema_name,
            "summary": summary,
            "errors": errors,
        }
    return {"verdict": "sí", "ok": True, "schema": schema_name, "summary": "", "errors": []}


def _prepare_schema(output_schema: dict[str, Any] | None) -> dict[str, Any] | None:
    """Make a captured JSON Schema usable by any provider's structured output.

    Anthropic's captured `input_schema` carries no title; OpenAI derives the
    schema's name from one and refuses an untitled schema. Adding it back is
    the only translation needed -- the schema body itself is provider-neutral,
    which is exactly why the capture stores it rather than the raw call params.
    """
    if not output_schema:
        return None
    schema = dict(output_schema.get("schema") or {})
    if not schema:
        return None
    schema.setdefault("title", output_schema.get("name") or "output")
    # OpenAI rechaza una descripción de esquema de más de 1.024 caracteres, y la de
    # TrainingDecisionNoHitl es su docstring entero (2026-09-23). La llamada real no
    # la manda así; la réplica tampoco debe.
    if len(str(schema.get("description") or "")) > 1024:
        schema.pop("description")
    return schema


async def replay_call(
    storage_dir: Path,
    entry_id: str,
    selector: str,
    model_key: str,
    role: AgentRole = AgentRole.WORKOUT,
    save: bool = True,
) -> dict[str, Any]:
    """Send one archived prompt to `model_key` and record what came back.

    Read-only with respect to the athlete: it reads the archive, calls the
    provider, and writes only into `<profile>/replays/`. It never touches the
    plan, the season plan, or Garmin.
    """
    storage_dir = Path(storage_dir)
    index_entry = resolve_call(storage_dir, entry_id, selector)
    if index_entry is None:
        raise ValueError(f"No hay ninguna llamada '{selector}' en el archivo {entry_id}")

    call = load_prompt_call(storage_dir, entry_id, index_entry["call_id"])
    if call is None:
        raise ValueError(f"No se pudo leer la llamada {index_entry['call_id']}")

    messages = call.get("messages") or []
    if not messages:
        raise ValueError(f"La llamada {index_entry['call_id']} no tiene mensajes archivados")

    schema = _prepare_schema(call.get("output_schema"))
    llm = ModelSelector.get_llm_by_role_and_name(role, model_key)
    runnable = llm.with_structured_output(schema) if schema else llm

    started = time.perf_counter()
    error: str | None = None
    output: Any = None
    with get_usage_metadata_callback() as usage_cb:
        try:
            result = await runnable.ainvoke(messages)
            output = result if schema else getattr(result, "content", result)
        except Exception as exc:  # noqa: BLE001 - a failed replay is a result
            error = f"{type(exc).__name__}: {exc}"
            logger.warning("Replay failed on %s: %s", model_key, error)
    latency = time.perf_counter() - started

    record = {
        "entry_id": entry_id,
        "call_id": index_entry["call_id"],
        "node": call.get("node"),
        "original_model": call.get("model"),
        "replay_model": model_key,
        "replayed_at": datetime.now().isoformat(),
        "latency_seconds": round(latency, 2),
        "schema": (call.get("output_schema") or {}).get("name"),
        "cost": _cost_from_usage(usage_cb.usage_metadata),
        "error": error,
        "output": output,
        "prompt_chars": call.get("prompt_chars"),
        "summary": describe_output(output, prompt=prompt_text(call)) if output is not None else None,
        "validation": validate_against_pipeline((call.get("output_schema") or {}).get("name"), output),
    }

    if save:
        out_dir = storage_dir / REPLAYS_DIRNAME
        out_dir.mkdir(parents=True, exist_ok=True)
        path = out_dir / f"{entry_id}__{index_entry['call_id']}__{model_key}.json"
        path.write_text(json.dumps(record, indent=2, ensure_ascii=False, default=str), encoding="utf-8")
        record["path"] = str(path)

    return record


def prompt_text(call: dict[str, Any]) -> str:
    """Everything the model was told, as one searchable string."""
    return "\n\n".join(str(m.get("content") or "") for m in (call.get("messages") or []))



def _coerce_structured(response: Any, output_schema: dict[str, Any] | None) -> Any:
    """A structured answer captured as text, read back as the object it is.

    Providers differ again: Anthropic answers a structured call with tool-call
    arguments (already a dict), while Google's native JSON mode answers with
    the JSON as message text. Both are the same object; only one of them
    arrives looking like one, and a baseline scored as "a string of 900
    characters" compares against nothing.
    """
    if not output_schema or not isinstance(response, str):
        return response
    text = response.strip()
    if text.startswith("```"):
        text = text.split("\n", 1)[-1].rsplit("```", 1)[0]
    try:
        return json.loads(text)
    except (ValueError, TypeError):
        return response


def original_record(storage_dir: Path, entry_id: str, selector: str) -> dict[str, Any] | None:
    """The archived call's OWN answer, scored like a replay. Costs nothing.

    Every comparison should start from what actually happened. Paying to
    re-run the model that already ran, just to have a baseline, would be the
    bench charging for something it already has on disk.
    """
    index_entry = resolve_call(Path(storage_dir), entry_id, selector)
    if index_entry is None:
        return None
    call = load_prompt_call(Path(storage_dir), entry_id, index_entry["call_id"])
    if call is None or call.get("response") is None:
        return None

    usage = call.get("usage") or {}
    model = str(call.get("model") or "?")
    response = _coerce_structured(call.get("response"), call.get("output_schema"))
    return {
        "entry_id": entry_id,
        "call_id": index_entry["call_id"],
        "node": call.get("node"),
        "original_model": model,
        "replay_model": f"{model} (original)",
        "replayed_at": call.get("finished_at"),
        "latency_seconds": None,
        "schema": (call.get("output_schema") or {}).get("name"),
        "cost": {
            "cost_usd": ModelSelector.estimate_cost_usd(
                model, usage.get("input_tokens", 0), usage.get("output_tokens", 0),
            ),
            "input_tokens": usage.get("input_tokens", 0),
            "output_tokens": usage.get("output_tokens", 0),
            "total_tokens": usage.get("total_tokens", 0),
            "models": [{"name": model}],
        },
        "error": call.get("error"),
        "output": response,
        "prompt_chars": call.get("prompt_chars"),
        "summary": describe_output(response, prompt=prompt_text(call)),
    }


# --- units: what goes to the watch against what the step says ---------------
#
# Fase C (Beta 3.0, F10): «métrica de la réplica que compare unidades». Los fallos caros de este repo
# fueron de UNIDADES con los minutos bien: «200m trote» como 200 segundos, «15-20\" progresivos»
# como 90 s, y una bici de 6 × 1' codificada como 6 × 50' (banco del codificador del 2026-09-24). Un
# banco que compara minutos por deporte no ve nada de eso. Esto cuenta, paso a paso (y su recuperación),
# los que el reloj llevaría distinto de lo que dice su texto, con las MISMAS lecturas que la red
# `_duracion_como_dice_el_paso` del validador: un solo tiempo escrito y otro número fuera del ±20 %;
# metros copiados como segundos; o una distancia en el reloj cuando el texto sólo da un tiempo.


def _no_cuadra(texto: str, tipo: str | None, valor: Any) -> str | None:
    from services.ai.langgraph.validation.plan_validator import (
        _DISTANCIA_EN_TEXTO,
        _HOLGURA_DURACION,
        _segundos_escritos,
    )

    if not texto or valor in (None, "", 0):
        return None
    try:
        valor = float(valor)
    except (TypeError, ValueError):
        return None
    tiempos = _segundos_escritos(texto)
    distancias = _DISTANCIA_EN_TEXTO.findall(texto)
    if tipo == "time":
        if len(tiempos) == 1:
            lo, hi = tiempos[0]
            if lo > 0 and not (lo * (1 - _HOLGURA_DURACION) - 2 <= valor <= hi * (1 + _HOLGURA_DURACION) + 2):
                return f"«{texto}»: {valor:.0f} s"
        elif not tiempos and len(distancias) == 1:
            metros = float(distancias[0][0].replace(",", ".")) * (1000 if distancias[0][1] == "km" else 1)
            if abs(valor - metros) < 0.5:
                return f"«{texto}»: {valor:.0f} s (son metros)"
    elif tipo == "distance" and tiempos and not distancias:
        return f"«{texto}»: {valor:.0f} m (el texto da un tiempo)"
    return None


def unidades_que_no_cuadran(days: list[Any]) -> dict[str, Any] | None:
    """Pasos (y recuperaciones) cuyo valor en el reloj no cuadra con su texto. None si no hay pasos."""
    revisados, ejemplos = 0, []

    def pasos(lista):
        for paso in lista or []:
            if isinstance(paso, dict):
                yield paso
                yield from pasos(paso.get("substeps"))

    for day in days:
        if not isinstance(day, dict) or day.get("sport") == "strength":
            continue
        for paso in pasos(day.get("steps")):
            if paso.get("substeps"):
                continue
            revisados += 1
            fallo = _no_cuadra(str(paso.get("description") or ""), paso.get("duration_type"), paso.get("duration_value"))
            if fallo:
                ejemplos.append(f"{day.get('date')} {fallo}")
            if paso.get("recovery_duration_value"):
                revisados += 1
                fallo = _no_cuadra(str(paso.get("recovery_description") or ""), paso.get("recovery_duration_type"),
                                   paso.get("recovery_duration_value"))
                if fallo:
                    ejemplos.append(f"{day.get('date')} recuperación {fallo}")
    if not revisados:
        return None
    return {"pasos": revisados, "no_cuadran": len(ejemplos), "ejemplos": ejemplos[:8]}


# --- describing an output --------------------------------------------------

def describe_output(output: Any, prompt: str = "") -> dict[str, Any]:
    """A comparable summary of whatever a replayed node produced.

    Week-shaped outputs (anything with a `days` list) get the coaching summary
    below. Anything else -- a season plan, a summarizer's markdown -- falls
    back to size and shape, which is still enough to notice that one model
    returned half as much as another.
    """
    if isinstance(output, str):
        return {"shape": "text", "chars": len(output), "words": len(output.split())}

    if not isinstance(output, dict):
        return {"shape": type(output).__name__}

    payload = output
    # TrainingDecisionOutputs wraps the week in `output`; unwrap so a replay
    # against a model that emits the wrapper compares against one that
    # doesn't.
    if "days" not in payload and isinstance(payload.get("output"), dict):
        payload = payload["output"]

    days = payload.get("days")
    if not isinstance(days, list):
        return {"shape": "object", "keys": sorted(payload.keys())}

    minutes_by_sport: dict[str, float] = {}
    minutes_by_discipline: dict[str, float] = {}
    sessions_by_intensity: dict[str, int] = {}
    total_minutes = 0.0
    sessions = 0
    rest_days = 0
    empty_sketches = 0
    two_a_days = 0
    seen_dates: dict[str, int] = {}

    for day in days:
        if not isinstance(day, dict):
            continue
        date = str(day.get("date") or "")
        seen_dates[date] = seen_dates.get(date, 0) + 1
        if day.get("is_rest"):
            rest_days += 1
            continue
        sessions += 1
        minutes = float(day.get("total_duration_minutes") or 0)
        total_minutes += minutes
        sport = str(day.get("sport") or "?")
        discipline = str(day.get("discipline") or "?")
        intensity = str(day.get("intensity_class") or "?")
        minutes_by_sport[sport] = minutes_by_sport.get(sport, 0.0) + minutes
        minutes_by_discipline[discipline] = minutes_by_discipline.get(discipline, 0.0) + minutes
        sessions_by_intensity[intensity] = sessions_by_intensity.get(intensity, 0) + 1
        if not str(day.get("session_sketch") or "").strip():
            empty_sketches += 1

    two_a_days = sum(1 for count in seen_dates.values() if count > 1)

    rationale = str(payload.get("week_rationale") or "")
    summary: dict[str, Any] = {
        "shape": "week",
        "days": len(days),
        "sessions": sessions,
        "rest_days": rest_days,
        "two_a_days": two_a_days,
        "total_minutes": round(total_minutes, 1),
        "total_hours": round(total_minutes / 60.0, 2),
        "minutes_by_sport": {k: round(v, 1) for k, v in sorted(minutes_by_sport.items())},
        "minutes_by_discipline": {k: round(v, 1) for k, v in sorted(minutes_by_discipline.items())},
        "sessions_by_intensity": dict(sorted(sessions_by_intensity.items())),
        # A non-rest day with no session sketch is unusable downstream: the
        # formatter has nothing to encode. Counting them is the single most
        # direct measure of whether a model can do this job at all.
        "sessions_without_sketch": empty_sketches,
        "rationale_chars": len(rationale),
    }
    unidades = unidades_que_no_cuadran(days)
    if unidades is not None:
        summary["unidades"] = unidades
    if rationale:
        # A rationale legitimately quotes two kinds of number: what the model
        # was TOLD (the prompt) and what the model itself just DECIDED (its own
        # week, plus the totals that week adds up to -- "2.9 horas", "174
        # minutos"). Only a number from neither source is a candidate for
        # having been invented. Auditing against the prompt alone flagged every
        # correct arithmetic total, which is noise that would train a reader to
        # ignore the whole report.
        sources = [
            prompt,
            json.dumps(payload, ensure_ascii=False, default=str),
            f"{total_minutes:g} {total_minutes / 60.0:.2f} {round(total_minutes / 60.0, 1):g}",
        ]
        summary["rationale_numbers"] = audit_summary(rationale, sources)
    return summary


# --- comparing -------------------------------------------------------------

def load_replays(storage_dir: Path, entry_id: str | None = None, call_id: str | None = None) -> list[dict[str, Any]]:
    """Every saved replay for a profile, optionally narrowed to one call."""
    out_dir = Path(storage_dir) / REPLAYS_DIRNAME
    if not out_dir.is_dir():
        return []
    records: list[dict[str, Any]] = []
    for path in sorted(out_dir.glob("*.json")):
        try:
            record = json.loads(path.read_text(encoding="utf-8"))
        except Exception as exc:  # noqa: BLE001
            logger.warning("Could not read replay %s: %s", path.name, exc)
            continue
        if entry_id and record.get("entry_id") != entry_id:
            continue
        if call_id and record.get("call_id") != call_id:
            continue
        record["path"] = str(path)
        records.append(record)
    return records


COMPARISON_ROWS: list[tuple[str, str]] = [
    ("Modelo", "replay_model"),
    ("¿Lo aceptaría el pipeline?", "validation.verdict"),
    ("Campos que faltan", "validation.summary"),
    ("Costo real (USD)", "cost.cost_usd"),
    ("Tokens entrada", "cost.input_tokens"),
    ("Tokens salida", "cost.output_tokens"),
    ("Latencia (s)", "latency_seconds"),
    ("Error", "error"),
    ("Sesiones", "summary.sessions"),
    ("Días de descanso", "summary.rest_days"),
    ("Horas totales", "summary.total_hours"),
    ("Minutos por deporte", "summary.minutes_by_sport"),
    ("Minutos por disciplina", "summary.minutes_by_discipline"),
    ("Sesiones por intensidad", "summary.sessions_by_intensity"),
    ("Sesiones sin descripción", "summary.sessions_without_sketch"),
    ("Pasos revisados (unidades)", "summary.unidades.pasos"),
    ("Pasos que el reloj llevaría distinto", "summary.unidades.no_cuadran"),
    ("Ejemplos", "summary.unidades.ejemplos"),
    ("Números citados", "summary.rationale_numbers.numbers_checked"),
    ("Números sin respaldo", "summary.rationale_numbers.unsupported_count"),
]


def _dig(record: dict[str, Any], path: str) -> Any:
    value: Any = record
    for part in path.split("."):
        if not isinstance(value, dict):
            return None
        value = value.get(part)
    return value


def build_comparison(records: list[dict[str, Any]]) -> dict[str, Any]:
    """A side-by-side table of N replays of the same call.

    Returns rows rather than printed text so the CLI, a test, and any future
    UI can each render it their own way.
    """
    rows = []
    for label, path in COMPARISON_ROWS:
        values = [_dig(record, path) for record in records]
        if all(value in (None, "", {}, 0) for value in values) and label not in ("Modelo",):
            continue
        rows.append({"label": label, "path": path, "values": values})
    return {
        "models": [record.get("replay_model") for record in records],
        "call_id": records[0].get("call_id") if records else None,
        "entry_id": records[0].get("entry_id") if records else None,
        "rows": rows,
    }
