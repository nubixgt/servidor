-- v1.2 — lo que el atleta nos ESCRIBE, no sólo lo que elige.
--
-- Hasta aquí el feedback eran dos cosas: un motivo cerrado al borrar (una
-- palabra en app_events.detail, VARCHAR(191)) y un botón que abre WhatsApp.
-- La primera suma bien pero no explica; la segunda explica pero no se puede
-- leer junta ni saber de qué pantalla vino, y muchos no escriben por WhatsApp
-- a alguien que conocen.
--
-- Una fila por mensaje, con el contexto de dónde se escribió:
--   borrar_semana / borrar_plan   el "cuéntanos más" del diálogo de borrado
--   general                       el botón "Reportar", en cualquier pantalla
-- `kind` es lo que el atleta dice que es (fallo / sugerencia / me_gusta) y
-- `reason` el motivo cerrado cuando lo hay, para poder cruzar los dos.
--
-- Idempotente: se puede correr dos veces. El código aguanta que no se haya
-- corrido (FeedbackRepository::save se traga el error y lo registra).

CREATE TABLE IF NOT EXISTS athlete_feedback (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    context     VARCHAR(40)  NOT NULL,
    kind        VARCHAR(20)  NULL,
    reason      VARCHAR(40)  NULL,
    message     TEXT         NOT NULL,
    page        VARCHAR(191) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_created (created_at),
    INDEX idx_feedback_user (user_id),
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
