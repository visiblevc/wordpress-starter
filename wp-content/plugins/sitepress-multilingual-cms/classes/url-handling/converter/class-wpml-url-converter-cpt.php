<?php

class WPML_URL_Converter_CPT {
	/**
	 * @var WPML_Slash_Management
	 */
	private $slash_helper;

	/**
	 * @param WPML_Slash_Management $slash_helper
	 */
	public function __construct( WPML_Slash_Management $slash_helper = null ) {
		if ( ! $slash_helper ) {
			$slash_helper = new WPML_Slash_Management();
		}
		$this->slash_helper = $slash_helper;
	}

	/**
	 * Adjusts the CPT archive slug for possible slug translations from ST.
	 *
	 * @param string $link
	 * @param string $post_type
	 * @param null|string $language_code
	 *
	 * @return string
	 */
	public function adjust_cpt_slug_in_url( $link, $post_type, $language_code = null ) {

		$post_type_object = get_post_type_object( $post_type );

		if ( isset( $post_type_object->rewrite ) ) {
			$slug = trim( $post_type_object->rewrite['slug'], '/' );
		} else {
			$slug = $post_type_object->name;
		}

		$translated_slug = apply_filters( 'wpml_get_translated_slug', $slug, $post_type, $language_code );

		if ( is_string( $translated_slug ) ) {
			$link_parts = explode( '?', $link, 2 );

			$pattern = '#\/' . preg_quote( $slug, '#' ) . '\/#';
			$link_new = trailingslashit( preg_replace( $pattern, '/' . $translated_slug . '/', trailingslashit( $link_parts[0] ), 1 ) );
			$link = $this->slash_helper->match_trailing_slash_to_reference( $link_new, $link_parts[0] );
			$link = isset( $link_parts[1] ) ? $link . '?' . $link_parts[1] : $link;
		}

		return $link;
	}
}