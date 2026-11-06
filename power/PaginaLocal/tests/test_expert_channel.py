"""Tests for Plan Maestro v5 Track 4: the experts' judgement arrives with a
weight attached, and stops travelling inside a union.

`ReceiverPayload` was already structured -- signals / evidence / implications /
uncertainty. What it never carried was **how much any of it mattered**. Three
domains look at the same athlete from three angles and routinely disagree
("ejecutó todo lo prescrito" / "la carga viene subiendo" / "la recuperación
viene cayendo"), and the consumer had no way to tell which claim outranked
which. It picked one silently, and differently on each run.

Note what is deliberately NOT here: any ranking of the experts themselves.
Deciding once and for all that physiology outranks activity would hold whether
the physiology read came from six measured nights or one. Weight travels with
the claim, never with the domain -- `test_no_expert_outranks_another_by_design`
pins that.

The second half is the union removal, the same fix v4 verified on the workout
formatter: `list[Question] | ReceiverOutputs` in a structured-output schema
measurably degrades what comes back, and the web app never asks questions.

Run with:  python -m unittest discover -s tests -v
"""

import importlib
import inspect
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.expert_outputs import (  # noqa: E402
    MetricsExpertOutputs,
    ReceiverOutputs,
    ReceiverPayload,
)
from services.ai.langgraph.utils.output_helper import (  # noqa: E402
    _render_receiver_payload,
    extract_expert_output,
)

EXPERT_MODULES = ("metrics", "activity", "physiology")


def _payload(**overrides) -> dict:
    base = {
        "signals": ["HRV por debajo de su propio rango"],
        "evidence": ["48 ms anoche vs banda personal 56-82 ms"],
        "implications": ["Limitar intensidad esta semana"],
    }
    base.update(overrides)
    return base


def _outputs(**overrides) -> ReceiverOutputs:
    payload = ReceiverPayload(**_payload(**overrides))
    return ReceiverOutputs(
        for_synthesis=payload, for_season_planner=payload, for_weekly_planner=payload,
    )


class WeightTravelsWithTheClaim(unittest.TestCase):
    def test_priority_confidence_and_horizon_are_rendered_before_the_claims(self):
        """A consumer reading three payloads back to back has to know how much
        each counts BEFORE it reads them, or it weighs them all the same."""
        rendered = _render_receiver_payload(_payload(priority=1, confidence="alta", horizon_days=2))
        weight_line = rendered.splitlines()[0]
        self.assertIn("prioridad 1", weight_line)
        self.assertIn("confianza alta", weight_line)
        self.assertIn("~2 días", weight_line)

    def test_a_likely_contradiction_gets_its_own_section(self):
        rendered = _render_receiver_payload(_payload(
            conflicts_with="Actividad dirá que ejecutó todo; otra noche de HRV lo resolvería.",
        ))
        self.assertIn("Posible contradicción con otro dominio", rendered)
        self.assertIn("otra noche de HRV", rendered)

    def test_no_contradiction_means_no_empty_section(self):
        self.assertNotIn("Posible contradicción", _render_receiver_payload(_payload(conflicts_with="")))

    def test_a_legacy_payload_renders_no_weight_line_rather_than_defaults(self):
        """Plans and archived runs from before this existed carry none of these
        fields. Rendering defaults there would print a judgement nobody made."""
        rendered = _render_receiver_payload(_payload())
        self.assertNotIn("**Peso**", rendered)
        self.assertTrue(rendered.startswith("### Signals"))

    def test_an_out_of_range_priority_is_ignored_rather_than_mislabelled(self):
        self.assertNotIn("**Peso**", _render_receiver_payload(_payload(priority=9)))

    def test_a_missing_horizon_simply_omits_that_part(self):
        rendered = _render_receiver_payload(_payload(priority=2, horizon_days=None))
        self.assertIn("prioridad 2", rendered)
        self.assertNotIn("vigencia", rendered)

    def test_the_original_four_sections_still_render_unchanged(self):
        rendered = _render_receiver_payload(_payload(uncertainty=["cobertura parcial de sueño"]))
        for section in ("### Signals", "### Evidence", "### Implications", "### Uncertainty"):
            with self.subTest(section=section):
                self.assertIn(section, rendered)


class TheScaleIsUsableRatherThanDecorative(unittest.TestCase):
    def test_priority_is_bounded_so_it_stays_a_ranking(self):
        for bad in (0, 4, -1):
            with self.subTest(priority=bad):
                with self.assertRaises(Exception):
                    ReceiverPayload(**_payload(priority=bad))

    def test_confidence_is_a_closed_vocabulary(self):
        with self.assertRaises(Exception):
            ReceiverPayload(**_payload(confidence="bastante"))

    def test_the_prompt_warns_against_marking_everything_top_priority(self):
        """A scale where everything is a 1 carries no information, which would
        put us back exactly where this track started."""
        for domain in EXPERT_MODULES:
            module = importlib.import_module(f"services.ai.langgraph.nodes.{domain}_expert_node")
            prompt = next(
                v for k, v in vars(module).items()
                if k.endswith("_USER_PROMPT") and isinstance(v, str)
            )
            with self.subTest(domain=domain):
                self.assertIn("Reserve 1 for the few things", prompt)
                self.assertIn("destroys the signal", prompt)

    def test_confidence_is_defined_as_a_property_of_the_data_not_of_the_model(self):
        module = importlib.import_module("services.ai.langgraph.nodes.metrics_expert_node")
        self.assertIn("not about how sure you feel", module.METRICS_USER_PROMPT)

    def test_experts_are_told_not_to_soften_a_read_to_avoid_a_conflict(self):
        module = importlib.import_module("services.ai.langgraph.nodes.physiology_expert_node")
        self.assertIn("never soften your own read", module.PHYSIOLOGY_USER_PROMPT)

    def test_no_expert_outranks_another_by_design(self):
        """If this ever fails, someone hard-coded a domain hierarchy -- which
        would hold regardless of whether the winning read came from six
        measured nights or one."""
        schema = (
            Path(__file__).resolve().parents[1]
            / "services/ai/langgraph/schemas/expert_outputs.py"
        ).read_text(encoding="utf-8")
        self.assertIn("Weight travels with the claim", schema)
        self.assertNotIn("DOMAIN_PRIORITY", schema)


class TheUnionIsGoneWhereNobodyAsksQuestions(unittest.TestCase):
    def test_every_expert_binds_the_bare_shape_when_hitl_is_off(self):
        for domain in EXPERT_MODULES:
            module = importlib.import_module(f"services.ai.langgraph.nodes.{domain}_expert_node")
            src = inspect.getsource(getattr(module, f"{domain}_expert_node"))
            with self.subTest(domain=domain):
                # Desde el 2026-09-23 la forma sin unión la da `salida_para` (sólo las
                # secciones que se van a leer); lo que importa es que no sea la unión.
                self.assertIn("if hitl_enabled else salida_para(", src)
        from services.ai.langgraph.schemas.expert_outputs import salida_para
        for receptores in (None, ["for_weekly_planner"], ["for_synthesis", "for_season_planner"]):
            with self.subTest(receptores=receptores):
                self.assertNotIn("output", salida_para(receptores).model_fields)

    def test_a_bare_receiver_outputs_is_read_correctly_downstream(self):
        """extract_expert_output has to fall through to the object itself when
        there is no `.output` wrapper, or narrowing the schema would break
        every consumer."""
        rendered = extract_expert_output(_outputs(priority=1), "for_weekly_planner")
        self.assertIn("prioridad 1", rendered)
        self.assertIn("48 ms anoche", rendered)

    def test_the_wrapped_hitl_shape_still_works(self):
        wrapped = MetricsExpertOutputs(output=_outputs(confidence="baja"))
        rendered = extract_expert_output(wrapped, "for_season_planner")
        self.assertIn("confianza baja", rendered)

    def test_the_orchestrator_finds_no_questions_on_a_bare_payload(self):
        """A ReceiverOutputs has no `.output`, so the question collector must
        simply skip it rather than mistake the analysis for questions."""
        orchestrator = importlib.import_module("services.ai.langgraph.nodes.orchestrator_node")
        node = orchestrator.MasterOrchestrator()
        state = {"metrics_outputs": _outputs()}
        self.assertEqual(node._collect_questions(state, ["metrics_outputs"], ["metrics"]), [])

    def test_questions_are_still_collected_from_the_wrapped_shape(self):
        from services.ai.langgraph.schemas.agent_outputs import Question

        orchestrator = importlib.import_module("services.ai.langgraph.nodes.orchestrator_node")
        node = orchestrator.MasterOrchestrator()
        wrapped = MetricsExpertOutputs(output=[Question(id="metrics_q1", message="¿Nadas en piscina?")])
        collected = node._collect_questions({"metrics_outputs": wrapped}, ["metrics_outputs"], ["metrics"])
        self.assertEqual(len(collected), 1)
        self.assertEqual(collected[0]["agent"], "metrics")

    def test_a_failed_expert_still_degrades_to_a_readable_note(self):
        rendered = extract_expert_output(None, "for_weekly_planner")
        self.assertIn("unavailable", rendered)


if __name__ == "__main__":
    unittest.main(verbosity=2)
