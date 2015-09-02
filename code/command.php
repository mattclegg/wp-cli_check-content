<?php

namespace WP_CLI\CheckContent;

use WP_CLI;
use WP_CLI_Command;

/**
 * Return information about the wordpress installation and environment.
 */
class command extends WP_CLI_Command {

	/**
	 * Should the output be colorized?
	 */
	protected $inColor = true;


	/**
	 * Various Content Checks
	 *
	 * ## OPTIONS
	 *
	 * [--nocolor]
	 * : Set flag to not output colorized
	 *
	 * [--exclude=<option>]
	 * : Note: parameters are optional. If none are provided the command
	 *       will run all checks.
	 *
	 * ---> Exclude options
	 *    = all                # Doesn't actually check for anything (leave blank for all)
	 *    = invalidhtml        # Ignore pages with invalid HTML
	 *    = 3rdpartyimages     # Ignore 3rd party hosted images
	 *    = myowncheck         # PR's welcome https://github.com/mattclegg/wp-cli_check-content
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-check
	 *     wp content-check --exclude=invalidhtml
	 *
	 * @synopsis [--exclude=<option>] [--nocolor]
	 */
	public function __invoke($args = array(), $assoc_args = array())
	{

		// <br/>
		$this->br();

		// Check for nocolor parameter
		if (array_key_exists('nocolor', $assoc_args)) {
			$this->inColor = false;
		}

		// Check for exclude parameters
		if (array_key_exists('exclude', $assoc_args)) {
			$this->check( $assoc_args['exclude'] );
		} else {
			$this->check();
		}

		WP_CLI::log(PHP_EOL);
	}


	/**
	 * Output results from check(s)
	 */
	protected function check($ignore = null)
	{

		$this->log('Check content in WP', null,   '%Y');

		// Load cache of available wp-sites
		$results = $this->site_list($ignore);

		foreach($results as $site_id => $site) {

			// Output title of current site
			$this->log($site);

			// Check for errors (invokes cache)
			if ($site->has_errors()) {
				foreach( $site->errors as $errors ) {
					if($errors) {
						foreach ($errors as $error) {
							$this->log($error[0], $error[1], $error[2]);
						}
						$this->br();
					}
				}
			} else {
				$this->log('Site is OK');
			}

			$this->br();
		}
	}

	/**
	 * List of all sites available in the current install
	 * @return array
	 */
	protected function site_list($ignore = null)
	{
		$sites = array();

		if ( is_multisite() ) {

			if ( ! wp_is_large_network() ) {

				foreach (wp_get_sites( array(
					'archived' => false,
					'mature'   => false,
					'spam'     => false,
					'deleted'  => false,
					'limit'    => '10000', // See: wp_is_large_network()
					'offset'   => 0
				) )
					as $site
				) {
					$sites[] = new wpsite( $site["blog_id"], $site["site_id"], $ignore );
				}
			} else {
				// See: wp_get_sites();
				$this->developer_prompt('wp_is_large_network not supported :(');
			}

		} else {
			$this->developer_prompt('Multisite content is currently only supported :(');
		}
		return $sites;
	}

	/**
	 * Output a colorized & formatted string
	 */
	protected function log($label, $value = null, $color = '%C')
	{
		if ($value) {
			$label = \cli\Colors::colorize( "- $color" . str_pad($label, 50) . ":%n ", $this->inColor );
		} else {
			$label = \cli\Colors::colorize( "$color" . $label . "%n", $this->inColor );
		}

		WP_CLI::log($label . $value);
	}

	/**
	 * Outputs a line break
	 */
	protected function br() {
		WP_CLI::log('');
		WP_CLI::log(str_repeat('-', 50));
	}

	protected function developer_prompt($msg) {
		$this->br();
		WP_CLI::log($msg);
		WP_CLI::error("PR's welcome https://github.com/mattclegg/wp-cli_check-content");
	}
}