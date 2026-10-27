"""Proceso en segundo plano:
- toma las importaciones PENDIENTES (subidas desde la web) y las ejecuta una por una;
- refresca los agregados del tablero cuando hubo cambios (cada N minutos) y cada noche.
"""
from __future__ import annotations

import logging
import time
from datetime import datetime

import psycopg
from psycopg.rows import dict_row

from .config import settings
from .etl.importador import ejecutar_lote

log = logging.getLogger("pam.worker")


def tomar_lote(conn) -> int | None:
    with conn.transaction():
        r = conn.execute("""SELECT id FROM stg.import_lote WHERE estado = 'PENDIENTE'
                            ORDER BY id FOR UPDATE SKIP LOCKED LIMIT 1""").fetchone()
        if not r:
            return None
        conn.execute("UPDATE stg.import_lote SET estado = 'LEYENDO' WHERE id = %s", (r["id"],))
        return r["id"]


def ciclo(intervalo_s: int = 5) -> None:
    log.info("Worker iniciado")
    ultimo_refresco = time.time()
    ultimo_nocturno = None
    while True:
        try:
            with psycopg.connect(settings.database_url, autocommit=True, row_factory=dict_row) as conn:
                # lotes que quedaron a medias por un reinicio
                conn.execute("""UPDATE stg.import_lote SET estado='ERROR', mensaje='Proceso interrumpido (reinicio del servicio). Vuelva a cargar el archivo.'
                                WHERE estado IN ('LEYENDO','VALIDANDO','TRANSFORMANDO','REFRESCANDO') AND iniciado_en < now() - interval '6 hours'""")
                while True:
                    lote = tomar_lote(conn)
                    if lote is None:
                        break
                    try:
                        ejecutar_lote(lote)
                    except Exception:
                        pass  # el error queda registrado en el lote
                    ultimo_refresco = time.time()
                minutos = int((conn.execute("SELECT valor FROM ref.parametro WHERE clave='refresco_tablero_min'").fetchone() or {"valor": 15})["valor"])
                pendiente = conn.execute("SELECT valor FROM mart.meta WHERE clave='pendiente_refresco'").fetchone()
                hoy = datetime.now().date()
                nocturno = datetime.now().hour == 2 and ultimo_nocturno != hoy
                if (pendiente and pendiente["valor"] == "1" and time.time() - ultimo_refresco > minutos * 60) or nocturno:
                    log.info("Refrescando agregados del tablero")
                    conn.execute("SELECT mart.refrescar(true)")
                    conn.execute("INSERT INTO mart.meta VALUES ('pendiente_refresco','0') ON CONFLICT (clave) DO UPDATE SET valor='0'")
                    conn.execute("INSERT INTO mart.meta VALUES ('cache_version', %s) ON CONFLICT (clave) DO UPDATE SET valor=EXCLUDED.valor", (str(time.time()),))
                    ultimo_refresco = time.time()
                    if nocturno:
                        ultimo_nocturno = hoy
        except Exception:
            log.exception("Error en el ciclo del worker; reintentando")
        time.sleep(intervalo_s)
