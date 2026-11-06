-- 025: cómo entrena la fuerza el atleta (Beta 3.0, paso S3).
--
-- Run ONCE, after 024. Aditiva y repetible: comprueba cada columna antes de
-- crearla y no toca ningún dato.
--
-- ## Por qué
--
-- Fernando (2026-09-17) hace una rutina Upper/Lower A/B con superseries de 2-3
-- ejercicios y 2' entre rondas, con objetivo de hipertrofia; Jessie dice que
-- los ejercicios le salen "muy básicos"; a Hugo le gusta como está. En las 8
-- semanas archivadas de José y Jessie ninguna ficha decía gimnasio, equipo,
-- objetivo ni rutina: el modelo elegía lo seguro porque no tenía otra cosa.
--
-- `strength_profile` (JSON): cómo quiere entrenarla.
--   {"mode": "rutina"|"coach", "goal": "hipertrofia"|"rendimiento"|"prevencion",
--    "equipment": "gimnasio"|"mancuernas_y_bandas"|"peso_corporal",
--    "structure": "full_body"|"upper_lower", "exercises_per_group": 1-3,
--    "session_minutes": N, "barbell_lifts": ["sentadilla", ...]}
--   NULL = no contestó, y el plan sigue exactamente como hasta hoy.
--
-- `strength_routine` (JSON): el programa, el mismo formato lo escriba el atleta
-- o el coach.
--   {"source": "atleta"|"coach", "original_text": "...", "confirmed_at": null,
--    "block_start": null, "sessions": []}
--   Sólo cuenta con `confirmed_at`: el texto pegado se ordena y el atleta lo
--   confirma antes de que ninguna semana lo use (paso S4).
--
-- La lectura y sus valores permitidos: PaginaLocal/services/ai/perfil_de_fuerza.py
-- (y su espejo Frontend/src/utils/fuerza.js). `ProfileRepository` lee las
-- columnas con SHOW COLUMNS, así que el código aguanta que todavía no esté.

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_profiles'
      AND COLUMN_NAME  = 'strength_profile'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE athlete_profiles ADD COLUMN strength_profile JSON NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_profiles'
      AND COLUMN_NAME  = 'strength_routine'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE athlete_profiles ADD COLUMN strength_routine JSON NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
