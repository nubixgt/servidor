"""Plan Maestro v6 Track 2 -- the load and readiness numbers reach the planner.

The defect: the athlete's week told them *"permitir que tu ACWR baje de un
elevado 1.47"* while Garmin's own screen said **1.10, `OPTIMAL`**.

Track 1's replay bench showed the model had not invented it. This app computes
`acwr_7d28d` with **rolling averages** and the summarizer published its race-day
peak; Garmin computes `dailyAcuteChronicWorkloadRatio` with **exponential**
averages and reported a value from two days later. Both are right under their
own definition, and nothing labelled either one.

So these tests pin two properties above all:

1. **Neither figure is dropped.** Deleting our own series would throw away the
   only view of the ramp; showing only ours leaves the athlete looking at a
   number that is not on their watch. Both go in, with method and date.
2. **No threshold is invented here.** Every status word in the block is one
   Garmin itself assigned, or a comparison against a range Garmin itself
   published. The module states no safety band of its own, because ACWR's value
   as an injury predictor is contested and method-dependent -- exactly the
   disagreement this block exists to expose.

The fixtures are the athlete's real Garmin payloads, read live on 2026-08-26.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from dataclasses import dataclass
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    CONFLICT_CONSEQUENCE_FOR_TRAINING,
    TRAINING_DECISION_USER_PROMPT,
)
from services.ai.load_state import (  # noqa: E402
    build_load_state,
    load_state_to_state,
    render_load_state,
)
from services.garmin.data_extractor import TriathlonCoachDataExtractor  # noqa: E402

NODE_SRC = (
    Path(__file__).resolve().parents[1] / "services/ai/langgraph/nodes/weekly_planner_node.py"
).read_text(encoding="utf-8")


# --- the athlete's real payloads, 2026-08-26 -------------------------------

REAL_ACUTE_LOAD = {
    "acute_load": 293.0,
    "chronic_load": 259.0,
    "acwr": 1.1,
    "acwr_status": "OPTIMAL",
    "acwr_percent": 47.0,
    "chronic_optimal_min": 207.2,
    "chronic_optimal_max": 388.5,
    "training_status_feedback": "MAINTAINING_3",
    "fitness_trend": 2.0,
    "fitness_trend_sport": "RUNNING",
}

# From 2026-08-25, the day of the generation that exposed the bug: the aerobic
# high band was genuinely over its ceiling and no agent was ever told.
REAL_LOAD_BALANCE_OVER = {
    "aerobic_low": {"load": 392.6, "target_min": 197.0, "target_max": 459.0, "status": "within"},
    "aerobic_high": {"load": 592.1, "target_min": 284.0, "target_max": 547.0, "status": "above"},
    "anaerobic": {"load": 68.4, "target_min": 0.0, "target_max": 262.0, "status": "within"},
    "feedback": "AEROBIC_HIGH_FOCUS",
}

REAL_READINESS = {
    "latest": {"time": "07:02", "context": "AFTER_POST_EXERCISE_RESET", "score": 70.0,
               "level": "MODERATE", "feedback": "RECOVERED_AND_READY", "recovery_time_hours": 9.7},
    "morning": {"time": "04:55", "context": "AFTER_WAKEUP_RESET", "score": 78.0,
                "level": "HIGH", "feedback": "WELL_RECOVERED", "recovery_time_hours": 0.0},
}

REAL_LOAD_HISTORY = [
    {"date": "2026-08-19", "acwr_7d28d": 0.90, "tsb": 0.5},
    {"date": "2026-08-23", "acwr_7d28d": 1.47, "tsb": -20.6},
    {"date": "2026-08-25", "acwr_7d28d": 1.31, "tsb": -12.0},
]


@dataclass
class FakeSummary:
    distance: float | None = None
    duration: int | None = None
    activity_training_load: int | None = None


@dataclass
class FakeActivity:
    activity_type: str
    start_time: str
    activity_name: str | None = None
    summary: FakeSummary | None = None


@dataclass
class FakeStatus:
    acute_training_load: dict | None = None
    vo2_max: dict | None = None
    load_balance: dict | None = None
    training_readiness: dict | None = None


@dataclass
class FakeGarmin:
    training_status: FakeStatus | None = None
    training_load_history: list | None = None
    all_activities: list | None = None
    recent_activities: list | None = None


def _run(km, minutes, day, load=None, name=None, sport="running"):
    return FakeActivity(
        activity_type=sport, start_time=f"{day} 07:00:00", activity_name=name,
        summary=FakeSummary(
            distance=km * 1000 if km is not None else None,
            duration=int(minutes * 60) if minutes is not None else None,
            activity_training_load=load,
        ),
    )


def _full_state(**overrides):
    garmin = FakeGarmin(
        training_status=FakeStatus(
            acute_training_load=REAL_ACUTE_LOAD,
            vo2_max={"value": 49.0, "date": "2026-08-25"},
            load_balance=REAL_LOAD_BALANCE_OVER,
            training_readiness=REAL_READINESS,
        ),
        training_load_history=REAL_LOAD_HISTORY,
        all_activities=[],
    )
    for key, value in overrides.items():
        setattr(garmin, key, value)
    return build_load_state(garmin, today="2026-08-26")


class BothAcwrsSurviveWithTheirMethodAndDate(unittest.TestCase):
    def test_garmins_figure_is_present_with_its_own_status(self):
        block = render_load_state(_full_state())
        self.assertIn("1.10", block)
        self.assertIn("OPTIMAL", block)
        self.assertIn("media exponencial", block)

    def test_this_apps_figure_survives_too_with_its_peak_and_date(self):
        """Deleting our own series would be the easy 'fix' and would destroy
        the only view of the ramp: 0.90 -> peak 1.47 -> 1.31."""
        state = _full_state()
        self.assertEqual(state.own_acwr, 1.31)
        self.assertEqual(state.own_acwr_peak, 1.47)
        self.assertEqual(state.own_acwr_peak_date, "2026-08-23")
        block = render_load_state(state)
        self.assertIn("1.47", block)
        self.assertIn("2026-08-23", block)
        self.assertIn("media móvil", block)

    def test_the_block_says_outright_that_the_two_are_not_interchangeable(self):
        block = render_load_state(_full_state())
        self.assertIn("NO son intercambiables", block)
        self.assertIn("El atleta ve la de Garmin en su reloj", block)

    def test_the_tsb_is_labelled_as_this_apps_own_calculation(self):
        """-20.6 was quoted to the athlete as if Garmin reported it. Garmin
        does not report TSB at all."""
        block = render_load_state(_full_state())
        self.assertIn("TSB calculado por esta app", block)

    def test_acwr_is_framed_as_descriptive_not_as_a_rule(self):
        """The one thing this module must never grow into is a threshold."""
        block = render_load_state(_full_state())
        self.assertIn("describe", block)
        self.assertIn("nunca como una regla", block)

    def test_no_safety_band_of_our_own_invention_appears(self):
        """The metrics expert once wrote 'superando la zona de seguridad
        (1.0-1.25)'. No such band exists in this app, or in the evidence."""
        block = render_load_state(_full_state())
        self.assertNotIn("zona de seguridad", block)
        self.assertNotIn("1.0-1.25", block)


class ReadinessKeepsBothReadingsOfTheDay(unittest.TestCase):
    def test_the_latest_and_the_morning_reading_are_both_shown(self):
        """They disagree by design: readiness drops after training and
        recomposes overnight. Showing one number would have the coach argue
        from whichever half happened to be captured."""
        block = render_load_state(_full_state())
        self.assertIn("Última lectura", block)
        self.assertIn("Al despertar", block)
        self.assertIn("70", block)
        self.assertIn("78", block)
        self.assertIn("9.7 h", block)
        self.assertIn("0.0 h", block)

    def test_a_single_reading_does_not_get_a_spurious_comparison_note(self):
        garmin = FakeGarmin(training_status=FakeStatus(
            acute_training_load=REAL_ACUTE_LOAD,
            training_readiness={"latest": REAL_READINESS["latest"], "morning": None},
        ))
        block = render_load_state(build_load_state(garmin, today="2026-08-26"))
        self.assertIn("Última lectura", block)
        self.assertNotIn("Al despertar", block)


class TheLoadBalanceGarminAlreadyPublishesIsPassedThrough(unittest.TestCase):
    """v6 finding A6: aerobic_high was 592 against a 547 ceiling on the day of
    the real generation. The week that resulted corrected it -- by luck,
    because nothing told the model."""

    def test_a_band_over_its_ceiling_is_named_as_such(self):
        block = render_load_state(_full_state())
        self.assertIn("592", block)
        self.assertIn("284–547", block)
        self.assertIn("por encima del objetivo", block)

    def test_garmins_own_feedback_phrase_travels_intact(self):
        self.assertIn("AEROBIC_HIGH_FOCUS", render_load_state(_full_state()))


class TheEffortHistoryIsComputedNotEstimated(unittest.TestCase):
    """v6 finding A5: the rationale said 'superadas las 72 horas del pico del
    21k' for a day that was five days after the race. Nothing in the app
    computed days-since-anything; the model estimated it from prose."""

    def test_the_hardest_sessions_are_ranked_by_garmins_cross_sport_load(self):
        """Not by distance: on this athlete's real data a 10.3 km trail run
        (load 223) outranks a 21.2 km road race (load 141). Distance would
        have picked the wrong session, and for a triathlete it cannot compare
        a swim with a ride at all."""
        state = _full_state(all_activities=[
            _run(21.2, 155, "2026-08-23", load=141, name="Guatemala Carrera"),
            _run(10.3, 114, "2026-08-09", load=223, name="Panajachel Trail"),
            _run(2.0, 40, "2026-08-20", load=60, name="Fuerza", sport="strength_training"),
        ])
        self.assertEqual(state.hardest_effort["name"], "Panajachel Trail")
        self.assertEqual(state.hardest_effort["days_ago"], 17)

    def test_the_most_recent_hard_session_is_still_visible(self):
        """'The hardest session' and 'the last hard session' are different
        questions and a taper needs both, so three are reported, not one."""
        state = _full_state(all_activities=[
            _run(21.2, 155, "2026-08-23", load=141, name="Guatemala Carrera"),
            _run(10.3, 114, "2026-08-09", load=223, name="Panajachel Trail"),
        ])
        dates = [effort["date"] for effort in state.top_efforts]
        self.assertIn("2026-08-23", dates)
        block = render_load_state(state)
        self.assertIn("hace 3 días", block)

    def test_a_step_up_in_distance_is_named_with_its_ratio(self):
        state = _full_state(all_activities=[
            _run(21.2, 155, "2026-08-23", load=141),
            _run(5.1, 40, "2026-08-19", load=50),
        ])
        efforts = next(s for s in state.sports if s.sport == "run")
        self.assertEqual(efforts.distance_jump, 4.2)
        self.assertIn("un salto de 4.2×", render_load_state(state))

    def test_a_normal_progression_is_not_announced_as_a_jump(self):
        """A lint that fires on every week teaches the reader to ignore it."""
        state = _full_state(all_activities=[
            _run(12.0, 70, "2026-08-23", load=100),
            _run(11.0, 65, "2026-08-19", load=95),
        ])
        self.assertNotIn("salto de", render_load_state(state))

    def test_the_history_is_framed_as_description_not_permission(self):
        state = _full_state(all_activities=[_run(21.2, 155, "2026-08-23", load=141), _run(5.1, 40, "2026-08-19", load=50)])
        self.assertIn("no dicta lo que puede hacer", render_load_state(state))


class NothingHereIsKeyedToRunning(unittest.TestCase):
    """The app serves runners, swimmers, cyclists and triathletes. A block
    that only knew how to describe running would leave every other athlete's
    coach reasoning from prose."""

    def test_a_swimmer_gets_a_swim_summary(self):
        state = _full_state(all_activities=[
            _run(3.0, 65, "2026-08-24", load=90, name="Piscina", sport="lap_swimming"),
            _run(1.5, 35, "2026-08-20", load=45, name="Piscina", sport="lap_swimming"),
        ])
        block = render_load_state(state)
        self.assertIn("Natación", block)
        self.assertNotIn("Carrera:", block)
        efforts = next(s for s in state.sports if s.sport == "swim")
        self.assertEqual(efforts.longest_km, 3.0)
        self.assertEqual(efforts.distance_jump, 2.0)

    def test_a_triathletes_three_sports_are_each_summarised(self):
        state = _full_state(all_activities=[
            _run(21.2, 155, "2026-08-23", load=141, sport="running"),
            _run(60.0, 120, "2026-08-22", load=180, sport="cycling"),
            _run(2.0, 45, "2026-08-21", load=70, sport="lap_swimming"),
        ])
        self.assertEqual({s.sport for s in state.sports}, {"run", "bike", "swim"})
        block = render_load_state(state)
        for label in ("Carrera:", "Bici:", "Natación:"):
            self.assertIn(label, block)

    def test_sports_without_a_meaningful_distance_are_ranked_by_time(self):
        """A 2.3 km walk is not a 'longer' session than a 40-minute hike.
        Ranking everything by distance said it was.

        Antes usaba una sesión de gimnasio como la segunda actividad, y dejó de
        valer cuando `_canonical_sport` aprendió a reconocer la fuerza: las dos
        ya no caían en el mismo cubo y el test medía otra cosa. Caminar y
        senderismo siguen siendo "other" a propósito (ver
        test_prescription_universality), así que prueban exactamente lo mismo.
        """
        state = _full_state(all_activities=[
            _run(2.3, 24, "2026-08-23", load=20, sport="walking"),
            _run(2.2, 40, "2026-08-14", load=30, sport="hiking"),
        ])
        efforts = next(s for s in state.sports if s.sport == "other")
        self.assertEqual(efforts.longest_minutes, 40.0)

    def test_strength_gets_its_own_bucket_instead_of_landing_in_other(self):
        """La fuerza es un deporte que esta app PRESCRIBE, así que tiene que
        saber reconocerla cuando vuelve. Mientras cayó en "other", el atleta
        con seis sesiones de fuerza al mes recibía un plan escrito sobre la
        premisa de que no hacía ninguna."""
        state = _full_state(all_activities=[
            _run(2.3, 24, "2026-08-23", load=20, sport="walking"),
            _run(0.0, 40, "2026-08-14", load=30, sport="strength_training"),
        ])
        self.assertIn("strength", {s.sport for s in state.sports})
        fuerza = next(s for s in state.sports if s.sport == "strength")
        self.assertEqual(fuerza.longest_minutes, 40.0)


class AnAbsentSignalReadsAsAbsent(unittest.TestCase):
    def test_no_data_at_all_says_so_instead_of_inventing_sections(self):
        """No section is rendered full of "N/A" -- that invites reasoning
        about data that does not exist. But silence is not the answer either:
        an absent training status is itself a fact the coach needs, or the
        model will read the gap as "nothing is wrong". So the block collapses
        to the warnings alone."""
        block = render_load_state(build_load_state(FakeGarmin(), today="2026-08-26"))
        self.assertIn("Garmin no devolvió estado de entrenamiento", block)
        self.assertIn("No hay ningún ACWR disponible", block)
        for section in ("Disposición para entrenar", "Load Focus", "Historial de esfuerzos"):
            self.assertNotIn(section, block)

    def test_a_missing_training_status_is_reported_not_hidden(self):
        state = build_load_state(FakeGarmin(training_load_history=REAL_LOAD_HISTORY), today="2026-08-26")
        self.assertTrue(any("estado de entrenamiento" in w for w in state.warnings))
        self.assertIn("⚠️", render_load_state(state))

    def test_none_is_handled(self):
        self.assertEqual(render_load_state(None), "")
        self.assertEqual(load_state_to_state(None), {})


class TheBlockReachesTheDecisionPrompt(unittest.TestCase):
    def test_the_prompt_has_a_load_state_placeholder(self):
        self.assertIn("{load_state}", TRAINING_DECISION_USER_PROMPT)

    def test_the_node_fills_it_from_availability_constraints(self):
        self.assertIn('.get("load_state") or {}).get("block")', NODE_SRC)
        self.assertIn("load_state=load_block", NODE_SRC)

    def test_the_state_dict_carries_the_rendered_block(self):
        payload = load_state_to_state(_full_state())
        self.assertIn("1.10", payload["block"])
        self.assertEqual(payload["garmin_acwr_status"], "OPTIMAL")
        self.assertEqual(payload["own_acwr_peak"], 1.47)

    def test_the_rationale_rules_demand_a_source_and_a_date(self):
        self.assertIn("aparecer literalmente más arriba en este prompt", TRAINING_DECISION_USER_PROMPT)
        self.assertIn("con su fuente y su fecha", TRAINING_DECISION_USER_PROMPT)
        self.assertIn("no lo tengo a la vista", TRAINING_DECISION_USER_PROMPT)

    def test_inventing_agreement_between_sources_is_named_as_the_worse_failure(self):
        """v6 finding A2: the model did not silently pick a side -- it
        fabricated sensor evidence so the sensors would agree with the
        self-report. That is worse, and the policy did not name it."""
        self.assertIn("Nunca escribas que las fuentes coinciden", CONFLICT_CONSEQUENCE_FOR_TRAINING)
        self.assertIn("los dos números encima de la mesa", CONFLICT_CONSEQUENCE_FOR_TRAINING)

    def test_the_example_does_not_hand_every_athlete_someone_elses_injury(self):
        """El ejemplo de esta politica era un "Caso real de este atleta" con una
        "molestia 2/10 en la ingle", y se mandaba a TODOS los atletas en cada
        semana. A un atleta sano le decia que tenia la ingle tocada. Un ejemplo
        con un diagnostico dentro es un sesgo con forma de ejemplo."""
        for palabra in ("ingle", "rodilla", "molestia", "Caso real de este atleta"):
            with self.subTest(palabra=palabra):
                self.assertNotIn(palabra, CONFLICT_CONSEQUENCE_FOR_TRAINING)


class TheExtractorReadsGarminsRealPayload(unittest.TestCase):
    """Both payloads below are verbatim from the athlete's live account on
    2026-08-26. Neither endpoint needed a new API call: Garmin ships the load
    balance inside the training-status response the app already fetches, and
    `garminconnect` has no `get_training_load_balance` method at all at the
    pinned version -- the v6 plan assumed it did."""

    RAW_STATUS = {
        "mostRecentTrainingLoadBalance": {
            "metricsTrainingLoadBalanceDTOMap": {
                "3493189285": {
                    "calendarDate": "2026-08-26",
                    "monthlyLoadAerobicLow": 404.76077,
                    "monthlyLoadAerobicHigh": 505.58954,
                    "monthlyLoadAnaerobic": 38.723022,
                    "monthlyLoadAerobicLowTargetMin": 197,
                    "monthlyLoadAerobicLowTargetMax": 459,
                    "monthlyLoadAerobicHighTargetMin": 284,
                    "monthlyLoadAerobicHighTargetMax": 547,
                    "monthlyLoadAnaerobicTargetMin": 0,
                    "monthlyLoadAnaerobicTargetMax": 262,
                    "trainingBalanceFeedbackPhrase": "BALANCED",
                    "primaryTrainingDevice": True,
                }
            }
        }
    }

    RAW_READINESS = [
        {"timestampLocal": "2026-08-26T07:02:01.0", "inputContext": "AFTER_POST_EXERCISE_RESET",
         "level": "MODERATE", "score": 70, "feedbackShort": "RECOVERED_AND_READY",
         "recoveryTime": 584, "sleepScore": 81, "hrvWeeklyAverage": 63, "acuteLoad": 293},
        {"timestampLocal": "2026-08-26T04:55:10.0", "inputContext": "AFTER_WAKEUP_RESET",
         "level": "HIGH", "score": 78, "feedbackShort": "WELL_RECOVERED",
         "recoveryTime": 0, "sleepScore": 81, "hrvWeeklyAverage": 63, "acuteLoad": 239},
    ]

    def test_the_load_balance_bands_and_their_status_are_derived(self):
        balance = TriathlonCoachDataExtractor._extract_load_balance(self.RAW_STATUS)
        self.assertEqual(balance["aerobic_high"]["load"], 505.58954)
        self.assertEqual(balance["aerobic_high"]["status"], "within")
        self.assertEqual(balance["feedback"], "BALANCED")

    def test_a_band_over_its_ceiling_reports_above(self):
        raw = {"mostRecentTrainingLoadBalance": {"metricsTrainingLoadBalanceDTOMap": {"1": {
            "monthlyLoadAerobicHigh": 592.1,
            "monthlyLoadAerobicHighTargetMin": 284,
            "monthlyLoadAerobicHighTargetMax": 547,
            "primaryTrainingDevice": True,
        }}}}
        self.assertEqual(
            TriathlonCoachDataExtractor._extract_load_balance(raw)["aerobic_high"]["status"], "above"
        )

    def test_the_primary_device_wins_when_several_report(self):
        raw = {"mostRecentTrainingLoadBalance": {"metricsTrainingLoadBalanceDTOMap": {
            "old": {"monthlyLoadAerobicHigh": 10.0, "primaryTrainingDevice": False},
            "watch": {"monthlyLoadAerobicHigh": 505.6, "primaryTrainingDevice": True},
        }}}
        self.assertEqual(
            TriathlonCoachDataExtractor._extract_load_balance(raw)["aerobic_high"]["load"], 505.6
        )

    def test_a_payload_without_the_balance_returns_none_not_empty_bands(self):
        self.assertIsNone(TriathlonCoachDataExtractor._extract_load_balance({}))

    def _readiness(self, payload):
        class FakeClient:
            def get_training_readiness(self, _day):
                return payload

        class FakeExtractor:
            garmin = type("G", (), {"client": FakeClient()})()

            def _call_api(self, fn, *args, default=None, what=""):
                return fn(*args)

        from datetime import date as _date
        return TriathlonCoachDataExtractor._extract_training_readiness(FakeExtractor(), _date(2026, 8, 26))

    def test_recovery_time_is_converted_from_minutes_to_hours(self):
        """Garmin reports 584. That is 9.7 hours, not 584 -- and a coach told
        the athlete had 584 hours of recovery pending would write a very
        different week."""
        readiness = self._readiness(self.RAW_READINESS)
        self.assertEqual(readiness["latest"]["recovery_time_hours"], 9.7)
        self.assertEqual(readiness["morning"]["recovery_time_hours"], 0.0)

    def test_both_the_latest_and_the_morning_reading_are_kept(self):
        readiness = self._readiness(self.RAW_READINESS)
        self.assertEqual(readiness["latest"]["score"], 70.0)
        self.assertEqual(readiness["latest"]["level"], "MODERATE")
        self.assertEqual(readiness["morning"]["score"], 78.0)
        self.assertEqual(readiness["morning"]["level"], "HIGH")

    def test_one_reading_only_does_not_duplicate_itself_as_both(self):
        readiness = self._readiness([self.RAW_READINESS[1]])
        self.assertEqual(readiness["latest"]["score"], 78.0)
        self.assertIsNone(readiness["morning"])

    def test_an_empty_or_failed_response_returns_none(self):
        self.assertIsNone(self._readiness([]))
        self.assertIsNone(self._readiness(None))


if __name__ == "__main__":
    unittest.main(verbosity=2)
