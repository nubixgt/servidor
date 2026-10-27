"""Importación masiva desde la web: subir archivo → cola → worker → reporte de resultados."""
from __future__ import annotations

import csv
import io
import re
from datetime import datetime

from fastapi import APIRouter, Depends, File, Form, HTTPException, Request, UploadFile
from fastapi.responses import StreamingResponse

from ...config import settings
from ...db import lectura, transaccion
from ..deps import Usuario, auditar, requiere

router = APIRouter(prefix="/importaciones", tags=["Importaciones"])
EXT = {".csv", ".txt", ".xlsx", ".xlsm"}


@router.post("", status_code=201)
async def subir(request: Request, archivo: UploadFile = File(...), modo: str = Form("INCREMENTAL"),
                u: Usuario = Depends(requiere("importacion.ejecutar"))):
    if modo not in ("INCREMENTAL", "VALIDAR"):
        raise HTTPException(400, "Modo no válido")
    nombre = archivo.filename or "archivo"
    ext = ("." + nombre.rsplit(".", 1)[-1].lower()) if "." in nombre else ""
    if ext not in EXT:
        raise HTTPException(400, "Formato no admitido. Use .xlsx o .csv")
    seguro = re.sub(r"[^A-Za-z0-9._-]+", "_", nombre)[:120]
    settings.import_dir.mkdir(parents=True, exist_ok=True)
    destino = settings.import_dir / f"{datetime.now():%Y%m%d_%H%M%S}_{u.usuario}_{seguro}"
    maximo = settings.max_upload_mb * 1024 * 1024
    total = 0
    with open(destino, "wb") as f:
        while True:
            bloque = await archivo.read(1 << 20)
            if not bloque:
                break
            total += len(bloque)
            if total > maximo:
                f.close()
                destino.unlink(missing_ok=True)
                raise HTTPException(413, f"El archivo supera {settings.max_upload_mb} MB")
            f.write(bloque)
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("INSERT INTO stg.import_lote(archivo, ruta, modo, usuario_id) VALUES (%s,%s,%s,%s) RETURNING id",
                    (nombre, str(destino), modo, u.id))
        lote = cur.fetchone()["id"]
    auditar(u, "SUBIR_IMPORTACION", "importacion", "stg.import_lote", str(lote), {"archivo": nombre, "bytes": total, "modo": modo}, request)
    return {"id": lote, "estado": "PENDIENTE", "bytes": total}


@router.get("")
def listar(u: Usuario = Depends(requiere("importacion.ejecutar", "auditoria.ver"))):
    with lectura() as cur:
        cur.execute("""SELECT l.id, l.archivo, l.modo, l.estado, l.progreso, l.mensaje, l.total_filas, l.filas_insertadas,
                              l.filas_actualizadas, l.filas_rechazadas, l.advertencias, l.creado_en, l.iniciado_en, l.finalizado_en,
                              l.formato, l.codificacion, u.usuario
                       FROM stg.import_lote l LEFT JOIN seg.usuario u ON u.id = l.usuario_id ORDER BY l.id DESC LIMIT 50""")
        return cur.fetchall()


@router.get("/{lote}")
def detalle(lote: int, u: Usuario = Depends(requiere("importacion.ejecutar", "auditoria.ver"))):
    with lectura() as cur:
        cur.execute("SELECT l.*, u.usuario FROM stg.import_lote l LEFT JOIN seg.usuario u ON u.id = l.usuario_id WHERE l.id = %s", (lote,))
        r = cur.fetchone()
        if not r:
            raise HTTPException(404, "Lote no encontrado")
        r.pop("ruta", None)
        cur.execute("""SELECT nivel, regla, count(*) AS n, min(linea) AS primera_linea FROM stg.import_error
                       WHERE lote_id = %s GROUP BY nivel, regla ORDER BY nivel, n DESC""", (lote,))
        r["resumen_errores"] = cur.fetchall()
        return r


@router.get("/{lote}/errores.csv")
def errores_csv(lote: int, request: Request, u: Usuario = Depends(requiere("importacion.ejecutar"))):
    def gen():
        buf = io.StringIO()
        w = csv.writer(buf)
        w.writerow(["linea", "nivel", "regla", "campo", "valor", "mensaje"])
        yield "﻿" + buf.getvalue()
        with lectura() as cur:
            cur.execute("SELECT linea, nivel, regla, campo, valor, mensaje FROM stg.import_error WHERE lote_id = %s ORDER BY linea", (lote,))
            for r in cur:
                buf.seek(0); buf.truncate()
                w.writerow([r["linea"], r["nivel"], r["regla"], r["campo"], r["valor"], r["mensaje"]])
                yield buf.getvalue()
    auditar(u, "DESCARGAR_ERRORES", "importacion", "stg.import_lote", str(lote), None, request)
    return StreamingResponse(gen(), media_type="text/csv", headers={"Content-Disposition": f'attachment; filename="lote_{lote}_errores.csv"'})


@router.post("/{lote}/cancelar")
def cancelar(lote: int, u: Usuario = Depends(requiere("importacion.ejecutar"))):
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("UPDATE stg.import_lote SET estado='CANCELADO', finalizado_en=now() WHERE id=%s AND estado='PENDIENTE' RETURNING id", (lote,))
        if not cur.fetchone():
            raise HTTPException(409, "Solo se pueden cancelar lotes pendientes")
    return {"ok": True}
