"""Lo que se rompía a los dos meses de uso, y que la beta nunca llegó a ver.

Auditoría previa al merge a `main`, 2026-09-11. Cada caso de aquí es un fallo
que sólo aparece cuando un atleta lleva semanas seguidas usando la app —
exactamente lo que la beta no midió ("37 generaciones, 0 semanas cerradas").

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date, timedelta
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.checkin import latest_checkin, save_checkin  # noqa: E402
from services.ai.langgraph.nodes.season_planner_node import _macrociclo_terminado  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from web_app.server import _last_week_planned_hours, semana_a_cerrar  # noqa: E402


def _dia(fecha, foco="Rodaje", minutos=60):
    return WorkoutDay(
        date=fecha, day_name_es="Lunes", is_rest=False, sport="run", focus=foco,
        purpose="p", adaptation="a", total_duration_minutes=minutos,
        steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=minutos * 60,
                           target_type=TargetType.NONE, description=foco)],
    )


def _plan(semanas):
    return WeeklyPlanPayload(athlete_name="T", start_date=semanas[0][0], zones_table="z",
                             weeks=[WeekBlock(week_number=i + 1, days=[_dia(f) for f in fechas])
                                    for i, fechas in enumerate(semanas)])


class SoloSeMideLaSemanaQueTERMINA(unittest.TestCase):
    """El plan guarda hasta 12 semanas y la generación semanal baja 30 días de
    actividades. Medir las 12 contra ese mes marcaba "no realizada" cada sesión
    de hace más de 30 días: desde la semana 5, un atleta que lo hizo todo salía
    al ~50 %, y el expediente cerraba cada semana guardada con los totales de
    todas juntas."""

    PLAN = _plan([["2026-07-06", "2026-07-08"], ["2026-07-13", "2026-07-15"], ["2026-07-20", "2026-07-22"]])

    def test_es_la_ultima_semana_y_solo_esa(self):
        semana = semana_a_cerrar(self.PLAN)
        self.assertEqual(len(semana.weeks), 1)
        self.assertEqual([d.date for d in semana.weeks[0].days], ["2026-07-20", "2026-07-22"])

    def test_al_rehacer_la_que_termina_es_la_anterior(self):
        """La última es la que se va a reescribir: todavía no ha ocurrido."""
        semana = semana_a_cerrar(self.PLAN, rehacer=True)
        self.assertEqual([d.date for d in semana.weeks[0].days], ["2026-07-13", "2026-07-15"])

    def test_sin_plan_no_hay_nada_que_cerrar(self):
        self.assertIsNone(semana_a_cerrar(None))
        self.assertIsNone(semana_a_cerrar(_plan([["2026-07-06"]]), rehacer=True))


class UnPlanDeHaceMESESNoEsLaReferenciaDeVolumen(unittest.TestCase):
    """Quien vuelve tras un mes parado no tiene carga crónica medida, así que
    la referencia caía en las horas PLANIFICADAS de su última semana — la de
    hace meses, +10 %. A un atleta desentrenado se le prescribía su pico."""

    def test_una_semana_reciente_si_cuenta(self):
        ayer = (date.today() - timedelta(days=1)).isoformat()
        self.assertEqual(_last_week_planned_hours(_plan([[ayer]])), 1.0)

    def test_una_de_hace_un_mes_no(self):
        viejo = (date.today() - timedelta(days=30)).isoformat()
        self.assertIsNone(_last_week_planned_hours(_plan([[viejo]])))


class UnCheckinVIEJONoEsComoLlegaHoy(unittest.TestCase):
    """Se presenta como "respondido justo antes de generar esta semana", y su
    molestia entra como queja actual. Con "Saltar", llegaba el último que
    hubiera: una rodilla declarada una vez producía contingencias de rodilla
    cada semana."""

    def setUp(self):
        import tempfile
        self.dir = Path(tempfile.mkdtemp())

    def _guardar(self, dias_atras):
        cuando = (date.today() - timedelta(days=dias_atras)).isoformat()
        save_checkin(self.dir, {"date": cuando, "sleep": 3, "fatigue": 3, "stress": 3,
                                "soreness": 3, "motivation": 5, "hooper_total": 12})

    def test_el_de_hoy_llega(self):
        self._guardar(0)
        self.assertIsNotNone(latest_checkin(self.dir, max_age_days=2))

    def test_el_de_hace_diez_dias_no(self):
        self._guardar(10)
        self.assertIsNone(latest_checkin(self.dir, max_age_days=2))
        # Sin límite sigue estando, que es lo que quiere el historial.
        self.assertIsNotNone(latest_checkin(self.dir))


class UnMacrociclamoVencidoNoSeRefina(unittest.TestCase):
    """El bloque decía siempre "no empieces de cero, conserva season_start_date
    EXACTAMENTE" -- incluso sobre un plan cuya última semana ya pasó, que el
    bloque de al lado describe como terminado. Obedecerlo deja al atleta en la
    semana 26 de un plan de 24."""

    def _fases(self, inicio, ultima_semana=4):
        return (
            '{"season_start_date": "' + inicio + '", "phases": [{"start_week": 1, "end_week": '
            + str(ultima_semana) + '}]}'
        )

    def test_uno_en_curso_sigue_vivo(self):
        hace_una_semana = (date.today() - timedelta(weeks=1)).isoformat()
        self.assertFalse(_macrociclo_terminado(self._fases(hace_una_semana)))

    def test_uno_que_se_paso_esta_terminado(self):
        hace_diez_semanas = (date.today() - timedelta(weeks=10)).isoformat()
        self.assertTrue(_macrociclo_terminado(self._fases(hace_diez_semanas)))

    def test_sin_datos_se_considera_vivo(self):
        """No saberlo no es razón para tirar el plan del atleta."""
        for basura in ("", "{}", '{"phases": []}', "no es json"):
            with self.subTest(entrada=basura):
                self.assertFalse(_macrociclo_terminado(basura))


class SubirAGarminEsDeHOYEnAdelante(unittest.TestCase):
    """"Subir a Garmin" mandaba las ~80 sesiones de las 12 semanas guardadas,
    cada una con su lectura de verificación, y las de hace más de 7 días se
    borran justo después: se subían para nada, y si Garmin empezaba a rechazar
    por ritmo, la que fallaba era la semana que importa."""

    def test_las_sesiones_pasadas_se_saltan(self):
        import asyncio
        from unittest import mock

        from services.garmin.garmin_upload_service import GarminUploadService

        ayer = (date.today() - timedelta(days=1)).isoformat()
        manana = (date.today() + timedelta(days=1)).isoformat()
        plan = _plan([[ayer, manana]])

        servicio = GarminUploadService.__new__(GarminUploadService)
        subidas = []

        async def falso_upload_day(day, zones, replaces=None):
            subidas.append(day.date)
            return mock.Mock()

        servicio.upload_day = falso_upload_day
        asyncio.run(servicio.upload_plan(plan, mock.Mock()))
        self.assertEqual(subidas, [manana])


class LaCalidadVaConLasPIERNASFRESCAS(unittest.TestCase):
    """El domingo de la mini-semana real del atleta 2 (2026-09-10): bici Z2 de
    75' por la mañana y 6x2' a VO2max por la tarde. El razonamiento era
    coherente, pero la sesión clave de la semana se corría sobre piernas ya
    cargadas -- un brick que nadie pidió, nueve días antes de su 5K. No había
    ninguna regla de ORDEN dentro del día."""

    def test_el_prompt_pone_la_calidad_primero(self):
        from services.ai.langgraph.nodes.weekly_planner_node import TRAINING_DECISION_USER_PROMPT
        self.assertIn("the quality one goes FIRST", TRAINING_DECISION_USER_PROMPT)
        self.assertIn("brick nobody asked for", TRAINING_DECISION_USER_PROMPT)

    def test_y_que_un_bloque_no_necesita_calidad_por_ser_un_bloque(self):
        """El atleta lo dijo asi: "no me tiene mucha logica cargar tanto la
        semana". Nada exigia calidad; tampoco decia que se pudiera no ponerla."""
        from services.ai.langgraph.nodes.weekly_planner_node import TRAINING_DECISION_USER_PROMPT
        self.assertIn("No session type is a quota either", TRAINING_DECISION_USER_PROMPT)
        self.assertIn("aerobic block is a correct answer",
                      " ".join(TRAINING_DECISION_USER_PROMPT.split()))


if __name__ == "__main__":
    unittest.main()
