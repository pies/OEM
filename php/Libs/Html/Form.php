<?php
namespace Html;

/**
 * A HTML form generator.
 */
class Form {

	/**
	 * Variables to use as form element values.
	 * 
	 * @var array
	 */
	public $data;

	/**
	 * If you provide the form element values as an array all the generated
	 * elements will be approprietly filled out/checked/selected.
	 * 
	 * @param array $data The data to fill out the form elements with
	 */
	public function  __construct($data=array()) {
		$this->data = $data;
	}

	/**
	 * Converts a PHP configuration value from the PHP-style short bytes 
	 * notation into an int.
	 * 
	 * @param string $name PHP configuration variable name
	 * @return int Number of bytes
	 */
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

	/**
	 * Returns current maximum uploads by finding the lowest of PHP's POST 
	 * upload size limits.
	 * 
	 * @return int Current maximum upload size
	 */
	public static function max_upload_size() {
		$upload = self::ini_get_bytes('upload_max_filesize');
		$post = self::ini_get_bytes('post_max_size');
		$memory = self::ini_get_bytes('memory_limit');
		return min($upload, $post, $memory);
	}

	/**
	 * Returns current for a specified form element name.
	 * 
	 * @param string $name Form element name
	 * @return mixed
	 */
	protected function value($name) {
		// checkbox
		if (is_array($name)) {
			return isset($this->data[$name[0]])? 
				in_array($name[1], $this->data[$name[0]]): 
				null;
		}
		
		return isset($this->data[$name])? $this->data[$name]: null;
	}
	
	/**
	 * Converts chars that can't be used in HTML attributes to entities. The 
	 * difference between this and htmlspecialchars() is that it doesn't convert
	 * the single quote, which is allowed if HTML attribute is double-quoted.
	 * 
	 * @param string $string String to convert
	 * @return string
	 */
	protected function specialchars($string) {
		$pairs = array(
			'&' => '&amp;',
			'"' => '&quot;',
			'<' => '&lt;',
			'>' => '&gt;',
		);
		return str_replace(array_keys($pairs), array_values($pairs), $string);
	}
	
	/**
	 * Generates a string of HTML tag attributes from an array.
	 * 
	 * @param array $pairs Tag attributes
	 * @return string
	 */
	protected function attributes($pairs) {
		$out = array();
		foreach ($pairs as $key=>$value) {
			$out[] = $key.'="'.$this->specialchars($value).'"';
		}
		return $out? ' '.join(' ', $out): '';
	}

	/**
	 * Automaticall adds appropriate class names to form elements.
	 * 
	 * @param array $attr Tag attributes
	 * @return string
	 */
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

	/**
	 * Generates a HTML tag. Correctly closes tags with contents (i.e. 
	 * <textarea>contents</textarea>), and allows skipping the closing tag if 
	 * you want to generate the opening tag only (i.e. <form> with attributes).
	 * 
	 * @param string $tag Tag name
	 * @param array $attr Tag attributes
	 * @param string $contents Tag contents
	 * @param bool $dont_close Whether to close the tag
	 * @return string
	 */
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

	/**
	 * Generates the <option> tags for <select> elements from an array of 
	 * values.
	 * 
	 * @param array $options Select options
	 * @param mixed $selected The option value to mark as selected
	 * @return string
	 */
	protected function options($options, $selected=false) {
		if (!is_array($selected)) {
			$selected = array($selected);
		}

		$output = array();
		foreach ($options as $value=>$label) {
			$attr = array();
			$attr['value'] = $value;
			if (in_array($value, $selected) || (!$value && !$selected)) $attr['selected'] = 'selected';
			$output[] = $this->element('option', $attr, htmlspecialchars($label));
		}
		return join("\n", $output);
	}

	/**
	 * Generates a <form> tag for chosen form method. The custom 'file' form 
	 * method is handled by adding appropriate attributes and a hidden 
	 * MAX_FILE_SIZE element.
	 * 
	 * @param string $action Form action URL, current URL used if null
	 * @param string $method Form method (get, post or file)
	 * @param array $attr Form tag attributes
	 * @return string
	 */
	public function start($action=null, $method='post', $attr=array()) {
		$attr['action'] = $action === null? URL_CURRENT: $action;
		
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

	/**
	 * Generates a </form> tag. I use it to prevent Netbeans IDE from marking
	 * an actual </form> tag as unopened when I use $form->start().
	 * 
	 * @return string
	 */
	public function end() {
		return '</form>';
	}

	/**
	 * Generates an <input/> tag.
	 * 
	 * @param string $name Element name
	 * @param int $size Input size
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function input($name, $size=null, $attr=array()) {
		$attr['type'] = 'text';
		$attr['name'] = $name;
		if ($size) $attr['size'] = $size;
		$attr['value'] = $this->value($name);
		return $this->element('input', $attr);
	}

	/**
	 * Generates an <input/> tag for passwords.
	 * 
	 * @param string $name Element name
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function password($name, $attr=array()) {
		$attr['type'] = 'password';
		$attr['name'] = $name;
		$attr['value'] = $this->value($name);
		return $this->element('input', $attr);
	}

	/**
	 * Generates a hidden <input/> tag.
	 * 
	 * @param string $name Element name
	 * @param string $value Element value
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function hidden($name, $value=false, $attr=array()) {
		$attr['type'] = 'hidden';
		$attr['name'] = $name;
		$attr['value'] = $value === false? $this->value($name): $value;
		return $this->element('input', $attr);
	}

	/**
	 * Generates a <select> tag with a set of <option>s.
	 * 
	 * @param string $name Element name
	 * @param array $options Values and labels for <option> tags
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function select($name, $options, $attr=array()) {
		$options = $this->options($options, $this->value($name));
		$attr['name'] = $name;
		return $this->element('select', $attr, $options);
	}

	/**
	 * Generates a multiple <select> tag with a set of <option>s.
	 * 
	 * @param string $name Element name
	 * @param array $options Values and labels for <option> tags
	 * @param int $size Number of <option>s to show
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function selectMultiple($name, $options, $size=5, $attr=array()) {
		$options = $this->options($options, $this->value($name));
		$attr['name'] = $name.'[]';
		$attr['multiple'] = 'multiple';
		$attr['size'] = $size;
		return $this->element('select', $attr, $options);
	}

	/**
	 * Generates a checkbox <input/> with a <label>.
	 * 
	 * @param string $name Element name
	 * @param string $label Value for a clickable label
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function checkbox($name, $label=false, $attr=array()) {
		$attr['type'] = 'checkbox';
		if (is_array($name)) {
			$attr['name'] = $name[0].'[]';
			$attr['value'] = $name[1];
		}
		else {
			$attr['name'] = $name;
		}
		if ($this->value($name)) $attr['checked'] = 'checked';
		$checkbox = $this->element('input', $attr);
		return $label? $this->element('label', array(), "{$checkbox} {$label}"): $checkbox;
	}

	/**
	 * Generates a radio <input/> with a <label>.
	 * 
	 * @param string $name Element name
	 * @param string $label Value for a clickable label
	 * @param string $value Radio value
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function radio($name, $label, $value, $attr=array()) {
		$attr['type'] = 'radio';
		$attr['name'] = $name;
		$attr['value'] = $value;
		if ($this->value($name) == $value) $attr['checked'] = 'checked';
		return $label? 
			$this->element('label', array(), $this->element('input', $attr).' '.$label):
			$this->element('input', $attr);
	}
	
	/**
	 * Generates a local file selection <input/>.
	 * 
	 * @param string $name Element name
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function file($name, $attr=array()) {
		$attr['type'] = 'file';
		$attr['name'] = $name;
		return $this->element('input', $attr);
	}

	/**
	 * Generates a <textarea>.
	 * 
	 * @param string $name Element name
	 * @param int $cols Number of columns
	 * @param int $rows Number of rows
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function textarea($name, $cols=40, $rows=5, $attr=array()) {
		$attr['name'] = $name;
		$attr['cols'] = $cols;
		$attr['rows'] = $rows;
		$value = (isset($attr['value']) && $attr['value'] !== false)?
			$attr['value']:
			$this->value($name);
		return $this->element('textarea', $attr, htmlspecialchars($value));
	}

	/**
	 * Generates a submit button <input/>.
	 * 
	 * @param string $label Button text
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function submit($label, $attr=array()) {
		$attr['type'] = 'submit';
		$attr['value'] = $label;
		return $this->element('input', $attr);
	}

	/**
	 * Generates a generic button <input/>.
	 * 
	 * @param string $label Button text
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function button($label, $attr=array()) {
		$attr['type'] = 'submit';
		return $this->element('button', $attr, $label);
	}

	/**
	 * Generates a <label> element.
	 * 
	 * @param string $name Element name
	 * @param string $label Label text
	 * @param array $attr Tag attributes
	 * @return string
	 */
	public function label($name, $label, $attr=array()) {
		$attr['for'] = $name;
		return $this->element('label', $attr, $label);
	}
}