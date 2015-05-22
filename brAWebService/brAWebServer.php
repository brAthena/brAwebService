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
            $this->add(new brAWebConfigLoad());     // <- Executa as valida��es e atribui��es para carregar permiss�es de acesso
                                                    //    e etc...
            $this->add(new brAWebConfigRoutes());
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
     * Verifica se o servidor est� online e retorna os dados.
     */
    public function checkServerStatus()
    {
        $this->halt(200, 'ok', array(
            'map' => false,
            'char' => false,
            'login' => false
        ));
    }
    
    /**
     * M�todo utilizado para realizar uma verifica��o da apiKey do usu�rio e obter as permiss�es.
     *
     * @param string $apiKey Chave de api para uso.
     *
     * @return boolean Verdadeiro caso chave esteja ok.
     */
    public function checkApiKey($apiKey)
    {
        $stmt = $this->pdoServer->prepare('
            SELECT
                apikeys.*
            FROM
                apikeys
            LEFT JOIN
                apikeys_day on (apikeys_day.ApiKeyID = apikeys.ApiKeyID AND
                                apikeys_day.ApiKeyDay = CURDATE())
            WHERE
                ApiKey = :ApiKey AND
                ApiKeyEnabled = true AND
                (ApiKeyExpires IS NULL OR ApiKeyExpires > NOW()) AND
                (ApiKeyUsedLimit = -1 OR ApiKeyUsedCount < ApiKeyUsedLimit) AND
                (ApiKeyUsedDayLimit = -1 OR ifnull(apikeys_day.UsedCount, 0) < ApiKeyUsedDayLimit) AND
                ApiKeyDtCanceled IS NULL
        ');
        $stmt->execute(array(
            ':ApiKey' => $apiKey
        ));
        
        $this->apikey = $stmt->fetchObject();
        
        if($this->apikey === false)
            return false;
        
        $stmt = $this->pdoServer->prepare('
            INSERT INTO apikeys_day VALUES (:ApiKeyID, CURDATE(), 1)
            ON DUPLICATE KEY UPDATE UsedCount = UsedCount + 1
        ');
        $stmt->execute(array(
            ':ApiKeyID' => $this->apikey->ApiKeyID
        ));
        
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
        $stmt->execute(array(
            ':ApiKeyID' => $this->apikey->ApiKeyID
        ));
        
        // Carrega o objeto de criptografia para a API.
        $this->apikey->crypt = new MCrypt($this->apikey->ApiCryptKey, $this->apikey->ApiCryptIV,
                                            $this->apikey->ApiCryptCipher, $this->apikey->ApiCryptMethod);
        
        $this->permissions = $this->apikey->ApiPermission;

        return true;
    }
    
    /**
     * @see \Slim\Slim::halt()
     */
    public function halt($status, $message = '', array $data = array())
    {
        parent::halt($status, $this->parseReturn(array_merge(array(
            'status' => $status,
            'message' => utf8_encode(htmlentities($message))
        ), $data)));
    }
    
    /**
     * Transforma a string recebida no retorno para a chave api.
     *
     * @param array $data Dados para retorno.
     *
     * @return string String de retorno.
     */
    public function parseReturn(array $data = array())
    {
        $sReturn = json_encode($data);
        
        if($this->config->SecureData->enabled === true && $this->hasApiKey === true 
            && $this->config->SecureData->force === true)
        {
            $sReturn = $this->apikey->crypt->encrypt($sReturn);
        }

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
        return crypt(uniqid(microtime(true), true));
    }
}


