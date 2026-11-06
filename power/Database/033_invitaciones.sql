-- 033: invitaciones (Beta 3.0; decisión del responsable, 2026-10-11).
--
-- Run ONCE, after 032. Aditiva y repetible.
--
-- ## Por qué
--
-- El objetivo del responsable es que el producto crezca por invitaciones, con semanas o
-- meses gratis para quien invita. Esto planta las bases: cada atleta tiene un CÓDIGO
-- (`users.referral_code`), quien entra con el enlace de otro queda apuntado como su
-- invitado (`athlete_referrals`) y, cuando el invitado genera su PRIMERA semana, la
-- invitación cuenta (`activated_at`) y las dos partes ganan sus semanas gratis.
--
-- Las semanas ganadas se guardan en la fila al activarse (`reward_weeks`,
-- `reward_weeks_invitee`): cambiar después cuántas da una invitación
-- (`ReferralService::SEMANAS_POR_INVITADO`) no reescribe lo ya ganado. Hoy no hay cobro al
-- que aplicarlas: son un saldo que se enseña (Legal, punto 8).
--
-- Tolerancia: `ReferralRepository::disponible()`; sin la migración no hay código ni
-- invitaciones, y entrar con un enlace con `?ref=` funciona igual que sin él.

SET @hay := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'referral_code');
SET @sql := IF(@hay = 0,
    'ALTER TABLE users ADD COLUMN referral_code VARCHAR(16) NULL COMMENT ''Su código para invitar (Database/033).'', ADD UNIQUE KEY uq_users_referral_code (referral_code)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS athlete_referrals (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    referrer_id           INT UNSIGNED NOT NULL COMMENT 'Quien invitó.',
    referred_id           INT UNSIGNED NOT NULL COMMENT 'Quien entró con su enlace. Uno solo por cuenta.',
    code                  VARCHAR(16) NOT NULL COMMENT 'El código con el que entró, tal cual.',
    activated_at          DATETIME NULL COMMENT 'Cuando el invitado generó su primera semana: ahí cuenta.',
    reward_weeks          SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Semanas gratis que ganó quien invitó.',
    reward_weeks_invitee  SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Semanas gratis que ganó el invitado.',
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_referrals_referred (referred_id),
    KEY idx_referrals_referrer (referrer_id),
    CONSTRAINT fk_referrals_referrer FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_referrals_referred FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
