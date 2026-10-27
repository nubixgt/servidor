"""Contraseñas (Argon2id), tokens de acceso (JWT) y de renovación."""
from __future__ import annotations

import hashlib
import re
import secrets
import time

import jwt
from argon2 import PasswordHasher
from argon2.exceptions import InvalidHashError, VerifyMismatchError

from .config import settings

_ph = PasswordHasher()


def hash_clave(clave: str) -> str:
    return _ph.hash(clave)


def verificar_clave(hash_: str, clave: str) -> bool:
    try:
        return _ph.verify(hash_, clave)
    except (VerifyMismatchError, InvalidHashError):
        return False


def validar_clave(clave: str) -> str | None:
    """Política: 10+ caracteres, mayúscula, minúscula y número."""
    if len(clave) < 10:
        return "Debe tener al menos 10 caracteres"
    if not re.search(r"[A-ZÁÉÍÓÚÑ]", clave) or not re.search(r"[a-záéíóúñ]", clave):
        return "Debe combinar mayúsculas y minúsculas"
    if not re.search(r"\d", clave):
        return "Debe incluir al menos un número"
    return None


def crear_access(usuario_id: int, sesion_id: str) -> str:
    ahora = int(time.time())
    return jwt.encode({"sub": str(usuario_id), "sid": sesion_id, "iat": ahora, "exp": ahora + settings.access_ttl_min * 60, "typ": "access"},
                      settings.jwt_secret, algorithm="HS256")


def leer_access(token: str) -> dict:
    datos = jwt.decode(token, settings.jwt_secret, algorithms=["HS256"])
    if datos.get("typ") != "access":
        raise jwt.InvalidTokenError("tipo de token inválido")
    return datos


def nuevo_refresh() -> tuple[str, str]:
    """Devuelve (token en claro para la cookie, hash para la base)."""
    t = secrets.token_urlsafe(48)
    return t, hashlib.sha256(t.encode()).hexdigest()


def hash_refresh(t: str) -> str:
    return hashlib.sha256(t.encode()).hexdigest()


def enmascarar_dpi(d: str | None) -> str | None:
    if not d:
        return d
    if len(d) == 13:
        return "•••• ••••• " + d[9:]
    return "•" * max(0, len(d) - 2) + d[-2:]
