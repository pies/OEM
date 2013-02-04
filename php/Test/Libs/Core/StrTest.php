<?php
use Core\Str;

class StrTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
    }

    public function tearDown() {
    }

    public function testCamelize() {
		$tests = array(
			'foo' => 'Foo',
			'foo_bar' => 'FooBar',
			'FooBar' => 'FooBar',
		);
		
		foreach ($tests as $input=>$expected) {
			$this->assertEquals($expected, Str::camelize($input));
		}
    }

}