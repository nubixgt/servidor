"""Los drills de natación se eligen por lo que necesita la brazada (el responsable, 2026-10-21).

«Los drills de natación, personalmente sólo me han aparecido finger tip drag, sculling, catch-up… no
recuerdo otros de patada, respiración, etc.» Medido en las 25 decisiones archivadas de los atletas 2 y 3
(23 días con natación): catch-up 15, arrastre de dedos 15, sculling 9, puños 7, palas 3, flecha 1, patada con
tabla 1; ninguna patada de costado, 6-1-6, un brazo, respiración bilateral, orientación ni crol con la cabeza
fuera. No lo limitábamos nosotros (el prompt no daba lista): el modelo volvía a su repertorio y COPIABA la
semana anterior -- el «Plan anterior» de la semana automática de José del 19-oct decía «6x50m técnica
alternando catch-up y fingertip drag» y la decisión escribió exactamente eso.

Réplicas de esa decisión real (gpt-5-mid, el de producción): con la primera versión del menú, otra vez
catch-up + fingertip y sculling, aunque ya emparejados con nado completo (0,132 USD); con «En Plan anterior
están los drills de su semana pasada: no los copies», el miércoles sculling delante + crol pensando en el
agarre y el viernes exhalar continuo bajo el agua (0,139 USD).

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

w = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")


class QuienRecibeLaGuia(unittest.TestCase):

    def test_quien_nada(self):
        self.assertTrue(w._nada({"planning_context": "| Nadar | Miércoles: fijo |"}))
        self.assertTrue(w._nada({"competitions": [{"name": "Triatlón Atitlán", "race_type": "Olympic"}]}))
        self.assertTrue(w._nada({"competitions": [{"name": "Travesía", "race_type": "aguas abiertas 3K"}]}))

    def test_quien_no_nada_no_recibe_ni_un_token(self):
        self.assertFalse(w._nada({"planning_context": "| Correr | Lunes: fijo |", "competitions": [{"name": "10K"}]}))


class LoQueDice(unittest.TestCase):

    def test_no_copiar_la_semana_anterior(self):
        self.assertIn("no los copies", w.GUIA_DE_DRILLS)

    def test_un_menu_por_objetivo_con_patada_y_respiracion(self):
        for objetivo in ("Posición del cuerpo y patada", "Respiración", "Rotación y alineación",
                         "Agarre y tracción", "Recobro y entrada", "Aguas abiertas"):
            self.assertIn(objetivo, w.GUIA_DE_DRILLS)

    def test_drill_seguido_de_nado_completo(self):
        self.assertIn("seguido de nado completo", w.GUIA_DE_DRILLS)

    def test_sin_hipoxicos_al_limite(self):
        """Un síncope en el agua no es un riesgo que una guía de técnica pueda invitar."""
        self.assertIn("Nada de apneas largas ni series hipóxicas al límite", w.GUIA_DE_DRILLS)

    def test_no_es_una_cuota(self):
        self.assertIn("ni cerrado ni una cuota", w.GUIA_DE_DRILLS)


if __name__ == "__main__":
    unittest.main()
