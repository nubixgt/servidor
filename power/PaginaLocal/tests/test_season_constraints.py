"""Tests for Plan Maestro v5 Track 3: the season plan's qualitative decisions
become structured, dated and reviewable.

"Cycling as the primary volume driver to protect the knee", "flat terrain",
"keep ACWR under 1.2" lived only inside `SeasonPlanContent.markdown`. Two
consequences: the weekly planner had to find them in prose, and a constraint
born from a knee niggle that resolved in June still governed the plan in
November, because nothing about it had a start, an expiry or a review.

The athlete's own framing set the boundaries for this track: constraints must
be **per athlete, recommendations rather than laws, and dated so the AI knows
that after a re-evaluation this is no longer a problem**. And explicitly: the
fix is NOT one validator rule per constraint -- that would let a sentence
written six weeks ago veto a coaching decision made against today's data.

So the only deterministic behaviour under test here is date filtering and
labelling. There is no rule that checks a plan against a constraint anywhere,
and `test_no_validator_rule_was_added_for_constraints` pins that.

Run with:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import SeasonConstraint, SeasonPlanContent  # noqa: E402
from services.ai.season_constraints import (  # noqa: E402
    active_constraints,
    render_constraints_block,
    render_constraints_for_review,
)

TODAY = date(2026, 9, 7)


def _constraint(**overrides) -> dict:
    base = {
        "id": "rodilla-impacto",
        "statement": "La bici lleva el volumen; la carrera queda para el trabajo específico.",
        "rationale": "Molestia de rodilla izquierda declarada, dolor 3/10, empeora en bajadas.",
        "kind": "sesgo_deporte",
        "strength": "recomendacion",
        "applies_from": "2026-09-01",
        "review_on": "2026-10-15",
        "source": "lesion_declarada",
        "status": "vigente",
    }
    base.update(overrides)
    return base


class DateFilteringIsTheOnlyDeterministicPart(unittest.TestCase):
    def test_a_constraint_in_force_today_is_included(self):
        self.assertEqual(len(active_constraints([_constraint()], TODAY)), 1)

    def test_one_that_has_not_started_yet_is_not(self):
        self.assertEqual(active_constraints([_constraint(applies_from="2026-11-01")], TODAY), [])

    def test_a_resolved_constraint_stops_reaching_the_planner(self):
        """This is the mechanism the athlete asked for: after a re-evaluation
        says the problem is gone, it must stop shaping the plan."""
        self.assertEqual(active_constraints([_constraint(status="resuelta")], TODAY), [])

    def test_an_expired_one_is_downgraded_but_never_silently_dropped(self):
        """Dropping it on the review date would quietly delete a declared
        medical limitation; keeping it forever is the bug this track fixes.
        So it stays, flagged."""
        active = active_constraints([_constraint(review_on="2026-08-01")], TODAY)
        self.assertEqual(len(active), 1)
        self.assertTrue(active[0]["expired"])

    def test_an_unreadable_start_date_fails_open(self):
        """Failing open on a date we cannot parse is safer than making a
        declared limitation disappear because of a typo."""
        self.assertEqual(len(active_constraints([_constraint(applies_from="pronto")], TODAY)), 1)
        self.assertEqual(len(active_constraints([_constraint(applies_from=None)], TODAY)), 1)

    def test_an_empty_statement_is_not_a_constraint(self):
        self.assertEqual(active_constraints([_constraint(statement="")], TODAY), [])

    def test_limitations_are_listed_before_recommendations(self):
        """What actually binds should be read before what is merely suggested."""
        constraints = [
            _constraint(id="reco", strength="recomendacion"),
            _constraint(id="limit", strength="limitacion"),
        ]
        self.assertEqual([c["id"] for c in active_constraints(constraints, TODAY)], ["limit", "reco"])

    def test_current_constraints_come_before_overdue_ones(self):
        constraints = [
            _constraint(id="vencida", review_on="2026-08-01"),
            _constraint(id="al-dia", review_on="2026-12-01"),
        ]
        self.assertEqual([c["id"] for c in active_constraints(constraints, TODAY)], ["al-dia", "vencida"])

    def test_pydantic_models_and_plain_dicts_are_both_accepted(self):
        model = SeasonConstraint(**_constraint())
        self.assertEqual(len(active_constraints([model], TODAY)), 1)


class TheBlockDoesNotNarrowTheCoach(unittest.TestCase):
    def setUp(self):
        self.block = render_constraints_block([_constraint()], TODAY)

    def test_a_recommendation_is_marked_as_overridable_with_a_reason(self):
        self.assertIn("RECOMENDACIÓN", self.block)
        self.assertIn("puedes apartarte con motivo", self.block)

    def test_a_limitation_is_marked_as_binding(self):
        block = render_constraints_block([_constraint(strength="limitacion")], TODAY)
        self.assertIn("LIMITACIÓN", block)

    def test_an_expired_one_is_told_to_be_treated_as_a_weak_hint(self):
        block = render_constraints_block([_constraint(review_on="2026-08-01")], TODAY)
        self.assertIn("VENCIDA", block)
        self.assertIn("pista débil", block)

    def test_it_says_explicitly_that_it_does_not_force_or_limit_sessions(self):
        """The athlete's standing worry across this whole plan: that the app's
        own rules end up forcing quality days where base building is right, or
        shrinking the menu of session types a coach may prescribe."""
        self.assertIn("Nada de esto te obliga a poner una sesión concreta", self.block)
        self.assertIn("ni te limita los tipos de entreno", self.block)

    def test_it_carries_the_reason_and_the_source_not_just_the_rule(self):
        self.assertIn("dolor 3/10", self.block)
        self.assertIn("lesión declarada", self.block)

    def test_nothing_applicable_costs_no_prompt_tokens(self):
        self.assertEqual(render_constraints_block([], TODAY), "")
        self.assertEqual(render_constraints_block([_constraint(status="resuelta")], TODAY), "")


class ReEvaluationForcesAVerdictOnEachOne(unittest.TestCase):
    def test_every_previous_constraint_comes_back_for_judgement(self):
        review = render_constraints_for_review([_constraint(), _constraint(id="terreno-llano")], TODAY)
        self.assertIn("rodilla-impacto", review)
        self.assertIn("terreno-llano", review)

    def test_the_three_verdicts_are_spelled_out(self):
        review = render_constraints_for_review([_constraint()], TODAY)
        for verdict in ("`vigente`", "`resuelta`", "`modificada`"):
            with self.subTest(verdict=verdict):
                self.assertIn(verdict, review)

    def test_resolving_one_requires_saying_what_datum_proves_it(self):
        review = render_constraints_for_review([_constraint()], TODAY)
        self.assertIn("resolution_note", review)
        self.assertIn("qué dato lo demuestra", review)

    def test_omitting_one_is_named_as_deleting_it_in_silence(self):
        self.assertIn("borra en silencio", render_constraints_for_review([_constraint()], TODAY))

    def test_an_overdue_review_is_pointed_out(self):
        review = render_constraints_for_review([_constraint(review_on="2026-08-01")], TODAY)
        self.assertIn("su revisión ya venció", review)

    def test_a_first_ever_season_plan_has_nothing_to_review(self):
        self.assertEqual(render_constraints_for_review([], TODAY), "")


class TheyReachTheWeeklyPlannerWithoutNewPlumbing(unittest.TestCase):
    def setUp(self):
        self.module = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")

    def test_the_decision_prompt_has_a_slot_for_them(self):
        self.assertIn("{season_constraints}", self.module.TRAINING_DECISION_USER_PROMPT)

    def test_they_are_filtered_against_the_weeks_own_start_date_not_today(self):
        """Generating a week that starts next Monday must be judged by what
        applies then -- a constraint starting mid-week is a real case."""
        phases = {"constraints": [_constraint(applies_from="2026-09-20")]}
        self.assertEqual(self.module._format_constraints(phases, [{"date": "2026-09-14"}]), "")
        self.assertIn("bici", self.module._format_constraints(phases, [{"date": "2026-09-21"}]).lower())

    def test_a_season_plan_with_no_constraints_adds_nothing(self):
        self.assertEqual(self.module._format_constraints({"constraints": []}, [{"date": "2026-09-14"}]), "")
        self.assertEqual(self.module._format_constraints(None, None), "")

    def test_an_unreadable_week_date_falls_back_to_today(self):
        """A malformed week_dates entry must not make every constraint vanish
        -- it falls back to today's date, so a long-standing constraint still
        reaches the planner."""
        phases = {"constraints": [_constraint(applies_from="2020-01-01", review_on="2099-01-01")]}
        self.assertNotEqual(self.module._format_constraints(phases, [{"nope": "x"}]), "")
        self.assertNotEqual(self.module._format_constraints(phases, []), "")

    def test_they_are_persisted_alongside_the_phases_they_belong_to(self):
        from web_app.server import extract_season_phases_dict

        extracted = extract_season_phases_dict({
            "output": {
                "phases": [], "season_start_date": "2026-09-01",
                "constraints": [_constraint()],
            }
        })
        self.assertEqual(len(extracted["constraints"]), 1)

    def test_a_legacy_season_plan_with_no_constraints_key_still_loads(self):
        from web_app.server import extract_season_phases_dict

        extracted = extract_season_phases_dict({"phases": [], "season_start_date": "2026-09-01"})
        self.assertEqual(extracted["constraints"], [])


class NoRuleWasBuiltBehindThem(unittest.TestCase):
    def test_no_validator_rule_was_added_for_constraints(self):
        """The athlete was explicit: one rule per constraint is exactly the
        guardrail pattern to avoid. If this ever fails, someone taught the
        deterministic validator to enforce a sentence."""
        validator = (
            Path(__file__).resolve().parents[1]
            / "services/ai/langgraph/validation/plan_validator.py"
        ).read_text(encoding="utf-8")
        self.assertNotIn("season_constraints", validator)
        self.assertNotIn("SeasonConstraint", validator)

    def test_an_empty_constraint_list_is_a_valid_season_plan(self):
        """Inventing constraints narrows what the weekly planner may prescribe
        for no reason, so 'none' has to be an ordinary answer."""
        plan = SeasonPlanContent(markdown="x", phases=[], season_start_date="2026-09-01")
        self.assertEqual(plan.constraints, [])

    def test_the_season_prompt_says_an_empty_list_is_acceptable(self):
        module = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")
        self.assertIn("an empty list is a valid answer", module.SEASON_PLANNER_USER_PROMPT)

    def test_strength_can_only_be_a_recommendation_or_a_limitation(self):
        """Never a 'rule'. The vocabulary itself is part of the guarantee."""
        with self.assertRaises(Exception):
            SeasonConstraint(**_constraint(strength="regla"))


if __name__ == "__main__":
    unittest.main(verbosity=2)
