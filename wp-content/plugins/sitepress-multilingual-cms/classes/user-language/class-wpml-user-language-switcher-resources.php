<?php

/**
 * @package    wpml-core
 * @subpackage wpml-user-language
 */
class WPML_User_Language_Switcher_Resources {
	private $nonce_name = 'wpml_user_language_switcher';

	public function __construct() {
	}

	public function enqueue_scripts( $data ) {
		wp_register_script( 'wpml-user-language', ICL_PLUGIN_URL . '/res/js/wpml-user-language.js', array( 'jquery' ) );

		$wp_mail_script_data = array(
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'mail'              => $data['mail'],
			'auto_refresh_page' => $data['auto_refresh_page'],
			'nonce'             => wp_create_nonce( $this->nonce_name ),
		);

		wp_localize_script( 'wpml-user-language', 'wpml_user_language_data', $wp_mail_script_data );

		wp_enqueue_script( 'wpml-user-language' );
	}

}
