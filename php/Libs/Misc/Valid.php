<?php
namespace Misc;

class Valid {
	
	static public function email($string) {
		return preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $string);
	}
	
}
