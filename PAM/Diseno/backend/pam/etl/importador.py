"""Ejecución de un lote de importación: archivo → stg.matriz → core → mart.

Se usa desde el worker (importaciones subidas por la web) y desde la línea de comandos:
    python -m pam.cli importar "BD PAM.csv" [--validar]
"""
from __future__ import annotations

import hashlib
import logging
import time
from datetime import datetime, timezone
from pathlib import Path

import psycopg
from psycopg.rows import dict_row

from ..config import settings
from .lector import DESTINO, ErrorEstructura, Lectura

log = logging.getLogger("pam.etl")
LOTE_COPY = 50_000


def sha256(ruta: Path) -> str:
    h = hashlib.sha256()
    with open(ruta, "rb") as f:
        for b in iter(lambda: f.read(1 << 20), b""):
            h.update(b)
    return h.hexdigest()


def crear_lote(conn, ruta: Path, usuario_id: int | None, modo: str = "INCREMENTAL") -> int:
    r = conn.execute(
        "INSERT INTO stg.import_lote(archivo, ruta, modo, usuario_id) VALUES (%s,%s,%s,%s) RETURNING id",
        (ruta.name, str(ruta), modo, usuario_id)).fetchone()
    return r["id"] if isinstance(r, dict) else r[0]


def _estado(conn, lote: int, **campos):
    sets = ", ".join(f"{k} = %s" for k in campos)
    conn.execute(f"UPDATE stg.import_lote SET {sets} WHERE id = %s", (*campos.values(), lote))


def ejecutar_lote(lote: int, dsn: str | None = None, refrescar: bool = True) -> dict:
    """Procesa el lote indicado (ya registrado en stg.import_lote). Devuelve el reporte."""
    dsn = dsn or settings.database_url
    t0 = time.time()
    with psycopg.connect(dsn, autocommit=True, row_factory=dict_row) as conn:
        info = conn.execute("SELECT * FROM stg.import_lote WHERE id = %s", (lote,)).fetchone()
        if not info:
            raise ValueError(f"Lote {lote} no existe")
        ruta = Path(info["ruta"])
        solo_validar = info["modo"] == "VALIDAR"
        try:
            _estado(conn, lote, estado="LEYENDO", progreso=1, iniciado_en=datetime.now(timezone.utc),
                    sha256=sha256(ruta), mensaje="Leyendo archivo")
            lectura = Lectura(ruta)
            conn.execute("DELETE FROM stg.matriz WHERE lote_id = %s", (lote,))
            total = 0
            tamano = max(ruta.stat().st_size, 1)
            cols = ", ".join(["lote_id", "linea", *DESTINO])
            with conn.cursor() as cur:
                with cur.copy(f"COPY stg.matriz ({cols}) FROM STDIN") as cp:
                    for linea, valores in lectura:
                        cp.write_row((lote, linea, *valores))
                        total += 1
                        if total % LOTE_COPY == 0:
                            log.info("Lote %s: %s filas leídas", lote, total)
            _estado(conn, lote, formato=lectura.formato, codificacion=lectura.codificacion, total_filas=total, progreso=35,
                    mensaje=f"{total:,} filas leídas".replace(",", ".") +
                            (f"; columnas no reconocidas: {', '.join(map(str, lectura.extra))}" if lectura.extra else ""))
            if total == 0:
                raise ErrorEstructura("El archivo no tiene filas de datos")
            conn.execute("ANALYZE stg.matriz")
            _estado(conn, lote, estado="TRANSFORMANDO", progreso=45,
                    mensaje="Validando y actualizando expedientes (para 800 mil filas toma de 3 a 6 minutos)")
            with conn.transaction():
                reporte = conn.execute("SELECT stg.procesar_lote(%s, %s) AS r", (lote, solo_validar)).fetchone()["r"]
            if not solo_validar:
                if refrescar:
                    _estado(conn, lote, estado="REFRESCANDO", progreso=92, mensaje="Actualizando los agregados del tablero")
                    conn.execute("SELECT mart.refrescar(true)")
                conn.execute("DELETE FROM stg.matriz WHERE lote_id = %s", (lote,))
                for t in ("stg.matriz", "core.persona", "core.expediente", "core.visita", "core.observacion",
                          "core.resolucion", "core.movimiento_nomina", "core.estado_expediente"):
                    conn.execute(f"VACUUM (ANALYZE) {t}")
                _estado(conn, lote, estado="COMPLETADO", progreso=100, finalizado_en=datetime.now(timezone.utc),
                        mensaje=f"Importación terminada en {time.time() - t0:,.0f} s")
            else:
                conn.execute("DELETE FROM stg.matriz WHERE lote_id = %s", (lote,))
            conn.execute("INSERT INTO mart.meta(clave, valor) VALUES ('cache_version', %s) "
                         "ON CONFLICT (clave) DO UPDATE SET valor = EXCLUDED.valor", (str(time.time()),))
            log.info("Lote %s terminado en %.0f s", lote, time.time() - t0)
            return reporte
        except Exception as e:  # el lote queda en ERROR con el motivo visible en la web
            log.exception("Lote %s falló", lote)
            try:
                conn.execute("DELETE FROM stg.matriz WHERE lote_id = %s", (lote,))
            except Exception:
                pass
            _estado(conn, lote, estado="ERROR", mensaje=str(e)[:2000], finalizado_en=datetime.now(timezone.utc))
            raise
