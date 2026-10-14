-- =====================================================================
-- Migración 002: ubicación (departamento/municipio/centro poblado), RUA,
-- causa "otra" y cultivos asociados.
-- Ejecutar UNA sola vez sobre la base de datos que ya tiene danos_cultivos.
-- =====================================================================

ALTER TABLE `danos_cultivos`
  ADD COLUMN `departamento` varchar(60) DEFAULT NULL AFTER `responsable`,
  ADD COLUMN `municipio` varchar(80) DEFAULT NULL AFTER `departamento`,
  ADD COLUMN `centro_poblado` varchar(150) DEFAULT NULL AFTER `municipio`,
  ADD COLUMN `agricultor_rua` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = agricultor registrado en RUA' AFTER `centro_poblado`,
  ADD COLUMN `codigo_rua` varchar(60) DEFAULT NULL AFTER `agricultor_rua`,
  ADD COLUMN `causa_otra` varchar(200) DEFAULT NULL COMMENT 'Detalle cuando causa = Otra' AFTER `causa`;

CREATE TABLE IF NOT EXISTS `danos_cultivos_asociados` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dano_id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `cultivo` varchar(100) NOT NULL,
  `etapa` varchar(60) DEFAULT NULL,
  `area_lote` decimal(10,2) DEFAULT NULL,
  `area_afectada` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_asociados_dano` (`dano_id`),
  CONSTRAINT `fk_asociados_dano` FOREIGN KEY (`dano_id`) REFERENCES `danos_cultivos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
