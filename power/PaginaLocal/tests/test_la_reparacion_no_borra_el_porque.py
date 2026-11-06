"""Una reparación tier-2 arregla un día y no puede llevarse el "por qué" de la semana.

El caso real, 2026-09-11, atleta 2: la semana del 12 al 18 de septiembre pasó
por una reparación por UN día (la natación del viernes 18, antes del 5K). El
modelo devolvió la semana con su `week_rationale` intacto -- "Esta semana está
diseñada como afinamiento hacia el 5K Monterrico..." --, pero
`_reconcile_repair` rehacía cada semana como `WeekBlock(week_number, days)` y
el razonamiento llegó vacío a MySQL, a la app y a la hoja impresa. Sin error
en ningún sitio: el fallo que CLAUDE.md dice que se repite en este repo.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import _reconcile_repair  # noqa: E402

POR_QUE = "Esta semana está diseñada como afinamiento hacia el 5K Monterrico del 2026-09-20."


def _dia(fecha, foco):
    return WorkoutDay(
        date=fecha, day_name_es="Viernes", is_rest=False, sport="swim", focus=foco,
        purpose="p", adaptation="a", total_duration_minutes=40,
        steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=2400,
                           target_type=TargetType.NONE, description=foco)],
    )


def _plan(foco, razon):
    return WeeklyPlanPayload(
        athlete_name="T", start_date="2026-09-12", zones_table="z",
        weeks=[WeekBlock(week_number=1, week_rationale=razon,
                         days=[_dia("2026-09-17", "Técnica"), _dia("2026-09-18", foco)])],
    )


class ElPorQueSobreviveALaReparacion(unittest.TestCase):
    VIOLACION = [{"date": "2026-09-18", "session_slot": "unica", "type": "race_taper_conflict"}]

    def test_reparar_un_dia_conserva_el_razonamiento_de_la_semana(self):
        original = _plan("CSS controlado", POR_QUE)
        reparado = _plan("Soltar + técnica", "")
        semana = _reconcile_repair(original, reparado, self.VIOLACION).weeks[0]
        self.assertEqual(semana.week_rationale, POR_QUE)
        self.assertEqual([d.focus for d in semana.days], ["Técnica", "Soltar + técnica"])

    def test_una_reparacion_de_semana_entera_sin_razonamiento_tampoco_lo_borra(self):
        original = _plan("CSS controlado", POR_QUE)
        reparado = _plan("Soltar + técnica", "")
        semana = _reconcile_repair(original, reparado, [{"week_number": 1, "type": "acwr_guardrail"}]).weeks[0]
        self.assertEqual(semana.week_rationale, POR_QUE)


if __name__ == "__main__":
    unittest.main()
