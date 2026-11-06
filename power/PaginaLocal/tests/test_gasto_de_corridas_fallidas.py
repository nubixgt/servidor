"""Una generación que falla cuesta dinero, y el libro tiene que decirlo.

## El fallo, con los números

El 2026-09-10 se lanzaron CINCO corridas del atleta 2 y el libro guardó TRES.
Las dos semanas que murieron a mitad no dejaron ni una línea, aunque su propio
`worker.log` dijera exactamente lo que habían costado:

    Week generation workflow complete: $0.1153 (76768 tokens, 8 node calls)
    ERROR - La semana no se pudo generar.

La causa era de POSICIÓN, no de lógica: `usage_ledger.record_run` vivía unas
170 líneas por debajo del `raise` que corta una semana sin plan usable, así que
sólo se llegaba a él por el camino feliz. `cost_summary` ni siquiera se leía
antes de ese `raise`.

## Por qué importa más de lo que parece

Porque el panel del administrador miente **a la baja justo donde se decide el
MVP**. Lo que la v1.1 salió a medir es el bucle de regeneración — 37
generaciones para 5 atletas en cinco días — y los intentos fallidos son el
corazón de ese bucle: cuestan dinero de verdad y son los que más se repiten,
porque a un atleta al que le falla la generación la vuelve a lanzar. Contarlos
fuera convierte el total en un suelo disfrazado de total.

`record_run` ya estaba escrito para esto: su docstring dice que acepta un
resumen vacío porque "anotar ese cero es más útil que no anotar nada". Sólo
faltaba llamarlo desde donde se puede.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core import usage_ledger  # noqa: E402
from web_app.server import anotar_gasto  # noqa: E402

SERVER_PY = Path(__file__).resolve().parents[1] / "web_app" / "server.py"

# Lo que devolvió el flujo de la semana que murió el 2026-09-10: coste real,
# tokens reales, y ningún plan.
FALLIDA = {
    "cost_summary": {
        "total_cost_usd": 0.1153,
        "total_tokens": 76768,
        "agents": [{"name": "workout", "cost_usd": 0.1153, "tokens": 76768,
                    "input_tokens": 70000, "output_tokens": 6768,
                    "models": ["gpt-5.2"]}],
    },
    "weekly_plan": None,
    "errors": [],
}


class GastoDeCorridasFallidas(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self._antes = usage_ledger.LEDGER_PATH
        usage_ledger.LEDGER_PATH = Path(self._tmp.name) / "usage.jsonl"
        self.addCleanup(self._tmp.cleanup)
        self.addCleanup(lambda: setattr(usage_ledger, "LEDGER_PATH", self._antes))

    def _lineas(self):
        if not usage_ledger.LEDGER_PATH.exists():
            return []
        return [json.loads(x) for x in
                usage_ledger.LEDGER_PATH.read_text(encoding="utf-8").splitlines() if x.strip()]

    def test_una_semana_sin_plan_deja_su_linea(self):
        """La garantía entera."""
        anotar_gasto({"name": "Jose Ines Castillo"}, usage_ledger.KIND_WEEK, FALLIDA)
        lineas = self._lineas()
        self.assertEqual(len(lineas), 1)
        self.assertAlmostEqual(lineas[0]["cost_usd"], 0.1153, places=6)
        self.assertEqual(lineas[0]["tokens"], 76768)
        self.assertEqual(lineas[0]["kind"], usage_ledger.KIND_WEEK)

    def test_una_corrida_que_murio_antes_de_llamar_a_nadie_anota_cero(self):
        """Cero también es un dato: deja el intento en el historial, que es lo
        que hace visible el bucle de regeneración."""
        anotar_gasto({"name": "Ana"}, usage_ledger.KIND_SEASON, {})
        lineas = self._lineas()
        self.assertEqual(len(lineas), 1)
        self.assertEqual(lineas[0]["cost_usd"], 0.0)

    def test_no_revienta_si_el_resultado_es_basura(self):
        """Va DELANTE del trabajo que le importa al atleta: una generación
        buena no se puede perder porque falle la contabilidad."""
        for basura in (None, {"cost_summary": None}, {"cost_summary": {"total_cost_usd": "x"}}):
            with self.subTest(basura=basura):
                anotar_gasto({"name": "Ana"}, usage_ledger.KIND_WEEK, basura)

    def test_el_gasto_suma_en_el_informe_del_atleta(self):
        """Que la línea exista no basta: tiene que llegar al panel."""
        anotar_gasto({"name": "Ana"}, usage_ledger.KIND_WEEK, FALLIDA)
        anotar_gasto({"name": "Ana"}, usage_ledger.KIND_WEEK, FALLIDA)
        informe = usage_ledger.usage_for_profile("Ana")
        self.assertAlmostEqual(informe["total_cost_usd"], 0.2306, places=4)
        self.assertEqual(informe["runs"], 2)

    def test_se_anota_UNA_vez_por_corrida(self):
        """El apunte se movió arriba; si alguien deja también el de abajo, cada
        semana buena se cobraría dos veces en el panel."""
        fuente = SERVER_PY.read_text(encoding="utf-8")
        # Sin contar la definicion: solo las dos llamadas.
        self.assertEqual(fuente.count("anotar_gasto(profile_dict, usage_ledger."), 2)
        # `record_run` sólo puede quedar DENTRO del ayudante y en el borrado de
        # perfil, en ningún camino de generación.
        self.assertEqual(fuente.count("usage_ledger.record_run("), 1)


class ElApunteVaAntesDelRaise(unittest.TestCase):
    """El fallo era de POSICIÓN. Fijarla es la única forma de que no vuelva."""

    def test_la_semana_se_anota_antes_de_poder_abortar(self):
        fuente = SERVER_PY.read_text(encoding="utf-8")
        anotacion = fuente.index("anotar_gasto(profile_dict, usage_ledger.KIND_WEEK")
        aborto = fuente.index('raise ValueError(f"No se pudo generar la semana')
        self.assertLess(anotacion, aborto)

    def test_la_temporada_se_anota_antes_de_escribir_nada(self):
        """Archivar, escribir ficheros y renderizar pueden fallar; el dinero ya
        se gastó de todas formas."""
        fuente = SERVER_PY.read_text(encoding="utf-8")
        anotacion = fuente.index("anotar_gasto(profile_dict, usage_ledger.KIND_SEASON")
        escritura = fuente.index('season_plan_path.write_text')
        self.assertLess(anotacion, escritura)


if __name__ == "__main__":
    unittest.main()
