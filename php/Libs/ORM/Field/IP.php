<?php
namespace ORM\Field;

class IP extends Base {

	public function __construct($config=null) {
		$this->value = request_origin();
		parent::__construct($config);
	}

	public function set($value) {
		if (is_string($value)) {
			$new_value = ip2long($value);
			$value = $new_value? $new_value: (int) $value;
		}

		return parent::set($value);
	}

	public function valid($value) {
		return (ip2long($value) || (int) $value) && parent::valid($value);
	}

	public function html() {
		return long2ip($this->value);
	}

}