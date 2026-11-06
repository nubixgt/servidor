"""La cola: cuantos corren a la vez, quien entra, y que no se arranque dos veces.

Cada generacion es un proceso aparte que corre la pipeline entera, pero las
claves de API son unas solas para todos. Medido sobre 18 generaciones reales
del atleta: ~8 llamadas y ~112.000 tokens de entrada por semana generada,
repartidos entre Google, Anthropic y OpenAI, y en rafagas -- los tres
summarizers salen a la vez, y despues los tres expertos. Diez atletas pulsando
"generar" a la misma hora son treinta llamadas simultaneas al mismo proveedor.

De ahi la cola. Y de la cola, dos propiedades que si se rompen cuestan dinero
de verdad:

  1. **Nunca corren mas de `max_concurrent`.** Es lo unico que separa la
     rafaga de un 429 en cadena.
  2. **Un trabajo se arranca UNA vez.** Dos drenados que vieran el mismo
     trabajo en cola lanzarian dos procesos para la misma generacion, y se
     pagaria dos veces por la misma semana.

Correr con:  python -m unittest discover -s AiService/tests -v
"""

import json
import sys
import tempfile
import time
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from bridge import jobs  # noqa: E402


def _job(root: Path, job_id: str, **estado) -> Path:
    d = root / job_id
    d.mkdir(parents=True, exist_ok=True)
    base = {"user_id": 1, "job_id": job_id, "queued": False, "running": True, "done": False}
    (d / "status.json").write_text(json.dumps({**base, **estado}), encoding="utf-8")
    return d


class Base(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)
        self.lanzados = []
        parche = mock.patch.object(
            jobs, "subprocess",
            mock.Mock(Popen=lambda cmd, **kw: self.lanzados.append(Path(cmd[2]).name),
                      DEVNULL=None),
        )
        parche.start()
        self.addCleanup(parche.stop)

    def drenar(self, limite=2):
        return jobs.drain(self.root, "python", Path("worker.py"), limite)


class NuncaMasDeLosQueCaben(Base):
    def test_con_hueco_libre_arranca(self):
        _job(self.root, "20260901100000", queued=True, running=False)
        self.assertEqual(self.drenar(limite=2), 1)
        self.assertEqual(self.lanzados, ["20260901100000"])

    def test_lleno_no_arranca_a_nadie(self):
        _job(self.root, "20260901100000")
        _job(self.root, "20260901100001")
        _job(self.root, "20260901110000", queued=True, running=False)
        self.assertEqual(self.drenar(limite=2), 0)
        self.assertEqual(self.lanzados, [])

    def test_arranca_solo_los_que_caben(self):
        _job(self.root, "20260901100000")                              # 1 corriendo
        for i in range(5):
            _job(self.root, f"2026090111000{i}", queued=True, running=False)
        self.assertEqual(self.drenar(limite=3), 2)                     # quedan 2 huecos
        self.assertEqual(len(self.lanzados), 2)

    def test_diez_a_la_vez_arrancan_tres_no_diez(self):
        """El caso del lunes a las 6am con el equipo de la federacion."""
        for i in range(10):
            _job(self.root, f"202609011000{i:02d}", queued=True, running=False)
        self.assertEqual(self.drenar(limite=3), 3)
        self.assertEqual(jobs.queue_position(self.root, "20260901100009"), 7)


class LaColaEsFIFO(Base):
    def test_entra_el_que_lleva_mas_esperando(self):
        _job(self.root, "20260901120000", queued=True, running=False)  # mas nuevo
        _job(self.root, "20260901100000", queued=True, running=False)  # mas viejo
        self.drenar(limite=1)
        self.assertEqual(self.lanzados, ["20260901100000"])

    def test_el_puesto_se_cuenta_desde_uno(self):
        for i in range(3):
            _job(self.root, f"2026090110000{i}", queued=True, running=False)
        self.assertEqual(jobs.queue_position(self.root, "20260901100000"), 1)
        self.assertEqual(jobs.queue_position(self.root, "20260901100002"), 3)

    def test_lo_que_no_esta_en_cola_no_tiene_puesto(self):
        _job(self.root, "20260901100000")  # corriendo, no en cola
        self.assertEqual(jobs.queue_position(self.root, "20260901100000"), 0)


class UnTrabajoSeArrancaUnaSolaVez(Base):
    """Si esto se rompe, se paga dos veces la misma semana."""

    def test_drenar_dos_veces_no_lo_lanza_dos_veces(self):
        _job(self.root, "20260901100000", queued=True, running=False)
        self.drenar(limite=5)
        self.drenar(limite=5)
        self.assertEqual(self.lanzados, ["20260901100000"])

    def test_al_lanzarlo_deja_de_estar_en_cola(self):
        d = _job(self.root, "20260901100000", queued=True, running=False)
        jobs.launch(d, "python", Path("worker.py"))
        s = jobs.read_status(d)
        self.assertFalse(s["queued"])
        self.assertTrue(s["running"])
        self.assertEqual(jobs.queued_jobs(self.root), [])

    def test_lanzar_conserva_de_quien_es(self):
        """`launch` reescribe status.json: perder el user_id ahi devolveria la
        fuga que arreglamos -- el trabajo dejaria de ser de nadie."""
        d = _job(self.root, "20260901100000", user_id=42, queued=True, running=False)
        jobs.launch(d, "python", Path("worker.py"))
        self.assertEqual(jobs.read_status(d)["user_id"], 42)
        self.assertIsNotNone(jobs.newest_status(self.root, 42))


class UnWorkerMuertoNoBloqueaLaCola(Base):
    def test_un_trabajo_rancio_no_ocupa_sitio(self):
        """Sin esto, un worker que muere sin escribir `done` deja la cola
        parada 25 minutos con gente esperando detras."""
        d = _job(self.root, "20260901100000")
        viejo = time.time() - (jobs.JOB_STALE_S + 60)
        import os
        os.utime(d / "status.json", (viejo, viejo))

        self.assertEqual(jobs.running_jobs(self.root), [])
        _job(self.root, "20260901110000", queued=True, running=False)
        self.assertEqual(self.drenar(limite=1), 1)

    def test_los_terminados_no_ocupan_sitio(self):
        _job(self.root, "20260901100000", running=False, done=True)
        _job(self.root, "20260901110000", queued=True, running=False)
        self.assertEqual(self.drenar(limite=1), 1)

    def test_los_que_esperan_no_se_cuentan_como_corriendo(self):
        for i in range(4):
            _job(self.root, f"2026090110000{i}", queued=True, running=False)
        self.assertEqual(jobs.running_jobs(self.root), [])


class ElLimiteSeConfigura(unittest.TestCase):
    def test_por_defecto_son_tres(self):
        with mock.patch.dict("os.environ", {}, clear=False):
            import os
            os.environ.pop("MAX_CONCURRENT_GENERATIONS", None)
            self.assertEqual(jobs.max_concurrent(), 3)

    def test_se_puede_subir_por_entorno(self):
        with mock.patch.dict("os.environ", {"MAX_CONCURRENT_GENERATIONS": "8"}):
            self.assertEqual(jobs.max_concurrent(), 8)

    def test_un_valor_absurdo_no_deja_el_servicio_sin_cola(self):
        for malo in ("0", "-3", "hola", ""):
            with self.subTest(valor=malo):
                with mock.patch.dict("os.environ", {"MAX_CONCURRENT_GENERATIONS": malo}):
                    self.assertGreaterEqual(jobs.max_concurrent(), 1)


if __name__ == "__main__":
    unittest.main()
