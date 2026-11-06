from dataclasses import dataclass, field
from enum import Enum

from core.config import AIMode, get_config


class AgentRole(Enum):
    SUMMARIZER = "summarizer"
    METRICS_EXPERT = "metrics_expert"
    PHYSIOLOGY_EXPERT = "physiology_expert"
    ACTIVITY_EXPERT = "activity_expert"
    SYNTHESIS = "synthesis"
    WORKOUT = "workout"
    # The weekly planner's SECOND call. Split out from WORKOUT in v7 because
    # the two calls do opposite kinds of work and were paying the same price:
    # WORKOUT decides what the athlete trains, WORKOUT_ENCODER turns that
    # decision into Garmin's step schema. Its own prompt says so -- "The
    # coaching decisions are already made -- your only job is faithful,
    # correctly-typed translation, not judgment" -- which is exactly the case
    # this codebase's tier rule says never to pay a frontier price for. It was
    # 46% of the weekly planner's bill.
    WORKOUT_ENCODER = "workout_encoder"
    # The conversational coach. Borrowed SYNTHESIS until v7, which meant the
    # athlete asking "¿por qué el martes es suave?" was answered by the most
    # expensive model in the tier. Unlike every other role here it is
    # UNBOUNDED in frequency -- an athlete can send twenty messages in an
    # evening -- so its price per call matters more than any single answer's
    # depth, and it emits free text, so a model's structured-output
    # reliability is not a constraint on this one.
    COACH_CHAT = "coach_chat"
    # Beta 2.1, S4: lee la rutina de gimnasio que el atleta pega como texto y la
    # pasa a sesiones, grupos y ejercicios (services/ai/rutina_de_fuerza.py). Es
    # COMPRESIÓN, no criterio: no corrige ni mejora nada, y el código comprueba
    # cada línea. Corre una vez por rutina pegada, y el atleta la confirma.
    STRENGTH_ROUTINE_READER = "strength_routine_reader"
    # El paso de fuerza de la semana (Beta 2.1, S5, `nodes/paso_de_fuerza.py`). Usaba
    # el modelo de la decisión; tiene rol propio desde el banco del 2026-09-23 porque
    # su tarea es mucho más estrecha -- elegir dosis y proponer cambios con motivo
    # sobre un programa que el código construye -- y un modelo barato la cumple.
    STRENGTH_STEP = "strength_step"
    # El cuaderno del chat (`notebook.propose_entries`): decide qué de lo que dijo el
    # atleta se anota. Usaba el modelo del chat; tiene rol propio desde el banco del
    # 2026-09-23 porque el modelo que mejor conversa no es el que mejor anota.
    NOTEBOOK = "notebook"
    SEASON_PLANNER = "season_planner"
    FORMATTER = "formatter"


@dataclass
class AISettings:
    mode: AIMode

    # Two customer-facing tiers (STANDARD="Lite", PRO="Pro" — see web_app
    # static/index.html's ai_mode selector, which only shows these two).
    # DEVELOPMENT/COST_EFFECTIVE are internal-only, not exposed in the UI.
    #
    # The split follows two lessons from the original project's author
    # (leonzzz435/garmin-ai-coach): (1) don't run one expensive model on
    # every node — most nodes (summarizers, experts) are large-context
    # *extraction* work where a cheap 1M-context model performs the same as
    # a frontier one; only the 1-2 nodes that produce actual coaching
    # judgment (season/weekly planner, synthesis) benefit from a stronger
    # model, so that's the only place Pro tier spends its budget. (2) Claude
    # is notably strong at digesting large structured data; GPT-5-class
    # models are notably strong at training-science judgment — Pro tier
    # splits along that line rather than using one frontier model everywhere.
    model_assignments: dict[AIMode, dict[AgentRole, str]] = field(
        default_factory=lambda: {
            # DEVELOPMENT: cheapest/fastest iteration for internal testing —
            # entirely Gemini so it runs off a Gemini API key without
            # touching OpenAI/Anthropic spend.
            AIMode.DEVELOPMENT: {
                AgentRole.SUMMARIZER: "gemini-3.1-flash-lite",
                AgentRole.METRICS_EXPERT: "gemini-3.1-flash-lite",
                AgentRole.PHYSIOLOGY_EXPERT: "gemini-3.1-flash-lite",
                AgentRole.ACTIVITY_EXPERT: "gemini-3.1-flash-lite",
                AgentRole.SYNTHESIS: "gemini-3.1-flash-lite",
                AgentRole.WORKOUT: "gemini-3.1-flash-lite",
                # NOT flash-lite, even here. On the v7 bench it wrote a repeat
                # block whose recovery existed only in the prose description
                # ("5x15 seg aceleración, 45 seg rec") with
                # `recovery_seconds=None` -- five strides back to back on the
                # watch. A development mode that produces workouts the
                # validator rejects tests the repair path, not the app.
                AgentRole.WORKOUT_ENCODER: "gemini-3.5-flash",
                AgentRole.COACH_CHAT: "gemini-3.1-flash-lite",
                AgentRole.STRENGTH_ROUTINE_READER: "gemini-3.1-flash-lite",
                AgentRole.STRENGTH_STEP: "gemini-3.1-flash-lite",
                AgentRole.NOTEBOOK: "gemini-3.1-flash-lite",
                AgentRole.SEASON_PLANNER: "gemini-3.1-flash-lite",
            },
            # COST_EFFECTIVE: internal alias, same as DEVELOPMENT today —
            # kept as a distinct mode in case it needs to diverge later
            # (e.g. testing a non-Gemini provider) without touching DEVELOPMENT.
            AIMode.COST_EFFECTIVE: {
                AgentRole.SUMMARIZER: "gemini-3.1-flash-lite",
                AgentRole.METRICS_EXPERT: "gemini-3.1-flash-lite",
                AgentRole.PHYSIOLOGY_EXPERT: "gemini-3.1-flash-lite",
                AgentRole.ACTIVITY_EXPERT: "gemini-3.1-flash-lite",
                AgentRole.SYNTHESIS: "gemini-3.1-flash-lite",
                AgentRole.WORKOUT: "gemini-3.1-flash-lite",
                AgentRole.WORKOUT_ENCODER: "gemini-3.5-flash",  # see DEVELOPMENT
                AgentRole.COACH_CHAT: "gemini-3.1-flash-lite",
                AgentRole.STRENGTH_ROUTINE_READER: "gemini-3.1-flash-lite",
                AgentRole.STRENGTH_STEP: "gemini-3.1-flash-lite",
                AgentRole.NOTEBOOK: "gemini-3.1-flash-lite",
                AgentRole.SEASON_PLANNER: "gemini-3.1-flash-lite",
            },
            # STANDARD: EL TIER QUE CORRE EN PRODUCCIÓN. Ya no es "el de
            # Gemini", y llamarlo así fue lo que lo dejó cuatro meses sin que
            # nadie lo replicara.
            #
            # El host lleva `AI_MODE=standard` (contexto/operacion/DEPLOYMENT.md §5) y el puente
            # PISA el `ai_mode` de la base de datos con esa variable
            # (AiService/main.py::_profile_dict), así que la columna dice 'pro'
            # para todos los atletas y ninguno lo está. Ese desacuerdo es
            # silencioso: la única forma de saber qué corre de verdad es
            # `GET /health` o el nombre de los prompts archivados.
            #
            # Lo que ya no aplica de la nota anterior: decía "es el respaldo
            # SÓLO-GEMINI, para una instalación que tiene GEMINI_API_KEY y nada
            # más... mantenlo funcionando; no lo vendas". Se escribió cuando
            # PRO era lo que corría de cara al usuario. Hoy es al revés, y este
            # mapa es el que escribe los entrenos que un atleta se lleva al
            # reloj. Se replica y se decide con números, igual que PRO.
            # ── v8: los tres nodos que el banco movió ────────────────────────
            #
            # STANDARD dejó de ser "el tier de Gemini" porque es el que corre
            # en PRODUCCIÓN (`AI_MODE=standard` en el host; ver contexto/operacion/DEPLOYMENT.md),
            # y hasta v8 nadie lo había replicado. Se replicaron 31 llamadas
            # contra prompts reales archivados de un atleta -- 3 generaciones
            # distintas para los expertos -- y salieron tres cosas que la
            # etiqueta "barato" tapaba.
            #
            # WORKOUT_ENCODER, sobre la MISMA semana archivada:
            #   claude-haiku      $0.0508  47s  9 sesiones, 5.75h  ← elegido
            #   gemini-3.5-flash  $0.1504  57s  9 sesiones, 5.75h  (lo que corría)
            #   gemini-3.1-flash-lite $0.0087 11s 9 sesiones, **0 HORAS**
            #
            # La semana es IDÉNTICA (mismos minutos por deporte: bici 35,
            # brick 75, carrera 120) por un tercio del precio: gemini-3.5-flash
            # emitió 15.020 tokens de salida contra los 6.803 de haiku, y la
            # salida es lo que se paga. Era la línea más cara de toda la
            # generación, y corría por ser "la barata".
            #
            # Y flash-lite reprodujo, en otra generación distinta, el desastre
            # que v6 cerró: una semana que el pipeline ACEPTA con cero horas y
            # cero minutos en todos los deportes. Nueve sesiones vacías al
            # reloj del atleta. La fila más barata de la hoja, otra vez.
            #
            # EXPERTOS, 9 llamadas por modelo (3 expertos x 3 generaciones).
            # Los tres validan SIEMPRE, y ninguno inventa números del atleta
            # (el auditor da 7,5 / 10,9 / 9,3 % y al mirarlos son falsos
            # positivos: `horizon_days` es un campo del esquema). Lo que
            # distingue es CÓMO DECLARAN LO QUE NO SABEN:
            #
            #                    $/llam  incertidumbres  evidencia  confianza
            #   flash-lite       0.0021       2.6            7.6    alta 23/27
            #   gpt-5-mid        0.0244       8.2           15.0    alta 12, media 15
            #   claude-sonnet    0.0413       5.8           12.7    alta 11, media 10, baja 5
            #
            # flash-lite declara confianza ALTA en el 85% de sus payloads. El
            # esquema define `baja` como "inferido, escaso o viejo -- sobre la
            # EVIDENCIA, no sobre lo seguro que te sientas", y la política de
            # conflicto que reciben los dos planificadores les manda ponderar
            # por evidencia. Un experto que nunca duda DESACTIVA ese mecanismo:
            # todo llega con el mismo peso y una inferencia floja se convierte
            # en premisa. No da error y el plan sale bonito -- es el fallo
            # invisible que ningún validador puede atrapar, y por eso este rol
            # es el que CLAUDE.md llama el diferenciador del producto.
            #
            # Se elige gpt-5-mid y no sonnet: 1,7x más barato, el doble de
            # evidencia, 3x la incertidumbre declarada y los conflictos
            # señalados siempre (3.0/3). La única ventaja medida de sonnet es
            # que llega a decir "baja". Corren en PARALELO, así que los ~40s
            # extra no se suman al reloj de la generación.
            #
            # WORKOUT (la decisión semanal): la evidencia es más floja y se
            # deja dicho. Más barato que flash en las dos mediciones (-46% en
            # el banco de v7, -18% en el de v8) pero 82s y 132s contra 38s, y
            # el MISMO prompt le sacó 4.369 y 9.925 tokens de salida en dos
            # corridas -- los tokens de razonamiento facturan a precio de
            # salida y varían mucho. Se mueve igual porque en el banco de v7
            # fue el único que produjo una semana COMPLETA y válida de 5,00h:
            # claude-sonnet sacó sólo 4 de 7 días y claude-opus se pasó del
            # presupuesto de horas, que dispara una reparación pagada. Este
            # nodo es SECUENCIAL, así que su latencia sí se suma: si la
            # generación se alarga de más en el host de 2 GB, ésta es la
            # primera línea que hay que devolver a gemini-3.5-flash.
            # ── Banco del 2026-09-23: ~156 réplicas sobre prompts reales ─────
            #
            # De los atletas 2 y 3 (José y Jessie), inputs congelados, con los
            # candidatos del día de las tres casas y cada nodo medido con lo que
            # le importa a ESE nodo, no con una nota general. Costos por llamada al
            # precio de lista (gpt-5.2 a $1,75/$14, no al de Batch). Scripts en el
            # scratchpad de la auditoría: banco2.py, eval_decision.py,
            # eval_encoder.py, eval_textos.py, eval_macro.py, banco_chat.py.
            #
            # SE QUEDAN en gpt-5-mid (gpt-5.2):
            #   - WORKOUT, la decisión: $0,12-0,15. Es el único que cumple la
            #     petición del atleta Y mantiene la carrera (>=83 % de su hábito)
            #     en los tres escenarios. Opus 5.5 ($0,31) dejó la carrera al 42 %,
            #     GPT-6 Sol al 21-56 %, gpt-5.6-terra al 0-37 %; Sonnet 5, Gemini
            #     3.1 Pro y 3.8 Flash no cumplieron la petición.
            #   - Los tres expertos: el más concreto al leerlo (6 muestras). Sol
            #     cuesta un 45 % menos pero escribe más escueto; Luna, vago.
            #   - Las preguntas del coach (van con PHYSIOLOGY_EXPERT): 3 útiles
            #     siempre; Sol hizo 1-2 y terra ninguna.
            #
            # CAMBIAN:
            #   - SEASON_PLANNER gpt-5-mid -> gpt-6-sol: $0,12 -> $0,08-0,11. Con
            #     el prompt de peticiones, gpt-5.2 siguió frenando la petición del
            #     atleta "porque hacía menos" en 2 de 2; Sol la cumplió y fue honesto
            #     con la incertidumbre. Opus 5.5 fue excelente, a $0,29-0,40: no
            #     compensa en un nodo que corre una vez por bloque.
            #   - WORKOUT_ENCODER se QUEDA en claude-haiku. El 2026-09-23 pasó a
            #     gemini-3.5-flash-lite con 4 corridas; el banco del 2026-09-24 (14
            #     semanas reales, 42 salidas de flash-lite contra Haiku a temperatura
            #     0, cada sesión juzgada a mano contra la decisión) lo devolvió:
            #     proporción de fallos parecida (~11 % contra ~10 % de sesiones),
            #     pero flash-lite se equivoca HACIA ARRIBA y al azar -- "6x(1' ...,
            #     2' Z1)" como 6 x 50 minutos (332' en el reloj por 70'), un "completa
            #     el resto" de +58', "4x20\" progresivos" como 20 METROS -- y omite
            #     strides y progresiones en ~21 sesiones. Haiku se queda corto cuando
            #     la decisión dice "completa el resto en Z2" (120' -> 30'), siempre
            #     igual: es del contrato de la decisión, no del modelo. Un plan que el
            #     reloj alarga 5 h no se paga con 0,02 USD de ahorro.
            #   - SUMMARIZER gemini-3.1-flash-lite -> gpt-6-luna: 0 % de números
            #     sin respaldo y 3 veces más datos conservados; el macrociclo pasa
            #     de $0,072 a $0,023 en resumidores.
            #   - SYNTHESIS gemini-3.5-flash -> gpt-6-luna: $0,06-0,08 -> $0,002,
            #     informe prudente, 0-2 % sin respaldo (3.8 Flash: 11-21 %).
            #   - STRENGTH_STEP (antes el de WORKOUT) -> gpt-6-luna: $0,016 ->
            #     $0,0007; cumple "menos series, misma carga" con los dos atletas.
            #     terra y 3.8 Flash bajaron la intensidad en vez de las series.
            #   - COACH_CHAT gemini-3.1-flash-lite -> gpt-6-luna: $0,006 -> $0,002
            #     por mensaje, misma nota y pregunta bien ante un dolor.
            #   - NOTEBOOK se QUEDA en gemini-3.1-flash-lite: con Luna se perdieron
            #     2 de 3 anotaciones (ni la molestia 3/10). Por eso tiene rol propio.
            AIMode.STANDARD: {
                AgentRole.SUMMARIZER: "gpt-6-luna",
                AgentRole.METRICS_EXPERT: "gpt-5-mid",
                AgentRole.PHYSIOLOGY_EXPERT: "gpt-5-mid",
                AgentRole.ACTIVITY_EXPERT: "gpt-5-mid",
                AgentRole.SYNTHESIS: "gpt-6-luna",
                AgentRole.WORKOUT: "gpt-5-mid",
                AgentRole.WORKOUT_ENCODER: "claude-haiku",
                AgentRole.COACH_CHAT: "gpt-6-luna",
                AgentRole.STRENGTH_ROUTINE_READER: "gemini-3.1-flash-lite",
                AgentRole.STRENGTH_STEP: "gpt-6-luna",
                AgentRole.NOTEBOOK: "gemini-3.1-flash-lite",
                # El macrociclo: la decision de mas alcance del sistema, 24
                # semanas. Pasa de gemini-3.5-flash a gpt-5-mid decidido con
                # scripts/replay_node.py sobre el prompt real del atleta 2 del
                # 2026-09-11 (traza 2026-09-11T08-52-43), no con la tabla:
                #
                #   gemini-3.5-flash  $0.0745  18.1k in / 5.3k out
                #   gpt-5-mid         $0.0454  18.0k in / 4.2k out, 80 s
                #
                # Mas barato Y mejor en lo que importa: con el mismo prompt,
                # gemini repitio la etiqueta de Garmin como orden ("corregir el
                # deficit de Aerobic High senalado por tu Garmin") y copio las
                # restricciones heredadas; gpt-5-mid confirmo por escrito las
                # respuestas del atleta, puso la meta contra la prediccion
                # ("Garmin te estima 26:47; bajar a 26:00 en 9 dias es
                # exigente"), dijo que observacion resolveria cada
                # contradiccion, y reviso las restricciones en vez de copiarlas.
                # Es secuencial, pero corre una vez por bloque.
                # 2026-09-23: y de gpt-5-mid a gpt-6-sol (ver el banco de arriba).
                AgentRole.SEASON_PLANNER: "gpt-6-sol",
            },
            # PRO. The governing rule, from v6 Track 5:
            #
            #   **Pay for a model where there is JUDGEMENT. Never where there
            #   is only COMPRESSION.**
            #
            # That came out of measuring a real generation rather than
            # reasoning about it. The activity summarizer received 62,889
            # input tokens and emitted 1,585 — it compresses 40:1 and makes no
            # coaching call at all. Running the three summarizers on Claude
            # Sonnet cost $0.264 against $0.022 on gemini-3.1-flash-lite:
            # eleven times the price to reformat a JSON payload, and the
            # single worst trade in the tier as it was configured.
            #
            # Where the money does go:
            #
            # * The three EXPERTS stay on Claude Sonnet. This is where reading
            #   turns into judgement — HRV against a personal baseline, a jump
            #   in long-run distance, whether a week's execution matched its
            #   intent. They are small (~3.6k tokens each, ~$0.028 a call), so
            #   a frontier model here is cheap in absolute terms and the one
            #   place the tier's premium buys something.
            #
            # * SEASON_PLANNER keeps gpt-5-search. It is the only role where
            #   live web search earns its latency — current periodization
            #   methodology — and it runs once per 16-week block.
            #
            # * SYNTHESIS moved from gpt-5-search to plain gpt-5. Identical
            #   price, and v5 (H7) established that synthesis feeds only the
            #   athlete's REPORT, not either planner: nothing downstream of it
            #   needs a web lookup, so the search variant was buying latency.
            #
            # * WORKOUT stays on Claude Opus 5 UNTIL the replay bench says
            #   otherwise (scripts/replay_node.py, v6 Track 1). It is 86% of a
            #   week's cost and output-dominated (14,952 in / 18,797 out on
            #   the measured run), so the decision has to optimise OUTPUT
            #   price — and gpt-5 ($0.88/$7.00) is cheaper than Opus
            #   ($5.00/$25.00) AND cheaper than the gemini-3.5-flash the Lite
            #   tier uses ($1.50/$9.00). It is not switched here because
            #   arithmetic is not evidence: gpt-5 bills reasoning tokens at
            #   output price, and a first probe spent 1,439 output tokens
            #   where Claude spent 538 on the same trivial task. Replay the
            #   node against both on one real archived prompt and change this
            #   line with the number in hand.
            #
            # FORMATTER is absent from every map on purpose — both formatter
            # nodes have been deterministic since v5 Track 9 and no code path
            # asks for the role. See get_model_for_role below.
            #
            # ── v7: what the bench actually said ────────────────────────────
            #
            # Every line below was replayed against ONE archived call from the
            # 2026-08-26T15-06-33 generation, inputs frozen. Prices are the
            # real billed amounts, not estimates.
            #
            # WORKOUT (the weekly DECISION, 11,730 input tokens):
            #   gpt-5-mid       $0.0437   82s   5.00h  válido   ← elegido
            #   gemini-3.5-flash $0.0810   —    5.08h  válido  (lo que corría)
            #   claude-sonnet   $0.0912   28s   2.17h  válido pero SOLO 4 DE 7 DÍAS
            #   gpt-5 (xhigh)   $0.1942  391s   5.00h  válido, 26,230 tok salida
            #   claude-opus     $0.2266   71s   7.08h  EXCEDE el presupuesto de
            #                                          5.0h (+10%) -> dispara
            #                                          weekly_hours_exceeded y
            #                                          una reparación pagada.
            #
            # The two GPT-5 rows are the same model. `xhigh` cost 4.4x more
            # and took 4.8x longer for a week that was not better -- reasoning
            # tokens bill at the output rate, and this node is output-heavy.
            # That is the whole reason "gpt-5-mid" exists in model_config.py.
            #
            # WORKOUT_ENCODER (the FORMATTING call, verified against the real
            # Pydantic class, not the archived dict schema):
            #   claude-haiku          $0.0283  27s  válido, serie 5x con rec=45s
            #   gemini-3.5-flash      $0.0684   —   válido        (lo que corría)
            #   gemini-3.1-flash-lite $0.0055   8s  INVÁLIDO EN LA PRÁCTICA: escribió
            #     "5x15 seg aceleración, 45 seg rec" en la descripción y dejó
            #     `recovery_seconds=None`. Schema-valid, unrunnable -- the exact
            #     failure v6 closed. The cheapest row on the sheet was the one
            #     that would have put five strides back to back on the watch.
            #
            # EXPERTS: kept on claude-sonnet. gpt-5-mid is 2.6x cheaper
            # ($0.0270 vs $0.0703) and both were valid, and on one archived
            # activity_expert call the two reads were genuinely close --
            # Sonnet caught cardiac drift across the 21k (144->161 bpm at
            # stable power) and gave the planner a dated rule; gpt-5-mid
            # documented its evidence better and caught a units
            # inconsistency. One sample of one expert is not enough to move
            # the role that CLAUDE.md calls the product's differentiator, and
            # "cheaper on a tie" is not the same as "as good". Bench all three
            # experts across several archived calls before switching; the
            # saving on the table is ~$0.55 per athlete per month.
            #
            # SEASON_PLANNER keeps gpt-5-search at xhigh: it runs once per
            # 16-week block, so the latency that disqualified xhigh for the
            # weekly decision costs nothing here, and it is the one role where
            # a live lookup (current periodization methodology) earns it.
            #
            # SYNTHESIS sigue SIN PASAR POR EL BANCO. Alimenta sólo el informe
            # del atleta, así que el riesgo es de calidad percibida y no de
            # seguridad, pero sigue siendo una decisión tomada sin medir.
            #
            # ── COACH_CHAT: de claude-haiku a claude-sonnet ──────────────────
            #
            # Estaba en haiku por precio, con este razonamiento: emite texto
            # libre, así que la debilidad en salida estructurada que lo
            # descalificó de los expertos (se dejó `for_season_planner`, un
            # campo obligatorio, por la ruta real de producción) no puede
            # morder aquí. Eso es cierto -- pero dice qué NO le estorba, no que
            # la tarea sea fácil.
            #
            # Lo que ese prompt le pide sostener a la vez: buscar en ~40 KB de
            # contexto bajo un encabezado que el atleta llama de otra forma;
            # separar SUS DATOS (sólo del contexto) de SU OFICIO (conocimiento
            # propio del modelo, que sí debe usar); no negar nunca algo que sí
            # está; preferir la restricción real declarada por encima de una
            # explicación fisiológica que suena mejor; no prometer que anota en
            # el cuaderno; y encima un registro de personaje. Son siete reglas
            # CON PRECEDENCIA ENTRE ELLAS. Por la regla de este archivo, eso es
            # JUICIO, no compresión -- era el único rol que la contradecía.
            #
            # Y es la superficie que más se usa: el plan tarda ~11 minutos y se
            # genera una vez por semana; el chat contesta al momento y no tiene
            # límite de frecuencia.
            #
            # El coste de la duda, medido: el contexto real del chat son 40.444
            # caracteres (~10.111 tokens), así que la ventana de 200K de haiku
            # nunca fue el problema -- se usaba el 5%. Lo que cambia es el
            # precio por llamada: ~$0,019 en haiku ($1/$5) frente a ~$0,038 en
            # sonnet ($2/$10). Con 20 mensajes al mes son $0,38 -> $0,76 por
            # atleta, sobre un total de $1,89/atleta/mes.
            #
            # ── Y de claude-sonnet a gpt-5-mid: AHORA SÍ, medido ────────────
            #
            # Una conversación de tres preguntas contra el contexto real de un
            # atleta (u2), las mismas preguntas y en el mismo orden para los
            # dos, con caché en ambos:
            #
            #                      msg1      msg2      msg3     TOTAL
            #   claude-sonnet   $0,06731  $0,01849  $0,01010  $0,09590
            #   gpt-5-mid       $0,01737  $0,00906  $0,00239  $0,02882
            #
            # 3,3 veces más barato. Y una parte grande no es la tarifa sino el
            # TOKENIZADOR: el mismo contexto son 23.251 tokens para Sonnet y
            # 14.748 para GPT-5, un 37% menos. Sobre texto en español eso se
            # paga en cada mensaje.
            #
            # CALIDAD: empate, con fuerzas distintas. Se comprobó lo único que
            # de verdad descalifica -- inventarse un dato del atleta -- citando
            # cada número de las seis respuestas contra el contexto: HRV 59 y
            # rango 56-83, ACWR 1,47 del 23/08, 66,7 km en 30 días, 3x6' a
            # 5:46-6:14/km, bandas de VO2 5:18-5:39/km. Los diez estaban en el
            # contexto; NINGUNO de los dos inventó nada. Los dos contestaron
            # "por qué mi semana es así" con la restricción real (fase 1, 5K
            # del 20/09, una sola calidad por semana) y no con una teoría
            # fisiológica, que es la regla más difícil del prompt.
            #
            # Sonnet ata mejor la fisiología al plan del atleta sin que se lo
            # pidan y escribe en un registro algo más ceñido al de un chat.
            # GPT-5 da MÁS números propios del atleta (dio sus bandas de VO2 de
            # carrera y de natación, que Sonnet no sacó) y resuelve en una
            # línea lo que se contesta en una línea. Ninguna de las dos
            # diferencias vale 3,3 veces el precio.
            #
            # Decide el precio porque el objetivo es no tener que limitar al
            # atleta: este rol no tiene techo de frecuencia, y lo que se está
            # comprando con la diferencia es poder dejar el chat abierto.
            #
            # La caché sigue puesta y sigue siendo correcta: `cache_control` es
            # de Anthropic y se manda sólo ahí (ver
            # ModelSelector.habla_anthropic_directo), y OpenAI cachea SOLO, sin
            # pedirlo -- verificado en el mismo banco, `cache_read` 15.232 en
            # los mensajes 2 y 3. Las dos familias descuentan la lectura al 10%.
            #
            # LO QUE HAY QUE VIGILAR, porque el banco no lo cubre: una
            # conversación, tres preguntas, un atleta. Y GPT-5 cobra los tokens
            # de razonamiento a precio de SALIDA -- aquí fueron 228 en total,
            # despreciables a `effort: medium`, pero es una variable que Sonnet
            # no tiene. Si el chat se alarga o se vuelve más verboso de lo que
            # su guía de estilo pide, mírese ahí antes que en el precio.
            AIMode.PRO: {
                AgentRole.SUMMARIZER: "gemini-3.1-flash-lite",
                AgentRole.METRICS_EXPERT: "claude-sonnet",
                AgentRole.PHYSIOLOGY_EXPERT: "claude-sonnet",
                AgentRole.ACTIVITY_EXPERT: "claude-sonnet",
                AgentRole.SYNTHESIS: "gpt-5",
                AgentRole.WORKOUT: "gpt-5-mid",
                AgentRole.WORKOUT_ENCODER: "claude-haiku",
                AgentRole.COACH_CHAT: "gpt-5-mid",
                AgentRole.STRENGTH_ROUTINE_READER: "gemini-3.1-flash-lite",
                # Los dos roles nuevos del 2026-09-23 conservan en PRO lo que usaban
                # antes de tener rol propio: el de la decisión y el del chat.
                AgentRole.STRENGTH_STEP: "gpt-5-mid",
                AgentRole.NOTEBOOK: "gpt-5-mid",
                AgentRole.SEASON_PLANNER: "gpt-5-search",
            },
        }
    )

    def get_model_for_role(self, role: AgentRole) -> str:
        assignments = self.model_assignments[self.mode]
        try:
            return assignments[role]
        except KeyError:
            # FORMATTER is the live case: both formatter nodes have been
            # deterministic since v5 Track 9, so paying for a model there was
            # paying for nothing. If this fires, a node started asking for a
            # role no tier funds -- decide which model it deserves rather than
            # letting it fall back to whatever is cheapest.
            raise RuntimeError(
                f"El modo {self.mode.value} no tiene modelo asignado para el rol '{role.value}'. "
                "Si un nodo nuevo necesita este rol, añádelo a model_assignments en ai_settings.py."
            ) from None

    @classmethod
    def load_settings(cls) -> "AISettings":
        return cls(mode=get_config().ai_mode)

    def reload(self) -> None:
        self.mode = get_config().ai_mode


# Global settings instance
ai_settings = AISettings.load_settings()
