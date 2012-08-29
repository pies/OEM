<?php
namespace App\Test\Controller;

class TestController {

	public function foo($arg) {
		return $arg;
	}

	public function applyLayout($title, $content, $url) {
		return "[{$content}]";
	}

}