-- 023: el test de umbral del atleta.
--
-- Run ONCE, after 022. Repetible: comprueba la columna antes de crearla.
--
-- ## Por qué
--
-- El informe del atleta Hugo (2026-09-07) tenía un umbral de carrera derivado
-- de una predicción y unas zonas de pulso de fábrica (50/60/70/80/90 % de una
-- FC máxima), y el propio informe decía que lo resolvería un test de umbral.
-- La app no tenía dónde hacerlo ni dónde guardarlo.
--
-- `threshold_tests` guarda, por deporte, el test que el atleta hizo y decidió
-- usar: la fecha, la actividad y los números que salieron
-- ({"run": {"fecha", "activity_id", "lthr", "ritmo_s_km"},
--   "bike": {"fecha", "activity_id", "lthr", "ftp_w"},
--   "swim": {"fecha", "t400_s", "t200_s", "css_s_100m"}}).
-- Los valores que el atleta USA se copian además a sus columnas de siempre
-- (`lactate_threshold_hr`, `lactate_threshold_pace_sec_per_km`, `ftp_watts`,
-- `swim_threshold_pace_sec_per_100m`); esta columna es la procedencia, con
-- fecha, para que el cerebro diga "de su test del 20-sep" y no "a mano".
--
-- Aditiva: no toca ningún dato. `ProfileRepository` lee las columnas con SHOW
-- COLUMNS, así que el código aguanta que todavía no esté.

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_profiles'
      AND COLUMN_NAME  = 'threshold_tests'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE athlete_profiles ADD COLUMN threshold_tests JSON NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
