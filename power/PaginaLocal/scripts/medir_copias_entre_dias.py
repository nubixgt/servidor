"""¿Copia el codificador pasos de un día a otro? (Beta 3.0, F10 / fase C: «codificar por día»).

El plan pide codificar día a día SÓLO si el codificador copia pasos entre días: dos sesiones del mismo
deporte con decisiones distintas y pasos codificados idénticos. Se midió el 2026-10-19 sobre las 5
semanas archivadas en local (decisión contra Haiku, atletas 2 y 3): 15 pares, 0 copias. Este script
repite esa medición sobre cualquier raíz de almacenamiento (la de producción, tras relanzar, con permiso).

Sin modelos y sin escribir nada. Cada decisión (`reasoning/<ISO>/week_intents.json`) se empareja con su
día codificado (`weekly_plan.json` y `history/*/weekly_plan.json`) por fecha, franja y `purpose`: el
codificador copia el propósito de la decisión (`_apply_discipline`), así que un día regenerado después
no se toma por el de otra decisión.

    python scripts/medir_copias_entre_dias.py ../AiService/_runtime/storage
"""

from __future__ import annotations

import json
import sys
from itertools import combinations
from pathlib import Path


def _leer(path: Path):
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return None


def _dias_de(payload) -> list[dict]:
    semanas = payload if isinstance(payload, list) else (payload or {}).get("weeks") or []
    return [d for s in semanas if isinstance(s, dict) for d in (s.get("days") or []) if isinstance(d, dict)]


def _firma(pasos) -> str:
    """Los pasos sin el texto: lo que va al reloj."""
    def limpio(p):
        return {k: v for k, v in sorted(p.items()) if k not in ("description", "step_order", "notes")
                and v not in (None, "", [], {})} | {"substeps": [limpio(s) for s in p.get("substeps") or []]}
    return json.dumps([limpio(p) for p in pasos or [] if isinstance(p, dict)], sort_keys=True, default=str)


def medir(raiz: Path) -> dict:
    pares, copias, ejemplos = 0, 0, []
    for atleta in sorted(p for p in raiz.iterdir() if p.is_dir()):
        codificados = {}
        for plan in [atleta / "weekly_plan.json", *sorted(atleta.glob("history/*/weekly_plan.json"))]:
            for d in _dias_de(_leer(plan)):
                codificados.setdefault((d.get("date"), d.get("session_slot"), d.get("purpose")), d)
        for intents in sorted(atleta.glob("reasoning/*/week_intents.json")):
            decididos = [d for d in _dias_de(_leer(intents)) if not d.get("is_rest") and d.get("sport") != "strength"]
            emparejados = []
            for dec in decididos:
                cod = codificados.get((dec.get("date"), dec.get("session_slot"), dec.get("purpose")))
                if cod and cod.get("steps"):
                    emparejados.append((dec, cod))
            for (d1, c1), (d2, c2) in combinations(emparejados, 2):
                if d1.get("sport") != d2.get("sport"):
                    continue
                pares += 1
                if d1.get("session_sketch") != d2.get("session_sketch") and _firma(c1["steps"]) == _firma(c2["steps"]):
                    copias += 1
                    ejemplos.append(f"{atleta.name} {intents.parent.name}: {d1.get('date')} y {d2.get('date')} "
                                    f"({d1.get('sport')}) con los mismos pasos")
    return {"pares": pares, "copias": copias, "ejemplos": ejemplos[:10]}


if __name__ == "__main__":
    raiz = Path(sys.argv[1] if len(sys.argv) > 1 else "../AiService/_runtime/storage")
    r = medir(raiz)
    print(f"{r['pares']} pares de sesiones del mismo deporte; {r['copias']} con los pasos copiados.")
    for e in r["ejemplos"]:
        print(" -", e)
