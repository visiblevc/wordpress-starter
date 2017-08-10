<?php

class WPML_PB_Update_Shortcodes_In_Content {

	/** @var  WPML_PB_Shortcode_Strategy $strategy */
	private $strategy;

	private $new_content;
	private $string_translations;
	private $lang;

	public function __construct( WPML_PB_Shortcode_Strategy $strategy ) {
		$this->strategy = $strategy;
	}

	public function update( $translated_post_id, $original_post, $string_translations, $lang ) {
		$original_content = $original_post->post_content;
		$new_content      = $this->update_content( $original_content, $string_translations, $lang );
		$translated_post  = get_post( $translated_post_id );
		$current_content  = isset( $translated_post->post_content ) ? $translated_post->post_content : '';

		if ( $new_content != $original_content || '' === $current_content ) {
			wp_update_post( array(
				'ID'           => $translated_post_id,
				'post_content' => $new_content,
			) );
		}
	}

	public function update_content( $original_content, $string_translations, $lang ) {
		$this->new_content         = $original_content;
		$this->string_translations = $string_translations;
		$this->lang                = $lang;

		$shortcode_parser = $this->strategy->get_shortcode_parser();
		$shortcodes       = $shortcode_parser->get_shortcodes( $original_content );

		foreach ( $shortcodes as $shortcode ) {
			$this->update_shortcodes( $shortcode );
			$this->update_shortcode_attributes( $shortcode );
		}

		return $this->new_content;
	}

	private function update_shortcodes( $shortcode_data ) {
		$encoding = $this->strategy->get_shortcode_tag_encoding( $shortcode_data['tag'] );
		$translation = $this->get_translation( $shortcode_data['content'], $encoding );
		$this->replace_string_with_translation( $shortcode_data['block'], $shortcode_data['content'], $translation );
	}

	private function update_shortcode_attributes( $shortcode_data ) {

		$shortcode_attribute = $shortcode_data['attributes'];

		$attributes              = (array) shortcode_parse_atts( $shortcode_attribute );
		$translatable_attributes = $this->strategy->get_shortcode_attributes( $shortcode_data['tag'] );
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attr => $attr_value ) {
				if ( in_array( $attr, $translatable_attributes, true ) ) {
					$encoding            = $this->strategy->get_shortcode_attribute_encoding( $shortcode_data['tag'], $attr );
					$translation = $this->get_translation( $attr_value, $encoding );
					$translation         = $this->filter_attribute_translation( $translation );
					$shortcode_attribute = $this->replace_string_with_translation( $shortcode_attribute, $attr_value, $translation );
				}
			}
		}
	}

	private function replace_string_with_translation( $block, $original, $translation, $is_attribute = false ) {
		$new_block        = $block;
		if ( $translation ) {
			if ( $is_attribute ) {
				$new_block         = preg_replace( '/(["\'])' . $original . '(["\'])/', '${1}' . $translation . '${2}', $block );
			} else {
				$new_block         = str_replace( $original, $translation, $block );
			}
			$this->new_content = str_replace( $block, $new_block, $this->new_content );
		}

		return $new_block;
	}

	private function get_translation( $original, $encoding = false ) {
		$original = $this->decode( $original, $encoding );

		$translation = null;
		$string_name = md5( $original );
		if ( isset( $this->string_translations[ $string_name ][ $this->lang ] ) && $this->string_translations[ $string_name ][ $this->lang ]['status'] == ICL_TM_COMPLETE ) {
			$translation = $this->string_translations[ $string_name ][ $this->lang ]['value'];
		}

		if ( $translation ) {
			$translation = $this->encode( $translation, $encoding );
		}

		return $translation;
	}

	private function decode( $string, $encoding ) {
		switch ( $encoding ) {
			case 'base64':
				$string = rawurldecode( base64_decode( strip_tags( $string ) ) );
				break;
		}

		return $string;
	}

	private function encode( $string, $encoding ) {
		switch ( $encoding ) {
			case 'base64':
				$string = base64_encode( $string );
				break;
		}

		return $string;
	}

	/**
	 * @param $translation
	 *
	 * @return string
	 */
	private function filter_attribute_translation( $translation ) {
		$translation = htmlspecialchars( $translation );
		$translation = str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), $translation );

		return $translation;
	}
}

