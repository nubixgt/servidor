-- 031: lo que el atleta cambia de su plan desde la app (Beta 3.0, F6 mover y F7 ajustar).
--
-- Run ONCE, after 030. Aditiva y repetible.
--
-- ## Por qué
--
-- Mover o ajustar una sesión reescribe `athlete_plans.weekly_plan`, que es lo que se
-- sube al reloj. Sin un registro aparte, lo que decidió el coach se perdería en el primer
-- cambio: no habría «volver a la del coach» (F7), ni forma de saber qué cambió el atleta
-- (para el diagnóstico de «Pedir ayuda» y para que la siguiente semana lo sepa).
--
-- Una fila por sesión tocada:
--   kind            mover | ajustar | revertir
--   session_before  fecha|franja antes del cambio
--   session_after   fecha|franja después
--   original_day    la sesión como la escribió el coach (la primera vez que se toca)
--   before_day      cómo estaba justo antes de este cambio
--   after_day       cómo quedó
--   reverted_at     si se volvió a la del coach (nunca se borra la fila)
--
-- Tolerancia: `PlanEditRepository::disponible()`; sin la tabla se puede mover igual y
-- simplemente no queda registro (se anota en el error_log).

CREATE TABLE IF NOT EXISTS athlete_plan_edits (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    kind            VARCHAR(12) NOT NULL,
    session_before  VARCHAR(24) NOT NULL,
    session_after   VARCHAR(24) NOT NULL,
    original_day    MEDIUMTEXT NULL,
    before_day      MEDIUMTEXT NULL,
    after_day       MEDIUMTEXT NULL,
    summary         VARCHAR(300) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reverted_at     DATETIME NULL,
    INDEX idx_plan_edits_user (user_id, created_at),
    CONSTRAINT fk_plan_edits_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
