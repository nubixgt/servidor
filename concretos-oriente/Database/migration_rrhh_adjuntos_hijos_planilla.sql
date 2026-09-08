-- Migración: Módulo RRHH (Adjuntos, Edades de Hijos, Incidencias y Pagos de Planilla)
-- Ejecutar en MySQL si se desea aplicar manualmente (también se auto-migra en backend)

-- 1. Adjuntos y edades de hijos en personnel
ALTER TABLE `personnel`
  ADD COLUMN `dpi_adjunto_path` VARCHAR(255) DEFAULT NULL AFTER `foto_path`,
  ADD COLUMN `contrato_adjunto_path` VARCHAR(255) DEFAULT NULL AFTER `dpi_adjunto_path`,
  ADD COLUMN `licencia_adjunto_path` VARCHAR(255) DEFAULT NULL AFTER `contrato_adjunto_path`,
  ADD COLUMN `edades_hijos` VARCHAR(255) DEFAULT NULL AFTER `cantidad_hijos`;

-- 2. Adjuntos en incidencias
ALTER TABLE `employee_incidents`
  ADD COLUMN `adjunto_path` VARCHAR(255) DEFAULT NULL AFTER `motivo`;

-- 3. Tabla de pagos de planilla individual
CREATE TABLE IF NOT EXISTS `employee_payroll_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `personnel_id` int(10) UNSIGNED NOT NULL,
  `periodo` varchar(100) NOT NULL,
  `fecha_pago` date NOT NULL,
  `salario_base` decimal(15,2) NOT NULL DEFAULT 0.00,
  `dias_trabajados` int(11) NOT NULL DEFAULT 30,
  `sueldo_calculado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tiene_horas_extras` tinyint(1) NOT NULL DEFAULT 0,
  `horas_extras` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarifa_hora_extra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_horas_extras` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tiene_viaticos` tinyint(1) NOT NULL DEFAULT 0,
  `monto_viaticos` decimal(15,2) NOT NULL DEFAULT 0.00,
  `observaciones_viaticos` text DEFAULT NULL,
  `total_pagar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` varchar(50) DEFAULT 'Transferencia',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pay_personnel` (`personnel_id`),
  CONSTRAINT `fk_pay_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
