<?php
define('DIR_CURRENT', str_replace('\\', '/', dirname(__FILE__)));
define('DIR_ROOT', dirname(DIR_CURRENT));

require_once(DIR_ROOT.'/php/Libs/Core/ResourceCompiler.php');

use Core\ResourceCompiler;

function insert($name) {
	return ResourceCompiler::insert($name);
}

function border_radius($width) { 
	return "border-radius: {$width};\n\t-moz-border-radius: {$width};\n";
}

ResourceCompiler::handle($_GET['URL'], 'text/css');
