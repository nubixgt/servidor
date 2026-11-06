"""Record the exact prompt every agent was actually sent.

Plan Maestro v6 Track 1.

## Why this exists

v5 Track 7 archives what the agents *produced*. It does not archive what they
were *given*, and that turns out to be the half that answers the expensive
questions:

- **"Did the model invent this number, or was it handed a wrong one?"** The
  first real generation told the athlete their ACWR was 1.47 when Garmin said
  1.10 (v6 finding A1). Answering that took a grep through the prompt template
  plus a manual reconstruction of what the placeholders would have rendered
  to. With the real prompt on disk it is a text search.
- **"Would a different model have written a better week?"** Re-running the
  whole pipeline to find out costs a full generation and changes the inputs
  (fresh Garmin data, a different day). Replaying one archived prompt against
  another model costs one call and holds every input fixed -- see
  `services/ai/replay.py`.
- **"Did pruning the summarizer input lose signal?"** (v6 Track 6.) That is a
  before/after on the same prompt, which requires having the before.

## Why a callback and not per-node instrumentation

This hooks LangChain's chat-model boundary through the callback LangGraph
propagates to every nested run, so it captures *every* LLM call in a workflow
without any node knowing it exists -- including the ones a developer would
forget to instrument: the tier-2 repair calls inside the plan validator, and
whatever nodes get added later. A capture that depends on remembering to call
it is a capture that silently goes incomplete.

## Structured output is recorded portably

The three providers ask for structured output in three different shapes --
Google sends `response_json_schema`, OpenAI sends `response_format`, Anthropic
sends a single forced tool with an `input_schema` (all three verified live
against gemini-3.1-flash-lite, gpt-5.2 and claude-sonnet-5). What they have in
common is a JSON Schema, so that is what gets stored: a replay against a
*different* provider can hand the same schema to `with_structured_output()`
and get a comparable answer, which re-binding one provider's raw invocation
parameters could never do.

## It must never break a generation

Every hook is wrapped. An archive is a nice-to-have, and a callback that
raises would kill a run the athlete already paid for.
"""

from __future__ import annotations

import logging
from datetime import datetime
from typing import Any
from uuid import UUID

from langchain_core.callbacks import BaseCallbackHandler
from langchain_core.messages import convert_to_openai_messages

logger = logging.getLogger(__name__)


def extract_output_schema(invocation_params: dict[str, Any] | None) -> dict[str, Any] | None:
    """Pull the requested output JSON Schema out of a provider's call params.

    Returns `{"name": ..., "schema": {...}}`, or None for a free-text call.
    Anything unrecognised returns None rather than a guess, so a replay
    refuses to invent a contract the original call did not have.
    """
    params = invocation_params or {}
    try:
        # Google (langchain-google-genai): native JSON mode.
        schema = params.get("response_json_schema")
        if isinstance(schema, dict) and schema:
            return {"name": schema.get("title") or "output", "schema": schema}

        # OpenAI: response_format={"type": "json_schema", "json_schema": {...}}
        response_format = params.get("response_format")
        # ...o la CLASE Pydantic misma, que es lo que manda langchain-openai por la
        # Responses API (visto el 2026-09-23 con langchain-openai 1.6.0). Hasta
        # entonces ninguna llamada a GPT guardaba su esquema: la decisión semanal,
        # los expertos y el macrociclo se replicaban como texto libre, y esa era la
        # "limitación conocida" del banco de réplicas.
        if isinstance(response_format, type) and hasattr(response_format, "model_json_schema"):
            return {"name": response_format.__name__, "schema": response_format.model_json_schema()}
        if isinstance(response_format, dict):
            json_schema = response_format.get("json_schema")
            if isinstance(json_schema, dict):
                inner = json_schema.get("schema")
                if isinstance(inner, dict) and inner:
                    name = json_schema.get("name") or inner.get("title") or "output"
                    return {"name": name, "schema": inner}

        # Anthropic: one tool, forced via tool_choice.
        tools = params.get("tools")
        choice = params.get("tool_choice")
        if isinstance(tools, list) and tools:
            forced_name = choice.get("name") if isinstance(choice, dict) else None
            for tool in tools:
                if not isinstance(tool, dict):
                    continue
                function = tool.get("function") or {}
                name = tool.get("name") or function.get("name")
                if forced_name and name != forced_name:
                    continue
                schema = tool.get("input_schema") or function.get("parameters")
                if isinstance(schema, dict) and schema:
                    return {"name": name or "output", "schema": schema}
    except Exception as exc:  # noqa: BLE001 - see module docstring
        logger.debug("Could not read output schema from invocation params: %s", exc)
    return None


def _messages_to_plain(messages: Any) -> list[dict[str, Any]]:
    """Flatten LangChain's list-of-lists of BaseMessage into plain dicts.

    `on_chat_model_start` hands over a batch (one inner list per prompt).
    Every call this app makes is a batch of one, and flattening stays correct
    if that ever stops being true.
    """
    flat: list[Any] = []
    for group in messages or []:
        if isinstance(group, list):
            flat.extend(group)
        else:
            flat.append(group)
    try:
        return convert_to_openai_messages(flat)
    except Exception:  # noqa: BLE001 - see module docstring
        return [
            {
                "role": getattr(message, "type", "unknown"),
                "content": str(getattr(message, "content", message)),
            }
            for message in flat
        ]


def _response_payload(response: Any) -> Any:
    """What the model actually answered, in a comparable form.

    v6 Track 5. Capturing this turns every archived call into its own
    baseline: a replay against another model can be scored side by side with
    what really happened, for free, instead of paying to re-run the original
    just to have something to compare against.

    Structured-output calls answer with a forced tool call whose arguments ARE
    the object, so those are preferred over the (usually empty) text.
    """
    try:
        for generations in getattr(response, "generations", []) or []:
            for generation in generations or []:
                message = getattr(generation, "message", None)
                for call in (getattr(message, "tool_calls", None) or []):
                    args = call.get("args") if isinstance(call, dict) else getattr(call, "args", None)
                    if args:
                        return args
                content = getattr(message, "content", None)
                if isinstance(content, str) and content.strip():
                    return content
                if isinstance(content, list):
                    text = "".join(
                        block.get("text", "") for block in content if isinstance(block, dict)
                    )
                    if text.strip():
                        return text
                text = getattr(generation, "text", None)
                if isinstance(text, str) and text.strip():
                    return text
    except Exception as exc:  # noqa: BLE001 - see module docstring
        logger.debug("Prompt capture could not read the response: %s", exc)
    return None


class PromptCapture(BaseCallbackHandler):
    """Collects every chat-model call made under it, in order.

    One instance per workflow run. Attach it via the run config's `callbacks`
    and LangGraph propagates it to every node and every nested chain.
    """

    # BaseCallbackHandler honours this: with it False, LangChain logs a
    # callback exception and carries on instead of failing the run.
    raise_error = False

    def __init__(self) -> None:
        self.calls: list[dict[str, Any]] = []
        self._by_run: dict[str, dict[str, Any]] = {}

    # --- capture ---------------------------------------------------------

    def on_chat_model_start(
        self,
        serialized: dict[str, Any],
        messages: Any,
        *,
        run_id: UUID,
        parent_run_id: UUID | None = None,
        tags: list[str] | None = None,
        metadata: dict[str, Any] | None = None,
        **kwargs: Any,
    ) -> None:
        try:
            meta = metadata or {}
            params = kwargs.get("invocation_params") or {}
            record: dict[str, Any] = {
                "index": len(self.calls),
                "run_id": str(run_id),
                "parent_run_id": str(parent_run_id) if parent_run_id else None,
                # LangGraph stamps the node name onto every run it spawns.
                # Without it a prompt is an orphan: replaying it still works,
                # but nobody can tell which agent it belonged to.
                "node": meta.get("langgraph_node") or "unknown",
                "step": meta.get("langgraph_step"),
                "provider": meta.get("ls_provider"),
                "model": meta.get("ls_model_name") or params.get("model") or params.get("model_name"),
                "started_at": datetime.now().isoformat(),
                "messages": _messages_to_plain(messages),
                "output_schema": extract_output_schema(params),
                "tags": list(tags or []),
            }
            record["prompt_chars"] = sum(len(str(m.get("content") or "")) for m in record["messages"])
            self.calls.append(record)
            self._by_run[record["run_id"]] = record
        except Exception as exc:  # noqa: BLE001 - see module docstring
            logger.debug("Prompt capture skipped a call: %s", exc)

    def on_llm_end(self, response: Any, *, run_id: UUID, **kwargs: Any) -> None:
        """Attach what the call actually cost.

        Token counts come from the provider's own usage metadata -- the same
        source the cost report uses -- so a replay's economics can be compared
        against the original run's on equal terms.
        """
        try:
            record = self._by_run.get(str(run_id))
            if record is None:
                return
            usage: dict[str, Any] = {}
            llm_output = getattr(response, "llm_output", None) or {}
            if isinstance(llm_output, dict):
                usage = llm_output.get("token_usage") or llm_output.get("usage") or {}
            if not usage:
                for generations in getattr(response, "generations", []) or []:
                    for generation in generations or []:
                        candidate = getattr(getattr(generation, "message", None), "usage_metadata", None)
                        if candidate:
                            usage = dict(candidate)
                            break
                    if usage:
                        break
            record["usage"] = {
                "input_tokens": usage.get("input_tokens") or usage.get("prompt_tokens") or 0,
                "output_tokens": usage.get("output_tokens") or usage.get("completion_tokens") or 0,
                "total_tokens": usage.get("total_tokens") or 0,
            }
            record["response"] = _response_payload(response)
            record["finished_at"] = datetime.now().isoformat()
        except Exception as exc:  # noqa: BLE001 - see module docstring
            logger.debug("Prompt capture could not read usage: %s", exc)

    def on_llm_error(self, error: BaseException, *, run_id: UUID, **kwargs: Any) -> None:
        try:
            record = self._by_run.get(str(run_id))
            if record is not None:
                record["error"] = str(error)[:500]
                record["finished_at"] = datetime.now().isoformat()
        except Exception:  # noqa: BLE001 - see module docstring
            pass

    # --- reporting -------------------------------------------------------

    def summary(self) -> dict[str, Any]:
        by_node: dict[str, int] = {}
        for call in self.calls:
            by_node[call["node"]] = by_node.get(call["node"], 0) + 1
        return {
            "calls": len(self.calls),
            "prompt_chars": sum(call.get("prompt_chars", 0) for call in self.calls),
            "calls_by_node": by_node,
        }
