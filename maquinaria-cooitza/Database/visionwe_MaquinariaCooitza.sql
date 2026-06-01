-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 01-06-2026 a las 13:44:46
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

--
-- Volcado de datos para la tabla `maquinas`
--

INSERT INTO `maquinas` (`id`, `marca`, `tipo`, `identificador`, `foto_path`, `estado`, `horas_acumuladas`, `proximo_servicio`, `created_at`) VALUES
(1, 'John Deere', 'Retro Excavadora', 'ID-5120', 'uploads/Maquinaria/1/foto.webp', 'Mantenimiento', 1240.00, '2026-06-07', '2026-05-30 04:22:21'),
(2, 'Komatsu', 'Excavadora', 'ID-987410', 'uploads/Maquinaria/2/foto.webp', 'Operativo', 3600.00, '2026-06-04', '2026-05-30 04:28:10');

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
(1, 'Juan Carlos', '45289012', 'activo', '2026-05-29 23:31:09'),
(2, 'Ricardo', '98763214', 'activo', '2026-05-29 23:31:26'),
(3, 'LUIS FERNANDO', '30154896', 'activo', '2026-05-29 23:59:10'),
(4, 'PRUEBA 123', '87654321', 'inactivo', '2026-05-31 11:56:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `piloto_maquinas`
--

CREATE TABLE `piloto_maquinas` (
  `piloto_id` int(11) NOT NULL,
  `maquina_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `piloto_maquinas`
--

INSERT INTO `piloto_maquinas` (`piloto_id`, `maquina_id`) VALUES
(1, 1),
(1, 2),
(2, 2),
(3, 2),
(4, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registros_maquinaria`
--

CREATE TABLE `registros_maquinaria` (
  `id` int(11) NOT NULL,
  `operador` varchar(255) NOT NULL,
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

INSERT INTO `registros_maquinaria` (`id`, `operador`, `maquina_id`, `tipo_registro`, `valor_horometro`, `foto_horometro`, `latitud`, `longitud`, `fecha_registro`) VALUES
(1, 'Oscar Choc', 'tractor', 'inicial', 100.00, 'uploads/foto_69fb9d5f5d3ac8.00853558.jpg', 14.54317206, -90.54946079, '2026-05-06 19:58:23'),
(2, 'Eduardo Choc', 'retro', 'final', 150.00, 'uploads/registros_maquinaria/2/horometro.jpg', 14.54317206, -90.54946079, '2026-05-06 20:06:13'),
(3, 'Ulises Ruano', 'rodo', 'inicial', 200.00, 'uploads/registros_maquinaria/3/horometro.jpg', 14.54317206, -90.54946079, '2026-05-06 20:12:20'),
(4, 'Gabriel Tun', 'tractor', 'inicial', 11111.00, 'uploads/registros_maquinaria/4/horometro.jpg', 14.63906080, -90.51281015, '2026-05-06 20:32:23'),
(5, 'Alejandro del Cid', 'excavadora', 'inicial', 1500.00, 'uploads/registros_maquinaria/5/horometro.jpg', 14.92287199, -90.19528197, '2026-05-06 22:15:54');

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
(1, 'admin', '$2y$10$SODQ9CWCE5iCV2fbh4i6pO2l.lQ9SdwyNMdBwlVMPZwS4CxKIXNBC', 'Administrador Principal', 'admin', 'activo', '2026-05-30 03:55:29', '2026-05-31 11:57:54'),
(2, 'analista', '$2y$10$SODQ9CWCE5iCV2fbh4i6pO2l.lQ9SdwyNMdBwlVMPZwS4CxKIXNBC', 'Técnico Analista', 'tecnico_dashboard', 'activo', '2026-05-30 03:55:29', '2026-05-30 00:00:30'),
(3, 'piloto1', '$2y$10$SODQ9CWCE5iCV2fbh4i6pO2l.lQ9SdwyNMdBwlVMPZwS4CxKIXNBC', 'Robert Andersson', 'tecnico_piloto', 'activo', '2026-05-30 03:55:29', '2026-05-31 11:56:21'),
(5, 'prueba2', '$2y$10$CC9E/o3C7tPVOfgmEBPY4uf6QpzAQm73PVSSuHZxhM7AAFhUyFqZe', 'Prueba 2', 'admin', 'activo', '2026-05-30 04:47:27', '2026-05-29 22:48:03');

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
(1, 'Volvo', 'ABC-123', 'Camión', '2026', 1600, 'uploads/Vehiculos/1/foto.jpg', 'activo', '2026-05-29 23:32:21', 1);

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
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `registros_maquinaria`
--
ALTER TABLE `registros_maquinaria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Filtros para la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD CONSTRAINT `fk_vehiculo_piloto` FOREIGN KEY (`piloto_id`) REFERENCES `pilotos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
