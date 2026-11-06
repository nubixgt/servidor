"""La carga, no las horas, es la moneda del plan.

## Por qué, con los números del atleta 2 (12 semanas hasta el 2026-09-11)

Carga de Garmin (EPOC, la del reloj) por hora de sesión, medida en su cuenta:

    bici suave ~6/h · natación suave ~34/h · carrera suave ~52/h
    carrera de calidad ~82/h · natación de calidad ~87/h

Semana a semana las horas explicaban poco de la carga (correlación 0,6): la de
más carga del trimestre (451) fue de 5,5 h y la de más horas (9,5 h) cargó 318.
Una bici de 244 min cargó 104; unas series de 56 min, 90.

Y el plan se anclaba en horas en tres capas: el macrociclo recibía "Carga
crónica: **6.6 h/semana** ... Este es el ancla de la progresión", la semana un
"techo" en horas, y el validador juzgaba horas. Mientras, Garmin decía
AEROBIC_HIGH_SHORTAGE: aeróbico alto 227 (objetivo 275-538), aeróbico bajo 771
(objetivo 205-468). Le sobraba trabajo suave y le faltaba del otro, y un plan
que reparte horas no puede verlo.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    _format_load_target,
    compute_weekly_load_target,
)
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import (  # noqa: E402
    validate_weekly_plan,
)
from services.ai.weekly_load import (  # noqa: E402
    carga_de_la_semana,
    carga_semanal_real,
    estimar_carga,
    tasas_de_carga,
)

HOY = date(2026, 9, 11)
TASAS_REALES = {
    "run:suave": {"por_hora": 52, "n": 16}, "run:calidad": {"por_hora": 82, "n": 12},
    "swim:suave": {"por_hora": 34, "n": 15}, "swim:calidad": {"por_hora": 87, "n": 5},
    "bike:suave": {"por_hora": 6, "n": 6}, "fuerza": {"por_sesion": 10, "n": 20},
}
BASE_REAL = {
    "chronic_weekly_hours": 6.6, "garmin_chronic_load": 280, "garmin_acute_load": 297,
    "garmin_optimal_min": 224, "garmin_optimal_max": 420, "load_rates": TASAS_REALES,
}
SEMANA = [{"date": f"2026-09-{d}"} for d in range(11, 18)]
FASE = {"week_number": 3, "is_deload": False, "weekly_hours_target": 6.0,
        "weekly_load_target": None, "intensity_emphasis": ""}


def act(fecha, tipo, minutos, carga, vig=0):
    return SimpleNamespace(
        start_time=f"{fecha} 07:00:00", activity_type=tipo,
        summary=SimpleNamespace(duration=minutos * 60, activity_training_load=carga,
                                vigorous_intensity_minutes=vig),
    )


class LasTasasSalenDelHistorialDelAtleta(unittest.TestCase):
    def test_las_dos_sesiones_reales_no_cargan_lo_mismo_por_hora(self):
        """Palín Ciclismo 244 min carga 104; Ritmo 5K 56 min carga 90 (vigorosa)."""
        historial = [
            act("2026-09-05", "cycling", 244, 104), act("2026-09-01", "cycling", 118, 12),
            act("2026-09-09", "running", 56, 90, vig=30), act("2026-09-02", "running", 56, 77, vig=28),
        ]
        t = tasas_de_carga(historial, HOY)
        self.assertLess(t["bike:suave"]["por_hora"], t["run:calidad"]["por_hora"])
        self.assertGreater(t["run:calidad"]["por_hora"], 80)

    def test_una_tirada_larga_con_algo_vigoroso_no_es_calidad(self):
        """La primera version contaba como calidad cualquier sesion con 15'
        vigorosos: la tirada de 155' del 23-ago (carga 141) diluia la tasa de
        las series hasta ~68/h. Las series reales cargaron 97/h y 84/h."""
        historial = [
            act("2026-09-09", "running", 56, 91, vig=25), act("2026-09-02", "running", 56, 79, vig=34),
            act("2026-08-23", "running", 155, 141, vig=20), act("2026-09-04", "running", 40, 25, vig=2),
        ]
        t = tasas_de_carga(historial, HOY)
        self.assertEqual(t["run:calidad"]["n"], 2)
        self.assertGreaterEqual(t["run:calidad"]["por_hora"], 90)

    def test_con_una_sola_sesion_no_hay_tasa(self):
        """Una salida no dice cuánto carga ese tipo de sesión a este atleta."""
        self.assertEqual(tasas_de_carga([act("2026-09-05", "cycling", 244, 104)], HOY), {})

    def test_la_fuerza_va_por_sesion(self):
        historial = [act("2026-09-10", "strength_training", 48, 8),
                     act("2026-09-07", "strength_training", 72, 10)]
        self.assertIn("por_sesion", tasas_de_carga(historial, HOY)["fuerza"])

    def test_la_carga_semanal_real_suma_la_de_garmin(self):
        historial = [act("2026-09-09", "running", 56, 90), act("2026-09-05", "cycling", 244, 104)]
        self.assertEqual(carga_semanal_real(historial, HOY, 7), 194)


class LaEstimacionDistingueCalidadDeRelleno(unittest.TestCase):
    def test_una_hora_de_bici_suave_no_es_una_hora_de_series(self):
        bici = estimar_carga("bike", "cross_training", 60, TASAS_REALES)
        series = estimar_carga("run", "vo2max", 60, TASAS_REALES)
        self.assertLess(bici * 10, series)

    def test_sin_tasa_se_abstiene(self):
        self.assertIsNone(estimar_carga("bike", "tempo_threshold", 60, TASAS_REALES))

    def test_una_suave_sin_tasa_usa_la_de_calidad_que_sobreestima(self):
        tasas = {"swim:calidad": {"por_hora": 87, "n": 5}}
        self.assertEqual(estimar_carga("swim", "easy", 60, tasas), 87)


class LaCargaEsUnaReferenciaNoUnTecho(unittest.TestCase):
    """La primera versión de la v1.2 calculaba un techo de carga y el validador
    lo hacía cumplir recortando sesiones. Se retiró: lo que el atleta puede hacer
    con seguridad lo dice lo que ya hizo y cómo respondió, no un número
    derivado de unas zonas que pueden estar mal."""

    def test_no_hay_techo(self):
        t = compute_weekly_load_target(BASE_REAL, FASE)
        self.assertNotIn("ceiling", t)
        self.assertEqual(t["chronic"], 280)
        self.assertEqual(t["kind"], "normal")

    def test_la_semana_de_carrera_se_reconoce(self):
        monterrico = {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B"}
        t = compute_weekly_load_target(BASE_REAL, FASE, competitions=[monterrico], week_dates=SEMANA)
        self.assertEqual(t["kind"], "afinamiento")

    def test_la_descarga_se_reconoce(self):
        self.assertEqual(compute_weekly_load_target(BASE_REAL, {**FASE, "is_deload": True})["kind"], "descarga")

    def test_sin_carga_real_no_hay_numero_inventado(self):
        self.assertIsNone(compute_weekly_load_target({"chronic_weekly_hours": 6.6}, FASE))


class ElBloqueDaHechosNoInstrucciones(unittest.TestCase):
    """El bloque decía "Techo: **220**" y "Cómo se planifica con esto, en este
    orden: 1. las sesiones clave según lo que el Load Focus dice que falta".
    Un número en negrita que gana a los matices, una etiqueta de Garmin
    convertida en criterio, y una plantilla de razonamiento."""

    def setUp(self):
        self.bloque = _format_load_target(compute_weekly_load_target(BASE_REAL, FASE))

    def test_trae_la_cronica_y_el_rango_dicho_como_lo_que_es(self):
        self.assertIn("Carga crónica: 280", self.bloque)
        self.assertIn("no un límite probado en este atleta", self.bloque)

    def test_sin_negritas_ni_techo_ni_receta(self):
        self.assertNotIn("**", self.bloque)
        self.assertNotIn("Techo", self.bloque)
        self.assertNotIn("en este orden", self.bloque)
        self.assertNotIn("Load Focus", self.bloque)


def _dia(fecha, sport, clase, minutos):
    return WorkoutDay(
        date=fecha, day_name_es="X", is_rest=False, sport=sport, focus="f", purpose="p",
        adaptation="a", intensity_class=clase, discipline="carrera",
        total_duration_minutes=float(minutos),
        steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time",
                           duration_value=minutos * 60, target_type=TargetType.NONE,
                           description="d")],
    )


class ElValidadorNoRecortaElCriterioDelEntrenador(unittest.TestCase):
    def test_una_semana_con_mucha_carga_no_es_una_violacion(self):
        """Si el modelo decidió una semana grande, la decidió con la historia
        del atleta delante. El validador no tiene con qué discutírsela."""
        payload = WeeklyPlanPayload(
            athlete_name="T", start_date="2026-09-11", zones_table="N/A",
            weeks=[WeekBlock(week_number=1, week_rationale="", days=[
                _dia("2026-09-11", "swim", "easy", 90), _dia("2026-09-13", "run", "easy", 150),
                _dia("2026-09-16", "run", "vo2max", 60),
            ])],
        )
        report = validate_weekly_plan(payload, {"weekly_load_budget": 10, "load_rates": TASAS_REALES,
                                                "weekly_hours_budget": 1.0}, [])
        tipos = {v["type"] for v in report["violations"]}
        self.assertNotIn("weekly_load_exceeded", tipos)
        self.assertNotIn("weekly_hours_exceeded", tipos)

    def test_el_recorte_de_sesiones_ya_no_existe(self):
        from services.ai.langgraph.validation import plan_validator
        self.assertFalse(hasattr(plan_validator, "_apply_weekly_load_trim"))
        self.assertFalse(hasattr(plan_validator, "_apply_weekly_hours_fallback"))

    def test_la_estimacion_sigue_existiendo_para_auditar(self):
        dias = [_dia("2026-09-16", "run", "vo2max", 60)]
        self.assertEqual(carga_de_la_semana(dias, TASAS_REALES)["carga"], 82)


if __name__ == "__main__":
    unittest.main()
