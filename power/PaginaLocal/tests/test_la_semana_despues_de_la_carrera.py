"""La semana de después de una carrera es de recuperación, y el código lo sabe.

Semana real del atleta 2 del 21 al 27 de septiembre de 2026 (traza
`u2/reasoning/2026-09-14T15-02-39`): empezaba el día siguiente a su 5K
Monterrico (B, a tope) y sumó ~8,4 h (505') contra 7,1 h de una semana normal
suya, con un racional que decía "la prioridad es recuperar". El bloque de carga
le decía "La fase del macrociclo describe una semana normal de ~270; hoy su
crónica es 285": el código sólo sabía de la semana ANTES de una carrera.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")

CINCO_K = {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B", "distance": "5K", "target_time": "00:26:00"}
DIEZ_K_A = {"name": "10K", "date": "2027-04-20", "priority": "A", "distance": "10K", "target_time": "00:53:00"}
SEMANA = [{"date": f"2026-09-{21 + i}"} for i in range(7)]
BASE = {"garmin_chronic_load": 285, "garmin_acute_load": 249, "chronic_weekly_hours": 7.1,
        "previous_week_planned_hours": 3.9}
POSICION = {"week_number": 2, "weekly_hours_target": 6.5, "weekly_load_target": 270, "is_deload": False}


class DetectaLaCarreraQueAcabaDeTerminar(unittest.TestCase):
    def test_el_caso_de_jose(self):
        r = nodo.carrera_reciente([CINCO_K, DIEZ_K_A], SEMANA)
        self.assertEqual((r["name"], r["days_since"], r["easy_days"]), ("5k Monterrico", 1, 2))

    def test_una_carrera_de_hace_tres_semanas_ya_no_cuenta(self):
        semana = [{"date": f"2026-10-{12 + i}"} for i in range(7)]
        self.assertIsNone(nodo.carrera_reciente([CINCO_K], semana))

    def test_una_maraton_pide_mas_dias_y_una_ventana_mas_larga(self):
        maraton = {"name": "Maratón", "date": "2026-09-20", "priority": "A", "distance": "marathon"}
        semana = [{"date": f"2026-10-{1 + i:02d}"} for i in range(7)]   # 11 días después
        r = nodo.carrera_reciente([maraton], semana)
        self.assertEqual(r["easy_days"], 14)
        self.assertEqual(r["days_since"], 11)

    def test_un_triatlon_no_recibe_una_regla_de_corredores(self):
        tri = {"name": "Olímpico", "date": "2026-09-20", "priority": "A", "distance": "tri_olympic"}
        r = nodo.carrera_reciente([tri], SEMANA)
        self.assertIsNotNone(r)
        self.assertIsNone(r["easy_days"])

    def test_la_carrera_de_la_semana_siguiente_no_es_reciente(self):
        self.assertIsNone(nodo.carrera_reciente([{**CINCO_K, "date": "2026-09-24"}], SEMANA))


class LaSemanaLoDiceYNoSubeElVolumen(unittest.TestCase):
    def test_el_bloque_de_carga_dice_recuperacion_y_no_la_fase(self):
        t = nodo.compute_weekly_load_target(BASE, POSICION, competitions=[CINCO_K, DIEZ_K_A], week_dates=SEMANA)
        self.assertEqual(t["kind"], "recuperacion")
        bloque = nodo._format_load_target(t)
        self.assertIn("empieza 1 día(s) después de 5k Monterrico", bloque)
        self.assertIn("No es una semana de construcción", bloque)
        self.assertIn("~7.1 h", bloque)
        self.assertIn("unos 2 días", bloque)
        self.assertNotIn("describe una semana normal de ~270", bloque)

    def test_las_horas_no_suben_por_encima_de_una_semana_normal(self):
        v = nodo.compute_weekly_volume_target(BASE, POSICION, competitions=[CINCO_K], week_dates=SEMANA)
        self.assertLessEqual(v["target_hours"], 7.1)
        self.assertIn("No es una semana de construcción", nodo._format_volume_target(v))

    def test_la_semana_del_afinamiento_sigue_siendo_afinamiento(self):
        semana_del_5k = [{"date": f"2026-09-{14 + i}"} for i in range(7)]
        t = nodo.compute_weekly_load_target(BASE, POSICION, competitions=[CINCO_K], week_dates=semana_del_5k)
        self.assertEqual(t["kind"], "afinamiento")

    def test_sin_carreras_cerca_es_una_semana_normal(self):
        semana = [{"date": f"2026-11-{2 + i:02d}"} for i in range(7)]
        t = nodo.compute_weekly_load_target(BASE, POSICION, competitions=[CINCO_K, DIEZ_K_A], week_dates=semana)
        self.assertEqual(t["kind"], "normal")

    def test_la_decision_sabe_que_despues_de_una_carrera_tambien_hay_algo(self):
        self.assertIn("the days right after one", nodo.TRAINING_DECISION_USER_PROMPT)


if __name__ == "__main__":
    unittest.main()
