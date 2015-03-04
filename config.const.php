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

// Define o algoritmo de hash padrão. (Padrão: sha512)
// Aqui pode ser qualquer valor encontrado em: http://php.net/manual/en/function.hash-algos.php
// - APIKEY, HASH de requsição. Etc...
DEFINE('BRAWB_HASH_ALGO', 'sha512', false);

?>
