<?php
namespace Core;

use Core\Str;

assert("function_exists('is_readable_file')");

class Router {
	
	private $url = '';
	private $routes = array();
	private $previous_routes = array();

	public function __construct($routes=array()) {
		$this->routes = $routes;
	}

	public function route($original_url, $default_controller='default', $default_action='index') {
		$this->url = $original_url;
		
		$url = $this->resolve($original_url);
		$request = $this->request($url, $default_controller, $default_action);

		try {
			$controller = new $request['class']($original_url);
			$controller->url = $original_url;

			if (!method_exists($controller, $request['action'])) return $this->error404();

			$content = call_user_func_array(array($controller, $request['action']), $request['params']);
		}
		catch (AutoloadException $e) {
			//debug($e);
			return $this->error404($e->getMessage());
		}
		
		// We also show a 404 page when controller method returns false
		if ($content === false) {
			return $this->error404();
		}

		// Wrap in template
		$title = empty($controller->title)? (string) config()->site->title: $controller->title;
		$description = empty($controller->description)? (string) config()->site->description: $controller->description;
		return $controller->applyLayout($title, $content, $original_url, $description);
	}

	protected function matches($url) {
		foreach ($this->routes->route as $route) {
			$match   = "@^{$route['match']}$@i";
			if (!preg_match($match, $url)) continue;
			$replace = (string) $route;
			return array($route, preg_replace($match, $replace, $url));
		}
		return false;
	}

	protected function resolve($url) {
		$this->previous_routes = array();
		while ($new = $this->matches($url)) {
			list($new_route, $new_url) = $new;
			if (in_array($new_url, $this->previous_routes)) {
				throw new RouterCircularRoutingException($new_url, $this->previous_routes, $this->routes);
			}
			
			$continue = (string) $new_route['continue'];
			if (empty($continue) || $continue=='false') return $new_url;

			$this->previous_routes[] = $new_url;
			$url = $new_url;
		}
		return $url;
	}

	protected function request($url, $default_controller, $default_action) {
		$tmp = array_filter(explode('/', $url), function($str){ return $str !== ''; });

		$name = is_array($tmp) && count($tmp)? array_shift($tmp): $default_controller;
		$action = is_array($tmp) && count($tmp)? array_shift($tmp): $default_action;
		$params = is_array($tmp) && count($tmp)? $tmp: array();

		$name = Str::camelize($name);
		$class = 'App\\'.APP.'\\Controller\\'.$name.'Controller';
		$path = DIR_CONTROLLERS."/{$name}Controller.php";

		return array(
			'url'    => $url,
			'name'   => $name,
			'class'  => $class,
			'path'   => $path,
			'action' => strtolower($action),
			'params' => $params,
		);
	}

	private function error404($message=false) {
		header("HTTP/1.0 404 Not Found");
		ErrorHandler::WriteLog("Page not found: ".URL_CURRENT_FULL, E_USER_NOTICE);
		//return render('404', compact('message'), URL_ROOT);
		
		$class = 'App\\'.APP.'\\Controller\\AppController';
		$controller = new $class($this->url);
		$controller->setTitle('404 Not Found');
		return $controller->renderAsFullPage('404', compact('message'));
	}

}

class RouterCircularRoutingException extends \Exception {
	public function __construct($url, $previous, $all) {
		print "<p>Route {$url} already seen, previous routes:</p>\n<pre>";
		print_r($previous);
		print "</pre>\n<p>Routing table:</p>\n<pre>";
		print_r($all);
		print '</pre>';
	}
};
