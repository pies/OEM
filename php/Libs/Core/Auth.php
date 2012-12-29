<?php
namespace Core;

class Auth {

	protected static $name = 'Auth';
	
	public static function hash($password) {
		$method = 'sha256';
		$key = hash_hmac($method, 'hash message', 'secret key');
		return hash_hmac($method, $password, $key);
	}
	
	public static function set($key, $value) {
		$_SESSION[self::$name][$key] = $value;
	}
	
	public static function get($key, $default=null) {
		return isset($_SESSION[self::$name][$key])? $_SESSION[self::$name][$key]: $default;
	}
	
	public static function setRoles($roles) {
		return self::set('@roles', $roles);
	}
	
	public static function hasRole($name) {
		return in_array($name, self::get('@roles'));
	}
	
	public static function has($role=null) {
		return $role? 
			in_array($role, self::get('@roles')):
			count(self::get('@roles'));
	}
	
	public static function logout() {
		$_SESSION[self::$name] = null;
		return true;
	}
	
}