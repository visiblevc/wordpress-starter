<?php

class WPML_ST_Label_Translation {

	function init() {
		add_filter( 'wpml_label_translation_data', array( $this, 'get_label_translations' ), 10, 2 );
		add_action( 'wp_ajax_wpml_tt_save_labels_translation', array( $this, 'save_label_translations' ) );
	}

	/**
	 * @param        $false
	 * @param string $taxonomy
	 *
	 * @return array|bool
	 */
	function get_label_translations( $false, $taxonomy ) {
		global $sitepress, $wpdb;
		$return          = false;
		$taxonomy_object = get_taxonomy( $taxonomy );

		// Careful index checking here, otherwise some of those private taxonomies used by WooCommerce will result in errors here.
		if ( $taxonomy_object
			 && isset( $taxonomy_object->label )
			 && isset( $taxonomy_object->labels )
			 && isset( $taxonomy_object->labels->singular_name )
		) {
			$label          = $taxonomy_object->label;
			$singular_label = $taxonomy_object->labels->singular_name;

			$return = $this->build_label_array( $singular_label, $label );
		} else {
			$return[ 'st_default_lang' ] = 'en';
		}

		return $return;
	}

	private function build_label_array( $singular_label, $label ) {
		global $sitepress, $wpdb;

		$return = array();

		$str_name_singular       = 'taxonomy singular name: ' . $singular_label;
		$return[ 'id_singular' ] = icl_get_string_id( $singular_label, 'WordPress', $str_name_singular );
		if ( ! $return[ 'id_singular' ] ) {
			$return[ 'id_singular' ] = icl_register_string( 'WordPress',
															$str_name_singular,
															$singular_label );
		}
		$str_name_general       = 'taxonomy general name: ' . $label;
		$return[ 'id_general' ] = icl_get_string_id( $label, 'WordPress', $str_name_general );
		if ( ! $return[ 'id_general' ] ) {
			$return[ 'id_general' ] = icl_register_string( 'WordPress',
														   $str_name_general,
														   $label );
		}

		$active_lang_codes = array_keys( $sitepress->get_active_languages( true ) );

		foreach ( $active_lang_codes as $language ) {
			$exists_singular  = null;
			$translated_label = icl_translate( 'WordPress',
											   $str_name_singular,
											   $singular_label,
											   false,
											   $exists_singular,
											   $language );
			if ( $exists_singular ) {
				$return [ $language ][ 'singular' ] = $translated_label;
			}
			$exists_plural    = null;
			$translated_label = icl_translate( 'WordPress',
											   $str_name_general,
											   $label,
											   false,
											   $exists_plural,
											   $language );
			if ( $exists_plural ) {
				$return [ $language ][ 'general' ] = $translated_label;
			}
		}
		
		$string = new WPML_ST_String( $return[ 'id_singular' ], $wpdb );
		$return[ $string->get_language() ] = array(
				'singular' => $singular_label,
				'general'  => $label,
				'original' => true
				);
		
		$return[ 'st_default_lang' ] = $string->get_language();

		return $return;
	}

	/**
	 * Ajax handler for saving label translations from the WPML Taxonomy Translations menu.
	 */
	public function save_label_translations() {
		if ( ! wpml_is_action_authenticated( 'wpml_tt_save_labels_translation' ) ) {
			wp_send_json_error( 'Wrong Nonce' );
		}

		$general  = isset( $_POST[ 'plural' ] ) ? sanitize_text_field( $_POST[ 'plural' ] ) : false;
		$singular = isset( $_POST[ 'singular' ] ) ? sanitize_text_field( $_POST[ 'singular' ] ) : false;
		$taxonomy = isset( $_POST[ 'taxonomy' ] ) ? sanitize_text_field( $_POST[ 'taxonomy' ] ) : false;
		$language = isset( $_POST[ 'taxonomy_language_code' ] ) ? sanitize_text_field( $_POST[ 'taxonomy_language_code' ] ): false;

		if ( $singular && $general && $taxonomy && $language ) {

			$tax_label_data = $this->get_label_translations( false, $taxonomy );

			if ( isset( $tax_label_data[ 'id_singular' ] )
				 && $tax_label_data[ 'id_singular' ]
				 && isset( $tax_label_data[ 'id_general' ] )
				 && $tax_label_data[ 'id_general' ]
			) {

				$original_id_singular = $tax_label_data[ 'id_singular' ];
				$original_id_plural   = $tax_label_data[ 'id_general' ];

				icl_add_string_translation( $original_id_singular, $language, $singular, ICL_TM_COMPLETE );
				$singular_result = (string) icl_get_string_by_id( $original_id_singular, $language );

				icl_add_string_translation( $original_id_plural, $language, $general, ICL_TM_COMPLETE );
				$plural_result = (string) icl_get_string_by_id( $original_id_plural, $language );

				if ( $singular_result && $plural_result ) {
					$result = array(
						'singular' => $singular_result,
						'general'  => $plural_result,
						'lang'     => $language
					);

					wp_send_json_success( $result );
				}
			}
		}

		wp_send_json_error();
	}
}