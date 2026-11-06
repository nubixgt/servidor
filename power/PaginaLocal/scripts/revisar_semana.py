"""Qué trae de verdad la semana recién generada, y qué llegará al reloj.

Mirar el plan en la pantalla dice si hay una sesión de trail. No dice si esa
sesión lleva `leg_sport`, ni si los ejercicios de fuerza traen la categoría que
enciende la animación del reloj, ni si la natación va por distancia o como un
bloque continuo. Eso son campos, y esta herramienta los lee.

Existe porque probar una versión a ojo es cómo se cuelan los fallos de esta
familia: la semana **parece** correcta y lo que le llega al atleta no lo es.

    python scripts/revisar_semana.py                    # el perfil por defecto
    python scripts/revisar_semana.py "Jose Ines Castillo"
"""

from __future__ import annotations

import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

# La consola de Windows llega en cp1252 y revienta con cualquier caracter fuera
# de ella. Paso: una flecha en un mensaje de progreso tiro el banco de pruebas
# entero con UnicodeEncodeError -- justo antes de la llamada, asi que no costo
# dinero, pero si costo la corrida. Una herramienta de diagnostico que se cae
# por como imprime no diagnostica nada.
for _flujo in (sys.stdout, sys.stderr):
    try:
        _flujo.reconfigure(encoding="utf-8", errors="replace")
    except (AttributeError, ValueError):
        pass

from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload  # noqa: E402
from services.garmin.garmin_vocabulary import (  # noqa: E402
    normalise_category,
    verified_exercise,
)

OK, MAL, AVISO = "OK ", "NO ", "-- "


def _storage_dir(nombre: str) -> Path:
    from web_app.server import _profile_storage_dir

    return _profile_storage_dir(nombre)


def _pasos(pasos):
    """Todos los pasos, incluidos los de dentro de un bloque de repetición."""
    for p in pasos:
        yield p
        yield from _pasos(p.substeps or [])


def revisar(nombre: str) -> int:
    plan_path = _storage_dir(nombre) / "weekly_plan.json"
    if not plan_path.exists():
        print(f"No hay plan generado para {nombre!r}.")
        return 1
    payload = WeeklyPlanPayload.model_validate(json.loads(plan_path.read_text(encoding="utf-8")))
    dias = [d for w in payload.weeks for d in w.days if not d.is_rest]

    print(f"\n=== Semana de {nombre}: {len(dias)} sesiones ===\n")
    problemas = 0

    for dia in dias:
        marca = f" [{dia.session_slot}]" if dia.session_slot != "unica" else ""
        bloque = f" franja {dia.block_index}" if dia.block_index is not None else ""
        print(f"{dia.date}{marca}{bloque}  {dia.sport}/{dia.discipline}  "
              f"{dia.intensity_class}  {dia.total_duration_minutes or 0:.0f}min  — {dia.focus}")

        # --- Fuerza: ¿llega al reloj con sus ejercicios y su animación?
        if dia.sport == "strength":
            bloque_fuerza = dia.strength_block
            ejercicios = (bloque_fuerza.exercises if bloque_fuerza else []) or []
            if not ejercicios:
                print(f"   {MAL}sin desglose: subirá como un bloque en blanco")
                problemas += 1
            for e in ejercicios:
                cat = normalise_category(e.garmin_category)
                anim = verified_exercise(cat, e.video_key)
                estado = OK if anim else (AVISO if cat else MAL)
                detalle = (f"animación {anim}" if anim
                           else f"categoría {cat}, sin animación" if cat
                           else "sin categoría: llegará como un bloque sin nombre")
                print(f"   {estado}{e.name} — {e.sets}x{e.reps} · {detalle}")
                if not cat:
                    problemas += 1

        # --- Brick: ¿sabe de qué tramo es cada paso?
        if dia.sport == "brick" or dia.discipline == "brick":
            tramos = [p.leg_sport for p in _pasos(dia.steps) if p.leg_sport]
            if len(set(tramos)) >= 2:
                print(f"   {OK}brick con tramos: {' → '.join(dict.fromkeys(tramos))}")
            else:
                print(f"   {MAL}brick sin `leg_sport`: no puede subir al reloj, irá escrito")
                problemas += 1

        # --- Natación: ¿por distancia y con estilo?
        if dia.sport == "swim":
            por_distancia = [p for p in _pasos(dia.steps) if p.duration_type == "distance"]
            con_estilo = [p for p in _pasos(dia.steps) if p.stroke]
            print(f"   {OK if por_distancia else MAL}pasos por distancia: {len(por_distancia)}")
            series = [p for p in dia.steps if p.substeps or (p.repeat_count or 1) > 1]
            print(f"   {OK if series else AVISO}series con repeticiones: {len(series)}")
            print(f"   {OK if con_estilo else AVISO}pasos con estilo declarado: {len(con_estilo)}")
            if not por_distancia:
                problemas += 1
            # Una serie escrita como bloque continuo pierde las repeticiones.
            #
            # Sólo se miran los pasos de PRIMER NIVEL. Mirar también los de
            # dentro de un bloque acusaba a series que estaban perfectas: el
            # hijo de un `6x50m` hereda el texto del padre y, visto suelto,
            # parece una serie escrita en plano. Lo dijo tres generaciones
            # seguidas de una natación que Garmin mostraba con sus seis
            # repeticiones -- una herramienta de verificación que da falsos
            # positivos es peor que no tenerla, porque manda a arreglar lo que
            # no está roto.
            for p in dia.steps:
                texto = (p.description or "").lower()
                if "x" in texto[:8] and not p.substeps and (p.repeat_count or 1) < 2:
                    print(f"   {AVISO}«{p.description[:50]}» va como bloque continuo, sin repeticiones")

        # --- Trail: ¿se prescribió por esfuerzo, no por ritmo?
        if dia.discipline == "trail":
            con_ritmo = [p for p in _pasos(dia.steps) if p.target_type == "pace"]
            print(f"   {OK if not con_ritmo else AVISO}trail: "
                  + ("por esfuerzo/zona, como toca" if not con_ritmo
                     else f"{len(con_ritmo)} paso(s) con banda de ritmo — revisa si el terreno lo justifica"))

        if not dia.garmin_exportable:
            print(f"   {AVISO}no va al reloj: se entrega escrita")

    presentes = {d.discipline for d in dias} | {d.sport for d in dias}
    print("\n--- Caminos ejercitados esta semana ---")
    for etiqueta, clave in (("Trail", "trail"), ("Natación", "swim"),
                            ("Brick", "brick"), ("Fuerza", "strength")):
        print(f"  {OK if clave in presentes else MAL}{etiqueta}")

    print(f"\n{problemas} punto(s) que revisar.\n")
    return 0


if __name__ == "__main__":
    sys.exit(revisar(sys.argv[1] if len(sys.argv) > 1 else "Jose Ines Castillo"))
