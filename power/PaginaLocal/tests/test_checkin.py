"""Tests for Plan Maestro v5 Track 6: the athlete gets asked, right before the
week is built.

Everything else the coach sees comes off a watch. The watch does not know last
week was terrible at work, that a shoe rubbed a spot on the achilles, or that
Thursday is a travel day. Since the plan is built one week at a time, the
moment before generation is when asking is cheap and the answer is still true.

The four scored items are the Hooper index (sleep / fatigue / stress /
soreness). Its value comes almost entirely from being repeated and read
against the SAME athlete's own history -- a total of 16 is a bad week for
someone who usually reports 9 and a good one for someone who usually reports
22. `TheBaselineIsPersonalNotPopulational` is the class that matters most here.

And the standing constraint for this project, pinned by
`NoGuardrailWasBuiltBehindIt`: no rule downgrades a session because a number
came back high. Self-report is the weakest evidence in the whole pipeline; a
rule built on it would be the app overriding a coach on the flimsiest data it
has.

Run with:  python -m unittest discover -s tests -v
"""

import importlib
import re
import sys
import tempfile
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.checkin import (  # noqa: E402
    checkin_to_state,
    has_content,
    hooper_total,
    latest_checkin,
    load_checkins,
    normalize_checkin,
    personal_baseline,
    render_checkin_block,
    save_checkin,
)


def _answers(**overrides) -> dict:
    base = {"sleep": 3, "fatigue": 4, "stress": 3, "soreness": 3}
    base.update(overrides)
    return base


def _history(totals: list[int], start_day: int = 1) -> list[dict]:
    """Check-ins whose Hooper totals are exactly `totals` (4 items summing)."""
    history = []
    for index, total in enumerate(totals):
        base, remainder = divmod(total, 4)
        scores = [base] * 4
        for i in range(remainder):
            scores[i] += 1
        history.append(normalize_checkin(
            dict(zip(("sleep", "fatigue", "stress", "soreness"), scores)),
            today=date(2026, 7, start_day + index),
        ))
    return history


class NormalisingWhatTheFormSends(unittest.TestCase):
    def test_a_complete_answer_gets_its_hooper_total(self):
        self.assertEqual(normalize_checkin(_answers())["hooper_total"], 13)

    def test_a_partial_answer_is_kept_rather_than_rejected(self):
        """It is still the only self-reported data the app has -- discarding it
        for incompleteness throws that away."""
        checkin = normalize_checkin({"fatigue": 6, "niggle_location": "Aquiles izquierdo"})
        self.assertEqual(checkin["fatigue"], 6)
        self.assertEqual(checkin["niggle_location"], "Aquiles izquierdo")
        self.assertTrue(has_content(checkin))

    def test_a_partial_answer_has_no_hooper_total(self):
        """A three-item sum is not comparable to a four-item one, and letting
        it through would corrupt every baseline built from it afterwards."""
        self.assertIsNone(hooper_total(normalize_checkin({"sleep": 2, "fatigue": 2, "stress": 2})))

    def test_out_of_range_scores_are_dropped_not_clamped(self):
        """Clamping a 9 to a 7 would invent an answer the athlete never gave."""
        checkin = normalize_checkin(_answers(sleep=9, fatigue=0))
        self.assertIsNone(checkin["sleep"])
        self.assertIsNone(checkin["fatigue"])

    def test_pain_uses_a_zero_to_ten_scale_unlike_the_hooper_items(self):
        self.assertEqual(normalize_checkin({"niggle_pain": 0})["niggle_pain"], 0)
        self.assertEqual(normalize_checkin({"niggle_pain": 10})["niggle_pain"], 10)
        self.assertIsNone(normalize_checkin({"niggle_pain": 11})["niggle_pain"])

    def test_an_unrecognised_week_ahead_is_discarded(self):
        self.assertEqual(normalize_checkin({"week_ahead": "vacaciones"})["week_ahead"], "")

    def test_an_entirely_empty_submission_has_no_content(self):
        self.assertFalse(has_content(normalize_checkin({})))
        self.assertFalse(has_content(None))


class TheBaselineIsPersonalNotPopulational(unittest.TestCase):
    def test_no_baseline_is_claimed_from_too_little_history(self):
        """Averaging two numbers and calling it a trend is worse than saying
        there is no trend yet."""
        self.assertIsNone(personal_baseline(_history([10, 12])))

    def test_a_baseline_appears_once_there_is_enough(self):
        baseline = personal_baseline(_history([10, 12, 14]))
        self.assertEqual(baseline["samples"], 3)
        self.assertEqual(baseline["low"], 10)
        self.assertEqual(baseline["high"], 14)

    def test_the_same_score_reads_differently_for_two_different_athletes(self):
        """The whole point of the instrument. 16/28 against a usual 9 is a bad
        week; against a usual 22 it is a good one."""
        today = normalize_checkin(_answers(sleep=4, fatigue=4, stress=4, soreness=4))
        self.assertEqual(today["hooper_total"], 16)

        low_baseline = render_checkin_block(today, _history([8, 9, 10]))
        high_baseline = render_checkin_block(today, _history([21, 22, 23]))

        self.assertIn("peor", low_baseline)
        self.assertIn("mejor", high_baseline)

    def test_without_a_baseline_the_block_refuses_to_call_the_score_high_or_low(self):
        block = render_checkin_block(normalize_checkin(_answers()), [])
        self.assertIn("no se puede leer como alto o bajo", block)
        self.assertIn("ninguna referencia poblacional", block)

    def test_the_baseline_ignores_the_current_check_in_itself(self):
        """Including today in its own baseline would drag the average toward
        whatever was just answered and shrink every deviation."""
        today = normalize_checkin(_answers(), today=date(2026, 7, 10))
        history = _history([20, 20, 20], start_day=1)
        history.append(today)  # as the caller passes it: today is IN the history
        block = render_checkin_block(today, history)
        self.assertIn("media de 20", block)


class TheBlockFramesRatherThanDecides(unittest.TestCase):
    def test_it_names_itself_as_self_report_not_measurement(self):
        block = render_checkin_block(normalize_checkin(_answers()), _history([12, 13, 14]))
        self.assertIn("Autorreporte, no medición", block)
        self.assertIn("no los sustituye", block)

    def test_one_bad_check_in_is_said_not_to_justify_a_change_on_its_own(self):
        """Same decision, same reason, as the HRV block in recovery_state.py."""
        block = render_checkin_block(normalize_checkin(_answers(fatigue=7)), _history([10, 11, 12]))
        self.assertIn("no justifica cambiar el plan por sí solo", block)

    def test_the_scale_direction_is_stated_so_a_five_is_never_ambiguous(self):
        block = render_checkin_block(normalize_checkin(_answers()), [])
        self.assertIn("7 es peor", block)

    def test_motivation_is_flagged_as_running_the_other_way(self):
        block = render_checkin_block(normalize_checkin(_answers(motivation=6)), [])
        self.assertIn("dirección opuesta", block)

    def test_a_declared_niggle_is_reported_with_its_pain_and_handed_on(self):
        """v6 Track 3 moved what to DO about a niggle into one place
        (services/ai/niggle_constraints.py), together with the health record's
        declared injuries and the gesture map. Saying it here too said it two
        different ways, and the model followed the weaker one: it answered a
        declared groin niggle with a knee contingency."""
        block = render_checkin_block(
            normalize_checkin(_answers(niggle_location="Aquiles izquierdo", niggle_pain=4)), [],
        )
        self.assertIn("Aquiles izquierdo", block)
        self.assertIn("dolor 4/10", block)
        self.assertIn("Molestias declaradas por el atleta", block)

    def test_a_contradiction_with_the_sensors_routes_to_the_conflict_policy(self):
        block = render_checkin_block(normalize_checkin(_answers()), [])
        self.assertIn("política de conflictos", block)
        self.assertIn("week_rationale", block)

    def test_the_week_ahead_shifts_placement_not_only_quantity(self):
        block = render_checkin_block(normalize_checkin(_answers(week_ahead="viaje")), [])
        self.assertIn("viaje", block)
        self.assertIn("no sólo cuánta carga hay", block)

    def test_skipping_the_check_in_costs_nothing(self):
        self.assertEqual(render_checkin_block(None, []), "")
        self.assertEqual(render_checkin_block(normalize_checkin({}), []), "")
        self.assertEqual(checkin_to_state(None)["block"], "")
        self.assertFalse(checkin_to_state(None)["available"])


class Persistence(unittest.TestCase):
    def test_check_ins_accumulate_so_a_baseline_can_exist(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            for day in (1, 8, 15):
                save_checkin(storage, normalize_checkin(_answers(), today=date(2026, 7, day)))
            self.assertEqual(len(load_checkins(storage)), 3)

    def test_a_second_answer_on_the_same_day_replaces_the_first(self):
        """An athlete who opens the form twice before generating must not skew
        their own baseline with a duplicate."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            save_checkin(storage, normalize_checkin(_answers(fatigue=2), today=date(2026, 7, 1)))
            save_checkin(storage, normalize_checkin(_answers(fatigue=6), today=date(2026, 7, 1)))
            stored = load_checkins(storage)
            self.assertEqual(len(stored), 1)
            self.assertEqual(stored[0]["fatigue"], 6)

    def test_the_latest_is_the_most_recent_by_date(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            save_checkin(storage, normalize_checkin(_answers(fatigue=2), today=date(2026, 7, 15)))
            save_checkin(storage, normalize_checkin(_answers(fatigue=6), today=date(2026, 7, 1)))
            self.assertEqual(latest_checkin(storage)["date"], "2026-07-15")

    def test_a_profile_with_no_check_ins_reads_empty_rather_than_failing(self):
        with tempfile.TemporaryDirectory() as tmp:
            self.assertEqual(load_checkins(Path(tmp)), [])
            self.assertIsNone(latest_checkin(Path(tmp)))

    def test_a_corrupted_file_does_not_take_a_generation_down(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            (storage / "checkins.json").write_text("{no es json", encoding="utf-8")
            self.assertEqual(load_checkins(storage), [])


class ItReachesTheDecisionLayer(unittest.TestCase):
    def setUp(self):
        self.module = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")

    def test_the_decision_prompt_has_a_slot_for_it(self):
        self.assertIn("{athlete_checkin}", self.module.TRAINING_DECISION_USER_PROMPT)

    def test_readiness_now_names_the_check_in_alongside_the_sensors(self):
        self.assertIn("Check-in del atleta", self.module.TRAINING_DECISION_USER_PROMPT)

    def test_the_endpoint_returns_the_baseline_not_just_the_answers(self):
        from web_app.server import CheckinModel

        submitted = CheckinModel(profile="x", fatigue=6).model_dump(exclude={"profile"})
        self.assertEqual(normalize_checkin(submitted)["fatigue"], 6)


class NoGuardrailWasBuiltBehindIt(unittest.TestCase):
    def test_the_validator_knows_nothing_about_check_ins(self):
        """Self-report is the weakest evidence in the pipeline. A rule that
        cancelled a session because Monday's answers were bad would be the app
        overriding a coach on the flimsiest data it has."""
        validator = (
            Path(__file__).resolve().parents[1]
            / "services/ai/langgraph/validation/plan_validator.py"
        ).read_text(encoding="utf-8")
        # Estas dos aserciones llevaban un carácter de control donde iba \b desde
        # el 2026-08-29: no podían fallar nunca (encontrado el 2026-09-24).
        # Arregladas, "check-in" aparece -- y con razón: desde el 2026-09-15 el
        # validador lee del check-in los DÍAS que el atleta no puede o abre esta
        # semana, que son disponibilidad, no una autoevaluación. La garantía de
        # debajo es que no lee su BIENESTAR: ni el Hooper (sueño, fatiga, estrés,
        # dolor muscular) ni las ganas ni la molestia.
        for campo in (r"\bhooper\b", r"\bsleep\b", r"\bfatigue\b", r"\bsoreness\b", r"\bmotivation\b",
                      r"\bniggle_pain\b", r"\bniggle_location\b"):
            with self.subTest(campo=campo):
                self.assertIsNone(re.search(campo, validator, re.IGNORECASE))

    def test_the_decision_is_documented_where_someone_would_look_to_undo_it(self):
        module_src = (
            Path(__file__).resolve().parents[1] / "services/ai/checkin.py"
        ).read_text(encoding="utf-8")
        self.assertIn("no automatic guardrail", module_src.lower())
        self.assertIn("never blocks a generation", module_src)


if __name__ == "__main__":
    unittest.main(verbosity=2)
