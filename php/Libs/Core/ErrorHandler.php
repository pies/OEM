<?php
namespace Core;

assert("defined('DEBUG')");
assert("defined('DIR_ROOT')");
assert("defined('DIR_LOGS')");

if (!defined('E_USER_STRICT')) {
	define('E_USER_STRICT', 4096);
}

class ErrorHandler {

	public static function Init() {
		$date = date('ymd');
		ini_set('error_log', DIR_LOGS."/{$date}_error.log");
		set_error_handler(array(__CLASS__, 'Handler'));
	}

	private static function GetType($no) {
		switch ($no) {
			case E_ERROR:   case E_USER_ERROR:   return 'error';   break;
			case E_WARNING: case E_USER_WARNING: return 'warning'; break;
			case E_NOTICE:  case E_USER_NOTICE:  return 'notice';  break;
			case E_STRICT:  case E_USER_STRICT:  return 'strict';  break;
			default: return "unknown_{$no}"; break;
		}
	}

	private static function GetTracePlace($step) {
		if (empty($step['file'])) return false;
		$path = str_replace(array('\\', DIR_ROOT.'/'), '/', $step['file']);
		return empty($path)? false: $path.':'.$step['line'];
	}

	private static function GetTraceName($step) {
		if (empty($step['function'])) {
			return false;
		}
		elseif (empty($step['class'])) {
			return $step['function'].self::GetTraceArgs($step);
		}
		else {
			return $step['class'].$step['type'].$step['function'].self::GetTraceArgs($step);
		}
	}

	private static function GetTraceArgs($step) {
		if (empty($step['args'])) return '()';
		$out = array();
		foreach ($step['args'] as $arg) {
			if (is_object($arg)) {
				$str = '${'.get_class($arg).'}';
			}
			elseif (is_string($arg)) {
				$str = "'{$arg}'";
			}
			elseif (is_int($arg)) {
				$str = "(int) {$arg}";
			}
			elseif (is_float($arg)) {
				$str = "(float) {$arg}";
			}
			elseif (is_array($arg)) {
				$str = "[".join(", ", array_keys($arg))."]";
			}
			else {
				$str = '${'.gettype($arg).'}';
			}
			//$str = is_object($arg)? '${'.get_class($arg).'}': (is_array($arg)? implode(', ', $arg) :(string) $arg);
			$out[] = $str;//var_export($str, true);
		}
		return '('.join(', ', $out).')';
	}

	private static function GetTraceStep($step, $prev) {
		$call = self::GetTraceName($step);
		$place = self::GetTracePlace($prev);
		$snippet = self::CodeSnippet($prev['file'], $prev['line'], 3);
		$line = $prev['line'];
		return compact('call','place','snippet','line');
	}

	private static function GetTrace() {
		$trace = debug_backtrace();
		array_shift($trace); // first is myself

		$place = false;
		$out = array();
		$prev = false;
		$step = null;

		$prev = array_shift($trace);

		foreach ($trace as $step) {
			$place = self::GetTracePlace($prev);
			if ($place) {
				$out[] = self::GetTraceStep($step, $prev);
			}
			$prev = $step;
		}

		if (!$step) $step = $prev;

		$out[] = self::GetTraceStep(null, $step);

		return $out;
	}

	private static function GetTraceStr($trace) {
		foreach ($trace as $step) {
			$out[] = $step['place'];
		}
		return join(" -- ", $out);
	}

	protected static function PerformWrite($str, $name, $trace=false) {
		$created = time();
		$created_ms = substr(microtime(), 2, 4);
		$date = date('ymd', $created);
		$time = date('His.', $created).$created_ms;
		
		$sid = session_id()? substr(session_id(), 0, 8): '-'.getmypid();
		$ip = long2ip(request_origin());
		$uri = URL_CURRENT_FULL;

		$trace or $trace = self::GetTrace();
		$trace_str = self::GetTraceStr($trace);

		$msg  = "{$time}\t{$sid}\t{$uri}\t{$str}\t{$ip}\t{$trace_str}\n";
		$path = DIR_LOGS."/{$date}_{$name}.log";
		error_log($msg, 3, $path);
	}

	public static function WriteInfo($str, $type, $trace=false) {
		return self::PerformWrite($str, "INFO_{$type}", $trace);
	}

	public static function WriteLog($str, $num, $trace=false) {
		$type = self::GetType($num);
		return self::PerformWrite($str, $type, $trace);
	}

	public static function Handler ($num, $str, $file, $line) {
		$trace = self::GetTrace();
		$type = self::GetType($num);

		self::WriteLog($str, $num, $trace);

		if (!ini_get('display_errors') || !DEBUG || ($type == 'strict')) return true;

		$name = ucfirst($type);
		$first = $trace[0];

		if (ini_get('html_errors')) {
			print self::Render($name, $str, $first, $trace);
		}
		else {
			print "\n[{$name}] {$str} in {$first['call']} ({$first['place']})\n";
		}

		if ($type == 'error') exit(1);

		/* Don't execute PHP internal error handler */
		return true;
	}

	// http://irsoft.de/web/PHP-longest-common-prefix
	private static function LongestCommonPrefix(array $arr, $ignore_empty = false) {
		$prefix = NULL;

		foreach ($arr as $s) {
			if ($ignore_empty && empty($s))
				continue;

			if (is_null($prefix)) {
				$prefix = $s;
			}
			else {
				while (!empty($prefix) && substr($s, 0, strlen($prefix)) != $prefix) {
					$prefix = substr($prefix, 0, -1);
				}
			}
		}

		return $prefix;
	}

	private static function CodeSnippet($file, $line, $before=3, $after=false) {
		if (!$after) $after = $before;

		if (ini_get('html_errors')) {
			$code = highlight_file($file, 1);
			$lines = array_map('trim', explode('<br />', ' '.$code));
			array_unshift($lines, '');
		}
		else {
			$code = html_entity_decode(strip_tags(str_replace('<br />', "\n", highlight_file($file, 1))));
			$lines = array_map('trim', explode("\n", ' '.$code));
		}

		$snippet = array_slice($lines, max(0, $line-$before), $before+$after+1, true);
		$prefix = self::LongestCommonPrefix($snippet, true);
		if ($prefix == '<') $prefix = false;

		if ($prefix) {
			$len = strlen($prefix);
			$callback = function($str) use($len) { return (string) substr($str, $len); };
			$snippet = array_map($callback, $snippet);
		}

		return $snippet;
	}

	private static function Render($name, $str, $first, $trace) {
		ob_start();
		include(__DIR__.'/ErrorHandler.html');
		return ob_get_clean();
	}

}
