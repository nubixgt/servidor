-- Athlete profile, ported from PaginaLocal's UserProfileModel
-- (PaginaLocal/web_app/server.py, class UserProfileModel + CompetitionModel + InjuryModel).
--
-- Run ONCE, after 001_create_users_table.sql.
--
-- The profile is per-authenticated-user: one row in athlete_profiles per users.id
-- (NOT keyed by a name string like PaginaLocal does). Column names match
-- PaginaLocal's field names so the PHP <-> Python payload mapping stays trivial;
-- the only renames are name -> full_name (users.name is the Google account name,
-- full_name is the athlete name used in AI prompts) and, on competitions,
-- date -> race_date.
--
-- JSON columns are always read/written whole (never queried by key). They need
-- MySQL 5.7+ / MariaDB 10.2+. If the server is older, change every `JSON` below
-- to `LONGTEXT` -- the PHP repository already json_decode()s them, so nothing
-- else changes.
--
-- The Garmin password is NOT stored in this phase (garmin_password_enc is
-- reserved but unused); the frontend keeps it in the form only for the session.

CREATE TABLE IF NOT EXISTS athlete_profiles (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,

    -- A. Identidad y Deporte
    full_name                         VARCHAR(255)   NOT NULL DEFAULT '',
    garmin_email                      VARCHAR(255)   NOT NULL DEFAULT '',
    garmin_password_enc               VARBINARY(512) NULL,            -- reserved, unused this phase
    sports_preference                 ENUM('triathlon','run','swim','bike') NOT NULL DEFAULT 'triathlon',
    target_distance                   VARCHAR(120)   NULL,
    triathlon_distance                VARCHAR(60)    NULL,
    run_distance                      VARCHAR(60)    NULL,
    bike_distance                     VARCHAR(60)    NULL,

    -- Disponibilidad / Horarios
    available_days                    JSON           NULL,           -- ["Lunes", ...]
    hours_per_day                     JSON           NULL,           -- {"Lunes": 1.5, ...}
    activities_per_day                JSON           NULL,           -- {"Lunes": ["Correr","Nadar"], ...}
    day_time_blocks                   JSON           NULL,           -- {"Lunes":[{start,end,sports:[]}], ...}
    available_hours                   DECIMAL(5,2)   NOT NULL DEFAULT 8.00,
    limitations                       TEXT           NULL,

    -- B. Objetivos (context; competiciones -> tabla hija)
    planning_context                  TEXT           NULL,
    analysis_context                  TEXT           NULL,

    -- Umbrales / D. Fisiología
    lactate_threshold_hr              DECIMAL(5,1)   NULL,
    lactate_threshold_pace_sec_per_km DECIMAL(6,1)   NULL,
    ftp_watts                         DECIMAL(6,1)   NULL,
    swim_threshold_pace_sec_per_100m  DECIMAL(6,1)   NULL,
    weight_kg                         DECIMAL(5,2)   NULL,
    height_cm                         DECIMAL(5,1)   NULL,
    gender                            VARCHAR(20)    NULL,           -- 'female'|'male'|'other'
    birth_date                        DATE           NULL,
    resting_heart_rate                SMALLINT UNSIGNED NULL,
    vo2max_running                    DECIMAL(4,1)   NULL,
    vo2max_cycling                    DECIMAL(4,1)   NULL,

    -- C. Historial y Nivel
    training_age_level                ENUM('principiante','intermedio','avanzado','elite') NULL,
    years_training                    DECIMAL(4,1)   NULL,
    current_weekly_volume_hours       DECIMAL(4,1)   NULL,
    longest_recent_session_hours      DECIMAL(4,1)   NULL,
    recent_prs                        JSON           NULL,           -- {"run_5k":"22:30","notes":"..."}

    -- E. Equipo y Entorno
    pool_access                       ENUM('ninguno','limitado','completo') NULL,
    brick_preference                  ENUM('si','no') NULL,
    bike_setup                        ENUM('outdoor','rodillo','ambos') NULL,
    treadmill_access                  TINYINT(1)     NOT NULL DEFAULT 0,
    altitude_m                        DECIMAL(6,1)   NULL,
    climate                           ENUM('templado','calido_humedo','frio','variable') NULL,
    terrain                           ENUM('llano','montanoso','mixto') NULL,

    -- F. Estilo de Vida y Recuperación
    occupation_type                   ENUM('sedentario','activo_fisico','mixto') NULL,
    avg_sleep_hours                   DECIMAL(3,1)   NULL,
    sleep_source                      ENUM('garmin','manual') NULL,
    stress_level_self_report          ENUM('bajo','medio','alto') NULL,
    travel_frequency                  VARCHAR(20)    NULL,           -- 'nunca'|'ocasional'|'frecuente'
    nutrition_notes                   TEXT           NULL,
    menstrual_tracking_opt_in         TINYINT(1)     NOT NULL DEFAULT 0,
    menstrual_cycle_notes             TEXT           NULL,

    -- G. Salud (lesiones actuales -> tabla hija)
    past_injuries_surgeries           TEXT           NULL,
    chronic_conditions                TEXT           NULL,
    medications                       TEXT           NULL,
    par_q_flags                       JSON           NULL,           -- {"q1":false, ..., "q7":false}
    last_medical_evaluation           DATETIME       NULL,
    medical_snapshot_hash             VARCHAR(64)    NULL,
    doctor_performance_report         MEDIUMTEXT     NULL,
    doctor_chat_history               JSON           NULL,           -- [{role,content}, ...]
    health_disclaimer_accepted        TINYINT(1)     NOT NULL DEFAULT 0,
    health_disclaimer_accepted_at     DATETIME       NULL,

    -- H. Preferencias de Coaching
    coaching_tone                     ENUM('directo','empatico','tecnico') NULL,
    strength_training_preference      ENUM('si','no','indiferente') NULL,
    preferred_rest_day                VARCHAR(15)    NULL,           -- 'Lunes'..'Domingo'
    cross_training_open               TINYINT(1)     NOT NULL DEFAULT 1,
    ai_load_guardrail_enabled         TINYINT(1)     NOT NULL DEFAULT 1,

    -- Sistema / no editable en UI
    ai_mode                           VARCHAR(20)    NOT NULL DEFAULT 'pro',
    garmin_autofilled_fields          JSON           NULL,           -- ["weight_kg", ...]
    completion_rate                   TINYINT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_athlete_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS athlete_competitions (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    name             VARCHAR(255) NOT NULL DEFAULT '',
    race_date        DATE         NULL,                       -- PaginaLocal key: "date"
    race_type        VARCHAR(60)  NOT NULL DEFAULT 'Olympic',
    priority         ENUM('A','B','C') NOT NULL DEFAULT 'B',
    target_time      VARCHAR(20)  NOT NULL DEFAULT '',
    distance         VARCHAR(60)  NOT NULL DEFAULT '',         -- "5K"|"10K"|"half_marathon"|"marathon"|""|...
    elevation_gain_m INT UNSIGNED NULL,
    sort_order       INT UNSIGNED NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_athlete_competitions_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_competitions_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS athlete_injuries (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    location            VARCHAR(120) NOT NULL DEFAULT '',
    laterality          ENUM('izquierda','derecha','bilateral') NULL,
    onset               VARCHAR(120) NOT NULL DEFAULT '',
    pain_scale          TINYINT UNSIGNED NULL,                 -- 0-10
    aggravating_factors TEXT         NULL,
    treatment           TEXT         NULL,
    sort_order          INT UNSIGNED NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_athlete_injuries_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_injuries_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
