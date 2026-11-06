"""v7 -- el tablero se actualiza a diario, y sigue sin costar IA.

El atleta lo preguntó exactamente así: *"¿los datos de las gráficas se pueden
actualizar diariamente llamando a la API de Garmin sin análisis extra de IAs?"*.

Sí. La restricción que gobierna este módulo es que la respuesta siga siendo sí
mañana: cero llamadas a un modelo, y de Garmin sólo las peticiones baratas. La
extracción completa tarda dos minutos en la cuenta real del atleta porque pide
el detalle y las vueltas de cada actividad; para dibujar una línea no hace
falta nada de eso.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import shutil
import sys
import tempfile
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import charts, dashboard_refresh  # noqa: E402


class _Sueno:
    def __init__(self, fecha, hrv=None, rhr=None):
        self.date = fecha
        self.sleep = {"avg_overnight_hrv": hrv, "resting_heart_rate": rhr, "quality": {}}


class _Status:
    def __init__(self, vo2=None, fecha=None, balance=None):
        self.vo2_max = {"value": vo2, "date": fecha} if vo2 else None
        self.training_load_balance = balance


class _Prediccion:
    def __init__(self, clave, segundos):
        self.distance_key, self.time_seconds = clave, segundos


class _Extractor:
    """Cuenta las peticiones. Es lo único que este módulo promete controlar."""

    def __init__(self, status=None, predicciones=None, recuperacion=None, revienta=()):
        self._status = status or _Status()
        self._predicciones = predicciones or []
        self._recuperacion = recuperacion or []
        self._revienta = set(revienta)
        self.llamadas: list[str] = []
        self.rango_recuperacion = None

    def get_training_status(self, dia):
        self.llamadas.append("training_status")
        if "training_status" in self._revienta:
            raise RuntimeError("Garmin caido")
        return self._status

    def get_race_predictions(self):
        self.llamadas.append("race_predictions")
        if "race_predictions" in self._revienta:
            raise RuntimeError("Garmin caido")
        return self._predicciones

    def get_recovery_indicators(self, desde, hasta):
        self.llamadas.append("recovery")
        self.rango_recuperacion = (desde, hasta)
        if "recovery" in self._revienta:
            raise RuntimeError("Garmin caido")
        return self._recuperacion


class NoCuestaNiUnaLlamadaDeIA(unittest.TestCase):
    """La restricción del atleta, escrita donde se puede romper."""

    FUENTE = (Path(__file__).resolve().parents[1] / "services/ai/dashboard_refresh.py").read_text(encoding="utf-8")

    def test_no_importa_ningun_modelo(self):
        for prohibido in ("ModelSelector", "AgentRole", "langchain", "ainvoke", "openai", "anthropic"):
            with self.subTest(prohibido=prohibido):
                self.assertNotIn(prohibido, self.FUENTE)

    def test_el_endpoint_tampoco(self):
        servidor = (Path(__file__).resolve().parents[1] / "web_app/server.py").read_text(encoding="utf-8")
        cuerpo = servidor[servidor.index("def refresh_charts("):][:1400]
        self.assertIn("dashboard_refresh.refresh", cuerpo)
        for prohibido in ("run_week_generation", "ModelSelector", "AgentRole"):
            with self.subTest(prohibido=prohibido):
                self.assertNotIn(prohibido, cuerpo)


class SoloLasLlamadasBaratas(unittest.TestCase):
    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def test_son_tres_peticiones_y_ninguna_es_el_detalle_de_actividades(self):
        """La extracción completa tarda dos minutos porque pide el detalle y
        las vueltas de cada actividad. Para dibujar una línea no hace falta."""
        ext = _Extractor(status=_Status(vo2=42.5, fecha="2026-08-27"))
        dashboard_refresh.build_light_snapshot(ext, self.dir, date(2026, 8, 28))
        self.assertEqual(sorted(ext.llamadas), ["race_predictions", "recovery", "training_status"])

    def test_solo_pide_los_DIAS_QUE_FALTAN(self):
        """Es la única llamada que escala con el tiempo, así que es la única
        que hay que acotar. Al día siguiente son dos peticiones, no sesenta."""
        dashboard_refresh.save_snapshot(self.dir, {"as_of": "2026-08-27", "vo2max": [1]})
        ext = _Extractor()
        dashboard_refresh.build_light_snapshot(ext, self.dir, date(2026, 8, 28))
        desde, hasta = ext.rango_recuperacion
        self.assertEqual((desde.isoformat(), hasta.isoformat()), ("2026-08-27", "2026-08-28"))

    def test_un_atleta_que_vuelve_tras_meses_no_dispara_cien_peticiones(self):
        dashboard_refresh.save_snapshot(self.dir, {"as_of": "2026-01-01", "vo2max": [1]})
        ext = _Extractor()
        dashboard_refresh.build_light_snapshot(ext, self.dir, date(2026, 8, 28))
        desde, hasta = ext.rango_recuperacion
        self.assertEqual((hasta - desde).days, dashboard_refresh.MAX_BACKFILL_DAYS)


class UnRefrescoAMediasEsMejorQueNinguno(unittest.TestCase):
    """Un reloj que no mide HRV, o una cuenta sin predicciones, son casos
    normales -- no una excepción en la cara del atleta."""

    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def test_si_falla_una_parte_las_otras_siguen(self):
        ext = _Extractor(
            status=_Status(vo2=42.5, fecha="2026-08-27"),
            recuperacion=[_Sueno("2026-08-28", hrv=64, rhr=48)],
            revienta=("race_predictions",),
        )
        foto = dashboard_refresh.build_light_snapshot(ext, self.dir, date(2026, 8, 28))
        self.assertIn("vo2max", foto)
        self.assertIn("recovery", foto)
        self.assertNotIn("race_predictions", foto)

    def test_si_no_se_leyo_NADA_no_se_guarda_una_foto_vacia(self):
        ext = _Extractor(revienta=("training_status", "race_predictions", "recovery"))
        resultado = dashboard_refresh.refresh(ext, self.dir, date(2026, 8, 28))
        self.assertFalse(resultado["saved"])
        self.assertEqual(resultado["refreshed"], [])

    def test_dice_lo_que_NO_refresco(self):
        """Un tablero que enseña un número viejo sin decir que es viejo es
        peor que uno que no lo enseña."""
        ext = _Extractor(status=_Status(vo2=42.5, fecha="2026-08-27"))
        resultado = dashboard_refresh.refresh(ext, self.dir, date(2026, 8, 28))
        self.assertTrue(any("adherencia" in x for x in resultado["not_refreshed"]))
        self.assertTrue(any("ACWR" in x for x in resultado["not_refreshed"]))

    def test_la_fecha_del_VO2max_es_la_de_garmin_no_la_de_hoy(self):
        """Garmin lo recalcula sólo después de ciertas carreras, así que su
        calendarDate puede ser de hace días. Poner la de hoy inventaría un
        punto que no se midió hoy."""
        ext = _Extractor(status=_Status(vo2=42.5, fecha="2026-08-20"))
        foto = dashboard_refresh.build_light_snapshot(ext, self.dir, date(2026, 8, 28))
        self.assertEqual(foto["vo2max"][0]["date"], "2026-08-20")


class ElRefrescoSeUneConLasGENERACIONES(unittest.TestCase):
    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def test_el_refresco_de_hoy_pisa_a_la_generacion_de_hoy(self):
        """Es la lectura más reciente del mismo reloj."""
        carpeta = self.dir / "reasoning" / "2026-08-28T10-00-00"
        carpeta.mkdir(parents=True)
        (carpeta / "fitness_series.json").write_text(
            json.dumps({"vo2max": [{"date": "2026-08-28", "value": 42.0}]}), encoding="utf-8")
        dashboard_refresh.save_snapshot(self.dir, {
            "as_of": "2026-08-28", "vo2max": [{"date": "2026-08-28", "value": 43.0}]})
        unido = charts.merge_archived_series(self.dir)
        self.assertEqual(unido["vo2max"], [{"date": "2026-08-28", "value": 43.0}])

    def test_refrescar_dos_veces_el_mismo_dia_no_acumula(self):
        for valor in (42.0, 43.0):
            dashboard_refresh.save_snapshot(self.dir, {
                "as_of": "2026-08-28", "vo2max": [{"date": "2026-08-28", "value": valor}]})
        self.assertEqual(len(list((self.dir / "fitness").glob("*.json"))), 1)

    def test_sabe_si_lo_que_ensena_es_de_hoy(self):
        self.assertTrue(dashboard_refresh.is_stale(self.dir, date(2026, 8, 28)))
        dashboard_refresh.save_snapshot(self.dir, {"as_of": "2026-08-28", "vo2max": [1]})
        self.assertFalse(dashboard_refresh.is_stale(self.dir, date(2026, 8, 28)))
        self.assertTrue(dashboard_refresh.is_stale(self.dir, date(2026, 8, 29)))


if __name__ == "__main__":
    unittest.main(verbosity=2)
