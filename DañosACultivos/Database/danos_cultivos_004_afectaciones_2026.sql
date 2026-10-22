-- =====================================================================
-- Migración 004: campos de "Afectaciones 2026" (período de monitoreo, familias
-- afectadas por tipo de agricultor, fecha de siembra, evento reportado) y los
-- nuevos módulos Pecuario e Infraestructura.
-- Ejecutar UNA sola vez sobre la base de datos que ya tiene danos_cultivos.
-- Todo lo nuevo es opcional (NULL): los registros existentes no se alteran.
-- =====================================================================

ALTER TABLE `danos_cultivos`
  ADD COLUMN `fecha_informe` date DEFAULT NULL COMMENT 'Fecha del informe' AFTER `fecha`,
  ADD COLUMN `monitoreo_inicio` date DEFAULT NULL AFTER `fecha_informe`,
  ADD COLUMN `monitoreo_fin` date DEFAULT NULL AFTER `monitoreo_inicio`,
  ADD COLUMN `semana` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Semana ISO del año (de la fecha de informe)' AFTER `monitoreo_fin`,
  ADD COLUMN `anio_semana` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Año ISO de la semana' AFTER `semana`,
  ADD COLUMN `fecha_siembra` date DEFAULT NULL AFTER `etapa`,
  ADD COLUMN `evento_reportado` varchar(150) DEFAULT NULL COMMENT 'Evento tal como lo reportó el técnico' AFTER `causa_otra`,
  ADD COLUMN `fam_afectadas` int(10) UNSIGNED DEFAULT NULL AFTER `perdida_estimada`,
  ADD COLUMN `fam_autoconsumo` int(10) UNSIGNED DEFAULT NULL COMMENT 'Familias de autoconsumo' AFTER `fam_afectadas`,
  ADD COLUMN `fam_intermedia` int(10) UNSIGNED DEFAULT NULL COMMENT 'Familias de agricultura intermedia' AFTER `fam_autoconsumo`,
  ADD COLUMN `fam_consolidada` int(10) UNSIGNED DEFAULT NULL COMMENT 'Familias de agricultura consolidada' AFTER `fam_intermedia`,
  ADD KEY `idx_danos_semana` (`anio_semana`, `semana`);

CREATE TABLE IF NOT EXISTS `danos_pecuarios` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Id del usuario (claim del JWT)',
  `registrado_por` varchar(150) DEFAULT NULL,
  `fecha_informe` date NOT NULL,
  `monitoreo_inicio` date DEFAULT NULL,
  `monitoreo_fin` date DEFAULT NULL,
  `semana` tinyint(3) UNSIGNED DEFAULT NULL,
  `anio_semana` smallint(5) UNSIGNED DEFAULT NULL,
  `departamento` varchar(60) NOT NULL,
  `municipio` varchar(80) NOT NULL,
  `centro_poblado` varchar(150) NOT NULL,
  `especie` varchar(100) NOT NULL,
  `especie_otra` varchar(150) DEFAULT NULL,
  `evento_reportado` varchar(150) DEFAULT NULL,
  `causa` varchar(120) NOT NULL,
  `causa_otra` varchar(200) DEFAULT NULL,
  `fam_afectadas` int(10) UNSIGNED DEFAULT NULL,
  `animales_perdidos` int(10) UNSIGNED DEFAULT NULL,
  `perdida_economica` decimal(14,2) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pecuarios_usuario` (`usuario_id`),
  KEY `idx_pecuarios_fecha` (`fecha_informe`),
  KEY `idx_pecuarios_semana` (`anio_semana`, `semana`),
  KEY `idx_pecuarios_especie` (`especie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `danos_infraestructura` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Id del usuario (claim del JWT)',
  `registrado_por` varchar(150) DEFAULT NULL,
  `fecha_informe` date NOT NULL,
  `monitoreo_inicio` date DEFAULT NULL,
  `monitoreo_fin` date DEFAULT NULL,
  `semana` tinyint(3) UNSIGNED DEFAULT NULL,
  `anio_semana` smallint(5) UNSIGNED DEFAULT NULL,
  `tipo_infraestructura` varchar(120) NOT NULL,
  `tipo_otra` varchar(150) DEFAULT NULL,
  `departamento` varchar(60) NOT NULL,
  `municipio` varchar(80) NOT NULL,
  `centro_poblado` varchar(150) NOT NULL,
  `gps_lat` decimal(10,6) DEFAULT NULL,
  `gps_lon` decimal(10,6) DEFAULT NULL,
  `gps_precision` decimal(10,2) DEFAULT NULL,
  `evento_reportado` varchar(150) DEFAULT NULL,
  `causa` varchar(120) NOT NULL,
  `causa_otra` varchar(200) DEFAULT NULL,
  `fam_afectadas` int(10) UNSIGNED DEFAULT NULL,
  `perdida_economica` decimal(14,2) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_infra_usuario` (`usuario_id`),
  KEY `idx_infra_fecha` (`fecha_informe`),
  KEY `idx_infra_semana` (`anio_semana`, `semana`),
  KEY `idx_infra_tipo` (`tipo_infraestructura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Verifica el resultado:
--   SHOW TABLES LIKE 'danos_%';   -- deben aparecer danos_pecuarios y danos_infraestructura
--   SHOW COLUMNS FROM danos_cultivos LIKE 'fam_%';
-- =====================================================================
