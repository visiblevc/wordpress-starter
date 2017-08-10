<?php

function wpml_st_parse_config( $file ) {
	global $wpdb;

	require_once WPML_ST_PATH . '/inc/admin-texts/wpml-admin-text-import.class.php';
	$config     = new WPML_Admin_Text_Configuration( $file );
	$st_records = new WPML_ST_Records( $wpdb );
	$import     = new WPML_Admin_Text_Import( $st_records );
	$import->parse_config( $config->get_config_array() );
}

add_action( 'wpml_parse_config_file', 'wpml_st_parse_config', 10, 1 );

/**
 * Action run on the wp_loaded hook that registers widget titles,
 * tagline and bloginfo as well as the current theme's strings when
 * String translation is first activated
 */
function wpml_st_initialize_basic_strings() {
	/** @var WPML_String_Translation $WPML_String_Translation */
	global $sitepress, $pagenow, $WPML_String_Translation;

	$load_action = new WPML_ST_WP_Loaded_Action(
		$sitepress,
		$WPML_String_Translation,
		$pagenow,
		isset( $_GET['page'] ) ? $_GET['page'] : '' );
	$load_action->run();
}

if ( is_admin() ) {
	add_action( 'wp_loaded', 'wpml_st_initialize_basic_strings' );
}

function icl_st_update_blogname_actions($old, $new){
	icl_st_update_string_actions('WP', 'Blog Title', $old, $new, true );
}

function icl_st_update_blogdescription_actions($old, $new){
	icl_st_update_string_actions('WP', 'Tagline', $old, $new, true );
}