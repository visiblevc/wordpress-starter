<?php

class WPML_Color_Picker {
	private $color_selector_item;

	public function __construct( $color_selector_item ) {
		$this->color_selector_item = $color_selector_item;

		add_action( 'admin_footer', array( $this, 'admin_print_scripts' ) );
	}

	public function admin_print_scripts() {
		wp_register_style( 'wpml-color-picker', ICL_PLUGIN_URL . '/res/css/colorpicker.css', array( 'wp-color-picker' ), ICL_SITEPRESS_VERSION );
		wp_register_script( 'wpml-color-picker', ICL_PLUGIN_URL . '/res/js/wpml-color-picker.js', array( 'wp-color-picker' ), ICL_SITEPRESS_VERSION );

		wp_enqueue_style( 'wpml-color-picker' );
		wp_enqueue_script( 'wpml-color-picker' );
	}

	public function current_language_color_selector_control() {
		echo $this->get_current_language_color_selector_control();
	}

	public function get_current_language_color_selector_control() {
		$args          = $this->color_selector_item;
		$label         = isset( $args['label'] ) ? $args['label'] : '';
		$color_default = $args['default'];
		$color_value   = isset( $args['value'] ) ? $args['value'] : $color_default;
		$input_size    = isset( $args['size'] ) ? $args['size'] : 7;

		$input_name = $args['input_name_group'] . '[' . $args['input_name_id'] . ']';

		$input_id = str_replace( array( ']', '[', '_' ), array( '', '-', '-' ), $input_name );

		$input_label = '';
		$input       = '';
		if ( $label ) {
			$input_label .= '<label for="' . esc_attr( $input_id ) . '">' . esc_html( $label ) . '</label><br />';
		} else {
			$input_label .= '<label for="' . esc_attr( $input_id ) . '" style="display: none;"></label>';
		}
		$input .= '<input class="wpml-colorpicker wp-color-picker-field" type="text"';
		$input_attributes['size']               = $input_size;
		$input_attributes['id']                 = $input_id;
		$input_attributes['name']               = $input_name;
		$input_attributes['value']              = $color_value;
		$input_attributes['data-default-color'] = $color_default;

		foreach ( $input_attributes as $key => $value ) {
			$input .= $key . '="' . esc_attr( $value ) . '" ';
		}

		$input .= '/>';

		return $input_label . $input;
	}
}