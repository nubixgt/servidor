"""El macrociclo no le dice al atleta "firme", "provisional" ni "preliminar" (2026-10-09).

El responsable, leyendo su macrociclo de producción: esas palabras confunden. El prompt decía
"sólo las primeras 16 semanas son firmes; las demás, un mapa provisional" y el modelo lo repetía
en el texto que lee el atleta. Ahora pide escribir primero las fases de esas semanas y, si hay
más, la línea «Fases posteriores por confirmar según tu progresión:». Y la fuerza: "esa intención
es TUYA" iba a todos, también a quien declaró para qué hace gimnasio.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.season_planner_node import (  # noqa: E402
    SEASON_PLANNER_USER_PROMPT,
    palabras_de_duda,
)


class ElPromptDelMacrociclo(unittest.TestCase):
    def test_pide_el_separador_y_prohibe_las_palabras(self):
        self.assertIn("**Fases posteriores por confirmar según tu\n  progresión:**", SEASON_PLANNER_USER_PROMPT)
        self.assertIn('Never label a phase or a week\n  "firme", "provisional", "preliminar"', SEASON_PLANNER_USER_PROMPT)
        self.assertNotIn("are a provisional map", SEASON_PLANNER_USER_PROMPT)
        self.assertNotIn("weeks are firm.", SEASON_PLANNER_USER_PROMPT)

    def test_la_fuerza_declarada_es_del_atleta(self):
        self.assertNotIn("that intent belongs to YOU", SEASON_PLANNER_USER_PROMPT)
        self.assertIn("that goal is theirs and every phase serves it", SEASON_PLANNER_USER_PROMPT)


class ElLintDelLog(unittest.TestCase):
    def test_las_ve_y_no_ve_de_mas(self):
        self.assertEqual(palabras_de_duda("Fase firme hasta la 16; lo demás es provisional y preliminar."),
                         ["firme", "preliminar", "provisional"])
        self.assertEqual(palabras_de_duda("**Fases posteriores por confirmar según tu progresión:**"), [])
        self.assertEqual(palabras_de_duda("cuando afirmes el ritmo"), [])


class ElAvisoDeLaSemana17(unittest.TestCase):
    def test_tampoco_dice_firmes(self):
        js = (Path(__file__).resolve().parents[2] / "Frontend/src/utils/macrociclo.js").read_text(encoding="utf-8")
        self.assertNotIn("eran firmes", js)
        self.assertIn("las fases siguientes quedaron por confirmar según tu progresión", js)


if __name__ == "__main__":
    unittest.main()
