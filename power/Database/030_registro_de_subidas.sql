-- 030: copia en MySQL del registro de subidas a Garmin (Beta 3.0, F5).
--
-- Run ONCE, after 029. Aditiva y repetible.
--
-- ## Por qué
--
-- `garmin_uploads.json` (en `AiService/_runtime/storage/u<id>/`) es lo ÚNICO que dice qué
-- entrenos del reloj puso esta app, y por tanto los únicos que se pueden retirar sin
-- tocar nada del atleta. Vive en el disco del AiService, fuera de los respaldos de la
-- base: si ese disco se pierde, los entrenos se quedan en los relojes para siempre.
--
-- Esta tabla es su copia. En esta fase se escribe DOBLE: el AiService sigue mandando con
-- su fichero, y PHP copia el registro aquí cada vez que termina una subida o se borra un
-- plan (`GarminUploadRepository::reemplazar`). `Backend/scripts/comparar_registro_de_subidas.php`
-- dice si las dos copias coinciden. Cuando hayan coincidido en la beta, la fuente pasa a
-- ser ésta (PHP lo manda en cada llamada y el espejo escribe el fichero).
--
-- Tolerancia: `GarminUploadRepository::disponible()`; sin la tabla no se copia nada.

CREATE TABLE IF NOT EXISTS athlete_garmin_uploads (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    session_date    DATE NULL COMMENT 'La sesión del plan a la que pertenece (la clave es fecha|franja).',
    session_slot    VARCHAR(12) NOT NULL DEFAULT 'unica',
    scheduled_date  DATE NULL COMMENT 'Dónde está de verdad en el reloj; distinta si se movió y no se ha vuelto a subir.',
    workout_id      BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(191) NULL,
    sport           VARCHAR(32) NULL,
    fingerprint     VARCHAR(32) NULL,
    kind            VARCHAR(16) NULL COMMENT 'test = el test de umbral; NULL = una sesión del plan.',
    adopted         TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_at     DATETIME NULL,
    copied_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_garmin_uploads_workout (user_id, workout_id),
    CONSTRAINT fk_garmin_uploads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
