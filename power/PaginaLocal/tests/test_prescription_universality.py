"""The prescription must work for any athlete, not just the one it was built with.

The rest of the suite is anchored to one real account, which is the right way
to prove the numbers are correct -- but it cannot prove they GENERALISE. These
cases exist to catch the opposite failure: logic that quietly assumes a
middle-of-the-pack male runner with a full Garmin history.

Every athlete here is deliberately far from that: an elite woman, an absolute
beginner, a 65-year-old with a low max heart rate, a cyclist who never runs, a
swimmer, and a brand-new account with no data at all.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.prescription import build_prescription, render_prescription_card  # noqa: E402
from services.garmin.models import (  # noqa: E402
    GarminData,
    GarminHrZones,
    RacePrediction,
    UserProfile,
)

_PACE_CLASS_ORDER = ["repeticiones", "vo2max", "umbral_tempo", "maraton", "facil", "recuperacion"]


def _zone_table(max_hr: int) -> dict[int, tuple[int, int | None]]:
    """A five-zone table scaled to one athlete's own max HR."""
    lows = [round(max_hr * pct) for pct in (0.50, 0.60, 0.70, 0.80, 0.90)]
    return {i + 1: (lows[i], (lows[i + 1] - 1) if i < 4 else None) for i in range(5)}


def _predictions(**kwargs) -> list[RacePrediction]:
    distances = {"five": ("5K", 5000.0), "ten": ("10K", 10000.0),
                 "half": ("half_marathon", 21097.5), "full": ("marathon", 42195.0)}
    return [
        RacePrediction(distances[k][0], distances[k][1], seconds)
        for k, seconds in kwargs.items() if seconds
    ]


def _athlete(*, threshold_pace=None, lthr=None, max_hr=None, sport="run", predictions=None, profile=None):
    return (
        GarminData(
            user_profile=UserProfile(
                lactate_threshold_speed=(1000.0 / threshold_pace) if threshold_pace else None,
                lactate_threshold_heart_rate=lthr,
            ),
            garmin_hr_zones=GarminHrZones(by_sport={sport: _zone_table(max_hr)}) if max_hr else None,
            race_predictions=predictions or [],
        ),
        profile or {},
    )


class BandsStayCoherentForEveryAthlete(unittest.TestCase):
    """Whatever the athlete's level, the table must read fast -> slow."""

    ATHLETES = {
        "elite woman, 2:35 marathon": _athlete(
            threshold_pace=205, lthr=172, max_hr=192,
            predictions=_predictions(five=980, ten=2050, half=4320, full=9300)),
        "absolute beginner, 40min 5K": _athlete(
            lthr=165, max_hr=185,
            predictions=_predictions(five=2400, ten=5100, half=11400, full=24600)),
        "65-year-old, low max HR": _athlete(
            threshold_pace=420, lthr=142, max_hr=155,
            predictions=_predictions(five=1980, ten=4200, half=9600, full=21000)),
        "runner with no race predictions at all": _athlete(
            threshold_pace=300, lthr=170, max_hr=190),
    }

    def test_every_athlete_gets_a_monotonic_pace_table(self):
        for name, (garmin_data, profile) in self.ATHLETES.items():
            with self.subTest(athlete=name):
                bands = build_prescription(garmin_data, profile).pace_bands
                self.assertTrue(bands, f"{name} got no pace bands")
                previous = None
                for key in _PACE_CLASS_ORDER:
                    band = bands[key]
                    self.assertLessEqual(band.fast_sec_per_km, band.slow_sec_per_km)
                    if previous is not None:
                        self.assertGreaterEqual(band.fast_sec_per_km, previous)
                    previous = band.fast_sec_per_km

    def test_threshold_is_never_faster_than_the_athletes_own_5k_pace(self):
        for name, (garmin_data, profile) in self.ATHLETES.items():
            with self.subTest(athlete=name):
                prescription = build_prescription(garmin_data, profile)
                five_k = next(
                    (p.pace_sec_per_km for p in prescription.race_predictions if p.distance_key == "5K"),
                    None,
                )
                if five_k and prescription.threshold_pace_sec_per_km:
                    self.assertGreaterEqual(prescription.threshold_pace_sec_per_km, five_k)

    def test_paces_actually_differ_between_athletes(self):
        """Guards against a table that is really a hardcoded constant."""
        elite = build_prescription(*self.ATHLETES["elite woman, 2:35 marathon"])
        beginner = build_prescription(*self.ATHLETES["absolute beginner, 40min 5K"])
        self.assertLess(
            elite.pace_bands["umbral_tempo"].fast_sec_per_km,
            beginner.pace_bands["umbral_tempo"].fast_sec_per_km,
        )


class NonRunners(unittest.TestCase):
    def test_a_cyclist_with_an_ftp_gets_power_bands_without_any_running_data(self):
        garmin_data, _ = _athlete(lthr=168, max_hr=180, sport="bike")
        prescription = build_prescription(garmin_data, {"ftp_watts": 285})
        self.assertTrue(prescription.has_power_data)
        self.assertFalse(prescription.has_pace_data)
        self.assertEqual(prescription.power_bands["umbral"], (259, 299))  # 91-105% of FTP

    def test_power_bands_scale_with_the_athletes_own_ftp(self):
        garmin_data, _ = _athlete(lthr=168, max_hr=180, sport="bike")
        weak = build_prescription(garmin_data, {"ftp_watts": 150}).power_bands["umbral"]
        strong = build_prescription(garmin_data, {"ftp_watts": 400}).power_bands["umbral"]
        self.assertLess(weak[1], strong[0])

    def test_a_cyclists_card_says_power_not_pace(self):
        garmin_data, _ = _athlete(lthr=168, max_hr=180, sport="bike")
        card = render_prescription_card(build_prescription(garmin_data, {"ftp_watts": 285}))
        self.assertIn("objetivo de POTENCIA en watts", card)
        self.assertIn("No inventes rangos de ritmo", card)

    def test_a_cyclist_without_an_ftp_is_told_to_use_heart_rate(self):
        garmin_data, _ = _athlete(lthr=168, max_hr=180, sport="bike")
        card = render_prescription_card(build_prescription(garmin_data, {}))
        self.assertIn("sin FTP conocido", card)
        self.assertIn("No uses ritmo en bici", card)

    def test_a_swimmer_gets_their_own_zones_and_an_honest_swim_rule(self):
        garmin_data, _ = _athlete(lthr=170, max_hr=178, sport="swim")
        prescription = build_prescription(garmin_data, {})
        self.assertTrue(prescription.has_hr_zones)
        self.assertIn("swim", prescription.hr_zones.sports)
        self.assertIn("Natación", render_prescription_card(prescription))


class NoDataAtAll(unittest.TestCase):
    def test_a_brand_new_account_invents_nothing(self):
        prescription = build_prescription(GarminData(), {})
        self.assertFalse(prescription.has_pace_data)
        self.assertFalse(prescription.has_power_data)
        self.assertFalse(prescription.has_hr_zones)
        self.assertTrue(prescription.warnings)

    def test_the_card_still_gives_the_model_usable_instructions(self):
        card = render_prescription_card(build_prescription(GarminData(), {}))
        self.assertIn("REGLA DE MODALIDAD", card)
        self.assertIn("No inventes rangos de ritmo", card)


class SportSpecificZones(unittest.TestCase):
    def test_walking_is_not_claimed_to_share_the_running_zone_table(self):
        """Which table Garmin gives walking is an account setting we can't see."""
        from services.garmin.data_extractor import DataExtractor

        self.assertEqual(DataExtractor._canonical_sport("walking"), "other")
        self.assertEqual(DataExtractor._canonical_sport("hiking"), "other")

    def test_sport_detection_covers_garmins_common_activity_types(self):
        from services.garmin.data_extractor import DataExtractor

        cases = {
            "running": "run", "treadmill_running": "run", "trail_running": "run",
            "cycling": "bike", "road_biking": "bike", "indoor_cycling": "bike",
            "lap_swimming": "swim", "open_water_swimming": "swim",
            # La fuerza es un deporte que la app PRESCRIBE, así que reconocerla
            # al volver no es un lujo: mientras cayó en "other", el bloque de
            # hábitos le dijo al planificador que un atleta con seis sesiones
            # de fuerza al mes no hacía ninguna.
            "strength_training": "strength",
            "functional_strength_training": "strength",
            "": "other", None: "other",
        }
        for activity_type, expected in cases.items():
            with self.subTest(activity_type=activity_type):
                self.assertEqual(DataExtractor._canonical_sport(activity_type), expected)

    def test_yoga_and_pilates_are_not_claimed_as_strength(self):
        """Son otro estímulo. Nadie que declara un bloque de "fuerza" en su
        perfil quiere decir yoga, y meterlos ahí haría que el contraste entre
        lo declarado y lo hecho mintiera en la dirección contraria."""
        from services.garmin.data_extractor import DataExtractor

        for activity_type in ("yoga", "pilates"):
            with self.subTest(activity_type=activity_type):
                self.assertEqual(DataExtractor._canonical_sport(activity_type), "other")


if __name__ == "__main__":
    unittest.main(verbosity=2)
