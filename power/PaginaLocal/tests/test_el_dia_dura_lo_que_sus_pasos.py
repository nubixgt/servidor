"""El día dura lo que suman sus pasos, salvo un error de unidad, que es del validador (2026-09-24).

Bici del atleta 2 del 2026-10-11, vista por el responsable en su perfil: "135 min" en la cabecera y
20' + 3x(12' + 6') + 65' + 5' = 144' en el reloj. La decisión escribió "completa el tiempo restante"
y el codificador restó mal. La causa va en el contrato de la decisión; esto deja una sola duración.

Y no puede tapar el caso grave del banco del codificador: 6 x 50' donde iban 6 x 1' (332' en un día
de 70'). Ese exceso lo ve `duration_mismatch` del validador; igualar el día antes se lo escondería.
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import pasos_que_no_suman_el_dia  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import WorkoutDay, WorkoutStep  # noqa: E402


def _paso(seg, n=1, tipo="main"):
    return WorkoutStep(step_order=1, step_type=tipo, duration_type="time", duration_value=seg, repeat_count=n,
                       description="x")


def _serie(n, trabajo, rec):
    return WorkoutStep(step_order=2, step_type="repeat", repeat_count=n, description="x",
                       substeps=[_paso(trabajo, tipo="interval"), _paso(rec, tipo="recovery")])


def _dia(total, pasos):
    return WorkoutDay(date="2026-10-11", day_name_es="Domingo", is_rest=False, purpose="", adaptation="",
                      sport="bike", focus="fondo + Z3",
                      total_duration_minutes=total, steps=pasos)


class LaBiciDelDomingo(unittest.TestCase):
    def test_la_cabecera_dice_lo_que_hace_el_reloj(self):
        d = _dia(135, [_paso(1200), _serie(3, 720, 360), _paso(3900), _paso(300)])
        avisos = pasos_que_no_suman_el_dia([d])
        self.assertEqual(d.total_duration_minutes, 144)
        self.assertIn("los pasos suman 144'", avisos[0])

    def test_un_redondeo_no_se_toca(self):
        d = _dia(60, [_paso(3480)])  # 58'
        self.assertEqual(pasos_que_no_suman_el_dia([d]), [])
        self.assertEqual(d.total_duration_minutes, 60)

    def test_el_error_de_unidad_se_deja_al_validador(self):
        d = _dia(70, [_paso(600), _serie(6, 3000, 120), _paso(600)])  # 332'
        avisos = pasos_que_no_suman_el_dia([d])
        self.assertEqual(d.total_duration_minutes, 70)
        self.assertIn("se deja al validador", avisos[0])


if __name__ == "__main__":
    unittest.main()
