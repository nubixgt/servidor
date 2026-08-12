-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 12-08-2026 a las 12:36:59
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
-- Base de datos: `visionwe_CONADEA`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `icono` varchar(10) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen_path` varchar(300) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `icono`, `titulo`, `descripcion`, `imagen_path`, `created_at`, `updated_at`) VALUES
(1, '📘', 'Curso 1', 'Prueba 1 para saber si se crea correctamente el curso 1', 'uploads/cursos/1/imagen.jpg', '2026-08-10 15:09:22', '2026-08-10 15:09:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id`, `nombre`) VALUES
(1, 'Alta Verapaz'),
(2, 'Baja Verapaz'),
(3, 'Chimaltenango'),
(4, 'Chiquimula'),
(5, 'El Progreso'),
(6, 'Escuintla'),
(7, 'Guatemala'),
(8, 'Huehuetenango'),
(9, 'Izabal'),
(10, 'Jalapa'),
(11, 'Jutiapa'),
(12, 'Petén'),
(13, 'Quetzaltenango'),
(14, 'Quiché'),
(15, 'Retalhuleu'),
(16, 'Sacatepéquez'),
(17, 'San Marcos'),
(18, 'Santa Rosa'),
(19, 'Sololá'),
(20, 'Suchitepéquez'),
(21, 'Totonicapán'),
(22, 'Zacapa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lecciones`
--

CREATE TABLE `lecciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `orden` smallint(5) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `contenido` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `lecciones`
--

INSERT INTO `lecciones` (`id`, `curso_id`, `orden`, `titulo`, `contenido`) VALUES
(1, 1, 0, 'Leccion 1', 'Prueba para saber si la leccion 1 funciona'),
(2, 1, 1, 'Leccion 2', 'Prueba para saber si la leccion 2 carga correctamente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipios`
--

CREATE TABLE `municipios` (
  `id` int(10) UNSIGNED NOT NULL,
  `departamento_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `municipios`
--

INSERT INTO `municipios` (`id`, `departamento_id`, `nombre`) VALUES
(1, 1, 'Cahabón'),
(2, 1, 'Chahal'),
(3, 1, 'Chisec'),
(4, 1, 'Cobán'),
(5, 1, 'Fray Bartolomé de las Casas'),
(6, 1, 'Lanquín'),
(7, 1, 'Panzós'),
(8, 1, 'Raxruhá'),
(9, 1, 'San Cristóbal Verapaz'),
(10, 1, 'San Juan Chamelco'),
(11, 1, 'San Pedro Carchá'),
(12, 1, 'Santa Catalina la Tinta'),
(13, 1, 'Santa Cruz Verapaz'),
(14, 1, 'Senahú'),
(15, 1, 'Tactic'),
(16, 1, 'Tamahú'),
(17, 1, 'Tucurú'),
(18, 2, 'Cubulco'),
(19, 2, 'Granados'),
(20, 2, 'Purulhá'),
(21, 2, 'Rabinal'),
(22, 2, 'Salamá'),
(23, 2, 'San Jerónimo'),
(24, 2, 'San Miguel Chicaj'),
(25, 2, 'Santa Cruz el Chol'),
(26, 3, 'Acatenango'),
(27, 3, 'Chimaltenango'),
(28, 3, 'El Tejar'),
(29, 3, 'Parramos'),
(30, 3, 'Patzicía'),
(31, 3, 'Patzún'),
(32, 3, 'Pochuta'),
(33, 3, 'San Andrés Itzapa'),
(34, 3, 'San José Poaquíl'),
(35, 3, 'San Juan Comalapa'),
(36, 3, 'San Martín Jilotepeque'),
(37, 3, 'San Pedro Yepocapa'),
(38, 3, 'Santa Apolonia'),
(39, 3, 'Santa Cruz Balanyá'),
(40, 3, 'Tecpán Guatemala'),
(41, 3, 'Zaragoza'),
(42, 4, 'Camotán'),
(43, 4, 'Chiquimula'),
(44, 4, 'Concepción Las Minas'),
(45, 4, 'Esquipulas'),
(46, 4, 'Ipala'),
(47, 4, 'Jocotán'),
(48, 4, 'Olopa'),
(49, 4, 'Quetzaltepeque'),
(50, 4, 'San Jacinto'),
(51, 4, 'San José la Arada'),
(52, 4, 'San Juan Ermita'),
(53, 5, 'El Jícaro'),
(54, 5, 'Guastatoya'),
(55, 5, 'Morazán'),
(56, 5, 'San Agustín Acasaguastlán'),
(57, 5, 'San Antonio La Paz'),
(58, 5, 'San Cristóbal Acasaguastlán'),
(59, 5, 'Sanarate'),
(60, 5, 'Sansare'),
(61, 6, 'Escuintla'),
(62, 6, 'Guanagazapa'),
(63, 6, 'Iztapa'),
(64, 6, 'La Democracia'),
(65, 6, 'La Gomera'),
(66, 6, 'Masagua'),
(67, 6, 'Nueva Concepción'),
(68, 6, 'Palín'),
(69, 6, 'San José'),
(70, 6, 'San Vicente Pacaya'),
(71, 6, 'Santa Lucía Cotzumalguapa'),
(72, 6, 'Sipacate'),
(73, 6, 'Siquinalá'),
(74, 6, 'Tiquisate'),
(75, 7, 'Amatitlán'),
(76, 7, 'Chinautla'),
(77, 7, 'Chuarrancho'),
(78, 7, 'Fraijanes'),
(79, 7, 'Guatemala'),
(80, 7, 'Mixco'),
(81, 7, 'Palencia'),
(82, 7, 'San José del Golfo'),
(83, 7, 'San José Pinula'),
(84, 7, 'San Juan Sacatepéquez'),
(85, 7, 'San Miguel Petapa'),
(86, 7, 'San Pedro Ayampuc'),
(87, 7, 'San Pedro Sacatepéquez'),
(88, 7, 'San Raymundo'),
(89, 7, 'Santa Catarina Pinula'),
(90, 7, 'Villa Canales'),
(91, 7, 'Villa Nueva'),
(92, 8, 'Aguacatán'),
(93, 8, 'Chiantla'),
(94, 8, 'Colotenango'),
(95, 8, 'Concepción Huista'),
(96, 8, 'Cuilco'),
(97, 8, 'Huehuetenango'),
(98, 8, 'Jacaltenango'),
(99, 8, 'La Democracia'),
(100, 8, 'La Libertad'),
(101, 8, 'Malacatancito'),
(102, 8, 'Nentón'),
(103, 8, 'Petatán'),
(104, 8, 'San Antonio Huista'),
(105, 8, 'San Gaspar Ixchil'),
(106, 8, 'San Ildefonso Ixtahuacán'),
(107, 8, 'San Juan Atitán'),
(108, 8, 'San Juan Ixcoy'),
(109, 8, 'San Mateo Ixtatán'),
(110, 8, 'San Miguel Acatán'),
(111, 8, 'San Pedro Nécta'),
(112, 8, 'San Pedro Soloma'),
(113, 8, 'San Rafael La Independencia'),
(114, 8, 'San Rafael Pétzal'),
(115, 8, 'San Sebastián Coatán'),
(116, 8, 'San Sebastián Huehuetenango'),
(117, 8, 'Santa Ana Huista'),
(118, 8, 'Santa Bárbara'),
(119, 8, 'Santa Cruz Barillas'),
(120, 8, 'Santa Eulalia'),
(121, 8, 'Santiago Chimaltenango'),
(122, 8, 'Tectitán'),
(123, 8, 'Todos Santos Cuchumatán'),
(124, 8, 'Unión Cantinil'),
(125, 9, 'El Estor'),
(126, 9, 'Livingston'),
(127, 9, 'Los Amates'),
(128, 9, 'Morales'),
(129, 9, 'Puerto Barrios'),
(130, 10, 'Jalapa'),
(131, 10, 'Mataquescuintla'),
(132, 10, 'Monjas'),
(133, 10, 'San Carlos Alzatate'),
(134, 10, 'San Luis Jilotepeque'),
(135, 10, 'San Manuel Chaparrón'),
(136, 10, 'San Pedro Pinula'),
(137, 11, 'Agua Blanca'),
(138, 11, 'Asunción Mita'),
(139, 11, 'Atescatempa'),
(140, 11, 'Comapa'),
(141, 11, 'Conguaco'),
(142, 11, 'El Adelanto'),
(143, 11, 'El Progreso'),
(144, 11, 'Jalpatagua'),
(145, 11, 'Jerez'),
(146, 11, 'Jutiapa'),
(147, 11, 'Moyuta'),
(148, 11, 'Pasaco'),
(149, 11, 'Quesada'),
(150, 11, 'San José Acatempa'),
(151, 11, 'Santa Catarina Mita'),
(152, 11, 'Yupiltepeque'),
(153, 11, 'Zapotitlán'),
(154, 12, 'Dolores'),
(155, 12, 'El Chal'),
(156, 12, 'Flores'),
(157, 12, 'La Libertad'),
(158, 12, 'Las Cruces'),
(159, 12, 'Melchor de Mencos'),
(160, 12, 'Poptún'),
(161, 12, 'San Andrés'),
(162, 12, 'San Benito'),
(163, 12, 'San Francisco'),
(164, 12, 'San José'),
(165, 12, 'San Luis'),
(166, 12, 'Santa Ana'),
(167, 12, 'Sayaxché'),
(168, 13, 'Almolonga'),
(169, 13, 'Cabricán'),
(170, 13, 'Cajolá'),
(171, 13, 'Cantel'),
(172, 13, 'Coatepeque'),
(173, 13, 'Colomba'),
(174, 13, 'Concepción Chiquirichapa'),
(175, 13, 'El Palmar'),
(176, 13, 'Flores Costa Cuca'),
(177, 13, 'Génova'),
(178, 13, 'Huitán'),
(179, 13, 'La Esperanza'),
(180, 13, 'Olintepeque'),
(181, 13, 'Palestina de Los Altos'),
(182, 13, 'Quetzaltenango'),
(183, 13, 'Salcajá'),
(184, 13, 'San Carlos Sija'),
(185, 13, 'San Francisco La Unión'),
(186, 13, 'San Juan Ostuncalco'),
(187, 13, 'San Martín Sacatepéquez'),
(188, 13, 'San Mateo'),
(189, 13, 'San Miguel Sigüilá'),
(190, 13, 'Sibilia'),
(191, 13, 'Zunil'),
(192, 14, 'Canillá'),
(193, 14, 'Chajul'),
(194, 14, 'Chicamán'),
(195, 14, 'Chiché'),
(196, 14, 'Chinique'),
(197, 14, 'Cunén'),
(198, 14, 'Ixcán'),
(199, 14, 'Joyabaj'),
(200, 14, 'Nebaj'),
(201, 14, 'Pachalum'),
(202, 14, 'Patzité'),
(203, 14, 'Sacapulas'),
(204, 14, 'San Andrés Sajcabajá'),
(205, 14, 'San Antonio Ilotenango'),
(206, 14, 'San Bartolomé Jocotenango'),
(207, 14, 'San Juan Cotzal'),
(208, 14, 'San Pedro Jocopilas'),
(209, 14, 'Santa Cruz del Quiché'),
(210, 14, 'Santo Tomás Chichicastenango'),
(211, 14, 'Uspantán'),
(212, 14, 'Zacualpa'),
(213, 15, 'Champerico'),
(214, 15, 'El Asintal'),
(215, 15, 'Nuevo San Carlos'),
(216, 15, 'Retalhuleu'),
(217, 15, 'San Andrés Villa Seca'),
(218, 15, 'San Felipe'),
(219, 15, 'San Martín Zapotitlán'),
(220, 15, 'San Sebastián'),
(221, 15, 'Santa Cruz Muluá'),
(222, 16, 'Alotenango'),
(223, 16, 'Antigua Guatemala'),
(224, 16, 'Ciudad Vieja'),
(225, 16, 'Jocotenango'),
(226, 16, 'Magdalena Milpas Altas'),
(227, 16, 'Pastores'),
(228, 16, 'San Antonio Aguas Calientes'),
(229, 16, 'San Bartolomé Milpas Altas'),
(230, 16, 'San Lucas Sacatepéquez'),
(231, 16, 'San Miguel Dueñas'),
(232, 16, 'Santa Catarina Barahona'),
(233, 16, 'Santa Lucía Milpas Altas'),
(234, 16, 'Santa María de Jesús'),
(235, 16, 'Santiago Sacatepéquez'),
(236, 16, 'Santo Domingo Xenacoj'),
(237, 16, 'Sumpango'),
(238, 17, 'Ayutla'),
(239, 17, 'Catarina'),
(240, 17, 'Comitancillo'),
(241, 17, 'Concepción Tutuapa'),
(242, 17, 'El Quetzal'),
(243, 17, 'El Tumbador'),
(244, 17, 'Esquipulas Palo Gordo'),
(245, 17, 'Ixchiguán'),
(246, 17, 'La Blanca'),
(247, 17, 'La Reforma'),
(248, 17, 'Malacatán'),
(249, 17, 'Nuevo Progreso'),
(250, 17, 'Ocós'),
(251, 17, 'Pajapita'),
(252, 17, 'Río Blanco'),
(253, 17, 'San Antonio Sacatepéquez'),
(254, 17, 'San Cristóbal Cucho'),
(255, 17, 'San José El Rodeo'),
(256, 17, 'San José Ojetenam'),
(257, 17, 'San Lorenzo'),
(258, 17, 'San Marcos'),
(259, 17, 'San Miguel Ixtahuacán'),
(260, 17, 'San Pablo'),
(261, 17, 'San Pedro Sacatepéquez'),
(262, 17, 'San Rafael Pie de la Cuesta'),
(263, 17, 'Sibinal'),
(264, 17, 'Sipacapa'),
(265, 17, 'Tacaná'),
(266, 17, 'Tajumulco'),
(267, 17, 'Tejutla'),
(268, 18, 'Barberena'),
(269, 18, 'Casillas'),
(270, 18, 'Chiquimulilla'),
(271, 18, 'Cuilapa'),
(272, 18, 'Guazacapán'),
(273, 18, 'Nueva Santa Rosa'),
(274, 18, 'Oratorio'),
(275, 18, 'Pueblo Nuevo Viñas'),
(276, 18, 'San Juan Tecuaco'),
(277, 18, 'San Rafael las Flores'),
(278, 18, 'Santa Cruz Naranjo'),
(279, 18, 'Santa María Ixhuatán'),
(280, 18, 'Santa Rosa de Lima'),
(281, 18, 'Taxisco'),
(282, 19, 'Concepción'),
(283, 19, 'Nahualá'),
(284, 19, 'Panajachel'),
(285, 19, 'San Andrés Semetabaj'),
(286, 19, 'San Antonio Palopó'),
(287, 19, 'San José Chacayá'),
(288, 19, 'San Juan La Laguna'),
(289, 19, 'San Lucas Tolimán'),
(290, 19, 'San Marcos La Laguna'),
(291, 19, 'San Pablo La Laguna'),
(292, 19, 'San Pedro La Laguna'),
(293, 19, 'Santa Catarina Ixtahuacán'),
(294, 19, 'Santa Catarina Palopó'),
(295, 19, 'Santa Clara La Laguna'),
(296, 19, 'Santa Cruz La Laguna'),
(297, 19, 'Santa Lucía Utatlán'),
(298, 19, 'Santa María Visitación'),
(299, 19, 'Santiago Atitlán'),
(300, 19, 'Sololá'),
(301, 20, 'Chicacao'),
(302, 20, 'Cuyotenango'),
(303, 20, 'Mazatenango'),
(304, 20, 'Patulul'),
(305, 20, 'Pueblo Nuevo'),
(306, 20, 'Río Bravo'),
(307, 20, 'Samayac'),
(308, 20, 'San Antonio Suchitepéquez'),
(309, 20, 'San Bernardino'),
(310, 20, 'San Francisco Zapotitlán'),
(311, 20, 'San Gabriel'),
(312, 20, 'San José El Ídolo'),
(313, 20, 'San José La Máquina'),
(314, 20, 'San Juan Bautista'),
(315, 20, 'San Lorenzo'),
(316, 20, 'San Miguel Panán'),
(317, 20, 'San Pablo Jocopilas'),
(318, 20, 'Santa Bárbara'),
(319, 20, 'Santo Domingo Suchitepéquez'),
(320, 20, 'Santo Tomás La Unión'),
(321, 20, 'Zunilito'),
(322, 21, 'Momostenango'),
(323, 21, 'San Andrés Xecul'),
(324, 21, 'San Bartolo'),
(325, 21, 'San Cristóbal Totonicapán'),
(326, 21, 'San Francisco El Alto'),
(327, 21, 'Santa Lucía La Reforma'),
(328, 21, 'Santa María Chiquimula'),
(329, 21, 'Totonicapán'),
(330, 22, 'Cabañas'),
(331, 22, 'Estanzuela'),
(332, 22, 'Gualán'),
(333, 22, 'Huité'),
(334, 22, 'La Unión'),
(335, 22, 'Río Hondo'),
(336, 22, 'San Diego'),
(337, 22, 'San Jorge'),
(338, 22, 'Teculután'),
(339, 22, 'Usumatlán'),
(340, 22, 'Zacapa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quiz_opciones`
--

CREATE TABLE `quiz_opciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `pregunta_id` int(10) UNSIGNED NOT NULL,
  `orden` smallint(5) UNSIGNED NOT NULL,
  `texto` varchar(300) NOT NULL,
  `es_correcta` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `quiz_opciones`
--

INSERT INTO `quiz_opciones` (`id`, `pregunta_id`, `orden`, `texto`, `es_correcta`) VALUES
(1, 1, 0, 'Opcion 1', 0),
(2, 1, 1, 'Opcion 2', 0),
(3, 1, 2, 'Opcion 3', 1),
(4, 1, 3, 'Opcion 4', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quiz_preguntas`
--

CREATE TABLE `quiz_preguntas` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `orden` smallint(5) UNSIGNED NOT NULL,
  `pregunta` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `quiz_preguntas`
--

INSERT INTO `quiz_preguntas` (`id`, `curso_id`, `orden`, `pregunta`) VALUES
(1, 1, 0, 'Preugnta 1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Supervisor'),
(3, 'Usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `departamento_id` int(10) UNSIGNED NOT NULL,
  `municipio_id` int(10) UNSIGNED NOT NULL,
  `rol_id` int(10) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `usuario`, `password_hash`, `telefono`, `departamento_id`, `municipio_id`, `rol_id`, `activo`, `created_at`, `updated_at`) VALUES
(2, 'Administrador', 'admin', '$2y$10$kJGSPv.l3jNcnsIZTzOMNO.H7CACg4D1N241uKYrMMm63NP33zeYK', '32927237', 7, 79, 3, 1, '2026-08-10 13:42:59', '2026-08-10 13:42:59'),
(3, 'Administrador 1', 'admin1', '$2y$10$x5.RxfDH6pItx4k09RvTu.e0SSHz8n8iWRJIRJ2ucfZNZkoEy7jcq', '78942563', 7, 79, 1, 1, '2026-08-10 14:45:49', '2026-08-10 14:45:49');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_departamentos_nombre` (`nombre`);

--
-- Indices de la tabla `lecciones`
--
ALTER TABLE `lecciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lecciones_curso` (`curso_id`);

--
-- Indices de la tabla `municipios`
--
ALTER TABLE `municipios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_municipios_depto_nombre` (`departamento_id`,`nombre`),
  ADD KEY `idx_municipios_departamento` (`departamento_id`);

--
-- Indices de la tabla `quiz_opciones`
--
ALTER TABLE `quiz_opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_opciones_pregunta` (`pregunta_id`);

--
-- Indices de la tabla `quiz_preguntas`
--
ALTER TABLE `quiz_preguntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_preguntas_curso` (`curso_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuarios_usuario` (`usuario`),
  ADD UNIQUE KEY `uq_usuarios_telefono` (`telefono`),
  ADD KEY `idx_usuarios_departamento` (`departamento_id`),
  ADD KEY `idx_usuarios_municipio` (`municipio_id`),
  ADD KEY `idx_usuarios_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `lecciones`
--
ALTER TABLE `lecciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `municipios`
--
ALTER TABLE `municipios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=341;

--
-- AUTO_INCREMENT de la tabla `quiz_opciones`
--
ALTER TABLE `quiz_opciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `quiz_preguntas`
--
ALTER TABLE `quiz_preguntas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `lecciones`
--
ALTER TABLE `lecciones`
  ADD CONSTRAINT `fk_lecciones_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `municipios`
--
ALTER TABLE `municipios`
  ADD CONSTRAINT `fk_municipios_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `quiz_opciones`
--
ALTER TABLE `quiz_opciones`
  ADD CONSTRAINT `fk_quiz_opciones_pregunta` FOREIGN KEY (`pregunta_id`) REFERENCES `quiz_preguntas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `quiz_preguntas`
--
ALTER TABLE `quiz_preguntas`
  ADD CONSTRAINT `fk_quiz_preguntas_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_municipio` FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
