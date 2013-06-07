Installation
===

### Requirements


First you'll need to install the required dependencies. Which is to say : [MsgPack](http://msgpack.org/).

Install MsgPack from git:
<pre>git clone https://github.com/msgpack/msgpack-php.git
cd msgpack-php
phpize
./configure && make && make install</pre>

Install MsgPack from PEAR:
<pre>pecl channel-discover php-msgpack.googlecode.com/svn/pecl
pecl install msgpack/msgpack-beta</pre>

### PHP Rexster Client

<pre>git clone https://github.com/PommeVerte/rexpro-php.git</pre>

Error Handling
===

The PHP Client does not throw Exceptions. It was built with the goal of being wrapped into a PHP framework and therefore fails silently (you can still get errors by checking method return values).

Examples
===

You can find more information by reading the API. 

Here are a few basic usages.


Example 1:

<pre>$db = new Connection;
//you can set $db->timeout = 0.5; if you wish
$message = $db->open('localhost:8184','tinkergraph',null,null);
$db->script = 'g.v(2)';
$result = $db->runScript();</pre>

Example 2 (with bindings):

<pre>$db = new Connection;
$message = $db->open('localhost:8184','tinkergraph',null,null);

$db->script = 'g.v(CUSTO_BINDING)';
$db->bindValue('CUSTO_BINDING',2);
$result = $db->runScript();</pre>

Example 3 (sessionless):

<pre>$db = new Connection;
$message = $db->open('localhost:8184');
$db->script = 'g.v(2).map()';
$db->graph = 'tinkergraph'; //need to provide graph
$result = $db->runScript(false); </pre>

Example 4 (transaction):

<pre>$db = new Connection;
$message = $db->open('localhost:8184','neo4jsample',null,null);

$db->script = 'g.V';
$db->runScript();
  	
$db->transactionStart();

$db->script = 'g.addVertex([name:"michael"])';
$result = $db->runScript();

$db->transactionStop(true);//accept commit of changes. set to false if you wish to cancel changes</pre>

