"""Tests for the pure-logic helpers behind Plan Maestro v4 Track B (weekly
rolling generation): where the next 7-day window starts, and how a freshly
generated week gets appended onto the athlete's running weekly_plan.json.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
)
from web_app.server import _append_week, _next_week_start  # noqa: E402


def _day(date: str, week_day_es: str = "Lunes") -> WorkoutDay:
    return WorkoutDay(
        date=date, day_name_es=week_day_es, is_rest=False, sport="run",
        focus="Facil", purpose="Rodaje.", adaptation="-",
        total_duration_minutes=30, steps=[],
    )


def _week_payload(week_number: int, dates: list[str], start_date: str = "2026-09-02") -> WeeklyPlanPayload:
    return WeeklyPlanPayload(
        athlete_name="Test", start_date=start_date, zones_table="z",
        weeks=[WeekBlock(week_number=week_number, days=[_day(d) for d in dates])],
    )


class NextWeekStart(unittest.TestCase):
    def test_no_existing_plan_starts_today(self):
        now = datetime(2026, 9, 2)
        self.assertEqual(_next_week_start(None, now), now.date())

    def test_starts_the_day_after_the_last_existing_date(self):
        existing = _week_payload(1, ["2026-09-02", "2026-09-03", "2026-09-08"])
        now = datetime(2026, 9, 3)
        self.assertEqual(_next_week_start(existing, now).isoformat(), "2026-09-09")

    def test_clamps_forward_to_today_if_the_athlete_fell_behind(self):
        # Last existing date is 3 weeks in the past relative to "now".
        existing = _week_payload(1, ["2026-08-10"])
        now = datetime(2026, 9, 3)
        self.assertEqual(_next_week_start(existing, now), now.date())

    def test_an_existing_plan_with_no_days_starts_today(self):
        existing = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[])],
        )
        now = datetime(2026, 9, 3)
        self.assertEqual(_next_week_start(existing, now), now.date())

    def test_clamps_back_to_today_if_the_latest_date_is_implausibly_far_ahead(self):
        # The real bug: a since-fixed validator bug synthesized race-day
        # entries for competitions MONTHS away and stapled them onto week 1
        # (see _race_day_violations' expected_dates gating) -- the next
        # "Generar Semana" click read 2027-08-20 as "the latest date" and
        # started the new week over a year in the future instead of the
        # following real week.
        existing = _week_payload(1, ["2026-08-24", "2026-08-30", "2027-08-20"])
        now = datetime(2026, 8, 24)
        self.assertEqual(_next_week_start(existing, now), now.date())

    def test_a_realistic_multiweek_gap_is_not_treated_as_corrupted(self):
        # A genuinely stale but plausible last date (a few weeks behind) must
        # still just clamp forward to today, not be flagged as corrupted.
        existing = _week_payload(1, ["2026-07-01"])
        now = datetime(2026, 8, 24)
        self.assertEqual(_next_week_start(existing, now), now.date())


class AppendWeek(unittest.TestCase):
    def test_first_week_ever_becomes_week_1(self):
        new_week = _week_payload(1, ["2026-09-02"], start_date="2026-09-02")

        merged = _append_week(None, new_week)

        self.assertEqual(len(merged.weeks), 1)
        self.assertEqual(merged.weeks[0].week_number, 1)
        self.assertEqual(merged.start_date, "2026-09-02")

    def test_a_second_week_is_appended_and_renumbered(self):
        # run_week_generation always returns week_number=1 for its own
        # isolated 1-week payload -- _append_week must renumber it to its
        # real position (2), not just tack on another "week 1".
        existing = _week_payload(1, ["2026-09-02"], start_date="2026-09-02")
        new_week = _week_payload(1, ["2026-09-09"], start_date="2026-09-09")

        merged = _append_week(existing, new_week)

        self.assertEqual(len(merged.weeks), 2)
        self.assertEqual([w.week_number for w in merged.weeks], [1, 2])
        # The original week's own content is untouched.
        self.assertEqual(merged.weeks[0].days[0].date, "2026-09-02")
        self.assertEqual(merged.weeks[1].days[0].date, "2026-09-09")
        # start_date anchors to the FIRST week ever generated, not the latest.
        self.assertEqual(merged.start_date, "2026-09-02")

    def test_the_weeks_rationale_survives_being_renumbered(self):
        """_append_week used to rebuild the WeekBlock from its `days` alone,
        which silently drops every field the constructor is not handed --
        exactly how the week's explanation would disappear the moment it got
        renumbered into position 2."""
        existing = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[_day("2026-09-02")], week_rationale="por qué la 1")],
        )
        new_week = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-09", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[_day("2026-09-09")], week_rationale="por qué la 2")],
        )

        merged = _append_week(existing, new_week)

        self.assertEqual(merged.weeks[0].week_rationale, "por qué la 1")
        self.assertEqual(merged.weeks[1].week_rationale, "por qué la 2")
        self.assertEqual(merged.weeks[1].week_number, 2)

    def test_the_very_first_weeks_rationale_is_kept_too(self):
        new_week = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[_day("2026-09-02")], week_rationale="por qué")],
        )
        self.assertEqual(_append_week(None, new_week).weeks[0].week_rationale, "por qué")

    def test_a_third_week_keeps_renumbering_sequentially(self):
        existing = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[
                WeekBlock(week_number=1, days=[_day("2026-09-02")]),
                WeekBlock(week_number=2, days=[_day("2026-09-09")]),
            ],
        )
        new_week = _week_payload(1, ["2026-09-16"], start_date="2026-09-16")

        merged = _append_week(existing, new_week)

        self.assertEqual([w.week_number for w in merged.weeks], [1, 2, 3])


if __name__ == "__main__":
    unittest.main(verbosity=2)
