"""La decisión escribe cada bloque con sus minutos; el codificador no resta (2026-09-24).

Banco del codificador (14 semanas reales del atleta 2 y 3): los dos modelos fallaban donde la
decisión escribía «Completa el resto en Z2» o «120' en Z2 continuo. Dentro de la salida, 4x(5'…)».
Haiku codificó ese 120' como 30' (y otro "resto" de 120' como 85'); gemini-3.5-flash-lite metió
114' en "el tiempo restante" de una bici de 135' (193' en total). El contrato pedía una duración por
repetición pero no prohibía la resta.

Réplica de la decisión (gpt-5.2) sobre los 3 prompts reales que la tenían (2026-09-23 10:04, 17:49 y
21:21), con la regla nueva: 0 de 3 escriben "resto" o "dentro", y los bloques suman la sesión
("10' Z1–Z2, 6x(3' cadencia alta + 2' Z1), 20' Z2, 10' Z1" = 70'; "15', 3x(12' Z3 + 6' Z2), 55' Z2,
10' Z1" = 134'). 0,55 USD.
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import DayIntent  # noqa: E402


class LaReglaEstaDondeLaLeeLaDecision(unittest.TestCase):
    def test_en_el_prompt(self):
        fuente = (Path(__file__).resolve().parents[1]
                  / "services/ai/langgraph/nodes/weekly_planner_node.py").read_text(encoding="utf-8")
        self.assertIn("Write the blocks in order, each with its own minutes or metres, adding up to the session", fuente)
        self.assertIn("«completa el resto en Z2»", fuente)

    def test_en_el_esquema(self):
        texto = DayIntent.model_json_schema()["properties"]["session_sketch"]["description"]
        self.assertIn("never 'complete the rest'", texto)
        self.assertIn("The formatting pass does not subtract", texto)


if __name__ == "__main__":
    unittest.main()
