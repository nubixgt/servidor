"""Tests for Plan Maestro v5 Track 11: deleting says what it deletes, and
deletes only that.

The athlete's report: *"le doy borrar generación, pero debería saber si estoy
borrando la semana actual o el macro ciclo"*. Both halves of that were real:

1. **The dialog said one fixed sentence** regardless of what was on disk, so
   there was no way to tell a week of sessions from a 16-week macrocycle.
2. **It always deleted everything.** Generating a week only ever touches the
   week files -- but "borrar generación" removed `season_plan.md`,
   `season_phases.json` and `analysis.html` too. Wanting to redo one week cost
   the macrocycle underneath it, silently.

And deleting a plan must not delete WHY it was written: the reasoning trails
are their own action, pinned by `ReasoningIsItsOwnAction`.

Run with:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.reasoning_archive import save_reasoning  # noqa: E402
from web_app.server import (  # noqa: E402
    _SEASON_FILES,
    _WEEK_FILES,
    _read_history_entry,
    _strip_summary_keys,
    describe_deletion,
)


def _populate(storage: Path) -> None:
    """A profile mid-season: a macrocycle plus two generated weeks."""
    (storage / "season_plan.md").write_text("# Plan de Temporada", encoding="utf-8")
    (storage / "season_phases.json").write_text(json.dumps({
        "season_start_date": "2026-09-01",
        "phases": [{"name": "Base"}, {"name": "Build"}, {"name": "Peak"}],
        "constraints": [],
    }), encoding="utf-8")
    (storage / "analysis.html").write_text("<html></html>", encoding="utf-8")
    (storage / "planning.html").write_text("<html></html>", encoding="utf-8")
    (storage / "weekly_plan.json").write_text(json.dumps({
        "athlete_name": "Test", "start_date": "2026-09-07", "zones_table": "z",
        "weeks": [
            {"week_number": 1, "days": [
                {"date": "2026-09-07", "is_rest": False},
                {"date": "2026-09-08", "is_rest": True},
                {"date": "2026-09-09", "is_rest": False},
            ]},
            {"week_number": 2, "days": [
                {"date": "2026-09-14", "is_rest": False},
            ]},
        ],
    }), encoding="utf-8")
    (storage / "summary.json").write_text(json.dumps({
        "athlete": "Test", "analysis_date": "2026-09-07T10:00:00",
        "generation_kind": "week",
        "weekly_plan": {"weeks": []}, "plan_validation": {"repaired": True},
        "adherence": {"pct": 80}, "prescription": {"card": "x"},
        "season_plan": "# Plan de Temporada", "goal_realism": [{"name": "5K"}],
        "volume_baseline": {"chronic_weekly_hours": 3.6},
        "total_cost_usd": 0.26,
    }), encoding="utf-8")


class ThePreviewSaysWhatWillActuallyGo(unittest.TestCase):
    def test_the_week_scope_names_the_dates_and_session_count(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            preview = describe_deletion(storage, "week")

            self.assertEqual(len(preview["items"]), 1)
            self.assertIn("2 semana(s)", preview["items"][0])
            self.assertIn("2026-09-07", preview["items"][0])
            self.assertIn("2026-09-14", preview["items"][0])
            self.assertIn("3 sesiones", preview["items"][0])

    def test_rest_days_are_not_counted_as_sessions(self):
        """3 of the 4 days are training days; saying "4 sesiones" would
        overstate what the athlete is losing."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            self.assertIn("3 sesiones", describe_deletion(storage, "week")["items"][0])

    def test_the_season_scope_names_the_phase_count(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            item = describe_deletion(storage, "season")["items"][0]
            self.assertIn("macrociclo", item)
            self.assertIn("3 fases", item)
            self.assertIn("restricciones", item)

    def test_the_all_scope_lists_both_halves_separately(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            self.assertEqual(len(describe_deletion(storage, "all")["items"]), 2)

    def test_it_warns_whether_anything_will_be_restored(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            warnings = " ".join(describe_deletion(storage, "all")["warnings"])
            self.assertIn("quedarás sin plan", warnings)

            (storage / "history" / "2026-09-01T10-00-00").mkdir(parents=True)
            warnings = " ".join(describe_deletion(storage, "all")["warnings"])
            self.assertIn("Se restaurará", warnings)
            self.assertIn("2026-09-01T10-00-00", warnings)

    def test_cada_alcance_dice_QUE_razonamiento_se_lleva(self):
        """El aviso decia "el razonamiento archivado NO se borra con esto", y
        dejo de ser verdad cuando el borrado empezo a llevarse la traza del
        plan borrado. Un aviso que describe mal lo que va a pasar es peor que
        no tenerlo: el atleta confirma otra cosa de la que pidio."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            esperado = {
                "week": "razonamiento de ESA semana",
                "season": "razonamiento del macrociclo",
                "all": "razonamiento de las dos",
            }
            for scope, frase in esperado.items():
                with self.subTest(scope=scope):
                    warnings = " ".join(describe_deletion(storage, scope)["warnings"])
                    self.assertIn(frase, warnings)
                    self.assertNotIn("NO se borra", warnings)

    def test_todos_los_alcances_prometen_que_el_historial_de_forma_se_queda(self):
        """Es la razon por la que las trazas no se borran enteras: dentro
        viven `fitness_series.json` y `load_state.json`, que son el historial
        del ATLETA y lo que dibuja las graficas del panel."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            for scope in ("week", "season", "all"):
                with self.subTest(scope=scope):
                    warnings = " ".join(describe_deletion(storage, scope)["warnings"])
                    self.assertIn("historial de forma física", warnings)

    def test_nothing_to_delete_is_reported_as_empty_rather_than_confirmed(self):
        with tempfile.TemporaryDirectory() as tmp:
            self.assertTrue(describe_deletion(Path(tmp), "all")["empty"])
            self.assertTrue(describe_deletion(Path(tmp), "week")["empty"])

    def test_the_reasoning_scope_counts_the_archived_trails(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            save_reasoning(storage, {"synthesis_result": "x"}, kind="week")
            preview = describe_deletion(storage, "reasoning")
            self.assertFalse(preview["empty"])
            self.assertIn("1 traza(s)", preview["items"][0])


class ScopesTouchOnlyTheirOwnFiles(unittest.TestCase):
    def test_the_two_file_groups_do_not_overlap(self):
        """If a file were in both groups, deleting the week would take part of
        the macrocycle with it -- the exact bug this track fixes."""
        self.assertEqual(set(_WEEK_FILES) & set(_SEASON_FILES), set())

    def test_the_season_plan_is_not_in_the_week_group(self):
        for filename in ("season_plan.md", "season_phases.json"):
            with self.subTest(filename=filename):
                self.assertNotIn(filename, _WEEK_FILES)

    def test_stripping_one_half_of_the_summary_keeps_the_other(self):
        """summary.json holds both halves. Deleting the file on a scoped delete
        would take the surviving half's cost and prescription with it."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            _strip_summary_keys(storage, ("weekly_plan", "plan_validation"))

            summary = json.loads((storage / "summary.json").read_text(encoding="utf-8"))
            self.assertNotIn("weekly_plan", summary)
            self.assertNotIn("plan_validation", summary)
            self.assertIn("season_plan", summary)
            self.assertEqual(summary["total_cost_usd"], 0.26)

    def test_stripping_a_missing_summary_is_a_no_op(self):
        with tempfile.TemporaryDirectory() as tmp:
            _strip_summary_keys(Path(tmp), ("weekly_plan",))  # must not raise


class HistoryEntriesSayWhatTheyAre(unittest.TestCase):
    def test_a_week_generation_is_labelled_as_one(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            self.assertEqual(_read_history_entry(storage)["kind"], "week")

    def test_a_season_generation_is_labelled_as_one(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            summary = json.loads((storage / "summary.json").read_text(encoding="utf-8"))
            summary["generation_kind"] = "season"
            (storage / "summary.json").write_text(json.dumps(summary), encoding="utf-8")
            self.assertEqual(_read_history_entry(storage)["kind"], "season")

    def test_a_legacy_entry_gets_its_kind_inferred_rather_than_shown_unknown(self):
        """Entries archived before this field existed still have to say what
        they are, or the whole point of labelling them is lost for the
        athlete's existing history."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            summary = json.loads((storage / "summary.json").read_text(encoding="utf-8"))
            del summary["generation_kind"]
            (storage / "summary.json").write_text(json.dumps(summary), encoding="utf-8")
            self.assertEqual(_read_history_entry(storage)["kind"], "week")

            (storage / "weekly_plan.json").unlink()
            self.assertEqual(_read_history_entry(storage)["kind"], "season")


class LasTrazasSeVanConSuPropioAlcance(unittest.TestCase):
    """La regla cambio, a peticion del atleta, y merece decirse entera.

    ANTES: borrar un plan no tocaba su traza, nunca. Eso dejaba al chat
    explicando con detalle una semana que el atleta acababa de borrar, y hacia
    crecer el archivo sin techo.

    AHORA: la traza se va con el plan que explica, **y solo con ese**. Borrar
    la semana no toca el razonamiento del macrociclo ni al reves: son cosas de
    escalas distintas, igual que los planes.

    Lo que NO cambio, y es lo que impide acoplarlas del todo: dentro de cada
    traza viven `fitness_series.json`, `load_state.json` y `recovery.json`, de
    donde `services/ai/charts.py` saca el historial de forma, carga y
    recuperacion. Eso es dato del ATLETA. Por eso se borran por alcance y se
    podan por antiguedad, y nunca "todas al borrar cualquier cosa".
    """

    def test_no_se_borran_por_nombre_de_fichero_sino_por_tipo(self):
        """Las listas de ficheros siguen sin mencionar `reasoning`: el borrado
        de trazas pasa por `borrar_trazas(kind=...)`, que sabe de que tipo es
        cada una. Meter la carpeta en `_WEEK_FILES` se llevaria TODAS."""
        for group in (_WEEK_FILES, _SEASON_FILES):
            for filename in group:
                with self.subTest(filename=filename):
                    self.assertNotIn("reasoning", filename)

    def test_the_scopes_are_offered_separately_in_the_ui(self):
        app_js = (
            Path(__file__).resolve().parents[1] / "web_app/static/app.js"
        ).read_text(encoding="utf-8")
        for scope in ("week:", "season:", "all:", "reasoning:"):
            with self.subTest(scope=scope):
                self.assertIn(scope, app_js.split("const DELETE_SCOPES")[1][:400])

    def test_the_success_message_does_not_claim_the_plan_is_gone_after_a_week_delete(self):
        """"Este atleta ya no tiene ningún plan activo" is simply false when
        only the week was removed."""
        app_js = (
            Path(__file__).resolve().parents[1] / "web_app/static/app.js"
        ).read_text(encoding="utf-8")
        self.assertIn("Tu macrociclo sigue intacto", app_js)


if __name__ == "__main__":
    unittest.main(verbosity=2)
