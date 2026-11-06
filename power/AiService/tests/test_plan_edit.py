"""Mover una sesión, en el puente (Beta 3.0, F6).

`/bridge/plan-edit` sólo calcula (PHP guarda el plan) y contesta 422 con el motivo para el
atleta. `/bridge/garmin-ledger-move` lleva los ids del registro de subidas a sus claves
nuevas, y NO lo hace con una generación o una subida de ese atleta en marcha: las dos
escriben el mismo registro, y la que terminara después pisaría a la otra.

Correr con:  python -m pytest AiService/tests -q
"""

import json
import sys
import tempfile
import time
import unittest
from pathlib import Path
from unittest import mock

from fastapi.testclient import TestClient

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import main  # noqa: E402

ATLETA = 13


def _dia(fecha, sport="run", clase="easy", foco="Rodaje", rest=False):
    return {"date": fecha, "day_name_es": "x", "is_rest": rest, "sport": sport, "focus": foco, "purpose": "-",
            "adaptation": "-", "steps": [], "total_duration_minutes": 0 if rest else 40, "intensity_class": clase}


PLAN = {"athlete_name": "x", "start_date": "2026-10-12", "zones_table": "", "weeks": [{"week_number": 1, "days": [
    _dia("2026-10-13", "rest", "rest", "Descanso", rest=True), _dia("2026-10-14", clase="tempo_threshold", foco="Ritmo 10K"),
    _dia("2026-10-17", clase="race_day", foco="10K")]}]}


class ElPuente(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self._tmp.cleanup)
        raiz = Path(self._tmp.name)
        self.jobs, self.subidas, self.espejo = raiz / "jobs", raiz / "upload_jobs", raiz / f"u{ATLETA}"
        for d in (self.jobs, self.subidas, self.espejo):
            d.mkdir()
        (self.espejo / "garmin_uploads.json").write_text(json.dumps({"version": 1, "entries": [
            {"date": "2026-10-14", "session_slot": "unica", "workout_id": 555, "fingerprint": "x"}]}), "utf-8")
        for parche in (
            mock.patch.object(main, "JOBS_DIR", self.jobs),
            mock.patch.object(main, "UPLOAD_JOBS", self.subidas),
            mock.patch.object(main, "current_storage_dir", lambda: self.espejo),
            mock.patch.object(main, "_check_token", lambda _t: None),
        ):
            parche.start()
            self.addCleanup(parche.stop)
        self.cliente = TestClient(main.app)

    def test_calcula_sin_escribir(self):
        r = self.cliente.post("/bridge/plan-edit", json={"user_id": ATLETA, "weekly_plan": PLAN,
                                                         "session_key": "2026-10-14|unica", "to_date": "2026-10-13",
                                                         "today": "2026-10-11"})
        self.assertEqual(r.status_code, 200)
        self.assertEqual(r.json()["data"]["cambios_registro"], {"2026-10-14|unica": "2026-10-13|unica"})
        registro = json.loads((self.espejo / "garmin_uploads.json").read_text("utf-8"))
        self.assertEqual(registro["entries"][0]["date"], "2026-10-14", "calcular no toca el registro")

    def test_la_carrera_contesta_con_su_motivo(self):
        r = self.cliente.post("/bridge/plan-edit", json={"user_id": ATLETA, "weekly_plan": PLAN,
                                                         "session_key": "2026-10-17|unica", "to_date": "2026-10-13",
                                                         "today": "2026-10-11"})
        self.assertEqual(r.status_code, 422)
        self.assertIn("carrera", r.json()["detail"])

    def test_mueve_el_registro(self):
        r = self.cliente.post("/bridge/garmin-ledger-move",
                              json={"user_id": ATLETA, "cambios": {"2026-10-14|unica": "2026-10-13|unica"}})
        self.assertEqual(r.status_code, 200)
        [e] = json.loads((self.espejo / "garmin_uploads.json").read_text("utf-8"))["entries"]
        self.assertEqual((e["date"], e["workout_id"], e["scheduled_date"]), ("2026-10-13", 555, "2026-10-14"))

    def test_no_con_una_subida_en_marcha(self):
        d = self.subidas / time.strftime("%Y%m%d%H%M%S000000")
        d.mkdir()
        (d / "status.json").write_text(json.dumps({"user_id": ATLETA, "running": True, "done": False}), "utf-8")
        r = self.cliente.post("/bridge/garmin-ledger-move",
                              json={"user_id": ATLETA, "cambios": {"2026-10-14|unica": "2026-10-13|unica"}})
        self.assertEqual(r.status_code, 409)
        [e] = json.loads((self.espejo / "garmin_uploads.json").read_text("utf-8"))["entries"]
        self.assertEqual(e["date"], "2026-10-14")


if __name__ == "__main__":
    unittest.main()
