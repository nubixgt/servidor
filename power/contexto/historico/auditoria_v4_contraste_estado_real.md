# Contraste de auditorías externas (Gemini + ChatGPT) contra el estado real del código

**Fecha:** 2026-08-24
**Método:** las dos auditorías externas ("Gemini" y "ChatGPT", adjuntas por el usuario) se escribieron con
acceso únicamente a los outputs de una corrida real y a los prompts del pipeline LangGraph — nunca al código
fuente completo (validador, nodos, esquemas, server.py). Este documento las contrasta línea por línea contra
el repositorio real (`services/ai/langgraph/`, `services/ai/`, `web_app/server.py`) para separar lo que es un
hallazgo genuino de lo que es una hipótesis razonable pero ya resuelta, parcialmente resuelta, o basada en una
lectura incompleta de la arquitectura.

**Alcance de este documento:** diagnóstico y hoja de ruta. No contiene cambios de código — está pensado para
entregarse a cualquier IA (o desarrollador) como contexto antes de implementar.

**Relación con trabajo en curso:** este repo ya tiene un plan de arreglos activo, "Plan Maestro v4"
(`.claude/plans/`), organizado en tracks A–G, disparado por la misma corrida real que ambas auditorías
analizan. Varios de los hallazgos de las auditorías **ya están resueltos o en progreso** dentro de ese plan;
se marca explícitamente dónde. Este documento cubre lo que el Plan Maestro v4 **no** cubre.

---

## Índice

1. [Veredicto general sobre las dos auditorías](#1-veredicto-general)
2. [Tabla de aplicabilidad rápida](#2-tabla-de-aplicabilidad-rápida)
3. [Corrección de percepción más importante: el validador ya es mayormente determinista](#3-corrección-de-percepción-más-importante)
4. [Análisis detallado por tema](#4-análisis-detallado-por-tema)
5. [Relación con el Plan Maestro v4 en curso](#5-relación-con-el-plan-maestro-v4-en-curso)
6. [Priorización recomendada, ajustada a la realidad del código](#6-priorización-recomendada)
7. [Qué NO implementar tal como lo proponen las auditorías](#7-qué-no-implementar-tal-como-lo-proponen-las-auditorías)

---

## 1. Veredicto general

Ambas auditorías son de buena calidad y coinciden en el diagnóstico de fondo: **la arquitectura de agentes es
sólida, pero le falta un modelo de estado del atleta explícito, una política de resolución de conflictos, y un
ciclo cerrado prescripción→ejecución→respuesta→decisión.** Ese diagnóstico de fondo es correcto y aplica.

Donde ambas auditorías se quedan cortas es en que **infieren la arquitectura desde los prompts y los outputs**,
no desde el código. Esto produce dos tipos de error sistemático:

1. **Sobre-estiman cuánto trabajo determinista ya se le pide al LLM.** El punto más citado por ambas
   auditorías — "el validador no debería pedirle matemáticas al LLM" — ya está resuelto en su mayor parte: la
   detección de cada violación (fechas, horas, duplicados, disponibilidad, ACWR, modalidad de intensidad) es
   100% Python determinista en `plan_validator.py` (1436 líneas). El LLM (`_tier2_repair`) solo se invoca para
   **generar contenido** (reescribir un bloque de pasos, añadir recuperación) — nunca para calcular sumas de
   horas o detectar duplicados. Ver sección 3.

2. **No conocen el Plan Maestro v4 ya en curso**, que ataca exactamente varios de los síntomas que ambas
   auditorías observaron en la misma corrida (días que desaparecen, fuerza colapsando, día de carrera mal
   armado, mensajes de error engañosos). Varios de esos síntomas ya tienen fix committeado en esta sesión.

Dicho esto, **el núcleo arquitectónico que ambas auditorías piden — Athlete State Model, Decision Engine,
Performance Response Engine, separación reasoning/intent/format en el Weekly Planner — no existe hoy en el
código y es un salto real, no una corrección menor.** Esa parte del diagnóstico se sostiene completamente.

---

## 2. Tabla de aplicabilidad rápida

| # | Hallazgo de la auditoría | Aplica? | Estado real verificado | Dónde se aborda |
|---|---|---|---|---|
| 1 | Validador determinista, no LLM haciendo matemáticas | **Ya resuelto en ~90%** | `validate_weekly_plan` (plan_validator.py:206-459) es 100% Python: fechas, horas, duplicados, disponibilidad, ACWR, modalidad de intensidad. El LLM solo repara contenido, nunca detecta ni calcula. | Ver §3 |
| 2 | Hard constraints vs soft constraints explícitos | **Parcialmente ya existe, sin nombrarse así** | `_TIER1_FIXABLE_TYPES` + fallbacks deterministas (missing_steps, missing_date, duplicate_date, race_day, ACWR) ya son de facto "hard constraints" con resolución forzada. Falta la capa de negociación explícita ("no puedo cumplir A y B, sacrifico C"). | §4.4 |
| 3 | Falta política de resolución de conflictos entre expertos (Synthesis) | **Aplica, confirmado** | `SYNTHESIS_SYSTEM_PROMPT_BASE`/`SYNTHESIS_USER_PROMPT_BASE` (synthesis_node.py:23-66) no tienen ninguna jerarquía ni política — solo "Integrate/Contextualize/Simplify". | §4.1 |
| 4 | Falta un Athlete State Model estructurado | **Aplica, confirmado** | Los expertos devuelven prosa (`for_synthesis`/`for_season_planner`/`for_weekly_planner`). `AthletePrescription` (prescription.py) es la única estructura numérica real, y cubre solo zonas/ritmo/potencia — no fitness/fatigue/readiness/confidence. | §4.2 |
| 5 | ACWR como juez rígido (>1.5 fuerza recorte automático) | **Aplica, confirmado** | `plan_validator.py:443-466`: `current_acwr > 1.5` es un corte binario que fuerza semana 1 a 70-80% del presupuesto. Sin matiz de ramp rate, monotony, TSB, ni tolerancia histórica. | §4.3 |
| 6 | Weekly Planner con demasiadas responsabilidades en un solo prompt | **Aplica, confirmado** | `WEEKLY_PLANNER_USER_PROMPT` (weekly_planner_node.py) mezcla disponibilidad + competencias/taper + estructura de intervalos + fuerza + modalidad + mesociclo + adherencia + formato de salida en un solo prompt de ~170 líneas. | §4.5 |
| 7 | Nutrición/hidratación ausente | **Aplica, confirmado** | Cero menciones de carbohidratos/hidratación/sodio en todo `services/ai/`. | §4.6 |
| 8 | Fuerza debería tener metadata de estrés, no solo ser freeform | **Aplica, pero hay que reconciliar con una decisión reciente** | Esta sesión (Plan Maestro v4 Track A) acaba de convertir fuerza en freeform **a pedido explícito del usuario**, precisamente porque la estructura anterior (series/reps prescritas) colapsaba 12/12 veces. La propuesta de "strength_metadata" (fase, estrés, RPE) es compatible y superior — no es volver atrás, es añadir una capa. | §4.7 |
| 9 | Adherence Drift Monitor (verde/amarillo/rojo a nivel de macrociclo) | **Parcialmente ya existe** | `services/ai/adherence.py` (308 líneas) ya calcula planificado-vs-ejecutado por sesión con tolerancia del 25%, y lo inyecta en la siguiente generación. Falta la agregación a nivel de macrociclo (drift semana a semana, clasificación GREEN/YELLOW/RED, distinción sesión clave vs sesión fácil). | §4.8 |
| 10 | Performance Response Engine (ciclo cerrado estímulo→ejecución→respuesta→decisión) | **No existe, aplica completamente** | Nada en el repo relaciona una sesión prescrita con la respuesta fisiológica posterior (HR drift, HRV del día siguiente, RPE) de forma estructurada. | §4.9 |
| 11 | Modelo explícito de incertidumbre/confidence | **Parcialmente ya existe en prosa, no como dato** | Los prompts de expertos ya piden "Signals → Evidence → Implications → Uncertainty" en texto libre, pero no hay ningún campo `confidence`/`data_coverage` estructurado en ningún esquema. | §4.10 |
| 12 | Data Quality Gate antes de planificar | **No existe formalmente, aplica** | Hay fallbacks puntuales (ej. fórmula de Friel si no hay zonas reales de Garmin) pero no hay un gate de confianza centralizado que bloquee/degrade la planificación ante datos pobres. | §4.11 |
| 13 | Doctor Agent no debería ser el gate médico final | **Aplica, confirmado — hallazgo real y no trivial** | `services/ai/doctor_agent.py`: la única consecuencia de un red flag PAR-Q+ es una instrucción de prompt ("MUST include recommendation to consult a physician" dentro del reporte generado). No hay ningún gate determinista en `server.py` que lea `par_q_flags` y bloquee `/api/generate`. | §4.12 |
| 14 | "Deload cada 4 semanas" es demasiado rígido | **Ya es más flexible de lo que asumen las auditorías** | `deload_weeks` es un campo que decide el propio LLM del season_planner (con la heurística "cada 4 semanas salvo que el contexto del atleta justifique otra cosa" como *sugerencia*, no regla dura) — `compute_mesocycle_position` solo LEE esa decisión de forma determinista. | §4.13 |
| 15 | Costo del weekly_planner (~90% del total) y fragilidad estructural | **Ya identificado y en remediación activa** | Confirmado en 3 corridas reales de esta sesión ($0.91–$1.07 de ~$1.00–$1.18 totales). Plan Maestro v4 Tracks A, B, C, D ya atacan esto directamente. | §5 |
| 16 | Umbrales absolutos de HRV (<45, <56) inventados sin calibración | **Aplica, confirmado** | El texto de season_planner citado en la corrida real ("HRV optimal zone 56.0-82.0 ms", "<56 → reduce sessions") es contenido **generado por el LLM dentro de su markdown libre**, no una constante en el código — pero como no hay ningún Athlete State Model que provea baseline individual, el LLM no tiene de dónde sacar un número mejor que inventarlo. Root cause = hallazgo #4. | §4.2, §4.14 |
| 17 | Confundir "fitness/fatigue/readiness" como sinónimos | **Aplica, mismo root cause** | Sin un Athlete State Model con esos tres campos separados, el lenguaje libre de los expertos los mezcla inevitablemente. | §4.2 |

---

## 3. Corrección de percepción más importante: el validador ya es mayormente determinista

Ambas auditorías (con especial énfasis en la sección "El Validator no debería pedirle al LLM hacer
matemáticas" de la propuesta de Gemini, y el punto 10 del análisis de ChatGPT) asumen que el sistema le pide al
LLM calcular sumas de horas, detectar fechas duplicadas o contar violaciones. **Esto no es así en el código
real.** Vale la pena corregirlo explícitamente porque cambia dónde hay que poner el esfuerzo.

`plan_validator.py::validate_weekly_plan` (líneas 206-459) es una función **puramente determinista**: recibe
un `WeeklyPlanPayload` ya generado y, sin ninguna llamada a LLM, calcula:

- fechas faltantes/duplicadas/inesperadas (comparando contra `week_dates`, líneas 234-258);
- horas por slot vs presupuesto de disponibilidad (líneas 264-310, con tolerancia 10%);
- reglas de coaching (calidad sin ritmo, repeticiones sin recuperación, calentamiento con ritmo en vez de FC,
  fuerza mal etiquetada) — todo por inspección de campos, sin LLM;
- el guardrail de ACWR (`current_acwr > 1.5`, líneas 443-466) — comparación numérica pura;
- (añadido en esta sesión) cruce de fechas contra el calendario de competencias.

El LLM (`_tier2_repair`, invocado solo cuando `validate_weekly_plan` ya encontró violaciones) **nunca hace el
cálculo** — recibe la lista de violaciones ya detectadas por Python y solo genera el contenido de reemplazo
(ej. "esta sesión debe durar menos, escribe los pasos que quepan en el presupuesto"). Y si el LLM tampoco
logra resolverlo, hay una cascada de *fallbacks deterministas* que sí hacen el ajuste final por código, sin
LLM: `_apply_tier1_fixes`, `_apply_missing_steps_fallback`, `_apply_missing_date_fallback`,
`_apply_duplicate_date_fallback`, `_apply_race_day_fallback`, `_apply_acwr_guardrail_fallback`.

**Esto es, de hecho, casi exactamente el patrón "hard constraints resueltos por código + LLM solo para
contenido" que ambas auditorías piden como mejora.** Lo que sí falta (y es real) es:

- una capa explícita de **negociación entre restricciones que compiten entre sí** cuando no se pueden
  satisfacer todas a la vez (§4.4);
- que el LLM de generación inicial (no solo el de reparación) reciba menos carga de razonamiento simultáneo
  (§4.5) — ese es un problema de **diseño del prompt de generación**, no del validador.

---

## 4. Análisis detallado por tema

### 4.1 Falta de política de resolución de conflictos entre expertos (Synthesis)

**Confirmado.** `synthesis_node.py:23-66` — el prompt completo del sintetizador es:

```
Integrate: Connect insights from metrics, activity, and physiology.
Contextualize: Relate data to the athlete's history and goals.
Simplify: Make complex relationships understandable.
```

No hay ninguna instrucción sobre qué hacer cuando Activity dice "ejecutó bien" y Physiology dice "recuperación
deteriorándose". El modelo resuelve esto de forma implícita e impredecible cada vez.

**Cómo abordarlo:** no la jerarquía rígida "Fisiología > Métricas > Actividad" que propone Gemini (ambas
auditorías coinciden en descartar esa versión). En su lugar, una **jerarquía de restricciones, no de
expertos** — la propuesta de niveles 0-4 del documento de Gemini/ChatGPT (Safety → Capacidad de recuperación →
Carga → Objetivo/estímulo → Calidad de ejecución) es razonable y debería codificarse como instrucción
explícita en `SYNTHESIS_USER_PROMPT_BASE`, no dejarse implícita. Esto es un cambio de prompt, de bajo costo de
implementación y alto impacto en consistencia.

### 4.2 Falta un Athlete State Model estructurado

**Confirmado — es el hallazgo más importante de ambas auditorías, y coincido.**

Hoy el flujo es: Garmin → `data_extractor.py` → `*_summarizer_node` (prosa) → `*_expert_node` (prosa
estructurada en 3 campos: `for_synthesis`/`for_season_planner`/`for_weekly_planner`) → estos textos se
concatenan en prompts posteriores. **En ningún punto existe un objeto con campos `fitness`/`fatigue`/
`readiness`/`confidence` con valores numéricos y metadatos de calidad.**

La única estructura numérica real hoy es `AthletePrescription` (`prescription.py:112-135`):
`hr_zones`, `threshold_pace_sec_per_km`, `pace_bands`, `ftp_watts`, `power_bands`. Es sólida para lo que cubre
(zonas y ritmos), pero es un **objeto de prescripción física**, no un modelo de estado del atleta — no tiene
readiness, fatiga, tendencia, ni score de confianza.

**Esto explica directamente el hallazgo #16 de la tabla** (HRV con umbrales absolutos inventados como "56-82
ms óptimo"): el season_planner no tiene ningún baseline individual estructurado que consultar, así que el LLM
inventa un rango razonable-sonante en su prosa libre. No es que el LLM "alucine porque sí" — es que no existe
la estructura de datos que le permitiría anclarse a algo real.

**Cómo abordarlo:** crear un módulo nuevo (ej. `services/ai/athlete_state.py`) que, a partir de los datos ya
extraídos por `data_extractor.py` (HRV, RHR, sueño, training load), calcule:

```python
@dataclass
class StateDimension:
    value: float | None
    baseline: float | None
    trend: Literal["improving", "stable", "declining"] | None
    confidence: float  # 0-1, basado en cobertura de datos
    data_coverage: float  # 0-1

@dataclass
class AthleteState:
    fitness: StateDimension
    fatigue: StateDimension
    readiness: StateDimension
    load_capacity: StateDimension
    injury_status: StateDimension
    confidence_overall: float
```

Esto se inyectaría junto a `prescription_card` en el prompt del weekly_planner y del season_planner, dándoles
números reales con baseline individual en vez de forzar al LLM a inventar un umbral universal.

**Nota de alcance:** esto es un componente nuevo, no una modificación menor — requiere decidir cómo calcular
baseline individual (ventana móvil de N días, ¿cuántos?), qué pasa con atletas sin suficiente historial, y
cómo versionar el cálculo sin romper generaciones anteriores. No es parte del Plan Maestro v4 actual.

### 4.3 ACWR como juez rígido

**Confirmado.** `plan_validator.py:443-466`:

```python
if (
    guardrail_enabled
    and not week1_is_deload
    and isinstance(current_acwr, int | float)
    and current_acwr > 1.5
    and weekly_budget is not None
):
    # ... fuerza semana 1 a 70-80% del presupuesto
```

Es un corte binario de una sola variable. El propio comentario del código ya cita la investigación de Gabbett
("~0.8-1.3 óptimo, >1.5 alto riesgo") como justificación — es decir, el equipo que escribió esto ya conocía la
literatura, pero la codificó como regla dura de todos modos. Las auditorías tienen razón en que la evidencia
2025 pide cautela (heterogeneidad metodológica, no usar como predictor individual determinista).

**Matiz importante que ambas auditorías no dan:** el guardrail actual **ya tiene un mecanismo de escape**
(`not week1_is_deload` — no se aplica si la semana ya es una semana de descarga por otra razón) y **solo
aplica a la semana 1**, nunca proyecta el riesgo hacia adelante. No es tan ciego como "ACWR 1.5 = frenar
siempre", pero sigue siendo de una sola variable.

**Cómo abordarlo (sin el Athlete State Model completo, como paso intermedio):** convertir el guardrail de un
corte binario a una función de varias señales ya disponibles hoy en `data_extractor.py`/`prescription.py`
aunque no estén todas conectadas al validador: ramp rate (ya se calcula, según el propio informe de auditoría
cita "Ramp Rate" entre las métricas ya presentes en el sistema), tendencia de carga crónica, y — cuando exista
el Athlete State Model — tolerancia histórica del atleta. Mientras tanto, el corte >1.5 puede quedarse como
**una señal de alerta que suma peso**, no como gate binario aislado.

### 4.4 Hard constraints vs soft constraints explícitos

**Parcialmente ya existe, sin estar nombrado ni diseñado como tal.**

De facto, hoy existen dos categorías:

- **Cosas que el sistema garantiza siempre** (hard, de facto): disponibilidad del atleta, deporte permitido,
  presupuesto de horas, que ningún día quede vacío/duplicado/ausente, que un día de carrera se refleje. Todas
  tienen fallback determinista que las fuerza al final de la cascada de reparación.
- **Cosas que el LLM decide con libertad** (soft, de facto): estructura exacta de una sesión de calidad,
  progresión de volumen, distribución de intensidades semana a semana.

Lo que falta es la capa intermedia que las auditorías piden: cuando dos restricciones compiten (ej. "el
atleta solo tiene 30 minutos disponibles el martes" + "el plan de temporada pide una sesión de calidad de 45
minutos ese día"), el sistema hoy no tiene una política explícita de qué sacrificar — cada regla se resuelve
de forma aislada en el validador, no hay una noción de "trade-off consciente" comunicada al atleta.

**Cómo abordarlo:** esto requiere el Decision Engine (§4.2 lo alimenta) — no es una regla más del validador,
es una capa de razonamiento ANTES de generar el plan, no después. Bajo prioridad hasta que el Athlete State
Model exista, porque sin datos estructurados no hay sobre qué negociar.

### 4.5 Weekly Planner con demasiadas responsabilidades en un solo prompt

**Confirmado, y es probablemente el hallazgo con mejor relación costo/impacto de todo el documento.**

`WEEKLY_PLANNER_USER_PROMPT` (`weekly_planner_node.py`) en su forma actual mezcla, en un solo prompt por
llamada:

1. Reglas de disponibilidad (qué días/deportes están permitidos).
2. Cruce contra calendario de competencias y taper (añadido en esta sesión, Track C).
3. Estructura de intervalos (`repeat` con `substeps`, con dos ejemplos completos en JSON).
4. Reglas de fuerza (ahora simplificadas a un solo paso libre, Track A).
5. Reglas de modalidad de intensidad (ritmo vs zona de FC vs potencia, con explicación de por qué).
6. Contexto de mesociclo, plan anterior, adherencia, resumen de semanas previas.
7. Semántica exacta de cada campo del schema de salida.

Esto es exactamente el patrón que el fenómeno *lost-in-the-middle* predice como frágil — y **es consistente
con lo observado en 3 corridas reales de esta sesión**: el nodo que hace esto (`weekly_planner`) es el que
concentra sistemáticamente el 90%+ del costo y la inmensa mayoría de las violaciones de validación, mientras
que los demás nodos (summarizers, expertos, season_planner) generan salidas limpias con mucho menos prompt.

**Importante — distinguir dos ejes de mejora que se confunden fácil:**

- **Chunking por semana** (generar 1 semana por llamada, no 4) — **ya existe** desde antes de esta sesión
  (`weekly_planner_node.py:42-49`, comentario explícito documentando por qué), y el Plan Maestro v4 Track B lo
  lleva un paso más allá (generar 1 semana *bajo demanda*, no 4 semanas en la misma corrida). Esto reduce el
  TAMAÑO del payload por llamada.
- **Separación reasoning → intent → format** (la propuesta de 3 capas de la auditoría) — **no existe**, es un
  eje distinto: reduce el NÚMERO DE RESPONSABILIDADES simultáneas dentro de una misma llamada, sin importar
  cuántos días cubra. Ambos ejes son complementarios, no sustitutos uno del otro.

**Cómo abordarlo:** dividir `weekly_planner_node` en (al menos) dos pasos encadenados dentro del mismo nodo o
como dos nodos:

1. **Training Decision Engine**: recibe Athlete State + prescripción + disponibilidad + competencias +
   adherencia, y devuelve una intención por día en lenguaje natural corto y estructurado (`"Martes: estímulo
   VO2max, readiness moderada-alta, evitar carga excesiva de tren inferior"`) — sin preocuparse por el schema
   de Garmin.
2. **Workout Formatter**: recibe SOLO esa intención (ya reducida, sin todo el contexto crudo) + las reglas de
   unidades/schema, y la convierte en `WeeklyPlanPayload`. Este paso es casi mecánico, ideal para un modelo
   barato o incluso para una plantilla determinista con relleno de contenido puntual por LLM.

Esto es un refactor de arquitectura del nodo, no un ajuste de texto — requiere decidir si son 2 llamadas LLM
encadenadas (más costo, pero cada una más simple y confiable) o 1 llamada con el prompt reestructurado en dos
secciones internas claras. Recomiendo la primera opción como experimento inicial, midiendo si el costo extra
de la segunda llamada se compensa con menos reparaciones tier-2 (que hoy también cuestan dinero y tiempo).

### 4.6 Nutrición/hidratación ausente

**Confirmado, sin matices.** Grep completo de `services/ai/` para `carboh|hidrat|sodium|fueling|nutrition` no
arroja ningún resultado fuera de comentarios genéricos. El sistema no calcula ni sugiere combustible para
ninguna sesión, sin importar su duración.

**Cómo abordarlo:** la recomendación de ambas auditorías (target contextual, no regla fija ">90min = 90g/h")
es correcta. Esto encaja naturalmente como un campo opcional en el `WorkoutDay`/`WorkoutStep` cuando
`total_duration_minutes` supera un umbral (ej. 75-90 min) o la sesión está marcada como día de competencia
(ver Track C, que ya identifica el día de carrera estructuralmente — es el punto de enganche natural). No
requiere un nodo nuevo, es una extensión del schema (`plan_outputs.py`) y del prompt del weekly_planner.

### 4.7 Fuerza: freeform vs metadata de estrés — reconciliar con la decisión ya tomada

**Aplica como mejora futura, pero hay que ser preciso sobre qué NO hay que revertir.**

Esta misma sesión implementó (Plan Maestro v4, Track A, commit `d0d6c4c`) que las sesiones de fuerza dejen de
requerir estructura de series/repeticiones prescrita por la IA — **por instrucción explícita del usuario**,
después de que la estructura anterior fallara 12 de 12 veces en una corrida real y consumiera la mayoría del
presupuesto de reparación. El resultado verificado: en la siguiente corrida real, fuerza generó contenido
correcto sin ninguna reparación por IA.

La propuesta de ambas auditorías ("`strength_metadata`: fase, estrés excéntrico, RPE, carga de tren
inferior/superior") **no contradice esto** — es una capa adicional de metadata *ligera* sobre la sesión
freeform, no una vuelta a prescribir series/reps. El objetivo de esa metadata no es que la IA controle la
sesión, sino que el sistema entienda **cuánto puede interferir esa sesión con el entrenamiento aeróbico de los
días siguientes** (una sesión de fuerza pesada de tren inferior debería poder influir en si el día siguiente
es una sesión de calidad o un día fácil).

**Cómo abordarlo:** añadir campos opcionales y ligeros al `WorkoutDay` cuando `sport == "strength"` —
`estimated_lower_body_stress: Literal["low","moderate","high"]` y similar — sin pedirle a la IA que estructure
series/reps. Esto es una extensión pequeña del schema, compatible con la decisión ya tomada, y con impacto
real solo si además existe una regla (validador o prompt) que module la sesión del día siguiente en función de
ese estrés — lo cual depende, otra vez, de tener algún tipo de "estado" que cruce información entre días
consecutivos (hoy cada día se genera con reglas de "evitar sesiones consecutivas de alta intensidad" pero de
forma cualitativa, en texto libre del prompt, no como una restricción estructurada).

### 4.8 Adherence Drift Monitor

**Parcialmente ya existe.** `services/ai/adherence.py` (308 líneas) ya:

- empareja actividades reales de Garmin con sesiones prescritas por fecha+deporte;
- calcula estado por sesión (`completed`/`modified`/`missed`/`pending`) con tolerancia de duración del 25%;
- distingue explícitamente "sesión de hoy" como `pending`, no `missed` (evita falsos negativos);
- inyecta el resultado en el prompt de la siguiente generación.

Lo que falta, y es exactamente lo que piden las auditorías: **agregación a nivel de macrociclo**. Hoy la
adherencia es por-sesión y por-generación; no hay una función que compare varias semanas y clasifique la
deriva como GREEN/YELLOW/RED, ni que distinga el peso de perder una sesión clave (una serie de calidad) vs una
sesión fácil de relleno.

**Cómo abordarlo:** una función nueva sobre el output ya existente de `adherence.py` — no requiere tocar la
extracción de Garmin ni el emparejamiento, solo agregar sobre `SessionAdherence` ya calculado a través de
varias generaciones históricas (que ya se archivan en `data/storage/<perfil>/history/`, ver Fase 4 del Plan
Maestro v3). Impacto alto, costo de implementación moderado — es lógica pura sobre datos que ya existen.

### 4.9 Performance Response Engine (ciclo cerrado)

**No existe, es el hallazgo más ambicioso y el que más transforma el producto.**

Hoy el ciclo es: prescribir → (opcionalmente) el atleta ejecuta → `adherence.py` compara duración/tipo real
vs. planeado. **Nunca se cierra el ciclo hacia la interpretación fisiológica de la respuesta** (deriva de FC
durante la sesión, HRV/RHR del día siguiente, si la sesión "costó" lo esperado o más/menos).

**Cómo abordarlo:** este es, correctamente, el ítem de mayor impacto pero también el que más depende de tener
primero el Athlete State Model (§4.2) — sin un baseline individual de referencia, "HR drift 3.2%" no significa
nada por sí solo. Recomiendo tratarlo como el último bloque grande a construir, después de State Model +
Decision Engine, no en paralelo.

### 4.10 Modelo explícito de incertidumbre/confidence

**Parcialmente ya existe, pero solo como prosa, no como dato.** Los prompts de los expertos ya piden razonar
en términos de "Signals → Evidence → Implications → Uncertainty" — es decir, la INTENCIÓN de expresar
incertidumbre ya está en el diseño de prompts. Lo que falta es que esa incertidumbre se convierta en un
**campo estructurado** (`confidence: float`, `data_coverage: float`) que el Decision Engine pueda usar para
decidir cuánto peso darle a una señal, en vez de quedar enterrada en un párrafo de texto que nadie más lee
programáticamente.

**Cómo abordarlo:** depende directamente de §4.2 — es, de hecho, uno de los campos que `StateDimension`
debería tener. No es un ítem separado, es una propiedad del Athlete State Model.

### 4.11 Data Quality Gate

**No existe formalmente como gate centralizado, aplica.** Hoy hay fallbacks puntuales y locales (ej.
`compute_zone_config` prefiere zonas reales de Garmin y cae a la fórmula de Friel solo si no hay datos reales,
documentado en Plan Maestro v3 Fase 1) pero no hay un paso explícito, antes de la planificación, que evalúe
"¿tengo suficientes datos confiables para tomar esta decisión?" de forma centralizada y que produzca un score
de confianza consultable.

**Cómo abordarlo:** de menor prioridad que el resto — los fallbacks puntuales ya cubren los casos más críticos
(ritmo umbral, zonas de FC). Un gate centralizado tiene sentido una vez exista el Athlete State Model, como la
función que calcula `data_coverage`/`confidence` para cada dimensión del estado, no como un componente
independiente nuevo.

### 4.12 Doctor Agent no debería ser el gate médico final

**Confirmado, y es un hallazgo real con implicación de seguridad, no solo de calidad.**

`services/ai/doctor_agent.py:32-37`: cuando cualquiera de los 7 flags PAR-Q+ es positivo, la única consecuencia
codificada es una instrucción de prompt: *"you MUST include an explicit recommendation... do not just proceed
to write training guidelines as if nothing happened"*. Esto depende enteramente de que el LLM obedezca esa
instrucción cada vez, en cada corrida, sin excepción.

**Verificado en `web_app/server.py`:** no existe ningún código que lea `par_q_flags` del perfil y bloquee o
condicione `/api/generate` de forma determinista. Si el LLM del doctor agent alguna vez no incluye la
recomendación (o si el atleta nunca pasó por la consulta del doctor agent para ese perfil), no hay una segunda
capa de seguridad que lo detecte.

**Cómo abordarlo — coincido con la auditoría:** esto debería ser una regla dura en `server.py`, no una
instrucción de prompt:

```text
PAR-Q flags positivos en el perfil
        ↓
   GATE DETERMINISTA (Python, en server.py, antes de /api/generate)
        ↓
   ┌────┴────┐
   ↓         ↓
Sin flags   Con flags activos
   ↓         ↓
Pipeline   Bloquear generación / requerir
normal     confirmación explícita de "consulté
           a un médico" antes de continuar
```

Este es, de todos los hallazgos "no existe", el que tiene el argumento más fuerte para priorizarse
independientemente del resto (no depende del Athlete State Model ni de ningún otro componente grande) —
es una verificación de una bandera booleana antes de un endpoint, de bajo costo de implementación.

### 4.13 "Deload cada 4 semanas" — ya es más flexible de lo que asumen las auditorías

**Corrección de percepción.** El texto real en `season_planner_node.py` no es una regla de código, es una
instrucción al LLM dentro de `SEASON_PLANNER_USER_PROMPT`:

> "default to a deload/recovery week every 4th week of the season... UNLESS the athlete's context (recovery
> metrics, race calendar, low current volume) clearly justifies a different cadence — you decide the exact
> weeks"

El `deload_weeks` resultante es un campo que el LLM decide libremente por fase, y `compute_mesocycle_position`
(`weekly_planner_node.py:297-327`) solo LEE esa decisión de forma determinista para que el weekly_planner sepa
qué semana está generando — no impone la cadencia. Es decir, **la heurística "cada 4 semanas" ya es una
sugerencia con excepción explícita, no una ley de código**, aunque el LLM puede optar por seguirla literalmente
si no encuentra una razón mejor (que es, en la práctica, lo que suele pasar sin un Athlete State Model que le
dé una razón mejor — otra vez, root cause = §4.2).

**Cómo abordarlo:** no requiere cambio de código por sí solo. Mejora naturalmente una vez exista el Athlete
State Model, porque el LLM tendría datos reales de tendencia de fatiga/readiness para decidir con criterio en
vez de caer en la sugerencia por defecto.

### 4.14 HRV como umbral absoluto en el reporte real observado

Ver §4.2 — mismo root cause. El texto citado en la corrida real ("HRV optimal zone 56.0-82.0 ms", "if HRV
drops below 56ms threshold") es contenido libre generado por el LLM del season_planner dentro de su
`markdown`, no una constante en el código de la app. La corrección que proponen ambas auditorías (comparar
contra baseline individual + tendencia, no umbral absoluto) es correcta, y su implementación real depende de
que exista un baseline individual estructurado y accesible — que es exactamente lo que el Athlete State Model
provee.

---

## 5. Relación con el Plan Maestro v4 en curso

El Plan Maestro v4 (`.claude/plans/`) ya cubre, con commits reales en esta sesión, varios síntomas que ambas
auditorías observaron en la misma corrida:

| Track | Qué resuelve | Relación con esta auditoría |
|---|---|---|
| **A** (commit `d0d6c4c`) | Fuerza deja de requerir estructura prescrita, va directo al fallback determinista sin gastar reparación por IA | Base para §4.7 — la metadata de estrés se añadiría ENCIMA de esto |
| **C** (commits `5989d62`, `bc9aacf`) | El plan cruza fechas contra el calendario de competencias; día de carrera y taper ya no se pierden | No cubierto por ninguna de las dos auditorías externas (no vieron el código de competencias) |
| **D** (commit `698fe66`) | Ningún día desaparece en silencio; mensajes de error honestos por tipo de violación | Ninguna de las dos auditorías señaló específicamente el bug de mensajes engañosos, aunque ChatGPT sí notó el patrón general de "demasiados errores estructurales" |
| **B** (pendiente) | Generación semana a semana bajo demanda, en vez de 4 semanas de una sola corrida | Reduce el tamaño del payload — complementario, no sustituto, de la separación reasoning/intent/format de §4.5 |
| **E** (pendiente) | Señales de recuperación en vivo (HRV/training load balance) inyectadas como números reales en el prompt | Es un paso PARCIAL hacia el Athlete State Model de §4.2 — cubre "readiness" pero no fitness/fatigue/confidence como dimensiones separadas |
| **G** (pendiente) | Explicación semanal generada por IA citando números reales | Relacionado con §4.10 (hacer visible el razonamiento), aunque no resuelve la falta de confidence estructurado |
| **F** (pendiente) | Tope real de rampa de volumen semana-a-semana | Relacionado con §4.3 (ACWR menos rígido) pero es una señal adicional, no un reemplazo del guardrail |

**Conclusión de esta sección:** el Plan Maestro v4 y esta auditoría no compiten por los mismos recursos — el
v4 arregla la capa de ejecución/validación (por qué el plan sale roto), y esta auditoría apunta a la capa de
decisión/razonamiento (por qué el plan, aunque salga bien formado, no razona como lo haría un entrenador que
recuerda el historial del atleta). Son secuenciales: tiene más sentido terminar el v4 (que ya está en
progreso y cerca de completarse) antes de empezar el Athlete State Model, porque el State Model se alimentaría
de datos (HRV en vivo, adherencia, prescripción) cuya extracción y fontanería el v4 ya está terminando de
estabilizar.

---

## 6. Priorización recomendada

Ajustando la priorización de ambas auditorías a lo que el código ya tiene vs. no tiene:

### Ya resuelto o en progreso activo (no re-priorizar, solo terminar)
- Validador determinista (§3) — ya está, no requiere trabajo adicional salvo lo que el v4 ya cubre.
- Plan Maestro v4 completo (Tracks B, E, F, G pendientes) — continuar como está planeado.

### Prioridad 1 — bajo costo, alto impacto, independiente del resto
1. **Gate médico determinista** (§4.12) — una verificación booleana en `server.py`, sin dependencias.
2. **Jerarquía de restricciones en Synthesis** (§4.1) — cambio de prompt, sin cambio de arquitectura.
3. **Separación reasoning/intent/format en Weekly Planner** (§4.5) — el ítem de mejor relación costo/impacto
   del análisis completo, porque ataca directamente la causa que ya se observó consumir 90% del costo y
   generar la mayoría de las reparaciones fallidas.

### Prioridad 2 — impacto alto, requiere diseño de datos nuevo
4. **Athlete State Model** (§4.2) — el componente que más otros ítems desbloquea (§4.3, §4.9, §4.10, §4.13,
   §4.14 dependen de él).
5. **Adherence Drift Monitor a nivel de macrociclo** (§4.8) — lógica sobre datos que ya existen, costo de
   implementación moderado.

### Prioridad 3 — depende de lo anterior
6. **Decision Engine / hard-vs-soft constraints explícitos** (§4.4) — depende de tener el State Model.
7. **ACWR multivariable** (§4.3) — depende parcialmente del State Model para tolerancia histórica.
8. **Performance Response Engine** (§4.9) — el más ambicioso, depende de State Model + Decision Engine.

### Prioridad 4 — extensiones de producto, independientes del núcleo
9. **Nutrición/hidratación** (§4.6) — extensión de schema, se puede hacer en paralelo a cualquier otra cosa.
10. **Strength metadata** (§4.7) — extensión ligera sobre la decisión freeform ya tomada.
11. **Data Quality Gate centralizado** (§4.11) — de menor urgencia, los fallbacks puntuales ya cubren lo
    crítico.

---

## 7. Qué NO implementar tal como lo proponen las auditorías

Ambas auditorías (la de ChatGPT explícitamente, revisando la propuesta de Gemini) ya descartan varias de sus
propias ideas más simplistas. Confirmando y agregando matices desde el código real:

1. **"Fisiología siempre manda sobre actividad"** — descartado correctamente por ambas. El código real ya no
   tiene ninguna jerarquía de expertos codificada (§4.1), así que no hay nada que "corregir" en ese sentido —
   hay que AÑADIR una jerarquía de restricciones, no reemplazar una jerarquía de expertos que no existe.

2. **"Fuerza = fatiga del SNC" como regla causal automática** — descartado correctamente. No hay ninguna
   regla así en el código hoy tampoco (fuerza ni siquiera influye en absoluto en sesiones de otros días
   actualmente) — el riesgo es real solo si se implementa mal §4.7 más adelante.

3. **"ACWR > 1.5 = automáticamente peligroso" como único criterio** — el código real ya tiene este patrón
   exacto (§4.3), y ambas auditorías tienen razón en pedir que se relaje. Pero ojo: relajarlo sin poner nada
   en su lugar sería peor que dejarlo — el guardrail actual, aunque rígido, es la única protección real contra
   sobrecarga aguda que existe hoy. No eliminar sin reemplazar por algo multivariable funcional.

4. **"Más preguntas médicas hasta aislar el riesgo" como criterio autónomo del LLM** — coincido en descartarlo.
   El prompt actual del doctor agent (`doctor_agent.py:53`) ya limita a 3 preguntas por turno, lo cual es
   razonable — el problema real no es el límite de preguntas, es que **no hay ningún gate determinista
   downstream** (§4.12), que es un problema distinto y más urgente que ajustar el número de preguntas.

5. **Implementar el Athlete State Model completo, el Decision Engine, Y el Performance Response Engine en un
   solo esfuerzo** — ninguna de las dos auditorías lo sugiere explícitamente, pero el diagrama de arquitectura
   final que ambas proponen puede leerse como "todo junto". Dado el tamaño de cada componente y que se
   depende uno del otro en cascada (§6), la secuencia importa: State Model primero, con datos reales
   verificados contra la cuenta de Garmin en cada paso — el patrón que este proyecto ya usa consistentemente
   (verificar contra datos reales antes de asumir que un cálculo es correcto) — no todo junto de una vez.
