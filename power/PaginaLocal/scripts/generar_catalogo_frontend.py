"""Vuelca los catálogos del cerebro a JSON para que los lea el frontend.

El catálogo vive en Python (`services/ai/exercise_catalog.py`) porque es el
cerebro quien tiene que ofrecerle al modelo la lista cerrada de la que elegir
`video_key`. Pero el frontend necesita la misma tabla para pintar la imagen, y
tener dos listas escritas a mano es tener dos listas que divergen — que es
exactamente cómo el desplegable de competiciones acabó ofreciendo cinco valores
que el catálogo de Python no reconocía.

Así que hay UNA fuente y este script genera la copia. Se vuelve a correr cada
vez que se toca el catálogo:

    python PaginaLocal/scripts/generar_catalogo_frontend.py

Se escribe en `Frontend/public/ejercicios/catalogo.json`, que Vite copia tal
cual a `dist/` y el despliegue sube junto al resto del frontend.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(RAIZ / "PaginaLocal"))

from services.ai.exercise_catalog import (  # noqa: E402
    ALIAS,
    EJERCICIOS,
    SIN_IMAGEN,
    garmin_de,
    imagen_de,
)
from services.ai.race_distances import options_for_ui  # noqa: E402

PUBLIC = RAIZ / "Frontend" / "public"
DESTINO = PUBLIC / "ejercicios" / "catalogo.json"
DESTINO_CARRERAS = PUBLIC / "carreras.json"
DESTINO_TEST = PUBLIC / "test_de_umbral.json"


def construir() -> dict:
    ejercicios = {}
    for slug, (n, es, en, grupo, sub) in EJERCICIOS.items():
        ejercicios[slug] = {
            "n": n,
            "es": es,
            "en": en,
            "grupo": grupo,
            "sub": sub,
            # `img` es el nombre del fichero, o null si esa imagen no existe.
            # El frontend NO lo deduce del número.
            #
            # Sale de `imagen_de` y NO de una copia de su regla aquí. Este
            # script existe para que el frontend y el cerebro no tengan dos
            # listas escritas a mano; tener dos IMPLEMENTACIONES de "¿qué
            # fichero le toca a este slug?" es el mismo fallo un piso más
            # abajo, y habría pasado desapercibido mientras las dos
            # coincidieran.
            "img": imagen_de(slug),
            # Si llega al reloj con su nombre de Garmin (pareja verificada). Lo enseña el selector del
            # catálogo al editar una fuerza (2026-10-23): sin él, el reloj lo muestra como un paso de
            # fuerza con sus repeticiones. Sale de `garmin_de`, lo mismo que usa la subida.
            "reloj": bool(garmin_de(es)[1]) if grupo in ("fuerza", "movilidad") else False,
        }
    return {
        "version": 1,
        "nota": "Generado por PaginaLocal/scripts/generar_catalogo_frontend.py. No editar a mano.",
        "ejercicios": ejercicios,
        "alias": dict(sorted(ALIAS.items())),
    }


def escribir(destino: Path, datos) -> None:
    destino.parent.mkdir(parents=True, exist_ok=True)
    destino.write_text(
        json.dumps(datos, ensure_ascii=False, separators=(",", ":")),
        encoding="utf-8",
    )


def main() -> int:
    datos = construir()
    escribir(DESTINO, datos)
    con_imagen = sum(1 for e in datos["ejercicios"].values() if e["img"])
    print(f"{DESTINO.relative_to(RAIZ)}")
    print(f"  {len(datos['ejercicios'])} ejercicios ({con_imagen} con imagen), "
          f"{len(datos['alias'])} alias, {DESTINO.stat().st_size / 1024:.1f} KB")
    faltan = sorted(SIN_IMAGEN)
    if faltan:
        print(f"  sin imagen: {faltan}")

    # Los formatos de carrera, por el mismo motivo y con el mismo remedio: el
    # desplegable del alta tenia NUEVE valores escritos a mano y cinco de ellos
    # -- Sprint, Olympic, half_ironman, ironman, gran_fondo -- no existian en
    # `RACE_FORMATS`. Con eso, ninguna meta de triatlon se podia convertir en
    # metros, y `goal_realism` no podia decir que ritmo exige el objetivo.
    grupos = [g for g in options_for_ui() if g["options"]]
    escribir(DESTINO_CARRERAS, {
        "version": 1,
        "nota": "Generado por PaginaLocal/scripts/generar_catalogo_frontend.py. No editar a mano.",
        "grupos": grupos,
    })
    total = sum(len(g["options"]) for g in grupos)
    print(f"{DESTINO_CARRERAS.relative_to(RAIZ)}")
    print(f"  {total} formatos en {len(grupos)} grupos, "
          f"{DESTINO_CARRERAS.stat().st_size / 1024:.1f} KB")

    # Los protocolos del test de umbral: el texto que el atleta sigue y la cuenta
    # que hace el cerebro tienen que decir lo mismo, así que salen de un sitio.
    from services.ai.test_de_umbral import PROTOCOLOS
    escribir(DESTINO_TEST, {
        "version": 1,
        "nota": "Generado por PaginaLocal/scripts/generar_catalogo_frontend.py. No editar a mano.",
        "protocolos": PROTOCOLOS,
    })
    print(f"{DESTINO_TEST.relative_to(RAIZ)}")
    print(f"  {len(PROTOCOLOS)} protocolos, {DESTINO_TEST.stat().st_size / 1024:.1f} KB")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
