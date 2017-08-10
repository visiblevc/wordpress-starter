<?php

class WPML_TP_API_AJAX {
	/**
	 * WPML_TP_AJAX constructor.
	 *
	 * @param WPML_TP_API $wpml_tp_api
	 */
	public function __construct( WPML_TP_API $wpml_tp_api ) {
		$this->wpml_tp_api = $wpml_tp_api;

		$this->init_hooks();
		$this->init_ajax_actions();
	}

	private function init_hooks() {
		add_action( 'wpml_tm_scripts_enqueued', array( $this, 'scripts' ) );
	}

	function init_ajax_actions() {
		add_action( 'wp_ajax_wpml-tp-refresh-language-pairs', array( $this, 'refresh_language_pairs' ) );
	}

	function refresh_language_pairs() {
		$project        = TranslationProxy::get_current_project();
		$nonce_is_valid = false;
		if ( array_key_exists( 'nonce', $_POST ) ) {
			$nonce          = $_POST['nonce'];
			$nonce_is_valid = wp_verify_nonce( $nonce, 'wpml-tp-refresh-language-pairs' );
		}
		if ( $nonce_is_valid ) {
			try {
				$this->wpml_tp_api->refresh_language_pairs( $project );
				wp_send_json_success( esc_attr__( 'Language pairs refreshed.', 'wpml-translation-management' ) );
			} catch ( Exception $e ) {
				wp_send_json_error( $e );
			}
		}
	}

	function scripts() {
		wp_register_script( 'wpml-tp-api', WPML_TM_URL . '/res/js/wpml-tp-api.js', array( 'jquery', 'wp-util' ), WPML_TM_VERSION );
		wp_enqueue_script( 'wpml-tp-api' );
	}
}
