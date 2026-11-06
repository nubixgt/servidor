# Estado y pendientes (al 2026-10-23)

Foto del proyecto para retomarlo sin conversación previa. **Lo que viene es la Beta 3.0:
`planes/PLAN_BETA_3_0.md`** (control y salud de la app, tope D2, "Pedir ayuda", estadísticas sin jerga, mover y
editar entrenos, progreso y medallas, Coach + Cuaderno). Todo lo hecho hasta el 2026-10-09 es Beta 2.x; su
detalle con casillas sigue en `planes/PLAN_DE_ACCION_BETA_2_0.md`.

## Dónde estamos

- **Beta 3.0 en producción desde el 2026-10-23.** `main` lleva la fusión `a0e9f68` (todo `pruebas-locales` hasta
  `d82beda`, con la configuración de producción de `0bb40c0`), desplegada por GitHub Actions y comprobada desde
  fuera (`/health` ok, las rutas nuevas del puente existen, el `dev-login` da 404). **Falta lo manual de Miguel**:
  migraciones 028-037 y, justo después de la 035-036, la cortesía de cada atleta de la beta
  (`operacion/DEPLOYMENT.md`, «Fusión de la Beta 3.0»). Se sigue trabajando en `pruebas-locales`; la próxima
  fusión tendrá el mismo conflicto en `Login.vue` (quedarse sin el `dev-login`). Después, el mismo día, dos
  fusiones más sin migraciones: `1de7d70` (aviso de los «sí o sí», editor de sesiones) y la de editar la fuerza y
  añadir entrenos (abajo).
- **Nombres (2026-10-10):** la fuerza S1-S8 se llamó "Beta 3.0" mientras se hacía y salió en la Beta 2.1; en el
  código y el contexto ya se llama Beta 2.1. "Beta 3.0" es sólo el plan nuevo.
- **Beta 2.0 = relanzamiento desde cero**: se borrarán todos los perfiles. Las migraciones van hasta la **037**
  (en local aplicadas y comprobadas con `Backend/scripts/verificar_migraciones.php`).
- **La Beta 2.0 ya está en `main` y publicada** (2026-09-17). La fuerza de la Beta 2.1: plan y rúbrica en
  `planes/PLAN_DE_ACCION_BETA_2_0.md` (sección Beta 2.1); hechos S1 (superseries en el esquema y en las pantallas),
  S2 (superseries al reloj como rondas; José revisó la Upper A de prueba, 1701100942, y la dio por buena),
  S3 (perfil de fuerza, Database/025), S4 (pegar la rutina, ordenarla con un modelo barato y confirmarla;
  ~0,004 USD por lectura) y S5 (la semana usa su rutina: el código elige la sesión y construye el bloque, el
  modelo sólo propone cambios con motivo; ~0,012 USD) y S6 (el coach escribe el programa de quien no tiene
  rutina, ~0,06 USD, y se confirma en la misma pantalla). Falta pagar la semana real de punta a punta: el
  perfil local de José no tiene macrociclo, así que hacen falta temporada + semana (decidido el 2026-09-17:
  se hará cuando esté todo). S7 hecho (el macrociclo recibe para qué hace fuerza y puede escribir
  `strength_quality` por fase, que llega a la semana). S8 son decisiones, no código: ver el plan.
- Pruebas (2026-10-23): 2.756 en PaginaLocal y 110 en AiService en verde; `ruff` 19 de línea base; `vulture` 204 líneas
  (casi todo campos de modelos Pydantic, falsos positivos); frontend compila; los PHP pasan `php -l`;
  `auditar_conexiones.py` con 0 rutas rotas.
- Atletas locales de prueba (`/auth/dev-login`): 1 Miguel (administrador), 2 José (atleta 2), 3 Jessie
  (atleta 3). Sus trazas reales están en `AiService/_runtime/storage/u2` y `u3`.

## Hecho en las últimas sesiones (lo que conviene saber que existe)

- **Editar la fuerza de una semana y añadir entrenos propios (2026-10-23)** (`test_editar_la_fuerza.py`; ver 02,
  «La fuerza de una semana y los entrenos que añade el atleta»). Lo pidió el responsable: cambiar un ejercicio por
  otro del catálogo, añadirlo al final o seguido, quitar y reordenar, con series, repeticiones, carga y descanso;
  sólo en esa semana (el programa del perfil no cambia). Y que el atleta pueda añadir un entreno a un día («es su
  responsabilidad») y escribir una nota o elegir un drill en cada paso. Sin migración.
  - **Probado en el reloj real de José** (permiso de la memoria: subir desde local y borrarlo todo después):
    «Enviar al reloj» manda sólo lo cambiado («4/4 · 3 reemplazadas»), un intercambio no duplica, quitar lo
    añadido lo retira («0/0 · 1 retiradas»), el ejercicio cambiado llega con su nombre (GOBLET_SQUAT) y el pull
    buoy como material. Al terminar se borraron los 9 entrenos de la prueba, el registro local y su copia en
    MySQL, y la semana volvió a la del coach. Los dos de producción (natación del 23, carrera del 24) intactos.
  - Fallos que salieron y se arreglaron: la huella no miraba la fuerza (un ejercicio cambiado seguía «En tu
    reloj»); un entreno añadido y quitado después de enviarlo se quedaba en el reloj; poner material a una
    natación borraba el ritmo del texto del coach; una plancha añadida llegaba como 10 repeticiones («30 s» por
    defecto en lo que se sostiene); unas repeticiones de 24+ letras salían «ajustado» sin tocarlas; volver una
    sesión a su día dejaba `edited: {"by": "atleta"}`; quitar lo añadido dejaba la del coach en «am».
  - Revisión de pantallas con Edge por CDP (PC 1440 y teléfono 375, sin errores en consola ni desbordes): la
    carga y las repeticiones se cortaban («Tensión que te dej…») y ahora crecen a dos líneas; la vista previa sólo
    decía «queda ajustada» y ahora dice qué («Gato-camello → Peso muerto rumano»); el buscador de drills de
    natación proponía «sentadilla, remo».
  - Sin hacer: cambiar el programa de fuerza del PERFIL desde la semana (es el otro camino que pidió: ya existe
    en Perfil → Tu fuerza). Sólo 26 de los 68 ejercicios de fuerza del catálogo tienen nombre de Garmin
    verificado; los demás llegan como un paso con su nombre escrito, y el editor lo avisa.
- **Por qué la semana de Fernando cuesta más y el aviso de los «sí o sí» (2026-10-23)** (`test_fijos_poco_hechos.py`,
  `scripts/costo_de_las_semanas.py`). Con permiso del responsable se leyó su traza de producción: su tabla tiene 13
  «sí o sí»; casi todas sus semanas salían de carga extrema y la decisión se devolvía una vez (~0,5 USD contra
  ~0,33 del resto). Decidido: la app le avisa, sin IA, de los «sí o sí» que hace 1 de 4 semanas o menos (con al
  menos 3 semanas con actividad), con «pasar a Puedo» en el perfil; no se corrige su historial de horas (cada
  atleta se mide contra el suyo: a uno de 15 h no se le dice 4). Su traza se borró de este equipo al terminar.

- **Auditoría antes de fusionar la Beta 3.0 en `main` (2026-10-23)** (`test_carga_de_madrugada.py`,
  `test_precio_y_muro.py::LaCortesiaDeLaBeta`, `test_semana_automatica.py::SinCondicionesNiAccesoEspera`).
  Pasos para Miguel en `operacion/DEPLOYMENT.md`, «Fusión de la Beta 3.0».
  - **Fusión**: un conflicto (`Login.vue`), el `dev-login` no vuelve, sin dependencias ni variables nuevas.
  - **Migraciones 028-037 ensayadas dos veces** sobre una base en el estado de producción (hasta la 027) con los
    datos de los tres atletas: sin errores y los datos idénticos byte a byte. La 037 necesita la 036. Los 9
    planes guardados (25 semanas, 186 días) abren y pasan por la huella, el plan anterior y la semana objetivo.
  - **Con la 035-036, todos los atletas de la beta quedaban bloqueados** y leían «Esta cuenta de Garmin ya usó
    sus semanas gratis» (falso: su Garmin se conectó antes de que hubiera semanas gratis; ensayado con José y
    Jessie). Decisión del responsable: migrar todo y darles **un año de cortesía** desde «Usuarios → Registrar
    pago → Cortesía de la beta» (Q0 en la contabilidad, aviso «Tienes acceso sin costo», Precios lo dice). Y el
    mensaje sin semanas gratis ya no afirma que las usó: ofrece volver a leer su Garmin. Probado en el navegador
    como Miguel y José; la cortesía de prueba se borró.
  - **La semana automática del domingo y las condiciones nuevas** (2026-09-15 → 2026-10-21): quien no abriera la
    app gastaba sus 3 intentos y se quedaba sin semana. Ahora sin condiciones o sin acceso no cuenta como intento,
    le dice qué falta y se genera en la hora siguiente a resolverlo; pasado su primer día, «no se generó a tiempo».
    Ensayado contra la base local (domingo 1-nov 21:30, sin lanzar nada).
  - **La carga de Garmin de madrugada**: a las 05:13 Garmin todavía no tiene el estado del día y la semana del
    26-oct de José salió sin carga aguda, crónica, rango óptimo ni reparto (3 de sus 8 generaciones archivadas).
    Ahora se usa la de ayer y el bloque dice de qué día es.
  - **Cerebro revisado** sobre la semana real del 26-oct y el macrociclo del 22-oct: sin molestias amplificadas
    (la pregunta del peroneo sale de lo que declaró), ritmos de natación coherentes con su meta, macrociclo con
    las carreras en su sitio. **Visto y NO tocado por decisión del responsable**: la semana después de cualquier
    carrera (A, B o C) se trata igual y da por hecho que fue «a tope» (José, 3,25 h contra ~5,8 h tras un gravel B
    que iba a hacer controlado). Y, menores: la guía de drills llegó pero repitió dos de la semana anterior; los
    ejemplos de la regla de idioma llevan números reales de José; una frase con «sin dolor… pero me molesta»
    no activa la regla de proporción.

- **Revisión completa de la app, el porqué en palabras de atleta y la hoja de la semana en una página
  (2026-10-22)** (`test_pantallas_del_atleta.py::RevisionCompletaDel22DeOctubre`, `test_porque_en_palabras.py`,
  `test_por_dia_de_la_semana.py`). Dos rondas (el tope que pidió el responsable) con evaluadores Sonnet nuevos y
  ciegos por grupo de pantallas, persona (Andrea) y heurística, PC a 1440 y teléfono a 375:
  | Pantalla | R1 persona / heur. | R2 persona / heur. |
  |---|---|---|
  | Inicio | 78 / 74 | 89 / 82 |
  | Plan | 61 / 59 | 70 / 64 |
  | Check-in | 67 / 71 | 74 / 72 |
  | Chat · Ayuda | 91 / 91 · 92 / 80 | 92 / 94 · 92 / 95 |
  | Perfil: Identidad · Objetivos · Experiencia · Avanzado | 88 · 84 · 94 · 88 (persona) | 89 · 88 · 95 · 91 (persona); 85 · 76 · 93 · 77 (heur.) |
  | Precios · Cuenta | 74 · 84 | 89 · 96 |
  | Progreso · Informes · Cuaderno · Historial | 75 · 94 · 92 · 75 | 67 · 92 · 91 · 76 (con el AiService sin reiniciar: vieron las gráficas de antes) |
  Lo que dijeron en la ronda 2 se arregló después sin una tercera ronda: Plan pone la semana arriba y «generar»
  debajo cuando ya hay semana; el check-in con «mejor / peor» bajo cada escala, «Ganas de entrenar» en el mismo
  sentido que las demás (el valor guardado no cambia) y el botón que siempre se puede pulsar («Generar sin
  responder»). Lo que cambió, por decisión del responsable:
  - **La tabla de días vuelve al ciclo de tres toques** (descanso → «Puedo» → «Sí o sí» → descanso) y en PC
    ocupa el ancho (el pincel dejaba todo a la izquierda en PC). Avanzado: zonas en columnas en PC.
  - **El porqué de la semana en palabras de atleta, traducido y no quitado**: el rationale se escribe DESPUÉS
    de los días en la misma llamada (es ex post: no cambia lo que se decide). `_LANGUAGE_DIRECTIVE` dice que las
    siglas entre agentes se quedan (VO2max, FTP, ACWR, HRV) y que en lo que lee el atleta ninguna va sola: «ACWR
    0,70» pasa a «esta semana cargaste un 30 % menos que tu media». Una frase y 3-5 líneas con su número dicho
    en palabras. Réplicas de la decisión real de José del 19-oct con gpt-5-mid: con la regla sola seguía
    escribiendo ACWR (la línea de «consagrados» la contradecía); con las dos, palabras. En la generación real
    del 22-oct sólo se coló «DOMS».
  - **La hoja de la semana es UNA página por semana** sin zonas ni porqué; **las zonas y ritmos van a la hoja
    de la temporada** (con las bandas de potencia y «en N semanas» junto a cada carrera).
  - **Historial dice qué hiciste** («4 de 6 sesiones hechas · 1 de 2 clave · 3,1 h de 4,0 h», sin IA, del
    expediente: `TrackRecordRepository::resultados`), con los ✓/✗ de hoy y la nota de que el resumen se contó
    al cerrar la semana.
  - Fallos encontrados y arreglados:
    - **`by_weekday` contaba fechas, no sesiones** (`track_record.summarise_week`): un sábado con dos
      sesiones y una hecha salía «0 de 1». Las líneas YA cerradas del expediente siguen con la cuenta vieja
      (no se reescriben: es historial); un día se juzga sólo con ≥3 sesiones (`MUESTRA_MINIMA_DEL_DIA`).
    - **Una carrera entre hoy y el inicio de la semana salía «ya corrida»** (el gravel del sábado 24 para la
      semana del lunes 26, generada el jueves 22; el coach preguntó «¿ya la corriste?»). Ahora
      `antes_de_esta_semana` + `todavia_por_correr`, y el contexto las separa de las corridas.
    - **Quien gastó sus semanas gratis con un Garmin ya visto** veía «Conecta tu Garmin»: estado
      `garmin_usado` en `AccessService` con su texto.
    - Dos expresiones regulares con el carácter 0x08 en vez de `\b` (`usaRirORpe` en Plan nunca casaba): un
      heredoc de Bash convierte `\\b`. Para scripts con barras, archivo o `chr(92)`.
    - Plan enseñaba «Primero genera el plan de temporada» mientras cargaba; el aviso de la semana automática
      salía apagada; un tercer «¿Seguro?» tras el check-in con la contraseña guardada.
  - **Dos generaciones reales de José (decididas por el responsable)**: la semana del 26-oct (0,319 USD; el coach
    hizo 3 preguntas, la primera destapó el fallo del gravel; una corrección de `zona_en_rangos` en el libro de
    redes) y el macrociclo (0,296 USD; 3 preguntas, usó «controlado, como rodada larga»; sin siglas ni palabras de
    duda). Réplicas del porqué: ~0,10 USD. Lo que salió al imprimirlas:
    - **La hoja de la semana del 26-oct salía en 3 páginas** (la tercera, el pie solo): con siete columnas iguales
      el martes de fuerza y bici marcaba la altura y había que reducir al 56 %. Ahora cada día recibe el ancho que
      pide su contenido antes de reducir (el descanso, estrecho): 82 %, y la del 19-oct del 73 % al 100 %.
    - **Una meta de natación llegaba al macrociclo como «16:40/km»** (su 400 m en 6:40, comparado con sus bandas
      de CARRERA; está en el prompt archivado `008__season_planner`). `goal_realism._pace_context`: natación en
      /100 m, bici en km/h, sin bandas de carrera; la hoja no enseña un /km guardado antes del arreglo. El modelo
      lo ignoró y escribió bien 1:40/100 m.
    - **Historial daba por cumplida la semana en curso** («3 de 3 hechas» el jueves): «En curso · hasta hoy…».
    - Visto y NO tocado: el informe de análisis (síntesis) dice «ACWR de la app: 1,03» en su tabla de
      indicadores, con la explicación en la columna de al lado; cambiarlo pide una réplica de la síntesis.

- **Avanzado, Progreso, Informes, Cuaderno e Historial, y el umbral de bici en el chat (2026-10-21)**
  (`test_pantallas_del_atleta.py`, `test_umbral_de_bici_en_el_chat.py`). Lo pidió el responsable con un comentario
  general: «hay mucho texto y a veces habla de cosas que el atleta no debería recibir» («sin pagar otra
  generación»: su cobro es fijo). Mismo método SIN sesgo que el perfil (dos evaluadores Sonnet nuevos y ciegos por
  ronda, persona y heurística, tres rondas como mucho). Persona / heurística:
  - Avanzado: 50/54 → 65/70 → 78/67 → 77/72.
  - Progreso: 67/65 → 78/65 → 82/60 → 75/74. Informes: 87/90 → 93/93 → 93/90 → 91/90. Cuaderno: 87/84 → 93/90 →
    89/89 → 92/91. Historial: 78/77 → 78/81 → 72/(lista 91, abierto 67) → (lista 93, abierto 81)/78. La ronda 2
    fotografió por primera vez la semana ABIERTA, y ahí bajó.
  - **El umbral de bici de José (producción)**: el chat le dio 176 tras su test de bici porque leía sólo la ficha de
    la última generación (anterior al test) y la ficha decía «FC umbral» sin deporte (176 es el de carrera). Ahora
    el chat recibe sus tests al día (`describir_tests`) y la ficha dice «FC umbral en carrera (LTHR)». Las zonas
    salen de las que el reloj aplicó a su ÚLTIMA actividad de cada deporte (`get_activity_hr_in_timezones`; no hay
    lectura de la tabla de Garmin Connect en garminconnect 0.2.25): su bici del 18-oct usó las de fábrica
    (Z2 141-154 con su LTHR de bici de 156). **Para decidir**: leer la tabla de Garmin Connect directamente.
  - Textos de por dentro quitados: «sin pagar otra generación», «generaciones… correcciones automáticas»,
    «Generación automática apagada», «Laboratorio de rendimiento» (también el título de la pestaña), «(sin IA)»,
    «Lo que hizo la IA».
  - Avanzado: zonas por deporte en palabras de atleta y «Tu plan usa 5:40/km» con su porqué; test de umbral en una
    frase con el protocolo plegado; Salud dice para qué se usa; bordes de los campos a 3:1 (toda la app);
    «Restablecer mi perfil» pasó a la tuerca → **Cuenta** con BORRAR escrito (borra también la contraseña de Garmin
    guardada, y lo dice).
  - Progreso: la racha dice por qué está así («La semana del 12 al 18 oct hiciste 3 de 6…») y con 0 dice «Empiezas
    una racha nueva»; nivel «2 de 3 semanas cumplidas, en total»; medallas en rejilla con icono por tipo
    («Semana con todos los clave», «Tu forma aeróbica subió»); barras por semana con su rango («26 sep-4 oct»,
    `charts._rango_de_la_semana`), valor encima y ámbar bajo el 70 %; días con su muestra («1 de 2»); ejes con el
    año entero; «Por deporte» sin sumar km de tres deportes.
  - Historial: cada versión se titula por la semana que AÑADIÓ (`PlanRepository` manda `ultima_desde/hasta`);
    semanas y temporadas aparte; al abrir, esa semana primero («sáb 17 oct», «1 h 20 min», icono por deporte), las
    de antes y el porqué plegados.
  - Cuaderno: el ejemplo del campo es del tipo que viene marcado (era un horario con «Preferencias») y cada tipo
    dice para qué sirve; «coach» en vez de «entrenador». Informes: «Ver y guardar» / «Leer» y enlace a Progreso.
  - **Para decidir (lo que más pesa en las dos últimas rondas, gravedad 3)**: el porqué del coach (`week_rationale`)
    llega lleno de siglas («ACWR 1,40», «Hooper 12/28», «HRV», «crónica 284») porque su prompt pide citar cada
    número «con su fuente y su fecha» y no dice que el atleta no las conoce. Arreglo propuesto: una regla en
    `weekly_planner_node` y una o dos réplicas sobre una semana real (~0,10-0,20 USD). No se tocó sin permiso.
  - Sin acuerdo entre evaluadores y sin tocar: el titular de la racha en 0 (uno pidió quitar el «0» y otro lo
    llama «alegre con contenido de fracaso»); el selector del tipo de nota (que lo deduzca la app sería otra
    llamada a un modelo).
- **El perfil en el teléfono: Identidad, Objetivos y días e Historial (2026-10-21)** (`test_perfil_en_el_telefono.py`).
  Revisión SIN sesgo (lo pidió el responsable: «la meta no es llegar a 90, es el mejor resultado»): en cada ronda dos
  evaluadores Sonnet NUEVOS y ciegos (no saben que hubo otra versión ni qué cambió), una persona (Andrea, primera vez,
  sin jerga) y una heurística, que listan problemas con su gravedad antes de puntuar (100 − 12/6/3/1 por gravedad).
  Persona 30 → 47 → 60 → 65; heurística 27 → 49 → 56 → 60 (tres rondas, el tope; ningún problema de gravedad 4
  desde la primera). Lo que queda de gravedad 3 en los dos: la tabla de días (celdas de 33×38-43 px, por debajo de
  44, y el «pincel»; un evaluador de la ronda 1 pidió quitar el ciclo de tres toques y otro de la ronda 3 pide
  volver a él: sin acuerdo, se quedó el pincel) y, para la persona, el alta (Garmin primero, «Guardar y continuar»
  junto a «Conectar»). Lo que cambió:
  - **Tabla de días**: los 7 días caben a 375 px (medía 520 y enseñaba lunes a miércoles: gravedad 4); se marca con
    un «pincel» (Puedo / Sí o sí / No puedo) en vez del ciclo de tres toques; las celdas miden 33×43 px, lo máximo con
    siete columnas.
  - **Guardar**: «Guardado» apagado o «Guardar» lima según haya cambios (`hayCambios`, foto de lo que se mandaría),
    barra fija «Cambios sin guardar» y aviso al salir con cambios.
  - **Carreras y marcas** como filas que se abren (`FilaPlegable.vue`), por fecha y con las corridas aparte; quitar
    pregunta antes; la prioridad A/B/C explicada. Objetivos pasó de 7.358 px a ~3.500.
  - **Tiempos** h/min/s sin cifras cortadas; **«Cuándo»** de una marca con mes y año (`MesAnio.vue`), guardado igual
    que antes («septiembre 2026»), sin migración.
  - **Garmin**: la contraseña en una frase y el detalle técnico plegado y entero; ojo para verla; «Volver a leer mi
    Garmin» cuando ya está conectado; en el alta sin pestañas ni segunda barra de progreso.
  - **Fuerza**: con un programa del coach confirmado, el programa primero (con sus ejercicios) y el formulario sólo al
    pedir otro; las palabras de gimnasio explicadas; «Como hasta ahora» pasó a «Sin programa fijo».
  - **Decisiones del responsable (2026-10-21)**: «Recordar mi contraseña» sigue marcada; el **día de descanso
    preferido sale de la tabla** (el campo ya no se pregunta y, con tabla, se guarda vacío: un día sin nada es
    descanso); la **distancia principal sale de la carrera A** (`utils/profile.distanciaDeLaCarreraA`; sin carrera A
    vuelve el selector); y **cada nivel tiene su definición**.
  - Visto al probar: las transiciones de color se quedaban en su primer fotograma en el panel y en Edge sin
    pantalla, y los evaluadores vieron un «Guardar» gris con cambios; se quitaron (no aportaban).
  - Después de la ronda 3 (decisiones del responsable): en el alta, con correo y contraseña escritos y sin conectar,
    el botón dice «Conectar y continuar» y conecta (si Garmin la rechaza, no avanza); la pestaña «Historial» se
    llama **«Mi experiencia»** (el menú ya tiene un «Historial» con los planes anteriores; el id sigue `historial`).
  - Con boceto y aprobados por el responsable (2026-10-21): la tabla de días con el deporte ENCIMA de su fila y el
    día escrito en cada casilla (39×43 px en vez de 33×43, medido a 375 px como José), y el programa de fuerza en su
    propia pantalla (`ProgramaDeFuerzaHoja.vue`): una pestaña por sesión, bloques con su descanso, «¿qué es?» en los
    ejercicios que sabemos explicar, «Cómo progresar» con las palabras explicadas, y Escape o la flecha para volver.
    Desplegado dentro del perfil eran ~3.160 px.
  - Sin tocar: «Uso otro reloj» sigue siendo puerta falsa (no hay alta sin Garmin); la insignia de avisos en el alta.
- **✓/✗ de cada sesión desde Garmin, sin IA (decisión del responsable del 2026-10-21)** (`test_estado_de_las_sesiones.py`):
  «que la app sepa que ya hiciste el entreno: un cheque cuando se logró y una x cuando no; hoy lo ve la IA al
  generar y eso crea ansiedad». `services/ai/estado_de_las_sesiones.py` usa la MISMA lectura que la adherencia
  de la generación (`build_adherence_report`: `workoutId`, fecha + deporte + duración, día de margen, sesión
  movida en su semana, intensidad contra SUS zonas, lo no sincronizado pendiente), juzgando también HOY (una
  sesión de hoy sin actividad está a tiempo). `/plan/session-status` → `/bridge/session-status` lee las
  actividades de 14 días, las zonas y la última sincronización con la contraseña guardada (~19 s la primera
  vez, contra la cuenta real de José) y lo guarda 15 min en el espejo (`estado_de_las_sesiones.json`;
  «Actualizar» lo salta una vez por minuto). Se ve en el calendario de Plan, en el detalle de la sesión
  («Hecha · 20 de 30' · 0,8 km · FC 114») y en Inicio. Sin contraseña guardada no hay marcas y se dice.
- **Inicio rehecho y la primera experiencia (2026-10-21)** (`test_inicio_y_primera_experiencia.py`): Inicio gira
  alrededor de HOY (la sesión con su ✓ o sus primeros pasos, «después: …»), «Esta semana» (N de M hechas, los
  días con ✓/✗, la línea del reloj en amarillo si lleva 3 h sin sincronizar, la racha), la carrera (sube si
  faltan ≤14 días), VO₂max y la temporada (fase y semana N de M). Sin semana: «Tu primera semana en 4 pasos»
  (perfil, Garmin con «+2 semanas gratis», temporada, semana). Revisión con subagente (Sonnet 5.5), 3 rondas: 78 → 87 → 91. En el teléfono «Ayuda» pasó a la
  barra de arriba y el Coach flotante se aparta al bajar. Fallos de lógica encontrados y arreglados:
  - «Volumen» y «Semanas» sumaban todas las semanas guardadas (hasta 12); la adherencia era la de la última
    generación; la fecha salía «21 De Octubre»;
  - «Fuerza 10 km» en la última semana (la fuerza no tiene km: `charts._SIN_KM`);
  - Progreso e Informes sólo estaban en la barra de arriba, que no se ve en el teléfono (ahora en el menú), y la
    barra decía «DASHBOARD/REPORTS»;
  - el Legal partía las frases a mitad (`markdown.renderDocumento`);
  - el paso a paso del perfil enseñaba «Avanzado», un segundo «Guardar» y una tarjeta «Estado del atleta» toda en
    «—» antes de conectar Garmin.
  - Perfil → Avanzado: seis secciones que se despliegan (`SeccionPlegable.vue`) con su resumen («6 de 7»).
- **El calendario de semanas y mover de cualquier semana activa (decisión del responsable del 2026-10-21)**
  (`test_calendario_de_semanas.py`, `test_mover_sesion.py`):
  - Plan enseña las semanas como un **calendario que se desliza a los lados** (`components/plan/CalendarioDeSemanas.vue`):
    una fila lunes-domingo por semana, también las terminadas (ya no hace falta ir a Historial para la anterior),
    icono y color por deporte, la carrera rellena en amarillo; tocar un día abre su detalle debajo.
  - **Mover**: cualquier sesión de una semana ACTIVA (la de hoy o una posterior ya escrita), **también la de un día
    de esta semana que ya pasó** («ayer no pude»), a hoy o a un día que viene, **en su semana o en la otra activa**
    (`edicion_de_sesion.mover`; una semana terminada no se toca: su expediente se cerró). La de un día pasado no se
    intercambia (mandaría la otra al pasado) y avisa «si ya la hiciste, déjala donde está». «Ajustar» sigue de hoy
    en adelante. «Mover» pone el calendario en modo mover (destinos marcados, también arrastrar en pantalla ancha),
    la hoja (`MoverSesionHoja.vue`) enseña antes→después, intercambiar o hacer las dos y los avisos del servidor en
    seco; al confirmar el icono vuela a su día, y hay «Deshacer» 15 s. «N cambios sin enviar · Enviar al reloj»
    va dentro del calendario.
  - **Revisión de UX/UI con un subagente, 3 rondas (tope pedido: 90 o 3 rondas): 77 → 89 → 92.** Fallos que salieron
    al probar y se arreglaron: el detalle enseñaba la copia vieja de la semana tras mover; la hoja no salía en el
    teléfono (`fixed` dentro de una vista con `transform`: va a `body`); el centrado de la barra «Deshacer» lo pisaba
    su animación; `sm:block` y la clase del icono pisaban `line-clamp` y `hidden`. Verificado como José en local
    (escritorio y 375 px; 375×667 para la hoja); cada movimiento de prueba se deshizo.
  - Sin ver todavía: la fila «cambios sin enviar» (en local sus sesiones no están en el reloj) y el arrastre real
    con ratón.
- **Pagos anulables y código de invitación hasta conectar Garmin (decisiones del 2026-10-21)**:
  - **Database/037** (a mano en producción): un pago registrado por error se ANULA desde «Usuarios → Pagos» (con
    motivo; sólo el último vigente, porque los siguientes empiezan donde terminaba él), deja de dar acceso y el
    atleta recibe «Corregimos un pago». Registrar un pago le avisa «Tu pago quedó registrado … hasta el …».
  - El código de quien te invitó se puede escribir **hasta conectar tu Garmin** (`POST /referrals/claim`, en
    «Invitar a un atleta» y desde el aviso «Conecta tu Garmin» de Plan); el de la entrada vale también si la cuenta
    ya existía. Probado contra la base local (Garmin ya conectado, código inexistente, el de José, repetido) y limpiado.
- **Drills de natación (2026-10-21)**: no los limitábamos; el modelo repetía su repertorio y COPIABA la semana
  anterior (23 días con natación archivados: catch-up 15, arrastre de dedos 15, sculling 9, puños 7; patada 1,
  respiración 0). La decisión recibe, sólo si el atleta nada (`_nada`), un menú por objetivo técnico (posición y
  patada, respiración, rotación, agarre, recobro, aguas abiertas), drill seguido de nado completo, nada de
  hipóxicos al límite, y «no copies los drills del Plan anterior». Dos réplicas de la semana real de José (0,27 USD):
  con el menú solo, otra vez catch-up y fingertip; con la frase, sculling delante + agarre y exhalación continua.
  `test_drills_de_natacion.py`. **Para decidir**: el módulo de análisis de vídeo (ver la respuesta de la sesión).
- **La fuerza al cambiar de fase (2026-10-21)**: el programa no caduca (es lo que deja progresar la carga); cuando
  empieza una fase nueva del macrociclo y el programa es de antes, Plan sugiere revisarlo («Revisar mi fuerza» /
  «Mantenerlo»; `macrociclo.fuerzaPorRevisar`). Sin número de semanas nuestro.
- **«Precios» en la tuerca y el plan anual (decisión del responsable del 2026-10-21)** (`test_precio_y_muro.py`):
  - **Q600 (80 USD) al año** además de Q60 (8 USD) al mes; el ahorro (Q120 / 16 USD, 2 meses) lo calcula
    `AccessService::precio()['anual']`. El modal de la tuerca tiene dos pestañas, «Apariencia» y «Precios»
    (`components/precios/PreciosPanel.vue`), y **todo «Quiero suscribirme» abre Precios** (`store.abrirPrecios`);
    elegir un plan abre «Ayuda» con el mensaje escrito («Quiero el plan anual: Q600 (80 USD) al año. ¿Cómo pago?»).
  - Cialdini SIN inventar: reciprocidad (lo que ya se le regaló), escasez real (la fecha en la que terminan SUS
    semanas), compromiso (su carrera A, con las semanas que faltan), contraste (Q720 tachado), autoridad (lo que
    la app hace), simpatía (su entrenador) y unidad (Guatemala, quetzales). **Sin prueba social** («el más
    elegido», «N atletas») hasta tener datos o testimonios reales; una prueba lo fija.
  - Usuarios → «Registrar pago» tiene «Plan anual (12 meses) · Q600» (`plan: 'anual'`), y registrar un pago
    **avisa al atleta** («Tu pago quedó registrado … hasta el …»). Las fechas de otro año llevan el año
    (`AutoWeekService::fechaLarga`): el aviso de un anual decía «hasta el jueves 28 de octubre» por el 2027.
  - **Legal 2026-10-21**: el plan anual y que no hay renovación automática; «ganáis» → «ganan». En producción
    todos aceptan otra vez (ya iban a hacerlo). Aceptada en la cuenta local de Jessie.
  - **La entrada ya deja escribir un código de invitación** («¿Tienes un código de invitación?»): la tarjeta
    compartida lleva el código y no había dónde escribirlo; sólo contaba el enlace `?ref=`. Cuenta sólo al
    CREAR la cuenta, como el enlace. Y decía «cuando generes tu primera semana, los dos ganáis», la regla de
    antes del 2026-10-20. Un código que no existe se ignora sin avisar.
  - Verificado en el navegador como Jessie (prueba sembrada, «te quedan 8 días»; su 10K en 26 semanas; a 375 px
    sin desbordar) y el pago anual registrado por Miguel por la ruta de la pantalla (del 29-oct-2026 al
    28-oct-2027, Q600, con su aviso). Datos de prueba borrados.
  - **Power Coach AI cuesta Q60 (8 USD) al mes**, dicho siempre igual desde `AccessService::precio()` (Plan,
    Invitar, el muro y el Legal). Sustituye a «pagas el costo real de la IA, sin margen».
  - **Las semanas gratis se gastan por calendario** (7 días cada una desde que se ganan; la que llega con días
    por delante se suma al final) y **terminan en un muro que sólo cierra generar** (semana, macrociclo y la
    semana automática; 402 `paywall` en `PlanService::startSeason/startWeek`, después de las condiciones). El
    plan, el reloj, el progreso, el cuaderno y el chat siguen. Sin Garmin conectado: «Conecta tu Garmin para
    empezar tus semanas gratis». El día es el de Guatemala (`AccessService::ZONA`): con UTC un pago del 20 por la
    tarde empezaba el 21.
  - **El pago va fuera de la app** y lo registra el administrador en «Usuarios» («Registrar pago»: 1-12 meses,
    Q60 cada uno, con nota), que alarga el acceso desde donde terminaba. **Database/036** (`athlete_payments`, a
    mano en producción; contabilidad sin FK, nunca se borra una fila). Los atletas de la beta empiezan como
    todos al relanzar.
  - Plan enseña los días gratis que quedan y el precio; sin acceso, una tarjeta «Tus semanas gratis terminaron el
    sábado 3 de octubre · Q60 (8 USD) al mes» con «Quiero suscribirme» (abre Ayuda con el mensaje escrito), y
    «Generar» avisa ANTES del check-in. Se quitó «¿Cuánto pagarías?» (`PuertaDePago.vue`): junto a un precio fijado
    ya no medía nada; sus respuestas siguen en Métricas.
  - Verificado en local como Jessie, sin gastar: sin Garmin, 402 sin llamar a la IA; prueba vencida, 402 con la
    fecha en español; prueba de hoy, «te quedan 14 días (hasta el lunes 2 de noviembre)»; pago registrado por
    Miguel, «pagado hasta el 2026-11-20». Arreglados al probar: la fecha ISO en el mensaje del muro, el mensaje
    de «Quiero suscribirme» que se borraba al abrir Ayuda, el aviso «Genera la siguiente» junto al muro, y el día
    en UTC. Datos de prueba borrados. **Ojo en local**: José y Jessie no tienen semanas gratis (sus Garmin se
    conectaron antes de la 035); para generar, volver a conectar su Garmin en el perfil.
- **Decisiones del responsable del 2026-10-20, aplicadas** (`test_semanas_gratis.py`):
  - **El codificador sigue en Haiku 4.5** (banco de 15 semanas, abajo). **La prudencia con la HRV en el borde
    inferior de su rango se queda.**
  - **Semanas gratis atadas a una cuenta Garmin NUEVA** («no free riders»): al conectar un Garmin que nunca
    había pasado por la app, 2 semanas de prueba; si entró invitado, 2 más para él y 2 para quien invitó
    (acumulables, una por invitado). Un Garmin ya visto, con esta u otra cuenta de la app, no da nada, y su
    invitación queda «no cuenta». **Database/035** (a mano en producción): `garmin_accounts_seen` (huella
    sha256 del `profileId` de Garmin, o del correo si no lo da; sin FK, sobrevive al borrado de la cuenta),
    `athlete_free_weeks` (el saldo, por motivo) y `athlete_referrals.garmin_repeated`.
    `FreeWeeksService::decidir` es la regla; se aplica en `ProfileController::garminImport`, y el id de Garmin
    no sale a la pantalla. Antes, una invitación contaba al generar la primera semana (4 y 4). Ensayado contra la
    base local con Garmins ficticios (nuevo: 2; volver a sincronizar: 2; el mismo Garmin en otra cuenta: 0;
    invitación con Garmin nuevo: 4 y 4) y limpiado. **No hay muro de pago**: es un saldo.
  - **Legal 2026-10-20**: semanas gratis y la huella de la cuenta Garmin que se conserva al borrar la cuenta.
    En producción todos vuelven a aceptar (ya iban a hacerlo por la 2026-10-11). Aceptada en la cuenta local de José.
  - **La ansiedad del atleta avanzado con la primera semana** (idea del responsable): tarjeta «Por qué tu
    primera semana es así» en Plan, sólo con una semana generada, con SUS horas de Garmin («unas 5,5 h por
    semana; esta semana tiene 5,8 h, con 1 sesión de calidad») y qué hacer si se le queda corta. Y el motivo de
    borrado «Demasiado o muy poco volumen» se partió en «se me queda corta» y «es demasiado», para medirlo.
  - Estudio Lean de todas las ideas de la Beta 3.0 con evidencia de mercado: `planes/ESTUDIO_LEAN_BETA_3_0.md`.
    El riesgo número uno no es una función: la dependencia de una API no oficial de Garmin.
- **Prueba de punta a punta del 2026-10-19 (0,28 USD): la semana automática REAL de José, por primera vez**
  (interruptor en Plan → `semanas_automaticas.php` → generar, guardar y subir 6/6 a su reloj, ~7 min). Todo dejó
  su rastro: etapa «hecha», evento `plan_semana_automatica`, generación `automatic=1`, libro con `detail:
  automatica` (0,278 USD), Métricas «1 (1 auto)», expediente copiado (7 semanas), registro de subidas copiado.
  En su reloj: la carrera del 24-oct «hasta pulsar vuelta» y **sin objetivo**, la fuerza por repeticiones (ni una
  serie de 2 s), los 4 entrenos de producción intactos. Sobre esa semana: mover la natación del 21 al 25 y
  «Enviar al reloj» (sólo ese día: «uploaded 1, replaced 1»), ajustar la bici 3x2' → 2x2' y volver a la del
  coach (de nuevo «En tu reloj»), ninguna respuesta con costos, el chat con la carrera de la semana, Progreso y
  la salud de la app. Después se borraron de su Garmin los 6 entrenos de la prueba, del registro local y de su
  copia, y el interruptor volvió a apagado. Los fallos que salieron, arreglados (`test_prueba_del_19_de_octubre.py`):
  - **El script de las semanas automáticas moría a los 45 s** (`HttpClient::postJson` llamaba a
    `set_time_limit` y en la línea de comandos eso CREA un tope): en producción la semana se habría guardado y
    subido una hora tarde. Ahora sólo amplía un tope que ya exista.
  - «Próxima: el domingo 18 de octubre a las 21:00» el lunes 19 (una hora pasada): ahora «en la próxima hora»,
    y no se enseña mientras esa semana se genera o se sube.
  - Plan enseñaba «Termina tu perfil primero — 62 %» a un perfil al 100 % mientras cargaba, y quien no tiene
    foto veía la de stock de una desconocida (Miguel, `avatarUrl: null`): el atleta de ejemplo del store se
    vació y sin foto sale la inicial.
  - El primer sondeo de una semana automática pedía quedarse a esperar las preguntas del coach.
  - Visto y no tocado: «Umbral de Garmin fuera de la ventana» sale 4 veces en cada `worker.log` (ruido); la fuerza
    de pierna puede caer a dos días de una carrera (la regla sólo protege la víspera: es criterio, no fallo).
- **Aviso de sincronizar en el check-in (2026-10-19, lo pidió José)**: al abrirse pregunta cuándo subió algo su
  reloj (`GET /plan/garmin-sync` → `/bridge/garmin-sync` → `get_device_last_used`, con la contraseña guardada,
  ~6 s, sin bloquear) y, si fue hace 3 h o más, lo dice antes de generar («hoy a las 09:51»). Sin contraseña o
  sin respuesta de Garmin, no dice nada. Verificado como José con su cuenta real (`lastUsedDeviceUploadTime`
  1792425092000 = 15:51:32 UTC, el campo y la unidad que lee la adherencia). `test_aviso_de_sincronizacion.py`.
- **«Ajustar» y «Mover de día» son botones** en cada sesión de hoy en adelante (eran texto gris pequeño y José no
  los encontró). Ojo: **en producción no existen** hasta fusionar (F6-F7 sólo están en `pruebas-locales`; `main`
  sigue en `ebd400b`), y en local la semana de José terminó el 18-oct.
- **Haiku 5.5 en el codificador: NO, por ahora (banco del 2026-10-19)**. `scripts/banco_codificador.py`
  reconstruye el prompt de HOY desde la decisión y la ficha de cada semana archivada
  (`scripts/prompts_del_codificador.py`; idéntico al archivado salvo las instrucciones cambiadas después):
  15 semanas reales (10 del atleta 2, 5 del 3, 15-sep a 12-oct), Haiku 4.5 una vez y Haiku 5.5 dos con
  razonamiento adaptativo y dos sin él. 0,64 USD.

  | | Haiku 4.5 | 5.5 | 5.5 sin razonar |
  |---|---|---|---|
  | USD por semana / segundos | 0,030 / 22 | 0,0033 / 9 | 0,0032 / 9 |
  | salidas que no pasan el esquema | 0/15 | 1/30 | 2/30 |
  | sesiones que no codificó | 0 | 4 (una semana entera a medias) | 0 |
  | unidades mal (por paso) | 1,1 % | 0,9 % | 0,4 % |
  | sesiones con ±10 % de minutos | 21 % | 27 % | 24 % |
  | frases condicionales perdidas | 0 | 5 | 4 |
  | sesiones idénticas entre dos vueltas | — | 43 % | 66 % |

  Lo que pesa: una semana sin días dispara la reparación pagada de la semana entera (~0,11 USD, más de lo que
  ahorra en cuatro), una salida fuera de esquema repite la llamada, y pierde lo condicional («si te sientes
  suelto al final…») aun con la regla nueva. A favor: obedece mejor «calentamiento y enfriamiento sin zona»
  (4.5 lo incumple y la red `_RAMPAS` lo corrige). **No admite `temperature`, `top_p` ni `top_k`** («deprecated
  for this model»), tampoco con el razonamiento apagado (`thinking={"type": "disabled"}` sí lo acepta; esa
  variante se quitó de `model_config.py` porque la prueba de proveedores prohíbe mandar `thinking`). Repetir el
  banco cuando salga otra versión: `claude-haiku-55` sigue en `model_config.py`, sin rol.
- **La competición va al reloj sin objetivo de ritmo** (el responsable, 2026-10-19): en código
  (`carrera_hasta_pulsar_vuelta`, sólo el paso de la carrera de un `race_day`), no en el prompt, que lo leería
  también para las sesiones de carrera a pie. El plan de ritmos sigue en su texto.
- **El codificador copia lo opcional y lo condicional** al texto del paso («si te sientes muy bien, añade…»,
  «si notas el aquiles, para»): regla nueva en sus instrucciones; con Haiku 4.5, 0 perdidas en las 15 semanas.
- **Fallos que encontró José y auditoría (2026-10-19)**, sin gastar en modelos:
  - **Métricas no contaba las semanas automáticas como planes**: la tabla «Atleta por atleta» cuenta por los
    clics (`EventService::EVENTOS`) y el cron no hace clic. Su costo sí estaba en el libro. Ahora
    `AutoWeekService` anota `plan_semana_automatica`, la tabla la cuenta («26 (1 auto)») y el libro marca la
    línea (`detail: automatica`), que el gasto por herramienta dice aparte. `test_semana_automatica_en_las_cuentas.py`.
  - **La fuerza subía al reloj una serie de 2 s**: `_reps_from_text` buscaba un tiempo en cualquier parte del
    texto, y «10 (2" arriba, 2" abajo)» era 2 s (en local también «12-15 con pausa 1" arriba», 1 s, y «8-10
    por lado (3-4 s bajada)», 4 s). Manda la primera cantidad; un tempo o una pausa son anotaciones; dos
    trabajos o un AMRAP los cierra el atleta. Las 29 fuerzas guardadas en local se construyen sin valores raros.
  - **Un entreno sin sincronizar contaba como «no realizado»**: se lee la última subida del reloj
    (`get_device_last_used`, `GarminData.ultima_sincronizacion`) y una sesión sin actividad de ese día o
    después queda pendiente («su reloj no ha sincronizado»), sin contar en la racha, y se mide entera en la
    generación siguiente (`semana_a_completar`). El día es el del atleta (el worker pone su zona).
    `test_reloj_sin_sincronizar.py`.
  - **«¿Qué no encaja?» ofrece «Mover o ajustar una sesión»** antes de borrar la semana (F2 lo pedía y no se
    hizo al cerrar F6-F7). Verificado en el navegador como José, también a 375 px, sin guardar nada.
  - **Los costos llegaban a la API del atleta**: `/plan`, `/plan/generate-status`, `/plan/history(/id)` y
    `PATCH /plan/session` devolvían `total_cost_usd` y `cost_breakdown`; la pantalla los escondía, pero a un
    F12. `App\Support\SinCostos` los quita para quien no es administrador. `test_costos_solo_del_administrador.py`.
  - Pronombres: tres textos de prompt decían «él» del atleta (chat, macrociclo, informe); ahora no.
  - El log de PHP se lee también con la ruta relativa de fábrica de cPanel («error_log»).
  - `scripts/medir_copias_entre_dias.py`: la medición de «codificar por día», repetible con producción (en
    local, 46 pares y 0 copias).
  - Visto y NO tocado: las carpetas vacías de `_runtime/jobs` (y `git worktree prune`) no se borran en local por
    un permiso heredado de Windows, «Todos: Deny DeleteSubdirectoriesAndFiles»; no es del código y en el
    servidor Linux no existe. No se cambian permisos del sistema sin el responsable.
  - Revisado sin hallazgos: el SQL va con parámetros o columnas de listas cerradas; sólo `/auth/google` y
    `/auth/dev-login` (local) van sin `#[Authorize]`; ninguna pantalla de atleta ni el diagnóstico de
    «Pedir ayuda» llevan costos; todas las pantallas de José, Jessie y Miguel cargan sin errores en consola.
- **Beta 3.0, F0 hecha (2026-10-11)**: se mide qué pantallas abren los atletas (`POST /telemetry/screen`, lista
  cerrada `ExperimentController::PANTALLAS`, sección «Qué pantallas abren» en Métricas, sin administradores);
  retirados el Doctor y «Traer de mi reloj» de punta a punta, y el código Vue muerto (ReportsModal,
  WorkoutDetailModal, Home y los campos de ejemplo del store). `tests/test_lo_retirado_no_vuelve.py`.
  La pantalla vieja `web_app/static` sigue (decisión) y su botón del Doctor sólo dice que se retiró.
- **F10/fase C, codificar por día: NO hace falta hoy (2026-10-19)**. El plan lo condiciona a que el codificador copie
  pasos entre días. Medido en las 5 semanas archivadas (decisión contra Haiku, atletas 2 y 3): 15 pares de sesiones
  del mismo deporte, 0 con los pasos idénticos y decisiones distintas. Codificar día a día multiplicaría las
  llamadas sin un fallo que arreglar. Volver a medirlo con las semanas de producción tras relanzar.
- **F10/fase C, pendientes que necesitan datos o decisión**: borrar las redes que no corrigen nada (hacen falta ≥20
  generaciones en el libro de redes de producción) y las predicciones propias desde el umbral medido (cambia lo
  que el informe le dice al atleta y no hay un test real con el que verificarlas: se decide con el primer atleta
  que haga el test).
- **F10/fase C, el banco de réplicas compara unidades (2026-10-19)**: `replay.unidades_que_no_cuadran` cuenta, paso a
  paso y en su recuperación, lo que el reloj llevaría distinto de su texto (un tiempo escrito y otro número fuera
  del ±20 %, metros copiados como segundos, una distancia donde el texto da un tiempo), con las mismas lecturas que
  la red `_duracion_como_dice_el_paso`; sale en la tabla de `replay_node.py`. Ve los tres fallos reales («200m
  trote» como 200 s, «15-20"» como 90 s, 6 × 1' como 50'); en las 5 semanas de Haiku archivadas, 0 de 103 pasos.
  `tests/test_replica_por_unidades.py`.
- **F10/fase C, copia del expediente en MySQL (2026-10-19)**: **Database/034** (`athlete_track_record`, a mano en
  producción): `track_record.jsonl` y sus descartadas, línea a línea con el texto EXACTO, copiados al terminar cada
  generación y al borrar un plan (`PlanService::copiarExpediente`, `/bridge/track-record`). El fichero sigue
  mandando. `Backend/scripts/comparar_expediente.php` compara y, con `--copiar`, hace la primera copia de los que ya
  había: en local, atleta 2 (6 semanas y 5 descartadas) y 3 (5 semanas), idénticas. **En producción, tras correr la
  034, hay que lanzar una vez `comparar_expediente.php --copiar`** (con permiso). **Pendiente, después de la beta**:
  que la copia pase a mandar (reponer el fichero desde MySQL si falta). `tests/test_copia_del_expediente.py`.
- **F10, series en cinta (A11.3, 2026-10-11)**: la línea del perfil («controlled-pace intervals … indoors») y la guía
  de trail («en llano —una cinta…— el ritmo sí es la unidad») invitaban a series por ritmo en cinta, y el reloj no
  mide bien el ritmo bajo techo. Ahora en cinta la velocidad va en el texto (km/h y su ritmo) y el objetivo del
  reloj por zona de FC: en los dos textos, en la REGLA DE MODALIDAD (obligatoria) de la ficha y en el punto de
  calidad del codificador, y el validador no exige ritmo a una sesión en cinta (`_en_cinta`). Réplica sobre la
  semana real de Jessie con «haré las series en cinta» (0,18 USD): la decisión escribió «5x3' a 11,3–11,9 km/h
  (5:18–5:02/km)»; con la excepción en un punto aparte Haiku puso ritmo 302-318 (la regla obligatoria ganaba);
  con la excepción dentro de la regla, Z4 y los km/h en el texto. `tests/test_series_en_cinta.py`.
- **F10, «sin piscina» como `limitacion` (A11.5) y «carrera objetivo» para una B (A11.6): no se repiten** con los
  modelos de hoy. Réplica del macrociclo REAL de Jessie del 23-sep (escrito con gpt-5.2, que sí metió «No programar
  natación… por falta de piscina» como limitación pese a la regla) con gpt-6-sol, el de producción desde ese día:
  4 restricciones, todas recomendaciones, ninguna de piscina (0,08 USD; el esquema se repuso desde
  `SeasonPlannerOutputs`). Y ningún informe desde el 15-sep llama «carrera objetivo» a una B (síntesis en
  gpt-6-luna); sólo «Carrera objetivo de la semana» para el 10K B del 9-oct, que era la de esa semana. Sin red.
- **F10, una noche de HRV como motivo (A11.2)**: no se repite. La regla («nunca con una sola noche», persistencia y
  segunda señal) ya estaba; las 6 decisiones de Jessie de hoy usan su promedio de 7 días. Visto y NO tocado, para
  decidir: las 6 contienen la intensidad porque ese promedio (47 ms) está en el borde INFERIOR de su rango
  (47-61), dentro de él y sin otra señal que lo confirme (Readiness 81, pulso normal). Es prudencia, no un error.
- **F10, molestia contada en las respuestas al coach (A10.1, 2026-10-11)**: las respuestas llegaban a la decisión sin
  la proporción al dolor que tiene la molestia del check-in. `preguntas_del_coach.py` no se tocó: el nodo semanal
  lee las respuestas (`nota_de_molestias_de_las_respuestas`, `niggle_constraints.molestias_en_respuestas`) y, SÓLO si
  dicen que algo molesta (no negado; también «peroneo 1/10»), añade la regla: leve (≤2/10 o «leve/ligera»)
  → sólo en las 2-3 sesiones que más cargan esa estructura; si no, en proporción y sin darla por leve. Réplicas
  sobre la decisión real de Jessie del 24-sep con «muy ligera molestia en el aquiles»: sin la nota, en 8 de 8
  sesiones; con ella, en 3 de 7 (las de carrera). 0,21 USD. `tests/test_molestia_en_las_respuestas.py`.
- **F7.9 hecha (2026-10-11): mover, ajustar y volver a la del coach en el reloj REAL de José** (permiso del
  responsable), por las mismas rutas que la pantalla: subir la semana (7 entrenos; retiró los dos caducados del
  19 y 20-sep), mover «Ritmo 10K» del miércoles 14 al jueves 15 (estado «cambios sin enviar» y, al enviar, el
  14 vacío y el 15 con id nuevo), ajustar 3x3' a 2x3' (Garmin: 2 repeticiones de 3' a 3,049-3,125 m/s =
  5:20-5:28/km, 32') y volver a la del coach (3 repeticiones, 38', se queda el jueves). Tras cuatro subidas (28
  entrenos creados) el calendario tenía sólo los 7 de la semana y la biblioteca sólo esos 7: **sin duplicados ni
  huérfanos**. «Enviar al reloj» volvía a crear TODA la semana («replaced: 7») aunque cambiara una
  sesión: ahora manda sólo las fechas con «cambios sin enviar» (`fechasSinEnviar`); probado en su reloj
  (mover el 14 al 15 y enviar: «uploaded: 1, replaced: 1», las otras 6 con su id) y limpiado después. Al terminar se borraron los 7 entrenos de la prueba de su Garmin (decisión del responsable: desde local
  se puede subir a su reloj, pero se borra todo al acabar, porque su entreno real es el de producción), y su
  calendario quedó con el entreno de producción del día; se quitaron también del registro local y de su copia.
  **Fallo de F5 encontrado en la prueba y arreglado**: la copia en MySQL no se escribía nunca al terminar una
  subida (`garminUploadStatus` miraba `status in (success, failed)` y al acabar `status` es el texto de
  progreso): tras cuatro subidas la copia tenía los ids de la primera, escritos sólo al mover una sesión.
- **F6.6 hecha (2026-10-11): la semana siguiente sabe lo que el atleta movió o ajustó.** Sin cable nuevo: el
  plan anterior ya llevaba la marca de F6/F7 (`edited.moved_from`, `edited.adjusted`). Cada sesión cambiada
  lleva su nota en «Plan anterior» y un párrafo lo presenta como preferencia OBSERVADA (no regla: la tabla y lo
  que pide mandan; un cambio suelto no rehace la semana); el «## Output» pide UNA frase del racional sobre ello
  (`RATIONALE_DE_CAMBIOS`, sólo si hubo cambios: sin ellos el prompt no cambia un carácter). Tres réplicas
  pagadas sobre la decisión REAL de Jessie (semana del 28-sep, gpt-5.2; 0,11 + 0,09 + 0,11 USD) con «intercambió
  la larga del domingo con el rodaje del viernes»: las tres dejaron la larga el domingo; con la frase pedida
  sólo en el bloque del plan anterior, ninguna lo dijo (iba a ~60.000 caracteres del Output); con la frase en
  el Output: «Vi que la semana pasada moviste la tirada larga del domingo al viernes; aquí vuelvo a dejar la
  tirada larga el domingo como ancla […] pero te dejo explícito cómo intercambiar viernes↔domingo».
  `tests/test_lo_que_cambio_el_atleta.py`.
- **Decisiones del responsable del 2026-10-11, aplicadas** (`tests/test_decisiones_del_11_de_octubre.py`):
  - **Borrar una nota la retira**; sólo una retirada se borra para siempre, preguntando antes.
  - **«Revisa mi cuaderno»** (sustituye a «Reordenar»): lee el cuaderno con el perfil y la fecha de hoy,
    pregunta (4 como mucho), ordena y avisa de lo que hay que cambiar en el perfil, sin escribirlo. Prueba
    REAL como José con 10 notas al estilo de Fernando (4 llamadas, ~0,005 USD): la primera vuelta acertó el
    martes, la natación, la medicación y la competición, pero retiró un viaje sin saber qué día era, mandó la
    molestia del aquiles «desde agosto» a lesiones actuales sin preguntar, y el martes salió dos veces.
    Arreglado (fecha en el mensaje, «ante una molestia, pregunta si sigue», sin avisos repetidos) y repetido:
    preguntó por el aquiles, con «ya pasó» la retiró, y el martes quedó como aviso único. Verificado también en
    el navegador (preguntas, avisos con «Ir a mi perfil» que abre Objetivos y días) con esas respuestas reales
    simuladas. Las notas de prueba se borraron.
  - **Legal 2026-10-11**: Ayuda y su diagnóstico (se borra a los **90 días**, cron horario), notas que se
    retiran, invitaciones y la tarjeta con nombre. Aceptada en la cuenta local de José. **En producción todos
    vuelven a ver la pantalla de aceptación.**
  - **Estadísticas**: el peso sale de la vista del atleta, y el pulso en reposo declarado de «Tu forma».
  - **Invitaciones** (Database/033, a mano en producción): código y enlace por atleta, cuenta cuando el
    invitado genera su primera semana y ganan 4 semanas cada uno (un saldo: no hay cobro aún; **el número es
    tuyo**). «Invitar a un atleta» ya no es puerta falsa; Métricas → «Invitaciones». Ensayado con una cuenta
    sintética (registro, activación, las dos partes) que se borró después. La tarjeta lleva el nombre de pila
    (se puede quitar al crearla) y el código; con el código abajo entran 2 medallas en 1080×1350 (se pisaban).
  - Arreglado de paso: «Pasar al perfil» podía mandar a «Perfil → Objetivos → Contexto para tu plan», que no
    existe; ahora cada ruta es la de la pantalla y una prueba lo comprueba.
- **Beta 3.0, F9 hecha (2026-10-11)**: Coach y Cuaderno juntos, y «Reordenar cuaderno».
  - El cuaderno es UN panel (`components/chat/CuadernoPanel.vue`): su página (`/admin/cuaderno`, igual que antes)
    y la pestaña «Lo que sé de ti» del chat. Lo que el chat anota se sigue guardando solo (regla del cuaderno),
    pero ahora el chat lo ENSEÑA con «Quitar», que lo retira con el motivo «Quitada desde el chat» (nunca borra).
    `CoachController` devuelve `notes` (id y texto).
  - «Reordenar cuaderno» (con 4 o más vigentes): `services/ai/reordenar_cuaderno.py` con el modelo del cuaderno
    (flash-lite, <0,001 USD, `kind` `notebook_reorder` en el libro) propone reescribir, juntar, pasar al perfil o
    retirar; el código descarta lo que no cuadra (una anotación dos veces, ids que no existen o retiradas,
    números que no estaban, campos del perfil fuera de la lista cerrada). El atleta acepta cada cambio
    (`POST /notebook/reorganize` y `/apply`): las originales se RETIRAN con su motivo y `replaced_by`
    (Database/032), la nueva lleva `source 'reorden'`, y una preferencia sigue siéndolo aunque la pantalla
    diga otra cosa (lo decide el servidor). «Pasar al perfil» no escribe el perfil: dice dónde va y da el
    texto para copiarlo.
  - Verificado en el navegador como José, con anotaciones de prueba (borradas después) y una propuesta
    SIMULADA: juntar, pasar al perfil y retirar, cada una comprobada en la base. Arreglados al probar: los
    «Aceptar» salían desactivados (`:disabled` con una cadena vacía) y la revisión desaparecía al bajar de 4
    vigentes. **Sin probar con el modelo real** (<0,001 USD; necesita tu permiso). Un mensaje real del chat
    (0,0026 USD) se escapó por error en la prueba: la simulación no interceptó.
  - **Pruebas que estaban en rojo desde F3 y F6** (se dijo que estaban en verde y no era cierto): el prompt
    del chat y la Bienvenida mandaban al botón «Opinar», que desde F3 se llama «Ayuda» (arreglado: una
    frase del prompt del chat, no las preguntas del coach); dos comprobaciones miraban
    `verificar_migraciones.php` en vez de `Migraciones::ESPERADO`; y una contaba dos copias del registro
    de subidas cuando F6 añadió la tercera con razón. Cada una apunta ahora a la garantía de debajo.
  - Decidido el 2026-10-11: `DELETE /notebook/{id}` retira (ver arriba).
- **Beta 3.0, F8 hecha (2026-10-11)**: progreso, racha, niveles, medallas y compartir. `services/ai/progreso.py`
  (sin IA, llega en las gráficas como `progreso`): una semana SUMA con el 70 % de sus sesiones (hechas,
  cambiadas o sin la intensidad) y sin pasar del 120 % de sus horas; por encima del 120 % ni suma ni rompe;
  una molestia del check-in (que se contesta ANTES de su semana) o una lesión que empezó esa semana la
  CONGELA; una semana sin plan rompe la cadena; rehacer una semana no la cuenta dos veces. Niveles por
  semanas cumplidas (1, 3, 6, 10, 16, 24, 36). Medallas derivadas (constancia, entrenos clave, km SÓLO de
  los deportes que hace, fuerza, check-in, test, carrera, VO2max +1); las vistas se recuerdan en el
  navegador. Progreso enseña «Tu racha», nivel, el mes, medallas y récords; el Inicio, «Tu racha».
  «Compartir» dibuja una tarjeta (1080×1350 o 1080×1920) SIN nombre ni datos de salud y registra
  `logro_compartido`. PHP manda ahora a las gráficas los check-in y el registro de fuerza. Verificado
  como José (racha 1, nivel 1, 4 medallas: primera semana, todas las clave, su 5K del 20-sep y VO2max +1).
  Arreglados al probar: el check-in del último día de una semana congelaba la que acababa, y una lesión
  activa vieja la habría congelado para siempre. **Sin probar en el navegador**: «Compartir» (descarga un
  archivo, y eso necesita tu permiso). **Para decidir** (plan F8): si la tarjeta lleva el nombre (hoy no) y
  si el código de invitación va ya (hoy no). No hecho: la función común con `embudo_del_bucle` que pedía el
  plan; cuentan cosas distintas (el embudo, si entrenó; la racha, si cumplió) y sólo comparten las ventanas.
- **Beta 3.0, F7 hecha (2026-10-11)**: ajustar los pasos de una sesión y volver a la del coach, sin IA.
  «Ajustar» en cada sesión que viene de carrera, bici, caminata o natación (no la fuerza, los combinados, la
  carrera ni lo pasado): duración (tiempo, distancia o hasta pulsar vuelta), repeticiones, recuperación y
  objetivo (zona, ritmo en m:ss/km, vatios; en natación ninguno, porque el reloj no lo lleva), quitar,
  reordenar y añadir desde plantillas con SUS números (`plantillas_de_paso.py`: sus bandas o, sin ellas, zona
  de pulso). Vista previa con avisos que no impiden (calidad sin ritmo, ritmo fuera de sus bandas, serie sin
  recuperación, ±30 % de la del coach) y «Volver a la del coach» (los pasos y textos del coach; el día se
  queda donde esté). `services/ai/ajuste_de_sesion.py`: lo que no cambia vuelve a ser EXACTAMENTE el paso
  del coach; lo que cambia se escribe desde sus campos (`rendering/describir_paso.py`, con las horas como
  horas y sin repetir la serie en el hijo) y pasa el guardián de unidades. Verificado como José (3x3' → 2x3',
  38' → 32', y de vuelta). Arreglado al probarlo: se reescribían TODOS los textos porque el plan llega de PHP
  con `600.0` y `{}` y vuelve del navegador con `600` y `[]`; y el editor no se veía (la vista de Plan anima
  con `transform` y el `fixed` se colocaba respecto a ella). **Con permiso, en tu reloj**: probar mover,
  ajustar 5×3' a 4×3' y volver, y mirar el calendario de Garmin (plan F7.9).
- **Beta 3.0, F6 hecha (2026-10-11), salvo un punto que espera tu permiso**: mover una sesión dentro de su
  semana, sin IA. «Mover a otro día» en cada sesión que viene (nunca la carrera): elegir el día, intercambiar o
  hacerla como segunda sesión si está ocupado, ver el resumen y los avisos (su tabla no da ese deporte ese
  día, calidad seguida, calidad la víspera de la carrera) y guardar. La sesión lleva «Movida por ti desde el
  …» y queda «Cambios sin enviar» hasta pulsar «Enviar al reloj» (sube la semana: crea y después borra).
  Cálculo puro en `services/ai/edicion_de_sesion.py` (`/bridge/plan-edit`); `PATCH /plan/session` guarda con
  la versión del plan (409 si cambió o hay una generación o subida en curso), deja constancia en
  **Database/031** (`athlete_plan_edits`, con la versión del coach), mueve el registro de fuerza y lleva los
  ids del reloj a sus claves (`/bridge/garmin-ledger-move`). Verificado como José en el navegador (Ritmo 10K
  del miércoles 14 al jueves 15 con su aviso, y de vuelta). Arreglados al probar: el día de carrera se
  ofrecía como destino, unas rectas contaban como «calidad seguida», y el candado del registro no veía una
  subida en marcha (el worker borra su `spec.json`). Los cambios del atleta van al diagnóstico de «Pedir
  ayuda». Que la semana siguiente sepa lo que el atleta movió (F6.6) se hizo el 2026-10-11 (arriba). Y probar mover en un
  reloj real (F6-F7) sólo con permiso y en tu cuenta. No hecho a propósito: la «red» de `pollStatus` del
  plan: con el candado (409 con algo en curso) y la versión, una edición no puede cruzarse con una generación.
- **Beta 3.0, F5 hecha (2026-10-11)**: el registro de subidas, sólido para mover y editar. Cada entrada guarda
  `scheduled_date` (dónde está DE VERDAD en el reloj); `upload_ledger.mover` cambia la clave de una sesión sin
  tocar su `workout_id` (la siguiente subida la reemplaza, y si falla el id sigue y se puede retirar) y
  `estado_de_la_sesion` dice «En tu reloj», «Cambios sin enviar» o «Sin subir» -- la huella no mira la fecha,
  y sin la fecha programada una sesión movida salía «en tu reloj» con el entreno en el día viejo. Cada
  tarjeta del plan lleva su insignia (`/plan/garmin-sessions`). **Database/030** (a mano en producción):
  copia del registro en MySQL, escrita al terminar cada subida y al borrar un plan; el fichero sigue
  mandando. `Backend/scripts/comparar_registro_de_subidas.php` dice si coinciden (en local: atleta 2, 3
  entrenos; atleta 3, 5). **Pendiente, después de la beta**: cuando hayan coincidido, que la copia pase a
  mandar (PHP la manda en cada llamada y el espejo escribe el fichero). `mover` todavía no lo llama nadie:
  es la base de F6. Visto y NO tocado: el registro local del atleta 2 aún tiene entrenos del 19-20 de
  septiembre y el test del 25; se retiran solos en su siguiente subida (`caducadas`).
- **Beta 3.0, F4 hecha (2026-10-11)**: estadísticas sin jerga. Analytics se llama «Progreso» y el atleta ve
  una lista blanca de tarjetas (`Frontend/src/utils/progreso.js`: meta, lo mandado contra lo hecho, entrenos
  clave, qué días cumple, VO2max, peso, volumen y reparto); el ACWR, el Load Focus, la VFC, el pulso en
  reposo medido, el estrés, la disponibilidad y el sueño quedan para el administrador («Vista técnica»).
  El Inicio enseña «Tu última semana» (horas hechas contra prescritas y km POR DEPORTE, `charts.last_week`)
  y el VO2max; «Salud» pasa a «Tu forma». El check-in ya no dice «Hooper» («/ 28 · cuanto más bajo,
  mejor»). La IA sigue recibiendo todo: `charts.py` sólo gana `last_week`. Verificado como José en el
  navegador (7,9 h de 7 h; bici 30,7 km, natación 6,5, carrera 6,3; VO2max 51). **Para decidir**: si el
  pulso en reposo y las horas de sueño vuelven a la vista del atleta y si el peso se queda (hoy: fuera,
  fuera, dentro, como dice el plan). **Medir** (plan F4.5): comparar `pantalla_vista` de Progreso dos
  semanas antes y después.
- **Beta 3.0, F3 hecha (2026-10-11)**: «Pedir ayuda» con una persona en el circuito. **Database/029 hay que
  correrla a mano en producción**; sin ella el botón sigue siendo «Opinar» y una consulta llega como mensaje.
  El botón flotante es «Ayuda» (pedir ayuda en 5 categorías, opinar, mis consultas) y cada sesión del plan
  tiene «¿Algo raro con esta sesión?». La consulta lleva un diagnóstico que arma el SERVIDOR con lista blanca
  (lo decidido contra lo codificado y el reloj, perfil, cuenta, trabajos, redes, migraciones que faltan,
  errores del navegador) y un origen probable (`services/ai/triaje.py`); el atleta ve antes exactamente qué
  se adjunta. Cola en Métricas, respuesta como aviso a él solo, avisos que se recargan al cambiar de pantalla.
  Errores del navegador en Salud de la app. Scripts `exportar_incidencia.php` y `analizar_incidencia.py`
  (en producción, con permiso para cada incidencia). Ver 02, «Pedir ayuda». Verificado en el navegador
  de punta a punta (José pregunta desde su sesión del 14-oct, Miguel responde, José lo ve, también a 375 px);
  la consulta de prueba se borró después. Arreglados al probar: el aviso salía sin su consulta (MariaDB no
  acepta un parámetro en `SHOW COLUMNS` y el error se tragaba) y un día ilegible del plan se callaba.
  **Para decidir**: si `Legal.php` debe contar el diagnóstico (y subir su versión; no se tocó) y cuánto se
  guardan los diagnósticos. Visto y decidido sin preguntar: una carrera en un día de descanso NO es señal
  del triaje (desde el 2026-10-09 ese día está disponible para la carrera); sale como información.
- **Beta 3.0, F2 hecha (2026-10-11)**: el tope de generaciones (D2) y la alerta de gasto. **Database/028**
  (`athlete_generations` y `notifications.user_id`) **hay que correrla a mano en producción**; sin ella no hay
  tope y todo sigue como antes. 3 versiones de una misma semana, 1 macrociclo cada 7 días, los fallos no
  gastan cupo y la semana automática no cuenta. Qué semana se paga lo decide el cerebro
  (`server.semana_objetivo`, la misma función que la escribe); PHP sólo guarda y manda las semanas ya
  generadas. Plan dice cuántas versiones quedan al borrar la semana y desde cuándo otro macrociclo, nunca en
  dinero. Alerta en Salud de la app y un aviso al administrador (una vez por atleta y día, desde el cron de
  las semanas automáticas) cuando un atleta pasa de 1 USD en un día. Ver 02, «El tope de generaciones».
  Verificado sin pagar: filas sembradas para José (409 del cupo antes de lanzar nada, la ventana sigue
  mandando con cupo libre) y el aviso dirigido sólo a Miguel con el día real 2026-10-09 a 0,50 USD de umbral.
  El libro local tiene al atleta 2 con 4,58 USD el 2026-09-23 (nuestras pruebas). Sin hacer: «Mover» y
  «Ajustar» del diálogo «¿Qué no encaja?» (llegan con F5-F7).
- **Beta 3.0, F1 hecha (2026-10-11)**: panel «Salud de la app» arriba de Métricas
  (`components/admin/SaludDeLaApp.vue`, se refresca cada 30 s con la pestaña visible): el servicio y la cola,
  los trabajos con su estado, duración, costo y log (saneado y con el recorte dicho), errores de proveedor y el
  final del log de errores de PHP. Y el **libro de redes** (`PaginaLocal/core/redes_ledger.py`,
  `data/billing/redes.jsonl`): cada corrección de una red determinista, para poder decir "0 de N
  generaciones" y retirarla (Fase C). Ver 02, «La salud de la app». Verificado en el navegador como Miguel,
  en escritorio y a 375 px. Lo que salió al estrenarlo, arreglado y con su prueba:
  - un macrociclo que salió bien aparecía como «Clave rechazada»: los milisegundos de la hora del log
    («17:36:48,401») se leían como un 401;
  - «45 trabajos parecen colgados»: eran carpetas vacías (abajo);
  - «0 correcciones en 65 generaciones» con el libro empezado ese día: ahora cuenta desde su primera línea;
  - la telemetría de F0 mandaba también las pantallas del administrador y daba un 400 en cada una.
  Visto y NO tocado: **en local `_cleanup` vacía las carpetas de trabajos pero no las quita** (56 de 64 en
  `_runtime/jobs`; `rmdir` falla en silencio, seguramente por OneDrive). El panel las salta. En producción
  (Linux) no debería pasar; mirarlo allí. **El log de PHP** sólo se lee si `error_log` apunta a un archivo:
  con el servidor de desarrollo dice que no hay; en cPanel falta comprobarlo. **El libro de redes empieza
  vacío** y en producción arranca con la primera generación tras desplegar. Los errores del navegador del
  panel llegan con F3.
- **Decisiones del responsable del 2026-10-09, aplicadas y probadas** (prueba local pagada: macrociclo
  0,312 + semana 0,316 USD, José con un 10K B de prueba en su sábado de descanso y una bici de 2 h con amigos
  en la nota de un miércoles sin bici):
  - **La carrera va al reloj hasta pulsar vuelta** (`plan_validator.carrera_hasta_pulsar_vuelta`, en el
    validador y otra vez tras reparar; el prompt del codificador lo pide y en la prueba ya lo escribió así).
  - **El macrociclo no dice "firme", "provisional" ni "preliminar"**: escribe la línea «Fases posteriores
    por confirmar según tu progresión:» (en la prueba, exacta y sin ninguna de esas palabras; lint en el
    log si vuelven). El aviso de la semana 17 tampoco las usa.
  - **La fuerza declarada es del atleta**: cada fase la sirve (en la prueba, "ganar músculo" en las cinco).
  - **La nota de la semana se queda como estaba** y se verificó: el coach preguntó si la bici se podía mover
    y cómo de suave era, no la programó en un día sin bici, y el miércoles dice cómo recortar la sesión si la
    hace.
  - La regla de la víspera sigue sin mirar las carreras C (decidido: no).
  - La semana de la prueba: afinamiento con −26 % de horas, 3x3' a ritmo de 10K el miércoles, rectas lunes y
    viernes, descanso martes, jueves y el domingo de después, una fuerza mínima, y el 10K con su plan por
    kilómetros (5:28-5:32 los dos primeros, 5:24-5:28 hasta el 8) para su objetivo de 54:00.
- **El día de una carrera está disponible para ella** (Hugo, 2026-10-09; ver 02): su trail del domingo,
  con los domingos de descanso, acabó quitado y con una simulación con cuestas la víspera. Arreglado en la
  disponibilidad, el prompt, la corrección de la decisión, la carga segura, la regla de la víspera y el
  check-in. Sus datos están en producción y NO se leyeron: la cadena se reconstruyó desde el código y sus
  capturas. **Su semana de producción sigue como está** hasta que se despliegue y se regenere.
  Visto y NO tocado, para decidir: la regla de la víspera no mira las carreras C (`_taper_window_days`
  da 0) aunque el prompt sí les da afinamiento (6 días); y una actividad de la nota de la semana en un día
  de descanso no se programa salvo que abra el día en el check-in.
- **Las horas no se leían en el texto de un paso** (prueba local pagada del 2026-10-09, 0,26 USD, José con
  un trail de prueba en su sábado de descanso): `_duracion_como_dice_el_paso` leyó «50 min» en «~1 h 50 min a
  tope» y puso 3.000 s en la carrera de 6.600. Le pasa a cualquier paso escrito «1 h 30'» (una bici larga a 30'
  en el reloj). Arreglado (`_HORAS_EN_TEXTO`); en los logs locales sólo pasó esa vez, los de producción no se
  han mirado. La misma prueba confirmó el día de carrera: la carrera en su día de descanso, víspera suave,
  recuperación el día después y carga «normal».
- **Última prueba antes de fusionar (2026-09-25, semana real de José del 26-sep al 4-oct)**:
  - Plan: el interruptor de la semana automática es una fila pequeña, y la tabla de zonas y ritmos ya no
    sale en la pantalla (sigue en la hoja imprimible, que la escribe de sus propios datos).
  - El aviso de carga sólo lo ve el atleta en semana EXTREMA, con un texto directo. Y «siete días seguidos
    sin descanso» contaba días con entreno: su bloque de nueve días, con descanso los dos sábados, lo decía.
    Su aviso guardado en local se vació (copia en el scratchpad de la sesión).
  - El paso de fuerza recibía «Acaba de competir: la semana es de recuperación» a secas y bajó a mínima sus
    tres fuerzas, cuando sus ~2 días fáciles tras el 5K del 20/09 habían pasado antes de la semana (la decisión
    sí lo sabía). Ahora recibe lo mismo que la decisión (`paso_de_fuerza.dias_faciles_que_quedan`).
  - Visto y NO tocado: Pierna A dos veces y ninguna «Cuerpo completo» (la colocación protege su natación CSS
    del viernes del torso y del cuerpo completo del jueves; es la regla de 02, no un fallo); su petición de
    natación no llegó porque su cuaderno local está vacío.
- **Dos semanas como mucho y «Tu mes»** (decisión del responsable, 2026-09-24): `ventana_de_generacion` no
  deja escribir una tercera semana por delante (`SEMANAS_POR_DELANTE = 1`; "generar de todas formas" no lo
  salta), y la pantalla de Plan enseña «Tu mes», las cuatro semanas del macrociclo sin IA (`semanasDelMes`).
- **Segunda vuelta del 2026-09-24** (la pidió el responsable):
  - **Contraseña de Garmin en el primer inicio de sesión**: guardarla hacía UPDATE y, sin fila de perfil
    (se importa antes de pulsar Guardar), no guardaba nada con "saved: false" y la pantalla decía
    "guardada". Ahora INSERT ... ON DUPLICATE KEY y la pantalla dice lo que responde el servidor.
  - **Fechas**: cada fecha llega al modelo ya escrita (`se_escribe`: "viernes 25/09"), la directiva
    prohíbe calcular días de la semana y, si aun así uno no cuadra, vuelve a la decisión.
  - **Fuerza con objetivo** (Jessie, ~0,33 USD): cambiar a "rendir más" con mancuernas dio un programa
    coherente (45 min, superseries de 2, unilateral y tobillo) y la semana lo usó entero. La semana leía
    el objetivo VIEJO del macrociclo: ahora recibe lo que declara hoy y el paso de fuerza sabe cuál manda.
  - **Semana automática y la cola**: evaluación en `operacion/DEPLOYMENT.md`. Arreglado que diera por
    perdida a los 60 min una generación todavía en cola (habría pagado otra).
  - **Fusión con main**: lista de comprobación en `operacion/DEPLOYMENT.md` («Antes de fusionar la 2.1»).
  - Visto y NO tocado, para decidir: la semana de Jessie metió una tirada de 90' con la más larga del mes
    en 60' sin que nadie lo pidiera; hoy "alta" sólo se avisa y sólo "extrema" (>100 %) vuelve a la
    decisión. Y un día mal fechado ahora abre la vuelta de corrección (~0,13 USD cuando pasa).
- **Auditoría completa de la app (2026-09-24, navegador como José y Miguel, escritorio y 375 px)**; lo
  arreglado, en `tests/test_auditoria_del_24_de_septiembre.py` y los commits de ese día:
  - Plan enseñaba sólo la ÚLTIMA semana: con la siguiente generada, la de hoy desaparecía. Ahora la en curso
    y la siguiente.
  - El tope de dos semanas se dice antes del check-in.
  - Inicio decía «no tienes plan» mientras cargaba.
  - El expediente cerraba entera una semana sustituida a mitad (`lo_vivido`), nunca volvía a medir el
    fin de semana de la que se cerraba antes de terminar (`semana_a_completar`) y guardaba para siempre
    las semanas borradas sin medir (`descartar_las_no_vividas`). La línea 21-27 sep del atleta 2 se reparó
    (copia en `track_record.antes-de-reparar-2026-09-24.jsonl`).
  - «Rehacer semana» no existe desde la v1.1 y el aviso de carga y el chat mandaban ahí.
  - Fechas ISO y decimales con punto en textos al atleta (directiva de idioma, chat, ventana, Analytics,
    Salud, Cuaderno, Perfil, PDF).
  - El PDF imprimía todas las semanas guardadas; Historial enseñaba la hora de archivado como la de
    generación; el check-in hablaba de «la semana pasada» mientras enseñaba la siguiente.
  - Prueba pagada después (0,58 USD): macrociclo 0,31 y primera semana 0,27, con los arreglos y la
    natación pedida (3.500 m el viernes). Un error del modelo: «viernes 26/09» con el viernes en 25; no se
    corrige (no se sabe si falla el día o la fecha), se AVISA en el `worker.log`
    (`number_audit.dias_mal_nombrados`).
  - Visto y NO tocado: un «por qué» de las preguntas del coach lleva «(2026-10-24)»; el prompt de las
    preguntas no se toca sin el responsable.
- **Semana automática** (Beta 2.1, 2026-09-24; ver 02 y `operacion/DEPLOYMENT.md` §4): interruptor en Plan,
  Database/027, script por hora. **En producción hace falta que Miguel configure el cron** tras desplegar la
  2.1 y correr la 027. Probado en local el interruptor y una vuelta en seco; **la generación automática de
  punta a punta todavía no se ha pagado** (se hará en la prueba que viene tras la auditoría).
- **«Rehacer semana» no existe desde la v1.1** y el aviso de carga (escrito el 2026-09-24) y el chat mandaban
  ahí: ahora dicen «Eliminar plan» → esta semana y volver a generarla, o pedirle al coach que lo anote.
- **Fechas de las carreras verificadas** (2026-09-24, calendario real del atleta 2; ver 02): el macrociclo
  recibe su semana 1 dicha (antes "hoy", y contaba desde el lunes), las carreras ya corridas sin número de
  semana, y la semana recibe cuántas semanas faltan para cada carrera y cuál ya se corrió. La lista de carreras
  de la semana se contaba 3 días desde HOY y perdía la carrera de la nota de recuperación: ahora 21 días desde
  el inicio de la semana. `tests/test_las_fechas_de_las_carreras.py`.

- **Lo que pide de una sesión es una petición** (Fernando, 2026-09-22; ver 02): las «Preferencias de
  entrenamiento» del cuaderno van a la semana junto a lo que se respeta tal cual, las horas de referencia
  separan resistencia de fuerza y la media no es un techo. Sin migración: vale para los perfiles que ya hay.
- **Fuerza auditada (2026-09-23; ver 02)**: sin avisos falsos de pierna, Garmin del catálogo, tarjeta sin
  párrafos (etiquetas con el porqué a un toque), texto del día escrito por el paso de fuerza, minutos sin
  doble recorte y registro que sólo guarda lo que el atleta escribe. Tres semanas reales de José (~0,70 USD).
- **Leer los kilos del reloj** existió del 2026-09-23 al 2026-10-11 y se retiró en la Beta 3.0 (F0; ver 02).
- **Umbral de ~1 hora también para el de Garmin** y **−18 % de costo por semana** escribiendo sólo lo que se
  lee (2026-09-23; ver 02).

- **Test de umbral** (Perfil → Avanzado, Database/023): protocolo de Friel para carrera y bici que va al reloj
  como entreno (15' + 10' + 20' + 10', los dos pasos del test dicen SIN PARAR) y se lee sólo por su
  `workoutId`; CSS en natación. Manda el test más reciente. Zonas de fábrica detectadas.
- **Adherencia por `workoutId`**: lo hecho con el entreno del reloj se empareja exacto; lo hecho de memoria
  sigue contando por fecha, deporte y duración.
- **Check-in** (Database/021, 022, 024): nota de la semana, días que no puede, y días que su tabla no le da y
  esta semana SÍ (con qué deportes). Empieza en blanco cada generación, enseña las fechas de la semana que se va
  a generar (`Frontend/src/utils/semana.js`) y "Saltar" genera sin ningún check-in.
- **Afinamiento**: en la ventana de una carrera los FIJO pasan a POSIBLE (tabla, regla 4 y nota de carga).
  Semana de después de una carrera con su nota de recuperación.
- **Macrociclo**: cada carrera llega con su semana; el prompt dice que una carrera se prepara ANTES, sin fijar
  cuántas semanas; aviso en el log si una fase empieza en su carrera; la disponibilidad no es una `constraint`.
- **Codificador**: recuperación con unidad, temperatura 0, catálogo de ejercicios sólo con fuerza.
- **La decisión se comprueba contra la disponibilidad antes de codificar** y se le devuelve una vez; la red que
  convierte una sesión en descanso lo dice en el día y en el racional; el nombre del día sale de la fecha real;
  las preguntas del coach reciben los días de la semana. La semana guardada de José (2026-09-15) es anterior a
  esto y tiene el martes vacío (se reescribe eliminándola y generándola de nuevo).

## Pendiente — Fase B (merge y relanzamiento), con el responsable

En `planes/PLAN_DE_ACCION_BETA_2_0.md`, Fase B, en este orden: B0.5-B0.6 (fetch, fast-forward de `main`,
`git worktree prune` —`.git/worktrees/merge-prueba` está huérfano—, `git merge-tree` con `JwtUtils.php` como
único conflicto esperado) → B1 respaldo → B2 código → **B3 retirar entrenos de Garmin ANTES de borrar nada** →
B4 migraciones 013-024 y reinicio, vaciar `_runtime/`, **rotar `BRIDGE_SERVICE_TOKEN` y la contraseña de la
base** → B5 prueba de humo → B6 primeras 48 h. Decisiones abiertas D1-D5 (hora, tope por atleta, generaciones
simultáneas, contraseñas de Garmin sin guardar, CORS) en `planes/PLAN_LANZAMIENTO_BETA_2_0.md` §1.

- **Modelos aplicados (2026-09-23, decisión del responsable)**: resumidores, síntesis, chat y paso de fuerza en
  GPT-6 Luna; macrociclo en GPT-6 Sol; el cuaderno con rol propio en flash-lite; decisión, expertos y
  preguntas siguen en gpt-5.2. **El codificador volvió a Haiku el 2026-09-24**: el banco de 14 semanas reales
  (42 salidas de gemini-3.5-flash-lite juzgadas sesión a sesión contra Haiku y la decisión) le vio omitir
  strides y progresiones en ~21 sesiones, "4x20\" progresivos" como 20 metros y una bici de 6 × 1' codificada
  como 6 × 50' (332' en el reloj por 70'). Scripts: `banco_codificador.py`, `pares_codificador.py`. Tabla en 02. Macrociclo real del atleta 2 con
  ellos: 0,339 USD (antes ~0,41), y respeta su petición de natación; su semana: 0,300 USD (antes ~0,34), sin
  reparación pagada, carga «alta» (+65 % por su natación) con el aviso. Chat: 0,0025 USD por mensaje.
- **Carga segura** (2026-09-23; ver 02): límite en la extrema; desde el 2026-09-25 el atleta sólo ve el aviso si es extrema.
- **Beta 2.1 = UNA sola subida desde `pruebas-locales`** (decisión del responsable, 2026-09-24): todo lo de Fercho,
  umbral, carga segura, modelos, la fuerza de la Beta 2.1 y los arreglos de la auditoría se prueban en local desde
  esa rama, y se fusiona entera en `main` cuando él lo diga. Los dos arreglos hechos en `main` el 2026-09-21
  (check-in al coach, semanas del macrociclo) ya están en `pruebas-locales`. **Al fusionar hay que conservar el
  commit de `main` 0bb40c0 ("restaurar configuración de producción"):** quita el `dev-login`, el `.env.example`
  local y lo demás que sólo existe para probar aquí. La rama `ensayo-fercho` (sólo local) queda obsoleta.

- **Auditoría del cerebro del 2026-09-24** (prompts reales de la última semana y macrociclo del atleta 2, nodo
  por nodo; commits `4c6a3ae` y anteriores del día): la cadena decisión → Haiku replicada en 6 semanas reales
  cuadra minutos y metros y respeta la natación pedida; arreglados el plan anterior por su primer paso, el
  triatlón supuesto, la carrera corrida como próxima, el expediente con semanas futuras, el macrociclo duplicado
  al refinar, la hidratación sin anotar, el recorte de la fuerza de su rutina (réplicas: normal 3/3 en semana
  normal, reducida 3/3 tras su 5K), el aviso de volumen contra carga, las zonas de fábrica y dos mensajes que
  mandaban al chat a hacer lo que no hace. Alta con Avanzado recomendado al terminar y botones flotantes que ya
  no tapan (el "no deja seleccionar el coach" en táctil era eso).

## Esperando decisión del responsable (vistos, NO tocados a propósito)

- **Solicitud Starter a Garmin preparada (2026-10-22)**: todo en `contexto/garmin/SOLICITUD_STARTER.md` (respuestas
  del formulario, revisión en dos rondas, compromisos de la app nueva) y la web pública (landing, privacidad y
  términos ES/EN) en la rama `web-publica` (carpeta `../wt-web-publica`). Decisiones del responsable: nombre y
  dominio nuevos, la solicitante es la S.A. del socio y **Power Coach AI queda como prueba interna**. **Abierto**:
  qué hacer con los atletas de Power Coach AI conectados por contraseña antes de enviar (ver el documento).

- **La API oficial de Garmin (programa reabierto el 2026-10-21): viable con condiciones** (diagnóstico del
  2026-10-22, leído en developer.garmin.com y en el acuerdo FRM-0952 Rev. B). Tres niveles:
  - **Personal**: gratis, sólo lectura, «no para apps comerciales con varios usuarios».
  - **Starter**: gratis, ~1.000 usuarios según DC Rainmaker. Activity, Health y Training (subir entrenos y
    planes al calendario), entrega por push y **sin históricos**.
  - **Enterprise**: 1.000 USD de entrada y tarifa por usuario activo negociada, ~30 días de históricos.

  **Lo que NO aparece en ninguna** y hoy usamos: la carga de Garmin (EPOC), el estado de entrenamiento y el Load
  Focus, la Training Readiness, las predicciones de carrera, el umbral de lactato del reloj y la tabla de zonas
  configurada (la Personal sí la tiene).

  **El acuerdo permite IA** con una «AI Transparency Statement» en la privacidad, consentimiento explícito y
  forma de retirarlo (15.10). Los subcontratistas los aprueba Garmin (4.1), y «derive income … without
  Garmin's prior written permission» (5.2.e) hay que preguntarlo.

  **Nuestro camino actual** (garminconnect 0.2.25 + garth 0.5.2, con la contraseña del atleta) depende de una
  librería abandonada desde marzo de 2026. Los `worker.log` locales desde septiembre sólo muestran tokens
  reanudados: **no está comprobado que un inicio de sesión NUEVO funcione** (un atleta nuevo podría no poder
  conectar).

- **Semanas encadenadas y "mes de entrenamiento"** (2026-09-24): hoy se pueden generar semanas futuras sin
  límite. Propuesta: como mucho la semana en curso y la siguiente; el "mes" como VISTA de lo que el macrociclo
  prevé (sin IA), y generación semanal automática para quien no quiere hacerlo a mano. Ver la respuesta de la
  sesión.

De la auditoría del 2026-09-23 (generaciones reales de José y Jessie y banco de modelos; ver 02):

- **Peticiones y carga total**: con la natación pedida y los otros deportes intactos, la semana de José sube
  a +21-41 % de carga sobre su crónica. El macrociclo (gpt-5.2) sigue proponiendo, 3 de 5 réplicas, "progresar
  gradual" o "compensar". La regla actual deja que la evidencia real la limite (dolor, recuperación,
  afinamiento) pero no "hacía menos". Si ese salto preocupa, es criterio suyo.

- **Caché de GPT-6 en el libro de costos**: el chat en gpt-6-luna reporta `cache_creation` (18.057 tokens en el
  primer mensaje) y `estimate_cost_usd` lo cobra a 1,25× la entrada, que es la tarifa de ANTHROPIC. Si OpenAI no
  cobra la escritura, el libro sobreestima ~0,0005 USD por mensaje. Mirarlo contra la factura de OpenAI antes de
  tocarlo.

De la auditoría de UX, UI y bugs del 2026-09-23 (navegador, José y Miguel, escritorio y móvil). **Arreglados el
2026-09-24**: el enlace a Salud, las pestañas en móvil, el administrador en el asistente, el "Cargando…" de
Informes, el texto de la adherencia, la etiqueta del deporte y la fecha del día (`utils/deportes.js`), el texto
de cada repetición (`_work_description`, también sin paréntesis), los estados de Garmin y las comas de Analytics
(`utils/garmin.js`) y las tildes de las zonas. Siguen abiertos: el alta sin Avanzado y las semanas encadenadas sin entrenar (topadas en dos el 2026-09-24).
Cerrados después: «Eliminar» junto a «Guardar» y los botones flotantes (2026-09-24; seguían aquí como abiertos) y
el umbral del perfil contra el ajustado (Beta 3.0, F10, 2026-10-11: bajo el campo, «Tu plan usa 5:42/km: el de
Garmin (5:53) ajustado a un esfuerzo de ~1 hora…», leído de `summary.prescription`, que ahora guarda también
`umbral_ajustado_desde`).
La lista original:

- **El alta no pasa por Avanzado** (Fisiología con zonas y test de umbral, Equipo y entorno, Estilo de vida,
  Salud): son 3 pasos a propósito (Salud salió del alta en la v1.1). Decidir si se ofrece al terminar.
- **"Declarar una lesión (opcional)" de la Bienvenida no lleva a Salud**: enlaza a `?tab=salud` y el perfil sólo
  acepta `identidad|objetivos|historial|avanzado` (`ProfileView.vue`, el `watch` de `route.query.tab`).
- **En el teléfono la pestaña Avanzado queda fuera de pantalla** (la barra se corta en "HISTOR"), ni estando dentro.
- **El administrador con perfil al 0 % cae en el asistente de atleta** cada vez que abre o recarga cualquier
  pantalla, también Métricas y Usuarios (`MainLayout.llevarAlPasoAPasoSiEsLaPrimeraVez` no mira el rol).
- **Informes dice "Genera una semana" mientras carga el plan** (~4 s): falta un estado de carga (`planLoaded`).
- **Adherencia dice "aparece cuando generes una segunda semana"** con tres generadas: aparece cuando pasa un
  día planificado. Y nada impide encadenar semanas futuras sin haber entrenado ninguna (~0,30 USD cada una).
- **La etiqueta del deporte del día es texto del modelo** (`day.discipline`): "Natacion" sin tilde, "Brick" en
  una carrera. Debería salir de `sport` con un nombre fijo. Las fechas del día van en ISO (2026-10-05).
- **Cada repetición repite la frase del bloque** ("8× · 8x50m técnica"): se lee como 64×50 en la pantalla y en
  el reloj, aunque el reloj recibe bien 8 × 50 m. Pasa con Haiku y con flash-lite: es del contrato del codificador.
- **Analytics enseña enums de Garmin** ("AEROBIC_HIGH_SHORTAGE", "OPTIMAL", "BALANCED") y decimales con punto
  ("0.9", "6.3h", "11.1 h"), también en Métricas; el resto de la app usa coma.
- (Cerrado, ver arriba) El perfil enseñaba el umbral de Garmin y el plan usaba el ajustado sin decirlo.
- (Cerrado el 2026-09-24) Botones flotantes que tapaban y "Eliminar" pegado a "Guardar".
- Textos sin tildes en la sección de zonas ("aqui", "veras", "numeros", "sesion").
- Arreglados ese día: el aviso de carga decía "Es lo que pediste" sin petición, y el informe de análisis abierto
  desde Informes decía "de tu Garmin" de un umbral ajustado (segunda copia de las tarjetas en `AiService/main.py`).

Salen de auditar las generaciones de Jessie del 2026-09-15 (A10 y A11 del plan de acción):

1. (Arreglado el 2026-10-11, ver arriba) **Molestia amplificada**: contestó "muy ligera molestia en el aquiles"
   a una pregunta del coach y la semana lo citó en todas las sesiones. La proporción al dolor sólo existe para la molestia del check-in con número; las
   respuestas al coach no pasan por ahí. Arreglarlo es tocar cómo llegan esas respuestas a los expertos (las
   preguntas no se tocan).
2. (Revisado el 2026-10-11, no se repite) **Una noche de HRV como motivo** en el racional.
3. (Arreglado el 2026-10-11) **Series con objetivo de ritmo sugeridas en cinta** (el ritmo del reloj en cinta no es fiable).
4. **Cuántas semanas específicas recibe una carrera B**: dos réplicas del macrociclo de Jessie dieron 1 y 4
   semanas para su trail. No se fija número en el prompt; mirar el primer macrociclo real tras relanzar.
5. (Revisado el 2026-10-11, no se repite con gpt-6-sol) El macrociclo escribía "sin piscina" como `limitacion`.
6. (Revisado el 2026-10-11, no se repite) El informe llamó "carrera objetivo" a una carrera B.
7. El **test de umbral del atleta 2** está programado en su reloj para el **2026-09-25** con el texto anterior (sin
   "SIN PARAR"): "Enviar el test a mi reloj" lo reemplaza, **sólo con su permiso**.
8. **La fuerza de la Beta 2.1, de punta a punta**: falta pagar temporada + semana en un perfil de prueba
   (~0,35-0,50 USD). El perfil local de José ya tiene un programa del coach confirmado (2 sesiones
   Upper/Lower de 5 grupos de 2, 45 min).
9. **¿Se le propone el programa de fuerza a quien no ha contestado?** (S8). Hoy no: quien no contesta sigue
   exactamente como antes. Se decide con datos de la beta.

## Fase C (después del lanzamiento, 1.2.2)

Mover o reacomodar sesiones desde la app sin regenerar (lo más grande que falta; verificar antes la
reprogramación de Garmin en un reloj real), predicciones de carrera propias desde el umbral medido, codificar
por día si hay copias entre días, registro de subidas en MySQL, quitar las compatibilidades de la Beta 1,
borrar las redes deterministas que no corrijan nada, aguas abiertas, métrica de la réplica que compare unidades.

## Herramientas con limitaciones conocidas

- `PaginaLocal/scripts/replay_node.py` busca los perfiles en `PaginaLocal/data/storage`; los de la app están en
  `AiService/_runtime/storage/u<id>` (copiar la entrada o cambiar la raíz).
- Con modelos GPT por la Responses API la réplica devuelve el JSON dentro de bloques de texto: hay que extraerlo
  para leerlo. Las trazas anteriores al 2026-09-23 no guardaban el esquema de ninguna llamada a GPT (la captura
  no leía la clase Pydantic que manda langchain-openai) y se replicaban como texto libre; ya se guarda. Para
  replicar una traza vieja hay que reponer su esquema (script de la auditoría: `reponer_esquemas.py`).
