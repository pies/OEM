<?php
namespace Html;

class Tag implements \ArrayAccess {

	private $attributes = array();
	private $content = null;
	private $name;

	public function __construct($name, $content=null) {
		$this->name = $name;
		$this->content = $content;
	}

	public function __toString() {
		$map = function($value, $name) { 
			return $name.'="'.addcslashes($value, '"\\').'"';
		};

		$attributes = $this->attributes? ' '.arr($this->attributes)->map($map)->join(' '): '';

		if ($this->content != null) {
			return "<{$this->name}{$attributes}>{$this->content}</{$this->name}>";
		}
		else {
			return "<{$this->name}{$attributes}/>";
		}
	}

	public function offsetExists($name) {
		return isset($this->attributes[$name]);
	}

	public function offsetGet($name) {
		return $this->attributes[$name];
	}

	public function offsetSet($name, $value) {
		return $this->attributes[$name] = (string) $value;
	}

	public function offsetUnset($name) {
		unset($this->attributes[$name]);
	}

	public function setAttributes($array, $dont_overwrite=false) {
		$this->attributes = $dont_overwrite?
			$this->attributes + $array:
			$array + $this->attributes;
		return $this;
	}
}
