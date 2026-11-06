"""Plan Maestro v6 Track 3 -- a declared complaint reaches the days that load it.

The real failure, from the athlete's first generated week:

    check-in:   "Un poco de dolor ingle izquierda despues de la 21k"
    plan said:  "Si la rodilla izquierda molesta, reduce la resistencia..."

...on the bike session. Cycling is repeated hip flexion, so that session really
was one of the ones that matter for a groin — and the contingency written for
it was about a different joint entirely.

The instruction to write the niggle into `adaptation` already existed. What did
not exist was any requirement that the structure named be the one the athlete
named. An adaptation for the wrong body part is worse than none: it tells the
athlete in writing that nobody read what they wrote.

Three properties are pinned here:

1. **The athlete's own words survive**, laterality included. Every paraphrase
   is a chance to drift to another body part.
2. **Both sources are collected** — this week's check-in and the health
   record's declared injuries. A coach who addresses one and forgets the other
   has still ignored the athlete.
3. **Nothing blocks a generation.** `unaddressed()` reports a gap; it never
   gates. And the block forbids diagnosing, because a coach who writes "eso
   suena a tendinitis" has stopped being a coach.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    TRAINING_DECISION_USER_PROMPT,
)
from services.ai.niggle_constraints import (  # noqa: E402
    GESTURE_MAP_ES,
    collect,
    niggles_to_state,
    render_niggle_constraints,
    unaddressed,
)

NODE_SRC = (
    Path(__file__).resolve().parents[1] / "services/ai/langgraph/nodes/weekly_planner_node.py"
).read_text(encoding="utf-8")
SERVER_SRC = (
    Path(__file__).resolve().parents[1] / "web_app/server.py"
).read_text(encoding="utf-8")

REAL_CHECKIN = {"niggle_location": "Un poco de dolor ingle izquierda despues de la 21k", "niggle_pain": 2}


class BothPlacesTheAthleteCanDeclarePainAreRead(unittest.TestCase):
    def test_the_check_in_niggle_is_collected(self):
        found = collect(REAL_CHECKIN, {})
        self.assertEqual(len(found), 1)
        self.assertEqual(found[0]["source"], "check-in")
        self.assertEqual(found[0]["pain"], 2)

    def test_the_health_records_active_injuries_are_collected_too(self):
        """v5 Track 10 already carried these into the prompt, in a different
        block, with a different instruction. One complaint, one constraint."""
        found = collect(None, {"current_injuries": [
            {"location": "tendón de Aquiles", "laterality": "derecha", "pain_scale": 3,
             "aggravating_factors": "cuestas", "treatment": "fisioterapia semanal"},
        ]})
        self.assertEqual(len(found), 1)
        self.assertEqual(found[0]["source"], "ficha de salud")
        self.assertIn("derecha", found[0]["location"])
        self.assertIn("cuestas", found[0]["detail"])
        self.assertIn("fisioterapia", found[0]["detail"])

    def test_both_at_once_produce_two_constraints(self):
        found = collect(REAL_CHECKIN, {"current_injuries": [{"location": "hombro"}]})
        self.assertEqual([n["source"] for n in found], ["check-in", "ficha de salud"])

    def test_an_injury_without_a_location_is_not_a_constraint(self):
        self.assertEqual(collect(None, {"current_injuries": [{"pain_scale": 5}]}), [])

    def test_nothing_declared_means_nothing_collected(self):
        self.assertEqual(collect({}, {}), [])
        self.assertEqual(collect(None, None), [])


class TheAthletesOwnWordsSurvive(unittest.TestCase):
    def test_the_declared_text_is_quoted_verbatim(self):
        """Not summarised to "molestia en la pierna". The drift from "ingle" to
        "rodilla" is exactly what a paraphrase step invites."""
        block = render_niggle_constraints(collect(REAL_CHECKIN, {}))
        self.assertIn("Un poco de dolor ingle izquierda despues de la 21k", block)

    def test_the_block_demands_the_declared_structure_and_side(self):
        block = render_niggle_constraints(collect(REAL_CHECKIN, {}))
        self.assertIn("nombrar la estructura que el atleta declaró", block)
        self.assertIn("con sus palabras y su lado", block)
        self.assertIn("no rodilla", block)

    def test_writing_it_for_the_wrong_part_is_named_as_worse_than_nothing(self):
        block = render_niggle_constraints(collect(REAL_CHECKIN, {}))
        self.assertIn("peor que no escribir ninguna", block)

    def test_no_declared_pain_means_no_block_at_all(self):
        self.assertEqual(render_niggle_constraints([]), "")
        self.assertEqual(render_niggle_constraints(None), "")


class TheGestureMapCoversEveryDisciplineThisAppPlans(unittest.TestCase):
    """Without it the model has to guess which sessions load a declared
    structure from the sport label alone — which is how a groin complaint got
    a knee contingency on the one session that actually loads hip flexors."""

    def test_cycling_is_named_as_loading_the_hip_flexors_and_groin(self):
        self.assertIn("ingle", GESTURE_MAP_ES)
        self.assertIn("psoas", GESTURE_MAP_ES)

    def test_swimming_is_covered_including_the_kick_that_loads_the_groin(self):
        self.assertIn("crol", GESTURE_MAP_ES)
        self.assertIn("hombro", GESTURE_MAP_ES)
        self.assertIn("aductores", GESTURE_MAP_ES)

    def test_running_distinguishes_downhill_from_uphill(self):
        self.assertIn("bajada", GESTURE_MAP_ES)
        self.assertIn("subida", GESTURE_MAP_ES)

    def test_strength_plyometrics_and_bricks_are_covered(self):
        for term in ("Fuerza", "Pliometría", "Brick"):
            with self.subTest(term=term):
                self.assertIn(term, GESTURE_MAP_ES)

    def test_it_also_says_what_UNLOADS_a_structure(self):
        """The conflict policy says never to resolve a problem with rest when
        a substitution preserves the stimulus. A coach who knows a pull buoy
        takes the legs out of a swim has an option other than cancelling."""
        self.assertIn("pull-buoy", GESTURE_MAP_ES)
        self.assertIn("descarga", GESTURE_MAP_ES)

    def test_it_is_declared_open_ended_not_a_closed_list(self):
        block = render_niggle_constraints(collect(REAL_CHECKIN, {}))
        self.assertIn("no un diagnóstico", block)
        self.assertIn("lista cerrada", block)
        self.assertIn("La lista es orientación, no un límite", block)


class ItStaysACoachAndNotAClinician(unittest.TestCase):
    def test_diagnosing_is_forbidden_explicitly(self):
        block = render_niggle_constraints(collect(REAL_CHECKIN, {}))
        self.assertIn("No diagnostiques", block)
        self.assertIn("No nombres una lesión, una patología ni un tratamiento", block)

    def test_referring_the_athlete_to_a_professional_is_named_as_in_scope(self):
        """v5 Track 10's line: the app is not a clinician, and saying "get
        this looked at" is the one medical-adjacent thing it should do."""
        block = render_niggle_constraints(collect(REAL_CHECKIN, {}))
        self.assertIn("profesional presencialmente", block)

    def test_adapting_comes_before_cancelling(self):
        block = render_niggle_constraints(collect(REAL_CHECKIN, {}))
        self.assertIn("Primero **adapta**, no canceles", block)


class TheLintReportsAndNeverBlocks(unittest.TestCase):
    def _day(self, **fields):
        base = {"adaptation": "", "purpose": "", "session_sketch": "", "focus": ""}
        base.update(fields)
        return base

    def test_the_real_failure_is_detected(self):
        """The declared groin, answered only with a knee contingency."""
        missing = unaddressed(
            collect(REAL_CHECKIN, {}),
            [self._day(adaptation="Si la rodilla izquierda molesta, reduce la resistencia.")],
        )
        self.assertEqual(len(missing), 1)

    def test_a_day_that_names_the_declared_structure_clears_it(self):
        missing = unaddressed(
            collect(REAL_CHECKIN, {}),
            [self._day(adaptation="Si la ingle izquierda molesta, baja la cadencia y acorta.")],
        )
        self.assertEqual(missing, [])

    def test_accents_and_case_do_not_decide_whether_it_was_addressed(self):
        missing = unaddressed(
            [{"location": "tendón de Aquiles", "source": "check-in"}],
            [self._day(adaptation="Cuidado con el TENDON DE AQUILES en las cuestas.")],
        )
        self.assertEqual(missing, [])

    def test_a_mention_anywhere_in_the_day_counts(self):
        """Judging WHICH day should carry it is a coaching call, not a string
        match. The lint only asks whether anyone wrote anything at all."""
        missing = unaddressed(
            collect(REAL_CHECKIN, {}),
            [self._day(purpose="Nadar con pull-buoy para no cargar la ingle.")],
        )
        self.assertEqual(missing, [])

    def test_filler_words_alone_never_count_as_addressing_it(self):
        missing = unaddressed(
            [{"location": "la rodilla", "source": "check-in"}],
            [self._day(adaptation="Si te sientes cansado, para la sesión.")],
        )
        self.assertEqual(missing, ["la rodilla"])

    def test_matching_the_side_is_not_matching_the_structure(self):
        """The first version of this lint scored the real failure as
        addressed, because "rodilla izquierda" and "ingle izquierda" share the
        word "izquierda". Laterality says WHERE on the midline, not WHAT."""
        missing = unaddressed(
            [{"location": "ingle izquierda", "source": "check-in"}],
            [self._day(adaptation="Cuida la rodilla izquierda en la bici.")],
        )
        self.assertEqual(missing, ["ingle izquierda"])

    def test_a_structure_outside_the_lexicon_still_gets_a_best_effort(self):
        """An athlete can write anything. Reporting "we could not tell" would
        be less useful than falling back to their own content words."""
        self.assertEqual(
            unaddressed([{"location": "sesamoideo"}], [self._day(adaptation="Nada relevante.")]),
            ["sesamoideo"],
        )
        self.assertEqual(
            unaddressed([{"location": "sesamoideo"}], [self._day(adaptation="Ojo con el sesamoideo.")]),
            [],
        )

    def test_no_declared_pain_or_no_days_means_nothing_to_report(self):
        self.assertEqual(unaddressed([], [self._day()]), [])
        self.assertEqual(unaddressed(collect(REAL_CHECKIN, {}), []), [])
        self.assertEqual(unaddressed(None, None), [])


class TheConstraintReachesTheDecisionPrompt(unittest.TestCase):
    def test_the_prompt_has_the_placeholder(self):
        self.assertIn("{niggle_constraints}", TRAINING_DECISION_USER_PROMPT)

    def test_the_node_fills_it_from_availability_constraints(self):
        self.assertIn('.get("niggles") or {}).get("block")', NODE_SRC)
        self.assertIn("niggle_constraints=niggle_block", NODE_SRC)

    def test_the_server_builds_it_from_both_sources_and_archives_it(self):
        self.assertIn("collect_niggles(latest, profile_dict)", SERVER_SRC)
        self.assertIn('availability_constraints["niggles"] = niggles_to_state', SERVER_SRC)
        self.assertIn('"niggles": availability_constraints.get("niggles")', SERVER_SRC)

    def test_the_lint_runs_after_generation_and_only_prints(self):
        self.assertIn("unaddressed_niggles(", SERVER_SRC)
        start = SERVER_SRC.index("unaddressed_niggles(")
        window = SERVER_SRC[start - 400:start + 400]
        self.assertIn("print(", window)
        for blocking in ("raise ", "return None"):
            self.assertNotIn(blocking, window)

    def test_the_state_dict_carries_the_rendered_block(self):
        payload = niggles_to_state(collect(REAL_CHECKIN, {}))
        self.assertEqual(payload["count"], 1)
        self.assertIn("ingle izquierda", payload["block"])
        self.assertEqual(niggles_to_state([])["block"], "")


if __name__ == "__main__":
    unittest.main(verbosity=2)
