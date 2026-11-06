-- Pre-week check-in, ported from PaginaLocal's services/ai/checkin.py.
--
-- The athlete's own words right before the week is planned: the Hooper index
-- (sleep / fatigue / stress / soreness, each 1-7 where 7 is worst), plus
-- motivation, a localised niggle, what got in the way last week, and what the
-- week ahead looks like. Read by the AI weekly planner in a later phase; for
-- now it is a manual form.
--
-- Run ONCE, after 001_create_users_table.sql.
--
-- One check-in per athlete per day: a second submission the same day REPLACES
-- the first (INSERT ... ON DUPLICATE KEY UPDATE on the unique key), so an
-- athlete opening the form twice before generating doesn't skew their baseline.

CREATE TABLE IF NOT EXISTS athlete_checkins (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    checkin_date     DATE         NOT NULL,

    -- Hooper index, 1-7, same direction for all four (1 = best, 7 = worst)
    sleep            TINYINT UNSIGNED NULL,
    fatigue          TINYINT UNSIGNED NULL,
    stress           TINYINT UNSIGNED NULL,
    soreness         TINYINT UNSIGNED NULL,

    motivation       TINYINT UNSIGNED NULL,       -- 1-7, here 7 = most motivation
    niggle_location  VARCHAR(160) NOT NULL DEFAULT '',
    niggle_pain      TINYINT UNSIGNED NULL,       -- 0-10
    last_week_note   TEXT         NULL,
    week_ahead       VARCHAR(20)  NOT NULL DEFAULT '',   -- normal|viaje|carga_laboral|poco_sueno

    hooper_total     TINYINT UNSIGNED NULL,       -- 4-28, only when all four Hooper items answered
    recorded_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_checkin_user_date (user_id, checkin_date),
    CONSTRAINT fk_athlete_checkins_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
