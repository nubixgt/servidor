-- Lo que el atleta quiere que se tenga en cuenta ESTA semana.
--
-- Run ONCE, after 020. Aditiva y repetible: no borra nada.
--
-- ## Por qué hacía falta
--
-- El check-in preguntaba por la semana que se CIERRA ("¿qué se atravesó la
-- semana pasada?") y por la que viene sólo con cuatro etiquetas (normal, viaje,
-- carga laboral, poco sueño). No había dónde decir "el sábado salgo en bici con
-- amigos", "el jueves corro una carrera familiar" o "el martes subo a un
-- volcán": nada de eso es una competición del macrociclo, y el entrenador
-- planificaba la semana sin saberlo -- con una tirada larga el día después de
-- una salida de tres horas que el atleta iba a hacer igual.
--
-- ## Por qué en el check-in y no en el cuaderno
--
-- Porque vale para UNA semana. El cuaderno es para hechos duraderos, y una
-- salida del sábado anotada ahí seguiría dirigiendo planes meses después.
--
-- La tolerancia a que esta migración todavía no esté es la de siempre:
-- `CheckinRepository::COLS_OPCIONALES`.

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_checkins'
      AND COLUMN_NAME  = 'week_note'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE athlete_checkins
        ADD COLUMN week_note TEXT NULL DEFAULT NULL
            COMMENT "Lo que el atleta quiere que se tenga en cuenta esta semana. NULL = no escribio nada."
            AFTER last_week_note',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
