"""Aplicación FastAPI · /api/v1"""
from __future__ import annotations

import logging
import uuid
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.middleware.gzip import GZipMiddleware
from fastapi.responses import JSONResponse

from ..config import settings
from ..db import close_pool, lectura, pool
from .routers import admin, auth, catalogos, expedientes, importaciones, reportes, tablero

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")


@asynccontextmanager
async def ciclo_vida(app):
    pool()
    yield
    close_pool()


app = FastAPI(
    title="PAM · Sistema Integral de Gestión y Monitoreo del Programa del Adulto Mayor",
    version="1.0.0",
    docs_url="/api/docs" if settings.entorno != "produccion" else None,
    redoc_url=None,
    openapi_url="/api/openapi.json" if settings.entorno != "produccion" else None,
    lifespan=ciclo_vida,
)
app.add_middleware(GZipMiddleware, minimum_size=2048)
if settings.cors_origins:
    app.add_middleware(CORSMiddleware, allow_origins=settings.cors_origins, allow_credentials=True,
                       allow_methods=["*"], allow_headers=["*"])


@app.middleware("http")
async def cabeceras(request: Request, call_next):
    rid = request.headers.get("x-request-id") or uuid.uuid4().hex[:16]
    resp = await call_next(request)
    resp.headers["X-Request-ID"] = rid
    resp.headers.setdefault("X-Content-Type-Options", "nosniff")
    resp.headers.setdefault("X-Frame-Options", "DENY")
    resp.headers.setdefault("Referrer-Policy", "same-origin")
    if request.url.path.startswith("/api/"):
        resp.headers.setdefault("Cache-Control", "no-store")
    return resp


@app.exception_handler(ValueError)
async def valor_invalido(request: Request, exc: ValueError):
    return JSONResponse(status_code=400, content={"detail": str(exc)})


for r in (auth, tablero, expedientes, reportes, importaciones, admin, catalogos):
    app.include_router(r.router, prefix="/api/v1")


@app.get("/api/salud", tags=["Sistema"])
def salud():
    with lectura() as cur:
        cur.execute("""SELECT (SELECT reltuples::bigint FROM pg_class WHERE oid = 'core.expediente'::regclass) AS expedientes_aprox,
                              (SELECT valor FROM mart.meta WHERE clave='refrescado_en') AS tablero""")
        r = cur.fetchone()
    return {"estado": "ok", **r}


if settings.static_dir:  # desarrollo: servir el frontend sin Nginx
    from fastapi.staticfiles import StaticFiles
    app.mount("/", StaticFiles(directory=settings.static_dir, html=True), name="web")
