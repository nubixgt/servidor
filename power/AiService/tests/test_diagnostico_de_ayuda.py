"""La ruta del diagnóstico de «Pedir ayuda» (Beta 3.0, F3).

Sólo lee (traza, registro de subidas, expediente, trabajos, libro de redes) y lo devuelve
con lista blanca y saneado. Lo que se fija: los trabajos son sólo los de ESE atleta, un
correo en un error del navegador no sale, lo que PHP mande de más no entra, y el espejo
no se toca (la ruta corre mientras otra generación puede estar escribiendo en él).

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

ATLETA = 11


def _trabajo(raiz: Path, job_id: str, status: dict) -> None:
    d = raiz / job_id
    d.mkdir(parents=True)
    (d / "status.json").write_text(json.dumps(status), "utf-8")


class ElDiagnosticoDeAyuda(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self._tmp.cleanup)
        raiz = Path(self._tmp.name)
        self.jobs, self.subidas, self.espejo = raiz / "jobs", raiz / "upload_jobs", raiz / f"u{ATLETA}"
        for d in (self.jobs, self.subidas, self.espejo):
            d.mkdir()
        _trabajo(self.jobs, "20261011100000000000", {"kind": "week", "user_id": ATLETA, "done": True})
        _trabajo(self.jobs, "20261011110000000000", {"kind": "week", "user_id": ATLETA + 1, "done": True,
                                                     "error": "de otro atleta"})
        for parche in (
            mock.patch.object(main, "JOBS_DIR", self.jobs),
            mock.patch.object(main, "UPLOAD_JOBS", self.subidas),
            mock.patch.object(main, "current_storage_dir", lambda: self.espejo),
            mock.patch.object(main, "_check_token", lambda _t: None),
        ):
            parche.start()
            self.addCleanup(parche.stop)
        self.cliente = TestClient(main.app)

    def _pedir(self, **extra):
        cuerpo = {"user_id": ATLETA, "categoria": "app"}
        cuerpo.update(extra)
        r = self.cliente.post("/bridge/support-bundle", json=cuerpo)
        self.assertEqual(r.status_code, 200)
        return r.json()["data"]

    def test_solo_sus_trabajos(self):
        d = self._pedir()
        self.assertEqual([t["job_id"] for t in d["trabajos"]], ["20261011100000000000"])
        self.assertNotIn("de otro atleta", json.dumps(d))

    def test_saneado_y_lista_blanca(self):
        d = self._pedir(client_errors=[{"mensaje": "fallo con ana.atleta@gmail.com", "donde": "/admin/plan"}],
                        php={"perfil_pct": 90, "garmin_password": "secreta"})
        texto = json.dumps(d, ensure_ascii=False)
        self.assertNotIn("ana.atleta", texto)
        self.assertNotIn("secreta", texto)
        self.assertEqual(d["php"], {"perfil_pct": 90})
        self.assertEqual(d["triaje"]["origen"], "app")

    def test_no_escribe_el_espejo(self):
        self._pedir(weekly_plan={"weeks": []}, session_key="2026-10-14|unica")
        self.assertEqual(list(self.espejo.iterdir()), [])


if __name__ == "__main__":
    unittest.main()
