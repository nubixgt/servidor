-- Desglose de ejecución presupuestaria por Unidad Ejecutora (hoja "UniEjeYGru_Gas" del Excel EP).
-- Cada fila es una unidad ejecutora cruzada con un grupo de gasto o una fuente de financiamiento.
-- Ejecutar una sola vez en el servidor (Hostinger) antes de importar la hoja.

CREATE TABLE IF NOT EXISTS `presupuesto_detalle_ue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ejercicio_fiscal` int(11) NOT NULL,
  `unidad_codigo` varchar(20) NOT NULL,
  `unidad_nombre` varchar(255) DEFAULT NULL,
  `tipo` enum('GRUPO_GASTO','FUENTE_FINANCIAMIENTO') NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `vigente` decimal(18,2) DEFAULT 0.00,
  `devengado` decimal(18,2) DEFAULT 0.00,
  `saldo` decimal(18,2) DEFAULT 0.00,
  `pct_ejec` decimal(8,4) DEFAULT 0.0000,
  `pct_rel` decimal(8,4) DEFAULT 0.0000,
  `fecha_corte` date NOT NULL,
  `creado_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_detalle_ue_ejercicio` (`ejercicio_fiscal`, `unidad_codigo`, `tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
