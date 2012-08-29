<?php
namespace Local;

class Dictionary {

	/**
	 * Inflects a word
	 *
	 * Example:
	 *
	 *   number_word(1, 'book', 'books') // "book"
	 *   number_word(5, 'book', 'books') // "books"
	 *
	 * Admittedly more useful for Polish:
	 *
	 *   number_word(1, 'próba', 'próby', 'prób') // "próba"
	 *   number_word(3, 'próba', 'próby', 'prób') // "próby"
	 *   number_word(5, 'próba', 'próby', 'prób') // "prób"
	 *   number_word(17, 'próba', 'próby', 'prób') // "prób"
	 *   number_word(22, 'próba', 'próby', 'prób') // "próby"
	 *
	 * If you're using the same word more than once I suggest making a "macro":
	 *
	 *   class Dict {
	 *     public static function tries($num) {
	 *		 return number_word($num, 'próba', 'próby', 'prób');
	 *     }
	 *   }
	 *
	 * @param int $number
	 * @param string $one
	 * @param string $two_four
	 * @param string $five_zero
	 * @return string
	 */
	public static function number_word($number, $one, $two_four, $five_zero=false) {
		if (1 == $number) return $one;
		if (!$five_zero) return $two_four;
		if (($number % 100) > 20) $number = $number % 10;
		if ($number>=2 && $number<=4) return $two_four;
		return $five_zero;
	}

	public static function transliterate($str) {
		return function_exists('iconv')?
            iconv('UTF-8', 'ASCII//TRANSLIT', $str):
			static::transliterate($str);
	}

}
