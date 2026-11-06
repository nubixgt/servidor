"""El chat dio el umbral de carrera como el de bici, con un test de bici hecho la víspera (José, 2026-10-21).

José hizo el test de umbral en bici el martes 20 de octubre (actividad 24790995176, «Test de umbral -
2026-10-20», indoor_cycling, 55'): FC umbral 156 ppm, guardado en su perfil. El miércoles preguntó al chat
«¿Cuál es mi umbral de bici?» y le contestó «176 ppm (LTHR)» con las zonas «Z1 123–140, Z2 141–154, …».

Dos causas, las dos del cable y no del modelo:
- El chat leía sus números SÓLO de la ficha que se escribe al generar la semana (`summary.json`), anterior al
  test. Ahora recibe también sus tests del perfil de hoy.
- La ficha decía «FC umbral (LTHR): 176» sin deporte, y es la de carrera: el modelo la leyó como de bici.

Lo que se vio en su Garmin al verificarlo (sólo lectura): perfil CICLISMO con LTHR 157 (Z2 127-140), perfil
genérico con LTHR 176 (Z2 141-154), y la última bici en la calle (24774065583, 18-oct) con las zonas del
genérico aplicadas. Las zonas que lee la app son las que el reloj aplicó a la última actividad de cada
deporte: un cambio en Garmin Connect se ve con la siguiente actividad.

Correr con:  python -m unittest tests.test_umbral_de_bici_en_el_chat -v
"""

import json
import sys
import tempfile
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import build_coach_context  # noqa: E402

FICHA_VIEJA = "**FC umbral en carrera (LTHR)**: 176 ppm (medida por Garmin)\n**Zonas**: Z2 141-154"
PERFIL = {"name": "José", "threshold_tests": {"bike": {"fecha": "2026-10-20", "activity_id": 24790995176, "lthr": 156}}}


class ElChatVeElTestDeHoy(unittest.TestCase):

    def _contexto(self, perfil):
        with tempfile.TemporaryDirectory() as d:
            (Path(d) / "summary.json").write_text(json.dumps({"prescription": {"card": FICHA_VIEJA}}), encoding="utf-8")
            return build_coach_context(Path(d), profile=perfil, today=date(2026, 10, 21))["context"]

    def test_el_test_de_bici_llega_aunque_la_ficha_sea_de_antes(self):
        texto = self._contexto(PERFIL)
        self.assertIn("Test de bici del 2026-10-20: FC umbral 156 ppm", texto)
        self.assertIn("la de carrera no vale en bici", texto)

    def test_sin_tests_no_se_anade_nada(self):
        self.assertNotIn("Sus tests de umbral", self._contexto({"name": "José"}))


class LaFichaDiceDeQueDeporteEsElUmbral(unittest.TestCase):

    def test_el_lthr_de_la_ficha_es_de_carrera(self):
        prescription = (Path(__file__).resolve().parents[1] / "services/ai/prescription.py").read_text(encoding="utf-8")
        self.assertIn("**FC umbral en carrera (LTHR)**", prescription)
        self.assertNotIn('"\\n**FC umbral (LTHR)**', prescription)


class UsarElTestDeBiciDiceQueCambieSusZonas(unittest.TestCase):

    def test_como_el_de_carrera(self):
        perfil = (Path(__file__).resolve().parents[2] / "Frontend/src/views/admin/ProfileView.vue").read_text(encoding="utf-8")
        cuerpo = perfil[perfil.index("const usarTest = (c) => {"):]
        cuerpo = cuerpo[:cuerpo.index("\n};")]
        self.assertIn("de pulso de ciclismo basadas en tu FC de umbral", cuerpo)


if __name__ == "__main__":
    unittest.main()
