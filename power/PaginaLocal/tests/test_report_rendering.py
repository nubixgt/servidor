"""Tests for Plan Maestro v5 Track 9: the reports stop being written by a model.

The analysis report used to be produced by handing the synthesis markdown to an
LLM with *"Create a complete HTML document... Clean CSS, responsive layout,
professional typography"* and keeping whatever came back. Three costs, all
real:

- **The design changed on every run**, so nothing could be maintained or
  reviewed, and the athlete got a differently-shaped document each time.
- **A model call per generation was spent producing CSS**, on top of the call
  that produced the content.
- **It was the last surface still emitting English**: a formatting model
  reproduces the language of what it is imitating, not the athlete's.

The content is unchanged -- still the synthesis agent's own writing. Only the
presentation moved into a template, where it is deterministic and free.

Run with:  python -m unittest discover -s tests -v
"""

import importlib
import inspect
import json
import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload  # noqa: E402
from services.ai.rendering.printable_renderer import (  # noqa: E402
    render_analysis_report,
    render_printable_plan,
    render_printable_season,
)

TEMPLATES = Path(__file__).resolve().parents[1] / "services/ai/rendering/templates"

SYNTHESIS = """# Resumen ejecutivo

La carga **sube** y la recuperación baja.

## Señales en conflicto
- Actividad: ejecutó 5 de 6 sesiones.
- Fisiología: HRV por debajo de su rango.

| Métrica | Valor |
| --- | --- |
| Carga aguda | 5.2 h |
"""


def _payload() -> WeeklyPlanPayload:
    return WeeklyPlanPayload(
        athlete_name="Jose Ines Castillo", start_date="2026-09-07", zones_table="z",
        weeks=[{"week_number": 1, "days": [{
            "date": "2026-09-07", "day_name_es": "Lunes", "is_rest": False, "sport": "run",
            "focus": "Umbral", "purpose": "Trabajo de umbral.", "adaptation": "Si duele: para.",
            "total_duration_minutes": 45, "steps": [],
        }]}],
    )


class TheReportIsRenderedNotWritten(unittest.TestCase):
    def setUp(self):
        self.module = importlib.import_module("services.ai.langgraph.nodes.formatter_node")

    def test_the_node_makes_no_model_call_at_all(self):
        """The whole point of the track: a model was being paid to produce CSS
        on every generation."""
        src = inspect.getsource(self.module)
        for forbidden in ("ModelSelector", "ainvoke", "retry_with_backoff", "get_usage_metadata_callback"):
            with self.subTest(symbol=forbidden):
                self.assertNotIn(forbidden, src)

    def test_it_reports_no_token_cost_because_there_is_none(self):
        """A cost entry with fabricated usage_metadata would make the athlete's
        cost breakdown lie about where their money went."""
        src = inspect.getsource(self.module.formatter_node)
        # create_cost_entry is what folds real token usage into the breakdown;
        # this node builds its entry by hand precisely because it has none.
        self.assertNotIn("create_cost_entry", src)
        self.assertIn('"agent": "formatter"', src)

    def test_the_same_input_renders_identically_every_time(self):
        first = render_analysis_report("Test", SYNTHESIS, generated_at="2026-09-07")
        second = render_analysis_report("Test", SYNTHESIS, generated_at="2026-09-07")
        self.assertEqual(first, second)


class TheAgentsWritingSurvivesIntact(unittest.TestCase):
    def test_markdown_structure_reaches_the_page(self):
        html = render_analysis_report("Test", SYNTHESIS)
        self.assertIn("<strong>sube</strong>", html)
        self.assertIn("<li>", html)
        self.assertIn("<table>", html)
        self.assertIn("Señales en conflicto", html)

    def test_raw_html_in_the_markdown_is_not_executed(self):
        """The text comes from our own agent, but an agent writing markup into
        a page we then print is not a thing worth allowing."""
        html = render_analysis_report("Test", "Hola <script>alert(1)</script> mundo")
        self.assertNotIn("<script>", html)

    def test_the_athletes_name_is_escaped(self):
        html = render_analysis_report("<b>Jose</b>", SYNTHESIS)
        self.assertNotIn("<b>Jose</b>", html)
        self.assertIn("&lt;b&gt;Jose&lt;/b&gt;", html)

    def test_a_missing_analysis_says_so_instead_of_rendering_an_empty_sheet(self):
        html = render_analysis_report("Test", "")
        self.assertIn("todavía no está disponible", html)


class HeadlineNumbersAreOnlyShownWhenTheyExist(unittest.TestCase):
    def setUp(self):
        self.module = importlib.import_module("services.ai.langgraph.nodes.formatter_node")

    def test_real_numbers_become_stat_tiles(self):
        stats = self.module._headline_stats({
            "prescription": {"threshold_pace_sec_per_km": 347.2},
            "availability_constraints": {
                "volume_baseline": {"chronic_weekly_hours": 3.6, "chronic_window_days": 28},
                "recovery": {"hrv_status": "UNBALANCED"},
            },
            "adherence": {"adherence_pct": 83.0},
        })
        labels = {s["label"]: s["value"] for s in stats}
        self.assertEqual(labels["Ritmo umbral"], "5:47/km")
        # "Volumen", no "carga": desde la v1.2 la carga es la de Garmin y esto son horas.
        self.assertEqual(labels["Volumen semanal"], "3.6 h/sem")
        self.assertEqual(labels["Adherencia"], "83%")
        self.assertEqual(labels["Estado HRV"], "UNBALANCED")

    def test_a_stat_with_no_data_is_omitted_rather_than_shown_as_a_dash(self):
        """A tile reading "—" tells the athlete nothing and takes the place of
        one that would have."""
        self.assertEqual(self.module._headline_stats({}), [])

    def test_prescription_warnings_surface_as_callouts(self):
        html = render_analysis_report(
            "Test", SYNTHESIS, warnings=["No se encontraron zonas de FC reales."],
        )
        self.assertIn("callout warn", html)
        self.assertIn("zonas de FC reales", html)


class OneDesignSystemAcrossEveryReport(unittest.TestCase):
    def test_every_report_template_includes_the_shared_style(self):
        for name in ("analysis_report", "printable_plan", "printable_season"):
            with self.subTest(template=name):
                source = (TEMPLATES / f"{name}.html.jinja2").read_text(encoding="utf-8")
                self.assertIn('include "_report_style.html.jinja2"', source)

    def test_the_palette_is_defined_in_exactly_one_place(self):
        """Two copies of a palette are two palettes, and they drift."""
        definitions = [
            name for name in ("analysis_report", "printable_plan", "printable_season")
            if "--ink-soft:" in (TEMPLATES / f"{name}.html.jinja2").read_text(encoding="utf-8")
        ]
        self.assertEqual(definitions, [])
        self.assertIn("--ink-soft:", (TEMPLATES / "_report_style.html.jinja2").read_text(encoding="utf-8"))

    def test_the_shared_tokens_reach_every_rendered_sheet(self):
        rendered = [
            render_analysis_report("Test", SYNTHESIS),
            render_printable_plan(_payload(), {}, "2026-09-07T10:00:00"),
            render_printable_season("Test", {"phases": [], "season_start_date": "2026-09-01"}, []),
        ]
        for index, html in enumerate(rendered):
            with self.subTest(sheet=index):
                self.assertIn("--ink-faint:", html)
                self.assertIn("--accent:", html)

    def test_every_sheet_is_declared_spanish_and_printable(self):
        rendered = {
            "analysis": render_analysis_report("Test", SYNTHESIS),
            "plan": render_printable_plan(_payload(), {}, "2026-09-07T10:00:00"),
            "season": render_printable_season("Test", {"phases": [], "season_start_date": "2026-09-01"}, []),
        }
        for name, html in rendered.items():
            with self.subTest(sheet=name):
                self.assertIn('<html lang="es">', html)
                self.assertIn("@page", html)
                self.assertIn("@media print", html)


class ThePrintLayoutWasNotRegressed(unittest.TestCase):
    """The two printable sheets were verified in a browser with real print
    metrics during v3 Fase 6. Sharing a palette must not have touched the page
    geometry those checks covered."""

    def test_the_plan_still_keeps_a_week_off_a_page_break(self):
        html = render_printable_plan(_payload(), {}, "2026-09-07T10:00:00")
        self.assertIn("break-inside: avoid", html)
        self.assertIn("page-break-inside: avoid", html)

    def test_the_plan_is_still_landscape_and_the_season_portrait(self):
        plan = render_printable_plan(_payload(), {}, "2026-09-07T10:00:00")
        season = render_printable_season("Test", {"phases": [], "season_start_date": "2026-09-01"}, [])
        self.assertIn("A4 landscape", plan)
        self.assertIn("A4 portrait", season)

    def test_the_plan_keeps_its_wider_screen_width(self):
        self.assertIn("1100px", render_printable_plan(_payload(), {}, "2026-09-07T10:00:00"))

    def test_the_sheets_still_render_against_this_athletes_real_stored_data(self):
        storage = Path(__file__).resolve().parents[1] / "data/storage/Jose_Ines_Castillo"
        if not (storage / "weekly_plan.json").is_file():
            self.skipTest("no hay plan real guardado en este entorno")

        payload = WeeklyPlanPayload(**json.loads((storage / "weekly_plan.json").read_text(encoding="utf-8")))
        summary = json.loads((storage / "summary.json").read_text(encoding="utf-8"))
        html = render_printable_plan(payload, summary.get("prescription"), summary.get("analysis_date"))

        self.assertGreater(len(html), 5000)
        self.assertNotIn("{{", html)
        self.assertIsNone(re.search(r"\{%", html))


if __name__ == "__main__":
    unittest.main(verbosity=2)
