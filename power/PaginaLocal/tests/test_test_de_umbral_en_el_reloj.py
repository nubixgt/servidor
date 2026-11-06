"""El test de umbral en el reloj del atleta, y la adherencia por el entreno del reloj (2026-09-14).

Motivos:

- Del usuario, sobre la primera versión: "no estoy de acuerdo que jale cualquier
  actividad del reloj creyendo que es umbral". Buscaba entre las actividades
  recientes la que pareciera un test, y en su cuenta aceptó sus series del 9 y
  del 2 de septiembre. Ahora el test va al reloj con su calentamiento y la app
  lee la actividad hecha con ESE entreno.
- La cuenta real (lista de actividades del 2026-09-14): cada actividad hecha
  desde un entreno programado trae `workoutId` -- su "Ritmo 5K" del 09-09 lleva
  el 1691199606 del entreno subido --, y el detalle de la actividad trae
  `associatedWorkoutId` vacío.
- Del usuario: "el test de umbral se puede realizar cada cierta cantidad de
  meses, por lo que el sistema deberá jalar el más reciente".

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import json
import sys
import tempfile
import unittest
from dataclasses import dataclass
from datetime import date, timedelta
from pathlib import Path
from types import SimpleNamespace
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.adherence import build_adherence_report  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload  # noqa: E402
from services.ai.prescription import build_prescription  # noqa: E402
from services.ai.test_de_umbral import (  # noqa: E402
    PROTOCOLOS,
    analizar_test,
    describir_tests,
    sesion_del_test,
    test_vigente,
    tests_por_fecha,
)
from services.garmin import upload_ledger  # noqa: E402
from services.garmin.garmin_upload_service import UploadResult  # noqa: E402
from services.garmin.workout_builder import build_garmin_workout, compute_zone_config  # noqa: E402

HOY = date.today()


def vuelta(minutos, ritmo_s_km, fc):
    segundos = minutos * 60
    metros = segundos / ritmo_s_km * 1000
    return {"duration_s": segundos, "distance_m": metros, "averageHR_bpm": fc, "averageSpeed_ms": metros / segundos}


# Las cuatro vueltas que produce el entreno del reloj: una por paso.
VUELTAS_DEL_ENTRENO = [vuelta(15, 370, 138), vuelta(10, 280, 165), vuelta(20, 282, 172), vuelta(10, 390, 142)]


class LaSesionQueVaAlReloj(unittest.TestCase):
    def test_lleva_el_calentamiento_y_parte_el_test_en_10_y_20(self):
        s = sesion_del_test("run", "2026-09-24")
        self.assertEqual([p.step_type for p in s.steps], ["warmup", "interval", "interval", "cooldown"])
        self.assertEqual([p.duration_value for p in s.steps], [900, 600, 1200, 600])
        self.assertTrue(all(p.target_type == "none" for p in s.steps))
        self.assertTrue(s.steps[0].description.startswith("Calentamiento 15'"))
        self.assertEqual((s.sport, s.discipline, s.total_duration_minutes, s.day_name_es), ("run", "test", 55, "Jueves"))

    def test_el_protocolo_dice_el_calentamiento_y_que_no_hay_que_pulsar_vuelta(self):
        for deporte in ("run", "bike"):
            with self.subTest(deporte):
                self.assertTrue(PROTOCOLOS[deporte]["pasos"][0].startswith("Calentamiento, 15'"))
                self.assertIn("no hace falta pulsar VUELTA", PROTOCOLOS[deporte]["cuando"])
                self.assertIn("sólo la actividad que hagas con este entreno", PROTOCOLOS[deporte]["calculo"])

    def test_las_vueltas_de_ese_entreno_dan_el_test(self):
        r = analizar_test(VUELTAS_DEL_ENTRENO, "run")
        self.assertTrue(r["ok"], r)
        self.assertEqual((r["lthr"], r["ritmo"], r["minutos_ultimos"]), (172, "4:41", 20.0))

    def test_garmin_lo_recibe_con_su_nombre_y_sus_pasos(self):
        entreno = build_garmin_workout(sesion_del_test("bike", "2026-09-24"), compute_zone_config())
        self.assertEqual(entreno["workoutName"], "Test de umbral - 2026-09-24")
        self.assertIn("Calentamiento 15'", json.dumps(entreno, ensure_ascii=False))

    def test_la_natacion_no_va_al_reloj(self):
        with self.assertRaises(ValueError):
            sesion_del_test("swim", "2026-09-24")


class MandaElMasReciente(unittest.TestCase):
    # Guardado con el vigente MÁS VIEJO que uno de sus anteriores: quien lee no se fía del orden.
    TESTS = {"run": {"fecha": "2026-06-01", "lthr": 170, "anteriores": [
        {"fecha": "2026-09-10", "lthr": 176, "ritmo_s_km": 275.0}, {"fecha": "2026-03-01", "lthr": 165}]}}

    def test_el_vigente_es_el_de_fecha_mas_reciente(self):
        self.assertEqual(test_vigente(self.TESTS, "run")["fecha"], "2026-09-10")
        self.assertEqual([t["fecha"] for t in tests_por_fecha(self.TESTS, "run")],
                         ["2026-09-10", "2026-06-01", "2026-03-01"])

    def test_los_nodos_leen_el_ultimo_y_el_anterior(self):
        linea = describir_tests(self.TESTS, hoy=date(2026, 9, 14))[0]
        self.assertIn("del 2026-09-10: FC umbral 176 ppm, ritmo umbral 4:35/km", linea)
        self.assertIn("el anterior, del 2026-06-01: FC umbral 170 ppm", linea)

    def test_la_ficha_reconoce_el_ultimo(self):
        p = build_prescription(None, {"lactate_threshold_hr": 176, "lactate_threshold_pace_sec_per_km": 275.0,
                                      "threshold_tests": self.TESTS})
        self.assertEqual((p.lthr_source, p.threshold_pace_source), ("test", "test"))


class ElRegistroDeSubidas(unittest.TestCase):
    def _registro(self):
        r = upload_ledger.anotar([], fecha=(HOY - timedelta(days=20)).isoformat(), franja="test-run", workout_id=1,
                                 nombre="Test de umbral", deporte="run", tipo=upload_ledger.TIPO_TEST)
        r = upload_ledger.anotar(r, fecha=(HOY - timedelta(days=20)).isoformat(), franja="unica", workout_id=2,
                                 nombre="Z2", deporte="run")
        return upload_ledger.anotar(r, fecha=(HOY - timedelta(days=40)).isoformat(), franja="test-bike",
                                    workout_id=3, nombre="Test de umbral", deporte="bike",
                                    tipo=upload_ledger.TIPO_TEST)

    def test_el_test_se_anota_como_test(self):
        self.assertEqual([upload_ledger.es_test(e) for e in self._registro()], [True, False, True])

    def test_borrar_o_regenerar_la_semana_no_se_lleva_el_test(self):
        fechas = {(HOY - timedelta(days=20)).isoformat()}
        self.assertEqual([e["workout_id"] for e in upload_ledger.para_fechas(self._registro(), fechas)], [2])
        self.assertEqual([e["workout_id"] for e in upload_ledger.huerfanas(self._registro(), fechas, set())], [2])

    def test_el_test_dura_mas_en_el_reloj_pero_no_para_siempre(self):
        self.assertEqual(sorted(e["workout_id"] for e in upload_ledger.caducadas(self._registro())), [2, 3])


class _Servicio:
    """GarminUploadService sin Garmin: devuelve ids crecientes y recuerda lo que reemplazó."""
    llamadas: list = []

    def __init__(self, client):
        pass

    async def upload_day(self, day, zones, replaces=None):
        _Servicio.llamadas.append((day.focus, day.date, replaces))
        nuevo = 999 + len(_Servicio.llamadas) - 1
        return UploadResult(date=day.date, sport=day.sport, success=True, workout_id=nuevo,
                            replaced_workout_id=replaces)


class SubirYLeerElTest(unittest.TestCase):
    def setUp(self):
        self.server = importlib.import_module("web_app.server")
        self.dir = Path(tempfile.mkdtemp())
        _Servicio.llamadas = []

    def _subir(self, fecha):
        cliente = SimpleNamespace(connect=lambda *a: None)
        with mock.patch.object(self.server, "GarminConnectClient", lambda: cliente), \
                mock.patch.object(self.server, "GarminUploadService", _Servicio):
            return self.server.subir_test_de_umbral("a@b.c", "x", self.dir, "run", fecha)

    def test_se_programa_y_se_anota_y_el_nuevo_reemplaza_al_pendiente(self):
        manana = (HOY + timedelta(days=1)).isoformat()
        primero = self._subir(manana)
        self.assertEqual((primero["workout_id"], primero["nombre"]), (999, f"Test de umbral - {manana}"))
        segundo = self._subir((HOY + timedelta(days=5)).isoformat())
        self.assertEqual(_Servicio.llamadas[1][2], 999)  # reemplaza el pendiente
        tests = [e for e in upload_ledger.cargar(self.dir) if upload_ledger.es_test(e)]
        self.assertEqual([e["workout_id"] for e in tests], [segundo["workout_id"]])

    def test_no_se_programa_en_el_pasado(self):
        with self.assertRaises(self.server.HTTPException):
            self._subir((HOY - timedelta(days=1)).isoformat())

    def _leer(self, actividades, vueltas):
        pedidas = []

        class Extractor:
            def __init__(self, email, password):
                self.garmin = SimpleNamespace(client=SimpleNamespace(get_activities_by_date=lambda d, h: actividades))

            def get_activity_laps(self, activity_id):
                pedidas.append(activity_id)
                return vueltas[activity_id]

            def get_user_profile(self):
                return SimpleNamespace(lactate_threshold_heart_rate=176)

        with mock.patch.object(self.server, "TriathlonCoachDataExtractor", Extractor):
            return self.server.analizar_test_de_umbral("a@b.c", "x", self.dir, "run"), pedidas

    def _anotar_test(self, fecha, workout_id=999):
        upload_ledger.guardar(self.dir, upload_ledger.anotar(
            [], fecha=fecha, franja="test-run", workout_id=workout_id, nombre=f"Test de umbral - {fecha}",
            deporte="run", tipo=upload_ledger.TIPO_TEST))

    def test_lee_solo_la_actividad_de_su_entreno_y_la_mas_reciente(self):
        ayer = (HOY - timedelta(days=1)).isoformat()
        self._anotar_test(ayer)
        series = [vuelta(10, 480, 130)] + [vuelta(2, 300, 160), vuelta(2, 700, 135)] * 8 + [vuelta(10, 600, 120)]
        actividades = [
            {"activityId": 1, "workoutId": None, "startTimeLocal": f"{HOY} 07:00:00", "duration": 3600,
             "activityType": {"typeKey": "running"}, "activityName": "Series"},
            {"activityId": 2, "workoutId": 999, "startTimeLocal": f"{ayer} 07:00:00", "duration": 3300,
             "activityType": {"typeKey": "running"}, "activityName": "Test de umbral - repetido"},
            {"activityId": 3, "workoutId": 999, "startTimeLocal": f"{ayer} 06:00:00", "duration": 3300,
             "activityType": {"typeKey": "running"}, "activityName": "Test de umbral - primero"},
        ]
        r, pedidas = self._leer(actividades, {1: series, 2: VUELTAS_DEL_ENTRENO, 3: VUELTAS_DEL_ENTRENO})
        self.assertEqual(pedidas, [2])
        self.assertTrue(r["encontrada"] and r["ok"])
        self.assertEqual((r["activity_id"], r["lthr"]), (2, 172))

    def test_sin_actividad_de_su_entreno_no_hay_numero(self):
        self._anotar_test((HOY - timedelta(days=1)).isoformat())
        actividades = [{"activityId": 1, "startTimeLocal": f"{HOY} 07:00:00", "duration": 3300,
                        "activityType": {"typeKey": "running"}, "activityName": "Parece un test"}]
        r, pedidas = self._leer(actividades, {1: VUELTAS_DEL_ENTRENO})
        self.assertFalse(r["encontrada"])
        self.assertEqual(pedidas, [])
        self.assertIn("Test de umbral", r["motivo"])

    def test_antes_de_su_dia_todavia_no_toca(self):
        self._anotar_test((HOY + timedelta(days=10)).isoformat())
        r, _ = self._leer([], {})
        self.assertFalse(r["encontrada"])
        self.assertIn("todavía no toca", r["motivo"])

    def test_sin_test_programado_se_pide_enviarlo(self):
        with self.assertRaises(self.server.HTTPException):
            self._leer([], {})


@dataclass
class _Resumen:
    duration: float
    distance: float = 0
    average_hr: int = 0
    max_hr: int = 0


@dataclass
class _Actividad:
    activity_id: int
    start_time: str
    activity_type: str
    activity_name: str
    summary: _Resumen
    workout_id: int | None = None


class LaAdherenciaPorElEntrenoDelReloj(unittest.TestCase):
    """Un martes de 45' hecho el viernes con su entreno y alargado a 70'. A cuatro días y fuera del ±25 %,
    las tolerancias lo daban por no hecho; con el workoutId es exacto."""

    LUNES = date(2026, 9, 7)

    def _plan(self):
        dia = lambda offset, sport, minutos: {  # noqa: E731
            "date": (self.LUNES + timedelta(days=offset)).isoformat(), "day_name_es": "X", "is_rest": False,
            "sport": sport, "focus": "Z2", "purpose": "p.", "adaptation": "a.", "session_slot": "unica",
            "total_duration_minutes": minutos}
        return WeeklyPlanPayload(athlete_name="J", start_date=self.LUNES.isoformat(), zones_table="",
                                 weeks=[{"week_number": 1, "week_rationale": "r",
                                         "days": [dia(1, "run", 45), dia(3, "bike", 60)]}])

    def test_la_sesion_hecha_con_su_entreno_se_empareja_exacta(self):
        martes = (self.LUNES + timedelta(days=1)).isoformat()
        viernes = (self.LUNES + timedelta(days=4)).isoformat()
        subidas = upload_ledger.anotar([], fecha=martes, franja="unica", workout_id=111, nombre="Z2", deporte="run")
        subidas = upload_ledger.anotar(subidas, fecha=martes, franja="test-run", workout_id=222, nombre="Test",
                                       deporte="run", tipo=upload_ledger.TIPO_TEST)
        actividades = [
            _Actividad(1, f"{viernes} 06:00:00", "running", "con el entreno", _Resumen(70 * 60), workout_id=111),
            _Actividad(2, f"{martes} 18:00:00", "running", "rodaje libre", _Resumen(44 * 60)),
        ]
        reporte = build_adherence_report(self._plan(), actividades, today=date(2026, 9, 14), subidas=subidas)
        martes_entry = next(e for e in reporte.entries if e.date == martes)
        self.assertEqual((martes_entry.activity_id, martes_entry.done_on, martes_entry.status),
                         (1, viernes, "modified"))
        self.assertTrue(martes_entry.desde_el_reloj)
        self.assertIn("con su entreno del reloj", martes_entry.describe())
        # El rodaje libre del martes no le roba nada a nadie: queda como entrenamiento por su cuenta.
        self.assertEqual([u.name for u in reporte.unplanned], ["rodaje libre"])

    def test_sin_registro_sigue_como_antes(self):
        martes = (self.LUNES + timedelta(days=1)).isoformat()
        actividades = [_Actividad(2, f"{martes} 18:00:00", "running", "rodaje libre", _Resumen(44 * 60))]
        reporte = build_adherence_report(self._plan(), actividades, today=date(2026, 9, 14))
        self.assertEqual(next(e for e in reporte.entries if e.date == martes).status, "completed")


if __name__ == "__main__":
    unittest.main()
