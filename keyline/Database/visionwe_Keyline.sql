-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 19-08-2026 a las 00:57:55
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `visionwe_Keyline`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos`
--

CREATE TABLE `fotos` (
  `id` int(10) UNSIGNED NOT NULL,
  `parcela_id` int(10) UNSIGNED NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `miniatura` varchar(255) DEFAULT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `subido_por` varchar(150) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parcelas`
--

CREATE TABLE `parcelas` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL COMMENT 'Código autogenerado KL-AAAA-00001',
  `nombre_parcela` varchar(200) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `municipio` varchar(100) NOT NULL,
  `comunidad` varchar(150) DEFAULT NULL,
  `propietario` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `tenencia_tierra` varchar(50) DEFAULT NULL,
  `num_familias_beneficiadas` int(10) UNSIGNED DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `latitud` decimal(10,6) DEFAULT NULL,
  `longitud` decimal(10,6) DEFAULT NULL,
  `gps_precision` decimal(10,2) DEFAULT NULL COMMENT 'Precisión GPS en metros',
  `altitud` decimal(10,2) DEFAULT NULL,
  `area_ha` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('Levantamiento','Diseño','Implementado','Pendiente') NOT NULL DEFAULT 'Levantamiento',
  `uso_actual` varchar(50) DEFAULT NULL,
  `tipo_suelo` varchar(150) DEFAULT NULL,
  `pendiente` decimal(6,2) DEFAULT NULL COMMENT 'Pendiente estimada en %',
  `agua` varchar(20) DEFAULT NULL COMMENT 'Disponibilidad de agua: Alta/Media/Baja/Estacional',
  `fuente_agua` varchar(100) DEFAULT NULL,
  `sistema_riego` varchar(100) DEFAULT NULL,
  `riesgo_erosion` varchar(20) DEFAULT NULL COMMENT 'Alto/Medio/Bajo',
  `cultivo_principal` varchar(150) DEFAULT NULL,
  `profundidad_suelo` decimal(6,2) DEFAULT NULL COMMENT 'cm',
  `talpetate` enum('Sí','No') DEFAULT NULL,
  `encharca` enum('Sí','No') DEFAULT NULL,
  `bioindicadores` text DEFAULT NULL,
  `lluvia_anual` decimal(8,2) DEFAULT NULL COMMENT 'mm acumulados anuales',
  `lluvia_fuente` varchar(150) DEFAULT NULL,
  `intervenciones` text DEFAULT NULL,
  `especies_reforestacion` text DEFAULT NULL,
  `fecha_proxima_visita` date DEFAULT NULL,
  `consentimiento_productor` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `tecnico_id` int(10) UNSIGNED NOT NULL,
  `estado_validacion` enum('Pendiente de revisión','Validado','Requiere corrección') NOT NULL DEFAULT 'Pendiente de revisión',
  `comentario_supervisor` text DEFAULT NULL,
  `revisado_por` varchar(150) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('tecnico','supervisor','administrador') NOT NULL DEFAULT 'tecnico',
  `region_asignada` varchar(100) DEFAULT NULL COMMENT 'Departamento asignado (principalmente para supervisores)',
  `telefono` varchar(30) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `email`, `password_hash`, `role`, `region_asignada`, `telefono`, `activo`, `created_at`, `ultimo_acceso`) VALUES
(1, 'Administrador Keyline', 'admin', 'admin@keyline.gt', '$2a$10$QLXS2cgebjdoQ6CV0p//wuSC.amE4zVd1i9DGdl96iDpPmSRjDMkq', 'administrador', NULL, NULL, 1, '2026-08-19 00:57:32', NULL),
(2, 'Supervisor Regional (demo)', 'supervisor', 'supervisor@keyline.gt', '$2a$10$v1hzgY8I9sPjX/xnhwwYmOtn4HxpfMBe6HUMXSwR/w3vRt9jXD32y', 'supervisor', 'Alta Verapaz', NULL, 1, '2026-08-19 00:57:32', NULL),
(3, 'Técnico de Campo (demo)', 'tecnico', 'tecnico@keyline.gt', '$2a$10$1kRN4Xi9t84n3WF75NhqyOH8FT8d3kLqrjTF.hFmDyDBZNHOaFF0G', 'tecnico', NULL, NULL, 1, '2026-08-19 00:57:32', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fotos_parcela` (`parcela_id`);

--
-- Indices de la tabla `parcelas`
--
ALTER TABLE `parcelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_parcelas_codigo` (`codigo`),
  ADD KEY `idx_parcelas_departamento` (`departamento`),
  ADD KEY `idx_parcelas_estado` (`estado`),
  ADD KEY `idx_parcelas_estado_validacion` (`estado_validacion`),
  ADD KEY `idx_parcelas_tecnico` (`tecnico_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuarios_usuario` (`usuario`),
  ADD KEY `idx_usuarios_role` (`role`),
  ADD KEY `idx_usuarios_region` (`region_asignada`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `fotos`
--
ALTER TABLE `fotos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parcelas`
--
ALTER TABLE `parcelas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD CONSTRAINT `fk_fotos_parcela` FOREIGN KEY (`parcela_id`) REFERENCES `parcelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `parcelas`
--
ALTER TABLE `parcelas`
  ADD CONSTRAINT `fk_parcelas_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
