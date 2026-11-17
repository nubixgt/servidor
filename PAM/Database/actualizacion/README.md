# Actualización de bases (BD.json · SOcioE.json · CUR_detalle)

Enlaza las 3 bases nuevas con el sistema por la llave **`nounico`**:

| Archivo (raíz del proyecto) | Contenido | Tabla nueva |
|---|---|---|
| `BD.json` | Base actual (823,880) | `core_bd_actual` |
| `SOcioE.json` | Estudio socioeconómico (182,841) | `core_estudio_socioeconomico` |
| `amayor_CUR_detalle.sql` | Detalle CUR / fallecidos (690,878) | `core_cur_detalle` (+ vista `core_v_cur_fallecido`) |

`core_expediente` gana la columna `nounico` (única). No se borra ni se reemplaza ningún dato existente.

## Orden de ejecución (desde la raíz del proyecto)

0. **Respaldo** de la base: `mysqldump -u USUARIO -p NOMBRE_BASE > respaldo.sql`
1. Convertir los archivos (≈2 min, usa poca memoria):
   `python3 Database/actualizacion/convertir.py`  → crea `Database/actualizacion/salida/*.tsv`
2. `mysql -u USUARIO -p NOMBRE_BASE < Database/actualizacion/01_estructura.sql`
3. `mysql --local-infile=1 -u USUARIO -p NOMBRE_BASE < Database/actualizacion/02_cargar.sql`
   (si da error de `local_infile`: `SET GLOBAL local_infile = 1;`)
4. `mysql -u USUARIO -p NOMBRE_BASE < Database/actualizacion/03_enlazar.sql`  (varios minutos)

5. `mariadb -u USUARIO -p NOMBRE_BASE < Database/actualizacion/04_fallecidos_tablero.sql`  (tablero: la serie de fallecidos pasa a calcularse con el CUR por año de `mes_pago`; reconstruye los agregados)

Todos los scripts se pueden volver a ejecutar sin duplicar datos. Requiere MySQL 8.0+ / MariaDB 10.6+.

## Qué hace el enlace (03)
- Une `core_expediente` ↔ `BD.json` por No. Expediente + documento + fecha de solicitud + nombre, con 4 pasadas más flexibles; solo acepta pares 1 a 1 (resultados en `act_enlace_log`).
- Los registros de `BD.json` que no existen en el sistema se dan de alta con el flujo normal (`stg_matriz` → `stg_procesar_lote`); `nounico = '0'` (fila de regularizaciones financieras) se excluye.
- `SOcioE` y `CUR` reciben `expediente_id` por `nounico`. `es_fallecimiento = 1` marca las resoluciones de suspensión por fallecimiento (RENAP / "Resolución para Fallecidos").
- No se modifican estados ni fechas existentes; las diferencias de estado se listan (consulta 5.7). Hay un bloque opcional comentado para completar `fecha_fallecimiento`.
- Termina con `CALL mart_refrescar();`.

## Notas
- `SOcioE.json` trae nombres/domicilio enmascarados (`...`): no se cargan. Las respuestas `rb*/ch*/cts*` quedan en la columna JSON `respuestas`.
- Los estudios socioeconómicos son datos sensibles: si se exponen en la app, restringir con un permiso en `seg_permiso`.
- `salida/` (≈1 GB) no se versiona.
