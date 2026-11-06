import json
import logging
import re
from datetime import date, datetime

from langchain_core.callbacks.usage import get_usage_metadata_callback

from services.ai.ai_settings import AgentRole
from services.ai.goal_realism import assess_goals, render_goal_realism_block
from services.ai.intensity_distribution import render_distribucion
from services.ai.langgraph.schemas.plan_outputs import SeasonPlanContent, SeasonPlannerOutputs
from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState
from services.ai.langgraph.utils.output_helper import extract_expert_output
from services.ai.model_config import ModelSelector
from services.ai.number_audit import con_fecha_escrita
from services.ai.perfil_de_fuerza import (
    leer_perfil_de_fuerza,
    leer_rutina_de_fuerza,
    render_para_el_macrociclo,
)
from services.ai.preguntas_del_coach import seccion_de_respuestas, sin_respuestas_al_coach
from services.ai.prescription import PACE_BAND_LABELS_ES
from services.ai.season_constraints import render_constraints_for_review
from services.ai.semanas_del_macrociclo import arranque_de_sus_semanas
from services.ai.utils.plan_storage import FilePlanStorage
from services.ai.utils.retry_handler import AI_ANALYSIS_CONFIG, retry_with_backoff

from .node_base import (
    configure_node_tools,
    create_cost_entry,
    execute_node_with_error_handling,
    log_node_completion,
)
from .prompt_components import get_conflict_policy, get_hitl_instructions, get_workflow_context
from .tool_calling_helper import handle_tool_calling_in_node

logger = logging.getLogger(__name__)

SEPARADOR_DE_BLOQUES = "\n\n"

SEASON_PLANNER_SYSTEM_PROMPT = """You are a strategic season planner.
## Goal
Create strategic season plans for long-term athletic development.
## Principles
- Strategic: Focus on macro-cycles and phases.
- Adaptive: Use expert insights to tailor the plan.
- Systematic: Ensure logical progression towards goals."""

SEASON_PLANNER_USER_PROMPT = """Create a STRATEGIC, HIGH-LEVEL season plan as a structured
`SeasonPlannerOutputs` (the JSON schema is enforced automatically — focus on the CONTENT below).

## How long the plan is: a map to the goal race, with detail only where it is reliable
- **If the athlete has a Priority A race within __SEMANAS_HASTA_LA_A__ weeks, the plan runs from today to
  that race, taper included**, and is built backward from it: what the race demands goes closest to it,
  because the qualities with the shortest-lived effect (speed, race-specific sharpness) fade in days while an
  aerobic base holds for weeks. With no A race that close, plan 12-24 weeks toward the athlete's goal.
- **Only the first __SEMANAS_FIRMES__ weeks are planned in detail.** Phases that start later are a map toward
  the race: their numbers are rough, and the app asks the athlete to re-plan at week __SEMANAS_FIRMES__, with
  how their body responded to the first block. Do not write week 28 as if you knew it as well as week 3.
  **How the markdown says it** (the athlete reads it): write the phases that start within those weeks first;
  then, only if some phase starts later, one line exactly `**Fases posteriores por confirmar según tu
  progresión:**` and those phases under it, saying what would change them. Never label a phase or a week
  "firme", "provisional", "preliminar", "tentativa" or anything like it — the athlete read those words as
  doubts about the plan; the separator line already says it.

## Inputs
- Athlete: {athlete_name}
- Date: ```json {current_date} ```
- Week 1 of this plan starts on **{inicio_del_macrociclo}**: the first day of the athlete's own week that
  contains today (the app counts every week from it; when refining, it is the existing plan's start).
  It is the app's bookkeeping: use it to count weeks, but do not explain it or its date to the athlete in
  the markdown (a real report opened with "Ancla conservada: 2026-09-21. Aunque hoy es 2026-09-23...").
- Competitions (`semana_del_macrociclo` is the week of this plan the race falls in, counted from that
  start; a race marked `ya_corrida` is already behind and is not prepared): ```json {competitions} ```
- Planning context (availability, goal discipline, declared health record): ```markdown
{planning_context}
```

{fuerza_declarada}## The athlete's real numbers
{prescription_card}

{volume_baseline}
{load_state}

{adherence}
{previous_season}
{track_record}
{phase_response}
{training_habits}

{goal_realism}
{coach_notebook}

## Expert Insights
### Metrics
```markdown
{metrics_insights}
```
### Activity
```markdown
{activity_insights}
```
### Physiology
```markdown
{physiology_insights}
```

## Task
Create a macro-cycle framework.
- **Integrate**: Use expert insights as your north star.
- **Strategize**: Define phases, themes, and focus areas.
- **Respect Boundaries**: Do NOT prescribe daily workouts (Weekly Planner's job).

## Output Requirements (schema `SeasonPlanContent`)
- `season_start_date`: the week-1 start given above, in YYYY-MM-DD. This is the fixed anchor every later
  weekly-plan generation uses to compute "we are in week N" — do not change it once set for an existing plan
  (see "Existing Season Plan" below if this is a refinement, not a fresh plan).
- `phases`: a list of `SeasonPhase`, covering the WHOLE season length contiguously (phase 1 starts at
  `start_week=1`, each next phase's `start_week` = previous `end_week + 1`, no gaps). Each phase needs
  `name`, `start_week`/`end_week`, `weekly_load_target`, `intensity_emphasis` (one sentence), and a rough
  `weekly_hours_target` as an agenda reference.
  **A phase is defined by what THIS athlete needs next, read from what they have actually done and how
  their body responded** — which sessions carried their best weeks, what they tolerate, how long they take
  to absorb a hard block, what they have been missing — and by the goal race. Say in `intensity_emphasis`
  and in the markdown which key sessions carry the phase and why, citing their history. Every athlete is
  different, and a phase that suits the data of another athlete is not a phase for this one.
  Garmin's Load Focus and Training Status are a second opinion computed from HR zones and a max HR that
  are often set wrong: check them against the history and the response before they shape a phase, and
  when they disagree, say so and follow the history.
  `weekly_load_target` is a rough reference, in Garmin load units, for a NORMAL week of that phase,
  anchored to what the athlete really absorbs today. Race weeks and their taper are not averaged into it:
  the weekly planner sees the race calendar and handles them week by week. Describe the taper in the
  markdown instead.
  **Anchored to today does not mean flat.** Across the phases the reference grows from where the athlete is
  toward what the goal race demands, at the rate their history and response support. If the time available
  cannot close that gap, say so plainly in the markdown and what a realistic goal for this race looks like —
  never flatten the phases to fit today, and never write numbers that pretend the gap closes.
- `deload_weeks` (on each phase): the weeks with deliberately reduced load, and **where they go is a
  coaching decision, not a cadence.** Place them where this athlete needs them — after a race, after the
  hardest block, where recovery metrics say load is accumulating — and say why in the markdown. A fixed
  "every 4th week" pattern applied without looking at the calendar is a template, not a plan. A season of
  12+ weeks with no recovery week at all does need a stated reason.
  **A race week or a taper week is not a `deload_week`**: the weekly planner already lowers the load for a
  race from the calendar, and marking it as a deload too cuts the same week twice. **The recovery after a race
  is sized to the race**, like its taper: a few easy days after a 5K-10K or a short trail, about a week after a
  half marathon, two or more after a marathon or a long-distance triathlon — a B race does not open a recovery
  phase of its own. If more than about one week in four ends up reduced (deloads, tapers and post-race
  weeks together), say why this athlete needs that much, because it is a lot of training time given away.
- **Each race is prepared in the weeks BEFORE it, not from its week.** `semana_del_macrociclo` says where every
  race falls. The work a race specifically asks for (climbing and descending for a trail, race pace for a road
  race) goes in the weeks leading to it and is sharpest just before its taper; the race week holds the taper and
  the race, and what follows is recovery sized to the race. A phase built for a race ends in that race's week or
  the week after — it does not start there (the taper-and-race phase of an A race is the only one that may). In
  the markdown, say for each B race from which week to which it gets specific work, and why that is enough for
  this race and this athlete.
- `intensity_distribution`, `hard_session_share_max`, `focus`, `maintains` (on each phase): **how the phase
  spreads intensity and what it concentrates on — and no distribution is right for everyone.** Read "Cómo
  reparte su intensidad" first: it is what this athlete has actually been doing. In every model that works
  most of the training is easy; the models differ in where the rest goes: `piramidal` (more between the
  thresholds than above), `polarizado` (more above the second threshold than between, little in the middle),
  `umbral` (the zone between the thresholds dominates, as in the Norwegian approach) and `alta_intensidad` (a
  large share above the second threshold). All four have evidence behind them: polarized training gave the
  largest gains in well-trained athletes over nine weeks (Stöggl & Sperlich 2014); most elite endurance
  athletes actually train pyramidal (Burnley et al. 2022); in amateur half-Ironman triathletes, more time
  between the thresholds went with faster races (Sellés-Pérez et al. 2019), and an Ironman is raced mostly
  there (Muñoz et al. 2014); short high-intensity blocks raise VO2max in little time. So choose from THIS
  athlete: what the goal race demands (a sprint and a full distance are raced at different intensities),
  what their history shows they respond to and lack, and how many hours they really have — with few hours,
  intensity may be the only stimulus available; with many, a large share above the threshold is neither
  realistic nor needed. `hard_session_share_max` is a CEILING on the share of sessions above the first
  threshold, never a target: the weekly planner may prescribe fewer, or none. `focus` names at most TWO
  capacities — a phase that develops everything develops nothing — and `maintains` says what is kept with a
  minimum dose meanwhile. Say in the markdown why each phase uses its distribution, citing this athlete's
  numbers. **If "Cómo respondió a cada fase" is present, it is this athlete's own evidence of what their past
  distributions did**: when it repeats across more than one phase, weigh it above the population evidence;
  when it rests on two or three weeks or a race, a taper or an illness could explain it, say so and do not
  lean on it.
- `constraints`: the qualitative decisions that should shape every week of this season, as structured
  `SeasonConstraint` entries instead of sentences buried in the markdown -- sport bias ("cycling carries
  the volume to spare the knee"), terrain, load ceilings, technique emphasis, medical caution. Each one
  needs its own `applies_from` and `review_on` dates, its `source`, and `strength`:
  **`limitacion`** for something that must be respected, **`recomendacion`** for a judgement call the
  weekly planner may override with a stated reason. Never write one as an absolute rule.
  **A recovery or load trigger ("when X, pull back") is good coaching only if it is THIS athlete's**: anchored
  to their own baseline (Garmin's personal HRV range, their usual resting HR and sleep — never a population
  cut-off), asking for persistence (several days, not one night) and a second signal that agrees, with a
  proportionate response (less intensity before cancelling). A threshold inside their normal range — a sleep
  score of 75 for an athlete who averages 77 — fires every other week: that is noise, not a rule.
  **A constraint's strength follows the severity the athlete gave it.** A niggle they rate 1-2/10 and train
  through is a `recomendacion` to watch where it is loaded, never a `limitacion` and never a reason to
  restructure a phase; a `limitacion` needs pain that changes how they can train, or a professional's
  instruction. A niggle the athlete says is already gone is history: it can inform how progression is dosed,
  but it is not a `medica` constraint and not the season's main risk.
  **A taper's length follows the race**: about a week for a 5K-10K, around two for a half marathon or an
  olympic triathlon, two to three for a marathon or a long-distance triathlon. Longer than that trades away
  fitness for no freshness.
  **Which days, sports and equipment the athlete has is not a constraint**: the weekly planner reads their
  table and what they open or close in each week's check-in, and a constraint that restates it freezes for
  months something that changes week to week.
  **Nor is what they ask of their training** (how many sessions, a minimum per session, a long one on a given
  day): their recent data showing less of it is not a reason to cap, delay or contradict it, nor to pay for
  it with the other sports (their key sessions, intensity and progression stay as without it) — the weekly
  planner follows it from the first week and the season's hours count it on top; say in the markdown how it
  is done safely. Only real evidence limits it — a pain they declared, recovery persistently below their own
  baseline, a taper — and then the constraint names that evidence.
  **Emit only what this athlete's data actually supports — an empty list is a valid answer, and inventing
  constraints narrows what the weekly planner may prescribe for no reason.** Give each a short stable
  `id` so it can be tracked across re-evaluations.
- `markdown`: the narrative report — same content/quality bar as before this structured schema existed:
  1. **Phases**: goals and themes in prose (matches the structured `phases` above).
  2. **Expert Rationale**: explicitly reference how Metrics, Activity, and Physiology informed the plan.
  3. **Constraints**: qualitative constraints derived from experts.

**Strength has intent**: say which quality each phase's gym work is building — fuerza maxima, potencia,
resistencia de fuerza, o mantenimiento — and which movement patterns carry it, because the weekly planner
reads this and designs the session from it. When the athlete declared what they lift for (see "Su fuerza,
como la pidió"), that goal is theirs and every phase serves it: you choose how each phase gets there and
how much it gives way near a race, never a different goal. What you must NOT do is
promise a specific weekly FREQUENCY (e.g. "bi-weekly strength sessions"): nothing downstream enforces a
count, so a number here is a promise the plan cannot keep. Name the quality and the emphasis, not the tally.
And strength is not a synonym for prehab — a phase can lift heavy with no injury in the picture.

**Stay high-level**. Design the **map of the season**, not the turn-by-turn navigation. **BE CONCISE**."""

# Cuánto dura un macrociclo (2026-09-13).
#
# El prompt pedía "12-24 semanas" y a la vez "periodiza hacia las carreras con
# taper antes de las A". Con la 10K A del atleta 2 a 31 semanas, las dos cosas
# no cabían y el modelo eligió la primera: un plan que terminaba 7 semanas antes
# de su carrera principal, sin fase específica ni afinamiento.
#
# Ni un macrociclo largo y rígido ni dos cortos a ciegas. Se planifica HACIA
# ATRÁS desde la carrera (Friel), porque lo que tiene efecto residual corto
# -- velocidad, afinamiento específico, días -- tiene que caer cerca de ella y
# la base aeróbica aguanta semanas (Issurin). Pero los plazos fijos de la
# periodización clásica no tienen respaldo (Kiely 2012), y el cuerpo responde
# distinto de lo previsto: por eso sólo las primeras SEMANAS_FIRMES son firmes
# y la app pide rehacerlo ahí, cuando el bucle por fase ya tiene datos de cómo
# respondió. Dieciséis son cuatro bloques de cuatro semanas, el tamaño de los
# macrociclos que usan los programas de élite (8-15 semanas).
SEMANAS_HASTA_LA_A = 40
SEMANAS_FIRMES = 16
SEASON_PLANNER_USER_PROMPT = (
    SEASON_PLANNER_USER_PROMPT
    .replace("__SEMANAS_HASTA_LA_A__", str(SEMANAS_HASTA_LA_A))
    .replace("__SEMANAS_FIRMES__", str(SEMANAS_FIRMES))
)




def _macrociclo_terminado(phases_json: str, hoy: date | None = None) -> bool:
    """Si la ultima semana del macrociclo guardado ya paso.

    Se mide con `season_start_date` y el `end_week` mas alto, que es como
    `compute_mesocycle_position` sabe en que semana va el atleta. Sin fecha o
    sin fases, se considera vivo: no saberlo no es razon para tirar un plan.
    """
    try:
        datos = json.loads(phases_json or "{}") or {}
        inicio = datetime.strptime(str(datos.get("season_start_date"))[:10], "%Y-%m-%d").date()
        ultima = max(int(f.get("end_week") or 0) for f in (datos.get("phases") or []))
    except (ValueError, TypeError, json.JSONDecodeError):
        return False
    if ultima <= 0:
        return False
    semana_actual = ((hoy or date.today()) - inicio).days // 7 + 1
    return semana_actual > ultima


def _macrociclo_por_revisar(phases_json: str, hoy: date | None = None) -> bool:
    """Si el atleta ya pasó las SEMANAS_FIRMES de su macrociclo.

    Desde el 2026-09-13 un macrociclo llega hasta la carrera A y sólo sus 16
    primeras semanas son firmes; la app pide rehacerlo al pasarlas. Pero rehacer
    un macrociclo que no ha terminado caía en el bloque de REFINAR: "no empieces
    de cero, conserva la estructura y `season_start_date` EXACTAMENTE". La
    revisión se volvía un visto bueno al mapa provisional y, con la fecha vieja,
    el atleta seguía en la semana 17: el aviso no se iba nunca y las fases
    posteriores seguían marcadas como provisionales.
    """
    try:
        datos = json.loads(phases_json or "{}") or {}
        inicio = datetime.strptime(str(datos.get("season_start_date"))[:10], "%Y-%m-%d").date()
    except (ValueError, TypeError, json.JSONDecodeError):
        return False
    return ((hoy or date.today()) - inicio).days // 7 + 1 > SEMANAS_FIRMES


def con_semana_del_macrociclo(competiciones: list, inicio_iso: str) -> list:
    """Cada carrera con la semana del macrociclo en la que cae (la 1 empieza en `inicio_iso`).

    Macrociclo de la atleta 3 del 2026-09-15, que arranca ese martes: su trail B
    de 12 km (+600 m) es el sábado 2026-11-21, semana 10, y la fase "Trail corto
    (B): cuestas + tolerancia excéntrica" empezó en la semana 10 -- la
    especificidad, la semana de la carrera. El modelo sólo recibía fechas y
    hacía la cuenta él. Devuelve copias; una fecha que no se entiende va sin semana.

    Una carrera anterior a la semana 1 no tiene semana: el 5K del atleta 2 (2026-09-20) llegaba
    al macrociclo del 2026-09-23 como "semana_del_macrociclo": 0. Va marcada `ya_corrida`.
    """
    try:
        inicio = datetime.strptime(str(inicio_iso)[:10], "%Y-%m-%d").date()
    except ValueError:
        return competiciones
    salida = []
    for comp in competiciones:
        if not isinstance(comp, dict):
            salida.append(comp)
            continue
        try:
            fecha = datetime.strptime(str(comp.get("date"))[:10], "%Y-%m-%d").date()
        except ValueError:
            salida.append(comp)
            continue
        if fecha < inicio:
            salida.append({**comp, "ya_corrida": True})
        else:
            salida.append({**comp, "semana_del_macrociclo": (fecha - inicio).days // 7 + 1})
    return salida


def fases_que_empiezan_en_su_carrera(fases, competiciones_con_semana: list) -> list[str]:
    """Fases que arrancan la semana de una carrera y siguen después de ella.

    Es la forma del fallo del macrociclo de Jessie (2026-09-15): "Trail corto
    (B): cuestas + tolerancia excéntrica", semanas 10-12, con el trail en la 10.
    La fase de afinamiento y carrera de una A (31-31) no sale: acaba en su
    carrera. Un aviso para el log del worker, no una puerta, como
    `number_audit`: decidir qué hacer con él es criterio.
    """
    def campo(fase, clave):
        return fase.get(clave) if isinstance(fase, dict) else getattr(fase, clave, None)

    avisos = []
    for comp in competiciones_con_semana or []:
        semana = comp.get("semana_del_macrociclo") if isinstance(comp, dict) else None
        if not isinstance(semana, int) or semana <= 1:
            continue
        for fase in fases or []:
            inicio, fin = campo(fase, "start_week"), campo(fase, "end_week")
            if inicio == semana and isinstance(fin, int) and fin > semana:
                avisos.append(
                    f"la fase '{campo(fase, 'name')}' (semanas {inicio}-{fin}) empieza la semana de "
                    f"{comp.get('name')} (semana {semana}): lo específico de esa carrera tendría que ir antes"
                )
    return avisos


_PALABRAS_QUE_CONFUNDEN = re.compile(r"\b(firmes?|provisional(?:es)?|preliminar(?:es)?|tentativ[oa]s?)\b", re.I)


def palabras_de_duda(markdown: str) -> list[str]:
    """"Firme", "provisional", "preliminar" en el texto que lee el atleta (LINT, no puerta).

    El responsable, 2026-10-09: confundían al atleta; el prompt pide en su lugar la línea
    «Fases posteriores por confirmar según tu progresión:». Se anota en el `worker.log` para
    ver si el modelo lo cumple; no se reescribe el texto.
    """
    return sorted({m.group(1).lower() for m in _PALABRAS_QUE_CONFUNDEN.finditer(markdown or "")})


def _fijar_arranque(contenido, hoy_iso: str, refinando: bool) -> None:
    """La fecha de arranque la pone el código cuando el plan es nuevo.

    El modelo recibía "usa la fecha de hoy" en un sitio y "consérvala
    EXACTAMENTE" en otro. Todo lo que cuelga de esa fecha -- en qué semana va
    el atleta, qué fase es provisional, cuándo sale el aviso -- no puede
    depender de cuál de las dos instrucciones obedezca. Sólo al refinar un
    macrociclo vigente se conserva la que tenía.
    """
    if not refinando and hoy_iso and hasattr(contenido, "season_start_date"):
        contenido.season_start_date = hoy_iso


def _format_previous_season(state: dict) -> str:
    """El macrociclo que este plan reemplaza.

    Este nodo recibía la ADHERENCIA -- qué se le prescribió al atleta y qué
    cumplió -- pero no el plan que generó esas prescripciones. Auditado sobre
    un prompt real de 34.948 caracteres: ni el nombre de una fase anterior, ni
    sus horas objetivo, ni una línea de su narrativa. Así el macrociclo número
    dos podía devolver a base a alguien que iba por la semana 14 de
    construcción, o contradecir al anterior sin decirlo.

    Ver services/ai/previous_season.py para qué se manda y qué no.
    """
    block = ((state.get("availability_constraints") or {}).get("previous_season") or {}).get("block") or ""
    return f"\n{block}\n" if block else ""


def _format_track_record(state: dict) -> str:
    """El expediente: qué se le prescribió a este atleta y cuánto cumplió.

    La adherencia que ya recibía este nodo mide contra `weekly_plan.json`, que
    desde la generación semana a semana contiene UNA semana: el macrociclo
    decidía dieciséis semanas mirando siete días. Esto le da el patrón de
    varios meses en unos cientos de bytes por semana, sin mandarle el
    historial archivado (que pesa ~14 MB al año y del que casi todo son pasos
    de entrenamiento que ya no le importan a nadie).
    """
    block = ((state.get("availability_constraints") or {}).get("track_record") or {}).get("block") or ""
    return f"\n{block}\n" if block else ""


def _format_training_habits(state: dict) -> str:
    """Los habitos reales del atleta, para el macrociclo.

    El mismo texto que ve el planificador semanal, por la misma razon por la
    que el cuaderno se comparte: si el macro y la semana leyeran retratos
    distintos del atleta podrian contradecirse sobre un hecho medido.

    Aqui pesa incluso mas que en la semana. Un macrociclo decide la proporcion
    entre deportes para dieciseis semanas, y hacerlo sobre las horas que el
    atleta DECLARO libres en vez de sobre las que de verdad entrena reparte la
    temporada entre deportes que no practica.
    """
    block = ((state.get("availability_constraints") or {}).get("habits") or {}).get("block") or ""
    return f"\n{block}\n" if block else ""


def _format_notebook(state: dict) -> str:
    """El cuaderno del entrenador, para el prompt de temporada.

    Mismo canal lateral y mismo texto que ve el planificador semanal: si los
    dos leyeran versiones distintas de lo mismo, el macrociclo y la semana
    podrían contradecirse sobre un hecho que el atleta contó una sola vez.
    """
    cuaderno = (state.get("availability_constraints") or {}).get("notebook") or {}
    if "peticiones" not in cuaderno:  # un estado escrito antes del 2026-09-23
        block = cuaderno.get("block") or ""
        return f"\n{block}\n" if block else ""
    # Las «Preferencias de entrenamiento» van como petición, delante y aparte del
    # resto, que sigue siendo lo que el atleta cuenta y se contrasta con Garmin.
    partes = [cuaderno.get("peticiones") or "", cuaderno.get("block_sin_peticiones") or ""]
    texto = "\n\n".join(p for p in partes if p)
    return f"\n{texto}\n" if texto else ""


def _format_volume_baseline(baseline: dict | None) -> str:
    """Lo que el atleta absorbe de verdad, con la CARGA delante y las horas detras.

    Hasta la v1.2 este bloque decia "Carga crónica: **6.6 h/semana** ... Este
    es el ancla de la progresión": llamaba carga a las horas y le decia al
    macrociclo que las anclara. Asi salian fases de "6.0 h" y "7.0 h", y una
    semana de taper heredando las 6 h de su fase.

    Las horas no son carga. En la cuenta real del atleta 2 una hora de bici
    suave cargo ~6 y una de series ~82, y la semana de mas carga del trimestre
    fue de 5,5 h. Ver services/ai/weekly_load.py.
    """
    if not baseline:
        return (
            "**Carga real**: sin datos suficientes de Garmin para establecer una base. "
            "No inventes una carga objetivo: dilo y construye las fases sobre lo que los expertos "
            "sí pudieron observar."
        )

    lines = ["**Carga real del atleta (unidades de carga de Garmin, las de su reloj)**:"]
    cronica = baseline.get("garmin_chronic_load") or baseline.get("chronic_weekly_load")
    if cronica:
        fuente = "Garmin" if baseline.get("garmin_chronic_load") else "suma de sus actividades"
        lines.append(f"- Carga crónica semanal: {round(cronica)} (según {fuente}): dónde está hoy.")
    if baseline.get("garmin_optimal_min") and baseline.get("garmin_optimal_max"):
        lines.append(f"- Garmin considera óptima para este atleta una carga semanal entre "
                     f"{round(baseline['garmin_optimal_min'])} y {round(baseline['garmin_optimal_max'])} "
                     "(0,8–1,5 veces su crónica). Es un cálculo de Garmin, no un límite probado en este atleta.")
    aguda = baseline.get("garmin_acute_load") or baseline.get("last_7d_load")
    if aguda:
        lines.append(f"- Carga de los últimos 7 días: {round(aguda)}.")
    horas = baseline.get("chronic_weekly_hours")
    window = baseline.get("chronic_window_days", 28)
    if horas:
        lines.append(f"- Como referencia de agenda, no de carga: {horas} h/semana de media en {window} días.")
        # Partidas, porque `weekly_hours_target` son horas de resistencia: la fuerza
        # no se las resta (Fernando, 2026-09-22: ver server._horas_semanales_por_tipo).
        por_tipo = baseline.get("chronic_hours_by_kind") or {}
        if por_tipo.get("resistencia"):
            lines.append(
                f"  De ellas, ~{por_tipo['resistencia']} h de natación, bici y carrera"
                + (f" y ~{por_tipo['fuerza']} h de fuerza" if por_tipo.get("fuerza") else "") + "."
            )
    # Cómo reparte su intensidad, medido por sesión. "" sin zonas o sin
    # muestras: el macrociclo no recibe un reparto inventado.
    reparto = render_distribucion(baseline.get("intensity_distribution"))
    if reparto:
        lines.append("\n" + reparto)
    return "\n".join(lines)


async def season_planner_node(state: TrainingAnalysisState) -> dict[str, list | str]:
    logger.info("Starting season planner node")

    hitl_enabled = state.get("hitl_enabled", True)
    logger.info("Season planner node: HITL %s", "enabled" if hitl_enabled else "disabled")

    agent_start_time = datetime.now()

    tools = configure_node_tools(
        agent_name="season_planner",
        plot_storage=None,
        plotting_enabled=False,
    )

    # La politica de conflictos llegaba a sintesis y al planificador SEMANAL, y
    # no aqui -- aunque este nodo consume los mismos tres payloads
    # `for_season_planner` de los mismos tres expertos, que se contradicen a
    # proposito. Sin ella, la contradiccion se resolvia en silencio y distinto
    # en cada corrida, sobre la decision de mas alcance del sistema: 16 semanas.
    system_prompt = (
        SEASON_PLANNER_SYSTEM_PROMPT +
        get_workflow_context("season_planner") +
        get_conflict_policy() +
        (get_hitl_instructions("season_planner") if hitl_enabled else "")
    )

    qa_messages_raw = state.get("season_planner_messages", [])
    qa_messages = []
    for msg in qa_messages_raw:
        if hasattr(msg, "type"):
            role = "assistant" if msg.type == "ai" else "user"
            qa_messages.append({"role": role, "content": msg.content})
        else:
            qa_messages.append(msg)

    existing_season_plan = ""
    existing_season_phases_json = ""
    try:
        storage = FilePlanStorage()
        loaded_plan = storage.load_plan(state["user_id"], "season_plan")
        if loaded_plan:
            # Sin las respuestas que el atleta le dio al coach del macro
            # anterior: eran de ese plan y no pasan al siguiente.
            existing_season_plan = sin_respuestas_al_coach(loaded_plan)
        loaded_phases = storage.load_plan(state["user_id"], "season_phases", extension="json")
        if loaded_phases:
            existing_season_phases_json = loaded_phases
    except Exception as exc:
        logger.warning("Could not read existing season plan: %s", exc)

    existing_plan_block = ""
    # Sólo se REFINA un macrociclo vigente dentro de sus semanas firmes; en
    # cualquier otro caso el plan es nuevo y arranca hoy. Ver _fijar_arranque.
    refinando = False
    if existing_season_plan:
        # ¿Sigue vivo ese macrociclo, o ya se acabó?
        #
        # El bloque decía siempre "no empieces de cero, y conserva
        # `season_start_date` EXACTAMENTE como estaba", incluso sobre un plan
        # terminado -- que el bloque de "macrociclo anterior" describe como
        # **terminado** en la misma llamada. Obedecer lo primero deja al atleta
        # en la semana 26 de un plan de 24, o con un macro nuevo ya vencido el
        # día que se escribe. Auditoria previa al merge, 2026-09-11.
        if (_macrociclo_por_revisar(existing_season_phases_json)
                and not _macrociclo_terminado(existing_season_phases_json)):
            existing_plan_block = (
                "\n\n## Previous Season Plan (REVIEW POINT)\nThe athlete is past the first "
                f"{SEMANAS_FIRMES} weeks of their macrocycle: those were firm, and what follows was written as a "
                "provisional map before anyone knew how their body would respond. This is the planned re-plan, "
                "not a refinement. Write a NEW macrocycle starting THIS week, with a new `season_start_date` "
                "(the week-1 start given in the Inputs), running to the goal race. Continue the progression from the "
                "phase the athlete is in -- do not restart it -- and use how they responded to each phase "
                "(\"Cómo respondió a cada fase\" and the track record) to decide whether the provisional map "
                "still holds, needs adjusting, or needs changing. Say which, and why, in the markdown.\n\n"
                f"```markdown\n{existing_season_plan}\n```"
            )
        elif _macrociclo_terminado(existing_season_phases_json):
            existing_plan_block = (
                "\n\n## Previous Season Plan (FINISHED)\nThe athlete's previous macrocycle has run out: "
                "its last week is behind us. Write a NEW one, starting THIS week, with a new "
                "`season_start_date` (the week-1 start given in the Inputs). Use the plan below as history -- "
                "what was built, what was emphasised, what it assumed -- and continue from where that left "
                "the athlete instead of restarting their progression from scratch.\n\n"
                f"```markdown\n{existing_season_plan}\n```"
            )
        else:
            refinando = True
            existing_plan_block = (
                "\n\n## Existing Season Plan\nWe have an existing season plan. Do NOT start from scratch. "
                "Review this plan against the new expert insights. If the plan is still valid, maintain the "
                "phase structure and just refine the details. Only trigger a full replan if the new data "
                "suggests the old plan is dangerously off-track.\n\n"
                f"```markdown\n{existing_season_plan}\n```"
            )
        # Hand back the previous constraints so each one gets an explicit
        # verdict. A malformed phases file must not take the whole season
        # generation down -- the athlete would lose a paid run over a
        # nice-to-have block.
        try:
            previous_constraints = (json.loads(existing_season_phases_json or "{}") or {}).get("constraints") or []
        except json.JSONDecodeError:
            logger.warning("Could not parse existing season phases for constraint review")
            previous_constraints = []

        constraints_review = render_constraints_for_review(previous_constraints)
        if constraints_review:
            existing_plan_block += "\n\n" + constraints_review

        if existing_season_phases_json:
            existing_plan_block += (
                (
                    "\n\nExisting structured phases (JSON) — if you're refining rather than replanning, "
                    "**keep `season_start_date` EXACTLY as it was** (later weekly-plan generations rely on "
                    "this date staying fixed to know which real week of the season they're in):\n"
                    if refinando else
                    "\n\nThe previous plan's structured phases (JSON), as history only — the new plan starts "
                    "today and numbers its weeks from 1:\n"
                )
                + f"```json\n{existing_season_phases_json}\n```"
            )

    # Everything below already existed somewhere in the app and simply never
    # reached this node (v5 Track 2, hallazgos H1/H2).
    prescription_state = state.get("prescription") or {}
    prescription_card = prescription_state.get("card") or ""
    volume_baseline = (state.get("availability_constraints") or {}).get("volume_baseline")
    fuerza_guardada = (state.get("availability_constraints") or {}).get("fuerza") or {}
    adherence_block = (state.get("adherence") or {}).get("prompt_block") or ""
    # v6 Track 4. Three things the season planner used to evaluate a goal
    # without: the athlete's own pace bands (so the pace a target actually
    # demands can be put next to them), the distance inferred from what the
    # athlete already wrote (every competition saved before v5 Track 2 has an
    # empty `distance`, which is why goal realism reported "sin_referencia"
    # for all three of the athlete's races), and how much data the predictions
    # rest on. The last one is evidence, not a score: how much a thin data set
    # should widen the uncertainty around a goal is a judgement about this
    # athlete, and this node has the whole picture.
    goal_realism_block = render_goal_realism_block(
        assess_goals(
            state.get("competitions") or [],
            prescription_state.get("race_predictions") or [],
            pace_bands=prescription_state.get("pace_bands"),
        ),
        evidence=(state.get("availability_constraints") or {}).get("goal_evidence"),
        pace_labels=PACE_BAND_LABELS_ES,
    )

    # La semana 1 del plan que se va a escribir: la de siempre si se refina, hoy si no.
    # Alineado a SUS semanas: la semana 1 es la suya que contiene hoy. Ver
    # services/ai/semanas_del_macrociclo.py (atleta 2, 2026-09-21).
    su_semana_empieza = (state.get("availability_constraints") or {}).get("week_start_day")
    inicio_del_macrociclo = arranque_de_sus_semanas(
        (state.get("current_date") or {}).get("date", ""), su_semana_empieza)
    if refinando:
        try:
            inicio_del_macrociclo = (json.loads(existing_season_phases_json or "{}") or {}).get(
                "season_start_date") or inicio_del_macrociclo
        except (json.JSONDecodeError, AttributeError):
            pass

    base_messages = [
        {"role": "system", "content": system_prompt},
        {"role": "user", "content": SEASON_PLANNER_USER_PROMPT.format(
            athlete_name=state["athlete_name"],
            current_date=json.dumps(con_fecha_escrita([state["current_date"]])[0], indent=2, ensure_ascii=False),
            inicio_del_macrociclo=inicio_del_macrociclo or "(sin fecha)",
            competitions=json.dumps(con_fecha_escrita(con_semana_del_macrociclo(
                state["competitions"], inicio_del_macrociclo)), indent=2, ensure_ascii=False),
            planning_context=state.get("planning_context") or "(sin contexto de planificación)",
            prescription_card=prescription_card or "(sin ficha de prescripción disponible)",
            # Beta 2.1 (S7): para qué hace gimnasio, dicho por él. "" si no contestó,
            # y entonces este prompt no cambia ni un carácter.
            fuerza_declarada=render_para_el_macrociclo(
                leer_perfil_de_fuerza(fuerza_guardada), leer_rutina_de_fuerza(fuerza_guardada)),
            volume_baseline=_format_volume_baseline(volume_baseline),
            load_state=SEPARADOR_DE_BLOQUES.join(b for b in (
                ((state.get("availability_constraints") or {}).get("load_state") or {}).get("block") or "",
                ((state.get("availability_constraints") or {}).get("training_response") or {}).get("block") or "",
            ) if b),
            adherence=adherence_block or "**Adherencia**: aún no hay un plan previo contra el que medir.",
            goal_realism=goal_realism_block or "**Metas**: el atleta no declaró tiempos objetivo.",
            # v7: el cuaderno también aquí, y no sólo en el planificador
            # semanal. El cuaderno guarda por definición HECHOS DURADEROS
            # ("desde marzo tomo esta medicación", "los martes entro a las
            # 5am", "la piscina cierra en agosto", "me operan en noviembre"),
            # y duradero es exactamente la escala de este nodo: son cosas que
            # condicionan la FORMA de las 16 semanas, no la táctica de una.
            #
            # La semana lo usa para decidir qué sesión va qué día; la
            # temporada, para decidir si una fase puede apoyarse en natación,
            # si un bloque de carga cae sobre una temporada de viajes, o si un
            # dato fisiológico hay que leerlo con una medicación de por medio.
            # Dárselo sólo al semanal dejaba al macrociclo planificando sobre
            # un atleta que no existe -- y el macrociclo es el que manda.
            # Al REFINAR no: el plan vigente ya va entero en "Existing Season Plan", con
            # la orden de conservar sus fases, y este bloque lo repetía como "el que este
            # plan reemplaza" -- dos órdenes contrarias sobre el mismo plan y ~10.000
            # caracteres pagados dos veces (macrociclo del atleta 2, 2026-09-23).
            previous_season="" if refinando else _format_previous_season(state),
            track_record=_format_track_record(state),
            # v1.2.2: cómo respondió a cada fase cerrada. "" sin fases medidas.
            phase_response=(lambda b: f"\n{b}\n" if b else "")(
                ((state.get("availability_constraints") or {}).get("phase_response") or {}).get("block") or ""
            ),
            training_habits=_format_training_habits(state),
            coach_notebook=_format_notebook(state),
            metrics_insights=extract_expert_output(state.get("metrics_outputs"), "for_season_planner"),
            activity_insights=extract_expert_output(state.get("activity_outputs"), "for_season_planner"),
            physiology_insights=extract_expert_output(state.get("physiology_outputs"), "for_season_planner"),
        ) + existing_plan_block},
    ]

    base_llm = ModelSelector.get_llm(AgentRole.SEASON_PLANNER)

    llm_with_tools = base_llm.bind_tools(tools) if tools else base_llm
    llm_with_structure = llm_with_tools.with_structured_output(SeasonPlannerOutputs)

    async def call_season_planning():
        messages_with_qa = base_messages + qa_messages
        if tools:
            return await handle_tool_calling_in_node(
                llm_with_tools=llm_with_structure,
                messages=messages_with_qa,
                tools=tools,
                max_iterations=15,
            )
        else:
            return await llm_with_structure.ainvoke(messages_with_qa)

    async def node_execution():
        with get_usage_metadata_callback() as usage_cb:
            agent_output = await retry_with_backoff(
                call_season_planning, AI_ANALYSIS_CONFIG, "Season Planning"
            )

        execution_time = (datetime.now() - agent_start_time).total_seconds()
        log_node_completion("Season planning", execution_time)

        # Lo que el atleta contesto al coach en esta generacion, al final del
        # informe: ahi lo lee la semana y ahi lo ve el atleta. Escrito por
        # codigo, no pedido al modelo, para que las respuestas salgan literales.
        seccion = seccion_de_respuestas(state.get("respuestas_al_coach") or [],
                                        (state.get("current_date") or {}).get("date", ""))
        contenido = getattr(agent_output, "output", None)
        if isinstance(contenido, SeasonPlanContent):
            _fijar_arranque(contenido, arranque_de_sus_semanas(
                (state.get("current_date") or {}).get("date", ""), su_semana_empieza), refinando)
            for aviso in fases_que_empiezan_en_su_carrera(contenido.phases, con_semana_del_macrociclo(
                    state.get("competitions") or [], contenido.season_start_date or inicio_del_macrociclo)):
                logger.warning("Macrociclo: %s", aviso)
            if palabras_de_duda(contenido.markdown):
                logger.warning("Macrociclo: el texto usa %s (el prompt pide el separador de fases por confirmar)",
                               ", ".join(palabras_de_duda(contenido.markdown)))
                from core import redes_ledger
                redes_ledger.anotar("macrociclo_palabras_de_duda", ", ".join(palabras_de_duda(contenido.markdown)))
            from services.ai.texto_del_atleta import sin_alfabetos_ajenos
            contenido.markdown = sin_alfabetos_ajenos(contenido.markdown, "macrociclo")
        if seccion and isinstance(contenido, SeasonPlanContent):
            contenido.markdown = sin_respuestas_al_coach(contenido.markdown).rstrip() + seccion

        return {
            "season_plan": agent_output.model_dump(),
            "costs": [create_cost_entry("season_planner", execution_time, usage_cb.usage_metadata)],
        }

    return await execute_node_with_error_handling(
        node_name="Season planner",
        node_function=node_execution,
        error_message_prefix="Season planning failed",
    )
