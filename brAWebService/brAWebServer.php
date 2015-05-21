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
    public function __construct(array $userSettings = array())
    {
        // Invoca o construtor do slim.
        parent::__construct($userSettings);

        // Adiciona o middleware para os conteudos.
        $this->add(new \Slim\Middleware\ContentTypes());
        $this->add(new brAWebConfigLoad());     // <- Executa as valida��es e atribui��es para carregar permiss�es de acesso
                                                //    e etc...
        $this->add(new brAWebConfigRoutes());
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
     */
    public function checkApiKey($apiKey)
    {
        return false;
    }
    
    /**
     * @see \Slim\Slim::halt()
     */
    public function halt($status, $message = '', array $data = array())
    {
        parent::halt($status, json_encode(array_merge(array(
            'status' => $status,
            'message' => utf8_encode(htmlentities($message))
        ), $data)));
    }
}


