<?php
namespace OEM\Misc;

use \Iterator;

/**
 * @package Misc
 */
class FileIterator Implements Iterator {
	private $fp, $line = NULL, $pos = 0;

	function __construct($path) {
		$this->fp = fopen($path, "r");
		if (!$this->fp) {
			throw new Exception("Could not open file [{$path}]");
		}
	}

	protected function read() {
		return fgets($this->fp);
	}

	public function rewind() {
		rewind($this->fp);
	}

	public function current() {
		if ($this->line === NULL) {
			$this->line = $this->read();
		}
		return $this->line;
	}

	public function key() {
		if ($this->line === NULL) {
			$this->line = $this->read();
		}

		if ($this->line === FALSE) {
			return FALSE;
		}

		return $this->pos;
	}

	public function next() {
		$this->line = $this->read();
		++$this->pos;
		return $this->line;
	}

	public function valid() {
		return ($this->line !== FALSE);
	}

}
