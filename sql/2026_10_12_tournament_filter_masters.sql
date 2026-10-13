-- Master records for the Tournament Management filters.

CREATE TABLE IF NOT EXISTS `ca_country_master` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `NAME` VARCHAR(100) NOT NULL,
  `STATUS` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_country_name` (`NAME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ca_province_master` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `COUNTRY_ID` INT UNSIGNED NOT NULL,
  `NAME` VARCHAR(100) NOT NULL,
  `STATUS` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_province_country_name` (`COUNTRY_ID`, `NAME`),
  CONSTRAINT `fk_province_master_country` FOREIGN KEY (`COUNTRY_ID`) REFERENCES `ca_country_master` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ca_city_master` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `PROVINCE_ID` INT UNSIGNED NOT NULL,
  `NAME` VARCHAR(100) NOT NULL,
  `STATUS` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_city_province_name` (`PROVINCE_ID`, `NAME`),
  CONSTRAINT `fk_city_master_province` FOREIGN KEY (`PROVINCE_ID`) REFERENCES `ca_province_master` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ca_tournament_category_master` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `GENDER_CATEGORY` VARCHAR(100) NOT NULL,
  `EVENT_TYPE` VARCHAR(100) NOT NULL,
  `EVENT_CATEGORY` VARCHAR(100) NOT NULL,
  `DISPLAY_NAME` VARCHAR(255) NOT NULL,
  `STATUS` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_tournament_category_values` (`GENDER_CATEGORY`, `EVENT_TYPE`, `EVENT_CATEGORY`),
  UNIQUE KEY `uq_tournament_category_display` (`DISPLAY_NAME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `ca_country_master` (`NAME`) VALUES ('Canada');

INSERT IGNORE INTO `ca_province_master` (`COUNTRY_ID`, `NAME`)
SELECT `ID`, 'Ontario' FROM `ca_country_master` WHERE `NAME` = 'Canada';

INSERT IGNORE INTO `ca_city_master` (`PROVINCE_ID`, `NAME`)
SELECT p.`ID`, 'GTA'
FROM `ca_province_master` p
INNER JOIN `ca_country_master` c ON c.`ID` = p.`COUNTRY_ID`
WHERE c.`NAME` = 'Canada' AND p.`NAME` = 'Ontario';

INSERT IGNORE INTO `ca_tournament_category_master`
(`GENDER_CATEGORY`, `EVENT_TYPE`, `EVENT_CATEGORY`, `DISPLAY_NAME`)
VALUES ('Male', 'Doubles', 'Open', 'Male - Doubles - Open');
