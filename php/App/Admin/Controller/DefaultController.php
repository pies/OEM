<?php
namespace App\Admin\Controller;

class DefaultController extends AppController {

	public function index() {
		return $this->render('Page/Index');
	}

	public function login() {
		if (isset($_POST['login'])) {
			$users = \Site::Users();
			$user = $users->find("./user[@login='{$_POST['login']}']");
			if ($user) {
				if ($_POST['password'] == (string) $user['password']) {
					$_SESSION['admin']['id'] = (string) $user['login'];
					return $this->index();
				}
			}
		}
		
		$this->RequiresLogin('error');
	}
	
	public function logout() {
		$_SESSION['admin'] = null;
		return $this->RequiresLogin(); 
	}
	
}