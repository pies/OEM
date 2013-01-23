<?php

use Core\Framework;
use Core\View;
use Core\Email;
use Core\Str;
use Core\XMLConfig;
use Vendor\Markdown;

/* SHORTCUTS */

function config() {
	return Framework::Config();
}

function model($name=null) {
	return Framework::Model($name);
}

function content_type($name, $charset=false) {
	return Framework::ContentType($name, $charset);
}

function shorten($path) {
	return Framework::Shorten($path);
}

function form($data=array(),$errors=array(),$id_prefix='') {
	return new Html\Form($data, $errors, $id_prefix);
}

function render($file, $data=array(), $prefix=false) {
	if ($file[0] == '~') {
		$file = substr($file, 2);
		$path = DIR_SHARED."/View/{$file}.html";
	}
	else {
		$path = DIR_VIEWS."/{$file}.html";
	}

	return View::Render($path, $data, $prefix);
}

function email($to_name, $to_email, $subject, $body) {
	$config = config()->email;
	$email = new Email($config->name, $config->email);
	return $email->send($to_name, $to_email, $subject, $body);
}

function pct($num, $total) {
	return round(100*$num/$total).'%';
}

function to_bytes($str) {
    $str = strtoupper(trim($str));
	
	if (substr($str, -1) == 'B') {
		$str = substr($str, 0, -1);
	}
	
	switch (substr($str, -1)) {
		case 'K': $mul = 1024;       $str = substr($str, 0, -1); break;
		case 'M': $mul = 1048576;    $str = substr($str, 0, -1); break;
		case 'G': $mul = 1073741824; $str = substr($str, 0, -1); break;
		default:  $mul = 1;
	}
	
	return (int) ($str * $mul);
}

/**
 * Shortcut for new ArrObject()

 * @param array $data
 * @return Misc\ArrObject
 */
function arr(Array $data=array()) {
	return new Misc\ArrObject($data);
}

/**
 * Shortcut for new Str()
 * 
 * @param string $str
 * @return Str
 */
function str($str='') {
	return new Core\StrObject($str);
}

function is_readable_file($path) {
	return is_file($path) && is_readable($path);
}

function request_origin() {
	if (empty($_SERVER['REMOTE_ADDR'])) return 0;
	return sprintf("%d", ip2long($_SERVER['REMOTE_ADDR']));
}

function data_uri($contents, $mime) {
  return "data:{$mime};base64,".base64_encode($contents);
}

function _GET($key=null, $default=null) {
	if (!$key) return $_GET;
	return isset($_GET[$key])? $_GET[$key]: $default;
}

function _POST($key=null, $default=null) {
	if (!$key) return $_POST;
	return isset($_POST[$key])? $_POST[$key]: $default;
}

function _REQUEST($key=null, $default=null) {
	if (!$key) return $_REQUEST;
	return isset($_REQUEST[$key])? $_REQUEST[$key]: $default;
}

function _SERVER($key=null, $default=null) {
	if (!$key) return $_SERVER;
	return isset($_SERVER[$key])? $_SERVER[$key]: $default;
}

function _COOKIE($key=null, $default=null) {
	if (!$key) return $_COOKIE;
	return isset($_COOKIE[$key])? $_COOKIE[$key]: $default;
}

function _FILES($key=null, $default=null) {
	if (!$key) return $_FILES;
	return isset($_FILES[$key])? $_FILES[$key]: $default;
}

function markdown($text) {
	# Setup static parser variable.
	static $parser;
	if (!isset($parser)) {
		$parser = new Markdown;
	}

	# Transform text using parser.
	return $parser->transform($text);
}

function markdown_file($path) {
	return markdown(file_get_contents($path));
}

/* DEBUG TOOLS */

function trim_path($path) {
	return str_replace(DIR_ROOT, '', str_replace('\\', '/', $path));
}

function pretty_path($path, $line=false) {
	$path = str_replace('\\', '/', $path);
	$short_path = str_replace(DIR_ROOT, '', $path);
	$file = basename($path);
	return str_replace($file, "<b>{$file}</b>", $short_path).($line? ":{$line}": '');
}

// Returns an array of execution path file locations
function place() {
	$places = array();
	$first = true;
	foreach(debug_backtrace() as $trace) {
		if ($first) { $first = false; continue; }
		if (empty($trace['file'])) continue;
		$file = substr(str_replace(DIR_ROOT, '', str_replace('\\', '/', $trace['file'])), 1);
		$places[] = pretty_path($trace['file'], $trace['line']);
	}
	return $places;
}

/**
 * Pretty-prints a variable.
 * 
 * @param mixed $var Variable to print
 */
function debug($var) {
	$place = place();
	$title = join(' &larr; ', $place);
	$vars = func_get_args();
	foreach ($vars as $var) {
		output(str_replace(array('&', '<', '>', "\'"), array('&amp;', '&lt;', '&gt;', "'"), var_export($var, true)), $title);
	}
}

/**
 * Prints a CSS-independent text frame.
 * 
 * @param string $msg Main message
 * @param string $title Message title
 * @param string $color Frame color as CSS hex (i.e. #F80)
 * @param string $tag HTML tag to use for the content frame
 */
function output($msg, $title=false, $color='#F88', $tag='pre') {
	$plain = !ini_get('html_errors');
	
	if ($plain) {
		$decode = function($str) {
			return html_entity_decode($str, ENT_NOQUOTES, 'UTF-8');
		};
		$dot = $decode('&rsaquo;');
		$title = $decode(strip_tags($title));
		$msg = $decode($msg);
		print "\n{$dot}{$dot}{$dot} {$title}\n{$msg}\n{$dot}{$dot}{$dot}\n\n";
	}
	else {
		$title = $title? "<span style=\"font-size:12px;background-color:#FFC;color:#555\">{$title}</span>\n": "";
		$style = <<<CSS
background-color:#FAFAF4;
color:#000;
padding:.4em .5em;
border:2px solid {$color};
margin-bottom:1em;
font-size:14px;
font-family:Consolas,monospace;
line-height:120%;
CSS;
		$tag_html = ($tag == 'pre')? "pre style=\"{$style}\"": $tag;
		print "<{$tag_html}>{$title}{$msg}</{$tag}>";
	}
}


// rarioj at gmail dot com - http://www.php.net/manual/en/function.get-object-vars.php#93416
// General purpose functions to convert an array (multidimensional) to object, and vice versa:
// From array to object.
function a2o($data) {
    return is_array($data) ? (object) array_map(__FUNCTION__, $data) : $data;
}
// From object to array.
function o2a($data) {
    if (is_object($data)) $data = get_object_vars($data);
    return is_array($data) ? array_map(__FUNCTION__, $data) : $data;
}
