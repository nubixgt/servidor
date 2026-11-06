-- Fuera la ficha clínica. Lo que quedaba del asistente de salud, también.
--
-- Run ONCE, after 016.
--
-- ## Qué se va, y por qué
--
-- Seis columnas, y las seis por la misma razón: eran canales por los que se le
-- preguntaba al atleta por lesiones, y **el exceso era la causa del sesgo que
-- la beta reportó**. Lo que los atletas sentían no venía de los prompts —
-- auditados uno a uno, el planificador de temporada no menciona lesión ni una
-- vez— sino de que se les preguntaba en SEIS sitios distintos, y todo eso les
-- volvía amplificado dentro del plan.
--
--   `par_q_flags`            el tamizaje de aptitud física, 7 preguntas
--                            clínicas seguidas. Era el bloque más pesado del
--                            alta y estaba en la ÚLTIMA pestaña: la última
--                            impresión de registrarse era un cuestionario
--                            médico.
--   `injury_prone`           la casilla "me considero propenso a lesionarme".
--   `doctor_performance_report`
--   `doctor_chat_history`    el asistente de salud. Su chat se retiró de la UI
--                            en la v1.1 con un veredicto unánime de los cuatro
--                            atletas beta ("es confuso, nadie lo usa
--                            intencionalmente, y es tedioso"); esto retira lo
--                            que quedaba del agente, que seguía escribiendo un
--                            informe de hasta 8.000 caracteres dentro del
--                            prompt del plan.
--   `last_medical_evaluation`
--   `medical_snapshot_hash`  sólo existían para el informe anterior.
--
-- ## El descargo médico lo sustituye la puerta legal
--
-- `health_disclaimer_accepted` y `health_disclaimer_accepted_at` eran una
-- casilla OPCIONAL, enterrada en la octava pestaña, que ninguna ruta
-- comprobaba. Desde la migración 015 hay una puerta legal de verdad:
-- `users.terms_accepted_at` + `terms_version`, que bloquea la app hasta que se
-- acepta, guarda QUÉ versión se aceptó, y dice con todas las letras que esto no
-- es un servicio médico. Dos mecanismos para lo mismo eran uno de más, y el que
-- se queda es el que funciona.
--
-- ## Lo que NO se va
--
-- `current_injuries`, `past_injuries_surgeries`, `chronic_conditions`,
-- `medications` y `limitations` se quedan enteras. Eso es historial declarado
-- que cambia la modalidad y la carga de verdad, y el atleta lo escribe cuando
-- le pasa algo — no un cuestionario que hay que rellenar para entrar.
--
-- ## Esto SÍ borra datos
--
-- A diferencia de las demás migraciones de este repo, ésta destruye
-- información: los informes del asistente de salud y las respuestas del
-- tamizaje de los atletas de la beta. Es deliberado y está pedido. Si algún día
-- hiciera falta conservarlos, el sitio sería un volcado previo, no dejar las
-- columnas muertas en la tabla que se lee en cada petición.

SET @sql := (
    SELECT IF(COUNT(*) = 0, 'SELECT 1',
        CONCAT('ALTER TABLE athlete_profiles ', GROUP_CONCAT(CONCAT('DROP COLUMN ', COLUMN_NAME))))
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'athlete_profiles'
      AND COLUMN_NAME IN (
          'par_q_flags',
          'injury_prone',
          'doctor_performance_report',
          'doctor_chat_history',
          'last_medical_evaluation',
          'medical_snapshot_hash',
          'health_disclaimer_accepted',
          'health_disclaimer_accepted_at'
      )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
