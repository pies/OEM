<?php
namespace Database;

class DBLog {

	const MAX_LENGTH = 1000;

	static private $data = array();

	static public function add($sql, $time=false) {
		if (count(self::$data) >= self::MAX_LENGTH) return;
		self::$data[] = array($sql, $time, join(' -> ', place()));
	}

	static public function reset() {
		self::$data = array();
		static::exec("RESET QUERY CACHE");
		static::exec("FLUSH TABLES");
	}

	static public function display() {
		$items = $time = 0;
		foreach (self::$data as $line) {
			$items++;
			$time += $line[1];
		}
		$time = sprintf('%0.1f', $time);
		debug("{$items} queries, {$time} ms total");
		debug(self::$data);
	}
	
}