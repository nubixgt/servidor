"""Sonnet 5 contra GPT-5, en el chat, con el contexto real de un atleta.

Se corre UNA conversacion de tres preguntas por modelo, en orden, para que la
segunda y la tercera puedan aprovechar cache. Las preguntas estan elegidas para
tocar las tres reglas que mas cuestan de sostener en ese prompt:

  1. Explicar fisiologia de su PROPIO conocimiento sin inventarle datos.
  2. Contestar POR QUE la semana es como es con la restriccion declarada, no
     con una teoria fisiologica que suena mejor.
  3. Dar un numero del atleta que si esta en el contexto (kilometraje).

Correr:  python scripts/banco_chat.py [carpeta_del_atleta]   (por defecto u2)
"""

import asyncio
import json
import sys
from pathlib import Path
from unittest.mock import patch

# Igual que el resto de scripts de esta carpeta: la raiz sale del propio
# fichero. La primera version llevaba la ruta absoluta de la maquina donde se
# escribio, que es la clase de detalle que hace que un script "que ya existe"
# no sirva la segunda vez que alguien lo necesita.
RAIZ = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(RAIZ))
sys.stdout.reconfigure(encoding="utf-8")

from services.ai.model_config import ModelSelector  # noqa: E402

# El atleta contra el que se banca. Va como argumento porque el contexto de
# CADA atleta es distinto -- uno sin plan generado tiene ~4.000 tokens y uno
# con temporada y semana tiene ~23.000 -- y bancar contra el pequeno mide
# otra cosa.
ATLETA = sys.argv[1] if len(sys.argv) > 1 else "u2"
STORAGE = RAIZ.parent / "AiService" / "_runtime" / "storage" / ATLETA
PERFIL = {"name": "Atleta", "gender": "male", "birth_date": "1992-03-15"}

PREGUNTAS = [
    "Para que sirve una sesion de VO2max y que adaptacion busca?",
    "Por que mi semana esta planteada asi?",
    "Cuantos km he corrido en los ultimos 30 dias?",
]

MODELOS = ["claude-sonnet", "gpt-5-mid"]


async def una_conversacion(clave: str) -> dict:
    from services.ai import coach_chat as cc

    real = ModelSelector.get_llm_by_role_and_name

    def forzado(role, _nombre=None):
        return real(role, clave)

    resultados, historial = [], []
    with patch.object(ModelSelector, "get_llm", side_effect=lambda role: forzado(role)):
        for i, q in enumerate(PREGUNTAS, 1):
            r = await cc.ask_coach(STORAGE, PERFIL, q, chat_history=historial, coach_id="ben")
            fila = {"n": i, "pregunta": q, "respuesta": r["reply"]}
            for modelo, u in (r["usage_metadata"] or {}).items():
                det = u.get("input_token_details") or {}
                cache_read = int(det.get("cache_read") or 0)
                cache_write = int(det.get("cache_creation") or 0) or int(
                    det.get("ephemeral_5m_input_tokens") or 0
                )
                fila.update({
                    "modelo": modelo,
                    "in": int(u.get("input_tokens") or 0),
                    "out": int(u.get("output_tokens") or 0),
                    "razonamiento": int((u.get("output_token_details") or {}).get("reasoning") or 0),
                    "cache_read": cache_read,
                    "cache_write": cache_write,
                    "detalles_crudos": det,
                    "coste": ModelSelector.estimate_cost_usd(
                        modelo, int(u.get("input_tokens") or 0), int(u.get("output_tokens") or 0),
                        cache_read_tokens=cache_read, cache_creation_tokens=cache_write,
                    ),
                })
            resultados.append(fila)
            historial += [{"role": "user", "content": q},
                          {"role": "assistant", "content": r["reply"]}]
    return {"clave": clave, "turnos": resultados}


async def main():
    todo = []
    for clave in MODELOS:
        print(f"\n{'='*70}\n  {clave}\n{'='*70}")
        try:
            res = await una_conversacion(clave)
        except Exception as exc:  # noqa: BLE001
            print(f"  FALLO: {type(exc).__name__}: {exc}")
            todo.append({"clave": clave, "error": f"{type(exc).__name__}: {exc}"})
            continue
        todo.append(res)
        total = 0.0
        for t in res["turnos"]:
            total += t.get("coste", 0.0)
            print(f"  {t['n']}. in={t.get('in',0):>7,} out={t.get('out',0):>5,} "
                  f"razon={t.get('razonamiento',0):>5,} "
                  f"cache_r={t.get('cache_read',0):>7,} cache_w={t.get('cache_write',0):>7,} "
                  f"${t.get('coste',0):.5f}")
        print(f"  TOTAL conversacion: ${total:.5f}")

    salida = Path(__file__).with_name("banco_chat_resultado.json")
    salida.write_text(json.dumps(todo, ensure_ascii=False, indent=1), encoding="utf-8")
    print(f"\nRespuestas completas en {salida}")


asyncio.run(main())
