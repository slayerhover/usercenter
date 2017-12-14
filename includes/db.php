<?php
use Illuminate\Database\Capsule\Manager as Capsule;

require './vendor/autoload.php';

$dbconfig = [
  'driver'    => 'mysql',
  'read'	  => [	
				 'host'      => '127.0.0.1',
				],
  'write'	  => [	
				 'host'      => '127.0.0.1',
				],
  'database'  => 'ucenter',
  'username'  => 'root',
  'password'  => 'asdfasdf',
  'port'	  => '3306',
  'charset'   => 'utf8',
  'collation' => 'utf8_general_ci',
  'prefix'    => ''
];  

$capsule = new Capsule;

$capsule->addConnection($dbconfig);
$capsule->setAsGlobal();

$capsule->bootEloquent();