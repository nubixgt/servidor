"""El check-in de hoy llega a las preguntas del coach y a los tres expertos.

Atleta 2, Beta 2.0 publicada, 2026-09-21: contestó en su check-in agujetas en
gemelos y sóleos, un ligero dolor en el psoas y "semana normal", y a
continuación el coach le preguntó "¿cómo quedaste de piernas después del 5K?"
y "¿esta semana ya estás en rutina normal o tienes algún viaje?". El check-in
sólo lo leía la decisión semanal: en los prompts archivados de la atleta 3
(2026-09-15) su nota de la semana ("El sábado pienso hacer la activación para
la carrera") aparece en `007__weekly_planner` y en ningún paso anterior.

Sin check-in (el atleta pulsó "Saltar") los prompts no cambian ni un carácter.

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import importlib
import sys
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import preguntas_del_coach as pc  # noqa: E402
from services.ai.checkin import bloque_del_checkin, checkin_to_state, render_lo_que_dijo  # noqa: E402

# Lo que contestó el atleta 2 el 2026-09-21, en la forma en que lo guarda la app.
CHECKIN = {
    "date": "2026-09-21",
    "sleep": 4, "fatigue": 4, "stress": 3, "soreness": 5,
    "hooper_total": 16, "motivation": 5,
    "niggle_location": "psoas", "niggle_pain": 2,
    "week_ahead": "normal",
    "week_note": "Agujetas en gemelos y sóleos después del 5K",
}


def _estado(checkin=None):
    return {
        "planning_context": "Triatleta, 10K en abril.",
        "activity_summary": "2026-09-20 5K a tope.",
        "metrics_summary": "Carga crónica 280.",
        "physiology_summary": "HRV 51 el 21-sep, mediana 60.",
        "availability_constraints": {"checkin": checkin_to_state(checkin, [])},
        "competitions": [],
        "current_date": {"date": "2026-09-21"},
        "analysis_context": "",
        "execution_id": "prueba-checkin",
    }


class _ModeloFalso:
    def __init__(self):
        self.recibido = None

    def with_structured_output(self, _esquema):
        return self

    def bind_tools(self, _tools):
        return self

    async def ainvoke(self, mensajes):
        self.recibido = mensajes
        return pc.PreguntasDelCoach(preguntas=[])


def _prompt_de_las_preguntas(estado) -> str:
    modelo = _ModeloFalso()
    pc.usar_proveedor(object())
    try:
        with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=modelo), \
                mock.patch.object(pc, "_avisar_sin_preguntas"):
            asyncio.run(pc.preguntas_del_coach_node(estado))
    finally:
        pc.usar_proveedor(None)
    return modelo.recibido[1]["content"]


class _Capturado(Exception):
    pass


def _prompt_del_experto(modulo: str, funcion: str, estado) -> str:
    """El mensaje de usuario que recibiría el experto, sin llamar a ningún modelo."""
    nodo = importlib.import_module(f"services.ai.langgraph.nodes.{modulo}")
    visto = {}

    async def capturar(llm_with_tools, messages, tools, max_iterations):  # noqa: ARG001
        visto["mensajes"] = messages
        raise _Capturado  # el nodo lo reintenta: ver sin_esperar

    async def sin_esperar(_segundos):
        return None

    # Los reintentos del nodo esperan entre intentos; aquí no hace falta esperar.
    with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=_ModeloFalso()), \
            mock.patch.object(nodo, "handle_tool_calling_in_node", capturar), \
            mock.patch("services.ai.utils.retry_handler.asyncio.sleep", sin_esperar), \
            mock.patch("logging.Logger.error"), mock.patch("logging.Logger.exception"):
        try:
            asyncio.run(getattr(nodo, funcion)(estado))
        except Exception:  # noqa: BLE001 - el nodo envuelve el error; sólo interesa lo capturado
            pass
    return visto["mensajes"][1]["content"]


EXPERTOS = (
    ("physiology_expert_node", "physiology_expert_node"),
    ("metrics_expert_node", "metrics_expert_node"),
    ("activity_expert_node", "activity_expert_node"),
)


class LoQueDijoEsSoloLoQueDijo(unittest.TestCase):
    def test_sin_checkin_no_hay_texto(self):
        self.assertEqual(render_lo_que_dijo(None), "")
        self.assertEqual(bloque_del_checkin(_estado(None)), "")

    def test_lleva_sus_respuestas(self):
        texto = render_lo_que_dijo(CHECKIN, [])
        self.assertIn("respondido el 2026-09-21", texto)
        self.assertIn("Molestia: psoas, dolor 2/10", texto)
        self.assertIn("Cómo viene la semana", texto)
        self.assertIn("«Agujetas en gemelos y sóleos después del 5K»", texto)

    def test_sin_las_instrucciones_del_planificador(self):
        """Estos pasos no escriben la semana: hablarles de `week_rationale` no les toca."""
        texto = render_lo_que_dijo(CHECKIN, [])
        self.assertNotIn("week_rationale", texto)
        self.assertNotIn("validador", texto)


class LlegaALasPreguntasDelCoach(unittest.TestCase):
    def test_el_coach_ve_el_checkin_de_hoy(self):
        prompt = _prompt_de_las_preguntas(_estado(CHECKIN))
        self.assertIn("Check-in del atleta (respondido el 2026-09-21)", prompt)
        self.assertIn("Molestia: psoas", prompt)

    def test_sin_checkin_el_prompt_no_cambia(self):
        con_vacio = _prompt_de_las_preguntas(_estado(None))
        sin_clave = _estado(None)
        sin_clave["availability_constraints"] = {}
        self.assertEqual(con_vacio, _prompt_de_las_preguntas(sin_clave))
        self.assertIn("Triatleta, 10K en abril.\n\n## Su cuaderno", con_vacio)


class LlegaALosTresExpertos(unittest.TestCase):
    def test_cada_experto_ve_el_checkin(self):
        for modulo, funcion in EXPERTOS:
            with self.subTest(experto=modulo):
                prompt = _prompt_del_experto(modulo, funcion, _estado(CHECKIN))
                self.assertIn("Check-in del atleta (respondido el 2026-09-21)", prompt)
                self.assertIn("«Agujetas en gemelos y sóleos después del 5K»", prompt)

    def test_sin_checkin_ni_una_linea_de_mas(self):
        for modulo, funcion in EXPERTOS:
            with self.subTest(experto=modulo):
                prompt = _prompt_del_experto(modulo, funcion, _estado(None))
                self.assertNotIn("Check-in del atleta", prompt)
                self.assertFalse(prompt.endswith("\n\n"))


if __name__ == "__main__":
    unittest.main()
