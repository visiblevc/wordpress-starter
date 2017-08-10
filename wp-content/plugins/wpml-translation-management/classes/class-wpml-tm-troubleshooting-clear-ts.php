<?php

class WPML_TM_Troubleshooting_Clear_TS extends WPML_TM_AJAX_Factory_Obsolete {
	private $script_handle = 'wpml_clear_ts';

	/**
	 * WPML_TM_Troubleshooting_Clear_TS constructor.
	 *
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( &$wpml_wp_api ) {
		parent::__construct( $wpml_wp_api );

		add_action( 'init', array( $this, 'load_action' ) );

		$this->add_ajax_action( 'wp_ajax_wpml_clear_ts', array( $this, 'clear_ts_action' ) );
		$this->init();
	}

	public function clear_ts_action() {
		$action              = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		$wpml_clear_ts_nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$valid_nonce         = wp_verify_nonce( $wpml_clear_ts_nonce, $action );
		if ( $valid_nonce && isset( $_POST ) && $_POST ) {
			$this->clear_tp_default_suid();
			return $this->wpml_wp_api->wp_send_json_success( __( 'Ok!', 'wpml-translation-management' ) );
		} else {
			return $this->wpml_wp_api->wp_send_json_error( __( "You can't do that!", 'wpml-translation-management' ) );
		}
	}

	protected function clear_tp_default_suid() {
		TranslationProxy::clear_preferred_translation_service();
	}

	public function enqueue_resources( $hook_suffix ) {
		if ( $this->wpml_wp_api->is_troubleshooting_page() ) {
			$this->register_resources();
			$strings = array(
				'placeHolder' => $this->script_handle,
				'action'      => $this->script_handle,
				'nonce'       => wp_create_nonce( $this->script_handle ),
			);
			wp_localize_script( $this->script_handle, $this->script_handle . '_strings', $strings );
			wp_enqueue_script( $this->script_handle );
		}
	}

	public function register_resources() {
		wp_register_script( $this->script_handle, WPML_TM_URL . '/res/js/clear-preferred-ts.js', array( 'jquery', 'jquery-ui-dialog' ), false, true );
	}

	public function load_action() {
		$page           = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		$should_proceed = ! $this->wpml_wp_api->is_heartbeat() && ! $this->wpml_wp_api->is_ajax() && ! $this->wpml_wp_api->is_cron_job() && strpos( $page, 'sitepress-multilingual-cms/menu/troubleshooting.php' ) === 0;

		if ( $should_proceed && TranslationProxy::get_tp_default_suid() ) {
			$this->add_hooks();
		}
	}

	private function add_hooks() {
		add_action( 'after_setup_complete_troubleshooting_functions', array( $this, 'render_ui' ) );
	}

	public function render_ui() {
		if ( TranslationProxy::get_tp_default_suid() ) {
			$clear_ts = new WPML_TM_Troubleshooting_Clear_TS_UI();
			$clear_ts->show();
		}
	}
}