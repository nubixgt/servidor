-- =====================================================
-- BASE DE DATOS VIDER - MAGA Guatemala
-- Sistema de Viceministerio de Desarrollo Económico Rural
-- =====================================================

CREATE DATABASE IF NOT EXISTS vider_maga CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vider_maga;

-- =====================================================
-- TABLAS DE CATÁLOGOS
-- =====================================================

-- Departamentos de Guatemala
CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    codigo VARCHAR(10),
    coordenadas_lat DECIMAL(10, 8),
    coordenadas_lng DECIMAL(11, 8),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Municipios
CREATE TABLE IF NOT EXISTS municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(10),
    coordenadas_lat DECIMAL(10, 8),
    coordenadas_lng DECIMAL(11, 8),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_municipio_dept (departamento_id, nombre)
) ENGINE=InnoDB;

-- Unidades Ejecutoras
CREATE TABLE IF NOT EXISTS unidades_ejecutoras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    codigo VARCHAR(20),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Dependencias
CREATE TABLE IF NOT EXISTS dependencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidad_ejecutora_id INT,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    siglas VARCHAR(50),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unidad_ejecutora_id) REFERENCES unidades_ejecutoras(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Programas
CREATE TABLE IF NOT EXISTS programas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo INT NOT NULL UNIQUE,
    nombre VARCHAR(255),
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Subprogramas
CREATE TABLE IF NOT EXISTS subprogramas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programa_id INT,
    codigo INT NOT NULL,
    nombre VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE SET NULL,
    UNIQUE KEY unique_subprograma (programa_id, codigo)
) ENGINE=InnoDB;

-- Actividades
CREATE TABLE IF NOT EXISTS actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(500) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Subproductos
CREATE TABLE IF NOT EXISTS subproductos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT,
    nombre TEXT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Intervenciones
CREATE TABLE IF NOT EXISTS intervenciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(500) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Medidas
CREATE TABLE IF NOT EXISTS medidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA PRINCIPAL DE DATOS VIDER
-- =====================================================

CREATE TABLE IF NOT EXISTS datos_vider (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Referencias a catálogos
    unidad_ejecutora_id INT,
    dependencia_id INT,
    programa_id INT,
    subprograma_id INT,
    actividad_id INT,
    producto_id INT,
    subproducto_id INT,
    intervencion_id INT,
    medida_id INT,
    departamento_id INT,
    municipio_id INT,
    
    -- Datos de ejecución física
    programado DECIMAL(15, 2) DEFAULT 0,
    ejecutado DECIMAL(15, 2) DEFAULT 0,
    porcentaje_ejecucion DECIMAL(5, 2) DEFAULT 0,
    
    -- Beneficiarios
    hombres INT DEFAULT 0,
    mujeres INT DEFAULT 0,
    total_personas INT DEFAULT 0,
    beneficiarios INT DEFAULT 0,
    
    -- Datos financieros
    vigente_financiera DECIMAL(18, 2) DEFAULT 0,
    financiera_ejecutado DECIMAL(18, 2) DEFAULT 0,
    financiera_porcentaje DECIMAL(5, 2) DEFAULT 0,
    
    -- Metadatos
    periodo VARCHAR(20),
    anio INT,
    mes INT,
    hash_registro VARCHAR(64) UNIQUE, -- Para detectar duplicados
    importacion_id INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (unidad_ejecutora_id) REFERENCES unidades_ejecutoras(id) ON DELETE SET NULL,
    FOREIGN KEY (dependencia_id) REFERENCES dependencias(id) ON DELETE SET NULL,
    FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE SET NULL,
    FOREIGN KEY (subprograma_id) REFERENCES subprogramas(id) ON DELETE SET NULL,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE SET NULL,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL,
    FOREIGN KEY (subproducto_id) REFERENCES subproductos(id) ON DELETE SET NULL,
    FOREIGN KEY (intervencion_id) REFERENCES intervenciones(id) ON DELETE SET NULL,
    FOREIGN KEY (medida_id) REFERENCES medidas(id) ON DELETE SET NULL,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL,
    FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE SET NULL,
    
    -- Índices para búsquedas
    INDEX idx_departamento (departamento_id),
    INDEX idx_municipio (municipio_id),
    INDEX idx_dependencia (dependencia_id),
    INDEX idx_periodo (anio, mes)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE TOBANIK (Cooperativas)
-- =====================================================

CREATE TABLE IF NOT EXISTS tobanik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cooperativa VARCHAR(255) NOT NULL,
    sede VARCHAR(255),
    monto_colocado DECIMAL(18, 2) DEFAULT 0,
    cantidad_productores INT DEFAULT 0,
    monto_otorgado DECIMAL(18, 2) DEFAULT 0,
    departamento_id INT,
    monto_financiero DECIMAL(18, 2) DEFAULT 0,
    cantidad_productores_depto INT DEFAULT 0,
    hash_registro VARCHAR(64) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE HISTORIAL DE IMPORTACIONES
-- =====================================================

CREATE TABLE IF NOT EXISTS importaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_archivo VARCHAR(255) NOT NULL,
    registros_totales INT DEFAULT 0,
    registros_importados INT DEFAULT 0,
    registros_duplicados INT DEFAULT 0,
    registros_error INT DEFAULT 0,
    usuario VARCHAR(100),
    ip_address VARCHAR(45),
    estado ENUM('procesando', 'completado', 'error') DEFAULT 'procesando',
    mensaje TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA DE USUARIOS
-- =====================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(150),
    email VARCHAR(100),
    rol ENUM('admin', 'editor', 'visor') DEFAULT 'visor',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (username, password, nombre_completo, rol) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador VIDER', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- =====================================================
-- INSERTAR DEPARTAMENTOS DE GUATEMALA
-- =====================================================

INSERT INTO departamentos (nombre, codigo, coordenadas_lat, coordenadas_lng) VALUES
('Alta Verapaz', 'AV', 15.4833, -90.3833),
('Baja Verapaz', 'BV', 15.0833, -90.3667),
('Chimaltenango', 'CM', 14.6667, -90.8167),
('Chiquimula', 'CQ', 14.8000, -89.5500),
('El Progreso', 'PR', 14.8500, -90.0667),
('Escuintla', 'ES', 14.3000, -90.7833),
('Guatemala', 'GU', 14.6349, -90.5069),
('Huehuetenango', 'HU', 15.3167, -91.4667),
('Izabal', 'IZ', 15.4833, -88.9833),
('Jalapa', 'JA', 14.6333, -89.9833),
('Jutiapa', 'JU', 14.2833, -89.8833),
('Petén', 'PE', 16.9167, -89.8833),
('Quetzaltenango', 'QZ', 14.8333, -91.5167),
('Quiché', 'QC', 15.0333, -91.1500),
('Retalhuleu', 'RE', 14.5333, -91.6833),
('Sacatepéquez', 'SA', 14.5500, -90.7333),
('San Marcos', 'SM', 14.9667, -91.8000),
('Santa Rosa', 'SR', 14.2833, -90.3000),
('Sololá', 'SO', 14.7667, -91.1833),
('Suchitepéquez', 'SU', 14.5333, -91.5000),
('Totonicapán', 'TO', 14.9167, -91.3667),
('Zacapa', 'ZA', 14.9667, -89.5333)
ON DUPLICATE KEY UPDATE codigo=VALUES(codigo);

-- =====================================================
-- INSERTAR MEDIDAS COMUNES
-- =====================================================

INSERT INTO medidas (nombre) VALUES
('Persona'),
('Familia'),
('Hectárea'),
('Unidad'),
('Proyecto'),
('Kilómetro'),
('Tonelada'),
('Quintal')
ON DUPLICATE KEY UPDATE nombre=nombre;

-- =====================================================
-- VISTAS PARA REPORTES
-- =====================================================

CREATE OR REPLACE VIEW vista_resumen_departamento AS
SELECT 
    d.id as departamento_id,
    d.nombre as departamento,
    COUNT(DISTINCT dv.id) as total_registros,
    SUM(dv.programado) as total_programado,
    SUM(dv.ejecutado) as total_ejecutado,
    ROUND(CASE WHEN SUM(dv.programado) > 0 
        THEN (SUM(dv.ejecutado) / SUM(dv.programado)) * 100 
        ELSE 0 END, 2) as porcentaje_ejecucion,
    SUM(dv.hombres) as total_hombres,
    SUM(dv.mujeres) as total_mujeres,
    SUM(dv.total_personas) as total_beneficiarios,
    SUM(dv.vigente_financiera) as total_financiero_vigente,
    SUM(dv.financiera_ejecutado) as total_financiero_ejecutado
FROM departamentos d
LEFT JOIN datos_vider dv ON d.id = dv.departamento_id
GROUP BY d.id, d.nombre;

CREATE OR REPLACE VIEW vista_resumen_dependencia AS
SELECT 
    dep.id as dependencia_id,
    dep.nombre as dependencia,
    dep.siglas,
    COUNT(DISTINCT dv.id) as total_registros,
    SUM(dv.programado) as total_programado,
    SUM(dv.ejecutado) as total_ejecutado,
    SUM(dv.total_personas) as total_beneficiarios,
    SUM(dv.vigente_financiera) as total_financiero_vigente,
    SUM(dv.financiera_ejecutado) as total_financiero_ejecutado
FROM dependencias dep
LEFT JOIN datos_vider dv ON dep.id = dv.dependencia_id
GROUP BY dep.id, dep.nombre, dep.siglas;

CREATE OR REPLACE VIEW vista_resumen_municipio AS
SELECT 
    m.id as municipio_id,
    m.nombre as municipio,
    d.id as departamento_id,
    d.nombre as departamento,
    COUNT(DISTINCT dv.id) as total_registros,
    SUM(dv.programado) as total_programado,
    SUM(dv.ejecutado) as total_ejecutado,
    SUM(dv.total_personas) as total_beneficiarios,
    SUM(dv.vigente_financiera) as total_financiero_vigente,
    SUM(dv.financiera_ejecutado) as total_financiero_ejecutado
FROM municipios m
JOIN departamentos d ON m.departamento_id = d.id
LEFT JOIN datos_vider dv ON m.id = dv.municipio_id
GROUP BY m.id, m.nombre, d.id, d.nombre;
