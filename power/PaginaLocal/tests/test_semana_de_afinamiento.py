"""Una semana de afinamiento no hereda el promedio de su fase.

## El fallo, con los números reales

Macrociclo del atleta 2 del 2026-09-10: Fase 1, semanas 1 a 4, **6.0 h/semana**,
descarga sólo en la 4. El 5k Monterrico (prioridad B) cae el 20-sep, en la
semana 2, y el propio texto del macrociclo decía *"enfocada en el taper y
ejecución del 5k Monterrico"*.

Pero `weekly_hours_target` es UN número por fase, y la semana 1 (11 al 17 de
septiembre) lo recibió así:

    Objetivo: **6.0h** de entrenamiento planeado en total esta semana.
    El plan de temporada fija ~6.0h/semana para esta fase ... Manda el plan de temporada.

junto a una restricción que decía *"Hit the prescribed weekly volume ...
Distribute it across the week's sessions"*. El planificador escribió en su
racional **"estamos en Fase 1 con objetivo de ~6.0h; la semana queda en ~5h55"**
sabiendo que estaba **"a 4-9 días del 5k Monterrico"**. Relleno hasta el número.
El codificador sumó 6,8 h, el validador lo cazó y se pagó una reparación
completa con gpt-5.2.

## Qué se fija aquí

1. El código sabe cuándo una semana cae en el afinamiento de una carrera.
2. En esa semana el número deja de presentarse como lo que toca.
3. El prompt ya no le pide al modelo que rellene el volumen, en ninguna semana.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    TRAINING_DECISION_USER_PROMPT,
    _format_volume_target,
    carrera_en_afinamiento,
    compute_weekly_volume_target,
)

SEMANA_1 = [{"date": f"2026-09-{d}"} for d in range(11, 18)]
MONTERRICO = {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B"}
EL_10K = {"name": "10K", "date": "2027-04-20", "priority": "A"}
BASE_REAL = {"chronic_weekly_hours": 6.6, "last_7d_actual_hours": 8.5,
             "previous_week_planned_hours": None}
FASE_1 = {"week_number": 1, "phase_name": "Transición y Recuperación Activa",
          "is_deload": False, "weekly_hours_target": 6.0, "intensity_emphasis": ""}


class ElCodigoVeLaCarrera(unittest.TestCase):
    def test_el_caso_real_monterrico_cae_en_el_afinamiento_de_la_semana_1(self):
        """Carrera B a 9 días del inicio de la semana."""
        c = carrera_en_afinamiento([MONTERRICO, EL_10K], SEMANA_1)
        self.assertIsNotNone(c)
        self.assertEqual(c["name"], "5k Monterrico")
        self.assertEqual(c["days_from_week_start"], 9)

    def test_una_carrera_lejana_no_es_afinamiento(self):
        self.assertIsNone(carrera_en_afinamiento([EL_10K], SEMANA_1))

    def test_una_A_se_afina_antes_que_una_B(self):
        """A 16 días: una A ya está afinando, una B todavía no."""
        lejos = {"date": "2026-09-27"}
        self.assertIsNotNone(carrera_en_afinamiento([{**lejos, "priority": "A"}], SEMANA_1))
        self.assertIsNone(carrera_en_afinamiento([{**lejos, "priority": "B"}], SEMANA_1))

    def test_una_carrera_ya_pasada_no_cuenta(self):
        self.assertIsNone(carrera_en_afinamiento([{"date": "2026-09-05", "priority": "A"}], SEMANA_1))

    def test_datos_raros_no_revientan(self):
        self.assertIsNone(carrera_en_afinamiento([{"date": ""}, "x", None], SEMANA_1))
        self.assertIsNone(carrera_en_afinamiento([MONTERRICO], []))


class ElNumeroDejaDeSerLoQueToca(unittest.TestCase):
    def setUp(self):
        self.t = compute_weekly_volume_target(BASE_REAL, FASE_1,
                                              competitions=[MONTERRICO], week_dates=SEMANA_1)
        self.bloque = _format_volume_target(self.t)

    def test_el_tope_no_sube_sobre_una_semana_normal(self):
        self.assertLessEqual(self.t["target_hours"], 6.0)

    def test_el_bloque_nombra_la_carrera_y_dice_que_la_fase_no_describe_esta_semana(self):
        self.assertIn("5k Monterrico", self.bloque)
        self.assertIn("describe una semana normal, no una de afinamiento", self.bloque)

    def test_ya_no_dice_manda_el_plan(self):
        self.assertNotIn("Manda el plan de temporada", self.bloque)

    def test_sin_carrera_la_semana_sigue_como_siempre(self):
        normal = compute_weekly_volume_target(BASE_REAL, FASE_1,
                                              competitions=[EL_10K], week_dates=SEMANA_1)
        self.assertIsNone(normal["race_taper"])
        self.assertEqual(normal["target_hours"], 6.0)


class ElPromptYaNoPideRellenar(unittest.TestCase):
    """El numero SIEMPRE fue un techo; se presentaba como objetivo."""

    def test_el_bloque_es_un_techo_y_no_un_objetivo(self):
        bloque = _format_volume_target(compute_weekly_volume_target(BASE_REAL, FASE_1))
        self.assertIn("no un objetivo", bloque)
        self.assertNotIn("Objetivo:", bloque)
        self.assertNotIn("**", bloque)
        self.assertNotIn("Volumen prescrito", bloque)

    def test_la_restriccion_ya_no_manda_distribuir_el_volumen(self):
        self.assertNotIn("Hit the prescribed weekly volume", TRAINING_DECISION_USER_PROMPT)
        self.assertNotIn("Distribute it across", TRAINING_DECISION_USER_PROMPT)
        self.assertIn("No number below is a quota", TRAINING_DECISION_USER_PROMPT)

    def test_el_afinamiento_es_de_la_SEMANA_no_del_dia_anterior(self):
        """La regla vieja decia "reduce that day's volume": el modelo bajo un
        dia y recoloco el volumen en otros para llegar al numero."""
        self.assertIn("the WEEK's", TRAINING_DECISION_USER_PROMPT)
        self.assertIn("is not relocated to another", " ".join(TRAINING_DECISION_USER_PROMPT.split()))

    def test_ningun_ejemplo_del_prompt_trae_una_lesion(self):
        """"cuidando rodilla izquierda" iba en el ejemplo de fuerza de TODOS."""
        self.assertNotIn("rodilla izquierda", TRAINING_DECISION_USER_PROMPT)


if __name__ == "__main__":
    unittest.main()
