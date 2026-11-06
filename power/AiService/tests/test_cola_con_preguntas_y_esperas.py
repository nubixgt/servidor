"""La cola cuenta la memoria, y un trabajo en cola sigue siendo del atleta.

Auditoría del 2026-09-15, antes del relanzamiento:

1. Un worker esperando las respuestas del coach no contaba para el tope, pero
   su proceso (~200 MB) seguía vivo y al reanudar no miraba el tope: con
   MAX_CONCURRENT_GENERATIONS=2 y tres atletas con preguntas corrían cinco
   grafos en un servidor de 2 GB.
2. Un trabajo EN COLA no toca su status.json; a los 25 minutos `_is_busy` lo
   daba por abandonado y el atleta podía encolar una segunda generación pagada.

Correr con:  python -m pytest AiService/tests -q
"""

import json
import os
import sys
import tempfile
import time
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from bridge import jobs  # noqa: E402


def _job(root: Path, job_id: str, envejecer_s: int = 0, **estado) -> Path:
    d = root / job_id
    d.mkdir(parents=True, exist_ok=True)
    base = {"user_id": 7, "job_id": job_id, "queued": False, "running": True, "done": False}
    (d / "spec.json").write_text(json.dumps({"user_id": 7}), encoding="utf-8")
    (d / "status.json").write_text(json.dumps({**base, **estado}), encoding="utf-8")
    if envejecer_s:
        viejo = time.time() - envejecer_s
        os.utime(d / "status.json", (viejo, viejo))
    return d


class Base(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)


class QuienEsperaRespuestasOcupaSitio(Base):
    def test_cuenta_como_corriendo(self):
        _job(self.root, "20260915060000", awaiting_answers=True)
        self.assertEqual(len(jobs.running_jobs(self.root)), 1)

    def test_con_dos_de_tope_y_dos_esperando_no_arranca_un_tercero(self):
        _job(self.root, "20260915060000", awaiting_answers=True)
        _job(self.root, "20260915060001", awaiting_answers=True)
        _job(self.root, "20260915060100", queued=True, running=False)
        lanzados = []
        original = jobs.launch
        jobs.launch = lambda d, *a, **k: lanzados.append(d.name)
        try:
            self.assertEqual(jobs.drain(self.root, "python", Path("worker.py"), limite=2), 0)
        finally:
            jobs.launch = original
        self.assertEqual(lanzados, [])


class UnTrabajoEnColaSigueSiendoSuyo(Base):
    def setUp(self):
        super().setUp()
        try:
            import main  # noqa: F401  (carga PaginaLocal y necesita el .env)
        except Exception as exc:  # noqa: BLE001
            self.skipTest(f"main no importa en este entorno: {exc}")
        self.main = main

    def test_en_cola_hace_media_hora_sigue_ocupado(self):
        _job(self.root, "20260915060000", envejecer_s=30 * 60, queued=True, running=False)
        self.assertTrue(self.main._is_busy(self.root, 7))

    def test_uno_corriendo_y_parado_hace_media_hora_ya_no(self):
        _job(self.root, "20260915060000", envejecer_s=30 * 60)
        self.assertFalse(self.main._is_busy(self.root, 7))


if __name__ == "__main__":
    unittest.main()
