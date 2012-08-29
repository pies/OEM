<?php
namespace App\Shared\Controller;

use Core\Controller;

class RestrictedController extends Controller {

	public function __construct($url=null) {
		parent::__construct($url);
		$this->RequiresLogin();
	}

	public function RequiresLogin($status=null) {
		if (!empty($_SESSION['admin']['id'])) return;
		$this->RenderAsFullPage('User/Login', compact('status'));
	}

	/*
	public function RequiresAll() {
		$this->RequiresCode();
		$this->RequiresLogin();
	}
	
	public function RequiresCode() {
		$key = _GET('key') or isset($_SESSION['session_auth_key']) and $key = $_SESSION['session_auth_key'];
		if ($key != 'bIQax3Ny') {
			die('Incorrect code.');
		}
		else {
			$_SESSION['session_auth_key'] = $key;
		}
	}
	*/
}
