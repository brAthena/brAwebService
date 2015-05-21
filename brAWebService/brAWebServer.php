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
     * @see \Slim\Slim::__construct()
     */
    public function __construct(array $userSettings = array())
    {
        // Invoca o construtor do slim.
        parent::__construct($userSettings);

        // Adiciona o middleware para os conteudos.
        $this->add(new \Slim\Middleware\ContentTypes());

        // Executa as operações para verificação do apiKey ao banco de dados.
        $this->hook('slim.before.router', function() {
            // Obtém a instancia do app.
            $app = brAWebServer::getInstance();
            
            // Verifica se o arquivo de configuração, gerado pós instalação existe,
            // se não existir, informa error.
            if(file_exists(dirname(__FILE__) . '/../config.xml') === false)
            {
                $app->halt(405, 'Sistema em manutenção. (COD: 0)');
            }

            // Carrega o arquivo de configuração e define os parametros no service.
            $app->loadConfig();
            
            exit;
        });
    } /* fim - public function __construct(array $userSettings = array()) */

    /**
     * Método utilizado para carregar uma configuração do xml de configuração.
     * @return void
     */
    public function loadConfig()
    {
    }    
    
    /**
     * @see \Slim\Slim::halt()
     */
    public function halt($status, $message = '')
    {
        parent::halt($status, json_encode(array(
            'status' => $status,
            'message' => utf8_encode($message)
        )));
    }
}


