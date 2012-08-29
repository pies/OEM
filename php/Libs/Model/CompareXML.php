<?php
namespace Model;

/**
 * @package Model
 */
class CompareXML {

	private $one, $two;

	public function  __construct($one, $two) {
		$this->one = $one;
		$this->two = $two;
	}

	private function GetTables($xml) {
		$out = array();
		foreach ($xml->table as $table) {
			$out[] = (string) $table['name'];
		}
		return $out;
	}

	private function GetFields($xml) {
		$out = array();
		foreach ($xml->field as $column) {
			$out[] = (string) $column['name'];
		}
		return $out;
	}

	private function GetAttributes($field) {
		$valid_attributes = array('name','type','size','signed','null','auto_increment');
		
		$out = array();
		foreach ($field->attributes() as $kk=>$vv) {
			if (in_array((string)$kk, $valid_attributes)) {
				$out[$kk] = (string) $vv;
			}
		}
		unset($out['extends']);
		ksort($out);

		return $out;
	}

	public function ComparePrimaryKey($name) {
		$one = $this->one->find("//table[@name='{$name}']");
		$two = $this->two->find("//table[@name='{$name}']");

		return array(
			(string) $one['primary_key'],
			(string) $two['primary_key']
		);
	}

	private function ExtractTableIndexes($obj, $name) {
		$obj = $obj->find("//table[@name='{$name}']");
		$out = array();
		if (empty($obj->index)) return $out;
		foreach ($obj->index as $index) {
			$out[] = (string) $index;
		}
		return $out;
	}

	public function CompareIndexes($name) {
		$one = $this->ExtractTableIndexes($this->one, $name);
		$two = $this->ExtractTableIndexes($this->two, $name);
		return array(array_diff($one, $two), array_diff($two, $one));
	}

	private function ExtractTableUniques($obj, $name) {
		$obj = $obj->find("//table[@name='{$name}']");
		$out = array();
		if (empty($obj->unique)) return $out;
		foreach ($obj->unique as $index) {
			$out[] = (string) $index;
		}
		return $out;
	}

	public function CompareUniques($name) {
		$one = $this->ExtractTableUniques($this->one, $name);
		$two = $this->ExtractTableUniques($this->two, $name);
		return array(array_diff($one, $two), array_diff($two, $one));
	}

	public function CompareTables() {
		$tmp_one = $this->GetTables($this->one);
		$tmp_two = $this->GetTables($this->two);
		return array(array_diff($tmp_one, $tmp_two), array_diff($tmp_two, $tmp_one));
	}

	public function CompareColumns($name) {
		$one = $this->one->find("//table[@name='{$name}']");
		$two = $this->two->find("//table[@name='{$name}']");

		if (!$one || !$two) return false;
		$one = $this->GetFields($one);
		$two = $this->GetFields($two);

		return array(array_diff($one, $two), array_diff($two, $one));
	}

	public function CompareAttributes($table_name, $column_name) {
		$one = $this->one->find("//table[@name='{$table_name}']//field[@name='{$column_name}']");
		$two = $this->two->find("//table[@name='{$table_name}']//field[@name='{$column_name}']");

		if (!$one || !$two) return false;

		$attr_one = array_filter($this->GetAttributes($one));
		$attr_two = array_filter($this->GetAttributes($two));

		return array(array_diff_assoc($attr_one, $attr_two), array_diff_assoc($attr_two, $attr_one));
	}
}
