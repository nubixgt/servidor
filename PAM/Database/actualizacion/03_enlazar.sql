-- =====================================================================
-- 03 · ENLACE: une core_expediente con BD.json, socioeconomico y fallecidos por `nounico`.
--
-- Requiere haber ejecutado 01_estructura.sql y 02_cargar.sql.
-- Re-ejecutable. Pensado para correr con el cliente mysql (varias sentencias y DELIMITER):
--   mysql -u USUARIO -p NOMBRE_BASE < Database/actualizacion/03_enlazar.sql
-- Los pasos 2 y 4 pueden tardar varios minutos (820 mil filas).
-- Haz un respaldo (mysqldump) ANTES de ejecutar: este script modifica core_expediente
-- (solo la columna nounico) e inserta expedientes nuevos si BD.json trae registros que no existen.
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PASO 1 · Llaves auxiliares de BD.json (documento solo digitos y nombre normalizado)
-- ---------------------------------------------------------------------
UPDATE core_bd_actual
SET k_dpi = NULLIF(REGEXP_REPLACE(COALESCE(no_id, ''), '[^0-9]', ''), ''),
    k_nombre_norm = ref_f_norm(nombre)
WHERE k_nombre_norm IS NULL;

-- ---------------------------------------------------------------------
-- PASO 2 · Enlazar expedientes existentes (5 pasadas, de la mas estricta a la mas flexible)
-- ---------------------------------------------------------------------
CALL act_enlazar_nounico();
SELECT * FROM act_enlace_log ORDER BY id DESC LIMIT 5;

SELECT COUNT(*) AS bd_json_sin_enlazar FROM core_bd_actual WHERE expediente_id IS NULL AND nounico <> '0';

-- ---------------------------------------------------------------------
-- PASO 3 · Registros de BD.json que NO existen en el sistema -> se cargan con el flujo normal de
--          importacion (stg_matriz -> stg_procesar_lote). El nounico '0' es una fila de
--          "REGULARIZACIONES REALIZADAS POR AJUSTES FINANCIEROS" y se excluye.
--          Las filas con municipio o estado no catalogado quedan rechazadas (ver stg_import_error).
-- ---------------------------------------------------------------------
SET @sql_mode_orig = @@SESSION.sql_mode;
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'STRICT_TRANS_TABLES', ''), 'STRICT_ALL_TABLES', '');

INSERT INTO stg_import_lote(archivo, formato, modo, estado, progreso, mensaje)
SELECT 'BD.json (registros nuevos por nounico)', 'sql', 'INCREMENTAL', 'LEYENDO', 10,
       'Alta de expedientes presentes en BD.json y ausentes en el sistema'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM core_bd_actual WHERE expediente_id IS NULL AND nounico <> '0');
SET @lote_nuevos = IF(ROW_COUNT() = 1, LAST_INSERT_ID(), NULL);

INSERT INTO stg_matriz(lote_id, linea, departamento, municipio, no_expediente, nombre, dpi, fecha_nacimiento,
                       fecha_solicitud, telefono, direccion, fecha_observacion, ultima_observacion,
                       id_apoderado, no_id_apoderado, apoderado, visita_positiva, visita_negativa,
                       nomina_desde, nomina_hasta, fallecio, suspendido, fecha_ultima_resolucion,
                       tipo_resolucion, resolucion, fecha_estado_actual, estado_actual)
SELECT @lote_nuevos, ROW_NUMBER() OVER (ORDER BY b.nounico),
       b.departamento, b.municipio, b.expediente, b.nombre, b.no_id,
       DATE_FORMAT(b.nacimiento, '%d/%m/%Y'),
       DATE_FORMAT(b.solicitud, '%d/%m/%Y'),
       b.telefono, b.direccion,
       DATE_FORMAT(b.ultimo_observacion, '%d/%m/%Y'), b.observacion,
       b.t_id_apoderado, b.no_id_apoderado, b.nombre_apoderado,
       DATE_FORMAT(b.visita_pos, '%d/%m/%Y'), DATE_FORMAT(b.visita_neg, '%d/%m/%Y'),
       DATE_FORMAT(b.activo_desde, '%d/%m/%Y'), DATE_FORMAT(b.activo_hasta, '%d/%m/%Y'),
       DATE_FORMAT(b.fallecido, '%d/%m/%Y'), DATE_FORMAT(b.suspendido, '%d/%m/%Y'),
       DATE_FORMAT(b.ultima_resolucion, '%d/%m/%Y'),
       b.tipo_resolucionC, b.tipo_resolucionD,
       DATE_FORMAT(b.estado_final_fecha, '%d/%m/%Y'), b.estado_final_descripcion
FROM core_bd_actual b
WHERE @lote_nuevos IS NOT NULL AND b.expediente_id IS NULL AND b.nounico <> '0';

SET SESSION sql_mode = @sql_mode_orig;

-- Procesa el lote solo si hay registros nuevos (si no, @lote_nuevos es NULL y no se hace nada)
DELIMITER $$
DROP PROCEDURE IF EXISTS act_procesar_nuevos$$
CREATE PROCEDURE act_procesar_nuevos(IN p_lote INT UNSIGNED)
BEGIN
  IF p_lote IS NOT NULL THEN
    CALL stg_procesar_lote(p_lote, 0);
    CALL act_enlazar_nounico();   -- ahora los recien creados tambien reciben su nounico
    UPDATE stg_import_lote SET estado = 'COMPLETADO', progreso = 100, finalizado_en = NOW() WHERE id = p_lote;
  END IF;
END$$
DELIMITER ;
CALL act_procesar_nuevos(@lote_nuevos);
DROP PROCEDURE act_procesar_nuevos;

-- ---------------------------------------------------------------------
-- PASO 4 · Socioeconomico y fallecidos (CUR) -> expediente_id por nounico
-- ---------------------------------------------------------------------
UPDATE core_estudio_socioeconomico s
JOIN core_expediente e ON e.nounico = s.sc_NoUnico
SET s.expediente_id = e.id
WHERE s.expediente_id IS NULL;

UPDATE core_cur_detalle d
JOIN core_expediente e ON e.nounico = d.nounico
SET d.expediente_id = e.id
WHERE d.expediente_id IS NULL;

-- Resoluciones de suspension por fallecimiento (RENAP y "Resolucion para Fallecidos"), no las reactivaciones
UPDATE core_cur_detalle
SET es_fallecimiento = (HabilitaDeshabilita = 'D' AND Descripcion_Operacion LIKE '%allecid%');

-- ---------------------------------------------------------------------
-- PASO 5 · Resumen y verificacion
-- ---------------------------------------------------------------------
-- 5.1 Enlace de las 3 bases (los "sin expediente" son nounico que no existen en core_expediente)
SELECT 'BD.json' AS base, COUNT(*) AS total, SUM(expediente_id IS NOT NULL) AS enlazados,
       SUM(expediente_id IS NULL) AS sin_expediente FROM core_bd_actual
UNION ALL SELECT 'Socioeconomico', COUNT(*), SUM(expediente_id IS NOT NULL), SUM(expediente_id IS NULL)
  FROM core_estudio_socioeconomico
UNION ALL SELECT 'CUR detalle', COUNT(*), SUM(expediente_id IS NOT NULL), SUM(expediente_id IS NULL)
  FROM core_cur_detalle;

-- 5.2 Expedientes del sistema con / sin nounico (esperado: sin nounico = solo los creados en el sistema)
SELECT COUNT(*) AS expedientes, SUM(nounico IS NOT NULL) AS con_nounico, SUM(nounico IS NULL) AS sin_nounico
FROM core_expediente;

-- 5.3 Enlaces por metodo
SELECT metodo_enlace, COUNT(*) AS filas FROM core_bd_actual GROUP BY metodo_enlace ORDER BY filas DESC;

-- 5.4 nounico duplicados en core_expediente (esperado: 0 filas)
SELECT nounico, COUNT(*) FROM core_expediente WHERE nounico IS NOT NULL GROUP BY nounico HAVING COUNT(*) > 1;

-- 5.5 Fallecidos segun CUR: expedientes con resolucion de fallecimiento y su estado actual
SELECT s.nombre AS estado_actual, COUNT(*) AS expedientes
FROM core_v_cur_fallecido f
JOIN core_expediente e ON e.id = f.expediente_id
JOIN ref_cat_estado s ON s.id = e.estado_id
GROUP BY s.nombre ORDER BY expedientes DESC;

-- 5.6 Registros de BD.json rechazados al darlos de alta (municipio/estado/nombre invalidos)
SELECT regla, COUNT(*) AS filas FROM stg_import_error
WHERE lote_id = @lote_nuevos AND nivel = 'ERROR' GROUP BY regla;

-- 5.7 Diferencias de estado: BD.json vs sistema (solo informativo, NO se modifica nada)
SELECT b.estado_final_descripcion AS estado_bd_json, c.nombre AS estado_sistema, COUNT(*) AS expedientes
FROM core_bd_actual b
JOIN core_expediente e ON e.id = b.expediente_id
JOIN ref_cat_estado c ON c.id = e.estado_id
WHERE c.texto_origen <> b.estado_final_descripcion
GROUP BY b.estado_final_descripcion, c.nombre ORDER BY expedientes DESC;

-- ---------------------------------------------------------------------
-- OPCIONAL (descomentar si se desea): completar la fecha de fallecimiento de core_expediente
-- con la de BD.json donde hoy esta vacia. No pisa fechas existentes.
-- ---------------------------------------------------------------------
-- UPDATE core_expediente e JOIN core_bd_actual b ON b.expediente_id = e.id
-- SET e.fecha_fallecimiento = DATE(b.fallecido), e.actualizado_en = e.actualizado_en
-- WHERE e.fecha_fallecimiento IS NULL AND b.fallecido IS NOT NULL;

-- ---------------------------------------------------------------------
-- PASO 6 · Refrescar el tablero (agregados)
-- ---------------------------------------------------------------------
CALL mart_refrescar();
