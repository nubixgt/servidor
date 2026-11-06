"""El día de una carrera está disponible para la carrera (Hugo, 2026-10-09).

Hugo no entrena los domingos: su tabla tiene el domingo de descanso. Su trail era el
domingo 11/10. La semana le puso el sábado 10/10, la víspera, "Carrera · trail controlado
(simulación)" -- 3x4' de subida a RPE 6 y 4x30" de bajada, 65' -- y el domingo quedó
"Descanso: Aquí iba «Dia de competencia», pero ese día no está disponible, así que se quitó".

No hay que adivinar al modelo: el prompt decía a la vez, bajo NEVER VIOLATE, "un día sin nada
es descanso, absolutamente nada", y más abajo "el día de una carrera es `race_day`". La
comprobación de la decisión (`intenciones_fuera_de_la_tabla`) le devolvía la carrera con "ese
día el atleta NO puede entrenar, muévela a otro día": la movió al sábado. Después
`_race_day_violations` exigía la competición el domingo, la red la escribía, y la de
disponibilidad la quitaba. Tres reglas sobre la misma fecha, cada una correcta por su cuenta.

La decisión del responsable: una carrera que el atleta apuntó en su calendario ES su
disponibilidad de ese día; su tabla es su rutina de entrenamiento, no su calendario de
carreras. Se aplica en UN sitio -- `plan_validator.restriccion_del_dia`, la única lectura de
"qué se puede esa fecha" -- y en las reglas del prompt, que tienen que decir lo mismo.

(Los datos de Hugo están en producción y no se leyeron: la cadena se reconstruyó desde el
código y las dos capturas de su semana. Su perfil aquí es el de la forma de su tabla.)
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import (  # noqa: E402
    deportes_de_la_competicion,
    dias_de_carrera,
    intenciones_fuera_de_la_tabla,
    restriccion_del_dia,
    validate_weekly_plan,
)
from web_app.server import build_custom_planning_context, describir_carreras_de_la_semana  # noqa: E402

NOMBRES = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]
FECHAS = [f"2026-10-{d:02d}" for d in range(5, 12)]
SEMANA = [{"date": f, "day_name_es": n} for f, n in zip(FECHAS, NOMBRES, strict=True)]
DOMINGO = "2026-10-11"
TRAIL = {"name": "Trail Volcán", "date": DOMINGO, "priority": "B", "distance": "trail_short"}

PERFIL_HUGO = {
    "name": "Hugo", "sports_preference": "run", "target_distance": "trail_short",
    "sport_days": {
        "Correr": {"Martes": "fijo", "Jueves": "posible", "Sábado": "fijo"},
        "Trail": {"Sábado": "posible"},
        "Fuerza": {"Lunes": "posible", "Miércoles": "posible"},
    },
    "preferred_rest_day": "Domingo",
}


def _disponibilidad(**extra):
    dias = {n: {"available": True, "hours": 1.0, "sports": ["run", "strength"], "blocks": []} for n in NOMBRES}
    dias["Domingo"] = {"available": False, "hours": 0, "sports": [], "blocks": []}
    return {"days": dias, **extra}


def _criticas(texto):
    return texto[texto.index("CRITICAL SCHEDULING RULES"):]


class LaDisponibilidadDeEseDia(unittest.TestCase):
    def test_la_carrera_abre_el_domingo_con_su_deporte(self):
        carreras = dias_de_carrera([TRAIL], FECHAS)
        self.assertEqual(carreras, {DOMINGO: ["run"]})
        r = restriccion_del_dia(_disponibilidad(race_dates=carreras), "Domingo", DOMINGO)
        self.assertTrue(r["available"])
        self.assertEqual(r["sports"], ["run"])
        self.assertIsNone(r["hours"])

    def test_un_espejo_sin_race_dates_lo_saca_de_sus_competiciones(self):
        r = restriccion_del_dia(_disponibilidad(competitions=[TRAIL]), "Domingo", DOMINGO)
        self.assertEqual((r["available"], r["sports"]), (True, ["run"]))

    def test_si_esta_semana_dijo_que_no_puede_gana_el_no(self):
        r = restriccion_del_dia(_disponibilidad(competitions=[TRAIL], unavailable_dates=[DOMINGO]), "Domingo", DOMINGO)
        self.assertFalse(r["available"])

    def test_un_dia_de_su_rutina_suma_el_deporte_de_la_carrera_y_pierde_el_tope_de_horas(self):
        """Una travesía el martes: su martes es de correr con 1 h; ese día también se nada, y más de 1 h."""
        travesia = {"name": "Travesía", "date": "2026-10-06", "distance": "ow_5000"}
        r = restriccion_del_dia(_disponibilidad(competitions=[travesia]), "Martes", "2026-10-06")
        self.assertEqual(r["sports"], ["run", "strength", "swim"])
        self.assertIsNone(r["hours"])

    def test_otro_dia_no_cambia(self):
        self.assertEqual(restriccion_del_dia(_disponibilidad(competitions=[TRAIL]), "Sábado", "2026-10-10"),
                         _disponibilidad()["days"]["Sábado"])

    def test_el_deporte_sale_de_la_distancia_o_de_su_grupo(self):
        self.assertEqual(deportes_de_la_competicion({"distance": "tri_olympic"}), {"swim", "bike", "run"})
        self.assertEqual(deportes_de_la_competicion({"race_type": "Ciclismo · Gravel 100"}), {"bike"})
        self.assertEqual(deportes_de_la_competicion({"race_type": "Otros · algo"}), set())

    def test_sin_saber_su_deporte_el_dia_se_abre_sin_limitarlo(self):
        r = restriccion_del_dia(_disponibilidad(competitions=[{"name": "?", "date": DOMINGO}]), "Domingo", DOMINGO)
        self.assertTrue(r["available"])
        self.assertIsNone(r["sports"])


class LaDecisionNoSeDevuelve(unittest.TestCase):
    def _dia(self, deporte):
        return {"date": DOMINGO, "is_rest": False, "sport": deporte, "focus": "Trail Volcán",
                "session_slot": "unica", "block_index": None}

    def test_la_carrera_del_domingo_ya_no_rompe_la_disponibilidad(self):
        """Antes: day_not_available, y la corrección decía "ese día NO puede, muévela"."""
        self.assertEqual(intenciones_fuera_de_la_tabla([self._dia("run")], _disponibilidad(), SEMANA)[0]["type"],
                         "day_not_available")
        self.assertEqual(intenciones_fuera_de_la_tabla(
            [self._dia("run")], _disponibilidad(race_dates={DOMINGO: ["run"]}), SEMANA), [])

    def test_pero_sigue_sin_ser_un_dia_para_otra_cosa(self):
        fuera = intenciones_fuera_de_la_tabla([self._dia("bike")], _disponibilidad(race_dates={DOMINGO: ["run"]}),
                                              SEMANA)
        self.assertEqual(fuera[0]["type"], "sport_not_allowed")


    def test_si_cerro_ese_dia_la_correccion_no_manda_moverla(self):
        """Lo que convirtió el trail de Hugo en una simulación la víspera: "va a otro día"."""
        from services.ai.langgraph.nodes.weekly_planner_node import mensaje_de_correccion
        dia = {**self._dia("run"), "intensity_class": "race_day"}
        fuera = intenciones_fuera_de_la_tabla(
            [dia], _disponibilidad(competitions=[TRAIL], unavailable_dates=[DOMINGO]), SEMANA)
        texto = mensaje_de_correccion(fuera, "{}")
        self.assertIn("NO la muevas a otro día ni la conviertas en una simulación", texto)


class ElValidadorNoQuitaLaCarrera(unittest.TestCase):
    def _semana(self, minutos=150.0):
        dias = []
        for f, n in zip(FECHAS, NOMBRES, strict=True):
            carrera = f == DOMINGO
            paso = WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                               duration_value=int(minutos * 60), description="Trail Volcán a tu ritmo")
            dias.append(WorkoutDay(date=f, day_name_es=n, is_rest=not carrera, sport="run" if carrera else "rest",
                                   focus="Carrera: Trail Volcán" if carrera else "Descanso", purpose="x",
                                   adaptation="x", intensity_class="race_day" if carrera else None,
                                   total_duration_minutes=minutos if carrera else 0.0,
                                   steps=[paso] if carrera else []))
        return WeeklyPlanPayload(athlete_name="Hugo", start_date=FECHAS[0], zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=dias)])

    def _tipos(self, **extra):
        limites = _disponibilidad(competitions=[TRAIL], **extra)
        return {v["type"] for v in validate_weekly_plan(self._semana(), limites, SEMANA)["violations"]}

    def test_la_carrera_del_domingo_pasa(self):
        tipos = self._tipos(race_dates={DOMINGO: ["run"]})
        self.assertNotIn("day_not_available", tipos)
        self.assertNotIn("hours_exceeded", tipos)    # 150' en un día cuya tabla no da horas
        self.assertNotIn("race_day_conflict", tipos)

    def test_cerrada_esta_semana_no_se_exige_ni_se_escribe(self):
        """Si marcó el domingo como que no puede, la carrera no se exige: exigirla la escribía
        para que la disponibilidad la quitara, que es justo el mensaje que vio Hugo."""
        semana = self._semana()
        semana.weeks[0].days[-1] = WorkoutDay(date=DOMINGO, day_name_es="Domingo", is_rest=True, sport="rest",
                                              focus="Descanso", purpose="x", adaptation="x",
                                              total_duration_minutes=0.0, steps=[])
        limites = _disponibilidad(competitions=[TRAIL], unavailable_dates=[DOMINGO])
        tipos = {v["type"] for v in validate_weekly_plan(semana, limites, SEMANA)["violations"]}
        self.assertNotIn("race_day_conflict", tipos)


class LasReglasDelPromptDicenLoMismo(unittest.TestCase):
    """Lo que va bajo NEVER VIOLATE gana a todo lo demás (tests/test_prompt_coherente.py)."""

    def setUp(self):
        self.carreras = describir_carreras_de_la_semana([TRAIL], FECHAS)
        self.siempre = build_custom_planning_context(PERFIL_HUGO)
        self.semana = build_custom_planning_context(PERFIL_HUGO, None, None, carreras=self.carreras)

    def test_la_carrera_se_describe_con_su_dia_escrito_y_su_deporte(self):
        self.assertEqual(self.carreras, {DOMINGO: "domingo 11/10: «Trail Volcán» (Correr)"})

    def test_sin_carreras_no_cambia_ni_un_caracter(self):
        self.assertEqual(build_custom_planning_context(PERFIL_HUGO, None, None, carreras={}), self.siempre)

    def test_las_reglas_1_a_3_dejan_correr_la_carrera(self):
        criticas = _criticas(self.semana)
        self.assertIn("or on the date of one of the athlete's races this week, for that race: "
                      "domingo 11/10: «Trail Volcán» (Correr)", criticas)
        self.assertIn("except a race's own date, which is available for the race", criticas)
        self.assertIn("(on a race's date, the race's own sports as well)", criticas)

    def test_la_nota_de_la_tabla_y_su_descanso_preferido(self):
        self.assertIn("El dia de una carrera esta disponible para esa carrera", self.semana)
        self.assertIn("The athlete races on 2026-10-11: the race wins over this preference.", self.semana)

    def test_una_carrera_que_cerro_esta_semana_no_se_ofrece(self):
        self.assertEqual(describir_carreras_de_la_semana([TRAIL], FECHAS, cerradas=[DOMINGO]), {})

    def test_el_prompt_de_la_decision_lo_dice_tambien(self):
        import importlib
        nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")
        fuente = Path(nodo.__file__).read_text(encoding="utf-8")
        self.assertIn("A race's own date is available for that race even when the table marks that weekday as "
                      "rest", fuente)


class LaMismaFamilia(unittest.TestCase):
    """Dos reglas más que leían el día de una carrera como si fuera un entreno."""

    def test_la_carga_segura_no_le_pide_a_la_decision_que_baje_la_carrera(self):
        from services.ai import carga_segura as cs

        def revisar(clase):
            return cs.revisar_la_carga(
                [{"date": DOMINGO, "sport": "run", "intensity_class": clase, "duration_minutes": 180}],
                tasas=None, cronica=None, horas_de_resistencia=None, carrera_mas_larga_min=75)
        self.assertEqual(revisar("long_run")["nivel"], cs.EXTREMA)   # una tirada que pusimos nosotros
        self.assertEqual(revisar("race_day")["nivel"], cs.NORMAL)    # la carrera que eligió él

    def test_las_cuestas_la_vispera_de_la_carrera_son_calidad(self):
        """3x4' de subida a RPE 6 el sábado, víspera de su trail B del domingo."""
        paso = WorkoutStep(step_order=1, step_type="interval", duration_type="time", duration_value=240,
                           description="3x4' subida sostenida RPE 6/10")
        sabado = WorkoutDay(date="2026-10-10", day_name_es="Sábado", is_rest=False, sport="run", focus="Trail controlado",
                            purpose="x", adaptation="x", intensity_class="hill_repeats",
                            total_duration_minutes=65.0, steps=[paso])
        carrera = WorkoutDay(date=DOMINGO, day_name_es="Domingo", is_rest=False, sport="run", focus="Carrera: Trail Volcán",
                             purpose="x", adaptation="x", intensity_class="race_day",
                             total_duration_minutes=150.0, steps=[paso])
        semana = WeeklyPlanPayload(athlete_name="Hugo", start_date=FECHAS[0], zones_table="",
                                   weeks=[WeekBlock(week_number=1, days=[sabado, carrera])])
        tipos = {v["type"] for v in validate_weekly_plan(
            semana, _disponibilidad(competitions=[TRAIL], race_dates={DOMINGO: ["run"]}), SEMANA)["violations"]}
        self.assertIn("race_taper_conflict", tipos)


class LaCarreraVaHastaPulsarVuelta(unittest.TestCase):
    """El responsable, 2026-10-09: nadie sabe cuánto dura una carrera hasta que la termina. El
    trail de prueba del atleta 2 iba como "~1 h 50 min" con 6.600 s: si tardaba más, el reloj
    lo mandaba al enfriamiento en plena carrera."""

    def _dia(self, clase="race_day"):
        pasos = [
            WorkoutStep(step_order=1, step_type="warmup", duration_type="time", duration_value=900,
                        description="15' trote muy suave"),
            WorkoutStep(step_order=2, step_type="repeat", repeat_count=4, description="4x progresivos", substeps=[
                WorkoutStep(step_order=1, step_type="interval", duration_type="time", duration_value=20,
                            description='20" progresivos'),
                WorkoutStep(step_order=2, step_type="recovery", duration_type="time", duration_value=60,
                            description='60" muy suave')]),
            WorkoutStep(step_order=3, step_type="main", duration_type="time", duration_value=300,
                        description="5' trote suave hacia la salida"),
            WorkoutStep(step_order=4, step_type="main", duration_type="time", duration_value=6600,
                        description="Trail de prueba (local): 15 km, ~600 m D+, ~1 h 50 min a tope"),
            WorkoutStep(step_order=5, step_type="cooldown", duration_type="time", duration_value=600,
                        description="10' trote muy suave"),
        ]
        dia = WorkoutDay(date=DOMINGO, day_name_es="Domingo", is_rest=False, sport="run", focus="Trail",
                         purpose="x", adaptation="x", intensity_class=clase, total_duration_minutes=145.0,
                         steps=pasos)
        return WeeklyPlanPayload(athlete_name="José", start_date=FECHAS[0], zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=[dia])])

    def test_el_paso_de_la_carrera_y_solo_ese(self):
        from services.ai.langgraph.validation.plan_validator import carrera_hasta_pulsar_vuelta
        semana, cambios = carrera_hasta_pulsar_vuelta(self._dia())
        pasos = semana.weeks[0].days[0].steps
        self.assertEqual([p.duration_type for p in pasos], ["time", None, "time", "lap_button", "time"])
        self.assertIsNone(pasos[3].duration_value)
        self.assertIn("pulsa vuelta al cruzar la meta", pasos[3].description)
        self.assertEqual(len(cambios), 1)
        # Aplicarlo dos veces no cambia nada más.
        self.assertEqual(carrera_hasta_pulsar_vuelta(semana)[1], [])

    def test_si_ya_venia_asi_solo_se_le_dice_que_pulse_vuelta(self):
        """Prueba del 2026-10-09: el codificador ya escribió el 10K como lap_button, con su plan
        de ritmo por kilómetros y sin decir qué hacer en la meta."""
        from services.ai.langgraph.validation.plan_validator import carrera_hasta_pulsar_vuelta
        semana = self._dia()
        paso = semana.weeks[0].days[0].steps[3]
        paso.duration_type, paso.duration_value = "lap_button", None
        semana, cambios = carrera_hasta_pulsar_vuelta(semana)
        self.assertEqual(cambios, [])
        self.assertIn("pulsa vuelta al cruzar la meta", semana.weeks[0].days[0].steps[3].description)

    def test_una_tirada_larga_no_se_toca(self):
        from services.ai.langgraph.validation.plan_validator import carrera_hasta_pulsar_vuelta
        self.assertEqual(carrera_hasta_pulsar_vuelta(self._dia("long_run"))[1], [])

    def test_la_competicion_va_sin_objetivo_y_una_sesion_con_ritmo_lo_conserva(self):
        """El responsable, 2026-10-19: la competición va sin objetivo de ritmo (su plan va en el
        texto). Banco de Haiku 5.5: el 10K del atleta 2 («km 1-2 a 5:28-5:32, km 3-8 a 5:24-5:28, y
        a tope») salió una vez con 5:24-5:32 para toda la carrera. Va en código para no tocar las
        sesiones de carrera a pie, que sí llevan ritmo."""
        from services.ai.langgraph.validation.plan_validator import carrera_hasta_pulsar_vuelta
        semana = self._dia()
        paso = semana.weeks[0].days[0].steps[3]
        paso.duration_type, paso.duration_value = "lap_button", None
        paso.target_type, paso.target_low, paso.target_high = "pace", 324, 332
        semana, cambios = carrera_hasta_pulsar_vuelta(semana)
        self.assertEqual(semana.weeks[0].days[0].steps[3].target_type, "none")
        self.assertIsNone(semana.weeks[0].days[0].steps[3].target_low)
        self.assertEqual(len(cambios), 1)
        series = self._dia("vo2max")
        series.weeks[0].days[0].steps[3].target_type = "pace"
        series.weeks[0].days[0].steps[3].target_low, series.weeks[0].days[0].steps[3].target_high = 300, 310
        series, cambios = carrera_hasta_pulsar_vuelta(series)
        self.assertEqual(cambios, [])
        self.assertEqual(series.weeks[0].days[0].steps[3].target_low, 300)

    def test_al_reloj_llega_como_boton_de_vuelta(self):
        from services.ai.langgraph.validation.plan_validator import carrera_hasta_pulsar_vuelta
        from services.garmin import workout_builder as wb
        dia = carrera_hasta_pulsar_vuelta(self._dia())[0].weeks[0].days[0]
        pasos = wb.build_garmin_workout(dia, wb.AthleteZoneConfig())["workoutSegments"][0]["workoutSteps"]
        self.assertEqual(pasos[3]["endCondition"]["conditionTypeKey"], "lap.button")

    def test_la_red_que_escribe_la_carrera_tambien(self):
        from services.ai.langgraph.validation.plan_validator import _race_day_session_fields
        _, pasos, _ = _race_day_session_fields(TRAIL)
        self.assertEqual(pasos[1].duration_type, "lap_button")


class LaGeneracionLoUsa(unittest.TestCase):
    def test_la_semana_le_pasa_las_carreras_a_la_disponibilidad_y_al_contexto(self):
        from web_app import server
        fuente = Path(server.__file__).read_text(encoding="utf-8")
        self.assertIn('availability_constraints["race_dates"] = {', fuente)
        self.assertIn("carreras=carreras_de_la_semana)", fuente)
        import importlib
        nodo = Path(importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node").__file__)
        self.assertIn("validated_payload, _ = carrera_hasta_pulsar_vuelta(validated_payload)",
                      nodo.read_text(encoding="utf-8"))


if __name__ == "__main__":
    unittest.main()
