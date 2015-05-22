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

namespace brAWebService;

/**
 * Classe para criptografia.
 */
class MCrypt
{
    /**
     * Senha de criptografia.
     * @var string
     */
    private $key;

    /**
     * Chave iv de criptografia.
     * @var string
     */
    private $iv;

    /**
     * Cifra de criptografia.
     * @var string
     */
    private $cipher;

    /**
     * Modo de criptografia.
     * @var string
     */
    private $mode;
    
    /**
     * Construtor para a classe de criptografia.
     *
     * @param string $key Senha de criptografia.
     * @param string $iv IV de criptografia.
     * @param string $cipher Cifra para criptografia.
     * @param string $mode Modo para criptografia.
     */
    public function __construct($key, $iv, $cipher = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC)
    {
        $this->key = $key;
        $this->iv = $iv;
        $this->cipher = $cipher;
        $this->mode = $mode;
        
        $this->checkKeyAndIv();
    }
    
    /**
     * Criptografa o texto plano enviado.
     *
     * @param string $plainText Texto plano.
     *
     * @return string Retorna o texto criptografado em base64.
     */
    public function encrypt($plainText)
    {
        return base64_encode(mcrypt_encrypt($this->cipher, base64_decode($this->key), $plainText, $this->mode,
                                                                                            base64_decode($this->iv)));
    }
    
    /**
     * Decriptografa o texto enviado.
     *
     * @param string $plainText Texto criptografado em base64.
     *
     * @return string Retorna o texto decriptografado.
     */
    public function decrypt($plainText)
    {
        return rtrim(mcrypt_decrypt($this->cipher, base64_decode($this->key), base64_decode($plainText), $this->mode,
                                base64_decode($this->iv)), "\0");
    }
    
    /**
     * Testa os tamanhos para chave e iv informados de acordo com a cifra.
     * @throw \Exception Caso a chave ou iv não estejam de acordo.
     */
    private function checkKeyAndIv()
    {
        $key = base64_decode($this->key);
        $iv = base64_decode($this->iv);
        
        // Testa os tamanhos da chave.
        if(self::getKeySize($this->cipher, $this->mode) !== strlen($key)
            || self::getIvSize($this->cipher, $this->mode) !== strlen($iv))
        {
            throw new \Exception('O Tamanho da senha/iv informados não estão de acordo com os tamanhos reais.');
        }
    }
    
    /**
     * Cria uma nova chave IV para a cifra e o modo escolhido.
     *
     * @param string $cipher Cifra para criptografia.
     * @param string $mode Modo para criptografia.
     *
     * @return string IV em base 64 para nova chave.
     */
    public static function createIv($cipher = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC, $source = MCRYPT_RAND)
    {
        return base64_encode(mcrypt_create_iv(self::getIvSize($cipher, $mode), $source));
    }

    /**
     * Cria uma nova senha para criptografia.
     *
     * @param string $cipher Cifra para criptografia.
     * @param string $mode Modo para criptografia.
     *
     * @return string Senha de criptografia.
     */
    public static function createKey($cipher = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC)
    {
        $keySize = self::getKeySize($cipher, $mode);
        $keyReturn = '';
        
        while(($keySize--) > 0) $keyReturn .= chr( rand(0, 255) );
        
        return base64_encode($keyReturn);
    }

    /**
     * Obtém o tamanho da senha que pode ser usado para o modo de criptografia.
     *
     * @param string $cipher Cifra para criptografia.
     * @param string $mode Modo para criptografia.
     *
     * @return integer Tamanho para a senha.
     */
    private static function getKeySize($cipher, $mode)
    {
        return mcrypt_get_key_size($cipher, $mode);
    }

    /**
     * Obtém o tamanho da chave IV que será criada.
     *
     * @param string $cipher Cifra para criptografia.
     * @param string $mode Modo para criptografia.
     *
     * @return integer Tamanho para a chave IV.
     */
    private static function getIvSize($cipher, $mode)
    {
        return mcrypt_get_iv_size($cipher, $mode);
    }
}
