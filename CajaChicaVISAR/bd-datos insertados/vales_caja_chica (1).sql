-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 27-02-2026 a las 16:52:15
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
-- Base de datos: `vales_caja_chica`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora_archivos`
--

CREATE TABLE `bitacora_archivos` (
  `id` int NOT NULL,
  `bitacora_id` int NOT NULL,
  `vale_id` int NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_archivo` varchar(100) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `tamano` int NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `subido_por` varchar(100) DEFAULT 'Sistema'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora_vales`
--

CREATE TABLE `bitacora_vales` (
  `id` int NOT NULL,
  `vale_id` int NOT NULL,
  `numero_vale` varchar(50) DEFAULT NULL,
  `usuario` varchar(100) DEFAULT 'Sistema',
  `accion` varchar(50) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) DEFAULT NULL,
  `campo_modificado` varchar(100) DEFAULT NULL,
  `valor_anterior` text,
  `valor_nuevo` text,
  `observacion` text,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int NOT NULL,
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`) VALUES
(1, 'prefijo_vale', 'VC', 'Prefijo para números de vale'),
(2, 'ultimo_numero', '13', 'Último número de vale generado'),
(3, 'institucion', 'VISAR - VICEDESPACHO', 'Nombre de la institución');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('USER','ADMIN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USER',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `nombre_completo`, `password`, `rol`, `activo`, `fecha_creacion`) VALUES
(1, 'admin', 'Administrador del Sistema', '$2y$10$eV.R0mXD6fDEsNLqgVCabuQb/7mKz.IzMruoMxfzeU6kNEfqv/Jke', 'ADMIN', 1, '2025-11-30 00:07:05'),
(3, 'memilia', 'Maria Emilia', '$2y$10$.peEHh3mJpVrpWYcJE5CCOhfocqvSUo0ShCjuej/KJBiIF6P9qPnS', 'ADMIN', 1, '2025-11-30 18:38:05'),
(6, 'thonix30', 'Anthony Alva', '$2y$10$ySQpBp44BTFZiH5n2/b/RuenLSRrPaISRlWStb1vqrFa85hpTD/Ni', 'ADMIN', 1, '2025-12-01 19:47:52'),
(7, 'ferdy', 'ferdy', '$2y$10$0Yr0xXx3FVx7Bwx.otNpHuqaCHZFv30jIc7xoeu5YmT7xh7x388/K', 'USER', 1, '2025-12-01 20:02:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vales`
--

CREATE TABLE `vales` (
  `id` int NOT NULL,
  `numero_vale` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `departamento` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otros_departamento` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_solicitante` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` enum('ALIMENTOS PERSONALES','INSUMOS','EQUIPO','LIBRERIA','MATERIALES DE CONSTRUCCION') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) DEFAULT '0.00',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_solicitud` date NOT NULL,
  `usuario_creador` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora_archivos`
--
ALTER TABLE `bitacora_archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bitacora` (`bitacora_id`),
  ADD KEY `idx_vale` (`vale_id`);

--
-- Indices de la tabla `bitacora_vales`
--
ALTER TABLE `bitacora_vales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vale_id` (`vale_id`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `vales`
--
ALTER TABLE `vales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_vale` (`numero_vale`),
  ADD KEY `idx_numero_vale` (`numero_vale`),
  ADD KEY `idx_fecha_solicitud` (`fecha_solicitud`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora_archivos`
--
ALTER TABLE `bitacora_archivos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `bitacora_vales`
--
ALTER TABLE `bitacora_vales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `vales`
--
ALTER TABLE `vales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora_archivos`
--
ALTER TABLE `bitacora_archivos`
  ADD CONSTRAINT `bitacora_archivos_ibfk_1` FOREIGN KEY (`bitacora_id`) REFERENCES `bitacora_vales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bitacora_archivos_ibfk_2` FOREIGN KEY (`vale_id`) REFERENCES `vales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `bitacora_vales`
--
ALTER TABLE `bitacora_vales`
  ADD CONSTRAINT `bitacora_vales_ibfk_1` FOREIGN KEY (`vale_id`) REFERENCES `vales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
