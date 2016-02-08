<?php

namespace WP_CLI\CheckContent\checks;

use WP_CLI\CheckContent\checks;
use WP_CLI\CheckContent\checks_default;
use DOMDocument;

/**
 * Checks $content for Invalid HTML
 *
 * @package WP_CLI\CheckContent\checks
 */
class check_04_PHPErrors extends checks_default implements checks {

	/**
	 * @param $content
	 *
	 * @return array of errors
	 */
	static public function run($_post) {

		$results = array();

		error_reporting(E_ALL);

		
		return $results;
	}

}