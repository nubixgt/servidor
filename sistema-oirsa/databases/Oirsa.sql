-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 24-02-2026 a las 14:54:54
-- Versión del servidor: 8.0.45-0ubuntu0.24.04.1
-- Versión de PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `Oirsa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contratos`
--

CREATE TABLE `contratos` (
  `id` int NOT NULL,
  `numero_contrato` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servicios` enum('Tecnicos','Profesionales') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iva` enum('Incluir','Sumarse') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fondos` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `armonizacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `armonizacion_otro` text COLLATE utf8mb4_unicode_ci,
  `termino_contratacion` text COLLATE utf8mb4_unicode_ci,
  `fecha_contrato` date DEFAULT NULL,
  `nombre_completo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `edad` int NOT NULL,
  `estado_civil` enum('Soltero','Casado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `profesion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domicilio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dpi` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `termino1` text COLLATE utf8mb4_unicode_ci,
  `termino2` text COLLATE utf8mb4_unicode_ci,
  `termino3` text COLLATE utf8mb4_unicode_ci,
  `termino4` text COLLATE utf8mb4_unicode_ci,
  `termino5` text COLLATE utf8mb4_unicode_ci,
  `termino6` text COLLATE utf8mb4_unicode_ci,
  `termino7` text COLLATE utf8mb4_unicode_ci,
  `termino8` text COLLATE utf8mb4_unicode_ci,
  `termino9` text COLLATE utf8mb4_unicode_ci,
  `termino10` text COLLATE utf8mb4_unicode_ci,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `monto_total` decimal(12,2) NOT NULL,
  `numero_pagos` int NOT NULL,
  `monto_pago` decimal(12,2) NOT NULL,
  `usuario_id` int NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contrato_archivos`
--

CREATE TABLE `contrato_archivos` (
  `id` int NOT NULL,
  `contrato_id` int NOT NULL,
  `tipo_archivo` enum('cv','titulo','colegiadoActivo','cuentaBanco','dpiArchivo','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin') COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`, `rol`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'admin', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'admin', 1, '2026-01-06 22:31:54', '2026-01-06 22:32:16'),
(2, 'memilia', '$2y$10$HNj0ZneBFxJKu2stal78PuuL7lZXzdHUMPpsNIFiMcnA6p0TcYpP.', 'admin', 1, '2026-01-08 02:45:09', '2026-01-08 02:49:44');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `contratos`
--
ALTER TABLE `contratos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dpi` (`dpi`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha_registro` (`fecha_registro`),
  ADD KEY `idx_numero_contrato` (`numero_contrato`);

--
-- Indices de la tabla `contrato_archivos`
--
ALTER TABLE `contrato_archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contrato` (`contrato_id`),
  ADD KEY `idx_tipo` (`tipo_archivo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_usuario` (`usuario`),
  ADD KEY `idx_activo` (`activo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contrato_archivos`
--
ALTER TABLE `contrato_archivos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `contratos`
--
ALTER TABLE `contratos`
  ADD CONSTRAINT `contratos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `contrato_archivos`
--
ALTER TABLE `contrato_archivos`
  ADD CONSTRAINT `contrato_archivos_ibfk_1` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
