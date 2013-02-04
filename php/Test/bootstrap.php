<?php

set_include_path(join(PATH_SEPARATOR, array(
	get_include_path(),
	
)));

define('OEM_LIBS_PATH', dirname(dirname(__FILE__)).'\Libs');

spl_autoload_register(function($class){
	$path = OEM_LIBS_PATH.'\\'.$class.'.php';
	if (is_file($path)) {
		include ($path);
	}
});
