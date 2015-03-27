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

/**
 * Função para listar todos os personagens da conta.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_CharList_Post(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição POST.
    $request = $app->getRequestFields('username', 'userpass');

    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->sendResponse(401, 'Nome de usuário/senha inválidos.');
    }
    else
    {
        $charList = $app->charList($obj->account_id);
        $app->sendResponse(200, 'Personagens listados com sucesso.', (object)array('chars' => $charList));
    }
}

/**
 * Função para resetar a aparencia dos personagens.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_CharResetAppear_Post(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição POST.
    $request = $app->getRequestFields('username', 'userpass', 'char_id');

    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->sendResponse(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->charResetAppear($obj->account_id, $request->char_id) === false)
    {
        $app->sendResponse(400, 'Impossivel resetar a aparência do personagem.');
    }
    else
    {
        $app->sendResponse(200, 'Aparência resetada com sucesso.', (object)array(
            'account_id' => $obj->account_id,
            'char_id' => $request->char_id,
            'message' => 'Aparência resetada com sucesso.'
        ));
    }
}

/**
 * Função para resetar a posição dos personagens.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_CharResetPosit_Post(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição POST.
    $request = $app->getRequestFields('username', 'userpass', 'char_id');

    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->sendResponse(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->charResetPosit($obj->account_id, $request->char_id) === false)
    {
        $app->sendResponse(400, 'Impossivel resetar a posição do personagem.');
    }
    else
    {
        $app->sendResponse(200, 'Posição resetada com sucesso.', (object)array(
            'account_id' => $obj->account_id,
            'char_id' => $request->char_id,
            'message' => 'Posição resetada com sucesso.'
        ));
    }
}

/**
 * Função para alterar o sexo de uma conta no brAWebService.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_AccountChangeSex_Post(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição POST.
    $request = $app->getRequestFields('username', 'userpass', 'sex');
    
    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->sendResponse(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changeSex($obj->account_id, $request->sex) === false)
    {
        $app->sendResponse(401, 'Ocorreu um erro durante a alteração do sexo.');
    }
    else
    {
        $app->sendResponse(200, 'Sexo alterado com sucesso.', (object)array(
            'account_id' => $obj->account_id,
            'message' => 'Sexo alterado com sucesso.',
            'time' => time()
        ));
    }
}

/**
 * Função para alterar o email de conta no brAWebService.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_AccountChangeMail_Post(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição POST.
    $request = $app->getRequestFields('username', 'userpass', 'new_email', 'old_email');
    
    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->sendResponse(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changeMail($obj->account_id, $request->new_email, $request->old_email))
    {
        $app->sendResponse(401, 'Ocorreu um erro durante a alteração de email.');
    }
    else
    {
        $app->sendResponse(200, 'Email alterado com sucesso.', (object)array(
            'account_id' => $obj->account_id,
            'message' => 'Email alterado com sucesso.',
            'time' => time()
        ));
    }
}

/**
 * Função para alterar uma senha de conta no brAWebService.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_AccountChangePass_Post(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição POST.
    $request = $app->getRequestFields('username', 'userpass', 'newpass');

    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->sendResponse(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changePass($obj->account_id, $request->userpass, $request->newpass) === false)
    {
        $app->sendResponse(401, 'Ocorreu um erro durante a alteração de senha.');
    }
    else
    {
        $app->sendResponse(200, 'Senha alterada com sucesso.', (object)array(
            'account_id' => $obj->account_id,
            'message' => 'Senha alterada com sucesso.',
            'time' => time()
        ));
    }
}

/**
 * Função para iniciar uma sessão de uma conta no brAWebService.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_AccountLogin_Post(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição do POST.
    $request = $app->getRequestFields('username', 'userpass');

    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->sendResponse(401, 'Nome de usuário/senha inválidos.');
    }
    else
    {
        $app->sendResponse(200, 'Login realizado com sucesso.', $obj);
    }
}

/**
 * Função para criação de uma conta.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_Account_Put(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição do put.
    $request = $app->getRequestFields('username', 'userpass', 'sex', 'email');
    
    if(($obj = $app->createAccount($request->username, $request->userpass, $request->sex, $request->email)) === false)
    {
        $app->sendResponse(400, 'Não foi possivel criar o nome de usuário. Verifique os parametros enviados.');
    }
    else
    {
        $app->sendResponse(200, 'Conta criada com sucesso.', $obj);
    }
}
?>
