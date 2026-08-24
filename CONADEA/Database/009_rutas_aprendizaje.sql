-- =====================================================================
-- CONADEA · MAGA AgroIA — Schema MySQL: Rutas de aprendizaje
-- Una ruta agrupa varios cursos reales según la actividad productiva
-- (Ganadería, Agrícola, etc.) — equivalente real a RUTAS en
-- Frontend/src/data/local.js. Creación/edición exclusiva de
-- Administrador (Backend/src/Controllers/RutaController.php); cualquier
-- usuario autenticado puede verlas.
-- Tablas: rutas_aprendizaje, ruta_cursos
-- Motor: InnoDB · Charset: utf8mb4
-- Ejecutar después de 001, 002 y 003.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- rutas_aprendizaje
-- "color" son los 4 temas de gradiente que ya existen en el CSS de
-- Rutas.vue (.ruta-header / .azul / .oro / .verde) — 'esmeralda' es el
-- gradiente por defecto (sin clase extra) que ya se usaba ahí.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rutas_aprendizaje (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    icono       VARCHAR(10)  NOT NULL,
    titulo      VARCHAR(150) NOT NULL,
    descripcion TEXT         NOT NULL,
    color       ENUM('esmeralda', 'verde', 'azul', 'oro') NOT NULL DEFAULT 'esmeralda',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ruta_cursos
-- Qué cursos (de la tabla real "cursos") pertenecen a cada ruta, y en
-- qué orden se muestran. Si se borra el curso o la ruta, se limpia solo.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ruta_cursos (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ruta_id  INT UNSIGNED NOT NULL,
    curso_id INT UNSIGNED NOT NULL,
    orden    SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ruta_curso (ruta_id, curso_id),
    KEY idx_ruta_cursos_curso (curso_id),
    CONSTRAINT fk_ruta_cursos_ruta
        FOREIGN KEY (ruta_id) REFERENCES rutas_aprendizaje (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ruta_cursos_curso
        FOREIGN KEY (curso_id) REFERENCES cursos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
