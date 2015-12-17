<?php

namespace WP_CLI\CheckContent;

interface checks {

	/**
	 * Implement this method in the check subclass to
	 * execute via WP-CLI
	 */
	static function run($_post);

}
