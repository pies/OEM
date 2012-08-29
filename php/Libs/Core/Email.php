<?php
namespace Core;

class Email {

	/**
	 *
	 * @var string
	 */
	protected $from_name;
	
	/**
	 *
	 * @var string
	 */
	protected $from_email;

	/**
	 *
	 * @var string
	 */
	protected $header_separator = "\r\n";

	/**
	 *
	 * @param string $from_name
	 * @param string $from_email
	 */
	public function  __construct($from_name, $from_email) {
		$this->from_name  = (string) $from_name;
		$this->from_email = (string) $from_email;
		if (!$this->from_email) {
			user_error("Sender e-mail address has to be set.", E_USER_ERROR);
		}
	}

	/**
	 *
	 * @param string $to_name
	 * @param string $to_email
	 * @param string $subject
	 * @param string $message
	 * @param array $extra_headers
	 * @return boolean
	 */
	public function send($to_name, $to_email, $subject, $message, $extra_headers=array()) {
		mb_internal_encoding('UTF-8');

		$from =    $this->encode($this->from_name)." <{$this->from_email}>";
		$to =      $to_name? $this->encode($to_name)." <{$to_email}>": $to_email;
		$subject = $this->encode($subject);

		$headers = $this->serialize_headers(array_merge(array(
			'MIME-Version' => '1.0',
			'Content-type' => 'text/html; charset=UTF-8',
			'From'         => $from,
		), $extra_headers));

		return mail($to, $subject, $message, $headers, '-f'.$this->from_email);
	}

	/**
	 *
	 * @param string $str
	 * @return string
	 */
	private function encode($str) {
		return mb_encode_mimeheader($str, 'UTF-8', 'B', $this->header_separator);
	}

	/**
	 *
	 * @param array $headers
	 * @return string
	 */
	private function serialize_headers($headers) {
		$out = array();
		foreach ($headers as $key=>$value) {
			$out[] = "{$key}: {$value}";
		}
		return join("\r\n", $out)."\r\n";
	}

}
