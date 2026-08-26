DROP TABLE IF EXISTS `visan_entregas`;

CREATE TABLE IF NOT EXISTS `datos_asistencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `departamento` varchar(100) NOT NULL,
  `municipio` varchar(100) NOT NULL,
  `nda_severa_r` int(11) DEFAULT 0,
  `nda_severa_f` int(11) DEFAULT 0,
  `nda_nacional_r` int(11) DEFAULT 0,
  `nda_nacional_f` int(11) DEFAULT 0,
  `nda_plan_abordaje_r` int(11) DEFAULT 0,
  `nda_plan_abordaje_f` int(11) DEFAULT 0,
  `insan_r` int(11) DEFAULT 0,
  `insan_f` int(11) DEFAULT 0,
  `insan_emergencia_r` int(11) DEFAULT 0,
  `insan_emergencia_f` int(11) DEFAULT 0,
  `medida_transitoria_r` int(11) DEFAULT 0,
  `medida_transitoria_f` int(11) DEFAULT 0,
  `medida_cautelar_r` int(11) DEFAULT 0,
  `medida_cautelar_f` int(11) DEFAULT 0,
  `total_aa_r` int(11) DEFAULT 0,
  `total_aa_f` int(11) DEFAULT 0,
  `apa_f` int(11) DEFAULT 0,
  `apa_huertos` int(11) DEFAULT 0,
  `reserva_estrategica_r` int(11) DEFAULT 0,
  `reserva_estrategica_f` int(11) DEFAULT 0,
  `total_aa_apa` int(11) DEFAULT 0,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_departamento` (`departamento`),
  KEY `idx_municipio` (`municipio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `historial_cambios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registro_id` int(11) NOT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `campo_modificado` varchar(100) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_nuevo` text DEFAULT NULL,
  `observacion_cambio` text DEFAULT NULL,
  `usuario` varchar(100) DEFAULT 'Sistema',
  `fecha_cambio` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
