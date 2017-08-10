<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Post_Element extends WPML_Translation_Element implements WPML_Duplicable_Element {
	/**
	 * @return WP_Post
	 */
	function get_wp_object() {
		return get_post( $this->id );
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	function get_type( $post = null ) {
		if ( $post ) {
			return $post->post_type;
		}

		return $this->get_wp_object()->post_type;
	}

	function get_wpml_element_type() {
		return 'post_' . $this->get_wp_element_type();
	}

	function get_element_id() {
		return $this->id;
	}

	/**
	 * @param null|stdClass $element_data null, or a standard object containing at least the `translation_id`, `language_code`, `element_id`, `source_language_code`, `element_type`, and `original` properties.
	 *
	 * @return WPML_Post_Element
	 * @throws \InvalidArgumentException
	 */
	function get_new_instance( $element_data ) {
		return new WPML_Post_Element( $element_data->element_id, $this->sitepress, $this->wpml_cache );
	}

	function is_translatable() {
		return $this->sitepress->is_translated_post_type( $this->get_wp_element_type() );
	}
}
