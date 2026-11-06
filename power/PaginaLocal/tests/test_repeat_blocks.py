"""Tests for repeat blocks -- the fix for intervals with no recovery between them.

Before repeat blocks, work and recovery could only be written as two sibling
steps each marked "repeat N times". Garmin faithfully built that as N hard reps
back to back followed by N easy ones, so the athlete started the next
repetition the instant the previous one ended. These tests pin the correct
shape down.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

import pydantic

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from pydantic import ValidationError  # noqa: E402

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    WORKOUT_FORMATTER_USER_PROMPT,
)
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    FormattedWeekBlock,
    FormattedWorkoutDay,
    FormattedWorkoutStep,
    TargetType,
    WeekBlock,
    WorkoutDay,
    WorkoutStep,
    expand_formatted_steps,
)
from services.ai.langgraph.validation.plan_validator import (  # noqa: E402
    _day_duration_minutes,
    _walk_steps,
)
from services.garmin.workout_builder import build_garmin_workout, compute_zone_config  # noqa: E402

REAL_HR_ZONES = {1: (123, 138), 2: (139, 152), 3: (153, 166), 4: (167, 180), 5: (181, None)}


def _zones():
    return compute_zone_config(lactate_threshold_hr=176, garmin_hr_zones=REAL_HR_ZONES)


def _interval_day(repeat_count=5):
    """5 x (3' at threshold pace + 2' jog), with warmup and cooldown."""
    return WorkoutDay(
        date="2026-09-02", day_name_es="Miércoles", is_rest=False, sport="run",
        focus="Intervalos", purpose="Estímulo de umbral.", adaptation="Si estás cansado: 3 reps.",
        steps=[
            WorkoutStep(step_order=1, step_type="warmup", duration_type="time", duration_value=600,
                        target_type=TargetType.HR_ZONE, target_zone=2, description="10' calentamiento Z2"),
            WorkoutStep(
                step_order=2, step_type="repeat", repeat_count=repeat_count,
                description=f"{repeat_count}x(3' a 5:40-5:55/km, 2' rec)",
                substeps=[
                    WorkoutStep(step_order=1, step_type="interval", duration_type="time", duration_value=180,
                                target_type=TargetType.PACE, target_low=340, target_high=355,
                                description="3' a 5:40-5:55/km"),
                    WorkoutStep(step_order=2, step_type="recovery", duration_type="time", duration_value=120,
                                target_type=TargetType.HR_ZONE, target_zone=1, description="2' trote Z1"),
                ],
            ),
            WorkoutStep(step_order=3, step_type="cooldown", duration_type="time", duration_value=480,
                        target_type=TargetType.HR_ZONE, target_zone=1, description="8' enfriamiento Z1"),
        ],
    )


def _flat(workout):
    return workout["workoutSegments"][0]["workoutSteps"]


class SchemaShape(unittest.TestCase):
    def test_a_repeat_block_needs_at_least_two_rounds(self):
        with self.assertRaises(pydantic.ValidationError):
            WorkoutStep(
                step_order=1, step_type="repeat", repeat_count=1, description="x",
                substeps=[WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                                      duration_value=60, description="y")],
            )

    def test_repeat_step_type_without_substeps_is_rejected(self):
        with self.assertRaises(pydantic.ValidationError):
            WorkoutStep(step_order=1, step_type="repeat", repeat_count=4, description="x")

    def test_a_normal_step_needs_a_duration_type(self):
        with self.assertRaises(pydantic.ValidationError):
            WorkoutStep(step_order=1, step_type="interval", duration_value=300, description="x")

    def test_walk_yields_nested_steps(self):
        day = _interval_day()
        self.assertEqual(len(day.steps), 3)
        self.assertEqual(len(_walk_steps(day.steps)), 5)  # 3 top-level + 2 nested


class UnitGuard(unittest.TestCase):
    """A pace written in the wrong unit is not a judgement call, it's a bug."""

    def _step(self, low, high, target_type=TargetType.PACE):
        return WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                           duration_value=180, target_type=target_type,
                           target_low=low, target_high=high, description="x")

    def test_pace_in_minutes_per_km_is_rejected(self):
        with self.assertRaises(pydantic.ValidationError):
            self._step(5.40, 5.55)  # minutes, not seconds

    def test_pace_in_metres_per_second_is_rejected(self):
        with self.assertRaises(pydantic.ValidationError):
            self._step(2.82, 2.94)  # already converted, wrong field

    def test_a_real_pace_range_in_seconds_is_accepted(self):
        step = self._step(340, 355)
        self.assertEqual(step.target_low, 340)

    def test_an_impossible_heart_rate_is_rejected(self):
        with self.assertRaises(pydantic.ValidationError):
            self._step(4, 5, target_type=TargetType.HR_ZONE)  # zone numbers, not bpm

    def test_plausible_bpm_is_accepted(self):
        self.assertEqual(self._step(139, 152, target_type=TargetType.HR_ZONE).target_low, 139)


class GarminStructure(unittest.TestCase):
    def setUp(self):
        self.steps = _flat(build_garmin_workout(_interval_day(), _zones()))

    def test_the_recovery_lives_inside_the_repeat_group(self):
        group = next(s for s in self.steps if s["type"] == "RepeatGroupDTO")
        kinds = [c["stepType"]["stepTypeKey"] for c in group["workoutSteps"]]
        self.assertEqual(kinds, ["interval", "recovery"])

    def test_there_is_exactly_one_repeat_group_not_two(self):
        """The old shape produced two: N hard reps, then N easy ones."""
        self.assertEqual(sum(1 for s in self.steps if s["type"] == "RepeatGroupDTO"), 1)

    def test_the_group_carries_the_iteration_count_and_condition(self):
        group = next(s for s in self.steps if s["type"] == "RepeatGroupDTO")
        self.assertEqual(group["numberOfIterations"], 5)
        self.assertEqual(group["endConditionValue"], 5)
        # Omitting conditionTypeId makes Garmin silently corrupt the count.
        self.assertEqual(group["endCondition"]["conditionTypeId"], 7)
        self.assertEqual(group["endCondition"]["conditionTypeKey"], "iterations")

    def test_step_order_is_globally_sequential_across_the_flattened_tree(self):
        orders = []

        def collect(steps):
            for step in steps:
                orders.append(step["stepOrder"])
                collect(step.get("workoutSteps", []))

        collect(self.steps)
        self.assertEqual(orders, list(range(1, len(orders) + 1)))

    def test_children_share_the_groups_child_step_id(self):
        group = next(s for s in self.steps if s["type"] == "RepeatGroupDTO")
        for child in group["workoutSteps"]:
            self.assertEqual(child["childStepId"], group["childStepId"])

    def test_each_repetition_keeps_its_own_target(self):
        group = next(s for s in self.steps if s["type"] == "RepeatGroupDTO")
        work, recovery = group["workoutSteps"]
        self.assertEqual(work["targetType"]["workoutTargetTypeKey"], "pace.zone")
        self.assertEqual(recovery["targetType"]["workoutTargetTypeKey"], "heart.rate.zone")
        self.assertEqual(recovery["zoneNumber"], 1)


class VaryingRepetitions(unittest.TestCase):
    """A descending set: every repetition at a different pace."""

    def test_reps_may_each_carry_a_different_pace(self):
        day = WorkoutDay(
            date="2026-09-04", day_name_es="Viernes", is_rest=False, sport="run",
            focus="Series", purpose="Progresivo.", adaptation="x",
            steps=[
                WorkoutStep(step_order=i, step_type="interval", duration_type="distance",
                            duration_value=1000, target_type=TargetType.PACE,
                            target_low=low, target_high=low + 5, description=f"1000m {low}")
                for i, low in enumerate((360, 350, 340), start=1)
            ],
        )
        steps = _flat(build_garmin_workout(day, _zones()))
        speeds = [s["targetValueTwo"] for s in steps]
        self.assertEqual(speeds, sorted(speeds), "each rep should be faster than the last")


class StrengthSetsStillWork(unittest.TestCase):
    """repeat_count on a leaf step still means SETS -- Fase 4 must not regress."""

    def test_a_strength_set_is_a_repeat_group_with_one_child(self):
        day = WorkoutDay(
            date="2026-09-03", day_name_es="Jueves", is_rest=False, sport="strength",
            focus="Fuerza", purpose="x", adaptation="x",
            steps=[
                WorkoutStep(step_order=1, step_type="main", duration_type="reps", duration_value=8,
                            repeat_count=4, exercise_name="Sentadilla",
                            description="Sentadilla: 4 series x 8 reps"),
            ],
        )
        steps = _flat(build_garmin_workout(day, _zones()))
        self.assertEqual(steps[0]["type"], "RepeatGroupDTO")
        self.assertEqual(steps[0]["numberOfIterations"], 4)
        self.assertEqual(len(steps[0]["workoutSteps"]), 1)
        self.assertEqual(steps[0]["workoutSteps"][0]["endCondition"]["conditionTypeKey"], "reps")


class DurationAccounting(unittest.TestCase):
    def test_repeat_blocks_are_counted_by_their_iterations(self):
        day = _interval_day(repeat_count=5)
        day.total_duration_minutes = None
        # 10' warmup + 5 x (3' + 2') + 8' cooldown = 43'
        self.assertAlmostEqual(_day_duration_minutes(day), 43.0, places=1)

    def test_changing_the_iteration_count_changes_the_duration(self):
        short, long = _interval_day(repeat_count=3), _interval_day(repeat_count=8)
        short.total_duration_minutes = long.total_duration_minutes = None
        self.assertAlmostEqual(_day_duration_minutes(short), 33.0, places=1)
        self.assertAlmostEqual(_day_duration_minutes(long), 58.0, places=1)


class TheCoachWritesOneLineAndCodeBuildsTheTree(unittest.TestCase):
    """v6. Against one real archived prompt, gemini-3.5-flash failed the SAME
    session (a 5x400m VO2max set) in three different ways as each was closed:

    1. It omitted `steps` on all nine days -- `WorkoutDay.steps` had a default
       and so was not `required` in the schema.
    2. Given `steps` as required, it wrote `{"step_type": "repeat",
       "repeat_count": 5, "description": "5x(400m a ritmo VO2max, 2' rec)"}`
       with no `substeps`, and kept writing it with the validator's own error
       handed back three times.
    3. Given no `"repeat"` option, it wrote the same flat step with no
       `duration_type`.

    The pattern is not stupidity: **a coach writes a repetition as ONE line.**
    The nested `substeps` tree is a fact about Garmin's file format, not about
    coaching, and every round of fighting it cost a tier-2 repair -- $0.1093 of
    a $0.3319 week, 33%, spent rebuilding the whole plan.

    So the formatter's step is flat and always runnable, and
    `expand_formatted_steps` builds the tree. The model expresses intent; code
    does the encoding -- the same principle as the decision/formatter split and
    as "Garmin is a translation step, not a design limit", one level lower."""

    def _step_schema(self):
        schema = FormattedWeekBlock.model_json_schema()
        return schema["$defs"]["FormattedWorkoutStep"]

    def test_the_formatter_cannot_nest_at_all(self):
        self.assertNotIn("substeps", self._step_schema()["properties"])

    def test_the_formatter_has_no_repeat_step_type(self):
        """Every consumer of a repeat block branches on `substeps`, and not one
        reads `step_type == "repeat"` -- the builder, the upload verifier,
        every validator rule, both Jinja templates, app.js, and
        `is_repeat_block` itself. It was a word the model had to say that
        nothing read, whose only effect was to create a failure."""
        self.assertNotIn("repeat", self._step_schema()["properties"]["step_type"]["enum"])

    def test_a_step_that_cannot_be_executed_cannot_be_written(self):
        """Failure 3: no `duration_type`. A step the watch cannot run is not a
        step, so the field is required here (it stays optional on the base
        `WorkoutStep`, where a repeat block legitimately has none)."""
        self.assertIn("duration_type", self._step_schema()["required"])
        with self.assertRaises(ValidationError):
            FormattedWorkoutStep(
                step_order=2, step_type="interval", repeat_count=5,
                description="5x(400m a ritmo VO2max, 2' recuperación)",
            )

    def test_the_real_failing_session_written_the_coachs_way(self):
        """What the model should have been allowed to write all along."""
        step = FormattedWorkoutStep(
            step_order=2, step_type="interval", duration_type="distance", duration_value=400,
            repeat_count=5, recovery_seconds=120, recovery_description="2' trote Z1",
            target_type="pace", target_low=312, target_high=333,
            description="5x400m a 5:12-5:33/km",
        )
        expanded = expand_formatted_steps([step])[0]
        self.assertTrue(expanded.is_repeat_block)
        self.assertEqual(expanded.repeat_count, 5)
        work, recovery = expanded.substeps
        self.assertEqual((work.duration_type, work.duration_value), ("distance", 400.0))
        self.assertEqual(work.target_type, TargetType.PACE)
        self.assertEqual((recovery.step_type, recovery.duration_value), ("recovery", 120.0))
        self.assertEqual(recovery.target_zone, 1)

    def test_the_recovery_ends_up_INSIDE_the_repetition(self):
        """The defect this whole app was built around: work and recovery as
        siblings gives N hard reps back to back and only then N easy ones."""
        step = FormattedWorkoutStep(
            step_order=1, step_type="interval", duration_type="time", duration_value=180,
            repeat_count=4, recovery_seconds=120, description="4x3'",
        )
        expanded = expand_formatted_steps([step])[0]
        self.assertEqual([s.step_type for s in expanded.substeps], ["interval", "recovery"])

    def test_repetitions_without_a_declared_recovery_stay_a_plain_step(self):
        """Not every repetition has recovery inside it (strength sets). The
        builder already wraps `repeat_count > 1` on its own."""
        step = FormattedWorkoutStep(
            step_order=1, step_type="main", duration_type="reps", duration_value=10,
            repeat_count=3, description="3x10",
        )
        expanded = expand_formatted_steps([step])[0]
        self.assertEqual(expanded.substeps, [])
        self.assertEqual(expanded.repeat_count, 3)

    def test_a_descending_set_is_still_expressible(self):
        """The one shape a flat list cannot fold into a repeat -- reps that
        differ from one another -- is written as separate steps, which a flat
        list does natively."""
        steps = [
            FormattedWorkoutStep(step_order=1, step_type="interval", duration_type="distance",
                                 duration_value=1000, target_type="pace", target_low=360,
                                 target_high=365, description="1000m a 6:00"),
            FormattedWorkoutStep(step_order=2, step_type="recovery", duration_type="time",
                                 duration_value=90, description="90\" trote"),
            FormattedWorkoutStep(step_order=3, step_type="interval", duration_type="distance",
                                 duration_value=1000, target_type="pace", target_low=340,
                                 target_high=345, description="1000m a 5:40"),
        ]
        expanded = expand_formatted_steps(steps)
        self.assertEqual([s.step_order for s in expanded], [1, 2, 3])
        self.assertTrue(all(not s.substeps for s in expanded))

    def test_garmin_receives_the_repeat_group_with_the_recovery_in_it(self):
        day = FormattedWorkoutDay(
            date="2026-08-31", day_name_es="Lunes", is_rest=False, sport="run", focus="VO2max",
            purpose="Series", adaptation="", intensity_class="vo2max", discipline="carrera",
            steps=[FormattedWorkoutStep(
                step_order=1, step_type="interval", duration_type="distance", duration_value=400,
                repeat_count=5, recovery_seconds=120, target_type="pace", target_low=312,
                target_high=333, description="5x400m a 5:12-5:33/km",
            )],
        )
        block = FormattedWeekBlock(week_number=1, days=[day]).to_week_block()
        built = build_garmin_workout(block.days[0], _zones())
        groups = [s for s in built["workoutSegments"][0]["workoutSteps"] if s.get("numberOfIterations")]
        self.assertEqual(len(groups), 1)
        self.assertEqual(groups[0]["numberOfIterations"], 5)
        self.assertEqual(len(groups[0]["workoutSteps"]), 2)

    def test_the_block_converts_to_the_plain_types_everything_else_stores(self):
        day = FormattedWorkoutDay(
            date="2026-08-31", day_name_es="Lunes", is_rest=False, sport="run", focus="Z2",
            purpose="p", adaptation="a", steps=[FormattedWorkoutStep(
                step_order=1, step_type="main", duration_type="time", duration_value=1800,
                target_type="hr_zone", target_zone=2, description="30' Z2")],
        )
        block = FormattedWeekBlock(week_number=1, days=[day], week_rationale="porque sí").to_week_block()
        self.assertIs(type(block), WeekBlock)
        self.assertIs(type(block.days[0]), WorkoutDay)
        self.assertEqual(block.week_rationale, "porque sí")

    def test_stored_plans_that_contain_repeat_still_load(self):
        """Plans already on disk use `step_type="repeat"` with `substeps`.
        Removing it from the base model would make every archived week
        unreadable."""
        step = WorkoutStep(
            step_order=2, step_type="repeat", repeat_count=5, description="5x(3' fuerte, 2' suave)",
            substeps=[
                WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                            duration_value=180, description="3' fuerte"),
                WorkoutStep(step_order=2, step_type="recovery", duration_type="time",
                            duration_value=120, description="2' suave"),
            ],
        )
        self.assertTrue(step.is_repeat_block)

    def test_the_prompt_teaches_exactly_what_the_schema_allows(self):
        """The bug that started all of this was a prompt and a schema saying
        opposite things (`steps` mandatory in prose, optional in the schema).
        A prompt still teaching `substeps` would be the same mistake."""
        self.assertNotIn("substeps", WORKOUT_FORMATTER_USER_PROMPT)
        self.assertNotIn('"step_type": "repeat"', WORKOUT_FORMATTER_USER_PROMPT)
        # La recuperación que el prompt enseña es la que el esquema admite
        # (con su unidad desde el 2026-09-15), y no la que se retiró.
        self.assertIn("recovery_duration_type", WORKOUT_FORMATTER_USER_PROMPT)
        self.assertNotIn("recovery_seconds", WORKOUT_FORMATTER_USER_PROMPT)
        self.assertIn("la lista de\n  pasos es PLANA", WORKOUT_FORMATTER_USER_PROMPT)

    def test_strength_is_told_not_to_repeat_at_all(self):
        self.assertIn("Nunca le pongas", WORKOUT_FORMATTER_USER_PROMPT)


if __name__ == "__main__":
    unittest.main(verbosity=2)


class LaRepeticionNoSeLeeDosVeces(unittest.TestCase):
    """v7 -- el paso hijo dejó de heredar la línea entera del entrenador.

    Encontrado leyendo un plan real generado con el modelo de lanzamiento. El
    árbol que llegaba a Garmin era correcto, pero `expand()` copiaba la MISMA
    descripción al bloque de repetición y a su paso de trabajo:

        [repeat] 5x  -> "5x(3min a 5:12-5:20/km, 2min rec)"
           [interval] -> "5x(3min a 5:12-5:20/km, 2min rec)"   <- aquí
           [recovery] -> "2min trote muy suave Z1"

    Dos consecuencias, las dos para el atleta y ninguna para el validador:
    en el reloj leía "5x(3min...)" mientras corría la repetición 3 de 5, y en
    el plan impreso la serie salía dos veces seguidas, como si fueran diez.
    """

    def _serie(self, descripcion: str):
        return FormattedWorkoutStep(
            step_order=1, step_type="interval", duration_type="time", duration_value=180,
            repeat_count=5, recovery_seconds=120, description=descripcion,
        ).expand()

    def test_el_trabajo_describe_solo_el_trabajo(self):
        arbol = self._serie("5x(3min a 5:12-5:20/km, 2min rec)")
        self.assertEqual(arbol.substeps[0].description, "3min a 5:12-5:20/km")

    def test_el_bloque_conserva_la_linea_entera(self):
        """Es la que tiene sentido a nivel de bloque, y la que el atleta lee
        en el plan: cuántas repeticiones y con qué descanso."""
        arbol = self._serie("5x(3min a 5:12-5:20/km, 2min rec)")
        self.assertEqual(arbol.description, "5x(3min a 5:12-5:20/km, 2min rec)")

    def test_el_padre_y_el_hijo_ya_no_dicen_lo_mismo(self):
        arbol = self._serie("6x(15s progresivos rápido y suelto, 67s rec)")
        self.assertNotEqual(arbol.description, arbol.substeps[0].description)

    def test_aguanta_parentesis_anidados(self):
        """Real, de un plan generado: la propia descripción llevaba
        '(strides)' dentro."""
        arbol = self._serie('5x(15" aceleración suave progresiva (strides), 45" rec caminando)')
        self.assertEqual(arbol.substeps[0].description, '15" aceleración suave progresiva (strides)')

    def test_aguanta_el_separador_con_mas(self):
        arbol = self._serie("5x(400m + 2' rec)")
        self.assertEqual(arbol.substeps[0].description, "400m")

    def test_una_linea_que_no_tiene_esa_forma_se_respeta(self):
        """Perder el matiz que escribió el entrenador sería peor que repetir
        la cabecera. Ante la duda, no se toca."""
        arbol = self._serie("Series de 1km a ritmo umbral")
        self.assertEqual(arbol.substeps[0].description, "Series de 1km a ritmo umbral")

    def test_un_paso_sin_recuperacion_conserva_su_texto(self):
        """Sin recuperación no hay bloque anidado, así que no hay nada que
        desdoblar y la descripción es la del paso."""
        paso = FormattedWorkoutStep(
            step_order=1, step_type="main", duration_type="time", duration_value=1800,
            description="30min continuo Z2",
        ).expand()
        self.assertEqual(paso.description, "30min continuo Z2")
        self.assertEqual(paso.substeps, [])
