<?php
namespace Model;

use Database\DB as DB;
use Core\XML as XML;

/**
 * @package Model
 */
class DBToXML {

	private static function IndexToStr($index) {
		$out = array();
		foreach ($index as $col) {
			$out[] = $col['Column_name'];
		}
		return join(',', $out);
	}

	public static function GetIndexes($table) {
		$indexes = DB::all("SHOW INDEX IN ".DB::quoteField($table));

		$tmp = array();
		foreach ($indexes as $index) {
			$name = $index['Key_name'];
			$seq = $index['Seq_in_index'] - 1;
			$tmp[$name][$seq] = $index;
		}

		$out = array(
			'index' => array(),
			'unique' => array(),
		);

		foreach ($tmp as $name=>$index) {
			$str = self::IndexToStr($index);
			if ($index[0]['Key_name'] == 'PRIMARY') {
				$out['primary'] = $str;
			}
			elseif ($index[0]['Non_unique'] == '1') {
				$out['index'][] = $str;
			}
			else {
				$out['unique'][] = $str;
			}
		}

		return $out;
	}

	public static function NormalizeType($name) {
		$name = strtolower($name);
		$replace_types = array(
			'bigint' => 'int',
		);
		return isset($replace_types[$name])? $replace_types[$name]: $name;
	}

	public static function TableAsXML($table_name, $table=false) {
		if ($table === false) $table = new XML('<table/>');

		$table['name'] = $table_name;

		$indexes = self::GetIndexes($table_name);

		foreach ($indexes['index'] as $index_str) {
			$table->addChild('index', $index_str);
		}

		foreach ($indexes['unique'] as $index_str) {
			$table->addChild('unique', $index_str);
		}

		$table['primary_key'] = isset($indexes['primary'])? $indexes['primary']: false;

		$cols = DB::all("DESCRIBE ".DB::quoteField($table_name));

		foreach ($cols as $col) {
			$field = $table->addChild('field');

			$name = $col['Field'];
			$type = $col['Type'];
			$key  = $col['Key'];
			$default = $col['Default'];
			$null = $col['Null'];
			$extra = $col['Extra'];

			$field['name'] = $name;

			// type, size
			if (preg_match('/^([a-z]+)\(([0-9]+)\) unsigned$/', $type, $match)) {
				$field['type'] = self::NormalizeType($match[1]);
				$field['size'] = $match[2];
				$field['signed'] = 'n';
			}
			elseif (preg_match('/^([a-z]+)\(([0-9]+)\)$/', $type, $match)) {
				$field['type'] = self::NormalizeType($match[1]);
				$field['size'] = $match[2];
			}
			elseif (preg_match('/^(decimal)\(([0-9]+,[0-9]+)\)$/', $type, $match)) {
				$field['type'] = self::NormalizeType($match[1]);
				$field['size'] = $match[2];
			}
			elseif (preg_match('/^(enum|set)\((.+)\)$/', $type, $match)) {
				$options = array();
				foreach (explode(',', $match[2]) as $option) {
					if (preg_match("/^'(.+)'$/", $option, $match2)) {
						$options[] = $match2[1];
					}
				}
				$field['type'] = self::NormalizeType($match[1]);
				$field['size'] = join(',', $options);
			}
			elseif (in_array($type, array('tinytext','text','blob','longblob'))) {
				$field['type'] = self::NormalizeType($type);
			}
			else {
				debug($type);
			}

			if ($field['type'] == 'int' && !isset($field['signed'])) {
				$field['signed'] = 'y';
			}

			// default
			if ($default !== NULL) {
				$field['default'] = $default;
			}
			else {
				$field['null'] = 'y';
			}

			if ($null == 'YES') {
				$field['null'] = 'y';
			}
			elseif ($null == 'NO') {
				$field['null'] = 'n';
			}

			// auto_increment
			if ($extra == 'auto_increment') {
				$field['auto_increment'] = 'y';
			}
			elseif ($extra) {
				debug($extra);
			}
		}

		return $table;
	}

	public static function asXML($tables) {
		$out = new XML('<model/>');

		foreach ($tables as $table_name) {
			self::TableAsXML($table_name, $out->addChild('table'));
		}

		return $out;
	}

}