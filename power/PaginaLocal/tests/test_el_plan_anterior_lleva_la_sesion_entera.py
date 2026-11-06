"""La semana siguiente recibe la sesión anterior ENTERA, no su primer paso (auditoría del 2026-09-24).

Prompt real de la semana del atleta 2 del 2026-10-05: "Plan anterior — lo que YA le dosificaste" decía
"run · VO2max controlado 60' — 4x20\" progresivos, 60\" muy suave" de una sesión cuyo bloque era
5x(3' a 5:09-5:29/km, 2'30" rec), y "swim · largo 3.5 km 110' — 8x50m técnica" de su natación de 3x800.
Se tomaba el primer paso de series o principal, y en esas sesiones era el calentamiento. Lo que la
decisión tiene que hacer progresar (la DOSIS del bloque) no llegaba.
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


def _paso(orden, tipo, seg, texto, n=1, dentro=None):
    return WorkoutStep(step_order=orden, step_type=tipo, duration_type=None if dentro else "time",
                       duration_value=None if dentro else seg, repeat_count=n, description=texto,
                       substeps=dentro or [])


class LaSesionDelMiercoles(unittest.TestCase):
    def test_llega_el_bloque_principal(self):
        pasos = [
            _paso(1, "warmup", 900, "15' calentamiento en Z1–Z2"),
            _paso(2, "repeat", None, '4x20" progresivos, 60" muy suave', 4,
                  [_paso(1, "interval", 20, '20" progresivos'), _paso(2, "recovery", 60, '60" muy suave')]),
            _paso(3, "repeat", None, "5x(3' a 5:09–5:29/km, 2'30\" rec)", 5,
                  [_paso(1, "interval", 180, "3' a 5:09–5:29/km"), _paso(2, "recovery", 150, "2'30\" trote")]),
            _paso(4, "cooldown", 600, "10' enfriamiento en Z1–Z2"),
        ]
        dia = WorkoutDay(date="2026-09-30", day_name_es="Miércoles", is_rest=False, purpose="", adaptation="",
                         sport="run", focus="VO2max controlado", total_duration_minutes=60, steps=pasos)
        plan = WeeklyPlanPayload(athlete_name="x", start_date="2026-09-28", zones_table="",
                                 weeks=[WeekBlock(week_number=2, week_rationale="", days=[dia])])
        texto = NODO._format_previous_plan(plan, "2026-10-01")
        self.assertIn("5x(3' a 5:09–5:29/km, 2'30\" rec)", texto)
        self.assertIn("15' calentamiento en Z1–Z2 | 4x20\" progresivos", texto)


if __name__ == "__main__":
    unittest.main()
