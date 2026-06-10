-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 01-06-2026 a las 22:03:14
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `visionwe_MaquinariaCooitza`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maquinas`
--

CREATE TABLE `maquinas` (
  `id` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `tipo` enum('Tractor','Excavadora','Retro Excavadora','Rodo','Pipa','Camion Volteo') NOT NULL,
  `identificador` varchar(50) NOT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `estado` enum('Operativo','Mantenimiento','Fuera de Servicio') DEFAULT 'Operativo',
  `horas_acumuladas` decimal(10,2) DEFAULT 0.00,
  `proximo_servicio` varchar(50) DEFAULT 'Sin Programar',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pilotos`
--

CREATE TABLE `pilotos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `status` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `pilotos`
--

INSERT INTO `pilotos` (`id`, `nombre`, `telefono`, `status`, `created_at`) VALUES
(5, 'ALEX TUX', '87654321', 'activo', '2026-06-01 11:51:26'),
(6, 'ALEJANDRO DEL CID', '87654321', 'activo', '2026-06-01 11:52:02'),
(7, 'BENJAMÍN GARCIA', '87654321', 'activo', '2026-06-01 11:52:18'),
(8, 'VICTOR POP', '87654321', 'activo', '2026-06-01 11:52:32'),
(9, 'JAVIER OCHOA', '87654321', 'activo', '2026-06-01 11:52:49'),
(10, 'VIRGILIO DE LA CRUZ', '87654321', 'activo', '2026-06-01 11:53:01'),
(11, 'MARIO CHE', '87654321', 'activo', '2026-06-01 11:53:14'),
(12, 'ARNOLDO CUCUL', '87654321', 'activo', '2026-06-01 11:53:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `piloto_maquinas`
--

CREATE TABLE `piloto_maquinas` (
  `piloto_id` int(11) NOT NULL,
  `maquina_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registros_maquinaria`
--

CREATE TABLE `registros_maquinaria` (
  `id` int(11) NOT NULL,
  `operador` varchar(255) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `maquina_id` varchar(50) NOT NULL,
  `tipo_registro` enum('inicial','final') NOT NULL,
  `valor_horometro` decimal(10,2) NOT NULL,
  `foto_horometro` varchar(255) NOT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `registros_maquinaria`
--

INSERT INTO `registros_maquinaria` (`id`, `operador`, `usuario_id`, `maquina_id`, `tipo_registro`, `valor_horometro`, `foto_horometro`, `latitud`, `longitud`, `fecha_registro`) VALUES
(1, 'Oscar Choc', NULL, 'tractor', 'inicial', 100.00, 'uploads/foto_69fb9d5f5d3ac8.00853558.jpg', 14.54317206, -90.54946079, '2026-05-06 19:58:23'),
(2, 'Eduardo Choc', NULL, 'retro', 'final', 150.00, 'uploads/registros_maquinaria/2/horometro.jpg', 14.54317206, -90.54946079, '2026-05-06 20:06:13'),
(3, 'Ulises Ruano', NULL, 'rodo', 'inicial', 200.00, 'uploads/registros_maquinaria/3/horometro.jpg', 14.54317206, -90.54946079, '2026-05-06 20:12:20'),
(4, 'Gabriel Tun', NULL, 'tractor', 'inicial', 11111.00, 'uploads/registros_maquinaria/4/horometro.jpg', 14.63906080, -90.51281015, '2026-05-06 20:32:23'),
(5, 'Alejandro del Cid', NULL, 'excavadora', 'inicial', 1500.00, 'uploads/registros_maquinaria/5/horometro.jpg', 14.92287199, -90.19528197, '2026-05-06 22:15:54'),
(6, 'Robert Andersson', NULL, 'rodo', 'inicial', 1500.00, 'uploads/registros_maquinaria/6/horometro.jpg', 15.49740000, -90.25250000, '2026-06-01 14:16:09'),
(7, 'Robert Andersson', NULL, 'pipa', 'inicial', 2000.00, 'uploads/registros_maquinaria/7/horometro.jpg', 15.49740000, -90.25250000, '2026-06-01 14:21:14'),
(8, 'Robert Andersson', NULL, 'rodo', 'inicial', 2000.00, 'uploads/registros_maquinaria/8/horometro.jpg', 15.49740000, -90.25250000, '2026-06-01 14:28:06'),
(9, 'Robert Andersson', NULL, 'tractor', 'inicial', 11111.00, 'uploads/registros_maquinaria/9/horometro.jpeg', 14.63490000, -90.50690000, '2026-06-01 15:47:36'),
(10, 'Prueba', 17, 'tractor', 'inicial', 15000.00, 'uploads/registros_maquinaria/10/horometro.jpg', 15.49740000, -90.25250000, '2026-06-01 21:58:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','tecnico_dashboard','tecnico_piloto') NOT NULL,
  `status` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_access` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password_hash`, `full_name`, `role`, `status`, `created_at`, `last_access`) VALUES
(1, 'admin', '$2y$10$SODQ9CWCE5iCV2fbh4i6pO2l.lQ9SdwyNMdBwlVMPZwS4CxKIXNBC', 'Administrador Principal', 'admin', 'activo', '2026-05-30 03:55:29', '2026-06-01 15:59:16'),
(9, 'atux', '$2y$10$0YdaAlnB4aLnkKUywOQ0lefpudMyTXtNTXFILzpArrLo2lkAg4XDy', 'Alex Tux', 'tecnico_piloto', 'activo', '2026-06-01 18:09:44', '2026-06-01 12:15:19'),
(10, 'acid', '$2y$10$EoQ9K4FyZpYkJ6OaiHzPK.K4H8noqUkV3iaYbXGj3vKnQnTAGJzYK', 'Alejandro Del Cid', 'tecnico_piloto', 'activo', '2026-06-01 18:10:39', '2026-06-01 12:15:30'),
(11, 'bgarcia', '$2y$10$TJL/TxdAjONgoOBe8fNKr.BaACFHm9Sz.ZGl1uHSDgNQpwh.jR5tO', 'Benjamin Garcia', 'tecnico_piloto', 'activo', '2026-06-01 18:11:25', '2026-06-01 12:15:40'),
(12, 'vpop', '$2y$10$poKrZToNBSj/b2gLmyHYvelXOHFh08xHEDs7nglzdEgkSncy7W1IO', 'Victor Pop', 'tecnico_piloto', 'activo', '2026-06-01 18:11:58', '2026-06-01 12:41:14'),
(13, 'jochoa', '$2y$10$aP81EhpUTKnz.TWA7RYHKeUlPnzAk2HTkZEc0QBrizL.eSkI2Y88m', 'Javier Ochoa', 'tecnico_piloto', 'activo', '2026-06-01 18:12:34', '2026-06-01 12:16:04'),
(14, 'vcruz', '$2y$10$Xo9SMUPPZHSetImLezNQrul6qhXjfdbMO5e7GXCe8gLUq3PRTouGW', 'Virgilio De La Cruz', 'tecnico_piloto', 'activo', '2026-06-01 18:13:10', '2026-06-01 12:16:15'),
(15, 'mche', '$2y$10$UGO7utGjF0XfE8VrE1dtNe30gVmnInDOLVb39yN/Ds7oijZe5yE.2', 'Mario Che', 'tecnico_piloto', 'activo', '2026-06-01 18:13:45', '2026-06-01 15:47:55'),
(16, 'acucul', '$2y$10$kIGaHGvAHKkq5XRoANUl9ucjFJMZWmT2IkEnWtIGyhUM8XGZ2OZuW', 'Arnoldo Cucul', 'tecnico_piloto', 'activo', '2026-06-01 18:14:31', '2026-06-01 12:16:36'),
(17, 'piloto1', '$2y$10$pGpwnvn4GlTwhvuRhWKPK.CAn9AEL66WNqMa3Pa.2jWN659yjkk.a', 'Prueba', 'tecnico_piloto', 'activo', '2026-06-01 21:57:27', '2026-06-01 15:57:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos`
--

CREATE TABLE `vehiculos` (
  `id` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `placa` varchar(50) NOT NULL,
  `tipo` enum('Camión','Pickup') NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `kilometraje_registro` int(11) NOT NULL DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` datetime DEFAULT current_timestamp(),
  `piloto_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `vehiculos`
--

INSERT INTO `vehiculos` (`id`, `marca`, `placa`, `tipo`, `modelo`, `kilometraje_registro`, `foto`, `status`, `created_at`, `piloto_id`) VALUES
(2, 'VOLTEO', '687BXM', 'Camión', '', 0, NULL, 'activo', '2026-06-01 11:49:46', NULL),
(3, 'VOLTEO', '868CCB', 'Camión', '', 0, NULL, 'activo', '2026-06-01 11:50:24', NULL),
(4, 'VOLTEO', '558BNC', 'Camión', '', 0, NULL, 'activo', '2026-06-01 11:50:39', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `maquinas`
--
ALTER TABLE `maquinas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identificador` (`identificador`);

--
-- Indices de la tabla `pilotos`
--
ALTER TABLE `pilotos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `piloto_maquinas`
--
ALTER TABLE `piloto_maquinas`
  ADD PRIMARY KEY (`piloto_id`,`maquina_id`),
  ADD KEY `maquina_id` (`maquina_id`);

--
-- Indices de la tabla `registros_maquinaria`
--
ALTER TABLE `registros_maquinaria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_registro_usuario` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `fk_vehiculo_piloto` (`piloto_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `maquinas`
--
ALTER TABLE `maquinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pilotos`
--
ALTER TABLE `pilotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `registros_maquinaria`
--
ALTER TABLE `registros_maquinaria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `piloto_maquinas`
--
ALTER TABLE `piloto_maquinas`
  ADD CONSTRAINT `piloto_maquinas_ibfk_1` FOREIGN KEY (`piloto_id`) REFERENCES `pilotos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `piloto_maquinas_ibfk_2` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `registros_maquinaria`
--
ALTER TABLE `registros_maquinaria`
  ADD CONSTRAINT `fk_registro_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD CONSTRAINT `fk_vehiculo_piloto` FOREIGN KEY (`piloto_id`) REFERENCES `pilotos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
