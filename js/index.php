<?php
define('DIR_CURRENT', str_replace('\\', '/', dirname(__FILE__)));
define('DIR_ROOT', dirname(DIR_CURRENT));

require_once(DIR_ROOT.'/php/Libs/Core/Basics.php');
require_once(DIR_ROOT.'/php/Libs/Core/Framework.php');
require_once(DIR_ROOT.'/php/Libs/Core/ResourceCompiler.php');

define('URL_ROOT', Core\Framework::UrlRoot());

use Core\ResourceCompiler;

function insert($name) {
	return ResourceCompiler::insert($name);
}

class JavascriptCompiler extends ResourceCompiler {
/*
	protected static function post_process_output($output) {
		require_once(DIR_ROOT.'/php/libs/vendor/JSMin/jsmin.php');
		return JSMin::minify($output);
	}
*/
}
/*
function debug($var) {
	print '<pre>';
	//debug_print_backtrace();
	print_r($var);
	print '</pre>';
}
*/
JavascriptCompiler::handle($_GET['URL'], 'application/javascript');
