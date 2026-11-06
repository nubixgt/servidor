"""El estado de una generacion es de un usuario, y de nadie mas.

El puente responde a `GET /bridge/generate-status` con el estado de un
trabajo, y ese estado lleva dentro `result`: el plan generado entero. El lado
PHP no lo trata como informacion de pantalla -- lo GUARDA:

    PlanService::pollStatus($userId)
        -> $this->repo->saveWeek($userId, $r['weekly_plan'], ...)

Hasta v7.1 el puente devolvia el trabajo mas reciente de CUALQUIERA. Con dos
atletas generando en la misma ventana, el que preguntaba primero se llevaba el
plan del otro y quedaba archivado como suyo en su fila de MySQL. No era una
fuga de lectura: era el plan equivocado, escrito.

Correr con:  python -m unittest discover -s AiService/tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from bridge.jobs import job_user, newest_status  # noqa: E402


def _job(root: Path, job_id: str, *, status: dict | None = None, spec: dict | None = None) -> Path:
    d = root / job_id
    d.mkdir(parents=True)
    if status is not None:
        (d / "status.json").write_text(json.dumps(status), encoding="utf-8")
    if spec is not None:
        (d / "spec.json").write_text(json.dumps(spec), encoding="utf-8")
    return d


class ElEstadoEsDeUnUsuario(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)

    def test_devuelve_el_trabajo_propio_aunque_no_sea_el_mas_reciente(self):
        _job(self.root, "20260901090000", status={"user_id": 7, "job_id": "a", "result": {"plan": "de 7"}})
        _job(self.root, "20260901100000", status={"user_id": 42, "job_id": "b", "result": {"plan": "de 42"}})

        self.assertEqual(newest_status(self.root, 7)["result"], {"plan": "de 7"})
        self.assertEqual(newest_status(self.root, 42)["result"], {"plan": "de 42"})

    def test_el_plan_de_otro_no_se_devuelve_jamas(self):
        """El fallo real: 42 genera, 7 pregunta, y 7 se lleva el plan de 42."""
        _job(self.root, "20260901100000", status={"user_id": 42, "result": {"plan": "de 42"}})
        self.assertIsNone(newest_status(self.root, 7))

    def test_sin_trabajos_propios_es_idle_no_el_de_otro(self):
        _job(self.root, "20260901100000", status={"user_id": 1, "result": {"plan": "ajeno"}})
        for quien in (2, "2", 99):
            with self.subTest(usuario=quien):
                self.assertIsNone(newest_status(self.root, quien))

    def test_el_id_compara_igual_venga_como_texto_o_como_numero(self):
        """El PHP manda el user_id por query string y llega como int; en
        status.json puede haberse escrito como cualquiera de los dos."""
        _job(self.root, "20260901100000", status={"user_id": "42", "result": {"plan": "x"}})
        self.assertIsNotNone(newest_status(self.root, 42))

    def test_un_directorio_sin_status_no_rompe_la_busqueda(self):
        _job(self.root, "20260901110000")  # worker que ni llego a escribir
        _job(self.root, "20260901100000", status={"user_id": 7, "result": {"plan": "de 7"}})
        self.assertEqual(newest_status(self.root, 7)["result"], {"plan": "de 7"})

    def test_raiz_inexistente_es_idle(self):
        self.assertIsNone(newest_status(self.root / "todavia-no", 7))


class UnTrabajoQueNoSePuedeAtribuirEsDeNadie(unittest.TestCase):
    """La regla de seguridad, en su forma mas corta.

    Los trabajos anteriores a v7.1 no llevan `user_id` en status.json, y el
    worker de Garmin borra spec.json en cuanto cifra la contraseña -- asi que
    existen trabajos sin dueño legible. Ante la duda se dice "idle".
    """

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)

    def test_sin_user_id_en_ningun_sitio_no_es_de_nadie(self):
        d = _job(self.root, "20260901100000", status={"job_id": "viejo", "result": {"plan": "?"}})
        self.assertIsNone(job_user(d, {"job_id": "viejo"}))
        self.assertIsNone(newest_status(self.root, 7))

    def test_un_trabajo_viejo_no_se_le_adjudica_a_quien_pregunta(self):
        """Lo contrario -- 'no se sabe, pues sera tuyo' -- reintroduce el fallo."""
        _job(self.root, "20260901100000", status={"result": {"plan": "de alguien"}})
        for quien in (1, 2, 3):
            with self.subTest(usuario=quien):
                self.assertIsNone(newest_status(self.root, quien))

    def test_si_solo_spec_json_lo_sabe_se_lee_de_ahi(self):
        d = _job(self.root, "20260901100000",
                 status={"result": {"plan": "de 7"}}, spec={"user_id": 7})
        self.assertEqual(job_user(d, {"result": {}}), "7")
        self.assertIsNotNone(newest_status(self.root, 7))

    def test_status_json_manda_sobre_spec_json(self):
        d = _job(self.root, "20260901100000", status={"user_id": 7}, spec={"user_id": 42})
        self.assertEqual(job_user(d, {"user_id": 7}), "7")


if __name__ == "__main__":
    unittest.main()
