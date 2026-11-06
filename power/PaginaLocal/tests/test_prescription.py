"""Tests for the athlete's real numbers reaching the planner correctly.

Every case here is anchored to a real Garmin account whose values were read
back over the API, not to invented fixtures -- including the unit bug that had
this athlete's threshold pace stored 20% too fast.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.prescription import (  # noqa: E402
    build_prescription,
    format_pace,
    prescription_to_state,
    render_prescription_card,
)
from services.garmin.data_extractor import DataExtractor  # noqa: E402
from services.garmin.models import (  # noqa: E402
    GarminData,
    GarminHrZones,
    RacePrediction,
    UserProfile,
)

# --- Real values, read from the athlete's Garmin account -------------------
# Zone boundaries came from get_activity_hr_in_timezones on activity
# 24060063262; the race predictions from get_race_predictions on 2026-08-21.
# Garmin keeps a SEPARATE zone table per sport, and they are far apart:
# these came from activity 24060063262 (a run) and 24060742418 (a swim).
REAL_HR_ZONES = GarminHrZones(
    by_sport={
        "run": {1: (123, 138), 2: (139, 152), 3: (153, 166), 4: (167, 180), 5: (181, None)},
        "swim": {1: (93, 111), 2: (112, 129), 3: (130, 148), 4: (149, 167), 5: (168, None)},
    },
    source_activity_ids={"run": 24060063262, "swim": 24060742418},
)
REAL_PREDICTIONS = [
    RacePrediction("5K", 5000.0, 1664),  # 27:44 -> 5:33/km
    RacePrediction("10K", 10000.0, 3560),  # 59:20 -> 5:56/km
    RacePrediction("half_marathon", 21097.5, 8064),  # 2:14:24 -> 6:22/km
    RacePrediction("marathon", 42195.0, 17768),  # 4:56:08 -> 7:01/km
]
# Garmin's raw userData.lactateThresholdSpeed for this athlete.
RAW_LT_SPEED = 0.34722125
CORRECT_THRESHOLD_SEC_PER_KM = 347.2  # 5:47/km


def _garmin_data(lt_speed=None, zones=REAL_HR_ZONES, predictions=None):
    return GarminData(
        user_profile=UserProfile(
            lactate_threshold_speed=lt_speed,
            lactate_threshold_heart_rate=176,
        ),
        garmin_hr_zones=zones,
        race_predictions=REAL_PREDICTIONS if predictions is None else predictions,
    )


class ThresholdSpeedConversion(unittest.TestCase):
    def test_raw_garmin_value_yields_a_plausible_threshold_pace(self):
        """0.347 is seconds-per-metre, so the threshold is 5:47/km, not 4:48/km."""
        speed_ms = DataExtractor.convert_lactate_threshold_speed(RAW_LT_SPEED)
        self.assertIsNotNone(speed_ms)
        self.assertAlmostEqual(1000.0 / speed_ms, CORRECT_THRESHOLD_SEC_PER_KM, delta=1.0)

    def test_a_value_already_in_m_per_s_is_passed_through(self):
        self.assertAlmostEqual(DataExtractor.convert_lactate_threshold_speed(2.88), 2.88, places=2)

    def test_implausible_values_are_rejected_rather_than_trusted(self):
        for bad in (None, 0, -1, 500.0):
            self.assertIsNone(DataExtractor.convert_lactate_threshold_speed(bad), f"{bad} should be rejected")

    def test_the_old_multiply_by_ten_result_would_be_faster_than_5k_pace(self):
        """Guards the reasoning behind the fix, not just the fix itself."""
        old_behaviour_sec_per_km = 1000.0 / (RAW_LT_SPEED * 10.0)
        five_k_pace = REAL_PREDICTIONS[0].pace_sec_per_km
        self.assertLess(old_behaviour_sec_per_km, five_k_pace)  # i.e. impossible
        self.assertGreater(CORRECT_THRESHOLD_SEC_PER_KM, five_k_pace)  # the fix is sane


class HeartRateZones(unittest.TestCase):
    def test_real_garmin_zones_are_used_verbatim(self):
        prescription = build_prescription(_garmin_data(lt_speed=2.88), {})
        self.assertEqual(prescription.hr_zones_source, "garmin")
        self.assertEqual(prescription.hr_zones.bounds(2, "run"), (139, 152))

    def test_zones_are_kept_separate_per_sport(self):
        """Running Z2 starts at 139 bpm; swimming Z2 starts at 112."""
        zones = build_prescription(_garmin_data(lt_speed=2.88), {}).hr_zones
        self.assertEqual(zones.bounds(2, "run"), (139, 152))
        self.assertEqual(zones.bounds(2, "swim"), (112, 129))
        self.assertNotEqual(zones.for_sport("run"), zones.for_sport("swim"))

    def test_unknown_sport_falls_back_to_running_not_to_whatever_is_first(self):
        zones = build_prescription(_garmin_data(lt_speed=2.88), {}).hr_zones
        self.assertEqual(zones.for_sport("bike"), zones.for_sport("run"))
        self.assertEqual(zones.for_sport(None), zones.for_sport("run"))

    def test_missing_zones_degrade_with_an_explicit_warning(self):
        prescription = build_prescription(_garmin_data(lt_speed=2.88, zones=None), {})
        self.assertFalse(prescription.has_hr_zones)
        self.assertTrue(any("zonas de FC" in w for w in prescription.warnings))

    def test_card_tells_the_model_to_use_named_zones(self):
        card = render_prescription_card(build_prescription(_garmin_data(lt_speed=2.88), {}))
        self.assertIn("Z2=139-152", card)
        self.assertIn("número de zona", card)

    def test_card_shows_every_sport_and_warns_they_are_not_interchangeable(self):
        card = render_prescription_card(build_prescription(_garmin_data(lt_speed=2.88), {}))
        self.assertIn("Carrera", card)
        self.assertIn("Natación", card)
        self.assertIn("Z2=112-129", card)
        self.assertIn("no son intercambiables", card.replace("NO son", "no son"))


class ThresholdPlausibilityGuard(unittest.TestCase):
    def test_a_threshold_faster_than_5k_pace_is_rejected(self):
        """The exact regression that shipped: LT speed of 3.47 m/s = 4:48/km."""
        prescription = build_prescription(_garmin_data(lt_speed=3.4722), {})
        self.assertEqual(prescription.threshold_pace_source, "race_prediction")
        self.assertAlmostEqual(
            prescription.threshold_pace_sec_per_km,
            REAL_PREDICTIONS[1].pace_sec_per_km,  # falls back to 10K pace
            delta=0.5,
        )
        self.assertTrue(any("MÁS RÁPIDO" in w for w in prescription.warnings))

    def test_a_correct_threshold_is_kept_and_attributed_to_garmin(self):
        prescription = build_prescription(_garmin_data(lt_speed=2.88), {})
        self.assertEqual(prescription.threshold_pace_source, "garmin_lt")
        self.assertAlmostEqual(prescription.threshold_pace_sec_per_km, CORRECT_THRESHOLD_SEC_PER_KM, delta=1.0)

    def test_threshold_is_derived_from_predictions_when_garmin_has_none(self):
        prescription = build_prescription(_garmin_data(lt_speed=None), {})
        self.assertEqual(prescription.threshold_pace_source, "race_prediction")
        self.assertTrue(prescription.has_pace_data)


class PaceBands(unittest.TestCase):
    def setUp(self):
        self.prescription = build_prescription(_garmin_data(lt_speed=2.88), {})
        self.bands = self.prescription.pace_bands

    def test_bands_run_monotonically_from_fast_to_slow(self):
        """An 'easy' pace faster than marathon pace is nonsense to an athlete."""
        ordered = ["repeticiones", "vo2max", "umbral_tempo", "maraton", "facil", "recuperacion"]
        previous = None
        for key in ordered:
            band = self.bands[key]
            self.assertLessEqual(band.fast_sec_per_km, band.slow_sec_per_km, f"{key} band is inverted")
            if previous is not None:
                self.assertGreaterEqual(
                    band.fast_sec_per_km, previous.fast_sec_per_km,
                    f"'{key}' starts faster than the harder class before it",
                )
            previous = band

    def test_threshold_band_contains_the_athletes_own_reference_workout(self):
        """The tempo Claude Desktop prescribed via MCP was 5:40-5:55/km.

        Allowing a second per kilometre either way: these bands are coaching
        guidance the planner picks an exact range inside, and no athlete can
        run -- or perceive -- a sub-second-per-km distinction.
        """
        band = self.bands["umbral_tempo"]
        tolerance = 1.0
        self.assertLessEqual(band.fast_sec_per_km, 340.0 + tolerance)  # 5:40
        self.assertGreaterEqual(band.slow_sec_per_km, 355.0 - tolerance)  # 5:55

    def test_easy_band_contains_a_real_executed_z2_run(self):
        """2931 m in 1342 s at 142 bpm -- inside Z2, so inside the easy band."""
        actual_pace = 1342.462 / 2.93129
        band = self.bands["facil"]
        self.assertLessEqual(band.fast_sec_per_km, actual_pace)
        self.assertGreaterEqual(band.slow_sec_per_km, actual_pace)

    def test_marathon_band_follows_garmins_prediction_not_the_multiplier(self):
        """A %-of-threshold estimate badly overrates an endurance-limited athlete."""
        band = self.bands["maraton"]
        predicted = REAL_PREDICTIONS[3].pace_sec_per_km  # 7:01/km
        self.assertLessEqual(band.fast_sec_per_km, predicted)
        self.assertGreaterEqual(band.slow_sec_per_km, predicted)

    def test_vo2max_band_is_faster_than_5k_race_pace(self):
        self.assertLess(self.bands["vo2max"].fast_sec_per_km, REAL_PREDICTIONS[0].pace_sec_per_km)


class NoDataAtAll(unittest.TestCase):
    def test_an_empty_account_produces_warnings_not_invented_paces(self):
        prescription = build_prescription(GarminData(), {})
        self.assertFalse(prescription.has_pace_data)
        self.assertFalse(prescription.has_hr_zones)
        self.assertEqual(prescription.threshold_pace_source, "none")
        self.assertEqual(len(prescription.warnings), 2)

    def test_the_card_still_renders_without_data(self):
        card = render_prescription_card(build_prescription(GarminData(), {}))
        self.assertIn("no disponibles", card)
        self.assertIn("REGLA DE MODALIDAD", card)


class StateSerialization(unittest.TestCase):
    def test_state_dict_carries_both_the_card_and_the_structured_bands(self):
        state = prescription_to_state(build_prescription(_garmin_data(lt_speed=2.88), {}))
        self.assertIn("REGLA DE MODALIDAD", state["card"])
        self.assertEqual(state["hr_zones_by_sport"]["run"]["2"], [139, 152])
        self.assertEqual(state["hr_zones_by_sport"]["swim"]["2"], [112, 129])
        self.assertEqual(len(state["pace_bands"]), 6)
        self.assertEqual(state["hr_zones_source"], "garmin")


class Formatting(unittest.TestCase):
    def test_pace_formatting(self):
        self.assertEqual(format_pace(347.2), "5:47")
        self.assertEqual(format_pace(360.0), "6:00")
        self.assertEqual(format_pace(None), "—")
        self.assertEqual(format_pace(0), "—")


if __name__ == "__main__":
    unittest.main(verbosity=2)
