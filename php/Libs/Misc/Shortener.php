<?php
namespace Misc;

/**
 * @package Misc
 */
class Shortener {

	public static $charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	public static function Expand($input, $index=false) {
		$index or $index = self::$charset;

		$input = strrev($input);
		$base  = strlen($index);
		$len = strlen($input) - 1;

		$out = 0;
		for ($t = 0; $t <= $len; $t++) {
			$bcpow  = bcpow($base, $len - $t);
			$out   += strpos($index, substr($input, $t, 1)) * $bcpow;
		}

		$out = sprintf('%F', $out);
		$out = substr($out, 0, strpos($out, '.'));

		return $out;
	}

	public static function Shorten($input, $index=false) {
		$index or $index = self::$charset;

		$input = (int) $input;
		$base  = strlen($index);

		$out = "";
		for ($t = floor(log($input, $base)); $t >= 0; $t--) {
			$bcp = bcpow($base, $t);
			$a   = floor($input / $bcp) % $base;
			$out = $out . substr($index, $a, 1);
			$input  = $input - ($a * $bcp);
		}
		$out = strrev($out); // reverse

		return $out;
	}

}
