"""✓/✗ de cada sesión desde Garmin, sin IA (el responsable, 2026-10-21).

«Hoy ya nadé y que marque un cheque, o si ya hizo la calidad; y una x cuando no.» La semana es la real de
José del 19 al 25 de octubre (la automática del 19): fuerza el lunes 19, bici de activación el martes 20,
natación de técnica el miércoles 21 (hoy en la prueba), fuerza el jueves 22, natación el viernes 23 y su
gravel de 50 K el sábado 24.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.estado_de_las_sesiones import estados  # noqa: E402
from services.garmin.models import Activity, ActivitySummary  # noqa: E402

HOY = date(2026, 10, 21)


def _dia(fecha, sport, foco, clase="easy", minutos=30.0, rest=False):
    return {"date": fecha, "day_name_es": "-", "is_rest": rest, "sport": "rest" if rest else sport, "focus": foco,
            "purpose": "-", "adaptation": "-", "steps": [], "total_duration_minutes": 0 if rest else minutos,
            "intensity_class": "rest" if rest else clase, "session_slot": "unica"}


PLAN = {"athlete_name": "José", "start_date": "2026-10-19", "zones_table": "", "weeks": [
    {"week_number": 4, "week_rationale": "", "days": [
        _dia("2026-10-19", "strength", "Torso A", "strength_freeform", 42),
        _dia("2026-10-20", "bike", "activación MTB", "easy", 39),
        _dia("2026-10-21", "swim", "técnica + ritmo", "easy", 30),
        _dia("2026-10-22", "strength", "Cuerpo completo", "strength_freeform", 30),
        _dia("2026-10-23", "swim", "soltura + chispa", "neuromuscular", 25),
        _dia("2026-10-24", "bike", "Carrera: 50 K Gravel", "race_day", 180),
        _dia("2026-10-25", "rest", "Descanso", rest=True),
    ]},
]}


def _actividad(fecha, tipo, minutos, nombre="x"):
    return Activity(activity_id=hash((fecha, tipo)) % 10**9, activity_type=tipo, activity_name=nombre,
                    start_time=f"{fecha} 06:00:00", summary=ActivitySummary(duration=int(minutos * 60), distance=1500.0))


class LoQueVeElAtleta(unittest.TestCase):

    def test_hoy_ya_nade_y_sale_el_cheque(self):
        r = estados(PLAN, [_actividad("2026-10-21", "lap_swimming", 31, "Natación")], HOY)
        self.assertEqual(r["2026-10-21|unica"]["estado"], "hecha")
        self.assertEqual(r["2026-10-21|unica"]["minutos"], 31)

    def test_hoy_sin_actividad_todavia_esta_a_tiempo(self):
        r = estados(PLAN, [], HOY, ultima_sincronizacion="2026-10-21T15:00:00Z")
        self.assertEqual(r["2026-10-21|unica"]["estado"], "pendiente", "hoy no es una ✗")

    def test_ayer_sin_actividad_y_el_reloj_ya_sincronizo_es_una_x(self):
        r = estados(PLAN, [_actividad("2026-10-19", "strength_training", 40)], HOY,
                    ultima_sincronizacion="2026-10-21T15:00:00Z")
        self.assertEqual(r["2026-10-19|unica"]["estado"], "hecha")
        self.assertEqual(r["2026-10-20|unica"]["estado"], "no_hecha")

    def test_si_el_reloj_no_ha_sincronizado_no_se_juzga(self):
        """José, 2026-10-19: lo que el reloj no subió contaba como no hecho."""
        r = estados(PLAN, [], HOY, ultima_sincronizacion="2026-10-19T12:00:00Z")
        self.assertEqual(r["2026-10-20|unica"]["estado"], "sin_sincronizar")

    def test_lo_que_viene_queda_pendiente(self):
        r = estados(PLAN, [_actividad("2026-10-21", "lap_swimming", 31)], HOY)
        for clave in ("2026-10-22|unica", "2026-10-23|unica", "2026-10-24|unica"):
            self.assertEqual(r[clave]["estado"], "pendiente")
        self.assertNotIn("2026-10-25|unica", r, "un descanso no lleva marca")

    def test_hecha_un_dia_despues(self):
        """La bici del martes, hecha el miércoles: ✓ y dice el día."""
        r = estados(PLAN, [_actividad("2026-10-21", "cycling", 40), _actividad("2026-10-21", "lap_swimming", 30)], HOY)
        self.assertEqual((r["2026-10-20|unica"]["estado"], r["2026-10-20|unica"]["otro_dia"]), ("hecha", "2026-10-21"))
        self.assertEqual(r["2026-10-21|unica"]["estado"], "hecha", "la natación de hoy no se la queda la bici")

    def test_las_semanas_viejas_no_se_miden(self):
        viejo = {**PLAN, "weeks": [{"week_number": 1, "week_rationale": "", "days": [
            _dia("2026-09-01", "run", "Rodaje", "easy", 40)]}] + PLAN["weeks"]}
        self.assertNotIn("2026-09-01|unica", estados(viejo, [], HOY))

    def test_sin_plan_no_hay_nada(self):
        self.assertEqual(estados(None, [], HOY), {})
        self.assertEqual(estados({"weeks": []}, [], HOY), {})


if __name__ == "__main__":
    unittest.main()
