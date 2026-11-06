"""¿Aceptaría el sistema esta semana? Sin gastar una generación.

El brick del domingo lo decidió bien el coach y lo tumbó el validador. Costó
$0.32 y una semana entera descubrirlo, y se podía haber sabido en un segundo:
la pregunta "¿acepta el sistema una sesión con esta forma para ESTE atleta?" no
necesita un modelo, necesita el validador y el constructor, que son código.

Esto arma sesiones sintéticas contra el perfil REAL del atleta -- su
disponibilidad, sus franjas, sus deportes permitidos -- y las pasa por las dos
puertas por las que tiene que pasar una sesión de verdad:

  1. `validate_weekly_plan`: ¿la deja pasar, o la convierte en descanso?
  2. `build_garmin_workout`: ¿se puede construir el entreno para el reloj?

No dice si el modelo VA a prescribirla. Dice si le dejaríamos.

    python scripts/probar_formas.py
    python scripts/probar_formas.py "Jose Ines Castillo"
"""

from __future__ import annotations

import sys
from datetime import date, timedelta
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

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
    is_garmin_exportable,
)
from services.ai.langgraph.validation.plan_validator import validate_weekly_plan  # noqa: E402
from services.garmin.workout_builder import (  # noqa: E402
    AthleteZoneConfig,
    UnsupportedSportError,
    build_garmin_workout,
)

OK, MAL = "OK ", "NO "
_DIAS_ES = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]


def _paso(descripcion, minutos, **extra):
    return WorkoutStep(
        step_order=extra.pop("step_order", 1), step_type=extra.pop("step_type", "interval"),
        duration_type="time", duration_value=minutos * 60, description=descripcion, **extra,
    )


def _dia(fecha, deporte, disciplina, focus, minutos, pasos, **extra):
    return WorkoutDay(
        date=fecha.isoformat(), day_name_es=_DIAS_ES[fecha.weekday()], is_rest=False,
        sport=deporte, discipline=disciplina, focus=focus,
        purpose=f"Prueba de forma: {focus}", adaptation="Si molesta, para.",
        total_duration_minutes=minutos, steps=pasos,
        garmin_exportable=is_garmin_exportable(disciplina, deporte, False, pasos),
        **extra,
    )


def _proximo(objetivo: int, desde: date) -> date:
    """El próximo día de la semana `objetivo` (0=lunes) a partir de `desde`."""
    return desde + timedelta(days=(objetivo - desde.weekday()) % 7 or 7)


def probar(nombre: str) -> int:
    import web_app.server as srv

    perfil = srv.load_profiles().get(nombre)
    if not perfil:
        print(f"No existe el perfil {nombre!r}.")
        return 1
    disponibilidad = srv.build_availability_constraints(perfil)
    zonas = AthleteZoneConfig(hr_zones={1: (123, 139), 2: (140, 153), 3: (154, 167),
                                        4: (168, 180), 5: (181, 200)})
    hoy = date.today()

    print(f"\n=== Formas que el sistema aceptaría para {nombre} ===")
    print("(contra su disponibilidad real; no dice si el modelo las prescribirá)\n")
    for dia_es in _DIAS_ES:
        d = srv._day_availability(perfil, dia_es)
        if not d["blocks"]:
            estado = f"{d['hours']}h sin horario" if dia_es in (perfil.get("available_days") or []) else "descanso"
            print(f"  {dia_es:<11} {estado}")
            continue
        for i, b in enumerate(d["blocks"]):
            print(f"  {dia_es:<11} franja {i}: {b['start']}-{b['end']} ({b['hours']}h)  "
                  f"{', '.join(b['sports']) if b['sports'] else 'cualquiera'}")

    casos: list[tuple[str, list[WorkoutDay]]] = []

    # 1) Brick de bici y carrera, en la franja larga.
    domingo = _proximo(6, hoy)
    casos.append(("Brick bici→carrera", [_dia(
        domingo, "brick", "brick", "Brick aeróbico", 110,
        [_paso("80' de bici a ritmo de carrera", 80, leg_sport="bike", target_type="hr_zone", target_zone=3),
         _paso("30' corriendo nada más bajar", 30, step_order=2, leg_sport="run",
               target_type="pace", target_low=400, target_high=430)],
        block_index=0)]))

    # 2) Dos sesiones distintas dentro de UNA franja.
    miercoles = _proximo(2, hoy)
    casos.append(("Correr + nadar en la misma franja", [
        _dia(miercoles, "run", "carrera", "Rodaje Z2", 45,
             [_paso("45' en Z2", 45, target_type="hr_zone", target_zone=2)],
             session_slot="am", block_index=0),
        _dia(miercoles, "swim", "natacion", "Técnica", 40,
             [_paso("40' de técnica", 40)], session_slot="pm", block_index=0),
    ]))

    # 3) Trail: tirada larga por montaña, por esfuerzo y desnivel.
    casos.append(("Tirada larga de trail", [_dia(
        domingo, "run", "trail", "Tirada larga por montaña", 120,
        [_paso("2h por sendero, 600m D+, en Z2 por esfuerzo", 120,
               target_type="hr_zone", target_zone=2)],
        intensity_class="long_run", block_index=0)]))

    # 4) Series en cuesta entre semana, sin montaña.
    jueves = _proximo(3, hoy)
    casos.append(("Series en cuesta (sin montaña)", [_dia(
        jueves, "run", "trail", "Cuestas", 50,
        [_paso("5x3' en cuesta fuerte, bajando trotando", 50,
               target_type="hr_zone", target_zone=4, repeat_count=5)],
        intensity_class="hill_repeats", block_index=0)]))

    print("\n--- Resultado ---\n")
    fallos = 0
    for etiqueta, dias in casos:
        fechas = sorted({d.date for d in dias})
        semana = [{"date": f, "day_name_es": _DIAS_ES[date.fromisoformat(f).weekday()]} for f in fechas]
        payload = WeeklyPlanPayload(
            athlete_name=nombre, start_date=fechas[0], zones_table="",
            weeks=[WeekBlock(week_number=1, days=dias)],
        )
        informe = validate_weekly_plan(payload, disponibilidad, semana)
        # Sólo interesan los rechazos de FORMA, no que falten días de la semana.
        relevantes = [v for v in informe["violations"]
                      if v["type"] not in ("missing_date", "unexpected_date")]
        if relevantes:
            fallos += 1
            print(f"{MAL}{etiqueta}")
            for v in relevantes:
                print(f"      {v['type']}: {v['detail'][:110]}")
            continue

        # Y que además se pueda construir para el reloj.
        problemas = []
        for d in dias:
            if not d.garmin_exportable:
                problemas.append(f"{d.sport}: va escrita, no al reloj")
                continue
            try:
                dto = build_garmin_workout(d, zonas)
                tramos = [s["sportType"]["sportTypeKey"] for s in dto["workoutSegments"]]
                problemas.append(f"{d.sport} → {'+'.join(tramos)}")
            except UnsupportedSportError as exc:
                problemas.append(f"{d.sport}: NO se puede construir ({exc})")
                fallos += 1
        print(f"{OK}{etiqueta}")
        for p in problemas:
            print(f"      {p}")

    print(f"\n{len(casos) - fallos} de {len(casos)} formas aceptadas.\n")
    return 1 if fallos else 0


if __name__ == "__main__":
    sys.exit(probar(sys.argv[1] if len(sys.argv) > 1 else "Jose Ines Castillo"))
