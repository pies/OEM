<?php
namespace ORM\Field;

class Date extends Base {

	protected $format = 'c';

	public function __construct($config=null) {
		$this->value = time();
		parent::__construct($config);
	}

	public function set($value) {
		if (is_string($value)) {
			$new_value = strtotime($value);
			$value = $new_value? $new_value: (int) $value;
		}
		return parent::set($value);
	}

	public function valid($value) {
		return (strtotime($value) || (int) $value) && parent::valid($value);
	}

	public function html() {
		return date($this->format, $this->value);
	}

}