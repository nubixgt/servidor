-- Días que el atleta NO puede entrenar esta semana.
--
-- Aditiva y repetible: no borra nada. NO depende de la 021 a propósito: la
-- primera versión ponía la columna `AFTER week_note` y en la base local, que no
-- tenía la 021, falló entera (2026-09-15).
--
-- ## Por qué hacía falta
--
-- Lo pidió el atleta Fernando el 2026-09-14: "te da la opción de semana de
-- viaje pero no la de decir qué días no estás disponible". La rejilla del
-- perfil es PERMANENTE; un lunes que esta semana no se puede no tenía dónde
-- decirse salvo en texto libre, que el validador no lee -- y el validador
-- podía incluso exigir un FIJO justo ese día.
--
-- Una lista JSON de fechas ISO ("2026-09-15"), por check-in. Vale para UNA
-- semana: por eso va aquí y no en el perfil.
--
-- La tolerancia a que esta migración todavía no esté es la de siempre:
-- `CheckinRepository::COLS_OPCIONALES`.

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_checkins'
      AND COLUMN_NAME  = 'unavailable_dates'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE athlete_checkins
        ADD COLUMN unavailable_dates TEXT NULL DEFAULT NULL
            COMMENT "JSON: fechas de esta semana en las que el atleta no puede entrenar. NULL = ninguna."',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- El ritmo de SU rodaje suave, cuando el atleta lo sabe.
--
-- Fernando: "mi zona 2 es realmente a 6-6:20". Su hoja decía "Fácil
-- 5:03-5:40/km", anclado a su predicción de maratón, con unas zonas de pulso
-- idénticas en los tres deportes (las de fábrica de Garmin). No había dónde
-- decir el ritmo al que de verdad rueda suave. Dos columnas, segundos por km:
-- el extremo rápido y el lento. Lo declarado gana a lo derivado.

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_profiles'
      AND COLUMN_NAME  = 'easy_run_pace_fast_sec_per_km'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE athlete_profiles
        ADD COLUMN easy_run_pace_fast_sec_per_km DECIMAL(6,1) NULL DEFAULT NULL
            COMMENT "Rodaje suave declarado: extremo rapido, s/km",
        ADD COLUMN easy_run_pace_slow_sec_per_km DECIMAL(6,1) NULL DEFAULT NULL
            COMMENT "Rodaje suave declarado: extremo lento, s/km"',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
