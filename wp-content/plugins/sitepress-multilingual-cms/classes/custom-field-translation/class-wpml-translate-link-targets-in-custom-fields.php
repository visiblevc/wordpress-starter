<?php

class WPML_Translate_Link_Targets_In_Custom_Fields extends WPML_Translate_Link_Targets {

	/* @var TranslationManagement $tm_instance */
	private $tm_instance;
	/* @var WPML_WP_API $wp_api */
	private $wp_api;
	/* @var array $meta_keys */
	private $meta_keys;
	
	/**
	 * WPML_Translate_Link_Targets_In_Custom_Fields constructor.
	 *
	 * @param TranslationManagement $tm_instance
	 * @param WPML_WP_API $wp_api
	 * @param AbsoluteLinks $absolute_links
	 * @param WPML_Absolute_To_Permalinks $permalinks_converter
	 */
	public function __construct( &$tm_instance, &$wp_api, $absolute_links, $permalinks_converter ) {
		parent::__construct( $absolute_links, $permalinks_converter );
		$this->tm_instance = &$tm_instance;
		$this->wp_api      = &$wp_api;

		$this->tm_instance->load_settings_if_required();
		if ( isset( $this->tm_instance->settings[ 'custom_fields_translate_link_target' ] ) &&
				! empty( $this->tm_instance->settings[ 'custom_fields_translate_link_target' ] ) ) {
			
			$this->meta_keys = $this->tm_instance->settings[ 'custom_fields_translate_link_target' ];
		}
	}
	
	public function has_meta_keys() {
		return (bool) $this->meta_keys;
	}

	/**
	 * maybe_translate_link_targets
	 * 
     * @param string|array $metadata - Always null for post metadata.
     * @param int $object_id - Post ID for post metadata
     * @param string $meta_key - metadata key.
     * @param bool $single - Indicates if processing only a single $metadata value or array of values.
     * @return Original or Modified $metadata.
     */	
	public function maybe_translate_link_targets( $metadata, $object_id, $meta_key, $single ) {
		
		if ( array_key_exists( $meta_key, $this->meta_keys ) ) {
			$custom_field_setting = new WPML_Post_Custom_Field_Setting( $this->tm_instance, $meta_key );
			if ( $custom_field_setting->is_translate_link_target() ) {
	
				$this->wp_api->remove_filter( 'get_post_metadata', array( $this, 'maybe_translate_link_targets' ), 10 );
				$metadata = maybe_unserialize( $this->wp_api->get_post_meta( $object_id, $meta_key, $single ) );
				$this->wp_api->add_filter( 'get_post_metadata', array( $this, 'maybe_translate_link_targets' ), 10, 4 );
				if ( $metadata ) {
					$sub_fields = $custom_field_setting->get_translate_link_target_sub_fields();
					if ( ! empty( $sub_fields ) ) {
						foreach ( $sub_fields as $sub_field ) {
							if ( isset( $sub_field['value'] ) && isset( $sub_field['attr']['translate_link_target'] ) && $sub_field['attr']['translate_link_target'] ) {
								$key = trim( $sub_field[ 'value' ] );
								if ( isset( $metadata[ $key ] ) ) {
									$metadata[ $key ] = $this->convert_text( $metadata[ $key ] );
								}
							}
						}
					} else {
						$metadata = $this->convert_text( $metadata );
					}

					if ( $single ) {
						$metadata[0] = $metadata;
					}
				}
			}
		}
		return $metadata;
	}
}

