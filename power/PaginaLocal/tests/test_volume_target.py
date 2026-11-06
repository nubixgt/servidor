"""Tests for the weekly volume target (Plan Maestro v4 Track F).

A ramp cap is easy to get wrong in a way that quietly harms the athlete: cap
against last week alone and a single deload/taper/illness week pins them below
the volume they had already proven they tolerate, and because next week's cap
is computed from *that* reduced number, the error compounds downward forever.
These tests pin the adaptive properties, not the arithmetic.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    compute_weekly_volume_target,
)
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import (  # noqa: E402
    validate_weekly_plan,
)


def _baseline(chronic=None, previous=None, ceiling=None):
    return {
        "chronic_weekly_hours": chronic,
        "last_7d_actual_hours": None,
        "previous_week_planned_hours": previous,
        "available_hours_ceiling": ceiling,
    }


def _position(hours_target=None, is_deload=False):
    return {
        "week_number": 2, "phase_name": "Base", "is_deload": is_deload,
        "weekly_hours_target": hours_target, "intensity_emphasis": "",
    }


class BaselineIsNeverLastWeekAlone(unittest.TestCase):
    """The property that matters most: an athlete coming off a light week must
    be allowed back to the load they already tolerate."""

    def test_the_real_case_a_light_week_does_not_pin_the_next_one(self):
        """Verified real generation: week 1 was 3.6h, week 2 came out 6.0h.
        Read against last week alone that is a reckless +68% jump; read against
        this athlete's established ~6h/week chronic load it is simply
        resumption. The target must reflect the second reading."""
        target = compute_weekly_volume_target(
            _baseline(chronic=6.0, previous=3.6), _position(),
        )
        self.assertIsNotNone(target)
        self.assertAlmostEqual(target["baseline_hours"], 6.0)
        self.assertGreaterEqual(target["target_hours"], 6.0)

    def test_a_bigger_previous_plan_wins_over_a_lower_chronic_load(self):
        target = compute_weekly_volume_target(
            _baseline(chronic=4.0, previous=5.0), _position(),
        )
        self.assertAlmostEqual(target["baseline_hours"], 5.0)

    def test_the_cap_does_not_compound_downward_across_weeks(self):
        """Simulates three consecutive light weeks: as long as chronic load
        holds, the target must not ratchet down each time."""
        chronic = 6.0
        targets = [
            compute_weekly_volume_target(_baseline(chronic=chronic, previous=p), _position())["target_hours"]
            for p in (3.0, 2.5, 2.0)
        ]
        self.assertEqual(len(set(targets)), 1, "target shrank as the previous week shrank")
        self.assertGreaterEqual(targets[0], chronic)


class SeasonPlanAndPhaseWin(unittest.TestCase):
    def test_a_lower_phase_target_overrides_the_progression_allowance(self):
        """A stabilisation phase deliberately capped at 5h is a coaching
        decision, not something a generic +10% should override."""
        target = compute_weekly_volume_target(
            _baseline(chronic=6.0), _position(hours_target=5.0),
        )
        self.assertAlmostEqual(target["target_hours"], 5.0)

    def test_a_higher_phase_target_does_not_license_a_bigger_jump(self):
        target = compute_weekly_volume_target(
            _baseline(chronic=5.0), _position(hours_target=9.0),
        )
        self.assertAlmostEqual(target["target_hours"], 5.5)

    def test_a_deload_week_moves_the_target_down_not_up(self):
        normal = compute_weekly_volume_target(_baseline(chronic=6.0), _position())
        deload = compute_weekly_volume_target(_baseline(chronic=6.0), _position(is_deload=True))
        self.assertLess(deload["target_hours"], normal["target_hours"])
        self.assertLess(deload["target_hours"], 6.0)
        self.assertTrue(deload["is_deload"])


class Guards(unittest.TestCase):
    def test_a_declared_hours_number_no_longer_governs_the_target(self):
        """Hasta la v1.2 esta prueba exigia que las horas que el atleta
        escribia en su perfil fueran un tope. Ese campo ya no existe: no es
        una pregunta que el atleta pueda contestar bien, y su defecto de 8.0
        llegaba al prompt como un dato medido. Si un baseline viejo todavia lo
        trae, se ignora -- el tope sale de su carga real."""
        con = compute_weekly_volume_target(_baseline(chronic=8.0, ceiling=5.0), _position())
        sin = compute_weekly_volume_target(_baseline(chronic=8.0), _position())
        self.assertEqual(con["target_hours"], sin["target_hours"])

    def test_no_real_data_produces_no_target_rather_than_an_invented_one(self):
        self.assertIsNone(compute_weekly_volume_target(_baseline(), _position()))
        self.assertIsNone(compute_weekly_volume_target(None, None))

    def test_a_phase_target_alone_is_enough_to_anchor_a_new_athlete(self):
        target = compute_weekly_volume_target(_baseline(), _position(hours_target=4.0))
        self.assertIsNotNone(target)
        self.assertAlmostEqual(target["target_hours"], 4.0)

    def test_every_target_carries_a_rationale_for_the_prompt(self):
        target = compute_weekly_volume_target(_baseline(chronic=6.0), _position())
        self.assertTrue(target["rationale"].strip())


def _run_day(date: str, minutes: float) -> WorkoutDay:
    return WorkoutDay(
        date=date, day_name_es="Lunes", is_rest=False, sport="run",
        focus="Facil", purpose="Rodaje.", adaptation="-",
        total_duration_minutes=minutes,
        steps=[WorkoutStep(
            step_order=1, step_type="main", duration_type="time",
            duration_value=minutes * 60, repeat_count=1,
            target_type=TargetType.HR_ZONE, target_zone=2, description=f"{minutes:.0f}' Z2",
        )],
    )


class TheValidatorNoLongerJudgesTheWeeksVolume(unittest.TestCase):
    """Aqui vivian las pruebas del recorte por horas (`_apply_weekly_hours_fallback`),
    que acortaba todas las sesiones de una semana que pasaba su presupuesto.

    Se retiro en la v1.2, junto con su gemelo por carga: cuanto entrena un
    atleta es la decision de entrenador por excelencia, y la toma un modelo que
    tiene delante su historia y como respondio. El validador solo tenia una
    estimacion con ruido, y acortaba sesiones que podian ser exactamente las
    que ese atleta necesitaba y sabia hacer.
    """

    def test_a_long_week_is_not_a_violation(self):
        payload = WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-09-02", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[
                _run_day("2026-09-02", 180), _run_day("2026-09-03", 180),
            ])],
        )
        # El presupuesto sigue viajando para el guardarrail de ACWR; ya no
        # convierte una semana larga en una violacion.
        report = validate_weekly_plan(payload, {"weekly_hours_budget": 1.0}, [])
        tipos = {v["type"] for v in report["violations"]}
        self.assertNotIn("weekly_hours_exceeded", tipos)
        self.assertNotIn("weekly_load_exceeded", tipos)


if __name__ == "__main__":
    unittest.main(verbosity=2)
