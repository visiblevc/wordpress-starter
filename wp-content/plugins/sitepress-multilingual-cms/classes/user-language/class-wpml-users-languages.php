<?php

/**
 * @package wpml-core
 * @subpackage wpml-user-language
 */
class WPML_Users_Languages {
	/**
	 * @var WPML_Language_Code
	 */
	private $WPML_Language_Code;
	/**
	 * @var WPML_WP_API
	 */
	private $WPML_WP_API;

	/**
	 * @param WPML_Language_Code $WPML_Language_Code
	 * @param WPML_WP_API        $WPML_WP_API
	 */
	public function __construct( &$WPML_Language_Code, &$WPML_WP_API ) {
		$this->WPML_Language_Code = &$WPML_Language_Code;
		$this->WPML_WP_API = &$WPML_WP_API;
		$this->register_hooks();
	}

	public function register_hooks() {
		add_filter( 'wpml_user_language', array( $this, 'wpml_user_language_filter' ), 10, 2 );
	}

	public function wpml_user_language_filter( $language, $email ) {
		return $this->wpml_user_language( $language, $email );
	}

	private function wpml_user_language( $language, $email ) {
		$language_in_db = $this->get_recipient_language( $email );
		if ( $language_in_db ) {
			$language = $language_in_db;
		}

		return $this->WPML_Language_Code->sanitize( $language );
	}

	private function get_recipient_language( $email ) {
		$language = apply_filters( 'wpml_user_email_language', null, $email );

		if ( ! $language && is_email( $email ) ) {
			$language = $this->get_language_from_globals();
		}
		if ( ! $language ) {
			$language = $this->get_language_from_tables( $email );
		}
		if ( ! $language ) {
			$language = $this->get_language_from_fallbacks();
		}

		return $this->WPML_Language_Code->sanitize( $language );
	}

	private function get_language_from_globals() {
		$lang = null;

		$inputs = array($_POST, $_GET, $GLOBALS);

		foreach($inputs as $input) {
			if ( array_key_exists( 'wpml_user_email_language', $input ) ) {
				$lang = sanitize_title($input['wpml_user_email_language']);
				$lang = $this->WPML_Language_Code->sanitize( $lang );
				break;
			}
		}

		return $lang;
	}

	private function get_language_from_tables( $email ) {
		$lang = $this->WPML_Language_Code->get_from_user_meta( $email );

		return $this->WPML_Language_Code->sanitize( $lang );
	}

	private function get_language_from_fallbacks() {

		$lang = get_option( 'wpml_user_email_language' );
		if ( ! $lang ) {

			$lang = apply_filters( 'wpml_default_language', null );

			if ( $this->WPML_WP_API->is_front_end() ) {
				$lang = apply_filters( 'wpml_current_language', null );
			}
		}

		return $this->WPML_Language_Code->sanitize( $lang );
	}
}
