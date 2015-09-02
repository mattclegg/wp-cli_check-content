<?php

namespace WP_CLI\CheckContent\checks;

use WP_CLI\CheckContent\checks;

/**
 * Checks for ThirdPartyImages in content
 */
class ThirdPartyImages extends InvalidHTML
{

	static public function run($content) {

		$results = array();
		list($DOM, $ErrorHandler) = self::validate_content($content);

		if ( $ErrorHandler->ok()) {
			$_urls = array();
			foreach ( $DOM->getElementsByTagName( 'img' ) as $image ) {
				$image_src = $image->getAttribute( 'src' );
				if ( ( 0 === strpos( $image_src, 'http' ) ) && ( ! strpos( $image_src, get_current_site()->domain ) ) ) {
					$_curr_urls [] = $image_src;
					$_urls[]       = $image_src;
				}
			}

			if ( count( $_urls ) ) {
				foreach ( $_urls as $_url ) {
					$results[] = array(
						'3rd Party Image',
						$_url,
						'%M'
					);
				}
			}
		}

		return $results;
	}

}