<?php

namespace WP_CLI\CheckContent\checks;

use WP_CLI\CheckContent\checks;

/**
 * Checks for ThirdPartyImages in content
 */
class NonHTTPSThirdPartyImages extends ThirdPartyImages
{

	public $category = 'warning';

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
					&& ( !( 0 === strpos( $image_src, 'https' )) ) // Doesn't start with HTTPS
				) {
					$_curr_urls [] = $image_src;
					$_urls[]       = $image_src;
				}
			}

			if ( count( $_urls ) ) {
				foreach ( $_urls as $_url ) {
					$results[] = array(
						'3rd Party Image (non HTTPS)',
						$_url,
						sprintf("<img src='%s'/>", $_url)
					);
				}
			}
		}
		return $results;
	}
}
