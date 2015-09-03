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
use brAWebService\Middleware;

/**
 * Classe para execu��o do Webserver local.
 * @final
 */
final class brAWebServer extends Slim\Slim
{
    /**
     * Gancho de conex�o com o banco de dados do Service.
     * @var \PDO
     */
    public $pdoServer;

    /**
     * Gancho de conex�o com o banco de dados do Ragnarok.
     * @var \PDO
     */
    public $pdoRagna;
    
    /**
     * Objeto de configura��o para o service.
     * @var object
     */
    public $config;

    /**
     * Objeto da apikey.
     * @var object
     */
    public $apikey;

    /**
     * Informa se o usu�rio enviou uma chave v�lida para o servidor.
     * @var boolean
     */
    public $hasApiKey;
    
    /**
     * Permiss�es carregadas para a chave atual.
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

            // O Slim usa conceito de pilha para execu��o dos middlewares adicionados,
            //  ent�o, necess�rio adicionar em forma inversa... [10/07/2015] CHLFZ
            $this->add(new Middleware\brAWebConfigRequest());
            $this->add(new Middleware\brAWebConfigRoutes());
            $this->add(new Middleware\brAWebConfigSql());
            $this->add(new Middleware\brAWebConfigLoad());
        }
    } /* fim - public function __construct(array $userSettings = array()) */

    /**
     * M�todo utilizado para definir as rotas que ser�o utilizadas pelo sistema.
     *
     * @param string $method M�todo para definir a rota. (GET, POST, PUT, DELETE)
     * @param string $route M�todo para a rota ser executada.
     * @param string $permission Permiss�es da APIKEY para execu��o do action e method.
     * @param callback $callback fun��o a ser executada quando a rota for utilizada.
     *
     * @return void
     */
    public function defineRoute($method, $route, $permission, $callback)
    {
        $this->{$method}($route, function() use ($callback, $permission) {
            $app = brAWebServer::getInstance();
            
            if(($app->permissions&$permission) != $permission)
            {
                $app->halt(401, 'Acesso negado. Voc� n�o tem permiss�es para esta rota.');
            }
            
            $callback();
        });
    }
    
    /**
     * M�todo utilizado para carregar uma configura��o do ini de configura��o.
     * @return void
     */
    public function loadConfig()
    {
        // Carrega o arquivo de configura��o.
        $this->config = json_decode(json_encode(parse_ini_file(dirname(__FILE__) . '/../config.ini', true)));
 
        // Faz o parse completo de algumas configura��es especiais que precisem de tratamento para ser utilizadas.
        // As demais j� foram tratadas acima.
        $this->config->Status->maintence = filter_var($this->config->Status->maintence, FILTER_VALIDATE_BOOLEAN);
        $this->config->SecureData->enabled = filter_var($this->config->SecureData->enabled, FILTER_VALIDATE_BOOLEAN);
        $this->config->SecureData->force = filter_var($this->config->SecureData->force, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * M�todo utilizado para bloqueio de uma chave de api e aplica��o.
     *
     * @param string $apiKey Chave de api para ser bloqueada.
     * @param string $applicationKey Chave do app para ser bloqueado.
     */
    public function blockApiKey($apiKey, $applicationKey)
    {
        // Realiza a query de update para bloquear ambos os registros.
        $stmt = $this->pdoServer->prepare('
            UPDATE
                application
            INNER JOIN
                apikeys
                    ON (apikeys.ApiKeyID = application.ApiKeyID)
            SET
                application.ApplicationBlocked = true,
                application.ApplicationDtBlocked = CURDATE(),
                apikeys.ApiKeyDtCanceled = NOW()
            WHERE
                application.ApplicationBlocked = false AND
                application.ApplicationDtBlocked is null AND
                apikeys.ApiKeyDtCanceled is null AND
                apikeys.ApiKey = :ApiKey AND
                application.ApplicationKey = :ApplicationKey;
        ');
        $stmt->execute([
            ':ApiKey' => $apiKey,
            ':ApplicationKey' => $applicationKey
        ]);
        // Caso nenhum registro seja afetado, significa que nada foi encontrado e nada pode
        //  ser cancelado/desativado/bloqueado.
        if($stmt->rowCount() == 0)
        {
            $this->halt(401, 'N�o foi poss�vel bloquear a chave de api e a aplica��o.');
        }
        else
        {
            return true;
        }
    }

    /**
     * M�todo utilizado para cria��o de uma nova chave de api para uma aplica��o.
     *
     * @param string $appName Nome da aplica��o a ser criada.
     * @param string $permission Permiss�o da aplica��o.
     * @param bool $allowAll Permiss�o para saber se ir� habilitar todos os ips para a api.
     * @param string $ipAllowed String em base64 caso $allowAll = 'false'
     *
     * @return mixed Caso n�o gere, retorna falso.
     */
    public function createApiKey($appName, $permission, $useLimit, $useDayLimit, $allowAll, $ipAllowed)
    {
        // Gera o novo c�digo da api.
        $apiKey = self::generateNewApiKey();
        // Dados de criptografia e certificado digital.
        $certificate = OpenSSLServer::newCertificate();

        // Prepara a query para a nova chave de api.
        $stmt = $this->pdoServer->prepare('
            INSERT INTO apikeys
            VALUES (NULL, :ApiKey, 1, NOW(), NULL, 0, :ApiKeyUsedLimit, 0, :ApiKeyUsedDayLimit, NULL,
                :ApiKeyPrivateKey, :ApiKeyPassword, :ApiKeyX509, :ApiPermission)
        ');

        // Executa a query para gerar a nova chave de api.
        $stmt->execute([
            ':ApiKey' => $apiKey,
            ':ApiKeyUsedLimit' => $useLimit,
            ':ApiKeyUsedDayLimit' => $useDayLimit,
            ':ApiKeyPrivateKey' => $certificate['privateKey'],
            ':ApiKeyPassword' => $certificate['passphrase'],
            ':ApiKeyX509' => $certificate['x509'],
            ':ApiPermission' => $permission
        ]);

        // Obt�m o c�digo para os dados do banco.
        $ApiKeyID = $this->pdoServer->lastInsertId();

        // Obt�m um novo c�digo para aplica��o.
        $AppKey = self::generateNewAppKey();

        // Cria a query para inserir uma nova aplica��o no banco de dados.
        $stmt = $this->pdoServer->prepare('
            INSERT INTO application
            VALUES (NULL, :Application, :ApplicationKey, NOW(), :ApplicationAllowFromAll, false, NULL, :ApiKeyID)
        ');
        $stmt->execute([
            ':Application' => $appName,
            ':ApplicationKey' => $AppKey,
            ':ApplicationAllowFromAll' => (($allowAll === false) ? 0:1),
            ':ApiKeyID' => $ApiKeyID
        ]);

        // Obt�m o c�digo da aplica��o.
        $ApplicationID = $this->pdoServer->lastInsertId();

        // Caso n�o esteja habilitada para uso por todos, ent�o,
        //  adiciona ao banco de dados, todos os endere�os ips.
        if($allowAll === false)
        {
            foreach(explode(',', base64_decode($ipAllowed)) as $sIp)
            {
                $stmt = $this->pdoServer->prepare('
                    INSERT INTO application_allowed_address
                    VALUES (:ApplicationID, NULL, :Address, CURDATE())
                ');
                $stmt->execute([
                    ':ApplicationID' => $ApplicationID,
                    ':Address' => $sIp
                ]);
            }
        }

        return [
            'apiKey' => $apiKey,
            'applicationKey' => $AppKey,
            'x509' => $certificate['x509']
        ];
    }

    /**
     * M�todo utilizado para tentar realizar uma altera��o de senha.
     *
     * @param string $userid Nome de usu�rio para altera��o da senha.
     * @param string $user_pass Senha atual do usu�rio para altera��o.
     * @param string $new_password Nova senha que ser� utilizada para o usu�rio.
     *
     * @return boolean Retorna verdadeiro caso tenha alterado com sucesso.
     */
    public function changePassword($userid, $user_pass, $new_password)
    {
        // � necess�rio estar logado para realizar esta mudan�a.
        // Caso os dados estejam incorretos, ser� retornado 404. Veja: $this->login($userid, $user_pass)
        $obj = $this->login($userid, $user_pass);

        // Necess�rio este m�todo pois caso seja necess�rio uma recupera��o de conta
        //  uma nova senha ser� gerada por ele.
        return $this->updatePassword($obj['account_id'], $new_password, $user_pass);
    }

    /**
     * M�todo utilizado para atualizar a senha de usu�rio no banco de dados.
     *
     * @param integer $account_id C�digo da conta que ser� atualizada.
     * @param string $password Senha que ser� definida para o usu�rio.
     * @param string $old_password Senha antiga. Caso definida far� parte do teste. Se n�o, ser� for�ado a altera��o.
     *
     * @return boolean Caso seja possivel alterar a senha, ser� retornado verdadeiro.
     */
    private function updatePassword($account_id, $password, $old_password = null)
    {
        // Verifica se a senha antiga foi enviada para o teste.
        // Caso tenha sido, somente ser� alterada a senha do usu�rio caso a senha
        //  antiga seja igual a senha atual e o account_id seja correspondente a senha.
        if(is_null($old_password) === false)
        {
            $stmt = $this->pdoRagna->prepare('
                update
                    login
                set
                    user_pass = :user_pass
                where
                    account_id = :account_id and
                    user_pass = :old_user_pass
            ');
            $stmt->execute([
                ':user_pass' => $password,
                ':account_id' => $account_id,
                ':old_user_pass' => $old_password
            ]);
        }
        else
        {
            // Caso a senha antiga n�o tenha sido enviada, ent�o for�a a altera��o da senha
            //  de usu�rio para a nova senha enviada.
            $stmt = $this->pdoServer->prepare('
                update
                    login
                set
                    user_pass = :user_pass
                where
                    account_id = :account_id
            ');
            $stmt->execute([
                ':user_pass' => $password,
                ':account_id' => $account_id
            ]);
        }

        return $stmt->rowCount() > 0;
    }

    /**
     * Tenta realizar um login no acesso de usu�rio fornecido.
     *
     * @param string $userid Nome de usu�rio para verifica��o.
     * @param string $user_pass Senha para o nome de usu�rio.
     */
    public function login($userid, $user_pass)
    {
        // Verifica se a conta pode realizar login.
        // Caso n�o consiga:
        //   1� - Nome de usu�rio e senha incorretos.
        //   2� - Nivel de conta n�o pode acessar via service.
        //   3� - A Conta est� bloqueada para acesso.
        if(($obj = $this->checkUserPass($userid, $user_pass)) === false)
        {
            $this->halt(404, 'Combina��o de usu�rio e senha inv�lidos.');
        }
        else
        {
            return $obj;
        }
    }

    /**
     * Verifica se os dados informados podem realizar login.
     *
     * @param string $userid Nome de usu�rio para login.
     * @param string $user_pass Senha para realizar o login.
     *
     * @return mixed Retorna falso caso n�o possa ou um objeto com informa��es login.
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
     * Verifica se um nome de usu�rio j� existe no banco de dados.
     *
     * @param string $userid Nome de usu�rio.
     *
     * @return boolean Caso verdadeiro, o nome de usu�rio j� est� cadastrado.
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
     * Verifica se o servidor est� online e retorna os dados.
     */
    public function checkServerStatus()
    {
        // Query para verificar se existem lan�amentos no banco de dados
        //  do status do servidor. (Cache temporario de verifica��o, se n�o da problema de DDOS na porta do emulador)
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

        // Caso n�o encontre os dados para a consulta, ent�o inicia o teste para as portas do servidor e ip.
        if($rs_status === false)
        {
            // Realiza a tentativa de abrir uma conex�o com as portas do servidor.
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
            // Informa os dados que ser�o inseridos no banco de dados para futuras auditorias e funcionalidades.
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
            // Caso tenha aberto os ponteiros de verifica��o, encerra os mesmos para n�o
            //  manter a conex�o aberta com o server e sobre-carregar.
            if($login) fclose($login);
            if($char) fclose($char);
            if($map) fclose($map);

            // Refaz a query de consulta que agora ter� os dados no banco.
            $this->checkServerStatus();
            return;
        }
        // Retorna as informa��es de status do servidor, com informa��es da pr�xima verifica��o
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
     * M�todo utilizado para realizar uma verifica��o da apiKey do usu�rio e obter as permiss�es.
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
        
        try
        {
            // Carrega o objeto de criptografia para a API.
            $this->apikey->crypt = new OpenSSLServer($this->apikey->ApiKeyPrivateKey, $this->apikey->ApiKeyPassword, $this->apikey->ApiKeyX509);
        }
        catch(\Exception $ex)
        {
            $this->halt(401, $ex->getMessage());
        }
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
     * Gera uma nova chave de api aleat�riamente.
     *
     * @static
     *
     * @return string Nova chave api unica.
     */
    public static function generateNewApiKey()
    {
        return @crypt(uniqid(microtime(true), true));
    }

    /**
     * Gera a nova chave de app.
     *
     * @static
     *
     * @return string C�digo da chave de aplica��o.
     */
    public static function generateNewAppKey()
    {
        return hash('md5', uniqid(microtime(true), true));
    }
}


