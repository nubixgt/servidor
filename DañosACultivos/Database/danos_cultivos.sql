-- =====================================================================
-- Módulo "Daños a cultivos".
-- Ejecutar UNA sola vez sobre la base de datos del proyecto
-- (la definida en Backend/config/database.php), por ejemplo en phpMyAdmin > pestaña SQL.
--
-- Crea 3 tablas:
--   * danos_cultivos          -> un registro de daño por levantamiento de campo.
--   * danos_cultivos_puntos   -> puntos de muestreo (severidad) de cada registro.
--   * danos_cultivos_fotos    -> fotografías del registro.
--       En disco: Backend/uploads/danos-cultivos/{id}/fotos/{archivo}
--
-- No depende de ninguna otra tabla: `usuario_id` guarda el id del JWT sin llave foránea.
-- Si el proyecto define una tabla de usuarios, se puede añadir la FK después.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `danos_cultivos` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Id del usuario (claim del JWT) que registró el levantamiento',
  `registrado_por` varchar(150) DEFAULT NULL COMMENT 'Nombre del usuario (claim del JWT)',
  `fecha` date NOT NULL COMMENT 'Fecha de levantamiento',
  `responsable` varchar(150) NOT NULL COMMENT 'Nombre del técnico / productor (texto libre)',
  `lote` varchar(200) NOT NULL COMMENT 'Lote / parcela (texto libre)',
  `cultivo` varchar(100) NOT NULL,
  `etapa` varchar(60) DEFAULT NULL COMMENT 'Estado fenológico',
  `causa` varchar(120) NOT NULL COMMENT 'Causa del daño',
  `area_lote` decimal(10,2) DEFAULT NULL COMMENT 'Superficie total del lote (ha)',
  `area_afectada` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Superficie afectada (ha)',
  `dano_porc` decimal(5,2) NOT NULL COMMENT '% de daño promedio del lote (0-100)',
  `rend_esperado` decimal(10,2) DEFAULT NULL COMMENT 'Rendimiento esperado sin daño',
  `unidad` enum('t/ha','kg/ha') NOT NULL DEFAULT 't/ha',
  `perdida_estimada` decimal(12,2) DEFAULT NULL,
  `gps_lat` decimal(10,6) DEFAULT NULL,
  `gps_lon` decimal(10,6) DEFAULT NULL,
  `gps_precision` decimal(10,2) DEFAULT NULL COMMENT 'Precisión GPS en metros',
  `notas` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_danos_usuario` (`usuario_id`),
  KEY `idx_danos_fecha` (`fecha`),
  KEY `idx_danos_cultivo` (`cultivo`),
  KEY `idx_danos_causa` (`causa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `danos_cultivos_puntos` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dano_id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `referencia` varchar(200) DEFAULT NULL COMMENT 'Ubicación / referencia del punto',
  `incidencia` decimal(5,2) DEFAULT NULL COMMENT '% de plantas afectadas (0-100)',
  `severidad` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '0 sin daño, 1 leve, 2 moderado, 3 grave',
  `observacion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_puntos_dano` (`dano_id`),
  CONSTRAINT `fk_puntos_dano` FOREIGN KEY (`dano_id`) REFERENCES `danos_cultivos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `danos_cultivos_fotos` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dano_id` int(10) UNSIGNED NOT NULL,
  `archivo` varchar(255) NOT NULL COMMENT 'Nombre del archivo dentro de danos-cultivos/{id}/fotos/',
  `nombre_original` varchar(255) DEFAULT NULL,
  `subido_por` varchar(150) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dfotos_dano` (`dano_id`),
  CONSTRAINT `fk_dfotos_dano` FOREIGN KEY (`dano_id`) REFERENCES `danos_cultivos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Verifica el resultado:
--   SHOW TABLES LIKE 'danos_cultivos%';   -- deben aparecer 3 tablas
-- =====================================================================
