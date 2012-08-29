<?php
namespace Local\Languages;

use Local\Dictionary;

class Polish extends Dictionary {

	public static function sets($num) {
		return $num.' '.self::number_word($num, 'zestaw', 'zestawy', 'zestawów');
	}

	public static function tries($num) {
		return $num.' '.self::number_word($num, 'próba', 'próby', 'prób');
	}

	public static function hotels($num) {
		return $num.' '.self::number_word($num, 'hotel', 'hotele', 'hoteli');
	}

	public static function days($num) {
		return $num.' '.self::number_word($num, 'dzień', 'dni');
	}

	public static function times($num) {
		return $num.' '.self::number_word($num, 'raz', 'razy');
	}

	public static function projects($num) {
		return $num.' '.self::number_word($num, 'projekt', 'projekty', 'projektów');
	}
	
	
	public static function _views($num) {
		return "<b>{$num}</b> ".self::number_word($num, 'wyświetlenie', 'wyświetlenia', 'wyświetleń');
	}
	
	public static function _urls($num) {
		return "<b>{$num}</b> ".self::number_word($num, 'adresu', 'różnych adresów');
	}
	
	public static function _sites($num) {
		return "<b>{$num}</b> ".self::number_word($num, 'serwisie naszego użytkownika', 'serwisach naszych użytkowników');
	}
	
	public static function _visitors($num) {
		return " <b>{$num}</b> ".self::number_word($num, 'odwiedzającego', 'odwiedzających');
	}
	
	public static function _sessions($num) {
		return "<b>{$num}</b> ".self::number_word($num, 'sesji', 'sesjach');
	}
	
	
}
