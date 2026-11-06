# 03 — El cerebro (`PaginaLocal/`)

Guidance for any agent (Antigravity, Claude Code or other) working inside `PaginaLocal/`. It used to live in
`PaginaLocal/CLAUDE.md`, which now only imports this file. Mixed Spanish/English: it grew over many versions.

## What this is

**This file describes ONE OF FOUR pieces. Read `contexto/02_ARQUITECTURA_Y_REGLAS.md` first** — it has the
architecture, the three hops between the pieces, the product rules and the commands
to start everything. This file is the deep reference for what happens INSIDE
`PaginaLocal/`: the workout schema, units, the validator, the reasoning archive and
the model tiers.

An AI triathlon/endurance coach: it pulls an athlete's real data from Garmin Connect, runs a multi-agent
LangGraph pipeline (Claude/GPT/Gemini depending on tier) to analyze training and write a 4-week structured
plan, and uploads it back to the athlete's Garmin as real scheduled workouts.

**`web_app/` is NO LONGER what end users see**, and this section used to say it was.
It was the interface when this was a desktop app for one person, and the FastAPI
routes in `web_app/server.py` are still where the generation pipeline lives —
`AiService` imports them directly. But the athlete's app is `Frontend/` (Vue)
talking to `Backend/` (PHP), and **MySQL is the source of truth**, not the JSON
files this package reads and writes. Those are a throwaway mirror that
`AiService/bridge/storage_adapter.py` rebuilds before every call. Two exceptions are
real history and survive a wipe: `track_record.jsonl` and `reasoning/`.

The CLI (`cli/garmin_ai_coach_cli.py`) is a secondary, config-file-driven interface for scripted/batch
runs — treat its `cli/README.md` as approximate, it lags the web app's model mapping.

The project's throughline: a plan that merely *looks* like a coach wrote it (has warmup/repeats/cooldown)
is not the same as a plan that is *safe to run*. Most of the hard bugs found in this repo were exactly that
gap — steps with the right shape but wrong units, wrong zones, or wrong pace. When touching anything that
produces or uploads a workout, verify against real athlete numbers, not just schema validity.

## Commands

```bash
# Run the full test suite (stdlib unittest, no pytest, no test framework dependency)
python -m unittest discover -s tests -v

# Run a single test file / class / test
python -m unittest tests.test_workout_builder -v
python -m unittest tests.test_workout_builder.PaceTargets -v
python -m unittest tests.test_workout_builder.PaceTargets.test_pace_target_matches_the_reference_workout_exactly -v

# Start the web app STANDALONE (the legacy desktop path, port 8000). The real
# app does NOT go through here — see `contexto/02_ARQUITECTURA_Y_REGLAS.md` for the three servers.
python run_app.py
# Windows shortcuts for non-technical restart during dev:
"Iniciar Triatlon 2.0.bat"     # start
"Detener Triatlon 2.0.bat"     # kill whatever is listening on :8000

# Start a dev server with autoreload on a different port (doesn't touch :8000)
py -m uvicorn web_app.server:app --host 127.0.0.1 --port 8010 --reload
# (declared in .claude/launch.json as "web-dev")

# CLI (secondary interface)
python cli/garmin_ai_coach_cli.py --init-config my_config.yaml
python cli/garmin_ai_coach_cli.py --config my_config.yaml [--output-dir ./data]
```

Summarizer payloads go through `services/garmin/payload_pruning.py` before they reach a model: nulls, empty
containers, IEEE-754 float noise and zero-length laps are removed, and the JSON is dumped compact (measured
on a real season run: 852,795 → 205,237 chars, 76% less). **Nothing that carries a measurement is removed**
— not a field, not a lap, not a zero — because lap-level detail is the product's differentiator, and a
dropped lap is always announced with a `laps_omitted` marker. Do not "optimise" this by sending fewer
activities or dropping laps.

Static analysis EXISTS now, and this line used to say the opposite. `pyproject.toml` at the REPO ROOT
configures `ruff` and `vulture` — run them from the root, not from here, because this repo has three import
roots and only that config declares them:

```bash
python -m ruff check .
python -m vulture
```

`vulture` sin argumentos: las rutas viven en `pyproject.toml` y pasarle `.` las pisa (escanea `tests/` y
triplica el ruido). No hay `vulture_whitelist.py` — se documentó durante meses un archivo que nunca existió.

Deliberately NOT wired into the deploy CI: that workflow publishes to production, and a linter that breaks a
deploy over a long line is worse than no linter. Run it before pushing.

### Required environment

**Los TRES proveedores hacen falta, en los dos tiers.** `GEMINI_API_KEY`, `OPENAI_API_KEY` y
`ANTHROPIC_API_KEY` (ver `.env.example` para la lista anotada). Esta linea decia que Gemini bastaba para el
tier barato y que los otros dos eran del Pro; dejo de ser verdad en v8 y se siguio repitiendo. Lo que corre
hoy en los nodos que usan mas de un proveedor:

| | `standard` (lo que corre en produccion) | `pro` |
|---|---|---|
| resumidores x3 | `gpt-6-luna` | `gemini-3.1-flash-lite` |
| expertos x3 | `gpt-5-mid` | `claude-sonnet-5` |
| decision semanal | `gpt-5-mid` | `gpt-5-mid` |
| codificador | `claude-haiku-4.5` | `claude-haiku-4.5` |
| cuaderno del chat | `gemini-3.1-flash-lite` | `gpt-5-mid` |

(Columna `standard` desde el banco del 2026-09-23; la tabla completa está en `02`.)

Montar el proyecto con solo `GEMINI_API_KEY` creyendo que es "el tier barato" deja TODAS las generaciones
fallando. `OPENROUTER_API_KEY` is a documented last resort, not a normal setup path — see model_config.py
notes below.

## Architecture

### Request flow

`web_app/server.py` (FastAPI, ~1800 lines, all routes in one file) is the only entry point that matters for
the web app. It owns profile CRUD, Garmin import, plan generation, plan history, adherence, printable
reports, and server shutdown. There is no separate router/controller layer — routes are plain `@app.get/post`
functions directly in this file.

`POST /api/generate` kicks off a background task that:
1. Pulls Garmin data (`services/garmin/data_extractor.py`) and builds a *prescription* — the athlete's real
   HR zones (per-sport, read from the watch, not computed from a formula unless real zones are unavailable)
   and threshold pace, converted into per-intensity pace bands (`services/ai/prescription.py`).
2. Loads *adherence* — what the athlete actually did against their last plan (`services/ai/adherence.py`) —
   and the previous plan itself, so the next generation refines instead of reinventing from scratch.
3. Runs the LangGraph workflow (see below) with prescription + adherence injected into the weekly planner's
   prompt as compact, concrete numbers — never let the LLM guess a pace or zone it wasn't handed explicitly.
4. Validates the resulting plan against deterministic coaching rules (`plan_validator.py`), repairs it via a
   tier-2 LLM prompt if needed, and falls back to a safe deterministic archetype only as a last resort.
5. Builds Garmin `WorkoutDTO`s (`services/garmin/workout_builder.py`) and uploads them
   (`services/garmin/garmin_upload_service.py`), then re-reads them back from Garmin to verify what was
   actually stored matches intent (never assume an upload succeeded just because the HTTP call didn't throw).
6. Archives the previous generation into `data/storage/<profile>/history/<ISO-timestamp>/` before writing
   the new one, so nothing is silently destroyed (`archive_previous_generation` / `delete_current_generation`
   in `server.py`).

### LangGraph pipeline (`services/ai/langgraph/`)

Two workflows in `workflows/`:
- **`create_analysis_workflow`**: 3 summarizers (metrics/physiology/activity, large-context cheap models) →
  3 experts (same domains, judgment) → master_orchestrator (HITL question routing via `Command(goto=...)`,
  no static edges out of it) → synthesis → formatter → plot_resolution.
- **`create_integrated_analysis_and_planning_workflow`** (what the web app actually runs): the same
  analysis branch, running in parallel with a planning branch —
  `data_integration → season_planner → weekly_planner → plan_formatter` (sequential: the weekly planner
  reads the season plan's phase intent from state, so season planning must finish first). Both branches join
  at a `finalize` barrier node before `END`.

**Barrier node gotcha**: `finalize` uses `defer=True` and MUST return `{}`, never the full state. Every
list/dict field in `TrainingAnalysisState` (`state/training_analysis_state.py`) uses an accumulator reducer
(`lambda x, y: x + y` or `{**x, **y}`) — returning the state itself re-merges it into itself, silently
doubling costs/tokens/plots. This has already caused a real bug (2x reported API cost) once; don't reintroduce it.

Node roles are defined in `services/ai/ai_settings.py::AgentRole`; the model assigned to each role per tier
(Lite/Pro, exposed in the UI; Development/CostEffective are internal-only) is in
`AISettings.model_assignments` — read the comment block there before changing tier model assignments, it
documents *why* each role uses the model it does (cost-per-output-token vs. web-search need vs.
structured-data digestion strength), not just the list.

### Model routing (`services/ai/model_config.py`)

`ModelSelector.get_llm_by_role_and_name` resolves a role + model key to a concrete LangChain client. Key
things to know before touching this file:
- **Direct provider keys are strongly preferred over `OPENROUTER_API_KEY`.** The OpenRouter fallback exists
  for when a direct key is missing, costs more (OpenRouter's margin on top of the provider price), and
  strips provider-specific parameters (Anthropic's adaptive thinking, OpenAI's Responses API params). Using
  it is logged as a `WARNING`, not `INFO`, on purpose.
- **Never pass `thinking` explicitly to Claude Opus 5 / Sonnet 5.** They think adaptively by default when
  the parameter is *omitted*; passing it explicitly pushes `langchain-anthropic` onto a forced-tool-calling
  path for structured output that it warns can raise `OutputParserException`. Four of the five Claude roles
  in this app produce structured output. See the long comment at the top of `MODEL_CONFIGS` before changing
  this — it's the result of a real, verified-against-the-live-API investigation, not a guess.
- `requirements.txt` pins the LLM stack to versions verified against the live Anthropic/OpenAI APIs
  (`anthropic==0.125.0`, `langchain-anthropic==1.6.1`, etc.) with a comment explaining why: a stale pin once
  made every single Claude call fail with a `TypeError`, silently killing the entire Pro tier. If you bump
  these, re-verify all four provider×role routes end-to-end against a real key before assuming it works —
  unit tests mock the LLM boundary and won't catch this class of bug.
- `MODEL_PRICING_USD_PER_MILLION` is keyed by the `CONFIGURATIONS` key (e.g. `"claude-opus"`), not the raw
  provider model string, because the same logical model reports a different string depending on whether it
  went through the direct API or the OpenRouter fallback (`_find_config_key_for_model_name` does the reverse
  lookup). **The two tables must hold exactly the same keys** — `tests/test_model_tiers.py` fails if they
  diverge, in either direction — because a model with no pricing entry costs $0 in the report and real money
  on the bill. The reverse lookup also tolerates a provider's **dated snapshot**: asking OpenAI for `gpt-5.2`
  gets `gpt-5.2-2025-12-11` back in usage metadata, and before v6 that matched nothing, so every
  OpenAI-routed call in the app reported as free.
- **Never ask a model for JSON in prose.** Use `with_structured_output` and let the provider enforce the
  shape. The health-intake agent asked for a JSON envelope in its system prompt and hand-stripped code
  fences from the reply; against a thinking-enabled model that broke in the athlete's face with Gemini's
  `MALFORMED_RESPONSE` (a finish reason the `google-genai` SDK does not even recognise). The likeliest way
  to break it is exactly what that agent does: embedding a multi-thousand-character markdown document,
  full of newlines and quotes, inside a JSON string field.
- **Tier assignment rule (v6 Track 5): pay for a model where there is JUDGEMENT, never where there is only
  COMPRESSION.** Measured, not assumed: the activity summarizer takes 62,889 input tokens and emits 1,585, so
  running the three summarizers on Claude Sonnet cost $0.28 against $0.02 on gemini-3.1-flash-lite. Experts
  keep the frontier model (that is where reading becomes judgement, and they are small). `AgentRole.FORMATTER`
  is deliberately absent from every tier — both formatter nodes are deterministic since v5 Track 9 —
  and `get_model_for_role` raises a named error rather than a `KeyError` if a node ever asks for it.
  The `WORKOUT` role is 86% of a week's cost and output-dominated, so decide it with
  `scripts/replay_node.py` against a real archived prompt, never from the price table alone: GPT-5 bills
  reasoning tokens at output price and can spend several times Claude's output on the same task.

### Workout schema and the Garmin builder

`services/ai/langgraph/schemas/plan_outputs.py::WorkoutStep` is a recursive schema: a step with `substeps`
IS a repeat block (`repeat_count` rounds of everything nested inside).

**The model does not write that shape, and must not be asked to.** v6 measured the same session fail three
times against one archived prompt as each escape was closed: no `steps` at all (the field had a default, so
it was not `required`), then `step_type="repeat"` with the reps written in prose instead of `substeps` —
repeated even with the validator's own error handed back — then the same flat step with no `duration_type`.
A coach writes a repetition as ONE line; the nested tree is a fact about Garmin's file format, not about
coaching, and each round cost a tier-2 repair that rebuilt the whole week ($0.11 of a $0.33 week).
So the formatter writes `FormattedWorkoutStep`: **flat, no `substeps`, no `"repeat"` type, `duration_type`
and `steps` required**, carrying `repeat_count` + `recovery_seconds` instead. `expand_formatted_steps()`
builds the tree deterministically. When you extend this schema, keep the rule: **the model expresses
coaching intent, code does the encoding — and any constraint a Python validator enforces must also be
expressible in the JSON Schema, or the model cannot obey it.** A prompt and a schema that disagree are
resolved by the model in favour of the schema, every time. This is the only way to express
"4×(3' hard + 2' easy)" as one atomic unit with recovery *inside* the repeat — the earlier, flatter schema
could only express work and recovery as separate top-level steps, which Garmin then ran as 4 hard reps back
to back followed by 4 easy ones. Any change to this schema should be checked against
`tests/test_repeat_blocks.py` and `tests/test_workout_builder.py::ReferenceWorkoutEndToEnd`, which rebuild a
real reference workout (created via Claude Desktop + the Garmin MCP) and diff the resulting DTO.

Unit discipline matters here more than almost anywhere else in the codebase:
- Pace targets are always **seconds per kilometre** in the schema (`target_low`/`target_high`), converted to
  m/s only inside `workout_builder.py`, and uploaded to Garmin as `pace.zone` (not `speed.zone` — Garmin
  accepts both but displays them differently to the athlete).
- HR targets are sent as a **named zone number**, not a custom bpm range, so Garmin applies the athlete's
  own zones from their watch instead of a range the app computed (which was once ~4-14 bpm off from the
  athlete's real Garmin-configured zones).
- `_check_shape_and_units` in `plan_outputs.py` enforces plausible-value bounds per target type specifically
  to catch a unit mix-up (e.g. minutes instead of seconds) before it reaches Garmin as an unrunnable workout.

### Deterministic safety net (`services/ai/langgraph/validation/plan_validator.py`)

Rules the LLM output must satisfy regardless of model quality: quality sessions (tempo/threshold/VO2max/
intervals) must carry a pace target; repeat blocks with work must include recovery; warmup/cooldown/Z2/long-
run days must use HR zone, not pace; pace ranges must be physiologically consistent with the athlete's
threshold. Failures trigger a tier-2 repair prompt; if that still fails, a deterministic archetype is
expanded instead of silently downgrading the session to a generic continuous jog. When adding a new rule,
check word-order sensitivity in the classification logic — "ritmo de maratón" (marathon pace, an easy pace)
was once misclassified as "threshold" because "ritmo" (pace) matched before "maratón" did, which would have
prescribed a dangerously fast pace band. Order these checks from most to least specific.

### Plan history, adherence, and prescription

- **History**: every generation is archived to `data/storage/<profile>/history/<ISO-timestamp>/` before the
  active plan is overwritten (`archive_previous_generation` in `server.py`). A plan whose 28-day window ended
  more than ~6 weeks ago is presented as history, not as "continue from here" — otherwise a long-idle athlete
  would have their next plan built as a direct continuation of a progression they may not have trained a day of.
- **Adherence** (`services/ai/adherence.py`): matches executed Garmin activities to prescribed sessions by
  date + sport. A same-day session is *pending*, not *missed*. "Completed" and "modified" both count as
  trained; duration tolerance is 25%; activity done outside the plan is reported separately, not folded in.
- **Prescription** (`services/ai/prescription.py`): the compact, concrete-numbers block (real HR zones per
  sport, threshold pace, derived pace bands per intensity class) injected into the weekly planner's prompt.
  Without this the planner only sees expert prose and literally cannot prescribe a number.
- **Load state** (`services/ai/load_state.py`): the load/readiness half of the same idea — ACWR, training
  readiness, Garmin's Load Focus bands, and the athlete's own recent effort history per sport. Two rules
  govern it. First, **it reports BOTH ACWRs**: Garmin's exponentially-weighted `dailyAcuteChronicWorkloadRatio`
  and this app's rolling-average `acwr_7d28d` (`services/garmin/utils/training_metrics.py`), each with its
  method and its date. They disagree — 1.10 vs a race-day peak of 1.47 on the same athlete — and both are
  correct under their own definition; picking one silently is what made a real plan quote a number the
  athlete could not find on their watch. Second, **it states no threshold of its own**: every status word is
  one Garmin assigned or a comparison against a range Garmin published. ACWR's value as an injury predictor
  is contested and method-dependent, so it describes load and never gates a decision.
  Garmin ships the load balance and the ACWR status inside the `get_training_status` payload the extractor
  already fetches — `garminconnect` has no `get_training_load_balance` method. `get_training_readiness`
  returns *several* readings per day (it recomputes after each session and on waking); both the latest and
  the morning one are kept, and `recoveryTime` is in MINUTES.
- **Declared complaints** (`services/ai/niggle_constraints.py`): the check-in niggle and the health record's
  active injuries, collected into one prompt block. It quotes the athlete's own words verbatim (laterality
  included) and requires the affected days' `adaptation` to name **that** structure — a real plan answered a
  declared groin niggle with a knee contingency, on the bike session, which is exactly the session that
  loads hip flexors. Ships a gesture→structure map covering run/bike/swim/strength/plyo/brick, framed as
  orientation and explicitly not a diagnosis or a closed list, plus what *unloads* each structure so the
  coach can substitute instead of cancel. `unaddressed()` is an after-the-fact **lint printed to the log**,
  never a gate; it matches on a body-part lexicon rather than raw words, because "ingle izquierda" and
  "rodilla izquierda" share a word and the first version scored the real failure as addressed.
- **Goal realism** (`services/ai/goal_realism.py`): frames a declared race target against Garmin's own
  predicted time for the season planner. It is **framing, not a gate, and states no verdict the agent has to
  accept** — it reports the required improvement, the average pace the target demands and which of the
  athlete's own bands that pace sits between, a coarse reference improvement rate labelled as coarse, and
  **how much the data is worth believing** (activity count and span, whether the threshold pace was measured
  or derived, whether the zones came from the watch, what the athlete says about their own level). Deciding
  what all that means is the agent's job: an athlete with three weeks of watch data can be far better than
  their prediction says. Do not add a rule here that forces a conclusion.
  The distance is read from the athlete's `distance` field, else inferred from the race type/name — **most
  specific pattern first**, because "media maratón" contains "maratón" (the same ordering trap
  `plan_validator.py` documents). An inferred distance is labelled as inferred in the block. Note this
  reverses a v5 decision not to infer at all: refusing to infer left every competition on the real profile
  unevaluated, since they predate the `distance` dropdown and carried `race_type: "Olympic"`.

`services/garmin/data_extractor.py` is the sole boundary to the `garminconnect` library — HR zones are
**per-sport** (a recent swim's zones are entirely different bpm ranges from a run's; resolving "most recent
activity with HR" without filtering by sport already caused zones from the wrong discipline to be applied
once) and threshold pace conversion has previously been off by ~20% due to a unit mismatch. Any change here
should be sanity-checked against `get_race_predictions` (5K/10K/HM/M pace) as an independent cross-check —
a threshold pace faster than 5K pace is definitionally wrong.

### Reasoning archive, prompt capture, and the replay bench

Every generation writes its full reasoning trail to `data/storage/<profile>/reasoning/<ISO-timestamp>/`
(`services/ai/reasoning_archive.py`) — summarizer views, the nine expert payloads, the synthesis markdown,
and `week_intents.json` (the decision layer's per-day coaching call before any Garmin encoding). Alongside
it, `prompts/` holds **the exact messages every LLM call received**, captured by
`services/ai/prompt_capture.py`: a LangChain callback attached to the run config in
`planning_workflow.py`, which LangGraph propagates to every node — so it also catches the calls no one would
remember to instrument, like the validator's tier-2 repairs. The captured output JSON Schema is normalised
across providers (Google sends `response_json_schema`, OpenAI `response_format`, Anthropic a forced tool's
`input_schema`), which is what makes a prompt replayable on a *different* provider.

That enables `scripts/replay_node.py`: re-run one archived node against another model with every input
frozen, and compare cost, latency and the resulting week. Use it before changing a tier's model assignment —
comparing by running the whole pipeline twice costs a full generation each time and changes the inputs, so
any difference observed is unattributable. The comparison in `services/ai/replay.py` aggregates by whatever
sports and disciplines the week contains and reports minutes, never distance: this app serves runners,
swimmers, cyclists and triathletes, and a bench scored in kilometres would silently rank models by how much
running they write.

`services/ai/number_audit.py` checks that numbers quoted in a `week_rationale` actually appear in what the
model was given (or in its own output). It is a **lint, not a validator** — it never blocks or rewrites a
generation. Integers below 10 are ignored (rep counts, zones); decimals, clock times and percentages are
always checked.

### Frontend (`web_app/static/`)

Vanilla JS + hash router (`app.js`, `initRouter`/`navigate`/`renderRoute`, routes like `#/`, `#/perfil`,
`#/plan`, `#/historial`), no build step, no framework. Hash routing is required because
`StaticFiles(html=True)` is mounted at `/` in `server.py` and would swallow real path-based routes.
`index.html` + `app.js` are both large single files (~600 / ~1600 lines) — search for the relevant
`function` or `id=` rather than assuming a component boundary exists. `styles.css` is the live stylesheet;
an old `style.css` (963 lines) is dead and unreferenced — don't edit it by mistake.

Printable views (`GET /api/reports/print`) render server-side via Jinja2 templates in
`services/ai/rendering/templates/` and rely on `@media print` / `window.print()` — no PDF library, this is
intentional (see plan history in git log, "Fase 6"). The sheet carries the REASONING, not just the calendar:
the week's rationale, each session's purpose, the step targets read from the structured fields (never from
the model's prose), and the full written instructions for any session Garmin cannot hold — if those are not
printed, the athlete never sees them and v5 Track 5's promise breaks. **`step_target` resolves HR zones by
the day's sport**: a swim Z2 and a run Z2 are 28 bpm apart on a real athlete, and the first draft printed
swim zones on a running session.

## Testing philosophy

All 1,597 tests run against pure functions/deterministic logic — no live LLM calls, no live Garmin calls in
the suite. Tests exist specifically where a bug would translate into a workout that could injure the
athlete or waste a paid generation: pace/zone unit conversion, repeat-block expansion, every validator rule,
and the archetype safety net. When a bug is found via a real Garmin/API round-trip (as most of the
significant ones in this project have been), write the regression test as a docstring-annotated case citing
the real numbers involved (see `tests/test_workout_builder.py` for the pattern: each test's docstring states
the exact real-world value that was wrong) — this codebase treats "verified against the live account/API"
as meaningfully stronger evidence than "passes existing unit tests," and preserves that provenance in test
docstrings rather than a changelog.
