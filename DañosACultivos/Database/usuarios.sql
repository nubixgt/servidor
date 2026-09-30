-- =====================================================================
-- Módulo de autenticación: tabla de usuarios con roles.
-- Ejecutar UNA sola vez sobre la misma base de datos (phpMyAdmin > SQL).
--
-- Roles: admin, supervisor, tecnico.
-- `password` guarda el hash bcrypt (password_hash de PHP), nunca texto plano.
-- La sesión se maneja con un JWT que emite el backend al hacer login.
--
-- Usuario inicial:  admin  /  admin123   (cambiar la contraseña después)
-- =====================================================================

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hash bcrypt',
  `rol` enum('admin','supervisor','tecnico') NOT NULL DEFAULT 'tecnico',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`usuario`, `password`, `rol`)
VALUES ('admin', '$2y$10$JDgh87r.ZJFgR2KVimb98uwxx8Lb3Jx5ilJqS1RszDJKoyWhpaJXe', 'admin')
ON DUPLICATE KEY UPDATE `usuario` = `usuario`;

-- Verifica:  SELECT id, usuario, rol, activo FROM usuarios;
