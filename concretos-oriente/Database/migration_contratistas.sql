-- Migración: módulo de Contratistas
-- Ejecutar una sola vez en la base de datos real (phpMyAdmin > SQL, o consola mysql).
-- Ya está reflejado en Database/visionwe_ConcretosOriente.sql como referencia del esquema.

CREATE TABLE `contractors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo_electronico` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_contractors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `monto_contratado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_asignacion` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_contractor` (`project_id`,`contractor_id`),
  KEY `fk_pc_contractor` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `project_contractors`
  ADD CONSTRAINT `fk_pc_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pc_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE CASCADE;

ALTER TABLE `expenses`
  ADD COLUMN `contratista_id` int(11) DEFAULT NULL AFTER `proyecto_id`,
  ADD KEY `fk_expense_contractor` (`contratista_id`);

ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_contractor` FOREIGN KEY (`contratista_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL;
