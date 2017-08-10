<?php

class WPML_Global_AJAX extends WPML_SP_User {

	/**
	 * WPML_Global_AJAX constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( &$sitepress ) {
		parent::__construct( $sitepress );
		add_action( 'wp_ajax_save_language_negotiation_type', array( $this, 'save_language_negotiation_type_action' ) );
	}

	public function save_language_negotiation_type_action() {
		$response       = false;
		$nonce          = filter_input( INPUT_POST, 'nonce' );
		$action         = filter_input( INPUT_POST, 'action' );
		$is_valid_nonce = wp_verify_nonce( $nonce, $action );

		if ( $is_valid_nonce ) {
			$icl_language_negotiation_type = filter_input( INPUT_POST, 'icl_language_negotiation_type', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
			$language_domains              = filter_input( INPUT_POST, 'language_domains', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE );
			$use_directory                 = filter_input( INPUT_POST, 'use_directory', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
			$show_on_root                  = filter_input( INPUT_POST, 'show_on_root', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
			$root_html_file_path           = filter_input( INPUT_POST, 'root_html_file_path', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
			$hide_language_switchers       = filter_input( INPUT_POST, 'hide_language_switchers', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
			$icl_xdomain_data              = filter_input( INPUT_POST, 'xdomain', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
			$sso_enabled                   = filter_input( INPUT_POST, 'sso_enabled', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

			if ( $icl_language_negotiation_type ) {
				$this->sitepress->set_setting( 'language_negotiation_type', $icl_language_negotiation_type );
				$response = true;

				if ( ! empty( $language_domains ) ) {
					$this->sitepress->set_setting( 'language_domains', $language_domains );
				}
				if ( 1 == $icl_language_negotiation_type ) {
					$urls                                   = $this->sitepress->get_setting( 'urls' );
					$urls['directory_for_default_language'] = $use_directory ? true : 0;
					if ( $use_directory ) {
						$urls['show_on_root'] = $use_directory ? $show_on_root : '';
						if ( 'html_file' == $show_on_root ) {
							$urls['root_html_file_path'] = $root_html_file_path ? $root_html_file_path : '';
						} else {
							$urls['hide_language_switchers'] = $hide_language_switchers ? $hide_language_switchers : 0;
						}
					}
					$this->sitepress->set_setting( 'urls', $urls );
				}

				$this->sitepress->set_setting( 'xdomain_data', $icl_xdomain_data );
				$this->sitepress->set_setting( 'language_per_domain_sso_enabled', $sso_enabled );
				$this->sitepress->save_settings();
			}

			if ( $response ) {
				$permalinks_settings_url = get_admin_url(null, 'options-permalink.php');
				$save_permalinks_link    = '<a href="' . $permalinks_settings_url . '">' . _x( 're-save the site permalinks', 'You may need to {re-save the site permalinks} - 2/2', 'sitepress' ) . '</a>';
				$save_permalinks_message = sprintf( _x( 'You may need to %s.', 'You may need to {re-save the site permalinks} - 1/2', 'sitepress' ), $save_permalinks_link );
				wp_send_json_success( $save_permalinks_message );
			} else {
				wp_send_json_error( __( 'Error', 'sitepress' ) );
			}
		}
	}
}
