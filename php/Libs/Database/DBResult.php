<?php
namespace Database;

use \Iterator as Iterator;
use \PDO as PDO;

class DBResult implements Iterator {

	public $query = false;
	private $current = false;
	private $key = false;
	private $count = false;
	public $parent = false;
	public $lastSQL;
	public $callback = null;

	public function __construct($query, $mode=PDO::FETCH_ASSOC) {
		$this->query = $query;
		$this->query->setFetchMode($mode);
		$this->lastSQL = (string) $this->query->queryString;
	}

	public function count() {
		if ($this->count === false)
			$this->execute();
		return $this->count;
	}

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

	public function next() {
		$this->key++;
		$this->current = $this->query->fetch();
	}

	public function current() {
		return $this->current;
	}

	public function key() {
		return $this->key;
	}

	public function valid() {
		return (bool) ($this->current !== false);
	}

	public function rewind() {
		$this->execute();
	}
}
