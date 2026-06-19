-- Script de base de datos para SIGIE
-- Base de datos: visionwe_sigie

CREATE DATABASE IF NOT EXISTS visionwe_sigie CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE visionwe_sigie;

-- ==========================================
-- 1. TABLA DE USUARIOS (SISTEMA DE AUTENTICACION)
-- ==========================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    usuario VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'inspector') NOT NULL DEFAULT 'inspector',
    estado TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 2. TABLA DE PERFILES DE INSPECTORES
-- ==========================================
CREATE TABLE IF NOT EXISTS inspectores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    area VARCHAR(100) NOT NULL,
    estado TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 3. TABLA DE VISITAS / INSPECCIONES PROGRAMADAS
-- ==========================================
CREATE TABLE IF NOT EXISTS visitas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NOT NULL,
    establecimiento VARCHAR(150) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    fecha_programada DATE NOT NULL,
    tipo_inspeccion VARCHAR(100) NOT NULL,
    estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspector_id) REFERENCES inspectores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 4. TABLA DE REGISTROS DE CHECK-IN
-- ==========================================
CREATE TABLE IF NOT EXISTS check_ins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visita_id INT UNSIGNED NULL,
    inspector_id INT UNSIGNED NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hora_ingreso DATETIME NULL,
    hora_salida DATETIME NULL,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    observaciones TEXT NULL,
    firma_path VARCHAR(255) NULL COMMENT 'Ruta de la imagen de la firma',
    foto_path VARCHAR(255) NULL COMMENT 'Ruta de la foto de la inspección',
    estado ENUM('exitoso', 'con_novedades') DEFAULT 'exitoso',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visita_id) REFERENCES visitas(id) ON DELETE SET NULL,
    FOREIGN KEY (inspector_id) REFERENCES inspectores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 5. TABLA DE REGISTROS DE ANIMALES SACRIFICADOS
-- ==========================================
CREATE TABLE IF NOT EXISTS animales_sacrificados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NULL,
    fecha_sacrificio DATE NOT NULL,
    procedencia_departamento VARCHAR(100) NOT NULL,
    procedencia_municipio VARCHAR(100) NOT NULL,
    procedencia_finca VARCHAR(150) NOT NULL,
    clasificacion VARCHAR(50) NOT NULL, -- vaca, novillo, toro, ternero, etc.
    lote VARCHAR(50) NOT NULL,
    propietario VARCHAR(150) NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    decomisos TEXT NULL,
    muestreo_oficial TINYINT(1) DEFAULT 0,
    documento_path VARCHAR(255) NULL COMMENT 'Ruta del documento adjunto (PDF, imagen)',
    observaciones TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspector_id) REFERENCES inspectores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 6. TABLA DE SEGUIMIENTO A DESVIACIONES DE LABORATORIO
-- ==========================================
CREATE TABLE IF NOT EXISTS desviaciones_laboratorio (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NULL,
    fecha_resultado DATE NOT NULL,
    codigo_muestra VARCHAR(50) NOT NULL,
    establecimiento VARCHAR(150) NOT NULL,
    tipo_analisis VARCHAR(100) NOT NULL,
    resultado_obtenido TEXT NOT NULL,
    parametro_fuera_norma VARCHAR(150) NOT NULL,
    accion_tomada TEXT NOT NULL,
    estado_seguimiento ENUM('Abierto', 'En proceso', 'Cerrado') NOT NULL DEFAULT 'Abierto',
    observaciones TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspector_id) REFERENCES inspectores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 7. TABLA DE DOCUMENTOS DE SOPORTE DE DESVIACIONES
-- ==========================================
CREATE TABLE IF NOT EXISTS desviaciones_documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    desviacion_id INT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(150) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (desviacion_id) REFERENCES desviaciones_laboratorio(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 8. TABLA DE SUPERVISIONES A ESTABLECIMIENTOS
-- ==========================================
CREATE TABLE IF NOT EXISTS supervisiones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NULL,
    fecha_supervision DATE NOT NULL,
    establecimiento VARCHAR(150) NOT NULL,
    hallazgos_detectados TEXT NOT NULL,
    norma_especifica VARCHAR(255) NULL COMMENT 'Artículo, acuerdo ministerial, gubernativo o manual',
    observaciones TEXT NULL,
    estado_hallazgo ENUM('Abierto', 'En proceso', 'Cerrado') NOT NULL DEFAULT 'Abierto',
    fecha_cumplimiento DATE NULL,
    verificacion_oficial TEXT NULL COMMENT 'Detalle de verificación del oficial asignado',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspector_id) REFERENCES inspectores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 9. TABLA DE DOCUMENTOS DE SOPORTE DE SUPERVISIONES
-- ==========================================
CREATE TABLE IF NOT EXISTS supervisiones_documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supervision_id INT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(150) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supervision_id) REFERENCES supervisiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 10. TABLA DE NO CONFORMIDADES EN INSPECCION PERMANENTE
-- ==========================================
CREATE TABLE IF NOT EXISTS no_conformidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inspector_id INT UNSIGNED NULL,
    fecha_inspeccion DATE NOT NULL,
    establecimiento VARCHAR(150) NOT NULL COMMENT 'Dirigido a personal asignado a rastros',
    hallazgos_detectados TEXT NOT NULL,
    norma_especifica VARCHAR(255) NULL COMMENT 'Artículo, acuerdo ministerial, gubernativo o manual',
    observaciones TEXT NULL,
    estado_hallazgo ENUM('Abierto', 'En proceso', 'Cerrado') NOT NULL DEFAULT 'Abierto',
    fecha_cumplimiento DATE NULL,
    verificacion_oficial TEXT NULL COMMENT 'Detalle de verificación del oficial asignado',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspector_id) REFERENCES inspectores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 11. TABLA DE DOCUMENTOS DE SOPORTE DE NO CONFORMIDADES
-- ==========================================
CREATE TABLE IF NOT EXISTS no_conformidades_documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    no_conformidad_id INT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(150) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (no_conformidad_id) REFERENCES no_conformidades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


