# Plan Maestro v5 — El cerebro antes que el formato

> Disparado por la auditoría de la arquitectura de pensamiento pedida por el atleta tras cerrar el Plan
> Maestro v4 (Tracks A-G, último commit `878b4e5`). v4 arregló **cómo se valida y se escribe** un entreno.
> v5 ataca lo anterior: **qué sabe cada agente cuando decide**, en qué idioma habla, y qué se le permite
> imaginar.
>
> Todos los hallazgos de abajo se verificaron leyendo el código y los datos reales en disco
> (`data/storage/Jose_Ines_Castillo/summary.json`), no por inferencia.

---

## Principio rector de v5: Garmin es un paso de traducción, no un límite de diseño

Instrucción textual del atleta:

> *"Creo que a veces limitamos al sistema a acoplarse a cómo podría escribirse el entreno en Garmin, pero
> este no debe ser un limitante, este debe ser un tema de traducción como último paso... si necesita hacer
> bricks o si necesita hacer pliometría, o si requiere ejercicios de rehab o prehab, cómo eso lo pasemos a
> Garmin creo que es otro tema."*

Se adopta como principio de diseño de todo v5:

1. **El plan de entreno se diseña con vocabulario de entrenador**, no con el vocabulario de la API de
   Garmin. Pliometría, rehab, prehab, movilidad, técnica de nado, brick, test de campo y trabajo por
   sensación son sesiones legítimas y de primera clase.
2. **La exportación a Garmin es explícitamente con pérdida.** Lo que Garmin no puede sostener no se
   degrada en silencio ni se elimina: se le entrega al atleta como instrucción clara y legible.
3. **Ninguna regla determinista puede existir sólo porque Garmin no sabe expresar algo.** Ese es un
   problema del exportador, no del coach.

Y se conserva el principio rector de v4, que sigue vigente y gobierna cada track nuevo:

> Regla determinista sólo si (a) está anclada en ciencia del entrenamiento citada honestamente —no tratada
> como ley—, (b) se lee contra los datos reales del atleta y no contra un umbral poblacional, y (c) no está
> construida de forma que el modelo choque contra ella y reintente hasta fallar. Un guardarraíl que provoca
> reintentos es evidencia de mal diseño de prompt, no evidencia de que el guardarraíl haga falta.

---

## Hallazgos de la auditoría del "cerebro de pensamiento"

### H1 — 🔴 El planificador de temporada es el agente más determinante y el peor alimentado

`season_planner_node.py:37-51` — todo lo que recibe es: `athlete_name`, `current_date`, `competitions`, y
los tres bloques `for_season_planner` de los expertos.

**Nunca recibe**: la ficha de prescripción (ritmo umbral real, zonas reales del reloj, predicciones de
carrera), el volumen crónico real, la disponibilidad (horas/días/deportes), la adherencia histórica, ni un
solo dato médico.

Consecuencia verificable: fija `weekly_hours_target` por fase sin saber que el atleta entrena ~3,6-6 h
reales por semana, mientras `available_hours` del perfil dice 14,25. El macrociclo se construye sobre un
número inventado, y **todas** las semanas se generan dentro de ese marco.

### H2 — 🔴 El dato que desmiente una meta irrealista existe y no llega al agente que la evalúa

El atleta lo planteó exactamente así: un recreativo de 5K en 28 min que pide ir a los Olímpicos debería
recibir criterio, no complacencia.

`get_race_predictions` ya se extrae (`data_extractor.py:383,488`) y ya vive en la ficha de prescripción
(`prescription.py:405-413`). El `target_time` de cada competencia ya viaja estructurado
(`server.py:242-243`). **Pero la prescripción sólo se inyecta al planificador semanal, nunca al de
temporada** — el único agente que evalúa metas de temporada las evalúa a ciegas. No falta un dato: falta
un cable.

### H3 — 🔴 Todo el módulo médico se recolecta y se descarta

`current_injuries` (con localización, lateralidad, dolor 0-10), `par_q_flags` (las 7 preguntas) y
`doctor_performance_report` se persisten en el perfil (`server.py:322-329`) y **ningún agente los ve
jamás**. `build_custom_planning_context` (`server.py:576-582`) sólo lee el campo de texto libre
`limitations`.

Esto reencuadra el hallazgo #4 original: el problema no es que el gate médico no bloquee. Es que el
módulo médico completo es un formulario cuyo producto se tira a la basura. El valor no está en una regla
que bloquee, está en enrutar el dato.

### H4 — 🟠 La mezcla de idiomas es sistémica, no cosmética

Ningún prompt de la rama de análisis (summarizers, expertos, síntesis, planificador de temporada,
formateador HTML) contiene una sola directiva de idioma. Sólo el planificador semanal y el validador
emiten español. El `season_plan` guardado en disco literalmente empieza con
`"# Strategic Season Plan (16-Week Macrocycle)"`.

### H5 — 🟠 El informe HTML lo escribe un LLM a mano, cada vez desde cero

`formatter_node.py:17-38` le pide a un modelo *"Create a complete HTML document... Clean CSS, responsive
layout, professional typography"*. Resultado: diseño distinto en cada corrida, imposible de mantener, y
se paga una llamada de LLM por generación para producir CSS.

El repo ya tiene el patrón correcto y probado: Jinja2 + `@media print` en
`services/ai/rendering/templates/`. Reemplazarlo hace el informe **consistente, bonito, gratis y en
español** de un solo movimiento.

### H6 — 🟠 El canal experto→consumidor tiene forma pero no jerarquía

`ReceiverPayload` (`expert_outputs.py`) sí es estructurado: `signals` / `evidence` / `implications` /
`uncertainty`, todas `list[str]`. Pero `_render_receiver_payload` (`output_helper.py`) las aplana a
viñetas markdown, y **no existe confianza, ni prioridad, ni horizonte temporal**. El planificador no tiene
forma de saber cuál de dos afirmaciones contradictorias pesa más.

Además los tres expertos siguen con el union `list[Question] | ReceiverOutputs`, el mismo patrón cuya
degradación de salida estructurada ya verificamos y quitamos del formateador en v4.

### H7 — 🟠 Síntesis no tiene política de conflictos — y además no alimenta a ningún planificador

Confirmado: su system prompt completo es *"Integrate / Contextualize / Simplify"*.

**Matiz importante que cambia el arreglo**: `season_planner` y `weekly_planner` leen directamente los
campos `for_season_planner` / `for_weekly_planner` de los expertos. **Nunca leen `synthesis_result`.**
Síntesis es una rama de *reporte*, no de decisión.

Por lo tanto, arreglar sólo el prompt de síntesis mejora el informe que lee el atleta, pero **no mejora un
solo entreno**. La política de conflictos tiene que existir también donde se toma la decisión de
entrenamiento.

### H8 — 🟠 El vocabulario de Garmin subió hasta el diseño del plan

`WorkoutDay.sport` es `Literal["run","bike","swim","strength","walk","brick","rest"]`. No hay pliometría,
ni rehab, ni prehab, ni movilidad, ni técnica. `WorkoutStep.duration_type` es
`time|distance|lap_button|reps` — el vocabulario literal de Garmin. El propio `exercise_name` ya lleva la
nota *"Garmin only recognizes a small fixed set of its own exercise names"*.

**Buena noticia**: la capa libre de Garmin ya existe. `DayIntent` + `session_sketch` (creados en v4 al
partir decisión/formateo) son exactamente el lugar correcto. No hay que rearquitecturar: hay que
**ascender `DayIntent` a plan de registro** y degradar el `WorkoutDay` a formato de exportación.

### H9 — 🟠 Casi nada de lo que piensan los agentes se guarda

En disco quedan: `season_plan.md`, `weekly_plan.json`, `summary.json`, `analysis.html`, `planning.html`.

**No queda nada** de: los tres resúmenes de summarizers, los nueve payloads de expertos (3 expertos × 3
receptores), el markdown crudo de síntesis, ni el `WeekIntentContent` de la capa de decisión. Es
información cara, ya pagada, y se descarta al terminar la corrida. Sin ella, el chatbot coach que pide el
atleta es imposible de construir con contexto real.

### H10 — 🟠 Las restricciones del plan de temporada son prosa sin fecha

"Ciclismo como motor de volumen", "terreno plano", "ACWR bajo 1,2" viven sólo dentro de
`SeasonPlanContent.markdown`. `SeasonPhase` sólo estructura `weekly_hours_target`, `deload_weeks` e
`intensity_emphasis`. Nada tiene fecha de inicio ni de revisión, así que una restricción nacida de una
molestia de rodilla ya resuelta sigue vigente para siempre.

---

## Tracks

### Track 1 — Un idioma y una política de conflictos

**Idioma.** Directiva compartida nueva en `prompt_components.py` (`get_language_directive()`), inyectada en
el system prompt de **todos** los nodos: summarizers, 3 expertos, síntesis, season planner, formateador.
Regla: toda salida legible por el atleta en español; los términos técnicos consagrados (VO2max, FTP, ACWR,
tempo, umbral) se mantienen en su forma estándar; nunca traducir un dato crudo de Garmin.

**Política de conflictos.** Bloque compartido, inyectado en **dos** lugares (por H7):

- `synthesis_node`: cuando dos dominios se contradicen, **nombrar la contradicción explícitamente**,
  ordenarla por recencia de la evidencia / cobertura de datos / cercanía del dato a la afirmación, decir
  qué observación la resolvería, y nunca elegir un lado en silencio.
- `TRAINING_DECISION_USER_PROMPT`: la misma política, más la consecuencia de entrenamiento — cuando
  ejecución y recuperación se contradicen, **la señal de recuperación gobierna la intensidad y la de
  ejecución gobierna el volumen**, y la decisión se escribe en `week_rationale`.

**Verificación**: test que afirma que la directiva de idioma está presente en el system prompt de cada
nodo; test de formato de prompts; revisión manual del `season_plan.md` de una corrida real.

---

### Track 2 — El season planner deja de planificar a ciegas + realismo de metas

**2a. Cablear los datos que ya existen.** Pasar al `season_planner_node` (por el mismo canal lateral
`availability_constraints` que ya usa el semanal): `prescription`, `volume_baseline` (crónico real de 28
días, ya calculado en `build_volume_baseline`), disponibilidad, adherencia histórica y el bloque médico
del Track 10. Nuevos placeholders en `SEASON_PLANNER_USER_PROMPT`.

**2b. Módulo nuevo `services/ai/goal_realism.py`.** Para cada competencia con `target_time`:

- Compara contra la predicción de Garmin para esa distancia (`race_predictions`) y contra la tendencia
  real del atleta.
- Calcula semanas disponibles hasta la fecha y el % de mejora que la meta exige.
- Emite un **bloque de encuadre**, no un rechazo: *"Predicción actual media maratón 2:14:24. Meta 1:45 =
  22% más rápido en 14 semanas. Mejora típica sostenible en ese plazo para este perfil: 3-6%. Rango
  realista estimado: 2:04-2:09."*

**No hay regla que rechace la meta.** El agente decide con criterio; nuestro trabajo es sólo impedir que
decida a ciegas. El prompt le pide explícitamente proponer un objetivo alcanzable y decirlo con respeto
cuando la meta declarada no cuadra, exactamente como haría un entrenador real.

**2c. UI**: la lectura de realismo se muestra en la tarjeta de la competencia, con el rango sugerido y la
opción de ajustar la meta.

**Verificación**: tests con números reales del atleta (5K 27:44 → meta 5K 20:00 debe salir marcada; meta
5K 26:30 no); una corrida real y revisar que el macrociclo cite el volumen crónico verdadero.

---

### Track 3 — Restricciones de temporada estructuradas, fechadas y revisables

Modelo nuevo `SeasonConstraint` en `SeasonPlanContent`:

| campo | para qué |
|---|---|
| `id` | estable entre reevaluaciones |
| `statement` | la restricción en una frase, en español |
| `rationale` | por qué existe, citando el dato que la originó |
| `kind` | `carga` / `terreno` / `sesgo_deporte` / `tecnica` / `medica` / `logistica` |
| `strength` | **`recomendacion`** o **`limitacion`** — nunca "regla" |
| `applies_from` / `review_on` | fechas reales |
| `source` | `experto_fisiologia`, `lesion_declarada`, `adherencia`, `atleta` |
| `status` | `vigente` / `resuelta` / `modificada` |

- El planificador semanal recibe **sólo las vigentes a la fecha de la semana** — filtrado determinista por
  fecha, que es lo único determinista de este track.
- En cada reevaluación el season planner recibe las restricciones previas y **debe** marcar cada una
  `vigente|resuelta|modificada` con motivo. Así una molestia superada deja de gobernar el plan.
- **Explícitamente NO se crea una regla de validación por restricción.** Eso sería justo el guardarraíl
  que el atleta pidió evitar. La restricción se hace inequívoca para el planificador; no se le pone un
  policía detrás.

---

### Track 4 — El juicio de los expertos llega con jerarquía

- `ReceiverPayload` gana `confidence` (`alta`/`media`/`baja`), `priority` (1-3), `horizon` (días) y
  `conflicts_with` (qué afirmación de otro dominio podría contradecir esto).
- `_render_receiver_payload` los renderiza al frente, no al final.
- Quitar el union `list[Question] | ReceiverOutputs` de los tres expertos cuando HITL está desactivado
  (que es el camino de la app web), replicando el arreglo ya verificado en el formateador de v4.

---

### Track 5 — El plan deja de hablar Garmin

- **`DayIntent` pasa a ser el plan de registro.** Gana `discipline` con vocabulario de entrenador
  (`carrera`, `bici`, `natacion`, `fuerza`, `pliometria`, `rehab`, `prehab`, `movilidad`, `tecnica`,
  `brick`, `test`, `otro`), `coach_notes`, `execution_cues` y `equipment`.
- **`WorkoutDay` se degrada a formato de exportación.** Conserva el `sport` literal de Garmin, y gana
  `garmin_exportable: bool` + `manual_instructions: str`.
- Lo no exportable **no se degrada ni se borra**: se muestra al atleta en la tarjeta del día y en el
  informe impreso como instrucción legible, con nota de por qué no está en el reloj.
- El validador se abstiene por completo en sesiones no exportables o de clase `other`.

---

### Track 6 — Check-in del atleta antes de generar la semana

Investigación: la herramienta breve más validada y usada en monitoreo de deportistas es el **índice de
Hooper (Hooper-Mackinnon)**: cuatro ítems — calidad de sueño, fatiga, estrés y dolor muscular — puntuados
en escala corta, aplicados de forma repetida y leídos **contra la propia línea base del atleta**. Se
complementa con el **sRPE de Foster** (esfuerzo percibido × minutos) como carga interna. A eso un
entrenador real le suma lo que ningún sensor ve: qué se atravesó la semana pasada, qué viene esta semana
(viajes, trabajo, sueño), si hay alguna molestia y dónde en 0-10, y las ganas de entrenar.

**Diseño**: 7 ítems, ~45 segundos, en un modal antes de "Generar Semana".

1. Sueño (1-7) · 2. Fatiga (1-7) · 3. Estrés (1-7) · 4. Dolor muscular (1-7) — *Hooper*
5. Molestia/dolor localizado: ¿dónde? + 0-10 — *el que puede cambiar el plan*
6. ¿Qué se atravesó la semana pasada? (texto libre corto)
7. ¿Qué viene esta semana? viaje / carga laboral / poco sueño / normal · y ganas de entrenar (1-7)

**Reglas de diseño, en línea con el principio rector:**

- Es **opcional**. Saltarlo nunca bloquea la generación.
- Se persiste por semana para que exista tendencia; se le entrega al modelo **contra las respuestas
  previas del propio atleta**, nunca contra un umbral poblacional — el mismo trato que ya le damos al HRV.
- **No se construye ningún guardarraíl automático** que degrade sesiones por una respuesta. El modelo
  decide; nosotros le damos el dato. (Misma decisión, y misma razón, que la documentada en
  `services/ai/recovery_state.py`.)

---

### Track 7 — Todo lo que piensan los agentes se guarda

Directorio nuevo `data/storage/<perfil>/reasoning/<ISO>/` con: los tres resúmenes de summarizers, los
nueve payloads de expertos, el markdown de síntesis, el plan de temporada estructurado, el
`WeekIntentContent` de la capa de decisión, la traza del validador y el check-in del Track 6. Se archiva
con el historial existente.

Barato, sin riesgo, y es la condición previa del Track 8.

---

### Track 8 — Chatbot coach

`POST /api/coach/chat` + ruta `#/coach`. System prompt armado con: fecha de hoy, perfil, ficha de
prescripción, plan de temporada y sus restricciones vigentes, la semana actual con su `week_rationale`,
adherencia, estado de recuperación, último check-in, y **los textos de razonamiento del Track 7**.

- **De sólo lectura**: explica, contextualiza y aconseja; no modifica el plan. Para cambiar la semana
  existe el botón de regenerar.
- Declara explícitamente que no sustituye a un médico ni a un fisioterapeuta.

---

### Track 9 — Informes en español y dignos de la refri

- `formatter_node` deja de pedirle HTML a un LLM. Se reemplaza por Jinja2 + `markdown-it-py` (ya es
  dependencia) sobre **un sistema de diseño compartido**: una paleta, una tipografía, jerarquía
  consistente, listo para imprimir.
- Dos informes con el mismo lenguaje visual: **macrociclo** y **semana/estadísticas**.
- Efecto secundario: se elimina una llamada de LLM por generación. Más barato **y** consistente.

---

### Track 10 — Módulo de salud honesto y, sobre todo, útil

- **Renombrar** "Doctor Deportivo" → **"Ficha de Salud y Antecedentes"**; el agente pasa a ser
  "Asistente de Antecedentes". Sin lenguaje clínico ni promesa de diagnóstico.
- **Descargo + casilla de afirmación** con fecha, persistida: el atleta declara que no tiene condiciones
  ni restricciones que le impidan entrenar, se le recomienda consulta médica presencial, y se aclara que
  la herramienta busca mejorar su experiencia y **no sustituye a un médico ni a un fisioterapeuta**.
- **Enrutar el dato médico al pipeline** (el arreglo que le da sentido al módulo): `current_injuries`
  estructuradas, `par_q_flags` y el informe de antecedentes entran al season planner y al planificador
  semanal.
- Un flag PAR-Q+ positivo **no bloquea**: genera un `SeasonConstraint` visible y fechado (Track 3) más una
  recomendación destacada en la UI y en el informe.

---

### Track 11 — Historial completo y borrado que dice qué borra

Hoy el botón dice "borrar generación" y el atleta no sabe si está borrando la semana en curso, el
macrociclo, o las dos cosas. Con la generación semana a semana (v4 Track B) y el archivo de razonamiento
(Track 7) eso ya son **tres cosas distintas** viviendo bajo un solo botón.

- **Toda generación entra al historial**, tanto las de temporada como las semanales, etiquetadas por tipo
  (`macrociclo` / `semana`), fecha, semanas cubiertas y costo.
- **El diálogo de borrado enumera exactamente qué se va a eliminar** antes de pedir confirmación: "vas a
  borrar la semana del 2 al 8 de septiembre (6 sesiones)" o "vas a borrar el macrociclo de 16 semanas y
  sus fases".
- **Borrar plan y borrar razonamiento son acciones separadas.** Borrar un plan no borra por qué se tomó;
  el razonamiento es el material del chatbot (Track 8) y del propio atleta para entender su progresión.
- Nada se borra en cascada sin decirlo.

---

### Track 12 — Las ventanas de datos las decide la ciencia, no el atleta

Hoy el perfil expone "Días de actividades" (90) y "Días de métricas fisiológicas" (56) como campos que el
atleta escribe a mano, bajo "Ajustes avanzados". Es una decisión técnica disfrazada de preferencia: el
atleta no tiene forma de saber qué número es correcto, y el número gobierna tanto la calidad del análisis
como el costo de cada corrida (en una corrida real medida, el `activity_summarizer` solo consumió 39.272
de 102.389 tokens — la ventana es directamente una palanca de costo).

**🔴 Bug encontrado al auditar esto**: `build_volume_baseline` (`server.py:1152`) calcula el volumen
crónico pidiendo **28 días** de actividades y dividiendo entre 4 — pero la extracción de la generación
semanal solo trae **21 días** (`server.py:1464`). El crónico se calcula sobre una ventana que no existe y
sale **subestimado hasta un 25%**, y ese número es justo el que ancla el objetivo de volumen del Track F
de v4. Se arregla alineando las ventanas, no parcheando la división.

**Ventanas propuestas, cada una anclada a para qué sirve:**

| Uso | Actividades | Métricas | Por qué |
|---|---|---|---|
| **Macrociclo** | 180 días | 90 días | Hace falta ver un ciclo completo de bloques y la estacionalidad. Más allá de ~6 meses el desentrenamiento y los cambios de vida hacen que el dato diga poco sobre la capacidad *actual*; el arco largo ya llega barato por `get_race_predictions` y la tendencia de VO2max, sin pagar el resumen de cada actividad. |
| **Semana** | 28 días | 15 días | 28 días es la ventana crónica estándar del ACWR — "fitness" frente a los 7 días agudos de "fatiga" — y es exactamente la que el cálculo de volumen ya pretende usar. 15 días de métricas cubren la lectura de recuperación sin arrastrar un promedio viejo. |

- Los campos desaparecen de "Ajustes avanzados" y pasan a constantes documentadas con su justificación,
  igual que `_DEFAULT_RAMP_ALLOWANCE` en `weekly_planner_node.py`.
- Los valores ya guardados en perfiles existentes se ignoran, no se migran: eran adivinanzas.
- Referencias del ACWR: [Science for Sport](https://www.scienceforsport.com/acutechronic-workload-ratio/),
  [Catapult](https://support.catapultsports.com/hc/en-us/articles/360000538795-How-to-Set-Up-an-Acute-Chronic-Workload-Ratio-Chart).

---

## Orden de ejecución

**1 → 7 → 10 → 12 → 2 → 3 → 4 → 6 → 11 → 5 → 9 → 8**

- **1** y **7** son baratos, sin riesgo arquitectónico, y mejoran de inmediato lo que el atleta ve y lo que
  nosotros podemos auditar de las corridas siguientes.
- **10** es seguridad y desbloquea datos que **2** y **3** necesitan.
- **2 → 3 → 4** arreglan, en orden de dependencia, *qué sabe* cada agente al decidir.
- **6** entra cuando el canal de entrada ya está sano, para no sumar una señal más a un prompt confuso.
- **5** es el cambio de esquema más grande: se hace cuando las entradas ya son correctas.
- **9** es presentación: después de que el contenido esté bien.
- **12** va justo después de 10 porque arregla un bug activo (el crónico subestimado) que ya está
  sesgando el objetivo de volumen de cada semana que se genera hoy.
- **11** entra cuando ya existen las tres cosas borrables (macrociclo, semana, razonamiento), no antes.
- **8** al final, porque necesita 7, 3 y 6 para tener contexto real que ofrecer.

Cada track cierra con commit + push, tests de la suite stdlib, y —donde aplique— verificación contra la
cuenta real de Garmin, preguntando antes de pasar al siguiente.
