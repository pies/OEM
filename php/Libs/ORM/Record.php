<?php
namespace ORM;

use Database\DB;
use Core\Framework, Core\Str, Core\XML;

abstract class Record {

	const STATE_FRESH = 1;
	const STATE_LOADED = 2;
	const STATE_TAINT = 3;

	protected $table;
	protected $id_field = 'id';
	protected $state = self::STATE_FRESH;

	protected $data;
	protected $config;

	public function __construct($id=null) {
		if (!$this->table) $this->table = Str::underscore(\get_class($this));
		if (!$this->table) throw new OrmException("Table name '{$this->table}' not valid, error.");

		$this->config = Framework::Model($this->table);
		if (!$this->config) throw new OrmException("Configuration couldn't be loaded, error.");

		foreach ($this->config->field as $field) {
			$name = (string) $field['name'];
			$class = (string) $field['class'];
			$class_name = '\\'.__NAMESPACE__."\\Field\\".($class? $class: 'Text');
			$this->data[$name] = new $class_name($name, $field);
			$this->data[$name]->parent = $this;
		}

		if ($id !== null) {
			$this->load($id);
		}
	}

	public function __get($name) {
		return $this->data[$name];
	}

	public function __set($name, $value) {
		return $this->set($name, $value);
	}

	public function get($name) {
		return $this->data[$name]->raw;
	}

	public function set($name, $value) {
		$this->state = self::STATE_TAINT;
		return $this->data[$name]->set($value);
	}

	public function load($new_id=null) {
		if ($new_id !== null) $this->set($this->id_field, $new_id);
		$id = $this->data[$this->id_field]->raw;
		$fields = join(',', array_keys($this->data));
		$conditions = "{$this->id_field} = {$id}";
		$row = DB::row($this->table, $fields, $conditions);
		if (empty($row)) throw new OrmException("Record with id {$id} not found.");
		$this->fromArray($row);
		$this->state = self::STATE_LOADED;
	}

	public function save($new_data=null) {
		if ($new_data !== null) $this->fromArray($new_data);
		if ($this->state == self::STATE_LOADED) return true;
		$id = $this->data[$this->id_field]->raw;

		$data = array();
		foreach ($this->data as $key=>$value) {
			if ($this->id_field == $key) continue;
			$data[$key] = $value->raw;
		}

		if ($id) {
			return DB::update($this->table, $id, $data, $this->id_field);
		}
		else {
			$new_id = DB::insert($this->table, $data);
			return $this->set($this->id_field, $new_id);
		}

	}

	public function fromArray($data) {
		foreach ($data as $key=>$value) {
			$this->set($key, $value);
		}
	}

	public function asArray() {
		$out = array();
		foreach ($this->data as $key=>$value) {
			$out[$key] = $value->raw();
		}
		return $out;
	}

	public function asXML() {
		$tmp = new XML("<{$this->table}/>");
		foreach ($this->data as $key=>$value) {
			$tmp->append($value->xml);
		}
		return $tmp->asXML(true, false, true);
	}

	public function debug() {
		$out = array();
		foreach ($this->data as $key=>$value) {
			$out[$key] = $value->debug();
		}
		return $out;
	}

}

class OrmException extends \Exception {};
class ValidationException extends OrmException {};
