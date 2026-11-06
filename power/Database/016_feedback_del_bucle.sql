-- Por qué el atleta borra un plan, y si entrenó la semana como esperaba.
--
-- Run ONCE, after 015.
--
-- ## Por qué hacía falta
--
-- La beta dejó el número que decide el MVP sin respuesta: **37 generaciones
-- pagadas y 0 semanas cerradas**, con 20 planes de temporada para 5 atletas.
-- Hugo generó 15. Eso no es uso, es reintento — y no hay una sola fila en toda
-- la base de datos que diga POR QUÉ.
--
-- `app_events` ya anota `plan_borrado` (3 veces sólo en local), así que se sabe
-- CUÁNTAS veces pasa y CUÁNDO. Lo que no se sabe es lo único accionable: si
-- borran porque el plan no tiene los días que querían, porque el volumen no
-- les cuadra, porque las sesiones no les convencen, o porque se lesionaron.
-- Cada una de esas cuatro respuestas lleva a un arreglo distinto, y sin la
-- pregunta se elige a ojo.
--
-- ## Por qué una columna y no una tabla
--
-- Porque el evento ya existe y ya está bien modelado: `app_events` tiene el
-- usuario, el momento y la ruta. Lo que falta es una palabra al lado. Una
-- tabla aparte obligaría a unir dos sitios para responder "de los que
-- borraron, cuántos fue por los días" y duplicaría la tolerancia a fallos que
-- `EventRepository::record()` ya tiene (se traga sus propios errores porque la
-- llama el router en cada petición).
--
-- `detail` queda genérico a propósito: hoy lo usa `plan_borrado`, mañana
-- cualquier otro evento que necesite una palabra de contexto.
--
-- ## Por qué el "¿entrenaste como esperabas?" va en el check-in
--
-- Porque el check-in ya se pregunta EXACTAMENTE en ese momento -- al generar
-- la siguiente semana, que es cuando la anterior se cierra -- y ya tiene la
-- pregunta abierta "¿qué se atravesó la semana pasada?". Lo que le faltaba era
-- la respuesta CONTABLE: con cuatro atletas, "a medias" en tres de cuatro
-- semanas es un dato; tres párrafos de texto libre no se pueden sumar.
--
-- NULL en las dos = no contestó, que es distinto de contestar que no.

-- 1) El motivo del borrado, junto al evento que ya se anota.
SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'app_events'
      AND COLUMN_NAME  = 'detail'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE app_events
        ADD COLUMN detail VARCHAR(191) NULL DEFAULT NULL
            COMMENT "Contexto del evento. En plan_borrado, por que lo borro."
            AFTER route',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Si la semana que se cierra salio como el atleta esperaba.
SET @existe2 := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_checkins'
      AND COLUMN_NAME  = 'week_as_expected'
);
SET @sql2 := IF(@existe2 = 0,
    'ALTER TABLE athlete_checkins
        ADD COLUMN week_as_expected VARCHAR(16) NULL DEFAULT NULL
            COMMENT "si | a_medias | no. NULL = no contesto."
            AFTER last_week_note',
    'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
