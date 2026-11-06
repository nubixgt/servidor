"""Beta 2.1, paso S1: el plan puede expresar una superserie.

Fernando (2026-09-17) hace su rutina Upper/Lower con dos o tres ejercicios "en
simultáneo" y 2' entre rondas:

    Upper A — 120 min
    Press de banca 4×8 - Elevaciones laterales — 4×12 - Crunch — 3×15
    Dominadas 4×10 - Press inclinado mancuernas — 4×10 - Curl con mancuernas — 4×10
    Aperturas de pecho 3x10 - Remo con polea 3×10 - Extensión tríceps 3×12

En las semanas archivadas de José y Jessie la decisión escribía "Bloque A (2
rondas)" o "Circuito x3" y el codificador lo aplanaba en series sueltas: el
esquema no tenía dónde escribir un grupo. S1 añade los grupos SIN tocar la IA:
el esquema que ven el codificador y la reparación no cambia, y todo lo que ya
leía la lista plana sigue funcionando.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import json
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import render_week_for_coach  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    FormattedWeekBlock,
    StrengthBlock,
    StrengthExercise,
    StrengthGroup,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
    grupos_de_fuerza,
    is_garmin_exportable,
)
from services.ai.rendering.printable_renderer import render_printable_plan  # noqa: E402

# El paquete `nodes` exporta una FUNCIÓN con el nombre del módulo: se pide el módulo.
NODO = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")


def _ej(nombre, series, reps, carga=""):
    return StrengthExercise(name=nombre, sets=series, reps=reps, load=carga)


def upper_a_de_fernando() -> StrengthBlock:
    return StrengthBlock(
        warmup="10' de movilidad de hombro",
        groups=[
            StrengthGroup(rest_between_rounds_s=120, exercises=[
                _ej("Press de banca", 4, "8"), _ej("Elevaciones laterales", 4, "12"), _ej("Crunch", 3, "15")]),
            StrengthGroup(rest_between_rounds_s=120, exercises=[
                _ej("Dominadas", 4, "10"), _ej("Press inclinado mancuernas", 4, "10"), _ej("Curl con mancuernas", 4, "10")]),
            StrengthGroup(rest_between_rounds_s=120, exercises=[
                _ej("Aperturas de pecho", 3, "10"), _ej("Remo con polea", 3, "10"), _ej("Extensión tríceps", 3, "12")]),
        ],
    )


def _dia(bloque) -> WorkoutDay:
    return WorkoutDay(
        date="2026-09-21", day_name_es="Lunes", is_rest=False, sport="strength", focus="Upper A",
        purpose="Su rutina de tren superior.", adaptation="Si llega cansado, una ronda menos.",
        total_duration_minutes=120, intensity_class="strength_freeform", discipline="fuerza",
        steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=7200,
                           description="Upper A, 120 min")],
        strength_block=bloque,
    )


def _plan(bloque) -> WeeklyPlanPayload:
    return WeeklyPlanPayload(athlete_name="Fernando", start_date="2026-09-21", zones_table="",
                             weeks=[WeekBlock(week_number=1, days=[_dia(bloque)], week_rationale="r")])


class ElCodificadorYLaReparacionNoVenLosGrupos(unittest.TestCase):
    """Sus esquemas eran 12.098 y 13.350 caracteres antes de S1 (2026-09-17)."""

    def test_los_esquemas_no_nombran_los_grupos(self):
        for modelo in (FormattedWeekBlock, WeeklyPlanPayload, StrengthBlock):
            with self.subTest(modelo=modelo.__name__):
                esquema = json.dumps(modelo.model_json_schema(), ensure_ascii=False)
                self.assertNotIn('"groups"', esquema)
                self.assertNotIn("StrengthGroup", esquema)
        # 12098 hasta el 2026-09-23: el codificador dejó de escribir el propósito, la
        # adaptación y el racional (los copia el código de la decisión), y su esquema
        # perdió esos tres campos. El de la reparación (abajo) no cambia.
        esquema = FormattedWeekBlock.model_json_schema()
        self.assertEqual(len(json.dumps(esquema, sort_keys=True, ensure_ascii=False)), 11721)
        dia = esquema["$defs"]["FormattedWorkoutDay"]["properties"]
        self.assertNotIn("purpose", dia)
        self.assertNotIn("adaptation", dia)
        self.assertNotIn("week_rationale", esquema["properties"])
        self.assertEqual(len(json.dumps(WeeklyPlanPayload.model_json_schema(), sort_keys=True, ensure_ascii=False)),
                         13350)


class LosGruposSonLaVerdad(unittest.TestCase):
    def test_la_lista_plana_sale_de_los_grupos_en_orden(self):
        bloque = upper_a_de_fernando()
        self.assertEqual([e.name for e in bloque.exercises][:4],
                         ["Press de banca", "Elevaciones laterales", "Crunch", "Dominadas"])
        self.assertEqual(len(bloque.exercises), 9)

    def test_sobrevive_al_guardado(self):
        guardado = json.loads(_plan(upper_a_de_fernando()).model_dump_json())
        vuelta = WeeklyPlanPayload.model_validate(guardado).weeks[0].days[0].strength_block
        self.assertEqual(len(vuelta.groups), 3)
        self.assertEqual(vuelta.groups[0].rest_between_rounds_s, 120)
        self.assertEqual(len(vuelta.exercises), 9)

    def test_un_plan_viejo_sin_grupos_se_lee_como_un_grupo_por_ejercicio(self):
        viejo = {"warmup": "", "exercises": [
            {"name": "Sentadilla goblet", "sets": 3, "reps": "6-8", "load": "RPE 6", "rest_seconds": 90},
            {"name": "Remo con mancuerna", "sets": 3, "reps": "8-10", "rest_seconds": 75},
        ], "cooldown": "", "notes": ""}
        bloque = StrengthBlock.model_validate(viejo)
        self.assertEqual(bloque.groups, [])
        self.assertEqual([e.name for e in bloque.exercises], ["Sentadilla goblet", "Remo con mancuerna"])
        grupos = grupos_de_fuerza(bloque)
        self.assertEqual([(g.label, g.rest_between_rounds_s, len(g.exercises)) for g in grupos],
                         [("A", 90, 1), ("B", 75, 1)])

    def test_sigue_siendo_exportable(self):
        dia = _dia(upper_a_de_fernando())
        self.assertTrue(is_garmin_exportable(dia.discipline, dia.sport, dia.is_rest, dia.steps, dia.strength_block))


class QuienLeeLaSesionVeLoQueVaSeguido(unittest.TestCase):
    def test_el_coach(self):
        semana = json.loads(_plan(upper_a_de_fernando()).model_dump_json())["weeks"][0]
        texto = render_week_for_coach(semana)
        self.assertIn("Grupo A (seguidos, en superserie), descanso 120s entre rondas:", texto)
        self.assertIn("A1) Press de banca — 4x8", texto)
        self.assertIn("C3) Extensión tríceps — 3x12", texto)

    def test_el_coach_con_un_plan_viejo_lee_como_antes(self):
        viejo = StrengthBlock(exercises=[StrengthExercise(name="Remo", sets=3, reps="10", rest_seconds=75)])
        texto = render_week_for_coach(json.loads(_plan(viejo).model_dump_json())["weeks"][0])
        self.assertIn("  1) Remo — 3x10, descanso 75s", texto)
        self.assertNotIn("Grupo A", texto)

    def test_el_plan_anterior_que_recibe_la_semana_siguiente(self):
        texto = NODO._format_previous_plan(_plan(upper_a_de_fernando()), "2026-09-28")
        # Qué iba seguido y cuánto se descansaba. Sin carga escrita, sin "()" vacíos: la
        # decisión leía "Press de banca 3x8 ()" en cada ejercicio (auditoría del 2026-09-24).
        self.assertIn("A: Press de banca 4x8 + Elevaciones laterales 4x12 + Crunch 3x15 [120s entre rondas]", texto)
        self.assertNotIn("()", texto)

    def test_la_hoja_impresa(self):
        html = render_printable_plan(_plan(upper_a_de_fernando()), {}, generated_at="2026-09-17")
        self.assertIn("3 seguidos", html)
        self.assertIn("120s entre rondas", html)
        self.assertIn("A1 · Press de banca", html)
        self.assertIn("C3 · Extensión tríceps", html)


if __name__ == "__main__":
    unittest.main()
