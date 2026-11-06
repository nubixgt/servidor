"""Tests for Plan Maestro v5 Track 1: one language, and a stated policy for
what to do when the three domain experts contradict each other.

Both defects were found by reading the real stored output, not by inference:

- **Language**: `data/storage/Jose_Ines_Castillo/season_plan.md` literally opens
  with "# Strategic Season Plan (16-Week Macrocycle)" while the sessions under
  it are in Spanish. Not one prompt in the analysis branch (summarizers, the
  three experts, synthesis, season planner, HTML formatter) contained a single
  word about language, so each defaulted to English on its own.

- **Conflict**: the synthesis agent's entire system prompt was "Integrate /
  Contextualize / Simplify". With Activity saying the athlete executed the week,
  Metrics saying load is climbing and Physiology saying recovery is falling, the
  model picked a winner silently -- and picked differently on each run.

The second half matters more than it looks: `season_planner` and
`weekly_planner` read the experts' `for_*_planner` payloads DIRECTLY and never
read `synthesis_result`. Fixing only synthesis would improve the report the
athlete reads and would not change a single workout, so the policy is asserted
in both places here.

Run with:  python -m unittest discover -s tests -v
"""

import importlib
import inspect
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.prompt_components import (  # noqa: E402
    AgentType,
    get_conflict_policy,
    get_language_directive,
    get_workflow_context,
)

# nodes/__init__.py re-exports each node FUNCTION under its module's name, so
# `from ...nodes import synthesis_node` hands back the coroutine, not the
# module holding the prompt constants. Import the modules explicitly.
formatter_module = importlib.import_module("services.ai.langgraph.nodes.formatter_node")
synthesis_module = importlib.import_module("services.ai.langgraph.nodes.synthesis_node")
weekly_module = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")

# Every value of the AgentType literal, i.e. every node that writes text a
# human will read.
ALL_AGENT_TYPES = list(AgentType.__args__)


class EveryNodeIsToldWhichLanguageToWriteIn(unittest.TestCase):
    def test_the_workflow_context_carries_the_directive_for_every_agent_type(self):
        for agent_type in ALL_AGENT_TYPES:
            with self.subTest(agent_type=agent_type):
                self.assertIn("## Idioma", get_workflow_context(agent_type))

    def test_synthesis_gets_it_too(self):
        """synthesis_node does not call get_workflow_context on its own, so
        riding the directive along in there alone would have left the node that
        writes the athlete's report still producing English."""
        synthesis_src = inspect.getsource(synthesis_module.synthesis_node)
        self.assertIn("get_workflow_context(\"synthesis\")", synthesis_src)

    def test_the_report_renderer_needs_no_directive_because_it_has_no_model(self):
        """This node used to be the other half of the language problem: an LLM
        asked to turn the synthesis into HTML reproduces the language of what
        it is imitating, not the athlete's. v5 Track 9 replaced it with a
        Jinja2 template, so there is no model here left to instruct -- and
        asserting a language directive on it would be asserting against
        nothing."""
        formatter_src = inspect.getsource(formatter_module.formatter_node)
        self.assertNotIn("ModelSelector", formatter_src)
        self.assertIn("render_analysis_report", formatter_src)

    def test_raw_garmin_field_names_are_explicitly_exempt(self):
        """A blanket 'write everything in Spanish' would have the summarizers
        translating the field names of the raw payload they are quoting, which
        makes the numbers untraceable back to the API that produced them."""
        directive = get_language_directive()
        self.assertIn("No traduzcas", directive)
        self.assertIn("hrvSummary", directive)

    def test_established_technical_terms_are_not_castellanised(self):
        directive = get_language_directive()
        for term in ("VO2max", "FTP", "ACWR", "tempo", "umbral"):
            with self.subTest(term=term):
                self.assertIn(term, directive)


class ConflictsAreNamedRatherThanSilentlyResolved(unittest.TestCase):
    def test_the_policy_forbids_picking_a_side_in_silence(self):
        policy = get_conflict_policy()
        self.assertIn("Nombra la contradicción", policy)
        self.assertIn("silencio", policy)

    def test_it_weighs_by_evidence_not_by_which_expert_said_it(self):
        """Ranking the three experts once and for all would be a bias baked
        into the design: physiology outranks activity forever, regardless of
        whether the physiology reading came from six measured nights or one."""
        policy = get_conflict_policy()
        self.assertIn("no por el dominio", policy)
        self.assertIn("cobertura", policy)

    def test_an_unresolved_tie_is_an_allowed_conclusion(self):
        self.assertIn("No se puede distinguir todavía", get_conflict_policy())

    def test_synthesis_reports_the_disagreement_to_the_athlete(self):
        prompt = synthesis_module.SYNTHESIS_USER_PROMPT_BASE
        self.assertIn("Señales en conflicto", prompt)
        self.assertIn("Reconcile", prompt)

    def test_synthesis_omits_the_section_when_the_domains_agree(self):
        """A report with a permanent 'conflicting signals' heading that says
        'none' every week trains the athlete to skip it."""
        self.assertIn("Omit this section entirely", synthesis_module.SYNTHESIS_USER_PROMPT_BASE)


class TheDecisionLayerAlsoActsOnTheConflict(unittest.TestCase):
    def test_the_decision_prompt_carries_both_the_policy_and_its_consequence(self):
        """The planners never read synthesis_result -- they read the experts'
        for_*_planner payloads directly. The policy has to be here too or no
        workout changes."""
        src = inspect.getsource(weekly_module.weekly_planner_node)
        self.assertIn("get_conflict_policy()", src)
        self.assertIn("CONFLICT_CONSEQUENCE_FOR_TRAINING", src)

    def test_recovery_governs_intensity_and_consistency_governs_volume(self):
        consequence = weekly_module.CONFLICT_CONSEQUENCE_FOR_TRAINING
        self.assertIn("INTENSIDAD", consequence)
        self.assertIn("VOLUMEN", consequence)

    def test_it_is_written_as_an_overridable_default_not_a_law(self):
        """The athlete's standing instruction for this project: a deterministic
        rule must never be able to override a coach's judgement about a
        specific athlete. Training through a heavy block on purpose is real
        coaching, so the split has to be an overridable default."""
        consequence = weekly_module.CONFLICT_CONSEQUENCE_FOR_TRAINING
        self.assertIn("no leyes", consequence)
        self.assertIn("si dices por qué", consequence)

    def test_a_conflict_is_never_resolved_by_cancelling_the_session(self):
        """Same principle the deterministic fallbacks already follow (see
        _apply_weekly_hours_fallback): shorten, never cancel."""
        self.assertIn("No resuelvas el empate con descanso", weekly_module.CONFLICT_CONSEQUENCE_FOR_TRAINING)

    def test_the_athlete_gets_told_which_way_it_was_resolved(self):
        self.assertIn("week_rationale", weekly_module.CONFLICT_CONSEQUENCE_FOR_TRAINING)


if __name__ == "__main__":
    unittest.main(verbosity=2)
