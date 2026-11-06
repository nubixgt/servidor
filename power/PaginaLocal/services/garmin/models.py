import os
from dataclasses import dataclass, field
from enum import Enum
from typing import Any


class TimeRange(Enum):
    # Values are determined by AI_MODE environment variable
    RECENT = 7 if os.getenv("AI_MODE") == "development" else 21
    EXTENDED = 14 if os.getenv("AI_MODE") == "development" else 56
    LONG_TERM_RANGE = 360
    LONG_TERM_INTERVAL = 7


@dataclass
class ExtractionConfig:
    activities_range: int = TimeRange.RECENT.value
    metrics_range: int = TimeRange.EXTENDED.value
    include_detailed_activities: bool = True
    include_metrics: bool = True
    include_mindfulness: bool = True
    include_long_term_trends: bool = True
    long_term_range: int = TimeRange.LONG_TERM_RANGE.value
    long_term_interval: int = TimeRange.LONG_TERM_INTERVAL.value


@dataclass
class UserProfile:
    gender: str | None = None
    weight: float | None = None
    height: float | None = None
    birth_date: str | None = None
    activity_level: str | None = None
    vo2max_running: float | None = None
    vo2max_cycling: float | None = None
    lactate_threshold_speed: float | None = None  # Speed in m/s
    lactate_threshold_heart_rate: int | None = None
    ftp_auto_detected: bool | None = None
    available_training_days: list[str] | None = None
    preferred_long_training_days: list[str] | None = None
    sleep_time: str | None = None
    wake_time: str | None = None


@dataclass
class DailyStats:
    date: str | None = None
    total_steps: int | None = None
    total_distance_meters: float | None = None
    total_calories: int | None = None
    active_calories: int | None = None
    bmr_calories: int | None = None
    wellness_start_time: str | None = None
    wellness_end_time: str | None = None
    duration_in_hours: float | None = None
    min_heart_rate: int | None = None
    max_heart_rate: int | None = None
    resting_heart_rate: int | None = None
    average_stress_level: float | None = None
    max_stress_level: int | None = None
    stress_duration_seconds: int | None = None
    sleeping_seconds: int | None = None
    sleeping_hours: float | None = None
    respiration_average: float | None = None
    respiration_highest: float | None = None
    respiration_lowest: float | None = None


@dataclass
class ActivitySummary:
    distance: float | None = None
    duration: int | None = None
    moving_duration: int | None = None
    elevation_gain: float | None = None
    elevation_loss: float | None = None
    average_speed_ms: float | None = None
    max_speed_ms: float | None = None
    calories: int | None = None
    average_hr: int | None = None
    max_hr: int | None = None
    min_hr: int | None = None
    activity_training_load: int | None = None
    moderate_intensity_minutes: int | None = None
    vigorous_intensity_minutes: int | None = None
    recovery_heart_rate: int | None = None
    # Respiration (for meditation/mindfulness-capable activities)
    avg_respiration_rate: float | None = None
    min_respiration_rate: float | None = None
    max_respiration_rate: float | None = None
    # Stress (for meditation/mindfulness-capable activities)
    start_stress: float | None = None
    end_stress: float | None = None
    avg_stress: float | None = None
    max_stress: float | None = None
    difference_stress: float | None = None
    # Power-related fields for cycling activities
    avg_power: float | None = None
    max_power: float | None = None
    normalized_power: float | None = None
    training_stress_score: float | None = None
    intensity_factor: float | None = None


@dataclass
class WeatherData:
    temp: float | None = None
    apparent_temp: float | None = None
    relative_humidity: float | None = None
    wind_speed: float | None = None
    weather_type: str | None = None


@dataclass
class HeartRateZone:
    zone_number: int | None = None
    secs_in_zone: int | None = None
    zone_low_boundary: int | None = None


@dataclass
class GarminHrZones:
    """The athlete's OWN configured HR zones, as Garmin applies them.

    Read back from real activities (see DataExtractor.get_garmin_hr_zones)
    rather than derived from a %-of-threshold formula. The formula in
    workout_builder.compute_zone_config put this athlete's running Z2 at
    143-157 bpm while their watch actually uses 139-152 -- close enough to
    look right in a plan and wrong enough to ruin the session.

    Zones are PER SPORT, and the difference is not subtle: the same athlete
    has running Z2 at 139-152 and swimming Z2 at 112-129. Sampling whichever
    activity happens to be most recent would silently prescribe a run against
    swim zones, so each sport is resolved from its own most recent activity.
    """

    by_sport: dict[str, dict[int, tuple[int, int | None]]] = field(default_factory=dict)
    """sport key ("run"/"bike"/"swim"/"other") -> {zone number (1-5): (low_bpm, high_bpm)}.
    The top zone's high is None (open-ended)."""
    source_activity_ids: dict[str, int] = field(default_factory=dict)

    # Running is the reference sport when a caller doesn't care which, since
    # it's the only one of the three where HR zones drive most prescriptions.
    _SPORT_PREFERENCE = ("run", "bike", "swim", "other")

    def for_sport(self, sport: str | None) -> dict[int, tuple[int, int | None]]:
        """Zones for a sport, falling back to the best available set."""
        if sport and sport in self.by_sport:
            return self.by_sport[sport]
        for candidate in self._SPORT_PREFERENCE:
            if candidate in self.by_sport:
                return self.by_sport[candidate]
        return {}

    def bounds(self, zone: int, sport: str | None = None) -> tuple[int, int | None] | None:
        return self.for_sport(sport).get(zone)

    @property
    def sports(self) -> list[str]:
        return [s for s in self._SPORT_PREFERENCE if s in self.by_sport]


@dataclass
class RacePrediction:
    """One of Garmin's current-fitness race-time predictions."""

    distance_key: str  # "5K" | "10K" | "half_marathon" | "marathon"
    distance_m: float
    time_seconds: int

    @property
    def pace_sec_per_km(self) -> float:
        return self.time_seconds / (self.distance_m / 1000.0)


@dataclass
class Activity:
    activity_id: int | None = None
    activity_type: str | None = None
    activity_name: str | None = None
    start_time: str | None = None
    summary: ActivitySummary | None = None
    weather: WeatherData | None = None
    hr_zones: list[HeartRateZone] | None = None
    laps: list[dict[str, Any]] | None = None  # Complex structure, keeping as Dict for now
    # El entreno programado con el que se hizo, si se hizo desde el reloj. Garmin
    # lo da en la LISTA de actividades (el detalle trae `associatedWorkoutId`
    # vacío). Es lo que empareja una sesión con su actividad sin adivinar nada:
    # ver services/ai/adherence.py y services/ai/test_de_umbral.py.
    workout_id: int | None = None


@dataclass
class PhysiologicalMarkers:
    resting_heart_rate: int | None = None
    vo2_max: float | None = None
    hrv: dict[str, Any] | None = None  # Complex nested structure, keeping as Dict for now


@dataclass
class BodyMetrics:
    weight: dict[str, Any] | None = None  # Complex nested structure with historical data
    hydration: list[dict[str, Any]] | None = None  # Complex structure with daily data


@dataclass
class RecoveryIndicators:
    date: str | None = None
    sleep: dict[str, Any] | None = None  # Complex nested structure
    stress: dict[str, Any] | None = None  # Complex nested structure


@dataclass
class TrainingStatus:
    vo2_max: dict[str, Any] | None = None
    acute_training_load: dict[str, Any] | None = None
    # v6 Track 2. Garmin's Load Focus -- how the last month's load split across
    # aerobic low / aerobic high / anaerobic, each against the target range
    # Garmin publishes for THIS athlete. It arrives in the same
    # get_training_status payload the app already fetches (under
    # mostRecentTrainingLoadBalance), so reading it costs nothing extra; before
    # this it was fetched and thrown away, and the planner never learned that
    # the athlete was over the aerobic-high ceiling.
    load_balance: dict[str, Any] | None = None
    # Garmin's Training Readiness. A list of snapshots through the day rather
    # than one value: it drops after a session and recomposes overnight, so
    # both the latest reading and the morning one are kept -- collapsing them
    # to one number would hide which of the two the athlete is looking at.
    training_readiness: dict[str, Any] | None = None


@dataclass
class GarminData:
    user_profile: UserProfile | None = None
    daily_stats: DailyStats | None = None
    recent_activities: list[Activity] | None = None
    all_activities: list[Activity] | None = None
    physiological_markers: PhysiologicalMarkers | None = None
    body_metrics: BodyMetrics | None = None
    recovery_indicators: list[RecoveryIndicators] | None = None
    training_status: TrainingStatus | None = None
    vo2_max_history: dict[str, list[dict[str, Any]]] | None = None
    training_load_history: list[dict[str, Any]] | None = None
    long_term_vo2_max_trend: dict[str, list[dict[str, Any]]] | None = None
    long_term_training_load_trend: list[dict[str, Any]] | None = None
    # Inputs for the prescription card the weekly planner actually reads
    # (services/ai/prescription.py): the athlete's real zones and Garmin's own
    # fitness-based race predictions, which the paces for every quality
    # session are anchored to.
    garmin_hr_zones: GarminHrZones | None = None
    race_predictions: list[RacePrediction] | None = None
    # Cuándo subió algo su reloj por última vez (ISO, UTC). Sin ella, una sesión hecha y
    # todavía sin sincronizar contaba como «no realizada» (ver adherence.dias_sin_sincronizar).
    ultima_sincronizacion: str | None = None
