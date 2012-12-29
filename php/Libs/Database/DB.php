<?php
namespace Database;

use \PDO as PDO;
use \Core\Str;

class DB {

	/**
	 * @var PDO
	 */
	static public $pdo = null;
	static public $lastSQL = null;

	static public $fetchMode = PDO::FETCH_ASSOC;

	/*
	 * UTILITY
	 */

	/**
	 * Parses the configuration to extract the appropriate settings.
	 */
	static public function config($config) {
			$port = $config->port? ";port={$config->port}": '';
			$type = $config['type']? $config['type']: 'mysql';
			$conn  = "{$type}:host={$config->host};dbname={$config->name}{$port}";
			$login = "{$config->login}";
			$pass  = "{$config->password}";
			$opts  = array(
				PDO::ATTR_PERSISTENT => (bool) @$config['persistent'],
				PDO::ATTR_TIMEOUT => 2,
			);
			return compact('conn', 'login', 'pass', 'opts', 'type');
	}
	
	/**
	 * Connects to the database
	 *
	 * @param XML $config Database-related piece of config.xml
	 * @return bool Success
	 */
	static public function connect($config) {

		if (!$config || !(string)$config->name) {
			throw new DBException("Please provide a valid configuration object.", E_USER_ERROR);
		}

		try {
			$settings = static::config($config);
			self::$pdo = new PDO($settings['conn'], $settings['login'], $settings['pass'], $settings['opts']);
			if ($settings['type'] == 'mysql') {
				self::query("SET NAMES utf8");
			}
			return self::$pdo;
		}
		catch (\PDOException $e) {
			throw new DBException("Can't connect to database: " . $e->getMessage(), E_USER_ERROR);
		}
	}

	static public function exec($sql) {
		self::$lastSQL = $sql;
		$start = microtime(true);
		$result = self::$pdo->exec($sql);
		self::log($sql, (microtime(true)-$start)*1000);

		$error = self::error();
		if ($error) {
			$message = "Database query error: ({$error[0]}/{$error[1]}) {$error[2]} in query [{$sql}]";
			throw new DBQueryException($message, E_USER_WARNING);
			return false;
		}

		return $result;
	}

	static public function error() {
		return (int) self::$pdo->errorCode()?
			self::$pdo->errorInfo():
			false;
	}

	static public function log($sql, $time=false) {
		DBLog::add($sql, $time);
	}

	static public function query($sql) {
		if (strpos(trim($sql), 'SELECT ') === 0) {
			return self::select($sql);
		}
		elseif (strpos(($sql), 'INSERT ') === 0) {
			return self::insert($sql);
		}
		else {
			return (bool) static::exec($sql);
		}
	}

	static public function quote($value) {
		if (is_object($value) && get_class($value) == 'Database\\DBExpression') {
			return (string) $value;
		}
		return is_array($value)?
			array_map(array('self', 'quote'), $value):
			self::$pdo->quote($value);
	}

	static public function quoteField($name) {
		if (is_string($name) && strpos($name, '.')) {
			return join('.', self::quoteField(explode('.', $name)));
		}
		
		return is_array($name)?
			array_map(array('self', 'quoteField'), $name):
			'`'.str_replace('`', '``', $name).'`';
	}

	static public function quoteInto($str, $data, $options=array()) {
		return Str::insert($str, self::quote($data), $options);
	}

	static protected function fieldsToSql($fields) {
		if (!is_array($fields)) return $fields;
		return join(', ', self::quoteField($fields));
	}

	static public function conditionsToSql($cond) {

		if (!is_array($cond) && (is_int($cond) || preg_match('/^[0-9]+$/', $cond))) {
			trigger_error('Unsafe condition: '.$cond, E_USER_WARNING);
			$cond = array('id'=>$cond);
		}

		// assume it's a string
		if (!is_array($cond)) {
			return (string) $cond;
		}

		$out = array();
		foreach ($cond as $key => $value) {
			if (is_array($value)) {
				$sign = $value[1];
				$value = $value[0];
			}
			else {
				$sign = '=';
			}
			$out[] = self::quoteCondition($key, $value, $sign);
		}
		return '('.join(') AND (', $out).')';
	}

	static protected function quoteCondition($field, $value, $sign='=') {
		$quoted_field = self::quoteField($field);
		$quoted_value = self::quote($value);
		if (is_array($quoted_value)) {
			$quoted_value = '('.join(',', $quoted_value).')';
		}
		return "{$quoted_field} {$sign} {$quoted_value}";
	}

	static public function expr($str) {
		return new DBExpression($str);
	}

	/*
	 * READING
	 */

	static public function select($sql_or_table, $fields=null, $cond=null, $limit=false) {
		if ($fields) {
			$fields = self::fieldsToSql($fields);
			$cond = $cond? ' WHERE '.self::conditionsToSql($cond): '';
			$limit = $limit? " LIMIT {$limit}": '';
			$table = self::quoteField($sql_or_table);
			$sql = "SELECT {$fields} FROM {$table}{$cond}{$limit};";
		}
		else {
			$sql = $sql_or_table;
		}
		
		$query = self::$pdo->prepare($sql);
		$result = new DBResult($query, self::$fetchMode);
		self::$lastSQL = (string) $result->query->queryString;
		$result->callback = function($sql, $time){
			$class = __CLASS__;
			$class::log($sql, $time);
		};
		return $result;
	}

	static public function all($sql_or_table, $fields=null, $conditions=null, $limit=false) {
		$out = array();
		$query = self::select($sql_or_table, $fields, $conditions, $limit);
		foreach ($query as $row) {
			$out[] = $row;
		}
		return $out;
	}

	static public function row($sql_or_table, $fields=null, $conditions=null) {
		$query = self::select($sql_or_table, $fields, $conditions, 1);
		foreach ($query as $row) {
			return $row;
		}
		return null;
	}

	static public function value($sql_or_table, $fields=null, $conditions=null) {
		$row = self::row($sql_or_table, $fields, $conditions, 1);
		return is_array($row)? array_shift($row): null;
	}

	static public function get($table, $cond) {
		return self::row($table, '*', $cond);
	}

	static public function tables() {
		$out = array();
		foreach (self::all("SHOW TABLES") as $row) {
			$out[] = array_shift($row);
		}
		return $out;
	}

	static public function tableExists($name) {
		return in_array($name, self::tables());
	}

	static public function count($table, $cond=null) {
		return self::value($table, 'COUNT(*)', $cond);
	}
	
	/*
	 * TRANSACTION
	 */

	static public function begin() {
		return DB::$pdo->beginTransaction();
	}
	
	static public function commit() {
		return DB::$pdo->commit();
	}
	
	static public function rollback() {
		return DB::$pdo->rollBack();
	}
	
	/*
	 * WRITING
	 */

	static private function insertValuesSQL($data) {
		return '('.join(', ', self::quote($data)).')';
	}

	static public function insert($sql, $data=false) {
		if ($data) {
			if (is_array(current($data))) {
				$keys = array_keys(current($data));
			}
			else {
				$keys = array_keys($data);
				$data = array($data);
			}
			$cols = join(', ', array_map(array('static','quoteField'), $keys));
			$values = join(', ', array_map(array('static', 'insertValuesSQL'), $data));
			$sql = "INSERT INTO `{$sql}` ({$cols}) VALUES {$values};";
		}

		$result = static::exec($sql);
		return $result? self::$pdo->lastInsertId(): false;
	}

	static public function update($table, $cond, $data) {
		$pairs = self::updatePairs($data);
		$cond = self::conditionsToSql($cond);
		$sql = "UPDATE ".self::quoteField($table)." SET {$pairs} WHERE {$cond};";
		return static::exec($sql);
	}

	static public function replace($table, $data) {
		$pairs = self::updatePairs($data);
		$sql = "REPLACE INTO `{$table}` SET {$pairs};";
		return static::exec($sql);
	}

	static public function delete($table, $cond) {
		$cond = static::conditionsToSql($cond);
		$sql = "DELETE FROM `{$table}` WHERE {$cond};";
		return static::exec($sql);
	}

	static public function truncate($table) {
		$sql = 'TRUNCATE TABLE '.self::quoteField($table);
		return static::exec($sql);
	}

	static public function updatePairs($data) {
		$map = array('self', 'updatePair');
		return join(', ', array_map($map, $data, array_keys($data)));
	}

	static protected function updatePair($value, $key) {
		return "`{$key}` = ".static::quote($value);
	}


}

class DBExpression {
	
	private $str;
	
	public function __construct($str) {
		$this->str = $str;
	}
	
	public function __toString() {
		return $this->str;
	}
	
}

class DBException extends \Exception {}
class DBQueryException extends DBException {};
