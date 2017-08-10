<?php

class WPML_PB_Config_Import_Shortcode {

	const PB_SHORTCODE_SETTING = 'pb_shortcode';

	/** @var  WPML_ST_Settings $st_settings */
	private $st_settings;

	public function __construct( WPML_ST_Settings $st_settings ) {
		$this->st_settings = $st_settings;
	}

	public function add_hooks() {
		add_filter( 'wpml_config_array', array( $this, 'wpml_config_filter' ) );
	}

	public function wpml_config_filter( $config_data ) {
		$old_shortcode_data = $this->get_settings();

		$shortcode_data = array();
		if ( isset ( $config_data['wpml-config']['shortcodes']['shortcode'] ) ) {
			foreach ( $config_data['wpml-config']['shortcodes']['shortcode'] as $data ) {
				$attributes = array();
				if ( isset( $data['attributes']['attribute'] ) ) {
					$single_attribute = false;
					foreach ( $data['attributes']['attribute'] as $attribute ) {
						if ( is_string( $attribute ) ) {
							$single_attribute   = true;
							$attribute_value    = $attribute;
							$attribute_encoding = '';
						} else if ( isset( $attribute['value'] ) ) {
							$attribute_value = $attribute['value'];
						}
						if ( $attribute_value ) {
							if ( $single_attribute ) {
								if ( isset( $attribute['encoding'] ) ) {
									$attribute_encoding = $attribute['encoding'];
								}
							} else {
								$attribute_encoding = isset( $attribute['attr']['encoding'] ) ? $attribute['attr']['encoding'] : '';
								$attributes[]       = array(
									'value'    => $attribute_value,
									'encoding' => $attribute_encoding
								);
							}
						}
					}
					if ( $single_attribute ) {
						$attributes[] = array(
							'value'    => $attribute_value,
							'encoding' => $attribute_encoding
						);
					}
				}
				$shortcode_data[] = array(
					'tag'        => array(
						'value'    => $data['tag']['value'],
						'encoding' => isset( $data['tag']['attr']['encoding'] ) ? $data['tag']['attr']['encoding'] : '',
					),
					'attributes' => $attributes,
				);
			}
		}

		if ( $shortcode_data != $old_shortcode_data ) {
			$this->st_settings->update_setting( self::PB_SHORTCODE_SETTING, $shortcode_data, true );
		}

		return $config_data;
	}

	public function get_settings() {
		return $this->st_settings->get_setting( self::PB_SHORTCODE_SETTING );
	}

	public function has_settings() {
		$settings = $this->get_settings();

		return ! empty( $settings );
	}
}
