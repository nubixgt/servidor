"""El porqué de la semana en palabras de atleta, con sus números (decisión del responsable, 2026-10-21).

El porqué que ve el atleta en Plan e Historial llegaba lleno de siglas: «ACWR de Garmin 1,40 y ACWR de esta
app 1,17», «Hooper 12/28 vs tu media 6,5», «TSB +13,0» (José, semana del 12 al 18 de octubre). Los dos
evaluadores ciegos de la revisión lo marcaron como lo más grave. La causa: la instrucción de idioma decía que
«los términos técnicos consagrados se mantienen» (ACWR, HRV…) y nada decía que el atleta no los conoce. El
responsable pidió traducir, no quitar: el detalle es lo que nos diferencia.

Réplicas de la decisión real de José del 2026-10-19 con gpt-5-mid (0,42 USD las tres):
- con el prompt de antes: «ACWR 0,70 en Garmin y 0,77 en la app», «carga aguda baja (201 vs crónica 279)»;
- con la regla nueva pero la línea de los «consagrados» intacta: siguió «el ACWR del 19/10 es 0,70» (ganó la
  línea que decía que se mantienen);
- con esa línea acotada a lo que se escriben entre agentes: «Tu carga de los últimos 7 días es 201 frente a tu
  base de 279 (un 30 % por debajo)», «Tu reloj marca 87/100 de disposición para entrenar», en una frase con la
  decisión y cinco líneas de lo que tuvo en cuenta.
Los días variaron entre las tres vueltas lo mismo que varían solos (la vuelta de control, con el prompt sin
tocar, también cambió dos días); en las tres: afinamiento, fuerza en 2 sesiones de mantenimiento y la carrera.

Correr con:  python -m unittest tests.test_porque_en_palabras -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.prompt_components import get_language_directive  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import WeekIntentContent  # noqa: E402

NODO = Path(__file__).resolve().parents[1] / "services/ai/langgraph/nodes/weekly_planner_node.py"


class LasSiglasSeTraducen(unittest.TestCase):

    def test_la_regla_esta_y_no_la_contradice_la_de_los_consagrados(self):
        d = get_language_directive()
        self.assertIn("ninguna sigla, índice o escala va sola", d)
        self.assertIn("se traduce, no se\n  quita", d)
        # La línea que ganaba: ya no dice que ACWR y HRV «se mantienen» sin más.
        self.assertNotIn("se mantienen en su forma estándar y NO se castellanizan", d)
        self.assertIn("entre agentes se escriben así", d)

    def test_el_porque_es_una_frase_y_lo_que_tuvo_en_cuenta(self):
        nodo = NODO.read_text(encoding="utf-8")
        self.assertIn('first ONE sentence\nwith what the week is and why', nodo)
        self.assertIn("each with its number said in\nwords", nodo)
        self.assertIn("lines starting with '- '", WeekIntentContent.model_fields["week_rationale"].description)


if __name__ == "__main__":
    unittest.main()
