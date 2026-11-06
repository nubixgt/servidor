
import logging
import os
from dataclasses import dataclass
from enum import Enum
from pathlib import Path

from dotenv import load_dotenv

PROJECT_ROOT = Path(__file__).resolve().parent.parent

# De donde salen las claves, en orden:
#
#   1. ENV_FILE, si alguien lo dice explicitamente. Es lo que usa el puente
#      (AiService/bridge/paginalocal.py) en produccion.
#   2. PaginaLocal/.env  -- la instalacion suelta de siempre.
#   3. ../.env           -- PaginaLocal vive dentro de un repo mas grande.
#
# El paso 3 existe desde que PaginaLocal paso a ser una carpeta dentro del
# proyecto: el .env quedo en la raiz, un nivel por encima, y arrancar la app en
# local dejo de encontrar ninguna clave. Fallaba tarde y mal -- no al arrancar,
# sino en la primera llamada a un modelo, a mitad de una generacion.
def resolver_env_file(raiz: Path, env_file: str | None = None) -> Path:
    """Devuelve el .env que toca cargar. Funcion aparte para poder probarla:
    esto se ejecuta al importar el modulo, y un fallo aqui no se ve hasta la
    primera llamada a un modelo."""
    if env_file:
        return Path(env_file)
    propio = raiz / ".env"
    return propio if propio.exists() else raiz.parent / ".env"


load_dotenv(resolver_env_file(PROJECT_ROOT, os.getenv("ENV_FILE")))

logger = logging.getLogger(__name__)

_config_cache: "Config | None" = None


class AIMode(Enum):
    STANDARD = "standard"
    COST_EFFECTIVE = "cost_effective"
    DEVELOPMENT = "development"
    PRO = "pro"


@dataclass
class Config:
    anthropic_api_key: str | None = None
    openai_api_key: str | None = None
    deepseek_api_key: str | None = None
    openrouter_api_key: str | None = None
    gemini_api_key: str | None = None

    ai_mode: AIMode = AIMode.STANDARD

    @classmethod
    def from_env(cls) -> "Config":
        anthropic_api_key = os.getenv("ANTHROPIC_API_KEY")
        openai_api_key = os.getenv("OPENAI_API_KEY")
        deepseek_api_key = os.getenv("DEEPSEEK_API_KEY")
        openrouter_api_key = os.getenv("OPENROUTER_API_KEY")

        ai_mode_str = os.getenv("AI_MODE", "standard").lower()
        try:
            ai_mode = AIMode(ai_mode_str)
        except ValueError:
            ai_mode = AIMode.STANDARD
            logger.info("Warning: Invalid AI_MODE '%s', using %s", ai_mode_str, ai_mode.value)

        if anthropic_api_key and not anthropic_api_key.startswith(("sk-ant-api03-", "sk-ant-")):
            raise ValueError("Invalid ANTHROPIC_API_KEY format")

        if openai_api_key and not openai_api_key.startswith("sk-"):
            raise ValueError("Invalid OPENAI_API_KEY format")

        gemini_api_key = os.getenv("GEMINI_API_KEY")

        # A key in the WRONG slot is worse than a missing one: the missing case
        # fails immediately with a clear message, while a wrong-provider key
        # sails through startup and then 401s deep inside a paid generation,
        # after the earlier nodes have already been billed.
        #
        # Found on a real .env: OPENROUTER_API_KEY held a copy of the Google
        # key (both "AIzaSy…"). Nothing complained, and Pro mode — which falls
        # back to OpenRouter for every Claude call when ANTHROPIC_API_KEY is
        # absent — would have failed mid-run on every single generation.
        if openrouter_api_key and not openrouter_api_key.startswith("sk-or-"):
            logger.warning(
                "OPENROUTER_API_KEY doesn't look like an OpenRouter key (they start with "
                "'sk-or-'); it starts with '%s…'. Ignoring it, so a missing provider key "
                "fails with a clear message instead of a confusing 401 mid-generation.",
                openrouter_api_key[:6],
            )
            openrouter_api_key = None

        # Google emite ya claves con otra forma, "AQ.": la del .env local lo es
        # y cada llamada a Gemini de las generaciones del 2026-09-14 respondio
        # 200. Avisar por ella era ruido en los 29 logs de ese dia.
        if gemini_api_key and not gemini_api_key.startswith(("AIza", "AQ.")):
            logger.warning(
                "GEMINI_API_KEY doesn't look like a Google API key (they start with 'AIza' or 'AQ.'); "
                "it starts with '%s…'.", gemini_api_key[:6],
            )

        return cls(
            anthropic_api_key=anthropic_api_key,
            ai_mode=ai_mode,
            openai_api_key=openai_api_key,
            deepseek_api_key=deepseek_api_key,
            openrouter_api_key=openrouter_api_key,
            gemini_api_key=gemini_api_key,
        )


def get_config() -> Config:
    global _config_cache  # noqa: PLW0603
    if _config_cache is None:
        _config_cache = Config.from_env()
    return _config_cache


def reload_config() -> Config:
    global _config_cache  # noqa: PLW0603
    _config_cache = None
    return get_config()
