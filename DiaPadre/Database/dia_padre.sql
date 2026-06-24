-- ============================================================
--  Día del Padre MAGA - Script de base de datos
--  Ejecutar en MySQL/MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS visionwe_DiaPadre
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE visionwe_DiaPadre;

-- ------------------------------------------------------------
--  Tabla: registros_padres
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS registros_padres (
    id               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre_completo  VARCHAR(255)    NOT NULL,
    telefono         VARCHAR(15)     NOT NULL,
    correo           VARCHAR(255)    NOT NULL,
    direccion        VARCHAR(100)    NOT NULL,
    departamento     VARCHAR(100)    NOT NULL,
    fecha_registro   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
