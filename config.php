<?php

$db_conf = array(
	'host'		=> 'localhost',
	'user'		=> 'root',
	'pass'		=> '',
	'db'		=> 'mvc',
	'charset'	=> 'utf8',
	'prefix'	=> ''
);

define("STATUS_NO_PAGE", 404);
define("STATUS_DENY", 403);
define("STATUS_REDIRECT", 301);
define("STATUS_OK", 200);

error_reporting(E_ALL);
ini_set('display_errors', 1);
