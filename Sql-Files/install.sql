
-- Cria a tabela de chaves para a api.
CREATE TABLE brawbkeys (
    KeyID INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    ApiKey VARCHAR (32) UNIQUE NOT NULL,
	ApiPassMethod VARCHAR(20) NOT NULL,
	ApiKeyCreated INTEGER DEFAULT (0) NOT NULL,
    -- -------------------------------------------------------------------------------------------------------------- --
    -- Será composto de 10 caracteres e estará no formato : 00000000000000000000.
    -- Cada caractere será uma permissão que o APIKEY poderá realizar, onde 1 permite e 0 bloqueia.
    -- -------------------------------------------------------------------------------------------------------------- --
    -- 1º: Permite criar conta.
    -- 2º: Permite realizar login.
    -- 3º: Permite alterações de senha. (Depende do 2º)
    -- 4º: Permite alterações de email. (Depende do 2º)
    -- 5º: Permite alterações de sexo. (Depende do 2º)
    -- 6º: Permite listagem de personagens. (Depende do 2º)
    -- 7º: Permite reset de posição. (Depende do 2º)
    -- 8º: Permite reset de aparência. (Depende do 2º)
    -- 9º: Permite criar novas chaves api.
    -- -------------------------------------------------------------------------------------------------------------- --
    -- OBS.: Permissões do 10º ao 20º livres para implementações customizadas.
    -- -------------------------------------------------------------------------------------------------------------- --
    ApiPermission CHAR(20) NOT NULL,
    ApiAllowed BOOLEAN NOT NULL DEFAULT true,
    ApiExpires DATE,
    ApiUsedCount INTEGER DEFAULT (0) NOT NULL,
    ApiLimitCount INTEGER DEFAULT (5000) NOT NULL,
    ApiUnlimitedCount BOOLEAN NOT NULL DEFAULT false
);
