"""Tests for the deterministic coaching rules.

The availability rules already in the validator say nothing about whether a
session will deliver the stimulus it claims to. A plan can respect every hour
budget and still be useless: intervals with no recovery between them, or a
threshold session prescribed by heart rate so the athlete over-exerts on the
first repetitions chasing a number their heart hasn't reached yet.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import (  # noqa: E402
    _apply_coaching_fallbacks,
    _apply_duplicate_date_fallback,
    _apply_missing_date_fallback,
    _apply_race_day_fallback,
    _pace_class_for_day,
    _tier2_repair,
    _walk_steps,
    validate_and_repair_weekly_plan,
    validate_weekly_plan,
)

# The athlete's real bands, as prescription_to_state serialises them.
PRESCRIPTION = {
    "pace_bands": {
        "repeticiones": [291.6, 312.5],
        "vo2max": [312.5, 333.3],
        "umbral_tempo": [340.3, 368.0],
        "maraton": [412.8, 433.8],
        "facil": [421.2, 471.7],
        "recuperacion": [463.3, 526.5],
    },
    "power_bands": {"umbral": [259, 299], "vo2max": [302, 342]},
    "card": "### Ficha (recortada para el test)",
}

WEEK_DATES = [{"date": "2026-09-02", "day_name_es": "Miércoles"}]
AVAILABILITY = {
    "days": {"Miércoles": {"available": True, "hours": 2, "sports": None, "blocks": []}},
    "weekly_hours_budget": None,
    "prescription": PRESCRIPTION,
}


def _day(steps, focus="Intervalos", sport="run", purpose="Estímulo de umbral."):
    return WorkoutDay(
        date="2026-09-02", day_name_es="Miércoles", is_rest=False, sport=sport,
        focus=focus, purpose=purpose, adaptation="Si estás cansado: menos reps.",
        total_duration_minutes=45, steps=steps,
    )


def _payload(day):
    return WeeklyPlanPayload(
        athlete_name="Test", start_date="2026-09-02", zones_table="z",
        weeks=[WeekBlock(week_number=1, days=[day])],
    )


def _violations(day, availability=None):
    report = validate_weekly_plan(_payload(day), availability or AVAILABILITY, WEEK_DATES)
    return {v["type"] for v in report["violations"]}


def _warmup(target_type=TargetType.HR_ZONE, **kwargs):
    return WorkoutStep(
        step_order=1, step_type="warmup", duration_type="time", duration_value=600,
        target_type=target_type, description="10' calentamiento", **kwargs,
    )


def _work(target_type=TargetType.PACE, low=340, high=355, **kwargs):
    extra = {"target_low": low, "target_high": high} if target_type == TargetType.PACE else kwargs
    return WorkoutStep(
        step_order=1, step_type="interval", duration_type="time", duration_value=180,
        target_type=target_type, description="3' fuerte", **extra,
    )


def _recovery():
    return WorkoutStep(
        step_order=2, step_type="recovery", duration_type="time", duration_value=120,
        target_type=TargetType.HR_ZONE, target_zone=1, description="2' trote Z1",
    )


def _repeat(substeps, count=5):
    return WorkoutStep(
        step_order=2, step_type="repeat", repeat_count=count,
        description=f"{count}x(...)", substeps=substeps,
    )


class RecoveryInsideRepeats(unittest.TestCase):
    def test_a_repeat_block_with_no_recovery_is_flagged(self):
        day = _day([_warmup(target_zone=2), _repeat([_work()])])
        self.assertIn("intervals_without_recovery", _violations(day))

    def test_a_repeat_block_with_recovery_is_accepted(self):
        day = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])])
        self.assertNotIn("intervals_without_recovery", _violations(day))

    def test_the_fallback_inserts_a_recovery_step(self):
        day = _day([_warmup(target_zone=2), _repeat([_work()])])
        payload = _payload(day)
        report = validate_weekly_plan(payload, AVAILABILITY, WEEK_DATES)
        payload, changed = _apply_coaching_fallbacks(payload, report["violations"], PRESCRIPTION)
        self.assertTrue(changed)

        block = payload.weeks[0].days[0].steps[1]
        self.assertEqual(len(block.substeps), 2)
        recovery = block.substeps[1]
        self.assertEqual(recovery.step_type, "recovery")
        # Sin zona: una recuperación entre series se corre con el pulso bajando,
        # y cualquier zona le hace pitar al reloj (ver _soltar_zona_en_rangos y
        # el prompt de tier 2, que ya lo pedían así).
        self.assertEqual(recovery.target_type, TargetType.NONE)
        self.assertEqual(recovery.duration_value, 90.0)  # half of the 180s effort
        self.assertNotIn("intervals_without_recovery",
                         {v["type"] for v in validate_weekly_plan(payload, AVAILABILITY, WEEK_DATES)["violations"]})


class QualityMustUsePace(unittest.TestCase):
    def test_a_quality_session_prescribed_by_heart_rate_is_flagged(self):
        """The exact defect the athlete reported from their watch."""
        day = _day([_warmup(target_zone=2),
                    _repeat([_work(TargetType.HR_ZONE, target_zone=4), _recovery()])])
        self.assertIn("quality_without_pace", _violations(day))

    def test_a_quality_session_prescribed_by_pace_is_accepted(self):
        day = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])])
        self.assertNotIn("quality_without_pace", _violations(day))

    def test_an_easy_session_is_not_required_to_carry_a_pace(self):
        day = _day(
            [WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=2700,
                         target_type=TargetType.HR_ZONE, target_zone=2, description="45' Z2")],
            focus="Base aeróbica", purpose="Rodaje suave en zona 2.",
        )
        self.assertNotIn("quality_without_pace", _violations(day))

    def test_the_rule_stays_quiet_when_the_athlete_has_no_pace_data(self):
        """No threshold, no bands -- heart rate is the honest fallback."""
        availability = {**AVAILABILITY, "prescription": {}}
        day = _day([_warmup(target_zone=2),
                    _repeat([_work(TargetType.HR_ZONE, target_zone=4), _recovery()])])
        self.assertNotIn("quality_without_pace", _violations(day, availability))

    def test_the_fallback_moves_work_steps_onto_the_right_band(self):
        day = _day([_warmup(target_zone=2),
                    _repeat([_work(TargetType.HR_ZONE, target_zone=4), _recovery()])],
                   focus="Tempo", purpose="Bloques a ritmo de umbral.")
        payload = _payload(day)
        report = validate_weekly_plan(payload, AVAILABILITY, WEEK_DATES)
        payload, changed = _apply_coaching_fallbacks(payload, report["violations"], PRESCRIPTION)
        self.assertTrue(changed)

        work = payload.weeks[0].days[0].steps[1].substeps[0]
        self.assertEqual(work.target_type, TargetType.PACE)
        self.assertEqual([work.target_low, work.target_high], PRESCRIPTION["pace_bands"]["umbral_tempo"])
        self.assertIsNone(work.target_zone)

    def test_the_recovery_step_is_left_on_heart_rate_by_the_fallback(self):
        day = _day([_warmup(target_zone=2),
                    _repeat([_work(TargetType.HR_ZONE, target_zone=4), _recovery()])])
        payload = _payload(day)
        report = validate_weekly_plan(payload, AVAILABILITY, WEEK_DATES)
        payload, _ = _apply_coaching_fallbacks(payload, report["violations"], PRESCRIPTION)
        recovery = payload.weeks[0].days[0].steps[1].substeps[1]
        self.assertEqual(recovery.target_type, TargetType.HR_ZONE)


class PaceClassInference(unittest.TestCase):
    def test_focus_text_selects_the_band(self):
        cases = {
            "Tempo": "umbral_tempo", "Umbral Z3": "umbral_tempo",
            "VO2max": "vo2max", "Series en pista": "vo2max",
            "Repeticiones 400m": "repeticiones", "Velocidad": "repeticiones",
            "Ritmo de maratón": "maraton",
        }
        for focus, expected in cases.items():
            with self.subTest(focus=focus):
                self.assertEqual(_pace_class_for_day(_day([], focus=focus, purpose="")), expected)

    def test_a_qualifier_does_not_demote_a_hard_session(self):
        """"Tempo suave" is still tempo -- prescribing it as recovery would
        hand the athlete a jog where they needed threshold work."""
        for focus in ("Tempo suave", "Umbral controlado", "Ritmo suave de umbral"):
            with self.subTest(focus=focus):
                self.assertEqual(_pace_class_for_day(_day([], focus=focus, purpose="")), "umbral_tempo")

    def test_marathon_pace_is_not_swallowed_by_the_word_ritmo(self):
        """"Ritmo de maraton" contains "ritmo"; matching it as threshold work
        would prescribe ~5:45/km where the athlete needed ~7:00/km."""
        self.assertEqual(_pace_class_for_day(_day([], focus="Ritmo de maratón", purpose="")), "maraton")

    def test_an_unlabelled_quality_session_defaults_to_the_gentlest_hard_band(self):
        """Guessing wrong should under-dose, never hand over an impossible session."""
        self.assertEqual(_pace_class_for_day(_day([], focus="Sesión dura", purpose="")), "umbral_tempo")


class EasyStepsMustNotBePrescribedByPace(unittest.TestCase):
    def test_a_warmup_with_a_pace_target_is_flagged(self):
        day = _day([_warmup(TargetType.PACE, target_low=421, target_high=471),
                    _repeat([_work(), _recovery()])])
        self.assertIn("easy_step_with_pace", _violations(day))

    def test_the_fallback_leaves_it_with_NO_target(self):
        """Antes esta reparacion lo devolvia a Z2, y esa era la mitad del
        fallo: un calentamiento empieza en reposo, asi que un objetivo Z2 le
        avisa al atleta durante todo el principio por ir suave -- que es
        exactamente lo que se le ha pedido. El pulso atraviesa zonas a
        proposito en un calentamiento, en un enfriamiento y en la recuperacion
        entre repeticiones, y el objetivo de Garmin avisa por arriba Y por
        abajo, asi que ninguna zona unica puede ser justa ahi.

        Lo que quedaria mal dicho sin objetivo se dice en la `description`,
        que el reloj muestra igual."""
        day = _day([_warmup(TargetType.PACE, target_low=421, target_high=471),
                    _repeat([_work(), _recovery()])])
        payload = _payload(day)
        report = validate_weekly_plan(payload, AVAILABILITY, WEEK_DATES)
        payload, changed = _apply_coaching_fallbacks(payload, report["violations"], PRESCRIPTION)
        self.assertTrue(changed)
        warmup = payload.weeks[0].days[0].steps[0]
        self.assertEqual(warmup.target_type, TargetType.NONE)
        self.assertIsNone(warmup.target_zone)
        self.assertIsNone(warmup.target_low)


class PaceMustBeReachable(unittest.TestCase):
    def test_a_pace_faster_than_the_athlete_can_run_is_flagged(self):
        day = _day([_warmup(target_zone=2), _repeat([_work(low=200, high=210), _recovery()])])
        self.assertIn("pace_out_of_range", _violations(day))

    def test_a_pace_slower_than_walking_is_flagged(self):
        day = _day([_warmup(target_zone=2), _repeat([_work(low=800, high=900), _recovery()])])
        self.assertIn("pace_out_of_range", _violations(day))

    def test_a_pace_inside_the_athletes_range_is_accepted(self):
        day = _day([_warmup(target_zone=2), _repeat([_work(low=340, high=355), _recovery()])])
        self.assertNotIn("pace_out_of_range", _violations(day))

    def test_the_fallback_clamps_it_back_into_range(self):
        day = _day([_warmup(target_zone=2), _repeat([_work(low=200, high=210), _recovery()])])
        payload = _payload(day)
        report = validate_weekly_plan(payload, AVAILABILITY, WEEK_DATES)
        payload, changed = _apply_coaching_fallbacks(payload, report["violations"], PRESCRIPTION)
        self.assertTrue(changed)
        work = payload.weeks[0].days[0].steps[1].substeps[0]
        self.assertGreaterEqual(work.target_low, PRESCRIPTION["pace_bands"]["repeticiones"][0])


class CyclingUsesPower(unittest.TestCase):
    def test_a_bike_quality_session_without_power_is_flagged(self):
        day = _day([_repeat([
            WorkoutStep(step_order=1, step_type="interval", duration_type="time", duration_value=300,
                        target_type=TargetType.HR_ZONE, target_zone=4, description="5' fuerte"),
            _recovery(),
        ])], sport="bike", focus="Intervalos", purpose="Trabajo de umbral en bici.")
        self.assertIn("quality_without_power", _violations(day))

    def test_a_bike_quality_session_with_power_is_accepted(self):
        day = _day([_repeat([
            WorkoutStep(step_order=1, step_type="interval", duration_type="time", duration_value=300,
                        target_type=TargetType.POWER, target_low=259, target_high=299,
                        description="5' a 259-299 W"),
            _recovery(),
        ])], sport="bike", focus="Intervalos", purpose="Trabajo de umbral en bici.")
        self.assertNotIn("quality_without_power", _violations(day))


class NoFalsePositives(unittest.TestCase):
    def test_a_rest_day_produces_no_coaching_violations(self):
        day = WorkoutDay(date="2026-09-02", day_name_es="Miércoles", is_rest=True, sport="rest",
                         focus="Descanso", purpose="Recuperación.", adaptation="—", steps=[])
        self.assertEqual(_violations(day), set())

    def test_a_strength_day_is_not_asked_for_a_pace(self):
        day = _day([
            WorkoutStep(step_order=1, step_type="main", duration_type="reps", duration_value=8,
                        repeat_count=4, exercise_name="Sentadilla", description="Sentadilla 4x8"),
        ], sport="strength", focus="Fuerza", purpose="Fuerza máxima de tren inferior.")
        found = _violations(day)
        self.assertNotIn("quality_without_pace", found)
        self.assertNotIn("quality_without_power", found)

    def test_a_correct_quality_session_passes_every_coaching_rule(self):
        day = _day([
            _warmup(target_zone=2),
            _repeat([_work(low=340, high=355), _recovery()]),
            WorkoutStep(step_order=3, step_type="cooldown", duration_type="time", duration_value=480,
                        target_type=TargetType.HR_ZONE, target_zone=1, description="8' Z1"),
        ], focus="Tempo", purpose="Bloques a ritmo de umbral.")
        self.assertEqual(_violations(day), set())

    def test_walking_steps_inside_a_repeat_count_as_recovery(self):
        day = _day([_repeat([
            _work(),
            WorkoutStep(step_order=2, step_type="rest", duration_type="time", duration_value=60,
                        target_type=TargetType.NONE, description="60\" caminando"),
        ])])
        self.assertNotIn("intervals_without_recovery", _violations(day))


class FallbacksAreScoped(unittest.TestCase):
    def test_a_day_that_was_not_flagged_is_left_untouched(self):
        good = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])])
        before = good.model_dump_json()
        payload, changed = _apply_coaching_fallbacks(_payload(good), [], PRESCRIPTION)
        self.assertFalse(changed)
        self.assertEqual(payload.weeks[0].days[0].model_dump_json(), before)

    def test_nested_steps_are_reachable_by_the_fallbacks(self):
        day = _day([_repeat([_work(TargetType.HR_ZONE, target_zone=4), _recovery()])])
        self.assertEqual(len(_walk_steps(day.steps)), 3)


class _FakeRepairResult:
    def __init__(self, output):
        self.output = output


class _FakeRepairLLM:
    """Stands in for llm_with_structure.ainvoke in tier-2 repair tests."""

    def __init__(self, response_payload):
        self._response = response_payload

    async def ainvoke(self, _messages):
        return _FakeRepairResult(self._response)


def _week2_good_day():
    return WorkoutDay(
        date="2026-09-09", day_name_es="Miércoles", is_rest=False, sport="run",
        focus="Umbral", purpose="Sesión de calidad.", adaptation="Si duele: para.",
        total_duration_minutes=45,
        steps=[_warmup(target_zone=2), _repeat([_work(), _recovery()])],
    )


class Tier2RepairIsScopedToFlaggedDays(unittest.IsolatedAsyncioTestCase):
    """The real production bug: tier-2 repair receives the FULL merged
    multi-week plan and is asked to return the FULL plan again -- exactly the
    single-giant-call shape weekly_planner_node.py's per-week generation
    exists to avoid (see its module docstring: asked to reproduce ~28 days of
    step detail in one completion, Claude silently drops most of it). When
    the repair model garbles a day it wasn't even asked to touch, that damage
    must never reach the plan."""

    async def test_an_unflagged_day_survives_even_if_the_repair_call_wipes_it(self):
        flagged_day = _day([])  # missing_steps, week 1
        good_day = _week2_good_day()  # week 2, not flagged, has real steps
        original = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[flagged_day]),
                   WeekBlock(week_number=2, days=[good_day])],
        )
        violations = [{
            "date": flagged_day.date, "session_slot": "unica", "type": "missing_steps",
            "detail": "sin steps",
        }]

        # The model fixes the flagged day correctly, but silently wipes the
        # OTHER week's real content to [] while "reproducing" it -- exactly
        # what a truncated giant completion does. Dates/weeks are all still
        # present, so the old anti-truncation check (weeks/dates count only)
        # would not have caught this.
        fixed_flagged_day = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])])
        wiped_good_day = good_day.model_copy(update={"steps": []})
        garbled_response = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[fixed_flagged_day]),
                   WeekBlock(week_number=2, days=[wiped_good_day])],
        )

        result = await _tier2_repair(original, violations, _FakeRepairLLM(garbled_response), AVAILABILITY)

        # The flagged day took the model's fix...
        self.assertTrue(result.weeks[0].days[0].steps)
        # ...but the untouched week must be byte-for-byte what it was before,
        # never the garbled empty version the model echoed back for it.
        self.assertEqual(result.weeks[1].days[0].model_dump_json(), good_day.model_dump_json())

    async def test_a_week_scoped_violation_trusts_only_its_own_week(self):
        """weekly_hours_exceeded / acwr_guardrail name a week_number, not a
        date, because the fix legitimately touches every day in that week --
        but a week NOT named by any violation must still come back untouched,
        even if the model garbles it while echoing the full plan back."""
        week1_day = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])])
        week2_day = _week2_good_day()
        original = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[week1_day]),
                   WeekBlock(week_number=2, days=[week2_day])],
        )
        violations = [{"week_number": 1, "type": "weekly_hours_exceeded", "detail": "excede"}]

        trimmed_week1_day = week1_day.model_copy(update={"total_duration_minutes": 30})
        wiped_week2_day = week2_day.model_copy(update={"steps": []})
        garbled_response = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[trimmed_week1_day]),
                   WeekBlock(week_number=2, days=[wiped_week2_day])],
        )

        result = await _tier2_repair(original, violations, _FakeRepairLLM(garbled_response), AVAILABILITY)

        self.assertEqual(result.weeks[0].days[0].total_duration_minutes, 30)
        self.assertEqual(result.weeks[1].days[0].model_dump_json(), week2_day.model_dump_json())


class RepairAcceptsAUnionFreeSchema(unittest.IsolatedAsyncioTestCase):
    """Repair is bound to WeeklyPlanPayload directly now, not to the
    `list[Question] | WeeklyPlanPayload` union: repair never needs the HITL
    branch, and the union measurably degrades structured output (a real run
    failed here with pydantic reporting errors for BOTH branches at once).
    _tier2_repair has to keep accepting the old wrapper shape too, since
    other call sites and the CLI still pass it."""

    async def test_a_bare_payload_response_is_accepted(self):
        flagged_day = _day([])
        original = _payload(flagged_day)
        violations = [{
            "date": flagged_day.date, "session_slot": "unica", "type": "missing_steps",
            "detail": "sin steps",
        }]
        fixed = _payload(_day([_warmup(target_zone=2), _repeat([_work(), _recovery()])]))

        class _BarePayloadLLM:
            async def ainvoke(self, _messages):
                return fixed  # no .output wrapper

        result = await _tier2_repair(original, violations, _BarePayloadLLM(), AVAILABILITY)
        self.assertTrue(result.weeks[0].days[0].steps)

    async def test_the_wrapped_shape_still_works(self):
        flagged_day = _day([])
        original = _payload(flagged_day)
        violations = [{
            "date": flagged_day.date, "session_slot": "unica", "type": "missing_steps",
            "detail": "sin steps",
        }]
        fixed = _payload(_day([_warmup(target_zone=2), _repeat([_work(), _recovery()])]))

        result = await _tier2_repair(original, violations, _FakeRepairLLM(fixed), AVAILABILITY)
        self.assertTrue(result.weeks[0].days[0].steps)


class _ExplodingLLM:
    """Raises if ever invoked -- proves a code path never reaches tier-2 LLM repair."""

    async def ainvoke(self, _messages):
        raise AssertionError("tier-2 repair should not have been invoked for this day")


class StrengthSkipsLlmRepair(unittest.IsolatedAsyncioTestCase):
    """Plan Maestro v4 Track A: a strength day with empty steps must be fixed
    by the deterministic single-step fallback directly, never via tier-2 LLM
    repair. In a real Lite-tier run, 12/12 strength days hit missing_steps,
    burning ~90% of the generation's cost on repair attempts that mostly
    failed anyway (one attempt threw a Pydantic OutputParserException trying
    to fill `substeps` for a strength "repeat" block that was never supposed
    to exist)."""

    async def test_a_strength_day_with_no_steps_is_fixed_without_calling_the_llm(self):
        strength_day = _day([], sport="strength", focus="Fuerza piernas", purpose="Fuerza de tren inferior.")
        payload = _payload(strength_day)

        result, report = await validate_and_repair_weekly_plan(
            payload, AVAILABILITY, WEEK_DATES, _ExplodingLLM(),
        )

        self.assertTrue(result.weeks[0].days[0].steps)
        self.assertEqual(report["attempts"], 0)

    async def test_a_non_strength_day_with_no_steps_still_goes_through_llm_repair(self):
        run_day = _day([], sport="run", focus="Tempo", purpose="Bloques a ritmo de umbral.")
        payload = _payload(run_day)
        fixed = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])])
        repair_response = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[fixed])],
        )

        result, report = await validate_and_repair_weekly_plan(
            payload, AVAILABILITY, WEEK_DATES, _FakeRepairLLM(repair_response),
        )

        self.assertGreaterEqual(report["attempts"], 1)
        self.assertTrue(result.weeks[0].days[0].steps)


class MissingDateNeverVanishesSilently(unittest.TestCase):
    """Plan Maestro v4 Track D: missing_date used to have no fallback at all
    -- a date the LLM never reconstructed just stayed absent from
    payload.weeks forever, and the UI's summary message claimed it had been
    "forced to rest" when nothing had actually been forced (the day simply
    didn't exist). In a real run, 6 of a week's 7 days -- including the
    athlete's race day -- vanished this way."""

    def test_a_missing_date_is_synthesized_as_a_rest_day(self):
        week_dates = [
            {"date": "2026-09-02", "day_name_es": "Miércoles"},
            {"date": "2026-09-03", "day_name_es": "Jueves"},
        ]
        present_day = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])])
        payload = _payload(present_day)  # only 2026-09-02 present

        report = validate_weekly_plan(payload, AVAILABILITY, week_dates)
        violations = [v for v in report["violations"] if v["type"] == "missing_date"]
        self.assertEqual(len(violations), 1)

        payload, changed = _apply_missing_date_fallback(payload, violations, week_dates)
        self.assertTrue(changed)

        dates = {d.date for d in payload.weeks[0].days}
        self.assertEqual(dates, {"2026-09-02", "2026-09-03"})
        synthesized = next(d for d in payload.weeks[0].days if d.date == "2026-09-03")
        self.assertTrue(synthesized.is_rest)
        self.assertEqual(synthesized.sport, "rest")

        report = validate_weekly_plan(payload, AVAILABILITY, week_dates)
        self.assertNotIn("missing_date", {v["type"] for v in report["violations"]})


class DuplicateDateReconciliation(unittest.IsolatedAsyncioTestCase):
    """The 2026-09-14 production bug: two entries both session_slot='pm' for
    the same date. duplicate_date violations don't carry a session_slot, so
    the old reconciliation code matched against a fabricated ('date','unica')
    key that could never equal the real 'pm'/'pm' keys the days actually
    carried -- the LLM's fix for that date was always discarded and the
    duplicate shipped broken."""

    async def test_tier2_repair_can_now_actually_fix_a_duplicate_pm_pair(self):
        dup_a = WorkoutDay(
            date="2026-09-14", day_name_es="Lunes", is_rest=False, sport="strength",
            focus="Fuerza", purpose="Fuerza de piernas.", adaptation="—",
            total_duration_minutes=45, session_slot="pm", steps=[],
        )
        dup_b = WorkoutDay(
            date="2026-09-14", day_name_es="Lunes", is_rest=False, sport="run",
            focus="Recuperación", purpose="Trote suave.", adaptation="—",
            total_duration_minutes=30, session_slot="pm", steps=[],
        )
        original = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-14", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[dup_a, dup_b])],
        )
        violations = [{
            "date": "2026-09-14", "type": "duplicate_date",
            "detail": "2 entradas para esta fecha pero session_slot no las distingue",
        }]

        fixed_a = dup_a.model_copy(update={"session_slot": "am"})
        fixed_b = dup_b.model_copy(update={"session_slot": "pm"})
        repair_response = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-14", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[fixed_a, fixed_b])],
        )

        result = await _tier2_repair(original, violations, _FakeRepairLLM(repair_response), AVAILABILITY)

        slots = sorted(d.session_slot for d in result.weeks[0].days)
        self.assertEqual(slots, ["am", "pm"])

    def test_a_duplicate_that_survives_repair_keeps_only_the_first_entry(self):
        dup_a = WorkoutDay(
            date="2026-09-14", day_name_es="Lunes", is_rest=False, sport="run",
            focus="Recuperación", purpose="Trote suave.", adaptation="—",
            total_duration_minutes=30, session_slot="pm",
            steps=[_warmup(target_zone=1)],
        )
        dup_b = dup_a.model_copy()
        payload = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-14", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[dup_a, dup_b])],
        )
        violations = [{"date": "2026-09-14", "type": "duplicate_date", "detail": "duplicado"}]

        payload, changed = _apply_duplicate_date_fallback(payload, violations)

        self.assertTrue(changed)
        self.assertEqual(len(payload.weeks[0].days), 1)


_MONTERRICO_5K = {
    "name": "5K Monterrico", "date": "2026-09-20", "race_type": "run", "priority": "b",
    "target_time": "00:25:00",
}


class RaceDayIsCrossCheckedAgainstTheCalendar(unittest.TestCase):
    """Plan Maestro v4 Track C: the real bug -- the season_planner's own text
    said "Week 4 incorporates a taper... for the 5k Monterrico B-race", but
    nothing ever cross-referenced WorkoutDay.date against the competition
    calendar, so the athlete's actual race day shipped as a generic
    70-minute Zone 2 long run."""

    def test_a_generic_session_on_race_day_is_flagged(self):
        race_day = WorkoutDay(
            date="2026-09-20", day_name_es="Domingo", is_rest=False, sport="run",
            focus="Tirada larga", purpose="Rodaje aerobico continuo en Z2.", adaptation="—",
            total_duration_minutes=70,
            steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=4200,
                                target_type=TargetType.HR_ZONE, target_zone=2, description="70' Z2")],
        )
        week_dates = [{"date": "2026-09-20", "day_name_es": "Domingo"}]
        availability = {**AVAILABILITY, "competitions": [_MONTERRICO_5K]}
        report = validate_weekly_plan(_payload(race_day), availability, week_dates)
        self.assertIn("race_day_conflict", {v["type"] for v in report["violations"]})

    def test_a_session_that_names_the_race_is_accepted(self):
        race_day = WorkoutDay(
            date="2026-09-20", day_name_es="Domingo", is_rest=False, sport="run",
            focus="Carrera: 5K Monterrico", purpose="Dia de competencia, ejecutar pacing objetivo.",
            adaptation="—", total_duration_minutes=30,
            steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=1800,
                                target_type=TargetType.NONE, description="Carrera 5K")],
        )
        week_dates = [{"date": "2026-09-20", "day_name_es": "Domingo"}]
        availability = {**AVAILABILITY, "competitions": [_MONTERRICO_5K]}
        report = validate_weekly_plan(_payload(race_day), availability, week_dates)
        self.assertNotIn("race_day_conflict", {v["type"] for v in report["violations"]})

    def test_a_quality_session_of_the_race_sport_on_the_eve_is_flagged(self):
        taper_day = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])],
                          focus="Umbral", purpose="Bloques a ritmo de umbral.")
        taper_day.date = "2026-09-19"
        week_dates = [{"date": "2026-09-19", "day_name_es": "Sábado"}]
        availability = {**AVAILABILITY, "competitions": [_MONTERRICO_5K]}
        report = validate_weekly_plan(_payload(taper_day), availability, week_dates)
        self.assertIn("race_taper_conflict", {v["type"] for v in report["violations"]})

    def test_three_days_out_the_quality_session_is_the_coachs_call(self):
        """La regla vieja marcaba esta misma sesion el 17 para el 5K del 20.
        Un taper mantiene la intensidad y recorta el volumen (Mujika, Bosquet
        2007); cuanto, lo decide quien ve la semana entera."""
        taper_day = _day([_warmup(target_zone=2), _repeat([_work(), _recovery()])],
                          focus="Umbral", purpose="Bloques a ritmo de umbral.")
        taper_day.date = "2026-09-17"
        week_dates = [{"date": "2026-09-17", "day_name_es": "Jueves"}]
        availability = {**AVAILABILITY, "competitions": [_MONTERRICO_5K]}
        report = validate_weekly_plan(_payload(taper_day), availability, week_dates)
        self.assertNotIn("race_taper_conflict", {v["type"] for v in report["violations"]})

    def test_a_swim_at_css_before_a_running_race_is_not_a_taper_conflict(self):
        """El caso real del 2026-09-11: 6x100 m a CSS el viernes 18 antes del
        5K de carrera del domingo 20. La regla lo marco sin mirar el deporte y
        la reparacion tier-2 costo una llamada de 58k caracteres."""
        swim = _day([_warmup(target_type=TargetType.NONE),
                     _repeat([_work(), _recovery()])],
                    focus="CSS controlado", sport="swim", purpose="Umbral en el agua sin cargar piernas.")
        swim.date = "2026-09-19"
        swim.intensity_class = "tempo_threshold"
        week_dates = [{"date": "2026-09-19", "day_name_es": "Sábado"}]
        availability = {**AVAILABILITY, "competitions": [{**_MONTERRICO_5K, "distance": "5K"}]}
        report = validate_weekly_plan(_payload(swim), availability, week_dates)
        self.assertNotIn("race_taper_conflict", {v["type"] for v in report["violations"]})

    def test_an_easy_session_inside_the_taper_window_is_not_flagged(self):
        easy_day = _day(
            [WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=1200,
                         target_type=TargetType.HR_ZONE, target_zone=1, description="20' Z1")],
            focus="Trote suave", purpose="Recuperacion antes de la carrera.",
        )
        easy_day.date = "2026-09-17"
        week_dates = [{"date": "2026-09-17", "day_name_es": "Jueves"}]
        availability = {**AVAILABILITY, "competitions": [_MONTERRICO_5K]}
        report = validate_weekly_plan(_payload(easy_day), availability, week_dates)
        self.assertNotIn("race_taper_conflict", {v["type"] for v in report["violations"]})

    def test_no_competitions_means_no_race_day_violations(self):
        race_day = WorkoutDay(
            date="2026-09-20", day_name_es="Domingo", is_rest=False, sport="run",
            focus="Tirada larga", purpose="Rodaje aerobico continuo en Z2.", adaptation="—",
            total_duration_minutes=70,
            steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=4200,
                                target_type=TargetType.HR_ZONE, target_zone=2, description="70' Z2")],
        )
        report = validate_weekly_plan(_payload(race_day), AVAILABILITY, [{"date": "2026-09-20", "day_name_es": "Domingo"}])
        self.assertNotIn("race_day_conflict", {v["type"] for v in report["violations"]})

    def test_a_competition_far_outside_this_weeks_window_is_never_flagged(self):
        """The real bug found in a Track B run: generating the week of
        2026-08-24 (7 dates, none of them near any competition) got THREE
        synthesized "race day" entries stapled onto it -- one for the 5K in
        September 2026, and two for races in April/August 2027 -- because the
        old check only asked "does this competition have a WorkoutDay in the
        payload", which is false for essentially every competition when the
        payload is only a week wide. Burned two tier-2 repair rounds
        (~$0.50 of a $0.52 week) fixing violations that should never have
        existed."""
        ordinary_day = _day(
            [WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=1800,
                         target_type=TargetType.HR_ZONE, target_zone=2, description="30' Z2")],
            focus="Facil", purpose="Rodaje suave.",
        )
        ordinary_day.date = "2026-08-24"
        week_dates = [{"date": "2026-08-24", "day_name_es": "Lunes"}]
        far_future_competitions = [
            _MONTERRICO_5K,  # 2026-09-20 -- weeks away, not in this window
            {"name": "10k Ciudad de Guatemala", "date": "2027-04-20", "race_type": "run", "priority": "a"},
            {"name": "21k Ciudad de Guatemala", "date": "2027-08-20", "race_type": "run", "priority": "a"},
        ]
        availability = {**AVAILABILITY, "competitions": far_future_competitions}

        report = validate_weekly_plan(_payload(ordinary_day), availability, week_dates)

        self.assertEqual(
            {v["type"] for v in report["violations"]} & {"race_day_conflict", "race_taper_conflict"},
            set(),
        )


class RaceDayFallbackIsLastResort(unittest.IsolatedAsyncioTestCase):
    """The deterministic race/taper template only applies once tier-2 LLM
    repair (which already gets the competition dates via
    availability["competitions"]) has exhausted every attempt -- never as
    the first response."""

    def test_the_fallback_rewrites_a_flagged_race_day_as_a_race_session(self):
        race_day = WorkoutDay(
            date="2026-09-20", day_name_es="Domingo", is_rest=False, sport="run",
            focus="Tirada larga", purpose="Rodaje aerobico continuo en Z2.", adaptation="—",
            total_duration_minutes=70,
            steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=4200,
                                target_type=TargetType.HR_ZONE, target_zone=2, description="70' Z2")],
        )
        payload = _payload(race_day)
        week_dates = [{"date": "2026-09-20", "day_name_es": "Domingo"}]
        violations = [{"date": "2026-09-20", "session_slot": "unica", "type": "race_day_conflict", "detail": "x"}]

        payload, changed = _apply_race_day_fallback(payload, violations, [_MONTERRICO_5K], week_dates)

        self.assertTrue(changed)
        fixed = payload.weeks[0].days[0]
        self.assertIn("competencia", fixed.focus.lower())
        self.assertIn("monterrico", fixed.purpose.lower())

    def test_the_fallback_synthesizes_a_race_day_that_has_no_workoutday_at_all(self):
        """The exact bug found in a real run: the race date never made it
        into the payload (missing_date), so _race_day_violations flagged
        race_day_conflict with no existing day to mutate — the fallback has
        to be able to create one, the same way _apply_missing_date_fallback
        creates a plain rest day for an ordinary missing date."""
        other_day = _week2_good_day()
        payload = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-14", zones_table="z",
            weeks=[WeekBlock(week_number=2, days=[other_day])],
        )
        week_dates = [{"date": "2026-09-20", "day_name_es": "Domingo"}]
        violations = [{"date": "2026-09-20", "session_slot": "unica", "type": "race_day_conflict", "detail": "x"}]

        payload, changed = _apply_race_day_fallback(payload, violations, [_MONTERRICO_5K], week_dates)

        self.assertTrue(changed)
        race_day = next(d for week in payload.weeks for d in week.days if d.date == "2026-09-20")
        self.assertFalse(race_day.is_rest)
        self.assertIn("competencia", race_day.focus.lower())
        self.assertIn("monterrico", race_day.purpose.lower())

    def test_a_race_day_that_became_a_missing_date_rest_day_is_still_flagged(self):
        """Reproduces the real production bug end to end: a race day the LLM
        never filled in got picked up by validate_weekly_plan as missing_date
        AND (after the fix) race_day_conflict, so even if something upstream
        turns it into a plain rest day first, the race check must still
        catch it rather than treat is_rest as "already handled"."""
        rest_day = WorkoutDay(
            date="2026-09-20", day_name_es="Domingo", is_rest=True, sport="rest",
            focus="Descanso", purpose="Dia forzado a descanso.", adaptation="—",
            steps=[], total_duration_minutes=0,
        )
        availability = {**AVAILABILITY, "competitions": [_MONTERRICO_5K]}
        week_dates = [{"date": "2026-09-20", "day_name_es": "Domingo"}]

        report = validate_weekly_plan(_payload(rest_day), availability, week_dates)

        self.assertIn("race_day_conflict", {v["type"] for v in report["violations"]})

    async def test_a_race_day_still_broken_after_repair_attempts_falls_back(self):
        race_day = WorkoutDay(
            date="2026-09-20", day_name_es="Domingo", is_rest=False, sport="run",
            focus="Tirada larga", purpose="Rodaje aerobico continuo en Z2.", adaptation="—",
            total_duration_minutes=70,
            steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=4200,
                                target_type=TargetType.HR_ZONE, target_zone=2, description="70' Z2")],
        )
        payload = _payload(race_day)
        week_dates = [{"date": "2026-09-20", "day_name_es": "Domingo"}]
        availability = {**AVAILABILITY, "competitions": [_MONTERRICO_5K]}

        # Simulates repair that keeps echoing the SAME unfixed day back --
        # never resolves the violation, so both attempts get exhausted.
        stuck_response = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-20", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[race_day.model_copy()])],
        )

        result, report = await validate_and_repair_weekly_plan(
            payload, availability, week_dates, _FakeRepairLLM(stuck_response),
        )

        self.assertIn("competencia", result.weeks[0].days[0].focus.lower())
        self.assertEqual(report["attempts"], 2)


if __name__ == "__main__":
    unittest.main(verbosity=2)
