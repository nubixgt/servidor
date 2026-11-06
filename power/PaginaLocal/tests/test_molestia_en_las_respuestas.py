"""Una molestia contada en las respuestas al coach pesa lo que el atleta dijo (Beta 3.0, F10; A10).

Jessie (2026-09-15) contestó «muy ligera molestia en el aquiles» a una pregunta del coach y su semana
la citó en TODAS las sesiones. La proporción al dolor sólo existía para la molestia del check-in; las
respuestas llegaban a la decisión sin ninguna regla. `preguntas_del_coach.py` no se toca: la regla se
añade en el nodo que recibe las respuestas, y sólo si dicen que algo molesta.

Réplicas del 2026-10-11 sobre la decisión REAL de Jessie del 2026-09-24 (gpt-5.2), cambiando sólo su
respuesta 1 por «Bien, aunque tengo una muy ligera molestia en el aquiles» (la pregunta real era «¿Alguna
molestia que siga hoy?»): sin la nota, el aquiles en 8 de 8 sesiones (también las tres fuerzas y la bici);
con la nota, en 3 de 7 (tempo, rodaje y tirada larga). 0,09 + 0,12 USD.

Respuesta real del atleta 2 en su prompt del 2026-10-09: «Igual que antes: peroneo 1/10 y psoas 1/10,
no suben al día siguiente».

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import niggle_constraints as nc  # noqa: E402

NODO = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")


class QueCuentaComoMolestia(unittest.TestCase):

    def test_la_de_jessie_es_leve_por_como_la_describe(self):
        m = nc.molestias_en_respuestas("1. muy ligera molestia en el aquiles")
        self.assertEqual(len(m), 1)
        self.assertEqual((m[0]["dolor"], m[0]["dice_que_es_leve"]), (None, True))
        nota = nc.nota_de_molestias_en_respuestas("1. muy ligera molestia en el aquiles")
        self.assertIn("dos o tres sesiones que MÁS cargan", nota)
        self.assertIn("«muy ligera molestia en el aquiles»", nota)

    def test_la_del_atleta_2_es_leve_por_su_numero(self):
        m = nc.molestias_en_respuestas("2. Igual que antes: peroneo 1/10 y psoas 1/10, no suben al día siguiente.")
        self.assertEqual(m[0]["dolor"], 1)

    def test_lo_que_no_es_una_molestia_no_añade_nada(self):
        for texto in ("1. (no contestó; decide con los datos)", "Sin molestias, todo bien", "No me duele nada",
                      "A tope, quiero competir.", "Ya pasó la molestia del gemelo", ""):
            self.assertEqual(nc.nota_de_molestias_en_respuestas(texto), "", texto)

    def test_una_alta_o_sin_numero_no_se_da_por_leve(self):
        self.assertIn("Pesa en proporción", nc.nota_de_molestias_en_respuestas("Me duele la rodilla, un 5/10"))
        self.assertIn("pídeselo en el racional", nc.nota_de_molestias_en_respuestas("Me duele la rodilla al bajar"))

    def test_no_supone_el_genero(self):
        nota = nc.nota_de_molestias_en_respuestas("muy ligera molestia en el aquiles")
        self.assertNotIn(" él ", f" {nota} ")
        self.assertNotIn("Él ", nota)


class ElNodo(unittest.TestCase):

    def test_solo_lee_las_respuestas_y_solo_añade_si_hay_molestia(self):
        con = [{"role": "assistant", "content": "Antes de planificar le pregunté al atleta:\n1. ¿Cómo estás?"},
               {"role": "user", "content": "Respuestas del atleta:\n1. muy ligera molestia en el aquiles"}]
        self.assertEqual(len(NODO.nota_de_molestias_de_las_respuestas(con)), 1)
        sin = [{"role": "assistant", "content": "Antes de planificar le pregunté al atleta:\n1. ¿Molestias?"},
               {"role": "user", "content": "Respuestas del atleta:\n1. Ninguna, todo bien"}]
        self.assertEqual(NODO.nota_de_molestias_de_las_respuestas(sin), [])
        self.assertEqual(NODO.nota_de_molestias_de_las_respuestas([]), [])


if __name__ == "__main__":
    unittest.main()
