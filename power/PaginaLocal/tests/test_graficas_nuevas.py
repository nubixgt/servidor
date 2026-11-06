"""Datos que Garmin ya bajaba y que el atleta no podía ver.

Sueño, estrés y peso se descargaban en CADA generación, llegaban a los prompts
del experto de fisiología y del de métricas, y no existía ninguna gráfica de
ellos. La puntuación de sueño incluso se archivaba ya en `fitness_series.json`
desde el principio -- 93 días de datos reales en la cuenta del atleta -- y
`recovery_trend` la descartaba.

Aquí no se añade una llamada a Garmin: se deja de tirar lo que ya venía.

## La disponibilidad es distinta

`readiness` sólo crece hacia delante. Garmin publica histórico de VO2max y de
la ratio de carga, pero NO de la puntuación de disponibilidad, así que la serie
arranca vacía y se llena un punto por refresco diario. La gráfica aparece sola
cuando haya dos días. Es honesto que tarde; la alternativa sería inventar el
pasado.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.charts import (  # noqa: E402
    readiness_trend,
    sleep_trend,
    stress_trend,
    weight_trend,
)
from services.ai.fitness_series import _recovery_points, _weight_points  # noqa: E402


class _Sueno:
    def __init__(self, fecha, horas=None, score=None, hrv=60.0, rhr=48, estres=None):
        self.date = fecha
        self.sleep = {
            "duration": {"total": horas},
            "quality": {"overall_score": score},
            "avg_overnight_hrv": hrv,
            "resting_heart_rate": rhr,
        }
        self.stress = {"avg_level": estres}


class _Garmin:
    def __init__(self, indicadores=None, peso=None):
        self.recovery_indicators = indicadores or []
        self.body_metrics = type("BM", (), {"weight": peso})()


class LaSerieDeRecuperacionYaTraiaMasDeLoQueSeUsaba(unittest.TestCase):
    def test_EL_FALLO_ahora_se_recogen_horas_de_sueno_y_estres(self):
        puntos = _recovery_points(_Garmin([
            _Sueno("2026-08-01", horas=7.5, score=82, estres=28),
            _Sueno("2026-08-02", horas=6.0, score=61, estres=44),
        ]))
        self.assertEqual([p["sleep_hours"] for p in puntos], [7.5, 6.0])
        self.assertEqual([p["stress"] for p in puntos], [28, 44])

    def test_un_dia_sin_esos_datos_no_rompe_ni_inventa_un_cero(self):
        """Un hueco es información distinta de un cero: cero horas de sueño
        significaría que no durmió."""
        puntos = _recovery_points(_Garmin([_Sueno("2026-08-01")]))
        self.assertIsNone(puntos[0]["sleep_hours"])
        self.assertIsNone(puntos[0]["stress"])

    def test_sigue_exigiendo_hrv_o_pulso_para_dar_el_dia_por_bueno(self):
        """Un día sin ninguno de los dos es un día que el reloj no midió."""
        puntos = _recovery_points(_Garmin([_Sueno("2026-08-01", estres=30, hrv=None, rhr=None)]))
        self.assertEqual(puntos, [])


class ElPesoSaleDeLoQueYaSeDescargaba(unittest.TestCase):
    """Estas pruebas fijaban una suposición EQUIVOCADA, y por eso pasaban.

    Afirmaban que `BodyMetrics.weight` es el payload crudo de Garmin -- clave
    `dateWeightList`, peso en gramos -- y comprobaban que `_weight_points`
    dividiera entre mil. Se escribieron sin mirar un payload real, y el código
    y la prueba compartían el mismo error, así que se confirmaban entre ellos.

    El payload real, del prompt archivado de la generación del 2026-09-03:

        "weight": {"data": [{"date": "2026-08-05", "weight": 65,
                             "source": "MANUAL"}], "average": 65.5}

    `get_body_metrics` YA lee `dateWeightList` y YA convierte los gramos. Con
    el código de ayer, 65 kg se volvían 0,065 y la guarda de cordura los
    tiraba: la serie salía vacía y parecía que el atleta no se pesaba.

    Lo que sobrevive de la idea original -- que la conversión sea correcta y
    que una pesada absurda no tuerza la tendencia -- está en
    `test_peso_y_cuaderno_garantizado.py`, escrito contra el payload de verdad.
    Aquí queda sólo lo que no dependía de la forma del dato.
    """

    def test_una_cuenta_sin_bascula_no_rompe_nada(self):
        for vacio in (None, {}, {"data": []}):
            with self.subTest(valor=vacio):
                self.assertEqual(_weight_points(_Garmin(peso=vacio)), [])

    def test_sin_fecha_no_se_inventa_el_dia_de_hoy(self):
        """Una pesada con fecha equivocada mueve una tendencia entera."""
        self.assertEqual(_weight_points(_Garmin(peso={"data": [{"weight": 72}]})), [])


class LasGraficasNoDibujanLoQueNoPueden(unittest.TestCase):
    """Un eje con un punto no es una tendencia y ocupa el sitio de algo que sí
    informa. Es la misma regla que el resto de charts.py."""

    def test_con_un_solo_punto_no_hay_grafica(self):
        uno = [{"date": "2026-08-01", "sleep_hours": 7.0, "stress": 30}]
        self.assertEqual(sleep_trend(uno), {})
        self.assertEqual(stress_trend(uno), {})
        self.assertEqual(weight_trend([{"date": "2026-08-01", "value": 70.0}]), {})
        self.assertEqual(readiness_trend([{"date": "2026-08-01", "value": 80}]), {})

    def test_sin_datos_devuelven_vacio_y_no_petan(self):
        for fn in (sleep_trend, stress_trend, weight_trend, readiness_trend):
            with self.subTest(fn=fn.__name__):
                self.assertEqual(fn([]), {})


class LoQueDicenLasGraficasCuandoSiHayDatos(unittest.TestCase):
    def setUp(self):
        self.rec = [
            {"date": f"2026-08-{d:02d}", "sleep_hours": 7.0 + (d % 3) * 0.5,
             "sleep_score": 70 + d, "stress": 30 + d}
            for d in range(1, 11)
        ]

    def test_el_sueno_trae_horas_y_puntuacion_por_separado(self):
        s = sleep_trend(self.rec)
        self.assertIn("hours", s)
        self.assertIn("score", s)
        self.assertEqual(s["hours"]["unit"], "h")
        self.assertEqual(s["hours"]["days"], 10)

    def test_se_da_la_media_de_los_ULTIMOS_7_y_no_solo_el_dato_suelto(self):
        """Una noche de 5 h no dice nada; siete seguidas sí.

        Se comprueba el CÁLCULO, no que salga distinto del último valor: la
        primera versión de esta prueba afirmaba `avg7 != current` y falló
        porque con los datos de ejemplo coincidían. Eso no era un fallo del
        código — era una prueba que comprobaba una casualidad.
        """
        s = sleep_trend(self.rec)
        ultimos7 = [p["sleep_hours"] for p in self.rec[-7:]]
        self.assertEqual(s["hours"]["avg7"], round(sum(ultimos7) / 7, 1))
        self.assertEqual(s["hours"]["current"], self.rec[-1]["sleep_hours"])

    def test_con_menos_de_siete_dias_la_media_usa_los_que_hay(self):
        """No se rellena con ceros ni se calla: tres noches son tres noches."""
        tres = self.rec[:3]
        s = sleep_trend(tres)
        esperado = round(sum(p["sleep_hours"] for p in tres) / 3, 1)
        self.assertEqual(s["hours"]["avg7"], esperado)

    def test_el_estres_lleva_los_tramos_de_GARMIN_no_unos_nuestros(self):
        e = stress_trend(self.rec)
        self.assertEqual([b["label"] for b in e["bands"]], ["reposo", "bajo", "medio", "alto"])
        self.assertIn("los que usa Garmin", e["note"])

    def test_el_peso_no_marca_ningun_objetivo(self):
        """Ponerle a un atleta un peso ideal sería inventarle un criterio
        clínico. Misma regla que el ACWR: describir, no juzgar."""
        w = weight_trend([{"date": f"2026-08-{d:02d}", "value": 72.0 - d * 0.1} for d in range(1, 11)])
        self.assertNotIn("objetivo", str(w.get("goal", "")))
        self.assertIn("No se marca ningún peso objetivo", w["note"])
        self.assertLess(w["change"], 0)

    def test_el_sueno_no_inventa_un_minimo_recomendado(self):
        self.assertIn("No se marca un mínimo", sleep_trend(self.rec)["note"])

    def test_la_disponibilidad_avisa_de_que_solo_crece_hacia_delante(self):
        r = readiness_trend([{"date": f"2026-08-{d:02d}", "value": 70 + d} for d in range(1, 5)])
        self.assertIn("sólo crece hacia delante", r["note"])


class LaCintaLlegaAlPlanificador(unittest.TestCase):
    """El atleta declara si tiene cinta, el dato viajaba hasta
    `build_custom_planning_context` y ahí se leía y se tiraba: la línea existía
    sin un `if` debajo. Lo cazó el linter nuevo (F841), no una prueba.

    Importa porque cambia lo que se puede prescribir: con cinta, una serie de
    ritmo controlado se salva de la lluvia y de la noche.
    """

    def _contexto(self, **perfil):
        from web_app.server import build_custom_planning_context
        base = {"sports_preference": "run", "available_hours": 6}
        base.update(perfil)
        return build_custom_planning_context(base)

    def test_EL_FALLO_con_cinta_el_planificador_se_entera(self):
        self.assertIn("treadmill", self._contexto(treadmill_access=True).lower())

    def test_sin_cinta_no_se_dice_nada(self):
        self.assertNotIn("treadmill", self._contexto(treadmill_access=False).lower())


if __name__ == "__main__":
    unittest.main()
