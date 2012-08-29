<?php
namespace ORM\Field;

class Email extends Text {

	protected $regexp = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i';
	protected $min = 6;
	protected $max = 320;

}