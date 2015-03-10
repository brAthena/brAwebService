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
     * Classe padrão para as configurações do serviço do webservice.
     */
    class brAWbServer extends \Slim\Slim
    {
        /**
         * Gancho para leitura do arquivo de configuração do sistema.
         * @var \SimpleXMLElement
         */
        public $simpleXmlHnd;

        /**
         * Gancho com a conexão sql do server do webservice.
         * @var \PDO
         */
        public $pdoServer;

        /**
         * Gancho com a conexão sql do servidor do ragnarok.
         * @var \PDO
         */
        public $pdoRagna;
        
        /**
         * Construtor para o objeto do servidor.
         *
         * @param array $userSettings Configurações do framework para execução.
         */
        public function __construct()
        {
            // Carrega o xml de configuração do sistema. Adicionado isso pois não estava conseguindo realizar a leitura
            // dos atributos corretamente.
            $this->simpleXmlHnd = json_decode(json_encode(\simplexml_load_file (dirname(__FILE__).'/../config.xml')));
            
            // Configurações padrões para a execução da aplicação.
            parent::__construct(array(
                'log.writer' => new \Slim\LogWriter(fopen(dirname(__FILE__).'/../Logs/brAWbServer.log', 'a+')) // logs
            ));
            
            // Iguala para poder usar dentro das funções. $this nao pode ser enviado.
            $app = $this;
            
            // Executa as operações para verificação do token de acesso ao banco de dados.
            // Tirei como base: https://gist.github.com/RodolfoSilva/1f438da56cb55c1eaea0 [carloshlfz, 10/03/2015]
            $this->hook('slim.before.router', function() use ($app)
            {
                // Configurações para carregar a conexão com o servidor. Verificações de chaves.
                $xmlPdo = $app->simpleXmlHnd->PdoServerConnection->{'@attributes'};
                
                // Token de acesso ao sistema.
                $token = $app->request()->get('token');

                // Se token não foi enviado para a aplicação.
                if(is_null($token) === true)
                {
                    throw new brAWbServerException('Acesso negado. Token de acesso não fornecido.');
                }
                
                // Abre a conexão de dados com o servidor.
                $app->pdoServer = $app->getPdoServer();

                // @todo: Verificação do token de acesso.

                // Encerra a conexão de dados com o servidor.
                $app->pdoServer = null;
            });
        } // fim - public function __construct()
        
        /**
         * Obtém a conexão com o banco de dados do servidor de serviço.
         * @return \PDO
         */
        public function getPdoServer()
        {
            $xmlPdo = $app->simpleXmlHnd->PdoServerConnection->{'@attributes'};
            return new \PDO($xmlPdo->connectionString, $xmlPdo->user, $xmlPdo->pass);
        }
        
        /**
         * Obtém a conexão com o banco de dados do servidor de ragnarok.
         * @return \PDO
         */
        public function getPdoRagna()
        {
            $xmlPdo = $app->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            return new \PDO($xmlPdo->connectionString, $xmlPdo->user, $xmlPdo->pass); 
        }
    } // fim - class brAWbServer extends \Slim\Slim
} // fim - namespace brAWbServer
?>
