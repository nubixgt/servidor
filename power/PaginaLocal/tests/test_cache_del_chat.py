"""La caché del chat, y el campo que casi la deja mal contada.

## Por qué existe la caché

El coste del chat es ENTRADA, no salida. Cuatro mensajes seguidos de una misma
conversación, del libro real:

    28.459 in /   120 out -> $0,05812   (98% del coste es la entrada)
    29.333 in / 1.108 out -> $0,06975
    30.176 in / 1.530 out -> $0,07565
    31.030 in / 1.563 out -> $0,07769

Los mismos ~28.000 tokens de contexto se reenviaban enteros y a precio
completo en cada mensaje. El modelo no era el problema: pagar cuatro veces por
el mismo texto lo era.

## El campo que casi lo estropea

`langchain-anthropic` 1.6.1 **no rellena `cache_creation`** cuando escribe la
caché: deja esa clave en cero y pone los tokens escritos en
`ephemeral_5m_input_tokens`. Capturado de la API real el 2026-09-03, primer
mensaje de una conversación:

    {"input_tokens": 22673, "output_tokens": 117,
     "input_token_details": {"cache_read": 0, "cache_creation": 0,
                             "ephemeral_5m_input_tokens": 22655,
                             "ephemeral_1h_input_tokens": 0}}

Leer sólo `cache_creation` habría cobrado esos 22.655 tokens a tarifa normal
en vez de a 1,25x -- un 25% de menos, en silencio, y en el único sitio donde
el error se descubre leyendo la factura. Es la misma clase de fallo que este
repo lleva encontrando desde v4: un campo que el consumidor no rellena, un
valor por defecto, y un número equivocado que nadie ve.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import core.usage_ledger as ul  # noqa: E402
from services.ai.model_config import ModelSelector  # noqa: E402

# Los DOS payloads reales, tal cual los devolvió la API el 2026-09-03.
ESCRITURA = {"claude-sonnet-5": {
    "input_tokens": 22673, "output_tokens": 117, "total_tokens": 22790,
    "input_token_details": {"cache_read": 0, "cache_creation": 0,
                            "ephemeral_5m_input_tokens": 22655,
                            "ephemeral_1h_input_tokens": 0}}}

LECTURA = {"claude-sonnet-5": {
    "input_tokens": 24020, "output_tokens": 733, "total_tokens": 24753,
    "input_token_details": {"cache_read": 23222, "cache_creation": 0,
                            "ephemeral_5m_input_tokens": 0,
                            "ephemeral_1h_input_tokens": 0}}}


def _anotar(usage: dict) -> dict:
    with patch.object(ul, "_append"):
        return ul.record_call("t", "chat", usage)


class ElPrecioDeCadaCuboDeTokens(unittest.TestCase):
    def test_sin_cache_el_precio_es_el_de_siempre(self):
        """Todo lo que no cachea -- que es todo lo demás de la app -- tiene que
        seguir costando exactamente lo mismo que antes de que esto existiera."""
        self.assertAlmostEqual(
            ModelSelector.estimate_cost_usd("claude-sonnet-5", 30_000, 1_500),
            (30_000 * 2.00 + 1_500 * 10.00) / 1_000_000, places=9,
        )

    def test_leer_la_cache_cuesta_la_decima_parte(self):
        con = ModelSelector.estimate_cost_usd("claude-sonnet-5", 10_000, 0, cache_read_tokens=10_000)
        sin = ModelSelector.estimate_cost_usd("claude-sonnet-5", 10_000, 0)
        self.assertAlmostEqual(con, sin * 0.10, places=9)

    def test_escribirla_cuesta_un_25_por_ciento_mas(self):
        con = ModelSelector.estimate_cost_usd("claude-sonnet-5", 10_000, 0, cache_creation_tokens=10_000)
        sin = ModelSelector.estimate_cost_usd("claude-sonnet-5", 10_000, 0)
        self.assertAlmostEqual(con, sin * 1.25, places=9)

    def test_la_de_una_hora_cuesta_el_doble(self):
        con = ModelSelector.estimate_cost_usd("claude-sonnet-5", 10_000, 0, cache_creation_1h_tokens=10_000)
        sin = ModelSelector.estimate_cost_usd("claude-sonnet-5", 10_000, 0)
        self.assertAlmostEqual(con, sin * 2.00, places=9)

    def test_los_tokens_frescos_se_cobran_aparte_de_los_cacheados(self):
        """`input_tokens` trae el TOTAL, caché incluida. Cada cubo a su tarifa."""
        c = ModelSelector.estimate_cost_usd("claude-sonnet-5", 10_000, 0, cache_read_tokens=9_000)
        esperado = (1_000 * 2.00 + 9_000 * 2.00 * 0.10) / 1_000_000
        self.assertAlmostEqual(c, esperado, places=9)

    def test_si_la_cache_supera_la_entrada_no_sale_un_numero_negativo(self):
        """Guarda, no suposición: si algún proveedor reportara `input_tokens`
        SIN la caché dentro, esto cobra de menos en vez de dar un negativo. De
        las dos formas de equivocarse, la que avisa es la que deja el total
        por debajo."""
        c = ModelSelector.estimate_cost_usd("claude-sonnet-5", 100, 0, cache_read_tokens=9_000)
        self.assertGreater(c, 0)


class ElLibroLeeElCampoQueLaLIBRERIARellenaDeVerdad(unittest.TestCase):
    def test_EL_FALLO_una_escritura_se_cobra_a_1_25x_aunque_cache_creation_sea_cero(self):
        """El payload real. `cache_creation` viene a 0 y los 22.655 tokens
        escritos están en `ephemeral_5m_input_tokens`."""
        r = _anotar(ESCRITURA)
        self.assertEqual(r["cache_creation_tokens"], 22_655)
        esperado = (18 * 2.00 + 22_655 * 2.00 * 1.25 + 117 * 10.00) / 1_000_000
        self.assertAlmostEqual(r["cost_usd"], round(esperado, 6), places=6)

    def test_una_escritura_cuesta_MAS_que_no_cachear_y_eso_es_correcto(self):
        """El primer mensaje sale un ~24% más caro. No es un fallo: es lo que
        se paga por que los siguientes bajen un 75%. Con una pregunta suelta
        se pierde; con el atleta curioso que manda diez, se gana."""
        con = _anotar(ESCRITURA)["cost_usd"]
        sin = ModelSelector.estimate_cost_usd("claude-sonnet-5", 22_673, 117)
        self.assertGreater(con, sin)
        self.assertLess(con, sin * 1.30)

    def test_EL_FALLO_una_lectura_baja_el_coste_tres_cuartos(self):
        r = _anotar(LECTURA)
        self.assertEqual(r["cache_read_tokens"], 23_222)
        sin = ModelSelector.estimate_cost_usd("claude-sonnet-5", 24_020, 733)
        self.assertLess(r["cost_usd"], sin * 0.30)

    def test_la_conversacion_entera_sale_a_cuenta(self):
        """Lo que de verdad se decidió: cinco mensajes, que es lo que manda un
        atleta curioso de una sentada."""
        con = _anotar(ESCRITURA)["cost_usd"] + 4 * _anotar(LECTURA)["cost_usd"]
        sin = (ModelSelector.estimate_cost_usd("claude-sonnet-5", 22_673, 117)
               + 4 * ModelSelector.estimate_cost_usd("claude-sonnet-5", 24_020, 733))
        self.assertLess(con, sin * 0.50, f"con caché ${con:.4f} vs sin ${sin:.4f}")

    def test_si_algun_dia_rellenan_cache_creation_se_usa_esa(self):
        """La clave estándar va por delante, para que esto siga siendo correcto
        cuando una versión futura de langchain la rellene."""
        futuro = {"claude-sonnet-5": {
            "input_tokens": 1_000, "output_tokens": 10,
            "input_token_details": {"cache_read": 0, "cache_creation": 900,
                                    "ephemeral_5m_input_tokens": 900}}}
        self.assertEqual(_anotar(futuro)["cache_creation_tokens"], 900)

    def test_lo_que_no_cachea_sigue_anotandose_igual_y_sin_ceros_raros(self):
        viejo = {"claude-sonnet-5": {"input_tokens": 5_000, "output_tokens": 200}}
        r = _anotar(viejo)
        self.assertEqual(r["cache_read_tokens"], 0)
        self.assertEqual(r["cache_creation_tokens"], 0)
        self.assertAlmostEqual(
            r["cost_usd"], round(ModelSelector.estimate_cost_usd("claude-sonnet-5", 5_000, 200), 6),
            places=6,
        )


class SoloSeCacheaDondeSeEntiende(unittest.TestCase):
    """`cache_control` es un parámetro PROPIO de Anthropic. Mandárselo a Gemini
    o a OpenAI no lo entienden; mandárselo por OpenRouter es peor, porque ese
    camino descarta los parámetros del proveedor -- se aceptaría sin error y
    sin cachear, o sea pagando el 25% extra de una caché que nadie va a leer."""

    def test_los_modelos_de_anthropic_si(self):
        for m in ("claude-sonnet", "claude-haiku", "claude-opus"):
            with self.subTest(modelo=m):
                self.assertTrue(ModelSelector.habla_anthropic_directo(m))

    def test_los_demas_no(self):
        for m in ("gpt-5", "gpt-5-mid", "gemini-3.1-flash-lite", "gemini-3.5-flash"):
            with self.subTest(modelo=m):
                self.assertFalse(ModelSelector.habla_anthropic_directo(m))

    def test_sin_clave_de_anthropic_tampoco(self):
        """Sin ANTHROPIC_API_KEY la llamada cae a OpenRouter."""
        from services.ai import model_config
        cfg = model_config.get_config()
        with patch.object(model_config, "get_config") as fake:
            fake.return_value = type("C", (), {**{k: getattr(cfg, k, None) for k in dir(cfg)
                                                  if not k.startswith("_")},
                                               "anthropic_api_key": ""})()
            self.assertFalse(ModelSelector.habla_anthropic_directo("claude-sonnet"))

    def test_un_modelo_que_no_existe_no_revienta(self):
        self.assertFalse(ModelSelector.habla_anthropic_directo("no-existe"))


class ElChatMarcaElPrefijoEstable(unittest.TestCase):
    """El mensaje de sistema lleva el prompt, el entrenador y TODO el contexto
    del atleta; los turnos van después. Ese orden ya existía -- es lo que hace
    que no hubiera que reordenar nada para cachear."""

    def test_el_bloque_de_sistema_pide_cache_cuando_corre_en_anthropic(self):
        import asyncio

        from services.ai import coach_chat as cc

        capturado = {}

        class _LLM:
            async def ainvoke(self, messages):
                capturado["m"] = messages
                return type("R", (), {"content": "vale"})()

        with patch.object(cc.ModelSelector, "get_llm", return_value=_LLM()), \
             patch.object(cc, "build_coach_context",
                          return_value={"context": "CONTEXTO", "stats": {"chars": 9, "approx_tokens": 2,
                                                                        "runs_included": 0}}), \
             patch.object(cc.ModelSelector, "habla_anthropic_directo", return_value=True):
            asyncio.run(cc.ask_coach(Path("."), {}, "hola"))

        sistema = capturado["m"][0]["content"]
        self.assertIsInstance(sistema, list, "el sistema tiene que ir en bloques para poder cachearse")
        self.assertEqual(sistema[0]["cache_control"], {"type": "ephemeral"})
        self.assertIn("CONTEXTO", sistema[0]["text"])
        # Y el turno del atleta va DESPUÉS, fuera del prefijo cacheado.
        self.assertEqual(capturado["m"][-1], {"role": "user", "content": "hola"})

    def test_en_un_modelo_que_no_es_de_anthropic_va_texto_plano(self):
        import asyncio

        from services.ai import coach_chat as cc

        capturado = {}

        class _LLM:
            async def ainvoke(self, messages):
                capturado["m"] = messages
                return type("R", (), {"content": "vale"})()

        with patch.object(cc.ModelSelector, "get_llm", return_value=_LLM()), \
             patch.object(cc, "build_coach_context",
                          return_value={"context": "CONTEXTO", "stats": {"chars": 9, "approx_tokens": 2,
                                                                        "runs_included": 0}}), \
             patch.object(cc.ModelSelector, "habla_anthropic_directo", return_value=False):
            asyncio.run(cc.ask_coach(Path("."), {}, "hola"))

        self.assertIsInstance(capturado["m"][0]["content"], str)


if __name__ == "__main__":
    unittest.main()
