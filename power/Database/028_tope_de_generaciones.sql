-- 028: el tope de generaciones (D2) y los avisos para una sola persona (Beta 3.0, F2).
--
-- Run ONCE, after 027. Aditiva y repetible: comprueba cada tabla y columna antes de crearla.
--
-- ## Por qué
--
-- Nada impedía un "agujero negro" de gasto: en la Beta 1 un atleta generó 16 planes en un
-- día, a ~0,30 USD cada uno. Decisión del responsable (2026-10-10): como mucho 3 versiones
-- de una misma semana y 1 macrociclo cada 7 días; los fallos no gastan cupo; la semana
-- automática no cuenta para el tope (sí para la alerta de gasto).
--
-- `athlete_generations` es una fila por generación lanzada: se escribe cuando el AiService
-- acepta el trabajo (`started`) y se cierra cuando termina (`ok` o `error`). `target_week`
-- es el primer día de la semana que se escribe (NULL en un macrociclo).
--
-- `notifications.user_id`: hasta aquí un aviso era para TODOS. La alerta de gasto va sólo
-- al administrador; NULL sigue significando "para todos", así que los avisos de siempre no
-- cambian.
--
-- Tolerancia: `GenerationRepository::disponible()` comprueba la tabla y, si no está, no hay
-- tope (queda en el error_log); `NotificationRepository` comprueba la columna.

CREATE TABLE IF NOT EXISTS athlete_generations (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    kind         VARCHAR(8) NOT NULL COMMENT 'season | week',
    target_week  DATE NULL COMMENT 'Primer día de la semana que se escribe; NULL en un macrociclo.',
    job_id       VARCHAR(64) NULL,
    automatic    TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'La lanzó el script de semanas automáticas.',
    status       VARCHAR(8) NOT NULL DEFAULT 'started' COMMENT 'started | ok | error',
    error        VARCHAR(500) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at  DATETIME NULL,
    INDEX idx_generations_user_kind (user_id, kind, created_at),
    INDEX idx_generations_job (job_id),
    CONSTRAINT fk_generations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'user_id');
SET @sql := IF(@hay = 0,
    'ALTER TABLE notifications ADD COLUMN user_id INT UNSIGNED NULL COMMENT ''NULL = para todos; si no, sólo para esa cuenta.'' AFTER body, ADD INDEX idx_notifications_user (user_id), ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
