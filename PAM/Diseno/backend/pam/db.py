"""Conexión a PostgreSQL (pool) y utilidades de migración."""
from __future__ import annotations

import contextlib
import logging
from pathlib import Path

import psycopg
from psycopg.rows import dict_row
from psycopg_pool import ConnectionPool

from .config import settings

log = logging.getLogger("pam.db")
_pool: ConnectionPool | None = None


def pool() -> ConnectionPool:
    global _pool
    if _pool is None:
        _pool = ConnectionPool(settings.database_url, min_size=2, max_size=20, kwargs={"row_factory": dict_row}, open=True)
    return _pool


def close_pool() -> None:
    global _pool
    if _pool is not None:
        _pool.close()
        _pool = None


@contextlib.contextmanager
def transaccion(usuario: dict | None = None, ip: str | None = None, request_id: str | None = None):
    """Transacción con el contexto de auditoría (usuario, IP) fijado para los triggers."""
    with pool().connection() as conn:
        with conn.transaction():
            with conn.cursor() as cur:
                if usuario:
                    cur.execute("SELECT set_config('pam.usuario_id', %s, true), set_config('pam.usuario', %s, true)",
                                (str(usuario["id"]), usuario["usuario"]))
                if ip:
                    cur.execute("SELECT set_config('pam.ip', %s, true)", (ip,))
                if request_id:
                    cur.execute("SELECT set_config('pam.request_id', %s, true)", (request_id,))
                yield cur


@contextlib.contextmanager
def lectura():
    with pool().connection() as conn:
        with conn.cursor() as cur:
            yield cur


def migrar(dsn: str | None = None, directorio: Path | None = None) -> list[str]:
    """Aplica en orden las migraciones SQL pendientes. Devuelve las aplicadas."""
    directorio = directorio or settings.migrations_dir
    aplicadas: list[str] = []
    with psycopg.connect(dsn or settings.database_url, autocommit=True) as conn:
        conn.execute("CREATE TABLE IF NOT EXISTS public.schema_migrations(version text PRIMARY KEY, aplicada_en timestamptz NOT NULL DEFAULT now())")
        hechas = {r[0] for r in conn.execute("SELECT version FROM public.schema_migrations")}
        for f in sorted(Path(directorio).glob("*.sql")):
            if f.stem in hechas:
                continue
            log.info("Aplicando migración %s", f.name)
            with conn.transaction():
                conn.execute(f.read_text(encoding="utf-8"))
                conn.execute("INSERT INTO public.schema_migrations(version) VALUES (%s)", (f.stem,))
            aplicadas.append(f.stem)
    return aplicadas
