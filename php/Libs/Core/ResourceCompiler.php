<?php
namespace Core;

class ResourceCompiler {

	static protected function render_file($path) {
		ob_start();
		include $path;
		return ob_get_clean();
	}

	static public function insert($name) {
		$path = DIR_CURRENT."/{$name}";
		$header = "/*\n * Includes {$path}\n */";
		return "{$header}\n\n".static::render_file($path)."\n";
	}

	static protected function remove_comments($str) {
		$p1 = $p2 = 0;
		while ($p1 !== false && $p2 !== false) {
			$str = substr($str, 0, $p1).substr($str, $p2);
			$p1 = strpos($str, '/*');
			$p2 = strpos($str, '*/')+2;
		}
		$str = str_replace(array("\t", "\n", "}"), array('', '', "}\n"), $str);
		return $str;
	}

	static protected function server($index, $default=null) {
		return isset($_SERVER[$index])? $_SERVER[$index]: null;
	}

	static public function handle($url, $mime) {
		if (!$url || $url == 'index.php') die('No file selected.');

		$path = DIR_ROOT."/{$url}";

		if (!is_file($path)) die("File {$path} doesn't exist.");
		if (!is_readable($path)) die("File {$path} is not readable.");

		$header = "/*\n * Automatically generated from\n * {$path}\n * Do not edit.\n */\n\n";
		$content = static::post_process_output($header.static::render_file($path));

		$hash = md5($content);
		$if_none_match = static::server('If-None-Match');
		$if_mod_since = strtotime(static::server('If-Modified-Since'));

		if (preg_match("/{$hash}/", $if_none_match) || ($if_mod_since > filemtime($path))) {
			header('HTTP/1.1 304 Not Modified');
		}
		else {
			header("ETag: \"{$hash}\"");
			header("Accept-Ranges: bytes");
			header("Content-Length: ".strlen($content));
			header("Content-Type: {$mime}");

			header('Cache-Control: store');
			header('Pragma: cache');
			header('Last-Modified: '.date('r', filemtime($path)));
			//header('Expires: '.date('r', time()+24*60*60));

			echo $content;
		}
	}

	static protected function post_process_output($output) {
		return $output;
	}

}
