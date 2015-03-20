
-- Cria a tabela de chaves para a api.
CREATE TABLE brawbkeys (
    KeyID INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    ApiKey VARCHAR (32) UNIQUE NOT NULL,
    ApiAllowed BOOLEAN NOT NULL DEFAULT true,
    ApiExpires DATE,
    ApiUsedCount INTEGER DEFAULT (0) NOT NULL,
    ApiLimitCount INTEGER DEFAULT (5000) NOT NULL,
    ApiUnlimitedCount BOOLEAN NOT NULL DEFAULT false,
    ApiCryptMethod VARCHAR(20) NULL DEFAULT NULL,
    ApiCryptPassword VARCHAR(32) NULL DEFAULT NULL,
    ApiCryptIV VARCHAR(32) NULL DEFAULT NULL
);
