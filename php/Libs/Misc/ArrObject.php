<?php
namespace Misc;

use Core\ArrayClass;

class ArrObject extends ArrayClass {

	public function get($key=null, $default=null) {
		if ($key === null) {
			return (array) $this->data;
		}
		elseif (isset($this->data[$key])) {
			return $this->data[$key];
		}
		else {
			return $default;
		}
	}

	/**
	 * Reorganize a 2D array, using the values within internal arrays
	 * as keys for the external array.
	 * @param mixed $key
	 * @param bool $skip_duplicates
	 * @return Arr
	 */
	public function organizeBy($key, $skip_duplicates=false) {
		return new self( Arr::organizeBy($this->data, $key, $skip_duplicates) );
	}

	/**
	 * Create an array of child values specified by a key in a 2D array.
	 * @param mixed $field
	 * @return Arr
	 */
	public function extract($key) {
		return new self( Arr::extract($this->data, $key) );
	}

	/**
	 * Filter out an array so that it only contains the specified keys.
	 * @param array $keys
	 * @param bool $force
	 * @return Arr
	 */
	public function only($keys, $force=false) {
		return new self( Arr::only($this->data, $keys, $force) );
	}

	/**
	 * Flip a 2D array so that it's organized by columns instead of rows.
	 *
	 * Example:
	 *   $a = array(array('a','1'), array('b','2'), array('c','3'));
	 *   $b = array(array('a','b','c'), array('1','2','3');
	 *   arr($a)->flip()->get() == $b; // true
	 *
	 * @return Arr
	 */
	public function flip() {
		return new self( Arr::flip($this->data) );
	}

	/**
	 * Pass each value in an array trough a callback function. It passes both
	 * the value and the key to the callback, as opposed to array_map().
	 * @param callback $callback
	 * @return Arr
	 */
	public function map($callback, $values_only=false) {
		return new self( Arr::map($this->data, $callback, $values_only) );
	}

	public function join($glue='') {
		return join($glue, $this->data);
	}

}
