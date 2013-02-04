<?php

use Core\Str;

class StrTest extends PHPUnit_Framework_TestCase {

	public function testCamelize() {
		$tests = array(
			'foo' => 'Foo',
			'foo_bar' => 'FooBar',
			'FooBar' => 'FooBar',
		);

		foreach ($tests as $input => $expected) {
			$this->assertEquals($expected, Str::camelize($input));
		}
	}

	public function testWrappers() {
		$input = 'Ąbć dęfć';
		$this->assertEquals('dęfć', Str::sub($input, 4));
		$this->assertEquals('bć dę', Str::sub($input, 1, 5));
		$this->assertEquals(2, Str::pos($input, 'ć'));
		$this->assertEquals(7, Str::pos($input, 'ć', 4));
		$this->assertEquals('ĄBĆ DĘFĆ', Str::upper($input));
		$this->assertEquals('ąbć dęfć', Str::lower($input));
		$this->assertEquals('Ąbć Dęfć', Str::ucwords($input));
	}

}