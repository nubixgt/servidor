-- 034: copia en MySQL del expediente de cada atleta (Beta 3.0, F10 / fase C).
--
-- Run ONCE, after 033. Aditiva y repetible.
--
-- ## Por qué
--
-- `track_record.jsonl` (en `AiService/_runtime/storage/u<id>/`) es una de las dos cosas del espejo
-- que NO se pueden reconstruir: una línea por semana con lo prescrito, lo hecho y lo que se cayó, y
-- de ahí salen la memoria larga del macrociclo, la racha, las medallas y las gráficas. Vive en el
-- disco del AiService, fuera de los respaldos de la base: si ese disco se pierde, se pierde.
--
-- Esta tabla es su copia, línea a línea y con el texto EXACTO (para poder reponer el fichero tal
-- cual), junto con `track_record.descartadas.jsonl` (las semanas borradas sin medirse, que también
-- son historial). Se escribe entera cuando termina una generación y cuando se borra un plan
-- (`PlanService::copiarExpediente`). El fichero sigue mandando: como con el registro de subidas
-- (030), la copia pasa a mandar cuando haya coincidido en la beta
-- (`Backend/scripts/comparar_expediente.php`).
--
-- Tolerancia: `TrackRecordRepository::disponible()`; sin la tabla no se copia nada.

CREATE TABLE IF NOT EXISTS athlete_track_record (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    fichero     ENUM('expediente', 'descartadas') NOT NULL DEFAULT 'expediente',
    orden       INT UNSIGNED NOT NULL COMMENT 'Su posición en el fichero (las líneas se añaden, nunca se reordenan).',
    week_from   DATE NULL,
    week_to     DATE NULL,
    linea       MEDIUMTEXT NOT NULL COMMENT 'La línea tal cual, para poder reponer el fichero idéntico.',
    copied_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_track_record_linea (user_id, fichero, orden),
    CONSTRAINT fk_track_record_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
