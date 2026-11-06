-- Generated training plan, ported from PaginaLocal.
--
-- PaginaLocal stores this as files per profile (season_plan.md, season_phases.json,
-- weekly_plan.json, summary.json). Here it is one row per user holding the same
-- payloads as JSON/text; the AiService bridge produces them and the PHP backend
-- stores what it gets back.
--
-- Run ONCE, after 001_create_users_table.sql.
--
-- Flow: generate-season writes season_plan_md + season_phases; then generate-week
-- writes weekly_plan (appending each new 7-day block). summary holds cost +
-- validation + adherence + prescription for the last generation.

CREATE TABLE IF NOT EXISTS athlete_plans (
    user_id             INT UNSIGNED NOT NULL PRIMARY KEY,

    season_plan_md      MEDIUMTEXT   NULL,   -- macrocycle, markdown
    season_phases       JSON         NULL,   -- {"phases":[{name,start_week,end_week,weekly_hours_target,...}]}
    weekly_plan         JSON         NULL,   -- WeeklyPlanPayload: {zones_table?, weeks:[{week_number, week_rationale?, days:[...]}]}
    summary             JSON         NULL,   -- {total_cost_usd, total_tokens, cost_breakdown, plan_validation, adherence, prescription, ...}

    season_generated_at DATETIME     NULL,
    week_generated_at    DATETIME     NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_athlete_plans_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
