<?php
require WPML_ST_PATH . '/inc/functions.php';
require WPML_ST_PATH . '/inc/private-actions.php';
require WPML_ST_PATH . '/inc/private-filters.php';

function wpml_st_load_label_menu() {
	global $wpml_st_label_menu;

	if ( ! isset( $wpml_st_label_menu ) ) {
		$wpml_st_label_menu = new WPML_ST_Label_Translation();
		$wpml_st_label_menu->init();
	}

	return $wpml_st_label_menu;
}

function wpml_st_setup_label_menu_hooks() {
	if ( is_admin() ) {
		add_action( 'wpml_st_load_label_menu', 'wpml_st_load_label_menu' );
	}
	if ( wpml_is_ajax() ) {
		wpml_st_load_label_menu();
	}
}

/**
 * @return WPML_Admin_Texts
 */
function wpml_st_load_admin_texts() {
	global $wpml_st_admin_texts;

	if ( ! isset( $wpml_st_label_menu ) ) {
		global $iclTranslationManagement, $WPML_String_Translation;
		$wpml_st_admin_texts = new WPML_Admin_Texts( $iclTranslationManagement, $WPML_String_Translation );
	}

	return $wpml_st_admin_texts;
}

/**
 * @return WPML_Slug_Translation
 */
function wpml_st_load_slug_translation( ) {
	global $wpml_slug_translation, $sitepress, $wpdb;

	if ( ! isset( $wpml_slug_translation ) ) {
		$wpml_slug_translation = new WPML_Slug_Translation( $sitepress, $wpdb );
		add_action( 'init', array( $wpml_slug_translation, 'init' ), - 1000 );
	}

	return $wpml_slug_translation;
}

/**
 * @return WPML_ST_String_Factory
 */
function wpml_st_load_string_factory() {
	global $wpml_st_string_factory, $wpdb;

	if ( ! isset( $wpml_st_string_factory ) ) {
		$wpml_st_string_factory = new WPML_ST_String_Factory( $wpdb );
	}

	return $wpml_st_string_factory;
}
