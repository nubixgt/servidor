-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: db.vider.maga.aws    Database: actividades_despacho
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '';

--
-- Table structure for table `actividades`
--

DROP TABLE IF EXISTS `actividades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actividades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_tecnico` int NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text,
  `estado` enum('activa','en_progreso','critica','completada') DEFAULT 'activa',
  `prioridad` enum('baja','media','alta','critica') DEFAULT 'media',
  `categoria` varchar(200) NOT NULL,
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_tecnico` (`id_tecnico`),
  CONSTRAINT `actividades_ibfk_1` FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actividades`
--

LOCK TABLES `actividades` WRITE;
/*!40000 ALTER TABLE `actividades` DISABLE KEYS */;
INSERT INTO `actividades` VALUES (1,2,'Prueba','Descripcion de Prueba','en_progreso','critica','','2025-10-27 18:55:50',NULL,'2025-10-27 18:55:50','2025-11-03 21:41:50'),(5,5,'generacion de dashboards sobre prioridades y temas en redes sociales','Los dashboards de redes sociales del MAGA permiten monitorear en tiempo real la percepción ciudadana, el alcance comunicacional y las tendencias de conversación relacionadas con el Ministerio y sus principales programas. A través de herramientas de análisis avanzado e inteligencia artificial, estos tableros integran datos de plataformas como X, Facebook y TikTok para identificar temas emergentes, evaluar el impacto de campañas institucionales y detectar posibles focos de desinformación. Su objetivo es fortalecer la comunicación estratégica, brindar insumos a las unidades de prensa y planificación, y generar reportes visuales que faciliten la toma de decisiones basada en evidencia digital.','completada','media','','2025-10-29 17:23:13','2025-09-30 11:22:00','2025-10-29 17:23:13','2025-10-29 17:23:13'),(6,5,'capacitacion a DIPLAN sobre herramientas de IA','La capacitación a DIPLAN sobre herramientas de inteligencia artificial tuvo como objetivo fortalecer las capacidades técnicas del personal en el uso de tecnologías emergentes aplicadas a la planificación y análisis institucional. Durante las sesiones se abordaron conceptos fundamentales de IA, automatización de tareas, generación de textos e informes asistidos por IA, análisis predictivo y uso de dashboards inteligentes para la toma de decisiones. La formación permitió que los equipos de DIPLAN comprendieran cómo integrar estas herramientas en sus procesos de planificación, evaluación de proyectos y elaboración de reportes, promoviendo una cultura de innovación y eficiencia dentro del MAGA.','completada','media','','2025-10-29 17:24:16','2025-10-06 11:24:00','2025-10-29 17:24:16','2025-10-29 17:24:16'),(7,5,'Reuniones bilaterales MP','','en_progreso','critica','','2025-10-29 17:24:54','2025-12-30 11:24:00','2025-10-29 17:24:54','2025-10-29 17:24:54'),(8,5,'Reuniones CGC','','en_progreso','critica','','2025-10-29 17:25:23','2025-12-30 11:25:00','2025-10-29 17:25:23','2025-11-03 21:41:58'),(9,5,'CREACION DE DASHBOARDS SOBRE ALERTAS Y AMENAZAS','MEDIOS TRADICIONALES \r\nREDES SOCIALES','completada','media','','2025-10-29 17:26:15','2025-09-23 11:26:00','2025-10-29 17:26:15','2025-10-29 17:26:15'),(10,5,'capacitacion sobre IA a CRC','La capacitación impartida a Catholic CRC estuvo enfocada en el uso responsable y estratégico de la inteligencia artificial y las herramientas digitales en contextos educativos y organizacionales. Durante la formación, se abordaron temas como la creación de contenidos asistidos por IA, análisis de información en redes sociales, detección de desinformación y seguridad digital básica.','completada','media','','2025-10-29 17:27:23','2025-09-17 11:27:00','2025-10-29 17:27:23','2025-10-29 17:27:23'),(11,5,'organizacion dia de la agricultura digital','La organización del Día de la Agricultura Digital en Guatemala representó un esfuerzo interinstitucional liderado por el MAGA para posicionar la digitalización como eje central del desarrollo agropecuario nacional. Bajo la coordinación general del despacho superior y el equipo de Innovación y Tecnología, se gestionaron alianzas con instituciones como IICA, FAO, OIRSA, SEGEPLAN y la Unión Europea, logrando reunir a representantes del sector público, privado y académico. El evento incluyó conferencias magistrales, exhibiciones tecnológicas, demostraciones de drones, sensores y sistemas de trazabilidad con blockchain, así como la presentación de pilotos de agricultura inteligente. Su realización consolidó a Guatemala como referente regional en transformación digital agropecuaria y marcó el inicio de una agenda permanente de innovación en el sector.','completada','media','','2025-10-29 17:28:33','2025-10-12 11:28:00','2025-10-29 17:28:33','2025-10-29 17:28:33'),(12,5,'GEOPORTAL','','en_progreso','media','','2025-10-29 17:28:55','2025-11-28 11:28:00','2025-10-29 17:28:55','2025-11-03 21:42:04'),(13,5,'dashboard sobre tendencias del congreso','El dashboard sobre tendencias en el Congreso de la República fue diseñado para analizar de forma sistemática la conversación digital y mediática relacionada con la actividad legislativa en Guatemala. Este tablero recopila datos en tiempo real de redes sociales, portales de noticias y fuentes oficiales, permitiendo identificar los temas más discutidos, las posturas de los distintos bloques parlamentarios y la percepción ciudadana hacia iniciativas específicas. A través de métricas como sentimiento, alcance y frecuencia, el dashboard facilita un seguimiento estratégico de los debates, reformas y actores clave, ofreciendo al MAGA y a otras instituciones insumos valiosos para comprender el entorno político y anticipar posibles impactos en la agenda agropecuaria y de desarrollo rural.','completada','media','','2025-10-29 17:29:00','2025-12-30 11:29:00','2025-10-29 17:29:49','2025-11-05 16:13:23'),(14,5,'coordinacion con COMUNICACION','','en_progreso','critica','','2025-10-29 17:30:55','2025-12-31 11:30:00','2025-10-29 17:30:55','2025-10-29 17:30:55'),(15,5,'coordinacion IT','coordinacion con equipo de IT para temas varios','en_progreso','critica','Simplificación y trazabilidad de trámites','2025-10-29 17:31:00','2025-12-31 11:31:00','2025-10-29 17:31:43','2025-11-19 18:16:13'),(16,5,'ANALISIS DE PERFILES','','en_progreso','critica','','2025-10-29 17:32:32','2025-12-31 11:32:00','2025-10-29 17:32:32','2025-10-29 17:32:32'),(17,5,'IMPLEMENTACION BLOCKCHAIN','','en_progreso','critica','','2025-10-29 17:32:55','2026-07-01 11:32:00','2025-10-29 17:32:55','2025-10-29 17:32:55'),(18,5,'HUB DIGITAL','','en_progreso','media','','2025-10-29 17:33:18','2025-12-31 11:33:00','2025-10-29 17:33:18','2025-10-29 17:33:18'),(19,5,'ORGANIZACION DE CAPACITACIONES SOBRE COMUNICACION','COORDINACION CON FATIMA FERNANDEZ PARA LA IMPLEMENTACION DE LOS TALLERES SOBRE COMUNICACION ESTRATEGICA','completada','media','','2025-10-29 17:34:41','2025-10-28 11:34:00','2025-10-29 17:34:41','2025-10-29 17:34:41'),(20,5,'Semana de la agricultura digital en COSTA RICA','a Semana de la Agricultura Digital en Costa Rica fue un espacio de intercambio regional organizado por el IICA que reunió a expertos, ministros, organismos internacionales y representantes del sector privado para discutir los avances, desafíos y oportunidades de la transformación digital en la agricultura. La participación de Guatemala, a través del MAGA, tuvo una importancia estratégica, ya que permitió fortalecer alianzas con instituciones como la FAO, OIRSA, BID y SEGEPLAN, además de compartir las experiencias nacionales en trazabilidad con blockchain, uso de drones, sensores de suelo y desarrollo de dashboards institucionales.','completada','media','','2025-10-29 17:36:53','2025-08-08 11:36:00','2025-10-29 17:36:53','2025-10-29 17:36:53'),(21,5,'DASHBOARD CONGRESO','analisis de votacion en el congreso por parte de congresistas y bloques','completada','media','','2025-10-30 16:30:00','2025-11-04 10:29:00','2025-10-30 16:30:08','2025-11-03 19:54:43'),(23,4,'Implementación administrativa y financiera de key line','Se ha tenido reuniones con miguel duro para definir los costos del proyecto, desde hace dos semanas.  1.- Según ultima reunión, con Miguel Duro y el Ing. Benedicto Lucas (03 nov ) el equipo de riego esta realizando la integración del costeo individual de cada proyecto, mientras tanto el sigue coordinando reunión capacitación con la empresa mexicana, para realizar una capacitación de 10 personas de diprodu y 20 de extensión rural sobre key line; \r\n2.- se sostuvo reunión con el vice Jose Antonio López, (03 nov) y se acordó reunión de trabajo el dia viernes 7 con el equipo de riego para que le realice presentación.','en_progreso','critica','Economía Campesina, SNER, Suelos y Agua','2025-08-10 09:00:00','2025-12-31 23:59:00','2025-11-06 00:21:38','2025-11-06 19:29:18'),(24,4,'Revisión de terminos de referencia de auditoria externa de OIRSA SEDE Y DELEGACIONES; y Convenios y tratados','1.- Integrar la comisión de evaluación y recomendación de auditoria externa a la CIRSA','en_progreso','media','Otro','2025-11-05 09:00:00','2026-03-15 23:50:00','2025-11-06 01:06:46','2025-11-06 19:29:22'),(25,4,'APOYO Y SEGUIMIENTO A LA CONSTRUCCIÓN DEL MODELO PARA EL EMPODERAMIENTO ECONÓMICO DE LAS MUJERES RURALES CON ACTIVIDADES AGROPECUARIAS','1.- apoyo a la unidad de genero en el seguimiento a la construcción del modelo conceptual\r\n2.- articulación con la unidad de planeamiento, administración financiera en coordinación con la administración general','en_progreso','media','Otro','2025-11-06 01:33:19','2025-12-31 23:59:00','2025-11-06 01:33:19','2025-11-06 19:29:25'),(26,4,'seguimiento de la construcción del modelo conceptual del tobanik e implementación de modificación del reglamento y convenio chn','1.- se ha trabajo con el Hector Marroquin, planificación y auditoria interna los insumos y recomendaciones para construir el modelo conceptual del fondo tobanik\r\n2.- se encuentra pendiente reunión con el viceministro Jose Antonio López para definir cronograma y estrategia de trabajo con el banco CHN','en_progreso','critica','Otro','2025-11-06 13:44:09','2025-12-31 23:59:00','2025-11-06 13:44:09','2025-11-06 19:29:28'),(27,3,'Propuesta de Fertilizantes 2025','Realizar una propuesta considerando actores clave a nivel territorial.','en_progreso','media','Economía Campesina, SNER, Suelos y Agua','2025-11-11 18:32:58','2025-12-05 12:32:00','2025-11-11 18:32:58','2025-11-11 18:32:58'),(28,3,'Fortalecimiento Institucional ICTA 2025','Integrar y realizar proyectos de fortalecimiento:\r\na) Ganadería\r\nb) Frutales\r\nc) Riego','en_progreso','critica','Fortalecimiento del sector agropecuario ampliado','2025-11-11 18:35:20','2025-11-28 12:34:00','2025-11-11 18:35:20','2025-11-11 18:35:20'),(29,3,'Fortalecimiento ENCA','Realizar propuesta de inversión para el fortalecimiento de la educación agropecuaria a nivel medio.\r\n\r\n(Mejora de los sistemas de producción: agrícola, forestal y pecuaria)','en_progreso','media','Fortalecimiento del sector agropecuario ampliado','2025-11-11 18:38:26','2025-12-08 12:37:00','2025-11-11 18:38:26','2025-11-11 18:38:26'),(30,3,'Nota Conceptual de Pesca Sostenible','Definir lineas estrategicas de inversión a nivel nacional.','en_progreso','media','Sanidad agropecuaria','2025-11-11 18:42:15','2025-11-14 12:41:00','2025-11-11 18:42:15','2025-11-11 18:42:15'),(31,3,'Construcción de la Política Sectorial Agropecuaria','Realizar talleres de consulta y retroalimentación de la Política','en_progreso','media','Fortalecimiento del sector agropecuario ampliado','2025-11-11 18:47:00','2025-12-12 12:46:00','2025-11-11 18:47:00','2025-11-11 18:47:00'),(32,3,'Elaboración de Plan Estratégico de Cambio Climático','Gestionar, definir y elaborar Plan Estratégico','en_progreso','media','Fortalecimiento del sector agropecuario ampliado','2025-11-11 18:50:48','2025-12-31 12:50:00','2025-11-11 18:50:48','2025-11-11 18:50:48'),(33,3,'Participación en Comité Altiplano Resiliente','Realizar intervenciones estratégicas del MAGA en el marco del área de cobertura del Proyecto','en_progreso','media','Fortalecimiento del sector agropecuario ampliado','2025-11-11 18:52:46','2025-12-31 12:52:00','2025-11-11 18:52:46','2025-11-11 18:52:46'),(34,3,'Elaboración de Proyecto Granjas Agro acuícolas Sostenibles','Definir modelo de intervención que integren SA y Economía Campesina','completada','media','Economía Campesina, SNER, Suelos y Agua','2025-11-11 18:54:31','2025-11-14 12:54:00','2025-11-11 18:54:31','2025-11-11 18:54:31'),(35,3,'Participación Consejo Directivo ENCA','supervisar la organización y el funcionamiento de la ENCA, aprobar el presupuesto anual, aprobar el reglamento interno, y autorizar la ejecución de programas y proyectos','en_progreso','media','Fortalecimiento del sector agropecuario ampliado','2025-11-11 18:58:51','2025-12-31 12:58:00','2025-11-11 18:58:51','2025-11-11 18:58:51'),(36,3,'Nota Conceptual Central de Abastos','Definir prioridades de inversión para el sector agropecuario','en_progreso','media','Economía Campesina, SNER, Suelos y Agua','2025-11-11 19:00:29','2025-12-31 13:00:00','2025-11-11 19:00:29','2025-11-11 19:00:29'),(37,3,'Nota Conceptual Centros de Conservación de Especies Nativas','Definir áreas de manejo sostenible de recursos nativos en Guatemala','en_progreso','media','Economía Campesina, SNER, Suelos y Agua','2025-11-11 19:01:24','2025-12-31 13:01:00','2025-11-11 19:01:24','2025-11-11 19:01:24'),(38,3,'Fortalecimiento INDECA','Realizar inversiones en la mejora de funcionamiento y manejo de INDECA, así como la gestión de fondos adicionales para el cumplimiento de naturaleza institucional.','en_progreso','media','Fortalecimiento del sector agropecuario ampliado','2025-11-11 19:06:07','2025-12-31 13:05:00','2025-11-11 19:06:07','2025-11-11 19:06:07'),(39,3,'Nota Conceptual sobre Ganadería Sostenible en Peten','Ampliar cobertura sobre Ganadería Sostenible','en_progreso','media','Economía Campesina, SNER, Suelos y Agua','2025-11-11 19:12:01','2025-11-28 13:11:00','2025-11-11 19:12:01','2025-11-11 19:12:01'),(40,3,'Nota Conceptual GEF Plantas Nativas','Gestión de fondos adicionales para conservación de especies nativas','en_progreso','media','Economía Campesina, SNER, Suelos y Agua','2025-11-11 19:15:39','2025-12-31 13:15:00','2025-11-11 19:15:39','2025-11-11 19:15:39'),(41,5,'SOLUCIONAR PROBLEMAS LICENCIAMIENTO OCRET','La interrupción en los servidores de Ocret se originó debido a un licenciamiento inadecuado proporcionado por Human Brands, lo cual generó incompatibilidades técnicas y fallos en la validación de los servicios. Este error provocó que varias funciones críticas quedaran temporalmente fuera de operación. Actualmente estamos trabajando con el proveedor para corregir el licenciamiento, además de evaluar soluciones alternas que garanticen mayor estabilidad, continuidad operativa y prevención de incidentes similares en el futuro.','en_progreso','critica','Simplificación y trazabilidad de trámites','2025-11-19 18:14:00','2025-12-06 12:13:00','2025-11-19 18:14:00','2025-11-19 18:14:00'),(42,5,'revision y asesoramiento SIRO','mejoramiento para traslado a local del programa SIRO','en_progreso','critica','Simplificación y trazabilidad de trámites','2025-11-19 18:17:16','2025-12-06 12:17:00','2025-11-19 18:17:16','2025-11-19 18:17:16'),(43,5,'coordinacion comunicacion con fatima sobre temas geneticos','Espera de contacto de la señora ministra para canalizar info','en_progreso','media','Simplificación y trazabilidad de trámites','2025-11-19 18:22:44','2025-12-01 12:22:00','2025-11-19 18:22:44','2025-11-19 18:22:44'),(44,8,'Política Nacional del Fuego','La Política Nacional del Fuego es un instrumento de política pública de carácter estratégico, interinstitucional y multisectorial, orientado a regular, prevenir, manejar y utilizar de forma integral el fuego en el territorio nacional, reconociendo su doble naturaleza: como riesgo ambiental, social y económico, y como herramienta tradicional y productiva cuando es empleada de manera planificada, controlada y técnicamente adecuada.\r\n\r\nLa PNF parte del principio de que el fuego no puede abordarse únicamente desde una lógica de supresión de incendios, sino desde un enfoque de Manejo Integral del Fuego (MIF), que integra la prevención, el uso responsable, la preparación, la respuesta y la restauración post-incendio, considerando las dimensiones ambiental, productiva, cultural, climática y de gobernanza territorial.','en_progreso','critica','Economía Campesina, SNER, Suelos y Agua','2026-01-07 03:46:50','2027-05-31 12:00:00','2026-01-07 03:46:50','2026-01-07 03:46:50'),(45,8,'Política Institucional de Agricultura Urbana y Periurbana','La Política Institucional de Agricultura Urbana y Periurbana es un instrumento de política pública orientado a promover, ordenar y fortalecer la producción agroalimentaria dentro y alrededor de las ciudades, como una estrategia clave para mejorar la seguridad alimentaria y nutricional, fortalecer la resiliencia climática, dinamizar la economía familiar, y contribuir a la sostenibilidad ambiental y territorial.\r\n\r\nEsta política reconoce a la agricultura urbana y periurbana como una función estratégica del sistema agroalimentario, especialmente en contextos de crecimiento urbano acelerado, vulnerabilidad climática, pobreza urbana y presión sobre los sistemas rurales de producción y abastecimiento.','en_progreso','media','Economía Campesina, SNER, Suelos y Agua','2026-01-07 03:49:09','2026-12-31 12:00:00','2026-01-07 03:49:09','2026-01-07 03:49:09');
/*!40000 ALTER TABLE `actividades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sso_access_log`
--

DROP TABLE IF EXISTS `sso_access_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sso_access_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `sistema` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `token_exp` datetime DEFAULT NULL,
  `access_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_access_time` (`access_time`),
  KEY `idx_sistema` (`sistema`),
  CONSTRAINT `fk_sso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sso_access_log`
--

LOCK TABLES `sso_access_log` WRITE;
/*!40000 ALTER TABLE `sso_access_log` DISABLE KEYS */;
INSERT INTO `sso_access_log` VALUES (1,1,'actividades_despacho','181.174.105.54','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 07:33:27','2025-11-19 23:42:30'),(2,1,'actividades_despacho','190.14.140.185','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0','2025-11-20 07:34:42','2025-11-19 23:42:41');
/*!40000 ALTER TABLE `sso_access_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('administrador','tecnico') NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin','$2y$10$Q9uIee3LUUhQctLkwjLqx.AWXmU/33CeuzY.U.wRwLyDZn26Fxmzm','administrador','activo','2025-10-27 18:28:54','2025-10-27 18:39:41'),(2,'ANA GUERRA','ana.guerra','$2y$10$Q9uIee3LUUhQctLkwjLqx.AWXmU/33CeuzY.U.wRwLyDZn26Fxmzm','tecnico','activo','2025-10-27 18:33:33','2025-10-27 18:39:41'),(3,'ARMANDO MARTINEZ','armando.martinez','$2y$10$Q9uIee3LUUhQctLkwjLqx.AWXmU/33CeuzY.U.wRwLyDZn26Fxmzm','tecnico','activo','2025-10-27 18:33:33','2025-10-27 18:39:41'),(4,'JAIRO SOLIS','jairo.solis','$2y$10$Q9uIee3LUUhQctLkwjLqx.AWXmU/33CeuzY.U.wRwLyDZn26Fxmzm','tecnico','activo','2025-10-27 18:33:33','2025-10-27 18:39:41'),(5,'LUIS PINEDA','luis.pineda','$2y$10$Q9uIee3LUUhQctLkwjLqx.AWXmU/33CeuzY.U.wRwLyDZn26Fxmzm','tecnico','activo','2025-10-27 18:33:33','2025-10-27 18:39:41'),(6,'MANUEL HENRY','manuel.henry','$2y$10$Q9uIee3LUUhQctLkwjLqx.AWXmU/33CeuzY.U.wRwLyDZn26Fxmzm','tecnico','activo','2025-10-27 18:33:33','2025-10-27 18:39:41'),(7,'YASSMIN ANDARAUS','yassmin.andaraus','$2y$10$Q9uIee3LUUhQctLkwjLqx.AWXmU/33CeuzY.U.wRwLyDZn26Fxmzm','tecnico','activo','2025-10-27 18:33:33','2025-10-27 18:39:41'),(8,'BENEDICTO LUCAS','blucas','$2y$10$dzukxWyQerfla.2IMoTG.uagaDdOq92NRji8hqTYy5taJlkOeCWS6','tecnico','activo','2025-11-03 19:50:12','2025-11-03 19:50:12');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'actividades_despacho'
--
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-09 15:44:16
