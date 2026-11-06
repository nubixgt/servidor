"""Mientras un atleta genera, nadie mas le pisa el espejo.

## El fallo

`worker.py` escribe el espejo del atleta (`_runtime/storage/u<id>/`), corre el
grafo cinco a ocho minutos -- que escribe la semana NUEVA en ese mismo
directorio -- y al final lee el resultado con `read_back`. Durante esa ventana,
`/bridge/charts` (que la app pide al abrir Inicio), `/bridge/coach-chat` y la
comprobacion de ventana de `/bridge/generate-week` reescribian el mismo
`weekly_plan.json` con lo que tiene MySQL: el plan ANTERIOR.

Si caia entre la escritura del grafo y `read_back`, el worker devolvia el plan
viejo y PHP lo guardaba como la generacion recien pagada. Sin error en ninguna
parte. Encontrado leyendo el codigo en la auditoria del 2026-09-13: lo que la
lista de pendientes llamaba "choque improbable" tenia a Inicio como disparador.

Correr con:  cd AiService && python -m pytest tests -q
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

ATLETA = 7
SEMANA_NUEVA = {"athlete_name": "x", "weeks": [{"week_number": 2, "days": [], "marca": "NUEVA"}]}
SEMANA_VIEJA = {"athlete_name": "x", "weeks": [{"week_number": 1, "days": [], "marca": "VIEJA"}]}


def _generacion_en_marcha(jobs: Path, user_id: int) -> None:
    d = jobs / time.strftime("%Y%m%d%H%M%S000000")
    d.mkdir(parents=True)
    (d / "spec.json").write_text(json.dumps({"user_id": user_id, "kind": "week"}), "utf-8")
    (d / "status.json").write_text(
        json.dumps({"user_id": user_id, "kind": "week", "running": True, "done": False}), "utf-8"
    )


class ElEspejoEsDeLaGeneracion(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self._tmp.cleanup)
        raiz = Path(self._tmp.name)
        self.jobs = raiz / "jobs"
        self.jobs.mkdir()
        self.espejo = raiz / f"u{ATLETA}"
        self.espejo.mkdir()
        # Lo que el grafo ya escribio: la semana nueva, todavia sin recoger.
        (self.espejo / "weekly_plan.json").write_text(json.dumps(SEMANA_NUEVA), "utf-8")

        for parche in (
            mock.patch.object(main, "JOBS_DIR", self.jobs),
            mock.patch.object(main, "current_storage_dir", lambda: self.espejo),
            mock.patch.object(main, "_check_token", lambda _t: None),
        ):
            parche.start()
            self.addCleanup(parche.stop)
        self.cliente = TestClient(main.app)

    def _semana_en_el_espejo(self) -> str:
        return json.loads((self.espejo / "weekly_plan.json").read_text("utf-8"))["weeks"][0]["marca"]

    def test_abrir_inicio_no_pisa_la_semana_que_se_esta_generando(self):
        _generacion_en_marcha(self.jobs, ATLETA)
        self.cliente.post("/bridge/charts", json={
            "user_id": ATLETA, "profile": {}, "weekly_plan": SEMANA_VIEJA,
        })
        self.assertEqual(self._semana_en_el_espejo(), "NUEVA",
                         "charts reescribio el espejo en mitad de una generacion: read_back devolveria el plan viejo")

    def test_generar_otra_vez_contesta_409_sin_tocar_el_espejo(self):
        _generacion_en_marcha(self.jobs, ATLETA)
        r = self.cliente.post("/bridge/generate-week", json={
            "user_id": ATLETA, "profile": {}, "garmin_password": "x", "previous_weekly_plan": SEMANA_VIEJA,
        })
        self.assertEqual(r.status_code, 409)
        self.assertEqual(self._semana_en_el_espejo(), "NUEVA")

    def test_sin_generacion_el_espejo_sigue_a_mysql(self):
        """La regla del espejo no cambia cuando nadie genera: lo que MySQL tiene
        es lo que queda, y una semana borrada no puede sobrevivir aqui."""
        self.cliente.post("/bridge/charts", json={
            "user_id": ATLETA, "profile": {}, "weekly_plan": SEMANA_VIEJA,
        })
        self.assertEqual(self._semana_en_el_espejo(), "VIEJA")

    def test_la_generacion_de_otro_atleta_no_bloquea_a_este(self):
        _generacion_en_marcha(self.jobs, ATLETA + 1)
        self.cliente.post("/bridge/charts", json={
            "user_id": ATLETA, "profile": {}, "weekly_plan": SEMANA_VIEJA,
        })
        self.assertEqual(self._semana_en_el_espejo(), "VIEJA")


if __name__ == "__main__":
    unittest.main()
