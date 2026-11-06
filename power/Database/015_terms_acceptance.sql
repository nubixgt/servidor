-- Quién aceptó las condiciones, cuándo, y CUÁL versión.
--
-- Run ONCE, after 014.
--
-- ## Por qué hacía falta
--
-- No existía nada. Ni términos, ni política de privacidad, ni registro de
-- aceptación — ni una tabla, ni una columna, ni una ruta. Lo único parecido era
-- un descargo médico enterrado en la última pestaña del perfil, **opcional**, y
-- que ninguna ruta comprobaba.
--
-- Mientras tanto la app guarda de personas reales: lesiones con escala de
-- dolor, medicación, condiciones crónicas, ciclo menstrual, un cuestionario de
-- aptitud física de siete preguntas, y la contraseña de Garmin (cifrada). Eso
-- no es deuda de producto, es exposición.
--
-- ## Por qué en `users` y no en `athlete_profiles`
--
-- Aceptar las condiciones es un acto de la CUENTA, no del perfil deportivo. Un
-- atleta puede borrar sus datos de entrenamiento — la app se lo permite a
-- propósito — y eso no puede borrar la constancia de que aceptó. Además la fila
-- de perfil puede no existir todavía: se acepta al entrar, antes de rellenar
-- nada.
--
-- ## Por qué se guarda la VERSIÓN y no sólo la fecha
--
-- "Aceptó el 9 de septiembre" no dice qué aceptó. Cuando el texto cambie —y va
-- a cambiar, porque esta primera versión la escribió quien programa y no un
-- abogado— hay que poder saber quién vio cuál, y volver a pedirlo a quien
-- aceptó una anterior. Sin la versión, la única salida sería volver a
-- preguntárselo a todo el mundo cada vez que se corrija una coma.
--
-- La comprobación va contra `LEGAL_VERSION` en Backend/src/Support/Legal.php:
-- el texto y su versión viven juntos, en un sitio, y subir la versión es lo que
-- vuelve a pedir la aceptación.
--
-- NULL = no ha aceptado todavía. Los nueve de la beta empiezan así y lo aceptan
-- la próxima vez que entren.

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'terms_accepted_at'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE users
        ADD COLUMN terms_accepted_at DATETIME NULL DEFAULT NULL
            COMMENT "Cuando acepto las condiciones. NULL = todavia no."
            AFTER last_login_at,
        ADD COLUMN terms_version VARCHAR(20) NULL DEFAULT NULL
            COMMENT "Que version acepto. Ver Backend/src/Support/Legal.php."
            AFTER terms_accepted_at',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
