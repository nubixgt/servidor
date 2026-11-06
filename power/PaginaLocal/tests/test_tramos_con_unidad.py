"""Los tramos llevan la unidad en el nombre, y quien los lee lee esas claves.

## El fallo, con los números reales

`payload_pruning._lap_is_artifact` descarta los tramos que son una pulsación
del botón: menos de **5 segundos** y menos de **20 metros**. Los dos umbrales
fueron siempre los correctos. Lo que estaba mal era la unidad de lo que se
comparaba contra ellos: `get_activity_laps` entregaba `duration` en MINUTOS y
`distance` en KILÓMETROS, así que la regla que se aplicaba de verdad era
**"menos de 5 minutos y menos de 20 kilómetros"** — o sea, casi cualquier
tramo que no fuera un rodaje largo.

Medido contra la cuenta real del atleta 2 el 2026-09-10, ventana de 10 días:

    Guatemala - Ritmo 5K   20 tramos -> 2  (WARMUP y COOLDOWN)
    Guatemala - VO2max     14 tramos -> 4  (WARMUP y 3 COOLDOWN)
    Natación en piscina    24 tramos -> 2
    --------------------------------------------------------
    124 tramos reales -> 40 llegaban al modelo

El 68% borrado, y el **100% de las repeticiones**. El sesgo no era aleatorio,
que es lo que lo hacía caro: borraba exactamente el trabajo corto e intenso y
dejaba intacto el largo y fácil. Con esa entrada, el planificador de temporada
escribió *"el análisis de laps revela que en realidad son rodajes sostenidos
sin estructura de intervalos"* sobre un atleta que el día antes había hecho
8 × 2' a 4:45-5:39/km, y aplazó el VO2max a la semana 21 de 24.

## Por qué esta prueba y no la que ya había

`test_payload_pruning.py` tenía un guardián para esto desde el principio, y su
fixture decía ser *"one activity in the exact shape the extractor produces"*.
No lo era: llevaba segundos y metros. La prueba se comprobaba a sí misma.

Por eso aquí no hay fixture inventado. Se llama al PRODUCTOR real
(`procesar_lap`) con un tramo crudo de Garmin, y se comprueba que lo que sale
es lo que los dos consumidores leen.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.training_habits import leer_estructura  # noqa: E402
from services.garmin.data_extractor import procesar_lap  # noqa: E402
from services.garmin.payload_pruning import _lap_is_artifact, prune  # noqa: E402

# Tramos CRUDOS de Garmin, copiados de la sesión real del 2026-09-09
# ("Guatemala - Ritmo 5K", actividad de 20 tramos). Garmin da metros, segundos
# y metros por segundo.
CALENTAMIENTO = {"startTimeGMT": "2026-09-09T23:14:34.0", "distance": 1000.0,
                 "duration": 500.0, "averageSpeed": 2.0, "averageHR": 127,
                 "maxHR": 137, "intensityType": "WARMUP"}
SERIE = {"startTimeGMT": "2026-09-09T23:22:34.0", "distance": 354.0,
         "duration": 120.0, "averageSpeed": 2.949, "averageHR": 146,
         "maxHR": 158, "intensityType": "ACTIVE"}
RECUPERACION = {"startTimeGMT": "2026-09-09T23:24:34.0", "distance": 195.0,
                "duration": 120.0, "averageSpeed": 1.625, "averageHR": 131,
                "maxHR": 158, "intensityType": "RECOVERY"}
BOTON = {"startTimeGMT": "2026-09-09T23:14:33.0", "distance": 0.0,
         "duration": 0.5, "averageSpeed": 0.0, "averageHR": 99, "maxHR": 104,
         "intensityType": None}


class ElProductorPoneLaUnidadEnElNombre(unittest.TestCase):
    def test_metros_segundos_y_metros_por_segundo(self):
        t = procesar_lap(SERIE)
        self.assertEqual(t["distance_m"], 354.0)
        self.assertEqual(t["duration_s"], 120.0)
        self.assertEqual(t["averageSpeed_ms"], 2.949)

    def test_ninguna_clave_ambigua_sobrevive(self):
        """`duration` a secas es la clave que costó la temporada de series."""
        t = procesar_lap(SERIE)
        for ambigua in ("duration", "distance", "averageSpeed", "maxSpeed",
                        "averageHR", "maxHR", "calories"):
            with self.subTest(clave=ambigua):
                self.assertNotIn(ambigua, t)

    def test_la_etiqueta_de_intensidad_llega_entera(self):
        """Sin ella, ocho repeticiones y un rodaje se leen igual."""
        self.assertEqual(procesar_lap(SERIE)["intensity"], "ACTIVE")
        self.assertEqual(procesar_lap(CALENTAMIENTO)["intensity"], "WARMUP")


class ElFiltroDeRuidoLeeLasMismasClaves(unittest.TestCase):
    def test_una_serie_de_dos_minutos_NO_es_ruido(self):
        """El caso exacto que se borraba: 120 s y 354 m.

        Con el umbral leyendo minutos, 2.0 < 5.0 y 0.354 < 20.0: fuera.
        """
        self.assertFalse(_lap_is_artifact(procesar_lap(SERIE)))

    def test_la_recuperacion_tampoco(self):
        self.assertFalse(_lap_is_artifact(procesar_lap(RECUPERACION)))

    def test_una_pulsacion_del_boton_si_lo_es(self):
        """0,5 s y 0 m. Esto es lo único que el filtro debía quitar."""
        self.assertTrue(_lap_is_artifact(procesar_lap(BOTON)))

    def test_la_sesion_real_conserva_sus_repeticiones(self):
        laps = [procesar_lap(BOTON), procesar_lap(CALENTAMIENTO)]
        for _ in range(8):
            laps += [procesar_lap(SERIE), procesar_lap(RECUPERACION)]
        quedan = prune({"laps": laps})["laps"]
        self.assertEqual(len(quedan), 17)
        self.assertEqual(sum(1 for t in quedan if t["intensity"] == "ACTIVE"), 8)

    def test_un_tramo_sin_duracion_no_se_descarta(self):
        """No saber cuánto duró no es saber que no duró nada."""
        self.assertFalse(_lap_is_artifact({"distance_m": 0.0}))


class ElLectorDeEstructuraLeeLasMismasClaves(unittest.TestCase):
    def _sesion(self, laps):
        return SimpleNamespace(laps=laps, activity_name="Guatemala - Ritmo 5K")

    def test_reconoce_la_sesion_real_del_9_de_septiembre(self):
        laps = [procesar_lap(CALENTAMIENTO)]
        for _ in range(8):
            laps += [procesar_lap(SERIE), procesar_lap(RECUPERACION)]
        s = leer_estructura(self._sesion(laps), date(2026, 9, 9), "run")
        self.assertIsNotNone(s)
        self.assertEqual(s.repeticiones, 8)
        self.assertEqual(s.trabajo_s, 120)
        self.assertEqual(s.recuperacion_s, 120)
        self.assertEqual(s.fc_pico, 158)

    def test_el_ritmo_sale_de_metros_por_segundo(self):
        """2,949 m/s son 5:39/km. Leído como km/h serían 5:39 por 2,9 km."""
        laps = [procesar_lap(CALENTAMIENTO)]
        for _ in range(3):
            laps += [procesar_lap(SERIE), procesar_lap(RECUPERACION)]
        s = leer_estructura(self._sesion(laps), date(2026, 9, 9), "run")
        self.assertEqual(s.ritmo, "5:39/km")

    def test_un_rodaje_con_vuelta_automatica_no_es_una_serie(self):
        """Ocho tramos ACTIVE seguidos y ninguno de recuperación."""
        laps = [procesar_lap(SERIE) for _ in range(8)]
        self.assertIsNone(leer_estructura(self._sesion(laps), date(2026, 9, 9), "run"))

    def test_sin_etiquetas_de_intensidad_se_abstiene(self):
        """Adivinar series por el ritmo sube la carga sobre una suposición."""
        laps = [procesar_lap({**SERIE, "intensityType": None}) for _ in range(8)]
        self.assertIsNone(leer_estructura(self._sesion(laps), date(2026, 9, 9), "run"))


if __name__ == "__main__":
    unittest.main()
