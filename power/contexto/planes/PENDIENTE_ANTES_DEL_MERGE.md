# Antes de subir a `main` (auditoría del 2026-09-11)

> **Historia y contexto.** Lo pendiente y el orden del merge viven desde el 2026-09-15 en
> `contexto/planes/PLAN_DE_ACCION_BETA_2_0.md`; si algo de aquí lo contradice, manda aquel.

Todo lo de abajo está **pendiente**. Lo ya arreglado está en los commits de
`pruebas-locales` (`4f751cf`, `378f96f`, `d728cce`, `c57c2d4`).

## 1. El merge en sí

La prueba de fusión (`git merge-tree`) sale **sin conflictos**, y el árbol
resultante conserva la configuración de producción: no entra el login de
desarrollo, ni `Backend/router.php`, ni los valores locales de `api.js`.
Comprobado sobre el árbol fusionado: 66 ficheros PHP sin errores de sintaxis,
1.736 pruebas de PaginaLocal, 60 del AiService, `ruff` en su línea base (23),
el frontend compila y `dist/` lleva `carreras.json` y los 126 dibujos.

### Orden exacto de despliegue

1. **Copia de seguridad** de la base de datos (`mysqldump`): las migraciones
   017 y 018 borran columnas a propósito.
2. Comprobar que 013, 014 y 015 están en producción:
   `SHOW COLUMNS FROM athlete_profiles LIKE 'sport_days'`, `pool_length_m`,
   `athlete_injuries.status`, `users.terms_accepted_at`.
3. Correr **016**, **019**, **020** y **021** (las cuatro son aditivas y repetibles).
   Con un cliente en `utf8mb4`: la 019 lleva "Miércoles" y "Sábado".
4. **Merge y push a `main`** (dispara el despliegue). Probar: guardar el
   perfil, generar una semana, escribir desde el botón Opinar.
5. **Sólo entonces**, correr **017** y **018** (las que borran columnas).

Al revés se rompe: el código de `main` nombra esas columnas y cada guardado de
perfil daría error 500 hasta que el despliegue termine.

## 2. Lo que hay que hacer a mano en producción

- **Rotar la contraseña de la base de datos.** Está escrita como valor por
  defecto en `Backend/config/database.php` (viene de antes de esta rama) y por
  tanto está en el historial de git. Rotarla y quitar el valor por defecto.
- ~~**`JwtUtils` no debe tener clave por defecto**.~~ Hecho en código
  (2026-09-13): fuera de `php -S` y la línea de comandos, sin `JWT_SECRET` el
  backend responde error en vez de firmar con una clave escrita en el repo.
  **Por eso, ANTES del merge: comprobar que `backend/.env` de producción tiene
  `JWT_SECRET`.** Si no la tiene, nadie podrá entrar; si la tiene, las sesiones
  abiertas siguen valiendo.
- **Borrar las contraseñas en claro** que quedaron en trabajos antiguos:
  `AiService/_runtime/jobs/*/spec.json` (el código nuevo ya no las escribe).
- Avisar a los 5 atletas: al entrar verán **una vez** la pantalla de
  condiciones de uso, porque su `terms_version` está vacía.

## 3. Decisiones de producto que dejo abiertas

- ~~**La puerta "¿Qué costaría esto?" enseña los costes de IA a todos los
  atletas.**~~ Resuelto (A3, `0de601e`): la puerta del precio se retiró y la
  sustituye «¿Cuánto pagarías?», sin ninguna cifra nuestra.
- **Nutrición**: si la puerta falsa recoge interés, lo barato antes de
  construirla es mandarla a mano por WhatsApp a quien la toque.

## 4. Arreglos pendientes (por orden de daño)

**Siete de los nueve están cerrados**: el 1, el 5, el 6, el 7 y el 9 en
`27675f3`; el 2 en `80ce2b0` (A2) y el 4 por decisión (A4). Siguen abiertos el
3 y el 8, que son C3 y no bloquean el merge.

1. ~~**El plan se guarda sólo cuando la app pregunta.**~~ Hecho: la app pregunta
   al cargar el plan desde cualquier pantalla (`appStore.rescatarGeneracion`) y
   la limpieza del AiService conserva 30 días lo que nadie recogió
   (`_sin_recoger`, con prueba en `test_lo_pagado_no_se_tira.py`).
2. ~~**Carrera entre procesos sobre la carpeta del atleta.**~~ Hecho (A2,
   `80ce2b0`): el chat, las gráficas y generar semana responden 409 sin tocar el
   espejo mientras hay una generación, y «Eliminar plan» se desactiva mientras
   se genera.
3. **Limpieza de trazas en Windows.** `rmtree` falla al quitar `prompts/`
   (OneDrive la tiene abierta). Ya hay reintentos; en Linux no ocurre, pero si
   alguien desarrolla en Windows las trazas se quedan a medias.
4. ~~**"Rehacer semana" no se puede usar.**~~ Cerrado por decisión (A4): no se
   cablea, porque arrastra el plan rechazado a la generación nueva. Queda
   «Eliminar».
5. ~~**El check-in usa la hora del servidor PHP.**~~ Hecho: el navegador manda su
   fecha local y `CheckinService::hoy()` la acepta a un día de distancia como
   máximo.
6. ~~**`kind="medical"`** en `/bridge/report`.~~ Hecho: rama retirada, y también
   del `Literal` del esquema. La plantilla y su render siguen vivos.
7. ~~**Sin límite de escritura** en `/feedback`.~~ Hecho: 30 mensajes por atleta
   y día. Las **puertas no llevan tope a propósito**: su embudo cuenta PERSONAS
   distintas (`doorFunnel`), así que repetir no mueve el número que se mide.
8. **El informe se pinta en un `iframe` sin `sandbox`.** El contenido lo
   generamos nosotros y va escapado, pero conviene revisarlo cuando el informe
   deje de necesitar scripts propios (hoy los usa para ajustar la impresión).
9. ~~**La tabla del modelo en el CLAUDE.md de la raíz.**~~ Hecho: los expertos y
   la decisión semanal son `gpt-5-mid` en los dos documentos.

### El plan borrado volvió a aparecer (2026-09-13), ya arreglado

Borrado «all» del atleta 2 desde la app: `season_phases` quedó en null y la
semana entera siguió en MySQL. `/bridge/plan-deleted` borraba primero las trazas
y después vaciaba el resultado de los trabajos; en Windows cada traza bloqueada
reintenta ~1,5 s, PHP corta la llamada a los 15 s y el sondeo de estado de la
app volvió a guardar la semana antes de que se vaciara. Ahora se descarta
primero (`AiService/main.py`, prueba en `test_el_borrado_no_resucita.py`). En
Linux el borrado de trazas es rápido, pero la carrera existía igual: dependía de
que lo lento terminara a tiempo. **Hace falta reiniciar uvicorn** para que el
arreglo aplique: el endpoint corre en el proceso del AiService, no en el worker.

## Auditoría final (2026-09-13, noche) — lo que hay que saber para el merge

**Conflicto de fusión conocido: `Backend/src/Utils/JwtUtils.php`.** `main` tiene
`0bb40c0` (restaurar configuración de producción), que devolvió la clave por
defecto `YOUR_SECRET_KEY_CHANGE_ME`; la rama tiene el guard que falla sin
`JWT_SECRET`. **Resolver quedándose con la versión de la rama** (sirve igual en
producción si `.env` tiene `JWT_SECRET`, que hay que comprobar antes). El resto
del árbol fusionado se comprobó con `git merge-tree`: el macrociclo en `standard`
queda en `gpt-5-mid`, sin login de desarrollo y con la URL de producción en
`api.js`.

**Reiniciar uvicorn tras desplegar**: el arreglo del borrado que resucitaba el
plan vive en el proceso del AiService, no en el worker.

**Verificado en la semana y el macrociclo de referencia** (trazas `21-04-01` y
`21-18-38`): salen a la primera (9 + 9 llamadas, un solo codificador, validador
sin nada que reparar, $0,28 + $0,24), y todos los cables nuevos llegan a su
prompt. Corregido tras auditarlos: amplificación de una molestia 1/10 en las diez
sesiones, el macrociclo y el informe; un objetivo de ritmo que contradecía el
texto de su paso; el afinamiento que recortaba todo un 20 % sin quitar lo
accesorio; tapers de 3-4 semanas para una 10K; el informe sin el nombre del
atleta; `acwr_uncoupled` en el racional.

**Sin tocar a propósito**: las preguntas del coach (repitió la del psoas quince
minutos después de contestarla; decisión del producto), el día de carrera (puerta
falsa), C3 (limpieza de trazas en Windows y `sandbox` del informe).

## Auditoría del 2026-09-14 (semanas del 5K de los atletas 2 y 3)

Las tres generaciones salieron a la primera. Corregido tras auditarlas (detalle en
`PaginaLocal/tests/test_auditoria_del_14_de_septiembre.py`): los FIJO seguían
siendo inviolables en afinamiento; la semana no conocía el ritmo de su carrera; el
guardarraíl de ACWR (umbral de población, activado por defecto) **se retiró**, y su
casilla salió del perfil; la fase y el atleta separados (Ironman con 6 h); estrés
"alto" como orden de prudencia; terreno "mixto" y distancias de catálogo que no
llegaban legibles; "puedes cerrar la pestaña" antes de las preguntas del coach.

Nuevo: la pregunta del check-in "¿Algo más que quieras que se tenga en cuenta esta
semana?" — **migración 021**, aditiva. Sin ella el campo no se guarda, pero el
check-in sigue funcionando.

**Sin tocar**: aguas abiertas como entreno (C2 del plan 1.2) y la guía de natación
de fondo, que siguen pendientes.

Segunda pasada, por la tarde (`a306a9a`), leyendo todos los prompts: las
pulsaciones del botón contaban como repeticiones (7 × 400 m salían como 10); la
nota de volumen llamaba "autorreporte" a lo medido y lo corregía con una semana de
viaje; `goal_realism` extrapolaba a "% por mes" una carrera a 6 días; los expertos
seguían en "12-24 semanas", métricas buscaba "rampas seguras", fisiología era un
"semáforo limitador" con cortes de población de sueño y estrés; una molestia ya
pasada acababa como restricción médica; el chat daba por hecha la descarga cada
cuatro semanas; el cuaderno eternizaba molestias de pasada. Retirada también la
plantilla `Example*` (rutas sin autenticación).

Tercera pasada, con las generaciones finales de los dos atletas (noche del 14):

- **Una generación colgada para siempre.** La llamada del experto de fisiología
  de la atleta 3 no volvió; sin `timeout` en ningún cliente no hubo excepción, y
  el worker parecía vivo. Ahora `LLM_TIMEOUT_S` (480 s) y un solo reintento
  interno, tope `WORKER_TOPE_S` (40 min) en el worker, y el frontend espera 45.
- **Duraciones que contradecían su texto** (`_duracion_como_dice_el_paso`), con
  0 violaciones en el validador: 4 en la semana de la atleta 3 y 1 en la del
  atleta 2. Ver `tests/test_la_duracion_dice_lo_que_dice_el_paso.py`.
- **Ruido**: `target_type` asignado como texto (585 avisos de Pydantic) y la
  clave de Gemini con la forma nueva `AQ.`.

Visto y **no tocado**, para decidir:

- Una molestia que la atleta dio por pasada ("hoy no tengo molestias, tuve en
  el aquiles y la tibia hace un mes") sigue saliendo como contingencia
  condicional en 4 sesiones de su semana. Viene de su respuesta a una pregunta
  del coach y de una `recomendacion` del macrociclo, no de un dolor declarado.
  Cambiarlo es tocar el prompt semanal y pide replay antes.
- El macrociclo de la atleta 3 se generó 6 minutos antes del filtro de
  alfabetos ajenos y su título lleva "դեպի" (armenio, "hacia"). El filtro está
  probado contra ese mismo título; con el reinicio desde cero desaparece.
- En la carrera, un atleta recibió el 5K sin objetivo en el reloj y la otra con
  5:12-5:18/km mientras el texto le pide apretar el último km: el reloj le
  pitará justo ahí. Criterio de producto (¿alerta de ritmo en carrera o no?).
- Las zonas de pulso de la atleta 3 ponen sus rodajes suaves en Z2
  (149-168 ppm) y la semana le pide "rodaje en Z1" con objetivo Z1: es probable
  que el reloj le avise durante todo el rodaje.

**El orden de mañana y las decisiones abiertas están en `contexto/planes/PLAN_LANZAMIENTO_BETA_2_0.md`.**

### El relanzamiento es desde cero (decidido el 2026-09-14)

Se borran todos los datos y cuentas de la Beta 1 (se conservan las filas de
administrador). Por eso, y no por las secciones de arriba sobre migraciones con
datos vivos, **mañana se corren 016-021 seguidas**, incluidas las que borran
columnas. Nuevo desde esa decisión: condiciones de uso 2026-09-15 con casilla de
18 años, `retirar_garmin_antes_del_reinicio.php`, `reinicio_beta_2_0.sql`,
`borrar_cuenta.php` (lo que prometen las condiciones) y errores de Garmin
legibles. Scripts ensayados en local: el reinicio sobre una base desechable, el
borrado sobre un atleta sintético (destapó un `cost_usd` ambiguo, corregido).

## 5. Lo que quedó verificado con generaciones reales

Tres semanas seguidas del atleta 2, con Garmin de verdad:

- Primer día de la semana: con "lunes", la primera semana salió de viernes a
  domingo (el modelo la llamó "mini-semana (vie–dom)"), y las siguientes
  encadenaron sin huecos (semana 2: 11–13; semana 3: numerada 3).
- La adherencia mide **sólo la semana que termina** (75 %: 1 completada, 1
  modificada, 1 sin intensidad, 1 no realizada) y el expediente cierra **una**
  línea, con sus propios números.
- El coach pregunta, espera, y ya **no repite** lo que se le contestó.
- La calidad va con las piernas frescas: el domingo pasó de "bici Z2 y luego
  VO2max" a "VO2max y luego bici Z2".
- Borrar el plan: motivo + texto libre guardados, entrenos retirados del reloj,
  historial limpio.
- Subir a Garmin: 10 de 10, sin avisos falsos.
