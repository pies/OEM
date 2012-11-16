<?php
namespace Html;

class Form {

	public $data;
	public $errors;
	public $id_prefix = '';

	public function  __construct($data=array(), $errors=array(), $id_prefix='') {
		$this->data = $data;
		$this->errors = $errors;
		$this->id_prefix = $id_prefix;
	}

	private static function ini_get_bytes($name) {
		$val = trim(ini_get($name));
		$last = strtolower(substr($val, -1));
		switch ($last) {
			case 'g': return $val*1024*1024*1024;
			case 'm': return $val*1024*1024;
			case 'k': return $val*1024;
			default: return (int) $val;
		}
	}

	public static function max_upload_size() {
		$upload = self::ini_get_bytes('upload_max_filesize');
		$post = self::ini_get_bytes('post_max_size');
		$memory = self::ini_get_bytes('memory_limit');
		return min($upload, $post, $memory);
	}

	protected function value($name) {
		return isset($this->data[$name])? $this->data[$name]: null;
	}
	
	protected function specialchars($string) {
		$pairs = array(
			'&' => '&amp;',
			'"' => '&quot;',
			'<' => '&lt;',
			'>' => '&gt;',
		);
		return str_replace(array_keys($pairs), array_values($pairs), $string);
	}
	
	protected function attributes($pairs) {
		$out = array();
		foreach ($pairs as $key=>$value) {
			$out[] = $key.'="'.$this->specialchars($value).'"';
		}
		return $out? ' '.join(' ', $out): '';
	}

	protected function autoClass($attr) {
		$class = '';
		switch (strtolower($attr['type'])) {
			case 'submit':   $class = 'Submit';   break;
			case 'text':     $class = 'Text';     break;
			case 'password': $class = 'Password'; break;
			case 'checkbox': $class = 'Checkbox'; break;
			case 'radio':    $class = 'Radio';    break;
			case 'file':     $class = 'File';     break;
			case 'button':   $class = 'Button';   break;
		}
		return trim($class.' '.(isset($attr['class'])? $attr['class']: ''));
	}

	protected function element($tag, $attr=array(), $contents=false, $dont_close=false) {
		if (isset($attr['type'])) {
			$class = $this->autoClass($attr);
			if ($class) $attr['class'] = $class;
		}
			
		$attrs = $this->attributes($attr);
		return $contents !== false?
			"<{$tag}{$attrs}>{$contents}</{$tag}>":
			"<{$tag}{$attrs}".($dont_close? '': '/').">";
	}

	protected function options($options, $selected=false) {
		if (!is_array($selected)) {
			$selected = array($selected);
		}

		$output = array();
		foreach ($options as $value=>$label) {
			$attr = array();
			$attr['value'] = $value;
			if (in_array($value, $selected)) $attr['selected'] = 'selected';
			$output[] = $this->element('option', $attr, htmlspecialchars($label));
		}
		return join("\n", $output);
	}

	
	public function start($action=null, $method='post', $attr=array()) {
		$attr['action'] = $action? $action: URL_CURRENT;
		
		if ($method == 'file') {
			$attr['enctype'] = 'multipart/form-data';
			$attr['method'] = 'post';
			$max = self::max_upload_size();
			return $this->element('form', $attr, false, true)."\n".$this->hidden('MAX_FILE_SIZE', $max);
		}
		else {
			$attr['method'] = $method;
			return $this->element('form', $attr, false, true);
		}

	}

	public function end() {
		return '</form>';
	}

	public function input($name, $size=null, $attr=array()) {
		$attr['type'] = 'text';
		$attr['name'] = $name;
		if ($size) $attr['size'] = $size;
		$attr['value'] = $this->value($name);
		return $this->element('input', $attr);
	}

	public function password($name, $attr=array()) {
		$attr['type'] = 'password';
		$attr['name'] = $name;
		$attr['value'] = $this->value($name);
		return $this->element('input', $attr);
	}

	public function hidden($name, $value=false, $attr=array()) {
		$attr['type'] = 'hidden';
		$attr['name'] = $name;
		$attr['value'] = $value === false? $this->value($name): $value;
		return $this->element('input', $attr);
	}

	public function select($name, $options, $attr=array()) {
		$options = $this->options($options, $this->value($name));
		$attr['name'] = $name;
		return $this->element('select', $attr, $options);
	}

	public function selectMultiple($name, $options, $size=5, $attr=array()) {
		$options = $this->options($options, $this->value($name));
		$attr['name'] = $name.'[]';
		$attr['multiple'] = 'multiple';
		$attr['size'] = $size;
		return $this->element('select', $attr, $options);
	}

	public function checkbox($name, $label=false, $attr=array()) {
		$attr['type'] = 'checkbox';
		$attr['name'] = $name;
		if ($this->value($name)) $attr['checked'] = 'checked';
		$checkbox = $this->element('input', $attr);
		return $label? $this->element('label', array(), "{$checkbox} {$label}"): $checkbox;
	}

	public function radio($name, $label, $value, $attr=array()) {
		$attr['type'] = 'radio';
		$attr['name'] = $name;
		$attr['value'] = $value;
		if ($this->value($name) == $value) $attr['checked'] = 'checked';
		return $label? 
			$this->element('label', array(), $this->element('input', $attr).' '.$label):
			$this->element('input', $attr);
	}
	
	public function file($name, $attr=array()) {
		$attr['type'] = 'file';
		$attr['name'] = $name;
		return $this->element('input', $attr);
	}

	public function textarea($name, $cols=40, $rows=5, $attr=array()) {
		$attr['name'] = $name;
		$attr['cols'] = $cols;
		$attr['rows'] = $rows;
		$value = $this->value($name);
		return $this->element('textarea', $attr, htmlspecialchars($value));
	}

	public function submit($label, $attr=array()) {
		$attr['type'] = 'submit';
		$attr['value'] = $label;
		return $this->element('input', $attr);
	}

	public function button($label, $attr=array()) {
		$attr['type'] = 'submit';
		return $this->element('button', $attr, $label);
	}

	public function label($name, $label, $attr=array()) {
		$attr['for'] = $name;
		return $this->element('label', $attr, $label);
	}
}