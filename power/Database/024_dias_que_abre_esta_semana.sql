-- Días que su tabla no le da y que ESTA semana sí puede entrenar.
--
-- Aditiva y repetible: no borra nada. No depende de ninguna otra.
--
-- ## Por qué hacía falta
--
-- Jessie, 2026-09-15: no entrena los sábados, y en el check-in escribió "mi
-- activación para una carrera la suelo hacer el día sábado antes de la
-- carrera, ligera". La 022 dejaba decir qué días NO podía; ninguna, qué día SÍ.
-- La semana le puso "descanso completo como prefieres" ese sábado y pasó la
-- activación a una fuerza el jueves.
--
-- Un objeto JSON por check-in: {"2026-09-19": ["Correr"]}, con los deportes de
-- la rejilla del perfil. Vale para UNA semana: por eso va aquí y no en el perfil.
--
-- La tolerancia a que esta migración todavía no esté es la de siempre:
-- `CheckinRepository::COLS_OPCIONALES`.

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_checkins'
      AND COLUMN_NAME  = 'open_dates'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE athlete_checkins
        ADD COLUMN open_dates TEXT NULL DEFAULT NULL
            COMMENT "JSON {fecha: [deportes]}: dias que su tabla no le da y esta semana si puede. NULL = ninguno."',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
