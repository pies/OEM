<?php
namespace App\Admin\Controller;

use \App\Shared\Controller\RestrictedController;

class AppController extends RestrictedController {
	
	public function __construct($url=null) {
		//$_SESSION['admin']['id'] = 0;
		if ($url != '/default/login') {
			$this->RequiresLogin();
		}
	}
	
}
