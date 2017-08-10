<?php

class WPML_ST_ICL_String_Translations extends WPML_WPDB_User {

	private $table = 'icl_string_translations';
	private $string_id = 0;
	private $lang_code;
	private $id;

	/**
	 * WPML_ST_ICL_String_Translations constructor.
	 *
	 * @param wpdb   $wpdb
	 * @param int    $string_id
	 * @param string $lang_code
	 */
	public function __construct( &$wpdb, $string_id, $lang_code ) {
		parent::__construct( $wpdb );
		$string_id = (int) $string_id;
		if ( $string_id > 0 && $lang_code ) {
			$this->string_id = $string_id;
			$this->lang_code = $lang_code;
		} else {
			throw new InvalidArgumentException( 'Invalid String ID: '
			                                    . $string_id . ' or language_code: ' . $lang_code );
		}
	}

	/**
	 * @return int|string
	 */
	public function translator_id() {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT translator_id
									FROM {$this->wpdb->prefix}{$this->table}
									WHERE id = %d LIMIT 1",
				$this->id() ) );
	}

	/**
	 * @return string
	 */
	public function value() {

		return $this->wpdb->get_var(
			$this->wpdb->prepare( " SELECT value
									FROM {$this->wpdb->prefix}{$this->table}
									WHERE id = %d LIMIT 1",
				$this->id() ) );
	}

	/**
	 * @return int
	 */
	public function id() {

		return (int) ( $this->id
			? $this->id
			: $this->wpdb->get_var(
				$this->wpdb->prepare( " SELECT id
									FROM {$this->wpdb->prefix}{$this->table}
									WHERE string_id = %d AND language = %s
									LIMIT 1",
					$this->string_id, $this->lang_code ) ) );
	}
}