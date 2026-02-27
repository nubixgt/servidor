-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 24-02-2026 a las 14:29:26
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
-- Base de datos: `Lotificacion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registros`
--

CREATE TABLE `registros` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono_americano` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `como_se_entero` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `registros`
--

INSERT INTO `registros` (`id`, `nombre`, `apellido`, `telefono`, `telefono_americano`, `como_se_entero`, `correo`, `comentario`, `usuario_id`, `fecha_registro`) VALUES
(1, 'Sabrina', 'Galvez', '+502 3035-7162', '', 'Vallas publicitarias', '', '', 1, '2025-11-20 15:53:08'),
(2, 'Joel', 'Torres', '', '+1 805-570-3810', 'Redes sociales', '', '', 2, '2025-11-20 17:06:19'),
(3, 'Daniel Antonio', 'Pineda Lopez', '+502 4811-0994', '', 'Redes sociales', '', '', 2, '2025-11-20 17:14:12'),
(4, 'TANIA', 'RODAS', '+502 5555-0007', '+1 786-413-0339', 'Redes sociales', 'vrodascardona@gmail.com', 'necesito info de 5 lotes', 2, '2025-11-21 15:26:24'),
(5, 'Fredy', 'Chacón', '+502 5696-5489', '', 'Vallas publicitarias', '', '', 1, '2025-11-21 22:42:50'),
(6, 'Pedro', 'Lopez', '+502 3292-7237', '', 'Por amigos', '', 'Prueba', 2, '2025-11-21 22:45:23'),
(7, 'victor', 'Rodas', '+502 5555-0007', '+1 786-413-0339', 'Por amigos', '', 'Prueba', 2, '2025-11-22 01:38:45'),
(8, 'ingrid', 'moran', '+502 5515-5170', '', 'Por amigos', '', 'Prueba Prueba', 2, '2025-11-22 01:39:13'),
(9, 'Tania', 'Rodas', '+502 3006-7911', '', 'Por amigos', '', 'Prueba', 2, '2025-11-22 01:40:53'),
(10, 'Cesar', 'Rodas', '+502 4740-7795', '', 'Por amigos', '', 'Prueba', 2, '2025-11-22 01:41:45'),
(11, 'Edson', 'Natareno', '+502 4631-6705', '', 'Por amigos', '', 'Prueba', 2, '2025-11-22 17:27:38'),
(12, 'Victor Manuel', 'Lopez  Hernandez', '+502 5730-9166', '', 'Redes sociales', '', '', 2, '2025-11-24 14:42:20'),
(13, 'José', 'Ortiz', '', '+1 360-451-6362', 'Redes sociales', '', '', 2, '2025-11-24 14:48:43'),
(14, 'Carlos David', 'Perez Gomez', '', '+1 559-551-6685', 'Redes sociales', '', '', 2, '2025-11-24 15:06:47'),
(15, 'Aura Teresa', 'Larios Girón', '+502 5203-2318', '', 'Redes sociales', '', '', 2, '2025-11-24 15:14:15'),
(16, 'Angelica', 'Caal', '+502 5201-5071', '', 'Redes sociales', '', '', 2, '2025-11-24 15:20:49'),
(17, 'Yoselin', 'Ortega', '+502 4004-8292', '', 'Redes sociales', '', '', 2, '2025-11-24 15:42:59'),
(18, 'Yoni', 'Torres', '+502 5483-2312', '', 'Redes sociales', '', '', 2, '2025-11-24 15:53:44'),
(19, 'Marlene', 'Herrera', '', '+1 214-625-0403', 'Redes sociales', 'mahrrera05@gamail.com', '', 2, '2025-11-24 16:05:25'),
(20, 'Marta', 'Simon', '+502 4136-8733', '', 'Redes sociales', '', '', 2, '2025-11-24 16:11:18'),
(21, 'Isaí', 'Ortiz', '', '+1 615-931-7895', 'Redes sociales', '', '', 2, '2025-11-24 16:20:50'),
(22, 'Amanda', 'Gramajo', '+502 3315-7320', '', 'Redes sociales', '', '', 2, '2025-11-24 16:29:46'),
(24, 'Glendy', 'Velásquez', '+502 4721-9716', '', 'Redes sociales', '', '', 2, '2025-11-24 16:36:27'),
(26, 'Erica', 'Lopez', '+502 5945-6060', '', 'Redes sociales', '', '', 2, '2025-11-24 17:38:53'),
(27, 'Gloria', 'Xitumul', '+502 5588-0777', '', 'Redes sociales', '', '', 2, '2025-11-24 17:48:48'),
(29, 'Jorge', 'Lopez Garcia', '+502 4075-5052', '', 'Redes sociales', '', '', 2, '2025-11-24 18:04:40'),
(30, 'Guillermo', 'Gracia', '+502 5567-7848', '', 'Redes sociales', '', '', 2, '2025-11-24 20:44:19'),
(31, 'Cesar Alexander', 'Toc', '', '+1 804-308-4485', 'Redes sociales', 'jdcracsaaak@gmail.com', 'el cliente está interesado en tres lotes necesita más información', 2, '2025-11-24 21:18:07'),
(32, 'Miguel', 'Lopez', '', '+1 615-972-9632', 'Redes sociales', '', '', 2, '2025-11-24 21:31:45'),
(33, 'Cesar', 'Turcios', '+502 3883-1819', '', 'Por amigos', '', '', 2, '2025-11-25 16:56:21'),
(34, 'Carmen', 'Chon', '+502 5554-2949', '', 'Redes sociales', '', '', 2, '2025-11-26 19:32:35'),
(35, 'Lourdes', 'Monzon', '', '+1 804-216-7081', 'Redes sociales', '', 'La clienta no atiende a la llamada por lo tanto se compartió un mensaje informativo via whastapp', 2, '2025-11-27 21:18:08'),
(36, 'Juan Jose', 'Lopez Juarez', '', '+1 804-877-5286', 'Redes sociales', '', 'El cliente agradece la informaciòn e indica que se comparta la información por medio de whatsapp debido a que se encuentra trabajando', 2, '2025-12-01 20:52:25'),
(37, 'Sabrina', 'Galvez', '+502 3035-7162', '', 'Redes sociales', '', '', 2, '2025-12-09 15:40:39'),
(38, 'Samuel', 'Lopez', '+502 4919-3821', '', 'Redes sociales', 'martinezamuel145@gmail.com', '', 2, '2025-12-09 16:50:22'),
(39, 'Lilian', 'Iboy', '+502 5328-8477', '', 'Redes sociales', '', 'necesita información sobre los lotes y precios', 2, '2025-12-10 21:58:01'),
(40, 'Oscar', 'Donis', '+502 5555-3199', '', 'Redes sociales', '', '', 2, '2025-12-11 14:35:49'),
(41, 'Sonia', 'Mendoza', '+502 4262-6750', '', 'Redes sociales', '', 'está interesada en saber precios y medidas de la lotificación', 2, '2025-12-11 17:52:14'),
(42, 'Jairo', 'Calo', '+502 3066-9034', '', 'Redes sociales', '', 'está interesado en más información acerca de los lotes y precios', 2, '2025-12-12 16:19:40'),
(43, 'Pedro', 'Lopez', '+502 3292-7237', '', 'Vallas publicitarias', 'oto@gmail.com', 'abc', 1, '2025-12-12 17:27:19'),
(44, 'Fredy', 'Chacón', '+502 5696-5489', '', 'Vallas publicitarias', 'n@n.com', '', 1, '2025-12-12 17:31:46'),
(45, 'Fredy', 'Chacón', '+502 5696-5489', '', 'Redes sociales', '', '', 1, '2025-12-12 17:33:13'),
(46, 'Fredy', 'Chacón', '+502 5696-5489', '', 'Redes sociales', '', '', 1, '2025-12-12 17:34:08'),
(47, 'Fredy', 'Chacón', '+502 5696-5489', '', 'Vallas publicitarias', '', '', 1, '2025-12-12 17:35:23'),
(48, 'Fredy', 'Chacón', '+502 5696-5489', '', 'Vallas publicitarias', '', '', 1, '2025-12-12 17:40:32'),
(49, 'Pedro', 'Lopez', '+502 3292-7237', '', 'Por amigos', '', '', 1, '2025-12-12 17:41:28'),
(50, 'Nelson', 'Cuxum', '', '+1 615-362-8486', 'Redes sociales', 'cuxumesturdo55@gmail.comes', 'está interesado en las medidas de los lotes y precios', 2, '2025-12-12 17:44:26'),
(51, 'Carlos', 'Morales', '+502 3676-6443', '', 'Redes sociales', '', 'Se le llamo al cliente para dar seguimiento a los lotes 195, 194 y 193 ya que el se encuentra interesado en los mismo. Se realiza la observación indicando que el cliente no responde a las llamadas realizadas ya que envia a buzón', 2, '2025-12-16 19:47:15'),
(52, 'Carlos', 'Morales', '+502 3676-6443', '', 'Redes sociales', '', 'Se le llamo al cliente para dar seguimiento a los lotes 195, 194 y 193 ya que el se encuentra interesado en los mismo. Se realiza la observación indicando que el cliente no responde a las llamadas realizadas ya que envia a buzón', 2, '2025-12-16 19:47:23'),
(53, 'Fernando', 'Balcarcel', '', '+1 804-873-4448', 'Redes sociales', '', 'Interesado en precios y medidas', 2, '2025-12-16 21:38:20'),
(54, 'Patricia', 'Ordoñez', '+502 5301-9592', '', 'Redes sociales', '', 'Solicito información via whatsapp y se compartió', 2, '2025-12-18 19:50:47'),
(55, 'Alexander', 'Gonzales', '+502 5123-2076', '', 'Redes sociales', '', 'Interesado en un lote', 2, '2025-12-19 15:32:48'),
(56, 'Rene', 'Sanchez', '+502 5610-9068', '', 'Redes sociales', '', 'el cliente esta interesado en adquirir un lote de 195,000', 2, '2025-12-22 16:47:41'),
(57, 'Luis', 'Canahui', '+502 5579-0335', '', 'Redes sociales', '', 'el cliente esta interesado en un lote el vendra el dia de mañna a ver los lotes', 2, '2025-12-27 16:34:46'),
(58, 'Evelin', 'Barrios', '+502 3240-7645', '', 'Por amigos', '', 'la clienta está interesada en un lote el lunes vendrá a verlos', 2, '2025-12-27 20:57:58'),
(59, 'Gustavo', 'Coz', '+502 5924-2047', '', 'Por amigos', '', 'el cliente está interesado en lote', 2, '2025-12-27 20:59:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento`
--

CREATE TABLE `seguimiento` (
  `id` int NOT NULL,
  `registro_id` int NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `seguimiento`
--

INSERT INTO `seguimiento` (`id`, `registro_id`, `comentario`, `usuario_id`, `fecha_creacion`) VALUES
(6, 2, 'Necesita información sobre precios, medidas de lotes y si serán financiados o al contado.', 2, '2025-11-20 17:10:54'),
(7, 3, 'Solicita información sobre precios de los lotes.', 2, '2025-11-20 17:18:11'),
(8, 10, 'Prueba', 2, '2025-11-22 01:42:51'),
(9, 10, 'Prueba 2', 2, '2025-11-22 01:43:07'),
(10, 10, 'Lote vendido', 2, '2025-11-22 01:43:23'),
(11, 11, 'se hizo llamada de seguimiento', 2, '2025-11-22 17:29:06'),
(12, 12, 'Necesita medidas y precios de los lotes.', 2, '2025-11-24 14:43:56'),
(13, 13, 'Necesita información sobre precios y medidas de los lotes.', 2, '2025-11-24 14:59:19'),
(14, 14, 'Necesita información sobre pagos, enganches y ubicación.', 2, '2025-11-24 15:08:54'),
(15, 15, 'Necesita información sobre precios y medidas de los lotes.', 2, '2025-11-24 15:19:01'),
(16, 16, 'Necesita información sobre pagos y medidas de los lotes.', 2, '2025-11-24 15:39:44'),
(17, 17, 'Necesita información sobre el precio de los lotes.', 2, '2025-11-24 15:55:06'),
(18, 18, 'Necesita información sobre el precio de los lotes.', 2, '2025-11-24 16:00:51'),
(19, 19, 'Necesita información sobre medidas y las facilidades de pago.', 2, '2025-11-24 16:07:47'),
(20, 20, 'Solicita información sobre el precio de los lotes.', 2, '2025-11-24 16:14:47'),
(21, 21, 'Necesita información sobre precios y medidas de los lotes.', 2, '2025-11-24 16:27:07'),
(24, 26, 'Solicita información sobre el precio de los lotes.', 2, '2025-11-24 17:40:13'),
(26, 29, 'Necesita información sobre el precio de los lotes.', 2, '2025-11-24 18:05:56'),
(27, 30, 'Necesita información sobre precios y medidas de los lotes.', 2, '2025-11-24 20:46:58'),
(28, 32, 'Necesita información sobre precios y medidas de los lotes.', 2, '2025-11-24 21:32:57'),
(29, 33, 'Necesita información sobre los precios y medidas de los lotes.', 2, '2025-11-25 16:58:17'),
(30, 34, 'Necesita información sobre precios y medidas de los lotes.', 2, '2025-11-26 19:33:56'),
(31, 35, 'Necesita información sobre precios y medidas de los lotes.', 2, '2025-11-27 21:19:05'),
(32, 9, 'prueba', 2, '2025-11-28 22:07:55'),
(33, 9, 'prueba 2', 2, '2025-11-28 22:08:14'),
(34, 6, 'prueba', 2, '2025-11-28 22:11:21'),
(35, 36, 'Necesita información sobre precios y medidas de los lotes.', 2, '2025-12-01 21:01:46'),
(36, 50, 'el cliente se comunico con un vendedor y indicando que papa aun no ha tenido respuesta el vendedor se comunicara hoy con el y si no el el dia de mañana se comunicara con nosotros.', 2, '2025-12-16 18:56:49'),
(37, 50, 'el cliente se comunico con un vendedor y indicando que papa aun no ha tenido respuesta el vendedor se comunicara hoy con el y si no el el dia de mañana se comunicara con nosotros.', 2, '2025-12-16 18:56:49'),
(38, 50, 'el cliente se comunico con un vendedor y indicando que papa aun no ha tenido respuesta el vendedor se comunicara hoy con el y si no el el dia de mañana se comunicara con nosotros.', 2, '2025-12-16 18:56:49'),
(39, 50, 'si contesto el cliente', 2, '2025-12-16 18:57:26'),
(40, 42, 'El cliente responde a la llamada e indica que se comparta la información via whastapp', 2, '2025-12-16 19:31:11'),
(41, 41, 'La clienta agradece la llamada e indica que se comparta la información por whastapp para no olvidar ningún detalle', 2, '2025-12-16 19:36:26'),
(42, 41, 'La clienta agradece la llamada e indica que se comparta la información por whastapp para no olvidar ningún detalle', 2, '2025-12-16 19:36:26'),
(43, 40, 'El cliente agradece la llamada y solicita poder compartir la información whastapp', 2, '2025-12-16 19:39:40'),
(44, 40, 'El cliente agradece la llamada y solicita poder compartir la información whastapp', 2, '2025-12-16 19:39:40'),
(45, 39, 'La cliente recibe la llamada e indica que se comparta la información por whastapp debido a que se encuentra trabajando.', 2, '2025-12-16 19:50:09'),
(46, 34, 'Agradece la información indicando que es su hija la interesada en adquirir un lote, se compartió via whatsapp la información', 2, '2025-12-16 20:35:23'),
(47, 33, 'Se compartió la información e indica que pronto vendra a la lotificaciòn', 2, '2025-12-16 20:38:13'),
(48, 33, 'Se compartió la información e indica que pronto vendra a la lotificaciòn', 2, '2025-12-16 20:38:13'),
(49, 32, 'El cliente no responde a la llamada, se llamara el dia de mañana.', 2, '2025-12-16 20:41:09'),
(50, 32, 'El cliente no responde a la llamada, se llamara el dia de mañana.', 2, '2025-12-16 20:41:09'),
(51, 31, 'El cliente no responde a la llamada, se llamara el dia de mañana', 2, '2025-12-16 20:43:57'),
(52, 31, 'El cliente no responde a la llamada, se llamara el dia de mañana', 2, '2025-12-16 20:43:57'),
(53, 30, 'El cliente indica que compro en otro, agradece la llamada', 2, '2025-12-16 20:46:48'),
(54, 29, 'Agradece la llamada, indica q', 2, '2025-12-16 20:54:49'),
(55, 29, 'Indica que vendra a conocer los lotes', 2, '2025-12-16 20:55:37'),
(56, 27, 'Se llamo y el numero no corresponde a doña Gloria Xitumul', 2, '2025-12-16 20:57:43'),
(57, 26, 'Agradece la llamada e indica que compartira la información con su esposo y estará visitándonos', 2, '2025-12-16 21:03:14'),
(58, 24, 'Agradece que la informaciòn se comparta por whatsapp, por lo cual se compartio', 2, '2025-12-16 21:06:03'),
(59, 24, 'Agradece que la informaciòn se comparta por whatsapp, por lo cual se compartio', 2, '2025-12-16 21:06:03'),
(60, 24, 'Agradece que la informaciòn se comparta por whatsapp, por lo cual se compartio', 2, '2025-12-16 21:06:03'),
(61, 22, 'No responde a la llamada, se llamara mañana', 2, '2025-12-16 21:09:00'),
(62, 21, 'No responde a la llamada, envia a buzòn, se llamara mañana.', 2, '2025-12-16 21:10:30'),
(63, 20, 'Agradece la información indicando que por el momento no cuenta con el enganche, indica que si se le da la oportunidad vendra en enero', 2, '2025-12-16 21:20:21'),
(64, 18, 'Agradece la informacion solicitando enviarla por whatsapp', 2, '2025-12-16 21:22:22'),
(65, 17, 'No responde, envia a buzón, se llamara mañana', 2, '2025-12-16 21:24:21'),
(66, 16, 'Agradece la llamada, solicitando una cotización misma que sera enviada via whatsapp', 2, '2025-12-16 21:28:48'),
(67, 16, 'Agradece la llamada, solicitando una cotización misma que sera enviada via whatsapp', 2, '2025-12-16 21:28:48'),
(68, 15, 'No contesto, envia a buzon se compartira información mañana.', 2, '2025-12-16 21:33:46'),
(69, 15, 'No contesto, envia a buzon se compartira información mañana.', 2, '2025-12-16 21:33:47'),
(70, 14, 'No contesto, rechaza la llamada, se llamara el dia de mañana', 2, '2025-12-16 21:35:16'),
(71, 13, 'No contesta, rechaza la llamada, se llamara el dia de mañana', 2, '2025-12-16 21:36:30'),
(72, 53, 'Solicito enviar la informacion por whatsapp', 2, '2025-12-16 21:43:00'),
(73, 53, 'Solicito enviar la informacion por whatsapp', 2, '2025-12-16 21:43:00'),
(74, 15, 'la clienta nos indica que nos visitara hoy en la oficina ya que esta interesada en un lote', 2, '2025-12-17 14:43:12'),
(75, 15, 'la clienta nos indica que nos visitara hoy en la oficina ya que esta interesada en un lote', 2, '2025-12-17 14:43:12'),
(76, 36, 'se le llamo al cliente y directamente al buzón se le llamara el dia de mañana', 2, '2025-12-17 16:42:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`, `nombre_completo`, `fecha_creacion`, `ultimo_acceso`, `activo`) VALUES
(1, 'admin', '$2y$10$vePN8//DTWAgUy/ZnopCauKfTD6SnANlS9wQX90FkPWWgS5hPCQRu', 'Administrador', '2025-11-17 11:46:20', '2026-02-24 14:03:44', 1),
(2, 'recepcion', '$2y$10$g5N4QIobAszP.OdLoKENX.E20rPqXF1lhFhG3DsCV5D.CizyHrBwu', 'Recepción', '2025-11-17 13:14:21', '2026-02-20 22:22:17', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `registros`
--
ALTER TABLE `registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registro_id` (`registro_id`),
  ADD KEY `usuario_id` (`usuario_id`);

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
-- AUTO_INCREMENT de la tabla `registros`
--
ALTER TABLE `registros`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT de la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `registros`
--
ALTER TABLE `registros`
  ADD CONSTRAINT `registros_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  ADD CONSTRAINT `seguimiento_ibfk_1` FOREIGN KEY (`registro_id`) REFERENCES `registros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seguimiento_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
