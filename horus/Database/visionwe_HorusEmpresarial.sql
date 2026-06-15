-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 15-06-2026 a las 13:36:26
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
-- Base de datos: `visionwe_HorusEmpresarial`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `cliente` varchar(255) NOT NULL,
  `refiere` int(11) DEFAULT NULL,
  `capital` decimal(12,2) DEFAULT NULL,
  `plazo` varchar(100) DEFAULT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `interes_pagar` decimal(12,2) DEFAULT NULL,
  `devolvio_capital` decimal(12,2) DEFAULT NULL,
  `pago_interes` decimal(12,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `documentacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `fecha`, `cliente`, `refiere`, `capital`, `plazo`, `porcentaje`, `interes_pagar`, `devolvio_capital`, `pago_interes`, `observaciones`, `created_at`, `documentacion`) VALUES
(1, '2026-06-11', 'Prueba 1', 1, 5000.00, '3', 8.00, 400.00, 5000.00, 400.00, 'REF. 655146315', '2026-06-10 17:00:59', '[\"uploads\\/Clientes\\/1\\/paisaje1.jpg\"]'),
(2, '2026-06-10', 'Prueba 2', 1, 10000.00, '24', 10.00, 15000.00, 0.00, 0.00, '', '2026-06-10 23:03:54', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `referencia` varchar(150) DEFAULT NULL,
  `monto_pagado` decimal(12,2) DEFAULT NULL,
  `foto` text DEFAULT NULL,
  `interes` decimal(12,2) DEFAULT NULL,
  `comprobante_interes` text DEFAULT NULL,
  `fecha_interes` date DEFAULT NULL,
  `capital` decimal(12,2) DEFAULT NULL,
  `comprobante_capital` text DEFAULT NULL,
  `fecha_capital` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `fecha`, `cliente_id`, `referencia`, `monto_pagado`, `foto`, `interes`, `comprobante_interes`, `fecha_interes`, `capital`, `comprobante_capital`, `fecha_capital`, `created_at`) VALUES
(1, '2026-06-12', 1, '798465132', 5000.00, 'uploads/pagos/foto/1/6a2add99e09ea_paisaje1.jpg', 500.00, 'uploads/pagos/intereses/1/6a2add99e0b2a_paisaje2.jpg', '2026-06-12', 500.00, 'uploads/pagos/capital/1/6a2add99e0c2c_paisaje2.jpg', '2026-06-12', '2026-06-11 15:53:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `referidos`
--

CREATE TABLE `referidos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `dpi` varchar(20) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` text DEFAULT NULL,
  `numero_cuenta` varchar(50) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `tipo_cuenta` varchar(20) DEFAULT NULL,
  `foto_perfil` text DEFAULT NULL,
  `dpi_anverso` text DEFAULT NULL,
  `dpi_reverso` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `referidos`
--

INSERT INTO `referidos` (`id`, `nombre`, `dpi`, `telefono`, `direccion`, `numero_cuenta`, `banco`, `tipo_cuenta`, `foto_perfil`, `dpi_anverso`, `dpi_reverso`, `created_at`) VALUES
(1, 'Zoe Villalobos', '7894 61532 0689', '8794-1532', 'Prueba 1 Referidos', '7984615', 'Banco Industrial', 'monetaria', 'uploads/Referidos/1/foto_perfil/6a29ec24be842_Logo_Horus_Empresarial.jpeg', 'uploads/Referidos/1/dpi/6a29b9838b2b5_paisaje1.jpg', 'uploads/Referidos/1/dpi/6a29b9838b37e_paisaje2.jpg', '2026-06-10 19:13:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(50) DEFAULT 'admin',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `rol`, `activo`, `created_at`) VALUES
(1, 'admin', '$2y$10$SODQ9CWCE5iCV2fbh4i6pO2l.lQ9SdwyNMdBwlVMPZwS4CxKIXNBC', 'admin', 1, '2026-06-10 17:16:44'),
(2, 'prueba', '$2y$10$SODQ9CWCE5iCV2fbh4i6pO2l.lQ9SdwyNMdBwlVMPZwS4CxKIXNBC', 'admin', 0, '2026-06-10 17:20:04');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cliente_referido` (`refiere`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pago_cliente` (`cliente_id`);

--
-- Indices de la tabla `referidos`
--
ALTER TABLE `referidos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `referidos`
--
ALTER TABLE `referidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_cliente_referido` FOREIGN KEY (`refiere`) REFERENCES `referidos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
