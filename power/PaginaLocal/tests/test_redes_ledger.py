"""El libro de redes: cuántas veces corrige algo cada red determinista (Beta 3.0, F1).

La regla de 01 es que una red determinista es una red con fecha de caducidad: "se borra si
no corrige nada en dos semanas de beta". Hasta F1 esa cuenta no existía en ningún sitio que
durara: cada red avisaba en el `worker.log`, y el AiService borra los trabajos a los 3 días
o a los 30. `core/redes_ledger.py` lo guarda junto al libro de costos y el panel de
administración enseña "0 de N generaciones".

Dos cosas que, rotas, dan la señal equivocada para borrar una red:

- una red de `REDES` a la que nadie llama sale siempre "0 correcciones" y parece retirable;
- una corrección que no es tal se cuenta como trabajo de la red. El caso real es el de la
  prueba local del 2026-10-09 (commit e59636c): "~1 h 50 min a tope" con 6.600 s, que la red
  de duraciones leía como 50 min. Hoy no corrige nada, y no se anota nada.

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import json
import re
import sys
import tempfile
import unittest
from datetime import UTC, datetime
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core import redes_ledger  # noqa: E402
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation import plan_validator  # noqa: E402

RAIZ = Path(__file__).resolve().parents[1]


class _LibroTemporal(unittest.TestCase):
    """Cada prueba escribe en su propio libro, y lo deja como estaba."""

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self._ruta, self._activo = redes_ledger.REDES_PATH, redes_ledger.ACTIVO
        redes_ledger.REDES_PATH = Path(self._tmp.name) / "billing" / "redes.jsonl"

    def tearDown(self):
        redes_ledger.REDES_PATH, redes_ledger.ACTIVO = self._ruta, self._activo
        self._tmp.cleanup()

    def lineas(self):
        return list(redes_ledger.leer())


class AnotarYResumir(_LibroTemporal):

    def test_apagado_no_escribe(self):
        """Las pruebas llaman a las redes miles de veces: sólo el puente lo enciende."""
        redes_ledger.ACTIVO = False
        redes_ledger.anotar("ritmo_del_paso", "x")
        self.assertFalse(redes_ledger.REDES_PATH.exists())

    def test_encendido_anota_una_linea_por_correccion(self):
        redes_ledger.ACTIVO = True
        redes_ledger.anotar("ritmo_del_paso", "jueves: 4:51-5:00 → 5:12", user_id="u2")
        redes_ledger.anotar("ritmo_del_paso", "d" * 1000, user_id="u3")
        [a, b] = self.lineas()
        self.assertEqual((a["red"], a["user_id"], a["detalle"]), ("ritmo_del_paso", "u2", "jueves: 4:51-5:00 → 5:12"))
        self.assertEqual(len(b["detalle"]), 300)

    def test_nunca_lanza(self):
        """Medir no puede tumbar una generación pagada: un disco que no se deja escribir tampoco."""
        redes_ledger.ACTIVO = True
        bloqueo = Path(self._tmp.name) / "soy_un_archivo"
        bloqueo.write_text("x", encoding="utf-8")
        redes_ledger.REDES_PATH = bloqueo / "redes.jsonl"  # su "carpeta" es un archivo
        with self.assertLogs("core.redes_ledger", level="ERROR"):
            redes_ledger.anotar("ritmo_del_paso")

    def test_resumen_por_red_con_su_ventana(self):
        redes_ledger.ACTIVO = True
        redes_ledger.REDES_PATH.parent.mkdir(parents=True)
        lineas = [
            {"ts": "2026-10-09T23:40:00+00:00", "red": "duracion_del_paso", "user_id": "u2", "detalle": "a"},
            {"ts": "2026-10-10T08:00:00+00:00", "red": "duracion_del_paso", "user_id": "u3", "detalle": "b"},
            {"ts": "2026-10-10T09:00:00+00:00", "red": "duracion_del_paso", "user_id": "u3", "detalle": "c"},
            {"ts": "2026-08-01T09:00:00+00:00", "red": "ritmo_del_paso", "user_id": "u2", "detalle": "vieja"},
            {"ts": "2026-10-10T09:00:00+00:00", "red": "una_red_nueva", "user_id": None, "detalle": ""},
        ]
        redes_ledger.REDES_PATH.write_text("\n".join(json.dumps(x) for x in lineas) + "\n", encoding="utf-8")
        ahora = datetime(2026, 10, 11, 12, 0, tzinfo=UTC)
        filas = {f["red"]: f for f in redes_ledger.resumen(30, generaciones=12, ahora=ahora)}

        d = filas["duracion_del_paso"]
        self.assertEqual((d["correcciones"], d["atletas"], d["ultimo_detalle"]), (3, 2, "c"))
        self.assertFalse(d["sin_corregir_nada"])
        # Fuera de la ventana no cuenta: en 30 días el ritmo no corrigió nada en 12 generaciones.
        r = filas["ritmo_del_paso"]
        self.assertEqual((r["correcciones"], r["generaciones"], r["sin_corregir_nada"]), (0, 12, True))
        # Una red que no está en REDES no se pierde: sale al final, sin descripción.
        self.assertEqual(filas["una_red_nueva"]["correcciones"], 1)
        self.assertEqual(list(filas)[-1], "una_red_nueva")
        # Todas las de REDES salen, hayan corregido o no.
        self.assertTrue(set(redes_ledger.REDES) <= set(filas))

    def test_activar_apunta_desde_cuando_se_mide_una_sola_vez(self):
        """Sin esa fecha el panel decía "0 de 65 generaciones" con el libro empezado ese día."""
        self.assertIsNone(redes_ledger.midiendo_desde())
        redes_ledger.activar()
        redes_ledger.activar()  # el siguiente worker no la mueve
        redes_ledger.anotar("ritmo_del_paso")
        lineas = self.lineas()
        self.assertEqual([x["red"] for x in lineas], [redes_ledger.INICIO, "ritmo_del_paso"])
        self.assertEqual(redes_ledger.midiendo_desde().isoformat(timespec="seconds"), lineas[0]["ts"])
        # La marca no es una corrección.
        filas = {f["red"]: f for f in redes_ledger.resumen(30, generaciones=1)}
        self.assertNotIn(redes_ledger.INICIO, filas)
        self.assertEqual(filas["ritmo_del_paso"]["correcciones"], 1)

    def test_sin_generaciones_no_se_puede_decir_cero_de_n(self):
        filas = redes_ledger.resumen(30, generaciones=None)
        self.assertFalse(any(f["sin_corregir_nada"] for f in filas))


class CadaRedTieneQuienLaAnote(unittest.TestCase):

    def test_cada_red_de_la_lista_se_anota_en_algun_sitio_y_al_reves(self):
        """Una red en REDES que nadie anota sale "0 de N" para siempre y parece retirable."""
        fuentes = [
            RAIZ / "services/ai/langgraph/validation/plan_validator.py",
            RAIZ / "services/ai/langgraph/nodes/weekly_planner_node.py",
            RAIZ / "services/ai/langgraph/nodes/season_planner_node.py",
        ]
        texto = "\n".join(p.read_text(encoding="utf-8") for p in fuentes)
        directas = set(re.findall(r'redes_ledger\.anotar\(\s*"([a-z_]+)"', texto))
        # Las de _anotar_redes([("red", cambios), ...]).
        en_lista = set()
        for bloque in re.findall(r"_anotar_redes\(\[(.*?)\]\)", texto, re.S):
            en_lista |= set(re.findall(r'\(\s*"([a-z_]+)"\s*,', bloque))
        llamadas = directas | en_lista
        self.assertEqual(set(redes_ledger.REDES) - llamadas, set(), "en REDES sin quien la anote")
        self.assertEqual(llamadas - set(redes_ledger.REDES), set(), "se anota sin estar en REDES")


def _semana(texto, segundos):
    paso = WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=segundos,
                       description=texto, target_type="none")
    dia = WorkoutDay(date="2026-10-10", day_name_es="Sábado", is_rest=False, sport="run", focus="Rodaje",
                     purpose="p", adaptation="a", intensity_class="easy", discipline="carrera",
                     total_duration_minutes=segundos / 60, steps=[paso])
    return WeeklyPlanPayload(athlete_name="José", start_date="2026-10-05", zones_table="",
                             weeks=[WeekBlock(week_number=1, week_rationale="", days=[dia])])


class LoQueAnotaElValidador(unittest.TestCase):
    """Por el validador entero, como en una generación: sólo se anota lo que se corrigió."""

    def _redes_anotadas(self, payload):
        async def _sin_modelo(*_a, **_k):
            raise AssertionError("esta prueba no llama a ningún modelo")

        llm = mock.Mock(ainvoke=_sin_modelo)
        with mock.patch.object(redes_ledger, "anotar") as anotar:
            asyncio.run(plan_validator.validate_and_repair_weekly_plan(
                payload, {}, [{"date": "2026-10-10", "day_name_es": "Sábado"}], llm, max_repair_attempts=0))
        return [c.args[0] for c in anotar.call_args_list]

    def test_las_horas_bien_escritas_no_cuentan_como_correccion(self):
        """El trail de prueba del 2026-10-09: "~1 h 50 min a tope" con 6.600 s está bien."""
        redes = self._redes_anotadas(_semana(
            "Trail de prueba (local): 15 km, ~600 m D+, ~1 h 50 min a tope, gestionando por esfuerzo y terreno",
            6600))
        self.assertNotIn("duracion_del_paso", redes)

    def test_una_duracion_corregida_se_anota_una_vez(self):
        redes = self._redes_anotadas(_semana("2 h en Z2", 3000))
        self.assertEqual(redes.count("duracion_del_paso"), 1)


if __name__ == "__main__":
    unittest.main()
