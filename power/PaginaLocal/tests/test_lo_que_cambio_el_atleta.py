"""La semana siguiente sabe lo que el atleta movió o ajustó (Beta 3.0, F6.6).

Plan del responsable (2026-10-10): «El planificador sabe lo que cambió el atleta. Bloque
determinista nuevo en el prompt de la decisión semanal: qué movió o ajustó el atleta la semana
anterior, como preferencia observada».

No hace falta un cable nuevo: el plan anterior que ya recibe la decisión lleva en cada día la
marca de F6/F7 (`edited.moved_from`, `edited.adjusted`). Lo que faltaba era decirlo. Se dice como
lo que es -- algo que HIZO, no algo que pidió -- para que un cambio suelto no reescriba su semana
ni pise su tabla de días (01: no meter reglas que nadie pidió).

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)

NODO = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")


def _dia(fecha, nombre, foco, editado=None, minutos=45):
    paso = WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=minutos * 60,
                       description=f"{minutos}' {foco}")
    return WorkoutDay(date=fecha, day_name_es=nombre, is_rest=False, purpose="", adaptation="", sport="run",
                      focus=foco, total_duration_minutes=minutos, steps=[paso], edited=editado)


def _plan(*dias):
    return WeeklyPlanPayload(athlete_name="x", start_date="2026-10-12", zones_table="",
                             weeks=[WeekBlock(week_number=3, week_rationale="", days=list(dias))])


# La prueba del navegador de F6 (atleta 2): «Ritmo 10K» del miércoles 14 al jueves 15.
MOVIDA = _dia("2026-10-15", "Jueves", "Ritmo 10K", {"moved_from": "2026-10-14", "by": "atleta"})
AJUSTADA = _dia("2026-10-16", "Viernes", "Rodaje suave", {"adjusted": True, "by": "atleta"}, minutos=32)
SIN_TOCAR = _dia("2026-10-18", "Domingo", "Tirada larga", minutos=75)


class LaFraseDelRacional(unittest.TestCase):
    """Dos réplicas del 2026-10-11 sobre la decisión REAL de Jessie (atleta 3, semana del 28-sep,
    gpt-5.2, 0,11 y 0,09 USD) con «intercambió la tirada larga del domingo 27 y el rodaje del
    viernes 25»: las dos dejaron la larga el domingo (bien: un cambio suelto no rehace la semana) y
    NINGUNA lo dijo en el racional. La petición iba en el bloque del plan anterior, a ~60.000
    caracteres del «## Output» donde se dice qué lleva `week_rationale`: ahora va ahí."""

    def test_la_pide_el_output_solo_si_hubo_cambios(self):
        self.assertIn("{rationale_de_cambios}", NODO.TRAINING_DECISION_USER_PROMPT)
        self.assertLess(NODO.TRAINING_DECISION_USER_PROMPT.index("## Output"),
                        NODO.TRAINING_DECISION_USER_PROMPT.index("{rationale_de_cambios}"))
        self.assertEqual(NODO.rationale_de_cambios(_plan(MOVIDA), "2026-10-17"), NODO.RATIONALE_DE_CAMBIOS)
        self.assertEqual(NODO.rationale_de_cambios(_plan(SIN_TOCAR), "2026-10-17"), "")
        self.assertEqual(NODO.rationale_de_cambios(_plan(MOVIDA), "2026-10-17", rehaciendo=True), "")


class LoQueCambio(unittest.TestCase):

    def test_las_frases_con_los_dias_escritos_por_el_codigo(self):
        self.assertEqual(NODO.cambios_del_atleta(_plan(MOVIDA, AJUSTADA, SIN_TOCAR)), [
            "movió «Ritmo 10K» del miércoles 14/10 al jueves 15/10",
            "ajustó los pasos de «Rodaje suave» del viernes 16/10 (lo de arriba es su versión)",
        ])

    def test_va_en_la_linea_y_en_el_bloque_como_preferencia_observada(self):
        texto = NODO._format_previous_plan(_plan(MOVIDA, AJUSTADA, SIN_TOCAR), "2026-10-17")
        self.assertIn("[la MOVIÓ el atleta desde el miércoles 14/10]", texto)
        self.assertIn("[el atleta AJUSTÓ sus pasos: es su versión]", texto)
        self.assertIn("preferencia OBSERVADA, no una regla ni una petición", texto)
        self.assertIn("no rehagas su semana por un cambio", texto)
        self.assertIn("Su tabla de días y lo que pide siguen mandando", texto)

    def test_sin_cambios_el_prompt_no_cambia_ni_un_caracter(self):
        texto = NODO._format_previous_plan(_plan(SIN_TOCAR), "2026-10-17")
        self.assertNotIn("desde la app", texto)
        self.assertNotIn("[la MOVIÓ", texto)

    def test_al_rehacer_la_misma_semana_no_se_dice(self):
        """Rehacer reescribe esos mismos días: el cambio ya está en el borrador que se corrige."""
        texto = NODO._format_previous_plan(_plan(MOVIDA), "2026-10-17", rehaciendo=True)
        self.assertNotIn("desde la app", texto)

    def test_un_plan_viejo_no_ensena_preferencias(self):
        texto = NODO._format_previous_plan(_plan(MOVIDA), "2026-12-30")
        self.assertNotIn("desde la app", texto)


if __name__ == "__main__":
    unittest.main()
