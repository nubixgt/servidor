"""Banco del codificador: varios modelos sobre las semanas REALES archivadas, con las instrucciones de HOY.

Cada semana se reconstruye desde su decisión y su ficha (`scripts/prompts_del_codificador.py`): así
prueba el prompt actual y no sólo las pocas semanas cuyo prompt se archivó. Cada salida se mide contra la
decisión de su semana. Cuesta dinero (2026-10-19: ~0,027 USD por semana con Haiku 4.5 y ~0,003 con
Haiku 5.5): avisar antes.

    python scripts/banco_codificador.py claude-haiku claude-haiku-55 claude-haiku-55

Un modelo repetido corre dos veces: así se mide cuánto varía entre vueltas.
"""

import asyncio
import json
import re
import sys
import tempfile
import time
from collections import defaultdict
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(RAIZ / "PaginaLocal"))
sys.path.insert(0, str(RAIZ / "PaginaLocal" / "scripts"))
for _f in (sys.stdout, sys.stderr):
    _f.reconfigure(encoding="utf-8", errors="replace")

from dotenv import load_dotenv  # noqa: E402

load_dotenv(RAIZ / ".env")

import prompts_del_codificador as pc  # noqa: E402
from langchain_core.callbacks import get_usage_metadata_callback  # noqa: E402

from services.ai.ai_settings import AgentRole  # noqa: E402
from services.ai.langgraph.nodes.weekly_planner_node import _minutos_de_los_pasos  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    FormattedWeekBlock,
    expand_formatted_steps,
)
from services.ai.model_config import ModelSelector  # noqa: E402
from services.ai.replay import _cost_from_usage, unidades_que_no_cuadran  # noqa: E402

ST = RAIZ / "AiService/_runtime/storage"
SALIDA = Path(tempfile.gettempdir()) / "banco_codificador_salidas.json"
ADORNOS = re.compile(r"rect|stride|progresiv|aceleraci|cambios de ritmo", re.I)
CONDICIONAL = re.compile(r"\b(?:si (?:te |notas|vas|hay|tienes|el |la |aparece|sientes)[^.;:,)]*|opcional\w*)", re.I)
RAMPAS = {"warmup", "cooldown", "recovery"}


def semanas_del_banco() -> list[tuple[str, str, object, list[dict]]]:
    """La última decisión de cada semana de cada atleta, con el prompt de hoy."""
    por_semana = {}
    for f in sorted(ST.glob("u*/reasoning/*/week_intents.json")):
        try:
            for intent, mensajes in pc.semanas(f.parent, sin_fuerza=True):
                clave = (f.parent.parent.parent.name, min(d.date for d in intent.days))
                por_semana[clave] = (f.parent.name, intent, mensajes)
        except Exception as e:  # noqa: BLE001
            print("sin reconstruir:", f.parent.name, str(e)[:100])
    return [(u, ent, intent, msgs) for (u, _), (ent, intent, msgs) in sorted(por_semana.items())]


def _firma(p: dict) -> tuple:
    return tuple(p.get(k) for k in ("step_type", "duration_type", "duration_value", "repeat_count", "target_type",
                                     "target_zone", "target_low", "target_high", "recovery_duration_type",
                                     "recovery_duration_value"))


def medir(dias: list[dict], intent) -> dict:
    dec = {(d.date, d.session_slot or "unica"): d for d in intent.days if not d.is_rest}
    m = defaultdict(int)
    m["ejemplos"] = []
    vistos = set()
    for d in dias:
        if d.get("is_rest"):
            continue
        clave = (d.get("date"), d.get("session_slot") or "unica")
        vistos.add(clave)
        de = dec.get(clave)
        pasos = d.get("steps") or []
        if not pasos:
            m["sin_pasos"] += 1
            continue
        if de is None:
            continue
        m["sesiones"] += 1
        if de.sport != d.get("sport"):
            m["deporte_distinto"] += 1
            m["ejemplos"].append(f"{clave[0]} deporte {de.sport}→{d.get('sport')}")
        for p in pasos:
            m["pasos"] += 1
            if p.get("step_type") in RAMPAS and p.get("target_type") not in (None, "none"):
                m["rampa_con_objetivo"] += 1
            if d.get("sport") == "swim" and p.get("target_type") not in (None, "none"):
                m["natacion_con_objetivo"] += 1
        textos = " ".join(f"{p.get('description') or ''} {p.get('recovery_description') or ''}" for p in pasos).lower()
        for frase in CONDICIONAL.findall(de.session_sketch or ""):
            palabras = [w for w in re.findall(r"\w+", frase.lower()) if len(w) > 3][:3]
            if palabras and not all(w in textos for w in palabras):
                m["condicional_perdido"] += 1
                m["ejemplos"].append(f"{clave[0]}: falta «{frase.strip()[:60]}»")
        if ADORNOS.search(de.session_sketch or "") and not ADORNOS.search(textos):
            m["adornos_omitidos"] += 1
            m["ejemplos"].append(f"{clave[0]}: la decisión pide rectas/progresivos y no están")
        try:
            from services.ai.langgraph.schemas.plan_outputs import FormattedWorkoutStep
            minutos = _minutos_de_los_pasos(expand_formatted_steps([FormattedWorkoutStep.model_validate(p) for p in pasos]))
        except Exception:  # noqa: BLE001
            minutos = None
        previsto = float(de.total_duration_minutes or 0)
        if minutos is not None and previsto and abs(minutos - previsto) / previsto > 0.10:
            m["minutos_fuera_10pc"] += 1
            m["ejemplos"].append(f"{clave[0]} {d.get('sport')}: {minutos:.0f}' por {previsto:.0f}' decididos")
    m["faltan"] = len(set(dec) - vistos)
    u = unidades_que_no_cuadran(dias) or {"no_cuadran": 0, "ejemplos": []}
    m["unidades_mal"] = u["no_cuadran"]
    m["ejemplos"] += u["ejemplos"]
    return dict(m)


async def uno(sem, u, ent, intent, msgs, modelo, vuelta):
    async with sem:
        llm = ModelSelector.get_llm_by_role_and_name(AgentRole.WORKOUT_ENCODER, modelo).with_structured_output(
            FormattedWeekBlock)
        inicio, error, salida = time.perf_counter(), None, None
        with get_usage_metadata_callback() as cb:
            try:
                r = await llm.ainvoke(msgs)
                salida = r.model_dump(mode="json")
            except Exception as e:  # noqa: BLE001
                error = f"{type(e).__name__}: {str(e)[:300]}"
        return {"atleta": u, "entrada": ent, "modelo": modelo, "vuelta": vuelta, "error": error,
                "segundos": round(time.perf_counter() - inicio, 1), "costo": _cost_from_usage(cb.usage_metadata),
                "medida": medir(salida["days"], intent) if salida else None, "output": salida}


async def main(modelos: list[str]):
    semanas = semanas_del_banco()
    vueltas = defaultdict(int)
    plan = []
    for m in modelos:
        vueltas[m] += 1
        plan.append((m, vueltas[m]))
    sem = asyncio.Semaphore(6)
    res = await asyncio.gather(*[uno(sem, u, e, i, msgs, m, v) for u, e, i, msgs in semanas for m, v in plan])
    SALIDA.write_text(json.dumps(res, ensure_ascii=False, indent=1, default=str), encoding="utf-8")
    print(f"{len(semanas)} semanas; salidas en {SALIDA}\n")
    resumen(res)


def resumen(res: list[dict]) -> None:
    claves = ["sesiones", "pasos", "faltan", "sin_pasos", "deporte_distinto", "minutos_fuera_10pc", "unidades_mal",
              "adornos_omitidos", "condicional_perdido", "rampa_con_objetivo", "natacion_con_objetivo"]
    por_modelo = defaultdict(list)
    for r in res:
        por_modelo[r["modelo"]].append(r)
    for modelo, rs in por_modelo.items():
        ok = [r for r in rs if r["medida"]]
        suma = {k: sum(r["medida"].get(k, 0) for r in ok) for k in claves}
        costo = sum((r["costo"] or {}).get("cost_usd") or 0 for r in rs)
        print(f"== {modelo}: {len(ok)}/{len(rs)} válidas · {costo / max(1, len(rs)):.4f} USD/semana · "
              f"{sum(r['segundos'] for r in rs) / max(1, len(rs)):.1f} s")
        print("   " + " · ".join(f"{k}={v}" for k, v in suma.items()))
        for r in rs:
            if r["error"]:
                print("   ERROR", r["atleta"], r["entrada"][:10], r["error"][:150])
        # Lo que cambia entre dos vueltas del mismo modelo, día a día.
        vueltas = defaultdict(dict)
        for r in ok:
            vueltas[(r["atleta"], r["entrada"])][r["vuelta"]] = {
                (d["date"], d.get("session_slot")): [_firma(p) for p in d.get("steps") or []]
                for d in r["output"]["days"] if not d.get("is_rest")}
        iguales = total = 0
        for v in vueltas.values():
            if 1 in v and 2 in v:
                for k in v[1]:
                    total += 1
                    iguales += v[1][k] == v[2].get(k)
        if total:
            print(f"   entre sus dos vueltas: {iguales}/{total} sesiones idénticas al reloj")
        ejemplos = [e for r in ok for e in r["medida"].get("ejemplos", [])]
        for e in ejemplos[:10]:
            print("    -", e)


if __name__ == "__main__":
    if sys.argv[1:2] == ["--resumen"]:
        resumen(json.loads(SALIDA.read_text(encoding="utf-8")))
    else:
        asyncio.run(main(sys.argv[1:] or ["claude-haiku", "claude-haiku-55", "claude-haiku-55"]))
