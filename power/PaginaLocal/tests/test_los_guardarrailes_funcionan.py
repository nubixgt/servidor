"""Un guardarraíl que parece funcionar y no hace nada (2026-09-13).

Semana regenerada del atleta 2 (traza `reasoning/2026-09-13T19-49-49`):

1. **La reparación tier-2 no funcionaba.** Las dos llamadas a gpt-5 dieron 400:
   `WeekBlock.coach_answers` es un `list[dict[str, str]]`, y el modo estricto de
   OpenAI rechaza un objeto con `additionalProperties` ("Extra required key
   'coach_answers'"). El validador se quedaba con el borrador y el atleta leía
   "se corrigió automáticamente (2 intento(s) de corrección por IA)".
   Verificado contra la API real: con `SkipJsonSchema` la misma llamada pasa.
2. **El expediente heredaba caídas falsas** de la línea que reemplazaba.
3. **El planificador recibía la carga como moneda sin el precio de nada**: las
   tasas por tipo de sesión se calculaban y sólo iban al log.
4. **El check-in de hace dos días se presentaba como "justo antes".**

Correr con:  python -m unittest discover -s tests -v
"""

import shutil
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import track_record as tr  # noqa: E402
from services.ai.checkin import render_checkin_block  # noqa: E402
from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    _format_load_target,
    compute_weekly_load_target,
)
from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload  # noqa: E402
from services.ai.preguntas_del_coach import PreguntasDelCoach  # noqa: E402


def _objetos_abiertos(nodo, ruta="raiz"):
    """Rutas de objetos cuyo `additionalProperties` es un esquema (un dict de
    Python): lo que el modo estricto de OpenAI rechaza."""
    halladas = []
    if isinstance(nodo, dict):
        if isinstance(nodo.get("additionalProperties"), dict):
            halladas.append(ruta)
        for clave, valor in nodo.items():
            halladas += _objetos_abiertos(valor, f"{ruta}.{clave}")
    elif isinstance(nodo, list):
        for i, valor in enumerate(nodo):
            halladas += _objetos_abiertos(valor, f"{ruta}[{i}]")
    return halladas


class LosEsquemasQueVanAOpenAIPasanElModoEstricto(unittest.TestCase):
    def test_la_reparacion_de_la_semana(self):
        self.assertEqual(_objetos_abiertos(WeeklyPlanPayload.model_json_schema()), [])

    def test_las_preguntas_del_coach(self):
        self.assertEqual(_objetos_abiertos(PreguntasDelCoach.model_json_schema()), [])

    def test_coach_answers_sigue_guardandose(self):
        """Fuera del esquema no es fuera del plan: el código lo rellena y viaja."""
        plan = WeeklyPlanPayload.model_validate({
            "athlete_name": "x", "start_date": "2026-09-13", "zones_table": "",
            "weeks": [{"week_number": 1, "week_rationale": "", "days": [],
                       "coach_answers": [{"pregunta": "¿Viajas?", "respuesta": "Sí"}]}],
        })
        self.assertEqual(plan.weeks[0].coach_answers[0]["respuesta"], "Sí")
        self.assertIn("coach_answers", plan.model_dump()["weeks"][0])


class ElExpedienteNoHeredaCaidasFalsas(unittest.TestCase):
    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def test_una_linea_sin_evaluar_no_le_pasa_sus_caidas_a_la_nueva(self):
        vieja = {"from": "2026-09-13", "to": "2026-09-20", "week_number": 1, "prescribed_hours": 7.42,
                 "prescribed_sessions": 10, "quality_prescribed": 2, "quality_done": 0,
                 "quality_missed": ["2026-09-16 VO2max 2'", "2026-09-20 5K Monterrico"],
                 "completed_sessions": None, "missed_sessions": None, "executed_hours": None, "acwr": 1.07}
        tr._append(self.dir, vieja)
        semana = {"week_number": 1, "days": [
            {"date": "2026-09-13", "is_rest": False, "sport": "run", "focus": "Soltura",
             "intensity_class": "easy", "total_duration_minutes": 45},
            {"date": "2026-09-20", "is_rest": False, "sport": "run", "focus": "5k Monterrico",
             "intensity_class": "race_day", "total_duration_minutes": 60},
            {"date": "2026-09-16", "is_rest": False, "sport": "run", "focus": "VO2max 2'",
             "intensity_class": "vo2max", "total_duration_minutes": 48},
        ]}
        nueva = tr.record_week(self.dir, semana, None)
        self.assertEqual(nueva["quality_missed"], [])
        self.assertEqual(nueva["acwr"], 1.07, "la foto de carga sí se conserva al regenerar")


class ElPlanificadorConoceElPrecioDeCadaSesion(unittest.TestCase):
    def test_el_bloque_de_carga_trae_sus_tasas(self):
        base = {"garmin_chronic_load": 291, "garmin_acute_load": 309, "load_rates": {
            "run:suave": {"por_hora": 47, "n": 50}, "run:calidad": {"por_hora": 107, "n": 14},
            "bike:suave": {"por_hora": 32, "n": 15}, "fuerza": {"por_sesion": 8, "n": 38},
        }}
        bloque = _format_load_target(compute_weekly_load_target(base, {"is_deload": False}))
        self.assertIn("carrera de calidad ~107/h", bloque)
        self.assertIn("fuerza ~8 por sesión", bloque)
        self.assertIn("no es un presupuesto que llenar", bloque)


class ElCheckInDiceDeCuandoEs(unittest.TestCase):
    def test_lleva_su_fecha_y_no_dice_justo_antes(self):
        bloque = render_checkin_block({"date": "2026-09-11", "sleep": 1, "fatigue": 1, "stress": 1,
                                       "soreness": 1, "motivation": 7, "hooper_total": 4})
        self.assertIn("respondido el 2026-09-11", bloque)
        self.assertNotIn("justo antes", bloque)


if __name__ == "__main__":
    unittest.main()
