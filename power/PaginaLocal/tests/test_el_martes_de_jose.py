"""La semana de José (atleta 2) del 2026-09-15, antes del 5K Monterrico.

Traza `u2/reasoning/2026-09-15T11-42-57`:

1. La decisión puso sus 6x400 m ("5K pace") el martes 15. Su tabla del martes:
   Fuerza (fijo) y Bici (posible), sin correr. La disponibilidad sólo se
   comprobaba DESPUÉS de codificar, donde la única salida determinista es el
   descanso: la semana perdió su única sesión de calidad y la pantalla enseñó un
   descanso titulado "5K pace", con el racional contando todavía las series.
   Ahora la decisión se comprueba antes y se le devuelve una vez; y si la red de
   después quita algo, lo dice.
2. El coach preguntó por "el martes (16/09)" y "el miércoles (17/09)": su prompt
   tenía la fecha de hoy pero no los días de la semana a planificar. Y dijo que
   había "una nota de que quieres nadar el martes": era el EJEMPLO de nuestra
   propia nota de la tabla, escrito como si fuera del atleta.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import inspect
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
    _apply_tier1_fixes,
    intenciones_fuera_de_la_tabla,
    validate_weekly_plan,
)
from services.ai.preguntas_del_coach import calendario  # noqa: E402
from web_app.server import _sport_days_note, build_availability_constraints  # noqa: E402

NODO = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")

# Su perfil real (MySQL, 2026-09-15).
PERFIL_JOSE = {
    "available_days": ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Domingo"],
    "sport_days": {
        "Correr": {"Lunes": "posible", "Miércoles": "posible", "Viernes": "posible", "Domingo": "posible"},
        "Trail": {"Domingo": "posible"}, "Bici": {"Martes": "posible", "Domingo": "posible"},
        "Nadar": {"Miércoles": "fijo", "Viernes": "fijo"}, "Fuerza": {"Lunes": "fijo", "Martes": "fijo", "Jueves": "fijo"},
    },
}
SEMANA = [{"date": f"2026-09-{15 + i}", "day_name_es": n}
          for i, n in enumerate(["Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"])]


def _disponibilidad():
    # Su check-in: el viernes 18 no puede; el sábado 19 abre correr.
    return {**build_availability_constraints(PERFIL_JOSE),
            "unavailable_dates": ["2026-09-18"], "open_dates": {"2026-09-19": ["run"]}}


def _intencion(fecha, nombre, deporte, foco, slot="unica"):
    return {"date": fecha, "day_name_es": nombre, "session_slot": slot, "is_rest": deporte == "rest",
            "sport": deporte, "focus": foco}


class LaDecisionSeCompruebaAntesDeCodificar(unittest.TestCase):
    def test_su_semana_real(self):
        dias = [
            _intencion("2026-09-15", "Martes", "run", "5K pace", "am"),
            _intencion("2026-09-15", "Martes", "strength", "fuerza + prehab", "pm"),
            _intencion("2026-09-16", "Miércoles", "swim", "técnica + ritmo"),
            _intencion("2026-09-18", "Viernes", "rest", "Descanso"),
            _intencion("2026-09-19", "Sábado", "run", "activación"),
            _intencion("2026-09-20", "Domingo", "run", "5k Monterrico"),
        ]
        fuera = intenciones_fuera_de_la_tabla(dias, _disponibilidad(), SEMANA)
        self.assertEqual([(v["date"], v["sport"], v["type"]) for v in fuera],
                         [("2026-09-15", "run", "sport_not_allowed")])
        self.assertEqual(fuera[0]["permitidos"], ["bike", "strength"])

    def test_el_dia_que_no_puede_y_el_que_abrio(self):
        fuera = intenciones_fuera_de_la_tabla(
            [_intencion("2026-09-18", "Viernes", "swim", "técnica"), _intencion("2026-09-19", "Sábado", "bike", "rodar")],
            _disponibilidad(), SEMANA)
        self.assertEqual([(v["date"], v["type"]) for v in fuera],
                         [("2026-09-18", "day_not_available"), ("2026-09-19", "sport_not_allowed")])

    def test_el_nombre_del_dia_sale_de_la_fecha_y_no_del_modelo(self):
        """Un 'Martes' escrito en el miércoles 16 se juzga contra el miércoles, que sí tiene correr."""
        self.assertEqual(intenciones_fuera_de_la_tabla(
            [_intencion("2026-09-16", "Martes", "run", "rodaje")], _disponibilidad(), SEMANA), [])

    def test_la_correccion_dice_que_y_pide_la_semana_entera(self):
        fuera = intenciones_fuera_de_la_tabla([_intencion("2026-09-15", "Martes", "run", "5K pace")],
                                             _disponibilidad(), SEMANA)
        texto = NODO.mensaje_de_correccion(fuera, '{"week_number": 1}')
        self.assertIn("Martes 2026-09-15: pusiste «5K pace» (run)", texto)
        self.assertIn("sólo permite: bike, strength", texto)
        self.assertIn("Devuelve la semana COMPLETA", texto)
        self.assertIn('{"week_number": 1}', texto)

    def test_el_nodo_la_comprueba_antes_de_guardar_la_decision(self):
        fuente = inspect.getsource(NODO.weekly_planner_node)
        comprobar = fuente.index("intenciones_fuera_de_la_tabla(week_intent.days")
        guardar = fuente.index("collected_intents.append(week_intent.model_dump())")
        self.assertLess(comprobar, guardar)


class LaRedNoQuitaEnSilencio(unittest.TestCase):
    def _plan(self):
        paso = WorkoutStep(step_order=1, step_type="interval", duration_type="distance", duration_value=400,
                           description="6x400 m")
        dia = WorkoutDay(date="2026-09-15", day_name_es="Martes", session_slot="am", is_rest=False, sport="run",
                         focus="5K pace", purpose="Último toque de ritmo específico antes del 5K.",
                         adaptation="Si estás cansado, 4x400.", total_duration_minutes=45, steps=[paso],
                         intensity_class="repeticiones")
        return WeeklyPlanPayload(athlete_name="José", start_date="2026-09-15", zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=[dia],
                                                  week_rationale="Mantengo un estímulo de 5K el martes (6x400 m).")])

    def test_el_dia_y_el_racional_dicen_lo_que_se_quito(self):
        plan, cambiado = _apply_tier1_fixes(self._plan(), [
            {"date": "2026-09-15", "session_slot": "am", "type": "sport_not_allowed"}])
        dia, semana = plan.weeks[0].days[0], plan.weeks[0]
        self.assertTrue(cambiado and dia.is_rest)
        self.assertEqual(dia.focus, "Descanso")
        self.assertIn("Aquí iba «5K pace»", dia.purpose)
        self.assertIn("tu disponibilidad no tiene ese deporte ese día", dia.purpose)
        self.assertTrue(semana.week_rationale.startswith("Mantengo un estímulo de 5K el martes"))
        self.assertIn("Nota: se quitó «5K pace» del martes 2026-09-15", semana.week_rationale)

    def test_una_segunda_pasada_no_repite_la_nota(self):
        plan, _ = _apply_tier1_fixes(self._plan(), [{"date": "2026-09-15", "session_slot": "am",
                                                     "type": "sport_not_allowed"}])
        plan, _ = _apply_tier1_fixes(plan, [{"date": "2026-09-15", "session_slot": "am", "type": "sport_not_allowed"}])
        self.assertEqual(plan.weeks[0].week_rationale.count("Nota:"), 1)

    def test_el_validador_juzga_por_la_fecha(self):
        paso = WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=1800, description="30'")
        dia = WorkoutDay(date="2026-09-16", day_name_es="Martes", is_rest=False, sport="run", focus="rodaje",
                         purpose="p", adaptation="a", total_duration_minutes=30, steps=[paso])
        plan = WeeklyPlanPayload(athlete_name="José", start_date="2026-09-15", zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=[dia])])
        tipos = [v["type"] for v in validate_weekly_plan(plan, _disponibilidad(), SEMANA)["violations"]]
        self.assertNotIn("sport_not_allowed", tipos)


class ElCoachSabeQueDiaEsCadaFecha(unittest.TestCase):
    def test_el_calendario_lleva_la_semana(self):
        texto = calendario({"current_date": {"date": "2026-09-15"}, "week_dates": SEMANA, "competitions": []})
        self.assertIn("## Hoy\nmartes 2026-09-15.", texto)
        self.assertIn("## La semana que se va a planificar\nmartes 2026-09-15, miércoles 2026-09-16, "
                      "jueves 2026-09-17, viernes 2026-09-18, sábado 2026-09-19, domingo 2026-09-20.", texto)

    def test_sin_semana_no_se_inventa(self):
        self.assertNotIn("La semana que se va", calendario({"current_date": {"date": "2026-09-15"}}))

    def test_el_ejemplo_de_la_tabla_no_parece_del_atleta(self):
        nota = _sport_days_note(PERFIL_JOSE)
        self.assertNotIn("El atleta dijo que quiere nadar el martes", nota)
        self.assertIn("hipotetico, no es de este atleta", nota)


if __name__ == "__main__":
    unittest.main()
