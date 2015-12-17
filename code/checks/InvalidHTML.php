<?php

namespace WP_CLI\CheckContent\checks;

use WP_CLI\CheckContent\checks;
use DOMDocument;

/**
 * Checks $content for Invalid HTML
 *
 * @package WP_CLI\CheckContent\checks
 */
class InvalidHTML implements checks {

	public $category = 'danger';

	static public function get_content($_post) {

		$GLOBALS['post'] = $_post;

		/**
		 * Filter the post content.
		 *
		 * @since 0.71
		 *
		 * @param string $content Content of the current post.
		 */
		return str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', $_post->post_content ) );
	}



	/**
	 * @param $content
	 *
	 * @return array of errors
	 */
	static public function run($_post) {

		$results = array();

		$content = self::get_content($_post);
		list($DOM, $ErrorHandler) = self::validate_content( $content );

		if ( ! $ErrorHandler->ok()) {
			foreach ($ErrorHandler->errors() as $error) {
				$results[] = array(
					'Failed to load HTML',
					str_replace(
						"DOMDocument::loadHTML() [<a href='http://www.php.net/domdocument.loadhtml'>domdocument.loadhtml</a>]: ",
						'',
						$error[1]
					),
					"<textarea>{$content}</textarea>"
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
class InvalidHTML_ErrorTrap {

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
