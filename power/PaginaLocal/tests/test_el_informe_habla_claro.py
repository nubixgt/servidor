"""El informe de análisis recibe una guía de estilo de verdad.

`style_guide` existía en el estado y en el prompt de síntesis, y nunca llegó
nada: `create_initial_state` lo declara con "" y ninguna de sus cinco llamadas
(analysis_workflow y planning_workflow) lo pasa. El bloque de estilo iba vacío y
el tono del informe eran cuatro palabras en inglés. La beta lo pidió con otras:
"lenguaje para mortales".

Correr con:  python -m unittest discover -s tests -v
"""

import inspect
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.synthesis_node import (  # noqa: E402
    GUIA_DE_ESTILO_DEL_INFORME,
    construir_mensaje_de_usuario,
)
from services.ai.langgraph.state.training_analysis_state import create_initial_state  # noqa: E402

ESTADO = {
    "athlete_name": "Atleta", "competitions": [], "current_date": {"date": "2026-09-13"},
    "metrics_outputs": None, "activity_outputs": None, "physiology_outputs": None,
}


class LaGuiaLlegaAunqueNadieLaPase(unittest.TestCase):
    def test_con_el_estado_vacio_llega_la_guia_entera(self):
        mensaje = construir_mensaje_de_usuario({**ESTADO, "style_guide": ""})
        self.assertIn(GUIA_DE_ESTILO_DEL_INFORME, mensaje)

    def test_el_estado_que_crean_los_flujos_sigue_sin_traerla(self):
        """Por eso el respaldo vive en el nodo: la fábrica del estado sigue
        poniendo "" por defecto. Si algún día cambia ese valor, esta prueba
        avisa de que el respaldo del nodo ya no es el que manda."""
        por_defecto = inspect.signature(create_initial_state).parameters["style_guide"].default
        self.assertEqual(por_defecto, "")
        mensaje = construir_mensaje_de_usuario({**ESTADO, "style_guide": por_defecto})
        self.assertIn(GUIA_DE_ESTILO_DEL_INFORME, mensaje)

    def test_una_guia_explicita_gana(self):
        mensaje = construir_mensaje_de_usuario({**ESTADO, "style_guide": "GUIA PROPIA"})
        self.assertIn("GUIA PROPIA", mensaje)
        self.assertNotIn(GUIA_DE_ESTILO_DEL_INFORME, mensaje)

    def test_ya_no_manda_el_tono_de_cuatro_palabras(self):
        self.assertNotIn("Professional, evidence-based, encouraging", construir_mensaje_de_usuario(
            {**ESTADO, "style_guide": ""}))


class LoQueDiceLaGuia(unittest.TestCase):
    def test_lo_que_pidio_la_beta(self):
        for regla in ("La conclusión antes que el método", "Cada término técnico se explica",
                      "Cuando faltan datos, dilo llano", "de tú"):
            with self.subTest(regla=regla):
                self.assertIn(regla, GUIA_DE_ESTILO_DEL_INFORME)


if __name__ == "__main__":
    unittest.main()
