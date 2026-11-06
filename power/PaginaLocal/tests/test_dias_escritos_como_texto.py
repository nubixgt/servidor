"""Una semana cuyo contenido es correcto no se descarta por venir entre comillas.

Semana real del atleta 2 (2026-09-13, traza `reasoning/2026-09-13T15-08-07`):
el codificador devolvió `days` como una cadena con la lista JSON dentro.
Pydantic la rechazó entera ("Input should be a valid list"), se reintentó y al
final se cayó al esquema flexible -- tres llamadas al codificador donde había
hecho falta una.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import sys
import unittest
from pathlib import Path

from pydantic import ValidationError

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import FormattedWeekBlock  # noqa: E402

ARGUMENTOS = {"week_number": 1, "week_rationale": "Semana de afinamiento."}


class LosDiasEscritosComoTexto(unittest.TestCase):
    def test_una_lista_en_texto_se_lee_como_lista(self):
        bloque = FormattedWeekBlock.model_validate({**ARGUMENTOS, "days": json.dumps([])})
        self.assertEqual(bloque.days, [])

    def test_una_lista_de_verdad_no_cambia(self):
        self.assertEqual(FormattedWeekBlock.model_validate({**ARGUMENTOS, "days": []}).days, [])

    def test_un_texto_que_no_es_una_lista_sigue_fallando(self):
        """Quita un envoltorio, no adivina contenido."""
        for malo in ("no es json", json.dumps({"date": "2026-09-14"}), "[{"):
            with self.subTest(valor=malo), self.assertRaises(ValidationError):
                FormattedWeekBlock.model_validate({**ARGUMENTOS, "days": malo})

    def test_el_esquema_que_ve_el_proveedor_no_cambia(self):
        """Toda restricción tiene que poder expresarse en el JSON Schema; ésta no
        añade ninguna: `days` sigue siendo un array."""
        esquema = FormattedWeekBlock.model_json_schema()
        self.assertEqual(esquema["properties"]["days"]["type"], "array")


if __name__ == "__main__":
    unittest.main()
