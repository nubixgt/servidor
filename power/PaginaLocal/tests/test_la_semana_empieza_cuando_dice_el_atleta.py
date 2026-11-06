"""La semana empieza el día que el atleta elige, no el día en que generó la primera.

Hay quien planifica de lunes a domingo y quien de sábado a viernes. Hasta la
v1.2 la primera semana arrancaba el día en que se generaba y cada siguiente
empezaba al día siguiente de la anterior: el atleta 2 generó un viernes y
quedó con semanas de viernes a jueves para siempre.

Con `week_start_day` (Database/019) el primer bloque va hasta la víspera de ese
día y los siguientes caen enteros en su sitio.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import describir_bloque  # noqa: E402
from web_app.server import dias_del_bloque  # noqa: E402

MIERCOLES = date(2026, 9, 16)
SABADO = date(2026, 9, 12)
DOMINGO = date(2026, 9, 13)
LUNES = date(2026, 9, 14)


class HastaLaVispera(unittest.TestCase):
    def test_el_ejemplo_del_atleta_miercoles_con_semana_de_lunes(self):
        """"Si hoy fuera miércoles y mi primer día es el lunes, me dará un plan hasta el domingo"."""
        self.assertEqual(dias_del_bloque(MIERCOLES, "Lunes"), 5)

    def test_si_hoy_es_el_dia_en_que_empieza_la_semana_es_una_semana_entera(self):
        self.assertEqual(dias_del_bloque(LUNES, "Lunes"), 7)

    def test_la_siguiente_ya_cae_entera(self):
        """El bloque de miércoles acaba el domingo; la siguiente empieza el lunes."""
        self.assertEqual(dias_del_bloque(LUNES, "Lunes"), 7)
        self.assertEqual(dias_del_bloque(SABADO, "Sábado"), 7)

    def test_sin_eleccion_siete_dias_como_siempre(self):
        for sin_eleccion in (None, "", "lunes", "Monday"):
            with self.subTest(valor=sin_eleccion):
                self.assertEqual(dias_del_bloque(MIERCOLES, sin_eleccion), 7)


class UnBloqueDeUnDiaNoSeGeneraSuelto(unittest.TestCase):
    """Una generación cuesta lo mismo escriba uno o siete días, y un bloque de
    un día obligaría a generar otra vez al día siguiente."""

    def test_domingo_con_semana_de_lunes_se_une_a_la_semana_siguiente(self):
        self.assertEqual(dias_del_bloque(DOMINGO, "Lunes"), 8)

    def test_sabado_con_semana_de_lunes_tambien(self):
        self.assertEqual(dias_del_bloque(SABADO, "Lunes"), 9)

    def test_tres_dias_ya_son_un_bloque(self):
        self.assertEqual(dias_del_bloque(date(2026, 9, 18), "Lunes"), 3)  # viernes


class ElModeloSabeCuantosDiasTiene(unittest.TestCase):
    def test_una_semana_normal_no_anade_nada(self):
        self.assertEqual(describir_bloque(7, "Lunes"), "7 days")

    def test_un_bloque_corto_escala_las_referencias_semanales(self):
        """En un bloque de cinco días, un modelo que no lo sabe mete la carga de siete en cinco."""
        texto = describir_bloque(5, "Lunes")
        self.assertIn("5 days", texto)
        self.assertIn("starts on Lunes", texto)
        self.assertIn("per 7 days: scale", texto)


if __name__ == "__main__":
    unittest.main()
