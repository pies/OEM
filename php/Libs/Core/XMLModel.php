<?php
namespace Core;

class XMLModel extends XML {

	static $DIR = DIR_APP;

	/**
	 *
	 * @param string $path
	 * @return XMLModel
	 */
	public static function factory($path) {
		return simplexml_load_file($path, __CLASS__);
	}

	public function extend() {
		
		foreach ($this->import as $kk=>$import) {
			$this->extendImport($import);
		}

		foreach ($this->table as $table) {
			$this->extendElement($table);
			foreach ($table->field as $field) {
				$this->extendElement($field);
			}
		}

		foreach ($this->xpath('//template | //import') as $template) {
			$template->remove();
		}

		return $this;
	}

	private function extendImport($import) {
		$path = self::$DIR."/{$import['name']}.xml";
		if (!is_readable_file($path)) {
			throw new XMLModelException("XML import '{$path}' not found.", E_USER_WARNING);
		}
		$class = __CLASS__;
		$xml = new $class(file_get_contents($path));
		foreach ($xml->children() as $node) {
			$this->append($node);
		}
	}

	private function extendElement($element) {
		$extends_id = (string) $element['extends'];
		if (!$extends_id) return $element;

		$extends = $this->find("//template[@name='{$extends_id}']");

		if (!$extends) {
			throw new XMLModelException("XML element //template[@name='{$extends_id}'] not found.", E_USER_WARNING);
		}

		if ($extends['extends']) {
			$this->extendElement($extends);
		}

		$element->copyAttributes($extends);

		foreach ($extends->children() as $node_name=>$node) {
			$this->extendElement($node);
			$xpath = "{$node_name}[@name={$node['name']}]";
			if (!$element->find($xpath)) {
				$element->add($node_name)->copyAttributes($node);
			}
		}

		unset($element['extends']);

		return $element;
	}

	public function getTable($name) {
		if (!$name) return $this;
		return $this->find("//table[@name='{$name}']");
	}

}

class XMLModelException extends XMLException {};
