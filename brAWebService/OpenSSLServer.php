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

use Slim;

/**
 * Classe para execução do Webserver local.
 * @final
 */
final class OpenSSLServer
{
    /**
     * Resource para a chave privada obtida.
     *
     * @return object
     */
    private $privateKeyRes;

    /**
     * Construtor para o objeto do OpenSSL.
     *
     * @param string $privateKey Chave privada para criptografia.
     * @param string $passphrase Senha da chave privada.
     * @param string $x509 Certificado digital.
     */
    public function __construct($privateKey, $passphrase, $x509)
    {
        // Obtém informações sobre a chave privada.
        $pk = openssl_pkey_get_private($privateKey, $passphrase);
        
        /*// Verifica se a chave privada pertence ao certificado indicado.
        if(openssl_x509_check_private_key($x509, $pk) === false)
        {
            throw new \Exception('O Certificado digital não pode ser validado contra a chave privada.');
        }
        
        $this->privateKeyRes = $pk;*/
    }

    /**
     * Decriptografa o conteudo com a chave privada. (Deve ser criptografado com a publica)
     *
     * @param string $crypted Conteudo criptografado em base64.
     *
     * @return string Conteudo decriptografado.
     */
    public function decrypt($crypted)
    {
        $plainText = '';
        
        if(openssl_private_decrypt(base64_decode($crypted), $plainText, $this->privateKeyRes) === false)
        {
            throw new \Exception('Impossivel decriptografar o conteudo com a chave privada.');
        }
        
        return $plainText;
    }

    /**
     * Criptografa o texto com a chave privada.
     *
     * @param string $plainText Texto a ser criptografado.
     * @param string $method Método para geração do digest.
     *
     * @param array Array com informações sobre o texto criptografado, assinatura e digest.
     */
    public function encrypt($plainText, $method = 'sha512')
    {
        $crypted = $sign = '';

        if(openssl_private_encrypt($plainText, $crypted, $this->privateKeyRes) === false)
        {
            throw new \Exception('Impossivel criptografar o conteudo enviado com a chave privada.');
        }
        else if(openssl_sign($plainText, $sign, $this->privateKeyRes) === false)
        {
            throw new \Exception('Impossivel criptografar o conteudo enviado com a chave privada.');
        }
        
        return [
            'crypted' => base64_encode($crypted),
            'signature' => base64_encode($sign),
            'digest' => openssl_digest($plainText, $method)
        ];
    }

    /**
     * Cria um novo certificado digital (x509) para ser utilizado.
     *
     * @param string $passphrase Senha para geração do certificado.
     * 
     * @return array Informações do certificado digital a ser utilizado.
     */
    public static function newCertificate($passphrase = '')
    {
        // Realiza a instância dos retornos.
        $aReturn = [
            'privateKey' => '',
            'passphrase' => $passphrase,
            'x509' => ''
        ];
        
        $kPair = self::generateKeyPair();
        $csr = self::generateCsr([], $kPair);
        $cert = self::signCsr($csr, openssl_pkey_get_private($kPair, $aReturn['passphrase']), 365);
        
        openssl_pkey_export($kPair, $aReturn['privateKey'], $aReturn['passphrase']);
        
        $aReturn['x509'] = self::export_x509($cert);
        
        return $aReturn;
    }

    /**
     * Exporta o certificado x509.
     *
     * @param object $x509 Cerificado digital a ser exportado.
     * @param boolean $notext The optional parameter notext affects the verbosity of the output; if it is FALSE,
     *                        then additional human-readable information is included in the output.
     *                        The default value of notext is TRUE.
     *
     * @return string Cerificado x509 exportado.
     */
    private static function export_x509($x509, $notext = true)
    {
        $output = '';
        @openssl_x509_export($x509, $output, $notext);
        return $output;
    }

    /**
     * Assina um certificado digital gerando um certificado x509 atraves do csr criado.
     *
     * @param string CSR para ser gerado o x509.
     * @param mixed $privateKey Chave privada para geração do certificado.
     * @param integer $days Dias de validade para o certificado.
     *
     * @return object Returns an x509 certificate resource on success, FALSE on failure.
     */
    private static function signCsr($csr, $privateKey, $days = 365)
    {
        return openssl_csr_sign($csr, null, $privateKey, $days);
    }
    
    /**
     * Gera um novo CSR para um par de chaves.
     *
     * @param array $dn Configurações para o CSR.
     * @param array $keyPair Par de chaves.
     *
     * @return string CSR para ser salvo.
     */
    private static function generateCsr(array $dn = array(), &$keyPair)
    {
        return openssl_csr_new($dn, $keyPair);
    }

    /**
     * Gera um recurso para chave publica e privada.
     *
     * @param array $configargs Configurações para geração da chave.
     * @return object
     */
    private static function generateKeyPair(array $configargs = array())
    {
        return openssl_pkey_new($configargs);
    }
}


