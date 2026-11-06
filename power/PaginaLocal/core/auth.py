"""Quién entra, y con qué. La puerta que la beta no puede no tener.

Hasta v7 la app no tenía autenticación de ninguna clase: ni login, ni sesión,
ni una sola comprobación. Eso funcionaba porque escuchaba en 127.0.0.1 y la
usaba una persona. Con atletas de pago en un servidor, las mismas 33 rutas
significan otra cosa:

- Cada ruta recibía el perfil como parámetro (`?profile=Ana`) y no comprobaba
  de quién era. Escribiendo otro nombre, cualquiera leía, regeneraba o
  borraba el plan ajeno.
- `POST /api/server/shutdown` estaba abierto: cualquiera apagaba el servidor
  de todos.

## Decisiones, y por qué

**Sin dependencias nuevas.** `hashlib.scrypt` es de la biblioteca estándar y
es un KDF de contraseñas serio (el mismo que recomienda el propio módulo
frente a un hash rápido). Este repo tiene 29 dependencias y una razón escrita
para cada una; añadir passlib/bcrypt para esto sería cobrar un coste de
mantenimiento sin comprar nada.

**Sesiones opacas del lado del servidor, no un JWT.** Un JWT no se puede
revocar sin llevar igualmente estado en el servidor, y aquí hay que poder
echar a alguien: es una beta, las contraseñas se reparten por correo. Un token
aleatorio en un archivo se revoca borrándolo.

**Las sesiones sobreviven a un reinicio.** Se guardan en disco. Un `--reload`
en desarrollo o un reinicio del servicio no puede desconectar a todos los
atletas a media generación.

**La contraseña temporal caduca al usarla.** `must_change_password` bloquea
todo lo demás hasta que el atleta pone la suya. Una contraseña que viajó por
correo no puede quedarse siendo la contraseña.

## Lo que este módulo NO hace

No decide qué puede ver un usuario -- sólo quién es. La autorización (que el
perfil que pides sea tuyo) vive en el servidor, donde están los perfiles.
"""

from __future__ import annotations

import hashlib
import hmac
import json
import logging
import os
import secrets
import threading
from datetime import UTC, datetime, timedelta
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)

_PROJECT_ROOT = Path(__file__).resolve().parents[1]
SECURE_DIR = _PROJECT_ROOT / "user_secure_data"
USERS_FILE = SECURE_DIR / "users.json"
SESSIONS_FILE = SECURE_DIR / "sessions.json"

SESSION_COOKIE = "tricoach_session"
SESSION_DAYS = 14

# Parámetros de scrypt. n=2**15 tarda del orden de 100 ms en un servidor
# modesto: suficientemente lento para que probar contraseñas a lo bruto no
# valga la pena, suficientemente rápido para que un login no se note. Van
# guardados en cada registro, así que subirlos más adelante no invalida las
# contraseñas ya creadas.
#
# `maxmem` NO es decorativo. scrypt necesita 128·n·r bytes, que con estos
# valores son exactamente 32 MiB, y el límite por omisión de OpenSSL son
# justamente 32 MiB -- así que sin subirlo la llamada falla con un
# "memory limit exceeded" que no menciona scrypt por ningún lado. Se
# descubrió creando el primer usuario de verdad: la suite no lo vio porque
# los tests bajan `n` para no tardar tres segundos por corrida, y con `n`
# pequeño el límite nunca se alcanza.
_SCRYPT = {"n": 2 ** 15, "r": 8, "p": 1, "dklen": 64, "maxmem": 128 * 1024 * 1024}

_lock = threading.Lock()


# --- almacenamiento --------------------------------------------------------

def _read(path: Path) -> dict[str, Any]:
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return {}


def _write(path: Path, data: dict[str, Any]) -> None:
    """Escritura atómica: a un temporal y luego os.replace.

    Escribir en el sitio deja una ventana en la que el archivo está a medias,
    y si el proceso muere ahí, el archivo de usuarios queda ilegible y nadie
    puede entrar. os.replace es atómico en POSIX y en Windows.
    """
    SECURE_DIR.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(path.suffix + ".tmp")
    tmp.write_text(json.dumps(data, indent=2, ensure_ascii=False), encoding="utf-8")
    os.replace(tmp, path)


def load_users() -> dict[str, Any]:
    return _read(USERS_FILE)


def _save_users(users: dict[str, Any]) -> None:
    _write(USERS_FILE, users)


# --- contraseñas -----------------------------------------------------------

def hash_password(password: str) -> dict[str, Any]:
    salt = secrets.token_bytes(16)
    digest = hashlib.scrypt(password.encode("utf-8"), salt=salt, **_SCRYPT)
    return {"algo": "scrypt", "salt": salt.hex(), "hash": digest.hex(), **_SCRYPT}


def verify_password(password: str, record: dict[str, Any] | None) -> bool:
    """Comparación en tiempo constante.

    `hmac.compare_digest` y no `==`: comparar hashes con el operador normal
    filtra, por cuánto tarda en fallar, cuántos bytes iniciales acertaste.
    """
    if not record or record.get("algo") != "scrypt":
        return False
    try:
        digest = hashlib.scrypt(
            password.encode("utf-8"),
            salt=bytes.fromhex(record["salt"]),
            n=int(record.get("n", _SCRYPT["n"])),
            r=int(record.get("r", _SCRYPT["r"])),
            p=int(record.get("p", _SCRYPT["p"])),
            dklen=int(record.get("dklen", _SCRYPT["dklen"])),
            maxmem=int(record.get("maxmem", _SCRYPT["maxmem"])),
        )
    except (ValueError, KeyError, TypeError):
        logger.warning("Registro de contraseña ilegible; se rechaza el intento")
        return False
    return hmac.compare_digest(digest.hex(), record.get("hash", ""))


# --- usuarios --------------------------------------------------------------

def normalize_username(username: str) -> str:
    return (username or "").strip().lower()


def create_user(
    username: str,
    password: str,
    *,
    profile: str | None = None,
    is_admin: bool = False,
    must_change_password: bool = True,
) -> dict[str, Any]:
    """Crea un usuario. Falla si ya existe -- nunca pisa uno en silencio."""
    username = normalize_username(username)
    if not username:
        raise ValueError("El usuario no puede estar vacío.")
    if len(password or "") < 8:
        raise ValueError("La contraseña temporal debe tener al menos 8 caracteres.")
    with _lock:
        users = load_users()
        if username in users:
            raise ValueError(f"El usuario '{username}' ya existe.")
        users[username] = {
            "username": username,
            "password": hash_password(password),
            # El perfil del atleta que este usuario puede ver. Uno por
            # usuario: es lo que convierte "?profile=" de parámetro de
            # confianza en algo que hay que comprobar.
            "profile": profile,
            "is_admin": bool(is_admin),
            "must_change_password": bool(must_change_password),
            "created_at": datetime.now(UTC).isoformat(timespec="seconds"),
            "last_login": None,
        }
        _save_users(users)
        return users[username]


def set_password(username: str, password: str) -> None:
    username = normalize_username(username)
    if len(password or "") < 8:
        raise ValueError("La contraseña debe tener al menos 8 caracteres.")
    with _lock:
        users = load_users()
        if username not in users:
            raise ValueError(f"No existe el usuario '{username}'.")
        users[username]["password"] = hash_password(password)
        users[username]["must_change_password"] = False
        _save_users(users)
    # Cambiar la contraseña cierra las demás sesiones. Si alguien entró con la
    # contraseña vieja, cambiarla tiene que echarlo -- si no, cambiarla no
    # sirve de nada justo cuando más falta hace.
    revoke_sessions_for(username)


def set_profile(username: str, profile: str | None) -> None:
    username = normalize_username(username)
    with _lock:
        users = load_users()
        if username not in users:
            raise ValueError(f"No existe el usuario '{username}'.")
        users[username]["profile"] = profile
        _save_users(users)


def authenticate(username: str, password: str) -> dict[str, Any] | None:
    """Devuelve el usuario si las credenciales son correctas, o None.

    Gasta el mismo trabajo con un usuario inexistente que con uno real: si no
    lo hiciera, el tiempo de respuesta diría cuáles de los usuarios probados
    existen.
    """
    username = normalize_username(username)
    user = load_users().get(username)
    if user is None:
        hash_password(password or "")  # trabajo equivalente, a propósito
        return None
    if not verify_password(password or "", user.get("password")):
        return None
    with _lock:
        users = load_users()
        if username in users:
            users[username]["last_login"] = datetime.now(UTC).isoformat(timespec="seconds")
            _save_users(users)
    return load_users().get(username)


# --- sesiones --------------------------------------------------------------

def _load_sessions() -> dict[str, Any]:
    return _read(SESSIONS_FILE)


def _prune(sessions: dict[str, Any]) -> dict[str, Any]:
    now = datetime.now(UTC)
    alive = {}
    for token, session in sessions.items():
        try:
            if datetime.fromisoformat(session["expires"]) > now:
                alive[token] = session
        except (KeyError, ValueError):
            continue  # sesión ilegible = sesión caducada
    return alive


def start_session(username: str) -> tuple[str, datetime]:
    token = secrets.token_urlsafe(32)
    expires = datetime.now(UTC) + timedelta(days=SESSION_DAYS)
    with _lock:
        sessions = _prune(_load_sessions())
        sessions[token] = {
            "username": normalize_username(username),
            "expires": expires.isoformat(),
            "started": datetime.now(UTC).isoformat(timespec="seconds"),
        }
        _write(SESSIONS_FILE, sessions)
    return token, expires


def session_user(token: str | None) -> dict[str, Any] | None:
    """El usuario de esta sesión, o None si no la hay, caducó o el usuario ya
    no existe (borrarlo tiene que invalidar sus sesiones al instante)."""
    if not token:
        return None
    session = _prune(_load_sessions()).get(token)
    if not session:
        return None
    return load_users().get(session["username"])


def end_session(token: str | None) -> None:
    if not token:
        return
    with _lock:
        sessions = _prune(_load_sessions())
        sessions.pop(token, None)
        _write(SESSIONS_FILE, sessions)


def revoke_sessions_for(username: str) -> int:
    username = normalize_username(username)
    with _lock:
        sessions = _prune(_load_sessions())
        doomed = [t for t, s in sessions.items() if s.get("username") == username]
        for token in doomed:
            del sessions[token]
        _write(SESSIONS_FILE, sessions)
    return len(doomed)


def delete_user(username: str) -> bool:
    username = normalize_username(username)
    with _lock:
        users = load_users()
        existed = users.pop(username, None) is not None
        if existed:
            _save_users(users)
    if existed:
        revoke_sessions_for(username)
    return existed


def any_users_exist() -> bool:
    """Si no hay ninguno, la app no puede dejar entrar a nadie -- y decirlo en
    la pantalla de login es mejor que un 'contraseña incorrecta' eterno."""
    return bool(load_users())
