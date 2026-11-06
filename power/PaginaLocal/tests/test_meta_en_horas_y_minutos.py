"""Una meta de maratón de "3:30" son tres horas y media, no tres minutos y medio.

Informe y macrociclo reales del atleta Fernando (versión de `main`,
2026-09-13): meta 3:30 para la Maratón de Guatemala del 2027-08-22, predicción
de Garmin 3:33:02. El bloque de realismo decía "fuera del rango de lo que suele
lograrse en ese plazo" y "ritmo que exige 0:05/km"; el macrociclo lo corrigió
por su cuenta ("la meta de 3:30 (4:58/km) es perfectamente alineada").

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.goal_realism import assess_goal, parse_time_to_seconds  # noqa: E402

MARATON = {"name": "Maraton Guatemala", "date": "2027-08-22", "priority": "A",
           "distance": "marathon", "target_time": "3:30"}
PREDICCION = [{"distance_key": "marathon", "time_seconds": 3 * 3600 + 33 * 60 + 2, "distance_m": 42195}]


class DosPartesSeLeenConLaDistancia(unittest.TestCase):
    def test_maraton_de_3_30(self):
        self.assertEqual(parse_time_to_seconds("3:30", "marathon"), 12600)

    def test_media_maraton_de_1_45(self):
        self.assertEqual(parse_time_to_seconds("1:45", "half_marathon"), 6300)

    def test_un_5k_en_26_minutos_sigue_siendo_minutos(self):
        self.assertEqual(parse_time_to_seconds("26:00", "5K"), 1560)

    def test_un_10k_en_45_minutos_sigue_siendo_minutos(self):
        self.assertEqual(parse_time_to_seconds("45:00", "10K"), 2700)

    def test_sin_distancia_se_lee_como_siempre(self):
        self.assertEqual(parse_time_to_seconds("3:30"), 210)

    def test_tres_partes_no_cambian(self):
        self.assertEqual(parse_time_to_seconds("3:30:00", "marathon"), 12600)


class ElCasoRealDeFernando(unittest.TestCase):
    def test_la_meta_es_alcanzable_y_pide_un_ritmo_de_maraton(self):
        a = assess_goal(MARATON, PREDICCION, today=date(2026, 9, 14))
        self.assertEqual(a["target_seconds"], 12600)
        self.assertEqual(a["verdict"], "alcanzable")
        self.assertAlmostEqual(a["required_improvement_pct"], 1.4, places=1)
        self.assertEqual(a["pace_context"]["required_pace_clock"], "4:59")


if __name__ == "__main__":
    unittest.main()
