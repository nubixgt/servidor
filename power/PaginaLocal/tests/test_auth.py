"""v7 -- la puerta, y el cerrojo entre atletas.

Antes de v7 la app no tenía autenticación de ninguna clase. Eso era correcto
mientras escuchaba en 127.0.0.1 y la usaba una persona. Con atletas de pago en
un servidor, las mismas 33 rutas significaban:

- **22 rutas recibían `?profile=` y no comprobaban de quién era.** Escribir
  otro nombre bastaba para leer, regenerar o BORRAR el plan ajeno.
- **`GET /api/profiles` devolvía todos los perfiles con la contraseña de
  Garmin de cada uno en claro.** La peor de las fugas: son credenciales de un
  tercero (Garmin), no datos de entrenamiento.
- **`POST /api/server/shutdown` sólo comprobaba que la llamada viniera de
  loopback** — y detrás de nginx, `request.client.host` es el proxy para
  TODOS, así que ese filtro habría dejado a cualquier atleta apagar el
  servidor de todos.

Los tests de abajo son la prueba de que cada uno de esos cuatro está cerrado,
escritos desde el lado del atacante: pedir lo ajeno y esperar un 403.

Correr con:  python -m unittest discover -s tests -v
"""

import shutil
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core import auth  # noqa: E402


class AuthTestCase(unittest.TestCase):
    """Usuarios y sesiones en un directorio temporal: estos tests escriben."""

    def setUp(self):
        self._dir = Path(tempfile.mkdtemp())
        for name, value in (
            ("SECURE_DIR", self._dir),
            ("USERS_FILE", self._dir / "users.json"),
            ("SESSIONS_FILE", self._dir / "sessions.json"),
        ):
            patcher = mock.patch.object(auth, name, value)
            patcher.start()
            self.addCleanup(patcher.stop)
        self.addCleanup(shutil.rmtree, self._dir, True)
        # scrypt con n=2**15 tarda ~100 ms por hash a propósito. Multiplicado
        # por los ~30 hashes de esta suite serían 3 segundos de reloj en cada
        # corrida completa; se baja SOLO aquí, y el test de abajo comprueba
        # que el valor real de producción no se movió.
        patcher = mock.patch.dict(auth._SCRYPT, {"n": 2 ** 4})
        patcher.start()
        self.addCleanup(patcher.stop)


class LaContrasenaSeGuardaComoDebe(AuthTestCase):
    def test_nunca_se_guarda_en_claro(self):
        auth.create_user("ana", "temporal-123")
        crudo = auth.USERS_FILE.read_text(encoding="utf-8")
        self.assertNotIn("temporal-123", crudo)

    def test_dos_usuarios_con_la_misma_contrasena_no_comparten_hash(self):
        """Sal por usuario. Sin ella, una tabla precalculada rompe las dos a
        la vez y ver dos hashes iguales ya delata que comparten contraseña."""
        auth.create_user("ana", "la-misma-123")
        auth.create_user("beto", "la-misma-123")
        usuarios = auth.load_users()
        self.assertNotEqual(usuarios["ana"]["password"]["hash"], usuarios["beto"]["password"]["hash"])
        self.assertNotEqual(usuarios["ana"]["password"]["salt"], usuarios["beto"]["password"]["salt"])

    def test_la_contrasena_correcta_entra_y_la_de_al_lado_no(self):
        auth.create_user("ana", "temporal-123")
        self.assertIsNotNone(auth.authenticate("ana", "temporal-123"))
        self.assertIsNone(auth.authenticate("ana", "temporal-124"))

    def test_el_usuario_no_distingue_mayusculas(self):
        auth.create_user("Ana", "temporal-123")
        self.assertIsNotNone(auth.authenticate("ANA", "temporal-123"))

    def test_un_usuario_inexistente_no_revienta(self):
        self.assertIsNone(auth.authenticate("fantasma", "loquesea"))

    def test_no_se_puede_crear_dos_veces_el_mismo(self):
        auth.create_user("ana", "temporal-123")
        with self.assertRaises(ValueError):
            auth.create_user("ana", "otra-cosa-123")

    def test_una_contrasena_corta_se_rechaza(self):
        with self.assertRaises(ValueError):
            auth.create_user("ana", "corta")


class LaTemporalNoSeQuedaSiendoLaContrasena(AuthTestCase):
    def test_nace_marcada_para_cambio(self):
        usuario = auth.create_user("ana", "temporal-123")
        self.assertTrue(usuario["must_change_password"])

    def test_cambiarla_levanta_la_marca(self):
        auth.create_user("ana", "temporal-123")
        auth.set_password("ana", "la-mia-de-verdad")
        self.assertFalse(auth.load_users()["ana"]["must_change_password"])
        self.assertIsNotNone(auth.authenticate("ana", "la-mia-de-verdad"))
        self.assertIsNone(auth.authenticate("ana", "temporal-123"))


class CambiarLaContrasenaEchaAQuienEstuviera(AuthTestCase):
    """Si cambiarla no cerrara las sesiones abiertas, no serviría de nada en
    el único momento en que hace falta: cuando alguien más la sabe."""

    def test_las_sesiones_abiertas_mueren(self):
        auth.create_user("ana", "temporal-123")
        token, _ = auth.start_session("ana")
        self.assertIsNotNone(auth.session_user(token))

        auth.set_password("ana", "la-mia-de-verdad")
        self.assertIsNone(auth.session_user(token))

    def test_borrar_al_usuario_tambien(self):
        auth.create_user("ana", "temporal-123")
        token, _ = auth.start_session("ana")
        auth.delete_user("ana")
        self.assertIsNone(auth.session_user(token))

    def test_la_sesion_de_otro_no_se_toca(self):
        auth.create_user("ana", "temporal-123")
        auth.create_user("beto", "temporal-456")
        token_ana, _ = auth.start_session("ana")
        token_beto, _ = auth.start_session("beto")

        auth.set_password("ana", "la-mia-de-verdad")
        self.assertIsNone(auth.session_user(token_ana))
        self.assertIsNotNone(auth.session_user(token_beto))


class LasSesiones(AuthTestCase):
    def test_un_token_inventado_no_vale(self):
        auth.create_user("ana", "temporal-123")
        self.assertIsNone(auth.session_user("token-inventado"))
        self.assertIsNone(auth.session_user(None))
        self.assertIsNone(auth.session_user(""))

    def test_dos_sesiones_del_mismo_usuario_son_distintas(self):
        auth.create_user("ana", "temporal-123")
        primero, _ = auth.start_session("ana")
        segundo, _ = auth.start_session("ana")
        self.assertNotEqual(primero, segundo)

    def test_una_sesion_caducada_no_vale(self):
        auth.create_user("ana", "temporal-123")
        token, _ = auth.start_session("ana")
        sesiones = auth._load_sessions()
        sesiones[token]["expires"] = "2020-01-01T00:00:00+00:00"
        auth._write(auth.SESSIONS_FILE, sesiones)
        self.assertIsNone(auth.session_user(token))

    def test_sobreviven_a_un_reinicio(self):
        """Se guardan en disco a propósito: un --reload en desarrollo o un
        reinicio del servicio no puede desconectar a todos los atletas a
        media generación."""
        auth.create_user("ana", "temporal-123")
        token, _ = auth.start_session("ana")
        self.assertTrue(auth.SESSIONS_FILE.exists())
        self.assertIsNotNone(auth.session_user(token))

    def test_cerrar_sesion_invalida_el_token(self):
        auth.create_user("ana", "temporal-123")
        token, _ = auth.start_session("ana")
        auth.end_session(token)
        self.assertIsNone(auth.session_user(token))


class ElKdfDeProduccionEsLento(unittest.TestCase):
    """Sin el parcheo de setUp: aquí se ve el valor real. Si alguien lo baja
    para acelerar la suite y lo deja puesto, las contraseñas de la beta pasan
    a ser rompibles a fuerza bruta."""

    def test_scrypt_no_se_aflojo(self):
        self.assertGreaterEqual(auth._SCRYPT["n"], 2 ** 14)


class ElServidorNoSeAbreSolo(unittest.TestCase):
    """Propiedades del middleware que no dependen de levantar la app."""

    def test_la_lista_publica_es_corta_y_no_lleva_datos(self):
        """Todo lo que no esté aquí exige sesión, incluida una ruta que
        todavía no existe. Que la lista sea corta es la propiedad: cada
        entrada nueva es una decisión de abrir algo."""
        from web_app.server import _PUBLIC_PATHS

        for ruta in _PUBLIC_PATHS:
            with self.subTest(ruta=ruta):
                self.assertFalse(ruta.startswith("/api/profiles"))
                self.assertFalse(ruta.startswith("/api/plans"))
                self.assertFalse(ruta.startswith("/api/generate"))
                self.assertFalse(ruta.startswith("/api/usage"))
                self.assertFalse(ruta.startswith("/api/server"))

    def test_la_cookie_va_cerrada_por_omision(self):
        """Un despliegue que se olvide de configurarla queda seguro, no
        abierto. El desarrollo la abre explícitamente.

        Se prueba la función y no la constante: recargar el módulo para leer
        su valor por omisión rebindea sus globales, y otro test que ya tenía
        una referencia al objeto viejo deja de ver los cambios. Pasó -- rompió
        el 409 de `delete_current_generation` en test_plan_history."""
        import os

        from web_app.server import _env_flag

        with mock.patch.dict(os.environ, {}, clear=False):
            os.environ.pop("COOKIE_SECURE", None)
            self.assertTrue(_env_flag("COOKIE_SECURE"))
        for apagado in ("false", "0", "no", "FALSE"):
            with self.subTest(valor=apagado), mock.patch.dict(os.environ, {"COOKIE_SECURE": apagado}):
                self.assertFalse(_env_flag("COOKIE_SECURE"))

    def test_el_modo_de_lanzamiento_es_el_de_por_defecto(self):
        from web_app.server import DEFAULT_AI_MODE

        self.assertEqual(DEFAULT_AI_MODE, "pro")


if __name__ == "__main__":
    unittest.main(verbosity=2)
