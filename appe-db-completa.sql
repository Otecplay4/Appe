-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: appe
-- ------------------------------------------------------
-- Server version	8.0.46

--
-- Base de datos y usuario de aplicación
--
-- Permiten importar este archivo en cualquier XAMPP recién instalado sin
-- pasos manuales. La clave de 'app_appe' debe coincidir con 'clave' en
-- config/config.php. ATENCIÓN: importar este archivo reemplaza las tablas
-- existentes (DROP TABLE) y se pierden los datos cargados.
--

CREATE DATABASE IF NOT EXISTS `appe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `appe`;

CREATE USER IF NOT EXISTS 'app_appe'@'localhost' IDENTIFIED BY '371365a1565409e5c0f7bd02d7007303';
CREATE USER IF NOT EXISTS 'app_appe'@'127.0.0.1' IDENTIFIED BY '371365a1565409e5c0f7bd02d7007303';
ALTER USER 'app_appe'@'localhost' IDENTIFIED BY '371365a1565409e5c0f7bd02d7007303';
ALTER USER 'app_appe'@'127.0.0.1' IDENTIFIED BY '371365a1565409e5c0f7bd02d7007303';
GRANT SELECT, INSERT, UPDATE, DELETE ON `appe`.* TO 'app_appe'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON `appe`.* TO 'app_appe'@'127.0.0.1';
FLUSH PRIVILEGES;

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

--
-- Table structure for table `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria` (
  `id_auditoria` int NOT NULL AUTO_INCREMENT,
  `id_actor` int DEFAULT NULL,
  `rol` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'anonimo',
  `accion` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `entidad` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `id_entidad` int DEFAULT NULL,
  `detalle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_fecha` (`fecha`),
  KEY `idx_auditoria_entidad` (`entidad`,`id_entidad`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria`
--

LOCK TABLES `auditoria` WRITE;
/*!40000 ALTER TABLE `auditoria` DISABLE KEYS */;
INSERT INTO `auditoria` VALUES (1,1,'entrenador','inicio_sesion','entrenador',1,'Acceso correcto','127.0.0.1','2026-10-21 18:37:35'),(2,1,'entrenador','inicio_sesion','entrenador',1,'Acceso correcto','127.0.0.1','2026-10-21 19:47:58'),(3,1,'entrenador','inicio_sesion','entrenador',1,'Acceso correcto','127.0.0.1','2026-10-22 07:47:00'),(4,NULL,'anonimo','alta_cliente','cliente',2,'mjleal999@gmail.com','10.48.33.92','2026-10-22 08:00:29'),(5,2,'cliente','inicio_sesion','cliente',2,'Acceso correcto','10.48.33.92','2026-10-22 08:00:48'),(6,NULL,'anonimo','alta_cliente','cliente',3,'leandro@gmail.com','10.48.33.87','2026-10-22 08:02:17'),(7,2,'cliente','cierre_sesion','cliente',2,'Cierre de sesión','10.48.33.92','2026-10-22 08:15:30'),(8,2,'cliente','inicio_sesion','cliente',2,'Acceso correcto','192.168.1.53','2026-10-22 13:23:26'),(9,2,'cliente','inicio_sesion','cliente',2,'Acceso correcto','192.168.1.53','2026-10-22 13:23:53'),(10,2,'admin','inicio_sesion','entrenador',2,'Acceso correcto','192.168.1.53','2026-10-22 13:44:10');
/*!40000 ALTER TABLE `auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `id_gimnasio` int DEFAULT NULL,
  `id_plan` int DEFAULT NULL,
  `id_rutina` int DEFAULT NULL,
  `id_entrenador` int DEFAULT NULL,
  `nombre` varchar(70) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(70) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `contraseña` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Hash bcrypt (password_hash). Nunca en texto plano.',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_alta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `uk_cliente_correo` (`correo`),
  KEY `idx_cliente_entrenador` (`id_entrenador`),
  KEY `idx_cliente_rutina` (`id_rutina`),
  KEY `fk_cliente_gimnasio` (`id_gimnasio`),
  KEY `fk_cliente_plan` (`id_plan`),
  CONSTRAINT `fk_cliente_entrenador` FOREIGN KEY (`id_entrenador`) REFERENCES `entrenador` (`id_entrenador`) ON DELETE SET NULL,
  CONSTRAINT `fk_cliente_gimnasio` FOREIGN KEY (`id_gimnasio`) REFERENCES `gimnasio` (`id_gimnasio`) ON DELETE SET NULL,
  CONSTRAINT `fk_cliente_plan` FOREIGN KEY (`id_plan`) REFERENCES `plan` (`id_plan`) ON DELETE SET NULL,
  CONSTRAINT `fk_cliente_rutina` FOREIGN KEY (`id_rutina`) REFERENCES `rutina` (`id_rutina`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,1,1,1,1,'Camila','Rodríguez','094445556','cliente@olympia.local','$2y$10$lIEVcULLUS1Hch0Js13wgOiXgts4/Npk8qA8TQT.VadbD1Lo1BIti',1,'2026-10-21 18:36:08'),(2,1,NULL,NULL,NULL,'Mateo','Leal','093801616','mjleal999@gmail.com','$2y$10$eNH9IJAvp2FWxgyhGOjuwO.Ah.0Xp0ogn5inDNATl/wNQtqy/ROBy',1,'2026-10-22 08:00:29'),(3,1,NULL,NULL,NULL,'leandro','villarueta','3242525','leandro@gmail.com','$2y$10$FxtS53yJ0kj8gnrRdeDGSueG2W335UC5mQzisa42OVTNjQ9qMRPe6',1,'2026-10-22 08:02:17');
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientecompraproducto`
--

DROP TABLE IF EXISTS `clientecompraproducto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientecompraproducto` (
  `id_cliente` int NOT NULL,
  `id_producto` int NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_cliente`,`id_producto`,`fecha`),
  KEY `idx_ccp_producto` (`id_producto`),
  CONSTRAINT `fk_ccp_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_ccp_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientecompraproducto`
--

LOCK TABLES `clientecompraproducto` WRITE;
/*!40000 ALTER TABLE `clientecompraproducto` DISABLE KEYS */;
/*!40000 ALTER TABLE `clientecompraproducto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuota`
--

DROP TABLE IF EXISTS `cuota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuota` (
  `id_cuota` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_plan` int DEFAULT NULL,
  `concepto` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `fecha_pago` date DEFAULT NULL,
  `estado` enum('pendiente','pagada','vencida','anulada') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `forma_pago` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cuota`),
  KEY `idx_cuota_cliente` (`id_cliente`),
  KEY `idx_cuota_vencimiento` (`fecha_vencimiento`,`estado`),
  KEY `fk_cuota_plan` (`id_plan`),
  CONSTRAINT `fk_cuota_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_cuota_plan` FOREIGN KEY (`id_plan`) REFERENCES `plan` (`id_plan`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuota`
--

LOCK TABLES `cuota` WRITE;
/*!40000 ALTER TABLE `cuota` DISABLE KEYS */;
INSERT INTO `cuota` VALUES (1,1,1,'Cuota mensual - Plan Full',1500.00,'2026-09-01','2026-09-30','2026-09-01','pagada','Tarjeta','2026-10-21 18:36:08'),(2,1,1,'Cuota mensual - Plan Full',1500.00,'2026-10-01','2026-10-31',NULL,'pendiente',NULL,'2026-10-21 18:36:08'),(3,1,1,'Cuota mensual - Plan Full',1500.00,'2026-08-01','2026-08-31',NULL,'vencida',NULL,'2026-10-21 18:36:08');
/*!40000 ALTER TABLE `cuota` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ejercicio`
--

DROP TABLE IF EXISTS `ejercicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ejercicio` (
  `id_ejercicio` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `musculo` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_ejercicio`),
  KEY `idx_ejercicio_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ejercicio`
--

LOCK TABLES `ejercicio` WRITE;
/*!40000 ALTER TABLE `ejercicio` DISABLE KEYS */;
INSERT INTO `ejercicio` VALUES (1,'Press de banca','Pectoral'),(2,'Remo con barra','Espalda'),(3,'Sentadilla trasera','Cuadriceps'),(4,'Press militar','Hombros'),(5,'Curl con barra','Biceps'),(6,'Peso muerto rumano','Femoral');
/*!40000 ALTER TABLE `ejercicio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entrenador`
--

DROP TABLE IF EXISTS `entrenador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entrenador` (
  `id_entrenador` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(70) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `correo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `contraseña` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Hash bcrypt (password_hash). Nunca en texto plano.',
  `rol` enum('admin','entrenador') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'entrenador',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_alta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_entrenador`),
  UNIQUE KEY `uk_entrenador_cedula` (`cedula`),
  UNIQUE KEY `uk_entrenador_correo` (`correo`),
  KEY `idx_entrenador_rol` (`rol`,`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entrenador`
--

LOCK TABLES `entrenador` WRITE;
/*!40000 ALTER TABLE `entrenador` DISABLE KEYS */;
INSERT INTO `entrenador` VALUES (1,'00000001','Entrenador','Principal','099000001','entrenador@olympia.local','$2y$10$eh4YHg51OvPcGQaWUfqEAuSBVcIMzMfRVEEskeFeNmXJUnRwnbh42','entrenador',1,'2026-10-21 18:36:07'),(2,'00000002','Administrador','del Sistema','099000002','admin@olympia.local','$2y$10$MwHxUonkRlVkACxnwVw0aejP1Gp1UnCJXLt6IhsYo.QYuFftOSiiG','admin',1,'2026-10-21 18:36:07');
/*!40000 ALTER TABLE `entrenador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gimnasio`
--

DROP TABLE IF EXISTS `gimnasio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gimnasio` (
  `id_gimnasio` int NOT NULL AUTO_INCREMENT,
  `direccion` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_gimnasio`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gimnasio`
--

LOCK TABLES `gimnasio` WRITE;
/*!40000 ALTER TABLE `gimnasio` DISABLE KEYS */;
INSERT INTO `gimnasio` VALUES (1,'Av. Gral. Rivera 1234, Canelones','contacto@olympia.local','24001234');
/*!40000 ALTER TABLE `gimnasio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intentologin`
--

DROP TABLE IF EXISTS `intentologin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intentologin` (
  `id_intento` int NOT NULL AUTO_INCREMENT,
  `correo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `exito` tinyint(1) NOT NULL DEFAULT '0',
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id_intento`),
  KEY `idx_intento_correo` (`correo`,`fecha`),
  KEY `idx_intento_ip` (`ip`,`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intentologin`
--

LOCK TABLES `intentologin` WRITE;
/*!40000 ALTER TABLE `intentologin` DISABLE KEYS */;
INSERT INTO `intentologin` VALUES (1,'validacion@appe.invalid','127.0.0.1',0,'2026-10-21 18:37:35'),(2,'entrenador@olympia.local','127.0.0.1',1,'2026-10-21 18:37:35'),(3,'cliente@olympia.local','192.168.1.46',0,'2026-10-21 18:45:24'),(4,'cliente@olympia.local','192.168.1.46',0,'2026-10-21 18:45:43'),(5,'validacion@appe.invalid','127.0.0.1',0,'2026-10-21 19:47:57'),(6,'entrenador@olympia.local','127.0.0.1',1,'2026-10-21 19:47:58'),(7,'validacion@appe.invalid','127.0.0.1',0,'2026-10-21 20:15:08'),(8,'validacion@appe.invalid','127.0.0.1',0,'2026-10-21 21:15:05'),(9,'validacion@appe.invalid','127.0.0.1',0,'2026-10-22 07:46:59'),(10,'entrenador@olympia.local','127.0.0.1',1,'2026-10-22 07:47:00'),(11,'mjleal999@gmail.com','10.48.33.92',1,'2026-10-22 08:00:48'),(12,'entrenador@olympia.local','10.48.33.87',0,'2026-10-22 08:03:39'),(13,'entrenador@olympia.local','10.48.33.87',0,'2026-10-22 08:03:54'),(15,'entrenador@olympia.local','10.48.33.87',0,'2026-10-22 08:05:48'),(16,'entrenador@olympia.local','10.48.33.87',0,'2026-10-22 08:14:42'),(17,'validacion@appe.invalid','127.0.0.1',0,'2026-10-22 08:15:11'),(18,'entrenador@olympia.local','10.48.33.87',0,'2026-10-22 08:16:07'),(21,'cliente@olympia.local','10.48.33.92',0,'2026-10-22 08:17:07'),(22,'cliente@olympia.local','10.48.33.92',0,'2026-10-22 08:17:07'),(23,'cliente@olympia.local','10.48.33.92',0,'2026-10-22 08:17:08'),(24,'validacion@appe.invalid','127.0.0.1',0,'2026-10-22 12:15:06'),(25,'validacion@appe.invalid','127.0.0.1',0,'2026-10-22 13:15:03'),(26,'mjleal999@gmail.com','192.168.1.53',1,'2026-10-22 13:23:26'),(27,'mjleal999@gmail.com','192.168.1.53',1,'2026-10-22 13:23:53'),(28,'admin@olympia.local','192.168.1.53',1,'2026-10-22 13:44:10'),(29,'validacion@appe.invalid','127.0.0.1',0,'2026-10-22 14:15:04');
/*!40000 ALTER TABLE `intentologin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajecontacto`
--

DROP TABLE IF EXISTS `mensajecontacto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensajecontacto` (
  `id_mensaje` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(70) COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mensaje` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT '0',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_mensaje`),
  KEY `idx_mensaje_leido` (`leido`,`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajecontacto`
--

LOCK TABLES `mensajecontacto` WRITE;
/*!40000 ALTER TABLE `mensajecontacto` DISABLE KEYS */;
INSERT INTO `mensajecontacto` VALUES (1,'Visitante','visitante@correo.local','091112223','Consulta sobre horarios de las clases grupales.',0,'2026-10-21 18:36:08');
/*!40000 ALTER TABLE `mensajecontacto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plan`
--

DROP TABLE IF EXISTS `plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan` (
  `id_plan` int NOT NULL AUTO_INCREMENT,
  `id_gimnasio` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `beneficio` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Precio de referencia del plan.',
  `duracion` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_de_plan` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_inicio` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Fecha de inicio de la vigencia (texto, según el MER del PDF).',
  `fecha_fin` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Fecha de fin de la vigencia (texto, según el MER del PDF).',
  `extension` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Extensión de la imagen; NULL si el plan no tiene imagen.',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_plan`),
  KEY `idx_plan_activo` (`activo`),
  KEY `fk_plan_gimnasio` (`id_gimnasio`),
  CONSTRAINT `fk_plan_gimnasio` FOREIGN KEY (`id_gimnasio`) REFERENCES `gimnasio` (`id_gimnasio`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan`
--

LOCK TABLES `plan` WRITE;
/*!40000 ALTER TABLE `plan` DISABLE KEYS */;
INSERT INTO `plan` VALUES (1,1,'Plan Full','Acceso a sala de musculación y clases grupales.','Acceso libre al gimnasio',1500.00,'Mensual','individual','2026-01-01','2026-12-31',NULL,1,'2026-10-21 18:36:08'),(2,1,'Plan Familiar','Acceso para dos personas, clases y musculación.','Acceso para dos personas',2600.00,'Mensual','familiar','2026-01-01','2026-12-31',NULL,1,'2026-10-21 18:36:08'),(3,1,'Plan Trimestral','Acceso completo durante tres meses.','Acceso libre y clases',3900.00,'Trimestral','individual','2026-01-01','2026-12-31',NULL,1,'2026-10-21 18:36:08');
/*!40000 ALTER TABLE `plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `planprecio`
--

DROP TABLE IF EXISTS `planprecio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planprecio` (
  `id_plan` int NOT NULL,
  `periodo` enum('semanal','mensual','trimestral','anual') COLLATE utf8mb4_general_ci NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_plan`,`periodo`),
  CONSTRAINT `fk_planprecio_plan` FOREIGN KEY (`id_plan`) REFERENCES `plan` (`id_plan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planprecio`
--

LOCK TABLES `planprecio` WRITE;
/*!40000 ALTER TABLE `planprecio` DISABLE KEYS */;
INSERT INTO `planprecio` VALUES (1,'mensual',1500.00),(1,'trimestral',3900.00),(1,'anual',15000.00),(2,'mensual',2600.00),(2,'trimestral',6500.00),(3,'trimestral',3900.00),(3,'anual',15000.00);
/*!40000 ALTER TABLE `planprecio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `id_gimnasio` int DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_de_producto` enum('consumible','accesorio','atuendo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'consumible',
  `extension` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Extensión de la imagen; NULL si el producto no tiene imagen.',
  `stock` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_producto`),
  KEY `idx_producto_tipo` (`tipo_de_producto`,`activo`),
  KEY `fk_producto_gimnasio` (`id_gimnasio`),
  CONSTRAINT `fk_producto_gimnasio` FOREIGN KEY (`id_gimnasio`) REFERENCES `gimnasio` (`id_gimnasio`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (1,1,350.00,'Botella deportiva Olympia','Botella reutilizable de 750 ml.','accesorio',NULL,40,1,'2026-10-21 18:36:08'),(2,1,1200.00,'Camiseta OLYMPIA','Camiseta de algodón con el logo del gimnasio.','atuendo',NULL,25,1,'2026-10-21 18:36:08'),(3,1,1800.00,'Pantalón de entrenamiento','Pantalón de entrenamiento resistente.','atuendo',NULL,20,1,'2026-10-21 18:36:08'),(4,1,900.00,'Shaker de proteína','Shaker de proteína de 600 ml.','consumible',NULL,30,1,'2026-10-21 18:36:08'),(5,1,450.00,'Banda elástica','Set de bandas elásticas para calentamiento.','accesorio',NULL,50,1,'2026-10-21 18:36:08');
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provee`
--

DROP TABLE IF EXISTS `provee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provee` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_proveedor` int NOT NULL,
  `id_producto` int NOT NULL,
  `id_gimnasio` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provee` (`id_proveedor`,`id_producto`),
  KEY `idx_provee_producto` (`id_producto`),
  KEY `fk_provee_gimnasio` (`id_gimnasio`),
  CONSTRAINT `fk_provee_gimnasio` FOREIGN KEY (`id_gimnasio`) REFERENCES `gimnasio` (`id_gimnasio`) ON DELETE SET NULL,
  CONSTRAINT `fk_provee_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_provee_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provee`
--

LOCK TABLES `provee` WRITE;
/*!40000 ALTER TABLE `provee` DISABLE KEYS */;
INSERT INTO `provee` VALUES (1,1,1,1),(2,1,2,1),(3,1,4,1);
/*!40000 ALTER TABLE `provee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedor`
--

DROP TABLE IF EXISTS `proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedor` (
  `id_proveedor` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `departamento` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `calle` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `manzana` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `solar` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `uk_proveedor_correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedor`
--

LOCK TABLES `proveedor` WRITE;
/*!40000 ALTER TABLE `proveedor` DISABLE KEYS */;
INSERT INTO `proveedor` VALUES (1,'Distribuidora Fitness','Montevideo','Av. Italia','1234','45','ventas@fitness.local','24009999',1,'2026-10-21 18:36:08');
/*!40000 ALTER TABLE `proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rutina`
--

DROP TABLE IF EXISTS `rutina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rutina` (
  `id_rutina` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_entrenador` int DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activa','archivada') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'activa',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rutina`),
  KEY `idx_rutina_cliente` (`id_cliente`),
  KEY `idx_rutina_entrenador` (`id_entrenador`),
  CONSTRAINT `fk_rutina_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_rutina_entrenador` FOREIGN KEY (`id_entrenador`) REFERENCES `entrenador` (`id_entrenador`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rutina`
--

LOCK TABLES `rutina` WRITE;
/*!40000 ALTER TABLE `rutina` DISABLE KEYS */;
INSERT INTO `rutina` VALUES (1,'Rutina Full',1,1,NULL,NULL,'activa','2026-10-21 18:36:08');
/*!40000 ALTER TABLE `rutina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rutinatieneejercicio`
--

DROP TABLE IF EXISTS `rutinatieneejercicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rutinatieneejercicio` (
  `id_relacion` int NOT NULL AUTO_INCREMENT,
  `id_rutina` int NOT NULL,
  `id_ejercicio` int NOT NULL,
  `serie_repeticion` varchar(60) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Prescripción, por ejemplo "4x12".',
  `dia` int NOT NULL COMMENT 'Día de la planificación (1..7).',
  `orden` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_relacion`),
  UNIQUE KEY `uk_relacion` (`id_rutina`,`id_ejercicio`,`dia`,`orden`),
  KEY `idx_rte_ejercicio` (`id_ejercicio`),
  CONSTRAINT `fk_rte_ejercicio` FOREIGN KEY (`id_ejercicio`) REFERENCES `ejercicio` (`id_ejercicio`) ON DELETE CASCADE,
  CONSTRAINT `fk_rte_rutina` FOREIGN KEY (`id_rutina`) REFERENCES `rutina` (`id_rutina`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rutinatieneejercicio`
--

LOCK TABLES `rutinatieneejercicio` WRITE;
/*!40000 ALTER TABLE `rutinatieneejercicio` DISABLE KEYS */;
INSERT INTO `rutinatieneejercicio` VALUES (1,1,1,'4x12',1,1),(2,1,3,'4x10',1,2),(3,1,2,'4x10',2,1),(4,1,5,'3x12',2,2),(5,1,4,'3x10',3,1),(6,1,6,'4x8',3,2);
/*!40000 ALTER TABLE `rutinatieneejercicio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudcambio`
--

DROP TABLE IF EXISTS `solicitudcambio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudcambio` (
  `id_solicitud` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_rutina` int DEFAULT NULL,
  `id_entrenador` int DEFAULT NULL,
  `mensaje` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `estado` enum('pendiente','aprobada','rechazada') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `respuesta` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_respuesta` datetime DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`),
  KEY `idx_solicitud_cliente` (`id_cliente`),
  KEY `idx_solicitud_estado` (`estado`),
  KEY `fk_sol_rutina` (`id_rutina`),
  KEY `fk_sol_entrenador` (`id_entrenador`),
  CONSTRAINT `fk_sol_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE,
  CONSTRAINT `fk_sol_entrenador` FOREIGN KEY (`id_entrenador`) REFERENCES `entrenador` (`id_entrenador`) ON DELETE SET NULL,
  CONSTRAINT `fk_sol_rutina` FOREIGN KEY (`id_rutina`) REFERENCES `rutina` (`id_rutina`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudcambio`
--

LOCK TABLES `solicitudcambio` WRITE;
/*!40000 ALTER TABLE `solicitudcambio` DISABLE KEYS */;
INSERT INTO `solicitudcambio` VALUES (1,1,1,1,'Quisiera cambiar los ejercicios de la gamba por trabajo en oficina.','pendiente',NULL,'2026-10-21 18:36:08',NULL);
/*!40000 ALTER TABLE `solicitudcambio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telcliente`
--

DROP TABLE IF EXISTS `telcliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telcliente` (
  `id_cliente` int NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_cliente`,`telefono`),
  CONSTRAINT `fk_telcliente_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telcliente`
--

LOCK TABLES `telcliente` WRITE;
/*!40000 ALTER TABLE `telcliente` DISABLE KEYS */;
INSERT INTO `telcliente` VALUES (1,'094445556');
/*!40000 ALTER TABLE `telcliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telentrenador`
--

DROP TABLE IF EXISTS `telentrenador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telentrenador` (
  `id_entrenador` int NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_entrenador`,`telefono`),
  CONSTRAINT `fk_telentrenador_entrenador` FOREIGN KEY (`id_entrenador`) REFERENCES `entrenador` (`id_entrenador`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telentrenador`
--

LOCK TABLES `telentrenador` WRITE;
/*!40000 ALTER TABLE `telentrenador` DISABLE KEYS */;
INSERT INTO `telentrenador` VALUES (1,'099000001'),(2,'099000002');
/*!40000 ALTER TABLE `telentrenador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telgimnasio`
--

DROP TABLE IF EXISTS `telgimnasio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telgimnasio` (
  `id_gimnasio` int NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_gimnasio`,`telefono`),
  CONSTRAINT `fk_telgimnasio_gimnasio` FOREIGN KEY (`id_gimnasio`) REFERENCES `gimnasio` (`id_gimnasio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telgimnasio`
--

LOCK TABLES `telgimnasio` WRITE;
/*!40000 ALTER TABLE `telgimnasio` DISABLE KEYS */;
INSERT INTO `telgimnasio` VALUES (1,'24001234');
/*!40000 ALTER TABLE `telgimnasio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telproveedor`
--

DROP TABLE IF EXISTS `telproveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telproveedor` (
  `id_proveedor` int NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_proveedor`,`telefono`),
  CONSTRAINT `fk_telproveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telproveedor`
--

LOCK TABLES `telproveedor` WRITE;
/*!40000 ALTER TABLE `telproveedor` DISABLE KEYS */;
INSERT INTO `telproveedor` VALUES (1,'24009999');
/*!40000 ALTER TABLE `telproveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tokenrecuperacion`
--

DROP TABLE IF EXISTS `tokenrecuperacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokenrecuperacion` (
  `id_token` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('cliente','entrenador') COLLATE utf8mb4_general_ci NOT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_entrenador` int DEFAULT NULL,
  `token_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `expira` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_token`),
  UNIQUE KEY `uk_token_hash` (`token_hash`),
  KEY `idx_token_expira` (`expira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tokenrecuperacion`
--

LOCK TABLES `tokenrecuperacion` WRITE;
/*!40000 ALTER TABLE `tokenrecuperacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `tokenrecuperacion` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-22 14:53:11
