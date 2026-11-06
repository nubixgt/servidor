"""Lo retirado en la Beta 3.0 (F0) no vuelve por la puerta de atrás.

- **El Doctor** (asistente de salud). Su chat salió de la pantalla en la v1.1 y la 017 borró
  `doctor_performance_report`; quedaban el agente, sus rutas en el AiService, en PHP y en el
  servidor viejo, su rol de modelo en cada tier y el informe `medical`, sin nadie que los
  llamara. Código que nadie usa y que alguien podría volver a enchufar creyendo que funciona.
- **«Traer de mi reloj»** (fuerza leída de Garmin). El responsable, 2026-10-10: poco
  intuitivo y sin uso. Se quitan el botón, las rutas, el módulo y la lectura automática al
  generar la semana; se queda el registro que escribe el atleta (`/strength-log`).

Y lo nuevo de F0: la lista de pantallas que se miden (`ExperimentController::PANTALLAS`)
son nombres del router. Si alguien renombra una ruta del frontend y no la lista, esa
pantalla deja de contarse sin ningún error.
"""

import importlib
import re
import sys
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(RAIZ / "PaginaLocal"))


def _leer(rel: str) -> str:
    return (RAIZ / rel).read_text(encoding="utf-8")


class ElDoctorNoVuelve(unittest.TestCase):
    def test_no_hay_rol_de_modelo(self):
        from services.ai.ai_settings import AgentRole
        self.assertNotIn("HEALTH_INTAKE", AgentRole.__members__)

    def test_no_hay_agente_ni_informe(self):
        with self.assertRaises(ModuleNotFoundError):
            importlib.import_module("services.ai.health_intake_agent")
        from services.ai.rendering import printable_renderer
        self.assertFalse(hasattr(printable_renderer, "render_medical_report"))

    def test_ni_rutas(self):
        self.assertNotIn('@app.post("/bridge/health-intake")', _leer("AiService/main.py"))
        self.assertNotIn('@app.post("/api/health-intake")', _leer("PaginaLocal/web_app/server.py"))
        self.assertNotIn("health-intake", _leer("Backend/src/Services/PythonBridgeService.php"))
        controlador = _leer("Backend/src/Controllers/PlanController.php")
        linea = next(x for x in controlador.splitlines() if "in_array($type" in x)
        self.assertNotIn("medical", linea)
        # Y la pantalla vieja ya no llama a una ruta que no existe (test_ui_audit lo exige).
        self.assertNotIn("fetch('/api/health-intake'", _leer("PaginaLocal/web_app/static/app.js"))

    def test_el_libro_de_costos_sigue_leyendo_lo_viejo(self):
        """Hay líneas antiguas del Doctor en el libro: se siguen nombrando."""
        from core.usage_ledger import KIND_HEALTH_INTAKE
        self.assertEqual(KIND_HEALTH_INTAKE, "health_intake")


class LaFuerzaDelRelojNoVuelve(unittest.TestCase):
    def test_no_hay_modulo_ni_lectura_al_generar(self):
        with self.assertRaises(ModuleNotFoundError):
            importlib.import_module("services.ai.fuerza_desde_garmin")
        servidor = _leer("PaginaLocal/web_app/server.py")
        self.assertNotIn("fuerza_del_reloj", servidor)
        self.assertNotIn("fuerza_desde_garmin", servidor)

    def test_ni_rutas_ni_boton(self):
        self.assertNotIn('@app.post("/bridge/strength-from-garmin")', _leer("AiService/main.py"))
        self.assertNotIn("#[Route('/strength-log/garmin'", _leer("Backend/src/Controllers/StrengthLogController.php"))
        self.assertNotIn("Traer de mi reloj", _leer("Frontend/src/views/admin/PlanView.vue"))

    def test_el_registro_del_atleta_se_queda(self):
        controlador = _leer("Backend/src/Controllers/StrengthLogController.php")
        self.assertIn("#[Route('/strength-log', 'POST')]", controlador)
        from services.garmin.workout_builder import _strength_steps_from_block
        self.assertEqual(list(_strength_steps_from_block.__code__.co_varnames[:1]), ["day"])


class LasPantallasQueSeMiden(unittest.TestCase):
    def test_son_nombres_del_router(self):
        php = _leer("Backend/src/Controllers/ExperimentController.php")
        bloque = php[php.index("private const PANTALLAS"):]
        bloque = bloque[:bloque.index("];")]
        pantallas = re.findall(r"'([A-Za-z]+)'", bloque)
        router = set(re.findall(r"name:\s*'([A-Za-z]+)'", _leer("Frontend/src/router/index.js")))
        self.assertTrue(pantallas)
        self.assertEqual([p for p in pantallas if p not in router], [])
        # Y el frontend manda exactamente ésas: una de más es un 400 en la consola (lo que
        # pasaba en cada pantalla del administrador, visto al estrenar el panel de salud).
        js = _leer("Frontend/src/services/experimentos.js")
        bloque_js = js[js.index("PANTALLAS_MEDIDAS = ["):]
        bloque_js = bloque_js[:bloque_js.index("];")]
        self.assertEqual(re.findall(r"'([A-Za-z]+)'", bloque_js), pantallas)

    def test_el_router_las_anota_y_el_panel_las_ensena(self):
        self.assertIn("router.afterEach", _leer("Frontend/src/router/index.js"))
        self.assertIn("'screen_views' => $this->repo->screenViews($days)", _leer("Backend/src/Services/EventService.php"))
        self.assertIn("m.value?.screen_views", _leer("Frontend/src/views/admin/MetricsAdminView.vue"))


if __name__ == "__main__":
    unittest.main()
