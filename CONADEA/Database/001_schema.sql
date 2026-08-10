-- =====================================================================
-- CONADEA · MAGA AgroIA — Schema MySQL (Prompt 2: Login / Registro)
-- Tablas: roles, departamentos, municipios, usuarios
-- Motor: InnoDB · Charset: utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- roles
-- 3 roles fijos del sistema: Administrador, Supervisor, Usuario.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(30)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- departamentos
-- Catálogo fijo: los 22 departamentos de Guatemala.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS departamentos (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departamentos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- municipios
-- Catálogo fijo: los 340 municipios de Guatemala, cada uno ligado a
-- su departamento. El formulario de Registro llena primero
-- departamento_id y con eso filtra este catálogo.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS municipios (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    departamento_id  INT UNSIGNED NOT NULL,
    nombre           VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_municipios_depto_nombre (departamento_id, nombre),
    KEY idx_municipios_departamento (departamento_id),
    CONSTRAINT fk_municipios_departamento
        FOREIGN KEY (departamento_id) REFERENCES departamentos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- usuarios
-- Cuenta de acceso. El registro público solo crea rol "Usuario"
-- (Administrador/Supervisor se asignan luego desde el panel).
-- El teléfono queda guardado para vincular el número con la API de
-- WhatsApp (identificar al agricultor que escribe y abrirle su curso).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_completo  VARCHAR(150) NOT NULL,
    usuario          VARCHAR(50)  NOT NULL,
    password_hash    VARCHAR(255) NOT NULL,
    telefono         VARCHAR(20)  NOT NULL,
    departamento_id  INT UNSIGNED NOT NULL,
    municipio_id     INT UNSIGNED NOT NULL,
    rol_id           INT UNSIGNED NOT NULL,
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    UNIQUE KEY uq_usuarios_telefono (telefono),
    KEY idx_usuarios_departamento (departamento_id),
    KEY idx_usuarios_municipio (municipio_id),
    KEY idx_usuarios_rol (rol_id),
    CONSTRAINT fk_usuarios_departamento
        FOREIGN KEY (departamento_id) REFERENCES departamentos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_usuarios_municipio
        FOREIGN KEY (municipio_id) REFERENCES municipios (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_usuarios_rol
        FOREIGN KEY (rol_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
