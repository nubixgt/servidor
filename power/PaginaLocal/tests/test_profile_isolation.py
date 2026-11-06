"""v7 -- Ana no puede tocar el plan de Beto. Probado pidiéndolo.

Los tests de `test_auth.py` comprueban las piezas (contraseñas, sesiones).
Éstos comprueban lo único que importa de verdad, y lo hacen por HTTP contra la
app real: **un atleta autenticado pidiendo lo de otro recibe un 403.**

Se escriben desde el lado del atacante a propósito. Un test que sólo verifique
que Ana puede ver lo suyo pasa igual de bien en una app sin ningún cerrojo.

El agujero que cierran era literal: 22 rutas recibían `?profile=` de la URL y
lo usaban tal cual, incluidas `DELETE /api/plans/current` y
`DELETE /api/profiles/{name}`. Y `GET /api/profiles` devolvía todos los
perfiles con la contraseña de Garmin de cada uno descifrada.

Correr con:  python -m unittest discover -s tests -v
"""

import shutil
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from fastapi.testclient import TestClient  # noqa: E402

from core import auth  # noqa: E402


class IsolationTestCase(unittest.TestCase):
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
        patcher = mock.patch.dict(auth._SCRYPT, {"n": 2 ** 4})  # ver test_auth.py
        patcher.start()
        self.addCleanup(patcher.stop)
        self.addCleanup(shutil.rmtree, self._dir, True)

        auth.create_user("ana", "temporal-123", profile="Ana Lopez", must_change_password=False)
        auth.create_user("beto", "temporal-456", profile="Beto Ruiz", must_change_password=False)
        auth.create_user("jose", "temporal-789", is_admin=True, must_change_password=False)

        import web_app.server as server

        self.server = server
        # La cookie de sesión sale con `Secure` por omisión, así que un
        # cliente sobre http:// -- éste, y también un despliegue sin HTTPS --
        # simplemente no la guarda y nadie puede entrar. Es la conducta
        # correcta (obliga a HTTPS) y por eso se apaga aquí explícitamente en
        # vez de aflojar el valor por defecto.
        patcher = mock.patch.object(server, "COOKIE_SECURE", False)
        patcher.start()
        self.addCleanup(patcher.stop)
        self.client = TestClient(server.app)

    def entrar(self, usuario: str, password: str) -> TestClient:
        respuesta = self.client.post("/api/auth/login", json={"username": usuario, "password": password})
        self.assertEqual(respuesta.status_code, 200, respuesta.text)
        return self.client


# Las rutas de lectura que llevan el perfil en la URL. Si alguna deja de estar
# cubierta, este parametrizado lo dice por su nombre.
RUTAS_CON_PERFIL = [
    "/api/plans/current",
    "/api/plans/history",
    "/api/plans/deletion-preview",
    "/api/reports/planning",
    "/api/reports/analysis",
    "/api/reasoning",
    "/api/reasoning/latest",
    "/api/checkin/latest",
    "/api/coach/context",
    "/api/usage",
]


class PedirLoAjenoDaCuatrocientosTres(IsolationTestCase):
    def test_ninguna_ruta_de_lectura_entrega_el_plan_de_otro(self):
        self.entrar("ana", "temporal-123")
        for ruta in RUTAS_CON_PERFIL:
            with self.subTest(ruta=ruta):
                respuesta = self.client.get(ruta, params={"profile": "Beto Ruiz"})
                self.assertEqual(respuesta.status_code, 403, f"{ruta} -> {respuesta.status_code}")

    def test_ana_si_puede_con_el_suyo(self):
        """El contrapeso del test anterior: un cerrojo que bloquea a todo el
        mundo pasaría aquel y rompería el producto."""
        self.entrar("ana", "temporal-123")
        for ruta in RUTAS_CON_PERFIL:
            with self.subTest(ruta=ruta):
                respuesta = self.client.get(ruta, params={"profile": "Ana Lopez"})
                self.assertNotEqual(respuesta.status_code, 403, f"{ruta} -> {respuesta.text[:120]}")

    def test_no_puede_borrar_el_plan_de_otro(self):
        """El peor de todos: `DELETE /api/plans/current?profile=Beto%20Ruiz`
        borraba el plan de Beto sin preguntar nada."""
        self.entrar("ana", "temporal-123")
        respuesta = self.client.delete("/api/plans/current", params={"profile": "Beto Ruiz", "scope": "all"})
        self.assertEqual(respuesta.status_code, 403)

    def test_no_puede_borrar_el_perfil_de_otro(self):
        self.entrar("ana", "temporal-123")
        self.assertEqual(self.client.delete("/api/profiles/Beto Ruiz").status_code, 403)

    def test_no_puede_generar_gasto_en_la_cuenta_de_otro(self):
        """Sin esto, Ana puede quemarle los créditos a Beto -- y el libro de
        costos se los cobraría a él."""
        self.entrar("ana", "temporal-123")
        respuesta = self.client.post("/api/generate-week", json={"profile": "Beto Ruiz"})
        self.assertEqual(respuesta.status_code, 403)

    def test_no_puede_preguntarle_al_coach_por_el_plan_de_otro(self):
        self.entrar("ana", "temporal-123")
        respuesta = self.client.post(
            "/api/coach/chat", json={"profile": "Beto Ruiz", "message": "¿qué entrena mañana?"}
        )
        self.assertEqual(respuesta.status_code, 403)

    def test_no_puede_escribir_un_checkin_en_el_perfil_de_otro(self):
        self.entrar("ana", "temporal-123")
        respuesta = self.client.post("/api/checkin", json={"profile": "Beto Ruiz", "fatigue": 5})
        self.assertEqual(respuesta.status_code, 403)


class LasCredencialesDeGarminNoSalenDeSuDueno(IsolationTestCase):
    """La peor fuga de las 33 rutas, porque son credenciales de un TERCERO.
    `GET /api/profiles` devolvía todos los perfiles con la contraseña de
    Garmin de cada uno descifrada, a quien la pidiera."""

    def _sembrar(self):
        from core.credential_crypto import encrypt_secret

        with mock.patch.object(self.server, "load_profiles") as cargar:
            cargar.return_value = {
                "Ana Lopez": {"name": "Ana Lopez", "garmin_email": "ana@x.com",
                              "garmin_password": encrypt_secret("la-de-ana")},
                "Beto Ruiz": {"name": "Beto Ruiz", "garmin_email": "beto@x.com",
                              "garmin_password": encrypt_secret("la-de-beto")},
            }
            yield

    def test_ana_solo_ve_su_perfil(self):
        from core.credential_crypto import encrypt_secret

        perfiles = {
            "Ana Lopez": {"name": "Ana Lopez", "garmin_email": "ana@x.com",
                          "garmin_password": encrypt_secret("la-de-ana")},
            "Beto Ruiz": {"name": "Beto Ruiz", "garmin_email": "beto@x.com",
                          "garmin_password": encrypt_secret("la-de-beto")},
        }
        self.entrar("ana", "temporal-123")
        with mock.patch.object(self.server, "load_profiles", return_value=perfiles):
            respuesta = self.client.get("/api/profiles")
        self.assertEqual(respuesta.status_code, 200)
        cuerpo = respuesta.text
        self.assertIn("Ana Lopez", cuerpo)
        self.assertNotIn("Beto Ruiz", cuerpo)
        self.assertNotIn("la-de-beto", cuerpo)
        self.assertNotIn("beto@x.com", cuerpo)


class ElOperadorSiPuede(IsolationTestCase):
    """Es quien atiende la beta: tiene que poder mirar el caso que le acaban
    de reportar por WhatsApp."""

    def test_ve_cualquier_perfil(self):
        self.entrar("jose", "temporal-789")
        respuesta = self.client.get("/api/plans/current", params={"profile": "Beto Ruiz"})
        self.assertNotEqual(respuesta.status_code, 403)

    def test_solo_el_operador_ve_el_costo_de_todos(self):
        self.entrar("ana", "temporal-123")
        self.assertEqual(self.client.get("/api/usage/admin").status_code, 403)
        self.client.post("/api/auth/logout")
        self.entrar("jose", "temporal-789")
        self.assertEqual(self.client.get("/api/usage/admin").status_code, 200)

    def test_solo_el_operador_lee_el_log_del_servidor(self):
        """El log es UNO para todo el servidor, con nombres, correos y trazas
        de todos los atletas dentro."""
        self.entrar("ana", "temporal-123")
        self.assertEqual(self.client.get("/api/logs").status_code, 403)

    def test_un_atleta_no_apaga_el_servidor_de_todos(self):
        """Detrás de nginx `request.client.host` es el proxy para TODOS, así
        que la comprobación de loopback sola no habría parado a nadie."""
        self.entrar("ana", "temporal-123")
        self.assertEqual(self.client.post("/api/server/shutdown").status_code, 403)


class SinSesionNoSePasa(IsolationTestCase):
    def test_la_api_responde_401(self):
        for ruta in RUTAS_CON_PERFIL:
            with self.subTest(ruta=ruta):
                respuesta = self.client.get(ruta, params={"profile": "Ana Lopez"})
                self.assertEqual(respuesta.status_code, 401)

    def test_la_pagina_manda_al_login(self):
        respuesta = self.client.get("/", follow_redirects=False)
        self.assertEqual(respuesta.status_code, 302)
        self.assertEqual(respuesta.headers["location"], "/login")

    def test_el_login_es_alcanzable_sin_sesion(self):
        self.assertEqual(self.client.get("/login").status_code, 200)

    def test_cerrar_sesion_vuelve_a_dejar_fuera(self):
        self.entrar("ana", "temporal-123")
        self.assertNotEqual(
            self.client.get("/api/plans/current", params={"profile": "Ana Lopez"}).status_code, 401
        )
        self.client.post("/api/auth/logout")
        self.assertEqual(
            self.client.get("/api/plans/current", params={"profile": "Ana Lopez"}).status_code, 401
        )

    def test_una_contrasena_incorrecta_no_abre_sesion(self):
        respuesta = self.client.post("/api/auth/login", json={"username": "ana", "password": "no-es"})
        self.assertEqual(respuesta.status_code, 401)
        self.assertEqual(
            self.client.get("/api/plans/current", params={"profile": "Ana Lopez"}).status_code, 401
        )

    def test_el_error_no_dice_si_el_usuario_existe(self):
        """El mensaje que distingue "no existe" de "contraseña incorrecta"
        regala una lista de usuarios válidos."""
        inexistente = self.client.post("/api/auth/login", json={"username": "fantasma", "password": "x"})
        existente = self.client.post("/api/auth/login", json={"username": "ana", "password": "x"})
        self.assertEqual(inexistente.json()["detail"], existente.json()["detail"])


class ElGuardiaNoPuedeRomperLaPaginaQueProtege(IsolationTestCase):
    """El bug que reportó el atleta al arrancar con el .bat: *"me manda a la
    página de inicio pero sin mi usuario y no logro tocar nada, ni para
    iniciar sesión ni nada."*

    Causa: `/styles.css` y `/app.js` no estaban en la lista pública, así que
    sin sesión devolvían un 302 a `/login`. El navegador pedía `app.js` y
    recibía el HTML del login — error de sintaxis, `app.js` no se ejecuta. Con
    un `index.html` cacheado de antes de que existiera el login, la app se
    servía desde caché y quedaba muerta: sin perfil, sin listeners, sin manera
    de entrar.

    Un guardia que sirve HTML donde se espera JavaScript no bloquea: rompe.
    """

    def test_el_css_y_el_js_llegan_sin_sesion_y_con_su_tipo(self):
        for ruta, tipo in (("/styles.css", "text/css"), ("/app.js", "javascript")):
            with self.subTest(ruta=ruta):
                respuesta = self.client.get(ruta)
                self.assertEqual(respuesta.status_code, 200)
                self.assertIn(tipo, respuesta.headers["content-type"])
                self.assertGreater(len(respuesta.content), 1000)

    def test_no_llega_html_donde_se_espera_javascript(self):
        """La comprobación literal del fallo: si esto devuelve `<!DOCTYPE`,
        el navegador no ejecuta nada y la app entera muere en silencio."""
        cuerpo = self.client.get("/app.js").text
        self.assertFalse(cuerpo.lstrip().startswith("<"))

    def test_pero_los_datos_siguen_cerrados(self):
        """Abrir los estáticos no puede abrir nada más. Son el mismo CSS y el
        mismo JS para todos, sin un dato dentro; lo de alguien viaja por
        /api/."""
        for ruta in ("/api/profiles", "/api/plans/current", "/api/usage/admin", "/index.html"):
            with self.subTest(ruta=ruta):
                self.assertIn(self.client.get(ruta, follow_redirects=False).status_code, (302, 401))

    def test_el_cascaron_cacheado_se_defiende_solo(self):
        """Segunda línea: aunque el navegador sirva un index.html viejo desde
        su caché, app.js pregunta quién es y se manda a /login. Sin esto el
        atleta se queda mirando una app que responde 401 a todo en silencio."""
        app_js = (Path(__file__).resolve().parents[1]
                  / "web_app/static/app.js").read_text(encoding="utf-8")
        self.assertIn("if (!me.setup_required) location.href = '/login';", app_js)
        self.assertIn("if (me.must_change_password) { location.href = '/login'; return; }", app_js)


class LaTemporalBloqueaTodoHastaQueSeCambia(IsolationTestCase):
    def test_no_se_puede_usar_la_app_con_la_contrasena_del_correo(self):
        auth.create_user("nuevo", "temporal-000", profile="Nuevo Atleta")
        self.client.post("/api/auth/login", json={"username": "nuevo", "password": "temporal-000"})
        respuesta = self.client.get("/api/plans/current", params={"profile": "Nuevo Atleta"})
        self.assertEqual(respuesta.status_code, 403)
        self.assertTrue(respuesta.json().get("must_change_password"))

    def test_cambiarla_desbloquea(self):
        auth.create_user("nuevo", "temporal-000", profile="Nuevo Atleta")
        self.client.post("/api/auth/login", json={"username": "nuevo", "password": "temporal-000"})
        cambio = self.client.post(
            "/api/auth/change-password",
            json={"current_password": "temporal-000", "new_password": "la-mia-de-verdad"},
        )
        self.assertEqual(cambio.status_code, 200)
        # Cambiarla cierra las sesiones: hay que volver a entrar.
        self.client.post("/api/auth/login", json={"username": "nuevo", "password": "la-mia-de-verdad"})
        self.assertNotEqual(
            self.client.get("/api/plans/current", params={"profile": "Nuevo Atleta"}).status_code, 403
        )

    def test_no_se_puede_cambiar_sin_saber_la_actual(self):
        self.entrar("ana", "temporal-123")
        respuesta = self.client.post(
            "/api/auth/change-password",
            json={"current_password": "la-que-sea", "new_password": "otra-cosa-1234"},
        )
        self.assertEqual(respuesta.status_code, 403)


if __name__ == "__main__":
    unittest.main(verbosity=2)
