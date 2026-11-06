"""El panel «Salud de la app» (Beta 3.0, F1): lo que sale del servicio y lo que no.

Hasta F1, ver por qué falló una generación exigía SSH y `_runtime/jobs/<id>/worker.log`.
El panel lo lee, y eso abre una puerta: el log de un trabajo lleva datos del atleta.
Los `worker.log` reales de Jessie (atleta 3, septiembre de 2026) traen, línea a línea:

    Connecting to Garmin Connect for <su correo>...
    services.garmin.client - INFO - Resumed existing Garmin OAuth tokens from
        C:\\Users\\josec\\.garminconnect\\44858901e7281db8...

y `spec.json`, junto al log, lleva el perfil entero. Estas pruebas fijan que el correo
y los tokens no salen, que `spec.json` no se lee para el panel, y que un recorte se dice.

Correr con:  python -m pytest AiService/tests -q
"""

import json
import os
import sys
import tempfile
import time
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from bridge import admin  # noqa: E402


def _trabajo(raiz: Path, job_id: str, status: dict, log: list[str] | None = None,
             spec: dict | None = None) -> Path:
    d = raiz / job_id
    d.mkdir(parents=True)
    (d / "status.json").write_text(json.dumps(status), encoding="utf-8")
    if log is not None:
        (d / "worker.log").write_text("\n".join(log), encoding="utf-8")
    if spec is not None:
        (d / "spec.json").write_text(json.dumps(spec), encoding="utf-8")
    return d


class ElSaneado(unittest.TestCase):

    def test_el_correo_de_garmin_del_log_real_no_sale(self):
        linea = "2026-09-15 13:59:55 - INFO - Connecting to Garmin Connect for atleta.prueba@gmail.com..."
        self.assertNotIn("atleta.prueba", admin.sanear(linea))
        self.assertIn("Connecting to Garmin Connect for ***@***", admin.sanear(linea))

    def test_el_hash_de_los_tokens_oauth_no_sale(self):
        # La forma del log real; el hash es inventado y de la misma longitud que el de Garmin.
        linea = ("services.garmin.client - INFO - Resumed existing Garmin OAuth tokens from "
                 "C:\\Users\\josec\\.garminconnect\\44858901e7281db8a0f3c9d2b7e6a15f4c3d2e1f0a9b8c7d")
        self.assertNotIn("44858901e7281db8a0f3c9d2b7e6a15f4c3d2e1f0a9b8c7d", admin.sanear(linea))

    def test_contrasenas_tokens_y_claves(self):
        texto = ('garmin_password: "secreta123" token=abc.def Bearer eyJhbGciOi.xyz '
                 'OPENAI sk-proj-ABCDEFGH12345678 GEMINI AIzaSyA1234567890abcdefghijk')
        limpio = admin.sanear(texto)
        for fuga in ("secreta123", "abc.def", "eyJhbGciOi", "sk-proj-ABCDEFGH", "AIzaSyA1234567890"):
            self.assertNotIn(fuga, limpio)

    def test_lo_que_sirve_para_depurar_se_queda(self):
        """Tapar de más no puede dejar el log inservible: costos, modelos y nodos siguen."""
        linea = "Using max_tokens=16000, temperature=0 for workout_encoder: $0.3903 (239920 tokens, 11 node calls)"
        self.assertEqual(admin.sanear(linea), linea)


class LosErroresDeProveedor(unittest.TestCase):

    def test_cada_error_tiene_su_nombre(self):
        casos = {
            "anthropic.BadRequestError: Your credit balance is too low": "sin_creditos",
            "openai.RateLimitError: Error code: 429 - Too Many Requests": "limite_429",
            "google.genai.errors.ClientError: 403 PERMISSION_DENIED": "credencial",
            "anthropic.InternalServerError: Error code: 529 overloaded_error": "sobrecargado",
            "httpx.ReadTimeout: The read operation timed out": "tiempo_agotado",
        }
        for texto, esperado in casos.items():
            self.assertEqual(admin.error_de_proveedor(texto), esperado, texto)

    def test_un_error_que_no_es_del_proveedor_no_se_etiqueta(self):
        self.assertIsNone(admin.error_de_proveedor("KeyError: 'weekly_plan'"))

    def test_los_milisegundos_de_la_hora_no_son_un_401(self):
        """Al estrenar el panel (2026-10-11), el macrociclo 20261009172815636770 del atleta 2,
        que salió bien (0,31 USD), apareció como «Clave rechazada» por esta línea real."""
        linea = ("2026-10-09 17:36:48,401 - services.ai.reasoning_archive - WARNING - "
                 "No se pudo podar la traza 2026-09-23T17-44-08")
        self.assertIsNone(admin.error_de_proveedor(linea))
        self.assertIsNone(admin.error_de_proveedor("2026-10-09 17:36:48,429 - ERROR - KeyError: 'x'"))


class LosTrabajos(unittest.TestCase):

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self._tmp.name) / "jobs"
        self.subidas = Path(self._tmp.name) / "upload_jobs"
        self.raiz.mkdir()
        self.subidas.mkdir()

    def tearDown(self):
        self._tmp.cleanup()

    def test_spec_json_nunca_se_lee_si_status_dice_el_atleta(self):
        """`spec.json` lleva el perfil (y un instante la contraseña cifrada de Garmin)."""
        _trabajo(self.raiz, "20261009172815636770",
                 {"kind": "week", "user_id": 2, "done": True},
                 spec={"user_id": 2, "profile": {"garmin_password": "NO-DEBE-SALIR"}})
        salida = json.dumps(admin.trabajos({"generacion": self.raiz}))
        self.assertNotIn("NO-DEBE-SALIR", salida)
        self.assertNotIn("garmin_password", salida)

    def test_estado_duracion_costo_y_proveedor(self):
        # El id es el instante de arranque, con microsegundos: aquí en cero para que cuadren los 360 s.
        d = _trabajo(self.raiz, "20261009172815000000",
                     {"kind": "week", "user_id": 2, "done": True,
                      "error": "La generación falló: Error code: 429 de atleta.prueba@gmail.com"},
                     log=["arranca", "openai.RateLimitError: Error code: 429"])
        # status.json se tocó por última vez 6 min después de empezar.
        inicio = time.mktime((2026, 10, 9, 17, 28, 15, 0, 0, -1))
        os.utime(d / "status.json", (inicio + 360, inicio + 360))
        libro = [
            {"ts": "2026-10-09T17:33:00", "user_id": "u2", "kind": "week", "cost_usd": 0.31},
            {"ts": "2026-10-09T17:33:00", "user_id": "u3", "kind": "week", "cost_usd": 9.0},   # otro atleta
            {"ts": "2026-10-08T17:33:00", "user_id": "u2", "kind": "week", "cost_usd": 9.0},   # otro día
        ]
        [t] = admin.trabajos({"generacion": self.raiz}, libro=libro)
        self.assertEqual(t["estado"], "error")
        self.assertEqual(t["proveedor"], "limite_429")
        self.assertNotIn("atleta.prueba", t["error"])
        self.assertEqual(t["duracion_s"], 360)
        self.assertEqual(t["costo_usd"], 0.31)
        self.assertTrue(t["tiene_log"])

    def test_un_trabajo_que_salio_bien_no_lleva_alerta_de_proveedor(self):
        """Un 429 que el cliente reintentó y salió bien no es algo que mirar."""
        _trabajo(self.raiz, "20261009172815000000", {"kind": "week", "user_id": 2, "done": True},
                 log=["openai.RateLimitError: Error code: 429 - reintentando", "Guardando resultados..."])
        [t] = admin.trabajos({"generacion": self.raiz})
        self.assertEqual((t["estado"], t["proveedor"]), ("terminado", None))

    def test_estados_en_cola_esperando_y_colgado(self):
        _trabajo(self.raiz, "20261009100000000001", {"kind": "week", "user_id": 2, "queued": True})
        _trabajo(self.raiz, "20261009100000000002", {"kind": "week", "user_id": 3, "awaiting_answers": True})
        colgado = _trabajo(self.raiz, "20261009100000000003", {"kind": "season", "user_id": 2})
        hace_una_hora = time.time() - 3600
        os.utime(colgado / "status.json", (hace_una_hora, hace_una_hora))
        estados = {t["job_id"][-1]: t["estado"] for t in admin.trabajos({"generacion": self.raiz})}
        self.assertEqual(estados, {"1": "en_cola", "2": "esperando_respuestas", "3": "colgado"})

    def test_una_carpeta_vaciada_por_la_limpieza_no_es_un_trabajo_colgado(self):
        """Al estrenar el panel (2026-10-11): 56 de las 64 carpetas de `_runtime/jobs` estaban vacías
        (la limpieza borra los archivos y en Windows no pudo quitar la carpeta), y el panel decía
        "45 trabajos parecen colgados"."""
        (self.raiz / "20260831150556485858").mkdir()
        _trabajo(self.raiz, "20261009172815000000", {"kind": "week", "user_id": 2, "done": True})
        self.assertEqual([t["job_id"] for t in admin.trabajos({"generacion": self.raiz})], ["20261009172815000000"])

    def test_mezcla_las_dos_colas_del_mas_nuevo_al_mas_viejo_y_respeta_el_limite(self):
        _trabajo(self.raiz, "20261009100000000001", {"kind": "week", "done": True})
        _trabajo(self.subidas, "20261009110000000001", {"done": True})
        _trabajo(self.raiz, "20261009120000000001", {"kind": "season", "done": True})
        lista = admin.trabajos({"generacion": self.raiz, "garmin-upload": self.subidas}, limite=2)
        self.assertEqual([t["job_id"] for t in lista], ["20261009120000000001", "20261009110000000001"])
        self.assertEqual(lista[1]["kind"], "garmin-upload")


class ElLogDeUnTrabajo(unittest.TestCase):

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self._tmp.name)

    def tearDown(self):
        self._tmp.cleanup()

    def test_el_recorte_se_anuncia(self):
        """Un extracto mudo se lee como el log entero (01: recortar sin decirlo está prohibido)."""
        _trabajo(self.raiz, "20261009172815636770", {"done": True},
                 log=[f"línea {i}" for i in range(1, 335)])  # 334, como el log real más largo
        log = admin.log_del_trabajo([self.raiz], "20261009172815636770", 200)
        self.assertEqual(len(log["lineas"]), 200)
        self.assertEqual(log["lineas"][-1], "línea 334")
        self.assertTrue(log["recortado"])
        self.assertEqual(log["aviso"], "Mostrando las últimas 200 de 334 líneas.")

    def test_entero_sin_aviso(self):
        _trabajo(self.raiz, "20261009172815636770", {"done": True}, log=["a", "b"])
        log = admin.log_del_trabajo([self.raiz], "20261009172815636770")
        self.assertFalse(log["recortado"])
        self.assertEqual(log["aviso"], "")

    def test_sale_saneado(self):
        _trabajo(self.raiz, "20261009172815636770", {"done": True},
                 log=["Connecting to Garmin Connect for atleta.prueba@gmail.com..."])
        log = admin.log_del_trabajo([self.raiz], "20261009172815636770")
        self.assertNotIn("atleta.prueba", log["lineas"][0])

    def test_un_id_que_no_es_de_trabajo_no_lee_nada(self):
        """El id llega de una URL: sin esto, «../../secure» leería fuera de la cola."""
        (self.raiz / "secreto").mkdir()
        (self.raiz / "secreto" / "worker.log").write_text("clave", encoding="utf-8")
        for malo in ("../secreto", "secreto", "2026/../../x", ""):
            self.assertIsNone(admin.log_del_trabajo([self.raiz], malo), malo)


class GeneracionesEnLaVentana(unittest.TestCase):

    def test_cuenta_semanas_y_macrociclos_no_el_chat(self):
        """Es el «N» de «0 correcciones en N generaciones»: el chat no es una generación."""
        from datetime import UTC, datetime
        ahora = datetime(2026, 10, 11, 12, 0, tzinfo=UTC)
        libro = [
            {"ts": "2026-10-09T17:36:46-06:00", "kind": "season"},
            {"ts": "2026-10-09T17:42:29-06:00", "kind": "week"},
            {"ts": "2026-10-10T09:00:00-06:00", "kind": "chat"},
            {"ts": "2026-08-01T09:00:00-06:00", "kind": "week"},  # fuera de 30 días
        ]
        self.assertEqual(admin.generaciones_en_ventana(libro, 30, ahora), 2)

    def test_no_cuenta_las_de_antes_de_empezar_a_medir(self):
        """Al estrenar el panel salía "0 correcciones en 65 generaciones" con un libro de redes
        empezado ese día: las 65 no pudieron anotar nada."""
        from datetime import UTC, datetime
        ahora = datetime(2026, 10, 11, 12, 0, tzinfo=UTC)
        libro = [{"ts": "2026-10-09T17:42:29-06:00", "kind": "week"},
                 {"ts": "2026-10-11T08:00:00-06:00", "kind": "week"}]
        desde = datetime(2026, 10, 11, 7, 0, tzinfo=UTC)
        self.assertEqual(admin.generaciones_en_ventana(libro, 30, ahora, desde=desde), 1)


if __name__ == "__main__":
    unittest.main()
