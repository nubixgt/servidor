-- Migración: Tabla de Ampliaciones de Presupuesto
-- Ejecutar en la base de datos del servidor

CREATE TABLE `budget_extensions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `tipo_ampliacion` varchar(50) NOT NULL,
  `documentos` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
