"""Dos fallos que se descubrieron por no mirar el dato real.

## 1. El peso: la serie salía vacía y el atleta SÍ tenía pesajes

Se dio por bueno que la cuenta no tenía datos, porque `body_metrics` no
aparecía en el prompt del resumidor de MÉTRICAS. Estaba mirando el prompt
equivocado: `body_metrics` va al de FISIOLOGÍA, y ahí estaba, con los pesajes
reales del atleta:

    "body_metrics": {"weight": {"data": [
        {"date": "2026-08-05", "weight": 65, "source": "MANUAL"},
        {"date": "2026-07-13", "weight": 66, "source": "MANUAL"}
    ], "average": 65.5}}

`BodyMetrics.weight` NO es el payload crudo de Garmin: `get_body_metrics` ya lo
procesó, ya leyó `dateWeightList` y ya convirtió los gramos a kg. La primera
versión de `_weight_points` volvía a hacer las dos cosas -- buscaba
`dateWeightList` (que ya no existe ahí) y dividía 65 entre mil.

El resultado, 0,065 kg, lo descartaba el filtro de cordura. Por eso el fallo
fue SILENCIOSO: sin la guarda, la app habría dibujado que el atleta pesa 65
gramos; con ella, la serie salía vacía y parecía falta de datos.

## 2. El cuaderno: pedirlo tiene que bastar

Falló DOS veces seguidas. La primera se arregló pidiéndoselo al modelo en el
prompt; la segunda demostró que eso no basta -- el entrenador incluso le dijo
al atleta *"lo acabo de escribir en tu cuaderno"* mientras no se escribía nada.

Una petición explícita es de las pocas cosas que el código puede GARANTIZAR, y
lo que el código puede garantizar no se delega en un modelo.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.fitness_series import _weight_points  # noqa: E402
from services.ai.notebook import load_notebook, pidio_que_se_anotara  # noqa: E402


class _BodyMetrics:
    def __init__(self, weight):
        self.weight = weight


class _Garmin:
    def __init__(self, weight):
        self.body_metrics = _BodyMetrics(weight)


# Lo que Garmin devolvió de verdad para este atleta, tal cual salió del prompt
# archivado de la generación del 2026-09-03.
REAL = {"data": [
    {"date": "2026-08-05", "weight": 65, "source": "MANUAL"},
    {"date": "2026-07-13", "weight": 66, "source": "MANUAL"},
], "average": 65.5}


class ElPesoSaleEnKilosPorqueYaVieneEnKilos(unittest.TestCase):
    def test_EL_FALLO_los_pesajes_reales_del_atleta_producen_serie(self):
        self.assertEqual(
            _weight_points(_Garmin(REAL)),
            [{"date": "2026-07-13", "value": 66.0}, {"date": "2026-08-05", "value": 65.0}],
        )

    def test_NO_se_vuelve_a_dividir_entre_mil(self):
        """65 son 65 kg. Dividirlos daba 0,065 y la guarda de cordura los
        tiraba, dejando la serie vacía sin que nada avisara."""
        p = _weight_points(_Garmin(REAL))
        self.assertTrue(all(25 <= x["value"] <= 250 for x in p), p)

    def test_la_clave_es_data_y_no_dateWeightList(self):
        """`dateWeightList` es la del payload CRUDO de Garmin, que el extractor
        ya consumió. Aquí llega su salida procesada."""
        self.assertEqual(_weight_points(_Garmin({"dateWeightList": [
            {"calendarDate": "2026-08-05", "weight": 65000}]})), [])

    def test_la_guarda_de_cordura_sigue_puesta(self):
        """Es lo que convirtió un fallo de unidades en una serie vacía en vez
        de en una gráfica que decía que el atleta pesa 65 gramos."""
        p = _weight_points(_Garmin({"data": [
            {"date": "2026-08-05", "weight": 0.065},
            {"date": "2026-08-06", "weight": 65},
            {"date": "2026-08-07", "weight": 65000},
        ]}))
        self.assertEqual([x["value"] for x in p], [65.0])

    def test_una_cuenta_sin_pesajes_no_rompe_nada(self):
        for vacio in (None, {}, {"data": []}, {"data": None}):
            with self.subTest(valor=vacio):
                self.assertEqual(_weight_points(_Garmin(vacio)), [])


class PedirloBastaSinDependerDeUnModelo(unittest.TestCase):
    def test_EL_FALLO_reconoce_como_lo_pidio_el_atleta_de_verdad(self):
        """Sus palabras exactas, de la conversación del 2026-09-03."""
        self.assertTrue(pidio_que_se_anotara(
            "Si, está bien puedo hacerlo lento, pero necesito que lo escribas "
            "en mi cuaderno para la proxima generación"))

    def test_reconoce_las_otras_formas_de_pedirlo(self):
        for frase in ("anótalo", "anotalo por favor", "quiero que lo anotes",
                      "apúntalo", "apuntalo", "regístralo", "registralo",
                      "que quede registrado", "que no se te olvide",
                      "guárdalo en mi cuaderno"):
            with self.subTest(frase=frase):
                self.assertTrue(pidio_que_se_anotara(frase), frase)

    def test_las_tildes_hay_que_ponerlas_a_mano(self):
        """`\\w` incluye la í pero no la vuelve i: el primer patrón cazaba
        'registralo' y no 'regístralo', que es como se escribe bien."""
        self.assertTrue(pidio_que_se_anotara("regístralo"))
        self.assertTrue(pidio_que_se_anotara("anótalo"))
        self.assertTrue(pidio_que_se_anotara("apúntalo"))

    def test_NO_se_dispara_con_una_conversacion_normal(self):
        """Si saltara con cualquier mensaje, el cuaderno se volvería un volcado
        del chat -- que es el fallo opuesto y también real."""
        for frase in ("¿por qué el martes es suave?", "cómo voy de fatiga y carga",
                      "me siento bien", "hoy estoy cansado",
                      "¿cuántos km corrí el mes pasado?", "qué debo priorizar",
                      "explícame para qué sirve el VO2max"):
            with self.subTest(frase=frase):
                self.assertFalse(pidio_que_se_anotara(frase), frase)

    def test_un_mensaje_vacio_no_es_una_peticion(self):
        for v in (None, "", "   "):
            self.assertFalse(pidio_que_se_anotara(v))


class ElRespaldoEscribeAunqueElModeloNoDevuelvaNada(unittest.TestCase):
    """La garantía de verdad. El prompt pide, el respaldo cumple."""

    def setUp(self):
        self.dir = Path(self.enterContext(tempfile.TemporaryDirectory()))

    def _correr(self, mensaje, devuelve):
        """Ejecuta propose_entries con el modelo sustituido por uno que
        devuelve lo que se le diga."""
        import asyncio
        from unittest.mock import patch

        from services.ai import notebook as nb

        class _Extraccion:
            entries = devuelve

        class _Runnable:
            async def ainvoke(self, _):
                return _Extraccion()

        class _LLM:
            def with_structured_output(self, _):
                return _Runnable()

        with patch("services.ai.model_config.ModelSelector.get_llm", return_value=_LLM()):
            return asyncio.run(nb.propose_entries(self.dir, mensaje, "vale"))

    def test_EL_FALLO_si_lo_pidio_y_el_modelo_no_devuelve_nada_se_anota_igual(self):
        creadas, _ = self._correr("necesito que lo escribas en mi cuaderno", devuelve=[])
        self.assertEqual(len(creadas), 1)
        guardadas = load_notebook(self.dir)
        self.assertEqual(len(guardadas), 1)
        self.assertIn("Pidió que se anotara", guardadas[0]["text"])

    def test_el_respaldo_usa_SUS_palabras_no_una_interpretacion(self):
        """Aquí no hay modelo que resuma, así que inventarle una redacción
        sería peor que citarle."""
        mensaje = "anótalo: el domingo hago el long run en trail"
        creadas, _ = self._correr(mensaje, devuelve=[])
        self.assertIn(mensaje, creadas[0]["text"])
        self.assertEqual(creadas[0]["quote"], mensaje)

    def test_si_NO_lo_pidio_y_el_modelo_calla_no_se_anota_nada(self):
        """Lo normal sigue siendo no anotar. El respaldo no puede convertir el
        cuaderno en un volcado del chat."""
        creadas, _ = self._correr("¿por qué el martes es suave?", devuelve=[])
        self.assertEqual(creadas, [])
        self.assertEqual(load_notebook(self.dir), [])

    def test_si_el_modelo_SI_devuelve_algo_no_se_duplica_con_el_respaldo(self):
        from services.ai.notebook import NotebookProposal
        creadas, _ = self._correr(
            "anótalo por favor",
            devuelve=[NotebookProposal(text="Prefiere trail los domingos",
                                       category="preferencia", quote="", affects_training=True)],
        )
        self.assertEqual(len(creadas), 1)
        self.assertNotIn("Pidió que se anotara", creadas[0]["text"])


if __name__ == "__main__":
    unittest.main()
