from services.ai.ai_settings import AgentRole
from services.ai.langgraph.state.training_analysis_state import TrainingAnalysisState

from .data_summarizer_node import create_data_summarizer_node

ACTIVITY_SUMMARIZER_SYSTEM_PROMPT = """## Goal
Extract and structure training activity data with factual precision.
## Principles
- Be objective: Present data without interpretation.
- Be precise: Preserve exact metrics and units.
- Be structured: Use consistent formatting and transparent compression."""

ACTIVITY_SUMMARIZER_USER_PROMPT = """## Task
Objectively describe the athlete's recent training activities.

## Constraints
- STRICTLY NO interpretation or coaching advice.
- Use transparent compression for long tails (summarize repetitive patterns with windows or ranges).
- **Every number you write carries the unit it had in `unidades`.** Convert if
  you want to (m/s to min/km is welcome), but never relabel: writing "6.018
  km/h" for an `average_speed_ms` of 6.018 is a 3.6x error that reads as a
  plausible number.
- **Never collapse a session's interval structure.** If `laps` alternate
  ACTIVE/INTERVAL with RECOVERY/REST, that session's shape — how many reps,
  how long, at what pace and HR — is the single most decision-relevant thing
  in this payload, and compressing it to "sustained run" changes what the
  coach prescribes. Compress the easy tail, never the hard middle.

## Required Structure
1. **All Activities Table**: compact table for every activity (date, type, duration, distance, elevation, avg HR, avg pace/power).
2. **Key Sessions**: deep dives ONLY for key sessions (intensity/novelty/anomaly).
3. **Zone Distributions**: summarize distributions in tables.

## Input Data
```json
{data}
```

## Key Session Template
# Activity: [Date - Type]

## Overview
* Duration: [time]
* Distance: [distance]
* Elevation: [elevation]
* Avg HR: [HR] | Avg Pace/Power: [pace/power]

## Lap Details
| Lap | Dist | Time | Pace | Avg HR | Max HR | ... |
|-----|------|------|------|--------|--------|-----|
| 1   | ...  | ...  | ...  | ...    | ...    | ... |"""


# Lo que significa cada numero, dicho una vez en vez de adivinado 171 veces.
#
# No es documentacion: es el arreglo de un fallo medido. En la generacion real
# del 2026-09-10 la tabla del resumidor escribio **"6.018 km/h"** para una
# salida de 88,10 km en 4 h 04 -- 21,7 km/h de verdad. El numero era
# `average_speed` en m/s y el modelo le puso encima la unidad que le parecio
# razonable para una bici. Un dato sin unidad no es un dato: es una invitacion
# a suponer, y aqui suponer sale mal en la direccion que menos se nota.
#
# Las claves de los TRAMOS no estan aqui porque ya la llevan en el nombre
# (`duration_s`, `distance_m`, `averageSpeed_ms`) -- ver
# `data_extractor.get_activity_laps`.
UNIDADES = {
    "summary.distance": "metros",
    "summary.duration": "segundos (tiempo total)",
    "summary.moving_duration": "segundos (en movimiento)",
    "summary.elevation_gain": "metros",
    "summary.elevation_loss": "metros",
    "summary.average_speed_ms": "metros por segundo",
    "summary.max_speed_ms": "metros por segundo",
    "summary.average_hr": "pulsaciones por minuto",
    "summary.max_hr": "pulsaciones por minuto",
    "summary.min_hr": "pulsaciones por minuto",
    "summary.calories": "kilocalorias",
    "summary.moderate_intensity_minutes": "minutos, contados por Garmin",
    "summary.vigorous_intensity_minutes": "minutos, contados por Garmin",
    "summary.avg_power / max_power / normalized_power": "vatios",
    "summary.activity_training_load": "carga de entrenamiento de Garmin, sin unidad",
    "laps[].intensity": (
        "WARMUP / ACTIVE / INTERVAL / RECOVERY / REST / COOLDOWN, puesto por "
        "Garmin. Es lo que distingue una sesion de series de un rodaje"
    ),
    "start_time": "hora local del atleta",
}


def extract_activity_data(state: TrainingAnalysisState) -> dict:
    """Las actividades, con una leyenda de unidades delante.

    Devuelve un dict y no una lista a proposito: la leyenda tiene que viajar
    dentro del payload, no en el prompt, porque es el payload el que se archiva
    y el que se replica en `scripts/replay_node.py`.
    """
    activities = state["garmin_data"].get("recent_activities", [])
    if not isinstance(activities, list):
        activities = []
    return {"unidades": UNIDADES, "actividades": activities}


activity_summarizer_node = create_data_summarizer_node(
    node_name="Activity Summarizer",
    agent_role=AgentRole.SUMMARIZER,
    data_extractor=extract_activity_data,
    state_output_key="activity_summary",
    agent_type="activity_summarizer",
    system_prompt=ACTIVITY_SUMMARIZER_SYSTEM_PROMPT,
    user_prompt=ACTIVITY_SUMMARIZER_USER_PROMPT,
)
