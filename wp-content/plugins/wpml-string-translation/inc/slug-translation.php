<?php

class WPML_Slug_Translation {

	/** @var array $post_link_cache */
	private $post_link_cache = array();

	/** @var  SitePress $sitepress */
	private $sitepress;

	/** @var wpdb $wpdb */
	private $wpdb;
	
	/** @var array $translated_slugs */
	private $translated_slugs = array();

	/**
	 * @param SitePress               $sitepress_instance
	 * @param wpdb                    $wpdb_instance
	 */
	function __construct( &$sitepress_instance, &$wpdb_instance ) {
		$this->sitepress             = $sitepress_instance;
		$this->wpdb                  = $wpdb_instance;
		$this->ignore_post_type_link = false;
	}

	function init() {
		$slug_settings = $this->sitepress->get_setting( 'posts_slug_translation' );
		if ( ! empty( $slug_settings['on'] ) ) {
			add_filter( 'option_rewrite_rules', array( $this, 'rewrite_rules_filter' ), 1, 1 ); // high priority
			add_filter( 'post_type_link', array( $this, 'post_type_link_filter' ), 1, 4 ); // high priority
			add_filter( 'query_vars', array( $this, 'add_cpt_names' ), 1, 2 );
			add_filter( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ), - 1000, 2 );
			// Slug translation API
			add_filter( 'wpml_get_translated_slug', array( $this, 'get_translated_slug' ), 1, 3 );
			add_filter( 'wpml_get_slug_translation_languages', array( $this, 'get_slug_translation_languages_filter' ), 1, 2 );
		}
		add_action( 'icl_ajx_custom_call', array( $this, 'gui_save_options' ), 10, 2 );
		// Slug translation API
		add_filter( 'wpml_slug_translation_available', array( $this, 'slug_translation_available_filter' ), 1, 1 );
		add_action( 'wpml_activate_slug_translation', array( $this, 'activate_slug_translation_action' ), 1, 2 );
		add_action( 'wpml_save_cpt_sync_settings', array( $this, 'save_sync_options' ), 1, 0 );
		add_filter( 'wpml_get_slug_translation_url', array( $this, 'get_slug_translation_url_filter' ), 1, 1 );
		
		if ( is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'maybe_migrate_string_name' ), 10, 0 );
		}
	}

	public static function get_slug_by_type( $type ) {
		global $wpdb, $sitepress;

		$rewrite_rule_filter = new WPML_Rewrite_Rule_Filter( $wpdb, $sitepress );

		return $rewrite_rule_filter->get_slug_by_type( $type );
	}

	static function rewrite_rules_filter( $value ) {
		global $wpdb, $sitepress;
		
		if ( empty( $value ) ) {
			return $value;
		} else {
			$rewrite_rule_filter = new WPML_Rewrite_Rule_Filter( $wpdb, $sitepress );
			return $rewrite_rule_filter->rewrite_rules_filter( $value );
		}
	}

	/**
	 * @param string $slug
	 * @param string $post_type
	 * @param string|bool $language
	 *
	 * @return string
	 */
	function get_translated_slug( $slug, $post_type, $language = false ) {
		if ( $post_type ) {
			$language = $language ? $language : $this->sitepress->get_current_language();

			if ( ! isset( $this->translated_slugs[ $post_type ][ $language ] ) ) {
				$slug_original = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT s.value, s.language
										FROM {$this->wpdb->prefix}icl_strings s
										WHERE s.name = %s
										    AND (s.context = %s OR s.context = %s)",
					'URL slug: ' . $post_type,
					'default',
					'WordPress') );
				if ( (bool) $slug_original === true ) {
					$this->translated_slugs[ $post_type ][ $slug_original->language ] = $slug_original->value;

					$slugs_translations = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT t.value, t.language
										FROM {$this->wpdb->prefix}icl_strings s
										JOIN {$this->wpdb->prefix}icl_string_translations t ON t.string_id = s.id
										WHERE s.name = %s
										    AND (s.context = %s OR s.context = %s)
											AND t.status = %d
											AND t.value <> ''",
						'URL slug: ' . $post_type,
						'default',
						'WordPress',
						ICL_TM_COMPLETE ) );

					foreach ( $slugs_translations as $translation ) {
						$this->translated_slugs[ $post_type ][ $translation->language ] = $translation->value;
					}
					foreach ( $this->sitepress->get_active_languages() as $lang ) {
						if ( ! isset( $this->translated_slugs[ $post_type ][ $lang['code'] ] ) ) {
							$this->translated_slugs[ $post_type ][ $lang['code'] ] = $slug;
						}
					}
				}
			}
			$slug = ! empty( $this->translated_slugs[ $post_type ][ $language ] )
				? $this->translated_slugs[ $post_type ][ $language ] : $slug;
		}

		return $slug;
	}

	function post_type_link_filter( $post_link, $post, $leavename, $sample ) {

		if ( $this->ignore_post_type_link ) {
			return $post_link;
		}
		
		if ( ! $this->sitepress->is_translated_post_type( $post->post_type )
		     || ! ( $ld = $this->sitepress->get_element_language_details( $post->ID, 'post_' . $post->post_type ) )
		) {
			return $post_link;
		}

		if ( isset( $this->post_link_cache[ $post->ID ][ $leavename . '#' . $sample ] ) ) {
			$post_link = $this->post_link_cache[ $post->ID ][ $leavename . '#' . $sample ];
		} else {
			$slug_settings = $this->sitepress->get_setting( 'posts_slug_translation' );
			$slug_settings = ! empty( $slug_settings['types'][ $post->post_type ] ) ? $slug_settings['types'][ $post->post_type ] : null;
			if ( (bool) $slug_settings === true ) {
				
				$post_type_obj = get_post_type_object ( $post->post_type );
				$slug_this = isset( $post_type_obj->rewrite[ 'slug' ] ) ? trim ( $post_type_obj->rewrite[ 'slug' ], '/' ) : false;
				$slug_real = $this->get_translated_slug( $slug_this, $post->post_type, $ld->language_code );

				if ( empty( $slug_real ) || empty( $slug_this ) || $slug_this == $slug_real ) {
					return $post_link;
				}

				global $wp_rewrite;

				if ( isset( $wp_rewrite->extra_permastructs[ $post->post_type ] ) ) {
					$struct_original = $wp_rewrite->extra_permastructs[ $post->post_type ]['struct'];

					$lslash = false !== strpos( $struct_original, '/' . $slug_this ) ? '/' : '';
					$wp_rewrite->extra_permastructs[ $post->post_type ]['struct'] = preg_replace( '@' . $lslash . $slug_this . '/@',
																								  $lslash . $slug_real . '/',
																								  $struct_original );
					$this->ignore_post_type_link = true;
					$post_link = get_post_permalink( $post->ID, $leavename, $sample );
					$this->ignore_post_type_link = false;
					$wp_rewrite->extra_permastructs[ $post->post_type ]['struct'] = $struct_original;
				} else {
					$post_link = str_replace( $slug_this . '=', $slug_real . '=', $post_link );
				}
			}
			$this->post_link_cache[ $post->ID ][ $leavename . '#' . $sample ] = $post_link;
		}

		return $post_link;
	}

	private static function get_all_slug_translations() {
		global $wpdb, $sitepress_settings;

		$cache_key   = 'WPML_Slug_Translation::get_all_slug_translations';

		$slugs_translations = wp_cache_get( $cache_key );
		
		if ( ! is_array( $slugs_translations ) ) {
			$in = '';
			$types = array();
			if ( isset( $sitepress_settings[ 'posts_slug_translation' ][ 'types' ] ) ) {
				$types = $sitepress_settings[ 'posts_slug_translation' ][ 'types' ];
				foreach ( $types as $type => $state ) {
					if ( $state ) {
						if ( $in != '' ) {
							$in .= ', ';
						}
						$in .= "'URL slug: " . $type . "'";
					}
				}
			}
			$slugs_translations = array();
			if ( $in ) {

				$in = str_replace( '%', '%%', $in );
				
				$data = $wpdb->get_results( $wpdb->prepare( "SELECT t.value, s.name
										FROM {$wpdb->prefix}icl_strings s
										JOIN {$wpdb->prefix}icl_string_translations t ON t.string_id = s.id
										WHERE s.name IN ({$in})
											AND t.status = %d
											AND t.value <> ''",
											   ICL_TM_COMPLETE ) );
				foreach($data as $row){
					foreach ( array_keys( $types ) as $type ) {
						if ( preg_match( '#\s' . $type . '$#', $row->name ) === 1 ) {
							$slugs_translations[ $row->value ] = $type;
						}
					}
				}
			}
			
			wp_cache_set( $cache_key, $slugs_translations );
		}

		return $slugs_translations;
	}

	/**
	 * Adds all translated custom post type slugs as valid query variables in addition to their original values
	 *
	 * @param array $qvars
	 *
	 * @return array
	 */
	public static function add_cpt_names( $qvars ) {

		$all_slugs_translations = array_keys( self::get_all_slug_translations() );
		$qvars                  = array_merge( $qvars, $all_slugs_translations );

		return $qvars;
	}

	/**
	 * @param WP_Query $query
	 *
	 * @return WP_Query
	 */
	function filter_pre_get_posts( $query ) {

		$all_slugs_translations = self::get_all_slug_translations();

		foreach ( $query->query as $slug => $post_name ) {
			if ( isset( $all_slugs_translations[ $slug ] ) ) {
				$new_slug = isset( $all_slugs_translations[ $slug ] ) ? $all_slugs_translations[ $slug ] : $slug;
				unset( $query->query[ $slug ] );
				$query->query[ $new_slug ] = $post_name;
				$query->query['name']      = $post_name;
				$query->query['post_type'] = $new_slug;
				unset( $query->query_vars[ $slug ] );
				$query->query_vars[ $new_slug ] = $post_name;
				$query->query_vars['name']      = $post_name;
				$query->query_vars['post_type'] = $new_slug;

			}
		}

		return $query;
	}

	static function gui_save_options( $action, $data ) {

		switch ( $action ) {
			case 'icl_slug_translation':
				global $sitepress;
				$iclsettings[ 'posts_slug_translation' ][ 'on' ] = intval( ! empty( $_POST[ 'icl_slug_translation_on' ] ) );
				$sitepress->save_settings( $iclsettings );
				echo '1|' . $iclsettings[ 'posts_slug_translation' ][ 'on' ];
				break;
		}

	}
	
	static function get_sql_to_get_string_id( $post_type ) {
		global $wpdb;
		
		return $wpdb->prepare( "SELECT id
                                FROM {$wpdb->prefix}icl_strings
                                WHERE name = %s",
                                'URL slug: ' . $post_type
                             );
	}
	
	static function get_translations ( $slug ) {
		global $wpdb;
		
        $string_id_prepared = self::get_sql_to_get_string_id( $slug );
        $string_id = $wpdb->get_var( $string_id_prepared );
        $slug_translations = icl_get_string_translations_by_id( $string_id );
		
		return array( $string_id, $slug_translations );
	}

	static function save_sync_options() {
		global $sitepress, $wpdb;

		$slug_settings = $sitepress->get_setting( 'posts_slug_translation' );
		if ( isset( $slug_settings['on'] ) && $slug_settings['on'] && ! empty( $_POST['translate_slugs'] ) ) {
			foreach ( $_POST['translate_slugs'] as $type => $data ) {
				$slug_settings['types'][ $type ] = isset( $data['on'] ) ? intval( ! empty( $data['on'] ) ) : false;
				if ( empty( $slug_settings['types'][ $type ] ) ) {
					continue;
				}
				$post_type_obj = get_post_type_object( $type );
				$slug          = trim( $post_type_obj->rewrite['slug'], '/' );
				$string_id     = $wpdb->get_var( self::get_sql_to_get_string_id( $type ) );
				$string_id     = empty( $string_id ) ? self::register_string_for_slug( $type, $slug ) : $string_id;
				if ( $string_id ) {
					if ( ! isset( $data[ 'original' ] ) ) {
						$data[ 'original' ]  = $sitepress->get_default_language();
					}
					$string = new WPML_ST_String( $string_id, $wpdb );
					if ( $string->get_language() != $data[ 'original' ] ) {
						$string->set_language( $data[ 'original' ] );
					}
					if ( isset( $data[ 'langs' ] ) ) {
						
						foreach ( $sitepress->get_active_languages() as $lang ) {
							if ( $lang['code'] != $data[ 'original' ] ) {
								$data['langs'][ $lang['code'] ] = join( '/',
																		array_map( array( 'WPML_Slug_Translation', 'sanitize' ),
																				   explode( '/',
																							$data['langs'][ $lang['code'] ] ) ) );
								$data['langs'][ $lang['code'] ] = urldecode( $data['langs'][ $lang['code'] ] );
								icl_add_string_translation( $string_id,
															$lang['code'],
															$data['langs'][ $lang['code'] ],
															ICL_TM_COMPLETE );
							}
						}
					}
					icl_update_string_status( $string_id );
				}
			}
		}

		$sitepress->set_setting( 'posts_slug_translation', $slug_settings, true );
	}
	
	static function sanitize( $slug ) {
		
		// we need to preserve the %
		$slug = str_replace( '%', '%45', $slug );
		$slug = sanitize_title_with_dashes( $slug );
		$slug = str_replace( '%45', '%', $slug );
		
		return $slug;
	}

	static function slug_translation_available_filter( $value ) {
		return true;
	}
	
	static function activate_slug_translation_action( $post_type, $slug = null ) {
		global $wpdb, $sitepress;

		if( is_null( $slug ) ){
			$slug = $post_type;
		}

		$string_id = $wpdb->get_var( self::get_sql_to_get_string_id( $slug ) );

		if( ! $string_id ){
			self::register_string_for_slug( $post_type, $slug );
		}

		$posts_slug_translation = $sitepress->get_setting( 'posts_slug_translation', array() );
		if ( empty( $posts_slug_translation['on'] ) || empty( $posts_slug_translation['types'][ $post_type ] ) ) {
			$posts_slug_translation['on']                  = 1;
			$posts_slug_translation['types'][ $post_type ] = 1;
			$sitepress->set_setting( 'posts_slug_translation', $posts_slug_translation, true );
		}
	}
	
	static function register_string_for_slug( $post_type, $slug ) {
		return icl_register_string( 'WordPress', 'URL slug: ' . $post_type, $slug );
	}
	
	function get_slug_translation_languages_filter( $languages, $post_type ) {
		global $wpdb;
		
        $slug_translation_languages = $wpdb->get_col( $wpdb->prepare(
				 "SELECT tr.language
				  FROM {$wpdb->prefix}icl_strings AS s
				  LEFT JOIN {$wpdb->prefix}icl_string_translations AS tr ON s.id = tr.string_id
				  WHERE s.context = 'WordPress' AND
						s.name = %s AND
						tr.status = %s",

				 'Url slug: ' . $post_type,
				 ICL_TM_COMPLETE
		) );

		return $slug_translation_languages;
	}
	
	static function get_slug_translation_url_filter ( $url ) {
		if ( defined( 'WPML_TM_VERSION' ) ) {
			return admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup#ml-content-setup-sec-7' );
		} else {
			return admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translation-options.php#ml-content-setup-sec-7' );
		}
	}
	
	public function maybe_migrate_string_name() {
		global $wpdb;

		$slug_settings = $this->sitepress->get_setting( 'posts_slug_translation' );
		
		if ( ! isset( $slug_settings[ 'string_name_migrated' ] ) ) {

			$queryable_post_types = get_post_types( array( 'publicly_queryable' => true ) );
	
			foreach ( $queryable_post_types as $type ) {
				$post_type_obj = get_post_type_object( $type );
				$slug          = trim( $post_type_obj->rewrite['slug'], '/' );
				
				if ( $slug ) {
					// First check if we should migrate from the old format URL slug: slug
					$string_id = $wpdb->get_var( $wpdb->prepare( "SELECT id
											FROM {$wpdb->prefix}icl_strings
											WHERE name = %s AND value = %s",
											'URL slug: ' . $slug,
											$slug
										 ) );
					if ( $string_id ) {
						// migrate it to URL slug: post_type
						
						$st_update[ 'name' ] = 'URL slug: ' . $type;
						$wpdb->update( $wpdb->prefix . 'icl_strings', $st_update, array( 'id' => $string_id ) );
					}
				}
			}
			
			$slug_settings[ 'string_name_migrated' ] = true;
			$this->sitepress->set_setting( 'posts_slug_translation', $slug_settings, true );
		}
	}
}