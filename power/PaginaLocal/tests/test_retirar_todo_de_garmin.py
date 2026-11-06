"""Antes de reiniciar la beta, se retira de Garmin TODO lo que la app subió.

El reinicio de la Beta 2.0 (2026-09-15) borra las cuentas y `_runtime/`, y con
ellos el registro de subidas: lo único que dice qué entrenos del calendario de
Garmin creó esta app. Borrar al atleta sin retirarlos antes los deja en su reloj
para siempre. `retirar_semana_de_garmin_con(..., todas=True)` retira todo lo
anotado, de cualquier fecha; sin la bandera sigue retirando sólo las fechas del
plan, que es lo que hace "Eliminar plan".

La regla de seguridad no cambia en ninguno de los dos casos: sólo ids anotados.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.garmin import upload_ledger  # noqa: E402
from web_app import server  # noqa: E402


class _Servicio:
    def __init__(self, *_):
        self.borrados = []

    def delete_workout(self, workout_id):
        self.borrados.append(workout_id)
        return True


def _dir_con_registro(fechas_del_plan):
    d = Path(tempfile.mkdtemp())
    upload_ledger.guardar(d, [
        {"date": "2026-08-31", "session_slot": "unica", "workout_id": 101, "name": "vieja"},
        {"date": "2026-09-14", "session_slot": "unica", "workout_id": 202, "name": "vigente"},
    ])
    semana = {"athlete_name": "T", "start_date": fechas_del_plan[0], "zones_table": "",
              "weeks": [{"week_number": 1, "days": [
                  {"date": f, "day_name_es": "X", "is_rest": True} for f in fechas_del_plan]}]}
    (d / "weekly_plan.json").write_text(json.dumps(semana), encoding="utf-8")
    return d


class RetirarTodoAntesDelReinicio(unittest.TestCase):
    def _retirar(self, todas):
        d = _dir_con_registro(["2026-09-14"])
        servicio = _Servicio()
        with mock.patch.object(server, "GarminConnectClient") as cliente, \
                mock.patch.object(server, "GarminUploadService", return_value=servicio):
            cliente.return_value.connect.return_value = None
            salida = server.retirar_semana_de_garmin_con("a@b.c", "clave", d, todas=todas)
        return salida, servicio.borrados, upload_ledger.cargar(d)

    def test_con_todas_se_retira_tambien_lo_de_semanas_pasadas(self):
        salida, borrados, queda = self._retirar(todas=True)
        self.assertEqual(sorted(borrados), [101, 202])
        self.assertEqual(salida["retirados"], 2)
        self.assertEqual(queda, [])

    def test_sin_la_bandera_solo_las_fechas_del_plan(self):
        """Lo de siempre: "Eliminar plan" no toca entrenos de otras semanas."""
        salida, borrados, queda = self._retirar(todas=False)
        self.assertEqual(borrados, [202])
        self.assertEqual([e["workout_id"] for e in queda], [101])

    def test_sin_registro_no_se_conecta_a_garmin(self):
        d = Path(tempfile.mkdtemp())
        with mock.patch.object(server, "GarminConnectClient") as cliente:
            salida = server.retirar_semana_de_garmin_con("a@b.c", "clave", d, todas=True)
        cliente.assert_not_called()
        self.assertEqual(salida["retirados"], 0)


if __name__ == "__main__":
    unittest.main()
