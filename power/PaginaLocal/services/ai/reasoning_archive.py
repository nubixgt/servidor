"""Persist everything the agents actually thought, not just what they decided.

Plan Maestro v5 Track 7.

Before this module, a generation left five files on disk: `season_plan.md`,
`weekly_plan.json`, `summary.json`, `analysis.html` and `planning.html`. What
it did NOT leave was almost all of the reasoning that produced them:

- the three summarizers' condensed views of the raw Garmin payload,
- the nine expert payloads (3 experts x 3 receivers: synthesis, season
  planner, weekly planner),
- the synthesis agent's own markdown (only its HTML rendering survived),
- the decision layer's `WeekIntentContent` -- the actual coaching call, made
  before any Garmin encoding, which is the single most interesting artefact
  of the whole run.

All of it was paid for with real API credits and thrown away when the process
exited. It is also the only material that can answer "why does my week look
like this?" with the real reason rather than a reconstruction, which is what
makes the coach chatbot possible at all.

Layout, one directory per generation, newest last by name:

    data/storage/<profile>/reasoning/<ISO-timestamp>/
        manifest.json          what kind of run, when, what it cost, what failed
        summarizer_*.md        the three condensed data views
        expert_*.json          each expert's three receiver payloads
        synthesis.md           the comprehensive report, pre-HTML
        season_plan.json       structured season plan content
        week_intents.json      the decision layer's per-day coaching calls
        plan_validation.json   what the deterministic rules found and did

Nothing is pruned. A missing artefact is written as an explicit marker rather
than silently omitted, so "the physiology expert produced nothing that run" is
distinguishable from "we forgot to save it".
"""

from __future__ import annotations

import ast
import json
import logging
import re
import shutil
from datetime import datetime
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)

REASONING_DIRNAME = "reasoning"
# v6 Track 1: the prompts themselves, one file per LLM call, captured by
# services/ai/prompt_capture.py. Kept in a subdirectory so the entry's top
# level stays readable and so load_reasoning_entry() (which feeds the coach
# chatbot) does not start dragging 2 MB of prompt text into a chat context.
PROMPTS_DIRNAME = "prompts"

_EXPERT_STATE_KEYS = {
    "metrics": "metrics_outputs",
    "physiology": "physiology_outputs",
    "activity": "activity_outputs",
}

_SUMMARY_STATE_KEYS = {
    "metrics": "metrics_summary",
    "physiology": "physiology_summary",
    "activity": "activity_summary",
}


def _jsonable(value: Any) -> Any:
    """Best-effort conversion of pydantic models / LangChain objects to JSON.

    Deliberately never raises: losing the whole archive because one nested
    object did not serialize would defeat the point of having it.
    """
    if value is None or isinstance(value, (str, int, float, bool)):
        return value
    if hasattr(value, "model_dump"):
        try:
            return value.model_dump()
        except Exception:  # noqa: BLE001 - see docstring
            return str(value)
    if isinstance(value, dict):
        return {str(k): _jsonable(v) for k, v in value.items()}
    if isinstance(value, (list, tuple)):
        return [_jsonable(v) for v in value]
    return str(value)


def _write_json(path: Path, payload: Any) -> None:
    path.write_text(json.dumps(_jsonable(payload), indent=2, ensure_ascii=False), encoding="utf-8")


def _write_text(path: Path, text: str) -> None:
    path.write_text(text, encoding="utf-8")


def _extract_text(value: Any) -> str:
    """Pull the markdown out of whatever shape a text-producing node returned.

    Summarizers return a plain string; synthesis returns a string but has
    historically been wrapped by the tool-calling helper, so accept both.
    """
    if value is None:
        return ""
    if isinstance(value, str):
        return value
    content = getattr(value, "content", None)
    if isinstance(content, str):
        return content
    # Gemini 3.5 devuelve el contenido como LISTA de partes
    # ([{"type": "text", "text": "..."}]). Sin esta rama caia al `str(value)`
    # del final y la traza guardaba "content=[{'type': 'text', 'text': '# Informe..."
    # -- visto el 2026-09-11 en synthesis.md, que leen el chat del coach y el
    # informe de analisis re-renderizado.
    if isinstance(content, list):
        partes = [
            p.get("text", "") if isinstance(p, dict) else p
            for p in content
            if (isinstance(p, dict) and p.get("type", "text") == "text") or isinstance(p, str)
        ]
        texto = "".join(partes)
        if texto.strip():
            return texto
    output = getattr(value, "output", None)
    if isinstance(output, str):
        return output
    if isinstance(value, dict):
        for key in ("content", "output", "text"):
            candidate = value.get(key)
            if isinstance(candidate, str):
                return candidate
    return str(value)


def _slug(text: str) -> str:
    keep = [c if (c.isalnum() or c in "-_") else "-" for c in (text or "unknown")]
    return "".join(keep).strip("-")[:48] or "unknown"


def _write_prompt_calls(entry_dir: Path, calls: list[dict[str, Any]]) -> dict[str, Any]:
    """One file per LLM call, plus an index that can be read without them.

    Split rather than stored as a single blob because the whole point is
    selective replay: reading "which calls does this run have?" must not cost
    the megabytes of prompt text that answering it fully would.
    """
    if not calls:
        # No directory at all rather than an empty one: a run whose capture
        # produced nothing should look exactly like a run from before the
        # capture existed, not like one whose prompts went missing.
        return {"prompt_calls": 0, "prompt_bytes": 0}

    prompts_dir = entry_dir / PROMPTS_DIRNAME
    prompts_dir.mkdir(parents=True, exist_ok=True)

    index: list[dict[str, Any]] = []
    total_bytes = 0
    for call in calls:
        call_id = f"{call.get('index', 0):03d}__{_slug(str(call.get('node')))}__{_slug(str(call.get('model')))}"
        path = prompts_dir / f"{call_id}.json"
        _write_json(path, {**call, "call_id": call_id})
        size = path.stat().st_size
        total_bytes += size
        index.append({
            "call_id": call_id,
            "index": call.get("index"),
            "node": call.get("node"),
            "model": call.get("model"),
            "provider": call.get("provider"),
            "output_schema": (call.get("output_schema") or {}).get("name"),
            "prompt_chars": call.get("prompt_chars"),
            "usage": call.get("usage"),
            "error": call.get("error"),
            "bytes": size,
        })

    _write_json(prompts_dir / "index.json", {"calls": index})
    return {"prompt_calls": len(index), "prompt_bytes": total_bytes}


def list_prompt_calls(storage_dir: Path, entry_id: str) -> list[dict[str, Any]]:
    """The index of captured prompts for one archived generation."""
    index_path = storage_dir / REASONING_DIRNAME / entry_id / PROMPTS_DIRNAME / "index.json"
    if not index_path.is_file():
        return []
    try:
        return json.loads(index_path.read_text(encoding="utf-8")).get("calls") or []
    except Exception as exc:  # noqa: BLE001
        logger.warning("Could not read prompt index for %s: %s", entry_id, exc)
        return []


def load_prompt_call(storage_dir: Path, entry_id: str, call_id: str) -> dict[str, Any] | None:
    """One captured call: its messages, its model, its requested schema."""
    path = storage_dir / REASONING_DIRNAME / entry_id / PROMPTS_DIRNAME / f"{call_id}.json"
    if not path.is_file():
        return None
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception as exc:  # noqa: BLE001
        logger.warning("Could not read prompt call %s/%s: %s", entry_id, call_id, exc)
        return None


def save_reasoning(
    storage_dir: Path,
    result: dict[str, Any],
    kind: str,
    extra: dict[str, Any] | None = None,
    timestamp: datetime | None = None,
) -> str | None:
    """Write one generation's full reasoning trail. Returns the entry id.

    `result` is the workflow's final state (both `run_analysis_and_season_planning`
    and `run_week_generation` return it whole). `kind` is "season" or "week".
    `extra` holds anything the workflow state does not carry -- the athlete's
    check-in, the recovery snapshot, the volume baseline.

    Never raises: an archive is a nice-to-have, and failing to write it must
    not fail a generation the athlete already paid for.
    """
    try:
        stamp = (timestamp or datetime.now()).strftime("%Y-%m-%dT%H-%M-%S")
        entry_dir = storage_dir / REASONING_DIRNAME / stamp
        entry_dir.mkdir(parents=True, exist_ok=True)

        missing: list[str] = []

        for domain, state_key in _SUMMARY_STATE_KEYS.items():
            text = _extract_text(result.get(state_key))
            if text.strip():
                _write_text(entry_dir / f"summarizer_{domain}.md", text)
            else:
                missing.append(state_key)

        for domain, state_key in _EXPERT_STATE_KEYS.items():
            payload = result.get(state_key)
            if payload is not None:
                _write_json(entry_dir / f"expert_{domain}.json", payload)
            else:
                missing.append(state_key)

        synthesis = _extract_text(result.get("synthesis_result"))
        if synthesis.strip():
            _write_text(entry_dir / "synthesis.md", synthesis)
        elif kind != "week":
            # v6 (A8). A week generation is `3 summarizers -> 3 experts ->
            # weekly_planner -> plan_formatter`: there IS no synthesis node in
            # it, so reporting the artefact as missing was the archive
            # accusing itself of losing something that never existed. Same
            # shape as the `week_intents` condition below, and the same fix.
            missing.append("synthesis_result")

        season_plan = result.get("season_plan")
        if season_plan is not None:
            _write_json(entry_dir / "season_plan.json", season_plan)

        week_intents = result.get("week_intents")
        if week_intents:
            _write_json(entry_dir / "week_intents.json", week_intents)
        elif kind == "week":
            missing.append("week_intents")

        validation = result.get("weekly_plan_validation")
        if validation is not None:
            _write_json(entry_dir / "plan_validation.json", validation)

        for name, payload in (extra or {}).items():
            if payload is not None:
                _write_json(entry_dir / f"{name}.json", payload)

        # v6 Track 1: what each agent was actually SENT, not just what it
        # produced. Attached to the final state by the workflow runners in
        # planning_workflow.py; absent for a run that predates the capture or
        # one where it failed, which the manifest then records as 0 calls
        # rather than pretending the run had none.
        prompt_stats = _write_prompt_calls(entry_dir, result.get("prompt_calls") or [])

        cost_summary = result.get("cost_summary") or {}
        _write_json(entry_dir / "manifest.json", {
            "id": stamp,
            "kind": kind,
            "generated_at": (timestamp or datetime.now()).isoformat(),
            "athlete_name": result.get("athlete_name"),
            "execution_id": result.get("execution_id"),
            "total_cost_usd": cost_summary.get("total_cost_usd"),
            "total_tokens": cost_summary.get("total_tokens"),
            "agents": [a.get("name") for a in (cost_summary.get("agents") or [])],
            **prompt_stats,
            "workflow_errors": result.get("errors") or [],
            # Named explicitly so an absent file reads as "this node produced
            # nothing on this run" rather than "the archive is incomplete".
            "missing": missing,
            # The complete directory listing, manifest included -- it is
            # written last, so it has to add itself rather than be scanned.
            "files": sorted([p.name for p in entry_dir.iterdir()] + ["manifest.json"]),
        })

        if missing:
            logger.info("Reasoning archive %s written with %d missing artefact(s): %s",
                        stamp, len(missing), ", ".join(missing))
        return stamp

    except Exception as exc:  # noqa: BLE001 - see docstring
        logger.warning("Could not archive reasoning trail: %s", exc)
        return None


def list_reasoning_entries(storage_dir: Path) -> list[dict[str, Any]]:
    """Every archived generation for a profile, newest first.

    Each item is the entry's manifest, plus its `id` and `path`. Entries whose
    manifest is unreadable are still listed, flagged rather than hidden.
    """
    base = storage_dir / REASONING_DIRNAME
    if not base.is_dir():
        return []

    entries: list[dict[str, Any]] = []
    for entry_dir in sorted(base.iterdir(), reverse=True):
        if not entry_dir.is_dir():
            continue
        manifest: dict[str, Any] = {"id": entry_dir.name}
        manifest_path = entry_dir / "manifest.json"
        if manifest_path.is_file():
            try:
                manifest = {**json.loads(manifest_path.read_text(encoding="utf-8")), "id": entry_dir.name}
            except Exception as exc:  # noqa: BLE001
                manifest["manifest_error"] = str(exc)
        else:
            manifest["manifest_error"] = "manifest.json ausente"
        manifest["path"] = str(entry_dir)
        entries.append(manifest)
    return entries


def load_reasoning_entry(storage_dir: Path, entry_id: str) -> dict[str, str] | None:
    """Read one archived generation back as {filename: contents}.

    JSON files come back as their raw text -- the caller (the coach chatbot)
    wants them as prompt material, not as re-parsed objects.
    """
    entry_dir = storage_dir / REASONING_DIRNAME / entry_id
    if not entry_dir.is_dir():
        return None
    contents: dict[str, str] = {}
    for path in sorted(entry_dir.iterdir()):
        if path.is_file():
            try:
                texto = path.read_text(encoding="utf-8")
                contents[path.name] = rescatar_markdown(texto) if path.suffix == ".md" else texto
            except Exception as exc:  # noqa: BLE001
                contents[path.name] = f"<no se pudo leer: {exc}>"
    return contents


_TEXTO_DEL_REPR = re.compile(r"""'text':\s*('(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*")""", re.S)


def rescatar_markdown(texto: str) -> str:
    """El markdown de una traza escrita antes de que `_extract_text` supiera de listas.

    Esas trazas guardaban el repr de un AIMessage ("content=[{'type': 'text',
    'text': '# Informe...\n...'}] additional_kwargs=..."): el modelo del coach
    lo leia con las secuencias de escape, y pintado era un unico parrafo de
    90.000 px de ancho. Se saca el literal de 'text' tal cual; si no se puede,
    se devuelve el texto sin tocar.
    """
    if not texto.lstrip().startswith("content="):
        return texto
    partes = []
    for literal in _TEXTO_DEL_REPR.findall(texto):
        try:
            partes.append(ast.literal_eval(literal))
        except (ValueError, SyntaxError):
            return texto
    return "".join(partes) or texto


def latest_reasoning_entry(storage_dir: Path, kind: str | None = None) -> dict[str, Any] | None:
    """The most recent archived generation, optionally of a given kind."""
    for entry in list_reasoning_entries(storage_dir):
        if kind is None or entry.get("kind") == kind:
            return entry
    return None


# Cuántas trazas se conservan por atleta, y por qué ese número.
#
# Una generación real ocupa ~500 KB, y tres cuartas partes son los prompts
# capturados. Con siete atletas generando una semana cada uno, eso son ~15 MB
# al mes creciendo para siempre: `save_reasoning` nunca ha borrado nada.
#
# Doce es lo que hace falta para lo que las trazas sirven de verdad:
#
#   - El chat las lee para explicar el plan VIGENTE, y sólo mira la última.
#   - Las gráficas del panel sacan de ellas la serie histórica de forma, carga
#     y recuperación (`fitness_series.json`, `load_state.json`). Eso es
#     historial del ATLETA y es la razón por la que borrarlas con el plan
#     sería un error: doce generaciones cubren un trimestre largo.
#   - `scripts/replay_node.py` necesita prompts recientes, no antiguos: un
#     prompt de hace medio año se compara contra un pipeline que ya cambió.
#
# Se poda por ANTIGÜEDAD y no por peso: el peso de una traza depende de cuánto
# escribió el modelo ese día, y borrar "la más gorda" retiraría justo la
# generación más interesante.
TRAZAS_QUE_SE_CONSERVAN = 12


def _borrar_directorio(ruta: Path, intentos: int = 4) -> None:
    """`shutil.rmtree` con reintentos, porque en Windows falla a medias.

    Visto el 2026-09-11 en local: al borrar el plan, las 20 trazas del atleta 2
    perdian sus ficheros pero `os.rmdir` de `prompts/` daba "Acceso denegado"
    (OneDrive o el indexador tienen la carpeta abierta un instante). La traza
    quedaba a medias y seguia listada -- y el informe de analisis la podia
    re-renderizar para un plan que ya no existe. Un segundo intento, un momento
    despues, casi siempre pasa. En Linux (produccion) el primero ya basta.
    """
    import time

    for intento in range(intentos):
        try:
            shutil.rmtree(ruta)
            return
        except FileNotFoundError:
            return
        except OSError:
            if intento == intentos - 1:
                raise
            time.sleep(0.25 * (intento + 1))


def podar_trazas(storage_dir: Path, conservar: int = TRAZAS_QUE_SE_CONSERVAN) -> int:
    """Deja sólo las `conservar` trazas más recientes. Devuelve cuántas quitó.

    Nunca revienta: perder una poda es un directorio de más en disco; que
    reviente al terminar una generación es un plan que el atleta ya pagó.
    """
    entradas = list_reasoning_entries(storage_dir)   # ya vienen de nueva a vieja
    sobran = entradas[max(0, int(conservar)):]
    quitadas = 0
    for entrada in sobran:
        ruta = Path(entrada.get("path") or (storage_dir / REASONING_DIRNAME / entrada["id"]))
        try:
            _borrar_directorio(ruta)
            quitadas += 1
        except OSError:
            logger.warning("No se pudo podar la traza %s", entrada.get("id"), exc_info=True)
    if quitadas:
        logger.info("Podadas %d traza(s) de razonamiento; se conservan %d", quitadas, conservar)
    return quitadas


def borrar_trazas(storage_dir: Path, kind: str | None = None) -> int:
    """Borra las trazas del atleta, o sólo las de un tipo. Devuelve cuántas.

    Existe para el borrado del plan: una traza de la semana explica una semana
    que ya no está, y conservarla no sirve a nadie -- el chat ya la ignora
    (ver `_ultima_traza_todavia_vigente` en coach_chat.py), pero seguía
    ocupando sitio para siempre.

    **Las de temporada no se van con la semana.** Son de escalas distintas y
    se borran por separado, igual que los planes: `kind` decide.
    """
    quitadas = 0
    for entrada in list_reasoning_entries(storage_dir):
        if kind is not None and entrada.get("kind") != kind:
            continue
        ruta = Path(entrada.get("path") or (storage_dir / REASONING_DIRNAME / entrada["id"]))
        try:
            _borrar_directorio(ruta)
            quitadas += 1
        except OSError:
            logger.warning("No se pudo borrar la traza %s", entrada.get("id"), exc_info=True)
    return quitadas
