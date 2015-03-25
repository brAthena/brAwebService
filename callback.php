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
function bra_CharList_Get(\brAWebServer\brAWebServer $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para resetar a aparencia dos personagens.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_CharResetAppear_Post(\brAWebServer\brAWebServer $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para resetar a posição dos personagens.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_CharResetPosit_Post(\brAWebServer\brAWebServer $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para alterar o sexo de uma conta no brAWebService.
 *
 * @param \brAWebServer\brAWebServer $app
 */
function bra_AccountChangeSex_Post(\brAWebServer\brAWebServer $app)
{
    // Obtém os dados da requisição POST.
    $request = $app->getRequestFields(array('username', 'userpass', 'sex'));
    
    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->halt(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changeSex($obj->account_id, $sex) === false)
    {
        $app->halt(401, 'Ocorreu um erro durante do sexo.');
    }
    else
    {
        $app->halt(200, json_encode((object)array(
            'account_id' => $obj->account_id,
            'message' => 'Sexo alterado com sucesso.',
            'time' => time()
        )));
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
    $request = $app->getRequestFields(array('username', 'userpass', 'new_email', 'old_email'));
    
    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->halt(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changeMail($obj->account_id, $request->new_email, $request->old_email))
    {
        $app->halt(401, 'Ocorreu um erro durante a alteração de email.');
    }
    else
    {
        $app->halt(200, json_encode((object)array(
            'account_id' => $obj->account_id,
            'message' => 'Email alterado com sucesso.',
            'time' => time()
        )));
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
    $request = $app->getRequestFields(array('username', 'userpass', 'newpass'));

    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->halt(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changePass($obj->account_id, $request->userpass, $request->newpass) === false)
    {
        $app->halt(401, 'Ocorreu um erro durante a alteração de senha.');
    }
    else
    {
        $app->halt(200, json_encode((object)array(
            'account_id' => $obj->account_id,
            'message' => 'Senha alterada com sucesso.',
            'time' => time()
        )));
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
    $request = $app->getRequestFields(array('username', 'userpass'));

    if(($obj = $app->login($request->username, $request->userpass)) === false)
    {
        $app->halt(401, 'Nome de usuário/senha inválidos.');
    }
    else
    {
        $app->halt(200, json_encode($obj));
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
    $request = $app->getRequestFields(array('username', 'userpass', 'sex', 'email'));
    
    if(($obj = $app->createAccount($request->username, $request->userpass, $request->sex, $request->email)) === false)
    {
        $app->halt(400, 'Não foi possivel criar o nome de usuário. Verifique os parametros enviados.');
    }
    else
    {
        $app->halt(200, json_encode($obj));
    }
}
?>
