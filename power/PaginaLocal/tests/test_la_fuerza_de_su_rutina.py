"""Beta 2.1, paso S5: la fuerza de la semana sale de SU programa, no se reinventa.

En las 8 semanas archivadas de José y Jessie los ejercicios de gimnasio se barajaban
distinto en cada regeneración de la MISMA semana. Con una rutina confirmada (S4) el
código elige qué sesión toca (rotación), el modelo sólo propone cambios con motivo y
una dosis que puede bajar y nunca subir, y el código construye el bloque.

Lo que esta prueba fija:

- **Sin rutina confirmada, el prompt de la decisión no cambia ni un carácter** y el
  codificador sigue recibiendo los días de fuerza (Hugo, que está bien como está).
- La rotación continúa donde la dejó la semana anterior.
- Un cambio sin motivo, un ejercicio que no es de esa sesión o una sesión que no
  existe se descartan; el día se queda con lo que el atleta confirmó.
- La dosis baja las series y nunca las sube.
- Un ejercicio sustituido pierde el nombre de Garmin del viejo.
- La fuerza que no se hace se dice en el día, no desaparece.
- Los bloques se reponen después de la reparación del validador, salvo si el día
  quedó como descanso.

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import importlib
import sys
import unittest
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes import paso_de_fuerza as pf  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    DayIntent,
    FormattedWeekBlock,
    FormattedWorkoutDay,
    StrengthBlock,
    TrainingDecisionNoHitl,
    WeekBlock,
    WeekIntentContent,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.perfil_de_fuerza import leer_perfil_de_fuerza, leer_rutina_de_fuerza  # noqa: E402

NODO = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")


def _ej(id_, nombre, series, reps, **extra):
    return {"id": id_, "name": nombre, "sets": series, "reps": reps, "load": "", "technique": "",
            "source_line": 1, "garmin_category": extra.get("cat"), "garmin_exercise": extra.get("garmin"),
            "video_key": extra.get("video")}


def rutina_de_fernando() -> dict:
    """La Upper/Lower de Fernando, como queda guardada tras confirmarla (S4)."""
    return {
        "source": "atleta", "original_text": "Upper A — 120 min\n...", "confirmed_at": "2026-09-17T18:00:00Z",
        "sessions": [
            {"id": "upper_a", "name": "Upper A", "minutes": 120, "emphasis": "torso", "groups": [
                {"id": "upper_a_g1", "rest_between_rounds_s": 120, "rest_between_exercises_s": None, "exercises": [
                    _ej("upper_a_g1_e1", "Press de banca", 4, "8", cat="BENCH_PRESS", garmin="BARBELL_BENCH_PRESS"),
                    _ej("upper_a_g1_e2", "Elevaciones laterales", 4, "12", cat="LATERAL_RAISE"),
                    _ej("upper_a_g1_e3", "Crunch", 3, "15", cat="CRUNCH", garmin="CRUNCH")]},
                {"id": "upper_a_g2", "rest_between_rounds_s": 120, "rest_between_exercises_s": None, "exercises": [
                    _ej("upper_a_g2_e1", "Dominadas", 4, "10", cat="PULL_UP", garmin="PULL_UP")]},
            ]},
            {"id": "lower_a", "name": "Lower A", "minutes": 60, "emphasis": "pierna", "groups": [
                {"id": "lower_a_g1", "rest_between_rounds_s": 120, "rest_between_exercises_s": None, "exercises": [
                    _ej("lower_a_g1_e1", "Sentadilla", 4, "10", cat="SQUAT"),
                    _ej("lower_a_g1_e2", "Curl femoral", 4, "10", cat="LEG_CURL", garmin="LEG_CURL")]},
            ]},
            {"id": "upper_b", "name": "Upper B", "minutes": 120, "emphasis": "torso", "groups": [
                {"id": "upper_b_g1", "rest_between_rounds_s": 120, "rest_between_exercises_s": None, "exercises": [
                    _ej("upper_b_g1_e1", "Press militar", 4, "10", cat="SHOULDER_PRESS", garmin="MILITARY_PRESS")]},
            ]},
        ],
    }


def perfil(modo="rutina", **extra):
    return leer_perfil_de_fuerza({"strength_profile": {"mode": modo, "goal": "hipertrofia",
                                                       "equipment": "gimnasio", **extra}})


_LA_DE_FERNANDO = object()


def rutina(datos=_LA_DE_FERNANDO):
    return leer_rutina_de_fuerza(
        {"strength_routine": rutina_de_fernando() if datos is _LA_DE_FERNANDO else datos})


def _dia(fecha, deporte="strength", **extra):
    base = {"date": fecha, "day_name_es": "Lunes", "is_rest": False, "sport": deporte, "focus": "Upper A",
            "purpose": "Su rutina de tren superior.", "adaptation": "Si llega cansado, una ronda menos.",
            "total_duration_minutes": 60, "intensity_class": "strength_freeform", "discipline": "fuerza",
            "session_sketch": ""}
    base.update(extra)
    return DayIntent(**base)


def semana(*dias) -> WeekIntentContent:
    return WeekIntentContent(week_number=1, days=list(dias), week_rationale="r")


class SinRutinaTodoSigueIgual(unittest.TestCase):
    """Hugo: le gusta la fuerza como está, y no ha contestado nada."""

    def test_el_prompt_de_la_decision_no_cambia_ni_un_caracter(self):
        campos = {c: "" for c in ("week_number", "total_weeks", "dias_del_bloque", "athlete_name",
                                  "current_date", "week_dates", "competitions", "planning_context",
                                  "prescription_card", "season_plan", "mesocycle_position", "volume_target",
                                  "training_habits", "load_state", "recovery_state", "athlete_checkin",
                                  "niggle_constraints", "coach_notebook", "peticiones_del_atleta", "season_constraints",
                                  "previous_plan", "adherence", "metrics_analysis", "activity_analysis",
                                  "physiology_analysis", "previous_weeks_summary", "trail_guidance",
                                  "rationale_de_cambios")}
        sin_fuerza = NODO.TRAINING_DECISION_USER_PROMPT.format(rutina_de_fuerza="", **campos)
        con_fuerza = NODO.TRAINING_DECISION_USER_PROMPT.format(
            rutina_de_fuerza="\n### Su rutina\n- Upper A\n", **campos)
        self.assertNotIn("rutina", sin_fuerza.lower())
        self.assertIn("### Su rutina", con_fuerza)
        self.assertEqual(len(con_fuerza) - len(sin_fuerza), len("\n### Su rutina\n- Upper A\n"))

    def test_sin_rutina_confirmada_no_hay_dias_que_cubrir(self):
        una_semana = semana(_dia("2026-09-21"))
        for guardada in (None, {"original_text": "Upper A", "confirmed_at": None, "sessions": []}):
            with self.subTest(guardada=guardada):
                self.assertEqual(NODO.dias_de_su_rutina(una_semana, rutina(guardada)), [])
        self.assertEqual(NODO.dias_de_su_rutina(una_semana, None), [])

    def test_con_rutina_confirmada_si(self):
        dias = NODO.dias_de_su_rutina(semana(_dia("2026-09-21"), _dia("2026-09-22", deporte="run")), rutina())
        self.assertEqual([d.date for d in dias], ["2026-09-21"])


class LaColocacionMiraLaSemana(unittest.TestCase):
    """Lo que Jessie vio el 2026-09-17: con 3 días fijos y una rotación por turno, el
    lunes le tocaba brazo y el lunes siguiente pierna, sin mirar que el lunes lleva
    calidad. Ahora cada día se queda con la sesión que menos estorba, y si la de pierna
    no cabe lejos de la calidad se coloca igual y se le DICE (decisión del responsable).
    """

    def _semana(self):
        return semana(
            _dia("2026-09-21", focus="Fuerza"),                                        # lunes
            _dia("2026-09-22", deporte="run", focus="5K pace", intensity_class="repeticiones"),
            _dia("2026-09-23", focus="Fuerza"),                                        # miércoles
            _dia("2026-09-24", deporte="swim", focus="Técnica", intensity_class="cross_training"),
            _dia("2026-09-25", focus="Fuerza"),                                        # viernes
            _dia("2026-09-27", deporte="run", focus="Tirada larga", intensity_class="long_run"),
        )

    def test_la_pierna_no_cae_el_dia_antes_de_la_calidad_si_hay_donde(self):
        elegidas, avisos = pf.colocar_las_sesiones(
            rutina(), ["2026-09-21", "2026-09-23", "2026-09-25"], self._semana())
        por_dia = dict(zip(["2026-09-21", "2026-09-23", "2026-09-25"], elegidas, strict=True))
        self.assertNotEqual(por_dia["2026-09-21"], "lower_a")   # el martes hay series
        self.assertEqual(por_dia["2026-09-23"], "lower_a")      # el jueves nada, ahí cabe
        self.assertEqual(avisos, [])

    def test_no_repite_el_mismo_enfasis_en_48_horas(self):
        elegidas, _ = pf.colocar_las_sesiones(
            rutina(), ["2026-09-21", "2026-09-22", "2026-09-23"], semana(_dia("2026-09-21")))
        enfasis = [pf.enfasis_de(next(x for x in pf.sesiones(rutina()) if x["id"] == i)) for i in elegidas]
        self.assertNotEqual(enfasis[0], enfasis[1])

    def test_entre_las_que_caben_se_elige_la_menos_hecha(self):
        """Sustituye al contador de rotación: ninguna se queda sin hacerse."""
        hecha = StrengthBlock(warmup="", groups=[], routine_session_id="upper_a")
        dia = WorkoutDay(date="2026-09-14", day_name_es="Lunes", is_rest=False, sport="strength",
                         focus="Upper A", purpose="p", adaptation="a", strength_block=hecha,
                         steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time",
                                            duration_value=3600, description="Upper A")])
        anterior = WeeklyPlanPayload(athlete_name="Fernando", start_date="2026-09-14", zones_table="",
                                     weeks=[WeekBlock(week_number=1, days=[dia], week_rationale="r")])
        self.assertEqual(pf.usos_por_sesion(anterior, ["upper_a", "lower_a", "upper_b"]),
                         {"upper_a": 1, "lower_a": 0, "upper_b": 0})
        elegidas, _ = pf.colocar_las_sesiones(rutina(), ["2026-09-21"], semana(_dia("2026-09-21")), anterior)
        self.assertNotEqual(elegidas, ["upper_a"])

    def test_si_no_cabe_lejos_se_coloca_igual_y_se_avisa_con_el_porque(self):
        """Un solo día de fuerza, y al día siguiente la tirada larga: no hay dónde."""
        una_semana = semana(
            _dia("2026-09-26", focus="Fuerza"),
            _dia("2026-09-27", deporte="run", focus="Tirada larga", intensity_class="long_run"),
        )
        solo_pierna = {**rutina_de_fernando(),
                       "sessions": [s for s in rutina_de_fernando()["sessions"] if s["id"] == "lower_a"]}
        elegidas, avisos = pf.colocar_las_sesiones(rutina(solo_pierna), ["2026-09-26"], una_semana)
        self.assertEqual(elegidas, ["lower_a"])          # no se quita: se coloca igual
        self.assertEqual(len(avisos), 1)
        self.assertEqual(avisos[0]["date"], "2026-09-26")
        # Una línea que se lee en el móvil: qué pasa, contra qué sesión y qué puede hacer.
        # El porqué largo (la economía de carrera) no lo leía nadie (el responsable, 2026-09-23).
        self.assertIn("Carga piernas el día antes de tu Tirada larga", avisos[0]["text"])
        self.assertIn("mueve o añade uno", avisos[0]["text"])
        self.assertLess(len(avisos[0]["text"]), 160)

    def test_el_aviso_llega_al_dia_del_atleta(self):
        dia = {"date": "2026-09-26", "day_name_es": "Sábado", "session_slot": "unica", "minutes": 60,
               "focus": "Fuerza", "purpose": "p", "adaptation": "a",
               "intensity_class": "strength_freeform", "discipline": "fuerza", "session_id": "lower_a"}
        construidos = pf.dias_construidos(rutina(), [dia], [pf.FuerzaDelDia(date="2026-09-26",
                                                                           session_id="lower_a")])
        pf.avisar_en_los_dias(construidos, [{"date": "2026-09-26", "text": "Cae antes de tu tirada larga."}])
        self.assertEqual(construidos[0].strength_block.placement_note, "Cae antes de tu tirada larga.")
        self.assertEqual(construidos[0].strength_block.notes, "")  # nada de párrafos

    def test_un_enfasis_que_no_se_sabe_no_se_da_por_pierna(self):
        """Hasta el 2026-09-23 lo desconocido se trataba como cuerpo completo "por prudencia",
        y la rutina del atleta 2 (confirmada antes de que existiera `emphasis`) dio
        "«Upper B» carga piernas" el día antes de su natación. Un aviso falso enseña a no
        leerlos: lo que no se puede afirmar se deduce o no avisa."""
        self.assertIsNone(pf.enfasis_de({"emphasis": "brazos"}))
        self.assertIsNone(pf.enfasis_de({}))
        self.assertEqual(pf.enfasis_de({"emphasis": "PIERNA"}), pf.PIERNA)


class ElBloqueLoEscribeElCodigo(unittest.TestCase):
    def setUp(self):
        self.sesion = rutina_de_fernando()["sessions"][0]

    def _bloque(self, **ajuste):
        datos = {"date": "2026-09-21", "session_id": "upper_a"}
        datos.update(ajuste)
        return pf.bloque_de_fuerza(self.sesion, pf.FuerzaDelDia(**datos))

    def test_sin_cambios_es_su_sesion_tal_cual(self):
        bloque = self._bloque()
        self.assertEqual([g.label for g in bloque.groups], ["A", "B"])
        self.assertEqual([(e.name, e.sets, e.reps) for e in bloque.groups[0].exercises],
                         [("Press de banca", 4, "8"), ("Elevaciones laterales", 4, "12"), ("Crunch", 3, "15")])
        self.assertEqual(bloque.routine_session_id, "upper_a")
        self.assertEqual(bloque.notes, "")
        # La lista plana sigue siendo el aplanado de los grupos: lectores viejos intactos.
        self.assertEqual(len(bloque.exercises), 4)

    def test_la_dosis_baja_las_series_y_nunca_las_sube(self):
        # Press 4, elevaciones 4, crunch 3. El crunch también baja: con `ceil` se quedaba en 3
        # y una sesión de ejercicios de 3 series salía "reducida" sin una serie menos
        # (Lower A de la atleta 3, 2026-09-23).
        self.assertEqual([e.sets for e in self._bloque(dose="reducida").groups[0].exercises], [3, 3, 2])
        self.assertEqual([e.sets for e in self._bloque(dose="minima").groups[0].exercises], [2, 2, 2])
        subida = self._bloque(changes=[pf.CambioDeEjercicio(exercise_id="upper_a_g1_e1", sets=8, reason="más")])
        self.assertEqual(pf.series_con_dosis(8, "minima"), 4)
        # Su programa dice 4: el paso no puede subir de ahí ni con motivo.
        self.assertEqual(subida.groups[0].exercises[0].sets, 4)

    def test_no_se_recorta_dos_veces(self):
        """Primera llamada real (2026-09-17, gpt-5-mid): bajó la sentadilla de 4 a 3 Y puso
        dosis reducida. Aplicar además el factor dejaría 2 series que nadie decidió."""
        bloque = self._bloque(dose="reducida", changes=[pf.CambioDeEjercicio(
            exercise_id="upper_a_g1_e1", sets=3, reason="el martes tiene series")])
        self.assertEqual(bloque.groups[0].exercises[0].sets, 3)
        self.assertEqual(bloque.groups[0].exercises[1].sets, 3)  # sin cambio propio, la dosis manda

    def test_la_dosis_se_dice_en_el_bloque(self):
        """En su campo, no en un párrafo: la pantalla enseña "Dosis reducida" y el porqué a un toque."""
        bloque = self._bloque(dose="reducida", dose_reason="es semana de afinamiento de tu 5K")
        self.assertEqual(bloque.dose, "reducida")
        self.assertIn("afinamiento", bloque.dose_reason)
        self.assertEqual(bloque.notes, "")
        self.assertEqual(self._bloque().dose, "")  # la normal no se anuncia

    def test_un_cambio_lleva_su_motivo_al_bloque(self):
        bloque = self._bloque(changes=[pf.CambioDeEjercicio(
            exercise_id="upper_a_g1_e2", sets=3, reason="el hombro derecho te molestó el jueves")])
        laterales = bloque.groups[0].exercises[1]
        self.assertEqual(laterales.sets, 3)
        self.assertEqual(laterales.change, "−1 serie")
        self.assertIn("el hombro derecho", laterales.change_reason)
        self.assertEqual(bloque.groups[0].exercises[0].change, "")  # lo que no cambió, sin etiqueta

    def test_con_la_dosis_bajada_las_series_de_menos_no_se_etiquetan_una_a_una(self):
        bloque = self._bloque(dose="minima", changes=[pf.CambioDeEjercicio(
            exercise_id="upper_a_g1_e2", sets=2, reason="para no alargar la sesión")])
        self.assertEqual(bloque.groups[0].exercises[1].sets, 2)
        self.assertEqual(bloque.groups[0].exercises[1].change, "")
        self.assertEqual(bloque.dose, "minima")

    def test_un_ejercicio_sustituido_pierde_el_nombre_de_garmin_del_viejo(self):
        bloque = self._bloque(changes=[pf.CambioDeEjercicio(
            exercise_id="upper_a_g1_e1", replace_with="Press con mancuernas",
            reason="el banco con barra está ocupado los lunes")])
        press = bloque.groups[0].exercises[0]
        self.assertEqual(press.name, "Press con mancuernas")
        self.assertEqual(press.garmin_exercise, "")
        self.assertEqual(press.garmin_category, "")
        self.assertEqual(bloque.groups[0].exercises[2].garmin_exercise, "CRUNCH")

    def test_quitar_un_ejercicio_se_dice(self):
        bloque = self._bloque(changes=[pf.CambioDeEjercicio(
            exercise_id="upper_a_g1_e3", skip=True, reason="hoy no hay abdominal, vienes de la tirada larga")])
        self.assertEqual([e.name for e in bloque.groups[0].exercises],
                         ["Press de banca", "Elevaciones laterales"])
        self.assertEqual(bloque.skipped, [{"name": "Crunch",
                                           "reason": "hoy no hay abdominal, vienes de la tirada larga"}])

    def test_los_descansos_entre_rondas_son_los_suyos(self):
        self.assertEqual([g.rest_between_rounds_s for g in self._bloque().groups], [120, 120])


class LoQueNoSeDaPorBueno(unittest.TestCase):
    def _ajustes(self, respuesta):
        async def llamar(_mensajes):
            return respuesta
        dias = [{"date": "2026-09-21", "day_name_es": "Lunes", "session_id": "upper_a", "minutes": 60}]
        return asyncio.run(pf.ajustar_la_fuerza(rutina(), perfil(), dias, "", "", "", "", "", llamar))

    def test_un_cambio_sin_motivo_se_descarta(self):
        respuesta = pf.AjusteDeFuerza(days=[pf.FuerzaDelDia(
            date="2026-09-21", session_id="upper_a",
            changes=[pf.CambioDeEjercicio(exercise_id="upper_a_g1_e1", sets=2, reason="   ")])])
        self.assertEqual(self._ajustes(respuesta)[0].changes, [])

    def test_un_ejercicio_de_otra_sesion_se_descarta(self):
        respuesta = pf.AjusteDeFuerza(days=[pf.FuerzaDelDia(
            date="2026-09-21", session_id="upper_a",
            changes=[pf.CambioDeEjercicio(exercise_id="lower_a_g1_e1", sets=2, reason="cansancio")])])
        self.assertEqual(self._ajustes(respuesta)[0].changes, [])

    def test_una_sesion_que_no_es_suya_vuelve_a_la_rotacion(self):
        respuesta = pf.AjusteDeFuerza(days=[pf.FuerzaDelDia(date="2026-09-21", session_id="pierna_inventada")])
        self.assertEqual(self._ajustes(respuesta)[0].session_id, "upper_a")

    def test_una_dosis_que_no_existe_es_normal(self):
        respuesta = pf.AjusteDeFuerza(days=[pf.FuerzaDelDia(date="2026-09-21", session_id="upper_a", dose="doble")])
        self.assertEqual(self._ajustes(respuesta)[0].dose, "normal")

    def test_un_dia_que_no_contesto_se_hace_tal_cual(self):
        self.assertEqual(self._ajustes(pf.AjusteDeFuerza(days=[]))[0].session_id, "upper_a")

    def test_si_la_llamada_falla_la_semana_no_se_pierde(self):
        async def llamar(_mensajes):
            raise RuntimeError("el proveedor no contestó")
        dias = [{"date": "2026-09-21", "day_name_es": "Lunes", "session_id": "upper_a", "minutes": 60}]
        ajustes = asyncio.run(pf.ajustar_la_fuerza(rutina(), perfil(), dias, "", "", "", "", "", llamar))
        self.assertEqual([(a.session_id, a.dose) for a in ajustes], [("upper_a", "normal")])


class LaIAPuedeAnadirConCorreaCorta(unittest.TestCase):
    """El responsable lo pidió el 2026-09-17: con un programa fijo, la IA se había
    quedado sin poder responder a una molestia declarada con el trabajo que pide
    (elevaciones de talón para un aquiles, por ejemplo). Ahora puede AÑADIR."""

    def _bloque(self, *anadidos):
        sesion = rutina_de_fernando()["sessions"][0]
        return pf.bloque_de_fuerza(sesion, pf.FuerzaDelDia(
            date="2026-09-21", session_id="upper_a", additions=list(anadidos)))

    def test_lo_anadido_va_en_su_grupo_y_dice_por_que(self):
        bloque = self._bloque(pf.EjercicioAnadido(
            name="Elevación de talones de pie", sets=3, reps="15", load="peso corporal",
            reason="tu molestia de aquiles pide cargarlo progresivamente"))
        ultimo = bloque.groups[-1]
        self.assertEqual([e.name for e in ultimo.exercises], ["Elevación de talones de pie"])
        self.assertEqual(ultimo.exercises[0].change, "nuevo esta semana")
        self.assertIn("aquiles", ultimo.exercises[0].change_reason)
        # Y con su imagen, resuelta por alias exacto como todo lo demás.
        self.assertEqual(ultimo.exercises[0].video_key, "elevacion_de_talones_de_pie")

    def test_si_escribe_la_CLAVE_el_atleta_ve_el_nombre(self):
        """Primera llamada real (2026-09-17): escribió "arco_corto" donde iba el nombre,
        y eso es lo que el atleta habría leído en el gimnasio."""
        bloque = self._bloque(pf.EjercicioAnadido(
            name="arco_corto", sets=2, reps="10-15", reason="soporte del arco para tu aquiles"))
        self.assertEqual(bloque.groups[-1].exercises[0].name, "Arco corto")
        self.assertEqual(bloque.groups[-1].exercises[0].video_key, "arco_corto")
        self.assertEqual(bloque.groups[-1].exercises[0].change, "nuevo esta semana")

    def test_sin_motivo_no_se_anade(self):
        limpios = pf._anadidos_saneados(
            [pf.EjercicioAnadido(name="Plancha", sets=3, reps="40 s", reason="  ")], "2026-09-21")
        self.assertEqual(limpios, [])

    def test_hay_un_tope(self):
        muchos = [pf.EjercicioAnadido(name=f"Ejercicio {i}", sets=3, reps="10", reason="porque sí")
                  for i in range(5)]
        self.assertEqual(len(pf._anadidos_saneados(muchos, "2026-09-21")), pf.MAX_ANADIDOS)

    def test_el_programa_confirmado_no_se_toca(self):
        antes = rutina_de_fernando()
        self._bloque(pf.EjercicioAnadido(name="Plancha", sets=3, reps="40 s", reason="core flojo"))
        self.assertEqual(rutina_de_fernando(), antes)

    def test_solo_se_puede_anadir_si_hay_algo_declarado(self):
        """Un atleta sano no recibe prehab que no pidió: el catálogo ni siquiera viaja."""
        self.assertFalse(pf.puede_anadir("", ""))
        self.assertTrue(pf.puede_anadir("Molestia en el aquiles, 2/10", ""))
        self.assertTrue(pf.puede_anadir("", "La fuerza en esta fase busca: mantener lo ganado"))

    def test_el_catalogo_viaja_solo_cuando_se_puede_anadir(self):
        vistos = {}

        async def llamar(mensajes):
            vistos.setdefault("prompts", []).append(mensajes[-1]["content"])
            return pf.AjusteDeFuerza(days=[])

        dias = [{"date": "2026-09-21", "day_name_es": "Lunes", "session_id": "upper_a", "minutes": 60}]
        asyncio.run(pf.ajustar_la_fuerza(rutina(), perfil(), dias, "", "", "", "", "", llamar))
        asyncio.run(pf.ajustar_la_fuerza(rutina(), perfil(), dias, "", "", "",
                                         "Molestia en el aquiles, 2/10", "", llamar))
        self.assertNotIn("sentadilla_goblet", vistos["prompts"][0])
        self.assertIn("sentadilla_goblet", vistos["prompts"][1])


class ElRegistroDeLoQueLevanto(unittest.TestCase):
    """Beta 2.1: lo que el atleta APUNTÓ (Database/026) es la única fuente de kilos de
    la app, y es lo que permite progresar la carga. Garmin no lo da: devuelve
    repeticiones y descansos, pero el ejercicio llega `UNKNOWN` y el peso a 0 (medido
    el 2026-09-17 en la actividad 24371594337 del atleta 2)."""

    LOGS = [{"fecha": "2026-09-14", "session_slot": "unica", "routine_session_id": "upper_a",
             "note": "el hombro, bien",
             "exercises": [{"exercise_id": "upper_a_g1_e1", "name": "Press de banca", "sets": 4,
                            "reps": "8", "weight_kg": 60, "rpe": 8, "done": True},
                           {"exercise_id": "upper_a_g1_e3", "name": "Crunch", "sets": 3, "reps": "15",
                            "weight_kg": None, "rpe": None, "done": False}]}]

    def test_sin_registro_no_se_dice_nada(self):
        self.assertEqual(pf.lo_que_levanto([]), "")
        self.assertEqual(pf.lo_que_levanto(None), "")
        self.assertEqual(pf.lo_que_levanto([{"fecha": "2026-09-14", "exercises": []}]), "")

    def test_lo_que_levanto_llega_con_sus_kilos(self):
        bloque = pf.lo_que_levanto(self.LOGS, rutina())
        self.assertIn("Press de banca 4x8 60 kg RPE 8", bloque)
        self.assertIn("(Upper A)", bloque)          # la sesión, con su nombre
        self.assertIn("(no la hizo)", bloque)        # lo que NO hizo también se dice
        self.assertIn("el hombro, bien", bloque)

    def test_el_paso_de_fuerza_lo_recibe(self):
        visto = {}

        async def llamar(mensajes):
            visto["prompt"] = mensajes[-1]["content"]
            return pf.AjusteDeFuerza(days=[])

        dias = [{"date": "2026-09-21", "day_name_es": "Lunes", "session_id": "upper_a", "minutes": 60}]
        asyncio.run(pf.ajustar_la_fuerza(rutina(), perfil(), dias, "", "", "", "", "", llamar,
                                         registro=pf.lo_que_levanto(self.LOGS, rutina())))
        self.assertIn("60 kg", visto["prompt"])
        self.assertIn("los kilos son un dato", visto["prompt"])


class LosDiasConstruidos(unittest.TestCase):
    def _dias(self, ajustes):
        dia = {"date": "2026-09-21", "day_name_es": "Lunes", "session_slot": "unica", "minutes": 60,
               "focus": "Upper A", "purpose": "p", "adaptation": "a",
               "intensity_class": "strength_freeform", "discipline": "fuerza", "session_id": "upper_a"}
        return pf.dias_construidos(rutina(), [dia], ajustes)

    def test_un_dia_de_fuerza_entero_sin_pasar_por_el_codificador(self):
        dia = self._dias([pf.FuerzaDelDia(date="2026-09-21", session_id="upper_a")])[0]
        self.assertEqual((dia.sport, dia.is_rest, dia.discipline), ("strength", False, "fuerza"))
        self.assertEqual(len(dia.steps), 1)
        self.assertEqual(dia.steps[0].duration_value, 3600)
        self.assertEqual(dia.strength_block.routine_session_id, "upper_a")

    def test_la_dosis_tambien_acorta_la_sesion(self):
        """Su Upper A dura 120' en su programa: a dosis mínima, 60'. La semana le dio 60'."""
        dia = self._dias([pf.FuerzaDelDia(date="2026-09-21", session_id="upper_a", dose="minima")])[0]
        self.assertEqual(dia.total_duration_minutes, 60)

    def test_los_minutos_no_se_recortan_dos_veces(self):
        """Atleta 2, semana del 2026-09-28: la semana dio 42' a su Upper A (120' en su programa),
        el paso puso dosis mínima y la sesión salió de 21' con cinco ejercicios."""
        self.assertEqual(pf.minutos_de({"minutes": 120}, "minima", 42), 42)
        self.assertEqual(pf.minutos_de({"minutes": 60}, "reducida", 60), 42)
        self.assertEqual(pf.minutos_de({}, "normal", 50), 50)

    def test_la_fuerza_que_no_se_hace_se_dice(self):
        dia = self._dias([pf.FuerzaDelDia(date="2026-09-21", session_id="upper_a", dose="ninguna",
                                          dose_reason="compites el domingo")])[0]
        self.assertTrue(dia.is_rest)
        self.assertIn("Aquí iba «Upper A»", dia.purpose)
        self.assertIn("compites el domingo", dia.purpose)

    def test_quitarlo_todo_no_es_una_dosis(self):
        """Si los cambios vacían la sesión, se hace tal cual: no se pierde el día."""
        cambios = [pf.CambioDeEjercicio(exercise_id=e, skip=True, reason="x")
                   for e in ("upper_a_g1_e1", "upper_a_g1_e2", "upper_a_g1_e3", "upper_a_g2_e1")]
        dia = self._dias([pf.FuerzaDelDia(date="2026-09-21", session_id="upper_a", changes=cambios)])[0]
        self.assertEqual(len(dia.strength_block.exercises), 4)


class SeReponenDespuesDeLaReparacion(unittest.TestCase):
    def _payload(self, **cambios):
        dia = WorkoutDay(date="2026-09-21", day_name_es="Lunes", is_rest=False, sport="strength",
                         focus="Lo que escribió la reparación", purpose="p", adaptation="a",
                         steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time",
                                            duration_value=1800, description="Fuerza")],
                         strength_block=StrengthBlock(warmup="", exercises=[]))
        for k, v in cambios.items():
            setattr(dia, k, v)
        return WeeklyPlanPayload(athlete_name="Fernando", start_date="2026-09-21", zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=[dia], week_rationale="r")])

    def _construidos(self):
        dia = {"date": "2026-09-21", "day_name_es": "Lunes", "session_slot": "unica", "minutes": 60,
               "focus": "Upper A", "purpose": "p", "adaptation": "a",
               "intensity_class": "strength_freeform", "discipline": "fuerza", "session_id": "upper_a"}
        construido = pf.dias_construidos(rutina(), [dia], [pf.FuerzaDelDia(date="2026-09-21",
                                                                          session_id="upper_a")])[0]
        return {("2026-09-21", "unica"): construido}

    def test_la_reparacion_no_se_lleva_por_delante_su_rutina(self):
        payload = self._payload()
        self.assertEqual(pf.reponer_la_fuerza(payload, self._construidos()), 1)
        bloque = payload.weeks[0].days[0].strength_block
        self.assertEqual(bloque.routine_session_id, "upper_a")
        self.assertEqual(len(bloque.exercises), 4)

    def test_un_dia_que_el_validador_dejo_en_descanso_no_resucita(self):
        payload = self._payload(is_rest=True, sport="rest", focus="Descanso",
                                purpose="Aquí iba «Upper A», pero tu tabla no da el lunes, así que se quitó.")
        self.assertEqual(pf.reponer_la_fuerza(payload, self._construidos()), 0)
        self.assertTrue(payload.weeks[0].days[0].is_rest)


class LosBloquesDelPrompt(unittest.TestCase):
    def test_el_resumen_para_la_decision_no_lleva_ejercicios(self):
        resumen = pf.resumen_de_la_rutina(rutina())
        self.assertIn("Upper A", resumen)
        self.assertIn("4 ejercicios", resumen)
        self.assertNotIn("Press de banca", resumen)

    def test_el_programa_del_paso_de_fuerza_si_los_lleva_con_sus_ids(self):
        programa = pf.programa_para_el_prompt(rutina())
        self.assertIn("Press de banca 4x8", programa)
        self.assertIn("[id: upper_a_g1_e1]", programa)
        self.assertIn("120s entre rondas", programa)

    def test_los_avisos_de_la_semana(self):
        self.assertIn("AFINAMIENTO", pf.avisos_de_la_semana({"race_taper": True}))
        self.assertIn("recuperación", pf.avisos_de_la_semana({"race_recovery": True}))
        self.assertEqual(pf.avisos_de_la_semana(None), "")

    def test_la_vuelta_de_carrera_dice_si_sus_dias_faciles_ya_pasaron(self):
        """Bloque real del atleta 2 del 2026-09-26 al 2026-10-04, su 5K el domingo 20/09
        (`carrera_reciente`: days_since 6, easy_days 2). La decisión leía "ya se cumplieron
        antes de esta semana"; el paso de fuerza sólo "Acaba de competir: la semana es de
        recuperación", y bajó a mínima sus tres fuerzas (28/09, 29/09 y 01/10) con el motivo
        "Vienes de competir". Ahora recibe lo mismo que la decisión."""
        cinco_k = {"name": "5k Monterrico", "date": "2026-09-20", "days_since": 6, "easy_days": 2}
        texto = pf.avisos_de_la_semana({"race_recovery": cinco_k})
        self.assertIn("terminaron antes de esta semana", texto)
        self.assertIn("ya no es motivo para bajar la fuerza", texto)
        self.assertNotIn("la semana es de recuperación", texto)
        # Con días fáciles todavía por delante sí se dice, y cuántos.
        self.assertIn("le quedan ~2 días fáciles", pf.avisos_de_la_semana(
            {"race_recovery": {**cinco_k, "days_since": 1}}))
        # Multideporte (sin regla de días fáciles): como siempre.
        self.assertIn("la semana es de recuperación", pf.avisos_de_la_semana(
            {"race_recovery": {**cinco_k, "easy_days": None}}))

    def test_el_lint_de_dosis_no_salta_pasados_sus_dias_faciles(self):
        cinco_k = {"name": "5k", "date": "2026-09-20", "days_since": 6, "easy_days": 2}
        normal = [SimpleNamespace(dose="normal")]
        self.assertEqual(pf.aviso_de_dosis(normal, {"race_recovery": cinco_k}), "")
        self.assertIn("vuelta de una carrera", pf.aviso_de_dosis(normal, {"race_recovery": {**cinco_k, "days_since": 1}}))

    def test_la_semana_alrededor_dice_que_hay_cada_dia(self):
        texto = pf.semana_alrededor(
            semana(_dia("2026-09-21"), _dia("2026-09-22", deporte="run", focus="5K pace"),
                   _dia("2026-09-23", deporte="rest", is_rest=True)),
            {"2026-09-21"})
        self.assertIn("fuerza de su rutina", texto)
        self.assertIn("run 5K pace", texto)
        self.assertIn("descanso", texto)


class LaSemanaEntera(unittest.TestCase):
    """El nodo semanal completo, con los modelos fuera: el cableado de S5 de punta a punta.

    Fija lo que ninguna prueba de una pieza suelta ve: que los días de su rutina SALEN
    del prompt del codificador (son ~12k caracteres de catálogos que ya no hacen falta),
    que vuelven a la semana escritos por el código, y que el atleta sin rutina recibe
    exactamente el mismo prompt de siempre.
    """

    def _correr(self, guardada, respuesta_de_fuerza=None, modo='rutina'):
        capturado = {"llamadas": []}

        class _Llamada:
            def __init__(self, modelo, respuestas, registro):
                self.modelo, self.respuestas, self.registro = modelo, respuestas, registro

            async def ainvoke(self, mensajes):
                self.registro.append((self.modelo.__name__, mensajes))
                return self.respuestas[self.modelo.__name__]

        class _LLM:
            def __init__(self, respuestas, registro):
                self.respuestas, self.registro = respuestas, registro

            def bind_tools(self, _tools):
                return self

            def with_structured_output(self, modelo):
                return _Llamada(modelo, self.respuestas, self.registro)

        decision = TrainingDecisionNoHitl(output=semana(
            _dia("2026-09-21"),
            _dia("2026-09-22", deporte="run", focus="5K pace", intensity_class="repeticiones",
                 discipline="carrera", session_sketch="6x400m"),
        ))
        formateada = FormattedWeekBlock(week_number=1, week_rationale="r", days=[FormattedWorkoutDay(
            date="2026-09-22", day_name_es="Martes", is_rest=False, sport="run", focus="5K pace",
            purpose="p", adaptation="a", total_duration_minutes=60, intensity_class="repeticiones",
            discipline="carrera",
            steps=[{"step_order": 1, "step_type": "main", "duration_type": "time", "duration_value": 3600,
                    "description": "6x400m", "target_type": "none"}])])
        respuestas = {
            "TrainingDecisionNoHitl": decision,
            "FormattedWeekBlock": formateada,
            "AjusteDeFuerza": respuesta_de_fuerza or pf.AjusteDeFuerza(days=[
                pf.FuerzaDelDia(date="2026-09-21", session_id="upper_a", dose="reducida",
                                dose_reason="el martes lleva series")]),
        }
        llm = _LLM(respuestas, capturado["llamadas"])

        async def sin_reparar(payload, **_kwargs):
            # El tier-2 reescribe la semana entera: se simula devolviendo los días
            # con el bloque de fuerza VACIADO, que es el riesgo real que S5 cubre.
            for semana_ in payload.weeks:
                for dia in semana_.days:
                    if dia.sport == "strength":
                        dia.strength_block = StrengthBlock(warmup="", exercises=[])
            return payload, {"errors": []}

        estado = {
            "user_id": "u_prueba", "athlete_name": "Fernando", "hitl_enabled": False,
            "current_date": {"date": "2026-09-19"}, "competitions": [], "planning_context": "",
            "week_dates": [{"date": "2026-09-21", "day_name": "Monday", "day_name_es": "Lunes"},
                           {"date": "2026-09-22", "day_name": "Tuesday", "day_name_es": "Martes"}],
            "availability_constraints": {"fuerza": {
                "strength_profile": {"mode": modo, "goal": "hipertrofia", "equipment": "gimnasio"},
                "strength_routine": guardada}},
            "prescription": {}, "adherence": {}, "season_plan": {"output": ""},
            "weekly_planner_messages": [],
            # Los tres expertos, sin salida: el nodo ya lo tolera (un experto que falla
            # no se lleva la semana por delante) y aquí no es lo que se prueba.
            "metrics_outputs": None, "activity_outputs": None, "physiology_outputs": None,
        }
        with patch.object(NODO.ModelSelector, "get_llm", return_value=llm), \
             patch.object(NODO, "configure_node_tools", return_value=[]), \
             patch.object(NODO, "get_stream_writer", return_value=lambda _x: None), \
             patch.object(NODO, "_load_season_phases", return_value=None), \
             patch.object(NODO, "_load_previous_plan", return_value=None), \
             patch.object(NODO, "validate_and_repair_weekly_plan", sin_reparar):
            salida = asyncio.run(NODO.weekly_planner_node(estado))
        self.assertFalse(salida.get("errors"), salida.get("errors"))
        return salida, capturado["llamadas"]

    def _prompt(self, llamadas, modelo):
        return next(m[-1]["content"] for nombre, m in llamadas if nombre == modelo)

    def test_con_su_rutina_el_codificador_ya_no_escribe_la_fuerza(self):
        salida, llamadas = self._correr(rutina_de_fernando())
        codificador = self._prompt(llamadas, "FormattedWeekBlock")
        self.assertNotIn("2026-09-21", codificador)      # el día de fuerza no viaja
        self.assertNotIn("GOBLET_SQUAT", codificador)  # ni el catálogo de Garmin
        decision = self._prompt(llamadas, "TrainingDecisionNoHitl")
        self.assertIn("Su rutina de gimnasio", decision)
        self.assertIn("Upper A", decision)
        self.assertNotIn("Press de banca", decision)     # los ejercicios, no

    def test_el_dia_de_fuerza_vuelve_escrito_por_el_codigo_y_sobrevive_a_la_reparacion(self):
        salida, _ = self._correr(rutina_de_fernando())
        dias = {d.date: d for d in salida["weekly_plan"].output.weeks[0].days}
        self.assertEqual(sorted(dias), ["2026-09-21", "2026-09-22"])
        fuerza = dias["2026-09-21"]
        self.assertEqual(fuerza.sport, "strength")
        self.assertEqual(fuerza.strength_block.routine_session_id, "upper_a")
        self.assertEqual([e.name for e in fuerza.strength_block.exercises],
                         ["Press de banca", "Elevaciones laterales", "Crunch", "Dominadas"])
        # Su programa 4-4-3-4 a dosis reducida (series_con_dosis, al más cercano).
        self.assertEqual([e.sets for e in fuerza.strength_block.exercises], [3, 3, 2, 3])
        self.assertEqual(fuerza.strength_block.dose_reason, "el martes lleva series")
        # El día se titula con SU sesión, no con el "Fuerza" genérico de la decisión.
        self.assertEqual(fuerza.focus, "Upper A")

    def test_el_programa_del_coach_va_por_el_mismo_camino(self):
        """S6: lo que el coach le escribió a Jessie y ella confirmó se usa igual que la
        rutina que pegó Fernando, y por eso sus ejercicios dejan de bailar cada semana."""
        del_coach = {**rutina_de_fernando(), "source": "coach"}
        salida, llamadas = self._correr(del_coach, modo="coach")
        fuerza = {d.date: d for d in salida["weekly_plan"].output.weeks[0].days}["2026-09-21"]
        self.assertEqual(fuerza.strength_block.routine_session_id, "upper_a")
        decision = self._prompt(llamadas, "TrainingDecisionNoHitl")
        self.assertIn("se lo escribió su coach", decision)

    def test_sin_rutina_el_nodo_se_comporta_como_siempre(self):
        salida, llamadas = self._correr(None)
        self.assertNotIn("AjusteDeFuerza", [nombre for nombre, _ in llamadas])
        decision = self._prompt(llamadas, "TrainingDecisionNoHitl")
        self.assertNotIn("Su rutina de gimnasio", decision)
        codificador = self._prompt(llamadas, "FormattedWeekBlock")
        self.assertIn("2026-09-21", codificador)  # la fuerza la sigue escribiendo el codificador
        self.assertIn("GOBLET_SQUAT", codificador)


if __name__ == "__main__":
    unittest.main()
