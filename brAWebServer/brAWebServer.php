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

            $this->add(new \Slim\Middleware\ContentTypes());
            
            // Executa as operações para verificação do apiKey ao banco de dados.
            // Tirei como base: https://gist.github.com/RodolfoSilva/1f438da56cb55c1eaea0 [carloshlfz, 10/03/2015]
            $this->hook('slim.before.router', function() use ($app) {
                
                // Caso esteja em modo manutenção, não permite que a requisição seja continuada.
                if($app->simpleXmlHnd->maintence === true)
                {
                    $app->sendResponse(503, 'Em manutenção. Tente mais tarde.');
                }
                else
                {
                    // ApiKey de acesso ao sistema.
                    $apiKey = $app->request()->get('apiKey');

                    // Se token não foi enviado para a aplicação.
                    if(is_null($apiKey) === true)
                    {
                        $app->sendResponse(400, 'Acesso negado. ApiKey de acesso não fornecido.');
                    }
                    else if($app->checkApiKey($apiKey) === false)
                    { // ApiKey inválido.
                        $app->sendResponse(401, 'Acesso negado. ApiKey inválida! Verifique por favor.');
                    }
                    else
                    {
                        // Remove a criptografia dos dados enviados.
                        $app->environment['slim.input'] = openssl_decrypt($app->environment['slim.input'],
                            $app->apiKeyInfo->ApiPassMethod, $app->getClientKey(), 0, '0000000000000000');
                        unset($app->environment['slim.request.form_hash']); // Deleta o hash inicial.
                    }
                }
            });

            // Adicionado método para registrar as rotas, callbacks e permissões de acesso. bra_Account_Put
            $this->registerRoute('put',     '/account/',                        'bra_Account_Put',              '10000000000000000000');
            $this->registerRoute('post',    '/account/login/',                  'bra_AccountLogin_Post',        '01000000000000000000');
            $this->registerRoute('post',    '/account/password/',               'bra_AccountChangePass_Post',   '01100000000000000000');
            $this->registerRoute('post',    '/account/email/',                  'bra_AccountChangeMail_Post',   '01010000000000000000');
            $this->registerRoute('post',    '/account/sex/',                    'bra_AccountChangeSex_Post',    '01001000000000000000');
            $this->registerRoute('post',    '/account/chars/',                  'bra_CharList_Post',            '01000100000000000000');
            $this->registerRoute('post',    '/account/chars/reset/posit/',      'bra_CharResetPosit_Post',      '01000010000000000000');
            $this->registerRoute('post',    '/account/chars/reset/appear/',     'bra_CharResetAppear_Post',     '01000001000000000000');

        } // fim - public function __construct()

        /**
         * Registra uma chamada para um callback para uma rota dependente de method.
         *
         * @param string $method Método para definir a rota. (GET, POST, PUT, DELETE)
         * @param string $route Método para a rota ser executada.
         * @param string $permission Permissões da APIKEY para execução do action e method.
         * @param callback $callback função a ser executada quando a rota for utilizada.
         *
         * @return void
         */
        public function registerRoute($method, $route, $callback, $permission)
        {
            $app = $this;
            $this->{$method}($route, function() use ($app, $callback, $permission){
                if(($app->apiKeyInfo->ApiPermission&$permission) <> $permission)
                {
                    $app->sendResponse(401, 'Esta ApiKey não pode realizar este tipo de operação.');
                }
                $callback($app);
            });
            return;
        }

        /**
         * Obtém a chave de criptografia do cliente.
         *
         * @return string
         */
        public function getClientKey()
        {
            return hash('md5',
                $this->simpleXmlHnd->openSslSettings->password.$this->apiKeyInfo->ApiKeyCreated);
        }

        /**
         * Obtém os campos que serão utilizados para uma requisição. Caso algum parametro não seja recebido,
         *  então retorna 400 com a mensagem de erro.
         *
         * @param ... Recebe os itens que serão verificados.
         *
         * @return object
         */
        public function getRequestFields()
        {
            $array2obj = array();

            foreach(func_get_args() as $field)
            {
                $field_ = $this->request()->post($field);
                if(is_null($field_) === true)
                {
                    $this->sendResponse(400, $msgNull. ' ('.implode(', ', func_get_args()).')');
                    return null;
                }
                $array2obj[$field] = $field_;
            }
            
            return (object)$array2obj;
        }

        /**
         * Reseta a aparência de um personagem que não esteja online.
         *
         * @param integer $account_id Conta do personagem que será resetado.
         * @param integer $char_id Código do char que será resetado.
         *
         * @return boolean
         */
        public function charResetAppear($account_id, $char_id)
        {
            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));

            $stmt = $this->pdoRagna->prepare("
                UPDATE
                    `char`
                SET
                    hair = 0,
                    hair_color = 0,
                    clothes_color = 0,
                    head_top = 0,
                    head_mid = 0,
                    head_bottom = 0
                WHERE
                    account_id = :account_id AND
                    char_id = :char_id AND
                    online = 0
            ");
            $stmt->execute(array(
                ':account_id' => $account_id,
                ':char_id' => $char_id
            ));

            $bAparenciaResetada = $stmt->rowCount() > 0;

            $this->pdoRagna = null;
            return $bAparenciaResetada;
        }

        /**
         * Reseta a posição de um personagem que não esteja online.
         *
         * @param integer $account_id Conta do personagem que será resetado.
         * @param integer $char_id Código do char que será resetado.
         *
         * @return boolean
         */
        public function charResetPosit($account_id, $char_id)
        {
            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));

            $stmt = $this->pdoRagna->prepare("
                UPDATE
                    `char`
                SET
                    last_map = save_map,
                    last_x = save_x,
                    last_y = save_y
                WHERE
                    account_id = :account_id AND
                    char_id = :char_id AND
                    online = 0
            ");
            $stmt->execute(array(
                ':account_id' => $account_id,
                ':char_id' => $char_id
            ));

            $bPosicaoResetada = $stmt->rowCount() > 0;

            $this->pdoRagna = null;
            return $bPosicaoResetada;
        }

        /**
         * Obtém a lista de personagens para a conta solicitada.
         *
         * @param integer $account_id
         *
         * @return array
         */
        public function charList($account_id)
        {
            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));
            
            $aChars = array();
            $stmt = $this->pdoRagna->prepare("
                SELECT
                    account_id,
                    char_id,
                    name,
                    char_num,
                    class as class_,
                    base_level,
                    job_level,
                    last_map,
                    last_x,
                    last_y,
                    save_map,
                    save_x,
                    save_y,
                    online
                FROM
                    `char`
                WHERE
                    account_id = :account_id
                ORDER BY
                    char_num ASC
            ");
            $stmt->execute(array(
                ':account_id' => $account_id
            ));
            
            $aChars = $stmt->fetchAll(\PDO::FETCH_OBJ);
            $this->pdoRagna = null;
            return $aChars;
        }
        
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
            $accountValidation = $this->simpleXmlHnd->accountValidation;

            if(!preg_match("/{$accountValidation->sex}/i", $sex))
                $this->sendResponse(400, 'Sexo de conta em formato incorreto!');

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
                    account_id = :account_id AND
                    group_id <= :max_group_id
            ");
            $stmt->execute(array(
                ':account_id' => $account_id,
                ':sex' => $sex,
                ':max_group_id' => $this->simpleXmlHnd->maxGroupId
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
            $accountValidation = $this->simpleXmlHnd->accountValidation;

            if(!preg_match("/{$accountValidation->email}/i", $old_email))
                $this->sendResponse(400, 'Endereço de email em formato inválido!');
            else if(!preg_match("/{$accountValidation->email}/i", $new_email))
                $this->sendResponse(400, 'Endereço de email em formato inválido!');
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
                    email = :old_email AND
                    group_id <= :max_group_id
            ");
            $stmt->execute(array(
                ':new_email' => $new_email,
                ':account_id' => $account_id,
                ':old_email' => $old_email,
                ':max_group_id' => $this->simpleXmlHnd->maxGroupId
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
            $accountValidation = $this->simpleXmlHnd->accountValidation;

            if(!preg_match("/{$accountValidation->userpass}/i", $old_userpass))
                $this->sendResponse(400, 'Senhas de usuário em formato inválido!');
            else if(!preg_match("/{$accountValidation->userpass}/i", $new_userpass))
                $this->sendResponse(400, 'Senhas de usuário em formato inválido!');
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
                    user_pass = :old_userpass AND
                    group_id <= :max_group_id");
            $stmt->execute(array(
                ':new_userpass' => $new_userpass,
                ':account_id' => $account_id,
                ':old_userpass' => $old_userpass,
                ':max_group_id' => $this->simpleXmlHnd->maxGroupId
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
            $accountValidation = $this->simpleXmlHnd->accountValidation;

            // Verifica se todos os dados recebidos estão dentro dos regex.
            if(!preg_match("/{$accountValidation->username}/i", $username))
                $this->sendResponse(400, 'Nome de usuário em formato inválido!');
            else if(!preg_match("/{$accountValidation->userpass}/i", $userpass))
                $this->sendResponse(400, 'Senha de usuário em formato inválido!');

            $account_id = -1;
            $pdoRagna = $this->simpleXmlHnd->PdoRagnaConnection->{'@attributes'};
            
            // Abre conexão com o mysql do ragnarok.
            $this->pdoRagna = new \PDO($pdoRagna->connectionString,
                $pdoRagna->user, $pdoRagna->pass, array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ));

            // Executa a consulta no banco de dados.
            $stmt = $this->pdoRagna->prepare("SELECT account_id, userid FROM login WHERE userid = :userid AND user_pass = :user_pass and group_id <= :max_group_id");
            $stmt->execute(array(
                ':userid' => $username,
                ':user_pass' => $userpass,
                ':max_group_id' => $this->simpleXmlHnd->maxGroupId
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
            $accountValidation = $this->simpleXmlHnd->accountValidation;

            // Verifica se todos os dados recebidos estão dentro dos regex.
            if(!preg_match("/{$accountValidation->username}/i", $username))
                $this->sendResponse(400, 'Nome de usuário em formato inválido!');
            else if(!preg_match("/{$accountValidation->userpass}/i", $userpass))
                $this->sendResponse(400, 'Senha de usuário em formato inválido!');
            else if(!preg_match("/{$accountValidation->sex}/i", $sex))
                $this->sendResponse(400, 'Sexo para conta inválido! Aceitos: M ou F');
            else if(!preg_match("/{$accountValidation->email}/i", $email))
                $this->sendResponse(400, 'Email de usuário em formato inválido');

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
                $this->sendResponse(401, 'Nome de usuário já cadastrado.');
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
                'username' => $username,       // Nome de usuário criado
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
         * Retorna a mensagem para que seja respondido nos conformes.
         *
         * @param integer $status Código de resposta.
         * @param string $message Mensagem de resposta.
         * @param $object Objeto json de resposta.
         *
         * @return void
         */
        public function sendResponse($status, $message, $object = -1)
        {
            $time = time();
            $object = json_encode($object);

            $this->halt($status, $this->returnString(json_encode((object)array(
                'code' => $status,
                'message' => $message,
                'messageHash' => hash('sha512', $message . $time),
                'object' => $object,
                'objectHash' => hash('sha512', $object . $time),
                'time' => $time
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
            return (($this->apiKeyInfo != null) ?
                openssl_encrypt($str, $this->apiKeyInfo->ApiPassMethod, $this->getClientKey(), 0, '0000000000000000'):
                    $str);
        }
    } // fim - class brAWebServer extends \Slim\Slim
} // fim - namespace brAWebServer
?>
