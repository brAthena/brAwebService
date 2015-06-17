DROP DATABASE IF EXISTS `brawebservice`;
CREATE DATABASE `brawebservice`;
USE `brawebservice`;

DROP TABLE IF EXISTS `apikeys`;
CREATE TABLE `apikeys` (
    `ApiKeyID` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `ApiKey` VARCHAR(200) NOT NULL,
    `ApiKeyEnabled` BOOLEAN NOT NULL DEFAULT TRUE,
    `ApiKeyDtCreated` DATETIME NOT NULL,
    `ApiKeyExpires` DATETIME NULL DEFAULT NULL,
    `ApiKeyUsedCount` INTEGER NOT NULL DEFAULT 0,
    `ApiKeyUsedLimit` INTEGER NOT NULL DEFAULT 100000,
    `ApiKeyUsedDay` INTEGER NOT NULL DEFAULT 0,
    `ApiKeyUsedDayLimit` INTEGER NOT NULL DEFAULT 5000,
    `ApiKeyDtCanceled` DATETIME NULL DEFAULT NULL,
    `ApiKeyPrivateKey` TEXT NOT NULL,
    `ApiKeyPassword` VARCHAR(50) NOT NULL DEFAULT '',
    `ApiKeyX509` TEXT NOT NULL,
    `ApiPermission` CHAR(20) NOT NULL DEFAULT '00000000000000000000',
    UNIQUE INDEX (`ApiKey`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `apikeys_day`;
CREATE TABLE `apikeys_day` (
    `ApiKeyID` INTEGER NOT NULL,
    `ApiKeyDay` DATE NOT NULL,
    `UsedCount` INTEGER NOT NULL,
    PRIMARY KEY (`ApiKeyID`, `ApiKeyDay`)
) ENGINE=MyISAM;
