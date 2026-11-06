# 02 — Arquitectura y reglas de producto

Guía para cualquier agente (Antigravity, Claude Code u otro) al trabajar en este repositorio. Antes vivía en
el `CLAUDE.md` de la raíz, que ahora sólo la importa. Léela después de `01_COMO_TRABAJAMOS.md`.

> Éste es el documento de la RAÍZ y describe la app entera. `contexto/03_CEREBRO.md`
> describe el cerebro con mucho más detalle (esquemas de entrenamiento, unidades,
> validador, archivo de razonamiento) y sigue siendo la referencia buena para todo
> lo que pase dentro de esa carpeta.

## Qué es esto

**Power Coach AI**: un entrenador de triatlón que lee los datos reales del atleta
en Garmin Connect, los pasa por un pipeline multiagente (LangGraph, con
Claude/GPT/Gemini según el rol), escribe un macrociclo y semanas estructuradas, y
las sube al reloj del atleta como entrenos de verdad.

El hilo conductor del proyecto: **un plan que PARECE escrito por un entrenador no
es lo mismo que un plan seguro de correr.** Casi todos los fallos caros de este
repo han sido exactamente ese hueco — pasos con la forma correcta y las unidades,
las zonas o el ritmo equivocados. Al tocar cualquier cosa que produzca o suba un
entreno, verifica contra los números REALES de un atleta, no contra la validez del
esquema.

## Las cuatro piezas y los tres saltos

```
Frontend/      Vue 3 + Vite + Tailwind + Pinia      :5173   lo que ve el atleta
   |  HTTP + JWT
Backend/       PHP sin framework, atributos #[Route] :8080   dueño de MySQL
   |  HTTP + X-Service-Token
AiService/     FastAPI; importa PaginaLocal          :8600   el puente
   |  import directo
PaginaLocal/   el cerebro: LangGraph, Garmin, prompts
```

**MySQL es la fuente de la verdad.** `PaginaLocal` fue una app de escritorio para
una sola persona y sigue leyendo y escribiendo ficheros; `AiService/bridge/
storage_adapter.py` le monta un **espejo de usar y tirar** en
`AiService/_runtime/storage/u<id>/` antes de cada llamada. Ese directorio se puede
borrar entero sin perder nada — salvo dos ficheros que NO son espejo y son
historial de verdad:

- `track_record.jsonl` — una línea por semana cerrada (lo prescrito, lo ejecutado,
  la calidad que se cayó). Es de donde salen las gráficas y la memoria larga del
  macrociclo. Desde la Beta 3.0 tiene copia en MySQL (Database/034, `athlete_track_record`,
  con el texto exacto de cada línea), escrita al terminar cada generación y al borrar un
  plan; el fichero sigue mandando (`comparar_expediente.php`).
- `reasoning/<ISO>/` — la traza de cada generación, con los prompts exactos que
  recibió cada modelo (`prompts/`, capturado por `services/ai/prompt_capture.py`).

### Dónde está el log de una generación que falló

**En `AiService/_runtime/jobs/<job_id>/worker.log`, y en ningún otro sitio.**

Una generación NO corre dentro de uvicorn: `_spawn_job` lanza un proceso worker
aparte (`AiService/worker.py`), así que la consola del AiService no tiene ni una
línea de ella. Y dentro de ese proceso `web_app/server.py` reemplaza `sys.stdout`
por un `StdoutRedirector` que acumula en `LOG_LIST` — una lista en memoria que el
AiService no sirve por ninguna ruta. Un `print()` de la generación, por tanto,
**muere con el proceso**.

Se perdieron tres depuraciones seguidas por buscarlo en el sitio equivocado, una
de ellas empujada por el propio mensaje de error, que decía "revisa la consola".
Junto al log están `spec.json` (todo lo que recibió el worker, incluido el perfil)
y `status.json` (lo que vio el frontend).

**`Database/` son migraciones numeradas y se corren A MANO**, también en
producción. Por eso todo lo que las use tiene que aguantar que todavía no estén:
ver `EventRepository::record()`, que se traga sus propios errores porque la llama
el router en cada petición.

## El fallo que se repite en este repo

Un campo que el consumidor nunca rellena, un valor por defecto que lo tapa, y
ningún error en ninguna parte. Ha pasado con `weekly_plan`, `season_plan_md`,
`season_phases`, `summary`, la cinta de correr, `travel_frequency`,
`cross_training_open`, el precio de la caché, `cache_creation` de
`langchain-anthropic`, y la ficha de números del entrenador del chat.

**Antes de dar por bueno que un dato llega, míralo en el prompt real archivado**
(`reasoning/<ISO>/prompts/*.json`). `PaginaLocal/scripts/auditar_conexiones.py`
hace la comprobación equivalente un nivel más arriba, con las RUTAS: cada llamada
del frontend tiene su `#[Route]`, y cada llamada del puente su `@app.<verbo>`.

## Comandos

Los tres servidores, cada uno en su carpeta. **Nunca los levantes con `&` desde
Bash**: se quedan huérfanos.

```bash
# Backend (en Backend/)
C:/xampp/php/php.exe -S localhost:8080 router.php
```

```bash
# Frontend (en Frontend/)
npm.cmd run dev
```

```bash
# AiService (en AiService/) — necesita MySQL y XAMPP arriba
python -m uvicorn main:app --host 127.0.0.1 --port 8600
```

```bash
# Pruebas: unittest de la stdlib, sin pytest en PaginaLocal
cd PaginaLocal && python -m unittest discover -s tests
```

```bash
# Pruebas del puente: éstas sí con pytest
cd AiService && python -m pytest tests -q
```

```bash
# Análisis estático (no está en el CI a propósito: ese despliega a producción).
# Dos comandos, no encadenados: los dos salen con código != 0 sobre su línea
# base, así que un `&&` entre ellos hace que el segundo NO se ejecute nunca.
python -m ruff check .
python -m vulture
```

`vulture` va **sin argumentos**: las rutas y el `min_confidence` están en
`pyproject.toml`, y pasarle `.` las PISA — se pone a escanear `tests/` y sube el
ruido de 187 hallazgos a 576. No existe ningún `vulture_whitelist.py`; esa
palabra estuvo escrita aquí, en `contexto/03_CEREBRO.md` y en `pyproject.toml`
sin que el archivo llegara a existir.

Estado al día de hoy (2026-09-15): **2.091 pruebas en PaginaLocal, 73 en AiService**, `ruff`
con 21 hallazgos de línea base y `vulture` con 189 — casi todos campos de
modelos Pydantic y callbacks de LangChain, que son falsos positivos por
construcción. Lo que se revisa es el DELTA, no el total.

En local hay `POST /auth/dev-login` con `{"slot": 1|2|3}`, que sólo responde desde
127.0.0.1 y **no existe en `main`**. Atleta 1 es Miguel, el administrador.

## Reglas de producto que el código tiene que respetar

- **Los costos son SÓLO del administrador.** A los atletas beta se les dijo que
  pagarían el costo exacto de IA, y enseñárselo les hace no usar la app para no
  gastar — que es justo lo contrario de lo que un MVP necesita medir. `#[Authorize
  (['admin'])]` en `UsageController`, `store.isAdmin` en el frontend, y **en la API**:
  toda respuesta que lleve el plan pasa por `App\Support\SinCostos::paraQuien` (hasta el
  2026-10-19 `/plan` mandaba `total_cost_usd` y el desglose por modelo a cualquier atleta).
- **Nada se sube a `main`.** Todo va a la rama de trabajo hasta que se acuerde un
  merge.
- **Nada se borra en silencio.** Retirar una anotación del cuaderno la marca con
  fecha de baja y conserva el texto. Desde el 2026-10-11 (decisión del
  responsable) «Borrar» también RETIRA; sólo una ya retirada se borra de verdad,
  con «Borrar para siempre» (`DELETE /notebook/{id}?para_siempre=1`): sus datos
  siguen siendo suyos.
- **Recortar sin decirlo está prohibido.** Todo bloque truncado lo anuncia en el
  texto. Un modelo que no sabe que está mirando un extracto completa el resto con
  seguridad.

## El cerebro, en corto

Dos flujos de LangGraph (`PaginaLocal/services/ai/langgraph/workflows/`):

- **Análisis**: 3 resumidores → 3 expertos (métricas, fisiología, actividad) →
  orquestador → síntesis.
- **Análisis + planificación** (el que corre la app): la misma rama de análisis en
  paralelo con `data_integration → season_planner → weekly_planner →
  plan_formatter`, unidas en un nodo barrera `finalize`.

**Cuidado con `finalize`**: usa `defer=True` y TIENE que devolver `{}`, nunca el
estado. Cada lista y cada dict de `TrainingAnalysisState` lleva un reductor
acumulador, así que devolver el estado lo mezcla consigo mismo y duplica costes y
tokens en silencio. Ya pasó una vez.

El planificador semanal está partido en dos llamadas: **decisión** (qué entrena
cada día y por qué, en lenguaje de entrenador) y **formateo** (la codificación al
esquema de Garmin, sin criterio). Un modelo que decide y codifica a la vez emitía
`steps=[]` en el 100% de los días.

### Qué modelo corre cada rol (`services/ai/ai_settings.py`)

**El tier lo decide `AI_MODE` en el `.env` de la RAÍZ, y el entorno le gana
siempre al `ai_mode` de la base de datos** (`AiService/main.py::_profile_dict`).
Producción corre `standard`, así que el `.env` local también: probar en `pro` es
probar otros modelos.

La columna `standard` está medida, no supuesta: sale del banco del 2026-09-23 (~156 réplicas sobre prompts
reales de los atletas 2 y 3, cada nodo con su criterio; el detalle y los números en el comentario de
`ai_settings.py`), aplicado ese mismo día y verificado con un macrociclo y una semana reales del atleta 2.

| Rol | `standard` (producción) | `pro` | Por qué |
|---|---|---|---|
| `SUMMARIZER` ×3 | `gpt-6-luna` | `gemini-3.1-flash-lite` | comprime 40:1; 0 % de números sin respaldo y 3× más datos que flash-lite |
| `METRICS/PHYSIOLOGY/ACTIVITY_EXPERT` | `gpt-5-mid` | `claude-sonnet-5` | aquí leer se convierte en criterio; el más concreto del banco |
| `SYNTHESIS` | `gpt-6-luna` | `gpt-5` | el informe del atleta; prudente y 0-2 % sin respaldo, a 0,002 USD |
| `SEASON_PLANNER` | `gpt-6-sol` | `gpt-5-search` | cumple la petición del atleta (gpt-5.2 la frenaba "porque hacía menos") |
| `WORKOUT` (decisión semanal) | `gpt-5-mid` | igual | el único que cumple la petición sin pagarla con la carrera en los tres escenarios |
| `WORKOUT_ENCODER` | `claude-haiku-4.5` | igual | codificación; banco del 2026-09-24 (14 semanas): flash-lite omitía strides y llegó a poner 6 × 50' por 6 × 1' en el reloj. Haiku falla hacia abajo y siempre igual |
| `COACH_CHAT` | `gpt-6-luna` | `gpt-5-mid` | corre tantas veces como el atleta escriba; 0,002 USD por mensaje |
| `NOTEBOOK` (cuaderno del chat) | `gemini-3.1-flash-lite` | `gpt-5-mid` | rol propio: con Luna se perdieron 2 de 3 anotaciones |
| `STRENGTH_ROUTINE_READER` | `gemini-3.1-flash-lite` | igual | lee la rutina pegada; es compresión, y el código comprueba cada línea |
| `STRENGTH_STEP` (paso de fuerza) | `gpt-6-luna` | `gpt-5-mid` | dosis y cambios sobre su programa; cumple "menos series, misma carga" a 0,0007 USD |
| preguntas del coach | modelo del experto de fisiología | igual | una llamada, antes de los expertos |
| `FORMATTER` | — | — | los dos formateadores son deterministas desde v5 |

**`standard` NO es "el tier Gemini" ni "el tier Lite"**, y eso se afirmó durante
meses en `contexto/operacion/DEPLOYMENT.md`, en `AiService/.env.example` y en `contexto/03_CEREBRO.md`.
Y esta tabla tampoco decía la verdad hasta 2026-09-11: cuatro filas de la columna
`standard` traían en realidad los valores de `pro`, y afirmaba que entre tiers sólo
cambiaban los expertos. La fuente es `ai_settings.py`; si esta tabla y ese archivo
discrepan, manda el archivo y hay que corregir la tabla. Hacen falta las TRES
claves de proveedor en cualquiera de los dos tiers.

**La regla que gobierna la tabla: paga por un modelo donde haya CRITERIO, nunca
donde sólo haya COMPRESIÓN.** Y no se cambia una línea por la tabla de precios:
se replica el nodo contra un prompt real archivado
(`PaginaLocal/scripts/replay_node.py`) y se cambia con el número en la mano. GPT-5
factura los tokens de razonamiento a precio de salida, así que la aritmética no es
evidencia.

**Nunca pases `thinking` explícitamente a Claude Opus 5 / Sonnet 5**: piensan de
forma adaptativa cuando el parámetro se OMITE, y pasarlo empuja a
`langchain-anthropic` a un camino de tool-calling forzado que puede lanzar
`OutputParserException`.

**Nunca pidas JSON en prosa.** `with_structured_output` y que el proveedor imponga
la forma. Pedirlo en prosa rompió el chat del doctor con `MALFORMED_RESPONSE`.

### Cada token de salida se paga: se escribe sólo lo que alguien lee (2026-09-23)

Semana real del atleta 2: 0,228 USD, 67 % tokens de salida. Tres cosas se pagaban sin que nadie las leyera:

- **Los expertos escribían sus tres secciones en cada generación** y una semana sólo lee
  `for_weekly_planner`. Ahora cada flujo dice qué lee (`receptores_de_expertos`: la semana
  `for_weekly_planner`; la temporada `for_season_planner` y `for_synthesis`) y el esquema pide sólo eso
  (`expert_outputs.salida_para`, con una línea en el prompt). Sin la clave, las tres como siempre.
- **El codificador reescribía el racional, el propósito y la adaptación** que ya había escrito la decisión (el
  62 % de su salida). Ya no están en su esquema; `_apply_discipline` los copia de la decisión.
- **El paso de fuerza explicaba ejercicio a ejercicio lo que ya explica la dosis.**

**Esos dólares estaban a la mitad** (ver abajo): la tabla cobraba gpt-5.2 a $0,88/$7, el precio de Batch; el
estándar es $1,75/$14 (corregido el 2026-09-23, `tests/test_precio_y_kilometros.py`). Con el precio real una
semana normal ronda 0,30 USD y un macrociclo 0,41. Los porcentajes de abajo siguen valiendo.
Medido en la misma semana regenerada: 0,187 USD (−18 %); salida de los expertos 9.014 → 4.880 tokens y del
codificador 5.141 → 3.450. Y la sección que SÍ se lee creció (p. ej. actividad 2.592 → 4.632 caracteres): el
experto dedica a lo que importa lo que antes repartía entre tres lectores.

### Los dos chats

- **Coach** (`services/ai/coach_chat.py`): sólo lectura, nunca edita el plan.
  Contexto medido sobre el atleta real: **50.837 caracteres (~12.700 tokens) por
  mensaje**, y todo está acotado — 16 turnos de conversación, 40 anotaciones de
  cuaderno, UNA generación archivada con presupuesto por artefacto, 8 semanas
  pasadas resumidas. El bloque de sistema
  entero es el prefijo cacheado.
- **Ante una molestia, en proporción** (2026-09-23): con "rodilla 2/10 desde el 5K" los cuatro modelos baratos
  cancelaban la carrera de la semana. El prompt sigue ahora el modelo de monitorización del dolor (Silbernagel
  2007): se entrena con ajustes si no sube durante ni al día siguiente; se quita lo que lo provoca si sube,
  cambia la forma de moverse o hay hinchazón o inestabilidad; sin número, se pregunta.
- **El Doctor (asistente de salud) ya no existe**: su chat salió de la pantalla en la v1.1, la 017 borró su
  informe y la Beta 3.0 (F0) quitó el agente, sus rutas y su rol de modelo. La salud llega a los planificadores
  sólo por los campos que el atleta rellena (`health_context.render_health_block`).

**El cuaderno lo escribe un paso APARTE**, `notebook.propose_entries()`, que corre
DESPUÉS de la respuesta del coach y sólo ve el intercambio y el cuaderno (~1k
tokens). Cuando el atleta lo pide expresamente hay además un respaldo determinista
(`pidio_que_se_anotara`) que lo anota pase lo que pase — y el prompt del coach
tiene que decir exactamente eso y nada más: **si lo pidió, confirma; si no lo
pidió, no prometas**. Lo que anota se ve en el chat con «Quitar», que lo retira (Beta 3.0, F9).

**«Revisa mi cuaderno» pregunta, ordena y avisa; el atleta acepta cada cambio** (Beta 3.0, F9 y decisión del
responsable del 2026-10-11, `services/ai/reordenar_cuaderno.py::revisar`, Database/032). El modelo del cuaderno
lee TODAS las anotaciones con un resumen del perfil (`resumen_del_perfil`: tabla de días, competiciones,
lesiones, medicación, fuerza) y la fecha de hoy; si algo es ambiguo pregunta (4 como mucho; ante una molestia,
si sigue) y con las respuestas junta, reescribe, recategoriza, manda al perfil o retira, y escribe **avisos del
perfil**: lo que el atleta tiene que cambiar en SU perfil («marca Correr el martes»), que no se guardan ni
escriben nada. El código descarta lo que no cuadra (una anotación dos veces, números que no estaban en las
notas ni en sus respuestas, campos fuera de `CAMPOS_DEL_PERFIL`, categorías que no existen, un aviso que repite
un «al perfil»). `CAMPOS_DEL_PERFIL` dice el sitio REAL de la pantalla (una prueba lo comprueba contra
`ProfileView.vue`; hasta el 2026-10-11 mandaba a un «Contexto para tu plan» que no existe). Nada se borra: la
original se retira con su motivo y `replaced_by`, y una preferencia sigue siendo preferencia (lo decide el
servidor). Prueba real del 2026-10-11 (~0,0013 USD por vuelta): preguntó si seguía la molestia del aquiles y,
con «no», la retiró; avisó del martes sin correr; juntó la natación repetida. Las formas de pedirlo que el prompt nombra tienen que estar
todas en la expresión regular; hay una prueba que lo fija.

## Lo que pide de una sesión es una petición (Fernando, 2026-09-22)

Nadador de aguas abiertas. Escribió en su cuaderno («Preferencias de entrenamiento») ~4 nataciones por semana
de 2-3 km con una larga de 3 km, y sus dos semanas siguientes le dieron tres de 1.200-1.800 m. La semana 2
sumó 7 h 55' con 130' de fuerza contra una fase de ~7,5 h y una media de 8,1 h que mezclaba el gimnasio con la
natación. Su frase iba a mitad del prompt, bajo "Son afirmaciones del atleta, no mediciones: contrástalas con
Garmin"; lo único que el prompt llamaba petición era la rutina de gimnasio, y la semana respetó sus días de
gimnasio y no sus metros.

- **Las «Preferencias de entrenamiento» del cuaderno llegan a la decisión semanal como petición**, dentro de
  "What the athlete decided, and is respected as given" (`notebook.render_peticiones_de_entrenamiento`,
  `weekly_planner_node.bloques_del_cuaderno`): cuántas sesiones, un mínimo por sesión, la larga de un día. El
  total de la semana y la intensidad siguen siendo nuestros; el sitio sale de que la semana crezca, nunca de las
  sesiones clave ni de la progresión de los otros deportes (el responsable, 2026-09-23), y si una no
  puede ir (afinamiento, tras una carrera, un día que no puede, una molestia declarada), el racional dice
  cuál y por qué. El resto del cuaderno sigue igual, y el chat, el macrociclo y las preguntas del coach lo
  reciben entero. Un espejo sin la clave `peticiones` recibe el cuaderno como antes.
- **El macrociclo también la lee como petición** (auditoría del 2026-09-23). Recibía el cuaderno entero bajo
  "contrástalas con Garmin" y escribió, sobre la natación pedida del atleta 2, "construir hacia esos 2,5-3,5 km,
  no exigirlo desde ya [...] se ajustará bajando otra cosa": el fallo de Fernando un nivel más arriba, en un
  texto que la semana recibe. Ahora recibe las peticiones aparte (`render_peticiones_de_entrenamiento(...,
  para="macrociclo")`), su prompt dice que una petición no es una `constraint`, y la revisión de restricciones
  (`render_constraints_for_review`) marca resuelta la que la frena "sólo porque hacía menos".
- **Pero no es una prohibición** (el responsable, 2026-09-23): una evidencia REAL sí la limita -- un dolor
  declarado, la recuperación persistentemente por debajo de su normal, un afinamiento, la vuelta de una
  carrera -- y entonces se dice cuál. Lo que es un error es inventarlo o exagerarlo.
- **Una natación suma lo que dice** (`plan_validator.totales_que_no_suman`): la decisión escribió "Total 2500 m"
  en series que sumaban 2.300. Antes de codificar se le devuelve, en la misma vuelta que la disponibilidad,
  y sólo cuando las series se quedan POR DEBAJO: leer texto libre cuenta de más ("(50 m pool)", subtotales).
- **Alta se anota; extrema se limita y se le avisa** (`services/ai/carga_segura.py`, 2026-09-23). Tres
  señales contra el historial del PROPIO atleta, cada una con su evidencia: carga contra su crónica (Garmin:
  alta 1,5, muy alta 2,0), una carrera contra la más larga de sus 30 días (Frandsen 2025, BJSM: >10 % sube la
  lesión, >100 % HRR 2,28) y horas de resistencia contra las suyas (Nielsen 2014); y siete días SEGUIDOS sin
  descanso (Foster 1998), que nunca es extrema (contaba días con entreno: un bloque de nueve días con dos
  sábados de descanso salía "sin descanso", 2026-09-25). No es el techo que se retiró: "alta" sólo va al
  `worker.log`; "extrema" vuelve a la decisión, que decide cómo bajarla (en progresión, sin pagarla con los
  otros deportes) y dice con qué evidencia, y si la semana final sigue siendo extrema el atleta lo ve en
  `WeekBlock.load_warning` (y el chat), con esas razones y dicho claro. **El atleta sólo ve un aviso cuando la
  semana es extrema** (el responsable, 2026-09-25: el de semana "alta" era raro y confuso). Réplica sobre el prompt real del
  atleta 2 con "3 horas todos los días, sin descanso": la propia decisión lo limitó a 6,1 h y lo dijo.
- **La fuerza no resta horas a la resistencia.** La referencia de horas se da partida: natación, bici y
  carrera por un lado y fuerza aparte (`server._horas_semanales_por_tipo`, `chronic_hours_by_kind`), y las
  horas de la fase NO se releen como sólo resistencia: en un macrociclo escrito antes incluían la fuerza.
  Caminar y yoga no cuentan en ninguna.
- **La media no es un techo, pero el margen lleva su número.** En una semana normal el bloque de carga dice
  "esas ~X h dicen dónde está: al empezar un plan la constancia suele sumar 1-2 h sobre ellas; es un margen,
  no un objetivo, y más allá sólo si lo que pidió lo exige" (decisión del responsable). La primera versión no
  decía sobre qué número y releía la fase como resistencia: réplicas sobre la decisión real del atleta 2
  (2026-09-23), 4,8 h de resistencia con el prompt de antes y 7,1 h con aquélla SIN ninguna petición; con el
  texto actual, 6,0 h sin petición y ~7,6 h con su natación de 2,5 y 3,5 km, sin bajar la carrera ni la bici
  (contrafactual: 2,0 h de carrera con y sin la petición). En afinamiento, descarga o tras una carrera esa
  línea no sale.

## Disponibilidad: lo que el atleta PIDE, no lo que puede

Desde la v1.1 el perfil tiene una **rejilla deporte x dia con tres estados**
(`athlete_profiles.sport_days`, migracion 013): `fijo` (lo quiere si o si),
`posible` (puede, decide el entrenador) y ausente (descanso).

Existe porque hasta entonces el modelo de datos **solo sabia expresar permiso**.
`day_time_blocks` guardaba franjas de reloj y el prompt lo decia con todas las
letras -- *"UN BLOQUE ES UN PERMISO, NO UNA OBLIGACION"* -- asi que un atleta que
queria nadar los martes no tenia donde decirlo, y el modelo veia un hueco y
decidia, correctamente, no usarlo.

La frontera, que no se mueve: **el deporte y el dia son del atleta; el volumen y
la intensidad son nuestros.** Un `fijo` no autoriza a meter una sesion de calidad
porque el atleta pidio correr — pidio el deporte, no la sesion.

- `render_sport_days_table` / `_sport_days_note` (`web_app/server.py`) emiten la
  tabla de peticiones. **Devuelven `""` si no hay rejilla**, y entonces se usa la
  tabla de franjas de siempre: un perfil viejo no cambia de comportamiento hasta
  que su dueno conteste, y `day_time_blocks` no se borra nunca.
- `sport_requested_missing` (`plan_validator.py`) marca un `fijo` que no aparece, **y sólo si su día está en el
  bloque**: la primera semana de José y de Jessie (miércoles a domingo) exigía la fuerza fija del lunes y del
  martes y pagaba dos reparaciones por IA imposibles (~0,26 USD, 2026-09-23).
  **Solo los `fijo`**: exigir los `posible` reintroduce el fallo de v7.2, donde
  un atleta con tres huecos el lunes recibio tres micro-sesiones.
- **El día de una carrera está disponible para esa carrera** (Hugo, 2026-10-09; decisión del responsable):
  su tabla es su rutina de entrenamiento, no su calendario de carreras. Hugo no entrena los domingos y su
  trail era el domingo: bajo NEVER VIOLATE el prompt decía "un día sin nada es descanso" y más abajo "el día
  de una carrera es `race_day`"; la comprobación de la decisión le devolvió la carrera ("muévela a otro
  día"), la semana puso una simulación de trail con cuestas el sábado, la víspera, y la red del día de
  carrera escribió la competición para que la de disponibilidad la quitara («Aquí iba «Dia de
  competencia», pero ese día no está disponible»). Ahora `plan_validator.restriccion_del_dia` (la única
  lectura de "qué se puede esa fecha") abre la fecha de una carrera con los deportes de la carrera
  (`dias_de_carrera`, `deportes_de_la_competicion`: de su distancia o de su grupo) y sin tope de horas; las
  reglas 1-3, la nota de la tabla y el descanso preferido lo dicen (`describir_carreras_de_la_semana`); el
  check-in enseña ese día con una bandera. **Si ESTA semana marca ese día como que no puede, gana el no**:
  la carrera no se exige, y la corrección dice que no se mueve ni se simula. En la misma pasada: la carga
  segura no cuenta la carrera (la eligió el atleta; contada, un trail de 3 h salía "extrema" y la decisión
  recibía "bájala"), y la regla de la víspera cuenta las series en cuesta como calidad. **La carrera va al
  reloj hasta pulsar vuelta** (`carrera_hasta_pulsar_vuelta`, decisión del responsable): su duración no se
  sabe, y un tiempo fijo mandaba al enfriamiento en plena carrera. **Y sin objetivo de ritmo** (2026-10-19):
  el plan de ritmos va en el texto; un 5:24-5:32 para todo el 10K pitaba en el final a tope. `tests/test_el_dia_de_la_carrera.py`.
- **El descanso es lo que la tabla deja vacío** (decisión del responsable, 2026-10-21): «Día de descanso
  preferido» ya no se pregunta; con tabla se guarda vacío (`assembleProfilePayload`), así que la línea "Prefers full
  rest on…" del prompt sólo sale en perfiles viejos sin tabla. Dejaba elegir un sábado de descanso con un `fijo` el
  sábado. Y la **distancia principal es la de la carrera A** que viene antes (`distanciaDeLaCarreraA`; de ella sale
  también `triathlon_distance`); sin carrera A con distancia, manda el selector.
- No es reparable en tier 1. Poner un dia a descanso es determinista; anadir una
  sesion exige decidir duracion e intensidad, y eso es criterio.
- **En la ventana de afinamiento de una carrera los `fijo` pasan a `posible`**
  (`disponibilidad_para_validar` y `_NOTA_DE_AFINAMIENTO` en
  `weekly_planner_node.py`): el atleta los marco por rutina y un afinamiento no
  es rutina. La ventana es la de su prioridad (A 20 dias, B 10, C 6), no "tres
  semanas antes de cualquier carrera". Medido en el 5K del atleta 2: 3,5 de las
  7,4 h eran fijas y la carga estimada fue 362 contra una cronica de 291, en una
  semana cuyo racional decia "bajamos el volumen". La nota dice tambien lo que
  sostiene la evidencia (Bosquet 2007; Houmard 1994 en corredores de 5K, 7 dias
  con 85 % menos volumen y series: -3 % de tiempo): bajar mucho el volumen
  manteniendo la intensidad y la frecuencia DEL DEPORTE DE LA CARRERA; lo
  accesorio puede bajar mas o quitarse. La primera version decia "acortar suele
  ser mejor que quitar" y la semana de referencia recorto todo un 20 % sin
  quitar nada.
- **Y el prompt tiene que dejar de exigirlos, no solo el validador.** La nota lo
  decia, pero la regla 4 de CRITICAL SCHEDULING RULES seguia diciendo "Every
  FIJO appears on its day": lo inviolable gano, y las semanas del 5K de los
  atletas 2 y 3 (2026-09-14) conservaron sus tres fuerzas fijas.
  `build_custom_planning_context(perfil, afinamiento)` escribe esa regla y la
  nota de FIJO ya como valen en la ventana; fuera no cambia un caracter. Hasta
  el 2026-09-14 el nodo semanal las reescribia con una expresion regular
  (`contexto_de_la_semana`): un parche de que el contexto se escribiera sin
  saber que semana era, y ya no existe. La semana recibe tambien el ritmo medio
  que pide el objetivo de la carrera (`_ritmo_de_la_carrera`): la atleta 3
  "ensayo el pacing" de un 5K de 5:12/km a 4:54-5:16, la banda de repeticiones.

## Lesiones: declaradas, no supuestas

Los prompts NO empujan a la prevencion. El planificador de temporada no menciona
lesion ni una vez, el experto de fisiologia no recibe la ficha de salud, y
`render_health_block` devuelve `""` si el atleta no declaro nada: **un atleta
sano recibe cero tokens sobre lesiones en todo el pipeline.** Lo que los atletas
de la beta sentian era lo que ellos mismos habian declarado, volviendoles
amplificado, porque se les preguntaba en seis sitios.

Por eso la v1.1 arregla el INTAKE y no el prompt:

- La pestaña Salud sale del alta a *Avanzado*. Era la octava de ocho, asi que la
  ultima impresion del registro era un cuestionario clinico.
- **El chat del asistente de salud se retiro de la UI.** El agente sigue vivo y
  los informes ya generados se siguen leyendo; lo que ya no hay es un boton que
  lo llame. Los campos estructurados llegan al prompt igual sin el.
- Una lesion curada **cambia de estado, no se borra** (`athlete_injuries.status`,
  migracion 014). Y una resuelta **deja de ser una restriccion**: no entra en
  `collect()` ni genera contingencias. Sin `status` se asume activa, que es lo
  que significaba la fila antes de que la columna existiera.

**La fuerza de la respuesta es la del dolor declarado.** Con psoas 1/10 y rodilla
1/10, la semana de referencia del 2026-09-13 nombró las dos estructuras en las
diez sesiones, el macrociclo escribió una `limitacion` médica de un mes y el
informe lo llamó "el principal riesgo". Ahora un dolor ≤2/10 sólo aparece donde
se carga con impacto o intensidad (`render_niggle_constraints`), nunca es una
`limitacion` en el macrociclo, y el informe lo menciona una vez. Sin número de
dolor no se da por leve.

## La decisión se comprueba contra la disponibilidad ANTES de codificarla

La semana de José del 2026-09-15 puso sus 6x400 m el martes, y su tabla del
martes no tiene correr. La disponibilidad sólo se miraba DESPUÉS de codificar,
donde la única salida determinista es el descanso: la semana perdió su única
sesión de calidad y la pantalla enseñó un descanso titulado "5K pace".

- `plan_validator.intenciones_fuera_de_la_tabla` comprueba la decisión en cuanto
  vuelve (días que no puede, deportes que su tabla o su check-in no dan) y el nodo
  semanal se la devuelve UNA vez al mismo modelo con `mensaje_de_correccion`: mover
  una sesión es criterio. Réplica sobre su decisión real (0,04 USD): movió las
  series al miércoles y el racional lo dijo.
- `restriccion_del_dia` es la única lectura de "qué se puede esa fecha" (tabla +
  días cerrados + días abiertos), y el **nombre del día sale de las fechas reales**,
  nunca del texto del modelo.
- Si aun así la red de después convierte una sesión en descanso, **lo dice**: el
  día queda como "Descanso" con "Aquí iba «…», pero …", y el racional lleva una nota.
- Las preguntas del coach reciben los días de la semana a planificar con su
  nombre (preguntó por "el martes 16/09", que era miércoles), y el ejemplo de la
  nota de la tabla ("quiere nadar el martes") dice que es hipotético: el coach lo
  leyó como algo que dijo el atleta.

**Un cambio en `AiService/main.py` o `bridge/` exige reiniciar el AiService**
(uvicorn va sin recarga): el `skip_checkin` estuvo sin llegar en local porque el
proceso era de antes del cambio. El cerebro sí se relee en cada worker.

## El reloj dice lo que dice el paso

`plan_validator._ritmo_como_dice_el_paso`, antes de validar: si un paso de carrera
escribe UN ritmo o UN rango ("~5:12/km") y su objetivo no lo contiene, el objetivo
se reencaja al texto. En la semana de referencia, la última activación antes del
5K decía 5:12 y el reloj habría pedido 4:51-5:00. Con dos ritmos en el texto no
hay una sola lectura y no se toca.

**Y la duración: la causa estaba en el esquema, no en el modelo.** En las semanas
del 2026-09-14, con 0 violaciones, el codificador mandó "200m trote" como 200
segundos y "25 m suave" nadando como 25 s. El prompt archivado lo dice: escribió
`recovery_description: "200m trote"` — sabía que eran metros — y los metió en
`recovery_seconds`, el único campo de recuperación que existía. Desde el
2026-09-15 la recuperación de una serie lleva su unidad
(`recovery_duration_type` + `recovery_duration_value`, igual que el trabajo), el
prompt tiene un ejemplo por distancia, la decisión escribe UN valor por duración
con su recuperación, el catálogo de ejercicios sólo viaja con fuerza, los
docstrings de historia salen del esquema (`_sin_historia_en_el_esquema`) y el
codificador corre a temperatura 0. Réplica sobre las dos semanas archivadas: 0
unidades equivocadas.

Lo que NO se arregló por prompt: una decisión ambigua ("3 progresivos de 15-20″",
sin recuperación) Haiku la sigue codificando mal (60 s), y reescribir su prompt
empeoró otro caso — por eso el arreglo va en el contrato de la decisión. Queda
la red `_duracion_como_dice_el_paso` (UN tiempo escrito fuera de ±20 % → el del
texto; el número EXACTO de una distancia sin tiempo → distancia), que avisa en el
log. Si en dos semanas de beta no corrige nada, se borra.

## Lo que el atleta PIDE y el sistema no dejaba decir (Fernando, 2026-09-14)

- **Días que no puede ESTA semana** (`unavailable_dates`, migración 022): casillas
  en el check-in, regla en el contexto de la semana y `day_not_available` por
  FECHA en el validador, que además deja de exigir el FIJO de ese día. La tabla del
  perfil es permanente y el texto libre el validador no lo lee.
- **Y el día que su tabla NO le da y esta semana SÍ** (`open_dates`, migración
  024, Jessie 2026-09-15): no entrena los sábados, pidió en la nota su activación
  del sábado antes de su 5K y la semana le dio "descanso completo como prefieres".
  En el check-in los días vienen como su rejilla (verde entrena, gris descanso);
  un toque apaga un día que entrena y abre uno de descanso preguntando con qué
  deportes. Un día abierto es POSIBLE con esos deportes y ninguno más: la
  excepción va DENTRO de las reglas 1-3 de CRITICAL SCHEDULING RULES
  (`build_custom_planning_context(perfil, afinamiento, abiertos)`), en la nota de
  la tabla y sobre `preferred_rest_day`; el validador la acepta por fecha y, si un
  día está abierto y marcado como que no puede, gana el no.
- **Cada generación pregunta de nuevo.** El modal empieza en BLANCO (antes se
  rellenaba con el check-in de hoy y Jessie volvió a encontrar su nota y sus días
  de la semana anterior), enseña las fechas de la semana que se va a generar
  (`Frontend/src/utils/semana.js`, el mismo cálculo que `_next_week_start` +
  `dias_del_bloque`; antes eran 9 días desde hoy y un día fuera de esa semana no
  hacía nada) y **"Saltar" significa sin check-in**: viaja como `skip_checkin`
  hasta `run_week_generation_task(sin_checkin=True)`. Antes la semana usaba igual
  el de los dos últimos días. Rehacer no pregunta y sigue usando el de hoy.
- **Mover una sesión dentro de su semana** cuenta como hecha
  (`adherence.py`, tercera pasada): misma semana, duración ±25 %, y nunca una
  actividad que ya era de su propio día. Mover desde la app es la Fase C.
- **La fuerza la escribe quien ve al atleta**: la decisión pone ejercicios,
  series y carga por RIR/RPE (kilos sólo si los dio), y el codificador los copia.
  Cómo entrena en el gimnasio es una petición; lo que pide el atleta gana al
  anclaje de la semana anterior, que ahora lleva los ejercicios.
- **Una superserie se puede escribir** (Beta 2.1, S1): `StrengthBlock.groups`
  (`StrengthGroup`: ejercicios seguidos, descanso entre rondas; las series sólo en
  cada ejercicio). Los grupos los escribe el CÓDIGO: `SkipJsonSchema` los deja
  fuera del esquema del codificador y de la reparación, y con grupos `exercises`
  es su aplanado, así que ningún lector viejo se rompe. Un plan sin grupos se lee
  como un grupo por ejercicio (`grupos_de_fuerza`). **Al reloj (S2)** cada grupo
  es UNA repetición con sus ejercicios dentro; series desiguales se parten en
  rondas y la que va una sola vez sube suelta; más de 50 pasos → el bloque de
  tiempo. Verificado en la cuenta de José: 1701096031 (nombres de gimnasio, 20 de
  20) y 1701100942 (la Upper A de Fernando leída de vuelta idéntica). **El perfil (S3, Database/025)**
  guarda cómo la entrena: `strength_profile` (modo `rutina|coach`, objetivo, equipo, estructura, ejercicios
  seguidos, minutos, barras que ya hace con técnica; NULL = como hoy) y `strength_routine` (el texto pegado y,
  al confirmarlo, sus sesiones). Se leen sólo con `services/ai/perfil_de_fuerza.py`, que descarta lo que no
  reconoce; espejo en `utils/fuerza.js`. Una rutina sin confirmar no cuenta, y cambiar el texto la
  desconfirma. Hasta S5 nadie la lee.
- **La rutina pegada se ORDENA, no se interpreta** (S4, `services/ai/rutina_de_fuerza.py`, ruta
  `POST /profile/strength-routine`): el modelo barato lee el texto con las líneas numeradas y copia lo
  escrito; el código comprueba que cada línea se usó, que el nombre y las series están en su línea, y
  convierte en PREGUNTA lo que no entiende (la «R» de "4×15R", «Geelos», un grupo sin descanso). Cómo se
  agrupan los ejercicios de una línea y el descanso entre rondas los dice el ATLETA en el formulario, porque
  su texto no los dice. Una duda que nombra un ejercicio le quita su nombre de Garmin: al reloj no llega con
  el nombre de otro. Nada se guarda hasta que el atleta confirma la revisión. Medido en la rutina real de
  Fernando: 0,004 USD y ~10 s por lectura.
- **Con una rutina confirmada, la semana NO reinventa la fuerza** (S5,
  `langgraph/nodes/paso_de_fuerza.py`): el CÓDIGO elige qué sesión va cada día **mirando la semana de
  resistencia** (`colocar_las_sesiones`) y construye el bloque con los
  ejercicios que el atleta confirmó; el MODELO sólo propone cambios con su motivo y una dosis que baja y
  nunca sube. Un cambio sin motivo, un ejercicio que no es de esa sesión o una sesión que no existe se
  descartan; las series que pide el paso no se vuelven a recortar por la dosis. La fuerza que no se hace se
  dice en el día.
- **Dónde cae cada sesión de fuerza es una decisión, no un turno.** Cada sesión lleva su ÉNFASIS
  (`pierna|torso|completo|core`): la que carga piernas se aleja de la calidad y de la tirada larga (la
  economía de carrera cae unas horas tras fuerza de tren inferior), no se repite el mismo énfasis en 48 h, y
  entre las que caben se usa la menos hecha. **Si no cabe lejos, se coloca igual en el mejor sitio y se le
  dice al atleta** en su día y en el racional, con la recomendación de añadir un día de gimnasio y el porqué:
  quitarle su sesión porque no tenemos dónde ponerla sería decidir por él (2026-09-17).
- **Y la IA conserva su rango dentro del programa**: puede AÑADIR uno o dos ejercicios con su motivo, de la
  lista cerrada y sólo para esa semana, **únicamente si hay algo declarado que lo pida** (una molestia, o lo
  que busque la fase). Un atleta sano no recibe prehab que no pidió, y el programa confirmado no se toca. Esos días salen del prompt del codificador (7.128 caracteres menos, medidos) y los bloques
  se reponen después de la reparación del validador, que reescribe la semana entera sin conocer el programa.
  Sin rutina confirmada no cambia nada: el prompt de la decisión es idéntico byte a byte.
- **Quien no tiene rutina puede pedir que se la escriba el coach** (S6,
  `services/ai/programa_del_coach.py`, `POST /profile/strength-program`): UNA vez por bloque, con lo que el
  atleta declaró y sus días de Fuerza, revisado y confirmado en la MISMA pantalla que una rutina pegada. A
  partir de ahí su semana va por el camino de S5, que es lo que hace que sus ejercicios dejen de bailar entre
  regeneraciones. Lo que no cuadra con lo que declaró (más ejercicios seguidos de los que pidió, una barra
  que no marcó) se pregunta antes de confirmar, y lo que el coach explica del programa se guarda con él.
  En afinamiento o vuelta de carrera con toda la fuerza a dosis normal, un LINT en el `worker.log`.
- **El macrociclo sabe para qué la hace** (S7): recibe lo que el atleta declaró como PETICIÓN suya y puede
  escribir `SeasonPhase.strength_quality` -- qué busca la fuerza en esa fase, con las palabras del atleta.
  Es opcional, así que un macrociclo guardado antes sigue validando; llega a la semana por
  `compute_mesocycle_position`, y quien no contestó no recibe ni una palabra de gimnasio.
- **Lo que el atleta LEVANTÓ se registra** (Database/026, `athlete_strength_logs`, rutas
  `GET/POST/DELETE /strength-log`): una fila por sesión con sus ejercicios, series, repeticiones, kilos y
  RPE, y el `exercise_id` del programa para seguir el mismo ejercicio semana a semana. **Es el único sitio
  de la app donde un peso es un dato y no una suposición**: lo escribe él. Se ve en el plan ("La última vez:
  62,5 kg · 4×8"), viaja con la semana (`strength_logs`) y el paso de fuerza propone la carga partiendo de
  ese número, con su motivo. Sin registro no cambia nada. La migración se tolera ausente
  (`StrengthLogRepository::disponible()`).
- **Auditoría de la fuerza (2026-09-23), con la rutina real del atleta 2:**
  - **El énfasis se deduce** (`paso_de_fuerza.enfasis_de`): del nombre de la sesión (Upper/Lower/Full) y, si
    no lo dice, de sus ejercicios (`exercise_catalog.region_de`). Lo que no se sabe ya NO se trata como pierna:
    su rutina, confirmada sin `emphasis`, daba "«Upper B» carga piernas" antes de una natación.
  - **Sólo correr y pedalear son sesiones clave para la pierna**; la calidad en el agua se protege del torso
    pesado del día anterior, como preferencia de colocación y sin aviso.
  - **Garmin, del catálogo primero** (`exercise_catalog.garmin_de`, sólo parejas verificadas): "Extensión de
    cuádriceps" iba como HAMSTRING_CURL (un estiramiento de MOVE) y "Press de banca" sin nombre. Se aplica al
    construir cada semana, así que corrige también rutinas ya guardadas sin tocar la base.
  - **Nada de párrafos**: la dosis, el motivo de cada cambio, lo quitado y el aviso de colocación van en campos
    (`StrengthBlock.session_name/dose/dose_reason/skipped/placement_note`, `StrengthExercise.change/
    change_reason`) y la pantalla enseña etiquetas con el porqué a un toque. Con la dosis bajada, las series
    de menos no se etiquetan una a una. El chat y la hoja imprimible reciben esos campos.
  - **El texto del día lo escribe quien sabe qué sesión cae** (el paso de fuerza, una frase de porqué y una de
    plan B): la decisión lo escribía antes y a una Upper le decía "baja series de pierna". No se atribuye la
    fuerza a quien la pidió.
  - **Los minutos no se recortan dos veces** (`minutos_de`: lo menor entre lo que dio la semana y su sesión a
    esa dosis; salía una Upper de 21'), y la semana previa dice cuándo una fuerza iba recortada, para que la
    siguiente no copie el recorte.
  - **Registro**: lo prescrito y los kilos de la última vez son PISTA, no valor (se guardaba como hecho lo que
    nadie tocó); botón "Lo hice tal cual"; borrar con el panel abierto ya no rompe; fechas imposibles, fechas
    lejanas, ids de más de 64 y más de 40 ejercicios se rechazan o recortan en `StrengthLogController`.
- **Leer la fuerza del RELOJ se retiró** (Beta 3.0, F0, decisión del responsable del 2026-10-10: poco intuitivo
  y sin uso). Existió del 2026-09-23 al 2026-10-11 (`fuerza_desde_garmin.py`, botón «Traer de mi reloj» y lectura
  automática al generar la semana). Lo medido entonces sigue valiendo si se retoma: en una sesión libre Garmin
  no sabe qué ejercicio fue (actividad 24371594337: `UNKNOWN`); en una hecha con el entreno de la app cada serie
  trae `wktStepIndex` (actividad 24309326801), y la unidad del peso nunca se verificó con kilos reales. Hoy los
  kilos sólo los escribe el atleta en el registro.
- **El ejercicio lleva el id de su programa** (`StrengthExercise.id`, fuera del esquema del modelo, 2026-09-23):
  hasta entonces el registro guardaba `exercise_id` vacío y seguía cada ejercicio por su nombre. Los registros
  viejos se siguen encontrando por nombre (`ultimaDe`, `historialPorEjercicio` en `Frontend/src/services/fuerza.js`).
  El plan de la Beta 2.1 está en `planes/PLAN_DE_ACCION_BETA_2_0.md`, sección Beta 2.1.
- **Zonas y ritmos suyos**: zonas idénticas en todos los deportes se avisan (son
  las de fábrica); el ritmo de rodaje suave declarado
  (`easy_run_pace_*_sec_per_km`, migración 022) gana a la banda derivada; el
  umbral tecleado gana a Garmin, pero no el que rellenó la importación.
- **"3:30" de maratón son horas**: `parse_time_to_seconds` lee dos partes con la
  distancia; como MM:SS pedía un ritmo imposible y el informe decía "fuera de rango".
  Y en la entrada ya no hay nada que interpretar: los tiempos objetivo y las marcas
  se escriben en TRES campos (`components/forms/TiempoHMS.vue`, `utils/tiempo.js`)
  y se guardan como "HH:MM:SS". Un valor viejo que no es un tiempo ("245W") se
  enseña y se conserva, no se borra en silencio.

## El umbral medido: test de umbral y zonas de fábrica (Hugo, 2026-09-14)

Los informes del atleta Hugo (`main`) tenían las tres formas de un umbral que
nadie midió: el de Garmin descartado (6:23/km, más lento que su maratón), uno
puesto desde su predicción de 10K (4:27, más rápido que su umbral) con la
tarjeta diciendo "de tu Garmin", y zonas de pulso de fábrica con las que el
informe leyó un 21K a 162 ppm como "tempo" y recetó "por debajo de 140-145".

- **Test de umbral** (`services/ai/test_de_umbral.py`, Perfil → Avanzado,
  Database/023 `threshold_tests`). Protocolos estándar, no propios: 30' a tope
  en carrera o bici (Friel: FC media de los últimos 20', ritmo o potencia media
  de los 30') y CSS en natación (los dos tiempos los escribe el atleta y la
  cuenta la hace la pantalla). **El test va al RELOJ** (`sesion_del_test`:
  15' de calentamiento + 10' + 20' + 10', cada paso cierra su vuelta; los dos
  pasos del test dicen SIN PARAR, porque dos pasos seguidos se leen como dos
  series con descanso) y la app
  lee **sólo la actividad hecha con ese entreno**, por el `workoutId` que Garmin
  guarda en la lista de actividades (`server.subir_test_de_umbral` /
  `analizar_test_de_umbral`, `/bridge/threshold-test` con `action`). La primera
  versión elegía entre las actividades recientes la que pareciera un test y en
  la cuenta real aceptó series y rodajes: adivinar no vale. `analizar_test`
  sigue absteniéndose si la forma no es de test. El test se repite cada pocos
  meses: **manda el más reciente** (`test_vigente` reordena al leer) y los
  anteriores quedan como historial. En el registro de subidas va con
  `kind="test"`: ni subir, ni regenerar, ni borrar la semana lo retira. Los
  protocolos del frontend salen del script `generar_catalogo_frontend.py`.
- **El cerebro reconoce el test** (`prescription.py`): un valor del perfil que
  coincide con su test sale como "de su test de umbral del …", y un test no se
  descarta porque no cuadre con una predicción (se duda de la predicción). La
  FC umbral en bici sólo sale de un test en bici y la usa el reparto de
  intensidad. Lo rellenado por una importación de Garmin no gana al Garmin de
  hoy, en FC igual que en ritmo.
- **Zonas de fábrica** (`fc_maxima_de_zonas_de_fabrica`): suelos de Z2-Z5 al
  60/70/80/90 % de una FC máxima, deporte a deporte. "Idénticas entre deportes"
  no bastaba (las de Hugo no lo eran). La ficha, el experto de actividad y el de
  métricas (`procedencia_de_umbrales`) dicen que su Z2/Z4 no son umbrales y que
  no se escriben reglas en ppm con ellas.
- **Un umbral es el esfuerzo de ~1 hora, también el de Garmin** (`prescription._umbral_que_se_sostiene_una_hora`,
  2026-09-23). El filtro sólo exigía que quedara entre su 5K y su maratón, y el del atleta 2 (5:53/km) era su
  ritmo de media maratón: su curva de predicciones lo sostiene ~2 h. De ahí un tempo casi a ritmo de maratón y
  series de VO2max más lentas que su 5K real. Ahora se calcula cuántos minutos sostiene su curva ese ritmo
  (`minutos_que_sostiene`) y la ventana de un umbral es 25-90' (ancha a propósito: TTE del MLSS ~30-70', FTP
  40-70', T de Daniels ~60'). El de Garmin fuera de ella se lleva al BORDE (lo mínimo, nunca más rápido de lo
  necesario, porque las predicciones pueden ser optimistas: las suyas daban un 5K de 25:24 y lo corrió a ~26'),
  con procedencia `garmin_ajustado` que la ficha, el informe, las metas y los expertos dicen como "ajustado, no
  medido". El escrito por el atleta manda y sólo se avisa; el de un test no se toca. El bucle por fase no
  compara umbrales de procedencias distintas: cambiar de método no es mejorar.
- **Umbral estimado = ritmo de ~1 hora** entre las predicciones (log-log, la
  forma de Riegel), no el ritmo de 10K: 4:38 para Hugo en vez de 4:27.
- **Montaña**: con ≥20 m de D+ por km (o un formato de montaña), una meta no se
  compara con la predicción en llano ni se da su ritmo medio (`assess_goal`,
  `missing_reason="desnivel"`): su UTG 21K de +1.500 m salía "hay margen para
  apuntar más alto" contra su media en asfalto.

## La semana de DESPUÉS de una carrera

El código sabía qué es la semana ANTES de una carrera (`carrera_en_afinamiento`,
que exige que la carrera caiga después del inicio de la semana) y nada de la de
después. La semana del atleta 2 que empezaba al día siguiente de su 5K a tope
recibió "la fase describe una semana normal de ~270" y sumó ~8,4 h contra sus 7,1
normales, con la base de volumen tomada de "lo mayor entre crónica y lo
planificado" y la semana anterior como anclaje.

`carrera_reciente` detecta la carrera que acaba de terminar y el bloque de carga
lo dice (`_nota_de_recuperacion`, tipo `recuperacion`): no es semana de
construcción, lo recortado en el afinamiento no se recupera, sin calidad en los
días fáciles y el volumen no pasa de una semana normal. Los días fáciles salen de
la regla práctica de Jack Daniels (un día por cada 3 km) SÓLO en carreras a pie;
en multideporte se dice la situación y decide el entrenador. Ventana: esos días,
como poco una semana y como mucho tres.

## Cables que se guardaban y nadie leía

"¿La semana que cierras salió como esperabas?" (`week_as_expected`, migración 016)
se guardaba en MySQL y `CheckinEntity::toArray()` no lo devolvía: no llegaba ni
al prompt ni al panel. `tests/test_cables_del_checkin.py` fija ahora que todo lo
que el DTO guarda sale de la entidad y todo lo que el modal manda lo lee el DTO.

## Ninguna generación espera para siempre

El 2026-09-14 la semana de la atleta 3 se quedó colgada 19 minutos: la llamada del
experto de fisiología a OpenAI no volvió nunca, sin excepción no hubo reintento, y
el hilo de progreso del worker seguía tocando `status.json`, así que parecía viva y
ocupaba un hueco de la cola. Tres capas, de dentro afuera:

- **Cada cliente LLM lleva `timeout`** (`LLM_TIMEOUT_S`, 480 s por defecto) y un
  solo reintento interno (`model_config.py`). La decisión semanal tarda 2-3 min.
- **El worker se corta solo** a los `WORKER_TOPE_S` (40 min): marca el error en
  lenguaje del atleta, libera la cola y sale.
- **El frontend espera 45 min** (`PlanView.vue`), alineado con `_MAX_JOB_TOTAL_S`
  del AiService, **contados desde que el trabajo arranca** (no desde que se
  encola), y aguanta un minuto de sondeos fallidos antes de rendirse: un
  reinicio del AiService ya no le borra las preguntas del coach.
- **Un worker esperando respuestas sigue ocupando hueco en la cola**: su proceso
  vive con su memoria, y en 2 GB lo que se agota es eso. Un trabajo en cola sigue
  siendo "en curso" para su atleta hasta 3 h (`_MAX_JOB_EN_COLA_S`), para que no
  pueda encolar una segunda generación pagada.

## La semana que se genera sola (Beta 2.1, 2026-09-24)

«Generar mi semana automáticamente» (decisión del responsable): un interruptor en Plan
(Database/027, `athlete_auto_weeks`). La lógica está en `Backend/src/Services/AutoWeekService.php`
y la dispara `Backend/scripts/semanas_automaticas.php` cada hora (el cron se configura a mano:
`operacion/DEPLOYMENT.md` §4).

- **Cuándo**: la VÍSPERA del primer día de su siguiente semana, desde las 21:00 de SU zona (la manda
  su navegador; el servidor no sabe qué hora es para él). Si el servidor estuvo caído, vale todo su
  primer día; después, se retoma a mano. Sólo continúa un plan: la primera semana es manual.
- **Sin preguntas ni check-in**: el worker no registra proveedor de preguntas (el paso no pregunta
  ni gasta) y la semana va con `skip_checkin`. **No supone lo que no dijo**:
  `server.NOTA_DE_SEMANA_AUTOMATICA` le dice al modelo que el atleta no ha dicho nada de esta semana
  y que lo de antes vale con su fecha. Sin ella, un prompt sin check-in no decía nada, y un silencio
  se rellena.
- **Se sube sola al reloj** (decisión del responsable), por el mismo camino que «Subir a Garmin»:
  reemplaza lo que había y retira lo que sobra.
- **Sin nadie delante, el script hace lo que hacía la pantalla**: pregunta el estado
  (`PlanService::pollStatus`, que es lo que GUARDA la semana en MySQL) y lanza la subida. La señal de
  que salió bien es que la semana está en su plan, no el estado del trabajo.
- Como mucho 3 intentos por semana (una generación que falla puede haber costado), "ocupado" no cuenta
  como intento, y respeta el tope de dos semanas. Lo que pasó se le dice en su pantalla (`message`).

## El tope de generaciones (D2, Beta 3.0 F2)

En la Beta 1 un atleta generó 16 planes en un día (~0,30 USD cada uno) y nada lo frenaba. Decisión del
responsable (2026-10-10): **3 versiones de una misma semana y 1 macrociclo cada 7 días; los fallos no gastan
cupo; la semana automática no cuenta** (sí cuenta para la alerta de gasto). Database/028.

- **Qué semana se va a pagar lo sabe sólo el cerebro**: `server.semana_objetivo` (la siguiente, la que se
  rehace, o hoy/mañana en la primera) es la misma función que usa `run_week_generation_task` para escribirla.
  PHP guarda cada generación en `athlete_generations` (`GenerationRepository`: `started` al lanzar, `ok` o
  `error` al recoger el estado) y manda al puente las semanas que ya cuentan (`versiones_previas`); el
  puente decide (`services/ai/cupo_de_generaciones.py`) y contesta 409 con `cupo` ANTES de lanzar nada.
- **Una versión cuenta para una semana si su inicio cae a ±1 día**: la primera semana arranca hoy o mañana
  a elección del atleta. Dos semanas reales están a 3 días o más (`MIN_DIAS_DEL_BLOQUE`).
- **El macrociclo lo comprueba PHP** antes de llamar al puente: no hay semana objetivo.
- **Se dice antes, nunca en dinero**: `/plan/generation-quota` (versiones de la semana actual y desde
  cuándo otro macrociclo); al borrar la semana sale «¿Qué no encaja?» con «Pedir ayuda» o «Eliminar y
  generar otra (quedan N)». Las constantes valen lo mismo en Python y PHP (`test_cupo_de_generaciones`).
- **Alerta de gasto**: `usage_ledger.gasto_por_dia` (sin el banco de pruebas) → `/bridge/usage-alerts` →
  `/admin/metrics/alerts`, en Salud de la app; y `SpendingAlertService::avisarAlAdministrador`, desde el
  cron horario de las semanas automáticas, deja un aviso a cada administrador una sola vez por atleta y día
  (`notifications.user_id`: NULL sigue siendo "para todos").

## Mover una sesión desde la app (Beta 3.0, F6)

Decisión del responsable (2026-10-10, ampliada el 2026-10-21): cualquier sesión de una semana ACTIVA (la de
hoy o una posterior ya escrita), también la de un día de esta semana que ya pasó, a hoy o a un día que viene de
su semana o de la otra activa; una semana terminada no se toca (su expediente ya se cerró); nunca el día de
carrera (ni hacia él); un destino ocupado se intercambia (no si la sesión era de un día pasado) o queda como
segunda sesión (como mucho dos); lo que no conviene se AVISA y no se impide (su tabla no da ese deporte ese día, dos días de calidad seguidos --sin
contar unas rectas--, calidad la víspera de la carrera).

- **El cálculo es puro y del cerebro** (`services/ai/edicion_de_sesion.py`): trabaja sobre el plan guardado
  como diccionario (no pierde campos de planes viejos), valida el resultado con el esquema y devuelve el
  resumen, los avisos y `cambios_registro`. `WorkoutDay.edited` (`moved_from`) va fuera de todo esquema de
  la IA (`SkipJsonSchema`).
- **PHP guarda sin pisar**: `PATCH /plan/session` con `plan_version` (sha1 del plan; `replaceWeeklyPlan` en
  una transacción con la fila bloqueada), 409 si hay una generación o una subida en curso, y `dry_run` para
  la vista previa. Cada sesión tocada queda en `athlete_plan_edits` (Database/031) con la versión del
  coach: de ahí sale «volver a la del coach» (F7).
- **La semana siguiente lo sabe** (F6.6): el plan anterior lleva `edited` y `_format_previous_plan` lo dice
  en la línea de la sesión y en un párrafo, como preferencia OBSERVADA; el «## Output» pide una frase del
  racional (`RATIONALE_DE_CAMBIOS`). Pedida sólo en el bloque del plan anterior, dos réplicas no la escribieron.
- **El reloj no se toca al mover**: los ids pasan a sus claves nuevas (`upload_ledger.mover`) y la sesión
  queda «cambios sin enviar» hasta la siguiente subida. El registro no se mueve con una subida en marcha (se
  mira su estado: el worker borra su `spec.json` y `_is_busy` la daría por muerta).

## Ajustar una sesión y volver a la del coach (Beta 3.0, F7)

Decisión del responsable (2026-10-10): ajustar y añadir pasos, 1 a 1 con lo que va al reloj, sin IA.
`services/ai/ajuste_de_sesion.py` trabaja con ELEMENTOS (paso, serie, o bloque si la repetición tiene otra
forma, y entonces sólo sus repeticiones) y los vuelve a pasos:

- **Lo que no cambia es el paso del coach, byte a byte** (`_firma`, con los números normalizados: el plan
  llega de PHP con `600.0` y `{}` y vuelve del navegador con `600` y `[]`). Lo que cambia se escribe desde
  sus campos (`rendering/describir_paso.py`): las horas como horas (lo lee bien la red de duraciones) y el
  hijo de una serie sólo con su trabajo (lo que lee `_work_description`; nada de «8× · 8x50m»).
- **Pasa el guardián de unidades** (`WorkoutStep._check_shape_and_units`): un ritmo en minutos no llega al
  reloj aunque lo escriba el atleta, y se le dice cómo se escribe.
- **Las plantillas llevan SUS números** (`plantillas_de_paso.py`): sus bandas de ritmo o su FTP; sin ellas,
  zona de pulso. En natación, sin objetivo: el reloj no lo lleva.
- **Avisa sin impedir**: calidad sin ritmo, un ritmo fuera de sus bandas, una serie sin recuperación, ±30 %
  de la del coach. **Volver a la del coach** repone su versión (de `athlete_plan_edits`) y deja el día donde
  está; los cambios se marcan `reverted_at`, nunca se borran.
- **Una nota por paso y los campos de natación** (2026-10-23): la nota va al final del texto del paso y de cada
  repetición (`_con_nota`), y «Elegir un drill» la toma del catálogo (natación o carrera). Estilo, material y
  tipo de nado van al reloj como campos suyos; si sólo cambia eso, el texto del coach se queda y se le añade lo
  nuevo (`_con_nado`): en el reloj de José, poner pull buoy a «4x100m crol a 1:47–1:53/100m» borraba el ritmo.

## La fuerza de una semana y los entrenos que añade el atleta (2026-10-23)

Decisión del responsable: «si lo edita en su semana, sólo se edita en la semana; si lo edita desde el módulo de
fuerza, se edita permanente (como está en el módulo, sin reglas extra)». Y sobre añadir: «está bien si la agrega
el atleta, ya es su responsabilidad: es lo mismo que salir a correr sin que lo haya planificado el coach».

- **Ajustar la fuerza** (`services/ai/ajuste_de_fuerza.py`, `EditorDeFuerza.vue`): por grupos de
  `StrengthBlock.groups`; cambiar un ejercicio por otro del catálogo (`CatalogoDeEjercicios.vue`, la misma lista
  que `exercise_catalog.py`, con los de su familia primero), añadir seguido en un grupo o al final, quitar,
  reordenar, y series, repeticiones, carga, descanso y nota. El ejercicio que no cambia vuelve tal cual, con su
  id (el registro de fuerza lo sigue); uno ajustado conserva el id; uno nuevo va sin id y con su nombre de Garmin
  sólo si está verificado (26 de 68: los demás llegan como un paso con su nombre escrito, y se avisa). Su
  programa en el perfil no cambia. El resumen dice qué cambió respecto a la del coach; las etiquetas del atleta
  se reconocen por su motivo («Lo cambiaste tú…»), porque el paso de fuerza del coach también escribe «en vez de».
- **Añadir un entreno** (`services/ai/sesion_del_atleta.py`): hoy o un día que viene de una semana activa, nunca
  el de una carrera, como mucho tres sesiones al día. Empieza con calentamiento, lo principal y enfriamiento de
  sus plantillas (`nuevo_editor`), o con ejercicios del catálogo si es fuerza. Lleva `edited.added`: «La
  añadiste tú», y sólo esos se quitan. Si el día tenía una sesión sola, ésta pasa a «am»; al quitar la añadida
  vuelve a «unica» (con su entreno del reloj).
- **El reloj**: la huella mira el bloque de fuerza y los textos de los pasos SÓLO en una sesión que el atleta
  ajustó o añadió (lo que ya está en los relojes y nadie tocó no pasa a «cambios sin enviar»). «Enviar al reloj»
  manda sólo esas fechas y, en ellas, retira lo que ya no está en el plan (un entreno añadido, enviado y quitado
  después sale «sobra» hasta enviarlo). Probado en el reloj de José el 2026-10-23: «4/4 · 3 reemplazadas», un
  entreno por sesión tras intercambiar, «0/0 · 1 retiradas» al quitar el añadido; todo borrado después.

## El registro de subidas sabe dónde está cada entreno (Beta 3.0, F5)

`garmin_uploads.json` es lo único que dice qué entrenos del reloj son nuestros, y desde la F5 también DÓNDE
están: `scheduled_date`. Mover una sesión (F6) no reprograma en Garmin (el nombre lleva la fecha): se borra y
se vuelve a crear en la siguiente subida, que primero crea y después borra (`upload_day`). Hasta entonces:

- `upload_ledger.mover` lleva el `workout_id` a la clave nueva, todos los cambios a la vez (un intercambio no
  pisa nada) y nunca sobre una sesión que se queda; el test de umbral no se mueve.
- `estado_de_la_sesion`: «cambios sin enviar» si la fecha programada no es la de la sesión o la huella cambió.
  La huella no mira la fecha a propósito (la regenerada conserva la suya) y por eso hace falta la fecha.
- Database/030 `athlete_garmin_uploads` es su copia en MySQL (entra en los respaldos), escrita entera al
  terminar una subida y al borrar un plan (`PlanService::copiarRegistroDeSubidas`). **Nunca se borra del
  reloj con la copia** mientras sea copia; `comparar_registro_de_subidas.php` dice si coinciden.

## Invitaciones (Beta 3.0, decisión del responsable del 2026-10-11)

«Crecimiento exponencial a través de meses o semanas gratis por referrals; planta las bases.» Database/033:
`users.referral_code` y `athlete_referrals`. `ReferralService` (PHP) tiene la regla:

- **El enlace es `<la app>/?ref=CÓDIGO`.** `main.js` guarda el código en el navegador (`utils/invitacion.js`)
  y viaja con el login de Google (`referral_code`); el servidor sólo lo usa al CREAR la cuenta
  (`alCrearLaCuenta`): nadie se invita a sí mismo ni cambia de invitador.
- **Todo lo gratis va atado a una cuenta Garmin NUEVA** (decisión del responsable, 2026-10-20: «que no haya
  free riders; las cuentas de Garmin son limitadas, no como las de Google»). Al conectar Garmin
  (`ProfileController::garminImport` → `FreeWeeksService::alConectarGarmin`) se mira la huella de esa cuenta
  (`garmin_accounts_seen`, Database/035: sha256 del `profileId` de Garmin, sin FK para que sobreviva al
  borrado): si nadie la había conectado, 2 semanas de prueba (`SEMANAS_DE_PRUEBA`), y si entró invitado, la
  invitación cuenta y ganan 2 y 2 (`SEMANAS_PARA_EL_INVITADO`, `SEMANAS_POR_INVITADO`; las de quien invita se
  acumulan por invitado). Un Garmin ya visto no da nada y su invitación se marca `garmin_repeated`. El saldo
  vive en `athlete_free_weeks`, una fila por concesión; cambiar las constantes no reescribe lo ganado. Hasta
  el 2026-10-20 la invitación contaba al generar la primera semana, con 4 y 4.
- **Son un saldo**: no hay suscripción a la que aplicarlas (Legal, punto 8).
- «Invitar a un atleta» dejó de ser una puerta falsa (`InvitarModal.vue`); abrirlo es `invitar_abierto` y
  Métricas tiene «Invitaciones». La tarjeta de compartir lleva el nombre de pila (se puede quitar) y el código.

## El precio y el muro de pago (decisión del responsable, 2026-10-20)

«El precio del producto es Q60 (8 USD) al mes, y quiero que seamos claros con eso.» `App\Services\AccessService`:

- **El precio se dice siempre igual** (`precio()`): Plan, Invitar, el muro, «Precios» y el Legal lo leen del
  servidor; una prueba fija que el Legal dice lo mismo que las constantes. **Plan anual** (2026-10-21): Q600
  (80 USD) por 12 meses; el ahorro sale de las constantes (`precio()['anual']`).
- **«Precios» vive en la tuerca** (`SettingsModal.vue`, pestaña `precios`) y todo «Quiero suscribirme» lleva
  ahí. Usa los principios de Cialdini con datos del atleta (sus semanas, su carrera, su entrenador) y **nunca
  prueba social ni escasez inventadas**.
- **Acceso = semanas gratis por calendario + meses pagados** (`calcular`, pura): cada semana gratis son 7 días
  desde que se concede (`athlete_free_weeks`, al conectar un Garmin nuevo); la que llega con días por delante se
  suma al final; un pago (`athlete_payments`, Database/036) alarga desde donde terminaba. El día es el de
  Guatemala (`ZONA`).
- **El muro sólo cierra lo que cuesta dinero**: `muro()` en `startSeason` y `startWeek` (también la semana
  automática), después de las condiciones de uso, con 402 `paywall`. El plan, el reloj, el progreso y el chat
  siguen. Un administrador nunca tiene muro, y sin las migraciones 035-036 no hay muro para nadie.
- **Sin acceso y con un Garmin ya visto** (`estado` `garmin_usado`, 2026-10-22): el muro no le dice «Conecta tu
  Garmin para empezar tus semanas gratis» (ya lo conectó y no le dan semanas), sino que esa cuenta ya las usó.
- **El pago va fuera de la app**: el administrador lo registra en «Usuarios» (`POST /admin/users/{id}/payment`,
  `{months}` o `{plan: 'anual'}`) y el atleta recibe un aviso con la fecha. Es contabilidad: sin FK y sin
  borrar filas; uno registrado por error se ANULA (Database/037, `.../payments/{pid}/void` con motivo, sólo el
  último vigente) y deja de dar acceso.

## Pedir ayuda (Beta 3.0, F3)

Un reporte llegaba por WhatsApp o «Opinar» sin nada para mirarlo. Ahora una consulta es una fila de
`athlete_feedback` con `context = 'ayuda'` (Database/029: categoría, estado, sesión, diagnóstico, triaje y
respuesta) y viaja por la misma ruta `/feedback` (con su tope diario).

- **El diagnóstico lo arma el servidor, con lista blanca en cada salto.** PHP (`HelpService::diagnosticoPhp`)
  pone lo que sabe de MySQL -- la contraseña de Garmin SÓLO como sí/no -- y el puente
  (`/bridge/support-bundle`, `services/ai/diagnostico_de_ayuda.py`, sin IA y sin escribir el espejo) pone la
  sesión: lo DECIDIDO (`week_intents.json` de su traza), lo CODIFICADO (los minutos que suman sus pasos) y el
  RELOJ (registro de subidas y huella), más sus trabajos, las redes que le corrigieron algo y la última
  semana de su expediente. Una clave que PHP mande y el puente no conozca no entra (`CLAVES_DE_PHP`; una
  prueba fija que coinciden). Más de 64 KB se recorta y se dice (`_recortado`).
- **El triaje propone, no decide** (`services/ai/triaje.py`): perfil, garmin, codificación (más de un 15 % de
  minutos, otro deporte o la calidad cambiada), decisión de la IA, la app o `desconocido`, en el orden que
  pide la categoría, y enseña todas las señales. **Una carrera en un día de descanso no es una señal** (el día
  de una carrera está disponible para ella) ni lo es no tener la contraseña guardada.
- **El atleta ve antes qué se adjunta**, con el texto exacto, en `AyudaModal.vue`. Los errores del navegador
  se guardan en memoria (los últimos 10, `utils/erroresDelNavegador.js`) y uno por minuto va al panel de salud
  (`error_cliente`).
- **La respuesta le llega como aviso a él solo** (`notifications.user_id` + `feedback_id`); los avisos se
  recargan al cambiar de pantalla o al volver a la pestaña, como mucho cada 60 s.
- Analizar una incidencia: `php Backend/scripts/exportar_incidencia.php <id>` y
  `python PaginaLocal/scripts/analizar_incidencia.py incidencia_<id>.json` (la traza, los prompts que
  entraron y el esqueleto de la prueba con los números). En producción, con permiso para cada una.
- La lista de lo que deja cada migración vive en `App\Support\Migraciones` (la usan el script de
  verificación y el diagnóstico).

## Lo que el atleta dice de ESTA semana

- **"¿Algo más que quieras que se tenga en cuenta esta semana?"** (`week_note`,
  migración 021): la salida en grupo del sábado, la carrera familiar, el volcán.
  Vale para una semana, por eso va en el check-in y no en el cuaderno. Llega a la
  semana como actividad que cuenta; si cae en un día que su tabla marca como
  descanso NO se programa ahí (el validador la convertiría en descanso): se
  encajan los días de alrededor. `CheckinRepository::COLS_OPCIONALES` aguanta que
  la 021 todavía no esté.
- **Cuándo se puede cerrar la pestaña.** La pantalla decía "puedes cerrar" desde
  el primer segundo, y el coach pregunta a mitad de generación: quien cerraba no
  veía las preguntas y la generación esperaba diez minutos. El worker publica
  `questions_done` cuando el coach preguntó o decidió no hacerlo, y hasta entonces
  `PlanView` pide quedarse.

## Lo que lee el atleta: el porqué y las hojas (2026-10-22)

- **El porqué de la semana se traduce, no se quita.** `week_rationale` se escribe después de los días en la
  misma llamada: es una explicación ex post y cambiar su forma no cambia lo que se decide. Entre agentes las
  siglas se quedan (VO2max, FTP, ACWR, HRV: `_LANGUAGE_DIRECTIVE`); en lo que lee el atleta (`week_rationale`,
  `purpose`, `adaptation`, el informe) ninguna sigla, índice o escala va sola. Forma: una frase con qué semana
  es y por qué, y 3-5 líneas «- » con lo que pesó, cada número dicho en palabras. VO2max, tempo, umbral, Z2 y
  RPE se quedan.
- **La hoja de la semana es una página por semana**, sin zonas ni porqué (`printable_plan.html.jinja2`); las
  zonas, ritmos y potencias viven en la hoja de la temporada (`printable_season.html.jinja2`), con la fecha de
  cada carrera y cuánto falta.
- **Historial dice qué hizo** (sin IA): de `athlete_track_record`, la línea de esa semana
  (`TrackRecordRepository::resultados`), contada al cerrarla; los ✓/✗ son los de su Garmin de hoy y la pantalla
  dice que pueden no coincidir.
- **El reparto por día de la semana cuenta sesiones, no fechas** (`track_record.summarise_week`) y un día sólo
  se juzga con 3 sesiones o más (`charts.MUESTRA_MINIMA_DEL_DIA`). Las líneas ya cerradas conservan la cuenta vieja.

## Brick: se lee, no se escribe

`sport="brick"` ya no lo emiten ni `DayIntent` ni `FormattedWorkoutDay` -- un
multideporte no le llega bien a varios relojes. Un brick son **dos sesiones el
mismo dia** y lo que las encadena va escrito en el texto.

`WorkoutDay` SI lo admite, a proposito: los planes guardados lo tienen dentro, y
quitarlo de ahi haria que un atleta no pudiera ni abrir su semana ni volver a
subirla. La asimetria esta fijada en `tests/test_brick_se_lee_no_se_escribe.py`.

## Se planifica por carga y calidad, no por horas

Desde la v1.2 la moneda del plan es la **carga de Garmin** (EPOC, la que el
atleta ve en su reloj), no las horas. Medido en la cuenta del atleta 2: una hora
de bici suave le carga ~6-12 y una de series ~96; la semana de más carga del
trimestre fue de 5,5 h. Las horas quedan como restricción de agenda.

- `services/ai/weekly_load.py` calibra con el historial REAL cuánto le cuesta al
  atleta cada tipo de sesión. Es "calidad" la actividad con ≥40 % de minutos
  vigorosos; con menos criterio la tasa se diluía (ver su docstring). Se abstiene
  sin muestras. El macrociclo guarda las tasas en `load_rates.json` y la semana
  las reusa.
- El macrociclo recibe la carga crónica, el rango óptimo de Garmin (0,8-1,5× la
  crónica) y el **Load Focus**, y cada fase trae `weekly_load_target`.
- La semana recibe la carga como **referencia, no como techo**
  (`compute_weekly_load_target`): la crónica, los últimos 7 días, el rango de
  Garmin dicho como lo que es, y si la semana es de afinamiento o de descarga.
  La primera versión de la v1.2 calculaba un techo (+10 % sobre la crónica) y el
  validador recortaba lo suave para cumplirlo; **se retiró**, porque sustituía el
  criterio del entrenador por un número derivado de unas zonas que pueden estar
  mal (`tests/test_la_carga_manda.py`). La carga estimada de la semana sólo se
  anota en el log del worker. Este párrafo siguió describiendo el techo después
  de quitarlo.

**Un número en negrita gana a cualquier matiz escrito debajo.** El planificador
rellenó "Objetivo: **6.0h**" en una semana de taper aunque una línea decía "no es
una cuota". Si un número no es un objetivo, no se presenta como uno.

**Cuando la fase y el atleta se separan** -- un Ironman cuya fase describe 12 h
con un atleta que sigue en 6 --, el bloque de carga pone los dos números y dice
que la distancia se recorre subiendo desde lo que absorbe; si no se acerca, es
información para revisar el macrociclo, no para apretar. Las horas de la fase ya
no se llaman "Target", y el bloque de horas no llama "historial" a lo que se le
PLANIFICÓ. El macrociclo, por su lado, no aplana las fases para que quepan en hoy
ni escribe números que finjan que la distancia se cierra.

## La intensidad verdadera: 3 zonas, el ritmo, y UNA sola respuesta

**El pulso solo miente sobre la intensidad, y Garmin también lo usa.** Medido en el
atleta 2 (seis meses hasta el 2026-09-13): de 43 tramos llanos corridos más rápido
que su umbral, en el 93 % el pulso MEDIO seguía bajo Z4 y en el 33 % ni el pico
llegó. Sus intervalos de 60" a 4:54/km iban a 135-151 ppm. El Load Focus y el
Training Effect de Garmin salen del pulso, así que a ese atleta le marcan
"shortage" aunque haga VO2max -- y en su macrociclo real el experto de métricas lo
escribió, la síntesis le propuso recalibrar zonas y la semana lo convirtió en
intención futura.

`services/ai/intensity_distribution.py::es_sesion_dura` es **la única respuesta de
la app a "¿esta sesión fue de calidad?"**. La usan el reparto de intensidad, las
tasas de carga (`weekly_load.tasas_de_carga`), los días de calidad
(`training_habits`) y las sesiones exigentes (`training_response`). Si devuelve
None, cada consumidor cae a los minutos vigorosos de Garmin -- que es lo que pasa
con un atleta sin zonas, y lo que siguen ejercitando las pruebas viejas.

- **Modelo de 3 zonas**: primer umbral = techo de la Z2 del reloj; segundo = el
  umbral de lactato en carrera, o el suelo de Z5 en bici y natación. **Tocar Z4 NO
  es estar por encima del segundo umbral** (la Z4 del atleta 2 empieza 9 ppm por
  debajo de su umbral); la primera versión lo afirmaba y contaba el trabajo de
  umbral como alta.
- **En cinta, el reloj va por FC** (Beta 3.0, F10): la velocidad la fija la máquina y el reloj no mide el ritmo
  bajo techo. La excepción va DENTRO de la regla de modalidad obligatoria de la ficha y del codificador (en un
  punto aparte, Haiku seguía poniendo ritmo), y el validador no exige ritmo a una sesión en cinta.
- **El ritmo vota en carrera** (`services/ai/ritmo_de_tramos.py`): tramos llanos de
  1' o más, nunca en cinta (la del 18-mar marcó 32:40/km), nunca en cuesta. La
  sesión se queda con la señal más dura y el bloque dice cuántas altas lo fueron
  sólo por ritmo. **En bici el ritmo no vota nunca**; la potencia votaría, pero no
  está escrita porque no hay una cuenta real con potenciómetro contra la que
  verificarla.
- **Se cuenta por sesión, y se da también por tiempo**: la literatura usa las dos,
  y por tiempo el pulso medio subestima la zona 3 en series cortas.
- **Llega a quien lee a Garmin**: expertos de métricas (con la instrucción de leer
  el Load Focus contra el reparto medido) y de actividad, los dos planificadores y
  el chat. Un atleta sin zonas recibe cero tokens.
- **La deriva cardíaca** (`services/ai/aerobic_decoupling.py`) es una señal de
  TEMPORADA: 3 sesiones válidas de 24 en seis meses del atleta 2 (bici sin
  potencia, series y progresiones no miden nada). Se calla sin ninguna válida.

El prompt del macrociclo da los cuatro repartos -- piramidal, polarizado, umbral,
alta intensidad -- con su evidencia y los factores para elegir (lo que exige la
carrera, lo que su historial dice, sus horas reales), **sin decirle cuál toca**. Una
versión anterior decía que entrenar entre umbrales "es la forma clásica de
estancarse": era un sesgo y la evidencia en triatletas amateur dice lo contrario.

## El bucle por fase: el macrociclo aprende de SU atleta

Al CERRAR cada semana, `track_record.jsonl` guarda `marcadores`
(`services/ai/respuesta_a_las_fases.py::marcadores_de_la_semana`): predicciones de
5K/10K/21K, ritmo umbral, umbral de lactato, VO2max, HRV de 7 días y su base, pulso
en reposo, sesiones suaves/umbral/altas, la deriva medible y la fase con el reparto
y el techo planificados. El macrociclo recibe "Cómo respondió a cada fase": lo
planificado, lo hecho y cómo se movieron los marcadores desde el cierre de la
semana anterior a la fase hasta su final.

**Asociaciones, no causas**, y el bloque lo dice: una carrera, un afinamiento, el
calor o un recálculo de Garmin también mueven una predicción. Se calla con menos de
dos semanas por fase y sin marcadores; dos "Base" de dos macrociclos son dos fases.

**Las regeneraciones no son entrenamiento.** El expediente de pruebas del atleta 2
(seis "semana 1" que se pisaban) se archivó el 2026-09-13 como
`track_record.pruebas-2026-09-13.jsonl` para que ni la IA ni el panel aprendieran
de él. Antes de sacar conclusiones de un expediente, míralo.

## El macrociclo: un mapa hasta la carrera A, firme sólo 16 semanas

El prompt pedía "12-24 semanas" y a la vez "taper antes de las A". Con la 10K A
del atleta 2 a 31 semanas, el plan terminó 7 semanas antes de su carrera
principal. Ahora (`SEMANAS_HASTA_LA_A`, `SEMANAS_FIRMES` en
`season_planner_node.py`): si hay una A a 40 semanas o menos, el macrociclo
llega hasta ella y se construye hacia atrás; **sólo sus primeras 16 semanas son
firmes**. Las fases que empiezan después llegan a la semana marcadas como
por confirmar (al atleta no se le dice "firme", "provisional" ni "preliminar": el macrociclo escribe la
línea «Fases posteriores por confirmar según tu progresión:», decisión del responsable del 2026-10-09), y `Frontend/src/utils/macrociclo.js` pide rehacerlo al pasar la
16, cuando el bucle por fase ya sabe cómo respondió. Ni un plan largo rígido
(los plazos fijos de la periodización clásica no tienen respaldo, Kiely 2012) ni
dos cortos a ciegas (lo de efecto residual corto tiene que caer cerca de la
carrera, Issurin). **Las dos constantes tienen que valer lo mismo en Python y en
JS.**

**Cada carrera llega con su semana** (`con_semana_del_macrociclo`, contada desde
`season_start_date`, la de siempre si se refina). El macrociclo de Jessie del
2026-09-15 empezó la fase específica de su trail B la semana de la carrera
(10-12 con el trail en la 10): sólo recibía fechas. El prompt dice ahora que una
carrera se prepara en las semanas ANTERIORES y pide que el informe diga cuántas
y por qué, **sin fijar un número** (eso es criterio de ese atleta y esa carrera);
`fases_que_empiezan_en_su_carrera` lo avisa en el `worker.log`, no bloquea. Y la
disponibilidad no es una `constraint`: una réplica escribió "Sábado descanso
total" como `limitacion`, que pisaría un sábado abierto en el check-in.
Dos réplicas sobre su prompt real (gpt-5-mid, 0,06 USD cada una): sólo con la
regla, la fase pasó a 9-11 y siguió escribiendo el sábado como `limitacion`; con
la regla y la frase de las `constraints`, trabajo específico en las semanas 6-10
con afinado corto en la 10, sin el sábado y sin avisos. Cuántas semanas recibe
una carrera B varía entre réplicas y queda como criterio del modelo.

**Las fechas, verificadas contra el calendario real del atleta 2 (2026-09-24).** Arranque el lunes 21-sep
(lo generó el miércoles 23), gravel B el sábado 24-oct en la semana 5 y 10K A el martes 20-abr-2027 en la 31,
con el afinamiento en la 30-31: la aritmética (Python y su espejo JS) estaba bien. Lo que fallaba era lo que
recibían los modelos:
- el macrociclo leía "`season_start_date` es hoy" (el 23) y las semanas se contaban desde el 21: contado
  desde hoy, el 10K caía en la 30. Ahora el prompt dice la fecha de la semana 1 (`inicio_del_macrociclo`);
- una carrera anterior a la semana 1 llegaba como "semana 0": ahora va marcada `ya_corrida`, sin semana;
  y si cae entre HOY y el inicio de la semana (generada el jueves para el lunes) es `antes_de_esta_semana` y
  `todavia_por_correr`, no `ya_corrida` (2026-10-22: el coach le preguntó a José si ya había corrido el gravel
  de pasado mañana);
- la semana recibía las carreras sólo con su fecha. `carreras_vistas_desde_la_semana` le da, contado por
  el código, `ya_corrida`, `en_esta_semana` o los días, y con macrociclo la semana de la carrera y
  `semanas_que_faltan`;
- la lista de la semana se cortaba a 3 días antes de HOY (`solo_las_que_vienen`) y la nota de después de
  una carrera mira hasta 21 días antes de la SEMANA: la segunda semana tras un maratón la perdía. Ahora se
  cuenta desde el inicio de la semana con `_VENTANA_DE_RECUPERACION_MAX`, y el bloque de carreras del
  contexto separa las corridas respecto a esa misma fecha (el 5K del domingo es corrido para la semana del
  lunes aunque se genere el sábado).

**Cómo se rehace.** El aviso "Toca revisar tu macrociclo" sale en la pantalla de
Plan (no es una notificación) desde la semana 17 y mientras falten más de dos
semanas; su botón es el mismo que "Regenerar Plan de Temporada". Pasada la 16,
rehacer NO es refinar: `_macrociclo_por_revisar` manda el bloque de plan nuevo,
el macrociclo arranca HOY (`_fijar_arranque` pone la fecha en código, no el
modelo) y recibe el anterior como historia (`previous_season.py`, con la fase en
la que iba), el expediente y cómo respondió a cada fase. Antes de la 16 se
refina conservando la fecha. PHP archiva el plan viejo en `athlete_plan_history`
y la semana ya generada no se toca. Sin esto, rehacer en la semana 17 conservaba
la fecha vieja: el atleta seguía en la 17 y el aviso no se iba nunca.

## Reglas del atleta sí, reglas de población no

Se prohibía derivar cualquier regla fija del HRV. Era demasiado: el entrenamiento
guiado por HRV funciona con reglas, ancladas a la base del propio atleta, con
persistencia y una segunda señal. Lo que estaba mal de la regla real del
macrociclo ("sueño < 75" para quien promedia 77) era su ancla. `recovery_state.py`
y el prompt del macrociclo dicen ahora qué regla vale y cuál no. El ACWR sigue
sin poder dictar una decisión: no tiene valor predictivo de lesión (Impellizzeri
2020).

Por eso **el guardarraíl de ACWR se retiró** (2026-09-14): con el ACWR de Garmin
por encima de 1,5 recortaba la semana 1 al 70-80 % y le decía al atleta que su
carga estaba "en zona de riesgo de lesión", y venía activado por defecto. Su
casilla salió del perfil; la columna `ai_load_guardrail_enabled` sigue en la base.
Por lo mismo, el estrés "alto" del perfil ya no ordena "conservative intensity":
se lee junto a su recuperación y su check-in.

**El informe recibe el bloque de carga de Garmin.** "Riesgo de lesión" y "óptimo
0,8-1,3" no los escribió ningún experto: los puso la síntesis, el único nodo que
hablaba de carga sin ese bloque. Una guía que prohíbe palabras tapa el síntoma;
el cable lo arregla.

## El volumen semanal se mide, no se pregunta

`data_extractor.get_weekly_training_volume()` suma la duracion de las
actividades de las ultimas 12 semanas en **una sola llamada** y sin ningun
modelo. No usa `get_recent_activities`, que baja el detalle de cada sesion: doce
semanas serian casi cien llamadas en mitad de un formulario.

La procedencia viaja en `garmin_autofilled_fields`, y el prompt cambia con ella:
medido se dice `MEASURED`, declarado se sigue diciendo *"a rough athlete
estimate"*. Las semanas sin entrenar cuentan como cero y el bloque lo advierte.

**Los records personales se traen en CRUDO y no rellenan ningun campo.** Garmin
los identifica por `typeId` numerico y esa correspondencia no esta verificada
contra una cuenta real: mapearla a ojo pondria el tiempo de 10K en el campo del
5K, sin dar error, alimentando las bandas de ritmo con la marca equivocada.

## Las imagenes de los ejercicios

`services/ai/exercise_catalog.py` tiene los 126 movimientos y drills con sus
alias, y es **la fuente unica**: de ahi sale la lista cerrada que el prompt le
ofrece al codificador para elegir `video_key`, y de ahi genera
`scripts/generar_catalogo_frontend.py` el JSON que lee el frontend. Dos listas
escritas a mano son dos listas que divergen.

La regla: **abstenerse antes que acertar por suerte.** El emparejamiento es
exacto contra los alias y devuelve `None` en cuanto duda -- ensenarle al atleta
el dibujo de otro ejercicio le hace entrenar el movimiento equivocado creyendo
que hace el suyo. Los alias incluyen a proposito las malas traducciones que el
modelo produce (*bicho muerto* por dead bug), para que la imagen salga bien.

Las imagenes viven en `Frontend/public/ejercicios/NNN.jpg`. **`exerciseName` de
Garmin NO enciende la animacion del reloj**: eso se afirmo durante meses en el
docstring de `garmin_vocabulary.py` sin haberse verificado nunca, y se comprobo
en la beta que no ocurre. Lo unico medido es que el nombre sobrevive al viaje de
ida y vuelta de la API.

## Condiciones de uso: la puerta legal

`Backend/src/Support/Legal.php` tiene el TEXTO y su VERSION juntos, en el codigo,
porque tienen que cambiar a la vez y porque asi `git log` responde "que decia
cuando este atleta lo acepto". `users.terms_accepted_at` + `terms_version`
(migracion 015) guardan la constancia; **subir `Legal::VERSION` vuelve a pedir la
aceptacion** a quien tenga una anterior, y a nadie mas.

La aceptacion va en `users` y no en `athlete_profiles` a proposito: un atleta
puede borrar sus datos de entrenamiento -- la app se lo permite -- y eso no puede
borrar la constancia de que acepto.

**`LegalGate.vue` no se enseña si el backend no dice `termsVersionRequired`**, y
el backend solo lo dice si `UserRepository::supportsTerms()` encuentra la
columna. Las migraciones de este repo se corren a mano: sin esa condicion, un
despliegue por delante de la 015 le enseñaria la puerta a todos y el UPDATE
reventaria contra una columna ausente, **dejando a los atletas atrapados en una
pantalla que no pueden pasar**. Misma tolerancia que `EventRepository::record()`.

Lo que el texto dice y ninguna plantilla dice: **que los datos de salud y
entrenamiento del atleta se envian a OpenAI, Anthropic y Google** para escribir
el plan. Es lo mas importante del documento y por eso tiene seccion propia.

**Versión 2026-10-11 (Beta 3.0).** Cuenta el diagnóstico de «Ayuda» y que se borra a los 90 días
(`FeedbackRepository::DIAS_DEL_DIAGNOSTICO`, lo borra el cron horario `semanas_automaticas.php`), que las
notas se retiran, las invitaciones y sus semanas gratis, la tarjeta con nombre y el botón «Ayuda».

**Version 2026-09-15 (Beta 2.0, relanzamiento desde cero).** Reescrita con las
decisiones del responsable del producto: el atleta paga el costo real de IA, el
responsable figura como "Power Coach AI", minimo 18 años (casilla obligatoria en
`LegalGate.vue`). La anterior afirmaba que el nombre no se envia a la IA -- los
prompts lo llevan -- y hablaba de un cuestionario retirado en la v1.2: **si cambia
lo que la app envia o guarda, cambia el texto y sube la version.** No la ha
revisado un abogado.

**Promesas del texto que dependen de scripts, no de la app:**

- Borrar la cuenta entera en 30 dias: `Backend/scripts/borrar_cuenta.php correo
  [--ejecutar]`. Retira sus entrenos de Garmin, borra trazas, borra la fila de
  `users` (el resto cae en cascada) y deja su gasto en la fila anonima
  `user_id = 0`. Ensayado con un atleta sintetico.
- Relanzar desde cero: `Backend/scripts/retirar_garmin_antes_del_reinicio.php`
  **antes** de `Database/reinicio_beta_2_0.sql` y de vaciar `_runtime/`. El
  registro de subidas (`garmin_uploads.json`) es lo unico que dice que entrenos
  del reloj son nuestros; borrado antes, se quedan para siempre. Orden completo
  en `contexto/planes/PLAN_LANZAMIENTO_BETA_2_0.md`.

**Un error de Garmin se le dice al atleta en su idioma**
(`services/garmin/client.py::explicar_error_de_garmin`): contraseña rechazada,
verificacion en dos pasos (no soportada), 429 y timeout. Lo que no reconoce se
deja tal cual: un mensaje amable que adivina mal manda a arreglar lo que no esta
roto.

## Adherencia y expediente

`services/ai/adherence.py` empareja cada sesión prescrita con las actividades
reales de Garmin por **fecha + deporte + duración** (tolerancia del 25%), con un
día de margen para las sesiones movidas. Además, en las tres clases que existen
por su intensidad (`vo2max`, `tempo_threshold`, `repeticiones`) compara el **pico**
de pulsaciones contra el suelo de Z4 de las zonas REALES del atleta: si no llegó
ni una vez, la sesión queda como `under_intensity` — cuenta como entrenada para el
volumen y **no** como calidad hecha.

**Los ✓/✗ de la pantalla son la misma lectura** (2026-10-21): `estado_de_las_sesiones.estados` llama a
`build_adherence_report` con mañana como «hoy» (para juzgar lo de hoy) y una sesión de hoy sin actividad queda
pendiente. Dos lecturas que dijeran cosas distintas de la misma sesión serían peor que ninguna.

**Lo que su reloj no ha subido no se juzga** (José, 2026-10-19): con la última sincronización del
reloj (`GarminData.ultima_sincronizacion`, de `get_device_last_used`), una sesión sin actividad de ese
día o posterior queda `pending` con `sin_sincronizar`, y la generación siguiente la mide entera. Antes
salía «no realizada» al expediente, a la racha y a la decisión de la semana.

La comprobación **se abstiene siempre que no sepa** (sin pulsaciones, sin zonas, en
cuestas y progresivos). Un "no hiciste tu sesión" falso cuesta la confianza del
atleta en todo lo demás.

**El ritmo rescata, nunca acusa.** Si el pico no llegó a Z4 pero, en llano, pasó 2'
a la banda de ritmo de su clase o más rápido, la sesión cuenta como hecha y lo dice
(`intensity_by_pace`). Es el caso de un atleta cuyo pulso llega tarde.

Lo que NO se comprueba: que la estructura se ejecutara. 4 series en vez de 5 sale
"completada", y el bloque del prompt lo dice explícitamente.

`services/ai/track_record.py` cierra cada semana en `track_record.jsonl` con unos
200 bytes: prescrito, ejecutado, calidad hecha y caída, ACWR y el reparto por día
de la semana. Es lo que le da al macrociclo memoria de más de siete días sin
mandarle el historial entero.

## El panel del administrador

`/admin/metricas`, sólo administrador. Tres fuentes y tres rutas separadas para que
la caída de una no se lleve el panel entero:

- `/admin/metrics` (MySQL) — embudo **de siempre**, actividad de la ventana,
  activación (cuántos llegan a su primera semana y en cuántos días), retención
  **por cohorte semanal**, y la tabla atleta por atleta.
- `/admin/metrics/spending` (libro del AiService) — gasto por herramienta.
- `/admin/metrics/delivery` (expediente del AiService) — **semanas cerradas contra
  semanas entrenadas**. Es la única que dice si la app SIRVE en vez de si se
  prueba.

Dos reglas que este panel aprendió por las malas: **un embudo mide progreso
acumulado** (mezclar ventanas hacía que se ensanchara hacia abajo), y **una
retención sin cohorte no es una retención** (contaba como fallo a quien se había
registrado ayer). `measuring_since` se enseña siempre: la medición empezó con la
migración 012 y todo lo anterior no está.

### La salud de la app (Beta 3.0, F1)

Lo que antes exigía SSH, arriba de Métricas (`Frontend/src/components/admin/SaludDeLaApp.vue`), con cuatro
rutas propias para que la caída del AiService no se lleve lo demás: `/admin/metrics/health` (el servicio,
la cola y el final del log de errores de PHP), `/jobs`, `/job-log/{id}` y `/nets`. Del lado del AiService,
`bridge/admin.py`, funciones puras sobre carpetas (`tests/test_admin_salud.py`):

- **Nunca `spec.json`**: lleva el perfil entero. Del trabajo sólo sale su `status.json` y su `worker.log`.
- **Todo sale saneado** (`sanear`, y `MetricsController::sanear` para PHP): correos, contraseñas, tokens,
  claves de proveedor y churros de ≥40 caracteres. El `worker.log` real lleva el correo de Garmin del
  atleta y la ruta de sus tokens OAuth.
- **Lo recortado se dice**: «Mostrando las últimas 200 de 334 líneas».
- **Un error de proveedor** (sin créditos, 429, clave, saturado, tiempo agotado) sólo se señala en un trabajo
  que falló o se colgó, sólo en líneas de error y sin la hora delante, y un código HTTP sólo con su
  contexto («Error code: 401»): los milisegundos de «17:36:48,401» marcaban un macrociclo bueno.
- El costo de un trabajo se cruza con el libro de costos por atleta, tipo y ventana de tiempo.

**El libro de redes** (`PaginaLocal/core/redes_ledger.py`, `data/billing/redes.jsonl`, junto al de
costos y fuera de lo que se borra): cada vez que una red determinista corrige algo, una línea
(`plan_validator._anotar_redes`, la vuelta de la decisión y el lint del macrociclo). Es la cuenta que pide
"una red que no corrige nada se borra": el `worker.log` muere a los 3 días. Tres reglas:

- **Sólo escribe el worker** (`redes_ledger.activar()` en `AiService/worker.py`): las pruebas de los dos
  lados llaman a las mismas redes y no pueden llenarlo.
- **Una red en `REDES` tiene quien la anote y al revés** (`tests/test_redes_ledger.py`): una que nadie anota
  saldría "0 correcciones" para siempre y parecería retirable.
- **"0 de N" cuenta desde la primera línea del libro** (`_inicio`): una generación de antes no pudo anotar
  nada. Sin libro empezado no hay N.

## Filosofía de pruebas

Todo corre contra funciones puras: ni una llamada a un modelo ni a Garmin en la
suite. Las pruebas existen donde un fallo se convierte en un entreno que puede
lesionar al atleta o en una generación pagada tirada.

Cuando un fallo aparece contra la cuenta o la API reales — que es como han
aparecido casi todos los importantes de este proyecto — la prueba de regresión se
escribe **con los números reales en el docstring**. Este repo trata "verificado
contra la cuenta real" como evidencia más fuerte que "pasa las pruebas", y guarda
esa procedencia en los docstrings en vez de en un changelog.

**Una aserción no se parchea para que pase en verde.** Se vuelve a apuntar a la
garantía que hay debajo, que casi nunca es la que la aserción decía.
