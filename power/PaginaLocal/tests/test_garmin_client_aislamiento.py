"""Una sesión de Garmin por CUENTA, no una por máquina.

## El fallo que cierra este fichero

Se reprodujo con dos cuentas reales el 2026-08-31. El atleta escribió el correo
y la contraseña de su esposa, y la app le devolvió **sus propios datos**.

La causa estaba en tres líneas de `client.py`:

  1. El directorio de tokens era uno solo para toda la máquina
     (`~/.garminconnect`), sin nada que lo separara por cuenta.
  2. `connect()` llamaba a `garth.resume()` **antes** de mirar el correo y la
     contraseña. Si había un token válido, entraba con él.
  3. Por tanto las credenciales de la segunda persona **nunca se usaban**.

No es un fallo de perfil: es una fuga de datos entre cuentas. Con siete
usuarios el viernes, el primero que conectara su Garmin sería el dueño de la
sesión de todos — y una subida de entrenos iría a su reloj, no al del atleta.

## Y un segundo fallo, que el primero tapaba

`garth.client` es un objeto global del módulo y cachea el perfil de Garmin en
`_user_profile`. `configure()` — que es lo que hacen `resume` y `login` — no lo
borra. Así que aislar los tokens no bastaba: el segundo atleta entraba con su
sesión correcta y `client.username` seguía devolviendo el del primero.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.garmin import client as gc  # noqa: E402

JOSE = "jose@example.com"
ESPOSA = "esposa@example.com"


class _GarthFalso:
    """Lo justo de garth para ver a quién se le pide la sesión."""

    def __init__(self):
        self.resumes: list[str] = []
        self.logins: list[tuple[str, str]] = []
        self.saves: list[str] = []
        self.tokens_validos: set[str] = set()   # directorios con sesión guardada
        self.client = mock.MagicMock()
        self.client._user_profile = None

    def resume(self, dir_path: str) -> None:
        self.resumes.append(dir_path)
        if dir_path not in self.tokens_validos:
            raise FileNotFoundError(dir_path)

    def login(self, email: str, password: str, **_kwargs) -> None:
        self.logins.append((email, password))

    def save(self, dir_path: str) -> None:
        self.saves.append(dir_path)
        self.tokens_validos.add(dir_path)


class AislamientoPorCuenta(unittest.TestCase):
    def setUp(self):
        self.base = Path(self.enterContext(__import__("tempfile").TemporaryDirectory()))
        self.garth = _GarthFalso()
        self.enterContext(mock.patch.object(gc, "garth", self.garth))
        # `Garmin` de garminconnect: sólo tiene que dejarse construir y loguear.
        self.enterContext(mock.patch.object(gc, "Garmin", mock.MagicMock()))

    def _conectar(self, email: str) -> gc.GarminConnectClient:
        cliente = gc.GarminConnectClient(token_dir=str(self.base))
        cliente.connect(email, f"clave-de-{email}")
        return cliente

    def test_cada_cuenta_guarda_sus_tokens_en_su_propia_carpeta(self):
        uno = self._conectar(JOSE)
        otra = self._conectar(ESPOSA)
        self.assertNotEqual(uno._token_dir, otra._token_dir)
        # Y ninguna de las dos es la base: la base sólo las contiene.
        self.assertEqual(uno._token_dir.parent, self.base)
        self.assertEqual(otra._token_dir.parent, self.base)

    def test_el_correo_no_queda_escrito_en_el_disco(self):
        cliente = self._conectar(JOSE)
        self.assertNotIn("jose", cliente._token_dir.name)
        self.assertNotIn("@", cliente._token_dir.name)

    def test_la_misma_cuenta_reusa_su_sesion(self):
        self._conectar(JOSE)
        self.assertEqual(len(self.garth.logins), 1)
        self._conectar(JOSE)
        # Segunda vez: reanuda, no vuelve a pedirle la contraseña a Garmin.
        self.assertEqual(len(self.garth.logins), 1)

    def test_EL_FALLO_la_sesion_de_uno_no_sirve_para_entrar_como_otro(self):
        """El caso reproducido. Es la razón de ser de todo este fichero."""
        self._conectar(JOSE)
        self.garth.logins.clear()

        self._conectar(ESPOSA)

        # Tuvo que entrar DE VERDAD, y con las credenciales de ella.
        self.assertEqual(self.garth.logins, [(ESPOSA, f"clave-de-{ESPOSA}")])

    def test_no_se_intenta_siquiera_reanudar_la_carpeta_de_otra_cuenta(self):
        uno = self._conectar(JOSE)
        self.garth.resumes.clear()
        self._conectar(ESPOSA)
        self.assertNotIn(str(uno._token_dir), self.garth.resumes)

    def test_el_perfil_cacheado_de_garth_se_tira_al_cambiar_de_cuenta(self):
        """El segundo fallo: `configure()` no limpia `_user_profile`."""
        self._conectar(JOSE)
        self.garth.client._user_profile = {"userName": JOSE}

        self._conectar(ESPOSA)

        self.assertIsNone(
            self.garth.client._user_profile,
            "El perfil de la cuenta anterior sigue cacheado en el cliente global de garth",
        )


class RedDeSeguridad(unittest.TestCase):
    """La comprobación de identidad, para cuando los tokens ya no bastan.

    La carpeta por cuenta hace imposible el caso conocido. Esto cubre el que no
    lo es: una carpeta cuyo token pertenece, por lo que sea, a otra persona.
    """

    def setUp(self):
        self.base = Path(self.enterContext(__import__("tempfile").TemporaryDirectory()))
        self.garth = _GarthFalso()
        self.enterContext(mock.patch.object(gc, "garth", self.garth))
        self.enterContext(mock.patch.object(gc, "Garmin", mock.MagicMock()))

    def _cliente_con_sesion_de(self, quien: str | None) -> gc.GarminConnectClient:
        cliente = gc.GarminConnectClient(token_dir=str(self.base))
        cliente._usuario_de_la_sesion = lambda: quien
        return cliente

    def test_una_sesion_de_otro_correo_se_descarta_y_se_entra_de_nuevo(self):
        cliente = self._cliente_con_sesion_de(JOSE)
        # Sembramos un token "válido" en la carpeta que le tocará a la esposa.
        destino = self.base / gc._clave_de_cuenta(ESPOSA)
        self.garth.tokens_validos.add(str(destino))

        cliente.connect(ESPOSA, "su-clave")

        self.assertEqual(self.garth.logins, [(ESPOSA, "su-clave")])

    def test_un_alias_que_no_es_un_correo_no_niega_la_entrada(self):
        """Garmin puede devolver un alias en `userName`. Eso no prueba nada."""
        cliente = self._cliente_con_sesion_de("joselito99")
        destino = self.base / gc._clave_de_cuenta(JOSE)
        self.garth.tokens_validos.add(str(destino))

        cliente.connect(JOSE, "su-clave")

        self.assertEqual(self.garth.logins, [], "Reanudó bien; no debió volver a entrar")

    def test_si_no_se_puede_saber_quien_es_se_sigue_adelante(self):
        cliente = self._cliente_con_sesion_de(None)
        destino = self.base / gc._clave_de_cuenta(JOSE)
        self.garth.tokens_validos.add(str(destino))

        cliente.connect(JOSE, "su-clave")

        self.assertEqual(self.garth.logins, [])

    def test_el_mismo_correo_con_otras_mayusculas_es_la_misma_cuenta(self):
        cliente = self._cliente_con_sesion_de(JOSE.upper())
        destino = self.base / gc._clave_de_cuenta(JOSE)
        self.garth.tokens_validos.add(str(destino))

        cliente.connect(JOSE, "su-clave")

        self.assertEqual(self.garth.logins, [])
        # Y la carpeta tiene que ser LA MISMA escriba como escriba el correo.
        self.assertEqual(gc._clave_de_cuenta(JOSE), gc._clave_de_cuenta(f"  {JOSE.upper()} "))


if __name__ == "__main__":
    unittest.main()
