<?php

class WPML_TM_Post extends WPML_TM_Translatable_Element {
	private $element_type;

	/**
	 * @var wpdb
	 */
	private $wpdb;
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var array|null|WP_Post
	 */
	private $wp_post;

	public function __construct( $id, &$sitepress, &$wpdb ) {
		parent::__construct( $id );

		$this->wpdb          = &$wpdb;
		$this->sitepress     = &$sitepress;
		$this->wp_post       = get_post( $id );
		$this->element_type  = 'post_' . $this->wp_post->post_type;
		$this->language_code = $this->sitepress->get_language_for_element( $id, $this->element_type );
	}

	public function get_words_count() {
		$count = $this->estimate_word_count();
		$count += $this->estimate_custom_field_word_count();

		return $count;
	}

	private function estimate_custom_field_word_count() {
		$sitepress_settings = $this->sitepress->get_settings();
		if ( ! isset( $this->language_code ) || ! isset( $this->id ) || ! $this->is_registered_type() ) {
			return 0;
		}

		$words   = 0;
		$post_id = $this->id;

		if ( ! empty( $sitepress_settings['translation-management']['custom_fields_translation'] )
		     && is_array( $sitepress_settings['translation-management']['custom_fields_translation'] )
		) {
			$custom_fields = array();
			foreach ( $sitepress_settings['translation-management']['custom_fields_translation'] as $cf => $op ) {
				if ( WPML_TRANSLATE_CUSTOM_FIELD === (int) $op ) {
					$custom_fields[] = $cf;
				}
			}
			foreach ( $custom_fields as $cf ) {
				$custom_fields_value = get_post_meta( $post_id, $cf );
				if ( $custom_fields_value && is_scalar( $custom_fields_value ) ) {
					// only support scalar values fo rnow
					$words += $this->get_string_words_count( $this->language_code, $custom_fields_value );
				} else {
					foreach ( $custom_fields_value as $custom_fields_value_item ) {
						if ( $custom_fields_value_item && is_scalar( $custom_fields_value_item ) ) {
							// only support scalar values fo rnow
							$words += $this->get_string_words_count( $this->language_code, $custom_fields_value_item );
						}
					}
				}
			}
		}

		return (int) $words;
	}

	private function is_registered_type() {
		$post_types = get_post_types();

		return in_array( $this->wp_post->post_type, $post_types );
	}

	private function estimate_word_count() {
		$words = 0;
		if ( isset( $this->language_code ) && $this->wp_post ) {
			$words += $this->get_string_words_count( $this->language_code, $this->wp_post->post_title );
			$words += $this->get_string_words_count( $this->language_code, $this->wp_post->post_content );
			$words += $this->get_string_words_count( $this->language_code, $this->wp_post->post_excerpt );
			$words += $this->get_string_words_count( $this->language_code, $this->wp_post->post_name );
		}

		return $words;
	}

	public function get_type_name( $label = null ) {
		$post_type = $this->wp_post->post_type;

		$post_type_label = ucfirst( $post_type );

		$post_type_object = get_post_type_object( $post_type );

		if ( isset( $post_type_object ) ) {
			$post_type_object_item = $post_type_object;
			$temp_post_type_label  = '';
			if ( isset( $post_type_object_item->labels->$label ) ) {
				$temp_post_type_label = $post_type_object_item->labels->$label;
			}
			if ( trim( $temp_post_type_label ) == '' ) {
				if ( isset( $post_type_object_item->labels->singular_name ) ) {
					$temp_post_type_label = $post_type_object_item->labels->singular_name;
				} elseif ( $label && $post_type_object_item->labels->name ) {
					$temp_post_type_label = $post_type_object_item->labels->name;
				}
			}
			if ( trim( $temp_post_type_label ) != '' ) {
				$post_type_label = $temp_post_type_label;
			}
		}

		return $post_type_label;
	}
}