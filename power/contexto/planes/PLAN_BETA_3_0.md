# Plan Beta 3.0 — control, ayuda, mover/editar, progreso y limpieza

> **Estado (2026-10-19): F0-F10 hechas.** De F10 quedan, por datos o por decisión: borrar las redes que no corrigen (≥20 generaciones de producción), predicciones propias desde un test de umbral real, y volver a medir las copias entre días del codificador tras relanzar. Detalle en `contexto/04`.
> Decisiones del responsable ya incorporadas abajo.
>
> **Lo que falta (2026-10-19, tras la prueba de punta a punta; todo lo de código, hecho y probado):**
> 1. **Decisiones del responsable**: tomadas el 2026-10-20 (Haiku 4.5; semanas gratis atadas a un Garmin
>    nuevo, 2 de prueba y 2 + 2 por invitación; la prudencia con la HRV se queda; **Q60 (8 USD) al mes, con
>    muro de pago al acabar las semanas gratis y el pago registrado a mano**, hecho). Lo que sigue: qué pide
>    Garmin a una app de pago (ver `ESTUDIO_LEAN_BETA_3_0.md`).
> 2. **Fase B, con el responsable** (`PLAN_DE_ACCION_BETA_2_0.md`, Fase B): fusionar `pruebas-locales` en
>    `main` conservando `0bb40c0`; respaldo; desplegar; retirar los entrenos de Garmin ANTES de borrar nada;
>    migraciones a mano (confirmar 025-027 y correr 028-036); rotar `BRIDGE_SERVICE_TOKEN` y la contraseña de la
>    base; cron horario de `semanas_automaticas.php`; `comparar_expediente.php --copiar` una vez; prueba de humo
>    (todos vuelven a aceptar el Legal 2026-10-11); vigilar las primeras 48 h.
> 3. **Con datos de producción** (después de relanzar): borrar las redes con 0 correcciones en ≥20
>    generaciones; repetir `medir_copias_entre_dias.py` y `banco_codificador.py`; comparar `pantalla_vista` de
>    Progreso; que las copias en MySQL (subidas y expediente) pasen a mandar; mirar en cPanel el log de PHP y que
>    se borren las carpetas de trabajos; cuántas semanas recibe una carrera B en el primer macrociclo real; S8.
> 4. **Con el primer test de umbral real**: predicciones de carrera propias desde el umbral medido.
>
> **Nombres (decisión del responsable, 2026-10-10):** ESTE plan es la **Beta 3.0**. Todo lo hecho antes es
> **Beta 2.x**; la fuerza S1-S8, que se llamó "Beta 3.0" mientras se hacía, salió en la Beta 2.1 y ya se llama así
> en el código y el contexto (2026-10-11). Este plan está enlazado desde `AGENTS.md` y `contexto/README.md`.

## Contexto

Hoy la app genera bien, pero el responsable no tiene control ni contexto. Cuatro carencias:

- **Sin contexto en los reportes.** Cuando Hugo o Fercho reportan algo, no llega con lo que Claude ve en la cuenta.
- **Sin visibilidad del servidor.** No hay forma de ver errores ni trabajos sin SSH. El `worker.log` muere a los 3 días o a los 30 trabajos.
- **Gasto sin freno.** Nada impide un "agujero negro" de gasto (16 planes en un día en la Beta 1). La decisión D2 sigue abierta.
- **Sin manera de cambiar un entreno.** El atleta sólo puede regenerar (~0,30 USD). Es la Fase C pendiente: mover, y ahora también editar.

Además, el responsable quiere:
- estadísticas tangibles y no de jerga;
- gamificación con crecimiento viral;
- Coach y Cuaderno juntos, con un "Reordenar";
- quitar lo descartado.

**Decisiones ya tomadas (2026-10-10):**
- **Orden:** control primero.
- **Editar:** ajustar pasos y añadir desde plantillas, 1 a 1 con Garmin y sin IA.
- **Mover:** sólo dentro de su semana, de hoy en adelante.
- **Estadísticas:** se quita la jerga al atleta y se queda VO2max.
- **D2:** 3 generaciones por semana y 1 macrociclo cada 7 días.
  - Los fallos no gastan cupo.
  - La semana automática está exenta.
  - Alerta al administrador por encima de 1 USD al día.
- **El planificador sabe lo que cambió el atleta.**
- **Racha semanal honesta:** cuenta la semana con ≥70 % de lo prescrito y ≤120 % de las horas. Se congela con una molestia o enfermedad declarada. Niveles por semanas constantes.
- **Limpieza:** sólo el Doctor, «Traer de mi reloj» y el código Vue confirmado muerto. No entran la CLI, el frontend viejo `web_app/static`, la tubería de «Rehacer» ni las columnas muertas.

## Reglas de todas las fases

- **Rama y fusión.** Todo en `pruebas-locales`. Cada fase se fusiona con `main` sólo cuando el responsable lo diga.
- **Migraciones tolerantes.**
  - Patrón `information_schema` + `PREPARE` (como la 024).
  - El código usa `COLS_OPCIONALES` o `disponible()`: sin la migración, la función no aparece y nada se rompe.
  - Cada migración entra en `ESPERADO` de `Backend/scripts/verificar_migraciones.php`.
- **Lógica en Python, PHP delgado.** Lo determinista va en `PaginaLocal` con unittest; PHP orquesta y guarda.
- **Rutas nuevas** en los dos lados, con `auditar_conexiones.py` en 0.
- **Comprobación por fase:**
  - pruebas de PaginaLocal y AiService;
  - ruff sin superar 21; vulture;
  - `npm run build`; `php -l`;
  - navegador con dev-login (1 Miguel, 2 José, 3 Jessie), en escritorio y a 375 px;
  - al cerrar, actualizar `contexto/04` (y `02`/`03` si cambia la arquitectura).
- **Fallos y textos.** Cada fallo encontrado deja una prueba con los números reales. Los costos sólo se muestran al administrador. Nada se borra en silencio: todo recorte se anuncia.
- **Dinero y Garmin.**
  - Toda generación o réplica pagada se anuncia y pide autorización antes.
  - Toda acción en un reloj Garmin real necesita permiso explícito para esa acción.

---

## F0 — Medir y limpieza segura (S)

1. **Medir qué pantallas se usan.**
   - Ruta `POST /telemetry/screen` (`#[Authorize(['admin','user'])]`) en `ExperimentController.php` o en un `TelemetryController.php` nuevo.
   - Lista cerrada con los `name` del router. Guarda con `EventRepository::record($uid,'pantalla_vista',…, $nombre)`.
   - Front: `router.afterEach` en `Frontend/src/router/index.js`, sin repetir la misma pantalla en 30 s.
   - En `MetricsAdminView.vue`, sección «Pantallas más vistas», con el patrón de `EventRepository::deleteReasons()`.
2. **Quitar «Traer de mi reloj»** (lectura automática de la fuerza):
   - Front: `services/fuerza.js::traerDeMiReloj` y en `PlanView.vue` el botón (~l.527), `traerDelReloj` (~l.1035) y su import.
   - PHP: `StrengthLogController::fromGarmin` y `PythonBridgeService::strengthFromGarmin`.
   - AiService: `/bridge/strength-from-garmin` y `StrengthFromGarminRequest`.
   - Cerebro:
     - `services/ai/fuerza_desde_garmin.py`;
     - en `server.py`: `_entreno_de_la_sesion`, `fuerza_del_reloj`, `fuerza_desde_garmin` y su llamada (~l.3830);
     - el parámetro `origen` de `workout_builder._strength_steps_from_block`/`_pasos_por_grupos` y la rama `'reloj'` de `paso_de_fuerza`;
     - `tests/test_fuerza_desde_el_reloj.py`.
   - **Se conservan** `athlete_strength_logs`, `/strength-log` y `ProgresoDeFuerza.vue`.
3. **Quitar el Doctor:**
   - Cerebro y AiService: `health_intake_agent.py`, `/bridge/health-intake` + `HealthIntakeRequest`.
   - PHP: `PythonBridgeService::healthIntake` y la línea `doctor_consulta` de `EventService::EVENTOS`.
   - En `server.py`: `/api/health-intake` y `DoctorConsultationModel`.
   - `printable_renderer.render_medical_report` y su plantilla, y `AgentRole.HEALTH_INTAKE` en `ai_settings.py`.
   - `'medical'` en `PlanController` (~l.260) y el `if` vacío de `ProfileView.vue` (~l.1668).
   - Las partes del Doctor en sus pruebas.
   - **Se conservan** `KIND_HEALTH_INTAKE` (líneas antiguas del libro de costos) y `health_context.py`.
4. **Quitar código Vue muerto:**
   - `ReportsModal.vue`, `WorkoutDetailModal.vue` y `views/public/Home.vue`.
   - Campos muertos de `appStore.js`: `isReportsOpen`, `selectedWorkout`, `weeklyWorkouts`, `macrocycle`, `macroHistory`, `adherence`, `sendToGarmin`, `toggleWorkoutStatus`, `uploadAllToGarmin`, `openWorkout`, `closeWorkout`.
   - `mockData.js` si queda sin uso.
   - Cada borrado se justifica con un `grep` previo sin resultados.
5. **Orden seguro:** front → PHP → AiService → cerebro. Al final, un `grep` sin restos y vulture.
6. **Prueba nueva:** `test_lo_retirado_no_vuelve.py`. Fija que no hay rol `health_intake`, que el informe `medical` da 400 y que la semana se genera sólo con el registro de fuerza manual.

## F1 — Panel «Salud de la app» y libro de redes (M)

1. **Rutas nuevas del AiService**, en `AiService/bridge/admin.py`, con `_check_token`:
   - `GET /bridge/admin/jobs`
     - Lee `_runtime/jobs` y `upload_jobs` con `bridge/jobs.py` (`job_dirs`, `read_status`).
     - Devuelve tipo, atleta, estado, error saneado, duración y posición en cola, y el costo cruzado con `PaginaLocal/core/usage_ledger.py`.
     - **Nunca** devuelve `spec.json`.
   - `GET /bridge/admin/job-log?job_id=&lines=200`
     - Final del `worker.log`, saneado: correos, `password`, `Bearer` y tokens.
     - Anuncia el recorte: «últimas 200 de N».
   - `GET /bridge/admin/health`: si el servicio está vivo, la cola, `max_concurrent()`, el tamaño de `_runtime` y del libro, y el commit.
   - `GET /bridge/admin/nets?days=30`: agregado del libro de redes.
2. **Libro de redes persistente** `PaginaLocal/core/redes_ledger.py`.
   - Sólo añade líneas, junto a `data/billing/usage.jsonl`.
   - `anotar(red, detalle)` se llama en cada red:
     - ritmo (`plan_validator._ritmo_como_dice_el_paso`);
     - duración (`_duracion_como_dice_el_paso`);
     - `carrera_hasta_pulsar_vuelta`;
     - el lint del macrociclo (`palabras_de_duda`);
     - las reparaciones pagadas y los respaldos.
   - Muestra «0 correcciones en N generaciones», que es la señal para borrar redes más tarde.
3. **PHP.** `MetricsController` gana:
   - `/admin/metrics/health`, `/jobs`, `/job-log/{id}` y `/nets`;
   - `/admin/metrics/php-errors`: el final del `error_log`, saneado y anunciado;
   - todas con `#[Authorize(['admin'])]`. `PythonBridgeService` gana los `get` con timeout de 5-10 s.
4. **Pantalla.** Pestaña «Salud de la app» en `MetricsAdminView.vue`, que se actualiza cada 30 s mientras está visible:
   - estado del servicio y la cola;
   - trabajos, con un clic para ver su log;
   - redes;
   - errores de proveedores (429/401/timeout);
   - errores de PHP;
   - errores del navegador (ver F3).
5. **Pruebas:**
   - `test_admin_salud.py`: el saneado con un log real, y que el recorte se anuncia.
   - `test_redes_ledger.py`: cada red anota. Cita «1 h 50 min» (commit e59636c).

## F2 — Tope D2 y alerta de gasto (M)

1. **Migración `athlete_generations`.**
   - Columnas: `user_id`, `kind` (season/week), `target_week`, `job_id`, `automatic`, `status` (started/ok/error), `error`, `created_at`, `finished_at`.
   - Índice `(user_id, kind, created_at)`.
2. **Dónde corta.** En `Backend/src/Services/PlanService.php::startSeason/startWeek`, **antes** de llamar al puente, con el mismo patrón de los códigos `busy`/`window`:
   - Semana: como mucho 3 filas `week`, no automáticas, con `status` en started/ok y la misma `target_week`.
   - Macrociclo: 1 cada 7 días.
   - Los fallos no gastan cupo.
   - `AutoWeekService` queda exento, pero sí cuenta en la alerta.
3. **Ciclo de cada fila.**
   - Se inserta cuando el puente responde ok.
   - `pollStatus` la cierra con ok o error, y corrige `target_week` con la primera fecha de la semana nueva.
   - Sin la tabla, no hay tope (queda en el `error_log`).
4. **Experiencia.**
   - PlanView: «Te quedan 2 versiones de esta semana» y «Macrociclo disponible el …». Nunca en dinero.
   - En el 2.º intento (y en «Borrar semana»), modal «¿Qué no encaja?»:
     - [Mover]/[Ajustar], cuando existan (F5-F6);
     - [Pedir ayuda] (F3);
     - [Generar otra (quedan N)].
5. **Alerta de gasto.**
   - `usage_ledger.gasto_por_dia()` → `/bridge/usage-alerts?threshold=1.0` → `/admin/metrics/alerts`.
   - Banner en Métricas y en Salud.
   - El cron de `semanas_automaticas.php` manda una notificación individual al administrador, una por atleta y día.
6. **Prueba:** `test_gasto_por_dia.py`, con líneas reales del libro. La parte de PHP se verifica con filas sembradas en el navegador, sin pagar.

## F3 — «Pedir ayuda» con una persona en el circuito (L)

1. **Migración.**
   - `athlete_feedback` gana `category`, `status` (nuevo/investigando/resuelto), `session_key` (fecha|franja), `diagnostic` (JSON, ≤64 KB), `triage`, `reply`, `replied_at`, `replied_by` y `updated_at`.
   - `notifications` gana `user_id NULL` (NULL = todos) y `feedback_id`.
   - `NotificationRepository::inboxFor` filtra por usuario, detectando si existe la columna.
2. **Lo que ve el atleta.**
   - `FeedbackButton.vue` pasa a «Ayuda / Opinar». Pedir ayuda tiene 5 categorías en lenguaje simple:
     - «Mi plan no tiene sentido»;
     - «Mi reloj no muestra lo mismo»;
     - «La app falla»;
     - «Mis datos o mi perfil»;
     - «Otra».
   - En la tarjeta de cada día: «¿Algo raro con esta sesión?», que adjunta la `session_key`.
   - Antes de enviar, el texto exacto de qué se adjunta. Nunca contraseñas.
   - «Mis consultas», con su estado y la respuesta (`GET /feedback/mine`).
3. **Diagnóstico, que arma el servidor con una lista blanca:**
   - **PHP:**
     - contraseña de Garmin guardada sí/no;
     - rejilla de deportes vacía;
     - carrera en día de descanso;
     - % de perfil completo;
     - migraciones que falten;
     - última fila de `athlete_generations`;
     - estado de la semana automática.
   - **AiService** `POST /bridge/support-bundle`, sin IA:
     - los últimos 3 trabajos;
     - la última traza de razonamiento;
     - para esa sesión: lo que decidió la IA (`week_intents.json`) contra los pasos codificados (`_minutos_de_los_pasos`) y lo que hay en el registro de subidas (si está desactualizado);
     - las redes que corrigieron esa semana;
     - la adherencia;
     - si tiene zonas de fábrica.
4. **Errores del navegador.**
   - `app.config.errorHandler`, `window.onerror` y `unhandledrejection` en `Frontend/src/main.js`.
   - Se guardan los últimos 10 en memoria y se adjuntan a la consulta.
   - Además, evento `error_cliente` (como mucho 1 por minuto) para el panel F1.
5. **Triaje automático** `services/ai/triaje.py`, que propone el origen probable:
   - **perfil:** hay señales rojas;
   - **garmin:** el registro está desactualizado o la subida dio error;
   - **codificación:** la decisión y lo codificado difieren en más de ±15 % de minutos, en el deporte o en la calidad;
   - **decisión de la IA:** «plan sin sentido» sin ninguna de las anteriores;
   - **app:** hubo errores recientes del navegador;
   - **desconocido**.
6. **Lo que ve el administrador.**
   - Rutas: `GET /admin/feedback?status=&category=`, `PATCH /admin/feedback/{id}` y `POST /admin/feedback/{id}/reply`.
   - La respuesta llega al atleta como notificación individual.
   - La cola tiene filtros y diagnóstico desplegable.
   - `loadNotifications` también al cambiar de pantalla (≤ cada 60 s) y al volver a la pestaña.
7. **Analizar una incidencia con Claude.**
   - `Backend/scripts/exportar_incidencia.php <id>` exporta la incidencia.
   - `PaginaLocal/scripts/analizar_incidencia.py` la cruza con los prompts archivados y propone la prueba de regresión.
   - Con permiso del responsable para cada incidencia.
8. **Legal.** Añadir a `Legal.php` el resumen técnico interno. El responsable decide si sube la `VERSION`.
9. **Pruebas:**
   - `test_diagnostico_de_ayuda.py`: la lista blanca no deja pasar `garmin_password`.
   - `test_triaje.py`, con la semana real de José del «8× · 8x50m».

## F4 — Estadísticas sin jerga (S-M)

1. **AnalyticsView** pasa a «Progreso». Lista blanca de tarjetas para el atleta:
   - `race_goal`, `pvr` (horas previstas y hechas), `quality` (entrenos clave hechos), `weekday`, `volume` (km y horas), `discipline`, `fitness` (VO2max) y `weight`.
   - Salen para el atleta: `load` (ACWR), `balance` (Load Focus), `hrv`, `stress`, `readiness` y `sleep_s`.
   - El administrador tiene el interruptor «Vista técnica».
   - `PaginaLocal/services/ai/charts.py::build_charts` no cambia: la IA sigue usando todo.
2. **DashboardView.** «Métricas avanzadas» pasa a «Tu progreso» (km y horas de la semana, VO2max y, tras F7, la racha).
3. **HealthModal.** Sólo VO2max (y el pulso en reposo si se decide); VFC y ACWR quedan para el administrador.
4. **Check-in.** Sin la palabra «Hooper»: «¿Cómo te sientes?». Las preguntas se mantienen.
5. **Medición.** Comparar `pantalla_vista` dos semanas antes y después.

## F5 — Registro de subidas sólido (M) (base de mover y editar)

1. **En el registro de archivo** `PaginaLocal/services/garmin/upload_ledger.py`:
   - `scheduled_date` en cada entrada; las antiguas se leen como `date`.
   - `desincronizada(entrada, día)`: compara la huella y la fecha programada.
   - `mover(entradas, {vieja: nueva})`: cambia la clave sin tocar `workout_id` ni `scheduled_date`. Así la siguiente subida reemplaza y borra el entreno de la fecha vieja. Si la subida falla, el id viejo nunca se pierde.
2. **Estado por sesión.**
   - `GET /bridge/garmin-sessions` → `GET /plan/garmin-sessions`.
   - Insignia en PlanView: «En tu reloj» / «Cambios sin enviar» / «Sin subir».
3. **Migración `athlete_garmin_uploads`.**
   - Copia del registro en MySQL (entra en los respaldos), con escritura doble mientras se comparan.
   - Después, PHP lo manda en cada llamada y `storage_adapter` escribe `garmin_uploads.json`.
4. **Prueba:** `test_registro_de_subidas_al_mover.py`. Intercambio martes ↔ jueves, una subida fallida, y el falso «Subido» con una huella que no mira la fecha.

## F6 — Mover dentro de la semana (M-L)

1. **Una sola ruta, `/bridge/plan-edit`** con `op` = `mover`/`ajustar`/`revertir`.
   - Llama a `PaginaLocal/services/ai/edicion_de_sesion.py`: puro, sin IA y sin Garmin.
   - Admite `dry_run` para enseñar el «antes → después» y los avisos.
2. **PHP** `PATCH /plan/session` → `PlanService::editSession`:
   - bloqueo 409 si hay una generación o subida en curso;
   - `plan_version` (sha1 del `weekly_plan`) para no pisar cambios;
   - `PlanRepository::patchWeeklyPlan`: leer, modificar y escribir en una transacción, como `deleteWeekByUserId`.
   - Red de seguridad en `pollStatus`: vuelve a aplicar las ediciones hechas durante una generación.
3. **Migración `athlete_plan_edits`.**
   - Guarda `original_day` (la sesión de la IA), `before_day`, `after_day`, `kind` y `reverted_at`.
   - Es la auditoría y la fuente de «Volver a la del coach».
   - `WorkoutDay` gana `edited: SkipJsonSchema[dict|None]` (`moved_from`, `sync`), que el modelo no ve.
4. **Reglas:**
   - Sólo en la semana generada actual, con origen y destino desde hoy.
   - Bloqueado: el día de carrera, el test de umbral y lo ya pasado.
   - Destino ocupado: intercambiar (por defecto) o doble sesión `am`/`pm`.
   - Destino de descanso: intercambio.
   - **Avisa pero permite:** un día no disponible según `restriccion_del_dia`, dos días seguidos de calidad, calidad la víspera de una carrera, piscina.
   - Se recalcula `day_name_es`. Los textos se mantienen, con la marca «Movida por ti desde el martes 13».
   - El registro de fuerza se re-fecha si existe.
5. **Garmin: borrar y volver a crear**, nunca reprogramar, porque el nombre lleva la fecha:
   - `run_garmin_upload_task(dates)` → `GarminUploadService.upload_day(day, zones, replaces=id_viejo)`;
   - después `anotar` con `scheduled_date`.
   - Sin contraseña: queda «pendiente de enviar» y aparece un botón «Enviar N cambios».
6. **El planificador sabe lo que cambió.** Bloque determinista nuevo en el prompt de la decisión semanal:
   - qué movió o ajustó el atleta la semana anterior, como preferencia observada;
   - verificado con **una réplica de nodo pagada (céntimos), anunciada antes**.
7. **Pruebas:**
   - `test_mover_sesion.py`: intercambio, doble sesión, descanso, carrera bloqueada, pasado bloqueado, `_next_week_start` intacto y aviso que no bloquea.
   - AiService: `test_plan_edit_bloqueado.py`.

## F7 — Editar: ajustar y añadir pasos, y volver a la del coach (L)

1. **Notación plana.**
   - `aplanar(steps)`, la inversa de `plan_outputs.expand_formatted_steps()` (l.1042), sobre `FormattedWorkoutStep`.
   - Un bloque que no cabe se edita «sólo en valores».
   - Al guardar, `expand` y `_check_shape_and_units`: un error de unidades bloquea.
2. **Qué se edita.**
   - Carrera, bici, caminata y natación: duración (`TiempoHMS.vue`, distancia, botón de vuelta), repeticiones, recuperación y objetivo (zona de FC, ritmo /km o /100 m, vatios, ninguno).
   - Borrar un paso.
   - Añadir desde plantillas.
   - Fuera de v1: fuerza y brick.
3. **Plantillas** `services/ai/plantillas_de_paso.py`.
   - Con valores del propio atleta: bandas de `summary.prescription` y `compute_zone_config`.
   - Sin prescripción, el objetivo es por FC; nunca un ritmo inventado.
4. **Descripción determinista** `services/ai/rendering/describir_paso.py`.
   - Reutiliza `printable_renderer.step_target`.
   - Sólo reescribe los pasos que cambiaron.
   - Contratos con prueba:
     - con `_work_description`, para no reabrir el «8× · 8x50m»;
     - con la red de horas: «1 h 50 min» son 6.600 s.
5. **Avisos que no bloquean**, de `_coaching_violations`:
   - calidad sin ritmo;
   - un ritmo incoherente con su umbral (José: 5:43 = 343 s/km);
   - series sin recuperación;
   - ±30 % de duración respecto a la versión de la IA.
   - Se recalculan `total_duration_minutes` con `_minutos_de_los_pasos` y `is_garmin_exportable`.
6. **Pantalla.**
   - Menú por tarjeta: «Mover» / «Ajustar» / «Volver a la del coach».
   - `components/forms/EditorDeSesion.vue` con vista previa (`dry_run`), «Guardar y enviar al reloj» y la clave con el patrón `claveGuardada`.
7. **Volver a la del coach.** `op: revertir` restaura `original_day` y marca `reverted_at`. Nunca borra.
8. **Pruebas:**
   - `test_ajustar_sesion.py`: aplanar y expandir sin perder nada.
   - `test_describir_paso.py`.
   - `test_plantillas_de_paso.py`.
9. **Reloj real** (sólo con permiso explícito, en la cuenta del responsable y en una semana de prueba): mover, intercambiar, ajustar 5×3' a 4×3' y volver a la del coach. Comprobar en el calendario de Garmin que no hay duplicados.

## F8 — Progreso, rachas, niveles, medallas y compartir (L)

1. **Cálculo** en `PaginaLocal/services/ai/progreso.py`, puro y sin IA.
   - Fuentes: `track_record.read_all()`, `strength_logs`, `checkins` y `marcadores.vo2max`.
   - Sale como `progreso` en `build_charts`.
   - Las medallas se derivan cada vez, sin tabla. «Ya vista» se guarda en `localStorage`.
2. **Racha semanal:**
   - Cuenta la semana con (completed+modified)/evaluadas ≥ 0,70 y `executed_hours` ≤ 1,20× lo prescrito.
   - Por encima del 120 %: ni suma ni rompe.
   - Con una molestia o enfermedad declarada: se congela.
   - Comparte la lógica con `embudo_del_bucle`, extraída a una función común.
3. **Niveles** por semanas constantes acumuladas: 1, 3, 6, 10, 16, 24, 36.
4. **Medallas:**
   - Constancia: primera semana, y 4, 8 y 12 seguidas.
   - Todas las de calidad de una semana.
   - Récords propios de la semana (km, horas, metros).
   - Distancia acumulada, sólo en los deportes que hace.
   - Fuerza: primera sesión, 10 sesiones, subió carga. Se porta a Python la lógica `subio`.
   - Check-in 4 semanas seguidas.
   - Test de umbral hecho.
   - Carrera completada.
   - VO2max +1 sobre el propio valor de hace 8 semanas.
5. **Pantallas.**
   - «Progreso»: racha, nivel, medallas (conseguidas y la siguiente), km y horas del mes, récords, VO2max y `ProgresoDeFuerza.vue`.
   - Dashboard: tarjeta «Tu racha».
6. **Compartir.**
   - Tarjeta en canvas en el navegador (1080×1350 y 1080×1920), con `navigator.share` o descarga.
   - Por defecto lo mínimo: sin nombre y sin VO2max.
   - Nunca peso, FC, sueño ni salud.
   - Evento `logro_compartido` para medir lo viral.
7. **Prueba:** `test_progreso.py`, con semanas reales del expediente:
   - una semana al 130 % no suma;
   - una molestia la congela;
   - un nadador no recibe medallas de carrera.

## F9 — Coach y Cuaderno juntos, y «Reordenar cuaderno» (M)

1. **Juntos.** `AiCoachModal.vue` con dos pestañas: «Conversación» y «Lo que sé de ti».
   - La segunda es `components/chat/CuadernoPanel.vue`, extraído de `CuadernoView.vue`.
   - `/admin/cuaderno` sigue existiendo.
   - Las anotaciones propuestas aparecen en el chat con «Guardar/Descartar».
   - `preguntas_del_coach.py` no se toca.
2. **Reordenar.**
   - Ruta: `POST /notebook/reorganize` → `/bridge/notebook-reorganize` → `notebook.proponer_reorden`.
   - Modelo: `AgentRole.NOTEBOOK` (flash-lite) con `with_structured_output`.
   - Acciones por entrada: mantener, reescribir, fusionar, pasar al perfil (lista cerrada de campos) o retirar por obsoleta.
   - Comprobaciones deterministas antes de enseñar la propuesta:
     - cada id aparece una sola vez;
     - ninguno queda sin acción;
     - no se inventan números ni fechas.
   - Costo: <0,001 USD, con su `kind` en el libro. **Se anuncia antes de probarlo.**
3. **Aplicar.** El atleta acepta uno a uno.
   - Las originales pasan a «retirada» con `retired_reason` y `replaced_by`. Nada se borra.
   - «Pasar al perfil» no escribe el perfil: dice dónde va, con el texto copiable.
   - Las «preferencias» conservan su categoría, porque alimentan al planificador.
4. **Migración.** `athlete_notebook.source` acepta `reorden` y gana `replaced_by`.
5. **Prueba:** `test_reordenar_cuaderno.py`, con el modelo simulado.

## F10 — Pendientes del cerebro, UX y fase C (M-L, después)

- **Del caso Jessie (A10/A11):**
  - una molestia leve contestada al coach que se amplifica: las respuestas pasan por el camino proporcional, sin tocar las preguntas;
  - una sola noche de HRV usada como motivo;
  - ritmo en cinta: pasa a FC o RPE;
  - «sin piscina» escrito como `limitacion`;
  - una carrera B llamada «carrera objetivo».
  - Cada cambio de prompt se verifica con una réplica de nodo **anunciada**.
- **UX:**
  - el perfil dice «Garmin 5:53 · el plan usa 5:43 (ajustado)»;
  - separar «Eliminar» de «Guardar»;
  - botones flotantes en móvil.
- **Fase C:**
  - borrar las redes con 0 correcciones en ≥20 generaciones (dato de F1), dejando una prueba del caso que cubrían;
  - predicciones propias desde el umbral medido;
  - codificar por día;
  - métrica de la réplica por unidades;
  - copia del expediente (`track_record.jsonl`) en MySQL.

## Operaciones en paralelo (no es código)

- Confirmar en producción las migraciones 025-027 (`verificar_migraciones.php`) y el cron horario de `semanas_automaticas.php`.
- Pagar una semana automática de punta a punta (~0,30 USD, anunciada).
- Verificar la unidad del peso de la fuerza en Garmin (lectura, con permiso).
- Rotar `BRIDGE_SERVICE_TOKEN` y la contraseña de la base.
- Decidir S8 con datos de la beta.

## Fuera de este plan (decisión del responsable)

CLI y `services/outside`; el frontend viejo `PaginaLocal/web_app/static` y las ~50 rutas `/api/*` de `server.py`; la tubería de «Rehacer semana»; columnas muertas (`ai_load_guardrail_enabled`, `coaching_tone`, `sleep_source`); la compatibilidad con la Beta 1.

## Decisiones que quedan para cada fase

- **F3:** ¿subir `Legal::VERSION`? ¿Cuánto tiempo se guardan los diagnósticos?
- **F4:** ¿se quedan el pulso en reposo, las horas de sueño y el peso?
- **F8:** ¿nombre visible en la tarjeta para compartir? ¿Código de invitación ya o después?
- **F9:** ¿`DELETE /notebook/{id}` pasa a retirar en vez de borrar?

## Verificación de conjunto

- **En cada fase:**
  - `cd PaginaLocal && python -m unittest discover -s tests` y `cd AiService && python -m pytest tests -q`;
  - `python -m ruff check .` (≤21) y `python -m vulture`;
  - `cd Frontend && npm.cmd run build` y `php -l` de lo tocado;
  - `verificar_migraciones.php` y `auditar_conexiones.py` (0 rutas rotas).
- **Navegador (dev-login):**
  - José y Jessie: el flujo nuevo, incluido guardar, en escritorio y a 375 px, sin errores en la consola.
  - Miguel: el panel de administración.
- **F2 (tope):** filas sembradas en `athlete_generations`, sin pagar.
- **F6-F7 (mover y editar):** pruebas puras primero. Luego, con permiso explícito, una prueba en el reloj real del responsable revisando su calendario de Garmin.
- **Lo pagado se anuncia antes:**
  - réplica del bloque «lo que cambió el atleta» (céntimos);
  - «Reordenar» (<0,001 USD);
  - semana automática de punta a punta (~0,30 USD).
- **Al cerrar cada fase:** `contexto/04` actualizado y push a `pruebas-locales`. La fusión con `main` sólo cuando el responsable lo diga.
