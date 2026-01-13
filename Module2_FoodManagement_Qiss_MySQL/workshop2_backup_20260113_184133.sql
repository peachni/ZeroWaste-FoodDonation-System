-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: workshop2
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
-- Current Database: `workshop2`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `workshop2` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `workshop2`;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `admin_username` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `CatID` varchar(10) NOT NULL,
  `CatType` varchar(100) NOT NULL,
  `admin_id` int(11) NOT NULL,
  PRIMARY KEY (`CatID`),
  KEY `fk_category_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES ('C001','Beverage',1),('C002','Soupy',1),('C003','Main Course',1),('C004','Appetizer',1),('C005','Fast Food',1),('C006','Vegetarian Food',1),('C007','Desserts',1),('C008','Pastry',1),('C009','Snack',1),('C010','Other',1),('C011','Fried Food',1);
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_generate_catid
BEFORE INSERT ON Category
FOR EACH ROW
BEGIN
    IF NEW.CatID IS NULL OR NEW.CatID = '' THEN
        SET NEW.CatID = CONCAT(
            'C',
            LPAD(
                IFNULL(
                    (SELECT SUBSTRING(MAX(CatID), 2) + 1 FROM Category),
                    1
                ),
                3,
                '0'
            )
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `donor`
--

DROP TABLE IF EXISTS `donor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donor` (
  `donor_id` varchar(10) NOT NULL,
  `donor_name` varchar(100) DEFAULT NULL,
  `donor_username` varchar(50) DEFAULT NULL,
  `donor_pass` varchar(255) DEFAULT NULL,
  `donor_type` varchar(20) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  PRIMARY KEY (`donor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donor`
--

LOCK TABLES `donor` WRITE;
/*!40000 ALTER TABLE `donor` DISABLE KEYS */;
INSERT INTO `donor` VALUES ('10','Siti Baiduri','Siti Baiduri','1234','Individual','2026-01-05'),('11','Aisyah Aziz','Aisyah Aziz','1234','Individual','2026-01-12'),('12','Priyadashwini Yoheswaran','Priyadashwini Yoheswaran','1234','Individual','2026-01-12'),('13','lola','lola','1234','Individual','2026-01-05'),('15','Amirul Kamil','Amirul Kamil','1234','Individual','2026-01-09'),('16','Qiss Aziz','Qiss Aziz','1234','Individual','2026-01-10'),('17','suhaimi','suhaimi','1234','Individual','2026-01-11'),('18','lisa','lisa','1234','Individual','2026-01-13');
/*!40000 ALTER TABLE `donor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_don_list`
--

DROP TABLE IF EXISTS `food_don_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `food_don_list` (
  `FoodList_ID` varchar(10) NOT NULL,
  `Food_Name` varchar(25) NOT NULL,
  `Food_Desc` varchar(255) NOT NULL,
  `Quantity` varchar(100) NOT NULL,
  `Manufacture_Date` date DEFAULT NULL,
  `Expiry_Date` date DEFAULT NULL,
  `Storage_Instruction` varchar(255) DEFAULT NULL,
  `Allergen_Info` varchar(255) DEFAULT NULL,
  `Image` blob NOT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Created_Date` date DEFAULT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `CatID` varchar(10) DEFAULT NULL,
  `Qty_Consumed` int(10) NOT NULL,
  PRIMARY KEY (`FoodList_ID`),
  KEY `donor_id` (`donor_id`),
  KEY `fk_food_category` (`CatID`),
  CONSTRAINT `fk_food_category` FOREIGN KEY (`CatID`) REFERENCES `category` (`CatID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_don_list`
--

LOCK TABLES `food_don_list` WRITE;
/*!40000 ALTER TABLE `food_don_list` DISABLE KEYS */;
INSERT INTO `food_don_list` VALUES ('F001','popia carbonara','fresh and cheezy also frozen','0','2026-01-11','2026-01-12','keep in the freezer','contain cheese','uploads/food_6963c7b08b7a91.86553228_popia-carbonara-cheese-2.jpg','consumed','2026-01-11',17,'C005',19),('F002','Curry puff frozen','spicy','0','2026-01-11','2026-01-30','keep it in the freezer','contain potato and curry herb','uploads/food_6963c92e5a0b23.19409426_karipap.jpeg','consumed','2026-01-11',15,'C008',40),('F003','Cupcake','sweet','0','2026-01-11','2026-01-19','keep it in room temperature','contain sugar','uploads/food_6963c974adfdd2.86447154_cupcake.jpeg','consumed','2026-01-11',15,'C007',25),('F004','Cream Puff','Sweet and creamy with different flavour','0','2026-01-11','2026-01-13','Keep in cool area','Contain nabati ingredient','uploads/food_6963ca75a6fd99.59508950_creampuf.jpg','consumed','2026-01-11',16,'C008',20),('F005','Bihun Goreng','Spicy','0','2026-01-11','2026-01-14','Keep in fridge and reheat before serve','Prawn','uploads/food_6963cd14dfcf28.37703024_bihun goreng.jpg','consumed','2026-01-11',17,'C011',25),('F006','Mee Goreng','Sweet and spicy','0','2026-01-11','2026-01-13','Keep it in fridge and reheat before serve','Egg and prawn','uploads/food_6963cc9a8edb42.10752453_mee.jpg','consumed','2026-01-11',13,'C011',20),('F008','Donut','Sweet','69','2026-01-12','2026-01-19','Keep refrigerated','Contains nuts','uploads/food_69646230583ff5.13108035_donut.jpg','Available','2026-01-12',12,'C007',21);
/*!40000 ALTER TABLE `food_don_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `foodwaste`
--

DROP TABLE IF EXISTS `foodwaste`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foodwaste` (
  `WasteID` varchar(10) NOT NULL,
  `Quantity_Waste` int(11) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Time` time DEFAULT NULL,
  `FoodList_ID` varchar(10) NOT NULL,
  `Admin_ID` int(11) NOT NULL,
  PRIMARY KEY (`WasteID`),
  KEY `fk_foodlist_waste` (`FoodList_ID`),
  KEY `fk_foodwaste_admin` (`Admin_ID`),
  CONSTRAINT `fk_foodwaste_admin` FOREIGN KEY (`Admin_ID`) REFERENCES `admin` (`Admin_ID`),
  CONSTRAINT `foodwaste_ibfk_1` FOREIGN KEY (`FoodList_ID`) REFERENCES `food_don_list` (`FoodList_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `foodwaste`
--

LOCK TABLES `foodwaste` WRITE;
/*!40000 ALTER TABLE `foodwaste` DISABLE KEYS */;
INSERT INTO `foodwaste` VALUES ('W003',20,'Expired food','2025-12-03','10:56:49','F005',0),('W004',10,'Expired food','2025-12-23','14:31:26','F003',0),('W005',20,'Expired food','2025-12-23','14:31:26','F004',0),('W006',9,'Expired food auto-detected','2026-01-07','10:59:30','F006',1);
/*!40000 ALTER TABLE `foodwaste` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER after_waste_insert
AFTER INSERT ON foodwaste
FOR EACH ROW
BEGIN
    DELETE FROM food_don_list 
    WHERE FoodList_ID = NEW.FoodList_ID;
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

-- Dump completed on 2026-01-13 18:41:33
