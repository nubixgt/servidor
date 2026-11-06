import logging
import os
import re
from dataclasses import dataclass
from typing import Any

from langchain_anthropic import ChatAnthropic
from langchain_openai import ChatOpenAI

try:
    from langchain_google_genai import ChatGoogleGenerativeAI
    _GOOGLE_GENAI_AVAILABLE = True
except ImportError:
    _GOOGLE_GENAI_AVAILABLE = False
    ChatGoogleGenerativeAI = None

from core.config import get_config

from .ai_settings import AgentRole, ai_settings

logger = logging.getLogger(__name__)

OPENROUTER_BASE_URL = "https://openrouter.ai/api/v1"

# Cuánto puede tardar UNA llamada a un modelo antes de darse por colgada. Las
# más largas medidas son la decisión semanal y el macrociclo (2-3 minutos) y el
# codificador con 16.000 tokens de salida; ocho minutos les da el doble largo
# sin dejar una generación esperando para siempre. Ver get_llm_by_role_and_name.
TIEMPO_LIMITE_LLM_S = float(os.getenv("LLM_TIMEOUT_S", "480"))
GOOGLE_GENAI_BASE_URL = "https://generativelanguage.googleapis.com"


@dataclass
class ModelConfiguration:
    name: str
    base_url: str
    openrouter_name: str | None = None


class ModelSelector:

    @staticmethod
    def _detect_provider(base_url: str) -> str:
        if "anthropic" in base_url:
            return "anthropic"
        elif "openai.com" in base_url:
            return "openai"
        elif "googleapis.com" in base_url:
            return "google"
        else:
            return "openrouter"

    CONFIGURATIONS: dict[str, ModelConfiguration] = {
        # OpenAI Models
        "gpt-4o": ModelConfiguration(
            name="gpt-4o",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-4o",
        ),
        "gpt-4.1": ModelConfiguration(
            name="gpt-4.1",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-4.1",
        ),
        "gpt-4o-mini": ModelConfiguration(
            name="gpt-4o-mini",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-4o-mini",
        ),
        "o1": ModelConfiguration(
            name="o1-preview",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/o1-preview",
        ),
        "o1-mini": ModelConfiguration(
            name="o1-mini",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/o1-mini",
        ),
        "o3": ModelConfiguration(
            name="o3",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/o3",
        ),
        "o3-mini": ModelConfiguration(
            name="o3-mini",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/o3-mini",
        ),
        "o4-mini": ModelConfiguration(
            name="o4-mini",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/o4-mini",
        ),
        "gpt-5": ModelConfiguration(
            name="gpt-5.2",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-5.2",
        ),
        # Same model as "gpt-5", different thinking budget. See MODEL_CONFIGS.
        "gpt-5-mid": ModelConfiguration(
            name="gpt-5.2",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-5.2",
        ),
        "gpt-5.2-pro": ModelConfiguration(
            name="gpt-5.2-pro",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-5.2-pro",
        ),
        "gpt-5-search": ModelConfiguration(
            name="gpt-5.2",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-5.2",
        ),
        "gpt-5.2-pro-search": ModelConfiguration(
            name="gpt-5.2-pro",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-5.2-pro",
        ),
        # Anthropic Models
        "claude-sonnet": ModelConfiguration(
            name="claude-sonnet-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-sonnet-5",
        ),
        "claude-opus": ModelConfiguration(
            name="claude-opus-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-opus-5",
        ),
        "claude-haiku": ModelConfiguration(
            name="claude-haiku-4-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-haiku-4.5",
        ),
        # Haiku 5.5 (2026-10-07). Para el banco del codificador del 2026-10-19; ningún rol lo usa
        # hasta que el responsable lo decida con los números.
        "claude-haiku-55": ModelConfiguration(
            name="claude-haiku-5-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-haiku-5.5",
        ),
        # Superseded, kept so an older saved profile or plan that names one
        # still resolves instead of raising "Unknown model".
        "claude-4": ModelConfiguration(
            name="claude-sonnet-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-sonnet-5",
        ),
        "claude-4-thinking": ModelConfiguration(
            name="claude-sonnet-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-sonnet-5",
        ),
        "claude-opus-thinking": ModelConfiguration(
            name="claude-opus-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-opus-5",
        ),
        "claude-3-haiku": ModelConfiguration(
            name="claude-haiku-4-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-haiku-4.5",
        ),
        # Google Gemini Models (direct via langchain-google-genai).
        # "gemini-2.5-pro" used to be declared TWICE here -- once routed
        # through OpenRouter and once direct. Python keeps the last one, so
        # the OpenRouter entry was dead and the direct one silently won; the
        # duplicate is gone rather than left to confuse the next reader.
        "gemini-2.5-flash": ModelConfiguration(
            name="gemini-2.5-flash",
            base_url=GOOGLE_GENAI_BASE_URL,
            openrouter_name="google/gemini-2.5-flash",
        ),
        "gemini-2.5-flash-lite": ModelConfiguration(
            name="gemini-2.5-flash-lite",
            base_url=GOOGLE_GENAI_BASE_URL,
            openrouter_name="google/gemini-2.5-flash-lite",
        ),
        "gemini-2.5-pro": ModelConfiguration(
            name="gemini-2.5-pro",
            base_url=GOOGLE_GENAI_BASE_URL,
            openrouter_name="google/gemini-2.5-pro",
        ),
        "gemini-3.1-pro": ModelConfiguration(
            name="gemini-3.1-pro-preview",
            base_url=GOOGLE_GENAI_BASE_URL,
            openrouter_name="google/gemini-3.1-pro-preview",
        ),
        "gemini-3.1-flash-lite": ModelConfiguration(
            name="gemini-3.1-flash-lite",
            base_url=GOOGLE_GENAI_BASE_URL,
            openrouter_name="google/gemini-3.1-flash-lite",
        ),
        "gemini-3.5-flash": ModelConfiguration(
            name="gemini-3.5-flash",
            base_url=GOOGLE_GENAI_BASE_URL,
            openrouter_name="google/gemini-3.5-flash",
        ),
        # Candidatos de la auditoría de modelos del 2026-09-23, replicados contra
        # prompts reales. El tier standard usa gpt-6-sol y gpt-6-luna (ver
        # ai_settings.py); los demás quedan para el banco. gemini-3.5-flash-lite
        # codificó y se retiró del codificador el 2026-09-24 (6 x 50' en el reloj).
        "gemini-3.8-flash": ModelConfiguration(
            name="gemini-3.8-flash",
            base_url=GOOGLE_GENAI_BASE_URL,
            openrouter_name="google/gemini-3.8-flash",
        ),
        "gemini-3.5-flash-lite": ModelConfiguration(
            name="gemini-3.5-flash-lite",
            base_url=GOOGLE_GENAI_BASE_URL,
            openrouter_name="google/gemini-3.5-flash-lite",
        ),
        "gpt-5.6-terra": ModelConfiguration(
            name="gpt-5.6-terra",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-5.6-terra",
        ),
        "gpt-5.6-luna": ModelConfiguration(
            name="gpt-5.6-luna",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-5.6-luna",
        ),
        "gpt-6-sol": ModelConfiguration(
            name="gpt-6-sol",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-6-sol",
        ),
        "gpt-6-luna": ModelConfiguration(
            name="gpt-6-luna",
            base_url="https://api.openai.com/v1",
            openrouter_name="openai/gpt-6-luna",
        ),
        "claude-opus-5.5": ModelConfiguration(
            name="claude-opus-5-5",
            base_url="https://api.anthropic.com",
            openrouter_name="anthropic/claude-opus-5.5",
        ),
    }

    MODEL_CONFIGS: dict[str, dict[str, Any]] = {
        # NO `thinking` parameter, on purpose -- and that is what turns it ON.
        #
        # Opus 5 and Sonnet 5 run adaptive thinking BY DEFAULT when the
        # parameter is omitted. Sending it explicitly is not just redundant
        # here, it is harmful: langchain-anthropic then routes structured
        # output through a forced-tool-calling path it warns about itself
        # ("not guaranteed when `thinking` is enabled ... will raise an
        # OutputParserException if tool calls are not generated"). Four of the
        # five Claude roles in this app produce structured output -- the three
        # experts and the weekly planner -- and the planner makes four calls
        # per generation, so that risk lands squarely on the most expensive,
        # least recoverable part of the pipeline.
        #
        # Omitting it gives adaptive thinking AND the plain structured-output
        # path. Verified against the live API: a real Opus 5 call returns the
        # nested repeat blocks the schema asks for, with no warning.
        #
        # The old {"type": "enabled", "budget_tokens": N} shape is gone for a
        # second reason: it is REJECTED with a 400 on these models.
        "claude-opus": {
            "max_tokens": 32000,
            "log": "Using max_tokens=32000 for {role} (adaptive thinking is the model default)",
        },
        "claude-sonnet": {
            "max_tokens": 32000,
            "log": "Using max_tokens=32000 for {role} (adaptive thinking is the model default)",
        },
        # Temperatura 0: es el codificador (WORKOUT_ENCODER), que traduce y no
        # decide. A la temperatura por defecto (1.0) inventó 90 s para unos
        # progresivos de "15-20″" en la semana de la atleta 3 del 2026-09-14 y
        # copió el mismo número del miércoles al domingo.
        "claude-haiku": {
            "max_tokens": 16000,
            "temperature": 0,
            "log": "Using max_tokens=16000, temperature=0 for {role}",
        },
        # Sin `temperature`: la API la rechaza en Haiku 5.5 («`temperature` is deprecated for this
        # model», 2026-10-19), y también `top_p` y `top_k`, con el razonamiento encendido o apagado
        # (`thinking={"type": "disabled"}` sí lo acepta; se midió en el banco y se quitó de aquí:
        # ver contexto/04).
        "claude-haiku-55": {
            "max_tokens": 16000,
            "log": "Using max_tokens=16000 for {role} (Haiku 5.5: sin temperature)",
        },
        "claude-opus-thinking": {
            "max_tokens": 32000,
            "log": "Using max_tokens=32000 for {role} (adaptive thinking is the model default)",
        },
        "claude-4-thinking": {
            "max_tokens": 32000,
            "log": "Using max_tokens=32000 for {role} (adaptive thinking is the model default)",
        },
        "claude-4": {
            "max_tokens": 32000,
            "log": "Using max_tokens=32000 for {role} (adaptive thinking is the model default)",
        },
        "gpt-5": {
            "use_responses_api": True,
            "reasoning": {"effort": "xhigh"},
            "model_kwargs": {"text": {"verbosity": "high"}},
            "log": "Using GPT-5 with Responses API for {role} (verbosity: high, reasoning_effort: xhigh)",
        },
        # Same model as "gpt-5", thinking dialled down on purpose.
        #
        # `effort` is a price lever, not a quality dial you can read off a
        # spec sheet, because reasoning tokens bill at the OUTPUT rate. On the
        # v6 replay bench, one archived weekly decision (11,730 input tokens)
        # cost $0.0810 on gemini-3.5-flash and $0.1942 on gpt-5 at xhigh --
        # not because the prompt got bigger, but because gpt-5 spent 26,230
        # output tokens where gemini spent 7,047, and took 391 seconds doing
        # it. Six and a half minutes for one node, on a page the athlete is
        # watching a progress bar on.
        #
        # xhigh earns its keep where a wrong answer is expensive and rare
        # (season planning, once per 16 weeks). It does not obviously earn it
        # on a weekly decision that runs 52 times a year. Bench the two
        # against the same archived prompt before assigning either.
        "gpt-5-mid": {
            "use_responses_api": True,
            "reasoning": {"effort": "medium"},
            "model_kwargs": {"text": {"verbosity": "medium"}},
            "log": "Using GPT-5 with Responses API for {role} (verbosity: medium, reasoning_effort: medium)",
        },
        "gpt-5.2-pro": {
            "use_responses_api": True,
            "reasoning": {"effort": "xhigh"},
            "model_kwargs": {"text": {"verbosity": "high"}},
            "log": "Using GPT-5.2 Pro with Responses API for {role} (verbosity: high, reasoning_effort: xhigh)",
        },
        "gpt-5-search": {
            "use_responses_api": True,
            "reasoning": {"effort": "xhigh"},
            "model_kwargs": {
                "text": {"verbosity": "high"},
                "tools": [{"type": "web_search"}],
                "include": ["web_search_call.action.sources"],
            },
            "log": "Using GPT-5.2 with web search + Responses API for {role} (verbosity: high, reasoning_effort: xhigh)",
        },
        "gpt-5.2-pro-search": {
            "use_responses_api": True,
            "reasoning": {"effort": "xhigh"},
            "model_kwargs": {
                "text": {"verbosity": "high"},
                "tools": [{"type": "web_search"}],
                "include": ["web_search_call.action.sources"],
            },
            "log": "Using GPT-5.2 Pro with web search + Responses API for {role} (verbosity: high, reasoning_effort: xhigh)",
        },
        "gpt-5.6-terra": {
            "use_responses_api": True,
            "reasoning": {"effort": "medium"},
            "model_kwargs": {"text": {"verbosity": "medium"}},
            "log": "Using GPT-5.6 Terra with Responses API for {role} (verbosity: medium, reasoning_effort: medium)",
        },
        "gpt-5.6-luna": {
            "use_responses_api": True,
            "reasoning": {"effort": "low"},
            "model_kwargs": {"text": {"verbosity": "medium"}},
            "log": "Using GPT-5.6 Luna with Responses API for {role} (verbosity: medium, reasoning_effort: low)",
        },
        "gpt-6-sol": {
            "use_responses_api": True,
            "reasoning": {"effort": "medium"},
            "model_kwargs": {"text": {"verbosity": "medium"}},
            "log": "Using GPT-6 Sol with Responses API for {role} (verbosity: medium, reasoning_effort: medium)",
        },
        "gpt-6-luna": {
            "use_responses_api": True,
            "reasoning": {"effort": "low"},
            "model_kwargs": {"text": {"verbosity": "medium"}},
            "log": "Using GPT-6 Luna with Responses API for {role} (verbosity: medium, reasoning_effort: low)",
        },
        "claude-opus-5.5": {
            "max_tokens": 32000,
            "log": "Using max_tokens=32000 for {role} (adaptive thinking is the model default)",
        },
        "gemini-3.8-flash": {
            "max_output_tokens": 32768,
            "log": "Using max_output_tokens=32768 for {role} (avoids truncating large structured JSON output)",
        },
        "gemini-3.5-flash-lite": {
            "max_output_tokens": 32768,
            "log": "Using max_output_tokens=32768 for {role} (avoids truncating large structured JSON output)",
        },
        "gemini-3.5-flash": {
            # langchain-google-genai defaults to no explicit cap, which lets
            # the provider fall back to a conservative default — observed in
            # practice truncating a structured weekly-plan completion mid-
            # object (a real JSON parse failure, not just short output) once
            # the response also has to spend tokens on internal "thinking".
            # Generous explicit ceiling so structured plan generation (workout
            # role) has room for both reasoning and a large nested JSON body.
            "max_output_tokens": 32768,
            "log": "Using max_output_tokens=32768 for {role} (avoids truncating large structured JSON output)",
        },
    }

    # USD per 1 million tokens, as (input_cost, output_cost). Keyed by the
    # CONFIGURATIONS key above (not the raw provider model string, which
    # differs between direct-API and OpenRouter-fallback routing for the same
    # model -- see _find_config_key_for_model_name). Verified against public
    # provider pricing pages as of 2026-08; prices change, so treat this as
    # best-effort and update when a provider announces new rates.
    #
    # v6 Track 5: this table and CONFIGURATIONS must hold exactly the same
    # keys, and tests/test_model_tiers.py fails if they ever diverge. Six
    # models (gpt-4.5, gpt-5-mini, three DeepSeek variants, grok-4) were
    # configured with no price here and no tier reaching them: using one would
    # have reported the whole generation as costing $0 with nothing but a
    # WARNING in the log. They were removed rather than given invented prices.
    # To add a model back, add its price in the same commit.
    MODEL_PRICING_USD_PER_MILLION: dict[str, tuple[float, float]] = {
        "gemini-3.1-flash-lite": (0.25, 1.50),
        "gemini-3.5-flash": (1.50, 9.00),
        # Precio de lanzamiento hasta el 31-12-2026; desde el 01-01-2027, $1,50/$7,50
        # (ai.google.dev/gemini-api/docs/pricing, leída el 2026-09-23).
        "gemini-3.8-flash": (0.75, 3.75),
        "gemini-3.5-flash-lite": (0.30, 2.50),
        "gpt-5.6-terra": (2.00, 12.00),
        "gpt-5.6-luna": (0.20, 1.20),
        # Salieron el 2026-09-22 (developers.openai.com, leída el 2026-09-23).
        "gpt-6-sol": (2.00, 10.00),
        "gpt-6-luna": (0.10, 0.50),
        "claude-opus-5.5": (4.00, 20.00),
        "gemini-2.5-flash-lite": (0.10, 0.40),
        "gemini-2.5-flash": (0.30, 2.50),
        "gemini-2.5-pro": (1.25, 10.00),
        "gemini-3.1-pro": (2.00, 12.00),
        # $2/$10, no $3/$15. Anthropic anuncio el precio de lanzamiento de
        # Sonnet 5 como "introductorio hasta el 31-08-2026", con subida a
        # $3/$15 el 01-09-2026 -- y despues CANCELO esa subida: el precio de
        # lanzamiento se quedo como el estandar. La tabla de precios oficial lo
        # dice expresamente ("the previously scheduled increase [...] will not
        # occur"). Verificado el 02-09-2026 contra
        # platform.claude.com/docs/en/about-claude/pricing.
        #
        # Con 3/15 cada nodo de Sonnet se cobraba un 50% de mas. Sonnet corre
        # los TRES expertos en el plan Pro, o sea en todas las generaciones:
        # medido sobre el libro real, $1.29 de mas sobre $9.57, un 13.5%.
        "claude-sonnet": (2.00, 10.00),
        "claude-opus": (5.00, 25.00),
        "claude-haiku": (1.00, 5.00),
        # Hasta 100.000 tokens de prompt; por encima, 0,50/2,50 (el codificador va por ~20.000).
        # Tabla oficial de Anthropic, la pegó el responsable el 2026-10-19.
        "claude-haiku-55": (0.10, 0.50),
        # Aliases resolve to the current models above, so they price the same.
        "claude-4": (3.00, 15.00),
        "claude-4-thinking": (3.00, 15.00),
        "claude-opus-thinking": (5.00, 25.00),
        "claude-3-haiku": (1.00, 5.00),
        "gpt-4o": (2.50, 10.00),
        "gpt-4o-mini": (0.15, 0.60),
        "gpt-4.1": (2.00, 8.00),
        "o1": (15.00, 60.00),
        "o1-mini": (1.10, 4.40),
        "o3": (2.00, 8.00),
        "o3-mini": (1.10, 4.40),
        "o4-mini": (1.10, 4.40),
        # gpt-5.2 a $1,75/$14 y gpt-5.2-pro a $21/$168: la tabla oficial de OpenAI
        # (developers.openai.com/api/docs/pricing, leída el 2026-09-23). Estuvo a
        # $0,88/$7 y $10,50/$84 desde el primer commit -- exactamente la MITAD, el
        # precio de Batch/Flex, que esta app no usa (ninguna llamada pide
        # `service_tier`) -- así que cada llamada a GPT se anotaba a la mitad de lo
        # que se factura. Ver tests/test_precio_y_kilometros.py.
        "gpt-5": (1.75, 14.00),
        "gpt-5-mid": (1.75, 14.00),  # same model as gpt-5, cheaper only because it thinks less
        "gpt-5.2-pro": (21.00, 168.00),
        "gpt-5-search": (1.75, 14.00),
        "gpt-5.2-pro-search": (21.00, 168.00),
    }

    @classmethod
    def _find_config_key_for_model_name(cls, reported_model_name: str) -> str | None:
        """Reverse-lookup CONFIGURATIONS: a usage_metadata dict from an LLM
        response is keyed by whatever model string was actually sent to the
        provider, which is either ModelConfiguration.name (direct API) or
        .openrouter_name (OpenRouter fallback path) -- both must resolve back
        to the same pricing entry regardless of which route was taken."""
        for key, cfg in cls.CONFIGURATIONS.items():
            if reported_model_name in (cfg.name, cfg.openrouter_name):
                return key

        # Providers may answer with a DATED SNAPSHOT of the model that was
        # requested: asking OpenAI for "gpt-5.2" gets back
        # "gpt-5.2-2025-12-11" in usage metadata. Verified live in v6 Track 1
        # -- and until this fallback existed every OpenAI-routed call in the
        # app costed $0, which in the Pro tier meant synthesis and the season
        # planner were free on the report and not free on the bill.
        #
        # Matched longest-name-first so "gpt-5.2-2025-12-11" cannot resolve to
        # a shorter "gpt-5" entry, and only when what follows really is a date
        # suffix, so an unrelated model that merely shares a prefix (gpt-5.2
        # vs gpt-5.2-pro) never collapses into the wrong price.
        snapshot = re.compile(r"-(\d{4}-\d{2}-\d{2}|\d{8}|latest)$")
        candidates: list[tuple[int, str]] = []
        for key, cfg in cls.CONFIGURATIONS.items():
            for known in (cfg.name, cfg.openrouter_name):
                if not known or not reported_model_name.startswith(known):
                    continue
                if snapshot.fullmatch(reported_model_name[len(known):]):
                    candidates.append((len(known), key))
        if candidates:
            return max(candidates)[1]
        return None

    @classmethod
    def habla_anthropic_directo(cls, model_name: str) -> bool:
        """True solo si ese modelo va a salir por la API de Anthropic con su
        propia clave.

        Existe para decidir si se puede mandar `cache_control`, que es un
        parametro PROPIO de Anthropic. Dos formas de equivocarse que esto
        evita: mandarselo a Gemini o a OpenAI (que no saben que es), y
        mandarselo por el camino de OpenRouter, que descarta los parametros
        especificos del proveedor -- se aceptaria sin error y sin cachear
        nada, o sea pagando el 25% extra de escribir una cache que nunca se
        va a leer.
        """
        cfg = cls.CONFIGURATIONS.get(model_name)
        if not cfg or cls._detect_provider(cfg.base_url) != "anthropic":
            return False
        # Sin clave propia cae a OpenRouter (ver get_llm_by_role_and_name).
        return bool(get_config().anthropic_api_key)

    # Lo que Anthropic cobra por un token de entrada segun de donde salga.
    # Escribir la cache de 5 minutos cuesta un 25% mas que mandarlo normal; la
    # de una hora, el doble; leer cualquiera de las dos, la decima parte. Son
    # multiplicadores sobre la tarifa de entrada del modelo, no precios
    # propios, asi que siguen valiendo si el precio del modelo cambia.
    CACHE_WRITE_MULTIPLIER = 1.25
    CACHE_WRITE_1H_MULTIPLIER = 2.00
    CACHE_READ_MULTIPLIER = 0.10

    @classmethod
    def estimate_cost_usd(
        cls,
        reported_model_name: str,
        input_tokens: int,
        output_tokens: int,
        *,
        cache_read_tokens: int = 0,
        cache_creation_tokens: int = 0,
        cache_creation_1h_tokens: int = 0,
    ) -> float:
        """Lo que costo una llamada, con la cache contada aparte.

        Sin los dos ultimos argumentos esto se comporta como siempre, que es
        lo correcto para todo lo que no cachea.

        **Por que hacen falta**: `usage_metadata` de LangChain reporta en
        `input_tokens` el TOTAL de entrada, cache incluida. Cobrarlo entero a
        tarifa plena es lo que hacia esta funcion, y con la cache del chat
        puesta habria reportado ~$0.078 por una llamada que costo ~$0.023 --
        un contador de costos que se equivoca al triple es peor que no
        tenerlo, porque nadie lo revisa hasta que llega la factura.
        """
        config_key = cls._find_config_key_for_model_name(reported_model_name)
        rates = cls.MODEL_PRICING_USD_PER_MILLION.get(config_key) if config_key else None
        if rates is None:
            logger.warning(
                "No pricing data for model '%s' (config key: %s) -- this call's cost will report as $0",
                reported_model_name, config_key,
            )
            return 0.0
        input_rate, output_rate = rates

        cache_read = max(0, int(cache_read_tokens or 0))
        cache_write = max(0, int(cache_creation_tokens or 0))
        cache_write_1h = max(0, int(cache_creation_1h_tokens or 0))
        # Los tokens que se pagaron a precio normal son los que quedan cuando
        # se descuentan los cubos de cache. El `max(0, ...)` es una guarda, no
        # una suposicion: si algun proveedor decidiera reportar `input_tokens`
        # SIN incluir la cache, esto cobraria de menos en vez de dar un numero
        # negativo -- y de las dos formas de equivocarse, la que avisa es la
        # que deja el total por debajo.
        fresh = max(0, input_tokens - cache_read - cache_write - cache_write_1h)

        entrada = (
            fresh * input_rate
            + cache_write * input_rate * cls.CACHE_WRITE_MULTIPLIER
            + cache_write_1h * input_rate * cls.CACHE_WRITE_1H_MULTIPLIER
            + cache_read * input_rate * cls.CACHE_READ_MULTIPLIER
        )
        return (entrada + output_tokens * output_rate) / 1_000_000

    @classmethod
    def _apply_model_config(cls, model_name: str, role: AgentRole, llm_params: dict[str, Any]):
        if model_name not in cls.MODEL_CONFIGS:
            return

        config_data = cls.MODEL_CONFIGS[model_name].copy()
        log_msg = config_data.pop("log", None)
        llm_params.update(config_data)
        if log_msg:
            logger.info(str(log_msg).format(role=role.value))

    @classmethod
    def get_llm(cls, role: AgentRole):
        model_name = ai_settings.get_model_for_role(role)
        return cls.get_llm_by_role_and_name(role, model_name)

    @classmethod
    def get_llm_by_name(cls, model_name: str):
        return cls.get_llm_by_role_and_name(AgentRole.FORMATTER, model_name)

    @classmethod
    def get_llm_by_role_and_name(cls, role: AgentRole, model_name: str):
        selected_config = cls.CONFIGURATIONS.get(model_name)
        if not selected_config:
            raise RuntimeError(f"Unknown model '{model_name}' in configuration")
        config = get_config()

        base_url = selected_config.base_url
        final_model_name = selected_config.name
        provider = cls._detect_provider(base_url)

        key_map = {
            "anthropic": config.anthropic_api_key,
            "openai": config.openai_api_key,
            "openrouter": config.openrouter_api_key,
            "google": config.gemini_api_key if hasattr(config, 'gemini_api_key') else None,
        }

        api_key = key_map.get(provider)
        use_fallback = False

        if not api_key and provider == "google":
            # Try OpenRouter fallback for Google models
            if config.openrouter_api_key and selected_config.openrouter_name:
                api_key = config.openrouter_api_key
                base_url = OPENROUTER_BASE_URL
                final_model_name = selected_config.openrouter_name
                use_fallback = True
                logger.info("Routing Google model %s through OpenRouter", selected_config.name)
            else:
                raise RuntimeError(
                    f"Google model {selected_config.name} requires GEMINI_API_KEY or OPENROUTER_API_KEY"
                )
        elif not api_key and provider in ("anthropic", "openai"):
            if not config.openrouter_api_key:
                raise RuntimeError(
                    f"Falta {provider.upper()}_API_KEY en el archivo .env, que es lo que necesita "
                    f"el modelo '{selected_config.name}'. Añádela (o cambia a modo Lite, que solo "
                    f"usa Gemini). Se obtiene en "
                    + ("console.anthropic.com" if provider == "anthropic" else "platform.openai.com")
                    + " → API Keys."
                )
            if not selected_config.openrouter_name:
                raise RuntimeError(
                    f"{provider.title()} model {selected_config.name} is not available via OpenRouter; "
                    f"provide an {provider.upper()}_API_KEY"
                )
            api_key = config.openrouter_api_key
            base_url = OPENROUTER_BASE_URL
            final_model_name = selected_config.openrouter_name
            use_fallback = True
            # WARNING, not INFO: this is a silent downgrade, not a neutral
            # routing choice. OpenRouter adds its own margin on top of the
            # provider's price, and the request has to drop provider-specific
            # parameters below -- for Anthropic that means adaptive thinking is
            # stripped, so the model reasons less than the tier promises.
            logger.warning(
                "No %s_API_KEY set: routing %s through OpenRouter instead. This costs more than "
                "the direct API and disables provider-specific features (adaptive thinking for "
                "Anthropic, the Responses API for OpenAI). Add the direct key to .env to avoid it.",
                provider.upper(), selected_config.name,
            )
        elif not api_key:
            raise RuntimeError("OpenRouter API key is required for OpenRouter-hosted models")

        logger.info("Configuring LLM for role %s with model %s", role.value, final_model_name)

        llm_params: dict[str, Any] = {"model": final_model_name, "api_key": api_key}

        cls._apply_model_config(model_name, role, llm_params)
        # Ninguna llamada tenía tiempo límite. El 2026-09-14 la del experto de
        # fisiología de la atleta 3 no volvió en 19 minutos y la generación se
        # quedó colgada: sin excepción, `retry_with_backoff` no tenía nada que
        # reintentar. Con el límite, la llamada lanza un error de tiempo que los
        # tres proveedores ya tienen en la lista de reintentables. Los tres
        # clientes aceptan `timeout` (en Anthropic es el alias de
        # `default_request_timeout`). Un reintento interno del cliente y no los
        # de por defecto (2 en OpenAI, 6 en Google): los reintentos con espera
        # los hace `retry_with_backoff`, y encadenar los dos multiplicaba la
        # espera de una llamada colgada.
        llm_params.setdefault("timeout", TIEMPO_LIMITE_LLM_S)
        llm_params.setdefault("max_retries", 1)

        # Google Gemini via langchain-google-genai (direct API, 1M context)
        if provider == "google" and not use_fallback:
            if not _GOOGLE_GENAI_AVAILABLE:
                raise RuntimeError(
                    "langchain-google-genai is required for Google models. "
                    "Run: pip install langchain-google-genai"
                )
            # Inherit from llm_params (not rebuild from scratch) so any
            # MODEL_CONFIGS override (e.g. max_output_tokens) actually reaches
            # the Gemini client — this used to silently drop every override
            # for Google models since google_params was built fresh from just
            # model+key, ignoring whatever _apply_model_config had set above.
            google_params = dict(llm_params)
            google_params["google_api_key"] = google_params.pop("api_key", api_key)
            # Remove OpenAI/Anthropic-specific params that don't apply to Google
            for key in ("base_url", "use_responses_api", "reasoning", "model_kwargs", "extra_body", "thinking"):
                google_params.pop(key, None)
            return ChatGoogleGenerativeAI(**google_params)

        if base_url == OPENROUTER_BASE_URL:
            llm_params.pop("use_responses_api", None)
            llm_params.pop("reasoning", None)
            llm_params.pop("model_kwargs", None)
            llm_params.pop("extra_body", None)
            if provider == "anthropic" and llm_params.pop("thinking", None) is not None:
                # _apply_model_config already logged "Using adaptive thinking"
                # before we got here, so without this the logs claim a
                # capability the request no longer has.
                logger.warning(
                    "Adaptive thinking was dropped for %s: it is not supported over the "
                    "OpenRouter route.", selected_config.name,
                )

        if provider == "anthropic" and not use_fallback:
            return ChatAnthropic(**llm_params)

        llm_params["base_url"] = base_url
        return ChatOpenAI(**llm_params)
