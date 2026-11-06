"""Lo que pidió el atleta Fernando el 2026-09-14, con sus palabras.

1. "Te da la opción de semana de viaje pero no da la opción de decir qué días no
   estás disponible." -- Días sin entrenar ESTA semana, en el check-in
   (Database/022), que el validador trata como descanso.
2. "Hoy no voy a poder entrenar, entonces lo tomo descanso y muevo lo de hoy al
   viernes, que es mi día de descanso." -- Una sesión movida dentro de su semana
   cuenta como hecha, no como "no realizada" más un entreno fuera del plan.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.adherence import build_adherence_report  # noqa: E402
from services.ai.checkin import (  # noqa: E402
    dias_sin_entrenar,
    normalize_checkin,
    render_checkin_block,
    render_dias_sin_entrenar,
)
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import validate_weekly_plan  # noqa: E402

NOMBRES = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]
FECHAS = [f"2026-09-{14 + i}" for i in range(7)]   # lunes 14 .. domingo 20


class LosDiasQueNoPuedeSeLeenBien(unittest.TestCase):
    def test_de_mysql_llegan_como_texto_json(self):
        self.assertEqual(dias_sin_entrenar({"unavailable_dates": '["2026-09-18", "2026-09-14"]'}),
                         ["2026-09-14", "2026-09-18"])

    def test_lo_que_no_es_una_fecha_se_descarta(self):
        self.assertEqual(dias_sin_entrenar({"unavailable_dates": ["2026-09-14", "lunes", "2026-02-30", None]}),
                         ["2026-09-14"])

    def test_sin_dias_no_hay_nada(self):
        self.assertEqual(dias_sin_entrenar({}), [])
        self.assertEqual(render_dias_sin_entrenar([]), "")

    def test_la_regla_nombra_el_dia_y_la_fecha(self):
        regla = render_dias_sin_entrenar(["2026-09-14", "2026-09-18"])
        self.assertIn("lunes 2026-09-14", regla)
        self.assertIn("viernes 2026-09-18", regla)
        self.assertIn("no pongas ninguna sesión", regla)

    def test_un_checkin_solo_con_dias_ya_dice_algo(self):
        checkin = normalize_checkin({"unavailable_dates": ["2026-09-14"]}, today=date(2026, 9, 14))
        bloque = render_checkin_block(checkin, [])
        self.assertIn("Días que NO puede entrenar esta semana", bloque)


class ElValidadorLosTrataComoDescanso(unittest.TestCase):
    def _semana(self, deporte_por_dia):
        paso = WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                           duration_value=1800, description="30'")
        dias = [WorkoutDay(date=f, day_name_es=n, is_rest=deporte_por_dia.get(n) is None,
                           sport=deporte_por_dia.get(n) or "rest", focus="x", purpose="x", adaptation="x",
                           total_duration_minutes=30.0 if deporte_por_dia.get(n) else 0.0,
                           steps=[paso] if deporte_por_dia.get(n) else [])
                for f, n in zip(FECHAS, NOMBRES, strict=True)]
        return WeeklyPlanPayload(athlete_name="Fernando", start_date=FECHAS[0], zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=dias)])

    def _tipos(self, plan, sin_entrenar):
        limites = {
            "days": {n: {"available": True, "hours": None, "sports": None, "blocks": []} for n in NOMBRES},
            "sport_days_fijos": {"Lunes": ["swim"]},
            "unavailable_dates": sin_entrenar,
        }
        semana = [{"date": f, "day_name_es": n} for f, n in zip(FECHAS, NOMBRES, strict=True)]
        return [v["type"] for v in validate_weekly_plan(plan, limites, semana)["violations"]]

    def test_una_sesion_el_dia_que_no_puede_es_una_violacion(self):
        self.assertIn("day_not_available", self._tipos(self._semana({"Lunes": "swim"}), ["2026-09-14"]))

    def test_su_fijo_de_ese_dia_no_se_exige(self):
        """El lunes tiene natación FIJA en su tabla, y este lunes no puede."""
        self.assertNotIn("sport_requested_missing", self._tipos(self._semana({"Martes": "run"}), ["2026-09-14"]))

    def test_sin_marcar_nada_el_fijo_se_sigue_exigiendo(self):
        self.assertIn("sport_requested_missing", self._tipos(self._semana({"Martes": "run"}), []))


def _actividad(fecha, tipo, minutos, aid):
    return SimpleNamespace(
        activity_id=aid, activity_type=tipo, start_time=f"{fecha} 07:00:00", activity_name="Entreno",
        summary=SimpleNamespace(duration=minutos * 60, distance=1500, average_hr=130),
    )


def _dia(fecha, deporte, minutos, foco="Sesión"):
    return SimpleNamespace(date=fecha, is_rest=False, sport=deporte, session_slot="unica",
                           focus=foco, total_duration_minutes=minutos)


def _descanso(fecha):
    return SimpleNamespace(date=fecha, is_rest=True, sport="rest", session_slot="unica",
                           focus="Descanso", total_duration_minutes=0)


class UnaSesionMovidaDentroDeSuSemana(unittest.TestCase):
    """Semana lunes 1 - domingo 7 de junio; el viernes 5 era su descanso."""

    HOY = date(2026, 6, 10)

    def _plan(self):
        dias = [_dia("2026-06-01", "swim", 30, "Técnica"), _dia("2026-06-03", "run", 40, "Rodaje"),
                _descanso("2026-06-05"), _dia("2026-06-07", "run", 70, "Tirada")]
        return SimpleNamespace(start_date="2026-06-01", weeks=[SimpleNamespace(days=dias)])

    def test_el_lunes_hecho_el_viernes_cuenta_y_lo_dice(self):
        informe = build_adherence_report(self._plan(), [
            _actividad("2026-06-03", "running", 40, 1),
            _actividad("2026-06-05", "lap_swimming", 30, 2),
            _actividad("2026-06-07", "running", 70, 3),
        ], today=self.HOY)
        natacion = next(e for e in informe.entries if e.sport == "swim")
        self.assertEqual(natacion.status, "completed")
        self.assertEqual(natacion.done_on, "2026-06-05")
        self.assertEqual(informe.unplanned, [])

    def test_una_actividad_de_otra_duracion_no_se_toma_por_la_sesion(self):
        """75' nadando el viernes no es la técnica de 30' del lunes movida."""
        informe = build_adherence_report(self._plan(), [
            _actividad("2026-06-05", "lap_swimming", 75, 2),
        ], today=self.HOY)
        natacion = next(e for e in informe.entries if e.sport == "swim")
        self.assertEqual(natacion.status, "missed")
        self.assertEqual(len(informe.unplanned), 1)

    def test_fuera_de_su_semana_no_cuenta(self):
        informe = build_adherence_report(self._plan(), [
            _actividad("2026-06-09", "lap_swimming", 30, 2),
        ], today=self.HOY)
        natacion = next(e for e in informe.entries if e.sport == "swim")
        self.assertEqual(natacion.status, "missed")

    def test_no_le_roba_a_una_sesion_su_propia_actividad(self):
        """El rodaje del miércoles se hizo su día: esa actividad es suya."""
        informe = build_adherence_report(self._plan(), [
            _actividad("2026-06-03", "running", 40, 1),
        ], today=self.HOY)
        rodaje = next(e for e in informe.entries if e.focus == "Rodaje")
        tirada = next(e for e in informe.entries if e.focus == "Tirada")
        self.assertIsNone(rodaje.done_on)
        self.assertEqual(tirada.status, "missed")


if __name__ == "__main__":
    unittest.main()
