<?php
namespace Core;

class Assert {

	public static function Handler($file, $line, $code) {
		$place = pretty_path($file, $line);
		output($code, "Assertion failed at {$place}");
	}

	public static function Init($active=true) {
		assert_options(ASSERT_ACTIVE, $active);
		if (!$active) return false;

		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_QUIET_EVAL, 1);
		assert_options(ASSERT_CALLBACK, array(__CLASS__, 'Handler'));

		assert("function_exists('output')");
		assert("function_exists('pretty_path')");
	}
}

?>