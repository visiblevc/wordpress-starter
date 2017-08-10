<?php
abstract class WPML_TM_Translatable_Element {

	protected $id;

	public function __construct( $id ) {
		$this->id = $id;
	}

	public abstract function get_words_count();

	public abstract function get_type_name( $label = null );

	protected function exclude_shortcodes_in_words_count() {
		if ( defined( 'EXCLUDE_SHORTCODES_IN_WORDS_COUNT' ) ) {
			return EXCLUDE_SHORTCODES_IN_WORDS_COUNT;
		}

		return false;
	}

	private function sanitize_string( $source ) {
		$result = $source;
		$result = html_entity_decode( $result );
		$result = strip_tags( $result );
		$result = trim( $result );
		if ( $this->exclude_shortcodes_in_words_count() ) {
			$result = strip_shortcodes( $result );
		}

		return $result;
	}

	protected function get_string_words_count( $language_code, $source ) {
		$sanitized_source = $this->sanitize_string( $source );
		$words            = 0;
		global $asian_languages;
		if ( $asian_languages && in_array( $language_code, $asian_languages ) ) {
			$words += strlen( strip_tags( $sanitized_source ) ) / ICL_ASIAN_LANGUAGE_CHAR_SIZE;
		} else {
			$words += count( preg_split( '/[\s\/]+/', $sanitized_source, 0, PREG_SPLIT_NO_EMPTY ) );
		}

		return $words;
	}
}