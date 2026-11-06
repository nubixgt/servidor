"""«Pedir ayuda»: el triaje y el diagnóstico (Beta 3.0, F3).

Una consulta llega con un diagnóstico que arma el servidor y un origen probable que
propone el código. Lo que se fija aquí:

- **El triaje propone con señales, y sin ninguna dice `desconocido`** (o `decision_ia`
  cuando dice que el plan no tiene sentido). Casos con números reales: la bici de 6 × 1'
  que el codificador mandó como 6 × 50' (332' en el reloj por 70', banco del codificador
  del 2026-09-24), y la semana real del atleta 2 del 2026-10-12, donde lo decidido y lo
  codificado cuadran (38 y 38 min el miércoles 14).
- **Una carrera en un día de descanso NO es un fallo del perfil**: desde el 2026-10-09 el
  día de una carrera está disponible para ella (Hugo). El gravel B del atleta 2 cae en
  sábado, sin deporte en su rejilla; en el triaje no cuenta.
- **Lista blanca en cada salto**: lo que PHP mande de más (una contraseña) no entra.
- **Las listas valen lo mismo en los tres lados** (categorías en Python, PHP y JS; las
  claves de PHP que el diagnóstico acepta).

Correr con:  python -m unittest discover -s tests -v
"""

import json
import re
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import triaje  # noqa: E402
from services.ai.diagnostico_de_ayuda import CLAVES_DE_PHP, armar, leer_clave_de_sesion  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


def _diag(categoria="plan", decidido=None, codificado=None, reloj=None, php=None, errores=None, trabajos=None):
    d = {"categoria": categoria, "php": php or {}, "errores_del_navegador": errores or [], "trabajos": trabajos or []}
    if decidido or codificado or reloj:
        d["sesion"] = {"decidido": decidido, "codificado": codificado, "reloj": reloj or {"estado": "al_dia"}}
    return d


class ElTriaje(unittest.TestCase):

    def test_la_bici_de_6x1_que_llego_como_6x50(self):
        """Banco del codificador (2026-09-24): la decisión pedía 70' y el reloj llevaba 332'."""
        t = triaje.proponer(_diag(
            "reloj",
            decidido={"sport": "bike", "intensity_class": "vo2max", "total_duration_minutes": 70},
            codificado={"sport": "bike", "intensity_class": "vo2max", "minutos_de_los_pasos": 332},
            reloj={"estado": "al_dia"}))
        self.assertEqual(t["origen"], "codificacion")
        self.assertIn("La IA decidió 70 min y los pasos del reloj suman 332 min.", t["motivos"]["codificacion"])

    def test_lo_que_cuadra_no_es_codificacion(self):
        """Atleta 2, miércoles 14/10: 38 min decididos, 38 en los pasos."""
        t = triaje.proponer(_diag(
            "plan",
            decidido={"sport": "run", "intensity_class": "tempo_threshold", "total_duration_minutes": 38},
            codificado={"sport": "run", "intensity_class": "tempo_threshold", "minutos_de_los_pasos": 38}))
        self.assertEqual(t["origen"], "decision_ia")

    def test_una_carrera_en_dia_de_descanso_no_es_un_fallo_del_perfil(self):
        php = {"perfil_pct": 100, "rejilla_vacia": False, "contrasena_garmin_guardada": False,
               "carreras_en_dia_de_descanso": ["50 K Gravel Maniac Bicicleta (Sábado 2026-10-24)"]}
        t = triaje.proponer(_diag("datos", php=php))
        self.assertEqual(t["origen"], "desconocido")
        self.assertNotIn("perfil", t["motivos"])

    def test_el_perfil_a_medias_y_sin_rejilla_si(self):
        t = triaje.proponer(_diag("datos", php={"perfil_pct": 62, "rejilla_vacia": True}))
        self.assertEqual(t["origen"], "perfil")
        self.assertEqual(len(t["motivos"]["perfil"]), 2)

    def test_el_reloj_con_otra_version(self):
        t = triaje.proponer(_diag("reloj", decidido={"sport": "run"}, codificado={"sport": "run"},
                                  reloj={"estado": "desactualizado"}))
        self.assertEqual(t["origen"], "garmin")

    def test_la_categoria_ordena_las_senales(self):
        """«La app falla» con errores del navegador y un reloj viejo: primero la app."""
        d = _diag("app", decidido={"sport": "run"}, codificado={"sport": "run"},
                  reloj={"estado": "desactualizado"}, errores=[{"mensaje": "TypeError"}])
        t = triaje.proponer(d)
        self.assertEqual(t["origen"], "app")
        self.assertIn("garmin", t["motivos"], "las otras señales se enseñan igual")

    def test_minutos_por_distancia_no_se_comparan(self):
        """Un 10K hasta pulsar vuelta no tiene minutos en los pasos: no se adivina."""
        t = triaje.proponer(_diag(
            "plan", decidido={"sport": "run", "intensity_class": "race_day", "total_duration_minutes": 80},
            codificado={"sport": "run", "intensity_class": "race_day", "minutos_de_los_pasos": None}))
        self.assertEqual(t["origen"], "decision_ia")


class ElDiagnostico(unittest.TestCase):

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.dir = Path(self._tmp.name)
        traza = self.dir / "reasoning" / "2026-10-09T17-42-29"
        traza.mkdir(parents=True)
        (traza / "week_intents.json").write_text(json.dumps([{"week_number": 1, "days": [
            {"date": "2026-10-14", "session_slot": "unica", "sport": "run", "intensity_class": "tempo_threshold",
             "total_duration_minutes": 38.0, "focus": "Ritmo 10K", "session_sketch": "x" * 2000}]}]), "utf-8")
        self.plan = {"weeks": [{"week_number": 1, "days": [{
            "date": "2026-10-14", "day_name_es": "Miércoles", "session_slot": "unica", "is_rest": False,
            "sport": "run", "focus": "Ritmo 10K", "purpose": "-", "adaptation": "-",
            "intensity_class": "tempo_threshold", "total_duration_minutes": 38.0,
            "steps": [{"step_order": 1, "step_type": "warmup", "duration_type": "time", "duration_value": 600, "description": "10' suave"},
                      {"step_order": 2, "step_type": "repeat", "repeat_count": 3, "duration_type": "time",
                       "duration_value": 0, "description": "3x3'", "substeps": [
                           {"step_order": 1, "step_type": "interval", "duration_type": "time", "duration_value": 180, "description": "3'"},
                           {"step_order": 2, "step_type": "recovery", "duration_type": "time", "duration_value": 180, "description": "3'"}]},
                      {"step_order": 3, "step_type": "cooldown", "duration_type": "time", "duration_value": 600, "description": "10' suave"}]}]}]}

    def tearDown(self):
        self._tmp.cleanup()

    def test_la_sesion_decidida_codificada_y_en_el_reloj(self):
        d = armar(self.dir, user_id=2, categoria="reloj", session_key="2026-10-14|unica", weekly_plan=self.plan)
        s = d["sesion"]
        self.assertEqual(s["decidido"]["total_duration_minutes"], 38.0)
        self.assertEqual(s["codificado"]["minutos_de_los_pasos"], 38.0)
        self.assertEqual(s["reloj"], {"estado": "sin_subir"})
        self.assertIn("recortado: 2000 caracteres", s["decidido"]["session_sketch"], "lo recortado se dice")

    def test_un_dia_que_no_se_puede_leer_se_dice(self):
        """Se devolvía None en silencio y la caja «Se codificó» decía «No está en su plan»."""
        malo = {"weeks": [{"days": [{"date": "2026-10-14", "session_slot": "unica", "steps": [{"x": 1}]}]}]}
        d = armar(self.dir, user_id=2, categoria="reloj", session_key="2026-10-14|unica", weekly_plan=malo)
        self.assertIn("no se pudo leer", d["sesion"]["codificado"]["ilegible"])

    def test_lo_que_php_manda_de_mas_no_entra(self):
        d = armar(self.dir, user_id=2, categoria="datos",
                  php={"perfil_pct": 96, "garmin_password": "secreta", "profile": {"x": 1}})
        self.assertEqual(d["php"], {"perfil_pct": 96})
        self.assertNotIn("secreta", json.dumps(d))

    def test_los_errores_del_navegador_saneados_y_los_ultimos_diez(self):
        errores = [{"cuando": "t", "donde": "/admin/plan", "mensaje": f"fallo {i} de ana@correo.com"} for i in range(14)]
        d = armar(self.dir, user_id=2, categoria="app", errores_del_navegador=errores,
                  sanear=lambda t: re.sub(r"[\w.]+@[\w.]+", "***@***", t))
        self.assertEqual(len(d["errores_del_navegador"]), 10)
        self.assertEqual(d["errores_del_navegador"][-1]["mensaje"], "fallo 13 de ***@***")
        self.assertEqual(d["errores_del_navegador_recortados"], "últimos 10 de 14")

    def test_una_clave_de_sesion_rara_no_abre_nada(self):
        for mala in ("../../secure", "2026-13-01|unica", "", None):
            self.assertIsNone(leer_clave_de_sesion(mala))
        self.assertEqual(leer_clave_de_sesion("2026-10-14|xx"), ("2026-10-14", "unica"))


class LasListasValenLoMismo(unittest.TestCase):

    def test_las_cinco_categorias_en_python_php_y_js(self):
        php = (RAIZ / "Backend/src/Services/HelpService.php").read_text(encoding="utf-8")
        en_php = re.findall(r"'(\w+)'", php[php.index("CATEGORIAS = ["):php.index("];", php.index("CATEGORIAS = ["))])
        js = (RAIZ / "Frontend/src/services/feedback.js").read_text(encoding="utf-8")
        en_js = re.findall(r"valor: '(\w+)'", js[js.index("CATEGORIAS_DE_AYUDA"):])
        self.assertEqual(list(triaje.CATEGORIAS), en_php)
        self.assertEqual(list(triaje.CATEGORIAS), en_js)

    def test_cada_clave_que_arma_php_la_acepta_el_diagnostico(self):
        """Una clave nueva en PHP sin añadirla aquí se perdería en silencio."""
        php = (RAIZ / "Backend/src/Services/HelpService.php").read_text(encoding="utf-8")
        cuerpo = php[php.index("public function diagnosticoPhp"):php.index("public function recortar")]
        bloque = cuerpo[cuerpo.rindex("return ["):]
        claves = set(re.findall(r"^\s{12}'(\w+)' =>", bloque, re.M))
        self.assertTrue(claves)
        self.assertEqual(claves - set(CLAVES_DE_PHP), set())

    def test_la_contrasena_de_garmin_sale_solo_como_si_o_no(self):
        php = (RAIZ / "Backend/src/Services/HelpService.php").read_text(encoding="utf-8")
        self.assertIn("'contrasena_garmin_guardada' => $perfilService->garminPasswordBlob($userId) !== null", php)


if __name__ == "__main__":
    unittest.main()
