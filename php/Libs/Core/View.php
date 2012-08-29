<?php
namespace Core;
use \Exception;

/**
 * The templating engine.
 *
 * @package Core
 */
class View {

	/**
	 * Renders the contents of an PHP file by require()'ing it while output
	 * buffering is active.
	 *
	 * Optionally can prefix local HTML paths in the output.
	 *
	 * @param string $_PATH_ Path to the PHP file to render.
	 * @param array $_DATA_ Variables to introduce into local scope.
	 * @param string $_PREFIX_ String with which to prefix local URLs in HTML.
	 * @return string The output.
	 */
	static public function render($_PATH_, $_DATA_=false, $_PREFIX_=false) {
		if (!is_readable_file($_PATH_)) {
			throw new ViewException("Could not read file '{$_PATH_}'");
		}

		if (is_array($_DATA_)) extract($_DATA_);
		ob_start();
		include($_PATH_);

		return is_string($_PREFIX_)?
			self::Prefix(ob_get_clean(), $_PREFIX_):
			ob_get_clean();
	}

	/**
	 * Prefixes URLs in given HTML with a string.
	 * 
	 * Used so that designers can use relative paths in templates.
	 *
	 * Example (before):
	 *   <a href="/"><img src="img/logo.gif"/></a>
	 *
	 * Example (after prefixing with '/MyApp/'):
	 *   <a href="/MyApp/"/><img src="/MyApp/img/logo.gif"/></a>
	 *
	 * @param string $html The HTML to prefix.
	 * @param string $prefix String to prefix with.
	 * @return string The prefixed HTML.
	 */
	static public function prefix($html, $prefix) {
		$pairs = array(
			// exceptions
			'href="#'        => '_href_="#',
			'href="mailto:'  => '_href_="mailto:',
			'href="http:'    => '_href_="http:',
			'href="https:'   => '_href_="https:',

			'src=""'         => '_src_=""',
			'src="#'         => '_src_="#',
			'src="http:'     => '_src_="http:',
			'src="https:'    => '_src_="https:',
			'src="//'        => '_src_="//',

			'action="#'      => '_action_="#',
			'action="mailto:'=> '_action_="mailto:',
			'action="http:'  => '_action_="http:',
			'action="https:' => '_action_="https:',

			'href="/' => 'href="',
			'src="/' => 'src="',
			'action="/' => 'action="',

			'href="' => 'href="'.$prefix.'/',
			'src="' => 'src="'.$prefix.'/',
			'action="' => 'action="'.$prefix.'/',
			
			//'href="'.$prefix.'/' => 'href="'.$prefix,
			//'src="'.$prefix.'/' => 'src="'.$prefix,
			//'action="'.$prefix.'/' => 'action="'.$prefix,

			'_src_="' => 'src="',
			'_href_="' => 'href="',
			'_action_="' => 'action="',
		);

		return str_replace(array_keys($pairs), array_values($pairs), $html);
	}

}

class ViewException extends Exception {};
