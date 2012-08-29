<?php
namespace Test;

use Core\Framework;

class AllTestsSuite {

	static protected $name;

	private static function getDir() {
		$file = DIR_TESTS.'/'.str_replace('_', '/', get_called_class()).'.php';
		return dirname($file);
	}

	public static function suite() {
		$suite = new \PHPUnit_Framework_TestSuite(static::$name);
		$dir = self::getDir();

		$it = new AllTestsFilterIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)));

		$pairs = array('\\' => '/',  DIR_TESTS.'/' => '',  '/' => '_');

		for ($it->rewind(); $it->valid(); $it->next()) {
			$path = str_replace(array_keys($pairs), array_values($pairs), $it->current());
			$class = basename($path, '.php');
			$suite->addTestSuite($class);
		}

		return $suite;
	}

}

class AllTestsSuiteFilterIterator extends \FilterIterator {

	public function accept() {
		$regexp = '|AllTests\.php$|';
		return preg_match($regexp, $this->current());
	}
}

class AllTestsFilterIterator extends \FilterIterator {

	public function accept() {
		return preg_match('|Test\.php$|', $this->current());
	}
}

class AllTestsConfiguration extends \PHPUnit_Util_Configuration {
    public function __construct($contents, $filename) {
        $this->filename = $filename;
        $this->document = \PHPUnit_Util_XML::load($contents, false, $filename);
        $this->xpath    = new \DOMXPath($this->document);
    }
}

class AllTests {

	private static function fixConfigXML($xml) {
		$pairs = array(
			'Reports/' => '..'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'Reports'.DIRECTORY_SEPARATOR,
			'../libs/'  => '..'.DIRECTORY_SEPARATOR.'libs'.DIRECTORY_SEPARATOR,
			'../libs'  => '..'.DIRECTORY_SEPARATOR.'libs',
		);
		return str_replace(array_keys($pairs), array_values($pairs), $xml);
	}

	public static function main($configuration='default', $name=false) {
		Framework::ContentType('text/html');
		set_include_path(get_include_path().PATH_SEPARATOR.DIR_PHP.'/tests');

		$file = $_SERVER['PHP_SELF'];

		$path = DIR_TESTS."/phpunit-{$configuration}.xml";
		$xml = self::fixConfigXML(file_get_contents($path));

		$configuration = new AllTestsConfiguration($xml, $path);

		ob_start();
		\PHPUnit_TextUI_TestRunner::run(self::suite($name), compact('configuration'));
		return ob_get_clean();
	}

	public static function suite($name=false) {
		$suite = new \PHPUnit_Framework_TestSuite('OEM');
		$path = $name? DIR_TESTS."/{$name}": DIR_TESTS;
		
		$it = new AllTestsSuiteFilterIterator(
			new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($path)));

		$pairs = array('\\' => '/',  DIR_TESTS.'/' => '',  '/' => '_');

		for ($it->rewind(); $it->valid(); $it->next()) {
			$path = str_replace(array_keys($pairs), array_values($pairs), $it->current());
			$class = basename($path, '.php');
			$suite->addTest($class::suite());
		}

		return $suite;
	}

}