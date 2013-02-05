<?php

use Core\View;
use Core\ViewException;

class ViewTest extends PHPUnit_Framework_TestCase {
	
	public function testRender() {
		$template = DIR_TEST.'\files\template.html';
		$vars = array(
			'var1' => 'House',
			'var2' => 'home',
			'var3' => 'foo',
			'var4' => 'bar',
		);
		
		$expect = "<a href=\"#baz\">House</a> is a <a href=\"/foo\">home</a>\nfoobar";
		$this->assertEquals($expect, View::render($template, $vars));
		$expect = "<a href=\"#baz\">House</a> is a <a href=\"/prefix/foo\">home</a>\nfoobar";
		$this->assertEquals($expect, View::render($template, $vars, '/prefix'));
	}
	
	public function testException() {
		$thrown = false;
		try {
			View::render('/fake/path/that/doesnt/exist');
		}
		catch (ViewException $e) {
			$thrown = true;
		}
		
		$this->assertTrue($thrown);
	}
}