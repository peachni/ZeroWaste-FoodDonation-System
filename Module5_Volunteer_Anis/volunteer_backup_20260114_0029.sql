-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: volunteer
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `volunteer`
--

DROP TABLE IF EXISTS `volunteer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `volunteer` (
  `volunteer_id` varchar(4) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `area_assigned` varchar(100) DEFAULT NULL,
  `availability_status` enum('Available','Busy','Inactive') DEFAULT 'Available',
  `donee_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`volunteer_id`),
  KEY `idx_donee_link` (`donee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `volunteer`
--

LOCK TABLES `volunteer` WRITE;
/*!40000 ALTER TABLE `volunteer` DISABLE KEYS */;
INSERT INTO `volunteer` VALUES ('V001','nis','0135346421','nishaz0987@gmail.com','Car','perak','Available',NULL),('V002','nafis','0132281468','nafisa345@gmail.com','Motorcycle','melaka','Available',NULL),('V003','kei','0194201325','kei@yahoo.com','Van','selangor','Available',NULL),('V004','ahmad','0135321000','abcd@yahoo.com','Van','pahang','Available',NULL),('V005','jat','0194201334','jatty12@gmail.com','Motorcycle','terengganu','Available',NULL),('V006','min','0114201334','xmn1@yahoo.com','Truck','kuala lumpur','Available',NULL),('V007','abu','0137896421','garfield@gmail.com','Motorcycle','johor','Available',NULL),('V008','lis','0122346431','lllla1234@gmail.com','Motorcycle','kelantan','Available',NULL),('V009','zed','0192345678','zsa145@yahoo.com','Car','negeri sembilan','Available',NULL),('V010','sam','0130011468','sammy567@yahoo.com','Car','johor','Available',NULL),('V011','yoy','0135678765','ratototo@yahoo.com','Van','sabah','Available',NULL),('V012','dan','0194321567','rom12345@gmail.com','Truck','sarawak','Available',NULL);
/*!40000 ALTER TABLE `volunteer` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = armscii8_bin */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_volunteer_id
BEFORE INSERT ON volunteer
FOR EACH ROW
BEGIN
    DECLARE next_num INT;
    
    -- Check if ID is empty or null
    IF NEW.volunteer_id IS NULL OR NEW.volunteer_id = '' THEN
        -- Find the current max number and add 1
        SELECT COALESCE(MAX(CAST(SUBSTRING(volunteer_id, 2) AS UNSIGNED)), 0) + 1 
        INTO next_num 
        FROM volunteer;
        
        -- Format it back to V00X
        SET NEW.volunteer_id = CONCAT('V', LPAD(next_num, 3, '0'));
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-14  0:29:54
