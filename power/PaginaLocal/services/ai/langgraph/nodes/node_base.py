import logging
from collections.abc import Callable
from datetime import datetime
from typing import Any

from langgraph.errors import GraphInterrupt
from services.ai.model_config import ModelSelector
from services.ai.tools.plotting import PlotStorage, create_plotting_tools

logger = logging.getLogger(__name__)


def configure_node_tools(
    agent_name: str,
    plot_storage: PlotStorage | None = None,
    plotting_enabled: bool = False,
) -> list:
    tools = []

    if plotting_enabled and plot_storage:
        plotting_tool = create_plotting_tools(plot_storage, agent_name=agent_name)
        tools.append(plotting_tool)
        logger.debug("%s: Added plotting tool", agent_name)

    return tools


def create_cost_entry(
    agent_name: str,
    execution_time: float,
    usage_metadata: dict[str, dict[str, Any]] | None = None,
) -> dict[str, Any]:
    """usage_metadata is what langchain_core's get_usage_metadata_callback()
    collects for every LLM call made inside its `with` block, keyed by model
    name -> {"input_tokens", "output_tokens", "total_tokens", ...}. This is
    the REAL per-generation cost source (each call's actual token usage x
    model_config.py's price table) -- it works even though most nodes call
    `.with_structured_output()` without include_raw=True and so never see the
    raw AIMessage themselves, because the callback hooks the LLM run directly
    rather than inspecting the return value. Before this, node cost entries
    only carried execution_time/timestamp and the app's only cost figure came
    from an optional LangSmith integration that reports $0.0000 whenever
    LANGSMITH_API_KEY isn't configured -- which was always the case here."""
    entry: dict[str, Any] = {
        "agent": agent_name,
        "execution_time": execution_time,
        "timestamp": datetime.now().isoformat(),
        "cost_usd": 0.0,
        "tokens": 0,
        "input_tokens": 0,
        "output_tokens": 0,
        "models": [],
    }

    for model_name, usage in (usage_metadata or {}).items():
        input_tokens = usage.get("input_tokens", 0) or 0
        output_tokens = usage.get("output_tokens", 0) or 0
        total_tokens = usage.get("total_tokens", input_tokens + output_tokens) or 0
        cost_usd = ModelSelector.estimate_cost_usd(model_name, input_tokens, output_tokens)

        entry["cost_usd"] += cost_usd
        entry["tokens"] += total_tokens
        entry["input_tokens"] += input_tokens
        entry["output_tokens"] += output_tokens
        entry["models"].append({
            "name": model_name,
            "cost_usd": cost_usd,
            "input_tokens": input_tokens,
            "output_tokens": output_tokens,
            "total_tokens": total_tokens,
        })

    return entry


def create_plot_entries(agent_name: str, plot_storage: PlotStorage) -> tuple[list, dict, list]:

    all_plots = plot_storage.get_all_plots()
    timestamp_iso = datetime.now().isoformat()

    plots = [
        {
            "agent": agent_name,
            "plot_id": plot_id,
            "timestamp": timestamp_iso,
        }
        for plot_id in all_plots
    ]

    plot_storage_data = {
        plot_id: {
            "plot_id": metadata.plot_id,
            "description": metadata.description,
            "agent_name": metadata.agent_name,
            "created_at": metadata.created_at.isoformat(),
            "html_content": metadata.html_content,
            "data_summary": metadata.data_summary,
        }
        for plot_id, metadata in all_plots.items()
    }

    return plots, plot_storage_data, list(all_plots.keys())


async def execute_node_with_error_handling(
    node_name: str,
    node_function: Callable,
    error_message_prefix: str,
) -> dict[str, Any]:

    try:
        return await node_function()

    except GraphInterrupt:
        logger.info("%s: GraphInterrupt raised - propagating to LangGraph", node_name)
        raise

    except Exception as exc:
        logger.exception("%s failed", node_name)
        return {"errors": [f"{error_message_prefix}: {exc!s}"]}


def log_node_completion(node_name: str, execution_time: float, plot_count: int = 0) -> None:
    if plot_count > 0:
        logger.info("%s completed in %.2fs with %s plots", node_name, execution_time, plot_count)
        return

    logger.info("%s completed in %.2fs", node_name, execution_time)
