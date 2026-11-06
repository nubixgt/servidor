"""Cada token de salida se paga: que el modelo escriba sólo lo que alguien va a leer.

Semana del atleta 2 del 2026-09-23 (reasoning/2026-09-23T09-11-14), 0,228 USD, de los
que 0,152 (67 %) eran tokens de salida:

- Los tres expertos escribían sus tres secciones (`for_synthesis` ~3.100 caracteres,
  `for_season_planner` ~2.600, `for_weekly_planner` ~2.500) y en una semana sólo se lee
  la última: no corren ni la síntesis ni el macrociclo. ~0,04 USD tirados por semana.
- El codificador (Haiku) volvía a escribir el racional, el propósito y la adaptación
  que ya había escrito la decisión: 3.540 de sus 5.710 caracteres.
- El paso de fuerza escribía un motivo por ejercicio para bajar series que la dosis ya
  bajaba (2.426 caracteres), y la pantalla ni los enseña.

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import importlib
import sys
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.checkin import checkin_to_state  # noqa: E402
from services.ai.langgraph.nodes import paso_de_fuerza as pf  # noqa: E402
from services.ai.langgraph.schemas.expert_outputs import (  # noqa: E402
    ReceiverOutputs,
    nota_de_receptores,
    salida_para,
)
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    DayIntent,
    WeekBlock,
    WeekIntentContent,
    WorkoutDay,
)

NODO = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")


class LosExpertosEscribenSoloLoQueSeLee(unittest.TestCase):
    def test_en_una_semana_solo_la_seccion_de_la_semana(self):
        esquema = salida_para(["for_weekly_planner"]).model_json_schema()
        self.assertEqual(list(esquema["properties"]), ["for_weekly_planner"])
        self.assertIn("sólo se lee `for_weekly_planner`", nota_de_receptores(["for_weekly_planner"]))

    def test_sin_receptores_se_piden_las_tres_como_siempre(self):
        self.assertIs(salida_para(None), ReceiverOutputs)
        self.assertIs(salida_para(["for_synthesis", "for_season_planner", "for_weekly_planner"]), ReceiverOutputs)
        self.assertEqual(nota_de_receptores(None), "")

    def test_lo_que_devuelven_se_sigue_leyendo_igual(self):
        from services.ai.langgraph.utils.output_helper import extract_expert_output

        Salida = salida_para(["for_weekly_planner"])
        leido = Salida(for_weekly_planner={"signals": ["Tolera bien el umbral"], "evidence": [], "implications": [],
                                           "uncertainty": []})
        self.assertIn("Tolera bien el umbral", extract_expert_output(leido, "for_weekly_planner"))

    def test_los_tres_nodos_piden_solo_eso(self):
        class _Falso:
            def __init__(self):
                self.esquemas = []

            def bind_tools(self, _t):
                return self

            def with_structured_output(self, esquema):
                self.esquemas.append(esquema)
                return self

        async def capturar(llm_with_tools, messages, tools, max_iterations):  # noqa: ARG001
            capturar.mensaje = messages[1]["content"]
            raise RuntimeError("basta")

        async def sin_esperar(_s):
            return None

        estado = {"execution_id": "t", "competitions": [], "current_date": {"date": "2026-09-23"},
                  "analysis_context": "", "hitl_enabled": False, "receptores_de_expertos": ["for_weekly_planner"],
                  "availability_constraints": {"checkin": checkin_to_state(None, [])}}
        for modulo in ("metrics_expert_node", "physiology_expert_node", "activity_expert_node"):
            with self.subTest(experto=modulo):
                nodo = importlib.import_module(f"services.ai.langgraph.nodes.{modulo}")
                falso = _Falso()
                with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=falso), \
                        mock.patch.object(nodo, "handle_tool_calling_in_node", capturar), \
                        mock.patch("services.ai.utils.retry_handler.asyncio.sleep", sin_esperar), \
                        mock.patch("logging.Logger.error"), mock.patch("logging.Logger.exception"):
                    try:
                        asyncio.run(getattr(nodo, modulo)(estado))
                    except Exception:  # noqa: BLE001
                        pass
                self.assertEqual(list(falso.esquemas[0].model_json_schema()["properties"]), ["for_weekly_planner"])
                self.assertIn("sólo se lee `for_weekly_planner`", capturar.mensaje)

    def test_cada_flujo_dice_que_lee(self):
        fuente = Path(importlib.import_module("services.ai.langgraph.workflows.planning_workflow").__file__).read_text(
            encoding="utf-8")
        self.assertIn('initial_state["receptores_de_expertos"] = ["for_weekly_planner"]', fuente)
        self.assertIn('["for_synthesis", "for_season_planner"]', fuente)


class ElCodificadorNoReescribeLoQueYaEstaEscrito(unittest.TestCase):
    def test_el_proposito_y_la_adaptacion_se_copian_de_la_decision(self):
        decision = WeekIntentContent(week_number=1, week_rationale="r", days=[DayIntent(
            date="2026-09-30", day_name_es="Miércoles", is_rest=False, sport="run", focus="Umbral",
            purpose="Consolidar tu umbral.", adaptation="Si dormiste mal, 2x8'.", total_duration_minutes=50,
            intensity_class="tempo_threshold", discipline="carrera", session_sketch="3x8'")])
        semana = WeekBlock(week_number=1, week_rationale="r", days=[WorkoutDay(
            date="2026-09-30", day_name_es="Miércoles", is_rest=False, sport="run", focus="Umbral",
            purpose="", adaptation="", steps=[])])
        NODO._apply_discipline(semana, decision)
        self.assertEqual(semana.days[0].purpose, "Consolidar tu umbral.")
        self.assertEqual(semana.days[0].adaptation, "Si dormiste mal, 2x8'.")


class LaFuerzaNoExplicaLoQueExplicaLaDosis(unittest.TestCase):
    def test_el_prompt_lo_dice(self):
        self.assertIn("no escribas un cambio de series por ejercicio", pf.AJUSTE_SYSTEM_PROMPT)


if __name__ == "__main__":
    unittest.main()
