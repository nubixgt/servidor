"""Las respuestas a las preguntas del coach llegan al job que las espera, y a ningún otro.

El worker de cada generación publica sus preguntas en status.json y espera un
`answers.json` en SU carpeta (ver ProveedorWeb en worker.py). La ruta que lo
escribe recibe el `job_id` desde el cliente, y un identificador que viene de
fuera nunca basta por sí solo para escribir en una carpeta: tiene que ser un
job de ESE atleta y que esté esperando de verdad.

Correr con:  python -m pytest tests -q
"""

import json
import os
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
os.environ.setdefault("BRIDGE_SERVICE_TOKEN", "test-token")

from fastapi.testclient import TestClient  # noqa: E402

import main  # noqa: E402

CLIENTE = TestClient(main.app)
CABECERA = {"X-Service-Token": os.environ["BRIDGE_SERVICE_TOKEN"]}


class LasRespuestasVanAlJobQueLasEspera(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self._tmp.cleanup)
        self.raiz = Path(self._tmp.name)
        self._jobs_antes = main.JOBS_DIR
        main.JOBS_DIR = self.raiz
        self.addCleanup(lambda: setattr(main, "JOBS_DIR", self._jobs_antes))

    def _job(self, job_id, user_id, esperando=True):
        d = self.raiz / job_id
        d.mkdir()
        (d / "status.json").write_text(json.dumps({
            "job_id": job_id, "user_id": user_id, "running": True, "done": False,
            "awaiting_answers": esperando,
            "questions": [{"pregunta": "¿Cómo te encontraste tras las series?", "por_que": "HRV -20%"}],
        }), encoding="utf-8")
        return d

    def _enviar(self, job_id, user_id, respuestas):
        return CLIENTE.post("/bridge/coach-answers", headers=CABECERA,
                            json={"user_id": user_id, "job_id": job_id, "answers": respuestas})

    def test_el_atleta_contesta_a_su_propio_job(self):
        d = self._job("20260911120000", 2)
        r = self._enviar("20260911120000", 2, ["Cansado dos días"])
        self.assertEqual(r.status_code, 200)
        self.assertEqual(json.loads((d / "answers.json").read_text("utf-8"))["answers"], ["Cansado dos días"])

    def test_nadie_escribe_en_el_job_de_otro(self):
        d = self._job("20260911120000", 2)
        r = self._enviar("20260911120000", 7, ["no es mío"])
        self.assertEqual(r.status_code, 409)
        self.assertFalse((d / "answers.json").exists())

    def test_un_job_que_ya_no_espera_no_recibe_nada(self):
        d = self._job("20260911120000", 2, esperando=False)
        self.assertEqual(self._enviar("20260911120000", 2, ["tarde"]).status_code, 409)
        self.assertFalse((d / "answers.json").exists())

    def test_el_estado_lleva_las_preguntas_mientras_espera(self):
        self._job("20260911120000", 2)
        datos = CLIENTE.get("/bridge/generate-status", params={"user_id": 2}, headers=CABECERA).json()["data"]
        self.assertTrue(datos["awaiting_answers"])
        self.assertEqual(datos["questions"][0]["por_que"], "HRV -20%")


if __name__ == "__main__":
    unittest.main()
