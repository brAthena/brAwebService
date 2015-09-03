# brAWebService (brAthena)
## O Que é o brAWebService?
O **brAWebService** é um projeto brasileiro, desenvolvido por CarlosHenrq sob-licença LGPL 3.0, diretamente vinculado a organização **brAthena**, que tem por fim criar um serviço RESTful que permita a abertura de novas ferramentas e desenvolvimentos para o emulador. Desenvolvido em linguagem PHP com banco de dados SQLite, permite a fácil implementação pela própria comunidade em seus programas.

## Pré-requisitos

Para a execução do **brAWebService** é necessário um servidor de páginas (servidor http) que suporte uma versão do PHP 5.3+. *Não há restrições de sistemas operacionais, desde que possua os requisitos instalados.*

Durante o desenvolvimento do **brAWebService** foram utilizados:

* Servidor HTTP
    * Apache 2.2.x
        * mod_rewrite: habilitado.
* PHP 5.4+
    * Configurações: SimpleXmlReader e OpenSSL
    * Banco de dados: PDO, PDO_MYSQL, PDO_SQLITE
    * Framework: [Slim Framework](http://www.slimframework.com/)

Para versões PHP 5.3.x, você deve utilizar a v1.0.63-stable https://github.com/brAthena/brAwebService/releases/tag/v1.0.63-stable

Para versões PHP 5.2.x, não existe versões disponíveis.
    
## Instalação

*Comming soon...*

## Resolvendo Problemas

Se você está com problemas ao utilizar o serviço você primeiro deve verificar se está documentado o problema. Realize uma busca em nosso [Issue list](https://github.com/carloshenrq/brAWebService/issues) para saber se já resolvido ou como contornar a situação.

Se você não encontrar a solução do problema em nossa documentação, porque não criar um issue? ^.^

***Lembre-se: Aqui não é um fórum de suporte. Então se você deseja modificar o código fonte e este começar a dar problemas... este não é o lugar ideal para pedir suporte.
Sempre tenha isso em mente: O Suporte e correções serão realizados em cima do código fonte aqui desenvolvido. Se você modificar, não poderemos lhe ajudar muito. Tome cuidado.***

## Porque usar devo um webservice no meu servidor?

Você já se perguntou...
Porque eu tenho que usar um painel de controle via web para criar a minha ou até mesmo resetar a posição daquele personagem que ficou preso em um mapa com problema?
Bom o **brAWebService** pode ser uma solução para este problema ou ser o precursor da desgraça.
Com ele, você poderá adicionar ao seu atualizador uma funcionalidade para cadastro de novos usuários, alterações de senha, senha perdidas e diversas outras opções...

É um serviço para facilitar e permitir a criação de novas ferramentas para melhorar os servidores. 

## Links Úteis

Abaixo segue uma relação de links que podem ser uteis na implementação do *brAWebService*.

* [brAWebClient (PHP e C#)](https://github.com/carloshenrq/brawebclient)
* [brAthena](http://forum.brathena.org/)
* [Apache](https://www.apache.org/)
* [PHP](http://php.net/)
    * [Slim Framework](http://www.slimframework.com/)

