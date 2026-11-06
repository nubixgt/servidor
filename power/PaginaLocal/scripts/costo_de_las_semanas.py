"""¿Cuánto cuesta la semana de cada atleta, y por qué? Sólo lee: ni modelos, ni Garmin, ni escribe nada.

Lo pidió el responsable (2026-10-23): la semana de Fernando costaba 0,55 USD y la de los demás ~0,32. Junta
dos fuentes:

- **El libro de costos** (`data/billing/usage.jsonl`): todas las generaciones desde que existe, con el costo
  por parte (resumidores, preguntas, expertos, la semana). Responde «¿le pasa todas las semanas?». Antes del
  2026-09-23 la tabla cobraba gpt-5.2 a la mitad: esas líneas se enseñan, pero no se comparan.
- **Las trazas** (`<storage>/u<id>/reasoning/<ISO>/prompts/index.json`): llamada a llamada, cuántas veces se
  le DEVOLVIÓ la decisión al modelo (una segunda llamada pagada a gpt-5.2), cuánto mide su prompt y las horas
  de la semana que escribió.

Con `--detalle u13` enseña además, semana a semana, lo de ese atleta: sus horas por deporte, el porqué de
la semana, por qué se le devolvió la decisión y el bloque de lo que pidió. Lleva datos suyos: en
producción, sólo con permiso del responsable para ese atleta.

    python scripts/costo_de_las_semanas.py --storage ../AiService/_runtime/storage
    python scripts/costo_de_las_semanas.py --storage /home/visionwe/aiservice/app/_runtime/storage --detalle u13
"""

import argparse
import json
import logging
import statistics
import sys
from collections import defaultdict
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(RAIZ))
logging.disable(logging.WARNING)

from services.ai.model_config import ModelSelector  # noqa: E402

# Desde aquí la tabla de precios cobra gpt-5.2 a su precio real (tests/test_precio_y_kilometros.py).
PRECIOS_BUENOS_DESDE = "2026-09-23"


def _parte(nodo: str) -> str:
    if "summarizer" in nodo:
        return "resumidores"
    if "pregunta" in nodo:
        return "preguntas"
    if "expert" in nodo or nodo in ("metrics", "physiology", "activity"):
        return "expertos"
    if nodo == "weekly_planner":
        return "semana"
    return "otros"


def libro(ruta: Path) -> dict:
    """Las semanas del libro, por atleta: [{ts, costo, partes, detalle}]."""
    por_atleta = defaultdict(list)
    if not ruta.exists():
        print(f"(no encuentro el libro en {ruta})")
        return por_atleta
    for linea in ruta.read_text(encoding="utf-8").splitlines():
        try:
            r = json.loads(linea)
        except json.JSONDecodeError:
            continue
        if r.get("kind") != "week":
            continue
        partes = defaultdict(float)
        for n in r.get("nodes") or []:
            partes[_parte(str(n.get("name") or ""))] += float(n.get("cost_usd") or 0)
        quien = f"{r.get('user_id') or '?'} {r.get('profile_label') or r.get('profile') or ''}".strip()
        por_atleta[quien].append({"ts": str(r.get("ts") or ""), "costo": float(r.get("cost_usd") or 0),
                                  "tokens": int(r.get("tokens") or 0), "partes": dict(partes),
                                  "detalle": r.get("detail") or ""})
    return por_atleta


def _texto(mensaje) -> str:
    c = mensaje.get("content") if isinstance(mensaje, dict) else ""
    return c if isinstance(c, str) else json.dumps(c, ensure_ascii=False)


def _mensajes(traza: Path, llamada: dict) -> list:
    archivo = traza / "prompts" / f"{llamada.get('call_id')}.json"
    if not archivo.exists():
        return []
    try:
        return json.loads(archivo.read_text(encoding="utf-8")).get("messages") or []
    except json.JSONDecodeError:
        return []


# La vuelta de la decisión (`mensaje_de_correccion`) pega la decisión anterior en un mensaje del usuario,
# después de lo que hay que corregir. Así se reconoce en las trazas (Fernando, 2026-10-18).
_DEVUELTA = "Tu decisión anterior:"


def _correccion(mensajes: list) -> str:
    """Lo que se le dijo al devolverle la decisión, o "" si no es una decisión devuelta."""
    for m in reversed(mensajes):
        texto = _texto(m)
        if _DEVUELTA in texto:
            return texto.split(_DEVUELTA, 1)[0].strip()
    return ""


def _que_es(traza: Path, llamada: dict, primera: list | None) -> str:
    """Qué fue una llamada de la semana: decision | devuelta | fuerza | reparacion | codificador.

    En las trazas la semana entera se llama `weekly_planner`. La primera llamada a gpt-5 es la decisión; la
    DEVUELTA lleva la decisión anterior pegada («Tu decisión anterior:»); el paso de fuerza empieza por su
    programa; lo demás después de la decisión es una reparación (tier 2).
    """
    modelo = str(llamada.get("model") or "")
    if modelo.startswith("claude-haiku"):
        return "codificador"
    if modelo.startswith("gpt-6-luna"):
        return "fuerza"
    mensajes = _mensajes(traza, llamada)
    if primera is None:
        return "decision"
    if mensajes and "programa de fuerza" in _texto(mensajes[-1])[:400].lower():
        return "fuerza"
    if _correccion(mensajes):
        return "devuelta"
    return "reparacion"


def trazas(storage: Path) -> dict:
    """Cada semana archivada, por atleta: las llamadas, cuántas decisiones y las horas."""
    por_atleta = defaultdict(list)
    for traza in sorted(storage.glob("u*/reasoning/*")):
        intenciones = traza / "week_intents.json"
        indice = traza / "prompts" / "index.json"
        if not intenciones.exists():
            continue   # es un macrociclo
        llamadas = []
        if indice.exists():
            try:
                llamadas = json.loads(indice.read_text(encoding="utf-8")).get("calls") or []
            except json.JSONDecodeError:
                llamadas = []
        costo = 0.0
        por_tipo = defaultdict(float)
        decisiones = reparaciones = 0
        prompt_decision = salida_decision = 0
        primera = None   # los mensajes de la primera decisión
        for c in llamadas:
            u = c.get("usage") or {}
            det = u.get("input_token_details") or {}
            gasto = ModelSelector.estimate_cost_usd(
                str(c.get("model") or ""), int(u.get("input_tokens") or 0), int(u.get("output_tokens") or 0),
                cache_read_tokens=int(det.get("cache_read") or 0), cache_creation_tokens=int(det.get("cache_creation") or 0))
            costo += gasto
            if c.get("node") != "weekly_planner":
                por_tipo[_parte(str(c.get("node") or ""))] += gasto
                continue
            tipo = _que_es(traza, c, primera)
            if tipo == "decision":
                primera = _mensajes(traza, c)
            por_tipo[tipo] += gasto
            if tipo in ("decision", "devuelta"):
                decisiones += 1
                prompt_decision = max(prompt_decision, int(c.get("prompt_chars") or 0))
                salida_decision += int(u.get("output_tokens") or 0)
            elif tipo == "reparacion":
                reparaciones += 1
        horas = defaultdict(float)
        try:
            semanas = json.loads(intenciones.read_text(encoding="utf-8"))
            for w in semanas if isinstance(semanas, list) else [semanas]:
                for d in w.get("days") or []:
                    if not d.get("is_rest"):
                        horas[str(d.get("sport") or "?")] += float(d.get("total_duration_minutes") or 0) / 60
        except (json.JSONDecodeError, TypeError, ValueError):
            pass
        por_atleta[traza.parent.parent.name].append({
            "traza": traza.name, "costo": costo, "por_tipo": dict(por_tipo), "llamadas": len(llamadas),
            "decisiones": decisiones,
            "reparaciones": reparaciones, "prompt_decision": prompt_decision, "salida_decision": salida_decision,
            "horas": dict(horas), "ruta": traza,
        })
    return por_atleta


def _horas(h: dict) -> str:
    total = sum(h.values())
    resistencia = sum(v for k, v in h.items() if k != "strength")
    return f"{total:4.1f} h ({resistencia:.1f} de resistencia)"


def detalle(atleta: str, semanas: list) -> None:
    print(f"\n## Detalle de {atleta}\n")
    for s in semanas:
        print(f"### {s['traza']} · {_horas(s['horas'])} · decisiones: {s['decisiones']} · ${s['costo']:.3f}")
        print("   por deporte:", ", ".join(f"{k} {v:.1f} h" for k, v in sorted(s["horas"].items())))
        try:
            w = json.loads((s["ruta"] / "week_intents.json").read_text(encoding="utf-8"))
            w = w[0] if isinstance(w, list) else w
            print("   porqué:", (w.get("week_rationale") or "").replace("\n", " | ")[:900])
        except (OSError, json.JSONDecodeError, IndexError, KeyError):
            pass
        print("   costo por parte:", ", ".join(f"{k} ${v:.3f}" for k, v in sorted(s["por_tipo"].items())))
        decisiones = sorted((s["ruta"] / "prompts").glob("*__weekly_planner__gpt-5*.json"))
        for i, f in enumerate(decisiones):
            try:
                mensajes = json.loads(f.read_text(encoding="utf-8")).get("messages") or []
            except json.JSONDecodeError:
                continue
            if i == 0:
                todo = "\n".join(_texto(m) for m in mensajes)
                for marca in ("### Lo que el atleta pide de su entrenamiento", "### Carga reciente"):
                    p = todo.find(marca)
                    if p >= 0:
                        print(f"   [{marca}] …", todo[p:p + 1600].replace("\n", " | "))
            elif correccion := _correccion(mensajes):
                print(f"   devuelta ({f.name}):", correccion.replace("\n", " | ")[:1500])
        print()


def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    ap.add_argument("--libro", type=Path, default=RAIZ / "data" / "billing" / "usage.jsonl")
    ap.add_argument("--storage", type=Path, default=RAIZ.parent / "AiService" / "_runtime" / "storage")
    ap.add_argument("--detalle", action="append", default=[], help="u<id> de un atleta (con permiso)")
    args = ap.parse_args()

    print("# Costo de las semanas por atleta\n")
    print(f"## Del libro de costos (sólo desde el {PRECIOS_BUENOS_DESDE}: antes la tabla cobraba gpt-5.2 a la mitad)\n")
    print(f"{'atleta':32s} {'semanas':>7s} {'media':>7s} {'mín':>6s} {'máx':>6s} | "
          f"{'resum.':>6s} {'pregun.':>7s} {'expert.':>7s} {'semana':>7s}")
    for quien, filas in sorted(libro(args.libro).items()):
        buenas = [f for f in filas if f["ts"][:10] >= PRECIOS_BUENOS_DESDE]
        if not buenas:
            continue
        costos = [f["costo"] for f in buenas]

        def media(parte, b=buenas):
            return statistics.mean(f["partes"].get(parte, 0.0) for f in b)
        print(f"{quien[:32]:32s} {len(buenas):>7d} {statistics.mean(costos):>7.3f} {min(costos):>6.3f} "
              f"{max(costos):>6.3f} | {media('resumidores'):>6.3f} {media('preguntas'):>7.3f} "
              f"{media('expertos'):>7.3f} {media('semana'):>7.3f}")
        for f in buenas:
            print(f"    {f['ts'][:16]}  ${f['costo']:.3f}  {f['tokens']:>7d} tokens"
                  + (f"  ({f['detalle']})" if f["detalle"] else ""))

    print("\n## De las trazas (las que el archivo conserva)\n")
    print(f"{'atleta':8s} {'traza':20s} {'costo':>7s} {'decis.':>6s} {'prompt':>7s} {'salida':>6s}  horas")
    todas = trazas(args.storage)
    for atleta, semanas in sorted(todas.items()):
        for s in semanas:
            print(f"{atleta:8s} {s['traza']:20s} {s['costo']:>7.3f} {s['decisiones']:>6d} "
                  f"{s['prompt_decision']:>7d} {s['salida_decision']:>6d}  {_horas(s['horas'])}")
    for atleta in args.detalle:
        detalle(atleta, todas.get(atleta, []))


if __name__ == "__main__":
    main()
