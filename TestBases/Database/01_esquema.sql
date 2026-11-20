-- =====================================================================
--  TestBases · 01 · ESQUEMA
--  Crea la base de datos y todas las tablas. No inserta datos.
--
--  Orden de ejecución (manual, una sola vez, en este orden):
--    01_esquema.sql                    <- este archivo
--    02_catalogos_y_usuarios.sql       roles y usuarios iniciales
--    03_carga_beneficiarios.sql        lo genera herramientas/generar_carga.py
--
--  Convenciones
--    · Tablas en plural y en minúsculas, separadas por guion bajo.
--    · Toda llave primaria es `id` (INT UNSIGNED AUTO_INCREMENT).
--    · Llaves foráneas: `<tabla_singular>_id`.
--    · Todo en InnoDB + utf8mb4 (acentos y ñ correctos, búsquedas sin
--      distinguir mayúsculas ni tildes con utf8mb4_unicode_ci).
--
--  Mapa de tablas
--    SEGURIDAD        roles ──< usuarios
--    DATOS            comunidades ──< beneficiarios >── comunidades_base
--    SEGUIMIENTO      beneficiarios ──1:1── seguimientos >── usuarios
-- =====================================================================

-- En cPanel la base ya viene creada y asignada al usuario: si es tu caso,
-- comenta las dos líneas siguientes y selecciona la base en phpMyAdmin.
CREATE DATABASE IF NOT EXISTS testbases
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE testbases;

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  SEGURIDAD
-- ---------------------------------------------------------------------

-- Catálogo de roles. El `codigo` es lo que viaja en el token (JWT) y lo que
-- el backend compara en #[Authorize]. No cambiar los códigos una vez en uso.
CREATE TABLE roles (
    id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo      VARCHAR(20)      NOT NULL,
    nombre      VARCHAR(60)      NOT NULL,
    descripcion VARCHAR(255)     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas que pueden iniciar sesión. La clave se guarda con password_hash()
-- (bcrypt), jamás en texto plano.
CREATE TABLE usuarios (
    id                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    rol_id            TINYINT UNSIGNED NOT NULL,
    usuario           VARCHAR(40)      NOT NULL,
    clave_hash        VARCHAR(255)     NOT NULL,
    nombre_completo   VARCHAR(120)     NOT NULL,
    activo            TINYINT(1)       NOT NULL DEFAULT 1,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta   DATETIME         NULL,
    ultimo_acceso     DATETIME         NULL,
    creado_en         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    KEY ix_usuarios_rol (rol_id),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  DATOS (provienen del Excel "Base Entrega_.xlsx")
-- ---------------------------------------------------------------------

-- Columna COMUNIDAD del Excel (37 valores distintos).
CREATE TABLE comunidades (
    id     SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120)      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_comunidades_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columna COMUNIDAD BASE del Excel (~80 valores distintos). Es un dato
-- distinto de `comunidades`: se guarda aparte para no mezclarlos.
CREATE TABLE comunidades_base (
    id     SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120)      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_comunidades_base_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Una fila por beneficiario (una fila del Excel = una fila aquí).
--
--  numero            Columna "No." del Excel: correlativo DENTRO de la comunidad.
--                    Se repite (p. ej. parejas comparten número), por eso no es único.
--  cui               13 dígitos, único. CHAR y no número: no pierde ceros ni
--                    se vuelve notación científica como en Excel.
--  telefono          NULL cuando el Excel traía 0 o un valor inválido.
--  comunidad_base_id NULL cuando la celda venía vacía.
--  resaltado_origen  Color con que venía marcada la fila en el Excel
--                    (rojo / ámbar). Se conserva tal cual; su significado
--                    está por confirmar con quien compartió la base.
--  fila_origen       Número de fila en el Excel, para poder auditar la carga.
CREATE TABLE beneficiarios (
    id                INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    comunidad_id      SMALLINT UNSIGNED NOT NULL,
    comunidad_base_id SMALLINT UNSIGNED NULL,
    numero            SMALLINT UNSIGNED NOT NULL,
    nombre            VARCHAR(150)      NOT NULL,
    cui               CHAR(13)          NOT NULL,
    telefono          VARCHAR(20)       NULL,
    resaltado_origen  ENUM('ninguno','rojo','ambar') NOT NULL DEFAULT 'ninguno',
    fila_origen       INT UNSIGNED      NOT NULL,
    creado_en         DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_beneficiarios_cui (cui),
    UNIQUE KEY uq_beneficiarios_fila_origen (fila_origen),
    KEY ix_beneficiarios_comunidad (comunidad_id, numero),
    KEY ix_beneficiarios_comunidad_base (comunidad_base_id),
    KEY ix_beneficiarios_nombre (nombre),
    CONSTRAINT fk_beneficiarios_comunidad
        FOREIGN KEY (comunidad_id) REFERENCES comunidades (id),
    CONSTRAINT fk_beneficiarios_comunidad_base
        FOREIGN KEY (comunidad_base_id) REFERENCES comunidades_base (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  SEGUIMIENTO (lo que se agrega sobre cada fila: imagen + comentario)
--  Va en tabla aparte para que los datos de origen del Excel nunca se
--  mezclen con lo que capturan los usuarios.
-- ---------------------------------------------------------------------

-- Máximo un seguimiento por beneficiario (uq_seguimientos_beneficiario).
-- La imagen se guarda en disco (Backend/uploads/adjuntos); aquí solo su
-- nombre de archivo y metadatos. Todos los campos de imagen son NULL o
-- ninguno lo es.
CREATE TABLE seguimientos (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    beneficiario_id  INT UNSIGNED  NOT NULL,
    comentario       TEXT          NULL,
    imagen_archivo   VARCHAR(80)   NULL,
    imagen_nombre    VARCHAR(255)  NULL,
    imagen_mime      VARCHAR(40)   NULL,
    imagen_bytes     INT UNSIGNED  NULL,
    creado_por       INT UNSIGNED  NOT NULL,
    actualizado_por  INT UNSIGNED  NOT NULL,
    creado_en        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_seguimientos_beneficiario (beneficiario_id),
    KEY ix_seguimientos_actualizado_por (actualizado_por),
    KEY ix_seguimientos_creado_por (creado_por),
    CONSTRAINT fk_seguimientos_beneficiario
        FOREIGN KEY (beneficiario_id) REFERENCES beneficiarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_seguimientos_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id),
    CONSTRAINT fk_seguimientos_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
