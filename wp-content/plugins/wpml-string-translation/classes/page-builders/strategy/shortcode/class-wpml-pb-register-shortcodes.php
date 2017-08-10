<?php

/**
 * Class WPML_PB_Register_Shortcodes
 */
class WPML_PB_Register_Shortcodes {

	private $handle_strings;
	/** @var  WPML_PB_Shortcode_Strategy $shortcode_strategy */
	private $shortcode_strategy;
	/** @var  SitePress $sitepress */
	private $sitepress;
	private $existing_package_strings;

	/**
	 * WPML_Add_Wrapper_Shortcodes constructor.
	 *
	 * @param SitePress $sitepress
	 * @param WPML_PB_String_Registration $handle_strings
	 */
	public function __construct( SitePress $sitepress, WPML_PB_String_Registration $handle_strings, WPML_PB_Shortcode_Strategy $shortcode_strategy ) {
		$this->sitepress      = $sitepress;
		$this->handle_strings = $handle_strings;
		$this->shortcode_strategy = $shortcode_strategy;
	}

	public function register_shortcode_strings( $post_id, $content ) {
		$shortcode_parser               = $this->shortcode_strategy->get_shortcode_parser();
		$shortcodes                     = $shortcode_parser->get_shortcodes( $content );
		$this->existing_package_strings = $this->shortcode_strategy->get_package_strings( $this->shortcode_strategy->get_package_key( $post_id ) );

		foreach ( $shortcodes as $shortcode ) {
			$shortcode_content = $shortcode['content'];
			$encoding          = $this->shortcode_strategy->get_shortcode_tag_encoding( $shortcode['tag'] );
			$shortcode_content = $this->decode( $shortcode_content, $encoding );
			$this->remove_from_clean_up_list( $shortcode_content );

			$string_id    = $this->handle_strings->get_string_id_from_package( $post_id, $shortcode_content );
			$string_title = $this->get_updated_shortcode_string_title( $string_id, $shortcode, 'content' );
			$this->handle_strings->register_string( $post_id, $shortcode_content, 'VISUAL', $string_title );

			$attributes              = (array) shortcode_parse_atts( $shortcode['attributes'] );
			$translatable_attributes = $this->shortcode_strategy->get_shortcode_attributes( $shortcode['tag'] );
			if ( ! empty( $attributes ) ) {
				foreach ( $attributes as $attr => $attr_value ) {
					if ( in_array( $attr, $translatable_attributes, true ) ) {
						$encoding   = $this->shortcode_strategy->get_shortcode_attribute_encoding( $shortcode['tag'], $attr );
						$attr_value = $this->decode( $attr_value, $encoding );
						$this->remove_from_clean_up_list( $attr_value );

						$string_id    = $this->handle_strings->get_string_id_from_package( $post_id, $attr_value );
						$string_title = $this->get_updated_shortcode_string_title( $string_id, $shortcode, $attr );
						$this->handle_strings->register_string( $post_id, $attr_value, 'LINE', $string_title );
					}
				}
			}
		}
		$this->clean_up_package_leftovers();
	}

	function get_updated_shortcode_string_title( $string_id, $shortcode, $attribute ) {
		$current_title = $this->get_shortcode_string_title( $string_id );

		$current_title_parts = explode( ':', $current_title );
		$current_title_parts = array_map( 'trim', $current_title_parts );

		$shortcode_tag = $shortcode['tag'];
		if ( isset( $current_title_parts[1] ) ) {
			$shortcode_attributes = explode( ',', $current_title_parts[1] );
			$shortcode_attributes = array_map( 'trim', $shortcode_attributes );
		}
		$shortcode_attributes[] = $attribute;
		sort( $shortcode_attributes );
		$shortcode_attributes = array_unique( $shortcode_attributes );

		return $shortcode_tag . ': ' . implode( ', ', $shortcode_attributes );
	}

	function get_shortcode_string_title( $string_id ) {
		return $this->handle_strings->get_string_title( $string_id );
	}

	private function remove_from_clean_up_list( $value ) {
		$hash_value = md5( $value );
		if ( isset( $this->existing_package_strings[ $hash_value ] ) ) {
			unset( $this->existing_package_strings[ $hash_value ] );
		}
	}

	private function decode( $string, $encoding ) {
		switch ( $encoding ) {
			case 'base64':
				$string = rawurldecode( base64_decode( strip_tags( $string ) ) );
				break;
		}

		return $string;
	}

	private function clean_up_package_leftovers() {
		if ( ! empty( $this->existing_package_strings ) ) {
			foreach ( $this->existing_package_strings as $string_data ) {
				$this->shortcode_strategy->remove_string( $string_data );
			}
		}
	}
}
