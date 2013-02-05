<?php

mb_internal_encoding('UTF-8');

define('DEBUG', false);
define('DIR_ROOT', dirname(dirname(dirname(__FILE__))));
define('DIR_PHP', DIR_ROOT.'\php');
define('DIR_LIBS', DIR_PHP.'\Libs');
define('DIR_LOGS', DIR_PHP.'\Logs');
define('DIR_TEST', DIR_PHP.'\Test');

require DIR_LIBS.'\Core\Basics.php';

spl_autoload_register(function($class){
	$path = DIR_LIBS.'\\'.$class.'.php';
	if (is_file($path)) {
		include ($path);
	}
});
