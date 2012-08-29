<?php
namespace App\Admin\Controller;

use Database\DB;
use Core\XML;

class SetupController extends AppController {

	public function index() {
		return $this->db();
	}

	public function categories() {
		$data = simplexml_load_file(DIR_APP.'/categories.xml', 'Core\XML');
		return $this->render('Setup/Categories', compact('data'));
	}
	
	public function db() {
		$printer = new \Model\Printer();
		return $printer->CompareModelWithDatabase();
	}

}