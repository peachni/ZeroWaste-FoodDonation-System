-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: food_donation_db
-- ------------------------------------------------------
-- Server version	11.5.2-MariaDB

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
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `Feedback_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Food_Quality_Rating` tinyint(4) NOT NULL,
  `Quality_Status` enum('Fresh','Near Expiration','Damaged','Expired') NOT NULL,
  `Comments` text DEFAULT NULL,
  `Date_Submitted` datetime DEFAULT current_timestamp(),
  `Donee_ID` int(11) NOT NULL,
  `FoodList_ID` varchar(10) DEFAULT NULL,
  `Admin_ID` int(11) NOT NULL,
  `Admin_Reply` text DEFAULT NULL,
  `Reply_Date` datetime DEFAULT NULL,
  PRIMARY KEY (`Feedback_ID`),
  KEY `Donee_ID` (`Donee_ID`),
  KEY `FoodList_ID` (`FoodList_ID`),
  KEY `Admin_ID` (`Admin_ID`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (8,5,'Fresh','The vegetables were extremely fresh and well-packed. Truly helpful!','2026-01-09 10:15:00',7,'FL001',1,'We are glad you liked the quality! Our donors try their best.','2026-01-09 12:00:00'),(2,5,'Fresh','The apples were very sweet and crisp! Thank you.','2026-01-05 10:00:00',4,'FL001',1,'You are most welcome! We always strive for the best quality.','2026-01-05 14:30:00'),(3,3,'Near Expiration','The bread expires tomorrow, so I had to eat it quickly. Still tastes good though.','2026-01-06 09:15:00',4,'FL002',1,NULL,NULL),(4,4,'Fresh','Canned sardines arrived in great condition. Packaging was very secure.','2026-01-07 16:45:00',4,'FL003',1,'ok','2026-01-08 09:53:50'),(5,5,'Fresh','The apples were very sweet and crisp! Thank you.','2026-01-05 10:00:00',4,'FL001',1,'You are most welcome! We always strive for the best quality.','2026-01-05 14:30:00'),(6,3,'Near Expiration','The bread expires tomorrow, so I had to eat it quickly. Still tastes good though.','2026-01-06 09:15:00',4,'FL002',1,NULL,NULL),(7,4,'Fresh','Canned sardines arrived in great condition. Packaging was very secure.','2026-01-07 16:45:00',4,'FL003',1,NULL,NULL),(9,4,'Fresh','Very happy with the canned food received today. Clean packaging.','2026-01-10 14:30:00',7,'FL004',1,NULL,NULL),(10,3,'Near Expiration','The milk expires in 2 days. I will finish it quickly. Otherwise, it tastes fine.','2026-01-10 08:45:00',8,'FL002',1,NULL,NULL),(11,1,'Expired','The donuts have expired and unsafe','2026-01-12 04:38:47',4,'F008',1,NULL,NULL),(12,4,'Fresh','the cupcakes were sweet and safe to consume','2026-01-12 14:28:26',4,'F003',1,NULL,NULL),(13,5,'Fresh','The donuts were fresh and safe to consume','2026-01-12 14:39:48',4,'F008',1,'Thank you lokman. Hope you enjoyed it!','2026-01-12 14:41:43'),(14,5,'Fresh','The donuts were great and happy to receive them.','2026-01-12 15:34:33',4,'F008',1,NULL,NULL),(15,5,'Fresh','The donuts were fresh and tasty','2026-01-12 16:00:12',4,'F008',1,NULL,NULL);
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER after_feedback_insert
AFTER INSERT ON feedback
FOR EACH ROW
BEGIN
    INSERT INTO feedback_audit_log (feedback_id, action_performed, changed_by, details)
    VALUES (NEW.Feedback_ID, 'INSERT', USER(), CONCAT('New feedback from Donee ID: ', NEW.Donee_ID));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `feedback_audit_log`
--

DROP TABLE IF EXISTS `feedback_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_audit_log` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback_id` int(11) DEFAULT NULL,
  `action_performed` varchar(50) DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `change_time` timestamp NULL DEFAULT current_timestamp(),
  `details` text DEFAULT NULL,
  PRIMARY KEY (`audit_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_audit_log`
--

LOCK TABLES `feedback_audit_log` WRITE;
/*!40000 ALTER TABLE `feedback_audit_log` DISABLE KEYS */;
INSERT INTO `feedback_audit_log` VALUES (1,12,'INSERT','app_feedback_user@localhost','2026-01-12 14:28:26','New feedback from Donee ID: 4'),(2,13,'INSERT','app_feedback_user@localhost','2026-01-12 14:39:48','New feedback from Donee ID: 4'),(3,14,'INSERT','app_feedback_user@localhost','2026-01-12 15:34:33','New feedback from Donee ID: 4'),(4,15,'INSERT','app_feedback_user@localhost','2026-01-12 16:00:12','New feedback from Donee ID: 4');
/*!40000 ALTER TABLE `feedback_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `feedback_public_view`
--

DROP TABLE IF EXISTS `feedback_public_view`;
/*!50001 DROP VIEW IF EXISTS `feedback_public_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `feedback_public_view` AS SELECT 
 1 AS `Food_Quality_Rating`,
 1 AS `Quality_Status`,
 1 AS `Comments`,
 1 AS `Date_Submitted`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `fresh_food_report`
--

DROP TABLE IF EXISTS `fresh_food_report`;
/*!50001 DROP VIEW IF EXISTS `fresh_food_report`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `fresh_food_report` AS SELECT 
 1 AS `Feedback_ID`,
 1 AS `Food_Quality_Rating`,
 1 AS `Quality_Status`,
 1 AS `Comments`,
 1 AS `Date_Submitted`,
 1 AS `Donee_ID`,
 1 AS `FoodList_ID`,
 1 AS `Admin_ID`,
 1 AS `Admin_Reply`,
 1 AS `Reply_Date`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `feedback_public_view`
--

/*!50001 DROP VIEW IF EXISTS `feedback_public_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `feedback_public_view` AS select `feedback`.`Food_Quality_Rating` AS `Food_Quality_Rating`,`feedback`.`Quality_Status` AS `Quality_Status`,`feedback`.`Comments` AS `Comments`,`feedback`.`Date_Submitted` AS `Date_Submitted` from `feedback` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `fresh_food_report`
--

/*!50001 DROP VIEW IF EXISTS `fresh_food_report`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `fresh_food_report` AS select `feedback`.`Feedback_ID` AS `Feedback_ID`,`feedback`.`Food_Quality_Rating` AS `Food_Quality_Rating`,`feedback`.`Quality_Status` AS `Quality_Status`,`feedback`.`Comments` AS `Comments`,`feedback`.`Date_Submitted` AS `Date_Submitted`,`feedback`.`Donee_ID` AS `Donee_ID`,`feedback`.`FoodList_ID` AS `FoodList_ID`,`feedback`.`Admin_ID` AS `Admin_ID`,`feedback`.`Admin_Reply` AS `Admin_Reply`,`feedback`.`Reply_Date` AS `Reply_Date` from `feedback` where `feedback`.`Quality_Status` = 'Fresh' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-13 13:01:38
