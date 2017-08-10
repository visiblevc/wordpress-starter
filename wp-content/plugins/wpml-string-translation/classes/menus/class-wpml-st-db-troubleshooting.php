<?php

class WPML_ST_DB_Troubleshooting extends WPML_Templates_Factory {

	public function add_hooks() {
		add_action( 'after_setup_complete_troubleshooting_functions', array( $this, 'show' ), 10, 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_required_js' ) );
	}

	public function add_required_js() {
		wp_register_script(
			'wpml-st-db-troubleshooting', WPML_ST_URL . '/res/js/troubleshooting.js',
			array( 'jquery', 'wp-util', 'jquery-ui-sortable', 'jquery-ui-dialog' )
		);
		wp_enqueue_script( 'wpml-st-db-troubleshooting' );
	}

	public function get_template() {
		return 'st-db-cache-tables.twig';
	}

	protected function init_template_base_dir() {
		$this->template_paths = array(
			WPML_ST_PATH . '/templates/troubleshooting/',
		);
	}

	public function get_model() {
		return array(
			'buttonLabel' => __( 'Recreate ST DB cache tables', 'sitepress' ),
			'description' => __( 'Recreate String Translation cache tables when they are missing or are invalid.', 'sitepress' ),
			'successMsg'  => esc_js( __( 'Done', 'sitepress' ) ),
			'nonce'       => wp_create_nonce( 'wpml-st-upgrade-db-cache-command-nonce' ),
		);
	}
}