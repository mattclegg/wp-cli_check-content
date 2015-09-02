<?php
/**
 * Author  : Matt Clegg (m@cle.gg)
 * URL     : https://github.com/mattclegg/wp-cli_check-content
 */

if ( !defined( 'WP_CLI' ) ) return;

require_once('autoload.php');

WP_CLI::add_command( 'check-content', 'WP_CLI\CheckContent\command' );