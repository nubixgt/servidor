"""La traza guarda el markdown de la síntesis, no el repr del mensaje.

Visto el 2026-09-11 en `reasoning/2026-09-11T11-17-46/synthesis.md` del atleta
2: Gemini 3.5 devuelve el contenido como LISTA de partes, `_extract_text` solo
sabía de cadenas y caía a `str(value)`, y el archivo empezaba por
"content=[{'type': 'text', 'text': '# Informe de Síntesis...". Lo leen el chat
del coach (con las secuencias de escape dentro) y el informe de análisis
re-renderizado, que pintado era un único párrafo de 93.204 px de ancho.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.reasoning_archive import _extract_text, rescatar_markdown  # noqa: E402

MARKDOWN = "# Informe de Síntesis del Atleta\n**Fecha:** 11 de septiembre\n\n## Resumen\nTexto 'con' comillas."


class LaListaDePartesEsTexto(unittest.TestCase):
    def test_un_mensaje_con_partes_se_guarda_como_su_texto(self):
        mensaje = SimpleNamespace(content=[{"type": "text", "text": MARKDOWN, "extras": {"signature": "x"}}])
        self.assertEqual(_extract_text(mensaje), MARKDOWN)

    def test_una_cadena_sigue_siendo_una_cadena(self):
        self.assertEqual(_extract_text(SimpleNamespace(content=MARKDOWN)), MARKDOWN)


class LasTrazasViejasSeRescatan(unittest.TestCase):
    def test_el_repr_se_convierte_en_su_markdown(self):
        crudo = "content=" + repr([{"type": "text", "text": MARKDOWN, "extras": {"signature": "abc"}}]) \
            + " additional_kwargs={} response_metadata={'finish_reason': 'STOP'}"
        self.assertEqual(rescatar_markdown(crudo), MARKDOWN)

    def test_un_markdown_bueno_no_se_toca(self):
        self.assertEqual(rescatar_markdown(MARKDOWN), MARKDOWN)


if __name__ == "__main__":
    unittest.main()
