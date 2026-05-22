-- Script principal de Base de Datos para SysDipu
-- Aquí se irán añadiendo todas las tablas que formen parte del sistema

-- ==========================================
-- 1. ESTRUCTURA PARA EL SISTEMA DE LOGIN
-- ==========================================

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    usuario VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'tecnico', 'readonly') NOT NULL DEFAULT 'readonly',
    categoria_asignada VARCHAR(100) NULL COMMENT 'Módulo asignado al técnico',
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
    titulo_puesto VARCHAR(255) DEFAULT NULL,
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
    file_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fiscalizacion_ministros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ministerio_id INT NOT NULL,
    nombre_ministro VARCHAR(255) DEFAULT 'Pendiente',
    foto_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `ministerio_id` (`ministerio_id`)
);

CREATE TABLE IF NOT EXISTS fiscalizacion_alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    sicoin_alerts TINYINT(1) DEFAULT 1,
    documento_alerts TINYINT(1) DEFAULT 1,
    critica_alerts TINYINT(1) DEFAULT 1,
    personal_alerts TINYINT(1) DEFAULT 1,
    canal VARCHAR(50) DEFAULT 'email',
    frecuencia VARCHAR(50) DEFAULT 'instante',
    estado TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
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

-- ==========================================
-- 4. ESTRUCTURA PARA EL MODULO DE INICIATIVAS
-- ==========================================

CREATE TABLE IF NOT EXISTS iniciativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referencia VARCHAR(50) NOT NULL UNIQUE,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Borrador',
    fecha DATE NOT NULL,
    autor VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 5. ESTRUCTURA PARA EL MODULO DE CITACIONES
-- ==========================================

CREATE TABLE IF NOT EXISTS citaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    citado VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    tipo VARCHAR(100) NOT NULL,
    fecha DATE NOT NULL,
    hora VARCHAR(100) NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Programada',
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 6. ESTRUCTURA PARA EL MODULO DE COMISIONES
-- ==========================================

CREATE TABLE IF NOT EXISTS comisiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    presidente VARCHAR(150) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'En Sesión',
    dictamenes INT DEFAULT 0,
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 7. ESTRUCTURA PARA EL MODULO DE COMPROMISOS DISTRITALES
-- ==========================================

CREATE TABLE IF NOT EXISTS compromisos_distritales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    lugar VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    compromiso TEXT NULL,
    tipo VARCHAR(100) NOT NULL,
    fecha DATE NOT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',
    avance INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 8. ESTRUCTURA PARA EL MODULO DE ACTIVIDADES
-- ==========================================

CREATE TABLE IF NOT EXISTS actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    lugar VARCHAR(255) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    fecha DATE NOT NULL,
    hora VARCHAR(100) NULL,
    descripcion TEXT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Programada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 9. ESTRUCTURA PARA EL MODULO DE REDES SOCIALES
-- ==========================================

CREATE TABLE IF NOT EXISTS redes_sociales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    plataforma VARCHAR(50) NOT NULL,
    enlace VARCHAR(500) NOT NULL,
    fecha DATE NOT NULL,
    hora VARCHAR(50) NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Publicado',
    impacto VARCHAR(100) NOT NULL DEFAULT 'Medio',
    interacciones VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 10. ESTRUCTURA PARA EL MODULO DE AFILIACIONES POLITICAS
-- ==========================================

CREATE TABLE IF NOT EXISTS afiliaciones_politicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    dpi VARCHAR(50) NOT NULL UNIQUE,
    municipio VARCHAR(255) NOT NULL,
    tipo_registro VARCHAR(100) NOT NULL,
    fecha_ingreso DATE NOT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 11. ESTRUCTURA PARA PRESUPUESTOS SICOIN
-- ==========================================

CREATE TABLE IF NOT EXISTS presupuestos_sicoin (
    id INT PRIMARY KEY,
    datos_json LONGTEXT NOT NULL,
    fecha_actualizacion DATETIME NOT NULL
);

-- ==========================================
-- 12. ESTRUCTURA PARA EL ARCHIVO CENTRAL
-- ==========================================

CREATE TABLE IF NOT EXISTS archivo_central (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expediente_id VARCHAR(50) NOT NULL UNIQUE,
    titulo VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    fecha DATE NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    file_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
