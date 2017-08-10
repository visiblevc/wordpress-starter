<?php

class WPML_ST_DB_Translation_Retrieve {

	/**
	 * @var WPDB $wpdb
	 */
	public $wpdb;

	/**
	 * @var array
	 */
	private $loaded = array();

	/**
	 * @var array
	 */
	private $loaded_contexts = array();

	/**
	 * @var WPML_ST_Domain_Fallback
	 */
	private $domain_fallback;

	/**
	 * @var WPML_DB_Chunk
	 */
	private $chunk_retrieve;

	/**
	 * @param WPDB $wpdb
	 */
	public function __construct( WPDB $wpdb ) {
		$this->wpdb = $wpdb;
		$this->domain_fallback = new WPML_ST_Domain_Fallback();
		$this->chunk_retrieve = new WPML_DB_Chunk( $wpdb );
	}

	/**
	 * @param string $language
	 * @param string $name
	 * @param string $context
	 * @param string $gettext_context
	 *
	 * @return WPML_ST_Page_Translation|null
	 */
	public function get_translation( $language, $name, $context, $gettext_context = '' ) {
		if ( ! in_array( $context, $this->loaded_contexts ) ) {
			$this->load( $language, $context );
		}

		$translation = $this->try_get_translation( $name, $context, $gettext_context );

		if ( ! $translation && $this->domain_fallback->has_fallback_domain( $context ) ) {
			$context = $this->domain_fallback->get_fallback_domain( $context );

			if ( ! in_array( $context, $this->loaded_contexts ) ) {
				$this->load( $language, $context );
			}
			$translation = $this->try_get_translation( $name, $context, $gettext_context );
		}

		return $translation;
	}

	public function clear_cache() {
		$this->loaded = array();
		$this->loaded_contexts = array();
	}

	/**
	 * @param string $language
	 * @param string $context
	 */
	protected function load( $language, $context ) {
		$args = array( $language, $language, $context );

		$query = "
			SELECT
				s.id,
				st.status,
				s.domain_name_context_md5 AS ctx ,
				st.value AS translated,
				s.value AS original,
				s.gettext_context
			FROM {$this->wpdb->prefix}icl_strings s
			LEFT JOIN {$this->wpdb->prefix}icl_string_translations st
				ON s.id=st.string_id
					AND st.language=%s
					AND s.language!=%s
			WHERE s.context = %s
			";

		$rowset = $this->chunk_retrieve->retrieve( $query, $args, $this->get_number_of_strings_in_context( $context ) );

		foreach ( $rowset as $row_data ) {
			$this->parse_result( $row_data, $context );
		}

		$this->loaded_contexts[] = $context;
	}

	/**
	 * @param string $context
	 *
	 * @return int
	 */
	private function get_number_of_strings_in_context( $context ) {
		$sql = "SELECT COUNT(id) FROM {$this->wpdb->prefix}icl_strings WHERE context = %s";
		$sql = $this->wpdb->prepare( $sql, array( $context ) );

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * @param string $name
	 * @param string $context
	 * @param string $gettext_content
	 *
	 * @return string
	 */
	private function create_key( $name, $context, $gettext_content ) {
		return md5( $context . $name. $gettext_content );
	}

	/**
	 * @param $name
	 * @param $context
	 * @param $gettext_context
	 *
	 * @return null|WPML_ST_Page_Translation
	 */
	private function try_get_translation( $name, $context, $gettext_context ) {
		$key = $this->create_key( $name, $context, $gettext_context );
		if ( isset( $this->loaded[ $context ][ $gettext_context ][ $key ] ) ) {
			$row_data = $this->loaded[ $context ][ $gettext_context ][ $key ];

			return $this->build_translation( $row_data, $name, $context, $gettext_context );
		}

		return null;
	}

	/**
	 * @param array $row_data
	 * @param string $name
	 * @param string $context
	 * @param string $gettext_context
	 *
	 * @return WPML_ST_Page_Translation
	 */
	private function build_translation( array $row_data, $name, $context, $gettext_context ) {
		return new WPML_ST_Page_Translation(
			$row_data[0],
			$name,
			$context,
			$row_data[1],
			count($row_data) > 2, // has an original value
			$gettext_context
		);
	}

	/**
	 * @param array $row_data
	 * @param string $context
	 */
	private function parse_result( array $row_data, $context ) {
		$has_translation = ! empty( $row_data['translated'] ) && ICL_TM_COMPLETE == $row_data['status'];
		$value           = $has_translation ? $row_data['translated'] : $row_data['original'];

		$data = array( $row_data['id'], $value );
		if ( $has_translation ) {
			$data[] = $row_data['original'];
		}

		$this->loaded[ $context ][ $row_data['gettext_context'] ][ $row_data['ctx'] ] = $data;
	}
}
