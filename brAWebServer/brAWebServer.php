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
         * Dados de criptografia para o APIKEY.
         * @var object
         */
        public $apiKeyInfo;
        
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
                return brAEnvironment::_getInstance($app);
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
                    else
                    {
                        // @todo: Criptografia do slim.input
                    }
                }
            });

            // Adiciona o método para put de crição de contas.
            $this->put('/account/', function() use ($app) {
                if(($app->apiKeyInfo->ApiPermission&'10000000000000000000') <> '10000000000000000000')
                {
                    $app->halt(401, 'Esta chave de acesso não possui permissões para esta ação.');
                }
                bra_Account_Put($app);
            });

            // Adiciona método para POST de realizar login.
            $this->post('/account/login/', function() use ($app) {
                if(($app->apiKeyInfo->ApiPermission&'01000000000000000000') <> '01000000000000000000')
                {
                    $app->halt(401, 'Esta chave de acesso não possui permissões para esta ação.');
                }
                bra_AccountLogin_Post($app);
            });
            
            // Adiciona método para POST de alteração de senha.
            $this->post('/account/password/', function() use ($app) {
                if(($app->apiKeyInfo->ApiPermission&'01100000000000000000') <> '01100000000000000000')
                {
                    $app->halt(401, 'Esta chave de acesso não possui permissões para esta ação.');
                }
                bra_AccountChangePass_Post($app);
            });
            
            // Adiciona método para POST de alteração de email.
            $this->post('/account/email/', function() use ($app) {
                if(($app->apiKeyInfo->ApiPermission&'01010000000000000000') <> '01010000000000000000')
                {
                    $app->halt(401, 'Esta chave de acesso não possui permissões para esta ação.');
                }
                bra_AccountChangeMail_Post($app);
            });
            
            // Adiciona método para POST de alteração de sexo.
            $this->post('/account/sex/', function() use ($app) {
                if(($app->apiKeyInfo->ApiPermission&'01001000000000000000') <> '01001000000000000000')
                {
                    $app->halt(401, 'Esta chave de acesso não possui permissões para esta ação.');
                }
                bra_AccountChangeSex_Post($app);
            });
            
            // Adiciona método para GET de listagem de personagens.
            $this->get('/account/chars/', function() use ($app) {
                if(($app->apiKeyInfo->ApiPermission&'01000100000000000000') <> '01000100000000000000')
                {
                    $app->halt(401, 'Esta chave de acesso não possui permissões para esta ação.');
                }
                bra_CharList_Get($app);
            });
            
            // Adiciona método para POST de alteração de posição.
            $this->post('/account/chars/reset/posit/', function() use ($app) {
                if(($app->apiKeyInfo->ApiPermission&'01000010000000000000') <> '01000010000000000000')
                {
                    $app->halt(401, 'Esta chave de acesso não possui permissões para esta ação.');
                }
                bra_CharResetPosit_Post($app);
            });
            
            // Adiciona método para POST de alteração de posição.
            $this->post('/account/chars/reset/appear/', function() use ($app) {
                if(($app->apiKeyInfo->ApiPermission&'01000001000000000000') <> '01000001000000000000')
                {
                    $app->halt(401, 'Esta chave de acesso não possui permissões para esta ação.');
                }
                bra_CharResetAppear_Post($app);
            });

        } // fim - public function __construct()
        
        /**
         * Realiza alteração de sexo da conta do usuário.
         *
         * @param integer $account_id
         * @param string $sex
         *
         * @return boolean
         */
        public function changeSex($account_id, $sex)
        {
            // Obtém as validações regex para este método.
            $createAccountValidation = $this->simpleXmlHnd->createAccountValidation;

            if(!preg_match("/{$createAccountValidation->sex}/i", $old_email))
                $this->halt(400, 'Sexo de conta em formato incorreto!');

            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));

            $stmt = $this->pdoRagna->prepare("
                UPDATE
                    login
                SET
                    sex = :sex
                WHERE
                    account_id = :account_id
            ");
            $stmt->execute(array(
                ':new_email' => $new_email,
                ':account_id' => $account_id,
                ':old_email' => $old_email
            ));

            $bChanged = $stmt->rowCount() > 0;

            $this->pdoRagna = null;

            return $bChanged;
        }
        
        /**
         * Realiza alteração de email da conta do usuário.
         *
         * @param integer $account_id
         * @param string $old_email
         * @param string $new_email
         *
         * @return boolean
         */
        public function changeMail($account_id, $old_email, $new_email)
        {
            // Obtém as validações regex para este método.
            $createAccountValidation = $this->simpleXmlHnd->createAccountValidation;

            if(!preg_match("/{$createAccountValidation->email}/i", $old_email))
                $this->halt(400, 'Endereço de email em formato inválido!');
            else if(!preg_match("/{$createAccountValidation->email}/i", $new_email))
                $this->halt(400, 'Endereço de email em formato inválido!');
            else if($old_email == $new_email)
                return false;

            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));

            $stmt = $this->pdoRagna->prepare("
                UPDATE
                    login
                SET
                    email = :new_email
                WHERE
                    account_id = :account_id AND
                    email = :old_email
            ");
            $stmt->execute(array(
                ':new_email' => $new_email,
                ':account_id' => $account_id,
                ':old_email' => $old_email
            ));

            $bChanged = $stmt->rowCount() > 0;

            $this->pdoRagna = null;

            return $bChanged;
        }
        
        /**
         * Tenta realizar a alteração de senha no usuário identificado.
         *
         * @param integer $account_id Código da conta que será alterado.
         * @param string $old_userpass Senha antiga da conta.
         * @param string $new_userpass Senha nova da conta.
         */
        public function changePass($account_id, $old_userpass, $new_userpass)
        {
            // Obtém as validações regex para este método.
            $createAccountValidation = $this->simpleXmlHnd->createAccountValidation;

            if(!preg_match("/{$createAccountValidation->userpass}/i", $old_userpass))
                $this->halt(400, 'Senhas de usuário em formato inválido!');
            else if(!preg_match("/{$createAccountValidation->userpass}/i", $new_userpass))
                $this->halt(400, 'Senhas de usuário em formato inválido!');
            else if($old_userpass == $new_userpass)
                return false;

            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));

            $stmt = $this->pdoRagna->prepare("
                UPDATE
                    login
                SET
                    user_pass = :new_userpass
                WHERE
                    account_id = :account_id AND
                    user_pass = :old_userpass");
            $stmt->execute(array(
                ':new_userpass' => $new_userpass,
                ':account_id' => $account_id,
                ':old_userpass' => $old_userpass
            ));

            $bChanged = $stmt->rowCount() > 0;

            $this->pdoRagna = null;

            return $bChanged;
        }

        /**
         * Tenta realizar o login na conta indicada.
         *
         * @param string $username Nome do usuário a realizar login. [Padrão: ^([a-z0-9]{4,24})$]
         * @param string $userpass Senha de usuário. [Padrão: ^([a-f0-9]{32})$]
         *
         * @return object
         */
        public function login($username, $userpass)
        {
            // Obtém as validações regex para este método.
            $createAccountValidation = $this->simpleXmlHnd->createAccountValidation;

            // Verifica se todos os dados recebidos estão dentro dos regex.
            if(!preg_match("/{$createAccountValidation->username}/i", $username))
                $this->halt(400, 'Nome de usuário em formato inválido!');
            else if(!preg_match("/{$createAccountValidation->userpass}/i", $userpass))
                $this->halt(400, 'Senha de usuário em formato inválido!');

            $account_id = -1;
            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));

            // Executa a consulta no banco de dados.
            $stmt = $this->pdoRagna->prepare("SELECT account_id, userid FROM login WHERE userid = :userid AND user_pass = :user_pass");
            $stmt->execute(array(
                ':userid' => $username,
                ':user_pass' => $userpass
            ));
            // Retorna os dados para verificação.
            $obj = $stmt->fetchObject();

            // Não encontrou o nome de usuário.
            if($obj === false)
            {
                return false;
            }

            // Obtém os dados da conta logada.
            $account_id = $obj->account_id;
            $username = $obj->userid;

            // Fecha a conexão com o servidor do ragnarok.
            $this->pdoRagna = null;

            return (object)array(
                'account_id' => $account_id,
                'username' => $username,
                'loginTime' => time()
            );
        }

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

            // Se a chave existir, então carregará em memória os dados para criptografia da mesma.
            if(($bExists = ($stmt->rowCount() > 0)) === true)
            {
                $stmt = $this->pdoServer->prepare('
                    SELECT
                        *
                    FROM
                        brawbkeys
                    WHERE
                        ApiKey = :ApiKey
                ');
                $stmt->execute(array(
                    ':ApiKey' => $apiKey
                ));
                // Obtém os dados e joga num atributo da classe.
                $this->apiKeyInfo = $stmt->fetchObject();
            }

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
            parent::halt($status, $this->returnString(json_encode((object)array(
                'code' => $status,
                'message' => $message,
                'time' => time()
            ))));
        }

        /**
         * Retorna a string criptografada.
         *
         * @param string $str
         *
         * @return string
         */
        public function returnString($str)
        {
            return $str;
        }
    } // fim - class brAWebServer extends \Slim\Slim
} // fim - namespace brAWebServer
?>
