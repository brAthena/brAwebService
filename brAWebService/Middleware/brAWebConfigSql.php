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
final class brAWebConfigSql extends Slim\Middleware
{
    public function call()
    {
        // Obtém a instancia do app.
        $app = $this->app;

        // Tenta realizar a conexão ao banco de dados do serviço e do ragnarok.
        try
        {
            $app->pdoServer = new \PDO($app->config->SQLService->connectionString,
                                       $app->config->SQLService->user,
                                       $app->config->SQLService->pass, array(
                                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                                       ));
            
            $app->pdoRagna = new \PDO($app->config->SQLRagnarok->connectionString,
                                       $app->config->SQLRagnarok->user,
                                       $app->config->SQLRagnarok->pass, array(
                                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                                       ));

            // Passa para o próximo call.
            $this->next->call();
        }
        catch(\Slim\Exception\Stop $e)
        {
            $app->response()->write(ob_get_clean());
        }
    }
}


