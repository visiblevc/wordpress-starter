<?php

class WPML_Active_Plugin_Provider {
	/** @var  WPML_WP_API $wp_api */
	private $wp_api;

	public function __construct( WPML_WP_API $wp_api ) {
		$this->wp_api = $wp_api;
	}

	/**
	 * @return array
	 */
	public function get_active_plugins() {
		$active_plugin_names = array();
		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			if ( is_plugin_active( $plugin_file ) ) {
				$active_plugin_names[] = $plugin_data;
			}
		}

		return $active_plugin_names;
	}

	/**
	 * @return array
	 */
	public function get_active_plugin_names() {
		return wp_list_pluck( $this->get_active_plugins(), 'Name' );
	}
}