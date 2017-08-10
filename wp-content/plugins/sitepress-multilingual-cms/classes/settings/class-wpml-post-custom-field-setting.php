<?php

class WPML_Post_Custom_Field_Setting extends WPML_Custom_Field_Setting {

	/**
	 * @return string
	 */
	protected function get_state_array_setting_index() {

		return 'custom_fields_translation';
	}

	/**
	 * @return string
	 */
	protected function get_read_only_array_setting_index() {

		return 'custom_fields_readonly_config';
	}
	
	/**
	 * @return string
	 */
	protected function get_editor_style_array_setting_index() {
		
		return 'custom_fields_editor_style';
	}

	/**
	 * @return string
	 */
	protected function get_editor_label_array_setting_index() {
		
		return 'custom_fields_editor_label';
	}

	/**
	 * @return string
	 */
	protected function get_editor_group_array_setting_index() {
		
		return 'custom_fields_editor_group';
	}

	/**
	 * @return string
	 */
	protected function get_translate_link_target_array_setting_index() {
		
		return 'custom_fields_translate_link_target';
	}

	/**
	 * @return string
	 */
	protected function get_convert_to_sticky_array_setting_index() {
		
		return 'custom_fields_convert_to_sticky';
	}

	/**
	 * @return  string[]
	 */
	protected function get_excluded_keys() {

		return array(
			'_edit_last',
			'_edit_lock',
			'_wp_page_template',
			'_wp_attachment_metadata',
			'_icl_translator_note',
			'_alp_processed',
			'_pingme',
			'_encloseme',
			'_icl_lang_duplicate_of',
			'_wpml_media_duplicate',
			'wpml_media_processed',
			'_wpml_media_featured',
			'_thumbnail_id'
		);
	}
}