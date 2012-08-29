<?php
namespace Core;

use Core\Str;

class StrObject implements \ArrayAccess {

	/**
	 * @var String
	 */
	private $value = '';

	public function __construct($value='') {
		$this->value = $value;
	}

	public function __toString() {
		return $this->value;
	}

	public function get() {
		return $this->value;
	}

	public function  __get($name) {
		return method_exists($this, $name)?
			$this->$name():
			$this->$name;
	}


	// ArrayAccess implementation

	public function offsetSet($offset, $value) {
		$offset = $this->smartOffset($offset);
		$this->value = Str::sub($this->value, 0, $offset).$value.Str::sub($this->value, $offset+1);
		return $this;
	}

	public function offsetExists($offset) {
		$offset = $this->smartOffset($offset);
		return $offset < $this->len();
	}

	public function offsetUnset($offset) {
		$offset = $this->smartOffset($offset);
		$this->value = Str::sub($this->value, 0, $offset).Str::sub($this->value, $offset+1);
		return $this;
	}

	public function offsetGet($offset) {
		$offset = $this->smartOffset($offset);
		if ($offset < 0 || ($offset >= $this->len())) return null;
		return (string) $this->sub($offset, 1);
	}

	private function smartOffset($offset) {
		return $offset < 0? $this->len() + $offset: $offset;
	}


	/**
	 * Converts from underscored to camel case, i.e. foo_bar_baz -> FooBarBaz
	 * Inspired by Symfony
	 *
	 * @return StrObject
	 */
	public function camelize() {
		$value = Str::camelize($this->value);
		return new self($value);
	}

	/**
	 * Converts from camel case to underscored, i.e. FooBarBaz -> foo_bar_baz
	 * Inspired by Symfony
	 *
	 * @return StrObject
	 */
	public function underscore() {
		$value = Str::underscore($this->value);
		return new self($value);
	}

	/**
	 * Wrapper for strtolower()
	 * @return StrObject
	 */
	public function lower() {
		$value = Str::lower($this->value);
		return new self($value);
	}

	/**
	 * Wrapper for mb_convert_case()
	 * @return StrObject
	 */
	public function upper() {
		$value = Str::upper($this->value);
		return new self($value);
	}

	/**
	 * Wrapper for trim()
	 * @return StrObject
	 */
	public function trim() {
		$value = Str::trim($this->value);
		return new self($value);
	}

	/**
	 * Wrapper for mb_strlen()
	 * @return int
	 */
	public function len() {
		return Str::len($this->value);
	}

	/**
	 * Wrapper for mb_strpos()
	 * @param string $needle
	 * @param int $offset
	 * @return int
	 */
	public function pos($needle, $offset=null) {
		return Str::pos($this->value, $needle, $offset);
	}

	/**
	 * Wrapper for str_replace()
	 * @return StrObject
	 */
	public function replace($search, $replace=null) {
		if (is_array($search) && $replace === null) {
			$replace = array_values($search);
			$search = array_keys($search);
		}
		$value = Str::replace($this->value, $search, $replace);
		return new self($value);
	}

	/**
	 * Wrapper for mb_substr()
	 * @return StrObject
	 */
	public function sub($start, $length=null) {
		if ($length === null) $length = $this->len();
		$value = Str::sub($this->value, $start, $length);
		return new self($value);
	}

	/**
	 * @return bool
	 */
	public function contains($needle, $offset=null) {
		return Str::contains($this->value, $needle, $offset);
	}

	/**
	 * @return bool
	 */
	public function startsWith($needle) {
		return Str::startsWith($this->value, $needle);
	}

	/**
	 * @return bool
	 */
	public function endsWith($needle) {
		return Str::endsWith($this->value, $needle);
	}

	/**
	 * @return StrObject
	 */
	public function insert($data, $options=array()) {
		$value = Str::insert($this->value, $data, $options);
		return new self($value);
	}

	/**
	 * @return StrObject
	 */
	public function preg_replace($search, $replace=null) {
		if (is_array($search) && (func_num_args() == 1)) {
			$replace = array_values($search);
			$search = array_keys($search);
		}
		$value = Str::preg_replace($this->value, $search, $replace);
		return new self($value);
	}

}
