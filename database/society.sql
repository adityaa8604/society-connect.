-- ============================================================
-- SOCIETY CONNECT - Complete Database Schema
-- Version: 2.0 | Author: Society Connect
-- Run this file in phpMyAdmin BEFORE starting the app
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `society_connect`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `society_connect`;

-- ============================================================
-- TABLE: users (Central authentication table)
-- ============================================================
CREATE TABLE `users` (
  `id`          INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `email`       VARCHAR(150) NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `role`        ENUM('admin','resident','staff') NOT NULL DEFAULT 'resident',
  `phone`       VARCHAR(20)  DEFAULT NULL,
  `avatar`      VARCHAR(255) DEFAULT NULL,
  `status`      ENUM('active','inactive') DEFAULT 'active',
  `last_login`  DATETIME     DEFAULT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: flats
-- ============================================================
CREATE TABLE `flats` (
  `id`          INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `flat_no`     VARCHAR(20)  NOT NULL,
  `block`       VARCHAR(10)  NOT NULL,
  `floor`       TINYINT      NOT NULL DEFAULT 0,
  `type`        ENUM('1BHK','2BHK','3BHK','4BHK','Penthouse') DEFAULT '2BHK',
  `area_sqft`   INT          DEFAULT 0,
  `status`      ENUM('occupied','vacant','under_maintenance') DEFAULT 'vacant',
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: residents
-- ============================================================
CREATE TABLE `residents` (
  `id`            INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT          UNSIGNED NOT NULL,
  `flat_id`       INT          UNSIGNED NOT NULL,
  `resident_type` ENUM('owner','tenant') DEFAULT 'owner',
  `members_count` TINYINT      DEFAULT 1,
  `vehicles`      VARCHAR(200) DEFAULT NULL,
  `move_in_date`  DATE         DEFAULT NULL,
  `emergency_contact` VARCHAR(20) DEFAULT NULL,
  `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`flat_id`) REFERENCES `flats`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: staff
-- ============================================================
CREATE TABLE `staff` (
  `id`          INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT          UNSIGNED NOT NULL,
  `designation` VARCHAR(100) NOT NULL,
  `department`  VARCHAR(100) DEFAULT NULL,
  `salary`      DECIMAL(10,2) DEFAULT 0.00,
  `join_date`   DATE         DEFAULT NULL,
  `shift`       ENUM('morning','evening','night','full_day') DEFAULT 'full_day',
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: vendors
-- ============================================================
CREATE TABLE `vendors` (
  `id`           INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(150) NOT NULL,
  `service_type` VARCHAR(100) NOT NULL,
  `contact_name` VARCHAR(100) DEFAULT NULL,
  `phone`        VARCHAR(20)  NOT NULL,
  `email`        VARCHAR(150) DEFAULT NULL,
  `address`      TEXT         DEFAULT NULL,
  `contract_start` DATE       DEFAULT NULL,
  `contract_end`   DATE       DEFAULT NULL,
  `status`       ENUM('active','inactive') DEFAULT 'active',
  `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: billing
-- ============================================================
CREATE TABLE `billing` (
  `id`          INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `flat_id`     INT          UNSIGNED NOT NULL,
  `resident_id` INT          UNSIGNED DEFAULT NULL,
  `bill_type`   VARCHAR(100) DEFAULT 'Maintenance',
  `month`       TINYINT      NOT NULL,
  `year`        SMALLINT     NOT NULL,
  `amount`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `penalty`     DECIMAL(10,2) DEFAULT 0.00,
  `due_date`    DATE         DEFAULT NULL,
  `paid_date`   DATE         DEFAULT NULL,
  `status`      ENUM('pending','paid','overdue','waived') DEFAULT 'pending',
  `payment_mode` VARCHAR(50) DEFAULT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `notes`       TEXT         DEFAULT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`flat_id`) REFERENCES `flats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resident_id`) REFERENCES `residents`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: complaints
-- ============================================================
CREATE TABLE `complaints` (
  `id`           INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `resident_id`  INT          UNSIGNED NOT NULL,
  `flat_id`      INT          UNSIGNED NOT NULL,
  `title`        VARCHAR(200) NOT NULL,
  `description`  TEXT         NOT NULL,
  `category`     ENUM('electrical','plumbing','civil','housekeeping','security','lift','common_area','other') DEFAULT 'other',
  `priority`     ENUM('low','medium','high','critical') DEFAULT 'medium',
  `status`       ENUM('open','assigned','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to`  INT          UNSIGNED DEFAULT NULL,
  `resolved_at`  DATETIME     DEFAULT NULL,
  `remarks`      TEXT         DEFAULT NULL,
  `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`resident_id`) REFERENCES `residents`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`flat_id`)     REFERENCES `flats`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `staff`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: visitors
-- ============================================================
CREATE TABLE `visitors` (
  `id`           INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `visitor_name` VARCHAR(150) NOT NULL,
  `phone`        VARCHAR(20)  DEFAULT NULL,
  `purpose`      VARCHAR(200) DEFAULT NULL,
  `id_proof`     VARCHAR(50)  DEFAULT NULL,
  `flat_id`      INT          UNSIGNED NOT NULL,
  `resident_id`  INT          UNSIGNED DEFAULT NULL,
  `vehicle_no`   VARCHAR(30)  DEFAULT NULL,
  `entry_time`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `exit_time`    DATETIME     DEFAULT NULL,
  `status`       ENUM('inside','exited','pre_approved') DEFAULT 'inside',
  `logged_by`    INT          UNSIGNED DEFAULT NULL,
  FOREIGN KEY (`flat_id`)     REFERENCES `flats`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`resident_id`) REFERENCES `residents`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`logged_by`)   REFERENCES `users`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: facilities
-- ============================================================
CREATE TABLE `facilities` (
  `id`           INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(150) NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `capacity`     INT          DEFAULT 0,
  `charges`      DECIMAL(10,2) DEFAULT 0.00,
  `open_time`    TIME         DEFAULT '06:00:00',
  `close_time`   TIME         DEFAULT '22:00:00',
  `status`       ENUM('available','maintenance','closed') DEFAULT 'available',
  `image_url`    VARCHAR(500) DEFAULT NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: bookings
-- ============================================================
CREATE TABLE `bookings` (
  `id`           INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `facility_id`  INT          UNSIGNED NOT NULL,
  `resident_id`  INT          UNSIGNED NOT NULL,
  `flat_id`      INT          UNSIGNED NOT NULL,
  `booking_date` DATE         NOT NULL,
  `start_time`   TIME         NOT NULL,
  `end_time`     TIME         NOT NULL,
  `guests_count` INT          DEFAULT 0,
  `purpose`      VARCHAR(200) DEFAULT NULL,
  `status`       ENUM('pending','approved','rejected','cancelled','completed') DEFAULT 'pending',
  `amount`       DECIMAL(10,2) DEFAULT 0.00,
  `notes`        TEXT         DEFAULT NULL,
  `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`facility_id`)  REFERENCES `facilities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resident_id`)  REFERENCES `residents`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`flat_id`)      REFERENCES `flats`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: notices
-- ============================================================
CREATE TABLE `notices` (
  `id`          INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(200) NOT NULL,
  `content`     TEXT         NOT NULL,
  `category`    ENUM('general','urgent','maintenance','event','finance','security') DEFAULT 'general',
  `posted_by`   INT          UNSIGNED NOT NULL,
  `file_name`   VARCHAR(255) DEFAULT NULL,
  `is_pinned`   TINYINT(1)   DEFAULT 0,
  `is_active`   TINYINT(1)   DEFAULT 1,
  `views`       INT          DEFAULT 0,
  `expiry_date` DATE         DEFAULT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: activity_log
-- ============================================================
CREATE TABLE `activity_log` (
  `id`         INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT          UNSIGNED DEFAULT NULL,
  `action`     VARCHAR(200) NOT NULL,
  `module`     VARCHAR(100) DEFAULT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- All passwords = "password123" (bcrypt hashed)
-- ============================================================

-- Users (Admin, Residents, Staff)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`, `status`) VALUES
('Super Admin',    'admin@society.com',   '$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'admin',    '9800000001', 'active'),
('Rajesh Kumar',   'rajesh@society.com',  '$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'resident', '9811111101', 'active'),
('Priya Sharma',   'priya@society.com',   '$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'resident', '9811111102', 'active'),
('Amit Singh',     'amit@society.com',    '$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'resident', '9811111103', 'active'),
('Neha Patel',     'neha@society.com',    '$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'resident', '9811111104', 'active'),
('Suresh Verma',   'suresh@society.com',  '$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'resident', '9811111105', 'active'),
('Ravi Maintenance','ravi@society.com',   '$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'staff',    '9822222201', 'active'),
('Anita Security',  'anita@society.com',  '$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'staff',    '9822222202', 'active'),
('Mohan Housekeeping','mohan@society.com','$2y$10$TKh8H1.PfunDi1.4OgboDuNEGbEnhk3uUi3I.lBelREy.6MilIHm2', 'staff',    '9822222203', 'active');

-- Flats
INSERT INTO `flats` (`flat_no`,`block`,`floor`,`type`,`area_sqft`,`status`) VALUES
('A-101','A',1,'2BHK',1100,'occupied'),
('A-102','A',1,'3BHK',1500,'occupied'),
('A-201','A',2,'2BHK',1100,'occupied'),
('A-202','A',2,'1BHK',750,'occupied'),
('A-301','A',3,'3BHK',1500,'occupied'),
('B-101','B',1,'2BHK',1200,'vacant'),
('B-102','B',1,'4BHK',2000,'occupied'),
('B-201','B',2,'2BHK',1200,'under_maintenance'),
('C-101','C',1,'Penthouse',3500,'vacant'),
('C-201','C',2,'3BHK',1600,'occupied');

-- Residents
INSERT INTO `residents` (`user_id`,`flat_id`,`resident_type`,`members_count`,`vehicles`,`move_in_date`,`emergency_contact`) VALUES
(2, 1,'owner', 3,'MH-01-AB-1234','2021-06-15','9800001111'),
(3, 2,'tenant',2,'MH-01-CD-5678','2022-03-01','9800002222'),
(4, 3,'owner', 4,'MH-01-EF-9012','2020-11-20','9800003333'),
(5, 4,'tenant',1, NULL,           '2023-01-10','9800004444'),
(6, 7,'owner', 5,'MH-01-GH-3456','2019-08-05','9800005555');

-- Staff
INSERT INTO `staff` (`user_id`,`designation`,`department`,`salary`,`join_date`,`shift`) VALUES
(7,'Maintenance Supervisor','Maintenance',28000.00,'2020-01-15','full_day'),
(8,'Security Guard',        'Security',   22000.00,'2021-03-01','evening'),
(9,'Housekeeping Staff',    'Housekeeping',18000.00,'2022-06-01','morning');

-- Vendors
INSERT INTO `vendors` (`name`,`service_type`,`contact_name`,`phone`,`email`,`status`,`contract_start`,`contract_end`) VALUES
('GreenClean Services','Housekeeping',     'Ramesh Joshi', '9900001111','green@clean.com','active','2024-01-01','2024-12-31'),
('SecureGuard Pvt Ltd','Security',         'Vikram Singh', '9900002222','secure@guard.com','active','2024-01-01','2024-12-31'),
('SparkElectric',      'Electrical Repair','Sunil Kumar',  '9900003333','spark@elec.com','active','2024-01-01','2024-12-31'),
('AquaPlumb',          'Plumbing',         'Dinesh Rao',   '9900004444','aqua@plumb.com','active','2024-04-01','2025-03-31'),
('LiftTech Solutions', 'Lift Maintenance', 'Arun Mehta',   '9900005555','lift@tech.com','inactive','2023-01-01','2023-12-31');

-- Billing (current + past months)
INSERT INTO `billing` (`flat_id`,`resident_id`,`bill_type`,`month`,`year`,`amount`,`penalty`,`due_date`,`paid_date`,`status`,`payment_mode`) VALUES
(1,1,'Maintenance',3,2026,3500.00,0.00,'2026-03-10','2026-03-07','paid','Online'),
(2,2,'Maintenance',3,2026,4500.00,0.00,'2026-03-10',NULL,'pending',NULL),
(3,3,'Maintenance',3,2026,3500.00,200.00,'2026-03-10',NULL,'overdue',NULL),
(4,4,'Maintenance',3,2026,2500.00,0.00,'2026-03-10','2026-03-05','paid','Cash'),
(7,5,'Maintenance',3,2026,6000.00,0.00,'2026-03-10',NULL,'pending',NULL),
(1,1,'Maintenance',2,2026,3500.00,0.00,'2026-02-10','2026-02-08','paid','Online'),
(2,2,'Maintenance',2,2026,4500.00,500.00,'2026-02-10','2026-02-18','paid','Online'),
(3,3,'Maintenance',2,2026,3500.00,0.00,'2026-02-10','2026-02-06','paid','Cash'),
(1,1,'Water',      3,2026,500.00, 0.00,'2026-03-10','2026-03-07','paid','Online'),
(2,2,'Water',      3,2026,650.00, 0.00,'2026-03-10',NULL,'pending',NULL),
(3,3,'Parking',    3,2026,1000.00,0.00,'2026-03-10',NULL,'overdue',NULL);

-- Complaints
INSERT INTO `complaints` (`resident_id`,`flat_id`,`title`,`description`,`category`,`priority`,`status`,`assigned_to`,`resolved_at`,`remarks`) VALUES
(1,1,'Water leakage in bathroom ceiling','There is constant water dripping from bathroom ceiling. It has been 3 days.','plumbing','high','in_progress',1,NULL,'Plumber scheduled for tomorrow'),
(2,2,'Lift not working in Block A','The lift has been out of order since 2 days. Very inconvenient for elderly residents.','lift','critical','assigned',1,NULL,'Waiting for technician'),
(3,3,'Street light flickering near gate','The street light near main gate keeps flickering and sometimes completely off at night.','electrical','medium','open',NULL,NULL,NULL),
(4,4,'Garbage not collected yesterday','The garbage collection was skipped yesterday. Foul smell in corridor.','housekeeping','low','resolved',3,'2026-03-20 10:00:00','Collected. Will ensure daily schedule is followed.'),
(5,7,'Parking area not cleaned','Parking area has oil spills and debris. Risk of accidents.','common_area','medium','open',NULL,NULL,NULL),
(1,1,'AC remote not working in gym','Gym air conditioner remote is faulty. Temperature uncontrollable.','electrical','low','resolved',1,'2026-03-18 15:30:00','Remote replaced with new one.');

-- Visitors
INSERT INTO `visitors` (`visitor_name`,`phone`,`purpose`,`flat_id`,`resident_id`,`entry_time`,`exit_time`,`status`,`logged_by`) VALUES
('Ramesh (Delivery)',    '9700001111','Package delivery',   1,1,DATE_SUB(NOW(),INTERVAL 2 HOUR),DATE_SUB(NOW(),INTERVAL 1 HOUR),'exited',8),
('Electrician - Suresh', '9700002222','Electrical repair',  2,2,DATE_SUB(NOW(),INTERVAL 1 HOUR),NULL,'inside',8),
('Mrs. Asha Kumar (Family)','9700003333','Personal visit',  3,3,DATE_SUB(NOW(),INTERVAL 3 HOUR),DATE_SUB(NOW(),INTERVAL 30 MINUTE),'exited',8),
('Plumber Dinesh',       '9700004444','Plumbing repair',    7,5,NOW(),NULL,'inside',8),
('Amazon Delivery Boy',  '9700005555','Courier delivery',   4,4,DATE_SUB(NOW(),INTERVAL 4 HOUR),DATE_SUB(NOW(),INTERVAL 3 HOUR),'exited',8);

-- Facilities
INSERT INTO `facilities` (`name`,`description`,`capacity`,`charges`,`open_time`,`close_time`,`status`,`image_url`) VALUES
('Swimming Pool',   'Olympic-size swimming pool with changing rooms and lifeguard on duty',50,500.00,'06:00:00','21:00:00','available','https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?w=400'),
('Clubhouse',       'Air-conditioned clubhouse for parties and gatherings with catering facility',200,5000.00,'08:00:00','23:00:00','available','https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?w=400'),
('Gym / Fitness',   'Fully equipped gym with cardio and weight training equipment',30,0.00,'05:00:00','23:00:00','available','https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400'),
('Badminton Court', 'Indoor badminton court with lighting',10,200.00,'06:00:00','22:00:00','available','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=400'),
('Conference Room', 'Air-conditioned conference room with projector and whiteboard',20,1000.00,'09:00:00','20:00:00','available','https://images.unsplash.com/photo-1497366216548-37526070297c?w=400'),
('Terrace Garden',  'Open terrace with garden seating for small gatherings',40,300.00,'06:00:00','22:00:00','maintenance','https://images.unsplash.com/photo-1586778578596-5cd9f54ca5a4?w=400');

-- Bookings
INSERT INTO `bookings` (`facility_id`,`resident_id`,`flat_id`,`booking_date`,`start_time`,`end_time`,`guests_count`,`purpose`,`status`,`amount`) VALUES
(2,1,1,'2026-03-30','18:00:00','22:00:00',50,'Birthday Party','approved',5000.00),
(1,2,2,'2026-03-28','07:00:00','08:00:00',0,'Morning swim','approved',500.00),
(4,3,3,'2026-03-27','17:00:00','18:00:00',3,'Badminton match','approved',200.00),
(5,5,7,'2026-03-29','10:00:00','12:00:00',10,'Society meeting','pending',1000.00),
(2,4,4,'2026-04-05','18:00:00','21:00:00',30,'Anniversary dinner','pending',5000.00);

-- Notices
INSERT INTO `notices` (`title`,`content`,`category`,`posted_by`,`is_pinned`,`is_active`,`views`,`expiry_date`) VALUES
('Society Annual General Meeting - March 2026','All residents are requested to attend the Annual General Meeting on 30th March 2026 at 6:00 PM in the Clubhouse. Agenda: Budget review, committee election, maintenance updates. Attendance is mandatory for flat owners.','event',1,1,1,47,'2026-03-31'),
('Water Supply Interruption Notice','Due to main pipeline maintenance by the municipality, water supply will be interrupted on 28th March 2026 from 10 AM to 4 PM. Please store water accordingly. Tanker water will be arranged during the interruption.','urgent',1,1,1,89,'2026-03-29'),
('Maintenance Charges Revised - April 2026','The managing committee has revised monthly maintenance charges effective from April 2026. 1BHK: ₹2,500 | 2BHK: ₹3,500 | 3BHK: ₹4,500 | 4BHK: ₹6,000. This revision is to cover increased electricity and housekeeping costs.','finance',1,0,1,34,'2026-04-30'),
('Parking Rules Reminder','All vehicles must be parked in designated spots only. No parking in fire lanes, visitor slots, or blocking other vehicles. Violating vehicles will be towed at owner''s expense. Please display parking sticker on windshield.','security',1,0,1,28,'2026-06-30'),
('Holi Celebration - 14th March','Join us for a joyful Holi celebration on 14th March at the society garden from 9 AM to 12 PM. Colors, sweets, and music! Please dress in old clothes. Children are welcome. Organized by the Residents'' Welfare Committee.','event',1,0,1,92,'2026-03-14'),
('Lift Maintenance Notice - Block A','The lift in Block A will be under maintenance on 27th March from 9 AM to 1 PM. Residents are requested to use stairs during this period. Sorry for the inconvenience.','maintenance',1,1,1,55,'2026-03-27');

-- Activity Log
INSERT INTO `activity_log` (`user_id`,`action`,`module`) VALUES
(1,'Admin logged in','auth'),
(2,'Complaint submitted: Water leakage in bathroom','complaints'),
(3,'Booking created: Clubhouse for Birthday Party','bookings'),
(1,'Notice posted: Water Supply Interruption','notices'),
(8,'Visitor entry logged: Ramesh Delivery','visitors');
