"""Tests for turning a planned session into Garmin's workout JSON.

The reference values are not invented: they were read back from a real
workout in the athlete's Garmin account (workout 1671714912, "Tempo 2x10min
(por ritmo)"), which is the workout they showed as the standard the app's
output had to match.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    FormattedWorkoutStep,
    TargetType,
    WorkoutDay,
    WorkoutStep,
    expand_formatted_steps,
)
from services.garmin.workout_builder import (  # noqa: E402
    build_garmin_workout,
    compute_zone_config,
    pace_sec_per_km_to_m_per_s,
)

# The athlete's real zones, read off activity 24060063262.
REAL_HR_ZONES = {1: (123, 138), 2: (139, 152), 3: (153, 166), 4: (167, 180), 5: (181, None)}


def _day(steps, sport="run"):
    return WorkoutDay(
        date="2026-08-26", day_name_es="Miércoles", is_rest=False, sport=sport,
        focus="Tempo", purpose="Bloques de tempo controlados.",
        adaptation="Si estás cansado: un solo bloque.", total_duration_minutes=41.0,
        steps=steps,
    )


def _steps_of(workout):
    return workout["workoutSegments"][0]["workoutSteps"]


class PaceUnitConversion(unittest.TestCase):
    def test_seconds_per_km_becomes_metres_per_second(self):
        self.assertAlmostEqual(pace_sec_per_km_to_m_per_s(355.0), 2.817, places=3)  # 5:55/km
        self.assertAlmostEqual(pace_sec_per_km_to_m_per_s(340.0), 2.941, places=3)  # 5:40/km

    def test_faster_pace_is_a_larger_speed(self):
        self.assertGreater(pace_sec_per_km_to_m_per_s(300.0), pace_sec_per_km_to_m_per_s(400.0))

    def test_a_non_positive_pace_is_an_error_not_a_silent_zero(self):
        for bad in (0, -10):
            with self.assertRaises(ValueError):
                pace_sec_per_km_to_m_per_s(bad)


class PaceTargets(unittest.TestCase):
    def setUp(self):
        self.zones = compute_zone_config(lactate_threshold_hr=176, garmin_hr_zones=REAL_HR_ZONES)

    def test_pace_target_matches_the_reference_workout_exactly(self):
        """Garmin stored 2.82-2.94 m/s for a 5:40-5:55/km tempo block."""
        workout = build_garmin_workout(
            _day([
                WorkoutStep(
                    step_order=1, step_type="interval", duration_type="time", duration_value=600,
                    target_type=TargetType.PACE, target_low=340, target_high=355,
                    description="Tempo - 5:40-5:55/km",
                )
            ]),
            self.zones,
        )
        step = _steps_of(workout)[0]
        self.assertAlmostEqual(step["targetValueOne"], 2.82, places=2)
        self.assertAlmostEqual(step["targetValueTwo"], 2.94, places=2)
        self.assertIsNone(step["zoneNumber"])

    def test_slow_bound_lands_in_target_value_one_regardless_of_input_order(self):
        """Converting to speed flips the ends; the builder must re-order them."""
        for low, high in ((340, 355), (355, 340)):
            with self.subTest(low=low, high=high):
                workout = build_garmin_workout(
                    _day([
                        WorkoutStep(
                            step_order=1, step_type="interval", duration_type="time", duration_value=600,
                            target_type=TargetType.PACE, target_low=low, target_high=high,
                            description="Tempo",
                        )
                    ]),
                    self.zones,
                )
                step = _steps_of(workout)[0]
                self.assertLess(step["targetValueOne"], step["targetValueTwo"])

    def test_a_pace_target_is_never_uploaded_as_raw_seconds(self):
        """The old bug sent 270 sec/km straight through as '270 m/s'."""
        workout = build_garmin_workout(
            _day([
                WorkoutStep(
                    step_order=1, step_type="interval", duration_type="time", duration_value=600,
                    target_type=TargetType.PACE, target_low=270, target_high=280,
                    description="Rápido",
                )
            ]),
            self.zones,
        )
        step = _steps_of(workout)[0]
        for value in (step["targetValueOne"], step["targetValueTwo"]):
            self.assertLess(value, 12.0, "a human running speed in m/s is under 12")


class HeartRateTargets(unittest.TestCase):
    def setUp(self):
        self.zones = compute_zone_config(lactate_threshold_hr=176, garmin_hr_zones=REAL_HR_ZONES)

    def test_hr_steps_use_a_named_zone_so_the_watch_applies_the_athletes_own(self):
        """Sending a bpm range is how 'Zone 2' became 143 bpm instead of 139."""
        workout = build_garmin_workout(
            _day([
                WorkoutStep(
                    step_order=1, step_type="warmup", duration_type="time", duration_value=600,
                    target_type=TargetType.HR_ZONE, target_zone=2, description="10' Z2",
                )
            ]),
            self.zones,
        )
        step = _steps_of(workout)[0]
        self.assertEqual(step["zoneNumber"], 2)
        self.assertIsNone(step["targetValueOne"])
        self.assertIsNone(step["targetValueTwo"])
        self.assertEqual(step["targetType"]["workoutTargetTypeKey"], "heart.rate.zone")



class LaZonaLaResuelveElReloj(unittest.TestCase):
    """Un paso de pulso manda el NUMERO de zona, nunca un rango de bpm nuestro.

    La regla ya la cumple `_build_target`, pero justo encima suya vive la rama
    de rango explicito -- `if step.target_low is not None and step.target_high
    is not None` -- y nada impedia que un dia alguien la usara tambien para
    `hr_zone`. Con eso el atleta recibe en la muneca unos limites que no son
    los suyos: su reloj empieza la Zona 2 en 139 y una vez le llego empezando
    en 143.

    Verificado contra la cuenta real (actividad 24060063262): las zonas de este
    atleta son 123-138 / 139-152 / 153-166 / 167-180 / 181+.
    """

    def setUp(self):
        self.zones = compute_zone_config(lactate_threshold_hr=176, garmin_hr_zones=REAL_HR_ZONES)

    @staticmethod
    def _todos_los_pasos(nodos):
        """Aplana repeticiones: un RepeatGroupDTO anida sus pasos dentro."""
        for nodo in nodos:
            hijos = nodo.get("workoutSteps")
            if hijos:
                yield from LaZonaLaResuelveElReloj._todos_los_pasos(hijos)
            else:
                yield nodo

    def test_las_cinco_zonas_viajan_como_numero_y_sin_rango(self):
        for zona in (1, 2, 3, 4, 5):
            with self.subTest(zona=zona):
                workout = build_garmin_workout(
                    _day([
                        WorkoutStep(
                            step_order=1, step_type="warmup", duration_type="time",
                            duration_value=600, target_type=TargetType.HR_ZONE,
                            target_zone=zona, description=f"10' Z{zona}",
                        )
                    ]),
                    self.zones,
                )
                paso = _steps_of(workout)[0]
                self.assertEqual(paso["zoneNumber"], zona)
                self.assertIsNone(paso["targetValueOne"])
                self.assertIsNone(paso["targetValueTwo"])

    def test_ningun_paso_de_pulso_de_una_semana_real_lleva_bpm(self):
        """La garantia sobre un dia entero, por el camino REAL de produccion.

        Se construye con `FormattedWorkoutStep` y `expand_formatted_steps`
        porque es lo que corre de verdad: el modelo escribe la lista plana con
        `recovery_seconds`, y el arbol anidado lo monta el codigo. Montar aqui
        un `WorkoutStep` con `recovery_seconds` no probaria nada -- ese campo
        no existe en ese esquema y Pydantic lo tira sin decir nada.
        """
        pasos = expand_formatted_steps([
            FormattedWorkoutStep(
                step_order=1, step_type="warmup", duration_type="time", duration_value=600,
                target_type=TargetType.HR_ZONE, target_zone=2, description="10' Z2"),
            FormattedWorkoutStep(
                step_order=2, step_type="interval", duration_type="time", duration_value=180,
                repeat_count=4, recovery_seconds=120, recovery_target_zone=1,
                recovery_description="2' trote Z1", target_type=TargetType.PACE,
                target_low=340, target_high=355, description="4x(3' a 5:40-5:55/km, 2' rec)"),
            FormattedWorkoutStep(
                step_order=3, step_type="cooldown", duration_type="time", duration_value=600,
                target_type=TargetType.HR_ZONE, target_zone=1, description="10' Z1"),
        ])
        workout = build_garmin_workout(_day(pasos), self.zones)

        de_pulso = [
            p for p in self._todos_los_pasos(_steps_of(workout))
            if p["targetType"]["workoutTargetTypeKey"] == "heart.rate.zone"
        ]
        # calentamiento, la recuperacion DENTRO de la repeticion, y enfriamiento
        self.assertEqual(len(de_pulso), 3)
        for paso in de_pulso:
            self.assertIsNotNone(paso["zoneNumber"])
            self.assertIsNone(paso["targetValueOne"])
            self.assertIsNone(paso["targetValueTwo"])

    def test_un_paso_sin_objetivo_no_inventa_una_zona(self):
        """Un rodaje que vale igual en Z1 que en Z2 va sin objetivo, no en Z2.

        El reloj avisa cada vez que se sale de la zona prescrita, asi que
        mandar Z2 en un paso cuya intencion abarca Z1-Z2 le regaña al atleta
        por hacerlo bien. El rango se dice en la descripcion.
        """
        workout = build_garmin_workout(
            _day([
                WorkoutStep(
                    step_order=1, step_type="interval", duration_type="time",
                    duration_value=3600, target_type=TargetType.NONE,
                    description="60' suave, Z1-Z2, sin forzar",
                )
            ]),
            self.zones,
        )
        paso = _steps_of(workout)[0]
        self.assertEqual(paso["targetType"]["workoutTargetTypeKey"], "no.target")
        self.assertIsNone(paso["zoneNumber"])
        self.assertIsNone(paso["targetValueOne"])
        self.assertIn("Z1-Z2", paso["description"])


class ZoneConfigSource(unittest.TestCase):
    def test_real_garmin_zones_beat_the_formula(self):
        zones = compute_zone_config(lactate_threshold_hr=176, garmin_hr_zones=REAL_HR_ZONES)
        self.assertEqual(zones.hr_zones[2], (139.0, 152.0))

    def test_formula_is_used_only_when_real_zones_are_unavailable(self):
        zones = compute_zone_config(lactate_threshold_hr=176)
        self.assertEqual(zones.hr_zones[2], (143, 157))  # the old, wrong-for-this-athlete values

    def test_open_ended_top_zone_gets_a_finite_upper_bound(self):
        zones = compute_zone_config(garmin_hr_zones=REAL_HR_ZONES)
        low, high = zones.hr_zones[5]
        self.assertEqual(low, 181.0)
        self.assertIsNotNone(high)
        self.assertGreater(high, low)


class ReferenceWorkoutEndToEnd(unittest.TestCase):
    def test_rebuilding_the_athletes_reference_tempo_session(self):
        zones = compute_zone_config(lactate_threshold_hr=176, garmin_hr_zones=REAL_HR_ZONES)
        workout = build_garmin_workout(
            _day([
                WorkoutStep(step_order=1, step_type="warmup", duration_type="time", duration_value=600,
                            target_type=TargetType.HR_ZONE, target_zone=2, description="10' calentamiento Z2"),
                WorkoutStep(step_order=2, step_type="interval", duration_type="time", duration_value=600,
                            target_type=TargetType.PACE, target_low=340, target_high=355,
                            description="Tempo bloque 1 - 5:40-5:55/km"),
                WorkoutStep(step_order=3, step_type="recovery", duration_type="time", duration_value=180,
                            target_type=TargetType.HR_ZONE, target_zone=1, description="3' recuperación Z1"),
                WorkoutStep(step_order=4, step_type="interval", duration_type="time", duration_value=600,
                            target_type=TargetType.PACE, target_low=340, target_high=355,
                            description="Tempo bloque 2 - 5:40-5:55/km"),
                WorkoutStep(step_order=5, step_type="cooldown", duration_type="time", duration_value=480,
                            target_type=TargetType.HR_ZONE, target_zone=1, description="8' enfriamiento Z1"),
            ]),
            zones,
        )
        steps = _steps_of(workout)
        self.assertEqual(len(steps), 5)
        self.assertEqual(
            [s["stepType"]["stepTypeKey"] for s in steps],
            ["warmup", "interval", "recovery", "interval", "cooldown"],
        )
        # pace.zone, not speed.zone: Garmin accepts both and stores whichever
        # it is given, but only pace.zone renders as "Ritmo 5:45 /km" -- the
        # way a runner reads a target, and what the reference workout uses.
        self.assertEqual(
            [s["targetType"]["workoutTargetTypeKey"] for s in steps],
            ["heart.rate.zone", "pace.zone", "heart.rate.zone", "pace.zone", "heart.rate.zone"],
        )
        for order in (2, 4):
            step = steps[order - 1]
            self.assertAlmostEqual(step["targetValueOne"], 2.82, places=2)
            self.assertAlmostEqual(step["targetValueTwo"], 2.94, places=2)


if __name__ == "__main__":
    unittest.main(verbosity=2)
