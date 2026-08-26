SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas dependientes que vinieron en el dump para quitar las llaves foráneas
DROP TABLE IF EXISTS `log_accesos`;
DROP TABLE IF EXISTS `sesiones_activas`;

-- Eliminar la tabla incorrecta que provino del dump antiguo
DROP TABLE IF EXISTS `usuarios`;

-- Recrear la estructura oficial de la tabla usuarios del sistema unificado
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rol` varchar(20) DEFAULT 'tecnico',
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar los usuarios por defecto
INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `email`, `rol`, `activo`) VALUES
(3, 'tecnico', '$2y$10$rU/36pXscJU1Hmcxzy.PPevioDJOQq8SeJCgaN2di2bpILNg1M912', 'Técnico VIDER', 'tecnico@maga.gob.gt', 'tecnico', 1),
(6, 'admin', '$2y$10$1RGr7yOh3XdrsFyw3eZHL.ytL360AR99ijwD3Q2ASiW3k9xOLiVSC', 'Administrador VIDER', NULL, 'admin', 1);

SET FOREIGN_KEY_CHECKS = 1;
