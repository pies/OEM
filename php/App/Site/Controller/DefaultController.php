<?php
namespace App\Site\Controller;

use Database\DB;
use Core\XML;

class DefaultController extends AppController {

	public function index() {
		return $this->render('Page/Index');
	}
}
