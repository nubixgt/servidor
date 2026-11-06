"""La carga de Garmin cuando se genera de madrugada (auditoría del 2026-10-23).

La semana del atleta 2 del 26-oct se generó a las 05:13 del jueves 22: su `worker.log` dice
«acuteTrainingLoadDTO missing or invalid (type=NoneType)» y `load_state.json` llegó con la carga aguda, la
crónica, el rango óptimo, el estado de entrenamiento y el reparto de Garmin vacíos. A esa hora Garmin todavía no
tiene el estado del día. Pasó en 3 de sus 8 generaciones archivadas (24-sep 16:42 y 19:55, 25-sep 09:15,
22-oct 05:18); el 19-oct a las 18:38 sí llegó (aguda 201, crónica 279, ACWR 0,7). La decisión siguió con la suma
de sus actividades, dicha como tal: no mentía, pero perdía lo que Garmin sabe de él. Ahora, sin la carga de
hoy, se usa la de ayer y el bloque dice de qué día es.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from collections import OrderedDict
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.load_state import LoadState, _apply_training_status, render_load_state  # noqa: E402
from services.garmin.data_extractor import TriathlonCoachDataExtractor  # noqa: E402


def _con_carga(aguda, cronica, acwr):
    """La forma real de `get_training_status`: el estado va por dispositivo."""
    return {
        "mostRecentVO2Max": {"generic": {"vo2MaxValue": 51.0, "calendarDate": "2026-10-21"}},
        "mostRecentTrainingStatus": {"latestTrainingStatusData": {"3466720123": {
            "trainingStatus": 7, "trainingStatusFeedbackPhrase": "PRODUCTIVE_3",
            "acuteTrainingLoadDTO": {"dailyTrainingLoadAcute": aguda, "dailyTrainingLoadChronic": cronica,
                                     "dailyAcuteChronicWorkloadRatio": acwr, "acwrStatus": "OPTIMAL"},
        }}},
    }


# De madrugada: el dispositivo aparece, pero sin la carga del día.
SIN_CARGA = {
    "mostRecentVO2Max": {"generic": {"vo2MaxValue": 51.0, "calendarDate": "2026-10-21"}},
    "mostRecentTrainingStatus": {"latestTrainingStatusData": {"3466720123": {"trainingStatus": None}}},
}


class _Cliente:
    def __init__(self, por_dia):
        self.por_dia = por_dia
        self.pedidos = []

    def get_training_status(self, dia):
        self.pedidos.append(dia)
        return self.por_dia.get(dia, {})

    def get_training_readiness(self, dia):
        return []


def _extractor(por_dia):
    ex = TriathlonCoachDataExtractor.__new__(TriathlonCoachDataExtractor)
    ex.garmin = type("G", (), {"client": _Cliente(por_dia)})()
    ex._training_status_cache = OrderedDict()
    ex._training_status_cache_max = 16
    return ex


class LaCargaDeMadrugada(unittest.TestCase):

    def test_sin_la_de_hoy_se_usa_la_de_ayer(self):
        ex = _extractor({"2026-10-22": SIN_CARGA, "2026-10-21": _con_carga(201, 279, 0.7)})
        carga = ex.get_training_status(date(2026, 10, 22)).acute_training_load
        self.assertEqual((carga["acute_load"], carga["chronic_load"], carga["acwr"]), (201.0, 279.0, 0.7))
        self.assertEqual(carga["load_date"], "2026-10-21")

    def test_con_la_de_hoy_no_se_pide_la_de_ayer(self):
        ex = _extractor({"2026-10-19": _con_carga(201, 279, 0.7)})
        carga = ex.get_training_status(date(2026, 10, 19)).acute_training_load
        self.assertEqual((carga["acute_load"], carga["load_date"]), (201.0, "2026-10-19"))
        self.assertEqual(ex.garmin.client.pedidos, ["2026-10-19"])

    def test_si_tampoco_esta_la_de_ayer_se_sigue_sin_ella(self):
        """Se abstiene: sin carga de Garmin, la semana usa la suma de sus actividades, como antes."""
        ex = _extractor({"2026-10-22": SIN_CARGA})
        carga = ex.get_training_status(date(2026, 10, 22)).acute_training_load
        self.assertIsNone(carga["acute_load"])
        self.assertEqual(carga["load_date"], "2026-10-22")

    def test_el_bloque_dice_el_dia_de_la_carga(self):
        ex = _extractor({"2026-10-22": SIN_CARGA, "2026-10-21": _con_carga(201, 279, 0.7)})
        estado = LoadState(as_of="2026-10-22")
        _apply_training_status(estado, ex.get_training_status(date(2026, 10, 22)))
        texto = render_load_state(estado)
        self.assertIn("ACWR de Garmin** (media exponencial), 2026-10-21", texto)


if __name__ == "__main__":
    unittest.main()
