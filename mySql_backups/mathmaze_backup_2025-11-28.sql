-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: mysql-178347b3-jiruumm03-9e90.f.aivencloud.com    Database: mathmaze_db
-- ------------------------------------------------------
-- Server version	8.0.35

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
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

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '3f9c0038-b148-11f0-b243-862ccfb01d9c:1-75,
9dba6b93-bab2-11f0-bd86-862ccfb029f0:1-372';

--
-- Table structure for table `grade3_progress`
--

DROP TABLE IF EXISTS `grade3_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade3_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `level` int DEFAULT NULL,
  `score` int DEFAULT NULL,
  `time_spent` float DEFAULT NULL,
  `date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `grade3_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `grade3_students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade3_progress`
--

LOCK TABLES `grade3_progress` WRITE;
/*!40000 ALTER TABLE `grade3_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade3_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade3_students`
--

DROP TABLE IF EXISTS `grade3_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade3_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `grade_level` int DEFAULT '3',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade3_students`
--

LOCK TABLES `grade3_students` WRITE;
/*!40000 ALTER TABLE `grade3_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade3_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade3_teachers`
--

DROP TABLE IF EXISTS `grade3_teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade3_teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade3_teachers`
--

LOCK TABLES `grade3_teachers` WRITE;
/*!40000 ALTER TABLE `grade3_teachers` DISABLE KEYS */;
INSERT INTO `grade3_teachers` VALUES (1,'testteacher1','$2y$10$mVmHVTsU3yZR5uqRL2ttSOnOWE0Gv6k.h2fHHgokiPOKJ/mxSNRnW','test teacher'),(2,'Frankie','$2y$10$Bykehu65osLVPsiF5LjkYer5rw40MVtZvSrXj4o1imkdI6pBqhbmS','Frankie Gonzaga');
/*!40000 ALTER TABLE `grade3_teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade4_progress`
--

DROP TABLE IF EXISTS `grade4_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade4_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `level` int DEFAULT NULL,
  `score` int DEFAULT NULL,
  `time_spent` float DEFAULT NULL,
  `date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade4_progress`
--

LOCK TABLES `grade4_progress` WRITE;
/*!40000 ALTER TABLE `grade4_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade4_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade4_students`
--

DROP TABLE IF EXISTS `grade4_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade4_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `grade_level` int DEFAULT '3',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade4_students`
--

LOCK TABLES `grade4_students` WRITE;
/*!40000 ALTER TABLE `grade4_students` DISABLE KEYS */;
INSERT INTO `grade4_students` VALUES (1,'student1','$2a$10$lwpu7cc9E3dnxoYO4SboNOigNS9Ugtv1XwWHkRpANCcAhBzuAzNFS','student1',4);
/*!40000 ALTER TABLE `grade4_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade4_teachers`
--

DROP TABLE IF EXISTS `grade4_teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade4_teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade4_teachers`
--

LOCK TABLES `grade4_teachers` WRITE;
/*!40000 ALTER TABLE `grade4_teachers` DISABLE KEYS */;
INSERT INTO `grade4_teachers` VALUES (1,'jiruumm','$2y$10$ssddjLcmglEFC3VmKt/0teV7eAG8ieMHCu3b3MMfRtp/mC8BzSOqG','jerome');
/*!40000 ALTER TABLE `grade4_teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade5_progress`
--

DROP TABLE IF EXISTS `grade5_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade5_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `level` int DEFAULT NULL,
  `score` int DEFAULT NULL,
  `time_spent` float DEFAULT NULL,
  `date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade5_progress`
--

LOCK TABLES `grade5_progress` WRITE;
/*!40000 ALTER TABLE `grade5_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade5_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade5_students`
--

DROP TABLE IF EXISTS `grade5_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade5_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `grade_level` int DEFAULT '3',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade5_students`
--

LOCK TABLES `grade5_students` WRITE;
/*!40000 ALTER TABLE `grade5_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade5_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade5_teachers`
--

DROP TABLE IF EXISTS `grade5_teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade5_teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade5_teachers`
--

LOCK TABLES `grade5_teachers` WRITE;
/*!40000 ALTER TABLE `grade5_teachers` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade5_teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade6_progress`
--

DROP TABLE IF EXISTS `grade6_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade6_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `level` int DEFAULT NULL,
  `score` int DEFAULT NULL,
  `time_spent` float DEFAULT NULL,
  `date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade6_progress`
--

LOCK TABLES `grade6_progress` WRITE;
/*!40000 ALTER TABLE `grade6_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade6_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade6_students`
--

DROP TABLE IF EXISTS `grade6_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade6_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `grade_level` int DEFAULT '3',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade6_students`
--

LOCK TABLES `grade6_students` WRITE;
/*!40000 ALTER TABLE `grade6_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade6_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade6_teachers`
--

DROP TABLE IF EXISTS `grade6_teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade6_teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade6_teachers`
--

LOCK TABLES `grade6_teachers` WRITE;
/*!40000 ALTER TABLE `grade6_teachers` DISABLE KEYS */;
INSERT INTO `grade6_teachers` VALUES (1,'mike','$2y$10$KRpq.sh9G5yUK0EKMli5xelql9CB7a3TgeisUUvxXCDITmTlNxxC2','mike');
/*!40000 ALTER TABLE `grade6_teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_name` varchar(100) NOT NULL,
  `grade_level` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `teacher_grade` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `super_admins`
--

DROP TABLE IF EXISTS `super_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `super_admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `super_admins`
--

LOCK TABLES `super_admins` WRITE;
/*!40000 ALTER TABLE `super_admins` DISABLE KEYS */;
INSERT INTO `super_admins` VALUES (1,'admin','$2y$10$DngUwccil8wMKduQdHu/AuPWAl3S9yV9iK7TVhHG0MbHfcg/0hp7.');
/*!40000 ALTER TABLE `super_admins` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-28 22:05:23
