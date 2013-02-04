<?php
namespace Misc;

use \Iterator;

/**
 * Implementation of simple day iterator
 *
 * @since PHP 5
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 * @author Michal "Techi" Vrchota <michal.vrchota@seznam.cz>
 * @version 1.03
 * @package Misc
 * @category Date
 */

/**
 * Use example:
 *
 * $from = MkTime(0, 0, 0, 7, 11, 1986); // I was born in friday
 * $to = MkTime(0, 0, 0, 7, 20, 1986); // I am still alive :)
 *
 * $dayIterator = new DayIterator($from, $to);
 *
 * echo "List of days from ".Date("j.n.Y", $dayIterator->getFrom())." - ".Date("j.n.Y", $dayIterator->getTo());
 * echo "<br />";
 *
 * foreach($dayIterator as $day => $date) {
 *    echo "$day. ".Date("j.n.Y D", $date)."<br />";
 * }
 * 
 * echo "Total number of days days: ".($dayIterator->getDiffDays() + 1);
 */
class DayIterator implements Iterator {

	/**
	 * @var	integer $from unix timestamp
	 */
	protected $from;

	/**
	 * @var	integer $to unix timestamp
	 */
	protected $to;

	/**
	 * @var	integer $currentDate unix timestamp
	 */
	protected $currentDate;

	/**
	 * @var	integer $currentDay
	 */
	protected $currentDay;

	/**
	 * constructor
	 *
	 * @param integer $from  unix timestamp - hour, min and sec should be equal to 0
	 * @param integer $to    unix timestamp - hour, min and sec should be equal to 0
	 */
	public function __construct($from, $to) {
		$this->from = strtotime(date('Y-m-d', $from));
		$this->to = strtotime(date('Y-m-d', $to));

		if (Empty($from) || Empty($to) || $from > $to) {
			throw new InvalidArgumentException("Invalid date");
		}
	}

	/**
	 * check if given $time is inside given range
	 *
	 * @param integer $time   unix timestamp - hour, min and sec should be equal to 0
	 * @return bool   true if $time is in range
	 */
	public function isBetween($time) {
		return $time >= $this->from && $time <= $this->to;
	}

	/**
	 * @return integer   period-from
	 */
	public function getFrom() {
		return $this->from;
	}

	/**
	 * @return integer   period-to
	 */
	public function getTo() {
		return $this->to;
	}

	/**
	 * Computes total number of days
	 * @return integer   number of days
	 */
	public function getDiffDays() {
		return round(($this->to - $this->from) / 86400);
	}

	public function rewind() {
		$this->currentDate = $this->from;
		$this->currentDay = 1;
	}

	/**
	 * @return integer
	 */
	public function key() {
		return $this->currentDay;
	}

	/**
	 * @return mixed
	 */
	public function current() {
		return $this->currentDate;
	}

	public function next() {
		$this->currentDate = StrToTime("+1 day", $this->currentDate);
		$this->currentDay++;
	}

	/**
	 * @return bool
	 */
	public function valid() {
		return $this->currentDate <= $this->to;
	}

}
