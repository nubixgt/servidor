"""Toda migración de Database/ tiene su comprobación en Backend/scripts/verificar_migraciones.php.

Las migraciones se corren a mano. El 2026-09-14 la base local tenía la 018 sin
aplicar y nada lo decía, porque el código aguanta que falten. El verificador mira
lo que cada una deja en la base; esta prueba falla si se añade una migración sin
decirle al verificador qué dejar.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]


class CadaMigracionSeComprueba(unittest.TestCase):
    def test_todas_las_migraciones_numeradas_estan_en_el_verificador(self):
        numeradas = {p.name[:3] for p in (RAIZ / "Database").glob("[0-9][0-9][0-9]_*.sql")}
        # La lista vive en App\\Support\\Migraciones desde la Beta 3.0 (F3); el script la usa.
        verificador = (RAIZ / "Backend" / "src" / "Support" / "Migraciones.php").read_text("utf-8")
        script = (RAIZ / "Backend" / "scripts" / "verificar_migraciones.php").read_text("utf-8")
        self.assertIn("Migraciones::problemas($pdo)", script)
        comprobadas = set(re.findall(r"'(\d{3})' =>", verificador))
        self.assertEqual(numeradas - comprobadas, set())
        self.assertEqual(comprobadas - numeradas, set())


if __name__ == "__main__":
    unittest.main()
