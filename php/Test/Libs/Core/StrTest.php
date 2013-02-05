<?php

use Core\Str;

class StrTest extends PHPUnit_Framework_TestCase {

	public function testWrappers() {
		$input = 'Ąbć dęfć';
		
		$this->assertEquals(8, Str::len($input));
		$this->assertEquals('dęfć', Str::sub($input, 4));
		$this->assertEquals('bć dę', Str::sub($input, 1, 5));
		
		// pos and related
		$this->assertEquals(2, Str::pos($input, 'ć'));
		$this->assertEquals(7, Str::pos($input, 'ć', 4));
		$this->assertTrue(Str::contains($input, 'ć'));
		$this->assertFalse(Str::contains($input, 'ź'));
		$this->assertTrue(Str::startsWith($input, 'Ą'));
		$this->assertTrue(Str::startsWith($input, 'Ąbć'));
		$this->assertFalse(Str::startsWith($input, 'Abc'));
		$this->assertTrue(Str::endsWith($input, 'ć'));
		$this->assertTrue(Str::endsWith($input, 'ęfć'));
		$this->assertFalse(Str::endsWith($input, 'f'));
		
		// case-related
		$this->assertEquals('ĄBĆ DĘFĆ', Str::upper($input));
		$this->assertEquals('ąbć dęfć', Str::lower($input));
		$this->assertEquals('Ąbć Dęfć', Str::ucwords($input));
		$this->assertEquals('Ąć', Str::ucfirst('ąć'));
	}
	
	public function testReplace() {
		$input = 'Ąbć dęfć';
		$this->assertEquals('Ąbć fółć', Str::replace($input, 'dęf', 'fół'));
		$this->assertEquals('Ąbź żółć', Str::replace($input, array('dęf' => 'fół', 'ć f' => 'ź ż')));
		$this->assertEquals('Ąbź żółć', Str::replace($input, array('dęf', 'ć f'), array('fół', 'ź ż')));
		$this->assertEquals('Ąbć mooć', Str::replace($input, array('dęf', 'ć f'), 'moo'));
	}

	public function testCamelize() {
		$tests = array(
			'foo' => 'Foo',
			'foo_bar' => 'FooBar',
			'foo__bar' => 'Foo_bar'
		);

		foreach ($tests as $input => $expected) {
			$this->assertEquals($expected, Str::camelize($input));
		}
	}
	
	public function testUnderscore() {
		$tests = array(
			'Foo' => 'foo',
			'FooBar' => 'foo_bar',
			'FooBarBAZ' => 'foo_bar_baz'
		);

		foreach ($tests as $input => $expected) {
			$this->assertEquals($expected, Str::underscore($input));
		}
	}
	
	public function testTrim() {
		$tests = array(
			' foó ' => 'foó',
			"\tBar\n" => 'Bar',
		);
		
		foreach ($tests as $input => $expected) {
			$this->assertEquals($expected, Str::trim($input));
		}
		
		$this->assertEquals("\nBaz\t", Str::trim("\nBaz\t\r", "\r"));
		$this->assertEquals("Boo", Str::trim("\nBoo\t\r", "", array(preg_quote("\n"), preg_quote("\t"), preg_quote("\r"))));
	}

	public function testInsert() {
		$this->assertEquals('FóóbąR:baż', Str::insert('Fó:óneR\\:baż', array('óne'=>'óbą')));
		$this->assertEquals('FooBar[Baz]', Str::insert('Foo[óne].[Baz]', array('óne'=>'Bar'), array('before'=>'[','after'=>']','escape'=>'.')));
	}
	
	public function testToLink() {
		$this->assertEquals('abc-def-gh-12', Str::toLink('Ąbć-DEF? Gh: 12'));
		$this->assertEquals('abc_def_gh_12', Str::toLink('Ąbć-DEF? Gh: 12', '_'));
		$this->assertEquals('abcdefgh', Str::toLink('Ąbć-DEF? Gh: 12', '_', 'a-zA-Z'));
	}
	
	public function testPregReplace() {
		$this->assertEquals('Foo-DEF? Gh: 12', Str::preg_replace('Ąbć-DEF? Gh: 12', '/Ąb./', 'Foo'));
	}
	
}