"""The athlete's current recovery state, as numbers the planner can reason with.

Why this module exists
----------------------
Plan Maestro v4 Track E. HRV, resting HR and sleep were already being pulled
from Garmin, but the only form in which any of it reached the weekly planner
was a sentence written by the physiology expert. Two independent audits of a
real generation landed on the same consequence: with no individual baseline to
anchor to, the planner invented absolute HRV cutoffs in its own prose ("HRV
optimal zone 56.0-82.0 ms", "if HRV drops below 56ms, reduce sessions") that
nothing in this app had ever computed or validated for this athlete.

The fix is not a rule that punishes the model for guessing -- it is giving it
the real numbers so it has no reason to guess.

What this deliberately does NOT do
----------------------------------
It does not automatically downgrade a session when HRV is low. An earlier
draft of Track E proposed exactly that, and it was dropped on purpose:

- HRV is meaningfully interpretable only against an individual's own rolling
  baseline and multi-day trend, never an absolute threshold -- which is why
  this module reports Garmin's own baseline-relative status rather than
  inventing a cutoff.
- A single abnormal night is not evidence of anything much on its own; it
  needs corroboration from resting HR, sleep, recent load and how the athlete
  actually feels.
- Evidence that HRV-guided training improves outcomes is real but modest, and
  depends heavily on measurement and baseline methodology.

So this hands over evidence, clearly framed, and leaves the judgment where a
model reasoning over the athlete's full context can make it. A deterministic
override here would be a guardrail that fires on a noisy single signal.
"""

from __future__ import annotations

import logging
from dataclasses import dataclass, field
from typing import Any

logger = logging.getLogger(__name__)

# Garmin's own status values, mapped to plain Spanish for the prompt. These
# are already computed against THIS athlete's personal baseline, which is
# exactly the property that makes them usable and an absolute cutoff not.
_HRV_STATUS_ES = {
    "BALANCED": "equilibrado (dentro de su rango normal)",
    "UNBALANCED": "fuera de su rango normal",
    "LOW": "por debajo de su rango normal",
    "POOR": "muy por debajo de su rango normal",
}


@dataclass
class RecoverySnapshot:
    hrv_last_night: float | None = None
    hrv_weekly_avg: float | None = None
    hrv_status: str | None = None
    hrv_baseline_low: float | None = None
    hrv_baseline_high: float | None = None
    resting_heart_rate: int | None = None
    warnings: list[str] = field(default_factory=list)

    @property
    def has_data(self) -> bool:
        return any((
            self.hrv_last_night, self.hrv_weekly_avg, self.hrv_status, self.resting_heart_rate,
        ))


def build_recovery_snapshot(garmin_data) -> RecoverySnapshot:
    """Pulls the recovery signals out of the extracted Garmin data.

    Tolerant by design: every field is optional, because an athlete with a
    watch that doesn't measure HRV, or a night without a recording, is a
    normal case and must not break plan generation.
    """
    markers = getattr(garmin_data, "physiological_markers", None)
    if markers is None:
        return RecoverySnapshot(warnings=["No hay marcadores fisiológicos disponibles."])

    hrv: dict[str, Any] = getattr(markers, "hrv", None) or {}
    baseline: dict[str, Any] = hrv.get("baseline") or {}

    snapshot = RecoverySnapshot(
        hrv_last_night=hrv.get("last_night_avg"),
        hrv_weekly_avg=hrv.get("weekly_avg"),
        hrv_status=(hrv.get("status") or None),
        hrv_baseline_low=baseline.get("balanced_low"),
        hrv_baseline_high=baseline.get("balanced_upper"),
        resting_heart_rate=getattr(markers, "resting_heart_rate", None),
    )

    if snapshot.hrv_last_night and not (snapshot.hrv_baseline_low and snapshot.hrv_baseline_high):
        # Without the athlete's own balanced range there is nothing legitimate
        # to compare a raw number against -- say so rather than let a reader
        # (human or model) fill the gap with a remembered population cutoff.
        snapshot.warnings.append(
            "Hay valor de HRV pero no el rango base personal de este atleta: "
            "interpreta el número solo como tendencia, nunca contra un umbral fijo."
        )
    return snapshot


def render_recovery_block(snapshot: RecoverySnapshot | None) -> str:
    """The prompt block. Returns "" when there is nothing real to report, so
    the planner sees no section at all rather than a section full of "N/A"
    that invites it to reason about absent data."""
    if not snapshot or not snapshot.has_data:
        return ""

    lines = ["\n### Estado de recuperación actual (datos reales de Garmin, de hoy)"]

    if snapshot.hrv_last_night:
        line = f"- HRV anoche: **{snapshot.hrv_last_night:.0f} ms**"
        if snapshot.hrv_baseline_low and snapshot.hrv_baseline_high:
            line += (
                f" — el rango normal PROPIO de este atleta es "
                f"{snapshot.hrv_baseline_low:.0f}-{snapshot.hrv_baseline_high:.0f} ms"
            )
        lines.append(line + ".")
    if snapshot.hrv_weekly_avg:
        lines.append(f"- HRV promedio de los últimos 7 días: {snapshot.hrv_weekly_avg:.0f} ms.")
    if snapshot.hrv_status:
        label = _HRV_STATUS_ES.get(snapshot.hrv_status.upper(), snapshot.hrv_status)
        lines.append(f"- Estado de HRV según Garmin (ya calculado contra su base personal): **{label}**.")
    if snapshot.resting_heart_rate:
        lines.append(f"- Frecuencia cardiaca en reposo: {snapshot.resting_heart_rate} ppm.")

    lines.extend(f"- ⚠️ {w}" for w in snapshot.warnings)

    lines.append(
        "\n**Cómo usar esto**: el rango de arriba es el rango base PROPIO de este atleta, no una zona "
        "óptima general — nunca lo presentes como si aplicara a cualquiera (\"la zona óptima de HRV es "
        "X-Y ms\"). **Una regla de ajuste SÍ es legítima si es suya**: anclada a este rango o a su "
        "promedio de 7 días, que pide persistencia (varios días fuera de su rango, no una noche), que otra "
        "señal lo confirme (pulso en reposo por encima de lo suyo, sueño, cómo dice sentirse) y con una "
        "respuesta proporcionada (bajar la intensidad antes que cancelar). Así se guía el entrenamiento por "
        "HRV en la literatura. Lo que no vale es un umbral de población, un número que no sale de su rango, "
        "o cambiar la semana por una sola noche. Si decides ajustar por recuperación, dilo explícitamente "
        "en el `purpose` del día y nombra la señal que lo motivó."
    )
    return "\n".join(lines) + "\n"


def recovery_to_state(snapshot: RecoverySnapshot | None) -> dict:
    """Serialize for LangGraph state, which carries plain dicts."""
    if snapshot is None:
        return {}
    return {
        "hrv_last_night": snapshot.hrv_last_night,
        "hrv_weekly_avg": snapshot.hrv_weekly_avg,
        "hrv_status": snapshot.hrv_status,
        "hrv_baseline_low": snapshot.hrv_baseline_low,
        "hrv_baseline_high": snapshot.hrv_baseline_high,
        "resting_heart_rate": snapshot.resting_heart_rate,
        "warnings": list(snapshot.warnings),
        "block": render_recovery_block(snapshot),
    }
