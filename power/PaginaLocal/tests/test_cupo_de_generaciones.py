"""El tope de generaciones y la alerta de gasto (D2, Beta 3.0 F2).

Decisión del responsable (2026-10-10): 3 versiones de una misma semana, 1 macrociclo cada
7 días, los fallos no gastan cupo, la semana automática no cuenta, y alerta al
administrador por encima de 1 USD de un atleta en un día. La Beta 1 tuvo un atleta con 16
planes en un día; el libro de costos local tiene al atleta 2 con 4,58 USD el 2026-09-23
(8 semanas y 5 macrociclos de pruebas en un solo día).

Correr con:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from datetime import date, datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core import usage_ledger  # noqa: E402
from services.ai import cupo_de_generaciones as cupo  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


class LaCuentaDeVersiones(unittest.TestCase):

    def test_tres_versiones_y_la_cuarta_no(self):
        objetivo = date(2026, 10, 19)
        self.assertTrue(cupo.cupo_de_la_semana(objetivo, ["2026-10-19"] * 2)["allowed"])
        c = cupo.cupo_de_la_semana(objetivo, ["2026-10-19"] * 3)
        self.assertEqual((c["allowed"], c["usadas"], c["quedan"]), (False, 3, 0))
        self.assertIn("las 3 versiones de tu semana del lunes 19 de octubre", c["reason"])

    def test_un_dia_de_holgura_y_no_mas(self):
        """La primera semana arranca hoy o mañana (elige el atleta): borrarla y pedirla
        "desde mañana" es la misma semana. Dos semanas de verdad están a 3 días o más
        (`MIN_DIAS_DEL_BLOQUE`), así que la holgura nunca las junta."""
        from web_app.server import MIN_DIAS_DEL_BLOQUE
        objetivo = date(2026, 10, 12)
        self.assertEqual(cupo.versiones_de(objetivo, ["2026-10-11", "2026-10-12", "2026-10-13"]), 3)
        self.assertEqual(cupo.versiones_de(objetivo, ["2026-10-09", "2026-10-15"]), 0)
        self.assertGreater(MIN_DIAS_DEL_BLOQUE, 1 + 1, "la holgura de ±1 día juntaría dos semanas reales")

    def test_lo_ilegible_no_cuenta(self):
        self.assertEqual(cupo.versiones_de(date(2026, 10, 12), [None, "", "ayer", "2026-13-40"]), 0)


class LaSemanaQueSePaga(unittest.TestCase):
    """El tope tiene que contar la misma semana que se escribe."""

    def _plan(self):
        from services.ai.langgraph.schemas.plan_outputs import WeekBlock, WeeklyPlanPayload, WorkoutDay
        dias = [WorkoutDay(date=f"2026-10-{d}", day_name_es="x", is_rest=True, sport="rest", focus="Descanso",
                           purpose="-", adaptation="-", steps=[], total_duration_minutes=0) for d in range(12, 19)]
        return WeeklyPlanPayload(athlete_name="x", start_date="2026-10-12", zones_table="",
                                 weeks=[WeekBlock(week_number=1, days=dias)])

    def test_siguiente_rehacer_y_primera(self):
        from web_app.server import semana_objetivo
        ahora = datetime(2026, 10, 17, 10)
        self.assertEqual(semana_objetivo(self._plan(), ahora), date(2026, 10, 19))
        self.assertEqual(semana_objetivo(self._plan(), ahora, rehacer=True), date(2026, 10, 12))
        self.assertEqual(semana_objetivo(None, ahora, "2026-10-18"), date(2026, 10, 18))
        self.assertEqual(semana_objetivo(None, ahora, "2026-11-01"), date(2026, 10, 17), "sólo hoy o mañana")

    def test_la_generacion_usa_la_misma_funcion(self):
        fuente = (RAIZ / "PaginaLocal/web_app/server.py").read_text(encoding="utf-8")
        self.assertIn("start_date = semana_objetivo(existing_payload, now, inicio, rehacer)", fuente)
        self.assertIn("pl_server.semana_objetivo(", (RAIZ / "AiService/main.py").read_text(encoding="utf-8"))


class LasConstantesValenLoMismoEnPhp(unittest.TestCase):

    def test_tope_dias_y_holgura(self):
        php = (RAIZ / "Backend/src/Repositories/GenerationRepository.php").read_text(encoding="utf-8")
        self.assertEqual(int(re.search(r"TOPE_POR_SEMANA = (\d+);", php).group(1)), cupo.TOPE_POR_SEMANA)
        self.assertEqual(int(re.search(r"DIAS_ENTRE_MACROCICLOS = (\d+);", php).group(1)),
                         cupo.DIAS_ENTRE_MACROCICLOS)
        servicio = (RAIZ / "Backend/src/Services/PlanService.php").read_text(encoding="utf-8")
        self.assertIn("abs((strtotime($w) - $t) / 86400) <= " + str(cupo._HOLGURA_DIAS), servicio)

    def test_la_automatica_y_los_fallos_no_cuentan(self):
        php = (RAIZ / "Backend/src/Repositories/GenerationRepository.php").read_text(encoding="utf-8")
        consulta = php[php.index("function semanasQueCuentan"):php.index("function macrocicloDisponibleDesde")]
        self.assertIn("automatic = 0", consulta)
        self.assertIn("status <> 'error'", consulta)
        servicio = (RAIZ / "Backend/src/Services/PlanService.php").read_text(encoding="utf-8")
        self.assertIn("'versiones_previas' => $automatica ? null :", servicio)


class ElGastoPorDia(unittest.TestCase):
    """Con líneas con la forma real del libro (`data/billing/usage.jsonl`)."""

    LIBRO = [
        {"ts": "2026-09-23T09:10:00-06:00", "user_id": "u2", "kind": "week", "cost_usd": 0.30},
        {"ts": "2026-09-23T10:10:00-06:00", "user_id": "u2", "kind": "season", "cost_usd": 0.41},
        {"ts": "2026-09-23T11:10:00-06:00", "user_id": "u2", "kind": "week", "cost_usd": 0.33},
        {"ts": "2026-09-23T12:10:00-06:00", "user_id": "u2", "kind": "replay", "cost_usd": 5.00},
        {"ts": "2026-09-23T13:10:00-06:00", "user_id": "u3", "kind": "chat", "cost_usd": 0.002},
        {"ts": "2026-09-24T09:10:00-06:00", "user_id": "u2", "kind": "week", "cost_usd": 0.30},
        {"ts": "2026-09-23T09:10:00-06:00", "user_id": None, "kind": "week", "cost_usd": 3.0},
    ]

    def test_suma_por_atleta_y_dia_sin_el_banco(self):
        """El banco de pruebas (`replay`) es gasto del operador, no del atleta."""
        [a] = usage_ledger.gasto_por_dia(self.LIBRO, umbral=1.0)
        self.assertEqual((a["user_id"], a["dia"], a["cost_usd"]), ("u2", "2026-09-23", 1.04))
        self.assertEqual(a["usos"], {"week": 2, "season": 1})

    def test_la_ventana_y_el_umbral(self):
        self.assertEqual(usage_ledger.gasto_por_dia(self.LIBRO, umbral=1.0, dias=1, hoy="2026-09-24"), [])
        dias = [a["dia"] for a in usage_ledger.gasto_por_dia(self.LIBRO, umbral=0.2)]
        self.assertEqual(dias, ["2026-09-24", "2026-09-23"], "del más reciente al más viejo")

    def test_el_umbral_es_el_que_decidio_el_responsable(self):
        self.assertEqual(usage_ledger.UMBRAL_DE_ALERTA_USD, 1.0)
        php = (RAIZ / "Backend/src/Services/SpendingAlertService.php").read_text(encoding="utf-8")
        self.assertIn("UMBRAL_USD = 1.0;", php)


if __name__ == "__main__":
    unittest.main()
