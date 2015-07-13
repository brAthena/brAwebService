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

namespace brAWebService;

use Slim;

/**
 * Classe para execução do Webserver local.
 * @final
 */
final class brAWebServer extends Slim\Slim
{
    /**
     * Gancho de conexão com o banco de dados do Service.
     * @var \PDO
     */
    public $pdoServer;

    /**
     * Gancho de conexão com o banco de dados do Ragnarok.
     * @var \PDO
     */
    public $pdoRagna;
    
    /**
     * Objeto de configuração para o service.
     * @var object
     */
    public $config;

    /**
     * Objeto da apikey.
     * @var object
     */
    public $apikey;

    /**
     * Informa se o usuário enviou uma chave válida para o servidor.
     * @var boolean
     */
    public $hasApiKey;
    
    /**
     * Permissões carregadas para a chave atual.
     * @var string
     */
    public $permissions;
    
    /**
     * @see \Slim\Slim::__construct()
     */
    public function __construct($bInstallMode = false, array $userSettings = array())
    {
        // Invoca o construtor do slim.
        parent::__construct($userSettings);

        if($bInstallMode === false)
        {
            // Adiciona o middleware para os conteudos.
            $this->add(new \Slim\Middleware\ContentTypes());
            $this->add(new brAWebConfigLoad());     // <- Executa as validações e atribuições para carregar permissões de acesso
                                                    //    e etc...
            $this->add(new brAWebConfigRoutes());
        }
    } /* fim - public function __construct(array $userSettings = array()) */

    /**
     * Método utilizado para definir as rotas que serão utilizadas pelo sistema.
     *
     * @param string $method Método para definir a rota. (GET, POST, PUT, DELETE)
     * @param string $route Método para a rota ser executada.
     * @param string $permission Permissões da APIKEY para execução do action e method.
     * @param callback $callback função a ser executada quando a rota for utilizada.
     *
     * @return void
     */
    public function defineRoute($method, $route, $permission, $callback)
    {
        $this->{$method}($route, function() use ($callback, $permission) {
            $app = brAWebServer::getInstance();
            
            if(($app->permissions&$permission) != $permission)
            {
                $app->halt(401, 'Acesso negado. Você não tem permissões para esta rota.');
            }
            
            $callback();
        });
    }
    
    /**
     * Método utilizado para carregar uma configuração do ini de configuração.
     * @return void
     */
    public function loadConfig()
    {
        // Carrega o arquivo de configuração.
        $this->config = json_decode(json_encode(parse_ini_file(dirname(__FILE__) . '/../config.ini', true)));
 
        // Faz o parse completo de algumas configurações especiais que precisem de tratamento para ser utilizadas.
        // As demais já foram tratadas acima.
        $this->config->Status->maintence = filter_var($this->config->Status->maintence, FILTER_VALIDATE_BOOLEAN);
        $this->config->SecureData->enabled = filter_var($this->config->SecureData->enabled, FILTER_VALIDATE_BOOLEAN);
        $this->config->SecureData->force = filter_var($this->config->SecureData->force, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Tenta realizar um login no acesso de usuário fornecido.
     *
     * @param string $userid Nome de usuário para verificação.
     * @param string $user_pass Senha para o nome de usuário.
     */
    public function login($userid, $user_pass)
    {
        // Verifica se a conta pode realizar login.
        // Caso não consiga:
        //   1º - Nome de usuário e senha incorretos.
        //   2º - Nivel de conta não pode acessar via service.
        //   3º - A Conta está bloqueada para acesso.
        if(($obj = $this->checkUserPass($userid, $user_pass)) === false)
        {
            $this->halt(404, 'Combinação de usuário e senha inválidos.');
        }
        else
        {
            return $obj;
        }
    }

    /**
     * Verifica se os dados informados podem realizar login.
     *
     * @param string $userid Nome de usuário para login.
     * @param string $user_pass Senha para realizar o login.
     *
     * @return mixed Retorna falso caso não possa ou um objeto com informações login.
     */
    private function checkUserPass($userid, $user_pass)
    {
        $ds_login = $this->pdoRagna->prepare('
            SELECT
                account_id,
                userid,
                sex,
                birthdate,
                logincount,
                unban_time
            FROM
                login
            WHERE
                userid = :userid AND
                user_pass = :user_pass AND
                group_id < :group_id AND
                state = 0
        ');
        $ds_login->execute([
            ':userid' => $userid,
            ':user_pass' => $user_pass,
            ':group_id' => $this->config->AccountLogin->maxLevel
        ]);

        return $ds_login->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica se um nome de usuário já existe no banco de dados.
     *
     * @param string $userid Nome de usuário.
     *
     * @return boolean Caso verdadeiro, o nome de usuário já está cadastrado.
     */
    private function checkUserId($userid)
    {
        $ds_login = $this->pdoRagna->prepare('
            SELECT
                COUNT(*) as qtdeAcc
            FROM
                login
            WHERE
                userid = :userid
        ');
        $ds_login->execute([
            ':userid' => $userid
        ]);
        $rs_login = $ds_login->fetchObject();

        return $rs_login->qtdeAcc > 0;
    }

    /**
     * Verifica se o servidor está online e retorna os dados.
     */
    public function checkServerStatus()
    {
        // Query para verificar se existem lançamentos no banco de dados
        //  do status do servidor. (Cache temporario de verificação, se não da problema de DDOS na porta do emulador)
        $ds_status = $this->pdoServer->query('
            SELECT
                loginStatus,
                charStatus,
                mapStatus,
                lastCheck,
                nextCheck,
                UNIX_TIMESTAMP(NOW()) as nowCheck
            FROM
                server_status
            WHERE
                nextCheck >= UNIX_TIMESTAMP(NOW())
        ');
        $rs_status = $ds_status->fetchObject();

        // Caso não encontre os dados para a consulta, então inicia o teste para as portas do servidor e ip.
        if($rs_status === false)
        {
            // Realiza a tentativa de abrir uma conexão com as portas do servidor.
            $login = @fsockopen($this->config->RagnarokServer->loginAddress, $this->config->RagnarokServer->loginPort);
            $char = @fsockopen($this->config->RagnarokServer->charAddress, $this->config->RagnarokServer->charPort);
            $map = @fsockopen($this->config->RagnarokServer->mapAddress, $this->config->RagnarokServer->mapPort);
            // Prepara a query para inserir os dados de status do servidor no banco de dados.
            $stmt_status = $this->pdoServer->prepare('
                INSERT INTO server_status
                VALUES (NULL,
                        :loginAddress, :loginPort, :loginStatus,
                        :charAddress, :charPort, :charStatus,
                        :mapAddress, :mapPort, :mapStatus,
                        UNIX_TIMESTAMP(NOW()),
                        UNIX_TIMESTAMP(NOW()) + :nextCheck )
            ');
            // Informa os dados que serão inseridos no banco de dados para futuras auditorias e funcionalidades.
            $stmt_status->execute([
                ':loginAddress' => $this->config->RagnarokServer->loginAddress,
                ':loginPort' => $this->config->RagnarokServer->loginPort,
                ':loginStatus' => (($login === false) ? 0:1),
                ':charAddress' => $this->config->RagnarokServer->charAddress,
                ':charPort' => $this->config->RagnarokServer->charPort,
                ':charStatus' => (($char === false) ? 0:1),
                ':mapAddress' => $this->config->RagnarokServer->mapAddress,
                ':mapPort' => $this->config->RagnarokServer->mapPort,
                ':mapStatus' => (($map === false) ? 0:1),
                ':nextCheck' => $this->config->RagnarokServer->checkInterval
            ]);
            // Caso tenha aberto os ponteiros de verificação, encerra os mesmos para não
            //  manter a conexão aberta com o server e sobre-carregar.
            if($login) fclose($login);
            if($char) fclose($char);
            if($map) fclose($map);

            // Refaz a query de consulta que agora terá os dados no banco.
            $this->checkServerStatus();
            return;
        }
        // Retorna as informações de status do servidor, com informações da próxima verificação
        //  e quando foi verificado.
        $this->halt(200, 'ok', [
            'map' => boolval($rs_status->mapStatus),
            'char' => boolval($rs_status->charStatus),
            'login' => boolval($rs_status->loginStatus),
            'lastCheck' => intval($rs_status->lastCheck),
            'nextCheck' => intval($rs_status->nextCheck),
            'now' => intval($rs_status->nowCheck)
        ]);
    }
    
    /**
     * Método utilizado para realizar uma verificação da apiKey do usuário e obter as permissões.
     *
     * @param string $apiKey Chave de api para uso.
     *
     * @return boolean Verdadeiro caso chave esteja ok.
     */
    public function checkApiKey($apiKey, $appKey)
    {
        $stmt = $this->pdoServer->prepare('
            SELECT
                apikeys.*
            FROM
                application
            left join
                application_allowed_address
                    on (application_allowed_address.ApplicationID = application.ApplicationID)
            inner join
                apikeys
                    on (apikeys.ApiKeyID = application.ApiKeyID)
            left join
                apikeys_day
                    on (apikeys_day.ApiKeyID = apikeys.ApiKeyID and
                            apikeys_day.ApiKeyDay = CURDATE())
            where
                application.ApplicationKey = :AppKey and
                apikeys.ApiKey = :ApiKey and
                apikeys.ApiKeyEnabled = true and
                (apikeys.ApiKeyExpires is null or apikeys.ApiKeyExpires > NOW()) and
                (apikeys.ApiKeyUsedLimit = -1 or apikeys.ApiKeyUsedCount < apikeys.ApiKeyUsedLimit) and
                (apikeys.ApiKeyUsedDayLimit = -1 or ifnull(apikeys_day.UsedCount, 0) < apikeys.ApiKeyUsedDayLimit) and
                application.ApplicationBlocked = false and
                (application.ApplicationAllowFromAll = true or
                    application.ApplicationAllowFromAll = false and application_allowed_address.Address = :Address)
        ');

        $stmt->execute([
            ':AppKey' => $appKey,
            ':ApiKey' => $apiKey,
            ':Address' => $this->request()->getIp()
        ]);
        
        $this->apikey = $stmt->fetchObject();
        
        if($this->apikey === false)
            return false;
        
        $stmt = $this->pdoServer->prepare('
            INSERT INTO apikeys_day VALUES (:ApiKeyID, CURDATE(), 1)
            ON DUPLICATE KEY UPDATE UsedCount = UsedCount + 1
        ');
        $stmt->execute([
            ':ApiKeyID' => $this->apikey->ApiKeyID
        ]);
        
        $stmt = $this->pdoServer->prepare('
            UPDATE
                apikeys
            INNER JOIN
                apikeys_day ON (apikeys_day.ApiKeyID = apikeys.ApiKeyID AND
                                    apikeys_day.ApiKeyDay = CURDATE())
            SET
                apikeys.ApiKeyUsedCount = ApiKeyUsedCount + 1,
                apikeys.ApiKeyUsedDay = apikeys_day.UsedCount
            WHERE
                apikeys.ApiKeyID = :ApiKeyID');
        $stmt->execute([
            ':ApiKeyID' => $this->apikey->ApiKeyID
        ]);
        
        // Carrega o objeto de criptografia para a API.
        $this->apikey->crypt = new OpenSSLServer($this->apikey->ApiKeyPrivateKey, $this->ApiKeyPassword, $this->ApiKeyX509);
        
        $this->permissions = $this->apikey->ApiPermission;

        return true;
    }
    
    /**
     * @see \Slim\Slim::halt()
     */
    public function halt($status, $message = '', array $data = array())
    {
        parent::halt($status, $this->parseReturn(array_merge([
            'status' => $status,
            'message' => utf8_encode($message),
        ], $data)));
    }
    
    /**
     * Transforma a string recebida no retorno para a chave api.
     *
     * @param array $data Dados para retorno.
     *
     * @return string String de retorno.
     */
    public function parseReturn($data = [])
    {
        $sReturn = json_encode($data);
        
        /*// Faz a assinatura e criptografa os dados de retorno.
        if($this->config->SecureData->enabled === true && $this->hasApiKey === true 
            && $this->config->SecureData->force === true)
        {
            $aSigned = $this->apikey->crypt->encrypt($sReturn);
            $data['signature'] = $aSigned['signature'];
            $data['digest'] = $aSigned['digest'];
            
            $sTmpReturn = json_encode($data);
            $aData = $this->apikey->crypt->encrypt($sTmpReturn);
            $sReturn = $aData['crypted'];
        }*/

        return $sReturn;
    }

    /**
     * Gera uma nova chave de api aleatóriamente.
     *
     * @static
     *
     * @return string Nova chave api unica.
     */
    public static function generateNewApiKey()
    {
        return crypt(uniqid(microtime(true), true));
    }
}


