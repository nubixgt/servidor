-- SANAFER 2026 - Venta de entradas
-- Ejecutar UNA VEZ sobre la base de datos ya creada (MySQL / MariaDB).
-- table_id es clave primaria: garantiza que una mesa no se reserve dos veces.
-- Una reserva (booking_id) puede abarcar varias mesas (una fila por mesa).

CREATE TABLE IF NOT EXISTS `reservations` (
  `table_id`   SMALLINT UNSIGNED NOT NULL,
  `booking_id` CHAR(36)          NOT NULL,
  `name`       VARCHAR(100)      NOT NULL,
  `created_at` DATETIME          NOT NULL COMMENT 'UTC',
  PRIMARY KEY (`table_id`),
  KEY `idx_reservations_booking` (`booking_id`),
  CONSTRAINT `chk_reservations_table` CHECK (`table_id` BETWEEN 1 AND 90)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
