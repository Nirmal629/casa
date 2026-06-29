CREATE TABLE `ca_master_config` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `GROUP_ID` varchar(50) NOT NULL COMMENT 'e.g., LOCATION',
  `CATEGORY_ID` varchar(50) NOT NULL COMMENT 'e.g., COUNTRY, PROVINCE, CITY',
  `PARENT_ID` int(11) DEFAULT NULL COMMENT 'Self-referencing ID to enforce hierarchy (e.g. Ontario belongs to Canada)',
  `VALUE` varchar(255) NOT NULL COMMENT 'The actual configuration value',
  `STATUS` enum('Active','Inactive') DEFAULT 'Active',
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`PARENT_ID`) REFERENCES `ca_master_config`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Example Seed Data for Canada -> Ontario -> GTA
INSERT INTO `ca_master_config` (`GROUP_ID`, `CATEGORY_ID`, `PARENT_ID`, `VALUE`, `STATUS`) VALUES 
('LOCATION', 'COUNTRY', NULL, 'Canada', 'Active');

-- Assuming the above insert gets ID = 1
SET @Canada_ID = LAST_INSERT_ID();

INSERT INTO `ca_master_config` (`GROUP_ID`, `CATEGORY_ID`, `PARENT_ID`, `VALUE`, `STATUS`) VALUES 
('LOCATION', 'PROVINCE', @Canada_ID, 'Ontario', 'Active');

-- Assuming the above insert gets ID = 2
SET @Ontario_ID = LAST_INSERT_ID();

INSERT INTO `ca_master_config` (`GROUP_ID`, `CATEGORY_ID`, `PARENT_ID`, `VALUE`, `STATUS`) VALUES 
('LOCATION', 'CITY', @Ontario_ID, 'GTA', 'Active');
