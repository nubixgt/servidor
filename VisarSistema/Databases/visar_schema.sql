-- Schema base para el sistema independiente de VISAR

-- 1. Tabla: maga_usuarios
CREATE TABLE IF NOT EXISTS `maga_usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `nombre_completo` VARCHAR(150) NOT NULL,
    `rol` VARCHAR(50) DEFAULT 'CONSULTOR',
    `activo` TINYINT(1) DEFAULT 1,
    `puesto_funcional` VARCHAR(150) NULL DEFAULT NULL,
    `ubicacion_laboral` VARCHAR(150) NULL DEFAULT NULL,
    `permisos` TEXT NULL DEFAULT NULL,
    `intentos_fallidos` INT NOT NULL DEFAULT 0,
    `bloqueado_hasta` DATETIME NULL DEFAULT NULL,
    `ultimo_acceso` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios iniciales (password para ambos es: admin123)
INSERT INTO `maga_usuarios` (id, username, password, email, nombre_completo, rol, activo, permisos) VALUES 
(1, 'admin', '$2y$10$/SRZUnVCDTj/WQgz8/Zx2e5eSAHDURu5upLAGUYX22LgoBt.4uPzu', 'admin@maga.gob.gt', 'Pedro López', 'ADMIN', 1, '{"modulo_visar":true}'),
(2, 'tecnico', '$2y$10$/SRZUnVCDTj/WQgz8/Zx2e5eSAHDURu5upLAGUYX22LgoBt.4uPzu', 'tecnico@maga.gob.gt', 'Técnico de Campo', 'TECNICO', 1, '{"modulo_visar":true}');

-- Tabla de configuraciones globales
CREATE TABLE IF NOT EXISTS `maga_settings` (
    `key`         VARCHAR(50)  NOT NULL,
    `value`       TEXT         NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `maga_settings` (`key`, `value`, `description`) VALUES
    ('session_timeout',    '480',              'Tiempo máximo de sesión en minutos (480 = 8 horas)'),
    ('maintenance_mode',   'false',            'Activar modo mantenimiento del sistema (true/false)');

-- 2. Tabla: maga_notificaciones
CREATE TABLE IF NOT EXISTS `maga_notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('success','warning','info','error') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `maga_notificaciones` (`user_id`, `title`, `message`, `type`, `is_read`) VALUES
(1, 'Brote detectado en aves', 'Se reportó sospecha de Influenza Aviar en Huehuetenango.', 'error', 0),
(1, 'Retraso en entrega de informe', 'El informe mensual de San Marcos presenta demoras.', 'warning', 0),
(1, 'Vencimiento de licencia', 'Licencia de transporte en Petén vence en 5 días.', 'info', 0);

-- 3. Tabla: visar_inspecciones
CREATE TABLE IF NOT EXISTS visar_inspecciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    area ENUM('FITOSANITARIO', 'ZOOSANITARIO', 'INOCUIDAD') NOT NULL,
    productor VARCHAR(200) NOT NULL,
    ubicacion VARCHAR(255),
    fecha DATE NOT NULL,
    motivo VARCHAR(255),
    estado ENUM('APROBADO', 'EN REVISIÓN', 'EN INSPECCIÓN', 'AGENDA', 'RECHAZADO') DEFAULT 'AGENDA',
    riesgo ENUM('ALTO', 'MEDIO', 'BAJO') DEFAULT 'BAJO',
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO visar_inspecciones (codigo, area, productor, ubicacion, fecha, motivo, estado, riesgo) VALUES 
('CERT-FITO-2026-001', 'FITOSANITARIO', 'Finca Los Girasoles', 'Jalapa, Jalapa', '2026-02-15', 'Exportación Melón', 'APROBADO', 'BAJO'),
('INSP-ZOO-2026-042', 'ZOOSANITARIO', 'Hacienda La Virgen', 'El Progreso', '2026-02-20', 'Brote de Tuberculosis Bov.', 'EN INSPECCIÓN', 'ALTO'),
('INSP-INO-2026-003', 'INOCUIDAD', 'Procesadora del Sur', 'Escuintla', '2026-02-25', 'Licencia Funcionamiento', 'EN REVISIÓN', 'MEDIO');

-- 4. Tabla: visar_licencias
CREATE TABLE IF NOT EXISTS visar_licencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento VARCHAR(50) NOT NULL UNIQUE,
    tipo ENUM('Exportación', 'Importación', 'Transporte', 'Funcionamiento') NOT NULL,
    subtipo VARCHAR(100),
    titular VARCHAR(200) NOT NULL,
    identificacion VARCHAR(50), -- NIT o DPI
    fecha_emision DATE,
    fecha_vencimiento DATE,
    estado ENUM('VIGENTE', 'EN TRAMITE', 'POR VENCER', 'VENCIDA') DEFAULT 'EN TRAMITE',
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO visar_licencias (documento, tipo, subtipo, titular, identificacion, fecha_emision, fecha_vencimiento, estado) VALUES 
('LIC-EXP-2026-0453', 'Exportación', 'Productos Pecuarios', 'Agrícola San Juan, S.A.', '4589230-1', '2026-01-10', '2027-01-10', 'VIGENTE'),
('PER-IMP-2026-1102', 'Importación', 'Fertilizantes Orgánicos', 'Distribuidora Agro Central', '8910234-5', NULL, NULL, 'EN TRAMITE');

-- 5. Tabla: visar_exportaciones (SIIA)
CREATE TABLE IF NOT EXISTS visar_exportaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(255),
    certificado VARCHAR(100),
    fecha_emision DATE,
    categoria_producto VARCHAR(100),
    pais_destino VARCHAR(100),
    producto VARCHAR(255),
    peso_neto DECIMAL(15, 2),
    valor_fob DECIMAL(15, 2),
    observaciones TEXT,
    destinatario VARCHAR(255),
    aduana VARCHAR(150),
    emisor VARCHAR(150),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla: visar_importaciones
CREATE TABLE IF NOT EXISTS visar_importaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(255),
    categoria_producto VARCHAR(100),
    producto VARCHAR(255),
    pais_origen VARCHAR(100),
    pais_procedencia VARCHAR(100),
    temperatura VARCHAR(50),
    no_bultos INT,
    no_lote VARCHAR(100),
    peso_neto DECIMAL(15, 2),
    valor_dolares DECIMAL(15, 2),
    tipo_valor VARCHAR(50),
    consignatario VARCHAR(255),
    aduana VARCHAR(150),
    observaciones TEXT,
    no_importacion VARCHAR(100),
    no_transaccion VARCHAR(100),
    no_recibo_electronico VARCHAR(100),
    fecha_emision DATE,
    sistema VARCHAR(100),
    emisor VARCHAR(150),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla: visar_licencias_transporte
CREATE TABLE IF NOT EXISTS visar_licencias_transporte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_licencia VARCHAR(100),
    empresa VARCHAR(255),
    nit VARCHAR(50),
    placa VARCHAR(50),
    transporte_de VARCHAR(150),
    inspector VARCHAR(150),
    fecha_emision DATE,
    fecha_vencimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla: visar_licencias_fitosanitarias
CREATE TABLE IF NOT EXISTS visar_licencias_fitosanitarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_recibo_osu VARCHAR(100),
    licencia VARCHAR(100),
    nombre_empresa VARCHAR(255),
    propietario VARCHAR(255),
    categoria VARCHAR(100),
    clasificacion VARCHAR(100),
    departamento VARCHAR(100),
    municipio VARCHAR(100),
    fecha_emision DATE,
    fecha_vencimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabla: visar_libre_venta
CREATE TABLE IF NOT EXISTS `visar_libre_venta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `empresa` varchar(255) NOT NULL,
  `numero_documento` varchar(50) NOT NULL,
  `fecha_emision` date NOT NULL,
  `categoria_producto` varchar(100) DEFAULT NULL,
  `producto` varchar(255) DEFAULT NULL,
  `peso_neto` decimal(12,2) DEFAULT NULL,
  `pais_destino` varchar(100) DEFAULT NULL,
  `emisor` varchar(150) DEFAULT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `usuario_carga` varchar(100) DEFAULT NULL,
  `fecha_carga` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_documento` (`numero_documento`),
  KEY `idx_empresa` (`empresa`),
  KEY `idx_numero_documento` (`numero_documento`),
  KEY `idx_fecha_emision` (`fecha_emision`),
  KEY `idx_categoria_producto` (`categoria_producto`),
  KEY `idx_pais_destino` (`pais_destino`),
  KEY `idx_emisor` (`emisor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
