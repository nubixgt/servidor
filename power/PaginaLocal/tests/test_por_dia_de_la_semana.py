"""«Qué días cumples» cuenta sesiones, no fechas (revisión del 2026-10-22).

El expediente del atleta 2, semana del 12 al 18 de octubre: el lunes 12 tenía dos sesiones (soltura + chispa
y fuerza) y el viernes 16 otras dos (activación y natación). `by_weekday` guardó «lunes 2 de 2» y «viernes 2 de
2» porque marcaba hecha la FECHA en cuanto se hacía una de sus sesiones; la misma línea decía 3 hechas y 3
perdidas en total. Progreso pintaba esos días al 100 % y el Historial, con las marcas de Garmin, enseñaba ✗.

Correr con:  python -m unittest tests.test_por_dia_de_la_semana -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.track_record import summarise_week  # noqa: E402


def _dia(fecha, slot, sport):
    return {"date": fecha, "session_slot": slot, "sport": sport, "is_rest": False, "total_duration_minutes": 30}


class CadaSesionCuenta(unittest.TestCase):

    def test_un_dia_de_dos_sesiones_con_una_hecha(self):
        semana = {"days": [_dia("2026-10-12", "am", "run"), _dia("2026-10-12", "pm", "strength"),
                           _dia("2026-10-16", "am", "run"), _dia("2026-10-16", "pm", "swim")]}
        adherencia = {"sessions": [
            {"date": "2026-10-12", "status": "modified"}, {"date": "2026-10-12", "status": "missed"},
            {"date": "2026-10-16", "status": "missed"}, {"date": "2026-10-16", "status": "completed"},
        ]}
        linea = summarise_week(semana, adherencia)
        self.assertEqual(linea["by_weekday"], {"lunes": [2, 1], "viernes": [2, 1]})

    def test_lo_pendiente_no_cuenta(self):
        semana = {"days": [_dia("2026-10-18", "unica", "run")]}
        linea = summarise_week(semana, {"sessions": [{"date": "2026-10-18", "status": "pending"}]})
        self.assertFalse(linea["by_weekday"])


if __name__ == "__main__":
    unittest.main()
