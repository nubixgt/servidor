"""El texto de un paso que ajustó el atleta, y sus dos contratos (Beta 3.0, F7).

- Las horas se escriben como horas y la red de duraciones las lee bien: «1 h 50 min» leído
  como 50 min mandó la carrera de 6.600 s del atleta 2 al reloj como 3.000 (2026-10-09).
- El bloque lleva la serie entera y el hijo sólo su trabajo: el hijo con la serie fue el
  «8× · 8x50m» que se leía como 64 × 50 (auditoría del 2026-09-24).

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.rendering import describir_paso as d  # noqa: E402


class ElTexto(unittest.TestCase):

    def test_duraciones(self):
        self.assertEqual(d.duracion("time", 45), '45"')
        self.assertEqual(d.duracion("time", 600), "10'")
        self.assertEqual(d.duracion("time", 90), "1'30\"")
        self.assertEqual(d.duracion("time", 6600), "1 h 50'")
        self.assertEqual(d.duracion("time", 7200), "2 h")
        self.assertEqual(d.duracion("distance", 400, "run"), "400 m")
        self.assertEqual(d.duracion("distance", 1500, "run"), "1,5 km")
        self.assertEqual(d.duracion("distance", 1500, "swim"), "1500 m", "en natación, metros")
        self.assertEqual(d.duracion("lap_button", None), "hasta pulsar vuelta")

    def test_objetivos(self):
        self.assertEqual(d.objetivo({"target_type": "hr_zone", "target_zone": 2}), "en Z2")
        self.assertEqual(d.objetivo({"target_type": "pace", "target_low": 335, "target_high": 362}), "a 5:35-6:02/km")
        self.assertEqual(d.objetivo({"target_type": "power", "target_low": 200, "target_high": 220}), "a 200-220 W")
        self.assertEqual(d.objetivo({"target_type": "hr_zone", "target_zone": 2}, "swim"), "", "natación sin objetivo")


class LosContratos(unittest.TestCase):

    def test_las_horas_las_lee_bien_la_red_de_duraciones(self):
        from services.ai.langgraph.schemas.plan_outputs import (
            WeekBlock,
            WeeklyPlanPayload,
            WorkoutDay,
            WorkoutStep,
        )
        from services.ai.langgraph.validation.plan_validator import _duracion_como_dice_el_paso

        texto = d.paso({"step_type": "main", "duration_type": "time", "duration_value": 6600,
                        "target_type": "hr_zone", "target_zone": 2})
        self.assertEqual(texto, "1 h 50' en Z2")
        paso = WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=6600, description=texto)
        dia = WorkoutDay(date="2026-10-18", day_name_es="Domingo", is_rest=False, sport="run", focus="Larga",
                         purpose="-", adaptation="-", total_duration_minutes=110, steps=[paso])
        plan, cambios = _duracion_como_dice_el_paso(WeeklyPlanPayload(
            athlete_name="x", start_date="2026-10-12", zones_table="", weeks=[WeekBlock(week_number=1, days=[dia])]))
        self.assertEqual((plan.weeks[0].days[0].steps[0].duration_value, cambios), (6600, []))

    def test_el_hijo_no_repite_la_serie(self):
        from services.ai.langgraph.schemas.plan_outputs import FormattedWorkoutStep

        trabajo = {"duration_type": "distance", "duration_value": 50, "target_type": "none"}
        rec = {"duration_type": "time", "duration_value": 20}
        linea = d.serie(8, trabajo, rec, "swim")
        self.assertEqual(linea, "8x(50 m, 20\" rec)")
        f = FormattedWorkoutStep(step_order=1, step_type="interval", duration_type="distance", duration_value=50,
                                 repeat_count=8, recovery_duration_type="time", recovery_duration_value=20,
                                 description=linea)
        self.assertEqual(f._work_description(), "50 m", "el hijo dice sólo su trabajo, nunca «8x50 m»")


if __name__ == "__main__":
    unittest.main()
