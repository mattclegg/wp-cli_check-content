<?php

namespace WP_CLI\CheckContent\checks;

use WP_CLI\CheckContent\checks;

/**
 * Checks for ThirdPartyImages in content
 */
class ThirdPartyImages extends InvalidHTML
{

	public $category = 'info';

	static public function run($_post) {

		$results = parent::run($_post);

		$content = self::get_content($_post);
		list($DOM, $ErrorHandler) = self::validate_content($content);


		if ( $ErrorHandler->ok() ) {
			$_urls = array();

			foreach ( $DOM->getElementsByTagName( 'img' ) as $image ) {
				$image_src = $image->getAttribute( 'src' );

				if (
					( 0 === strpos( $image_src, 'http' ) ) // Starts with HTTP
					&& ( ! strpos( $image_src, get_current_site()->domain ) ) //Is a 3rd party domain
				) {
			}

			if ( count( $_urls ) ) {
				foreach ( $_urls as $_url ) {
					$results[] = array(
						'3rd Party Image',
						$_url,
						sprintf("<img src='%s'/>", $_url)
					);
				}
			}
		}
		return $results;
	}
}
