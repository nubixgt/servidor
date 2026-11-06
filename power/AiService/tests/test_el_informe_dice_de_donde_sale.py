"""El informe de análisis dice de dónde sale su umbral, también al volver a abrirlo.

Auditoría del 2026-09-23, informe del atleta 2 abierto desde Informes: la tarjeta decía
"5:43/km · de tu Garmin" justo encima del aviso "Se ajustó el ritmo umbral de Garmin
(5:53/km)". Al generar, el cerebro pinta las tarjetas con `formatter_node._headline_stats`,
que ya decía "ajustado desde el de tu Garmin"; pero Informes lo vuelve a pintar desde la
traza (`main._informe_de_analisis`), y ahí había una COPIA de las tarjetas que ponía
siempre "de tu Garmin" y "Carga crónica" (son horas, no la carga de Garmin). Es el fallo
del informe de Hugo (2026-09-07), vuelto a entrar por la segunda copia.

Correr con:  python -m pytest AiService/tests -q
"""

import sys
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(RAIZ / "PaginaLocal"))

from services.ai.langgraph.nodes.formatter_node import _headline_stats  # noqa: E402

MAIN = (RAIZ / "AiService" / "main.py").read_text(encoding="utf-8")


class UnaSolaFuenteDeLasTarjetas(unittest.TestCase):
    def test_el_puente_usa_la_funcion_del_cerebro(self):
        self.assertIn("from services.ai.langgraph.nodes.formatter_node import _headline_stats", MAIN)
        self.assertNotIn('"note": "de tu Garmin"', MAIN)
        self.assertNotIn('"label": "Carga crónica"', MAIN)

    def test_el_umbral_ajustado_se_dice_ajustado(self):
        """El resumen real guardado del atleta 2: 343,1 s/km, procedencia garmin_ajustado, 6,1 h."""
        tarjetas = _headline_stats({
            "prescription": {"threshold_pace_sec_per_km": 343.1, "threshold_pace_source": "garmin_ajustado"},
            "availability_constraints": {"volume_baseline": {"chronic_weekly_hours": 6.1}},
            "adherence": {},
        })
        self.assertEqual(tarjetas[0], {"label": "Ritmo umbral", "value": "5:43/km",
                                       "note": "ajustado desde el de tu Garmin"})
        self.assertEqual(tarjetas[1]["label"], "Volumen semanal")


if __name__ == "__main__":
    unittest.main()
