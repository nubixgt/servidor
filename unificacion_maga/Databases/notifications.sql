-- Script SQL para crear la tabla de notificaciones en Hostinger
-- Ejecuta este código en tu gestor de base de datos (phpMyAdmin, por ejemplo)

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('success','warning','info','error') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de prueba para que puedas ver que funciona al conectar el frontend
-- Asegúrate de cambiar el user_id por el ID de tu usuario administrador activo
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`) VALUES
(1, 'Bienvenido al Sistema', 'Las notificaciones en tiempo real han sido activadas.', 'success', 0),
(1, 'Mantenimiento Programado', 'El sistema entrará en mantenimiento el domingo a las 02:00 AM.', 'warning', 0),
(1, 'Reporte Listo', 'Tu reporte DAPCA se generó correctamente.', 'info', 0);
