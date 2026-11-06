"""Cada repetición dice UNA repetición, también sin paréntesis (auditoría del 2026-09-24).

Semana real del atleta 2 del 2026-10-07, generada el 2026-09-23: el reloj recibía bien 8 × 50 m,
pero la pantalla y el paso del reloj decían "8× · 8x50m técnica con 20\" pausa", que se lee como
64 × 50. `_work_description` sólo quitaba la cabecera escrita como "Nx(...)", y en natación la
línea va sin paréntesis; en carrera la recuperación se llama "3' trote" y tampoco se quitaba.
Pasaba igual con Haiku y con gemini-3.5-flash-lite: es del código que expande la serie.
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import FormattedWorkoutStep  # noqa: E402


def _serie(texto, tipo="distance", valor=50, rec=20):
    return FormattedWorkoutStep(step_order=2, step_type="interval", duration_type=tipo, duration_value=valor,
                                repeat_count=8, description=texto, recovery_duration_type="time",
                                recovery_duration_value=rec)


class UnTotalNoEsUnPaso(unittest.TestCase):
    def test_un_paso_de_cero_metros_no_llega_al_reloj(self):
        """Réplica del 2026-09-24: Haiku codificó "Total 2.500 m" como un paso de 0 m al final."""
        from services.ai.langgraph.schemas.plan_outputs import expand_formatted_steps
        pasos = [FormattedWorkoutStep(step_order=1, step_type="warmup", duration_type="distance", duration_value=400,
                                      repeat_count=1, description="400 m suave"),
                 FormattedWorkoutStep(step_order=2, step_type="cooldown", duration_type="distance", duration_value=0,
                                      repeat_count=1, description="Total 2.500 m")]
        arbol = expand_formatted_steps(pasos)
        self.assertEqual([p.description for p in arbol], ["400 m suave"])


class LosTextosReales(unittest.TestCase):
    def test_natacion_con_pausa(self):
        paso = _serie('8x50m técnica con 20" pausa').expand()
        self.assertEqual(paso.description, '8x50m técnica con 20" pausa')  # el bloque conserva la línea
        self.assertEqual(paso.substeps[0].description, "50m técnica")

    def test_natacion_con_coma(self):
        self.assertEqual(_serie('6x200m a 1:43–1:47/100m con 30" pausa', valor=200).expand().substeps[0].description,
                         "200m a 1:43–1:47/100m")
        self.assertEqual(_serie('6x200m aeróbico a 1:47–1:53/100m, 30" pausa', valor=200).expand().substeps[0].description,
                         "200m aeróbico a 1:47–1:53/100m")

    def test_carrera_con_trote(self):
        paso = _serie("3x(8' a 5:40–5:55/km, 3' trote)", tipo="time", valor=480, rec=180).expand()
        self.assertEqual(paso.substeps[0].description, "8' a 5:40–5:55/km")

    def test_la_recuperacion_sin_la_palabra_rec(self):
        """Bici del atleta 2 del 2026-10-11: "12' en Z3, 6' en Z2" en el paso de trabajo y "6' en Z2"
        debajo. La recuperación no se llamaba "rec": se reconoce por su duración exacta (360 s)."""
        for texto in ("3x(12' en Z3, 6' en Z2)", "3x12' en Z3 con 6' en Z2 entre bloques"):
            with self.subTest(texto=texto):
                paso = _serie(texto, tipo="time", valor=720, rec=360).expand()
                self.assertEqual(paso.substeps[0].description, "12' en Z3")

    def test_una_duracion_que_no_es_la_recuperacion_no_se_quita(self):
        paso = _serie("4x(5' cadencia baja, 5' cadencia normal)", tipo="time", valor=300, rec=120).expand()
        self.assertEqual(paso.substeps[0].description, "5' cadencia baja, 5' cadencia normal")

    def test_lo_de_siempre_sigue_igual(self):
        self.assertEqual(_serie("5x(3min a 5:12-5:20/km, 2min rec)", tipo="time", valor=180).expand()
                         .substeps[0].description, "3min a 5:12-5:20/km")

    def test_una_linea_sin_cabecera_no_se_toca(self):
        texto = "progresivos rápido y suelto"
        self.assertEqual(_serie(texto, tipo="time", valor=20).expand().substeps[0].description, texto)


if __name__ == "__main__":
    unittest.main()
