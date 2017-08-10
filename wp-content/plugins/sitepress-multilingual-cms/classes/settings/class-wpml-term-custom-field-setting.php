<?php

class WPML_Term_Custom_Field_Setting extends WPML_Custom_Field_Setting {

	/**
	 * @return string
	 */
	protected function get_state_array_setting_index() {

		return WPML_TERM_META_SETTING_INDEX_PLURAL;
	}

	/**
	 * @return string
	 */
	protected function get_read_only_array_setting_index() {

		return 'custom_term_fields_readonly_config';
	}

	/**
	 * @return string
	 */
	protected function get_editor_style_array_setting_index() {

		return 'custom_term_fields_editor_style';
	}

	protected function get_translate_link_target_array_setting_index() {
		
		return 'custom_term_fields_translate_link_target';
	}

	/**
	 * @return string
	 */
	protected function get_editor_label_array_setting_index() {
		
		return 'custom_term_fields_editor_label';
	}

	/**
	 * @return string
	 */
	protected function get_editor_group_array_setting_index() {
		
		return 'custom_term_fields_editor_group';
	}


	protected function get_convert_to_sticky_array_setting_index() {
		
		return 'custom_term_fields_convert_to_sticky';
	}

	/**
	 * @return  string[]
	 */
	protected function get_excluded_keys() {

		return array();
	}
}