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
final class brAWebConfigRequest extends Slim\Middleware
{
    public function call()
    {
        // Obtém a instancia do app.
        $app = $this->app;

        // Tenta realizar a conexão ao banco de dados do serviço e do ragnarok.
        try
        {
            // Obtém a chave de api enviada pelo usuário.
            $apiKey = $app->request()->get('apiKey');
            // Obtém o código único da aplicação que está fazendo uso da apikey.
            $appKey = $app->request()->get('applicationKey');
            
            // Realiza a validação a chave enviada pelo usuário.
            // [10/07/2015] CHLFZ: Adicionado verificação da appKey.
            if(!is_null($apiKey) && !is_null($appKey))
            {
                // Realiza os testes da chave api e já carrega as configurações para a chave.
                // OBS.: Permissões e etc...
                if(!$app->checkApiKey($apiKey, $appKey))
                {
                    $app->halt(405, 'ApiKey fornecida é inválida ou atingiu o limite de requisições diárias. (COD: 2)');
                }

                // Informa que a chave API fornecida é válida.
                $app->hasApiKey = true;
                
                // Realiza os testes para saber se será forçado o uso de criptografia.
                // Caso forçado, retornará como a versão anterior do sistema, porém com modificações na biblioteca
                //  de criptografia. [Atualizar, brAWebClient]
                if($app->config->SecureData->force === false)
                {
                    $secure = $app->request()->get('secure');
                    $app->config->SecureData->force = (is_null($secure) === false
                                                    && filter_var($secure, FILTER_VALIDATE_BOOLEAN) === true);
                }
            }
            else
            {
                // Carrega as permissões padrões caso não haja chave.
                $app->permissions = $app->config->Status->noKeyPermission;
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


