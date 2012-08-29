<?php
namespace Core;

class XMLConfig extends XML {

	public function extend($base_dir=false) {
		foreach ($this->xpath('//*[@import]') as $node) {
			$import = (string) $node['import'];
			unset($node['import']);
			$node->import("{$base_dir}/{$import}");
		}
		return $this;
	}

	public function import($path) {
		$path = realpath($path);
		if (!$path) {
			throw new XMLConfigException("Import '{$path}' could not be loaded (file not readable).");
		}
		return $this->copyRecursive(self::Factory($path));
	}

	public function copyRecursive($from) {
		$this->copyAttributes($from);
		$this->copyNodes($from);
		return $this;
	}

	public static function read($path){}

	/**
	 * @param string $path
	 * @return XMLConfig
	 */
	public static function Factory($path) {
		if (!realpath($path)) {
			throw new XMLConfigException("Import '{$path}' could not be loaded.");
		}
		return new XMLConfig(file_get_contents($path));
	}

}

class XMLConfigException extends XMLException {};
