"""Loads PaginaLocal as a library, without modifying a single file of it.

- Puts PaginaLocal/ on sys.path so `import core.config`, `import services.*`,
  `import web_app.server` resolve.
- Points its .env loader at the repo-root .env (via ENV_FILE) so the API keys
  live in ONE place.
- Redirects ALL of PaginaLocal's file storage to AiService/_runtime/, keyed by
  the per-request STORAGE_KEY contextvar, so nothing is written inside
  PaginaLocal/ and two users never share a directory.
"""
from __future__ import annotations

import contextvars
import os
import sys
from pathlib import Path

AISERVICE_DIR = Path(__file__).resolve().parent.parent
REPO_ROOT = AISERVICE_DIR.parent
PAGINALOCAL_DIR = REPO_ROOT / "PaginaLocal"
RUNTIME_DIR = AISERVICE_DIR / "_runtime"
RUNTIME_STORAGE = RUNTIME_DIR / "storage"

# Per-request storage directory name (set by main.py, e.g. "u42").
STORAGE_KEY: contextvars.ContextVar[str] = contextvars.ContextVar("storage_key", default="default")

# --- 1. env: one .env for the whole project (repo root) --------------------
os.environ["ENV_FILE"] = os.getenv("ENV_FILE") or str(REPO_ROOT / ".env")
os.environ.setdefault("COOKIE_SECURE", "false")

# --- 2. make PaginaLocal importable --------------------------------------
if str(PAGINALOCAL_DIR) not in sys.path:
    sys.path.insert(0, str(PAGINALOCAL_DIR))

RUNTIME_STORAGE.mkdir(parents=True, exist_ok=True)


def current_storage_dir(_profile_name: str | None = None) -> Path:
    """The storage dir for the request in flight. Ignores the profile name on
    purpose: isolation is by authenticated user (STORAGE_KEY), not by the
    athlete's display name, which is not unique."""
    d = RUNTIME_STORAGE / STORAGE_KEY.get()
    d.mkdir(parents=True, exist_ok=True)
    return d


# --- 3. import PaginaLocal (defines the orchestration; starts no server) ---
import services.ai.utils.plan_storage as _plan_storage  # noqa: E402
from core import usage_ledger as _usage_ledger  # noqa: E402
from core.config import get_config, reload_config  # noqa: E402
from services.ai.coach_chat import ask_coach  # noqa: E402
from services.garmin.data_extractor import TriathlonCoachDataExtractor  # noqa: E402
from web_app import server as pl_server  # noqa: E402

# --- 4. redirect every storage path to current_storage_dir() --------------
pl_server.DATA_DIR = RUNTIME_DIR
pl_server.SECURE_DATA_DIR = RUNTIME_DIR / "secure"
pl_server.STORAGE_DIR = RUNTIME_STORAGE
pl_server.SECURE_DATA_DIR.mkdir(parents=True, exist_ok=True)

# server.py's task functions call `_profile_storage_dir(name)` as a bare global.
pl_server._profile_storage_dir = current_storage_dir

# The Garmin-upload task reads/writes profiles.json (for encrypted creds) —
# keep it out of PaginaLocal/ too.
pl_server.PROFILES_FILE = pl_server.SECURE_DATA_DIR / "profiles.json"

# The season planner / weekly planner nodes reach for FilePlanStorage()._get_user_dir
# independently of server.py. Patch the method on the class object so it applies
# no matter how the nodes imported the class.
_plan_storage._DEFAULT_BASE_DIR = RUNTIME_STORAGE
_plan_storage.FilePlanStorage._get_user_dir = lambda self, user_id: current_storage_dir()

# --- 5. quien esta gastando ------------------------------------------------
#
# `record_run` se llama desde dentro de las tareas de generacion, que reciben un
# perfil y no una sesion. Con una sola instalacion el perfil ERA la identidad;
# con cuentas de verdad no lo es -- dos atletas pueden llamarse igual, y cobrar
# por nombre es cobrarle a quien no toca. El libro tenia la columna `user_id`
# vacia desde v7, esperando exactamente esto.
_usage_ledger.USER_ID_RESOLVER = lambda: STORAGE_KEY.get()

# El libro de redes (core/redes_ledger.py) NO se enciende aquí sino en worker.py: las
# pruebas del AiService importan este módulo y no deben anotar correcciones.

__all__ = [
    "pl_server",
    # Estaba "usage_ledger", que NO existe: el modulo se importa arriba como
    # `_usage_ledger`. Un `from ... import *` habria fallado con AttributeError.
    # Lo caza el linter nuevo (F822) y nunca lo habria cazado una prueba,
    # porque nadie hace import * de este modulo.
    "_usage_ledger",
    "ask_coach",
    "TriathlonCoachDataExtractor",
    "get_config",
    "reload_config",
    "current_storage_dir",
    "STORAGE_KEY",
    "RUNTIME_STORAGE",
    "PAGINALOCAL_DIR",
    "REPO_ROOT",
]
