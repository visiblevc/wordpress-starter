<?php

/**
 * Class WPML_TM_Dashboard
 */
class WPML_TM_Dashboard {

	/**
	 * @var array
	 */
	private $translatable_post_types = null;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WPML_TM_Dashboard constructor.
	 *
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb = $wpdb;
		$this->sitepress = $sitepress;
		add_filter( 'posts_where', array( $this, 'add_dashboard_filter_conditions' ), 10, 2 );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_documents( $args = array() ) {
		$results = array();

		$defaults = array(
			'from_lang'   => 'en',
			'to_lang'     => '',
			'tstatus'     => -1,
			'sort_by'     => 'date',
			'sort_order'  => 'DESC',
			'limit_no'    => ICL_TM_DOCS_PER_PAGE,
			'parent_type' => 'any',
			'parent_id'   => false,
			'type'        => '',
			'title'       => '',
			'status'      => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),
		);

		$args = $this->remove_empty_arguments( $args );
		$args = wp_parse_args( $args, $defaults );

		$results = $this->add_string_packages( $results, $args );
		$results = $this->add_translatable_posts( $results, $args );

		return array_slice( $results, 0, $args['limit_no'] );
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	private function remove_empty_arguments( $args ) {
		$output = array();
		foreach ( $args as $argument_name => $argument_value ) {
			if ( '' !== $argument_value ) {
				$output[ $argument_name ] = $argument_value;
			}
		}

		return $output;
	}

	/**
	 * Add list of translatable post types to dashboard.
	 *
	 * @param array $results
	 * @param array $args
	 *
	 * @return array
	 */
	private function add_translatable_posts( $results, $args ) {
		global $sitepress;

		$post_types = $this->get_translatable_post_types();
		if ( array_key_exists( 'type', $args ) && ! empty( $args['type'] ) ) {
			$post_types = array( $args['type'] );
		}
		$query_args = array(
			'post_type'                => $post_types,
			'order_by'                 => $args['sort_by'],
			'order'                    => $args['sort_order'],
			'posts_per_page'           => $args['limit_no'] + 1,
			'post_status'              => $args['status'],
			'post_language'            => $args['from_lang'],
			'post_language_to'         => $args['to_lang'],
			'post_translation_status'  => $args['tstatus'],
			'suppress_filters'         => false,
			'update_post_meta_cache'   => false,
			'update_post_term_cache'   => false,
			'no_found_rows'            => true,
		);

		if ( 'any' !== $args['parent_type'] ) {
			switch ( $args['parent_type'] ) {
				case 'page':
					$query_args['post_parent'] = (int) $args['parent_id'];
					break;
				case 'category':
					$query_args['tax_query'] = array(
						array(
							'taxonomy' => 'category',
							'field'    => 'term_id',
							'terms'    => (int) $args['parent_id'],
						),
					);
					break;
			}
		}

		if ( ! empty( $args['title'] ) ) {
			$query_args['post_title_like'] = $args['title'];
		}

		$lang = $sitepress->get_current_language();
		$sitepress->switch_lang( $args['from_lang'] );
		$query = new WP_Query( $query_args );
		$sitepress->switch_lang( $lang );
		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post ) {
				$language_details                   = $sitepress->get_element_language_details( $post->ID, 'post_' . $post->post_type );
				$post_obj                           = new stdClass();
				$post_obj->ID                       = $post->ID;
				$post_obj->translation_element_type = 'post_' . $post->post_type;
				$post_obj->title                    = $post->post_title;
				$post_obj->is_translation           = ( null === $language_details->source_language_code ) ? '0' : '1';
				$post_obj->language_code            = $language_details->language_code;
				$post_obj->trid                     = $language_details->trid;
				$results[]                          = $post_obj;
			}
		}
		wp_reset_query();
		return $results;
	}

	/**
	 * Add additional where conditions to support the following query arguments:
	 *  - post_title_like         - Allow query posts with SQL LIKE in post title.
	 *  - post_language_to        - Allow query posts with language they are translated to.
	 *  - post_translation_status - Allow to query posts by their translation status.
	 * @param string $where
	 * @param object $wp_query
	 *
	 * @return string
	 */
	public function add_dashboard_filter_conditions( $where, &$wp_query ) {
		$post_title_like = $wp_query->get( 'post_title_like' );
		$post_language = $wp_query->get( 'post_language_to' );
		$post_translation_status = (int) $wp_query->get( 'post_translation_status' );
		$translations_table_name = $this->wpdb->prefix . 'icl_translations';

		if ( $post_title_like ) {
			$where .= $this->wpdb->prepare( " AND {$this->wpdb->posts}.post_title LIKE '%s'", '%' . $this->wpdb->esc_like( $post_title_like ) . '%' );
		}

		if ( ! empty( $post_language ) ) {
			$where .= $this->wpdb->prepare( " AND t.trid IN (SELECT trid FROM {$translations_table_name} 
			WHERE {$translations_table_name}.language_code='%s')", $post_language );
		}

		$post_type = $wp_query->get( 'post_type' );
		if ( $post_translation_status >= 0 && $this->is_cpt_type( array(), $post_type[0] ) ) {
			$where .= $this->build_translation_status_where( $post_translation_status );
		}
		return $where;
	}

	/**
	 * Add string packages to translation dashboard.
	 * @param array $results
	 * @param array $args
	 *
	 * @return array
	 */
	private function add_string_packages( $results, $args ) {
		$string_packages_table = $this->wpdb->prefix . 'icl_string_packages';
		$translations_table = $this->wpdb->prefix . 'icl_translations';

		if ( $this->is_cpt_type( $args ) ) {
			return array();
		}

		if ( ! is_plugin_active( 'wpml-string-translation/plugin.php' ) ) {
			return $results;
		}

		// Exit if *icl_string_packages table doesn't exist.
		if ( $this->wpdb->get_var( "SHOW TABLES LIKE '$string_packages_table'" ) !== $string_packages_table ) {
			return $results;
		}

		$where = $this->create_string_packages_where( $args );
		$sql = "SELECT DISTINCT 
				 st_table.ID, 
				 st_table.kind_slug, 
				 st_table.title, 
				 t.element_type, 
				 t.language_code, 
				 t.source_language_code,
				 t.trid 
				 FROM {$string_packages_table} AS st_table
				 LEFT JOIN {$translations_table} AS t 
				 ON t.element_id=st_table.ID OR t.element_id = null 
				 WHERE 1 = 1 {$where} 
				 GROUP BY st_table.ID
				 ORDER BY st_table.ID ASC 
				 LIMIT {$args['limit_no']}";
		$packages = $this->wpdb->get_results( $sql );
		foreach ( $packages as $package ) {
			$package_obj                           = new stdClass();
			$package_obj->ID                       = $package->ID;
			$package_obj->translation_element_type = $package->element_type;
			$package_obj->title                    = $package->title;
			$package_obj->is_translation           = ( null === $package->source_language_code ) ? '0' : '1';
			$package_obj->language_code            = $package->language_code;
			$package_obj->trid                     = $package->trid;
			$results[] = $package_obj;
		}

		return $results;
	}

	/**
	 * Create additional where clause for querying string packages based on filters.
	 * @param array $args
	 *
	 * @return string
	 */
	private function create_string_packages_where( $args ) {
		$where = " AND t.element_type LIKE 'package%'";
		if ( ! $this->is_cpt_type( $args ) && ! empty( $args['type'] ) ) {
			$where .= $this->wpdb->prepare( " AND kind_slug='%s'", $args['type'] );
		}

		if ( ! empty( $args['title'] ) ) {
			$where .= $this->wpdb->prepare( " AND title LIKE '%s'", '%' . $this->wpdb->esc_like( $args['title'] ) . '%' );
		}

		if ( ! empty( $args['to_lang'] ) ) {
			$where .= $this->wpdb->prepare( " AND t.language_code='%s'", $args['to_lang'] );
			$where .= $this->wpdb->prepare( " AND t.source_language_code='%s'", $args['from_lang'] );
		} else {
			$where .= $this->wpdb->prepare( " AND t.language_code='%s'" , $args['from_lang'] );
		}

		if ( $args['tstatus'] >= 0 ) {
			$where .= $this->build_translation_status_where( $args['tstatus'] );
		}

		return $where;
	}

	/**
	 * @param integer $post_translation_status
	 *
	 * @return string
	 */
	private function build_translation_status_where( $post_translation_status ) {
		$post_translation_status = (int) $post_translation_status;
		$translation_status_table = $this->wpdb->prefix . 'icl_translation_status';
		$translations_table_name = $this->wpdb->prefix . 'icl_translations';
		$where = '';
		if ( ICL_TM_NEEDS_UPDATE === $post_translation_status ) {
			$where .= " AND t.trid IN (SELECT trid FROM {$translations_table_name} WHERE {$translations_table_name}.translation_id IN ";
			$where .= "(SELECT {$translation_status_table}.translation_id FROM {$translation_status_table} WHERE {$translation_status_table}.needs_update=1 ) )";
		} else {
			$status = false;
			if ( in_array( $post_translation_status, array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ) ) ) {
				$status = wpml_prepare_in( array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ), '%d' );
			} elseif ( ICL_TM_COMPLETE === $post_translation_status ) {
				$status = wpml_prepare_in( array( ICL_TM_COMPLETE, ICL_TM_DUPLICATE ), '%d' );
			} elseif ( in_array( $post_translation_status, array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ) ) ) {
				$status = wpml_prepare_in( array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ), '%d' );
			} else {
				$where .= $this->wpdb->prepare( " AND t.trid IN ( SELECT trid FROM {$translations_table_name} iclt  
					LEFT OUTER JOIN {$translation_status_table} tls ON iclt.translation_id = tls.translation_id 
					WHERE 
					element_type NOT LIKE 'tax_%%' 
					AND (
					tls.status IN (0) 
					OR tls.status IS NULL 
					AND (
							SELECT 
								COUNT(trid) 
							FROM 
								{$translations_table_name} 
							WHERE 
								trid = t.trid
							) < %d
						)
					) ", count( $this->sitepress->get_active_languages() ) );
			}
			if ( $status ) {
				$where .= " AND t.trid IN (SELECT trid FROM {$translations_table_name} WHERE {$translations_table_name}.translation_id IN ";
				$where .= "(SELECT {$translation_status_table}.translation_id 
							FROM {$translation_status_table} 
							WHERE {$translation_status_table}.status IN (" . $status . ") ) )";
			}
		}

		return $where;
	}

	/**
	 * @param array $args
	 * @param string $post_type
	 *
	 * @return bool
	 */
	private function is_cpt_type( $args = array(), $post_type = '' ) {
		$is_cpt_type = false;
		if ( ! empty( $args ) && '' === $post_type && array_key_exists( 'type', $args ) && ! empty( $args['type'] ) ) {
			$post_type = $args['type'];
		}

		if ( in_array( $post_type, $this->get_translatable_post_types() ) ) {
			$is_cpt_type = true;
		}

		return $is_cpt_type;
	}

	/**
	 * @return array
	 */
	private function get_translatable_post_types() {
		if ( null === $this->translatable_post_types ) {
			$translatable_post_types = $this->sitepress->get_translatable_documents();
			$this->translatable_post_types = array_keys( apply_filters( 'wpml_tm_dashboard_translatable_types', $translatable_post_types ) );
		}

		return $this->translatable_post_types;
	}
}
