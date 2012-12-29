<?php
namespace Misc;

use Core\Str;

class Valid {
	
	static public function email($string) {
		return preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $string);
	}

	static public function notEmpty($var) {
		return !empty($var);
	}
	
	static public function regex($str, $expression) {
		return (bool) preg_match($expression, (string) $str);
	}

	static public function minLength($str, $length) {
		return Str::len($str) >= $length;
	}

	static public function maxLength($str, $length) {
		return Str::len($str) <= $length;
	}
	
	static public function isLength($str, $length) {
		return Str::len($str) == $length;
	}
	
	static public function lengthBetween($str, $min, $max) {
		return self::minLength($str, $min) && self::maxLength($str, $max);
	}
	
	static public function is($var, $expected) {
		return $var === $expected;
	}
	
	static public function date($str) {
		return strtotime($str) !== false;
	}

	static public function between($num, $min, $max) {
		return ($num >= $min) && ($num <= $max);
	}

	static public function matches($values, $field1, $field2) {
		return ($values[$field1] === $values[$field2]);
	}
	
	static public function uploadType(array $file, array $allowed) {
		if ($file['error'] !== UPLOAD_ERR_OK) return false;
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		return in_array($ext, $allowed);
	}

	static public function uploadSize(array $file, $size) {
		// upload failed
		if ($file['error'] !== UPLOAD_ERR_OK) return false;

		// upload larger than upload_max_filesize
		if ($file['error'] === UPLOAD_ERR_INI_SIZE) return false;

		return $file['size'] <= to_bytes($size);
	}

	static public function inArray($value, $array) {
		return in_array($value, $array);
	}
	
}
