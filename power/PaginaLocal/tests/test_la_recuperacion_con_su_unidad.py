"""La recuperación de una serie se escribe en la unidad en que se hace.

Causa raíz de las duraciones equivocadas de las semanas finales del 2026-09-14
(prompts archivados `008__weekly_planner__claude-haiku-4-5.json`):

- Atleta 3, miércoles 6x400 m: el codificador devolvió `recovery_seconds=200`
  con `recovery_description="200m trote muy suave Z1"`. Sabía que eran metros;
  el esquema sólo le daba un campo en segundos, y al reloj iban 3'20".
- Atleta 2, natación: "25 m suave" como `recovery_seconds=25`.
- Atleta 3: "3 progresivos de 15-20″" sin recuperación en la decisión; el
  codificador inventó 90 s y 45 s y copió lo mismo al domingo.

Lo que se arregló es el esquema, el prompt y la entrada, no una regla más.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

# El paquete `nodes` exporta la FUNCIÓN con el mismo nombre que el módulo.
nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    DayIntent,
    FormattedWeekBlock,
    FormattedWorkoutStep,
    WeekIntentContent,
    expand_formatted_steps,
)


def _serie(**kwargs):
    base = dict(step_order=1, step_type="interval", duration_type="distance", duration_value=400,
                repeat_count=6, description="6x(400m a 5:10-5:16/km, 200 m trote)")
    base.update(kwargs)
    return expand_formatted_steps([FormattedWorkoutStep(**base)])[0]


class LaRecuperacionLlevaSuUnidad(unittest.TestCase):
    def test_200_metros_de_trote_son_200_metros(self):
        bloque = _serie(recovery_duration_type="distance", recovery_duration_value=200,
                        recovery_description="200m trote muy suave Z1")
        trabajo, recuperacion = bloque.substeps
        self.assertEqual((recuperacion.duration_type, recuperacion.duration_value), ("distance", 200.0))
        self.assertEqual(bloque.repeat_count, 6)
        self.assertEqual(trabajo.duration_value, 400.0)

    def test_25_metros_suaves_nadando(self):
        bloque = _serie(duration_value=25, repeat_count=4, recovery_duration_type="distance",
                        recovery_duration_value=25, description="4x25m rápido")
        self.assertEqual(bloque.substeps[1].duration_type, "distance")

    def test_hasta_que_recuperes_es_boton_de_vuelta(self):
        bloque = _serie(recovery_duration_type="lap_button")
        recuperacion = bloque.substeps[1]
        self.assertEqual(recuperacion.duration_type, "lap_button")
        self.assertIsNone(recuperacion.duration_value)

    def test_los_segundos_siguen_siendo_segundos(self):
        bloque = _serie(duration_type="time", duration_value=20, repeat_count=4,
                        recovery_duration_type="time", recovery_duration_value=60,
                        description="4x20\" progresivos")
        self.assertEqual((bloque.substeps[1].duration_type, bloque.substeps[1].duration_value), ("time", 60.0))

    def test_recovery_seconds_de_antes_se_lee_como_tiempo(self):
        """Prompts archivados antes del 2026-09-15 y réplicas los traen."""
        bloque = _serie(recovery_seconds=120)
        self.assertEqual((bloque.substeps[1].duration_type, bloque.substeps[1].duration_value), ("time", 120.0))

    def test_un_tipo_sin_valor_no_inventa_una_recuperacion(self):
        paso = _serie(recovery_duration_type="time")
        self.assertEqual(paso.substeps, [])


class ElEsquemaQueVeElModelo(unittest.TestCase):
    def setUp(self):
        self.esquema = FormattedWeekBlock.model_json_schema()
        self.paso = self.esquema["$defs"]["FormattedWorkoutStep"]

    def test_ofrece_la_unidad_y_no_el_campo_de_segundos(self):
        self.assertIn("recovery_duration_type", self.paso["properties"])
        self.assertIn("recovery_duration_value", self.paso["properties"])
        self.assertNotIn("recovery_seconds", self.paso["properties"])

    def test_no_lleva_la_historia_de_desarrollo(self):
        """4.327 caracteres de docstrings (fallos de Gemini, costes) viajaban
        en cada llamada del codificador como descripción de clase."""
        for clase in ("FormattedWorkoutStep", "FormattedWorkoutDay", "StrengthExercise"):
            with self.subTest(clase=clase):
                self.assertNotIn("description", self.esquema["$defs"][clase])
        self.assertIn("gemini-3.5-flash", FormattedWorkoutStep.__doc__ or "", "el docstring sigue en el código")


class ElPromptEnsenaLoMismo(unittest.TestCase):
    def test_el_codificador_no_convierte_metros_en_segundos(self):
        prompt = nodo.WORKOUT_FORMATTER_USER_PROMPT
        self.assertNotIn("recovery_seconds", prompt)
        self.assertIn('"recovery_duration_type": "distance", "recovery_duration_value": 200', prompt)
        self.assertIn("Nunca conviertas", prompt)
        self.assertNotIn('"duration_value": 90', prompt)

    def test_la_decision_escribe_un_valor_y_la_recuperacion_con_unidad(self):
        self.assertIn("ONE duration or distance per repetition", nodo.TRAINING_DECISION_USER_PROMPT)
        descripcion = DayIntent.model_fields["session_sketch"].description
        self.assertIn("never for durations", descripcion)

    def test_un_rango_de_duracion_se_queda_dentro_del_rango(self):
        self.assertIn("use a value inside that range", nodo.WORKOUT_FORMATTER_SYSTEM_PROMPT)


class ElCatalogoSoloConFuerza(unittest.TestCase):
    def _semana(self, *deportes):
        dias = [DayIntent(date=f"2026-09-{14 + i}", day_name_es="d", is_rest=False, sport=d, focus="f",
                          purpose="p", adaptation="a", intensity_class="easy", discipline="carrera")
                for i, d in enumerate(deportes)]
        return WeekIntentContent(week_number=1, days=dias, week_rationale="r")

    def test_sin_fuerza_no_va(self):
        self.assertFalse(nodo._hay_fuerza(self._semana("run", "bike", "swim")))

    def test_con_fuerza_va(self):
        self.assertTrue(nodo._hay_fuerza(self._semana("run", "strength")))


class ElCodificadorNoImprovisa(unittest.TestCase):
    def test_temperatura_cero(self):
        from services.ai.model_config import ModelSelector
        self.assertEqual(ModelSelector.MODEL_CONFIGS["claude-haiku"]["temperature"], 0)


if __name__ == "__main__":
    unittest.main()
