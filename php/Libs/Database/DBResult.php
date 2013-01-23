<?php
namespace Database;

use \Iterator as Iterator;
use \PDO as PDO;

/**
 * Allows for iterating an PDO query result object.
 */
class DBResult implements Iterator {

	/**
	 * The PDO statement object being executed and iterated.
	 * @var PDOStatement
	 */
	public $query = false;
	
	/**
	 * Current row
	 * @var array
	 */
	private $current = false;
	
	/**
	 * Current row index
	 * @var int
	 */
	private $key = false;
	
	/**
	 * Result count
	 * @var int
	 */
	private $count = false;
	public $parent = false;
	public $lastSQL;
	public $callback = null;

	public function __construct($query, $mode=PDO::FETCH_ASSOC) {
		$this->query = $query;
		$this->query->setFetchMode($mode);
		$this->lastSQL = (string) $this->query->queryString;
	}

	/**
	 * Returns the results count from the query.
	 * 
	 * @return int
	 */
	public function count() {
		if ($this->count === false)
			$this->execute();
		return $this->count;
	}

	/**
	 * Executes the statement.
	 * 
	 * @return boolean
	 * @throws DBQueryException
	 */
	public function execute() {
		$start = microtime(true);
		$this->query->execute();
		$end = microtime(true);

		$sql = (string) $this->query->queryString;
		$error_id = (int) $this->query->errorCode();

		if ($this->callback) {
			$time = ($end - $start) * 1000;
			call_user_func_array($this->callback, array($sql, $time));
		}

		if ($error_id) {
			$error = $this->query->errorInfo();
			$message = "Database query error: ({$error[0]}/{$error[1]}) {$error[2]} in query [{$sql}]";
			throw new DBQueryException($message, E_USER_WARNING);
			return false;
		}

		$this->key = 0;
		$this->count = $this->query->rowCount();
		$this->current = $this->query->fetch();
	}

	/**
	 * Fetches the next result in a query.
	 */
	public function next() {
		$this->key++;
		$this->current = $this->query->fetch();
	}

	/**
	 * Returns the current result.
	 * 
	 * @return array
	 */
	public function current() {
		return $this->current;
	}

	/**
	 * Returns current result index.
	 * 
	 * @return int
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * Checks if result was fetched.
	 * 
	 * @return bool
	 */
	public function valid() {
		return (bool) ($this->current !== false);
	}

	/**
	 * Rewinds the query by executing it again.
	 */
	public function rewind() {
		$this->execute();
	}
}
