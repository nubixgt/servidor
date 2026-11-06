-- 029: «Pedir ayuda» con una persona en el circuito (Beta 3.0, F3).
--
-- Run ONCE, after 028. Aditiva y repetible: comprueba cada columna antes de crearla.
--
-- ## Por qué
--
-- Cuando un atleta de la beta reportaba algo («mi reloj no dice lo mismo», «esta semana no
-- tiene sentido»), llegaba sin nada de lo que hace falta para mirarlo: ni qué sesión, ni
-- qué decidió la IA, ni qué se codificó, ni si el reloj llevaba la versión de hoy. Una
-- consulta de ayuda es una fila de `athlete_feedback` con `context = 'ayuda'` y estas
-- columnas:
--
--   category     plan | reloj | app | datos | otra (las cinco del botón «Ayuda»)
--   status       nuevo | investigando | resuelto
--   session_key  fecha|franja de la sesión desde la que preguntó, si fue desde una
--   diagnostic   JSON que arma el SERVIDOR con una lista blanca (≤ 64 KB; lo recortado
--                se dice dentro). Nunca contraseñas.
--   triage       el origen probable que propone el código: perfil | garmin | codificacion
--                | decision_ia | app | desconocido
--   reply...     la respuesta del administrador, que le llega como aviso a él solo
--
-- `notifications.feedback_id`: el aviso que responde a una consulta.
--
-- Tolerancia: `FeedbackRepository::admiteAyuda()` comprueba las columnas; sin ellas el
-- botón sigue siendo el «Opinar» de siempre.

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'athlete_feedback' AND COLUMN_NAME = 'category');
SET @sql := IF(@hay = 0,
    'ALTER TABLE athlete_feedback
        ADD COLUMN category    VARCHAR(20) NULL AFTER reason,
        ADD COLUMN status      VARCHAR(16) NULL AFTER category,
        ADD COLUMN session_key VARCHAR(40) NULL AFTER status,
        ADD COLUMN diagnostic  MEDIUMTEXT  NULL AFTER page,
        ADD COLUMN triage      VARCHAR(20) NULL AFTER diagnostic,
        ADD COLUMN reply       TEXT        NULL AFTER triage,
        ADD COLUMN replied_at  DATETIME    NULL AFTER reply,
        ADD COLUMN replied_by  INT UNSIGNED NULL AFTER replied_at,
        ADD COLUMN updated_at  TIMESTAMP   NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
        ADD INDEX idx_feedback_ayuda (context, status, created_at)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'feedback_id');
SET @sql := IF(@hay = 0,
    'ALTER TABLE notifications ADD COLUMN feedback_id INT UNSIGNED NULL AFTER user_id,
        ADD CONSTRAINT fk_notifications_feedback FOREIGN KEY (feedback_id) REFERENCES athlete_feedback(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
