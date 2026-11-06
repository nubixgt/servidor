"""Con su rutina confirmada, la fuerza no se recorta sin una razón de verdad (auditoría del 2026-09-24).

Semana real del atleta 2 del 2026-10-05: Upper B "dosis reducida" y sin extensión de tríceps "para
ajustar la sesión al tiempo disponible" (la decisión le dio 70' a una sesión de ~120' que el atleta
nunca limitó), y Lower A reducida "porque vienes del umbral del miércoles y el domingo hay salida
larga". Cada semana, su pierna iba recortada.

Réplicas del paso de fuerza (gpt-6-luna) con la regla nueva y sin los minutos de la decisión:
- esa misma semana: 3 de 3 corridas, dosis normal en los tres días y sin cambios;
- la semana de después de su 5K: 3 de 3 siguen bajando la dosis, "vienes de competir".
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

pf = importlib.import_module("services.ai.langgraph.nodes.paso_de_fuerza")
NODO = (Path(__file__).resolve().parents[1] / "services/ai/langgraph/nodes/weekly_planner_node.py").read_text(
    encoding="utf-8")


class SuRutina(unittest.TestCase):
    def test_la_regla_de_la_dosis_pide_una_razon(self):
        texto = next(v for v in vars(pf).values() if isinstance(v, str) and "La dosis normal es la norma" in v)
        self.assertIn("el afinamiento o la vuelta de una carrera", texto)
        self.assertIn("NO es razón", texto)
        self.assertIn("Schumann 2022", texto)

    def test_la_decision_no_le_pone_los_minutos(self):
        self.assertIn('"minutes": None, "focus": d.focus', NODO)

    def test_sin_minutos_dura_lo_de_su_programa(self):
        self.assertEqual(pf.minutos_de({"minutes": 120}, "normal", None), 120)
        self.assertEqual(pf.minutos_de({"minutes": 120}, "reducida", None) < 120, True)


if __name__ == "__main__":
    unittest.main()
