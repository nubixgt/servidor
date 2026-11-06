"""Beta 2.1, paso S4: la rutina pegada se ordena y todo lo dudoso se le pregunta al atleta.

La fixture es el texto EXACTO que Fernando mandó por WhatsApp el 2026-09-17
(``fixtures/rutina_fernando.txt``) y la lectura REAL que hizo gemini-3.1-flash-lite
ese día con "cada línea es una superserie" y 120 s entre rondas
(``fixtures/rutina_fernando_lectura_flash_lite.json``, reconstruida del borrador
guardado de esa llamada). Tres lecturas medidas: 0,0039-0,0047 USD y ~10 s cada una;
las tres con 4 sesiones, 13 grupos, 33 ejercicios, 0 líneas sin leer, y preguntando
por la «R» de "4×15R" y por «Geelos». En dos de las tres, «Geelos» llegó igual con
STANDING_CALF_RAISE: el código se lo quita.

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import json
import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import rutina_de_fuerza as rf  # noqa: E402

FIXTURES = Path(__file__).parent / "fixtures"
TEXTO = (FIXTURES / "rutina_fernando.txt").read_text(encoding="utf-8")
LECTURA = rf.RutinaLeida.model_validate_json((FIXTURES / "rutina_fernando_lectura_flash_lite.json").read_text(encoding="utf-8"))


def _ejercicios(borrador):
    return [e for s in borrador["sessions"] for g in s["groups"] for e in g["exercises"]]


def _uno(borrador, nombre, sesion=None):
    return next(e for s in borrador["sessions"] if sesion is None or s["name"].startswith(sesion)
                for g in s["groups"] for e in g["exercises"] if e["name"] == nombre)


class LaRutinaDeFernando(unittest.TestCase):
    def setUp(self):
        self.borrador = rf.borrador_desde_lectura(LECTURA, TEXTO, 120, rf.AGRUPACION_POR_LINEA)

    def test_cuatro_sesiones_con_sus_superseries(self):
        sesiones = self.borrador["sessions"]
        self.assertEqual([s["minutes"] for s in sesiones], [120, 60, 120, 60])
        self.assertEqual([len(s["groups"]) for s in sesiones], [3, 3, 3, 4])
        self.assertEqual([[len(g["exercises"]) for g in s["groups"]] for s in sesiones],
                         [[3, 3, 3], [2, 2, 3], [3, 3, 3], [2, 2, 2, 2]])
        self.assertEqual(len(_ejercicios(self.borrador)), 9 + 7 + 9 + 8)

    def test_ninguna_linea_se_queda_sin_leer(self):
        self.assertEqual(self.borrador["unread_lines"], [])

    def test_series_y_repeticiones_como_estan_escritas(self):
        press = _uno(self.borrador, "Press de banca")
        self.assertEqual((press["sets"], press["reps"]), (4, "8"))
        self.assertEqual(_uno(self.borrador, "Crunch", "Upper A")["sets"], 3)
        self.assertEqual(_uno(self.borrador, "Predicador")["reps"], "10–12")

    def test_el_descanso_del_formulario_llega_a_cada_grupo(self):
        self.assertTrue(all(g["rest_between_rounds_s"] == 120 for s in self.borrador["sessions"] for g in s["groups"]))
        self.assertNotIn("No encontramos el descanso entre rondas", " ".join(self.borrador["doubts"]))

    def test_la_r_es_una_pregunta_y_no_ocho(self):
        dudas_r = [d for d in self.borrador["doubts"] + [d for e in _ejercicios(self.borrador) for d in e["doubts"]]
                   if "'R'" in d or "«R»" in d]
        self.assertEqual(len(dudas_r), 1)

    def test_geelos_se_pregunta_y_no_llega_al_reloj_como_otro_ejercicio(self):
        geelos = _uno(self.borrador, "Geelos")
        self.assertIsNone(geelos["garmin_category"])
        self.assertIsNone(geelos["garmin_exercise"])
        self.assertTrue(any("Geelos" in d for d in geelos["doubts"]))

    def test_la_duda_de_geelos_es_suya_y_no_se_pregunta_dos_veces(self):
        self.assertFalse(any("Geelos" in d for d in self.borrador["doubts"]))
        self.assertEqual(sum("Geelos" in d for e in _ejercicios(self.borrador) for d in e["doubts"]), 1)

    def test_las_dudas_sin_comillas_tambien_cuentan(self):
        """La cuarta lectura, desde la app (2026-09-17), escribió las dos dudas sin comillas:
        "En Lower B, ¿qué significa la R en las repeticiones (ej. 15R)?" y "¿Qué ejercicio es
        Geelos?". La «R» salió preguntada dos veces y «Geelos» no se enganchó a su ejercicio."""
        lectura = LECTURA.model_copy(deep=True)
        lectura.general_doubts = ["En Lower B, ¿qué significa la R en las repeticiones (ej. 15R)?",
                                  "¿Qué ejercicio es Geelos?"]
        borrador = rf.borrador_desde_lectura(lectura, TEXTO, 120, rf.AGRUPACION_POR_LINEA)
        todas = borrador["doubts"] + [d for e in _ejercicios(borrador) for d in e["doubts"]]
        self.assertEqual(sum(bool(re.search(r"(?<![A-Za-z])R(?![A-Za-z])", d)) for d in todas), 1)
        geelos = _uno(borrador, "Geelos")
        self.assertIsNone(geelos["garmin_exercise"])
        self.assertEqual(geelos["doubts"], ["¿Qué ejercicio es Geelos?"])

    def test_los_nombres_de_garmin_son_verificados(self):
        self.assertEqual(_uno(self.borrador, "Hack squat")["garmin_exercise"], "BARBELL_HACK_SQUAT")
        self.assertEqual(_uno(self.borrador, "Jalón al pecho")["garmin_exercise"], "LAT_PULLDOWN")
        # Garmin no tiene extensión de cuádriceps de máquina (S2): sin nombre, no con el de otro.
        self.assertIsNone(_uno(self.borrador, "Extensión de cuádriceps")["garmin_exercise"])

    def test_ids_estables_y_unicos(self):
        ids = [s["id"] for s in self.borrador["sessions"]]
        ids += [g["id"] for s in self.borrador["sessions"] for g in s["groups"]]
        ids += [e["id"] for e in _ejercicios(self.borrador)]
        self.assertEqual(len(ids), len(set(ids)))
        otra_vez = rf.borrador_desde_lectura(LECTURA, TEXTO, 120, rf.AGRUPACION_POR_LINEA)
        self.assertEqual([e["id"] for e in _ejercicios(otra_vez)], [e["id"] for e in _ejercicios(self.borrador)])

    def test_no_se_confirma_sola(self):
        self.assertIsNone(self.borrador["confirmed_at"])
        self.assertEqual(self.borrador["original_text"], TEXTO)


class LoQueElCodigoNoDaPorBueno(unittest.TestCase):
    def _lectura(self, **ejercicio):
        base = {"name": "Sentadilla", "sets": 4, "reps": "6", "line": 2}
        base.update(ejercicio)
        return rf.RutinaLeida(sessions=[rf.SesionLeida(name="Pierna", line=1, groups=[
            rf.GrupoLeido(exercises=[rf.EjercicioLeido(**base)])])])

    def test_un_nombre_que_no_esta_en_su_linea(self):
        borrador = rf.borrador_desde_lectura(self._lectura(name="Sentadilla búlgara"), "Pierna\nSentadilla 4x6", 90)
        self.assertTrue(any("no está escrito así" in d for d in _ejercicios(borrador)[0]["doubts"]))

    def test_unas_series_que_no_estan_en_su_linea(self):
        borrador = rf.borrador_desde_lectura(self._lectura(sets=5), "Pierna\nSentadilla 4x6", 90)
        self.assertTrue(any("5 series" in d for d in _ejercicios(borrador)[0]["doubts"]))

    def test_una_linea_que_nadie_uso(self):
        borrador = rf.borrador_desde_lectura(self._lectura(), "Pierna\nSentadilla 4x6\nPlancha 3x40s", 90)
        self.assertEqual([x["line"] for x in borrador["unread_lines"]], [3])

    def test_sin_descanso_se_dice(self):
        borrador = rf.borrador_desde_lectura(self._lectura(), "Pierna\nSentadilla 4x6", None)
        self.assertTrue(any("descanso entre rondas" in d for d in borrador["doubts"]))

    def test_con_lo_dice_el_texto_una_agrupacion_sin_marca_se_pregunta(self):
        """flash-lite juntó cada línea de Fernando sin que el texto lo dijera."""
        borrador = rf.borrador_desde_lectura(LECTURA, TEXTO, 120, rf.AGRUPACION_LO_DICE_EL_TEXTO)
        self.assertTrue(any("el texto no lo dice" in d for d in borrador["doubts"]))
        con_marca = rf.borrador_desde_lectura(LECTURA, TEXTO.replace(" - ", " + "), 120, rf.AGRUPACION_LO_DICE_EL_TEXTO)
        self.assertFalse(any("el texto no lo dice" in d for d in con_marca["doubts"]))

    def test_por_linea_no_pregunta_lo_que_el_atleta_ya_dijo(self):
        borrador = rf.borrador_desde_lectura(LECTURA, TEXTO, 120, rf.AGRUPACION_POR_LINEA)
        self.assertFalse(any("el texto no lo dice" in d for d in borrador["doubts"]))


class LaLlamada(unittest.TestCase):
    def test_el_modelo_recibe_las_lineas_numeradas_y_la_regla_de_agrupacion(self):
        recibido = {}

        async def leer(sistema, usuario):
            recibido.update(sistema=sistema, usuario=usuario)
            return LECTURA, {"gemini-3.1-flash-lite": {"input_tokens": 1480, "output_tokens": 2350}}

        borrador, uso = asyncio.run(rf.ordenar_rutina(TEXTO, rf.AGRUPACION_POR_LINEA, 120, leer=leer))
        self.assertIn("L20: Geelos (4x12R) - Crunch (4×15R)", recibido["usuario"])
        self.assertIn("MISMA línea", recibido["sistema"])
        self.assertIn("LAT_PULLDOWN", recibido["sistema"])
        self.assertEqual(len(borrador["sessions"]), 4)
        self.assertIn("gemini-3.1-flash-lite", uso)

    def test_un_texto_demasiado_largo_se_dice_no_se_recorta(self):
        async def leer(*_):
            raise AssertionError("no debería llamar al modelo")
        with self.assertRaises(rf.RutinaDemasiadoLarga):
            asyncio.run(rf.ordenar_rutina("Press 4x8\n" * 200, leer=leer))

    def test_texto_vacio(self):
        with self.assertRaises(ValueError):
            asyncio.run(rf.ordenar_rutina("  \n "))

    def test_el_rol_es_el_barato_en_todos_los_tiers(self):
        from services.ai.ai_settings import AgentRole, AIMode, AISettings
        for modo in AIMode:
            with self.subTest(modo=modo):
                self.assertEqual(AISettings(mode=modo).get_model_for_role(AgentRole.STRENGTH_ROUTINE_READER),
                                 "gemini-3.1-flash-lite")


class LaFixtureEsLaReal(unittest.TestCase):
    def test_el_texto_es_el_de_whatsapp(self):
        self.assertTrue(TEXTO.startswith("Upper A — 120 min\nPress de banca 4×8 - Elevaciones laterales — 4×12"))
        self.assertIn("Geelos (4x12R) - Crunch (4×15R)", TEXTO)
        self.assertEqual(json.loads((FIXTURES / "rutina_fernando_lectura_flash_lite.json").read_text(encoding="utf-8"))
                         ["sessions"][3]["groups"][3]["exercises"][0]["garmin_exercise"], "STANDING_CALF_RAISE")


if __name__ == "__main__":
    unittest.main()
