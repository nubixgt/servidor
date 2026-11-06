"""Tests for how provider API keys are validated and routed.

A key pasted into the wrong slot is worse than a missing one: the missing case
fails at once with a clear message, while a wrong-provider key sails through
startup and then 401s deep inside a paid generation, after the earlier nodes
have already been billed. Found on a real .env, where OPENROUTER_API_KEY held
a copy of the Google key.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core.config import Config  # noqa: E402

GOOGLE_KEY = "AIzaSyC" + "x" * 32
OPENROUTER_KEY = "sk-or-v1-" + "x" * 40
ANTHROPIC_KEY = "sk-ant-api03-" + "x" * 40
OPENAI_KEY = "sk-proj-" + "x" * 40


def _config(**env):
    """Build a Config from a controlled environment."""
    base = {k: "" for k in (
        "ANTHROPIC_API_KEY", "OPENAI_API_KEY", "OPENROUTER_API_KEY",
        "GEMINI_API_KEY", "DEEPSEEK_API_KEY", "AI_MODE",
    )}
    base.update(env)
    with mock.patch.dict("os.environ", base, clear=False):
        return Config.from_env()


class WrongProviderKeys(unittest.TestCase):
    def test_a_google_key_in_the_openrouter_slot_is_ignored(self):
        """The exact mistake found on a real .env."""
        config = _config(OPENROUTER_API_KEY=GOOGLE_KEY, GEMINI_API_KEY=GOOGLE_KEY)
        self.assertIsNone(config.openrouter_api_key)
        self.assertEqual(config.gemini_api_key, GOOGLE_KEY)

    def test_an_openai_key_in_the_openrouter_slot_is_ignored(self):
        self.assertIsNone(_config(OPENROUTER_API_KEY=OPENAI_KEY).openrouter_api_key)

    def test_a_real_openrouter_key_is_kept(self):
        self.assertEqual(_config(OPENROUTER_API_KEY=OPENROUTER_KEY).openrouter_api_key, OPENROUTER_KEY)

    def test_a_malformed_anthropic_key_is_rejected_outright(self):
        with self.assertRaises(ValueError):
            _config(ANTHROPIC_API_KEY="not-a-real-key")

    def test_a_malformed_openai_key_is_rejected_outright(self):
        with self.assertRaises(ValueError):
            _config(OPENAI_API_KEY="nope")

    def test_valid_keys_pass_through(self):
        config = _config(ANTHROPIC_API_KEY=ANTHROPIC_KEY, OPENAI_API_KEY=OPENAI_KEY)
        self.assertEqual(config.anthropic_api_key, ANTHROPIC_KEY)
        self.assertEqual(config.openai_api_key, OPENAI_KEY)


class RoutingPrefersDirectKeys(unittest.TestCase):
    """Direct provider APIs cost less and keep provider-specific features."""

    def _selector_with(self, **env):
        from services.ai.model_config import ModelSelector

        return ModelSelector, _config(**env)

    def test_a_direct_anthropic_key_uses_the_anthropic_client(self):
        ModelSelector, config = self._selector_with(ANTHROPIC_API_KEY=ANTHROPIC_KEY)
        with mock.patch("services.ai.model_config.get_config", return_value=config):
            from services.ai.ai_settings import AgentRole

            llm = ModelSelector.get_llm_by_role_and_name(AgentRole.WORKOUT, "claude-opus")
        self.assertEqual(type(llm).__name__, "ChatAnthropic")
        self.assertEqual(llm.model, "claude-opus-5")

    def test_thinking_is_left_to_the_model_default(self):
        """Omitting `thinking` is what keeps adaptive thinking AND reliable
        structured output: Opus 5 and Sonnet 5 think adaptively by default,
        while passing the parameter explicitly pushes langchain-anthropic onto
        a forced-tool-calling path it warns can fail. Four of the five Claude
        roles here produce structured output."""
        ModelSelector, config = self._selector_with(ANTHROPIC_API_KEY=ANTHROPIC_KEY)
        with mock.patch("services.ai.model_config.get_config", return_value=config):
            from services.ai.ai_settings import AgentRole

            llm = ModelSelector.get_llm_by_role_and_name(AgentRole.WORKOUT, "claude-opus")
        self.assertIsNone(getattr(llm, "thinking", None))
        self.assertNotIn("thinking", llm.model_kwargs or {})

    def test_no_key_at_all_fails_with_an_actionable_message(self):
        """Not a 401 halfway through a paid run."""
        ModelSelector, config = self._selector_with()
        with mock.patch("services.ai.model_config.get_config", return_value=config):
            from services.ai.ai_settings import AgentRole

            with self.assertRaises(RuntimeError) as caught:
                ModelSelector.get_llm_by_role_and_name(AgentRole.WORKOUT, "claude-opus")
        message = str(caught.exception)
        self.assertIn("ANTHROPIC_API_KEY", message)
        self.assertIn("console.anthropic.com", message)

    def test_openrouter_is_used_only_when_the_direct_key_is_absent(self):
        ModelSelector, config = self._selector_with(OPENROUTER_API_KEY=OPENROUTER_KEY)
        with mock.patch("services.ai.model_config.get_config", return_value=config):
            from services.ai.ai_settings import AgentRole

            llm = ModelSelector.get_llm_by_role_and_name(AgentRole.WORKOUT, "claude-opus")
        self.assertEqual(type(llm).__name__, "ChatOpenAI")
        self.assertIn("openrouter", str(llm.openai_api_base))

    def test_the_openrouter_route_carries_no_anthropic_only_parameters(self):
        ModelSelector, config = self._selector_with(OPENROUTER_API_KEY=OPENROUTER_KEY)
        with mock.patch("services.ai.model_config.get_config", return_value=config):
            from services.ai.ai_settings import AgentRole

            llm = ModelSelector.get_llm_by_role_and_name(AgentRole.WORKOUT, "claude-opus")
        self.assertNotIn("thinking", llm.model_kwargs or {})


class ModelsAreCurrent(unittest.TestCase):
    def test_claude_keys_resolve_to_current_model_ids(self):
        from services.ai.model_config import ModelSelector

        expected = {
            "claude-opus": "claude-opus-5",
            "claude-sonnet": "claude-sonnet-5",
            "claude-haiku": "claude-haiku-4-5",
        }
        for key, model_id in expected.items():
            with self.subTest(key=key):
                self.assertEqual(ModelSelector.CONFIGURATIONS[key].name, model_id)

    def test_superseded_aliases_still_resolve(self):
        """An older saved profile naming one must not raise 'Unknown model'."""
        from services.ai.model_config import ModelSelector

        for legacy in ("claude-4", "claude-4-thinking", "claude-opus-thinking", "claude-3-haiku"):
            with self.subTest(alias=legacy):
                self.assertIn(legacy, ModelSelector.CONFIGURATIONS)
                self.assertIn(legacy, ModelSelector.MODEL_PRICING_USD_PER_MILLION)

    def test_no_claude_model_sends_a_thinking_parameter_at_all(self):
        """budget_tokens is rejected with a 400 on these models, and even the
        valid {"type": "adaptive"} shape costs reliable structured output. The
        models think adaptively on their own when the parameter is absent."""
        from services.ai.model_config import ModelSelector

        for key, cfg in ModelSelector.MODEL_CONFIGS.items():
            if not key.startswith("claude"):
                continue
            with self.subTest(model=key):
                self.assertNotIn("thinking", cfg)

    def test_every_tier_role_resolves_to_a_priced_model(self):
        """A model with no price silently reports that call as costing $0.

        v6 Track 5 dropped FORMATTER from every tier: both formatter nodes
        have been deterministic since v5 Track 9, so the role no longer
        resolves to anything and a tier that funded it was funding nothing.
        Any OTHER role missing from a tier is a real bug and still fails
        here."""
        from core.config import AIMode
        from services.ai.ai_settings import AgentRole, AISettings
        from services.ai.model_config import ModelSelector

        for mode in (AIMode.STANDARD, AIMode.PRO):
            settings = AISettings(mode=mode)
            for role in AgentRole:
                if role is AgentRole.FORMATTER:
                    continue
                key = settings.get_model_for_role(role)
                with self.subTest(mode=mode.value, role=role.value):
                    self.assertIn(key, ModelSelector.CONFIGURATIONS)
                    self.assertIn(key, ModelSelector.MODEL_PRICING_USD_PER_MILLION)


if __name__ == "__main__":
    unittest.main(verbosity=2)
