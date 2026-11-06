"""Cruza una consulta de «Pedir ayuda» con la traza archivada del atleta (Beta 3.0, F3).

Primero `php Backend/scripts/exportar_incidencia.php <id>` (en producción, sólo con permiso
del responsable para ESA incidencia). Esto lee el JSON y, sin IA ni red:

1. dice qué traza de razonamiento tiene la sesión (`reasoning/<ISO>/week_intents.json`) y
   qué prompts archivados entran en juego (`prompts/*.json`), que es donde se mira primero
   (01: causa raíz, no parche);
2. pone juntos lo decidido, lo codificado y lo que había en el reloj, con el triaje;
3. propone el esqueleto de la prueba de regresión con los números reales en el docstring
   (01: cada fallo encontrado deja su prueba).

Uso:
    python PaginaLocal/scripts/analizar_incidencia.py incidencia_42.json
    python PaginaLocal/scripts/analizar_incidencia.py incidencia_42.json --storage AiService/_runtime/storage
"""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]


def trazas_con_la_sesion(dir_atleta: Path, fecha: str) -> list[Path]:
    raiz = dir_atleta / "reasoning"
    if not raiz.is_dir():
        return []
    salida = []
    for traza in sorted((d for d in raiz.iterdir() if d.is_dir()), key=lambda d: d.name, reverse=True):
        try:
            semanas = json.loads((traza / "week_intents.json").read_text(encoding="utf-8"))
        except (OSError, ValueError):
            continue
        if any(d.get("date") == fecha for s in semanas if isinstance(s, dict) for d in s.get("days") or []):
            salida.append(traza)
    return salida


def prompts_de(traza: Path) -> list[str]:
    carpeta = traza / "prompts"
    if not carpeta.is_dir():
        return []
    return [f"{p.name} ({p.stat().st_size // 1024} KB)" for p in sorted(carpeta.glob("*.json"))]


def esqueleto_de_prueba(inc: dict, sesion: dict | None) -> str:
    origen = (inc.get("diagnostic") or {}).get("triaje", {}).get("origen", "desconocido")
    datos = []
    if sesion:
        for etiqueta, parte in (("decidido", sesion.get("decidido")), ("codificado", sesion.get("codificado")),
                                ("reloj", sesion.get("reloj"))):
            if parte:
                datos.append(f"        {etiqueta}: {json.dumps(parte, ensure_ascii=False)}")
    nombre = f"test_incidencia_{inc.get('id')}_{origen}"
    return (
        f'    def {nombre}(self):\n'
        f'        """Consulta de ayuda {inc.get("id")} (atleta {inc.get("user_id")}, {inc.get("created_at")}):\n'
        f'        «{str(inc.get("message") or "")[:160]}»\n'
        f'        Sesión {inc.get("session_key") or "—"}; origen propuesto: {origen}.\n'
        + ("\n".join(datos) + "\n" if datos else "")
        + '        Qué falló de verdad y dónde se arregló: ESCRIBIRLO aquí antes de cerrar la consulta."""\n'
        + '        self.fail("Reproduce aquí el caso con estos números")\n'
    )


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("incidencia", type=Path)
    ap.add_argument("--storage", type=Path, default=RAIZ / "AiService" / "_runtime" / "storage")
    args = ap.parse_args()

    inc = json.loads(args.incidencia.read_text(encoding="utf-8"))
    diag = inc.get("diagnostic") or {}
    sesion = diag.get("sesion")
    print(f"Consulta {inc.get('id')} · atleta {inc.get('user_id')} · {inc.get('category')} · {inc.get('status')}")
    print(f"«{inc.get('message')}»\n")
    triaje = diag.get("triaje") or {}
    print(f"Origen propuesto: {triaje.get('origen', '—')}")
    for origen, senales in (triaje.get("motivos") or {}).items():
        for s in senales:
            print(f"  [{origen}] {s}")
    if diag.get("_recortado"):
        print(f"\nOjo, el diagnóstico se recortó: {diag['_recortado']}")

    dir_atleta = args.storage / f"u{inc.get('user_id')}"
    if sesion:
        print(f"\nSesión {sesion.get('fecha')} ({sesion.get('franja')}):")
        for etiqueta in ("decidido", "codificado", "reloj"):
            print(f"  {etiqueta}: {json.dumps(sesion.get(etiqueta), ensure_ascii=False)}")
        trazas = trazas_con_la_sesion(dir_atleta, sesion.get("fecha", ""))
        if not trazas:
            print(f"\nNo hay ninguna traza con esa fecha en {dir_atleta / 'reasoning'}.")
        for traza in trazas[:2]:
            print(f"\nTraza {traza.name} — los prompts que recibió cada modelo:")
            for p in prompts_de(traza):
                print(f"  {p}")
    else:
        print("\nLa consulta no se hizo desde una sesión: mira sus últimas generaciones en el diagnóstico.")

    print("\nEsqueleto de la prueba de regresión (con los números reales):\n")
    print(esqueleto_de_prueba(inc, sesion))
    return 0


if __name__ == "__main__":
    sys.exit(main())
