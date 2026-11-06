"""Importa TODOS los módulos del proyecto y dice cuáles fallan.

Existe por lo que pasó el 2026-09-02: se metió `ruff` y su `--fix` reordenó
imports en decenas de archivos de una sola pasada. Las pruebas cubren mucho,
pero no importan cada módulo — un archivo que sólo se carga en producción
podía quedar roto sin que nada se pusiera rojo.

Un import roto no es un fallo sutil: es un 500 en la cara del atleta. Y es lo
más barato de comprobar que existe.

Correr con:  python scripts/auditar_imports.py
"""

import importlib
import pathlib
import sys

RAIZ = pathlib.Path(__file__).resolve().parents[1]
sys.path.insert(0, str(RAIZ))

# Se saltan los que no son módulos de la app o requieren un entorno aparte.
EXCLUIR = ("__pycache__", "venv", ".venv", "site-packages")


def modulos():
    for p in sorted(RAIZ.rglob("*.py")):
        rel = p.relative_to(RAIZ)
        partes = rel.parts
        if any(x in partes for x in EXCLUIR):
            continue
        nombre = ".".join(partes)[:-3]
        yield nombre.removesuffix(".__init__")


def main() -> int:
    ok, fallos = 0, []
    for mod in modulos():
        try:
            importlib.import_module(mod)
            ok += 1
        except Exception as e:  # noqa: BLE001 - se quiere ver CUALQUIER fallo
            fallos.append((mod, f"{type(e).__name__}: {e}"))

    print(f"importados sin error : {ok}")
    print(f"FALLOS               : {len(fallos)}")
    for mod, err in fallos:
        print(f"  - {mod}\n      {err[:200]}")
    return 1 if fallos else 0


if __name__ == "__main__":
    raise SystemExit(main())
