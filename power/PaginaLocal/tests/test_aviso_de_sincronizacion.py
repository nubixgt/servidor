"""El check-in avisa si el reloj no ha sincronizado (José, 2026-10-19).

Lo que no está en Garmin Connect al generar no se puede contar como hecho (la adherencia lo deja
pendiente y lo mide la semana siguiente, pero la decisión de ESTA semana ya no lo ve). El check-in
pregunta al abrirse cuándo subió algo su reloj (`GET /plan/garmin-sync` → `/bridge/garmin-sync` →
`get_device_last_used`) y, si fue hace 3 horas o más, lo dice antes de generar. Verificado en local
como José: «hoy a las 09:51» (1792425092000 = 15:51:32 UTC; su hora, UTC-6), ~6 s, sin generar nada.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import os
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]


@unittest.skipUnless(shutil.which("node"), "sin node no se puede ejecutar el JS")
class ElTexto(unittest.TestCase):

    def test_como_lo_diria_una_persona(self):
        js = (RAIZ / "Frontend/src/utils/sincronizacion.js").as_uri()
        ahora = "2026-10-19T17:30:00-06:00"
        casos = [
            "2026-10-19T15:51:32+00:00",  # hoy 09:51 en su hora
            "2026-10-19T22:00:00+00:00",  # hace 1 h 30: no se avisa
            "2026-10-19T03:15:00+00:00",  # ayer 21:15
            "2026-10-12T15:51:32+00:00",  # el lunes anterior
            None, "no es una fecha",
        ]
        script = (f"import {{ cuandoSincronizo }} from '{js}';\n"
                  f"const ahora = new Date('{ahora}');\n"
                  f"console.log(JSON.stringify({json.dumps(casos)}.map(c => cuandoSincronizo(c, ahora))));\n")
        with tempfile.NamedTemporaryFile("w", suffix=".mjs", delete=False, encoding="utf-8") as f:
            f.write(script)
        try:
            salida = subprocess.run(["node", f.name], capture_output=True, text=True, check=True, timeout=60,
                                    env={**os.environ, "TZ": "America/Guatemala"})
        finally:
            Path(f.name).unlink(missing_ok=True)
        self.assertEqual(json.loads(salida.stdout), [
            "hoy a las 09:51", None, "ayer a las 21:15", "el lunes 12 a las 09:51", None, None,
        ])


class ElCable(unittest.TestCase):

    def test_del_check_in_al_reloj(self):
        modal = (RAIZ / "Frontend/src/components/modals/CheckinModal.vue").read_text(encoding="utf-8")
        self.assertIn("getUltimaSincronizacion()", modal)
        self.assertIn("avisoDeSincronizacion", modal)
        plan_js = (RAIZ / "Frontend/src/services/plan.js").read_text(encoding="utf-8")
        self.assertIn("api.get('/plan/garmin-sync')", plan_js)
        php = (RAIZ / "Backend/src/Controllers/PlanController.php").read_text(encoding="utf-8")
        self.assertIn("#[Route('/plan/garmin-sync', 'GET')]", php)
        puente = (RAIZ / "AiService/main.py").read_text(encoding="utf-8")
        self.assertIn('@app.post("/bridge/garmin-sync")', puente)


if __name__ == "__main__":
    unittest.main()
