-- =====================================================
-- SISTEMA DE EJECUCIÓN PRESUPUESTARIA - MAGA
-- Base de Datos Relacional
-- =====================================================

CREATE DATABASE IF NOT EXISTS ejecucion_presupuestaria 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ejecucion_presupuestaria;

-- =====================================================
-- TABLAS DE CATÁLOGOS
-- =====================================================

-- Tabla de Unidades Ejecutoras
CREATE TABLE unidades_ejecutoras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    nombre_corto VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Programas
CREATE TABLE programas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Grupos de Gasto
CREATE TABLE grupos_gasto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Fuentes de Financiamiento
CREATE TABLE fuentes_financiamiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Ministerios
CREATE TABLE ministerios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    siglas VARCHAR(20),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Tipos de Ejecución
CREATE TABLE tipos_ejecucion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- =====================================================
-- TABLAS DE DATOS PRINCIPALES
-- =====================================================

-- Ejecución Principal (Hoja UNI EJE)
CREATE TABLE ejecucion_principal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidad_ejecutora_id INT,
    programa_id INT,
    grupo_gasto_id INT,
    fuente_financiamiento_id INT,
    tipo_ejecucion_id INT NOT NULL,
    asignado DECIMAL(18,2) DEFAULT 0,
    modificado DECIMAL(18,2) DEFAULT 0,
    vigente DECIMAL(18,2) DEFAULT 0,
    devengado DECIMAL(18,2) DEFAULT 0,
    saldo_por_devengar DECIMAL(18,2) DEFAULT 0,
    porcentaje_ejecucion DECIMAL(8,4) DEFAULT 0,
    porcentaje_relativo DECIMAL(8,4) DEFAULT 0,
    porcentaje_ejecucion_al_dia DECIMAL(8,4) DEFAULT NULL,
    periodo VARCHAR(20) DEFAULT NULL,
    fecha_registro DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unidad_ejecutora_id) REFERENCES unidades_ejecutoras(id) ON DELETE SET NULL,
    FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE SET NULL,
    FOREIGN KEY (grupo_gasto_id) REFERENCES grupos_gasto(id) ON DELETE SET NULL,
    FOREIGN KEY (fuente_financiamiento_id) REFERENCES fuentes_financiamiento(id) ON DELETE SET NULL,
    FOREIGN KEY (tipo_ejecucion_id) REFERENCES tipos_ejecucion(id)
) ENGINE=InnoDB;

-- Detalle Unidad Ejecutora y Grupo de Gasto (Hoja UniEjeYGru_Gas)
CREATE TABLE ejecucion_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidad_ejecutora_id INT NOT NULL,
    grupo_gasto_id INT,
    fuente_financiamiento_id INT,
    tipo_registro ENUM('Grupo de gasto', 'Fuente de financiamiento') NOT NULL,
    vigente DECIMAL(18,2) DEFAULT 0,
    devengado DECIMAL(18,2) DEFAULT 0,
    saldo_por_devengar DECIMAL(18,2) DEFAULT 0,
    porcentaje_ejecucion DECIMAL(8,4) DEFAULT 0,
    porcentaje_relativo DECIMAL(8,4) DEFAULT 0,
    periodo VARCHAR(20) DEFAULT NULL,
    fecha_registro DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unidad_ejecutora_id) REFERENCES unidades_ejecutoras(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_gasto_id) REFERENCES grupos_gasto(id) ON DELETE SET NULL,
    FOREIGN KEY (fuente_financiamiento_id) REFERENCES fuentes_financiamiento(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Ejecución de Ministerios
CREATE TABLE ejecucion_ministerios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ministerio_id INT NOT NULL,
    asignado DECIMAL(18,2) DEFAULT 0,
    modificado DECIMAL(18,2) DEFAULT 0,
    vigente DECIMAL(18,2) DEFAULT 0,
    devengado DECIMAL(18,2) DEFAULT 0,
    saldo_por_devengar DECIMAL(18,2) DEFAULT 0,
    porcentaje_ejecucion DECIMAL(8,4) DEFAULT 0,
    porcentaje_relativo DECIMAL(8,4) DEFAULT 0,
    periodo VARCHAR(20) DEFAULT NULL,
    fecha_registro DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLAS DE USUARIOS Y SEGURIDAD
-- =====================================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
    activo TINYINT(1) DEFAULT 1,
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE BITÁCORA (HISTORIAL DE CAMBIOS)
-- =====================================================

CREATE TABLE bitacora (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    tabla_afectada VARCHAR(100) NOT NULL,
    registro_id INT NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    datos_anteriores JSON,
    datos_nuevos JSON,
    campos_modificados TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_tabla_registro (tabla_afectada, registro_id),
    INDEX idx_fecha (created_at),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB;

-- =====================================================
-- VISTAS PARA CONSULTAS FRECUENTES
-- =====================================================

-- Vista de Ejecución Principal con nombres
CREATE VIEW v_ejecucion_principal AS
SELECT 
    ep.id,
    ue.codigo as unidad_codigo,
    ue.nombre as unidad_nombre,
    ue.nombre_corto as unidad_corto,
    p.codigo as programa_codigo,
    p.nombre as programa_nombre,
    gg.codigo as grupo_gasto_codigo,
    gg.nombre as grupo_gasto_nombre,
    ff.codigo as fuente_codigo,
    ff.nombre as fuente_nombre,
    te.nombre as tipo_ejecucion,
    ep.asignado,
    ep.modificado,
    ep.vigente,
    ep.devengado,
    ep.saldo_por_devengar,
    ep.porcentaje_ejecucion,
    ep.porcentaje_relativo,
    ep.porcentaje_ejecucion_al_dia,
    ep.fecha_registro,
    COALESCE(
        CONCAT(ue.codigo, ' "', ue.nombre_corto, '"'),
        CONCAT(p.codigo, ' "', p.nombre, '"'),
        CONCAT(gg.codigo, ' "', gg.nombre, '"'),
        CONCAT(ff.codigo, ' "', ff.nombre, '"')
    ) as progra_uni_gasto_finan
FROM ejecucion_principal ep
LEFT JOIN unidades_ejecutoras ue ON ep.unidad_ejecutora_id = ue.id
LEFT JOIN programas p ON ep.programa_id = p.id
LEFT JOIN grupos_gasto gg ON ep.grupo_gasto_id = gg.id
LEFT JOIN fuentes_financiamiento ff ON ep.fuente_financiamiento_id = ff.id
LEFT JOIN tipos_ejecucion te ON ep.tipo_ejecucion_id = te.id;

-- Vista de Detalle con nombres
CREATE VIEW v_ejecucion_detalle AS
SELECT 
    ed.id,
    ue.codigo as unidad_codigo,
    ue.nombre as unidad_nombre,
    ue.nombre_corto as unidad_corto,
    gg.codigo as grupo_gasto_codigo,
    gg.nombre as grupo_gasto_nombre,
    ff.codigo as fuente_codigo,
    ff.nombre as fuente_nombre,
    ed.tipo_registro,
    ed.vigente,
    ed.devengado,
    ed.saldo_por_devengar,
    ed.porcentaje_ejecucion,
    ed.porcentaje_relativo,
    ed.fecha_registro,
    COALESCE(
        CONCAT(gg.codigo, ' "', gg.nombre, '"'),
        CONCAT(ff.codigo, ' "', ff.nombre, '"')
    ) as tipo_gasto_financiamiento
FROM ejecucion_detalle ed
LEFT JOIN unidades_ejecutoras ue ON ed.unidad_ejecutora_id = ue.id
LEFT JOIN grupos_gasto gg ON ed.grupo_gasto_id = gg.id
LEFT JOIN fuentes_financiamiento ff ON ed.fuente_financiamiento_id = ff.id;

-- Vista de Ministerios
CREATE VIEW v_ejecucion_ministerios AS
SELECT 
    em.id,
    m.nombre as ministerio,
    m.siglas,
    em.asignado,
    em.modificado,
    em.vigente,
    em.devengado,
    em.saldo_por_devengar,
    em.porcentaje_ejecucion,
    em.porcentaje_relativo,
    em.fecha_registro
FROM ejecucion_ministerios em
JOIN ministerios m ON em.ministerio_id = m.id;

-- =====================================================
-- DATOS INICIALES (CATÁLOGOS)
-- =====================================================

-- Tipos de Ejecución
INSERT INTO tipos_ejecucion (nombre) VALUES 
('Unidad Ejecutora'),
('Programa'),
('Grupo de Gasto'),
('Fuente de Financiamiento');

-- Unidades Ejecutoras
INSERT INTO unidades_ejecutoras (codigo, nombre, nombre_corto) VALUES
('201', 'Ministerio de Agricultura, Ganadería y Alimentación', 'MAGA'),
('202', 'Instituto Geográfico Nacional', 'IGN'),
('203', 'Oficina de Control de Áreas de Reservas Territoriales del Estado', 'OCRET'),
('204', 'Viceministerio de Seguridad Alimentaria y Nutricional', 'VISAN'),
('205', 'Viceministerio de Desarrollo Económico y Rural', 'VIDER'),
('208', 'Viceministerio de Asuntos de Petén', 'VICEPETÉN'),
('209', 'Viceministerio de Sanidad Agropecuaria y Regulaciones', 'VISAR'),
('210', 'Dirección de Coordinación Regional y Extensión Rural', 'DICORER'),
('213', 'Fondo Nacional para la Reactivación y Modernización de la Actividad Agropecuaria', 'FONAGRO');

-- Programas
INSERT INTO programas (codigo, nombre) VALUES
('01', 'Actividades Centrales'),
('11', 'Acceso y Disponibilidad Alimentaria'),
('12', 'Investigación, Restauración y Conservación de Suelos'),
('13', 'Apoyo a la Producción Agrícola, Pecuaria e Hidrobiológica'),
('14', 'Apoyo a la Protección y Bienestar Animal'),
('99', 'Partidas no Asignables a Programas');

-- Grupos de Gasto
INSERT INTO grupos_gasto (codigo, nombre) VALUES
('000', 'Servicios Personales'),
('100', 'Servicios No Personales'),
('200', 'Materiales y Suministros'),
('300', 'Propiedad, Planta, Equipo e Intangibles'),
('400', 'Transferencias Corrientes'),
('500', 'Transferencias De Capital'),
('600', 'Activos Financieros'),
('900', 'Asignaciones Globales');

-- Fuentes de Financiamiento
INSERT INTO fuentes_financiamiento (codigo, nombre) VALUES
('11', 'Ingresos Corrientes'),
('21', 'Ingresos Tributarios IVA PAZ'),
('31', 'Ingresos Propios'),
('32', 'Disminución de Caja y Bancos Ingresos Propios'),
('41', 'Colocaciones Internas'),
('51', 'Colocaciones Externas'),
('52', 'Prestamos Externos'),
('61', 'Donaciones Externas');

-- Ministerios (los que se mencionan en el Excel)
INSERT INTO ministerios (nombre, siglas) VALUES
('MINISTERIO DE LA DEFENSA NACIONAL', 'MINDEF'),
('MINISTERIO DE DESARROLLO SOCIAL', 'MIDES'),
('MINISTERIO DE RELACIONES EXTERIORES', 'MINEX'),
('MINISTERIO DE GOBERNACIÓN', 'MINGOB'),
('MINISTERIO DE TRABAJO Y PREVISIÓN SOCIAL', 'MINTRAB'),
('MINISTERIO DE EDUCACIÓN', 'MINEDUC'),
('MINISTERIO DE SALUD PÚBLICA Y ASISTENCIA SOCIAL', 'MSPAS'),
('MINISTERIO DE ECONOMÍA', 'MINECO'),
('MINISTERIO DE ENERGÍA Y MINAS', 'MEM'),
('MINISTERIO DE AMBIENTE Y RECURSOS NATURALES', 'MARN'),
('MINISTERIO DE FINANZAS PÚBLICAS', 'MINFIN'),
('MINISTERIO DE AGRICULTURA, GANADERÍA Y ALIMENTACIÓN', 'MAGA'),
('MINISTERIO DE CULTURA Y DEPORTES', 'MCD'),
('MINISTERIO DE COMUNICACIONES, INFRAESTRUCTURA Y VIVIENDA', 'CIV');

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@maga.gob.gt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- =====================================================
-- ÍNDICES ADICIONALES PARA RENDIMIENTO
-- =====================================================

CREATE INDEX idx_ep_tipo ON ejecucion_principal(tipo_ejecucion_id);
CREATE INDEX idx_ep_fecha ON ejecucion_principal(fecha_registro);
CREATE INDEX idx_ed_unidad ON ejecucion_detalle(unidad_ejecutora_id);
CREATE INDEX idx_ed_tipo ON ejecucion_detalle(tipo_registro);
CREATE INDEX idx_em_ministerio ON ejecucion_ministerios(ministerio_id);
