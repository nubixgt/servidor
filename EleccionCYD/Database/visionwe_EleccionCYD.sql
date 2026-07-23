-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 23-07-2026 a las 17:52:03
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `visionwe_EleccionCYD`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones_coreografia`
--

CREATE TABLE `calificaciones_coreografia` (
  `id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL,
  `jurado_id` int(11) NOT NULL,
  `coordinacion` tinyint(3) UNSIGNED NOT NULL,
  `ritmo` tinyint(3) UNSIGNED NOT NULL,
  `desplazamiento` tinyint(3) UNSIGNED NOT NULL,
  `total` decimal(4,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calificaciones_coreografia`
--

INSERT INTO `calificaciones_coreografia` (`id`, `participante_id`, `jurado_id`, `coordinacion`, `ritmo`, `desplazamiento`, `total`, `created_at`, `updated_at`) VALUES
(5, 6, 1, 10, 6, 5, 21.00, '2026-07-23 17:18:54', '2026-07-23 17:47:49'),
(6, 1, 2, 10, 10, 10, 10.00, '2026-07-23 17:26:12', '2026-07-23 17:26:12'),
(7, 6, 2, 5, 5, 5, 15.00, '2026-07-23 17:26:39', '2026-07-23 17:50:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones_fashion_show`
--

CREATE TABLE `calificaciones_fashion_show` (
  `id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL,
  `jurado_id` int(11) NOT NULL,
  `originalidad` tinyint(3) UNSIGNED NOT NULL,
  `presentacion` tinyint(3) UNSIGNED NOT NULL,
  `coordinacion` tinyint(3) UNSIGNED NOT NULL,
  `total` decimal(4,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calificaciones_fashion_show`
--

INSERT INTO `calificaciones_fashion_show` (`id`, `participante_id`, `jurado_id`, `originalidad`, `presentacion`, `coordinacion`, `total`, `created_at`, `updated_at`) VALUES
(5, 6, 1, 4, 10, 8, 22.00, '2026-07-23 17:18:31', '2026-07-23 17:47:09'),
(7, 6, 2, 1, 1, 1, 3.00, '2026-07-23 17:49:39', '2026-07-23 17:49:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones_gala`
--

CREATE TABLE `calificaciones_gala` (
  `id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL,
  `jurado_id` int(11) NOT NULL,
  `modelaje` tinyint(3) UNSIGNED NOT NULL,
  `seguridad` tinyint(3) UNSIGNED NOT NULL,
  `pregunta_o_elegancia` tinyint(3) UNSIGNED NOT NULL,
  `total` decimal(4,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calificaciones_gala`
--

INSERT INTO `calificaciones_gala` (`id`, `participante_id`, `jurado_id`, `modelaje`, `seguridad`, `pregunta_o_elegancia`, `total`, `created_at`, `updated_at`) VALUES
(2, 6, 1, 5, 7, 8, 20.00, '2026-07-23 17:48:24', '2026-07-23 17:48:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participantes`
--

CREATE TABLE `participantes` (
  `id` int(11) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `categoria` enum('SENORITA','JOVEN') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `participantes`
--

INSERT INTO `participantes` (`id`, `codigo`, `nombre`, `categoria`, `activo`, `created_at`) VALUES
(1, 'SR01', 'Luna Estefany Consuelo Arevalo Ramos', 'SENORITA', 1, '2026-07-21 23:13:44'),
(2, 'SR02', 'Diana Luisa Alejandra Castro Velásquez', 'SENORITA', 1, '2026-07-21 23:13:44'),
(3, 'SR03', 'Madelyn Samara Hernandez Hernandez', 'SENORITA', 1, '2026-07-21 23:13:44'),
(4, 'SR04', 'Victoria Margarita Sharshente Gonzalez', 'SENORITA', 1, '2026-07-21 23:13:44'),
(5, 'SR05', 'Laisha Sofia Xitumul Ixcopal', 'SENORITA', 1, '2026-07-21 23:13:44'),
(6, 'SR06', 'Delmi Leonela Catalan Gonzalez', 'SENORITA', 1, '2026-07-21 23:13:44'),
(7, 'SR07', 'Dayra Yamileth Gomez Ixpata', 'SENORITA', 1, '2026-07-21 23:13:44'),
(8, 'SR08', 'Elba Dayanary Lopez Perez', 'SENORITA', 1, '2026-07-21 23:13:44'),
(9, 'SR09', 'Yaneli Alexandra Molineros Franco', 'SENORITA', 1, '2026-07-21 23:13:44'),
(10, 'JV01', 'Erik Josue Marroquin Villavicencio', 'JOVEN', 1, '2026-07-21 23:13:44'),
(11, 'JV02', 'Anthony Jose Salvatierra Rodriguez', 'JOVEN', 1, '2026-07-21 23:13:44'),
(12, 'JV03', 'Edgar Julio Manuel Juarez Garcia', 'JOVEN', 1, '2026-07-21 23:13:44'),
(13, 'JV04', 'Nery Bagner Josué Tello Oliva', 'JOVEN', 1, '2026-07-21 23:13:44'),
(14, 'JV05', 'Jefferson Gerrad Canahui Jeronimo', 'JOVEN', 1, '2026-07-21 23:13:44'),
(15, 'JV06', 'Juan Pablo Hernandez Rodriguez', 'JOVEN', 1, '2026-07-21 23:13:44'),
(16, 'JV07', 'Jeremy Alain Ebany Sarpec Ixtecoc', 'JOVEN', 1, '2026-07-21 23:13:44'),
(17, 'JV08', 'Ardany Edwin Mendoza', 'JOVEN', 1, '2026-07-21 23:13:44'),
(18, 'JV09', 'Cristhian Samuel Cuellar Flores', 'JOVEN', 1, '2026-07-21 23:13:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol` varchar(30) NOT NULL DEFAULT 'admin',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`, `nombre`, `rol`, `activo`, `created_at`) VALUES
(1, 'admin', '$2b$10$R6zmnnEIzXDrrxLRBbCYNOm22urniQw4Hf1ucpBa/tp4SttmMWhM.', 'Jurado Oficial', 'admin', 1, '2026-07-21 18:50:29'),
(2, 'pflores', '$2y$10$/ET3pmMwIxhGyVu1nVRFou47qjNBT4/txZDoGWxck8dDY49bb8/Vu', 'Pamela Flores', 'admin', 1, '2026-07-23 17:13:36'),
(3, 'emorela', '$2y$10$jOQwcctOhesEwK/AOQxqRe.1xUSktvx5hovn7Vfx7BdY8GdHuUDJC', 'Edin Mórela', 'admin', 1, '2026-07-23 17:13:36'),
(4, 'gtobias', '$2y$10$51E.GIio55mJJ1pXS1yQHOx5HjJOxwKGWU9E/s6.EOVHCuA8QdlTe', 'Gilary Tobías', 'admin', 1, '2026-07-23 17:13:36');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `calificaciones_coreografia`
--
ALTER TABLE `calificaciones_coreografia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_coreografia` (`participante_id`,`jurado_id`),
  ADD KEY `fk_co_jurado` (`jurado_id`);

--
-- Indices de la tabla `calificaciones_fashion_show`
--
ALTER TABLE `calificaciones_fashion_show`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fashion_show` (`participante_id`,`jurado_id`),
  ADD KEY `fk_fs_jurado` (`jurado_id`);

--
-- Indices de la tabla `calificaciones_gala`
--
ALTER TABLE `calificaciones_gala`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_gala` (`participante_id`,`jurado_id`),
  ADD KEY `fk_ga_jurado` (`jurado_id`);

--
-- Indices de la tabla `participantes`
--
ALTER TABLE `participantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `calificaciones_coreografia`
--
ALTER TABLE `calificaciones_coreografia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `calificaciones_fashion_show`
--
ALTER TABLE `calificaciones_fashion_show`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `calificaciones_gala`
--
ALTER TABLE `calificaciones_gala`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `participantes`
--
ALTER TABLE `participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `calificaciones_coreografia`
--
ALTER TABLE `calificaciones_coreografia`
  ADD CONSTRAINT `fk_co_jurado` FOREIGN KEY (`jurado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_co_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificaciones_fashion_show`
--
ALTER TABLE `calificaciones_fashion_show`
  ADD CONSTRAINT `fk_fs_jurado` FOREIGN KEY (`jurado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fs_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificaciones_gala`
--
ALTER TABLE `calificaciones_gala`
  ADD CONSTRAINT `fk_ga_jurado` FOREIGN KEY (`jurado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ga_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
