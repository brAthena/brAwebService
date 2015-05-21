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
    public function __construct(array $userSettings = array())
    {
        // Invoca o construtor do slim.
        parent::__construct($userSettings);

        // Adiciona o middleware para os conteudos.
        $this->add(new \Slim\Middleware\ContentTypes());
        $this->add(new brAWebConfigLoad());     // <- Executa as validações e atribuições para carregar permissões de acesso
                                                //    e etc...
        $this->add(new brAWebConfigRoutes());
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
    }
    
    /**
     * Verifica se o servidor está online e retorna os dados.
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
     * Método utilizado para realizar uma verificação da apiKey do usuário e obter as permissões.
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


