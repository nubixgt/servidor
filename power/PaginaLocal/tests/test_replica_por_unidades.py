"""El banco de réplicas compara también UNIDADES (Beta 3.0, F10 / fase C).

Los fallos caros fueron de unidades con los minutos bien, y un banco que sólo suma minutos por deporte
no los ve. Los tres reales que la métrica tiene que contar:

- semana de la atleta 3 del 2026-09-14: «200m trote muy suave Z1» con `duration_type="time"` y 200
  (los metros en el campo de los segundos), y «15-20" progresivos» con 90 s;
- banco del codificador del 2026-09-24 (gemini-3.5-flash-lite): una bici de 6 × 1' codificada como
  6 × 50' (332' en el reloj por 70').

Sobre las 5 semanas reales codificadas por Haiku archivadas hoy (atletas 2 y 3, 103 pasos): 0.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import replay  # noqa: E402


def _paso(texto, tipo, valor, **extra):
    return {"description": texto, "duration_type": tipo, "duration_value": valor, **extra}


class LosFallosReales(unittest.TestCase):

    def test_los_tres_y_su_recuperacion(self):
        dias = [
            {"date": "2026-09-16", "sport": "run", "steps": [
                _paso("15-20\" progresivos, acelerando suave", "time", 90),
                _paso("400m a ritmo 5K", "distance", 400, recovery_duration_type="time",
                      recovery_duration_value=200, recovery_description="200m trote muy suave Z1"),
            ]},
            {"date": "2026-09-30", "sport": "bike", "steps": [_paso("1' fuerte a cadencia alta", "time", 3000)]},
        ]
        u = replay.unidades_que_no_cuadran(dias)
        self.assertEqual((u["pasos"], u["no_cuadran"]), (4, 3))
        self.assertTrue(any("son metros" in e and "recuperación" in e for e in u["ejemplos"]))

    def test_lo_que_cuadra_no_se_cuenta(self):
        dias = [{"date": "2026-10-14", "sport": "run", "steps": [
            _paso("10' calentamiento en Z1–Z2", "time", 600),
            _paso("3' a 5:20–5:28/km", "time", 180, recovery_duration_type="time", recovery_duration_value=180,
                  recovery_description="3' trote muy suave en Z1"),
            _paso("~1 h 50 min a tope", "time", 6600),
            _paso("200 m trote", "time", 120),
        ]}]
        self.assertEqual(replay.unidades_que_no_cuadran(dias)["no_cuadran"], 0,
                         "120 s para «200 m trote» es una aproximación del entrenador, no un fallo")

    def test_sin_pasos_no_hay_metrica_y_la_fuerza_no_cuenta(self):
        self.assertIsNone(replay.unidades_que_no_cuadran([{"date": "x", "sport": "strength", "steps": [
            _paso("3x8 sentadilla", "time", 900)]}]))
        self.assertIsNone(replay.unidades_que_no_cuadran([{"date": "x", "is_rest": True}]))

    def test_sale_en_la_tabla_del_banco(self):
        etiquetas = [e for e, _ in replay.COMPARISON_ROWS]
        self.assertIn("Pasos que el reloj llevaría distinto", etiquetas)
        resumen = replay.describe_output({"days": [{"date": "2026-09-30", "sport": "bike", "total_duration_minutes": 70,
                                                    "steps": [_paso("1' fuerte", "time", 3000)]}]})
        self.assertEqual(resumen["unidades"]["no_cuadran"], 1)


if __name__ == "__main__":
    unittest.main()
