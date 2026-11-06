from __future__ import annotations

import logging
from collections.abc import Mapping
from typing import Any

logger = logging.getLogger(__name__)

_MISSING = object()



def _get_field(container: Any, field_name: str) -> Any:
    if container is None:
        return _MISSING

    if hasattr(container, field_name):
        return getattr(container, field_name)

    if isinstance(container, Mapping) and field_name in container:
        return container[field_name]

    return _MISSING


_PRIORITY_LABELS_ES = {
    1: "prioridad 1 (debe condicionar la decisión)",
    2: "prioridad 2 (a considerar)",
    3: "prioridad 3 (contexto)",
}


def _render_weight(data: Mapping) -> str:
    """One line saying how much this payload should count, and for how long.

    Rendered only from what the expert actually supplied: a legacy payload
    carrying none of these fields produces no line at all, rather than a row of
    defaults that would read as a judgement nobody made.
    """
    parts: list[str] = []

    priority = data.get("priority")
    if isinstance(priority, int) and priority in _PRIORITY_LABELS_ES:
        parts.append(_PRIORITY_LABELS_ES[priority])

    confidence = str(data.get("confidence") or "").strip()
    if confidence:
        parts.append(f"confianza {confidence}")

    horizon = data.get("horizon_days")
    if isinstance(horizon, int) and horizon > 0:
        parts.append(f"vigencia ~{horizon} días")

    return f"**Peso**: {' · '.join(parts)}" if parts else ""


def _render_receiver_payload(payload: Any) -> str:
    if payload is None:
        return ""

    if hasattr(payload, "model_dump"):
        data = payload.model_dump()
    elif isinstance(payload, Mapping):
        data = payload
    else:
        return str(payload)

    sections = {
        "Signals": data.get("signals", []),
        "Evidence": data.get("evidence", []),
        "Implications": data.get("implications", []),
    }
    uncertainty = data.get("uncertainty")
    if uncertainty:
        sections["Uncertainty"] = uncertainty

    lines: list[str] = []

    # Weight FIRST, not as a footnote (v5 Track 4). A consumer reading three of
    # these back to back needs to know how much each one counts before it reads
    # the claims -- otherwise it weighs them all the same, which is how one
    # night's HRV used to carry as much force as a six-week volume trend.
    header = _render_weight(data)
    if header:
        lines.extend([header, ""])

    for title, items in sections.items():
        lines.append(f"### {title}")
        if items:
            lines.extend([f"- {item}" for item in items])
        else:
            lines.append("- None")
        lines.append("")

    conflicts_with = str(data.get("conflicts_with") or "").strip()
    if conflicts_with:
        lines.extend(["### Posible contradicción con otro dominio", f"- {conflicts_with}", ""])

    return "\n".join(lines).strip()


def extract_expert_output(expert_output: Any, target_field: str) -> str:
    if expert_output is None:
        logger.warning(
            "Expert output is None for field '%s'. This expert may have failed. "
            "Proceeding with empty data for this domain.",
            target_field,
        )
        return (
            f"### {target_field.replace('_', ' ').title()}\n"
            f"- Expert analysis unavailable (expert node returned None — check logs for errors).\n"
        )



    output_container: Any = _MISSING
    if hasattr(expert_output, "output"):
        output_container = expert_output.output
    elif isinstance(expert_output, Mapping):
        output_container = expert_output.get("output")

    if isinstance(output_container, list):
        raise ValueError("Expert output contains questions, not analysis. HITL interaction required.")

    for candidate in (output_container, expert_output):
        payload = _get_field(candidate, target_field)
        if payload is not _MISSING:
            return _render_receiver_payload(payload)

    raise ValueError(f"Expert output missing '{target_field}' field. Type: {type(expert_output)}")


def extract_agent_content(value: Any) -> str:
    if not value:
        return ""

    if hasattr(value, "output"):
        output = value.output
        if isinstance(output, str):
            return output
        raise ValueError("AgentOutput contains questions, not content. HITL interaction required.")

    if isinstance(value, dict):
        result = value.get("output") or value.get("content")
        if isinstance(result, str):
            return result
        return str(value)

    if isinstance(value, str):
        return value

    return str(value)
