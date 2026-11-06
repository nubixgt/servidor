"""Un día sin anotar lo que bebe no es "0 L" (auditoría del 2026-09-24).

Semana del atleta 2 del 2026-10-05: el experto de fisiología escribió "en 8/16 días la ingesta figura
como 0 L pese a pérdida por sudor (p. ej., 0 L y 1,70 L el 2026-09-12)". Garmin devuelve
`valueInML: 0` los días en que no se anota nada; el extractor lo pasaba como un dato.
"""

import sys
import unittest
from datetime import date
from pathlib import Path
from unittest.mock import MagicMock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.garmin.data_extractor import TriathlonCoachDataExtractor  # noqa: E402


class _Falso:
    def __init__(self, hidratacion):
        self.garmin = MagicMock()
        self._h = hidratacion

    def _call_api(self, fn, *args, default=None, what=""):
        if what.startswith("get_hydration_data"):
            return self._h.get(args[0], default)
        return default


class LaHidratacion(unittest.TestCase):
    def test_cero_es_sin_anotar_y_lo_anotado_se_conserva(self):
        falso = _Falso({"2026-09-12": {"calendarDate": "2026-09-12", "valueInML": 0, "sweatLossInML": 1700},
                        "2026-09-13": {"calendarDate": "2026-09-13", "valueInML": 2300, "sweatLossInML": 600}})
        marcadores = TriathlonCoachDataExtractor.get_body_metrics(falso, date(2026, 9, 12), date(2026, 9, 13))
        por_dia = {h["date"]: h for h in marcadores.hydration}
        self.assertIsNone(por_dia["2026-09-12"]["intake"])
        self.assertEqual(por_dia["2026-09-12"]["sweat_loss"], 1.7)
        self.assertEqual(por_dia["2026-09-13"]["intake"], 2.3)


if __name__ == "__main__":
    unittest.main()
