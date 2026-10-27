"""Configuración por variables de entorno (ver .env.example)."""
import os
from pathlib import Path


def _env(name: str, default: str | None = None) -> str | None:
    v = os.getenv(name)
    return v if v not in (None, "") else default


class Settings:
    database_url: str = _env("DATABASE_URL", "postgresql://pam:pam@localhost:5432/pam")
    redis_url: str | None = _env("REDIS_URL")
    jwt_secret: str = _env("JWT_SECRET", "cambie-este-secreto")
    access_ttl_min: int = int(_env("ACCESS_TTL_MIN", "30"))
    refresh_ttl_h: int = int(_env("REFRESH_TTL_H", "12"))
    max_intentos: int = int(_env("LOGIN_MAX_INTENTOS", "5"))
    bloqueo_min: int = int(_env("LOGIN_BLOQUEO_MIN", "15"))
    import_dir: Path = Path(_env("IMPORT_DIR", "/data/importaciones"))
    export_dir: Path = Path(_env("EXPORT_DIR", "/data/exportaciones"))
    migrations_dir: Path = Path(_env("MIGRATIONS_DIR", str(Path(__file__).resolve().parents[2] / "db" / "migrations")))
    static_dir: str | None = _env("STATIC_DIR")  # solo desarrollo: servir el frontend desde la API
    cors_origins: list[str] = [o for o in (_env("CORS_ORIGINS", "") or "").split(",") if o]
    max_upload_mb: int = int(_env("MAX_UPLOAD_MB", "600"))
    busqueda_rpm: int = int(_env("BUSQUEDA_RPM", "90"))
    cookie_secure: bool = _env("COOKIE_SECURE", "true").lower() == "true"
    entorno: str = _env("ENTORNO", "produccion")


settings = Settings()
