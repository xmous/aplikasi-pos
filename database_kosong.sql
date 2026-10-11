-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: project_pos
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_action_created_at_index` (`action`,`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_transactions`
--

DROP TABLE IF EXISTS `cash_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cashier_shift_id` bigint(20) unsigned NOT NULL,
  `expense_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_transactions_cashier_shift_id_foreign` (`cashier_shift_id`),
  KEY `cash_transactions_created_by_foreign` (`created_by`),
  KEY `cash_transactions_expense_id_type_index` (`expense_id`,`type`),
  CONSTRAINT `cash_transactions_cashier_shift_id_foreign` FOREIGN KEY (`cashier_shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `cash_transactions_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_transactions`
--

LOCK TABLES `cash_transactions` WRITE;
/*!40000 ALTER TABLE `cash_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashier_shifts`
--

DROP TABLE IF EXISTS `cashier_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cashier_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `opened_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  `opening_cash` decimal(18,2) NOT NULL DEFAULT 0.00,
  `expected_cash` decimal(18,2) NOT NULL DEFAULT 0.00,
  `actual_cash` decimal(18,2) DEFAULT NULL,
  `cash_difference` decimal(18,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cashier_shifts_store_id_foreign` (`store_id`),
  KEY `cashier_shifts_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `cashier_shifts_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cashier_shifts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashier_shifts`
--

LOCK TABLES `cashier_shifts` WRITE;
/*!40000 ALTER TABLE `cashier_shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `cashier_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_point_transactions`
--

DROP TABLE IF EXISTS `customer_point_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_point_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `sale_return_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `points` int(11) NOT NULL,
  `balance_after` int(10) unsigned NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_point_transactions_sale_return_id_foreign` (`sale_return_id`),
  KEY `customer_point_transactions_created_by_foreign` (`created_by`),
  KEY `customer_point_history_index` (`customer_id`,`created_at`),
  KEY `sale_point_type_index` (`sale_id`,`type`),
  CONSTRAINT `customer_point_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `customer_point_transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `customer_point_transactions_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `customer_point_transactions_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_point_transactions`
--

LOCK TABLES `customer_point_transactions` WRITE;
/*!40000 ALTER TABLE `customer_point_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_point_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `points` int(10) unsigned NOT NULL DEFAULT 0,
  `credit_limit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_code_unique` (`code`),
  KEY `customers_phone_index` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'CUST-001','Pelanggan Member','082222222222',NULL,NULL,0,1000000.00,1,'2026-08-02 08:39:53','2026-08-02 08:39:53',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL,
  `cashier_shift_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `payment_method` varchar(30) NOT NULL DEFAULT 'cash',
  `notes` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_store_id_foreign` (`store_id`),
  KEY `expenses_cashier_shift_id_foreign` (`cashier_shift_id`),
  KEY `expenses_created_by_foreign` (`created_by`),
  KEY `expenses_expense_date_index` (`expense_date`),
  CONSTRAINT `expenses_cashier_shift_id_foreign` FOREIGN KEY (`cashier_shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `expenses_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_07_31_000001_add_pos_fields_to_users_table',1),(5,'2026_07_31_000002_create_permission_tables',1),(6,'2026_07_31_000010_create_pos_master_tables',1),(7,'2026_07_31_000020_create_pos_sales_purchase_tables',1),(8,'2026_07_31_000030_create_pos_inventory_operation_tables',1),(9,'2026_07_31_000040_create_product_units_and_transaction_unit_columns',1),(10,'2026_07_31_000050_add_expense_reference_to_cash_transactions',1),(11,'2026_08_02_000001_add_loyalty_points_to_sales_and_create_transactions',1),(12,'2026_08_02_000001_improve_sale_returns_table',1),(13,'2026_08_02_000003_add_receipt_alignment_to_users_table',1),(14,'2026_08_02_000004_make_receipt_alignment_center_default',1),(15,'2026_08_02_000005_add_receipt_policy_text_to_stores_table',1),(16,'2026_08_02_000006_add_receipt_paper_size_to_users_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',2),(3,'App\\Models\\User',3),(4,'App\\Models\\User',4);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(2,'sales.view','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(3,'sales.create','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(4,'sales.payment','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(5,'sales.discount','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(6,'sale_returns.create','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(7,'shifts.manage','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(8,'products.view','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(9,'products.create','web','2026-08-02 08:39:47','2026-08-02 08:39:47'),(10,'products.update','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(11,'products.delete','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(12,'categories.manage','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(13,'units.manage','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(14,'suppliers.manage','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(15,'customers.manage','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(16,'warehouses.manage','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(17,'purchases.view','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(18,'purchases.create','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(19,'purchases.payment','web','2026-08-02 08:39:48','2026-08-02 08:39:48'),(20,'purchase_returns.create','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(21,'stock.view','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(22,'stock.adjust','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(23,'opname.view','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(24,'opname.create','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(25,'opname.approve','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(26,'expenses.view','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(27,'expenses.create','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(28,'expenses.update','web','2026-08-02 08:39:49','2026-08-02 08:39:49'),(29,'promotions.manage','web','2026-08-02 08:39:50','2026-08-02 08:39:50'),(30,'reports.sales','web','2026-08-02 08:39:50','2026-08-02 08:39:50'),(31,'reports.stocks','web','2026-08-02 08:39:50','2026-08-02 08:39:50'),(32,'reports.profit','web','2026-08-02 08:39:50','2026-08-02 08:39:50'),(33,'reports.debts','web','2026-08-02 08:39:50','2026-08-02 08:39:50'),(34,'reports.operations','web','2026-08-02 08:39:50','2026-08-02 08:39:50'),(35,'reports.export','web','2026-08-02 08:39:50','2026-08-02 08:39:50'),(36,'users.manage','web','2026-08-02 08:39:50','2026-08-02 08:39:50'),(37,'roles.manage','web','2026-08-02 08:39:51','2026-08-02 08:39:51'),(38,'settings.manage','web','2026-08-02 08:39:51','2026-08-02 08:39:51'),(39,'audit.view','web','2026-08-02 08:39:51','2026-08-02 08:39:51');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `purchase_item_id` bigint(20) unsigned DEFAULT NULL,
  `batch_number` varchar(100) NOT NULL,
  `expired_at` date DEFAULT NULL,
  `quantity` decimal(18,3) NOT NULL DEFAULT 0.000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_batches_product_id_foreign` (`product_id`),
  KEY `product_batches_purchase_item_id_foreign` (`purchase_item_id`),
  KEY `batch_fefo_lookup` (`warehouse_id`,`product_id`,`expired_at`),
  CONSTRAINT `product_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_batches_purchase_item_id_foreign` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_batches_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_categories_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (21,'FROZENFOOD','Frozen Food',1,'2025-11-10 09:42:22','2025-11-10 09:42:22'),(22,'SAOS','saos',1,'2025-11-11 12:01:48','2025-11-11 12:01:48'),(23,'ADDITIONAL','Additional',1,'2025-12-06 17:05:30','2025-12-06 17:05:30'),(24,'TEPUNG','Tepung',1,'2025-12-06 17:42:50','2025-12-06 17:42:50'),(25,'BUMBU','Bumbu',1,'2025-12-06 19:27:29','2025-12-06 19:27:29'),(26,'BAHANKUE','Bahan kue',1,'2025-12-06 21:44:58','2025-12-06 21:44:58');
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_units`
--

DROP TABLE IF EXISTS `product_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `conversion_factor` decimal(18,6) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `purchase_price` decimal(18,2) DEFAULT NULL,
  `selling_price` decimal(18,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_units_product_id_unit_id_unique` (`product_id`,`unit_id`),
  UNIQUE KEY `product_units_barcode_unique` (`barcode`),
  KEY `product_units_unit_id_foreign` (`unit_id`),
  CONSTRAINT `product_units_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_units_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_units`
--

LOCK TABLES `product_units` WRITE;
/*!40000 ALTER TABLE `product_units` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_category_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(60) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `purchase_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `average_cost` decimal(18,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `wholesale_price` decimal(18,2) DEFAULT NULL,
  `wholesale_min_qty` decimal(18,3) DEFAULT NULL,
  `minimum_stock` decimal(18,3) NOT NULL DEFAULT 0.000,
  `rack_location` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `track_stock` tinyint(1) NOT NULL DEFAULT 1,
  `has_expiry` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_barcode_unique` (`barcode`),
  KEY `products_product_category_id_foreign` (`product_category_id`),
  KEY `products_unit_id_foreign` (`unit_id`),
  KEY `products_name_is_active_index` (`name`,`is_active`),
  CONSTRAINT `products_product_category_id_foreign` FOREIGN KEY (`product_category_id`) REFERENCES `product_categories` (`id`),
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=606 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (304,21,1,'8997021179032','8997021179032','Jofran Naget krispi 500g',19500.00,19500.00,21000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 20:53:07','2025-12-28 18:45:12',NULL),(305,21,1,'8994130899691','8994130899691','Belfoods Chicken Naget krispi 500g',34000.00,34000.00,36000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:03:44','2025-11-10 21:09:00',NULL),(306,21,1,'89972042205237','89972042205237','Oye Naget Funny 500g',16000.00,16000.00,18000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:08:52','2025-11-10 21:08:52',NULL),(307,21,1,'8993110001567','8993110001567','So Nice Naget ayam',20000.00,20000.00,22000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:10:07','2025-11-10 23:20:28',NULL),(308,21,1,'8993110000331','8993110000331','So Echo Naget 1kg',30000.00,30000.00,32000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:10:56','2025-11-10 23:44:59',NULL),(309,21,1,'8997035791916','8997035791916','SF sosis salju 500g',17000.00,17000.00,19000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:11:55','2025-11-10 21:11:55',NULL),(310,21,1,'8998080265599','8998080265599','Hemato Naget 1kg',32000.00,32000.00,34000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:13:25','2025-11-10 21:13:25',NULL),(311,21,1,'8994130899943','8994130899943','Belfoods Naget 140g',10000.00,10000.00,12000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:14:15','2025-11-10 21:14:15',NULL),(312,21,1,'8998080260747','8998080260747','Yona beef patties isi 10',35500.00,35500.00,37500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:15:11','2025-12-07 18:15:50',NULL),(313,21,1,'8998080265551','8998080265551','Hemato Naget Ayam 250g',9000.00,9000.00,11000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:17:19','2025-11-10 21:17:19',NULL),(314,21,1,'8993207130064','8993207130064','Akumo Naget ayam 500g',21000.00,21000.00,23000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:21:52','2025-12-28 18:43:45',NULL),(315,21,1,'8997222641437','8997222641437','Zaitun Chicken pokpok',34000.00,34000.00,36000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:22:48','2025-11-10 21:22:48',NULL),(316,21,1,'8997018181727','8997018181727','Mabel Naget ori 500g',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:23:48','2025-11-10 21:23:48',NULL),(317,21,1,'8997014710013','8997014710013','Naget Goldstar Ori 500g',35000.00,35000.00,37000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:25:34','2025-11-10 21:25:34',NULL),(318,21,1,'CUFD7928','CUFD7928','Cireng Praja',8500.00,8500.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:26:42','2025-12-28 18:54:14',NULL),(319,21,1,'PAMG6151','PAMG6151','Stik Mie',6750.00,6750.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:27:12','2025-11-10 21:27:12',NULL),(320,21,1,'STBC8182','STBC8182','Scalop stik',6750.00,6750.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:27:34','2025-11-10 21:27:34',NULL),(321,21,1,'8997222640621','8997222640621','Salam Naget Stik 500g',16500.00,16500.00,18500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:28:28','2025-11-10 21:28:28',NULL),(322,21,1,'8997222640577','8997222640577','Salam naget ayam 500g',16500.00,16500.00,18500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:29:41','2025-11-10 21:29:41',NULL),(323,21,1,'8997207136323','8997207136323','Vitalia isi burger 280 (mini)',8750.00,8750.00,10500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:33:10','2025-11-30 19:29:45',NULL),(324,21,1,'8993200664399','8993200664399','Kanzler Naget krispi 500g',37000.00,37000.00,39000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:34:49','2025-11-30 19:27:11',NULL),(325,21,1,'8997207132950','8997207132950','Vitalia beef patties isi 6',18000.00,18000.00,20000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:36:00','2025-11-10 21:36:00',NULL),(326,21,1,'8997018183691','8997018183691','Mabel Naget Krispi',25750.00,25750.00,28000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:37:15','2025-11-10 21:37:15',NULL),(327,21,1,'8993207230221','8993207230221','Champ Naget Krispi 500g',29000.00,29000.00,31000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:37:59','2025-11-10 21:37:59',NULL),(328,21,1,'8993200669134','8993200669134','Kanzler Naget Krispi Spicy 500g',39000.00,39000.00,41000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:39:49','2025-11-10 21:39:49',NULL),(329,21,1,'8993200668076','8993200668076','Kanzler Naget Stik 500g',37000.00,37000.00,39000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:40:40','2025-11-10 21:40:40',NULL),(330,21,1,'8998080265704','8998080265704','Yona Naget krispi 500g',35000.00,35000.00,37000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:41:26','2025-11-10 21:41:26',NULL),(331,21,1,'OOTO3377','OOTO3377','Cireng Salju pedas',5500.00,5500.00,7500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:46:02','2025-11-10 21:46:02',NULL),(332,21,1,'PWXF5260','PWXF5260','Sosis mie',6500.00,6500.00,8000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-10 21:56:45','2025-11-10 21:56:45',NULL),(333,21,1,'021135000100423','021135000100423','Cireng asoy',6500.00,6500.00,8000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:00:50','2025-12-28 18:54:14',NULL),(334,21,1,'8997019860010','8997019860010','Bakso ikan Go',10500.00,10500.00,12500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:04:21','2025-11-30 17:57:47',NULL),(335,21,1,'8997017570393','8997017570393','ILM Tempura',11500.00,11500.00,13500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:05:53','2025-12-28 18:54:14',NULL),(336,21,1,'8997017570454','8997017570454','ILM Scalop',11500.00,11500.00,13500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:06:58','2025-12-28 18:54:14',NULL),(337,21,1,'VGPL1017','VGPL1017','Sate usus',7000.00,7000.00,9000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:08:11','2025-11-11 00:08:11',NULL),(338,21,1,'8997032113346','8997032113346','Minipao Strawberry',12000.00,12000.00,14000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:10:13','2025-11-11 00:10:13',NULL),(339,21,1,'LIEB2624','LIEB2624','ILM Naget Es',10500.00,10500.00,12500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:10:41','2025-11-11 00:10:41',NULL),(340,21,1,'8997017570546','8997017570546','ILM Tempura Burger',11500.00,11500.00,13500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:11:38','2025-12-28 18:54:14',NULL),(341,21,1,'8997032113360','8997032113360','Minipao ayam isi 30',12000.00,12000.00,14000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:12:21','2025-11-11 00:12:21',NULL),(342,21,1,'8997032113308','8997032113308','Minipao coklat isi 30',12000.00,12000.00,14000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:13:17','2025-11-11 00:13:17',NULL),(343,21,1,'8997017570416','8997017570416','ILM Bintang',11000.00,11000.00,13500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:14:16','2026-01-04 16:36:14',NULL),(344,21,1,'8997207137313','8997207137313','vitalia isi burger 250(besar)',8500.00,8500.00,10500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:15:09','2025-11-11 00:15:09',NULL),(345,21,1,'8997035790780','8997035790780','SF Dumpling keju 500g',25000.00,25000.00,27000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:20:40','2025-11-11 00:20:40',NULL),(346,21,1,'8993374323801','8993374323801','Shifudo Chikuwa mini 500g',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:21:21','2025-11-11 00:21:21',NULL),(347,21,1,'8997035790919','8997035790919','SF Kembang Cumi 500g',26500.00,26500.00,28500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:22:07','2025-11-11 00:22:07',NULL),(348,21,1,'8997035791190','8997035791190','SF Crabstick',24000.00,24000.00,26000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:26:27','2025-11-11 00:26:27',NULL),(349,21,1,'8993374323894','8993374323894','Shifudo Dumpling keju',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:27:05','2025-11-11 00:27:05',NULL),(350,21,1,'8997035791053','8997035791053','SF Udang Gulung',22000.00,22000.00,24000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:27:44','2025-11-11 00:27:44',NULL),(351,21,1,'8997035791282','8997035791282','SF Bakso ikan mix 500g',25000.00,25000.00,27000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:29:53','2025-11-11 00:29:53',NULL),(352,21,1,'9789703579082','9789703579082','SF Chikuwa mini 500g',25000.00,25000.00,27000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:30:58','2025-12-07 18:51:44',NULL),(353,21,1,'8997035791428','8997035791428','SF Ekor Udang',26000.00,26000.00,28000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:31:35','2025-11-11 00:31:35',NULL),(354,21,1,'8997035791749','8997035791749','SF Odeng isi 20',21000.00,21000.00,23000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:32:31','2025-11-11 00:32:31',NULL),(355,21,1,'BJVT1313','BJVT1313','Mie pangsit 1kg',13000.00,13000.00,15000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:33:26','2025-11-11 00:33:26',NULL),(356,21,1,'8997233655133','8997233655133','Beejay Siomay Ikan 800g',33000.00,33000.00,35000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:36:42','2025-11-11 00:36:42',NULL),(357,21,1,'8997233655294','8997233655294','Beejay Siomay sayur 800g',33000.00,33000.00,35000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:37:16','2025-11-11 00:37:16',NULL),(358,21,1,'NOVQ8598','NOVQ8598','siomay polos daging',11000.00,11000.00,13000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:37:47','2025-11-11 00:37:47',NULL),(359,21,1,'8997212361000','8997212361000','Joyful Siomay udang 900g',36000.00,36000.00,38000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:39:21','2025-11-11 00:39:21',NULL),(360,21,1,'8997212361024','8997212361024','Joyful siomay ayam 900g',35000.00,35000.00,37000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:42:20','2025-11-11 00:42:20',NULL),(361,21,1,'HESD7007','HESD7007','Kulit Dimsum 250g',4000.00,4000.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:42:53','2025-11-11 00:42:53',NULL),(362,21,1,'VKUB7029','VKUB7029','Kulit Dimsum 500g warna warni',8500.00,8500.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:43:23','2025-11-11 00:43:23',NULL),(363,21,1,'UVLV5022','UVLV5022','Kulit Dimsum 500g Putih',8000.00,8000.00,10000.00,9000.00,10.000,0.000,NULL,NULL,1,0,1,'2025-11-11 00:43:58','2025-11-11 00:43:58',NULL),(364,21,1,'DNWE6234','DNWE6234','Kulit Dimsum 500g Kuning',8000.00,8000.00,10000.00,9000.00,10.000,0.000,NULL,NULL,1,0,1,'2025-11-11 00:44:28','2025-11-11 00:44:28',NULL),(365,21,1,'8998080260679','8998080260679','Sosis merah yona isi 3',4500.00,4500.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 00:45:26','2025-11-11 00:45:26',NULL),(366,22,1,'8993200664542','8993200664542','Mamayo mayonis 1kg',21000.00,21000.00,23000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-11 12:02:03','2025-11-11 12:02:48',NULL),(367,22,1,'8992845859269','8992845859269','PrimaAgung mentai 1kg',47000.00,47000.00,49000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 16:38:40','2025-11-30 16:38:40',NULL),(368,22,1,'8997024460373','8997024460373','Mcluwis mayo manis 1kg',20500.00,20500.00,22500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 16:39:27','2025-11-30 16:39:27',NULL),(369,22,1,'8998866632553','8998866632553','Kecap Sedap manis 550g',14000.00,14000.00,15000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 16:41:49','2025-11-30 16:41:49',NULL),(370,22,1,'8993200668878','8993200668878','Gourmet bbq 250g',7000.00,7000.00,8000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 16:43:03','2025-11-30 16:43:03',NULL),(371,22,1,'8994063102684','8994063102684','SK kecap manis 700g',10000.00,10000.00,11000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 16:45:17','2025-11-30 16:45:17',NULL),(372,22,1,'8999999593223','8999999593223','Bango pouch 280g',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 16:46:37','2025-11-30 16:46:37',NULL),(373,22,1,'8997024460359','8997024460359','Mcluwis mayo ori 1kg',23500.00,23500.00,24500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:10:20','2025-11-30 17:10:20',NULL),(374,22,1,'8998888461186','8998888461186','Maestro preimum mayo 1kg',28000.00,28000.00,29000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:25:00','2025-11-30 17:25:00',NULL),(375,22,1,'8993200668670','8993200668670','Mamayo mayonais 500g ori',12000.00,12000.00,13000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:27:57','2025-11-30 17:27:57',NULL),(376,22,1,'8993200662760','8993200662760','Mamayo mayonais 200g ori',6000.00,6000.00,7000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:28:43','2025-11-30 19:27:11',NULL),(377,22,1,'8998888000002','8998888000002','Delmonte korean bbq 250g',8000.00,8000.00,9000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:34:23','2025-11-30 18:57:44',NULL),(378,22,1,'8998888712196','8998888712196','Delmonte saos spageti 250g',8000.00,8000.00,9000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:35:27','2025-11-30 17:35:27',NULL),(379,22,1,'8998888461124','8998888461124','Maestro mayonais 180g',7500.00,7500.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:36:24','2025-11-30 17:36:24',NULL),(380,22,1,'8998888000019','8998888000019','Delmonte saos blackpaper 250g',8000.00,8000.00,9000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:38:39','2025-11-30 18:56:56',NULL),(381,22,1,'8998888712295','8998888712295','Delmonte bbq saos 250g',8000.00,8000.00,9000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:39:18','2025-11-30 18:57:19',NULL),(382,22,1,'8993200666607','8993200666607','Gourmet Extra Pedas 1kg',14000.00,14000.00,15000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:54:21','2025-11-30 17:54:21',NULL),(383,22,1,'8998888330178','8998888330178','Kiko tomat 1kg',10000.00,10000.00,11000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:55:28','2025-11-30 17:55:28',NULL),(384,22,1,'8993200665198','8993200665198','Gourmet saos tomat 1kg',11000.00,11000.00,12000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:56:43','2025-12-06 19:23:11',NULL),(385,22,1,'8998888000101','8998888000101','Kiko saos sambal 500g',5000.00,5000.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 17:57:33','2025-11-30 17:57:33',NULL),(386,22,1,'8998888330277','8998888330277','Kiko Saos sambal 1kg',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 18:00:04','2025-11-30 18:00:04',NULL),(387,22,1,'8998888000118','8998888000118','Kiko saos tomat 500g',5000.00,5000.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 18:23:11','2025-11-30 18:23:11',NULL),(388,22,1,'8993200667734','8993200667734','Gourmet saos sambal 500g',7000.00,7000.00,8000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 18:30:21','2025-11-30 18:30:21',NULL),(389,22,1,'8998888000088','8998888000088','Delmonte extra hot 500g',11000.00,11000.00,12000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 18:46:34','2025-11-30 18:46:34',NULL),(390,22,1,'8998888000095','8998888000095','Delmonte Saos tomat 500g',8000.00,8000.00,9000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 18:53:43','2025-11-30 18:53:43',NULL),(391,22,1,'8998888710192','8998888710192','Delmonte saos tomat 200g',6000.00,6000.00,7000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 18:54:43','2025-11-30 18:54:43',NULL),(392,22,1,'8998888710178','8998888710178','Delmonte saos tomat 1kg',15000.00,15000.00,16000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 18:55:26','2025-11-30 19:27:11',NULL),(393,22,1,'8998888710574','8998888710574','Delmonte Extra Hot 1kg',21000.00,21000.00,22000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-11-30 19:00:06','2025-11-30 19:00:06',NULL),(394,21,1,'8997227881432','8997227881432','Pakden Bakso Ikan 500g',15500.00,15500.00,17500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:28:17','2025-12-02 16:28:17',NULL),(395,21,1,'8997227882712','8997227882712','Pakden Odeng 500g',17000.00,17000.00,19000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:29:16','2025-12-02 16:29:16',NULL),(396,21,1,'8997001450397','8997001450397','Indomina Dumpling keju 500g',21000.00,21000.00,23000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:31:34','2025-12-07 18:51:44',NULL),(397,21,1,'8997001450779','8997001450779','Indomina bakso udang 500g',24500.00,24500.00,26500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:32:35','2025-12-02 16:32:35',NULL),(398,21,1,'8997227881272','8997227881272','Pakden Tofu 500g',17000.00,17000.00,19000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:33:21','2025-12-07 18:51:44',NULL),(399,21,1,'9986450101003','9986450101003','Kornet Umiami',14500.00,14500.00,16500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:34:23','2025-12-28 18:54:14',NULL),(400,21,1,'8993207800240','8993207800240','Champ sosis ayam isi 40',40000.00,40000.00,42000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:36:09','2025-12-02 16:36:09',NULL),(401,21,1,'8997207138266','8997207138266','Horeca Sosis sapi mini',28000.00,28000.00,30000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:37:22','2025-12-02 16:37:22',NULL),(402,21,1,'8997001450571','8997001450571','Indomina Bebek 500g',24000.00,24000.00,26000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:38:37','2025-12-02 16:38:37',NULL),(403,21,1,'8899409625226','8899409625226','Bakso Mak\'e jamur isi 50',25500.00,25500.00,27500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:44:10','2025-12-02 16:44:10',NULL),(404,21,1,'BOED3066','BOED3066','Bakso Mak\'e super ori isi 50',25500.00,25500.00,27500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:45:14','2025-12-28 18:44:38',NULL),(405,21,1,'8994034523319','8994034523319','Bakso mak\'e extra ori isi 50',19000.00,19000.00,21000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:45:51','2025-12-28 18:44:15',NULL),(406,21,1,'5273873472419','5273873472419','Bakso Mak\'e Besar Extra isi 25',52000.00,52000.00,55000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:46:59','2025-12-28 18:43:57',NULL),(407,21,1,'8994096232211','8994096232211','Taso Mak\'e isi 25',28000.00,28000.00,30000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:48:09','2025-12-02 16:48:09',NULL),(408,21,1,'8994096212282','8994096212282','Mak\'e Bakso Besar Super Ori isi 25',56000.00,56000.00,58000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:49:02','2025-12-02 16:49:02',NULL),(409,21,1,'8997222641741','8997222641741','Fishroll Salam',21000.00,21000.00,23000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:50:41','2025-12-07 18:03:10',NULL),(410,21,1,'HMPZ2734','HMPZ2734','Tahu bakso polos isi 20',20000.00,20000.00,22000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:51:07','2025-12-02 16:51:07',NULL),(411,21,1,'8997014710297','8997014710297','Ngetop sosis merah 750g',25500.00,25500.00,27500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:53:06','2025-12-28 18:54:14',NULL),(412,21,1,'8997021178950','8997021178950','Sosis bakar chopchop isi 6',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:54:14','2025-12-02 17:04:24',NULL),(413,21,1,'AIGP8097','AIGP8097','Sosis bakar chopchop isi 10',25000.00,25000.00,27000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:58:12','2025-12-02 16:58:12',NULL),(414,21,1,'8997222641192','8997222641192','Salam bakso sapi isi 50',17000.00,17000.00,19000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 16:59:28','2025-12-02 16:59:28',NULL),(415,21,1,'8997222640072','8997222640072','Salam bakso ayam isi 50',16000.00,16000.00,18000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:00:48','2025-12-28 19:20:06',NULL),(416,21,1,'8990714710464','8990714710464','Ngetop sosis merah 500g',17000.00,17000.00,19000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:02:07','2025-12-02 17:03:53',NULL),(417,21,1,'8997222640201','8997222640201','Salam sosis merah 500g',16000.00,16000.00,18000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:03:43','2025-12-02 17:03:43',NULL),(418,21,1,'8995555218210','8995555218210','Vigo sosis bakar sapi isi 12',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:05:10','2025-12-02 17:05:10',NULL),(419,21,1,'8997222641680','8997222641680','Salam sosis bakar mini isi 13',25500.00,25500.00,27500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:06:18','2025-12-02 17:06:18',NULL),(420,21,1,'8997017562923','8997017562923','Mister max sosis bakar isi 13',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:06:52','2025-12-02 17:06:52',NULL),(421,21,1,'8997207136811','8997207136811','Umiami sosis sp 750g',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:08:04','2025-12-02 17:08:04',NULL),(422,21,1,'8997222640041','8997222640041','Salam sosis ayam merah 750g',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:09:19','2025-12-02 17:09:19',NULL),(423,21,1,'8995229881298','8995229881298','Sosis Uenak 900g',30000.00,30000.00,32000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:10:27','2025-12-07 18:18:16',NULL),(424,21,1,'8993207568355','8993207568355','Sosis Champ 500g',23000.00,23000.00,25000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:11:38','2025-12-02 17:11:38',NULL),(425,21,1,'8993207730035','8993207730035','Sosis champ 375g',16000.00,16000.00,18000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:12:34','2025-12-02 17:12:34',NULL),(426,21,1,'8995229804206','8995229804206','Sosis Uenak 475g',17000.00,17000.00,19000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:13:58','2025-12-02 17:13:58',NULL),(427,21,1,'SSBO0486','SSBO0486','Bakso Polos Besar isi 50',55000.00,55000.00,57000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:15:00','2025-12-02 17:15:00',NULL),(428,21,1,'XDJB8967','XDJB8967','Bakso polos kecil isi 50',27500.00,27500.00,29500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:15:57','2025-12-02 17:15:57',NULL),(429,21,1,'JGTI3965','JGTI3965','Bakso Maju mapan isi 50',13000.00,13000.00,15000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:17:11','2025-12-02 17:17:11',NULL),(430,21,1,'8994016010547','8994016010547','Bakso JJ Hijau isi 50',17000.00,17000.00,19000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-02 17:18:37','2025-12-02 17:18:37',NULL),(431,23,1,'8997213440902','8997213440902','Selasih Tabura',1500.00,1500.00,2000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:06:36','2025-12-06 17:06:36',NULL),(432,23,1,'089686000108','089686000108','Mie telur 3 ayam isi 2',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:09:23','2025-12-06 17:09:23',NULL),(433,23,1,'8991688890101','8991688890101','Mie burung dara kuning isi 2',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:12:07','2025-12-06 17:12:07',NULL),(434,23,1,'089686010947','089686010947','Indomie goreng 85g',2800.00,2800.00,3500.00,10000.00,3.000,0.000,NULL,NULL,1,0,1,'2025-12-06 17:14:04','2025-12-28 19:18:22',NULL),(435,23,1,'089686000061','089686000061','Mie Telur 200g',5000.00,5000.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:15:04','2025-12-06 17:15:04',NULL),(436,23,1,'8994291101046','8994291101046','Mie Telur Kuda menjangan 200g',5500.00,5500.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:15:53','2025-12-06 17:15:53',NULL),(437,23,1,'8998866203555','8998866203555','Mie sukses isi 2 rendang',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:17:30','2025-12-06 17:25:07',NULL),(438,23,1,'8886007811076','8886007811076','Teh celup sosro isi 30',5000.00,5000.00,5500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:20:28','2025-12-06 17:20:28',NULL),(439,23,1,'8999999195649','8999999195649','Teh sariwangi isi 25',5500.00,5500.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:22:09','2025-12-06 17:22:09',NULL),(440,23,1,'8994286120045','8994286120045','Teh dandang bubuk',3500.00,3500.00,4000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:24:56','2025-12-06 17:24:56',NULL),(441,23,1,'8991818080112','8991818080112','Uleg sambal terasi saset',1000.00,1000.00,1500.00,1300.00,12.000,0.000,NULL,NULL,1,0,1,'2025-12-06 17:26:44','2025-12-06 17:26:44',NULL),(442,23,1,'8991818800857','8991818800857','uleg sambal bawang saset',1000.00,1000.00,1500.00,1300.00,12.000,0.000,NULL,NULL,1,0,1,'2025-12-06 17:31:26','2025-12-06 17:31:26',NULL),(443,23,1,'8991818060350','8991818060350','Krupuk finna',800.00,800.00,1000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:32:41','2025-12-06 17:32:41',NULL),(444,24,1,'ZNXK0580','ZNXK0580','Tepung kriuk 135g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:43:03','2025-12-06 17:43:03',NULL),(445,23,1,'YTCR7560','YTCR7560','Abon sapi kapal terbang',5000.00,5000.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:44:14','2025-12-06 22:22:38',NULL),(446,23,1,'8994286160010','8994286160010','Teh dandang 2 in 1',1500.00,1500.00,2000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:55:08','2025-12-06 18:09:00',NULL),(447,23,1,'IUBN4949','IUBN4949','Tepung hunkwe hijau',1000.00,1000.00,1500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:56:07','2025-12-06 17:56:07',NULL),(448,23,1,'8998866203265','8998866203265','ekomie goreng instan 60g',1000.00,1000.00,1500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:57:00','2025-12-06 17:57:00',NULL),(449,23,1,'089686043815','089686043815','Indomie mie bangladesh 60g',3000.00,3000.00,3500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 17:57:41','2025-12-06 17:57:41',NULL),(450,23,1,'089686043433','089686043433','Indomie geprek 60g',3000.00,3000.00,3500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:00:53','2025-12-06 18:00:53',NULL),(451,23,1,'8998866201667','8998866201667','Mie sukses ayam kecap isi 2',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:01:48','2025-12-28 19:18:22',NULL),(452,23,1,'8998866204484','8998866204484','Mie sukses goreng aceh isi 2',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:07:48','2025-12-06 18:07:48',NULL),(453,23,1,'089686021219','089686021219','Intermie goreng 60g',1000.00,1000.00,1500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:08:45','2025-12-06 18:08:45',NULL),(454,23,1,'089686010343','089686010343','Indomie soto mie 60g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:11:25','2025-12-06 18:11:25',NULL),(455,23,1,'089686043754','089686043754','Indomie soto koya 60g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:12:33','2025-12-06 18:12:33',NULL),(456,23,1,'8998866203272','8998866203272','Mie sukses ayam geprek isi 2',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:13:16','2025-12-06 18:13:16',NULL),(457,23,1,'8998866200325','8998866200325','Mie sedaap soto 60g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:14:47','2025-12-06 18:32:47',NULL),(458,23,1,'8998866200882','8998866200882','Mie sukses ayam kremes isi 2',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:15:36','2025-12-06 18:15:36',NULL),(459,23,1,'089686043204','089686043204','Indomie goreng aceh 60g',3000.00,3000.00,3500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:17:10','2025-12-06 22:19:43',NULL),(460,23,1,'089686021004','089686021004','Intermie goreng pedas 60g',1000.00,1000.00,1500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:18:29','2025-12-06 18:18:29',NULL),(461,23,1,'8998866202343','8998866202343','Mie sedaap Selection seaweed crunchy 60g',3000.00,3000.00,3500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:21:07','2025-12-06 18:21:07',NULL),(462,24,1,'8996001319000','8996001319000','Tepung terigu life 1kg',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:21:52','2025-12-06 18:21:52',NULL),(463,22,1,'8997024460731','8997024460731','Mc luwis cheese saus 250g',15000.00,15000.00,16000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:25:10','2025-12-06 18:25:10',NULL),(464,22,1,'8997024460663','8997024460663','Mc luwis bbq saus 310g',10000.00,10000.00,11000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:25:51','2025-12-06 18:25:51',NULL),(465,23,1,'8997024460656','8997024460656','Mc Luwis spageti saus 310g',10000.00,10000.00,11000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:32:32','2025-12-06 18:32:32',NULL),(466,22,1,'8993200664719','8993200664719','Gourmet saos keju 500g',21000.00,21000.00,22000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:37:44','2025-12-06 18:37:44',NULL),(467,22,1,'089686420678','089686420678','Indofood kecap manis 725g',16000.00,16000.00,17000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:39:45','2025-12-06 18:39:45',NULL),(468,23,1,'8998866200578','8998866200578','Mie sedaap kari spesial 60g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:40:37','2025-12-06 18:40:37',NULL),(469,23,1,'8998866200301','8998866200301','Mie Sedaap goreng 60g',3000.00,3000.00,3500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:41:37','2025-12-06 18:41:37',NULL),(470,22,1,'8998866202299','8998866202299','Sedaap kecap manis 270g (hijau)',8000.00,8000.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:43:00','2025-12-06 18:48:28',NULL),(471,22,1,'8999999100506','8999999100506','Bango kecap manis 700g pouch',22000.00,22000.00,23000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:46:03','2025-12-06 18:46:03',NULL),(472,22,1,'8998866608046','8998866608046','Sedaap kecap manis 250g (coklat)',8000.00,8000.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:48:03','2025-12-06 18:48:03',NULL),(473,22,1,'711844110144','711844110144','Kecap ABC 250g (merah)',8000.00,8000.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:50:19','2025-12-06 18:50:19',NULL),(474,22,1,'8997231193804','8997231193804','Kecap sarasa 720g',10000.00,10000.00,11000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:51:41','2025-12-06 18:51:41',NULL),(475,22,1,'5919660612614','5919660612614','Kecap inggris Sinar mentari',5500.00,5500.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:56:51','2025-12-06 18:56:51',NULL),(476,22,1,'3013881677978','3013881677978','Minyak bawang merah SM 150ml',5500.00,5500.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:58:06','2025-12-06 18:58:06',NULL),(477,22,1,'5919660612612','5919660612612','Minyak wijen SM 150ml',5500.00,5500.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 18:58:53','2025-12-06 18:58:53',NULL),(478,22,1,'DBCK7159','DBCK7159','Minyak cabe cap SM 150ml',5500.00,5500.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:02:21','2025-12-06 19:02:21',NULL),(479,22,1,'5919660612615','5919660612615','Kecap ikan SM 150ml',5500.00,5500.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:03:12','2025-12-06 19:03:12',NULL),(480,22,1,'711844115057','711844115057','Kecap asin ABC 150ml',5500.00,5500.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:04:29','2025-12-06 19:04:29',NULL),(481,22,1,'5919660612611','5919660612611','Saos Praja rasa SM 150ml',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:07:07','2025-12-06 19:07:07',NULL),(482,22,1,'5919660612616','5919660612616','Kecap asin SM 150ml',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:09:43','2025-12-06 19:09:43',NULL),(483,22,1,'8998866202305','8998866202305','sedaap kecap manis hijau 135ml',7500.00,7500.00,8000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:10:58','2025-12-06 19:10:58',NULL),(484,22,1,'5919660612613','5919660612613','saos Tiram SM 150ml',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:13:17','2025-12-06 19:13:17',NULL),(485,22,1,'089686400816','089686400816','Indofood extra pedas 135ml',6500.00,6500.00,7000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:14:55','2025-12-06 19:14:55',NULL),(486,22,1,'8992770096128','8992770096128','Saos tiram saori 133ml',9500.00,9500.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:15:56','2025-12-06 19:15:56',NULL),(487,22,1,'8992770096135','8992770096135','Saos Tiram saori 270ml',18000.00,18000.00,18500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:16:36','2025-12-06 19:16:36',NULL),(488,22,1,'089686400526','089686400526','Indofood sambal pedas manis',6500.00,6500.00,7000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:17:09','2025-12-06 19:17:09',NULL),(489,22,1,'8998888710161','8998888710161','Delmonte saos tomat botol 135ml',5500.00,5500.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:18:19','2025-12-06 19:18:19',NULL),(490,22,1,'8998888710567','8998888710567','Delmonte extra hot botol 135ml',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:18:52','2025-12-06 19:18:52',NULL),(491,22,1,'089686400427','089686400427','Indofood sambal pedas 135ml',6500.00,6500.00,7000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:19:40','2025-12-06 19:19:40',NULL),(492,22,1,'8999999002503','8999999002503','Kecap Bango botol 385g',21000.00,21000.00,22000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:21:07','2025-12-06 19:21:07',NULL),(493,22,1,'30138816779778','30138816779778','Cuka Meja SM 135ml',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:22:48','2025-12-06 19:22:48',NULL),(494,25,1,'8992735210149','8992735210149','bambu semur besar',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:28:35','2025-12-06 19:40:24',NULL),(495,25,1,'8992735210071','8992735210071','Bambu soto daging besar',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:29:28','2025-12-06 19:40:47',NULL),(496,25,1,'8992735210019','8992735210019','Bambu gule besar',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:31:17','2025-12-06 19:39:32',NULL),(497,25,1,'8992735210033','8992735210033','bambu opor bumbu',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:31:50','2025-12-06 19:39:57',NULL),(498,25,1,'8992735250558','8992735250558','Bambu tahu campur 69g',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:32:24','2025-12-06 19:32:24',NULL),(499,25,1,'8992735248692','8992735248692','Bambu balado besar',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:32:54','2025-12-06 19:39:18',NULL),(500,25,1,'8992735210064','8992735210064','Bambu soto ayam besar',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:33:31','2025-12-06 19:40:36',NULL),(501,25,1,'8992735210040','8992735210040','Bambu rawon besar',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:34:18','2025-12-06 19:40:09',NULL),(502,25,1,'8992735210026','8992735210026','Bambu kare besar',6000.00,6000.00,69000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:35:04','2025-12-06 19:39:44',NULL),(503,25,1,'8992735210057','8992735210057','Bambu rendang besar',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:37:35','2025-12-06 19:37:35',NULL),(504,25,1,'8992735249811','8992735249811','Bambu krengsengan besar',6000.00,6000.00,65000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:38:56','2025-12-06 19:38:56',NULL),(505,25,1,'8992735249934','8992735249934','Bambu bulgogi 80g',7000.00,7000.00,7500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:41:33','2025-12-06 19:41:33',NULL),(506,25,1,'08992735248678','08992735248678','Bambu saos lada hitam 50g',7000.00,7000.00,7500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:42:37','2025-12-06 19:42:37',NULL),(507,25,1,'8992735248203','8992735248203','Bambu rendang kecil',3000.00,3000.00,3500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:43:42','2025-12-06 19:43:42',NULL),(508,22,1,'TCMQ7338','TCMQ7338','Saos sambal ria rasa 450g',2000.00,2000.00,2500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:45:00','2025-12-28 18:54:14',NULL),(509,22,1,'WZPG1106','WZPG1106','Saos tomat ria rasa 450g',1500.00,1500.00,2000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:46:01','2025-12-06 19:46:01',NULL),(510,22,1,'MXKU5679','MXKU5679','Asia saos tomat 450g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:47:34','2025-12-28 18:54:14',NULL),(511,22,1,'8997024460588','8997024460588','Mc lewis chili saus 500g',7000.00,7000.00,7500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:55:17','2025-12-06 19:55:17',NULL),(512,22,1,'8994063101212','8994063101212','Saus cabai Gandaria 615g',3500.00,3500.00,4000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:56:50','2025-12-06 19:56:50',NULL),(513,22,1,'8997024460793','8997024460793','Mc lewis hotnhot 1kg bantal',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:58:15','2025-12-06 19:58:15',NULL),(514,22,1,'8994063101427','8994063101427','Saos bagong 615g',3500.00,3500.00,4000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 19:59:32','2025-12-06 19:59:32',NULL),(515,22,1,'8997029640268','8997029640268','Saos Sambal Swan bantal 1kg',8500.00,8500.00,9000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:02:22','2025-12-06 20:02:22',NULL),(516,22,1,'8997029640329','8997029640329','Saos sambal cap swan 500g bantal',4500.00,4500.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:04:13','2025-12-06 20:04:13',NULL),(517,22,1,'8997029640350','8997029640350','Saos swan sambal bawang 500g bantal',3500.00,3500.00,4000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:05:10','2025-12-06 20:05:10',NULL),(518,22,1,'IAQT3680','IAQT3680','Kecap manis skt 400g',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:06:10','2025-12-06 20:06:10',NULL),(519,21,1,'TESI4299','TESI4299','Sambal pedas SKT 450g',2000.00,2000.00,2500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:07:03','2025-12-06 20:07:03',NULL),(520,22,1,'8994063101960','8994063101960','Kecap manis SK 200g',3500.00,3500.00,4000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:07:56','2025-12-06 20:07:56',NULL),(521,22,1,'8993319620033','8993319620033','Saos tomat rimbaria 500g',8000.00,8000.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:09:12','2025-12-06 20:09:12',NULL),(522,23,1,'8992826122320','8992826122320','Minyak kita 1lt',17500.00,17500.00,18000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:10:57','2025-12-06 22:23:27',NULL),(523,23,1,'8993496107051','8993496107051','Sovia minyak 1lt',18000.00,18000.00,18500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:11:40','2025-12-06 20:11:40',NULL),(524,23,1,'8992946122002','8992946122002','Tropical 2 lt botol',39000.00,39000.00,40000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:12:21','2025-12-06 20:12:21',NULL),(525,23,1,'8993379500238','8993379500238','Sunco 2lt refill',40000.00,40000.00,41000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:13:25','2025-12-06 20:13:25',NULL),(526,23,1,'8992826112215','8992826112215','Kunci Mas 2lt refill',38000.00,38000.00,39000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:14:17','2025-12-06 20:14:17',NULL),(527,23,1,'8992826117333','8992826117333','Masku 2lt refill',38000.00,38000.00,38500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:15:08','2025-12-28 18:46:06',NULL),(528,23,1,'8992946533662','8992946533662','Fitri minyak 400ml',8500.00,8500.00,9000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:16:01','2025-12-06 20:16:01',NULL),(529,23,1,'8992946534676','8992946534676','Fitri minyak mungil',5500.00,5500.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:18:18','2025-12-06 20:18:18',NULL),(530,23,1,'HGIL3183','HGIL3183','Minyak gading sakti botol 700ml',14500.00,14500.00,15000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:19:14','2025-12-06 20:19:14',NULL),(531,25,1,'VBTJ6750','VBTJ6750','Royco / masako / kuah bakso ecer',350.00,350.00,500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:19:54','2025-12-06 21:19:07',NULL),(532,25,1,'CLWN0401','CLWN0401','Royco / masako / kuah bakso renteng',4700.00,4700.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:20:31','2025-12-28 18:54:14',NULL),(533,25,1,'BWFV5354','BWFV5354','masako sapi ecer',350.00,350.00,500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:23:02','2025-12-06 20:23:02',NULL),(534,25,1,'NZLJ4514','NZLJ4514','Sedaap bumbu renteng',4700.00,4700.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:27:04','2025-12-06 20:27:55',NULL),(535,25,1,'VHQI2254','VHQI2254','sedaap bumbu ecer 1000/3',800.00,800.00,1000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 20:27:36','2025-12-06 20:27:36',NULL),(536,25,1,'VGHQ6757','VGHQ6757','Ladaku ecer',800.00,800.00,1000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:13:05','2025-12-06 21:13:05',NULL),(537,25,1,'LAMG1654','LAMG1654','Ladaku / Bubuk\" Desaku / renteng isi 12',9600.00,9600.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:16:05','2025-12-06 21:22:45',NULL),(538,25,1,'EOMU3439','EOMU3439','Cabe bubuk desaku ecer',2000.00,2000.00,2500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:23:09','2025-12-06 21:23:09',NULL),(539,25,1,'NQZP1859','NQZP1859','cabe bubuk desaku rtg isi 10',20000.00,20000.00,23000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:23:37','2025-12-06 21:23:37',NULL),(540,25,1,'HRVM6937','HRVM6937','Bumbu racik ecer',1800.00,1800.00,2000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:24:01','2025-12-06 21:24:01',NULL),(541,25,1,'PYAD7425','PYAD7425','magic renteng isi 11',4500.00,4500.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:24:35','2025-12-06 21:24:35',NULL),(542,25,1,'ZPEM5311','ZPEM5311','magic bumbu ecer saset 15g',400.00,400.00,500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:24:58','2025-12-06 21:24:58',NULL),(543,23,1,'8991688891115','8991688891115','Mie Kenduri besar',12000.00,12000.00,13000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:25:49','2025-12-06 21:28:24',NULL),(544,23,1,'BYWD3991','BYWD3991','mie kenduri kecil',5000.00,5000.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:26:07','2025-12-06 21:26:07',NULL),(545,23,1,'8998866204170','8998866204170','Ekomie bakso isi 5',4000.00,4000.00,4500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:27:03','2025-12-06 22:19:43',NULL),(546,23,1,'8998866204101','8998866204101','Ekomie bakso isi 15pcs',12500.00,12500.00,13500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:27:46','2025-12-06 21:27:46',NULL),(547,24,1,'8994451990022','8994451990022','Syaza tepung bumbu 100g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:34:15','2025-12-06 21:34:15',NULL),(548,24,1,'8993207180854','8993207180854','Fiesta Tepung sbg 80g',2000.00,2000.00,2500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:36:20','2025-12-06 21:37:25',NULL),(549,23,1,'6922130108057','6922130108057','Kaldu jamur totole 40g',6000.00,6000.00,6500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:37:08','2025-12-06 21:37:08',NULL),(550,24,1,'8992736980133','8992736980133','Sasa tepung serbaguna 70g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:39:46','2025-12-06 21:39:46',NULL),(551,24,1,'8992736980164','8992736980164','Sasa tepung kentucky 70g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:40:33','2025-12-06 21:40:33',NULL),(552,24,1,'8992736290409','8992736290409','Sasa tepung bakwan 90g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:41:23','2025-12-06 21:41:23',NULL),(553,24,1,'8992736010168','8992736010168','Sasa tepung pisang goreng 70g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:42:05','2025-12-06 21:42:05',NULL),(554,24,1,'8992770061010','8992770061010','Sajiku tepung serbaguna 75g',2000.00,2000.00,2500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:43:13','2025-12-06 21:43:13',NULL),(555,23,1,'3516663670001','3516663670001','Fermipan 11g',4500.00,4500.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:43:53','2025-12-06 21:43:53',NULL),(556,26,1,'8994326102406','8994326102406','R&W Baking soda 40g',2500.00,2500.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:45:29','2025-12-06 21:45:29',NULL),(557,22,1,'8999999514006','8999999514006','Kecap manis bango 77g 3000',2600.00,2600.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:46:12','2025-12-06 21:46:12',NULL),(558,22,1,'8998888000064','8998888000064','Delmonte Saos Carbonara 180g',13000.00,13000.00,14000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:47:16','2025-12-28 18:45:26',NULL),(559,25,1,'8998866203869','8998866203869','Sedaap kaldu ayam 250g',8900.00,8900.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:48:34','2025-12-06 21:48:34',NULL),(560,26,1,'8994326102383','8994326102383','BAking powder R&W 15g biru',1500.00,1500.00,2000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:49:43','2025-12-28 19:04:01',NULL),(561,26,1,'8994326104875','8994326104875','Coklat bubuk R&W 10g',2800.00,2800.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:50:39','2025-12-06 21:50:39',NULL),(562,26,1,'8994326108255','8994326108255','Penstabil R&W 25g',2800.00,2800.00,3000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:52:30','2025-12-06 21:52:30',NULL),(563,25,1,'8999999516215','8999999516215','Royco sapi 220g',9500.00,9500.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:54:13','2025-12-06 21:54:13',NULL),(564,22,1,'8998888461810','8998888461810','Maestro mayo pedas 100g',4500.00,4500.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:55:55','2025-12-06 21:55:55',NULL),(565,23,1,'8990064001704','8990064001704','WInstar Dipglaze strawberry 200g',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:56:53','2025-12-06 21:59:27',NULL),(566,23,1,'8990064001711','8990064001711','Winstar dipglaze Taro 200g',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:58:05','2025-12-06 21:59:15',NULL),(567,23,1,'8990064001728','8990064001728','Winstar dipglaze tiramisu 200g',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 21:59:04','2025-12-06 21:59:04',NULL),(568,26,1,'8994326108620','8994326108620','Coklat bubuk R&W 250g',39000.00,39000.00,40000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:01:08','2025-12-06 22:01:08',NULL),(569,23,1,'8997014450155','8997014450155','Prochiz Spreedy keju oles 160g',12000.00,12000.00,12500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:03:19','2025-12-06 22:03:19',NULL),(570,23,1,'8990064001742','8990064001742','Winstar dipglaze cheese 200g',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:04:34','2025-12-06 22:04:34',NULL),(571,22,1,'8993200668250','8993200668250','Mamayo mayo 100g',3500.00,3500.00,4000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:05:22','2025-12-06 22:05:22',NULL),(572,23,1,'8990064001735','8990064001735','Winstar dipglaze greentea 200g',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:06:07','2025-12-06 22:06:07',NULL),(573,26,1,'8994326108231','8994326108231','R&W Baking powder 15g kuning',1500.00,1500.00,2000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:08:58','2025-12-28 19:04:24',NULL),(574,23,1,'8996001526231','8996001526231','Spageti wow bolognise 76g',3000.00,3000.00,3500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:10:34','2025-12-06 22:10:34',NULL),(575,22,1,'8998888461117','8998888461117','Maestro mayo premium 100g',4500.00,4500.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:11:24','2025-12-06 22:11:24',NULL),(576,23,1,'8990064001698','8990064001698','Winstar Dip Glaze white 200g',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-06 22:12:30','2025-12-06 22:12:30',NULL),(577,24,1,'8994451990039','8994451990039','Tepung bumbu syaza 400g',9000.00,9000.00,10000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-07 17:34:44','2025-12-07 17:34:44',NULL),(578,21,1,'8997023078708','8997023078708','Roti burger isi 6 bernardi',11500.00,11500.00,13500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-07 18:12:22','2025-12-07 18:15:51',NULL),(579,26,1,'DCPN7736','DCPN7736','amanda mentega 200g',4500.00,4500.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-07 18:12:47','2025-12-07 18:15:51',NULL),(580,21,1,'BHQF6324','BHQF6324','bakso ikan indomina 500g',16500.00,16500.00,18500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-07 18:50:55','2025-12-07 18:51:44',NULL),(581,23,1,'ZENB6032','ZENB6032','Bawang merah goreng BJ',5000.00,5000.00,5500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:34:24','2025-12-28 18:37:19',NULL),(582,23,1,'JODL7138','JODL7138','Bawang putih goreng bj',7000.00,7000.00,7500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:35:08','2025-12-28 18:35:08',NULL),(583,26,1,'RKOM3836','RKOM3836','pewarna R&W 12.5ml',2000.00,2000.00,2500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:36:03','2025-12-28 18:36:03',NULL),(584,26,1,'APRB6392','APRB6392','RedBell Perisa 55ml',5500.00,5500.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:37:00','2025-12-28 18:37:00',NULL),(585,26,1,'MZCQ2951','MZCQ2951','Kapal lajar essense 20ml',4500.00,4500.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:38:12','2025-12-28 18:38:12',NULL),(586,26,1,'8997002600128','8997002600128','RM coklat batang',15000.00,15000.00,16000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:39:26','2025-12-28 18:40:05',NULL),(587,26,1,'8990064001872','8990064001872','Winstar Coklat batang',10000.00,10000.00,11000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:41:02','2025-12-28 18:41:02',NULL),(588,24,1,'8997214470489','8997214470489','Teoung roti putih primera',4500.00,4500.00,5000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:42:13','2025-12-28 18:43:21',NULL),(589,24,1,'8997214470380','8997214470380','Tepung Roti bubble crumbs',8000.00,8000.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:42:58','2025-12-28 18:42:58',NULL),(590,21,1,'WFIR2621','WFIR2621','gorengan tusuk',6750.00,6750.00,8500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:50:45','2025-12-28 18:54:14',NULL),(591,24,1,'UZFA0791','UZFA0791','tepung terigu 1kg',7000.00,7000.00,8000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 18:52:16','2025-12-28 18:54:14',NULL),(592,25,1,'TSLF8987','TSLF8987','bumbu bakso polos',12500.00,12500.00,13000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:05:07','2025-12-28 19:05:07',NULL),(593,25,1,'UPJS6094','UPJS6094','bumbu kuah bakso kepala sapi',11000.00,11000.00,12000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:05:46','2025-12-28 19:05:46',NULL),(594,23,1,'KLPT9149','KLPT9149','Minyak kelapa gading 700ml',14000.00,14000.00,15000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:06:20','2025-12-28 19:06:20',NULL),(595,23,1,'ITEL2939','ITEL2939','Minyak kita 2lt',34500.00,34500.00,35500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:07:00','2025-12-28 19:07:00',NULL),(596,23,1,'8994094000577','8994094000577','dairy Champ kaleng skm 480g',11500.00,11500.00,12500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:07:46','2025-12-28 19:07:46',NULL),(597,23,1,'8993007002967','8993007002967','tiga sapi skm 490g',11500.00,11500.00,12500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:08:30','2025-12-28 19:08:30',NULL),(598,23,1,'8992696426528','8992696426528','SKM Carnation 488g',13000.00,13000.00,14000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:09:07','2025-12-28 19:09:07',NULL),(599,23,1,'VMJN1921','VMJN1921','Teh gopek seduh',3000.00,3000.00,4000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:10:04','2025-12-28 19:10:04',NULL),(600,23,1,'8719200177314','8719200177314','Blueband renteng',1500.00,1500.00,2000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:11:04','2025-12-28 19:11:04',NULL),(601,23,1,'ZFTM7988','ZFTM7988','Garam 200g',1500.00,1500.00,2000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:12:31','2025-12-28 19:12:31',NULL),(602,23,1,'MKMI7363','MKMI7363','garam 500g',3000.00,3000.00,3500.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:12:49','2025-12-28 19:12:49',NULL),(603,23,1,'IXOY8418','IXOY8418','Krupuk seblak 250g',5000.00,5000.00,6000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:13:22','2025-12-28 19:13:22',NULL),(604,25,1,'ADWA1588','ADWA1588','Tahu bakso mak\'e isi 25',28000.00,28000.00,30000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2025-12-28 19:23:27','2025-12-28 19:24:32',NULL),(605,22,1,'8992845859252','8992845859252','primaagung mentai 500g',27000.00,27000.00,29000.00,NULL,NULL,0.000,NULL,NULL,1,0,1,'2026-01-03 20:28:43','2026-01-03 20:28:43',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion_product`
--

DROP TABLE IF EXISTS `promotion_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion_product` (
  `promotion_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`promotion_id`,`product_id`),
  KEY `promotion_product_product_id_foreign` (`product_id`),
  CONSTRAINT `promotion_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_product_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_product`
--

LOCK TABLES `promotion_product` WRITE;
/*!40000 ALTER TABLE `promotion_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotion_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `value` decimal(18,2) NOT NULL,
  `minimum_transaction` decimal(18,2) NOT NULL DEFAULT 0.00,
  `maximum_discount` decimal(18,2) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotions_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
INSERT INTO `promotions` VALUES (1,'HEMAT10','Diskon 10%','percent',10.00,50000.00,25000.00,'2026-08-01 15:39:53','2027-08-02 15:39:53',1,'2026-08-02 08:39:53','2026-08-02 08:39:53');
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_unit_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(18,3) NOT NULL,
  `conversion_factor` decimal(18,6) NOT NULL DEFAULT 1.000000,
  `base_quantity` decimal(18,3) NOT NULL DEFAULT 0.000,
  `unit_cost` decimal(18,2) NOT NULL,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(18,2) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expired_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_product_id_foreign` (`product_id`),
  KEY `purchase_items_product_unit_id_foreign` (`product_unit_id`),
  KEY `purchase_items_unit_id_foreign` (`unit_id`),
  CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_items_product_unit_id_foreign` FOREIGN KEY (`product_unit_id`) REFERENCES `product_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_payments`
--

DROP TABLE IF EXISTS `purchase_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `method` varchar(30) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_payments_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_payments_created_by_foreign` (`created_by`),
  CONSTRAINT `purchase_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `purchase_payments_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_payments`
--

LOCK TABLES `purchase_payments` WRITE;
/*!40000 ALTER TABLE `purchase_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_return_items`
--

DROP TABLE IF EXISTS `purchase_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_return_id` bigint(20) unsigned NOT NULL,
  `purchase_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(18,3) NOT NULL,
  `unit_cost` decimal(18,2) NOT NULL,
  `subtotal` decimal(18,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_return_items_purchase_return_id_foreign` (`purchase_return_id`),
  KEY `purchase_return_items_purchase_item_id_foreign` (`purchase_item_id`),
  KEY `purchase_return_items_product_id_foreign` (`product_id`),
  CONSTRAINT `purchase_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_return_items_purchase_item_id_foreign` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`),
  CONSTRAINT `purchase_return_items_purchase_return_id_foreign` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_return_items`
--

LOCK TABLES `purchase_return_items` WRITE;
/*!40000 ALTER TABLE `purchase_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_returns`
--

DROP TABLE IF EXISTS `purchase_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(60) NOT NULL,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `returned_at` datetime NOT NULL,
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_returns_number_unique` (`number`),
  KEY `purchase_returns_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_returns_created_by_foreign` (`created_by`),
  CONSTRAINT `purchase_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `purchase_returns_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_returns`
--

LOCK TABLES `purchase_returns` WRITE;
/*!40000 ALTER TABLE `purchase_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(60) NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `supplier_invoice` varchar(255) DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `additional_cost` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `status` varchar(20) NOT NULL DEFAULT 'received',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_number_unique` (`number`),
  KEY `purchases_store_id_foreign` (`store_id`),
  KEY `purchases_warehouse_id_foreign` (`warehouse_id`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_created_by_foreign` (`created_by`),
  KEY `purchases_purchase_date_payment_status_index` (`purchase_date`,`payment_status`),
  CONSTRAINT `purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `purchases_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(2,1),(2,2),(2,4),(3,1),(3,2),(3,4),(4,1),(4,2),(4,4),(5,1),(5,2),(6,1),(6,2),(7,1),(7,2),(7,4),(8,1),(8,2),(8,3),(9,1),(9,2),(9,3),(10,1),(10,2),(10,3),(11,1),(11,2),(12,1),(12,2),(12,3),(13,1),(13,2),(13,3),(14,1),(14,2),(14,3),(15,1),(15,2),(15,3),(16,1),(16,2),(16,3),(17,1),(17,2),(17,3),(18,1),(18,2),(18,3),(19,1),(19,2),(19,3),(20,1),(20,2),(20,3),(21,1),(21,2),(21,3),(22,1),(22,2),(22,3),(23,1),(23,2),(23,3),(24,1),(24,2),(24,3),(25,1),(25,2),(26,1),(26,2),(27,1),(27,2),(28,1),(28,2),(29,1),(29,2),(30,1),(30,2),(31,1),(31,2),(31,3),(32,1),(32,2),(33,1),(33,2),(34,1),(34,2),(34,3),(35,1),(35,2),(36,1),(36,2),(37,1),(38,1),(38,2),(39,1),(39,2);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'owner','web','2026-08-02 08:39:51','2026-08-02 08:39:51'),(2,'admin','web','2026-08-02 08:39:51','2026-08-02 08:39:51'),(3,'warehouse','web','2026-08-02 08:39:51','2026-08-02 08:39:51'),(4,'cashier','web','2026-08-02 08:39:51','2026-08-02 08:39:51');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_unit_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(60) NOT NULL,
  `quantity` decimal(18,3) NOT NULL,
  `conversion_factor` decimal(18,6) NOT NULL DEFAULT 1.000000,
  `base_quantity` decimal(18,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(18,2) NOT NULL,
  `unit_cost` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(18,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_product_id_foreign` (`product_id`),
  KEY `sale_items_product_unit_id_foreign` (`product_unit_id`),
  KEY `sale_items_unit_id_foreign` (`unit_id`),
  CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_items_product_unit_id_foreign` FOREIGN KEY (`product_unit_id`) REFERENCES `product_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_payments`
--

DROP TABLE IF EXISTS `sale_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `method` varchar(30) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_payments_sale_id_foreign` (`sale_id`),
  KEY `sale_payments_created_by_foreign` (`created_by`),
  CONSTRAINT `sale_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `sale_payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_payments`
--

LOCK TABLES `sale_payments` WRITE;
/*!40000 ALTER TABLE `sale_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_return_items`
--

DROP TABLE IF EXISTS `sale_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_return_id` bigint(20) unsigned NOT NULL,
  `sale_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(18,3) NOT NULL,
  `unit_price` decimal(18,2) NOT NULL,
  `subtotal` decimal(18,2) NOT NULL,
  `condition` varchar(20) NOT NULL DEFAULT 'restock',
  `batch_number` varchar(100) DEFAULT NULL,
  `expired_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_items_sale_return_id_foreign` (`sale_return_id`),
  KEY `sale_return_items_sale_item_id_foreign` (`sale_item_id`),
  KEY `sale_return_items_product_id_foreign` (`product_id`),
  CONSTRAINT `sale_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_return_items_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`),
  CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_return_items`
--

LOCK TABLES `sale_return_items` WRITE;
/*!40000 ALTER TABLE `sale_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_returns`
--

DROP TABLE IF EXISTS `sale_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(60) NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `returned_at` datetime NOT NULL,
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_method` varchar(30) NOT NULL DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_returns_number_unique` (`number`),
  KEY `sale_returns_sale_id_foreign` (`sale_id`),
  KEY `sale_returns_created_by_foreign` (`created_by`),
  CONSTRAINT `sale_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `sale_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_returns`
--

LOCK TABLES `sale_returns` WRITE;
/*!40000 ALTER TABLE `sale_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(60) NOT NULL,
  `store_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `cashier_shift_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `cashier_id` bigint(20) unsigned NOT NULL,
  `promotion_id` bigint(20) unsigned DEFAULT NULL,
  `sold_at` datetime NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `promotion_discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `manual_discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(20) NOT NULL DEFAULT 'paid',
  `status` varchar(30) NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `points_earned` int(10) unsigned NOT NULL DEFAULT 0,
  `points_redeemed` int(10) unsigned NOT NULL DEFAULT 0,
  `points_discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `points_earn_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `points_redeem_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `points_awarded_at` timestamp NULL DEFAULT NULL,
  `points_reversed` int(10) unsigned NOT NULL DEFAULT 0,
  `points_redeemed_restored` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_number_unique` (`number`),
  KEY `sales_store_id_foreign` (`store_id`),
  KEY `sales_warehouse_id_foreign` (`warehouse_id`),
  KEY `sales_cashier_shift_id_foreign` (`cashier_shift_id`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_cashier_id_foreign` (`cashier_id`),
  KEY `sales_promotion_id_foreign` (`promotion_id`),
  KEY `sales_sold_at_payment_status_index` (`sold_at`,`payment_status`),
  CONSTRAINT `sales_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_cashier_shift_id_foreign` FOREIGN KEY (`cashier_shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'expiry_warning_days','30','2026-08-02 08:39:53','2026-08-02 08:39:53'),(2,'allow_negative_stock','0','2026-08-02 08:39:54','2026-08-02 08:39:54'),(3,'brand_kicker','GENERAL AFFAIR · SIGAP & HANDAL','2026-08-02 08:53:17','2026-08-02 08:53:17'),(4,'brand_tagline','Sigap & Handal','2026-08-02 08:53:17','2026-08-02 08:53:17'),(5,'login_hero_title','Kelola operasional dan stok dalam satu tempat.','2026-08-02 08:53:17','2026-08-02 08:53:17'),(6,'login_hero_description','Pantau penjualan, persediaan, pembelian, audit, dan laporan dengan data yang terhubung serta mudah ditindaklanjuti.','2026-08-02 08:53:17','2026-08-02 08:53:17'),(7,'login_welcome_title','Selamat datang','2026-08-02 08:53:17','2026-08-02 08:53:17'),(8,'login_welcome_description','Masukkan akun Anda untuk melanjutkan ke sistem pengelolaan stok.','2026-08-02 08:53:17','2026-08-02 08:53:17'),(9,'login_benefit_1','Data terpusat','2026-08-02 08:53:17','2026-08-02 08:53:17'),(10,'login_benefit_2','Pemantauan cepat','2026-08-02 08:53:17','2026-08-02 08:53:17'),(11,'login_benefit_3','Akses terkontrol','2026-08-02 08:53:17','2026-08-02 08:53:17'),(12,'login_button_text','Masuk ke Sistem','2026-08-02 08:53:17','2026-08-02 08:53:17'),(13,'login_security_text','Sesi dan informasi akun Anda dilindungi.','2026-08-02 08:53:17','2026-08-02 08:53:17'),(14,'theme_preset','navy_gold','2026-08-02 08:53:17','2026-08-06 05:03:01'),(15,'font_scale','medium','2026-08-02 08:53:17','2026-08-02 08:53:17'),(16,'appearance_mode','light','2026-08-02 08:53:17','2026-08-02 08:53:17'),(17,'surface_finish','normal','2026-08-02 08:53:17','2026-08-02 08:53:17'),(18,'loyalty_enabled','1','2026-08-02 08:53:17','2026-08-02 08:53:17'),(19,'loyalty_earn_amount','10000','2026-08-02 08:53:17','2026-08-02 08:53:17'),(20,'loyalty_redeem_value','100','2026-08-02 08:53:17','2026-08-02 08:53:17'),(21,'loyalty_max_redeem_percent','20','2026-08-02 08:53:17','2026-08-02 08:53:17');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_batch_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `quantity` decimal(18,3) NOT NULL,
  `stock_before` decimal(18,3) NOT NULL,
  `stock_after` decimal(18,3) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_warehouse_id_foreign` (`warehouse_id`),
  KEY `stock_movements_product_batch_id_foreign` (`product_batch_id`),
  KEY `stock_movements_created_by_foreign` (`created_by`),
  KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `stock_movement_lookup` (`product_id`,`warehouse_id`,`created_at`),
  CONSTRAINT `stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_product_batch_id_foreign` FOREIGN KEY (`product_batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_opname_items`
--

DROP TABLE IF EXISTS `stock_opname_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_opname_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_opname_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `system_quantity` decimal(18,3) NOT NULL,
  `physical_quantity` decimal(18,3) DEFAULT NULL,
  `difference` decimal(18,3) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_opname_items_stock_opname_id_product_id_unique` (`stock_opname_id`,`product_id`),
  KEY `stock_opname_items_product_id_foreign` (`product_id`),
  CONSTRAINT `stock_opname_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_opname_items_stock_opname_id_foreign` FOREIGN KEY (`stock_opname_id`) REFERENCES `stock_opnames` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_opname_items`
--

LOCK TABLES `stock_opname_items` WRITE;
/*!40000 ALTER TABLE `stock_opname_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_opname_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_opnames`
--

DROP TABLE IF EXISTS `stock_opnames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_opnames` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(60) NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `opname_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_opnames_number_unique` (`number`),
  KEY `stock_opnames_warehouse_id_foreign` (`warehouse_id`),
  KEY `stock_opnames_created_by_foreign` (`created_by`),
  KEY `stock_opnames_approved_by_foreign` (`approved_by`),
  CONSTRAINT `stock_opnames_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_opnames_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_opnames_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_opnames`
--

LOCK TABLES `stock_opnames` WRITE;
/*!40000 ALTER TABLE `stock_opnames` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_opnames` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stocks`
--

DROP TABLE IF EXISTS `stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(18,3) NOT NULL DEFAULT 0.000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stocks_warehouse_id_product_id_unique` (`warehouse_id`,`product_id`),
  KEY `stocks_product_id_foreign` (`product_id`),
  CONSTRAINT `stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stocks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=303 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stocks`
--

LOCK TABLES `stocks` WRITE;
/*!40000 ALTER TABLE `stocks` DISABLE KEYS */;
INSERT INTO `stocks` VALUES (1,1,304,1000.000,'2025-11-10 20:53:07','2025-12-28 18:45:12'),(2,1,305,12.000,'2025-11-10 21:03:44','2025-11-10 21:09:00'),(3,1,306,12.000,'2025-11-10 21:08:52','2025-11-10 21:08:52'),(4,1,307,11.000,'2025-11-10 21:10:07','2025-11-10 23:20:28'),(5,1,308,6.000,'2025-11-10 21:10:56','2025-11-10 23:44:59'),(6,1,309,6.000,'2025-11-10 21:11:55','2025-11-10 21:11:55'),(7,1,310,6.000,'2025-11-10 21:13:25','2025-11-10 21:13:25'),(8,1,311,16.000,'2025-11-10 21:14:15','2025-11-10 21:14:15'),(9,1,312,6.000,'2025-11-10 21:15:11','2025-12-07 18:15:50'),(10,1,313,25.000,'2025-11-10 21:17:19','2025-11-10 21:17:19'),(11,1,314,10000.000,'2025-11-10 21:21:52','2025-12-28 18:43:45'),(12,1,315,8.000,'2025-11-10 21:22:48','2025-11-10 21:22:48'),(13,1,316,6.000,'2025-11-10 21:23:48','2025-11-10 21:23:48'),(14,1,317,10.000,'2025-11-10 21:25:34','2025-11-10 21:25:34'),(15,1,318,27.000,'2025-11-10 21:26:42','2025-12-28 18:54:14'),(16,1,319,20.000,'2025-11-10 21:27:12','2025-11-10 21:27:12'),(17,1,320,20.000,'2025-11-10 21:27:34','2025-11-10 21:27:34'),(18,1,321,10.000,'2025-11-10 21:28:28','2025-11-10 21:28:28'),(19,1,322,8.000,'2025-11-10 21:29:41','2025-11-10 21:29:41'),(20,1,323,23.000,'2025-11-10 21:33:10','2025-11-30 19:29:45'),(21,1,324,29.000,'2025-11-10 21:34:49','2025-11-30 19:27:11'),(22,1,325,15.000,'2025-11-10 21:36:00','2025-11-10 21:36:00'),(23,1,326,14.000,'2025-11-10 21:37:15','2025-11-10 21:37:15'),(24,1,327,12.000,'2025-11-10 21:37:59','2025-11-10 21:37:59'),(25,1,328,10.000,'2025-11-10 21:39:49','2025-11-10 21:39:49'),(26,1,329,10.000,'2025-11-10 21:40:40','2025-11-10 21:40:40'),(27,1,330,11.000,'2025-11-10 21:41:26','2025-11-10 21:41:26'),(28,1,331,15.000,'2025-11-10 21:46:02','2025-11-10 21:46:02'),(29,1,332,8.000,'2025-11-10 21:56:45','2025-11-10 21:56:45'),(30,1,333,11.000,'2025-11-11 00:00:50','2025-12-28 18:54:14'),(31,1,334,20.000,'2025-11-11 00:04:21','2025-11-30 17:57:47'),(32,1,335,39.000,'2025-11-11 00:05:53','2025-12-28 18:54:14'),(33,1,336,19.000,'2025-11-11 00:06:58','2025-12-28 18:54:14'),(34,1,337,34.000,'2025-11-11 00:08:11','2025-11-11 00:08:11'),(35,1,338,10.000,'2025-11-11 00:10:13','2025-11-11 00:10:13'),(36,1,339,30.000,'2025-11-11 00:10:41','2025-11-11 00:10:41'),(37,1,340,18.000,'2025-11-11 00:11:38','2025-12-28 18:54:14'),(38,1,341,6.000,'2025-11-11 00:12:21','2025-11-11 00:12:21'),(39,1,342,14.000,'2025-11-11 00:13:17','2025-11-11 00:13:17'),(40,1,343,19.000,'2025-11-11 00:14:16','2026-01-04 16:36:14'),(41,1,344,15.000,'2025-11-11 00:15:09','2025-11-11 00:15:09'),(42,1,345,15.000,'2025-11-11 00:20:40','2025-11-11 00:20:40'),(43,1,346,15.000,'2025-11-11 00:21:21','2025-11-11 00:21:21'),(44,1,347,15.000,'2025-11-11 00:22:07','2025-11-11 00:22:07'),(45,1,348,15.000,'2025-11-11 00:26:27','2025-11-11 00:26:27'),(46,1,349,15.000,'2025-11-11 00:27:05','2025-11-11 00:27:05'),(47,1,350,15.000,'2025-11-11 00:27:44','2025-11-11 00:27:44'),(48,1,351,26.000,'2025-11-11 00:29:53','2025-11-11 00:29:53'),(49,1,352,19.000,'2025-11-11 00:30:58','2025-12-07 18:51:44'),(50,1,353,30.000,'2025-11-11 00:31:35','2025-11-11 00:31:35'),(51,1,354,20.000,'2025-11-11 00:32:31','2025-11-11 00:32:31'),(52,1,355,22.000,'2025-11-11 00:33:26','2025-11-11 00:33:26'),(53,1,356,6.000,'2025-11-11 00:36:42','2025-11-11 00:36:42'),(54,1,357,6.000,'2025-11-11 00:37:16','2025-11-11 00:37:16'),(55,1,358,6.000,'2025-11-11 00:37:47','2025-11-11 00:37:47'),(56,1,359,8.000,'2025-11-11 00:39:21','2025-11-11 00:39:21'),(57,1,360,16.000,'2025-11-11 00:42:20','2025-11-11 00:42:20'),(58,1,361,100.000,'2025-11-11 00:42:53','2025-11-11 00:42:53'),(59,1,362,200.000,'2025-11-11 00:43:23','2025-11-11 00:43:23'),(60,1,363,100.000,'2025-11-11 00:43:58','2025-11-11 00:43:58'),(61,1,364,100.000,'2025-11-11 00:44:28','2025-11-11 00:44:28'),(62,1,365,20.000,'2025-11-11 00:45:26','2025-11-11 00:45:26'),(63,1,366,24.000,'2025-11-11 12:02:03','2025-11-11 12:02:48'),(64,1,367,5.000,'2025-11-30 16:38:40','2025-11-30 16:38:40'),(65,1,368,4.000,'2025-11-30 16:39:27','2025-11-30 16:39:27'),(66,1,369,3.000,'2025-11-30 16:41:49','2025-11-30 16:41:49'),(67,1,370,15.000,'2025-11-30 16:43:03','2025-11-30 16:43:03'),(68,1,371,6.000,'2025-11-30 16:45:17','2025-11-30 16:45:17'),(69,1,372,24.000,'2025-11-30 16:46:37','2025-11-30 16:46:37'),(70,1,373,4.000,'2025-11-30 17:10:20','2025-11-30 17:10:20'),(71,1,374,6.000,'2025-11-30 17:25:00','2025-11-30 17:25:00'),(72,1,375,5.000,'2025-11-30 17:27:57','2025-11-30 17:27:57'),(73,1,376,11.000,'2025-11-30 17:28:43','2025-11-30 19:27:11'),(74,1,377,12.000,'2025-11-30 17:34:23','2025-11-30 18:57:44'),(75,1,378,12.000,'2025-11-30 17:35:27','2025-11-30 17:35:27'),(76,1,379,12.000,'2025-11-30 17:36:24','2025-11-30 17:36:24'),(77,1,380,12.000,'2025-11-30 17:38:39','2025-11-30 18:56:56'),(78,1,381,10.000,'2025-11-30 17:39:18','2025-11-30 18:57:19'),(79,1,382,5.000,'2025-11-30 17:54:21','2025-11-30 17:54:21'),(80,1,383,6.000,'2025-11-30 17:55:28','2025-11-30 17:55:28'),(81,1,384,6.000,'2025-11-30 17:56:43','2025-12-06 19:23:11'),(82,1,385,6.000,'2025-11-30 17:57:33','2025-11-30 17:57:33'),(83,1,386,6.000,'2025-11-30 18:00:04','2025-11-30 18:00:04'),(84,1,387,10.000,'2025-11-30 18:23:11','2025-11-30 18:23:11'),(85,1,388,6.000,'2025-11-30 18:30:21','2025-11-30 18:30:21'),(86,1,389,12.000,'2025-11-30 18:46:34','2025-11-30 18:46:34'),(87,1,390,12.000,'2025-11-30 18:53:43','2025-11-30 18:53:43'),(88,1,391,12.000,'2025-11-30 18:54:43','2025-11-30 18:54:43'),(89,1,392,11.000,'2025-11-30 18:55:26','2025-11-30 19:27:11'),(90,1,393,12.000,'2025-11-30 19:00:06','2025-11-30 19:00:06'),(91,1,394,18.000,'2025-12-02 16:28:17','2025-12-02 16:28:17'),(92,1,395,12.000,'2025-12-02 16:29:16','2025-12-02 16:29:16'),(93,1,396,6.000,'2025-12-02 16:31:34','2025-12-07 18:51:44'),(94,1,397,28.000,'2025-12-02 16:32:35','2025-12-02 16:32:35'),(95,1,398,12.000,'2025-12-02 16:33:21','2025-12-07 18:51:44'),(96,1,399,12.000,'2025-12-02 16:34:23','2025-12-28 18:54:14'),(97,1,400,12.000,'2025-12-02 16:36:09','2025-12-02 16:36:09'),(98,1,401,16.000,'2025-12-02 16:37:22','2025-12-02 16:37:22'),(99,1,402,3.000,'2025-12-02 16:38:37','2025-12-02 16:38:37'),(100,1,403,10.000,'2025-12-02 16:44:10','2025-12-02 16:44:10'),(101,1,404,1500.000,'2025-12-02 16:45:14','2025-12-28 18:44:38'),(102,1,405,22000.000,'2025-12-02 16:45:51','2025-12-28 18:44:15'),(103,1,406,3000.000,'2025-12-02 16:46:59','2025-12-28 18:43:57'),(104,1,407,8.000,'2025-12-02 16:48:09','2025-12-02 16:48:09'),(105,1,408,11.000,'2025-12-02 16:49:02','2025-12-02 16:49:02'),(106,1,409,8.000,'2025-12-02 16:50:41','2025-12-07 18:03:10'),(107,1,410,7.000,'2025-12-02 16:51:07','2025-12-02 16:51:07'),(108,1,411,11.000,'2025-12-02 16:53:06','2025-12-28 18:54:14'),(109,1,412,16.000,'2025-12-02 16:54:14','2025-12-02 17:04:24'),(110,1,413,10.000,'2025-12-02 16:58:12','2025-12-02 16:58:12'),(111,1,414,3.000,'2025-12-02 16:59:28','2025-12-02 16:59:28'),(112,1,415,12.000,'2025-12-02 17:00:48','2025-12-28 19:20:06'),(113,1,416,15.000,'2025-12-02 17:02:07','2025-12-02 17:03:53'),(114,1,417,13.000,'2025-12-02 17:03:43','2025-12-02 17:03:43'),(115,1,418,17.000,'2025-12-02 17:05:10','2025-12-02 17:05:10'),(116,1,419,9.000,'2025-12-02 17:06:18','2025-12-02 17:06:18'),(117,1,420,12.000,'2025-12-02 17:06:52','2025-12-02 17:06:52'),(118,1,421,20.000,'2025-12-02 17:08:04','2025-12-02 17:08:04'),(119,1,422,12.000,'2025-12-02 17:09:19','2025-12-02 17:09:19'),(120,1,423,9.000,'2025-12-02 17:10:27','2025-12-07 18:18:16'),(121,1,424,10.000,'2025-12-02 17:11:38','2025-12-02 17:11:38'),(122,1,425,7.000,'2025-12-02 17:12:34','2025-12-02 17:12:34'),(123,1,426,18.000,'2025-12-02 17:13:58','2025-12-02 17:13:58'),(124,1,427,12.000,'2025-12-02 17:15:00','2025-12-02 17:15:00'),(125,1,428,13.000,'2025-12-02 17:15:57','2025-12-02 17:15:57'),(126,1,429,10.000,'2025-12-02 17:17:11','2025-12-02 17:17:11'),(127,1,430,15.000,'2025-12-02 17:18:37','2025-12-02 17:18:37'),(128,1,431,4.000,'2025-12-06 17:06:36','2025-12-06 17:06:36'),(129,1,432,12.000,'2025-12-06 17:09:23','2025-12-06 17:09:23'),(130,1,433,14.000,'2025-12-06 17:12:07','2025-12-06 17:12:07'),(131,1,434,20.000,'2025-12-06 17:14:04','2025-12-28 19:18:22'),(132,1,435,7.000,'2025-12-06 17:15:04','2025-12-06 17:15:04'),(133,1,436,11.000,'2025-12-06 17:15:53','2025-12-06 17:15:53'),(134,1,437,19.000,'2025-12-06 17:17:30','2025-12-06 17:25:07'),(135,1,438,4.000,'2025-12-06 17:20:28','2025-12-06 17:20:28'),(136,1,439,9.000,'2025-12-06 17:22:09','2025-12-06 17:22:09'),(137,1,440,46.000,'2025-12-06 17:24:56','2025-12-06 17:24:56'),(138,1,441,40.000,'2025-12-06 17:26:44','2025-12-06 17:26:44'),(139,1,442,50.000,'2025-12-06 17:31:26','2025-12-06 17:31:26'),(140,1,443,118.000,'2025-12-06 17:32:41','2025-12-06 17:32:41'),(141,1,444,15.000,'2025-12-06 17:43:03','2025-12-06 17:43:03'),(142,1,445,5.000,'2025-12-06 17:44:14','2025-12-06 22:22:38'),(143,1,446,20.000,'2025-12-06 17:55:08','2025-12-06 18:09:00'),(144,1,447,7.000,'2025-12-06 17:56:07','2025-12-06 17:56:07'),(145,1,448,19.000,'2025-12-06 17:57:00','2025-12-06 17:57:00'),(146,1,449,1.000,'2025-12-06 17:57:41','2025-12-06 17:57:41'),(147,1,450,23.000,'2025-12-06 18:00:53','2025-12-06 18:00:53'),(148,1,451,8.000,'2025-12-06 18:01:48','2025-12-28 19:18:22'),(149,1,452,5.000,'2025-12-06 18:07:48','2025-12-06 18:07:48'),(150,1,453,12.000,'2025-12-06 18:08:45','2025-12-06 18:08:45'),(151,1,454,33.000,'2025-12-06 18:11:25','2025-12-06 18:11:25'),(152,1,455,15.000,'2025-12-06 18:12:33','2025-12-06 18:12:33'),(153,1,456,11.000,'2025-12-06 18:13:16','2025-12-06 18:13:16'),(154,1,457,1.000,'2025-12-06 18:14:47','2025-12-06 18:32:47'),(155,1,458,11.000,'2025-12-06 18:15:36','2025-12-06 18:15:36'),(156,1,459,10.000,'2025-12-06 18:17:10','2025-12-06 22:19:43'),(157,1,460,40.000,'2025-12-06 18:18:29','2025-12-06 18:18:29'),(158,1,461,28.000,'2025-12-06 18:21:07','2025-12-06 18:21:07'),(159,1,462,12.000,'2025-12-06 18:21:52','2025-12-06 18:21:52'),(160,1,463,7.000,'2025-12-06 18:25:10','2025-12-06 18:25:10'),(161,1,464,7.000,'2025-12-06 18:25:51','2025-12-06 18:25:51'),(162,1,465,9.000,'2025-12-06 18:32:32','2025-12-06 18:32:32'),(163,1,466,6.000,'2025-12-06 18:37:44','2025-12-06 18:37:44'),(164,1,467,6.000,'2025-12-06 18:39:45','2025-12-06 18:39:45'),(165,1,468,25.000,'2025-12-06 18:40:37','2025-12-06 18:40:37'),(166,1,469,10.000,'2025-12-06 18:41:37','2025-12-06 18:41:37'),(167,1,470,10.000,'2025-12-06 18:43:00','2025-12-06 18:48:28'),(168,1,471,4.000,'2025-12-06 18:46:03','2025-12-06 18:46:03'),(169,1,472,8.000,'2025-12-06 18:48:03','2025-12-06 18:48:03'),(170,1,473,6.000,'2025-12-06 18:50:19','2025-12-06 18:50:19'),(171,1,474,5.000,'2025-12-06 18:51:41','2025-12-06 18:51:41'),(172,1,475,4.000,'2025-12-06 18:56:51','2025-12-06 18:56:51'),(173,1,476,6.000,'2025-12-06 18:58:06','2025-12-06 18:58:06'),(174,1,477,11.000,'2025-12-06 18:58:53','2025-12-06 18:58:53'),(175,1,478,5.000,'2025-12-06 19:02:21','2025-12-06 19:02:21'),(176,1,479,8.000,'2025-12-06 19:03:12','2025-12-06 19:03:12'),(177,1,480,7.000,'2025-12-06 19:04:29','2025-12-06 19:04:29'),(178,1,481,8.000,'2025-12-06 19:07:07','2025-12-06 19:07:07'),(179,1,482,5.000,'2025-12-06 19:09:43','2025-12-06 19:09:43'),(180,1,483,4.000,'2025-12-06 19:10:58','2025-12-06 19:10:58'),(181,1,484,5.000,'2025-12-06 19:13:17','2025-12-06 19:13:17'),(182,1,485,9.000,'2025-12-06 19:14:55','2025-12-06 19:14:55'),(183,1,486,5.000,'2025-12-06 19:15:56','2025-12-06 19:15:56'),(184,1,487,5.000,'2025-12-06 19:16:36','2025-12-06 19:16:36'),(185,1,488,5.000,'2025-12-06 19:17:09','2025-12-06 19:17:09'),(186,1,489,5.000,'2025-12-06 19:18:19','2025-12-06 19:18:19'),(187,1,490,7.000,'2025-12-06 19:18:52','2025-12-06 19:18:52'),(188,1,491,8.000,'2025-12-06 19:19:40','2025-12-06 19:19:40'),(189,1,492,5.000,'2025-12-06 19:21:07','2025-12-06 19:21:07'),(190,1,493,5.000,'2025-12-06 19:22:48','2025-12-06 19:22:48'),(191,1,494,10.000,'2025-12-06 19:28:35','2025-12-06 19:40:24'),(192,1,495,3.000,'2025-12-06 19:29:28','2025-12-06 19:40:47'),(193,1,496,17.000,'2025-12-06 19:31:17','2025-12-06 19:39:32'),(194,1,497,24.000,'2025-12-06 19:31:50','2025-12-06 19:39:57'),(195,1,498,14.000,'2025-12-06 19:32:24','2025-12-06 19:32:24'),(196,1,499,12.000,'2025-12-06 19:32:54','2025-12-06 19:39:18'),(197,1,500,7.000,'2025-12-06 19:33:31','2025-12-06 19:40:36'),(198,1,501,9.000,'2025-12-06 19:34:18','2025-12-06 19:40:09'),(199,1,502,6.000,'2025-12-06 19:35:04','2025-12-06 19:39:44'),(200,1,503,10.000,'2025-12-06 19:37:35','2025-12-06 19:37:35'),(201,1,504,21.000,'2025-12-06 19:38:56','2025-12-06 19:38:56'),(202,1,505,6.000,'2025-12-06 19:41:33','2025-12-06 19:41:33'),(203,1,506,11.000,'2025-12-06 19:42:37','2025-12-06 19:42:37'),(204,1,507,17.000,'2025-12-06 19:43:42','2025-12-06 19:43:42'),(205,1,508,20.000,'2025-12-06 19:45:00','2025-12-28 18:54:14'),(206,1,509,19.000,'2025-12-06 19:46:01','2025-12-06 19:46:01'),(207,1,510,13.000,'2025-12-06 19:47:34','2025-12-28 18:54:14'),(208,1,511,12.000,'2025-12-06 19:55:17','2025-12-06 19:55:17'),(209,1,512,22.000,'2025-12-06 19:56:50','2025-12-06 19:56:50'),(210,1,513,15.000,'2025-12-06 19:58:15','2025-12-06 19:58:15'),(211,1,514,23.000,'2025-12-06 19:59:32','2025-12-06 19:59:32'),(212,1,515,13.000,'2025-12-06 20:02:22','2025-12-06 20:02:22'),(213,1,516,10.000,'2025-12-06 20:04:13','2025-12-06 20:04:13'),(214,1,517,9.000,'2025-12-06 20:05:10','2025-12-06 20:05:10'),(215,1,518,100.000,'2025-12-06 20:06:10','2025-12-06 20:06:10'),(216,1,519,14.000,'2025-12-06 20:07:03','2025-12-06 20:07:03'),(217,1,520,13.000,'2025-12-06 20:07:56','2025-12-06 20:07:56'),(218,1,521,14.000,'2025-12-06 20:09:12','2025-12-06 20:09:12'),(219,1,522,72.000,'2025-12-06 20:10:57','2025-12-06 22:23:27'),(220,1,523,50.000,'2025-12-06 20:11:40','2025-12-06 20:11:40'),(221,1,524,3.000,'2025-12-06 20:12:21','2025-12-06 20:12:21'),(222,1,525,9.000,'2025-12-06 20:13:25','2025-12-06 20:13:25'),(223,1,526,15.000,'2025-12-06 20:14:17','2025-12-06 20:14:17'),(224,1,527,19.000,'2025-12-06 20:15:08','2025-12-28 18:46:06'),(225,1,528,16.000,'2025-12-06 20:16:01','2025-12-06 20:16:01'),(226,1,529,6.000,'2025-12-06 20:18:18','2025-12-06 20:18:18'),(227,1,530,24.000,'2025-12-06 20:19:14','2025-12-06 20:19:14'),(228,1,531,99999.000,'2025-12-06 20:19:54','2025-12-06 21:19:07'),(229,1,532,9993.000,'2025-12-06 20:20:31','2025-12-28 18:54:14'),(230,1,533,9999.000,'2025-12-06 20:23:02','2025-12-06 20:23:02'),(231,1,534,9999.000,'2025-12-06 20:27:04','2025-12-06 20:27:55'),(232,1,535,9999.000,'2025-12-06 20:27:36','2025-12-06 20:27:36'),(233,1,536,9999.000,'2025-12-06 21:13:05','2025-12-06 21:13:05'),(234,1,537,9999.000,'2025-12-06 21:16:05','2025-12-06 21:22:45'),(235,1,538,999.000,'2025-12-06 21:23:09','2025-12-06 21:23:09'),(236,1,539,999.000,'2025-12-06 21:23:37','2025-12-06 21:23:37'),(237,1,540,999.000,'2025-12-06 21:24:01','2025-12-06 21:24:01'),(238,1,541,999.000,'2025-12-06 21:24:35','2025-12-06 21:24:35'),(239,1,542,999.000,'2025-12-06 21:24:58','2025-12-06 21:24:58'),(240,1,543,10.000,'2025-12-06 21:25:49','2025-12-06 21:28:24'),(241,1,544,10.000,'2025-12-06 21:26:07','2025-12-06 21:26:07'),(242,1,545,17.000,'2025-12-06 21:27:03','2025-12-06 22:19:43'),(243,1,546,10.000,'2025-12-06 21:27:46','2025-12-06 21:27:46'),(244,1,547,8.000,'2025-12-06 21:34:15','2025-12-06 21:34:15'),(245,1,548,16.000,'2025-12-06 21:36:20','2025-12-06 21:37:25'),(246,1,549,22.000,'2025-12-06 21:37:08','2025-12-06 21:37:08'),(247,1,550,9.000,'2025-12-06 21:39:46','2025-12-06 21:39:46'),(248,1,551,6.000,'2025-12-06 21:40:33','2025-12-06 21:40:33'),(249,1,552,18.000,'2025-12-06 21:41:23','2025-12-06 21:41:23'),(250,1,553,14.000,'2025-12-06 21:42:05','2025-12-06 21:42:05'),(251,1,554,22.000,'2025-12-06 21:43:13','2025-12-06 21:43:13'),(252,1,555,9999.000,'2025-12-06 21:43:53','2025-12-06 21:43:53'),(253,1,556,13.000,'2025-12-06 21:45:29','2025-12-06 21:45:29'),(254,1,557,9999.000,'2025-12-06 21:46:12','2025-12-06 21:46:12'),(255,1,558,100.000,'2025-12-06 21:47:16','2025-12-28 18:45:26'),(256,1,559,8.000,'2025-12-06 21:48:34','2025-12-06 21:48:34'),(257,1,560,7.000,'2025-12-06 21:49:43','2025-12-28 19:04:01'),(258,1,561,15.000,'2025-12-06 21:50:39','2025-12-06 21:50:39'),(259,1,562,8.000,'2025-12-06 21:52:30','2025-12-06 21:52:30'),(260,1,563,3.000,'2025-12-06 21:54:13','2025-12-06 21:54:13'),(261,1,564,4.000,'2025-12-06 21:55:55','2025-12-06 21:55:55'),(262,1,565,2.000,'2025-12-06 21:56:53','2025-12-06 21:59:27'),(263,1,566,1.000,'2025-12-06 21:58:05','2025-12-06 21:59:15'),(264,1,567,1.000,'2025-12-06 21:59:04','2025-12-06 21:59:04'),(265,1,568,4.000,'2025-12-06 22:01:08','2025-12-06 22:01:08'),(266,1,569,16.000,'2025-12-06 22:03:19','2025-12-06 22:03:19'),(267,1,570,3.000,'2025-12-06 22:04:34','2025-12-06 22:04:34'),(268,1,571,4.000,'2025-12-06 22:05:22','2025-12-06 22:05:22'),(269,1,572,1.000,'2025-12-06 22:06:07','2025-12-06 22:06:07'),(270,1,573,9999.000,'2025-12-06 22:08:58','2025-12-28 19:04:24'),(271,1,574,12.000,'2025-12-06 22:10:34','2025-12-06 22:10:34'),(272,1,575,4.000,'2025-12-06 22:11:24','2025-12-06 22:11:24'),(273,1,576,5.000,'2025-12-06 22:12:30','2025-12-06 22:12:30'),(274,1,577,8.000,'2025-12-07 17:34:44','2025-12-07 17:34:44'),(275,1,578,998.000,'2025-12-07 18:12:22','2025-12-07 18:15:51'),(276,1,579,111099.000,'2025-12-07 18:12:47','2025-12-07 18:15:51'),(277,1,580,19.000,'2025-12-07 18:50:55','2025-12-07 18:51:44'),(278,1,581,99999.000,'2025-12-28 18:34:24','2025-12-28 18:37:19'),(279,1,582,9999.000,'2025-12-28 18:35:08','2025-12-28 18:35:08'),(280,1,583,9999.000,'2025-12-28 18:36:03','2025-12-28 18:36:03'),(281,1,584,9999.000,'2025-12-28 18:37:00','2025-12-28 18:37:00'),(282,1,585,9999.000,'2025-12-28 18:38:12','2025-12-28 18:38:12'),(283,1,586,999.000,'2025-12-28 18:39:26','2025-12-28 18:40:05'),(284,1,587,9999.000,'2025-12-28 18:41:02','2025-12-28 18:41:02'),(285,1,588,9999.000,'2025-12-28 18:42:13','2025-12-28 18:43:21'),(286,1,589,9999.000,'2025-12-28 18:42:58','2025-12-28 18:42:58'),(287,1,590,8986.000,'2025-12-28 18:50:45','2025-12-28 18:54:14'),(288,1,591,9998.000,'2025-12-28 18:52:16','2025-12-28 18:54:14'),(289,1,592,100.000,'2025-12-28 19:05:07','2025-12-28 19:05:07'),(290,1,593,100.000,'2025-12-28 19:05:46','2025-12-28 19:05:46'),(291,1,594,100.000,'2025-12-28 19:06:20','2025-12-28 19:06:20'),(292,1,595,100.000,'2025-12-28 19:07:00','2025-12-28 19:07:00'),(293,1,596,100.000,'2025-12-28 19:07:46','2025-12-28 19:07:46'),(294,1,597,100.000,'2025-12-28 19:08:30','2025-12-28 19:08:30'),(295,1,598,100.000,'2025-12-28 19:09:07','2025-12-28 19:09:07'),(296,1,599,100.000,'2025-12-28 19:10:04','2025-12-28 19:10:04'),(297,1,600,100.000,'2025-12-28 19:11:04','2025-12-28 19:11:04'),(298,1,601,100.000,'2025-12-28 19:12:31','2025-12-28 19:12:31'),(299,1,602,100.000,'2025-12-28 19:12:49','2025-12-28 19:12:49'),(300,1,603,1000.000,'2025-12-28 19:13:22','2025-12-28 19:13:22'),(301,1,604,99.000,'2025-12-28 19:23:27','2025-12-28 19:24:32'),(302,1,605,100.000,'2026-01-03 20:28:43','2026-01-03 20:28:43');
/*!40000 ALTER TABLE `stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `receipt_footer` varchar(255) DEFAULT NULL,
  `receipt_policy_text` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stores`
--

LOCK TABLES `stores` WRITE;
/*!40000 ALTER TABLE `stores` DISABLE KEYS */;
INSERT INTO `stores` VALUES (1,'Makmur Jaya','081234567890','toko@example.com','Alamat toko','Terima kasih sudah berbelanja','Barang yang sudah dibeli mengikuti ketentuan retur toko.',NULL,'2026-08-02 08:39:53','2026-08-02 08:53:17');
/*!40000 ALTER TABLE `stores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'SUP-001','Supplier Demo','081111111111',NULL,NULL,NULL,1,'2026-08-02 08:39:53','2026-08-02 08:39:53',NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'Pieces','pcs','2026-08-02 08:39:53','2026-08-03 21:46:13'),(2,'Pack','pack','2026-08-02 08:39:53','2026-08-02 08:39:53'),(3,'Dus','dus','2026-08-02 08:39:53','2026-08-02 08:39:53');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `receipt_alignment` varchar(10) NOT NULL DEFAULT 'center',
  `receipt_paper_size` tinyint(3) unsigned NOT NULL DEFAULT 58,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Owner Demo','owner@example.com','2026-08-02 08:39:52','$2y$12$ZQt30jxSdU7BBBRrqvAviuWag8gVVYoE8A6f75jIY3UqCPk6Ax1ni',NULL,NULL,NULL,1,'center',58,'2026-10-11 04:23:30','2026-08-02 08:39:52','vPy33lVNlptoRL4YBfwhK8i38oqx0SRSriqF46AUtd7A1qGMBJkWRM4ZsZjG','2026-08-02 08:39:52','2026-10-11 04:23:30'),(2,'Admin Demo','admin@example.com','2026-08-02 08:39:52','$2y$12$8RCAGacrpYVBFrPA9yLk/OVZ2EwWjO5wvtRdmo3iOLfWQb4DWjx56',NULL,NULL,NULL,1,'center',58,NULL,'2026-08-02 08:39:52',NULL,'2026-08-02 08:39:52','2026-08-02 08:39:52'),(3,'Gudang Demo','gudang@example.com','2026-08-02 08:39:52','$2y$12$OF3xrFL2j44OcdBeIH5Zx.3z8F7TifLqPcDbzYMd87kI9EqAAVRby',NULL,NULL,NULL,1,'center',58,NULL,'2026-08-02 08:39:52',NULL,'2026-08-02 08:39:52','2026-08-02 08:39:52'),(4,'Kasir Demo','kasir@example.com','2026-08-02 08:39:53','$2y$12$aJZOh4jrTQ3vmyu5snEtie3iRpbp.8dawqETEhTfDN8CX42S5IQae',NULL,NULL,NULL,1,'center',58,'2026-08-04 16:42:16','2026-08-02 08:39:53',NULL,'2026-08-02 08:39:53','2026-08-04 16:42:16');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint(20) unsigned NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`),
  KEY `warehouses_store_id_foreign` (`store_id`),
  CONSTRAINT `warehouses_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,1,'GDG-UTAMA','Gudang Utama','Alamat toko',1,'2026-08-02 08:39:53','2026-08-02 08:39:53');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-11 14:30:04
