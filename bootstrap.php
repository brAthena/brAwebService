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

require_once dirname(__FILE__) . '/_dependences/autoload.php';

try
{
    spl_autoload_register(function($class) {
        $class_path = str_replace('\\', '/', dirname(__FILE__).'/'.$class.'.php');
        
        if(is_file($class_path) && file_exists($class_path))
        {
            require_once($class_path);
        }
    }, true);
}
catch(Exception $ex)
{
    echo $ex->getMessage();
    exit;
}

// Inicializa o serviço do server.
$service = new brAWebService\brAWebServer();
$service->run();
