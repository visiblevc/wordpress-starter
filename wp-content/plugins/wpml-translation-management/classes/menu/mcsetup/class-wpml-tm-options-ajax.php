<?php

class WPML_TM_Options_Ajax {

	const NONCE_TRANSLATED_DOCUMENT = 'wpml-translated-document-options-nonce';

	private $sitepress;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function ajax_hooks() {
		add_action( 'wp_ajax_wpml_translated_document_options', array( $this, 'wpml_translated_document_options' ) );
	}

	public function wpml_translated_document_options() {

		if ( ! $this->is_valid_request() ) {
			wp_send_json_error();
		} else {
			$settings = $this->sitepress->get_settings();

			if( array_key_exists('document_status', $_POST ) ) {
				$settings['translated_document_status']      = filter_var( $_POST['document_status'], FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
			}
			if( array_key_exists( 'page_url', $_POST ) ) {
				$settings['translated_document_page_url']    = filter_var( $_POST['page_url'], FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
			}
			if( $settings ) {
				$this->sitepress->save_settings( $settings );
			}

			wp_send_json_success();
		}
	}

	private function is_valid_request() {
		$valid_request = true;
		if ( ! array_key_exists( 'nonce', $_POST ) ) {
			$valid_request = false;
		}
		if ( $valid_request ) {
			$nonce = $_POST['nonce'];
			$nonce_is_valid = wp_verify_nonce( $nonce, self::NONCE_TRANSLATED_DOCUMENT );
			if ( ! $nonce_is_valid ) {
				$valid_request = false;
			}
		}
		return $valid_request;
	}
}