"""Cuando Garmin falla, el atleta lee qué pasó y qué hacer — o el error tal cual.

Conectar Garmin es el primer paso de un atleta nuevo en la Beta 2.0. La pantalla
enseñaba "No se pudo conectar a Garmin Connect: 401 Client Error: Unauthorized
for url: https://sso.garmin.com/...". `explicar_error_de_garmin` traduce las
causas que se reconocen sin dudar y se abstiene en el resto: un mensaje amable
que adivina mal manda al atleta a arreglar lo que no está roto.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path
from types import SimpleNamespace

import requests

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.garmin.client import explicar_error_de_garmin  # noqa: E402


def _http(estado):
    return requests.HTTPError(f"{estado} Client Error", response=SimpleNamespace(status_code=estado, text=""))


class LosErroresDeGarminSeEntienden(unittest.TestCase):
    def test_contrasena_rechazada(self):
        self.assertIn("no aceptó tu correo o tu contraseña", explicar_error_de_garmin(_http(401)))

    def test_demasiadas_peticiones(self):
        self.assertIn("limitando las conexiones", explicar_error_de_garmin(_http(429)))

    def test_verificacion_en_dos_pasos(self):
        self.assertIn("verificación en dos pasos", explicar_error_de_garmin(RuntimeError("MFA code required")))

    def test_sin_respuesta(self):
        self.assertIn("no respondió a tiempo", explicar_error_de_garmin(requests.Timeout("read timed out")))

    def test_lo_que_no_reconoce_no_lo_adivina(self):
        """Un fallo del modelo de IA no es un problema de contraseña."""
        self.assertIsNone(explicar_error_de_garmin(ValueError("schema validation failed")))
        self.assertIsNone(explicar_error_de_garmin(_http(500)))


if __name__ == "__main__":
    unittest.main()
