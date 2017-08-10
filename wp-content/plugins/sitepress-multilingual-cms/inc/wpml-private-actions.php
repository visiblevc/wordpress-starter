<?php

function new_duplicated_terms_filter( $post_ids, $duplicates_only = true ) {
	global $wpdb, $sitepress, $wpml_admin_notices;

	require_once ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-hierarchy-duplication.class.php';
	$hier_dupl  = new WPML_Term_Hierarchy_Duplication( $wpdb, $sitepress );
	$taxonomies = $hier_dupl->duplicates_require_sync( $post_ids, $duplicates_only );
	if ( (bool) $taxonomies ) {
		$text      = __(
			'<p>Some taxonomy terms are out of sync between languages. This means that content in some languages will not have the correct tags or categories.</p>
			 <p>In order to synchronize the taxonomies, you need to go over each of them from the following list and click the "Update taxonomy hierarchy" button.</p>',
			'wpml-translation-management'
		);
		$collapsed = 'Taxonomy sync problem';

		foreach ( $taxonomies as $taxonomy ) {
			$text .= '<p><a href="admin.php?page='
			         . ICL_PLUGIN_FOLDER . '/menu/taxonomy-translation.php&taxonomy='
			         . $taxonomy . '&sync=1">' . get_taxonomy_labels(
				         get_taxonomy( $taxonomy )
			         )->name . '</a></p>';
		}

		$text .= '<p align="right"><a target="_blank" href="https://wpml.org/documentation/getting-started-guide/translating-post-categories-and-custom-taxonomies/#synchronizing-hierarchical-taxonomies">Help about translating taxonomy >></a></p>';

		$notice = new WPML_Notice( 'wpml-taxonomy-hierarchy-sync', $text, 'wpml-core' );
		$notice->set_css_class_types( 'info' );
		$notice->set_collapsed_text( $collapsed );
		$notice->set_hideable( false );
		$notice->set_dismissible( false );
		$notice->set_collapsable( true );
		$wpml_admin_notices = wpml_get_admin_notices();
		$wpml_admin_notices->add_notice( $notice );
	} else {
		remove_taxonomy_hierarchy_message();
	}
}

add_action( 'wpml_new_duplicated_terms', 'new_duplicated_terms_filter', 10, 2 );

function display_tax_sync_message( $post_id ) {
	do_action( 'wpml_new_duplicated_terms', array( 0 => $post_id ), false );
}

add_action( 'save_post', 'display_tax_sync_message', 10 );

function remove_taxonomy_hierarchy_message() {
	$wpml_admin_notices = wpml_get_admin_notices();
	$wpml_admin_notices->remove_notice( 'wpml-core', 'wpml-taxonomy-hierarchy-sync' );
}

add_action( 'wpml_sync_term_hierarchy_done', 'remove_taxonomy_hierarchy_message' );

function taxonomy_hierarchy_sync_message_script() {
	wp_register_script( 'taxonomy_hierarchy_sync_message', ICL_PLUGIN_URL . '/res/js/taxonomy-hierarchy-sync-message.js', array( 'jquery' ) );
	wp_enqueue_script( 'taxonomy_hierarchy_sync_message' );
}

add_action( 'admin_enqueue_scripts', 'taxonomy_hierarchy_sync_message_script' );


/**
 * @return WPML_Notices
 */
function wpml_get_admin_notices() {
	global $wpml_admin_notices;

	if ( ! $wpml_admin_notices ) {
		$wpml_admin_notices = new WPML_Notices( new WPML_Notice_Render() );
		$wpml_admin_notices->init_hooks();
	}

	return $wpml_admin_notices;
}

function wpml_validate_language_domain_action() {

	if ( wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ),
		filter_input( INPUT_POST,
			'action' ) ) ) {
		global $sitepress;
		$http                    = new WP_Http();
		$wp_api                  = $sitepress->get_wp_api();
		$language_domains_helper = new WPML_Language_Domain_Validation( $wp_api,
			$http, filter_input( INPUT_POST,
				'url' ), '' );
		$res                     = $language_domains_helper->is_valid();
	}
	if ( ! empty( $res ) ) {
		wp_send_json_success( __( 'Valid', 'sitepress' ) );
	}
	wp_send_json_error( __( 'Not valid', 'sitepress' ) );
}

add_action( 'wp_ajax_validate_language_domain', 'wpml_validate_language_domain_action' );
