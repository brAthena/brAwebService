<?php
/**
 *  brAWS - brAthena Webservice for Ragnarok Emulators
 *  Copyright (C) 2015  brAthena, CHLFZ
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Route
{
    /**
     * Middleware para definição das rotas.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callback $next
     *
     * @return
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        // Verifica os dados de retorno para status.
        if(BRAWS_SERVER_STATUS)
        {
            // Define a rota para obter os status do servidor.
            brAWSApp::getInstance()->get('/', function(ServerRequestInterface $request, ResponseInterface $response, $args) {

                // Obtém o cache criado para os status do servidor, não é necessário tabela quando se existe
                //  o servidor de cache para dados.
                $servers = Cache::get('BRAWS_SERVER_STATUS', function() {
                    global $_BRAWS_SERVERS;

                    $tmp = [];
                    // Varre os servidores no vetor de servers para realizar o ping na porta e retorna o status.
                    foreach($_BRAWS_SERVERS as $server)
                    {
                        // Abre a porta de conexão com o servidor do jogador.
                        $map    = false;
                        $char   = false;
                        $login  = false;

                        try { $map    = @fsockopen($server['map']['address'], $server['map']['port']); }
                        catch(\Exception $ex) {  }

                        try { $char   = @fsockopen($server['char']['address'], $server['map']['port']); }
                        catch(\Exception $ex) {  }

                        try { $login  = @fsockopen($server['login']['address'], $server['map']['port']); }
                        catch(\Exception $ex) {  }


                        $tmp[] = [
                            'name'      => $server['name'],
                            'map'       => $map !== false,
                            'char'      => $char !== false,
                            'login'     => $login !== false,
                        ];

                        if($map !== false) fclose($map);
                        if($char !== false) fclose($char);
                        if($login !== false) fclose($login);
                    }

                    // Retorna os dados para o cache definido.
                    return [
                        'servers' => $tmp,
                        '_cacheInit'    => time(),
                        '_cacheEnd'     => time() + BRAWS_MEMCACHE_EXPIRE,
                    ];
                });

                // Responde ao request com os dados de retorno.
                $response->withJson(array_merge($servers, [
                    '_now' => time(),
                ]), 200);
            });
        } /* end - if(BRAWS_SERVER_STATUS) */

        // Chama o próximo middleware.
        return $next($request, $response);
    }
}
