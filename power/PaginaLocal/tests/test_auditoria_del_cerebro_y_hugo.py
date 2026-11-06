"""Auditoría del cerebro del 2026-09-14 y los informes del atleta Hugo (versión de `main`, 2026-09-07/14).

Lo que decían sus PDF, con sus números:

- "RITMO UMBRAL 4:27/km — de tu Garmin", encima del aviso "Se descartó el ritmo
  umbral de Garmin (6:23/km, más lento que su maratón, 5:44) … se usó el de 10K
  predicho (4:27)". Para un 10K de 44:31 eso es más rápido que su umbral: la
  banda de tempo empezaba en 4:22.
- Zonas de pulso de carrera y natación 98/118/137/157/176 (60/70/80/90 % de una
  FC máxima de ~196, las de fábrica) y de bici 96/115/134/153/172 (de ~191). No
  eran idénticas entre sí, así que el aviso de "zonas iguales" no saltó, y el
  informe leyó un 21K a 162 ppm de media como "tempo/umbral" y le recetó rodar
  "por debajo de 140-145 bpm".
- "UTG 21K" (+1.500 m, meta 3:05) comparado con su predicción de media en
  asfalto (1:45:06): "más lento que su predicción — hay margen para apuntar más
  alto". "3760 Race" (+700 m, meta 1:55) contra un 10K de 44:31, "11:30/km".

Y lo que la auditoría línea por línea encontró en los nodos.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import inspect
import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.goal_realism import assess_goal, render_goal_realism_block  # noqa: E402
from services.ai.intensity_distribution import clasificar_sesion  # noqa: E402
from services.ai.prescription import (  # noqa: E402
    build_prescription,
    fc_maxima_de_zonas_de_fabrica,
    format_pace,
    prescription_to_state,
    procedencia_de_umbrales,
    render_prescription_card,
)
from services.garmin.models import GarminData, GarminHrZones, RacePrediction, UserProfile  # noqa: E402

HUGO_CARRERA = {1: (98, 117), 2: (118, 136), 3: (137, 156), 4: (157, 175), 5: (176, None)}
HUGO_BICI = {1: (96, 114), 2: (115, 133), 3: (134, 152), 4: (153, 171), 5: (172, None)}
HUGO_ZONAS = GarminHrZones(by_sport={"run": dict(HUGO_CARRERA), "bike": dict(HUGO_BICI), "swim": dict(HUGO_CARRERA)})
HUGO_PREDICCIONES = [
    RacePrediction("10K", 10000.0, 2671),            # 44:31
    RacePrediction("half_marathon", 21097.5, 6306),  # 1:45:06
    RacePrediction("marathon", 42195.0, 14515),      # 5:44/km
]
# Las del atleta 2: configuradas por umbral, no de fábrica. Y su 5K predicho (27:44, 5:33/km).
ATLETA_2_CARRERA = {1: (123, 138), 2: (139, 152), 3: (153, 166), 4: (167, 180), 5: (181, None)}
ATLETA_2_5K = [RacePrediction("5K", 5000.0, 1664), RacePrediction("10K", 10000.0, 3560)]


def hugo(lt_speed=None, lthr=None):
    return GarminData(
        user_profile=UserProfile(lactate_threshold_speed=lt_speed, lactate_threshold_heart_rate=lthr),
        garmin_hr_zones=HUGO_ZONAS, race_predictions=list(HUGO_PREDICCIONES),
    )


def vuelta(minutos, ritmo_s_km, fc):
    segundos = minutos * 60
    metros = segundos / ritmo_s_km * 1000
    return {"duration_s": segundos, "distance_m": metros, "averageHR_bpm": fc, "averageSpeed_ms": metros / segundos}


class ElUmbralEstimadoEsElDeUnaHora(unittest.TestCase):
    def test_entre_su_10k_y_su_media_la_hora_cae_a_4_38(self):
        p = build_prescription(hugo(lt_speed=1000 / 383), {})  # 6:23/km, más lento que su maratón
        self.assertEqual(p.threshold_pace_source, "race_prediction")
        self.assertEqual(format_pace(p.threshold_pace_sec_per_km), "4:38")
        # La banda de tempo ya no empieza más rápido que su propio ritmo de 10K (4:27).
        self.assertGreater(p.pace_bands["umbral_tempo"].fast_sec_per_km, 2671 / 10)
        self.assertTrue(any("sostendrías una hora" in w for w in p.warnings))
        self.assertFalse(any("10K predicho" in w for w in p.warnings))

    def test_la_tarjeta_del_informe_dice_de_donde_sale(self):
        formatter = importlib.import_module("services.ai.langgraph.nodes.formatter_node")
        estado = {"prescription": prescription_to_state(build_prescription(hugo(lt_speed=1000 / 383), {}))}
        tarjeta = next(s for s in formatter._headline_stats(estado) if s["label"] == "Ritmo umbral")
        self.assertEqual(tarjeta["note"], "estimado de tus predicciones de carrera")


class LasZonasDeFabricaSeReconocen(unittest.TestCase):
    def test_las_de_hugo_son_de_fabrica_en_los_tres_deportes(self):
        self.assertEqual(fc_maxima_de_zonas_de_fabrica(HUGO_CARRERA), 196)
        self.assertEqual(fc_maxima_de_zonas_de_fabrica(HUGO_BICI), 191)
        p = build_prescription(hugo(), {})
        self.assertFalse(p.zonas_identicas)
        self.assertEqual(p.zonas_de_fabrica, {"run": 196, "bike": 191, "swim": 196})
        aviso = next(w for w in p.warnings if "fábrica" in w)
        self.assertIn("Tus zonas de pulso de carrera, bici, natación son las de fábrica de Garmin", aviso)
        # Cada deporte, al sitio que lo arregla (auditoría del 2026-09-24): el test de umbral da la FC
        # umbral de carrera y bici; en natación manda el ritmo, y lo afina el CSS, no un pulso.
        self.assertIn("En carrera y bici: el test de umbral", aviso)
        self.assertIn("En natación no hace falta arreglarlas", aviso)

    def test_si_solo_la_natacion_es_de_fabrica_no_le_manda_a_configurar_pulsos(self):
        """Atleta 2 (2026-09-23): sólo sus zonas de natación son de fábrica. El aviso le decía "haz el
        test de umbral y configura en Garmin tus zonas por tu FC umbral", y el test de natación de la
        app es el CSS, que da un ritmo por 100 m: le mandaba a un sitio que no lo arregla."""
        p = build_prescription(hugo(), {})
        p.zonas_de_fabrica = {"swim": 187}
        p.zonas_identicas = False
        from services.ai.prescription import _aviso_de_zonas
        aviso = _aviso_de_zonas(p)
        self.assertIn("En natación no hace falta arreglarlas", aviso)
        self.assertIn("Test de umbral → Natación", aviso)
        self.assertNotIn("FC umbral", aviso)

    def test_unas_configuradas_por_umbral_no(self):
        self.assertIsNone(fc_maxima_de_zonas_de_fabrica(ATLETA_2_CARRERA))

    def test_la_ficha_prohibe_leer_tempo_por_caer_en_z4(self):
        tarjeta = render_prescription_card(build_prescription(hugo(), {}))
        self.assertIn("sus zonas de carrera, bici, natación son las de fábrica de Garmin", tarjeta)
        self.assertIn("No escribas reglas en ppm", tarjeta)

    def test_los_expertos_lo_reciben(self):
        estado = {"prescription": prescription_to_state(build_prescription(hugo(), {}))}
        actividad = importlib.import_module("services.ai.langgraph.nodes.activity_expert_node")
        metricas = importlib.import_module("services.ai.langgraph.nodes.metrics_expert_node")
        self.assertIn("fábrica de Garmin", actividad._bloque_de_zonas(estado))
        self.assertIn("ESTIMADO", actividad._bloque_de_zonas(estado))
        self.assertIn("fábrica de Garmin", metricas._bloque_de_procedencia(estado))

    def test_sin_nada_que_decir_cero_tokens(self):
        self.assertEqual(procedencia_de_umbrales({}), "")
        self.assertEqual(procedencia_de_umbrales({"threshold_pace_source": "garmin_lt"}), "")


class ElTestDeUmbralSeReconoce(unittest.TestCase):
    TESTS = {"run": {"fecha": "2026-09-12", "lthr": 170, "ritmo_s_km": 281.3},
             "bike": {"fecha": "2026-09-13", "lthr": 158, "ftp_w": 237}}

    def test_el_ritmo_y_la_fc_del_test_se_etiquetan_como_test(self):
        perfil = {"lactate_threshold_hr": 170, "lactate_threshold_pace_sec_per_km": 281.3,
                  "threshold_tests": self.TESTS}
        p = build_prescription(hugo(lthr=176), perfil)
        self.assertEqual(p.threshold_pace_source, "test")
        self.assertEqual(p.lthr_source, "test")
        self.assertEqual(p.lthr_por_deporte, {"run": 170, "bike": 158})
        tarjeta = render_prescription_card(p)
        self.assertIn("de su test de umbral del 2026-09-12", tarjeta)
        self.assertIn("**FC umbral en bici**: 158 ppm", tarjeta)

    def test_si_lo_cambia_a_mano_deja_de_ser_test(self):
        perfil = {"lactate_threshold_pace_sec_per_km": 290.0, "threshold_tests": self.TESTS}
        self.assertEqual(build_prescription(hugo(), perfil).threshold_pace_source, "manual")

    def test_un_test_no_se_descarta_por_una_prediccion(self):
        """Un test mide; una predicción estima. Si no cuadran, se duda de la predicción."""
        datos = GarminData(garmin_hr_zones=HUGO_ZONAS, race_predictions=list(ATLETA_2_5K))
        perfil = {"lactate_threshold_pace_sec_per_km": 320.0,  # más rápido que su 5K predicho (5:33)
                  "threshold_tests": {"run": {"fecha": "2026-09-12", "ritmo_s_km": 320.0}}}
        p = build_prescription(datos, perfil)
        self.assertEqual(p.threshold_pace_sec_per_km, 320.0)
        self.assertTrue(any("no cuadra con tus predicciones" in w for w in p.warnings))

    def test_la_fc_umbral_rellenada_por_una_importacion_no_gana_a_la_de_hoy(self):
        perfil = {"lactate_threshold_hr": 165, "garmin_autofilled_fields": ["lactate_threshold_hr"]}
        p = build_prescription(hugo(lthr=176), perfil)
        self.assertEqual((p.lactate_threshold_hr, p.lthr_source), (176, "garmin"))

    def test_la_bici_se_clasifica_con_su_umbral_de_bici(self):
        rodaje = SimpleNamespace(activity_type="cycling", start_time="2026-09-10 07:00:00",
                                 summary=SimpleNamespace(duration=1800),
                                 laps=[{"duration_s": 1800, "averageHR_bpm": 160, "maxHR_bpm": 165}])
        zonas = {"bike": HUGO_BICI}
        self.assertEqual(clasificar_sesion(rodaje, zonas, {})["clase"], "umbral")  # 160 < suelo de Z5 (172)
        self.assertEqual(clasificar_sesion(rodaje, zonas, {"lthr_bici": 158})["clase"], "alta")


class LaMontanaNoSeComparaConElAsfalto(unittest.TestCase):
    PREDICCIONES = [{"distance_key": "10K", "distance_m": 10000, "time_seconds": 2671},
                    {"distance_key": "half_marathon", "distance_m": 21097.5, "time_seconds": 6306}]
    HOY = date(2026, 9, 7)

    def test_utg_21k_con_1500_m(self):
        a = assess_goal({"name": "UTG 21K", "date": "2026-11-21", "priority": "A", "distance": "half_marathon",
                         "elevation_gain_m": 1500, "target_time": "03:05:00"}, self.PREDICCIONES, self.HOY)
        self.assertEqual((a["verdict"], a["missing_reason"]), ("sin_referencia", "desnivel"))
        self.assertIsNone(a["pace_context"])
        self.assertEqual(a["flat_prediction_clock"], "1:45:06")
        self.assertEqual(a["elevation_m_per_km"], 71)
        bloque = render_goal_realism_block([a])
        self.assertIn("1500 m, ~71 m por km", bloque)
        self.assertIn("No la compares con la meta", bloque)
        self.assertNotIn("hay margen para apuntar más alto", bloque)

    def test_3760_race_con_700_m(self):
        a = assess_goal({"name": "3760 Race", "date": "2026-10-11", "priority": "B", "distance": "10K",
                         "elevation_gain_m": 700, "target_time": "01:55:00"}, self.PREDICCIONES, self.HOY)
        self.assertEqual(a["missing_reason"], "desnivel")

    def test_un_10k_de_asfalto_con_poco_desnivel_se_sigue_comparando(self):
        a = assess_goal({"name": "10K Ciudad", "date": "2026-10-11", "priority": "B", "distance": "10K",
                         "elevation_gain_m": 60, "target_time": "00:44:00"}, self.PREDICCIONES, self.HOY)
        self.assertNotEqual(a["verdict"], "sin_referencia")


class LoQueEncontroLaAuditoriaEnLosNodos(unittest.TestCase):
    def test_el_ritmo_de_la_carrera_lee_3_30_de_maraton_como_horas(self):
        nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")
        self.assertEqual(nodo._ritmo_de_la_carrera({"distance": "marathon", "target_time": "3:30"}), "4:59/km")

    def test_un_resumidor_caido_no_escribe_none_en_el_prompt(self):
        for modulo, clave in (("metrics_expert_node", "metrics_summary"),
                              ("physiology_expert_node", "physiology_summary"),
                              ("activity_expert_node", "activity_summary")):
            fuente = inspect.getsource(importlib.import_module(f"services.ai.langgraph.nodes.{modulo}"))
            self.assertIn(f'state.get("{clave}") or ', fuente, modulo)

    def test_con_el_hitl_apagado_el_macrociclo_no_se_pierde(self):
        orquestador = importlib.import_module("services.ai.langgraph.nodes.orchestrator_node")
        preguntas = {"output": [{"message": "¿Cómo te sentiste?"}]}
        estado = {"hitl_enabled": False, "metrics_outputs": preguntas, "physiology_outputs": None,
                  "activity_outputs": None}
        comando = orquestador.master_orchestrator_node(estado)
        self.assertEqual(sorted(comando.goto), ["data_integration", "synthesis"])

    def test_el_dia_de_carrera_de_respaldo_usa_el_deporte_de_la_carrera(self):
        from services.ai.langgraph.schemas.plan_outputs import WeekBlock, WeeklyPlanPayload, WorkoutDay
        from services.ai.langgraph.validation.plan_validator import _apply_race_day_fallback

        travesia = {"name": "Travesía del lago", "date": "2026-09-20", "priority": "A", "distance": "ow_1500"}
        payload = WeeklyPlanPayload(athlete_name="T", start_date="2026-09-14", zones_table="z", weeks=[
            WeekBlock(week_number=1, days=[WorkoutDay(date="2026-09-19", day_name_es="Sábado", is_rest=True,
                                                     sport="rest", focus="Descanso", purpose="—", adaptation="—",
                                                     steps=[], total_duration_minutes=0)])])
        violaciones = [{"date": "2026-09-20", "session_slot": "unica", "type": "race_day_conflict", "detail": "x"}]
        payload, _ = _apply_race_day_fallback(payload, violaciones, [travesia],
                                              [{"date": "2026-09-20", "day_name_es": "Domingo"}])
        dia = next(d for w in payload.weeks for d in w.days if d.date == "2026-09-20")
        self.assertEqual(dia.sport, "swim")


# La búsqueda del test entre actividades recientes se retiró el 2026-09-14 (el
# usuario: "no estoy de acuerdo que jale cualquier actividad del reloj creyendo que
# es umbral"). Subir y leer el test de su entreno: test_test_de_umbral_en_el_reloj.


if __name__ == "__main__":
    unittest.main()
