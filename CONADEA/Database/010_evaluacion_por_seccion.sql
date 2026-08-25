-- =====================================================================
-- CONADEA · MAGA AgroIA — Schema MySQL: Evaluaciones por Lección
-- Migración para trasladar los quizzes (evaluaciones) desde el nivel de 
-- curso hacia el nivel de lección (sección).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Eliminar la relación de quiz_preguntas con el curso
ALTER TABLE quiz_preguntas DROP FOREIGN KEY fk_quiz_preguntas_curso;
ALTER TABLE quiz_preguntas DROP INDEX idx_quiz_preguntas_curso;
ALTER TABLE quiz_preguntas DROP COLUMN curso_id;

-- 2. Agregar la relación de quiz_preguntas con la lección
ALTER TABLE quiz_preguntas ADD COLUMN leccion_id INT UNSIGNED NOT NULL AFTER id;
ALTER TABLE quiz_preguntas ADD CONSTRAINT fk_quiz_preguntas_leccion 
    FOREIGN KEY (leccion_id) REFERENCES lecciones(id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE quiz_preguntas ADD INDEX idx_quiz_preguntas_leccion (leccion_id);

-- 3. Agregar el campo de nota en el progreso de la lección para guardar la puntuación obtenida
ALTER TABLE progreso_lecciones ADD COLUMN nota INT UNSIGNED NULL AFTER completada;

SET FOREIGN_KEY_CHECKS = 1;
