-- 035: semanas gratis ligadas a una cuenta Garmin NUEVA (decisión del responsable, 2026-10-20).
--
-- Run ONCE, after 034. Aditiva y repetible.
--
-- ## Por qué
--
-- «2 semanas gratis, pero hay que garantizar que no haya free riders: 2 semanas por cada cuenta
-- nueva de Garmin vinculada (éstas son limitadas, no como las de Google), 2 semanas para el
-- referido y 2 extra para el que refiere, acumulables por cada referido nuevo pero que no se
-- repita con otro.» Una cuenta de Google se crea en un minuto; una de Garmin lleva un reloj
-- detrás. Así que lo que se regala va atado a la cuenta Garmin, no a la de la app:
--
-- - `garmin_accounts_seen`: cada cuenta Garmin que alguna vez se conectó, como una HUELLA
--   (sha256 de su `profileId`, o de su correo si Garmin no lo da). Sin clave foránea a `users`
--   a propósito: borrar la cuenta de la app no borra la huella, o borrar y volver a entrar
--   regalaría otra vez las semanas.
-- - `athlete_free_weeks`: el saldo, una fila por concesión con su motivo (`prueba` al conectar
--   un Garmin nuevo; `invitado` y `invitador` cuando el invitado conecta un Garmin nuevo). Un
--   saldo: hoy no hay cobro al que aplicarlo (Legal, punto 8).
-- - `athlete_referrals.garmin_repeated`: la invitación cuyo invitado conectó un Garmin que ya
--   estaba en la app. No cuenta, y se dice por qué.
--
-- Tolerancia: `FreeWeeksRepository::disponible()`; sin la migración, conectar Garmin funciona
-- como siempre y no se regala nada.

CREATE TABLE IF NOT EXISTS garmin_accounts_seen (
    account_hash   CHAR(64) NOT NULL PRIMARY KEY COMMENT 'sha256 de la cuenta Garmin (profileId o correo).',
    first_user_id  INT UNSIGNED NULL COMMENT 'Quién la conectó primero. Sin FK: sobrevive al borrado.',
    first_seen_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS athlete_free_weeks (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    weeks        SMALLINT UNSIGNED NOT NULL COMMENT 'Las que valían al concederlas.',
    reason       ENUM('prueba','invitado','invitador') NOT NULL,
    referral_id  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 en la de prueba; la invitación en las otras.',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_free_weeks_once (user_id, reason, referral_id),
    CONSTRAINT fk_free_weeks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'athlete_referrals' AND COLUMN_NAME = 'garmin_repeated');
SET @sql := IF(@hay = 0,
    'ALTER TABLE athlete_referrals ADD COLUMN garmin_repeated TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''El invitado conectó un Garmin que ya estaba en la app: no cuenta (Database/035).''',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
