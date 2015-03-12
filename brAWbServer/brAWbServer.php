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
            
            $this->add(new \Slim\Middleware\ContentTypes());
            
            // Iguala para poder usar dentro das funções. $this nao pode ser enviado.
            $app = $this;
            
            // Executa as operações para verificação do apiKey ao banco de dados.
            // Tirei como base: https://gist.github.com/RodolfoSilva/1f438da56cb55c1eaea0 [carloshlfz, 10/03/2015]
            $this->hook('slim.before.router', function() use ($app) {
                // ApiKey de acesso ao sistema.
                $apiKey = $app->request()->get('apiKey');

                // Se token não foi enviado para a aplicação.
                if(is_null($apiKey) === true)
                {
                    throw new brAWbServerException('Acesso negado. ApiKey de acesso não fornecido.');
                }
                else if($app->checkApiKey($apiKey) === false)
                { // ApiKey inválido.
                    throw new brAWbServerException('Acesso negado. ApiKey inválida! Verifique por favor.');
                }
            });

            // Adiciona o método para put de crição de contas.
            $this->put('/account/', function() use ($app) {
                // Obtém os dados da requisição do put.
                $username = $app->request()->put('username');
                $userpass = $app->request()->put('userpass');
                $sex = $app->request()->put('sex');
                $email = $app->request()->put('email');
                
                // Verifica se algum dado foi retornado de forma incorreta.
                if(is_null($username) or is_null($username) or is_null($sex) or is_null($email))
                {
                    throw new brAWbServer\brAWbServerException('Nem todos os parametros para criação de conta foram recebidos.');
                }
                // Testa se a conta foi criada com sucesso.
                else if(($obj = $app->createAccount($username, $userpass, $sex, $email)) === false)
                {
                    throw new brAWbServer\brAWbServerException('Não foi possivel criar o nome de usuário. Verifique os parametros enviados.');
                }

                echo json_encode($obj);
            });

        } // fim - public function __construct()
        
        /**
         * Cria uma conta no banco de dados e retorna o objeto com os dados de criação.
         *
         * @param string $username Nome de usuário. [Padrão: ^([a-z0-9]{4,24})$]
         * @param string $userpass Senha de usuário. [Padrão: ^([a-f0-9]{32})$]
         * @param string $sex Senha da conta a ser criada. [Padrão: ^(M|F)$]
         * @param string $email Email para a conta a ser criada. [Padrão: ^([^@]+)@([^\.]+)\..+$]
         *
         * @return \stdClass Classe retornando os dados de criação da conta.
         */
        public function createAccount($username, $userpass, $sex, $email)
        {
            // Obtém as validações regex para este método.
            $createAccountValidation = $this->simpleXmlHnd->createAccountValidation;

            // Verifica se todos os dados recebidos estão dentro dos regex.
            if(!preg_match("/{$createAccountValidation->username}/i", $username))
                throw new brAWbServerException('Nome de usuário em formato inválido!');
            else if(!preg_match("/{$createAccountValidation->userpass}/i", $userpass))
                throw new brAWbServerException('Senha de usuário em formato inválido!');
            else if(!preg_match("/{$createAccountValidation->sex}/i", $sex))
                throw new brAWbServerException('Sexo para conta inválido! Aceitos: M ou F');
            else if(!preg_match("/{$createAccountValidation->email}/i", $email))
                throw new brAWbServerException('Email de usuário em formato inválido');

            // @todo: Criação da conta.
            return (object)array(
                'account_id' => -1
            );
        }
        
        /**
         * Verifica se o apikey solicitado é valido. E atualiza a contagem do ApiKey.
         *
         * @param string $apiKey
         *
         * @return boolean
         */
        public function checkApiKey($apiKey)
        {
            $bExists = false;
            
            $this->pdoServer = new \PDO($this->simpleXmlHnd->PdoServerConnection->{'@attributes'}->connectionString,
                NULL, NULL, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));
            $stmt = $this->pdoServer->prepare('
                UPDATE
                    brawbkeys
                SET
                    ApiUsedCount = ApiUsedCount + 1
                WHERE
                    ApiKey = :ApiKey
                        AND
                    (ApiUnlimitedCount = true
                        or (ApiUsedCount < ApiLimitCount AND date() <= ApiExpires))
            ');
            $stmt->execute(array(
                ':ApiKey' => $apiKey
            ));
            $bExists = ($stmt->rowCount() > 0);
            $this->pdoServer = null;

            return $bExists;
        }
    } // fim - class brAWbServer extends \Slim\Slim
} // fim - namespace brAWbServer
?>
