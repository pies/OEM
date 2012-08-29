<?php
namespace ORM\Field;
use Core\Str, Core\XML;

class Text extends Base {

	protected function size($value) {
		return Str::len($value);
	}

	public function xml() {
		$doc = new XML("<tmp/>");
		return $doc->cdata($this->name, (string) $this->raw());
	}

}
