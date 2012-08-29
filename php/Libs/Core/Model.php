<?php
namespace Core;

use Database\DB;
use Misc\Arr;

abstract class Model {

	protected $table = null;
	protected static $_table = null;
	protected $id_field = null;

	protected $id = null;
	protected $data = array();
	protected $config = null;
	protected $messages = null;
	protected $fields = array();
	
	protected $exists = null;

	public function __construct($config=null) {
		if (!empty(static::$_table)) {
			$this->table = static::$_table;
		}
		
		if (empty($config) && empty($this->table)) {
			throw new ModelException("Table name not known.");
		}
		$this->config = $config? $config: model($this->table);

		if (empty($this->config)) {
			throw new ModelException("Configuration not found for '{$this->table}'.");
		}

		$this->messages = config()->messages;

		if (empty($this->id_field)) {
			$this->id_field = (string) $this->config['primary_key'];
		}
		
		foreach ($this->config->field as $field) {
			$name = (string) $field['name'];
			if ($name == $this->id_field) continue;
			$this->fields[] = $name;
		}
	}

	public static function Factory($id=null) {
		$obj = new static;
		if ($id) $obj->load($id);
		return $obj;		
	}
	
	public function __get($key) {
		$getter = 'get_'.$key;
		if ($this->methodExists($getter)) {
			return $this->$getter($key);
		}
		else {
			if ($key != $this->id_field && !in_array($key, $this->fields)) {
				throw new ModelException("Field not in model config: '{$key}'");
			}
			return $key == $this->id_field?
				$this->id:
				$this->data[$key];
		}
	}

	public function __set($key, $value) {
		if (!in_array($key, $this->fields)) {
			throw new ModelException("Field not in model config: `{$this->table}`.`{$key}`");
		}

		$setter = 'set_'.$key;
		if ($this->methodExists($setter)) {
			return $this->$setter($value);
		}
		else {
			return $this->data[$key] = $value;
		}
	}

	public function load($id) {
		$this->id = $id;
		$this->data = DB::row($this->table, '*', $this->getIdCondition());
		$this->exists = is_array($this->data);
		$this->unset_id_fields();
		return $this;
	}
	
	public function exists() {
		return $this->exists;
	}
	
	public function loadArray($data) {
		$this->id = $data[$this->id_field];
		$this->data = $data;
		$this->unset_id_fields();
		return $this;
	}

	private function unset_id_fields() {
		$id_fields = is_array($this->id_field)? $this->id_field: array($this->id_field);
		foreach ($id_fields as $id_field) {
			unset($this->data[$id_field]);
		}
	}

	private function update() {
		$this->beforeUpdate();
		$ok = (bool) DB::update($this->table, $this->getIdCondition(), $this->data);
		return $ok? $this->afterUpdate(): false;
	}

	protected function create($force_id=false) {
		$this->beforeCreate();

		$data = $this->data;
		if (is_array($this->id_field)) {
			foreach ($this->id_field as $kk=>$vv) {
				$data[$vv] = $this->id[$kk];
			}
			DB::insert($this->table, $data);
		}
		elseif ($force_id) {
			$data[$this->id_field] = $this->id;
			DB::insert($this->table, $data);
		}
		else {
			$this->id = DB::insert($this->table, $data);
		}

		return $this->id? $this->afterCreate(): false;
	}

	public function save($force_create=false, $force_id=false) {
		return $this->id && !$force_create? 
			$this->update():
			$this->create($force_id);
	}

	public function delete() {
		$this->beforeDelete();
		$ok = (bool) DB::delete($this->table, $this->getIdCondition());
		return $this->id? $this->afterDelete(): false;
	}


	protected function beforeCreate() { return true; }
	protected function afterCreate()  { return true; }

	protected function beforeUpdate() { return true; }
	protected function afterUpdate()  { return true; }

	protected function beforeDelete() { return true; }
	protected function afterDelete()  { return true; }

	protected function getIdCondition() {
		if (!is_array($this->id_field)) {
			return array($this->id_field => $this->id);
		}
		else {
			$out = array();
			foreach ($this->id_field as $kk=>$vv) {
				$out[$vv] = $this->id[$kk];
			}
			return $out;
		}
	}

	protected function methodExists($name) {
		return method_exists($this, $name);
	}

	public function fields($data=null) {
		return $data !== null? Arr::only($data, $this->fields): $this->fields;
	}

	public function set($data) {
		$errors = array();
		$copy = $this->data;
		foreach ($data as $key=>$value) {
			try {
				$this->$key = $value;
			}
			catch (ModelException $e) {
				$errors[$key] = $e;
			}
		}
		if ($errors) {
			$this->data = $copy;
		}
		return $errors;
	}

	public function get() {
		return $this->data;
	}
}

class ModelException extends \Exception {};

class ModelValidationException extends ModelException {
	public function __toString() {
		return $this->getMessage();
	}
};
