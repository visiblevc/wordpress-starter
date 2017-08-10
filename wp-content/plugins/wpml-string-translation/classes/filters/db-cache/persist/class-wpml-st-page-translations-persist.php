<?php

class WPML_ST_Page_Translations_Persist implements IWPML_ST_Page_Translations_Persist {
	const SHARED_CACHE_THRESHOLD = 500;
	const INSERT_CHUNK_SIZE = 2000;

	/** @var WPDB $wpdb */
	private $wpdb;

	/**
	 * @param WPDB $wpdb
	 */
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param $language
	 * @param $page_url
	 *
	 * @return WPML_ST_Page_Translations
	 */
	public function get_translations_for_page( $language, $page_url ) {
		$res_query = "
					SELECT DISTINCT 
						s.id,
						s.name,
						s.context,
						st.status,
						s.gettext_context, 
						st.value AS tra,
						s.value AS orig
					FROM {$this->wpdb->prefix}icl_string_pages sp
					INNER JOIN {$this->wpdb->prefix}icl_string_urls su
						ON su.id = sp.url_id
					INNER JOIN {$this->wpdb->prefix}icl_strings s
						ON s.id = sp.string_id
					LEFT JOIN {$this->wpdb->prefix}icl_string_translations st
						ON s.id=st.string_id
							AND st.language=su.language
							AND s.language!=su.language
					WHERE (su.language=%s and su.url=%s) or (su.language=%s and su.url IS NULL)
					";

		$res_prepare = $this->wpdb->prepare( $res_query, array( $language, $page_url, $language ) );
		$rowset      = $this->wpdb->get_results( $res_prepare, ARRAY_A );

		$rowset = is_array( $rowset ) ? $rowset : array();
		$rowset = $this->validate_rowset( $rowset ) ? $rowset : array();
		$rowset = array_map( array( $this, 'create_translation_from_db_record' ), $rowset );

		return new WPML_ST_Page_Translations( $rowset );
	}

	/**
	 * @param array $rowset
	 *
	 * @return bool
	 */
	private function validate_rowset( array $rowset ) {
		if ( 0 === count( $rowset ) ) {
			return true;
		}

		$expected_fields = array( 'id', 'name', 'context', 'status', 'gettext_context', 'tra', 'orig' );
		$row             = current( $rowset );

		if ( count( $row ) !== count( $expected_fields ) ) {
			return false;
		}

		foreach ( $expected_fields as $field ) {
			if ( ! array_key_exists( $field, $row ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $res
	 *
	 * @return WPML_ST_Page_Translation
	 */
	private function create_translation_from_db_record( array $res ) {
		$has_translation = ! empty( $res['tra'] ) && ICL_TM_COMPLETE == $res['status'];
		$value = $has_translation ? $res['tra'] : $res['orig'];

		return new WPML_ST_Page_Translation(
			$res['id'],
			$res['name'],
			$res['context'],
			$value,
			$has_translation,
			$res['gettext_context']
		);
	}

	/**
	 * @param string $language
	 * @param string $page_url
	 * @param WPML_ST_Page_Translation[] $translations
	 */
	public function store_new_translations( $language, $page_url, $translations ) {
		$shared_cache = $this->should_use_shared_cache( $language, $translations );
		$url_id = $this->fetch_or_create_url_id( $language, $shared_cache ? null : $page_url );

		foreach ( array_chunk( $translations, self::INSERT_CHUNK_SIZE ) as $translations_chunk ) {
			$query = "INSERT IGNORE INTO {$this->wpdb->prefix}icl_string_pages (`string_id`, `url_id`) VALUES ";

			$i = 0;
			foreach ( $translations_chunk as $translation ) {
				if ( $i > 0 ) {
					$query .= ',';
				}

				$query .= sprintf( '(%d, %d)', (int) $translation->get_string_id(), (int) $url_id );
				$i ++;
			}

			$this->wpdb->query( $query );
		}
	}

	/**
	 * @param string $language
	 * @param string|null $page_url
	 *
	 * @return int
	 */
	private function fetch_or_create_url_id( $language, $page_url ) {
		$id = null;
		if ( $page_url ) {
			$sql = "SELECT id FROM {$this->wpdb->prefix}icl_string_urls WHERE language=%s and url=%s";
			$sql = $this->wpdb->prepare( $sql, array( $language, $page_url ) );
			$id  = $this->wpdb->get_var( $sql );
		}

		if ( ! $id ) {
			// wpdb::insert method is not used due to problems with converting NULL value to empty string in some cases
			$params = array( $language );
			if ( null !== $page_url ) {
				$params[] = $page_url;
			}

			$sql = "INSERT IGNORE INTO {$this->wpdb->prefix}icl_string_urls (`language`, `url`) VALUES (%s, ";
			$sql .= null !== $page_url ? '%s' : 'NULL';
			$sql .= ')';
			$sql = $this->wpdb->prepare( $sql, $params );

			$this->wpdb->query( $sql );
			$id = $this->wpdb->insert_id;
		}

		return $id;
	}

	public function clear_cache() {

		if ( $this->table_exists( $this->wpdb->prefix . 'icl_string_pages' ) ) {
			$sql = "TRUNCATE `{$this->wpdb->prefix}icl_string_pages`";
			$this->wpdb->query( $sql );
		}

		if ( $this->table_exists( $this->wpdb->prefix . 'icl_string_urls' ) ) {
			$sql = "TRUNCATE `{$this->wpdb->prefix}icl_string_urls`";
			$this->wpdb->query( $sql );
		}
	}

	private function table_exists( $table ) {
		return $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	/**
	 * @param $language
	 *
	 * @return bool
	 */
	private function has_any_strings_in_shared_cached( $language ) {
		$sql = "
			SELECT id FROM {$this->wpdb->prefix}icl_string_urls
			WHERE language = %s AND url IS NULL
			ORDER BY id ASC LIMIT 1
		";

		$query = $this->wpdb->prepare( $sql, array( $language ) );
		$value = $this->wpdb->get_var( $query );

		return (bool) $value;
	}

	/**
	 * @param $language
	 * @param $translations
	 *
	 * @return bool
	 */
	private function should_use_shared_cache( $language, $translations ) {
		$shared_cache = false;

		if ( self::SHARED_CACHE_THRESHOLD < count( $translations ) ) {
			$shared_cache = ! $this->has_any_strings_in_shared_cached( $language );
		}

		return $shared_cache;
	}
}
