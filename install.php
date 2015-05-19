<?php
/**
 * brAWebService
 * Copyright (c) brAthena, All rights reserved.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.
 */

// Caso o arquivo de configurações exista, então não permite que a instalação continue.
if(file_exists(dirname(__FILE__) . '/config.xml') === true)
    exit;

// Verifica se os dados foram recebidos pelo POST.
if(empty($_POST) === false)
{
    $xml = new SimpleXMLElement('<brAWebServerConfig></brAWebServerConfig>');
    $xml->addChild('maintence', 'yes');
    
    $pdoServer = $xml->addChild('PdoServerConnection');
    $pdoServer->addAttribute('user', $_POST['serviceUser']);
    $pdoServer->addAttribute('pass', $_POST['servicePass']);
    $pdoServer->addAttribute('connectionString', $_POST['serviceConnectionString']);

    $pdoRagna = $xml->addChild('PdoRagnaConnection');
    $pdoRagna->addAttribute('user', $_POST['ragnaUser']);
    $pdoRagna->addAttribute('pass', $_POST['ragnaPass']);
    $pdoRagna->addAttribute('connectionString', $_POST['ragnaConnectionString']);

    $xml->addChild('maxGroupId', $_POST['maxGroupId']);
    
    $acc = $xml->addChild('accountValidation');
    $acc->addChild('username', $_POST['username']);
    $acc->addChild('userpass', $_POST['userpass']);
    $acc->addChild('sex', $_POST['sex']);
    $acc->addChild('email', $_POST['email']);
    
    $ssl = $xml->addChild('openSslSettings');
    $ssl->addChild('password', $_POST['openSslPassword']);

    $write = file_put_contents(dirname(__FILE__).'/config.xml', $xml->asXML());
    
    if($write === false)
    {
        echo utf8_decode("OCORREU UM ERRO DURANTE A TENTATIVA DE ESCREVER AS CONFIGURAÇÕES.");
    }
    else
    {
        $pdo = new PDO($_POST['serviceConnectionString'], $_POST['serviceUser'], $_POST['servicePass']);
        $pdo->exec(file_get_contents(dirname(__FILE__).'/Sql-Files/install.sql'));

        $time = time();
        $apiKey = hash_hmac('md5', uniqid(), $_POST['openSslPassword'] . $time);
        $clientKey = hash_hmac('md5', $_POST['openSslPassword'], $time);
        
        $param = array(
            ':ApiKey' => $apiKey,
            ':ApiKeyCreated' => $time,
            ':ApiExpires' => '2050-12-31'
        );
        
        $stmt = $pdo->prepare('
            INSERT INTO
                brawbkeys
            (KeyID, ApiKey, ApiPassMethod, ApiKeyCreated, ApiPermission, ApiAllowed, ApiExpires, ApiUsedCount, ApiLimitCount, ApiUnlimitedCount)
                VALUES
            (NULL, :ApiKey, "aes-256-cbc", :ApiKeyCreated, "11111111111111111111", "true", :ApiExpires, 0, 2147483647, "true");');
        $stmt->execute($param);

        $pdo = null;
        echo utf8_decode("Configurações foram salvas com sucesso.<br><br>
            <strong>Por favor, salve essas configurações. Utilize os dados abaixo para gerar novas chaves de Api para outros utilizarem.</strong>
            <br><br>
            <strong>ApiKey Mestre:</strong> {$apiKey}<br>
            <strong>Chave de Criptografia:</strong> {$clientKey}<br>
            <strong>Cifra:</strong> aes-256-cbc<br>
        ");
    }
    exit;
}

// Escolhe um algoritmo para gerar a chave aleatóriamente.
$hash_algos = hash_algos();
shuffle($hash_algos);

$hash_algo = $hash_algos[ rand(0, sizeof($hash_algos) - 1) ];

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <style>
            input[type="text"]
            {
                font-family: Consolas, Courier, "Courier New";
            }
        </style>
    </head>
    <body>
        <form action="install.php" method="POST">
            <fieldset>
                <legend>Configuração PDO do serviço</legend>
                
                <label>
                    String de Conexão:<br>
                    <input name="serviceConnectionString" value="sqlite:brAWebService.db" size="100" type="text"/>
                </label><br>
                <label>
                    Usuário:<br>
                    <input name="serviceUser" value="" size="30" type="text"/>
                </label><br>
                <label>
                    Senha:<br>
                    <input name="servicePass" value="" size="30" type="text"/>
                </label><br>
            </fieldset>

            <fieldset>
                <legend>Configuração PDO do Jogo</legend>
                
                <label>
                    String de Conexão:<br>
                    <input name="ragnaConnectionString" value="mysql:host=localhost;dbname=ragnarok;" size="100" type="text"/>
                </label><br>
                <label>
                    Usuário:<br>
                    <input name="ragnaUser" value="ragnarok" size="30" type="text"/>
                </label><br>
                <label>
                    Senha:<br>
                    <input name="ragnaPass" value="ragnarok" size="30" type="text"/>
                </label><br>
            </fieldset>

            <fieldset>
                <legend>Criptografia</legend>
                
                <label>
                    Chave Privada:<br>
                    <input name="openSslPassword" value="<?php echo hash($hash_algo, microtime(true)); ?>" size="180" maxlength="180" type="text"/>
                </label><br>
            </fieldset>

            <fieldset>
                <legend>Validação de campos</legend>
                
                <label>
                    Nome de usuário:<br>
                    <input name="username" value="^([a-z0-9]{4,24})$" size="50" maxlength="50" type="text"/>
                </label><br>
                <label>
                    Senha de usuário:<br>
                    <input name="userpass" value="^([a-f0-9]{32})$" size="50" maxlength="50" type="text"/>
                </label><br>
                <label>
                    Sexo:<br>
                    <input name="sex" value="^(M|F)$" size="10" maxlength="10" type="text"/>
                </label><br>
                <label>
                    Email:<br>
                    <input name="email" value="^([^@]+)@([^\.]+)\..+$" size="50" maxlength="50" type="text"/>
                </label><br>
            </fieldset>
            
            <fieldset>
                <legend>Outros</legend>
                
                <label>
                    Nível máximo para login:<br>
                    <input name="maxGroupId" value="10" size="3" maxlength="3" type="text"/>
                </label><br>
            </fieldset>
            
            <input type="submit" value="Salvar"/>
            <input type="reset" value="Limpar"/>
        </form>
    </body>
</html>
