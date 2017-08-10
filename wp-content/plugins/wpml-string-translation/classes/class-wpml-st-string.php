<?php

/**
 * WPML_ST_String class
 *
 * Low level access to string in Database
 *
 * NOTE: Don't use this class to process a large amount of strings as it doesn't
 * do any caching, etc.
 *
 */
class WPML_ST_String extends WPML_WPDB_User {

	private $string_id;

	/** @var  string $language */
	private $language;

	/** @var  int $status */
	private $status;

	/**
	 * @param int $string_id
	 * @param wpdb $wpdb
	 */
	public function __construct( $string_id, &$wpdb ) {
		parent::__construct( $wpdb );

		$this->string_id = $string_id;
	}

	/**
	 * @return int
	 */
	public function string_id() {

		return $this->string_id;
	}

	/**
	 * @return string|null
	 */
	public function get_language() {
		$this->language = $this->language
			? $this->language
			: $this->wpdb->get_var(
				"SELECT language " . $this->from_where_snippet() . " LIMIT 1" );

		return $this->language;
	}
	
	/**
	 * @return string
	 */
	
	public function get_value() {
		return $this->wpdb->get_var( "SELECT value " . $this->from_where_snippet() . " LIMIT 1" );
	}

	/**
	 * @return int
	 */
	public function get_status() {

		$this->status = $this->status !== null
			? $this->status
			: (int) $this->wpdb->get_var(
				"SELECT status " . $this->from_where_snippet() . " LIMIT 1" );

		return $this->status;
	}

	/**
	 * @param string $language
	 */
	public function set_language( $language ) {
		if ( $language !== $this->get_language() ) {
			$this->language = $language;
		$this->set_property( 'language', $language );
			$this->update_status();
		}
	}

	/**
	 * @return object[]
	 */
	public function get_translation_statuses() {

		return $this->wpdb->get_results( "SELECT language, status " . $this->from_where_snippet( true ) );
	}

	/**
	 */
	public function update_status() {
		global $sitepress;

		$st = $this->get_translation_statuses();

		if ( $st ) {

			$string_language = $this->get_language();
			foreach ( $st as $t ) {
				if ( $string_language != $t->language ) {
					$translations[ $t->language ] = $t->status;
				}
			}

			$active_languages = $sitepress->get_active_languages();

			if ( empty( $translations ) || max( $translations ) == ICL_TM_NOT_TRANSLATED ) {
				$status = ICL_TM_NOT_TRANSLATED;
			} elseif ( in_array( ICL_TM_WAITING_FOR_TRANSLATOR, $translations ) ) {
				$status = ICL_TM_WAITING_FOR_TRANSLATOR;
			} elseif ( count( $translations ) < count( $active_languages ) - intval( in_array( $string_language, array_keys( $active_languages ) ) ) ) {
				if ( in_array( ICL_TM_NEEDS_UPDATE, $translations ) ) {
					$status = ICL_TM_NEEDS_UPDATE;
				} elseif ( in_array( ICL_TM_COMPLETE, $translations ) ) {
					$status = ICL_STRING_TRANSLATION_PARTIAL;
				} else {
					$status = ICL_TM_NOT_TRANSLATED;
				}
			} elseif ( ICL_TM_NEEDS_UPDATE == array_unique( $translations ) ) {
				$status = ICL_TM_NEEDS_UPDATE;
			} else {
				if ( in_array( ICL_TM_NEEDS_UPDATE, $translations ) ) {
					$status = ICL_TM_NEEDS_UPDATE;
				} elseif ( in_array( ICL_TM_NOT_TRANSLATED, $translations ) ) {
					$status = ICL_STRING_TRANSLATION_PARTIAL;
				} else {
					$status = ICL_TM_COMPLETE;
				}
			}
		} else {
			$status = ICL_TM_NOT_TRANSLATED;
		}
		if ( $status !== $this->get_status() ) {
			$this->status = $status;
		$this->set_property( 'status', $status );
		}

		return $status;
	}

	/**
	 * @param string          $language
	 * @param string|null     $value
	 * @param int|bool|false  $status
	 * @param int|null        $translator_id
	 * @param string|int|null $translation_service
	 * @param int|null        $batch_id
	 *
	 * @return bool|int id of the translation
	 */
	public function set_translation( $language, $value = null, $status = false, $translator_id = null, $translation_service = null, $batch_id = null ) {
		/** @var $ICL_Pro_Translation WPML_Pro_Translation */
		global $ICL_Pro_Translation;

		$res          = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT id, value, status
                                          " . $this->from_where_snippet( true )
		                                                            . " AND language=%s", $language ) );
		if ( isset( $res->status ) && $res->status == ICL_TM_WAITING_FOR_TRANSLATOR && is_null( $value ) ) {

			return false;
		}

		$translation_data = array();
		if ( $translation_service ) {
			$translation_data['translation_service'] = $translation_service;
		}
		if ( $batch_id ) {
			$translation_data['batch_id'] = $batch_id;
		}
		if ( ! is_null( $value ) ) {
			$translation_data['value'] = $value;
		}
		if ( $translator_id ) {
			$translation_data['translator_id'] = $translator_id;
		}

		if ( $res ) {
			$st_id = $res->id;
			if ( $status ) {
				$translation_data['status'] = $status;
			} elseif ( $status === ICL_TM_NOT_TRANSLATED ) {
				$translation_data['status'] = ICL_TM_NOT_TRANSLATED;
			}

			if ( ! empty( $translation_data ) ) {
				$st_update['translation_date'] = current_time( "mysql" );
				$this->wpdb->update( $this->wpdb->prefix . 'icl_string_translations', $translation_data, array( 'id' => $st_id ) );
			}
		} else {
			$translation_data = array_merge( $translation_data, array(
				'string_id'     => $this->string_id,
				'language'      => $language,
				'status'        => ( $status ? $status : ICL_TM_NOT_TRANSLATED ),
			) );

			$this->wpdb->insert( $this->wpdb->prefix . 'icl_string_translations', $translation_data );
			$st_id = $this->wpdb->insert_id;
		}

		if ( $ICL_Pro_Translation ) {
			$ICL_Pro_Translation->fix_links_to_translated_content( $st_id, $language, 'string' );
		}

		icl_update_string_status( $this->string_id );
		do_action( 'icl_st_add_string_translation', $st_id );

		return $st_id;
	}

	/**
	 * @param string $property
	 * @param mixed  $value
	 */
	protected function set_property( $property, $value ) {
		$this->wpdb->update( $this->wpdb->prefix . 'icl_strings', array( $property => $value ), array( 'id' => $this->string_id ) );
	}

	/**
	 * @param bool $translations sets whether to use original or translations table
	 *
	 * @return string
	 */
	protected function from_where_snippet( $translations = false ) {

		if ( $translations ) {
			$id_column = 'string_id';
			$table     = 'icl_string_translations';
		} else {
			$id_column = 'id';
			$table     = 'icl_strings';
		}

		return $this->wpdb->prepare( "FROM {$this->wpdb->prefix}{$table} WHERE {$id_column}=%d", $this->string_id );
	}
}