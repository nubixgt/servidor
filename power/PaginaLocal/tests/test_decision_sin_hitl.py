"""Con el HITL apagado, el modelo no puede devolver preguntas.

## El fallo, con los números reales

Dos de tres generaciones seguidas del atleta 2 (2026-09-10, tier `standard`,
el que corre en producción) murieron aquí. El `worker.log` lo cuenta en cuatro
líneas:

    Weekly planner node: HITL disabled
    Configuring LLM for role workout with model gpt-5.2
    POST https://api.openai.com/v1/responses "HTTP/1.1 200 OK"     (11 s)
    Week generation workflow complete: $0.1153 (76768 tokens, 8 node calls)

El modelo devolvió `list[Question]` en lugar de la semana. El nodo lo leyó como
preguntas de HITL y salió antes de tiempo **con `errors: []`**, así que arriba
sólo quedó un `weekly_plan` cuyo `output` no era un `WeeklyPlanPayload` y ni un
motivo que dar. El atleta veía "No se pudo generar la semana. Revisa la
consola." — y la consola no lo tenía: la generación corre en un proceso worker
aparte y su salida vive en `_runtime/jobs/<id>/worker.log`.

Once segundos y $0.1153, dos veces, sin plan y sin rastro.

## Por qué el esquema y no el prompt

Porque el HITL estaba APAGADO. Con `hitl_enabled=False` el prompt ni siquiera
lleva las instrucciones de HITL (`get_hitl_instructions` sólo se añade si está
encendido), pero el esquema seguía ofreciendo la rama. Un prompt y un esquema
que no dicen lo mismo los resuelve el modelo a favor del esquema, siempre — es
la regla que este repo ya tenía escrita, y por la que esa misma unión se quitó
del formateador.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

from pydantic import ValidationError

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TrainingDecisionNoHitl,
    TrainingDecisionOutputs,
)

PREGUNTAS = [{"id": "weekly_q1", "message": "¿Cuántos días puedes nadar?"}]

DIA = {
    "date": "2026-09-15",
    "day_name_es": "Martes",
    "session_slot": "am",
    "is_rest": False,
    "sport": "swim",
    "focus": "CSS suave",
    "purpose": "Mantener frecuencia de agua.",
    "adaptation": "Si el hombro avisa, 12x50 suave.",
    "intensity_class": "easy",
    "discipline": "natacion",
}
SEMANA = {"week_number": 1, "week_rationale": "Arranque.", "days": [DIA]}


class DecisionSinHitl(unittest.TestCase):
    def test_el_esquema_sin_hitl_rechaza_una_lista_de_preguntas(self):
        """La garantía entera: si nadie va a leer las preguntas, no se le
        ofrece hacerlas."""
        with self.assertRaises(ValidationError):
            TrainingDecisionNoHitl(output=PREGUNTAS)

    def test_el_esquema_sin_hitl_acepta_la_semana(self):
        d = TrainingDecisionNoHitl(output=SEMANA)
        self.assertEqual(d.output.week_number, 1)
        self.assertEqual(d.output.days[0].sport, "swim")

    def test_el_esquema_con_hitl_sigue_aceptando_las_dos_cosas(self):
        """El camino con HITL encendido no cambia: el orquestador sigue
        enrutando preguntas donde se contestan."""
        self.assertIsInstance(TrainingDecisionOutputs(output=PREGUNTAS).output, list)
        self.assertEqual(TrainingDecisionOutputs(output=SEMANA).output.week_number, 1)

    def test_la_rama_de_preguntas_no_esta_en_el_json_schema_apagado(self):
        """Lo que ve el proveedor. Si la rama sigue en el JSON Schema que se
        manda, el modelo la puede tomar por mucho que diga el prompt."""
        # Se mira la ESTRUCTURA, no el texto: la descripción de este propio
        # esquema menciona `list[Question]` al contar el fallo, y un
        # `assertNotIn` sobre la cadena entera lo confundiría con la rama.
        apagado = TrainingDecisionNoHitl.model_json_schema()
        self.assertNotIn("Question", apagado["$defs"])
        self.assertNotIn("anyOf", apagado["properties"]["output"])
        self.assertEqual(apagado["properties"]["output"]["$ref"], "#/$defs/WeekIntentContent")

        encendido = TrainingDecisionOutputs.model_json_schema()
        self.assertIn("Question", encendido["$defs"])
        self.assertIn("anyOf", encendido["properties"]["output"])

    def test_el_nodo_elige_el_esquema_segun_hitl(self):
        """La eleccion vive en `weekly_planner_node`. Si alguien vuelve a
        cablear `TrainingDecisionOutputs` a secas, esto lo cuenta."""
        fuente = (Path(__file__).resolve().parents[1]
                  / "services/ai/langgraph/nodes/weekly_planner_node.py").read_text(encoding="utf-8")
        self.assertIn("TrainingDecisionOutputs if hitl_enabled else TrainingDecisionNoHitl", fuente)


if __name__ == "__main__":
    unittest.main()
