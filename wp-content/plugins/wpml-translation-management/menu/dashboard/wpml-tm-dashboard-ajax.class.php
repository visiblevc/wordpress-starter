<?php

class WPML_Dashboard_Ajax {

	public function init_ajax_actions(){
		add_action ( 'wp_ajax_wpml_duplicate_dashboard', array( $this, 'wpml_duplicate_dashboard' ) );
		add_action ( 'wp_ajax_wpml_need_sync_message', array( $this, 'wpml_need_sync_message' ) );
	}

	public function enqueue_js() {
		wp_register_script (
			'wpml-tm-dashboard-scripts',
			WPML_TM_URL . '/res/js/tm-dashboard/wpml-tm-dashboard.js',
			array( 'jquery', 'backbone' ),
			WPML_TM_VERSION
		);
		$wpml_tm_strings = $this->get_wpml_tm_script_js_strings ();
		wp_localize_script ( 'wpml-tm-dashboard-scripts', 'wpml_tm_strings', $wpml_tm_strings );
		wp_enqueue_script ( 'wpml-tm-dashboard-scripts' );
	}

	private function get_wpml_tm_script_js_strings() {
		$wpml_tm_strings = array(
			'BB_default'                     => __( 'Add to translation basket', 'wpml-translation-management' ),
			'BB_mixed_actions'               => __(
				'Add to translation basket / Duplicate',
				'wpml-translation-management'
			),
			'BB_duplicate_all'               => __( 'Duplicate', 'wpml-translation-management' ),
			'BB_no_actions'                  => __(
				'Choose at least one translation action',
				'wpml-translation-management'
			),
			'duplication_complete'           => __(
				'Finished Post Duplication',
				'wpml-translation-management'
			),
			'wpml_duplicate_dashboard_nonce' => wp_create_nonce( 'wpml_duplicate_dashboard_nonce' ),
			'wpml_need_sync_message_nonce'   => wp_create_nonce( 'wpml_need_sync_message_nonce' ),
			'duplicating'                    => __( 'Duplicating', 'wpml-translation-management' ),
		);

		return $wpml_tm_strings;
	}

	public function wpml_duplicate_dashboard() {
		if ( ! wpml_is_action_authenticated( 'wpml_duplicate_dashboard' ) ) {
			wp_send_json_error( 'Wrong Nonce' );
		}

		global $sitepress;

		$post_ids  = filter_var( $_POST['duplicate_post_ids'], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$languages = filter_var( $_POST['duplicate_target_languages'], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$res       = array();
		foreach ( $post_ids as $pid ) {
			foreach ( $languages as $lang_code ) {
				if ( $sitepress->make_duplicate( $pid, $lang_code ) !== false ) {
					$res[ $lang_code ] = $pid;
				}
			}
		}

		wp_send_json_success( $res );
	}

	public function wpml_need_sync_message() {
		if ( !wpml_is_action_authenticated( 'wpml_need_sync_message' ) ) {
			wp_send_json_error( 'Wrong Nonce' );
		}

		$post_ids   = filter_input( INPUT_POST, 'duplicated_post_ids' );
		$post_ids   = array_filter( explode( ',', $post_ids ) );
		do_action( 'wpml_new_duplicated_terms', $post_ids );
	}
}