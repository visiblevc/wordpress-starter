<?php

/**
 * Class WPML_PB_String_Translation
 */
class WPML_PB_String_Translation {

	/** @var  wpdb $wpdb */
	private $wpdb;
	/** @var  WPML_PB_Factory $factory */
	private $factory;
	/** @var  WPML_PB_Shortcode_Strategy $strategy */
	private $strategy;

	/** @var array $packages_to_update */
	private $packages_to_update = array();

	public function __construct( wpdb $wpdb, WPML_PB_Factory $factory, IWPML_PB_Strategy $strategy ) {
		$this->wpdb    = $wpdb;
		$this->factory = $factory;
		$this->strategy = $strategy;
	}

	public function new_translation( $translated_string_id ) {
		list( $package_id, $string_id, $language ) = $this->get_package_for_translated_string( $translated_string_id );
		if ( $package_id ) {
			$package = $this->factory->get_wpml_package( $package_id );
			if ( $package->post_id && $this->strategy->get_package_kind() === $package->kind ) {
				$this->add_package_to_update_list( $package, $language );
				if ( DEFINED( 'DOING_AJAX' ) && DOING_AJAX ) {
					$this->save_translations_to_post();
				}
			}
		}
	}

	public function save_translations_to_post() {
		foreach ( $this->packages_to_update as $package_data ) {
			if ( $package_data['package']->kind == $this->strategy->get_package_kind() ) {
				$update_post = $this->strategy->get_update_post( $package_data );
				$update_post->update();
			}
		}
	}

	private function get_package_for_translated_string( $translated_string_id ) {
		$sql    = $this->wpdb->prepare(
			"SELECT s.string_package_id, s.id, t.language
			FROM {$this->wpdb->prefix}icl_strings s 
			LEFT JOIN {$this->wpdb->prefix}icl_string_translations t 
			ON s.id = t.string_id 
			WHERE t.id = %d", $translated_string_id );
		$result = $this->wpdb->get_row( $sql );

		if ( $result ) {
			return array( $result->string_package_id, $result->id, $result->language );
		} else {
			return array( null, null, null );
		}
	}

	private function add_package_to_update_list( $package, $language ) {
		if ( ! isset( $this->packages_to_update[ $package->ID ] ) ) {
			$this->packages_to_update[ $package->ID ] = array( 'package'   => $package,
			                                                   'languages' => array( $language )
			);
		} else {
			if ( ! in_array( $language, $this->packages_to_update[ $package->ID ]['languages'] ) ) {
				$this->packages_to_update[ $package->ID ]['languages'][] = $language;
			}
		}
	}

	public function get_package_strings( $package_data ) {
		$strings = array();
		$package_id = $this->get_package_id( $package_data );
		if ( $package_id ) {
			$sql_to_get_strings_with_package_id = $this->wpdb->prepare( "SELECT *
			FROM {$this->wpdb->prefix}icl_strings s
			WHERE s.string_package_id=%d",
			$package_id );
			$package_strings = $this->wpdb->get_results( $sql_to_get_strings_with_package_id );

			if ( ! empty( $package_strings ) ) {
				foreach ( $package_strings as $string ) {
					$strings[ md5( $string->value ) ] = array(
						'context'    => $string->context,
						'name'       => $string->name,
						'id'         => $string->id,
						'package_id' => $package_id,
					);
				}
			}
		}
		return $strings;
	}

	public function remove_string( $string_data ) {
		icl_unregister_string( $string_data['context'], $string_data['name'] );
		$field_type = 'package-string-' . $string_data['package_id'] . '-' . $string_data['id'];
		$this->wpdb->delete( $this->wpdb->prefix . 'icl_translate', array( 'field_type' => $field_type ), array( '%s' ) );
	}

	private function get_package_id( $package_data ) {
		$package_id = false;
		$sql_to_get_package_id = $this->wpdb->prepare( "SELECT s.ID
		FROM {$this->wpdb->prefix}icl_string_packages s
		WHERE s.kind=%s AND s.name=%s AND s.title=%s AND s.post_id=%s",
			$package_data['kind'], $package_data['name'], $package_data['title'], $package_data['post_id'] );
		$result = $this->wpdb->get_row( $sql_to_get_package_id );
		if ( $result ) {
			$package_id = $result->ID;
		}

		return $package_id;
	}
}
