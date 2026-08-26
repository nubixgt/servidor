-- Migración de Climatología con Prefijos (clima_)
-- Limpiado de directivas BINLOG y GTID_PURGED
SET FOREIGN_KEY_CHECKS=0;

-- DROPS en orden inverso para evitar conflictos de claves foraneas
DROP TABLE IF EXISTS `clima_fotos`;
DROP TABLE IF EXISTS `clima_registros`;
DROP TABLE IF EXISTS `clima_alertas`;
DROP TABLE IF EXISTS `clima_usuarios`;

-- 1. Tabla de Usuarios (clima_usuarios)
CREATE TABLE `clima_usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `NombreCompleto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Usuario` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Contrasena` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DPI` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Telefono` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Departamento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Municipio` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Rol` enum('Administrador','Supervisor','Tecnico') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Estado` enum('Activo','Suspendido','Pendiente') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Activo',
  `FechaCreacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UltimoAcceso` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Usuario` (`Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clima_usuarios` VALUES
(1,'Ministra','admin','$2y$12$wD0odNPINpc2GPeBcrVgBeaZCePgmKhZceHlo6fZcYV/3N8jzSaZm','3000 05369 0101','3010-7000','Guatemala','Guatemala','Administrador','Activo','2025-10-13 20:55:34','2026-02-20 22:30:45'),(2,'María Fernanda López Hernández','supervisor','$2y$12$a7jwE1WinB9/3Yrc0YOxwOcjvKUdmEeGHsgqGUKrT5smSycLA9LJi','2500 12345 0202','4020-8000','Quetzaltenango','Quetzaltenango','Tecnico','Activo','2025-10-13 20:55:34','2025-10-18 18:34:22'),(3,'Carlos Ramírez Morales','tecnico','$2y$12$UaWofK8w.O9WDKdjV2fabu4PmS6tEhnDRZXvyTGv/3fyEMgb5b2ra','1800 98765 0303','5030-9000','Sacatepéquez','Santo Domingo Xenacoj','Tecnico','Activo','2025-10-13 20:55:34','2026-01-28 03:43:28'),(4,'Jose Carlos Mata Aragon','jmata','$2y$12$fb99pq91eOSxPoQFLe.7/eZY0Ffmy/hU26Y6qudoR9p.vtaRgQjRq','1234 56789 1233','4528-9012','Guatemala','Guatemala','Tecnico','Activo','2025-10-13 22:13:41','2026-01-17 18:33:40'),(5,'Diego Alexander Lopez','dlopez','$2y$12$PttAFLz5y4/JcBpwjEnA1O3VS7eyrbsYq8DKCk7bLKZxu6d2uSllK','4528 90124 5289','3010-5000','Guatemala','Fraijanes','Supervisor','Pendiente','2025-10-14 16:04:02',NULL),(6,'Manuel Esteban Fuentes Monzon','mfuentes','$2y$12$oCTdsA7Ug9EuZAmsX8Yi4OKa2MNV251LS94t73aG7nvsuah2gUfhG','8321 45489 9798','4516-0602','Izabal','Los Amates','Supervisor','Activo','2025-10-14 16:53:15','2025-11-12 16:19:14'),(7,'Pedro Lopez','plopez','$2y$12$37xErSFbdyMZOgqn35lZYO4cVPeA3u9N//YkuEpnaRuHUgxzciL1e','3423 42342 5553','2342-3423','Guatemala','Guatemala','Administrador','Activo','2025-10-14 23:00:14','2025-10-15 00:23:35'),(8,'Diana Lucrecia Monzon Cordero','dmonzon','$2y$12$YXkVMcD1mz5/w8swXM84B.mJKM9j9yXV/0qNzCUw9PBlNzKabLQSu','0021 62021 5485','0215-1202','Huehuetenango','San Miguel Acatán','Supervisor','Activo','2025-10-15 03:48:36','2026-01-14 15:43:54'),(9,'Prueba Prueba','pprueba','$2y$12$S6flVtrUresONgTc0hRIQeyDEd.OG/b2wUrSyIj/rzn7W5qVTHKTW','0302 30651 6160','0620-3202','Chimaltenango','San Martín Jilotepeque','Administrador','Suspendido','2025-10-15 03:53:05','2025-10-15 04:09:06'),(10,'Carlos Chacon','cchacon','$2y$12$t3VBipZ9H2wWdW/V7arXHOK9I87VNduFiUbb6SWP6db4bPbL7bHR2','1811 15527 0101','4212-4545','Guatemala','Guatemala','Administrador','Activo','2025-12-04 17:46:05','2026-02-06 15:59:11');

-- 2. Tabla de Alertas (clima_alertas)
CREATE TABLE `clima_alertas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_corta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_detallada` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_alerta` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nivel_severidad` enum('ALTA','MEDIA','BAJA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icono` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 0xF09F9AA8,
  `fecha_emision` datetime NOT NULL,
  `fecha_vigencia` datetime NOT NULL,
  `estado` enum('Activa','Inactiva','Vencida') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Activa',
  `id_usuario_creador` int NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario_creador`),
  CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`id_usuario_creador`) REFERENCES `clima_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clima_alertas` VALUES
(1,'Alerta de SEQUIA','Prueba de descripcion corta','Prueba de descripcion detallada, para saber si todas las palabras encajan correctamente, y si tambien no hay ningun error al momento de actulizar y revisarlo en la base de datos completos ','Sequía','BAJA','Altiplano Occidental','🌵','2025-10-14 18:58:00','2025-10-21 18:58:00','Inactiva',1,'2025-10-14 18:58:41','2025-10-15 00:04:36'),(2,'Alerta de Tormenta','Esto es una prueba de una descripcion corta','Esto es una prueba de descripcion detallada para saber si todo esto funciona correctamente','Tormenta','ALTA','Costa Sur','⛈️','2026-01-17 18:37:26','2026-01-20 18:37:26','Activa',7,'2025-10-15 00:44:04','2026-01-17 18:37:26'),(3,'Alerta de Inundacion','Prueba de una descripcion corta sobre la alerta de inundacion','Prueba de descripcion detallada para la informacion sobre la alerta de la inundacion','Inundación','MEDIA','Altiplano Central y Occidental','🌊','2026-01-17 18:37:26','2026-01-20 18:37:26','Activa',1,'2025-10-15 03:51:00','2026-01-17 18:37:26');

-- 3. Tabla de Registros Climaticos (clima_registros)
CREATE TABLE `clima_registros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `temperatura` decimal(5,2) DEFAULT NULL,
  `humedad` decimal(5,2) DEFAULT NULL,
  `precipitacion` decimal(5,2) DEFAULT NULL,
  `viento` decimal(5,2) DEFAULT NULL,
  `categoria` enum('condicion','desastre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'condicion',
  `condicion_climatica` enum('normal','sequia','exceso_lluvia','helada','tormenta') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desastre_natural` enum('inundacion','deslizamiento','sismo','erupcion_volcanica','huracan','incendio_forestal','granizo') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `sincronizado` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_fecha` (`fecha_registro`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_ubicacion` (`latitud`,`longitud`),
  CONSTRAINT `registros_climaticos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `clima_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clima_registros` VALUES
(1,4,'2026-01-12 16:02:42',14.63490000,-90.50690000,'Mountain View, California, United States (37.42200, -122.08400)',21.00,21.00,21.00,21.00,'condicion','sequia',NULL,'Primera prueba del formulario a la base de datos',1,'2026-01-12 22:02:42','2026-01-14 14:28:21'),(2,4,'2026-01-12 16:12:48',14.55860000,-90.73390000,'Mountain View, California, United States (37.42200, -122.08400)',22.00,22.00,22.00,22.00,'desastre',NULL,'huracan','Segunda prueba para ver si se sube la foto correctamente',1,'2026-01-12 22:12:48','2026-01-14 14:28:21'),(3,4,'2026-01-12 16:28:20',14.83470000,-91.51830000,'Mountain View, California, United States (37.42200, -122.08400)',25.00,25.00,25.00,25.00,'condicion','exceso_lluvia',NULL,'Tercera prueba subiendo fotos',1,'2026-01-12 22:28:20','2026-01-14 14:28:21'),(4,4,'2026-01-12 16:42:56',15.47140000,-90.37060000,'Mountain View, California, United States (37.42200, -122.08400)',26.00,26.00,26.00,26.00,'desastre',NULL,'erupcion_volcanica','Cuarta prueba subiendo fotos',1,'2026-01-12 22:42:56','2026-01-14 14:28:21'),(5,4,'2026-01-12 16:50:06',14.30530000,-90.78500000,'Mountain View, California, United States (37.42200, -122.08400)',30.00,30.00,30.00,30.00,'desastre',NULL,'inundacion','Quinta prueba',1,'2026-01-12 22:50:06','2026-01-14 15:44:27'),(6,4,'2026-01-12 16:56:51',15.31970000,-91.47140000,'Mountain View, California, United States (37.42200, -122.08400)',31.00,31.00,31.00,31.00,'desastre',NULL,'incendio_forestal','Sexta prueba',1,'2026-01-12 22:56:51','2026-01-14 14:28:21'),(7,3,'2026-01-12 18:08:54',15.73080000,-88.59280000,'Mountain View, California, United States (37.42200, -122.08400)',50.00,50.00,50.00,50.00,'desastre',NULL,'huracan','Septima prueba',1,'2026-01-13 00:08:54','2026-01-14 14:28:22'),(8,3,'2026-01-15 08:15:38',14.56077950,-90.52223120,'Ciudad de Guatemala, Guatemala, Guatemala (14.56078, -90.52223)',2.00,2.00,NULL,NULL,'condicion','normal',NULL,'prueba',1,'2026-01-15 14:15:38','2026-01-15 14:15:38');

-- 4. Tabla de Fotografias (clima_fotos)
CREATE TABLE `clima_fotos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_registro` int NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` tinyint DEFAULT '1',
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_registro` (`id_registro`),
  KEY `idx_orden` (`orden`),
  CONSTRAINT `registros_fotografias_ibfk_1` FOREIGN KEY (`id_registro`) REFERENCES `clima_registros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clima_fotos` VALUES
(1,6,'scaled_d51ec802-40b5-413d-9c0f-7826580b363a6312600490939370249.jpg','uploads/registros/usuario_4/registro_6_foto_1_1768258611.jpg',1,'2026-01-12 22:56:51'),(2,6,'scaled_7bf0e88f-49cd-4786-8a0b-a4110f3d13293799490314700106218.jpg','uploads/registros/usuario_4/registro_6_foto_2_1768258611.jpg',2,'2026-01-12 22:56:51'),(3,7,'scaled_372725f5-cc5d-42a1-9117-eda40bf190676433640327328313316.jpg','uploads/registros/usuario_3/registro_7_foto_1_1768262934.jpg',1,'2026-01-13 00:08:54'),(4,7,'scaled_a07fb270-4a49-46f5-b952-0324ea1f2a0e4331194227330059514.jpg','uploads/registros/usuario_3/registro_7_foto_2_1768262934.jpg',2,'2026-01-13 00:08:54'),(5,7,'scaled_7e445f29-df7f-4c4b-a91f-6e0e8f412d526061231418472497848.jpg','uploads/registros/usuario_3/registro_7_foto_3_1768262934.jpg',3,'2026-01-13 00:08:54'),(6,7,'scaled_dbd394d5-0bcf-45d3-9de5-31804780ac5c3141397724334024957.jpg','uploads/registros/usuario_3/registro_7_foto_4_1768262934.jpg',4,'2026-01-13 00:08:54'),(7,7,'scaled_d4e50be8-774f-48f5-975c-36c9895177524796904402283058807.jpg','uploads/registros/usuario_3/registro_7_foto_5_1768262934.jpg',5,'2026-01-13 00:08:54'),(8,8,'scaled_e258c45b-96ef-4434-8821-a5602099f0b48291635256772352624.jpg','uploads/registros/usuario_3/registro_8_foto_1_1768486538.jpg',1,'2026-01-15 14:15:38'),(9,8,'scaled_3ba37115-03fa-4bf5-996a-ff0b229c0b3f1393633877723505067.jpg','uploads/registros/usuario_3/registro_8_foto_2_1768486538.jpg',2,'2026-01-15 14:15:38');

SET FOREIGN_KEY_CHECKS=1;
