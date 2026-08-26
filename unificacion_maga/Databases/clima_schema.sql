-- Schema para el módulo Climatológico (RegistrosClimatologicos)
-- Ajustado para la arquitectura unificacion_maga con prefijo clima_

SET FOREIGN_KEY_CHECKS=0;

-- 1. Alertas Climatológicas
DROP TABLE IF EXISTS `clima_alertas`;
CREATE TABLE `clima_alertas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion_corta` TEXT NOT NULL,
  `descripcion_detallada` TEXT,
  `tipo_alerta` VARCHAR(100) NOT NULL,
  `nivel_severidad` ENUM('ALTA', 'MEDIA', 'BAJA') NOT NULL DEFAULT 'MEDIA',
  `region` VARCHAR(255) NOT NULL,
  `icono` VARCHAR(50),
  `fecha_emision` DATETIME NOT NULL,
  `fecha_vigencia` DATETIME NOT NULL,
  `estado` ENUM('Activa', 'Inactiva', 'Vencida') NOT NULL DEFAULT 'Activa',
  `id_usuario_creador` INT NOT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  -- Asumiendo que `usuarios` es la tabla global de la unificación.
  -- Ajustar según corresponda (ej. si la tabla global se llama `usuarios_unificados`)
  KEY `id_usuario_creador` (`id_usuario_creador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Registros Climáticos
DROP TABLE IF EXISTS `clima_registros`;
CREATE TABLE `clima_registros` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `fecha_registro` DATETIME NOT NULL,
  `latitud` DECIMAL(10,8) NOT NULL,
  `longitud` DECIMAL(11,8) NOT NULL,
  `direccion` VARCHAR(255),
  `temperatura` DECIMAL(5,2),
  `humedad` DECIMAL(5,2),
  `precipitacion` DECIMAL(5,2),
  `viento` DECIMAL(5,2),
  `categoria` ENUM('condicion', 'desastre') NOT NULL DEFAULT 'condicion',
  `condicion_climatica` ENUM('normal', 'sequia', 'exceso_lluvia', 'helada', 'tormenta') DEFAULT NULL,
  `desastre_natural` ENUM('inundacion', 'deslizamiento', 'sismo', 'erupcion_volcanica', 'huracan', 'incendio_forestal', 'granizo') DEFAULT NULL,
  `observaciones` TEXT,
  `sincronizado` TINYINT(1) DEFAULT '0',
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. Fotografías Adjuntas a Registros Climáticos
DROP TABLE IF EXISTS `clima_fotos`;
CREATE TABLE `clima_fotos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_registro` INT NOT NULL,
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(500) NOT NULL,
  `orden` TINYINT DEFAULT '0',
  `fecha_subida` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_registro` (`id_registro`),
  CONSTRAINT `clima_fotos_fk_1` FOREIGN KEY (`id_registro`) REFERENCES `clima_registros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
