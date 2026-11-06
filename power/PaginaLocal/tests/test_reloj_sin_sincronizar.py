"""Lo que el reloj todavía no subió no es un entreno «no realizado».

José, 2026-10-19: «al generar semana si Connect no estaba sincronizado con el reloj, cuenta como que no se
hicieron los entrenos». La adherencia sólo veía las actividades que ya estaban en Garmin Connect, y una
sesión sin actividad su día (ni a su lado) salía «no realizada»: al expediente, a la racha y a la decisión
de la semana siguiente, como si no hubiera entrenado.

Ahora se lee cuándo subió algo su reloj por última vez (`get_device_last_used` →
`lastUsedDeviceUploadTime`, en milisegundos; en su cuenta: Forerunner 265, 1792425092000 = 2026-10-19 15:51:32 UTC) y una sesión sin
actividad de un día que el reloj todavía no subió queda PENDIENTE. La generación siguiente la mide
entera (`server.semana_a_completar`).

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace
from zoneinfo import ZoneInfo

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.adherence import (  # noqa: E402
    build_adherence_report,
    primer_dia_sin_sincronizar,
    render_adherence_block,
)
from services.ai.langgraph.schemas.plan_outputs import WeekBlock, WeeklyPlanPayload, WorkoutDay  # noqa: E402
from services.ai.track_record import summarise_week  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


def _dia(fecha, sport="run", minutos=45.0):
    return WorkoutDay(date=fecha, day_name_es="X", is_rest=False, sport=sport, focus="Base", purpose="p",
                      adaptation="a", total_duration_minutes=minutos, session_slot="unica", steps=[])


def _actividad(i, fecha, minutos):
    return SimpleNamespace(activity_id=i, activity_type="running", activity_name="Rodaje",
                           start_time=f"{fecha} 06:00:00",
                           summary=SimpleNamespace(duration=minutos * 60, distance=None, average_hr=None))


def _plan(dias):
    return WeeklyPlanPayload(athlete_name="T", start_date=dias[0].date, zones_table="z",
                             weeks=[WeekBlock(week_number=1, days=dias)])


class LoQueElRelojNoSubio(unittest.TestCase):

    def setUp(self):
        # La semana del 12 al 18; el reloj sincronizó por última vez el jueves 15 por la mañana.
        self.dias = [_dia("2026-10-13"), _dia("2026-10-15"), _dia("2026-10-17"), _dia("2026-10-18", minutos=90)]
        self.actividades = [_actividad(1, "2026-10-13", 46)]

    def test_sin_saber_la_sincronizacion_todo_sigue_como_antes(self):
        r = build_adherence_report(_plan(self.dias), self.actividades, today=date(2026, 10, 19))
        self.assertEqual([e.status for e in r.entries], ["completed", "missed", "missed", "missed"])

    def test_lo_posterior_a_la_ultima_sincronizacion_queda_pendiente(self):
        r = build_adherence_report(_plan(self.dias), self.actividades, today=date(2026, 10, 19),
                                   ultima_sincronizacion="2026-10-15T13:00:00+00:00")
        self.assertEqual([e.status for e in r.entries], ["completed", "pending", "pending", "pending"])
        self.assertEqual(len(r.evaluated), 1, "sólo se juzga lo que el reloj ya subió")
        self.assertTrue(all(e.sin_sincronizar for e in r.entries[1:]))
        bloque = render_adherence_block(r)
        self.assertIn("no se sabe si las hizo", bloque.lower())
        self.assertNotIn("no realizadas (", bloque.replace("0 no realizadas (", ""))

    def test_un_dia_que_el_reloj_ya_subio_entero_si_se_juzga(self):
        """Sincronizó el 16 a las 13:00 UTC: el 13 y el 15 están enteros en Garmin (en cualquier huso),
        y una sesión sin actividad esos días sí es «no realizada»."""
        r = build_adherence_report(_plan(self.dias), self.actividades, today=date(2026, 10, 19),
                                   ultima_sincronizacion="2026-10-16T13:00:00+00:00")
        self.assertEqual([e.status for e in r.entries], ["completed", "missed", "pending", "pending"])

    def test_lo_que_ya_subio_se_cuenta_aunque_sea_posterior(self):
        r = build_adherence_report(_plan(self.dias), self.actividades + [_actividad(2, "2026-10-18", 92)],
                                   today=date(2026, 10, 19), ultima_sincronizacion="2026-10-15T13:00:00+00:00")
        self.assertEqual(r.entries[-1].status, "completed")

    def test_el_expediente_no_la_da_por_caida(self):
        r = build_adherence_report(_plan(self.dias), self.actividades, today=date(2026, 10, 19),
                                   ultima_sincronizacion="2026-10-15T13:00:00+00:00")
        semana = _plan(self.dias).model_dump()["weeks"][0]
        linea = summarise_week(semana, r.to_state_dict())
        self.assertEqual(linea.get("missed_sessions"), 0)


class ElHuso(unittest.TestCase):

    def test_el_dia_es_el_del_atleta(self):
        """Sincronizar a las 02:00 UTC del 16 son las 21:00 del 15 en Lima: el 15 no estaba entero."""
        lima = ZoneInfo("America/Lima")
        self.assertEqual(primer_dia_sin_sincronizar("2026-10-16T02:00:00+00:00", lima), date(2026, 10, 15))
        self.assertEqual(primer_dia_sin_sincronizar("2026-10-16T13:00:00Z", lima), date(2026, 10, 16))
        # La de José, leída de su cuenta: `lastUsedDeviceUploadTime` 1792425092000 = 15:51:32 UTC del
        # lunes 19 (09:51 en su hora, UTC-6): el domingo 18 ya estaba entero en Garmin.
        self.assertEqual(primer_dia_sin_sincronizar("2026-10-19T15:51:32+00:00", ZoneInfo("America/Guatemala")),
                         date(2026, 10, 19))

    def test_sin_dato_no_hay_corte(self):
        self.assertIsNone(primer_dia_sin_sincronizar(None))
        self.assertIsNone(primer_dia_sin_sincronizar("no es una fecha"))


class ElCable(unittest.TestCase):

    def test_las_tres_mediciones_la_reciben(self):
        server = (RAIZ / "PaginaLocal/web_app/server.py").read_text(encoding="utf-8")
        self.assertEqual(server.count('ultima_sincronizacion=getattr(garmin_data, "ultima_sincronizacion", None)'),
                         server.count("build_adherence_report("))
        extractor = (RAIZ / "PaginaLocal/services/garmin/data_extractor.py").read_text(encoding="utf-8")
        self.assertIn('data["ultima_sincronizacion"] = self.get_ultima_sincronizacion()', extractor)


if __name__ == "__main__":
    unittest.main()
