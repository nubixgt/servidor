"""Plan Maestro v6 Track 7 -- the printed sheet carries the reasoning, not just
the calendar.

The athlete's complaint: the plan they print is *"un mega resumen"*. It was.
Everything below already existed in `weekly_plan.json`, was archived, and was
shown in the app — and none of it reached the page they take with them:

| In the data | On the sheet, before |
|---|---|
| `week.week_rationale` — why THIS week, with their real numbers | absent |
| `day.purpose` — why this session, today, for them | absent |
| `day.manual_instructions` — the whole session for anything Garmin cannot hold | absent |
| `step.target_low/high`, `step.target_zone` — the pace band from their own threshold | only if the model happened to repeat it in prose |
| `day.garmin_exportable` — whether it is on the watch at all | absent |
| totals by discipline | absent |

The last one matters most for what this product is: v5 Track 5 promised that a
plyometric or rehab session Garmin cannot hold is handed to the athlete as
written instructions. If those instructions are not on the printed page, the
athlete never sees them, and the promise breaks exactly where it was made.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.rendering.printable_renderer import (  # noqa: E402
    render_printable_plan,
    step_target,
    week_summary,
)

# This athlete's REAL zones. A swim Z2 and a run Z2 are 28 bpm apart.
REAL_PRESCRIPTION = {
    "hr_zones_by_sport": {
        "swim": {"1": [93, 111], "2": [112, 129], "3": [130, 148], "4": [149, 167], "5": [168, None]},
        "run": {"1": [123, 139], "2": [140, 153], "3": [154, 167], "4": [168, 180], "5": [181, None]},
        "other": {"1": [123, 139], "2": [140, 153], "3": [154, 167], "4": [168, 180], "5": [181, None]},
    },
    "pace_bands": {"vo2max": [312.0, 333.0], "umbral_tempo": [340.0, 355.0]},
}


def _day(**fields):
    base = dict(
        date="2026-08-31", day_name_es="Lunes", is_rest=False, sport="run", focus="VO2max",
        purpose="Introducción ordenada a la intensidad para el 5k.",
        adaptation="Si la ingle izquierda molesta, aborta las series.",
        total_duration_minutes=35.0, intensity_class="vo2max", discipline="carrera", steps=[],
    )
    base.update(fields)
    return WorkoutDay(**base)


def _payload(days):
    return WeeklyPlanPayload(
        athlete_name="Jose", start_date=days[0].date, zones_table="",
        weeks=[WeekBlock(week_number=1, days=days, week_rationale=(
            "Semana de transición para asimilar el 21k del 23 de agosto, que fue un salto de 2.1 veces."))],
    )


def _render(days, prescription=REAL_PRESCRIPTION):
    return render_printable_plan(_payload(days), prescription, generated_at="2026-08-26")


class TheZoneShownIsTheZoneForTHATSPORT(unittest.TestCase):
    """The bug this file caught while being written, live, on the athlete's own
    plan: a RUNNING step printed `Z1 (93–111 ppm)` -- the SWIM table. The first
    draft took whichever sport came first in the dict.

    CLAUDE.md already records this exact mistake shipping once through the
    extractor. It must not ship again through the renderer."""

    def _step(self, zone=2):
        return WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=1800,
                           target_type="hr_zone", target_zone=zone, description="30' Z2")

    def test_a_run_gets_the_run_table(self):
        self.assertEqual(step_target(self._step(), REAL_PRESCRIPTION, "run"), "Z2 (140–153 ppm)")

    def test_a_swim_gets_the_swim_table(self):
        self.assertEqual(step_target(self._step(), REAL_PRESCRIPTION, "swim"), "Z2 (112–129 ppm)")

    def test_a_sport_with_no_table_of_its_own_falls_back_to_other(self):
        self.assertEqual(step_target(self._step(), REAL_PRESCRIPTION, "bike"), "Z2 (140–153 ppm)")

    def test_with_no_zones_at_all_it_prints_the_zone_and_invents_nothing(self):
        self.assertEqual(step_target(self._step(), {}, "run"), "Z2")

    def test_en_un_brick_manda_el_tramo_no_el_dia(self):
        """El deporte del DIA de un brick es "brick", que no tiene tabla y cae
        en "other". Pero cada paso sabe de que tramo es, y en la mayoria de
        los atletas la FC pedaleando no es la de correr.

        Aqui se ve con la natacion, que es donde la diferencia es visible en
        este atleta: 28 ppm entre su Z2 nadando y su Z2 corriendo."""
        paso = self._step()
        paso.leg_sport = "swim"
        self.assertEqual(step_target(paso, REAL_PRESCRIPTION, "brick"), "Z2 (112–129 ppm)")

    def test_sin_tramo_declarado_se_sigue_usando_el_deporte_del_dia(self):
        self.assertEqual(step_target(self._step(), REAL_PRESCRIPTION, "swim"), "Z2 (112–129 ppm)")

    def test_the_rendered_page_never_mixes_them(self):
        """Checked inside each DAY block, not across the whole page: the
        reference table at the top legitimately lists every sport's zones."""
        import re

        html = _render([
            _day(sport="run", discipline="carrera", steps=[self._step()]),
            _day(date="2026-09-01", sport="swim", discipline="natacion", focus="Técnica",
                 steps=[self._step()]),
        ])
        days = re.findall(r'<div class="day">(.*?)<div class="adaptation"', html, re.S)
        self.assertEqual(len(days), 2)
        run_day, swim_day = days
        self.assertIn("Carrera", run_day)
        self.assertIn("140–153 ppm", run_day)
        self.assertNotIn("93–111 ppm", run_day)
        self.assertIn("Natación", swim_day)
        self.assertIn("112–129 ppm", swim_day)
        self.assertNotIn("140–153 ppm", swim_day)


class TheRealNumbersReachThePage(unittest.TestCase):
    def test_a_pace_band_is_rendered_from_the_structured_fields(self):
        """Not from the prose. The band the app computed from the athlete's
        real threshold used to reach the page only if the model repeated it."""
        step = WorkoutStep(step_order=1, step_type="interval", duration_type="distance",
                           duration_value=400, target_type="pace", target_low=312, target_high=333,
                           description="400m fuerte")
        self.assertEqual(step_target(step, REAL_PRESCRIPTION, "run"), "5:12–5:33/km")

    def test_power_is_rendered_in_watts(self):
        step = WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=1200,
                           target_type="power", target_low=180, target_high=210, description="20' tempo")
        self.assertEqual(step_target(step, REAL_PRESCRIPTION, "bike"), "180–210 W")

    def test_a_by_feel_step_shows_no_invented_target(self):
        """`intensity_class="neuromuscular"` exists precisely so a 15-second
        stride is not given a pace band."""
        step = WorkoutStep(step_order=1, step_type="interval", duration_type="time", duration_value=20,
                           target_type="none", description='20" relajado y rápido')
        self.assertEqual(step_target(step, REAL_PRESCRIPTION, "run"), "")


class TheSheetSaysWHYNotJustWHAT(unittest.TestCase):
    def test_the_weeks_rationale_stays_in_the_app(self):
        """It was added in v6 (the one thing missing from the page). Since 2026-10-22 the sheet is ONE page with
        only the training week (el responsable: «no va con las zonas, mejor que sea una sola página sólo con la
        semana»); the why lives in the app, in Plan and in Historial."""
        html = _render([_day()])
        self.assertNotIn("salto de 2.1 veces", html)

    def test_each_sessions_purpose_is_on_the_page(self):
        self.assertIn("Introducción ordenada", _render([_day()]))

    def test_the_adaptation_survives(self):
        self.assertIn("aborta las series", _render([_day()]))


class ASessionTheWatchCannotHoldIsPrintedInFull(unittest.TestCase):
    """v5 Track 5's promise: what Garmin cannot carry is handed to the athlete
    as written instructions. On paper is where that promise is kept or broken."""

    def _rehab_day(self):
        return _day(
            sport="strength", discipline="rehab", focus="Rehab cadera", garmin_exportable=False,
            manual_instructions="3x12 puente de glúteo\n3x30\" plancha lateral\n2x15 aductor isométrico",
            steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time",
                               duration_value=1800, description="30' rehab")],
        )

    def test_the_written_session_is_on_the_sheet(self):
        html = _render([self._rehab_day()])
        self.assertIn("puente de glúteo", html)
        self.assertIn("plancha lateral", html)

    def test_it_is_labelled_as_not_being_on_the_watch(self):
        self.assertIn("No va al reloj", _render([self._rehab_day()]))

    def test_the_week_overview_counts_them(self):
        summary = week_summary(_payload([self._rehab_day(), _day()]).weeks[0])
        self.assertEqual(summary["off_watch"], 1)

    def test_a_normal_session_carries_no_such_warning(self):
        self.assertNotIn("No va al reloj", _render([_day()]))


class TheWeekIsSummarisedAtAGlance(unittest.TestCase):
    """What Runna's 'plan overview' gives -- the week at a glance -- plus the
    part no run-only competitor has to solve: the split across disciplines."""

    def test_it_totals_time_and_sessions(self):
        summary = week_summary(_payload([
            _day(total_duration_minutes=45.0),
            _day(date="2026-09-01", total_duration_minutes=35.0),
            _day(date="2026-09-02", is_rest=True, sport="rest", discipline="descanso",
                 intensity_class="rest", total_duration_minutes=None),
        ]).weeks[0])
        self.assertEqual(summary["sessions"], 2)
        self.assertEqual(summary["rest_days"], 1)
        self.assertEqual(summary["total"], "1h 20m")

    def test_it_splits_by_discipline_not_by_garmin_sport(self):
        """A rehab session and a strength session are both `sport="strength"`
        to Garmin and completely different things to a coach."""
        summary = week_summary(_payload([
            _day(sport="strength", discipline="fuerza", total_duration_minutes=45.0),
            _day(date="2026-09-01", sport="strength", discipline="rehab", total_duration_minutes=20.0),
            _day(date="2026-09-02", sport="run", discipline="carrera", total_duration_minutes=50.0),
        ]).weeks[0])
        self.assertEqual(dict(summary["by_discipline"]), {"Carrera": 50.0, "Fuerza": 45.0, "Rehab": 20.0})

    def test_a_week_of_only_rest_does_not_crash(self):
        summary = week_summary(_payload([
            _day(is_rest=True, sport="rest", discipline="descanso", intensity_class="rest",
                 total_duration_minutes=None),
        ]).weeks[0])
        self.assertEqual(summary["sessions"], 0)


if __name__ == "__main__":
    unittest.main(verbosity=2)
