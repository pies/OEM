<?php
namespace Misc;

use Misc\Valid;
use Core\Str;

class Validation {
	
	protected $rules = array();
	protected $messages = array(
		'email'         => 'To pole musi być prawidłowym adresem email',
		'notEmpty'      => 'To pole nie może być puste',
		'regex'         => 'To pole nie jest zgodne z formatem',
		'minLength'     => 'To pole musi mieć przynajmniej :param1 znaki/ów',
		'maxLength'     => 'To pole nie może mieć więcej niż :param1 znaki/ów',
		'isLength'      => 'To pole może mieć długość :param1',
		'lengthBetween' => 'To pole musi mieć długość pomiędzy :param1 a :param2 znaki/ów',
		'is'            => 'To pole musi się równać :param1',
		'date'          => 'To pole musi być datą',
		'between'       => 'To pole musi się zawierać pomiędzy :param1 a :param2',
		'matches'       => 'Pola :param1 i :param2 muszą być takie same',
		'uploadSize'    => 'Plik jest zbyt duży',
		'uploadType'    => 'Nieprawidłowy format pliku',
		'inArray'       => 'To pole musi być jedną z dozwolonych opcji',
	);
	public $errors = array();
	
	public function __construct($rules=array()) {
		$this->rules = $rules;
	}
	
	public function add($field, $rule, $param1=null, $param2=null, $message=null) {
		$this->rules[] = array($field, $rule, $param1, $param2, $message);
	}
	
	public function check($data) {
		foreach ($this->rules as $item) {
			$field = $item[0];
			$value = isset($data[$field])? $data[$field]: null;
			$rule = $item[1];
			$param1 = isset($item[2])? $item[2]: null;
			$param2 = isset($item[3])? $item[3]: null;
			$message = isset($item[4])? $item[4]: $this->messages[$rule];
			
			if ($rule =='matches') {
				$valid = Valid::matches($data, $field, $param1);
			}
			elseif ($rule == 'notEmpty' || $rule == 'is') {
				$valid = Valid::$rule($value, $param1, $param2);
			}
			else {
				$valid = !$value || Valid::$rule($value, $param1, $param2);
			}
			
			if (!$valid && empty($this->errors[$field])) {
				$message = Str::insert($message, compact('field','value','rule','param1','param2'));
				$this->errors[$field] = $message;
			}
		}

		return $this->errors;
	}
	
}