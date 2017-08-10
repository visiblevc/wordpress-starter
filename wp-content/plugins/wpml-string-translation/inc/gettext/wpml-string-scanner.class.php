<?php

class WPML_String_Scanner {
	/**
	 * @param string|NULL $type 'plugin' or 'theme'
	 */
	protected $current_type;
	protected $current_path;

	private $domains;
	private $registered_strings;
	private $lang_codes;
	private $currently_scanning;
	private $domains_found;
	private $default_domain;

	/**
	 * WP_Filesystem object.
	 * @var oject
	 */
	private $wp_filesystem;

	/** @var WPML_File $wpml_file */
	private $wpml_file;

	/**
	 * @var array
	 */
	private $scan_stats;
	private $scanned_files;

	/**
	 * @var WPML_File_Name_Converter
	 */
	private $file_name_converter;

	/**
	 * @var WPML_ST_DB_Mappers_String_Positions
	 */
	private $string_positions_mapper;

	/**
	 * @var WPML_ST_DB_Mappers_Strings
	 */
	private $strings_mapper;

	public function __construct( $wp_filesystem ) {
		$this->domains            = array();
		$this->registered_strings = array();
		$this->lang_codes         = array();
		$this->domains_found      = array();
		$this->scan_stats         = array();
		$this->scanned_files      = array();

		$this->default_domain     = 'default';
		$this->wp_filesystem      = $wp_filesystem;
	}

	private function remove_trailing_new_line( $text ) {
		if ( substr( $text, - 1 ) == PHP_EOL || substr( $text, - 1 ) == "\n" ) {
			$text = substr( $text, 0, - 1 );
		}

		return $text;
	}

	protected function scan_starting( $scanning ) {
		$this->currently_scanning                         = $scanning;
		$this->domains_found[ $this->currently_scanning ] = array();
		$this->default_domain                             = 'default';
	}

	protected function scan_response() {
		$scan_stats = $this->scan_stats ? implode( PHP_EOL, $this->scan_stats ) : '';
		echo '1|' . $scan_stats;
		exit;
	}

	protected final function init_text_domain( $text_domain ) {
		$string_settings = apply_filters( 'wpml_get_setting', false, 'st' );

		$use_header_text_domain = isset( $string_settings[ 'use_header_text_domains_when_missing' ] ) && $string_settings[ 'use_header_text_domains_when_missing' ];
		
		$this->default_domain = 'default';
	
		if ( $use_header_text_domain && $text_domain ) {
			$this->default_domain = $text_domain;
		}
	}

	protected function get_domains_found() {
		return $this->domains_found[ $this->currently_scanning ];
	}
	
	protected function get_default_domain() {
		return $this->default_domain;
	}

	protected function add_translations( $contexts, $context_prefix ) {

		if ( $contexts ) {
			$path     = $this->current_path;
			$mo_files = $this->get_mo_files( $path );
			$path     = preg_replace( '#\/plugins\/(.+)#', '/languages/plugins/', $path );
			$mo_files = array_merge( $mo_files, $this->get_mo_files( $path ) );
			foreach ( (array) $mo_files as $m ) {
				$i = preg_match( '#[-]?([a-z_]+)\.mo$#i', $m, $matches );
				if ( $i && $lang = $this->get_lang_code( $matches[ 1 ] ) ) {
					$tr_pairs = $this->load_translations_from_mo( $m );
					foreach ( $tr_pairs as $translation ) {
						$original = $translation[ 'orig' ];
						foreach ( $contexts as $tld ) {

							$this->fix_existing_string_with_wrong_context( $original, $context_prefix . $tld, $translation[ 'context' ] );
							if ( $this->add_translation( $original, $translation[ 'trans' ], $translation[ 'context' ], $lang, $context_prefix . $tld ) ) {
								break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Get list of .mo files under directory.
	 *
	 * @param  string $path
	 * @return array
	 */
	public function get_mo_files( $path ) {

		static $mo_files = array();

		if ( function_exists( 'realpath' ) ) {
			$path = realpath( $path );
		}

		if ( $this->wp_filesystem->is_dir( $path ) && $this->wp_filesystem->is_readable( $path ) ) {
			$files = $this->extract_files( $path, $this->wp_filesystem );
			foreach ( $files as $file ) {
				if ( preg_match( '#\.mo$#', $file ) ) {
					$mo_files[] = $file;
				}
			}
		}

		return $mo_files;
	}

	/**
	 * Get list of files under directory.
	 * @param  string $path       Directory to parse.
	 * @param  object $filesystem WP_Filesystem object
	 * @return array
	 */
	private function extract_files( $path, $filesystem ) {
		$path = $this->add_dir_separator( $path );
		$files = array();
		$list = $filesystem->dirlist( $path );
		foreach ( $list as $single_file ) {
			if ( 'f' === $single_file['type'] ) {
				$files[] = $path . $single_file['name'];
			} else {
				$files = array_merge( $files, $this->extract_files( $path . $single_file['name'], $filesystem ) );
			}
		}
		return $files;
	}

	/**
	 * Make sure that the last character is second argument.
	 * @param  string $path
	 * @param  string $separator
	 * @return string
	 */
	private function add_dir_separator( $path, $separator = DIRECTORY_SEPARATOR ) {
		if ( strlen( $path ) > 0 ) {
			if ( substr( $path, -1 ) !== $separator ) {
				return $path . $separator;
			} else {
				return $path;
			}
		} else {
			return $path;
		}
	}
	
	private function load_translations_from_mo( $mo_file ) {
		$translations = array();
		$mo           = new MO();
		$pomo_reader  = new POMO_CachedFileReader( $mo_file );
		
		$mo->import_from_reader( $pomo_reader );
		
		foreach ($mo->entries as $str => $v ){
			$str = str_replace( "\n",'\n', $v->singular );
			$translations[ ] = array( 'orig' => $str, 'trans' => $v->translations[0], 'context' => $v->context );
			if( $v->is_plural ) {
				$str = str_replace( "\n",'\n', $v->plural );
				$translation = ! empty( $v->translations[1] ) ? $v->translations[1] : $v->translations[0];
				$translations[ ] = array( 'orig' => $str, 'trans' => $translation, 'context' => $v->context );
			}
		}
		return $translations;
	}
	
	private function fix_existing_string_with_wrong_context( $original_value, $new_string_context, $gettext_context ) {
		if ( ! isset( $this->current_type ) || ! isset( $this->current_path ) ) {
			return;
		}

        $old_context = $this->get_old_context( );

		$new_context_string_id = $this->get_string_id( $original_value, $new_string_context, $gettext_context );

		if ( ! $new_context_string_id ) {
			$old_context_string_id = $this->get_string_id( $original_value, $old_context, $gettext_context );
			if ( $old_context_string_id ) {
				$this->fix_string_context( $old_context_string_id, $new_string_context );
				unset( $this->registered_strings[ $old_context ] );
				unset( $this->registered_strings[ $new_string_context ] );
			}
		}
	}
    
    private function get_old_context( ) {
		
        $plugin_or_theme_path = $this->current_path;

		$name    = basename( $plugin_or_theme_path );
		$old_context = $this->current_type . ' ' . $name;
        
        return $old_context;
        
    }

	private function get_lang_code( $lang_locale ) {
		global $wpdb;

		if ( ! isset( $this->lang_codes[ $lang_locale ] ) ) {
			$this->lang_codes[ $lang_locale ] = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE locale=%s", $lang_locale ) );
		}

		return $this->lang_codes[ $lang_locale ];
	}

	private function add_translation( $original, $translation, $gettext_context, $lang, $context ) {
		global $wpdb;

		$string_id = $this->get_string_id( $original, $context, $gettext_context );
		if ( $string_id ) {
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id
													FROM {$wpdb->prefix}icl_string_translations
													WHERE string_id=%d AND language=%s AND value<>%s",
													$string_id,
													$lang,
													$original ) ) ) {
				icl_add_string_translation( $string_id, $lang, $translation, ICL_TM_COMPLETE );
			}

			return true;
		}

		return false;
	}

	private function get_string_id( $original, $domain, $gettext_context ) {

		$this->warm_cache( $domain );

		$string_context_name = md5( $gettext_context . md5( $original ) );
		$string_id  = isset( $this->registered_strings[ $domain ] [ 'context-name' ] [ $string_context_name ] ) ? $this->registered_strings[ $domain ] [ 'context-name' ] [ $string_context_name ] : null;

		return $string_id;
	}

	private function fix_string_context( $string_id, $new_string_context ) {
		global $wpdb;

		$string = $wpdb->get_row( $wpdb->prepare( "SELECT gettext_context, name FROM {$wpdb->prefix}icl_strings WHERE id=%d", $string_id ) );

		$domain_name_context_md5 = md5( $new_string_context . $string->name . $string->gettext_context );
		
		$wpdb->update( $wpdb->prefix . 'icl_strings',
						array(
							  'context'                 => $new_string_context,
							  'domain_name_context_md5' => $domain_name_context_md5
							  ),
						array( 'id' => $string_id ), array( '%s', '%s') , '%d' );
		


	}

	public function store_results( $string, $domain, $_gettext_context, $file, $line ) {

		global $wpdb;

		$domain = $domain ? $domain : 'WordPress';

		if ( ! isset( $this->domains_found[ $this->currently_scanning ] [ $domain ] ) ) {
			$this->domains_found[ $this->currently_scanning ] [ $domain ] = 1;
		} else {
			$this->domains_found[ $this->currently_scanning ] [ $domain ] += 1;
		}

		if ( ! in_array( $domain, $this->domains ) ) {
			$this->domains[ ] = $domain;

			// clear existing entries (both source and page type)
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id IN
                (SELECT id FROM {$wpdb->prefix}icl_strings WHERE context = %s)", $domain ) );
		}

        $string = str_replace( '\n', "\n", $string );
		$string = str_replace( array( '\"', "\\'" ), array( '"', "'" ), $string );
		//replace extra backslashes added by _potx_process_file
		$string = str_replace( array( '\\\\' ), array( '\\' ), $string );

		global $__icl_registered_strings;

		if ( ! isset( $__icl_registered_strings ) ) {
			$__icl_registered_strings = array();
		}

		if ( ! isset( $__icl_registered_strings[ $domain . '||' . $string . '||' . $_gettext_context ] ) ) {

			$name = md5( $string );
			$this->fix_existing_string_with_wrong_context( $string, $domain, $_gettext_context );
			$this->register_string( $domain, $_gettext_context, $name, $string );

			$__icl_registered_strings[ $domain . '||' . $string . '||' . $_gettext_context ] = true;
		}

		// store position in source
		$this->track_string( $string,
							 array( 'domain' => $domain,
								    'context' => $_gettext_context
								  ),
							ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE,
							$file,
							$line );
	}

	private function register_string( $domain, $context, $name, $string ) {

		$this->warm_cache( $domain );

		if ( ! isset( $this->registered_strings[ $domain ] [ 'context-name' ] [ md5( $context . $name ) ] ) ) {
			
			if ( $context ) {
				$string_context = array( 'domain'  => $domain,
										 'context' => $context
										);
			} else {
				$string_context = $domain;
			}
			$string_id = icl_register_string( $string_context, $name, $string );
			
			$this->registered_strings[ $domain ] [ 'context-name' ] [ md5( $context . $name ) ] = $string_id;
		}
	}

	private function warm_cache( $domain ) {
		if ( ! isset( $this->registered_strings[ $domain ] ) ) {

			$this->registered_strings[ $domain ] = array(
				'context-name' => array(),
				'value'      => array(),
			);

			$results = $this->get_strings_mapper()->get_all_by_context( $domain );
			foreach ( $results as $result ) {
				$this->registered_strings[ $domain ] ['context-name'] [ md5( $result['gettext_context'] . $result['name'] ) ] = $result['id'];
			}
		}
	}

	public function track_string( $text, $context, $kind = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE, $file = null, $line = null ) {
		list ( $domain, $gettext_context ) = wpml_st_extract_context_parameters( $context );
		
		// get string id
		$string_id = $this->get_string_id( $text, $domain, $gettext_context );
		if ( $string_id ) {
			$str_pos_mapper = $this->get_string_positions_mapper();
			$string_records_count = $str_pos_mapper->get_count_of_positions_by_string_and_kind( $string_id, $kind );

			if ( ICL_STRING_TRANSLATION_STRING_TRACKING_THRESHOLD > $string_records_count ) {
				if ( $kind == ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE ) {
					// get page url
					$https    = isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] == 'on' ? 's' : '';
					$position = 'http' . $https . '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
				} else {
					$file = $this->get_file_name_converter()->transform_realpath_to_reference( $file );
					$position = $file . '::' . $line;
				}

				if ( ! $str_pos_mapper->is_string_tracked( $string_id, $position, $kind ) && ! $this->is_string_preview() ) {
					$str_pos_mapper->insert( $string_id, $position, $kind );
				}
			}
		}
	}

	protected function add_stat( $text, $top = false ) {
		$text = $this->remove_trailing_new_line( $text );
		if ( $top ) {
			array_unshift( $this->scan_stats, $text );
		} else {
			$this->scan_stats[ ] = $text;
		}
	}

	protected function get_scan_stats() {
		return $this->scan_stats;
	}

	protected function add_scanned_file( $file ) {
		$this->scanned_files[ ] = $file;
	}

	protected function get_scanned_files() {
		return $this->scanned_files;
	}
    
    protected function cleanup_wrong_contexts( ) {
        global $wpdb;
		
        $old_context = $this->get_old_context( );

	    /** @var array $results */
		$results = $wpdb->get_results( $wpdb->prepare( "
	        SELECT id, name, value
	        FROM {$wpdb->prefix}icl_strings
	        WHERE context = %s",
			$old_context
			) );
		
		foreach( $results as $string ) {
			// See if the string has no translations

			/** @var array $old_translations */
			$old_translations = $wpdb->get_results( $wpdb->prepare( "
				SELECT id, language, status, value
				FROM {$wpdb->prefix}icl_string_translations
				WHERE string_id = %d",
				$string->id
				) );

			if ( ! $old_translations ) {
				// We don't have any translations so we can delete the string.
				
				$wpdb->delete( $wpdb->prefix . 'icl_strings', array( 'id' => $string->id ), array( '%d' ) );
			} else {
				// check if we have a new string in the right context
				
				$domains = $this->get_domains_found( );
				
				foreach ( $domains as $domain => $count ) {
					$new_string_id = $wpdb->get_var( $wpdb->prepare( "
						SELECT id
						FROM {$wpdb->prefix}icl_strings
						WHERE context = %s AND name = %s AND value = %s",
						$domain, $string->name, $string->value
						) );
					
					if ( $new_string_id ) {
						
						// See if it has the same translations

						/** @var array $new_translations */
						$new_translations = $wpdb->get_results( $wpdb->prepare( "
							SELECT id, language, status, value
							FROM {$wpdb->prefix}icl_string_translations
							WHERE string_id = %d",
							$new_string_id
							) );
						
						foreach ( $new_translations as $new_translation) {
							foreach ( $old_translations as $index => $old_translation ) {
								if ( $new_translation->language == $old_translation->language &&
										$new_translation->status == $old_translation->status &&
										$new_translation->value == $old_translation->value ) {
									unset( $old_translations[$index] );
								}
							}
						}
						if ( ! $old_translations ) {
							// We don't have any old translations that are not in the new strings so we can delete the string.
							
							$wpdb->delete( $wpdb->prefix . 'icl_strings', array( 'id' => $string->id ), array( '%d' ) );
							break;
						}
						
					}					
					
				}
				
			}
		}
		
		// Rename the context for any strings that are in the old context
		// This way the update message will no longer show.
		
		$obsolete_context = str_replace( 'plugin ', '', $old_context );
		$obsolete_context = str_replace( 'theme ', '', $obsolete_context );
		$obsolete_context = $obsolete_context . ' (obsolete)';
		
		$wpdb->query( $wpdb->prepare( "
									 UPDATE {$wpdb->prefix}icl_strings
									 SET context = %s
									 WHERE context = %s
									 ",
									 $obsolete_context,
									 $old_context ) );
        
    }
	
	protected function copy_old_translations( $contexts, $prefix ) {
		foreach ( $contexts as $context ) {
			$old_strings = $this->get_strings_by_context( $prefix . ' ' . $context );
			if ( 0 === count( $old_strings ) ) {
				continue;
			}

			$old_translations = $this->get_strings_translations( $old_strings );

			$new_strings = $this->get_strings_by_context( $context );
			$new_translations = $this->get_strings_translations( $new_strings );

			/** @var array $old_translations */
			foreach( $old_translations as $old_translation ) {
				// see if we have a new translation.
				$found = false;
				/** @var array $new_translations */
				foreach ( $new_translations as $new_translation ) {
					if ( $new_translation->string_id == $old_translation->string_id &&
							$new_translation->language == $old_translation->language ) {
						$found = true;
						break;
					}
				}
				
				if ( ! $found ) {
					// Copy the old translation to the new string.
					
					// Find the original
					foreach ( $old_strings as $old_string ) {
						if ( $old_string->id == $old_translation->string_id ) {
							// See if we have the same string in the new strings
							foreach ( $new_strings as $new_string ) {
								if ( $new_string->value == $old_string->value ) {
									// Add the old translation to new string.
									icl_add_string_translation( $new_string->id, $old_translation->language, $old_translation->value, ICL_TM_COMPLETE );
									break;
								}
							}
							break;
						}
					}
					
				}
				
			}
		}
			
	}

	/**
	 * @param string $context
	 *
	 * @return array
	 */
	private function get_strings_by_context( $context ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "
				SELECT id, name, value
				FROM {$wpdb->prefix}icl_strings
				WHERE context = %s",
			$context
		) );
	}

	/**
	 * @param array $strings
	 *
	 * @return array
	 */
	private function get_strings_translations( $strings ) {
		global $wpdb;

		$translations = array();

		if (count($strings)) {
			foreach ( array_chunk( $strings, 100 ) as $chunk ) {
				$ids = array();
				foreach ( $chunk as $string ) {
					$ids[] = $string->id;
				}
				$ids = implode( ',', $ids );

				$rows = $wpdb->get_results( "
							SELECT id, string_id, language, status, value
							FROM {$wpdb->prefix}icl_string_translations
							WHERE string_id IN ({$ids})"
				);

				$translations = array_merge( $translations, $rows );
			}
		}

		return $translations;
	}

	protected function remove_notice( $notice_id ) {
		global $wpml_st_admin_notices;
		if ( isset( $wpml_st_admin_notices ) ) {
			/** @var WPML_ST_Themes_And_Plugins_Updates $wpml_st_admin_notices */
			$wpml_st_admin_notices->remove_notice( $notice_id );
		}
	}


	/**
	 * @return WPML_ST_DB_Mappers_Strings
	 */
	public function get_strings_mapper() {
		if ( null === $this->strings_mapper ) {
			global $wpdb;
			$this->strings_mapper = new WPML_ST_DB_Mappers_Strings( $wpdb );
		}

		return $this->strings_mapper;
	}

	/**
	 * @param WPML_ST_DB_Mappers_Strings $strings_mapper
	 */
	public function set_strings_mapper( WPML_ST_DB_Mappers_Strings $strings_mapper ) {
		$this->strings_mapper = $strings_mapper;
	}

	/**
	 * @return WPML_ST_DB_Mappers_String_Positions
	 */
	public function get_string_positions_mapper() {
		if ( null === $this->string_positions_mapper ) {
			global $wpdb;
			$this->string_positions_mapper = new WPML_ST_DB_Mappers_String_Positions( $wpdb );
		}

		return $this->string_positions_mapper;
	}

	/**
	 * @param WPML_ST_DB_Mappers_String_Positions $string_positions_mapper
	 */
	public function set_string_positions_mapper( WPML_ST_DB_Mappers_String_Positions $string_positions_mapper ) {
		$this->string_positions_mapper = $string_positions_mapper;
	}

	/**
	 * @return WPML_File_Name_Converter
	 */
	public function get_file_name_converter() {
		if ( null === $this->file_name_converter ) {
			$this->file_name_converter = new WPML_File_Name_Converter();
		}

		return $this->file_name_converter;
	}

	/**
	 * @param WPML_File_Name_Converter $converter
	 */
	public function set_file_name_converter(WPML_File_Name_Converter $converter) {
		$this->file_name_converter = $converter;
	}

	/**
	 * @return WPML_File
	 */
	protected function get_wpml_file() {
		if ( ! $this->wpml_file ) {
			$this->wpml_file = new WPML_File();
		}

		return $this->wpml_file;
	}

	private function is_string_preview() {
		$is_string_preview = false;
		if ( array_key_exists( 'icl_string_track_value', $_GET ) || array_key_exists( 'icl_string_track_context', $_GET ) ) {
			$is_string_preview = true;
		}

		return $is_string_preview;
	}
}

