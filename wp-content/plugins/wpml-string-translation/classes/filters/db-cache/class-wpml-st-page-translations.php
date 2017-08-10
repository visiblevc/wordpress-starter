<?php

class WPML_ST_Page_Translations {
	/**
	 * @var array
	 */
	private $string_translations = array();

	/**
	 * @var array
	 */
	private $new_translations = array();

	/**
	 * @var WPML_ST_Domain_Fallback
	 */
	private $domain_fallback;

	/**
	 * Register constructor.
	 *
	 * @param WPML_ST_Page_Translation[] $string_translations
	 */
	public function __construct( array $string_translations = array() ) {
		foreach ( $string_translations as $translation ) {
			if ( $translation instanceof WPML_ST_Page_Translation ) {
				$this->string_translations[ $translation->get_context() ][ $translation->get_gettext_context() ][ $translation->get_name() ] = $translation;
			}
		}

		$this->domain_fallback = new WPML_ST_Domain_Fallback();
	}

	/**
	 * @param WPML_ST_Page_Translation
	 */
	public function add_translation( WPML_ST_Page_Translation $translation ) {
		$this->string_translations[ $translation->get_context() ][ $translation->get_gettext_context() ][ $translation->get_name() ] = $translation;

		$this->new_translations[] = $translation;
	}

	/**
	 * @param string $name
	 * @param string $context
	 *
	 * @return WPML_ST_Page_Translation|null
	 */
	public function get_translation( $name, $context, $gettext_context = '' ) {
		$result = isset( $this->string_translations[ $context ][ $gettext_context ][ $name ] ) ?
			$this->string_translations[ $context ][ $gettext_context ][ $name ]
			: null;

		if ( ! $result && $this->domain_fallback->has_fallback_domain( $context ) ) {
			$context = $this->domain_fallback->get_fallback_domain( $context );

			$result = isset( $this->string_translations[ $context ][ $gettext_context ][ $name ] ) ?
				$this->string_translations[ $context ][ $gettext_context ][ $name ]
				: null;
		}

		return $result;
	}

	/**
	 * @return WPML_ST_Page_Translation[]
	 */
	public function get_new_translations() {
		return $this->new_translations;
	}

	/**
	 * @return boolean
	 */
	public function has_new_translations() {
		return count( $this->new_translations ) > 0;
	}
}
