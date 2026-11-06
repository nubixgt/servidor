import json
import logging
from collections.abc import Callable
from datetime import datetime
from typing import Any

from langchain_core.callbacks.usage import get_usage_metadata_callback

from services.ai.ai_settings import AgentRole
from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState
from services.ai.model_config import ModelSelector
from services.ai.utils.retry_handler import AI_ANALYSIS_CONFIG, retry_with_backoff
from services.garmin.payload_pruning import measure, prune

from .node_base import create_cost_entry
from .prompt_components import AgentType, get_workflow_context
from .tool_calling_helper import extract_text_content

logger = logging.getLogger(__name__)

GENERIC_SUMMARIZER_SYSTEM_PROMPT = """## Goal
Preserve decision-relevant metrics from raw data with transparent compression.
## Principles
- Preserve: Keep meaningful numbers (measurements, counts, rates) that affect downstream decisions.
- Detect: Distinguish signal (measurements) from noise (IDs, nulls).
- Organize: Use tables and lists with clear time windows.
- Transparent Compression: You MAY compress long sequences if you show how and where.
- No Hidden Aggregation: If you summarize a sequence, expose the values or an explicit table of windows."""

GENERIC_SUMMARIZER_USER_PROMPT = """## Task
Extract and organize decision-relevant metrics from this data with transparent compression.

## Constraints
- Do NOT interpret or speculate.
- Exclude repeated nulls and structural IDs.
- You MAY compress long sequences, but show how (windows, ranges, or tables).

## Required Structure
1. **Coverage Header**: date range, sampling granularity, missing periods.
2. **Core Tables**: time index → main measurements.
3. **Change Points & Extremes**: highs/lows with timestamps.
4. **Data Quality Notes**: gaps, suspicious zeros, outliers.

## Input Data
```json
{data}
```

## Output Format
- Markdown tables for numeric data.
- Clear section headers.
- Consistent units.

Deliver a compact, decision-focused summary with explicit compression."""

SUMMARIZER_FINAL_CHECKLIST = """
## Final Checklist
- Factual only (no interpretation).
- Transparent compression (no hidden aggregation).
- Decision-relevant numbers prioritized."""


def create_data_summarizer_node(
    node_name: str,
    agent_role: AgentRole,
    data_extractor: Callable[[TrainingAnalysisState], dict[str, Any]],
    state_output_key: str,
    agent_type: AgentType,
    system_prompt: str | None = None,
    user_prompt: str | None = None,
) -> Callable:
    workflow_context = get_workflow_context(agent_type)
    base_system_prompt = system_prompt or GENERIC_SUMMARIZER_SYSTEM_PROMPT
    effective_system_prompt = workflow_context + base_system_prompt + SUMMARIZER_FINAL_CHECKLIST
    effective_user_prompt = user_prompt or GENERIC_SUMMARIZER_USER_PROMPT

    async def summarizer_node(state: TrainingAnalysisState) -> dict[str, list | str]:
        logger.info("Starting %s node", node_name)

        try:
            agent_start_time = datetime.now()

            raw_data = data_extractor(state)
            # v6 Track 6. Nulls, float representation noise and zero-length
            # laps removed, and the payload no longer pretty-printed -- 855 KB
            # of `indent=2` JSON is whitespace nobody reads and every model
            # pays for. Measured on a real season run: 852,795 -> 205,237
            # chars, 76% less, with every real measurement intact. See
            # services/garmin/payload_pruning.py for why nothing that carries
            # a number is touched.
            data_to_summarize = prune(raw_data)
            savings = measure(raw_data, data_to_summarize)
            logger.info(
                "%s payload: %d -> %d chars (%.1f%% less)",
                node_name, savings["chars_before"], savings["chars_after"], savings["saved_pct"],
            )

            async def call_llm():
                response = await ModelSelector.get_llm(agent_role).ainvoke(
                    [
                        {"role": "system", "content": effective_system_prompt},
                        {
                            "role": "user",
                            "content": effective_user_prompt.format(
                                data=json.dumps(
                                    data_to_summarize,
                                    separators=(",", ":"),
                                    ensure_ascii=False,
                                    default=str,
                                )
                            ),
                        },
                    ]
                )
                return extract_text_content(response)

            with get_usage_metadata_callback() as usage_cb:
                summary = await retry_with_backoff(
                    call_llm, AI_ANALYSIS_CONFIG, node_name
                )

            execution_time = (datetime.now() - agent_start_time).total_seconds()
            logger.info("%s completed in %.2fs", node_name, execution_time)

            return {
                state_output_key: summary,
                "costs": [create_cost_entry(
                    state_output_key.replace("_summary", "_summarizer"),
                    execution_time,
                    usage_cb.usage_metadata,
                )],
            }

        except Exception as exc:
            logger.error("%s node failed: %s", node_name, exc)
            return {"errors": [f"{node_name} failed: {exc}"]}

    return summarizer_node
