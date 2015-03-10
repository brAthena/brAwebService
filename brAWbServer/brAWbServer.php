<?php
/**
 * brAWbService
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

namespace brAWbServer
{
    /**
     * Classe padr�o para as configura��es do servi�o do webservice.
     */
    class brAWbServer extends \Slim\Slim
    {
        /**
         * Gancho para leitura do arquivo de configura��o do sistema.
         * @var \SimpleXMLElement
         */
        public $simpleXmlHnd;

        /**
         * Gancho com a conex�o sql do server do webservice.
         * @var \PDO
         */
        public $pdoServer;

        /**
         * Gancho com a conex�o sql do servidor do ragnarok.
         * @var \PDO
         */
        public $pdoRagna;
        
        /**
         * Construtor para o objeto do servidor.
         *
         * @param array $userSettings Configura��es do framework para execu��o.
         */
        public function __construct()
        {
            // Carrega o xml de configura��o do sistema. Adicionado isso pois n�o estava conseguindo realizar a leitura
            // dos atributos corretamente.
            $this->simpleXmlHnd = json_decode(json_encode(\simplexml_load_file (dirname(__FILE__).'/../config.xml')));
            
            // Configura��es padr�es para a execu��o da aplica��o.
            parent::__construct(array(
                'log.writer' => new \Slim\LogWriter(fopen(dirname(__FILE__).'/../Logs/brAWbServer.log', 'a+')) // logs
            ));
            
            // Iguala para poder usar dentro das fun��es. $this nao pode ser enviado.
            $app = $this;
            
            // Executa as opera��es para verifica��o do token de acesso ao banco de dados.
            // Tirei como base: https://gist.github.com/RodolfoSilva/1f438da56cb55c1eaea0 [carloshlfz, 10/03/2015]
            $this->hook('slim.before.router', function() use ($app) {
                // Obt�m informa��es sobre a requisi��o do slim.
                $appReq = $app->request();
                // Configura��es para carregar a conex�o com o servidor. Verifica��es de chaves.
                $xmlPdo = $app->simpleXmlHnd->PdoServerConnection->{'@attributes'};
                
                // Token de acesso ao sistema.
                $token = $app->request()->get('token');

                // Se token n�o foi enviado para a aplica��o.
                if(is_null($token) === true)
                {
                    throw new brAWbServerException('Acesso negado. Token de acesso n�o fornecido.');
                }
                
                // Abre a conex�o de dados com o servidor.
                $app->pdoServer = new \PDO($xmlPdo->connectionString, $xmlPdo->user, $xmlPdo->pass);

                // @todo: Verifica��o do token de acesso.

                // Encerra a conex�o de dados com o servidor.
                $app->pdoServer = null;
            });
        } // fim - public function __construct()
    } // fim - class brAWbServer extends \Slim\Slim
} // fim - namespace brAWbServer
?>
