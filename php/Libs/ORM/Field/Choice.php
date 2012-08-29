<?php
namespace ORM\Field;

class Choice extends Base {

	public function valid($value) {
		return in_array($value, explode(',', $this->config['size']));
	}

}
