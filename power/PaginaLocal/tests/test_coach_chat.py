"""Tests for Plan Maestro v5 Track 8: a coach the athlete can ask questions.

Everything it needs was built by the earlier tracks -- the archived reasoning
(7), the dated constraints (3), the check-in (6), the health record that now
reaches the pipeline (10), the week rationale (v4 G).

The load-bearing concern is the athlete's own, and it is the right one:

    "que el chatbot tenga acceso limitado a las corridas recientes ya que
    acceso a muchas corridas anteriores desbordará el costo de entrada de
    tokens"

Every message re-sends the whole context, so an unbounded context is not a
one-off cost -- it is a tax on every question ever asked. `TheContextIsBounded`
is the class that matters most here; the rest checks that what survives the
budget is the part that actually answers questions.

Run with:  python -m unittest discover -s tests -v
"""

import json
import re
import sys
import tempfile
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.checkin import normalize_checkin, save_checkin  # noqa: E402
from services.ai.coach_chat import MAX_RUNS, SYSTEM_PROMPT, build_coach_context  # noqa: E402
from services.ai.reasoning_archive import save_reasoning  # noqa: E402

TODAY = date(2026, 9, 14)

PROFILE = {
    "name": "Jose Ines Castillo",
    "sports_preference": "run",
    "available_hours": 14.25,
    "training_age_level": "intermedio",
    "garmin_password": "no-deberia-salir-nunca",
    "garmin_email": "tampoco@ejemplo.com",
    "current_injuries": [{"location": "Rodilla", "laterality": "izquierda", "pain_scale": 3}],
    "par_q_flags": {"q5": True},
}


def _populate(storage: Path, with_reasoning: bool = True) -> None:
    (storage / "season_plan.md").write_text("# Plan de Temporada\n\n## Fases\nBase, Build, Peak.", encoding="utf-8")
    (storage / "season_phases.json").write_text(json.dumps({
        "season_start_date": "2026-09-01",
        "phases": [{"name": "Base", "start_week": 1, "end_week": 4, "weekly_hours_target": 5.0,
                    "deload_weeks": [4], "intensity_emphasis": "Aeróbico."}],
        "constraints": [{
            "id": "rodilla-impacto", "statement": "La bici lleva el volumen.",
            "rationale": "Molestia de rodilla, dolor 3/10.", "kind": "sesgo_deporte",
            "strength": "recomendacion", "applies_from": "2026-09-01", "review_on": "2026-10-15",
            "source": "lesion_declarada", "status": "vigente",
        }],
    }), encoding="utf-8")
    (storage / "weekly_plan.json").write_text(json.dumps({
        "athlete_name": "Jose Ines Castillo", "start_date": "2026-09-07", "zones_table": "z",
        "weeks": [
            {"week_number": 1, "week_rationale": "Semana uno anclada a tu carga real.",
             "days": [{"date": "2026-09-07", "is_rest": False, "focus": "Fácil"}]},
            {"week_number": 2, "week_rationale": "Bajamos intensidad porque tu HRV cayó.",
             "days": [{"date": "2026-09-14", "is_rest": False, "focus": "Umbral"}]},
        ],
    }), encoding="utf-8")
    (storage / "summary.json").write_text(json.dumps({
        "athlete": "Jose Ines Castillo", "analysis_date": "2026-09-14T10:00:00",
        "prescription": {"card": "Zonas de carrera: Z2 140-153 ppm. Ritmo umbral 5:47/km."},
        "adherence": {"adherence_pct": 83.0, "completed": 5, "missed": 1,
                      "sessions": [{"date": "2026-09-07"}] * 40, "prompt_block": "x" * 5000},
    }), encoding="utf-8")

    save_checkin(storage, normalize_checkin(
        {"sleep": 5, "fatigue": 6, "stress": 4, "soreness": 5, "niggle_location": "Aquiles"},
        today=date(2026, 9, 13),
    ))

    if with_reasoning:
        save_reasoning(storage, {
            "metrics_summary": "M" * 60000,
            "physiology_summary": "P" * 60000,
            "activity_summary": "A" * 60000,
            "metrics_outputs": {"for_weekly_planner": {"signals": ["carga subiendo"] * 400}},
            "physiology_outputs": {"for_weekly_planner": {"signals": ["HRV bajo baseline"]}},
            "activity_outputs": {"for_weekly_planner": {"signals": ["ejecutó 5 de 6"]}},
            "synthesis_result": "S" * 40000,
            "week_intents": [{"week_rationale": "Bajamos intensidad.", "days": []}],
        }, kind="week")


class TheContextIsBounded(unittest.TestCase):
    def test_the_whole_context_stays_small_even_with_huge_archived_runs(self):
        """The archived run below holds ~180k characters of summarizer output
        alone. If any of that reaches the context, every single question the
        athlete asks pays for it."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            stats = build_coach_context(storage, PROFILE, max_runs=1, today=TODAY)["stats"]
            self.assertLess(stats["approx_tokens"], 12000)

    def test_the_summarizer_outputs_are_excluded_outright(self):
        """Biggest artefact by far, and raw-data views rather than reasoning --
        everything decision-relevant in them already reached the experts."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            built = build_coach_context(storage, PROFILE, max_runs=1, today=TODAY)

            self.assertNotIn("M" * 200, built["context"])
            self.assertEqual(len(built["stats"]["artefacts_excluded"]), 3)

    def test_only_one_run_is_read_by_default(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            save_reasoning(storage, {"synthesis_result": "otra corrida"}, kind="week",
                           timestamp=__import__("datetime").datetime(2026, 9, 15, 8, 0, 0))
            self.assertEqual(
                build_coach_context(storage, PROFILE, today=TODAY)["stats"]["runs_included"], 1
            )

    def test_a_caller_cannot_ask_for_more_runs_than_the_cap(self):
        """The cap is server-side on purpose: a UI or a stray query parameter
        must not be able to widen it."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            stats = build_coach_context(storage, PROFILE, max_runs=99, today=TODAY)["stats"]
            self.assertLessEqual(stats["max_runs"], MAX_RUNS)

    def test_zero_runs_still_produces_a_usable_context(self):
        """Most questions are answered by the plan itself, not the reasoning."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            built = build_coach_context(storage, PROFILE, max_runs=0, today=TODAY)
            self.assertEqual(built["stats"]["runs_included"], 0)
            self.assertIn("5:47/km", built["context"])

    def test_only_the_current_week_is_sent_in_full(self):
        """Re-sending every past week on every message is exactly the unbounded
        growth the budget exists to prevent."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            context = build_coach_context(storage, PROFILE, today=TODAY)["context"]
            self.assertIn("Semana actual", context)
            self.assertIn("Semanas anteriores", context)
            self.assertIn("2026-09-14", context)

    def test_the_bulkiest_adherence_fields_are_dropped(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            context = build_coach_context(storage, PROFILE, today=TODAY)["context"]
            self.assertIn("83.0", context)
            self.assertNotIn("x" * 200, context)

    def test_truncation_is_announced_rather_than_silent(self):
        """Silent truncation is how a model confidently completes a sentence
        that was never there."""
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            self.assertIn("recortado", build_coach_context(storage, PROFILE, today=TODAY)["context"])

    def test_the_size_is_reported_so_it_is_never_a_surprise(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            stats = build_coach_context(storage, PROFILE, today=TODAY)["stats"]
            self.assertIn("approx_tokens", stats)
            self.assertGreater(stats["chars"], 0)


class WhatSurvivesTheBudgetIsWhatAnswersQuestions(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)
        _populate(self.storage)
        self.context = build_coach_context(self.storage, PROFILE, today=TODAY)["context"]

    def tearDown(self):
        self._tmp.cleanup()

    def test_it_knows_what_day_it_is(self):
        self.assertIn("2026-09-14", self.context)

    def test_it_knows_the_athletes_real_numbers(self):
        self.assertIn("5:47/km", self.context)
        self.assertIn("140-153 ppm", self.context)

    def test_it_carries_the_week_rationale_so_why_can_be_answered(self):
        self.assertIn("Bajamos intensidad porque tu HRV cayó", self.context)

    def test_only_the_constraints_in_force_today_appear(self):
        self.assertIn("La bici lleva el volumen", self.context)

    def test_a_constraint_that_has_not_started_yet_does_not(self):
        phases = json.loads((self.storage / "season_phases.json").read_text(encoding="utf-8"))
        phases["constraints"][0]["applies_from"] = "2026-12-01"
        (self.storage / "season_phases.json").write_text(json.dumps(phases), encoding="utf-8")
        context = build_coach_context(self.storage, PROFILE, today=TODAY)["context"]
        self.assertNotIn("La bici lleva el volumen", context)

    def test_it_carries_the_declared_health_record(self):
        self.assertIn("Rodilla", self.context)

    def test_it_carries_the_last_check_in(self):
        self.assertIn("Aquiles", self.context)

    def test_credentials_never_enter_the_context(self):
        """The profile holds the athlete's Garmin login. A context assembled by
        dumping the profile would put it in every prompt."""
        self.assertNotIn("no-deberia-salir-nunca", self.context)
        self.assertNotIn("tampoco@ejemplo.com", self.context)

    def test_embedded_documents_are_fenced_so_their_headings_do_not_blur_ours(self):
        inside, sections = False, []
        for line in self.context.splitlines():
            if line.startswith("```"):
                inside = not inside
            elif not inside and line.startswith("## "):
                sections.append(line)
        self.assertIn("## Hoy", sections)
        self.assertNotIn("## Fases", sections)


class ItIsReadOnlyAndNotAClinician(unittest.TestCase):
    def test_it_is_told_it_cannot_change_the_plan(self):
        self.assertIn("No modificas el plan", SYSTEM_PROMPT)
        # Y le dice al atleta un camino que existe (2026-09-24): "vuelve a generar la semana
        # desde la app" no lo es para la semana en curso -- no hay botón hasta eliminarla, y
        # «Rehacer» se retiró en la v1.1.
        self.assertIn("eliminarla («Eliminar plan» → esta semana) y volver a generarla", SYSTEM_PROMPT)
        self.assertNotIn("Rehacer", SYSTEM_PROMPT)

    def test_it_is_told_it_is_not_a_clinician(self):
        self.assertIn("No eres médico ni fisioterapeuta", SYSTEM_PROMPT)
        self.assertIn("no diagnostiques", SYSTEM_PROMPT)

    def test_it_is_told_not_to_invent_what_it_was_not_given(self):
        """La regla se acotó a los DATOS del atleta y la frase literal cambió
        ("Nunca inventes un número" -> "Nunca inventes uno", detrás de la lista
        de qué es un dato suyo). La promesa no cambió, así que es la promesa lo
        que se comprueba: sus números salen del contexto y de ningún otro sitio.
        """
        plano = re.sub(r"\s+", " ", SYSTEM_PROMPT)
        self.assertIn("Los DATOS del atleta salen SÓLO del contexto", plano)
        self.assertIn("Nunca inventes uno", plano)
        self.assertIn("recortada", SYSTEM_PROMPT)

    def test_it_is_told_to_answer_with_this_athletes_numbers(self):
        self.assertIn("Una respuesta que serviría para cualquier atleta es una mala respuesta",
                      SYSTEM_PROMPT)


class ItIsACoachAndNotAContextReader(unittest.TestCase):
    """El fallo, reportado por el atleta el 2026-09-02.

    Le preguntó a Ben *"¿podrías explicarme para qué sirve hacer VO2 max o
    tempo?"* y contestó:

        "Eso no lo tengo en el contexto que me dieron — son preguntas de
         fisiología general, no sobre tu plan concreto. [...] si quieres
         entender qué fisiología hay detrás de esos entrenamientos, eso va más
         allá de lo que un entrenador hace en un chat: necesitas hablar con
         alguien que enseñe eso (un fisiólogo del ejercicio)."

    Dos reglas se aplicaron donde no tocaba:

      - *"Responde SOLO con lo que tienes en el contexto"* estaba escrita para
        que no se inventara SUS DATOS, y el modelo la leyó como que tampoco
        podía usar lo que sabe de entrenamiento. Correcto de leer: la regla no
        distinguía las dos cosas.
      - *"No eres médico ni fisioterapeuta"* se estiró hasta mandar al atleta a
        un fisiólogo a preguntar qué hace un intervalo.

    El resultado es un entrenador que sólo sabe releer lo que tiene delante, que
    es justo lo que no hace falta que exista. Explicar para qué sirve una sesión
    ES el oficio.
    """

    def setUp(self):
        self.plano = re.sub(r"\s+", " ", SYSTEM_PROMPT)

    def test_EL_FALLO_puede_explicar_fisiologia_del_entrenamiento(self):
        self.assertIn("Lo que sabes de ENTRENAMIENTO es tuyo y lo usas", self.plano)
        self.assertIn("eso lo explicas tú, porque eres entrenador", self.plano)

    def test_se_le_dice_que_negarse_lo_inutiliza(self):
        """Una regla sin su motivo se salta en cuanto el modelo cree tener uno
        mejor -- y aquí el motivo es lo único que separa las dos mitades."""
        self.assertIn("no es un entrenador, es un buscador roto", self.plano)

    def test_no_puede_mandarlo_a_aprender_a_otro_sitio(self):
        self.assertIn("Nunca lo mandes a otro sitio a aprender de entrenamiento", self.plano)

    def test_la_regla_medica_se_queda_en_los_SINTOMAS(self):
        """Sigue sin ser médico -- eso no se toca -- pero el límite es el
        síntoma, no la fisiología del entrenamiento."""
        self.assertIn("No eres médico ni fisioterapeuta", self.plano)
        self.assertIn("Esto es sobre SÍNTOMAS, no sobre fisiología del entrenamiento", self.plano)

    def test_lo_util_es_unir_la_fisiologia_con_SU_plan(self):
        """Ni pura teoría ni puro resumen de su semana: las dos cosas juntas
        son lo que ningún otro sitio puede darle."""
        self.assertIn("Cuando pueda, une las dos", self.plano)
        self.assertIn("para qué está eso en SU plan, con SUS números", self.plano)

    def test_una_pregunta_general_se_contesta_y_luego_se_aterriza(self):
        self.assertIn("nunca negándose a responder", self.plano)
        self.assertIn("Aunque la pregunta sea general, contéstala", self.plano)

    def test_sigue_respondiendo_el_POR_QUE_del_plan_con_la_restriccion_real(self):
        """La regla que esto NO puede haber deshecho. Explicar fisiología es
        ahora bienvenido, pero cuando pregunta por qué su plan es así, la causa
        suele ser su horario y no la fisiología."""
        self.assertIn("responde con la restricción, no con una teoría", self.plano)


class EmptyAndBrokenProfiles(unittest.TestCase):
    def test_a_profile_with_nothing_stored_still_builds_a_context(self):
        with tempfile.TemporaryDirectory() as tmp:
            built = build_coach_context(Path(tmp), {}, today=TODAY)
            self.assertIn("2026-09-14", built["context"])
            self.assertEqual(built["stats"]["runs_included"], 0)

    def test_corrupted_files_are_skipped_rather_than_raising(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _populate(storage)
            (storage / "weekly_plan.json").write_text("{no es json", encoding="utf-8")
            (storage / "summary.json").write_text("tampoco", encoding="utf-8")
            built = build_coach_context(storage, PROFILE, today=TODAY)
            self.assertIn("2026-09-14", built["context"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
