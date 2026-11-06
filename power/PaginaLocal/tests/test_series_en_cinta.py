"""Las series en cinta van por FC, con la velocidad de la cinta en el texto (Beta 3.0, F10; A11).

Auditoría de Jessie (2026-09-15): su semana le sugirió series con objetivo de RITMO en cinta. El reloj no
mide bien el ritmo bajo techo (el rodaje en cinta del atleta 2 del 18-mar marcó 32:40/km): un objetivo de
ritmo pitaría al ritmo equivocado. La causa estaba en lo que leía el modelo -- la línea del perfil decía
«controlled-pace intervals and tempo work can be prescribed indoors» y la guía de trail «en llano —una
cinta, una pista, la calle— el ritmo sí es la unidad» -- y en el validador, que exige ritmo a toda calidad
de carrera y lo habría vuelto a poner.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import TargetType, WorkoutStep  # noqa: E402
from tests.test_coaching_rules import _day, _recovery, _repeat, _violations, _warmup  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


def _serie_por_fc(texto="3' a 11,3 km/h (5:18/km) en cinta"):
    return WorkoutStep(step_order=1, step_type="interval", duration_type="time", duration_value=180,
                       target_type=TargetType.HR_ZONE, target_zone=4, description=texto)


class ElValidador(unittest.TestCase):

    def test_una_calidad_en_cinta_por_fc_no_es_un_defecto(self):
        dia = _day([_warmup(target_zone=2), _repeat([_serie_por_fc(), _recovery()])], focus="Umbral en cinta")
        self.assertNotIn("quality_without_pace", _violations(dia))

    def test_fuera_de_la_cinta_sigue_exigiendo_ritmo(self):
        dia = _day([_warmup(target_zone=2), _repeat([_serie_por_fc("3' fuerte"), _recovery()])])
        self.assertIn("quality_without_pace", _violations(dia))


class LoQueLeeElModelo(unittest.TestCase):

    def test_el_perfil_y_el_codificador_lo_dicen(self):
        servidor = (RAIZ / "PaginaLocal/web_app/server.py").read_text(encoding="utf-8")
        self.assertIn("On a treadmill the \"\n            \"MACHINE sets the pace", servidor)
        nodo = (RAIZ / "PaginaLocal/services/ai/langgraph/nodes/weekly_planner_node.py").read_text(encoding="utf-8")
        self.assertIn("except on a treadmill", nodo)
        self.assertNotIn("En llano —una cinta, una pista, la calle—", nodo)

    def test_la_regla_obligatoria_de_la_ficha_lleva_la_excepcion(self):
        """Réplicas del 2026-10-11 sobre la semana real de Jessie (24-sep) con «haré las series en cinta»: la
        decisión escribió «5x3' a 11,3–11,9 km/h (5:18–5:02/km)» en cinta, pero Haiku las codificó con ritmo
        302-318 s/km porque la ficha dice «REGLA DE MODALIDAD (obligatoria) … Nunca de FC» y la excepción iba
        en otro punto. Con la excepción DENTRO de la regla: zona de FC 4 y los km/h en el texto. 0,18 USD."""
        from services.ai.prescription import AthletePrescription, render_prescription_card
        p = AthletePrescription()
        p.threshold_pace_sec_per_km = 330.0
        p.pace_bands = {"umbral_tempo": (325.0, 350.0)}
        self.assertIn("**Salvo en CINTA**", render_prescription_card(p))


if __name__ == "__main__":
    unittest.main()
