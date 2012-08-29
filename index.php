<?php

$url = (string) @$_GET['URL'];
if (!$url || ($url[0] != '/')) $url = "/{$url}";

try {
	require_once dirname(__FILE__).'/php/init.php';
	$router = new Core\Router(config()->routes);
	print $router->route($url);
}
catch (Exception $e) {
	print file_get_contents(dirname(__FILE__).'/php/App/Shared/View/500.html');
	if (DEBUG) print '<p>'.$e->getMessage().'</p>'.
	error_log((string)$e, E_USER_ERROR);
}
