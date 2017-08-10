<?php

abstract class WPML_TM_AJAX_Factory_Obsolete {
	protected $ajax_actions;

	/**
	 * @var WPML_WP_API
	 */
	protected $wpml_wp_api;


	public function __construct( &$wpml_wp_api ) {
		$this->wpml_wp_api = &$wpml_wp_api;
	}

	protected function init() {
		$this->add_ajax_actions();
	}

	protected final function add_ajax_action( $handle, $callback ) {
		$this->ajax_actions[ $handle ] = $callback;
	}

	private final function add_ajax_actions() {
		if ( ! $this->wpml_wp_api->is_cron_job() ) {
			foreach ( $this->ajax_actions as $handle => $callback ) {

				if($this->wpml_wp_api->is_ajax()) {
					if ( stripos( $handle, 'wp_ajax_' ) !== 0 ) {
						$handle = 'wp_ajax_' . $handle;
					}
					add_action( $handle, $callback );
				}
				if ( $this->wpml_wp_api->is_back_end() && $this->ajax_actions ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_resources' ) );
				}
			}
		}
	}

	public abstract function enqueue_resources( $hook_suffix );
}