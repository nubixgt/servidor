"""Tests for Plan Maestro v5 Track 7: the reasoning trail actually survives
the process that produced it.

Before this, a generation left five files on disk and threw away the three
summarizer views, the nine expert payloads (3 experts x 3 receivers), the
synthesis markdown, and the decision layer's WeekIntentContent -- the last of
which is the coaching call itself, made before any Garmin encoding. All of it
was paid for with real API credits.

The archive is a nice-to-have wrapped around work the athlete already paid
for, so the load-bearing property tested here is that it can NEVER take a
generation down with it: not on an unserializable object, not on a node that
returned nothing, not on a read-back of a directory that isn't there.

Run with:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from datetime import datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import DayIntent, WeekIntentContent  # noqa: E402
from services.ai.reasoning_archive import (  # noqa: E402
    latest_reasoning_entry,
    list_reasoning_entries,
    load_reasoning_entry,
    save_reasoning,
)


def _week_intent() -> WeekIntentContent:
    return WeekIntentContent(
        week_number=1,
        week_rationale="Bajamos intensidad porque tu HRV cayó bajo tu propio rango.",
        days=[
            DayIntent(
                date="2026-09-02", day_name_es="Miércoles", is_rest=False, sport="run",
                focus="Umbral", purpose="Trabajo de umbral controlado.",
                adaptation="Si duele: para.", total_duration_minutes=40,
                intensity_class="tempo_threshold", discipline="carrera",
                session_sketch="10min Z1, 3x8min a 5:40-5:55/km con 3min trote, 10min Z1",
            )
        ],
    )


def _full_result() -> dict:
    return {
        "athlete_name": "Jose Ines Castillo",
        "execution_id": "test_20260902_120000_week",
        "metrics_summary": "## Cobertura\n2026-08-05 a 2026-09-01.",
        "physiology_summary": "## Cobertura\nHRV medido 24 de 28 noches.",
        "activity_summary": "## Cobertura\n11 actividades.",
        "metrics_outputs": {"output": {"for_weekly_planner": {"signals": ["carga subiendo"]}}},
        "physiology_outputs": {"output": {"for_weekly_planner": {"signals": ["HRV bajo baseline"]}}},
        "activity_outputs": {"output": {"for_weekly_planner": {"signals": ["ejecutó 5 de 6"]}}},
        "synthesis_result": "# Informe\nLa carga sube y la recuperación baja.",
        "week_intents": [_week_intent().model_dump()],
        "weekly_plan_validation": {"repaired": True, "attempts": 1, "violations_found": 0},
        "cost_summary": {
            "total_cost_usd": 0.2607,
            "total_tokens": 102389,
            "agents": [{"name": "activity_summarizer"}, {"name": "weekly_planner"}],
        },
        "errors": [],
    }


class EverythingTheAgentsProducedIsWritten(unittest.TestCase):
    def test_each_artefact_lands_in_its_own_file(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(storage, _full_result(), kind="week")

            self.assertIsNotNone(entry_id)
            entry_dir = storage / "reasoning" / entry_id
            for name in (
                "summarizer_metrics.md", "summarizer_physiology.md", "summarizer_activity.md",
                "expert_metrics.json", "expert_physiology.json", "expert_activity.json",
                "synthesis.md", "week_intents.json", "plan_validation.json", "manifest.json",
            ):
                with self.subTest(file=name):
                    self.assertTrue((entry_dir / name).is_file(), f"falta {name}")

    def test_the_coaching_decision_survives_with_its_rationale(self):
        """week_intents is the whole point: it is the coaching call BEFORE any
        Garmin encoding, and it used to be a local variable in
        weekly_planner_node that died with the process."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(storage, _full_result(), kind="week")

            intents = json.loads((storage / "reasoning" / entry_id / "week_intents.json").read_text(encoding="utf-8"))
            self.assertIn("HRV", intents[0]["week_rationale"])
            self.assertEqual(intents[0]["days"][0]["intensity_class"], "tempo_threshold")

    def test_extra_context_the_workflow_state_does_not_carry_is_saved_too(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(
                storage, _full_result(), kind="week",
                extra={"recovery": {"hrv_status": "UNBALANCED"}, "volume_baseline": {"chronic_weekly_hours": 3.6}},
            )

            entry_dir = storage / "reasoning" / entry_id
            recovery = json.loads((entry_dir / "recovery.json").read_text(encoding="utf-8"))
            self.assertEqual(recovery["hrv_status"], "UNBALANCED")
            self.assertTrue((entry_dir / "volume_baseline.json").is_file())

    def test_a_none_extra_is_skipped_rather_than_written_as_null(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(storage, _full_result(), kind="week", extra={"adherence": None})
            self.assertFalse((storage / "reasoning" / entry_id / "adherence.json").exists())


class AMissingArtefactIsNamedNotHidden(unittest.TestCase):
    def test_a_failed_expert_is_recorded_in_the_manifest(self):
        """"The physiology expert produced nothing on this run" and "we forgot
        to save it" must not look the same on disk a month later."""
        result = _full_result()
        result["physiology_outputs"] = None
        result["synthesis_result"] = ""

        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            # A SEASON run: v6 stopped faulting week runs for an absent
            # synthesis, because the week workflow has no synthesis node --
            # see AWeekRunDoesNotAccuseItselfOfLosingASynthesis below.
            entry_id = save_reasoning(storage, result, kind="season")

            manifest = json.loads((storage / "reasoning" / entry_id / "manifest.json").read_text(encoding="utf-8"))
            self.assertIn("physiology_outputs", manifest["missing"])
            self.assertIn("synthesis_result", manifest["missing"])
            self.assertNotIn("metrics_outputs", manifest["missing"])

    def test_a_season_run_is_not_faulted_for_having_no_week_intents(self):
        """The season generation never runs the weekly planner, so an absent
        week_intents there is correct, not a gap."""
        result = _full_result()
        del result["week_intents"]

        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            season = save_reasoning(storage, result, kind="season")
            week = save_reasoning(storage, result, kind="week", timestamp=datetime(2026, 9, 3, 8, 0, 0))

            season_manifest = json.loads((storage / "reasoning" / season / "manifest.json").read_text(encoding="utf-8"))
            week_manifest = json.loads((storage / "reasoning" / week / "manifest.json").read_text(encoding="utf-8"))
            self.assertNotIn("week_intents", season_manifest["missing"])
            self.assertIn("week_intents", week_manifest["missing"])

    def test_the_manifest_records_what_the_run_cost(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(storage, _full_result(), kind="week")
            manifest = json.loads((storage / "reasoning" / entry_id / "manifest.json").read_text(encoding="utf-8"))
            self.assertAlmostEqual(manifest["total_cost_usd"], 0.2607)
            self.assertEqual(manifest["kind"], "week")
            self.assertIn("weekly_planner", manifest["agents"])


class ArchivingNeverTakesAGenerationDown(unittest.TestCase):
    def test_an_object_that_cannot_be_serialized_is_stringified_not_raised(self):
        class Opaque:
            def __repr__(self):
                return "<objeto opaco>"

        result = _full_result()
        result["metrics_outputs"] = {"output": Opaque()}

        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(storage, result, kind="week")
            self.assertIsNotNone(entry_id)
            saved = (storage / "reasoning" / entry_id / "expert_metrics.json").read_text(encoding="utf-8")
            self.assertIn("objeto opaco", saved)

    def test_a_model_whose_dump_explodes_still_lets_the_archive_finish(self):
        class Exploding:
            def model_dump(self):
                raise RuntimeError("boom")

            def __repr__(self):
                return "<modelo que explota>"

        result = _full_result()
        result["weekly_plan_validation"] = Exploding()

        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            self.assertIsNotNone(save_reasoning(storage, result, kind="week"))

    def test_an_entirely_empty_result_still_produces_a_readable_manifest(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(storage, {}, kind="season")
            manifest = json.loads((storage / "reasoning" / entry_id / "manifest.json").read_text(encoding="utf-8"))
            self.assertEqual(manifest["files"], ["manifest.json"])
            self.assertIn("metrics_summary", manifest["missing"])


class ReadingItBack(unittest.TestCase):
    def test_entries_come_back_newest_first(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            save_reasoning(storage, _full_result(), kind="season", timestamp=datetime(2026, 9, 1, 8, 0, 0))
            save_reasoning(storage, _full_result(), kind="week", timestamp=datetime(2026, 9, 8, 8, 0, 0))

            entries = list_reasoning_entries(storage)
            self.assertEqual([e["kind"] for e in entries], ["week", "season"])

    def test_the_latest_can_be_filtered_by_kind(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            save_reasoning(storage, _full_result(), kind="season", timestamp=datetime(2026, 9, 1, 8, 0, 0))
            save_reasoning(storage, _full_result(), kind="week", timestamp=datetime(2026, 9, 8, 8, 0, 0))

            self.assertEqual(latest_reasoning_entry(storage)["kind"], "week")
            self.assertEqual(latest_reasoning_entry(storage, "season")["id"], "2026-09-01T08-00-00")
            self.assertIsNone(latest_reasoning_entry(storage, "no-such-kind"))

    def test_contents_come_back_as_prompt_ready_text(self):
        """The coach chatbot wants this as prompt material, so even the JSON
        files come back as their raw text rather than re-parsed objects."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(storage, _full_result(), kind="week")

            contents = load_reasoning_entry(storage, entry_id)
            self.assertIn("La carga sube", contents["synthesis.md"])
            self.assertIsInstance(contents["week_intents.json"], str)

    def test_a_profile_with_no_archive_lists_empty_instead_of_failing(self):
        with tempfile.TemporaryDirectory() as tmp:
            self.assertEqual(list_reasoning_entries(Path(tmp)), [])
            self.assertIsNone(latest_reasoning_entry(Path(tmp)))
            self.assertIsNone(load_reasoning_entry(Path(tmp), "2026-09-01T08-00-00"))

    def test_a_corrupted_manifest_is_flagged_rather_than_dropped_from_the_list(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            entry_id = save_reasoning(storage, _full_result(), kind="week")
            (storage / "reasoning" / entry_id / "manifest.json").write_text("{no es json", encoding="utf-8")

            entries = list_reasoning_entries(storage)
            self.assertEqual(len(entries), 1)
            self.assertIn("manifest_error", entries[0])


class AWeekRunDoesNotAccuseItselfOfLosingASynthesis(unittest.TestCase):
    """v6 finding A8, seen twice in real logs:

        Reasoning archive ... written with 1 missing artefact(s): synthesis_result

    A week generation is `3 summarizers -> 3 experts -> weekly_planner ->
    plan_formatter`. There is no synthesis node in it. The archive was
    reporting the absence of something that was never produced, which is the
    opposite of what the `missing` list is for: telling "this node produced
    nothing" apart from "we forgot to save it"."""

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)

    def tearDown(self):
        self._tmp.cleanup()

    def _manifest(self, kind):
        entry = save_reasoning(self.storage, {"athlete_name": "T"}, kind=kind,
                               timestamp=datetime(2026, 1, 1) if kind == "week" else datetime(2026, 1, 2))
        return json.loads((self.storage / "reasoning" / entry / "manifest.json").read_text(encoding="utf-8"))

    def test_a_week_run_does_not_list_it(self):
        self.assertNotIn("synthesis_result", self._manifest("week")["missing"])

    def test_a_season_run_still_does_when_it_really_is_absent(self):
        """The season workflow DOES have a synthesis node, so silence there is
        a real gap and must stay reported."""
        self.assertIn("synthesis_result", self._manifest("season")["missing"])

    def test_a_week_run_still_reports_a_missing_week_intent(self):
        self.assertIn("week_intents", self._manifest("week")["missing"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
