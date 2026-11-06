"""How far back each kind of generation reads, and why.

Plan Maestro v5 Track 12.

These used to be two number inputs in the profile form ("Días de actividades",
"Días de métricas fisiológicas") under "Ajustes avanzados". That is a technical
decision dressed up as a preference: the athlete has no way to know which
number is right, and the number governs both the quality of the analysis and
the cost of every run -- in one measured real generation the activity
summarizer alone consumed 39,272 of 102,389 tokens, so the window is a direct
cost lever.

**The bug this module exists to make impossible.** `build_volume_baseline`
asked for 28 days of activities and divided by 4 to get chronic weekly hours,
while the week generation only fetched 21 days. The chronic baseline was
computed over a window that had never been fetched and came out understated by
up to 25% -- and that number is exactly what anchors the weekly volume target
(v4 Track F). Extraction window and computation window now come from the same
constants, and a test asserts the extraction window can never be shorter than
the load window it feeds.

## Where the numbers come from

**28 days chronic / 7 days acute.** The standard windows in acute:chronic
workload work: the last 28 days stand in for "fitness", the last 7 for
"fatigue". They are used here to describe the athlete's real recent load, NOT
to compute an ACWR number and act on a threshold -- the ratio's value as an
injury predictor is genuinely contested, and this project does not build rules
on contested findings.

**180 days of activities for the macrocycle.** Long enough to see a full cycle
of training blocks and any seasonality in how the athlete actually trains.
Beyond roughly six months, detraining and changes in life circumstances make
old sessions weak evidence about *current* capacity, and the longer arc
already reaches the agents cheaply through `get_race_predictions` and the
VO2max trend -- without paying to summarize every individual activity.

**90 days of metrics for the macrocycle, 15 for the week.** Long enough for a
physiological trend at season scale; short enough at week scale that a stale
average does not blur the recovery reading the week is supposed to respond to.

None of these are laws. They are documented defaults with a stated reason,
the same way `_DEFAULT_RAMP_ALLOWANCE` is in weekly_planner_node.py.
"""

from __future__ import annotations

from dataclasses import dataclass

# The two load windows the rest of the app computes against. Anything that
# fetches activities in order to describe recent load must cover at least
# CHRONIC_LOAD_WINDOW_DAYS.
CHRONIC_LOAD_WINDOW_DAYS = 28
ACUTE_LOAD_WINDOW_DAYS = 7

# Activities are fetched by calendar date range while the load computation
# filters on each activity's own start timestamp, so an exactly-equal window
# can clip its own oldest day on a timezone or rounding boundary. Two days of
# slack costs almost nothing and removes a whole class of off-by-one.
_BOUNDARY_MARGIN_DAYS = 2


@dataclass(frozen=True)
class ExtractionWindow:
    """How many days back to read, per data family."""

    activities_days: int
    metrics_days: int
    rationale: str = ""


# "Generar Plan" -- the macrocycle. Reasons in months.
SEASON_WINDOW = ExtractionWindow(
    activities_days=180,
    metrics_days=90,
    rationale=(
        "Seis meses de actividades para ver un ciclo completo de bloques y la estacionalidad; "
        "más atrás el desentrenamiento y los cambios de vida hacen que el dato diga poco sobre "
        "la capacidad actual. El arco largo ya llega por las predicciones de carrera y la "
        "tendencia de VO2max."
    ),
)

# "Generar Semana" -- the next 7 days. Reasons in weeks, and must cover the
# chronic window it feeds.
WEEK_WINDOW = ExtractionWindow(
    activities_days=CHRONIC_LOAD_WINDOW_DAYS + _BOUNDARY_MARGIN_DAYS,
    metrics_days=15,
    rationale=(
        "28 días de actividades es la ventana crónica estándar (la referencia de 'fitness' "
        "frente a los 7 días agudos de 'fatiga'), y es exactamente la que el cálculo de volumen "
        "ya pretendía usar. 15 días de métricas cubren la lectura de recuperación sin arrastrar "
        "un promedio viejo."
    ),
)
