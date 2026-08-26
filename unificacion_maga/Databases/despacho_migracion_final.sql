-- Migración de Actividades Despacho con Prefijos (despacho_)
-- Limpiado de directivas BINLOG y GTID_PURGED
SET FOREIGN_KEY_CHECKS=0;

-- DROPS en orden inverso para evitar conflictos de claves foraneas en phpMyAdmin
DROP TABLE IF EXISTS `despacho_actividades_adjuntos`;
DROP TABLE IF EXISTS `despacho_actividades_seguimiento`;
DROP TABLE IF EXISTS `despacho_actividades`;
DROP TABLE IF EXISTS `despacho_tecnicos`;

-- 1. Tabla de Tecnicos
CREATE TABLE `despacho_tecnicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `cargo` varchar(100),
  `area` varchar(100),
  `rol` varchar(100),
  `email` varchar(150),
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Actividades
CREATE TABLE `despacho_actividades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tecnico_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text,
  `categoria` varchar(100),
  `estado` varchar(50) DEFAULT 'PENDIENTE',
  `prioridad` varchar(50) DEFAULT 'MEDIA',
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tecnico_id` (`tecnico_id`),
  CONSTRAINT `despacho_act_fk_1` FOREIGN KEY (`tecnico_id`) REFERENCES `despacho_tecnicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Seguimiento
CREATE TABLE `despacho_actividades_seguimiento` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `actividad_id` INT NOT NULL,
    `comentario` TEXT NOT NULL,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `despacho_seg_fk` FOREIGN KEY (`actividad_id`) REFERENCES `despacho_actividades`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Adjuntos
CREATE TABLE `despacho_actividades_adjuntos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `actividad_id` INT NOT NULL,
    `nombre_archivo` VARCHAR(255) NOT NULL,
    `url_archivo` VARCHAR(255) NOT NULL,
    `tipo_archivo` VARCHAR(50),
    `fecha_carga` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `despacho_adj_fk` FOREIGN KEY (`actividad_id`) REFERENCES `despacho_actividades`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Insertando Técnicos (Migrados de la antigua tabla usuarios)
INSERT INTO `despacho_tecnicos` (`id`, `nombre`, `rol`, `activo`) VALUES
(1, 'Administrador', 'administrador', 1),
(2, 'ANA GUERRA', 'tecnico', 1),
(3, 'ARMANDO MARTINEZ', 'tecnico', 1),
(4, 'JAIRO SOLIS', 'tecnico', 1),
(5, 'LUIS PINEDA', 'tecnico', 1),
(6, 'MANUEL HENRY', 'tecnico', 1),
(7, 'YASSMIN ANDARAUS', 'tecnico', 1),
(8, 'BENEDICTO LUCAS', 'tecnico', 1);

-- Insertando Actividades
INSERT INTO `despacho_actividades` (`id`, `tecnico_id`, `titulo`, `descripcion`, `estado`, `prioridad`, `categoria`, `fecha_inicio`, `fecha_fin`, `fecha_creacion`, `fecha_modificacion`) VALUES
(1,2,'Prueba','Descripcion de Prueba','EN PROGRESO','CRITICA','','2025-10-27 18:55:50',NULL,'2025-10-27 18:55:50','2025-11-03 21:41:50'),(5,5,'generacion de dashboards sobre prioridades y temas en redes sociales','Los dashboards de redes sociales del MAGA permiten monitorear en tiempo real la percepción ciudadana, el alcance comunicacional y las tendencias de conversación relacionadas con el Ministerio y sus principales programas. A través de herramientas de análisis avanzado e inteligencia artificial, estos tableros integran datos de plataformas como X, Facebook y TikTok para identificar temas emergentes, evaluar el impacto de campañas institucionales y detectar posibles focos de desinformación. Su objetivo es fortalecer la comunicación estratégica, brindar insumos a las unidades de prensa y planificación, y generar reportes visuales que faciliten la toma de decisiones basada en evidencia digital.','COMPLETADA','MEDIA','','2025-10-29 17:23:13','2025-09-30 11:22:00','2025-10-29 17:23:13','2025-10-29 17:23:13'),(6,5,'capacitacion a DIPLAN sobre herramientas de IA','La capacitación a DIPLAN sobre herramientas de inteligencia artificial tuvo como objetivo fortalecer las capacidades técnicas del personal en el uso de tecnologías emergentes aplicadas a la planificación y análisis institucional. Durante las sesiones se abordaron conceptos fundamentales de IA, automatización de tareas, generación de textos e informes asistidos por IA, análisis predictivo y uso de dashboards inteligentes para la toma de decisiones. La formación permitió que los equipos de DIPLAN comprendieran cómo integrar estas herramientas en sus procesos de planificación, evaluación de proyectos y elaboración de reportes, promoviendo una cultura de innovación y eficiencia dentro del MAGA.','COMPLETADA','MEDIA','','2025-10-29 17:24:16','2025-10-06 11:24:00','2025-10-29 17:24:16','2025-10-29 17:24:16'),(7,5,'Reuniones bilaterales MP','','EN PROGRESO','CRITICA','','2025-10-29 17:24:54','2025-12-30 11:24:00','2025-10-29 17:24:54','2025-10-29 17:24:54'),(8,5,'Reuniones CGC','','EN PROGRESO','CRITICA','','2025-10-29 17:25:23','2025-12-30 11:25:00','2025-10-29 17:25:23','2025-11-03 21:41:58'),(9,5,'CREACION DE DASHBOARDS SOBRE ALERTAS Y AMENAZAS','MEDIOS TRADICIONALES \r\nREDES SOCIALES','COMPLETADA','MEDIA','','2025-10-29 17:26:15','2025-09-23 11:26:00','2025-10-29 17:26:15','2025-10-29 17:26:15'),(10,5,'capacitacion sobre IA a CRC','La capacitación impartida a Catholic CRC estuvo enfocada en el uso responsable y estratégico de la inteligencia artificial y las herramientas digitales en contextos educativos y organizacionales. Durante la formación, se abordaron temas como la creación de contenidos asistidos por IA, análisis de información en redes sociales, detección de desinformación y seguridad digital básica.','COMPLETADA','MEDIA','','2025-10-29 17:27:23','2025-09-17 11:27:00','2025-10-29 17:27:23','2025-10-29 17:27:23'),(11,5,'organizacion dia de la agricultura digital','La organización del Día de la Agricultura Digital en Guatemala representó un esfuerzo interinstitucional liderado por el MAGA para posicionar la digitalización como eje central del desarrollo agropecuario nacional. Bajo la coordinación general del despacho superior y el equipo de Innovación y Tecnología, se gestionaron alianzas con instituciones como IICA, FAO, OIRSA, SEGEPLAN y la Unión Europea, logrando reunir a representantes del sector público, privado y académico. El evento incluyó conferencias magistrales, exhibiciones tecnológicas, demostraciones de drones, sensores y sistemas de trazabilidad con blockchain, así como la presentación de pilotos de agricultura inteligente. Su realización consolidó a Guatemala como referente regional en transformación digital agropecuaria y marcó el inicio de una agenda permanente de innovación en el sector.','COMPLETADA','MEDIA','','2025-10-29 17:28:33','2025-10-12 11:28:00','2025-10-29 17:28:33','2025-10-29 17:28:33'),(12,5,'GEOPORTAL','','EN PROGRESO','MEDIA','','2025-10-29 17:28:55','2025-11-28 11:28:00','2025-10-29 17:28:55','2025-11-03 21:42:04'),(13,5,'dashboard sobre tendencias del congreso','El dashboard sobre tendencias en el Congreso de la República fue diseñado para analizar de forma sistemática la conversación digital y mediática relacionada con la actividad legislativa en Guatemala. Este tablero recopila datos en tiempo real de redes sociales, portales de noticias y fuentes oficiales, permitiendo identificar los temas más discutidos, las posturas de los distintos bloques parlamentarios y la percepción ciudadana hacia iniciativas específicas. A través de métricas como sentimiento, alcance y frecuencia, el dashboard facilita un seguimiento estratégico de los debates, reformas y actores clave, ofreciendo al MAGA y a otras instituciones insumos valiosos para comprender el entorno político y anticipar posibles impactos en la agenda agropecuaria y de desarrollo rural.','COMPLETADA','MEDIA','','2025-10-29 17:29:00','2025-12-30 11:29:00','2025-10-29 17:29:49','2025-11-05 16:13:23'),(14,5,'coordinacion con COMUNICACION','','EN PROGRESO','CRITICA','','2025-10-29 17:30:55','2025-12-31 11:30:00','2025-10-29 17:30:55','2025-10-29 17:30:55'),(15,5,'coordinacion IT','coordinacion con equipo de IT para temas varios','EN PROGRESO','CRITICA','Simplificación y trazabilidad de trámites','2025-10-29 17:31:00','2025-12-31 11:31:00','2025-10-29 17:31:43','2025-11-19 18:16:13'),(16,5,'ANALISIS DE PERFILES','','EN PROGRESO','CRITICA','','2025-10-29 17:32:32','2025-12-31 11:32:00','2025-10-29 17:32:32','2025-10-29 17:32:32'),(17,5,'IMPLEMENTACION BLOCKCHAIN','','EN PROGRESO','CRITICA','','2025-10-29 17:32:55','2026-07-01 11:32:00','2025-10-29 17:32:55','2025-10-29 17:32:55'),(18,5,'HUB DIGITAL','','EN PROGRESO','MEDIA','','2025-10-29 17:33:18','2025-12-31 11:33:00','2025-10-29 17:33:18','2025-10-29 17:33:18'),(19,5,'ORGANIZACION DE CAPACITACIONES SOBRE COMUNICACION','COORDINACION CON FATIMA FERNANDEZ PARA LA IMPLEMENTACION DE LOS TALLERES SOBRE COMUNICACION ESTRATEGICA','COMPLETADA','MEDIA','','2025-10-29 17:34:41','2025-10-28 11:34:00','2025-10-29 17:34:41','2025-10-29 17:34:41'),(20,5,'Semana de la agricultura digital en COSTA RICA','a Semana de la Agricultura Digital en Costa Rica fue un espacio de intercambio regional organizado por el IICA que reunió a expertos, ministros, organismos internacionales y representantes del sector privado para discutir los avances, desafíos y oportunidades de la transformación digital en la agricultura. La participación de Guatemala, a través del MAGA, tuvo una importancia estratégica, ya que permitió fortalecer alianzas con instituciones como la FAO, OIRSA, BID y SEGEPLAN, además de compartir las experiencias nacionales en trazabilidad con blockchain, uso de drones, sensores de suelo y desarrollo de dashboards institucionales.','COMPLETADA','MEDIA','','2025-10-29 17:36:53','2025-08-08 11:36:00','2025-10-29 17:36:53','2025-10-29 17:36:53'),(21,5,'DASHBOARD CONGRESO','analisis de votacion en el congreso por parte de congresistas y bloques','COMPLETADA','MEDIA','','2025-10-30 16:30:00','2025-11-04 10:29:00','2025-10-30 16:30:08','2025-11-03 19:54:43'),(23,4,'Implementación administrativa y financiera de key line','Se ha tenido reuniones con miguel duro para definir los costos del proyecto, desde hace dos semanas.  1.- Según ultima reunión, con Miguel Duro y el Ing. Benedicto Lucas (03 nov ) el equipo de riego esta realizando la integración del costeo individual de cada proyecto, mientras tanto el sigue coordinando reunión capacitación con la empresa mexicana, para realizar una capacitación de 10 personas de diprodu y 20 de extensión rural sobre key line;

SET FOREIGN_KEY_CHECKS=1;
