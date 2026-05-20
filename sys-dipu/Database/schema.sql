-- Script principal de Base de Datos para SysDipu
-- Aquí se irán añadiendo todas las tablas que formen parte del sistema

-- ==========================================
-- 1. ESTRUCTURA PARA EL SISTEMA DE LOGIN
-- ==========================================

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'tecnico') NOT NULL DEFAULT 'tecnico',
    categoria_asignada VARCHAR(50) NULL COMMENT 'Módulo asignado al técnico',
    estado TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 2. ESTRUCTURA PARA EL MODULO DE FISCALIZACION
-- ==========================================

CREATE TABLE IF NOT EXISTS fiscalizacion_personal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ministerio_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    tipo_puesto VARCHAR(100) NOT NULL,
    sueldo VARCHAR(100) NULL,
    fecha_posesion DATE NULL,
    foto_nombre VARCHAR(255) NULL,
    foto_preview LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fiscalizacion_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    entidad VARCHAR(50) NOT NULL,
    fecha DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 3. ESTRUCTURA PARA EL MODULO DE CALENDARIO
-- ==========================================

CREATE TABLE IF NOT EXISTS calendario_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT NULL,
    files LONGTEXT NULL COMMENT 'Listado de metadatos de archivos adjuntos en formato JSON',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


