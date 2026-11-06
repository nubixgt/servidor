"""Tests for the recovery snapshot (Plan Maestro v4 Track E).

The finding this exists to fix, from a real generation: the season plan told
the athlete to "stabilize HRV within the optimal 56.0-82.0 ms zone". Those
numbers turned out NOT to be hallucinated -- 56-82 ms is this athlete's own
Garmin balanced range, which reached the model as prose and came back out
relabelled as a universal optimum with a threshold rule attached. The failure
was framing, not arithmetic, and the fix is to hand over the same numbers
already labelled as personal.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.recovery_state import (  # noqa: E402
    build_recovery_snapshot,
    recovery_to_state,
    render_recovery_block,
)


class _Markers:
    def __init__(self, hrv=None, rhr=None):
        self.hrv = hrv
        self.resting_heart_rate = rhr


class _Garmin:
    def __init__(self, markers):
        self.physiological_markers = markers


def _full_markers():
    return _Markers(
        hrv={
            "last_night_avg": 48.0, "weekly_avg": 52.0, "status": "LOW",
            "baseline": {"balanced_low": 56.0, "balanced_upper": 82.0, "low_upper": 50.0},
        },
        rhr=48,
    )


class SnapshotReadsRealGarminShape(unittest.TestCase):
    def test_it_captures_hrv_status_and_the_personal_baseline(self):
        snap = build_recovery_snapshot(_Garmin(_full_markers()))
        self.assertEqual(snap.hrv_last_night, 48.0)
        self.assertEqual(snap.hrv_status, "LOW")
        self.assertEqual(snap.hrv_baseline_low, 56.0)
        self.assertEqual(snap.hrv_baseline_high, 82.0)
        self.assertEqual(snap.resting_heart_rate, 48)

    def test_an_athlete_with_no_hrv_data_is_a_normal_case_not_an_error(self):
        snap = build_recovery_snapshot(_Garmin(_Markers(hrv=None, rhr=52)))
        self.assertIsNone(snap.hrv_last_night)
        self.assertEqual(snap.resting_heart_rate, 52)
        self.assertTrue(snap.has_data)

    def test_missing_markers_entirely_does_not_raise(self):
        snap = build_recovery_snapshot(_Garmin(None))
        self.assertFalse(snap.has_data)

    def test_hrv_without_a_personal_baseline_is_flagged(self):
        """A raw HRV number with nothing personal to compare it against is
        exactly the situation that invites a remembered population cutoff."""
        snap = build_recovery_snapshot(_Garmin(_Markers(
            hrv={"last_night_avg": 48.0, "baseline": {}}, rhr=50,
        )))
        self.assertTrue(snap.warnings)


class BlockFramesTheNumbersAsPersonal(unittest.TestCase):
    def test_the_baseline_is_labelled_as_this_athletes_own(self):
        block = render_recovery_block(build_recovery_snapshot(_Garmin(_full_markers())))
        self.assertIn("56-82 ms", block)
        self.assertIn("PROPIO", block)

    def test_it_warns_against_restating_the_range_as_a_universal_optimum(self):
        """Directly targets the observed failure mode."""
        block = render_recovery_block(build_recovery_snapshot(_Garmin(_full_markers()))).lower()
        self.assertIn("no una zona óptima general", block)
        self.assertIn("una sola noche", block)

    def test_garmins_own_status_is_translated_not_reinterpreted(self):
        block = render_recovery_block(build_recovery_snapshot(_Garmin(_full_markers())))
        self.assertIn("por debajo de su rango normal", block)

    def test_no_data_renders_nothing_rather_than_a_block_full_of_na(self):
        self.assertEqual(render_recovery_block(build_recovery_snapshot(_Garmin(None))), "")
        self.assertEqual(render_recovery_block(None), "")


class StateSerialization(unittest.TestCase):
    def test_the_state_dict_carries_both_numbers_and_the_rendered_block(self):
        state = recovery_to_state(build_recovery_snapshot(_Garmin(_full_markers())))
        self.assertEqual(state["hrv_status"], "LOW")
        self.assertEqual(state["hrv_baseline_low"], 56.0)
        self.assertTrue(state["block"])

    def test_none_serializes_to_an_empty_dict(self):
        self.assertEqual(recovery_to_state(None), {})


if __name__ == "__main__":
    unittest.main(verbosity=2)
