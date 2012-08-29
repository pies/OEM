<?php
namespace Model;

/**
 * @package Model
 */
class XMLToSQL {

	const DEFAULT_MYSQL_ENGINE = 'InnoDB';
	const DEFAULT_MYSQL_CHARSET = 'utf8';
	const DEFAULT_MYSQL_COLLATE = 'utf8_polish_ci';
	const DEFAULT_SIGNED = 'n';
	const DEFAULT_NULL = '';

	protected $model;
	protected $table;

	public function  __construct($model=false) {
		if ($model) {
			$config = new Config($model->asXML());
			$this->model = $config->get();
			$this->table = $this->model->table;
		}
		else {
			$this->model = \Core\Framework::Model();
		}
	}

	public function AsSQL() {
		$sql = array();
		foreach ($this->model->table as $table) {
			$name = (string) $table['name'];
			$sql[$name] = self::TableSQL($table);
		}
		return $sql;
	}

	public function TableSQL($table) {

		$this->table = $table;

		$sql = "CREATE TABLE `{$table['name']}` (\n\t";

		$fields = array();
		foreach ($this->table->field as $field) {
			$name = (string) $field['name'];
			$fields[] = $this->FieldSQL($field);
		}
		$sql .= join(",\n\t", $fields);

		foreach ($this->GetUniques() as $unique) {
			$sql .= ",\n\tUNIQUE KEY ({$unique})";
		}

		$indices = array();
		foreach ($this->GetIndices() as $index) {
			$indices[] = $this->IndexSQL($index);
		}
		$sql .= $indices? ",\n\t".join(",\n\t", $indices): '';

		$primary_key = $this->table['primary_key'];
		if ($primary_key) {
			$primary = self::QuoteFields($primary_key);
			$sql .= ",\n\tPRIMARY KEY ({$primary})";
		}

		$engine = $this->GetEngine();
		$charset = $this->GetCharset();
		$collate = $this->GetCollate();
		$sql .= "\n) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};\n";

		return $sql;
	}

	public function IndexSQL($index, $unique=false) {
		return ($unique? 'UNIQUE ': '')."KEY ({$index})";
	}

	private function isYes($value) {
		$value = (string) $value;
		return $value == 'true' || $value == 'y' || $value == '1';
	}

	private function isNo($value) {
		$value = (string) $value;
		return $value == 'false' || $value == 'n' || $value == '0';
	}

	public function FieldSQL($input) {
		$valid_attributes = array('name','type','size','signed','null','auto_increment','default');

		$field = array();
		foreach ($valid_attributes as $attribute) {
			$field[$attribute] = (string) $input[$attribute] ?: null;
		}

		$name = (string) $field['name'];
		$type = $this->GetType($field);

		$sql = "`{$name}` \t{$type}";

		$size = (string) $field['size'];
		if ($type == 'ENUM' || $type == 'SET') {
			$size = $this->Quote($size);
		}
		if ($size) $sql .= "({$size})";

		if ($type == 'INT') {
			$signed = $this->GetSigned($field);
			if ($this->isYes($signed)) $sql .= " \tSIGNED";
			if ($this->isNo($signed)) $sql .= " \tUNSIGNED";
		}

		if ($this->isYes($field['auto_increment'])) {
			$sql .= " \tAUTO_INCREMENT";
		}

		$default = $this->GetDefault($field);
		$null = $this->GetNull($field);

		if ($this->isNo($null)) {
			$sql .= " \tNOT NULL";
		}

		if ($this->isYes($null)) {
			$sql .= " \tNULL";
		}

		if ($default !== false) {
			$sql .= " \tDEFAULT {$default}";
		}
		
		return $sql;
	}

	private function GetEngine() {
		$engine = (string) $this->table['engine']
			or $engine = (string) $this->model['engine']
			or $engine = self::DEFAULT_MYSQL_ENGINE;
		return $engine;
	}

	private function GetCharset() {
		$charset = (string) $this->table['charset']
			or $charset = (string) $this->model['charset']
			or $charset = self::DEFAULT_MYSQL_CHARSET;
		return $charset;
	}

	private function GetCollate() {
		$collate = (string) $this->table['collate']
			or $collate = (string) $this->model['collate']
			or $collate = self::DEFAULT_MYSQL_COLLATE;
		return $collate;
	}

	private function GetUniques() {
		return $this->QuoteFields((array) $this->table->unique);
	}

	private function GetIndices($input=array()) {
		return $this->QuoteFields((array) $this->table->index);
	}

	private function GetSigned($field) {
		$signed = (string) $field['signed']
			or $signed = (string) $this->table['signed']
			or $signed = (string) $this->model['signed']
			or $signed = self::DEFAULT_SIGNED;
		return $signed;
	}

	private function GetNull($field) {
		$null = (string) $field['null']
			or $null = (string) $this->table['null']
			or $null = (string) $this->model['null']
			or $null = self::DEFAULT_NULL;
		return $null;
	}

	private function GetDefault($field) {
		$type = $this->GetType($field);
		$null = $this->GetNull($field);

		$default = isset($field['default'])?
			($type=='INT'? (int) $field['default']: $field['default']):
			false;

		if ($null == 'y' && $default === false) {
			$default = NULL;
		}

		if ($default !== false) {
			$default = $default===NULL? 'NULL': self::Quote($default);
		}

		return $default;
	}

	private function GetType($field) {
		return strtoupper($field['type']);
	}

	private static function Quote($string) {
		if (is_array($string)) {
			return array_map(array('self',__FUNCTION__), $string);
		}
		return join(', ', array_map(array('\Database\DB','quote'), array_map('trim', explode(',', $string))));
	}

	private static function QuoteFields($string) {
		if (is_array($string)) {
			return array_map(array('self',__FUNCTION__), $string);
		}
		return join(', ', array_map(array('\Database\DB','quoteField'), array_map('trim', explode(',', $string))));
	}

}
