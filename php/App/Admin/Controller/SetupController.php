<?php
namespace App\Admin\Controller;

use Database\DB;
use Core\XML;

class SetupController extends AppController {

	public function index() {
		return $this->db();
	}
	
	public function db() {
		$printer = new \Model\Printer();
		return $printer->CompareModelWithDatabase();
	}

}