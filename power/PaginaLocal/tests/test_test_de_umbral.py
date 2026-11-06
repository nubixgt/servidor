"""El test de umbral: la cuenta sobre las vueltas del reloj, y cuándo se niega a hacerla.

Motivo, con los números reales: los informes del atleta Hugo (2026-09-07) tenían
el ritmo umbral de Garmin descartado (6:23/km, más lento que su maratón), un
umbral puesto desde su predicción de 10K (4:27/km) y zonas de pulso de fábrica;
el propio informe decía que lo resolvería un test de umbral y la app no tenía
dónde hacerlo.

Las vueltas van en el formato de `data_extractor.procesar_lap` (segundos,
metros, m/s, ppm, vatios). Las de `SESIONES_REALES` son del atleta 2, leídas de
su cuenta. **La primera versión del análisis, probada contra esa cuenta el
2026-09-14, aceptó como test las cuatro de abajo que no lo son** -- sus series
del 9 y del 2 de septiembre y dos rodajes suaves -- y dio FC umbral de 137-143
ppm a un atleta cuyo Garmin mide 176. Cada una es ahora una regresión.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.test_de_umbral import PROTOCOLOS, analizar_test, describir_tests  # noqa: E402


def vuelta(minutos, ritmo_s_km=None, fc=None, metros=None, potencia=None, desnivel=None):
    segundos = minutos * 60
    if metros is None and ritmo_s_km:
        metros = segundos / ritmo_s_km * 1000
    v = {"duration_s": segundos, "distance_m": metros, "averageHR_bpm": fc,
         "averageSpeed_ms": (metros / segundos) if metros else None, "elevationGain_m": desnivel}
    if potencia is not None:
        v["averagePower_w"] = potencia
    return v


def reales(filas):
    return [vuelta(m, ritmo, fc) for m, ritmo, fc in filas]


# Atleta 2, vueltas reales: (minutos, ritmo s/km, FC media).
SESIONES_REALES = {
    # 2026-08-23: tirada larga de 2 h 35, pulso subiendo de 144 a 161.
    "tirada_larga": [
        (7.4, 442, 144), (7.4, 443, 146), (7.4, 441, 142), (7.3, 439, 145), (7.6, 456, 149), (7.8, 469, 150),
        (7.6, 458, 151), (7.5, 452, 149), (7.3, 439, 149), (7.6, 457, 151), (7.4, 443, 155), (7.1, 426, 156),
        (7.2, 430, 155), (7.1, 423, 154), (7.2, 432, 157), (7.3, 436, 158), (7.3, 440, 161), (7.0, 421, 161),
        (7.0, 420, 160), (7.0, 420, 160), (6.9, 412, 161),
    ],
    # 2026-09-09 "Ritmo 5K": 8x2' con 2' de trote. La primera versión dio FC umbral 143.
    "series_2x2": [
        (8.3, 500, 127), (2.7, 562, 133), (2.0, 339, 146), (2.0, 614, 131), (2.0, 293, 153), (2.0, 747, 132),
        (2.0, 306, 139), (2.0, 842, 131), (2.0, 306, 137), (2.0, 761, 135), (2.0, 330, 151), (2.0, 747, 136),
        (2.0, 326, 150), (2.0, 727, 133), (2.0, 313, 155), (2.0, 750, 141), (2.0, 285, 160), (2.0, 890, 146),
        (10.0, 614, 120), (3.3, 907, 113),
    ],
    # 2026-09-02 "VO2max": 4x3' con 2'30" de trote. La primera versión dio 142.
    "series_4x3": [
        (8.3, 495, 121), (1.7, 529, 142), (3.0, 356, 143), (2.5, 728, 134), (3.0, 312, 151), (2.5, 535, 134),
        (3.0, 307, 158), (2.5, 705, 133), (3.0, 339, 159), (2.5, 672, 130), (5.0, 447, 138), (8.4, 506, 133),
        (10.6, 635, 124), (0.5, 705, 113),
    ],
    # 2026-08-30: rodaje de 36' sin calentamiento. La primera versión dio 142.
    "rodaje_36": [(9.3, 558, 124), (7.5, 449, 140), (7.2, 433, 142), (7.4, 443, 143), (2.2, 458, 138),
                  (2.6, 857, 117)],
}

# El protocolo tal cual, con la vuelta pulsada a los 10 minutos.
TEST_CON_VUELTA_MANUAL = [
    vuelta(15, 370, 138),
    vuelta(10, 280, 165),
    vuelta(20, 282, 172),
    vuelta(10, 390, 142),
]


class LaCuentaDelProtocolo(unittest.TestCase):
    def test_fc_de_los_ultimos_20_y_ritmo_de_los_30(self):
        r = analizar_test(TEST_CON_VUELTA_MANUAL, "run")
        self.assertTrue(r["ok"], r)
        self.assertEqual(r["lthr"], 172)
        # 10' a 4:40 + 20' a 4:42 = 6.397 m en 1.800 s: 4:41/km.
        self.assertEqual(r["ritmo"], "4:41")
        self.assertEqual(r["minutos_ultimos"], 20.0)
        self.assertEqual(r["avisos"], [])

    def test_con_vueltas_automaticas_de_un_km_tambien(self):
        vueltas = [vuelta(6, 360, 132)] * 3 + [vuelta(4.667, 280, fc) for fc in (160, 164, 168, 170, 172, 173)] \
            + [vuelta(6.5, 390, 140)] * 2
        r = analizar_test(vueltas, "run")
        self.assertTrue(r["ok"], r)
        self.assertEqual(r["ritmo"], "4:40")
        self.assertTrue(15 <= r["minutos_ultimos"] <= 24)
        # Los últimos ~20': las cuatro últimas vueltas (168-173), no el principio del test.
        self.assertEqual(r["lthr"], 171)


class LasSesionesRealesQueNoSonUnTest(unittest.TestCase):
    def test_la_tirada_larga_de_2h35(self):
        r = analizar_test(reales(SESIONES_REALES["tirada_larga"]), "run")
        self.assertFalse(r["ok"])
        self.assertNotIn("lthr", r)

    def test_las_series_con_recuperaciones(self):
        for nombre in ("series_2x2", "series_4x3"):
            with self.subTest(nombre):
                r = analizar_test(reales(SESIONES_REALES[nombre]), "run")
                self.assertFalse(r["ok"], r)
                self.assertIn("esfuerzo seguido y parejo", r["motivo"])

    def test_el_rodaje_suave_de_36_minutos(self):
        r = analizar_test(reales(SESIONES_REALES["rodaje_36"]), "run")
        self.assertFalse(r["ok"], r)


class NoEsUnTest(unittest.TestCase):
    def test_un_rodaje_continuo_parejo_no_destaca(self):
        r = analizar_test([vuelta(5, 330, 150)] * 11, "run")
        self.assertFalse(r["ok"])

    def test_sin_calentamiento_no_se_distingue_de_un_rodaje(self):
        r = analizar_test([vuelta(10, 280, 165), vuelta(20, 282, 172), vuelta(4, 390, 142)], "run")
        self.assertFalse(r["ok"])
        self.assertIn("calentamiento", r["motivo"])

    def test_si_no_destaca_sobre_el_calentamiento(self):
        r = analizar_test([vuelta(15, 300, 150), vuelta(10, 290, 155), vuelta(20, 290, 158)], "run")
        self.assertFalse(r["ok"])
        self.assertIn("no destacan sobre el calentamiento", r["motivo"])

    def test_una_sola_vuelta_larga_pide_pulsar_vuelta(self):
        r = analizar_test([vuelta(45, 290, 168)], "run")
        self.assertFalse(r["ok"])
        self.assertIn("pulsa vuelta", r["motivo"])

    def test_sin_pulso_no_hay_numero(self):
        vueltas = [vuelta(15, 370), vuelta(10, 280), vuelta(20, 282), vuelta(10, 390)]
        self.assertFalse(analizar_test(vueltas, "run")["ok"])

    def test_la_natacion_no_se_lee_por_vueltas(self):
        self.assertFalse(analizar_test(TEST_CON_VUELTA_MANUAL, "swim")["ok"])


class ElTerrenoLaCintaYGarmin(unittest.TestCase):
    def test_con_cuestas_solo_vale_el_pulso(self):
        vueltas = [vuelta(15, 370, 138), vuelta(10, 300, 165, desnivel=150), vuelta(20, 305, 172, desnivel=300),
                   vuelta(10, 390, 142)]
        r = analizar_test(vueltas, "run")
        self.assertTrue(r["ok"])
        self.assertIsNone(r["ritmo_s_km"])
        self.assertEqual(r["lthr"], 172)
        self.assertTrue(any("desnivel" in a for a in r["avisos"]))

    def test_en_cinta_solo_vale_el_pulso(self):
        r = analizar_test(TEST_CON_VUELTA_MANUAL, "run", en_cinta=True)
        self.assertIsNone(r["ritmo_s_km"])
        self.assertTrue(any("cinta" in a for a in r["avisos"]))

    def test_se_pone_al_lado_de_la_fc_umbral_de_garmin(self):
        """La forma dice si fue un test, no si fue a tope: eso lo decide el atleta con los dos números."""
        bajo = analizar_test(TEST_CON_VUELTA_MANUAL, "run", lthr_de_garmin=186)
        self.assertTrue(any("tu Garmin tiene 186" in a and "no lo uses" in a for a in bajo["avisos"]))
        alto = analizar_test(TEST_CON_VUELTA_MANUAL, "run", lthr_de_garmin=158)
        self.assertTrue(any("más fiable que la estimación del reloj" in a for a in alto["avisos"]))
        cerca = analizar_test(TEST_CON_VUELTA_MANUAL, "run", lthr_de_garmin=176)
        self.assertEqual(cerca["avisos"], [])


class EnBici(unittest.TestCase):
    def test_con_potenciometro_da_ftp(self):
        vueltas = [vuelta(15, None, 120, metros=6000, potencia=150), vuelta(10, None, 150, metros=6000, potencia=240),
                   vuelta(20, None, 158, metros=12000, potencia=236), vuelta(10, None, 125, metros=4000, potencia=120)]
        r = analizar_test(vueltas, "bike")
        self.assertTrue(r["ok"], r)
        self.assertEqual(r["ftp_w"], 237)
        self.assertEqual(r["lthr"], 158)

    def test_sin_potenciometro_da_la_fc_umbral_y_lo_dice(self):
        vueltas = [vuelta(15, None, 120, metros=6000), vuelta(10, None, 150, metros=6000),
                   vuelta(20, None, 158, metros=12000), vuelta(10, None, 125, metros=4000)]
        r = analizar_test(vueltas, "bike")
        self.assertTrue(r["ok"], r)
        self.assertIsNone(r["ftp_w"])
        self.assertTrue(any("potenciómetro" in a for a in r["avisos"]))


class LoQueLeenLosNodos(unittest.TestCase):
    def test_cada_test_con_su_fecha_y_su_edad(self):
        tests = {"run": {"fecha": "2026-06-01", "lthr": 172, "ritmo_s_km": 281.3},
                 "swim": {"fecha": "2026-09-10", "css_s_100m": 107.5}}
        lineas = describir_tests(tests, hoy=date(2026, 9, 14))
        self.assertIn("Test de carrera del 2026-06-01: FC umbral 172 ppm, ritmo umbral 4:41/km", lineas[0])
        self.assertIn("hace 15 semanas", lineas[0])
        self.assertEqual(lineas[1], "Test de natación del 2026-09-10: CSS 1:48/100 m.")

    def test_sin_tests_no_hay_lineas(self):
        self.assertEqual(describir_tests({}), [])
        self.assertEqual(describir_tests(None), [])

    def test_los_tres_protocolos_existen_y_dicen_como_se_calcula(self):
        self.assertEqual(set(PROTOCOLOS), {"run", "bike", "swim"})
        for p in PROTOCOLOS.values():
            self.assertTrue(p["pasos"] and p["calculo"] and p["titulo"])

    def test_la_pantalla_lee_los_mismos_protocolos(self):
        """Una sola fuente: el JSON del frontend lo genera el script desde PROTOCOLOS."""
        import json

        destino = Path(__file__).resolve().parents[2] / "Frontend" / "public" / "test_de_umbral.json"
        if not destino.exists():
            self.skipTest("test_de_umbral.json no generado todavía")
        self.assertEqual(json.loads(destino.read_text("utf-8"))["protocolos"], PROTOCOLOS)


if __name__ == "__main__":
    unittest.main()
