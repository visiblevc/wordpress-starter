<?php

class WPML_Custom_Field_XML_Settings_Import extends WPML_WPDB_User {

	/** @var  WPML_Custom_Field_Setting_Factory $setting_factory */
	private $setting_factory;
	/** @var  array $settings_array */
	private $settings_array;

	/**
	 * WPML_Custom_Field_XML_Settings_Import constructor.
	 *
	 * @param wpdb                              $wpdb
	 * @param WPML_Custom_Field_Setting_Factory $setting_factory
	 * @param array                             $settings_array
	 */
	public function __construct( &$wpdb, &$setting_factory, $settings_array ) {
		parent::__construct( $wpdb );
		$this->setting_factory = &$setting_factory;
		$this->settings_array  = $settings_array;
	}

	/**
	 * Runs the actual import of the xml
	 */
	public function run() {
		$config = $this->settings_array;
		foreach (
			array(
				'post_meta_setting' => array(
					WPML_POST_META_CONFIG_INDEX_PLURAL,
					WPML_POST_META_CONFIG_INDEX_SINGULAR
				),
				'term_meta_setting' => array(
					WPML_TERM_META_CONFIG_INDEX_PLURAL,
					WPML_TERM_META_CONFIG_INDEX_SINGULAR
				)
			) as $setting_constructor => $settings
		) {
			if ( ! empty( $config[ $settings[0] ] ) ) {
				$field = $config[ $settings[0] ][ $settings[1] ];
				$cf    = ! is_numeric( key( current( $config[ $settings[0] ] ) ) ) ? array( $field ) : $field;
				foreach ( $cf as $c ) {
					$setting = call_user_func_array( array(
						$this->setting_factory,
						$setting_constructor
					), array( trim( $c['value'] ) ) );
					$this->import_action( $c, $setting );
					if ( $c['attr']['action'] === 'translate' ) {
						$setting->set_to_translatable();
					} elseif ( $c['attr']['action'] === 'copy' ) {
						$setting->set_to_copy();
					} else {
						$setting->set_to_nothing();
					}
					$setting->make_read_only();
					$this->import_editor_settings( $c, $setting );
					if ( isset( $c[ 'attr' ][ 'translate_link_target' ] ) || isset( $c[ 'custom-field' ] ) ) {
						$setting->set_translate_link_target( isset( $c[ 'attr' ][ 'translate_link_target' ] ) ? (bool) $c[ 'attr' ][ 'translate_link_target' ] : false, isset( $c[ 'custom-field' ] ) ? $c[ 'custom-field' ] : array() );
					}
					if ( isset( $c[ 'attr' ][ 'convert_to_sticky' ] ) ) {
						$setting->set_convert_to_sticky( (bool) $c[ 'attr' ][ 'convert_to_sticky' ] );
					}
				}
			}
		}
	}
	
	private function import_action( $c, $setting ) {
		if ( $c['attr']['action'] === 'translate' ) {
			$setting->set_to_translatable();
		} elseif ( $c['attr']['action'] === 'copy' ) {
			$setting->set_to_copy();
		} else {
			$setting->set_to_nothing();
		}
	}
	
	private function import_editor_settings( $c, $setting ) {
		if ( isset( $c[ 'attr' ][ 'style' ] ) ) {
			$setting->set_editor_style( $c[ 'attr' ][ 'style' ] );
		}
		if ( isset( $c[ 'attr' ][ 'label' ] ) ) {
			$setting->set_editor_label( $c[ 'attr' ][ 'label' ] );
		}					
		if ( isset( $c[ 'attr' ][ 'group' ] ) ) {
			$setting->set_editor_group( $c[ 'attr' ][ 'group' ] );
		}					
	}
}