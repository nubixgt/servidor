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
