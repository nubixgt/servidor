"""De donde salen las claves cuando PaginaLocal vive dentro de otro repo.

Al mover PaginaLocal a su propia carpeta, el `.env` se quedo en la raiz del
proyecto -- un nivel por encima de donde `core/config.py` lo buscaba. Arrancar
la app en local dejaba de encontrar ninguna clave, y lo hacia de la peor
manera: no al arrancar, sino en la primera llamada a un modelo, a mitad de una
generacion ya empezada.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core.config import resolver_env_file  # noqa: E402


class DeDondeSalenLasClaves(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self._tmp.name) / "proyecto" / "PaginaLocal"
        self.raiz.mkdir(parents=True)
        self.addCleanup(self._tmp.cleanup)

    def test_ENV_FILE_manda_sobre_todo(self):
        """Es lo que usa el puente en produccion."""
        (self.raiz / ".env").write_text("", encoding="utf-8")
        elegido = resolver_env_file(self.raiz, "/otro/sitio/.env")
        self.assertEqual(elegido, Path("/otro/sitio/.env"))

    def test_el_propio_si_existe(self):
        """La instalacion suelta de siempre."""
        propio = self.raiz / ".env"
        propio.write_text("", encoding="utf-8")
        self.assertEqual(resolver_env_file(self.raiz, None), propio)

    def test_el_de_arriba_si_no_hay_propio(self):
        """PaginaLocal dentro de un repo mas grande: el .env esta en la raiz."""
        arriba = self.raiz.parent / ".env"
        arriba.write_text("", encoding="utf-8")
        self.assertEqual(resolver_env_file(self.raiz, None), arriba)

    def test_sin_ninguno_devuelve_el_de_arriba_y_no_revienta(self):
        """load_dotenv() con una ruta inexistente no falla: no tener claves se
        avisa despues, con un mensaje que dice cual falta."""
        self.assertEqual(resolver_env_file(self.raiz, None), self.raiz.parent / ".env")

    def test_ENV_FILE_vacio_no_cuenta_como_dicho(self):
        propio = self.raiz / ".env"
        propio.write_text("", encoding="utf-8")
        self.assertEqual(resolver_env_file(self.raiz, ""), propio)


if __name__ == "__main__":
    unittest.main()
