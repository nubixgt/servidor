"""Tests for the printable plan and macrocycle sheets.

The athlete asked for something they can print and put on a wall. These check
the two things that make a printed plan useful rather than decorative: that it
carries the athlete's own reference numbers so it stands alone at the track,
and that a repeat block prints with its rounds inside it rather than as a
single opaque line.

Run with:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.rendering.printable_renderer import (  # noqa: E402
    markdown_to_html,
    render_printable_plan,
    render_printable_season,
)

PRESCRIPTION = {
    "hr_zones_by_sport": {
        "run": {"1": [123, 138], "2": [139, 152], "5": [181, None]},
        "swim": {"2": [112, 129]},
    },
    "pace_bands": {"umbral_tempo": [340.3, 368.0], "facil": [421.2, 471.7]},
    "power_bands": {"umbral": [259, 299]},
}

SEASON_PHASES = {
    "season_start_date": "2026-07-01",
    "phases": [
        {"name": "Base", "start_week": 1, "end_week": 8, "weekly_hours_target": 8,
         "intensity_emphasis": "Volumen aeróbico.", "deload_weeks": [4, 8]},
        {"name": "Build", "start_week": 9, "end_week": 16, "weekly_hours_target": 10,
         "intensity_emphasis": "Umbral.", "deload_weeks": [12]},
    ],
}


def _interval_day(day="2026-09-02", name="Miércoles"):
    return WorkoutDay(
        date=day, day_name_es=name, is_rest=False, sport="run", focus="Intervalos",
        purpose="Umbral.", adaptation="Si estás cansado: 3 reps.", total_duration_minutes=43,
        steps=[
            WorkoutStep(step_order=1, step_type="warmup", duration_type="time", duration_value=600,
                        target_type=TargetType.HR_ZONE, target_zone=2, description="10' calentamiento Z2"),
            WorkoutStep(step_order=2, step_type="repeat", repeat_count=5,
                        description="5x(3' a 5:40-5:55/km, 2' rec)",
                        substeps=[
                            WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                                        duration_value=180, target_type=TargetType.PACE,
                                        target_low=340, target_high=355, description="3' a 5:40-5:55/km"),
                            WorkoutStep(step_order=2, step_type="recovery", duration_type="time",
                                        duration_value=120, target_type=TargetType.HR_ZONE,
                                        target_zone=1, description="2' trote Z1"),
                        ]),
        ],
    )


def _rest_day(day="2026-09-03"):
    return WorkoutDay(date=day, day_name_es="Jueves", is_rest=True, sport="rest",
                      focus="Descanso", purpose="Recuperación.", adaptation="—", steps=[])


def _plan(days=None):
    return WeeklyPlanPayload(
        athlete_name="Jose Ines Castillo", start_date="2026-09-01", zones_table="z",
        weeks=[WeekBlock(week_number=1, days=days or [_interval_day(), _rest_day()])],
    )


class ThePrintedPlanStandsAlone(unittest.TestCase):
    """No phone needed at the track: each step carries its own pulse or pace, and the full zone and pace
    tables live on the season sheet (el responsable, 2026-10-22: «una sola página sólo con la semana; las
    zonas, en el macrociclo, que es un documento más largo y no va en la refri»)."""

    def setUp(self):
        self.html = render_printable_plan(_plan(), PRESCRIPTION, "2026-08-21T10:00:00")
        self.temporada = render_printable_season("Jose Ines Castillo", None, [], "", prescription=PRESCRIPTION)

    def test_each_step_carries_the_athletes_own_heart_rate(self):
        self.assertIn("139–152 ppm", self.html)

    def test_the_week_sheet_has_no_zone_tables(self):
        self.assertNotIn('class="reference"', self.html)
        self.assertNotIn("181+ ppm", self.html)

    def test_the_open_ended_top_zone_reads_as_open_ended(self):
        self.assertIn("181+ ppm", self.temporada)

    def test_zones_are_labelled_per_sport(self):
        self.assertIn("Carrera", self.temporada)
        self.assertIn("Natación", self.temporada)
        self.assertIn("112–129 ppm", self.temporada)

    def test_it_carries_the_pace_bands(self):
        self.assertIn("5:40–6:08/km", self.temporada)

    def test_it_carries_the_power_bands(self):
        self.assertIn("259–299 W", self.temporada)

    def test_a_plan_without_a_prescription_still_prints(self):
        html = render_printable_plan(_plan(), None, None)
        self.assertIn("Plan de Entrenamiento", html)
        self.assertIn("Intervalos", html)


class RepeatBlocksPrintWithTheirRoundsInside(unittest.TestCase):
    def setUp(self):
        self.html = render_printable_plan(_plan(), PRESCRIPTION)

    def test_the_block_label_is_shown(self):
        """Las rondas, sí; la frase del entrenador, sólo si añade algo.

        "5x(3' a 5:40-5:55/km, 2' rec)" como cabecera y debajo "3' a
        5:40-5:55/km" y "2' trote Z1" decía la misma serie dos veces seguidas
        (visto por el atleta 2 en su hoja del 2026-09-11)."""
        self.assertIn("5×", self.html)
        self.assertEqual(self.html.count("3&#39; a 5:40-5:55/km"), 1)

    def test_the_label_keeps_what_the_rounds_do_not_say(self):
        from types import SimpleNamespace as S

        from services.ai.rendering.printable_renderer import repeat_extra
        bloque = S(description="4x(400 m en pista con zapatillas de clavos)",
                   substeps=[S(description="400 m a 4:50/km"), S(description="90'' de pie")])
        self.assertIn("clavos", repeat_extra(bloque))

    def test_the_work_and_recovery_inside_it_are_shown_too(self):
        self.assertIn("3&#39; a 5:40-5:55/km", self.html)
        self.assertIn("2&#39; trote Z1", self.html)

    def test_the_substeps_are_nested_not_flattened(self):
        self.assertEqual(len(re.findall(r'class="repeat"', self.html)), 1)
        nested = re.search(r'class="repeat".*?<ol class="steps">(.*?)</ol>', self.html, re.S)
        self.assertIsNotNone(nested)
        self.assertIn("trote Z1", nested.group(1))


class WeekSummary(unittest.TestCase):
    """Con una sola semana -- lo normal desde la v4 -- las sesiones y el tiempo
    van en la cabecera de la hoja, no repetidos en una etiqueta encima del
    calendario (rediseño del 2026-09-11)."""

    def test_each_week_shows_its_session_count_and_volume(self):
        html = render_printable_plan(_plan(), PRESCRIPTION)
        self.assertIn('<div class="v">1</div><div class="l">sesiones</div>', html)
        self.assertIn('<div class="v">43&#39;</div>', html)

    def test_rest_days_do_not_count_towards_volume(self):
        two_sessions = _plan([_interval_day(), _interval_day("2026-09-04", "Viernes"), _rest_day()])
        html = render_printable_plan(two_sessions, PRESCRIPTION)
        self.assertIn('<div class="v">2</div><div class="l">sesiones</div>', html)
        self.assertIn("1h26m", html)
        self.assertIn('<div class="v">1</div><div class="l">descanso</div>', html)

    def test_several_weeks_still_label_each_one(self):
        from services.ai.langgraph.schemas.plan_outputs import WeekBlock, WeeklyPlanPayload
        base = _plan()
        dos = WeeklyPlanPayload(athlete_name=base.athlete_name, start_date=base.start_date, zones_table="z",
                                weeks=[base.weeks[0], WeekBlock(week_number=2, days=base.weeks[0].days)])
        # Con `hoy` antes de las dos: la hoja imprime las semanas que quedan por vivir (auditoría del
        # 2026-09-24), y desde el 2026-10-22 cada una en su propia hoja, con su cabecera.
        html = render_printable_plan(dos, PRESCRIPTION, hoy="2026-09-01")
        self.assertEqual(html.count('<section class="page">'), 2)
        self.assertEqual(html.count("<h1>Tu semana"), 2)
        self.assertIn(".page + .page { break-before: page;", html)

    def test_solo_las_semanas_que_quedan_por_vivir(self):
        """La hoja de José del 2026-09-24 imprimía las tres semanas de su plan bajo "Tu semana"."""
        from services.ai.langgraph.schemas.plan_outputs import WeekBlock, WeeklyPlanPayload
        base = _plan()
        dos = WeeklyPlanPayload(athlete_name=base.athlete_name, start_date=base.start_date, zones_table="z",
                                weeks=[base.weeks[0], WeekBlock(week_number=2, days=base.weeks[0].days)])
        html = render_printable_plan(dos, PRESCRIPTION, hoy="2026-12-01")   # todas pasaron: la última
        self.assertIn("<h1>Tu semana", html)

    def test_a_rest_day_is_marked_as_such(self):
        html = render_printable_plan(_plan(), PRESCRIPTION)
        self.assertIn("day rest", html)
        self.assertIn("Descanso", html)


class PrintStyling(unittest.TestCase):
    """Designed for paper, not adapted from the dark web UI."""

    def setUp(self):
        self.html = render_printable_plan(_plan(), PRESCRIPTION)

    def test_it_is_laid_out_landscape_on_a4(self):
        self.assertIn("size: A4 landscape", self.html)

    def test_a_week_is_never_split_across_pages(self):
        self.assertIn("page-break-inside: avoid", self.html)

    def test_the_print_button_is_hidden_when_printing(self):
        self.assertRegex(self.html, r"@media print \{[^}]*\.print-bar \{ display: none;")

    def test_the_seven_days_form_a_real_calendar_row(self):
        self.assertIn("grid-template-columns: repeat(7, 1fr)", self.html)


class TheMacrocycleSheet(unittest.TestCase):
    def setUp(self):
        self.html = render_printable_season(
            "Jose Ines Castillo", SEASON_PHASES,
            [{"name": "Maratón de Guatemala", "date": "2026-11-15", "priority": "A"}],
            markdown_to_html("## Fases\n\nBase larga.\n\n- Semana 1-8\n- Semana 9-16"),
        )

    def test_every_season_week_gets_a_cell(self):
        self.assertEqual(len(re.findall(r'class="w[ "]', self.html)), 16)

    def test_deload_weeks_are_marked(self):
        self.assertEqual(len(re.findall(r"w deload", self.html)), 3)

    def test_phases_are_tabulated(self):
        self.assertIn("Base", self.html)
        self.assertIn("Build", self.html)
        self.assertIn("1–8", self.html)

    def test_races_show_how_far_away_they_are(self):
        self.assertIn("Maratón de Guatemala", self.html)
        self.assertRegex(self.html, r"en \d+ sem")

    def test_a_swim_goal_saved_before_the_fix_shows_no_pace_per_km(self):
        """José, 2026-10-22: su 400 m en 6:40, guardado por la generación de ese día, salía «16:40/km» en la
        hoja. Una meta guardada sin `unit` sólo enseña /km si es de correr; la nueva lleva su unidad."""
        meta = {"name": "400m Masters FENADEGUA", "date": "2026-11-21", "priority": "B", "target_clock": "6:40",
                "legs": [{"sport": "swim", "metres": 400}],
                "pace_context": {"required_pace_clock": "16:40", "required_pace_sec_per_km": 1000.0}}
        html = render_printable_season("Jose Ines Castillo", SEASON_PHASES, [], "", goals=[meta])
        self.assertNotIn("16:40/km", html)
        meta["pace_context"] = {"required_pace_clock": "1:40", "unit": "/100 m", "sport": "swim"}
        html = render_printable_season("Jose Ines Castillo", SEASON_PHASES, [], "", goals=[meta])
        self.assertIn("1:40/100 m", html)

    def test_a_season_with_no_phases_still_renders(self):
        html = render_printable_season("Atleta", None, [], "")
        self.assertIn("Plan de Temporada", html)

    def test_it_is_laid_out_portrait(self):
        self.assertIn("size: A4 portrait", self.html)


class SeasonNarrative(unittest.TestCase):
    def test_markdown_becomes_real_html(self):
        html = markdown_to_html("## Fases\n\n- uno\n- dos")
        self.assertIn("<h2>", html)
        self.assertIn("<li>", html)

    def test_raw_html_in_the_narrative_is_not_passed_through(self):
        """The text comes from our own agent, but it is still LLM output."""
        html = markdown_to_html("Texto <script>alert(1)</script> normal")
        self.assertNotIn("<script>", html)

    def test_empty_markdown_is_empty(self):
        self.assertEqual(markdown_to_html(""), "")


if __name__ == "__main__":
    unittest.main(verbosity=2)
