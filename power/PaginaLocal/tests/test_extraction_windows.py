"""Tests for Plan Maestro v5 Track 12: the data window is a decision the app
makes for a stated reason, not a number the athlete guesses.

The two profile inputs ("Días de actividades" 90, "Días de métricas
fisiológicas" 56) were a technical decision dressed up as a preference. They
also hid a live bug:

    build_volume_baseline asked _compute_recent_actual_weekly_hours for 28 days
    and divided by 4, while run_week_generation_task only fetched 21 days of
    activities (server.py:1487). The chronic baseline was averaged over a
    window that had never been fetched, came out understated by up to 25%, and
    is exactly what anchors the weekly volume target (v4 Track F). Every week
    generated was being planned against a number that was too low.

The load-bearing regression guard is the first test class: extraction window
>= load window, asserted structurally so the two can never drift apart again.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import datetime, timedelta
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.garmin.extraction_windows import (  # noqa: E402
    ACUTE_LOAD_WINDOW_DAYS,
    CHRONIC_LOAD_WINDOW_DAYS,
    SEASON_WINDOW,
    WEEK_WINDOW,
)
from web_app.server import build_volume_baseline  # noqa: E402


def _activity(days_ago: float, minutes: float) -> SimpleNamespace:
    start = datetime.now() - timedelta(days=days_ago)
    return SimpleNamespace(
        start_time=start.isoformat(),
        summary=SimpleNamespace(duration=minutes * 60.0),
    )


def _garmin(activities) -> SimpleNamespace:
    return SimpleNamespace(recent_activities=activities)


class TheFetchedWindowAlwaysCoversTheComputedOne(unittest.TestCase):
    def test_the_week_fetch_reaches_at_least_as_far_back_as_the_chronic_window(self):
        """THE regression guard. If someone shortens WEEK_WINDOW to save
        tokens, chronic weekly hours silently drops and every volume target
        with it -- with no error anywhere."""
        self.assertGreaterEqual(WEEK_WINDOW.activities_days, CHRONIC_LOAD_WINDOW_DAYS)

    def test_it_carries_slack_past_the_boundary_rather_than_matching_exactly(self):
        """Activities are fetched by calendar date range while the load
        computation filters on each activity's own timestamp, so an
        exactly-equal window can clip its own oldest day."""
        self.assertGreater(WEEK_WINDOW.activities_days, CHRONIC_LOAD_WINDOW_DAYS)

    def test_the_season_fetch_also_covers_the_chronic_window(self):
        self.assertGreaterEqual(SEASON_WINDOW.activities_days, CHRONIC_LOAD_WINDOW_DAYS)

    def test_the_season_window_is_the_longer_of_the_two(self):
        self.assertGreater(SEASON_WINDOW.activities_days, WEEK_WINDOW.activities_days)
        self.assertGreater(SEASON_WINDOW.metrics_days, WEEK_WINDOW.metrics_days)


class TheWindowsAreAnchoredToWhatTheyAreFor(unittest.TestCase):
    def test_chronic_is_four_weeks_and_acute_is_one(self):
        self.assertEqual(CHRONIC_LOAD_WINDOW_DAYS, 28)
        self.assertEqual(ACUTE_LOAD_WINDOW_DAYS, 7)
        self.assertEqual(CHRONIC_LOAD_WINDOW_DAYS % 7, 0)

    def test_a_year_of_activities_is_not_fetched_for_a_macrocycle(self):
        """Beyond roughly six months, detraining and life changes make old
        sessions weak evidence about current capacity -- and every extra
        activity is paid for in summarizer tokens."""
        self.assertLessEqual(SEASON_WINDOW.activities_days, 200)

    def test_every_window_states_its_reasoning(self):
        for window in (SEASON_WINDOW, WEEK_WINDOW):
            with self.subTest(window=window):
                self.assertTrue(window.rationale.strip())

    def test_the_windows_are_immutable(self):
        """A frozen dataclass so no caller can quietly mutate a shared default
        for its own run."""
        with self.assertRaises(Exception):
            WEEK_WINDOW.activities_days = 7


class TheChronicAverageMatchesTheWindowItClaims(unittest.TestCase):
    def test_four_equal_weeks_average_to_one_of_them(self):
        """4 weeks x 5h = 20h over 28 days must read as 5.0 h/week."""
        activities = [_activity(days_ago=d, minutes=300 / 3) for d in (2, 4, 6, 9, 11, 13, 16, 18, 20, 23, 25, 27)]
        baseline = build_volume_baseline({}, _garmin(activities), None)

        expected = (12 * 100 / 60) / (CHRONIC_LOAD_WINDOW_DAYS / 7.0)
        self.assertAlmostEqual(baseline["chronic_weekly_hours"], round(expected, 1), places=1)

    def test_the_window_it_used_is_reported_not_assumed(self):
        baseline = build_volume_baseline({}, _garmin([_activity(3, 60)]), None)
        self.assertEqual(baseline["chronic_window_days"], CHRONIC_LOAD_WINDOW_DAYS)

    def test_activities_older_than_the_chronic_window_do_not_count(self):
        recent = build_volume_baseline({}, _garmin([_activity(3, 60)]), None)["chronic_weekly_hours"]
        with_old = build_volume_baseline(
            {}, _garmin([_activity(3, 60), _activity(CHRONIC_LOAD_WINDOW_DAYS + 5, 600)]), None
        )["chronic_weekly_hours"]
        self.assertEqual(recent, with_old)

    def test_the_acute_reading_only_counts_the_last_seven_days(self):
        baseline = build_volume_baseline(
            {}, _garmin([_activity(2, 60), _activity(20, 600)]), None
        )
        self.assertAlmostEqual(baseline["last_7d_actual_hours"], 1.0, places=1)

    def test_the_understatement_this_track_fixes_is_reproducible(self):
        """With the old 21-day fetch, the four oldest days of a 28-day block
        were simply absent from the payload while the divisor stayed 4. Same
        training, ~25% lower chronic -- and that number anchors the volume
        target of every generated week."""
        full = [_activity(days_ago=d, minutes=60) for d in range(1, CHRONIC_LOAD_WINDOW_DAYS)]
        truncated = [a for a in full if a.start_time >= (datetime.now() - timedelta(days=21)).isoformat()]

        chronic_full = build_volume_baseline({}, _garmin(full), None)["chronic_weekly_hours"]
        chronic_truncated = build_volume_baseline({}, _garmin(truncated), None)["chronic_weekly_hours"]

        self.assertLess(chronic_truncated, chronic_full)
        self.assertGreater((chronic_full - chronic_truncated) / chronic_full, 0.15)

    def test_an_athlete_with_no_recent_activity_gets_none_not_zero(self):
        """None means "no data to anchor to" and makes compute_weekly_volume_target
        stand down; 0.0 would read as "trained nothing" and cap the next week
        at nothing."""
        self.assertIsNone(build_volume_baseline({}, _garmin([]), None)["chronic_weekly_hours"])


class TheAthleteIsNoLongerAskedToGuess(unittest.TestCase):
    def test_the_number_inputs_are_gone_from_the_form(self):
        html = (Path(__file__).resolve().parents[1] / "web_app/static/index.html").read_text(encoding="utf-8")
        self.assertNotIn('id="activities_days"', html)
        self.assertNotIn('id="metrics_days"', html)

    def test_the_form_explains_what_is_read_instead(self):
        html = (Path(__file__).resolve().parents[1] / "web_app/static/index.html").read_text(encoding="utf-8")
        self.assertIn("Qué historial lee tu entrenador", html)

    def test_nothing_reads_a_saved_window_off_the_profile_any_more(self):
        """Values already stored on existing profiles are ignored rather than
        migrated: they were guesses, and honouring them would keep the bug
        alive for exactly the athletes who already have a profile."""
        server_src = (Path(__file__).resolve().parents[1] / "web_app/server.py").read_text(encoding="utf-8")
        self.assertNotIn('profile_dict.get("activities_days"', server_src)
        self.assertNotIn('profile_dict.get("week_activities_days"', server_src)


if __name__ == "__main__":
    unittest.main(verbosity=2)
