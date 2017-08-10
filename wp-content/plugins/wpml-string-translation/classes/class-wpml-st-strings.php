<?php

class WPML_ST_Strings {

	const EMPTY_CONTEXT_LABEL = 'empty-context-domain';

	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var WP_Query
	 */
	private $wp_query;
	/**
	 * @var WPDB
	 */
	private $wpdb;

	public function __construct( &$sitepress, &$wpdb, &$wp_query ) {
		$this->wpdb               = &$wpdb;
		$this->sitepress          = &$sitepress;
		$this->wp_query           = &$wp_query;
		$this->sitepress_settings = $this->sitepress->get_settings();
	}

	public function get_string_translations() {
		$string_translations = array();

		$current_user                       = $this->sitepress->get_current_user();
		$current_user_can_translate_strings = $this->sitepress->get_wp_helper()->current_user_can_translate_strings();
		$user_lang_pairs                    = $this->sitepress->get_wp_helper()->get_user_language_pairs( $current_user );

		$extra_cond = "";

		$active_languages = null;
		$language_code_aliases = null;
		if ( $current_user_can_translate_strings ) {
			$active_languages = $this->sitepress->get_active_languages();
			foreach ( $active_languages as $l ) {
				$language_code_aliases[] = esc_sql( str_replace( '-', '', $l['code'] ) );
			}

			foreach ( $user_lang_pairs as $source_lang_code => $pair ) {
				if ( ! isset( $active_languages[ $source_lang_code ] ) ) {
					$active_languages[ $source_lang_code ] = $this->sitepress->get_language_details( $source_lang_code );
					$language_code_aliases[] = esc_sql( str_replace( '-', '', $source_lang_code ) );
				}
				foreach ( array_keys( $pair ) as $target_lang_code ) {
					if ( ! isset( $active_languages[ $target_lang_code ] ) ) {
						$active_languages[ $target_lang_code ] = $this->sitepress->get_language_details( $target_lang_code );
						$language_code_aliases[] = esc_sql( str_replace( '-', '', $target_lang_code ) );
					}
				}
			}
		}


		if ( $current_user_can_translate_strings && isset( $_GET[ 'status' ] ) && preg_match( "#" . ICL_TM_WAITING_FOR_TRANSLATOR . "-(.+)#", $_GET[ 'status' ], $matches ) ) {
			$status_filter       = ICL_TM_WAITING_FOR_TRANSLATOR;
			$status_filter_lang = $matches[1];
			$language_code_alias = str_replace( '-', '', $status_filter_lang );

			if ( in_array( $language_code_alias, $language_code_aliases, true ) ) {
				$extra_cond .= " AND str_{$language_code_alias}.language = '{$status_filter_lang}' ";
			} else {
				$status_filter_lang = null;
			}
		} else {
			$status_filter = isset( $_GET['status'] ) ? (int) $_GET['status']  : false;
		}

		$search_filter = isset( $_GET[ 'search' ] ) ? $_GET[ 'search' ] : false;
		$exact_match   = isset( $_GET[ 'em' ] ) ? $_GET[ 'em' ] == 1 : false;

		if ( $status_filter !== false ) {
			if ( $status_filter == ICL_TM_COMPLETE ) {
				$extra_cond .= " AND s.status = " . ICL_TM_COMPLETE;
			} elseif ( $status_filter != ICL_TM_WAITING_FOR_TRANSLATOR ) {
				$extra_cond .= " AND status IN (" . ICL_STRING_TRANSLATION_PARTIAL . "," . ICL_TM_NEEDS_UPDATE . "," . ICL_TM_NOT_TRANSLATED . "," . ICL_TM_WAITING_FOR_TRANSLATOR . ")";
			}
		}
		if ( $search_filter != false ) {
			if ( $exact_match ) {
				$extra_cond .= " AND s.value = '" . esc_sql( $search_filter ) . "' ";
			} else {
				$extra_cond .= " AND s.value LIKE '%" . esc_sql( $search_filter ) . "%' ";
			}
		}

		if( array_key_exists( 'context', $_GET ) ) {
			$context = filter_var( $_GET['context'], FILTER_SANITIZE_STRING );

			if ( self::EMPTY_CONTEXT_LABEL === $context ) {
				$context = '';
			}

		}

		$extra_cond .= '';
		if ( isset( $context ) ) {
			$extra_cond .= " AND s.context = '" . esc_sql( $context ) . "'";
		}

		if ( isset( $_GET['show_results'] ) && $_GET['show_results'] == 'all' ) {
			$limit  = 9999;
			$offset = 0;
		} else {
			$limit         = $this->get_strings_per_page();
			$_GET['paged'] = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
			$offset        = ( $_GET['paged'] - 1 ) * $limit;
		}

		/* TRANSLATOR - START */
		if ( $current_user_can_translate_strings ) {

			$_joins = $_sels = $_where = array();

			foreach ( $language_code_aliases as $language_code_alias ) {
				$_sels[]             = "str_{$language_code_alias}.id AS id_{$language_code_alias},
	                             str_{$language_code_alias}.status AS status_{$language_code_alias},
	                             str_{$language_code_alias}.value AS value_{$language_code_alias},
	                             str_{$language_code_alias}.translator_id AS translator_{$language_code_alias},
	                             str_{$language_code_alias}.translation_date AS date_{$language_code_alias}
	                             ";
				$_joins[]            = $this->wpdb->prepare( "LEFT JOIN {$this->wpdb->prefix}icl_string_translations str_{$language_code_alias}
	                                                ON str_{$language_code_alias}.string_id = s.id AND str_{$language_code_alias}.language = %s ", $l['code'] );

				if ( empty( $status_filter_lang ) ) {
					if ( $status_filter == ICL_TM_COMPLETE ) {
						$_where[] .= " AND str_{$language_code_alias}.status = " . ICL_TM_COMPLETE;
					} else {
						if ( empty( $_lwhere ) ) {
							$_lwheres = array();
							$_lwhere  = ' AND (';
							foreach ( $active_languages as $l2 ) {
								$l2code_alias = esc_sql( str_replace( '-', '', $l2['code'] ) );
								$_lwheres[]   = $this->wpdb->prepare( " str_{$l2code_alias}.status = %d
	                                                          OR str_{$l2code_alias}.translator_id = %d ", ICL_TM_WAITING_FOR_TRANSLATOR, $current_user->ID );
							}
							$_lwhere .= join( ' OR ', $_lwheres ) . ')';
							$_where[] = $_lwhere;
						}
					}
				}
			}

			$sql_query = empty( $status_filter_lang )
				? " WHERE s.language IN ( " . wpml_prepare_in( array_keys( $user_lang_pairs ) ) . " ) " . join( ' ', $_where )
				: $this->wpdb->prepare( " WHERE str_{$status_filter_lang}.status = %d
											AND (str_{$status_filter_lang}.translator_id IS NULL
													OR str_{$status_filter_lang}.translator_id = %d)",
					array( ICL_TM_WAITING_FOR_TRANSLATOR, $current_user->ID ) );
			$res       = $this->get_results( $sql_query, $extra_cond, $offset, $limit, $_joins, $_sels );
			if ( $res ) {
				$string_translations = empty( $status_filter_lang )
					? $this->rows_from_unfiltered( $res, $active_languages, $string_translations )
					: $this->rows_from_lang_status_filtered( $res, $active_languages, $string_translations );
			}
			/* TRANSLATOR - END */
		} else {
			if ( $status_filter != ICL_TM_WAITING_FOR_TRANSLATOR ) {
				$sql_query = " WHERE 1 ";
			} else {
				$sql_query = " JOIN {$this->wpdb->prefix}icl_string_translations str ON str.string_id = s.id
	                           WHERE str.status = " . ICL_TM_WAITING_FOR_TRANSLATOR;
			}
			$res = $this->get_results( $sql_query, $extra_cond, $offset, $limit );

			if ( $res ) {
				$extra_cond = '';
				if ( isset( $_GET[ 'translation_language' ] ) ) {
					$extra_cond .= " AND language='" . esc_sql( $_GET[ 'translation_language' ] ) . "'";
				}

				foreach ( $res as $row ) {
					$string_translations[ $row[ 'string_id' ] ] = $row;
					$tr                                         = $this->wpdb->get_results( $this->wpdb->prepare( "
	                    SELECT id, language, status, value, translator_id, translation_date  
	                    FROM {$this->wpdb->prefix}icl_string_translations 
	                    WHERE string_id=%d {$extra_cond}
	                ", $row[ 'string_id' ] ), ARRAY_A );
					if ( $tr ) {
						foreach ( $tr as $t ) {
							$string_translations[ $row[ 'string_id' ] ][ 'translations' ][ $t[ 'language' ] ] = $t;
						}
					}
				}
			}
		}

		return $string_translations;
	}

	public function get_per_domain_counts( $status ) {
		$extra_cond = '';
		$joins      = '';

		$current_user = $this->sitepress->get_current_user();

		if ( $status !== false ) {
			if ( $status == ICL_TM_COMPLETE ) {
				$extra_cond .= " AND s.status = " . ICL_TM_COMPLETE;
			} else {
				$extra_cond .= " AND s.status IN (" . ICL_STRING_TRANSLATION_PARTIAL . "," . ICL_TM_NEEDS_UPDATE . "," . ICL_TM_NOT_TRANSLATED . ")";
			}
		}

		if ( icl_st_is_translator() ) {
			$user_langs = get_user_meta( $current_user->ID, $this->wpdb->prefix . 'language_pairs', true );

			foreach ( $user_langs as $source_lang => $lang_pair ) {
				$source_lang = esc_sql( $source_lang );
				foreach ( $lang_pair as $lang => $one ) {
					$lcode_alias = esc_sql( str_replace( '-', '', $source_lang . $lang ) );
					$joins[]     = $this->wpdb->prepare( " JOIN {$this->wpdb->prefix}icl_string_translations {$lcode_alias}_str
															ON {$lcode_alias}_str.string_id = s.id AND {$lcode_alias}_str.language= %s AND s.language = %s
			                                                  AND ( {$lcode_alias}_str.status = " . ICL_TM_WAITING_FOR_TRANSLATOR .
					                                     " OR {$lcode_alias}_str.translator_id = %d ) ", $lcode_alias, $source_lang, $current_user->ID );
				}
			}
			$sql     = "
                SELECT s.context, COUNT(s.context) AS c FROM {$this->wpdb->prefix}icl_strings s
                " . join( "\n", $joins ) . "
                WHERE 1 {$extra_cond}  AND TRIM(s.value) != ''
                GROUP BY context
                ORDER BY context ASC
            ";
			$results = $this->wpdb->get_results( $sql );
		} else {
			$results = $this->wpdb->get_results( "
            SELECT context, COUNT(context) AS c
            FROM {$this->wpdb->prefix}icl_strings s
            WHERE 1 {$extra_cond} AND TRIM(s.value) != ''
            GROUP BY context
            ORDER BY context ASC" );
		}

		return $results;
	}

	/**
	 * @param WP_User $current_user
	 *
	 * @return array
	 */
	public function get_pending_translation_stats( $current_user ) {
		$user_lang_pairs = get_user_meta( $current_user->ID, $this->wpdb->prefix . 'language_pairs', true );
		$stats           = array();
		if ( ! empty( $user_lang_pairs ) ) {
			$conds        = array();
			$target_langs = array();
			foreach ( $user_lang_pairs as $source_lang => $pair ) {
				$target_lang_codes = array_keys( $pair );
				$conds[]           = $this->wpdb->prepare( " ( s.language = %s AND st.language IN (" . wpml_prepare_in(
						$target_lang_codes
					) . ") ) ", $source_lang );
				$target_langs      = array_unique( array_merge( $target_langs, $target_lang_codes ) );
			}

			$results = $this->wpdb->get_results( $this->wpdb->prepare( "
            SELECT COUNT(s.id) AS c, st.language
            FROM {$this->wpdb->prefix}icl_string_translations st
            JOIN {$this->wpdb->prefix}icl_strings s
              ON s.id = st.string_id
            WHERE st.status=%d AND ( " . join( " OR ", $conds ) . " )
                    AND (translator_id IS NULL OR translator_id = %d)
            GROUP BY st.language
            ORDER BY c DESC
            ",
				ICL_TM_WAITING_FOR_TRANSLATOR, $current_user->ID
			) );
			foreach ( $results as $r ) {
				$_stats[ $r->language ] = $r->c;
			}
			foreach ( $target_langs as $lang ) {
				$stats[ $lang ] = isset( $_stats[ $lang ] ) ? $_stats[ $lang ] : 0;
			}
		}

		return $stats;
	}

	private function get_strings_per_page() {
		$st_settings = $this->sitepress->get_setting( 'st' );

		return isset( $st_settings['strings_per_page'] ) ? $st_settings['strings_per_page'] : WPML_ST_DEFAULT_STRINGS_PER_PAGE;
	}

	private function get_results( $where_snippet, $extra_cond, $offset, $limit, $joins = array(), $selects = array() ) {
		$res = $this->wpdb->get_results( $this->build_sql_start( $selects, $joins ) . $where_snippet . " {$extra_cond} " . $this->filter_empty_order_snippet( $offset, $limit ), ARRAY_A );
		$this->set_pagination_counts( $limit );

		return $res;
	}

	private function filter_empty_order_snippet( $offset, $limit ) {

		return " AND TRIM(s.value) != '' ORDER BY string_id DESC LIMIT {$offset},{$limit}";
	}

	private function set_pagination_counts( $limit ) {
		if ( ! is_null( $this->wp_query ) ) {
			$this->wp_query->found_posts                  = $this->wpdb->get_var( "SELECT FOUND_ROWS()" );
			$this->wp_query->query_vars['posts_per_page'] = $limit;
			$this->wp_query->max_num_pages                = ceil( $this->wp_query->found_posts / $limit );
		}
	}

	private function rows_from_unfiltered( $res, $active_languages, $string_translations ) {
		foreach ( $res as $row ) {
			$_translations = array();
			$_statuses     = array();
			foreach ( $active_languages as $l ) {
				list( $language_code_alias, $_translations ) = $this->parse_row_translations( $row, $l, $_translations );
				$_statuses[ $l['code'] ] = intval( $row[ 'status_' . $language_code_alias ] );
			}
			$_statuses = array_unique( array_values( $_statuses ) );
			if ( $_statuses == array( ICL_TM_COMPLETE, ICL_TM_NOT_TRANSLATED ) ) {
				$_status = ICL_STRING_TRANSLATION_PARTIAL;
			} elseif ( $_statuses == array( ICL_TM_COMPLETE ) ) {
				$_status = ICL_TM_COMPLETE;
			} elseif ( in_array( ICL_TM_WAITING_FOR_TRANSLATOR, $_statuses ) || in_array( ICL_TM_NEEDS_UPDATE, $_statuses ) ) {
				$_status = ICL_TM_WAITING_FOR_TRANSLATOR;
			} else {
				$_status = ICL_TM_NOT_TRANSLATED;
			}
			$row['status']       = $_status;
			$string_translations = $this->add_row_to_result( $row, $_translations, $string_translations );
		}

		return $string_translations;
	}

	private function rows_from_lang_status_filtered( $res, $active_languages, $string_translations ) {
		foreach ( $res as $row ) {
			$_translations = array();
			foreach ( $active_languages as $l ) {
				list( , $_translations ) = $this->parse_row_translations( $row, $l, $_translations );
			}
			$row['status']       = ICL_TM_WAITING_FOR_TRANSLATOR;
			$string_translations = $this->add_row_to_result( $row, $_translations, $string_translations );
		}

		return $string_translations;
	}

	private function parse_row_translations( $row, $l, $_translations ) {
		$language_code_alias = esc_sql( str_replace( '-', '', $l['code'] ) );
		if ( isset( $row[ 'id_' . $language_code_alias ] ) ) {
			$_translations[ $l['code'] ] = array(
				'id'               => $row[ 'id_' . $language_code_alias ],
				'status'           => $row[ 'status_' . $language_code_alias ],
				'language'         => $l['code'],
				'value'            => $row[ 'value_' . $language_code_alias ],
				'translator_id'    => $row[ 'translator_' . $language_code_alias ],
				'translation_date' => $row[ 'date_' . $language_code_alias ]
			);
		}

		return array( $language_code_alias, $_translations );
	}

	/**
	 * @param array $row
	 * @param array $translations
	 * @param array $result
	 *
	 * @return array
	 */
	private function add_row_to_result( $row, $translations, $result ) {
		$result[ $row['string_id'] ] = array(
			'string_id'       => $row['string_id'],
			'string_language' => $row['string_language'],
			'context'         => $row['context'],
			'gettext_context' => $row['gettext_context'],
			'name'            => $row['name'],
			'value'           => $row['value'],
			'status'          => $row['status'],
			'translations'    => $translations
		);

		return $result;
	}

	private function build_sql_start( $selects = array(), $joins = array() ) {
		array_unshift( $selects, "SQL_CALC_FOUND_ROWS DISTINCT(s.id) AS string_id, s.language AS string_language, s.string_package_id, s.context, s.gettext_context, s.name, s.value, s.status AS status" );

		return "SELECT " . join( ', ', $selects ) . " FROM {$this->wpdb->prefix}icl_strings s " . join( "\n", $joins ) . " ";
	}
}