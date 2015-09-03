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
 * Classe para execu��o do Webserver local.
 * @final
 */
final class brAWebConfigRequest extends Slim\Middleware
{
    public function call()
    {
        // Obt�m a instancia do app.
        $app = $this->app;

        // Tenta realizar a conex�o ao banco de dados do servi�o e do ragnarok.
        try
        {
            // Obt�m a chave de api enviada pelo usu�rio.
            $apiKey = $app->request()->get('apiKey');
            // Obt�m o c�digo �nico da aplica��o que est� fazendo uso da apikey.
            $appKey = $app->request()->get('applicationKey');
            
            // Realiza a valida��o a chave enviada pelo usu�rio.
            // [10/07/2015] CHLFZ: Adicionado verifica��o da appKey.
            if(!is_null($apiKey) && !is_null($appKey))
            {
                // Realiza os testes da chave api e j� carrega as configura��es para a chave.
                // OBS.: Permiss�es e etc...
                if(!$app->checkApiKey($apiKey, $appKey))
                {
                    $app->halt(405, 'ApiKey fornecida � inv�lida ou atingiu o limite de requisi��es di�rias. (COD: 2)');
                }

                // Informa que a chave API fornecida � v�lida.
                $app->hasApiKey = true;
                
                // Realiza os testes para saber se ser� for�ado o uso de criptografia.
                // Caso for�ado, retornar� como a vers�o anterior do sistema, por�m com modifica��es na biblioteca
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
                // Carrega as permiss�es padr�es caso n�o haja chave.
                $app->permissions = $app->config->Status->noKeyPermission;
            }

            // Passa para o pr�ximo call.
            $this->next->call();
        }
        catch(\Slim\Exception\Stop $e)
        {
            $app->response()->write(ob_get_clean());
        }
    }
}


