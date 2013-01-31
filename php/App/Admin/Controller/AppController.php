<?php
namespace App\Admin\Controller;

use \App\Shared\Controller\RestrictedController;
use \Site;

class AppController extends RestrictedController {
	
	public function __construct($url=null) {
		//$_SESSION['admin']['id'] = 0;
		if ($url != Site::AdminPrefix.'/default/login') {
			$this->RequiresLogin();
		}
	}
	
}
