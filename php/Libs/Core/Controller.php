<?php
namespace Core;

use Site;

class Controller {

	public $url;
	public $skipLayout = false;
	public $layoutName = 'Layout';
	public $layoutData = array();

	protected $title = 'Welcome';

	public function  __construct($url=null) {
		$this->url = $url;
	}

	public function render($name, $_DATA_=false, $_PREFIX_=false) {
		if ($name[0] == '~') {
			$name = substr($name, 1);
			$_PATH_ = DIR_SHARED."/View/{$name}.html";
		}
		else {
			$_PATH_ = DIR_VIEWS."/{$name}.html";
		}
		
		if (!is_readable_file($_PATH_)) {
			new \Exception("Could not read file '{$_PATH_}'");
		}

		if (is_array($_DATA_)) extract($_DATA_);
		ob_start();
		include($_PATH_);

		return is_string($_PREFIX_)?
			View::prefix(ob_get_clean(), $_PREFIX_):
			ob_get_clean();
	}

	public function applyLayout($data=array()) {
		return $this->skipLayout? $data['content']: $this->render($this->layoutName, $data, URL_ROOT);
	}

	public function setTitle($title, $no_template=false) {
		if ($no_template) {
			$this->title = (string) $title;
		}
		else {
			$template = (string) config()->site->title;
			$this->title = sprintf($template, $title);
		}
		
	}

	public function renderAsFullPage($view, $data=array()) {
		$content = $this->render($view, $data);
		die($this->render($this->layoutName, compact('content'), URL_ROOT));
	}
	
	protected function redirectTo($url) {
		header('Location: '.URL_ROOT.$url);
		die();
	}

	protected function error404($message=false) {
		header("HTTP/1.0 404 Not Found");
		return render('404', compact('message'));
	}
	
	protected function outputJson($out) {
		$this->skipLayout = true;
		Site::ContentType('json');
		return json_encode($out);
	}
	

}
