<?php

use Misc\Arr;

class ArrTest extends PHPUnit_Framework_TestCase {
	
	public function setUp() {
		$this->input = array(
			'a' => array('i'=>'ai','j'=>'aj'),
			'b' => array('i'=>'bi','j'=>'bj','k'=>'bk'),
			'c' => array(),
			'd' => array('i'=>'ai','j'=>'dj','k'=>'dk'),
		);
	}
	
	public function testFlip() {
		$expected = array(
			'i' => array('a'=>'ai', 'b'=>'bi', 'd'=>'ai'),
			'j' => array('a'=>'aj', 'b'=>'bj', 'd'=>'dj'),
			'k' => array('b'=>'bk', 'd'=>'dk')
		);
		$this->assertEquals($expected, Arr::flip($this->input));
	}
	
	public function testExtract() {
		$expected = array('a'=>'ai','b'=>'bi','d'=>'ai');
		$this->assertEquals($expected, Arr::extract($this->input, 'i'));
		$expected = array('b'=>'bk','d'=>'dk');
		$this->assertEquals($expected, Arr::extract($this->input, 'k'));
	}
	
	public function testOnly() {
		$expected = array(
			'a' => array('i'=>'ai','j'=>'aj'),
			'b' => array('i'=>'bi','j'=>'bj','k'=>'bk'),
			'c' => array(),
		);
		$this->assertEquals($expected, Arr::only($this->input, 'a,b,c'));
		
		$expected = array(
			'a' => array('i'=>'ai','j'=>'aj'),
			'e' => null
		);
		$this->assertEquals($expected, Arr::only($this->input, array('a','e'), true));
	}
	
	public function testOrganizeBy() {
		$expected = array(
			'ai' => array('i'=>'ai','j'=>'dj','k'=>'dk'),
			'bi' => array('i'=>'bi','j'=>'bj','k'=>'bk'),
		);
		$this->assertEquals($expected, Arr::organizeBy($this->input, 'i'));
		
		$expected = array(
			'ai' => array('i'=>'ai','j'=>'aj'),
			'bi' => array('i'=>'bi','j'=>'bj','k'=>'bk'),
		);
		$this->assertEquals($expected, Arr::organizeBy($this->input, 'i', true));
	}
	
	public function testOrganizeExtract() {
		$expected = array(
			'ai' => 'dj',
			'bi' => 'bj'
		);
		$this->assertEquals($expected, Arr::organizeExtract($this->input, 'i', 'j'));

		$expected = array(
			'ai' => 'aj',
			'bi' => 'bj'
		);
		$this->assertEquals($expected, Arr::organizeExtract($this->input, 'i', 'j', true));
	}
	
	public function testMap() {
		$expected = array(
			'a' => 'A=IJ',
			'b' => 'B=IJK',
			'c' => 'C=',
			'd' => 'D=IJK',
		);
		$callback = function($value, $key) {
			return strtoupper($key.'='.join(array_keys($value)));
		};
		$this->assertEquals($expected, Arr::map($this->input, $callback));
	}
	
	public function testFromToObject() {
		$this->assertEquals($this->input, Arr::fromObject(Arr::asObject($this->input)));
	}
	
	public function testJoin() {
		$input = array(
			'ąćęł',
			'foo',
			'bar',
			123
		);
		$expected = 'ąćęłfoobar123';
		$this->assertEquals($expected, Arr::join($input));
		
		$expected = 'ąćęł+foo+bar+123';
		$this->assertEquals($expected, Arr::join($input,'+'));
	}
}