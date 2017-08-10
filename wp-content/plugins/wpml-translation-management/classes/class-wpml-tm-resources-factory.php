<?php

abstract class WPML_TM_Resources_Factory {
	protected $ajax_actions;
	/**
	 * @var WPML_WP_API
	 */
	protected $wpml_wp_api;

	/**
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( &$wpml_wp_api ) {
		$this->wpml_wp_api = &$wpml_wp_api;
		add_action( 'admin_enqueue_scripts', array( $this, 'register_resources' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_resources' ), 20 );
	}

	public abstract function enqueue_resources( $hook_suffix );
	public abstract function register_resources( $hook_suffix );
}