<?php

class WPML_Register_String_Filter extends WPML_Displayed_String_Filter {
	/**
	 * @var array
	 */
	private $excluded_contexts = array();

	private $registered_string_cache = array();
	
	/** @var  WPML_ST_String_Factory $string_factory */
	private $string_factory;

	/**
	 * @var WPML_Autoregister_Save_Strings
	 */
	private $save_strings;

	// Current string data.
	protected $name;
	protected $domain;
	protected $gettext_context;
	protected $name_and_gettext_context;
	protected $key;

	/**
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 * @param string $language
	 * @param null|object $string_factory
	 * @param null $existing_filter
	 * @param array $excluded_contexts
	 * @param WPML_ST_Db_Cache_Factory|null $db_cache_factory
	 * @param WPML_Autoregister_Save_Strings|null $save_strings
	 */
	public function __construct(
		&$wpdb, &$sitepress,
		$language,
		&$string_factory,
		$existing_filter = null,
		array $excluded_contexts = array(),
		WPML_ST_DB_Cache_Factory $db_cache_factory = null,
		WPML_Autoregister_Save_Strings $save_strings = null
	) {
		parent::__construct( $wpdb, $sitepress, $language, $existing_filter, $db_cache_factory );
		$this->string_factory    = &$string_factory;
		$this->excluded_contexts = $excluded_contexts;
		$this->save_strings      = $save_strings;
	}

	public function translate_by_name_and_context( $untranslated_text, $name, $context = '', &$has_translation = null ) {
		if ( $untranslated_text ) {
			list ($name_tmp, $domain, $gettext_content) = $this->transform_parameters( $name, $context );
			$translation = $this->db_cache->get_translation( $name_tmp, $domain, $untranslated_text, $gettext_content );

			if ( ! $translation instanceof WPML_ST_Page_Translation ) {
				if ( ! in_array( $domain, $this->excluded_contexts ) ) {
					$save_strings = $this->get_save_strings();
					$save_strings->save( $untranslated_text, $name_tmp, $domain, $gettext_content );
				}

				$result = $untranslated_text;
				$has_translation = false;
			} else {
				$result = $translation->get_value();
				$has_translation = $translation->has_translation();
			}
		} else {
			$result = parent::translate_by_name_and_context( $untranslated_text, $name, $context, $has_translation );
		}

		return $result;
	}

	public function force_saving_of_autoregistered_strings() {
		$this->get_save_strings()->shutdown();
	}

	public function register_string( $context, $name, $value, $allow_empty_value = false, $source_lang = '' ) {

		$name = trim( $name ) ? $name : md5( $value );
		$this->initialize_current_string( $name, $context );

		/* cpt slugs - do not register them when scanning themes and plugins
		 * if name starting from 'URL slug: '
		 * and context is different from 'WordPress'
		 */
		if ( substr( $name, 0, 10 ) === 'URL slug: ' && 'WordPress' !== $context ) {
			return false;
		}

		list( $domain, $context, $key ) = $this->key_by_name_and_context( $name, $context );
		list( $name, $context ) = $this->truncate_name_and_context( $name, $context );

		if ( $source_lang == '' ) {
			$source_lang = $this->get_save_strings()->get_source_lang( $name, $domain );
		}

		$res = $this->get_registered_string( $domain, $context, $name );
		if ( $res ) {
			$string_id = $res['id'];

			$update_string = array();
			if ( $value != $res['value'] ) {
				$update_string['value'] = $value;
			}
			$existing_lang = $this->string_factory->find_by_id($res['id'])->get_language();
			if ( ! empty( $update_string ) ) {
				if ( $existing_lang == $source_lang ) {
					$this->wpdb->update( $this->wpdb->prefix . 'icl_strings', $update_string, array( 'id' => $string_id ) );
					$this->wpdb->update( $this->wpdb->prefix . 'icl_string_translations',
						array( 'status' => ICL_TM_NEEDS_UPDATE ),
						array( 'string_id' => $string_id ) );
					icl_update_string_status( $string_id );
				} else {
					$orig_data = array( 'string_id' => $string_id, 'language' => $source_lang );
					$update_string['status'] = ICL_TM_COMPLETE;
					if ( $this->wpdb->get_var( $this->wpdb->prepare( "SELECT COUNT(*)
																	  FROM {$this->wpdb->prefix}icl_string_translations
																	  WHERE string_id = %d
																	  	AND language = %s",
						$string_id, $source_lang ) )
					) {
						$this->wpdb->update( $this->wpdb->prefix . 'icl_string_translations',
							$update_string,
							$orig_data );
					} else {
						$this->wpdb->insert( $this->wpdb->prefix . 'icl_string_translations',
							array_merge( $update_string, $orig_data ) );
					}
					icl_update_string_status( $string_id );
				}
			}
		} else {
			$string_id = $this->save_string( $value, $allow_empty_value, $source_lang, $domain, $context, $name );
		}

		if ( ! isset( $this->name_cache[ $key ] ) ) {
			$this->name_cache[ $key ] = $value;
		}
		
		$this->found_cache[ $domain. $source_lang ][ $name ] = array( $value, true );
		$this->cache_needs_saving = true;


		return $string_id;
	}
	
	private function get_registered_string( $domain, $context, $name ) {
		
		if ( ! isset( $this->registered_string_cache[ $domain ] ) ) {
			// preload all the strings for this domain.

			$query = $this->wpdb->prepare( "SELECT id, value, gettext_context, name FROM {$this->wpdb->prefix}icl_strings WHERE context=%s",
										   $domain,
										   $context,
										   $name );
			$res   = $this->wpdb->get_results( $query );
			$this->registered_string_cache[ $domain ] = array();
			
			foreach( $res as $string ) {
				$this->registered_string_cache[ $domain ][ md5( $domain . $string->name . $string->gettext_context ) ] = array( 'id'    => $string->id,
																															    'value' => $string->value
																															  );
			}
		}

		$key = md5( $domain . $name . $context );
		if ( ! isset( $this->registered_string_cache[ $domain ][ $key ] ) ) {
			$this->registered_string_cache[ $domain ][ $key ] = null;
		}
		return $this->registered_string_cache[ $domain ][ $key ];
	}
	
	

	private function save_string( $value, $allow_empty_value, $language, $domain, $context, $name ) {
		if ( $allow_empty_value || 0 !== strlen( $value ) ) {
			$this->wpdb->insert( $this->wpdb->prefix . 'icl_strings', array(
				'language'                => $language,
				'context'                 => $domain,
				'gettext_context'         => $context,
				'domain_name_context_md5' => md5( $domain . $name . $context ),
				'name'                    => $name,
				'value'                   => $value,
				'status'                  => ICL_TM_NOT_TRANSLATED,
			) );
			$string_id = $this->wpdb->insert_id;
			if ( $string_id === 0 ) {
				throw new Exception( 'Could not add String with arguments: value: ' . $value . ' allow_empty_value:' . $allow_empty_value . ' language: ' . $language );
			}

			icl_update_string_status( $string_id );
			
			$key = md5( $domain . $name . $context );
			$this->registered_string_cache[ $domain ][ $key ] = array( 'id'    => $string_id,
																	   'value' => $value
																	 );
		} else {
			$string_id = 0;
		}

		return $string_id;
	}

	/**
	 * @param string          $name
	 * @param string|string[] $context
	 *
	 * @return string[]
	 */
	protected function initialize_current_string( $name, $context ) {
		list ( $this->domain, $this->gettext_context ) = wpml_st_extract_context_parameters( $context );

		list( $this->name, $this->domain ) = array_map( array(
			$this,
			'truncate_long_string'
		), array( $name, $this->domain ) );

		$this->name_and_gettext_context = $this->name . $this->gettext_context;
		$this->key = md5( $this->domain . $this->name_and_gettext_context );
	}

	/**
	 * @param string          $name
	 * @param string|string[] $context
	 *
	 * @return array
	 */
	protected function truncate_name_and_context( $name, $context) {
		if ( is_array( $context ) ) {
			$domain          = isset ( $context[ 'domain' ] ) ? $context[ 'domain' ] : '';
			$gettext_context = isset ( $context[ 'context' ] ) ? $context[ 'context' ] : '';
		} else {
			$domain = $context;
			$gettext_context = '';
		}
		list( $name, $domain ) = array_map( array(
			$this,
			'truncate_long_string'
		), array( $name, $domain ) );

		return array( $name . $gettext_context, $domain );
	}

	protected function key_by_name_and_context( $name, $context ) {

		return array(
			$this->domain,
			$this->gettext_context,
			md5( $this->domain . $this->name_and_gettext_context )
		);
	}

	/**
	 * @return WPML_Autoregister_Save_Strings
	 */
	private function get_save_strings() {
		if ( null === $this->save_strings ) {
			$this->save_strings = new WPML_Autoregister_Save_Strings( $this->wpdb, $this->sitepress );
		}

		return $this->save_strings;
	}
}