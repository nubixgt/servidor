-- Coach's notebook, ported from PaginaLocal's services/ai/notebook.py.
--
-- Durable facts the athlete has told the coach, with a date. Read by the AI
-- weekly/season planner and the coach chat in later phases; for now it is a
-- manual CRUD list in the Cuaderno section.
--
-- Run ONCE, after 001_create_users_table.sql.
--
-- Semantics (from PaginaLocal):
--   * status 'vigente'   -> active, feeds the plan
--   * status 'propuesta' -> suggested (by the AI, or added as a draft), awaits confirmation
--   * status 'retirada'  -> withdrawn: kept with a retired_at date, NOT deleted.
--   Retiring is what the coach can do; a hard DELETE is the athlete's right only.

CREATE TABLE IF NOT EXISTS athlete_notebook (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    text            TEXT         NOT NULL,
    category        VARCHAR(40)  NOT NULL DEFAULT 'otro',
    quote           TEXT         NULL,
    source          ENUM('manual','chat') NOT NULL DEFAULT 'manual',
    status          ENUM('vigente','propuesta','retirada') NOT NULL DEFAULT 'vigente',
    retired_at      DATETIME     NULL,
    retired_reason  VARCHAR(255) NOT NULL DEFAULT '',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_athlete_notebook_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notebook_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
