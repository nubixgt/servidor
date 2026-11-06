"""Tests for Plan Maestro v5 Track 5: the plan stops speaking Garmin.

The athlete's framing, which became the governing principle of v5:

    "a veces limitamos al sistema a acoplarse a cómo podría escribirse el
    entreno en Garmin, pero este no debe ser un limitante, este debe ser un
    tema de traducción como último paso... si necesita hacer bricks o si
    necesita hacer pliometría, o si requiere ejercicios de rehab o prehab,
    cómo eso lo pasemos a Garmin creo que es otro tema."

Before this, the only vocabulary in the plan was Garmin's -- run / bike / swim
/ strength / walk / brick / rest. A coach who wanted to prescribe plyometrics,
rehab, prehab, mobility or a technique session had no field to say so and had
to disguise it as "strength", which then reached the watch as a duration with
no content. Worse than not sending it, because the athlete believes the watch
has the session.

Two things are pinned here above all:

- **Nothing is dropped silently.** A session Garmin cannot hold reaches the
  athlete as written instructions, never as an empty card or an upload error.
- **The exporter's limits never reach back into the plan.** The validator
  stands down on those days rather than judging a structural approximation
  nobody will execute.

Run with:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    DISCIPLINES_WITHOUT_FAITHFUL_GARMIN_FORM,
    DayIntent,
    WeekBlock,
    WeekIntentContent,
    WorkoutDay,
    WorkoutStep,
    is_garmin_exportable,
)

weekly_module = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")


def _intent(**overrides) -> DayIntent:
    base = dict(
        date="2026-09-07", day_name_es="Lunes", is_rest=False, sport="strength",
        focus="Pliometría", purpose="Trabajo reactivo.", adaptation="Si duele: para.",
        total_duration_minutes=30, intensity_class="neuromuscular", discipline="pliometria",
        session_sketch="3x8 saltos al cajón, 2min descanso entre series",
        execution_cues="Aterriza suave, rodilla alineada.", equipment="Cajón de 40cm",
    )
    base.update(overrides)
    return DayIntent(**base)


def _day(**overrides) -> WorkoutDay:
    base = dict(
        date="2026-09-07", day_name_es="Lunes", is_rest=False, sport="strength",
        focus="Pliometría", purpose="Trabajo reactivo.", adaptation="Si duele: para.",
        total_duration_minutes=30, steps=[],
    )
    base.update(overrides)
    return WorkoutDay(**base)


def _applied(intent: DayIntent, day: WorkoutDay) -> WorkoutDay:
    block = WeekBlock(week_number=1, days=[day])
    weekly_module._apply_discipline(block, WeekIntentContent(week_number=1, days=[intent]))
    return block.days[0]


class ExportabilityIsAFactAboutOurExporter(unittest.TestCase):
    def test_the_disciplines_garmin_cannot_hold_are_named_explicitly(self):
        """Sin desglose de ejercicios, estas disciplinas siguen yendo escritas."""
        for discipline in ("pliometria", "rehab", "prehab", "movilidad"):
            with self.subTest(discipline=discipline):
                self.assertFalse(is_garmin_exportable(discipline, "strength", False))

    def test_the_real_upload_of_2026_09_11_sends_all_ten(self):
        """El atleta subió su semana y llegaron 8 de 10. Las dos que faltaban:

        - la carrera tras la bici del 13-sep, `discipline="brick"`: desde la
          v1.1 un brick son dos sesiones de un deporte, y ésta nunca iba a traer
          tramos de multideporte, así que la regla vieja la dejaba fuera siempre;
        - la fuerza del 17-sep, `discipline="prehab"` CON sus ejercicios, que
          `_strength_steps_from_block` convierte en series con descanso.
        """
        from types import SimpleNamespace
        con_ejercicios = SimpleNamespace(exercises=[SimpleNamespace(name="Pallof press")])
        self.assertTrue(is_garmin_exportable("brick", "run", False))
        self.assertTrue(is_garmin_exportable("prehab", "strength", False, None, con_ejercicios))

    def test_a_legacy_multisport_brick_still_needs_its_legs(self):
        """Los planes guardados con `sport="brick"` siguen la regla de siempre."""
        self.assertFalse(is_garmin_exportable("brick", "brick", False, []))

    def test_a_technique_session_and_a_test_still_export(self):
        """Being unable to encode the drill NAMES is not the same as being
        unable to encode the session: a technique swim is a swim with
        lap-button steps, and a 5K time trial is a run."""
        self.assertTrue(is_garmin_exportable("tecnica", "swim", False))
        self.assertTrue(is_garmin_exportable("test", "run", False))

    def test_the_ordinary_four_disciplines_export(self):
        for discipline, sport in (("carrera", "run"), ("bici", "bike"),
                                  ("natacion", "swim"), ("fuerza", "strength")):
            with self.subTest(discipline=discipline):
                self.assertTrue(is_garmin_exportable(discipline, sport, False))

    def test_a_rest_day_is_never_exportable(self):
        self.assertFalse(is_garmin_exportable("descanso", "rest", True))
        self.assertFalse(is_garmin_exportable("carrera", "run", True))

    def test_a_plan_with_no_declared_discipline_still_exports(self):
        """Plans generated before this field existed must keep uploading."""
        self.assertTrue(is_garmin_exportable(None, "run", False))

    def test_it_is_computed_in_code_never_asked_of_the_model(self):
        """What a session IS is a coaching judgement; what our exporter can
        carry is not. Letting the model set this would let a formatting call
        mark a rehab session exportable and mangle it onto the watch."""
        self.assertNotIn("garmin_exportable", weekly_module.WORKOUT_FORMATTER_SYSTEM_PROMPT)
        self.assertNotIn("garmin_exportable", weekly_module.WORKOUT_FORMATTER_USER_PROMPT)


class TheCoachingVocabularySurvivesTheFormatter(unittest.TestCase):
    def test_the_discipline_is_recopied_from_the_decision_layer(self):
        """A formatting call that quietly changed "pliometria" to "fuerza"
        would turn a session Garmin cannot hold into one it will happily
        accept and mangle."""
        day = _applied(_intent(), _day(discipline="fuerza"))
        self.assertEqual(day.discipline, "pliometria")
        self.assertFalse(day.garmin_exportable)

    def test_days_are_matched_by_date_and_slot(self):
        intents = [_intent(session_slot="am", discipline="natacion", sport="swim"),
                   _intent(session_slot="pm", discipline="pliometria")]
        block = WeekBlock(week_number=1, days=[
            _day(session_slot="am", sport="swim"), _day(session_slot="pm"),
        ])
        weekly_module._apply_discipline(block, WeekIntentContent(week_number=1, days=intents))

        self.assertEqual(block.days[0].discipline, "natacion")
        self.assertTrue(block.days[0].garmin_exportable)
        self.assertEqual(block.days[1].discipline, "pliometria")
        self.assertFalse(block.days[1].garmin_exportable)

    def test_a_day_with_no_matching_intent_is_left_exportable_not_broken(self):
        block = WeekBlock(week_number=1, days=[_day(date="2026-09-09", sport="run")])
        weekly_module._apply_discipline(block, WeekIntentContent(week_number=1, days=[_intent()]))
        self.assertTrue(block.days[0].garmin_exportable)


class NothingIsDroppedSilently(unittest.TestCase):
    def test_the_session_sketch_becomes_the_written_session(self):
        day = _applied(_intent(), _day())
        self.assertIn("3x8 saltos al cajón", day.manual_instructions)

    def test_the_cues_and_equipment_are_appended_not_substituted(self):
        """An earlier version used the cues as the fallback, which left the
        athlete with "Aterriza suave" and no session at all."""
        day = _applied(_intent(), _day())
        self.assertIn("3x8 saltos al cajón", day.manual_instructions)
        self.assertIn("Aterriza suave", day.manual_instructions)
        self.assertIn("Cajón de 40cm", day.manual_instructions)

    def test_what_the_formatter_wrote_wins_over_the_sketch(self):
        day = _applied(_intent(), _day(manual_instructions="A) 3x10 saltos. B) 3x8 zancadas."))
        self.assertIn("3x10 saltos", day.manual_instructions)

    def test_a_non_exportable_day_is_never_left_with_nothing_to_read(self):
        """The watch will not have it, so an empty manual_instructions means
        the session simply does not exist for the athlete."""
        day = _applied(
            _intent(session_sketch="", execution_cues="", equipment=""),
            _day(purpose="Movilidad de cadera y tobillo."),
        )
        self.assertTrue(day.manual_instructions.strip())
        self.assertIn("Movilidad de cadera", day.manual_instructions)

    def test_an_exportable_day_gets_no_manual_text(self):
        day = _applied(_intent(discipline="carrera", sport="run"), _day(sport="run"))
        self.assertTrue(day.garmin_exportable)
        self.assertEqual(day.manual_instructions, "")

    def test_the_upload_reports_it_as_planned_not_as_a_failure(self):
        upload_src = (
            Path(__file__).resolve().parents[1] / "services/garmin/garmin_upload_service.py"
        ).read_text(encoding="utf-8")
        self.assertIn("garmin_exportable", upload_src)
        self.assertIn("No hace falta que hagas nada", upload_src)

    def test_the_day_card_shows_the_written_session(self):
        app_js = (
            Path(__file__).resolve().parents[1] / "web_app/static/app.js"
        ).read_text(encoding="utf-8")
        self.assertIn("plan-day-manual", app_js)
        self.assertIn("Fuera del reloj", app_js)
        self.assertIn("garmin_exportable === false", app_js)


class TheExporterDoesNotReachBackIntoThePlan(unittest.TestCase):
    def setUp(self):
        self.validator = importlib.import_module(
            "services.ai.langgraph.validation.plan_validator"
        )

    def test_the_validator_stands_down_on_a_session_that_never_reaches_the_watch(self):
        """Its `steps` are a structural approximation kept only so the app can
        show a duration. Judging that against pace-band and recovery rules
        flags defects in something nobody will execute -- and the repair prompt
        would then rewrite a session the coach wrote deliberately."""
        offending = _day(
            garmin_exportable=False, discipline="pliometria", intensity_class="vo2max",
            steps=[WorkoutStep(
                step_order=1, step_type="repeat", repeat_count=4,
                description="4x8 saltos",
                substeps=[WorkoutStep(
                    step_order=1, step_type="interval", duration_type="time",
                    duration_value=30, description="8 saltos",
                )],
            )],
        )
        self.assertEqual(self.validator._coaching_violations(offending, {}), [])

    def test_the_same_session_marked_exportable_is_still_checked(self):
        """The stand-down must be scoped to non-exportable days only, or it
        becomes a hole in every rule."""
        offending = _day(
            garmin_exportable=True, discipline="carrera", intensity_class="vo2max", sport="run",
            steps=[WorkoutStep(
                step_order=1, step_type="repeat", repeat_count=4,
                description="4x800",
                substeps=[WorkoutStep(
                    step_order=1, step_type="interval", duration_type="time",
                    duration_value=180, description="800m fuerte",
                )],
            )],
        )
        self.assertNotEqual(self.validator._coaching_violations(offending, {}), [])

    def test_a_legacy_day_without_the_field_is_still_checked(self):
        legacy = _day(discipline=None, intensity_class="vo2max", sport="run", steps=[WorkoutStep(
            step_order=1, step_type="repeat", repeat_count=4, description="4x800",
            substeps=[WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                                  duration_value=180, description="800m fuerte")],
        )])
        self.assertTrue(legacy.garmin_exportable)
        self.assertNotEqual(self.validator._coaching_violations(legacy, {}), [])


class TheCoachIsToldTheyAreNotLimited(unittest.TestCase):
    def test_the_decision_prompt_names_the_unusual_disciplines_as_first_class(self):
        prompt = weekly_module.TRAINING_DECISION_USER_PROMPT
        for discipline in ("pliometria", "rehab", "prehab", "movilidad", "tecnica"):
            with self.subTest(discipline=discipline):
                self.assertIn(discipline, prompt)
        # Sin depender de donde cae el salto de linea: es el significado lo que
        # se fija, no la maquetacion del texto.
        self.assertIn("are first-class", " ".join(prompt.split()))

    def test_it_says_exportability_is_never_a_reason_to_avoid_a_session(self):
        texto = " ".join(weekly_module.TRAINING_DECISION_USER_PROMPT.split())
        self.assertIn("never a reason not to prescribe it", texto)

    def test_the_formatter_is_told_a_placeholder_is_a_failure(self):
        self.assertIn("is a failure", weekly_module.WORKOUT_FORMATTER_SYSTEM_PROMPT)

    def test_every_non_exportable_discipline_has_somewhere_to_be_written(self):
        """If a discipline is in the non-exportable set, the formatter must be
        told to write it out -- otherwise it vanishes for the athlete."""
        prompt = weekly_module.WORKOUT_FORMATTER_SYSTEM_PROMPT
        self.assertIn("manual_instructions", prompt)
        self.assertEqual(
            DISCIPLINES_WITHOUT_FAITHFUL_GARMIN_FORM,
            frozenset({"pliometria", "rehab", "prehab", "movilidad"}),
        )

    def test_el_brick_YA_tiene_forma_en_garmin(self):
        """v7. `brick` estuvo en esa lista porque el formato multideporte era
        una incognita, no porque Garmin no lo tuviera. Dejo de serlo cuando el
        atleta creo uno en su cuenta (1679387475) y se leyo de vuelta: tramos
        ordenados, cada uno con su deporte, sin pasos de transicion."""
        from services.ai.langgraph.schemas.plan_outputs import (
            WorkoutStep,
            is_garmin_exportable,
        )
        pasos = [
            WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                        duration_value=1800, description="30' bici Z2", leg_sport="bike"),
            WorkoutStep(step_order=2, step_type="interval", duration_type="time",
                        duration_value=900, description="15' carrera", leg_sport="run"),
        ]
        self.assertTrue(is_garmin_exportable("brick", "brick", False, pasos))

    def test_un_brick_que_no_dice_sus_tramos_sigue_yendo_ESCRITO(self):
        """Un brick cuyos pasos no dicen a que deporte pertenecen no se puede
        codificar. Va escrito, que es lo que hacia antes y es correcto."""
        from services.ai.langgraph.schemas.plan_outputs import (
            WorkoutStep,
            is_garmin_exportable,
        )
        sin_tramos = [WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                                  duration_value=1800, description="Bici y luego correr")]
        self.assertFalse(is_garmin_exportable("brick", "brick", False, sin_tramos))
        self.assertFalse(is_garmin_exportable("brick", "brick", False, None))


if __name__ == "__main__":
    unittest.main(verbosity=2)
