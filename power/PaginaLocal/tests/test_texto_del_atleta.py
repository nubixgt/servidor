"""Un informe no puede llevar palabras de un alfabeto que el atleta no lee.

Macrociclo regenerado de la atleta 3 (2026-09-14): "(RUN) դեպի 10K A", con
"hacia" en armenio en el título. Y la guía del informe, que citaba un "rango
óptimo de ACWR 0,8-1,3" en los informes de los atletas 2 y 3 ese mismo día.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.texto_del_atleta import sin_alfabetos_ajenos  # noqa: E402


class SinAlfabetosAjenos(unittest.TestCase):
    def test_el_titulo_real_de_jessie(self):
        self.assertEqual(
            sin_alfabetos_ajenos("# Plan estratégico de temporada — Jessie Navas (RUN) դեպի 10K A"),
            "# Plan estratégico de temporada — Jessie Navas (RUN) 10K A",
        )

    def test_el_espanol_y_la_notacion_se_quedan(self):
        texto = "Umbral: 5:51/km · VO₂max 46 · Δ carga +12 % · ¿Cómo estás? ñ ü"
        self.assertEqual(sin_alfabetos_ajenos(texto), texto)

    def test_otros_alfabetos_tambien(self):
        self.assertEqual(sin_alfabetos_ajenos("sube la carga 逐步 poco a poco"), "sube la carga poco a poco")
        self.assertEqual(sin_alfabetos_ajenos("zona Зона 2"), "zona 2")

    def test_vacio(self):
        self.assertEqual(sin_alfabetos_ajenos(None), "")
        self.assertEqual(sin_alfabetos_ajenos(""), "")


class ElInformeNoInventaUnRangoOptimoDeACWR(unittest.TestCase):
    def test_la_guia_lo_dice(self):
        from services.ai.langgraph.nodes.synthesis_node import GUIA_DE_ESTILO_DEL_INFORME

        self.assertIn("No existe un rango óptimo de ACWR", GUIA_DE_ESTILO_DEL_INFORME)
        # Decía "es de CARGA CRÓNICA" y el bloque de carga del mismo prompt dice, bien, que es el
        # rango de la carga AGUDA (0,8-1,5 veces la crónica): dos textos contrarios (2026-09-24).
        self.assertIn("es para su CARGA AGUDA (0,8-1,5 veces su crónica)", GUIA_DE_ESTILO_DEL_INFORME)
        self.assertNotIn("es de CARGA CRÓNICA", GUIA_DE_ESTILO_DEL_INFORME)


if __name__ == "__main__":
    unittest.main()
