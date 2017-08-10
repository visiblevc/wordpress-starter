<?php
/*
Plugin Name: WPML String Translation
Plugin URI: https://wpml.org/
Description: Adds theme and plugins localization capabilities to WPML | <a href="https://wpml.org">Documentation</a> | <a href="https://wpml.org/version/wpml-2-5-2/">WPML 2.5.2 release notes</a>
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 2.5.2
Plugin Slug: wpml-string-translation
*/

if ( defined( 'WPML_ST_VERSION' ) || get_option( '_wpml_inactive' ) ) {
	return;
}

define( 'WPML_ST_VERSION', '2.5.2' );

// Do not uncomment the following line!
// If you need to use this constant, use it in the wp-config.php file
//define( 'WPML_PT_VERSION_DEV', '2.2.3-dev' );
define( 'WPML_ST_PATH', dirname( __FILE__ ) );

$autoloader_dir = WPML_ST_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

add_action( 'admin_init', 'wpml_st_verify_wpml' );
function wpml_st_verify_wpml() {
	$verifier     = new WPML_ST_Verify_Dependencies();
	$wpml_version = defined( 'ICL_SITEPRESS_VERSION' ) ? ICL_SITEPRESS_VERSION : false;
	$verifier->verify_wpml( $wpml_version );
}

/** @var array $bundle */
$bundle = json_decode( file_get_contents( dirname( __FILE__ ) . '/wpml-dependencies.json' ), true );
if ( defined( 'ICL_SITEPRESS_VERSION' ) && is_array( $bundle ) ) {
	$sp_version_stripped = ICL_SITEPRESS_VERSION;
	$dev_or_beta_pos = strpos( ICL_SITEPRESS_VERSION, '-' );
	if ( $dev_or_beta_pos > 0 ) {
		$sp_version_stripped = substr( ICL_SITEPRESS_VERSION, 0, $dev_or_beta_pos );
	}
	if ( version_compare( $sp_version_stripped, $bundle[ 'sitepress-multilingual-cms' ], '<' ) ) {
		return;
	}
}

function wpml_st_core_loaded() {
	global $sitepress, $wpdb, $wpml_admin_notices;
	new WPML_ST_TM_Jobs( $wpdb );

	$setup_complete = apply_filters( 'wpml_get_setting', false, 'setup_complete' );
	$theme_localization_type = new WPML_Theme_Localization_Type( $sitepress );
	$is_admin = $sitepress->get_wp_api()->is_admin();

	if ( isset( $wpml_admin_notices ) && $theme_localization_type->is_st_type() && $is_admin && $setup_complete ) {
		global $wpml_st_admin_notices;
		$themes_and_plugins_settings = new WPML_ST_Themes_And_Plugins_Settings();
		$wpml_st_admin_notices = new WPML_ST_Themes_And_Plugins_Updates( $wpml_admin_notices, $themes_and_plugins_settings );
		$wpml_st_admin_notices->init_hooks();
	}

	$st_settings = new WPML_ST_Settings();
	new WPML_PB_Loader( $sitepress, $wpdb, $st_settings );
}

function load_wpml_st_basics() {
	global $WPML_String_Translation, $wpdb, $wpml_st_string_factory, $sitepress;
	$wpml_st_string_factory = new WPML_ST_String_Factory( $wpdb );

	require WPML_ST_PATH . '/inc/functions-load.php';
	require WPML_ST_PATH . '/inc/wpml-string-translation.class.php';
	require WPML_ST_PATH . '/inc/constants.php';

	$WPML_String_Translation = new WPML_String_Translation( $sitepress, $wpml_st_string_factory );
	$WPML_String_Translation->set_basic_hooks();

	require WPML_ST_PATH . '/inc/package-translation/wpml-package-translation.php';

	add_action( 'wpml_loaded', 'wpml_st_setup_label_menu_hooks', 10, 0 );
	add_action( 'wpml_loaded', 'wpml_st_core_loaded', 10 );

	$troubleshooting = new WPML_ST_DB_Troubleshooting();
	$troubleshooting->add_hooks();

	$st_theme_localization_type = new WPML_ST_Theme_Localization_Type( $wpdb );
	$st_theme_localization_type->add_hooks();
}

add_action( 'wpml_before_init', 'load_wpml_st_basics' );
