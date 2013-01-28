<?php
namespace Libs;

use \Exception;
use \XMLReader;

/**
 * Implements a mechanism to search an XML stream for nodes of a specified 
 * type and parse them for data.
 * 
 * XML files are validated using an XSD schema. Unfortunately XMLReader only
 * provides validation information as PHP warnings, so this class temporarily
 * overrides the PHP error handler to catch those warnings and throw them as
 * exceptions.
 */
abstract class XMLStreamItemExtractor {
	
	const SCHEMA_VALIDATION_ERROR = 1000;

	/**
	 * Node name to extract.
	 * @var string
	 */
	protected $itemName;
	
	/**
	 * Path to XSD schema file.
	 * @var string
	 */
	protected $schema;
	
	/**
	 * Path to the XML input file.
	 * @var string
	 */
	protected $file;
	
	/**
	 * The XMLReader object.
	 * @var \XMLReader
	 */
	protected $reader;
	
	/**
	 * Sanity check for existance of the schema file.
	 * 
	 * @throws XMLStreamItemExtractorException
	 */
	public function __construct() {
		$this->schema = realpath($this->schema);
		if (!$this->schema) {
			throw new XMLStreamItemExtractorException("Schema {$this->schema} does not exist.");
		}
	}
	
	/**
	 * Our custom error handler to catch validation warnings and throw them as
	 * custom exceptions. All other PHP errors are thrown as generic Exceptions.
	 * 
	 * @param int $num Error number.
	 * @param string $str Error string.
	 * @param string $file Error file.
	 * @param int $line Error line.
	 * @throws XMLStreamItemExtractorException
	 * @throws Exception
	 */
	public function errorHandler($num, $str, $file, $line) {
		if (strpos($str, 'This element is not expected.') !== false) {
			throw new XMLStreamItemExtractorException("XML file {$this->file} does not match the required schema.", self::SCHEMA_VALIDATION_ERROR);
		}
		else {
			throw new Exception("{$str} at {$file}:{$line}");
		}
	}
	
	/**
	 * Sanity checks the XML input file, creates the XMLReader object.
	 * 
	 * @param string $path The XML file to parse.
	 * @return \XMLReader
	 * @throws XMLStreamItemExtractorException
	 */
	private function getReader($path) {
		$this->file = $path;
		$this->reader = new XMLReader();
	
		if (!file_exists($this->file)) {
			throw new XMLStreamItemExtractorException("File {$this->file} does not exist.");
		}		
		elseif (!is_readable($this->file)) {
			throw new XMLStreamItemExtractorException("File {$this->file} is not readable.");
		}
		elseif (!$this->reader->open($this->file)) {
			throw new XMLStreamItemExtractorException("File {$this->file} could not be opened by XMLReader.");
		}
		
		$this->reader->setSchema($this->schema);
		
		if (!$this->reader->read()) {
			throw new XMLStreamItemExtractorException("File {$this->file} is not valid or could not be read.");
		}
		
		return $this->reader;
	}
	
	/**
	 * Implements the parser loop by iterating the XML stream until it finds the
	 * required XML node type.
	 * 
	 * @param string $path The XML file to parse.
	 * @throws XMLStreamItemExtractorException
	 */
	public function parse($path) {
		set_error_handler(array($this, 'errorHandler'));
		
		try {
			$this->reader = $this->getReader($path);
			while ($this->reader->localName == $this->itemName || $this->reader->read()) {
				if ($this->reader->localName != $this->itemName) continue;
				$this->parseItem();
			}
			restore_error_handler();
			$this->reader->close();
		}
		catch (XMLStreamItemExtractorException $e) {
			restore_error_handler();
			$this->reader->close();
			throw $e;
		}
	}
	
	/**
	 * Extracts the required data from an XML node. Needs to be implemented for 
	 * the specific task.
	 */
	abstract protected function parseItem();
	
}

class XMLStreamItemExtractorException extends Exception {}
