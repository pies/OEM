<?php
namespace Core;
use \ArrayAccess, \Iterator, \Countable;

class ArrayClass implements ArrayAccess, Iterator, Countable {

	protected $data = array();

	public function __construct($data=array()) {
		$this->data = $data;
		$this->rewind();
	}

	/**
	 * @param array $data
	 * @return \Core\ArrayClass
	 */
	public static function factory($data=array()) {
		return new self($data);
	}
	
	// ArrayAccess implementation

	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset) {
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value) {
		return $this->data[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	// Iterator implementation

	public function rewind() {
		reset($this->data);
	}

	public function key() {
		return key($this->data);
	}

	public function current() {
		return current($this->data);
	}

	public function next() {
		return next($this->data);
	}

	public function valid() {
		return current($this->data) !== false;
	}

	// Countable implementation

	public function count() {
		return count($this->data);
	}

	// Extras
	
	public function sortBy($key, $asc=true) {
		$sort = array();
		foreach ($this as $row_id=>$row) {
			$sort[$row_id] = $row[$key];
		}
		$asc? asort($sort): arsort($sort);
		$out = array();
		foreach ($sort as $row_id=>$value) {
			$out[] = $this[$row_id];
		}
		return $out;
	}
	
}
