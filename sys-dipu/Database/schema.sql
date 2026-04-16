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
