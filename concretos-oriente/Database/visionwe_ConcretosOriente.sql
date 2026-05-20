-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 20-05-2026 a las 20:15:29
-- Versión del servidor: 11.4.11-MariaDB
-- Versión de PHP: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `visionwe_ConcretosOriente`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `machinery`
--

CREATE TABLE `machinery` (
  `id` int(11) NOT NULL,
  `categoria` enum('Maquinaria Pesada','Maquinaria Especial','Vehículo','Transporte Pesado','Equipo Menor') NOT NULL,
  `codigo_interno` varchar(50) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `anio_fabricacion` smallint(6) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `horometro_actual` int(11) NOT NULL DEFAULT 0,
  `kilometraje_actual` int(11) NOT NULL DEFAULT 0,
  `intervalo_servicio` int(11) DEFAULT NULL,
  `fecha_ultimo_servicio` date DEFAULT NULL,
  `operador_id` int(10) UNSIGNED DEFAULT NULL,
  `proyecto_id` int(11) DEFAULT NULL,
  `estado` enum('Activo','En Mantenimiento','En Reparación','Inactivo') NOT NULL DEFAULT 'Activo',
  `costo_adquisicion` decimal(12,2) DEFAULT NULL,
  `fecha_adquisicion` date DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `machinery`
--

INSERT INTO `machinery` (`id`, `categoria`, `codigo_interno`, `marca`, `modelo`, `numero_serie`, `anio_fabricacion`, `placa`, `horometro_actual`, `kilometraje_actual`, `intervalo_servicio`, `fecha_ultimo_servicio`, `operador_id`, `proyecto_id`, `estado`, `costo_adquisicion`, `fecha_adquisicion`, `foto_path`, `created_at`) VALUES
(1, 'Maquinaria Especial', '001-2026', 'SE12', '2026', '234DFSDF234', 2001, '567sfd', 100, 15000, 360, '2026-05-03', 1, 1, 'Activo', 20000.00, '2026-05-22', 'Uploads/Machinery/1/foto.jpg', '2026-05-20 19:45:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `machinery_log`
--

CREATE TABLE `machinery_log` (
  `id` int(11) NOT NULL,
  `maquina_id` int(11) NOT NULL,
  `proyecto_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `horometro_inicial` int(11) NOT NULL,
  `horometro_final` int(11) NOT NULL,
  `combustible_consumido` decimal(8,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `operador_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `machinery_log`
--

INSERT INTO `machinery_log` (`id`, `maquina_id`, `proyecto_id`, `fecha`, `horometro_inicial`, `horometro_final`, `combustible_consumido`, `observaciones`, `operador_id`, `created_at`) VALUES
(1, 1, 1, '2026-05-20', 15000, 30000, 95641.00, 'Prueba para saber si todo funciona correctamente', 1, '2026-05-20 19:46:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personnel`
--

CREATE TABLE `personnel` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo_empleado` enum('Administrativo','Operador','Piloto','Contratista') NOT NULL,
  `nombres` varchar(150) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `dpi` varchar(13) NOT NULL,
  `nit` varchar(20) DEFAULT NULL,
  `telefono` varchar(9) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `puesto` varchar(100) NOT NULL,
  `tipo_planilla` enum('Quincenal','Mensual','Semanal','Diario') NOT NULL,
  `salario_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarifa_hora_extra` decimal(10,2) DEFAULT NULL,
  `fecha_contratacion` date NOT NULL,
  `fecha_baja` date DEFAULT NULL,
  `numero_cuenta` varchar(50) DEFAULT NULL,
  `nombre_banco` varchar(100) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `proyecto_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personnel`
--

INSERT INTO `personnel` (`id`, `tipo_empleado`, `nombres`, `apellidos`, `dpi`, `nit`, `telefono`, `direccion`, `puesto`, `tipo_planilla`, `salario_base`, `tarifa_hora_extra`, `fecha_contratacion`, `fecha_baja`, `numero_cuenta`, `nombre_banco`, `foto_path`, `proyecto_id`, `created_at`, `updated_at`) VALUES
(1, 'Operador', 'Juan', 'Perez', '6546516510650', '9874', '9878-4651', 'Prueba de direccion', 'Programador', 'Mensual', 5000.00, 25.00, '2026-05-01', '2026-05-29', '987415', 'sdfsdf', 'Uploads/Personal/1/foto.jpg', 1, '2026-05-20 18:43:35', '2026-05-20 18:48:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `coordenadas` varchar(100) DEFAULT NULL,
  `presupuesto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` date NOT NULL,
  `fecha_fin_estimada` date DEFAULT NULL,
  `fecha_fin_real` date DEFAULT NULL,
  `estado` enum('Borrador','Activo','Pausado','Completado','Cancelado') NOT NULL DEFAULT 'Borrador',
  `numero_contrato` varchar(100) DEFAULT NULL,
  `contratos_archivos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contratos_archivos`)),
  `foto` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `contactos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contactos`)),
  `gerente_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `projects`
--

INSERT INTO `projects` (`id`, `codigo`, `nombre`, `cliente_id`, `ubicacion`, `coordenadas`, `presupuesto`, `fecha_inicio`, `fecha_fin_estimada`, `fecha_fin_real`, `estado`, `numero_contrato`, `contratos_archivos`, `foto`, `descripcion`, `contactos`, `gerente_id`, `created_at`, `updated_at`) VALUES
(1, '2026-001', 'Prueba 1', 5, 'Zona 14', '14.600518, -90.509191', 20000.00, '2026-05-01', '2026-05-14', '2026-05-20', 'Activo', '0000', '[\"Uploads\\/Projects\\/1\\/docs\\/FODA_de_trabajo_de_graduaci__n.pdf\",\"Uploads\\/Projects\\/1\\/docs\\/Propuesta_te__rica_y_dise__o_de_la_propuesta_te__rica.pdf\",\"Uploads\\/Projects\\/1\\/docs\\/Evaluaci__n_Final_____Pregunta_1.pdf\"]', 'Uploads/Projects/1/foto_1779301265.jpg', 'Prueba para saber si todo funciona correctamente y sin problema', '[{\"tipo\":\"Proveedor\",\"nombre\":\"Prueba 1\",\"telefono\":\"98754132\",\"email\":\"prueba1@gmail.com\"}]', 2, '2026-05-20 18:18:29', '2026-05-20 18:47:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(50) NOT NULL DEFAULT 'admin',
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `nombre`, `usuario`, `password`, `rol`, `estado`, `foto`, `created_at`, `updated_at`) VALUES
(2, 'Admin', 'admin', '$2y$10$bU4xCoXE5sAwb6y7yS1Wl.N9xVsyq7SwY5nVWVVquWVL.5Ehc2Qly', 'admin', 'Activo', 'Uploads/Users/2/foto_1779295276.jpg', '2026-05-20 16:41:16', '2026-05-20 16:41:51');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `machinery`
--
ALTER TABLE `machinery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_machinery_operador` (`operador_id`),
  ADD KEY `fk_machinery_proyecto` (`proyecto_id`);

--
-- Indices de la tabla `machinery_log`
--
ALTER TABLE `machinery_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_maquina` (`maquina_id`),
  ADD KEY `fk_log_proyecto` (`proyecto_id`),
  ADD KEY `fk_log_operador` (`operador_id`);

--
-- Indices de la tabla `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dpi` (`dpi`),
  ADD KEY `idx_proyecto_id` (`proyecto_id`);

--
-- Indices de la tabla `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `machinery`
--
ALTER TABLE `machinery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `machinery_log`
--
ALTER TABLE `machinery_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `machinery`
--
ALTER TABLE `machinery`
  ADD CONSTRAINT `fk_machinery_operador` FOREIGN KEY (`operador_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_machinery_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `machinery_log`
--
ALTER TABLE `machinery_log`
  ADD CONSTRAINT `fk_log_maquina` FOREIGN KEY (`maquina_id`) REFERENCES `machinery` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_operador` FOREIGN KEY (`operador_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `personnel`
--
ALTER TABLE `personnel`
  ADD CONSTRAINT `fk_personnel_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
