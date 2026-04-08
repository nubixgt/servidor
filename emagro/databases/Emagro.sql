-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 08-04-2026 a las 21:33:14
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
-- Base de datos: `Emagro`
--

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `detalle_nota_envio`;
DROP TABLE IF EXISTS `pagos`;
DROP TABLE IF EXISTS `nueva_venta`;
DROP TABLE IF EXISTS `nota_envio`;
DROP TABLE IF EXISTS `productos_precios`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nit` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `departamento` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `municipio` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bloquear_ventas` enum('si','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `usuario_id` int NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `nit`, `telefono`, `departamento`, `municipio`, `direccion`, `email`, `bloquear_ventas`, `usuario_id`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Juan Pérez López', '11652646-7', '4528-9012', 'Guatemala', 'Guatemala', 'Zona 1, 5ta Avenida 10-20', 'juan.perez@example.com', 'no', 1, '2026-01-20 17:42:50', '2026-01-20 17:42:50'),
(3, 'Carlos Rodríguez', '98765432-1', '7789-4561', 'Escuintla', 'Escuintla', 'Barrio El Centro, 3ra Calle 8-45', NULL, 'no', 1, '2026-01-20 17:42:50', '2026-01-20 17:42:50'),
(6, 'Prueba 2', 'CF', '0202-1878', 'Guatemala', 'Palencia', 'Prueba 2 de direccion', 'prueba2@gmail.com', 'no', 3, '2026-01-20 18:41:28', '2026-01-25 18:15:03'),
(7, 'agropecuaria la cosecha', 'CF', '4848-8266', 'Sacatepéquez', 'Sumpango', 'calle la Alameda', NULL, 'no', 5, '2026-01-23 18:40:09', '2026-01-23 18:40:09'),
(8, 'Estela Ticun', 'CF', '3157-6774', 'Sacatepéquez', 'Santiago Sacatepéquez', 'calle del cementerio', NULL, 'no', 5, '2026-01-23 18:59:46', '2026-01-23 18:59:46'),
(9, 'Elvis Velasquez', 'CF', '5907-7279', 'Chimaltenango', 'Parramos', 'camino a chuito', NULL, 'no', 5, '2026-01-23 22:27:15', '2026-01-23 22:27:15'),
(10, 'Danilo Gómez', 'CF', '4776-2652', 'Baja Verapaz', 'Salamá', 'La unión Barrios', NULL, 'no', 5, '2026-01-24 06:49:25', '2026-01-24 06:49:25'),
(11, 'Andrés Ticun', 'CF', '4565-7361', 'Sacatepéquez', 'Santiago Sacatepéquez', 'camino al Cementerio', NULL, 'no', 5, '2026-01-24 07:00:23', '2026-01-24 07:00:23'),
(12, 'Sergio Roquel', 'CF', '5746-6327', 'Chimaltenango', 'Zaragoza', 'Aldea Rincón Grande', NULL, 'no', 5, '2026-01-24 07:06:30', '2026-01-24 07:06:30'),
(13, 'Edy Pérez', 'CF', '4998-6894', 'Guatemala', 'San Juan Sacatepéquez', 'Sector 5 Sajcavilla', NULL, 'no', 5, '2026-01-24 07:12:29', '2026-01-24 07:12:29'),
(14, 'José Pirir', 'CF', '3012-7812', 'Guatemala', 'San Juan Sacatepéquez', 'Loma Alta San Juan Sacatepéquez', NULL, 'no', 5, '2026-01-24 07:17:21', '2026-01-24 07:17:21'),
(15, 'Hugo Coronado Mauricio', 'CF', '5054-9560', 'San Marcos', 'Comitancillo', 'aldea Taltimiche', NULL, 'no', 5, '2026-01-24 07:26:07', '2026-01-24 07:26:07'),
(16, 'Agrogenio', 'CF', '4258-1359', 'Guatemala', 'San Juan Sacatepéquez', 'Los García Loma Alta San Juan Sacatepéquez', NULL, 'no', 5, '2026-01-24 22:48:49', '2026-01-24 22:48:49'),
(17, 'Aura Pérez', 'CF', '4050-7715', 'Guatemala', 'San Juan Sacatepéquez', 'Loma Alta', NULL, 'no', 5, '2026-01-24 22:55:47', '2026-01-24 22:55:47'),
(18, 'Marcos Díaz', 'CF', '5324-3523', 'Guatemala', 'San Juan Sacatepéquez', 'Cruz blanca San Juan Sacatepéquez', NULL, 'no', 5, '2026-01-24 22:59:00', '2026-01-24 22:59:00'),
(19, 'Alfonso Boch', 'CF', '5191-4248', 'Guatemala', 'San Juan Sacatepéquez', 'Loma Alta San Juan Sacatepéquez', NULL, 'no', 5, '2026-01-24 23:05:37', '2026-01-24 23:05:37'),
(20, 'Mario Patzan', 'CF', '4052-3810', 'Guatemala', 'San Juan Sacatepéquez', 'Loma Alta San Juan Sacatepéquez', NULL, 'no', 5, '2026-01-24 23:12:54', '2026-01-24 23:12:54'),
(21, 'Agroservicio El Chuluc', 'CF', '3604-6235', 'Chimaltenango', 'Patzicía', 'El Chuluc Patzicia', NULL, 'no', 5, '2026-01-24 23:23:12', '2026-01-24 23:23:12'),
(22, 'Héctor Tuquer', 'CF', '5031-2262', 'Guatemala', 'San Juan Sacatepéquez', 'Loma Alta San Juan Sacatepéquez', NULL, 'no', 5, '2026-01-24 23:33:51', '2026-01-24 23:33:51'),
(23, 'Industrial de Aceites y Grasas Suprema S.A', 'CF', '5947-8850', 'Guatemala', 'Guatemala', '9a Avenida 19-61 Edificio Zenit, Nível 10, Zona 10 Guatemala.', NULL, 'no', 5, '2026-01-29 23:44:22', '2026-01-29 23:44:22'),
(24, 'cesar Tepet', 'CF', '3684-4787', 'Guatemala', 'San Juan Sacatepéquez', 'Joya de las Flores San Juan Sacatepéquez', NULL, 'no', 5, '2026-02-02 22:09:22', '2026-02-02 22:09:22'),
(25, 'Agrocampo', 'CF', '5195-2487', 'Guatemala', 'San Juan Sacatepéquez', 'Cruz Blanca Entrada al campo', NULL, 'no', 5, '2026-02-07 03:26:29', '2026-02-07 03:26:29'),
(26, 'Venancio Locon', 'CF', '5064-1883', 'Guatemala', 'San Juan Sacatepéquez', 'Loma Alta San Juan Sacatepéquez', NULL, 'no', 5, '2026-02-07 03:30:42', '2026-02-07 03:30:42'),
(27, 'Agroservicio Caleb', 'CF', '4387-3430', 'Sacatepéquez', 'Sumpango', 'Aldea Santa Marta Sumpango Sacatepéquez', NULL, 'no', 5, '2026-02-10 01:02:58', '2026-02-10 01:02:58'),
(28, 'Octavio Turuy', 'CF', '3813-5716', 'Sacatepéquez', 'Sumpango', 'Aldea el Rejón Sumpango Sacatepéquez', NULL, 'no', 5, '2026-02-21 00:57:34', '2026-02-21 00:57:34'),
(29, 'Héctor Carrascoza', 'CF', '5491-8518', 'Quiché', 'Joyabaj', 'Barrio La Libertad a la par del Colegio Florencio Carrascoza, Joyabaj Quiché', NULL, 'no', 5, '2026-02-21 01:18:38', '2026-02-21 01:18:38'),
(30, 'Federico Ticun', 'CF', '3036-5459', 'Sacatepéquez', 'Santiago Sacatepéquez', 'Santa María Cauque', NULL, 'no', 5, '2026-03-08 04:35:19', '2026-03-08 04:35:19'),
(31, 'Luciano Alvarez', 'CF', '4684-3898', 'Chimaltenango', 'Santa Cruz Balanyá', 'Aldea Chimazat Santa Cruz Balanya', NULL, 'no', 5, '2026-03-08 04:40:40', '2026-03-08 04:40:40'),
(32, 'Ivonne Cooperativa 4 Pinos', 'CF', '3058-0750', 'Sacatepéquez', 'Santiago Sacatepéquez', '4ta. Calle 5-01 Zona 1 Santiago Sacatepéquez', NULL, 'no', 5, '2026-03-08 04:55:27', '2026-03-08 04:55:27'),
(33, 'Universidad del Valle', 'CF', '7931-0814', 'Sololá', 'Sololá', 'Km 137 Caserío Xolbe El Tablón Sololá', NULL, 'no', 5, '2026-03-08 05:01:16', '2026-03-08 05:01:16'),
(34, 'Maritza Rivas', 'CF', '4098-9933', 'Chimaltenango', 'Chimaltenango', 'Km 58 Chimaltenango', NULL, 'no', 5, '2026-03-13 03:01:52', '2026-03-13 03:02:45'),
(35, 'Óscar Tuquer', 'CF', '5304-7145', 'Guatemala', 'San Juan Sacatepéquez', 'Loma Alta San Juan Sacatepéquez', NULL, 'no', 5, '2026-03-19 04:34:00', '2026-03-19 04:34:00'),
(36, 'Mario Tol', 'CF', '4605-8079', 'Chimaltenango', 'Tecpán Guatemala', 'Agua Escondida Tecpán Guatemala', NULL, 'no', 5, '2026-03-24 05:03:34', '2026-03-24 05:03:34'),
(37, 'Club Rotario de la Antigua Guatemala', 'CF', '7832-7600', 'Sacatepéquez', 'Antigua Guatemala', 'calle del Espíritu Santo No. 37A Antigua Guatemala', NULL, 'no', 5, '2026-03-28 04:49:35', '2026-03-28 04:49:35'),
(38, 'Henry Constanza', 'CF', '5203-7180', 'Escuintla', 'Escuintla', 'Escuintla', NULL, 'no', 5, '2026-04-07 04:11:01', '2026-04-07 04:11:01'),
(39, 'Marvin Similox', 'CF', '4831-8077', 'Chimaltenango', 'San Juan Comalapa', 'San Juan Comalapa', NULL, 'no', 5, '2026-04-07 04:19:33', '2026-04-07 04:19:33'),
(40, 'Pablo Subuyuj', 'CF', '5705-0056', 'Guatemala', 'San Juan Sacatepéquez', 'Los pingüinos Loma Alta San Juan Sacatepéquez', NULL, 'no', 5, '2026-04-08 04:38:53', '2026-04-08 04:38:53'),
(41, 'Walter Giron', 'CF', '4976-5616', 'Chimaltenango', 'San Andrés Itzapa', 'Resistenciales Valle del Sol Km 58.5 camino a la Antigua Guatemala', NULL, 'no', 5, '2026-04-08 04:40:36', '2026-04-08 04:40:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_nota_envio`
--

CREATE TABLE `detalle_nota_envio` (
  `id` int NOT NULL,
  `nota_envio_id` int NOT NULL,
  `producto` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `presentacion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int NOT NULL,
  `es_bonificacion` enum('si','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_nota_envio`
--

INSERT INTO `detalle_nota_envio` (`id`, `nota_envio_id`, `producto`, `presentacion`, `precio_unitario`, `cantidad`, `es_bonificacion`, `descuento`, `total`) VALUES
(3, 2, 'EM SuperSuelo', '4 litros', 120.00, 10, 'no', 200.00, 1000.00),
(4, 3, 'EM SuperSuelo', '20 litros', 550.00, 4, 'no', 400.00, 1800.00),
(5, 3, 'EM SuperFoliar', '1 litro', 170.00, 15, 'no', 675.00, 1875.00),
(6, 3, 'EM SuperRaiz', '1 litro', 90.00, 15, 'no', 225.00, 1125.00),
(7, 4, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(8, 4, 'EM SuperAgua', '20 litros', 525.00, 2, 'no', 150.00, 900.00),
(9, 5, 'EM SuperSuelo', '20 litros', 550.00, 1, 'no', 25.00, 525.00),
(10, 5, 'EM SuperFoliar', '1 litro', 170.00, 1, 'no', 30.00, 140.00),
(11, 5, 'EM SuperRaiz', '1 litro', 90.00, 1, 'no', 90.00, 0.00),
(12, 6, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(13, 6, 'EM SuperSuelo', '4 litros', 120.00, 2, 'no', 240.00, 0.00),
(14, 7, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 225.00, 1425.00),
(15, 8, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(16, 9, 'EM SuperSuelo', '20 litros', 550.00, 11, 'no', 750.00, 5300.00),
(17, 10, 'EM SuperSuelo', '4 litros', 120.00, 1, 'no', 0.00, 120.00),
(18, 36, 'EM SuperSuelo', '20 litros', 550.00, 2, 'no', 200.00, 900.00),
(19, 37, 'EM SuperSuelo', '20 litros', 550.00, 2, 'no', 200.00, 900.00),
(20, 38, 'EM SuperRaiz', '1 litro', 90.00, 15, 'no', 150.00, 1200.00),
(21, 39, 'EM SuperSuelo', '20 litros', 550.00, 11, 'no', 1250.00, 4800.00),
(22, 40, 'EM SuperRaiz', '4 litros', 325.00, 2, 'no', 90.00, 560.00),
(23, 41, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(24, 41, 'EM SuperSuelo', '4 litros', 120.00, 2, 'si', 240.00, 0.00),
(25, 42, 'EM SuperSuelo', '4 litros', 120.00, 5, 'no', 100.00, 500.00),
(26, 42, 'EM SuperRaiz', '1 litro', 90.00, 5, 'no', 75.00, 375.00),
(27, 42, 'EM SuperFoliar', '1 litro', 170.00, 3, 'no', 120.00, 390.00),
(28, 43, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(29, 43, 'EM SuperSuelo', '4 litros', 120.00, 2, 'si', 240.00, 0.00),
(30, 43, 'EM SuperSuelo', '20 litros', 550.00, 8, 'no', 800.00, 3600.00),
(31, 44, 'EM SuperSuelo', '20 litros', 550.00, 11, 'no', 1050.00, 5000.00),
(32, 45, 'EM SuperSuelo', '20 litros', 550.00, 22, 'no', 2100.00, 10000.00),
(33, 46, 'EM SuperSuelo', '20 litros', 550.00, 2, 'no', 200.00, 900.00),
(34, 46, 'EM SuperRaiz', '4 litros', 325.00, 5, 'no', 225.00, 1400.00),
(35, 47, 'EM SuperAgua', '1 litro', 30.00, 1, 'no', 0.00, 30.00),
(36, 48, 'EM SuperAgua', '4 litros', 110.00, 40, 'no', 0.00, 4400.00),
(37, 49, 'EM SuperSuelo', '20 litros', 550.00, 5, 'no', 500.00, 2250.00),
(38, 50, 'EM SuperRaiz', '1 litro', 90.00, 15, 'no', 225.00, 1125.00),
(39, 50, 'EM SuperFoliar', '1 litro', 170.00, 5, 'no', 225.00, 625.00),
(40, 51, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(41, 51, 'EM SuperSuelo', '4 litros', 120.00, 2, 'si', 0.00, 0.00),
(42, 51, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(43, 52, 'EM SuperSuelo', '20 litros', 550.00, 5, 'no', 125.00, 2625.00),
(44, 52, 'EM SuperRaiz', '4 litros', 325.00, 3, 'no', 15.00, 960.00),
(45, 52, 'EM SuperRaiz', '1 litro', 90.00, 8, 'no', 80.00, 640.00),
(46, 53, 'EM SuperRaiz', '20 litros', 1540.00, 1, 'no', 240.00, 1300.00),
(47, 54, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(49, 56, 'EM SuperFoliar', '1 litro', 170.00, 15, 'no', 675.00, 1875.00),
(50, 56, 'EM SuperRaiz', '1 litro', 90.00, 15, 'no', 225.00, 1125.00),
(51, 57, 'EM SuperSuelo', '4 litros', 120.00, 15, 'no', 300.00, 1500.00),
(52, 57, 'EM SuperRaiz', '1 litro', 90.00, 5, 'no', 75.00, 375.00),
(53, 58, 'EM SuperRaiz', '20 litros', 1540.00, 1, 'no', 140.00, 1400.00),
(54, 58, 'EM SuperFoliar', '4 litros', 660.00, 1, 'no', 120.00, 540.00),
(55, 59, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(56, 59, 'EM SuperSuelo', '4 litros', 120.00, 2, 'si', 0.00, 0.00),
(57, 59, 'EM SuperSuelo', '20 litros', 550.00, 4, 'no', 300.00, 1900.00),
(58, 59, 'EM SuperFoliar', '1 litro', 170.00, 5, 'no', 225.00, 625.00),
(59, 59, 'EM SuperRaiz', '1 litro', 90.00, 5, 'no', 50.00, 400.00),
(60, 60, 'EM SuperSuelo', '20 litros', 550.00, 5, 'no', 500.00, 2250.00),
(61, 61, 'EM SuperSuelo', '4 litros', 120.00, 5, 'no', 100.00, 500.00),
(62, 62, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(63, 62, 'EM SuperSuelo', '4 litros', 120.00, 2, 'si', 0.00, 0.00),
(64, 63, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(65, 63, 'EM SuperSuelo', '20 litros', 550.00, 6, 'no', 600.00, 2700.00),
(66, 63, 'EM SuperSuelo', '4 litros', 120.00, 2, 'si', 0.00, 0.00),
(67, 64, 'EM SuperSuelo', '20 litros', 550.00, 6, 'no', 450.00, 2850.00),
(68, 65, 'EM SuperSuelo', '20 litros', 550.00, 4, 'no', 400.00, 1800.00),
(69, 66, 'EM SuperRaiz', '20 litros', 1540.00, 1, 'no', 240.00, 1300.00),
(70, 67, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(71, 68, 'EM SuperSuelo', '20 litros', 550.00, 1, 'no', 0.00, 550.00),
(72, 68, 'EM SuperRaiz', '1 litro', 90.00, 1, 'no', 0.00, 90.00),
(73, 69, 'EM SuperFoliar', '1 litro', 170.00, 2, 'no', 340.00, 0.00),
(74, 69, 'EM SuperAnimal', '1 litro', 170.00, 1, 'no', 170.00, 0.00),
(75, 70, 'EM SuperSuelo', '4 litros', 120.00, 25, 'no', 500.00, 2500.00),
(76, 70, 'EM SuperSuelo', '4 litros', 120.00, 2, 'si', 0.00, 0.00),
(77, 70, 'EM SuperSuelo', '20 litros', 550.00, 6, 'no', 600.00, 2700.00),
(78, 71, 'EM SuperSuelo', '4 litros', 120.00, 10, 'no', 200.00, 1000.00),
(79, 72, 'EM SuperSuelo', '20 litros', 550.00, 1, 'no', 50.00, 500.00),
(80, 72, 'EM SuperRaiz', '1 litro', 90.00, 2, 'no', 20.00, 160.00),
(81, 73, 'EM SuperSuelo', '20 litros', 550.00, 11, 'no', 1050.00, 5000.00),
(82, 74, 'EM SuperSuelo', '20 litros', 550.00, 2, 'no', 50.00, 1050.00),
(83, 74, 'EM SuperRaiz', '4 litros', 325.00, 4, 'no', 20.00, 1280.00),
(84, 75, 'EM SuperSuelo', '20 litros', 550.00, 10, 'no', 1000.00, 4500.00),
(85, 76, 'EM SuperSuelo', '4 litros', 120.00, 5, 'no', 0.00, 600.00),
(86, 76, 'EM SuperFoliar', '1 litro', 170.00, 5, 'no', 0.00, 850.00),
(87, 76, 'EM SuperRaiz', '1 litro', 90.00, 5, 'no', 0.00, 450.00),
(88, 77, 'EM SuperSuelo', '20 litros', 550.00, 1, 'no', 0.00, 550.00),
(89, 77, 'EM SuperFoliar', '1 litro', 170.00, 1, 'no', 0.00, 170.00),
(90, 77, 'EM SuperRaiz', '1 litro', 90.00, 1, 'no', 0.00, 90.00),
(91, 77, 'EM SuperAgua', '4 litros', 110.00, 1, 'no', 0.00, 110.00),
(92, 78, 'EM SuperSuelo', '4 litros', 120.00, 10, 'no', 200.00, 1000.00),
(93, 78, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(94, 79, 'EM SuperSuelo', '20 litros', 550.00, 11, 'no', 1050.00, 5000.00),
(95, 79, 'EM SuperRaiz', '1 litro', 90.00, 4, 'no', 20.00, 340.00),
(96, 80, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(97, 81, 'EM SuperSuelo', '4 litros', 120.00, 2, 'no', 40.00, 200.00),
(98, 81, 'EM SuperCompost', '20 litros', 550.00, 1, 'no', 100.00, 450.00),
(99, 81, 'EM SuperCompost', '4 litros', 120.00, 2, 'no', 40.00, 200.00),
(100, 82, 'EM SuperSuelo', '20 litros', 550.00, 11, 'no', 1050.00, 5000.00),
(101, 83, 'EM SuperFoliar', '1 litro', 170.00, 12, 'no', 540.00, 1500.00),
(102, 84, 'EM SuperSuelo', '200 litros', 5000.00, 1, 'no', 0.00, 5000.00),
(103, 84, 'EM SuperAgua', '20 litros', 525.00, 2, 'no', 150.00, 900.00),
(104, 85, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(105, 85, 'EM SuperFoliar', '1 litro', 170.00, 10, 'no', 450.00, 1250.00),
(106, 86, 'EM SuperSuelo', '4 litros', 120.00, 6, 'no', 120.00, 600.00),
(107, 87, 'EM SuperAgua', '4 litros', 110.00, 25, 'no', 0.00, 2750.00),
(108, 88, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(109, 89, 'EM SuperSuelo', '20 litros', 550.00, 11, 'no', 1050.00, 5000.00),
(110, 90, 'EM SuperCompost', '20 litros', 550.00, 1, 'no', 0.00, 550.00),
(111, 90, 'EM SuperAgua', '20 litros', 525.00, 1, 'no', 0.00, 525.00),
(112, 91, 'EM SuperSuelo', '20 litros', 550.00, 11, 'no', 1250.00, 4800.00),
(113, 91, 'EM SuperRaiz', '1 litro', 90.00, 15, 'no', 225.00, 1125.00),
(114, 92, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(115, 92, 'EM SuperRaiz', '1 litro', 90.00, 3, 'no', 45.00, 225.00),
(116, 93, 'EMA', '20 litros', 480.00, 2, 'no', 60.00, 900.00),
(117, 94, 'EM SuperSuelo', '200 litros', 5000.00, 1, 'no', 0.00, 5000.00),
(118, 95, 'EM SuperSuelo', '20 litros', 550.00, 3, 'no', 300.00, 1350.00),
(119, 96, 'EM SuperSuelo', '20 litros', 550.00, 1, 'no', 100.00, 450.00),
(120, 96, 'EM SuperSuelo', '4 litros', 120.00, 5, 'no', 100.00, 500.00),
(121, 97, 'EM SuperSuelo', '20 litros', 550.00, 1, 'no', 100.00, 450.00),
(122, 97, 'EM SuperSuelo', '4 litros', 120.00, 5, 'no', 100.00, 500.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nota_envio`
--

CREATE TABLE `nota_envio` (
  `id` int NOT NULL,
  `numero_nota` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `vendedor` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int NOT NULL,
  `cliente_nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nit` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_venta` enum('Contado','Crédito','Pruebas','Bonificación') COLLATE utf8mb4_unicode_ci NOT NULL,
  `dias_credito` int DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `descuento_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `usuario_id` int NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nota_envio`
--

INSERT INTO `nota_envio` (`id`, `numero_nota`, `fecha`, `vendedor`, `cliente_id`, `cliente_nombre`, `nit`, `direccion`, `tipo_venta`, `dias_credito`, `subtotal`, `descuento_total`, `total`, `usuario_id`, `fecha_creacion`) VALUES
(2, '00002', '2026-01-02', 'Felipe Machán', 7, 'agropecuaria la cosecha', 'CF', 'calle la Alameda', 'Crédito', 30, 1200.00, 200.00, 1000.00, 5, '2026-01-23 18:43:35'),
(3, '00003', '2026-01-07', 'Felipe Machán', 8, 'Estela Ticun', 'CF', 'calle del cementerio', 'Contado', NULL, 6100.00, 1300.00, 4800.00, 5, '2026-01-23 19:10:27'),
(4, '00004', '2026-01-19', 'Felipe Machán', 9, 'Elvis Velasquez', 'CF', 'camino a chuito', 'Crédito', 30, 2700.00, 450.00, 2250.00, 5, '2026-01-23 22:31:05'),
(5, '00005', '2026-01-02', 'Felipe Machán', 10, 'Danilo Gómez', 'CF', 'La unión Barrios', 'Pruebas', NULL, 810.00, 145.00, 665.00, 5, '2026-01-24 06:55:06'),
(6, '00006', '2026-01-07', 'Felipe Machán', 11, 'Andrés Ticun', 'CF', 'camino al Cementerio', 'Pruebas', NULL, 3240.00, 740.00, 2500.00, 5, '2026-01-24 07:04:17'),
(7, '00007', '2026-01-09', 'Felipe Machán', 12, 'Sergio Roquel', 'CF', 'Aldea Rincón Grande', 'Contado', NULL, 1650.00, 225.00, 1425.00, 5, '2026-01-24 07:07:57'),
(8, '00008', '2026-01-13', 'Felipe Machán', 13, 'Edy Pérez', 'CF', 'Sector 5 Sajcavilla', 'Contado', NULL, 3000.00, 500.00, 2500.00, 5, '2026-01-24 07:14:03'),
(9, '00009', '2026-01-13', 'Felipe Machán', 14, 'José Pirir', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 17, 6050.00, 750.00, 5300.00, 5, '2026-01-24 07:20:37'),
(10, '00010', '2026-01-24', 'Felipe Machán', 15, 'Hugo Coronado Mauricio', 'CF', 'aldea Taltimiche', 'Contado', NULL, 120.00, 0.00, 120.00, 5, '2026-01-24 07:27:00'),
(36, '00011', '2026-01-13', 'Felipe Machán', 13, 'Edy Pérez', 'CF', 'Sector 5 Sajcavilla', 'Contado', NULL, 1100.00, 200.00, 900.00, 5, '2026-01-25 17:35:27'),
(37, '00012', '2026-01-16', 'Felipe Machán', 17, 'Aura Pérez', 'CF', 'Loma Alta', 'Contado', NULL, 1100.00, 200.00, 900.00, 5, '2026-01-25 17:47:25'),
(38, '00013', '2026-01-19', 'Felipe Machán', 21, 'Agroservicio El Chuluc', 'CF', 'El Chuluc Patzicia', 'Contado', NULL, 1350.00, 150.00, 1200.00, 5, '2026-01-25 17:51:00'),
(39, '00014', '2026-01-16', 'Felipe Machán', 19, 'Alfonso Boch', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Contado', NULL, 6050.00, 1250.00, 4800.00, 5, '2026-01-25 17:54:15'),
(40, '00015', '2026-01-23', 'Felipe Machán', 17, 'Aura Pérez', 'CF', 'Loma Alta', 'Contado', NULL, 650.00, 90.00, 560.00, 5, '2026-01-25 17:56:22'),
(41, '00016', '2026-01-16', 'Felipe Machán', 18, 'Marcos Díaz', 'CF', 'Cruz blanca San Juan Sacatepéquez', 'Bonificación', NULL, 3240.00, 740.00, 2500.00, 5, '2026-01-25 18:01:31'),
(42, '00017', '2026-01-16', 'Felipe Machán', 20, 'Mario Patzan', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Contado', NULL, 1560.00, 295.00, 1265.00, 5, '2026-01-25 18:05:27'),
(43, '00018', '2026-01-21', 'Felipe Machán', 8, 'Estela Ticun', 'CF', 'calle del cementerio', 'Contado', NULL, 7640.00, 1540.00, 6100.00, 5, '2026-01-25 18:10:07'),
(44, '00019', '2026-01-21', 'Felipe Machán', 22, 'Héctor Tuquer', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 6050.00, 1050.00, 5000.00, 5, '2026-01-25 21:35:02'),
(45, '00020', '2026-01-23', 'Felipe Machán', 22, 'Héctor Tuquer', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 12100.00, 2100.00, 10000.00, 5, '2026-01-25 21:36:43'),
(46, '00021', '2026-01-16', 'Felipe Machán', 16, 'Agrogenio', 'CF', 'Los García Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 2725.00, 425.00, 2300.00, 5, '2026-01-25 21:40:34'),
(47, '00022', '2026-01-27', 'Jurandir Terreaux', 14, 'José Pirir', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Pruebas', NULL, 30.00, 0.00, 30.00, 1, '2026-01-27 12:37:09'),
(48, '00023', '2026-01-29', 'Felipe Machán', 23, 'Industrial de Aceites y Grasas Suprema S.A', 'CF', '9a Avenida 19-61 Edificio Zenit, Nível 10, Zona 10 Guatemala.', 'Crédito', 45, 4400.00, 0.00, 4400.00, 5, '2026-01-29 23:45:41'),
(49, '00024', '2026-01-29', 'Felipe Machán', 8, 'Estela Ticun', 'CF', 'calle del cementerio', 'Crédito', 30, 2750.00, 500.00, 2250.00, 5, '2026-01-29 23:46:39'),
(50, '00025', '2026-02-02', 'Felipe Machán', 16, 'Agrogenio', 'CF', 'Los García Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 2200.00, 450.00, 1750.00, 5, '2026-02-02 21:55:38'),
(51, '00026', '2026-02-02', 'Felipe Machán', 17, 'Aura Pérez', 'CF', 'Loma Alta', 'Contado', NULL, 4650.00, 800.00, 3850.00, 5, '2026-02-02 22:04:28'),
(52, '00027', '2026-02-02', 'Felipe Machán', 24, 'cesar Tepet', 'CF', 'Joya de las Flores San Juan Sacatepéquez', 'Crédito', 26, 4445.00, 220.00, 4225.00, 5, '2026-02-02 22:12:40'),
(53, '00028', '2026-02-02', 'Felipe Machán', 22, 'Héctor Tuquer', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 1540.00, 240.00, 1300.00, 5, '2026-02-02 22:14:05'),
(54, '00029', '2026-02-06', 'Felipe Machán', 21, 'Agroservicio El Chuluc', 'CF', 'El Chuluc Patzicia', 'Contado', NULL, 1650.00, 300.00, 1350.00, 5, '2026-02-07 02:47:49'),
(56, '00031', '2026-02-06', 'Felipe Machán', 8, 'Estela Ticun', 'CF', 'calle del cementerio', 'Contado', NULL, 3900.00, 900.00, 3000.00, 5, '2026-02-07 03:09:03'),
(57, '00032', '2026-02-06', 'Felipe Machán', 25, 'Agrocampo', 'CF', 'Cruz Blanca Entrada al campo', 'Crédito', 22, 2250.00, 375.00, 1875.00, 5, '2026-02-07 03:32:29'),
(58, '00033', '2026-02-06', 'Felipe Machán', 26, 'Venancio Locon', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 22, 2200.00, 260.00, 1940.00, 5, '2026-02-07 03:35:13'),
(59, '00034', '2026-02-09', 'Felipe Machán', 27, 'Agroservicio Caleb', 'CF', 'Aldea Santa Marta Sumpango Sacatepéquez', 'Contado', NULL, 6500.00, 1075.00, 5425.00, 5, '2026-02-10 01:07:29'),
(60, '00035', '2026-02-17', 'Felipe Machán', 9, 'Elvis Velasquez', 'CF', 'camino a chuito', 'Crédito', 30, 2750.00, 500.00, 2250.00, 5, '2026-02-21 00:54:11'),
(61, '00036', '2026-02-18', 'Felipe Machán', 7, 'agropecuaria la cosecha', 'CF', 'calle la Alameda', 'Crédito', 30, 600.00, 100.00, 500.00, 5, '2026-02-21 00:55:31'),
(62, '00037', '2026-02-19', 'Felipe Machán', 28, 'Octavio Turuy', 'CF', 'Aldea el Rejón Sumpango Sacatepéquez', 'Contado', NULL, 3000.00, 500.00, 2500.00, 5, '2026-02-21 00:59:47'),
(63, '00038', '2026-02-20', 'Felipe Machán', 8, 'Estela Ticun', 'CF', 'calle del cementerio', 'Crédito', 30, 6300.00, 1100.00, 5200.00, 5, '2026-02-21 01:05:07'),
(64, '00039', '2026-02-20', 'Felipe Machán', 20, 'Mario Patzan', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Contado', NULL, 3300.00, 450.00, 2850.00, 5, '2026-02-21 01:06:19'),
(65, '00040', '2026-02-20', 'Felipe Machán', 13, 'Edy Pérez', 'CF', 'Sector 5 Sajcavilla', 'Contado', NULL, 2200.00, 400.00, 1800.00, 5, '2026-02-21 01:07:20'),
(66, '00041', '2026-02-20', 'Felipe Machán', 19, 'Alfonso Boch', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 1540.00, 240.00, 1300.00, 5, '2026-02-21 01:08:41'),
(67, '00042', '2026-02-20', 'Felipe Machán', 17, 'Aura Pérez', 'CF', 'Loma Alta', 'Crédito', 30, 1650.00, 300.00, 1350.00, 5, '2026-02-21 01:09:40'),
(68, '00043', '2026-02-20', 'Felipe Machán', 29, 'Héctor Carrascoza', 'CF', 'Barrio La Libertad a la par del Colegio Florencio Carrascoza, Joyabaj Quiché', 'Pruebas', NULL, 640.00, 0.00, 640.00, 5, '2026-02-21 01:20:51'),
(69, '00044', '2026-02-28', 'Felipe Machán', 29, 'Héctor Carrascoza', 'CF', 'Barrio La Libertad a la par del Colegio Florencio Carrascoza, Joyabaj Quiché', 'Pruebas', NULL, 510.00, 510.00, 0.00, 5, '2026-03-02 02:26:16'),
(70, '00045', '2026-03-02', 'Felipe Machán', 8, 'Estela Ticun', 'CF', 'calle del cementerio', 'Contado', NULL, 6300.00, 1100.00, 5200.00, 5, '2026-03-08 04:31:24'),
(71, '00046', '2026-03-02', 'Felipe Machán', 30, 'Federico Ticun', 'CF', 'Santa María Cauque', 'Crédito', 30, 1200.00, 200.00, 1000.00, 5, '2026-03-08 04:36:31'),
(72, '00047', '2026-03-03', 'Felipe Machán', 31, 'Luciano Alvarez', 'CF', 'Aldea Chimazat Santa Cruz Balanya', 'Crédito', 15, 730.00, 70.00, 660.00, 5, '2026-03-08 04:42:34'),
(73, '00048', '2026-03-04', 'Felipe Machán', 22, 'Héctor Tuquer', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 6050.00, 1050.00, 5000.00, 5, '2026-03-08 04:45:57'),
(74, '00049', '2026-03-07', 'Felipe Machán', 24, 'cesar Tepet', 'CF', 'Joya de las Flores San Juan Sacatepéquez', 'Crédito', 30, 2400.00, 70.00, 2330.00, 5, '2026-03-08 04:47:40'),
(75, '00050', '2026-03-05', 'Felipe Machán', 20, 'Mario Patzan', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 5500.00, 1000.00, 4500.00, 5, '2026-03-08 04:48:47'),
(76, '00051', '2026-03-07', 'Felipe Machán', 32, 'Ivonne Cooperativa 4 Pinos', 'CF', '4ta. Calle 5-01 Zona 1 Santiago Sacatepéquez', 'Pruebas', NULL, 1900.00, 0.00, 1900.00, 5, '2026-03-08 04:57:52'),
(77, '00052', '2026-03-03', 'Felipe Machán', 33, 'Universidad del Valle', 'CF', 'Km 137 Caserío Xolbe El Tablón Sololá', 'Pruebas', NULL, 920.00, 0.00, 920.00, 5, '2026-03-08 05:03:02'),
(78, '00053', '2026-03-05', 'Felipe Machán', 7, 'agropecuaria la cosecha', 'CF', 'calle la Alameda', 'Crédito', 30, 2850.00, 500.00, 2350.00, 5, '2026-03-13 02:54:31'),
(79, '00054', '2026-03-10', 'Felipe Machán', 19, 'Alfonso Boch', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 6410.00, 1070.00, 5340.00, 5, '2026-03-13 02:58:26'),
(80, '00055', '2026-03-10', 'Felipe Machán', 18, 'Marcos Díaz', 'CF', 'Cruz blanca San Juan Sacatepéquez', 'Crédito', 15, 1650.00, 300.00, 1350.00, 5, '2026-03-13 02:59:58'),
(81, '00056', '2026-03-12', 'Felipe Machán', 34, 'Maritza Rivas', 'CF', 'Km 58 Chimaltenango', 'Crédito', 10, 1030.00, 180.00, 850.00, 5, '2026-03-13 03:04:54'),
(82, '00057', '2026-03-18', 'Felipe Machán', 35, 'Óscar Tuquer', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 6050.00, 1050.00, 5000.00, 5, '2026-03-19 04:35:39'),
(83, '00058', '2026-03-18', 'Felipe Machán', 16, 'Agrogenio', 'CF', 'Los García Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 2040.00, 540.00, 1500.00, 5, '2026-03-19 04:37:47'),
(84, '00059', '2026-03-18', 'Felipe Machán', 22, 'Héctor Tuquer', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 6050.00, 150.00, 5900.00, 5, '2026-03-19 04:39:32'),
(85, '00060', '2026-03-18', 'Felipe Machán', 8, 'Estela Ticun', 'CF', 'calle del cementerio', 'Crédito', 30, 3350.00, 750.00, 2600.00, 5, '2026-03-19 04:41:01'),
(86, '00061', '2026-03-23', 'Felipe Machán', 36, 'Mario Tol', 'CF', 'Agua Escondida Tecpán Guatemala', 'Crédito', 15, 720.00, 120.00, 600.00, 5, '2026-03-24 05:04:25'),
(87, '00062', '2026-03-23', 'Felipe Machán', 23, 'Industrial de Aceites y Grasas Suprema S.A', 'CF', '9a Avenida 19-61 Edificio Zenit, Nível 10, Zona 10 Guatemala.', 'Crédito', 45, 2750.00, 0.00, 2750.00, 5, '2026-03-24 05:05:17'),
(88, '00063', '2026-03-26', 'Felipe Machán', 7, 'agropecuaria la cosecha', 'CF', 'calle la Alameda', 'Crédito', 30, 1650.00, 300.00, 1350.00, 5, '2026-03-27 05:25:25'),
(89, '00064', '2026-03-26', 'Felipe Machán', 22, 'Héctor Tuquer', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 6050.00, 1050.00, 5000.00, 5, '2026-03-27 05:26:33'),
(90, '00065', '2026-04-06', 'Felipe Machán', 38, 'Henry Constanza', 'CF', 'Escuintla', 'Crédito', 30, 1075.00, 0.00, 1075.00, 5, '2026-04-07 04:12:49'),
(91, '00066', '2026-04-06', 'Felipe Machán', 8, 'Estela Ticun', 'CF', 'calle del cementerio', 'Crédito', 30, 7400.00, 1475.00, 5925.00, 5, '2026-04-07 04:16:07'),
(92, '00067', '2026-04-06', 'Felipe Machán', 39, 'Marvin Similox', 'CF', 'San Juan Comalapa', 'Crédito', 30, 1920.00, 345.00, 1575.00, 5, '2026-04-07 04:20:51'),
(93, '00068', '2026-04-07', 'Felipe Machán', 41, 'Walter Giron', 'CF', 'Resistenciales Valle del Sol Km 58.5 camino a la Antigua Guatemala', 'Crédito', 30, 960.00, 60.00, 900.00, 5, '2026-04-08 04:42:31'),
(94, '00069', '2026-04-07', 'Felipe Machán', 22, 'Héctor Tuquer', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 5000.00, 0.00, 5000.00, 5, '2026-04-08 04:43:44'),
(95, '00070', '2026-04-07', 'Felipe Machán', 18, 'Marcos Díaz', 'CF', 'Cruz blanca San Juan Sacatepéquez', 'Crédito', 30, 1650.00, 300.00, 1350.00, 5, '2026-04-08 04:44:57'),
(96, '00071', '2026-04-07', 'Felipe Machán', 40, 'Pablo Subuyuj', 'CF', 'Los pingüinos Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 1150.00, 200.00, 950.00, 5, '2026-04-08 04:46:09'),
(97, '00072', '2026-04-07', 'Felipe Machán', 19, 'Alfonso Boch', 'CF', 'Loma Alta San Juan Sacatepéquez', 'Crédito', 30, 1150.00, 200.00, 950.00, 5, '2026-04-08 04:47:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nueva_venta`
--

CREATE TABLE `nueva_venta` (
  `id` int NOT NULL,
  `fecha` date NOT NULL,
  `vendedor` enum('Felipe Machán','Jurandir Terreaux','Vinicio Arreaga') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int NOT NULL,
  `nit` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Copiado del cliente',
  `direccion` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Copiado del cliente',
  `tipo_venta` enum('Contado','Crédito','Pruebas') COLLATE utf8mb4_unicode_ci NOT NULL,
  `dias_credito` int DEFAULT NULL COMMENT 'Solo si tipo_venta = Crédito',
  `producto` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `presentacion` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL COMMENT 'Precio según producto+presentación',
  `cantidad` int NOT NULL,
  `descuento` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL COMMENT 'Calculado: (precio_unitario * cantidad) - descuento',
  `usuario_id` int NOT NULL COMMENT 'Usuario que registró la venta',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nueva_venta`
--

INSERT INTO `nueva_venta` (`id`, `fecha`, `vendedor`, `cliente_id`, `nit`, `direccion`, `tipo_venta`, `dias_credito`, `producto`, `presentacion`, `precio_unitario`, `cantidad`, `descuento`, `total`, `usuario_id`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, '2026-01-20', 'Felipe Machán', 1, '11652646-7', 'Zona 1, 5ta Avenida 10-20', 'Contado', NULL, 'EM1', '1 litro', 150.00, 10, 0.00, 1500.00, 1, '2026-01-20 20:39:23', '2026-01-20 20:39:23'),
(2, '2026-01-20', 'Jurandir Terreaux', 1, '11652646-7', 'Zona 1, 5ta Avenida 10-20', 'Crédito', 30, 'EMA', '20 litros', 480.00, 5, 50.00, 2350.00, 1, '2026-01-20 20:39:23', '2026-01-20 20:39:23'),
(4, '2026-01-22', 'Vinicio Arreaga', 3, '98765432-1', 'Barrio El Centro, 3ra Calle 8-45', 'Contado', NULL, 'EM SuperAnimal', '1 litro', 170.00, 2, 50.00, 290.00, 1, '2026-01-22 20:24:31', '2026-01-22 20:24:31'),
(5, '2026-01-23', 'Felipe Machán', 3, '98765432-1', 'Barrio El Centro, 3ra Calle 8-45', 'Crédito', 5, 'EM SuperCompost', '20 litros', 550.00, 10, 250.00, 5250.00, 1, '2026-01-23 17:43:11', '2026-01-23 17:43:11'),
(6, '2026-01-23', 'Felipe Machán', 3, '98765432-1', 'Barrio El Centro, 3ra Calle 8-45', 'Pruebas', NULL, 'EM SuperSuelo', '4 litros', 120.00, 1, 0.00, 120.00, 1, '2026-01-23 17:47:15', '2026-01-23 17:47:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int NOT NULL,
  `factura_id` int NOT NULL,
  `fecha_pago` date NOT NULL,
  `banco` enum('Banco G&T Continental','Banco Industrial','BAC Credomatic','Banrural','Bantrab') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto_pago` decimal(10,2) NOT NULL,
  `referencia_transaccion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `factura_id`, `fecha_pago`, `banco`, `monto_pago`, `referencia_transaccion`, `usuario_id`, `fecha_creacion`) VALUES
(1, 9, '2026-02-04', 'Banco Industrial', 5000.00, '095329', 5, '2026-02-04 19:28:18'),
(2, 9, '2026-02-06', 'Banco Industrial', 300.00, '018845', 5, '2026-02-07 02:42:00'),
(3, 49, '2026-02-09', 'Banco Industrial', 2250.00, '20012161', 5, '2026-02-10 01:00:03'),
(4, 2, '2026-02-09', 'Banco Industrial', 1000.00, '20012162', 5, '2026-02-10 01:01:20'),
(5, 46, '2026-02-20', 'Banco Industrial', 2300.00, '076326', 5, '2026-02-21 00:50:02'),
(6, 44, '2026-02-20', 'Banco Industrial', 5000.00, '085045', 5, '2026-02-21 00:51:33'),
(7, 63, '2026-02-23', 'Banco Industrial', 5200.00, '062867- 097657', 5, '2026-02-23 18:40:59'),
(8, 66, '2026-02-27', 'Banco Industrial', 1300.00, '064848', 5, '2026-02-27 23:32:56'),
(9, 67, '2026-02-27', 'Banco Industrial', 1350.00, '064848', 5, '2026-02-27 23:33:27'),
(10, 61, '2026-02-27', 'Banco Industrial', 500.00, '064848', 5, '2026-02-27 23:33:52'),
(11, 58, '2026-02-27', 'Banco Industrial', 1940.00, '064848', 5, '2026-02-27 23:34:31'),
(12, 53, '2026-02-27', 'Banco Industrial', 1300.00, '064848', 5, '2026-02-27 23:35:26'),
(13, 4, '2026-02-27', 'Banco Industrial', 2250.00, '064848', 5, '2026-02-27 23:36:15'),
(14, 50, '2026-03-01', 'Banco Industrial', 1750.00, '20018417', 5, '2026-03-02 02:21:16'),
(15, 45, '2026-03-01', 'Banco Industrial', 10000.00, '20018417', 5, '2026-03-02 02:22:32'),
(16, 52, '2026-03-01', 'Banco Industrial', 4225.00, '20018417', 5, '2026-03-02 02:23:17'),
(17, 57, '2026-03-20', 'Banco Industrial', 1875.00, '069280', 5, '2026-03-21 04:02:10'),
(18, 71, '2026-03-20', 'Banco Industrial', 1000.00, '069280', 5, '2026-03-21 04:03:19'),
(19, 75, '2026-03-20', 'Banco Industrial', 4500.00, '069280', 5, '2026-03-21 04:04:11'),
(20, 60, '2026-03-27', 'Banco Industrial', 2250.00, '042253', 5, '2026-03-27 21:21:34'),
(21, 72, '2026-03-27', 'Banco Industrial', 660.00, '042253', 5, '2026-03-27 21:22:11'),
(22, 79, '2026-03-27', 'Banco Industrial', 5340.00, '042253', 5, '2026-03-27 21:22:46'),
(23, 81, '2026-03-27', 'Banco Industrial', 850.00, '042253', 5, '2026-03-27 21:23:16'),
(24, 86, '2026-03-27', 'Banco Industrial', 600.00, '042253', 5, '2026-03-27 21:25:11'),
(25, 74, '2026-03-31', 'Banco Industrial', 2330.00, '001972', 5, '2026-04-01 01:24:14'),
(26, 80, '2026-03-31', 'Banco Industrial', 1350.00, '0566 - 030679', 5, '2026-04-01 01:25:23'),
(27, 85, '2026-03-31', 'Banco Industrial', 2600.00, '0566', 5, '2026-04-01 01:26:19'),
(28, 90, '2026-04-06', 'Banco Industrial', 1075.00, '062595', 5, '2026-04-07 04:21:52'),
(29, 91, '2026-04-06', 'Banco Industrial', 5925.00, '078680', 5, '2026-04-07 04:22:40'),
(30, 92, '2026-04-06', 'Banco Industrial', 1575.00, '022909', 5, '2026-04-07 04:23:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_precios`
--

CREATE TABLE `productos_precios` (
  `id` int NOT NULL,
  `producto` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `presentacion` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `cantidad` int NOT NULL DEFAULT '0',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos_precios`
--

INSERT INTO `productos_precios` (`id`, `producto`, `presentacion`, `precio`, `cantidad`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'EM1', '1 litro', 150.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(2, 'EM1', '4 litros', 540.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(3, 'EM1', '20 litros', 2400.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(4, 'EM1', '200 litros', 21000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(5, 'EM1', '1000 litros', 90000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(6, 'EMA', '1 litro', 30.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(7, 'EMA', '4 litros', 108.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(8, 'EMA', '20 litros', 480.00, 198, '2026-01-20 20:38:42', '2026-04-08 04:42:31'),
(9, 'EMA', '200 litros', 4200.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(10, 'EMA', '1000 litros', 17000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(11, 'EM SuperSuelo', '1 litro', 35.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(12, 'EM SuperSuelo', '4 litros', 120.00, 430, '2026-01-20 20:38:42', '2026-04-08 04:47:18'),
(13, 'EM SuperSuelo', '20 litros', 550.00, 905, '2026-01-20 20:38:42', '2026-04-08 04:47:18'),
(14, 'EM SuperSuelo', '200 litros', 5000.00, 198, '2026-01-20 20:38:42', '2026-04-08 04:43:44'),
(15, 'EM SuperSuelo', '1000 litros', 21000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(16, 'EM SuperAgua', '1 litro', 30.00, 999, '2026-01-20 20:38:42', '2026-01-27 12:37:09'),
(17, 'EM SuperAgua', '4 litros', 120.00, 134, '2026-01-20 20:38:42', '2026-04-07 14:04:53'),
(18, 'EM SuperAgua', '20 litros', 525.00, 197, '2026-01-20 20:38:42', '2026-04-07 04:12:49'),
(19, 'EM SuperAgua', '200 litros', 4800.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(20, 'EM SuperAgua', '1000 litros', 20000.00, 1000, '2026-01-20 20:38:42', '2026-01-25 17:17:57'),
(21, 'EM SuperRaiz', '1 litro', 90.00, 101, '2026-01-20 20:38:42', '2026-04-07 04:20:51'),
(22, 'EM SuperRaiz', '4 litros', 325.00, 186, '2026-01-20 20:38:42', '2026-03-08 04:47:40'),
(23, 'EM SuperRaiz', '20 litros', 1540.00, 197, '2026-01-20 20:38:42', '2026-02-21 01:08:41'),
(24, 'EM SuperRaiz', '200 litros', 14600.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(25, 'EM SuperFoliar', '1 litro', 170.00, 142, '2026-01-20 20:38:42', '2026-03-19 04:41:01'),
(26, 'EM SuperFoliar', '4 litros', 660.00, 199, '2026-01-20 20:38:42', '2026-02-07 03:35:13'),
(27, 'EM SuperFoliar', '20 litros', 3150.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(28, 'EM SuperFoliar', '200 litros', 30000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(29, 'EM SuperFruto', '1 litro', 170.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(30, 'EM SuperFruto', '4 litros', 660.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(31, 'EM SuperFruto', '20 litros', 3150.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(32, 'EM SuperFruto', '200 litros', 30000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(33, 'EM SuperCompost', '1 litro', 35.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(34, 'EM SuperCompost', '4 litros', 120.00, 198, '2026-01-20 20:38:42', '2026-03-13 03:04:54'),
(35, 'EM SuperCompost', '20 litros', 550.00, 198, '2026-01-20 20:38:42', '2026-04-07 04:12:49'),
(36, 'EM SuperCompost', '200 litros', 5000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(37, 'EM SuperCompost', '1000 litros', 21000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(38, 'EM SuperAnimal', '1 litro', 170.00, 199, '2026-01-20 20:38:42', '2026-03-02 02:26:16'),
(39, 'EM SuperAnimal', '4 litros', 660.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(40, 'EM SuperAnimal', '20 litros', 3150.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(41, 'EM SuperAnimal', '200 litros', 30000.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(42, 'EM SuperMelaza', '1 litro', 15.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(43, 'EM SuperMelaza', '4 litros', 54.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(44, 'EM SuperMelaza', '20 litros', 230.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(45, 'EM SuperMelaza', '200 litros', 1800.00, 200, '2026-01-20 20:38:42', '2026-01-25 17:28:01'),
(46, 'Prueba 1', '1 litro', 35.00, 200, '2026-01-20 22:33:14', '2026-01-25 17:28:01'),
(47, 'Prueba 1', '5 litros', 55.00, 200, '2026-01-21 01:09:59', '2026-01-25 17:28:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrasena` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','vendedor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vendedor',
  `estado` enum('activo','De Baja') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `contrasena`, `rol`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Administrador', 'admin', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'admin', 'activo', '2026-01-20 16:14:02', '2026-01-20 16:19:11'),
(2, 'Usuario Inactivo', 'inactivo', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'vendedor', 'activo', '2026-01-20 16:43:17', '2026-02-09 15:23:29'),
(3, 'Juan Vendedor', 'vendedor1', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'vendedor', 'De Baja', '2026-01-20 16:49:51', '2026-01-29 03:16:54'),
(5, 'Felipe machan', 'fmachan85', '$2y$10$rCMrseorUWHxZPU9WA31VOnOOnSzUoM0Lu9jCgzoZJ1AHYfCUdppu', 'admin', 'activo', '2026-01-23 18:25:42', '2026-01-25 17:19:17'),
(6, 'Jurandir Terreaux', 'JJTerreaux', '$2y$10$3ezrQ7uNBK5wl6WNPcxWiOnNVoDri0D9oEKtxy0E.a5hyVEgu1a/W', 'vendedor', 'activo', '2026-01-25 17:35:01', '2026-01-25 17:35:01');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nit` (`nit`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_departamento` (`departamento`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `detalle_nota_envio`
--
ALTER TABLE `detalle_nota_envio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nota_envio_id` (`nota_envio_id`);

--
-- Indices de la tabla `nota_envio`
--
ALTER TABLE `nota_envio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_nota` (`numero_nota`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_numero_nota` (`numero_nota`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `nueva_venta`
--
ALTER TABLE `nueva_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_vendedor` (`vendedor`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `idx_tipo_venta` (`tipo_venta`),
  ADD KEY `idx_producto` (`producto`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_factura_id` (`factura_id`),
  ADD KEY `idx_fecha_pago` (`fecha_pago`);

--
-- Indices de la tabla `productos_precios`
--
ALTER TABLE `productos_precios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_producto_presentacion` (`producto`,`presentacion`),
  ADD KEY `idx_producto` (`producto`);

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
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `detalle_nota_envio`
--
ALTER TABLE `detalle_nota_envio`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT de la tabla `nota_envio`
--
ALTER TABLE `nota_envio`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT de la tabla `nueva_venta`
--
ALTER TABLE `nueva_venta`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `productos_precios`
--
ALTER TABLE `productos_precios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `detalle_nota_envio`
--
ALTER TABLE `detalle_nota_envio`
  ADD CONSTRAINT `detalle_nota_envio_ibfk_1` FOREIGN KEY (`nota_envio_id`) REFERENCES `nota_envio` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `nota_envio`
--
ALTER TABLE `nota_envio`
  ADD CONSTRAINT `nota_envio_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `nota_envio_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `nueva_venta`
--
ALTER TABLE `nueva_venta`
  ADD CONSTRAINT `nueva_venta_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `nueva_venta_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `nota_envio` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
