"""La fuerza del atleta 2: sin avisos falsos de pierna y con el nombre de Garmin de SU ejercicio.

Auditoría del 2026-09-18 (macro + semana del atleta 2, su rutina Upper/Lower A/B
confirmada antes de que existiera `emphasis`):

- Las cuatro sesiones tenían `emphasis` vacío, el paso de fuerza las trataba como
  cuerpo completo y el plan decía "«Upper B» carga piernas y cae el 2026-09-24, justo
  el día antes de tu CSS" (una natación). En la semana del 2026-09-28 dijo lo mismo de
  «Upper A» antes de un tempo.
- "Extensión de cuádriceps" (Lower A) y "Extension de cuadricep" (Lower B) iban al reloj
  como HAMSTRING_CURL de la familia MOVE -- un estiramiento --, y "Press de banca" iba sin
  nombre aunque el catálogo lo reconoce.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import exercise_catalog as cat  # noqa: E402
from services.ai.langgraph.nodes import paso_de_fuerza as pf  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import DayIntent, WeekIntentContent  # noqa: E402
from services.ai.perfil_de_fuerza import leer_rutina_de_fuerza  # noqa: E402
from services.garmin.garmin_vocabulary import VERIFIED_EXERCISES  # noqa: E402


def _s(id_, nombre, ejercicios):
    return {"id": id_, "name": nombre, "emphasis": None, "groups": [
        {"id": f"{id_}_g1", "rest_between_rounds_s": 120, "exercises": [
            {"id": f"{id_}_e{i}", "name": n, "sets": 3, "reps": "10", "load": "", "technique": "",
             "garmin_category": c, "garmin_exercise": g}
            for i, (n, c, g) in enumerate(ejercicios)]}]}


# Su rutina guardada en MySQL (athlete_profiles.strength_routine, user_id 2), tal cual.
UPPER_A = _s("upper_a", "Upper A", [
    ("Press de banca", None, None), ("Elevaciones laterales", "LATERAL_RAISE", "DUMBBELL_LATERAL_RAISE"),
    ("Crunch", "CRUNCH", "CRUNCH"), ("Dominadas", "PULL_UP", "PULL_UP"),
    ("Press inclinado mancuernas", "BENCH_PRESS", "INCLINE_DUMBBELL_BENCH_PRESS"),
    ("Curl con mancuernas", "CURL", "DUMBBELL_BICEPS_CURL"), ("Aperturas de pecho", "FLYE", "DUMBBELL_FLYE"),
    ("Remo con polea", "ROW", "SEATED_CABLE_ROW"), ("Extensión tríceps", "TRICEPS_EXTENSION", "TRICEPS_PRESSDOWN")])
LOWER_A = _s("lower_a", "Lower A", [
    ("Abductores", "HIP_STABILITY", "STANDING_HIP_ABDUCTION"), ("Aductores", "HIP_STABILITY", "STANDING_ADDUCTION"),
    ("Curl femoral", "LEG_CURL", "LEG_CURL"), ("Extensión de cuádriceps", "MOVE", "HAMSTRING_CURL"),
    ("Sentadilla", "SQUAT", "SQUAT"), ("Hack squat", "SQUAT", "BARBELL_HACK_SQUAT"), ("Crunch", "CRUNCH", "CRUNCH")])
UPPER_B = _s("upper_b", "Upper B", [
    ("Press militar", "SHOULDER_PRESS", "MILITARY_PRESS"), ("Predicador", "CURL", "EZ_BAR_PREACHER_CURL"),
    ("Remo T", "ROW", "T_BAR_ROW"), ("Jalón al pecho", "PULL_UP", "LAT_PULLDOWN")])
LOWER_B = _s("lower_b", "Lower B", [
    ("Peso muerto rumano", "DEADLIFT", "ROMANIAN_DEADLIFT"), ("Prensa", "SQUAT", "LEG_PRESS"),
    ("Extension de cuadricep", "MOVE", "HAMSTRING_CURL"), ("Gemelos", None, None)])


def _rutina(*sesiones):
    return leer_rutina_de_fuerza({"strength_routine": {
        "source": "atleta", "original_text": "x", "confirmed_at": "2026-09-17T18:00:00Z",
        "sessions": list(sesiones)}})


def _dia(fecha, deporte, clase, foco):
    return DayIntent(date=fecha, day_name_es="Lunes", is_rest=False, sport=deporte, focus=foco,
                     purpose="p", adaptation="a", total_duration_minutes=45, intensity_class=clase,
                     discipline={"strength": "fuerza", "run": "carrera", "bike": "bici", "swim": "natacion"}[deporte],
                     session_sketch="")


class ElEnfasisSeDeduce(unittest.TestCase):
    def test_del_nombre_de_la_sesion(self):
        self.assertEqual(pf.enfasis_de(UPPER_A), pf.TORSO)
        self.assertEqual(pf.enfasis_de(UPPER_B), pf.TORSO)
        self.assertEqual(pf.enfasis_de(LOWER_A), pf.PIERNA)
        self.assertEqual(pf.enfasis_de({"name": "Full body", "groups": []}), pf.COMPLETO)

    def test_de_los_ejercicios_si_el_nombre_no_lo_dice(self):
        sin_nombre = {**LOWER_B, "name": "Día 2"}
        self.assertEqual(pf.enfasis_de(sin_nombre), pf.PIERNA)
        self.assertEqual(pf.enfasis_de({**UPPER_A, "name": "Día 1"}), pf.TORSO)

    def test_lo_que_dice_el_programa_manda(self):
        self.assertEqual(pf.enfasis_de({**UPPER_A, "emphasis": "completo"}), pf.COMPLETO)


class SinAvisosFalsos(unittest.TestCase):
    def test_el_caso_real_upper_b_antes_de_la_natacion(self):
        """Semana del 2026-09-21: Upper B el 24, CSS de natación el 25."""
        semana = WeekIntentContent(week_number=1, week_rationale="r", days=[
            _dia("2026-09-24", "strength", "strength_freeform", "Fuerza"),
            _dia("2026-09-25", "swim", "tempo_threshold", "CSS")])
        rutina = _rutina(UPPER_B)
        _, avisos = pf.colocar_las_sesiones(rutina, ["2026-09-24"], semana)
        self.assertEqual(avisos, [])

    def test_la_natacion_no_es_una_sesion_clave_para_las_piernas(self):
        semana = WeekIntentContent(week_number=1, week_rationale="r", days=[
            _dia("2026-09-25", "swim", "tempo_threshold", "CSS"),
            _dia("2026-09-26", "run", "long_run", "Tirada larga"),
            _dia("2026-09-27", "bike", "vo2max", "VO2 en bici")])
        self.assertEqual(sorted(pf.dias_clave(semana)), ["2026-09-26", "2026-09-27"])

    def test_el_torso_pesado_se_aleja_de_la_calidad_en_el_agua(self):
        """Lo simétrico: con Upper y Lower para el día antes de su CSS, va Lower (sin aviso)."""
        semana = WeekIntentContent(week_number=1, week_rationale="r", days=[
            _dia("2026-09-24", "strength", "strength_freeform", "Fuerza"),
            _dia("2026-09-25", "swim", "tempo_threshold", "CSS")])
        elegidas, avisos = pf.colocar_las_sesiones(_rutina(UPPER_B, LOWER_B), ["2026-09-24"], semana)
        self.assertEqual(elegidas, ["lower_b"])
        self.assertEqual(avisos, [])

    def test_la_pierna_antes_de_correr_si_avisa(self):
        """El aviso que sí vale, en una línea."""
        semana = WeekIntentContent(week_number=1, week_rationale="r", days=[
            _dia("2026-09-28", "strength", "strength_freeform", "Fuerza"),
            _dia("2026-09-29", "run", "tempo_threshold", "Tempo")])
        _, avisos = pf.colocar_las_sesiones(_rutina(LOWER_B), ["2026-09-28"], semana)
        self.assertEqual(len(avisos), 1)
        self.assertIn("Carga piernas el día antes de tu Tempo", avisos[0]["text"])


class ElDiaQueDiceSuNombre(unittest.TestCase):
    """Lectura real del 2026-09-23: "LUNES 💪 pecho y triceps", "MIERCOLES pierna". Si el
    atleta nombra sus sesiones por día, ese día es una petición suya."""

    def test_cada_una_en_su_dia(self):
        lunes = {**UPPER_A, "id": "lunes", "name": "LUNES 💪 pecho y triceps"}
        miercoles = {**LOWER_B, "id": "miercoles", "name": "MIERCOLES pierna"}
        semana = WeekIntentContent(week_number=1, week_rationale="r", days=[
            _dia("2026-09-28", "strength", "strength_freeform", "Fuerza"),
            _dia("2026-09-30", "strength", "strength_freeform", "Fuerza")])
        elegidas, _ = pf.colocar_las_sesiones(_rutina(miercoles, lunes), ["2026-09-28", "2026-09-30"], semana)
        self.assertEqual(elegidas, ["lunes", "miercoles"])

    def test_sin_dia_en_el_nombre_no_cambia_nada(self):
        self.assertIsNone(pf.dia_del_nombre({"name": "Upper A"}))
        self.assertIsNone(pf.dia_del_nombre({"name": "Lunes y jueves"}))  # dos días: no se afirma
        self.assertEqual(pf.dia_del_nombre({"name": "Miércoles pierna"}), 2)


class DosSesionesElMismoDia(unittest.TestCase):
    """Hasta el 2026-09-23 todo se emparejaba por fecha: la segunda sesión de fuerza de un
    día pisaba a la primera (su bloque, su dosis y sus cambios)."""

    def test_cada_una_con_su_sesion_y_su_dosis(self):
        dias = [
            {"date": "2026-09-28", "day_name_es": "Lunes", "session_slot": "am", "minutes": 60, "focus": "F",
             "purpose": "p", "adaptation": "a", "intensity_class": "strength_freeform", "discipline": "fuerza",
             "session_id": "upper_a"},
            {"date": "2026-09-28", "day_name_es": "Lunes", "session_slot": "pm", "minutes": 60, "focus": "F",
             "purpose": "p", "adaptation": "a", "intensity_class": "strength_freeform", "discipline": "fuerza",
             "session_id": "lower_a"},
        ]
        ajustes = [pf.FuerzaDelDia(date="2026-09-28", session_slot="pm", session_id="lower_a", dose="minima"),
                   pf.FuerzaDelDia(date="2026-09-28", session_slot="am", session_id="upper_a")]
        construidos = pf.dias_construidos(_rutina(UPPER_A, LOWER_A), dias, ajustes)
        self.assertEqual([(d.session_slot, d.strength_block.routine_session_id, d.strength_block.dose)
                          for d in construidos], [("am", "upper_a", ""), ("pm", "lower_a", "minima")])

    def test_el_saneado_no_confunde_las_franjas(self):
        dias = [{"date": "2026-09-28", "session_slot": "am", "session_id": "upper_a"},
                {"date": "2026-09-28", "session_slot": "pm", "session_id": "lower_a"}]
        respuesta = pf.AjusteDeFuerza(days=[
            pf.FuerzaDelDia(date="2026-09-28", session_slot="pm", session_id="lower_a", dose="reducida"),
            pf.FuerzaDelDia(date="2026-09-28", session_slot="am", session_id="upper_a")])
        salida = pf._saneado(respuesta, dias, _rutina(UPPER_A, LOWER_A))
        self.assertEqual([(a.session_slot, a.session_id, a.dose) for a in salida],
                         [("am", "upper_a", "normal"), ("pm", "lower_a", "reducida")])


class LoQueDiceLaCiencia(unittest.TestCase):
    """Decisiones del responsable del 2026-09-23 sobre la fuerza (B, C, F y G)."""

    def test_en_mantenimiento_se_quitan_series_no_carga(self):
        self.assertIn("Mantener no es aflojar", pf.AJUSTE_SYSTEM_PROMPT)
        self.assertIn("se quitan SERIES, no carga", pf.AJUSTE_SYSTEM_PROMPT)

    def test_el_calentamiento_es_una_linea_con_su_primer_ejercicio_fuerte(self):
        bloque = pf.bloque_de_fuerza(LOWER_B, pf.FuerzaDelDia(date="2026-09-28", session_id="lower_b"))
        self.assertIn("Antes de Peso muerto rumano: 2 series de aproximación", bloque.warmup)
        self.assertNotIn("movilidad", bloque.warmup)  # muchos programas ya la traen
        solo_core = _s("core", "Core", [("Crunch", "CRUNCH", "CRUNCH")])
        self.assertEqual(pf.bloque_de_fuerza(solo_core, pf.FuerzaDelDia(date="2026-09-28", session_id="core")).warmup,
                         "")

    def test_la_pliometria_la_decide_la_ia_para_quien_corre_y_el_prehab_solo_con_molestia(self):
        self.assertTrue(pf.puede_anadir("", "", corre=True))
        self.assertFalse(pf.puede_anadir("", "", corre=False))
        self.assertIn("Pliometría", pf.AJUSTE_SYSTEM_PROMPT)
        self.assertIn("Un atleta sano no recibe prehab que no pidió", pf.AJUSTE_SYSTEM_PROMPT)

    def test_la_pierna_el_mismo_dia_que_la_clave_se_avisa_y_no_se_mueve(self):
        semana = WeekIntentContent(week_number=1, week_rationale="r", days=[
            _dia("2026-09-30", "strength", "strength_freeform", "Fuerza"),
            _dia("2026-09-30", "run", "tempo_threshold", "Umbral")])
        elegidas, avisos = pf.colocar_las_sesiones(_rutina(LOWER_B), ["2026-09-30"], semana)
        self.assertEqual(elegidas, ["lower_b"])
        self.assertIn("Mismo día que tu Umbral: haz primero esa", avisos[0]["text"])


class ElNombreDeGarminEsElDeSuEjercicio(unittest.TestCase):
    def test_la_extension_de_cuadriceps_no_es_un_estiramiento(self):
        for nombre in ("Extensión de cuádriceps", "Extension de cuadricep"):
            with self.subTest(nombre=nombre):
                self.assertEqual(cat.garmin_de(nombre, "MOVE", "HAMSTRING_CURL"), (None, None))

    def test_el_press_de_banca_se_reconoce(self):
        self.assertEqual(cat.garmin_de("Press de banca"), ("BENCH_PRESS", "BARBELL_BENCH_PRESS"))

    def test_una_eleccion_verificada_de_la_misma_familia_se_respeta(self):
        self.assertEqual(cat.garmin_de("Press militar", "SHOULDER_PRESS", "MILITARY_PRESS"),
                         ("SHOULDER_PRESS", "MILITARY_PRESS"))

    def test_lo_que_el_catalogo_no_conoce_sigue_siendo_del_modelo_si_esta_verificado(self):
        self.assertEqual(cat.garmin_de("Remo con polea", "ROW", "SEATED_CABLE_ROW"), ("ROW", "SEATED_CABLE_ROW"))
        self.assertEqual(cat.garmin_de("Remo con polea", "ROW", "INVENTADO"), ("ROW", None))

    def test_el_bloque_de_la_semana_lo_arregla_sin_tocar_su_rutina_guardada(self):
        """Su rutina guardada sigue diciendo HAMSTRING_CURL; lo que llega al reloj, no."""
        bloque = pf.bloque_de_fuerza(LOWER_A, pf.FuerzaDelDia(date="2026-09-28", session_id="lower_a"))
        por_nombre = {e.name: (e.garmin_category, e.garmin_exercise) for e in bloque.exercises}
        self.assertEqual(por_nombre["Extensión de cuádriceps"], ("", ""))
        self.assertEqual(por_nombre["Curl femoral"], ("LEG_CURL", "LEG_CURL"))
        bloque = pf.bloque_de_fuerza(UPPER_A, pf.FuerzaDelDia(date="2026-09-28", session_id="upper_a"))
        self.assertEqual(bloque.exercises[0].garmin_exercise, "BARBELL_BENCH_PRESS")
        self.assertEqual(bloque.session_name, "Upper A")

    def test_el_coach_del_chat_ve_el_porque(self):
        """Auditoría del 2026-09-18: sin los motivos, el chat inventó por qué «Upper B» cargaba piernas."""
        from services.ai.coach_chat import render_week_for_coach

        bloque = pf.bloque_de_fuerza(UPPER_A, pf.FuerzaDelDia(
            date="2026-09-28", session_id="upper_a", dose="reducida", dose_reason="vienes del 5K",
            changes=[pf.CambioDeEjercicio(exercise_id="upper_a_e0", replace_with="Flexión",
                                          reason="el banco está ocupado"),
                     pf.CambioDeEjercicio(exercise_id="upper_a_e2", skip=True, reason="hoy sin abdominal")]))
        texto = render_week_for_coach({"week_number": 1, "days": [{
            "date": "2026-09-28", "day_name_es": "Lunes", "sport": "strength", "focus": "Upper A",
            "is_rest": False, "strength_block": bloque.model_dump()}]})
        self.assertIn("Dosis reducida esta semana: vienes del 5K", texto)
        self.assertIn("en vez de Press de banca — el banco está ocupado", texto)
        self.assertIn("Sin Crunch esta semana: hoy sin abdominal", texto)

    def test_una_semana_recortada_no_ancla_el_tamano_de_la_siguiente(self):
        """Atleta 2, 2026-09-23: la decisión copió 36', 21' y 42' de una semana a dosis mínima,
        también al Rehacer (tercera y cuarta generación del día, mismos minutos)."""
        import importlib

        from services.ai.langgraph.schemas.plan_outputs import WeekBlock, WeeklyPlanPayload
        nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")
        dia = pf.dias_construidos(_rutina(UPPER_A), [{
            "date": "2026-09-28", "day_name_es": "Lunes", "session_slot": "unica", "minutes": 21,
            "focus": "Fuerza", "purpose": "p", "adaptation": "a", "intensity_class": "strength_freeform",
            "discipline": "fuerza", "session_id": "upper_a"}],
            [pf.FuerzaDelDia(date="2026-09-28", session_id="upper_a", dose="minima")])[0]
        plan = WeeklyPlanPayload(athlete_name="J", start_date="2026-09-28", zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=[dia], week_rationale="r")])
        texto = nodo._format_previous_plan(plan, "2026-09-30")
        self.assertIn("(de su programa: el tiempo lo fijan su sesión y su dosis)", texto)
        self.assertNotIn("21'", texto)  # nada que copiar, tampoco al rehacer la semana

    def test_el_chat_ve_su_programa_y_lo_que_levanto(self):
        """Auditoría del 2026-09-18: el chat le dijo que su programa estaba "en el cuaderno"
        e inventó por qué Upper B cargaba piernas. No lo tenía delante."""
        from services.ai.coach_chat import render_fuerza_para_el_coach

        perfil = {"strength_routine": {"source": "atleta", "original_text": "x",
                                       "confirmed_at": "2026-09-17T18:00:00Z", "sessions": [UPPER_B]}}
        texto = render_fuerza_para_el_coach(perfil, [{"fecha": "2026-09-22", "routine_session_id": "upper_b",
                                                       "exercises": [{"name": "Press militar", "sets": 3,
                                                                      "reps": "10", "weight_kg": 40}]}])
        self.assertIn("- Upper B: A: Press militar 3x10 + Predicador 3x10", texto)
        self.assertIn("Press militar 3x10 40 kg", texto)
        self.assertEqual(render_fuerza_para_el_coach({}, []), "")

    def test_lectura_real_de_una_rutina_de_whatsapp(self):
        """Lectura real del 2026-09-23 (flash-lite, ruta /profile/strength-routine)."""
        self.assertEqual(cat.garmin_de("Flexiones", "PUSH_UP", "PILATES_PUSHUP"), ("PUSH_UP", None))
        self.assertEqual(cat.garmin_de("Zancadas atrás", "LUNGE", "ALTERNATING_DUMBBELL_LUNGE"), ("LUNGE", None))
        self.assertEqual(cat.garmin_de("curl biceps barra", "CURL", "DUMBBELL_BICEPS_CURL"), ("CURL", None))
        # Segunda lectura de la misma rutina: otro ejercicio de la misma familia.
        self.assertEqual(cat.garmin_de("curl biceps barra", "CURL", "EZ_BAR_PREACHER_CURL"), ("CURL", None))
        self.assertEqual(cat.garmin_de("martillo", "CURL", "DUMBBELL_BICEPS_CURL"), ("CURL", None))
        # Y los que sí son el suyo se conservan (la rutina de José).
        self.assertEqual(cat.garmin_de("Predicador", "CURL", "EZ_BAR_PREACHER_CURL"), ("CURL", "EZ_BAR_PREACHER_CURL"))
        self.assertEqual(cat.garmin_de("Press inclinado", "BENCH_PRESS", "INCLINE_DUMBBELL_BENCH_PRESS"),
                         ("BENCH_PRESS", "INCLINE_DUMBBELL_BENCH_PRESS"))

    def test_45s_son_segundos_y_no_una_duda(self):
        from services.ai.rutina_de_fuerza import dudas_de_notacion
        self.assertEqual(dudas_de_notacion({5: "- Plancha 3x45s", 6: "- Remo 3x20m"}, [], ""), [])
        self.assertTrue(dudas_de_notacion({7: "Sentadilla 4×15R"}, [], ""))  # la R de Fernando sí

    def test_el_mapa_solo_tiene_parejas_verificadas_de_ejercicios_que_existen(self):
        for slug, (familia, nombre) in cat.GARMIN.items():
            with self.subTest(slug=slug):
                self.assertIn(slug, cat.EJERCICIOS)
                self.assertIn(nombre, VERIFIED_EXERCISES.get(familia, frozenset()))


if __name__ == "__main__":
    unittest.main()
