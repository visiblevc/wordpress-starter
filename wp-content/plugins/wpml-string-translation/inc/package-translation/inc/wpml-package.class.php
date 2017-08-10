<?php

class WPML_Package {
	public  $ID;
	public  $view_link;
	public  $edit_link;
	public  $is_translation;
	public  $string_data;
	public  $title;
	public  $new_title;
	public  $kind_slug;
	public  $kind;
	public  $trid;
	public  $name;
	public  $translation_element_type;
	public  $post_id;

	private $element_type_prefix;

	/**
	 * @param stdClass|WPML_Package|array|int $data_item
	 */
	function __construct( $data_item ) {
		$this->element_type_prefix = 'package';
		$this->view_link           = '';
		$this->edit_link           = '';
		$this->post_id             = null;
		if ( $data_item ) {
			if ( is_object( $data_item ) ) {
				$data_item = get_object_vars( $data_item );
			}
			if ( is_numeric( $data_item ) ) {
				$data_item = $this->init_from_id( $data_item, ARRAY_A );
			}
			if ( isset( $data_item[ 'title' ] ) ) {
				$this->new_title = $data_item[ 'title' ];
			}
			if ( $data_item && is_array( $data_item ) ) {
				$this->init_from_array( $data_item );
			}
			$this->new_title = $this->new_title != $this->title ? $this->new_title : null;
		}
	}

	private function init_from_id( $id, $output = OBJECT ) {
		global $wpdb;

		$packages_query    = "SELECT * FROM {$wpdb->prefix}icl_string_packages WHERE id=%s";
		$packages_prepared = $wpdb->prepare( $packages_query, $id );
		$package           = $wpdb->get_row( $packages_prepared, $output );

		return $package;
	}

	public function __get( $property ) {
		if ( $property == 'id' ) {
			_deprecated_argument( 'id', '0.0.2', "Property 'id' is deprecated. Please use 'ID'." );

			return $this->ID;
		}
		if ( $property == 'post_id' ) {
			return $this->ID;
		}
		if ( $property == 'post_title' ) {
			return $this->title;
		}
		if ( $property == 'post_type' ) {
			return $this->kind_slug;
		}

		return null;
	}

	public function __set( $property, $value ) {
		if ( $property == 'id' ) {
			_deprecated_argument( 'id', '0.0.2', "Property 'id' is deprecated. Please use 'ID'." );
			$this->ID = $value;
		} else {
			$this->$property = $value;
		}
	}

	public function __isset( $property ) {
		if ( $property == 'id' ) {
			return isset( $this->ID );
		}

		return false;
	}

	public function __unset( $property ) {
		if ( $property == 'id' ) {
			unset( $this->$property );
		}
	}

	public function get_translation_element_type() {
		return $this->translation_element_type;
	}

	public function get_package_post_id() {
		return $this->get_element_type_prefix() . '_' . $this->kind_slug . '_' . $this->ID;
	}

	public function get_element_type_prefix() {
		return $this->element_type_prefix;
	}

	public function set_package_post_data() {
		$this->translation_element_type = $this->element_type_prefix . '_' . $this->kind_slug;
		$this->update_strings_data();
	}

	/**
	 * @return array
	 */
	public function update_strings_data() {
		$strings    = array();
		$package_id = $this->ID;
		if ( $package_id ) {
			$results = $this->get_package_strings();
			foreach ( $results as $result ) {
				$string_name             = $this->get_package_string_name_from_st_name( $result );
				$strings[ $string_name ] = $result->value;
			}

			// Add/update any registered strings
			if ( isset( $package_strings[ $package_id ][ 'strings' ] ) ) {
				foreach ( $package_strings[ $package_id ][ 'strings' ] as $id => $string_data ) {
					$strings[ $id ] = $string_data[ 'value' ];
				}
			}
			$this->string_data = $strings;
		}
	}

	/**
	 * @return mixed
	 */
	public function get_package_strings() {
		global $wpdb;
		$package_id = $this->ID;
		$results    = false;
		if ( $package_id ) {
			$results_query   = "SELECT id, name, value, type FROM {$wpdb->prefix}icl_strings WHERE string_package_id=%d";
			$results_prepare = $wpdb->prepare( $results_query, $package_id );
			$results         = $wpdb->get_results( $results_prepare );
		}

		return $results;
	}
	
	public function set_strings_language( $language_code ) {
		global $wpdb;
		$package_id = $this->ID;
		if ( $package_id ) {
			$update_query   = "UPDATE {$wpdb->prefix}icl_strings SET language=%s WHERE string_package_id=%d";
			$update_prepare = $wpdb->prepare( $update_query, $language_code, $package_id );
			$wpdb->query( $update_prepare );
		}
		
	}

	/**
	 * @param $result
	 *
	 * @return string
	 */
	private function get_package_string_name_from_st_name( $result ) {

		// package string name is the same as the string name.
		return $result->name;
	}

	private function sanitize_attributes() {
		if ( isset( $this->name ) ) {
			$this->name = $this->sanitize_string_name( $this->name );
		}
		if ( (! isset( $this->title ) || $this->title === '') && isset($this->name) ) {
			$this->title = $this->name;
		}
		if ( ! isset( $this->edit_link ) ) {
			$this->edit_link = '';
		}

		$this->sanitize_kind();
	}

	public function create_new_package_record() {
		$this->sanitize_attributes();

		$package_id = $this->package_exists();
		if ( ! $package_id ) {
			global $wpdb;

			$data = array(
				'kind_slug' => $this->kind_slug,
				'kind'      => $this->kind,
				'name'      => $this->name,
				'title'     => $this->title,
				'edit_link' => $this->edit_link,
				'view_link' => $this->view_link,
				'post_id'   => $this->post_id,
			);
			$wpdb->insert( $wpdb->prefix . 'icl_string_packages', $data );
			$package_id = $wpdb->insert_id;

			$this->ID = $package_id;
		}

		return $package_id;
	}

	public function update_package_record() {
		$result = false;

		if ( $this->ID ) {
			global $wpdb;

			$update_data = array(
				'kind_slug' => $this->kind_slug,
				'kind'      => $this->kind,
				'name'      => $this->name,
				'title'     => $this->title,
				'edit_link' => $this->edit_link,
				'view_link' => $this->view_link,
			);
			$update_where = array(
				'ID' => $this->ID
			);
			$result = $wpdb->update( $wpdb->prefix . 'icl_string_packages', $update_data, $update_where );
		}

		return $result;
	}

	public function get_package_id() {
		return $this->ID;
	}

	public function sanitize_string_name( $string_name ) {
		$string_name = preg_replace( '/[ \[\]]+/', '-', $string_name );

		return $string_name;
	}

	function translate_string( $string_value, $sanitized_string_name ) {
		$package_id = $this->get_package_id();

		if ( $package_id ) {
			$sanitized_string_name = $this->sanitize_string_name( $sanitized_string_name );

			$string_context = $this->get_string_context_from_package();

			$string_name = $sanitized_string_name;

			return icl_translate( $string_context, $string_name, $string_value );
		} else {
			return $string_value;
		}
	}

	function get_string_context_from_package() {
		return $this->kind_slug . '-' . $this->name;
	}

	public function get_string_id_from_package( $string_name, $string_value ) {
		$package_id     = $this->get_package_id();
		$string_context = $this->get_string_context_from_package();

		$string_data = array(
			'context' => $string_context,
			'name' => $string_name,
		);

		/**
		 * @param int|null $default
		 * @param array    $string_data {
		 *
		 * @type string    $context
		 * @type string    $name        Optional
		 *                           }
		 */
		$string_id = apply_filters('wpml_string_id', null, $string_data);

		if ( ! $string_id ) {
			$string_id = icl_register_string( $string_context, $string_name, $string_value, null, $this->get_package_language() );
		}

		return $string_id;
	}

	function get_translated_strings( $strings ) {
		$package_id = $this->get_package_id();

		if ( $package_id ) {
			$results = $this->get_package_strings();

			foreach ( $results as $result ) {
				$translations = icl_get_string_translations_by_id( $result->id );
				if ( ! empty ( $translations ) ) {
					$string_name             = $this->get_package_string_name_from_st_name( $result );
					$strings[ $string_name ] = $translations;
				}
			}
		}

		return $strings;
	}

	function set_translated_strings( $translations ) {
		global $wpdb;

		$this->sanitize_attributes();
		$package_id = $this->get_package_id();

		if ( $package_id ) {
			foreach ( $translations as $string_name => $languages ) {
				$string_id_query   = "SELECT id FROM {$wpdb->prefix}icl_strings WHERE name='%s'";
				$string_id_prepare = $wpdb->prepare( $string_id_query, $string_name );
				$string_id         = $wpdb->get_var( $string_id_prepare );
				foreach ( $languages as $language_code => $language_data ) {
					icl_add_string_translation( $string_id, $language_code, $language_data[ 'value' ], $language_data[ 'status' ] );
				}
			}
		}
	}

	private function init_from_array( $args ) {
		foreach ( $args as $key => $value ) {
			if ( 'id' == $key ) {
				$key = 'ID';
			}
			$this->$key = $value;
		}

		$this->sanitize_attributes();

		if ( $this->package_id_exists() || $this->package_name_and_kind_exists() ) {
			$this->set_package_from_db();
		}
		$this->set_package_post_data();
	}

	public function has_kind_and_name() {
		return ( isset( $this->kind ) && isset( $this->name ) && $this->kind && $this->name );
	}

	private function set_package_from_db() {
		$package = false;
		if ( $this->package_id_exists() ) {
			$package = $this->get_package_from_id( $this->ID );
		} elseif ( $this->package_name_and_kind_exists() ) {
			$package = $this->get_package_from_name_and_kind();
		}
		if ( $package ) {
			$this->object_to_package( $package );
		}
		$this->sanitize_kind();
	}

	private function get_package_from_id() {
		$result = false;
		if ( $this->has_id() ) {
			global $wpdb;

			$package_query    = "SELECT * FROM {$wpdb->prefix}icl_string_packages WHERE ID=%d";
			$package_prepared = $wpdb->prepare( $package_query, array( $this->ID ) );

			$result = $wpdb->get_row( $package_prepared );
		}

		return $result;
	}

	private function get_package_from_name_and_kind() {
		global $wpdb;

		$package_query    = "SELECT * FROM {$wpdb->prefix}icl_string_packages WHERE kind_slug=%s AND name=%s";
		$package_prepared = $wpdb->prepare( $package_query, array( $this->kind_slug, $this->name ) );

		return $wpdb->get_row( $package_prepared );
	}

	private function package_name_and_kind_exists() {
		$result = false;
		if ( $this->has_kind_and_name() ) {
			global $wpdb;

			$package_query    = "SELECT ID FROM {$wpdb->prefix}icl_string_packages WHERE kind_slug=%s AND name=%s";
			$package_prepared = $wpdb->prepare( $package_query, array( $this->kind_slug, $this->name ) );

			$result = $wpdb->get_var( $package_prepared );
		}

		return $result;
	}

	private function package_id_exists() {
		$result = false;
		if ( $this->has_id() ) {
			global $wpdb;

			$package_query    = "SELECT ID FROM {$wpdb->prefix}icl_string_packages WHERE ID=%d";
			$package_prepared = $wpdb->prepare( $package_query, array( $this->ID ) );

			$result = $wpdb->get_var( $package_prepared );
		}

		return $result;
	}

	/**
	 * @return bool|mixed
	 */
	protected function package_exists() {
		$existing_package = false;
		if ( $this->has_id() ) {
			$existing_package = $this->package_id_exists();
		} elseif ( $this->has_kind_and_name() ) {
			$existing_package = $this->package_name_and_kind_exists();
		}

		return $existing_package;
	}

	/**
	 * @return bool
	 */
	private function has_id() {
		return isset( $this->ID ) && $this->ID;
	}

	/**
	 * @param $package
	 */
	private function object_to_package( $package ) {
		$this->ID        = $package->ID;
		$this->kind_slug = $package->kind_slug;
		$this->kind      = $package->kind;
		$this->name      = $package->name;
		$this->title     = $package->title;
		$this->edit_link = $package->edit_link;
		$this->view_link = $package->view_link;
	}

	private function get_kind_from_slug() {
		global $wpdb;
		$kinds_query    = "SELECT kind FROM {$wpdb->prefix}icl_string_packages WHERE kind_slug=%s GROUP BY kind";
		$kinds_prepared = $wpdb->prepare( $kinds_query, $this->kind_slug );
		$kinds          = $wpdb->get_col( $kinds_prepared );
		if ( count( $kinds ) > 1 ) {
			throw new WPML_Package_Exception( 'error', 'Package contains multiple kinds' );
		}
		if ( $kinds ) {
			return $kinds[ 0 ];
		}
		return null;
	}

	private function sanitize_kind() {
		if ( isset( $this->kind ) && ( ! isset( $this->kind_slug ) || trim( $this->kind_slug ) === '' ) ) {
			$this->kind_slug = sanitize_title_with_dashes( $this->kind );
		}
		if ( $this->kind == $this->kind_slug ) {
			$this->kind = $this->get_kind_from_slug();
		}
	}

	public function get_package_element_type() {
		return 'package_' . $this->kind_slug;
	}
	
	public function get_package_language() {
		global $sitepress;

		if ( $this->post_id ) {
			$post = get_post( $this->post_id );
			$details = $sitepress->get_element_language_details( $this->post_id, 'post_' . $post->post_type );
		} else {
			$element_type = $this->get_package_element_type();
			$details      = $sitepress->get_element_language_details( $this->ID, $element_type );
		}
		
		if ( $details ) {
			return $details->language_code;
		} else {
			return null;
		}
	}
	
	public function are_all_strings_included( $strings ) {
		// check to see if all the strings in this package are present in $strings
		
		$package_strings = $this->get_package_strings();
		if ( is_array( $package_strings ) ) {
			foreach( $package_strings as $string ) {
				if ( ! in_array( $string->id, $strings ) ) {
					return false;
				}
			}
		}
		
		return true;
	}
	
}
