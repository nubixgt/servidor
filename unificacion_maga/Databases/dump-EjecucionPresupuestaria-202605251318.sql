-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: db.vider.maga.aws    Database: EjecucionPresupuestaria
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
-- Table structure for table `bitacora`
--

DROP TABLE IF EXISTS `bitacora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacora` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `tabla_afectada` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_id` int NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `campos_modificados` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `bitacora_chk_1` CHECK (json_valid(`datos_anteriores`)),
  CONSTRAINT `bitacora_chk_2` CHECK (json_valid(`datos_nuevos`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bitacora`
--

LOCK TABLES `bitacora` WRITE;
/*!40000 ALTER TABLE `bitacora` DISABLE KEYS */;
INSERT INTO `bitacora` VALUES (1,1,'importacion',0,'INSERT',NULL,'{\"anio\": 2025, \"errores\": 31, \"tipo_hoja\": \"principal\", \"insertados\": 0, \"actualizados\": 0}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:12:59'),(2,1,'importacion',0,'INSERT',NULL,'{\"anio\": 2025, \"errores\": 0, \"tipo_hoja\": \"principal\", \"insertados\": 31, \"actualizados\": 0}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:20:59'),(3,1,'importacion',0,'INSERT',NULL,'{\"anio\": 2025, \"errores\": 0, \"tipo_hoja\": \"detalle\", \"insertados\": 91, \"actualizados\": 0}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:21:20'),(4,1,'importacion',0,'INSERT',NULL,'{\"anio\": 2025, \"errores\": 0, \"tipo_hoja\": \"ministerios\", \"insertados\": 14, \"actualizados\": 0}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:21:40'),(5,1,'importacion',0,'INSERT',NULL,'{\"anio\": 2026, \"errores\": 0, \"tipo_hoja\": \"principal\", \"insertados\": 31, \"actualizados\": 0}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:22:11'),(6,1,'importacion',0,'INSERT',NULL,'{\"anio\": 2026, \"errores\": 0, \"tipo_hoja\": \"detalle\", \"insertados\": 91, \"actualizados\": 0}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:23:46'),(7,1,'importacion',0,'INSERT',NULL,'{\"anio\": 2026, \"errores\": 0, \"tipo_hoja\": \"ministerios\", \"insertados\": 14, \"actualizados\": 0}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:24:03'),(8,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'191.98.195.146','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15','2026-02-09 18:29:33'),(9,1,'usuarios',1,'DELETE',NULL,'{\"tipo\": \"LOGOUT\", \"accion\": \"Cierre de sesión\"}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:39:17'),(10,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 18:54:33'),(11,1,'usuarios',1,'DELETE',NULL,'{\"tipo\": \"LOGOUT\", \"accion\": \"Cierre de sesión\"}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 19:02:45'),(12,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 19:02:51'),(13,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'172.225.30.129','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1','2026-02-09 19:04:11'),(14,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-09 22:39:43'),(15,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'190.106.222.13','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Safari/605.1.15','2026-02-10 04:14:41'),(16,4,'usuarios',4,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'172.225.30.141','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1','2026-02-10 12:36:36'),(17,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'104.28.94.86','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1','2026-02-11 02:47:09'),(18,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-11 04:51:21'),(19,5,'usuarios',5,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'200.119.172.138','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-11 13:48:46'),(20,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'190.143.186.95','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-12 03:39:57'),(21,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'181.174.104.202','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-20 21:27:20'),(22,1,'usuarios',1,'INSERT',NULL,'{\"tipo\": \"LOGIN\", \"accion\": \"Inicio de sesión\"}',NULL,'181.174.104.202','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-20 22:09:41'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'190.143.186.2','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-27 18:34:06'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'190.14.140.185','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0','2026-02-27 18:34:39'),(0,1,'usuarios',1,'DELETE',NULL,'{\"tipo\":\"LOGOUT\",\"accion\":\"Cierre de sesi\\u00f3n\"}',NULL,'190.14.140.185','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0','2026-02-27 18:35:00'),(0,1,'usuarios',1,'DELETE',NULL,'{\"tipo\":\"LOGOUT\",\"accion\":\"Cierre de sesi\\u00f3n\"}',NULL,'190.143.186.2','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-27 18:35:32'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'181.174.105.56','Mozilla/5.0 (iPhone; CPU iPhone OS 26_3_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/145.0.7632.108 Mobile/15E148 Safari/604.1','2026-03-02 23:45:06'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'181.174.105.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-04 17:14:26'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'191.98.195.146','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Safari/605.1.15','2026-03-05 18:24:49'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'181.174.105.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-05 18:28:46'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'181.174.105.56','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 19:16:44'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'190.14.142.88','Mozilla/5.0 (iPhone; CPU iPhone OS 26_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.151 Mobile/15E148 Safari/604.1','2026-03-23 20:18:51'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-24 05:55:00'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'186.33.2.215','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-13 20:21:18'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'181.174.105.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 00:16:47'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'181.174.105.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 01:05:36'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'181.174.105.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 02:05:34'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 03:09:31'),(0,1,'usuarios',1,'DELETE',NULL,'{\"tipo\":\"LOGOUT\",\"accion\":\"Cierre de sesi\\u00f3n\"}',NULL,'172.22.144.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 03:12:51'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 03:13:08'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-14 03:34:51'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1','2026-04-14 04:09:41'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.82','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1','2026-04-14 13:21:39'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:24:42'),(0,1,'usuarios',1,'DELETE',NULL,'{\"tipo\":\"LOGOUT\",\"accion\":\"Cierre de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:28:49'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:28:58'),(0,1,'usuarios',1,'DELETE',NULL,'{\"tipo\":\"LOGOUT\",\"accion\":\"Cierre de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:29:56'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:30:03'),(0,1,'usuarios',1,'DELETE',NULL,'{\"tipo\":\"LOGOUT\",\"accion\":\"Cierre de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:30:52'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:30:59'),(0,1,'usuarios',1,'DELETE',NULL,'{\"tipo\":\"LOGOUT\",\"accion\":\"Cierre de sesi\\u00f3n\"}',NULL,'172.22.128.196','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:48:33'),(0,5,'usuarios',5,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:54:19'),(0,5,'usuarios',5,'DELETE',NULL,'{\"tipo\":\"LOGOUT\",\"accion\":\"Cierre de sesi\\u00f3n\"}',NULL,'172.22.144.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 13:54:55'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 OPR/129.0.0.0','2026-04-14 21:09:26'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-22 00:56:39'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.149','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0','2026-04-22 20:21:51'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.27','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15','2026-05-02 06:52:21'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.27','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-07 17:31:35'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.128','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 18:18:46'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.198','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1','2026-05-18 18:18:55'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.198','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0','2026-05-18 20:31:14'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.167','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-05-22 18:08:07'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.128.167','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-22 18:08:38'),(0,1,'usuarios',1,'INSERT',NULL,'{\"tipo\":\"LOGIN\",\"accion\":\"Inicio de sesi\\u00f3n\"}',NULL,'172.22.144.128','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-25 16:38:46');
/*!40000 ALTER TABLE `bitacora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ejecucion_detalle`
--

DROP TABLE IF EXISTS `ejecucion_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ejecucion_detalle` (
  `id` int NOT NULL,
  `unidad_ejecutora_id` int NOT NULL,
  `grupo_gasto_id` int DEFAULT NULL,
  `fuente_financiamiento_id` int DEFAULT NULL,
  `tipo_registro` enum('Grupo de gasto','Fuente de financiamiento') COLLATE utf8mb4_unicode_ci NOT NULL,
  `anio` int NOT NULL DEFAULT '2025',
  `vigente` decimal(18,2) DEFAULT '0.00',
  `devengado` decimal(18,2) DEFAULT '0.00',
  `saldo_por_devengar` decimal(18,2) DEFAULT '0.00',
  `porcentaje_ejecucion` decimal(8,4) DEFAULT '0.0000',
  `porcentaje_relativo` decimal(8,4) DEFAULT '0.0000',
  `periodo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` date DEFAULT (curdate()),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ejecucion_detalle`
--

LOCK TABLES `ejecucion_detalle` WRITE;
/*!40000 ALTER TABLE `ejecucion_detalle` DISABLE KEYS */;
INSERT INTO `ejecucion_detalle` VALUES (1,1,1,NULL,'Grupo de gasto',2025,203451763.00,187599980.80,15851782.20,92.2086,28.9600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(2,1,2,NULL,'Grupo de gasto',2025,13489875.00,10735484.92,2754390.08,79.5818,1.9200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(3,1,3,NULL,'Grupo de gasto',2025,8452059.00,5937040.04,2515018.96,70.2437,1.2000,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(4,1,4,NULL,'Grupo de gasto',2025,7540662.00,4639812.81,2900849.19,61.5306,1.0700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(5,1,5,NULL,'Grupo de gasto',2025,300548160.00,292407428.56,8140731.44,97.2914,42.7800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(6,1,6,NULL,'Grupo de gasto',2025,131127590.00,131122277.00,5313.00,99.9959,18.6600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(7,1,8,NULL,'Grupo de gasto',2025,38000000.00,37937933.64,62066.36,99.8367,5.4100,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(8,1,NULL,1,'Fuente de financiamiento',2025,301855546.00,274554109.09,27301436.91,90.9555,42.9600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(9,1,NULL,2,'Fuente de financiamiento',2025,321359749.00,319154773.13,2204975.87,99.3139,45.7400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(10,1,NULL,3,'Fuente de financiamiento',2025,108000.00,0.00,108000.00,0.0000,0.0200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(11,1,NULL,4,'Fuente de financiamiento',2025,35000.00,0.00,35000.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(12,1,NULL,5,'Fuente de financiamiento',2025,2265186.00,2265184.61,1.39,99.9999,0.3200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(13,1,NULL,7,'Fuente de financiamiento',2025,5875000.00,5842260.13,32739.87,99.4427,0.8400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(14,1,NULL,8,'Fuente de financiamiento',2025,1125000.00,1117066.99,7933.01,99.2948,0.1600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(15,2,1,NULL,'Grupo de gasto',2025,10933191.00,10168307.78,764883.22,93.0040,65.9600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(16,2,2,NULL,'Grupo de gasto',2025,2191878.00,1371302.45,820575.55,62.5629,13.2200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(17,2,3,NULL,'Grupo de gasto',2025,990449.00,623909.32,366539.68,62.9926,5.9800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(18,2,4,NULL,'Grupo de gasto',2025,1872273.00,476144.00,1396129.00,25.4313,11.2900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(19,2,5,NULL,'Grupo de gasto',2025,55000.00,52015.20,2984.80,94.5731,0.3300,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(20,2,8,NULL,'Grupo de gasto',2025,533400.00,531734.30,1665.70,99.6877,3.2200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(21,2,NULL,1,'Fuente de financiamiento',2025,12425671.00,11407018.13,1018652.87,91.8020,74.9600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(22,2,NULL,3,'Fuente de financiamiento',2025,660000.00,404155.29,255844.71,61.2357,3.9800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(23,2,NULL,4,'Fuente de financiamiento',2025,1737847.00,970950.63,766896.37,55.8709,10.4800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(24,2,NULL,5,'Fuente de financiamiento',2025,1752673.00,441289.00,1311384.00,25.1781,10.5700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(25,3,1,NULL,'Grupo de gasto',2025,16828539.00,15933814.67,894724.33,94.6833,57.4400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(26,3,2,NULL,'Grupo de gasto',2025,2913627.00,2765667.02,147959.98,94.9218,9.9400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(27,3,3,NULL,'Grupo de gasto',2025,1423986.00,1344247.32,79738.68,94.4003,4.8600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(28,3,4,NULL,'Grupo de gasto',2025,3530320.00,3505555.19,24764.81,99.2985,12.0500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(29,3,5,NULL,'Grupo de gasto',2025,157470.00,157469.84,0.16,99.9999,0.5400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(30,3,8,NULL,'Grupo de gasto',2025,4445038.00,4445035.68,2.32,99.9999,15.1700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(31,3,NULL,1,'Fuente de financiamiento',2025,10108000.00,9861170.74,246829.26,97.5581,34.5000,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(32,3,NULL,3,'Fuente de financiamiento',2025,11699160.00,10998424.83,700735.17,94.0104,39.9300,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(33,3,NULL,4,'Fuente de financiamiento',2025,3977000.00,3798634.96,178365.04,95.5151,13.5700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(34,3,NULL,5,'Fuente de financiamiento',2025,1076669.00,1076668.59,0.41,100.0000,3.6700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(35,3,NULL,6,'Fuente de financiamiento',2025,2438151.00,2416890.60,21260.40,99.1280,8.3200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(36,4,1,NULL,'Grupo de gasto',2025,43579127.00,42099300.39,1479826.61,96.6043,10.3800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(37,4,2,NULL,'Grupo de gasto',2025,12143332.00,11357866.25,785465.75,93.5317,2.8900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(38,4,3,NULL,'Grupo de gasto',2025,350814299.00,349271672.60,1542626.40,99.5603,83.5500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(39,4,4,NULL,'Grupo de gasto',2025,6072528.00,1666763.00,4405765.00,27.4476,1.4500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(40,4,5,NULL,'Grupo de gasto',2025,597836.00,372372.38,225463.62,62.2867,0.1400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(41,4,8,NULL,'Grupo de gasto',2025,6687165.00,6606069.31,81095.69,98.7873,1.5900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(42,4,NULL,1,'Fuente de financiamiento',2025,11462019.00,10551224.26,910794.74,92.0538,2.7300,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(43,4,NULL,2,'Fuente de financiamiento',2025,402359740.00,399156056.67,3203683.33,99.2038,95.8200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(44,4,NULL,5,'Fuente de financiamiento',2025,329291.00,329291.00,0.00,100.0000,0.0800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(45,4,NULL,6,'Fuente de financiamiento',2025,5743237.00,1337472.00,4405765.00,23.2878,1.3700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(46,5,1,NULL,'Grupo de gasto',2025,36407130.00,34708909.67,1698220.33,95.3355,11.7400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(47,5,2,NULL,'Grupo de gasto',2025,57644279.00,55245809.97,2398469.03,95.8392,18.5900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(48,5,3,NULL,'Grupo de gasto',2025,157667437.00,115587141.82,42080295.18,73.3107,50.8300,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(49,5,4,NULL,'Grupo de gasto',2025,14310804.00,9739738.19,4571065.81,68.0586,4.6100,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(50,5,5,NULL,'Grupo de gasto',2025,1001821.00,991076.90,10744.10,98.9275,0.3200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(51,5,7,NULL,'Grupo de gasto',2025,20000000.00,17977061.33,2022938.67,89.8853,6.4500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(52,5,8,NULL,'Grupo de gasto',2025,23128724.00,22088127.06,1040596.94,95.5008,7.4600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(53,5,NULL,1,'Fuente de financiamiento',2025,230849391.00,181598126.75,49251264.25,78.6652,74.4300,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(54,5,NULL,5,'Fuente de financiamiento',2025,72848676.00,72848673.59,2.41,100.0000,23.4900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(55,5,NULL,6,'Fuente de financiamiento',2025,6462128.00,1891064.60,4571063.40,29.2638,2.0800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(56,6,1,NULL,'Grupo de gasto',2025,17072751.00,16506320.16,566430.84,96.6823,35.5000,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(57,6,2,NULL,'Grupo de gasto',2025,2869779.00,2363862.53,505916.47,82.3709,5.9700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(58,6,3,NULL,'Grupo de gasto',2025,9927815.00,8896238.67,1031576.33,89.6092,20.6400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(59,6,4,NULL,'Grupo de gasto',2025,16951171.00,15224126.00,1727045.00,89.8116,35.2500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(60,6,8,NULL,'Grupo de gasto',2025,1267627.00,1267624.93,2.07,99.9998,2.6400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(61,6,NULL,1,'Fuente de financiamiento',2025,31484972.00,29343265.29,2141706.71,93.1977,65.4700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(62,6,NULL,5,'Fuente de financiamiento',2025,1595058.00,1595058.00,0.00,100.0000,3.3200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(63,6,NULL,6,'Fuente de financiamiento',2025,15009113.00,13319849.00,1689264.00,88.7451,31.2100,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(64,7,1,NULL,'Grupo de gasto',2025,64987901.00,61678729.01,3309171.99,94.9080,68.8500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(65,7,2,NULL,'Grupo de gasto',2025,8401777.00,3688262.60,4713514.40,43.8986,8.9000,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(66,7,3,NULL,'Grupo de gasto',2025,8096664.00,4263005.23,3833658.77,52.6514,8.5800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(67,7,4,NULL,'Grupo de gasto',2025,3411096.00,1801374.27,1609721.73,52.8093,3.6100,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(68,7,5,NULL,'Grupo de gasto',2025,2594180.00,1363438.98,1230741.02,52.5576,2.7500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(69,7,8,NULL,'Grupo de gasto',2025,6898545.00,5509647.44,1388897.56,79.8668,7.3100,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(70,7,NULL,1,'Fuente de financiamiento',2025,18670074.00,17667689.29,1002384.71,94.6311,19.7800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(71,7,NULL,3,'Fuente de financiamiento',2025,40226728.00,29430077.29,10796650.71,73.1605,42.6200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(72,7,NULL,4,'Fuente de financiamiento',2025,32082265.00,29405316.68,2676948.32,91.6560,33.9900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(73,7,NULL,5,'Fuente de financiamiento',2025,637940.00,637939.08,0.92,99.9999,0.6800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(74,7,NULL,6,'Fuente de financiamiento',2025,2773156.00,1163435.19,1609720.81,41.9535,2.9400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(75,8,1,NULL,'Grupo de gasto',2025,145582624.00,122452284.11,23130339.89,84.1119,50.9400,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(76,8,2,NULL,'Grupo de gasto',2025,18651568.00,9532342.18,9119225.82,51.1075,6.5300,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(77,8,3,NULL,'Grupo de gasto',2025,23966956.00,9414811.59,14552144.41,39.2825,8.3900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(78,8,4,NULL,'Grupo de gasto',2025,35052828.00,2527559.06,32525268.94,7.2107,12.2700,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(79,8,5,NULL,'Grupo de gasto',2025,5933412.00,4859852.97,1073559.03,81.9065,2.0800,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(80,8,8,NULL,'Grupo de gasto',2025,56584764.00,54968450.92,1616313.08,97.1436,19.8000,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(81,8,NULL,1,'Fuente de financiamiento',2025,58144327.00,48966800.75,9177526.25,84.2160,20.3500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(82,8,NULL,2,'Fuente de financiamiento',2025,227503511.00,154788500.08,72715010.92,68.0379,79.6100,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(83,8,NULL,3,'Fuente de financiamiento',2025,18000.00,0.00,18000.00,0.0000,0.0100,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(84,8,NULL,4,'Fuente de financiamiento',2025,70000.00,0.00,70000.00,0.0000,0.0200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(85,8,NULL,5,'Fuente de financiamiento',2025,36314.00,0.00,36314.00,0.0000,0.0100,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(86,9,2,NULL,'Grupo de gasto',2025,18437387.00,13249250.70,5188136.30,71.8608,47.5900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(87,9,3,NULL,'Grupo de gasto',2025,912396.00,356599.38,555796.62,39.0838,2.3600,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(88,9,4,NULL,'Grupo de gasto',2025,1595000.00,0.00,1595000.00,0.0000,4.1200,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(89,9,6,NULL,'Grupo de gasto',2025,14739413.00,12239411.50,2500001.50,83.0387,38.0500,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(90,9,8,NULL,'Grupo de gasto',2025,3055217.00,2390258.22,664958.78,78.2353,7.8900,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(91,9,NULL,2,'Fuente de financiamiento',2025,38739413.00,28235519.80,10503893.20,72.8858,100.0000,NULL,'2026-02-09','2026-02-09 18:21:20','2026-02-09 18:21:20'),(92,1,1,NULL,'Grupo de gasto',2026,205461627.00,72243694.76,133217932.20,35.1616,36.4600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(93,1,2,NULL,'Grupo de gasto',2026,20489695.00,3581627.41,16908067.59,17.4801,3.6400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(94,1,3,NULL,'Grupo de gasto',2026,14704735.00,1422604.40,13282130.60,9.6745,2.6100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(95,1,4,NULL,'Grupo de gasto',2026,11291669.00,277032.47,11014636.53,2.4534,2.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(96,1,5,NULL,'Grupo de gasto',2026,251995360.00,78004606.07,173990753.90,30.9548,44.7100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(97,1,6,NULL,'Grupo de gasto',2026,41630240.00,15611338.00,26018902.00,37.5000,7.3900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(98,1,8,NULL,'Grupo de gasto',2026,18000000.00,7925235.35,10074764.65,44.0291,3.1900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(99,1,NULL,1,'Fuente de financiamiento',2026,251999716.00,76971249.20,175028466.80,30.5442,44.7100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(100,1,NULL,2,'Fuente de financiamiento',2026,288497610.00,101867506.79,186630103.20,35.3097,51.1900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(101,1,NULL,3,'Fuente de financiamiento',2026,108000.00,0.00,108000.00,0.0000,0.0200,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(102,1,NULL,4,'Fuente de financiamiento',2026,35000.00,0.00,35000.00,0.0000,0.0100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(103,1,NULL,5,'Fuente de financiamiento',2026,10000000.00,227382.47,9772617.53,2.2738,1.7700,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(104,1,NULL,7,'Fuente de financiamiento',2026,12933000.00,0.00,12933000.00,0.0000,2.2900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(105,1,NULL,8,'Fuente de financiamiento',2026,0.00,0.00,0.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-04-14 03:35:30'),(106,2,1,NULL,'Grupo de gasto',2026,10620760.00,3129317.72,7491442.28,29.4642,44.1600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(107,2,2,NULL,'Grupo de gasto',2026,3386477.00,291600.82,3094876.18,8.6107,14.0800,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(108,2,3,NULL,'Grupo de gasto',2026,905116.00,277796.72,627319.28,30.6918,3.7600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(109,2,4,NULL,'Grupo de gasto',2026,8100000.00,319867.00,7780133.00,3.9490,33.6800,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(110,2,5,NULL,'Grupo de gasto',2026,37000.00,0.00,37000.00,0.0000,0.1500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(111,2,8,NULL,'Grupo de gasto',2026,1000000.00,581109.54,418890.46,58.1110,4.1600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(112,2,NULL,1,'Fuente de financiamiento',2026,14889353.00,4283269.35,10606083.65,28.7673,61.9100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(113,2,NULL,3,'Fuente de financiamiento',2026,660000.00,0.00,660000.00,0.0000,2.7400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(114,2,NULL,4,'Fuente de financiamiento',2026,500000.00,86155.45,413844.55,17.2311,2.0800,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(115,2,NULL,5,'Fuente de financiamiento',2026,8000000.00,230267.00,7769733.00,2.8783,33.2600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(116,3,1,NULL,'Grupo de gasto',2026,16959829.00,6728972.02,10230856.98,39.6759,57.9500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(117,3,2,NULL,'Grupo de gasto',2026,4780953.00,833968.05,3946984.95,17.4436,16.3400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(118,3,3,NULL,'Grupo de gasto',2026,1304062.00,174857.75,1129204.25,13.4087,4.4600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(119,3,4,NULL,'Grupo de gasto',2026,2519681.00,45876.00,2473805.00,1.8207,8.6100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(120,3,5,NULL,'Grupo de gasto',2026,0.00,0.00,0.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-04-14 03:35:31'),(121,3,8,NULL,'Grupo de gasto',2026,3700925.00,3639611.68,61313.32,98.3433,12.6500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(122,3,NULL,1,'Fuente de financiamiento',2026,10533895.00,5719314.69,4814580.31,54.2944,35.9900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(123,3,NULL,3,'Fuente de financiamiento',2026,12698555.00,5477235.41,7221319.59,43.1327,43.3900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(124,3,NULL,4,'Fuente de financiamiento',2026,3533000.00,180859.40,3352140.60,5.1191,12.0700,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(125,3,NULL,5,'Fuente de financiamiento',2026,2500000.00,45876.00,2454124.00,1.8350,8.5400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(126,3,NULL,6,'Fuente de financiamiento',2026,0.00,0.00,0.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-04-14 03:35:31'),(127,4,1,NULL,'Grupo de gasto',2026,45195544.00,16994620.10,28200923.90,37.6024,8.9400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(128,4,2,NULL,'Grupo de gasto',2026,6374607.00,1016710.55,5357896.45,15.9494,1.2600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(129,4,3,NULL,'Grupo de gasto',2026,446707330.00,246501.30,446460828.70,0.0552,88.3400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(130,4,4,NULL,'Grupo de gasto',2026,2000000.00,0.00,2000000.00,0.0000,0.4000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(131,4,5,NULL,'Grupo de gasto',2026,515000.00,0.00,515000.00,0.0000,0.1000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(132,4,8,NULL,'Grupo de gasto',2026,4900000.00,1188743.97,3711256.03,24.2601,0.9700,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(133,4,NULL,1,'Fuente de financiamiento',2026,11678436.00,3672640.05,8005795.95,31.4480,2.3100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(134,4,NULL,2,'Fuente de financiamiento',2026,427014045.00,15773935.87,411240109.10,3.6940,84.4400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(135,4,NULL,5,'Fuente de financiamiento',2026,67000000.00,0.00,67000000.00,0.0000,13.2500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(136,4,NULL,6,'Fuente de financiamiento',2026,0.00,0.00,0.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-04-14 03:35:31'),(137,5,1,NULL,'Grupo de gasto',2026,43955471.00,14149807.11,29805663.89,32.1912,14.1100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(138,5,2,NULL,'Grupo de gasto',2026,50174531.00,699678.35,49474852.65,1.3945,16.1100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(139,5,3,NULL,'Grupo de gasto',2026,87801346.00,720712.49,87080633.51,0.8208,28.1900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(140,5,4,NULL,'Grupo de gasto',2026,105840895.00,0.00,105840895.00,0.0000,33.9900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(141,5,5,NULL,'Grupo de gasto',2026,1916250.00,1727673.35,188576.65,90.1591,0.6200,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(142,5,7,NULL,'Grupo de gasto',2026,20000000.00,0.00,20000000.00,0.0000,6.4200,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(143,5,8,NULL,'Grupo de gasto',2026,1740300.00,1731307.64,8992.36,99.4833,0.5600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(144,5,NULL,1,'Fuente de financiamiento',2026,205587898.00,19029178.94,186558719.10,9.2560,66.0100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(145,5,NULL,5,'Fuente de financiamiento',2026,105840895.00,0.00,105840895.00,0.0000,33.9900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(146,5,NULL,6,'Fuente de financiamiento',2026,0.00,0.00,0.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-04-14 03:35:31'),(147,6,1,NULL,'Grupo de gasto',2026,17108717.00,6372734.74,10735982.26,37.2485,43.1800,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(148,6,2,NULL,'Grupo de gasto',2026,3828989.00,704340.10,3124648.90,18.3949,9.6600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(149,6,3,NULL,'Grupo de gasto',2026,9189803.00,1651397.85,7538405.15,17.9699,23.2000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(150,6,4,NULL,'Grupo de gasto',2026,8098235.00,0.00,8098235.00,0.0000,20.4400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(151,6,8,NULL,'Grupo de gasto',2026,1365046.00,1330879.39,34166.61,97.4970,3.4500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(152,6,NULL,1,'Fuente de financiamiento',2026,31520938.00,10059352.08,21461585.92,31.9132,79.5600,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(153,6,NULL,5,'Fuente de financiamiento',2026,8098235.00,0.00,8098235.00,0.0000,20.4400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(154,6,NULL,6,'Fuente de financiamiento',2026,0.00,0.00,0.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-04-14 03:35:31'),(155,7,1,NULL,'Grupo de gasto',2026,70671565.00,22045348.46,48626216.54,31.1941,57.8200,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(156,7,2,NULL,'Grupo de gasto',2026,9341717.00,736625.06,8605091.94,7.8853,7.6400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(157,7,3,NULL,'Grupo de gasto',2026,7004857.00,732279.93,6272577.07,10.4539,5.7300,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(158,7,4,NULL,'Grupo de gasto',2026,30000000.00,133876.00,29866124.00,0.4463,24.5500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(159,7,5,NULL,'Grupo de gasto',2026,785570.00,621816.93,163753.07,79.1549,0.6400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(160,7,8,NULL,'Grupo de gasto',2026,4420350.00,3413795.82,1006554.18,77.2291,3.6200,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(161,7,NULL,1,'Fuente de financiamiento',2026,18658509.00,15526999.60,3131509.40,83.2167,15.2700,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(162,7,NULL,3,'Fuente de financiamiento',2026,26507758.00,7589496.07,18918261.93,28.6312,21.6900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(163,7,NULL,4,'Fuente de financiamiento',2026,47057792.00,4433370.53,42624421.47,9.4211,38.5000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(164,7,NULL,5,'Fuente de financiamiento',2026,30000000.00,133876.00,29866124.00,0.4463,24.5500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(165,7,NULL,6,'Fuente de financiamiento',2026,0.00,0.00,0.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-04-14 03:35:31'),(166,8,1,NULL,'Grupo de gasto',2026,167461878.00,59853127.66,107608750.30,35.7413,40.4500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(167,8,2,NULL,'Grupo de gasto',2026,80838821.00,2180529.78,78658291.22,2.6974,19.5300,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(168,8,3,NULL,'Grupo de gasto',2026,81917287.00,1943992.00,79973295.00,2.3731,19.7900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(169,8,4,NULL,'Grupo de gasto',2026,51531562.00,105850.00,51425712.00,0.2054,12.4500,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(170,8,5,NULL,'Grupo de gasto',2026,8038800.00,549633.60,7489166.40,6.8373,1.9400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(171,8,8,NULL,'Grupo de gasto',2026,24161217.00,16664464.72,7496752.28,68.9720,5.8400,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(172,8,NULL,1,'Fuente de financiamiento',2026,117198255.00,27047048.84,90151206.16,23.0780,27.6700,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(173,8,NULL,2,'Fuente de financiamiento',2026,278261545.00,54144698.92,224116846.10,19.4582,65.7000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(174,8,NULL,3,'Fuente de financiamiento',2026,18000.00,0.00,18000.00,0.0000,0.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(175,8,NULL,4,'Fuente de financiamiento',2026,70000.00,0.00,70000.00,0.0000,0.0200,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(176,8,NULL,5,'Fuente de financiamiento',2026,18401765.00,105850.00,18295915.00,0.5752,6.6100,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(177,9,2,NULL,'Grupo de gasto',2026,16705545.00,4637290.63,12068254.37,27.7590,26.5300,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(178,9,3,NULL,'Grupo de gasto',2026,3584011.00,69916.60,3514094.40,1.9508,5.6900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(179,9,4,NULL,'Grupo de gasto',2026,36265000.00,0.00,36265000.00,0.0000,57.5900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-04-14 03:35:31'),(180,9,6,NULL,'Grupo de gasto',2026,36265000.00,0.00,36265000.00,0.0000,57.5900,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(181,9,8,NULL,'Grupo de gasto',2026,6412444.00,0.00,6412444.00,0.0000,10.1800,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(182,9,NULL,2,'Fuente de financiamiento',2026,62967000.00,4707207.23,58259792.77,7.4757,100.0000,NULL,'2026-02-09','2026-02-09 18:23:46','2026-05-22 18:09:39'),(0,1,NULL,6,'Fuente de financiamiento',2026,28383.00,0.00,28383.00,0.0000,0.0700,NULL,'2026-04-14','2026-04-14 03:35:30','2026-05-22 18:09:39'),(0,6,5,NULL,'Grupo de gasto',2026,28383.00,0.00,28383.00,0.0000,0.0700,NULL,'2026-04-14','2026-04-14 03:35:31','2026-05-22 18:09:39');
/*!40000 ALTER TABLE `ejecucion_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ejecucion_ministerios`
--

DROP TABLE IF EXISTS `ejecucion_ministerios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ejecucion_ministerios` (
  `id` int NOT NULL,
  `ministerio_id` int NOT NULL,
  `anio` int NOT NULL DEFAULT '2025',
  `asignado` decimal(18,2) DEFAULT '0.00',
  `modificado` decimal(18,2) DEFAULT '0.00',
  `vigente` decimal(18,2) DEFAULT '0.00',
  `devengado` decimal(18,2) DEFAULT '0.00',
  `saldo_por_devengar` decimal(18,2) DEFAULT '0.00',
  `porcentaje_ejecucion` decimal(8,4) DEFAULT '0.0000',
  `porcentaje_relativo` decimal(8,4) DEFAULT '0.0000',
  `periodo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` date DEFAULT (curdate()),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ejecucion_ministerios`
--

LOCK TABLES `ejecucion_ministerios` WRITE;
/*!40000 ALTER TABLE `ejecucion_ministerios` DISABLE KEYS */;
INSERT INTO `ejecucion_ministerios` VALUES (1,1,2025,3859965720.00,2205938830.00,6065904550.00,1668927453.12,4396977097.00,27.5132,7.6373,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(2,2,2025,2879702000.00,889298000.00,3769000000.00,410984969.58,3358015030.00,10.9044,4.7454,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(3,3,2025,980000000.00,270400000.00,1250400000.00,185711147.77,1064688852.00,14.8521,1.5743,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(4,4,2025,8272774000.00,1481581600.00,9754355600.00,1799443270.60,7954912329.00,18.4476,12.2813,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(5,5,2025,2414418000.00,-304418000.00,2110000000.00,500487047.02,1609512953.00,23.7198,2.6566,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(6,6,2025,25649968000.00,941532000.00,26591500000.00,6692739888.78,19898760111.00,25.1687,33.4803,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(7,7,2025,505041000.00,85241343.00,590282343.00,90516070.32,499766272.70,15.3344,0.7432,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(8,8,2025,977678000.00,-336906000.00,640772000.00,81882659.52,558889340.50,12.7788,0.8068,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(9,9,2025,15199951000.00,1337727000.00,16537678000.00,3024470279.06,13513207721.00,18.2884,20.8219,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(10,10,2025,367000000.00,-23057000.00,343943000.00,51689301.02,292253699.00,15.0284,0.4330,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(11,11,2025,2592102000.00,-461332800.00,2130769200.00,219774541.63,1910994658.00,10.3143,2.6828,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(12,12,2025,106500000.00,158492366.00,264992366.00,20785730.25,244206635.80,7.8439,0.3336,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(13,13,2025,9929875000.00,-1585207308.00,8344667692.00,551486418.13,7793181274.00,6.6088,10.5064,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(14,14,2025,1001272000.00,28728000.00,1030000000.00,128126247.34,901873752.70,12.4394,1.2968,NULL,'2026-02-09','2026-02-09 18:21:40','2026-04-14 03:35:08'),(15,6,2026,25649968000.00,941532000.00,26591500000.00,8645731918.65,17945768081.00,32.5131,2.6000,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(16,4,2026,8272774000.00,1481581600.00,9754355600.00,2626626895.10,7127728705.00,26.9277,12.0100,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(17,1,2026,3859965720.00,1805938830.00,5665904550.00,2092897608.37,3573006942.00,36.9385,7.2200,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(18,7,2026,505041000.00,55241343.00,560282343.00,130821826.42,429460516.60,23.3493,0.6900,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(19,5,2026,2414418000.00,-304418000.00,2110000000.00,675857162.99,1434142837.00,32.0311,32.7500,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(20,9,2026,15199951000.00,1337727000.00,16537678000.00,4664287155.63,11873390844.00,28.2040,20.3700,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(21,12,2026,106500000.00,2158492366.00,2264992366.00,174610390.49,2090381976.00,7.7091,2.7900,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(22,3,2026,980000000.00,270400000.00,1250400000.00,258292848.99,992107151.00,20.6568,1.5400,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(23,10,2026,367000000.00,-23057000.00,343943000.00,72851371.66,271091628.30,21.1812,1.2700,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(24,14,2026,1001272000.00,28728000.00,1030000000.00,217682181.99,812317818.00,21.1342,0.7900,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(25,2,2026,2879702000.00,889298000.00,3769000000.00,815022930.82,2953977069.00,21.6244,4.6400,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(26,11,2026,2592102000.00,-519332800.00,2072769200.00,357311769.89,1715457430.00,17.2384,2.6200,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(27,8,2026,977678000.00,-336906000.00,640772000.00,137720287.65,503051712.40,21.4929,0.4200,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51'),(28,13,2026,9929875000.00,-2135207308.00,7794667692.00,1103899253.84,6690768438.00,14.1622,10.2800,NULL,'2026-02-09','2026-02-09 18:24:03','2026-05-22 18:09:51');
/*!40000 ALTER TABLE `ejecucion_ministerios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ejecucion_principal`
--

DROP TABLE IF EXISTS `ejecucion_principal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ejecucion_principal` (
  `id` int NOT NULL,
  `unidad_ejecutora_id` int DEFAULT NULL,
  `programa_id` int DEFAULT NULL,
  `grupo_gasto_id` int DEFAULT NULL,
  `fuente_financiamiento_id` int DEFAULT NULL,
  `tipo_ejecucion_id` int NOT NULL,
  `anio` int NOT NULL DEFAULT '2025',
  `asignado` decimal(18,2) DEFAULT '0.00',
  `modificado` decimal(18,2) DEFAULT '0.00',
  `vigente` decimal(18,2) DEFAULT '0.00',
  `devengado` decimal(18,2) DEFAULT '0.00',
  `saldo_por_devengar` decimal(18,2) DEFAULT '0.00',
  `porcentaje_ejecucion` decimal(8,4) DEFAULT '0.0000',
  `porcentaje_relativo` decimal(8,4) DEFAULT '0.0000',
  `porcentaje_ejecucion_al_dia` decimal(8,4) DEFAULT NULL,
  `periodo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` date DEFAULT (curdate()),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ejecucion_principal`
--

LOCK TABLES `ejecucion_principal` WRITE;
/*!40000 ALTER TABLE `ejecucion_principal` DISABLE KEYS */;
INSERT INTO `ejecucion_principal` VALUES (94,1,NULL,NULL,NULL,1,2025,545007427.00,157602682.00,702610109.00,670379957.77,32230151.23,95.4128,36.1100,89.1700,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(95,2,NULL,NULL,NULL,1,2025,24087270.00,-7511079.00,16576191.00,13223413.05,3352777.95,79.7735,0.8500,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(96,3,NULL,NULL,NULL,1,2025,31761000.00,-2462020.00,29298980.00,28151789.72,1147190.28,96.0845,1.5100,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(97,4,NULL,NULL,NULL,1,2025,766139591.00,-346245304.00,419894287.00,411374043.93,8520243.07,97.9709,21.5800,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(98,5,NULL,NULL,NULL,1,2025,402749965.00,-92589770.00,310160195.00,256337864.94,53822330.06,82.6469,15.9400,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(99,6,NULL,NULL,NULL,1,2025,51322530.00,-3233387.00,48089143.00,44258172.29,3830970.71,92.0336,2.4700,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(100,7,NULL,NULL,NULL,1,2025,122221417.00,-27831254.00,94390163.00,78304457.53,16085705.47,82.9583,4.8500,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(101,8,NULL,NULL,NULL,1,2025,588547800.00,-302775648.00,285772152.00,203755300.83,82016851.17,71.2999,14.6900,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(102,9,NULL,NULL,NULL,1,2025,60265000.00,-21525587.00,38739413.00,28993110.36,9746302.64,74.8414,1.9900,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(103,NULL,1,NULL,NULL,2,2025,288647129.00,-67312744.00,221334385.00,189800151.41,31534233.59,85.7527,11.3800,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(104,NULL,2,NULL,NULL,2,2025,1200060251.00,-515382971.00,684677280.00,633285022.46,51392257.54,92.4939,35.1900,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(105,NULL,3,NULL,NULL,2,2025,123231097.00,-40372722.00,82858375.00,75941585.26,6916789.74,91.6523,4.2600,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(106,NULL,4,NULL,NULL,2,2025,693191253.00,-175551109.00,517640144.00,403780406.55,113859737.50,78.0041,26.6100,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(107,NULL,5,NULL,NULL,2,2025,14419000.00,-4489971.00,9929029.00,8751258.01,1177770.99,88.1381,0.5100,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(108,NULL,6,NULL,NULL,2,2025,272553270.00,156538150.00,429091420.00,423219686.73,5871733.27,98.6316,22.0600,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(109,NULL,NULL,1,NULL,3,2025,544598985.00,-5755959.00,538843026.00,491147646.59,47695379.41,91.1486,27.7000,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(110,NULL,NULL,2,NULL,3,2025,270940417.00,-134196915.00,136743502.00,110988494.76,25755007.24,81.1655,7.0300,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(111,NULL,NULL,3,NULL,3,2025,1001722968.00,-439470907.00,562252061.00,495773610.39,66478450.61,88.1764,28.9000,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(112,NULL,NULL,4,NULL,3,2025,370364764.00,-280028082.00,90336682.00,39581072.52,50755609.48,43.8151,4.6400,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(113,NULL,NULL,5,NULL,3,2025,244711240.00,66176639.00,310887879.00,300203654.83,10684224.17,96.5633,15.9800,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(114,NULL,NULL,6,NULL,3,2025,77895240.00,67971763.00,145867003.00,143361688.50,2505314.50,98.2825,7.5000,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(115,NULL,NULL,7,NULL,3,2025,20000000.00,0.00,20000000.00,17977061.33,2022938.67,89.8853,1.0300,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(116,NULL,NULL,8,NULL,3,2025,61868386.00,78732094.00,140600480.00,135744881.50,4855598.50,96.5465,7.2300,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(117,NULL,NULL,NULL,1,4,2025,675000000.00,0.00,675000000.00,583949404.30,91050595.70,86.5110,34.6900,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(118,NULL,NULL,NULL,2,4,2025,1011488000.00,-21525587.00,989962413.00,902092440.24,87869972.76,91.1239,50.8800,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(119,NULL,NULL,NULL,3,4,2025,76509000.00,-23797112.00,52711888.00,40832657.41,11879230.59,77.4638,2.7100,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(120,NULL,NULL,NULL,4,4,2025,14105000.00,23797112.00,37902112.00,34174902.27,3727209.73,90.1662,1.9500,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(121,NULL,NULL,NULL,5,4,2025,815000000.00,-734458193.00,80541807.00,79194103.87,1347703.13,98.3267,4.1400,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(122,NULL,NULL,NULL,6,4,2025,0.00,102412413.00,102412413.00,87575275.21,14837137.79,85.5124,5.2600,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(123,NULL,NULL,NULL,7,4,2025,0.00,5875000.00,5875000.00,5842260.13,32739.87,99.4427,0.3000,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(124,NULL,NULL,NULL,8,4,2025,0.00,1125000.00,1125000.00,1117066.99,7933.01,99.2948,0.0600,NULL,NULL,'2026-02-09','2026-02-09 18:20:59','2026-02-09 18:20:59'),(125,1,NULL,NULL,NULL,1,2026,545007427.00,18565899.00,563573326.00,179066138.46,384507187.50,31.7734,27.1900,17.2400,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(126,2,NULL,NULL,NULL,1,2026,24087270.00,-37917.00,24049353.00,4599691.80,19449661.20,19.1261,1.1600,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(127,3,NULL,NULL,NULL,1,2026,31761000.00,-2495550.00,29265450.00,11423285.50,17842164.50,39.0333,1.4100,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(128,4,NULL,NULL,NULL,1,2026,766139591.00,-260447110.00,505692481.00,19446575.92,486245905.10,3.8455,24.4000,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(129,5,NULL,NULL,NULL,1,2026,402749965.00,-91321172.00,311428793.00,19029178.94,292399614.10,6.1103,15.0200,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(130,6,NULL,NULL,NULL,1,2026,51322530.00,-11703357.00,39619173.00,10059352.08,29559820.92,25.3901,1.9100,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(131,7,NULL,NULL,NULL,1,2026,122221417.00,2642.00,122224059.00,27683742.20,94540316.80,22.6500,5.9000,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(132,8,NULL,NULL,NULL,1,2026,588547800.00,-174598235.00,413949565.00,81297597.76,332651967.20,19.6395,19.9700,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(133,9,NULL,NULL,NULL,1,2026,60265000.00,2702000.00,62967000.00,4707207.23,58259792.77,7.4757,3.0400,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(134,NULL,1,NULL,NULL,2,2026,288647129.00,-67645674.00,221001455.00,65895097.36,155106357.60,29.8166,10.6600,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(135,NULL,2,NULL,NULL,2,2026,1200060251.00,-376767100.00,823293151.00,104803550.79,718489600.20,12.7298,39.7200,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(136,NULL,3,NULL,NULL,2,2026,123231097.00,-12083110.00,111147987.00,25485510.83,85662476.17,22.9293,5.3600,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(137,NULL,4,NULL,NULL,2,2026,693191253.00,-82488466.00,610702787.00,62024410.79,548678376.20,10.1562,29.4600,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(138,NULL,5,NULL,NULL,2,2026,14419000.00,0.00,14419000.00,4037620.87,10381379.13,28.0021,0.7000,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(139,NULL,6,NULL,NULL,2,2026,272553270.00,19651550.00,292204820.00,95066579.25,197138240.80,32.5342,14.1000,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(140,NULL,NULL,1,NULL,3,2026,544598985.00,32836406.00,577435391.00,201517622.57,375917768.40,34.8987,27.8600,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(141,NULL,NULL,2,NULL,3,2026,270940417.00,-75019082.00,195921335.00,14682370.75,181238964.30,7.4940,9.4500,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(142,NULL,NULL,3,NULL,3,2026,1001722968.00,-348604421.00,653118547.00,7240059.04,645878488.00,1.1085,31.5100,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(143,NULL,NULL,4,NULL,3,2026,370364764.00,-150982722.00,219382042.00,882501.47,218499540.50,0.4023,10.5800,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(144,NULL,NULL,5,NULL,3,2026,244711240.00,18605123.00,263316363.00,80903729.95,182412633.10,30.7249,12.7000,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(145,NULL,NULL,6,NULL,3,2026,77895240.00,0.00,77895240.00,15611338.00,62283902.00,20.0415,3.7600,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(146,NULL,NULL,7,NULL,3,2026,20000000.00,0.00,20000000.00,0.00,20000000.00,0.0000,0.9600,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(147,NULL,NULL,8,NULL,3,2026,61868386.00,3831896.00,65700282.00,36475148.11,29225133.89,55.5175,3.1700,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(148,NULL,NULL,NULL,1,4,2026,675000000.00,-12933000.00,662067000.00,144674990.49,517392009.50,21.8520,31.9400,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(149,NULL,NULL,NULL,2,4,2026,1011488000.00,45252200.00,1056740200.00,144625979.76,912114220.20,13.6860,50.9800,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(150,NULL,NULL,NULL,3,4,2026,76509000.00,-36516687.00,39992313.00,11096963.38,28895349.62,27.7477,1.9300,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(151,NULL,NULL,NULL,4,4,2026,14105000.00,37090792.00,51195792.00,4700385.38,46495406.62,9.1812,2.4700,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(152,NULL,NULL,NULL,5,4,2026,815000000.00,-565159105.00,249840895.00,743251.47,249097643.50,0.2975,12.0500,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(153,NULL,NULL,NULL,6,4,2026,0.00,0.00,0.00,0.00,0.00,0.0000,0.0000,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-04-14 03:35:21'),(154,NULL,NULL,NULL,7,4,2026,0.00,12933000.00,12933000.00,0.00,12933000.00,0.0000,0.6200,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-05-22 18:09:25'),(155,NULL,NULL,NULL,8,4,2026,0.00,0.00,0.00,0.00,0.00,0.0000,0.0000,NULL,NULL,'2026-02-09','2026-02-09 18:22:11','2026-04-14 03:35:21');
/*!40000 ALTER TABLE `ejecucion_principal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuentes_financiamiento`
--

DROP TABLE IF EXISTS `fuentes_financiamiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fuentes_financiamiento` (
  `id` int NOT NULL,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuentes_financiamiento`
--

LOCK TABLES `fuentes_financiamiento` WRITE;
/*!40000 ALTER TABLE `fuentes_financiamiento` DISABLE KEYS */;
INSERT INTO `fuentes_financiamiento` VALUES (1,'11','Código 11',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(2,'21','Código 21',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(3,'31','Código 31',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(4,'32','Código 32',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(5,'41','Código 41',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(6,'51','Código 51',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(7,'52','Código 52',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(8,'61','Código 61',1,'2026-02-09 18:12:59','2026-02-09 18:12:59');
/*!40000 ALTER TABLE `fuentes_financiamiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupos_gasto`
--

DROP TABLE IF EXISTS `grupos_gasto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupos_gasto` (
  `id` int NOT NULL,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos_gasto`
--

LOCK TABLES `grupos_gasto` WRITE;
/*!40000 ALTER TABLE `grupos_gasto` DISABLE KEYS */;
INSERT INTO `grupos_gasto` VALUES (1,'000','Código 000',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(2,'100','Código 100',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(3,'200','Código 200',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(4,'300','Código 300',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(5,'400','Código 400',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(6,'500','Código 500',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(7,'600','Código 600',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(8,'900','Código 900',1,'2026-02-09 18:12:59','2026-02-09 18:12:59');
/*!40000 ALTER TABLE `grupos_gasto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ministerios`
--

DROP TABLE IF EXISTS `ministerios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ministerios` (
  `id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `siglas` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ministerios`
--

LOCK TABLES `ministerios` WRITE;
/*!40000 ALTER TABLE `ministerios` DISABLE KEYS */;
INSERT INTO `ministerios` VALUES (1,'MINISTERIO DE LA DEFENSA NACIONAL','MDN',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(2,'MINISTERIO DE DESARROLLO SOCIAL','MDS',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(3,'MINISTERIO DE RELACIONES EXTERIORES','MRE',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(4,'MINISTERIO DE GOBERNACIÓN','MG',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(5,'MINISTERIO DE TRABAJO Y PREVISIÓN SOCIAL','MTPS',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(6,'MINISTERIO DE EDUCACIÓN','ME',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(7,'MINISTERIO DE FINANZAS PÚBLICAS','MFP',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(8,'MINISTERIO DE ECONOMÍA','ME',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(9,'MINISTERIO DE SALUD PÚBLICA Y ASISTENCIA SOCIAL','MSPAS',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(10,'MINISTERIO DE AMBIENTE Y RECURSOS NATURALES','MARN',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(11,'MINISTERIO DE AGRICULTURA, GANADERÍA Y ALIMENTACIÓN','MAGA',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(12,'MINISTERIO DE ENERGÍA Y MINAS','MEM',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(13,'MINISTERIO DE  COMUNICACIONES, INFRAESTRUCTURA Y VIVIENDA','MCIV',1,'2026-02-09 18:21:40','2026-02-09 18:21:40'),(14,'MINISTERIO DE CULTURA Y DEPORTES','MCD',1,'2026-02-09 18:21:40','2026-02-09 18:21:40');
/*!40000 ALTER TABLE `ministerios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programas`
--

DROP TABLE IF EXISTS `programas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `programas` (
  `id` int NOT NULL,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programas`
--

LOCK TABLES `programas` WRITE;
/*!40000 ALTER TABLE `programas` DISABLE KEYS */;
INSERT INTO `programas` VALUES (1,'01','Código 01',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(2,'11','Código 11',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(3,'12','Código 12',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(4,'13','Código 13',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(5,'14','Código 14',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(6,'99','Código 99',1,'2026-02-09 18:12:59','2026-02-09 18:12:59');
/*!40000 ALTER TABLE `programas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_ejecucion`
--

DROP TABLE IF EXISTS `tipos_ejecucion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_ejecucion` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_ejecucion`
--

LOCK TABLES `tipos_ejecucion` WRITE;
/*!40000 ALTER TABLE `tipos_ejecucion` DISABLE KEYS */;
INSERT INTO `tipos_ejecucion` VALUES (1,'Unidades Ejecutoras',1),(2,'Programas',1),(3,'Grupos de Gasto',1),(4,'Fuentes de Financiamiento',1);
/*!40000 ALTER TABLE `tipos_ejecucion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidades_ejecutoras`
--

DROP TABLE IF EXISTS `unidades_ejecutoras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidades_ejecutoras` (
  `id` int NOT NULL,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_corto` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidades_ejecutoras`
--

LOCK TABLES `unidades_ejecutoras` WRITE;
/*!40000 ALTER TABLE `unidades_ejecutoras` DISABLE KEYS */;
INSERT INTO `unidades_ejecutoras` VALUES (1,'201','Código 201','201',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(2,'202','Código 202','202',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(3,'203','Código 203','203',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(4,'204','Código 204','204',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(5,'205','Código 205','205',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(6,'208','Código 208','208',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(7,'209','Código 209','209',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(8,'210','Código 210','210',1,'2026-02-09 18:12:59','2026-02-09 18:12:59'),(9,'213','Código 213','213',1,'2026-02-09 18:12:59','2026-02-09 18:12:59');
/*!40000 ALTER TABLE `unidades_ejecutoras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','editor','viewer') COLLATE utf8mb4_unicode_ci DEFAULT 'viewer',
  `activo` tinyint(1) DEFAULT '1',
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','admin@maga.gob.gt','$2y$10$KfJhJ1FVcctPrsr91Eu6oeeQFsvmBFePEnJK6xukapxVbKOs9PcjS','admin',1,'2026-05-25 16:38:46','2026-01-07 16:22:58','2026-05-25 16:38:46'),(2,'prueba','usuarioprueba@maga.gt','$2y$10$m5x/ez1cJCnsAD8HH3PkxOQmoh9idKYy/NQ34sbsbVazxCmy9YiSS','viewer',1,'2026-01-09 17:43:04','2026-01-09 16:52:25','2026-01-09 17:43:04'),(3,'Pedro Daniel López','plopez@maga.com','$2y$10$KxR9tYkquYTf9Kf2iYFvHuy7TiqnrAevAFAd5oJD3NMoQPxCSK/eq','admin',1,'2026-01-09 17:08:05','2026-01-09 17:07:43','2026-01-09 17:08:05'),(4,'Luis Pineda','lpineda@maga.gob.gt','$2y$10$oBlMGiUDdzkmxUhVMP3LEeKXcLDZNh2HOBRFiGxtrkdGA5SesKMMi','admin',1,'2026-02-10 12:36:36','2026-01-09 17:09:00','2026-02-10 12:36:36'),(5,'Maria Fernanda','ministra@maga.gob.gt','$2y$10$bID/LBF4.Ye/HLndibl8PuqrFQ1UVSxY4EZVBG.kDxP66Za6fIDJG','admin',1,'2026-04-14 13:54:19','2026-01-15 19:42:49','2026-04-14 13:54:19');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `v_ejecucion_ministerios`
--

DROP TABLE IF EXISTS `v_ejecucion_ministerios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `v_ejecucion_ministerios` (
  `id` int DEFAULT NULL,
  `anio` int DEFAULT NULL,
  `ministerio` varchar(255) DEFAULT NULL,
  `siglas` varchar(20) DEFAULT NULL,
  `asignado` decimal(18,2) DEFAULT NULL,
  `modificado` decimal(18,2) DEFAULT NULL,
  `vigente` decimal(18,2) DEFAULT NULL,
  `devengado` decimal(18,2) DEFAULT NULL,
  `saldo_por_devengar` decimal(18,2) DEFAULT NULL,
  `porcentaje_ejecucion` decimal(8,4) DEFAULT NULL,
  `porcentaje_relativo` decimal(8,4) DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `v_ejecucion_ministerios`
--

LOCK TABLES `v_ejecucion_ministerios` WRITE;
/*!40000 ALTER TABLE `v_ejecucion_ministerios` DISABLE KEYS */;
/*!40000 ALTER TABLE `v_ejecucion_ministerios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `v_ejecucion_principal`
--

DROP TABLE IF EXISTS `v_ejecucion_principal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `v_ejecucion_principal` (
  `id` int DEFAULT NULL,
  `anio` int DEFAULT NULL,
  `unidad_codigo` varchar(10) DEFAULT NULL,
  `unidad_nombre` varchar(255) DEFAULT NULL,
  `unidad_corto` varchar(100) DEFAULT NULL,
  `programa_codigo` varchar(10) DEFAULT NULL,
  `programa_nombre` varchar(255) DEFAULT NULL,
  `grupo_gasto_codigo` varchar(10) DEFAULT NULL,
  `grupo_gasto_nombre` varchar(255) DEFAULT NULL,
  `fuente_codigo` varchar(10) DEFAULT NULL,
  `fuente_nombre` varchar(255) DEFAULT NULL,
  `tipo_ejecucion` varchar(100) DEFAULT NULL,
  `asignado` decimal(18,2) DEFAULT NULL,
  `modificado` decimal(18,2) DEFAULT NULL,
  `vigente` decimal(18,2) DEFAULT NULL,
  `devengado` decimal(18,2) DEFAULT NULL,
  `saldo_por_devengar` decimal(18,2) DEFAULT NULL,
  `porcentaje_ejecucion` decimal(8,4) DEFAULT NULL,
  `porcentaje_relativo` decimal(8,4) DEFAULT NULL,
  `porcentaje_ejecucion_al_dia` decimal(8,4) DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `progra_uni_gasto_finan` varchar(268) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `v_ejecucion_principal`
--

LOCK TABLES `v_ejecucion_principal` WRITE;
/*!40000 ALTER TABLE `v_ejecucion_principal` DISABLE KEYS */;
/*!40000 ALTER TABLE `v_ejecucion_principal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'EjecucionPresupuestaria'
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

-- Dump completed on 2026-05-25 13:18:59
