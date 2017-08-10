<?php

class WPML_Custom_Field_Editor_Settings {

	private $settings_factory;
	private $custom_field;

	public function __construct( $custom_field, $tm_instance ) {
		$this->custom_field     = substr( $custom_field, 6 );
		$this->settings_factory = new WPML_Custom_Field_Setting_Factory( $tm_instance );
	}

	public function filter_name( $name ) {

		$filtered_name = $this->settings_factory->post_meta_setting( $this->custom_field )->get_editor_label();

		return $filtered_name ? $filtered_name : $name;
	}

	public function filter_style( $style ) {
		$filtered_style = $this->settings_factory->post_meta_setting( $this->custom_field )->get_editor_style();
		switch ( $filtered_style ) {
			case 'line':
				return 0;
			case 'textarea':
				return 1;
			case 'visual':
				return 2;
		}

		return $style;
	}

	public function get_group() {
		return $this->settings_factory->post_meta_setting( $this->custom_field )->get_editor_group();
	}
}

	