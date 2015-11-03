<?php

namespace WP_CLI\CheckContent\checks;

use WP_CLI\CheckContent\checks;
use DOMDocument;

//Required for catching errors in parsing HTML
error_reporting(E_ALL);

/**
 * Checks $content for Invalid HTML
 *
 * @package WP_CLI\CheckContent\checks
 */
class InvalidHTML implements checks
{
	/**
	 * @param $content
	 *
	 * @return array of errors
	 */
	static public function run($content) {

		$results = array();
		list($DOM, $ErrorHandler) = self::validate_content($content);

		if ( ! $ErrorHandler->ok()) {
			foreach ($ErrorHandler->errors() as $error) {
				$results[] = array(
					'Failed to load HTML',
					str_replace(
						"DOMDocument::loadHTML() [<a href='http://www.php.net/domdocument.loadhtml'>domdocument.loadhtml</a>]: ",
						'',
						$error[1]
					)
				);
			}
		}

		return $results;
	}

	static public function validate_content($content) {
		$DOM = new DOMDocument();
		$ErrorHandler = new InvalidHTML_ErrorTrap(array($DOM, 'loadHTML'));
		$ErrorHandler->call($content);
		return array($DOM, $ErrorHandler);
	}

}

/**
 * DomDocument will throw an error if HTML is invalid.
 * This traps the error to return a more helpful string to the (l)user.
 *
 * http://stackoverflow.com/questions/1148928/disable-warnings-when-loading-non-well-formed-html-by-domdocument-php#answer-1148967
 *
 * @package WP_CLI\CheckContent\checks
 */
class InvalidHTML_ErrorTrap
{

	protected $callback;
	protected $errors = array();

	function __construct($callback) {
		$this->callback = $callback;
	}

	function call() {
		$result = null;
		set_error_handler(array($this, 'onError'));
		try {
			$result = call_user_func_array($this->callback, func_get_args());
		} catch (Exception $ex) {
			restore_error_handler();
			throw $ex;
		}
		restore_error_handler();
		return $result;
	}

	function onError($errno, $errstr, $errfile, $errline) {
		$this->errors[] = array($errno, $errstr, $errfile, $errline);
	}

	function ok() {
		return count($this->errors) === 0;
	}

	function errors() {
		return $this->errors;
	}
}