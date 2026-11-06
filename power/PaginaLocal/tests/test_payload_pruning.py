"""Plan Maestro v6 Track 6 -- the summarizer payload loses noise, never signal.

One real season generation sent the activity summarizer **353,613 input
tokens** and got 1,814 back: 171 activities, 855 KB of Garmin JSON, compressed
195:1 before a single coaching decision was made.

The tempting fix -- send fewer activities, drop the laps, summary rows only --
is the one trade this project will not make. The athlete's own framing: the
difference between this app and Runna is *"los detalles y el feedback constante
haciendo planes de entreno totalmente adaptativos"*. Lap splits are how you see
that an interval session faded in the last two reps, or that a "Z2" run drifted
into Z3.

So every test here pins the same property from a different angle: **nothing
that carries a measurement is removed.** What goes is nulls, IEEE-754 float
noise, empty containers, whitespace, and laps that are button presses.

Measured on the real 171-activity payload: 852,795 -> 205,237 characters,
**75.9% less**, and the longest lap it dropped covered **0.92 metres in 4.98
seconds**.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.garmin.payload_pruning import measure, prune  # noqa: E402

# One activity in the exact shape the extractor produces, with the real field
# names and the real proportion of nulls (13 of 29 summary fields were null on
# a real run).
#
# "The exact shape the extractor produces" used to be FALSE of this fixture,
# and that is how the unit bug below lived for months: the laps here carried
# seconds and metres while `get_activity_laps` emitted minutes and kilometres,
# so the guard was only ever checking itself. `tests/test_tramos_con_unidad.py`
# now pins the two against the real producer instead of against prose.
REAL_ACTIVITY = {
    "activity_id": 24122547680,
    "activity_type": "running",
    "activity_name": "Guatemala Carrera",
    "start_time": "2026-08-23 07:18:00",
    "summary": {
        "distance": 21200.0,
        "duration": 9300,
        "average_speed": 0.9390000104904175,
        "max_speed": 2.622999906539917,
        "average_hr": 153,
        "max_hr": 172,
        "activity_training_load": 141,
        "avg_power": None,
        "max_power": None,
        "normalized_power": None,
        "training_stress_score": None,
        "intensity_factor": None,
        "avg_respiration_rate": None,
        "avg_stress": None,
        "elevation_gain": None,
    },
    "weather": {"temp": 21.0, "relative_humidity": 78, "wind_speed": None},
    "hr_zones": [],
    "laps": [
        {"startTime": "2026-08-23T13:18:00.0", "duration_s": 0.5, "distance_m": 0.0,
         "averageSpeed_ms": 0.0, "averageHR_bpm": 99, "maxHR_bpm": 104, "calories_kcal": 0,
         "elevationGain_m": None, "elevationLoss_m": None, "intensity": None},
        {"startTime": "2026-08-23T13:18:01.0", "duration_s": 1200.0, "distance_m": 2800.0,
         "averageSpeed_ms": 2.3333333333333335, "averageHR_bpm": 148, "maxHR_bpm": 158,
         "calories_kcal": 210,
         "elevationGain_m": None, "elevationLoss_m": None, "intensity": None},
    ],
}


def _numbers(value, found):
    if isinstance(value, bool):
        return
    if isinstance(value, dict):
        for key, item in value.items():
            if key != "laps_omitted":
                _numbers(item, found)
    elif isinstance(value, list):
        for item in value:
            _numbers(item, found)
    elif isinstance(value, (int, float)):
        found.append(round(float(value), 3))


class NothingThatCarriesAMeasurementIsRemoved(unittest.TestCase):
    def test_every_real_lap_survives_with_every_real_field(self):
        lap = prune(REAL_ACTIVITY)["laps"][0]
        self.assertEqual(lap["duration_s"], 1200)
        self.assertEqual(lap["distance_m"], 2800)
        self.assertEqual(lap["averageHR_bpm"], 148)
        self.assertEqual(lap["maxHR_bpm"], 158)
        self.assertEqual(lap["calories_kcal"], 210)

    def test_a_zero_is_a_measurement_and_stays(self):
        """Zero elevation gain is a fact about a flat course. Only None goes."""
        pruned = prune({"elevation_gain": 0, "elevation_loss": None, "distance": 0.0})
        self.assertIn("elevation_gain", pruned)
        self.assertIn("distance", pruned)
        self.assertNotIn("elevation_loss", pruned)

    def test_false_is_not_empty(self):
        self.assertEqual(prune({"primaryTrainingDevice": False}), {"primaryTrainingDevice": False})

    def test_the_session_structure_survives_intact(self):
        """The whole point: an interval session's splits are what shows the
        last two reps fading. Losing them would make the plan cheaper and
        worse."""
        session = {"laps": [
            {"duration": 180.0, "distance": 700.0, "averageHR": 168},
            {"duration": 120.0, "distance": 300.0, "averageHR": 142},
            {"duration": 180.0, "distance": 690.0, "averageHR": 171},
            {"duration": 120.0, "distance": 295.0, "averageHR": 145},
        ]}
        self.assertEqual(len(prune(session)["laps"]), 4)
        self.assertEqual([lap["averageHR"] for lap in prune(session)["laps"]], [168, 142, 171, 145])


class OnlyNoiseGoes(unittest.TestCase):
    def test_nulls_are_dropped(self):
        """`"avg_power": null` on a run appears on every activity and every
        lap. An absent key and a null key say the same thing, and one of them
        costs tokens 171 times over."""
        summary = prune(REAL_ACTIVITY)["summary"]
        for gone in ("avg_power", "max_power", "training_stress_score", "avg_stress"):
            with self.subTest(field=gone):
                self.assertNotIn(gone, summary)
        self.assertEqual(summary["average_hr"], 153)

    def test_float_representation_noise_is_rounded_away(self):
        """0.9390000104904175 is 0.939 with fifteen digits of IEEE-754 error
        attached. Nobody coaches off the sixteenth decimal."""
        summary = prune(REAL_ACTIVITY)["summary"]
        self.assertEqual(summary["average_speed"], 0.939)
        self.assertEqual(summary["max_speed"], 2.623)

    def test_empty_containers_are_dropped(self):
        self.assertNotIn("hr_zones", prune(REAL_ACTIVITY))

    def test_a_button_press_lap_is_dropped(self):
        """0.5 seconds, 0.0 metres. On the real payload, 1,133 of 1,611 laps
        were like this -- and the longest one it dropped covered 0.92 metres in
        4.98 seconds."""
        self.assertEqual(len(prune(REAL_ACTIVITY)["laps"]), 1)

    def test_dropping_a_lap_is_never_silent(self):
        """Silence about a gap is what this codebase's archive rules forbid:
        the reader must be able to tell "this session had no splits" from "we
        hid some"."""
        pruned = prune(REAL_ACTIVITY)
        self.assertIn("laps_omitted", pruned)
        self.assertIn("1 vuelta", pruned["laps_omitted"])

    def test_a_short_but_real_effort_is_not_a_button_press(self):
        """A 5-second stride covers ~30 m. The two conditions are AND-ed
        precisely so distance protects it."""
        stride = {"laps": [{"duration": 4.0, "distance": 30.0, "averageHR": 165}]}
        self.assertEqual(len(prune(stride)["laps"]), 1)

    def test_a_long_lap_with_no_distance_is_kept(self):
        """A 20-minute strength block records no distance. Dropping it would
        erase the session."""
        strength = {"laps": [{"duration": 1200.0, "distance": 0.0, "averageHR": 118}]}
        self.assertEqual(len(prune(strength)["laps"]), 1)


class TheSavingIsRealAndMeasured(unittest.TestCase):
    def test_the_payload_shrinks_by_more_than_half(self):
        saving = measure([REAL_ACTIVITY] * 20, prune([REAL_ACTIVITY] * 20))
        self.assertGreater(saving["saved_pct"], 50)

    def test_the_measurement_is_reported_so_it_can_be_checked(self):
        saving = measure(REAL_ACTIVITY, prune(REAL_ACTIVITY))
        self.assertGreater(saving["chars_before"], saving["chars_after"])
        self.assertEqual(
            round((1 - saving["chars_after"] / saving["chars_before"]) * 100, 1), saving["saved_pct"]
        )

    def test_no_number_outside_a_dropped_lap_changes(self):
        before, after = [], []
        activity = {k: v for k, v in REAL_ACTIVITY.items() if k != "laps"}
        _numbers(activity, before)
        _numbers(prune(activity), after)
        self.assertEqual(sorted(before), sorted(after))

    def test_it_survives_the_shapes_the_other_two_summarizers_send(self):
        """metrics and physiology send dicts of lists of dicts, not activity
        lists. The prune is structural, not activity-specific."""
        metrics = {"training_load_history": [
            {"date": "2026-08-25", "acwr_7d28d": 1.31, "tsb": -12.0, "ramp_7d": None},
        ], "vo2_max_history": {"running": [{"date": "2026-08-25", "value": 49.0}], "cycling": []}}
        pruned = prune(metrics)
        self.assertEqual(pruned["training_load_history"][0]["acwr_7d28d"], 1.31)
        self.assertNotIn("ramp_7d", pruned["training_load_history"][0])
        self.assertNotIn("cycling", pruned["vo2_max_history"])


class TheSummarizerActuallyUsesIt(unittest.TestCase):
    NODE_SRC = (
        Path(__file__).resolve().parents[1] / "services/ai/langgraph/nodes/data_summarizer_node.py"
    ).read_text(encoding="utf-8")

    def test_all_three_summarizers_go_through_one_place(self):
        self.assertIn("data_to_summarize = prune(raw_data)", self.NODE_SRC)

    def test_the_payload_is_no_longer_pretty_printed(self):
        """`indent=2` on 855 KB of JSON is whitespace nobody reads and every
        model pays for -- 34% of the payload on its own."""
        self.assertNotIn("json.dumps(data_to_summarize, indent=2)", self.NODE_SRC)
        self.assertIn('separators=(",", ":")', self.NODE_SRC)

    def test_the_saving_is_logged_so_a_real_run_can_be_checked(self):
        self.assertIn("payload: %d -> %d chars", self.NODE_SRC)


if __name__ == "__main__":
    unittest.main(verbosity=2)
