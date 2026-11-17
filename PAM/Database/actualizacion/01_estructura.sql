-- =====================================================================
-- 01 · ESTRUCTURA: agrega `nounico` a core_expediente y crea las tablas nuevas.
--
-- Ejecutar sobre la base del sistema (la misma donde estan core_expediente,
-- core_persona, etc.). Se puede ejecutar mas de una vez sin dañar nada
-- (no borra datos existentes).
--
-- Convencion de charset: las columnas llave (nounico, expediente, k_dpi,
-- k_nombre_norm) son latin1 igual que core_expediente/core_persona, para que
-- los JOIN usen indice. El resto del texto es utf8mb4 (conserva tildes y Ñ).
-- Requiere MySQL 8.0+ / MariaDB 10.6+.
-- =====================================================================
SET NAMES utf8mb4;
DELIMITER $$

-- ---------------------------------------------------------------------
-- 1) core_expediente.nounico (llave que enlaza las 3 bases)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS act_agregar_nounico$$
CREATE PROCEDURE act_agregar_nounico()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'core_expediente' AND COLUMN_NAME = 'nounico') THEN
    ALTER TABLE core_expediente
      ADD COLUMN nounico VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL AFTER clave_origen,
      ADD UNIQUE KEY exp_nounico (nounico);
  END IF;
END$$
CALL act_agregar_nounico()$$
DROP PROCEDURE act_agregar_nounico$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 2) core_bd_actual · BD.json completo (80 campos) + columnas auxiliares de enlace
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core_bd_actual(
  nounico VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  solicitud DATE NULL,
  visita_pos DATE NULL,
  visita_neg DATE NULL,
  activo_desde DATE NULL,
  activo_hasta DATE NULL,
  fallecido DATETIME NULL,
  suspendido DATE NULL,
  ultima_resolucion DATETIME NULL,
  tipo_resolucionC VARCHAR(10) NULL,
  tipo_resolucionD VARCHAR(255) NULL,
  reprogramado DATE NULL,
  ultimo_movimiento DATETIME NULL,
  descripcion_movimiento VARCHAR(255) NULL,
  ultimo_observacion DATETIME NULL,
  observacion TEXT NULL,
  fcrea DATETIME NULL,
  ucrea VARCHAR(50) NULL,
  progra_visita DATE NULL,
  reprogra_visita DATE NULL,
  orden VARCHAR(20) NULL,
  registro VARCHAR(40) NULL,
  upago DATE NULL,
  saldo DECIMAL(14,2) NULL,
  t_id VARCHAR(10) NULL,
  no_id VARCHAR(40) NULL,
  nombre VARCHAR(255) NULL,
  telefono VARCHAR(100) NULL,
  direccion TEXT NULL,
  departamento VARCHAR(80) NULL,
  municipio VARCHAR(150) NULL,
  nacimiento DATE NULL,
  estado CHAR(1) NULL,
  estado_final_fecha DATE NULL,
  estado_final_descripcion VARCHAR(80) NULL,
  genero VARCHAR(30) NULL,
  estado_civil VARCHAR(40) NULL,
  edad SMALLINT NULL,
  expediente VARCHAR(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  etnia VARCHAR(120) NULL,
  lengua VARCHAR(80) NULL,
  nombre_apoderado VARCHAR(255) NULL,
  t_id_apoderado VARCHAR(10) NULL,
  no_id_apoderado VARCHAR(40) NULL,
  nacimiento_apoderado DATE NULL,
  departamento_apoderado VARCHAR(80) NULL,
  municipio_apoderado VARCHAR(150) NULL,
  telefono_apoderado VARCHAR(100) NULL,
  direccion_apoderado TEXT NULL,
  calidad_apoderado VARCHAR(60) NULL,
  certificado DATE NULL,
  certificado_vence DATE NULL,
  apoderado DATE NULL,
  apoderado_vence DATE NULL,
  servicios VARCHAR(10) NULL,
  pensionado VARCHAR(10) NULL,
  trabaja VARCHAR(10) NULL,
  perdio_nac VARCHAR(10) NULL,
  tramite_ant VARCHAR(10) NULL,
  servicios_cual VARCHAR(255) NULL,
  pensionado_cual VARCHAR(255) NULL,
  trabaja_cual VARCHAR(255) NULL,
  autentica_dpi VARCHAR(10) NULL,
  certificacion_nac VARCHAR(10) NULL,
  discapacidad VARCHAR(10) NULL,
  fisica VARCHAR(10) NULL,
  psiquica VARCHAR(10) NULL,
  sensorial VARCHAR(10) NULL,
  discapacidad_cual VARCHAR(255) NULL,
  fecha_pago DATE NULL,
  hora_pago VARCHAR(20) NULL,
  monto_pagado DECIMAL(14,2) NULL,
  cobra VARCHAR(60) NULL,
  agencia_pago VARCHAR(255) NULL,
  depto_pago VARCHAR(80) NULL,
  muni_pago VARCHAR(150) NULL,
  boleta VARCHAR(80) NULL,
  secuencial_banco VARCHAR(80) NULL,
  agencia_codigo VARCHAR(40) NULL,
  usuario_banco VARCHAR(60) NULL,
  -- auxiliares (no vienen del archivo; los llena 03_enlazar.sql)
  k_dpi VARCHAR(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  k_nombre_norm VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  expediente_id BIGINT UNSIGNED NULL,
  metodo_enlace VARCHAR(30) NULL,
  PRIMARY KEY (nounico),
  KEY bda_expediente (expediente, k_dpi),
  KEY bda_dpi (k_dpi),
  KEY bda_nombre (k_nombre_norm),
  KEY bda_exp_id (expediente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3) core_estudio_socioeconomico · SOcioE.json
--    Columnas principales tipadas; el bloque rb*/ch*/cts* va en `respuestas` (JSON).
--    Los nombres/domicilio del archivo vienen enmascarados ("...") y no se cargan.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core_estudio_socioeconomico(
  sc_NoUnico VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  sc_noexp VARCHAR(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  sc_depto INT NULL,
  sc_reg BIGINT NULL,
  sc_fecha_est DATETIME NULL,
  sc_hora VARCHAR(10) NULL,
  sc_cod_idi INT NULL,
  sc_cod_ts INT NULL,
  sc_traductor VARCHAR(5) NULL,
  sc_cod_etn INT NULL,
  sc_cod_etnc INT NULL,
  sc_edadc INT NULL,
  sc_ponderacion INT NULL,
  sc_GT INT NULL,
  sc_MinPond INT NULL,
  sc_DenAprob VARCHAR(5) NULL,
  sc_procesada VARCHAR(5) NULL,
  sc_fechac DATETIME NULL,
  sc_fecham DATETIME NULL,
  sc_usuarioc VARCHAR(60) NULL,
  sc_usuariom VARCHAR(60) NULL,
  sc_stado VARCHAR(5) NULL,
  sc_transferido VARCHAR(5) NULL,
  sc_cod_dir INT NULL,
  sc_cod_uni INT NULL,
  sc_cod_per INT NULL,
  sc_datoTS INT NULL,
  lee_escribe VARCHAR(5) NULL,
  nivel_estudio VARCHAR(5) NULL,
  urbano_rural VARCHAR(5) NULL,
  observaciones TEXT NULL,
  observaciones_ponderacion TEXT NULL,
  respuestas LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (respuestas IS NULL OR json_valid(respuestas)),
  expediente_id BIGINT UNSIGNED NULL,
  PRIMARY KEY (sc_NoUnico),
  KEY ses_noexp (sc_noexp),
  KEY ses_exp_id (expediente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4) core_cur_detalle · amayor_CUR_detalle.sql (documentos/operaciones; incluye fallecidos/RENAP)
--    es_fallecimiento = 1 en las resoluciones de suspension por fallecimiento (se llena en 03).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core_cur_detalle(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nounico VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  fecha_documento DATETIME NULL,
  monto DOUBLE NULL,
  dias INT UNSIGNED NULL,
  tipo_documento VARCHAR(5) COLLATE utf8mb4_bin NULL,
  tipo_operacion VARCHAR(5) COLLATE utf8mb4_bin NULL,
  HabilitaDeshabilita VARCHAR(5) COLLATE utf8mb4_bin NULL,
  Descripcion_Operacion VARCHAR(150) NULL,
  periodo_del DATETIME NULL,
  periodo_al DATETIME NULL,
  mes_pago DATETIME NULL,
  vence_pago DATETIME NULL,
  cur INT UNSIGNED NULL,
  fechac DATETIME NULL,
  usuarioc VARCHAR(50) NULL,
  expediente_id BIGINT UNSIGNED NULL,
  es_fallecimiento TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY cur_nounico (nounico),
  KEY cur_exp_id (expediente_id),
  KEY cur_cur (cur),
  KEY cur_fall (es_fallecimiento, nounico)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Resumen de fallecimientos segun el detalle CUR (un registro por expediente)
CREATE OR REPLACE VIEW core_v_cur_fallecido AS
SELECT d.nounico, d.expediente_id,
       MIN(d.fecha_documento) AS primera_resolucion,
       MAX(d.fecha_documento) AS ultima_resolucion,
       COUNT(*) AS resoluciones
FROM core_cur_detalle d
WHERE d.es_fallecimiento = 1
GROUP BY d.nounico, d.expediente_id;

-- ---------------------------------------------------------------------
-- 5) Bitacora de la actualizacion (resultado de cada pasada de enlace)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS act_enlace_log(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ejecutado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  metodo VARCHAR(40) NOT NULL,
  enlazados INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6) Procedimientos de enlace  core_expediente  <->  core_bd_actual (por nounico)
--    Cada pasada solo acepta pares 1 a 1 (si una llave es ambigua, no se enlaza
--    y queda para la siguiente pasada o para revision manual).
-- ---------------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS act_enlazar_pasada$$
CREATE PROCEDURE act_enlazar_pasada(IN p_modo TINYINT, IN p_metodo VARCHAR(40))
BEGIN
  DECLARE v_n INT DEFAULT 0;
  DROP TEMPORARY TABLE IF EXISTS t_par;
  DROP TEMPORARY TABLE IF EXISTS t_ok;
  CREATE TEMPORARY TABLE t_par(
    eid BIGINT UNSIGNED NOT NULL,
    nounico VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
  ) ENGINE=InnoDB;
  CREATE TEMPORARY TABLE t_ok(
    eid BIGINT UNSIGNED NOT NULL,
    nounico VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    PRIMARY KEY (eid), UNIQUE KEY (nounico)
  ) ENGINE=InnoDB;

  IF p_modo = 1 THEN        -- numero + documento + fecha de solicitud + nombre
    INSERT INTO t_par
    SELECT e.id, b.nounico
    FROM core_expediente e
    JOIN core_persona p ON p.id = e.persona_id
    JOIN core_bd_actual b ON b.expediente = e.numero AND b.k_dpi <=> p.documento_numero
                         AND b.solicitud <=> e.fecha_solicitud AND b.k_nombre_norm <=> p.nombre_norm
    WHERE e.nounico IS NULL AND b.expediente_id IS NULL;
  ELSEIF p_modo = 2 THEN    -- numero + documento + fecha de solicitud
    INSERT INTO t_par
    SELECT e.id, b.nounico
    FROM core_expediente e
    JOIN core_persona p ON p.id = e.persona_id
    JOIN core_bd_actual b ON b.expediente = e.numero AND b.k_dpi <=> p.documento_numero
                         AND b.solicitud <=> e.fecha_solicitud
    WHERE e.nounico IS NULL AND b.expediente_id IS NULL;
  ELSEIF p_modo = 3 THEN    -- numero + documento
    INSERT INTO t_par
    SELECT e.id, b.nounico
    FROM core_expediente e
    JOIN core_persona p ON p.id = e.persona_id
    JOIN core_bd_actual b ON b.expediente = e.numero AND b.k_dpi <=> p.documento_numero
    WHERE e.nounico IS NULL AND b.expediente_id IS NULL;
  ELSEIF p_modo = 4 THEN    -- numero + nombre + fecha de nacimiento
    INSERT INTO t_par
    SELECT e.id, b.nounico
    FROM core_expediente e
    JOIN core_persona p ON p.id = e.persona_id
    JOIN core_bd_actual b ON b.expediente = e.numero AND b.k_nombre_norm = p.nombre_norm
                         AND b.nacimiento <=> p.fecha_nacimiento
    WHERE e.nounico IS NULL AND b.expediente_id IS NULL;
  ELSEIF p_modo = 5 THEN    -- documento + nombre + fecha de solicitud (cuando el numero difiere)
    INSERT INTO t_par
    SELECT e.id, b.nounico
    FROM core_expediente e
    JOIN core_persona p ON p.id = e.persona_id
    JOIN core_bd_actual b ON b.k_dpi = p.documento_numero AND b.k_nombre_norm = p.nombre_norm
                         AND b.solicitud <=> e.fecha_solicitud
    WHERE e.nounico IS NULL AND b.expediente_id IS NULL AND p.documento_numero IS NOT NULL;
  END IF;

  INSERT INTO t_ok(eid, nounico)
  SELECT eid, nounico FROM (
    SELECT eid, nounico, COUNT(*) OVER (PARTITION BY eid) AS ce, COUNT(*) OVER (PARTITION BY nounico) AS cb
    FROM t_par
  ) x WHERE x.ce = 1 AND x.cb = 1;

  UPDATE core_expediente e JOIN t_ok o ON o.eid = e.id
  SET e.nounico = o.nounico, e.actualizado_en = e.actualizado_en;

  UPDATE core_bd_actual b JOIN t_ok o ON o.nounico = b.nounico
  SET b.expediente_id = o.eid, b.metodo_enlace = p_metodo;
  SET v_n = ROW_COUNT();

  INSERT INTO act_enlace_log(metodo, enlazados) VALUES (p_metodo, v_n);
  DROP TEMPORARY TABLE IF EXISTS t_par;
  DROP TEMPORARY TABLE IF EXISTS t_ok;
END$$

DROP PROCEDURE IF EXISTS act_enlazar_nounico$$
CREATE PROCEDURE act_enlazar_nounico()
BEGIN
  SET @pam_origen = 'IMPORTACION';   -- evita escribir bitacora de auditoria fila por fila
  CALL act_enlazar_pasada(1, 'exp+doc+fsol+nombre');
  CALL act_enlazar_pasada(2, 'exp+doc+fsol');
  CALL act_enlazar_pasada(3, 'exp+doc');
  CALL act_enlazar_pasada(4, 'exp+nombre+fnac');
  CALL act_enlazar_pasada(5, 'doc+nombre+fsol');
  SET @pam_origen = NULL;
END$$

DELIMITER ;
