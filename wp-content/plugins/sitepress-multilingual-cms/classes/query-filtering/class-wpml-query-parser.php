<?php

/**
 * Class WPML_Query_Parser
 *
 * @since 3.2.3
 */
class WPML_Query_Parser {
	/** @var  WPML_Post_Translation $post_translations */
	protected $post_translationss;
	/** @var  WPML_Term_Translation $post_translations */
	protected $term_translations;
	/** @var SitePress $sitepress */
	protected $sitepress;
	/** @var WPDB $wpdb */
	public $wpdb;

	/** @var WPML_Query_Filter $query_filter */
	private $query_filter;

	/**
	 * @param SitePress         $sitepress
	 * @param WPML_Query_Filter $query_filter
	 */
	public function __construct( $sitepress, $query_filter ) {
		$this->sitepress         = $sitepress;
		$this->wpdb              = $sitepress->wpdb();
		$this->post_translations = $sitepress->post_translations();
		$this->term_translations = $sitepress->term_translations();
		$this->query_filter      = $query_filter;
	}

	/**
	 * @param WP_Query $q
	 * @param string $lang
	 *
	 * @return WP_Query
	 */
	private function adjust_default_taxonomies_query_vars( $q, $lang ) {
		$vars = array(
			'cat'              => array( 'type' => 'ids',   'tax' => 'category' ),
			'category_name'    => array( 'type' => 'slugs', 'tax' => 'category' ),
			'category__and'    => array( 'type' => 'ids',   'tax' => 'category' ),
			'category__in'     => array( 'type' => 'ids',   'tax' => 'category' ),
			'category__not_in' => array( 'type' => 'ids',   'tax' => 'category' ),
			'tag'              => array( 'type' => 'slugs', 'tax' => 'post_tag' ),
			'tag_id'           => array( 'type' => 'ids',   'tax' => 'post_tag' ),
			'tag__and'         => array( 'type' => 'ids',   'tax' => 'post_tag' ),
			'tag__in'          => array( 'type' => 'ids',   'tax' => 'post_tag' ),
			'tag__not_in'      => array( 'type' => 'ids',   'tax' => 'post_tag' ),
			'tag_slug__and'    => array( 'type' => 'slugs', 'tax' => 'post_tag' ),
			'tag_slug__in'     => array( 'type' => 'slugs', 'tax' => 'post_tag' ),
		);

		foreach ( $vars as $key => $args ) {

			if ( isset( $q->query_vars[ $key ] )
				 && ! ( empty( $q->query_vars[ $key ] ) || $q->query_vars[ $key ] === 0 )
			) {
				list( $values, $glue ) = $this->parse_scalar_values_in_query_vars( $q, $key, $args['type'] );
				$translated_values     = $this->translate_term_values( $values, $args['type'], $args['tax'], $lang );
				$q                     = $this->replace_query_vars_value( $q, $key, $translated_values, $glue );
			}
		}

		return $q;
	}

	/**
	 * @param WP_Query $q
	 * @param string   $key
	 * @param string   $type
	 *
	 * @return array
	 */
	private function parse_scalar_values_in_query_vars( $q, $key, $type ) {
		$glue   = false;
		$values = array();

		if( is_scalar( $q->query_vars[ $key ] ) ) {
			$glue = strpos( $q->query_vars[ $key ], ',' ) !== false ? ',' : $glue;
			$glue = strpos( $q->query_vars[ $key ], '+' ) !== false ? '+' : $glue;

			if( $glue ) {
				$values = explode( $glue, $q->query_vars[ $key ] );
			} else {
				$values = array( $q->query_vars[ $key ] );
			}

			$values = array_map( 'trim', $values );
			$values = $type === 'ids' ? array_map( 'intval', $values ) : $values;
		} else if ( is_array( $q->query_vars[ $key ] ) ) {
			$values = $q->query_vars[ $key ];
		}

		return array( $values, $glue );
	}

	/**
	 * @param array  $values
	 * @param string $type
	 * @param string $taxonomy
	 * @param string $lang
	 *
	 * @return array
	 */
	private function translate_term_values( $values, $type, $taxonomy, $lang ) {
		$translated_values = array();

		if ( $type === 'ids' ) {

			foreach ( $values as $id ) {
				$sign                = (int) $id < 0 ? - 1 : 1;
				$id                  = abs( $id );
				$translated_values[] = $sign * (int) $this->term_translations->term_id_in( $id, $lang, true );
			}

		} else if ( $type === 'slugs' ) {

			foreach ( $values as $slug ) {
				$slug_elements = explode( '/', $slug );

				foreach ( $slug_elements as &$slug_element ) {
					$slug_element = $this->translate_term_slug( $slug_element, $taxonomy, $lang );
				}

				$slug = implode( '/', $slug_elements );

				$translated_values[] = $slug;
			}
		}

		return $translated_values;
	}

	/**
	 * @param string $slug
	 * @param string $taxonomy
	 * @param string $lang
	 *
	 * @return null|string
	 */
	private function translate_term_slug( $slug, $taxonomy, $lang ) {
		$id = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT t.term_id FROM {$this->wpdb->terms} t
								 JOIN {$this->wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
								 WHERE tt.taxonomy = %s AND t.slug = %s LIMIT 1",
				$taxonomy,
				$slug
			)
		);

		$term_id = (int) $this->term_translations->term_id_in( $id, $lang, true );

		if ( $term_id !== $id ) {

			$slug = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT slug FROM {$this->wpdb->terms}
								 WHERE term_id = %d LIMIT 1",
					$term_id
				)
			);
		}

		return $slug;
	}

	/**
	 * @param WP_Query $q
	 * @param string   $key
	 * @param array    $translated_values
	 * @param string   $glue
	 *
	 * @return WP_Query
	 */
	private function replace_query_vars_value( $q, $key, $translated_values, $glue ) {
		if ( ! empty( $translated_values ) && ! empty( $translated_values[0] ) ) {

			$translated_values = array_unique( $translated_values );

			if( is_scalar( $q->query_vars[ $key ] ) ) {
				$q->query_vars[ $key ] = implode( $glue, $translated_values );
			} else if ( is_array( $q->query_vars[ $key ] ) ) {
				$q->query_vars[ $key ] = $translated_values;
			}
		}

		return $q;
	}

	/**
	 * @param WP_Query $q
	 *
	 * @return WP_Query
	 */
	private function adjust_taxonomy_query( $q ) {
		if ( isset( $q->query_vars['tax_query'] ) && is_array( $q->query_vars['tax_query'] )
		     && isset( $q->tax_query->queries ) && is_array( $q->tax_query->queries )
		     && isset( $q->query['tax_query'] ) && is_array( $q->query['tax_query'] )
		) {

			$new_conditions = $this->adjust_tax_query_conditions( $q->query['tax_query'] );
			$q->query['tax_query']      = $new_conditions;
			$q->tax_query->queries      = $new_conditions;
			$q->query_vars['tax_query'] = $new_conditions;
		}

		return $q;
	}

	/**
	 * Recursive method to allow conversion of nested conditions
	 *
	 * @param array $conditions
	 *
	 * @return array
	 */
	private function adjust_tax_query_conditions( $conditions ) {

		foreach ( $conditions as $key => $condition ) {

			if ( ! is_array( $condition ) ) { // e.g 'relation' => 'OR'
				continue;
			} else if ( ! isset( $condition['terms'] ) ) { // Process recursively the nested condition
				$conditions[ $key ] = $this->adjust_tax_query_conditions( $condition );
			} else if ( is_array( $condition['terms'] ) ) {

				foreach ( $condition['terms'] as $value ) {

					$field = isset( $condition['field'] ) ? $condition['field'] : 'term_id';
					$term  = $this->sitepress->get_wp_api()->get_term_by( $field, $value, $condition['taxonomy'] );

					if ( is_object( $term ) ) {

						if ( $field === 'id' && ! isset( $term->id ) ) {
							$translated_value = isset( $term->term_id ) ? $term->term_id : null;
						} else {
							$translated_value = isset( $term->{$field} ) ? $term->{$field} : null;
						}

						$index = array_search( $value, $condition['terms'] );
						$condition['terms'][ $index ] = $translated_value;
					}
				}

				$conditions[ $key ] = $condition;

			} else if ( is_scalar( $condition['terms'] ) ) {
				$field = isset( $condition['field'] ) ? $condition['field'] : 'id';
				$term  = $this->sitepress->get_wp_api()->get_term_by( $field, $condition['terms'], $condition['taxonomy'] );

				if ( is_object( $term ) ) {
					$field = $field == 'id' ? 'term_id' : $field;
					$conditions[ $key ]['terms'] = isset( $term->{$field} ) ? $term->{$field} : null;
				}
			}
		}

		return $conditions;
	}

	/**
	 * @param WP_Query $q
	 *
	 * @return WP_Query
	 */
	function parse_query( $q ) {
		if ( $this->sitepress->get_wp_api()->is_admin()
		     && ! $this->sitepress->get_wp_api()->constant( 'DOING_AJAX' )
		) {
			return $q;
		}

		$q = apply_filters( 'wpml_pre_parse_query', $q );

		list( $q, $redir_pid ) = $this->maybe_adjust_name_var( $q );
		/** @var WP_Query $q */
		if ( $q->is_main_query() && (bool) $redir_pid === true ) {
			if ( (bool) ( $redir_target = $this->is_redirected( $redir_pid, $q ) ) ) {
				$this->sitepress->get_wp_api()->wp_safe_redirect( $redir_target, 301 );
			}
		}

		$post_type = 'post';
		if ( ! empty( $q->query_vars['post_type'] ) ) {
			$post_type = $q->query_vars['post_type'];
		}

		$current_language = $this->sitepress->get_current_language();
		if ( 'attachment' === $post_type || $current_language !== $this->sitepress->get_default_language() ) {
			$q = $this->adjust_default_taxonomies_query_vars( $q, $current_language );

			if ( ! is_array( $post_type ) ) {
				$post_type = (array) $post_type;
			}
			if ( ! empty( $q->query_vars['page_id'] ) ) {
				$q->query_vars['page_id'] = $this->post_translations->element_id_in( $q->query_vars['page_id'],
				                                                                     $current_language,
				                                                                     true );
			}
			$q = $this->adjust_query_ids( $q, 'include' );
			$q = $this->adjust_query_ids( $q, 'exclude' );
			if ( isset( $q->query_vars['p'] ) && ! empty( $q->query_vars['p'] ) ) {
				$q->query_vars['p'] = $this->post_translations->element_id_in( $q->query_vars['p'],
				                                                               $current_language,
				                                                               true );
			}

			if ( $post_type ) {
				$first_post_type = reset( $post_type );

				if ( $this->sitepress->is_translated_post_type( $first_post_type ) && ! empty( $q->query_vars['name'] ) ) {
					if ( is_post_type_hierarchical( $first_post_type ) ) {
						$requested_page = get_page_by_path( $q->query_vars['name'], OBJECT, $first_post_type );
						if ( $requested_page ) {
							$q->query_vars['p'] = $this->post_translations->element_id_in( $requested_page->ID, $current_language, true );
							unset( $q->query_vars['name'] );
							// We need to set this to an empty string otherwise WP will derive the pagename from this.
							$q->query_vars[ $first_post_type ] = '';
						}
					} else {
						$pid_prepared = $this->wpdb->prepare( "SELECT ID FROM {$this->wpdb->posts} WHERE post_name=%s AND post_type=%s LIMIT 1", array( $q->query_vars['name'], $first_post_type ) );
						$pid          = $this->wpdb->get_var( $pid_prepared );
						if ( ! empty( $pid ) ) {
							$q->query_vars['p'] = $this->post_translations->element_id_in( $pid, $current_language, true );
							unset( $q->query_vars['name'] );
						}
					}
				}
				$q = $this->adjust_q_var_pids( $q, $post_type, 'post__in' );
				$q = $this->adjust_q_var_pids( $q, $post_type, 'post__not_in' );
				$q = $this->maybe_adjust_parent( $q, $post_type, $current_language );
			}
			//TODO: [WPML 3.3] Discuss this. Why WP assumes it's there if query vars are altered? Look at wp-includes/query.php line #2468 search: if ( $this->query_vars_changed ) {
			$q->query_vars['meta_query'] = isset( $q->query_vars['meta_query'] ) ? $q->query_vars['meta_query'] : array();

			$q = $this->adjust_taxonomy_query( $q );
		}

		$q = apply_filters( 'wpml_post_parse_query', $q );

		return $q;
	}

	/**
	 * Adjust the parent post in the query in case we're dealing with a translated
	 * post type.
	 *
	 * @param WP_Query        $q
	 * @param string|string[] $post_type
	 * @param string          $current_language
	 *
	 * @return WP_Query  mixed
	 */
	private function maybe_adjust_parent( $q, $post_type, $current_language ) {
		$post_type = ! is_scalar( $post_type ) && count( $post_type ) === 1 ? end( $post_type ) : $post_type;
		if ( ! empty( $q->query_vars['post_parent'] )
		     && $q->query_vars['post_type'] !== 'attachment'
		     && $post_type
		     && is_scalar( $post_type )
		     && $this->sitepress->is_translated_post_type( $post_type )
		) {
			$q->query_vars['post_parent'] = $this->post_translations->element_id_in(
				$q->query_vars['post_parent'],
				$current_language,
				true );
		}

		return $q;
	}

	/**
	 * Tries to transform certain queries from "by name" querying to "by ID" to overcome WordPress Core functionality
	 * for resolving names not being filtered by language
	 *
	 * @param WP_Query $q
	 *
	 * @return WP_Query
	 */
	private function maybe_adjust_name_var( $q ) {
		$redirect = false;
		if ( ( (bool) ( $name_in_q = $q->get( 'name' ) ) === true
		     || (bool) ( $name_in_q = $q->get( 'pagename' ) ) === true )
			&& (bool) $q->get( 'page_id' ) === false
			|| ( (bool) ( $post_type = $q->get('post_type') ) === true
                && is_scalar($post_type)
                && (bool) ( $name_in_q = $q->get($post_type)) === true ) ) {
			list( $name_found, $type, $altered ) = $this->query_filter->get_404_util()->guess_cpt_by_name( $name_in_q,
			                                                                                               $q );
			if ( $altered === true ) {
				$name_before = $q->get( 'name' );
				$q->set( 'name', $name_found );
			}
			$type = $type ? $type : 'page';
			$type = is_scalar( $type ) ? $type : ( count( $type ) === 1 ? end( $type ) : false );
			/**
			 * @var WP_Query $q
			 * @var $pid int|false
			 */
			list( $q, $redirect ) = $type
				? $this->query_filter->get_page_name_filter( $type )->filter_page_name( $q ) : array( $q, false );
			if ( isset( $name_before ) ) {
				$q->set( 'name', $name_before );
			}
		}

		return array( $q, $redirect );
	}

	private function adjust_query_ids( $q, $index ) {
		if ( ! empty( $q->query_vars[ $index ] ) ) {
			$untranslated = is_array( $q->query_vars[ $index ] ) ? $q->query_vars[ $index ] : explode( ',',
			                                                                                           $q->query_vars[ $index ] );
			$this->post_translations->prefetch_ids( $untranslated );
			$ulanguage_code = $this->sitepress->get_current_language();
			$translated     = array();
			foreach ( $untranslated as $element_id ) {
				$translated[] = $this->post_translations->element_id_in( $element_id, $ulanguage_code );
			}
			$q->query_vars[ $index ] = is_array( $q->query_vars[ $index ] ) ? $translated : implode( ',', $translated );
		}

		return $q;
	}

	private function adjust_q_var_pids( $q, $post_types, $index ) {
		if ( ! empty( $q->query_vars[ $index ] ) && (bool) $post_types !== false ) {

			$untranslated = $q->query_vars[ $index ];
			$this->post_translations->prefetch_ids( $untranslated );
			$current_lang = $this->sitepress->get_current_language();
			$pid          = array();
			foreach ( $q->query_vars[ $index ] as $p ) {
				$pid[] = $this->post_translations->element_id_in( $p, $current_lang, true );
			}
			$q->query_vars[ $index ] = $pid;
		}

		return $q;
	}

	/**
	 * @param int $post_id
	 * @param WP_Query $q
	 *
	 * @return false|string redirect target url if redirect is needed, false otherwise
	 */
	private function is_redirected( $post_id, $q ) {
		$request_uri = explode( '?', $_SERVER['REQUEST_URI'] );
		$redirect    = false;
		$permalink   = $this->sitepress->get_wp_api()->get_permalink( $post_id );
		if ( ! $this->is_permalink_part_of_request( $permalink, $request_uri[0] ) ) {
			if ( isset( $request_uri[1] ) ) {
				$lang = substr( $request_uri[1], strpos( $request_uri[1], '=' ) + 1 );
				$permalink = add_query_arg( array( 'lang' => $lang ), $permalink );
			}
			$redirect = $permalink;
		}

		return apply_filters( 'wpml_is_redirected', $redirect, $post_id, $q );
	}

	private function is_permalink_part_of_request( $permalink, $request_uri ) {
		$permalink_path = trailingslashit( urldecode( wpml_parse_url( $permalink, PHP_URL_PATH ) ) );
		$request_uri    = trailingslashit( urldecode( $request_uri ) );
		return 0 === strcasecmp( substr( $request_uri, 0, strlen( $permalink_path ) ), $permalink_path );
	}
}
