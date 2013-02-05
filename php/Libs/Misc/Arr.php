<?php
namespace Misc;

class Arr {

	/**
	 * Flip a 2D array so that it's organized by columns instead of rows.
	 *
	 * Example:
	 *   $a = array(array('a','1'), array('b','2'), array('c','3'));
	 *   $b = array(array('a','b','c'), array('1','2','3');
	 *   Arr::flip($a) == $b; // true
	 *
	 * @param array $array
	 * @return array
	 */
	public static function flip($array) {
		$out = array();
		foreach ($array as $k1=>$row) {
			foreach ($row as $k2=>$value) {
				$out[$k2][$k1] = $value;
			}
		}
		return $out;
	}

	/**
	 * Create an array of child values specified by a key in a 2D array.
	 *
	 * @param array $array
	 * @param mixed $field
	 * @return array
	 */
	public static function extract($array, $key) {
		$out = array();
		foreach ($array as $index=>$value) {
			if (isset($value[$key]))
				$out[$index] = $value[$key];
		}
		return $out;
	}

	/**
	 * Filter out an array so that it only contains the specified keys.
	 *
	 * @param array $array
	 * @param array $keys
	 * @param bool $force
	 * @return array
	 */
	public static function only($array, $keys, $force=false) {
		if (is_string($keys)) $keys = explode(',', $keys);
		$out = array();
		foreach ($keys as $key) {
			if ($force) $out[$key] = null;
			if (isset($array[$key])) {
				$out[$key] = $array[$key];
			}
		}
		return $out;
	}

	/**
	 * Reorganize a 2D array, using the values within internal arrays
	 * as keys for the external array.
	 *
	 * @param array $array
	 * @param mixed $key
	 * @param bool $skip_duplicates
	 * @return array
	 */
	public static function organizeBy($array, $key, $skip_duplicates=false) {
		$out = array();
		foreach ($array as $value) {
			if (isset($value[$key])) {
				$new_key = $value[$key];
				$exists = isset($out[$new_key]);
				if (!$exists || !$skip_duplicates) {
					$out[$new_key] = $value;
				}
			}
		}
		return $out;
	}

	/**
	 * Reorganize a 2D array, using a value within internal array
	 * as key and another value as new value.
	 *
	 * @param array $array
	 * @param mixed $key1
	 * @param mixed $key2
	 * @param bool $skip_duplicates
	 * @return array
	 */
	public static function organizeExtract($array, $key1, $key2, $skip_duplicates=false) {
		$out = array();
		foreach ($array as $value) {
			if (isset($value[$key1]) && isset($value[$key2])) {
				$new_key = $value[$key1];
				$new_value = $value[$key2];
				if (!(isset($out[$new_key]) && $skip_duplicates)) {
					$out[$new_key] = $new_value;
				}
			}
		}
		return $out;
	}

	/**
	 * Pass each value in an array trough a callback function. It passes both
	 * the value and the key to the callback, as opposed to array_map().
	 *
	 * @param callback $callback
	 * @return array
	 */
	public static function map($array, $callback, $values_only=false) {
		$out = array();
		foreach ($array as $key=>$value) {
			$out[$key] = $values_only? $callback($value): $callback($value, $key);
		}
		return $out;
	}

	/**
	 * Recursively convert an object into an array.
	 *
	 * @param object $obj
	 * @return array
	 */
	public static function fromObject($obj) {
		$out = array();
		foreach ($obj as $key => $value) {
			$out[$key] = is_object($value) && count($value)? self::fromObject($value): (string) $value;
		}
		return $out;
	}

	/**
	 * Recursively convert an array into an object.
	 *
	 * @param array $array
	 * @return object
	 */
	public static function asObject($array) {
		$obj = (object) array();
		foreach ($array as $key => $value) {
			$obj->$key = is_array($value)? self::asObject($value): $value;
		}
		return $obj;
	}

	/**
	 * Joins an array into a string using optional glue.
	 * 
	 * @param array $array
	 * @param string $glue
	 * @return string
	 */
	public static function join($array, $glue='') {
		return join($glue, $array);
	}

}
