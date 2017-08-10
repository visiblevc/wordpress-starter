<?php
/* 
Plugin Name: Advanced Custom Fields Multilingual
Description: This 'glue' plugin makes it easier to translate with WPML content provided in fields created with Advanced Custom Fields
Author: OnTheGo Systems
Version: 0.4
 */

$autoloader_dir = __DIR__ . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

$WPML_ACF = new WPML_ACF();
$WPML_ACF = $WPML_ACF->init_worker();
