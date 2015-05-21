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
final class brAWebConfigRoutes extends Slim\Middleware
{
    public function call()
    {
        // Obtém a instancia do app.
        $app = $this->app;
        
        // Define o acesso a rota padrão para o sistema e retorna uma mensagem:
        //  code: 200, message: 'ok'
        $app->defineRoute('get', '/', '10000000000000000000', function() {
            brAWebServer::getInstance()->halt(200, 'ok');
        });
        
        // Define a rota para teste de se o servidor está online.
        $app->defineRoute('get', '/status/', '10000000000000000000', function() {
            brAWebServer::getInstance()->checkServerStatus();
        });
        
        // Run inner middleware and application
        $this->next->call();
    }
}


