"""Una generacion que termino bien no se borra por no haberla recogido.

## El agujero

El resultado de una generacion vive en `status.json` hasta que PHP lo guarda en
MySQL, y PHP solo lo tiene delante cuando la app pregunta por el estado
(`PlanService::pollStatus`). Hasta la auditoria del 2026-09-11 preguntaba UNA
sola pantalla, la de Plan: el atleta que pulsaba "generar", cerraba la pestaña y
volvia a Inicio dejaba su semana --**ya pagada**-- aqui dentro, y `_cleanup` la
borraba a los tres dias o al llegar a 30 trabajos.

Se arreglo por los dos lados: la app pregunta al cargar el plan desde cualquier
pantalla (`appStore.rescatarGeneracion`) y la limpieza deja de tirar lo que
nadie recogio. Esta prueba fija el segundo lado, que es el que protege a quien
no vuelve hasta la semana siguiente.

Correr con:  cd AiService && python -m pytest tests -q
"""

import json
import os
import sys
import tempfile
import time
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import main  # noqa: E402

HECHO_CON_PLAN = {
    "done": True,
    "running": False,
    "kind": "week",
    "job_id": "x",
    "result": {"weekly_plan": {"weeks": [{"week_number": 1}]}},
}
HECHO_Y_FALLIDO = {"done": True, "running": False, "kind": "week", "error": "Garmin no contesto"}


def _job(root: Path, job_id: str, status: dict, *, antiguedad_s: float = 0) -> Path:
    d = root / job_id
    d.mkdir(parents=True)
    s = d / "status.json"
    s.write_text(json.dumps(status), encoding="utf-8")
    if antiguedad_s:
        cuando = time.time() - antiguedad_s
        os.utime(s, (cuando, cuando))
    return d


class LoPagadoNoSeTira(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.root = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)

    def test_sobrevive_al_tope_de_treinta_trabajos(self):
        """40 trabajos despues, el unico que traia un plan sigue ahi."""
        pagado = _job(self.root, "20260901000000000000", HECHO_CON_PLAN)
        for i in range(40):
            _job(self.root, f"2026091100000{i:07d}", {"done": True, "running": False})

        main._cleanup(self.root)

        self.assertTrue(pagado.exists(), "se borro una semana ya pagada por el tope de trabajos")

    def test_sobrevive_a_los_tres_dias(self):
        pagado = _job(self.root, "20260901000000000000", HECHO_CON_PLAN, antiguedad_s=5 * 24 * 3600)
        main._cleanup(self.root)
        self.assertTrue(pagado.exists())

    def test_pero_no_para_siempre(self):
        """A los 30 dias ya no hay nada que rescatar: el atleta no volvio."""
        viejo = _job(self.root, "20260101000000000000", HECHO_CON_PLAN, antiguedad_s=40 * 24 * 3600)
        main._cleanup(self.root)
        self.assertFalse(viejo.exists())

    def test_un_trabajo_que_fallo_si_se_limpia(self):
        """No hay nada dentro que valga dinero, y guardarlo taparia el hueco."""
        fallido = _job(self.root, "20260901000000000000", HECHO_Y_FALLIDO, antiguedad_s=5 * 24 * 3600)
        main._cleanup(self.root)
        self.assertFalse(fallido.exists())


if __name__ == "__main__":
    unittest.main()
