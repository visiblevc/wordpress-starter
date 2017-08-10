<?php

/**
 * @package    wpml-core
 * @subpackage wpml-user-language
 */
class WPML_Language_Code extends WPML_SP_User {

	private $WPML_WP_API;

	function __construct( &$sitepress ) {
		parent::__construct( $sitepress );

		$this->WPML_WP_API = $this->sitepress->get_wp_api();
	}

	function sanitize( $code ) {
		$code = trim( (string) $code );
		if ( $code ) {
			if ( strlen( $code ) < 2 ) {
				return false;
			}
			if ( strlen( $code ) > 2 ) {
				$code = substr( $code, 0, 2 );
			}
			$code = strtolower( $code );
		}

		$languages = $this->sitepress->get_languages();

		if ( ! isset( $languages[ $code ] ) ) {
			return false;
		}

		if ( ! (bool) $code ) {
			$code = null;
		}

		return $code;
	}

	function get_from_user_meta( $email ) {
		$language = false;
		$user     = get_user_by( 'email', $email );
		if ( $user && isset( $user->ID ) ) {
			$language = $this->WPML_WP_API->get_user_meta( $user->ID, 'icl_admin_language', true );
		}

		return $this->sanitize( $language );
	}
}
