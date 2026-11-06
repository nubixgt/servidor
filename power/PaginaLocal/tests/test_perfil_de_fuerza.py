"""Beta 2.1, paso S3: cómo entrena la fuerza el atleta se guarda (Database/025).

Fernando (2026-09-17) tiene su rutina Upper/Lower con superseries y quiere
hipertrofia; Jessie dice que los ejercicios le salen "muy básicos"; a Hugo le
gusta como está. Ninguna de las 8 fichas archivadas de José y Jessie decía
gimnasio, equipo, objetivo ni rutina.

Lo que fija:

- no contestar es ``None`` (y el plan sigue como hoy): tampoco un modo que no
  reconoce, un JSON roto o lo que dejara una versión vieja;
- lo que no se reconoce se descarta campo a campo, no se adivina;
- una rutina sólo cuenta confirmada y con sesiones;
- los valores permitidos son los mismos en Python y en ``Frontend/src/utils/fuerza.js``;
- las dos columnas cruzan todas las capas: migración, verificador, PHP, el
  modelo del servidor y el formulario.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import perfil_de_fuerza as pf  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]
COLUMNAS = ("strength_profile", "strength_routine")


def _claves_js(nombre: str) -> tuple[str, ...]:
    js = (RAIZ / "Frontend/src/utils/fuerza.js").read_text(encoding="utf-8")
    bloque = re.search(rf"export const {nombre} = \{{(.*?)\n\}};", js, re.S)
    return tuple(re.findall(r"^\s+([a-z_]+):", bloque.group(1), re.M)) if bloque else ()


def _rango_js(nombre: str) -> tuple[int, int]:
    js = (RAIZ / "Frontend/src/utils/fuerza.js").read_text(encoding="utf-8")
    encontrado = re.search(rf"export const {nombre} = \[(\d+), (\d+)\];", js)
    return (int(encontrado.group(1)), int(encontrado.group(2))) if encontrado else (0, 0)


class SinContestarEsNone(unittest.TestCase):
    def test_perfil_sin_la_columna_o_vacio(self):
        for perfil in (None, {}, {"strength_profile": None}, {"strength_profile": {}},
                       {"strength_profile": "rutina"}, {"strength_profile": []}):
            with self.subTest(perfil=perfil):
                self.assertIsNone(pf.leer_perfil_de_fuerza(perfil))

    def test_un_modo_que_no_existe_no_se_adivina(self):
        for modo in ("", "Rutina", "si", "indiferente", None, 1):
            with self.subTest(modo=modo):
                self.assertIsNone(pf.leer_perfil_de_fuerza({"strength_profile": {"mode": modo, "goal": "hipertrofia"}}))


class LoQueSeReconoce(unittest.TestCase):
    def test_fernando_con_su_rutina(self):
        perfil = pf.leer_perfil_de_fuerza({"strength_profile": {
            "mode": "rutina", "goal": "hipertrofia", "equipment": "gimnasio",
            "barbell_lifts": ["press_banca", "sentadilla"]}})
        self.assertEqual(perfil.modo, "rutina")
        self.assertEqual(perfil.objetivo, "hipertrofia")
        self.assertEqual(perfil.equipo, "gimnasio")
        # En el orden del catálogo, no en el que llegaron.
        self.assertEqual(perfil.levantamientos_con_barra, ("sentadilla", "press_banca"))

    def test_lo_que_no_se_reconoce_se_descarta_campo_a_campo(self):
        perfil = pf.leer_perfil_de_fuerza({"strength_profile": {
            "mode": "coach", "goal": "estetica", "equipment": "gimnasio", "structure": "push_pull_legs",
            "exercises_per_group": 5, "session_minutes": "60", "barbell_lifts": ["clean", "peso_muerto"]}})
        self.assertIsNone(perfil.objetivo)
        self.assertEqual(perfil.equipo, "gimnasio")
        self.assertIsNone(perfil.estructura)
        self.assertIsNone(perfil.ejercicios_por_grupo)
        self.assertEqual(perfil.minutos_por_sesion, 60)
        self.assertEqual(perfil.levantamientos_con_barra, ("peso_muerto",))

    def test_numeros_fuera_de_rango_o_que_no_son_enteros(self):
        for minutos in (5, 500, 45.5, True, "una hora"):
            with self.subTest(minutos=minutos):
                perfil = pf.leer_perfil_de_fuerza({"strength_profile": {"mode": "coach", "session_minutes": minutos}})
                self.assertIsNone(perfil.minutos_por_sesion)


class LaRutinaSoloCuentaConfirmada(unittest.TestCase):
    TEXTO = "Upper A — 120 min\nPress de banca 4×8 - Elevaciones laterales — 4×12 - Crunch — 3×15"

    def test_pegada_y_sin_confirmar_se_guarda_pero_no_cuenta(self):
        rutina = pf.leer_rutina_de_fuerza({"strength_routine": {
            "source": "atleta", "original_text": self.TEXTO, "confirmed_at": None, "sessions": []}})
        self.assertEqual(rutina.texto_original, self.TEXTO)
        self.assertFalse(rutina.confirmada)

    def test_confirmada_sin_sesiones_no_cuenta(self):
        rutina = pf.leer_rutina_de_fuerza({"strength_routine": {
            "original_text": self.TEXTO, "confirmed_at": "2026-09-17T18:00:00", "sessions": []}})
        self.assertFalse(rutina.confirmada)

    def test_confirmada_con_sesiones(self):
        rutina = pf.leer_rutina_de_fuerza({"strength_routine": {
            "source": "coach", "original_text": "", "confirmed_at": "2026-09-17T18:00:00",
            "sessions": [{"id": "upper_a", "name": "Upper A"}, "basura"]}})
        self.assertTrue(rutina.confirmada)
        self.assertEqual(rutina.origen, "coach")
        self.assertEqual(len(rutina.sesiones), 1)

    def test_nada_que_leer(self):
        for perfil in (None, {"strength_routine": None}, {"strength_routine": {"original_text": "  "}}):
            with self.subTest(perfil=perfil):
                self.assertIsNone(pf.leer_rutina_de_fuerza(perfil))


class PythonYJavaScriptDicenLoMismo(unittest.TestCase):
    def test_los_valores_permitidos(self):
        pares = {
            "MODOS_DE_FUERZA": pf.MODOS, "OBJETIVOS_DE_FUERZA": pf.OBJETIVOS,
            "EQUIPOS_DE_FUERZA": pf.EQUIPOS, "ESTRUCTURAS_DE_FUERZA": pf.ESTRUCTURAS,
            "LEVANTAMIENTOS_CON_BARRA": pf.LEVANTAMIENTOS_CON_BARRA,
        }
        for nombre, python in pares.items():
            with self.subTest(nombre=nombre):
                self.assertEqual(_claves_js(nombre), python)

    def test_los_rangos(self):
        self.assertEqual(_rango_js("EJERCICIOS_POR_GRUPO"), pf.EJERCICIOS_POR_GRUPO)
        self.assertEqual(_rango_js("MINUTOS_POR_SESION"), pf.MINUTOS_POR_SESION)


class LasColumnasCruzanTodasLasCapas(unittest.TestCase):
    """Un campo que una capa guarda y otra no rellena, sin error en ninguna: el fallo que se repite."""

    def _texto(self, ruta):
        return (RAIZ / ruta).read_text(encoding="utf-8")

    def test_en_cada_capa(self):
        capas = {
            "Database/025_perfil_de_fuerza.sql": self._texto("Database/025_perfil_de_fuerza.sql"),
            # Lo que deja cada migración vive en Migraciones::ESPERADO desde la Beta 3.0 (F3); el
            # script verificar_migraciones.php sólo lo recorre.
            "Migraciones::ESPERADO": self._texto("Backend/src/Support/Migraciones.php"),
            "ProfileFields::JSON_COLS": re.search(r"JSON_COLS = \[(.*?)\n    \];",
                                                  self._texto("Backend/src/Support/ProfileFields.php"), re.S).group(1),
            "UserProfileModel": self._texto("PaginaLocal/web_app/server.py"),
            "profile.js": self._texto("Frontend/src/utils/profile.js"),
        }
        for capa, texto in capas.items():
            for columna in COLUMNAS:
                with self.subTest(capa=capa, columna=columna):
                    self.assertIn(columna, texto)

    def test_el_perfil_las_manda_siempre_al_guardar(self):
        """El perfil se guarda entero: un campo que no viajara quedaría en NULL y borraría su rutina."""
        js = self._texto("Frontend/src/utils/profile.js")
        cuerpo = js[js.index("export function assembleProfilePayload"):]
        self.assertIn("strength_profile: perfilDeFuerzaParaGuardar(", cuerpo)
        self.assertIn("strength_routine: rutinaParaGuardar(", cuerpo)

    def test_el_modelo_del_servidor_no_las_descarta(self):
        from web_app.server import UserProfileModel
        cuenta = {"garmin_email": "", "garmin_password": ""}
        modelo = UserProfileModel(name="Fernando", strength_profile={"mode": "rutina"},
                                  strength_routine={"original_text": "Upper A"}, **cuenta)
        self.assertEqual(modelo.model_dump()["strength_profile"], {"mode": "rutina"})
        self.assertIsNone(UserProfileModel(name="Hugo", **cuenta).model_dump()["strength_profile"])


if __name__ == "__main__":
    unittest.main()
