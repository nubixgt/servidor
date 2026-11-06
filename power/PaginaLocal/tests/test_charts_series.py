"""v7 -- las cinco gráficas nuevas, y de dónde salen sin costar una llamada.

El atleta preguntó qué más se podía dibujar. La respuesta interesante no era
"más cosas", era que **cada generación ya traía meses de datos de Garmin que
se resumían en prosa y se tiraban**: el arco de VO2max de un año
(`long_term_vo2_max_trend`, 360 días muestreados cada 14) y la HRV nocturna y
el pulso en reposo **día a día** (`recovery_indicators`). Estaban en el
`GarminData` de cada corrida y nadie los guardaba.

Así que estas gráficas no piden un dato nuevo a Garmin ni a ningún modelo:
archivan lo que ya venía. Y por eso son largas desde la PRIMERA generación, en
vez de tardar meses en tener forma como las que salen del expediente.

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

from services.ai import charts  # noqa: E402
from services.ai.fitness_series import build_fitness_series  # noqa: E402


class _Sueno:
    """Lo que `get_recovery_indicators` produce de verdad: un objeto con
    `.date` y un `.sleep` que lleva la HRV nocturna y el pulso en reposo."""

    def __init__(self, fecha, hrv=None, rhr=None, sueno=None):
        self.date = fecha
        self.sleep = {
            "avg_overnight_hrv": hrv,
            "resting_heart_rate": rhr,
            "quality": {"overall_score": sueno},
        }


class _Garmin:
    def __init__(self, largo=None, corto=None, recuperacion=None):
        self.long_term_vo2_max_trend = largo
        self.vo2_max_history = corto
        self.recovery_indicators = recuperacion or []


class LaSerieSaleDeLoQueYaSeTraia(unittest.TestCase):
    """Ninguna llamada nueva: el `GarminData` es el que la generación ya tenía
    en la mano."""

    FUENTE = (Path(__file__).resolve().parents[1] / "services/ai/fitness_series.py").read_text(encoding="utf-8")

    def test_no_llama_a_garmin_ni_a_un_modelo(self):
        for prohibido in ("garminconnect", "DataExtractor", "ModelSelector", "ainvoke", "requests"):
            with self.subTest(prohibido=prohibido):
                self.assertNotIn(prohibido, self.FUENTE)

    def test_une_la_tendencia_larga_con_el_historial_corto(self):
        """La larga da el arco de un año, la corta el detalle reciente."""
        serie = build_fitness_series(_Garmin(
            largo={"running": [{"date": "2026-01-01", "value": 40}, {"date": "2026-06-01", "value": 41}]},
            corto={"running": [{"date": "2026-08-01", "value": 42.5}]},
        ))
        self.assertEqual([p["date"] for p in serie["vo2max"]],
                         ["2026-01-01", "2026-06-01", "2026-08-01"])

    def test_en_una_fecha_repetida_gana_el_historial_fino(self):
        serie = build_fitness_series(_Garmin(
            largo={"running": [{"date": "2026-08-01", "value": 40}]},
            corto={"running": [{"date": "2026-08-01", "value": 42}]},
        ))
        self.assertEqual(serie["vo2max"], [{"date": "2026-08-01", "value": 42.0}])

    def test_recoge_hrv_y_pulso_en_reposo_dia_a_dia(self):
        serie = build_fitness_series(_Garmin(recuperacion=[
            _Sueno("2026-08-01", hrv=64, rhr=48, sueno=81),
            _Sueno("2026-08-02", hrv=68, rhr=47),
        ]))
        self.assertEqual(len(serie["recovery"]), 2)
        self.assertEqual(serie["recovery"][1]["hrv"], 68.0)
        self.assertEqual(serie["recovery"][0]["sleep_score"], 81)

    def test_una_noche_sin_medir_no_deja_un_cero(self):
        """Un hueco y un cero son información distinta: un cero de HRV se
        dibujaría como un desplome que no ocurrió."""
        serie = build_fitness_series(_Garmin(recuperacion=[
            _Sueno("2026-08-01", hrv=64, rhr=48),
            _Sueno("2026-08-02"),  # el reloj no midió esa noche
        ]))
        self.assertEqual(len(serie["recovery"]), 1)

    def test_un_reloj_que_no_mide_nada_no_rompe_la_generacion(self):
        """Es un caso normal, no un error: hay relojes sin HRV y cuentas sin
        historial de VO2max, y una generación que el atleta ya pagó no puede
        caerse por eso."""
        self.assertEqual(build_fitness_series(_Garmin()), {})
        self.assertEqual(build_fitness_series(None), {})


class ElArchivoSeUneEntreGeneraciones(unittest.TestCase):
    """Cada corrida aporta sus días; los solapados no se duplican."""

    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def _generacion(self, sello, serie=None, prescripcion=None):
        carpeta = self.dir / "reasoning" / sello
        carpeta.mkdir(parents=True, exist_ok=True)
        if serie is not None:
            (carpeta / "fitness_series.json").write_text(json.dumps(serie), encoding="utf-8")
        if prescripcion is not None:
            (carpeta / "prescription.json").write_text(json.dumps(prescripcion), encoding="utf-8")

    def test_dos_generaciones_no_duplican_los_dias_que_comparten(self):
        self._generacion("2026-08-20T10-00-00", {"vo2max": [
            {"date": "2026-08-01", "value": 41}, {"date": "2026-08-15", "value": 42}]})
        self._generacion("2026-08-27T10-00-00", {"vo2max": [
            {"date": "2026-08-15", "value": 42}, {"date": "2026-08-22", "value": 43}]})
        unido = charts.merge_archived_series(self.dir)
        self.assertEqual([p["date"] for p in unido["vo2max"]],
                         ["2026-08-01", "2026-08-15", "2026-08-22"])

    def test_en_una_fecha_repetida_gana_la_generacion_MAS_RECIENTE(self):
        """Es la que Garmin recalculó al final, con las actividades de ese día
        ya sincronizadas."""
        self._generacion("2026-08-20T10-00-00", {"vo2max": [{"date": "2026-08-15", "value": 41}]})
        self._generacion("2026-08-27T10-00-00", {"vo2max": [{"date": "2026-08-15", "value": 43}]})
        self.assertEqual(charts.merge_archived_series(self.dir)["vo2max"][0]["value"], 43)

    def test_las_predicciones_se_leen_del_archivo_VIEJO_si_hace_falta(self):
        """`prescription.json` se guarda desde v6, así que esta gráfica
        funciona hacia atrás en vez de esperar a la próxima generación."""
        self._generacion("2026-08-26T10-00-00", prescripcion={
            "race_predictions": [{"distance_key": "5K", "time_seconds": 1636}]})
        self._generacion("2026-08-27T10-00-00", prescripcion={
            "race_predictions": [{"distance_key": "5K", "time_seconds": 1639}]})
        serie = charts.merge_archived_series(self.dir)["race_predictions"]
        self.assertEqual([e["date"] for e in serie], ["2026-08-26", "2026-08-27"])
        self.assertEqual(serie[-1]["times"]["5K"], 1639)

    def test_un_archivo_corrupto_no_se_lleva_la_serie(self):
        self._generacion("2026-08-20T10-00-00", {"vo2max": [{"date": "2026-08-01", "value": 41}]})
        malo = self.dir / "reasoning" / "2026-08-27T10-00-00"
        malo.mkdir(parents=True, exist_ok=True)
        (malo / "fitness_series.json").write_text("{esto no es json", encoding="utf-8")
        self.assertEqual(len(charts.merge_archived_series(self.dir)["vo2max"]), 1)


class ElVo2maxSeDibujaConSuRango(unittest.TestCase):
    def test_no_aparece_con_un_solo_punto(self):
        self.assertEqual(charts.fitness_trend([{"date": "2026-08-01", "value": 42}]), {})

    def test_dice_cuanto_ha_cambiado_desde_el_primer_dato(self):
        datos = charts.fitness_trend([
            {"date": "2026-01-01", "value": 40.0}, {"date": "2026-08-01", "value": 42.5}])
        self.assertEqual(datos["current"], 42.5)
        self.assertEqual(datos["change"], 2.5)

    def test_lleva_los_extremos_del_eje(self):
        """El eje no empieza en cero -- la variación útil de un VO2max son dos
        puntos y desde cero es invisible -- así que los extremos van escritos:
        un eje recortado sin números sí sería engañoso."""
        datos = charts.fitness_trend([
            {"date": "2026-01-01", "value": 40.0}, {"date": "2026-08-01", "value": 42.5}])
        self.assertEqual((datos["min"], datos["max"]), (40.0, 42.5))

    def test_una_serie_de_UN_ANO_lleva_el_ano_en_el_eje(self):
        """El VO2max real del atleta va de 2025-08-09 a 2026-08-28 y el eje
        decia "9 ago ... 28 ago": un ano entero que se lee como tres semanas.
        `_short_date` es correcto para doce semanas de expediente y falso para
        esto."""
        datos = charts.fitness_trend([
            {"date": "2025-08-09", "value": 45.0}, {"date": "2026-08-28", "value": 49.0}])
        # Con el año entero desde el 2026-10-21: «ago 25» se leía como «25 de agosto».
        self.assertEqual([p["label"] for p in datos["points"]], ["ago 2025", "ago 2026"])

    def test_una_serie_CORTA_no_se_llena_de_anos(self):
        datos = charts.fitness_trend([
            {"date": "2026-08-01", "value": 42.0}, {"date": "2026-08-28", "value": 43.0}])
        self.assertEqual([p["label"] for p in datos["points"]], ["1 ago", "28 ago"])

    def test_una_serie_larga_se_adelgaza_conservando_el_ultimo(self):
        """El valor de hoy es el que el atleta viene a buscar."""
        serie = [{"date": f"2026-{m:02d}-{d:02d}", "value": 40 + d / 10}
                 for m in range(1, 13) for d in range(1, 29)]
        datos = charts.fitness_trend(serie)
        self.assertLessEqual(len(datos["points"]), charts.SERIES_POINTS_MAX)
        self.assertEqual(datos["points"][-1]["value"], serie[-1]["value"])


class LaRecuperacionVaConLaBandaPROPIA(unittest.TestCase):
    """El rango base es el de ESTE atleta, no una zona óptima general. Es la
    misma regla que `services/ai/recovery_state.py` fija desde v4: fuera de él
    esos números no significan nada."""

    SERIE = [{"date": "2026-08-01", "hrv": 64, "rhr": 48},
             {"date": "2026-08-02", "hrv": 68, "rhr": 47},
             {"date": "2026-08-03", "hrv": 61, "rhr": 49}]

    def test_lleva_el_rango_base_del_atleta(self):
        datos = charts.recovery_trend(self.SERIE, {
            "hrv_baseline_low": 56, "hrv_baseline_high": 82, "hrv_status": "BALANCED"})
        self.assertEqual(datos["hrv"]["band"], [56, 82])
        self.assertEqual(datos["hrv"]["current"], 61)

    def test_sin_rango_base_se_dibuja_la_linea_pero_no_una_banda_inventada(self):
        datos = charts.recovery_trend(self.SERIE, {})
        self.assertIsNone(datos["hrv"]["band"])

    def test_las_dos_series_van_por_separado(self):
        """ms y ppm son escalas muy distintas: en un solo eje una de las dos
        quedaría plana para siempre."""
        datos = charts.recovery_trend(self.SERIE, {})
        self.assertEqual(datos["hrv"]["unit"], "ms")
        self.assertEqual(datos["rhr"]["unit"], "ppm")

    def test_dice_que_la_banda_es_personal(self):
        datos = charts.recovery_trend(self.SERIE, {})
        self.assertIn("TU rango normal", datos["note"])
        self.assertIn("no significa nada para otra persona", datos["note"])

    def test_sin_datos_no_hay_grafica(self):
        self.assertEqual(charts.recovery_trend([], {}), {})
        self.assertEqual(charts.recovery_trend([{"date": "2026-08-01", "hrv": 64}], {}), {})


class LaMetaContraLaPrediccion(unittest.TestCase):
    """La gráfica que responde a la pregunta por la que alguien entrena."""

    SERIE = [
        {"date": "2026-08-26", "times": {"5K": 1660, "10K": 3512}},
        {"date": "2026-08-27", "times": {"5K": 1639, "10K": 3512}},
    ]
    CARRERA = [{"name": "5k Monterrico", "date": "2026-09-20",
                "target_time": "00:26:00", "distance": "5K", "race_type": "Olympic"}]

    def test_dibuja_la_prediccion_y_la_meta(self):
        datos = charts.race_goal_gap(self.SERIE, self.CARRERA, today=date(2026, 8, 28))
        self.assertEqual(datos["goal_clock"], "26:00")
        self.assertEqual(datos["current_clock"], "27:19")
        self.assertEqual(datos["gap_seconds"], 79)
        self.assertFalse(datos["reached"])
        self.assertEqual(len(datos["points"]), 2)

    def test_elige_la_carrera_MAS_PROXIMA(self):
        carreras = self.CARRERA + [{"name": "10k", "date": "2027-04-20",
                                    "target_time": "00:53:00", "distance": "10K"}]
        datos = charts.race_goal_gap(self.SERIE, carreras, today=date(2026, 8, 28))
        self.assertEqual(datos["distance_key"], "5K")

    def test_una_carrera_YA_PASADA_no_se_dibuja(self):
        self.assertEqual(charts.race_goal_gap(self.SERIE, self.CARRERA, today=date(2026, 10, 1)), {})

    def test_sin_meta_declarada_no_hay_grafica(self):
        """Sin meta esto es la predicción sola, que ya vive en el informe."""
        sin_meta = [dict(self.CARRERA[0], target_time="")]
        self.assertEqual(charts.race_goal_gap(self.SERIE, sin_meta, today=date(2026, 8, 28)), {})

    def test_si_ya_esta_dentro_lo_dice(self):
        rapido = [{"date": "2026-08-27", "times": {"5K": 1500}}]
        datos = charts.race_goal_gap(rapido, self.CARRERA, today=date(2026, 8, 28))
        self.assertTrue(datos["reached"])

    def test_no_emite_ningun_veredicto_sobre_la_meta(self):
        """Si la meta es alcanzable lo decide el planificador de temporada, que
        tiene el contexto entero. Ver services/ai/goal_realism.py."""
        datos = charts.race_goal_gap(self.SERIE, self.CARRERA, today=date(2026, 8, 28))
        texto = json.dumps(datos, ensure_ascii=False).lower()
        for veredicto in ("irreal", "imposible", "ambicios", "poco realista"):
            with self.subTest(palabra=veredicto):
                self.assertNotIn(veredicto, texto)


class ElDiaQueSeCae(unittest.TestCase):
    """Una adherencia del 70% sostenida puede ser repartida o ser siempre el
    mismo día. Son dos problemas distintos con dos soluciones distintas."""

    def _semanas(self):
        # Tres semanas: desde el 2026-10-22 un día se señala con 3 sesiones o más (MUESTRA_MINIMA_DEL_DIA).
        return [
            {"by_weekday": {"martes": [1, 1], "jueves": [1, 0], "sábado": [1, 1]}},
            {"by_weekday": {"martes": [1, 1], "jueves": [1, 0], "sábado": [1, 1]}},
            {"by_weekday": {"martes": [1, 1], "jueves": [1, 0], "sábado": [1, 1]}},
        ]

    def test_encuentra_el_dia_flojo(self):
        datos = charts.weekday_adherence(self._semanas())
        self.assertEqual(datos["weakest"], "jueves")

    def test_con_dos_sesiones_no_se_saca_conclusion(self):
        """«Los sábados cumples menos» salía de 1 de 2 sesiones (revisión del 2026-10-22)."""
        datos = charts.weekday_adherence(self._semanas()[:2])
        self.assertIsNone(datos["weakest"])
        self.assertIsNone(datos["note"])

    def test_los_dias_salen_en_orden_de_semana(self):
        """Ordenados alfabéticamente, el domingo iría primero."""
        datos = charts.weekday_adherence(self._semanas())
        self.assertEqual([p["full"] for p in datos["points"]], ["martes", "jueves", "sábado"])

    def test_sin_un_dia_flojo_no_se_señala_ninguno(self):
        datos = charts.weekday_adherence([
            {"by_weekday": {"martes": [2, 2], "jueves": [2, 2]}},
            {"by_weekday": {"martes": [1, 1], "jueves": [1, 1]}},
        ])
        self.assertIsNone(datos["weakest"])

    def test_con_una_sola_semana_no_hay_patron(self):
        """Es la adherencia de esa semana contada de otra forma."""
        self.assertEqual(charts.weekday_adherence([{"by_weekday": {"martes": [1, 0]}}]), {})

    def test_una_semana_sin_evaluar_no_aporta_nada(self):
        self.assertEqual(charts.weekday_adherence([{"by_weekday": None}, {}]), {})


class ElVolumenAcumulado(unittest.TestCase):
    def test_suma_horas_y_kilometros_por_disciplina(self):
        datos = charts.volume_by_sport([
            {"executed_by_discipline": {"carrera": {"hours": 2.0, "km": 18.0},
                                        "fuerza": {"hours": 1.0, "km": 0}}},
            {"executed_by_discipline": {"carrera": {"hours": 3.0, "km": 27.0}}},
        ])
        carrera = next(p for p in datos["points"] if p["label"] == "Carrera")
        self.assertEqual((carrera["hours"], carrera["km"]), (5.0, 45.0))
        self.assertEqual(datos["total_hours"], 6.0)
        self.assertEqual(datos["weeks"], 2)

    def test_ordena_por_horas_y_no_por_kilometros(self):
        """40 km de bici y 40 km de carrera no son el mismo entrenamiento: las
        horas son lo único que compara de verdad un deporte con otro."""
        datos = charts.volume_by_sport([
            {"executed_by_discipline": {"bici": {"hours": 1.0, "km": 30.0},
                                        "carrera": {"hours": 2.0, "km": 18.0}}},
        ])
        self.assertEqual(datos["points"][0]["label"], "Carrera")

    def test_una_disciplina_sin_kilometros_conserva_sus_horas(self):
        datos = charts.volume_by_sport([
            {"executed_by_discipline": {"fuerza": {"hours": 1.5, "km": 0}}}])
        self.assertEqual(datos["points"][0]["hours"], 1.5)
        self.assertEqual(datos["points"][0]["km"], 0)

    def test_sin_ejecucion_registrada_no_hay_grafica(self):
        self.assertEqual(charts.volume_by_sport([{"prescribed_hours": 5.0}]), {})


if __name__ == "__main__":
    unittest.main(verbosity=2)
