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

namespace brAWebService\Middleware;

use Slim;

/**
 * Classe para execução do Webserver local.
 * @final
 */
final class brAWebConfigLoad extends Slim\Middleware
{
    public function call()
    {
        // Obtém a instancia do app.
        $app = $this->app;

        try
        {
            // Verifica se o arquivo de configuração, gerado pós instalação existe,
            // se não existir, informa error.
            if(file_exists(dirname(__FILE__) . '/../../config.ini') === false)
            {
                $app->halt(405, 'Sistema em manutenção. (COD: 0)');
            }

            // Carrega o arquivo de configuração e define os parametros no service.
            $app->loadConfig();
            
            // Verifica se o sistema está configurado para modo manutenção, se estiver, nega a conexão.
            if($app->config->Status->maintence === true)
            {
                $app->halt(405, 'Sistema em manutenção. (COD: 1)');
            }
            
            // Passa para o próximo call.
            $this->next->call();
        }
        catch(\Slim\Exception\Stop $e)
        {
            $app->response()->write(ob_get_clean());
        }
    }
}


