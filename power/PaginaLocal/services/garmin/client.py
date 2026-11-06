import hashlib
import logging
import os
import shutil
import threading
from collections.abc import Callable
from pathlib import Path

import garth
import requests
from garminconnect import Garmin

logger = logging.getLogger(__name__)


def explicar_error_de_garmin(exc: BaseException) -> str | None:
    """Lo que el ATLETA tiene que leer cuando Garmin falla, o None si no es de Garmin.

    Hasta la Beta 2.0 la pantalla enseñaba la excepción tal cual: "No se pudo
    conectar a Garmin Connect: 401 Client Error: Unauthorized for url: https://
    sso.garmin.com/..." -- en inglés, con una URL, y sin decir qué hacer. Es el
    primer paso de un atleta nuevo, y un error ilegible ahí es un atleta que no
    vuelve. El detalle técnico sigue en el log.

    Se abstiene cuando no reconoce la causa: un mensaje amable que adivina mal
    ("tu contraseña es incorrecta" cuando Garmin estaba caído) es peor que el
    texto crudo, porque manda al atleta a arreglar lo que no está roto.
    """
    texto = f"{type(exc).__name__} {exc}".lower()
    respuesta = getattr(exc, "response", None)
    estado = getattr(respuesta, "status_code", None)

    if "mfa" in texto or "otp" in texto or "two-factor" in texto or "verification code" in texto:
        return ("Tu cuenta de Garmin pide verificación en dos pasos, y la app todavía no puede completarla. "
                "Desactívala en Garmin Connect mientras conectas, o escríbenos desde Opinar.")
    if estado == 429 or "429" in texto or "too many requests" in texto:
        return ("Garmin está limitando las conexiones en este momento. Espera entre 15 y 30 minutos "
                "y vuelve a intentarlo; insistir antes alarga el bloqueo.")
    if estado in (401, 403) or "401" in texto or "unauthorized" in texto or "authentication" in texto:
        return ("Garmin no aceptó tu correo o tu contraseña. Revisa que sean los de tu cuenta de "
                "Garmin Connect (no los de Google) y vuelve a intentarlo.")
    if isinstance(exc, requests.Timeout | requests.ConnectionError) or "timed out" in texto:
        return "Garmin no respondió a tiempo. Vuelve a intentarlo en unos minutos."
    return None

# El candado de la sesion de Garmin.
#
# `garth.client` es un objeto GLOBAL del modulo: `garth.resume()` y
# `garth.login()` lo reconfiguran en sitio, y tanto `garminconnect` como las
# subidas leen ese mismo objeto. Dos atletas atendidos a la vez por el mismo
# proceso no son dos sesiones: son una, y gana la ultima que entro.
#
# Las generaciones no lo necesitan -- cada una corre en su propio proceso
# (AiService/worker.py) -- pero la importacion de Garmin y el refresco de las
# graficas SI se atienden dentro del servicio, y FastAPI los reparte en un
# pool de hilos. Quien use el cliente en un proceso compartido tiene que tomar
# este candado y no soltarlo hasta terminar de leer.
SESION_GARMIN = threading.Lock()


def _clave_de_cuenta(email: str) -> str:
    """Un directorio de tokens por cuenta de Garmin, derivado del correo.

    No es un secreto ni pretende serlo: es un nombre de carpeta estable que no
    deja el correo del atleta escrito en el disco del servidor.
    """
    return hashlib.sha256((email or "").strip().lower().encode("utf-8")).hexdigest()[:16]

# Cuanto se espera a que Garmin conteste, en segundos.
#
# garth trae 10 por defecto, y eso es poco para lo que le pedimos: la
# extraccion baja cientos de actividades una por una y la subida crea entrenos
# con varios tramos. Una noche real dejo esto en el registro una y otra vez:
#
#   ReadTimeoutError: HTTPSConnectionPool(host='connectapi.garmin.com',
#   port=443): Read timed out. (read timeout=10)
#
# Con la extraccion no es grave -- urllib3 reintenta y la sesion acaba
# entrando -- pero en la SUBIDA no habia reintento, asi que un unico pico de
# latencia dejaba una sesion sin subir y el atleta veia un aspa roja junto a
# un entreno perfectamente valido. Diez segundos no describen a Garmin: la
# cuenta real tardo 4 minutos en devolver 173 actividades.
#
# 30 no arregla una caida de Garmin; evita confundir "va lento" con "no
# contesta", que es lo que estaba pasando.
TIEMPO_DE_ESPERA_S = int(os.getenv("GARMIN_TIMEOUT_S", "30"))


class GarminConnectClient:
    """Una sesion de Garmin, aislada por CUENTA.

    El directorio que se recibe (o `GARMINCONNECT_TOKENS`, o `~/.garminconnect`)
    es la BASE, no el destino: dentro se abre una carpeta por cuenta de Garmin.
    Hasta aqui era el destino directo, y eso significaba un unico juego de
    tokens para toda la maquina. Con `garth.resume()` corriendo ANTES de mirar
    la contrasena, el segundo atleta que entraba reanudaba la sesion del
    primero: sus credenciales no se llegaban a usar y recibia los datos del
    otro. Se reprodujo con dos cuentas reales.
    """

    def __init__(self, token_dir: str | None = None):
        self._client: Garmin | None = None
        self._base_dir = Path(
            token_dir
            or os.getenv("GARMINCONNECT_TOKENS")
            or os.getenv("GARTH_HOME")
            or os.path.expanduser("~/.garminconnect")
        )
        # Hasta que `connect()` sepa de quien es la sesion no hay carpeta de
        # cuenta que valga; la base sirve de marcador y nunca se lee sola.
        self._token_dir = self._base_dir
        self._email = ""

    @staticmethod
    def _olvidar_sesion_anterior() -> None:
        """Tira el perfil que `garth.client` trae cacheado de otra cuenta.

        `Client.user_profile` guarda la respuesta de `/socialProfile` en
        `_user_profile` y `configure()` -- que es lo que hacen `resume` y
        `login` -- no la borra. Sin esto, aislar los tokens no bastaba: el
        segundo atleta entraba con su sesion correcta y `client.username`,
        `client.profile` y el `get_full_name()` de garminconnect seguian
        devolviendo los del primero.
        """
        try:
            garth.client._user_profile = None
        except Exception:  # noqa: BLE001 - una version de garth sin ese atributo no es un error
            logger.debug("No se pudo limpiar el perfil cacheado de garth", exc_info=True)

    def _usuario_de_la_sesion(self) -> str | None:
        """Con que cuenta acabamos de entrar, segun Garmin. None si no se sabe."""
        try:
            return str(garth.client.profile.get("userName") or "").strip() or None
        except Exception:  # noqa: BLE001 - preguntarlo no puede costar el login
            logger.debug("No se pudo leer el usuario de la sesion de Garmin", exc_info=True)
            return None

    def _try_resume_tokens(self) -> bool:
        try:
            self._olvidar_sesion_anterior()
            garth.resume(str(self._token_dir))
            logger.info("Resumed existing Garmin OAuth tokens from %s", self._token_dir)
            return True
        except Exception as exc:
            logger.info("No valid tokens found; need fresh login (%s)", exc)
            return False

    def _la_sesion_es_de(self, email: str) -> bool:
        """Si la sesion viva pertenece a la cuenta que se pidio.

        Garmin devuelve `userName` en su perfil social. En la mayoria de las
        cuentas ES el correo, pero puede ser un alias, asi que sólo se declara
        AJENA cuando la respuesta es otro correo distinto: un alias, un campo
        vacio o un fallo de red no son prueba de nada, y negarle la entrada al
        atleta por una comprobacion que no concluye seria peor que el problema
        que arregla.
        """
        usuario = self._usuario_de_la_sesion()
        if not usuario or "@" not in usuario:
            return True
        return usuario.lower() == (email or "").strip().lower()

    def _descartar_tokens(self) -> None:
        """Borra los tokens de esta cuenta para forzar un login de verdad."""
        try:
            shutil.rmtree(self._token_dir, ignore_errors=True)
        except OSError:
            logger.warning("No se pudieron borrar los tokens de %s", self._token_dir)

    def _fresh_login(
        self,
        email: str,
        password: str,
        mfa_callback: Callable[[], str] | None,
    ) -> None:
        try:
            if mfa_callback is not None:
                code = mfa_callback()
                try:
                    garth.login(email, password, otp=code)
                except TypeError:
                    garth.login(email, password, otp_callback=lambda: code)
            else:
                garth.login(email, password)
            self._olvidar_sesion_anterior()
            garth.save(str(self._token_dir))
            logger.info("Saved Garmin OAuth tokens to %s after fresh login", self._token_dir)
        except requests.HTTPError as http_err:
            body = getattr(http_err.response, "text", "")
            logger.error("Garmin login HTTP error: %s; body=%s", http_err, body[:500])
            raise
        except Exception as exc:
            logger.error("Garmin login failed: %s", exc)
            raise

    def connect(
        self,
        email: str,
        password: str,
        mfa_callback: Callable[[], str] | None = None,
    ) -> None:
        try:
            logger.info("Initializing Garmin Connect client")
            # La carpeta de tokens de ESTA cuenta. Es lo que impide que el
            # `resume` de abajo reanime la sesion de otro atleta: si la cuenta
            # no ha entrado nunca en esta maquina, su carpeta esta vacia y el
            # unico camino que queda es un login con SUS credenciales.
            self._email = (email or "").strip()
            self._token_dir = self._base_dir / _clave_de_cuenta(self._email)
            self._token_dir.mkdir(parents=True, exist_ok=True)
            # Antes de cualquier llamada, y sobre el cliente global de garth:
            # es el mismo objeto que usan tanto garminconnect como las subidas
            # (ver garmin_upload_service._upload_and_schedule), asi que
            # configurarlo aqui los cubre a los dos.
            garth.client.configure(timeout=TIEMPO_DE_ESPERA_S)

            resumed = self._try_resume_tokens()
            if resumed and not self._la_sesion_es_de(self._email):
                # La red de seguridad de lo de arriba. La carpeta por cuenta ya
                # hace imposible el caso conocido, pero si por lo que sea la
                # sesion reanudada resulta ser de otra persona, la respuesta
                # NUNCA es seguir con ella: se tira y se entra de verdad.
                logger.warning(
                    "Los tokens de %s pertenecen a otra cuenta de Garmin. Se descartan.",
                    self._token_dir,
                )
                self._descartar_tokens()
                self._token_dir.mkdir(parents=True, exist_ok=True)
                resumed = False
            if not resumed:
                logger.info("Performing fresh login due to missing or expired tokens")
                self._fresh_login(email, password, mfa_callback)

            self._client = Garmin()
            try:
                self._client.login(tokenstore=str(self._token_dir))
            except requests.HTTPError as http_err:
                status = getattr(getattr(http_err, "response", None), "status_code", None)
                body = getattr(http_err.response, "text", "")
                if status in (401, 403):
                    logger.info("Token resume rejected by server (%s). Performing fresh login", status)
                    self._fresh_login(email, password, mfa_callback)
                    self._client.login(tokenstore=str(self._token_dir))
                else:
                    logger.error("Garmin client login HTTP error: %s; body=%s", http_err, body[:500])
                    raise
            try:
                if hasattr(self._client, "get_full_name"):
                    _ = self._client.get_full_name()
            except requests.HTTPError as http_err:
                status = getattr(getattr(http_err, "response", None), "status_code", None)
                if status in (401, 403):
                    logger.info("Session ping unauthorized (%s). Performing fresh login", status)
                    self._fresh_login(email, password, mfa_callback)
                    self._client.login(tokenstore=str(self._token_dir))
            logger.info("Successfully connected to Garmin Connect")
        except Exception as exc:
            logger.error("Failed to connect to Garmin Connect: %s", exc)
            raise

    @property
    def client(self) -> Garmin:
        if self._client is None:
            raise RuntimeError("GarminConnectClient not connected")
        return self._client

    def disconnect(self) -> None:
        if self._client:
            self._client = None
            logger.info("Disconnected from Garmin Connect")

    def __enter__(self):
        return self

    def __exit__(self, exc_type, _exc_val, _exc_tb):
        self.disconnect()
