"""Administración: usuarios, roles/permisos configurables, parámetros, catálogo de resoluciones y bitácora."""
from __future__ import annotations

import secrets
from datetime import date

from typing import Any

from fastapi import APIRouter, Body, Depends, HTTPException, Query, Request
from psycopg.types.json import Jsonb
from pydantic import BaseModel, Field

from ...db import lectura, transaccion
from ...seguridad import hash_clave, validar_clave
from ..deps import Usuario, auditar, requiere

router = APIRouter(prefix="/admin", tags=["Administración"])


class NuevoUsuario(BaseModel):
    usuario: str = Field(min_length=3, max_length=40, pattern=r"^[A-Za-z0-9._-]+$")
    nombre: str = Field(min_length=3, max_length=120)
    correo: str | None = None
    rol_id: int
    departamento_id: int | None = None
    municipio_id: int | None = None


class CambioUsuario(BaseModel):
    nombre: str | None = None
    correo: str | None = None
    rol_id: int | None = None
    departamento_id: int | None = None
    municipio_id: int | None = None
    activo: bool | None = None
    ambito_nacional: bool | None = None


def _clave_temporal() -> str:
    while True:
        c = secrets.token_urlsafe(9) + "Aa7"
        if not validar_clave(c):
            return c


@router.get("/usuarios")
def usuarios(u: Usuario = Depends(requiere("usuarios.gestionar"))):
    with lectura() as cur:
        cur.execute("""SELECT x.id, x.usuario, x.nombre, x.correo, x.activo, x.rol_id, r.nombre AS rol, x.departamento_id, x.municipio_id,
                              d.nombre AS departamento, m.nombre AS municipio, x.ultimo_acceso, x.bloqueado_hasta, x.debe_cambiar_clave
                       FROM seg.usuario x JOIN seg.rol r ON r.id = x.rol_id
                       LEFT JOIN ref.departamento d ON d.id = x.departamento_id LEFT JOIN ref.municipio m ON m.id = x.municipio_id
                       ORDER BY x.usuario""")
        return cur.fetchall()


@router.post("/usuarios", status_code=201)
def crear_usuario(datos: NuevoUsuario, u: Usuario = Depends(requiere("usuarios.gestionar"))):
    clave = _clave_temporal()
    dep = datos.departamento_id or (datos.municipio_id // 100 if datos.municipio_id else None)
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("SELECT 1 FROM seg.usuario WHERE lower(usuario) = lower(%s)", (datos.usuario,))
        if cur.fetchone():
            raise HTTPException(409, "Ese nombre de usuario ya existe")
        cur.execute("""INSERT INTO seg.usuario(usuario, nombre, correo, hash, rol_id, departamento_id, municipio_id)
                       VALUES (%s,%s,%s,%s,%s,%s,%s) RETURNING id""",
                    (datos.usuario, datos.nombre, datos.correo, hash_clave(clave), datos.rol_id, dep, datos.municipio_id))
        nid = cur.fetchone()["id"]
    return {"id": nid, "clave_temporal": clave}


@router.patch("/usuarios/{uid}")
def editar_usuario(uid: int, datos: CambioUsuario, u: Usuario = Depends(requiere("usuarios.gestionar"))):
    if uid == u.id and (datos.activo is False or datos.rol_id is not None):
        raise HTTPException(400, "No puede desactivarse ni cambiar su propio rol")
    campos = datos.model_dump(exclude_unset=True)
    if campos.pop("ambito_nacional", None):
        campos["departamento_id"] = None
        campos["municipio_id"] = None
    if campos.get("municipio_id"):
        campos["departamento_id"] = campos["municipio_id"] // 100
    if not campos:
        return {"ok": True}
    sets = ", ".join(f"{k} = %s" for k in campos)
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute(f"UPDATE seg.usuario SET {sets}, actualizado_en = now() WHERE id = %s", (*campos.values(), uid))
        if campos.get("activo") is False:
            cur.execute("UPDATE seg.sesion SET revocado_en = now() WHERE usuario_id = %s AND revocado_en IS NULL", (uid,))
    return {"ok": True}


@router.post("/usuarios/{uid}/restablecer-clave")
def restablecer(uid: int, request: Request, u: Usuario = Depends(requiere("usuarios.gestionar"))):
    clave = _clave_temporal()
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("""UPDATE seg.usuario SET hash = %s, debe_cambiar_clave = true, intentos_fallidos = 0, bloqueado_hasta = NULL
                       WHERE id = %s RETURNING usuario""", (hash_clave(clave), uid))
        if not cur.fetchone():
            raise HTTPException(404, "Usuario no encontrado")
        cur.execute("UPDATE seg.sesion SET revocado_en = now() WHERE usuario_id = %s AND revocado_en IS NULL", (uid,))
    auditar(u, "RESTABLECER_CLAVE", "admin", "seg.usuario", str(uid), None, request)
    return {"clave_temporal": clave}


@router.get("/roles")
def roles(u: Usuario = Depends(requiere("roles.gestionar", "usuarios.gestionar", "auditoria.ver"))):
    with lectura() as cur:
        cur.execute("""SELECT r.id, r.codigo, r.nombre, r.descripcion, r.sistema,
                              coalesce(array_agg(rp.permiso_codigo ORDER BY rp.permiso_codigo) FILTER (WHERE rp.permiso_codigo IS NOT NULL), '{}') AS permisos,
                              (SELECT count(*) FROM seg.usuario x WHERE x.rol_id = r.id) AS usuarios
                       FROM seg.rol r LEFT JOIN seg.rol_permiso rp ON rp.rol_id = r.id GROUP BY r.id ORDER BY r.id""")
        roles = cur.fetchall()
        cur.execute("SELECT codigo, modulo, descripcion FROM seg.permiso ORDER BY orden")
        return {"roles": roles, "permisos": cur.fetchall()}


class NuevoRol(BaseModel):
    codigo: str = Field(pattern=r"^[A-Z_]{3,30}$")
    nombre: str = Field(min_length=3, max_length=60)
    descripcion: str | None = None


@router.post("/roles", status_code=201)
def crear_rol(datos: NuevoRol, u: Usuario = Depends(requiere("roles.gestionar"))):
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("INSERT INTO seg.rol(codigo, nombre, descripcion) VALUES (%s,%s,%s) ON CONFLICT (codigo) DO NOTHING RETURNING id",
                    (datos.codigo, datos.nombre, datos.descripcion))
        r = cur.fetchone()
        if not r:
            raise HTTPException(409, "Ya existe un rol con ese código")
    return {"id": r["id"]}


@router.put("/roles/{rid}/permisos")
def asignar_permisos(rid: int, permisos: list[str], u: Usuario = Depends(requiere("roles.gestionar"))):
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("SELECT codigo FROM seg.rol WHERE id = %s", (rid,))
        rol = cur.fetchone()
        if not rol:
            raise HTTPException(404, "Rol no encontrado")
        if rol["codigo"] == "ADMINISTRADOR" and not {"roles.gestionar", "usuarios.gestionar"} <= set(permisos):
            raise HTTPException(400, "El rol Administrador debe conservar la gestión de usuarios y roles")
        cur.execute("SELECT codigo FROM seg.permiso WHERE codigo = ANY(%s)", (permisos,))
        validos = [r["codigo"] for r in cur.fetchall()]
        cur.execute("DELETE FROM seg.rol_permiso WHERE rol_id = %s AND NOT (permiso_codigo = ANY(%s))", (rid, validos))
        cur.execute("INSERT INTO seg.rol_permiso(rol_id, permiso_codigo) SELECT %s, unnest(%s::text[]) ON CONFLICT DO NOTHING", (rid, validos))
    return {"ok": True, "permisos": sorted(validos)}


@router.get("/bitacora")
def bitacora(usuario: str | None = None, accion: str | None = None, entidad: str | None = None, entidad_id: str | None = None,
             desde: date | None = None, hasta: date | None = None, antes_de: int | None = None, limite: int = Query(50, le=200),
             u: Usuario = Depends(requiere("auditoria.ver"))):
    w, p = [], []
    if usuario: w.append("b.usuario = %s"); p.append(usuario)
    if accion: w.append("b.accion = %s"); p.append(accion)
    if entidad: w.append("b.entidad = %s"); p.append(entidad)
    if entidad_id: w.append("b.entidad_id = %s"); p.append(entidad_id)
    if desde: w.append("b.ts >= %s"); p.append(desde)
    if hasta: w.append("b.ts < %s::date + 1"); p.append(hasta)
    if antes_de: w.append("b.id < %s"); p.append(antes_de)
    sql = "SELECT b.id, b.ts, b.usuario, b.accion, b.modulo, b.entidad, b.entidad_id, b.antes, b.despues, host(b.ip) AS ip FROM audit.bitacora b"
    if w:
        sql += " WHERE " + " AND ".join(w)
    sql += " ORDER BY b.id DESC LIMIT %s"
    with lectura() as cur:
        cur.execute(sql, p + [limite])
        return cur.fetchall()


@router.get("/parametros")
def parametros(u: Usuario = Depends(requiere("catalogos.gestionar", "auditoria.ver"))):
    with lectura() as cur:
        cur.execute("SELECT clave, valor, descripcion FROM ref.parametro ORDER BY clave")
        return cur.fetchall()


@router.put("/parametros/{clave}")
def cambiar_parametro(clave: str, valor: Any = Body(...), u: Usuario = Depends(requiere("catalogos.gestionar"))):
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("UPDATE ref.parametro SET valor = %s WHERE clave = %s RETURNING clave", (Jsonb(valor), clave))
        if not cur.fetchone():
            raise HTTPException(404, "Parámetro no encontrado")
    return {"ok": True}


class TipoResolucion(BaseModel):
    codigo: str = Field(min_length=1, max_length=6)
    nombre: str = Field(min_length=3, max_length=200)
    familia_id: int
    activo: bool = True


@router.put("/tipos-resolucion")
def guardar_tipo(datos: TipoResolucion, u: Usuario = Depends(requiere("catalogos.gestionar"))):
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("""INSERT INTO ref.cat_tipo_resolucion(codigo, nombre, familia_id, activo) VALUES (%s,%s,%s,%s)
                       ON CONFLICT (codigo) DO UPDATE SET nombre = EXCLUDED.nombre, familia_id = EXCLUDED.familia_id, activo = EXCLUDED.activo""",
                    (datos.codigo.upper(), datos.nombre, datos.familia_id, datos.activo))
        cur.execute("INSERT INTO mart.meta VALUES ('pendiente_refresco','1') ON CONFLICT (clave) DO UPDATE SET valor='1'")
    return {"ok": True}
