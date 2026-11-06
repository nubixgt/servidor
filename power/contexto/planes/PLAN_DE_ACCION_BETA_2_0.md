# Plan de acción Beta 2.0 — el único documento que manda

Creado el 2026-09-15. **Sustituye el orden de despliegue** de `contexto/planes/PLAN_LANZAMIENTO_BETA_2_0.md`,
`contexto/planes/PENDIENTE_ANTES_DEL_MERGE.md` y `contexto/planes/PLAN_1_2_Y_1_2_2.md`: esos tres se quedan como historia y contexto. Si
algo sale en el camino, se cambia AQUÍ.

Fuentes: la auditoría de bugs del 2026-09-14/15, las semanas finales de los atletas 2 y 3, y la
conversación y los PDF de Fernando (generados con la versión de `main`).

Leyenda: `[ ]` pendiente · `[x]` hecho y verificado en local · `[~]` hecho, falta verificarlo con una
generación real o en producción.

**Estado (2026-09-15, tarde):** la Fase A está hecha en `pruebas-locales`, con 1.979 pruebas en PaginaLocal y
73 en el AiService en verde, `ruff` en 21 (bajó uno), el frontend compila y el PHP pasa `php -l`. Falta B0.3
(generación real con el código final) antes de tocar producción.

---

## Fase A — Arreglar antes del merge

### A1. Codificador: la causa raíz de las duraciones equivocadas

Evidencia en `u3/reasoning/2026-09-14T12-53-47/prompts/008__weekly_planner__claude-haiku-4-5.json`:
el esquema sólo podía expresar la recuperación de una serie en **segundos** (`recovery_seconds`), y el
modelo metió ahí los "200 m" y los "25 m" que sabía que eran metros (lo escribió en `recovery_description`).

- [x] Esquema: la recuperación con su unidad (`recovery_duration_type` + `recovery_duration_value`);
      `recovery_seconds` se sigue leyendo en planes y réplicas viejas.
- [x] Prompt del codificador: sin unidad fija, ejemplo por distancia (carrera y natación), sin el `90″`.
- [x] Contrato de la decisión: cada serie con UN valor y su recuperación con unidad.
- [x] Menos ruido: historia de desarrollo fuera del esquema (−4.327 caracteres por llamada), catálogo de
      ejercicios sólo con fuerza, temperatura 0 en el codificador.
- [x] Réplica con Haiku sobre las decisiones archivadas: **0 unidades equivocadas** en las dos semanas
      (antes 4). José: 0 problemas en 3 réplicas idénticas.
- [x] Lo que quedaba: una decisión VIEJA ambigua ("3 progresivos de 15-20″", sin recuperación) Haiku la
      codifica como 60 s; la red la corrige a 18 s. Reescribir su prompt para ese caso EMPEORÓ otro, así que
      se revirtió. Con el contrato nuevo la decisión ya no la escribe así (comprobado en B0.3).
- `_duracion_como_dice_el_paso` se queda como red; si en dos semanas de beta corrige 0 veces, se borra.

### A2. Tiempo objetivo mal leído
- [x] "3:30" de maratón se leía como 3 min 30 s → "fuera de rango" y "ritmo que exige 0:05/km". El parser
      lee dos partes con la distancia; con los números reales de Fernando sale "alcanzable" a 4:59/km.
- [x] El perfil explica el formato debajo del campo.

### A3. Flujo de generación
- [x] A3.1 Un worker esperando respuestas cuenta para el tope de generaciones simultáneas.
- [x] A3.2 Un trabajo en cola sigue siendo "en curso" para su atleta (hasta 3 h) y la espera del frontend
      cuenta desde que arranca.
- [x] A3.3 El sondeo aguanta ~1 minuto de fallos (un reinicio del AiService) antes de rendirse.
- [x] A3.4 Sin contexto del atleta, el prompt ya no dice "None".
- [x] A3.5 "Prefiero no contestar" ya no dice "Planificando con tus respuestas".
- [x] Encontrado de paso: en `validate_weekly_plan` un bucle con la variable `date` tapaba el import
      (el F402 de ruff). Reventaba en cuanto se usaba `date` más abajo.

### A4. Lo que pidió Fernando
- [x] A4.1 Días no disponibles ESTA semana: casillas en el check-in, migración 022, regla en el contexto y
      `day_not_available` por fecha en el validador (que deja de exigir el FIJO de ese día).
- [x] A4.2 La adherencia empareja una sesión movida dentro de su semana (duración ±25 %, nunca la actividad de
      otro día).
- [x] A4.3 Fuerza: la decisión escribe ejercicios, series y carga por RIR/RPE; el codificador los copia; el
      cuaderno de "cómo entreno" es petición y gana al anclaje; la semana anterior lleva los ejercicios.
      Comprobado en B0.3. Falta verlo con un atleta que SÍ escribió su rutina en el cuaderno (Fernando, tras el
      relanzamiento).
- [~] A4.4 Zonas idénticas avisadas; ritmo de rodaje suave declarable (migración 022) que gana a la banda
      derivada; el umbral tecleado gana a Garmin (no el autorrellenado). La bici: pista para estimar el FTP
      desde los vatios de Z2, sin campo nuevo.

### A5. Seguridad y privacidad
- [x] A5.1 Sin token del puente por defecto en el código (en local sale del `.env` de la raíz). **Rotar en
      producción** (B4): el anterior está en el historial de git.
- [x] A5.2 `borrar_cuenta.php` dice también cómo borrar los `jobs` del atleta.
- [x] A5.3 La casilla de 18 años y del aviso de salud se exige en el servidor.

### A7. Segunda pasada (2026-09-15, noche)
- [x] **La semana de después de una carrera** (la de José sumaba ~8,4 h contra 7,1 tras su 5K): el código sólo
      conocía la semana ANTES de una carrera. `carrera_reciente` + `_nota_de_recuperacion` lo dicen en el bloque
      de carga y las horas no suben de una semana normal. A/B sobre su prompt REAL (mismo modelo, sólo cambian la
      nota y la regla): **505′ → 365′ (8,4 h → 6,1 h)**, sin calidad de carrera los dos primeros días. Queda para
      mirar en la beta: el viernes juntó VO2max corriendo y natación de umbral, y el racional dijo "~5,5 h"
      cuando sumaba 6,1.
- [x] **Tiempos en tres campos** (h, min, s) en tiempos objetivo y marcas personales; se guardan "HH:MM:SS".
      Verificado en el navegador: 3 h + 30 min → `03:30:00`.
- [x] **Auditoría de cables**: rutas 0 rotas; `week_as_expected` se guardaba y no llegaba a nadie (conectado,
      con prueba que fija los nombres en cada salto del check-in). `coaching_tone` y `sleep_source` no tienen
      control en ninguna pantalla ni cambian nada: campos muertos, no rotos.

### A8. Auditoría del cerebro nodo a nodo y los PDF de Hugo (2026-09-14)
Leídos línea a línea: grafo y estado, los 3 resumidores, los 3 expertos, preguntas del coach, orquestador,
síntesis, formateador, macrociclo, planificador semanal (decisión, codificador, reparación) y validador.
- [x] **Test de umbral** de extremo a extremo (Perfil → Avanzado, migración **023**): protocolos estándar, lectura
      de vueltas que se abstiene si la actividad no tiene forma de test, "Usar" + Guardar, y el cerebro lo
      reconoce con su fecha.
- [x] **Zonas de fábrica** reconocidas deporte a deporte (las de Hugo no eran idénticas y no se avisaba); la
      ficha y los expertos de actividad y métricas dicen que no son umbrales.
- [x] **Umbral estimado = ritmo de ~1 hora** entre predicciones (Hugo: 4:38 en vez del 4:27 de su 10K); la
      tarjeta del informe dice de dónde sale ("de tu Garmin" salía siempre) y "Volumen" en vez de "Carga".
- [x] **Metas de montaña**: con ≥20 m de D+ por km no se comparan con la predicción en llano (su UTG 21K de
      +1.500 m salía "hay margen para apuntar más alto").
- [x] Bugs de nodos: el orquestador con HITL apagado dejaba fuera el macrociclo; un resumidor caído escribía
      "None" en el prompt de su experto; `_ritmo_de_la_carrera` leía "3:30" de maratón como minutos; el día de
      carrera de respaldo era siempre a pie (una travesía salía corriendo); la recuperación que añade el
      respaldo llevaba Z1 y hacía pitar el reloj; la FC umbral de una importación vieja ganaba al Garmin de hoy.
- Visto en los PDF de Hugo y ya arreglado en la rama: guardarraíl de ACWR recortando la semana ("proteger el
  guardarraíl"), "objetivo de volumen fijado en **6.5h**", identificadores crudos (`tsb`, `acwr_uncoupled`) en el
  racional, "riesgo de lesión" con el ACWR, la ITBS amplificada en cada sesión.
- **Criterio a vigilar en la beta, no tocado:** a un atleta de 4-5 h de bici y tiradas de 21K con una A de
  montaña a 10 semanas, la semana 2 no le puso tirada larga ni rodaje de más de 90′ (en `main`, con el
  guardarraíl activo). Mirar en la primera semana real con la rama.

### A9. Revisión Beta 1 → Beta 2.0: errores nuestros y parches de errores de fondo (2026-09-14)
84 commits de `origin/main` a esta rama, leídos con una pregunta: ¿esto arregla la causa o tapa el síntoma?

**Arreglado de raíz ahora**
- [x] **Test de umbral**: la primera versión elegía entre las actividades recientes la que "pareciera" un test
      (en tu cuenta aceptó tus series). Ahora el test va al RELOJ con su calentamiento (15' + 10' + 20' + 10') y la
      app lee sólo la actividad hecha con ese entreno, por su `workoutId`; manda el más reciente y los anteriores
      quedan de historial. Subido a tu reloj para el **2026-09-25**, el día del "Tempo controlado".
- [x] **Adherencia**: emparejaba por fecha + deporte + duración con tres pasadas de tolerancia (±1 día, misma semana
      ±25 %). Garmin guarda en cada actividad el `workoutId` del entreno con el que se hizo: lo hecho con el entreno
      del reloj se empareja exacto y se dice ("con su entreno del reloj"). Las tolerancias quedan para quien entrena
      de memoria, que sigue contando igual.
- [x] **Contexto de la semana**: el nodo semanal reescribía con una expresión regular dos frases de `server.py`
      cuando la semana era de afinamiento. Ahora el contexto se escribe ya para su semana
      (`build_custom_planning_context(perfil, afinamiento)`).
- [x] **Migraciones sin registro**: la 018 se quedó sin aplicar en local y nada lo dijo, porque el código aguanta
      que falten. `php Backend/scripts/verificar_migraciones.php` mira lo que deja cada una; una prueba falla si
      se añade una migración sin comprobación. Se corre en B4.

**Parches que se quedan, con su porqué y cuándo se van**
- `_duracion_como_dice_el_paso` y `_ritmo_como_dice_el_paso` (red del codificador): 0 correcciones en los trabajos
  locales desde el arreglo del esquema. Se borran si en dos semanas de beta siguen en 0 (grep de B6).
- `_dias_escritos_como_texto` (la semana llega como texto JSON): la causa es codificar la semana entera en una
  llamada. Se va con "codificar por día" (Fase C).
- `sin_alfabetos_ajenos`: la síntesis mete palabras de otro alfabeto (el "稳定" del informe de Hugo). Causa: el
  modelo. Antes de cambiarlo, replay del nodo.
- `parse_time_to_seconds` leyendo dos partes con la distancia: la causa ya está arreglada en la entrada (tres
  campos). Tras el reinicio desde cero no hay tiempos viejos: se quita en la 1.2.2.
- Compatibilidades con datos de la Beta 1 que el reinicio BORRA: `DISTANCIAS_HEREDADAS`, la rejilla derivada de
  `day_time_blocks`, `recovery_seconds` legado, leer `sport="brick"`, repartos con formato anterior. No se tocan
  antes del merge; después de B4 son código muerto (1.2.2).
- `_espejo_ocupado` y el registro de subidas en `_runtime/` y no en MySQL: la causa es que el cerebro guarda en
  ficheros. El registro es lo único que dice qué entrenos del reloj son nuestros y NO está en la base: B1 respalda
  `_runtime/`; moverlo a MySQL es 1.2.2.

**Lo que hace Transition (transition.fun) y nosotros no** — inspiración, no lista de copia
- "¿Te faltó una sesión? Pregúntale a tu coach y reacomoda el resto de tu semana": su coach MUEVE la semana. El
  nuestro es de sólo lectura y rehacer paga una generación entera. Es la pieza más grande que falta (Fase C: mover
  y reacomodar sin regenerar).
- Predicciones de carrera "basadas en tus datos de entrenamiento": nosotros usamos las de Garmin, que salen del
  pulso y de las zonas (el sesgo visto con Hugo). Con el umbral medido por test, una predicción propia (Daniels /
  Riegel desde el umbral) sería más fiel (1.2.2).
- Cambiar interior/exterior por clima, nutrición por entreno, análisis de vídeo y control del rodillo: fuera de
  la beta. Precio de referencia: 139 USD/año.

### A10. Auditoría de la generación de Jessie (2026-09-15)
Macrociclo 06:22 (0,26 USD) y semana 06:36 (0,19 USD), 0 violaciones, sin errores en los `worker.log`.

**Arreglado**
- [x] **Abrir un día que su tabla no da** (Database/024, `open_dates`). Pidió en la nota su activación del
  sábado antes del 5K; el sábado es descanso en su tabla y la semana le dio "descanso completo como prefieres"
  y pasó la activación a una fuerza el jueves. El check-in enseña los días como su rejilla: un toque apaga un
  día que entrena y abre uno de descanso con sus deportes. La excepción va dentro de las reglas 1-3, en la nota
  de la tabla y sobre `preferred_rest_day`; el validador la acepta por fecha. Guardado de ida y vuelta
  verificado en el navegador con la atleta 3.
- [x] **La semana de cada carrera, al macrociclo** (`con_semana_del_macrociclo`). La fase específica de su
  trail B (+600 m, 21-nov, semana 10) empezaba en la semana 10: el modelo recibía fechas y contaba él.
- [x] **"Rango crónico óptimo 325-609"** era el rango de la carga AGUDA (0,8-1,5 x 406). El informe le dijo
  que su crónica estaba "dentro del rango óptimo… un nivel mínimo aceptable para competir".

**Comprobado y bien**
- Afinamiento del 5K (B, 10 días): los FIJO pasaron a POSIBLE en la tabla, en la regla 4 y en la nota de carga;
  la semana conservó sus dos fuerzas acortadas (lo permite, no lo exige) y bajó un 55 % (2,6 h contra 5,8,
  dentro del 40-60 % de la evidencia). El viernes que no podía es descanso. Ningún texto empuja a quitar ni a
  poner: no se toca.
- Umbral: el de Garmin (5:17) se descarta por más rápido que su 5K previsto (5:26); el estimado de ~1 h es 5:51,
  que es su ritmo de 10K previsto (58:25). La meta de 26:00 sale "fuera de rango" a 5 días y el macro lo dice.
- Las 7x400 del 09-09: la "repetición 12" del informe es la VUELTA 12 (la 5.ª serie, 153 s contra 118-136);
  el dato es real, la numeración confunde.

**Visto, sin tocar (para decidir)**
- **Aquiles amplificado.** Contestó "muy ligera molestia en el aquiles" a una pregunta del coach; el prompt de la
  semana lo nombra 22 veces (los expertos lo repiten) y la semana lo cita en todas las sesiones, también en la
  fuerza y en el descanso del viernes. La proporción al dolor declarado (`render_niggle_constraints`) sólo
  existe para la molestia del check-in con número; una respuesta al coach no pasa por ahí. Arreglarlo es tocar
  cómo llegan las respuestas a los expertos, no las preguntas.
- **Una noche de HRV como motivo.** El racional dice "un solo toque… porque hoy tu HRV fue 44", cuando el experto
  de fisiología pidió confirmarlo otra noche y la regla es persistencia más segunda señal. En afinamiento el
  toque único se sostenía solo.
- **Series con objetivo de ritmo en cinta.** El miércoles sugiere cinta "para máxima constancia" con 6x400 a
  4:55-5:16: en cinta el ritmo del reloj sale del acelerómetro (la de marzo marcó 32:40/km).
- El informe llama "carrera objetivo" al 5K, que es B.

### A11. Segunda vuelta sobre Jessie (2026-09-15)
- [x] **El check-in empieza en blanco en cada generación.** Al volver a generar traía la nota, el viernes cerrado
      y el sábado abierto de la semana anterior.
- [x] **"Saltar" es sin check-in** (`skip_checkin` de punta a punta). Antes la semana usaba el de los dos últimos
      días aunque no se contestara.
- [x] **El calendario enseña las fechas de la semana que se genera** (`utils/semana.js`, comparado con Python en
      9 casos con plan y 6 sin plan). Antes, 9 días desde hoy: un día fuera de la semana no hacía nada.
- [x] **Test de umbral: "sin parar"** en los dos pasos del reloj (10' + 20') y en el protocolo de la pantalla. El
      test ya subido al reloj del atleta 2 (2026-09-25) lleva el texto viejo: "Enviar el test a mi reloj" lo
      reemplaza, con su permiso.
- [x] **Macrociclo:** semana de cada carrera, la regla de que se prepara ANTES (sin número), aviso en el log si una
      fase empieza en su carrera, y la disponibilidad fuera de las `constraints`. Dos réplicas con su prompt
      real (gpt-5-mid, 0,06 USD cada una): sólo con la regla, la fase del trail pasó de 10-12 a 9-11 y siguió
      escribiendo "Sábado descanso total" como `limitacion`; con la regla y la frase de las `constraints`,
      trabajo específico en las semanas 6-10 con afinado corto en la 10, sin el sábado y sin avisos.
- [ ] **Para decidir:** cuántas semanas específicas recibe una carrera B varía entre réplicas (1 y 4 para el mismo
      trail). No se fija un número en el prompt (sería un sesgo); mirar el primer macrociclo real tras relanzar.
### A12. La semana de José antes de publicar (2026-09-15)
- [x] **La decisión se comprueba antes de codificar** y se le devuelve una vez si pone un deporte donde su
      disponibilidad no lo da (sus 6x400 del martes acabaron en un descanso titulado "5K pace"). Réplica sobre su
      decisión real: las movió al miércoles, 0 sesiones fuera, 0,04 USD.
- [x] **La red que convierte en descanso lo dice** en el día y en el racional.
- [x] **El nombre del día sale de la fecha**, nunca del texto del modelo, en el validador y en la comprobación.
- [x] **Preguntas del coach con los días de la semana** (preguntó por "el martes 16/09"); **el ejemplo de la tabla**
      ("quiere nadar el martes") ya no parece del atleta.
- [x] **AiService reiniciado en local**: corría desde antes del `skip_checkin` y no lo pasaba.
- [ ] La semana guardada de José sigue con el martes vacío: "Rehacer semana" la escribe con el código nuevo.

- [x] **Documentación reunida en `contexto/`** con `AGENTS.md` en la raíz (Antigravity) y los dos `CLAUDE.md` como
      punteros que la importan.

### A6. Visto en los PDF de Fernando y ya arreglado en la rama
"4 semanas desde", "zona óptima 0,8-1,3 / riesgo de lesión", "Z1–Z2" enviado como Z2, recuperaciones en
la pared con zona, "objetivo 8.0h". Se comprueba en la prueba de humo.

### Visto y no tocado
- `Frontend/src/components/modals/ReportsModal.vue` no lo importa nadie y lleva un texto de maqueta ("adherencia
  del 85 %… tendón de Aquiles derecho"). No se ve; se puede borrar en la 1.2.2.
- La semana de DESPUÉS de una carrera: resuelta en A7.

---

## Fase B — El merge (juntos, en este orden)

> Por qué este orden: el SQL de reinicio necesita la tabla de la 020 y las columnas de la 015, y la
> retirada de Garmin necesita el AiService NUEVO (el viejo ignora `todo` y no retiraría nada).

### B0. En local
1. [x] Suites (PaginaLocal 1.979, AiService 73), `ruff` 21, build del frontend, `php -l`.
2. [x] `auditar_conexiones.py` con 0 rotas (2026-09-15).
3. [x] Generación real de José y Jessie con el código final (semana del 21 al 27 de septiembre, trazas
       `u2/reasoning/2026-09-14T15-02-39` y `u3/reasoning/2026-09-14T15-01-44`): 0 violaciones, 0 correcciones
       de la red, 0 unidades equivocadas, temperatura 0 activa. La decisión ya escribe UN valor por duración con
       su recuperación («4x20″ progresivos con 60″ suave», «3x6′ … con 2′ de trote»), y la fuerza sale con
       ejercicios, series y RPE/RIR sin kilos inventados (la atleta 3 recibe un día de tren superior de verdad).
       $0,24 y $0,22.
4. [x] Migraciones en local (2026-09-15): la 022 falló la primera vez porque colgaba su columna `AFTER week_note`
       y la base local tampoco tenía la 021; se quitó esa dependencia y se corrieron las dos. 2026-09-14: la
       **018 tampoco estaba aplicada** (seguían `available_hours`, `strength_training_preference`,
       `brick_preference`; ningún código las nombra) y se corrió; la **023** también. Las 23 comprobadas contra
       `information_schema`. Falta guardar un check-in con días y un perfil con ritmo suave desde la pantalla.
5. [ ] `git fetch`, fast-forward de `main` local (va 58 commits atrás), `git worktree prune`.
6. [ ] `git merge-tree`: único conflicto esperado `Backend/src/Utils/JwtUtils.php`. En el árbol fusionado,
       `Backend/config/database.php` no puede llevar la contraseña de producción por defecto.

### B1. Producción: respaldo, sin atletas dentro
1. [ ] `mysqldump` completo y `tar` de `_runtime/`.
2. [ ] `JWT_SECRET` y `PYTHON_SERVICE_TOKEN` presentes en `backend/.env`.
3. [ ] ¿Corrieron 013-015? (no están en `main`).

### B2. Código
1. [ ] `git checkout main && git pull && git merge pruebas-locales`.
2. [ ] `JwtUtils.php` con `--theirs`, `add`, `commit`, `push`.
3. [ ] Workflow en verde; `/health` ok con los tres proveedores.
4. [ ] D3: `MAX_CONCURRENT_GENERATIONS=2` si se decide.

### B3. Retirar los entrenos de los relojes — con el código nuevo y ANTES de borrar nada
1. [ ] `php Backend/scripts/retirar_garmin_antes_del_reinicio.php` (lista) y revisar.
2. [ ] Lo mismo con `--ejecutar`.

### B4. Base y `_runtime`
1. [ ] Migraciones 013-015 si faltan, después 016 a 023 (comprobar la 018: en local no estaba aplicada).
2. [ ] `reinicio_beta_2_0.sql`, revisar el `SELECT`, `COMMIT`.
3. [ ] Vaciar `_runtime/{storage,jobs,upload_jobs,secure,respaldos}/*`.
4. [ ] Rotar la contraseña de la base y `BRIDGE_SERVICE_TOKEN` (en los dos `.env`); reiniciar el AiService.

### B5. Prueba de humo con tu cuenta (20-30 min)
1. [ ] Registro, puerta legal con la casilla, perfil, Garmin.
2. [ ] Check-in con un día no disponible; meta de maratón "3:30"; ritmo de rodaje suave.
3. [ ] Macrociclo y semana, contestando las preguntas.
4. [ ] `worker.log` sin errores.
5. [ ] Subida a Garmin sólo a tu reloj y con tu permiso.
6. Si falla: `git revert -m 1` + restaurar el respaldo.

### B6. Abrir y primeras 48 h
1. [ ] Mensaje a los atletas; aprobar rápido en `/admin/usuarios`.
2. [ ] Mañana y tarde: `grep -l ERROR */worker.log`, `grep -h "corregid[ao] para que diga" */worker.log`,
       `grep -l "no terminó en" */status.json`.
3. [ ] `free -m` con varias generaciones a la vez.

Decisiones abiertas (D1 hora, D2 tope por atleta, D3 simultáneas, D4 contraseñas de Garmin sin guardar,
D5 CORS): detalle en `contexto/planes/PLAN_LANZAMIENTO_BETA_2_0.md` §1.

---

## Fase C — Después del lanzamiento (1.2.2)
- Mover un entreno desde la app (backend + `/bridge/garmin-move` + ledger), verificando antes la
  reprogramación de Garmin en un reloj real.
- Codificar por día si B0.3 o la beta siguen viendo copias entre días.
- Métrica de la réplica que compare unidades del paso contra el texto de la decisión.
- Aguas abiertas (C2), trazas en Windows y `sandbox` (C3); la puerta falsa del día de carrera no se toca.
- Criterio: alerta de ritmo en el paso de carrera; objetivo Z1 en quien trota en Z2; molestias ya
  pasadas que siguen como contingencia; campo de vatios de Z2 si el FTP estimado no basta.

---

## Beta 2.1 — Fuerza de verdad (desde 2026-09-17)

Plan completo: rutina propia pegada como texto (base que el coach puede mejorar con motivo), coach con objetivo
(hipertrofia, rendimiento, prevención), sin responder = idéntico a hoy, y superseries al reloj como rondas.
Lo que motivó el plan: Fernando (rutina Upper/Lower con superseries de 2-3), Jessie ("muy básicos"), Hugo (le gusta).
En las 8 semanas archivadas de José y Jessie la fuerza fue siempre mancuernas y bandas a RIR 3-4, los ejercicios se
barajaban en cada regeneración y las superseries se aplanaban porque el esquema no podía expresarlas.

### S0. Rúbrica para juzgar una sesión de fuerza (antes de cualquier réplica)
Se juzga sobre semanas NORMALES, no sólo de carrera (las 8 archivadas eran de carrera o de después).
1. Encaja con su equipo y su objetivo declarados (sin barra para quien entrena en casa; con cargas reales para
   hipertrofia; sin empujar a pesado a quien pidió prevención).
2. Las superseries de su rutina siguen juntas, con su descanso entre rondas.
3. Mismos ejercicios entre regeneraciones de la misma semana y dentro de un bloque; la progresión va en dosis.
4. Cada cambio a su rutina trae un motivo escrito.
5. En afinamiento o recuperación la dosis baja; nunca sube por el objetivo.
6. 0 kilos inventados (sólo los que dio el atleta; si no, RIR o RPE).
7. Costo por semana medido contra los ~0,20 USD actuales. Cada réplica se avisa antes de gastar.

### S1. El plan puede expresar una superserie — [x] 2026-09-17
- [x] `StrengthGroup` y `StrengthBlock.groups` (fuera del esquema que ven el codificador y la reparación).
      **Comprobado el 2026-09-17 contra el commit anterior a la Beta 2.1**: `FormattedWeekBlock` 12.593,
      `WeeklyPlanPayload` 13.680, `StrengthBlock` 3.457, `WeekIntentContent` 5.573 y `TrainingDecisionNoHitl`
      7.215 caracteres, idénticos byte a byte. Las cifras que decía antes esta línea (12.098 y 13.350) estaban
      mal medidas: no describían ningún cambio. Con grupos, `exercises` es su aplanado; un plan viejo se lee como un
      grupo por ejercicio (`grupos_de_fuerza`).
- [x] Lo leen el chat del coach, el plan anterior de la semana siguiente, la hoja impresa y `PlanView` (A1/A2,
      "3 seguidos", descanso entre rondas; el plan viejo se ve como antes, ahora con su descanso).
- [x] `tests/test_superseries_de_fernando.py` con su Upper A real; navegador con una semana de prueba en memoria.

### S2. La superserie llega al reloj como una ronda — [x] 2026-09-17
- [x] `workout_builder._pasos_por_grupos`: un grupo de repetición por superserie con sus ejercicios dentro, descanso
      entre ejercicios (no tras el último) y de ronda al cerrarla. Series desiguales se parten
      (`_rondas_del_grupo`: 4, 4 y 3 → 3×los tres + la cuarta suelta). Un plan sin grupos sube igual que antes.
- [x] **Arreglado de paso**: un ejercicio de UNA serie con descanso tumbaba la subida (`WorkoutStep` no admite
      una repetición de 1); ahora va suelto.
- [x] Tope de Garmin (50 pasos, cuenta prudente con grupos): por encima, al reloj va el bloque de tiempo y el
      desglose sigue en la app; se avisa en el log.
- [x] Vocabulario de gimnasio verificado en la cuenta de José con su permiso: entreno 1701096031 (20 de 20 nombres
      con categoría, borrado después); 5 categorías nuevas (LATERAL_RAISE, CRUNCH, FLYE, LEG_CURL, HIP_STABILITY).
      Garmin NO tiene extensión de cuádriceps ni abductor/aductor de máquina: van sin nombre.
- [x] Upper A de Fernando armada por el constructor, subida a su reloj (1701100942, 18/09) y leída de vuelta
      idéntica. José la revisó (2026-09-17) y la dio por buena, con la cuarta serie de press y elevaciones suelta.
      Garmin también guarda una repetición de 1 (verificación 1701118922, borrada) por si algún día se prefiere
      enmarcarla como ronda.
- El catálogo de IMÁGENES no crece: cada entrada exige su dibujo, y sin él se abstiene (el nombre se ve igual).

### S3. El perfil guarda cómo entrena la fuerza — [x] 2026-09-17
- [x] `Database/025_perfil_de_fuerza.sql`: `athlete_profiles.strength_profile` y `strength_routine` (JSON, NULL =
      no contestó). En `ProfileFields::JSON_COLS`, `verificar_migraciones.php`, `UserProfileModel`, `profile.js`.
- [x] `services/ai/perfil_de_fuerza.py`, la única lectura: modo `rutina|coach` (otro valor = no contestó), objetivo,
      equipo, estructura, ejercicios seguidos (1-3), minutos (15-180), levantamientos con barra que ya hace con buena
      técnica; lo que no reconoce se descarta campo a campo. Una rutina sólo cuenta con `confirmed_at` y sesiones.
      Espejo en `Frontend/src/utils/fuerza.js`; `tests/test_perfil_de_fuerza.py` fija que coinciden y que las dos
      columnas cruzan todas las capas.
- [x] Sección "Tu fuerza" en Perfil → Objetivos y días (`components/forms/PerfilDeFuerza.vue`): "Como hasta ahora",
      "Tengo mi rutina" (objetivo, equipo, barras y el texto pegado) y "Que el coach me arme una" (además estructura,
      ejercicios seguidos y minutos). Cambiar el texto desconfirma la rutina; cambiar de modo no borra el texto.
- [x] Navegador con la cuenta local de José: SIN la 025 guarda igual (PUT 200, nada se pierde); con la 025 guarda,
      recarga idéntico, y volver a "Como hasta ahora" deja `strength_profile` en NULL y el texto guardado. El resto
      de la fila, igual. Datos de prueba retirados después.
- **Nadie lo lee todavía** (lo usa S5): hasta entonces contestar no cambia ningún plan, y `vulture` lo marca.
### S4. Pegar la rutina, ordenarla y confirmarla — [x] 2026-09-17
- [x] `services/ai/rutina_de_fuerza.py`: el modelo sólo LEE (rol nuevo `STRENGTH_ROUTINE_READER`,
      gemini-3.1-flash-lite en los cuatro tiers) con las líneas numeradas, y copia nombres, series y
      repeticiones como están escritos. No corrige ni mejora: eso es del coach semanal, con motivo (S5).
- [x] El CÓDIGO comprueba y pregunta en vez de arreglar en silencio: líneas que nadie usó, un nombre que no
      está en su línea, unas series que no están, una letra pegada a las repeticiones ("4×15R", UNA pregunta
      para toda la rutina), y sin descanso escrito lo dice. Una duda que nombra un ejercicio le quita su
      nombre de Garmin y pasa a ser suya.
- [x] Lo que el texto casi nunca dice se PREGUNTA en el formulario, no lo adivina el modelo: cómo se agrupan
      los ejercicios de una línea y el descanso entre rondas. Con "lo dice mi texto", si el modelo junta sin
      marca, el código lo pregunta.
- [x] Ruta `POST /profile/strength-routine` (puerta legal como el chat) → `/bridge/strength-routine`. No
      guarda nada: la rutina se guarda con el perfil al CONFIRMARLA. Gasto en el libro como
      `strength_routine` ("Rutina de fuerza"), sólo para el administrador.
- [x] Pantalla de revisión (`components/forms/RevisarRutina.vue`): sesiones, grupos A/B/C con sus rondas,
      A1/A2 con series, repeticiones y carga, separar y unir grupos, quitar ejercicios, y el nombre con el que
      lo verá el reloj. No se confirma hasta marcar cada duda como revisada. Cambiar el nombre de un ejercicio
      le quita el nombre de Garmin y la imagen del viejo.
- [x] Medido con el texto EXACTO de Fernando (4 lecturas reales): **0,0039-0,0047 USD y ~10-11 s** cada una,
      4 sesiones, 13 grupos, 33 ejercicios, 0 líneas sin leer, y siempre preguntando por la «R» y por «Geelos».
      Fixture en `PaginaLocal/tests/fixtures/`.
- [x] Flujo entero en el navegador con la cuenta local de José: pegar → ordenar → corregir «Geelos» y las «R»
      → confirmar → se guarda y el lector de Python la ve confirmada; recargar la conserva; "Revisar y editar"
      no vuelve a llamar al modelo; cambiar el texto y guardar la desconfirma y vacía las sesiones. Datos de
      prueba retirados después.
- **Un cambio en `AiService/main.py` o `bridge/` exige reiniciar el AiService** (aquí costó una prueba).
### S5. La semana usa SU rutina, con cambios y motivo — [x] 2026-09-17
- [x] `nodes/paso_de_fuerza.py`. El reparto: **el código elige qué sesión toca** (rotación que continúa donde
      la dejó la semana anterior, por `StrengthBlock.routine_session_id`, que va fuera del esquema del modelo);
      **el modelo sólo propone CAMBIOS** con su motivo y una dosis que baja y nunca sube; **el código construye
      el bloque** con los ejercicios que el atleta confirmó.
- [x] Lo que no se da por bueno: un cambio SIN motivo, un ejercicio que no es de esa sesión, una sesión que no
      está en su programa y una dosis desconocida. Las series que pide el paso no se recortan además por la
      dosis (no se recorta dos veces) y nunca pasan de las del programa. Un ejercicio sustituido pierde el
      nombre de Garmin del viejo. Si el paso falla, la semana sigue con su rutina tal cual.
- [x] La fuerza que NO se hace (`dose: ninguna`) se dice en el día ("Aquí iba «Upper A», pero…"), no desaparece.
- [x] Los días de su rutina SALEN del prompt del codificador: **7.128 caracteres menos** medidos en la semana de
      prueba (catálogo de Garmin, catálogo de imágenes y reglas de series que ya no hacen falta). El resumen de
      su rutina en la decisión cuesta 401.
- [x] Los bloques se REPONEN después del validador y su reparación, desde una copia: el tier-2 reescribe la
      semana entera y no conoce su programa. Un día que el validador dejó en descanso no resucita.
- [x] Sin rutina confirmada nada de esto corre: el prompt de la decisión no cambia ni un carácter y el
      codificador sigue escribiendo la fuerza (Hugo). Prueba de integración del nodo entero con los modelos
      fuera, más 34 pruebas en `tests/test_la_fuerza_de_su_rutina.py`.
- [x] Una llamada REAL del paso (gpt-5-mid, 2026-09-17, rutina de Fernando + semana con series el martes y
      tirada larga el domingo + molestia de hombro 2/10 declarada): **0,0122 USD y 33 s**. Bajó la dosis del
      lunes por las series del martes, cambió el press militar por press inclinado con agarre neutro y las
      elevaciones laterales por face pull SÓLO por el hombro declarado, y no inventó ni un kilo (RIR).
      De ahí salió el arreglo del doble recorte.
- [ ] **Pendiente de pagar**: una generación real de semana en la cuenta de prueba (~0,20-0,30 USD) y las
      réplicas A/B de la rúbrica. Se hacen con el responsable delante.
### S6. El coach escribe el programa, y el atleta lo confirma — [x] 2026-09-17
- [x] `services/ai/programa_del_coach.py` + `POST /profile/strength-program` → `/bridge/strength-program`.
      Se escribe UNA vez por bloque, con lo que el atleta DECLARÓ (objetivo, equipo, reparto, ejercicios
      seguidos, minutos, barras que ya hace, molestias) y sus días de Fuerza; se revisa y se confirma en la
      MISMA pantalla que una rutina pegada, y desde ahí la semana lo usa por el camino de S5.
- [x] Sin números de criterio nuestros en el prompt: ni series por grupo muscular, ni un RIR objetivo, ni
      cuántas sesiones "tocan". Cuántas sesiones se piden sale de sus días (par si eligió torso/pierna).
- [x] Lo que NO cuadra con lo que declaró se pregunta en la revisión: grupos con más ejercicios seguidos de
      los que pidió, y un levantamiento con barra que no marcó como suyo (esa duda va en SU ejercicio).
      Nombre de Garmin sólo si está verificado; imagen sólo por alias exacto.
- [x] Lo que el coach explica del programa entero (la rotación, cómo progresar) **se guarda**, no sólo se lee
      al revisar, y el porqué de cada sesión viaja con ella. Salió de la primera prueba real, donde se perdía.
- [x] LINT en el log del worker: semana de afinamiento o de vuelta de carrera con toda la fuerza a dosis
      normal. No se recorta en código: cuánto acompaña la fuerza a una puesta a punto es criterio.
- [x] **Arreglado de paso**: `HttpClient::postJson` sube el tope de ejecución de PHP al de la llamada que va a
      esperar. El programa tardó 60 s, PHP mató la petición a los 60 (`max_execution_time`) y el atleta vio un
      error de una llamada que el servicio había contestado bien. Afectaba a cualquier ruta del puente que
      pase del minuto, el chat del coach incluido.
- [x] Probado en la cuenta local de José, dos llamadas reales (gpt-5-mid, ~60 s cada una): pidió "torso y
      pierna, 2 seguidos, 45 min, gimnasio, rendimiento" con 3 días de Fuerza en su tabla y salieron 2
      sesiones de 5 grupos de 2, con el porqué de cada una y la rotación explicada. Confirmado y guardado.
- [ ] Pendiente de pagar: la semana real de punta a punta (su perfil local no tiene macrociclo, así que
      hacen falta temporada + semana).
### S7. El macrociclo sabe para qué hace gimnasio — [x] 2026-09-17
- [x] Al planificador de temporada le llega lo que el atleta DECLARÓ (objetivo con sus palabras, equipo,
      barras que ya hace, y si tiene programa confirmado y de quién), como una PETICIÓN suya, igual que su
      tabla de días. `render_para_el_macrociclo` en `services/ai/perfil_de_fuerza.py`.
- [x] `SeasonPhase.strength_quality`, opcional y sin vocabulario cerrado: qué busca la fuerza en esa fase,
      con las palabras del atleta. Un macrociclo guardado antes sigue validando y quien no contestó no recibe
      ni una palabra de gimnasio.
- [x] Y la fase se lo dice a la SEMANA: `compute_mesocycle_position` lo trae y el bloque de fase lo escribe,
      así que llega a la decisión y al paso de fuerza. Sin ese campo no se dice nada (macrociclos de antes).
- [x] Sin perfil de fuerza el prompt del macrociclo es idéntico byte a byte (prueba).

### S5.1-S6.1. Lo que el responsable vio al probarlo (2026-09-17) — [x]
Probó su rutina pegada (Fernando) y el programa del coach (Jessie) y encontró dos huecos de diseño y un fallo
de producto. Los tres están cerrados:

- **La IA se había quedado sin rango.** El paso sólo podía cambiar lo que ya estaba: con una molestia de
  aquiles declarada no podía meter su trabajo. Ahora puede **AÑADIR** 1-2 ejercicios (`additions`), con motivo,
  de la lista cerrada, **sólo si hay algo declarado que lo pida** (`puede_anadir`: molestia o lo que busque la
  fase), marcados en el bloque y **sin tocar el programa confirmado**. Si escribe la clave del catálogo donde
  iba el nombre ("arco_corto"), el código lo enseña como "Arco corto".
- **La rotación era ciega al calendario.** A una triatleta con 3 días fijos le tocaba pierna un lunes y torso
  el siguiente, por turno, sin mirar que el lunes lleva calidad. Ahora cada sesión lleva su **énfasis**
  (`pierna|torso|completo|core`, lo escribe el coach, se lee de una rutina pegada y se corrige en la revisión)
  y `colocar_las_sesiones` elige el día: la que carga piernas se aleja de la calidad y de la tirada larga, no
  se repite el mismo énfasis en 48 h, y entre las que caben se usa la menos hecha (adiós al contador).
  **Si no cabe lejos se coloca igual en el mejor sitio y se le DICE al atleta** -- en su día y en el racional
  de la semana --, con la recomendación de marcar otro día de gimnasio y el porqué. Lo decidió el responsable:
  quitarle su sesión de pierna porque no tenemos dónde ponerla sería decidir por él.
- **El número de sesiones ya no se fuerza a par**: con torso/pierna y 3 días salían 2 sesiones alternándose,
  que es lo que producía el patrón de arriba. Con 3 días, 3 sesiones (pierna / torso / completo).
- **Las casillas de la revisión eran una puerta y ahora son comentarios**: marcar una casilla por duda para
  poder confirmar era pedirle al atleta que validara nuestro código.
- Medido con una llamada real (gpt-5-mid, 0,0121 USD, 23 s): con aquiles 3/10 declarado colocó la pierna el
  jueves (lejos de las series del martes y de la tirada larga del domingo), bajó su dosis, y añadió arco corto
  y dorsiflexión con banda **evitando a propósito las elevaciones de talón**, con el motivo escrito.
- **De dónde sale el criterio** (investigado antes de escribirlo): Runna coloca la fuerza alrededor de las
  sesiones clave (pierna pesada en días fáciles, fuerza antes de correr con ~6 h, y core/torso si va después);
  la revisión de entrenamiento concurrente de 2024 pide 6-24 h de separación y recuerda que la economía de
  carrera cae ~8 h tras fuerza de tren inferior; Fitbod no usa rotación sino frescura por grupo muscular
  (48-72 h) con sustitución libre de ejercicios.

### S9. Registrar lo que levanta, y progresar con ello — [x] 2026-09-17
Sale de comparar la app con lo que ya existe (Hevy, Strong, Fitbod, Juggernaut, Runna): lo primero que
tienen todas y esta app no tenía era **saber qué se hizo en el gimnasio**.

- [x] `Database/026_registro_de_fuerza.sql` (`athlete_strength_logs`): una fila por sesión (día + franja) con
      sus ejercicios, series, repeticiones, kilos y RPE, y el `exercise_id` del programa para poder seguir el
      MISMO ejercicio semana a semana. Tolera que la migración no esté (`disponible()`), y su comprobación
      está en `verificar_migraciones.php`.
- [x] `GET/POST/DELETE /strength-log` + `StrengthLogRepository`. Los kilos los escribe el ATLETA: es el único
      sitio de la app donde un peso es un dato y no una suposición.
- [x] En la pantalla del plan: "Registrar lo que hiciste" bajo cada sesión de fuerza, con lo prescrito ya
      propuesto para corregirlo, y **"La última vez: 62,5 kg · 4×8"** debajo de cada ejercicio.
- [x] **La progresión**: lo registrado viaja con la semana (`strength_logs` → spec → `availability_constraints`)
      y llega al paso de fuerza, que puede proponer la carga partiendo de ESE número, con su motivo. Sin
      registro no se dice nada y todo va como antes.
- [x] **Periodización de la fuerza** en el macrociclo (`COMO_SE_PERIODIZA`): adaptación → cargas altas →
      mantenimiento cerca de competir, con su evidencia (Blagrove 2018, Schumann 2022) y **sin fijar semanas
      ni sesiones**; el día lo sigue eligiendo la semana.
- [x] Probado contra el backend real (2026-09-17): POST guarda, GET devuelve, DELETE borra y un cuerpo sin
      ejercicios da 422. Dos fallos que salieron de probarlo: la clave ajena pedía `INT UNSIGNED` (errno 150,
      la tabla no se creaba) y la ventana por defecto del GET acababa HOY, así que una sesión de la semana en
      curso se guardaba y no volvía en la lista.

### S10. Auditoría de regresión de toda la fuerza — [x] 2026-09-17
Para probar que añadir la fuerza no rompió lo demás, y no sólo afirmarlo:

- [x] **Los esquemas que viajan a los modelos son IDÉNTICOS** a los de antes de la Beta 2.1 (se compararon
      los cinco contra el commit `b01f987`, byte a byte).
- [x] **Los tres prompts de un atleta sin fuerza declarada son idénticos** a los de entonces: decisión
      semanal, codificador y macrociclo.
- [x] **Y ahí salió un fallo real**: el hueco de `{fuerza_declarada}` estaba en su propia línea, así que el
      macrociclo de quien NO contestó llevaba un salto de línea de más. Un carácter, pero la garantía era
      "idéntico". Arreglado y con prueba (`test_EL_FALLO_ni_una_linea_en_blanco_de_mas`).
- [x] El plan REAL guardado del atleta 2 (8 días) valida con los esquemas nuevos, construye sus 6 entrenos
      de Garmin, y se renderiza en la hoja impresa (31.413 caracteres) y en el chat del coach (7.464).
- [x] Prueba de humo del backend: `/profile`, `/plan`, `/checkin/latest`, `/notebook`, `/notifications`,
      `/plan/generate-status`, `/strength-log`, `/admin/metrics` y `/usage` devuelven 200; sin token, 401.
- [x] AiService importa con sus 21 rutas de puente; 2.228 pruebas en PaginaLocal y 75 en AiService; `ruff` 21
      de línea base y `vulture` 198 (por debajo de las 209 de ayer); migraciones al día hasta la 026.

### S8. Lo que queda para decidir con datos de la beta
- **¿Y los que no contestan?** Hugo está bien como está, y el camino viejo no se toca. Si al cabo de unas
  semanas los atletas con programa reciben mejor fuerza, se decide si se les propone a los demás. Necesita
  datos de uso, no código.
- **Cargas reales desde Garmin: MEDIDO (2026-09-17, cuenta de José, actividad 24371594337).**
  `get_activity_exercise_sets` devuelve las series de verdad: repeticiones (12, 12, 7, 10), descansos reales
  (120 s, 677 s, 444 s, 245 s) y `setType` ACTIVE/REST. Lo que NO devuelve fiable es QUÉ ejercicio: dos series
  salieron `UNKNOWN` al 99,6 % y las otras como probabilidad (BURPEE 53,9 %, PUSH_UP 50,8 % con
  BENCH_DIP 28,9 % detrás). `weight` llega a 0.0: no registra kilos.
  → Se pueden leer repeticiones y descansos; el NOMBRE del ejercicio no se puede dar por bueno (misma regla
  que las imágenes: abstenerse antes que acertar por suerte). `wktStepIndex` vino a `null` porque esa sesión
  no salió de un entreno del reloj; con los nuestros podría emparejarse serie a serie, y eso queda por
  comprobar cuando haga una sesión desde la app.
  → **COMPROBADO Y HECHO (2026-09-23).** La actividad 24309326801 ("Full body - 2026-09-10"), hecha con el
  entreno de la app, trae `wktStepIndex` en cada serie y el ejercicio al 99,6 %: se empareja exacto
  (`services/ai/fuerza_desde_garmin.py`, ver 02). Botón "Traer de mi reloj" y lectura automática en la
  semana cuando anotó kilos. Pendiente: verificar la unidad de `weight` con la primera serie real con peso.
