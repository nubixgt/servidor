"""Tests that the deterministic rules protect the athlete WITHOUT narrowing
what a good coach may prescribe.

A safety rule that misfires does not just annoy: it gets "repaired", and the
repair rewrites the session. So every rule that misclassifies a legitimate
session is a rule that silently deletes a coaching decision. The sessions
pinned here are all routine tools of a competent endurance coach that the
previous keyword-based classifier swept into "quality work that must carry a
pace target":

- strides / hill sprints / drills, run by feel, where a pace band for a
  15-second effort is meaningless;
- run/walk blocks, structurally intervals but deliberately EASY -- forcing a
  quality pace band there turns base building into a hard session, which is
  exactly the failure mode of a rule that outranks the coach;
- over-unders and structured fartleks, where the easier half is work, not
  rest, and inserting a recovery step destroys the session;
- swim and bike work, which was being range-checked against pace bands
  derived from the athlete's RUNNING threshold.

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
from services.ai.langgraph.validation.plan_validator import validate_weekly_plan  # noqa: E402

PRESCRIPTION = {
    "pace_bands": {
        "repeticiones": [291.6, 312.5], "vo2max": [312.5, 333.3],
        "umbral_tempo": [340.3, 368.0], "maraton": [412.8, 433.8],
        "facil": [421.2, 471.7], "recuperacion": [463.3, 526.5],
    },
    "power_bands": {"umbral": [259, 299], "vo2max": [302, 342]},
    "card": "### Ficha",
}
WEEK_DATES = [{"date": "2026-09-02", "day_name_es": "Miércoles"}]
AVAILABILITY = {
    "days": {"Miércoles": {"available": True, "hours": 3, "sports": None, "blocks": []}},
    "weekly_hours_budget": None,
    "prescription": PRESCRIPTION,
}


def _day(steps, *, sport="run", focus="Sesión", purpose="Trabajo.", intensity_class=None):
    return WorkoutDay(
        date="2026-09-02", day_name_es="Miércoles", is_rest=False, sport=sport,
        focus=focus, purpose=purpose, adaptation="Si duele: para.",
        total_duration_minutes=45, steps=steps, intensity_class=intensity_class,
    )


def _violations(day, availability=None, week_dates=None):
    report = validate_weekly_plan(
        WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[day])],
        ),
        availability or AVAILABILITY,
        week_dates or WEEK_DATES,
    )
    return {v["type"] for v in report["violations"]}


def _warmup():
    return WorkoutStep(
        step_order=1, step_type="warmup", duration_type="time", duration_value=600,
        target_type=TargetType.HR_ZONE, target_zone=1, description="10' Z1",
    )


def _effort(description, seconds=20):
    """A by-feel effort: no target, instruction lives in the description."""
    return WorkoutStep(
        step_order=1, step_type="interval", duration_type="time", duration_value=seconds,
        target_type=TargetType.NONE, description=description,
    )


def _recovery(seconds=60):
    return WorkoutStep(
        step_order=2, step_type="recovery", duration_type="time", duration_value=seconds,
        target_type=TargetType.HR_ZONE, target_zone=1, description="recuperación",
    )


def _paced(low, high, seconds=300):
    return WorkoutStep(
        step_order=1, step_type="interval", duration_type="time", duration_value=seconds,
        target_type=TargetType.PACE, target_low=low, target_high=high,
        description=f"{seconds // 60}' a ritmo",
    )


def _repeat(substeps, count=6):
    return WorkoutStep(
        step_order=2, step_type="repeat", repeat_count=count,
        description=f"{count}x(...)", substeps=substeps,
    )


class ByFeelWorkIsLegitimate(unittest.TestCase):
    def test_strides_after_an_easy_run_need_no_pace_band(self):
        """6x20\" relaxed accelerations. Prescribing a pace band for a
        15-20 second effort is meaningless; coaches use these constantly."""
        day = _day(
            [_warmup(), _repeat([_effort('20" relajado y rápido'), _recovery()])],
            focus="Rodaje + rectas", purpose="Trabajo neuromuscular ligero al final del rodaje.",
            intensity_class="neuromuscular",
        )
        self.assertNotIn("quality_without_pace", _violations(day))

    def test_hill_sprints_need_no_pace_band(self):
        """Pace up a hill is not a meaningful target."""
        day = _day(
            [_warmup(), _repeat([_effort('10" cuesta máximo', seconds=10), _recovery(120)])],
            focus="Cuestas cortas", purpose="Potencia y reclutamiento en cuesta.",
            intensity_class="neuromuscular",
        )
        self.assertNotIn("quality_without_pace", _violations(day))

    def test_a_walk_run_block_is_not_treated_as_quality_work(self):
        """Structurally intervals, but the whole point is staying easy. This
        is the case that most directly harms an athlete if misclassified:
        base building would be rewritten into a hard session."""
        run_easy = WorkoutStep(
            step_order=1, step_type="interval", duration_type="time", duration_value=180,
            target_type=TargetType.HR_ZONE, target_zone=2, description="3' trote suave Z2",
        )
        walk = WorkoutStep(
            step_order=2, step_type="recovery", duration_type="time", duration_value=120,
            target_type=TargetType.HR_ZONE, target_zone=1, description="2' caminata",
        )
        day = _day(
            [_warmup(), _repeat([run_easy, walk], count=6)],
            focus="Trote-caminata", purpose="Reintroducir impacto de forma controlada.",
            intensity_class="walk_run",
        )
        self.assertNotIn("quality_without_pace", _violations(day))


class RealQualityWorkIsStillProtected(unittest.TestCase):
    """The rules must still catch the defect they were written for."""

    def test_a_threshold_session_by_heart_rate_is_still_flagged(self):
        hr_work = WorkoutStep(
            step_order=1, step_type="interval", duration_type="time", duration_value=480,
            target_type=TargetType.HR_ZONE, target_zone=4, description="8' Z4",
        )
        day = _day(
            [_warmup(), _repeat([hr_work, _recovery(180)], count=3)],
            focus="Umbral", purpose="Bloques a ritmo de umbral.",
            intensity_class="tempo_threshold",
        )
        self.assertIn("quality_without_pace", _violations(day))

    def test_identical_reps_with_no_recovery_are_still_flagged(self):
        day = _day(
            [_warmup(), _repeat([_paced(340, 355)], count=5)],
            focus="Series", purpose="Series a umbral.", intensity_class="tempo_threshold",
        )
        self.assertIn("intervals_without_recovery", _violations(day))


class AlternatingSetsAreNotMissingRecovery(unittest.TestCase):
    def test_an_over_under_set_is_accepted(self):
        """4x(2' at threshold + 2' at marathon pace). The easier half is
        work, not rest -- inserting a recovery destroys the session."""
        over = _paced(340, 355, seconds=120)
        under = WorkoutStep(
            step_order=2, step_type="interval", duration_type="time", duration_value=120,
            target_type=TargetType.PACE, target_low=412, target_high=433,
            description="2' a ritmo maratón",
        )
        day = _day(
            [_warmup(), _repeat([over, under], count=4)],
            focus="Over-under", purpose="Alternar por encima y por debajo del umbral.",
            intensity_class="tempo_threshold",
        )
        self.assertNotIn("intervals_without_recovery", _violations(day))


class PaceRangeIsRunningOnly(unittest.TestCase):
    def test_a_swim_step_is_not_range_checked_against_running_bands(self):
        """Swim pace is seconds per 100m; the athlete's bands are seconds per
        km derived from running. Comparing them flags a correct session."""
        swim_work = WorkoutStep(
            step_order=1, step_type="interval", duration_type="distance", duration_value=100,
            target_type=TargetType.PACE, target_low=110, target_high=120,
            description="100m fuerte",
        )
        day = _day(
            [_repeat([swim_work, _recovery(30)], count=8)],
            sport="swim", focus="Series", purpose="Series de natación.",
            intensity_class="tempo_threshold",
        )
        self.assertNotIn("pace_out_of_range", _violations(day))

    def test_an_impossible_running_pace_is_still_flagged(self):
        # 3:20/km: a plausible pace in the abstract (so the schema's own unit
        # guard lets it through) but far outside anything this athlete's bands
        # support -- exactly what this rule exists to catch.
        day = _day(
            [_warmup(), _repeat([_paced(200, 210), _recovery()], count=3)],
            focus="Series", purpose="Series.", intensity_class="vo2max",
        )
        self.assertIn("pace_out_of_range", _violations(day))


class TaperKeepsStridesButNotHardWork(unittest.TestCase):
    """Keeping strides through a taper is standard practice to stay sharp; a
    rule that stripped them out of race week would be bad coaching."""

    # La vispera: desde 2026-09-11 la regla solo mira el dia antes de la carrera.
    COMPETITION = {"name": "5K", "date": "2026-09-03", "race_type": "run", "priority": "b"}
    TAPER_WEEK = [{"date": "2026-09-02", "day_name_es": "Miércoles"}]

    def _availability(self):
        return {**AVAILABILITY, "competitions": [self.COMPETITION]}

    def test_strides_inside_the_taper_window_are_allowed(self):
        day = _day(
            [_warmup(), _repeat([_effort('20" rectas'), _recovery()])],
            focus="Rectas", purpose="Mantener frescura antes de la carrera.",
            intensity_class="neuromuscular",
        )
        self.assertNotIn("race_taper_conflict", _violations(day, self._availability(), self.TAPER_WEEK))

    def test_a_full_intensity_session_inside_the_taper_window_is_flagged(self):
        day = _day(
            [_warmup(), _repeat([_paced(340, 355), _recovery(180)], count=5)],
            focus="Umbral", purpose="Bloques a umbral.", intensity_class="tempo_threshold",
        )
        self.assertIn("race_taper_conflict", _violations(day, self._availability(), self.TAPER_WEEK))


class LegacyPlansKeepWorking(unittest.TestCase):
    def test_a_day_without_a_declared_class_falls_back_to_the_keyword_heuristic(self):
        """Plans stored before intensity_class existed have None and must
        still be validated, not silently waved through."""
        hr_work = WorkoutStep(
            step_order=1, step_type="interval", duration_type="time", duration_value=480,
            target_type=TargetType.HR_ZONE, target_zone=4, description="8' Z4",
        )
        day = _day(
            [_warmup(), _repeat([hr_work, _recovery(180)], count=3)],
            focus="Umbral", purpose="Bloques a ritmo de umbral.", intensity_class=None,
        )
        self.assertIn("quality_without_pace", _violations(day))


if __name__ == "__main__":
    unittest.main(verbosity=2)
