"""La semana automática cuenta en Métricas (José, 2026-10-19: «el contador de costos no está contando las
generaciones automáticas»).

Su costo siempre entró en el libro (`anotar_gasto` corre igual en las dos), pero la tabla «Atleta por
atleta» cuenta los planes por los clics del atleta (`EventService::EVENTOS`, que sólo ve rutas HTTP), y
la semana automática la lanza el cron sin clic: el atleta salía con su costo y sin el plan que lo
explicaba. Ahora AutoWeekService anota `plan_semana_automatica`, la tabla la cuenta (y dice cuántas
fueron solas), y el libro marca la línea para que el gasto por herramienta la diga aparte.
Verificado en local con un evento sembrado y borrado después: atleta 2, 26 planes, 1 automática.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core import usage_ledger  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


class ElLibro(unittest.TestCase):

    def test_la_automatica_va_dentro_del_total_y_se_dice_aparte(self):
        with tempfile.TemporaryDirectory() as d, mock.patch.object(usage_ledger, "LEDGER_PATH", Path(d) / "u.jsonl"):
            resumen = {"total_cost_usd": 0.30, "total_tokens": 1000, "agents": []}
            usage_ledger.record_run("Jose", usage_ledger.KIND_WEEK, resumen, user_id="u2")
            usage_ledger.record_run("Jose", usage_ledger.KIND_WEEK, resumen, user_id="u2",
                                    detail=usage_ledger.DETALLE_AUTOMATICA)
            semana = usage_ledger.usage_global()["by_kind"]["week"]
        self.assertEqual(semana["runs"], 2)
        self.assertAlmostEqual(semana["cost_usd"], 0.60)
        self.assertEqual((semana["automatic_runs"], semana["automatic_cost_usd"]), (1, 0.30))


class LosCables(unittest.TestCase):

    def test_el_cerebro_marca_la_automatica(self):
        server = (RAIZ / "PaginaLocal/web_app/server.py").read_text(encoding="utf-8")
        self.assertIn("detail=usage_ledger.DETALLE_AUTOMATICA if automatica else None", server)

    def test_el_cron_anota_el_plan_y_la_tabla_lo_cuenta(self):
        auto = (RAIZ / "Backend/src/Services/AutoWeekService.php").read_text(encoding="utf-8")
        self.assertIn("'plan_semana_automatica'", auto)
        eventos = (RAIZ / "Backend/src/Repositories/EventRepository.php").read_text(encoding="utf-8")
        self.assertIn("IN ('plan_temporada','plan_semana','plan_semana_automatica')", eventos)


if __name__ == "__main__":
    unittest.main()
