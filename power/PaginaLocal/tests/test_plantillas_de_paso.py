"""Las plantillas para añadir un paso llevan los números del atleta (Beta 3.0, F7).

Con las bandas reales del atleta 2 (umbral 5:35-6:02/km, VO2max 5:08-5:28/km) las series
salen a SU ritmo. Sin bandas, por zona de pulso: nunca un ritmo inventado. En natación, sin
objetivo: el reloj no lo lleva.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.ajuste_de_sesion import expandir  # noqa: E402
from services.ai.plantillas_de_paso import plantillas  # noqa: E402

BANDAS = {"pace_bands": {"vo2max": [307.5, 328.0], "umbral_tempo": [334.9, 362.2]}}


def _por_nombre(lista, nombre):
    return next(p for p in lista if p["nombre"] == nombre)


class LasPlantillas(unittest.TestCase):

    def test_con_sus_bandas_su_ritmo(self):
        umbral = _por_nombre(plantillas("run", BANDAS), "Series al umbral")
        self.assertEqual(umbral["trabajo"]["objetivo"], {"tipo": "pace", "bajo": 335, "alto": 362})

    def test_sin_bandas_zona_de_pulso(self):
        umbral = _por_nombre(plantillas("run", {}), "Series al umbral")
        self.assertEqual(umbral["trabajo"]["objetivo"], {"tipo": "hr_zone", "zona": 4})
        self.assertFalse(any((p.get("objetivo") or {}).get("tipo") == "pace" for p in plantillas("run", None)))

    def test_bici_con_ftp_en_vatios_y_sin_ella_por_pulso(self):
        con = _por_nombre(plantillas("bike", {"power_bands": {"umbral": [182, 210]}}), "Series al umbral")
        self.assertEqual(con["trabajo"]["objetivo"]["tipo"], "power")
        sin = _por_nombre(plantillas("bike", {}), "Series al umbral")
        self.assertEqual(sin["trabajo"]["objetivo"], {"tipo": "hr_zone", "zona": 4})

    def test_natacion_sin_objetivo(self):
        for p in plantillas("swim", BANDAS):
            for parte in (p, p.get("trabajo") or {}, p.get("recuperacion") or {}):
                self.assertIn((parte.get("objetivo") or {"tipo": "none"})["tipo"], ("none",))

    def test_la_fuerza_no_tiene(self):
        self.assertEqual(plantillas("strength", BANDAS), [])

    def test_cada_plantilla_es_un_paso_valido(self):
        """Se expanden y pasan el guardián de unidades como cualquier paso."""
        for deporte in ("run", "bike", "swim", "walk"):
            for p in plantillas(deporte, BANDAS):
                e = {k: v for k, v in p.items() if k != "nombre"}
                pasos = expandir([e], {"sport": deporte, "steps": []})
                self.assertEqual(len(pasos), 1, (deporte, p["nombre"]))


if __name__ == "__main__":
    unittest.main()
