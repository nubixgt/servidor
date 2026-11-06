"""Lo que salió en la prueba de punta a punta del 2026-10-19 (semana automática real de José en local).

- El script de las semanas automáticas murió a los 45 s («Maximum execution time of 45 seconds
  exceeded»): `HttpClient::postJson` llamaba a `set_time_limit(timeout + 15)` y en la línea de comandos,
  que no tiene tope, eso lo CREABA. La semana se generaba, pero se guardaba y se subía una hora después.
- Plan decía «Próxima: el domingo 18 de octubre a las 21:00» el lunes 19 por la tarde.
- Mientras cargaba, Plan enseñaba «Termina tu perfil primero — Va por el 62%» a un perfil al 100 %: el
  atleta de ejemplo del store traía 62, y la foto de stock de una desconocida como foto por defecto.
- El primer sondeo de una semana automática pedía quedarse a esperar las preguntas del coach.

Correr con:  python -m unittest discover -s tests -v
"""

import shutil
import subprocess
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
PHP = shutil.which("php") or ("C:/xampp/php/php.exe" if Path("C:/xampp/php/php.exe").exists() else None)


@unittest.skipUnless(PHP, "sin PHP en esta máquina")
class ElScriptNoMuereALos45Segundos(unittest.TestCase):

    def test_en_la_linea_de_comandos_no_se_crea_un_tope(self):
        codigo = ("require 'Backend/autoload.php';"
                  "App\\Utils\\HttpClient::postJson('http://127.0.0.1:9/nada', [], [], 1);"
                  "echo ini_get('max_execution_time');")
        salida = subprocess.run([PHP, "-r", codigo], cwd=RAIZ, capture_output=True, text=True, timeout=60)
        self.assertEqual(salida.stdout.strip(), "0", salida.stderr)


class LaPantalla(unittest.TestCase):

    def test_la_proxima_semana_automatica_no_es_una_hora_pasada(self):
        plan = (RAIZ / "Frontend/src/views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn("'en la próxima hora'", plan)
        self.assertIn("!['generando', 'subiendo'].includes(auto.stage)", plan)

    def test_nada_inventado_mientras_carga(self):
        ejemplo = (RAIZ / "Frontend/src/data/mockData.js").read_text(encoding="utf-8")
        self.assertIn("completionRate: null", ejemplo)
        self.assertNotIn("unsplash", ejemplo)
        plan = (RAIZ / "Frontend/src/views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn("completion.value === null ||", plan)

    def test_la_semana_automatica_no_pide_quedarse(self):
        worker = (RAIZ / "AiService/worker.py").read_text(encoding="utf-8")
        inicio = worker[worker.index("async def _main() -> None:"):]
        self.assertLess(inicio.index('STATUS["questions_done"] = True'), inicio.index("_flush()"))


if __name__ == "__main__":
    unittest.main()
