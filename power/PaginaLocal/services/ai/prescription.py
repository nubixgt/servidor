"""The athlete's real numbers, turned into something the weekly planner can read.

Why this module exists
----------------------
The weekly planner's prompt used to receive only prose from the three expert
agents. It never saw the athlete's threshold heart rate, threshold pace, or
heart-rate zones -- so it could not prescribe a pace target even when a pace
target was the correct coaching decision. That is the single biggest reason
plans written through a Garmin MCP chat came out better than the app's: there,
the model simply asked for `get_lactate_threshold`, got 5:47/km, and wrote
"5:40-5:55/km" on the tempo blocks.

This module closes that gap. It turns GarminData into a compact block of
concrete numbers (`render_prescription_card`) that goes straight into the
planner prompt, and into a structured object (`AthletePrescription`) the
validator uses to check that prescribed paces are physiologically sane.

Modality rule encoded here
--------------------------
Quality work is prescribed BY PACE, easy work BY HEART RATE. On the first
repetitions of an interval session the heart hasn't risen into the target zone
yet, so an athlete chasing a bpm number over-exerts and destroys the stimulus
the session was designed to deliver. Warmup, cooldown, recovery, Z2 and long
runs are the opposite case: there, heart rate is the honest measure of the
internal load and pace is a distraction.
"""

from __future__ import annotations

import logging
from dataclasses import dataclass, field
from math import exp, log

from services.garmin.models import GarminData, GarminHrZones, RacePrediction

logger = logging.getLogger(__name__)


# Intensity classes, as multipliers of threshold pace (sec/km). A LARGER
# multiplier means a SLOWER pace, because the unit is seconds per kilometre.
#
# The bands deliberately overlap a little: they describe coaching intent, not
# hard partitions, and the planner picks an exact range inside them from the
# athlete's own data. `umbral_tempo` is centred on threshold itself, matching
# how the athlete's own reference workout was written (5:40-5:55/km against a
# 5:47/km threshold).
_PACE_CLASS_MULTIPLIERS: dict[str, tuple[float, float]] = {
    # class            (fastest, slowest) x threshold pace
    "repeticiones": (0.84, 0.90),
    "vo2max": (0.90, 0.96),
    "umbral_tempo": (0.98, 1.06),
    "maraton": (1.06, 1.12),
    "facil": (1.14, 1.26),
    "recuperacion": (1.26, 1.42),
}

# Cycling intensity as a fraction of FTP (Coggan's classic zones). Pace is
# meaningless on a bike -- wind, gradient and drafting move it around far more
# than effort does -- so a cyclist's quality work is prescribed by POWER when
# an FTP is known, and by heart rate when it isn't.
_POWER_CLASS_FRACTIONS: dict[str, tuple[float, float]] = {
    "anaerobico": (1.21, 1.50),
    "vo2max": (1.06, 1.20),
    "umbral": (0.91, 1.05),
    "tempo": (0.76, 0.90),
    "facil": (0.56, 0.75),
    "recuperacion": (0.30, 0.55),
}

# Natación: fracciones del ritmo umbral por 100 m (la CSS, "critical swim
# speed", que es el estándar del deporte).
#
# Por qué la natación necesita su propia tabla y no puede reutilizar nada:
#
# 1. **El ritmo de carrera no se traduce.** Un nadador de 1:45/100 m y un
#    corredor de 4:00/km pueden ser la misma persona o no tener nada que ver.
# 2. **La frecuencia cardiaca en el agua es otra.** En este atleta la Z2 de
#    natación va de 112 a 129 ppm y la de carrera de 140 a 153: veintiocho
#    pulsaciones de diferencia por la posición horizontal, la presión del agua
#    y la respiración forzada. Usar las zonas de carrera en el agua manda a
#    nadar en Z4 creyendo que es Z2.
# 3. **El ritmo que reporta Garmin en piscina NO es de fiar.** El reloj detecta
#    los virajes por acelerómetro, y un parón a media piscina, un viraje suave
#    o un largo con material le hacen contar un largo de más o de menos. Eso
#    produce parciales imposibles -- 15 s en 100 m es el caso típico -- que ni
#    se pueden usar para derivar un umbral ni deben creerse al leer una sesión.
#
# Por eso la referencia sale de un TEST que el atleta hace y declara, no de la
# media que calcula el reloj. Y si no la ha declarado, no se inventa: se
# prescribe por distancia, descanso y esfuerzo, que es como un entrenador de
# natación escribe una serie de todos modos.
_SWIM_CLASS_FRACTIONS: dict[str, tuple[float, float]] = {
    "velocidad": (0.88, 0.94),
    "vo2max": (0.94, 0.98),
    "umbral": (0.98, 1.02),
    "aerobico": (1.02, 1.08),
    "facil": (1.08, 1.16),
    "recuperacion": (1.16, 1.28),
}

_SWIM_CLASS_LABELS_ES: dict[str, str] = {
    "velocidad": "Velocidad (25-50 m)",
    "vo2max": "VO2max (100-200 m)",
    "umbral": "Umbral / CSS (200-400 m)",
    "aerobico": "Aeróbico (400 m+)",
    "facil": "Suave",
    "recuperacion": "Recuperación y técnica",
}

_POWER_CLASS_LABELS_ES: dict[str, str] = {
    "anaerobico": "Anaeróbico (sprints, 15-60\")",
    "vo2max": "VO2max (intervalos 3-5')",
    "umbral": "Umbral (FTP)",
    "tempo": "Tempo / sweet spot",
    "facil": "Fondo aeróbico",
    "recuperacion": "Recuperación",
}

_PACE_CLASS_LABELS_ES: dict[str, str] = {
    "repeticiones": "Repeticiones / velocidad (400-800m)",
    "vo2max": "VO2max (intervalos 3-5')",
    "umbral_tempo": "Umbral / tempo",
    "maraton": "Ritmo de maratón",
    "facil": "Fácil / aeróbico",
    "recuperacion": "Recuperación",
}

# Classes that must be prescribed by pace, and the ones that must be
# prescribed by heart rate. Consumed by the plan validator.
PACE_PRESCRIBED_CLASSES = frozenset({"repeticiones", "vo2max", "umbral_tempo", "maraton"})
HR_PRESCRIBED_CLASSES = frozenset({"facil", "recuperacion"})


def _format_100(sec_per_100m: float | None) -> str:
    """105.0 -> '1:45'. La natación se lee siempre por 100 m."""
    if not sec_per_100m or sec_per_100m <= 0:
        return "—"
    total = int(round(sec_per_100m))
    return f"{total // 60}:{total % 60:02d}"


def format_pace(sec_per_km: float | None) -> str:
    """347.2 -> '5:47'."""
    if not sec_per_km or sec_per_km <= 0:
        return "—"
    total = int(round(sec_per_km))
    return f"{total // 60}:{total % 60:02d}"


@dataclass
class PaceBand:
    """A pace target as a range. `fast` is the faster (smaller sec/km) end."""

    fast_sec_per_km: float
    slow_sec_per_km: float

    def __str__(self) -> str:
        return f"{format_pace(self.fast_sec_per_km)}-{format_pace(self.slow_sec_per_km)}/km"


@dataclass
class AthletePrescription:
    hr_zones: GarminHrZones | None = None
    hr_zones_source: str = "none"  # "garmin" | "formula" | "none"
    lactate_threshold_hr: int | None = None
    threshold_pace_sec_per_km: float | None = None
    # "garmin_lt" | "garmin_ajustado" | "race_prediction" | "manual" | "test" | "none"
    threshold_pace_source: str = "none"
    # El umbral de Garmin que se llevó al borde de lo que es un umbral (s/km), o None.
    # Ver _umbral_que_se_sostiene_una_hora: la ficha no puede seguir diciendo "medido".
    umbral_ajustado_desde: float | None = None
    # De dónde sale la FC umbral: "test" | "manual" | "garmin" | "none".
    lthr_source: str = "none"
    # El test de umbral que el atleta hizo y guardó (Database/023), por deporte.
    # Ver services/ai/test_de_umbral.py.
    threshold_tests: dict = field(default_factory=dict)
    # La FC umbral por deporte cuando se sabe: la de carrera es `lactate_threshold_hr`;
    # la de bici sólo sale de un test en bici.
    lthr_por_deporte: dict[str, int] = field(default_factory=dict)
    # Si la banda "Fácil" es la que declaró el atleta (Database/022) y no una derivada.
    facil_declarado: bool = False
    # Si las zonas de FC son iguales en todos los deportes: casi siempre las de
    # fábrica de Garmin (% de la FC máxima), no unas medidas.
    zonas_identicas: bool = False
    # Deporte -> la FC máxima de la que salen, cuando las zonas de ese deporte son
    # EXACTAMENTE las de fábrica de Garmin (50/60/70/80/90 % de la FC máxima).
    zonas_de_fabrica: dict[str, int] = field(default_factory=dict)
    race_predictions: list[RacePrediction] = field(default_factory=list)
    pace_bands: dict[str, PaceBand] = field(default_factory=dict)
    ftp_watts: float | None = None
    power_bands: dict[str, tuple[int, int]] = field(default_factory=dict)
    # Natación: el umbral por 100 m que el atleta declara de un test suyo, no
    # el que calcula el reloj. Ver _SWIM_CLASS_FRACTIONS.
    swim_threshold_pace_sec_per_100m: float | None = None
    swim_bands: dict[str, tuple[float, float]] = field(default_factory=dict)
    warnings: list[str] = field(default_factory=list)

    @property
    def has_pace_data(self) -> bool:
        return bool(self.pace_bands)

    @property
    def has_power_data(self) -> bool:
        return bool(self.power_bands)

    @property
    def has_hr_zones(self) -> bool:
        return bool(self.hr_zones and self.hr_zones.by_sport)


def _plausible_threshold(pace_sec_per_km: float, predictions: list[RacePrediction]) -> tuple[bool, str]:
    """Cross-check a threshold pace against Garmin's own race predictions.

    Threshold is roughly the pace an athlete can hold for an hour, so it must
    sit BETWEEN 5K pace and marathon pace, and can never be faster than 5K
    pace. This check is what would have caught the unit bug that had this
    athlete's threshold stored as 4:48/km -- faster than their predicted 5K.
    """
    by_key = {p.distance_key: p.pace_sec_per_km for p in predictions}
    fastest_ref = by_key.get("5K")
    slowest_ref = by_key.get("marathon")

    if fastest_ref and pace_sec_per_km < fastest_ref:
        return False, (
            f"el ritmo umbral ({format_pace(pace_sec_per_km)}/km) es MÁS RÁPIDO que el ritmo "
            f"de 5K que Garmin predice ({format_pace(fastest_ref)}/km), lo cual es imposible"
        )
    if slowest_ref and pace_sec_per_km > slowest_ref:
        return False, (
            f"el ritmo umbral ({format_pace(pace_sec_per_km)}/km) es MÁS LENTO que el ritmo "
            f"de maratón que Garmin predice ({format_pace(slowest_ref)}/km)"
        )
    return True, ""


_UNA_HORA_S = 3600.0
_EXPONENTE_DE_RIEGEL = 1.06


def _derive_threshold_from_predictions(predictions: list[RacePrediction]) -> float | None:
    """El ritmo que el atleta sostendría UNA HORA, leído entre sus predicciones.

    El umbral es, en la definición práctica de Daniels y de Friel, el ritmo de
    una carrera de unos 60 minutos. Antes se devolvía el ritmo de 10K a secas,
    que sólo es eso para quien corre el 10K en una hora: el atleta Hugo
    (2026-09-07), con un 10K predicho de 44:31, recibió 4:27/km como umbral y una
    banda de tempo que empezaba en 4:22, más rápida que su propio ritmo de 10K.
    Entre su 10K y su media (1:45:06), la hora cae a ~4:38/km.

    Se interpola en log-log -- la forma de la ley de Riegel -- entre las dos
    predicciones que rodean la hora; si todas quedan del mismo lado, se
    extrapola desde la más cercana con el exponente de Riegel.
    """
    ritmo = _ritmo_para_una_duracion(_UNA_HORA_S, predictions)
    return round(ritmo, 1) if ritmo else None


def _puntos(predictions: list[RacePrediction]) -> list[tuple[float, float]]:
    return sorted(
        (float(p.time_seconds), float(p.distance_m))
        for p in predictions or []
        if getattr(p, "time_seconds", None) and getattr(p, "distance_m", None)
    )


def _ritmo_para_una_duracion(segundos: float, predictions: list[RacePrediction]) -> float | None:
    """El ritmo (s/km) que su curva de predicciones da para un esfuerzo de esa duración.

    Log-log entre las dos predicciones que la rodean (la forma de Riegel); fuera
    de ellas, Riegel desde la más cercana.
    """
    puntos = _puntos(predictions)
    if not puntos:
        return None
    antes = [p for p in puntos if p[0] <= segundos]
    despues = [p for p in puntos if p[0] >= segundos]
    if antes and despues:
        (t1, d1), (t2, d2) = antes[-1], despues[0]
        fraccion = 0.0 if t2 == t1 else (log(segundos) - log(t1)) / (log(t2) - log(t1))
        metros = exp(log(d1) + fraccion * (log(d2) - log(d1)))
    else:
        t, d = antes[-1] if antes else despues[0]
        metros = d * (segundos / t) ** (1 / _EXPONENTE_DE_RIEGEL)
    return segundos / (metros / 1000.0)


def minutos_que_sostiene(ritmo_s_km: float, predictions: list[RacePrediction]) -> float | None:
    """Cuántos minutos de carrera da su curva de predicciones a ese ritmo. None sin predicciones."""
    if not _puntos(predictions) or not ritmo_s_km:
        return None
    bajo, alto = log(60.0), log(8 * 3600.0)
    for _ in range(60):  # bisección: el ritmo se hace más lento con la duración
        medio = (bajo + alto) / 2
        if _ritmo_para_una_duracion(exp(medio), predictions) < ritmo_s_km:
            bajo = medio
        else:
            alto = medio
    return exp(bajo) / 60.0


# Cuánto se sostiene un ritmo umbral. El umbral de lactato (o MLSS, o la FTP en
# bici) es el esfuerzo de "alrededor de una hora": su tiempo hasta el agotamiento
# medido va de ~30 a ~70 minutos según el atleta (Baron 2008; la FTP de Coggan,
# 40-70'), y Daniels define el ritmo T como el de una carrera de ~60 minutos. La
# ventana es ANCHA a propósito, para no corregir a nadie por un matiz: sólo se
# sale de ella un "umbral" que es de verdad otro esfuerzo.
#
# Atleta 2, 2026-09-23: Garmin le daba 5:53/km de umbral, que según sus propias
# predicciones es su ritmo de ~2 horas (su media maratón, 2:04:35, va a 5:54). Pasaba
# el filtro de arriba (entre el 5K y el maratón) y de ahí salían un tempo a 5:46-6:14
# (casi su ritmo de maratón) y unas series de VO2max más lentas que su 5K real.
VENTANA_DE_UMBRAL_MIN = (25.0, 90.0)


def _numero(valor) -> float | None:
    try:
        v = float(valor)
    except (TypeError, ValueError):
        return None
    return v if v > 0 else None


def _coincide(valor_del_test, valor_del_perfil, tolerancia: float) -> bool:
    """Si el valor del perfil es el que salió del test (con el redondeo de la pantalla)."""
    a, b = _numero(valor_del_test), _numero(valor_del_perfil)
    return a is not None and b is not None and abs(a - b) <= tolerancia


# Las zonas que trae Garmin de fábrica: suelos de Z2-Z5 al 60/70/80/90 % de la FC
# máxima (la Z1 empieza en el 50 %).
_PORCENTAJES_DE_FABRICA = {2: 0.60, 3: 0.70, 4: 0.80, 5: 0.90}
_HOLGURA_DE_FABRICA_PPM = 2.0


def fc_maxima_de_zonas_de_fabrica(tabla: dict | None) -> int | None:
    """La FC máxima de la que salen unas zonas, si son EXACTAMENTE las de fábrica de Garmin.

    Hoja del atleta Hugo (2026-09-07), carrera y natación: 98/118/137/157/176, que
    son el 50/60/70/80/90 % de 196. Bici: 96/115/134/153/172, de 191. Unas zonas
    configuradas por umbral no siguen esa escalera (las del atleta 2 empiezan su
    Z2 en 139 con el suelo de Z5 en 181). Devuelve None si no casan: no se supone.
    """
    suelos: dict[int, float] = {}
    for zona, limites in (tabla or {}).items():
        try:
            bajo = float((list(limites) + [None])[0])
            suelos[int(zona)] = bajo
        except (TypeError, ValueError):
            continue
    if not all(z in suelos for z in _PORCENTAJES_DE_FABRICA):
        return None
    fc_max = suelos[5] / _PORCENTAJES_DE_FABRICA[5]
    if not 140 <= fc_max <= 230:
        return None
    if all(abs(suelos[z] - fc_max * p) <= _HOLGURA_DE_FABRICA_PPM for z, p in _PORCENTAJES_DE_FABRICA.items()):
        return round(fc_max)
    return None


_DEPORTES_ES = {"run": "carrera", "bike": "bici", "swim": "natación", "other": "otros"}


def _aviso_de_zonas(prescription: AthletePrescription) -> str:
    """El aviso al atleta cuando sus zonas no son unas medidas."""
    if prescription.zonas_de_fabrica:
        deportes = [_DEPORTES_ES.get(d, d) for d in prescription.zonas_de_fabrica if d != "other"]
        fcs = sorted(set(prescription.zonas_de_fabrica.values()))
        quien = ("Tus zonas de pulso son iguales en carrera, bici y natación y" if prescription.zonas_identicas
                 or not deportes else f"Tus zonas de pulso de {', '.join(deportes)}")
        # Qué hacer, deporte a deporte: el aviso mandaba a "Haz el test de umbral y configura en
        # Garmin tus zonas por tu FC umbral" también por la NATACIÓN, y el test de natación de la
        # app es el CSS, que da un ritmo, no un pulso (auditoría del 2026-09-24). Cada deporte, al
        # sitio que de verdad lo arregla.
        en_tierra = [d for d in ("carrera", "bici") if d in deportes] or (
            ["carrera", "bici"] if prescription.zonas_identicas or not deportes else [])
        que_hacer = []
        if en_tierra:
            que_hacer.append(
                f"En {' y '.join(en_tierra)}: el test de umbral (Perfil → Avanzado → Test de umbral) te da tu FC "
                "umbral, y con ella puedes poner tus zonas en la configuración de frecuencia cardiaca de tu reloj "
                "en Garmin Connect.")
        if "natación" in deportes or prescription.zonas_identicas or not deportes:
            que_hacer.append(
                "En natación no hace falta arreglarlas: tu plan la prescribe por ritmo por 100 m, y el pulso de "
                "muñeca en el agua es poco fiable. Lo que la afina es tu CSS (Perfil → Avanzado → Test de umbral "
                "→ Natación).")
        return (
            f"{quien} son las de fábrica de Garmin "
            f"(el 60/70/80/90 % de una FC máxima de {' y '.join(str(f) for f in fcs)} ppm), no unas medidas: su Z2 y "
            f"su Z4 no marcan tus umbrales. {' '.join(que_hacer)} Y si sabes a qué ritmo ruedas suave, escríbelo en "
            "tu perfil (Avanzado)."
        )
    return (
        "Tus zonas de pulso son iguales en carrera, bici y natación: suelen ser las de fábrica de "
        "Garmin, no unas medidas. Si puedes, configúralas por deporte en tu reloj (o haz el test de "
        "umbral), y si sabes a qué ritmo ruedas suave, escríbelo en tu perfil."
    )


def build_prescription(
    garmin_data: GarminData | None,
    profile: dict | None = None,
) -> AthletePrescription:
    """Assemble the athlete's real numbers, preferring measured over derived.

    Priority for each value: an explicit manual entry in the athlete's profile
    beats Garmin's own measurement, which beats anything computed from a
    formula. Every downgrade is recorded in `warnings` so the athlete can see
    in the UI why a number is missing or approximate.
    """
    profile = profile or {}
    prescription = AthletePrescription()

    user_profile = getattr(garmin_data, "user_profile", None) if garmin_data else None
    predictions = list(getattr(garmin_data, "race_predictions", None) or [])
    prescription.race_predictions = predictions

    # --- Heart rate zones: the athlete's real ones, or nothing -------------
    real_zones = getattr(garmin_data, "garmin_hr_zones", None) if garmin_data else None
    if real_zones and real_zones.by_sport:
        prescription.hr_zones = real_zones
        prescription.hr_zones_source = "garmin"
        # Hoja real del atleta Fernando (2026-09-13): 101-120 / 121-138 /
        # 139-158 / 159-178 / 179+ en bici, carrera y natación, con 38 ppm en
        # reposo. Son los porcentajes de fábrica de la FC máxima; su "Z2"
        # corriendo le salía a 6:00-6:20/km. No se inventan otras: se dice.
        tablas = [real_zones.by_sport[s] for s in real_zones.by_sport]
        if len(tablas) >= 2 and all(t == tablas[0] for t in tablas):
            prescription.zonas_identicas = True
        # Y, deporte a deporte, si son EXACTAMENTE las de fábrica. Iguales entre
        # deportes no basta para saberlo: las del atleta Hugo (2026-09-07) eran
        # de fábrica en carrera y natación (98/118/137/157/176, FC máx. ~196) y
        # también en bici, pero con otra FC máxima (96/115/134/153/172), así
        # que no eran idénticas y nadie avisó.
        prescription.zonas_de_fabrica = {
            deporte: fc_max
            for deporte, tabla in real_zones.by_sport.items()
            if (fc_max := fc_maxima_de_zonas_de_fabrica(tabla))
        }
        if prescription.zonas_identicas or prescription.zonas_de_fabrica:
            prescription.warnings.append(_aviso_de_zonas(prescription))
    else:
        prescription.warnings.append(
            "No se pudieron leer las zonas de FC configuradas en Garmin; se usarán zonas "
            "estimadas por fórmula, que pueden no coincidir con las del reloj."
        )

    # --- El test de umbral del atleta (Database/023) ------------------------
    # Lo que el atleta USÓ de un test está en sus columnas de siempre; aquí sólo
    # se reconoce de dónde salió, para decir "de su test del 20-sep" en vez de
    # "a mano". Si luego lo cambia a mano, deja de coincidir y deja de ser test.
    from services.ai.test_de_umbral import test_vigente

    tests = profile.get("threshold_tests")
    prescription.threshold_tests = {
        k: v for k, v in (tests.items() if isinstance(tests, dict) else []) if isinstance(v, dict)
    }
    # El test se repite cada pocos meses: manda el MÁS RECIENTE de cada deporte,
    # se haya guardado como vigente o no.
    test_run = test_vigente(prescription.threshold_tests, "run")
    test_bike = test_vigente(prescription.threshold_tests, "bike")
    rellenados = profile.get("garmin_autofilled_fields") or []

    # --- Threshold heart rate ---------------------------------------------
    # La misma regla que el ritmo, más abajo: lo tecleado gana a Garmin, pero no
    # lo que rellenó una importación de Garmin de otro día -- ese no es una
    # decisión del atleta, y el de hoy de Garmin es más nuevo.
    lthr_perfil = profile.get("lactate_threshold_hr")
    lthr_garmin = getattr(user_profile, "lactate_threshold_heart_rate", None) if user_profile else None
    lthr_tecleada = lthr_perfil and "lactate_threshold_hr" not in rellenados
    lthr = lthr_perfil if lthr_tecleada else (lthr_garmin or lthr_perfil)
    prescription.lactate_threshold_hr = int(lthr) if lthr else None
    if prescription.lactate_threshold_hr:
        if lthr_tecleada and _coincide(test_run.get("lthr"), prescription.lactate_threshold_hr, 1.0):
            prescription.lthr_source = "test"
        else:
            prescription.lthr_source = "manual" if lthr_tecleada else "garmin"
        prescription.lthr_por_deporte["run"] = prescription.lactate_threshold_hr
    if _numero(test_bike.get("lthr")):
        prescription.lthr_por_deporte["bike"] = int(round(_numero(test_bike.get("lthr"))))

    # --- Cycling power (FTP) ----------------------------------------------
    # Built independently of running data: a cyclist with no race predictions
    # and no threshold pace still gets a complete, usable prescription.
    ftp = profile.get("ftp_watts")
    if ftp:
        prescription.ftp_watts = float(ftp)
        prescription.power_bands = {
            key: (round(float(ftp) * low), round(float(ftp) * high))
            for key, (low, high) in _POWER_CLASS_FRACTIONS.items()
        }

    # --- Swimming (CSS por 100 m) -----------------------------------------
    # Sale del perfil y NUNCA de los parciales de Garmin: el reloj cuenta los
    # largos por acelerometro y un viraje mal detectado produce un 15 s/100 m
    # que corromperia la referencia entera. Ver _SWIM_CLASS_FRACTIONS.
    swim_threshold = profile.get("swim_threshold_pace_sec_per_100m")
    if swim_threshold:
        prescription.swim_threshold_pace_sec_per_100m = float(swim_threshold)
        prescription.swim_bands = {
            key: (round(float(swim_threshold) * low, 1), round(float(swim_threshold) * high, 1))
            for key, (low, high) in _SWIM_CLASS_FRACTIONS.items()
        }

    # --- Threshold pace ----------------------------------------------------
    threshold_pace: float | None = None
    source = "none"

    manual_pace = profile.get("lactate_threshold_pace_sec_per_km")
    lt_speed = getattr(user_profile, "lactate_threshold_speed", None) if user_profile else None
    # El docstring dice que lo que escribe el atleta gana a Garmin, y el código
    # hacía lo contrario. Pero el campo "manual" también lo RELLENA la
    # importación de Garmin (`garmin_autofilled_fields`): ese es el valor de
    # Garmin de otro día, no una decisión del atleta. Sólo gana el tecleado.
    tecleado = manual_pace and "lactate_threshold_pace_sec_per_km" not in (
        profile.get("garmin_autofilled_fields") or [])

    if tecleado:
        threshold_pace = float(manual_pace)
        source = "test" if _coincide(test_run.get("ritmo_s_km"), threshold_pace, 1.5) else "manual"
    elif lt_speed:
        threshold_pace, source = 1000.0 / lt_speed, "garmin_lt"
    elif manual_pace:
        # Lo rellenó una importación de Garmin: es de Garmin, de otro día. Se
        # etiquetaba "introducido a mano por el atleta".
        threshold_pace, source = float(manual_pace), "garmin_lt"

    if threshold_pace and predictions:
        ok, reason = _plausible_threshold(threshold_pace, predictions)
        if not ok and source == "test":
            # Un test MIDE y una predicción ESTIMA: cuando no cuadran, la que se
            # pone en duda es la predicción, no el test.
            prescription.warnings.append(
                f"Tu ritmo umbral del test no cuadra con tus predicciones de Garmin ({reason}). Se usa el del "
                "test: una predicción se calcula con tu pulso y tus zonas, y un test mide lo que corriste."
            )
        elif not ok:
            fallback = _derive_threshold_from_predictions(predictions)
            quien = "que escribiste" if source == "manual" else "de Garmin"
            prescription.warnings.append(
                f"Se descartó el ritmo umbral {quien} porque {reason}. "
                + (
                    f"Se estimó el ritmo que sostendrías una hora, entre tus predicciones de carrera "
                    f"({format_pace(fallback)}/km). Un test de umbral lo mediría."
                    if fallback
                    else "No hay alternativa disponible, así que no se prescriben ritmos."
                )
            )
            logger.warning("Implausible threshold pace rejected: %s", reason)
            threshold_pace, source = fallback, "race_prediction"

    if threshold_pace and predictions and source in ("garmin_lt", "manual"):
        _umbral_que_se_sostiene_una_hora(prescription, threshold_pace, source, predictions)
        if prescription.umbral_ajustado_desde:
            threshold_pace, source = prescription.threshold_pace_sec_per_km, "garmin_ajustado"

    if not threshold_pace and predictions:
        threshold_pace = _derive_threshold_from_predictions(predictions)
        source = "race_prediction"

    prescription.threshold_pace_sec_per_km = threshold_pace
    prescription.threshold_pace_source = source if threshold_pace else "none"

    if not threshold_pace:
        prescription.warnings.append(
            "No hay ritmo umbral disponible: las sesiones de calidad DE CARRERA no podrán "
            "llevar objetivo de ritmo y caerán a zonas de FC."
        )
        return prescription

    # --- Pace bands per intensity class ------------------------------------
    for class_key, (fast_mult, slow_mult) in _PACE_CLASS_MULTIPLIERS.items():
        prescription.pace_bands[class_key] = PaceBand(
            fast_sec_per_km=round(threshold_pace * fast_mult, 1),
            slow_sec_per_km=round(threshold_pace * slow_mult, 1),
        )

    # %-of-threshold multipliers work well for the intensity classes near
    # threshold, but break down in the endurance domain: an endurance-limited
    # athlete fades far more over a marathon than any fixed multiplier
    # assumes. For this athlete the multiplier put marathon pace at ~6:20/km
    # while Garmin's own prediction says 7:01/km. So whenever Garmin has a
    # real marathon prediction we anchor the whole endurance end of the table
    # to it instead -- marathon, easy and recovery alike. Cross-checked
    # against a real Z2 run: 7:38/km at 142 bpm, which the threshold-derived
    # "easy" band (6:36-7:18) would have excluded and this one contains.
    by_key = {p.distance_key: p.pace_sec_per_km for p in predictions}
    marathon_pace = by_key.get("marathon")
    if marathon_pace:
        prescription.pace_bands["maraton"] = PaceBand(
            fast_sec_per_km=round(marathon_pace * 0.98, 1),
            slow_sec_per_km=round(marathon_pace * 1.03, 1),
        )
        prescription.pace_bands["facil"] = PaceBand(
            fast_sec_per_km=round(marathon_pace * 1.00, 1),
            slow_sec_per_km=round(marathon_pace * 1.12, 1),
        )
        prescription.pace_bands["recuperacion"] = PaceBand(
            fast_sec_per_km=round(marathon_pace * 1.10, 1),
            slow_sec_per_km=round(marathon_pace * 1.25, 1),
        )

    _enforce_band_ordering(prescription)
    _ritmo_suave_declarado(prescription, profile)
    return prescription


def _umbral_que_se_sostiene_una_hora(prescription: AthletePrescription, ritmo: float, fuente: str,
                                     predictions: list[RacePrediction]) -> None:
    """Un umbral que su curva sostiene fuera de 25-90 minutos no es un umbral.

    - **El de Garmin** se lleva al borde de la ventana, no a la hora. Cuando Garmin y
      las predicciones no cuadran no se sabe cuál exagera -- las del atleta 2 eran
      optimistas: predecían un 5K de 25:24 y lo corrió a ~26' --, así que se corrige
      lo MÍNIMO para que sea un umbral y nunca se le pone más rápido de lo necesario.
      Para él: 5:53 → 5:43 (el ritmo que sostendría 90'), y sus series de VO2max
      caen a 5:09-5:29, su ritmo real de 5K. Un umbral absurdo (más lento que su
      maratón) ya se estima arriba desde la hora, como siempre.
    - **El que escribió él** manda (puede venir de un laboratorio): sólo se avisa.
    - **El de un test** no pasa por aquí: un test mide.

    Deja el ritmo en `prescription.threshold_pace_sec_per_km` si lo cambia.
    """
    minutos = minutos_que_sostiene(ritmo, predictions)
    if minutos is None:
        return
    corto, largo = VENTANA_DE_UMBRAL_MIN
    if corto <= minutos <= largo:
        return
    borde = largo if minutos > largo else corto
    nuevo = _ritmo_para_una_duracion(borde * 60.0, predictions)
    que = (f"según tus predicciones de carrera, {format_pace(ritmo)}/km es el ritmo que sostendrías unos "
           f"{minutos:.0f} minutos, y un umbral es el esfuerzo de alrededor de una hora (25-90')")
    if fuente == "manual":
        prescription.warnings.append(
            f"Tu ritmo umbral escrito puede no ser un umbral: {que}. Se usa el tuyo; un test de umbral "
            "(Perfil → Avanzado) lo confirmaría.")
        return
    prescription.warnings.append(
        f"Se ajustó el ritmo umbral de Garmin: {que}. Se usa {format_pace(nuevo)}/km, el más "
        f"{'lento' if borde == largo else 'rápido'} que cuadra con un umbral. Un test de umbral lo mediría.")
    logger.warning("Umbral de Garmin fuera de la ventana (%.0f min): %.1f -> %.1f s/km", minutos, ritmo, nuevo)
    prescription.threshold_pace_sec_per_km = round(nuevo, 1)
    prescription.umbral_ajustado_desde = round(ritmo, 1)
    prescription.threshold_pace_source = "garmin_ajustado"


def _ritmo_suave_declarado(prescription: AthletePrescription, profile: dict) -> None:
    """El ritmo al que el atleta dice que rueda suave gana a la banda derivada.

    Atleta Fernando, 2026-09-14: "mi zona 2 es realmente a 6-6:20". Su hoja le
    decía "Fácil 5:03-5:40/km", anclado a una predicción de maratón de 3:33. Una
    predicción describe una carrera; lo que el atleta sabe de sus rodajes
    describe sus rodajes. Va después de ordenar las bandas, a propósito: un
    rodaje suave declarado no se "corrige" contra el ritmo de maratón.
    """
    try:
        rapido = float(profile.get("easy_run_pace_fast_sec_per_km") or 0)
        lento = float(profile.get("easy_run_pace_slow_sec_per_km") or 0)
    except (TypeError, ValueError):
        return
    if not rapido and not lento:
        return
    rapido, lento = sorted((rapido or lento, lento or rapido))
    if not (180 <= rapido <= 900):
        return
    prescription.pace_bands["facil"] = PaceBand(fast_sec_per_km=round(rapido, 1), slow_sec_per_km=round(lento, 1))
    prescription.facil_declarado = True


def _enforce_band_ordering(prescription: AthletePrescription) -> None:
    """Guarantee the table reads as a coherent progression, fast to slow.

    Mixing two anchors (threshold multipliers for the quality classes, race
    predictions for the endurance ones) can invert neighbouring bands -- an
    "easy" pace faster than marathon pace, which is nonsense to any athlete
    reading it and worse than useless to a model prescribing from it. This
    clamps any inversion and records it, rather than emitting a table the
    athlete would rightly distrust.
    """
    previous_key: str | None = None
    for class_key in _PACE_CLASS_MULTIPLIERS:  # ordered fastest -> slowest
        band = prescription.pace_bands.get(class_key)
        if band is None:
            continue
        if previous_key is not None:
            previous = prescription.pace_bands[previous_key]
            if band.fast_sec_per_km < previous.fast_sec_per_km:
                logger.warning(
                    "Pace band '%s' (%s) started faster than '%s' (%s); clamping.",
                    class_key, band, previous_key, previous,
                )
                band.fast_sec_per_km = previous.fast_sec_per_km
            if band.slow_sec_per_km < band.fast_sec_per_km:
                band.slow_sec_per_km = band.fast_sec_per_km
        previous_key = class_key


def prescription_to_state(prescription: AthletePrescription) -> dict:
    """Serialize for LangGraph state, which carries plain dicts.

    Keeps both the rendered card (what the planner prompt needs) and the
    structured bands (what the validator needs to check a prescribed pace),
    so the two can never drift apart.
    """
    return {
        "card": render_prescription_card(prescription),
        "threshold_pace_sec_per_km": prescription.threshold_pace_sec_per_km,
        "threshold_pace_source": prescription.threshold_pace_source,
        # El de Garmin antes de llevarlo al borde de un umbral (s/km): el perfil lo cita junto al
        # que usa el plan (Beta 3.0, F10).
        "umbral_ajustado_desde": prescription.umbral_ajustado_desde,
        "lactate_threshold_hr": prescription.lactate_threshold_hr,
        # De dónde sale cada umbral y si las zonas son de fábrica: lo leen los
        # expertos (`procedencia_de_umbrales`) y el reparto de intensidad.
        "lthr_source": prescription.lthr_source,
        "lthr_por_deporte": dict(prescription.lthr_por_deporte),
        "threshold_tests": dict(prescription.threshold_tests),
        "zonas_de_fabrica": dict(prescription.zonas_de_fabrica),
        "hr_zones_by_sport": (
            {
                sport: {str(z): list(bounds) for z, bounds in zones.items()}
                for sport, zones in prescription.hr_zones.by_sport.items()
            }
            if prescription.has_hr_zones and prescription.hr_zones
            else {}
        ),
        "hr_zones_source": prescription.hr_zones_source,
        "pace_bands": {
            key: [band.fast_sec_per_km, band.slow_sec_per_km]
            for key, band in prescription.pace_bands.items()
        },
        "ftp_watts": prescription.ftp_watts,
        # Carried into state (v5 Track 2) so the SEASON planner can weigh a
        # declared race goal against what the athlete's current fitness
        # actually predicts. Until this existed, the only agent that evaluates
        # season goals could not see a single predicted time.
        "race_predictions": [
            {
                "distance_key": p.distance_key,
                "distance_m": p.distance_m,
                "time_seconds": p.time_seconds,
            }
            for p in prescription.race_predictions
        ],
        "power_bands": {key: list(band) for key, band in prescription.power_bands.items()},
        "swim_threshold_pace_sec_per_100m": prescription.swim_threshold_pace_sec_per_100m,
        "swim_bands": {key: list(band) for key, band in prescription.swim_bands.items()},
        "warnings": list(prescription.warnings),
    }


def render_prescription_card(prescription: AthletePrescription) -> str:
    """The block injected into the weekly planner prompt.

    Kept deliberately compact and unambiguous: every number carries its unit,
    and the modality rule is stated as a rule, not as an example to imitate.
    """
    lines: list[str] = ["### Ficha de Prescripción del Atleta (datos REALES, no estimados salvo donde se indique)"]

    # Heart-rate zones, per sport -- Garmin configures them separately and the
    # difference is large (this athlete: running Z2 139-152, swimming Z2 112-129).
    if prescription.has_hr_zones:
        assert prescription.hr_zones is not None
        origin = (
            "leídas de la configuración real de Garmin del atleta"
            if prescription.hr_zones_source == "garmin"
            else "estimadas por fórmula"
        )
        sport_labels = {"run": "Carrera", "bike": "Bici", "swim": "Natación", "other": "Otros"}
        lines.append(f"\n**Zonas de frecuencia cardiaca por deporte** ({origin}):")
        for sport in prescription.hr_zones.sports:
            zones = prescription.hr_zones.by_sport[sport]
            rendered = ", ".join(
                f"Z{z}={low}-{high if high is not None else 'máx'}"
                for z, (low, high) in sorted(zones.items())
            )
            lines.append(f"- {sport_labels.get(sport, sport)}: {rendered} ppm")
        lines.append(
            "Garmin configura las zonas por deporte y NO son intercambiables: no uses las de "
            "carrera para nadar ni al revés. Para prescribir FC usa SIEMPRE el número de zona "
            "(Z1-Z5), nunca un rango de ppm propio: así el reloj aplica la tabla correcta del atleta."
        )
        if prescription.zonas_de_fabrica:
            lines.append(_nota_de_zonas_de_fabrica(prescription))
        elif prescription.zonas_identicas:
            lines.append(
                "**Ojo: sus zonas son idénticas en todos los deportes**, lo que casi siempre es la "
                "configuración de fábrica de Garmin (porcentajes de la FC máxima), no unas zonas medidas. "
                "Se usan igual, pero un pulso bajo en bici o un Z2 que le obliga a trotar muy lento no se "
                "leen como falta de esfuerzo sin contrastarlos con su ritmo o su esfuerzo percibido."
            )
    else:
        lines.append("\n**Zonas de frecuencia cardiaca**: no disponibles.")

    if prescription.lactate_threshold_hr:
        origen_lthr = {
            "test": _origen_del_test(prescription.threshold_tests, "run"),
            "manual": "introducida a mano por el atleta",
            "garmin": "medida por Garmin",
        }.get(prescription.lthr_source, "")
        # «en carrera»: sin el deporte, el chat la dio como umbral de bici (José, 2026-10-21: 176 por 156).
        lines.append(f"\n**FC umbral en carrera (LTHR)**: {prescription.lactate_threshold_hr} ppm"
                     + (f" ({origen_lthr})" if origen_lthr else ""))
    if prescription.lthr_por_deporte.get("bike"):
        lines.append(f"**FC umbral en bici**: {prescription.lthr_por_deporte['bike']} ppm "
                     f"({_origen_del_test(prescription.threshold_tests, 'bike')}). Es la suya para rodar; "
                     "la de carrera no vale en bici.")

    # Threshold pace + provenance
    if prescription.threshold_pace_sec_per_km:
        source_es = {
            "garmin_lt": "medido por Garmin (umbral de lactato)",
            "race_prediction": "estimado entre sus predicciones de carrera de Garmin como el ritmo de ~1 hora, "
                               "no medido",
            "manual": "introducido a mano por el atleta",
            "test": _origen_del_test(prescription.threshold_tests, "run"),
            "garmin_ajustado": (f"ajustado desde el de Garmin ({format_pace(prescription.umbral_ajustado_desde)}"
                                "/km), que según sus predicciones no es un esfuerzo de ~1 hora; no medido"),
        }.get(prescription.threshold_pace_source, prescription.threshold_pace_source)
        lines.append(
            f"\n**Ritmo umbral**: {format_pace(prescription.threshold_pace_sec_per_km)}/km "
            f"({source_es})"
        )

    # Race predictions
    if prescription.race_predictions:
        label_es = {
            "5K": "5K",
            "10K": "10K",
            "half_marathon": "Media maratón",
            "marathon": "Maratón",
        }
        lines.append("\n**Predicciones de carrera de Garmin (condición actual)**:")
        for prediction in prescription.race_predictions:
            total = prediction.time_seconds
            clock = f"{total // 3600}:{(total % 3600) // 60:02d}:{total % 60:02d}"
            lines.append(
                f"- {label_es.get(prediction.distance_key, prediction.distance_key)}: "
                f"{clock} ({format_pace(prediction.pace_sec_per_km)}/km)"
            )

    # Pace bands (running)
    if prescription.pace_bands:
        lines.append("\n**CARRERA — ritmos objetivo por tipo de sesión** (rangos min/km):")
        for class_key in _PACE_CLASS_MULTIPLIERS:
            band = prescription.pace_bands.get(class_key)
            if band:
                origen = " (el ritmo al que el atleta dice que rueda suave)" if (
                    class_key == "facil" and prescription.facil_declarado) else ""
                lines.append(f"- {_PACE_CLASS_LABELS_ES[class_key]}: {band}{origen}")
        lines.append(
            "Son rangos de referencia, no una tabla que copiar: ajusta el ritmo exacto de cada "
            "repetición dentro de su rango según el objetivo de la sesión, y puedes variarlo "
            "entre repeticiones (series progresivas, descendentes, etc.)."
        )

    # Power bands (cycling)
    if prescription.power_bands:
        lines.append(
            f"\n**BICI — potencias objetivo por tipo de sesión** (FTP {prescription.ftp_watts:.0f} W):"
        )
        for class_key in _POWER_CLASS_FRACTIONS:
            band = prescription.power_bands.get(class_key)
            if band:
                lines.append(f"- {_POWER_CLASS_LABELS_ES[class_key]}: {band[0]}-{band[1]} W")

    # Swimming (CSS per 100 m)
    if prescription.swim_bands:
        umbral = prescription.swim_threshold_pace_sec_per_100m or 0
        lines.append(
            f"\n**NATACIÓN — ritmos objetivo por 100 m** "
            f"(umbral declarado por el atleta: {_format_100(umbral)}/100m):"
        )
        for class_key in _SWIM_CLASS_FRACTIONS:
            band = prescription.swim_bands.get(class_key)
            if band:
                lines.append(
                    f"- {_SWIM_CLASS_LABELS_ES[class_key]}: "
                    f"{_format_100(band[0])}-{_format_100(band[1])}/100m"
                )
    else:
        lines.append(
            "\n**NATACIÓN — sin ritmo de referencia**\n"
            "Este atleta no ha declarado su umbral por 100 m, así que **NO inventes un ritmo**. "
            "Prescribe las series de natación por **distancia + descanso + esfuerzo descrito** "
            "(ej. «8x100 m fuerte pero sostenible, 20\" de descanso»), que es como se escribe una "
            "serie de natación de todos modos, y usa las zonas de FC de NATACIÓN para el trabajo suave."
        )

    lines.append(
        "\n**NATACIÓN — cuidado con los datos de Garmin.** El reloj cuenta los largos por "
        "acelerómetro: un parón a media piscina, un viraje suave o nadar con material le hacen contar "
        "un largo de más o de menos. Por eso aparecen parciales imposibles (15 s en 100 m es el caso "
        "típico). **No los uses como evidencia de nada**: si un parcial de natación es fisiológicamente "
        "absurdo, es un error del sensor, no un dato del atleta."
    )

    # The rule that motivated this whole card, stated per sport because the
    # right modality is not the same for all three.
    lines.append("\n**REGLA DE MODALIDAD (obligatoria)**")
    lines.append(
        "- **Carrera, sesiones de CALIDAD** (tempo, umbral, intervalos, VO2max, fartlek, cuestas, "
        "series): objetivo de RITMO en min/km. Nunca de FC. En las primeras repeticiones la FC "
        "todavía no ha subido a la zona, y un atleta persiguiendo un número de ppm se sobre-esfuerza "
        "y arruina el estímulo de la sesión. **Salvo en CINTA**: ahí la velocidad la fija la máquina "
        "(nadie persigue nada), el reloj no mide bien el ritmo bajo techo y pitaría mal; la velocidad "
        "va en el texto en km/h con su ritmo, y el objetivo del reloj es la zona de FC."
        if prescription.has_pace_data
        else "- **Carrera**: sin ritmo umbral disponible, prescribe por ZONA DE FC y describe la "
        "intensidad en el texto del paso. No inventes rangos de ritmo."
    )
    lines.append(
        "- **Bici, sesiones de CALIDAD**: objetivo de POTENCIA en watts. El ritmo no significa nada "
        "sobre una bici (viento, pendiente y rebufo lo mueven más que el esfuerzo)."
        if prescription.has_power_data
        else "- **Bici**: sin FTP conocido, prescribe por ZONA DE FC. No uses ritmo en bici."
    )
    lines.append(
        "- **Natación**: Garmin no acepta objetivos de ritmo ni de FC en los entrenos de natación que "
        "sube esta app, así que la intensidad va descrita en el texto del paso (p. ej. ritmo objetivo "
        "por 100 m, o esfuerzo percibido)."
    )
    lines.append(
        "- **Cualquier deporte — calentamiento, enfriamiento, recuperación entre repeticiones, "
        "rodajes fáciles y tiradas largas**: objetivo de ZONA DE FC. Ahí la FC sí es la medida "
        "honesta de la carga interna."
    )

    if prescription.warnings:
        lines.append("\n**Limitaciones de los datos disponibles**:")
        lines.extend(f"- {w}" for w in prescription.warnings)

    return "\n".join(lines)


def _nota_de_zonas_de_fabrica(prescription: AthletePrescription) -> str:
    """Lo que un modelo tiene que saber de unas zonas de fábrica antes de leer un pulso con ellas.

    El informe del atleta Hugo (2026-09-07) leyó un 21K a 162 ppm de media como
    "tempo/umbral" porque su Z4 de fábrica empezaba en 157, y le escribió "por
    debajo de 140-145 bpm" como regla -- con las mismas zonas que en la frase
    anterior decía que parecían mal calibradas.
    """
    deportes = ", ".join(_DEPORTES_ES.get(d, d) for d in prescription.zonas_de_fabrica if d != "other")
    fcs = " y ".join(str(f) for f in sorted(set(prescription.zonas_de_fabrica.values())))
    quien = ("sus zonas son idénticas en todos los deportes y" if prescription.zonas_identicas or not deportes
             else f"sus zonas de {deportes}")
    return (
        f"**Ojo: {quien} son las de fábrica de Garmin** (suelos de Z2-Z5 al "
        f"60/70/80/90 % de una FC máxima de {fcs} ppm), no medidas. Úsalas como número de zona para el reloj, "
        "pero su Z2 no es su primer umbral ni su Z4 el segundo: un pulso sostenido en Z3-Z4 no es por sí solo "
        "tempo o umbral, ni un pulso bajo falta de esfuerzo. No escribas reglas en ppm a partir de ellas: léelas "
        "con su ritmo, su esfuerzo y, si lo hay, su test de umbral."
    )


def _origen_del_test(tests: dict | None, deporte: str) -> str:
    """ "de su test de umbral del 2026-09-20" (el MÁS RECIENTE de ese deporte), con la edad si ya tiene tiempo."""
    from datetime import date

    from services.ai.test_de_umbral import SEMANAS_DE_VIGENCIA, test_vigente

    fecha = str(test_vigente(tests, deporte).get("fecha") or "")[:10]
    if not fecha:
        return "de su test de umbral"
    try:
        semanas = (date.today() - date.fromisoformat(fecha)).days // 7
    except ValueError:
        return f"de su test de umbral del {fecha}"
    viejo = f", de hace {semanas} semanas: puede haber cambiado" if semanas >= SEMANAS_DE_VIGENCIA else ""
    return f"de su test de umbral del {fecha}{viejo}"


def procedencia_de_umbrales(prescription_state: dict | None) -> str:
    """De dónde salen el umbral y las zonas del atleta, en pocas líneas, para los EXPERTOS.

    Los planificadores lo leen en la ficha de prescripción; los expertos de
    actividad y de métricas no la reciben, y son los que convierten un pulso en
    "tempo" o un Load Focus en un déficit. Cuánto pesa una lectura depende de si
    el umbral es medido o estimado y de si las zonas son las del atleta o las de
    fábrica. "" si no hay nada que decir: cero tokens.
    """
    from services.ai.test_de_umbral import describir_tests

    s = prescription_state or {}
    lineas: list[str] = []
    fabrica = s.get("zonas_de_fabrica") or {}
    if fabrica:
        deportes = ", ".join(_DEPORTES_ES.get(d, d) for d in fabrica if d != "other") or "todos sus deportes"
        fcs = " y ".join(str(f) for f in sorted(set(fabrica.values())))
        lineas.append(
            f"- Sus zonas de pulso de {deportes} son las de fábrica de Garmin (60/70/80/90 % de una FC máxima de "
            f"{fcs} ppm), no medidas: su Z2 no marca su primer umbral ni su Z4 el segundo. No llames tempo o umbral "
            "a un pulso por caer en Z3-Z4 ni escribas reglas en ppm con ellas; dilo como una limitación de los datos."
        )
    fuente = str(s.get("threshold_pace_source") or "")
    if fuente == "race_prediction":
        lineas.append("- Su ritmo umbral está ESTIMADO desde sus predicciones de carrera, no medido.")
    elif fuente == "garmin_ajustado":
        lineas.append("- Su ritmo umbral es el de Garmin AJUSTADO: según sus predicciones, el de Garmin era un "
                      "esfuerzo de mucho más de una hora. No está medido.")
    elif fuente == "test":
        lineas.append("- Su ritmo umbral sale de un test de umbral suyo (medido).")
    for linea in describir_tests(s.get("threshold_tests")):
        lineas.append(f"- {linea}")
    if not fabrica and fuente not in ("race_prediction", "test", "garmin_ajustado") and not s.get("threshold_tests"):
        return ""
    return "\n".join(lineas)


# Labels for render_zones_table below. Kept next to it rather than inlined so
# a new pace band or sport shows up as a missing label at a glance.
_ZONE_SPORT_LABELS_ES = {
    "run": "Carrera", "bike": "Bici", "swim": "Natación", "strength": "Fuerza",
    "walk": "Caminata", "other": "Otros",
}
PACE_BAND_LABELS_ES = {
    "repeticiones": "Repeticiones", "vo2max": "VO2max", "umbral_tempo": "Umbral/Tempo",
    "maraton": "Maratón", "facil": "Fácil", "recuperacion": "Recuperación",
}


def render_zones_table(prescription_state: dict | None) -> str:
    """The athlete's real zones/paces, as the one-line header a plan carries.

    Deterministic on purpose. This used to be a field the weekly planner's
    formatting call had to emit as part of its structured output -- but it is
    a pure restatement of numbers this app already holds exactly (see
    prescription_to_state), so asking a model to retype them cost tokens on
    every generation and added a way for a plan's stated zones to drift from
    the athlete's real ones. Anything the LLM does not need to decide should
    not be in the LLM's output schema.

    Returns "" when there is no prescription, so callers can fall back.
    """
    state = prescription_state or {}
    parts: list[str] = []

    # Los deportes que comparten zonas se agrupan en una sola línea.
    #
    # Garmin devuelve una tabla por deporte y muy a menudo varias son
    # IDÉNTICAS: en este atleta, carrera, bici y "otros" son las mismas
    # (123-139...) y sólo natación difiere (93-111...). Listarlas por separado
    # daba cuatro renglones casi iguales en la cabecera del plan y en la hoja
    # impresa, y el efecto era el contrario del buscado: la única diferencia
    # que de verdad importa -- que nadando entrenas 28 ppm más abajo --
    # quedaba enterrada entre repeticiones.
    agrupadas: dict[tuple, list[str]] = {}
    for sport, zones in (state.get("hr_zones_by_sport") or {}).items():
        if not zones:
            continue
        bounds = []
        for zone_key in sorted(zones, key=lambda z: int(z)):
            low, high = (list(zones[zone_key]) + [None, None])[:2]
            if low is None:
                continue
            bounds.append(f"Z{zone_key} ({low}-{high} ppm)" if high else f"Z{zone_key} ({low}+ ppm)")
        if bounds:
            agrupadas.setdefault(tuple(bounds), []).append(
                _ZONE_SPORT_LABELS_ES.get(sport, sport.capitalize())
            )

    # Orden de lectura, no orden de diccionario: el atleta busca su deporte,
    # y "Otros" es la etiqueta que menos le dice. Los deportes reales primero
    # y el cajón de sastre al final, dentro de cada línea y entre líneas.
    def _peso(label: str) -> int:
        orden = ["Carrera", "Bici", "Natación", "Fuerza"]
        return orden.index(label) if label in orden else len(orden)

    for bounds, labels in sorted(agrupadas.items(), key=lambda kv: min(_peso(x) for x in kv[1])):
        labels = sorted(labels, key=_peso)
        # "Carrera, Bici y Otros" — la coma sola dejaría dudando si es una
        # lista o un nombre compuesto.
        nombre = labels[0] if len(labels) == 1 else f"{', '.join(labels[:-1])} y {labels[-1]}"
        parts.append(f"FC {nombre}: {', '.join(bounds)}")

    pace_bands = state.get("pace_bands") or {}
    band_parts = [
        f"{PACE_BAND_LABELS_ES.get(key, key)} ({format_pace(fast)}-{format_pace(slow)}/km)"
        for key, (fast, slow) in (
            (k, v) for k, v in pace_bands.items() if v and len(v) == 2
        )
    ]
    if band_parts:
        parts.append(f"Ritmos: {', '.join(band_parts)}")

    return ". ".join(parts) + "." if parts else ""
