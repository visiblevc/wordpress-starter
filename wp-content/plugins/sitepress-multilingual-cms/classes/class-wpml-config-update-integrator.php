<?php

class WPML_Config_Update_Integrator {
	/**
	 * @var WPML_Config_Update
	 */
	private $worker;

	/**
	 * @param WPML_Config_Update|null $worker
	 */
	public function __construct( WPML_Config_Update $worker = null ) {
		$this->worker = $worker;
	}

	/**
	 * @return WPML_Config_Update
	 */
	public function get_worker() {
		if ( null === $this->worker ) {
			global $sitepress;
			$http = new WP_Http();
			$this->worker = new WPML_Config_Update( $sitepress, $http );
		}

		return $this->worker;
	}

	/**
	 * @param WPML_Config_Update $worker
	 */
	public function set_worker( WPML_Config_Update $worker ) {
		$this->worker = $worker;
	}

	public function add_hooks() {
		add_action( 'update_wpml_config_index', array( $this, 'update_event_cron' ) );
		add_action( 'wp_ajax_update_wpml_config_index', array( $this, 'update_event_ajax' ) );
		add_action( 'after_switch_theme', array( $this, 'update_event' ) );
		add_action( 'activated_plugin', array( $this, 'update_event' ) );
		add_action( 'wpml_setup_completed', array( $this, 'update_event' ) );

		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete_event' ), 10 );
	}

	public function update_event() {
		$this->get_worker()->run();
	}


	public function upgrader_process_complete_event() {
		$this->get_worker()->run();
	}

	public function update_event_ajax() {
		if ( $this->get_worker()->run() ) {
			echo date( 'F j, Y H:i a', time() );
		}

		die;
	}
	
	public function update_event_cron() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->update_event();
	}
}