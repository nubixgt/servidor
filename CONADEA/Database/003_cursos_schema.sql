-- =====================================================================
-- CONADEA · MAGA AgroIA — Schema MySQL: Cursos (creación exclusiva de
-- Administrador, según el modelo Curso/Leccion/PreguntaQuiz que ya existe
-- en app_conadea/lib/data/models/curso.dart)
-- Tablas: cursos, lecciones, quiz_preguntas, quiz_opciones
-- Motor: InnoDB · Charset: utf8mb4
-- Ejecutar después de 001_schema.sql y 002_data_inicial.sql.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- cursos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cursos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    icono       VARCHAR(10)  NOT NULL,
    titulo      VARCHAR(150) NOT NULL,
    descripcion TEXT         NOT NULL,
    imagen_url  VARCHAR(500) NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- lecciones
-- Contenido del curso, en el orden en que se deben estudiar.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lecciones (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    curso_id  INT UNSIGNED NOT NULL,
    orden     SMALLINT UNSIGNED NOT NULL,
    titulo    VARCHAR(150) NOT NULL,
    contenido TEXT         NOT NULL,
    PRIMARY KEY (id),
    KEY idx_lecciones_curso (curso_id),
    CONSTRAINT fk_lecciones_curso
        FOREIGN KEY (curso_id) REFERENCES cursos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- quiz_preguntas
-- Evaluación final del curso.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_preguntas (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    curso_id  INT UNSIGNED NOT NULL,
    orden     SMALLINT UNSIGNED NOT NULL,
    pregunta  TEXT         NOT NULL,
    PRIMARY KEY (id),
    KEY idx_quiz_preguntas_curso (curso_id),
    CONSTRAINT fk_quiz_preguntas_curso
        FOREIGN KEY (curso_id) REFERENCES cursos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- quiz_opciones
-- Opciones de cada pregunta; exactamente una marcada como correcta
-- (se valida en el Backend, no aquí, para no depender de triggers).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_opciones (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pregunta_id  INT UNSIGNED NOT NULL,
    orden        SMALLINT UNSIGNED NOT NULL,
    texto        VARCHAR(300) NOT NULL,
    es_correcta  TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_quiz_opciones_pregunta (pregunta_id),
    CONSTRAINT fk_quiz_opciones_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES quiz_preguntas (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
