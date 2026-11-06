"""La intensidad se mide por sesión, con 3 zonas bien ancladas, y nunca inventa.

Ver services/ai/intensity_distribution.py. Los números son los del atleta 2
(trazas `reasoning/2026-09-11T16-39-00` y `2026-09-13T13-42-33`):

- Carrera: techo de Z2 152 (primer umbral), umbral de lactato 176 (segundo
  umbral), Z4 desde 167, Z5 desde 180. Ritmo umbral 5:52/km (353 s), primer
  umbral de ritmo ~7:01/km. Natación: techo de Z2 129, Z5 desde 168.
- La primera versión llamaba "por encima del segundo umbral" a tocar Z4: 21
  carreras "altas" en seis meses que, bien ancladas, son 12.
- Sólo con el pulso, sus intervalos a 4:37-5:07/km y su VO2max salían suaves:
  pulso medio de las repeticiones en 135-158. El ritmo los devuelve a alta.
- Progresivos de 30" a 4:18/km y tiradas largas con picos sueltos de 165-172
  dejan de contar como duras.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.intensity_distribution import (  # noqa: E402
    clasificar_sesion,
    forma_del_reparto,
    medir_distribucion,
    referencias_de_prescripcion,
    render_distribucion,
    revisar_reparto_de_la_semana,
)

HOY = date(2026, 9, 11)
ZONAS = {
    "run": {"1": [122, 139], "2": [140, 152], "3": [153, 166], "4": [167, 179], "5": [180, None]},
    "swim": {"1": [93, 111], "2": [112, 129], "3": [130, 148], "4": [149, 167], "5": [168, None]},
    "bike": {"1": [123, 139], "2": [140, 153], "3": [154, 167], "4": [168, 180], "5": [181, None]},
}
REF = {"lthr": 176, "umbral_ritmo_s_km": 353.0, "lt1_ritmo_s_km": 421.0}


def tramo(minutos, media, pico, ritmo=None, desnivel=0):
    t = {"duration_s": minutos * 60, "averageHR_bpm": media, "maxHR_bpm": pico}
    if ritmo:
        t["averageSpeed_ms"] = 1000.0 / ritmo
        t["distance_m"] = minutos * 60 * 1000.0 / ritmo
        t["elevationGain_m"] = desnivel
    return t


def act(fecha, tipo, tramos):
    return SimpleNamespace(
        start_time=f"{fecha}T07:00:00.0", activity_type=tipo, laps=tramos,
        summary=SimpleNamespace(duration=sum(t["duration_s"] for t in tramos)),
    )


# El VO2max del 2-sep: repeticiones a 5:07/km (307 s) con pico de 168 -- ni el
# pico llega a Z5 ni la media al umbral de lactato. Sólo el ritmo lo ve.
VO2MAX = act("2026-09-02", "running", [tramo(12, 138, 150, ritmo=420)]
             + [tramo(2, 158, 168, ritmo=307), tramo(2, 142, 160, ritmo=480)] * 5 + [tramo(12, 130, 140, ritmo=450)])
# La tirada del 23-ago: 155', 80' entre umbrales.
TIRADA = act("2026-08-23", "running", [tramo(75, 148, 160, ritmo=440), tramo(80, 158, 166, ritmo=410)])
SUAVE = act("2026-09-11", "running", [tramo(23, 145, 152, ritmo=450), tramo(6, 155, 165, ritmo=430)])
# Los progresivos del 17-abr: tramos de 30" a 4:18/km con picos de 170.
PROGRESIVOS = act("2026-09-06", "running", [tramo(10, 126, 140, ritmo=570)]
                  + [tramo(0.5, 157, 170, ritmo=258), tramo(0.5, 150, 163, ritmo=900)] * 4 + [tramo(10, 140, 150, ritmo=420)])
# La tirada del 14-jun: 1-km a 8:00-9:22/km, pico suelto de 172.
TIRADA_CON_PICO = act("2026-09-07", "running", [tramo(9, 140, 150, ritmo=500)] * 9 + [tramo(7, 150, 172, ritmo=444)])
BICI_LARGA = act("2026-09-05", "cycling", [tramo(244, 135, 160)])
FUERZA = act("2026-09-03", "strength_training", [tramo(40, 110, 130)])


class SeClasificaConTresZonasBienAncladas(unittest.TestCase):
    def test_tocar_z4_ya_no_es_estar_por_encima_del_segundo_umbral(self):
        """Z4 empieza en 167; su umbral de lactato está en 176."""
        rodaje = act("2026-09-08", "running", [tramo(40, 150, 170)])
        self.assertEqual(clasificar_sesion(rodaje, ZONAS, REF)["clase"], "suave")

    def test_un_tramo_de_un_minuto_en_su_umbral_de_lactato_es_alta(self):
        """El 10K del 25-abr: tramos con el pulso medio en 175-179."""
        carrera = act("2026-04-25", "running", [tramo(10, 160, 170), tramo(5, 177, 186)])
        s = clasificar_sesion(carrera, ZONAS, REF)
        self.assertEqual((s["clase"], s["senal"]), ("alta", "pulso"))

    def test_la_tirada_con_80_minutos_entre_umbrales_es_de_umbral(self):
        s = clasificar_sesion(TIRADA, ZONAS, REF)
        self.assertEqual(s["clase"], "umbral")
        self.assertEqual(s["minutos_zona2"], 80)

    def test_seis_minutos_de_deriva_siguen_siendo_suave(self):
        self.assertEqual(clasificar_sesion(SUAVE, ZONAS, REF)["clase"], "suave")

    def test_los_progresivos_de_30_segundos_no_son_zona_3(self):
        self.assertEqual(clasificar_sesion(PROGRESIVOS, ZONAS, REF)["clase"], "suave")

    def test_un_pico_suelto_en_una_tirada_larga_no_la_hace_dura(self):
        self.assertEqual(clasificar_sesion(TIRADA_CON_PICO, ZONAS, REF)["clase"], "suave")

    def test_nunca_se_usan_las_zonas_de_otro_deporte(self):
        nado = act("2026-09-04", "lap_swimming", [tramo(30, 150, 160)])
        self.assertEqual(clasificar_sesion(nado, ZONAS, REF)["clase"], "umbral")
        self.assertIsNone(clasificar_sesion(nado, {"run": ZONAS["run"]}, REF))

    def test_sin_pulso_por_tramo_se_abstiene(self):
        self.assertIsNone(clasificar_sesion(act("2026-09-04", "running", [{"duration_s": 1800}]), ZONAS, REF))

    def test_la_fuerza_no_es_una_sesion_de_resistencia(self):
        self.assertIsNone(clasificar_sesion(FUERZA, ZONAS, REF))


class ElRitmoVotaCuandoElPulsoLlegaTarde(unittest.TestCase):
    def test_un_vo2max_real_es_alta_por_ritmo(self):
        s = clasificar_sesion(VO2MAX, ZONAS, REF)
        self.assertEqual((s["clase"], s["senal"]), ("alta", "ritmo"))

    def test_sin_ritmo_umbral_el_ritmo_no_vota(self):
        """Y entonces ese VO2max sale suave: es la limitación, dicha."""
        self.assertEqual(clasificar_sesion(VO2MAX, ZONAS, {"lthr": 176})["clase"], "suave")

    def test_en_cinta_el_ritmo_no_vota(self):
        """La cinta del 18-mar marcó tramos a 32:40/km: el acelerómetro no mide ritmo."""
        cinta = act("2026-03-18", "treadmill_running", VO2MAX.laps)
        self.assertEqual(clasificar_sesion(cinta, ZONAS, REF)["clase"], "suave")

    def test_en_cuesta_el_ritmo_no_vota(self):
        cuesta = act("2026-08-09", "running", [tramo(3, 150, 160, ritmo=300, desnivel=200)] * 4)
        self.assertEqual(clasificar_sesion(cuesta, ZONAS, REF)["clase"], "suave")

    def test_en_bici_el_ritmo_no_vota_nunca(self):
        rapida = act("2026-09-06", "cycling", [tramo(30, 140, 150, ritmo=100)])
        self.assertEqual(clasificar_sesion(rapida, ZONAS, REF)["senal"], "pulso")

    def test_las_referencias_salen_de_la_prescripcion_en_sus_dos_formas(self):
        serializada = {"lactate_threshold_hr": 176, "threshold_pace_sec_per_km": 352.7,
                       "pace_bands": {"maraton": [410.0, 421.0]}}
        self.assertEqual(referencias_de_prescripcion(serializada),
                         {"lthr": 176.0, "lthr_bici": None, "umbral_ritmo_s_km": 352.7, "lt1_ritmo_s_km": 421.0})
        # La FC umbral en bici sólo existe con un test en bici.
        self.assertEqual(referencias_de_prescripcion({**serializada, "lthr_por_deporte": {"bike": 158}})["lthr_bici"],
                         158.0)
        banda = SimpleNamespace(slow_sec_per_km=421.0)
        objeto = SimpleNamespace(lactate_threshold_hr=176, threshold_pace_sec_per_km=352.7, pace_bands={"maraton": banda})
        self.assertEqual(referencias_de_prescripcion(objeto)["lt1_ritmo_s_km"], 421.0)
        self.assertEqual(referencias_de_prescripcion(None)["umbral_ritmo_s_km"], None)


class ElReparto(unittest.TestCase):
    def test_cuenta_sesiones_dentro_de_la_ventana(self):
        vieja = act("2026-07-01", "running", [tramo(40, 178, 185)])
        d = medir_distribucion([VO2MAX, TIRADA, SUAVE, BICI_LARGA, FUERZA, vieja], ZONAS, HOY, referencias=REF)
        self.assertEqual(d["sesiones"], {"suave": 2, "umbral": 1, "alta": 1, "total": 4})
        self.assertEqual(d["no_resistencia"], 1)
        self.assertEqual(d["altas_por_ritmo"], 1)
        self.assertEqual(d["sesiones_de_umbral"][0]["fecha"], "2026-08-23")

    def test_sin_muestras_no_hay_numero(self):
        self.assertIsNone(medir_distribucion([VO2MAX, SUAVE], ZONAS, HOY, referencias=REF))

    def test_un_atleta_sin_zonas_recibe_cero_tokens(self):
        """Igual que un atleta sano recibe cero tokens sobre lesiones."""
        d = medir_distribucion([VO2MAX, TIRADA, SUAVE, BICI_LARGA], None, HOY, referencias=REF)
        self.assertEqual(render_distribucion(d), "")

    def test_las_formas(self):
        self.assertEqual(forma_del_reparto(16, 1, 7), "polarizado")
        self.assertEqual(forma_del_reparto(16, 5, 2), "piramidal")
        self.assertEqual(forma_del_reparto(4, 5, 1), "umbral")
        self.assertEqual(forma_del_reparto(4, 1, 5), "alta intensidad")
        self.assertEqual(forma_del_reparto(9, 0, 0), "todo suave")


class ElBloque(unittest.TestCase):
    def setUp(self):
        otro_vo2max = act("2026-09-09", "running", VO2MAX.laps)
        self.d = medir_distribucion([VO2MAX, otro_vo2max, TIRADA, SUAVE, BICI_LARGA], ZONAS, HOY, referencias=REF)
        self.bloque = render_distribucion(self.d)

    def test_dice_que_describe_y_que_no_hay_un_reparto_para_todos(self):
        self.assertIn("describe, no es un objetivo", self.bloque)
        self.assertIn("No hay un reparto correcto para todos", self.bloque)

    def test_ya_no_llama_segundo_umbral_a_la_z4(self):
        self.assertNotIn("tocó Z4", self.bloque)
        self.assertIn("su umbral de lactato", self.bloque)

    def test_describe_el_pulso_que_llega_tarde(self):
        self.assertIn("2 de sus sesiones altas lo son sólo por el ritmo", self.bloque)

    def test_ningun_numero_va_en_negrita(self):
        """Un número en negrita gana a cualquier matiz escrito debajo."""
        self.assertNotIn("**", self.bloque)

    def test_avisa_de_que_el_tiempo_subestima_la_zona_3(self):
        self.assertIn("subestima la zona 3", self.bloque)

    def test_la_nota_de_natacion_solo_aparece_si_hay_nados_intensos(self):
        self.assertNotIn("Natación:", self.bloque)
        nado = act("2026-09-04", "lap_swimming", [tramo(30, 150, 169)])
        d = medir_distribucion([VO2MAX, TIRADA, SUAVE, BICI_LARGA, nado], ZONAS, HOY, referencias=REF)
        self.assertIn("Murúa", render_distribucion(d))


class UnRepartoGuardadoConElFormatoAnterior(unittest.TestCase):
    """El reparto vive en summary.json, en MySQL, y el chat lo relee en cada
    mensaje. El atleta 2 tenía uno guardado con las claves de antes del
    2026-09-13 (`minutos_pct` con zonas del reloj, `minutos_z3`): leerlas con
    acceso directo tumbaba el chat hasta la siguiente generación."""

    VIEJO = {
        "dias": 28, "hasta": "2026-09-11", "sesiones": {"suave": 15, "umbral": 1, "alta": 7, "total": 23},
        "forma": "polarizado", "por_deporte": {"run": {"suave": 7, "umbral": 1, "alta": 3}},
        "minutos_pct": {"z12": 87, "z3": 13, "z45": 0},
        "sesiones_de_umbral": [{"fecha": "2026-08-23", "deporte": "run", "minutos": 155, "minutos_z3": 80}],
        "sin_pulso": 0, "no_resistencia": 9,
    }

    def test_se_pinta_sin_romper(self):
        bloque = render_distribucion(self.VIEJO)
        self.assertIn("23 sesiones", bloque)
        self.assertIn("155', 80' en zona 2", bloque)

    def test_no_reinterpreta_las_zonas_del_reloj_como_3_zonas(self):
        self.assertNotIn("Por tiempo", render_distribucion(self.VIEJO))

    def test_lo_que_no_tiene_forma_de_reparto_no_se_pinta(self):
        self.assertEqual(render_distribucion({"sesiones": None}), "")
        self.assertEqual(render_distribucion({"sesiones": {"total": 0}}), "")


class ElLintDeLaSemana(unittest.TestCase):
    """Nunca bloquea: devuelve un aviso para el log."""

    @staticmethod
    def dia(sport, clase):
        return {"sport": sport, "intensity_class": clase, "is_rest": False}

    def test_la_mini_semana_del_domingo_con_un_solo_vo2max_no_avisa(self):
        semana = [self.dia("run", "vo2max"), self.dia("bike", "easy"), self.dia("swim", "easy")]
        self.assertIsNone(revisar_reparto_de_la_semana(semana, 0.2))

    def test_dos_calidades_en_tres_sesiones_pasan_un_techo_de_0_2(self):
        semana = [self.dia("run", "vo2max"), self.dia("run", "tempo_threshold"), self.dia("swim", "easy")]
        self.assertIn("2 sesiones duras de 3", revisar_reparto_de_la_semana(semana, 0.2))

    def test_la_carrera_no_cuenta_contra_el_techo(self):
        """Primera semana real con el lint (atleta 2, 2026-09-13): avisó "2 duras
        de 5" y la segunda era el 5K Monterrico."""
        semana = [
            {"sport": "strength", "intensity_class": "strength_freeform", "is_rest": False},
            self.dia("bike", "easy"), self.dia("run", "repeticiones"), self.dia("swim", "easy"),
            self.dia("swim", "other"), self.dia("run", "neuromuscular"),
            {"sport": "rest", "intensity_class": "rest", "is_rest": True},
            self.dia("run", "race_day"),
        ]
        self.assertIsNone(revisar_reparto_de_la_semana(semana, 0.25))

    def test_fuerza_descanso_y_sin_techo_no_cuentan(self):
        semana = [self.dia("strength", "strength_freeform"), {"is_rest": True, "sport": "rest"},
                  self.dia("run", "vo2max"), self.dia("run", "repeticiones")]
        self.assertIsNone(revisar_reparto_de_la_semana(semana, None))
        self.assertIn("2 sesiones duras de 2", revisar_reparto_de_la_semana(semana, 0.2))


if __name__ == "__main__":
    unittest.main()
