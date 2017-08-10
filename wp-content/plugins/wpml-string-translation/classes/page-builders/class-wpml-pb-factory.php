<?php

class WPML_PB_Factory {

	private $wpdb;
	private $sitepress;
	private $string_translations = array();

	public function __construct( $wpdb, $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	public function get_wpml_package( $package_id ) {
		return new WPML_Package( $package_id );
	}

	public function get_string_translations( IWPML_PB_Strategy $strategy ) {
		$kind = $strategy->get_package_kind();
		if ( ! array_key_exists( $kind, $this->string_translations ) ) {
			$this->string_translations[ $kind ] = new WPML_PB_String_Translation( $this->wpdb, $this, $strategy );
		}
		return $this->string_translations[ $kind ];
	}

	public function get_shortcode_parser( WPML_PB_Shortcode_Strategy $strategy ) {
		return new WPML_PB_Shortcodes( $strategy );
	}

	public function get_register_shortcodes( WPML_PB_Shortcode_Strategy $strategy ) {
		return new WPML_PB_Register_Shortcodes( $this->sitepress, new WPML_PB_String_Registration( $strategy ), $strategy );
	}

	public function get_update_post( $package_data, IWPML_PB_Strategy $strategy ) {
		return new WPML_PB_Update_Post( $this->wpdb, $this->sitepress, $package_data, $strategy );
	}

	public function get_shortcode_content_updater( IWPML_PB_Strategy $strategy ) {
		return new WPML_PB_Update_Shortcodes_In_Content( $strategy );
	}

	public function get_api_hooks_content_updater( IWPML_PB_Strategy $strategy ) {
		return new WPML_PB_Update_API_Hooks_In_Content( $strategy );
	}
}
