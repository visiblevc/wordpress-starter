<?php

class WPML_Package_Helper {
	private   $default_language;
	private   $last_registered_string_id;
	protected $registered_strings;
	private   $package_cleanup;

	private $cache_group;

	function __construct() {
		global $wpdb;

		$this->registered_strings = array();
		$this->cache_group        = 'string_package';
		$this->package_cleanup = new WPML_ST_Package_Cleanup( $wpdb );
	}

	/**
	 * @param $package_id
	 */
	protected function delete_package( $package_id ) {
		// delete the strings and the translations

		$this->delete_package_strings( $package_id );
		$this->delete_package_translation_jobs( $package_id );
		$this->delete_package_translations( $package_id );

		global $wpdb;
		$delete_query   = "DELETE FROM {$wpdb->prefix}icl_string_packages WHERE id=%d";
		$delete_prepare = $wpdb->prepare( $delete_query, $package_id );
		$wpdb->query( $delete_prepare );
	}

	/**
	 * @param $package_id
	 *
	 * @return array
	 */
	protected function get_strings_ids_from_package_id( $package_id ) {
		global $wpdb;
		$string_ids_query   = "SELECT id FROM {$wpdb->prefix}icl_strings WHERE string_package_id=%d";
		$string_ids_prepare = $wpdb->prepare( $string_ids_query, $package_id );
		$string_ids         = $wpdb->get_col( $string_ids_prepare );

		return $string_ids;
	}

	/**
	 * @param $package_id
	 */
	protected function delete_package_strings( $package_id ) {
		$strings = $this->get_strings_ids_from_package_id( $package_id );

		foreach ( $strings as $string_id ) {
			do_action( 'wpml_st_delete_all_string_data', $string_id );
		}
	}

	protected function delete_package_translation_jobs( $package_id ) {
		$package = new WPML_Package( $package_id );
		$tm      = new WPML_Package_TM( $package );
		$tm->delete_translation_jobs();
	}

	protected function delete_package_translations( $package_id ) {
		$package = new WPML_Package( $package_id );
		$tm      = new WPML_Package_TM( $package );
		$tm->delete_translations();
	}

	protected function loaded() {
		$this->default_language = icl_get_default_language();
	}

	/**
	 * @param string             $string_value
	 * @param string             $string_name
	 * @param array|WPML_Package $package
	 * @param string             $string_title
	 * @param string             $string_type
	 */
	final function register_string_action( $string_value, $string_name, $package, $string_title, $string_type ) {
		$this->register_string_for_translation( $string_value, $string_name, $package, $string_title, $string_type );

		return $this->last_registered_string_id;
	}

	/**
	 * @param $default
	 * @param $package
	 * @param $string_name
	 * @param $string_value
	 *
	 * @return bool|int|mixed
	 */
	function string_id_from_package_filter( $default, $package, $string_name, $string_value ) {
		$string_id = $this->get_string_id_from_package( $package, $string_name, $string_value );
		if ( ! $string_id ) {
			return $default;
		}

		return $string_id;
	}

	function string_title_from_id_filter( $default, $string_id ) {
		global $wpdb;
		$string_title_query = 'SELECT title FROM ' . $wpdb->prefix . 'icl_strings WHERE id=%d';
		$string_title_sql   = $wpdb->prepare( $string_title_query, array( $string_id ) );

		$string_title = $wpdb->get_var( $string_title_sql );

		if ( ! $string_title ) {
			$string_title = $default;
		}

		return $string_title;
	}

	/**
	 * @param string             $string_value
	 * @param string             $string_name
	 * @param array|WPML_Package $package
	 * @param string             $string_title
	 * @param string             $string_type
	 *
	 * @return string
	 */
	final function register_string_for_translation( $string_value, $string_name, $package, $string_title, $string_type ) {
		$package    = new WPML_Package( $package );
		$package_id = $package->ID;
		if ( ! $package_id ) {
			// need to create a new record.

			if ( $package->has_kind_and_name() ) {
				$package_id = $this->create_new_package( $package );
				$package    = new WPML_Package( $package_id );
			}
		}
		if ( $package_id ) {
			$this->maybe_update_package( $package );
			$tm = new WPML_Package_TM( $package );
			$tm->validate_translations();

			$this->init_package_registered_strings( $package_id );

			$string_name = $package->sanitize_string_name( $string_name );

			$this->set_package_registered_strings( $package_id, $string_type, $string_title, $string_name, $string_value );
			$this->last_registered_string_id = $this->register_string_with_wpml( $package, $string_name, $string_title, $string_type, $string_value );
			$this->package_cleanup->record_register_string( $package, $this->last_registered_string_id );
		}

		return $string_value;
	}

	final function get_string_context_from_package( $package ) {
		$package = new WPML_Package( $package );

		return $package->get_string_context_from_package();
	}

	/**
	 * @param WPML_Package $package
	 * @param string       $string_name
	 * @param string       $string_title
	 * @param string       $string_type
	 * @param string       $string_value
	 *
	 * @return bool|int|mixed
	 */
	final function register_string_with_wpml( $package, $string_name, $string_title, $string_type, $string_value ) {
		$string_id = $this->get_string_id_from_package( $package, $string_name, $string_value );

		if ( $string_id ) {
			$package_id = $package->ID;
			$this->update_string_from_package( $package_id, $string_title, $string_type, $string_value, $string_id );
		}

		return $string_id;
	}

	final function translate_string( $string_value, $string_name, $package ) {
		$result = $string_value;

		if ( is_string( $string_value ) ) {
			$package = new WPML_Package( $package );

			if ( $package ) {
				$sanitized_string_name = $package->sanitize_string_name( $string_name );

				$result = $package->translate_string( $string_value, $sanitized_string_name );
			}
		}

		return $result;
	}

	final function get_translated_strings( $strings, $package ) {
		$package = new WPML_Package( $package );

		return $package->get_translated_strings( $strings );
	}

	final function set_translated_strings( $translations, $package ) {
		$package = new WPML_Package( $package );
		$package->set_translated_strings( $translations );
	}

	final function get_translatable_types( $types ) {
		global $wpdb;

		$package_kinds = $wpdb->get_results( "SELECT kind, kind_slug FROM {$wpdb->prefix}icl_string_packages WHERE id>0" );

		// Add any packages found to the $types array
		foreach ( $package_kinds as $package_data ) {
			$package_kind_slug = $package_data->kind_slug;
			$package_kind      = $package_data->kind;
			$kinds_added       = array_keys( $types );
			if ( ! in_array( $package_kind_slug, $kinds_added ) ) {
				$translatable_type                        = new stdClass();
				$translatable_type->name                  = $package_kind_slug;
				$translatable_type->label                 = $package_kind;
				$translatable_type->prefix                = 'package';
				$translatable_type->labels                = new stdClass();
				$translatable_type->labels->singular_name = $package_kind;
				$translatable_type->labels->name          = $package_kind;
				$translatable_type->external_type         = 1;
				$types[ $package_kind_slug ]              = $translatable_type;
			}
		}

		return $types;
	}

	/**
	 * @param WPML_Package             $item
	 * @param int|WP_Post|WPML_Package $package
	 *
	 * @return bool|WPML_Package
	 */
	final public function get_translatable_item( $item, $package ) {
		$tm = new WPML_Package_TM( $item );

		return $tm->get_translatable_item( $package );
	}

	final function get_post_title( $title, $package_id ) {
		$package = new WPML_Package( $package_id );
		if ( $package ) {
			$title = $package->kind . ' - ' . $package->title;
		}

		return $title;
	}

	final function get_editor_string_name( $name, $package ) {
		$package    = new WPML_Package( $package );
		$package_id = $package->ID;
		$title      = $this->get_editor_string_element( $name, $package_id, 'title' );
		if ( $title && $title != '' ) {
			$name = $title;
		}

		return $name;
	}

	final function get_editor_string_style( $style, $field_type, $package ) {
		$package       = new WPML_Package( $package );
		$package_id    = $package->ID;
		$element_style = $this->get_editor_string_element( $field_type, $package_id, 'type' );
		if ( $element_style ) {
			$style = 0;
			if ( defined( $element_style ) ) {
				$style = constant( $element_style );
			}
		}

		return $style;
	}

	final public function get_element_id_from_package_filter( $default, $package_id ) {
		global $wpdb;
		$element_id_query   = 'SELECT name FROM ' . $wpdb->prefix . 'icl_string_packages WHERE ID=%d';
		$element_id_prepare = $wpdb->prepare( $element_id_query, $package_id );
		$element_id         = $wpdb->get_var( $element_id_prepare );
		if ( ! $element_id ) {
			$element_id = $default;
		}

		return $element_id;
	}

	final public function get_package_type( $type, $post_id ) {
		$package = new WPML_Package( $post_id );
		if ( $package ) {
			return $this->get_package_context( $package );
		} else {
			return $type;
		}
	}

	final public function get_package_type_prefix( $type, $post_id ) {
		if ( $type == 'package' ) {
			$package = new WPML_Package( $post_id );
			if ( $package ) {
				$type = $package->get_string_context_from_package();
			}
		}

		return $type;
	}

	/**
	 * @param              $language_for_element
	 * @param WPML_Package $current_document
	 *
	 * @return mixed
	 */
	final public function get_language_for_element( $language_for_element, $current_document ) {
		if ( $this->is_a_package( $current_document ) ) {
			global $sitepress;
			$language_for_element = $sitepress->get_language_for_element( $current_document->ID, $current_document->get_translation_element_type() );
		}

		return $language_for_element;
	}

	final protected function get_package_context( $package ) {
		$package = new WPML_Package( $package );

		return $package->kind_slug . '-' . $package->name;
	}

	final function delete_packages_ajax() {
		if ( ! $this->verify_ajax_call( 'wpml_package_nonce' ) ) {
			die( 'verification failed' );
		}
		$packages_ids = $_POST[ 'packages' ];

		$this->delete_packages( $packages_ids );

		exit;
	}

	final function delete_package_action( $name, $kind ) {
		$package_data[ 'name' ] = $name;
		$package_data[ 'kind' ] = $kind;

		$package                = new WPML_Package( $package_data );
		if ( $package && $package->ID && $this->is_a_package( $package ) ) {
			$this->delete_package( $package->ID );
			$this->flush_cache();
		}
	}

	final protected function delete_packages( $packages_ids ) {
		$flush_cache = false;

		foreach ( $packages_ids as $package_id ) {
			$this->delete_package( $package_id );

			$flush_cache = true;
		}
		if ( $flush_cache ) {
			$this->flush_cache();
		}
	}

	final function change_package_lang_ajax() {
		global $wpdb, $sitepress;

		if ( ! $this->verify_ajax_call( 'wpml_package_nonce' ) ) {
			die( 'verification failed' );
		}

		$package_id = $_POST[ 'package_id' ];

		$package = new WPML_Package( $package_id );
		$package->set_strings_language( $_POST[ 'package_lang' ] );

		$package_job = new WPML_Package_TM( $package );
		$package_job->set_language_details( $_POST[ 'package_lang' ] );

		$args = json_decode( base64_decode( $_POST[ 'args' ] ) );

		$package_metabox = new WPML_Package_Translation_Metabox( $package, $wpdb, $sitepress, $args );
		$response = array( 'metabox' => $package_metabox->get_metabox_status(),
						   'lang'    => $package_metabox->get_package_language_name()
						 );

		wp_send_json( $response );
	}

	/**
	 * @param $string_name
	 * @param $package_id
	 * @param $column
	 *
	 * @return mixed
	 */
	final private function get_editor_string_element( $string_name, $package_id, $column ) {
		global $wpdb;

		$element_query    = "SELECT " . $column . "
						FROM {$wpdb->prefix}icl_strings
						WHERE string_package_id=%d AND name=%s";
		$element_prepared = $wpdb->prepare( $element_query, array( $package_id, $string_name ) );
		$element          = $wpdb->get_var( $element_prepared );

		return $element;
	}

	final private function flush_cache() {
		// delete the cache key we use
		wp_cache_delete( 'get_all_packages', $this->cache_group );
	}

	/**
	 * @param WPML_Package $package
	 *
	 * @return bool|mixed|null|string
	 */
	final private function create_new_package( $package ) {
		$package_id = $package->create_new_package_record();

		$tm = new WPML_Package_TM( $package );

		$tm->update_package_translations( true );

		return $package_id;
	}

	/**
	 * @param $package_id
	 */
	final private function init_package_registered_strings( $package_id ) {
		if ( ! isset( $this->registered_strings[ $package_id ] ) ) {
			$this->registered_strings[ $package_id ] = array( 'strings' => array() );
		}
	}

	/**
	 * @param $package_id
	 * @param $string_type
	 * @param $string_title
	 * @param $string_name
	 * @param $string_value
	 */
	final private function set_package_registered_strings( $package_id, $string_type, $string_title, $string_name, $string_value ) {
		$this->registered_strings[ $package_id ][ 'strings' ][ $string_name ] = array(
			'title' => $string_title,
			'kind'  => $string_type,
			'value' => $string_value
		);
	}

	/**
	 * @param $package
	 * @param $string_name
	 * @param $string_value
	 *
	 * @return bool|int|mixed
	 */
	final private function get_string_id_from_package( $package, $string_name, $string_value ) {
		$package = new WPML_Package( $package );

		return $package->get_string_id_from_package( $string_name, $string_value );
	}

	/**
	 * @param $package_id
	 * @param $string_title
	 * @param $string_type
	 * @param $string_value
	 * @param $string_id
	 */
	final private function update_string_from_package( $package_id, $string_title, $string_type, $string_value, $string_id ) {
		global $wpdb;

		$update_data  = array(
			'string_package_id' => $package_id,
			'type'              => $string_type,
			'title'             => $this->truncate_long_string( $string_title ),
		);
		$update_where = array( 'id' => $string_id );
		$did_update = $wpdb->update( $wpdb->prefix . 'icl_strings', $update_data, $update_where );

		$update_data  = array(
			'value'             => $string_value
		);
		$did_update_value = $wpdb->update( $wpdb->prefix . 'icl_strings', $update_data, $update_where );
		if ( $did_update || $did_update_value ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}icl_strings
										   SET status=%d
										   WHERE id=%d AND status<>%d",
										   ICL_TM_NEEDS_UPDATE,
										   $string_id,
										   ICL_TM_NOT_TRANSLATED
										)
						);
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}icl_string_translations
										   SET status=%d
										   WHERE string_id=%d AND status<>%d",
										   ICL_TM_NEEDS_UPDATE,
										   $string_id,
										   ICL_TM_NOT_TRANSLATED
										)
						);

			$translation_ids = $wpdb->get_col( $wpdb->prepare( " SELECT translation_id
                      FROM {$wpdb->prefix}icl_translations
                      WHERE trid = ( SELECT trid
                      FROM {$wpdb->prefix}icl_translations
                      WHERE element_id = %d
                        AND element_type LIKE 'package%%'
                      LIMIT 1 )", $package_id ) );
			if ( ! empty( $translation_ids ) ) {
				$wpdb->query( "UPDATE {$wpdb->prefix}icl_translation_status
                              SET needs_update = 1
                              WHERE translation_id IN (" . wpml_prepare_in( $translation_ids, '%d' ) . " ) " );
			}
		}

		$this->flush_cache();
	}

	private function truncate_long_string( $string ) {
		return strlen( $string ) > WPML_STRING_TABLE_NAME_CONTEXT_LENGTH
			? mb_substr( $string, 0, WPML_STRING_TABLE_NAME_CONTEXT_LENGTH )
			: $string;
	}

	final function get_external_id_from_package( $package ) {
		return 'external_' . $package[ 'kind_slug' ] . '_' . $package[ 'ID' ];
	}

	final function get_string_context( $package ) {
		return sanitize_title_with_dashes( $package[ 'kind_slug' ] . '-' . $package[ 'name' ] );
	}

	final function get_package_id( $package, $from_cache = true ) {
		global $wpdb;
		static $cache = array();

		if ( $this->is_a_package( $package ) ) {
			$package = object_to_array( $package );
		}

		$key = $this->get_string_context( $package );
		if ( ! $from_cache || ! array_key_exists( $key, $cache ) ) {
			$package_id_query   = "SELECT ID FROM {$wpdb->prefix}icl_string_packages WHERE kind_slug = %s AND name = %s";
			$package_id_prepare = $wpdb->prepare( $package_id_query, array( $package[ 'kind_slug' ], $package[ 'name' ] ) );
			$package_id         = $wpdb->get_var( $package_id_prepare );
			if ( ! $package_id ) {
				return false;
			}
			$cache[ $key ] = $package_id;
		}

		return $cache[ $key ];
	}

	final public function get_all_packages() {
		global $wpdb;
		$cache_key   = 'get_all_packages';
		$cache_found = false;

		$all_packages = wp_cache_get( $cache_key, $this->cache_group, false, $cache_found );

		if ( ! $cache_found ) {
			$all_packages            = array();
			$all_packages_data_query = "SELECT * FROM {$wpdb->prefix}icl_string_packages";
			$all_packages_data       = $wpdb->get_results( $all_packages_data_query );
			foreach ( $all_packages_data as $package_data ) {
				$package                      = new WPML_Package( $package_data );
				$all_packages[ $package->ID ] = $package;
			}
			if ( $all_packages ) {
				wp_cache_set( $cache_key, $all_packages, $this->cache_group );
			}
		}

		return $all_packages;
	}

	final protected function is_a_package( $element ) {
		return is_a( $element, 'WPML_Package' );
	}

	protected function verify_ajax_call( $ajax_action ) {
		return isset( $_POST[ 'wpnonce' ] ) && wp_verify_nonce( $_POST[ 'wpnonce' ], $ajax_action );
	}

	protected function sanitize_string_name( $string_name ) {
		$string_name = preg_replace( '/[ \[\]]+/', '-', $string_name );

		return $string_name;
	}

	public function refresh_packages() {

		//TODO: deprecated.
		// This is required to support Layouts 1.0
		do_action( 'WPML_register_string_packages', 'layout', array() );
		// TODO: END deprecated.

		do_action( 'wpml_register_string_packages' );
	}

	/**
	 * @param WPML_Package $package
	 */
	private function maybe_update_package( $package ) {
		if ( $package->new_title ) {
			$package->title = $package->new_title;
			$package->update_package_record();
		}
	}

	public function change_language_of_strings( $strings, $lang ) {
		global $wpdb;

		$all_ok = true;

		$strings_in = implode(',', $strings);

		$string_packages_query   = "SELECT DISTINCT string_package_id FROM {$wpdb->prefix}icl_strings WHERE id IN ($strings_in)";
		$package_ids             = $wpdb->get_col( $string_packages_query );

		foreach( $package_ids as $package_id ) {
			if ( $package_id ) {
				$package = new WPML_Package( $package_id );
				if ( ! $package->are_all_strings_included( $strings ) ) {
					$all_ok = false;
					break;
				}
			}
		}

		if ( $all_ok ) {
			$this->set_packages_language( $package_ids, $lang );
		}

		$response = array();
		$response[ 'success' ] = $all_ok;
		if ( ! $all_ok ) {
			$response[ 'error' ] = __( 'Some of the strings selected belong to packages. You can only change the language of these strings if all strings in the packages are selected.', 'wpml-string-translation');
		}

		return $response;
	}

	public function change_language_of_strings_in_domain( $domain, $langs, $to_lang ) {
		global $wpdb;

		if ( ! empty( $langs ) ) {
			foreach( $langs as &$lang ) {
				$lang = "'" . $lang . "'";
			}
			$langs = implode(',', $langs);

			$string_packages_query   = "SELECT DISTINCT string_package_id FROM {$wpdb->prefix}icl_strings WHERE context='%s' AND language IN ($langs)";
			$string_packages_query   = $wpdb->prepare( $string_packages_query, $domain );
			$package_ids             = $wpdb->get_col( $string_packages_query );

			$this->set_packages_language( $package_ids, $to_lang );
		}

	}

	private function set_packages_language( $package_ids, $lang ) {
		foreach( $package_ids as $package_id ) {
			if ( $package_id ) {
				$package = new WPML_Package( $package_id );
				$package->set_strings_language( $lang );

				$package_job = new WPML_Package_TM( $package );
				$package_job->set_language_details( $lang );
			}
		}
	}

	/**
	 * @param null|array $packages
	 * @param int        $post_id
	 *
	 * @return array
	 */
	public function get_post_string_packages( $packages, $post_id ) {
		global $wpdb;

		$query       = $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}icl_string_packages WHERE post_id = %d", $post_id );
		$package_ids = $wpdb->get_col( $query );

		$packages = $packages ? $packages : array();

		foreach ( $package_ids as $package_id ) {
			$packages[ $package_id ] = new WPML_Package( $package_id );
		}

		return $packages;
	}

	/**
	 * @param int         $string_id
	 * @param string      $language
	 * @param string|null $value
	 * @param int|bool    $status
	 * @param int|null    $translator_id
	 * @param int|null    $translation_service
	 * @param int|null    $batch_id
	 */
	public function add_string_translation_action( $string_id, $language, $value = null, $status = false, $translator_id = null, $translation_service = null, $batch_id = null ) {
		icl_add_string_translation( $string_id, $language, $value, $status, $translator_id, $translation_service, $batch_id );
	}

	/**
	 * @param mixed $package
	 * @param int   $package_id
	 *
	 * @return WPML_Package
	 */
	public function get_string_package( $package, $package_id ) {
		return new WPML_Package( $package_id );
	}

	public function start_string_package_registration_action( $package ) {
		$this->package_cleanup->record_existing_strings( new WPML_Package( $package ) );
	}

	public function delete_unused_package_strings_action( $package ) {
		$this->package_cleanup->delete_unused_strings( new WPML_Package( $package ) );
	}

}