"""Tests for matching what was prescribed against what the athlete really did.

The coach could see what it had prescribed but never what became of it, so it
kept prescribing into a void while the athlete quietly diverged. These tests
pin down the matching, and especially the places where being wrong would be
worse than saying nothing: calling a session "missed" that the athlete
actually did, or judging one they haven't had the chance to do yet.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.adherence import (  # noqa: E402
    build_adherence_report,
    render_adherence_block,
)
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
)

TODAY = date(2026, 9, 10)


def _activity(activity_id, day, activity_type, minutes, distance_m=None, avg_hr=None, name="Sesión"):
    return SimpleNamespace(
        activity_id=activity_id,
        activity_type=activity_type,
        activity_name=name,
        start_time=f"{day} 06:00:00",
        summary=SimpleNamespace(duration=minutes * 60, distance=distance_m, average_hr=avg_hr),
    )


def _day(day, sport="run", minutes=45.0, focus="Base", is_rest=False, slot="unica"):
    return WorkoutDay(
        date=day, day_name_es="X", is_rest=is_rest, sport=sport, focus=focus,
        purpose="p", adaptation="a", total_duration_minutes=minutes, session_slot=slot, steps=[],
    )


def _plan(days, start="2026-09-01"):
    return WeeklyPlanPayload(
        athlete_name="Test", start_date=start, zones_table="z",
        weeks=[WeekBlock(week_number=1, days=days)],
    )


def _report(days, activities, start="2026-09-01"):
    return build_adherence_report(_plan(days, start), activities, today=TODAY)


class Matching(unittest.TestCase):
    def test_a_session_done_at_the_prescribed_length_is_completed(self):
        report = _report([_day("2026-09-08", minutes=45)],
                         [_activity(1, "2026-09-08", "running", 46)])
        self.assertEqual(report.entries[0].status, "completed")

    def test_a_much_longer_session_counts_as_modified(self):
        report = _report([_day("2026-09-08", minutes=40)],
                         [_activity(1, "2026-09-08", "running", 70)])
        self.assertEqual(report.entries[0].status, "modified")

    def test_a_much_shorter_session_counts_as_modified(self):
        report = _report([_day("2026-09-08", minutes=60)],
                         [_activity(1, "2026-09-08", "running", 25)])
        self.assertEqual(report.entries[0].status, "modified")

    def test_small_drift_is_not_called_modified(self):
        """Athletes round to the nearest lap of the park, not to the minute."""
        for actual in (38, 45, 52):
            with self.subTest(actual=actual):
                report = _report([_day("2026-09-08", minutes=45)],
                                 [_activity(1, "2026-09-08", "running", actual)])
                self.assertEqual(report.entries[0].status, "completed")

    def test_nothing_on_that_day_is_missed(self):
        report = _report([_day("2026-09-08")], [])
        self.assertEqual(report.entries[0].status, "missed")

    def test_a_different_sport_that_day_does_not_count_as_the_session(self):
        report = _report([_day("2026-09-08", sport="run")],
                         [_activity(1, "2026-09-08", "lap_swimming", 45)])
        self.assertEqual(report.entries[0].status, "missed")
        self.assertEqual(len(report.unplanned), 1)

    def test_the_real_numbers_are_carried_through(self):
        report = _report([_day("2026-09-08", minutes=45)],
                         [_activity(1, "2026-09-08", "running", 46, distance_m=8200, avg_hr=142)])
        entry = report.entries[0]
        self.assertEqual(entry.actual_minutes, 46.0)
        self.assertEqual(entry.actual_distance_km, 8.2)
        self.assertEqual(entry.actual_avg_hr, 142)
        self.assertEqual(entry.activity_id, 1)


class NotJudgingWhatHasNotHappenedYet(unittest.TestCase):
    def test_todays_session_is_pending_not_missed(self):
        """The athlete may still be about to train."""
        report = _report([_day(TODAY.isoformat())], [])
        self.assertEqual(report.entries[0].status, "pending")

    def test_future_sessions_are_pending(self):
        report = _report([_day("2026-09-20")], [])
        self.assertEqual(report.entries[0].status, "pending")

    def test_pending_sessions_are_excluded_from_the_percentage(self):
        report = _report([_day("2026-09-08"), _day("2026-09-20")],
                         [_activity(1, "2026-09-08", "running", 45)])
        self.assertEqual(len(report.evaluated), 1)
        self.assertEqual(report.adherence_pct, 100.0)

    def test_rest_days_are_not_sessions(self):
        report = _report([_day("2026-09-08", is_rest=True)], [])
        self.assertEqual(report.entries, [])


class TwoADays(unittest.TestCase):
    def test_each_activity_is_used_once(self):
        report = _report(
            [_day("2026-09-08", sport="run", minutes=30, slot="am"),
             _day("2026-09-08", sport="run", minutes=60, slot="pm")],
            [_activity(1, "2026-09-08", "running", 31), _activity(2, "2026-09-08", "running", 62)],
        )
        self.assertEqual([e.status for e in report.entries], ["completed", "completed"])
        self.assertNotEqual(report.entries[0].activity_id, report.entries[1].activity_id)

    def test_sessions_pair_with_the_closest_duration_not_the_first_found(self):
        report = _report(
            [_day("2026-09-08", sport="run", minutes=30, slot="am"),
             _day("2026-09-08", sport="run", minutes=90, slot="pm")],
            [_activity(1, "2026-09-08", "running", 92), _activity(2, "2026-09-08", "running", 29)],
        )
        self.assertEqual(report.entries[0].activity_id, 2)  # the 29' one
        self.assertEqual(report.entries[1].activity_id, 1)  # the 92' one

    def test_only_one_activity_for_two_sessions_leaves_the_other_missed(self):
        report = _report(
            [_day("2026-09-08", sport="run", minutes=30, slot="am"),
             _day("2026-09-08", sport="run", minutes=60, slot="pm")],
            [_activity(1, "2026-09-08", "running", 31)],
        )
        self.assertEqual(sorted(e.status for e in report.entries), ["completed", "missed"])


class SportMapping(unittest.TestCase):
    def test_strength_matches_garmins_strength_training(self):
        report = _report([_day("2026-09-08", sport="strength", minutes=40)],
                         [_activity(1, "2026-09-08", "strength_training", 42)])
        self.assertEqual(report.entries[0].status, "completed")

    def test_strength_still_matches_a_gym_session_garmin_did_not_label(self):
        """La red que no se puede quitar.

        Cuando `_canonical_sport` aprendió a reconocer la fuerza, la tentación
        era dejar que una sesión de fuerza casara sólo con "strength". Eso
        habría marcado como NO REALIZADA toda sesión de gimnasio que Garmin
        etiquete de otra forma, y ese "no realizado" falso entra tal cual en el
        prompt de la semana siguiente: el coach recortaría la progresión por
        algo que el atleta sí hizo.
        """
        report = _report([_day("2026-09-08", sport="strength", minutes=40)],
                         [_activity(1, "2026-09-08", "indoor_cardio", 42)])
        self.assertEqual(report.entries[0].status, "completed")

    def test_cycling_variants_match_a_bike_session(self):
        for activity_type in ("cycling", "road_biking", "indoor_cycling"):
            with self.subTest(activity_type=activity_type):
                report = _report([_day("2026-09-08", sport="bike", minutes=60)],
                                 [_activity(1, "2026-09-08", activity_type, 61)])
                self.assertEqual(report.entries[0].status, "completed")


class UnplannedTraining(unittest.TestCase):
    def test_extra_training_is_surfaced_not_ignored(self):
        report = _report([_day("2026-09-08", sport="run")],
                         [_activity(1, "2026-09-08", "running", 45),
                          _activity(2, "2026-09-09", "lap_swimming", 40, name="Natación")])
        self.assertEqual(len(report.unplanned), 1)
        self.assertEqual(report.unplanned[0].sport, "swim")

    def test_activities_from_before_the_plan_are_not_called_unplanned(self):
        report = _report([_day("2026-09-08")],
                         [_activity(1, "2026-09-08", "running", 45),
                          _activity(2, "2026-08-15", "running", 30)],
                         start="2026-09-01")
        self.assertEqual(report.unplanned, [])


class Summary(unittest.TestCase):
    def test_completed_and_modified_both_count_as_trained(self):
        report = _report(
            [_day("2026-09-05", minutes=45), _day("2026-09-06", minutes=45),
             _day("2026-09-07", minutes=45), _day("2026-09-08", minutes=45)],
            [_activity(1, "2026-09-05", "running", 45), _activity(2, "2026-09-06", "running", 90)],
        )
        self.assertEqual(report.completed_count, 1)
        self.assertEqual(report.modified_count, 1)
        self.assertEqual(report.missed_count, 2)
        self.assertEqual(report.adherence_pct, 50.0)

    def test_no_previous_plan_produces_an_empty_report(self):
        report = build_adherence_report(None, [], today=TODAY)
        self.assertEqual(report.entries, [])
        self.assertIsNone(report.adherence_pct)

    def test_the_prompt_block_is_empty_when_nothing_can_be_judged(self):
        report = _report([_day("2026-09-20")], [])
        self.assertEqual(render_adherence_block(report), "")

    def test_the_prompt_block_reads_as_evidence_not_as_a_scoreboard(self):
        report = _report([_day("2026-09-08", minutes=45, focus="Tempo")],
                         [_activity(1, "2026-09-08", "running", 45)])
        text = render_adherence_block(report)
        self.assertIn("evidencia, no como reproche", text)
        self.assertIn("2026-09-08 run · Tempo: completada", text)

    def test_the_state_dict_is_serialisable_and_complete(self):
        report = _report([_day("2026-09-08", minutes=45)],
                         [_activity(1, "2026-09-08", "running", 45)])
        state = report.to_state_dict()
        self.assertEqual(state["adherence_pct"], 100.0)
        self.assertEqual(state["evaluated"], 1)
        self.assertEqual(len(state["sessions"]), 1)
        self.assertEqual(state["sessions"][0]["status"], "completed")


class BadDataIsSurvivable(unittest.TestCase):
    def test_an_activity_with_no_start_time_is_skipped(self):
        broken = SimpleNamespace(activity_id=9, activity_type="running", activity_name=None,
                                 start_time=None, summary=None)
        report = _report([_day("2026-09-08")], [broken])
        self.assertEqual(report.entries[0].status, "missed")

    def test_a_session_with_an_unparseable_date_is_skipped_not_crashed(self):
        report = _report([_day("no-es-fecha")], [])
        self.assertEqual(report.entries, [])

    def test_an_activity_without_a_summary_still_matches(self):
        bare = SimpleNamespace(activity_id=9, activity_type="running", activity_name="X",
                               start_time="2026-09-08 06:00:00", summary=None)
        report = _report([_day("2026-09-08")], [bare])
        self.assertEqual(report.entries[0].status, "completed")
        self.assertIsNone(report.entries[0].actual_minutes)


class ElBrickNoExisteEnGarmin(unittest.TestCase):
    """Garmin no guarda "brick" en ningun sitio.

    Un brick completado llega como un multideporte -- que `_canonical_sport`
    colapsa a "other", porque no es ni carrera ni bici -- o como las dos
    actividades sueltas de cada tramo. Buscando literalmente "brick" no se
    encuentra NUNCA: cada brick que el atleta hiciera se contaba como no
    realizado, y sus tramos aparecian ademas como entrenamiento fuera del
    plan. Eso entra tal cual en el prompt de la semana siguiente, asi que el
    coach le recortaria la progresion por una sesion que si hizo.
    """

    def test_un_brick_grabado_como_multideporte_cuenta_como_hecho(self):
        r = _report([_day("2026-09-06", sport="brick", minutes=90)],
                    [_activity(1, "2026-09-06", "multi_sport", 88)])
        self.assertEqual(r.entries[0].status, "completed")

    def test_un_brick_grabado_por_tramos_suma_los_dos(self):
        """60' de bici + 30' de carrera son los 90' prescritos, no 60.

        Quedarse con el primer tramo lee una sesion clavada como recortada, y
        deja el otro contado como entrenamiento fuera del plan."""
        r = _report([_day("2026-09-06", sport="brick", minutes=90)],
                    [_activity(1, "2026-09-06", "cycling", 60),
                     _activity(2, "2026-09-06", "running", 30)])
        self.assertEqual(r.entries[0].actual_minutes, 90.0)
        self.assertEqual(r.entries[0].status, "completed")

    def test_los_tramos_de_un_brick_no_salen_ademas_como_fuera_del_plan(self):
        r = _report([_day("2026-09-06", sport="brick", minutes=90)],
                    [_activity(1, "2026-09-06", "cycling", 60),
                     _activity(2, "2026-09-06", "running", 30)])
        self.assertEqual(r.unplanned, [])

    def test_una_sesion_normal_sigue_exigiendo_SU_deporte(self):
        """El emparejamiento ancho es solo del brick: una tirada no se da por
        hecha porque ese dia el atleta fuera a nadar."""
        r = _report([_day("2026-09-06", sport="run", minutes=45)],
                    [_activity(1, "2026-09-06", "lap_swimming", 45)])
        self.assertEqual(r.entries[0].status, "missed")


if __name__ == "__main__":
    unittest.main(verbosity=2)
