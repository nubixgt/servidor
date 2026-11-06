-- Archived plan generations, ported from PaginaLocal.
--
-- PaginaLocal archives each generation into data/storage/<profile>/history/<ISO>/
-- BEFORE the new one overwrites the active plan, so nothing is ever lost.
-- Here it is one row per archived generation.
--
-- Run ONCE, after 006_create_athlete_plans.sql.
--
-- The ACTIVE plan lives in athlete_plans and is NOT in this table — an entry is
-- created only when the next generation replaces it.

CREATE TABLE IF NOT EXISTS athlete_plan_history (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    kind           ENUM('season','week') NOT NULL,   -- what produced the archived state

    season_plan_md MEDIUMTEXT NULL,
    season_phases  JSON       NULL,
    weekly_plan    JSON       NULL,
    summary        JSON       NULL,

    generated_at   DATETIME   NULL,                  -- when the archived plan was generated
    archived_at    DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_plan_history_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_plan_history_user (user_id, archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Which finished generation was last persisted, so polling generate-status
-- repeatedly doesn't archive/save the same result over and over.
ALTER TABLE athlete_plans
    ADD COLUMN last_generation_job VARCHAR(40) NULL AFTER week_generated_at;
