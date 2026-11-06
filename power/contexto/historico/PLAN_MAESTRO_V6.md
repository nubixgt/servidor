# Plan Maestro v6 — Corregir lo que la primera corrida real expuso, y hacer que Pro valga lo que cuesta

> **Estado al abrir este plan**: v5 cerrado (10 tracks, 525 tests, último commit `47dad4f`).
> Disparado por la **primera generación real end-to-end** (2026-08-25, modo Lite) y su auditoría
> contra la cuenta de Garmin del atleta vía MCP.
>
> **Este documento es autosuficiente.** Contiene los números medidos, la evidencia verificada, las
> rutas de archivo con línea, y el razonamiento económico. No depende de la conversación previa.

---

## 0. Contexto de la corrida auditada

**Generación de temporada** (2026-08-25 20:08–20:09): `$0.2084`, 448.164 tokens, 10 nodos.
**Generación de semana** (2026-08-25 20:19–20:22): `$0.2219`, 124.336 tokens, 8 nodos.
Ambas en **modo Lite** (`gemini-3.1-flash-lite` para summarizers/expertos, `gemini-3.5-flash` para
synthesis/workout/season_planner).

**El resultado fue bueno.** La semana prescrita (descarga de 2,9 h tras una media maratón, cero
intensidad, superficies blandas, natación con pull-buoy, bici Z1) es defendible como coaching. El
chat del coach respondió con los ritmos reales del atleta. El informe salió en español. Nada de lo
que sigue contradice eso: son defectos sobre una base que funciona.

### Datos reales de Garmin al 2026-08-25 (verificados por MCP, no inferidos)

| Métrica | Valor real |
|---|---|
| ACWR | **1,10 — `OPTIMAL`** (acute 287 / chronic 258) |
| Training Readiness | **77 `HIGH` — `WELL_RECOVERED`, recovery_time 0,0 h** |
| HRV anoche / semanal | 63 ms / 61 ms — `BALANCED`, banda personal 55–82 |
| Sleep score | 87 |
| Training status | `MAINTAINING_4`, fitness trend +2 |
| VO2max | 49,0 (preciso 48,7) |
| Load balance | aeróbico bajo 392,6 (`within` 197–459) · **aeróbico alto 592,1 (`above` 284–547)** · anaeróbico 68,4 (`within`) · feedback `AEROBIC_HIGH_FOCUS` |
| Predicciones | 5K **27:15** · 10K 58:26 · HM 2:12:12 · M 4:53:07 |
| Umbral | 5:47/km, LTHR 176 |

### Historial de carrera previo a la media maratón

| Fecha | Sesión | Distancia |
|---|---|---|
| 14 ago | cinta | 3,2 km |
| 17 ago | cinta | 1,7 km |
| 19 ago | calle | 5,1 km |
| 21 ago | calle | 2,9 km |
| **23 ago** | **"Guatemala Carrera"** | **21,2 km — 2h35, FC media 153, ritmo 7:18/km** |

**La tirada más larga previa a un 21k fue de 5,1 km.** Un salto de 4×. Esto es el hecho más
importante de todo el expediente y **no aparece en ningún prompt del sistema**.

---

## Parte A — Bugs verificados

### A1 🔴 El `week_rationale` citó un ACWR que no es el del reloj  
> **Corregido en T1 (2026-08-26)**: el modelo NO lo inventó. Ver «Hallazgos de T1» al final.

El plan le dijo al atleta: *"permitir que tu ACWR baje de un elevado **1.47**"*. El valor real es
**1,10 (`OPTIMAL`)**, y el propio log de la app lo imprimió correctamente antes de generar:
`ACWR actual (Garmin): 1.10`.

**Causa raíz verificada**: `current_acwr` se escribe en `availability_constraints`
(`web_app/server.py:1617-1618`) y **sólo lo lee el validador** (`plan_validator.py:538`). Un grep
sobre `TRAINING_DECISION_USER_PROMPT` confirma que **ni "acwr" ni "carga crónica" aparecen en el
prompt de decisión**. El modelo no lo tenía; lo dedujo del resumen de métricas y se equivocó.

El mismo párrafo afirma "TSB de -20.6", que tampoco es verificable contra ningún endpoint que la app
consulte. Trátalo como inventado hasta que se demuestre lo contrario.

**Impacto**: el atleta abre Garmin, ve `OPTIMAL`, y pierde confianza en todo el plan. Es el defecto
más caro de la corrida aunque no cambió una sola sesión.

### A2 🔴 La política de conflictos se violó de la peor forma posible

Situación real: los **sensores decían "recuperado"** (readiness 77 `HIGH`, recovery_time 0 h, HRV
`BALANCED`, ACWR `OPTIMAL`) mientras el **autorreporte decía "fatigado"** (Hooper 21/28, fatiga 6/7,
molestia 2/10 en ingle).

Eso es exactamente el escenario para el que se construyó `get_conflict_policy()` (v5 Track 1). La
política dice: **nombra la contradicción, nunca elijas un lado en silencio.**

El modelo no eligió un lado en silencio: **fabricó evidencia del lado de los sensores para que
coincidieran con el autorreporte.** Es peor que ignorar la política — es inventar consenso. La
decisión resultante fue correcta, pero por un motivo falso.

### A3 🟠 `goal_realism` nunca se ejecutó

Log: `Meta '5k Monterrico': 25:00 vs prediccion n/d -> sin_referencia` (las tres competencias).
Causa: el campo `distance` de `CompetitionModel` (añadido en v5 Track 2) **está vacío en el perfil
guardado**, que es anterior al campo. El desplegable existe en la UI pero nadie lo llenó.

Consecuencia concreta: la meta de **25:00 en 5K = 5:00/km**, cuando su umbral es 5:47/km y su banda
de VO2max es 5:12–5:33/km. **Correría los 5 km enteros más rápido que su ritmo de intervalos de
VO2max.** El módulo lo habría marcado `fuera_de_rango` automáticamente. En vez de eso el season
planner lo llamó "ambicioso" y construyó 16 semanas alrededor.

### A4 🟠 La adaptación apunta a la estructura equivocada

Check-in declarado: *"Un poco de dolor ingle izquierda despues de la 21k"*.
Adaptación de la sesión de bici: *"Si la **rodilla** izquierda molesta, reduce la resistencia…"*.

Pedalear es flexión de cadera repetida: es el gesto que puede irritar un psoas/aductor. El plan B
está escrito para otra articulación. La molestia del check-in **no está ligada estructuralmente** al
campo `adaptation` de los días afectados; el modelo la reasigna por su cuenta.

### A5 🟠 Aritmética de fechas hecha en prosa

*"superadas las 72 horas del pico del 21k"* para una sesión del **viernes 28**, cuando el 21k fue el
**domingo 23 a las 07:18** — son 5 días, no 72 horas. La dirección es correcta, el número no. Ningún
código calcula "días desde el último esfuerzo máximo"; el modelo lo estima.

### A6 🟠 Garmin publica el balance de carga y la app no lo extrae

`get_training_load_balance` dice **`aerobic_high` = 592 con techo 547 (`above`)**. Es decir: el
atleta tiene exceso de trabajo aeróbico alto y su 21k a 153 ppm (frontera Z2/Z3) lo confirma. La
semana toda en Z1/Z2 corrige eso exactamente — **por casualidad, porque nadie se lo dijo al modelo.**

Verificado: `grep -c "get_training_load_balance" services/garmin/data_extractor.py` → **0**.

### A7 🟠 El salto 5 km → 21 km no existe en ningún prompt

La app conoce las actividades, pero nada calcula ni comunica *"su tirada más larga reciente antes de
la carrera era de 5,1 km"*. Ese es el verdadero factor de riesgo y el mejor argumento para la semana
suave — y no está disponible para ningún agente.

### A8 🟡 El archivo de razonamiento reporta un artefacto faltante que no debería existir

Log: `Reasoning archive 2026-08-25T20-22-07 written with 1 missing artefact(s): synthesis_result`.
El workflow de semana es `3 summarizers → 3 experts → weekly_planner → plan_formatter` — **no tiene
nodo de síntesis**. Es la misma clase de bug que ya se arregló para `week_intents` en corridas de
temporada: `services/ai/reasoning_archive.py` marca `synthesis_result` como faltante sin condicionar
por `kind`.

### A9 🟡 Modelos configurados sin fila de precio

`gpt-5-mini`, `gpt-4.5`, `deepseek-chat`, `deepseek-reasoner`, `deepseek-v3.2`, `grok-4` están en
`CONFIGURATIONS` pero **no en `MODEL_PRICING_USD_PER_MILLION`**. Usar cualquiera reporta **$0** y
loguea sólo un WARNING — el reporte de costo mentiría en silencio. CLAUDE.md ya advierte de esto.

### A10 🟡 Tensión no reconocida entre descarga e hipertrofia

Sus sesiones reales de fuerza duran 38–53 min (17, 18, 20 ago). El plan prescribe 3×20 min. Respeta
la frecuencia que pidió su nutricionista pero recorta a la mitad el volumen, sin decir que hay un
conflicto entre "descargar" y "ganar masa". No es un bug de código: es un conflicto de objetivos que
el sistema no sabe nombrar.

---

## Parte B — Economía real del pipeline (medida, no estimada)

### Desglose de la generación semanal

| Nodo | Modelo | Tokens | Costo | Entrada / Salida* |
|---|---|---|---|---|
| activity_summarizer | gemini-3.1-flash-lite | 64.474 | $0,0181 | **62.889 in** / 1.585 out |
| metrics_summarizer | gemini-3.1-flash-lite | 6.715 | $0,0027 | ~6.548 / ~167 |
| physiology_summarizer | gemini-3.1-flash-lite | 8.794 | $0,0034 | ~8.575 / ~219 |
| activity_expert | gemini-3.1-flash-lite | 3.751 | $0,0020 | mixto |
| metrics_expert | gemini-3.1-flash-lite | 3.580 | $0,0021 | mixto |
| physiology_expert | gemini-3.1-flash-lite | 3.273 | $0,0021 | mixto |
| **weekly_planner** | **gemini-3.5-flash** | **33.749** | **$0,1916** | **14.952 in / 18.797 out** |
| TOTAL | | 124.336 | **$0,2219** | |

\* Despejado del costo reportado y del par de precios del modelo
(`in·rate_in + out·rate_out = costo·1e6`, con `in + out = tokens`). Es aritmética exacta, no estimación.

### Las dos conclusiones que gobiernan todo el diseño de Pro

**1. El `weekly_planner` es el 86% del costo y está dominado por la SALIDA.**
18.797 tokens de salida a $9/M = $0,169 de los $0,1916. Cualquier decisión sobre el rol `WORKOUT`
debe optimizar **precio de salida**, no de entrada.

**2. Los summarizers son un embudo, no un juez.**
El de actividad recibe 62.889 tokens y emite 1.585 — comprime 40:1. **No hay juicio ahí**, hay
extracción fiel. Es el rol donde un modelo caro es puro desperdicio, y el rol donde reducir la
ENTRADA rinde más que cambiar de modelo.

### El costo de la generación de temporada

448.164 tokens por $0,2084. Casi todo entrada de summarizers sobre la ventana de 180 días (168
actividades, v5 Track 12). Barato hoy porque el modelo cuesta $0,25/M de entrada. **Con Claude Sonnet
($3/M) esa misma corrida costaría ~$1,35 sólo en summarizers.**

---

## Parte C — El plan Pro: qué modelo, en qué nodo, y por qué

### Precios configurados en el repo (`model_config.py:313-340`, USD por millón)

| Clave | Modelo real | Entrada | Salida |
|---|---|---|---|
| gemini-2.5-flash-lite | gemini-2.5-flash-lite | 0,10 | 0,40 |
| gemini-3.1-flash-lite | gemini-3.1-flash-lite | 0,25 | 1,50 |
| gemini-2.5-flash | gemini-2.5-flash | 0,30 | 2,50 |
| **gpt-5** | **gpt-5.2** | **0,88** | **7,00** |
| gemini-2.5-pro | gemini-2.5-pro | 1,25 | 10,00 |
| **gemini-3.5-flash** | gemini-3.5-flash | **1,50** | **9,00** |
| gemini-3.1-pro | gemini-3.1-pro-preview | 2,00 | 12,00 |
| claude-haiku | claude-haiku-4-5 | 1,00 | 5,00 |
| claude-sonnet | claude-sonnet-5 | 3,00 | 15,00 |
| claude-opus | claude-opus-5 | 5,00 | 25,00 |
| gpt-5.2-pro | gpt-5.2-pro | 10,50 | 84,00 |

### 🔍 El hallazgo que cambia el trato

**`gpt-5` ($0,88 / $7,00) es MÁS BARATO que `gemini-3.5-flash` ($1,50 / $9,00) en ambas direcciones**
— y es un modelo más capaz. El modo Lite actual está pagando de más por un modelo peor en el nodo más
caro del pipeline.

### Costo proyectado por generación semanal, mismo perfil de tokens

| Configuración de `WORKOUT` | Cálculo | Costo del nodo |
|---|---|---|
| gpt-5 | 14.952×0,88 + 18.797×7,00 | **$0,145** |
| gemini-3.5-flash (Lite hoy) | 14.952×1,50 + 18.797×9,00 | $0,192 |
| claude-sonnet | 14.952×3,00 + 18.797×15,00 | $0,327 |
| claude-opus (Pro hoy) | 14.952×5,00 + 18.797×25,00 | **$0,545** |
| gpt-5.2-pro | 14.952×10,50 + 18.797×84,00 | $1,735 |

| Configuración de summarizers (3 nodos, 79.983 tokens) | Costo |
|---|---|
| gemini-2.5-flash-lite | $0,0086 |
| gemini-3.1-flash-lite (Lite hoy) | $0,0242 |
| claude-sonnet (**Pro hoy**) | **$0,264** |

### Pro actual vs. Pro propuesto

**Pro tal como está configurado hoy** (`ai_settings.py`: summarizers y expertos en `claude-sonnet`,
workout en `claude-opus`, synthesis y season_planner en `gpt-5-search`):

> semana ≈ **$0,89** · temporada ≈ **$1,50+** → ~4× el costo de Lite

**Pro propuesto:**

| Rol | Modelo | Por qué |
|---|---|---|
| `SUMMARIZER` | **gemini-3.1-flash-lite** | Comprime 40:1 sin juicio. Contexto de 1M. Pagar Sonnet aquí cuesta 11× por cero ganancia de calidad. **El peor trato del tier actual.** |
| `METRICS_EXPERT` | **claude-sonnet** | Aquí empieza el juicio. ~3,6k tokens → $0,028. Barato en absoluto, ganancia real. |
| `PHYSIOLOGY_EXPERT` | **claude-sonnet** | Ídem. Lectura de HRV contra línea base personal. |
| `ACTIVITY_EXPERT` | **claude-sonnet** | Ídem. Es el que debe detectar el salto 5 km → 21 km (A7). |
| `SYNTHESIS` | **gpt-5** (sin `-search`) | Sólo alimenta el informe, no a los planificadores (v5 H7). Buen redactor, precio moderado. `-search` es el mismo precio pero añade latencia sin necesidad aquí. |
| `SEASON_PLANNER` | **gpt-5-search** | El único rol donde la búsqueda web vale: metodología de periodización actual. Se ejecuta 1 vez cada 16 semanas. |
| `WORKOUT` | **gpt-5** ← *a validar con el banco de pruebas* | Más barato Y mejor que el `gemini-3.5-flash` actual. Alternativa premium: `claude-sonnet` (+$0,18/semana). |
| `FORMATTER` | *(ninguno)* | Ya es determinista desde v5 Track 9. Debe salir de `model_assignments`. |

> semana ≈ **$0,25** · temporada ≈ **$0,35**

**Un Pro mejor que el Lite de hoy, por prácticamente el mismo precio.** El ahorro no viene de usar
modelos peores: viene de dejar de pagar modelos premium para que hagan compresión.

### Variante "Pro+" (para cuando el resultado justifique el gasto)

`WORKOUT` → `claude-opus` (+$0,40/semana ≈ **$0,65/semana**). Sólo si el banco de pruebas demuestra
una diferencia real en la calidad de la sesión, no antes.

---

## Parte D — Tracks de ejecución

> Orden: **T1 → T2 → T3 → T4 → T5 → T6 → T7**
> T1 y T2 son la condición previa para gastar un solo crédito en Pro con criterio.

### T1 — Banco de pruebas de modelos por réplica (sin generar de nuevo) ✅ COMPLETADO

**El track que hace posible todo lo demás sin quemar créditos.**

v5 Track 7 ya archiva cada corrida completa en `data/storage/<perfil>/reasoning/<ISO>/`, incluyendo
`week_intents.json`. Eso permite **volver a ejecutar un solo nodo** contra el contexto exacto que ya
recibió, con otro modelo, y comparar.

1. Nuevo `scripts/replay_node.py` (o `services/ai/replay.py`):
   `--entry <ISO> --node weekly_planner --model gpt-5|claude-sonnet|gemini-3.5-flash`
2. Reconstruye el prompt desde el trail archivado + `summary.json` + `season_phases.json`.
3. Ejecuta **sólo ese nodo**, guarda salida + costo real + latencia en
   `data/storage/<perfil>/replays/<ISO>__<node>__<model>.json`.
4. Comando de diff que compare N réplicas lado a lado: sesiones prescritas, volumen total, si el
   `week_rationale` cita números verificables, tokens y costo.
5. **Criterio de aceptación**: poder responder "¿gpt-5 escribe una semana mejor que gemini-3.5-flash
   para ESTE atleta?" por **menos de $0,50 en total**, sin tocar la cuenta de Garmin.

### T2 — Los números que el modelo inventó, dárselos ✅ COMPLETADO

Ataca A1, A2, A5, A6, A7 de raíz: **el modelo inventa porque no lo tiene.**

1. **Nuevo bloque `render_load_state()`** en un módulo nuevo `services/ai/load_state.py`, inyectado en
   `TRAINING_DECISION_USER_PROMPT` con placeholder `{load_state}`:
   - ACWR real + su `acwr_status` de Garmin (`OPTIMAL`/etc.), carga aguda y crónica
   - `training_readiness` (score, nivel, `recovery_time_hours`, feedback)
   - `training_status` + `fitness_trend`
   - Balance de carga: aeróbico bajo/alto/anaeróbico con su rango objetivo y `status`
   - **`days_since_hardest_effort` y `longest_run_last_28d` calculados en código**
2. **Extraer lo que falta** en `services/garmin/data_extractor.py`:
   `get_training_load_balance`, `get_training_readiness` (hoy no se llaman).
3. **`current_acwr` deja de vivir sólo en el validador**: pasa al prompt de decisión vía el bloque.
4. **Regla de prosa en el prompt**: *"Todo número que cites en `week_rationale` debe aparecer
   literalmente en este prompt. Si no está, no lo cites — di 'no lo tengo a la vista'."*
5. Reforzar la política de conflictos con el caso real como ejemplo textual:
   *"Ejemplo: readiness 77 HIGH + recovery_time 0 h contra Hooper 21/28 y molestia 2/10 → nómbralo:
   'sistémicamente estás recuperado, pero el tejido no'. **Nunca inventes que los sensores coinciden
   con el autorreporte.**"*
6. Tests: que el bloque contenga los valores reales; que el prompt prohíba números no dados.

### T3 — La molestia del check-in llega atada al día ✅ COMPLETADO

Ataca A4.

1. Cuando el check-in declara `niggle_location`, propagarla al prompt de decisión como **restricción
   explícita por sesión**, no como dato suelto.
2. Instrucción: *"Si hay molestia declarada, el campo `adaptation` de cada día con carga sobre esa
   estructura debe nombrar ESA estructura, no otra. Una adaptación escrita para la rodilla cuando el
   atleta declaró la ingle no sirve."*
3. Considerar un mapa **informativo** (no una regla) de gesto→estructura en el prompt: pedalear
   carga flexores de cadera; patada de crol carga aductores; correr en bajada carga cuádriceps y
   rodilla. Es contexto para el coach, **no un validador**.
4. Test: check-in con "ingle" → el prompt contiene "ingle" en la sección de restricciones del día.

### T4 — Cerrar el hueco de `goal_realism` ✅ COMPLETADO

Ataca A3.

1. **Migración/aviso en UI**: competencia con `target_time` pero sin `distance` → banner en el
   perfil, *"Ponle distancia a esta competencia para que pueda evaluar si la meta es alcanzable"*.
2. Que el bloque de realismo diga explícitamente cuando **no pudo** evaluar y por qué, en vez de
   quedarse callado (hoy emite `sin_referencia` pero el season planner lo ignora).
3. Endurecer el prompt del season planner: *"Si una meta exige un ritmo más rápido que la banda de
   VO2max del atleta, dilo con esas palabras: es fisiológicamente inalcanzable en este bloque, no
   'ambiciosa'."*
4. Test con los números reales: 5K meta 25:00 (5:00/km) vs banda VO2max 5:12–5:33 → debe salir
   marcado, no elogiado.

### T5 — Reasignación de modelos Pro ✅ COMPLETADO (salvo la decisión de `WORKOUT`, que espera al banco)

Depende de T1 para el rol `WORKOUT`. Lo demás es aplicable de inmediato.

1. `services/ai/ai_settings.py::model_assignments[AIMode.PRO]`:
   - `SUMMARIZER` → `gemini-3.1-flash-lite`
   - `SYNTHESIS` → `gpt-5`
   - `WORKOUT` → **lo que gane en T1** (hipótesis: `gpt-5`)
   - expertos y `SEASON_PLANNER` sin cambio
   - **`FORMATTER` fuera del mapa** (determinista desde v5 Track 9)
2. **También revisar Lite**: `WORKOUT` en `gemini-3.5-flash` cuesta más que `gpt-5` y rinde menos.
   Si T1 lo confirma, cambiarlo en los dos tiers.
3. Reescribir el comentario del bloque `PRO` explicando el nuevo criterio: **modelo caro sólo donde
   hay juicio; nunca donde hay compresión.**
4. Arreglar A9: fila de precio para todo modelo en `CONFIGURATIONS`, o eliminarlo. Añadir test que
   falle si un modelo configurado no tiene precio.

### T6 — Reducir la entrada, no sólo el precio ✅ COMPLETADO

62.889 tokens de entrada para 27 actividades es el mayor gasto no-planificador del pipeline, y en la
corrida de temporada son ~400k.

1. Auditar qué se le manda a `activity_summarizer`: podar del payload de Garmin campos que ningún
   agente usa (IDs, `owner_display_name`, nulos repetidos, series por vuelta si las hay).
2. Medir antes/después con el mismo `reasoning/` archivado.
3. **Criterio**: si la salida del summarizer no cambia en contenido decisivo, la poda es gratis.
4. Objetivo razonable: −40% de entrada sin pérdida de señal.

### T7 — Honestidad del archivo y del reporte ✅ COMPLETADO

1. A8: `services/ai/reasoning_archive.py` — `synthesis_result` sólo cuenta como faltante en corridas
   `kind="season"`. Mismo patrón que ya se aplicó a `week_intents`.
2. A10: que el prompt de decisión reciba el **volumen histórico de fuerza del atleta** (38–53 min) y
   nombre el conflicto descarga/hipertrofia en `week_rationale` en vez de recortar en silencio.
3. Revisar que el informe de temporada no se corte: la vista mostró
   *"3. Estructura de Fases del Macrociclo"* sin contenido debajo.

---

## Criterios de aceptación de v6

Una generación real posterior a v6 debe cumplir, verificable contra el MCP de Garmin:

1. **Todo número del `week_rationale` es comprobable.** Cero valores que no estén en el prompt.
2. Cuando sensores y autorreporte se contradigan, **el rationale nombra la contradicción**.
3. Una meta fuera de rango fisiológico **se dice**, con el ritmo requerido contra la banda de VO2max.
4. Una molestia declarada aparece en el `adaptation` de los días que cargan esa estructura.
5. El costo por semana en Pro **≤ $0,30** con expertos Claude y planificador GPT-5.
6. La decisión de modelo del rol `WORKOUT` está respaldada por réplicas comparadas, no por intuición.

## Comandos

```bash
python -m unittest discover -s tests -v          # 525 tests al abrir v6
python run_app.py                                 # o "Iniciar Triatlon 2.0.bat"
py -m uvicorn web_app.server:app --host 127.0.0.1 --port 8010 --reload   # dev, no toca :8000
```


---

## Hallazgos de T1 (2026-08-26)

El banco de pruebas se construyó y, antes de gastar un solo crédito en Pro, ya corrigió dos cosas
de este mismo documento.

### 🔁 Corrección de A1 — el 1.47 no fue inventado

El `week_rationale` citó *"un elevado 1.47"* de ACWR. **Ese número sí estaba en el prompt.** Lo
produjo el propio pipeline:

- `services/garmin/utils/training_metrics.py:88` calcula `acwr_7d28d` con **medias móviles**
  (suma de 7 días ÷ promedio de 28 días).
- El `metrics_summarizer` lo publicó tal cual: *"Máximo `acwr_7d28d`: 1.47 (2026-08-23)"*.
- El `metrics_expert` lo elevó a hallazgo: *"ACWR de 1.31 a 1.47 en la última semana, superando la
  zona de seguridad"*.

Garmin, en cambio, reporta `dailyAcuteChronicWorkloadRatio` = **1.10 el 25 de agosto**, calculado con
**medias exponenciales (EWMA)**. Las dos cifras son correctas bajo su propia definición, y son de
**fechas distintas**: 1.47 es el pico del día de la carrera, 1.10 es el estado dos días después.

**El bug real, entonces, no es alucinación sino que conviven dos ACWR y el atleta ve el que no está
en su reloj, sin fecha ni definición.** Eso cambia el arreglo de T2:

1. El bloque `render_load_state()` debe llevar **los dos**, etiquetados y fechados: *"ACWR Garmin
   (EWMA) hoy: 1.10 OPTIMAL. ACWR propio (media móvil 7/28): pico 1.47 el 23-ago, 1.31 hoy."*
2. La regla de prosa se endurece: **todo número lleva su fuente y su fecha**. "Tu ACWR es 1.47" es
   inaceptable aunque el número esté en el prompt; "tu ACWR llegó a 1.47 el día de la carrera y
   Garmin te marca 1.10 hoy" es lo correcto.
3. `TSB -20.6` viene de la misma fuente propia. Mismo tratamiento.

Sigue en pie que **A1 costó confianza**: el atleta abrió Garmin, vio `OPTIMAL`, y el plan le decía
otra cosa. Sólo cambia dónde está el defecto — en el etiquetado, no en el modelo.

### 🆕 A11 🔴 Toda llamada a OpenAI reportaba costo $0

Encontrado al medir la primera réplica: `gpt-5` devolvió 312 tokens de entrada y 4.761 de salida
por **$0.0000**.

OpenAI responde con un **snapshot fechado** del modelo pedido: se pide `gpt-5.2` y el metadato de
uso dice `gpt-5.2-2025-12-11`. `_find_config_key_for_model_name` hacía coincidencia exacta, no
encontraba nada, y `estimate_cost_usd` devolvía $0 con un WARNING.

**Alcance**: el tier Pro tal como está hoy usa `gpt-5-search` en `SYNTHESIS` y `SEASON_PLANNER`.
Ambos venían reportándose gratis. Cualquier medición de "cuánto cuesta Pro" hecha antes de este
arreglo **subestima el costo real**. Arreglado con una coincidencia por prefijo + sufijo de fecha,
de nombre más largo primero (para que `gpt-5.2-pro-2026-01-01` no caiga en el precio de `gpt-5.2`,
que es 12 veces menor). Cubierto por `tests/test_replay_bench.py::ADatedModelSnapshotStillHasAPrice`.

### 📊 Dato para T5: GPT-5 factura tokens de razonamiento

En la prueba, `gpt-5` gastó **1.439 tokens de salida** donde `claude-sonnet` gastó 538, para la
misma tarea trivial. Son tokens de razonamiento, y se facturan a precio de salida. Como el
`weekly_planner` es un nodo **dominado por la salida** (18.797 de 33.749 tokens), la proyección de
$0.145 para `WORKOUT` en `gpt-5` de la Parte C es **un piso, no un pronóstico**. Hay que medirlo con
una réplica real antes de decidir T5 — que es exactamente para lo que existe el banco.

### Qué quedó construido

| Pieza | Para qué |
|---|---|
| `services/ai/prompt_capture.py` | Callback que archiva el prompt exacto de cada llamada, con su esquema de salida normalizado entre proveedores |
| `services/ai/reasoning_archive.py` (`prompts/`) | Un archivo por llamada + índice; fuera del contexto del chat del coach |
| `services/ai/replay.py` | Vuelve a correr una llamada archivada con otro modelo; resumen por deporte/disciplina, nunca por distancia |
| `services/ai/number_audit.py` | Lint (no validador) de números citados contra lo que el modelo recibió |
| `scripts/replay_node.py` | CLI: `--list`, `--call`, `--model` (repetible), `--compare`. Estima el costo y pide confirmación antes de gastar |
| `tests/test_replay_bench.py` | 38 tests |

**Verificado en vivo** contra los tres proveedores (Gemini, OpenAI, Anthropic): captura dentro de un
workflow real de LangGraph con el nombre de nodo correcto, archivado, réplica cruzada de proveedor y
costo real distinto de cero.

### Cómo usarlo

```bash
# qué llamadas tiene archivadas la última generación
py scripts/replay_node.py --profile "Jose Ines Castillo" --list

# comparar dos modelos en la decisión semanal (estima el costo y pregunta antes)
py scripts/replay_node.py --profile "Jose Ines Castillo"     --call weekly_planner --model gpt-5 --model gemini-3.5-flash
```

> ⚠️ Requiere una generación **posterior** a T1: las tres corridas archivadas del 25-ago no tienen
> prompts guardados. La primera semana que se genere a partir de ahora ya queda replicable.


---

## Hallazgos de T2 (2026-08-26)

### 🔁 Corrección de A7 — el salto no fue de 4×, fue de 2,1×

La auditoría leyó una lista de **14 días** y concluyó que la tirada más larga antes del 21k había sido de
**5,1 km**. Calculado en código sobre la ventana real de **28 días**, aparece un **trail de 10,3 km el 9 de
agosto** — catorce días antes de la carrera. El salto real es **2,1×**, no 4×.

Sigue siendo un salto que vale la pena nombrar. Pero es otra conversación, y la diferencia entre las dos
la produjo exactamente lo que T2 arregla: **mirar a ojo una ventana corta en vez de calcular sobre la
ventana que el resto del sistema usa.**

### 🔁 Corrección de A5 — la sesión más dura no fue la carrera

Ordenando por la carga que **Garmin** asignó a cada sesión (comparable entre deportes, a diferencia de la
distancia):

| Fecha | Sesión | Carga |
|---|---|---|
| 09-ago (hace 17 días) | Panajachel Trail running, 10,3 km / 114 min | **223** |
| 23-ago (hace 3 días) | Guatemala Carrera, 21,2 km / 155 min | 141 |
| 29-jul | Intervalos 90, 5,2 km / 41 min | 100 |

Por eso el bloque reporta **las tres**, no una: *"el esfuerzo más duro"* y *"el último esfuerzo duro"* son
preguntas distintas y una descarga necesita las dos.

### 🔁 Corrección de la Parte D — `get_training_load_balance` no existe en la librería

El plan pedía extraer `get_training_load_balance` y `get_training_readiness` de `data_extractor.py`.
Verificado contra la librería instalada:

- **`garminconnect` no tiene `get_training_load_balance`.** Pero **Garmin lo manda dentro de la respuesta de
  `get_training_status`** (`mostRecentTrainingLoadBalance`), que la app ya pedía y ya cacheaba. Se leía y se
  tiraba. **Cero llamadas nuevas.**
- Lo mismo con el estado del ACWR: `acwrStatus`, `acwrPercent` y el rango crónico que Garmin considera
  óptimo para este atleta venían en el mismo DTO del que sólo se leían tres números.
- `get_training_readiness` **sí** existe, y devuelve **varias lecturas del mismo día** — se recalcula tras
  cada sesión y al despertar. Hoy: **70 `MODERATE` con 9,7 h de recuperación pendiente a las 07:02**, y
  **78 `HIGH` con 0 h a las 04:55**. Colapsarlas a un número escondería cuál está mirando el atleta, así que
  se guardan las dos. `recoveryTime` viene en **minutos** (584 = 9,7 h), no en horas.

### Qué quedó construido

| Pieza | Para qué |
|---|---|
| `services/ai/load_state.py` | Bloque `{load_state}` del prompt de decisión: los dos ACWR etiquetados y fechados, disposición (última + al despertar), estado de entrenamiento, Load Focus con sus rangos, e historial de esfuerzos **por deporte** |
| `data_extractor.py` | `_extract_load_balance` y `_extract_training_readiness`; el DTO de carga aguda ahora entrega también `acwr_status`, `acwr_percent` y el rango crónico óptimo |
| `models.py` | `TrainingStatus.load_balance` y `.training_readiness` |
| `weekly_planner_node.py` | Reglas de prosa: **todo número lleva fuente y fecha**, y lo que no está en el prompt no se cita. Política de conflictos reforzada con el caso real |
| `server.py` | Construye el bloque, lo pasa por `availability_constraints` y lo archiva con la corrida |
| `tests/test_load_state.py` | 34 tests, con los payloads reales de Garmin del 26-ago |

**Verificado en vivo**: el bloque son ~2.790 caracteres (~700 tokens, ≈$0,0006 por semana) y llega entero al
prompt de decisión.

### Lo que T2 deliberadamente NO hizo

- **No borró el ACWR propio de la app.** Habría sido el arreglo fácil y habría destruido la única vista de la
  rampa (0,90 → pico 1,47 → 1,31). Garmin sólo publica un número del día.
- **No añadió ninguna banda de seguridad.** Ni 1,0–1,25, ni ningún umbral. Todo estado del bloque lo asignó
  Garmin o sale de comparar contra un rango que Garmin publica. El ACWR describe carga; su valor como
  predictor de lesión es discutido y depende del método.
- **No convirtió el salto de distancia en una regla.** El bloque lo nombra cuando es ≥1,5× y dice, con esas
  palabras, que describe lo que el atleta hizo y no dicta lo que puede hacer.


---

## Hallazgos de T3 (2026-08-26)

### La instrucción ya existía; lo que faltaba era la exigencia

`render_checkin_block` ya decía *"Dilo en `purpose`/`adaptation` del día afectado"*. El modelo lo hizo — y
escribió una contingencia para la **rodilla** cuando el atleta había declarado la **ingle**, en la sesión de
bici, que es precisamente la que carga los flexores de cadera.

Lo que no existía era la exigencia de que **la estructura nombrada fuera la que el atleta nombró**. Ahora el
bloque lo dice con esas palabras, y dice también por qué importa: *"Una adaptación escrita para otra parte
del cuerpo es peor que no escribir ninguna: le dice al atleta por escrito que nadie leyó lo que puso."*

### Dos fuentes, una restricción

La molestia llegaba por dos caminos que se trataban por separado: el **check-in** (v5 Track 6) y las
**lesiones activas de la ficha de salud** (v5 Track 10). Los dos son el atleta diciendo "esto me duele". Se
juntan en un solo bloque para que no se pueda atender uno y olvidar el otro.

De paso, la instrucción de la ficha de check-in se retiró: estaba dicha en dos sitios de dos formas
distintas y el modelo seguía la más débil.

### El mapa de gestos, y por qué no es un validador

El bloque incluye qué gesto carga qué estructura, cubriendo **carrera (llano/subida/bajada/series), bici,
crol, patada de braza, fuerza, pliometría y brick** — más **qué descarga** cada estructura (pull-buoy,
agua profunda, superficie blanda), para que la salida no sea siempre cancelar.

Es orientación biomecánica y lo dice: **no es diagnóstico y no es una lista cerrada.** Si el atleta declara
algo que no está, se razona igual. Y el bloque prohíbe explícitamente nombrar una lesión, una patología o un
tratamiento que el atleta no haya declarado — recomendar que lo vea un profesional presencialmente sí es
parte del trabajo.

### El lint que casi se equivoca solo

`unaddressed()` reporta, después de generar y **sólo en el log**, que una molestia declarada no aparece en
ninguna sesión. Nunca bloquea.

La primera versión comparaba palabras sueltas y **daba por atendido el fallo real**: *"ingle izquierda"* y
*"rodilla izquierda"* comparten "izquierda". Ahora compara contra un **léxico de estructuras corporales**,
con una lista de palabras que describen *cómo* duele o *de qué lado* — no *qué* duele — excluidas. Si el
atleta escribe algo fuera del léxico, cae a sus propias palabras como mejor esfuerzo.

| Pieza | Para qué |
|---|---|
| `services/ai/niggle_constraints.py` | Recolecta de ambas fuentes, renderiza el bloque `{niggle_constraints}`, y el lint |
| `checkin.py` | Deja de dar su propia instrucción y apunta al bloque |
| `weekly_planner_node.py` / `server.py` | Placeholder, construcción, archivado y el aviso en el log |
| `tests/test_niggle_constraints.py` | 31 tests |

Coste: ~780 tokens **sólo cuando hay algo declarado**; cero si no.


---

## Hallazgos de T4 (2026-08-26)

### La causa exacta, confirmada en el archivo de la corrida

`reasoning/2026-08-25T20-09-30/competitions.json` guarda lo que el season planner recibió de verdad:

```json
{"name": "5k Monterrico",  "distance": "", "race_type": "Olympic", "target_time": "00:25:00"}
{"name": "10k Ciudad...",  "distance": "", "race_type": "Olympic", "target_time": "00:50:00"}
{"name": "21k Ciudad...",  "distance": "", "race_type": "Olympic", "target_time": "01:45:00"}
```

El código hacía `distance or race_type` → **`"Olympic"`**, que es un tipo de carrera de triatlón, no una
distancia de carrera a pie. Garmin no predice "Olympic", así que las tres salieron `sin_referencia` y el
módulo de realismo **nunca se ejecutó**.

Con la deducción de T4, exactamente esas tres competencias archivadas ahora resuelven:

| Competencia | Deducción | Meta | Predicción | Lectura orientativa |
|---|---|---|---|---|
| 5k Monterrico | `5K` (deducida del nombre) | 25:00 | 27:15 | fuera de rango |
| 10k Ciudad de Guatemala | `10K` | 50:00 | 58:26 | muy exigente |
| 21k Ciudad de Guatemala | `half_marathon` | 1:45:00 | 2:12:12 | muy exigente |

### Se revierte una decisión de v5, con motivo

v5 Track 2 decidió **no** deducir la distancia del nombre, citando el patrón de adivinar por palabras clave
que v4 quitó del validador. La primera corrida real mostró el costo de esa cautela: sin deducción, ninguna
meta se evaluó nunca.

Los dos casos no son el mismo riesgo. En el validador, una etiqueta mal leída elegía una **banda de ritmo**,
y una banda equivocada es un entreno peligroso. Aquí elige un **tiempo de referencia** para una comparación
que el bloque marca como deducida y que invita explícitamente a rechazar. La seguridad está en tres
propiedades, todas con test: la distancia declarada siempre gana, el orden va de lo más específico a lo menos
(«media maratón» contiene «maratón»), y no se deduce nada que no esté («Ultra 50K», «15K», «Ironman 70.3» no
matchean).

### Nada de veredictos deterministas — el cambio de rumbo respecto al plan

El plan v6 proponía endurecer el prompt: *"si una meta exige un ritmo más rápido que la banda de VO2max,
dilo con esas palabras: es fisiológicamente inalcanzable"*. **Eso no se implementó, a petición del atleta, y
tenía razón**: un atleta con tres semanas de reloj, con el umbral derivado en vez de medido, o que vuelve de
un parón, puede ser bastante mejor de lo que dice su predicción. Un veredicto duro en el prompt es una
guardarraíl determinista disfrazada.

Lo que entra es **evidencia**, no conclusión:

- **El ritmo que exige la meta, y entre qué bandas suyas cae.** Un hecho:
  *"Ritmo medio que exige la meta: 4:59/km sostenidos. Ese ritmo cae entre su banda de Repeticiones
  (4:52–5:05/km) y su banda de VO2max (5:12–5:33/km)."* Lo que eso significa lo decide el agente.
  (Se reporta **dónde cae**, no la lista de todas las bandas que supera: decir que un objetivo de 5:00/km es
  más rápido que su trote de recuperación es cierto e inútil.)
- **Con cuántos datos se dice todo eso**: número de actividades y su rango de fechas, si el ritmo umbral fue
  medido por el reloj o derivado, si las zonas son las del reloj o de una fórmula, qué nivel se declara el
  atleta y cuántos años lleva, y las advertencias de la prescripción.
- Una instrucción nueva: *"**Pesa la fidelidad de los datos antes que los datos.** Cuando la evidencia sea
  fina, dilo y razona con más margen — no la trates como un hecho, ni tampoco la descartes."*

Y un test que **falla si alguien vuelve a meter el veredicto forzado** en el bloque.

### Cuando no se puede evaluar, se dice — y se distingue de qué

Antes había un solo `sin_referencia`. Ahora son dos problemas distintos con dos mensajes distintos:

- **`distancia_no_identificada`**: nadie puede saber qué distancia es. Lo arregla el atleta en diez segundos,
  y ahora se lo pide el prompt, el log de generación **y un aviso en el propio formulario del perfil**, que
  aparece en cuanto hay tiempo objetivo sin distancia y desaparece al elegirla (verificado en el navegador).
- **`sin_prediccion`**: la distancia está declarada pero Garmin no predice esa prueba. No lo arregla nadie —
  un nadador o un ciclista **nunca** tendrá predicciones, porque son de carrera a pie. El bloque lo dice con
  esas palabras y manda apoyarse en el umbral, en la potencia y en los expertos.

| Pieza | Para qué |
|---|---|
| `goal_realism.py` | `infer_distance`, ritmo exigido vs bandas propias, `build_goal_evidence`, `competitions_missing_distance` |
| `season_planner_node.py` | Le pasa las bandas de ritmo, la evidencia y las etiquetas de banda |
| `server.py` | Construye la evidencia y avisa en el log de las carreras sin distancia |
| `app.js` | Aviso en el formulario, verificado en el navegador |
| `tests/test_goal_realism.py` | 50 tests |


---

## Hallazgos de T5 (2026-08-26)

### La regla que gobierna el tier

> **Se paga un modelo donde hay JUICIO. Nunca donde sólo hay COMPRESIÓN.**

Salió de medir, no de razonar. Los repartos de tokens de abajo se despejan del costo reportado y del par de
precios de cada modelo, y **reproducen el total medido del Lite ($0,2219) hasta la cuarta cifra** — que es lo
que permite proyectar los demás tiers con confianza.

| Bloque | Entrada | Salida |
|---|---|---|
| 3 summarizers | 76.620 | 3.363 |
| 3 expertos | 7.765 | 2.839 |
| weekly_planner | 14.952 | 18.797 |

### Lo que cambió, y lo que no

| Rol | Antes | Ahora | Por qué |
|---|---|---|---|
| `SUMMARIZER` | claude-sonnet | **gemini-3.1-flash-lite** | Comprime 40:1 sin emitir juicio. **$0,2803 → $0,0242**: once veces el precio por reformatear un JSON. |
| Expertos ×3 | claude-sonnet | claude-sonnet | Aquí sí hay juicio, y son pequeños: $0,066 en total. El único sitio donde la prima del tier compra algo. |
| `SYNTHESIS` | gpt-5-search | **gpt-5** | Mismo precio. v5 (H7) dejó establecido que la síntesis alimenta el **informe**, no a los planificadores: nada aguas abajo necesita una búsqueda web, así que `-search` sólo compraba latencia. |
| `SEASON_PLANNER` | gpt-5-search | gpt-5-search | El único rol donde la búsqueda vale la latencia, y corre una vez cada 16 semanas. |
| `WORKOUT` | claude-opus | **claude-opus (sin cambiar)** | Ver abajo. |
| `FORMATTER` | gemini-3.5-flash | **fuera del mapa** | Los dos nodos formateadores son deterministas desde v5 Track 9. Ningún código pide el rol. |

**Costo de una semana en Pro: $0,8909 → $0,6348** (−29%), sin tocar el nodo más caro y sin cambiar un solo
modelo donde hay juicio.

### Por qué `WORKOUT` NO se cambió

Es el 86% del costo de la semana y está **dominado por la salida** (14.952 entrada / 18.797 salida), así que
la decisión se optimiza contra el precio de salida. La aritmética favorece a `gpt-5` ($0,88/$7,00) contra el
Opus de Pro ($5,00/$25,00) **y** contra el `gemini-3.5-flash` de Lite ($1,50/$9,00) — más barato en las dos
direcciones que cualquiera de los dos.

**Pero la aritmética no es evidencia.** GPT-5 factura los tokens de razonamiento a precio de salida, y en las
pruebas en vivo de esta sesión gastó entre 2,5× y 4,7× los tokens de salida de Claude en la misma tarea. Eso
puede borrar la ventaja entera. Se decide con una réplica real, no con una tabla.

| Configuración de `WORKOUT` | Semana en Pro |
|---|---|
| claude-opus (hoy) | $0,6348 |
| **gpt-5 (hipótesis)** | **$0,2348** ← por debajo del techo de $0,30 de v6 |

Y la misma pregunta abierta para Lite: `$0,2220` hoy → `$0,1751` si `gpt-5` gana, con un modelo mejor.

### A9 cerrado: ya no hay ningún modelo sin precio

Seis modelos (`gpt-4.5`, `gpt-5-mini`, tres variantes de DeepSeek, `grok-4`) estaban en `CONFIGURATIONS` sin
fila de precio y sin ningún tier que los alcanzara. Usar cualquiera habría reportado la generación entera
como **$0** con nada más que un WARNING en el log. **Se eliminaron** en vez de inventarles un precio, y ahora
un test falla si las dos tablas se separan otra vez, en cualquiera de las dos direcciones.

De paso: **`gemini-2.5-pro` estaba declarado dos veces** —una vía OpenRouter y otra directa—. Python se queda
con la última, así que una de las dos definiciones llevaba tiempo muerta. Hay un test que revienta si vuelve a
aparecer una clave duplicada.

Y `get_model_for_role` ya no revienta con un `KeyError` cuando un rol no está financiado: dice qué rol, en qué
modo, y dónde arreglarlo.

### El banco ahora trae su propia línea base, gratis

`prompt_capture.py` guarda también **lo que el modelo contestó**, no sólo lo que recibió. Eso convierte cada
llamada archivada en su propia base de comparación: una réplica contra otro modelo se puntúa al lado de lo
que realmente pasó **sin pagar nada**. Antes, para tener contra qué comparar, había que volver a correr el
modelo que ya había corrido.

(Los proveedores vuelven a diferir: Anthropic contesta una llamada estructurada con los argumentos de una
herramienta —ya es un objeto—, y el modo JSON nativo de Google contesta con el JSON como texto del mensaje.
Los dos son el mismo objeto y sólo uno llega pareciéndolo, así que el banco lo normaliza al leerlo.)

### Lo que falta para cerrar el criterio de aceptación #6

Hace falta **una generación real posterior a T1** para tener un prompt replicable. Ninguna de las tres
corridas archivadas del 25-ago lo tiene.

```bash
# 1) generar una semana normal (Lite, ~$0,22) -- deja el primer archivo replicable
#    y de paso ejerce T2/T3/T4 contra datos reales

# 2) decidir WORKOUT con la base gratis incluida
py scripts/replay_node.py --profile "Jose Ines Castillo" \
    --call weekly_planner --model gpt-5 --model claude-opus
```

Costo del paso 2: **~$0,69** (gpt-5 $0,14 + opus $0,54), con la salida real de Gemini como tercera columna
sin costo. Sólo `gpt-5` serían **$0,14** contra esa base.


---

## Auditoría de la segunda corrida real (2026-08-26)

Primera corrida **con prompts archivados** (v6 Track 1), así que por primera vez se puede decir qué recibió
cada nodo y qué contestó, en vez de deducirlo.

**Temporada**: `$0.2077` · 455.272 tokens · 8 llamadas.
**Semana**: `$0.3319` · 151.059 tokens · 9 llamadas (fueron 9, no 8: hubo una reparación).

### 🔴 B1 — El formateador devolvió CERO pasos, los nueve días

Del archivo `reasoning/2026-08-26T11-50-54/prompts/`:

| # | Llamada | Entrada | Salida | Costo |
|---|---|---|---|---|
| 6 | decisión (`TrainingDecisionOutputs`) | 11.694 | 6.383 | $0,0750 |
| 7 | **formateo** (`WeekBlock`) | 6.032 | **11.909** | $0,1162 |
| 8 | **reparación tier-2** (`WeeklyPlanPayload`) | 9.975 | 10.486 | **$0,1093** |

La llamada 7 gastó **11.909 tokens de salida para emitir 4.973 caracteres de JSON** —casi todo fue
pensamiento— y **omitió el campo `steps` en los nueve días**. La reparación determinista los escribió todos.

**Causa raíz, exacta**: `WorkoutDay.steps` se declara con `default_factory=list`, así que **no aparece en la
lista `required` del esquema JSON**. El prompt le decía al modelo que los pasos eran obligatorios; el esquema
le decía que eran opcionales. Le creyó al esquema.

**Lo que costó**: `$0,1093` de `$0,3319` — **el 33% de la semana** — en una segunda llamada haciendo el
trabajo entero de la primera. Y algo peor que el dinero: **la red de seguridad estaba escribiendo el plan.**
Una red que se dispara todas las veces no es una red, es el generador con peor prompt.

**Arreglo**: `FormattedWorkoutDay`, un `WorkoutDay` donde `steps` es obligatorio, usado **sólo** como esquema
de salida del formateador. Un día de descanso sigue llevando `steps: []`; lo que cambia es que el modelo
tiene que **decirlo** en vez de callarse. El `WorkoutDay` base conserva su default, porque todos los demás
sitios que construyen un día (los respaldos del validador, los planes guardados, las vistas imprimibles)
legítimamente no pasan pasos.

### 🔴 B2 — Un `repeat` sin `substeps` tumba la llamada entera, y no es transitorio

Al probar el arreglo de B1 contra **el prompt archivado exacto**, el modelo sí emitió pasos... y escribió
esto en la sesión de VO2max:

```json
{"step_order": 2, "step_type": "repeat", "repeat_count": 5,
 "description": "5x(400m a ritmo VO2max, 2' recuperación)"}
```

Sin `substeps`. El validador de Pydantic lo rechaza, la llamada entera se descarta, y **volvió a pasar tres
veces seguidas** con reintento ciego.

Es el mismo patrón que B1: `substeps` es **opcional en el esquema JSON** y obligatorio sólo en un validador
de Python que el modelo no puede ver. Un reintento ciego no lo arregla porque el fallo es determinista, no
transitorio.

**Lo que se intentó, y NO funcionó**: un bucle de autocorrección que le devuelve al modelo el error exacto
del validador y le pide la semana otra vez. Replicado contra ese mismo prompt archivado, **gemini-3.5-flash
se negó a expandir el bloque las tres veces**, con el error delante. El fallo no es transitorio: es que el
modelo no puede ver la restricción, porque vive en un validador de Python y no en el esquema.

**Lo que sí quedó**: el bucle se conserva (dos intentos: sirve para otras clases de error) y, si sigue sin
cumplir, **cae al esquema laxo de siempre**. Así la corrida se comporta exactamente como hoy —el formateador
entrega lo que puede, el validador marca los huecos, la reparación los llena— y nunca peor. Sin ese respaldo,
el arreglo de B1 habría convertido un fallo parcial en un fallo total del nodo.

**El problema de fondo sigue abierto** y necesita su propio track: un esquema en el que el estado inválido
**no se pueda escribir**. La vía natural es quitar `"repeat"` del vocabulario de `step_type` que ve el
formateador y dejar que un bloque de repetición sea, simplemente, *un paso con `substeps`* — que es
exactamente lo que `is_repeat_block` ya calcula y lo que el constructor de Garmin ya usa.

### 🟡 B3 — A8 cerrado

`Reasoning archive ... written with 1 missing artefact(s): synthesis_result`, otra vez. La corrida de semana
es `3 summarizers → 3 expertos → weekly_planner → plan_formatter`: **no tiene nodo de síntesis**. El archivo
se estaba acusando a sí mismo de perder algo que nunca existió. Mismo patrón que ya se arregló para
`week_intents`.

### 📊 Lo que el macrociclo realmente ve (respuesta a la duda del atleta)

| Nodo de la corrida de temporada | Entrada | Salida |
|---|---|---|
| activity_summarizer | **353.613** | **1.814** |
| metrics_summarizer | 25.109 | 972 |
| physiology_summarizer | 41.005 | 824 |
| los 3 expertos | 11.919 | 2.851 |
| synthesis | 1.847 | 4.422 |
| **season_planner** | **6.740** | 4.156 |

El summarizer de actividad comprime **195:1**: 171 actividades, 855 KB de datos de Garmin, reducidos a 3.260
caracteres de markdown. El season planner —el que fija el norte de 16 semanas— decide sobre **6.740 tokens
de entrada**.

Que la temporada cueste menos que la semana **no significa menos cuidado**: la temporada es entrada barata
($0,25/M) y la semana es salida cara ($9/M). Pero el cuello de botella de información es real y merece su
propio track.

### ✅ La semana generada ES fresca

El log lo demuestra: la generación de semana hace **su propia extracción** de Garmin (29 actividades del
27-jul al 26-ago), y de ahí salen `ACWR actual (Garmin): 1.10`, el esfuerzo más exigente de los últimos 28
días, el ritmo umbral, el check-in del día y el estado de HRV. Los tres summarizers y los tres expertos
**también vuelven a correr** con esos datos frescos. Lo único que es una foto de hace 16 semanas es el
**plan de temporada**, que es lo que debe ser.


---

## El esquema de repeticiones (2026-08-26)

### Tres intentos, tres fallos, el mismo día

Contra un prompt archivado real, gemini-3.5-flash falló **la misma sesión** (una serie de 5x400m) de tres
formas distintas, según se le iba cerrando cada una:

1. Omitió `steps` **en los nueve días** — porque `WorkoutDay.steps` tenía valor por defecto y por tanto no
   estaba en `required`.
2. Con `steps` obligatorio, escribió `{"step_type": "repeat", "repeat_count": 5, "description":
   "5x(400m a ritmo VO2max, 2' rec)"}` sin `substeps`, y **siguió escribiéndolo tres veces con el error del
   validador delante**.
3. Sin la opción `"repeat"`, escribió el mismo paso plano **sin `duration_type`**.

### El patrón no es torpeza

**Un entrenador escribe una repetición en UNA línea.** «5x400m con 2' de recuperación» es un solo
pensamiento. El árbol anidado de `substeps` es un hecho sobre el **formato de archivo de Garmin**, no sobre
entrenamiento — y cada ronda peleándose con él costaba una reparación tier-2 que reconstruía el plan entero
($0,1093 de una semana de $0,3319, el 33%).

### La resolución: el modelo expresa intención, el código codifica

El paso del formateador es ahora **plano y siempre ejecutable**:

```json
{"step_order": 2, "step_type": "interval", "duration_type": "distance", "duration_value": 400,
 "repeat_count": 5, "recovery_seconds": 120, "recovery_description": "2' trote Z1",
 "target_type": "pace", "target_low": 312, "target_high": 333, "description": "5x400m a 5:12-5:33/km"}
```

`expand_formatted_steps()` construye el bloque anidado que el constructor de Garmin, el validador y las
plantillas ya esperan. Verificado extremo a extremo: esa línea produce un `RepeatGroupDTO` con
`numberOfIterations=5` y **la recuperación dentro**, que es el defecto original que dio origen a toda la app.

Es el mismo principio que la separación decisión/formateo (v4) y que «Garmin es un paso de traducción, no un
límite de diseño» (v5), un nivel más abajo.

Tres cosas quedan imposibles de escribir mal, en vez de rechazadas después:

| Antes se podía escribir | Ahora |
|---|---|
| un día sin `steps` | `steps` es `required` |
| `step_type: "repeat"` sin `substeps` | no existe `"repeat"`, y no existe `substeps` |
| un paso sin `duration_type` | `duration_type` es `required` |

**Lo que sigue expresándose igual de bien**: una serie descendente (repeticiones distintas entre sí) se
escribe como pasos sueltos, que es lo que una lista plana hace de forma natural. Y las series de fuerza no
llevan `repeat_count` — la estructura la decide el atleta.

`WorkoutStep` conserva `"repeat"` y `substeps`: los planes ya guardados los contienen y tienen que seguir
cargando. Verificada la ida y vuelta a JSON.

### Medido: tres corridas contra el prompt exacto que falló seis veces

| | Entrada | Salida | Costo | Días sin pasos | Bloques de repetición | Violaciones |
|---|---|---|---|---|---|---|
| corrida 1 | 5.991 | 6.468 | $0,0672 | **0** | 5x(trabajo+recuperación) | **0** |
| corrida 2 | 5.991 | 6.447 | $0,0670 | **0** | 5x(trabajo+recuperación) | **0** |
| corrida 3 | 5.991 | 5.991 | $0,0629 | **0** | 5x(trabajo+recuperación) | **0** |

**Cero violaciones del validador** en las tres. Es decir: **la reparación tier-2 no se ejecuta**, y la red de
seguridad vuelve a ser una red en vez de la autora del plan.

Y de paso la salida bajó a la mitad: **~6.300 tokens contra los 11.909 que gastó la corrida real**, casi
todos ellos pensamiento. El formateo cuesta ahora **~$0,067** contra los **$0,1162 + $0,1093 = $0,2255** de
formateo más reparación.

> Proyección para la semana completa: **$0,3319 → ~$0,142**, un **57% menos**, con el mismo modelo.

### La auditoría que lo permitió

Antes de tocar nada: **ningún consumidor lee `step_type == "repeat"`**. Todos se ramifican sobre `substeps`
— `workout_builder` (que además emite una constante como tipo Garmin del grupo), `garmin_upload_service`,
todas las reglas del validador, las dos plantillas Jinja, `app.js`, y `is_repeat_block`, que es literalmente
`bool(self.substeps)`. Era una palabra que el modelo tenía que decir y que nadie leía.

---

## T6 — La poda (2026-08-26)

### Lo que NO se hizo, y por qué

La tentación era mandar menos: menos actividades, sin vueltas, sólo la fila de resumen. **Eso sería vender el
producto.** Las palabras del atleta: la diferencia con Runna son *"los detalles y el feedback constante
haciendo planes de entreno totalmente adaptativos"*. Los parciales por vuelta son cómo se ve que una sesión
de series se cayó en las dos últimas repeticiones, o que un rodaje "Z2" se fue a Z3.

**Ningún campo se eliminó por "probablemente no se usa".** En el momento en que ese archivo empieza a
adivinar qué números necesita un entrenador, se convierte en aquello que vino a evitar.

### Lo que sí se quitó

| Ruido | Por qué no es señal |
|---|---|
| Campos `null` | `"avg_power": null` en una carrera aparece en cada actividad y cada vuelta. **13 de los 29 campos** del resumen de una actividad real eran nulos. Una clave ausente y una clave nula dicen lo mismo, y una de las dos cuesta tokens 171 veces |
| Ruido de coma flotante | `0.9390000104904175` es 0,939 con quince dígitos de error IEEE-754 pegados. `2.622999906539917` es 2,623 |
| Vueltas de 0 segundos | **1.133 de 1.611 vueltas** duraban entre 0,1 y 1,1 s. Son pulsaciones del botón |
| El sangrado del JSON | `indent=2` sobre 855 KB es espacio en blanco que nadie lee y que todo modelo cobra. **34% del payload él solo** |

**La vuelta más larga que descartó recorrió 0,92 metros en 4,98 segundos.** Las dos condiciones van con Y
lógico precisamente para eso: una zancada de 5 s recorre ~30 m y se conserva por la distancia. Y un bloque
de fuerza de 20 min sin distancia también se conserva, por la duración.

Cuando se descarta una vuelta **se dice**, con un marcador `laps_omitted`. El lector tiene que poder
distinguir "esta sesión no tenía parciales" de "te escondimos algunos".

### Medido sobre el payload real

| | Actividades | Antes | Después | Ahorro | Tokens | Costo de entrada |
|---|---|---|---|---|---|---|
| Temporada | 171 | 852.795 | 205.237 | **75,9%** | 213.198 → 51.309 | $0,0533 → $0,0128 |
| Semana | 29 | 159.232 | 34.515 | **78,3%** | 39.808 → 8.628 | $0,0100 → $0,0022 |

Verificado además que **ningún número fuera de una vuelta descartada cambia**: se extraen todos los valores
numéricos del payload antes y después y las listas coinciden.

Y aplica a los tres summarizers, no sólo al de actividad: la poda es estructural, no específica de
actividades.

---

## T7 — El informe deja de ser un mega resumen (2026-08-26)

### Lo que ya existía y no llegaba al papel

Todo esto estaba en `weekly_plan.json`, se archivaba, y se mostraba en la app. Nada de ello llegaba a la
hoja que el atleta imprime y pega en la refri:

| En los datos | En la hoja, antes |
|---|---|
| `week.week_rationale` — por qué ESTA semana, con sus números | ausente |
| `day.purpose` — para qué esta sesión, hoy, para él | ausente |
| `day.manual_instructions` — la sesión entera de lo que Garmin no puede llevar | ausente |
| `step.target_low/high`, `step.target_zone` — la banda de ritmo salida de su umbral real | sólo si el modelo la repetía en la prosa |
| `day.garmin_exportable` — si va al reloj o no | ausente |
| totales por disciplina | ausente |

El tercero es el que más importa para lo que este producto es: **v5 Track 5 prometió** que una sesión de
pliometría o rehab que Garmin no puede llevar se le entrega al atleta como instrucciones escritas. Si esas
instrucciones no están en la hoja impresa, **el atleta nunca las ve**, y la promesa se rompe justo donde se
hizo.

### Lo que ahora lleva la hoja semanal

- **Cabecera de semana**: sesiones, tiempo total, el reparto por disciplina en chips, y —lo que ningún
  competidor de sólo carrera tiene que resolver— **cuántas sesiones no van al reloj**.
- **La explicación del entrenador**, con sus números, encima del calendario.
- **Por sesión**: el propósito, la disciplina real (rehab ≠ fuerza, aunque para Garmin ambas sean
  `strength`), y las instrucciones escritas completas cuando la sesión no va al reloj, marcadas en naranja.
- **Por paso**: la duración y **el objetivo leído de los campos estructurados** — la banda de ritmo en
  min/km, o la zona de FC **con sus pulsaciones reales**.

### El bug que apareció escribiendo esto

La primera versión de `step_target` recorría `hr_zones_by_sport.values()` y se quedaba con la primera que
encontrara. Sobre el plan real del atleta imprimió **`Z1 (93–111 ppm)` en una sesión de carrera**: las zonas
de **natación**. Su Z2 de natación y su Z2 de carrera están a 28 pulsaciones de distancia.

`CLAUDE.md` ya documenta ese error exacto habiendo salido a producción una vez, por el extractor. Ahora la
zona se resuelve **por el deporte del día**, con respaldo a `other`, y si no hay tabla para ese deporte
imprime el número de zona a secas en vez de inventar pulsaciones de otro deporte. Hay tests para los tres
casos.

### Lo que ahora lleva la hoja de temporada

- **Tus metas frente a tu condición actual**: meta, predicción de Garmin, **el ritmo que exige** y la lectura
  orientativa. Ya se calculaba en T4 y no salía del prompt.
- **Lo que condiciona este bloque**: las restricciones vigentes con su fecha de revisión.
- **Tus números**: zonas por deporte y bandas de ritmo, para que la hoja se sostenga sola.

### Contra qué se comparó

[TrainingPeaks](https://help.trainingpeaks.com/hc/en-us/articles/231472468-TrainingPeaks-Athlete-User-Guide)
imprime el plan pero avisa de que la versión impresa "may not be visually formatted as neatly"; el coach
adjunta aparte una guía en PDF. [Runna](https://support.runna.com/en/articles/10393191-how-to-use-training-preferences-in-the-runna-app)
da un *plan overview* con la semana de un vistazo y los kilómetros totales.

Lo que ninguno de los dos hace, porque no tiene con qué: **explicar por qué esta semana se ve así, con los
números de este atleta**, y **entregar por escrito la sesión que el reloj no puede llevar**. Eso es lo que
ahora está en la hoja.
