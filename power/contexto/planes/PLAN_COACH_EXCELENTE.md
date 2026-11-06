# De aquí a un coach excelente (2026-09-11)

Este documento contrasta la investigación de Gemini sobre el estado del arte en
entrenamiento de resistencia con **lo que este repositorio ya hace**, y sale con
cuatro frentes que valen la pena, tres que no, y el orden para hacerlos.

La regla con la que se decidió cada línea es la del proyecto: **se mide, no se
supone; se abstiene cuando no sabe; y ningún número nuevo gobierna una decisión
hasta que se haya visto acertar.**

---

## 1. Lo que la investigación pide y ya está hecho

Conviene decirlo primero, porque es la mitad del documento y evita reconstruir
lo que existe:

| Lo que pide | Dónde está | Nota |
|---|---|---|
| Descartar el ACWR como predictor de lesión (Impellizzeri) | `services/ai/load_state.py` | Ya se reportan **los dos** (el exponencial de Garmin y el nuestro de medias móviles), cada uno con su método y su fecha, y **ninguno gatea ninguna decisión**. El docstring dice exactamente por qué. Nuestra postura es más estricta que la de la investigación, que todavía propone vigilarlo. |
| Reemplazarlo por carga real y picos de volumen | `services/ai/weekly_load.py` | La moneda del plan es la **carga de Garmin (EPOC)**, con las tasas calibradas contra el historial del propio atleta, techo de carga por semana y recorte de lo suave antes de pagar una reparación. La investigación no menciona esto y es lo más avanzado que tenemos. |
| HRV contra la línea base individual, nunca un corte absoluto | `services/ai/recovery_state.py` | Se entrega el estado que Garmin calcula **contra la base de esa persona**, y está documentado por qué NO se degrada la sesión automáticamente: una noche mala no es evidencia de nada. |
| Zonas de FC individuales, por deporte | `services/ai/prescription.py`, `workout_builder.py` | Del reloj, por deporte (la Z2 de nadar y la de correr van 28 ppm aparte en el atleta 2), y se manda a Garmin **el número de zona**, no un rango de ppm nuestro. |
| CSS en natación, devaluada para series largas | `prescription.py::_SWIM_CLASS_FRACTIONS` | La CSS sale de un test que el atleta declara (el ritmo de piscina de Garmin no es fiable), y la banda aeróbica ya va **2–8 % más lenta** que la CSS, que es la corrección que la investigación pide. |
| GAP / ritmo ajustado por pendiente para trail | `weekly_planner_node.py` (reglas de trail) | Resuelto río arriba y mejor: en terreno con desnivel **no se prescribe ritmo**, se prescribe esfuerzo (zona), tiempo de subida y D+. Un ritmo corregido sigue siendo un ritmo que el reloj regaña. |
| Atleta con poco tiempo: densidad, indoor, encadenar deportes | perfil (`bike_setup`, cinta) y dos sesiones el mismo día | El brick se retiró como entreno exportable a propósito (no le llega bien a varios relojes): son dos sesiones y el texto dice que se encadenan. |
| Reductores acumuladores, topes de contexto, trazabilidad | `training_analysis_state.py`, `prompt_capture.py`, `reasoning/` | Cada llamada guarda el prompt exacto que recibió. Es lo que permite auditar si un dato llegó, en vez de suponerlo. |

---

## 2. Los cuatro frentes que sí valen la pena

### F1 · La distribución de intensidad, medida *(el hueco de verdad)*

**Qué falta.** La palabra «polarizado» no aparece en ningún prompt ni en ningún
módulo de este repositorio. Hay techo de **carga** y hay clases de intensidad
por sesión, pero **nadie mide ni prescribe el reparto**: cuánto del mes pasado
fue por debajo del primer umbral, cuánto entre umbrales y cuánto por encima.

**Por qué importa más que ninguna otra línea de la investigación.** Es el único
hallazgo donde la literatura es a la vez unánime (la mayor parte del volumen va
suave, en todos los modelos que funcionan) y **nosotros no tenemos nada que lo
impida**. Sin esa cuenta, nada evita que el plan se instale en el «agujero
negro» del entrenamiento: demasiado esfuerzo medio, poco suave y poco fuerte.

Y explica lo que viste sin recurrir a «la IA razona mal»: en una mini-semana de
tres días apareció un VO2max. El razonamiento citaba bien los datos y el techo
de carga se respetaba — lo que no existía era una frase que dijera *en este
bloque, el 80 % va suave*.

**Cómo se hace, con lo que ya bajamos de Garmin.**

1. Módulo nuevo `services/ai/intensity_distribution.py`. Para los últimos 28 y
   84 días, reparte los **minutos de cada tramo** (`procesar_lap` ya trae
   `averageHR_bpm` y `duration_s`) en tres cubos, usando **las zonas reales del
   atleta**: por debajo del techo de Z2 ≈ primer umbral, Z3 ≈ entre umbrales,
   Z4–Z5 ≈ por encima.
2. **Se abstiene y lo dice**: sin pulsaciones, sin zonas del reloj o con menos
   de N sesiones no devuelve nada. Y anuncia el método — un tramo que cruza dos
   zonas se cuenta por su media, y eso es una aproximación, no un test de
   lactato. (Misma disciplina que `weekly_load.py`.)
3. `SeasonPhase` gana **`intensity_distribution`** (`piramidal` /
   `polarizado` / `umbral_controlado`) con sus porcentajes objetivo. La fase lo
   declara; la semana lo recibe como restricción, junto al techo de carga.
4. El planificador de temporada recibe además **lo que el atleta ha estado
   haciendo de verdad** («78/16/6 — piramidal»), que es información que hoy no
   existe en ninguna parte del sistema.
5. En el validador, primero **lint** (como `number_audit.py`: se escribe en el
   log, no bloquea nada). Sólo cuando se le haya visto acertar durante unas
   semanas, pasa a recortar lo que se pase del reparto de la fase — y recortando
   lo suave o acortando la calidad, nunca inventando sesiones.

**Coste**: 2–3 días. **Riesgo**: bajo; nace como lint.

### F2 · Que las fases concentren de verdad (periodización en bloques)

**Qué falta.** Las fases existen, con tema y carga objetivo, pero nada les
impide desarrollar todo a la vez — que es exactamente la crítica que la
literatura hace a la periodización tradicional.

**Cómo.** `SeasonPhase` gana `focus` limitado a **una o dos** capacidades, y el
prompt dice lo que hoy no dice: *una fase que desarrolla todo no desarrolla
nada*, y cada fase declara qué **mantiene con dosis mínima** mientras concentra
en otra cosa (el efecto residual es el que sostiene el modelo por bloques).

**Coste**: horas, si va en el mismo pase que F1 (mismo esquema, mismo prompt).
**Riesgo**: bajo. Es quitar libertad, no añadir datos.

### F3 · La deriva cardíaca: la única prueba de que lo suave funciona

**Qué falta.** No existe. Y es la métrica que convierte ocho semanas de rodajes
aburridos en algo que el atleta **ve**.

**Cómo.** `services/ai/decoupling.py`: en cualquier sesión de 45 min o más hecha
en Z1–Z2, compara la relación ritmo:pulso de la primera mitad con la segunda. Se
abstiene en series, en cuestas (D+ por km por encima de un umbral), sin
pulsaciones y por debajo de 45 min — los mismos criterios de abstención que ya
usa `adherence.py` para no acusar a nadie en falso.

Sale un número por sesión larga y una tendencia en el macrociclo, y entra en el
informe de análisis y en el experto de fisiología: *«tu deriva bajó del 8 % al
4 % en ocho semanas: tu base aeróbica está mejorando»*. Hoy todo lo que el
atleta ve viene de Garmin; esto sería lo primero que le damos nosotros.

**Coste**: ~1 día. **Riesgo**: ninguno, es sólo lectura.

### F4 · El bucle diario — puerta falsa primero

**Qué falta.** Es el hueco de producto, no de ciencia. El plan es semanal y
estático. El check-in recoge Hooper y molestia todos los días y **no cambia nada
ese mismo día**; el HRV llega al prompt de la semana que ya se generó.

La investigación (Kiviniemi y las revisiones posteriores) apunta ahí, y su
conclusión honesta es que el efecto es **real pero modesto**, y depende mucho de
cómo se mida. Por eso no se construye a ciegas:

**Cómo.** En la tarjeta de la sesión del día: *«Hoy no me siento para esto»*.
Detrás, de momento, **nada mágico ni caro**: un ajuste determinista (cambiar la
calidad por suave, o acortar un porcentaje) propuesto a partir del estado de HRV
contra su base, el Hooper contra su base y la carga reciente. **Se propone, no
se aplica**: no reescribe el plan ni toca el reloj hasta que el atleta diga sí.

Eso respeta lo que `recovery_state.py` documentó a propósito (una señal ruidosa
no degrada una sesión por su cuenta) porque **decide la persona**, y a la vez
mide con el embudo si esto le importa a alguien antes de construir la versión
buena.

**Coste**: medio día la puerta. **Riesgo**: ninguno mientras no aplique nada.

---

## 3. Lo que NO haríamos, y por qué

| Propuesta | Por qué no |
|---|---|
| **LangGraph con `PostgresSaver`, `checkpoints`, `thread_id`** | Es la recomendación arquitectónica central de la investigación y **no aplica a esta app**. Nuestro grafo corre **una vez por generación** en un worker desacoplado, y la fuente de la verdad es MySQL, no el estado del grafo. La única espera dentro del grafo (las preguntas del coach) ya persiste en disco. Lo que esa sección señala de verdad —que un resultado no puede depender de que alguien esté mirando— era un fallo real y **ya está arreglado** (`27675f3`). |
| **Doble umbral / método noruego** | Exige lactato o proxies muy fiables, y volumen de profesional. Nuestros atletas entrenan 6–8 h/semana. Lo transferible (que la calidad vaya **controlada** y no a tope) ya está en los prompts. |
| **Estimar VO2max del FTP (Hawley & Noakes) y declive por edad** | Garmin nos da el VO2max **medido**. Sustituir un número medido por uno estimado es exactamente el fallo que este repositorio persigue. |
| **GAP para prescribir** | Ver §1: en desnivel se prescribe esfuerzo y D+, que es mejor. Corregir el ritmo para **leer** una sesión de cuestas es un extra pequeño, y hoy la adherencia ya se abstiene en cuestas en vez de acusar en falso. |
| **km-esfuerzo ITRA (1 km por cada 100 m de D+)** | El prompt de trail ya progresa el D+ aparte del volumen. Añadir una distancia virtual mezclaría dos magnitudes en el mismo número, que es cómo se pierde una temporada de series. |
| **Calentamiento de alta intensidad antes de competir** | Un hallazgo de un solo estudio con n pequeño, sobre relevos súper sprint. Se puede escribir como sugerencia en la estrategia de carrera; no como regla del planificador. |

---

## 4. Orden

Nada de esto entra antes del merge. Después:

| | Qué | Días |
|---|---|---|
| 1 | **F1 + F2** en un solo pase: el módulo de distribución, el esquema de la fase y los dos prompts. Verificar contra un prompt archivado de verdad, y generar una semana real para ver el reparto medido dentro del prompt. | 3–4 |
| 2 | **F3** deriva cardíaca, al informe y al experto de fisiología. | 1 |
| 3 | **F4** la puerta del bucle diario, con su embudo. | 0,5 |
| 4 | Subir el lint de F1 a recorte **sólo si las semanas medidas le dan la razón**. | — |

## 5. Cómo se verifica (y no vale «pasa las pruebas»)

- El reparto medido tiene que **aparecer en el prompt archivado**
  (`reasoning/<ISO>/prompts/*.json`), no en un log. Es la única prueba de que un
  dato llegó, y este repositorio ya se equivocó seis veces creyendo que llegaba.
- Un atleta **sin zonas del reloj** tiene que recibir cero tokens sobre
  distribución, igual que un atleta sano recibe cero tokens sobre lesiones.
- Una semana de afinamiento con un reparto `polarizado` y una semana base con
  `piramidal` tienen que salir **distintas**, y la diferencia tiene que poder
  leerse en el `week_rationale`.
- La deriva cardíaca se comprueba contra dos sesiones reales del atleta 2 con
  los números en el docstring, y **una sesión de cuestas tiene que salir sin
  número**, no con uno malo.
