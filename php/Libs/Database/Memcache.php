<?php
namespace Database;

/**
 * Memcache handler.
 */
class Memcache {

	/**
	 * @var \Memcache
	 */
	static private $connection = null;

	static private $flags = null;

	/**
	 * Connects to the service
	 *
	 * @param XML $config Database-related piece of config.xml
	 * @return bool Success
	 */
	static public function connect($server='localhost', $port=11211, $compressed=false) {
		self::$flags = $compressed? MEMCACHE_COMPRESSED: 0;
		self::$connection = \memcache_connect($server, $port);
	}

	static public function get($key) {
		//return self::$connection->get($key, self::$flags);
		return \memcache_get(self::$connection, $key, self::$flags);
	}

	static public function add($key, $value, $expire=0) {
		return \memcache_add(self::$connection, $key, $value, self::$flags, $expire);
	}

	static public function set($key, $value, $expire=0) {
		return \memcache_set(self::$connection, $key, $value, self::$flags, $expire);
	}

	static public function replace($key, $value, $expire=0) {
		return \memcache_replace(self::$connection, $key, $value, self::$flags, $expire);
	}

	static public function delete($key, $timeout=0) {
		return \memcache_delete(self::$connection, $key, $timeout);
	}

	static public function inc($key) {
		return \memcache_increment(self::$connection, $key);
	}

	static public function dec($key) {
		return \memcache_decrement(self::$connection, $key);
	}


	static public function flush() {
		return \memcache_flush(self::$connection);
	}

	static public function stats() {
		return \memcache_get_stats(self::$connection);
	}

}