import logging
from abc import ABC, abstractmethod
from pathlib import Path

logger = logging.getLogger(__name__)

# services/ai/utils/plan_storage.py -> utils -> ai -> services -> project root.
# Resolving from __file__ (not a relative "data/storage") means every caller
# gets the same absolute directory regardless of the process's CWD at launch.
_PROJECT_ROOT = Path(__file__).resolve().parents[3]
_DEFAULT_BASE_DIR = _PROJECT_ROOT / "data" / "storage"


def sanitize_storage_name(name: str, default: str = "default") -> str:
    """Filesystem-safe directory/file name segment: alnum, hyphen, underscore
    only; everything else (spaces, punctuation) becomes '_'.

    Single source of truth for turning a profile name / user_id into a
    storage path segment. web_app/server.py's _profile_storage_dir() and
    FilePlanStorage's _get_user_dir() used to each do their own version of
    this — server.py sanitized, FilePlanStorage used the raw name verbatim —
    so "Jose Ines Castillo" resolved to two different directories depending
    on which code path touched it. season_phases.json (Fase 3) and the
    "existing season plan" continuity check both went through FilePlanStorage,
    so they silently read/wrote an empty sibling directory forever: the
    season plan appeared to "restart from scratch" on every regeneration,
    and computed mesocycle position never reached the weekly planner's
    prompt. Both call sites must route through this one function now.
    """
    safe = "".join(c if c.isalnum() or c in ("-", "_") else "_" for c in (name or "")).strip("_")
    return safe or default


def _as_safe_filename(value: str) -> str:
    return "".join(char for char in value if char.isalnum() or char in ("_", "-"))


class PlanStorage(ABC):

    @abstractmethod
    def load_plan(self, user_id: str, plan_type: str, extension: str = "md") -> str | None:
        pass

    @abstractmethod
    def save_plan(self, user_id: str, plan_type: str, content: str, extension: str = "md") -> None:
        pass

class FilePlanStorage(PlanStorage):

    def __init__(self, base_dir: str | Path | None = None):
        self.base_dir = Path(base_dir) if base_dir is not None else _DEFAULT_BASE_DIR
        self.base_dir.mkdir(parents=True, exist_ok=True)

    def _get_user_dir(self, user_id: str) -> Path:
        user_dir = self.base_dir / sanitize_storage_name(user_id)
        user_dir.mkdir(parents=True, exist_ok=True)
        return user_dir

    def _get_plan_path(self, user_id: str, plan_type: str, extension: str = "md") -> Path:
        return self._get_user_dir(user_id) / f"{_as_safe_filename(plan_type)}.{extension}"

    def load_plan(self, user_id: str, plan_type: str, extension: str = "md") -> str | None:
        try:
            plan_path = self._get_plan_path(user_id, plan_type, extension)
            if plan_path.exists():
                logger.info("Loading %s for user %s from %s", plan_type, user_id, plan_path)
                return plan_path.read_text(encoding="utf-8")
            return None
        except OSError:
            logger.exception("IO Error loading %s for user %s", plan_type, user_id)
            return None
        except Exception:
            logger.exception("Unexpected error loading %s for user %s", plan_type, user_id)
            return None

    def save_plan(self, user_id: str, plan_type: str, content: str, extension: str = "md") -> None:
        if not content:
            logger.warning("Attempted to save empty content for %s (user: %s)", plan_type, user_id)
            return

        try:
            plan_path = self._get_plan_path(user_id, plan_type, extension)
            plan_path.write_text(content, encoding="utf-8")
            logger.info("Saved %s for user %s to %s", plan_type, user_id, plan_path)
        except OSError:
            logger.exception("IO Error saving %s for user %s", plan_type, user_id)
        except Exception:
            logger.exception("Unexpected error saving %s for user %s", plan_type, user_id)
