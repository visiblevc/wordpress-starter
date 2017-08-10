<?php

class WPML_Custom_Field_Setting_Factory extends WPML_TM_User {
	public $show_system_fields = false;

	/**
	 * @param  string $meta_key
	 *
	 * @return WPML_Post_Custom_Field_Setting
	 */
	public function post_meta_setting( $meta_key ) {

		return new WPML_Post_Custom_Field_Setting( $this->tm_instance, $meta_key );
	}

	/**
	 * @param  string $meta_key
	 *
	 * @return WPML_Term_Custom_Field_Setting
	 */
	public function term_meta_setting( $meta_key ) {

		return new WPML_Term_Custom_Field_Setting( $this->tm_instance, $meta_key );
	}

	/**
	 * Returns all custom field names for which a site has either a setting
	 * in the TM settings or that can be found on any post.
	 *
	 * @return string[]
	 */
	public function get_post_meta_keys() {
		return $this->filter_custom_field_keys( $this->tm_instance->initial_custom_field_translate_states() );
	}

	/**
	 * Returns all term custom field names for which a site has either a setting
	 * in the TM settings or that can be found on any term.
	 *
	 * @return string[]
	 */
	public function get_term_meta_keys() {
		return $this->filter_custom_field_keys( $this->tm_instance->initial_term_custom_field_translate_states() );
	}

	private function filter_custom_field_key( $custom_fields_key ) {
		return $this->show_system_fields || '_' !== substr( $custom_fields_key, 0, 1 );
	}

	/**
	 * @param array $keys
	 *
	 * @return array
	 */
	public function filter_custom_field_keys( $keys ) {
		return array_filter( $keys, array( $this, 'filter_custom_field_key' ) );
	}

}