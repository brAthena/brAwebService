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
    -- --------------------------------------------------------------------------------------------------------------- --
    --  ******* É dividido em formato de 20 caracteres.
    --  ******* Cada caractere representa uma permissão, ou seja, 20 tipos de permissões diferentes são permitidas.
    --  ******* Cada permissão possui status: desligado ou ligado que são representadas por 0 ou 1, respectivamente.
    --
    -- Tabela de permissões e descrição para cada permissão. 
    --
    -- + --------- + ----------------------------------------------------------------------------------------- +
    -- | Caractere | Descrição                                                                                 |
    -- + --------- + ----------------------------------------------------------------------------------------- +
    -- |         1 | ApiKey possui permissão de acesso ao webservice.                                          |
    -- |         2 | Informa que é uma Apikey válida para requisições. Deve ser enviada via GET.               |
    -- |         3 | 
    -- |         4 | 
    -- |         5 | 
    -- |         6 | 
    -- |         7 | 
    -- |         8 | 
    -- |         9 | 
    -- |        10 | 
    -- |        11 | 
    -- |        12 | 
    -- |        13 | 
    -- |        14 | 
    -- |        15 | 
    -- |        16 | 
    -- |        17 | 
    -- |        18 | 
    -- |        19 | Permite o bloequar de Apikeys.                                                            |
    -- |        20 | Permite a crição de novas Apikeys.                                                        |
    -- + --------- + ----------------------------------------------------------------------------------------- +
    -- --------------------------------------------------------------------------------------------------------------- --
    `ApiPermission` CHAR(20) NOT NULL DEFAULT '00000000000000000000',
    UNIQUE INDEX (`ApiKey`)
) ENGINE=INNODB;

DROP TABLE IF EXISTS `apikeys_day`;
CREATE TABLE `apikeys_day` (
    `ApiKeyID` INTEGER NOT NULL,
    `ApiKeyDay` DATE NOT NULL,
    `UsedCount` INTEGER NOT NULL,
    PRIMARY KEY (`ApiKeyID`, `ApiKeyDay`),
    CONSTRAINT FOREIGN KEY (`ApiKeyID`) REFERENCES `apikeys` (`ApiKeyID`) ON DELETE RESTRICT
) ENGINE=INNODB;

DROP TABLE IF EXISTS `application`;
CREATE TABLE `application` (
    `ApplicationID` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `Application` VARCHAR(50) NOT NULL,
    `ApplicationKey` VARCHAR(32) NOT NULL,
    `ApplicationDtCreated` DATE NOT NULL,
    `ApplicationAllowFromAll` BOOLEAN NOT NULL DEFAULT TRUE,
    `ApplicationBlocked` BOOLEAN NOT NULL DEFAULT FALSE,
    `ApplicationDtBlocked` DATE NULL DEFAULT NULL,
    `ApiKeyID` INTEGER NOT NULL,
    UNIQUE INDEX (`ApplicationKey`),
    CONSTRAINT FOREIGN KEY (`ApiKeyID`) REFERENCES `apikeys`(`ApiKeyID`) ON DELETE RESTRICT
) ENGINE=INNODB;

DROP TABLE IF EXISTS `application_allowed_address`;
CREATE TABLE `application_allowed_address` (
    `ApplicationID` INTEGER NOT NULL,
    `AddressID` INTEGER NOT NULL AUTO_INCREMENT,
    `Address` VARCHAR(50) NOT NULL DEFAULT '',
    `AddressDtAllowed` DATE NOT NULL,
    CONSTRAINT FOREIGN KEY (`ApplicationID`) REFERENCES `application` (`ApplicationID`) ON DELETE RESTRICT,
    PRIMARY KEY (`AddressID`, `ApplicationID`)
) ENGINE=INNODB;

DROP TABLE IF EXISTS `server_status`;
CREATE TABLE `server_status` (
    `StatusID` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `loginAddress` VARCHAR(50) NOT NULL DEFAULT 'localhost',
    `loginPort`  SMALLINT(5) UNSIGNED NOT NULL DEFAULT 6900,
    `loginStatus` BOOLEAN NOT NULL DEFAULT false,
    `charAddress` VARCHAR(50) NOT NULL DEFAULT 'localhost',
    `charPort` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 6121,
    `charStatus` BOOLEAN NOT NULL DEFAULT false,
    `mapAddress` VARCHAR(50) NOT NULL DEFAULT 'localhost',
    `mapPort` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 5121,
    `mapStatus` BOOLEAN NOT NULL DEFAULT false,
    `lastCheck` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `nextCheck` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    INDEX (`nextCheck`)
) ENGINE=MyISAM;
