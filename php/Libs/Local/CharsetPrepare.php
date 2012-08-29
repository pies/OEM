<?php

class CharsetPrepare {

	private static function GetDefineName($name) {
		return 'CHARSET_'.strtoupper(str_replace('-', '_', $name));
	}

	private static function RemoveLineComment($line, $comment_start='#') {
		$pos = strpos($line, $comment_start);
		if ($pos === false) return $line;
		return trim(substr($line, 0, $pos));
	}

	private static function DecToUTF ($dec) {
		if ($dec < 128)     return chr($dec);
		if ($dec < 2048)    return chr(($dec>>6)+192) .chr(($dec&63)+128);
		if ($dec < 65536)   return chr(($dec>>12)+224).chr((($dec>>6)&63)+128) .chr(($dec&63)+128);
		if ($dec < 2097152) return chr($dec>>18+240)  .chr((($dec>>12)&63)+128).chr(($dec>>6)&63+128).chr($dec&63+128);
		return false;
	}

	private static function ReadCharsetFile($name) {
		$path = DIR_CHARSETS.'/'.$name;

		assert('is_file($path)');
		$map = array('self', 'RemoveLineComment');
		$lines = array_values(array_filter(array_map($map, explode("\n", file_get_contents($path)))));

		assert('is_array($lines)');

		$output = array();
		foreach ($lines as $line) {
			list($xxx, $utf) = explode("\t", $line);
			$xxx = hexdec(strtoupper(str_replace('0x', '', $xxx)));
			$utf = hexdec(strtoupper(str_replace('0x', '', $utf)));

			if (!$utf) continue;

			if (($xxx <> $utf) || ($xxx > 127)) {
				$utf_char = self::DecToUTF($utf);
				$output[$utf_char] = chr($xxx);
			}
		}

		return $output;
	}

	public static function GetEncodedCharset($name) {
		return base64_encode(serialize(self::ReadCharsetFile($name)));
	}

	/**
	 * @example CharsetPrepare::DumpCharsets('iso-8859-2', 'windows-1250', 'cp852', 'mazovia');
	 */
	public static function DumpCharsets() {
		$names = func_get_args();
		foreach ($names as $name) {
			$str = self::GetEncodedCharset($name);
			print "\ndefine('".self::GetDefineName($name)."', '{$str}');\n";
		}
	}
}
