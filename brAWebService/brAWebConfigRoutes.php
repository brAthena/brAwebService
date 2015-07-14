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
final class brAWebConfigRoutes extends Slim\Middleware
{
    public function call()
    {
        // Obt�m a instancia do app.
        $app = $this->app;
        
        // Define o acesso a rota padr�o para o sistema e retorna uma mensagem:
        //  code: 200, message: 'ok'
        $app->defineRoute('get', '/', '10000000000000000000', function() {
            brAWebServer::getInstance()->halt(200, 'ok');
        });
        
        // Define a rota para teste de se o servidor est� online.
        $app->defineRoute('get', '/status/', '10000000000000000000', function() {
            brAWebServer::getInstance()->checkServerStatus();
        });

        // Define a rota para realizar login no sistema.
        $app->defineRoute('get', '/account/login/', '11000000000000000000', function() {
            // Obt�m a inst�ncia do APP global.
            $app = brAWebServer::getInstance();

            // Obt�m os dados enviados para a requisi��o.
            $userid = $app->request()->get('userid');
            $user_pass = $app->request()->get('user_pass');

            // Caso realize o login com sucesso, retorna os dados do login e mensagem de sucesso.
            // OBS.: A Mensagem de erro j� acontece durante o teste do m�todo.
            if(($obj = $app->login($userid, $user_pass)))
            {
                $app->halt(200, 'Login realizado com sucesso.', $obj);
            }
        });

        // Define a rota para altera��o de senha.
        $app->defineRoute('post', '/account/password/change/', '11000000000000000000', function() {
            // Obt�m a inst�ncia da aplica��o.
            $app = brAWebServer::getInstance();

            // Obt�m os dados para realizar a altera��o de senha.
            $userid = $app->request()->post('userid');
            $user_pass = $app->request()->post('user_pass');
            $new_password = $app->request()->post('new_password');

            // Verifica se a senha pode ser alterada, caso possa, retorna mensagem de sucesso.
            if($app->changePassword($userid, $user_pass, $new_password))
            {
                $app->halt(200, 'Senha alterada com sucesso.');
            }
            else
            {
                $app->halt(401, 'N�o foi poss�vel alterar sua senha. Verifique se a senha atual corresponde a senha informada.');
            }
        });

        // Rota para obter o certificado da chave de api.
        $app->defineRoute('get', '/apikey/certificate/', '11000000000000000000', function() {
            // Obt�m a inst�ncia da aplica��o.
            $app = brAWebServer::getInstance();

            // Verifica se a apikey est� definida e n�o vazia e se o certificado est� definido.
            // Se estiver, retorna status 200.
            if(isset($app->apikey) && !empty($app->apikey) && isset($app->apikey->ApiKeyX509))
            {
                $app->halt(200, 'Leitura do certificado realizada com sucesso.', [
                    'x509' => $app->apikey->ApiKeyX509
                ]);
            }
            else
            {
                $app->halt(412, 'Api declarada n�o possui certificado.');
            }
        });

        // Rota para bloquear uma chave de api e uma aplica��o.
        $app->defineRoute('post', '/apikey/block/', '11000000000000000010', function() {
            $app = brAWebServer::getInstance();

            // Ambas as informa��es devem estar vinculadas.
            $apiKey = $app->request()->post('apiKey');
            $appKey = $app->request()->post('applicationKey');

            // Caso bloqueie com sucesso, retornar� 200 de status.
            if($app->blockApiKey($apiKey, $applicationKey))
            {
                $app->halt(200, 'Chave de api e aplica��o bloqueadas com sucesso.');
            }
        });

        // Rota para cria��o de uma nova chave de api com todas as informa��es de certificado.
        $app->defineRoute('get', '/apikey/create/', '11000000000000000001', function() {
            $app = brAWebServer::getInstance();
            // Obt�m os parametros para 
            $appName = $app->request()->get('appName');
            $permission = $app->request()->get('permission');
            $useLimit = $app->request()->get('useLimit');
            $useDayLimit = $app->request()->get('useDayLimit');
            $allowAll = boolval($app->request()->get('allowAll'));
            $ipAllowed = $app->request()->get('ipAllowed');

            // Caso o teste para cri��o da chave retorne verdadeiro, retorna os dados.
            // OBS.: O Teste erro j� est� no m�todo.
            if(($obj = $app->createApiKey($appName, $permission, $useLimit, $useDayLimit, $allowAll, $ipAllowed)))
            {
                $app->halt(200, 'Chave de api gerada com sucesso.', $obj);
            }
        });
        
        // Run inner middleware and application
        $this->next->call();
    }
}


