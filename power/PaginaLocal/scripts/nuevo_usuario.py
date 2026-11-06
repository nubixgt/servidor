"""Crea, lista y administra los usuarios de la beta.

Se corre EN EL SERVIDOR, nunca desde la app: dar de alta a alguien es una
decisión del operador, y una ruta HTTP que cree usuarios es una ruta que
alguien encontrará.

    py scripts/nuevo_usuario.py --admin                    # tu cuenta de operador
    py scripts/nuevo_usuario.py ana --perfil "Ana Lopez"   # una atleta
    py scripts/nuevo_usuario.py --listar
    py scripts/nuevo_usuario.py ana --reset                # contraseña nueva
    py scripts/nuevo_usuario.py ana --borrar

La contraseña temporal se genera aquí y **se imprime una sola vez**: no se
guarda en claro en ningún sitio, así que si se pierde hay que resetearla. Es a
propósito — un archivo con las contraseñas de la beta en texto plano es
exactamente lo que no queremos tener.

El usuario nace con `must_change_password`, así que la contraseña que mandes
por correo sólo sirve para entrar y cambiarla. Una contraseña que viajó por
correo no puede quedarse siendo la contraseña.
"""

from __future__ import annotations

import argparse
import secrets
import string
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core import auth  # noqa: E402

# Sin caracteres que se confundan al leerlos de un correo: 0/O, 1/l/I. Una
# contraseña temporal se teclea a mano una vez, y "no me funciona" casi
# siempre es una ele que era un uno.
_ALFABETO = "".join(c for c in string.ascii_letters + string.digits if c not in "0O1lI")


def generar_password(largo: int = 14) -> str:
    return "".join(secrets.choice(_ALFABETO) for _ in range(largo))


def _perfiles_disponibles() -> list[str]:
    import json

    ruta = Path(__file__).resolve().parents[1] / "user_secure_data" / "profiles.json"
    try:
        return sorted(json.loads(ruta.read_text(encoding="utf-8")).keys())
    except (OSError, json.JSONDecodeError):
        return []


def _listar() -> int:
    usuarios = auth.load_users()
    if not usuarios:
        print("No hay usuarios. La app deja entrar a cualquiera hasta que crees el primero:")
        print("    py scripts/nuevo_usuario.py --admin")
        return 0
    print(f"{'usuario':<20} {'perfil':<26} {'admin':<7} {'debe cambiar':<14} último acceso")
    print("-" * 92)
    for nombre, u in sorted(usuarios.items()):
        print(f"{nombre:<20} {str(u.get('profile') or '—'):<26} "
              f"{'sí' if u.get('is_admin') else '—':<7} "
              f"{'SÍ' if u.get('must_change_password') else '—':<14} "
              f"{u.get('last_login') or 'nunca'}")
    return 0


def _entregar(usuario: str, password: str, perfil: str | None) -> None:
    print("\n" + "=" * 66)
    print("  CREDENCIALES — se muestran UNA sola vez")
    print("=" * 66)
    print(f"  usuario:    {usuario}")
    print(f"  contraseña: {password}")
    if perfil:
        print(f"  perfil:     {perfil}")
    print("=" * 66)
    print("  Mándalas por un canal que no sea el mismo donde vive la app.")
    print("  Al entrar se le pedirá cambiarla; ésta deja de servir ahí mismo.\n")


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("usuario", nargs="?", help="Nombre de usuario (se guarda en minúsculas)")
    parser.add_argument("--perfil", help="Perfil del atleta que este usuario podrá ver")
    parser.add_argument("--admin", action="store_true", help="Cuenta de operador: ve todos los perfiles")
    parser.add_argument("--password", help="Contraseña concreta (si no, se genera una)")
    parser.add_argument("--listar", action="store_true", help="Lista los usuarios y sale")
    parser.add_argument("--reset", action="store_true", help="Le pone una contraseña temporal nueva")
    parser.add_argument("--borrar", action="store_true", help="Elimina el usuario y cierra sus sesiones")
    args = parser.parse_args(argv)

    if args.listar:
        return _listar()

    usuario = args.usuario or ("admin" if args.admin else None)
    if not usuario:
        parser.error("Falta el nombre de usuario (o usa --admin, o --listar).")

    if args.borrar:
        if auth.delete_user(usuario):
            print(f"Usuario '{usuario}' eliminado; sus sesiones quedaron cerradas.")
            return 0
        print(f"No existe el usuario '{usuario}'.")
        return 1

    if args.reset:
        password = args.password or generar_password()
        try:
            auth.set_password(usuario, password)
        except ValueError as exc:
            print(f"Error: {exc}")
            return 1
        # set_password deja must_change_password en False (es el camino del
        # atleta cambiando la suya). Un reset del operador es lo contrario:
        # vuelve a ser temporal.
        usuarios = auth.load_users()
        usuarios[auth.normalize_username(usuario)]["must_change_password"] = True
        auth._save_users(usuarios)
        print(f"Contraseña de '{usuario}' restablecida. Sus sesiones quedaron cerradas.")
        _entregar(usuario, password, usuarios[auth.normalize_username(usuario)].get("profile"))
        return 0

    perfil = args.perfil
    if perfil and perfil not in _perfiles_disponibles():
        disponibles = _perfiles_disponibles()
        print(f"Aviso: el perfil '{perfil}' todavía no existe.")
        if disponibles:
            print(f"       Existentes: {', '.join(disponibles)}")
        print("       Se asigna igual: el atleta lo creará al entrar la primera vez.\n")

    password = args.password or generar_password()
    try:
        auth.create_user(usuario, password, profile=perfil, is_admin=args.admin)
    except ValueError as exc:
        print(f"Error: {exc}")
        return 1

    print(f"Usuario '{usuario}' creado" + (" (operador)." if args.admin else "."))
    if not args.admin and not perfil:
        print("Aviso: sin --perfil, este usuario no podrá ver ningún plan hasta que le asignes uno.")
    _entregar(usuario, password, perfil)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
