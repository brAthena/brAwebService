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
    class brAEnvironment extends \Slim\Environment
    {
        /**
         * Construtor para o environment do serviço.
         */
        private function __construct(brAWebServer $app)
        {
            $env = array();

            // Obtem as configurações de OpenSSL para tratamento dos dados recebidos.
            $openssl = $app->simpleXmlHnd->openSslSettings;

            //The HTTP request method
            $env['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

            //The IP
            $env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

            // Server params
            $scriptName = $_SERVER['SCRIPT_NAME']; // <-- "/foo/index.php"
            $requestUri = $_SERVER['REQUEST_URI']; // <-- "/foo/bar?test=abc" or "/foo/index.php/bar?test=abc"
            $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING']: ''; // <-- "test=abc" or ""

            // Physical path
            if (strpos($requestUri, $scriptName) !== false) {
                $physicalPath = $scriptName; // <-- Without rewriting
            } else {
                $physicalPath = str_replace('\\', '', dirname($scriptName)); // <-- With rewriting
            }
            $env['SCRIPT_NAME'] = rtrim($physicalPath, '/'); // <-- Remove trailing slashes

            // Virtual path
            $env['PATH_INFO'] = $requestUri;
            if (substr($requestUri, 0, strlen($physicalPath)) == $physicalPath) {
                $env['PATH_INFO'] = substr($requestUri, strlen($physicalPath)); // <-- Remove physical path
            }
            $env['PATH_INFO'] = str_replace('?' . $queryString, '', $env['PATH_INFO']); // <-- Remove query string
            $env['PATH_INFO'] = '/' . ltrim($env['PATH_INFO'], '/'); // <-- Ensure leading slash

            // Query string (without leading "?")
            $env['QUERY_STRING'] = ((strlen($queryString) > 0) ? openssl_decrypt($queryString,
                                                            $openssl->method, $openssl->password, 0, $openssl->iv):'');

            //Name of server host that is running the script
            $env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

            //Number of server port that is running the script
            //Fixes: https://github.com/slimphp/Slim/issues/962
            $env['SERVER_PORT'] = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;

            //HTTP request headers (retains HTTP_ prefix to match $_SERVER)
            $headers = \Slim\Http\Headers::extract($_SERVER);
            foreach ($headers as $key => $value) {
                $env[$key] = $value;
            }

            //Is the application running under HTTPS or HTTP protocol?
            $env['slim.url_scheme'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

            //Input stream (readable one time only; not available for multipart/form-data requests)
            $rawInput = @file_get_contents('php://input');
            if (!$rawInput) {
                $rawInput = '';
            }
            $env['slim.input'] = $rawInput;

            //Error stream
            $env['slim.errors'] = @fopen('php://stderr', 'w');

            $this->properties = $env;
        }
        
        /**
         * @see \Slim\Environment
         */
        public static function _getInstance(brAWebServer $app)
        {
            if (is_null(self::$environment) || $refresh) {
                self::$environment = new self($app);
            }

            return self::$environment;
        }
    } // fim - class brAEnvironment extends \Slim\Slim
} // fim - namespace brAWebServer
?>
