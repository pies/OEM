<?php
namespace Database;

use \PDO as PDO;
use \Core\Str;

/**
 * Facilitates database access as a static class. The downside of it being 
 * static is that it can only be connected to a single database at a time. The 
 * upside is that it's very convenient to be able to directly call its static 
 * methods from anywhere in the code.
 * 
 * Includes many helper methods to make common operations -- like fetching a row 
 * or a bunch of rows into an array, or inserting a row and getting its 
 * auto-increment ID -- very easy.
 * 
 * In many cases it automatically quotes field names and values to help 
 * prevent SQL injection and make it easier to handle unconventional table
 * definitions.
 * 
 */
class DB {

	/**
	 * The PDO connection object.
	 * 
	 * @var PDO
	 */
	static public $pdo = null;

	/**
	 * Default PDO fetch mode to use.
	 * 
	 * @var int
	 */
	static public $fetchMode = PDO::FETCH_ASSOC;

	static public $lastSQL = null;

	/*
	 * UTILITY METHODS
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

	/**
	 * Executes an SQL query and throws an exception if there were any execution 
	 * errors.
	 * 
	 * @param string $sql The SQL query to execute
	 * @return boolean Success
	 * @throws DBQueryException
	 */
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

	/**
	 * Returns error information if there was a query error or false if there 
	 * wasn't.
	 * 
	 * @return array PDO error information
	 */
	static public function error() {
		$code = self::$pdo->errorCode();
		return $code && ($code !== '00000')?
			self::$pdo->errorInfo():
			false;
	}

	static public function log($sql, $time=false) {
		DBLog::add($sql, $time);
	}

	/**
	 * Routes a query to the appropriate handler by checking if it begins with
	 * SELECT or INSERT.
	 * 
	 * @param string $sql The SQL query to execute
	 * @return type
	 */
	static public function query($sql) {
		if (strpos(trim(strtoupper($sql)), 'SELECT ') === 0) {
			return self::select($sql);
		}
		elseif (strpos(strtoupper($sql), 'INSERT ') === 0) {
			return self::insert($sql);
		}
		else {
			return (bool) static::exec($sql);
		}
	}

	/**
	 * Database-quotes a variable or an array of variables. Does not quote 
	 * objects which is handy if you don't want something quoted, like NULL.
	 * 
	 * @param mixed $value Value or an array of values
	 * @return mixed
	 */
	static public function quote($value) {
		if (is_object($value) && get_class($value) == 'Database\\DBExpression') {
			return (string) $value;
		}
		return is_array($value)?
			array_map(array('self', 'quote'), $value):
			self::$pdo->quote($value);
	}

	/**
	 * Quotes a field name or an array of field names for SQL, like a table or 
	 * column name. Correctly handles complex names like "table.column".
	 * 
	 * @param mixed $name Field name or an array of field names
	 * @return mixed
	 */
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

	/**
	 * Quotes and collates an array of field names, like a list of column names
	 * for an INSERT query.
	 * 
	 * @param array $fields Array of field names
	 * @return array
	 */
	static protected function fieldsToSql($fields) {
		if (!is_array($fields)) return $fields;
		return join(', ', self::quoteField($fields));
	}

	/**
	 * Turns an array of SQL 'WHERE' conditions into a string. Appropriate 
	 * condition formats are:
	 * 
	 * $conditions = array(
	 *	 'name' => 'value',               // SQL: `name` = 'value'
	 *   'name' => array('value', 'IS'),  // SQL: `name` IS 'value'
	 *   'name' => new DB::expr('NULL'),  // SQL: `name` = NULL
	 *   'name' => array('foo','bar'),    // SQL: `name` = ('foo','bar')
	 *   'name' => array(array('foo','bar'), 'IN')
	 * );                                 // SQL: `name` IN ('foo','bar')
	 * 
	 * // result
	 * DB::conditionsToSql($conditions, 'OR') ===
	 *   "((`name` = 'value') OR (`name` IS 'value') OR (`name` = NULL) OR 
	 *    (`name` = ('foo','bar')) OR (`name` IN ('foo','bar')))"
	 * 
	 * @param array $cond Conditions for WHERE query
	 * @param string $joiner String to join conditions with
	 * @return string
	 */
	static public function conditionsToSql($cond, $joiner='AND') {

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
		return '('.join(') '.$joiner.' (', $out).')';
	}

	/**
	 * Formats an SQL condition for WHERE. Helper function for 
	 * DB::conditionsToSql().
	 * 
	 * @param string $field
	 * @param mixed $value
	 * @param string $sign
	 * @return string
	 */
	static protected function quoteCondition($field, $value, $sign='=') {
		$quoted_field = self::quoteField($field);
		$quoted_value = self::quote($value);
		if (is_array($quoted_value)) {
			$quoted_value = '('.join(',', $quoted_value).')';
		}
		return "{$quoted_field} {$sign} {$quoted_value}";
	}

	/**
	 * Shortcut for creating an expression object which is used instead of a 
	 * value when you don't want the value to be quoted.
	 * 
	 * @param string $str The value string
	 * @return \Libs\DBExpression
	 */
	static public function expr($str) {
		return new DBExpression($str);
	}

	
	/*
	 * READING
	 */

	/**
	 * Queries the database and returns an iterable DBResult object with 
	 * query results.
	 * 
	 * You can either provide the SQL query as a string, or as table name,
	 * fields list, conditions (see DB::conditionsToSql() docs for info) and
	 * offset/limit.
	 * 
	 * Can only be used for SELECT queries.
	 * 
	 * @param string $sql_or_table SQL query or table name
	 * @param mixed $fields Array or string of field names
	 * @param array $conditions Conditions for WHERE
	 * @param string $limit Either limit or offset,limit
	 * @return \Libs\DBResult
	 * @throws DBQueryException
	 */
	static public function select($sql_or_table, $fields=null, $conditions=null, $limit=false) {
		if ($fields) {
			$fields = self::fieldsToSql($fields);
			$conditions = $conditions? ' WHERE '.self::conditionsToSql($conditions): '';
			$limit = $limit? " LIMIT {$limit}": '';
			$table = self::quoteField($sql_or_table);
			$sql = "SELECT {$fields} FROM {$table}{$conditions}{$limit};";
		}
		else {
			$sql = $sql_or_table;
		}
		
		$query = self::$pdo->prepare($sql);

		$error = self::error();
		if ($error) {
			$message = "Database query error: ({$error[0]}/{$error[1]}) {$error[2]} in query [{$sql}]";
			throw new DBQueryException($message, E_USER_WARNING);
			return false;
		}

		$result = new DBResult($query, self::$fetchMode);
		self::$lastSQL = (string) $result->query->queryString;
		$result->callback = function($sql, $time){
			$class = __CLASS__;
			$class::log($sql, $time);
		};
		return $result;
	}

	/**
	 * Queries the database and returns an array of all results.
	 * 
	 * You can either provide the SQL query as a string, or as table name,
	 * fields list, conditions (see DB::conditionsToSql() docs for info) and
	 * offset/limit.
	 * 
	 * Can only be used for SELECT queries.
	 * 
	 * @param string $sql_or_table SQL query or table name
	 * @param mixed $fields Array or string of field names
	 * @param array $conditions Conditions for WHERE
	 * @param string $limit Either limit or offset,limit
	 * @return array
	 */
	static public function all($sql_or_table, $fields=null, $conditions=null, $limit=false) {
		$out = array();
		$query = self::select($sql_or_table, $fields, $conditions, $limit);
		foreach ($query as $row) {
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * Queries the database and returns a single row of results.
	 * 
	 * You can either provide the SQL query as a string, or as table name,
	 * fields list, conditions (see DB::conditionsToSql() docs for info) and
	 * offset/limit.
	 * 
	 * Can only be used for SELECT queries.
	 * 
	 * @param string $sql_or_table SQL query or table name
	 * @param mixed $fields Array or string of field names
	 * @param array $conditions Conditions for WHERE
	 * @return array
	 */
	static public function row($sql_or_table, $fields=null, $conditions=null) {
		$query = self::select($sql_or_table, $fields, $conditions, 1);
		foreach ($query as $row) {
			return $row;
		}
		return null;
	}

	/**
	 * Queries the database and returns the first value from the first row of 
	 * results.
	 * 
	 * You can either provide the SQL query as a string, or as table name,
	 * fields list, conditions (see DB::conditionsToSql() docs for info) and
	 * offset/limit.
	 * 
	 * Can only be used for SELECT queries.
	 * 
	 * @param string $sql_or_table SQL query or table name
	 * @param mixed $fields Array or string of field names
	 * @param array $conditions Conditions for WHERE
	 * @return string
	 */
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

	/**
	 * Begins a database transaction.
	 * 
	 * @return bool Succes or failure
	 */
	static public function begin() {
		return DB::$pdo->beginTransaction();
	}

	/**
	 * Commits a database transaction.
	 * 
	 * @return bool Succes or failure
	 */
	static public function commit() {
		return DB::$pdo->commit();
	}
	
	/**
	 * Rolls back a database transaction.
	 * 
	 * @return bool Succes or failure
	 */
	static public function rollback() {
		return DB::$pdo->rollBack();
	}
	
	
	/*
	 * WRITING
	 */

	/**
	 * Converts an array of values into a string of quoted values for inserting.
	 * 
	 * @param array $data Values to convert
	 * @return string
	 */
	static private function insertValuesSQL($data) {
		return '('.join(', ', self::quote($data)).')';
	}

	/**
	 * Performs an INSERT operation and returns the insert ID.
	 * 
	 * You can either provide the SQL as a string, or a table name and an array
	 * of keys=>values.
	 * 
	 * @param string $sql_or_table The SQL or table name
	 * @param array $data Array of keys=>values
	 * @return int Insert ID
	 */
	static public function insert($sql_or_table, $data=false) {
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
			$sql = "INSERT INTO ".static::quoteField($sql_or_table)." ({$cols}) VALUES {$values};";
		}
		else {
			$sql = $sql_or_table;
		}

		$result = static::exec($sql);
		return $result? self::$pdo->lastInsertId(): false;
	}

	/**
	 * Generates and performs an UPDATE query.
	 * 
	 * @param string $table Table name
	 * @param array $cond Query conditions 
	 * @param array $data Array of keys=>values
	 * @return int Number of updated fields
	 */
	static public function update($table, $cond, $data) {
		$pairs = self::updatePairs($data);
		$cond = self::conditionsToSql($cond);
		$sql = "UPDATE ".self::quoteField($table)." SET {$pairs} WHERE {$cond};";
		return static::exec($sql);
	}

	/**
	 * Generates and performs an REPLACE query.
	 * 
	 * @param string $table Table name
	 * @param array $data Array of keys=>values
	 * @return int Number of replaced fields
	 */
	static public function replace($table, $data) {
		$pairs = self::updatePairs($data);
		$sql = "REPLACE INTO ".static::quoteField($table)." SET {$pairs};";
		return static::exec($sql);
	}

	/**
	 * Generates and performs an DELETE query.
	 * 
	 * @param string $table Table name
	 * @param array $cond Query conditions 
	 * @return int Number of deleted fields
	 */
	static public function delete($table, $cond) {
		$cond = static::conditionsToSql($cond);
		$sql = "DELETE FROM `{$table}` WHERE {$cond};";
		return static::exec($sql);
	}

	/**
	 * Generates and performs an TRUNCATE query.
	 * @param string $table Table name
	 * @return bool Success
	 */
	static public function truncate($table) {
		$sql = 'TRUNCATE TABLE '.self::quoteField($table);
		return static::exec($sql);
	}

	/**
	 * Generates a string of update pairs from an array of keys=>values.
	 * 
	 * @param array $data Array of keys=>values
	 * @return string
	 */
	static public function updatePairs($data) {
		$map = array('self', 'updatePair');
		return join(', ', array_map($map, $data, array_keys($data)));
	}

	/**
	 * Generates an key/value pair association SQL string.
	 * 
	 * @param string $value
	 * @param mixed $key
	 * @return string
	 */
	static protected function updatePair($value, $key) {
		return static::quoteField($key).' = '.static::quote($value);
	}

}

/**
 * A helper class to allow for values not to be database-quoted, to handle 
 * values such as NULL.
 */
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
