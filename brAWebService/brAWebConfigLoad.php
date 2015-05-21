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
final class brAWebConfigLoad extends Slim\Middleware
{
    public function call()
    {
        // Obt�m a instancia do app.
        $app = $this->app;

        try
        {
            // Verifica se o arquivo de configura��o, gerado p�s instala��o existe,
            // se n�o existir, informa error.
            if(file_exists(dirname(__FILE__) . '/../config.ini') === false)
            {
                $app->halt(405, 'Sistema em manuten��o. (COD: 0)');
            }

            // Carrega o arquivo de configura��o e define os parametros no service.
            $app->loadConfig();
            
            // Verifica se o sistema est� configurado para modo manuten��o, se estiver, nega a conex�o.
            if($app->config->Status->maintence === true)
            {
                $app->halt(405, 'Sistema em manuten��o. (COD: 1)');
            }
            
            // Obt�m a chave de api enviada pelo usu�rio.
            $apiKey = $app->request()->get('apiKey');
            
            // Realiza a valida��o a chave enviada pelo usu�rio.
            if(!is_null($apiKey))
            {
                // Realiza os testes da chave api e j� carrega as configura��es para a chave.
                // OBS.: Permiss�es e etc...
                if(!$app->checkApiKey($apiKey))
                {
                    $app->halt(405, 'ApiKey fornecida � inv�lida. (COD: 2)');
                }
                // Informa que a chave API fornecida � v�lida.
                $app->hasApiKey = true;
            }
            else
            {
                // Carrega as permiss�es padr�es caso n�o haja chave.
                $app->permissions = $app->config->Status->noKeyPermission;
            }

            // Tenta realizar a conex�o ao banco de dados do servi�o e do ragnarok.
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
            }
            catch(\PDOException $e)
            {
                $app->halt(405, $e->getMessage());
            }

            $this->next->call();
        }
        catch(\Slim\Exception\Stop $e)
        {
            $app->response()->write(ob_get_clean());
        }
    }
}


