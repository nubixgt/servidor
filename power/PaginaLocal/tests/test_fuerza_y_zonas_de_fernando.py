"""Las rutinas de gimnasio y las zonas del atleta Fernando (2026-09-13/14).

Sus PDF (versión de `main`) y lo que dijo:

- "El tema de la rutina del gym se puede mejorar porque las que pone realmente
  no me sirven": 29 años, 90 kg, entrena Upper/Lower y lo escribió en el
  cuaderno. Recibió "flexiones o en pared" y press de hombro con 5-10 kg, y un
  lunes "Lower terapéutico" por una banda iliotibial 3/10. Los ejercicios los
  elegía el codificador, que no ve ni el cuaderno ni el perfil.
- "Subí varios puntos en el cuaderno... pero el plan me lo hizo similar a la
  semana anterior": el cuaderno se presentaba como "afirmaciones a contrastar"
  y la semana anterior como "mantén los anclajes... la fuerza".
- "Mi zona 2 es realmente a 6-6:20": zonas idénticas en bici, carrera y
  natación (101-120 / 121-138 / 139-158 / 159-178 / 179+, las de fábrica) y una
  banda "Fácil 5:03-5:40/km" sacada de su predicción de maratón.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import inspect
import sys
import unittest
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import notebook  # noqa: E402
from services.ai.prescription import build_prescription, render_prescription_card  # noqa: E402
from services.garmin.models import GarminHrZones  # noqa: E402

nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")
# Con `discover -s tests` el módulo es `test_prescription`; con
# `python -m unittest tests.x`, `tests.test_prescription`.
try:
    prueba_de_prescripcion = importlib.import_module("test_prescription")
except ModuleNotFoundError:
    prueba_de_prescripcion = importlib.import_module("tests.test_prescription")

ZONAS_DE_FABRICA = {1: (101, 120), 2: (121, 138), 3: (139, 158), 4: (159, 178), 5: (179, None)}


class LaFuerzaLaDecideQuienVeAlAtleta(unittest.TestCase):
    def test_la_decision_escribe_ejercicios_series_y_carga_por_esfuerzo(self):
        prompt = nodo.TRAINING_DECISION_USER_PROMPT
        self.assertIn("load by effort (RIR or RPE)", prompt)
        self.assertIn("kilos only when the athlete has given them", prompt)

    def test_una_molestia_de_pierna_no_convierte_en_rehab_el_tren_superior(self):
        self.assertIn("an upper-body day is never rehab because of a leg", nodo.TRAINING_DECISION_USER_PROMPT)

    def test_como_entrena_en_el_gimnasio_es_una_peticion(self):
        self.assertIn("How the athlete already trains in the gym", nodo.TRAINING_DECISION_USER_PROMPT)
        self.assertIn("is a request, like the availability table", nodo.TRAINING_DECISION_USER_PROMPT)

    def test_el_codificador_copia_la_sesion_y_no_inventa_kilos(self):
        prompt = nodo.WORKOUT_FORMATTER_USER_PROMPT
        self.assertIn("son los que trae la decisión", prompt)
        self.assertIn("nunca unos kilos que nadie te dio", prompt)
        self.assertNotIn("Cuántos y cuáles los decides tú", prompt)
        self.assertNotIn("12 kg por mancuerna", prompt)


class ElCuadernoPideYLaSemanaAnteriorNoManda(unittest.TestCase):
    def test_como_quiere_entrenar_es_una_peticion(self):
        fuente = inspect.getsource(notebook.render_notebook_block)
        self.assertIn("es una **petición**", fuente)
        self.assertIn("afirmaciones del atleta", fuente)

    def test_lo_que_pide_el_atleta_gana_al_anclaje(self):
        fuente = inspect.getsource(nodo._format_previous_plan)
        self.assertIn("lo que pide el atleta", fuente)
        self.assertIn("gana al anclaje", fuente)

    def test_la_semana_anterior_lleva_los_ejercicios_de_fuerza(self):
        """Sin ellos no hay forma de hacerlos progresar: el paso único decía
        "Fuerza de mantenimiento, 30 min"."""
        ejercicio = SimpleNamespace(name="Sentadilla trasera", sets=4, reps="6", load="RIR 2")
        dia = SimpleNamespace(
            date="2026-09-14", is_rest=False, sport="strength", focus="Lower", total_duration_minutes=60.0,
            steps=[SimpleNamespace(description="Fuerza, 60 min", substeps=[], step_type="main")],
            strength_block=SimpleNamespace(exercises=[ejercicio]),
        )
        semana = SimpleNamespace(week_number=1, days=[dia])
        texto = nodo._format_previous_plan(SimpleNamespace(weeks=[semana], start_date="2026-09-14"), "2026-09-20")
        self.assertIn("Sentadilla trasera 4x6 (RIR 2)", texto)


class SusZonasYSuRitmoSuave(unittest.TestCase):
    def _datos(self, zonas=None, lt_speed=2.88):
        return prueba_de_prescripcion._garmin_data(lt_speed=lt_speed, zones=zonas or prueba_de_prescripcion.REAL_HR_ZONES)

    def test_zonas_identicas_en_todos_los_deportes_se_avisan(self):
        zonas = GarminHrZones(by_sport={"run": dict(ZONAS_DE_FABRICA), "bike": dict(ZONAS_DE_FABRICA),
                                        "swim": dict(ZONAS_DE_FABRICA)})
        p = build_prescription(self._datos(zonas), {})
        self.assertTrue(p.zonas_identicas)
        self.assertTrue(any("zonas de pulso son iguales" in w for w in p.warnings))
        self.assertIn("idénticas en todos los deportes", render_prescription_card(p))

    def test_zonas_de_verdad_por_deporte_no_se_avisan(self):
        p = build_prescription(self._datos(), {})
        self.assertFalse(p.zonas_identicas)

    def test_el_ritmo_suave_declarado_gana_a_la_prediccion(self):
        p = build_prescription(self._datos(), {"easy_run_pace_fast_sec_per_km": 360,
                                               "easy_run_pace_slow_sec_per_km": 380})
        facil = p.pace_bands["facil"]
        self.assertEqual((facil.fast_sec_per_km, facil.slow_sec_per_km), (360.0, 380.0))
        self.assertIn("dice que rueda suave", render_prescription_card(p))

    def test_sin_declararlo_la_banda_sigue_saliendo_de_los_datos(self):
        p = build_prescription(self._datos(), {})
        self.assertFalse(p.facil_declarado)

    def test_el_umbral_que_teclea_el_atleta_gana_a_garmin(self):
        umbral = prueba_de_prescripcion.CORRECT_THRESHOLD_SEC_PER_KM + 5
        p = build_prescription(self._datos(), {"lactate_threshold_pace_sec_per_km": umbral})
        self.assertEqual(p.threshold_pace_source, "manual")

    def test_el_que_relleno_la_importacion_de_garmin_no_le_gana_al_de_hoy(self):
        umbral = prueba_de_prescripcion.CORRECT_THRESHOLD_SEC_PER_KM + 5
        p = build_prescription(self._datos(), {"lactate_threshold_pace_sec_per_km": umbral,
                                               "garmin_autofilled_fields": ["lactate_threshold_pace_sec_per_km"]})
        self.assertEqual(p.threshold_pace_source, "garmin_lt")


if __name__ == "__main__":
    unittest.main()
