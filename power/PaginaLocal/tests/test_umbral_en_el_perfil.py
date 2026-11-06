"""El perfil dice qué umbral usa el plan (Beta 3.0, F10).

Auditoría del 2026-09-23: el perfil del atleta 2 enseñaba el umbral de Garmin (5:53/km) y su plan
usaba el ajustado a un esfuerzo de ~1 hora (5:43; en su resumen de hoy, 341,7 s/km = 5:42) sin
decirlo en ninguna parte de esa pantalla. Ahora el resumen guarda también el de Garmin antes del
ajuste y el perfil escribe la frase bajo el campo, sólo cuando no coinciden.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.prescription import AthletePrescription, prescription_to_state  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


class ElResumenLoGuarda(unittest.TestCase):

    def test_guarda_el_de_garmin_antes_de_ajustar(self):
        p = AthletePrescription()
        p.threshold_pace_sec_per_km, p.threshold_pace_source, p.umbral_ajustado_desde = 341.7, "garmin_ajustado", 353.0
        estado = prescription_to_state(p)
        self.assertEqual((estado["threshold_pace_source"], estado["umbral_ajustado_desde"]), ("garmin_ajustado", 353.0))


class ElPerfilLoDice(unittest.TestCase):

    def test_la_frase_y_cuando_sale(self):
        vista = (RAIZ / "Frontend/src/views/admin/ProfileView.vue").read_text(encoding="utf-8")
        # Desde el 2026-10-21, como pareja de datos («Tu plan usa 5:40/km» y por qué) y no como una línea chica.
        self.assertIn('v-if="umbralDelPlan"', vista)
        self.assertIn("Tu plan usa <b", vista)
        # Y dice cuál marca Garmin y por qué el plan usa otro, sin pedirle nada al atleta.
        self.assertIn("de tu Garmin, porque tus tiempos de carrera dicen que puedes más", vista)
        self.assertIn("Math.abs(aqui - delPlan) < 3) return ''", vista, "si coincide no hay nada que aclarar")


if __name__ == "__main__":
    unittest.main()
