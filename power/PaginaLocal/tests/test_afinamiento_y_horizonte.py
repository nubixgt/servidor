"""Afinamiento, horizonte del macrociclo y reglas de recuperación (2026-09-13).

Revisión con ojo de entrenador del macrociclo y la semana del atleta 2:

1. **La semana del 5K Monterrico (B) no afinaba.** Natación miércoles y viernes
   y fuerza lunes, martes y jueves estaban marcados como FIJO -- 3,5 de las
   7,4 h -- y el validador exige cada fijo. Carga estimada 362 contra una
   crónica de 291, con un racional que decía "bajamos el volumen". En la
   ventana de afinamiento de cada carrera los fijos pasan a posibles.
2. **El macrociclo terminaba 7 semanas antes de la carrera A** (10K del
   2027-04-20, a 31 semanas): el prompt pedía 12-24 semanas y a la vez un taper
   antes de las A. Ahora llega hasta la A, y sólo sus 16 primeras semanas son
   firmes; lo de después es un mapa que la app pide rehacer.
3. **Prohibir toda regla fija de recuperación era demasiado.** El
   entrenamiento guiado por HRV funciona con reglas, ancladas a la base del
   propio atleta y con persistencia. Lo que estaba mal de la regla real ("sueño
   < 75" para quien promedia 77) era su ancla, no que fuera una regla.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import inspect
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    _format_load_target,
    _format_mesocycle_position,
    _format_volume_target,
    compute_mesocycle_position,
    compute_weekly_load_target,
    compute_weekly_volume_target,
    disponibilidad_para_validar,
)
from services.ai.recovery_state import render_recovery_block  # noqa: E402

SEMANA_DEL_5K = [{"date": f"2026-09-{d}"} for d in range(13, 21)]
TRES_SEMANAS_ANTES = [{"date": f"2026-08-{d}"} for d in range(24, 31)]
MONTERRICO = {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B"}
BASE = {"garmin_chronic_load": 291, "garmin_acute_load": 309, "chronic_weekly_hours": 7.1, "load_rates": {}}
FASE = {"week_number": 1, "is_deload": False, "weekly_hours_target": 6.5}
FIJOS = {"Lunes": ["strength"], "Miércoles": ["swim"], "Viernes": ["swim"]}


class EnAfinamientoLosFijosSonPosibles(unittest.TestCase):
    def test_el_bloque_de_carga_lo_dice(self):
        bloque = _format_load_target(
            compute_weekly_load_target(BASE, FASE, competitions=[MONTERRICO], week_dates=SEMANA_DEL_5K))
        self.assertIn("sus días FIJO pasan a POSIBLE", bloque)
        # La frase era "acortar suele ser mejor que quitar", y el modelo acortó
        # todo sin quitar nada accesorio. Lo que conserva la evidencia es la
        # intensidad y la frecuencia del deporte de la carrera.
        self.assertIn("La frecuencia que hay que conservar es la del deporte de la carrera", bloque)

    def test_el_bloque_de_horas_tambien(self):
        objetivo = compute_weekly_volume_target(
            {"chronic_weekly_hours": 7.1}, FASE, competitions=[MONTERRICO], week_dates=SEMANA_DEL_5K)
        self.assertIn("sus días FIJO pasan a POSIBLE", _format_volume_target(objetivo))

    def test_una_semana_normal_no_lo_dice(self):
        bloque = _format_load_target(compute_weekly_load_target(BASE, FASE, week_dates=SEMANA_DEL_5K))
        self.assertNotIn("FIJO", bloque)

    def test_el_validador_deja_de_exigirlos_sin_tocar_el_estado(self):
        disponibilidad = {"sport_days_fijos": dict(FIJOS), "days": {}}
        objetivo = compute_weekly_load_target(BASE, FASE, competitions=[MONTERRICO], week_dates=SEMANA_DEL_5K)
        self.assertEqual(disponibilidad_para_validar(disponibilidad, objetivo, None)["sport_days_fijos"], {})
        self.assertEqual(disponibilidad["sport_days_fijos"], FIJOS)

    def test_una_carrera_b_no_libera_la_rutina_tres_semanas_antes(self):
        """La ventana es la de su prioridad: una B se afina la semana previa, no
        tres. Tres semanas son la de una A."""
        objetivo = compute_weekly_load_target(BASE, FASE, competitions=[MONTERRICO], week_dates=TRES_SEMANAS_ANTES)
        self.assertEqual(disponibilidad_para_validar({"sport_days_fijos": FIJOS}, objetivo)["sport_days_fijos"], FIJOS)


class ElMacrocicloLlegaALaCarreraA(unittest.TestCase):
    FASES = {"season_start_date": "2026-09-13", "phases": [
        {"name": "Base", "start_week": 1, "end_week": 16, "intensity_emphasis": ""},
        {"name": "Específico 10K", "start_week": 17, "end_week": 31, "intensity_emphasis": ""},
    ]}

    def test_el_prompt_pide_llegar_a_la_a_y_dice_que_es_firme(self):
        prompt = importlib.import_module("services.ai.langgraph.nodes.season_planner_node").SEASON_PLANNER_USER_PROMPT
        self.assertIn("Priority A race within 40 weeks", prompt)
        # 2026-10-09: sin la palabra "firm" (confundía al atleta); la garantía es la misma.
        self.assertIn("Only the first 16 weeks are planned in detail", prompt)
        self.assertNotIn("__SEMANAS", prompt)
        self.assertNotIn("season plan (12-24 weeks) as a structured", prompt)

    def test_una_fase_mas_alla_de_las_firmes_llega_como_provisional(self):
        posicion = compute_mesocycle_position(self.FASES, "2027-01-10")
        self.assertEqual(posicion["week_number"], 18)
        self.assertTrue(posicion["provisional"])
        # El responsable, 2026-10-09: "firme" y "provisional" confundían al atleta.
        self.assertIn("Fase por confirmar según su progresión", _format_mesocycle_position(posicion))

    def test_las_primeras_semanas_no_son_provisionales(self):
        posicion = compute_mesocycle_position(self.FASES, "2026-09-20")
        self.assertFalse(posicion["provisional"])
        self.assertNotIn("provisional", _format_mesocycle_position(posicion))


class RehacerEnLaSemana17EsReplanificarNoRefinar(unittest.TestCase):
    """Rehacer un macrociclo que no ha terminado caía en "refina y conserva
    `season_start_date` EXACTAMENTE". En la semana 17 eso dejaba al atleta en la
    17 del plan nuevo: el aviso de revisar no se iba nunca, las fases
    posteriores seguían provisionales y la revisión era un visto bueno al mapa."""

    def setUp(self):
        self.nodo = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")

    def _fases(self, semanas_atras):
        import json
        from datetime import date, timedelta

        inicio = (date.today() - timedelta(weeks=semanas_atras)).isoformat()
        return json.dumps({"season_start_date": inicio, "phases": [{"start_week": 1, "end_week": 32}]})

    def test_pasada_la_16_toca_revisar(self):
        self.assertTrue(self.nodo._macrociclo_por_revisar(self._fases(17)))
        self.assertFalse(self.nodo._macrociclo_terminado(self._fases(17)))

    def test_dentro_de_las_firmes_se_refina(self):
        self.assertFalse(self.nodo._macrociclo_por_revisar(self._fases(5)))

    def test_sin_datos_no_se_revisa(self):
        for basura in ("", "{}", "no es json"):
            with self.subTest(entrada=basura):
                self.assertFalse(self.nodo._macrociclo_por_revisar(basura))

    def test_el_codigo_pone_la_fecha_de_un_plan_nuevo(self):
        from types import SimpleNamespace

        nuevo = SimpleNamespace(season_start_date="2026-09-13")
        self.nodo._fijar_arranque(nuevo, "2027-01-10", refinando=False)
        self.assertEqual(nuevo.season_start_date, "2027-01-10")

        refinado = SimpleNamespace(season_start_date="2026-09-13")
        self.nodo._fijar_arranque(refinado, "2027-01-10", refinando=True)
        self.assertEqual(refinado.season_start_date, "2026-09-13")

    def test_el_modelo_sabe_que_es_la_revision_planificada(self):
        fuente = inspect.getsource(self.nodo)
        self.assertIn("## Previous Season Plan (REVIEW POINT)", fuente)
        self.assertIn("_fijar_arranque(contenido", fuente)


class LasReglasDeRecuperacionSonDelAtleta(unittest.TestCase):
    def test_la_semana_puede_usar_una_regla_si_es_suya(self):
        fuente = inspect.getsource(render_recovery_block)
        self.assertIn("Una regla de ajuste SÍ es legítima si es suya", fuente)
        self.assertIn("un umbral de población", fuente)
        self.assertNotIn("ni derives de él una regla fija", fuente)

    def test_el_macrociclo_ancla_sus_disparadores_a_la_base_del_atleta(self):
        prompt = importlib.import_module("services.ai.langgraph.nodes.season_planner_node").SEASON_PLANNER_USER_PROMPT
        self.assertIn("never a population", prompt)
        self.assertIn("fires every other week", prompt)


if __name__ == "__main__":
    unittest.main()
