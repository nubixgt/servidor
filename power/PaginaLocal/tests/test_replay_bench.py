"""Plan Maestro v6 Track 1 -- the model replay bench.

The bench exists so that "which model should the Pro tier use for the weekly
planner?" can be answered by evidence instead of intuition, for less than the
cost of one generation. That only works if three things hold, and each one is
pinned here:

1. **The prompt is captured exactly**, including the output schema, in a form
   that a DIFFERENT provider can be asked to honour. The three providers ask
   for structured output in three different shapes; all three were verified
   live against gemini-3.1-flash-lite, gpt-5.2 and claude-sonnet-5, and the
   fixtures below are those real shapes.
2. **The cost is real.** A bench that reports $0 is worse than no bench. The
   dated-snapshot case below is a real bug found while building this: OpenAI
   answers a request for "gpt-5.2" with "gpt-5.2-2025-12-11", which matched no
   pricing row, so every OpenAI-routed call in the whole app -- synthesis and
   the season planner in the Pro tier -- reported as free.
3. **The comparison does not favour one sport.** This is a product for
   runners, swimmers, cyclists and triathletes. A summary that measured a week
   in kilometres run would quietly rank models by how much running they write.

Run with:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from datetime import datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from langchain_core.messages import HumanMessage, SystemMessage  # noqa: E402

from services.ai.model_config import ModelSelector  # noqa: E402
from services.ai.number_audit import audit_summary, find_unsupported_numbers  # noqa: E402
from services.ai.prompt_capture import PromptCapture, extract_output_schema  # noqa: E402
from services.ai.reasoning_archive import (  # noqa: E402
    list_prompt_calls,
    load_prompt_call,
    load_reasoning_entry,
    save_reasoning,
)
from services.ai.replay import (  # noqa: E402
    _coerce_structured,
    build_comparison,
    describe_output,
    estimate_cost_usd,
    original_record,
    resolve_call,
)

# The exact schema body a captured call carries, trimmed to what the bench
# actually reads back.
WEEK_SCHEMA = {
    "type": "object",
    "properties": {"week_number": {"type": "integer"}, "days": {"type": "array"}},
    "required": ["week_number", "days"],
}


class TheOutputSchemaSurvivesEveryProvider(unittest.TestCase):
    """A replay is only comparable if the second model is asked for the same
    thing as the first. Since each provider encodes that request differently,
    what gets stored is the one thing they share: a JSON Schema."""

    def test_google_native_json_mode(self):
        found = extract_output_schema({
            "model": "gemini-3.1-flash-lite",
            "response_mime_type": "application/json",
            "response_json_schema": {**WEEK_SCHEMA, "title": "WeekIntentContent"},
        })
        self.assertEqual(found["name"], "WeekIntentContent")
        self.assertEqual(found["schema"]["required"], ["week_number", "days"])

    def test_openai_response_format(self):
        found = extract_output_schema({
            "model": "gpt-5.2",
            "response_format": {
                "type": "json_schema",
                "json_schema": {"name": "WeekIntentContent", "schema": WEEK_SCHEMA},
            },
        })
        self.assertEqual(found["name"], "WeekIntentContent")
        self.assertEqual(found["schema"], WEEK_SCHEMA)

    def test_openai_responses_api_hands_over_the_pydantic_class(self):
        """Lo que manda de verdad langchain-openai 1.6.0 por la Responses API (visto el
        2026-09-23): `response_format` es la CLASE, no un dict. Hasta entonces ninguna
        llamada a GPT guardaba su esquema -- la decisión semanal, los expertos y el
        macrociclo de la traza del atleta 2 tenían `output_schema: null` -- y el banco
        los replicaba como texto libre."""
        from services.ai.langgraph.schemas.plan_outputs import WeekIntentContent

        found = extract_output_schema({"model": "gpt-5.2", "response_format": WeekIntentContent})
        self.assertEqual(found["name"], "WeekIntentContent")
        self.assertEqual(found["schema"], WeekIntentContent.model_json_schema())

    def test_anthropic_forced_tool(self):
        found = extract_output_schema({
            "model": "claude-sonnet-5",
            "tools": [{"name": "WeekIntentContent", "input_schema": WEEK_SCHEMA, "description": ""}],
            "tool_choice": {"type": "tool", "name": "WeekIntentContent"},
        })
        self.assertEqual(found["name"], "WeekIntentContent")
        self.assertEqual(found["schema"], WEEK_SCHEMA)

    def test_a_free_text_call_reports_no_schema(self):
        """The summarizers return markdown. Inventing a schema for them on
        replay would change what the node was asked to do."""
        self.assertIsNone(extract_output_schema({"model": "gemini-3.1-flash-lite", "temperature": 1.0}))

    def test_an_unrecognised_shape_is_not_guessed(self):
        self.assertIsNone(extract_output_schema({"some_future_provider_field": {"schema": WEEK_SCHEMA}}))


class TheCaptureNeverBreaksAGeneration(unittest.TestCase):
    """An archive is a nice-to-have. A callback that raised would kill a run
    the athlete already paid for."""

    def test_it_records_node_model_and_prompt(self):
        capture = PromptCapture()
        capture.on_chat_model_start(
            {}, [[SystemMessage("reglas"), HumanMessage("decide la semana")]],
            run_id="r1", parent_run_id=None, tags=["seq:step:1"],
            metadata={"langgraph_node": "weekly_planner", "ls_model_name": "gemini-3.5-flash",
                      "ls_provider": "google_genai"},
            invocation_params={"response_json_schema": {**WEEK_SCHEMA, "title": "WeekIntentContent"}},
        )
        call = capture.calls[0]
        self.assertEqual(call["node"], "weekly_planner")
        self.assertEqual(call["model"], "gemini-3.5-flash")
        self.assertEqual(call["output_schema"]["name"], "WeekIntentContent")
        self.assertEqual([m["role"] for m in call["messages"]], ["system", "user"])
        self.assertEqual(call["prompt_chars"], len("reglas") + len("decide la semana"))

    def test_a_call_with_no_langgraph_metadata_is_still_kept(self):
        """The coach chatbot and any direct ModelSelector call run outside the
        graph. Losing them silently would make the archive lie by omission."""
        capture = PromptCapture()
        capture.on_chat_model_start({}, [[HumanMessage("hola")]], run_id="r1", metadata={})
        self.assertEqual(capture.calls[0]["node"], "unknown")

    def test_garbage_input_does_not_raise(self):
        capture = PromptCapture()
        capture.on_chat_model_start({}, None, run_id="r1", metadata=None, invocation_params=None)
        capture.on_llm_end(object(), run_id="nonexistent")
        capture.on_llm_error(ValueError("boom"), run_id="nonexistent")
        self.assertEqual(len(capture.calls), 1)

    def test_something_that_is_not_a_langchain_message_is_still_recorded(self):
        """The fallback path: content matters more than the role label, and
        losing the call entirely would be worse than either."""
        capture = PromptCapture()
        capture.on_chat_model_start({}, [[_NotAMessage("no soy un BaseMessage")]], run_id="r1", metadata={})
        self.assertIn("no soy un BaseMessage", capture.calls[0]["messages"][0]["content"])

    def test_summary_counts_calls_per_node(self):
        capture = PromptCapture()
        for node in ("weekly_planner", "weekly_planner", "activity_summarizer"):
            capture.on_chat_model_start({}, [[HumanMessage("x")]], run_id=node + str(len(capture.calls)),
                                        metadata={"langgraph_node": node})
        self.assertEqual(capture.summary()["calls_by_node"],
                         {"weekly_planner": 2, "activity_summarizer": 1})


class _NotAMessage:
    """Anything a future LangChain version might hand the callback."""

    type = "human"

    def __init__(self, content):
        self.content = content


class ThePromptsReachDiskAndComeBack(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)
        self.calls = [
            {"index": 0, "node": "weekly_planner", "model": "gemini-3.5-flash",
             "provider": "google_genai", "prompt_chars": 42,
             "messages": [{"role": "user", "content": "decide"}],
             "output_schema": {"name": "WeekIntentContent", "schema": WEEK_SCHEMA},
             "usage": {"input_tokens": 14952, "output_tokens": 18797, "total_tokens": 33749}},
            {"index": 1, "node": "weekly_planner", "model": "gemini-3.5-flash",
             "provider": "google_genai", "prompt_chars": 21,
             "messages": [{"role": "user", "content": "formatea"}],
             "output_schema": {"name": "WeekBlock", "schema": WEEK_SCHEMA}},
        ]
        self.entry_id = save_reasoning(
            self.storage, {"prompt_calls": self.calls, "athlete_name": "Test"}, kind="week",
        )

    def tearDown(self):
        self._tmp.cleanup()

    def test_the_index_lists_every_call_without_carrying_the_prompts(self):
        index = list_prompt_calls(self.storage, self.entry_id)
        self.assertEqual(len(index), 2)
        self.assertEqual(index[0]["node"], "weekly_planner")
        self.assertEqual(index[0]["output_schema"], "WeekIntentContent")
        self.assertNotIn("messages", index[0])

    def test_one_call_round_trips_with_its_messages_and_schema(self):
        index = list_prompt_calls(self.storage, self.entry_id)
        call = load_prompt_call(self.storage, self.entry_id, index[0]["call_id"])
        self.assertEqual(call["messages"], [{"role": "user", "content": "decide"}])
        self.assertEqual(call["output_schema"]["schema"], WEEK_SCHEMA)

    def test_the_manifest_says_how_much_prompt_it_holds(self):
        manifest = json.loads(
            (self.storage / "reasoning" / self.entry_id / "manifest.json").read_text(encoding="utf-8")
        )
        self.assertEqual(manifest["prompt_calls"], 2)
        self.assertGreater(manifest["prompt_bytes"], 0)

    def test_the_coach_chat_context_does_not_swallow_the_prompts(self):
        """load_reasoning_entry feeds the chatbot's bounded context. Prompts
        live in a subdirectory precisely so a run's own inputs -- ~250 KB for
        one week, over a megabyte for a season -- never leak into it."""
        contents = load_reasoning_entry(self.storage, self.entry_id)
        self.assertNotIn("prompts", contents)
        self.assertTrue(all(not name.endswith(".json") or "__" not in name for name in contents))

    def test_a_run_captured_before_v6_reports_zero_calls_not_a_crash(self):
        # An explicit timestamp: entry ids are second-resolution, and setUp's
        # archive would otherwise be reused rather than a new one written.
        entry = save_reasoning(
            self.storage, {"athlete_name": "Old"}, kind="week",
            timestamp=datetime(2020, 1, 1, 0, 0, 0),
        )
        self.assertEqual(list_prompt_calls(self.storage, entry), [])

    def test_a_call_can_be_selected_by_id_by_node_or_by_index(self):
        index = list_prompt_calls(self.storage, self.entry_id)
        by_id = resolve_call(self.storage, self.entry_id, index[1]["call_id"])
        self.assertEqual(by_id["index"], 1)
        # A node name is ambiguous on purpose: weekly_planner makes a decision
        # call and a formatting call. The decision one is what you want.
        by_node = resolve_call(self.storage, self.entry_id, "weekly_planner")
        self.assertEqual(by_node["index"], 0)
        self.assertEqual(resolve_call(self.storage, self.entry_id, "1")["index"], 1)
        self.assertIsNone(resolve_call(self.storage, self.entry_id, "no_such_node"))


class TheCostIsKnownBeforeSpendingIt(unittest.TestCase):
    def test_the_estimate_uses_the_original_call_s_real_token_counts(self):
        """Measured on the athlete's real week: 14,952 in / 18,797 out. On
        gpt-5 ($1.75 / $14.00 since 2026-09-23; the table had half of it) that is $0.289."""
        call = {"usage": {"input_tokens": 14952, "output_tokens": 18797}, "prompt_chars": 60000}
        estimate = estimate_cost_usd(call, "gpt-5")
        self.assertAlmostEqual(estimate["usd"], (14952 * 1.75 + 18797 * 14.00) / 1e6, places=4)

    def test_without_recorded_usage_it_falls_back_to_the_prompt_size(self):
        estimate = estimate_cost_usd({"prompt_chars": 40000}, "gemini-3.1-flash-lite")
        self.assertEqual(estimate["input_tokens"], 10000)
        self.assertIsNotNone(estimate["usd"])

    def test_a_model_with_no_price_row_says_so_instead_of_saying_zero(self):
        """v6 finding A9: six models are configured without a pricing row.
        Reporting them as free is how a cost report lies in silence."""
        estimate = estimate_cost_usd({"usage": {"input_tokens": 100, "output_tokens": 100}}, "grok-4")
        self.assertIsNone(estimate["usd"])
        self.assertIn("grok-4", estimate["note"])


class ADatedModelSnapshotStillHasAPrice(unittest.TestCase):
    """Found live while building the bench: a replay on gpt-5 reported $0.00
    for 312 input and 4,761 output tokens. OpenAI answers a request for
    "gpt-5.2" with "gpt-5.2-2025-12-11" in its usage metadata, which matched
    no CONFIGURATIONS entry, so the reverse lookup returned None and every
    OpenAI-routed call in the app costed zero -- including the Pro tier's
    synthesis and season planner."""

    def test_the_dated_snapshot_resolves_to_the_same_price(self):
        dated = ModelSelector.estimate_cost_usd("gpt-5.2-2025-12-11", 1_000_000, 1_000_000)
        plain = ModelSelector.estimate_cost_usd("gpt-5.2", 1_000_000, 1_000_000)
        self.assertEqual(dated, plain)
        self.assertAlmostEqual(dated, sum(ModelSelector.MODEL_PRICING_USD_PER_MILLION["gpt-5"]), places=4)
        self.assertGreater(dated, 0)

    def test_an_anthropic_style_date_suffix_resolves_too(self):
        self.assertEqual(
            ModelSelector._find_config_key_for_model_name("claude-sonnet-5-20250929"), "claude-sonnet"
        )

    def test_a_longer_model_name_wins_over_a_shorter_prefix(self):
        """"gpt-5.2-pro-2026-01-01" must not collapse into gpt-5.2's price:
        one is twelve times the other."""
        key = ModelSelector._find_config_key_for_model_name("gpt-5.2-pro-2026-01-01")
        self.assertIn("gpt-5.2-pro", key)

    def test_a_genuinely_unknown_model_still_reports_zero_and_warns(self):
        self.assertIsNone(ModelSelector._find_config_key_for_model_name("some-model-nobody-configured"))


class TheComparisonDoesNotFavourASport(unittest.TestCase):
    """A triathlete's week and a swimmer's week must both be summarised on
    their own terms. Minutes are the only currency all disciplines share."""

    def _week(self, days, rationale=""):
        return {"week_number": 1, "days": days, "week_rationale": rationale}

    def test_a_swim_and_bike_week_is_summarised_in_its_own_disciplines(self):
        summary = describe_output(self._week([
            {"date": "2026-09-01", "is_rest": False, "sport": "swim", "discipline": "tecnica",
             "intensity_class": "easy", "total_duration_minutes": 45, "session_sketch": "drills"},
            {"date": "2026-09-02", "is_rest": False, "sport": "bike", "discipline": "bici",
             "intensity_class": "tempo_threshold", "total_duration_minutes": 90, "session_sketch": "2x20"},
            {"date": "2026-09-03", "is_rest": True, "sport": "rest", "discipline": "descanso",
             "intensity_class": "rest"},
        ]))
        self.assertEqual(summary["minutes_by_sport"], {"bike": 90.0, "swim": 45.0})
        self.assertEqual(summary["minutes_by_discipline"], {"bici": 90.0, "tecnica": 45.0})
        self.assertEqual(summary["sessions"], 2)
        self.assertEqual(summary["rest_days"], 1)
        self.assertEqual(summary["total_hours"], 2.25)

    def test_disciplines_garmin_cannot_hold_are_counted_like_any_other(self):
        """v5's whole point: Garmin is a translation step, not a design limit.
        A bench that dropped plyometrics or rehab would penalise exactly the
        models that prescribe them."""
        summary = describe_output(self._week([
            {"date": "2026-09-01", "is_rest": False, "sport": "run", "discipline": "pliometria",
             "intensity_class": "neuromuscular", "total_duration_minutes": 20, "session_sketch": "saltos"},
            {"date": "2026-09-02", "is_rest": False, "sport": "strength", "discipline": "rehab",
             "intensity_class": "strength_freeform", "total_duration_minutes": 30, "session_sketch": "gluteo"},
        ]))
        self.assertEqual(summary["minutes_by_discipline"], {"pliometria": 20.0, "rehab": 30.0})

    def test_a_session_with_no_sketch_is_counted_as_unusable(self):
        """A non-rest day with an empty sketch gives the formatting step
        nothing to encode. It is the most direct measure of whether a model
        can do this job at all."""
        summary = describe_output(self._week([
            {"date": "2026-09-01", "is_rest": False, "sport": "run", "discipline": "carrera",
             "intensity_class": "easy", "total_duration_minutes": 40, "session_sketch": "   "},
        ]))
        self.assertEqual(summary["sessions_without_sketch"], 1)

    def test_two_a_days_are_visible(self):
        summary = describe_output(self._week([
            {"date": "2026-09-01", "session_slot": "am", "is_rest": False, "sport": "swim",
             "discipline": "natacion", "intensity_class": "easy", "total_duration_minutes": 40,
             "session_sketch": "suave"},
            {"date": "2026-09-01", "session_slot": "pm", "is_rest": False, "sport": "run",
             "discipline": "carrera", "intensity_class": "easy", "total_duration_minutes": 30,
             "session_sketch": "trote"},
        ]))
        self.assertEqual(summary["two_a_days"], 1)

    def test_the_wrapper_around_the_week_is_unwrapped(self):
        """TrainingDecisionOutputs nests the week under `output`. Two models
        must be compared on the week, not on whether they emitted a wrapper."""
        summary = describe_output({"output": self._week([
            {"date": "2026-09-01", "is_rest": True, "sport": "rest", "discipline": "descanso",
             "intensity_class": "rest"},
        ])})
        self.assertEqual(summary["shape"], "week")
        self.assertEqual(summary["rest_days"], 1)

    def test_a_non_week_output_still_gets_a_comparable_shape(self):
        self.assertEqual(describe_output("un informe largo")["shape"], "text")
        self.assertEqual(describe_output({"markdown": "..."})["shape"], "object")

    def test_the_comparison_table_lines_up_two_replays(self):
        records = [
            {"replay_model": "gpt-5", "cost": {"cost_usd": 0.14}, "latency_seconds": 30,
             "call_id": "000__weekly_planner__x", "entry_id": "E",
             "summary": {"sessions": 5, "total_hours": 4.0}},
            {"replay_model": "gemini-3.5-flash", "cost": {"cost_usd": 0.19}, "latency_seconds": 40,
             "call_id": "000__weekly_planner__x", "entry_id": "E",
             "summary": {"sessions": 6, "total_hours": 4.5}},
        ]
        table = build_comparison(records)
        self.assertEqual(table["models"], ["gpt-5", "gemini-3.5-flash"])
        rows = {row["label"]: row["values"] for row in table["rows"]}
        self.assertEqual(rows["Sesiones"], [5, 6])
        self.assertEqual(rows["Costo real (USD)"], [0.14, 0.19])


class QuotedNumbersAreCheckedAgainstWhatTheModelWasGiven(unittest.TestCase):
    """v6's first acceptance criterion, made measurable. The real week told
    the athlete their ACWR was "un elevado 1.47" while Garmin said 1.10
    OPTIMAL -- a number they can check on their own watch and find wrong."""

    def test_a_number_that_appears_nowhere_is_flagged(self):
        findings = find_unsupported_numbers(
            "Tu ACWR de 1.47 exige bajar la carga.",
            ["ACWR actual segun Garmin: 1.10 (OPTIMAL)."],
        )
        self.assertEqual([f["literal"] for f in findings], ["1.47"])

    def test_a_number_that_was_given_is_not_flagged(self):
        self.assertEqual(
            find_unsupported_numbers("Tu ACWR de 1.10 esta en rango.", ["ACWR: 1.10 (OPTIMAL)"]), []
        )

    def test_the_spanish_and_english_decimal_marks_are_the_same_number(self):
        self.assertEqual(find_unsupported_numbers("umbral 5,47", ["umbral 5.47"]), [])

    def test_small_integers_are_not_treated_as_measurements(self):
        """Rep counts, zone numbers and session counts come from the plan the
        coach just wrote, not from data they were handed. Flagging them would
        bury the real findings."""
        self.assertEqual(find_unsupported_numbers("3 sesiones en Z2 con 4 series", []), [])

    def test_a_rounded_integer_is_supported_by_the_precise_value(self):
        """"los 21 km" is a fair way to refer to a 21.2 km race."""
        self.assertEqual(find_unsupported_numbers("tras los 21 km del domingo", ["distancia 21.2 km"]), [])

    def test_a_clock_time_is_checked_like_any_other_measurement(self):
        findings = find_unsupported_numbers("tu umbral de 4:48/km", ["umbral 5:47/km"])
        self.assertEqual([f["literal"] for f in findings], ["4:48"])

    def test_the_summary_counts_checked_and_unsupported_alike(self):
        summary = audit_summary(
            "ACWR 1.47, HRV 63 ms, volumen 2.9 h.",
            ["ACWR 1.10, HRV 63 ms, volumen objetivo 2.9 h"],
        )
        self.assertEqual(summary["numbers_checked"], 3)
        self.assertEqual(summary["unsupported_count"], 1)

    def test_it_reports_where_the_number_was_used(self):
        finding = find_unsupported_numbers("y permitir que tu ACWR baje de un elevado 1.47 esta semana", [])[0]
        self.assertIn("ACWR", finding["context"])


class WhatTheModelAnsweredIsKeptTooSoTheBaselineIsFree(unittest.TestCase):
    """v6 Track 5. A comparison should start from what actually happened, and
    what actually happened is already on disk. Paying to re-run the model that
    already ran, just to have something to compare against, would be the bench
    charging for something it has."""

    class _Message:
        def __init__(self, content=None, tool_calls=None, usage=None):
            self.content = content
            self.tool_calls = tool_calls or []
            self.usage_metadata = usage

    class _Generation:
        def __init__(self, message, text=""):
            self.message = message
            self.text = text

    class _Response:
        def __init__(self, generations, llm_output=None):
            self.generations = generations
            self.llm_output = llm_output

    def _capture_with(self, response, output_schema=None):
        capture = PromptCapture()
        capture.on_chat_model_start(
            {}, [[HumanMessage("hola")]], run_id="r1",
            metadata={"langgraph_node": "weekly_planner", "ls_model_name": "gemini-3.5-flash"},
            invocation_params=(
                {"response_json_schema": {**WEEK_SCHEMA, "title": "WeekIntentContent"}}
                if output_schema else {}
            ),
        )
        capture.on_llm_end(response, run_id="r1")
        return capture.calls[0]

    def test_a_forced_tool_calls_arguments_are_the_answer(self):
        """Anthropic answers a structured call this way: the object arrives as
        tool-call arguments and the text is empty."""
        message = self._Message(content="", tool_calls=[{"name": "W", "args": {"week_number": 1, "days": []}}])
        call = self._capture_with(self._Response([[self._Generation(message)]]))
        self.assertEqual(call["response"], {"week_number": 1, "days": []})

    def test_plain_text_is_kept_as_text(self):
        message = self._Message(content="## Resumen\nTodo bien.")
        call = self._capture_with(self._Response([[self._Generation(message)]]))
        self.assertIn("Resumen", call["response"])

    def test_content_blocks_are_flattened(self):
        message = self._Message(content=[{"type": "text", "text": "parte uno "}, {"type": "text", "text": "y dos"}])
        call = self._capture_with(self._Response([[self._Generation(message)]]))
        self.assertEqual(call["response"], "parte uno y dos")

    def test_a_response_it_cannot_read_is_recorded_as_absent_not_as_a_crash(self):
        call = self._capture_with(self._Response([]))
        self.assertIsNone(call["response"])

    def test_googles_json_mode_answer_is_read_back_as_the_object(self):
        """Google's native JSON mode answers with the JSON as message TEXT
        while Anthropic answers with tool-call arguments. Both are the same
        object; a baseline scored as "a string of 900 characters" compares
        against nothing."""
        payload = '{"week_number": 1, "days": [{"date": "2026-09-01", "is_rest": true, "sport": "rest"}]}'
        self.assertEqual(
            _coerce_structured(payload, {"name": "W", "schema": WEEK_SCHEMA})["week_number"], 1
        )

    def test_a_fenced_json_answer_is_unwrapped(self):
        fenced = '```json\n{"week_number": 2, "days": []}\n```'
        self.assertEqual(
            _coerce_structured(fenced, {"name": "W", "schema": WEEK_SCHEMA})["week_number"], 2
        )

    def test_text_from_a_free_text_call_is_left_alone(self):
        self.assertEqual(_coerce_structured("un informe", None), "un informe")

    def test_unparseable_text_is_left_alone_rather_than_lost(self):
        self.assertEqual(_coerce_structured("no soy json", {"schema": WEEK_SCHEMA}), "no soy json")


class TheBaselineRowCostsNothing(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)
        week = {"week_number": 1, "week_rationale": "Semana suave tras el 21.2 km del domingo.",
                "days": [
                    {"date": "2026-09-01", "is_rest": False, "sport": "swim", "discipline": "natacion",
                     "intensity_class": "easy", "total_duration_minutes": 45, "session_sketch": "suave"},
                    {"date": "2026-09-02", "is_rest": True, "sport": "rest", "discipline": "descanso",
                     "intensity_class": "rest"},
                ]}
        self.entry_id = save_reasoning(self.storage, {"prompt_calls": [{
            "index": 0, "node": "weekly_planner", "model": "gemini-3.5-flash",
            "prompt_chars": 100, "messages": [{"role": "user", "content": "el atleta hizo 21.2 km"}],
            "output_schema": {"name": "WeekIntentContent", "schema": WEEK_SCHEMA},
            "usage": {"input_tokens": 14952, "output_tokens": 18797, "total_tokens": 33749},
            "response": week,
        }]}, kind="week")

    def tearDown(self):
        self._tmp.cleanup()

    def test_it_scores_the_archived_answer_like_any_replay(self):
        record = original_record(self.storage, self.entry_id, "weekly_planner")
        self.assertEqual(record["summary"]["shape"], "week")
        self.assertEqual(record["summary"]["sessions"], 1)
        self.assertEqual(record["summary"]["minutes_by_sport"], {"swim": 45.0})

    def test_it_is_labelled_as_the_original_so_nobody_mistakes_it_for_a_replay(self):
        record = original_record(self.storage, self.entry_id, "weekly_planner")
        self.assertIn("original", record["replay_model"])

    def test_its_cost_is_what_the_run_really_paid(self):
        """gemini-3.5-flash at $1.50/$9.00 over the measured 14,952/18,797."""
        record = original_record(self.storage, self.entry_id, "weekly_planner")
        self.assertAlmostEqual(record["cost"]["cost_usd"], 0.1916, places=3)

    def test_a_run_with_no_captured_answer_has_no_baseline(self):
        entry = save_reasoning(self.storage, {"prompt_calls": [{
            "index": 0, "node": "weekly_planner", "model": "x", "messages": [{"role": "user", "content": "hi"}],
        }]}, kind="week", timestamp=datetime(2020, 1, 1))
        self.assertIsNone(original_record(self.storage, entry, "weekly_planner"))


if __name__ == "__main__":
    unittest.main(verbosity=2)
