<?php
namespace Core;

use Local\Dict;

class Str {

	/**
	 * Wrapper for mb_strlen()
	 */
	public static function len($str) {
		return mb_strlen($str);
	}

	/**
	 * Wrapper for mb_substr()
	 */
	public static function sub($str, $start, $length=null) {
		return call_user_func_array('mb_substr', func_get_args());
	}

	/**
	 * Wraper for mb_strpos()
	 */
	public static function pos($str, $needle, $offset=null) {
		return call_user_func_array('mb_strpos', func_get_args());
	}

	/**
	 * The multi-byte equivalent of str_replace().
	 */
	public static function replace($str, $needle, $replacement=null) {
		if (!is_array($needle)) {
			$needle = array($needle);
		}

		if ($replacement === null) {
			$replacement = array_values($needle);
			$needle = array_keys($needle);
		}
		elseif (!is_array($replacement)) {
			$keys = array_keys($needle);
			$replacement = array_combine($keys, array_fill(0, count($keys), $replacement));
		}

		foreach (array_keys($needle) as $key) {
			$str = static::replaceSingle($str, $needle[$key], $replacement[$key]);
		}

		return $str;
	}

	/**
	 * Wrapper for mb_convert_case()
	 */
	public static function upper($str) {
		return static::convertCase($str, MB_CASE_UPPER);
	}

	/**
	 * Wrapper for mb_convert_case()
	 */
	public static function lower($str) {
		return static::convertCase($str, MB_CASE_LOWER);
	}

	/**
	 * Wrapper for mb_convert_case()
	 */
	public static function ucwords($str) {
		return static::convertCase($str, MB_CASE_TITLE);
	}

	/**
	 * Uppercase just the first letter.
	 */
	public static function ucfirst($str) {
		$first = static::sub($str, 0, 1);
		$rest = static::sub($str, 1, static::len($str));
		return static::upper($first).$rest;
	}

	/**
	* Multibyte safe version of trim()
	* Always strips whitespace characters (those equal to \s)
	*
	* @author Peter Johnson
	* @email phpnet@rcpt.at
	* @param $string The string to trim
	* @param $chars Optional list of chars to remove from the string ( as per trim() )
	* @param $chars_array Optional array of preg_quote'd chars to be removed
	* @return string
	*/
	public static function trim($string, $chars = "", $chars_array = array()) {
		$len = static::len($chars);
		for ($x=0; $x < $len; $x++) {
			$chars_array[] = preg_quote(self::sub($chars, $x, 1));
		}
		$encoded_char_list = implode("|", array_merge(array("\s", "\t", "\n", "\r", "\0", "\x0B"), $chars_array));
		$string = mb_ereg_replace("^($encoded_char_list)*", "", $string);
		$string = mb_ereg_replace("($encoded_char_list)*$", "", $string);
		return $string;
	}

	/**
	 * Converts from underscored to camel case, i.e. foo_bar_baz -> FooBarBaz
	 * Inspired by Symfony
	 */
	public static function camelize($str) {
		$rules = array(
			'#/(.?)#ue'    => "'::'.mb_strtoupper('\\1')",
			'/(^|_)(.)/ue' => "mb_strtoupper('\\2')"
		);
		return static::pregReplacePairs($str, $rules);
	}

	/**
	 * Converts from camel case to underscored, i.e. FooBarBaz -> foo_bar_baz
	 * Inspired by Symfony
	 */
	public static function underscore($str) {
		$rules = array(
			'/([\p{Lu}]+)([\p{Lu}][\p{Ll}])/u' => '\\1_\\2',
			'/([\p{Ll}\p{Nd}])(\p{Lu})/u'     => '\\1_\\2',
		);
		return static::lower(static::pregReplacePairs(static::replace($str, '::', '/'), $rules));
	}

	public static function insert($str, $data, $options=array()) {
		$options += array(
			'before' => ':',
			'after'  => null,
			'escape' => '\\'
		);

		$format = sprintf(
			'/(?<!%s)%s%%s%s/u',
			preg_quote($options['escape'], '/'),
			static::replace(preg_quote($options['before'], '/'), '%', '%%'),
			static::replace(preg_quote($options['after'], '/'), '%', '%%')
		);

		asort($data);

		$hashKeys = array_map('crc32', array_keys($data));
		$tempData = array_combine(array_keys($data), array_values($hashKeys));

		krsort($tempData);
		foreach ($tempData as $key => $hashVal) {
			$key = sprintf($format, preg_quote($key, '/'));
			$str = preg_replace($key, $hashVal, $str);
		}

		$dataReplacements = array_combine($hashKeys, array_values($data));
		foreach ($dataReplacements as $tmpHash => $tmpValue) {
			$tmpValue = (is_array($tmpValue)) ? '' : $tmpValue;
			$str = static::replace($str, $tmpHash, $tmpValue);
		}

		$str = static::replace($str, $options['escape'].$options['before'], $options['before']);

		return $str;
	}

	public static function contains($str, $needle, $offset=null) {
		return self::pos($str, $needle, $offset) !== false;
	}

	public static function startsWith($str, $needle) {
		return self::pos($str, $needle) === 0;
	}

	public static function endsWith($str, $needle) {
		$len = static::len($str);
		$pos = static::pos($str, $needle);
		return $pos == ($len - static::len($needle));
	}

	public static function preg_replace($str, $pattern, $replacement, $modifiers='ue') {
		$pairs = array($pattern.$modifiers => $replacement);
		return static::pregReplacePairs($str, $pairs);
	}

	/**
	 * Wrapper for preg_replace()
	 */
	protected static function pregReplacePairs($value, Array $pairs) {
		return preg_replace(array_keys($pairs), array_values($pairs), $value);
	}

	/**
	 * Utility for replace()
	 */
	protected static function replaceSingle($str, $search, $replace) {
		$pos = static::pos($str, $search, null);
		$len = static::len($str);
		$s_len = static::len($search);
		$r_len = static::len($replace);
		while ($pos !== false) {
			$bef = static::sub($str, 0, $pos);
			$aft = static::sub($str, $pos + $s_len, $len);
			$str = $bef.$replace.$aft;
			$pos = static::pos($str, $search, $pos + $r_len);
		}
		return $str;
	}

	/**
	 * Wrapper for mb_convert_case()
	 */
	protected static function convertCase($str, $case) {
		return mb_convert_case($str, $case);
	}

	public static function toLink($str, $space='-', $chars='a-zA-Z0-9 \-_') {
		// convert national chars
		$str = Dict::transliterate($str);
		// remove restricted chars
		$str = preg_replace("/[^{$chars}]/", '', $str);
		// remove consecutive spaces
		$str = preg_replace("/[\\s]+/", ' ', $str);
		// trim and lowercase
		$str = strtolower(trim($str));
		// convert spaces, dashes and underscores to chosen space characters
        $str = str_replace(array(' ','-','_'), $space, $str);
		// remove consecutive dashes
		$str = preg_replace('/[\-]{2,}/', '-', $str);

		return $str;
	}
	
}
