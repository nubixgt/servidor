"""Borrar el plan descarta el resultado del trabajo ANTES de lo lento.

## El fallo, reproducido el 2026-09-13 con el atleta 2

Borrado "all" desde la app a las 19:28. `/bridge/plan-deleted` borraba primero
las trazas y despues vaciaba el resultado de los trabajos terminados. En
Windows cada traza bloqueada reintenta ~1,5 s; PHP corta la llamada a los 15 s,
y el sondeo de estado que la app hace justo despues del borrado encontro la
semana todavia en el trabajo y la volvio a guardar en MySQL. `GET /plan`
devolvia `season_phases` null y la semana entera. Los trabajos quedaron
marcados como cobrados a las 19:28:54, tarde.

La garantia no es "borra rapido": es que en cuanto empieza lo que puede tardar,
ya no queda ningun resultado que resucitar.

Correr con:  cd AiService && python -m pytest tests -q
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

ATLETA = 2


class ElBorradoNoResucitaLaSemana(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self._tmp.cleanup)
        raiz = Path(self._tmp.name)
        self.jobs = raiz / "jobs"
        self.trabajo = self.jobs / "20260913145858395985"
        self.trabajo.mkdir(parents=True)
        (self.trabajo / "spec.json").write_text(json.dumps({"user_id": ATLETA, "kind": "week"}), "utf-8")
        (self.trabajo / "status.json").write_text(json.dumps({
            "user_id": ATLETA, "kind": "week", "done": True, "running": False,
            "result": {"weekly_plan": {"weeks": [{"week_number": 1}]}},
        }), "utf-8")
        espejo = raiz / f"u{ATLETA}"
        espejo.mkdir()

        self.resultado_al_borrar_trazas = "no se llamo"

        def borrar_trazas_lento(_dir, kind=None):
            estado = json.loads((self.trabajo / "status.json").read_text("utf-8"))
            self.resultado_al_borrar_trazas = estado.get("result")
            return 0

        for parche in (
            mock.patch.object(main, "JOBS_DIR", self.jobs),
            mock.patch.object(main, "current_storage_dir", lambda: espejo),
            mock.patch.object(main, "_check_token", lambda _t: None),
            mock.patch.object(main.pl_reasoning, "borrar_trazas", borrar_trazas_lento),
        ):
            parche.start()
            self.addCleanup(parche.stop)
        self.cliente = TestClient(main.app)

    def test_cuando_empieza_lo_lento_ya_no_hay_resultado(self):
        r = self.cliente.post("/bridge/plan-deleted", json={"user_id": ATLETA, "scope": "all"})
        self.assertEqual(r.status_code, 200)
        self.assertIsNone(self.resultado_al_borrar_trazas,
                          "las trazas se borraban con el resultado todavia en el trabajo: un sondeo lo resucita")
        self.assertEqual(r.json()["data"]["trabajos_cerrados"], 1)


if __name__ == "__main__":
    unittest.main()
