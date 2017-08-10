<?php
/*
Plugin Name: WPML Media
Plugin URI: https://wpml.org/
Description: Add multilingual support for Media files | <a href="https://wpml.org">Documentation</a> | <a href="https://wpml.org/version/wpml-2-1-24/">WPML 2.1.24 release notes</a>
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 2.1.24
Plugin Slug: wpml-media-translation
*/

if (defined('WPML_MEDIA_VERSION')) {
	return;
}

define('WPML_MEDIA_VERSION', '2.1.24');
define( 'WPML_MEDIA_PATH', dirname( __FILE__ ) );

$autoloader_dir = WPML_MEDIA_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

require WPML_MEDIA_PATH . '/inc/constants.inc';
require WPML_MEDIA_PATH . '/inc/private-filters.php';
require WPML_MEDIA_PATH . '/inc/wpml-media-dependencies.class.php';
require WPML_MEDIA_PATH . '/inc/wpml-media-upgrade.class.php';

global $WPML_media, $wpdb, $sitepress;
$WPML_media = new WPML_Media( false, $sitepress, $wpdb );
new WPML_Media_Attachments_Query();
