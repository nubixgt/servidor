"""Plan Maestro v6 Track 5 -- what each tier pays for, and why.

The governing rule, which came out of measuring a real generation rather than
reasoning about one:

    **Pay for a model where there is JUDGEMENT. Never where there is only
    COMPRESSION.**

The activity summarizer received 62,889 input tokens and emitted 1,585. It
compresses 40:1 and makes no coaching call at all. Running the three
summarizers on Claude Sonnet cost $0.28 against $0.02 on
gemini-3.1-flash-lite -- eleven times the price to reformat a JSON payload,
and the single worst trade in the Pro tier as it was configured.

The token counts below are solved from the real run's reported cost and each
model's price pair (`in*rate_in + out*rate_out = cost`, with `in + out =
tokens`). They reproduce the measured Lite total of $0.2219 to the fourth
decimal, which is what makes them safe to project other tiers from.

Also pinned here: **every configured model has a price**. Six models
(gpt-4.5, gpt-5-mini, three DeepSeek variants, grok-4) were reachable from
`CONFIGURATIONS` with no row in the pricing table, so using any of them would
have reported the whole generation as free with nothing but a WARNING. They
were removed rather than given invented prices, and this file fails if the two
tables ever drift apart again.

Run with:  python -m unittest discover -s tests -v
"""

import ast
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from core.config import AIMode  # noqa: E402
from services.ai.ai_settings import AgentRole, AISettings  # noqa: E402
from services.ai.model_config import ModelSelector  # noqa: E402

MODEL_CONFIG_SRC = (
    Path(__file__).resolve().parents[1] / "services/ai/model_config.py"
).read_text(encoding="utf-8")

# Solved from the measured week generation (2026-08-25, Lite, $0.2219).
SUMMARIZERS = (76_620, 3_363)
EXPERTS = (7_765, 2_839)
WORKOUT = (14_952, 18_797)


def usd(model_key: str, tokens: tuple[int, int]) -> float:
    rate_in, rate_out = ModelSelector.MODEL_PRICING_USD_PER_MILLION[model_key]
    return (tokens[0] * rate_in + tokens[1] * rate_out) / 1_000_000


class EveryConfiguredModelHasAPrice(unittest.TestCase):
    """v6 finding A9. A model with no pricing row costs $0 in the report and
    real money on the bill, and says so only in a log line nobody reads."""

    def test_the_two_tables_hold_exactly_the_same_keys(self):
        configured = set(ModelSelector.CONFIGURATIONS)
        priced = set(ModelSelector.MODEL_PRICING_USD_PER_MILLION)
        self.assertEqual(
            configured - priced, set(),
            "configurados sin precio -- añade su fila o quítalos de CONFIGURATIONS",
        )
        self.assertEqual(
            priced - configured, set(),
            "con precio pero no configurables -- filas muertas en la tabla de precios",
        )

    def test_the_removed_models_are_really_gone(self):
        for key in ("gpt-4.5", "gpt-5-mini", "deepseek-chat", "deepseek-reasoner",
                    "deepseek-v3.2", "grok-4"):
            with self.subTest(model=key):
                self.assertNotIn(key, ModelSelector.CONFIGURATIONS)

    def test_no_model_is_declared_twice(self):
        """`gemini-2.5-pro` was declared twice -- once via OpenRouter and once
        direct. Python keeps the last, so one definition was silently dead."""
        tree = ast.parse(MODEL_CONFIG_SRC)
        for node in ast.walk(tree):
            if not isinstance(node, ast.AnnAssign) or getattr(node.target, "id", None) != "CONFIGURATIONS":
                continue
            keys = [k.value for k in node.value.keys if isinstance(k, ast.Constant)]
            duplicates = {k for k in keys if keys.count(k) > 1}
            self.assertEqual(duplicates, set(), f"claves duplicadas en CONFIGURATIONS: {duplicates}")
            return
        self.fail("No se encontró la declaración de CONFIGURATIONS")

    def test_no_tuning_entry_survives_its_model(self):
        """MODEL_CONFIGS holds per-model call tuning. An entry for a model
        that no longer exists is dead weight that reads as live config."""
        orphans = set(ModelSelector.MODEL_CONFIGS) - set(ModelSelector.CONFIGURATIONS)
        self.assertEqual(orphans, set(), f"tuning huérfano en MODEL_CONFIGS: {orphans}")

    def test_every_price_is_a_plausible_pair(self):
        for key, rates in ModelSelector.MODEL_PRICING_USD_PER_MILLION.items():
            with self.subTest(model=key):
                self.assertEqual(len(rates), 2)
                self.assertGreater(rates[0], 0)
                # Output always costs more than input, on every provider.
                self.assertGreater(rates[1], rates[0])


class TheFormatterRoleIsNoLongerFunded(unittest.TestCase):
    """Both formatter nodes have been deterministic since v5 Track 9. A tier
    that assigned them a model was paying for a model nobody asks for."""

    def test_no_tier_assigns_it(self):
        settings = AISettings(mode=AIMode.PRO)
        for mode, assignments in settings.model_assignments.items():
            with self.subTest(mode=mode.value):
                self.assertNotIn(AgentRole.FORMATTER, assignments)

    def test_asking_for_it_fails_loudly_instead_of_guessing(self):
        settings = AISettings(mode=AIMode.PRO)
        with self.assertRaises(RuntimeError) as caught:
            settings.get_model_for_role(AgentRole.FORMATTER)
        self.assertIn("formatter", str(caught.exception))
        self.assertIn("ai_settings.py", str(caught.exception))

    def test_every_role_a_node_actually_asks_for_still_resolves(self):
        for mode in (AIMode.STANDARD, AIMode.PRO):
            settings = AISettings(mode=mode)
            for role in AgentRole:
                if role is AgentRole.FORMATTER:
                    continue
                with self.subTest(mode=mode.value, role=role.value):
                    self.assertIn(settings.get_model_for_role(role), ModelSelector.CONFIGURATIONS)


class ProPaysForJudgementNotCompression(unittest.TestCase):
    def _pro(self, role):
        return AISettings(mode=AIMode.PRO).get_model_for_role(role)

    def test_the_summarizers_run_on_the_cheap_model(self):
        """40:1 compression with no coaching call in it. Eleven times the
        price bought nothing here."""
        self.assertEqual(self._pro(AgentRole.SUMMARIZER), "gemini-3.1-flash-lite")

    def test_the_experts_keep_the_frontier_model(self):
        """This is where reading turns into judgement, and it is small enough
        that the premium is cheap in absolute terms."""
        for role in (AgentRole.METRICS_EXPERT, AgentRole.PHYSIOLOGY_EXPERT, AgentRole.ACTIVITY_EXPERT):
            with self.subTest(role=role.value):
                self.assertEqual(self._pro(role), "claude-sonnet")

    def test_synthesis_dropped_web_search_it_never_needed(self):
        """Same price, less latency: v5 (H7) established that synthesis feeds
        the athlete's report and neither planner."""
        self.assertEqual(self._pro(AgentRole.SYNTHESIS), "gpt-5")

    def test_the_season_planner_keeps_it(self):
        """The one role where a live lookup earns its latency -- current
        periodization methodology -- and it runs once per 16-week block."""
        self.assertEqual(self._pro(AgentRole.SEASON_PLANNER), "gpt-5-search")

    def test_swapping_the_summarizers_is_where_the_saving_came_from(self):
        """Las cifras absolutas se reancoraron el 02-09-2026.

        Eran $0.2803 / $0.0242, medidas cuando Sonnet costaba $3/$15. Anthropic
        dejo el precio de lanzamiento de Sonnet 5 ($2/$10) como definitivo -- la
        subida prevista para el 01-09-2026 se cancelo -- asi que la cifra vieja
        describia un precio que ya no existe.

        Lo que esta prueba protege NO es el numero: es que resumir con el modelo
        caro cuesta un orden de magnitud mas que con el barato, y por eso los
        resumidores no llevan modelo de frontera. Esa relacion (>10x) no se
        movio ni un poco al cambiar el precio, que es justo la senal de que la
        decision de tier no dependia de la cifra exacta.
        """
        expensive = usd("claude-sonnet", SUMMARIZERS)
        cheap = usd("gemini-3.1-flash-lite", SUMMARIZERS)
        self.assertAlmostEqual(expensive, 0.18687, places=4)
        self.assertAlmostEqual(cheap, 0.0242, places=3)
        self.assertGreater(expensive / cheap, 7)

    def test_the_experts_premium_is_small_in_absolute_terms(self):
        self.assertLess(usd("claude-sonnet", EXPERTS), 0.10)

    def test_a_pro_week_costs_less_than_it_did(self):
        """El cambio de resumidor abarato la semana, sin tocar WORKOUT.

        Eran $0.8909 -> $0.6348 con Sonnet a $3/$15; a $2/$10 son los numeros
        de abajo. La comparacion sigue siendo la misma y sigue diciendo lo
        mismo, porque el unico termino que cambia entre `before` y `after` es
        el resumidor -- por eso lo que se comprueba es el AHORRO, y las cifras
        absolutas quedan como referencia de cuanto cuesta una semana hoy.
        """
        before = usd("claude-sonnet", SUMMARIZERS) + usd("claude-sonnet", EXPERTS) + usd("claude-opus", WORKOUT)
        after = usd("gemini-3.1-flash-lite", SUMMARIZERS) + usd("claude-sonnet", EXPERTS) + usd("claude-opus", WORKOUT)
        self.assertAlmostEqual(before, 0.77547, places=4)
        self.assertAlmostEqual(after, 0.6128, places=4)
        self.assertGreater(before - after, 0.15)


class TheWorkoutNodeDecidesTheTierSPrice(unittest.TestCase):
    """86% of a week's cost, and dominated by OUTPUT: 14,952 in against 18,797
    out. Any decision about this role optimises the output price.

    v6 left this deliberately unchanged, with the arithmetic favouring gpt-5
    and a note that arithmetic is not evidence. v7 ran the bench. Every number
    below is a real billed amount from replaying ONE archived call
    (2026-08-26T15-06-33, call 6, 11,730 input tokens) with inputs frozen:

    | modelo           | costo   | latencia | horas (5.0 prescritas) | válido |
    |------------------|---------|----------|------------------------|--------|
    | gpt-5-mid        | 0.0437  |   82 s   | 5.00                   | sí     |
    | gemini-3.5-flash | 0.0810  |    -     | 5.08                   | sí     |
    | claude-sonnet    | 0.0912  |   28 s   | 2.17 (4 de 7 días)     | sí     |
    | gpt-5 (xhigh)    | 0.1942  |  391 s   | 5.00                   | sí     |
    | claude-opus      | 0.2266  |   71 s   | 7.08 (excede)          | sí     |

    The v6 note was right to be suspicious of the arithmetic, and wrong about
    which way: gpt-5 came in MORE expensive than predicted, not less, because
    `reasoning_effort: xhigh` spent 26,230 output tokens against gemini's
    7,047. The same model at medium effort ("gpt-5-mid") cost a fifth of that
    and produced the week the season phase actually called for.
    """

    def test_it_is_the_dominant_cost_and_output_heavy(self):
        self.assertGreater(WORKOUT[1], WORKOUT[0])
        week = usd("gemini-3.1-flash-lite", SUMMARIZERS) + usd("gemini-3.1-flash-lite", EXPERTS)
        self.assertGreater(usd("gemini-3.5-flash", WORKOUT) / (week + usd("gemini-3.5-flash", WORKOUT)), 0.85)

    def test_the_bench_moved_the_role_off_opus(self):
        """The v6 tripwire said Pro stays on Opus until someone has a replay
        comparison to point at. This is that comparison: Opus prescribed 7.08h
        against a 5.0h budget -- past the validator's +10% tolerance, so it
        would have triggered `weekly_hours_exceeded` and a paid tier-2 repair
        on top of being the most expensive row on the sheet."""
        pro = AISettings(mode=AIMode.PRO)
        self.assertEqual(pro.get_model_for_role(AgentRole.WORKOUT), "gpt-5-mid")

    def test_the_two_gpt5_profiles_are_the_same_model_at_a_different_price(self):
        """`gpt-5-mid` is not a cheaper model, it is the same model thinking
        less. Both must price identically, or the cost report silently lies
        about whichever one it does not know."""
        from services.ai.model_config import ModelSelector

        self.assertEqual(
            ModelSelector.CONFIGURATIONS["gpt-5-mid"].name,
            ModelSelector.CONFIGURATIONS["gpt-5"].name,
        )
        self.assertEqual(
            ModelSelector.MODEL_PRICING_USD_PER_MILLION["gpt-5-mid"],
            ModelSelector.MODEL_PRICING_USD_PER_MILLION["gpt-5"],
        )

    def test_effort_is_a_price_lever_not_a_free_upgrade(self):
        """Measured, not assumed: xhigh billed $0.1942 against medium's
        $0.0437 on the identical prompt, and took 391 seconds against 82. It
        earns that on the season planner (once per 16 weeks); it does not earn
        it on a decision that runs 52 times a year."""
        from services.ai.model_config import ModelSelector

        self.assertEqual(ModelSelector.MODEL_CONFIGS["gpt-5-mid"]["reasoning"]["effort"], "medium")
        self.assertEqual(ModelSelector.MODEL_CONFIGS["gpt-5-search"]["reasoning"]["effort"], "xhigh")
        self.assertEqual(
            AISettings(mode=AIMode.PRO).get_model_for_role(AgentRole.SEASON_PLANNER), "gpt-5-search"
        )


class EncodingIsNotDeciding(unittest.TestCase):
    """The weekly planner makes two calls, and until v7 they paid the same
    price for opposite work. The second one's own prompt says what it is:
    *"The coaching decisions are already made -- your only job is faithful,
    correctly-typed translation, not judgment."*

    It was 46% of the node's bill ($0.0684 of $0.1495 on the measured week).

    Verified against the real Pydantic class through the production path, not
    the archived dict schema:

    | modelo                | costo   | resultado                            |
    |-----------------------|---------|--------------------------------------|
    | claude-haiku          | 0.0283  | serie 5x con recovery_seconds=45     |
    | gemini-3.5-flash      | 0.0684  | serie 5x con recovery_seconds=45     |
    | gemini-3.1-flash-lite | 0.0055  | recovery_seconds=None                |
    """

    def test_pro_splits_the_two_roles(self):
        pro = AISettings(mode=AIMode.PRO)
        self.assertEqual(pro.get_model_for_role(AgentRole.WORKOUT_ENCODER), "claude-haiku")
        self.assertNotEqual(
            pro.get_model_for_role(AgentRole.WORKOUT),
            pro.get_model_for_role(AgentRole.WORKOUT_ENCODER),
        )

    # DEVELOPMENT and COST_EFFECTIVE are exempt from the invariant below, and
    # the exemption is the interesting part. Both are Gemini-only by design
    # (they must run off GEMINI_API_KEY alone), and in a Gemini-only world
    # there is no cheap encoder that is also correct: flash-lite is 4x cheaper
    # than the decision model and drops `recovery_seconds`, so those modes
    # deliberately pay MORE to encode than to decide rather than generate
    # workouts the validator rejects. That inversion is a fact about Gemini's
    # price ladder, not a mistake to fix -- and it is another reason the
    # launch tier is not Gemini-only.
    CUSTOMER_FACING = (AIMode.STANDARD, AIMode.PRO)

    def test_encoding_never_costs_more_than_deciding(self):
        """If a tier we sell ever pays more to encode a decision than to make
        it, something is upside down."""
        for mode in self.CUSTOMER_FACING:
            settings = AISettings(mode=mode)
            decide = usd(settings.get_model_for_role(AgentRole.WORKOUT), WORKOUT)
            encode = usd(settings.get_model_for_role(AgentRole.WORKOUT_ENCODER), WORKOUT)
            with self.subTest(mode=mode.value):
                self.assertLessEqual(encode, decide * 1.5)

    def test_no_tier_encodes_on_the_model_that_dropped_the_recovery(self):
        """gemini-3.1-flash-lite is the cheapest row on the sheet and the one
        that would have put five strides back to back on the athlete's watch:
        it wrote the recovery into the prose description ("5x15 seg
        aceleración, 45 seg rec") and left `recovery_seconds` null. Schema
        valid, unrunnable -- the exact failure v6 closed."""
        for mode in AIMode:
            with self.subTest(mode=mode.value):
                self.assertNotEqual(
                    AISettings(mode=mode).get_model_for_role(AgentRole.WORKOUT_ENCODER),
                    "gemini-3.1-flash-lite",
                )


class TheChatIsPricedForHowOftenItRuns(unittest.TestCase):
    """Every other role runs a bounded number of times per generation. This
    one runs as often as the athlete feels like typing, so price per call
    dominates. It borrowed AgentRole.SYNTHESIS until v7 -- the tier's
    report-writing model -- which made a one-line question one of the most
    expensive calls in the app."""

    def test_every_mode_funds_it(self):
        for mode in AIMode:
            with self.subTest(mode=mode.value):
                self.assertTrue(AISettings(mode=mode).get_model_for_role(AgentRole.COACH_CHAT))

    # El contexto REAL del chat, medido sobre el almacenamiento de un atleta de
    # verdad (u2, 2026-09-03): 40.444 caracteres ~= 10.111 tokens. No es una
    # estimación -- se obtuvo llamando a `build_coach_context` y leyendo
    # `stats["approx_tokens"]`. Vale la pena tenerlo aquí porque toda la
    # aritmética de este bloque cuelga de él.
    CONTEXTO_REAL = 10_111
    RESPUESTA_TIPICA = 500
    # Lo que un atleta enganchado escribe en un mes. Deliberadamente generoso:
    # si el techo aguanta con éste, aguanta con el atleta medio.
    MENSAJES_AL_MES = 20

    def test_el_chat_no_puede_volverse_la_linea_principal_de_la_factura(self):
        """La garantía de verdad de este rol, y la que sobrevive al cambio de
        modelo.

        Hasta v7 esto se comprobaba comparándolo con SYNTHESIS ("el chat tiene
        que ser más barato que el modelo del que se copió"). Esa comparación
        dejó de significar nada al subir el chat a claude-sonnet: sonnet cuesta
        $2/$10 y el gpt-5 de synthesis $0,88/$7, así que el chat pasó a ser MÁS
        caro por llamada que el informe -- $0,021 frente a $0,0105 en un
        intercambio de 8.000 tokens. Eso es una consecuencia aceptada del
        cambio, no un fallo, y la prueba antigua sólo servía ya para prohibirlo.

        Lo que sigue importando no es contra quién se compara, sino que un rol
        SIN LÍMITE DE FRECUENCIA no puede comerse la factura. Todos los demás
        corren un número acotado de veces por generación; éste corre las que al
        atleta le apetezca escribir.
        """
        pro = AISettings(mode=AIMode.PRO)
        chat = pro.get_model_for_role(AgentRole.COACH_CHAT)
        al_mes = usd(chat, (self.CONTEXTO_REAL, self.RESPUESTA_TIPICA)) * self.MENSAJES_AL_MES
        # Con claude-sonnet salen $0,50/atleta/mes sobre un total de ~$1,89.
        # El techo de $1,00 deja sitio para que el contexto crezca un poco sin
        # que nadie lo note, y salta antes de que el chat sea la mitad del
        # recibo.
        self.assertLess(al_mes, 1.00, f"{chat} costaría ${al_mes:.2f}/atleta/mes en el chat")

    # Lo que el banco midio de verdad, contra el contexto real de un atleta
    # (u2, 2026-09-03): una conversacion de TRES preguntas, las mismas y en el
    # mismo orden para los dos, con cache en ambos.
    #
    #                    msg1      msg2      msg3     TOTAL
    #   claude-sonnet  $0,06731  $0,01849  $0,01010  $0,09590
    #   gpt-5-mid      $0,01737  $0,00906  $0,00239  $0,02882
    BANCADOS_PARA_EL_CHAT = {"gpt-5-mid": 0.02882, "claude-sonnet": 0.09590}

    def test_el_chat_corre_en_un_modelo_QUE_SE_HA_BANCADO(self):
        """La garantia que sustituye a comparar precios entre proveedores.

        La primera version de esta prueba exigia que el chat costase al menos
        lo que un experto, como forma barata de decir "que no sea mas debil".
        El proxy se rompio en cuanto el chat cambio de proveedor: gpt-5-mid
        ($0,88/$7,00) es mas barato por token que claude-sonnet ($2,00/$10,00)
        y NO es un modelo mas debil. Un precio bajo dentro de una misma familia
        suele significar menos modelo; entre familias distintas no significa
        nada, y una prueba que lo trate como si significara algo prohibe
        exactamente el cambio que hay que poder hacer.

        Asi que se comprueba lo que si es cierto: este rol no se cambia a ojo.
        Cada modelo de la lista de arriba se corrio contra una conversacion
        real y su coste quedo escrito. Si alguien pone otro, esta prueba le
        obliga a bancarlo antes -- que es como se decidieron WORKOUT y
        WORKOUT_ENCODER.
        """
        chat = AISettings(mode=AIMode.PRO).get_model_for_role(AgentRole.COACH_CHAT)
        self.assertIn(
            chat, self.BANCADOS_PARA_EL_CHAT,
            f"'{chat}' no se ha pasado por el banco del chat. Correlo contra una "
            "conversacion archivada y apunta el numero aqui antes de asignarlo.",
        )

    def test_el_modelo_elegido_es_el_mas_barato_de_los_bancados(self):
        """El criterio con el que se eligio, escrito para que se note si se
        rompe: entre modelos cuya calidad el banco no distinguio, decide el
        precio -- porque este rol no tiene techo de frecuencia y lo que se
        compra con la diferencia es poder dejar el chat abierto.

        Si algun dia se elige uno mas caro, sera porque el banco SI encontro
        una diferencia de calidad. Entonces hay que escribirla aqui, no
        borrar la prueba.
        """
        chat = AISettings(mode=AIMode.PRO).get_model_for_role(AgentRole.COACH_CHAT)
        mas_barato = min(self.BANCADOS_PARA_EL_CHAT, key=self.BANCADOS_PARA_EL_CHAT.get)
        self.assertEqual(chat, mas_barato)

    def test_haiku_sigue_sin_ser_un_experto(self):
        """El hecho que la prueba anterior guardaba y que no debe perderse al
        haber sacado a haiku del chat: dejó fuera un campo obligatorio de
        `ReceiverOutputs` por la ruta real de producción, así que no puede
        ocupar un rol de salida estructurada por muy barato que salga."""
        pro = AISettings(mode=AIMode.PRO)
        for role in (AgentRole.METRICS_EXPERT, AgentRole.PHYSIOLOGY_EXPERT, AgentRole.ACTIVITY_EXPERT):
            with self.subTest(role=role.value):
                self.assertNotEqual(pro.get_model_for_role(role), "claude-haiku")


class TheLaunchTierCosts(unittest.TestCase):
    """One tier, frontier reasoning, and a bill that fits a Q20/month
    subscription. The point of v7."""

    def test_a_launch_week_lands_near_what_lite_used_to_cost(self):
        """The measured Lite week was $0.1667 on 2026-08-26. The launch tier
        replaces the planner and the encoder with better-benched models and
        keeps Claude Sonnet on the experts, where the judgement is.

        The guarantee is the one in the class docstring: a month of weeks fits a
        Q20 subscription. The old ceiling of $0.35 per week was calibrated with
        gpt-5.2 at HALF its price ($0.88/$7, the Batch rate; the standard is
        $1.75/$14, corrected 2026-09-23), so it is now written as that guarantee
        instead of a number that only held because the price was wrong."""
        pro = AISettings(mode=AIMode.PRO)
        week = (
            usd(pro.get_model_for_role(AgentRole.SUMMARIZER), SUMMARIZERS)
            + usd(pro.get_model_for_role(AgentRole.METRICS_EXPERT), EXPERTS)
            + usd(pro.get_model_for_role(AgentRole.WORKOUT), WORKOUT)
        )
        semanas_al_mes = 52 / 12
        q20_en_usd = 20 / 7.7  # quetzales por dolar, 2026
        self.assertLess(week * semanas_al_mes, q20_en_usd)

    def test_it_costs_less_than_the_opus_configuration_it_replaces(self):
        base = usd("gemini-3.1-flash-lite", SUMMARIZERS) + usd("claude-sonnet", EXPERTS)
        self.assertLess(base + usd("gpt-5-mid", WORKOUT), base + usd("claude-opus", WORKOUT))


if __name__ == "__main__":
    unittest.main(verbosity=2)


class StandardIsTheTierThatActuallyShipsToAthletes(unittest.TestCase):
    """v8. `AI_MODE=standard` es lo que corre en producción, y nadie lo había
    replicado nunca.

    El puente PISA el `ai_mode` de la base de datos con la variable de entorno
    del servidor (`AiService/main.py::_profile_dict`), y el host lleva
    `AI_MODE=standard` (contexto/operacion/DEPLOYMENT.md §5). Así que la columna dice 'pro' para
    todos los atletas, ninguno lo está, y ese desacuerdo no da ningún error:
    la única forma de verlo era `GET /health` o el nombre de los prompts
    archivados. Descubierto porque las cuentas de OpenAI y Anthropic marcaban
    cero gasto mientras Google facturaba $2.36 con usuarios reales dentro.

    Estas pruebas fijan las tres decisiones que el banco de v8 tomó sobre ese
    mapa, replicando 31 llamadas contra prompts reales archivados.
    """

    ASSIGNMENTS = AISettings(mode=AIMode.STANDARD).model_assignments[AIMode.STANDARD]

    def test_EL_HALLAZGO_the_encoder_errs_short_never_long(self):
        """v8, sobre la MISMA semana archivada, salida idéntica (9 sesiones, 5.75h):

            claude-haiku      $0.0508   6.803 tokens de salida
            gemini-3.5-flash  $0.1504  15.020 tokens de salida  (lo que corría)

        Banco del 2026-09-24 (`banco_codificador.py`, `pares_codificador.py`): 14 semanas
        reales de los atletas 2 y 3, 42 salidas de gemini-3.5-flash-lite contra Haiku a
        temperatura 0, cada sesión distinta juzgada a mano contra la decisión:

            claude-haiku           $0.037  ~8 de 84 sesiones mal: se queda CORTO con
                                           "completa el resto en Z2" (120' -> 30')
            gemini-3.5-flash-lite  $0.015  ~27 de 252 mal: omite strides/progresiones,
                                           "4x20\" progresivos" como 20 METROS, y
                                           "6x(1' ..., 2' Z1)" como 6 x 50' = 332' por 70'

        El 2026-09-23 se había movido a flash-lite con 4 corridas. La garantía que
        importa en el reloj no es el promedio: es que el codificador no alargue una
        sesión cinco horas. Haiku a temperatura 0 falla igual cada vez y hacia abajo.
        """
        self.assertEqual(self.ASSIGNMENTS[AgentRole.WORKOUT_ENCODER], "claude-haiku")
        self.assertEqual(ModelSelector.MODEL_CONFIGS["claude-haiku"].get("temperature"), 0)

    def test_the_cheapest_row_on_the_sheet_stays_out_of_the_encoder(self):
        """`gemini-3.1-flash-lite` produjo una semana que el pipeline ACEPTA
        con CERO horas y cero minutos en todos los deportes -- nueve sesiones
        vacías al reloj del atleta. Es la segunda vez que se mide, en una
        generación distinta de la de v7, donde dejó `recovery_seconds=None`.

        Ya hay una prueba que lo prohíbe en TODOS los tiers; ésta existe para
        que se lea junto al número que lo justifica en standard.
        """
        for modo, mapa in AISettings(mode=AIMode.STANDARD).model_assignments.items():
            with self.subTest(modo=modo):
                self.assertNotEqual(mapa.get(AgentRole.WORKOUT_ENCODER), "gemini-3.1-flash-lite")

    def test_EL_HALLAZGO_the_experts_have_to_be_able_to_doubt(self):
        """9 llamadas por modelo (3 expertos x 3 generaciones). Los tres
        validan siempre y ninguno inventa datos del atleta. Lo que los separa
        es cómo declaran lo que NO saben:

                           $/llam  incertidumbres  evidencia  confianza
            flash-lite     0.0021       2.6            7.6    alta 23/27
            gpt-5-mid      0.0244       8.2           15.0    alta 12, media 15
            claude-sonnet  0.0413       5.8           12.7    alta 11, media 10, baja 5

        flash-lite declara confianza ALTA en el 85% de sus payloads. La
        política de conflicto manda a los planificadores ponderar POR
        EVIDENCIA; un experto que nunca duda desactiva ese mecanismo y una
        inferencia floja llega como premisa. No da error y el plan sale bonito.
        """
        for rol in (AgentRole.METRICS_EXPERT, AgentRole.PHYSIOLOGY_EXPERT, AgentRole.ACTIVITY_EXPERT):
            with self.subTest(rol=rol):
                self.assertNotEqual(self.ASSIGNMENTS[rol], "gemini-3.1-flash-lite")

    def test_the_summarizers_stay_on_a_COMPRESSION_price(self):
        """La regla no cambió: se paga donde hay CRITERIO, no donde hay
        COMPRESIÓN. Comprimen 40:1 y no deciden nada.

        Lo que cambió es cuál es el modelo barato. Banco del 2026-09-23 sobre el
        resumidor real del atleta 2 (traza 13-56-56, llamada 0):

            gpt-6-luna             $0.0192  0 % de números sin respaldo, 3x más datos conservados
            gpt-5.6-luna           $0.0380
            gemini-3.5-flash-lite  $0.1000  13.111 tokens de salida

        Los tres resumidores de un macrociclo: $0.072 en gemini-3.1-flash-lite,
        $0.023 en gpt-6-luna. La garantía es el precio de compresión: por debajo
        del de cualquier experto, en entrada y en salida.
        """
        resumidor = self.ASSIGNMENTS[AgentRole.SUMMARIZER]
        self.assertEqual(resumidor, "gpt-6-luna")
        precio = ModelSelector.MODEL_PRICING_USD_PER_MILLION
        for rol in (AgentRole.METRICS_EXPERT, AgentRole.PHYSIOLOGY_EXPERT, AgentRole.ACTIVITY_EXPERT):
            with self.subTest(rol=rol):
                experto = precio[self.ASSIGNMENTS[rol]]
                self.assertLess(precio[resumidor][0], experto[0])
                self.assertLess(precio[resumidor][1], experto[1])

    def test_the_chat_moved_but_the_notebook_did_NOT(self):
        """Banco del 2026-09-23 sobre el contexto real del chat del atleta 2:
        gpt-6-luna contesta con la misma nota que gemini-3.1-flash-lite, pregunta
        bien ante una molestia, y cuesta $0.002 por mensaje contra $0.006. Pero el
        cuaderno (`notebook.propose_entries`) con Luna perdió 2 de 3 anotaciones,
        entre ellas una molestia 3/10 que el atleta dijo en el chat.

        Por eso el cuaderno tiene rol propio y se queda donde anotaba. La
        garantía: el chat y el cuaderno pueden ir en modelos distintos, y el del
        cuaderno es el que se midió anotando.
        """
        self.assertEqual(self.ASSIGNMENTS[AgentRole.COACH_CHAT], "gpt-6-luna")
        self.assertEqual(self.ASSIGNMENTS[AgentRole.NOTEBOOK], "gemini-3.1-flash-lite")
        fuente = (Path(__file__).resolve().parents[1] / "services/ai/notebook.py").read_text(encoding="utf-8")
        self.assertIn("ModelSelector.get_llm(AgentRole.NOTEBOOK)", fuente)
        self.assertNotIn("get_llm(AgentRole.COACH_CHAT)", fuente)

    def test_the_strength_step_has_its_own_cheap_role(self):
        """El paso de fuerza (S5) usaba el modelo de la decisión: $0.016 por
        semana. Banco del 2026-09-23 con los dos atletas: gpt-6-luna cumple "menos
        series, misma carga" por $0.0007; gpt-5.6-terra y gemini-3.8-flash bajaron
        la intensidad en vez de las series. La decisión de la semana NO se mueve."""
        self.assertEqual(self.ASSIGNMENTS[AgentRole.STRENGTH_STEP], "gpt-6-luna")
        self.assertEqual(self.ASSIGNMENTS[AgentRole.WORKOUT], "gpt-5-mid")
        fuente = (Path(__file__).resolve().parents[1]
                  / "services/ai/langgraph/nodes/weekly_planner_node.py").read_text(encoding="utf-8")
        self.assertIn("ModelSelector.get_llm(AgentRole.STRENGTH_STEP).with_structured_output(pf.AjusteDeFuerza)",
                      fuente)

    def test_the_season_planner_follows_the_request_rule(self):
        """Macrociclo del atleta 2 con su natación pedida, prompt de peticiones del
        2026-09-23: gpt-5.2 siguió proponiendo "progresar gradual" por lo que hacía
        antes en 2 de 2 réplicas; gpt-6-sol cumplió la petición ($0.08-0.11 contra
        $0.12-0.13). Opus 5.5 también, a $0.29-0.40: no compensa."""
        self.assertEqual(self.ASSIGNMENTS[AgentRole.SEASON_PLANNER], "gpt-6-sol")
        self.assertLess(usd("gpt-6-sol", (18_000, 5_500)), usd("claude-opus-5.5", (18_000, 5_500)))

    def test_the_weekly_decision_moved_on_WEAKER_evidence_and_it_is_said(self):
        """La honestidad del expediente importa más que la coherencia.

        Más barato que flash en las dos mediciones (-46% en v7, -18% en v8),
        pero 82s y 132s contra 38s, y el MISMO prompt le sacó 4.369 y 9.925
        tokens de salida en dos corridas. Se movió porque en v7 fue el único
        que produjo una semana COMPLETA y válida de 5,00h (sonnet sacó 4 de 7
        días; opus se pasó del presupuesto y dispara una reparación pagada).

        Este nodo es SECUENCIAL: su latencia se suma entera al reloj. Si la
        generación se alarga de más en el host de 2 GB, es la primera línea
        que hay que devolver a `gemini-3.5-flash`.
        """
        self.assertEqual(self.ASSIGNMENTS[AgentRole.WORKOUT], "gpt-5-mid")
        fuente = (Path(__file__).resolve().parents[1] / "services/ai/ai_settings.py").read_text(encoding="utf-8")
        self.assertIn("la evidencia es más floja y se", fuente)
        self.assertIn("primera línea que hay que devolver a gemini-3.5-flash", fuente)
