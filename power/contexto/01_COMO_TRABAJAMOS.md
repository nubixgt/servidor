# Cómo trabajamos

Esto no es una guía de estilo: es lo que costó aprender en este repo, casi siempre con una generación pagada
tirada o un atleta de la beta recibiendo algo que no debía. Aplica a cualquier agente.

## 1. El responsable y cómo hablarle

- El responsable del producto escribe en español y se le contesta en español, claro y sin jerga innecesaria.
- Decide él: el merge, lo que cuesta dinero, lo que toca relojes o cuentas reales, lo que cambia la experiencia
  del atleta. Cuando algo es criterio suyo (un número de semanas, cuánto pesa una molestia), **se le presenta
  con la evidencia y se deja para decidir**, no se decide en silencio.
- Se informa con honestidad: qué se verificó y cómo, qué no se pudo verificar, qué costó (una réplica, una
  generación) y qué queda abierto. "Pasa las pruebas" no es lo mismo que "funciona con el atleta real".
- Nada que pidió se recorta sin decirlo. Si una parte no se hizo, se dice cuál y por qué.

## 2. Causa raíz, no parche

El patrón que más se repite: un síntoma en la salida del modelo y la tentación de corregirlo con una regla o
una expresión regular encima. Antes de eso:

1. **Mira el prompt real archivado** (`AiService/_runtime/storage/u<id>/reasoning/<ISO>/prompts/*.json`). La
   mayoría de las veces el modelo hizo lo razonable con lo que tenía:
   - le **faltaba el dato** (el macrociclo recibía fechas y contaba él las semanas; la semana no recibía el
     ritmo de la carrera);
   - el **esquema no podía expresarlo** (la recuperación sólo existía en segundos y metió ahí "200 m");
   - **dos textos se contradecían** y ganó el marcado como inviolable (la nota decía que los FIJO cedían en el
     afinamiento y la regla 4 seguía exigiéndolos);
   - un **dato llegaba mal etiquetado** (Garmin llama "chronic" al rango óptimo de la carga aguda, y el informe
     le dijo al atleta que su crónica estaba "en el rango óptimo").
2. Arregla ahí: el dato, el esquema, el contrato entre nodos, el texto contradictorio.
3. Si aun así hace falta una red determinista, es una **red con fecha de caducidad**: registra en el log cada
   vez que corrige y se borra si no corrige nada en dos semanas de beta (`_duracion_como_dice_el_paso`).
4. Un aviso que sólo informa es un **lint** (se escribe en el `worker.log`), no una puerta que bloquea o
   reescribe (`number_audit`, `fases_que_empiezan_en_su_carrera`).

**El fallo que se repite**: un campo que el consumidor nunca rellena, un valor por defecto que lo tapa y ningún
error en ninguna parte. Antes de dar por bueno que un dato llega, míralo en el prompt archivado.
`PaginaLocal/scripts/auditar_conexiones.py` comprueba las rutas; `AiService/tests/test_php_rellena_el_esquema.py`
y `PaginaLocal/tests/test_cables_del_checkin.py` fijan los nombres en cada salto.

## 3. Sesgos: no metas los que nadie pidió

El pipeline escribe para personas distintas. Una regla general metida en un prompt se aplica a todas.

- **Reglas del atleta sí, reglas de población no.** Una regla de recuperación vale si está anclada a la base
  del propio atleta, pide persistencia y una segunda señal. "Sueño < 75" para quien promedia 77 es ruido.
- **Lesiones: declaradas, no supuestas.** Un atleta sano recibe cero tokens sobre lesiones. La fuerza de la
  respuesta es la del dolor declarado: un 1-2/10 no reestructura una fase ni aparece en todas las sesiones.
- **El ACWR no decide nada** (no predice lesión): el guardarraíl que recortaba la semana se retiró.
- **Un número en negrita gana a cualquier matiz escrito debajo.** Si no es un objetivo, no se presenta como uno.
- **No fijes números de criterio en un prompt** (cuántas semanas prepara una carrera B, cuántas sesiones de
  fuerza): se da el principio y la evidencia, y se pide que el informe diga por qué ese número vale para ESE
  atleta. Si el resultado preocupa, se le lleva al responsable.
- **Los repartos de intensidad se presentan con su evidencia y sin decir cuál toca.** Una versión decía que
  entrenar entre umbrales "es la forma clásica de estancarse": era un sesgo.
- **Lo que el atleta pide manda sobre el deporte, el día y lo que pide de una sesión; el total de la semana y
  la intensidad son nuestros.** Un FIJO pide el deporte, no autoriza una sesión de calidad. "Natación de
  2-3 km" en su cuaderno es una petición: no se encoge para cuadrar unas horas
  ni se paga con los otros deportes; el sitio sale de que la semana crezca (Fernando, 2026-09-22). Lo que dice para ESTA semana (check-in) gana a lo que prefiere siempre
  (perfil).
- Antes de añadir una frase a un prompt, pregúntate a quién más le llega y qué haría con ella un atleta
  distinto del que motivó el cambio.

## 4. Abstenerse antes que adivinar

- Si no se sabe, se dice que no se sabe (sin zonas no se juzga la intensidad; sin potencia la bici no vota).
- Un emparejamiento dudoso devuelve `None` (imágenes de ejercicios, récords de Garmin por `typeId`).
- El test de umbral lee SÓLO la actividad hecha con su entreno (por `workoutId`); la versión que elegía entre
  actividades "la que pareciera un test" aceptó series y rodajes.

## 5. Verificar de verdad

- **Una generación que falló**: `AiService/_runtime/jobs/<job_id>/worker.log` (con `spec.json` y
  `status.json`). La consola del AiService no tiene nada de ella.
- **Qué recibió cada modelo**: `reasoning/<ISO>/prompts/`. **Qué decidió**: `week_intents.json`, `weekly_plan.json`.
- **Cambios de prompt o de modelo**: réplica del nodo contra el prompt real, con todo lo demás congelado
  (`PaginaLocal/scripts/replay_node.py`; su `STORAGE_ROOT` apunta a `PaginaLocal/data/storage`, así que para
  los atletas de la app hay que copiar la entrada del archivo o apuntarlo a `AiService/_runtime/storage`). Se
  compara con el número en la mano, no con la tabla de precios. Cuesta dinero: se avisa antes.
- **Pantallas**: se abre la app en el navegador con `dev-login` y se prueba el flujo, incluido guardar.
- **Frontend y Python con la misma lógica** (fechas de la semana, constantes del macrociclo): una prueba fija que
  las constantes coinciden y se comparan los resultados en varios casos.
- La suite corre sin modelos ni Garmin. **"Verificado contra la cuenta real" es evidencia más fuerte que "pasa
  las pruebas"**, y esa procedencia se guarda en el docstring de la prueba, con los números reales.

## 6. Pruebas

- Donde un fallo se convierte en un entreno que puede lesionar o en una generación pagada tirada.
- Cada fallo encontrado deja su prueba de regresión con los números reales en el docstring.
- **Una aserción no se parchea para que pase**: se vuelve a apuntar a la garantía que hay debajo.
- Se revisa el delta de `ruff` y `vulture` sobre su línea base, no el total.

## 7. Datos, dinero y cuentas reales

- MySQL es la fuente de la verdad; `AiService/_runtime/storage/u<id>/` es un espejo de usar y tirar, salvo
  `track_record.jsonl` y `reasoning/`. El registro de subidas `garmin_uploads.json` es lo único que dice qué
  entrenos del reloj son nuestros: no se borra antes de retirarlos.
- Nunca contraseñas en campos; nunca subir o retirar entrenos de un reloj real, ni tocar producción, sin
  permiso explícito para esa acción concreta. Aceptar términos sólo en las cuentas locales de prueba 1-3.
- Costos de IA: sólo el administrador los ve. Una generación ronda 0,20-0,30 USD; una réplica de un nodo,
  céntimos. Se dice antes de gastar.
- Migraciones a mano, tolerancia a que falten, y su comprobación en `verificar_migraciones.php`.

## 8. Entorno local (Windows)

- PowerShell 5.1: sin `&&`; `;` y `if ($?)`. Para scripts de Python largos, un archivo o `python - <<'EOF'`
  desde Bash, no `python -c` con comillas anidadas.
- Los servidores no se lanzan con `&` (quedan huérfanos). XAMPP da PHP y MySQL (`C:\xampp`).
- El frontend recarga en caliente; el PHP se relee en cada petición; el AiService hay que reiniciarlo para
  código del puente (el cerebro se importa en cada worker).
- `.env` de la raíz: `AI_MODE=standard` como producción (probar en `pro` es probar otros modelos). Hacen falta
  las TRES claves de proveedor.
