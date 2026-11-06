-- The AI pipeline renders two HTML reports per generation:
--   analysis.html   — the athlete's training analysis (from the season generation)
--   planning.html   — the weekly plan laid out as a report (from the week generation)
-- PaginaLocal serves these as-is. Store them so the Reports section can show them.
--
-- Run ONCE, after 007_create_athlete_plan_history.sql.

ALTER TABLE athlete_plans
    ADD COLUMN analysis_html MEDIUMTEXT NULL AFTER summary,
    ADD COLUMN planning_html MEDIUMTEXT NULL AFTER analysis_html;

ALTER TABLE athlete_plan_history
    ADD COLUMN analysis_html MEDIUMTEXT NULL AFTER summary,
    ADD COLUMN planning_html MEDIUMTEXT NULL AFTER analysis_html;
