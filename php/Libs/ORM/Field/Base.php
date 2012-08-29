<?php
namespace ORM\Field;

use Exception;
use Core\XML;

abstract class Base {

	public $name = null;
	public $parent = null;

	protected $value = null;
	protected $required = null;

	protected $min = null;
	protected $max = null;

	protected $regexp = null;

	public function __construct($name, $config=null) {
		$this->name = $name;
		
		if ($config !== null) {
			$this->config = $config;
			$this->required = ((string)$config['null'] == 'n');
		}
	}

	public function  __get($name) {
		switch ($name) {
			case 'html': return $this->html();
			case 'sql':  return $this->sql();
			case 'raw':  return $this->raw();
			case 'xml':  return $this->xml();
			case 'debug': return $this->debug();
			default:     return false;
		}
	}

	public function set($value) {
		if ($this->valid($value)) {
			return $this->value = $value;
		}
		else {
			throw new ValidationException('Value invalid: '.$value);
		}
	}

	public function valid($value) {
		if (!is_null($this->min) && ($this->size($value) < $this->min)) return false;
		if (!is_null($this->max) && ($this->size($value) > $this->max)) return false;

		if ($value && !is_null($this->regexp) && !preg_match($this->regexp, (string)$value)) return false;

		return !$this->required || !empty($value);
	}

	public function html() {
		return htmlentities($this->value);
	}

	public function debug() {
		return $this->html();
	}

	public function sql() {
		return \Database\DB::quote($this->value);
	}

	public function raw() {
		return $this->value;
	}

	public function xml() {
		$doc = new XML("<tmp/>");
		return $doc->add($this->name, $this->raw());
	}

	public function __toString() {
		$tmp = $this->debug();
		$str = is_string($tmp)? "'{$tmp}'": $tmp;
		return '['.get_class($this)." \${$this->name} = {$str}]";
	}

	protected function size($value) {
		return (int) $value;
	}

}

class ValidationException extends Exception {};