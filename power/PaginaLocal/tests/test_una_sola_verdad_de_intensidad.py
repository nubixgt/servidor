"""Una sola respuesta en toda la app a "¿esta sesión fue de calidad?".

Hasta la v1.2.2 se decidía en cuatro sitios con dos criterios: el reparto usaba
el pulso anclado a los umbrales y el ritmo; las tasas de carga, los hábitos y
las sesiones exigentes usaban los minutos vigorosos de Garmin, que salen del
pulso. En el atleta 2 -- cuyo pulso llega tarde en las series: 43 tramos más
rápidos que su umbral, el 93 % con el pulso medio bajo Z4 -- sus VO2max eran
"calidad" en un sitio y "suave" en los otros tres.

Y la advertencia llega a donde se lee el Load Focus: en su macrociclo real del
2026-09-13 el experto de métricas escribió "AEROBIC_HIGH_SHORTAGE" sin ninguna
medición propia delante, y la semana lo convirtió en intención futura.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from datetime import date, timedelta
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.intensity_distribution import es_sesion_dura  # noqa: E402
from services.ai.training_habits import build_training_habits  # noqa: E402
from services.ai.weekly_load import tasas_de_carga  # noqa: E402
from services.garmin.models import GarminHrZones  # noqa: E402

HOY = date(2026, 9, 13)
ZONAS = GarminHrZones(by_sport={"run": {1: (122, 139), 2: (140, 152), 3: (153, 166), 4: (167, 179), 5: (180, None)}})
REF = {"lthr": 176, "umbral_ritmo_s_km": 353.0, "lt1_ritmo_s_km": 421.0}


def series(fecha, vigorosos=5):
    """Un VO2max real del atleta 2: repeticiones a 5:07/km con el pulso medio en 158."""
    tramos = [{"duration_s": 120, "averageHR_bpm": 158, "maxHR_bpm": 168, "averageSpeed_ms": 1000 / 307,
               "distance_m": 391, "elevationGain_m": 0}] * 5 + [
              {"duration_s": 1800, "averageHR_bpm": 135, "maxHR_bpm": 150, "averageSpeed_ms": 1000 / 450,
               "distance_m": 4000, "elevationGain_m": 0}]
    return SimpleNamespace(
        start_time=f"{fecha.isoformat()}T07:00:00", activity_type="running", laps=tramos,
        summary=SimpleNamespace(duration=2400, activity_training_load=60, vigorous_intensity_minutes=vigorosos),
    )


class LaClasificacionManda(unittest.TestCase):
    def test_un_vo2max_con_pocos_minutos_vigorosos_es_calidad(self):
        """Garmin le contó 5 de 40 minutos vigorosos: por su criterio, un rodaje."""
        self.assertTrue(es_sesion_dura(series(HOY), ZONAS, REF))

    def test_sin_zonas_no_se_pronuncia(self):
        self.assertIsNone(es_sesion_dura(series(HOY), None, REF))


class LasTasasDeCarga(unittest.TestCase):
    def test_con_la_clasificacion_el_vo2max_entra_en_la_tasa_de_calidad(self):
        historial = [series(HOY - timedelta(days=d)) for d in (2, 9)]
        tasas = tasas_de_carga(historial, HOY, es_dura=lambda a: es_sesion_dura(a, ZONAS, REF))
        self.assertIn("run:calidad", tasas)
        self.assertNotIn("run:suave", tasas)

    def test_sin_clasificacion_manda_garmin_como_siempre(self):
        historial = [series(HOY - timedelta(days=d)) for d in (2, 9)]
        self.assertIn("run:suave", tasas_de_carga(historial, HOY))
        self.assertIn("run:suave", tasas_de_carga(historial, HOY, es_dura=lambda a: None))


class LosHabitos(unittest.TestCase):
    def test_un_atleta_cuyo_pulso_llega_tarde_tiene_dia_de_calidad(self):
        """Series cada miércoles durante 12 semanas, con 5 minutos vigorosos cada vez."""
        miercoles = [HOY - timedelta(days=(HOY.weekday() - 2) % 7 + 7 * i) for i in range(12)]
        garmin = SimpleNamespace(
            recent_activities=[series(d) for d in miercoles], garmin_hr_zones=ZONAS, race_predictions=[],
            user_profile=SimpleNamespace(lactate_threshold_speed=1000 / 353, lactate_threshold_heart_rate=176),
        )
        habitos = build_training_habits(garmin, {}, hoy=HOY)
        self.assertIn("miércoles", habitos.dias_de_calidad)

    def test_sin_zonas_siguen_mandando_los_minutos_de_garmin(self):
        miercoles = [HOY - timedelta(days=(HOY.weekday() - 2) % 7 + 7 * i) for i in range(12)]
        garmin = SimpleNamespace(recent_activities=[series(d) for d in miercoles], race_predictions=[])
        self.assertNotIn("miércoles", build_training_habits(garmin, {}, hoy=HOY).dias_de_calidad)


class LaAdvertenciaLlegaADondeSeLeeElLoadFocus(unittest.TestCase):
    ESTADO = {"availability_constraints": {"volume_baseline": {"intensity_distribution": {
        "dias": 28, "hasta": "2026-09-13", "sesiones": {"suave": 19, "umbral": 3, "alta": 3, "total": 25},
        "forma": "piramidal", "por_deporte": {}, "tiempo_pct": {"zona1": 87, "zona2": 13, "zona3": 0},
        "sesiones_de_umbral": [], "altas_por_ritmo": 2, "con_ritmo": True, "sin_pulso": 0, "no_resistencia": 9,
    }}}}

    def test_el_experto_de_metricas_lo_recibe_con_la_advertencia(self):
        # Por importlib: el paquete `nodes` exporta una FUNCIÓN con el nombre del
        # módulo, y `from ... import metrics_expert_node` devuelve la función.
        metricas = importlib.import_module("services.ai.langgraph.nodes.metrics_expert_node")
        bloque = metricas._bloque_de_reparto(self.ESTADO)
        self.assertIn("Cómo reparte su intensidad", bloque)
        self.assertIn("Load Focus", bloque)
        self.assertIn("{intensity_distribution}", metricas.METRICS_USER_PROMPT)
        self.assertEqual(metricas._bloque_de_reparto({}), "")

    def test_el_experto_de_actividad_lo_recibe(self):
        actividad = importlib.import_module("services.ai.langgraph.nodes.activity_expert_node")
        self.assertIn("sólo por el ritmo", actividad._bloque_de_reparto(self.ESTADO))
        self.assertIn("{intensity_distribution}", actividad.ACTIVITY_EXPERT_USER_PROMPT)
        self.assertEqual(actividad._bloque_de_reparto({}), "")

    def test_el_macrociclo_tiene_su_hueco_para_la_respuesta_a_las_fases(self):
        temporada = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")
        self.assertIn("{phase_response}", temporada.SEASON_PLANNER_USER_PROMPT)
        self.assertIn("Cómo respondió a cada fase", temporada.SEASON_PLANNER_USER_PROMPT)


if __name__ == "__main__":
    unittest.main()
