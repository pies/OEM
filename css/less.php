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

require DIR_CURRENT.'/lessc.inc.php';
require_once(DIR_ROOT.'/php/Libs/Core/ResourceCompiler.php');
require_once(DIR_ROOT.'/php/Libs/Core/View.php');

use Core\ResourceCompiler;

class LessCompiler extends ResourceCompiler {

	static public function url($arg) {
		return "url('".URL_ROOT.$arg[1]."')";
	}
	
	static protected function render_file($path) {
		$cache_path = $path.'-cache.css';
		if (!file_exists($cache_path) || filemtime($cache_path) < filemtime($path)) {
			$out = parent::render_file($path);
			$less = new lessc();
			$less->importDir = dirname($path);
			//$less->registerFunction('url', array('LessCompiler', 'url'));
			$css = $less->parse($out);
			$css = str_replace('url("/', 'url("'.URL_ROOT.'/', $css);
			
			file_put_contents($cache_path, $css);
		}
		return file_get_contents($cache_path);
	}

}

LessCompiler::handle($_GET['URL'], 'text/css');
/*
try {
	$less = new lessc(DIR_CURRENT . '/' . basename($_GET['URL']));
	print $less->parse();
} catch (exception $ex) {
	exit('lessc fatal error:<br />' . $ex->getMessage());
}*/