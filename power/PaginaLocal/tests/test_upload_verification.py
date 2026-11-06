"""Tests for reading a workout back from Garmin after uploading it.

Uploading used to be fire-and-forget: a 200 response counted as success, so
anything Garmin quietly dropped or rewrote reached the athlete's watch
unnoticed. Garmin is known to silently corrupt a repeat count when the
endCondition is malformed, which is exactly the kind of failure a status code
cannot reveal.

Run with:  python -m unittest discover -s tests -v
"""

import asyncio
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WorkoutDay,
    WorkoutStep,
)
from services.garmin.garmin_upload_service import GarminUploadService  # noqa: E402


def _interval_day():
    """5 x (3' hard + 2' easy), with warmup and cooldown: 4 executable steps."""
    return WorkoutDay(
        date="2026-09-02", day_name_es="Miércoles", is_rest=False, sport="run",
        focus="Intervalos", purpose="Umbral.", adaptation="—", total_duration_minutes=43,
        steps=[
            WorkoutStep(step_order=1, step_type="warmup", duration_type="time", duration_value=600,
                        target_type=TargetType.HR_ZONE, target_zone=2, description="10' Z2"),
            WorkoutStep(step_order=2, step_type="repeat", repeat_count=5, description="5x(3'+2')",
                        substeps=[
                            WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                                        duration_value=180, target_type=TargetType.PACE,
                                        target_low=340, target_high=355, description="3' tempo"),
                            WorkoutStep(step_order=2, step_type="recovery", duration_type="time",
                                        duration_value=120, target_type=TargetType.HR_ZONE,
                                        target_zone=1, description="2' Z1"),
                        ]),
            WorkoutStep(step_order=3, step_type="cooldown", duration_type="time", duration_value=480,
                        target_type=TargetType.HR_ZONE, target_zone=1, description="8' Z1"),
        ],
    )


def _stored(rounds=5, include_recovery=True, targets=("heart.rate.zone", "pace.zone", "heart.rate.zone", "heart.rate.zone")):
    """A Garmin read-back response, in Garmin's own key shape."""
    inner = [{"stepOrder": 3, "targetType": {"workoutTargetTypeKey": targets[1]}}]
    if include_recovery:
        inner.append({"stepOrder": 4, "targetType": {"workoutTargetTypeKey": targets[2]}})
    return {
        "workoutSegments": [{
            "workoutSteps": [
                {"stepOrder": 1, "targetType": {"workoutTargetTypeKey": targets[0]}},
                {"stepOrder": 2, "numberOfIterations": rounds, "workoutSteps": inner},
                {"stepOrder": 5, "targetType": {"workoutTargetTypeKey": targets[3]}},
            ]
        }]
    }


class _FakeGarmin:
    def __init__(self, response=None, raises=False):
        self._response, self._raises = response, raises

    def get_workout_by_id(self, workout_id):
        if self._raises:
            raise RuntimeError("Garmin unreachable")
        return self._response


class _FakeClient:
    def __init__(self, garmin):
        self.client = garmin


def _verify(response=None, raises=False, day=None):
    service = GarminUploadService(_FakeClient(_FakeGarmin(response, raises)))
    return service._verify_uploaded_workout(1, day or _interval_day())


class MatchingWorkoutPassesClean(unittest.TestCase):
    def test_no_warnings_when_garmin_stored_what_we_sent(self):
        self.assertEqual(_verify(_stored()), [])

    def test_the_curated_key_shape_is_also_understood(self):
        curated = {"segments": [{"steps": [
            {"order": 1, "target_type": "heart.rate.zone"},
            {"order": 2, "repeat_count": 5, "steps": [
                {"order": 3, "target_type": "pace.zone"},
                {"order": 4, "target_type": "heart.rate.zone"},
            ]},
            {"order": 5, "target_type": "heart.rate.zone"},
        ]}]}
        self.assertEqual(_verify(curated), [])


class DetectsWhatGarminChanged(unittest.TestCase):
    def test_a_corrupted_repeat_count_is_caught(self):
        """The known Garmin failure: it accepts the upload and stores 1 round."""
        warnings = _verify(_stored(rounds=1))
        self.assertTrue(any("repeticiones no coinciden" in w for w in warnings))

    def test_a_dropped_step_is_caught(self):
        warnings = _verify(_stored(include_recovery=False))
        self.assertTrue(any("pasos" in w for w in warnings))

    def test_targets_downgraded_to_no_target_are_caught(self):
        warnings = _verify(_stored(targets=("no.target",) * 4))
        self.assertTrue(any("objetivo de intensidad" in w for w in warnings))

    def test_swim_is_not_warned_about_missing_targets(self):
        """Swim uploads intentionally carry no target at all."""
        day = _interval_day()
        day.sport = "swim"
        warnings = _verify(_stored(targets=("no.target",) * 4), day=day)
        self.assertFalse(any("objetivo de intensidad" in w for w in warnings))


class VerificationNeverBreaksTheUpload(unittest.TestCase):
    def test_a_read_back_error_is_not_a_false_alarm(self):
        """The workout is on Garmin either way; don't invent a problem."""
        self.assertEqual(_verify(raises=True), [])

    def test_an_unexpected_response_shape_is_ignored(self):
        self.assertEqual(_verify(response="not a dict"), [])
        self.assertEqual(_verify(response=None), [])

    def test_upload_still_reports_success_when_verification_warns(self):
        day = _interval_day()
        service = GarminUploadService(_FakeClient(_FakeGarmin(_stored(rounds=1))))
        service._upload_and_schedule = lambda workout_json, date_str: 4242

        result = asyncio.run(service.upload_day(day, _zones()))
        self.assertTrue(result.success)
        self.assertEqual(result.workout_id, 4242)
        self.assertTrue(result.warnings)


def _zones():
    from services.garmin.workout_builder import compute_zone_config

    return compute_zone_config(garmin_hr_zones={1: (123, 138), 2: (139, 152), 3: (153, 166),
                                                4: (167, 180), 5: (181, None)})


if __name__ == "__main__":
    unittest.main(verbosity=2)
