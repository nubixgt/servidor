-- SANAFER 2026 - Migración: usuarios con roles + quién registró cada mesa + monto.
-- Ejecutar UNA VEZ, DESPUÉS de reservations.sql (que ya ejecutaste).
-- Instalación nueva: ejecutar reservations.sql y luego este archivo.

-- 1) Usuarios del sistema
--    Roles: admin (todo + gestión de usuarios) y vendedor (reserva y ve/cancela solo lo suyo).
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(30)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(100) NOT NULL,
  `role`          ENUM('admin','vendedor') NOT NULL DEFAULT 'vendedor',
  `active`        TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL COMMENT 'UTC',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Reservas: quién la registró, categoría y precio de la mesa al momento de reservar.
--    (Las filas anteriores a esta migración quedan con user_id/category/price en NULL.)
ALTER TABLE `reservations`
  ADD COLUMN `user_id`  INT UNSIGNED  NULL AFTER `name`,
  ADD COLUMN `category` VARCHAR(20)   NULL AFTER `user_id`,
  ADD COLUMN `price`    DECIMAL(10,2) NULL COMMENT 'Quetzales' AFTER `category`,
  ADD KEY `idx_reservations_user` (`user_id`),
  ADD CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

-- 3) Primer administrador.
--    Usuario: admin   |   Contraseña temporal: Sanafer2026#
--    CÁMBIALA apenas entres (menú Usuarios > Cambiar contraseña).
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `role`, `active`, `created_at`)
VALUES ('admin', '$2y$10$ZmDSLCThjgP96eM4BiRxPORj80N6vNyird/cXRZLGf1DbiY3hkkuy', 'Administrador', 'admin', 1, UTC_TIMESTAMP());
