<?php
namespace Core;

use Core\Assert;
use Core\XML;
use Core\XMLConfig;
use Database\DB;

class Framework {

	private static $configs = array();
	private static $model;
	private static $start;

	protected static $session_length = 1800;
	protected static $default_timezone = 'Europe/London';
	protected static $encoding = 'UTF-8';
	protected static $locale = 'en_US';

	public static $app;

	static $SwitchDomain = array();

	static $DefaultApp = 'Site';
	static $ResolveApp = array();
	
	public static function Init($root_dir) {
		self::SwitchDomain();

		self::$start = microtime(true);

		define('IS_HTTPS', (bool) @$_SERVER['HTTPS']);
		define('IS_LIVE', self::IsLive());

		if (!defined('DEBUG')) define('DEBUG', !IS_LIVE);

		define('APP_SHARED', 'Shared');
		if (!defined('APP')) define('APP', static::ResolveApp());

		mb_internal_encoding(static::$encoding);
		setlocale(LC_ALL, static::$locale);

		self::Directories($root_dir);
		self::IncludePath();

		spl_autoload_register(array('\Core\Framework', 'Autoload'), true);

		// load configuration
		self::Config();

		// run this as early as possible
		self::Timezone();
		self::Session();
		self::MagicQuotes();

		// set up error logging and reporting
		self::ErrorReporting();

		// load basic utility functions
		require_once('Core/Basics.php');

		self::Urls();
		Assert::Init(DEBUG);
		ErrorHandler::Init();
		self::Database();

	}
	
	private static function SwitchDomain() {
		if (empty($_SERVER['HTTP_HOST'])) return false;
		$host = $_SERVER['HTTP_HOST'];

		if (empty(static::$SwitchDomain[$host])) return false;
		$new_host = static::$SwitchDomain[$host];

		$url = $_SERVER['REQUEST_URI'];
		header("Location: http://{$new_host}{$url}", true, 301);
		die();
	}
	
	protected static function ResolveApp() {
		if (empty($_SERVER['HTTP_HOST'])) return static::$DefaultApp;
		$host = $_SERVER['HTTP_HOST'];

		return empty(static::$ResolveApp[$host])?
			static::$DefaultApp:
			static::$ResolveApp[$host];
	}
	
	/**
	 * Class __autoload() handler
	 *
	 * @param string $name Class name
	 */
	public static function Autoload($name) {
		$path = str_replace(array('\\', '_'), '/', $name).'.php';
		//return require $path;

		foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir) {
			if (is_file("{$dir}/{$path}")) {
				return require "{$dir}/{$path}";
			}
		}

		throw new AutoloadException("Class '{$name}' could not be autoloaded");
	}

	/**
	 * Loads, caches and returns the config.xml file
	 *
	 * @uses config.xml
	 * @return XML
	 */
	public static function Config() {
		return self::LoadConfigFile('config', true);
	}

	/**
	 * @static
	 * @param string $name Name of the XML file to load.
	 * @param bool $extend Should the base file be extended with app-specific extension.
	 * @return XMLConfig
	 */
	protected static function LoadConfigFile($name, $extend=false) {
		if (empty(self::$configs[$name])) {
			$base = DIR_APP."/{$name}.xml";
			$extension = DIR_CURRENT_APP."/{$name}.xml";
			self::$configs[$name] = $extend?
				XMLConfig::Factory($extension)->import($base):
				XMLConfig::Factory($base);
		}
		return self::$configs[$name];
	}

	protected static function ExtensionToContentType($ext) {
		$types = array(
			'html' => 'text/html',
			'txt'  => 'text/plain',
			'text' => 'text/plain',
			'xml'  => 'text/xml',
			'css'  => 'text/css',
			'flv'  => 'video/x-flv',
			'swf'  => 'application/x-shockwave-flash',
			'png'  => 'image/png',
			'jpg'  => 'image/jpeg',
			'gif'  => 'image/gif',
			'json' => 'application/json',
			'ico'  => 'image/x-icon',
			'pdf'  => 'application/pdf',
		);
		return isset($types[$ext])? $types[$ext]: false;
	}

	/**
	 * @param string $name Content type full mime name or short name
	 */
	public static function ContentType($name, $charset=false) {
		$mime = self::ExtensionToContentType($name)
			or $mime = $name;
		
		$charset and $charset = '; charset='.$charset;
		ini_set('html_errors', ($mime == 'text/html'));
		header('Content-Type: '.$mime.$charset);
	}

	/**
	 * @return string
	 */
	public static function Elapsed() {
		if(empty(self::$start)) {
			$message = "Please set ".__CLASS__."::\$start = time() first";
			user_error($message, E_USER_WARNING);
			return false;
		}
		return sprintf('%.3f', round( (float) microtime(true) - (float) self::$start , 3)).'s';
	}

	/**
	 * Loads, caches and returns the model.xml file
	 *
	 * @uses php/model.xml
	 * @static
	 * @param string $name
	 * @return XML
	 */
	public static function Model($name=null) {
		if (empty(self::$model)) {
			$path = DIR_APP.'/model.xml';
			//$cache_path = DIR_APP.'/Shared/Config/Cache/model.xml.cache';
			$cache_path = DIR_TMP.'/model.xml.cache';
			
			if (!is_readable($cache_path) || (filemtime($path) > filemtime($cache_path))) {
				try {
					$tmp = XMLModel::factory($path)->extend();
					file_put_contents($cache_path, $tmp->asXML(false, false));
				}
				catch (XMLModelException $e) {
					trigger_error($e->getMessage(), E_USER_ERROR);
				}
			}
			
			self::$model = XMLModel::factory($cache_path);
		}

		return self::$model->getTable($name);
	}

	public static function Shorten($path) {
		return str_replace(array('\\', DIR_ROOT, '//'), '/', $path);
	}

	public static function XDebug($enabled=true) {
		if (!function_exists('xdebug_enable')) return false;
		
		if ($enabled) {
			ini_set('xdebug.collect_vars', 'on');
			ini_set('xdebug.collect_params', '1');
			ini_set('xdebug.dump_globals', 'off');
			ini_set('xdebug.dump.SERVER', 'REQUEST_URI');
			ini_set('xdebug.show_local_vars', 'off');
			xdebug_enable();
		}
		else {
			if (function_exists('xdebug_disable')) {
				xdebug_disable();
			}
			ini_set('xdebug.collect_vars', 'off');
			ini_set('xdebug.collect_params', '0');
		}

		return true;
	}



	/**
	 * Sets the basic directory-related defines.
	 */
	private static function Directories($php_dir) {
		define('DIR_ROOT', str_replace('\\', '/', dirname($php_dir)));
		define('DIR_PHP',        DIR_ROOT.'/php');
			define('DIR_APP',    DIR_PHP.'/App');
				define('DIR_SHARED', DIR_APP.'/'.APP_SHARED);
				define('DIR_CURRENT_APP',DIR_APP.'/'.APP);
					define('DIR_CONTROLLERS', DIR_CURRENT_APP.'/Controller');
					define('DIR_MODELS', DIR_CURRENT_APP.'/Model');
					define('DIR_VIEWS', DIR_CURRENT_APP.'/View');
			define('DIR_FILES',  DIR_PHP.'/Files');
			define('DIR_LIBS',   DIR_PHP.'/Libs');
			define('DIR_LOGS',   DIR_PHP.'/Logs');
			define('DIR_TESTS',  DIR_PHP.'/Tests');
			define('DIR_TMP',    DIR_PHP.'/Tmp');
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	private static function StrEndsWith($haystack, $needle) {
		$len = strlen($haystack);
		$pos = strrpos($haystack, $needle);
		if ($pos === false) return false;
		return $pos == ($len - strlen($needle));
	}

	/**
	 * Configures the include path.
	 */
	private static function IncludePath() {
		set_include_path(join(PATH_SEPARATOR, array(
			DIR_LIBS,
			DIR_PHP,
			DIR_LIBS.'/Vendor',
		)));
	}

	private static function IsLive() {
		if (empty($_SERVER['HTTP_HOST'])) return true;
		$host = $_SERVER['HTTP_HOST'];
		$suffixes = array('localhost','local');
		foreach ($suffixes as $suff) {
			if ($host == $suff) return false;
			if (self::StrEndsWith($host, ".{$suff}")) return false;
		}
		return true;
	}

	/**
	 * Converts from camel case to underscored, i.e. FooBarBaz -> foo_bar_baz
	 * inspired by Symfony
	 *
	 * @param string $str
	 * @return string
	 */
	private static function Underscore($str) {
		$rules = array(
			'/([A-Z]+)([A-Z][a-z])/' => '\\1_\\2',
			'/([a-z\d])([A-Z])/'     => '\\1_\\2'
		);
		return strtolower(preg_replace(array_keys($rules), array_values($rules), str_replace('::', '/', $str)));
	}

	/**
	 * Sets the basic URL-related defines.
	 */
	private static function Urls() {
		define('URL_ROOT', static::UrlRoot());
		define('URL_CURRENT', self::UrlCurrent());
		define('URL_ROOT_FULL', self::UrlFullBase());
		define('URL_FULL', self::UrlFull());
		define('URL_CURRENT_FULL', URL_ROOT_FULL.URL_CURRENT);
	}

	/**
	 * Returns the base URL of this site, i.e. "/MySite". If the site is in the
	 * root of a domain, it returns "/". It strips the double "/" at the end.
	 * It depends on using Apache with mod_redirect and the provided .htaccess
	 * files.
	 *
	 * @return mixed Root URL of this site or false.
	 */
	public static function UrlRoot() {
		$get  = _GET('URL'); // is urldecoded by default
		$uri  = urldecode(_SERVER('REQUEST_URI'));
		$self = _SERVER('PHP_SELF');

		if (!$uri) return false;

		$qm = strpos($uri, '?');
		if ($qm !== false) {
			$uri = substr($uri, 0, $qm);
		}

		if (!$get && $uri==$self) {
			$root = dirname($self);
			if ($root == '/' || $root == '\\') $root = '';
			return $root;
		}

		if (!(strlen($uri)-strlen($get))) {
			return '';
		}

		$url = substr($uri, 0, strlen($uri)-strlen($get));
		
		while (substr($url, -1) == '/') {
			$url = substr($url, 0, -1);
		}

		return $url;
	}

	/**
	 * Returns the URL of current page, i.e. "/MySite/items/list?page=2"
	 *
	 * @uses static::UrlRoot()
	 * @return string Current URL.
	 */
	private static function UrlCurrent() {
		$url = @$_SERVER['REQUEST_URI'];
		if (!$url) return false;

		$url = substr($url, strlen(static::UrlRoot()));

		while (substr($url, -2) == '//') {
			$url = substr($url, 0, -1);
		}

		if ($url[0] != '/') {
			$url = '/'.$url;
		}

		return $url;
	}

	/**
	 * Returns the full URL of current page, i.e.
	 * "https://www.example.com/MySite/items/list?page=2"
	 *
	 * @return mixed Full URL or false.
	 */
	private static function UrlFull() {
		$protocol = @$_SERVER['HTTPS']? 'https': 'http';
		$host = @$_SERVER['HTTP_HOST'];
		return $host? "{$protocol}://{$host}".$_SERVER['REQUEST_URI']: false;
	}

	/**
	 * Returns the full base URL of this site, i.e.
	 * "https://www.example.com/MySite/"
	 *
	 * @uses static::UrlRoot()
	 * @return mixed Full base URL or false.
	 */
	private static function UrlFullBase() {
		$protocol = @$_SERVER['HTTPS']? 'https': 'http';
		$host = @$_SERVER['HTTP_HOST'];
		return $host? "{$protocol}://{$host}".static::UrlRoot(): false;
	}


	/**
	 * Connects to the database.
	 *
	 * @uses config.xml
	 * @return bool Success
	 */
	private static function Database() {
		$config = IS_LIVE?
			self::Config()->database->live:
			self::Config()->database->dev;

		return $config? DB::connect($config): false;
	}

	/**
	 * Configures the error reporting and logging.
	 */
	private static function ErrorReporting() {
		error_reporting(DEBUG? E_ALL & ~E_DEPRECATED: 0);
		//error_reporting(DEBUG? E_ALL ^ E_STRICT: 0);
		ini_set('display_errors', DEBUG);
		ini_set('error_log', DIR_LOGS.'/'.date('ymd').'_error.log');
		ini_set('log_errors', 1);
		ini_set('log_errors_max_len', 1024);
	}

	/**
	 * Configures and starts the session.
	 */
	private static function Session() {
		/*
		if (IS_LIVE) {
			$domain = '.'.SITE_DOMAIN;
			$name = session_name();

			if (empty($_COOKIE[$name])) {
				session_set_cookie_params(self::$session_length, '/', $domain, false, false);
			}
			else {
				setcookie($name, $_COOKIE[$name], time()+self::$session_length, '/', $domain, false, false);
			}
		}
		*/
		if (isset($_POST['PHPSESSID'])) {
			session_id($_POST['PHPSESSID']);
		}

		ini_set('session.use_only_cookies', true);
		ini_set('session.gc_maxlifetime', self::$session_length);

		session_start();
	}

	/**
	 * Configure the timezone.
	 *
	 * @uses config.xml
	 */
	private static function Timezone() {
		$timezone = (string) self::Config()->site->timezone
			or $timezone = static::$default_timezone;
		date_default_timezone_set($timezone);
	}


	/**
	 * For badly configured servers only.
	 *
	 * @return bool
	 */
	public static function EnableCompression() {
		$ua = $_SERVER['HTTP_USER_AGENT'];

		$is_opera = strpos($ua, 'Opera') !== false;
		$is_ie = !$is_opera && (strpos($ua, 'Mozilla/4.0 (compatible; MSIE ') !== 0);

		if ($is_ie && !$is_opera) {
			$version = (float)substr($ua, 30);
			$is_broken_v6 = ($version == 6) && (strpos($ua, 'SV1') !== false);
			if ($version < 6 || $is_broken_v6) {
				return false;
			}
		}

		ob_start("ob_gzhandler");
		return true;
	}

	/**
	 * Fixes the whole magic quotes situation once and for all
	 */
	private static function MagicQuotes() {
		if (!get_magic_quotes_gpc()) return;

		$_GET = self::DeepStripslashWithKeys($_GET);
		$_POST = self::DeepStripslashWithKeys($_POST);
		$_COOKIE = self::DeepStripslashWithKeys($_COOKIE);
		$_REQUEST = self::DeepStripslashWithKeys($_REQUEST);
	}

	/**
	 * Strips slashes from both keys and values recursively in an array
	 * Used for cleaning up after magic_quotes_gpc
	 *
	 * @param array $input An array to clean (can be nested)
	 * @return array
	 */
	private static function DeepStripslashWithKeys($input) {
		$output = array();
		foreach($input as $key=>$value) {
			$key = stripslashes($key);
			$output[$key] = is_array($value)?
				self::DeepStripslashWithKeys($value):
				stripslashes($value);
		}
		return $output;
	}

}

class AutoloadException extends \Exception {};
