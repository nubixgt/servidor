-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 24-06-2026 a las 14:51:57
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
  `porcentaje_referido` decimal(5,2) DEFAULT NULL,
  `interes_pagar` decimal(12,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `documentacion` text DEFAULT NULL,
  `inversionista_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `fecha`, `cliente`, `refiere`, `capital`, `plazo`, `porcentaje`, `porcentaje_referido`, `interes_pagar`, `observaciones`, `created_at`, `documentacion`, `inversionista_id`) VALUES
(3, '2026-05-10', 'JORGE OLIVA', 2, 50000.00, '24', 5.00, 0.00, 60000.00, '', '2026-06-19 22:51:20', NULL, 2),
(4, '2026-03-20', 'FEERNANDO MARIN', 3, 4000.00, '3', 8.00, 3.00, 960.00, 'PIDIO AMPLIACION CON FECHA 19 DE JUNIO PARA PAGAR EN SEPTIEMBRE CAPITAL E INTERESES ', '2026-06-19 23:18:32', NULL, 2),
(13, '2026-06-19', 'THELMA BARCARCEL', 2, 100000.00, '12', 5.00, 0.00, 60000.00, '', '2026-06-19 23:48:06', NULL, 2),
(14, '2026-06-15', 'ADAN DE LA PEÑA', 2, 30000.00, '1', 3.33, 0.00, 999.00, '', '2026-06-19 23:52:22', NULL, 1),
(15, '2026-02-06', 'EDGAR CHAMALE ABR', 2, 50000.00, '12', 5.00, 0.00, 30000.00, '', '2026-06-19 23:55:41', NULL, 1),
(16, '2026-02-06', 'EDGAR CHAMALE HORUS', 2, 5000.00, '12', 5.00, 0.00, 3000.00, '', '2026-06-19 23:57:51', NULL, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egresos`
--

CREATE TABLE `egresos` (
  `id` int(11) NOT NULL,
  `recurrente_id` int(11) DEFAULT NULL,
  `tipo_egreso` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `pagador` varchar(255) DEFAULT NULL,
  `comprobante` text DEFAULT NULL,
  `descripcion_concepto` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `egresos`
--

INSERT INTO `egresos` (`id`, `recurrente_id`, `tipo_egreso`, `fecha`, `referencia`, `pagador`, `comprobante`, `descripcion_concepto`, `created_at`) VALUES
(1, NULL, 'GASTOS ADMINISTRATIVOS', '2026-06-30', '00000000', 'DIANA RUANO', 'uploads/Egresos/1/6a31bf727e52f_carro1.jpg', 'ESTE PAGO DEBE SALIR DEL 5% DE LAS GANANCIAS ', '2026-06-16 14:40:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egreso_registros`
--

CREATE TABLE `egreso_registros` (
  `id` int(11) NOT NULL,
  `egreso_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos_recurrentes`
--

CREATE TABLE `gastos_recurrentes` (
  `id` int(11) NOT NULL,
  `concepto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `dia_pago` int(2) NOT NULL,
  `estado` varchar(20) DEFAULT 'ACTIVO',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inversionistas`
--

CREATE TABLE `inversionistas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `capital` decimal(12,2) DEFAULT NULL,
  `banco` varchar(150) DEFAULT NULL,
  `numero_cuenta` varchar(50) DEFAULT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `documentos` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `inversionistas`
--

INSERT INTO `inversionistas` (`id`, `nombre`, `capital`, `banco`, `numero_cuenta`, `porcentaje`, `documentos`, `created_at`) VALUES
(1, 'ABR', 661697.95, 'BANRURAL', '03913602860308', 2.50, '[\"uploads\\/Inversionistas\\/1\\/6a31bbc57952e_carro4.jpg\"]', '2026-06-15 14:07:56'),
(2, 'HORUS', 815000.00, 'BANRURAL', '4151162143', 5.00, NULL, '2026-06-17 00:39:29'),
(3, 'DEL VALLE', 150000.00, '', '', 2.50, NULL, '2026-06-19 23:02:48'),
(4, 'ORLANDO DONIS', 150000.00, 'BANRURAL', '4151159685', 2.50, NULL, '2026-06-19 23:03:49');

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
(1, '2026-06-12', NULL, '7984651', 5000.00, 'uploads/pagos/foto/1/6a31bee061b5f_carro4.jpg', 500.00, 'uploads/pagos/intereses/1/6a31bed10bbf9_carro3.jpg', '2026-06-12', 500.00, 'uploads/pagos/capital/1/6a31bed10c045_carro2.jpg', '2026-06-12', '2026-06-11 15:53:18'),
(2, '2026-06-15', 3, '4587555855', 5000.00, NULL, 2500.00, NULL, '2026-06-15', 2500.00, NULL, '2026-06-15', '2026-06-19 23:27:54'),
(3, '2026-06-17', 3, '25448987', 5000.00, NULL, 2375.00, NULL, '2026-06-19', 2625.00, NULL, '2026-06-19', '2026-06-19 23:36:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos`
--

CREATE TABLE `prestamos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `monto_principal` decimal(12,2) NOT NULL,
  `tasa` decimal(5,2) NOT NULL,
  `tipo_interes` varchar(50) NOT NULL,
  `plazo` int(11) NOT NULL,
  `fecha_desembolso` date NOT NULL,
  `cuota_seguro` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_intereses` decimal(12,2) NOT NULL,
  `total_seguro` decimal(12,2) NOT NULL,
  `costo_total` decimal(12,2) NOT NULL,
  `estado` varchar(20) DEFAULT 'ACTIVO',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `historial_pagos_mensual` decimal(12,2) DEFAULT NULL,
  `historial_pagos_anual` decimal(12,2) DEFAULT NULL,
  `tipo_clientes_refiere` varchar(255) DEFAULT NULL,
  `cantidad_clientes` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `referidos`
--

INSERT INTO `referidos` (`id`, `nombre`, `dpi`, `telefono`, `direccion`, `numero_cuenta`, `banco`, `tipo_cuenta`, `foto_perfil`, `dpi_anverso`, `dpi_reverso`, `created_at`, `historial_pagos_mensual`, `historial_pagos_anual`, `tipo_clientes_refiere`, `cantidad_clientes`) VALUES
(2, 'OSCAR DONIS', '2272 79328 1602', '5555-3199', 'ALDEA SAN JUAN', '4151125225', 'BANRURAL S A ', 'ahorro', NULL, NULL, NULL, '2026-06-19 22:47:51', 0.00, 0.00, '', 0),
(3, 'RICARDO  GIRON', '', '5516-3058', 'BARRIO AGUA CALIENTE, SALAMÁ', '4204180258', 'BANRURAL', 'ahorro', NULL, NULL, NULL, '2026-06-19 23:08:26', 0.00, 0.00, '', 0),
(4, 'ADAN DE LA PEÑA', '', '3361-6177', 'PASEO LAS LOMAS, SALAMÁ', '7173986733', 'INDUSTRIAL', 'monetaria', NULL, NULL, NULL, '2026-06-19 23:09:41', 0.00, 0.00, '', 0),
(5, 'MARICELA ERICASTILLA', '', '3186-2907', 'BARRIO LAS PIEDRECITAS', '4010114778', 'BANRURAL', 'ahorro', NULL, NULL, NULL, '2026-06-19 23:10:58', 0.00, 0.00, '', 0);

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
  ADD KEY `fk_cliente_referido` (`refiere`),
  ADD KEY `fk_cliente_inversionista` (`inversionista_id`);

--
-- Indices de la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_egreso_recurrente` (`recurrente_id`);

--
-- Indices de la tabla `egreso_registros`
--
ALTER TABLE `egreso_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_registro_egreso` (`egreso_id`);

--
-- Indices de la tabla `gastos_recurrentes`
--
ALTER TABLE `gastos_recurrentes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inversionistas`
--
ALTER TABLE `inversionistas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pago_cliente` (`cliente_id`);

--
-- Indices de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `egresos`
--
ALTER TABLE `egresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `egreso_registros`
--
ALTER TABLE `egreso_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `gastos_recurrentes`
--
ALTER TABLE `gastos_recurrentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `inversionistas`
--
ALTER TABLE `inversionistas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `referidos`
--
ALTER TABLE `referidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  ADD CONSTRAINT `fk_cliente_inversionista` FOREIGN KEY (`inversionista_id`) REFERENCES `inversionistas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cliente_referido` FOREIGN KEY (`refiere`) REFERENCES `referidos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `egresos`
--
ALTER TABLE `egresos`
  ADD CONSTRAINT `fk_egreso_recurrente` FOREIGN KEY (`recurrente_id`) REFERENCES `gastos_recurrentes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `egreso_registros`
--
ALTER TABLE `egreso_registros`
  ADD CONSTRAINT `fk_registro_egreso` FOREIGN KEY (`egreso_id`) REFERENCES `egresos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD CONSTRAINT `prestamos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inversionista_movimientos`
--

CREATE TABLE `inversionista_movimientos` (
  `id` int(11) NOT NULL,
  `inversionista_id` int(11) NOT NULL,
  `tipo` enum('INGRESO','DESCUENTO') NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `inversionista_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mov_inversionista` (`inversionista_id`);

ALTER TABLE `inversionista_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `inversionista_movimientos`
  ADD CONSTRAINT `fk_mov_inversionista_fk` FOREIGN KEY (`inversionista_id`) REFERENCES `inversionistas` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `referido_pagos`
--

CREATE TABLE `referido_pagos` (
  `id` int(11) NOT NULL,
  `referido_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `referido_pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pago_referido_2` (`referido_id`);

ALTER TABLE `referido_pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `referido_pagos`
  ADD CONSTRAINT `fk_pago_referido_fk` FOREIGN KEY (`referido_id`) REFERENCES `referidos` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
