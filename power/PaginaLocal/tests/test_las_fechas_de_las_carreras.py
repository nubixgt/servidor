"""Las fechas del macrociclo y de la semana, con el calendario real del atleta 2 (2026-09-24).

Su macrociclo arranca el lunes 2026-09-21 (su semana empieza el lunes; lo generó el miércoles
23). Sus carreras: el 5K Monterrico del domingo 2026-09-20 (B, ya corrido), la 50K Gravel
Maniac del sábado 2026-10-24 (B, semana 5) y el 10K del martes 2027-04-20 (A, semana 31), con
las fases 1-6, 7-12, 13-16, 17-24, 25-29 y 30-31 (afinamiento y carrera A).

Lo que se encontró al revisarlo:
- el macrociclo recibía el 5K como "semana_del_macrociclo": 0, y el prompt decía que
  `season_start_date` era "la fecha de hoy" (el 23) mientras las semanas se contaban desde el 21;
- la semana recibía las carreras sólo con su fecha: contar cuántas semanas faltaban le tocaba
  al modelo;
- la lista de carreras de la semana se cortaba a tres días antes de HOY, y la nota de después
  de una carrera mira hasta 21 días antes de la SEMANA.
"""

import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.season_planner_node import (  # noqa: E402
    SEASON_PLANNER_USER_PROMPT,
    con_semana_del_macrociclo,
)
from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    _VENTANA_DE_RECUPERACION_MAX,
    TRAINING_DECISION_USER_PROMPT,
    carrera_en_afinamiento,
    carrera_reciente,
    carreras_vistas_desde_la_semana,
    compute_mesocycle_position,
)
from services.ai.race_distances import solo_las_que_vienen  # noqa: E402

CARRERAS = [
    {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B", "distance": "5K"},
    {"name": "10K", "date": "2027-04-20", "priority": "A", "distance": "10K"},
    {"name": "50 K Gravel Maniac Bicicleta", "date": "2026-10-24", "priority": "B",
     "distance": "bike_mtb_maraton"},
]
FASES = {
    "season_start_date": "2026-09-21",
    "phases": [
        {"name": "Fase 1", "start_week": 1, "end_week": 6, "deload_weeks": []},
        {"name": "Fase 2", "start_week": 7, "end_week": 12, "deload_weeks": [12]},
        {"name": "Fase 3", "start_week": 13, "end_week": 16, "deload_weeks": [16]},
        {"name": "Fase 4", "start_week": 17, "end_week": 24, "deload_weeks": [21, 24]},
        {"name": "Fase 5", "start_week": 25, "end_week": 29, "deload_weeks": []},
        {"name": "Fase 6 — Taper y carrera A de 10K", "start_week": 30, "end_week": 31, "deload_weeks": []},
    ],
}
_ES = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]


def semana(desde: str, dias: int = 7) -> list[dict]:
    inicio = date.fromisoformat(desde)
    return [{"date": date.fromordinal(inicio.toordinal() + k).isoformat(),
             "day_name_es": _ES[date.fromordinal(inicio.toordinal() + k).weekday()]} for k in range(dias)]


class ElMacrocicloCuentaSusSemanas(unittest.TestCase):
    def test_cada_carrera_en_su_semana_y_la_corrida_sin_semana(self):
        por_nombre = {c["name"]: c for c in con_semana_del_macrociclo(CARRERAS, "2026-09-21")}
        self.assertEqual(por_nombre["10K"]["semana_del_macrociclo"], 31)
        self.assertEqual(por_nombre["50 K Gravel Maniac Bicicleta"]["semana_del_macrociclo"], 5)
        self.assertNotIn("semana_del_macrociclo", por_nombre["5k Monterrico"])
        self.assertTrue(por_nombre["5k Monterrico"]["ya_corrida"])

    def test_el_prompt_da_el_arranque_y_no_dice_que_es_hoy(self):
        """Contado desde el 23 (hoy), el 10K caía en la semana 30; desde el 21, en la 31."""
        self.assertIn("{inicio_del_macrociclo}", SEASON_PLANNER_USER_PROMPT)
        self.assertNotIn("the Date given above (today)", SEASON_PLANNER_USER_PROMPT)

    def test_la_semana_sabe_en_que_semana_y_fase_esta(self):
        self.assertEqual(compute_mesocycle_position(FASES, "2026-09-23")["week_number"], 1)
        self.assertEqual(compute_mesocycle_position(FASES, "2026-10-05")["week_number"], 3)
        carrera_a = compute_mesocycle_position(FASES, "2027-04-20")
        self.assertEqual(carrera_a["week_number"], 31)
        self.assertEqual(carrera_a["phase_name"], "Fase 6 — Taper y carrera A de 10K")


class LaSemanaRecibeCuantoFalta(unittest.TestCase):
    def test_la_semana_del_5_de_octubre(self):
        por_nombre = {c["name"]: c for c in carreras_vistas_desde_la_semana(
            CARRERAS, semana("2026-10-05"), FASES)}
        cinco_k = por_nombre["5k Monterrico"]
        self.assertEqual((cinco_k["ya_corrida"], cinco_k["hace_dias"]), (True, 15))
        self.assertNotIn("semanas_que_faltan", cinco_k)
        gravel = por_nombre["50 K Gravel Maniac Bicicleta"]
        self.assertEqual(gravel["dias_desde_el_inicio_de_esta_semana"], 19)
        self.assertEqual((gravel["semana_del_macrociclo"], gravel["semanas_que_faltan"]), (5, 2))
        self.assertEqual(por_nombre["10K"]["semanas_que_faltan"], 28)

    def test_la_semana_de_la_gravel_la_tiene_dentro(self):
        gravel = carreras_vistas_desde_la_semana(CARRERAS, semana("2026-10-19"), FASES)[2]
        self.assertEqual((gravel["en_esta_semana"], gravel["dia"], gravel["semanas_que_faltan"]),
                         (True, "Sábado", 0))

    def test_un_primer_bloque_corto_cuenta_desde_su_semana(self):
        """El primer bloque de José fue de miércoles a domingo (23-27): es la semana 1."""
        gravel = carreras_vistas_desde_la_semana(CARRERAS, semana("2026-09-23", 5), FASES)[2]
        self.assertEqual(gravel["semanas_que_faltan"], 4)

    def test_sin_macrociclo_solo_los_dias(self):
        gravel = carreras_vistas_desde_la_semana(CARRERAS, semana("2026-10-05"), None)[2]
        self.assertEqual(gravel["dias_desde_el_inicio_de_esta_semana"], 19)
        self.assertNotIn("semanas_que_faltan", gravel)

    def test_el_prompt_dice_que_no_se_recuenta(self):
        self.assertIn("do not recount it from the dates", TRAINING_DECISION_USER_PROMPT)


class LaSemanaDeDespuesVeSuCarrera(unittest.TestCase):
    MARATON = {"name": "Maratón", "date": "2026-10-04", "priority": "A", "distance": "marathon"}

    def test_una_semana_generada_tarde_no_pierde_la_carrera(self):
        """Maratón el domingo 4-oct y la semana del lunes 12 (segunda después). Cortada a tres
        días desde hoy (jueves 8) la carrera desaparecía, y con ella la nota de recuperación,
        cuya ventana para 42 km es de 14 días."""
        viejas = solo_las_que_vienen([self.MARATON], hoy=date(2026, 10, 8))
        self.assertEqual(viejas, [])
        vistas = solo_las_que_vienen([self.MARATON], hoy=date(2026, 10, 12),
                                     dias_atras=_VENTANA_DE_RECUPERACION_MAX)
        reciente = carrera_reciente(vistas, semana("2026-10-12"))
        self.assertIsNotNone(reciente)
        self.assertEqual(reciente["days_since"], 8)


class ElAfinamientoCaeDondeDebe(unittest.TestCase):
    def test_la_gravel_b(self):
        """Ventana B de 10 días: la semana del 12-oct (la gravel a 12 días) todavía es normal; la
        del 19, la de la carrera, es de afinamiento."""
        self.assertIsNone(carrera_en_afinamiento(CARRERAS, semana("2026-10-12")))
        afin = carrera_en_afinamiento(CARRERAS, semana("2026-10-19"))
        self.assertEqual((afin["name"], afin["days_from_week_start"]), ("50 K Gravel Maniac Bicicleta", 5))

    def test_la_semana_de_despues_de_la_gravel(self):
        reciente = carrera_reciente(CARRERAS, semana("2026-10-26"))
        self.assertEqual((reciente["name"], reciente["days_since"]), ("50 K Gravel Maniac Bicicleta", 2))


class ElContextoSeEscribeParaSuSemana(unittest.TestCase):
    def test_la_carrera_del_domingo_es_corrida_para_la_semana_del_lunes(self):
        """La semana del lunes 21 se puede generar el sábado 19: para ella el 5K del domingo 20
        es una carrera corrida, no una que "MUST periodize towards"."""
        from web_app import server

        texto = server.build_custom_planning_context(
            {"competitions": CARRERAS + [{"name": "Vieja", "date": "2026-07-01", "priority": "B"}]},
            referencia=date(2026, 9, 21))
        corridas, _, proximas = texto.partition("[UPCOMING COMPETITIONS]")
        self.assertIn("5k Monterrico", corridas)
        self.assertNotIn("5k Monterrico", proximas)
        self.assertNotIn("Vieja", texto)


class LaDeAntesDeLaSemanaTodaviaNoSeCorrio(unittest.TestCase):

    def test_no_es_ya_corrida(self):
        """José, 2026-10-22: semana del lunes 26 generada el jueves 22; el gravel del sábado 24 llegaba como
        `ya_corrida` y el coach preguntó si ya la había corrido."""
        from datetime import date, timedelta

        from services.ai.langgraph.nodes.weekly_planner_node import carreras_vistas_desde_la_semana
        lunes = date.today() + timedelta(days=4)
        sabado = (date.today() + timedelta(days=2)).isoformat()
        semana = [{"date": (lunes + timedelta(days=i)).isoformat(), "day_name_es": "x"} for i in range(7)]
        [c] = carreras_vistas_desde_la_semana([{"name": "50 K Gravel Maniac", "date": sabado}], semana, None)
        self.assertNotIn("ya_corrida", c)
        self.assertTrue(c["antes_de_esta_semana"] and c["todavia_por_correr"])


if __name__ == "__main__":
    unittest.main()
