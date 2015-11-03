<?php

namespace WP_CLI\CheckContent;

/**
 * Class WPCLI_Plugin_CheckContent__site
 *
 * Creates a wrapper for a WPSite to cache general site information & any errors found from checks.
 *
 */
class wpsite {

	private $checks_to_ignore = array();

	/**
	 * Should the output be in HTML?
	 */
	protected $inHTML = false;

	private $data = array();

	private $blog_id = '';
	private $site_id = '';

	/**
	 * @param $blog_id
	 * @param $site_id
	 */
	function __construct($blog_id, $site_id, $checks_to_ignore = null, $inHTML = false) {
		$this->blog_id = $blog_id;
		$this->site_id = $site_id;

		foreach ( explode(",", $checks_to_ignore) as $_ignore) {
			$this->checks_to_ignore[] = strtolower($_ignore);
		}

		$this->inHTML = $inHTML;
	}

	public function __get($name) {
		if (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}

		// Make sure wp is looking at the right blog for this 'WPCLI_Plugin_CheckContent__site'
		global $wpdb;
		switch_to_blog( $this->blog_id );
		$wpdb->set_blog_id( $this->blog_id );

		// Return cached values
		switch ($name) {
			case 'title':
				// A fancy title
				return $this->data[$name] = sprintf( "%s (%s)", $this->link, get_option('blogname') );
			case 'link':
				// Link to the homepage
				return $this->data[$name] = get_blog_details( $this->blog_id )->siteurl;
			case 'errors':
				// This can take a while depending on the size of the site.
				return $this->data[$name] = $this->_errors();
		}

		// BSOD
		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE
		);
		return null;
	}

	/**
	 * @return string (no shit)
	 */
	function __toString() {
		return $this->title;
	}

	/**
	 * Check for errors in current site (and cache results)
	 * @return int|boolean
	 */
	function has_errors() {
		return count($this->errors);
	}

	/**
	 * Load available checks from 'checks' folder
	 * @return array
	 */
	function load_checks() {
		$checks = array();
		foreach (glob(realpath(dirname(__FILE__)) . "/checks/" . "*.php") as $check) {

			$class = pathinfo($check, PATHINFO_FILENAME);
			if(!in_array(strtolower($class), $this->checks_to_ignore)) {
				$checks[] = 'WP_CLI\CheckContent\checks\\' . $class;
			}
		}
		return $checks;
	}

	/**
	 * @return array of all errors for this 'WPCLI_Plugin_CheckContent__site'
	 */
	function _errors() {
		$results = array();

		$checks = $this->load_checks();
		$bootstap_button = "color: #fff;padding: 6px 12px;text-decoration: none;font-size: 14px;font-weight: 400;text-align: center;border-radius: 4px;";

		// Loop through every post for the current blog
		foreach(get_posts(array(
			'post_type'      => get_post_types(),
			'orderby'        => 'post_id',
			'order'          => 'ASC',
			'posts_per_page' => -1
		)) as $post) {

			$_post = get_post($post->ID);
			$_curr_urls = array();

			// Apply any content filters to post_content
			$content = str_replace(']]>', ']]&gt;', apply_filters('the_content', $_post->post_content));

			if( $content) {
				foreach($checks as $check) {

					//Only continue checking if no errors found
					if(
						(! isset($results[$post->ID])) ||
						(is_array($results[$post->ID]) && count($results[$post->ID]) === 0)
					) {
						$_result = $check::run($content);
						if( count( $_result ) ) {
							$_post = get_post( $post->ID );
							$results[$post->ID] = array(
								'title' => array(
									"Error with page: " . $_post->post_title,
									sprintf(
										"<a href='%s' style='%s'>View</a>&nbsp;||&nbsp;<a href='%s' style='%s'>Edit</a>",
										str_replace('https://','http://', get_permalink($_post->ID)),
										"background-color: #337ab7;border: 1px solid #2e6da4;" . $bootstap_button,
										sprintf('%s/post.php?post=%d&action=edit', $this->LinkAdmin(), $_post->ID),
										"background-color: #eea236;border: 1px solid #f0ad4e;" . $bootstap_button
									)
								),
								'results' => $_result
							);
						}
					}
				}
			}
		}

//		//Add useful information for any posts with errors
//		foreach ($results as $_id => $result) {
//			if(count($result)) {
//
//				if( $this->inHTML ) {
//
//				} else {
//					array_unshift(
//						$results[$_id],
//						array(
//							'Error with content on page',
//							$_post->post_title,
//							'%_'
//						),
//						array(
//							'View link',
//							str_replace('https://','http://', get_permalink($_post->ID)),
//							'%g'
//						),
//						array(
//							'Edit link',
//							sprintf('%s/post.php?post=%d&action=edit', $this->LinkAdmin(), $_post->ID),
//							'%g'
//						)
//					);
//				}
//			}
//		}
		return $results;
	}

	/**
	 * @return string Link to the WPSite CMS
	 */
	function LinkAdmin() {
		return $this->link . "/wp-admin";
	}
}