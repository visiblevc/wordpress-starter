<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Translations extends WPML_SP_User {
	/** @var bool */
	public $skip_empty = false;
	/** @var bool */
	public $all_statuses = false;
	/** @var bool */
	public $skip_cache = false;
	/** @var bool */
	public $skip_recursions = false;

	private $duplicated_by              = array();
	private $mark_as_duplicate_meta_key = '_icl_lang_duplicate_of';
	private $wpml_cache;

	/**
	 * WPML_Translations constructor.
	 *
	 * @param SitePress     $sitepress
	 * @param WPML_WP_Cache $wpml_cache
	 */
	public function __construct( SitePress $sitepress, WPML_WP_Cache $wpml_cache = null ) {
		parent::__construct( $sitepress );
		$this->wpml_cache = $wpml_cache ? $wpml_cache : new WPML_WP_Cache( WPML_ELEMENT_TRANSLATIONS_CACHE_GROUP );
	}

	/**
	 * @param $trid
	 * @param $wpml_element_type
	 *
	 * @return array|bool|false|mixed
	 */
	public function get_translations( $trid, $wpml_element_type ) {
		$cache_key_args = array_filter( array( $trid, $wpml_element_type, $this->skip_empty, $this->all_statuses, $this->skip_recursions ) );
		$cache_key   = md5( wp_json_encode( $cache_key_args ) );
		$cache_found = false;

		$temp_elements = $this->wpml_cache->get( $cache_key, $cache_found );
		if ( ! $this->skip_cache && $cache_found ) {
			return $temp_elements;
		}

		$translations = array();
		$sql_parts    = array(
			'select'   => array(),
			'join'     => array(),
			'where'    => array(),
			'group_by' => array(),
		);
		if ( $trid ) {

			if ( $this->wpml_element_type_is_post( $wpml_element_type ) ) {
				$sql_parts = $this->get_sql_parts_for_post( $wpml_element_type, $sql_parts );
			} elseif ( $this->wpml_element_type_is_taxonomy( $wpml_element_type ) ) {
				$sql_parts = $this->get_sql_parts_for_taxonomy( $sql_parts );
			}
			$sql_parts['where'][] = $this->sitepress->get_wpdb()->prepare( ' AND t.trid=%d ', $trid );

			$select   = implode( ' ', $sql_parts['select'] );
			$join     = implode( ' ', $sql_parts['join'] );
			$where    = implode( ' ', $sql_parts['where'] );
			$group_by = implode( ' ', $sql_parts['group_by'] );

			$query = "
				SELECT t.translation_id, t.language_code, t.element_id, t.source_language_code, t.element_type, NULLIF(t.source_language_code, '') IS NULL AS original 
				{$select}
				FROM {$this->sitepress->get_wpdb()->prefix}icl_translations t
					 {$join}
				WHERE 1 {$where}
				{$group_by}
			";

			$results = $this->sitepress->get_wpdb()->get_results( $query );

			foreach ( $results as $translation ) {
				if ( $this->must_ignore_translation( $translation ) ) {
					continue;
				}

				$cached_object_key = $translation->element_id . '#' . $wpml_element_type . '#0#' . $translation->language_code;
				wp_cache_set( $cached_object_key, $cached_object_key, 'icl_object_id' );

				$translations[ $translation->language_code ] = $translation;
			}
		}

		if ( $translations ) {
			$this->wpml_cache->set( $cache_key, $translations );
		}

		return $translations;
	}

	public function link_elements( WPML_Translation_Element $source_translation_element, WPML_Translation_Element $target_translation_element, $target_language = null ) {
		if ( null !== $target_language ) {
			$this->set_language_code( $target_translation_element, $target_language );
		}
		$this->set_source_element( $target_translation_element, $source_translation_element );
	}

	public function set_source_element( WPML_Translation_Element $element, WPML_Translation_Element $source_element ) {
		$this->elements_type_matches( $element, $source_element );

		$this->sitepress->set_element_language_details( $element->get_element_id(), $element->get_wpml_element_type(), $source_element->get_trid(), $element->get_language_code(), $source_element->get_language_code() );

		$element->flush_cache();
	}

	private function elements_type_matches( $element1, $element2 ) {
		if ( get_class( $element1 ) !== get_class( $element2 ) ) {
			throw new UnexpectedValueException( '$source_element is not an instance of ' . get_class( $element1 ) . ': instance of ' . get_class( $element2 ) . ' received instead.' );
		}
	}

	/**
	 * @param WPML_Translation_Element $element
	 * @param string                   $language_code
	 */
	public function set_language_code( WPML_Translation_Element $element, $language_code ) {
		$element_id        = $element->get_element_id();
		$wpml_element_type = $element->get_wpml_element_type();
		$trid              = $element->get_trid();
		$this->sitepress->set_element_language_details( $element_id, $wpml_element_type, $trid, $language_code );
		$element->flush_cache();
	}

	/**
	 * @param WPML_Translation_Element $element
	 * @param int                      $trid
	 *
	 * @throws \UnexpectedValueException
	 */
	public function set_trid( WPML_Translation_Element $element, $trid ) {
		if ( ! $element->get_language_code() ) {
			throw new UnexpectedValueException( 'Element has no language information.' );
		}
		$this->sitepress->set_element_language_details( $element->get_element_id(), $element->get_wpml_element_type(), $trid, $element->get_language_code() );
		$element->flush_cache();
	}

	/**
	 * @param WPML_Duplicable_Element|WPML_Translation_Element $duplicate
	 * @param WPML_Duplicable_Element|WPML_Translation_Element $original
	 *
	 * @throws \UnexpectedValueException
	 */
	public function make_duplicate_of( WPML_Duplicable_Element $duplicate, WPML_Duplicable_Element $original ) {
		$this->validate_duplicable_element( $duplicate );
		$this->validate_duplicable_element( $original, 'source' );
		$this->set_source_element( $duplicate, $original );
		update_post_meta( $duplicate->get_id(), $this->mark_as_duplicate_meta_key, $original->get_id() );
		$duplicate->flush_cache();
		$this->duplicated_by[ $duplicate->get_id() ] = array();
	}

	/**
	 * @param WPML_Duplicable_Element|WPML_Translation_Element $element
	 *
	 * @return WPML_Post_Element
	 * @throws \InvalidArgumentException
	 */
	public function is_a_duplicate_of( WPML_Duplicable_Element $element ) {
		$this->validate_duplicable_element( $element );
		$duplicate_of = get_post_meta( $element->get_id(), $this->mark_as_duplicate_meta_key, true );
		if ( $duplicate_of ) {
			return new WPML_Post_Element( $duplicate_of, $this->sitepress );
		}

		return null;
	}

	/**
	 * @param WPML_Duplicable_Element|WPML_Translation_Element $element
	 *
	 * @return array
	 * @throws \UnexpectedValueException
	 * @throws \InvalidArgumentException
	 */
	public function is_duplicated_by( WPML_Duplicable_Element $element ) {
		$this->validate_duplicable_element( $element );

		$this->init_cache_for_element( $element );

		if ( ! $this->duplicated_by[ $element->get_id() ] ) {
			$this->duplicated_by[ $element->get_id() ] = array();

			$args  = array(
				'post_type'  => $element->get_wp_element_type(),
				'meta_query' => array(
					array(
						'key'     => $this->mark_as_duplicate_meta_key,
						'value'   => $element->get_id(),
						'compare' => '=',
					),
				),
			);
			$query = new WP_Query( $args );

			$results = $query->get_posts();
			foreach ( $results as $post ) {
				$this->duplicated_by[ $element->get_id() ][] = new WPML_Post_Element( $post->ID, $this->sitepress );
			}
		}

		return $this->duplicated_by[ $element->get_id() ];
	}

	/**
	 * @param WPML_Translation_Element $element
	 * @param string                   $argument_name
	 *
	 * @throws \UnexpectedValueException
	 */
	private function validate_duplicable_element( WPML_Translation_Element $element, $argument_name = 'element' ) {
		if ( ! ( $element instanceof WPML_Duplicable_Element ) ) {
			throw new UnexpectedValueException( sprintf( 'Argument %s does not implement `WPML_Duplicable_Element`.', $argument_name ) );
		}
	}

	/**
	 * @param WPML_Translation_Element $element
	 */
	private function init_cache_for_element( WPML_Translation_Element $element ) {
		if ( ! array_key_exists( $element->get_id(), $this->duplicated_by ) ) {
			$this->duplicated_by[ $element->get_id() ] = array();
		}
	}

	/**
	 * @param $element_type
	 * @param $sql_parts
	 *
	 * @return mixed
	 */
	private function get_sql_parts_for_post( $element_type, $sql_parts ) {
		$sql_parts['select'][] = ', p.post_title, p.post_status';
		$sql_parts['join'][]   = " LEFT JOIN {$this->sitepress->get_wpdb()->posts} p ON t.element_id=p.ID";
		if ( ! $this->all_statuses && 'post_attachment' !== $element_type && ! is_admin() ) {
			// the current user may not be the admin but may have read private post/page caps!
			if ( current_user_can( 'read_private_pages' ) || current_user_can( 'read_private_posts' ) ) {
				$sql_parts['where'][] = " AND (p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'pending')";
				$sql_parts['where'][] = " AND (p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'pending')";
			} else {
				$sql_parts['where'][] = ' AND (';
				$sql_parts['where'][] = "p.post_status = 'publish' OR p.post_status = 'pending' ";
				if ( $uid = $this->sitepress->get_current_user()->ID ) {
					$sql_parts['where'][] = $this->sitepress->get_wpdb()->prepare( " OR (post_status in ('draft', 'private', 'pending') AND  post_author = %d)", $uid );
				}
				$sql_parts['where'][] = ') ';
			}
		}

		return $sql_parts;
	}

	/**
	 * @param $sql_parts
	 *
	 * @return mixed
	 */
	private function get_sql_parts_for_taxonomy( $sql_parts ) {
		$sql_parts['select'][]   = ', tm.name, tm.term_id, COUNT(tr.object_id) AS instances';
		$sql_parts['join'][]     = " LEFT JOIN {$this->sitepress->get_wpdb()->term_taxonomy} tt ON t.element_id=tt.term_taxonomy_id
							  LEFT JOIN {$this->sitepress->get_wpdb()->terms} tm ON tt.term_id = tm.term_id
							  LEFT JOIN {$this->sitepress->get_wpdb()->term_relationships} tr ON tr.term_taxonomy_id=tt.term_taxonomy_id
							  ";
		$sql_parts['group_by'][] = 'GROUP BY tm.term_id';

		return $sql_parts;
	}

	/**
	 * @param stdClass $translation
	 *
	 * @return bool
	 */
	private function must_ignore_translation( $translation ) {
		return $this->wpml_element_type_is_taxonomy( $translation->element_type ) && $this->skip_empty && $translation->instances === 0 && ( ! $this->skip_recursions && ! _icl_tax_has_objects_recursive( $translation->element_id ) );
	}

	/**
	 * @param string $wpml_element_type
	 *
	 * @return int
	 */
	private function wpml_element_type_is_taxonomy( $wpml_element_type ) {
		return preg_match( '#^tax_(.+)$#', $wpml_element_type );
	}

	/**
	 * @param string $wpml_element_type
	 *
	 * @return bool
	 */
	private function wpml_element_type_is_post( $wpml_element_type ) {
		return 0 === strpos( $wpml_element_type, 'post_' );
	}
}

