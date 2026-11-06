"""Tests for the Training Decision / Workout Formatter split in
weekly_planner_node.py (audit follow-up to Plan Maestro v4 Track B): a
verified real run showed the single-call weekly_planner spending 95% of a
week's cost and 2 full repair rounds on ONE LLM call simultaneously deciding
WHAT to train and HOW to encode it into the Garmin schema. Splitting into a
narrower decision call (no schema) and a narrower formatter call (no
coaching judgment) is a prompt-string-heavy change with no live LLM calls in
this suite -- these tests exist specifically to catch a stray/missing
`{placeholder}` in either prompt before it only surfaces mid a real, paid
generation.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    TRAINING_DECISION_USER_PROMPT,
    WORKOUT_FORMATTER_USER_PROMPT,
)
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    DayIntent,
    TrainingDecisionOutputs,
    WeekIntentContent,
)
from services.ai.prescription import render_zones_table  # noqa: E402


class DecisionSchemaShape(unittest.TestCase):
    def test_a_day_intent_needs_no_json_schema_fields(self):
        """The whole point of the split: DayIntent has no step_type/
        duration_type/target_type -- those belong to the formatter's
        WorkoutStep, not the decision layer."""
        day = DayIntent(
            date="2026-09-02", day_name_es="Miércoles", is_rest=False, sport="run",
            focus="Umbral", purpose="Trabajo de umbral controlado.", adaptation="Si duele: para.",
            total_duration_minutes=40, intensity_class="tempo_threshold", discipline="carrera",
            session_sketch="10min calentamiento Z1, 3x8min a ritmo umbral con 3min trote suave, 10min enfriamiento Z1",
        )
        self.assertFalse(hasattr(day, "steps"))
        self.assertFalse(hasattr(day, "step_type"))

    def test_a_rest_day_needs_no_sketch(self):
        day = DayIntent(
            date="2026-09-06", day_name_es="Domingo", is_rest=True, sport="rest",
            focus="Descanso", purpose="Recuperacion.", adaptation="—",
            intensity_class="rest", discipline="descanso",
        )
        self.assertEqual(day.session_sketch, "")

    def test_a_week_intent_holds_several_day_intents(self):
        days = [
            DayIntent(
                date=f"2026-09-0{i}", day_name_es="Miércoles", is_rest=False, sport="run",
                focus="Facil", purpose="Rodaje.", adaptation="—",
                intensity_class="easy", discipline="carrera", session_sketch="30min Z2",
            )
            for i in range(2, 4)
        ]
        week_intent = WeekIntentContent(week_number=1, days=days)
        self.assertEqual(len(week_intent.days), 2)

    def test_training_decision_outputs_accepts_hitl_questions_too(self):
        """Mirrors WeeklyPlannerOutputs' HITL union -- MasterOrchestrator's
        question-collection machinery must keep working for this earlier step."""
        outputs = TrainingDecisionOutputs(output=[])
        self.assertEqual(outputs.output, [])


class LaGuiaDeMontanaSoloViajaCuandoHaceFalta(unittest.TestCase):
    """~600 tokens en CADA llamada de decision de la corrida. Mandarselos a un
    nadador es pagar por ruido; no mandarselos a un corredor de montana le da
    un plan de asfalto para correr en el monte."""

    def _detecta(self, **estado):
        from services.ai.langgraph.nodes.weekly_planner_node import _is_trail_athlete
        base = {"planning_context": "", "competitions": []}
        base.update(estado)
        return _is_trail_athlete(base)

    def test_una_franja_con_TERRENO_de_montana_la_activa(self):
        """Trail es terreno, no deporte: se declara por franja horaria porque
        casi nadie tiene monte todos los dias. Lo tiene el domingo por la
        manana, y entre semana escaleras o una cuesta."""
        self.assertTrue(self._detecta(planning_context="Domingo | YES | 06:00-10:00 | run, trail"))

    def test_una_competicion_de_montana_la_activa(self):
        self.assertTrue(self._detecta(competitions=[{"name": "Ultra Trail Acatenango"}]))
        self.assertTrue(self._detecta(competitions=[{"name": "Copa", "distance": "trail_ultra_50"}]))

    def test_el_terreno_declarado_la_activa(self):
        self.assertTrue(self._detecta(planning_context="Terrain: montaña y sendero"))

    def test_un_triatleta_de_asfalto_NO_la_paga(self):
        self.assertFalse(self._detecta(
            planning_context="Terrain: road",
            competitions=[{"name": "Triatlon Standard", "distance": "tri_olympic"}],
        ))

    def test_el_ritmo_NO_es_la_modalidad_en_montana(self):
        """La regla entera del track: en una subida del 15% el min/km de
        alguien en forma y el de alguien reventado son el mismo numero."""
        from services.ai.langgraph.schemas.plan_outputs import (
            PACE_TARGETED_CLASSES,
            TRAIL_CLASSES,
        )
        self.assertFalse(TRAIL_CLASSES & PACE_TARGETED_CLASSES)

    def test_la_guia_nombra_las_sesiones_que_hacen_a_un_plan_de_trail(self):
        from services.ai.langgraph.nodes.weekly_planner_node import TRAIL_GUIDANCE
        for pieza in ("hill_repeats", "downhill_technique", "hike_run",
                      "desnivel", "excéntrica", "nutrición"):
            with self.subTest(pieza=pieza):
                self.assertIn(pieza, TRAIL_GUIDANCE)

    def test_la_guia_NO_da_por_hecho_que_hay_montana_a_diario(self):
        """El corredor de montana real tiene monte el domingo y entre semana
        escaleras, una cuesta del barrio o la cinta. Una guia que solo sabe
        prescribir en el monte le deja cuatro dias sin sesion de trail."""
        from services.ai.langgraph.nodes.weekly_planner_node import TRAIL_GUIDANCE
        for alternativa in ("escaleras", "cinta", "cuesta", "cuaderno"):
            with self.subTest(alternativa=alternativa):
                self.assertIn(alternativa, TRAIL_GUIDANCE)

    def test_la_guia_NO_le_quita_las_sesiones_por_ritmo(self):
        """Un corredor de montana hace umbral en llano con toda normalidad. La
        modalidad la decide el TERRENO de esa sesion, no la carrera para la que
        entrena -- restringirlo suprimiria entrenos que un entrenador real si
        manda."""
        from services.ai.langgraph.nodes.weekly_planner_node import TRAIL_GUIDANCE
        self.assertIn("en llano", TRAIL_GUIDANCE)
        self.assertIn("tempo_threshold", TRAIL_GUIDANCE)
        self.assertIn("lista cerrada", TRAIL_GUIDANCE.lower())

    def test_marcar_trail_en_una_franja_no_quita_poder_correr_en_ella(self):
        """Si "Trail" excluyera "run", un domingo de sendero se convertiria en
        un dia sin correr -- que es literalmente lo que se hace en un sendero."""
        import sys
        from pathlib import Path as _P
        sys.path.insert(0, str(_P(__file__).resolve().parents[1]))
        from web_app.server import _canonical_sports_for_day
        self.assertEqual(sorted(_canonical_sports_for_day(["Trail"])), ["run", "trail"])
        self.assertEqual(sorted(_canonical_sports_for_day(["Trail", "Correr"])), ["run", "trail"])
        self.assertEqual(_canonical_sports_for_day(["Nadar"]), ["swim"])


class PromptFormatStringsAreValid(unittest.TestCase):
    """Every placeholder actually used by weekly_planner_node.py's two
    .format(...) calls must exist in the prompt string, and vice versa --
    a mismatch here only shows up mid a real, paid LLM call otherwise."""

    def test_the_training_decision_prompt_accepts_all_kwargs_the_node_passes(self):
        TRAINING_DECISION_USER_PROMPT.format(
            week_number=1,
            total_weeks=1,
            dias_del_bloque="7 days",
            athlete_name="Test",
            current_date="{}",
            week_dates="[]",
            competitions="[]",
            planning_context="",
            prescription_card="### Ficha",
            season_plan="(No season plan available yet.)",
            mesocycle_position="",
            volume_target="",
            load_state="", recovery_state="", season_constraints="", athlete_checkin="",
            niggle_constraints="",
            # Beta 2.1 (S5): el resumen de SU rutina de gimnasio, cuando la tiene
            # confirmada. Vacío para todos los demás, y entonces este prompt no
            # cambia ni un carácter (tests/test_la_fuerza_de_su_rutina.py).
            rutina_de_fuerza="",
            # v7.2: los hábitos de entrenamiento leídos de Garmin — qué días
            # entrena de verdad, de qué, y en cuáles hace calidad. Que este
            # test fallara al añadirlo es justo su trabajo.
            training_habits="",
            # v7: la guia de montana. Va vacia para quien no corre en montana,
            # asi que el marcador siempre existe pero casi nunca cuesta tokens.
            trail_guidance="",
            rationale_de_cambios="",
            # v7 Track 7: el cuaderno del coach. Que este test fallara al
            # añadirlo es exactamente su trabajo -- un placeholder que el nodo
            # pasa y el prompt no declara (o al revés) sólo se manifiesta a
            # mitad de una llamada real y pagada.
            coach_notebook="",
            # Fernando, 2026-09-22: las «Preferencias de entrenamiento» del
            # cuaderno, como petición junto a lo que se respeta tal cual.
            peticiones_del_atleta="",
            previous_plan="",
            adherence="",
            metrics_analysis="",
            activity_analysis="",
            physiology_analysis="",
            previous_weeks_summary="",
        )

    def test_the_workout_formatter_prompt_accepts_all_kwargs_the_node_passes(self):
        WORKOUT_FORMATTER_USER_PROMPT.format(
            week_number=1,
            prescription_card="### Ficha",
            # v7: el catalogo de nombres que el reloj reconoce.
            exercise_catalogue="  SQUAT: GOBLET_SQUAT",
            # v1.1: la lista cerrada de la que sale `video_key`, que es lo que
            # le pone al atleta la imagen del movimiento al lado de la serie.
            exercise_images="### Claves\n**Empuje**: flexion (Flexion)",
            week_intent="{}",
        )

    def test_el_catalogo_del_prompt_SALE_del_diccionario(self):
        """Dos listas que puedan divergir devolverian un ejercicio generico
        donde el plan pedia uno concreto -- el mismo fallo silencioso que
        garmin_vocabulary existe para cerrar."""
        from services.garmin.garmin_vocabulary import (
            VERIFIED_EXERCISES,
            render_exercise_catalogue,
        )
        catalogo = render_exercise_catalogue()
        for categoria, nombres in VERIFIED_EXERCISES.items():
            for nombre in nombres:
                with self.subTest(ejercicio=nombre):
                    self.assertIn(nombre, catalogo)
            self.assertIn(categoria, catalogo)


class ZonesTableIsDeterministic(unittest.TestCase):
    """`zones_table` used to be a field the formatting call had to emit as
    part of its structured output, even though it is a verbatim restatement
    of numbers this app already holds exactly. Anything the model does not
    need to DECIDE should not be in its output schema -- it costs tokens on
    every generation and adds a way for a plan's stated zones to drift from
    the athlete's real ones."""

    # This athlete's real Garmin zones and derived bands.
    REAL_STATE = {
        "hr_zones_by_sport": {
            "run": {"1": [123, 139], "2": [140, 153], "3": [154, 167], "4": [168, 180], "5": [181, None]},
        },
        "pace_bands": {"umbral_tempo": [340.3, 368.0], "facil": [421.2, 471.7]},
    }

    def test_it_reproduces_the_athletes_real_zones(self):
        table = render_zones_table(self.REAL_STATE)
        self.assertIn("Z1 (123-139 ppm)", table)
        self.assertIn("Z2 (140-153 ppm)", table)

    def test_an_open_ended_top_zone_reads_as_a_floor_not_a_range(self):
        self.assertIn("Z5 (181+ ppm)", render_zones_table(self.REAL_STATE))

    def test_pace_bands_are_rendered_in_min_per_km_not_raw_seconds(self):
        table = render_zones_table(self.REAL_STATE)
        self.assertIn("5:40-6:08/km", table)
        self.assertNotIn("340.3", table)

    def test_it_is_stable_across_calls(self):
        self.assertEqual(render_zones_table(self.REAL_STATE), render_zones_table(self.REAL_STATE))

    def test_no_prescription_yields_an_empty_string_for_the_caller_to_handle(self):
        self.assertEqual(render_zones_table(None), "")
        self.assertEqual(render_zones_table({}), "")


if __name__ == "__main__":
    unittest.main(verbosity=2)
