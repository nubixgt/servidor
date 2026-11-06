"""El coach mira los datos, pregunta lo que los datos no dicen, y la respuesta llega a todos.

Ver services/ai/preguntas_del_coach.py para por qué es un paso aparte entre
los resumidores y los expertos, y no el relanzado de expertos del LangGraph
original de Leon (una barrera que espera a los tres expertos no se vuelve a
disparar si sólo se relanza uno).

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import sys
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import preguntas_del_coach as pc  # noqa: E402

ESTADO = {
    "planning_context": "Triatleta, 10K en abril.",
    "activity_summary": "2026-09-09 8x2' a 4:45-5:39/km, pico 188 ppm.",
    "metrics_summary": "Carga crónica 280.",
    "physiology_summary": "HRV 48 el 10-sep, mediana 60.",
    "availability_constraints": {},
}


class _ModeloFalso:
    def __init__(self, salida):
        self.salida = salida
        self.recibido = None

    def with_structured_output(self, _esquema):
        return self

    async def ainvoke(self, mensajes):
        self.recibido = mensajes
        return self.salida


class _Atleta:
    def __init__(self, respuestas):
        self.respuestas = respuestas
        self.preguntado = None

    def preguntar(self, preguntas):
        self.preguntado = preguntas
        return self.respuestas


def _correr(salida, atleta):
    modelo = _ModeloFalso(salida)
    pc.usar_proveedor(atleta)
    try:
        with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=modelo):
            return asyncio.run(pc.preguntas_del_coach_node(ESTADO)), modelo
    finally:
        pc.usar_proveedor(None)


UNA_PREGUNTA = pc.PreguntasDelCoach(preguntas=[pc.PreguntaAlAtleta(
    pregunta="¿Cómo te encontraste el día después de las series del martes?",
    por_que="Tu HRV bajó a 48 el 10-sep, con mediana 60.",
)])


class SinProveedorNoGastaNada(unittest.TestCase):
    def test_no_llama_al_modelo_ni_pregunta(self):
        pc.usar_proveedor(None)
        with mock.patch("services.ai.model_config.ModelSelector.get_llm") as get_llm:
            self.assertEqual(asyncio.run(pc.preguntas_del_coach_node(ESTADO)), {})
        get_llm.assert_not_called()


class LaRespuestaLlegaATodos(unittest.TestCase):
    def setUp(self):
        self.atleta = _Atleta(["Me costó dos días; el jueves aún tenía las piernas pesadas."])
        self.salida, self.modelo = _correr(UNA_PREGUNTA, self.atleta)

    def test_el_atleta_ve_la_pregunta_y_su_porque(self):
        self.assertEqual(self.atleta.preguntado[0]["por_que"], "Tu HRV bajó a 48 el 10-sep, con mediana 60.")

    def test_la_conversacion_llega_a_los_tres_expertos_y_a_los_dos_planificadores(self):
        for clave in ("metrics_expert_messages", "physiology_expert_messages", "activity_expert_messages",
                      "season_planner_messages", "weekly_planner_messages"):
            with self.subTest(clave=clave):
                texto = " ".join(m.content for m in self.salida[clave])
                self.assertIn("piernas pesadas", texto)
                self.assertIn("series del martes", texto)

    def test_el_modelo_ve_los_resumenes_no_los_datos_crudos(self):
        prompt = self.modelo.recibido[1]["content"]
        self.assertIn("8x2'", prompt)
        self.assertIn("HRV 48", prompt)

    def test_el_coste_se_anota(self):
        self.assertEqual(self.salida["costs"][0]["agent"], "preguntas_del_coach")


class CasosQueNoPuedenRomperNada(unittest.TestCase):
    def test_sin_preguntas_no_molesta_al_atleta(self):
        atleta = _Atleta([])
        salida, _ = _correr(pc.PreguntasDelCoach(preguntas=[]), atleta)
        self.assertIsNone(atleta.preguntado)
        self.assertNotIn("weekly_planner_messages", salida)

    def test_si_no_contesta_se_dice_y_se_sigue(self):
        salida, _ = _correr(UNA_PREGUNTA, _Atleta([]))
        self.assertIn("no contestó", salida["weekly_planner_messages"][1].content)

    def test_un_fallo_del_modelo_no_cuesta_la_generacion(self):
        class _Roto(_ModeloFalso):
            async def ainvoke(self, mensajes):
                raise RuntimeError("proveedor caído")
        pc.usar_proveedor(_Atleta([]))
        try:
            with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=_Roto(None)):
                self.assertEqual(asyncio.run(pc.preguntas_del_coach_node(ESTADO)), {})
        finally:
            pc.usar_proveedor(None)

    def test_nunca_mas_de_tres(self):
        from pydantic import ValidationError
        with self.assertRaises(ValidationError):
            pc.PreguntasDelCoach(preguntas=[UNA_PREGUNTA.preguntas[0]] * 4)


class LasRespuestasVivenConElMacrociclo(unittest.TestCase):
    """No en el cuaderno: cada generación pregunta, y el cuaderno es para hechos
    duraderos. En unas semanas habría sido una pila de "cómo te sentiste tras
    las series del 9-sep", y un macro de dentro de un año seguiría leyendo "vas
    a tope al 5K Monterrico" (atleta 2, 2026-09-11)."""

    QA = [{"pregunta": "¿Cómo quieres correr el 5K Monterrico?", "por_que": "p",
           "respuesta": "A tope, buscando marca."},
          {"pregunta": "¿Te quedó molestia tras la bici larga?", "por_que": "p", "respuesta": ""}]
    MACRO = "# Plan\n\n## Fases\nBase y construcción."

    def test_el_nodo_devuelve_lo_contestado(self):
        salida, _ = _correr(UNA_PREGUNTA, _Atleta(["Me costó dos días."]))
        self.assertEqual(salida["respuestas_al_coach"][0]["respuesta"], "Me costó dos días.")

    def test_la_seccion_lleva_solo_lo_contestado(self):
        seccion = pc.seccion_de_respuestas(self.QA, "2026-09-11")
        self.assertIn("A tope, buscando marca.", seccion)
        self.assertNotIn("bici larga", seccion)
        self.assertEqual(pc.seccion_de_respuestas([], "2026-09-11"), "")

    def test_la_semana_la_lee_del_macro_vigente(self):
        macro = self.MACRO + pc.seccion_de_respuestas(self.QA, "2026-09-11")
        self.assertIn("A tope", pc.respuestas_del_macrociclo(macro))
        self.assertEqual(pc.respuestas_del_macrociclo(self.MACRO), "")

    def test_el_macro_siguiente_no_la_hereda(self):
        macro = self.MACRO + pc.seccion_de_respuestas(self.QA, "2026-09-11")
        self.assertEqual(pc.sin_respuestas_al_coach(macro), self.MACRO)

    def test_ni_por_el_extracto_del_plan_anterior(self):
        from services.ai.previous_season import render_previous_season
        macro = self.MACRO + pc.seccion_de_respuestas(self.QA, "2026-09-11")
        bloque = render_previous_season(macro, {"phases": [], "season_start_date": "2026-09-11"})
        self.assertIn("Base y construcción", bloque)
        self.assertNotIn("Monterrico", bloque)

    def test_el_paso_de_preguntas_ve_lo_ya_contestado_para_no_repetirlo(self):
        macro = self.MACRO + pc.seccion_de_respuestas(self.QA, "2026-09-11")
        estado = {**ESTADO, "season_plan": {"output": {"markdown": macro}}}
        modelo = _ModeloFalso(pc.PreguntasDelCoach(preguntas=[]))
        pc.usar_proveedor(_Atleta([]))
        try:
            with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=modelo):
                asyncio.run(pc.preguntas_del_coach_node(estado))
        finally:
            pc.usar_proveedor(None)
        self.assertIn("A tope, buscando marca.", modelo.recibido[1]["content"])


class NoPreguntaLoQueYASABE(unittest.TestCase):
    """2026-09-11, generación real: le preguntó al atleta 2 si tiene rodillo.
    Su perfil dice "las dos" desde hace meses -- pero el bloque del perfil se
    recortaba a 6.000 caracteres y el equipamiento va al final, así que nunca
    llegaba. Y las respuestas de la semana anterior tampoco llegaban."""

    def test_el_perfil_entero_llega_al_paso_de_preguntas(self):
        largo = "x" * 9000 + " TIENE RODILLO Y CALLE"
        estado = {**ESTADO, "planning_context": largo}
        modelo = _ModeloFalso(pc.PreguntasDelCoach(preguntas=[]))
        pc.usar_proveedor(_Atleta([]))
        try:
            with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=modelo):
                asyncio.run(pc.preguntas_del_coach_node(estado))
        finally:
            pc.usar_proveedor(None)
        self.assertIn("TIENE RODILLO Y CALLE", modelo.recibido[1]["content"])

    def test_ve_lo_que_contesto_al_generar_la_semana_anterior(self):
        estado = {**ESTADO, "availability_constraints": {
            "respuestas_previas": {"block": "## Lo que te contesto\n- **¿A tope el 5K?** Sí, a tope."},
        }}
        modelo = _ModeloFalso(pc.PreguntasDelCoach(preguntas=[]))
        pc.usar_proveedor(_Atleta([]))
        try:
            with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=modelo):
                asyncio.run(pc.preguntas_del_coach_node(estado))
        finally:
            pc.usar_proveedor(None)
        self.assertIn("Sí, a tope.", modelo.recibido[1]["content"])


class ElGrafoLoTieneEnSuSitio(unittest.TestCase):
    def test_entre_los_resumidores_y_los_expertos_en_los_dos_grafos(self):
        from services.ai.langgraph.workflows.planning_workflow import (
            create_analysis_and_season_workflow,
            create_week_generation_workflow,
        )
        for crear in (create_analysis_and_season_workflow, create_week_generation_workflow):
            with self.subTest(grafo=crear.__name__):
                aristas = {(e.source, e.target) for e in crear().get_graph().edges}
                self.assertIn(("preguntas_del_coach", "activity_expert"), aristas)
                self.assertIn(("activity_summarizer", "preguntas_del_coach"), aristas)
                self.assertNotIn(("activity_summarizer", "activity_expert"), aristas)


if __name__ == "__main__":
    unittest.main()
