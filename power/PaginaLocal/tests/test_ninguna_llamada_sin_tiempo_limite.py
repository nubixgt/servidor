"""Ninguna llamada a un modelo puede esperar para siempre.

El 2026-09-14 la semana de la atleta 3 se quedó 19 minutos colgada: la llamada
del experto de fisiología a OpenAI no volvió nunca. Sin tiempo límite no hubo
excepción, `retry_with_backoff` no tuvo nada que reintentar, y el worker siguió
"vivo" ocupando un hueco de la cola. Cada cliente sale ahora con `timeout`, y un
solo reintento interno para no multiplicar la espera con los de fuera.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import model_config  # noqa: E402
from services.ai.ai_settings import AgentRole  # noqa: E402


class _Capturar:
    def __init__(self):
        self.kwargs = None

    def __call__(self, **kwargs):
        self.kwargs = kwargs
        return object()


class CadaClienteLlevaTiempoLimite(unittest.TestCase):
    def _construir(self, clase, modelo):
        capturar = _Capturar()
        config = mock.Mock(anthropic_api_key="a", openai_api_key="o", gemini_api_key="g", openrouter_api_key=None)
        with mock.patch.object(model_config, clase, capturar), \
                mock.patch.object(model_config, "get_config", return_value=config), \
                mock.patch.object(model_config, "_GOOGLE_GENAI_AVAILABLE", True, create=True):
            model_config.ModelSelector.get_llm_by_role_and_name(AgentRole.PHYSIOLOGY_EXPERT, modelo)
        return capturar.kwargs

    def test_openai(self):
        kwargs = self._construir("ChatOpenAI", "gpt-5-mid")
        self.assertEqual(kwargs["timeout"], model_config.TIEMPO_LIMITE_LLM_S)
        self.assertEqual(kwargs["max_retries"], 1)

    def test_anthropic(self):
        kwargs = self._construir("ChatAnthropic", "claude-haiku")
        self.assertEqual(kwargs["timeout"], model_config.TIEMPO_LIMITE_LLM_S)

    def test_google(self):
        kwargs = self._construir("ChatGoogleGenerativeAI", "gemini-3.5-flash")
        self.assertEqual(kwargs["timeout"], model_config.TIEMPO_LIMITE_LLM_S)
        self.assertEqual(kwargs["max_retries"], 1)

    def test_el_limite_es_largo_para_las_llamadas_lentas_de_verdad(self):
        """La decisión semanal y el macrociclo tardan 2-3 minutos: el límite no
        puede cortar una llamada sana."""
        self.assertGreaterEqual(model_config.TIEMPO_LIMITE_LLM_S, 300)


if __name__ == "__main__":
    unittest.main()
