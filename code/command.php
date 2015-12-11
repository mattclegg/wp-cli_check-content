<?php

namespace WP_CLI\CheckContent;

use WP_CLI;
use WP_CLI\CommandWithDBObject;

/**
 * Return information about the wordpress installation and environment.
 */
class command extends CommandWithDBObject {

	/**
	 * Should the output be colorized?
	 */
	protected $inColor = true;

	/**
	 * Should the output be in HTML?
	 */
	protected $inHTML = false;

	/**
	 * Various Content Checks
	 *
	 * ## OPTIONS
	 *
	 * [--nocolor]
	 * : Set flag to not output colorized
	 *
	 * [--exclude=<option>]
	 * : Note: parameters are optional. If none are provided the command will run all checks.
	 *
	 * [--format=<option>]
	 * : Note: parameter is optional. If none are provided the command will output to bash.
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
	 * @synopsis [--nocolor] [--exclude=<option>] [--format=<option>]
	 */
	public function __invoke($args = array(), $assoc_args = array())
	{

		if (array_key_exists('format', $assoc_args)) {
			if ($assoc_args['format']='html') {
				$this->inHTML = true;
			} else {
				// <br/>
				$this->br();
			}
		} else {
			$this->inHTML = true;
		}

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
						$this->table_log($errors);
					}
				}
			} else {
				$this->f_panel( "Site is OK!" );
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
					$sites[] = new wpsite( $site["blog_id"], $site["site_id"], $ignore, $this->inHTML );
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

	protected $default_style = 'font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;';

	protected $style = array(
		'h1'			=> "font-size: 36px;font-weight: 500;line-height: 1.1;",
		'h2'			=> "font-size: 30px;font-weight: 500;line-height: 1.1;",
		'h2'			=> "font-size: 24px;font-weight: 500;line-height: 1.1;",
		'h2'			=> "font-size: 18px;font-weight: 500;line-height: 1.1;",
		'strong'		=> "font-weight: 700;",
		'caption'		=> "color: #777;padding-top: 8px;padding-bottom: 8px;text-align: left;",
		'table'			=> "border-spacing: 0;border-collapse: collapse;",
		'tr'			=> "display: table-row;",
		'th'			=> "border-bottom: 2px solid #ddd;line-height: 1.42857143;padding:8px 0;",
		'td'			=> "padding: 8px;",
		'table.tr-odd'	=> "background-color: #f9f9f9;",
		'table.tr.td'	=> "border-top: 1px solid #ddd;",
		'panel.success' => "color: #3c763d;background-color: #dff0d8;border-color: #d6e9c6;border-radius: 4px;padding: 15px;"
	);

	protected function f($path, $open = true, $custom_css = "") {

		$dom = explode(".", $path);
		$str = "";

		foreach ($dom as $node) {

			if(isset($this->style[$node])) {
				$custom_css .= $this->style[$node];
			}

			$css = $this->default_style . $custom_css;

			$str .= vsprintf(
				'<%1$s%2$s>' . PHP_EOL,
				($open) ?
					array(
						$node,
						 sprintf(" style='%s'",  $css)
					)
				: array(
					"/", $node
				)
			);
		}
		return $str;
	}

	protected function f_enclosed($path, $string, $custom_css = "") {
		return $this->f($path, true, $custom_css) . $string . $this->f($path, false, $custom_css);
	}

	protected function f_panel($string, $type = "success") {
		if($this->inHTML) {
			echo $this->f_enclosed("div", $this->f_enclosed("strong", "Well Done!") . " {$string}", $this->style["panel.{$type}"]);
		} else {
			$this->log($string);
		}
	}

	protected function table_log($errors){

		if($this->inHTML) {

			echo $this->f('table.tr.td.table.tbody.tr');
			echo $this->f_enclosed('th', $this->f_enclosed('strong', $errors['title'][0]), "text-align:left;");
			echo $this->f_enclosed('th', $errors['title'][1]);
			echo $this->f('tr', false);

			echo $this->f('tr.td');

			echo $this->f('div', true, "border: 1px #ddd solid;border-radius: 4px 4px 0 0;margin-top: 5px;padding: 0 15px 15px;");

			echo $this->f('table');
			//echo $this->f_enclosed('caption', "The following errors will need resolving.");

			echo $this->f('tr');
			foreach (array("#", "Description", "Details") as $title) {
				echo $this->f_enclosed('th', $title);
			}
			echo $this->f('tr', false);

			foreach ($errors['results'] as $i => $error) {
				echo $this->f('tr', true, ($i % 2) ? "" : $this->style['table.tr-odd']);
				echo $this->f_enclosed('td', $i + 1, $this->style['table.tr.td']);
				echo $this->f_enclosed('td', $this->f_enclosed('strong', $error[0]), $this->style['table.tr.td']);
				echo $this->f_enclosed('td', $error[1], $this->style['table.tr.td']);
				echo $this->f('tr', false);
			}

			echo "</table></div></td><td>&nbsp;</td></tf></tr></tbody>";

			echo "</table></td></tr></table>";
		} else {

			$this->log($errors['title'][0], $errors['title'][1], "%Y");
			foreach ($errors['results'] as $i => $error) {
				$this->log($error[0], $error[1], "%C");
			}


		}
	}


	/**
	 * Output a colorized & formatted string
	 */
	protected function log($label, $value = null, $color = '%C')
	{
		if($this->inHTML) {

			switch($color) {
				case "%Y":
					$path = "h1";
					break;
				case "%C":
					$path = "h2";
					break;
				default:
					$path = "p";
			}

			echo $this->f_enclosed($path, $label);
			echo $this->f_enclosed($path, $value);


		} else {
			if ($value) {
				$label = \cli\Colors::colorize( "- $color" . str_pad($label, 50) . ":%n ", $this->inColor );
			} else {
				$label = \cli\Colors::colorize( "$color" . $label . "%n", $this->inColor );
			}
			WP_CLI::log($label . $value);
		}
	}

	/**
	 * Outputs a line break
	 */
	protected function br() {
		if(!$this->inHTML) {
			WP_CLI::log('');
			WP_CLI::log(str_repeat('-', 50));
		}
	}

	protected function developer_prompt($msg) {
		$this->br();
		WP_CLI::log($msg);
		WP_CLI::error("PR's welcome https://github.com/mattclegg/wp-cli_check-content");
	}

}