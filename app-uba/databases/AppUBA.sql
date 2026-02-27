-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 24-02-2026 a las 14:00:29
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
-- Base de datos: `AppUBA`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_seguimiento`
--

CREATE TABLE `archivos_seguimiento` (
  `id_archivo` int NOT NULL,
  `id_seguimiento` int NOT NULL,
  `tipo_archivo` enum('imagen','documento','audio','video') COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tamano_bytes` int NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `archivos_seguimiento`
--

INSERT INTO `archivos_seguimiento` (`id_archivo`, `id_seguimiento`, `tipo_archivo`, `nombre_archivo`, `ruta_archivo`, `tamano_bytes`, `fecha_subida`) VALUES
(1, 1, 'imagen', 'perfil-icon.png', 'uploads/seguimiento/seguimiento_1_694ac0ae88cad_1766506670.png', 30848, '2025-12-23 16:17:50'),
(2, 2, 'imagen', 'images.jpg', 'uploads/seguimiento/seguimiento_2_694b03fb46ae0_1766523899.jpg', 5377, '2025-12-23 21:04:59'),
(3, 6, 'imagen', 'buyer-persona-branding.png', 'uploads/seguimiento/seguimiento_6_694b05cf5b73e_1766524367.png', 415928, '2025-12-23 21:12:47'),
(4, 7, 'imagen', 'Logo Ceiba-2.png', 'uploads/seguimiento/seguimiento_7_694b069d1dc82_1766524573.png', 220896, '2025-12-23 21:16:13'),
(5, 8, 'imagen', 'login-background.png', 'uploads/seguimiento/seguimiento_8_694b10ba68653_1766527162.png', 582829, '2025-12-23 21:59:22'),
(6, 9, 'imagen', 'Fondo.jpg', 'uploads/seguimiento/seguimiento_9_694b159a5726a_1766528410.jpg', 168214, '2025-12-23 22:20:10'),
(7, 10, 'imagen', 'background.png', 'uploads/seguimiento/seguimiento_10_694b172720b5a_1766528807.png', 360174, '2025-12-23 22:26:47'),
(8, 11, 'imagen', 'Nubes.png', 'uploads/seguimiento/seguimiento_11_695bf026ef360_1767632934.png', 212746, '2026-01-05 17:08:54'),
(9, 12, 'imagen', 'background.png', 'uploads/seguimiento/seguimiento_12_69611b8b3e81a_1767971723.png', 360174, '2026-01-09 15:15:23'),
(10, 13, 'imagen', 'Nubes.png', 'uploads/seguimiento/seguimiento_13_69611ea460158_1767972516.png', 212746, '2026-01-09 15:28:36'),
(11, 13, 'imagen', 'login-background.png', 'uploads/seguimiento/seguimiento_13_69611ea46065c_1767972516.png', 582829, '2026-01-09 15:28:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `denuncias`
--

CREATE TABLE `denuncias` (
  `id_denuncia` int NOT NULL,
  `tipo_persona` enum('Individual','Juridica') COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_completo` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `dpi` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `edad` int NOT NULL,
  `genero` enum('Masculino','Femenino') COLLATE utf8mb4_general_ci NOT NULL,
  `celular` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `foto_dpi_frontal` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `foto_dpi_trasera` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_responsable` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion_infraccion` text COLLATE utf8mb4_general_ci NOT NULL,
  `departamento` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `municipio` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `color_casa` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color_puerta` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_fachada` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `especie_animal` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `especie_otro` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cantidad` int NOT NULL,
  `raza` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion_detallada` text COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_denuncia` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_denuncia` enum('pendiente','en_proceso','resuelta','rechazada') COLLATE utf8mb4_general_ci DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `denuncias`
--

INSERT INTO `denuncias` (`id_denuncia`, `tipo_persona`, `nombre_completo`, `dpi`, `edad`, `genero`, `celular`, `foto_dpi_frontal`, `foto_dpi_trasera`, `nombre_responsable`, `direccion_infraccion`, `departamento`, `municipio`, `color_casa`, `color_puerta`, `foto_fachada`, `latitud`, `longitud`, `especie_animal`, `especie_otro`, `cantidad`, `raza`, `descripcion_detallada`, `fecha_denuncia`, `estado_denuncia`) VALUES
(1, 'Individual', 'Juan Pérez López', '3000 05369 0101', 35, 'Masculino', '3010-7000', 'uploads/dpi/frontal_123.jpg', 'uploads/dpi/trasera_123.jpg', 'Pedro García', '5ta Calle 3-45 Zona 1', 'Guatemala', 'Guatemala', 'Azul', 'Blanca', 'uploads/fachadas/fachada_123.jpg', 14.63490000, -90.50690000, 'Caninos', NULL, 2, 'Labrador', 'Se observó maltrato físico a dos perros en el patio trasero de la vivienda.', '2025-12-17 17:44:06', 'en_proceso'),
(2, 'Individual', 'Miguel Fuentes', '4529846503249', 25, 'Masculino', '97120665', '../uploads/dpi/6943159878299_1766004120.jpg', '../uploads/dpi/6943159b45639_1766004123.jpg', NULL, 'prueba de direccion', 'Izabal', 'Morales', 'rojo', 'negro', '../uploads/fachadas/6943159baa042_1766004123.jpg', 37.42199830, -122.08400000, 'Caninos', NULL, 2, NULL, 'prueba de descripcion', '2025-12-17 20:42:04', 'en_proceso'),
(3, 'Juridica', 'prueba 2', '0651894561032', 32, 'Femenino', '02015488', '../uploads/dpi/6943273e4781f_1766008638.jpg', '../uploads/dpi/6943273ece09a_1766008638.jpg', 'prueba 2 responsable', 'prueba 2 de direccion', 'Guatemala', 'Villa Nueva', 'negro', 'amarillo', '../uploads/fachadas/6943273f448f7_1766008639.jpg', 37.42153967, -122.08343826, 'Reptil', 'Reptil', 2, NULL, 'prueba 2 de descripcion detallada', '2025-12-17 21:57:20', 'resuelta'),
(4, 'Individual', 'prueba3', '2050561566465', 22, 'Masculino', '98765132', '../uploads/dpi/6944322dd79d6_1766076973.jpg', '../uploads/dpi/6944322e59338_1766076974.jpg', NULL, 'prueba 3', 'El Petén', 'San Francisco', 'negro', 'azul', '../uploads/fachadas/6944322eb4f23_1766076974.jpg', 37.42199830, -122.08400000, 'Caninos', NULL, 1, NULL, 'prueba 3', '2025-12-18 16:56:15', 'en_proceso'),
(5, 'Individual', 'prueba4', '4570761616890', 47, 'Masculino', '16575760', '../uploads/dpi/694584cc05e7a_1766163660.jpg', '../uploads/dpi/694584ccc9aa4_1766163660.jpg', NULL, 'prueba 4', 'Chiquimula', 'Camotán', 'rojo', 'azul', '../uploads/fachadas/694584cdbe67d_1766163661.jpg', 14.58971920, -90.53343890, 'Caninos', 'Prueba de edicion', 1, NULL, 'prueba 4 pero ahora con el apartado de edicion', '2025-12-19 17:01:03', 'en_proceso'),
(6, 'Individual', 'ldkz', '6169497979676', 20, 'Masculino', '65646646', '../uploads/dpi/69693aef02fc6_1768504047.jpg', '../uploads/dpi/69693af01b880_1768504048.jpg', NULL, 'ndmzm', 'Chimaltenango', 'Comalapa (San Juan Comalapa)', NULL, NULL, '../uploads/fachadas/69693af0dce4d_1768504048.jpg', 14.59954148, -90.52966587, 'Caninos', NULL, 2, NULL, 'jdndn', '2026-01-15 19:07:33', 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencias_denuncia`
--

CREATE TABLE `evidencias_denuncia` (
  `id_evidencia` int NOT NULL,
  `id_denuncia` int NOT NULL,
  `tipo_archivo` enum('imagen','pdf','doc','audio','video','otro') COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tamanio_kb` int DEFAULT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evidencias_denuncia`
--

INSERT INTO `evidencias_denuncia` (`id_evidencia`, `id_denuncia`, `tipo_archivo`, `nombre_archivo`, `ruta_archivo`, `tamanio_kb`, `fecha_subida`) VALUES
(1, 1, 'imagen', 'evidencia1.jpg', 'uploads/evidencias/evidencia1.jpg', 1500, '2025-12-17 17:44:06'),
(2, 1, 'imagen', 'evidencia2.jpg', 'uploads/evidencias/evidencia2.jpg', 2300, '2025-12-17 17:44:06'),
(3, 2, 'imagen', '6943159c3f731_1766004124.jpg', '../uploads/evidencias/6943159c3f731_1766004124.jpg', 141, '2025-12-17 20:42:04'),
(4, 3, 'imagen', '6943273fe2f7a_1766008639.jpg', '../uploads/evidencias/6943273fe2f7a_1766008639.jpg', 142, '2025-12-17 21:57:20'),
(5, 4, 'imagen', '6944322f10933_1766076975.jpg', '../uploads/evidencias/6944322f10933_1766076975.jpg', 52, '2025-12-18 16:56:15'),
(6, 5, 'imagen', '694584cee42ef_1766163662.jpg', '../uploads/evidencias/694584cee42ef_1766163662.jpg', 988, '2025-12-19 17:01:03'),
(7, 6, 'imagen', '69693af5044b0_1768504053.jpg', '../uploads/evidencias/69693af5044b0_1768504053.jpg', 988, '2026-01-15 19:07:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `infracciones_denuncia`
--

CREATE TABLE `infracciones_denuncia` (
  `id` int NOT NULL,
  `id_denuncia` int NOT NULL,
  `tipo_infraccion` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `infraccion_otro` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `infracciones_denuncia`
--

INSERT INTO `infracciones_denuncia` (`id`, `id_denuncia`, `tipo_infraccion`, `infraccion_otro`) VALUES
(1, 1, 'Maltrato físico', NULL),
(2, 1, 'No garantizar condiciones de bienestar', NULL),
(3, 2, 'Actos de Crueldad', NULL),
(4, 2, 'Abandono', NULL),
(5, 3, 'Maltrato físico', NULL),
(6, 3, 'Mutilaciones', NULL),
(7, 3, 'Técnicas de adiestramiento que causen sufrimiento', NULL),
(8, 4, 'Actos de Crueldad', NULL),
(14, 5, 'Actos de Crueldad', NULL),
(15, 6, 'Envenenar o intoxicar a un animal', NULL),
(16, 6, 'Abandono', NULL),
(17, 6, 'No garantizar condiciones de bienestar', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

CREATE TABLE `noticias` (
  `id_noticia` int NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` enum('Campaña','Rescate','Legislación','Alerta','Evento','Otro') COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_corta` text COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Para preview en la app',
  `contenido_completo` text COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contenido completo de la noticia',
  `imagen_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Foto de la noticia',
  `fecha_publicacion` date NOT NULL,
  `estado` enum('publicada','borrador','archivada') COLLATE utf8mb4_general_ci DEFAULT 'publicada',
  `prioridad` enum('normal','importante','urgente') COLLATE utf8mb4_general_ci DEFAULT 'normal',
  `creado_por` int NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `noticias`
--

INSERT INTO `noticias` (`id_noticia`, `titulo`, `categoria`, `descripcion_corta`, `contenido_completo`, `imagen_url`, `fecha_publicacion`, `estado`, `prioridad`, `creado_por`, `fecha_creacion`, `fecha_modificacion`) VALUES
(1, 'Campaña de Esterilización Gratuita', 'Campaña', 'Jornada de esterilización en zona 18 los días 5 y 6 de octubre.', 'El Ministerio de Agricultura, Ganadería y Alimentación anuncia una jornada de esterilización gratuita para perros y gatos en la zona 18. La campaña se llevará a cabo los días 5 y 6 de octubre de 8:00 AM a 4:00 PM. Se recomienda llevar a las mascotas en ayunas.', 'uploads/noticias/695d4c5928bad_1767722073.jpg', '2025-09-28', 'publicada', 'importante', 1, '2025-12-22 21:39:10', '2026-01-06 17:54:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento_denuncias`
--

CREATE TABLE `seguimiento_denuncias` (
  `id_seguimiento` int NOT NULL,
  `id_denuncia` int NOT NULL,
  `etapa` enum('area_legal','area_tecnica','emitir_dictamen','opinion_legal','resolucion_final') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Etapa donde se procesó',
  `accion` enum('siguiente_paso','rechazado','resuelto') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Acción tomada',
  `comentario` text COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Comentario del técnico',
  `etapa_actual` enum('pendiente_revision','en_area_legal','en_area_tecnica','en_dictamen','en_opinion_legal','en_resolucion_final','finalizada') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Próxima etapa después de esta acción',
  `procesado_por` int NOT NULL COMMENT 'ID del usuario que procesó',
  `fecha_procesamiento` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `seguimiento_denuncias`
--

INSERT INTO `seguimiento_denuncias` (`id_seguimiento`, `id_denuncia`, `etapa`, `accion`, `comentario`, `etapa_actual`, `procesado_por`, `fecha_procesamiento`) VALUES
(1, 4, 'area_legal', 'siguiente_paso', 'primera prueba de comentario de procesar denuncia en area legal', 'en_area_tecnica', 1, '2025-12-23 16:17:50'),
(2, 3, 'area_legal', 'siguiente_paso', 'segunda prueba de procesar denuncia en area legal', 'en_area_tecnica', 1, '2025-12-23 21:04:59'),
(6, 4, 'area_tecnica', 'siguiente_paso', 'primera prueba en procesar denuncia en area tecnica', 'en_dictamen', 1, '2025-12-23 21:12:47'),
(7, 3, 'area_tecnica', 'siguiente_paso', 'segunda prueba de procesar denuncia en area tecnica', 'en_dictamen', 1, '2025-12-23 21:16:13'),
(8, 3, 'emitir_dictamen', 'siguiente_paso', 'primera prueba de procesar denuncia en area emitir dictamen', 'en_opinion_legal', 1, '2025-12-23 21:59:22'),
(9, 3, 'opinion_legal', 'siguiente_paso', 'primera prueba de procesar denuncia en area opinion legal', 'en_resolucion_final', 1, '2025-12-23 22:20:10'),
(10, 3, 'resolucion_final', 'resuelto', 'primera prueba final de procesar denuncia en area resolucion final dandole al boton de resolver', 'finalizada', 1, '2025-12-23 22:26:47'),
(11, 5, 'area_legal', 'siguiente_paso', 'Prueba desde el tecnico 1', 'en_area_tecnica', 2, '2026-01-05 17:08:54'),
(12, 2, 'area_legal', 'siguiente_paso', 'Segunda prueba usando areas tecnicas, en este caso estoy enviando esta informacion desde area legal', 'en_area_tecnica', 1, '2026-01-09 15:15:23'),
(13, 1, 'area_legal', 'siguiente_paso', 'Tercera prueba subiendo mas de un archivo a este apartado la cual es area tecnica legal', 'en_area_tecnica', 1, '2026-01-09 15:28:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios_autorizados`
--

CREATE TABLE `servicios_autorizados` (
  `id_servicio` int NOT NULL,
  `nombre_servicio` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `servicios_ofrecidos` text COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Ej: Consulta, Cirugía, Emergencias 24/7',
  `calificacion` decimal(2,1) DEFAULT '0.0' COMMENT 'Calificación de 0.0 a 5.0',
  `total_calificaciones` int DEFAULT '0' COMMENT 'Cantidad de personas que han calificado',
  `imagen_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Foto de la clínica/veterinaria',
  `estado` enum('activo','inactivo') COLLATE utf8mb4_general_ci DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `creado_por` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios_autorizados`
--

INSERT INTO `servicios_autorizados` (`id_servicio`, `nombre_servicio`, `direccion`, `latitud`, `longitud`, `telefono`, `servicios_ofrecidos`, `calificacion`, `total_calificaciones`, `imagen_url`, `estado`, `fecha_creacion`, `fecha_modificacion`, `creado_por`) VALUES
(1, 'Clínica Veterinaria Mascota Feliz', '5ta Avenida 12-53 Zona 10, Guatemala', 14.59378000, -90.51384000, '2334-5678', 'Consulta, Cirugía, Emergencias 24/7', 4.8, 127, NULL, 'activo', '2025-12-22 16:42:27', NULL, NULL),
(2, 'Hospital Veterinario Pet Care', 'Boulevard Los Próceres 24-69, Zona 10', 14.58912300, -90.51623400, '2267-8900', 'Consulta, Laboratorio, Hospitalización', 4.9, 203, NULL, 'activo', '2025-12-22 16:46:52', NULL, 1),
(3, 'Prueba 1', 'Zona 13, Cdad. de Guatemala, Guatemala', 14.58187168, -90.53006969, '6546-5789', 'Prueba 1 de servicios ofrecidos', 3.5, 2, 'uploads/servicios/695d52355faa7_1767723573.png', 'activo', '2025-12-22 21:15:23', '2026-01-08 18:28:59', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_web`
--

CREATE TABLE `usuarios_web` (
  `id_usuario` int NOT NULL,
  `nombre_completo` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rol` enum('admin','tecnico_1','tecnico_2','tecnico_3','tecnico_4','tecnico_5') COLLATE utf8mb4_general_ci NOT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_general_ci DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios_web`
--

INSERT INTO `usuarios_web` (`id_usuario`, `nombre_completo`, `usuario`, `email`, `password`, `rol`, `estado`, `fecha_creacion`, `ultimo_login`) VALUES
(1, 'Administrador', 'admin', 'admin@maga.gob.gt', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'admin', 'activo', '2025-12-19 17:42:15', '2026-01-28 00:48:45'),
(2, 'Técnico Legal', 'tecnico1', 'tecnico1@maga.gob.gt', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'tecnico_1', 'activo', '2025-12-22 15:39:13', '2026-01-09 17:02:53'),
(3, 'Técnico Área Técnica', 'tecnico2', 'tecnico2@maga.gob.gt', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'tecnico_2', 'activo', '2025-12-22 15:39:13', '2026-01-08 16:58:39'),
(4, 'Técnico Dictamen', 'tecnico3', 'tecnico3@maga.gob.gt', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'tecnico_3', 'activo', '2025-12-22 15:39:13', '2026-01-08 17:00:33'),
(5, 'Técnico Opinión Legal', 'tecnico4', 'tecnico4@maga.gob.gt', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'tecnico_4', 'activo', '2025-12-22 15:39:13', '2026-01-08 17:03:08'),
(6, 'Técnico Resolución', 'tecnico5', 'tecnico5@maga.gob.gt', '$2y$10$zvfG.iexCthElHR7zC0EYOKl0ZpNht4/554HTT1Eq19xEgKoN6bTW', 'tecnico_5', 'activo', '2025-12-22 15:39:13', '2026-01-08 17:03:20');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos_seguimiento`
--
ALTER TABLE `archivos_seguimiento`
  ADD PRIMARY KEY (`id_archivo`),
  ADD KEY `idx_seguimiento` (`id_seguimiento`),
  ADD KEY `idx_tipo` (`tipo_archivo`);

--
-- Indices de la tabla `denuncias`
--
ALTER TABLE `denuncias`
  ADD PRIMARY KEY (`id_denuncia`),
  ADD KEY `idx_fecha` (`fecha_denuncia`),
  ADD KEY `idx_estado` (`estado_denuncia`),
  ADD KEY `idx_departamento` (`departamento`);

--
-- Indices de la tabla `evidencias_denuncia`
--
ALTER TABLE `evidencias_denuncia`
  ADD PRIMARY KEY (`id_evidencia`),
  ADD KEY `idx_denuncia` (`id_denuncia`);

--
-- Indices de la tabla `infracciones_denuncia`
--
ALTER TABLE `infracciones_denuncia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_denuncia` (`id_denuncia`);

--
-- Indices de la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id_noticia`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_fecha_publicacion` (`fecha_publicacion`),
  ADD KEY `idx_prioridad` (`prioridad`),
  ADD KEY `creado_por` (`creado_por`);

--
-- Indices de la tabla `seguimiento_denuncias`
--
ALTER TABLE `seguimiento_denuncias`
  ADD PRIMARY KEY (`id_seguimiento`),
  ADD KEY `idx_denuncia` (`id_denuncia`),
  ADD KEY `idx_etapa` (`etapa`),
  ADD KEY `idx_etapa_actual` (`etapa_actual`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_fecha` (`fecha_procesamiento`),
  ADD KEY `procesado_por` (`procesado_por`);

--
-- Indices de la tabla `servicios_autorizados`
--
ALTER TABLE `servicios_autorizados`
  ADD PRIMARY KEY (`id_servicio`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_calificacion` (`calificacion`),
  ADD KEY `idx_latitud_longitud` (`latitud`,`longitud`),
  ADD KEY `creado_por` (`creado_por`);

--
-- Indices de la tabla `usuarios_web`
--
ALTER TABLE `usuarios_web`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_rol` (`rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos_seguimiento`
--
ALTER TABLE `archivos_seguimiento`
  MODIFY `id_archivo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `denuncias`
--
ALTER TABLE `denuncias`
  MODIFY `id_denuncia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `evidencias_denuncia`
--
ALTER TABLE `evidencias_denuncia`
  MODIFY `id_evidencia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `infracciones_denuncia`
--
ALTER TABLE `infracciones_denuncia`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id_noticia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `seguimiento_denuncias`
--
ALTER TABLE `seguimiento_denuncias`
  MODIFY `id_seguimiento` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `servicios_autorizados`
--
ALTER TABLE `servicios_autorizados`
  MODIFY `id_servicio` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios_web`
--
ALTER TABLE `usuarios_web`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos_seguimiento`
--
ALTER TABLE `archivos_seguimiento`
  ADD CONSTRAINT `archivos_seguimiento_ibfk_1` FOREIGN KEY (`id_seguimiento`) REFERENCES `seguimiento_denuncias` (`id_seguimiento`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evidencias_denuncia`
--
ALTER TABLE `evidencias_denuncia`
  ADD CONSTRAINT `evidencias_denuncia_ibfk_1` FOREIGN KEY (`id_denuncia`) REFERENCES `denuncias` (`id_denuncia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `infracciones_denuncia`
--
ALTER TABLE `infracciones_denuncia`
  ADD CONSTRAINT `infracciones_denuncia_ibfk_1` FOREIGN KEY (`id_denuncia`) REFERENCES `denuncias` (`id_denuncia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `noticias_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios_web` (`id_usuario`);

--
-- Filtros para la tabla `seguimiento_denuncias`
--
ALTER TABLE `seguimiento_denuncias`
  ADD CONSTRAINT `seguimiento_denuncias_ibfk_1` FOREIGN KEY (`id_denuncia`) REFERENCES `denuncias` (`id_denuncia`) ON DELETE CASCADE,
  ADD CONSTRAINT `seguimiento_denuncias_ibfk_2` FOREIGN KEY (`procesado_por`) REFERENCES `usuarios_web` (`id_usuario`);

--
-- Filtros para la tabla `servicios_autorizados`
--
ALTER TABLE `servicios_autorizados`
  ADD CONSTRAINT `servicios_autorizados_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios_web` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
