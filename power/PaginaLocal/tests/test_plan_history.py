"""Tests for archiving past generations instead of destroying them.

Every generation used to overwrite the last one in place. An athlete who
printed Monday's plan and regenerated on Tuesday had no way back to it, and
the coach had no record of what it had actually prescribed -- so every
generation reinvented the training block from scratch.

Run with:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from fastapi import HTTPException  # noqa: E402

from services.ai.langgraph.nodes.weekly_planner_node import _format_previous_plan  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from web_app.server import (  # noqa: E402
    _read_history_entry,
    archive_previous_generation,
    delete_current_generation,
    delete_history_entry,
    get_history_report,
    get_plan_history_entry,
)
from web_app.server import execution_status as server_execution_status  # noqa: E402


def _write_generation(storage_dir: Path, generated_at: str, focus: str = "Tempo"):
    """Lay down the files one finished generation leaves behind."""
    storage_dir.mkdir(parents=True, exist_ok=True)
    (storage_dir / "summary.json").write_text(json.dumps({
        "athlete": "Test", "analysis_date": generated_at,
        "total_cost_usd": 0.71, "total_tokens": 12345,
        "cost_breakdown": [{"name": "weekly_planner", "cost_usd": 0.3}],
        "plan_validation": {"repaired": False},
        "prescription": {"threshold_pace_sec_per_km": 347.2},
    }), encoding="utf-8")
    (storage_dir / "weekly_plan.json").write_text(
        _payload(focus).model_dump_json(indent=2), encoding="utf-8")
    (storage_dir / "season_plan.md").write_text(f"# Temporada {focus}", encoding="utf-8")
    (storage_dir / "planning.html").write_text("<p>plan</p>", encoding="utf-8")


def _payload(focus="Tempo", date="2026-09-02"):
    return WeeklyPlanPayload(
        athlete_name="Test", start_date=date, zones_table="z",
        weeks=[WeekBlock(week_number=1, days=[
            WorkoutDay(date=date, day_name_es="Miércoles", is_rest=False, sport="run",
                       focus=focus, purpose="p", adaptation="a", total_duration_minutes=45,
                       steps=[WorkoutStep(
                           step_order=1, step_type="repeat", repeat_count=5,
                           description="5x(3' a 5:40-5:55/km, 2' rec)",
                           substeps=[
                               WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                                           duration_value=180, target_type=TargetType.PACE,
                                           target_low=340, target_high=355, description="3' tempo"),
                               WorkoutStep(step_order=2, step_type="recovery", duration_type="time",
                                           duration_value=120, target_type=TargetType.HR_ZONE,
                                           target_zone=1, description="2' Z1"),
                           ])]),
            WorkoutDay(date="2026-09-03", day_name_es="Jueves", is_rest=True, sport="rest",
                       focus="Descanso", purpose="p", adaptation="a", steps=[]),
        ])],
    )


class Archiving(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)

    def tearDown(self):
        self._tmp.cleanup()

    def test_a_first_generation_has_nothing_to_archive(self):
        self.assertIsNone(archive_previous_generation(self.storage))

    def test_the_entry_is_named_after_the_generation_it_holds(self):
        _write_generation(self.storage, "2026-08-21T10:21:12.123456")
        self.assertEqual(archive_previous_generation(self.storage), "2026-08-21T10-21-12")

    def test_the_archived_copy_survives_the_next_generation_overwriting(self):
        _write_generation(self.storage, "2026-08-21T10:21:12", focus="Tempo")
        entry_id = archive_previous_generation(self.storage)

        _write_generation(self.storage, "2026-08-22T09:00:00", focus="VO2max")

        archived = _read_history_entry(self.storage / "history" / entry_id)
        self.assertEqual(archived["weekly_plan"]["weeks"][0]["days"][0]["focus"], "Tempo")
        live = json.loads((self.storage / "weekly_plan.json").read_text(encoding="utf-8"))
        self.assertEqual(live["weeks"][0]["days"][0]["focus"], "VO2max")

    def test_archiving_twice_does_not_duplicate_the_entry(self):
        _write_generation(self.storage, "2026-08-21T10:21:12")
        first = archive_previous_generation(self.storage)
        second = archive_previous_generation(self.storage)
        self.assertEqual(first, second)
        self.assertEqual(len(list((self.storage / "history").iterdir())), 1)

    def test_every_generation_file_present_is_copied(self):
        _write_generation(self.storage, "2026-08-21T10:21:12")
        entry_id = archive_previous_generation(self.storage)
        entry = self.storage / "history" / entry_id
        for filename in ("summary.json", "weekly_plan.json", "season_plan.md", "planning.html"):
            self.assertTrue((entry / filename).exists(), filename)

    def test_a_broken_summary_does_not_stop_the_generation(self):
        """Losing the history copy must never cost the athlete their new plan."""
        self.storage.mkdir(exist_ok=True)
        (self.storage / "summary.json").write_text("{not json", encoding="utf-8")
        self.assertIsNone(archive_previous_generation(self.storage))

    def test_successive_generations_accumulate(self):
        for stamp, focus in (("2026-08-21T10:00:00", "A"), ("2026-08-22T10:00:00", "B")):
            _write_generation(self.storage, stamp, focus=focus)
            archive_previous_generation(self.storage)
        entries = sorted(p.name for p in (self.storage / "history").iterdir())
        self.assertEqual(entries, ["2026-08-21T10-00-00", "2026-08-22T10-00-00"])


class ReadingAnEntryBack(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)
        _write_generation(self.storage, "2026-08-21T10:21:12")
        self.entry_id = archive_previous_generation(self.storage)
        self.entry = _read_history_entry(self.storage / "history" / self.entry_id)

    def tearDown(self):
        self._tmp.cleanup()

    def test_it_carries_what_the_plan_cost_and_was_based_on(self):
        self.assertEqual(self.entry["total_cost_usd"], 0.71)
        self.assertEqual(self.entry["prescription"]["threshold_pace_sec_per_km"], 347.2)
        self.assertTrue(self.entry["has_planning_html"])

    def test_a_missing_entry_reads_as_none(self):
        self.assertIsNone(_read_history_entry(self.storage / "history" / "no-such-entry"))


class EntryIdsCannotEscapeTheHistoryDirectory(unittest.TestCase):
    """The id comes straight from a URL, so it must stay inside history/."""

    def test_traversal_attempts_are_rejected(self):
        for bad in ("../../etc/passwd", "..", "a/b", "a\\b"):
            with self.subTest(entry_id=bad), self.assertRaises(HTTPException) as caught:
                get_plan_history_entry(bad, "Test")
            self.assertEqual(caught.exception.status_code, 400)

    def test_a_clean_id_that_simply_does_not_exist_is_a_404(self):
        with self.assertRaises(HTTPException) as caught:
            get_plan_history_entry("2026-01-01T00-00-00", "no-such-profile")
        self.assertEqual(caught.exception.status_code, 404)

    def test_the_report_kind_is_restricted_to_the_two_real_reports(self):
        for bad_kind in ("../secrets", "summary", "weekly_plan"):
            with self.subTest(kind=bad_kind), self.assertRaises(HTTPException) as caught:
                get_history_report("2026-01-01T00-00-00", "Test", kind=bad_kind)
            self.assertEqual(caught.exception.status_code, 400)


class DeletingARun(unittest.TestCase):
    """Undo the last generation, for an athlete testing repeatedly."""

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)
        self._patch = mock.patch("web_app.server._profile_storage_dir", return_value=self.storage)
        self._patch.start()

    def tearDown(self):
        self._patch.stop()
        self._tmp.cleanup()

    def test_deleting_restores_the_previously_archived_plan(self):
        """"Delete this run" means going back, not being left with nothing."""
        _write_generation(self.storage, "2026-08-21T10:00:00", focus="Antiguo")
        archive_previous_generation(self.storage)
        _write_generation(self.storage, "2026-08-22T10:00:00", focus="Nuevo")

        result = delete_current_generation(profile="Test")

        self.assertTrue(result["deleted"])
        self.assertEqual(result["restored"], "2026-08-21T10-00-00")
        live = json.loads((self.storage / "weekly_plan.json").read_text(encoding="utf-8"))
        self.assertEqual(live["weeks"][0]["days"][0]["focus"], "Antiguo")

    def test_the_restored_plan_leaves_the_history(self):
        """Otherwise deleting twice would keep resurrecting the same entry."""
        _write_generation(self.storage, "2026-08-21T10:00:00")
        archive_previous_generation(self.storage)
        _write_generation(self.storage, "2026-08-22T10:00:00")

        delete_current_generation(profile="Test")
        self.assertEqual(list((self.storage / "history").iterdir()), [])

    def test_deleting_the_only_generation_leaves_no_plan(self):
        _write_generation(self.storage, "2026-08-21T10:00:00")
        result = delete_current_generation(profile="Test")
        self.assertIsNone(result["restored"])
        self.assertFalse((self.storage / "weekly_plan.json").exists())
        self.assertFalse((self.storage / "summary.json").exists())

    def test_deleting_when_there_is_nothing_is_a_404(self):
        with self.assertRaises(HTTPException) as caught:
            delete_current_generation(profile="Test")
        self.assertEqual(caught.exception.status_code, 404)

    def test_a_generation_in_progress_blocks_deletion(self):
        """Deleting files mid-run would corrupt the generation writing them."""
        _write_generation(self.storage, "2026-08-21T10:00:00")
        with mock.patch.dict(server_execution_status, {"running": True}):
            with self.assertRaises(HTTPException) as caught:
                delete_current_generation(profile="Test")
        self.assertEqual(caught.exception.status_code, 409)

    def test_a_history_entry_can_be_deleted_on_its_own(self):
        _write_generation(self.storage, "2026-08-21T10:00:00")
        entry_id = archive_previous_generation(self.storage)
        delete_history_entry(entry_id, profile="Test")
        self.assertFalse((self.storage / "history" / entry_id).exists())
        # The live plan is untouched.
        self.assertTrue((self.storage / "weekly_plan.json").exists())

    def test_history_deletion_rejects_traversal(self):
        for bad in ("../../etc", "..", "a/b"):
            with self.subTest(entry_id=bad), self.assertRaises(HTTPException) as caught:
                delete_history_entry(bad, profile="Test")
            self.assertEqual(caught.exception.status_code, 400)


class GeneratingDoesNotRequireDeleting(unittest.TestCase):
    """The delete button is a convenience, never a prerequisite."""

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)

    def tearDown(self):
        self._tmp.cleanup()

    def test_a_second_generation_archives_the_first_by_itself(self):
        _write_generation(self.storage, "2026-08-21T10:00:00", focus="Primero")
        archive_previous_generation(self.storage)          # what run_generation_task does
        _write_generation(self.storage, "2026-08-22T10:00:00", focus="Segundo")

        live = json.loads((self.storage / "weekly_plan.json").read_text(encoding="utf-8"))
        self.assertEqual(live["weeks"][0]["days"][0]["focus"], "Segundo")

        archived = _read_history_entry(self.storage / "history" / "2026-08-21T10-00-00")
        self.assertEqual(archived["weekly_plan"]["weeks"][0]["days"][0]["focus"], "Primero")


class PreviousPlanAsContext(unittest.TestCase):
    def test_it_names_the_real_doses_not_just_that_a_run_happened(self):
        text = _format_previous_plan(_payload(), "2026-09-05")
        self.assertIn("5x(3' a 5:40-5:55/km, 2' rec)", text)
        self.assertIn("2026-09-02 run", text)
        self.assertIn("MICROCICLO", text)

    def test_rest_days_are_left_out(self):
        text = _format_previous_plan(_payload(), "2026-09-05")
        self.assertNotIn("2026-09-03", text)

    def test_a_recent_plan_is_presented_as_a_block_in_progress(self):
        text = _format_previous_plan(_payload(date="2026-09-02"), "2026-09-10")
        self.assertIn("ha estado siguiendo", text)
        self.assertIn("MICROCICLO", text)

    def test_repeating_sessions_is_presented_as_CORRECT_not_as_a_failure(self):
        """Decia "no repitas la misma semana", y eso es malo entrenando.

        Un microciclo se repite dentro de su mesociclo por diseno: lo que
        progresa es la DOSIS, no la identidad de las sesiones. Pedirle
        novedad al coach cada semana es pedirle que rompa la adaptacion, que
        necesita repeticion para producirse."""
        text = _format_previous_plan(_payload(date="2026-09-02"), "2026-09-10")
        self.assertNotIn("no repitas", text.lower())
        self.assertIn("es lo normal y lo correcto", text)
        self.assertIn("reinventar la semana", text)  # eso si se sigue evitando

    def test_a_months_old_plan_is_presented_as_history_instead(self):
        """Resuming a build the athlete may not have trained a day of is worse
        than having no previous plan at all."""
        text = _format_previous_plan(_payload(date="2026-05-01"), "2026-09-10")
        self.assertIn("historial", text)
        self.assertIn("NO asumas que lo completo", text)
        self.assertNotIn("MICROCICLO", text)

    def test_an_unparseable_date_does_not_crash_or_mislabel(self):
        text = _format_previous_plan(_payload(date="no es una fecha"), "2026-09-10")
        self.assertIn("MICROCICLO", text)  # falls back to the safe framing

    def test_no_previous_plan_produces_nothing_rather_than_a_placeholder(self):
        self.assertEqual(_format_previous_plan(None, "2026-09-05"), "")

    def test_a_plan_with_only_rest_days_produces_nothing(self):
        payload = _payload()
        payload.weeks[0].days = [payload.weeks[0].days[1]]
        self.assertEqual(_format_previous_plan(payload, "2026-09-05"), "")


if __name__ == "__main__":
    unittest.main(verbosity=2)
