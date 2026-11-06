"""v7 -- los tres deportes, y el atleta que entrena de memoria.

Cinco preguntas del atleta, cinco propiedades que fijar:

1. Si sigue el plan pero NO abre el entreno programado en Garmin, ¿cuenta?
2. Si mueve una sesión un día, ¿cuenta o se le apunta dos veces mal?
3. Si borra una semana y la regenera, ¿qué pasa con el expediente?
4. La fuerza deja de ser libre: se desglosa, y NO va al reloj.
5. Natación y bici: cómo se prescribe la calidad cuando el ritmo del reloj no
   es de fiar (natación) o no significa nada (bici).

Correr con:  python -m unittest discover -s tests -v
"""

import shutil
import sys
import tempfile
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import track_record as tr  # noqa: E402
from services.ai.adherence import build_adherence_report  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    StrengthBlock,
    StrengthExercise,
    WorkoutDay,
)
from services.ai.prescription import (  # noqa: E402
    build_prescription,
    render_prescription_card,
)


def _actividad(fecha, tipo, minutos, aid):
    return SimpleNamespace(
        activity_id=aid, activity_type=tipo, start_time=f"{fecha} 07:00:00",
        activity_name="Entreno",
        summary=SimpleNamespace(duration=minutos * 60, distance=8000, average_hr=150),
    )


def _dia(fecha, deporte, minutos, foco="Sesión"):
    return SimpleNamespace(date=fecha, is_rest=False, sport=deporte, session_slot="unica",
                           focus=foco, total_duration_minutes=minutos)


def _plan(dias):
    return SimpleNamespace(start_date=dias[0].date, weeks=[SimpleNamespace(days=dias)])


class EntrenarDeMemoriaCuentaIgual(unittest.TestCase):
    """La pregunta del atleta: *"hay atletas que se pueden memorizar su plan y
    no lo subirían a su garmin"*.

    Atar la adherencia al entreno programado convertiría en incumplidor a quien
    se sabe el plan. Desde el 2026-09-14 lo hecho CON el entreno del reloj se
    empareja exacto por su `workoutId` (services/ai/adherence.py::_desde_el_reloj),
    pero eso sólo añade precisión: sin abrir el entreno, se sigue emparejando por
    fecha + deporte + duración, y este test existe para que siga siendo así.
    """

    HOY = date(2026, 6, 10)

    def test_sin_abrir_el_entreno_del_reloj_cuenta_completa(self):
        plan = _plan([_dia("2026-06-01", "run", 45), _dia("2026-06-02", "strength", 40)])
        informe = build_adherence_report(plan, [
            _actividad("2026-06-01", "running", 45, 1),
            _actividad("2026-06-02", "strength_training", 40, 2),
        ], today=self.HOY)
        self.assertEqual(informe.adherence_pct, 100.0)
        self.assertEqual(informe.missed_count, 0)
        self.assertEqual(informe.unplanned, [])

    def test_con_el_entreno_subido_pero_sin_abrirlo_tambien_cuenta(self):
        """El entreno de ese día está en el reloj y en el registro de subidas, y el atleta
        corrió igual sin abrirlo: sin `workoutId` en la actividad, cuenta por fecha y duración."""
        from services.garmin import upload_ledger

        plan = _plan([_dia("2026-06-01", "run", 45)])
        subidas = upload_ledger.anotar([], fecha="2026-06-01", franja=plan.weeks[0].days[0].session_slot,
                                       workout_id=111, nombre="Z2", deporte="run")
        informe = build_adherence_report(plan, [_actividad("2026-06-01", "running", 45, 1)],
                                         today=self.HOY, subidas=subidas)
        self.assertEqual(informe.adherence_pct, 100.0)
        self.assertFalse(informe.entries[0].desde_el_reloj)


class UnaSesionMovidaUnDiaSigueSiendoLaSesion(unittest.TestCase):
    """Sin tolerancia, correr el martes al miércoles se apuntaba DOS VECES
    mal: no realizada el martes, y entrenamiento fuera del plan el miércoles.
    Un entrenador no dice que no entrenaste; dice que lo moviste."""

    HOY = date(2026, 6, 10)

    def test_cuenta_como_hecha_y_lo_dice(self):
        plan = _plan([_dia("2026-06-01", "run", 45, "VO2max")])
        informe = build_adherence_report(
            plan, [_actividad("2026-06-02", "running", 45, 1)], today=self.HOY)
        self.assertEqual(informe.adherence_pct, 100.0)
        self.assertEqual(informe.entries[0].done_on, "2026-06-02")
        self.assertIn("la hizo el 2026-06-02", informe.entries[0].describe())

    def test_no_aparece_ademas_como_entrenamiento_fuera_del_plan(self):
        plan = _plan([_dia("2026-06-01", "run", 45)])
        informe = build_adherence_report(
            plan, [_actividad("2026-06-02", "running", 45, 1)], today=self.HOY)
        self.assertEqual(informe.unplanned, [])

    def test_el_dia_propio_gana_al_vecino(self):
        """El martes no puede robarle al miércoles la actividad que sí era
        suya. Por eso hay dos pasadas: primero todos su día, después los
        vecinos."""
        plan = _plan([_dia("2026-06-01", "run", 45, "VO2max"), _dia("2026-06-02", "run", 30, "Rodaje")])
        informe = build_adherence_report(plan, [
            _actividad("2026-06-02", "running", 30, 2),   # el rodaje, su día
            _actividad("2026-06-02", "running", 45, 1),   # el VO2max, movido
        ], today=self.HOY)
        rodaje = next(e for e in informe.entries if e.focus == "Rodaje")
        self.assertIsNone(rodaje.done_on)          # se hizo su día
        self.assertEqual(rodaje.actual_minutes, 30)  # y con SU actividad

    def test_a_dos_dias_ya_no_se_perdona(self):
        """Más allá de un día no se distingue una sesión movida de otra
        distinta, y confundirlas ensuciaría el expediente."""
        plan = _plan([_dia("2026-06-01", "run", 45)])
        informe = build_adherence_report(
            plan, [_actividad("2026-06-04", "running", 45, 1)], today=self.HOY)
        self.assertEqual(informe.entries[0].status, "missed")


class BorrarYRegenerarNoDesanotaLoEntrenado(unittest.TestCase):
    """La otra pregunta: *"si un atleta elimina una semana y crea otra, o crea
    una sobre la otra, esto como funciona"*."""

    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def _semana(self, minutos=300):
        return {"week_number": 1, "days": [{
            "date": "2026-06-01", "is_rest": False, "sport": "run", "discipline": "carrera",
            "focus": "VO2max", "intensity_class": "vo2max", "total_duration_minutes": minutos,
        }]}

    def test_lo_ejecutado_sobrevive_a_una_regeneracion(self):
        """Volver a prescribir algo no deshace lo que la persona entrenó. Sin
        esto, borrar y regenerar una semana ya cumplida la dejaría contando
        como no ejecutada, y el macrociclo leería un incumplimiento que nunca
        existió."""
        from services.ai.adherence import AdherenceReport, SessionAdherence
        informe = AdherenceReport()
        informe.entries.append(SessionAdherence(
            date="2026-06-01", session_slot="unica", sport="run", focus="VO2max",
            status="completed", planned_minutes=270, actual_minutes=270))
        adherencia = informe.to_state_dict()   # 270 min = 4.5 h
        tr.record_week(self.dir, self._semana(), adherencia)
        tr.record_week(self.dir, self._semana(minutos=200), None)   # regenerada, sin adherencia

        linea = tr.read_all(self.dir)[0]
        self.assertAlmostEqual(linea["prescribed_hours"], 200 / 60, places=2)  # lo NUEVO prescrito
        self.assertEqual(linea["executed_hours"], 4.5)         # lo VIEJO ejecutado, intacto
        self.assertEqual(linea["quality_done"], 1)

    def test_y_sigue_habiendo_una_sola_linea_por_semana(self):
        for _ in range(4):
            tr.record_week(self.dir, self._semana(), None)
        self.assertEqual(len(tr.read_all(self.dir)), 1)


class LaFuerzaSeDesglosaYNoVaAlReloj(unittest.TestCase):
    """v4 Track A la dejó LIBRE porque el modelo fallaba 12 de 12 veces
    metiendo series y repeticiones en el esquema de Garmin. Lo que cambió no
    es el modelo sino el destino: esto no viaja al reloj, así que la
    estructura deja de ser el problema que era."""

    def _dia_de_fuerza(self):
        return WorkoutDay(
            date="2026-06-01", day_name_es="Lunes", is_rest=False, sport="strength",
            focus="Fuerza", purpose="Hipertrofia y estabilidad de cadera.",
            adaptation="Si la ingle molesta, reduce el rango.",
            intensity_class="strength_freeform", discipline="fuerza",
            total_duration_minutes=35.0,
            strength_block=StrengthBlock(
                warmup="5 min de bici suave y movilidad de cadera.",
                exercises=[StrengthExercise(
                    name="Peso muerto rumano unipodal", sets=3, reps="8-10 por lado",
                    load="12 kg por mancuerna", rest_seconds=90,
                    cues="Columna neutra; la cadera va atrás, no abajo.",
                    targets="Isquiotibiales y glúteo mayor",
                    video_key="peso_muerto_rumano_unipodal",
                )],
                cooldown="Movilidad de cadera, 3 min.",
                notes="Si la ingle izquierda se activa, reduce el rango.",
            ),
        )

    def test_el_desglose_viaja_en_su_propio_campo(self):
        """Aparte de `steps`, que existe sólo para ir a Garmin. Mezclarlos
        obligaría a torcer el esquema del reloj para que aceptara series."""
        dia = self._dia_de_fuerza()
        self.assertEqual(len(dia.strength_block.exercises), 1)
        self.assertEqual(dia.steps, [])

    def test_lleva_lo_que_hace_falta_para_hacerlo(self):
        ej = self._dia_de_fuerza().strength_block.exercises[0]
        self.assertEqual(ej.sets, 3)
        self.assertEqual(ej.reps, "8-10 por lado")
        self.assertTrue(ej.load)
        self.assertTrue(ej.cues)
        self.assertEqual(ej.rest_seconds, 90)

    def test_las_repeticiones_son_TEXTO(self):
        """Un isométrico se mide en tiempo y un unilateral se cuenta por lado.
        Un entero no puede expresar ninguno de los dos."""
        ej = StrengthExercise(name="Plancha lateral", sets=3, reps="30 s por lado")
        self.assertEqual(ej.reps, "30 s por lado")

    def test_hay_sitio_para_el_video_de_la_fase_2(self):
        """Se emite desde ya: rellenarlo después contra un catálogo es fácil,
        reconstruirlo a posteriori de la prosa no lo es."""
        ej = self._dia_de_fuerza().strength_block.exercises[0]
        self.assertEqual(ej.video_key, "peso_muerto_rumano_unipodal")

    def test_un_dia_que_no_es_de_fuerza_no_lo_lleva(self):
        dia = WorkoutDay(
            date="2026-06-02", day_name_es="Martes", is_rest=False, sport="run",
            focus="VO2max", purpose="Calidad.", adaptation="Si duele, para.",
        )
        self.assertIsNone(dia.strength_block)

    def test_se_pinta_en_el_plan_y_en_la_hoja_impresa(self):
        raiz = Path(__file__).resolve().parents[1]
        app_js = (raiz / "web_app/static/app.js").read_text(encoding="utf-8")
        plantilla = (raiz / "services/ai/rendering/templates/printable_plan.html.jinja2").read_text(encoding="utf-8")
        self.assertIn("renderStrengthBlock", app_js)
        self.assertIn("day.strength_block", plantilla)

    def test_el_prompt_pide_el_desglose_y_no_lo_deja_libre(self):
        nodo = (Path(__file__).resolve().parents[1]
                / "services/ai/langgraph/nodes/weekly_planner_node.py").read_text(encoding="utf-8")
        self.assertIn("strength_block", nodo)
        self.assertIn("video_key", nodo)


class _GarminVacio:
    user_profile = None
    hr_zones = None
    race_predictions: list = []
    recent_activities: list = []


class LaNatacionTieneSuPropiaReferencia(unittest.TestCase):
    """El ritmo de carrera no se traduce al agua, la FC en el agua es otra, y
    el ritmo que reporta Garmin en piscina no es de fiar: el reloj cuenta los
    largos por acelerómetro y un viraje mal detectado produce un 15 s/100 m."""

    def test_las_bandas_salen_del_umbral_QUE_DECLARA_el_atleta(self):
        p = build_prescription(_GarminVacio(), {"swim_threshold_pace_sec_per_100m": 105})
        self.assertEqual(p.swim_threshold_pace_sec_per_100m, 105)
        # Velocidad más rápida que umbral, recuperación más lenta. En s/100 m,
        # más rápido es MENOS.
        self.assertLess(p.swim_bands["velocidad"][1], p.swim_bands["umbral"][0])
        self.assertGreater(p.swim_bands["recuperacion"][0], p.swim_bands["umbral"][1])

    def test_nunca_salen_de_los_parciales_del_reloj(self):
        fuente = (Path(__file__).resolve().parents[1] / "services/ai/prescription.py").read_text(encoding="utf-8")
        derivacion = fuente[fuente.index("swim_threshold = profile.get("):][:400]
        self.assertIn('profile.get("swim_threshold_pace_sec_per_100m")', derivacion)

    def test_sin_referencia_NO_se_inventa_un_ritmo(self):
        """Se prescribe por distancia, descanso y esfuerzo, que es como se
        escribe una serie de natación de todos modos."""
        p = build_prescription(_GarminVacio(), {})
        ficha = render_prescription_card(p)
        self.assertEqual(p.swim_bands, {})
        self.assertIn("NO inventes un ritmo", ficha)
        self.assertIn("distancia + descanso + esfuerzo", ficha)

    def test_la_ficha_avisa_de_que_los_parciales_de_garmin_mienten(self):
        """Sin este aviso, un experto podría leer un 15 s/100 m como evidencia
        de que el atleta nada rapidísimo."""
        ficha = render_prescription_card(build_prescription(_GarminVacio(), {}))
        self.assertIn("acelerómetro", ficha)
        self.assertIn("error del sensor", ficha)

    def test_con_referencia_la_ficha_las_muestra_por_100m(self):
        ficha = render_prescription_card(
            build_prescription(_GarminVacio(), {"swim_threshold_pace_sec_per_100m": 105}))
        self.assertIn("/100m", ficha)
        self.assertIn("1:45", ficha)  # el umbral declarado


class LaBiciYaEstabaResuelta(unittest.TestCase):
    """Comprobación, no cambio: la bici se prescribe por POTENCIA cuando hay
    FTP y por FC cuando no. El ritmo en bici no significa nada -- el viento,
    la pendiente y ir a rueda lo mueven mucho más que el esfuerzo."""

    def test_con_ftp_hay_bandas_de_potencia(self):
        p = build_prescription(_GarminVacio(), {"ftp_watts": 250})
        self.assertTrue(p.has_power_data)
        self.assertEqual(p.power_bands["umbral"], (228, 262))

    def test_sin_ftp_no_se_inventan_watts(self):
        p = build_prescription(_GarminVacio(), {})
        self.assertEqual(p.power_bands, {})
        self.assertFalse(p.has_power_data)


if __name__ == "__main__":
    unittest.main(verbosity=2)
