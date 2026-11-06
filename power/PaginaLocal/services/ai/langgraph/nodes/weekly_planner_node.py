import asyncio
import json
import logging
from datetime import date, datetime

from langchain_core.callbacks.usage import get_usage_metadata_callback
from langchain_core.exceptions import OutputParserException
from pydantic import ValidationError

from langgraph.config import get_stream_writer
from services.ai import carga_segura as cs
from services.ai.ai_settings import AgentRole
from services.ai.exercise_catalog import render_catalogo_para_prompt
from services.ai.intensity_distribution import render_distribucion, revisar_reparto_de_la_semana
from services.ai.langgraph.schemas.plan_outputs import (
    FormattedWeekBlock,
    TrainingDecisionNoHitl,
    TrainingDecisionOutputs,
    WeekBlock,
    WeekIntentContent,
    WeeklyPlannerOutputs,
    WeeklyPlanPayload,
    WorkoutDay,
    is_garmin_exportable,
)
from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState
from services.ai.langgraph.utils.message_helper import normalize_langchain_messages
from services.ai.langgraph.utils.output_helper import extract_expert_output
from services.ai.langgraph.validation.plan_validator import (
    _HOLGURA_DURACION_MIN,
    _TOLERANCIA_DURACION,
    carrera_hasta_pulsar_vuelta,
    intenciones_fuera_de_la_tabla,
    totales_que_no_suman,
    validate_and_repair_weekly_plan,
)
from services.ai.model_config import ModelSelector
from services.ai.number_audit import con_fecha_escrita
from services.ai.perfil_de_fuerza import leer_perfil_de_fuerza, leer_rutina_de_fuerza
from services.ai.prescription import render_zones_table
from services.ai.season_constraints import render_constraints_block
from services.ai.utils.plan_storage import FilePlanStorage
from services.ai.utils.retry_handler import AI_ANALYSIS_CONFIG, retry_with_backoff
from services.ai.weekly_load import carga_de_la_semana
from services.garmin.garmin_vocabulary import render_exercise_catalogue

from . import paso_de_fuerza as pf
from .node_base import (
    configure_node_tools,
    create_cost_entry,
    execute_node_with_error_handling,
    log_node_completion,
)
from .prompt_components import get_conflict_policy, get_hitl_instructions, get_workflow_context
from .tool_calling_helper import handle_tool_calling_in_node

logger = logging.getLogger(__name__)

# Plan Maestro v4 (audit follow-up): generation used to be ONE LLM call doing
# coaching reasoning AND schema/unit encoding AND JSON serialization at once
# -- verified against real runs to be the single biggest driver of cost and
# failed validations (a real Lite-tier week generation spent 95% of its cost,
# and 2 full repair rounds, on a single call that was simultaneously deciding
# WHAT to train and HOW to encode it). Split into two narrower calls instead:
# a Training Decision (coaching judgment, no schema) followed by a Workout
# Formatter (schema encoding, no coaching judgment) — see weekly_planner_node
# below. Each call has to do less at once, which is exactly what "lost in
# the middle" context degradation predicts helps.
TRAINING_DECISION_SYSTEM_PROMPT = """## Goal
Decide what this athlete should train each day this week, and why -- not how to encode it.
## Principles
- Adaptation: Progressive overload with adequate recovery.
- Specificity: Training must match the demands of the event.
- Individualization: Adapt to the athlete's current state and history.
- Evidence over labels: the athlete's ACTUAL recent training (what they have really been doing — session
  types, intensities, volumes, as analyzed by the Activity/Metrics experts) is the primary signal for what
  they're ready for. A self-reported experience label is secondary context, never an excuse to ignore what
  the data already shows the athlete can do. If the data shows the athlete is already running intervals,
  tempo, or threshold work, continue and build on that — do not reset them to a generic beginner progression.
## What you are NOT doing here
You are not writing Garmin-format steps, choosing target_type/duration_type, or picking exact JSON units —
a separate formatting pass turns your decision into that schema afterward. Write `session_sketch` the way a
coach would explain a session out loud to the athlete: plain language, with real numbers where you have them
(pace ranges from the Prescription Card, durations, rep counts), but no field names or JSON syntax."""

# The three experts routinely hand this node contradictory readings of the same
# week -- "ejecuto todo lo prescrito" next to "la carga viene subiendo" next to
# "la recuperacion viene cayendo". get_conflict_policy() says how to WEIGH a
# contradiction; this says what a coach then DOES about it, which is a
# different question and belongs here, at the point where a training decision
# is actually made.
#
# The split below (recovery governs intensity, consistency governs volume) is a
# widely used coaching default, not a law, and it is written as a default the
# coach may override with a stated reason -- an athlete deliberately training
# through a heavy block is a real and correct scenario. It is deliberately NOT
# a deterministic rule in the validator: a rule that cancelled quality work on
# a low-recovery reading would be exactly the kind of guardrail that overrides
# a coach's judgement about a specific athlete.
CONFLICT_CONSEQUENCE_FOR_TRAINING = """
## Qué hacer con un conflicto al decidir la semana
Ponderar el conflicto no basta: hay que decidir. Reglas por defecto, no leyes —
puedes apartarte de ellas si dices por qué en `week_rationale`:
- **La señal de recuperación gobierna la INTENSIDAD**; la señal de ejecución/consistencia gobierna el
  **VOLUMEN**. Un atleta que cumplió todo pero cuya recuperación viene cayendo puede sostener sus horas y
  bajar el trabajo duro; no al revés.
- **No resuelvas el empate con descanso.** Acortar, bajar intensidad o cambiar de modalidad conserva el
  hábito y el estímulo aeróbico; cancelar no conserva nada.
- **La contradicción se escribe en `week_rationale`**, con los dos números que la formaron y la decisión
  que tomaste. El atleta tiene derecho a ver por qué su semana se ve así.
- **Nunca escribas que las fuentes coinciden cuando no coinciden.** Inventar consenso es peor que elegir un
  lado en silencio: el atleta pierde la única señal de que había algo que decidir.
  Ejemplo de la forma, no de este atleta: si los sensores dicen recuperado y el check-in dice fatiga alta,
  lo correcto es poner los dos números encima de la mesa y decir cuál pesó más y por qué — no "todo indica
  que necesitas descargar".
"""
# Hasta la v1.2 el ejemplo de arriba era un "Caso real de este atleta" con una
# "molestia 2/10 en la ingle", y se le mandaba a TODOS los atletas en cada
# semana. A un atleta sano le decia que tenia una ingle tocada, y a todos les
# ensenaba que "el tejido todavia no" es la lectura por defecto de un
# conflicto. Un ejemplo con un diagnostico dentro es un sesgo con forma de
# ejemplo: ver "Lesiones: declaradas, no supuestas" en ../../../../../CLAUDE.md.

# Chunked ONE WEEK AT A TIME (not all 4 weeks in a single call) since before
# this split existed — a single call asked to produce full step-by-step
# detail for ~7 sessions x 4 weeks was observed to silently return
# WorkoutDay.steps=[] for 100% of days in real testing. That's a separate
# axis from the decision/format split above: this controls how much of the
# CALENDAR one call covers, the split above controls how many DIFFERENT
# KINDS of work happen within one call.
# Los drills de natación (el responsable, 2026-10-21: «solo me han aparecido finger tip drag, sculling y
# catch-up»). Medido en las 25 decisiones archivadas de los atletas 2 y 3 (23 días con natación): catch-up
# 15, arrastre de dedos 15, sculling 9, puños 7, palas 3, flecha 1, patada con tabla 1; ninguna patada de
# costado, 6-1-6, un brazo, respiración bilateral, orientación ni crol con la cabeza fuera. No lo limitábamos
# nosotros: el prompt no daba ninguna lista y el modelo volvía a su repertorio de siempre, con la semana
# anterior como ancla. Esto le da el MENÚ por objetivo técnico, sin cuota: elige por lo que necesita la
# brazada de este atleta. Sólo a quien nada (`_nada`).
GUIA_DE_DRILLS = """
### Técnica de natación: el drill se elige por lo que necesita la brazada

Un drill aísla UNA parte del gesto para corregirla; sólo sirve si después se nada completo pensando en esa
misma parte. Por eso, cada drill va seguido de nado completo con la misma intención («50 drill + 50 crol
pensando en el agarre»). La evidencia de que un drill concreto mejora el rendimiento viene sobre todo de la
práctica de entrenadores, no de ensayos; el aprendizaje motor sí respalda practicar una parte y volver al
gesto entero enseguida, y variar la práctica.

Menú por objetivo (orientativo, ni cerrado ni una cuota):
- **Posición del cuerpo y patada**: patada con tabla; patada de costado (un brazo extendido, mirando al fondo);
  patada en flecha boca arriba; patada vertical en lo hondo; patada con aletas para quien hunde las piernas.
- **Respiración**: respiración bilateral (cada 3 o 5 brazadas); soltar el aire bajo el agua sin pausa;
  respiración de costado con el brazo extendido, la cabeza apoyada en él; tubo frontal para nadar sin
  pensar en respirar. Nada de apneas largas ni series hipóxicas al límite: pueden causar un desmayo en el agua.
- **Rotación y alineación**: 6-1-6 (seis patadas de costado, una brazada, seis del otro); un brazo con el otro
  pegado al cuerpo; nado con banda en los tobillos (sólo quien ya tiene buena posición).
- **Agarre y tracción**: sculling delante, a la altura del pecho y atrás; nado con puños; palas; pull buoy;
  catch-up.
- **Recobro y entrada**: arrastre de dedos; cremallera (el pulgar sube rozando el costado); catch-up.
- **Aguas abiertas** (si compite en ellas): orientación (levantar los ojos cada 6-10 brazadas sin parar),
  crol con la cabeza fuera, salidas y virajes alrededor de una boya.

Elige uno o dos objetivos por sesión, los que este atleta necesita según lo que sabes de él (su cuaderno, su
nivel, sus tiempos, su carrera: aguas abiertas pide orientación). **En «Plan anterior» están los drills de su
semana pasada: no los copies.** Salvo que el atleta los pida o tengas un motivo que digas en `purpose`, esta
semana trabaja OTRO objetivo del menú; la semana anterior es un ancla para el volumen, no una plantilla para la
técnica. Escribe el drill con su nombre en español y la distancia de cada repetición.
"""


TRAIL_GUIDANCE = """
### Montaña (trail), cuando el atleta corre o compite en montaña

Un plan de trail no es un plan de asfalto corrido en el monte. Pero tampoco es un plan que dé por hecho que
el atleta tiene una montaña a mano todos los días: casi nadie la tiene. Lo normal es tener monte el domingo
por la mañana y, entre semana, escaleras, la cuesta de un barrio, un parque con desnivel o la inclinación
de una cinta. Un entrenador de verdad trabaja con eso.

**Cuánta montaña lleva ESTA semana lo decide lo que viene, no la etiqueta del atleta.** Mucha gente tiene
un trail en enero y una 5K plana en marzo, y cambia de idea por el camino. Mira la fase del macrociclo y la
competición más próxima:
- cerca de una carrera de montaña, el terreno es la especificidad y manda;
- cerca de una carrera plana, la especificidad es el ritmo en llano — la montaña baja a mantenimiento (una
  sesión de cuestas y la fuerza excéntrica bastan para no perder lo ganado);
- lejos de las dos, en base, decide por lo que le gusta y le conviene a este atleta. Si el cuaderno dice que
  disfruta el sendero, dáselo aunque no haya un trail cerca: la adherencia también es un criterio de
  entrenador.
Nada de esto se te impone: es el marco para que dosifiques, y puedes apartarte de él si sus datos lo piden.

**Dónde mirar si hoy hay monte.** La tabla de disponibilidad lo dice: las franjas que incluyen `trail` entre
sus deportes son aquellas en las que este atleta tiene terreno de verdad. Prefiere poner ahí las sesiones de
`discipline="trail"`. En el resto de franjas puedes seguir entrenando la montaña — simplemente cambia el
medio. Y si el cuaderno del entrenador dice algo sobre su acceso al terreno ("los domingos alternos puede ir
al sendero"), eso manda sobre cualquier suposición.

**La modalidad la decide el TERRENO de esa sesión, no la carrera para la que entrena.**

- En pendiente o en terreno técnico, **el ritmo no es la unidad**: en una subida del 15% el min/km de
  alguien en forma y el de alguien reventado son el mismo número, y en una bajada con piedras lo pone el
  terreno. Ahí se prescribe por **esfuerzo (zona de FC o sensación), tiempo de subida y desnivel (D+)**.
- En llano —una pista, la calle— **el ritmo sí es la unidad**, y un corredor de montaña hace
  sesiones en llano con toda normalidad: umbral, VO2max, rodajes. No se las quites por ser trail runner. Un
  umbral en llano con `intensity_class="tempo_threshold"` y su banda de ritmo es correcto y deseable. En una
  cinta el ritmo lo pone la máquina: escribe su velocidad en km/h en el texto; el reloj no mide bien el ritmo
  bajo techo y su objetivo va por zona de FC.

Las sesiones que un plan de trail combina a lo largo de la semana. Ninguna es obligatoria y ninguna es una
lista cerrada: son las herramientas, y eliges tú:

- **Tirada larga por montaña** (`intensity_class="long_run"`, `discipline="trail"`). El núcleo, si hay
  acceso. Eficiencia, acostumbrar las piernas al castigo de las bajadas, y probar nutrición e hidratación
  tal como se llevarán en carrera. Por tiempo y D+, nunca por ritmo. Di en `execution_cues` qué comer y
  beber y cada cuánto: en una tirada larga de montaña eso ES parte de la sesión. Sin acceso al monte, una
  tirada larga en llano sigue construyendo el motor — dilo así y no finjas que es lo mismo.
- **Series en cuesta** (`intensity_class="hill_repeats"`). Por TIEMPO DE SUBIDA y esfuerzo — "5x3' en cuesta
  pronunciada, bajando trotando suave para recuperar" — no por ritmo ni por distancia. **Sirve cualquier
  cuesta**: un repecho urbano, un tramo de escaleras, o una cinta al 8-10%. Si usas la cinta, di la
  inclinación. Es la sesión de trail más fácil de conseguir sin montaña, y por eso es la que sostiene un
  plan entre semana.
- **Técnica de bajada** (`intensity_class="downhill_technique"`). Necesita terreno de verdad: prescríbela en
  una franja con `trail`. Lo que se entrena es la pisada, la vista adelantada y la tolerancia excéntrica del
  cuádriceps, que es lo que revienta a la gente a mitad de carrera. Es una sesión de HABILIDAD: describe qué
  practicar en `execution_cues` (mirar tres o cuatro pasos por delante, pasos cortos y rápidos, brazos
  abiertos, no frenar con el talón) y no le pongas ritmo objetivo.
- **Caminar-correr en pendiente fuerte** (`intensity_class="hike_run"`). Caminar rápido en un 20% es más
  eficiente que trotarlo, y saber cuándo cambiar es una destreza que se entrena, no un fracaso. Vale también
  en escaleras o en cinta muy inclinada. Prescríbela cuando la carrera tenga rampas largas.
- **Fuerza excéntrica y pliometría** (`discipline="fuerza"` o `"pliometria"`). Crucial en trail y disponible
  siempre, sin necesitar terreno: las bajadas son trabajo excéntrico y sin prepararlas las piernas se
  destruyen. Sentadilla búlgara, zancadas, bajadas controladas, saltos. Va en `strength_block`.

**Progresión del desnivel, no sólo del volumen.** El D+ semanal sube como suben las horas: gradualmente y
con descargas. Un atleta que nunca ha hecho 1.000 m de D+ en una semana no debe hacer 2.500 la siguiente
aunque las horas cuadren — el daño muscular de las bajadas no aparece en las horas.

Nada de esto es una lista cerrada. Si los datos de este atleta justifican otra cosa, prescríbela: estas
clases existen para decidir qué objetivo lleva cada paso, no para limitar lo que un entrenador puede mandar.
"""


TRAINING_DECISION_USER_PROMPT = """## Task
Decide the training for week {week_number} of {total_weeks} ({dias_del_bloque}) as a `WeekIntentContent` — one
`DayIntent` per date in "This Week's Dates". The JSON schema is enforced for you; think about the content.

## How to think about this week
- **Build the week from what this athlete has actually done and how their body responded.** Their real
  sessions and the structure of their hard ones, their habits, and how their recovery reacted after their
  most demanding sessions (see "Cómo respondió a sus sesiones más exigentes") are the primary evidence of
  what they can do safely and of what moves them forward. Every athlete is different: read THIS one first —
  what they tolerate, how long they take to absorb a hard day, what they have been missing, what they have
  been doing too much of — and then decide.
- **Recovery governs intensity.** Read their recovery numbers and the "Check-in del atleta" against THEIR
  OWN baseline — never against a population threshold, and never on one night alone.
- **Garmin's own readings are a second opinion, not the answer.** Training Status, Load Focus, Training
  Effect and the ACWR status are computed from HR zones and a max HR that are often set wrong; a "shortage"
  there can be a zone error rather than something the athlete lacks. Check them against the history and the
  response; when they disagree, the history wins and you say so in `week_rationale`.
- **The season plan is a guide, not a script.** Honor the phase's intent and continue the athlete's current
  training flow; do not restart a phase or force a session their recent weeks don't support.
- **No number below is a quota.** Load and volume references say where the athlete is now, not where the
  week has to land. Hours only have to fit the athlete's availability.
- **No session type is a quota either.** A block does not need a key session because it is a block. If the
  days available, the recovery numbers or how close a race is do not support intensity, an entirely
  aerobic block is a correct answer — say why in `week_rationale`. Quality that is squeezed in because the
  week "should have one" is the fastest way to arrive at a race flat.
- Space the hard days: no back-to-back high-intensity or heavy strength sessions, also across the boundary
  with last week (see "Previous weeks" if this isn't their first).

## What the athlete decided, and is respected as given
- **Availability**: the day-by-day table in `planning_context` is exact — no training on a day marked
  unavailable, no sport that isn't allowed that day. A sport the athlete marked as requested (fijo) on a day
  appears that day — except in a race's taper week, where the table's rules say FIJO counts as POSIBLE.
  A race's own date is available for that race even when the table marks that weekday as rest: the table is
  their training routine, not their race calendar.
- **Races**: if a date in "This Week's Dates" is a competition's `date`, that day's `intensity_class` is
  `"race_day"`, named after the race, with a real warmup, race plan and cooldown. If a competition falls in
  this week or right after it, this is a taper: the WEEK's total load comes down — not only the day before —
  and what you remove from one day is not relocated to another. Keep some short, sharp intensity. The other
  side of a race counts too: the days right after one (a race that ended just before this week, or earlier in
  it) are recovery, not the start of a build — the load block says how many.
- **Deload**: if "Real Mesocycle Position" says this is a deload week, the week's load really comes down.
- **What they ask of their training** — how many sessions of a sport, a minimum distance or duration per
  session, a long session on a given day: it is a request, like the availability table. Never shrink it to
  fit a number of hours, and never pay for it with the other sports: their key sessions and their
  progression stay as they would be without the request. The room comes from the week growing — the load
  block says how far that is normal. Their recent data showing less of it is not a reason to shrink it; real
  evidence is (a taper, the days after a race, a day they cannot train, a declared niggle, recovery
  persistently below their own baseline, a week that would be extreme for their own history — about twice
  their usual endurance hours or load, or a run over twice their longest of the last month): then follow its
  direction progressively and say in `week_rationale` which request you did not follow and the evidence.
{peticiones_del_atleta}
## What the formatting pass needs from you
A separate pass turns your decision into Garmin steps, so each field has to carry the intent:
- Every non-rest day has a real `session_sketch`: the main work, roughly how long and how hard, and for
  quality sessions real pace numbers from the Prescription Card. Rest days: `is_rest=true`, `sport="rest"`,
  `intensity_class="rest"`, empty sketch.
- **Write each set the way it will be run**: how many repetitions, ONE duration or distance per repetition
  («4x20″ progresivos», not «15-20″»), and the recovery with its unit («con 200 m de trote», «con 60″
  caminando»). Ranges are for paces and effort, not for durations: the formatting pass copies your numbers
  onto the watch and cannot choose them for you.
- **Write the blocks in order, each with its own minutes or metres, adding up to the session**: never
  «completa el resto en Z2», «el tiempo restante» or a set inside a longer block («120' en Z2 y dentro
  4x5'…») — write «20' Z2, 4x(5' cadencia baja, 5' normal), 60' Z2, 10' Z1». The formatting pass does not
  subtract.
- `intensity_class` is what the session really is — it decides the target applied downstream:
  `tempo_threshold` / `vo2max` / `repeticiones` get a pace or power target; `neuromuscular` (strides,
  drills, sprints) is run by feel; `walk_run` is deliberately easy; `cross_training` is aerobic bike or swim
  that spares impact; `other` is a real option when nothing fits.
- `discipline` is what the session IS — `pliometria`, `rehab`, `prehab`, `movilidad`, `tecnica` and `test`
  are first-class. Whether it can go to the watch is decided later in code and is never a reason not to
  prescribe it; say how to execute it well in `execution_cues`.
- **Strength days**: you design the session and the formatter only writes it out (`strength_freeform` does NOT
  mean "leave it to the athlete"). Write it in `session_sketch` the way it will be done: the exercises in
  order, sets x reps, and the load by effort (RIR or RPE) — kilos only when the athlete has given them, because
  you do not know what they lift. Say which quality it trains and how it sits next to the endurance days.
  Strength is not automatically prehab: a declared knee or hip niggle changes the exercises that load that
  structure, not the whole session, and an upper-body day is never rehab because of a leg. In a taper, the days
  after a race or a maintenance phase, strength loses sets, not load.
- **How the athlete already trains in the gym** — their split, their days, their exercises, in the notebook or
  the profile — is a request, like the availability table: build this week on it and progress it. If you do not
  follow it, say why in `week_rationale`.
- **Varias sesiones en un día**: each session is its own `DayIntent`, with its `block_index` and a
  `session_slot` in chronological order (`am`, `pm`, `s3`, `s4`; `unica` only when the day has one). At most 4 per
  day, and only when there is a training reason.
- **On a day with two sessions, the quality one goes FIRST.** Intervals, threshold, race-pace or heavy
  strength are run on fresh legs; the aerobic ride or swim goes after. Putting a 75' Z2 ride in the morning
  and the VO2max repeats in the afternoon turns the key session of the week into a brick nobody asked for,
  and the athlete hits the paces tired — exactly what the session exists to avoid. The exceptions are
  deliberate and you say so in `purpose`: a real brick, or a fixed time the athlete's own grid imposes.
- **Brick — DOS sesiones encadenadas**: bike then run are two sessions the same day, one `sport="bike"` and
  one `sport="run"`, and what makes them a brick — no pause between them — is written in their `purpose`.
  Call it a brick only when there really is no pause.
- You are not limited to the named session types: if the data justifies a progression run, an over-under, a
  fartlek or a time trial, prescribe it and pick the closest class.

{trail_guidance}
## Inputs

### Athlete Context
- Name: {athlete_name}
- Date: ```json {current_date} ```
- This Week's Dates: ```json {week_dates} ```
- Competitions (where each falls from THIS week was computed by the app -- `ya_corrida`, `antes_de_esta_semana`
  (still to be raced, before this week starts), `en_esta_semana`, or the days and macrocycle weeks away;
  do not recount it from the dates): ```json {competitions} ```
- **User Context**: ``` {planning_context} ```

{prescription_card}

### Season Plan (the guide for this block)
```markdown
{season_plan}
```
{mesocycle_position}{volume_target}{training_habits}{load_state}{recovery_state}{athlete_checkin}{niggle_constraints}{coach_notebook}{rutina_de_fuerza}{season_constraints}{previous_plan}{adherence}
### Expert Analysis
- Metrics: ``` {metrics_analysis} ```
- Activity: ``` {activity_analysis} ```
- Physiology: ``` {physiology_analysis} ```
{previous_weeks_summary}
## Output
Return one `WeekIntentContent` with `week_number={week_number}` and one `DayIntent` per date (more when a
day has several sessions).

Write `week_rationale` in Spanish, to the athlete, on why THIS week looks the way it does: first ONE sentence
with what the week is and why (the decision, said plainly); then, each on its own line starting with "- ", the
3-5 things you weighed — what you read in their history and in how they responded, how last week went against
its plan (or that this is their first), how close a race is, the season phase — each with its number said in
words (see «Idioma»). A rationale that would fit any athlete is a failed rationale.{rationale_de_cambios}

**Reglas para los números del `week_rationale`** — el atleta puede comprobarlos en Garmin, y un número que
no le cuadra le hace desconfiar de todo el plan:
- Todo número que cites tiene que aparecer literalmente más arriba en este prompt, o ser una suma de las
  sesiones que acabas de decidir. Si no está, di "eso no lo tengo a la vista".
- Toda medición va con su fuente y su fecha, y cuando dos fuentes se contradicen, citas las dos.
- Una decisión se justifica con un dato del atleta, nunca con la antigüedad de un documento."""


def nota_de_molestias_de_las_respuestas(qa_messages: list) -> list[dict]:
    """La regla de proporción para una molestia contada en las respuestas al coach (Beta 3.0, F10).

    Las respuestas llegan tal cual desde `preguntas_del_coach` (que no se toca); aquí, en el nodo que
    las recibe, se añade un mensaje SÓLO si dicen que algo molesta. Jessie, 2026-09-15: «muy ligera
    molestia en el aquiles» acabó citada en todas sus sesiones.
    """
    from services.ai.niggle_constraints import nota_de_molestias_en_respuestas

    respuestas = "\n".join(
        str(m.get("content") or "") for m in qa_messages or []
        if isinstance(m, dict) and m.get("role") in ("user", "human")
        and str(m.get("content") or "").startswith("Respuestas del atleta")
    )
    nota = nota_de_molestias_en_respuestas(respuestas)
    return [{"role": "user", "content": nota}] if nota else []


def bloques_del_cuaderno(state) -> tuple[str, str]:
    """(lo que pide de su entrenamiento, el resto del cuaderno), listos para el prompt.

    Las «Preferencias de entrenamiento» van aparte, como petición y junto a lo que
    se respeta tal cual (Fernando, 2026-09-22: ver
    notebook.render_peticiones_de_entrenamiento). Un estado sin la clave
    `peticiones` -- escrito antes de esto -- recibe el cuaderno entero, como antes.
    """
    cuaderno = (state.get("availability_constraints") or {}).get("notebook") or {}
    peticiones = cuaderno.get("peticiones") or ""
    resto = (cuaderno.get("block_sin_peticiones") if "peticiones" in cuaderno
             else cuaderno.get("block")) or ""
    return (f"\n{peticiones}\n" if peticiones else "", f"\n{resto}\n" if resto else "")


def dias_de_su_rutina(week_intent, rutina) -> list:
    """Los días de fuerza que cubre su programa CONFIRMADO, en orden de fecha.

    La comprobación de que está confirmado va aquí y no sólo en quien llama: una
    rutina pegada y sin revisar no puede escribir la semana de nadie, y esta
    función es la que decide qué días salen del prompt del codificador.
    """
    if not rutina or not rutina.confirmada or not pf.sesiones(rutina):
        return []
    return sorted(
        (d for d in week_intent.days if d.sport == "strength" and not d.is_rest),
        key=lambda d: (d.date or "", d.session_slot or ""),
    )


def _hay_fuerza(week_intent) -> bool:
    """Si la semana decidida lleva alguna sesión de fuerza.

    El catálogo de ejercicios (claves de imagen y de Garmin) son ~12.400 de los
    33.806 caracteres del mensaje del codificador medido en la semana de la
    atleta 3 del 2026-09-14, y sólo sirven para escribir un `strength_block`.
    """
    return any(getattr(d, "sport", None) == "strength" and not getattr(d, "is_rest", False)
               for d in getattr(week_intent, "days", []) or [])


WORKOUT_FORMATTER_SYSTEM_PROMPT = """## Goal
Convert already-decided training sessions into the exact Garmin workout schema. The coaching decisions are
already made — your only job is faithful, correctly-typed translation, not judgment.
## Rules
- Never change what a session IS (its intensity, sport, duration, or intent) — only how it's expressed.
- Copy `date`, `session_slot`, `block_index`, `is_rest`, `sport`, `focus`, `total_duration_minutes`,
  `intensity_class` and `discipline` directly from the matching decision below — do not invent different values
  for them. The purpose, the adaptation and the week's rationale are copied by the app itself: do not write them. `intensity_class` in particular must be copied verbatim: downstream rules read
  it to know which target modality this session should carry, so changing it silently changes the session.
- If a `session_sketch` is ambiguous about a pace or an intensity, use the Prescription Card's band for that
  day's `intensity_class` rather than guessing a number that isn't grounded in the athlete's real data. If it
  gives a DURATION as a range («15-20″»), use a value inside that range: the card has no durations, and a
  number outside what the decision wrote is a different session.
- **Some sessions do not fit Garmin's workout format at all** -- plyometrics, rehab, prehab, mobility
  and brick sessions. For those, write the WHOLE session out in `manual_instructions`: exercises, sets,
  reps or time, rest, and the execution cues, in Spanish, exactly as the athlete will read it off their
  phone. That text is the only form of the session they will ever see, so a placeholder like 'ver el
  plan' is a failure. Still fill `steps` with the best structural approximation you can -- it drives the
  duration the app shows -- but never let the approximation stand in for the written session."""

WORKOUT_FORMATTER_USER_PROMPT = """## Task
Convert the day-by-day training decisions below into a structured `WeeklyPlanPayload` with exactly ONE
`WeekBlock` (the exact JSON schema is enforced automatically — focus on the CONTENT below).

## Schema rules
- **MANDATORY — every non-rest day MUST have real `steps`, never an empty list.** A day with `is_rest=false`
  and `steps=[]` is an invalid, unusable output — every training session needs at minimum a warmup step, one
  or more main/interval steps, and a cooldown step (skip warmup/cooldown only for genuinely short easy days
  where that's standard practice, e.g. a 20-minute recovery jog).
- **UNA REPETICIÓN SE ESCRIBE EN UNA SOLA LÍNEA, como la diría un entrenador.** No anides nada: la lista de
  pasos es PLANA. Para «5x400m a ritmo VO2max con 2' de trote entre series» escribe UN paso con el trabajo,
  `repeat_count=5`, `recovery_duration_type="time"` y `recovery_duration_value=120`. El código construye el
  bloque anidado que el reloj necesita.
  **La recuperación no es opcional cuando hay repeticiones**: sin ella las series salen pegadas una detrás
  de otra y el atleta empieza la siguiente en el instante en que termina la anterior. **Va en la unidad en
  que la decisión la escribió**, igual que el trabajo: «2' de trote» es `time` con 120 segundos; «200 m de
  trote» es `distance` con 200 metros; «hasta que recuperes» es `lap_button` sin valor. Nunca conviertas
  metros en segundos ni al revés: el reloj corta el paso donde le digas.  Ejemplo (5x3' a ritmo umbral con 2' de trote de recuperación):
  ```json
  {{"steps": [
    {{"step_order": 1, "step_type": "warmup", "duration_type": "time", "duration_value": 600, "target_type": "none", "description": "10' calentamiento, entra muy suave y sube poco a poco"}},
    {{"step_order": 2, "step_type": "interval", "duration_type": "time", "duration_value": 180, "repeat_count": 5, "recovery_duration_type": "time", "recovery_duration_value": 120, "recovery_target_zone": 1, "recovery_description": "2' trote suave Z1", "target_type": "pace", "target_low": 340, "target_high": 355, "description": "5x(3' a 5:40-5:55/km, 2' rec)"}},
    {{"step_order": 3, "step_type": "cooldown", "duration_type": "time", "duration_value": 600, "target_type": "none", "description": "10' enfriamiento, deja que el pulso baje solo"}}
  ]}}
  ```
  Y con la recuperación por distancia (6x400m con 200 m de trote entre series):
  ```json
  {{"step_order": 2, "step_type": "interval", "duration_type": "distance", "duration_value": 400, "repeat_count": 6, "recovery_duration_type": "distance", "recovery_duration_value": 200, "recovery_description": "200 m trote muy suave", "target_type": "pace", "target_low": 305, "target_high": 315, "description": "6x(400m a 5:05-5:15/km, 200 m trote)"}}
  ```
  Cuando las repeticiones **no son iguales entre sí** (series progresivas o descendentes), escríbelas como
  pasos sueltos, uno por repetición, alternando trabajo y recuperación. Ejemplo de serie descendente:
  ```json
  {{"steps": [
    {{"step_order": 1, "step_type": "interval", "duration_type": "distance", "duration_value": 1000, "target_type": "pace", "target_low": 360, "target_high": 365, "description": "1000m a 6:00-6:05/km"}},
    {{"step_order": 2, "step_type": "recovery", "duration_type": "time", "duration_value": 120, "target_type": "none", "description": "2' trote suave"}},
    {{"step_order": 3, "step_type": "interval", "duration_type": "distance", "duration_value": 1000, "target_type": "pace", "target_low": 350, "target_high": 355, "description": "1000m a 5:50-5:55/km"}},
    {{"step_order": 4, "step_type": "recovery", "duration_type": "time", "duration_value": 120, "target_type": "none", "description": "2' trote suave"}},
    {{"step_order": 5, "step_type": "interval", "duration_type": "distance", "duration_value": 1000, "target_type": "pace", "target_low": 340, "target_high": 345, "description": "1000m a 5:40-5:45/km"}}
  ]}}
  ```
  `target_low`/`target_high` for pace are in SECONDS PER KILOMETRE (340 = 5:40/km), taken from the pace
  bands in the Prescription Card. Never write minutes (5.40) or metres per second (2.94) there.
  A recovery the athlete ends manually when ready is `lap_button` (inside a set,
  `recovery_duration_type="lap_button"`).
- **No emitas `sport="brick"`.** No está en el esquema y no se sube al reloj: un multideporte no le llega
  bien a varios relojes. Un brick viene ya partido en dos sesiones desde la capa de decisión, y cada una se
  formatea como lo que es — la de bici como una sesión de bici, la de carrera como una de carrera. Lo que
  las encadena va escrito en la descripción del primer paso de la segunda ("empieza nada más bajar de la
  bici"), que es lo que el atleta lee en la muñeca cuando arranca.
  `leg_sport` sigue existiendo en el esquema para los planes ya guardados que llevan bricks dentro; en una
  sesión nueva no hace falta y lo dejas vacío.
- **Natación: rellena los campos NATIVOS del paso**, además de escribirlo en la descripción.
  `stroke`: `free` (crol), `backstroke` (espalda), `breaststroke` (pecho), `fly` (mariposa),
  `individual_medley` (estilos), `mixed` (alterna dentro del bloque), `any_stroke` (elige el atleta).
  `equipment`: `pull_buoy`, `paddles`, `fins`, `kickboard`, `snorkel` — vacío si no lleva material.
  `drill`: `kick` (sólo piernas), `pull` (sólo brazos), `drill` (técnica) — vacío si es nado completo.
  Sirven para que el reloj muestre el estilo y cuente bien las vueltas. **La descripción los sigue
  llevando igualmente**, así que si dudas del valor exacto, déjalo vacío y escríbelo en el texto.
  Y las series se escriben como series: `8x100m` es un paso con `repeat_count=8` y `duration_value=100`,
  no un bloque continuo de 800 m — también cuando la decisión escribe la suma al lado («6x200 m … = 1.200 m»
  son seis de 200 m). Un «Total …» es una cuenta para comprobar, nunca un paso.
- **`sport="strength"`: UN solo paso, y el desglose real en `strength_block`.**

  `steps` lleva exactamente un paso — `step_type="main"`, `duration_type="time"`,
  `duration_value` = los segundos de toda la sesión, `repeat_count=1`, `target_type="none"` — porque
  `steps` sólo existe para viajar a Garmin y la fuerza NO viaja. **Nunca le pongas `repeat_count` ni
  recuperación a un paso de fuerza.**

  La sesión de verdad va en **`strength_block`**, y ahí sí la escribes entera. El atleta la lee en el
  móvil, en el gimnasio, entre serie y serie: escríbela para hacerla, no para resumirla.
  - `warmup` y `cooldown`: una o dos frases cada uno.
  - `exercises`: los ejercicios EN ORDEN, cada uno con `name`, `sets`, `reps` (texto: "8-10",
    "12 por lado", "30 s"), `load` ("peso corporal", "12 kg", "RPE 7"), `rest_seconds`, `cues`
    (una frase de ejecución) y `targets` (qué trabaja).
  - `video_key`: **la clave del ejercicio, ELEGIDA DE LA LISTA CERRADA de más abajo.** Es lo que le
    pone al atleta la imagen del movimiento al lado de la serie, en el móvil y en el gimnasio. Si el
    ejercicio que prescribes no está en la lista, **déjala vacía** y explica el movimiento en `cues`:
    una clave parecida le enseña la imagen de OTRO ejercicio, y hacer el movimiento equivocado creyendo
    que hace el suyo es peor que no ver ninguna imagen. Nunca cambies el ejercicio para que encaje.
  - `garmin_exercise`: el identificador de Garmin del ejercicio, si alguno de la lista de Garmin es el
    mismo movimiento. Sirve para que el nombre exacto sobreviva al reloj — sin él el atleta ve un bloque
    genérico donde el plan decía "peso muerto rumano unipodal". Elige uno sólo si es realmente ese
    movimiento; si el que prescribes no está, déjalo vacío. Nunca deformes la sesión para que encaje en
    la lista: primero prescribes lo que el atleta necesita, y sólo después miras si Garmin lo tiene.
{exercise_catalogue}
{exercise_images}
  - `garmin_category`: la familia del movimiento, para que el ejercicio llegue al RELOJ con nombre.
    Una de: `SQUAT`, `DEADLIFT`, `LUNGE`, `HIP_RAISE`, `CALF_RAISE`, `BENCH_PRESS`, `PUSH_UP`, `PULL_UP`,
    `ROW`, `SHOULDER_PRESS`, `CURL`, `TRICEPS_EXTENSION`, `PLANK`, `CARRY`, `SLED`, `CARDIO`.
    Una sentadilla búlgara es `SQUAT`; un puente de glúteo, `HIP_RAISE`; un dead bug, `PLANK`;
    unas elevaciones de talón, `CALF_RAISE`. Si de verdad ninguna encaja, déjalo vacío.
  - `notes`: sólo si hay algo que ajustar para ESTE atleta (una molestia declarada, material que hace
    falta). Vacío si no lo hay.

  **Los ejercicios, las series, las repeticiones y la carga son los que trae la decisión** en su
  `session_sketch`: cópialos al `strength_block` tal cual y sólo elige sus claves de las listas. Si la
  decisión no los detalla, complétalos con su foco y sus minutos (cuatro o cinco ejercicios en 35-40 min es
  lo normal) y pon la carga por esfuerzo (RIR o RPE), nunca unos kilos que nadie te dio.
  Si el atleta declaró una molestia, cambia sólo los ejercicios que la cargan y dilo en `notes`.

  Ejemplo:
  ```json
  {{"steps": [
    {{"step_order": 1, "step_type": "main", "duration_type": "time", "duration_value": 2100, "repeat_count": 1, "target_type": "none", "description": "Fuerza - cadena posterior y gluteo medio, 35 min"}}
  ],
    "strength_block": {{
      "warmup": "5 min de bici suave y movilidad de cadera y tobillo.",
      "exercises": [
        {{"name": "Peso muerto rumano unipodal", "sets": 3, "reps": "8-10 por lado",
          "load": "RIR 2: termina cada serie con dos repeticiones en reserva",
          "rest_seconds": 90, "cues": "Columna neutra; la cadera va atras, no abajo.",
          "targets": "Isquiotibiales y gluteo mayor", "video_key": "peso_muerto_rumano_unipodal"}},
        {{"name": "Puente de gluteo con apoyo elevado", "sets": 3, "reps": "12",
          "load": "Peso corporal", "rest_seconds": 60,
          "cues": "Empuja con el talon y no arquees la zona lumbar.",
          "targets": "Gluteo mayor", "video_key": "puente_gluteo_apoyo_elevado"}}
      ],
      "cooldown": "Movilidad de cadera, 3 min.",
      "notes": "Si la ingle izquierda se activa, reduce el rango del peso muerto y no bajes tanto."
    }}
  }}
  ```
- **`sport="swim"`: por DISTANCIA, con el descanso como paso propio.**

  Una serie de natación se escribe en metros, no en minutos: `8x100m`, no `13 min de series`. El paso
  lleva `duration_type="distance"` y `duration_value` en METROS, y el descanso va dentro de la serie en
  SU unidad, igual que en carrera: «20″ en el borde» es `recovery_duration_type="time"` con 20; «25 m
  suave» es `recovery_duration_type="distance"` con 25. Reservar el tiempo continuo para el
  calentamiento, la vuelta a la calma y el nado suave.

  **El estilo y el material van en `description`**, en texto: «crol», «espalda», «estilos», «técnica de
  brazo con pull-buoy», «pies con tabla», «palas». Garmin acepta cualquier texto ahí y es lo que el
  atleta lee en el reloj entre series.

  **Objetivo:** si la Ficha de Prescripción trae ritmos de natación por 100 m, escríbelos en la
  `description` («8x100m a 1:45-1:48»). Si NO los trae, no inventes un ritmo: escribe la serie por
  distancia + descanso + esfuerzo («8x100m fuerte pero sostenible, 20" de descanso»), que es como se
  escribe una serie de natación de todos modos. Para el nado suave usa `target_type="hr_zone"` con las
  zonas de NATACIÓN, que son distintas de las de carrera.

  Ejemplo de una sesión de calidad en piscina:
  ```json
  {{"steps": [
    {{"step_order": 1, "step_type": "warmup", "duration_type": "distance", "duration_value": 400, "repeat_count": 1, "target_type": "none", "description": "400m suave alternando crol y espalda"}},
    {{"step_order": 2, "step_type": "interval", "duration_type": "distance", "duration_value": 50, "repeat_count": 6, "recovery_duration_type": "time", "recovery_duration_value": 20, "recovery_description": "20\\" de descanso en el borde", "target_type": "none", "description": "6x50m tecnica de brazo con pull-buoy, largo y sin prisa"}},
    {{"step_order": 3, "step_type": "interval", "duration_type": "distance", "duration_value": 100, "repeat_count": 8, "recovery_duration_type": "time", "recovery_duration_value": 20, "recovery_description": "20\\" de descanso", "target_type": "none", "description": "8x100m crol a 1:45-1:48, fuerte pero sostenible"}},
    {{"step_order": 4, "step_type": "cooldown", "duration_type": "distance", "duration_value": 200, "repeat_count": 1, "target_type": "none", "description": "200m suave, respiracion bilateral"}}
  ]}}
  ```

- Every step with an intensity target must set `target_type`, following the MODALITY RULE in the
  Prescription Card above — it is not a preference, it is the difference between a session that works and
  one that doesn't:
  - **Quality run steps** (`intensity_class` tempo_threshold/vo2max/repeticiones) → `target_type="pace"` with
    an explicit `target_low`/`target_high` **in seconds per kilometre**, taken from the pace band for that
    intensity class. Do NOT prescribe these by heart rate -- **except on a treadmill** (the session or the step
    says "cinta" or "treadmill"): there the machine sets the speed and the watch cannot measure pace indoors, so
    the work steps take `target_type="hr_zone"` and the `description` keeps the speed in km/h with its pace,
    exactly as the decision wrote it ("3' a 11,3–11,9 km/h (5:18–5:02/km) en cinta").
  - **Warmup, cooldown, recovery, easy/long runs** (`intensity_class` recovery/easy/long_run/walk_run) →
    `target_type="hr_zone"` with `target_zone` (1-5). Leave `target_low`/`target_high` empty.
  - **By-feel work** (`intensity_class="neuromuscular"`: strides, accelerations, hill sprints, drills) →
    `target_type="none"`, and put the instruction in `description` ("20\" relajado y rapido, sin forzar").
    That is correct and expected here — do NOT invent a pace band for a 15-second effort.
  - **Bike steps defined by power** → `target_type="power"`. **Strength** → `target_type="none"`.
  - **Race day** (`intensity_class="race_day"`): warmup + race effort/pacing plan + cooldown, never a
    generic continuous step — name the race in the description. The race itself is ONE step with
    `duration_type="lap_button"` and no value: nobody knows how long a race takes until it ends, and a fixed
    time would send the athlete to the cooldown mid-race. The pacing plan goes in its `description`.
  The target must agree with `description`: a step described as "5:40-5:55/km" carries those exact numbers,
  and a step described as "Z2" carries `target_zone=2`.
- **UNA zona por paso, o ninguna. "Z1-Z2" no es un objetivo válido.** `target_zone` es un número, y el
  reloj sólo entiende una zona: si mandas Z2, el atleta recibe un aviso cada vez que baja a Z1, que es
  justo lo que hace inútil un rodaje suave. Cuando la intención abarca de verdad dos zonas — un rodaje
  largo que vale igual en Z1 que en Z2 — el paso lleva `target_type="none"` y la `description` dice el
  rango en texto ("60' suave, Z1-Z2, sin forzar"). El atleta lo lee y el reloj no le regaña.
  Cuando la intención es una zona concreta, manda esa zona y ya.
- **Calentamiento, enfriamiento y recuperación van SIEMPRE con `target_type="none"`.** No es una
  preferencia de estilo: en esos tres pasos el pulso está atravesando zonas a propósito. Un calentamiento
  empieza en reposo, y un enfriamiento (o la recuperación entre repeticiones) empieza donde acabó el
  esfuerzo — después de 8x2' fuertes el pulso tarda minutos en bajar. El objetivo de Garmin avisa por
  arriba Y por abajo, así que cualquier zona única en esos pasos le avisa al atleta justo mientras hace
  lo correcto, y un aviso injusto le enseña a ignorar todos los demás. Lo que quieras decirle ("entra
  suave", "trote muy tranquilo", "deja que baje solo") va en la `description`, que el reloj le muestra.
  Las zonas se reservan para los pasos donde el pulso debe QUEDARSE quieto en un sitio.
- **Nunca mandes un rango de pulsaciones en `target_low`/`target_high` para `hr_zone`.** Las zonas son
  las que el atleta tiene configuradas en SU reloj: mandando el número de zona, Garmin aplica sus
  límites. Un rango en bpm derivado aquí ya le puso una vez una "Zona 2" empezando en 143 cuando su
  reloj empieza en 139.
- Each `WorkoutStep.description`: compact standard notation, e.g. "4x(5' Z4, 2' recovery)".
- **Lo que la decisión le dice al atleta va entero en las `description`, también lo opcional o lo
  condicional** («si te sientes muy bien, añade al final 4x20" progresivos», «si notas el aquiles, para»):
  ponlo en el paso donde ocurre. El atleta lee su sesión en esos pasos, en el reloj y en la app; lo que no
  copies, no lo verá nunca.
- Rest days: `is_rest=true`, `sport="rest"`, empty `steps`.

{prescription_card}

## This week's training decisions (already decided — format these, do not change what they are)
```json
{week_intent}
```

## Output
Return exactly one `WeekBlock` with `week_number={week_number}` and one `WorkoutDay` per decision above,
covering exactly the same dates/session_slots."""


def _week_summary_line(day: WorkoutDay) -> str:
    if day.is_rest:
        return f"{day.date} ({day.day_name_es}): rest"
    return f"{day.date} ({day.day_name_es}, {day.session_slot}): {day.sport} — {day.focus}"


def _build_previous_weeks_summary(weeks: list[WeekBlock]) -> str:
    """Short recap of prior chunks' last few days, so week N+1 doesn't repeat
    a hard session right after week N's long run/back-to-back intensity, and
    stays in the same training flow instead of reading as a disconnected plan."""
    if not weeks:
        return ""
    all_days = [d for w in weeks for d in w.days]
    tail = all_days[-3:]
    lines = "\n".join(f"- {_week_summary_line(d)}" for d in tail)
    return f"\n### Previous Weeks (last few days — keep the flow, avoid repeating a hard day right after these)\n{lines}\n"


def _load_season_phases(user_id: str) -> dict | None:
    """Structured {"phases": [...], "season_start_date": ...} persisted by
    web_app/server.py's run_generation_task after a season plan generation.
    Returns None for profiles that never generated a season plan under this
    schema (pre-Fase-3 plans, HITL-pending, or failed generation) — callers
    must fall back to the old prose-only behavior in that case."""
    try:
        raw = FilePlanStorage().load_plan(user_id, "season_phases", extension="json")
        return json.loads(raw) if raw else None
    except (OSError, json.JSONDecodeError):
        logger.warning("Could not load/parse season_phases.json for user %s", user_id)
        return None


def _load_previous_plan(user_id: str) -> WeeklyPlanPayload | None:
    """The plan this athlete was last given.

    Read at generation time, when weekly_plan.json still holds the PREVIOUS
    generation -- web_app/server.py only overwrites it (and archives the old
    copy into history/) once the workflow has finished. Without this the coach
    had no memory: every generation reinvented the block from scratch, so an
    athlete regenerating mid-week could get a completely different progression
    with no acknowledgement that anything had come before.
    """
    try:
        raw = FilePlanStorage().load_plan(user_id, "weekly_plan", extension="json")
        if not raw:
            return None
        parsed = json.loads(raw)
        if "error" in parsed:
            return None  # a failed generation left a placeholder, not a plan
        return WeeklyPlanPayload.model_validate(parsed)
    except (OSError, json.JSONDecodeError, ValidationError):
        logger.warning("Could not load the previous weekly plan for user %s", user_id, exc_info=True)
        return None


# CUANTAS semanas anteriores se le enseñan al planificador.
#
# UNA. Iban TODAS las guardadas -- hasta `MAX_WEEKS_KEPT` (12), medido en 2.966
# tokens por generacion contra los 433 de una sola, y cada semana extra suma
# ~230. Pero el argumento no es el precio, es que las otras once no aportan
# nada que no venga ya por un canal mejor:
#
#   - la FASE, el volumen objetivo y el enfasis los da el plan de temporada;
#   - lo que el atleta REALMENTE hizo lo da el bloque de adherencia, medido
#     contra Garmin, no lo que se le prescribio;
#   - la memoria de mas de siete dias la da `track_record.jsonl`, que existe
#     exactamente para eso (ver ../CLAUDE.md).
#
# Lo unico que la semana anterior aporta y nadie mas da son los ANCLAJES: que
# dia es el de calidad, cual la tirada larga, que dobles ya hace este atleta.
# Para eso basta la ultima, que es ademas la que el prompt de al lado manda
# conservar ("manten los anclajes y haz progresar la DOSIS").
#
# Once semanas de prescripciones viejas no son contexto: son ruido con forma de
# contexto, y encima invitan a promediar sobre una progresion que ya no corre.
SEMANAS_DE_CONTEXTO = 1


def _ejercicio_previo(e) -> str:
    """"Press de banca 3x8 (RIR 2)"; sin carga escrita, sin paréntesis vacíos."""
    carga = (getattr(e, "load", "") or "").strip()
    return f"{e.name} {e.sets}x{e.reps}" + (f" ({carga})" if carga else "")


_DIAS_ES = ("lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo")


def _dia_escrito(fecha: str) -> str:
    """'2026-10-13' -> 'martes 13/10'. Escrito por el código: el modelo no calcula días."""
    try:
        d = datetime.strptime(str(fecha)[:10], "%Y-%m-%d").date()
    except ValueError:
        return str(fecha)
    return f"{_DIAS_ES[d.weekday()]} {d.strftime('%d/%m')}"


def cambios_del_atleta(payload: WeeklyPlanPayload | None) -> list[str]:
    """Lo que el ATLETA cambió de su última semana desde la app (Beta 3.0, F6.6): sesiones que
    movió de día (`edited.moved_from`) o cuyos pasos ajustó (`edited.adjusted`). Una frase por
    sesión, con los días escritos por el código. Sin cambios, lista vacía y el prompt no cambia.
    """
    if payload is None or not payload.weeks:
        return []
    frases = []
    for day in payload.weeks[-1].days:
        editado = getattr(day, "edited", None) or {}
        if day.is_rest or not isinstance(editado, dict):
            continue
        nombre = f"«{day.focus}»" if day.focus else f"la sesión de {day.sport}"
        if editado.get("moved_from"):
            frases.append(f"movió {nombre} del {_dia_escrito(editado['moved_from'])} al {_dia_escrito(day.date)}")
        if editado.get("adjusted"):
            frases.append(f"ajustó los pasos de {nombre} del {_dia_escrito(day.date)} (lo de arriba es su versión)")
    return frases


# La frase que el racional le debe al atleta cuando cambió algo desde la app (F6.6). Va en el
# «## Output», junto a lo que pide `week_rationale`: escrita en el bloque del plan anterior
# (a ~60.000 caracteres de ahí) dos réplicas sobre la semana real de Jessie no la escribieron.
RATIONALE_DE_CAMBIOS = (
    "\nThe athlete moved or adjusted sessions of last week from the app (see \"Lo que el atleta cambió\" in "
    "the previous plan): one sentence of `week_rationale` says, in Spanish, that you saw it and what you did "
    "with it -- kept it, followed it, or why not."
)


def rationale_de_cambios(payload: WeeklyPlanPayload | None, current_date: str, rehaciendo: bool = False) -> str:
    """La frase de arriba sólo si el bloque del plan anterior dice que hubo cambios."""
    texto = _format_previous_plan(payload, current_date, rehaciendo)
    return RATIONALE_DE_CAMBIOS if "**Lo que el atleta cambió de esa semana desde la app**" in texto else ""


def _format_previous_plan(payload: WeeklyPlanPayload | None, current_date: str,
                          rehaciendo: bool = False) -> str:
    """What the athlete was already prescribed, compact enough for a prompt.

    One line per training day, carrying the main set's own notation so the
    coach can see the actual doses it gave -- not just "there was a run" but
    "5x(3' a 5:40-5:55/km, 2' rec)". That is what makes a follow-up generation
    a refinement instead of a fresh guess.
    """
    if payload is None or not payload.weeks:
        return ""

    lines: list[str] = []
    for week in payload.weeks[-SEMANAS_DE_CONTEXTO:]:
        day_lines = []
        for day in week.days:
            if day.is_rest:
                continue
            # La sesión ENTERA, en orden. Era el primer paso de series o principal, y en una
            # sesión con progresiones de calentamiento ese paso son las progresiones: la
            # semana del atleta 2 del 2026-10-05 recibió "VO2max controlado 60' -- 4x20"
            # progresivos" de una sesión cuyo bloque era 5x3', y "largo 3.5 km -- 8x50m
            # técnica" de su natación larga (auditoría del 2026-09-24). Lo que se hace
            # progresar tiene que llegar.
            main = " | ".join(s.description.strip() for s in day.steps if (s.description or "").strip())
            # La fuerza, con sus ejercicios: su único paso decía "Fuerza de
            # mantenimiento, 30 min", y así no hay forma de hacer progresar
            # unas series que el planificador nunca vio (atleta Fernando,
            # 2026-09-14: "el plan me lo hizo similar a la semana anterior").
            fuerza = getattr(day, "strength_block", None)
            if fuerza and getattr(fuerza, "groups", None):
                # Beta 2.1: con superseries, qué iba seguido y cuánto se descansaba.
                main = "; ".join(
                    f"{g.label or chr(65 + i)}: " + " + ".join(_ejercicio_previo(e) for e in g.exercises)
                    + (f" [{g.rest_between_rounds_s}s entre rondas]" if g.rest_between_rounds_s else "")
                    for i, g in enumerate(fuerza.groups)
                )
            elif fuerza and getattr(fuerza, "exercises", None):
                main = "; ".join(_ejercicio_previo(e) for e in fuerza.exercises)
            duration = f"{day.total_duration_minutes:.0f}'" if day.total_duration_minutes else ""
            # La fuerza de SU rutina no lleva minutos aquí: los fijan su sesión y la dosis
            # del paso de fuerza, no la decisión. Con ellos delante la decisión los copiaba
            # (atleta 2, 2026-09-23: 36', 21' y 42' de una semana a dosis mínima, para
            # sesiones de 60-120', también al "Rehacer") y el recorte de una semana se volvía
            # el de todas.
            if fuerza and getattr(fuerza, "routine_session_id", ""):
                duration = "(de su programa: el tiempo lo fijan su sesión y su dosis)"
            # Lo que cambió el atleta desde la app (Beta 3.0, F6.6), en la misma línea.
            editado = getattr(day, "edited", None) or {}
            marca = ""
            if isinstance(editado, dict) and editado.get("moved_from"):
                marca += f" [la MOVIÓ el atleta desde el {_dia_escrito(editado['moved_from'])}]"
            if isinstance(editado, dict) and editado.get("adjusted"):
                marca += " [el atleta AJUSTÓ sus pasos: es su versión]"
            day_lines.append(
                f"  - {day.date} {day.sport} · {day.focus} {duration}"
                + (f" — {main}" if main else "") + marca
            )
        if day_lines:
            lines.append(f"Semana {week.week_number}:")
            lines.extend(day_lines)

    if not lines:
        return ""

    # A plan whose 28 days ended long ago is history, not a block in progress.
    # Telling the coach to "continue from here" after a three-month gap would
    # have it resume a build the athlete may not have trained a day of.
    #
    # Contra la ULTIMA semana, no contra `payload.start_date`: `_append_week`
    # nunca mueve esa fecha, que es la de la primera semana de todas. Asi, un
    # atleta constante que iba por su semana 7 veia su semana anterior
    # etiquetada como "historial -- NO retomes la progresion". Auditoria
    # previa al merge, 2026-09-11.
    fechas_ultima = [d.date for d in (payload.weeks[-1].days if payload.weeks else []) if d.date]
    days_elapsed = _days_between(min(fechas_ultima) if fechas_ultima else payload.start_date, current_date)
    is_stale = days_elapsed is not None and days_elapsed > 42

    # Rehacer no es continuar: es REESCRIBIR estos mismos siete dias.
    #
    # Sin esta rama, el modo "rehacer solo la semana" le pasaba la semana que
    # iba a reemplazar bajo el encabezado "lo que YA le dosificaste" y con la
    # instruccion "no repitas la misma semana". El modelo hacia justo lo que
    # se le pedia: la cambiaba entera. El atleta pidio ejercicios de hombro el
    # jueves y de paso perdio sus dos sesiones del viernes, que la generacion
    # anterior si le habia puesto.
    #
    # Aqui la semana anterior no es un antecedente del que alejarse: es el
    # BORRADOR que se corrige.
    if rehaciendo:
        framing = (
            f"**Esta es la semana que estas REESCRIBIENDO**, no una anterior de la que alejarte: "
            f"empieza el {payload.start_date} y son exactamente los dias que vas a volver a escribir. "
            "El atleta la ha visto y ha pedido UN cambio concreto (lo tienes en User Context).\n"
            "**Conserva todo lo demas**: los mismos dias, los mismos deportes, las mismas sesiones "
            "dobles y la misma estructura. Cambia lo que te pidio y lo que ese cambio arrastre por "
            "necesidad -- nada mas. Reescribirla entera le quita cosas que no pidio perder y le "
            "borra el trabajo de revisarla."
        )
    elif is_stale:
        framing = (
            f"Empezaba el {payload.start_date}, hace unas {days_elapsed // 7} semanas, y su ventana de 28 "
            f"dias ya termino (hoy es {current_date}). Tomalo como historial, NO como un bloque en curso: "
            "sirve para saber que tipo de trabajo ya conoce este atleta, pero NO asumas que lo completo ni "
            "retomes la progresion donde la dejo. Lo que realmente hizo esta en el analisis de actividad."
        )
    else:
        framing = (
            f"Empezaba el {payload.start_date}; hoy es {current_date}. Es el plan que el atleta ha estado "
            "siguiendo (o al menos el que recibio).\n"
            "Esto es un MICROCICLO dentro de un mesociclo, no una semana suelta. **Repetir sesiones de "
            "una semana a otra es lo normal y lo correcto**: un entrenador mantiene los anclajes -- el dia "
            "de calidad, la tirada larga, la fuerza, las dobles que el atleta ya hace -- y hace progresar "
            "la DOSIS (minutos, repeticiones, densidad), no la identidad de las sesiones. Variar por variar "
            "rompe la adaptacion, que necesita repeticion para producirse.\n"
            "Lo que si evitas es reinventar la semana de cero o volver a empezar un bloque que ya estaba "
            "en marcha. Si mueves o cambias un anclaje, que sea por una razon que se pueda leer en los "
            "datos del atleta, o porque el atleta lo pidio (cuaderno, check-in): lo que pide el atleta "
            "gana al anclaje. La semana de un atleta que escribio como entrena en el gimnasio no se "
            "parece a la anterior porque haya que mantenerla, sino si eso es lo que pidio."
        )

    # Lo que el atleta cambió de esa semana (Beta 3.0, F6.6; plan: «el planificador sabe lo que
    # cambió el atleta»). Es lo que HIZO, no lo que pidió: una preferencia observada. No se
    # convierte en regla (la tabla de días y lo que pide siguen mandando) ni un cambio suelto
    # rehace su semana; si se repite, se coloca o dosifica como lo hace el atleta.
    cambios = cambios_del_atleta(payload) if not (rehaciendo or is_stale) else []
    if cambios:
        framing += (
            "\n**Lo que el atleta cambió de esa semana desde la app** (lo hizo el atleta; es una preferencia "
            "OBSERVADA, no una regla ni una petición): " + "; ".join(cambios) + ". "
            "Si un cambio así se repite o encaja con lo que ha dicho, colócalo o dosifícalo como lo "
            "hace el atleta; si fue una vez, pudo ser una semana rara: no rehagas su semana por un cambio. Su tabla "
            "de días y lo que pide siguen mandando."
        )

    if rehaciendo:
        heading = "La semana que estas REESCRIBIENDO"
    else:
        heading = "Plan anterior (historial)" if is_stale else "Plan anterior — lo que YA le dosificaste a este atleta"
    return f"\n### {heading}\n{framing}\n{chr(10).join(lines)}\n"


def _days_between(start_date: str, end_date: str) -> int | None:
    try:
        start = datetime.strptime(start_date, "%Y-%m-%d").date()
        end = datetime.strptime(end_date, "%Y-%m-%d").date()
    except (ValueError, TypeError):
        return None
    return (end - start).days


def _techo_de_sesiones_duras(valor) -> float | None:
    """El techo de sesiones duras de la fase, acotado a una fracción entre 0 y 1.

    El esquema no puede llevar `ge`/`le` (ver `SeasonPhase`), así que se acota
    aquí. Un modelo que escribe 20 queriendo decir 20 % se lee como 0,2: es la
    lectura evidente, y la alternativa -- un techo de 2.000 % -- no significa nada.
    """
    try:
        v = float(valor)
    except (TypeError, ValueError):
        return None
    if 1.0 < v <= 100.0:
        v /= 100.0
    return v if 0.0 <= v <= 1.0 else None


def compute_mesocycle_position(season_phases: dict | None, target_date_str: str) -> dict | None:
    """Deterministic (non-LLM) calculation of which real season week a given
    date falls in, replacing what used to be pure LLM guesswork from reading
    the season plan's markdown prose. Returns None when there's no structured
    season plan to anchor against (see _load_season_phases)."""
    if not season_phases or not season_phases.get("phases"):
        return None
    try:
        start = datetime.strptime(season_phases["season_start_date"], "%Y-%m-%d").date()
        target = datetime.strptime(target_date_str, "%Y-%m-%d").date()
    except (ValueError, TypeError, KeyError):
        return None

    days_elapsed = (target - start).days
    if days_elapsed < 0:
        return None
    week_number = days_elapsed // 7 + 1

    for phase in season_phases["phases"]:
        if phase.get("start_week", 0) <= week_number <= phase.get("end_week", 0):
            return {
                "week_number": week_number,
                "phase_name": phase.get("name"),
                "is_deload": week_number in (phase.get("deload_weeks") or []),
                "weekly_hours_target": phase.get("weekly_hours_target"),
                "weekly_load_target": phase.get("weekly_load_target"),
                "intensity_emphasis": phase.get("intensity_emphasis"),
                # Beta 2.1 (S7): qué busca la FUERZA en esta fase, si el macrociclo lo
                # escribió (sólo lo escribe cuando el atleta dijo para qué la hace).
                # Ausente en todo macrociclo anterior, y entonces no se dice nada.
                "strength_quality": (phase.get("strength_quality") or "").strip() or None,
                # v1.2.2. Ausentes en un macrociclo guardado antes: entonces el
                # prompt no dice nada de ellos, que es lo que decía antes.
                "intensity_distribution": phase.get("intensity_distribution"),
                "hard_session_share_max": _techo_de_sesiones_duras(phase.get("hard_session_share_max")),
                "focus": [str(f).strip() for f in (phase.get("focus") or []) if str(f).strip()][:2],
                "maintains": (phase.get("maintains") or "").strip(),
                # Más allá de las semanas firmes el macrociclo es un mapa. Ver
                # SEMANAS_FIRMES en season_planner_node.py.
                "provisional": int(phase.get("start_week") or 0) > _semanas_firmes(),
            }
    # Date falls beyond the last defined phase (season plan didn't cover this
    # far, or ran long) — still return the week number, just no phase match.
    return {"week_number": week_number, "phase_name": None, "is_deload": False,
            "weekly_hours_target": None, "weekly_load_target": None, "intensity_emphasis": None,
            "intensity_distribution": None, "hard_session_share_max": None, "focus": [], "maintains": "",
            "strength_quality": None, "provisional": False}


def _semanas_firmes() -> int:
    from services.ai.langgraph.nodes.season_planner_node import SEMANAS_FIRMES

    return SEMANAS_FIRMES


# How far ABOVE an athlete's already-established load a single week may reach.
# Deliberately a conservative PARAMETER, not a law: the "10% rule" is widely
# repeated in running lore but its direct evidence is weak -- Buist et al.
# (2008), a randomised trial of a 13-week graded 10%-per-week programme against
# a standard programme in novice runners, found no reduction in running
# injuries. It is used here only as a default ceiling on progression BEYOND
# what the athlete is already absorbing, and any per-phase weekly_hours_target
# the season plan declares overrides it.
_DEFAULT_RAMP_ALLOWANCE = 0.10

# A deload is a deliberate reduction, so on those weeks the target moves DOWN
# from baseline instead of up. Kept in step with the season planner's own
# instruction ("~40% less volume than the weeks around it", see
# SEASON_PLANNER_USER_PROMPT) so the two layers cannot disagree.
_DELOAD_FRACTION = 0.60

# A partir de cuántas semanas el objetivo de horas de una fase deja de hablar
# del atleta que hay hoy.
#
# Ocho son dos mesociclos: tiempo de sobra para que la base real haya cambiado,
# y poco como para que un macrociclo recién hecho no se cuestione a sí mismo en
# su segunda semana. No caduca nada ni cambia ningún número por su cuenta: sólo
# decide cuándo el prompt le pone la EDAD encima al tope, para que la decisión
# se tome con datos de hoy en vez de heredarse.
_PHASE_TARGET_STALE_WEEKS = 8


# Cuántos días antes de una carrera empieza a notarse su afinamiento, por
# prioridad. No es una prescripción de taper -- cuánto bajar lo decide el
# entrenador --, es sólo cuándo el promedio de la fase deja de describir la
# semana. Una A se afina dos semanas y pico; una B, la semana previa; una C es
# una carrera de entrenamiento y sólo toca la propia semana.
_VENTANA_DE_AFINAMIENTO = {"A": 20, "B": 10, "C": 6}

# Los días FIJO son rutina, y un afinamiento no es rutina (2026-09-13).
#
# Semana del 5K Monterrico del atleta 2: natación miércoles y viernes y fuerza
# lunes, martes y jueves marcados como fijos -- 3,5 de las 7,4 h de la semana --,
# y el validador exige cada fijo. El racional decía "bajamos el volumen" con una
# carga estimada de 362 contra una crónica de 291. En la ventana de afinamiento
# de cada carrera (la misma `_VENTANA_DE_AFINAMIENTO`) los fijos pasan a posibles
# y el validador deja de pedirlos: ver `disponibilidad_para_validar`.
#
# La evidencia (Bosquet 2007) es bajar el volumen un 41-60 % manteniendo la
# intensidad y la frecuencia DEL DEPORTE DE LA CARRERA; lo accesorio puede
# quitarse. La primera versión decía "acortar suele ser mejor que quitar" y la
# semana de referencia recortó todo un 20 % sin quitar nada.
#
# Y el número de fuerzas que pide un profesional (2026-09-14): el atleta 2 tiene
# "3 sesiones de fuerza" de su nutricionista, y el prompt dice que ese número
# es el objetivo "igual que un día fijo". Si el fijo cede en afinamiento, esa
# petición también, y hay que decirlo: si no, gana la frase que no cede.
_NOTA_DE_AFINAMIENTO = (
    "- **Es semana de afinamiento, así que sus días FIJO pasan a POSIBLE.** El atleta los marcó por rutina, y "
    "un afinamiento no es rutina: es la puesta a punto para competir. Puedes acortar, espaciar o quitar una "
    "sesión fija -- también la fuerza o la natación, y también si una restricción del macrociclo nace de esa "
    "rutina -- cuando la puesta a punto lo pide. No es un error ni el validador lo marcará; di en "
    "`week_rationale` qué acortaste o quitaste y por qué. Lo que sostiene la evidencia (Bosquet 2007; en "
    "corredores de 5K, Houmard y Shepley con siete días) es bajar mucho el VOLUMEN -- del orden del 40-60 % en "
    "ese tramo si la carrera se va a correr a tope -- manteniendo la INTENSIDAD con toques cortos a ritmo de "
    "carrera. La frecuencia que hay que conservar es la del deporte de la carrera; lo accesorio para esa "
    "carrera (fuerza, y bici o natación si es a pie) puede bajar más o quitarse, porque suma fatiga sin sumar "
    "frescura. Lo mismo vale para un número de sesiones de fuerza que pida un profesional: una semana de "
    "carrera puede llevar menos, y se dice. Quitar una sesión entera también es bajar volumen, a veces mejor "
    "que acortarlas todas. Cuánto dura depende de la carrera: los últimos 7-10 días para un 5K o un 10K, unas dos semanas "
    "para una media maratón o un olímpico, dos o tres para un maratón o una larga distancia; menos si es "
    "secundaria y no se va a correr a tope. Si la carrera está a más días que eso, esta semana se entrena "
    "normal y sólo se cuida el final."
)


def _dias_mal_en_la_decision(week_intent, week_dates: list) -> list[str]:
    """Los "día dd/mm" que no cuadran en el texto que el atleta lee de esta decisión."""
    from services.ai.number_audit import dias_mal_nombrados

    try:
        referencia = date.fromisoformat(str(week_dates[0]["date"])[:10])
    except (IndexError, KeyError, TypeError, ValueError):
        return []
    texto = " ".join([week_intent.week_rationale or ""] + [
        f"{d.purpose or ''} {d.adaptation or ''} {getattr(d, 'session_sketch', '') or ''}" for d in week_intent.days])
    return dias_mal_nombrados(texto, referencia)


def mensaje_de_correccion(fuera: list[dict], decision_json: str, totales: list[dict] | None = None,
                          carga: str = "", dias_mal: list[str] | None = None) -> str:
    """Lo que se le devuelve a la decisión cuando pone sesiones donde el atleta no puede,
    o una natación cuyo total no es lo que suman sus series.

    Una vuelta, con el mismo modelo y su contexto: mover una sesión es criterio
    (a qué día, si se cambia de deporte o se quita), y la red de después sólo sabe
    convertirla en descanso. Ver `plan_validator.intenciones_fuera_de_la_tabla` y
    `plan_validator.totales_que_no_suman`.
    """
    partes = []
    if fuera:
        partes.append(_lineas_de_disponibilidad(fuera))
    if totales:
        partes.append(
            "Estas nataciones declaran un total que sus series no suman (el reloj recibe las series):\n"
            + "\n".join(f"- {t.get('date')} «{t.get('focus') or 'natación'}»: escribiste un total de "
                        f"{t.get('declarado_m')} m y las series suman {t.get('suman_m')} m." for t in totales)
            + "\nAjusta las series para que sumen el total que querías (si es lo que pidió el atleta, manda el "
            "total), o corrige el total, y que `week_rationale` diga lo que de verdad lleva la semana.")
    if carga:
        partes.append(carga)
    if dias_mal:
        partes.append(
            "Estas fechas no cuadran con su día de la semana en lo que lee el atleta: " + "; ".join(dias_mal)
            + ". Escribe cada fecha copiando su `se_escribe` de \"This Week's Dates\" o de las carreras; "
            "si una fecha no lo trae, escríbela sin el día.")
    return (
        "\n\n".join(partes)
        + "\n\nDevuelve la semana COMPLETA otra vez. No cambies lo demás.\n\nTu decisión anterior:\n```json\n"
        + decision_json + "\n```"
    )


def datos_de_carga(state: dict, load_target: dict | None, dias_del_bloque: int, peticiones: str) -> dict:
    """Lo que `carga_segura.revisar_la_carga` necesita, sacado del estado de la semana."""
    restricciones = state.get("availability_constraints") or {}
    horas = ((restricciones.get("volume_baseline") or {}).get("chronic_hours_by_kind") or {}).get("resistencia")
    return {
        "tasas": (load_target or {}).get("rates") or None,
        "cronica": (load_target or {}).get("chronic"),
        "horas_de_resistencia": horas,
        "carrera_mas_larga_min": (restricciones.get("historial_de_carga") or {}).get("carrera_mas_larga_30d_min"),
        "dias_del_bloque": dias_del_bloque,
        "deportes_pedidos": cs.deportes_de_las_peticiones(peticiones),
    }


def _lineas_de_disponibilidad(fuera: list[dict]) -> str:
    lineas = []
    for v in fuera:
        que = f"«{v.get('focus') or v.get('sport')}» ({v.get('sport')})"
        # Una carrera no se mueve: "va a otro día" convirtió el trail del domingo de Hugo en
        # una simulación el sábado, la víspera (2026-10-09). Hoy su día ya está disponible;
        # sólo llega aquí si ESTA semana dijo que no puede ese día, y entonces no la corre.
        if v.get("intensity_class") == "race_day":
            lineas.append(f"- {v.get('day_name_es')} {v.get('date')}: pusiste {que}, su carrera, y esta semana el "
                          "atleta dijo que ese día NO puede: no la corre. Quítala y NO la muevas a otro día ni "
                          "la conviertas en una simulación; el resto de la semana ya no es de afinamiento para ella.")
            continue
        if v.get("type") == "day_not_available":
            lineas.append(f"- {v.get('day_name_es')} {v.get('date')}: pusiste {que} y ese día el atleta NO puede "
                          "entrenar.")
        else:
            lineas.append(f"- {v.get('day_name_es')} {v.get('date')}: pusiste {que} y ese día su disponibilidad "
                          f"sólo permite: {', '.join(v.get('permitidos') or [])}.")
    return (
        "Tu decisión rompe la disponibilidad del atleta (CRITICAL SCHEDULING RULES):\n" + "\n".join(lineas)
        + ("\nCada una de las demás sesiones" if any(v.get("intensity_class") == "race_day" for v in fuera)
           else "\nCada una de esas sesiones")
        + (" va a otro día de esta misma semana cuya disponibilidad permita ese deporte, "
           "o cambia a un deporte que ese día sí permita, o se quita si de verdad no cabe: decídelo con el mismo "
           "criterio con el que escribiste la semana, y si algo se mueve o se quita, que `week_rationale` lo diga.")
    )


def disponibilidad_para_validar(availability: dict, *objetivos: dict | None) -> dict:
    """La disponibilidad que ve el validador: sin los fijos en afinamiento.

    Se le pasan los objetivos de la semana (carga u horas); si cualquiera cae en
    el afinamiento de una carrera, `sport_requested_missing` no tiene nada que
    exigir. Devuelve una copia: el estado no se toca.
    """
    salida = dict(availability or {})
    if any((o or {}).get("race_taper") for o in objetivos):
        salida["sport_days_fijos"] = {}
    return salida


# Aquí estaba `contexto_de_la_semana`, que reescribía con una expresión regular
# las dos frases de `server.py` que exigen los FIJO cuando la semana es de
# afinamiento. Era el parche de que el contexto se escribiera sin saber qué semana
# era; ahora `server.build_custom_planning_context(profile, afinamiento)` las
# escribe ya como valen (2026-09-14).


def posicion_sin_doble_recorte(posicion: dict | None, *objetivos: dict | None) -> dict | None:
    """La posición en el macrociclo, sin "semana de descarga" cuando es semana de carrera.

    Los dos macrociclos regenerados el 2026-09-14 marcaron como `deload_weeks`
    las semanas de sus carreras (la atleta 3: semanas 1, 2, 10, 30 y 31). La
    semana recibía a la vez "Deload week: Yes — reduce volume ~40%" y la nota de
    afinamiento, que ya dice cuánto bajar: dos recortes de la misma semana por
    dos caminos. El afinamiento manda; la copia no toca la posición original.
    """
    if not posicion or not posicion.get("is_deload"):
        return posicion
    if any((o or {}).get("race_taper") for o in objetivos):
        return {**posicion, "is_deload": False}
    return posicion


def _ritmo_de_la_carrera(carrera: dict) -> str:
    """El ritmo medio que pide el objetivo del atleta, o "" si no se puede decir.

    La semana del 5K de la atleta 3 (2026-09-14) mandó 6x400 m "a ritmo de
    repeticiones/5K (4:54-5:16/km)" con el propósito de "practicar el pacing":
    su objetivo, 26:00, pide 5:12/km, y Garmin le predice 5:26. La semana no
    recibía el ritmo de la carrera -- sólo las bandas de entrenamiento --, así
    que ensayó un ritmo que no iba a correr.

    Sólo carreras de un deporte que se miden por ritmo: en una de montaña manda
    el desnivel, y en bici la velocidad media no es algo que se ensaye.
    """
    from services.ai.goal_realism import parse_time_to_seconds
    from services.ai.race_distances import is_elevation_driven, legs_of

    tramos = legs_of(carrera.get("distance"))
    if len(tramos) != 1 or is_elevation_driven(carrera.get("distance")):
        return ""
    # El lector único de tiempos, con la distancia: "3:30" de maratón son horas.
    # Aquí se partía por ":" a mano, así que ese objetivo pedía 0:05/km -- el
    # mismo fallo que se arregló en `goal_realism` el 2026-09-15, en otro sitio.
    segundos = parse_time_to_seconds(carrera.get("target_time"), carrera.get("distance"))
    deporte, metros = tramos[0]
    if not segundos or not metros:
        return ""
    if deporte == "run":
        por, unidad = round(segundos / (metros / 1000)), "/km"
    elif deporte == "swim":
        por, unidad = round(segundos / (metros / 100)), "/100 m"
    else:
        return ""
    return f"{por // 60}:{por % 60:02d}{unidad}"


def _escala_del_afinamiento(carrera: dict, horas_normales: float | None) -> str:
    """Lo que "40-60 % menos" significa para ESTE atleta, cuando la carrera cae en esta semana.

    Semana intermedia del atleta 2 del 2026-09-14, a 6 días de su 5K: 375 minutos
    (6,25 h) contra sus 6,6 h habituales, un 5 % menos, con un racional que decía
    "bajamos el volumen". La nota ya daba el porcentaje de la evidencia; al
    modelo le faltaba la cuenta hecha y la costumbre de sumar antes de responder.
    La de la atleta 3, con el mismo prompt, bajó un 38 %.

    Es una ESCALA, dicha como rango y sin negrita, no una cuota: una carrera B
    que no se corre a tope baja menos, y la nota lo dice. Sólo cuando la carrera
    cae dentro de esta semana; si cae en la siguiente, esta se entrena normal.
    """
    if not horas_normales or int(carrera.get("days_from_week_start") or 99) > 6:
        return ""
    return (
        f"- Para este atleta, una semana normal son ~{horas_normales:.1f} h; bajar un 40-60 % deja "
        f"~{horas_normales * 0.4:.1f}-{horas_normales * 0.6:.1f} h en la semana, con la carrera dentro. Es la "
        "escala de la evidencia, no una cuota: si quedas fuera, di por qué en `week_rationale`. Antes de "
        "responder, suma los minutos de las sesiones que decidiste y compáralos con ella; un racional que dice "
        "que bajaste el volumen tiene que ser verdad en esa suma."
    )


def _linea_de_carrera(c: dict) -> str:
    linea = (
        f"- Hay carrera: {c['name']} (prioridad {c['priority']}) el {c['date']}, a "
        f"{c['days_from_week_start']} días del inicio de esta semana. El número de la fase describe una "
        "semana normal, no una de afinamiento."
    )
    ritmo = _ritmo_de_la_carrera(c)
    if ritmo:
        objetivo = str(c.get("target_time") or "").strip()
        objetivo = objetivo[3:] if objetivo.startswith("00:") else objetivo
        linea += (
            f" Su objetivo ({objetivo}) pide un ritmo medio de {ritmo}: si una sesión de esta semana es para "
            "ensayar el ritmo de carrera, es ése; las bandas de la ficha son rangos de entrenamiento, no el "
            "ritmo de esta carrera."
        )
    return linea


# La distancia entre lo que la fase describe y lo que el atleta absorbe hoy
# (2026-09-14). El caso: un Ironman con un atleta de 6 h, un macrociclo que
# sube a 12 y un atleta que sigue haciendo 6. La semana recibía "Target weekly
# hours for this phase: ~12h" sin decir nada más: o saltaba al número de la
# fase, o se quedaba en 6 sin que nadie dijera que el mapa ya no describe al
# atleta. Ninguna de las dos es de entrenador.
_UMBRAL_DE_DISTANCIA = 1.15
_DISTANCIA_A_LA_FASE = (
    "- Esa distancia se recorre subiendo desde lo que hoy absorbe, al ritmo al que responde, no saltando al "
    "número de la fase. Si pasan las semanas y su volumen real no se acerca al de la fase —por tiempo, por "
    "recuperación o porque no hace lo prescrito—, eso es información, no algo que corregir apretando: "
    "planifica desde su realidad, dilo en `week_rationale` y di que el macrociclo necesita revisarse."
)


def carrera_en_afinamiento(competitions: list | None, week_dates: list | None) -> dict | None:
    """La carrera cuyo afinamiento cae en esta semana, o None.

    ## El fallo que esto arregla, con los números reales

    Macrociclo del atleta 2, 2026-09-10: Fase 1 de las semanas 1 a 4 a
    **6.0 h/semana**, con el 5k Monterrico (B) el 20-sep, en la semana 2. El
    propio texto del macrociclo decía "enfocada en el taper y ejecución del 5k".
    Pero `weekly_hours_target` es UN número por fase, y este código se lo
    entregaba a la semana 1 como "Objetivo: **6.0h** ... Manda el plan de
    temporada". El planificador escribió en su racional *"estamos en Fase 1 con
    objetivo de ~6.0h; la semana queda en ~5h55"* sabiendo que estaba *"a 4-9
    días del 5k Monterrico"*: rellenó hasta el número. El codificador sumó
    6.8 h, el validador lo cazó, y se pagó una reparación completa.

    El promedio de una fase describe una semana NORMAL de esa fase. Una semana
    de afinamiento no lo es, y el código era el único que no lo sabía.
    """
    if not competitions or not week_dates:
        return None
    try:
        inicio = date.fromisoformat(str(week_dates[0]["date"])[:10])
    except (KeyError, TypeError, ValueError):
        return None
    mejor = None
    for comp in competitions:
        if not isinstance(comp, dict):
            continue
        try:
            dia = date.fromisoformat(str(comp.get("date") or "")[:10])
        except ValueError:
            continue
        prioridad = str(comp.get("priority") or "B").strip().upper()[:1] or "B"
        ventana = _VENTANA_DE_AFINAMIENTO.get(prioridad, _VENTANA_DE_AFINAMIENTO["B"])
        faltan = (dia - inicio).days
        if not 0 <= faltan <= ventana:
            continue
        candidata = {"name": comp.get("name") or "competencia", "date": dia.isoformat(),
                     "priority": prioridad, "days_from_week_start": faltan,
                     "distance": comp.get("distance"), "target_time": comp.get("target_time")}
        # La más importante; entre iguales, la más cercana.
        if mejor is None or (prioridad, faltan) < (mejor["priority"], mejor["days_from_week_start"]):
            mejor = candidata
    return mejor


def carreras_vistas_desde_la_semana(competitions: list | None, week_dates: list | None,
                                    season_phases: dict | None = None) -> list:
    """Cada carrera con dónde cae respecto a la semana que se escribe, contado por el código.

    La semana del atleta 2 del 2026-10-05 recibía sus carreras sólo con la fecha: el 5K del
    2026-09-20 (ya corrido) sin marcar, y la gravel del 2026-10-24 sin decir que caía en la
    semana 5 de su macrociclo, a dos semanas de la que escribía (la 3). Contar las semanas
    le tocaba al modelo, y es el fallo que el macrociclo ya tuvo (`con_semana_del_macrociclo`).

    Devuelve copias: `ya_corrida` (con `hace_dias`), `en_esta_semana` (con su día) o
    `dias_desde_el_inicio_de_esta_semana`; con macrociclo, además `semana_del_macrociclo` y
    `semanas_que_faltan` (0 = esta semana). Una fecha ilegible va tal cual.
    """
    if not competitions:
        return competitions or []
    try:
        inicio = date.fromisoformat(str(week_dates[0]["date"])[:10])
    except (IndexError, KeyError, TypeError, ValueError):
        return competitions
    dias_de_la_semana = {str(d.get("date"))[:10]: d.get("day_name_es") or d.get("day_name")
                         for d in week_dates if isinstance(d, dict)}
    arranque = None
    try:
        arranque = date.fromisoformat(str((season_phases or {}).get("season_start_date") or "")[:10])
    except ValueError:
        pass

    def semana_de(dia: date) -> int | None:
        return (dia - arranque).days // 7 + 1 if arranque and dia >= arranque else None

    salida = []
    for comp in competitions:
        try:
            dia = date.fromisoformat(str(comp.get("date") or "")[:10])
        except (AttributeError, ValueError):
            salida.append(comp)
            continue
        dias = (dia - inicio).days
        c = dict(comp)
        if dias < 0 and dia >= date.today():
            # Antes de esta semana pero todavía por correr (2026-10-22): la semana del lunes 26 se generó el jueves
            # 22 y el gravel del sábado 24 llegaba como `ya_corrida`; el coach preguntó si ya la había corrido.
            c.update(antes_de_esta_semana=True, todavia_por_correr=True, dias_antes_de_la_semana=-dias)
        elif dias < 0:
            c.update(ya_corrida=True, hace_dias=-dias)
        elif dia.isoformat() in dias_de_la_semana:
            c.update(en_esta_semana=True, dia=dias_de_la_semana[dia.isoformat()])
        else:
            c["dias_desde_el_inicio_de_esta_semana"] = dias
        if dias >= 0 and semana_de(dia) and semana_de(inicio):
            c["semana_del_macrociclo"] = semana_de(dia)
            c["semanas_que_faltan"] = semana_de(dia) - semana_de(inicio)
        salida.append(c)
    return salida


# Lo que pasa DESPUÉS de una carrera (2026-09-15).
#
# La semana del atleta 2 del 21 al 27 de septiembre empezaba el día siguiente
# a su 5K corrido a tope, y sumó ~8,4 h (505') contra 7,1 h de una semana
# normal suya: tres fuerzas, dos bicis, tempo y tirada de 70', con un racional
# que decía "la prioridad es recuperar". El código sabía lo que era la semana
# ANTES de una carrera (`carrera_en_afinamiento` exige que la carrera caiga
# después del inicio de la semana) y nada de la de después: para él era una
# semana normal de la fase, con la base de volumen tomada de "lo mayor entre la
# crónica y lo planificado", y la semana anterior presentada como anclaje. El
# modelo "volvía a la estructura" y rellenaba lo que el afinamiento quitó.
#
# Cuántos días fáciles: no hay un número probado, y no se inventa uno. La regla
# práctica que usan los entrenadores de fondo es la de Jack Daniels, un día
# fácil por cada 3 km de carrera, y sólo tiene sentido en carreras a pie; en
# multideporte, natación o bici se dice la situación y decide el entrenador.
_KM_POR_DIA_FACIL = 3
_VENTANA_DE_RECUPERACION_MIN = 7
_VENTANA_DE_RECUPERACION_MAX = 21


def carrera_reciente(competitions: list | None, week_dates: list | None) -> dict | None:
    """La carrera que terminó justo antes de que empiece esta semana, o None.

    Ventana: los días fáciles de la regla de Daniels, y como poco una semana.
    Entre varias, la más reciente.
    """
    if not competitions or not week_dates:
        return None
    try:
        inicio = date.fromisoformat(str(week_dates[0]["date"])[:10])
    except (KeyError, TypeError, ValueError):
        return None
    from services.ai.goal_realism import DISTANCE_LABELS_ES, infer_distance
    from services.ai.race_distances import is_multisport, legs_of

    mejor = None
    for comp in competitions:
        if not isinstance(comp, dict):
            continue
        try:
            dia = date.fromisoformat(str(comp.get("date") or "")[:10])
        except ValueError:
            continue
        hace = (inicio - dia).days
        if hace < 1:
            continue
        clave, _origen = infer_distance(comp)
        dias_faciles = None
        if clave and not is_multisport(clave):
            km_a_pie = sum(m for deporte, m in legs_of(clave) if deporte == "run") / 1000.0
            if km_a_pie:
                dias_faciles = max(2, round(km_a_pie / _KM_POR_DIA_FACIL))
        ventana = min(_VENTANA_DE_RECUPERACION_MAX, max(_VENTANA_DE_RECUPERACION_MIN, dias_faciles or 0))
        if hace > ventana:
            continue
        candidata = {
            "name": comp.get("name") or "competencia", "date": dia.isoformat(),
            "priority": str(comp.get("priority") or "B").strip().upper()[:1] or "B",
            "days_since": hace, "distance": DISTANCE_LABELS_ES.get(clave or "", clave),
            "easy_days": dias_faciles,
        }
        if mejor is None or hace < mejor["days_since"]:
            mejor = candidata
    return mejor


def _nota_de_recuperacion(carrera: dict, horas_normales: float | None) -> str:
    """La semana de después de una carrera, dicha como lo que es. Sin cuota."""
    dias = ""
    if carrera.get("easy_days"):
        quedan = max(0, carrera["easy_days"] - carrera["days_since"] + 1)
        dias = (
            f"La regla práctica de corredores de fondo (Jack Daniels, orientativa, no una ley) es un día fácil "
            f"por cada 3 km de carrera: para esta, unos {carrera['easy_days']} días desde la carrera, así que "
            f"{'le quedan ~' + str(quedan) + ' en esta semana' if quedan else 'ya se cumplieron antes de esta semana'}. "
        )
    normal = f" (~{horas_normales:.1f} h)" if horas_normales else ""
    return (
        f"- **Esta semana empieza {carrera['days_since']} día(s) después de {carrera['name']}** "
        f"({carrera['date']}, prioridad {carrera['priority']}"
        + (f", {carrera['distance']}" if carrera.get("distance") else "")
        + "). No es una semana de construcción: después de competir a tope, el cuerpo necesita días fáciles antes "
        "de volver a la calidad, y lo que se quitó en el afinamiento NO se recupera ahora. "
        + dias
        + "En esos días no hay calidad y la fuerza va ligera. El volumen de la semana no pasa del de una semana "
        f"normal suya{normal} y puede quedarse por debajo; sus días FIJO se pueden acortar y hacer suaves. Si la "
        "carrera no se había corrido todavía cuando se genera esta semana, planifica igual: la semana va después "
        "de ella. Di en `week_rationale` cómo la encajaste."
    )


def compute_weekly_volume_target(
    volume_baseline: dict | None,
    mesocycle_position: dict | None,
    competitions: list | None = None,
    week_dates: list | None = None,
) -> dict | None:
    """This week's prescribed training volume in hours, derived from what this
    specific athlete is really absorbing -- not from a fixed rule applied to
    everyone.

    Plan Maestro v4 Track F. Returns None when there is nothing real to anchor
    to (a new athlete with no Garmin history and no season plan), in which case
    callers keep their previous behaviour rather than inventing a number.

    Two properties matter far more here than the exact arithmetic:

    1. **Baseline is the MAXIMUM of chronic load and last week's plan, never
       last week alone.** Capping at "last week + 10%" right after a deload, a
       taper, an illness or a missed week would pin the athlete below a volume
       they had already demonstrated they tolerate -- and because next week's
       cap would then be computed from *that* reduced number, the error
       compounds downward every week. Returning to an established baseline is
       resumption, not progression, and is not what a ramp cap exists to
       prevent. (This is the exact case that motivated Track F: a real
       generation went 3.6h -> 6.0h, which reads as a reckless +68% jump only
       if you ignore that this athlete's established load is ~6h/week.)
    2. **A lower per-phase target from the season plan wins.** That is a
       deliberate coaching decision about this specific block (e.g. a
       stabilisation phase after a load spike) and must not be overridden by a
       generic progression allowance.
    """
    baseline_data = volume_baseline or {}
    position = mesocycle_position or {}

    baseline_candidates = [
        hours for hours in (
            baseline_data.get("chronic_weekly_hours"),
            baseline_data.get("previous_week_planned_hours"),
        )
        if hours
    ]
    phase_target = position.get("weekly_hours_target")
    is_deload = bool(position.get("is_deload"))
    # La semana de temporada en la que estamos ES la edad del macrociclo: se
    # calcula desde `season_start_date`, que es el día en que se escribió.
    semanas_del_plan = max(0, int(position.get("week_number") or 1) - 1)

    if not baseline_candidates and not phase_target:
        return None

    baseline = max(baseline_candidates) if baseline_candidates else phase_target

    if is_deload:
        target = baseline * _DELOAD_FRACTION
        rationale = (
            f"Es una semana de descarga: el objetivo baja a ~{int(_DELOAD_FRACTION * 100)}% de la base "
            f"({baseline:.1f}h/semana) a proposito, para asimilar la carga de las semanas anteriores."
        )
    else:
        ramp_ceiling = baseline * (1 + _DEFAULT_RAMP_ALLOWANCE)
        if phase_target and phase_target < ramp_ceiling:
            target = phase_target
            rationale = (
                f"El plan de temporada fija ~{phase_target:.1f}h/semana para esta fase, por debajo de lo "
                f"que la progresion permitiria ({ramp_ceiling:.1f}h). Manda el plan de temporada."
            )
            # Un macrociclo VIEJO cuyo tope se ha quedado por debajo de lo que
            # el atleta ya absorbe deja de ser una decision de entrenador
            # sobre este bloque y pasa a ser una foto de otro atleta: el que
            # era hace cuatro meses. La ficha de ritmos se recalcula cada
            # semana con datos frescos, pero este numero se fijo el dia 1 y
            # nadie lo volvia a mirar -- asi que en la semana 20 podia estar
            # frenando a alguien que ya habia crecido, sin decirselo a nadie.
            #
            # No se sobreescribe en codigo: se le pone la EDAD encima y se le
            # da permiso explicito al entrenador para pasarse hasta el techo
            # de progresion si lo justifica. Es el mismo patron que ya usan
            # las restricciones de temporada, que llevan fecha de revision y
            # dejan de mandar cuando vence.
            if semanas_del_plan >= _PHASE_TARGET_STALE_WEEKS and baseline > phase_target:
                rationale += (
                    f" **Ese tope se escribio hace {semanas_del_plan} semanas**, cuando la base real del "
                    f"atleta era otra: hoy es {baseline:.1f}h/semana, por encima del tope. No des por "
                    f"bueno ninguno de los dos numeros por defecto -- decide entre {phase_target:.1f}h y "
                    f"{ramp_ceiling:.1f}h con los datos de ESTA semana, y di en `week_rationale` cual "
                    "elegiste, con que dato del atleta y contra que hizo la semana pasada. "
                    "**Que el tope este viejo no es por si solo una razon para subir**: la recuperacion, "
                    "una molestia declarada, la adherencia real de la semana anterior y una semana de "
                    "descarga mandan por encima de esto, y si cualquiera de ellas pide menos, planifica "
                    f"menos de {phase_target:.1f}h. Si el macrociclo se ha quedado corto para el atleta "
                    "que es ahora, dilo tambien: es la senal de que toca rehacerlo."
                )
        else:
            target = ramp_ceiling
            rationale = (
                f"Base actual del atleta: {baseline:.1f}h/semana (lo mayor entre su carga cronica real de "
                f"Garmin y lo que se le prescribio la semana pasada). Se permite hasta un "
                f"+{int(_DEFAULT_RAMP_ALLOWANCE * 100)}% sobre esa base."
            )

    carrera = carrera_en_afinamiento(competitions, week_dates)
    if carrera:
        # El tope nunca por encima de una semana normal: ni de la base del
        # atleta ni de lo que la fase previo. Pero el numero deja de ser "lo
        # que toca" -- ver `carrera_en_afinamiento` y `_format_volume_target`.
        target = min(baseline, phase_target) if phase_target else baseline
        rationale = (
            f"**Esta semana cae en el afinamiento de {carrera['name']}** (prioridad "
            f"{carrera['priority']}, el {carrera['date']}, a {carrera['days_from_week_start']} dias "
            f"del inicio de la semana). "
            + (f"Los ~{phase_target:.1f}h de la fase describen una semana NORMAL de la fase, no esta. "
               if phase_target else "")
            + "Cuanto baja el volumen y como se reparte lo decides tu, con la carrera, su prioridad y "
            "la recuperacion del atleta delante; el tope de arriba solo dice que no se sube. Lo que "
            "quites de un dia no se recoloca en otro: eso deshace el afinamiento."
        )

    reciente = None if carrera else carrera_reciente(competitions, week_dates)
    if reciente:
        # Lo mismo que en el afinamiento, del otro lado de la carrera: la base
        # ("lo mayor entre crónica y lo planificado") no puede empujar hacia
        # arriba una semana de recuperación. Ver `carrera_reciente`.
        target = min(baseline, phase_target) if phase_target else baseline
        rationale = (
            f"**Esta semana va justo después de {reciente['name']}** ({reciente['date']}). Es de "
            "recuperación, no de construcción: el tope de arriba sólo dice que no sube por encima de una "
            "semana normal."
        )

    return {
        "target_hours": round(target, 1),
        "baseline_hours": round(baseline, 1),
        # Por separado, porque la base mezcla lo HECHO con lo PLANIFICADO: a un
        # atleta que sigue en 6 h con una semana planificada de 9, el bloque le
        # decía "según su historial: ~9 h".
        "chronic_hours": baseline_data.get("chronic_weekly_hours"),
        "hours_by_kind": baseline_data.get("chronic_hours_by_kind"),
        "previous_planned_hours": baseline_data.get("previous_week_planned_hours"),
        "phase_hours": phase_target,
        "is_deload": is_deload,
        "race_taper": carrera,
        "race_recovery": reciente,
        "rationale": rationale,
    }


def compute_weekly_load_target(
    volume_baseline: dict | None,
    mesocycle_position: dict | None,
    competitions: list | None = None,
    week_dates: list | None = None,
) -> dict | None:
    """Dónde está el atleta en carga y qué tipo de semana es. Sin techo.

    ## Por qué no calcula un techo

    La primera versión de la v1.2 calculaba uno (+10 % sobre la crónica,
    acotado por la fase y por el borde de Garmin) y el validador lo hacía
    cumplir acortando sesiones. Era sustituir el criterio del entrenador por un
    número: la crónica viene de la carga de Garmin, que depende de unas zonas y
    una FC máxima que a menudo están mal, y las tasas por hora con las que se
    estimaba una semana planificada tenían un ruido enorme (natación "suave" del
    atleta real: de 24 a 102 por hora). Lo que un atleta puede hacer con
    seguridad lo dice lo que ya ha hecho y cómo respondió, y eso lo lee el
    modelo, que lo tiene delante.

    Queda como referencia: la crónica, la carga reciente, el rango de Garmin
    dicho como lo que es, y si la semana es de afinamiento o de descarga.

    Devuelve None cuando no hay ninguna carga real: un atleta sin historial no
    recibe un número inventado.
    """
    b = volume_baseline or {}
    pos = mesocycle_position or {}
    cronica_garmin = b.get("garmin_chronic_load")
    cronica = cronica_garmin or b.get("chronic_weekly_load")
    if not cronica:
        return None
    carrera = carrera_en_afinamiento(competitions, week_dates)
    reciente = None if carrera else carrera_reciente(competitions, week_dates)
    if carrera:
        tipo = "afinamiento"
    elif reciente:
        tipo = "recuperacion"
    elif pos.get("is_deload"):
        tipo = "descarga"
    else:
        tipo = "normal"
    return {
        "chronic": round(float(cronica)),
        "chronic_source": "Garmin" if cronica_garmin else "la suma de sus actividades",
        "acute": b.get("garmin_acute_load") or b.get("last_7d_load"),
        "optimal_min": b.get("garmin_optimal_min"),
        "optimal_max": b.get("garmin_optimal_max"),
        "kind": tipo,
        "race_taper": carrera,
        "race_recovery": reciente,
        "phase_load": pos.get("weekly_load_target"),
        "normal_hours": b.get("chronic_weekly_hours"),
        "hours_by_kind": b.get("chronic_hours_by_kind"),
        # Solo para el registro de la carga estimada tras decidir: ver el
        # comentario sobre `weekly_hours_budget` en weekly_planner_node.
        "rates": b.get("load_rates") or {},
    }


def _lineas_de_horas(horas: dict | None, semana_normal: bool) -> list[str]:
    """Sus horas de resistencia y de fuerza por separado, y que la media no es un techo.

    Fernando, Beta 2.0 publicada, 2026-09-22: pidió en su cuaderno natación de 2-3 km y
    su semana le dio 1.200-1.800 m, con 7 h 55' en total contra una media de 8,1 h que
    mezclaba su gimnasio con su natación: la fuerza (130' esa semana) se comía los metros.
    Y el responsable, ese día: al empezar un plan la constancia sola suele sumar 1-2 h
    sobre la media reciente, y eso no es un pico que haya que frenar. Esa línea va sólo
    en una semana normal: en afinamiento, descarga o tras una carrera manda su nota.

    **El margen va anclado a SU media, con el número.** La primera versión decía "1-2 h
    sobre su media reciente" sin decir cuál, y reetiquetaba las ~7,5 h de la fase como
    horas sólo de resistencia (en un macrociclo viejo incluían la fuerza). Réplicas sobre
    la decisión real del atleta 2 del 2026-09-23 (gpt-5-mid, 0,07 USD cada una), horas de
    resistencia: 4,8 con el prompt de antes, 7,1 con aquella versión y sin ninguna
    petición -- +2,2 h a un atleta que la semana anterior hizo 1 de 4 sesiones.
    """
    lineas = []
    resistencia = (horas or {}).get("resistencia")
    if resistencia:
        fuerza = (horas or {}).get("fuerza")
        lineas.append(
            f"- Horas de una semana normal en su historial (4 semanas): ~{resistencia:.1f} h de natación, bici "
            "y carrera" + (f", y ~{fuerza:.1f} h de fuerza aparte" if fuerza else "")
            + ". La fuerza no resta horas a la resistencia: una sesión de gimnasio no le quita metros a "
            "su natación ni minutos a su bici."
        )
        if semana_normal:
            lineas.append(
                f"- Esas ~{resistencia:.1f} h dicen dónde está, no dónde tiene que quedarse: al empezar un plan, "
                "la constancia sola suele sumar 1-2 h de resistencia sobre ellas, y es normal. Es un margen, no "
                "un objetivo, y es UNO para toda la semana: lo que pide de su entrenamiento cabe en él, y si ya lo "
                "ocupa, los demás deportes se quedan cerca de lo suyo (ni menos, ni otro margen encima). Más allá "
                "sólo si lo que pidió lo exige."
            )
    return lineas


def _format_load_target(t: dict | None) -> str:
    """Dónde está el atleta en carga. Una REFERENCIA, no un techo ni una receta.

    La primera versión de la v1.2 decía "Techo de esta semana: **220**" en
    negrita y añadía "Cómo se planifica con esto, en este orden: 1. las
    sesiones clave según lo que el Load Focus dice que falta". Tres errores
    juntos, y el primero repetía el que la propia v1.2 había documentado con el
    taper: un número en negrita gana a cualquier matiz escrito debajo. El
    segundo convertía una etiqueta de Garmin -- que depende de unas zonas que
    pueden estar mal -- en el criterio para elegir sesiones. El tercero era una
    plantilla: le decía al modelo en qué orden pensar.

    Lo que queda son hechos con su fuente. Decidir qué significan es del
    entrenador, que tiene al lado la historia del atleta y cómo respondió.
    """
    if not t:
        return ""
    lineas = [
        "\n### Carga reciente (referencia de dónde está el atleta, no un objetivo)",
        f"- Carga crónica: {t['chronic']} (según {t['chronic_source']})"
        + (f"; últimos 7 días: {round(t['acute'])}." if t.get("acute") else "."),
    ]
    if t.get("optimal_min") and t.get("optimal_max"):
        lineas.append(
            f"- Garmin marca como rango óptimo {round(t['optimal_min'])}-{round(t['optimal_max'])} "
            "(0,8-1,5 veces su crónica). Es un cálculo de Garmin, no un límite probado en este atleta: "
            "lo que ha tolerado de verdad está en su historial y en cómo respondió."
        )
    # Cuánto le carga cada tipo de sesión. Se calculaban, se guardaban en
    # `load_rates.json` y sólo se usaban para anotar en el log la carga estimada
    # DESPUÉS de decidir: el modelo recibía la carga como moneda sin saber el
    # precio de nada. La semana del 5K del atleta 2 (2026-09-13) dijo "bajamos el
    # volumen" dos veces seguidas con una carga estimada de 362 y 367 contra una
    # crónica de 291 -- no tenía con qué comprobarlo.
    partes = []
    tasas = t.get("rates") or {}
    for clave, etiqueta in (("run:suave", "carrera suave"), ("run:calidad", "carrera de calidad"),
                            ("bike:suave", "bici suave"), ("bike:calidad", "bici de calidad"),
                            ("swim:suave", "natación suave"), ("swim:calidad", "natación de calidad")):
        valor = tasas.get(clave) or {}
        if valor.get("por_hora"):
            partes.append(f"{etiqueta} ~{valor['por_hora']}/h")
    if (tasas.get("fuerza") or {}).get("por_sesion"):
        partes.append(f"fuerza ~{tasas['fuerza']['por_sesion']} por sesión")
    if partes:
        lineas.append(
            "- Lo que le carga cada tipo de sesión, medido en su historial (mediana, orientativo): "
            + ", ".join(partes) + ". Sirve para que lo que digas de la carga de la semana sea verdad; "
            "no es un presupuesto que llenar."
        )
    if t["kind"] == "afinamiento":
        lineas.append(_linea_de_carrera(t["race_taper"]))
        lineas.append(_NOTA_DE_AFINAMIENTO)
        escala = _escala_del_afinamiento(t["race_taper"], t.get("normal_hours"))
        if escala:
            lineas.append(escala)
    elif t["kind"] == "recuperacion":
        lineas.append(_nota_de_recuperacion(t["race_recovery"], t.get("normal_hours")))
    elif t["kind"] == "descarga":
        lineas.append("- El macrociclo marca esta semana como de descarga.")
    elif t.get("phase_load"):
        lineas.append(
            f"- La fase del macrociclo describe una semana normal de ~{round(t['phase_load'])}; hoy su crónica "
            f"es {t['chronic']}."
        )
        if t["phase_load"] > t["chronic"] * _UMBRAL_DE_DISTANCIA:
            lineas.append(_DISTANCIA_A_LA_FASE)
    lineas.extend(_lineas_de_horas(t.get("hours_by_kind"), t["kind"] == "normal"))
    return "\n".join(lineas) + "\n"


def _format_volume_target(target: dict | None) -> str:
    """La referencia en HORAS, para el atleta sin datos de carga. Mismo criterio."""
    if not target:
        return ""
    lineas = ["\n### Volumen reciente (referencia de dónde está el atleta, no un objetivo)"]
    cronica = target.get("chronic_hours")
    if cronica:
        lineas.append(f"- Horas de una semana normal según su historial real: ~{cronica:.1f} h.")
    if target.get("previous_planned_hours"):
        lineas.append(
            f"- La semana anterior se le planificaron ~{target['previous_planned_hours']:.1f} h: es lo "
            "planificado, no lo hecho (lo hecho está en la adherencia)."
        )
    if len(lineas) == 1:
        lineas.append(f"- Horas de referencia: ~{target.get('baseline_hours', target['target_hours']):.1f} h.")
    carrera = target.get("race_taper")
    if carrera:
        lineas.append(_linea_de_carrera(carrera))
        lineas.append(_NOTA_DE_AFINAMIENTO)
        escala = _escala_del_afinamiento(carrera, cronica)
        if escala:
            lineas.append(escala)
    elif target.get("race_recovery"):
        lineas.append(_nota_de_recuperacion(target["race_recovery"], cronica))
    elif target.get("is_deload"):
        lineas.append("- El macrociclo marca esta semana como de descarga.")
    elif cronica and target.get("phase_hours") and target["phase_hours"] > cronica * _UMBRAL_DE_DISTANCIA:
        lineas.append(f"- La fase del macrociclo describe una semana normal de ~{target['phase_hours']:.1f} h.")
        lineas.append(_DISTANCIA_A_LA_FASE)
    normal = not (carrera or target.get("race_recovery") or target.get("is_deload"))
    lineas.extend(_lineas_de_horas(target.get("hours_by_kind"), normal))
    return "\n".join(lineas) + "\n"


def _minutos_de_los_pasos(pasos) -> float | None:
    """Lo que dura en el reloj una sesión escrita en tiempo. None si algún paso va por distancia."""
    total = 0.0
    for p in pasos or []:
        n = p.repeat_count or 1
        if p.step_type == "repeat":
            dentro = _minutos_de_los_pasos(p.substeps)
            if dentro is None:
                return None
            total += n * dentro
        elif p.duration_type == "time":
            total += n * (p.duration_value or 0) / 60
        else:
            return None
    return total


def pasos_que_no_suman_el_dia(dias, tolerancia: float = 0.05) -> list[str]:
    """La duración del día es la que suman sus pasos: es lo que el reloj va a hacer.

    Bici del atleta 2 del 2026-10-11: la cabecera decía 135 min y el reloj llevaba 144'
    (la decisión escribió "completa el tiempo restante" y el codificador restó mal), y el
    responsable lo vio en su perfil. Dos números para una sesión: la pantalla, la carga y
    el chat leían el de la decisión y el atleta corría el otro. La causa se arregló en el
    contrato de la decisión (bloques en orden, cada uno con sus minutos); esto deja una
    sola duración -- la de los pasos -- y anota en el `worker.log` cuando no cuadraban, sin
    pagar ninguna reparación. Sólo en sesiones escritas en tiempo: con distancias no hay
    minutos que sumar sin suponer un ritmo.

    **Sólo lo que no es un error de unidad.** El validador (`duration_mismatch`) detecta
    pasos que suman mucho MÁS que el día -- un `duration_value` en minutos donde van
    segundos, el 6 x 50' del banco del codificador del 2026-09-24 -- y lo devuelve a
    reparar. Igualar el día a esos pasos antes de validar le quitaría justo esa señal: un
    exceso que el validador ve se deja tal cual y sólo se anota.
    """
    avisos = []
    for d in dias:
        if d.is_rest or not d.steps or not d.total_duration_minutes:
            continue
        minutos = _minutos_de_los_pasos(d.steps)
        if not minutos or abs(minutos - d.total_duration_minutes) / d.total_duration_minutes <= tolerancia:
            continue
        if minutos > d.total_duration_minutes * _TOLERANCIA_DURACION + _HOLGURA_DURACION_MIN:
            avisos.append(f"{d.date} {d.sport}: los pasos suman {minutos:.0f}' y la decisión decía "
                          f"{d.total_duration_minutes:.0f}'; se deja al validador (¿unidad equivocada?)")
            continue
        avisos.append(f"{d.date} {d.sport}: los pasos suman {minutos:.0f}' y la decisión decía "
                      f"{d.total_duration_minutes:.0f}'; el día queda en {minutos:.0f}'")
        d.total_duration_minutes = round(minutos)
    return avisos


def _apply_discipline(week_block: WeekBlock, week_intent: WeekIntentContent) -> None:
    """Carry the coaching vocabulary across, and decide exportability in code.

    v5 Track 5. `discipline` is what the session IS; `sport` is only what
    Garmin's API can be told. The formatter is asked to copy the discipline,
    but this re-copies it from the decision layer regardless: a formatting call
    that quietly changed "pliometria" to "fuerza" would turn a session Garmin
    cannot hold into one it will happily accept and mangle.

    Whether a session survives the export is NOT asked of the model -- what a
    session is, is a coaching judgement; what our exporter can carry is a fact
    about our exporter.
    """
    by_key = {(d.date, d.session_slot): d for d in week_intent.days}

    for day in week_block.days:
        intent = by_key.get((day.date, day.session_slot)) or by_key.get((day.date, "unica"))
        if intent is not None:
            day.discipline = intent.discipline
            # El codificador ya no los escribe (2026-09-23, ver FormattedWorkoutDay): se
            # copian de quien los decidió. Un día que ya los trae (la fuerza de su rutina,
            # un descanso de la red) conserva los suyos.
            day.purpose = day.purpose or intent.purpose or ""
            day.adaptation = day.adaptation or intent.adaptation or ""

        day.garmin_exportable = is_garmin_exportable(
            day.discipline, day.sport, day.is_rest, day.steps, getattr(day, "strength_block", None))

        if day.is_rest or day.garmin_exportable:
            continue

        # The watch will not have this session, so this text IS the session.
        # Prefer what the formatter wrote, fall back to the coach's own sketch,
        # and never leave the athlete with nothing to read.
        body = day.manual_instructions.strip()
        if not body and intent is not None:
            body = (intent.session_sketch or "").strip()
        if not body:
            body = (day.purpose or "").strip()

        cues = (intent.execution_cues or "").strip() if intent is not None else ""
        if cues and cues not in body:
            body = f"{body}\n\nCómo ejecutarla: {cues}" if body else cues

        equipment = (intent.equipment or "").strip() if intent is not None else ""
        if equipment and equipment not in body:
            body = f"{body}\n\nMaterial: {equipment}"

        day.manual_instructions = body


def _format_constraints(season_phases: dict | None, week_dates: list | None) -> str:
    """The season plan's standing constraints that apply to THIS week.

    Filtered against the week's own first date, not against today: generating
    a week that starts next Monday must be judged by what applies then. Wrapped
    in blank lines because the prompt concatenates these blocks inline.
    """
    constraints = (season_phases or {}).get("constraints") or []
    if not constraints:
        return ""

    on_date = None
    if week_dates:
        try:
            on_date = datetime.strptime(week_dates[0]["date"], "%Y-%m-%d").date()
        except (ValueError, KeyError, TypeError):
            on_date = None

    block = render_constraints_block(constraints, on_date)
    return f"\n{block}\n" if block else ""


_TRAIL_HINTS = ("trail", "montaña", "montana", "sendero", "sky", "ultra", "vertical", "cerro", "volcán", "volcan")


def _is_trail_athlete(state: dict) -> bool:
    """Si este atleta corre o compite en montaña.

    Tres señales, cualquiera basta, porque cada una falta en un caso real:
    el terreno declarado (que mucha gente no rellena), una competición de
    montaña (que sólo existe si ya se apuntó a una), y la disciplina de trail
    en el contexto de planificación. Deliberadamente generosa: darle la guía a
    quien no la necesita cuesta unos tokens; no dársela a quien sí, le da un
    plan de asfalto para correr en el monte.
    """
    piezas = [str(state.get("planning_context") or "")]
    for competicion in (state.get("competitions") or []):
        if isinstance(competicion, dict):
            piezas.extend(str(competicion.get(k) or "") for k in ("name", "race_type", "distance"))
    texto = " ".join(piezas).lower()
    return any(pista in texto for pista in _TRAIL_HINTS)


_PISTAS_DE_NATACION = ("nadar", "natación", "natacion", "swim", "triatl", "triathl", "aguas abiertas",
                       "acuatl", "piscina")


def _nada(state: dict) -> bool:
    """Si este atleta nada: su tabla, su contexto o una competición con agua. Generosa como `_is_trail_athlete`:
    dársela a quien no nada cuesta unos tokens; no dársela a quien nada repite siempre los mismos drills."""
    piezas = [str(state.get("planning_context") or "")]
    for competicion in (state.get("competitions") or []):
        if isinstance(competicion, dict):
            piezas.extend(str(competicion.get(k) or "") for k in ("name", "race_type", "distance"))
    texto = " ".join(piezas).lower()
    return any(pista in texto for pista in _PISTAS_DE_NATACION)


def _format_mesocycle_position(position: dict | None) -> str:
    if not position:
        return ""
    if not position["phase_name"]:
        return f"\n### Real Mesocycle Position (computed, not guessed)\nThis is week {position['week_number']} of the season — beyond the season plan's defined phases (no phase data for this week).\n"
    deload_line = "Yes — reduce volume ~40% vs. surrounding weeks." if position["is_deload"] else "No."
    # "Target weekly hours" era la palabra equivocada: es la agenda de una semana
    # NORMAL de la fase, y el bloque de carga dice dónde está el atleta hoy.
    target_line = (
        f" Weekly hours of a normal week in this phase (agenda reference, not a target): "
        f"~{position['weekly_hours_target']}h." if position["weekly_hours_target"] else ""
    )
    # La EDAD del macrociclo, que es la misma cuenta que la semana de temporada.
    #
    # El plan de temporada se escribe una vez y luego gobierna meses. La ficha
    # de ritmos se recalcula cada semana con datos frescos de Garmin, así que
    # los ritmos nunca son viejos -- pero la INTENCIÓN de la fase y su tope de
    # horas se fijaron el día 1, y nada se lo decía al planificador semanal.
    # Decirlo no invalida el plan: le pone su fecha, que es lo que permite
    # leerlo como lo que es.
    edad = max(0, int(position["week_number"]) - 1)
    edad_line = (
        f" Este macrociclo se escribió hace **{edad} semana(s)**: su intención sigue mandando, pero "
        "cualquier NÚMERO suyo hay que leerlo contra lo que el atleta demuestra hoy más arriba "
        "(carga real, recuperación, adherencia de la semana pasada)."
        if edad else " Este macrociclo se acaba de escribir."
    )
    # v1.2.2. El reparto de la fase y en qué se concentra. El techo se dice como
    # TECHO en la misma frase que su número: un número suelto se lee como
    # objetivo, y lo que esto vigila es justo la calidad metida por cuota.
    reparto_line = ""
    partes = []
    if position.get("intensity_distribution"):
        partes.append(f"reparto {position['intensity_distribution']}")
    if position.get("hard_session_share_max") is not None:
        partes.append(
            f"como mucho ~{round(position['hard_session_share_max'] * 100)} % de sus sesiones de resistencia "
            "por encima del primer umbral — un techo, no una cuota: puede haber menos, o ninguna"
        )
    if partes:
        reparto_line = " Intensidad de la fase: " + "; ".join(partes) + "."
    foco_line = ""
    if position.get("focus"):
        foco_line = f" La fase concentra en: {', '.join(position['focus'])}."
        if position.get("maintains"):
            foco_line += f" Mantiene con dosis mínima: {position['maintains']}."
    fuerza_line = (f" La fuerza en esta fase busca: {position['strength_quality']}."
                   if position.get("strength_quality") else "")
    provisional_line = (
        " **Fase por confirmar según su progresión**: el macrociclo detalla sus primeras semanas y ésta se "
        "escribió como un mapa hacia la carrera. Su intención orienta; sus números léelos contra lo que el atleta "
        "demuestra hoy. No le llames \"provisional\" ni \"preliminar\" al atleta."
        if position.get("provisional") else ""
    )
    return (
        f"\n### Real Mesocycle Position (computed from season_start_date, not guessed from prose)\n"
        f"This is week {position['week_number']} of the season, phase '{position['phase_name']}' "
        f"({position['intensity_emphasis'] or ''}).{target_line} Deload week: {deload_line}"
        f"{reparto_line}{foco_line}{fuerza_line}{edad_line}{provisional_line}\n"
    )


async def weekly_planner_node(state: TrainingAnalysisState) -> dict[str, list | str]:
    logger.info("Starting weekly planner node")

    hitl_enabled = state.get("hitl_enabled", True)
    logger.info("Weekly planner node: HITL %s", "enabled" if hitl_enabled else "disabled")

    agent_start_time = datetime.now()

    tools = configure_node_tools(
        agent_name="weekly_planner",
        plot_storage=None,
        plotting_enabled=False,
    )

    workflow_context = get_workflow_context("weekly_planner")
    decision_system_prompt = (
        workflow_context
        + TRAINING_DECISION_SYSTEM_PROMPT
        + get_conflict_policy()
        + CONFLICT_CONSEQUENCE_FOR_TRAINING
        + (get_hitl_instructions("weekly_planner") if hitl_enabled else "")
    )
    format_system_prompt = workflow_context + WORKOUT_FORMATTER_SYSTEM_PROMPT

    season_plan_text = extract_season_plan_text(state.get("season_plan"))
    season_phases = _load_season_phases(state["user_id"])
    # Read now, while weekly_plan.json on disk is still the PREVIOUS plan —
    # server.py archives and overwrites it only after this workflow finishes.
    plan_anterior = _load_previous_plan(state["user_id"])
    previous_plan = _format_previous_plan(
        plan_anterior,
        (state.get("current_date") or {}).get("date", ""),
        rehaciendo=bool((state.get("availability_constraints") or {}).get("rehacer_semana")),
    )
    frase_de_cambios = rationale_de_cambios(
        plan_anterior,
        (state.get("current_date") or {}).get("date", ""),
        rehaciendo=bool((state.get("availability_constraints") or {}).get("rehacer_semana")),
    )
    # What the athlete actually did with that plan. Computed in server.py,
    # where both the previous plan and the real Garmin activities are in hand.
    adherence_block = (state.get("adherence") or {}).get("prompt_block") or ""
    # Track E: real HRV/RHR numbers against this athlete's own Garmin baseline
    # (services/ai/recovery_state.py). Empty string when the athlete has no
    # such data, so the prompt simply has no recovery section rather than one
    # full of "N/A" that invites reasoning about absent data.
    recovery_block = ((state.get("availability_constraints") or {}).get("recovery") or {}).get("block") or ""
    # v6 Track 2: the load and readiness numbers Garmin already had and the
    # planner never saw -- ACWR with its status and its DATE, training
    # readiness, the monthly load balance, and this athlete's own recent
    # effort history per sport. Before this the planner had only the metrics
    # expert's prose about them, which is how a rationale ended up quoting an
    # ACWR from race day as if it were today's (services/ai/load_state.py).
    load_raw = ((state.get("availability_constraints") or {}).get("load_state") or {}).get("block") or ""
    # Como respondio a sus sesiones exigentes va pegado al estado de carga: son
    # las dos mitades de lo mismo, lo que hizo y lo que le costo. Ver
    # services/ai/training_response.py.
    respuesta = ((state.get("availability_constraints") or {}).get("training_response") or {}).get("block") or ""
    # v1.2.2: y cómo reparte su intensidad, medido por sesión. Va con la carga
    # porque es la otra mitad de la misma pregunta -- cuánto, y de qué. "" sin
    # zonas o sin muestras. Ver services/ai/intensity_distribution.py.
    reparto = render_distribucion(
        ((state.get("availability_constraints") or {}).get("volume_baseline") or {}).get("intensity_distribution")
    )
    load_raw = "\n\n".join(b for b in (load_raw, respuesta, reparto) if b)
    load_block = f"\n{load_raw}\n" if load_raw else ""
    # v7.2. La forma de la semana habitual: que dias entrena, de que, cuantas
    # calidades y en que dia caen. Nadie lo miraba -- el experto de actividad
    # tiene prohibido hablar de carga global y el de metricas ve ACWR y CTL, que
    # son un numero por dia sin forma semanal. Un atleta con calidad todos los
    # miercoles desde hace meses recibio una semana entera en Z1/Z2.
    # DESCRIBE, no prescribe: ver services/ai/training_habits.py.
    habits_raw = ((state.get("availability_constraints") or {}).get("habits") or {}).get("block") or ""
    habits_block = f"\n{habits_raw}\n" if habits_raw else ""
    # v5 Track 6. Optional by design: an athlete who skipped the form leaves
    # this empty and nothing downstream changes.
    checkin_raw = ((state.get("availability_constraints") or {}).get("checkin") or {}).get("block") or ""
    checkin_block = f"\n{checkin_raw}\n" if checkin_raw else ""
    # v6 Track 3: what the athlete said hurts, from BOTH places they can say it
    # (this week's check-in and the health record), plus which gestures load
    # which structures. Separate from the check-in block because it carries a
    # requirement the check-in block never did: the day's `adaptation` must
    # name the structure the ATHLETE named. A real plan answered a declared
    # groin niggle with a contingency written for the knee -- on the bike
    # session, the one that loads the hip flexors most.
    niggle_raw = ((state.get("availability_constraints") or {}).get("niggles") or {}).get("block") or ""
    niggle_block = f"\n{niggle_raw}\n" if niggle_raw else ""
    # v7 Track 7: lo que el atleta le ha contado al coach en el chat, con
    # fecha. Llega por el mismo canal lateral que todo lo demas.
    #
    # Que entre AQUI y no solo en el chat es el track entero. Un coach que
    # recuerda que las sesiones intensas de tarde le arruinan el sueno, y que
    # la semana siguiente le vuelve a poner series a las 6 PM, no tiene
    # memoria: tiene cortesia. Son AFIRMACIONES del atleta, no mediciones, y
    # el bloque lo dice para que el planificador las contraste con sus datos
    # de Garmin en vez de tratarlas como un dato duro.
    peticiones_block, notebook_block = bloques_del_cuaderno(state)
    qa_messages = normalize_langchain_messages(state.get("weekly_planner_messages", []))

    base_llm = ModelSelector.get_llm(AgentRole.WORKOUT)
    # A SECOND model, for the calls that encode rather than decide. Same split
    # this node already makes between deciding and formatting -- v7 just
    # stopped paying the decision price for the formatting half, which the
    # bench put at 46% of this node's bill. The tier may well point both roles
    # at the same model; that is the tier's call, not this node's.
    encoder_llm = ModelSelector.get_llm(AgentRole.WORKOUT_ENCODER)
    llm_with_tools = base_llm.bind_tools(tools) if tools else base_llm
    # Decision call may need tools (research); the formatter never does -- its
    # entire input is already handed to it, there's nothing left to look up.
    # Con el HITL apagado, la rama de preguntas SALE DEL ESQUEMA. No es
    # cosmetico: el prompt ya no lleva las instrucciones de HITL en ese caso, y
    # un esquema que ofrece una salida que el prompt no explica es una salida
    # que el modelo toma de vez en cuando -- dos de tres semanas seguidas
    # murieron asi, pagadas y sin plan. Ver TrainingDecisionNoHitl.
    decision_llm = llm_with_tools.with_structured_output(
        TrainingDecisionOutputs if hitl_enabled else TrainingDecisionNoHitl
    )
    # The formatter emits a bare WeekBlock (week_number + days), NOT the full
    # WeeklyPlannerOutputs. Three things came off its output schema, none of
    # which it ever needed to DECIDE: the `list[Question] | WeeklyPlanPayload`
    # union (only the decision step can raise HITL questions, and unions
    # degrade structured-output reliability), the payload wrapper (one extra
    # nesting level around every day), and `zones_table` (a verbatim
    # restatement of numbers already held exactly -- now rendered
    # deterministically by prescription.render_zones_table). Verified against
    # real runs where roughly half of each week's non-strength days came back
    # with steps=[] on the first pass and had to be repaired; anything the
    # model does not need to decide should not be in its output schema.
    format_llm = encoder_llm.with_structured_output(FormattedWeekBlock)
    # Same call, `steps` optional again. Only used when the strict schema
    # cannot be satisfied after a corrected retry -- see call_format.
    lenient_format_llm = encoder_llm.with_structured_output(WeekBlock)
    # Repair works across the whole (possibly multi-week) payload, so it needs
    # WeeklyPlanPayload rather than a single WeekBlock -- but for the same
    # reason as above it gets the payload directly, without the HITL union.
    #
    # On the DECISION model, deliberately. A repair is not an encoding: the
    # week arrived broken and something has to work out why and what to do
    # instead -- exceeded volume, a quality session with no pace band, a
    # repeat with no recovery. That is the judgement half of this node.
    repair_llm = base_llm.with_structured_output(WeeklyPlanPayload)

    all_week_dates = state["week_dates"]
    # Split the flat 28-date list into 7-date chunks (last chunk absorbs any
    # remainder if week_dates isn't an exact multiple of 7).
    # Una semana de la app es UN bloque aunque no tenga siete dias: el primero
    # va hasta la vispera del dia en que empieza la semana del atleta, y si
    # eso son uno o dos dias se une a la siguiente (hasta 9). Partirlo en 7+2
    # pagaria dos decisiones por una semana. Ver server.py::dias_del_bloque.
    if len(all_week_dates) <= 9:
        week_chunks = [all_week_dates]
    else:
        week_chunks = [all_week_dates[i:i + 7] for i in range(0, len(all_week_dates), 7)] or [all_week_dates]
    total_weeks = len(week_chunks)

    metrics_analysis = extract_expert_output(state.get("metrics_outputs"), "for_weekly_planner")
    activity_analysis = extract_expert_output(state.get("activity_outputs"), "for_weekly_planner")
    physiology_analysis = extract_expert_output(state.get("physiology_outputs"), "for_weekly_planner")

    # The athlete's real numbers (see services/ai/prescription.py). Everything
    # above this line is prose written by other agents; this is the only place
    # the planner learns the actual zone boundaries and threshold pace it needs
    # to prescribe a pace target instead of guessing a heart-rate zone.
    prescription_card = (state.get("prescription") or {}).get("card") or (
        "### Ficha de Prescripción del Atleta\n"
        "No disponible para este atleta. Prescribe por zona de FC y describe la intensidad "
        "en el texto del paso; no inventes rangos de ritmo sin datos que los respalden."
    )

    # La guia de montana solo viaja cuando el atleta CORRE en montana. Son
    # ~600 tokens en cada una de las llamadas de decision de la corrida, y
    # mandarselos a un nadador es pagar por ruido. La misma logica que hace
    # que la ficha de prescripcion no lleve bandas de potencia a quien no
    # tiene bici.
    trail_guidance = (TRAIL_GUIDANCE if _is_trail_athlete(state) else "") + (GUIA_DE_DRILLS if _nada(state) else "")

    # --- La fuerza, cuando el atleta tiene su rutina confirmada (Beta 2.1, S5) ---
    #
    # Sin rutina confirmada NADA de esto corre y el prompt de la decisión no cambia
    # ni un carácter: `resumen_de_fuerza` es "" y el codificador sigue escribiendo
    # los días de gimnasio como hasta hoy. Ver tests/test_la_fuerza_de_su_rutina.py.
    fuerza_guardada = (state.get("availability_constraints") or {}).get("fuerza") or {}
    registro_de_fuerza = fuerza_guardada.get("logs") or []
    perfil_de_fuerza = leer_perfil_de_fuerza(fuerza_guardada)
    rutina_de_fuerza = leer_rutina_de_fuerza(fuerza_guardada)
    # Los dos modos que tienen programa: la rutina que pegó él (S4) y la que le escribió
    # el coach (S6). Los dos pasan por el mismo camino, que es lo que hace que los
    # ejercicios dejen de bailar entre regeneraciones.
    usa_su_rutina = bool(
        perfil_de_fuerza and perfil_de_fuerza.modo in ("rutina", "coach")
        and rutina_de_fuerza and rutina_de_fuerza.confirmada and pf.sesiones(rutina_de_fuerza)
    )
    resumen_de_fuerza = f"\n{pf.resumen_de_la_rutina(rutina_de_fuerza, perfil_de_fuerza)}\n" if usa_su_rutina else ""
    # El paso de fuerza decide dosis y cambios sobre un programa que construye el
    # código: una tarea estrecha con rol propio (AgentRole.STRENGTH_STEP, banco del
    # 2026-09-23), no el modelo de la decisión de toda la semana.
    fuerza_llm = (ModelSelector.get_llm(AgentRole.STRENGTH_STEP).with_structured_output(pf.AjusteDeFuerza)
                  if usa_su_rutina else None)
    if usa_su_rutina:
        logger.info("Fuerza: el atleta tiene %d sesiones confirmadas en su programa (%s)",
                    len(pf.sesiones(rutina_de_fuerza)), rutina_de_fuerza.origen)

    async def node_execution():
        collected_weeks: list[WeekBlock] = []
        # Archived by services/ai/reasoning_archive.py -- see state.week_intents.
        collected_intents: list[dict] = []
        # Los días de fuerza que escribió el código desde su rutina. Se reponen
        # DESPUÉS de la reparación: el tier-2 reescribe la semana entera y no
        # tiene por qué conocer el programa del atleta.
        fuerza_de_su_rutina: dict[tuple, WorkoutDay] = {}
        # Rendered from the athlete's real zones rather than asked of the
        # formatter -- see render_zones_table and the format_llm comment above.
        zones_table = render_zones_table(state.get("prescription"))
        week1_volume_target: dict | None = None
        week1_load_target: dict | None = None
        # get_stream_writer() surfaces custom mid-node progress via LangGraph's
        # stream_mode="custom" (see workflow_cost_tracker.py) -- without this,
        # the outer workflow only sees "weekly_planner" as one opaque node that
        # takes ~2.5min, with no visibility into which of the 4 week-chunk LLM
        # calls (or the tier-2 repair calls after them) is currently running.
        stream_writer = get_stream_writer()

        # Wraps every LLM call this node makes (all week chunks + any tier-2
        # repair calls inside validate_and_repair_weekly_plan) so the real
        # token usage is available for create_cost_entry below, regardless of
        # how many chunks/repairs actually ran.
        with get_usage_metadata_callback() as usage_cb:
            for week_number, week_dates in enumerate(week_chunks, start=1):
                # --- Step 1: Training Decision (coaching judgment, no schema) ---
                stream_writer({"status": f"Decidiendo el entrenamiento: semana {week_number} de {total_weeks}..."})
                mesocycle_position = compute_mesocycle_position(season_phases, week_dates[0]["date"]) if week_dates else None
                volume_target = compute_weekly_volume_target(
                    (state.get("availability_constraints") or {}).get("volume_baseline"),
                    mesocycle_position,
                    competitions=state.get("competitions"),
                    week_dates=week_dates,
                )
                # La CARGA manda cuando hay con que anclarla; las horas quedan
                # solo para los perfiles sin datos de carga. Ver
                # compute_weekly_load_target.
                load_target = compute_weekly_load_target(
                    (state.get("availability_constraints") or {}).get("volume_baseline"),
                    mesocycle_position,
                    competitions=state.get("competitions"),
                    week_dates=week_dates,
                )
                if week_number == 1:
                    week1_load_target = load_target
                    # Only week 1's target is used for validation below: the
                    # validator carries a single weekly_hours_budget for the
                    # whole payload. In the week-at-a-time flow this node is
                    # built for there is only ever one chunk, so that is exact;
                    # the legacy 4-week path has no volume_baseline at all
                    # (see server.py) so it keeps its previous behaviour.
                    week1_volume_target = volume_target
                decision_user_message = {
                    "role": "user",
                    "content": TRAINING_DECISION_USER_PROMPT.format(
                        week_number=week_number,
                        total_weeks=total_weeks,
                        dias_del_bloque=describir_bloque(
                            len(week_dates),
                            (state.get("availability_constraints") or {}).get("week_start_day"),
                        ),
                        athlete_name=state["athlete_name"],
                        current_date=json.dumps(con_fecha_escrita([state["current_date"]])[0], indent=2, ensure_ascii=False),
                        week_dates=json.dumps(con_fecha_escrita(week_dates), indent=2, ensure_ascii=False),
                        competitions=json.dumps(con_fecha_escrita(carreras_vistas_desde_la_semana(
                            state["competitions"], week_dates, season_phases)), indent=2, ensure_ascii=False),
                        # Ya viene escrito para ESTA semana: en afinamiento, sin
                        # exigir los FIJO (server.build_custom_planning_context).
                        planning_context=state["planning_context"],
                        prescription_card=prescription_card,
                        season_plan=season_plan_text or "(No season plan available yet.)",
                        mesocycle_position=_format_mesocycle_position(
                            posicion_sin_doble_recorte(mesocycle_position, load_target, volume_target)),
                        volume_target=(_format_load_target(load_target) if load_target
                                       else _format_volume_target(volume_target)),
                        training_habits=habits_block,
                        load_state=load_block,
                        recovery_state=recovery_block,
                        athlete_checkin=checkin_block,
                        niggle_constraints=niggle_block,
                        coach_notebook=notebook_block,
                        peticiones_del_atleta=peticiones_block,
                        rutina_de_fuerza=resumen_de_fuerza,
                        season_constraints=_format_constraints(season_phases, week_dates),
                        previous_plan=previous_plan,
                        adherence=adherence_block,
                        metrics_analysis=metrics_analysis,
                        activity_analysis=activity_analysis,
                        physiology_analysis=physiology_analysis,
                        previous_weeks_summary=_build_previous_weeks_summary(collected_weeks),
                        trail_guidance=trail_guidance,
                        rationale_de_cambios=frase_de_cambios,
                    ),
                }
                decision_messages = [
                    {"role": "system", "content": decision_system_prompt}, decision_user_message,
                ] + qa_messages + nota_de_molestias_de_las_respuestas(qa_messages)

                async def call_decision(messages=decision_messages):
                    if tools:
                        return await handle_tool_calling_in_node(
                            llm_with_tools=decision_llm,
                            messages=messages,
                            tools=tools,
                            max_iterations=15,
                        )
                    return await decision_llm.ainvoke(messages)

                decision_output: TrainingDecisionOutputs = await retry_with_backoff(
                    call_decision, AI_ANALYSIS_CONFIG, f"Training Decision (week {week_number}/{total_weeks})"
                )

                if isinstance(decision_output.output, list):
                    # HITL questions mid-generation — abort and surface them,
                    # same contract as the pre-split single-call behavior.
                    #
                    # Con el HITL apagado esto ya no puede pasar (la rama no
                    # esta en el esquema), pero si pasara NO se sale en
                    # silencio: `errors: []` dejaba a quien llama sin nada que
                    # decir, y el atleta veia "no se pudo generar la semana"
                    # sin motivo. Un cinturon detras del tirante.
                    aviso = [] if hitl_enabled else [
                        "El planificador devolvio preguntas de HITL con el HITL apagado: "
                        f"{len(decision_output.output)} pregunta(s), ninguna se puede contestar. "
                        "La semana se queda sin generar."
                    ]
                    if aviso:
                        logger.error(aviso[0])
                    return {
                        "weekly_plan": WeeklyPlannerOutputs(output=decision_output.output),
                        "weekly_plan_validation": None,
                        "week_intents": collected_intents,
                        "costs": [create_cost_entry(
                            "weekly_planner", (datetime.now() - agent_start_time).total_seconds(), usage_cb.usage_metadata,
                        )],
                        "errors": aviso,
                    }

                week_intent: WeekIntentContent = decision_output.output
                # La decisión, contra la disponibilidad ANTES de codificarla (José,
                # 2026-09-15: sus 6x400 en un martes sin correr acabaron en un
                # descanso titulado "5K pace"). Se le devuelve UNA vez; si vuelve a
                # salirse, la red de después convierte esa sesión en descanso y lo dice.
                # Con sus carreras: el día de una carrera está disponible para ella aunque su
                # tabla lo tenga de descanso (Hugo, 2026-10-09). La generación ya trae
                # `race_dates`; esto cubre a quien llegue sin ellas (un espejo viejo, una réplica).
                disponibilidad_de_la_semana = disponibilidad_para_validar(
                    {"competitions": state.get("competitions") or [], **(state.get("availability_constraints") or {})},
                    load_target, volume_target)
                fuera = intenciones_fuera_de_la_tabla(week_intent.days, disponibilidad_de_la_semana, week_dates)
                # Y una natación cuyo "Total" no es lo que suman sus series (atleta 2,
                # 2026-09-23: "Total 2500 m" que sumaba 2.300). NO paga una vuelta por sí
                # sola (el responsable, 2026-09-24: nada de reparaciones caras): la
                # petición pide ahora el subtotal de cada serie, que es lo que evita el
                # error; si la semana ya vuelve por otra cosa, va en la misma vuelta.
                totales = totales_que_no_suman(week_intent.days)
                # Y un día que no cuadra con su fecha en lo que lee el atleta ("viernes 26/09" con
                # el viernes en 25, primera semana del atleta 2 del 2026-09-24). Pocas veces con la
                # fecha ya escrita en el prompt; cuando pasa, vuelve: una fecha mal dicha es un
                # error que el atleta ve. Ver number_audit.dias_mal_nombrados.
                dias_mal = _dias_mal_en_la_decision(week_intent, week_dates)
                if dias_mal:
                    logger.warning("Semana %s: día y fecha que no cuadran (%s); se le devuelve",
                                   week_number, "; ".join(dias_mal))
                # Y una semana EXTREMA contra su propio historial (services/ai/carga_segura.py):
                # la IA la baja, con la evidencia; lo alto pero no extremo sólo se avisa.
                datos_carga = datos_de_carga(state, load_target, len(week_dates), peticiones_block)
                revision = cs.revisar_la_carga(week_intent.days, **datos_carga)
                extrema = revision["nivel"] == cs.EXTREMA
                if extrema:
                    logger.warning("Semana %s: carga EXTREMA contra su historial (%s); se le devuelve", week_number,
                                   "; ".join(s["texto"] for s in revision["senales"] if s["nivel"] == cs.EXTREMA))
                if totales:
                    logger.warning("Semana %s: natación con un total que sus series no suman (%s)%s",
                                   week_number, "; ".join(f"{t['date']} {t['declarado_m']} vs {t['suman_m']} m"
                                                          for t in totales),
                                   "; va en la vuelta" if (fuera or extrema or dias_mal) else "; sólo se anota")
                if fuera:
                    logger.warning(
                        "Semana %s: la decisión se sale de la disponibilidad en %d sesión(es) (%s); se le devuelve",
                        week_number, len(fuera), "; ".join(f"{v['date']} {v['sport']} {v['type']}" for v in fuera))
                if fuera or extrema or dias_mal:
                    from core import redes_ledger
                    redes_ledger.anotar("vuelta_de_la_decision", "; ".join(
                        x for x in (f"{len(fuera)} fuera de la disponibilidad" if fuera else "",
                                    "carga extrema" if extrema else "",
                                    f"{len(dias_mal)} días mal nombrados" if dias_mal else "") if x))
                    stream_writer({"status": f"Revisando la semana {week_number}..."})
                    correccion = decision_messages + [{
                        "role": "user", "content": mensaje_de_correccion(
                            fuera, week_intent.model_dump_json(), totales,
                            cs.mensaje_para_la_decision(revision) if extrema else "", dias_mal),
                    }]

                    async def call_correction(messages=correccion):
                        return await call_decision(messages)

                    try:
                        segunda = await retry_with_backoff(
                            call_correction, AI_ANALYSIS_CONFIG, f"Training Decision correction (week {week_number})")
                        if isinstance(getattr(segunda, "output", None), WeekIntentContent):
                            week_intent = segunda.output
                            quedan = intenciones_fuera_de_la_tabla(
                                week_intent.days, disponibilidad_de_la_semana, week_dates)
                            logger.warning("Semana %s: tras devolverla, %d sesión(es) fuera de la disponibilidad "
                                           "y %d natación(es) con el total sin cuadrar", week_number, len(quedan),
                                           len(totales_que_no_suman(week_intent.days)))
                            if extrema:
                                logger.warning("Semana %s: tras devolverla, la carga queda %s", week_number,
                                               cs.revisar_la_carga(week_intent.days, **datos_carga)["nivel"])
                    except Exception as exc:  # noqa: BLE001 - se sigue con la primera y la red la corrige
                        logger.warning("Semana %s: la corrección de la decisión falló (%s); se sigue con la primera",
                                       week_number, exc)
                collected_intents.append(week_intent.model_dump())
                # LINT, nunca bloquea: si la semana pasa el techo de sesiones
                # duras de su fase, se dice en el log (worker.log) y se sigue.
                # Sube a recorte sólo cuando se le haya visto acertar. Ver
                # intensity_distribution.revisar_reparto_de_la_semana.
                aviso_reparto = revisar_reparto_de_la_semana(
                    [d.model_dump() for d in week_intent.days],
                    (mesocycle_position or {}).get("hard_session_share_max"),
                )
                if aviso_reparto:
                    logger.warning("Semana %s: %s", week_number, aviso_reparto)
                # LINT: "viernes 26/09" con el 26 en sábado. Ver number_audit.dias_mal_nombrados.
                try:
                    mal = _dias_mal_en_la_decision(week_intent, week_dates)
                    if mal:
                        logger.warning("Semana %s: día y fecha que no cuadran en el texto al atleta: %s",
                                       week_number, "; ".join(mal))
                except Exception:  # noqa: BLE001 - un aviso nunca tumba la semana
                    logger.exception("No se pudieron revisar los días del texto")

                # --- Paso de fuerza: su programa, no una sesión reinventada (S5) ---
                dias_de_fuerza = dias_de_su_rutina(week_intent, rutina_de_fuerza) if usa_su_rutina else []
                # Qué sesión va cada día: la que menos estorba a su semana, no la que
                # toca por turno. Si la de pierna no cabe lejos de la calidad, se coloca
                # igual en el mejor sitio y se le DICE al atleta (decisión del
                # responsable, 2026-09-17), que es quien puede añadir un día.
                turnos, avisos_de_la_fuerza = pf.colocar_las_sesiones(
                    rutina_de_fuerza, [d.date for d in dias_de_fuerza], week_intent, plan_anterior
                ) if dias_de_fuerza else ([], [])
                dias_para_la_fuerza = [
                    # Sin los minutos de la decisión: con su rutina confirmada, la sesión dura lo
                    # que dura SU sesión (a su dosis). Con "70 min previstos" para una Upper B de
                    # ~120' el paso bajaba la dosis y quitaba ejercicios "para ajustar al tiempo",
                    # un tiempo que el atleta nunca dio (semana del atleta 2 del 2026-10-05).
                    {"date": d.date, "day_name_es": d.day_name_es, "session_slot": d.session_slot,
                     "minutes": None, "focus": d.focus, "purpose": d.purpose,
                     "adaptation": d.adaptation, "intensity_class": d.intensity_class,
                     "discipline": d.discipline, "session_id": sesion_id}
                    for d, sesion_id in zip(dias_de_fuerza, turnos, strict=False)
                ]
                # Lo que cubre su rutina SALE del prompt del codificador: son ~12k
                # caracteres de catálogos y reglas de series que ya no hacen falta.
                cubiertos = {(d["date"], d["session_slot"]) for d in dias_para_la_fuerza}
                intent_a_codificar = week_intent.model_copy(update={
                    "days": [d for d in week_intent.days if (d.date, d.session_slot) not in cubiertos]
                }) if cubiertos else week_intent

                # --- Step 2: Workout Formatter (schema encoding, no judgment) ---
                stream_writer({"status": f"Formateando el entrenamiento: semana {week_number} de {total_weeks}..."})
                format_user_message = {
                    "role": "user",
                    "content": WORKOUT_FORMATTER_USER_PROMPT.format(
                        week_number=week_number,
                        prescription_card=prescription_card,
                        # El catalogo de ejercicios con animacion en el reloj.
                        # Se genera desde garmin_vocabulary para que no pueda
                        # divergir de lo que el constructor acepta.
                        # Sólo si la semana tiene fuerza: ~12k caracteres que
                        # en una semana sin gimnasio sólo diluyen las reglas
                        # de las series. Ver _hay_fuerza.
                        exercise_catalogue=render_exercise_catalogue() if _hay_fuerza(intent_a_codificar) else "",
                        exercise_images=render_catalogo_para_prompt() if _hay_fuerza(intent_a_codificar) else "",
                        week_intent=intent_a_codificar.model_dump_json(indent=2),
                    ),
                }
                format_messages = [{"role": "system", "content": format_system_prompt}, format_user_message]

                async def call_format(messages=format_messages, week=week_number):
                    """Format the week, and hand the model its own error back.

                    Measured on a real archived prompt: gemini-3.5-flash wrote
                    `{"step_type": "repeat", "repeat_count": 5, "description":
                    "5x(400m a ritmo VO2max, 2' rec)"}` with no `substeps` --
                    three times in a row. A blind retry cannot fix that,
                    because the failure is deterministic, not transient: the
                    JSON Schema says `substeps` is optional and only a Python
                    validator says otherwise, so the model has no way to know.

                    Telling it exactly what was rejected costs one small call
                    (~$0.03) against $0.11 for the tier-2 repair rebuilding the
                    entire week's payload.

                    **It does not always work, and that is measured too.**
                    Replayed against that same archived prompt, Gemini 3.5
                    Flash refused to expand the repeat block on all three
                    attempts even with the validator's own error in front of
                    it. So the last attempt drops back to the lenient schema:
                    the run then behaves exactly as it does today (the
                    formatter returns what it can, the validator flags the
                    gaps, the tier-2 repair fills them). Never worse than
                    before, sometimes much better -- but the repeat-block
                    problem itself is NOT solved here. Its real fix is a
                    schema in which the invalid state cannot be written down
                    at all, which is a separate change.
                    """
                    attempt_messages = messages
                    for attempt in range(2):
                        try:
                            return await format_llm.ainvoke(attempt_messages)
                        except (OutputParserException, ValidationError) as exc:
                            logger.warning(
                                "Workout formatting (week %s) rejected on attempt %d/2: %s",
                                week, attempt + 1, str(exc)[-400:],
                            )
                            attempt_messages = messages + [{
                                "role": "user",
                                "content": (
                                    "Tu respuesta anterior NO cumplió el esquema y se descartó por completo. "
                                    "Error exacto del validador:\n\n"
                                    f"{str(exc)[-1500:]}\n\n"
                                    "Corrígelo y devuelve la semana ENTERA otra vez, con todos los días. "
                                    "Recuerda que un paso con `step_type=\"repeat\"` DEBE llevar `substeps` "
                                    "con los pasos que se repiten dentro (el trabajo y su recuperación) y "
                                    "`repeat_count` >= 2: escribir la repetición sólo en `description` no "
                                    "sirve, porque el reloj no la puede ejecutar."
                                ),
                            }]

                    logger.warning(
                        "Workout formatting (week %s) could not satisfy the strict schema; falling back to "
                        "the lenient one so the validator and its repair can take it from here", week,
                    )
                    return await lenient_format_llm.ainvoke(messages)

                async def call_fuerza(dias=dias_para_la_fuerza, intent=week_intent,
                                      posicion=mesocycle_position, carga=load_target):
                    """El ajuste de su rutina, en paralelo con el codificador: ni una espera de más."""
                    if not dias:
                        return []
                    return await pf.ajustar_la_fuerza(
                        rutina=rutina_de_fuerza,
                        perfil=perfil_de_fuerza,
                        dias=dias,
                        semana=pf.semana_alrededor(intent, {d["date"] for d in dias}),
                        fase=_format_mesocycle_position(posicion).strip(),
                        avisos=pf.avisos_de_la_semana(carga),
                        molestias=niggle_raw,
                        anterior=pf.fuerza_anterior(plan_anterior),
                        # Lo que levantó de verdad: de aquí sale la progresión de carga.
                        registro=pf.lo_que_levanto(registro_de_fuerza, rutina_de_fuerza),
                        llamar=fuerza_llm.ainvoke,
                    )

                formatted, ajustes_de_fuerza = await asyncio.gather(
                    retry_with_backoff(
                        call_format, AI_ANALYSIS_CONFIG, f"Workout Formatting (week {week_number}/{total_weeks})"
                    ),
                    call_fuerza(),
                )
                # The flat lines the model wrote become the nested repeat
                # blocks Garmin needs -- deterministically, in code, once.
                # A lenient fallback answer is already a WeekBlock.
                week_block: WeekBlock = (
                    formatted.to_week_block() if isinstance(formatted, FormattedWeekBlock) else formatted
                )

                if dias_para_la_fuerza:
                    aviso_de_fuerza = pf.aviso_de_dosis(ajustes_de_fuerza, load_target)
                    if aviso_de_fuerza:
                        logger.warning("Semana %s: %s", week_number, aviso_de_fuerza)
                    construidos = pf.dias_construidos(
                        rutina_de_fuerza, dias_para_la_fuerza, ajustes_de_fuerza, perfil_de_fuerza)
                    # Una COPIA: lo que va a la semana lo pueden tocar el validador y su
                    # reparación, y lo que se repone tiene que ser lo que escribió el código.
                    pf.avisar_en_los_dias(construidos, avisos_de_la_fuerza)
                    for aviso in avisos_de_la_fuerza:
                        logger.warning("Semana %s: %s", week_number, aviso["text"])
                    fuerza_de_su_rutina.update(
                        {(d.date, d.session_slot): d.model_copy(deep=True) for d in construidos})
                    week_block.days = sorted(
                        [d for d in week_block.days if (d.date, d.session_slot) not in cubiertos] + construidos,
                        key=lambda d: (d.date or "", d.session_slot or ""),
                    )

                if week_block.days:
                    week_block.week_number = week_number
                    # Copied, never retyped by the formatter: the reasoning
                    # belongs to the step that actually did the reasoning, and
                    # asking a second model to restate it would only give it a
                    # chance to drift from the decisions it describes.
                    week_block.week_rationale = week_intent.week_rationale
                    # El aviso de colocación de la fuerza va SÓLO en su día, en una línea
                    # (`placement_note`). Hasta el 2026-09-23 se repetía aquí en un párrafo
                    # de cinco líneas, y el responsable pidió menos texto que nadie lee.
                    _apply_discipline(week_block, week_intent)
                    for aviso in pasos_que_no_suman_el_dia(week_block.days):
                        logger.warning("Semana %s: %s", week_number, aviso)
                    collected_weeks.append(week_block)
                else:
                    logger.warning("Week %d/%d returned no days; skipping", week_number, total_weeks)

            merged_payload = WeeklyPlanPayload(
                athlete_name=state["athlete_name"],
                start_date=all_week_dates[0]["date"] if all_week_dates else "",
                zones_table=zones_table or "N/A",
                weeks=collected_weeks,
            )
            agent_output = WeeklyPlannerOutputs(output=merged_payload)

            validation_report = None
            errors: list[str] = []
            availability = dict(state.get("availability_constraints") or {})
            # Tells the ACWR guardrail (plan_validator.py) whether week 1 is
            # already a scheduled deload week, so it doesn't cut the same
            # week's volume twice for two different reasons (Fase 3's
            # periodization vs. Fase 5's injury-risk guardrail).
            week1_position = compute_mesocycle_position(season_phases, all_week_dates[0]["date"]) if all_week_dates else None
            availability["week1_is_deload"] = bool(week1_position and week1_position.get("is_deload"))
            # Same side-channel the ACWR guardrail already uses: the validator
            # needs the athlete's real pace/power bands to check that quality
            # work is prescribed by the right modality and within reach.
            availability["prescription"] = state.get("prescription") or {}
            # Plan Maestro v4 Track C: same side-channel, so the validator's
            # race-day/taper rule (plan_validator.py's _race_day_violations)
            # and tier-2 repair (which reads availability wholesale) both see
            # the athlete's real competition dates, not just the raw JSON
            # dump in the prompt below.
            availability["competitions"] = state.get("competitions") or []
            # El VOLUMEN DE LA SEMANA NO SE VALIDA. Es una decision de
            # entrenador, y el modelo que la toma tiene delante la historia del
            # atleta y como respondio; el validador no tiene nada de eso, solo
            # una estimacion.
            #
            # Hasta la v1.2 aqui se le pasaba un presupuesto (primero en horas,
            # luego en carga) y el validador ACORTABA sesiones cuando la semana
            # lo pasaba, o pagaba una reparacion tier-2 para que lo hiciera el
            # modelo. Las dos cosas sustituian el criterio de quien habia
            # decidido por un numero con ruido: la carga por hora de una
            # natacion "suave" del atleta real va de 24 a 102. Una sesion larga
            # puede ser exactamente la que ese atleta necesita y sabe hacer.
            #
            # `weekly_hours_budget` viajaba por una ultima razon, el guardarrail
            # de ACWR (>1,5 recortaba la semana 1 al 70-80 %), y se retiro el
            # 2026-09-14: ver el comentario en plan_validator.validate_weekly_plan.
            # La carga estimada, solo para el registro: sirve para auditar
            # despues que hizo el modelo, no para corregirlo.
            if week1_load_target and week1_load_target.get("rates"):
                estimada = carga_de_la_semana(
                    [d for w in merged_payload.weeks for d in w.days], week1_load_target["rates"])
                logger.info(
                    "Carga estimada de la semana (solo registro): %s (cronica %s)%s",
                    estimada["carga"], week1_load_target["chronic"],
                    f"; sin tasa: {', '.join(estimada['sin_tasa'])}" if estimada["sin_tasa"] else "",
                )
            # Y las horas contra una semana normal, que es lo que se audita de un
            # afinamiento (ver _escala_del_afinamiento). Registro, no corrección.
            normales = ((state.get("availability_constraints") or {}).get("volume_baseline") or {}).get(
                "chronic_weekly_hours")
            if normales:
                minutos = sum(d.total_duration_minutes or 0 for w in merged_payload.weeks for d in w.days
                              if not d.is_rest)
                logger.info(
                    "Horas de la semana (solo registro): %.1f h contra %.1f h de una semana normal (%+.0f %%)%s",
                    minutos / 60, normales, (minutos / 60 / normales - 1) * 100,
                    "; semana de afinamiento" if (week1_load_target or {}).get("race_taper")
                    else "; semana después de una carrera" if (week1_load_target or {}).get("race_recovery") else "",
                )
            stream_writer({"status": "Validando y ajustando el plan semanal..."})
            validated_payload, validation_report = await validate_and_repair_weekly_plan(
                payload=merged_payload,
                availability=disponibilidad_para_validar(availability, week1_load_target, week1_volume_target),
                week_dates=all_week_dates,
                llm_with_structure=repair_llm,
            )
            if fuerza_de_su_rutina:
                repuestos = pf.reponer_la_fuerza(validated_payload, fuerza_de_su_rutina)
                logger.info("Fuerza: %d de %d días de su rutina repuestos tras la reparación",
                            repuestos, len(fuerza_de_su_rutina))
            # La reparación reescribe la semana entera: la carrera vuelve a ir hasta pulsar vuelta.
            validated_payload, _ = carrera_hasta_pulsar_vuelta(validated_payload)
            # El aviso de carga que ve el atleta, sobre la semana que de verdad le llega.
            if week1_load_target is not None or (state.get("availability_constraints") or {}).get("volume_baseline"):
                for semana in validated_payload.weeks:
                    fechas = {d.date for d in semana.days}
                    revision_final = cs.revisar_la_carga(
                        semana.days, **datos_de_carga(state, week1_load_target, len(fechas) or 7, peticiones_block))
                    semana.load_warning = cs.aviso_para_el_atleta(revision_final, peticiones_block)
                    logger.info("Carga de la semana %s: %s (%s)", semana.week_number, revision_final["nivel"],
                                "; ".join(s["texto"] for s in revision_final["senales"]) or "sin señales")
            agent_output.output = validated_payload
            errors = validation_report.get("errors", [])

            execution_time = (datetime.now() - agent_start_time).total_seconds()
            log_node_completion("Weekly planning", execution_time)

            return {
                "weekly_plan": agent_output,
                "weekly_plan_validation": validation_report,
                "week_intents": collected_intents,
                "costs": [create_cost_entry("weekly_planner", execution_time, usage_cb.usage_metadata)],
                "errors": errors,
            }

    return await execute_node_with_error_handling(
        node_name="Weekly planner",
        node_function=node_execution,
        error_message_prefix="Weekly planning failed",
    )


def describir_bloque(n_dias: int, week_start_day: str | None) -> str:
    """Cuantos dias tiene el bloque, y si no son siete, por que y como leer las referencias.

    Las referencias de carga y volumen son SEMANALES: en un bloque de cinco
    dias, un modelo que no lo sabe mete la carga de siete en cinco."""
    if n_dias == 7:
        return "7 days"
    motivo = (f"this athlete's training week starts on {week_start_day}, so this block runs until the day "
              f"before and the next one starts on {week_start_day}") if week_start_day else "a non-standard block"
    return (f"{n_dias} days — {motivo}. The weekly load and volume references below are per 7 days: scale "
            f"them to the {n_dias} days this block really has")


def extract_season_plan_text(season_plan) -> str:
    """season_plan is a SeasonPlannerOutputs.model_dump() dict ({"output": ...})
    stored as-is by season_planner_node — unwrap it to plain text for the prompt.
    output can be: a plain str (legacy, pre-SeasonPlanContent plans), a dict
    shaped like SeasonPlanContent ({"markdown": ..., "phases": [...], ...}),
    or a list of Question dicts (HITL pending)."""
    if not season_plan:
        return ""
    output = season_plan.get("output") if isinstance(season_plan, dict) else getattr(season_plan, "output", None)
    if isinstance(output, str):
        return output
    if isinstance(output, dict):
        return output.get("markdown", "") or ""
    if isinstance(output, list):
        # list of Question dicts (HITL pending) — nothing usable yet
        return ""
    return ""
