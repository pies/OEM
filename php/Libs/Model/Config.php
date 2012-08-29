<?php
namespace Model;

use Core\XMLModel;

/**
 * @package Model
 */
class Config {

	public $xml;

	public function __construct($xml_string, $skip_extend=false) {
		$this->xml = new XMLModel($xml_string);
		if (!$skip_extend) $this->xml->extend();

		foreach ($this->xml->table as $table) {
			if (!$table['primary_key']) {
				$table['primary_key'] = 'id';
			}

			foreach ($table->field as $field) {
				if (empty($field['default']) && empty($field['null'])) {
					$field['null'] = 'y';
				}
				elseif (!empty($field['default'])) {
					$field['null'] = 'n';
				}
			}
		}
	}

	public function __get($table) {
		return $this->get($table);
	}

	public function get($table=null) {
		return $table?
			$this->xml->find("//table[@name='{$table}']"):
			$this->xml;
	}

	public function getSQL($table=null) {
		$to_sql = new Model_XMLToSQL();
		return $to_sql->TableSQL($this->get(table));
	}

}