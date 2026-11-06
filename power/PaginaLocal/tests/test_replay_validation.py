"""v7 -- el banco de pruebas tiene que saber distinguir un plan de un plan roto.

`scripts/replay_node.py` decide qué modelo escribe los entrenos de todos los
atletas. Hasta v7 no validaba nada.

La razón es real y está en el código: `replay_call` le pasa a
`with_structured_output` un **dict** de JSON Schema (el que quedó archivado),
no la clase Pydantic, porque el archivo tiene que poder replicarse en un
proveedor que nunca vio la clase. LangChain devuelve entonces lo que el
proveedor produjo, sin validar.

Lo que eso permitió, en una corrida real del banco:

    claude-sonnet   $0.0689   error: null   604 tokens de salida

Devolvió un `WeekIntentContent` con `week_number`, un `week_rationale`
excelente... y **ningún `days`**. Una semana entera sin sesiones, contada como
éxito. Un banco que no distingue eso termina recomendando al modelo que
fracasa con más elegancia.

Estos tests fijan la propiedad opuesta: "válido" en el banco significa
exactamente lo que significa en producción, porque lo comprueba la misma clase
Pydantic que usa el pipeline.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.replay import (  # noqa: E402
    _schema_registry,
    validate_against_pipeline,
)

# La semana que Sonnet devolvió de verdad, recortada a lo esencial: los dos
# campos que sí escribió y el que no.
LA_SEMANA_SIN_DIAS = {
    "output": {
        "week_number": 1,
        "week_rationale": (
            "Esta semana arranca justo después de tu 21k no planificada del 23 de agosto "
            "(carga 141 según Garmin)..."
        ),
    }
}

UN_DIA = {
    "date": "2026-08-26",
    "day_name_es": "Miércoles",
    "is_rest": False,
    "sport": "run",
    "focus": "VO2max",
    "purpose": "Único estímulo de intensidad de la semana, corto.",
    "adaptation": "Si la ingle izquierda molesta, corta las series y trota suave.",
    "intensity_class": "vo2max",
    "discipline": "carrera",
}


class UnaSemanaSinDiasNoEsUnExito(unittest.TestCase):
    def test_la_falla_real_de_sonnet_queda_marcada(self):
        veredicto = validate_against_pipeline("TrainingDecisionOutputs", LA_SEMANA_SIN_DIAS)
        self.assertFalse(veredicto["ok"])
        self.assertIn("days", veredicto["summary"])

    def test_el_veredicto_es_legible_sin_abrir_el_json(self):
        """La tabla de comparación es lo único que alguien mira antes de
        cambiar el modelo de todos los atletas."""
        veredicto = validate_against_pipeline("TrainingDecisionOutputs", LA_SEMANA_SIN_DIAS)
        self.assertTrue(veredicto["verdict"].startswith("NO"))

    def test_una_semana_completa_si_pasa(self):
        buena = {"output": {"week_number": 1, "days": [UN_DIA], "week_rationale": "..."}}
        veredicto = validate_against_pipeline("TrainingDecisionOutputs", buena)
        self.assertTrue(veredicto["ok"], veredicto["summary"])
        self.assertEqual(veredicto["verdict"], "sí")


class NoSaberNoEsAprobar(unittest.TestCase):
    """Tres estados distintos, y confundir el tercero con el primero es
    justamente el bug: sin comprobar NO es lo mismo que válido."""

    def test_una_llamada_de_texto_libre_no_se_juzga(self):
        self.assertIsNone(validate_against_pipeline(None, {"lo que sea": 1}))

    def test_una_llamada_que_reventó_no_se_juzga(self):
        self.assertIsNone(validate_against_pipeline("TrainingDecisionOutputs", None))

    def test_un_esquema_que_no_existe_se_dice_en_voz_alta(self):
        veredicto = validate_against_pipeline("EsquemaQueNadieDefinio", {"a": 1})
        self.assertIsNone(veredicto["ok"])
        self.assertEqual(veredicto["verdict"], "sin comprobar")


class ElRegistroCubreLoQueElPipelinePide(unittest.TestCase):
    """Se construye escaneando los módulos de esquemas, no con una lista a
    mano, para que un esquema nuevo quede validado sin que nadie se acuerde de
    registrarlo aquí."""

    def test_estan_todos_los_esquemas_que_el_archivo_guarda(self):
        registro = _schema_registry()
        for nombre in (
            "TrainingDecisionOutputs",
            "FormattedWeekBlock",
            "ReceiverOutputs",
            "SeasonPlannerOutputs",
            "WeekBlock",
            "WeeklyPlanPayload",
        ):
            with self.subTest(esquema=nombre):
                self.assertIn(nombre, registro)


class LaComparacionMuestraElVeredicto(unittest.TestCase):
    def test_la_tabla_lleva_la_validez_antes_que_el_precio(self):
        """El orden importa: quien lee la tabla tiene que ver si la respuesta
        sirve ANTES de ver cuánto costó, o comparará precios de cosas que no
        son comparables."""
        from services.ai.replay import COMPARISON_ROWS

        etiquetas = [fila[0] for fila in COMPARISON_ROWS]
        self.assertIn("¿Lo aceptaría el pipeline?", etiquetas)
        self.assertLess(
            etiquetas.index("¿Lo aceptaría el pipeline?"),
            etiquetas.index("Costo real (USD)"),
        )


if __name__ == "__main__":
    unittest.main(verbosity=2)
