"""La semana que volvia sola cada vez que se reiniciaba la app.

## El fallo, tal cual lo conto el atleta

    "tengo una semana fantasma, siempre que reinicias, esta semana aparece,
     la volvi a eliminar, pero me parece raro"

## Por que pasaba

`generate-status` devuelve el trabajo mas reciente del atleta, y PHP guarda su
`result` en MySQL cuando lo ve terminado. Para no guardarlo dos veces compara
el id del trabajo con `athlete_plans.last_generation_job`:

    $alreadySaved = $existing && $jobId !== '' && $existing->lastGenerationJob === $jobId;

Esa columna vive DENTRO de la fila del plan. Borrar el plan entero
(`deleteByUserId`) borra la fila, asi que `$existing` pasa a ser null, la
guarda deja de valer, y el siguiente sondeo vuelve a guardar el resultado del
mismo trabajo terminado. La app sondea al arrancar, de ahi el "siempre que
reinicias".

Reproducido en la maquina del atleta: cuatro trabajos terminados en disco, los
cuatro con su `result` completo, todos suyos.

## La forma del arreglo

El trabajo se marca como COBRADO al borrar el plan -- se le vacia el `result`
y se le pone `collected: true`. No se borra el directorio: `worker.log` es lo
unico que queda para entender una generacion que fallo.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from bridge import jobs  # noqa: E402


def _trabajo(raiz: Path, nombre: str, *, user_id: int, done: bool, result) -> Path:
    d = raiz / nombre
    d.mkdir(parents=True)
    (d / "status.json").write_text(json.dumps({
        "kind": "week", "job_id": nombre, "user_id": user_id,
        "running": not done, "done": done, "progress": 100 if done else 40,
        "error": None, "result": result,
    }), encoding="utf-8")
    (d / "worker.log").write_text("registro de la generacion", encoding="utf-8")
    return d


class UnResultadoSeCobraUnaSolaVez(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)

    def test_EL_FALLO_un_trabajo_terminado_deja_de_devolver_su_resultado(self):
        _trabajo(self.raiz, "20260831192947144532", user_id=2, done=True,
                 result={"weekly_plan": {"weeks": [{"week_number": 1}]}})

        antes = jobs.newest_status(self.raiz, 2)
        self.assertIsNotNone(antes["result"], "El montaje de la prueba no reproduce el fallo")

        self.assertEqual(jobs.descartar_resultados(self.raiz, 2), 1)

        despues = jobs.newest_status(self.raiz, 2)
        self.assertIsNone(despues["result"], "La semana volveria a guardarse en el siguiente sondeo")
        self.assertTrue(despues["collected"])
        self.assertTrue(despues["done"], "Sigue estando terminado; lo que cambia es que ya se cobro")

    def test_un_trabajo_EN_CURSO_no_se_toca(self):
        """El atleta pudo borrar el plan viejo mientras se genera el nuevo, y
        ese resultado si tiene que llegar."""
        _trabajo(self.raiz, "20260901120000000000", user_id=2, done=False, result=None)
        self.assertEqual(jobs.descartar_resultados(self.raiz, 2), 0)
        self.assertFalse(jobs.newest_status(self.raiz, 2)["done"])

    def test_no_toca_los_trabajos_de_OTRO_atleta(self):
        _trabajo(self.raiz, "20260831150000000000", user_id=3, done=True, result={"weekly_plan": {}})
        _trabajo(self.raiz, "20260831160000000000", user_id=2, done=True, result={"weekly_plan": {}})
        self.assertEqual(jobs.descartar_resultados(self.raiz, 2), 1)
        self.assertIsNotNone(jobs.newest_status(self.raiz, 3)["result"])

    def test_se_cobran_TODOS_los_terminados_no_solo_el_ultimo(self):
        """`newest_status` devuelve el mas reciente, pero si ese se borra o se
        cae, el siguiente sondeo cogeria el anterior y resucitaria un plan aun
        mas viejo."""
        for n, nombre in enumerate(("20260831150000000000", "20260831160000000000", "20260831170000000000")):
            _trabajo(self.raiz, nombre, user_id=2, done=True, result={"weekly_plan": {"n": n}})
        self.assertEqual(jobs.descartar_resultados(self.raiz, 2), 3)
        for d in jobs.job_dirs(self.raiz):
            self.assertIsNone(jobs.read_status(d)["result"])

    def test_el_registro_del_worker_sobrevive(self):
        """Es lo único que queda para entender una generación que falló."""
        d = _trabajo(self.raiz, "20260831150000000000", user_id=2, done=True, result={"weekly_plan": {}})
        jobs.descartar_resultados(self.raiz, 2)
        self.assertTrue((d / "worker.log").is_file())

    def test_cobrar_dos_veces_no_hace_nada_la_segunda(self):
        _trabajo(self.raiz, "20260831150000000000", user_id=2, done=True, result={"weekly_plan": {}})
        self.assertEqual(jobs.descartar_resultados(self.raiz, 2), 1)
        self.assertEqual(jobs.descartar_resultados(self.raiz, 2), 0)

    def test_un_atleta_sin_trabajos_no_revienta(self):
        self.assertEqual(jobs.descartar_resultados(self.raiz, 99), 0)


if __name__ == "__main__":
    unittest.main()
