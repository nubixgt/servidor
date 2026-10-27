"""Dependencias comunes: usuario autenticado, permisos, ámbito territorial, auditoría y límites."""
from __future__ import annotations

import ipaddress
import time
import uuid
from collections import defaultdict, deque
from dataclasses import dataclass, field

import jwt
from fastapi import Depends, HTTPException, Request, status

from ..config import settings
from ..db import lectura, transaccion
from ..seguridad import leer_access


@dataclass
class Usuario:
    id: int
    usuario: str
    nombre: str
    rol: str
    rol_nombre: str
    permisos: set[str]
    departamento_id: int | None = None
    municipio_id: int | None = None
    debe_cambiar_clave: bool = False
    sesion_id: str | None = None
    ip: str | None = None
    request_id: str = field(default_factory=lambda: uuid.uuid4().hex[:16])

    def puede(self, permiso: str) -> bool:
        return permiso in self.permisos

    def ctx(self) -> dict:
        return {"id": self.id, "usuario": self.usuario}

    def as_dict(self) -> dict:
        return {"id": self.id, "usuario": self.usuario, "nombre": self.nombre, "rol": self.rol, "rol_nombre": self.rol_nombre,
                "permisos": sorted(self.permisos), "departamento_id": self.departamento_id, "municipio_id": self.municipio_id,
                "debe_cambiar_clave": self.debe_cambiar_clave}


def ip_cliente(request: Request) -> str | None:
    """IP real del cliente (Nginx envía X-Forwarded-For / X-Real-IP)."""
    cand = request.headers.get("x-real-ip") or (request.headers.get("x-forwarded-for") or "").split(",")[0].strip() \
        or (request.client.host if request.client else None)
    try:
        return str(ipaddress.ip_address(cand)) if cand else None
    except ValueError:
        return None


def cargar_usuario(usuario_id: int, sesion_id: str | None = None) -> Usuario | None:
    with lectura() as cur:
        cur.execute("""
            SELECT u.id, u.usuario, u.nombre, u.activo, u.debe_cambiar_clave, u.departamento_id, u.municipio_id,
                   r.codigo AS rol, r.nombre AS rol_nombre,
                   coalesce(array_agg(rp.permiso_codigo) FILTER (WHERE rp.permiso_codigo IS NOT NULL), '{}') AS permisos,
                   (SELECT s.revocado_en IS NULL AND s.expira_en > now() FROM seg.sesion s WHERE s.id = %s::uuid) AS sesion_ok
            FROM seg.usuario u JOIN seg.rol r ON r.id = u.rol_id
            LEFT JOIN seg.rol_permiso rp ON rp.rol_id = r.id
            WHERE u.id = %s GROUP BY u.id, r.codigo, r.nombre""", (sesion_id, usuario_id))
        r = cur.fetchone()
    if not r or not r["activo"] or (sesion_id and not r["sesion_ok"]):
        return None
    return Usuario(r["id"], r["usuario"], r["nombre"], r["rol"], r["rol_nombre"], set(r["permisos"]),
                   r["departamento_id"], r["municipio_id"], r["debe_cambiar_clave"], sesion_id)


def usuario_actual(request: Request) -> Usuario:
    auth = request.headers.get("authorization", "")
    if not auth.lower().startswith("bearer "):
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Sesión requerida")
    try:
        datos = leer_access(auth[7:])
    except jwt.ExpiredSignatureError:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "La sesión expiró")
    except jwt.InvalidTokenError:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Token inválido")
    u = cargar_usuario(int(datos["sub"]), datos.get("sid"))
    if not u:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Sesión cerrada o usuario inactivo")
    u.ip = ip_cliente(request)
    u.request_id = request.headers.get("x-request-id", u.request_id)
    request.state.usuario = u
    return u


def requiere(*permisos: str):
    """Exige al menos uno de los permisos indicados."""
    def dep(u: Usuario = Depends(usuario_actual)) -> Usuario:
        if u.debe_cambiar_clave:
            raise HTTPException(status.HTTP_403_FORBIDDEN, "Debe cambiar su contraseña antes de continuar")
        if not any(u.puede(p) for p in permisos):
            raise HTTPException(status.HTTP_403_FORBIDDEN, "No tiene permiso para esta acción")
        return u
    return dep


def ambito_sql(u: Usuario, alias: str = "v") -> tuple[str, list]:
    """Filtro territorial del usuario (municipio o departamento asignado)."""
    if u.municipio_id:
        return f" AND {alias}.municipio_id = %s", [u.municipio_id]
    if u.departamento_id:
        return f" AND {alias}.departamento_id = %s", [u.departamento_id]
    return "", []


def auditar(u: Usuario | None, accion: str, modulo: str, entidad: str | None = None, entidad_id: str | None = None,
            detalle: dict | None = None, request: Request | None = None) -> None:
    from psycopg.types.json import Jsonb
    with transaccion() as cur:
        cur.execute("""INSERT INTO audit.bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, despues, ip, user_agent, request_id)
                       VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)""",
                    (u.id if u else None, u.usuario if u else None, accion, modulo, entidad, entidad_id,
                     Jsonb(detalle) if detalle is not None else None,
                     (u.ip if u else None) or (ip_cliente(request) if request else None),
                     request.headers.get("user-agent") if request else None, u.request_id if u else None))


# --- límite de consultas por usuario (protección contra barridos masivos) ---
_ventanas: dict[str, deque] = defaultdict(deque)


def limitar(clave: str, por_minuto: int) -> None:
    ahora = time.time()
    v = _ventanas[clave]
    while v and ahora - v[0] > 60:
        v.popleft()
    if len(v) >= por_minuto:
        raise HTTPException(status.HTTP_429_TOO_MANY_REQUESTS, "Demasiadas consultas seguidas. Espere un minuto e intente de nuevo.")
    v.append(ahora)


def marcar_refresco_pendiente(cur) -> None:
    cur.execute("INSERT INTO mart.meta VALUES ('pendiente_refresco','1') ON CONFLICT (clave) DO UPDATE SET valor='1'")
