-- =====================================================================
-- PAM · Sistema Integral de Gestión y Monitoreo del Programa del Adulto Mayor
-- Esquema MySQL 8.0+ (compatible con MariaDB 10.6+)
--
-- Importar con:   mysql -u USUARIO -p BASE < Database/schema.sql
--                 mysql -u USUARIO -p BASE < Database/seed.sql
--
-- Los "esquemas" de PostgreSQL pasan a prefijos de tabla en una sola BD:
--   ref_  catálogos y parámetros        seg_    usuarios, roles, permisos, sesiones
--   core_ datos operativos              audit_  bitácora inmutable
--   stg_  área de carga (importación)   mart_   agregados del tablero
-- =====================================================================
-- Se importa en la base de datos que esté seleccionada (en phpMyAdmin, entrar primero a la base).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 001 · Funciones utilitarias
-- ---------------------------------------------------------------------
DELIMITER $$

-- Texto normalizado para búsqueda: minúsculas, sin tildes, espacios colapsados (NULL si queda vacío).
DROP FUNCTION IF EXISTS ref_f_norm$$
CREATE FUNCTION ref_f_norm(t TEXT) RETURNS VARCHAR(255) DETERMINISTIC
BEGIN
  DECLARE r TEXT;
  IF t IS NULL THEN RETURN NULL; END IF;
  SET r = LOWER(t);
  SET r = REPLACE(r, 'á', 'a'); SET r = REPLACE(r, 'à', 'a'); SET r = REPLACE(r, 'ä', 'a'); SET r = REPLACE(r, 'â', 'a');
  SET r = REPLACE(r, 'é', 'e'); SET r = REPLACE(r, 'è', 'e'); SET r = REPLACE(r, 'ë', 'e'); SET r = REPLACE(r, 'ê', 'e');
  SET r = REPLACE(r, 'í', 'i'); SET r = REPLACE(r, 'ì', 'i'); SET r = REPLACE(r, 'ï', 'i'); SET r = REPLACE(r, 'î', 'i');
  SET r = REPLACE(r, 'ó', 'o'); SET r = REPLACE(r, 'ò', 'o'); SET r = REPLACE(r, 'ö', 'o'); SET r = REPLACE(r, 'ô', 'o');
  SET r = REPLACE(r, 'ú', 'u'); SET r = REPLACE(r, 'ù', 'u'); SET r = REPLACE(r, 'ü', 'u'); SET r = REPLACE(r, 'û', 'u');
  SET r = REPLACE(r, 'ñ', 'n');
  SET r = TRIM(REGEXP_REPLACE(r, '[[:space:]]+', ' '));
  IF r = '' THEN RETURN NULL; END IF;
  RETURN LEFT(r, 255);
END$$

-- Fecha dd/mm/aaaa -> DATE. NULL para vacíos, '#VALOR!', años de relleno (<=1900) y fechas imposibles.
DROP FUNCTION IF EXISTS ref_f_fecha$$
CREATE FUNCTION ref_f_fecha(t VARCHAR(255)) RETURNS DATE DETERMINISTIC
BEGIN
  DECLARE d INT; DECLARE m INT; DECLARE y INT;
  IF t IS NULL OR t NOT REGEXP '^[[:space:]]*[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}[[:space:]]*$' THEN RETURN NULL; END IF;
  SET t = TRIM(t);
  SET d = CAST(SUBSTRING_INDEX(t, '/', 1) AS UNSIGNED);
  SET m = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(t, '/', 2), '/', -1) AS UNSIGNED);
  SET y = CAST(SUBSTRING_INDEX(t, '/', -1) AS UNSIGNED);
  IF y BETWEEN 1901 AND 2100 AND m BETWEEN 1 AND 12 AND d >= 1
     AND d <= DAY(LAST_DAY(STR_TO_DATE(CONCAT(y, '-', m, '-1'), '%Y-%c-%e'))) THEN
    RETURN STR_TO_DATE(CONCAT(y, '-', m, '-', d), '%Y-%c-%e');
  END IF;
  RETURN NULL;
END$$

-- Validación del CUI/DPI de Guatemala (13 dígitos, depto 01-22, dígito verificador módulo 11).
DROP FUNCTION IF EXISTS ref_f_cui_valido$$
CREATE FUNCTION ref_f_cui_valido(t VARCHAR(64)) RETURNS TINYINT(1) DETERMINISTIC
BEGIN
  DECLARE i INT DEFAULT 1; DECLARE s INT DEFAULT 0;
  IF t IS NULL OR t NOT REGEXP '^[0-9]{13}$' THEN RETURN 0; END IF;
  IF CAST(SUBSTRING(t, 10, 2) AS UNSIGNED) NOT BETWEEN 1 AND 22 THEN RETURN 0; END IF;
  IF CAST(SUBSTRING(t, 12, 2) AS UNSIGNED) < 1 THEN RETURN 0; END IF;
  WHILE i <= 8 DO
    SET s = s + CAST(SUBSTRING(t, i, 1) AS UNSIGNED) * (i + 1);
    SET i = i + 1;
  END WHILE;
  RETURN (s MOD 11) = CAST(SUBSTRING(t, 9, 1) AS UNSIGNED);
END$$

-- Banda de edad (0: 65-69 … 5: 90+; las edades < 65 caen en la banda 0).
DROP FUNCTION IF EXISTS ref_f_banda_edad$$
CREATE FUNCTION ref_f_banda_edad(fnac DATE, ref DATE) RETURNS SMALLINT DETERMINISTIC
BEGIN
  IF fnac IS NULL THEN RETURN 0; END IF;
  RETURN LEAST(5, GREATEST(0, (TIMESTAMPDIFF(YEAR, fnac, ref) - 65) DIV 5));
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 002 · Catálogos territoriales y de negocio, parámetros
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ref_departamento(
  id SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  codigo CHAR(2) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

-- id = código INE numérico del municipio (p. ej. 1601 = Cobán); id DIV 100 = departamento.
CREATE TABLE IF NOT EXISTS ref_municipio(
  id SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  codigo CHAR(4) NOT NULL UNIQUE,
  departamento_id SMALLINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  KEY ix_mun_dep (departamento_id),
  CONSTRAINT fk_mun_dep FOREIGN KEY (departamento_id) REFERENCES ref_departamento(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ref_cat_estado(
  id SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL,
  texto_origen VARCHAR(80) NOT NULL UNIQUE,   -- texto exacto en la matriz
  orden SMALLINT NOT NULL,                    -- orden de despliegue (índice del tablero)
  activo_nomina TINYINT(1) NOT NULL DEFAULT 0,
  final TINYINT(1) NOT NULL DEFAULT 0,
  color VARCHAR(9)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ref_cat_familia_resolucion(
  id SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL UNIQUE,
  orden SMALLINT NOT NULL
) ENGINE=InnoDB;

-- codigo en utf8mb4_bin: 'Ñ' y 'N' son códigos distintos.
CREATE TABLE IF NOT EXISTS ref_cat_tipo_resolucion(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) COLLATE utf8mb4_bin NOT NULL UNIQUE,
  nombre VARCHAR(255) NOT NULL,
  familia_id SMALLINT UNSIGNED NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_tipores_fam FOREIGN KEY (familia_id) REFERENCES ref_cat_familia_resolucion(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ref_cat_resultado_visita(
  id SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ref_parametro(
  clave VARCHAR(80) NOT NULL PRIMARY KEY,
  valor JSON NOT NULL,
  descripcion VARCHAR(255)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 003 · Seguridad: usuarios, roles, permisos (configurables) y sesiones
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS seg_permiso(
  codigo VARCHAR(60) NOT NULL PRIMARY KEY,
  modulo VARCHAR(40) NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  orden SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS seg_rol(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255),
  sistema TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS seg_rol_permiso(
  rol_id INT UNSIGNED NOT NULL,
  permiso_codigo VARCHAR(60) NOT NULL,
  PRIMARY KEY (rol_id, permiso_codigo),
  CONSTRAINT fk_rp_rol FOREIGN KEY (rol_id) REFERENCES seg_rol(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permiso_codigo) REFERENCES seg_permiso(codigo) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS seg_usuario(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  nombre VARCHAR(160) NOT NULL,
  correo VARCHAR(160),
  hash VARCHAR(255) NOT NULL,
  rol_id INT UNSIGNED NOT NULL,
  departamento_id SMALLINT UNSIGNED NULL,   -- ámbito territorial (NULL = nacional)
  municipio_id SMALLINT UNSIGNED NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  debe_cambiar_clave TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_usr_rol FOREIGN KEY (rol_id) REFERENCES seg_rol(id),
  CONSTRAINT fk_usr_dep FOREIGN KEY (departamento_id) REFERENCES ref_departamento(id),
  CONSTRAINT fk_usr_mun FOREIGN KEY (municipio_id) REFERENCES ref_municipio(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS seg_sesion(
  id CHAR(36) NOT NULL PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  refresh_hash CHAR(64) NOT NULL,
  ip VARCHAR(45),
  user_agent VARCHAR(255),
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_uso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expira_en DATETIME NOT NULL,
  revocado_en DATETIME NULL,
  KEY ix_ses_usuario (usuario_id, revocado_en),
  KEY ix_ses_refresh (refresh_hash),
  CONSTRAINT fk_ses_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Contador por ventana para limitar peticiones (login, búsquedas); reemplaza el rate limit en memoria.
CREATE TABLE IF NOT EXISTS seg_limite(
  clave VARCHAR(120) NOT NULL,
  ventana INT UNSIGNED NOT NULL,            -- minuto UNIX (time() DIV 60)
  n INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (clave, ventana)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 004 · Núcleo: Persona → Expediente → Eventos
-- origen: MATRIZ (importado) | SISTEMA (registrado en la plataforma)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core_persona(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(300) NOT NULL UNIQUE,          -- CUI:<13 dígitos> | NF:<nombre normalizado>|<fecha nac.>
  nombre VARCHAR(255) NOT NULL,
  nombre_norm VARCHAR(255) NOT NULL,
  documento_tipo ENUM('CUI','CEDULA','SIN_DOCUMENTO') NOT NULL,
  documento_numero VARCHAR(40),
  cui_valido TINYINT(1) NOT NULL DEFAULT 0,
  fecha_nacimiento DATE,
  telefono VARCHAR(40),
  direccion TEXT,
  municipio_id SMALLINT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY persona_nombre (nombre_norm),
  KEY persona_doc (documento_numero),
  KEY persona_tel (telefono),
  KEY persona_fnac (fecha_nacimiento),
  CONSTRAINT fk_per_mun FOREIGN KEY (municipio_id) REFERENCES ref_municipio(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS core_apoderado(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(300) NOT NULL UNIQUE,
  tipo_id VARCHAR(40),
  numero_id VARCHAR(60),
  nombre VARCHAR(255),
  nombre_norm VARCHAR(255),
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY apoderado_num (numero_id),
  KEY apoderado_nombre (nombre_norm)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS core_expediente(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(60),
  clave_origen VARCHAR(48) NULL UNIQUE,        -- llave estable de la fila de la matriz (para re-importar)
  persona_id BIGINT UNSIGNED NOT NULL,
  apoderado_id BIGINT UNSIGNED NULL,
  municipio_id SMALLINT UNSIGNED NOT NULL,
  fecha_solicitud DATE,
  estado_id SMALLINT UNSIGNED NOT NULL,
  fecha_estado DATE,
  -- resumen (se recalcula con core_recalcular_resumen)
  fecha_nomina_desde DATE,
  fecha_nomina_hasta DATE,
  fecha_fallecimiento DATE,
  fecha_suspension DATE,
  ult_visita_resultado_id SMALLINT UNSIGNED NULL,
  ult_visita_fecha DATE,
  ult_resolucion_tipo_id INT UNSIGNED NULL,
  ult_resolucion_fecha DATE,
  ult_observacion TEXT,
  ult_observacion_fecha DATE,
  -- banderas de calidad persistidas (las que requieren comparar contra toda la base)
  flag_dpi_duplicado TINYINT(1) NOT NULL DEFAULT 0,
  flag_exp_duplicado TINYINT(1) NOT NULL DEFAULT 0,
  origen VARCHAR(20) NOT NULL DEFAULT 'MATRIZ',
  lote_id INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  version INT NOT NULL DEFAULT 1,
  KEY exp_persona (persona_id),
  KEY exp_numero (numero),
  KEY exp_mun_estado (municipio_id, estado_id),
  KEY exp_estado_fsol (estado_id, fecha_solicitud),
  KEY exp_fsol_id (fecha_solicitud, id),
  KEY exp_apoderado (apoderado_id),
  KEY exp_lote (lote_id),
  CONSTRAINT fk_exp_per FOREIGN KEY (persona_id) REFERENCES core_persona(id),
  CONSTRAINT fk_exp_apo FOREIGN KEY (apoderado_id) REFERENCES core_apoderado(id),
  CONSTRAINT fk_exp_mun FOREIGN KEY (municipio_id) REFERENCES ref_municipio(id),
  CONSTRAINT fk_exp_est FOREIGN KEY (estado_id) REFERENCES ref_cat_estado(id),
  CONSTRAINT fk_exp_vis FOREIGN KEY (ult_visita_resultado_id) REFERENCES ref_cat_resultado_visita(id),
  CONSTRAINT fk_exp_res FOREIGN KEY (ult_resolucion_tipo_id) REFERENCES ref_cat_tipo_resolucion(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS core_visita(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expediente_id BIGINT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  resultado_id SMALLINT UNSIGNED NOT NULL,
  observacion TEXT,
  tecnico VARCHAR(160),
  usuario_id INT UNSIGNED NULL,
  origen VARCHAR(20) NOT NULL DEFAULT 'SISTEMA',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY vis_exp (expediente_id, fecha),
  KEY vis_fecha (fecha),
  CONSTRAINT fk_vis_exp FOREIGN KEY (expediente_id) REFERENCES core_expediente(id) ON DELETE CASCADE,
  CONSTRAINT fk_vis_res FOREIGN KEY (resultado_id) REFERENCES ref_cat_resultado_visita(id),
  CONSTRAINT fk_vis_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS core_observacion(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expediente_id BIGINT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  texto TEXT NOT NULL,
  usuario_id INT UNSIGNED NULL,
  origen VARCHAR(20) NOT NULL DEFAULT 'SISTEMA',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY obs_exp (expediente_id, fecha),
  CONSTRAINT fk_obs_exp FOREIGN KEY (expediente_id) REFERENCES core_expediente(id) ON DELETE CASCADE,
  CONSTRAINT fk_obs_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS core_resolucion(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expediente_id BIGINT UNSIGNED NOT NULL,
  fecha DATE,
  tipo_id INT UNSIGNED NOT NULL,
  numero VARCHAR(60),
  descripcion TEXT,
  usuario_id INT UNSIGNED NULL,
  origen VARCHAR(20) NOT NULL DEFAULT 'SISTEMA',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY res_exp (expediente_id, fecha),
  KEY res_fecha (fecha),
  CONSTRAINT fk_resol_exp FOREIGN KEY (expediente_id) REFERENCES core_expediente(id) ON DELETE CASCADE,
  CONSTRAINT fk_resol_tipo FOREIGN KEY (tipo_id) REFERENCES ref_cat_tipo_resolucion(id),
  CONSTRAINT fk_resol_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS core_movimiento_nomina(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expediente_id BIGINT UNSIGNED NOT NULL,
  tipo ENUM('ALTA','BAJA') NOT NULL,
  fecha DATE NOT NULL,
  motivo TEXT,
  usuario_id INT UNSIGNED NULL,
  origen VARCHAR(20) NOT NULL DEFAULT 'SISTEMA',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY mov_exp (expediente_id, fecha),
  KEY mov_tipo (tipo, fecha),
  CONSTRAINT fk_mov_exp FOREIGN KEY (expediente_id) REFERENCES core_expediente(id) ON DELETE CASCADE,
  CONSTRAINT fk_mov_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id)
) ENGINE=InnoDB;

-- vigente_unico reemplaza al índice único parcial de PostgreSQL: un solo estado vigente por expediente.
CREATE TABLE IF NOT EXISTS core_estado_expediente(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expediente_id BIGINT UNSIGNED NOT NULL,
  estado_id SMALLINT UNSIGNED NOT NULL,
  fecha DATE,
  vigente TINYINT(1) NOT NULL DEFAULT 1,
  vigente_unico TINYINT(1) GENERATED ALWAYS AS (IF(vigente = 1, 1, NULL)) STORED,
  motivo TEXT,
  usuario_id INT UNSIGNED NULL,
  origen VARCHAR(20) NOT NULL DEFAULT 'SISTEMA',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY estado_vigente_unico (expediente_id, vigente_unico),
  KEY est_exp (expediente_id, creado_en),
  CONSTRAINT fk_est_exp FOREIGN KEY (expediente_id) REFERENCES core_expediente(id) ON DELETE CASCADE,
  CONSTRAINT fk_est_est FOREIGN KEY (estado_id) REFERENCES ref_cat_estado(id),
  CONSTRAINT fk_est_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id)
) ENGINE=InnoDB;

-- Seguimiento de casos detectados por las reglas de alerta / calidad
CREATE TABLE IF NOT EXISTS core_caso_revision(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  regla VARCHAR(20) NOT NULL,
  expediente_id BIGINT UNSIGNED NOT NULL,
  estado ENUM('EN_REVISION','CONFIRMADO','DESCARTADO','CORREGIDO') NOT NULL DEFAULT 'EN_REVISION',
  comentario TEXT,
  usuario_id INT UNSIGNED NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY caso_regla_exp (regla, expediente_id),
  CONSTRAINT fk_caso_exp FOREIGN KEY (expediente_id) REFERENCES core_expediente(id) ON DELETE CASCADE,
  CONSTRAINT fk_caso_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id)
) ENGINE=InnoDB;

-- Documentos adjuntos (almacenamiento en disco)
CREATE TABLE IF NOT EXISTS core_documento(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expediente_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(255) NOT NULL,
  ruta VARCHAR(500) NOT NULL,
  tipo_mime VARCHAR(120),
  sha256 CHAR(64),
  usuario_id INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_doc_exp FOREIGN KEY (expediente_id) REFERENCES core_expediente(id) ON DELETE CASCADE,
  CONSTRAINT fk_doc_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id)
) ENGINE=InnoDB;

-- Alcance temporal de un recálculo de resumen (lo llenan la importación y la API).
CREATE TABLE IF NOT EXISTS core_resumen_scope(
  token CHAR(36) NOT NULL,
  expediente_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (token, expediente_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 005 · Auditoría inmutable
-- La API fija por conexión: @pam_usuario_id, @pam_usuario, @pam_ip, @pam_request_id.
-- Las cargas masivas fijan @pam_origen = 'IMPORTACION' y se auditan como un solo evento por lote.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_bitacora(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuario_id INT UNSIGNED NULL,
  usuario VARCHAR(60),
  accion VARCHAR(30) NOT NULL,     -- INSERT | UPDATE | DELETE | LOGIN | LOGIN_FALLIDO | VER_DPI | EXPORTAR | IMPORTAR | ...
  modulo VARCHAR(40),
  entidad VARCHAR(80),
  entidad_id VARCHAR(80),
  antes JSON,
  despues JSON,
  ip VARCHAR(45),
  user_agent VARCHAR(255),
  request_id VARCHAR(40),
  KEY bitacora_ts (ts),
  KEY bitacora_entidad (entidad, entidad_id),
  KEY bitacora_usuario (usuario_id, ts)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 006 · Importación masiva (CSV)
-- Flujo: archivo → stg_matriz (sin transformar) → stg_procesar_lote() → core_* → conciliación → mart_refrescar()
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stg_import_lote(
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  archivo VARCHAR(255) NOT NULL,
  ruta VARCHAR(500),
  sha256 CHAR(64),
  formato VARCHAR(20),
  codificacion VARCHAR(20),
  modo ENUM('INCREMENTAL','VALIDAR') NOT NULL DEFAULT 'INCREMENTAL',
  estado ENUM('PENDIENTE','LEYENDO','VALIDANDO','TRANSFORMANDO','REFRESCANDO','COMPLETADO','VALIDADO','ERROR','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  progreso DECIMAL(5,2) NOT NULL DEFAULT 0,
  mensaje TEXT,
  total_filas INT,
  filas_insertadas INT,
  filas_actualizadas INT,
  filas_sin_cambio INT,
  filas_rechazadas INT,
  advertencias INT,
  reporte JSON,
  usuario_id INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  iniciado_en DATETIME NULL,
  finalizado_en DATETIME NULL,
  KEY lote_estado (estado),
  CONSTRAINT fk_lote_usr FOREIGN KEY (usuario_id) REFERENCES seg_usuario(id)
) ENGINE=InnoDB;

-- Copia fiel de la matriz (34 columnas, todo como texto). Área temporal de carga.
CREATE TABLE IF NOT EXISTS stg_matriz(
  lote_id INT UNSIGNED NOT NULL,
  linea INT UNSIGNED NOT NULL,
  departamento VARCHAR(255), municipio VARCHAR(255), no_expediente VARCHAR(255), nombre VARCHAR(255), dpi VARCHAR(255),
  fecha_nacimiento VARCHAR(255), fecha_solicitud VARCHAR(255), anio_solicitud VARCHAR(255), telefono VARCHAR(255), direccion TEXT,
  fecha_observacion VARCHAR(255), ultima_observacion TEXT, id_apoderado VARCHAR(255), no_id_apoderado VARCHAR(255), apoderado VARCHAR(255),
  visita_positiva VARCHAR(255), anio_visita_positiva VARCHAR(255), mes_visita VARCHAR(255), visita_negativa VARCHAR(255), anio_visita_negativa VARCHAR(255),
  mes_visita_negativa VARCHAR(255), nomina_desde VARCHAR(255), anio_ingreso_nomina VARCHAR(255), nomina_hasta VARCHAR(255), fallecio VARCHAR(255),
  anio_fallecido VARCHAR(255), suspendido VARCHAR(255), fecha_ultima_resolucion VARCHAR(255), anio_resolucion VARCHAR(255), tipo_resolucion VARCHAR(255),
  resolucion TEXT, fecha_estado_actual VARCHAR(255), anio_estado_actual VARCHAR(255), estado_actual VARCHAR(255),
  PRIMARY KEY (lote_id, linea)
) ENGINE=InnoDB;

-- Filas normalizadas de un lote (área de trabajo de stg_procesar_lote).
CREATE TABLE IF NOT EXISTS stg_norm(
  lote_id INT UNSIGNED NOT NULL,
  linea INT UNSIGNED NOT NULL,
  numero VARCHAR(60),
  nombre VARCHAR(255),
  nombre_norm VARCHAR(255),
  dpi VARCHAR(64),
  fnac DATE, fsol DATE, fobs DATE, vpos DATE, vneg DATE, ndesde DATE, nhasta DATE, ffall DATE, fsusp DATE, fres DATE, fest DATE,
  telefono VARCHAR(40),
  direccion TEXT,
  obs TEXT,
  ap_tipo VARCHAR(40), ap_num VARCHAR(60), ap_nombre VARCHAR(255), ap_clave VARCHAR(300),
  tres VARCHAR(20) COLLATE utf8mb4_bin,
  res_txt TEXT,
  estado_txt VARCHAR(80),
  mun_txt VARCHAR(10),
  municipio_raw VARCHAR(255),
  municipio_id SMALLINT UNSIGNED NULL,
  estado_id SMALLINT UNSIGNED NULL,
  es_cui TINYINT(1),
  cui_ok TINYINT(1),
  pclave VARCHAR(300),
  clave_origen VARCHAR(48),
  rechazo VARCHAR(40),
  persona_id BIGINT UNSIGNED NULL,
  apoderado_id BIGINT UNSIGNED NULL,
  expediente_id BIGINT UNSIGNED NULL,
  PRIMARY KEY (lote_id, linea),
  KEY norm_exp (expediente_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stg_import_error(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  lote_id INT UNSIGNED NOT NULL,
  linea INT UNSIGNED,
  nivel ENUM('ERROR','ADVERTENCIA') NOT NULL,
  regla VARCHAR(60) NOT NULL,
  campo VARCHAR(60),
  valor VARCHAR(255),
  mensaje VARCHAR(255),
  KEY err_lote (lote_id, nivel, regla),
  CONSTRAINT fk_err_lote FOREIGN KEY (lote_id) REFERENCES stg_import_lote(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 007 · Agregados del tablero. El tablero nunca recorre el detalle: lee estos agregados.
-- (Tablas reales; mart_refrescar() las reconstruye y las intercambia de forma atómica.)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mart_cubo(
  municipio_id SMALLINT UNSIGNED NOT NULL,
  e SMALLINT NOT NULL, a SMALLINT NOT NULL, anio SMALLINT NOT NULL, r SMALLINT NOT NULL, v SMALLINT NOT NULL,
  s SMALLINT NOT NULL, f SMALLINT NOT NULL, n SMALLINT NOT NULL, t SMALLINT NOT NULL,
  c INT NOT NULL, p INT NOT NULL,
  qd INT NOT NULL, qu INT NOT NULL, qx INT NOT NULL, qf INT NOT NULL, qe INT NOT NULL,
  qa INT NOT NULL, qs INT NOT NULL, qm INT NOT NULL,
  PRIMARY KEY (municipio_id, e, a, anio, r, v, s, f, n, t)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mart_serie(
  municipio_id SMALLINT UNSIGNED NOT NULL,
  e SMALLINT NOT NULL,
  tipo VARCHAR(8) NOT NULL,
  mes DATE NOT NULL,
  n INT NOT NULL,
  PRIMARY KEY (municipio_id, e, tipo, mes)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mart_tiempos(
  dep SMALLINT UNSIGNED NOT NULL,
  cohorte VARCHAR(8) NOT NULL,
  tramo VARCHAR(8) NOT NULL,
  n INT NOT NULL, media INT, mediana INT, p90 INT,
  PRIMARY KEY (dep, cohorte, tramo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mart_meta(
  clave VARCHAR(60) NOT NULL PRIMARY KEY,
  valor VARCHAR(255)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Vistas
-- ---------------------------------------------------------------------
-- Línea de tiempo: vista (no se duplica información)
CREATE OR REPLACE VIEW core_v_evento AS
  SELECT e.id AS expediente_id, e.fecha_solicitud AS fecha, 'SOLICITUD' AS tipo, 'Solicitud recibida' AS titulo,
         CONCAT('Expediente ', COALESCE(e.numero, 's/n')) AS detalle, e.origen, e.creado_en
    FROM core_expediente e WHERE e.fecha_solicitud IS NOT NULL
  UNION ALL
  SELECT v.expediente_id, v.fecha, CONCAT('VISITA_', r.codigo), CONCAT('Visita ', LOWER(r.nombre)),
         CONCAT_WS(' · ', v.observacion, v.tecnico), v.origen, v.creado_en
    FROM core_visita v JOIN ref_cat_resultado_visita r ON r.id = v.resultado_id
  UNION ALL
  SELECT o.expediente_id, o.fecha, 'OBSERVACION', 'Observación', o.texto, o.origen, o.creado_en FROM core_observacion o
  UNION ALL
  SELECT s.expediente_id, s.fecha, 'RESOLUCION', CONCAT('Resolución ', t.codigo COLLATE utf8mb4_unicode_ci),
         CONCAT_WS(' · ', t.nombre, s.numero, s.descripcion), s.origen, s.creado_en
    FROM core_resolucion s JOIN ref_cat_tipo_resolucion t ON t.id = s.tipo_id
  UNION ALL
  SELECT m.expediente_id, m.fecha, CONCAT('NOMINA_', m.tipo),
         CASE m.tipo WHEN 'ALTA' THEN 'Ingreso a nómina' ELSE 'Salida de nómina' END, m.motivo, m.origen, m.creado_en
    FROM core_movimiento_nomina m
  UNION ALL
  SELECT e.id, e.fecha_suspension, 'SUSPENSION', 'Suspensión registrada', NULL, e.origen, e.creado_en
    FROM core_expediente e WHERE e.fecha_suspension IS NOT NULL
  UNION ALL
  SELECT e.id, e.fecha_fallecimiento, 'FALLECIMIENTO', 'Fallecimiento registrado', NULL, e.origen, e.creado_en
    FROM core_expediente e WHERE e.fecha_fallecimiento IS NOT NULL
  UNION ALL
  SELECT x.expediente_id, x.fecha, 'ESTADO', CONCAT('Estado: ', c.nombre), x.motivo, x.origen, x.creado_en
    FROM core_estado_expediente x JOIN ref_cat_estado c ON c.id = x.estado_id;

-- Vista plana con las dimensiones de análisis de cada expediente (tableros, listados y reportes).
CREATE OR REPLACE VIEW core_v_expediente AS
SELECT e.id, e.numero, e.persona_id, p.nombre, p.nombre_norm, p.documento_tipo, p.documento_numero, p.cui_valido,
       p.fecha_nacimiento, p.telefono, p.direccion,
       e.municipio_id, m.departamento_id, m.nombre AS municipio, d.nombre AS departamento,
       e.fecha_solicitud, e.estado_id, ce.orden AS estado_orden, ce.nombre AS estado, e.fecha_estado,
       e.fecha_nomina_desde, e.fecha_nomina_hasta, e.fecha_fallecimiento, e.fecha_suspension,
       e.ult_visita_resultado_id, e.ult_visita_fecha, e.ult_resolucion_tipo_id, e.ult_resolucion_fecha,
       tr.codigo AS ult_resolucion_codigo, tr.familia_id AS ult_resolucion_familia_id,
       e.ult_observacion, e.ult_observacion_fecha, e.apoderado_id,
       e.flag_dpi_duplicado, e.flag_exp_duplicado, e.actualizado_en,
       CASE WHEN e.estado_id = 1 THEN 1 WHEN e.fecha_nomina_desde IS NOT NULL THEN 2 ELSE 0 END AS situacion_nomina,
       CASE e.ult_visita_resultado_id WHEN 1 THEN 1 WHEN 2 THEN 2 WHEN 3 THEN 2 ELSE 0 END AS visita_cod,
       ref_f_banda_edad(p.fecha_nacimiento, CURDATE()) AS banda_edad,
       TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
       CASE WHEN e.estado_id = 2 AND e.fecha_solicitud IS NOT NULL THEN
         CASE WHEN DATEDIFF(CURDATE(), e.fecha_solicitud) <= 30 THEN 1 WHEN DATEDIFF(CURDATE(), e.fecha_solicitud) <= 60 THEN 2
              WHEN DATEDIFF(CURDATE(), e.fecha_solicitud) <= 90 THEN 3 WHEN DATEDIFF(CURDATE(), e.fecha_solicitud) <= 180 THEN 4
              WHEN DATEDIFF(CURDATE(), e.fecha_solicitud) <= 365 THEN 5 ELSE 6 END
       WHEN e.estado_id = 2 THEN 6 ELSE 0 END AS antiguedad,
       -- banderas de calidad por fila
       (p.cui_valido = 0) AS q_doc,
       ((e.ult_visita_fecha < e.fecha_solicitud OR e.ult_resolucion_fecha < e.fecha_solicitud OR e.fecha_nomina_desde < e.fecha_solicitud
         OR e.fecha_fallecimiento < e.fecha_solicitud OR e.fecha_fallecimiento < p.fecha_nacimiento
         OR GREATEST(COALESCE(e.fecha_solicitud, '1900-01-01'), COALESCE(e.ult_visita_fecha, '1900-01-01'),
                     COALESCE(e.ult_resolucion_fecha, '1900-01-01'), COALESCE(e.fecha_nomina_desde, '1900-01-01'),
                     COALESCE(e.fecha_fallecimiento, '1900-01-01'), COALESCE(e.fecha_suspension, '1900-01-01')) > CURDATE())
        IS TRUE) AS q_fechas,
       ((p.fecha_nacimiento IS NULL OR p.fecha_nacimiento < DATE_SUB(CURDATE(), INTERVAL 110 YEAR)
         OR p.fecha_nacimiento > DATE_SUB(e.fecha_solicitud, INTERVAL 60 YEAR)) IS TRUE) AS q_edad,
       (e.fecha_fallecimiento IS NOT NULL AND e.estado_id = 1) AS q_fall_activo,
       (e.fecha_suspension IS NOT NULL AND e.estado_id = 1) AS q_susp_activo,
       (p.telefono IS NULL) AS q_sin_tel
FROM core_expediente e
JOIN core_persona p ON p.id = e.persona_id
JOIN ref_municipio m ON m.id = e.municipio_id
JOIN ref_departamento d ON d.id = m.departamento_id
JOIN ref_cat_estado ce ON ce.id = e.estado_id
LEFT JOIN ref_cat_tipo_resolucion tr ON tr.id = e.ult_resolucion_tipo_id;

DELIMITER $$

-- Nadie puede modificar ni borrar la bitácora (TRUNCATE debe impedirse con privilegios: ver README).
DROP TRIGGER IF EXISTS bitacora_no_update$$
CREATE TRIGGER bitacora_no_update BEFORE UPDATE ON audit_bitacora FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La bitácora de auditoría es de solo inserción';
END$$
DROP TRIGGER IF EXISTS bitacora_no_delete$$
CREATE TRIGGER bitacora_no_delete BEFORE DELETE ON audit_bitacora FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La bitácora de auditoría es de solo inserción';
END$$

-- Registro automático de cambios en tablas de negocio (se omite durante la importación masiva).
DROP TRIGGER IF EXISTS core_persona_ai$$
CREATE TRIGGER core_persona_ai AFTER INSERT ON core_persona FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.persona', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'clave', NEW.clave, 'nombre', NEW.nombre, 'nombre_norm', NEW.nombre_norm, 'documento_tipo', NEW.documento_tipo, 'documento_numero', NEW.documento_numero, 'cui_valido', NEW.cui_valido, 'fecha_nacimiento', NEW.fecha_nacimiento, 'telefono', NEW.telefono, 'direccion', NEW.direccion, 'municipio_id', NEW.municipio_id, 'creado_en', NEW.creado_en, 'actualizado_en', NEW.actualizado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_persona_au$$
CREATE TRIGGER core_persona_au AFTER UPDATE ON core_persona FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.clave <=> NEW.clave) THEN SET v_a = JSON_SET(v_a, '$.clave', OLD.clave); SET v_d = JSON_SET(v_d, '$.clave', NEW.clave); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.nombre <=> NEW.nombre) THEN SET v_a = JSON_SET(v_a, '$.nombre', OLD.nombre); SET v_d = JSON_SET(v_d, '$.nombre', NEW.nombre); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.nombre_norm <=> NEW.nombre_norm) THEN SET v_a = JSON_SET(v_a, '$.nombre_norm', OLD.nombre_norm); SET v_d = JSON_SET(v_d, '$.nombre_norm', NEW.nombre_norm); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.documento_tipo <=> NEW.documento_tipo) THEN SET v_a = JSON_SET(v_a, '$.documento_tipo', OLD.documento_tipo); SET v_d = JSON_SET(v_d, '$.documento_tipo', NEW.documento_tipo); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.documento_numero <=> NEW.documento_numero) THEN SET v_a = JSON_SET(v_a, '$.documento_numero', OLD.documento_numero); SET v_d = JSON_SET(v_d, '$.documento_numero', NEW.documento_numero); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.cui_valido <=> NEW.cui_valido) THEN SET v_a = JSON_SET(v_a, '$.cui_valido', OLD.cui_valido); SET v_d = JSON_SET(v_d, '$.cui_valido', NEW.cui_valido); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha_nacimiento <=> NEW.fecha_nacimiento) THEN SET v_a = JSON_SET(v_a, '$.fecha_nacimiento', OLD.fecha_nacimiento); SET v_d = JSON_SET(v_d, '$.fecha_nacimiento', NEW.fecha_nacimiento); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.telefono <=> NEW.telefono) THEN SET v_a = JSON_SET(v_a, '$.telefono', OLD.telefono); SET v_d = JSON_SET(v_d, '$.telefono', NEW.telefono); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.direccion <=> NEW.direccion) THEN SET v_a = JSON_SET(v_a, '$.direccion', OLD.direccion); SET v_d = JSON_SET(v_d, '$.direccion', NEW.direccion); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.municipio_id <=> NEW.municipio_id) THEN SET v_a = JSON_SET(v_a, '$.municipio_id', OLD.municipio_id); SET v_d = JSON_SET(v_d, '$.municipio_id', NEW.municipio_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.persona', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_persona_ad$$
CREATE TRIGGER core_persona_ad AFTER DELETE ON core_persona FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.persona', OLD.id, JSON_OBJECT('id', OLD.id, 'clave', OLD.clave, 'nombre', OLD.nombre, 'nombre_norm', OLD.nombre_norm, 'documento_tipo', OLD.documento_tipo, 'documento_numero', OLD.documento_numero, 'cui_valido', OLD.cui_valido, 'fecha_nacimiento', OLD.fecha_nacimiento, 'telefono', OLD.telefono, 'direccion', OLD.direccion, 'municipio_id', OLD.municipio_id, 'creado_en', OLD.creado_en, 'actualizado_en', OLD.actualizado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS core_expediente_ai$$
CREATE TRIGGER core_expediente_ai AFTER INSERT ON core_expediente FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.expediente', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'numero', NEW.numero, 'clave_origen', NEW.clave_origen, 'persona_id', NEW.persona_id, 'apoderado_id', NEW.apoderado_id, 'municipio_id', NEW.municipio_id, 'fecha_solicitud', NEW.fecha_solicitud, 'estado_id', NEW.estado_id, 'fecha_estado', NEW.fecha_estado, 'fecha_nomina_desde', NEW.fecha_nomina_desde, 'fecha_nomina_hasta', NEW.fecha_nomina_hasta, 'fecha_fallecimiento', NEW.fecha_fallecimiento, 'fecha_suspension', NEW.fecha_suspension, 'ult_visita_resultado_id', NEW.ult_visita_resultado_id, 'ult_visita_fecha', NEW.ult_visita_fecha, 'ult_resolucion_tipo_id', NEW.ult_resolucion_tipo_id, 'ult_resolucion_fecha', NEW.ult_resolucion_fecha, 'ult_observacion', NEW.ult_observacion, 'ult_observacion_fecha', NEW.ult_observacion_fecha, 'flag_dpi_duplicado', NEW.flag_dpi_duplicado, 'flag_exp_duplicado', NEW.flag_exp_duplicado, 'origen', NEW.origen, 'lote_id', NEW.lote_id, 'creado_en', NEW.creado_en, 'actualizado_en', NEW.actualizado_en, 'version', NEW.version), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_expediente_au$$
CREATE TRIGGER core_expediente_au AFTER UPDATE ON core_expediente FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.numero <=> NEW.numero) THEN SET v_a = JSON_SET(v_a, '$.numero', OLD.numero); SET v_d = JSON_SET(v_d, '$.numero', NEW.numero); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.clave_origen <=> NEW.clave_origen) THEN SET v_a = JSON_SET(v_a, '$.clave_origen', OLD.clave_origen); SET v_d = JSON_SET(v_d, '$.clave_origen', NEW.clave_origen); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.persona_id <=> NEW.persona_id) THEN SET v_a = JSON_SET(v_a, '$.persona_id', OLD.persona_id); SET v_d = JSON_SET(v_d, '$.persona_id', NEW.persona_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.apoderado_id <=> NEW.apoderado_id) THEN SET v_a = JSON_SET(v_a, '$.apoderado_id', OLD.apoderado_id); SET v_d = JSON_SET(v_d, '$.apoderado_id', NEW.apoderado_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.municipio_id <=> NEW.municipio_id) THEN SET v_a = JSON_SET(v_a, '$.municipio_id', OLD.municipio_id); SET v_d = JSON_SET(v_d, '$.municipio_id', NEW.municipio_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha_solicitud <=> NEW.fecha_solicitud) THEN SET v_a = JSON_SET(v_a, '$.fecha_solicitud', OLD.fecha_solicitud); SET v_d = JSON_SET(v_d, '$.fecha_solicitud', NEW.fecha_solicitud); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.estado_id <=> NEW.estado_id) THEN SET v_a = JSON_SET(v_a, '$.estado_id', OLD.estado_id); SET v_d = JSON_SET(v_d, '$.estado_id', NEW.estado_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha_estado <=> NEW.fecha_estado) THEN SET v_a = JSON_SET(v_a, '$.fecha_estado', OLD.fecha_estado); SET v_d = JSON_SET(v_d, '$.fecha_estado', NEW.fecha_estado); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha_nomina_desde <=> NEW.fecha_nomina_desde) THEN SET v_a = JSON_SET(v_a, '$.fecha_nomina_desde', OLD.fecha_nomina_desde); SET v_d = JSON_SET(v_d, '$.fecha_nomina_desde', NEW.fecha_nomina_desde); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha_nomina_hasta <=> NEW.fecha_nomina_hasta) THEN SET v_a = JSON_SET(v_a, '$.fecha_nomina_hasta', OLD.fecha_nomina_hasta); SET v_d = JSON_SET(v_d, '$.fecha_nomina_hasta', NEW.fecha_nomina_hasta); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha_fallecimiento <=> NEW.fecha_fallecimiento) THEN SET v_a = JSON_SET(v_a, '$.fecha_fallecimiento', OLD.fecha_fallecimiento); SET v_d = JSON_SET(v_d, '$.fecha_fallecimiento', NEW.fecha_fallecimiento); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha_suspension <=> NEW.fecha_suspension) THEN SET v_a = JSON_SET(v_a, '$.fecha_suspension', OLD.fecha_suspension); SET v_d = JSON_SET(v_d, '$.fecha_suspension', NEW.fecha_suspension); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.ult_visita_resultado_id <=> NEW.ult_visita_resultado_id) THEN SET v_a = JSON_SET(v_a, '$.ult_visita_resultado_id', OLD.ult_visita_resultado_id); SET v_d = JSON_SET(v_d, '$.ult_visita_resultado_id', NEW.ult_visita_resultado_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.ult_visita_fecha <=> NEW.ult_visita_fecha) THEN SET v_a = JSON_SET(v_a, '$.ult_visita_fecha', OLD.ult_visita_fecha); SET v_d = JSON_SET(v_d, '$.ult_visita_fecha', NEW.ult_visita_fecha); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.ult_resolucion_tipo_id <=> NEW.ult_resolucion_tipo_id) THEN SET v_a = JSON_SET(v_a, '$.ult_resolucion_tipo_id', OLD.ult_resolucion_tipo_id); SET v_d = JSON_SET(v_d, '$.ult_resolucion_tipo_id', NEW.ult_resolucion_tipo_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.ult_resolucion_fecha <=> NEW.ult_resolucion_fecha) THEN SET v_a = JSON_SET(v_a, '$.ult_resolucion_fecha', OLD.ult_resolucion_fecha); SET v_d = JSON_SET(v_d, '$.ult_resolucion_fecha', NEW.ult_resolucion_fecha); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.ult_observacion <=> NEW.ult_observacion) THEN SET v_a = JSON_SET(v_a, '$.ult_observacion', OLD.ult_observacion); SET v_d = JSON_SET(v_d, '$.ult_observacion', NEW.ult_observacion); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.ult_observacion_fecha <=> NEW.ult_observacion_fecha) THEN SET v_a = JSON_SET(v_a, '$.ult_observacion_fecha', OLD.ult_observacion_fecha); SET v_d = JSON_SET(v_d, '$.ult_observacion_fecha', NEW.ult_observacion_fecha); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.flag_dpi_duplicado <=> NEW.flag_dpi_duplicado) THEN SET v_a = JSON_SET(v_a, '$.flag_dpi_duplicado', OLD.flag_dpi_duplicado); SET v_d = JSON_SET(v_d, '$.flag_dpi_duplicado', NEW.flag_dpi_duplicado); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.flag_exp_duplicado <=> NEW.flag_exp_duplicado) THEN SET v_a = JSON_SET(v_a, '$.flag_exp_duplicado', OLD.flag_exp_duplicado); SET v_d = JSON_SET(v_d, '$.flag_exp_duplicado', NEW.flag_exp_duplicado); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.origen <=> NEW.origen) THEN SET v_a = JSON_SET(v_a, '$.origen', OLD.origen); SET v_d = JSON_SET(v_d, '$.origen', NEW.origen); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.lote_id <=> NEW.lote_id) THEN SET v_a = JSON_SET(v_a, '$.lote_id', OLD.lote_id); SET v_d = JSON_SET(v_d, '$.lote_id', NEW.lote_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.expediente', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_expediente_ad$$
CREATE TRIGGER core_expediente_ad AFTER DELETE ON core_expediente FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.expediente', OLD.id, JSON_OBJECT('id', OLD.id, 'numero', OLD.numero, 'clave_origen', OLD.clave_origen, 'persona_id', OLD.persona_id, 'apoderado_id', OLD.apoderado_id, 'municipio_id', OLD.municipio_id, 'fecha_solicitud', OLD.fecha_solicitud, 'estado_id', OLD.estado_id, 'fecha_estado', OLD.fecha_estado, 'fecha_nomina_desde', OLD.fecha_nomina_desde, 'fecha_nomina_hasta', OLD.fecha_nomina_hasta, 'fecha_fallecimiento', OLD.fecha_fallecimiento, 'fecha_suspension', OLD.fecha_suspension, 'ult_visita_resultado_id', OLD.ult_visita_resultado_id, 'ult_visita_fecha', OLD.ult_visita_fecha, 'ult_resolucion_tipo_id', OLD.ult_resolucion_tipo_id, 'ult_resolucion_fecha', OLD.ult_resolucion_fecha, 'ult_observacion', OLD.ult_observacion, 'ult_observacion_fecha', OLD.ult_observacion_fecha, 'flag_dpi_duplicado', OLD.flag_dpi_duplicado, 'flag_exp_duplicado', OLD.flag_exp_duplicado, 'origen', OLD.origen, 'lote_id', OLD.lote_id, 'creado_en', OLD.creado_en, 'actualizado_en', OLD.actualizado_en, 'version', OLD.version), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS core_visita_ai$$
CREATE TRIGGER core_visita_ai AFTER INSERT ON core_visita FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.visita', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'expediente_id', NEW.expediente_id, 'fecha', NEW.fecha, 'resultado_id', NEW.resultado_id, 'observacion', NEW.observacion, 'tecnico', NEW.tecnico, 'usuario_id', NEW.usuario_id, 'origen', NEW.origen, 'creado_en', NEW.creado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_visita_au$$
CREATE TRIGGER core_visita_au AFTER UPDATE ON core_visita FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.expediente_id <=> NEW.expediente_id) THEN SET v_a = JSON_SET(v_a, '$.expediente_id', OLD.expediente_id); SET v_d = JSON_SET(v_d, '$.expediente_id', NEW.expediente_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha <=> NEW.fecha) THEN SET v_a = JSON_SET(v_a, '$.fecha', OLD.fecha); SET v_d = JSON_SET(v_d, '$.fecha', NEW.fecha); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.resultado_id <=> NEW.resultado_id) THEN SET v_a = JSON_SET(v_a, '$.resultado_id', OLD.resultado_id); SET v_d = JSON_SET(v_d, '$.resultado_id', NEW.resultado_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.observacion <=> NEW.observacion) THEN SET v_a = JSON_SET(v_a, '$.observacion', OLD.observacion); SET v_d = JSON_SET(v_d, '$.observacion', NEW.observacion); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.tecnico <=> NEW.tecnico) THEN SET v_a = JSON_SET(v_a, '$.tecnico', OLD.tecnico); SET v_d = JSON_SET(v_d, '$.tecnico', NEW.tecnico); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SET v_a = JSON_SET(v_a, '$.usuario_id', OLD.usuario_id); SET v_d = JSON_SET(v_d, '$.usuario_id', NEW.usuario_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.origen <=> NEW.origen) THEN SET v_a = JSON_SET(v_a, '$.origen', OLD.origen); SET v_d = JSON_SET(v_d, '$.origen', NEW.origen); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.visita', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_visita_ad$$
CREATE TRIGGER core_visita_ad AFTER DELETE ON core_visita FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.visita', OLD.id, JSON_OBJECT('id', OLD.id, 'expediente_id', OLD.expediente_id, 'fecha', OLD.fecha, 'resultado_id', OLD.resultado_id, 'observacion', OLD.observacion, 'tecnico', OLD.tecnico, 'usuario_id', OLD.usuario_id, 'origen', OLD.origen, 'creado_en', OLD.creado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS core_observacion_ai$$
CREATE TRIGGER core_observacion_ai AFTER INSERT ON core_observacion FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.observacion', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'expediente_id', NEW.expediente_id, 'fecha', NEW.fecha, 'texto', NEW.texto, 'usuario_id', NEW.usuario_id, 'origen', NEW.origen, 'creado_en', NEW.creado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_observacion_au$$
CREATE TRIGGER core_observacion_au AFTER UPDATE ON core_observacion FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.expediente_id <=> NEW.expediente_id) THEN SET v_a = JSON_SET(v_a, '$.expediente_id', OLD.expediente_id); SET v_d = JSON_SET(v_d, '$.expediente_id', NEW.expediente_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha <=> NEW.fecha) THEN SET v_a = JSON_SET(v_a, '$.fecha', OLD.fecha); SET v_d = JSON_SET(v_d, '$.fecha', NEW.fecha); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.texto <=> NEW.texto) THEN SET v_a = JSON_SET(v_a, '$.texto', OLD.texto); SET v_d = JSON_SET(v_d, '$.texto', NEW.texto); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SET v_a = JSON_SET(v_a, '$.usuario_id', OLD.usuario_id); SET v_d = JSON_SET(v_d, '$.usuario_id', NEW.usuario_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.origen <=> NEW.origen) THEN SET v_a = JSON_SET(v_a, '$.origen', OLD.origen); SET v_d = JSON_SET(v_d, '$.origen', NEW.origen); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.observacion', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_observacion_ad$$
CREATE TRIGGER core_observacion_ad AFTER DELETE ON core_observacion FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.observacion', OLD.id, JSON_OBJECT('id', OLD.id, 'expediente_id', OLD.expediente_id, 'fecha', OLD.fecha, 'texto', OLD.texto, 'usuario_id', OLD.usuario_id, 'origen', OLD.origen, 'creado_en', OLD.creado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS core_resolucion_ai$$
CREATE TRIGGER core_resolucion_ai AFTER INSERT ON core_resolucion FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.resolucion', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'expediente_id', NEW.expediente_id, 'fecha', NEW.fecha, 'tipo_id', NEW.tipo_id, 'numero', NEW.numero, 'descripcion', NEW.descripcion, 'usuario_id', NEW.usuario_id, 'origen', NEW.origen, 'creado_en', NEW.creado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_resolucion_au$$
CREATE TRIGGER core_resolucion_au AFTER UPDATE ON core_resolucion FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.expediente_id <=> NEW.expediente_id) THEN SET v_a = JSON_SET(v_a, '$.expediente_id', OLD.expediente_id); SET v_d = JSON_SET(v_d, '$.expediente_id', NEW.expediente_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha <=> NEW.fecha) THEN SET v_a = JSON_SET(v_a, '$.fecha', OLD.fecha); SET v_d = JSON_SET(v_d, '$.fecha', NEW.fecha); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.tipo_id <=> NEW.tipo_id) THEN SET v_a = JSON_SET(v_a, '$.tipo_id', OLD.tipo_id); SET v_d = JSON_SET(v_d, '$.tipo_id', NEW.tipo_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.numero <=> NEW.numero) THEN SET v_a = JSON_SET(v_a, '$.numero', OLD.numero); SET v_d = JSON_SET(v_d, '$.numero', NEW.numero); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.descripcion <=> NEW.descripcion) THEN SET v_a = JSON_SET(v_a, '$.descripcion', OLD.descripcion); SET v_d = JSON_SET(v_d, '$.descripcion', NEW.descripcion); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SET v_a = JSON_SET(v_a, '$.usuario_id', OLD.usuario_id); SET v_d = JSON_SET(v_d, '$.usuario_id', NEW.usuario_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.origen <=> NEW.origen) THEN SET v_a = JSON_SET(v_a, '$.origen', OLD.origen); SET v_d = JSON_SET(v_d, '$.origen', NEW.origen); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.resolucion', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_resolucion_ad$$
CREATE TRIGGER core_resolucion_ad AFTER DELETE ON core_resolucion FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.resolucion', OLD.id, JSON_OBJECT('id', OLD.id, 'expediente_id', OLD.expediente_id, 'fecha', OLD.fecha, 'tipo_id', OLD.tipo_id, 'numero', OLD.numero, 'descripcion', OLD.descripcion, 'usuario_id', OLD.usuario_id, 'origen', OLD.origen, 'creado_en', OLD.creado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS core_movimiento_nomina_ai$$
CREATE TRIGGER core_movimiento_nomina_ai AFTER INSERT ON core_movimiento_nomina FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.movimiento_nomina', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'expediente_id', NEW.expediente_id, 'tipo', NEW.tipo, 'fecha', NEW.fecha, 'motivo', NEW.motivo, 'usuario_id', NEW.usuario_id, 'origen', NEW.origen, 'creado_en', NEW.creado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_movimiento_nomina_au$$
CREATE TRIGGER core_movimiento_nomina_au AFTER UPDATE ON core_movimiento_nomina FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.expediente_id <=> NEW.expediente_id) THEN SET v_a = JSON_SET(v_a, '$.expediente_id', OLD.expediente_id); SET v_d = JSON_SET(v_d, '$.expediente_id', NEW.expediente_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.tipo <=> NEW.tipo) THEN SET v_a = JSON_SET(v_a, '$.tipo', OLD.tipo); SET v_d = JSON_SET(v_d, '$.tipo', NEW.tipo); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha <=> NEW.fecha) THEN SET v_a = JSON_SET(v_a, '$.fecha', OLD.fecha); SET v_d = JSON_SET(v_d, '$.fecha', NEW.fecha); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.motivo <=> NEW.motivo) THEN SET v_a = JSON_SET(v_a, '$.motivo', OLD.motivo); SET v_d = JSON_SET(v_d, '$.motivo', NEW.motivo); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SET v_a = JSON_SET(v_a, '$.usuario_id', OLD.usuario_id); SET v_d = JSON_SET(v_d, '$.usuario_id', NEW.usuario_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.origen <=> NEW.origen) THEN SET v_a = JSON_SET(v_a, '$.origen', OLD.origen); SET v_d = JSON_SET(v_d, '$.origen', NEW.origen); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.movimiento_nomina', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_movimiento_nomina_ad$$
CREATE TRIGGER core_movimiento_nomina_ad AFTER DELETE ON core_movimiento_nomina FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.movimiento_nomina', OLD.id, JSON_OBJECT('id', OLD.id, 'expediente_id', OLD.expediente_id, 'tipo', OLD.tipo, 'fecha', OLD.fecha, 'motivo', OLD.motivo, 'usuario_id', OLD.usuario_id, 'origen', OLD.origen, 'creado_en', OLD.creado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS core_estado_expediente_ai$$
CREATE TRIGGER core_estado_expediente_ai AFTER INSERT ON core_estado_expediente FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.estado_expediente', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'expediente_id', NEW.expediente_id, 'estado_id', NEW.estado_id, 'fecha', NEW.fecha, 'vigente', NEW.vigente, 'motivo', NEW.motivo, 'usuario_id', NEW.usuario_id, 'origen', NEW.origen, 'creado_en', NEW.creado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_estado_expediente_au$$
CREATE TRIGGER core_estado_expediente_au AFTER UPDATE ON core_estado_expediente FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.expediente_id <=> NEW.expediente_id) THEN SET v_a = JSON_SET(v_a, '$.expediente_id', OLD.expediente_id); SET v_d = JSON_SET(v_d, '$.expediente_id', NEW.expediente_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.estado_id <=> NEW.estado_id) THEN SET v_a = JSON_SET(v_a, '$.estado_id', OLD.estado_id); SET v_d = JSON_SET(v_d, '$.estado_id', NEW.estado_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.fecha <=> NEW.fecha) THEN SET v_a = JSON_SET(v_a, '$.fecha', OLD.fecha); SET v_d = JSON_SET(v_d, '$.fecha', NEW.fecha); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.vigente <=> NEW.vigente) THEN SET v_a = JSON_SET(v_a, '$.vigente', OLD.vigente); SET v_d = JSON_SET(v_d, '$.vigente', NEW.vigente); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.motivo <=> NEW.motivo) THEN SET v_a = JSON_SET(v_a, '$.motivo', OLD.motivo); SET v_d = JSON_SET(v_d, '$.motivo', NEW.motivo); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SET v_a = JSON_SET(v_a, '$.usuario_id', OLD.usuario_id); SET v_d = JSON_SET(v_d, '$.usuario_id', NEW.usuario_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.origen <=> NEW.origen) THEN SET v_a = JSON_SET(v_a, '$.origen', OLD.origen); SET v_d = JSON_SET(v_d, '$.origen', NEW.origen); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.estado_expediente', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_estado_expediente_ad$$
CREATE TRIGGER core_estado_expediente_ad AFTER DELETE ON core_estado_expediente FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.estado_expediente', OLD.id, JSON_OBJECT('id', OLD.id, 'expediente_id', OLD.expediente_id, 'estado_id', OLD.estado_id, 'fecha', OLD.fecha, 'vigente', OLD.vigente, 'motivo', OLD.motivo, 'usuario_id', OLD.usuario_id, 'origen', OLD.origen, 'creado_en', OLD.creado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS core_caso_revision_ai$$
CREATE TRIGGER core_caso_revision_ai AFTER INSERT ON core_caso_revision FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.caso_revision', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'regla', NEW.regla, 'expediente_id', NEW.expediente_id, 'estado', NEW.estado, 'comentario', NEW.comentario, 'usuario_id', NEW.usuario_id, 'actualizado_en', NEW.actualizado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_caso_revision_au$$
CREATE TRIGGER core_caso_revision_au AFTER UPDATE ON core_caso_revision FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.regla <=> NEW.regla) THEN SET v_a = JSON_SET(v_a, '$.regla', OLD.regla); SET v_d = JSON_SET(v_d, '$.regla', NEW.regla); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.expediente_id <=> NEW.expediente_id) THEN SET v_a = JSON_SET(v_a, '$.expediente_id', OLD.expediente_id); SET v_d = JSON_SET(v_d, '$.expediente_id', NEW.expediente_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.estado <=> NEW.estado) THEN SET v_a = JSON_SET(v_a, '$.estado', OLD.estado); SET v_d = JSON_SET(v_d, '$.estado', NEW.estado); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.comentario <=> NEW.comentario) THEN SET v_a = JSON_SET(v_a, '$.comentario', OLD.comentario); SET v_d = JSON_SET(v_d, '$.comentario', NEW.comentario); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SET v_a = JSON_SET(v_a, '$.usuario_id', OLD.usuario_id); SET v_d = JSON_SET(v_d, '$.usuario_id', NEW.usuario_id); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.caso_revision', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_caso_revision_ad$$
CREATE TRIGGER core_caso_revision_ad AFTER DELETE ON core_caso_revision FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.caso_revision', OLD.id, JSON_OBJECT('id', OLD.id, 'regla', OLD.regla, 'expediente_id', OLD.expediente_id, 'estado', OLD.estado, 'comentario', OLD.comentario, 'usuario_id', OLD.usuario_id, 'actualizado_en', OLD.actualizado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS core_documento_ai$$
CREATE TRIGGER core_documento_ai AFTER INSERT ON core_documento FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'core', 'core.documento', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'expediente_id', NEW.expediente_id, 'nombre', NEW.nombre, 'ruta', NEW.ruta, 'tipo_mime', NEW.tipo_mime, 'sha256', NEW.sha256, 'usuario_id', NEW.usuario_id, 'creado_en', NEW.creado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS core_documento_au$$
CREATE TRIGGER core_documento_au AFTER UPDATE ON core_documento FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.expediente_id <=> NEW.expediente_id) THEN SET v_a = JSON_SET(v_a, '$.expediente_id', OLD.expediente_id); SET v_d = JSON_SET(v_d, '$.expediente_id', NEW.expediente_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.nombre <=> NEW.nombre) THEN SET v_a = JSON_SET(v_a, '$.nombre', OLD.nombre); SET v_d = JSON_SET(v_d, '$.nombre', NEW.nombre); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.ruta <=> NEW.ruta) THEN SET v_a = JSON_SET(v_a, '$.ruta', OLD.ruta); SET v_d = JSON_SET(v_d, '$.ruta', NEW.ruta); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.tipo_mime <=> NEW.tipo_mime) THEN SET v_a = JSON_SET(v_a, '$.tipo_mime', OLD.tipo_mime); SET v_d = JSON_SET(v_d, '$.tipo_mime', NEW.tipo_mime); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.sha256 <=> NEW.sha256) THEN SET v_a = JSON_SET(v_a, '$.sha256', OLD.sha256); SET v_d = JSON_SET(v_d, '$.sha256', NEW.sha256); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SET v_a = JSON_SET(v_a, '$.usuario_id', OLD.usuario_id); SET v_d = JSON_SET(v_d, '$.usuario_id', NEW.usuario_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'core', 'core.documento', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS core_documento_ad$$
CREATE TRIGGER core_documento_ad AFTER DELETE ON core_documento FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'core', 'core.documento', OLD.id, JSON_OBJECT('id', OLD.id, 'expediente_id', OLD.expediente_id, 'nombre', OLD.nombre, 'ruta', OLD.ruta, 'tipo_mime', OLD.tipo_mime, 'sha256', OLD.sha256, 'usuario_id', OLD.usuario_id, 'creado_en', OLD.creado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS seg_usuario_ai$$
CREATE TRIGGER seg_usuario_ai AFTER INSERT ON seg_usuario FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'seg', 'seg.usuario', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'usuario', NEW.usuario, 'nombre', NEW.nombre, 'correo', NEW.correo, 'rol_id', NEW.rol_id, 'departamento_id', NEW.departamento_id, 'municipio_id', NEW.municipio_id, 'activo', NEW.activo, 'debe_cambiar_clave', NEW.debe_cambiar_clave, 'intentos_fallidos', NEW.intentos_fallidos, 'bloqueado_hasta', NEW.bloqueado_hasta, 'ultimo_acceso', NEW.ultimo_acceso, 'creado_en', NEW.creado_en, 'actualizado_en', NEW.actualizado_en), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS seg_usuario_au$$
CREATE TRIGGER seg_usuario_au AFTER UPDATE ON seg_usuario FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.usuario <=> NEW.usuario) THEN SET v_a = JSON_SET(v_a, '$.usuario', OLD.usuario); SET v_d = JSON_SET(v_d, '$.usuario', NEW.usuario); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.nombre <=> NEW.nombre) THEN SET v_a = JSON_SET(v_a, '$.nombre', OLD.nombre); SET v_d = JSON_SET(v_d, '$.nombre', NEW.nombre); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.correo <=> NEW.correo) THEN SET v_a = JSON_SET(v_a, '$.correo', OLD.correo); SET v_d = JSON_SET(v_d, '$.correo', NEW.correo); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.rol_id <=> NEW.rol_id) THEN SET v_a = JSON_SET(v_a, '$.rol_id', OLD.rol_id); SET v_d = JSON_SET(v_d, '$.rol_id', NEW.rol_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.departamento_id <=> NEW.departamento_id) THEN SET v_a = JSON_SET(v_a, '$.departamento_id', OLD.departamento_id); SET v_d = JSON_SET(v_d, '$.departamento_id', NEW.departamento_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.municipio_id <=> NEW.municipio_id) THEN SET v_a = JSON_SET(v_a, '$.municipio_id', OLD.municipio_id); SET v_d = JSON_SET(v_d, '$.municipio_id', NEW.municipio_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.activo <=> NEW.activo) THEN SET v_a = JSON_SET(v_a, '$.activo', OLD.activo); SET v_d = JSON_SET(v_d, '$.activo', NEW.activo); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.debe_cambiar_clave <=> NEW.debe_cambiar_clave) THEN SET v_a = JSON_SET(v_a, '$.debe_cambiar_clave', OLD.debe_cambiar_clave); SET v_d = JSON_SET(v_d, '$.debe_cambiar_clave', NEW.debe_cambiar_clave); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.intentos_fallidos <=> NEW.intentos_fallidos) THEN SET v_a = JSON_SET(v_a, '$.intentos_fallidos', OLD.intentos_fallidos); SET v_d = JSON_SET(v_d, '$.intentos_fallidos', NEW.intentos_fallidos); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.bloqueado_hasta <=> NEW.bloqueado_hasta) THEN SET v_a = JSON_SET(v_a, '$.bloqueado_hasta', OLD.bloqueado_hasta); SET v_d = JSON_SET(v_d, '$.bloqueado_hasta', NEW.bloqueado_hasta); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.ultimo_acceso <=> NEW.ultimo_acceso) THEN SET v_a = JSON_SET(v_a, '$.ultimo_acceso', OLD.ultimo_acceso); SET v_d = JSON_SET(v_d, '$.ultimo_acceso', NEW.ultimo_acceso); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.creado_en <=> NEW.creado_en) THEN SET v_a = JSON_SET(v_a, '$.creado_en', OLD.creado_en); SET v_d = JSON_SET(v_d, '$.creado_en', NEW.creado_en); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'seg', 'seg.usuario', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS seg_usuario_ad$$
CREATE TRIGGER seg_usuario_ad AFTER DELETE ON seg_usuario FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'seg', 'seg.usuario', OLD.id, JSON_OBJECT('id', OLD.id, 'usuario', OLD.usuario, 'nombre', OLD.nombre, 'correo', OLD.correo, 'rol_id', OLD.rol_id, 'departamento_id', OLD.departamento_id, 'municipio_id', OLD.municipio_id, 'activo', OLD.activo, 'debe_cambiar_clave', OLD.debe_cambiar_clave, 'intentos_fallidos', OLD.intentos_fallidos, 'bloqueado_hasta', OLD.bloqueado_hasta, 'ultimo_acceso', OLD.ultimo_acceso, 'creado_en', OLD.creado_en, 'actualizado_en', OLD.actualizado_en), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS seg_rol_ai$$
CREATE TRIGGER seg_rol_ai AFTER INSERT ON seg_rol FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'seg', 'seg.rol', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'codigo', NEW.codigo, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion, 'sistema', NEW.sistema), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS seg_rol_au$$
CREATE TRIGGER seg_rol_au AFTER UPDATE ON seg_rol FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.codigo <=> NEW.codigo) THEN SET v_a = JSON_SET(v_a, '$.codigo', OLD.codigo); SET v_d = JSON_SET(v_d, '$.codigo', NEW.codigo); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.nombre <=> NEW.nombre) THEN SET v_a = JSON_SET(v_a, '$.nombre', OLD.nombre); SET v_d = JSON_SET(v_d, '$.nombre', NEW.nombre); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.descripcion <=> NEW.descripcion) THEN SET v_a = JSON_SET(v_a, '$.descripcion', OLD.descripcion); SET v_d = JSON_SET(v_d, '$.descripcion', NEW.descripcion); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.sistema <=> NEW.sistema) THEN SET v_a = JSON_SET(v_a, '$.sistema', OLD.sistema); SET v_d = JSON_SET(v_d, '$.sistema', NEW.sistema); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'seg', 'seg.rol', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS seg_rol_ad$$
CREATE TRIGGER seg_rol_ad AFTER DELETE ON seg_rol FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'seg', 'seg.rol', OLD.id, JSON_OBJECT('id', OLD.id, 'codigo', OLD.codigo, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion, 'sistema', OLD.sistema), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS seg_rol_permiso_ai$$
CREATE TRIGGER seg_rol_permiso_ai AFTER INSERT ON seg_rol_permiso FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'seg', 'seg.rol_permiso', CONCAT(NEW.rol_id, ':', NEW.permiso_codigo), NULL, JSON_OBJECT('rol_id', NEW.rol_id, 'permiso_codigo', NEW.permiso_codigo), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS seg_rol_permiso_au$$
CREATE TRIGGER seg_rol_permiso_au AFTER UPDATE ON seg_rol_permiso FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.rol_id <=> NEW.rol_id) THEN SET v_a = JSON_SET(v_a, '$.rol_id', OLD.rol_id); SET v_d = JSON_SET(v_d, '$.rol_id', NEW.rol_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.permiso_codigo <=> NEW.permiso_codigo) THEN SET v_a = JSON_SET(v_a, '$.permiso_codigo', OLD.permiso_codigo); SET v_d = JSON_SET(v_d, '$.permiso_codigo', NEW.permiso_codigo); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'seg', 'seg.rol_permiso', CONCAT(NEW.rol_id, ':', NEW.permiso_codigo), v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS seg_rol_permiso_ad$$
CREATE TRIGGER seg_rol_permiso_ad AFTER DELETE ON seg_rol_permiso FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'seg', 'seg.rol_permiso', CONCAT(OLD.rol_id, ':', OLD.permiso_codigo), JSON_OBJECT('rol_id', OLD.rol_id, 'permiso_codigo', OLD.permiso_codigo), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS ref_cat_tipo_resolucion_ai$$
CREATE TRIGGER ref_cat_tipo_resolucion_ai AFTER INSERT ON ref_cat_tipo_resolucion FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'ref', 'ref.cat_tipo_resolucion', NEW.id, NULL, JSON_OBJECT('id', NEW.id, 'codigo', NEW.codigo, 'nombre', NEW.nombre, 'familia_id', NEW.familia_id, 'activo', NEW.activo), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS ref_cat_tipo_resolucion_au$$
CREATE TRIGGER ref_cat_tipo_resolucion_au AFTER UPDATE ON ref_cat_tipo_resolucion FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.id <=> NEW.id) THEN SET v_a = JSON_SET(v_a, '$.id', OLD.id); SET v_d = JSON_SET(v_d, '$.id', NEW.id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.codigo <=> NEW.codigo) THEN SET v_a = JSON_SET(v_a, '$.codigo', OLD.codigo); SET v_d = JSON_SET(v_d, '$.codigo', NEW.codigo); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.nombre <=> NEW.nombre) THEN SET v_a = JSON_SET(v_a, '$.nombre', OLD.nombre); SET v_d = JSON_SET(v_d, '$.nombre', NEW.nombre); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.familia_id <=> NEW.familia_id) THEN SET v_a = JSON_SET(v_a, '$.familia_id', OLD.familia_id); SET v_d = JSON_SET(v_d, '$.familia_id', NEW.familia_id); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.activo <=> NEW.activo) THEN SET v_a = JSON_SET(v_a, '$.activo', OLD.activo); SET v_d = JSON_SET(v_d, '$.activo', NEW.activo); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'ref', 'ref.cat_tipo_resolucion', NEW.id, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS ref_cat_tipo_resolucion_ad$$
CREATE TRIGGER ref_cat_tipo_resolucion_ad AFTER DELETE ON ref_cat_tipo_resolucion FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'ref', 'ref.cat_tipo_resolucion', OLD.id, JSON_OBJECT('id', OLD.id, 'codigo', OLD.codigo, 'nombre', OLD.nombre, 'familia_id', OLD.familia_id, 'activo', OLD.activo), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DROP TRIGGER IF EXISTS ref_parametro_ai$$
CREATE TRIGGER ref_parametro_ai AFTER INSERT ON ref_parametro FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'INSERT', 'ref', 'ref.parametro', NEW.clave, NULL, JSON_OBJECT('clave', NEW.clave, 'valor', NEW.valor, 'descripcion', NEW.descripcion), @pam_ip, @pam_request_id);
  END IF;
END$$
DROP TRIGGER IF EXISTS ref_parametro_au$$
CREATE TRIGGER ref_parametro_au AFTER UPDATE ON ref_parametro FOR EACH ROW
BEGIN
  DECLARE v_a JSON; DECLARE v_d JSON; DECLARE v_n INT DEFAULT 0;
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    SET v_a = JSON_OBJECT(); SET v_d = JSON_OBJECT();
    IF NOT (OLD.clave <=> NEW.clave) THEN SET v_a = JSON_SET(v_a, '$.clave', OLD.clave); SET v_d = JSON_SET(v_d, '$.clave', NEW.clave); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.valor <=> NEW.valor) THEN SET v_a = JSON_SET(v_a, '$.valor', OLD.valor); SET v_d = JSON_SET(v_d, '$.valor', NEW.valor); SET v_n = v_n + 1; END IF;
    IF NOT (OLD.descripcion <=> NEW.descripcion) THEN SET v_a = JSON_SET(v_a, '$.descripcion', OLD.descripcion); SET v_d = JSON_SET(v_d, '$.descripcion', NEW.descripcion); SET v_n = v_n + 1; END IF;
    IF v_n > 0 THEN
      INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'UPDATE', 'ref', 'ref.parametro', NEW.clave, v_a, v_d, @pam_ip, @pam_request_id);
    END IF;
  END IF;
END$$
DROP TRIGGER IF EXISTS ref_parametro_ad$$
CREATE TRIGGER ref_parametro_ad AFTER DELETE ON ref_parametro FOR EACH ROW
BEGIN
  IF COALESCE(@pam_origen, '') <> 'IMPORTACION' THEN
    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
    VALUES (@pam_usuario_id, @pam_usuario, 'DELETE', 'ref', 'ref.parametro', OLD.clave, JSON_OBJECT('clave', OLD.clave, 'valor', OLD.valor, 'descripcion', OLD.descripcion), NULL, @pam_ip, @pam_request_id);
  END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 004 · Procedimientos del núcleo
-- ---------------------------------------------------------------------
DELIMITER $$

-- Recalcula el resumen del expediente desde las tablas de detalle.
-- El alcance son las filas de core_resumen_scope con el token indicado; se limpia al terminar.
DROP PROCEDURE IF EXISTS core_recalcular_resumen$$
CREATE PROCEDURE core_recalcular_resumen(IN p_token CHAR(36))
BEGIN
  UPDATE core_expediente e
  JOIN core_resumen_scope sc ON sc.expediente_id = e.id AND sc.token = p_token
  LEFT JOIN (
    SELECT z.expediente_id, z.resultado_id, z.fecha FROM (
      SELECT v.expediente_id, v.resultado_id, v.fecha,
             ROW_NUMBER() OVER (PARTITION BY v.expediente_id ORDER BY v.fecha DESC, v.id DESC) AS rn
      FROM core_visita v JOIN core_resumen_scope s ON s.expediente_id = v.expediente_id AND s.token = p_token) z
    WHERE z.rn = 1) v ON v.expediente_id = e.id
  LEFT JOIN (
    SELECT z.expediente_id, z.tipo_id, z.fecha FROM (
      SELECT r.expediente_id, r.tipo_id, r.fecha,
             ROW_NUMBER() OVER (PARTITION BY r.expediente_id ORDER BY r.fecha IS NULL, r.fecha DESC, r.id DESC) AS rn
      FROM core_resolucion r JOIN core_resumen_scope s ON s.expediente_id = r.expediente_id AND s.token = p_token) z
    WHERE z.rn = 1) r ON r.expediente_id = e.id
  LEFT JOIN (
    SELECT z.expediente_id, z.texto, z.fecha FROM (
      SELECT o.expediente_id, o.texto, o.fecha,
             ROW_NUMBER() OVER (PARTITION BY o.expediente_id ORDER BY o.fecha DESC, o.id DESC) AS rn
      FROM core_observacion o JOIN core_resumen_scope s ON s.expediente_id = o.expediente_id AND s.token = p_token) z
    WHERE z.rn = 1) o ON o.expediente_id = e.id
  LEFT JOIN (
    SELECT x.expediente_id, x.estado_id, x.fecha
    FROM core_estado_expediente x JOIN core_resumen_scope s ON s.expediente_id = x.expediente_id AND s.token = p_token
    WHERE x.vigente = 1) st ON st.expediente_id = e.id
  LEFT JOIN (
    SELECT m.expediente_id,
           MIN(CASE WHEN m.tipo = 'ALTA' THEN m.fecha END) AS alta,
           MAX(CASE WHEN m.tipo = 'BAJA' THEN m.fecha END) AS baja
    FROM core_movimiento_nomina m JOIN core_resumen_scope s ON s.expediente_id = m.expediente_id AND s.token = p_token
    GROUP BY m.expediente_id) n ON n.expediente_id = e.id
  SET e.ult_visita_resultado_id = v.resultado_id, e.ult_visita_fecha = v.fecha,
      e.ult_resolucion_tipo_id = r.tipo_id, e.ult_resolucion_fecha = r.fecha,
      e.ult_observacion = o.texto, e.ult_observacion_fecha = o.fecha,
      e.estado_id = COALESCE(st.estado_id, e.estado_id), e.fecha_estado = COALESCE(st.fecha, e.fecha_estado),
      e.fecha_nomina_desde = n.alta,
      e.fecha_nomina_hasta = CASE WHEN COALESCE(st.estado_id, e.estado_id) = 1 THEN NULL ELSE n.baja END;

  DELETE FROM core_resumen_scope WHERE token = p_token;
END$$

-- Banderas que dependen de toda la base (DPI y número de expediente repetidos).
DROP PROCEDURE IF EXISTS core_recalcular_banderas$$
CREATE PROCEDURE core_recalcular_banderas()
BEGIN
  UPDATE core_expediente e
  JOIN (
    SELECT x.id,
           COUNT(CASE WHEN p.documento_tipo = 'CUI' THEN 1 END) OVER (PARTITION BY p.documento_numero) AS n_dpi,
           COUNT(*) OVER (PARTITION BY x.numero) AS n_exp
    FROM core_expediente x JOIN core_persona p ON p.id = x.persona_id) d ON d.id = e.id
  SET e.flag_dpi_duplicado = (COALESCE(d.n_dpi, 0) > 1),
      e.flag_exp_duplicado = (e.numero IS NOT NULL AND d.n_exp > 1)
  WHERE NOT (e.flag_dpi_duplicado <=> (COALESCE(d.n_dpi, 0) > 1))
     OR NOT (e.flag_exp_duplicado <=> (e.numero IS NOT NULL AND d.n_exp > 1));
END$$

-- ---------------------------------------------------------------------
-- 006 · Procesa un lote ya cargado en stg_matriz. Idempotente: re-importar la
-- misma matriz no duplica expedientes (llave clave_origen) y los cambios
-- de estado generan historial en core_estado_expediente.
-- El reporte de conciliación lo arma la aplicación (ImportacionService).
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS stg_procesar_lote$$
CREATE PROCEDURE stg_procesar_lote(IN p_lote INT UNSIGNED, IN p_solo_validar TINYINT)
BEGIN
  DECLARE v_total INT DEFAULT 0; DECLARE v_rech INT DEFAULT 0; DECLARE v_adv INT DEFAULT 0;
  DECLARE v_ins INT DEFAULT 0; DECLARE v_upd INT DEFAULT 0; DECLARE v_valid INT DEFAULT 0;
  DECLARE v_token CHAR(36);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET @pam_origen = NULL;
    RESIGNAL;
  END;

  SET @pam_origen = 'IMPORTACION';
  UPDATE stg_import_lote SET estado = 'VALIDANDO', progreso = 40 WHERE id = p_lote;
  DELETE FROM stg_norm WHERE lote_id = p_lote;

  -- 1) Normalizar
  INSERT INTO stg_norm(lote_id, linea, numero, nombre, nombre_norm, dpi, fnac, fsol, telefono, direccion, fobs, obs,
                       ap_tipo, ap_num, ap_nombre, vpos, vneg, ndesde, nhasta, ffall, fsusp, fres, tres, res_txt,
                       fest, estado_txt, mun_txt, municipio_raw)
  SELECT s.lote_id, s.linea,
         NULLIF(TRIM(s.no_expediente), ''),
         NULLIF(TRIM(REGEXP_REPLACE(s.nombre, '[[:space:]]+', ' ')), ''),
         ref_f_norm(s.nombre),
         NULLIF(REGEXP_REPLACE(COALESCE(s.dpi, ''), '[^0-9]', ''), ''),
         ref_f_fecha(s.fecha_nacimiento),
         ref_f_fecha(s.fecha_solicitud),
         NULLIF(TRIM(s.telefono), ''),
         NULLIF(TRIM(s.direccion), ''),
         ref_f_fecha(s.fecha_observacion),
         CASE WHEN TRIM(COALESCE(s.ultima_observacion, '')) IN ('', '.', '..', '...') THEN NULL ELSE TRIM(s.ultima_observacion) END,
         NULLIF(TRIM(s.id_apoderado), ''),
         NULLIF(TRIM(s.no_id_apoderado), ''),
         CASE WHEN CHAR_LENGTH(TRIM(COALESCE(s.apoderado, ''))) <= 1 THEN NULL ELSE TRIM(s.apoderado) END,
         ref_f_fecha(s.visita_positiva),
         ref_f_fecha(s.visita_negativa),
         ref_f_fecha(s.nomina_desde),
         ref_f_fecha(s.nomina_hasta),
         ref_f_fecha(s.fallecio),
         ref_f_fecha(s.suspendido),
         ref_f_fecha(s.fecha_ultima_resolucion),
         CASE WHEN TRIM(COALESCE(s.tipo_resolucion, '')) IN ('', '0') THEN NULL ELSE UPPER(TRIM(s.tipo_resolucion)) END,
         NULLIF(TRIM(s.resolucion), ''),
         ref_f_fecha(s.fecha_estado_actual),
         UPPER(TRIM(s.estado_actual)),
         NULLIF(REGEXP_SUBSTR(TRIM(s.municipio), '^[0-9]{3,4}'), ''),
         TRIM(s.municipio)
  FROM stg_matriz s WHERE s.lote_id = p_lote;

  UPDATE stg_norm t
  LEFT JOIN ref_municipio m ON m.id = CAST(NULLIF(t.mun_txt, '') AS UNSIGNED)
  LEFT JOIN ref_cat_estado ce ON ce.texto_origen = t.estado_txt
  SET t.municipio_id = m.id,
      t.estado_id = ce.id,
      t.es_cui = IFNULL(t.dpi REGEXP '^[0-9]{13}$', 0),
      t.cui_ok = ref_f_cui_valido(t.dpi),
      t.pclave = CASE WHEN t.dpi REGEXP '^[0-9]{13}$' THEN CONCAT('CUI:', t.dpi)
                      ELSE CONCAT('NF:', COALESCE(t.nombre_norm, '?'), '|', COALESCE(DATE_FORMAT(t.fnac, '%Y-%m-%d'), '')) END,
      t.ap_clave = CASE WHEN t.ap_num IS NOT NULL OR t.ap_nombre IS NOT NULL
                        THEN CONCAT_WS('|', t.ap_tipo, t.ap_num, ref_f_norm(t.ap_nombre)) END
  WHERE t.lote_id = p_lote;

  -- llave estable por fila; repetidos exactos reciben sufijo #n
  UPDATE stg_norm t
  JOIN (SELECT n.linea,
               MD5(CONCAT_WS('|', n.numero, n.dpi, n.fsol, n.nombre_norm, n.municipio_id)) AS base,
               ROW_NUMBER() OVER (PARTITION BY n.numero, n.dpi, n.fsol, n.nombre_norm, n.municipio_id ORDER BY n.linea) AS rn
        FROM stg_norm n WHERE n.lote_id = p_lote) k ON k.linea = t.linea
  SET t.clave_origen = CONCAT(k.base, CASE WHEN k.rn > 1 THEN CONCAT('#', k.rn) ELSE '' END)
  WHERE t.lote_id = p_lote;

  -- 2) Validar: errores (fila rechazada) y advertencias (se importa y se marca)
  DELETE FROM stg_import_error WHERE lote_id = p_lote;
  UPDATE stg_norm SET rechazo = 'MUNICIPIO_DESCONOCIDO' WHERE lote_id = p_lote AND municipio_id IS NULL;
  UPDATE stg_norm SET rechazo = COALESCE(rechazo, 'ESTADO_DESCONOCIDO') WHERE lote_id = p_lote AND estado_id IS NULL;
  UPDATE stg_norm SET rechazo = COALESCE(rechazo, 'SIN_NOMBRE') WHERE lote_id = p_lote AND nombre IS NULL;

  INSERT INTO stg_import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, linea, 'ERROR', rechazo,
         CASE rechazo WHEN 'MUNICIPIO_DESCONOCIDO' THEN 'Municipio' WHEN 'ESTADO_DESCONOCIDO' THEN 'Estado Actual' ELSE 'Nombre' END,
         CASE rechazo WHEN 'MUNICIPIO_DESCONOCIDO' THEN LEFT(municipio_raw, 255) WHEN 'ESTADO_DESCONOCIDO' THEN estado_txt ELSE NULL END,
         CONCAT('Fila rechazada: ', rechazo)
  FROM stg_norm WHERE lote_id = p_lote AND rechazo IS NOT NULL;

  INSERT INTO stg_import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, linea, 'ADVERTENCIA',
         CASE WHEN dpi IS NULL THEN 'SIN_DOCUMENTO' WHEN es_cui = 0 THEN 'DOCUMENTO_NO_CUI' ELSE 'CUI_VERIFICADOR' END,
         'DPI', dpi, 'Documento sin CUI válido'
  FROM stg_norm WHERE lote_id = p_lote AND rechazo IS NULL AND (dpi IS NULL OR es_cui = 0 OR cui_ok = 0);

  INSERT INTO stg_import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, linea, 'ADVERTENCIA', 'SIN_FECHA_SOLICITUD', 'Fecha Solicitud', NULL, 'Fecha de solicitud vacía o inválida'
  FROM stg_norm WHERE lote_id = p_lote AND rechazo IS NULL AND fsol IS NULL;

  INSERT INTO stg_import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, linea, 'ADVERTENCIA', 'SIN_FECHA_NACIMIENTO', 'Fecha Nacimiento', NULL, 'Fecha de nacimiento vacía o inválida'
  FROM stg_norm WHERE lote_id = p_lote AND rechazo IS NULL AND fnac IS NULL;

  INSERT INTO stg_import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, linea, 'ADVERTENCIA', 'FECHAS_INCONSISTENTES', NULL, NULL, 'Evento anterior a la solicitud o al nacimiento'
  FROM stg_norm WHERE lote_id = p_lote AND rechazo IS NULL
    AND (vpos < fsol OR fres < fsol OR ndesde < fsol OR ffall < fsol OR ffall < fnac);

  INSERT INTO stg_import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, linea, 'ADVERTENCIA', 'FALLECIDO_ACTIVO', 'Fallecio', NULL, 'Fecha de fallecimiento con estado EN NOMINA ACTIVA'
  FROM stg_norm WHERE lote_id = p_lote AND rechazo IS NULL AND ffall IS NOT NULL AND estado_id = 1;

  INSERT INTO stg_import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, t.linea, 'ADVERTENCIA', 'TIPO_RESOLUCION_NUEVO', 'Tipo Resolucion', t.tres,
         'Código de resolución no catalogado: se agrega a la familia "Otras"'
  FROM stg_norm t LEFT JOIN ref_cat_tipo_resolucion c ON c.codigo = t.tres
  WHERE t.lote_id = p_lote AND t.rechazo IS NULL AND t.tres IS NOT NULL AND c.id IS NULL;

  SELECT COUNT(*), COALESCE(SUM(rechazo IS NOT NULL), 0) INTO v_total, v_rech FROM stg_norm WHERE lote_id = p_lote;
  SELECT COUNT(*) INTO v_adv FROM stg_import_error WHERE lote_id = p_lote AND nivel = 'ADVERTENCIA';

  IF p_solo_validar = 1 THEN
    UPDATE stg_import_lote SET estado = 'VALIDADO', progreso = 100, total_filas = v_total, filas_rechazadas = v_rech,
           advertencias = v_adv, finalizado_en = NOW(), mensaje = 'Validación terminada; no se modificó la base.'
    WHERE id = p_lote;
    DELETE FROM stg_norm WHERE lote_id = p_lote;
    SET @pam_origen = NULL;
  ELSE
    UPDATE stg_import_lote SET estado = 'TRANSFORMANDO', progreso = 55 WHERE id = p_lote;
    DELETE FROM stg_norm WHERE lote_id = p_lote AND rechazo IS NOT NULL;
    SET v_valid = v_total - v_rech;

    -- 3) Catálogo de resoluciones: códigos nuevos
    INSERT IGNORE INTO ref_cat_tipo_resolucion(codigo, nombre, familia_id)
    SELECT tres, LEFT(COALESCE(MIN(res_txt), tres), 255), 9
    FROM stg_norm WHERE lote_id = p_lote AND tres IS NOT NULL GROUP BY tres;

    -- 4) Personas (los datos de contacto se toman de la solicitud más reciente)
    INSERT INTO core_persona(clave, nombre, nombre_norm, documento_tipo, documento_numero, cui_valido,
                             fecha_nacimiento, telefono, direccion, municipio_id)
    SELECT z.pclave, z.nombre, COALESCE(z.nombre_norm, ''),
           CASE WHEN z.es_cui = 1 THEN 'CUI' WHEN z.dpi IS NOT NULL THEN 'CEDULA' ELSE 'SIN_DOCUMENTO' END,
           z.dpi, COALESCE(z.cui_ok, 0), z.fnac, z.telefono, z.direccion, z.municipio_id
    FROM (SELECT t.*, ROW_NUMBER() OVER (PARTITION BY t.pclave ORDER BY t.fsol IS NULL, t.fsol DESC, t.linea DESC) AS rn
          FROM stg_norm t WHERE t.lote_id = p_lote) z
    WHERE z.rn = 1
    ON DUPLICATE KEY UPDATE
      nombre = VALUES(nombre), nombre_norm = VALUES(nombre_norm),
      fecha_nacimiento = COALESCE(VALUES(fecha_nacimiento), core_persona.fecha_nacimiento),
      telefono = COALESCE(VALUES(telefono), core_persona.telefono),
      direccion = COALESCE(VALUES(direccion), core_persona.direccion),
      municipio_id = VALUES(municipio_id);

    -- 5) Apoderados
    INSERT IGNORE INTO core_apoderado(clave, tipo_id, numero_id, nombre, nombre_norm)
    SELECT z.ap_clave, z.ap_tipo, z.ap_num, z.ap_nombre, ref_f_norm(z.ap_nombre)
    FROM (SELECT t.*, ROW_NUMBER() OVER (PARTITION BY t.ap_clave ORDER BY t.linea) AS rn
          FROM stg_norm t WHERE t.lote_id = p_lote AND t.ap_clave IS NOT NULL) z
    WHERE z.rn = 1;

    UPDATE stg_import_lote SET progreso = 65 WHERE id = p_lote;

    -- 6) Expedientes (upsert por clave_origen)
    UPDATE stg_norm t
    JOIN core_persona p ON p.clave = t.pclave
    LEFT JOIN core_apoderado a ON a.clave = t.ap_clave
    SET t.persona_id = p.id, t.apoderado_id = a.id
    WHERE t.lote_id = p_lote;

    SELECT COUNT(*) INTO v_upd
    FROM stg_norm t JOIN core_expediente e ON e.clave_origen = t.clave_origen WHERE t.lote_id = p_lote;
    SET v_ins = v_valid - v_upd;

    INSERT INTO core_expediente(numero, clave_origen, persona_id, apoderado_id, municipio_id, fecha_solicitud, estado_id,
                                fecha_estado, fecha_nomina_desde, fecha_nomina_hasta, fecha_fallecimiento, fecha_suspension,
                                origen, lote_id)
    SELECT t.numero, t.clave_origen, t.persona_id, t.apoderado_id, t.municipio_id, t.fsol, t.estado_id, t.fest,
           t.ndesde, CASE WHEN t.estado_id = 1 THEN NULL ELSE t.nhasta END, t.ffall, t.fsusp, 'MATRIZ', p_lote
    FROM stg_norm t WHERE t.lote_id = p_lote
    ON DUPLICATE KEY UPDATE
      persona_id = VALUES(persona_id), apoderado_id = VALUES(apoderado_id), municipio_id = VALUES(municipio_id),
      fecha_solicitud = VALUES(fecha_solicitud), fecha_estado = VALUES(fecha_estado),
      fecha_fallecimiento = VALUES(fecha_fallecimiento), fecha_suspension = VALUES(fecha_suspension),
      lote_id = VALUES(lote_id), version = core_expediente.version + 1;

    UPDATE stg_norm t JOIN core_expediente e ON e.clave_origen = t.clave_origen
    SET t.expediente_id = e.id WHERE t.lote_id = p_lote;

    UPDATE stg_import_lote SET progreso = 75 WHERE id = p_lote;

    -- 7) Detalle de origen MATRIZ: se reemplaza (los registros hechos en el sistema no se tocan)
    DELETE v FROM core_visita v JOIN stg_norm t ON t.expediente_id = v.expediente_id
      WHERE t.lote_id = p_lote AND v.origen = 'MATRIZ';
    DELETE v FROM core_observacion v JOIN stg_norm t ON t.expediente_id = v.expediente_id
      WHERE t.lote_id = p_lote AND v.origen = 'MATRIZ';
    DELETE v FROM core_resolucion v JOIN stg_norm t ON t.expediente_id = v.expediente_id
      WHERE t.lote_id = p_lote AND v.origen = 'MATRIZ';
    DELETE v FROM core_movimiento_nomina v JOIN stg_norm t ON t.expediente_id = v.expediente_id
      WHERE t.lote_id = p_lote AND v.origen = 'MATRIZ';

    INSERT INTO core_visita(expediente_id, fecha, resultado_id, origen)
    SELECT expediente_id, vpos, 1, 'MATRIZ' FROM stg_norm WHERE lote_id = p_lote AND vpos IS NOT NULL;
    INSERT INTO core_visita(expediente_id, fecha, resultado_id, origen)
    SELECT expediente_id, vneg, 2, 'MATRIZ' FROM stg_norm WHERE lote_id = p_lote AND vneg IS NOT NULL;

    INSERT INTO core_observacion(expediente_id, fecha, texto, origen)
    SELECT expediente_id, COALESCE(fobs, fest, fsol, CURDATE()), obs, 'MATRIZ'
    FROM stg_norm WHERE lote_id = p_lote AND obs IS NOT NULL;

    INSERT INTO core_resolucion(expediente_id, fecha, tipo_id, descripcion, origen)
    SELECT t.expediente_id, t.fres, c.id, t.res_txt, 'MATRIZ'
    FROM stg_norm t JOIN ref_cat_tipo_resolucion c ON c.codigo = t.tres WHERE t.lote_id = p_lote;

    INSERT INTO core_movimiento_nomina(expediente_id, tipo, fecha, origen)
    SELECT expediente_id, 'ALTA', ndesde, 'MATRIZ' FROM stg_norm WHERE lote_id = p_lote AND ndesde IS NOT NULL;
    INSERT INTO core_movimiento_nomina(expediente_id, tipo, fecha, origen)
    SELECT expediente_id, 'BAJA', nhasta, 'MATRIZ' FROM stg_norm
    WHERE lote_id = p_lote AND nhasta IS NOT NULL AND estado_id <> 1 AND ndesde IS NOT NULL;

    -- 8) Estado: si cambió respecto al vigente, se cierra el anterior y se abre uno nuevo (historial)
    UPDATE core_estado_expediente x JOIN stg_norm t ON t.expediente_id = x.expediente_id AND t.lote_id = p_lote
    SET x.vigente = 0 WHERE x.vigente = 1 AND x.estado_id <> t.estado_id;

    INSERT INTO core_estado_expediente(expediente_id, estado_id, fecha, vigente, motivo, origen)
    SELECT t.expediente_id, t.estado_id, t.fest, 1, CONCAT('Importación lote ', p_lote), 'MATRIZ'
    FROM stg_norm t
    LEFT JOIN core_estado_expediente x ON x.expediente_id = t.expediente_id AND x.vigente = 1
    WHERE t.lote_id = p_lote AND x.id IS NULL;

    UPDATE stg_import_lote SET progreso = 85 WHERE id = p_lote;

    -- 9) Resumen y banderas
    SET v_token = UUID();
    INSERT INTO core_resumen_scope(token, expediente_id)
    SELECT v_token, expediente_id FROM stg_norm WHERE lote_id = p_lote AND expediente_id IS NOT NULL;
    CALL core_recalcular_resumen(v_token);
    CALL core_recalcular_banderas();

    UPDATE stg_import_lote SET estado = 'REFRESCANDO', progreso = 92, total_filas = v_total, filas_rechazadas = v_rech,
           advertencias = v_adv, filas_insertadas = v_ins, filas_actualizadas = v_upd, filas_sin_cambio = 0
    WHERE id = p_lote;

    INSERT INTO audit_bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, despues)
    SELECT l.usuario_id, u.usuario, 'IMPORTAR', 'importacion', 'stg.import_lote', l.id,
           JSON_OBJECT('archivo', l.archivo, 'total', v_total, 'insertadas', v_ins, 'actualizadas', v_upd,
                       'rechazadas', v_rech, 'advertencias', v_adv)
    FROM stg_import_lote l LEFT JOIN seg_usuario u ON u.id = l.usuario_id WHERE l.id = p_lote;

    DELETE FROM stg_norm WHERE lote_id = p_lote;
    SET @pam_origen = NULL;
  END IF;
END$$

-- ---------------------------------------------------------------------
-- 007 · Reconstruye los agregados del tablero y los intercambia de forma atómica
-- (equivalente a REFRESH MATERIALIZED VIEW CONCURRENTLY: los lectores nunca ven tablas a medias).
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS mart_refrescar$$
CREATE PROCEDURE mart_refrescar()
BEGIN
  DECLARE v_cohorte DATE;
  SELECT STR_TO_DATE(JSON_UNQUOTE(valor), '%Y-%m-%d') INTO v_cohorte FROM ref_parametro WHERE clave = 'cohorte_reciente_desde';
  SET v_cohorte = COALESCE(v_cohorte, '2023-01-01');

  DROP TABLE IF EXISTS mart_cubo_nuevo, mart_serie_nuevo, mart_tiempos_nuevo;
  CREATE TABLE mart_cubo_nuevo LIKE mart_cubo;
  CREATE TABLE mart_serie_nuevo LIKE mart_serie;
  CREATE TABLE mart_tiempos_nuevo LIKE mart_tiempos;

  -- Cubo: una fila por combinación de dimensiones.
  INSERT INTO mart_cubo_nuevo(municipio_id, e, a, anio, r, v, s, f, n, t, c, p, qd, qu, qx, qf, qe, qa, qs, qm)
  SELECT b.municipio_id, b.estado_orden, b.banda_edad, COALESCE(YEAR(b.fecha_solicitud), 0), COALESCE(f.orden, 9),
         b.visita_cod, (b.fecha_suspension IS NOT NULL), (b.fecha_fallecimiento IS NOT NULL), b.situacion_nomina, b.antiguedad,
         COUNT(*), SUM(b.rn = 1),
         SUM(b.q_doc), SUM(b.flag_dpi_duplicado), SUM(b.flag_exp_duplicado), SUM(b.q_fechas), SUM(b.q_edad),
         SUM(b.q_fall_activo), SUM(b.q_susp_activo), SUM(b.q_sin_tel)
  FROM (SELECT x.*, ROW_NUMBER() OVER (PARTITION BY x.persona_id ORDER BY x.fecha_solicitud IS NULL, x.fecha_solicitud DESC, x.id DESC) AS rn
        FROM core_v_expediente x) b
  LEFT JOIN ref_cat_familia_resolucion f ON f.id = b.ult_resolucion_familia_id
  GROUP BY b.municipio_id, b.estado_orden, b.banda_edad, COALESCE(YEAR(b.fecha_solicitud), 0), COALESCE(f.orden, 9),
           b.visita_cod, (b.fecha_suspension IS NOT NULL), (b.fecha_fallecimiento IS NOT NULL), b.situacion_nomina, b.antiguedad;

  -- Eventos por mes, municipio y estado actual del expediente.
  INSERT INTO mart_serie_nuevo(municipio_id, e, tipo, mes, n)
  SELECT x.municipio_id, ce.orden, ev.tipo, DATE_SUB(ev.fecha, INTERVAL DAYOFMONTH(ev.fecha) - 1 DAY), COUNT(*)
  FROM (
    SELECT id AS expediente_id, fecha_solicitud AS fecha, 'sol' AS tipo FROM core_expediente
    UNION ALL SELECT expediente_id, fecha, CASE WHEN resultado_id = 1 THEN 'vpos' ELSE 'vneg' END FROM core_visita
    UNION ALL SELECT expediente_id, fecha, 'res' FROM core_resolucion
    UNION ALL SELECT expediente_id, fecha, CASE WHEN tipo = 'ALTA' THEN 'ing' ELSE 'sal' END FROM core_movimiento_nomina
    UNION ALL SELECT id, fecha_suspension, 'susp' FROM core_expediente WHERE fecha_suspension IS NOT NULL
    UNION ALL SELECT id, fecha_fallecimiento, 'fall' FROM core_expediente WHERE fecha_fallecimiento IS NOT NULL) ev
  JOIN core_expediente x ON x.id = ev.expediente_id
  JOIN ref_cat_estado ce ON ce.id = x.estado_id
  WHERE ev.fecha >= '2006-01-01' AND ev.fecha <= CURDATE()
  GROUP BY x.municipio_id, ce.orden, ev.tipo, DATE_SUB(ev.fecha, INTERVAL DAYOFMONTH(ev.fecha) - 1 DAY);

  -- Tiempos del proceso (días): media, mediana y P90 por departamento (dep 0 = nacional).
  DROP TABLE IF EXISTS mart_t_base, mart_t_tramo, mart_t_in;
  CREATE TABLE mart_t_base(dep SMALLINT UNSIGNED, reciente TINYINT, fsol DATE, fvis DATE, fres DATE, fnom DATE) ENGINE=InnoDB;
  CREATE TABLE mart_t_tramo(dep SMALLINT UNSIGNED, reciente TINYINT, tramo VARCHAR(8), d INT) ENGINE=InnoDB;
  CREATE TABLE mart_t_in(dep SMALLINT UNSIGNED, cohorte VARCHAR(8), tramo VARCHAR(8), d INT, KEY (dep, cohorte, tramo)) ENGINE=InnoDB;

  INSERT INTO mart_t_base(dep, reciente, fsol, fvis, fres, fnom)
  SELECT m.departamento_id, COALESCE(e.fecha_solicitud >= v_cohorte, 0), e.fecha_solicitud,
         (SELECT MIN(v.fecha) FROM core_visita v
           WHERE v.expediente_id = e.id AND v.resultado_id = 1 AND v.fecha >= e.fecha_solicitud),
         (SELECT MIN(r.fecha) FROM core_resolucion r JOIN ref_cat_tipo_resolucion t ON t.id = r.tipo_id
           WHERE r.expediente_id = e.id AND t.familia_id = 1),
         e.fecha_nomina_desde
  FROM core_expediente e JOIN ref_municipio m ON m.id = e.municipio_id;

  INSERT INTO mart_t_tramo SELECT dep, reciente, 'sv', DATEDIFF(fvis, fsol) FROM mart_t_base WHERE DATEDIFF(fvis, fsol) >= 0;
  INSERT INTO mart_t_tramo SELECT dep, reciente, 'vr', DATEDIFF(fres, fvis) FROM mart_t_base WHERE DATEDIFF(fres, fvis) >= 0;
  INSERT INTO mart_t_tramo SELECT dep, reciente, 'rn', DATEDIFF(fnom, fres) FROM mart_t_base WHERE DATEDIFF(fnom, fres) >= 0;
  INSERT INTO mart_t_tramo SELECT dep, reciente, 'sn', DATEDIFF(fnom, fsol) FROM mart_t_base WHERE DATEDIFF(fnom, fsol) >= 0;

  INSERT INTO mart_t_in SELECT dep, 'all', tramo, d FROM mart_t_tramo;
  INSERT INTO mart_t_in SELECT dep, 'rec', tramo, d FROM mart_t_tramo WHERE reciente = 1;
  INSERT INTO mart_t_in SELECT 0, 'all', tramo, d FROM mart_t_tramo;
  INSERT INTO mart_t_in SELECT 0, 'rec', tramo, d FROM mart_t_tramo WHERE reciente = 1;

  -- mediana y P90 con interpolación lineal (equivale a percentile_cont)
  INSERT INTO mart_tiempos_nuevo(dep, cohorte, tramo, n, media, mediana, p90)
  SELECT z.dep, z.cohorte, z.tramo, MAX(z.cnt), ROUND(AVG(z.d)),
         ROUND(SUM(CASE WHEN z.rn = z.lo5 THEN z.d * (1 - z.f5) END) + COALESCE(SUM(CASE WHEN z.rn = z.lo5 + 1 THEN z.d * z.f5 END), 0)),
         ROUND(SUM(CASE WHEN z.rn = z.lo9 THEN z.d * (1 - z.f9) END) + COALESCE(SUM(CASE WHEN z.rn = z.lo9 + 1 THEN z.d * z.f9 END), 0))
  FROM (SELECT y.dep, y.cohorte, y.tramo, y.d, y.rn, y.cnt,
               FLOOR(1 + (y.cnt - 1) * 0.5) AS lo5, (y.cnt - 1) * 0.5 - FLOOR((y.cnt - 1) * 0.5) AS f5,
               FLOOR(1 + (y.cnt - 1) * 0.9) AS lo9, (y.cnt - 1) * 0.9 - FLOOR((y.cnt - 1) * 0.9) AS f9
        FROM (SELECT i.dep, i.cohorte, i.tramo, i.d,
                     ROW_NUMBER() OVER (PARTITION BY i.dep, i.cohorte, i.tramo ORDER BY i.d) AS rn,
                     COUNT(*) OVER (PARTITION BY i.dep, i.cohorte, i.tramo) AS cnt
              FROM mart_t_in i) y) z
  GROUP BY z.dep, z.cohorte, z.tramo;

  DROP TABLE IF EXISTS mart_t_base, mart_t_tramo, mart_t_in;

  -- Intercambio atómico
  DROP TABLE IF EXISTS mart_cubo_old, mart_serie_old, mart_tiempos_old;
  RENAME TABLE mart_cubo TO mart_cubo_old, mart_cubo_nuevo TO mart_cubo,
               mart_serie TO mart_serie_old, mart_serie_nuevo TO mart_serie,
               mart_tiempos TO mart_tiempos_old, mart_tiempos_nuevo TO mart_tiempos;
  DROP TABLE IF EXISTS mart_cubo_old, mart_serie_old, mart_tiempos_old;

  REPLACE INTO mart_meta(clave, valor) VALUES ('refrescado_en', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'));
END$$

DELIMITER ;
