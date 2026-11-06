"""v7 -- el gasto sobrevive a que el atleta borre sus datos.

Dos cosas rompían la contabilidad del producto, y las dos eran reales:

1. **La corrida siguiente pisaba el costo de la anterior.** El plan de
   temporada del 2026-08-26 costó $0.2077; la semana lo sobrescribió media
   hora después en el mismo `summary.json`. El atleta preguntó por qué veía
   el costo de la semana y no el del plan. Porque ya no estaba.

2. **Borrar el plan borraba la cuenta.** `delete_current_generation` y
   `DELETE /api/profiles/{name}` hacen `rmtree` sobre `data/storage/<perfil>/`.
   Anthropic, OpenAI y Google ya cobraron; la factura llega igual.

La propiedad que estos tests fijan es una sola, mirada desde varios ángulos:
**nada de lo que haga el atleta con sus datos puede reducir lo que ya se
gastó.** Por eso el libro vive fuera de ese árbol y sólo crece.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import shutil
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core import usage_ledger  # noqa: E402

# El desglose real de la semana del 2026-08-26 15:06, recortado.
UNA_SEMANA = {
    "total_cost_usd": 0.16669175,
    "total_tokens": 69542,
    "agents": [
        {"name": "activity_summarizer", "cost_usd": 0.0058605, "tokens": 16282,
         "input_tokens": 14850, "output_tokens": 1432,
         "models": [{"name": "gemini-3.1-flash-lite"}]},
        {"name": "weekly_planner", "cost_usd": 0.1494555, "tokens": 31567,
         "input_tokens": 17953, "output_tokens": 13614,
         "models": [{"name": "gemini-3.5-flash"}]},
    ],
}

UNA_TEMPORADA = {"total_cost_usd": 0.2076855, "total_tokens": 455272, "agents": []}


class LedgerTestCase(unittest.TestCase):
    """Cada test contra su propio libro en un directorio temporal -- estos
    tests escriben, y el libro de verdad es append-only a propósito."""

    def setUp(self):
        self._dir = Path(tempfile.mkdtemp())
        patcher = mock.patch.object(usage_ledger, "LEDGER_PATH", self._dir / "usage.jsonl")
        patcher.start()
        self.addCleanup(patcher.stop)
        self.addCleanup(shutil.rmtree, self._dir, True)


class NadaDeLoQueHagaElAtletaBorraLoGastado(LedgerTestCase):
    def test_el_libro_no_vive_dentro_del_arbol_que_se_borra(self):
        """La propiedad estructural de la que dependen todas las demás. Si
        alguien mueve el libro a data/storage/, todo lo de abajo pasa igual y
        el dinero se pierde igual."""
        with mock.patch.object(usage_ledger, "LEDGER_PATH", usage_ledger.LEDGER_PATH):
            pass
        real = Path(usage_ledger.__file__).resolve().parents[1] / "data" / "billing" / "usage.jsonl"
        borrable = Path(usage_ledger.__file__).resolve().parents[1] / "data" / "storage"
        self.assertFalse(str(real).startswith(str(borrable)))

    def test_borrar_el_perfil_deja_el_gasto_en_pie(self):
        usage_ledger.record_run("Ana", usage_ledger.KIND_SEASON, UNA_TEMPORADA)
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        antes = usage_ledger.usage_for_profile("Ana")["total_cost_usd"]

        usage_ledger.record_profile_deleted("Ana")

        despues = usage_ledger.usage_for_profile("Ana")
        self.assertAlmostEqual(despues["total_cost_usd"], antes, places=6)
        self.assertTrue(despues["deleted"])

    def test_el_perfil_borrado_sigue_en_el_informe_del_operador(self):
        """Si desapareciera de la lista, el total del mes bajaría solo -- que
        es justo la mentira que este módulo existe para impedir."""
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        usage_ledger.record_profile_deleted("Ana")

        admin = usage_ledger.usage_by_profile()
        fila = next(r for r in admin["profiles"] if r["profile"] == "Ana")
        self.assertTrue(fila["deleted"])
        self.assertAlmostEqual(admin["total_cost_usd"], UNA_SEMANA["total_cost_usd"], places=6)

    def test_el_evento_de_borrado_no_cuenta_como_gasto(self):
        usage_ledger.record_profile_deleted("Ana")
        uso = usage_ledger.usage_for_profile("Ana")
        self.assertEqual(uso["runs"], 0)
        self.assertEqual(uso["total_cost_usd"], 0.0)


class LaCorridaSiguienteYaNoPisaLaAnterior(LedgerTestCase):
    def test_temporada_y_semana_conviven(self):
        """El caso literal que el atleta reportó: generó la temporada, generó
        la semana, y el costo de la temporada desapareció de la vista."""
        usage_ledger.record_run("Ana", usage_ledger.KIND_SEASON, UNA_TEMPORADA)
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)

        uso = usage_ledger.usage_for_profile("Ana")
        self.assertEqual(uso["runs"], 2)
        self.assertAlmostEqual(
            uso["by_kind"][usage_ledger.KIND_SEASON]["cost_usd"], 0.207686, places=5
        )
        self.assertAlmostEqual(
            uso["by_kind"][usage_ledger.KIND_WEEK]["cost_usd"], 0.166692, places=5
        )

    def test_los_tokens_se_guardan_ademas_del_costo(self):
        """El costo es una multiplicación nuestra contra una tabla de precios
        que mantenemos a mano; los tokens son un hecho medido. Con los tokens
        guardados el pasado se puede recalcular cuando un proveedor cambie de
        precio. Sin ellos, el error queda congelado."""
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        linea = next(usage_ledger.read_all())
        self.assertEqual(linea["tokens"], 69542)
        self.assertEqual(linea["input_tokens"], 14850 + 17953)
        self.assertEqual(linea["output_tokens"], 1432 + 13614)


class UnaPersonaUnaClave(LedgerTestCase):
    """El nombre visible y el de la carpeta son el mismo atleta. Sin
    normalizar, el libro le cobra a dos personas lo que gastó una -- la misma
    trampa que `_profile_storage_dir` documenta haber pisado en producción."""

    def test_el_nombre_visible_y_el_de_carpeta_son_el_mismo_atleta(self):
        usage_ledger.record_run("Jose Ines Castillo", usage_ledger.KIND_WEEK, UNA_SEMANA)
        usage_ledger.record_run("Jose_Ines_Castillo", usage_ledger.KIND_SEASON, UNA_TEMPORADA)

        admin = usage_ledger.usage_by_profile()
        self.assertEqual(len(admin["profiles"]), 1)
        self.assertEqual(admin["profiles"][0]["runs"], 2)

    def test_se_consulta_con_cualquiera_de_los_dos(self):
        usage_ledger.record_run("Jose Ines Castillo", usage_ledger.KIND_WEEK, UNA_SEMANA)
        for escrito_como in ("Jose Ines Castillo", "Jose_Ines_Castillo"):
            with self.subTest(nombre=escrito_como):
                self.assertEqual(usage_ledger.usage_for_profile(escrito_como)["runs"], 1)

    def test_el_informe_muestra_el_nombre_legible(self):
        usage_ledger.record_run("Jose Ines Castillo", usage_ledger.KIND_WEEK, UNA_SEMANA)
        self.assertEqual(usage_ledger.usage_by_profile()["profiles"][0]["profile"], "Jose Ines Castillo")


class ElAtletaNoPagaElDesarrollo(LedgerTestCase):
    """El banco de pruebas replica prompts archivados del atleta para decidir
    qué modelo usa el producto. Se gastó contra sus datos y no es su
    entrenamiento: medido sobre el libro real, $0.6862 de banco contra $0.7063
    de entrenamiento -- casi le duplicaba la cifra."""

    def test_el_banco_no_aparece_en_su_vista(self):
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        usage_ledger.record_run("Ana", usage_ledger.KIND_REPLAY, {"total_cost_usd": 0.2266, "agents": []})

        suyo = usage_ledger.usage_for_profile("Ana")
        self.assertEqual(suyo["runs"], 1)
        self.assertNotIn(usage_ledger.KIND_REPLAY, suyo["by_kind"])

    def test_pero_el_operador_lo_sigue_pagando(self):
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        usage_ledger.record_run("Ana", usage_ledger.KIND_REPLAY, {"total_cost_usd": 0.2266, "agents": []})

        completo = usage_ledger.usage_for_profile("Ana", include_operator_only=True)
        self.assertEqual(completo["runs"], 2)
        self.assertAlmostEqual(
            usage_ledger.usage_by_profile()["total_cost_usd"],
            UNA_SEMANA["total_cost_usd"] + 0.2266,
            places=6,
        )


class ElLibroAguantaLoQueLePase(LedgerTestCase):
    def test_una_linea_corrupta_no_se_lleva_la_contabilidad(self):
        """Un byte malo a mitad del archivo no puede costar el resto del
        historial, porque no hay forma de recuperarlo desde otro lado."""
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        with open(usage_ledger.LEDGER_PATH, "a", encoding="utf-8") as handle:
            handle.write("{esto no es json\n")
        usage_ledger.record_run("Ana", usage_ledger.KIND_SEASON, UNA_TEMPORADA)

        self.assertEqual(usage_ledger.usage_for_profile("Ana")["runs"], 2)

    def test_una_corrida_que_no_llamo_a_ningun_modelo_se_anota_igual(self):
        """Anotar el cero deja el intento en el historial; no anotarlo hace
        que un fallo caro de depurar sea además invisible."""
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, None)
        self.assertEqual(usage_ledger.usage_for_profile("Ana")["runs"], 1)

    def test_no_poder_escribir_no_tumba_la_generacion(self):
        """El atleta ya pagó la corrida. Perder la anotación es malo; perder
        el plan porque no se pudo anotar es peor."""
        with mock.patch("builtins.open", side_effect=OSError("disco lleno")):
            with self.assertLogs("core.usage_ledger", level="ERROR"):
                usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)

    def test_el_libro_solo_crece(self):
        for _ in range(3):
            usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        lineas = usage_ledger.LEDGER_PATH.read_text(encoding="utf-8").strip().split("\n")
        self.assertEqual(len(lineas), 3)
        self.assertTrue(all(json.loads(linea)["profile"] == "Ana" for linea in lineas))


class ElGastoSeAgrupaPorMes(LedgerTestCase):
    def test_la_fecha_es_cuando_se_gasto_no_cuando_se_anoto(self):
        """El sembrado histórico anota hoy corridas de meses pasados. Con la
        fecha de anotación, todo el histórico caería en el mes en curso y el
        informe mensual sería inservible desde el primer día."""
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA, ts="2026-06-15T10:00:00")
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA, ts="2026-08-26T15:06:00")

        meses = usage_ledger.usage_for_profile("Ana")["by_month"]
        self.assertEqual(sorted(meses), ["2026-06", "2026-08"])


if __name__ == "__main__":
    unittest.main(verbosity=2)


class ElLibroSabeDeQuienEsElGasto(unittest.TestCase):
    """Cobrar por nombre de perfil es cobrarle a quien no toca.

    Dos atletas pueden llamarse igual y uno puede cambiarse el nombre. La
    columna `user_id` existia desde v7 con un comentario que decia "reservado
    para cuando exista autenticacion"; con siete cuentas en la beta, ese momento
    llego.

    `record_run` se llama desde dentro de las tareas de generacion, que reciben
    un perfil y no una sesion, asi que el puente instala un resolvedor -- igual
    que ya reemplaza el directorio de almacenamiento.
    """

    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self._tmp.cleanup)
        self._original = usage_ledger.LEDGER_PATH
        usage_ledger.LEDGER_PATH = Path(self._tmp.name) / "usage.jsonl"
        self.addCleanup(lambda: setattr(usage_ledger, "LEDGER_PATH", self._original))
        self.addCleanup(lambda: setattr(usage_ledger, "USER_ID_RESOLVER", None))
        usage_ledger.USER_ID_RESOLVER = None

    def test_el_resolvedor_pone_el_usuario_cuando_el_llamador_no_lo_sabe(self):
        usage_ledger.USER_ID_RESOLVER = lambda: "u42"
        linea = usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        self.assertEqual(linea["user_id"], "u42")

    def test_un_user_id_explicito_gana_al_resolvedor(self):
        usage_ledger.USER_ID_RESOLVER = lambda: "u42"
        linea = usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA, user_id="u7")
        self.assertEqual(linea["user_id"], "u7")

    def test_sin_resolvedor_todo_sigue_como_antes(self):
        linea = usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        self.assertIsNone(linea["user_id"])

    def test_un_resolvedor_que_revienta_no_tumba_la_generacion(self):
        """El cobro no puede costar una semana ya pagada al proveedor."""
        def explota():
            raise RuntimeError("sin contexto")
        usage_ledger.USER_ID_RESOLVER = explota
        linea = usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        self.assertIsNone(linea["user_id"])

    def test_dos_atletas_con_el_MISMO_nombre_no_comparten_factura(self):
        usage_ledger.USER_ID_RESOLVER = lambda: "u1"
        usage_ledger.record_run("Jose Castillo", usage_ledger.KIND_WEEK, UNA_SEMANA)
        usage_ledger.USER_ID_RESOLVER = lambda: "u2"
        usage_ledger.record_run("Jose Castillo", usage_ledger.KIND_SEASON, UNA_TEMPORADA)

        uno = usage_ledger.usage_for_user("u1")
        dos = usage_ledger.usage_for_user("u2")
        self.assertEqual(uno["runs"], 1)
        self.assertEqual(dos["runs"], 1)
        self.assertNotEqual(uno["total_cost_usd"], dos["total_cost_usd"])
        # Y por nombre siguen sumando juntos, que es justo el fallo.
        self.assertEqual(usage_ledger.usage_for_profile("Jose Castillo")["runs"], 2)

    def test_el_gasto_sin_usuario_no_se_le_cuelga_a_nadie(self):
        """Preferimos cobrar de menos a cobrarselo al equivocado."""
        usage_ledger.record_run("Ana", usage_ledger.KIND_WEEK, UNA_SEMANA)
        self.assertEqual(usage_ledger.usage_for_user("u1")["runs"], 0)
        self.assertEqual(usage_ledger.usage_for_user("")["runs"], 0)

    def test_borrar_el_plan_no_borra_lo_que_ya_costo(self):
        """La factura del proveedor llega igual."""
        usage_ledger.USER_ID_RESOLVER = lambda: "u9"
        usage_ledger.record_run("Ana", usage_ledger.KIND_SEASON, UNA_TEMPORADA)
        antes = usage_ledger.usage_for_user("u9")["total_cost_usd"]
        usage_ledger.record_profile_deleted("Ana", user_id="u9")
        self.assertEqual(usage_ledger.usage_for_user("u9")["total_cost_usd"], antes)
        self.assertGreater(antes, 0)
