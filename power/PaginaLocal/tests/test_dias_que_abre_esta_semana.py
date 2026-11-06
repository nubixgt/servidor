"""Lo que salió de la generación de la atleta 3 (Jessie) del 2026-09-15.

1. "Mi activación para una carrera, la suelo hacer el día sábado antes de la
   carrera, ligera." Jessie no entrena los sábados; su tabla lo tiene de
   descanso y su perfil dice que prefiere descansar ese día. La semana del 5K
   Monterrico le puso "Descanso completo como prefieres" ese sábado y pasó la
   activación a una fuerza el jueves. El check-in dejaba decir qué días NO
   podía (Database/022) y ninguno qué día SÍ: ahora abre un día con los deportes
   que podrá hacer (Database/024).
2. Su macrociclo puso la fase específica del trail B (12 km, +600 m, sábado
   2026-11-21) empezando en la semana 10, que es la de la carrera. El modelo
   sólo recibía fechas y contaba las semanas él.
3. El informe le dijo "tu carga crónica (406) está dentro del rango óptimo de
   Garmin (325–609)". Ese rango es el de la carga AGUDA, 0,8-1,5 veces la
   crónica; el bloque lo llamaba "rango crónico".

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.checkin import (  # noqa: E402
    dias_abiertos,
    has_content,
    normalize_checkin,
    render_checkin_block,
    render_dias_abiertos,
)
from services.ai.langgraph.nodes.season_planner_node import con_semana_del_macrociclo  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import validate_weekly_plan  # noqa: E402
from services.ai.load_state import LoadState, render_load_state  # noqa: E402
from web_app.server import build_custom_planning_context  # noqa: E402

# Su semana real: martes 15 a domingo 20, con el 5K el domingo.
NOMBRES = ["Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]
FECHAS = [f"2026-09-{d}" for d in range(15, 21)]
SABADO = "2026-09-19"

# La rejilla real de la atleta 3 (MySQL, 2026-09-15), con su día de descanso preferido.
PERFIL_JESSIE = {
    "name": "Jessie Navas", "sports_preference": "run", "target_distance": "10K",
    "sport_days": {
        "Correr": {"Lunes": "posible", "Miércoles": "posible", "Viernes": "posible", "Domingo": "posible"},
        "Trail": {"Domingo": "posible"}, "Bici": {"Martes": "posible", "Domingo": "posible"},
        "Fuerza": {"Lunes": "fijo", "Martes": "fijo", "Jueves": "fijo"},
    },
    "preferred_rest_day": "Sábado",
}


def _criticas(texto):
    return texto[texto.index("CRITICAL SCHEDULING RULES"):]


class ElCheckinGuardaLosDiasQueAbre(unittest.TestCase):
    def test_de_mysql_llegan_como_texto_json(self):
        self.assertEqual(dias_abiertos({"open_dates": '{"2026-09-19": ["Correr"]}'}), {SABADO: ["Correr"]})

    def test_fechas_y_deportes_que_no_se_entienden_se_descartan(self):
        self.assertEqual(dias_abiertos({"open_dates": {
            SABADO: ["Fuerza", "Correr", "Pilates", "Correr"],
            "sábado": ["Correr"],
            "2026-02-30": ["Correr"],
            "2026-09-18": [],
        }}), {SABADO: ["Correr", "Fuerza"]})

    def test_si_tambien_dijo_que_no_puede_gana_el_no(self):
        self.assertEqual(dias_abiertos({"open_dates": {SABADO: ["Correr"]}, "unavailable_dates": [SABADO]}), {})

    def test_un_checkin_solo_con_un_dia_abierto_ya_dice_algo(self):
        checkin = normalize_checkin({"open_dates": {SABADO: ["Correr"]}}, today=date(2026, 9, 15))
        self.assertTrue(has_content(checkin))
        bloque = render_checkin_block(checkin, [])
        self.assertIn("**Días que abre esta semana**", bloque)
        self.assertIn("sábado 2026-09-19 (Correr)", bloque)

    def test_la_nota_de_la_semana_ya_no_manda_a_descansar_un_dia_abierto(self):
        checkin = normalize_checkin({"week_note": "Mi activación para una carrera, la suelo hacer el día sábado",
                                     "open_dates": {SABADO: ["Correr"]}}, today=date(2026, 9, 15))
        bloque = render_checkin_block(checkin, [])
        self.assertIn("y no lo abrió esta semana, no la programes como sesión", bloque)
        self.assertIn("ese día lo puede abrir en su check-in", bloque)

    def test_la_regla_nombra_el_dia_y_los_deportes(self):
        self.assertEqual(render_dias_abiertos({}), "")
        regla = render_dias_abiertos({SABADO: ["Correr"]})
        self.assertIn("sábado 2026-09-19 (Correr)", regla)
        self.assertIn("POSIBLE con esos deportes y ningún otro", regla)


class LasReglasInviolablesDejanUsarlo(unittest.TestCase):
    """Lo marcado NEVER VIOLATE gana a cualquier bloque que venga detrás
    (tests/test_prompt_coherente.py), así que la excepción va dentro."""

    def setUp(self):
        self.siempre = build_custom_planning_context(PERFIL_JESSIE)
        self.semana = build_custom_planning_context(PERFIL_JESSIE, None, {SABADO: ["Correr"]})

    def test_sin_dias_abiertos_no_cambia_ni_un_caracter(self):
        self.assertEqual(build_custom_planning_context(PERFIL_JESSIE, None, {}), self.siempre)
        self.assertNotIn("opened", _criticas(self.siempre))

    def test_las_reglas_1_a_3_abren_el_sabado(self):
        criticas = _criticas(self.semana)
        self.assertIn("or on a date the athlete opened for THIS week in their check-in: sábado 2026-09-19 (Correr)",
                      criticas)
        self.assertIn("absolutely NO training whatsoever — except the dates opened this week", criticas)
        self.assertIn("(on a date opened this week, only the sports it was opened with)", criticas)

    def test_la_nota_de_la_tabla_tambien(self):
        self.assertIn("Esta semana el atleta abrio en su check-in sábado 2026-09-19 (Correr)", self.semana)

    def test_lo_de_esta_semana_gana_a_su_descanso_preferido(self):
        self.assertIn("Prefers full rest specifically on: Sábado", self.semana)
        self.assertIn("This week the athlete opened 2026-09-19 in their check-in: that wins over this preference.",
                      self.semana)
        self.assertNotIn("This week the athlete opened", self.siempre)


class ElValidadorAceptaElDiaAbierto(unittest.TestCase):
    def _semana(self, deporte_del_sabado):
        paso = WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                           duration_value=1200, description="20' suave con 4 progresivos")
        dias = []
        for f, n in zip(FECHAS, NOMBRES, strict=True):
            deporte = deporte_del_sabado if n == "Sábado" else None
            dias.append(WorkoutDay(date=f, day_name_es=n, is_rest=deporte is None, sport=deporte or "rest",
                                   focus="x", purpose="x", adaptation="x",
                                   total_duration_minutes=20.0 if deporte else 0.0,
                                   steps=[paso] if deporte else []))
        return WeeklyPlanPayload(athlete_name="Jessie Navas", start_date=FECHAS[0], zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=dias)])

    def _tipos(self, deporte_del_sabado, abiertos, sin_entrenar=()):
        dias = {n: {"available": True, "hours": None, "sports": ["run", "trail", "bike", "strength"], "blocks": []}
                for n in NOMBRES}
        dias["Sábado"] = {"available": False, "hours": 0, "sports": [], "blocks": []}
        limites = {"days": dias, "open_dates": abiertos, "unavailable_dates": list(sin_entrenar)}
        semana = [{"date": f, "day_name_es": n} for f, n in zip(FECHAS, NOMBRES, strict=True)]
        return [v["type"] for v in validate_weekly_plan(self._semana(deporte_del_sabado), limites, semana)["violations"]]

    def test_sin_abrirlo_correr_el_sabado_es_una_violacion(self):
        self.assertIn("day_not_available", self._tipos("run", {}))

    def test_abierto_con_correr_se_puede_correr(self):
        self.assertEqual([t for t in self._tipos("run", {SABADO: ["run"]}) if t != "missing_date"], [])

    def test_abierto_con_correr_no_se_puede_ir_en_bici(self):
        self.assertIn("sport_not_allowed", self._tipos("bike", {SABADO: ["run"]}))

    def test_si_tambien_dijo_que_no_puede_gana_el_no(self):
        self.assertIn("day_not_available", self._tipos("run", {SABADO: ["run"]}, sin_entrenar=[SABADO]))


class ElMacrocicloRecibeLaSemanaDeCadaCarrera(unittest.TestCase):
    CARRERAS = [
        {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B"},
        {"name": "12k UTG Trail Guatemala", "date": "2026-11-21", "priority": "B"},
        {"name": "10k Ciudad de Guatemala", "date": "2027-04-17", "priority": "A"},
    ]

    def test_las_tres_de_jessie(self):
        """La A en la 31 la contó bien el modelo; el trail cae en la 10, donde empezó su fase específica."""
        semanas = [c["semana_del_macrociclo"] for c in con_semana_del_macrociclo(self.CARRERAS, "2026-09-15")]
        self.assertEqual(semanas, [1, 10, 31])

    def test_no_toca_las_originales_ni_inventa_una_semana(self):
        salida = con_semana_del_macrociclo([{"name": "sin fecha", "date": ""}, *self.CARRERAS], "2026-09-15")
        self.assertNotIn("semana_del_macrociclo", salida[0])
        self.assertNotIn("semana_del_macrociclo", self.CARRERAS[0])
        self.assertEqual(con_semana_del_macrociclo(self.CARRERAS, ""), self.CARRERAS)


class ElRangoOptimoEsDeLaCargaAguda(unittest.TestCase):
    def test_con_sus_numeros(self):
        bloque = render_load_state(LoadState(as_of="2026-09-15", garmin_acwr=0.70, acute_load=320,
                                             chronic_load=406, chronic_optimal_min=325, chronic_optimal_max=609))
        self.assertIn("rango óptimo de la carga AGUDA según Garmin, 0,8-1,5 veces la crónica: 325–609", bloque)
        self.assertNotIn("rango crónico", bloque)


if __name__ == "__main__":
    unittest.main()
