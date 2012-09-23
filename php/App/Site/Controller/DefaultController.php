<?php
namespace App\Site\Controller;

use Database\DB;
use Core\XML;

class DefaultController extends AppController {

	public function index() {
		return $this->render('Page/Index');
	}
	
	public function docs() {
		$path = func_num_args()? join('/', func_get_args()): 'Readme';
		return $this->render("Page/Docs/{$path}");
	}
}
