import asyncio
import logging
import random
from collections.abc import Awaitable, Callable
from functools import wraps
from typing import Any

import anthropic
import openai


#from anthropic._exceptions import DeadlineExceededError, OverloadedError, ServiceUnavailableError
class DeadlineExceededError(Exception): pass
class OverloadedError(Exception): pass
class ServiceUnavailableError(Exception): pass

try:
    # Gemini calls (langchain-google-genai) raise these, not anthropic.* /
    # openai.* exceptions — without them, a transient Gemini error (rate
    # limit, 503, timeout) was treated as non-retryable and failed the whole
    # node immediately instead of retrying like every other provider does.
    from google.api_core.exceptions import (
        Aborted as GoogleAborted,
    )
    from google.api_core.exceptions import (
        DeadlineExceeded as GoogleDeadlineExceeded,
    )
    from google.api_core.exceptions import (
        InternalServerError as GoogleInternalServerError,
    )
    from google.api_core.exceptions import (
        ResourceExhausted as GoogleResourceExhausted,
    )
    from google.api_core.exceptions import (
        ServiceUnavailable as GoogleServiceUnavailable,
    )
    _GOOGLE_RETRYABLE_EXCEPTIONS: set[type[Exception]] = {
        GoogleResourceExhausted,
        GoogleServiceUnavailable,
        GoogleDeadlineExceeded,
        GoogleInternalServerError,
        GoogleAborted,
    }
except ImportError:
    _GOOGLE_RETRYABLE_EXCEPTIONS = set()

from langgraph.errors import GraphInterrupt
from pydantic import ValidationError as PydanticValidationError

logger = logging.getLogger(__name__)


class RetryableError(Exception):
    pass


class APIOverloadError(RetryableError):
    pass


class RetryConfig:

    def __init__(
        self,
        max_retries: int = 3,
        base_delay: float = 1.0,
        max_delay: float = 60.0,
        exponential_base: float = 2.0,
        jitter: bool = True,
        retryable_exceptions: set[type[Exception]] | None = None,
    ):
        self.max_retries = max_retries
        self.base_delay = base_delay
        self.max_delay = max_delay
        self.exponential_base = exponential_base
        self.jitter = jitter
        self.retryable_exceptions = retryable_exceptions or {
            anthropic.RateLimitError,  # 429 - rate limits
            anthropic.ConflictError,  # 409 - conflicts
            anthropic.InternalServerError,  # 5xx - server errors
            ServiceUnavailableError,  # 503 - service unavailable
            DeadlineExceededError,  # 504 - gateway timeout
            OverloadedError,  # 529 - overloaded
            anthropic.APIConnectionError,  # Network/connection issues
            anthropic.APITimeoutError,  # Timeouts
            # OpenAI equivalents (Pro tier's gpt-5/gpt-5.2-pro-search roles)
            openai.RateLimitError,
            openai.ConflictError,
            openai.InternalServerError,
            openai.APIConnectionError,
            openai.APITimeoutError,
            # Network-level issues regardless of provider
            TimeoutError,
            ConnectionError,
            APIOverloadError,  # Custom local exception
            # Un campo que el modelo se dejo al rellenar su esquema.
            #
            # Estaba fuera y costo dos generaciones enteras: el experto de
            # actividad omitio `evidence` en UNO de sus tres destinatarios, el
            # error se clasifico como no-reintentable, el nodo murio, y la
            # semana salio vacia despues de cobrar $0.22. Volver a pedirlo es
            # exactamente lo que arregla esto: no es que el modelo NO PUEDA
            # rellenar el campo, es que esa vez no lo hizo.
            #
            # No enmascara un esquema mal disenado: si el modelo no puede
            # cumplirlo, falla las tres veces y el error llega igual, solo que
            # con tres intentos de evidencia en vez de uno.
            PydanticValidationError,
            *_GOOGLE_RETRYABLE_EXCEPTIONS,  # Gemini (Lite tier + fallback roles)
        }

    def calculate_delay(self, attempt: int) -> float:
        delay = min(self.base_delay * (self.exponential_base**attempt), self.max_delay)
        if self.jitter:
            jitter_range = delay * 0.1
            delay += random.uniform(-jitter_range, jitter_range)
        return max(delay, 0.1)


async def retry_with_backoff(
    func: Callable[[], Awaitable[Any]],
    config: RetryConfig | None = None,
    context: str = "operation",
) -> Any:

    if config is None:
        config = RetryConfig()

    last_exception = None

    for attempt in range(config.max_retries + 1):
        try:
            logger.debug(
                "Attempting %s (attempt %s/%s)",
                context,
                attempt + 1,
                config.max_retries + 1,
            )
            return await func()

        except GraphInterrupt:
            raise

        except Exception as e:
            last_exception = e

            is_retryable = any(isinstance(e, exc_type) for exc_type in config.retryable_exceptions)

            if is_retryable:
                logger.warning("%s failed with retryable %s: %s", context, type(e).__name__, e)
            else:
                logger.error("%s failed with non-retryable error: %s", context, e)
                break

            if attempt < config.max_retries:
                delay = config.calculate_delay(attempt)
                logger.info(
                    "%s failed (attempt %s), retrying in %.1fs: %s",
                    context,
                    attempt + 1,
                    delay,
                    e,
                )
                await asyncio.sleep(delay)
            else:
                logger.error("%s failed after %s attempts: %s", context, config.max_retries + 1, e)

    if last_exception is None:
        raise RuntimeError(f"{context} failed without capturing an exception")
    raise last_exception


def with_retry(config: RetryConfig | None = None, context: str | None = None):
    def decorator(func: Callable) -> Callable:
        @wraps(func)
        async def wrapper(*args, **kwargs):
            func_context = context or func.__name__

            async def call_func():
                return await func(*args, **kwargs)

            return await retry_with_backoff(call_func, config, func_context)

        return wrapper

    return decorator


DEFAULT_CONFIG = RetryConfig(max_retries=3, base_delay=1.0, max_delay=60.0)

AI_ANALYSIS_CONFIG = RetryConfig(
    max_retries=5,
    base_delay=2.0,
    max_delay=120.0,
    exponential_base=2.5,
)

QUICK_RETRY_CONFIG = RetryConfig(max_retries=2, base_delay=0.5, max_delay=10.0)
