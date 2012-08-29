<?php
namespace ORM\Field;

class Int extends Base {

	public function set($value) {
		return parent::set((int)$value);
	}

	public function debug() {
		return (int) $this->value;
	}

	public function valid($value) {
		return is_int($value) && parent::valid($value);
	}

}