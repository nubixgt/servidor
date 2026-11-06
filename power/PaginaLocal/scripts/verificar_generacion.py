"""Comprueba una generación REAL contra las garantías de la v1.1.

No sustituye a la suite: la suite prueba funciones puras, y esto mira lo que de
verdad salió — el prompt que recibió cada modelo y el plan que produjo. Es el
nivel de evidencia que este repo trata como el bueno ("verificado contra la
cuenta real"), aplicado a los cambios de la v1.1.

    python PaginaLocal/scripts/verificar_generacion.py [u<id>]

Lee lo último de `data/storage/<perfil>/`:

  - `reasoning/<ISO>/prompts/*.json`  — lo que cada modelo recibió
  - `weekly_plan.json`                — lo que produjo

Cada comprobación imprime OK o FALLA con lo que encontró, y el proceso sale con
código 1 si algo falla, para poder encadenarlo.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(RAIZ))

from services.ai.exercise_catalog import resolver  # noqa: E402

MARCA_PETICIONES = "[LO QUE EL ATLETA PIDE PARA SU SEMANA]"

fallos: list[str] = []


def check(nombre: str, ok: bool, detalle: str = "") -> None:
    print(f"  {'OK  ' if ok else '?? '} {nombre}" + (f"  — {detalle}" if detalle else ""))
    if not ok:
        fallos.append(nombre)


def ultimo_directorio(base: Path) -> Path | None:
    dirs = [d for d in base.iterdir() if d.is_dir()] if base.is_dir() else []
    return max(dirs, key=lambda d: d.name) if dirs else None


def texto_de_prompts(carpeta: Path) -> dict[str, str]:
    """El texto completo de cada llamada archivada, por nombre de fichero."""
    salida = {}
    for f in sorted(carpeta.glob("*.json")):
        try:
            crudo = json.loads(f.read_text(encoding="utf-8"))
        except (OSError, json.JSONDecodeError):
            continue
        salida[f.stem] = json.dumps(crudo, ensure_ascii=False)
    return salida


ETIQUETA_A_CLAVE = {"Correr": "run", "Trail": "run", "Bici": "bike",
                    "Nadar": "swim", "Fuerza": "strength"}


def _fijos_del_prompt(texto: str) -> dict[str, list[str]]:
    """Los `fijo` leidos de la TABLA QUE RECIBIO EL MODELO.

    No del perfil ni del espejo: del prompt archivado. Es la unica fuente que
    no puede mentir sobre lo que se le pidio al modelo -- `profiles.json` es un
    espejo de usar y tirar que puede estar rancio, y el perfil vive en MySQL.

    "Trail" se cuenta como `run`: es terreno, no deporte, y un trail pedido se
    satisface con una sesion de carrera (misma regla que en el validador).

    Devuelve `{}` si no hay tabla -- un perfil sin rejilla no ha pedido nada.
    """
    if MARCA_PETICIONES not in texto:
        return {}
    trozo = texto.split(MARCA_PETICIONES, 1)[1]
    fijos: dict[str, list[str]] = {}
    for linea in trozo.splitlines():
        partes = [x.strip() for x in linea.split("|")]
        if len(partes) != 3 or partes[0] in ("Dia", "") or partes[0].startswith("-"):
            continue
        claves = sorted({ETIQUETA_A_CLAVE[e.strip()]
                         for e in partes[1].split(",")
                         if e.strip() in ETIQUETA_A_CLAVE})
        if claves:
            fijos[partes[0]] = claves
    return fijos


def main(perfil: str) -> int:
    # El espejo VIVO que usa el AiService, no `PaginaLocal/data/storage`, que
    # es el de la app de escritorio de la que salio este cerebro. Ver
    # AiService/bridge/storage_adapter.py y ../CLAUDE.md.
    storage = RAIZ.parent / "AiService" / "_runtime" / "storage" / perfil
    if not storage.is_dir():
        print(f"No existe {storage}")
        return 1

    print(f"\n=== {perfil} ===")

    # ---- El prompt que recibieron los modelos --------------------------------
    razon = ultimo_directorio(storage / "reasoning")
    prompts = texto_de_prompts(razon / "prompts") if razon and (razon / "prompts").is_dir() else {}
    todo = "\n".join(prompts.values())
    # `todo` es JSON, asi que sus saltos de linea viajan escapados. Para BUSCAR
    # una frase da igual; para PARSEAR la tabla de peticiones linea a linea, no.
    legible = todo.encode().decode("unicode_escape", errors="ignore")
    print(f"\nTraza: {razon.name if razon else '(ninguna)'} — {len(prompts)} llamadas archivadas")

    print("\n-- La disponibilidad llega como PETICIÓN --")
    check("la tabla de peticiones está en el prompt",
          MARCA_PETICIONES in todo)
    check("distingue FIJO de POSIBLE", "FIJO (lo ha pedido)" in todo)
    check("la regla de la frontera está",
          "VOLUMEN Y LA INTENSIDAD" in todo,
          "el deporte y el día son suyos; el volumen y la intensidad nuestros")
    check("ya NO se le dice que un bloque es un permiso",
          "UN BLOQUE ES UN PERMISO" not in todo)

    print("\n-- El catálogo de ejercicios llega al codificador --")
    check("la lista cerrada de `video_key` está en el prompt",
          "Claves de ejercicio disponibles" in todo)

    print("\n-- Lo que ya no debe aparecer --")
    check("no se le ofrece `sport=\"brick\"`",
          '`sport="brick"` y `discipline="brick"`' not in todo)

    # ---- El plan que salió ---------------------------------------------------
    plan_path = storage / "weekly_plan.json"
    if not plan_path.is_file():
        print("\n(todavía no hay weekly_plan.json)")
        return 1 if fallos else 0

    plan = json.loads(plan_path.read_text(encoding="utf-8"))
    dias = [d for s in plan.get("weeks", []) for d in s.get("days", [])]
    entrenos = [d for d in dias if not d.get("is_rest")]
    print(f"\n-- El plan: {len(dias)} días, {len(entrenos)} con entrenamiento --")

    # LO QUE EL ATLETA PIDIO, EN EL PLAN QUE SALIO.
    #
    # Faltaba, y por eso este script dio "todo pasó" sobre una semana en la
    # que el `fijo` de natación del martes se había convertido en descanso.
    # Comprobar que la petición llega al PROMPT no es comprobar que se cumple:
    # entre el prompt y el plan estan el formateador y el validador, y fue el
    # validador quien la borro. Ver tests/test_la_rejilla_manda.py.
    fijos = _fijos_del_prompt(legible)
    if fijos:
        por_dia: dict[str, set] = {}
        for d in dias:
            if not d.get("is_rest"):
                por_dia.setdefault(d.get("day_name_es") or "", set()).add(d.get("sport"))
        for dia, deportes in sorted(fijos.items()):
            puestos = por_dia.get(dia, set())
            faltan = [s for s in deportes if s not in puestos]
            check(f"el `fijo` de {dia} ({', '.join(deportes)}) está en el plan",
                  not faltan,
                  f"ese día salio: {sorted(puestos) or 'descanso'}")
    else:
        print("  --   el perfil no tiene rejilla, no hay peticiones que comprobar")

    check("ningún día es un brick",
          all(d.get("sport") != "brick" for d in dias),
          f"deportes: {sorted({d.get('sport') for d in dias})}")

    pasos = [p for d in dias for p in (d.get("steps") or [])]
    def todos_los_pasos(ps):
        for p in ps:
            yield p
            yield from todos_los_pasos(p.get("substeps") or [])
    pasos = list(todos_los_pasos(pasos))
    de_pulso = [p for p in pasos if p.get("target_type") == "hr_zone"]
    check("todo paso de pulso lleva UNA zona y ningún rango de bpm",
          all(p.get("target_zone") and p.get("target_low") is None for p in de_pulso),
          f"{len(de_pulso)} pasos de pulso")

    multi = [p for p in pasos if "Z1-Z2" in str(p.get("description") or "")
             and p.get("target_type") == "hr_zone"]
    check("ningún paso descrito Z1-Z2 lleva una zona única forzada",
          not multi,
          f"{len(multi)} sospechosos" if multi else "")

    # `.get(clave, {})` NO cubre una clave presente con valor None, que es como
    # viene un dia sin bloque de fuerza. El mismo fallo que este script existe
    # para cazar, cometido dentro del script. Ver test_distancia_de_triatlon.
    bloque = lambda d: d.get("strength_block") or {}          # noqa: E731
    fuerza = [d for d in dias if bloque(d).get("exercises")]
    if fuerza:
        ejercicios = [e for d in fuerza for e in bloque(d)["exercises"]]
        resueltos = [e for e in ejercicios if resolver(e.get("name"), e.get("video_key"))]
        check("las sesiones de fuerza tienen 4 ejercicios o más",
              all(len(bloque(d)["exercises"]) >= 4 for d in fuerza),
              f"{[len(bloque(d)['exercises']) for d in fuerza]}")
        check("la mayoría de ejercicios resuelve a una imagen",
              len(resueltos) >= len(ejercicios) * 0.6,
              f"{len(resueltos)}/{len(ejercicios)}")
        sin_resolver = [e.get("name") for e in ejercicios
                        if not resolver(e.get("name"), e.get("video_key"))]
        if sin_resolver:
            print(f"       sin imagen: {sin_resolver}")
    else:
        print("  --   no hubo sesiones de fuerza esta semana")

    print()
    if fallos:
        print(f"{len(fallos)} comprobación(es) fallaron: {fallos}")
        return 1
    print("Todo lo comprobable pasó.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1] if len(sys.argv) > 1 else "u2"))
