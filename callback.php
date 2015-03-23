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
 * @param \brAWebService\brAWebService $app
 */
function bra_CharList_Get(\brAWebService\brAWebService $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para resetar a aparencia dos personagens.
 *
 * @param \brAWebService\brAWebService $app
 */
function bra_CharResetAppear_Post(\brAWebService\brAWebService $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para resetar a posição dos personagens.
 *
 * @param \brAWebService\brAWebService $app
 */
function bra_CharResetPosit_Post(\brAWebService\brAWebService $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para alterar o sexo de uma conta no brAWebService.
 *
 * @param \brAWebService\brAWebService $app
 */
function bra_AccountChangeSex_Post(\brAWebService\brAWebService $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para alterar o email de conta no brAWebService.
 *
 * @param \brAWebService\brAWebService $app
 */
function bra_AccountChangeMail_Post(\brAWebService\brAWebService $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para alterar uma senha de conta no brAWebService.
 *
 * @param \brAWebService\brAWebService $app
 */
function bra_AccountChangePass_Post(\brAWebService\brAWebService $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para iniciar uma sessão de uma conta no brAWebService.
 *
 * @param \brAWebService\brAWebService $app
 */
function bra_AccountLogin_Post(\brAWebService\brAWebService $app)
{
    $app->halt(503, 'Em manutenção. Tente mais tarde.');
}

/**
 * Função para criação de uma conta.
 *
 * @param \brAWebService\brAWebService $app
 */
function bra_Account_Put(\brAWebService\brAWebService $app)
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
