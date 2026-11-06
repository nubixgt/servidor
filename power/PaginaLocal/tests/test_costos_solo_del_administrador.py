"""Los costos de IA son SÓLO del administrador, también en la API (auditoría del 2026-10-19).

Las pantallas los escondían (`store.isAdmin`), pero como José (atleta) en local, `GET /plan`,
`/plan/generate-status`, `/plan/history` y `/plan/history/61` devolvían `summary.total_cost_usd` y
`cost_breakdown` con el costo de cada modelo: a un F12 de distancia, que es lo que `UsageController`
dice que no puede pasar. Ahora `App\\Support\\SinCostos` los quita antes de responder a quien no es
administrador; verificado después en local: ninguna de las cuatro lleva una clave de costo.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import shutil
import subprocess
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
PHP = shutil.which("php") or ("C:/xampp/php/php.exe" if Path("C:/xampp/php/php.exe").exists() else None)


class LasRutasDelPlan(unittest.TestCase):

    def test_cada_respuesta_con_el_plan_pasa_por_el_filtro(self):
        php = (RAIZ / "Backend/src/Controllers/PlanController.php").read_text(encoding="utf-8")
        for funcion in ("function show(", "function generateStatus(", "function history(",
                        "function historyEntry(", "function editSession("):
            cuerpo = php[php.index(funcion):].split("#[Route", 1)[0]
            self.assertIn("SinCostos::paraQuien($payload", cuerpo, funcion)


@unittest.skipUnless(PHP, "sin PHP en esta máquina")
class ElFiltro(unittest.TestCase):

    def _correr(self, rol):
        codigo = (
            "require 'Backend/autoload.php';"
            "$d = ['summary' => ['total_cost_usd' => 0.3, 'cost_breakdown' => [['cost_usd' => 0.1]],"
            " 'prescription' => ['threshold' => 342]], 'entries' => [['id' => 1, 'total_cost_usd' => 0.2]]];"
            f"echo json_encode(App\\Support\\SinCostos::paraQuien(['role' => '{rol}'], $d));"
        )
        salida = subprocess.run([PHP, "-r", codigo], cwd=RAIZ, capture_output=True, text=True, check=True)
        return json.loads(salida.stdout)

    def test_al_atleta_le_llega_todo_menos_el_dinero(self):
        d = self._correr("user")
        self.assertEqual(d["summary"], {"prescription": {"threshold": 342}})
        self.assertEqual(d["entries"], [{"id": 1}])

    def test_al_administrador_le_llega_entero(self):
        self.assertEqual(self._correr("admin")["summary"]["total_cost_usd"], 0.3)


if __name__ == "__main__":
    unittest.main()
