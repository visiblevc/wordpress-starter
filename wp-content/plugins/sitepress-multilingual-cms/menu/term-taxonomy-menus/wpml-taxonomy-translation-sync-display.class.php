<?php

class WPML_Taxonomy_Translation_Sync_Display {

	public function __construct() {
		add_action( 'wp_ajax_wpml_tt_sync_hierarchy_preview', array( $this, 'ajax_sync_preview' ) );
		add_action( 'wp_ajax_wpml_tt_sync_hierarchy_save', array( $this, 'ajax_sync_save' ) );
	}

	private function get_req_data() {
		$taxonomy = isset( $_POST[ 'taxonomy' ] ) ? $_POST[ 'taxonomy' ] : false;
		$ref_lang = isset( $_POST[ 'ref_lang' ] ) ? $_POST[ 'ref_lang' ] : false;

		return array( $taxonomy, $ref_lang );
	}

	public function ajax_sync_preview() {
		global $wpml_language_resolution, $sitepress;

		if ( !wpml_is_action_authenticated( 'wpml_tt_sync_hierarchy' ) ) {
			wp_send_json_error( 'Wrong Nonce' );
		}
		$sync_helper = wpml_get_hierarchy_sync_helper( 'term' );
		list( $taxonomy, $ref_lang ) = $this->get_req_data();
		if ( $taxonomy ) {
			$ref_lang = $wpml_language_resolution->is_language_active($ref_lang) || $wpml_language_resolution->is_language_hidden($ref_lang)
				? $ref_lang : $sitepress->get_default_language();
			$corrections = $sync_helper->get_unsynced_elements( $taxonomy, $ref_lang );
			wp_send_json_success( $corrections );
		} else {
			wp_send_json_error( 'No taxonomy in request!' );
		}
	}

	public function ajax_sync_save() {
		if ( !wpml_is_action_authenticated( 'wpml_tt_sync_hierarchy' ) ) {
			wp_send_json_error( 'Wrong Nonce' );
		}
		$sync_helper = wpml_get_hierarchy_sync_helper( 'term' );
		list( $taxonomy, $ref_lang ) = $this->get_req_data();
		if ( $taxonomy ) {
			$sync_helper->sync_element_hierarchy( $taxonomy, $ref_lang );
			wp_send_json_success( 1 );
		} else {
			wp_send_json_error( 'No taxonomy in request!' );
		}
	}
}