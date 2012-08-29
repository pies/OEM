<?php
// OEM - a PHP framework for web applications
// by Michał Tatarynowicz (pies@sputnik.pl)
// Hereby released into the public domain.

define('DEBUG', true);

require_once dirname(__FILE__).'/Libs/Core/Framework.php';

class Site extends \Core\Framework {

	const DateFormat      = 'd.m.Y';
	const DateTimeFormat  = 'd.m.Y H:i';

    static $SwitchDomain = array(
		'www.ip.local'       => 'ip.local',
		'www.admin.ip.local' => 'admin.ip.local',
	);

    static $ResolveApp = array(
		'ip.local'       => 'Site',
		'admin.ip.local' => 'Admin',
	);

    static $DefaultApp = 'Site';
	
	protected static $locale = 'pl_PL.UTF8';
	
	public static function Date($utime=false) {
		return date(Site::DateFormat, $utime);
	}

	public static function DateTime($utime=false) {
		return date(Site::DateTimeFormat, $utime);
	}

	public static function SendEmail($to_name, $to_email, $subject, $message) {
		$site = config()->site;
		$from = config()->email;
		$html = render('user/email/layout', compact('subject','message'));
		
		if (IS_LIVE) {
			$mailer = new \Core\Email($site->name, $site->email);
			return $mailer->send($to_name, $to_email, $subject, $html);
		}
		else {
			debug("\nFrom {$site->name} <{$site->email}>\nTo: {$to_name} <{$to_email}>\nSubject: {$subject}\n\n{$html}\n");
			return true;
		}
	}

	/**
	 * @return \Core\XMLConfig
	 */
	public static function Categories() {
		return static::LoadConfigFile('categories');
	}	

	public static function Category($id) {
		return static::LoadConfigFile('categories')->find("//category[@id='{$id}']");
	}

	public static function Users() {
		return static::LoadConfigFile('users');
	}

}

\Site::Init(dirname(__FILE__));
