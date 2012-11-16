<?php

function getUrlRoot($get, $prefix='') {
		$uri  = urldecode($_SERVER['REQUEST_URI']);
		$self = $_SERVER['PHP_SELF'];

		if (!$uri) return false;

		$qm = strpos($uri, '?');
		if ($qm !== false) {
			$uri = substr($uri, 0, $qm);
		}

		if (!$get && $uri==$self) {
			$root = dirname($self);
			if ($root == '/' || $root == '\\') $root = '';
			return $root;
		}

		if (!(strlen($uri)-strlen($get))) {
			return '';
		}

		$url = substr($uri, 0, strlen($uri)-strlen($get));
		
		while (substr($url, -1) == '/') {
			$url = substr($url, 0, -1);
		}

		return $url;
}

define('DIR_CURRENT', str_replace('\\', '/', dirname(__FILE__)));
define('DIR_ROOT', dirname(DIR_CURRENT));
define('URL_ROOT', getUrlRoot($_GET['URL']));

require_once(DIR_ROOT.'/php/Libs/Core/ResourceCompiler.php');

use Core\ResourceCompiler;

function insert($name) {
	return ResourceCompiler::insert($name);
}

function border_radius($width) { 
	return "border-radius: {$width};\n\t-moz-border-radius: {$width};\n";
}

class CssCompiler extends ResourceCompiler {
	
	static protected function render_file($path) {
		$css = parent::render_file($path);
		$css = str_replace('url(/', 'url('.URL_ROOT.'/', $css);
		$css = str_replace('url("/', 'url("'.URL_ROOT.'/', $css);
		return $css;
	}

}

CssCompiler::handle($_GET['URL'], 'text/css');
