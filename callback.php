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
    $username = $app->request()->post('username');
    $userpass = $app->request()->post('userpass');
    $sex = $app->request()->post('sex');
    
    if(is_null($username) or is_null($userpass) or is_null($sex))
    {
        $app->halt(400, 'Nem todos os parametros para alteração de sexo foram recebidos.');
    }
    else if(($obj = $app->login($username, $userpass)) === false)
    {
        $app->halt(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changeSex($obj->account_id, $sex) === false)
    {
        $app->halt(401, 'Ocorreu um erro durante do sexo.');
    }
    else
    {
        echo $app->returnString(json_encode((object)array(
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
    $username = $app->request()->post('username');
    $userpass = $app->request()->post('userpass');
    $newemail = $app->request()->post('new_email');
    $oldemail = $app->request()->post('old_email');
    
    if(is_null($username) or is_null($userpass) or is_null($newemail) or is_null($oldemail))
    {
        $app->halt(400, 'Nem todos os parametros para alteração de email foram recebidos.');
    }
    else if(($obj = $app->login($username, $userpass)) === false)
    {
        $app->halt(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changeMail($obj->account_id, $oldemail, $newemail))
    {
        $app->halt(401, 'Ocorreu um erro durante a alteração de email.');
    }
    else
    {
        echo $app->returnString(json_encode((object)array(
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
    $username = $app->request()->post('username');
    $userpass = $app->request()->post('userpass');
    $newpass = $app->request()->post('newpass');

    if(is_null($username) or is_null($userpass) or is_null($newpass))
    {
        $app->halt(400, 'Nem todos os parametros para alteração de senha foram recebidos.');
    }
    else if(($obj = $app->login($username, $userpass)) === false)
    {
        $app->halt(401, 'Nome de usuário/senha inválidos.');
    }
    else if($app->changePass($obj->account_id, $userpass, $newpass) === false)
    {
        $app->halt(401, 'Ocorreu um erro durante a alteração de senha.');
    }
    else
    {
        echo $app->returnString(json_encode((object)array(
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
    $username = $app->request()->post('username');
    $userpass = $app->request()->post('userpass');

    if(is_null($username) or is_null($userpass))
    {
        $app->halt(400, 'Nem todos os parametros para login de conta foram recebidos.');
    }
    else if(($obj = $app->login($username, $userpass)) === false)
    {
        $app->halt(401, 'Nome de usuário/senha inválidos.');
    }
    else
    {
        echo $app->returnString(json_encode($obj));
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
    $username = $app->request()->put('username');
    $userpass = $app->request()->put('userpass');
    $sex = $app->request()->put('sex');
    $email = $app->request()->put('email');
    
    // Verifica se algum dado foi retornado de forma incorreta.
    if(is_null($username) or is_null($username) or is_null($sex) or is_null($email))
    {
        $app->halt(400, 'Nem todos os parametros para criação de conta foram recebidos.');
    }
    // Testa se a conta foi criada com sucesso.
    else if(($obj = $app->createAccount($username, $userpass, $sex, $email)) === false)
    {
        $app->halt(400, 'Não foi possivel criar o nome de usuário. Verifique os parametros enviados.');
    }
    else
    {
        echo $app->returnString(json_encode($obj));
    }
}
?>
