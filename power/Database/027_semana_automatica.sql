-- 027: «Generar mi semana automáticamente» (decisión del responsable, 2026-09-24).
--
-- Run ONCE, after 026. Aditiva y repetible: comprueba la tabla antes de crearla.
--
-- ## Por qué
--
-- Generar la semana a mano cada domingo es lo que más se olvida, y la semana que no
-- se genera es una semana sin plan en el reloj. Con el interruptor encendido, la
-- víspera del primer día de su semana, por la noche y en SU hora, un script
-- (`Backend/scripts/semanas_automaticas.php`, lanzado por cron cada hora) genera la
-- siguiente semana SIN preguntas del coach y SIN check-in, y la sube a su reloj con
-- lo que eso conlleva (reemplaza lo que ya estaba y retira lo que sobra).
--
-- Una fila por atleta. La zona horaria la manda su navegador al encender el
-- interruptor: el servidor no sabe qué hora es para él. `week_start` es la semana de
-- la que habla el resto de la fila; `stage` dice en qué va (generando, subiendo,
-- hecha, error) y `message` es lo que se le enseña, en su idioma.
--
-- La tolerancia a que esta migración todavía no esté es la de siempre:
-- `AutoWeekRepository::disponible()` comprueba la tabla y, si no está, el
-- interruptor no aparece y el script no hace nada.

CREATE TABLE IF NOT EXISTS athlete_auto_weeks (
    user_id     INT UNSIGNED NOT NULL PRIMARY KEY,
    enabled     TINYINT(1) NOT NULL DEFAULT 0,
    timezone    VARCHAR(64) NULL COMMENT 'Zona IANA de su navegador, p. ej. America/Lima.',
    week_start  DATE NULL COMMENT 'La semana de la que hablan stage, attempts y message.',
    stage       VARCHAR(16) NULL COMMENT 'generando | subiendo | hecha | error',
    job_id      VARCHAR(64) NULL COMMENT 'El trabajo de generacion lanzado para week_start.',
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    message     VARCHAR(500) NULL COMMENT 'Lo que se le dice al atleta de la ultima vez.',
    stage_at    DATETIME NULL COMMENT 'Cuando se entro en stage (UTC).',
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_auto_weeks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
