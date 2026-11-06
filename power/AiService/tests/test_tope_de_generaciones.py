"""El tope de versiones de una misma semana, en la ruta del puente (D2, Beta 3.0 F2).

Decisión del responsable (2026-10-10): como mucho 3 versiones de una misma semana; los
fallos no gastan cupo y la semana automática no cuenta. En la Beta 1 un atleta generó 16
planes en un día, a ~0,30 USD cada uno, y nada lo frenaba.

PHP manda las semanas que ya generó (`versiones_previas`, de `athlete_generations`) y
aquí se decide con la MISMA función que elige qué semana se escribe
(`server.semana_objetivo`). Lo que se prueba es que el 409 llega antes de lanzar nada
(ningún trabajo, ningún gasto) y que sin la lista no hay tope (PHP sin la 028).

Correr con:  python -m pytest AiService/tests -q
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

from fastapi.testclient import TestClient

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import main  # noqa: E402

ATLETA = 9


def _semana(desde_dia: int) -> dict:
    """Una semana guardada del 12 al 18 de octubre de 2026 (lunes a domingo)."""
    dias = [{"date": f"2026-10-{d}", "day_name_es": "x", "is_rest": True, "sport": "rest",
             "focus": "Descanso", "purpose": "-", "adaptation": "-", "steps": [],
             "total_duration_minutes": 0} for d in range(desde_dia, desde_dia + 7)]
    return {"athlete_name": "x", "start_date": f"2026-10-{desde_dia}", "zones_table": "",
            "weeks": [{"week_number": 1, "days": dias}]}


class ElTopeDeVersiones(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self._tmp.cleanup)
        raiz = Path(self._tmp.name)
        self.jobs = raiz / "jobs"
        self.jobs.mkdir()
        self.espejo = raiz / f"u{ATLETA}"
        self.espejo.mkdir()
        self.lanzados = []

        def _lanzar(_root, kind, spec):
            self.lanzados.append(spec)
            return "20261017120000000000"

        for parche in (
            mock.patch.object(main, "JOBS_DIR", self.jobs),
            mock.patch.object(main, "current_storage_dir", lambda: self.espejo),
            mock.patch.object(main, "_check_token", lambda _t: None),
            mock.patch.object(main, "_spawn_job", _lanzar),
        ):
            parche.start()
            self.addCleanup(parche.stop)
        self.cliente = TestClient(main.app)

    def _generar(self, **extra):
        cuerpo = {"user_id": ATLETA, "profile": {}, "garmin_password": "x",
                  "previous_weekly_plan": _semana(12), "today": "2026-10-17"}
        cuerpo.update(extra)
        return self.cliente.post("/bridge/generate-week", json=cuerpo)

    def test_la_cuarta_version_de_la_misma_semana_no_se_lanza(self):
        r = self._generar(versiones_previas=["2026-10-19", "2026-10-19", "2026-10-19"])
        self.assertEqual(r.status_code, 409)
        detalle = r.json()["detail"]
        self.assertTrue(detalle["cupo"])
        self.assertEqual((detalle["target_week"], detalle["usadas"], detalle["quedan"]), ("2026-10-19", 3, 0))
        self.assertIn("lunes 19 de octubre", detalle["reason"])
        self.assertEqual(self.lanzados, [], "con el cupo agotado no se lanza ningún trabajo")

    def test_con_cupo_se_lanza_y_dice_que_semana_paga(self):
        r = self._generar(versiones_previas=["2026-10-19", "2026-10-12", "2026-10-12", "2026-10-12"])
        self.assertEqual(r.status_code, 200)
        self.assertEqual(r.json()["data"]["target_week"], "2026-10-19")
        self.assertEqual(len(self.lanzados), 1)

    def test_rehacer_cuenta_contra_la_semana_que_se_rehace(self):
        r = self._generar(redo=True, versiones_previas=["2026-10-12"] * 3)
        self.assertEqual(r.status_code, 409)
        self.assertEqual(r.json()["detail"]["target_week"], "2026-10-12")

    def test_la_semana_automatica_no_cuenta(self):
        r = self._generar(automatic=True, versiones_previas=["2026-10-19"] * 3)
        self.assertEqual(r.status_code, 200)

    def test_sin_la_lista_no_hay_tope(self):
        """PHP sin la migración 028 manda null: todo como antes."""
        r = self._generar(versiones_previas=None)
        self.assertEqual(r.status_code, 200)

    def test_la_primera_semana_desde_manana_es_la_misma_que_desde_hoy(self):
        """Borrarla y pedirla "desde mañana" no puede ser una semana nueva (±1 día)."""
        r = self._generar(previous_weekly_plan=None, today="2026-10-11", start_date="2026-10-12",
                          versiones_previas=["2026-10-11", "2026-10-11", "2026-10-12"])
        self.assertEqual(r.status_code, 409)
        self.assertEqual(r.json()["detail"]["target_week"], "2026-10-12")

    def test_el_espejo_recibe_el_plan_tambien_al_rehacer(self):
        """Antes sólo se escribía sin `redo`; ahora la semana objetivo se lee siempre del espejo."""
        self._generar(redo=True, versiones_previas=[])
        guardado = json.loads((self.espejo / "weekly_plan.json").read_text("utf-8"))
        self.assertEqual(guardado["start_date"], "2026-10-12")


if __name__ == "__main__":
    unittest.main()
