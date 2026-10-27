"""Comandos de administración.

    python -m pam.cli migrar
    python -m pam.cli crear-admin --usuario admin --nombre "Administrador" [--clave ...]
    python -m pam.cli importar "/data/importaciones/BD PAM.csv" [--validar] [--usuario admin]
    python -m pam.cli refrescar
    python -m pam.cli worker
"""
from __future__ import annotations

import argparse
import getpass
import logging
import secrets
import shutil
import sys
from pathlib import Path

import psycopg
from psycopg.rows import dict_row

from .config import settings
from .db import migrar


def main(argv: list[str] | None = None) -> int:
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
    ap = argparse.ArgumentParser(prog="pam")
    sub = ap.add_subparsers(dest="cmd", required=True)
    sub.add_parser("migrar", help="Aplicar migraciones pendientes")
    a = sub.add_parser("crear-admin", help="Crear (o restablecer) un usuario administrador")
    a.add_argument("--usuario", default="admin")
    a.add_argument("--nombre", default="Administrador del sistema")
    a.add_argument("--correo")
    a.add_argument("--clave", help="Si se omite, se genera una aleatoria y se muestra una sola vez")
    i = sub.add_parser("importar", help="Importar la matriz (CSV o XLSX)")
    i.add_argument("archivo")
    i.add_argument("--validar", action="store_true", help="Solo validar, sin modificar la base")
    i.add_argument("--usuario", default=None, help="Usuario que figura en la bitácora")
    i.add_argument("--sin-refrescar", action="store_true", help="No refrescar el tablero al terminar")
    sub.add_parser("refrescar", help="Recalcular los agregados del tablero")
    sub.add_parser("worker", help="Procesar importaciones en cola y refrescos programados")
    args = ap.parse_args(argv)

    if args.cmd == "migrar":
        hechas = migrar()
        print("Migraciones aplicadas:", ", ".join(hechas) if hechas else "ninguna (la base está al día)")
        return 0

    if args.cmd == "crear-admin":
        from .seguridad import hash_clave, validar_clave
        clave = args.clave or (secrets.token_urlsafe(12) + "A1!")
        problema = validar_clave(clave)
        if problema:
            print("Clave rechazada:", problema)
            return 2
        with psycopg.connect(settings.database_url, row_factory=dict_row) as conn:
            rol = conn.execute("SELECT id FROM seg.rol WHERE codigo='ADMINISTRADOR'").fetchone()["id"]
            conn.execute(
                """INSERT INTO seg.usuario(usuario, nombre, correo, hash, rol_id, debe_cambiar_clave)
                   VALUES (%s,%s,%s,%s,%s,true)
                   ON CONFLICT (usuario) DO UPDATE SET hash = EXCLUDED.hash, activo = true, intentos_fallidos = 0,
                       bloqueado_hasta = NULL, debe_cambiar_clave = true, rol_id = EXCLUDED.rol_id""",
                (args.usuario, args.nombre, args.correo, hash_clave(clave), rol))
        print(f"Usuario '{args.usuario}' listo." + ("" if args.clave else f" Clave temporal: {clave}  (se pedirá cambiarla al ingresar)"))
        return 0

    if args.cmd == "importar":
        from .etl.importador import crear_lote, ejecutar_lote
        origen = Path(args.archivo)
        if not origen.exists():
            print("No existe el archivo", origen)
            return 2
        settings.import_dir.mkdir(parents=True, exist_ok=True)
        destino = settings.import_dir / origen.name
        if origen.resolve() != destino.resolve():
            shutil.copy2(origen, destino)
        with psycopg.connect(settings.database_url, autocommit=True, row_factory=dict_row) as conn:
            uid = None
            if args.usuario:
                r = conn.execute("SELECT id FROM seg.usuario WHERE usuario=%s", (args.usuario,)).fetchone()
                uid = r["id"] if r else None
            lote = crear_lote(conn, destino, uid, "VALIDAR" if args.validar else "INCREMENTAL")
            conn.execute("UPDATE stg.import_lote SET estado='LEYENDO' WHERE id=%s", (lote,))  # que el worker no lo tome
        print(f"Lote {lote}: procesando {destino.name} …")
        rep = ejecutar_lote(lote, refrescar=not args.sin_refrescar)
        import json
        print(json.dumps(rep, ensure_ascii=False, indent=2, default=str))
        return 0

    if args.cmd == "refrescar":
        with psycopg.connect(settings.database_url, autocommit=True) as conn:
            conn.execute("SELECT mart.refrescar(true)")
        print("Agregados del tablero actualizados.")
        return 0

    if args.cmd == "worker":
        from .worker import ciclo
        ciclo()
        return 0
    return 1


if __name__ == "__main__":
    sys.exit(main())
