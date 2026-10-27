from __future__ import annotations

from datetime import datetime, timedelta, timezone

from fastapi import APIRouter, Depends, HTTPException, Request, Response
from pydantic import BaseModel

from ...config import settings
from ...db import lectura, transaccion
from ...seguridad import (crear_access, hash_clave, hash_refresh, nuevo_refresh, validar_clave, verificar_clave)
from ..deps import Usuario, auditar, cargar_usuario, ip_cliente, limitar, usuario_actual

router = APIRouter(prefix="/auth", tags=["Autenticación"])
COOKIE = "pam_rt"


class Credenciales(BaseModel):
    usuario: str
    clave: str


class CambioClave(BaseModel):
    actual: str
    nueva: str


def _cookie(resp: Response, token: str, max_age: int) -> None:
    resp.set_cookie(COOKIE, token, max_age=max_age, httponly=True, secure=settings.cookie_secure,
                    samesite="strict", path="/api/v1/auth")


def _emitir(resp: Response, u: Usuario, request: Request) -> dict:
    rt, rh = nuevo_refresh()
    expira = datetime.now(timezone.utc) + timedelta(hours=settings.refresh_ttl_h)
    with transaccion() as cur:
        cur.execute("""INSERT INTO seg.sesion(usuario_id, refresh_hash, ip, user_agent, expira_en)
                       VALUES (%s,%s,%s,%s,%s) RETURNING id""",
                    (u.id, rh, ip_cliente(request), (request.headers.get("user-agent") or "")[:300], expira))
        sid = str(cur.fetchone()["id"])
    _cookie(resp, rt, settings.refresh_ttl_h * 3600)
    u.sesion_id = sid
    return {"access_token": crear_access(u.id, sid), "expira_en_s": settings.access_ttl_min * 60, "usuario": u.as_dict()}


@router.post("/login")
def login(datos: Credenciales, request: Request, response: Response):
    limitar("login:" + (ip_cliente(request) or "?"), 20)
    with lectura() as cur:
        cur.execute("SELECT id, hash, activo, intentos_fallidos, bloqueado_hasta FROM seg.usuario WHERE lower(usuario) = lower(%s)",
                    (datos.usuario.strip(),))
        r = cur.fetchone()
    ahora = datetime.now(timezone.utc)
    if r and r["bloqueado_hasta"] and r["bloqueado_hasta"] > ahora:
        minutos = int((r["bloqueado_hasta"] - ahora).total_seconds() // 60) + 1
        raise HTTPException(423, f"Usuario bloqueado temporalmente por intentos fallidos. Intente en {minutos} min.")
    if not r or not r["activo"] or not verificar_clave(r["hash"], datos.clave):
        if r:
            with transaccion() as cur:
                cur.execute("""UPDATE seg.usuario SET intentos_fallidos = intentos_fallidos + 1,
                                 bloqueado_hasta = CASE WHEN intentos_fallidos + 1 >= %s THEN now() + make_interval(mins => %s) END
                               WHERE id = %s""", (settings.max_intentos, settings.bloqueo_min, r["id"]))
        auditar(None, "LOGIN_FALLIDO", "auth", "seg.usuario", str(r["id"]) if r else None, {"usuario": datos.usuario[:60]}, request)
        raise HTTPException(401, "Usuario o contraseña incorrectos")
    with transaccion() as cur:
        cur.execute("UPDATE seg.usuario SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = now() WHERE id = %s", (r["id"],))
    u = cargar_usuario(r["id"])
    u.ip = ip_cliente(request)
    auditar(u, "LOGIN", "auth", "seg.usuario", str(u.id), None, request)
    return _emitir(response, u, request)


@router.post("/refresh")
def refresh(request: Request, response: Response):
    rt = request.cookies.get(COOKIE)
    if not rt:
        raise HTTPException(401, "Sin sesión")
    with transaccion() as cur:
        cur.execute("""UPDATE seg.sesion SET revocado_en = now() WHERE refresh_hash = %s AND revocado_en IS NULL AND expira_en > now()
                       RETURNING usuario_id""", (hash_refresh(rt),))
        r = cur.fetchone()
    if not r:
        response.delete_cookie(COOKIE, path="/api/v1/auth")
        raise HTTPException(401, "La sesión expiró; ingrese de nuevo")
    u = cargar_usuario(r["usuario_id"])
    if not u:
        raise HTTPException(401, "Usuario inactivo")
    return _emitir(response, u, request)


@router.post("/logout")
def logout(request: Request, response: Response):
    rt = request.cookies.get(COOKIE)
    if rt:
        with transaccion() as cur:
            cur.execute("UPDATE seg.sesion SET revocado_en = now() WHERE refresh_hash = %s AND revocado_en IS NULL", (hash_refresh(rt),))
    response.delete_cookie(COOKIE, path="/api/v1/auth")
    return {"ok": True}


@router.get("/yo")
def yo(u: Usuario = Depends(usuario_actual)):
    return u.as_dict()


@router.post("/cambiar-clave")
def cambiar_clave(datos: CambioClave, request: Request, u: Usuario = Depends(usuario_actual)):
    with lectura() as cur:
        cur.execute("SELECT hash FROM seg.usuario WHERE id = %s", (u.id,))
        h = cur.fetchone()["hash"]
    if not verificar_clave(h, datos.actual):
        raise HTTPException(400, "La contraseña actual no es correcta")
    problema = validar_clave(datos.nueva)
    if problema:
        raise HTTPException(400, problema)
    if datos.nueva == datos.actual:
        raise HTTPException(400, "La nueva contraseña debe ser distinta de la actual")
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("UPDATE seg.usuario SET hash = %s, debe_cambiar_clave = false, actualizado_en = now() WHERE id = %s",
                    (hash_clave(datos.nueva), u.id))
        cur.execute("UPDATE seg.sesion SET revocado_en = now() WHERE usuario_id = %s AND id <> %s::uuid AND revocado_en IS NULL",
                    (u.id, u.sesion_id))
    auditar(u, "CAMBIO_CLAVE", "auth", "seg.usuario", str(u.id), None, request)
    return {"ok": True}
