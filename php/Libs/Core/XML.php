<?php
namespace Core;
use SimpleXMLElement;

assert('extension_loaded("dom")');
assert('extension_loaded("SimpleXML")');

class XML extends SimpleXMLElement {

	/**
	 * Creates an XML element. Alias for SimpleXMLElement->addChild().
	 *
	 * @param string $name
	 * @param SimpleXMLElement $value
	 * @param string $namespace
	 * @return XML
	 */
	public function add ($name, $value = null, $namespace = null) {
		if (($value === null) && is_object($name) && (is_a($name, 'XML') || is_subclass_of($name, 'SimpleXMLElement'))) {
			return $this->append($name);
		}
		return $this->addChild($name, $value, $namespace);
	}

	/**
	 * Copies all attributes from object into self.
	 * 
	 * @param XML $from
	 * @param bool $overwrite
	 * @return XML
	 */
	public function copyAttributes($from, $overwrite=false) {
		foreach ($from->attributes() as $kk=>$vv) {
			if ($overwrite || !isset($this[$kk])) {
				$this[$kk] = (string) $vv;
			}
		}
		return $this;
	}

	public function copyNodes($from, $overwrite=false) {
		$this_name = $this->getName();
		foreach ($from->children() as $node_name=>$node) {
			$old_node = $this->find($node_name);
			if ($old_node) {
				if ($overwrite) {
					$old_node->replace($node);
				}
				else {
					$old_node->copyNodes($node);
				}
			}
			else {
				$this->append($node);
			}
		}
		return $this;
	}

	/**
	 * Adds an existing SimpleXMLElement to this tree.
	 *
	 * @param SimpleXMLElement $new_child An element to add as a child.
	 * @return XML This tree.
	 */
	public function append($new_child){
		$dom = dom_import_simplexml($this);
		$new_dom = dom_import_simplexml($new_child);
		$new_node = $dom->ownerDocument->importNode($new_dom, true);
		$dom->appendChild($new_node);
		return $this;
	}

	/**
	 * Removes an element from the document.
	 * 
	 * @return XML
	 */
	public function remove() {
        $dom = dom_import_simplexml($this);
        $dom->parentNode->removeChild($dom);
		return $this;
	}

	public function replace($new_child) {
        $dom = dom_import_simplexml($this);
		$new_dom = dom_import_simplexml($new_child);
		$new_node = $dom->ownerDocument->importNode($new_dom, true);
		$dom->parentNode->appendChild($new_node);
        $dom->parentNode->removeChild($dom);
		return $this;
	}

	/**
	 * Removes all children from a DOMElement
	 * @param DOMElement $node
	 */
	private function removeDOMChildren(\DOMElement $node) {
		while ($node->firstChild) {
			while ($node->firstChild->firstChild) {
				self::removeDOMChildren($node->firstChild);
			}
			$node->removeChild($node->firstChild);
		}
	}

	/**
	 * Creates a CDATA section element to this tree.
	 *
	 * @param string $text String to append.
	 * @return XML This tree.
	 */
	public function cdata($name, $text=null) {
		if ($text === null && !$this->getName()) {
			throw new XMLDOMException("Element not initialized yet, can't add CDATA.");
			return;
		}

		if ($text === null) {
			$obj = $this;
			$text = $name;
		}
		else {
			$obj = $this->addChild($name);
		}

		$node = dom_import_simplexml($obj);
		if (!$node) return false;

		$this->removeDOMChildren($node);
		$owner = $node->ownerDocument;
		$node->appendChild($owner->createCDATASection($text));

		return $obj;
	}

	/**
	 * Returns an SimpleXMLElement attribute value.
	 *
	 * @param string $name
	 * @return string Attribute value.
	 */
	public function attr($name, $value=null) {
		if (func_num_args() == 2) {
			return (string) $this->attributes()->$name = $value;
		}
		else {
			return (string) $this->attributes()->$name;
		}
	}

	/**
	 * Searches for an XPATH in this tree and returns the first matching
	 * element or null.
	 *
	 * @param string $xpath XPATH to search for.
	 * @return mixed XML object matching the $xpath or null.
	 */
	public function find($xpath) {
		$tmp = $this->xpath($xpath);
		return isset($tmp[0])? $tmp[0]: null;
	}

	/**
	 * Finds the parent of an element.
	 *
	 * @return XML
	 */
	public function parent() {
		return $this->find('parent::*');
	}

	/**
	 * Returns or saves the XML representation of this object.
	 *
	 * @param string $filename Filename to save the XML as.
	 * @return mixed The XML string or operation result if saving.
	 */
	public function asXML($format=true, $preserve=false, $no_header=false) {
		$doc = new \DOMDocument('1.0', 'UTF-8');
		$doc->formatOutput = $format;
		$doc->preserveWhiteSpace = $preserve;
		$doc->loadXML(parent::asXML());
		return $no_header?
			$doc->saveXML($doc->documentElement):
			$doc->saveXML();
	}

	/**
	 * Creates an HTML representation of the this XML object.
	 *
	 * @return string HTML string.
	 */
	public function asHTML() {
		return nl2br(str_replace(array(' ', "\t", '<', '>'), array('&nbsp;', '&nbsp; &nbsp; ', '&lt;', '&gt;'), $this->asXML(1, 0, 1)));
	}

}

class XMLException extends \Exception {};
class XMLDOMException extends XMLException {};
