<?php

/**
 * @package    wpml-core
 * @subpackage wpml-user-language
 */
class WPML_User_Language_Switcher_Hooks {

	private $nonce_name = 'wpml_user_language_switcher';

	/**
	 * @var WPML_User_Language_Switcher_UI
	 */
	private $user_language_switcher_ui;
	/**
	 * @var WPML_User_Language_Switcher
	 */
	private $user_language_switcher;

	/**
	 * @var WPML_User_Language_Switcher
	 * @var WPML_User_Language_Switcher_UI
	 */
	public function __construct( &$WPML_User_Language_Switcher, &$WPML_User_Language_Switcher_UI ) {

		$this->user_language_switcher    = &$WPML_User_Language_Switcher;
		$this->user_language_switcher_ui = &$WPML_User_Language_Switcher_UI;

		add_action( 'wpml_user_language_switcher', array( $this, 'language_switcher_action' ), 10, 1 );
		add_action( 'wp_ajax_wpml_user_language_switcher_form_ajax', array( $this, 'language_switcher_form_ajax_callback' ) );
		add_action( 'wp_ajax_nopriv_wpml_user_language_switcher_form_ajax', array( $this, 'language_switcher_form_ajax_callback' ) );
	}

	public function language_switcher_action( $args ) {

		$defaults = array(
			'mail'              => null,
			'auto_refresh_page' => 0,
		);

		$args = array_replace( $defaults, $args );

		$model = $this->user_language_switcher->get_model( $args['mail'] );
		echo $this->user_language_switcher_ui->language_switcher( $args, $model );
	}

	public function language_switcher_form_ajax_callback() {
		$this->language_switcher_form_ajax();
	}

	public function language_switcher_form_ajax() {

		$language = filter_input( INPUT_POST, 'language', FILTER_SANITIZE_STRING );
		$language = $this->user_language_switcher->sanitize( $language );

		$email = filter_input( INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL );

		$valid = $this->is_valid_data( $_POST['nonce'], $email );

		if ( ! $valid || ! $language ) {
			wp_send_json_error();
		}

		$saved_by_third_party = $updated = apply_filters( 'wpml_user_language_switcher_save', false, $email, $language );

		if ( ! $saved_by_third_party ) {
			$updated = $this->user_language_switcher->save_language_user_meta( $email, $language );
		}
		wp_send_json_success( $updated );
	}

	private function is_valid_data( $nonce, $email ) {
		return ( wp_verify_nonce( $nonce, $this->nonce_name ) && is_email( $email ) );
	}

}
