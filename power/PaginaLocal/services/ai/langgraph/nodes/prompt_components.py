from typing import Literal

AgentType = Literal[
    "metrics_summarizer",
    "physiology_summarizer",
    "activity_summarizer",
    "metrics",
    "physiology",
    "activity",
    "synthesis",
    "season_planner",
    "weekly_planner",
]


def _workflow_context_body(agent_type: AgentType) -> str:
    # Summarizer agents
    if agent_type in ["metrics_summarizer", "physiology_summarizer", "activity_summarizer"]:
        domain = agent_type.replace("_summarizer", "")
        return f"""
## System Role
You are the **{agent_type.replace('_', ' ').title()}**.
- **Input**: Raw `garmin_data`
- **Output**: Structured `{domain}_summary`
- **Goal**: Condense raw data into a factual, structured summary for the {domain} expert. Do NOT interpret."""

    # Expert agents
    if agent_type in ["metrics", "physiology", "activity"]:
        return f"""
## System Role
You are the **{agent_type.title()} Expert**.
- **Input**: `{agent_type}_summary`
- **Output**: `{agent_type}_outputs` with 3 fields:
  1. `for_synthesis`: For the comprehensive report.
  2. `for_season_planner`: Strategic insights (the macrocycle, up to the goal race).
  3. `for_weekly_planner`: Tactical details (the next 7 days).
- **Goal**: Analyze patterns and provide specific insights for each consumer.
- **Context**: You are 1 of 3 parallel experts. Focus ONLY on your domain.
- **Proportion**: the weight of what you write is the weight of the evidence, in both directions. Room to
  build is as much a finding as a reason to hold back. A past niggle the athlete says is gone, or one they
  rate 1-2/10 and train through, is context — never the main risk and never a limit on the plan."""

    # Synthesis agent
    if agent_type == "synthesis":
        return """
## System Role
You are the **Synthesis Agent**.
- **Input**: `for_synthesis` fields from Metrics, Physiology, and Activity experts.
- **Output**: `synthesis_result` (Comprehensive Athlete Report).
- **Goal**: Integrate domain insights into a coherent story. Focus on historical patterns, not future planning."""

    # Planner agents
    if agent_type in ["season_planner", "weekly_planner"]:
        timeframe = "season strategy up to the goal race" if agent_type == "season_planner" else "next 7 days of training"
        return f"""
## System Role
You are the **{agent_type.replace('_', ' ').title()}**.
- **Input**: `for_{agent_type}` fields from Metrics, Physiology, and Activity experts.
- **Output**: `{agent_type.replace('_planner', '_plan')}` ({timeframe}).
- **Goal**: Translate expert insights into a concrete {timeframe}.
- **Context**: Use the expert signals as your primary constraints and guides."""

    return ""


def get_plotting_instructions(agent_name: str) -> str:
    return f"""
## Visualization Rules
- **Constraint**: Create plots ONLY for unique insights not visible in standard Garmin reports. Max 2 plots.
- **Reference**: You MUST reference each plot EXACTLY ONCE in your text using `[PLOT:{agent_name}_TIMESTAMP_ID]`.
- **Placement**: Place the reference where it best supports your analysis. Do not repeat it."""


def get_hitl_instructions(agent_name: str) -> str:
    return """
## Human Interaction
- **Questions**: If you need clarification, set `output` to a list of Question items.
- **Otherwise**: Set `output` to your node's normal output schema.
- **Criteria**: Only ask if data is ambiguous or user preference is required. Do not ask for obvious info.
- **Process**: If you ask questions, your execution pauses until the user answers."""


# Every node in the analysis branch used to run without a single word about
# language, so each one defaulted to English while only the weekly planner and
# the validator emitted Spanish -- which is why the athlete's stored season
# plan literally opens with "# Strategic Season Plan (16-Week Macrocycle)"
# while the sessions underneath it are in Spanish. This is that one word,
# stated once and injected everywhere.
_LANGUAGE_DIRECTIVE = """
## Idioma
- Escribe **todo** en español: encabezados, tablas, prosa, listas y recomendaciones.
- **No traduzcas** nombres de campos crudos de Garmin, identificadores, ni unidades
  (`hrvSummary`, `trainingStatus`, bpm, m/s, W, km). Si citas uno, va tal cual.
- **Pero en el texto que lee el atleta** (`purpose`, `adaptation`, `execution_cues`, `week_rationale`, el
  informe) no escribas identificadores de campo (`acwr_uncoupled`, `chronic_ewma`, `acute_ewma`) ni
  etiquetas de Garmin en mayúsculas (`AEROBIC_HIGH_SHORTAGE`, `PRODUCTIVE_3`): di en palabras lo que
  significan. Las unidades sí van.
- En ese mismo texto, **las fechas como las dice una persona** ("el sábado 24/10", "el 23/09"), nunca
  en formato ISO (2026-10-24), y **los decimales con coma** (0,90; 1,5 h), como en el resto de la app.
- **Nunca calcules qué día de la semana es una fecha.** Las fechas que te damos traen su forma escrita
  (`se_escribe`: "viernes 25/09"): cópiala tal cual. Si una fecha no la trae, escríbela sin día
  ("el 20/09"). Un "viernes 26/09" con el viernes en 25 es un error que el atleta ve.
- Los términos técnicos consagrados no se castellanizan (VO2max, FTP, ACWR, HRV, tempo, umbral, sweet spot,
  taper, brick, RPE): entre agentes se escriben así, tal cual.
- **Pero en las explicaciones que lee el atleta** (`week_rationale`, `purpose`, `adaptation`, el informe)
  **ninguna sigla, índice o escala va sola** -- ni ACWR, TSB, HRV, Hooper, «carga aguda» o «crónica», ni los
  nombres de Garmin como Training Readiness o Training Status: el atleta no sabe qué miden. Di en palabras qué
  mide y deja su número; el detalle es lo que le enseña que miraste sus datos, así que se traduce, no se
  quita. Por ejemplo, «ACWR 0,70 (carga 201 vs crónica 279)» es «tu carga de los últimos 7 días (201) está un
  30 % por debajo de la de tus últimas 4 semanas (279)»; «Hooper 12/28 vs tu media 6,5» es «llegas peor que de
  costumbre según tu check-in (12 sobre 28; tu media es 6,5 y más bajo es mejor)»; «HRV 60 ms en tu rango
  55-78» es «tu variabilidad cardiaca de anoche, 60 ms, está dentro de lo normal para ti»; «Training
  Readiness 87» es «tu reloj te da 87 sobre 100 de disposición para entrenar». VO2max, tempo, umbral, zonas
  (Z2) y RPE sí van tal cual: el atleta los ve en su reloj o en su plan.
- **Los nombres de ejercicios y drills que se usan en inglés se escriben en inglés.** Un atleta
  busca "dead bug" en internet y encuentra el movimiento; busca "bicho muerto" y no encuentra nada
  -- y quien lo lea entre serie y serie no sabrá qué hacer. Van tal cual, sin traducir y sin
  traducción entre paréntesis:
  dead bug, bird dog, plank, side plank, hollow hold, Pallof press, face pull, farmer's carry,
  suitcase carry, hip thrust, good morning, box jump, drop jump, pogo hops, step-up, split squat,
  goblet squat, front squat, deadlift, RDL, push-up, pull-up, chin-up, dips, wall slide, clamshell,
  monster walk, Copenhagen plank, sculling, catch-up, fingertip drag, fist drill, sighting,
  streamline, A-skip, B-skip, butt kicks, ankling, carioca, strides, bounding.
- Los movimientos que SÍ tienen nombre corriente en español se escriben en español y no en inglés:
  sentadilla, zancada, remo, dominada, flexión, peso muerto rumano, elevación de talones,
  puente de glúteo, plancha lateral, patada con tabla.
- No escribas la misma frase en dos idiomas ni pongas traducciones entre paréntesis.
"""


def get_language_directive() -> str:
    """The single language rule shared by every node that writes text a human reads."""
    return _LANGUAGE_DIRECTIVE


# Three domain experts look at the same athlete from three angles and WILL
# disagree -- "ejecuto bien la semana" (activity) against "la carga viene
# subiendo" (metrics) against "la recuperacion viene cayendo" (physiology).
# Nothing in any prompt said what to do about that, so whoever consumed the
# three payloads silently picked one, and picked differently on each run. The
# fix is not to rank the experts once and for all: it is to make the
# disagreement itself part of the output, so the reasoning is visible and the
# athlete can see it too.
_CONFLICT_POLICY = """
## Cuando los dominios se contradicen
Las tres miradas (carga, ejecución y respuesta fisiológica) se contradicen con frecuencia. Eso es
información, no un error a esconder.
- **Nombra la contradicción explícitamente.** Nunca elijas un lado en silencio.
- **Pondérala por la evidencia, no por el dominio**: gana la afirmación con el dato más reciente, con
  mayor cobertura (más días medidos, menos huecos) y más directamente medida (un número del reloj pesa
  más que una inferencia).
- **Di qué la resolvería**: qué observación de los próximos días confirmaría una u otra lectura.
- Si la evidencia empata, dilo. "No se puede distinguir todavía" es una conclusión legítima y honesta.
"""


def get_conflict_policy() -> str:
    """How to handle two domains asserting opposite things about the same athlete."""
    return _CONFLICT_POLICY


def get_workflow_context(agent_type: AgentType) -> str:
    """Each node's role in the pipeline, plus the language rule.

    The language directive rides along here rather than being added at eight
    separate call sites: every node that writes text a human reads already
    calls this, so one place is enough and none of them can silently forget it.
    Synthesis and the HTML formatter do NOT call this -- they add
    `get_language_directive()` themselves.
    """
    return _workflow_context_body(agent_type) + _LANGUAGE_DIRECTIVE
