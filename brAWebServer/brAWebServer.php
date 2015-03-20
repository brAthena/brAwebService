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

namespace brAWebServer
{
    /**
     * Classe padrão para as configurações do serviço do webservice.
     */
    class brAWebServer extends \Slim\Slim
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
            
            // Converte os campos para informações de filtros.
            $this->simpleXmlHnd->maintence = filter_var($this->simpleXmlHnd->maintence, FILTER_VALIDATE_BOOLEAN);

            // Configurações padrões para a execução da aplicação.
            parent::__construct(array(
                'log.writer' => new \Slim\LogWriter(fopen(dirname(__FILE__).'/../Logs/brAWebServer.log', 'a+')) // logs
            ));
            
            // Iguala para poder usar dentro das funções. $this nao pode ser enviado.
            $app = $this;

            // Default environment
            $this->container->singleton('environment', function ($c) use ($app) {
                return brAEnvironment::getInstance($app);
            });

            $this->add(new \Slim\Middleware\ContentTypes());
            
            // Executa as operações para verificação do apiKey ao banco de dados.
            // Tirei como base: https://gist.github.com/RodolfoSilva/1f438da56cb55c1eaea0 [carloshlfz, 10/03/2015]
            $this->hook('slim.before.router', function() use ($app) {
                
                // Caso esteja em modo manutenção, não permite que a requisição seja continuada.
                if($app->simpleXmlHnd->maintence === true)
                {
                    $app->halt(503, 'Em manutenção. Tente mais tarde.');
                }
                else
                {
                    // ApiKey de acesso ao sistema.
                    $apiKey = $app->request()->get('apiKey');

                    // Se token não foi enviado para a aplicação.
                    if(is_null($apiKey) === true)
                    {
                        $app->halt(400, 'Acesso negado. ApiKey de acesso não fornecido.');
                    }
                    else if($app->checkApiKey($apiKey) === false)
                    { // ApiKey inválido.
                        $app->halt(401, 'Acesso negado. ApiKey inválida! Verifique por favor.');
                    }
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
                    $app->halt(400, 'Nem todos os parametros para criação de conta foram recebidos.');
                }
                // Testa se a conta foi criada com sucesso.
                else if(($obj = $app->createAccount($username, $userpass, $sex, $email)) === false)
                {
                    $app->halt(400, 'Não foi possivel criar o nome de usuário. Verifique os parametros enviados.');
                }
                else
                {
                    echo json_encode($obj);
                }
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
                $this->halt(400, 'Nome de usuário em formato inválido!');
            else if(!preg_match("/{$createAccountValidation->userpass}/i", $userpass))
                $this->halt(400, 'Senha de usuário em formato inválido!');
            else if(!preg_match("/{$createAccountValidation->sex}/i", $sex))
                $this->halt(400, 'Sexo para conta inválido! Aceitos: M ou F');
            else if(!preg_match("/{$createAccountValidation->email}/i", $email))
                $this->halt(400, 'Email de usuário em formato inválido');

            $account_id = -1;
            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));

            // Query para verificar se o nome de usuário existe.
            // Chave no banco de dados não está unique!!! Por isso é necessário a verificação.
            $stmt = $this->pdoRagna->prepare("SELECT account_id FROM login WHERE userid = :userid");
            $stmt->execute(array(
                ':userid' => $username
            ));
            $objAccount = $stmt->fetchObject();
            
            // Já existe a conta no banco de dados pois retornou alguma coisa.
            if($objAccount !== false)
            {
                $this->halt(401, 'Nome de usuário já cadastrado.');
            }
            
            // Prepara a query a ser executada para inserir o usuário no banco de dados.
            // rAthena@8d47306
            $stmt = $this->pdoRagna->prepare("INSERT INTO login (userid, user_pass, sex, email) VALUES (:userid, :user_pass, :sex, :email);");
            $stmt->execute(array(
                ':userid' => $username,
                ':user_pass' => $userpass,
                ':sex' => $sex,
                ':email' => $email
            ));
            
            // Obtém o ultimo account_id lançado na tabela de login.
            $account_id = $this->pdoRagna->lastInsertId();

            // Fecha a conexão com o servidor do ragnarok.
            $this->pdoRagna = null;

            // Retorna o objeto de conta.
            return (object)array(
                'account_id' => $account_id, // Código da conta criada
                'userid' => $username,       // Nome de usuário criado
                'create_time' => time()      // Segundos desde 01/01/1970 até hora de retorno.
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
                    (ApiUnlimitedCount = 1
                        or (ApiUsedCount < ApiLimitCount AND date() <= ApiExpires))
            ');
            $stmt->execute(array(
                ':ApiKey' => $apiKey
            ));
            $bExists = ($stmt->rowCount() > 0);
            $this->pdoServer = null;

            return $bExists;
        }
        
        /**
         * Sobre-escrito para retornar uma mensagem de erro e hora da ocorrência do problema.
         *
         * @see \Slim\Slim::halt()
         *
         * @override
         */
        public function halt($status, $message = '')
        {
            parent::halt($status, json_encode((object)array(
                'message' => $message,
                'time' => time()
            )));
        }
    } // fim - class brAWebServer extends \Slim\Slim
} // fim - namespace brAWebServer
?>
