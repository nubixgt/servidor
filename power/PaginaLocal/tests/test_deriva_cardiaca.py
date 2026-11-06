"""La deriva cardíaca sólo se mide donde significa algo, y si no, se calla.

Ver services/ai/aerobic_decoupling.py. Los casos son los del atleta 2, seis
meses hasta el 2026-09-11 (traza `reasoning/2026-09-11T15-47-20`): de 24
sesiones largas, 3 válidas (carreras con potencia: 4,6 · 1,6 · 2,7 %).

- 2026-06-21, carrera 131' por potencia, pulso 129 → 132: deriva 1,6 %.
- 2026-08-23, carrera 155' que ACELERÓ un 6,6 % con el pulso de 148 a 158: su
  "deriva" sale 0,4 % y se leería como base excelente. Fue una progresión.
- 2026-09-05, bici 244' a Palín SIN potencia: +35,6 %, pulso 115 → 142. Terreno.
- 2026-09-02, VO2max: +16,5 %. Series; comparar mitades no mide nada.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.aerobic_decoupling import (  # noqa: E402
    MAX_CAMBIO_DE_SALIDA_PCT,
    medir_deriva,
    medir_sesion,
    render_deriva,
)

HOY = date(2026, 9, 11)
ZONAS = {
    "run": {"3": [153, 166], "4": [167, 179]},
    "bike": {"3": [154, 167], "4": [168, 180]},
}


def tramo(minutos, fc, pico=None, potencia=None, velocidad=3.0, km=None, desnivel=0):
    t = {"duration_s": minutos * 60, "averageHR_bpm": fc, "maxHR_bpm": pico or fc + 6,
         "averageSpeed_ms": velocidad, "distance_m": (km if km is not None else velocidad * minutos * 60 / 1000) * 1000,
         "elevationGain_m": desnivel}
    if potencia is not None:
        t["averagePower_w"] = potencia
    return t


def act(fecha, tipo, tramos):
    return SimpleNamespace(start_time=f"{fecha}T06:00:00.0", activity_type=tipo, laps=tramos,
                           summary=SimpleNamespace(duration=sum(t["duration_s"] for t in tramos)))


# 131' sostenidos por potencia: 6 tramos a 129 ppm y 6 a 132, misma potencia.
SOSTENIDA = act("2026-06-21", "running", [tramo(11, 129, potencia=250)] * 6 + [tramo(11, 132, potencia=250)] * 6)
# La del 23-ago: la potencia sube un 6,6 % en la segunda mitad.
PROGRESION = act("2026-08-23", "running", [tramo(13, 148, potencia=240)] * 6 + [tramo(13, 158, potencia=256)] * 6)
PALIN = act("2026-09-05", "cycling", [tramo(20, 115, velocidad=8.0)] * 6 + [tramo(20, 142, velocidad=6.5)] * 6)
VO2MAX = act("2026-09-02", "running", [tramo(6, 150, pico=168, potencia=300)] * 10)
CORTA = act("2026-09-11", "running", [tramo(5, 140, potencia=230)] * 8)


class SoloDondeSignificaAlgo(unittest.TestCase):
    def test_una_tirada_sostenida_se_mide(self):
        m = medir_sesion(SOSTENIDA, ZONAS)
        self.assertTrue(m["valida"])
        self.assertEqual(m["medida"], "potencia")
        self.assertEqual((m["fc_1"], m["fc_2"]), (129, 132))
        self.assertAlmostEqual(m["deriva_pct"], 2.3, places=1)

    def test_una_progresion_no_pasa_por_base_excelente(self):
        m = medir_sesion(PROGRESION, ZONAS)
        self.assertFalse(m["valida"])
        self.assertEqual(m["motivo"], "no_sostenido")
        self.assertGreater(m["cambio_de_salida_pct"], MAX_CAMBIO_DE_SALIDA_PCT)

    def test_la_bici_sin_potencia_no_confunde_la_ruta_con_la_forma(self):
        self.assertEqual(medir_sesion(PALIN, ZONAS)["motivo"], "sin_potencia")

    def test_las_series_no_se_miden(self):
        self.assertEqual(medir_sesion(VO2MAX, ZONAS)["motivo"], "series")

    def test_por_ritmo_la_cuesta_no_se_mide(self):
        cuesta = act("2026-07-12", "running", [tramo(10, 135, velocidad=2.8, desnivel=40)] * 12)
        self.assertEqual(medir_sesion(cuesta, ZONAS)["motivo"], "desnivel")

    def test_menos_de_una_hora_ni_siquiera_es_candidata(self):
        self.assertIsNone(medir_sesion(CORTA, ZONAS))


class ElBloque(unittest.TestCase):
    def test_sin_ninguna_valida_el_prompt_no_recibe_nada(self):
        """Una sección que anuncia lo que falta invita a razonar sobre el hueco."""
        self.assertIsNone(medir_deriva([PROGRESION, PALIN, VO2MAX], ZONAS, HOY))
        self.assertEqual(render_deriva(None), "")

    def test_dice_cuantas_se_descartaron_y_por_que(self):
        bloque = render_deriva(medir_deriva([SOSTENIDA, PROGRESION, PALIN, VO2MAX, CORTA], ZONAS, HOY))
        self.assertIn("De 4 sesiones largas", bloque)
        self.assertIn("1 de bici sin potencia", bloque)
        self.assertIn("1 sin un esfuerzo sostenido", bloque)
        self.assertIn("1 con series", bloque)

    def test_con_pocas_no_habla_de_tendencia(self):
        bloque = render_deriva(medir_deriva([SOSTENIDA], ZONAS, HOY))
        self.assertIn("puntos sueltos", bloque)
        self.assertIn("una referencia, no un diagnóstico", bloque)

    def test_nada_en_negrita(self):
        self.assertNotIn("**", render_deriva(medir_deriva([SOSTENIDA], ZONAS, HOY)))


if __name__ == "__main__":
    unittest.main()
