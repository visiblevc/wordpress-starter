<?php

class WPML_ST_Reset {
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var WPML_ST_Settings
	 */
	private $settings;

	/**
	 * @param wpdb $wpdb
	 * @param WPML_ST_Settings $settings
	 */
	public function __construct( $wpdb, WPML_ST_Settings $settings = null ) {
		$this->wpdb = $wpdb;

		if ( ! $settings ) {
			$settings = new WPML_ST_Settings();
		}
		$this->settings = $settings;
	}

	public function reset() {
		$this->settings->delete_settings();

		// remove tables at the end to avoid errors in ST due to last actions invoked by hooks
		add_action( 'shutdown', array( $this, 'remove_db_tables' ), PHP_INT_MAX - 1 );
	}

	public function remove_db_tables() {
		$blog_id = $this->retrieve_current_blog_id();

		$is_multisite_reset = $blog_id && function_exists( 'is_multisite' ) && is_multisite();
		if ( $is_multisite_reset ) {
			switch_to_blog( $blog_id );
		}

		$table = $this->wpdb->prefix . 'icl_string_pages';
		$this->wpdb->query( 'DROP TABLE IF EXISTS ' . $table );

		$table = $this->wpdb->prefix . 'icl_string_urls';
		$this->wpdb->query( 'DROP TABLE IF EXISTS ' . $table );

		if ( $is_multisite_reset ) {
			restore_current_blog();
		}
	}

	/**
	 * @return int
	 */
	private function retrieve_current_blog_id() {
		$filtered_id = array_key_exists( 'id', $_POST )
			? filter_var( $_POST['id'], FILTER_SANITIZE_NUMBER_INT ) : false;
		$filtered_id = array_key_exists( 'id', $_GET ) && ! $filtered_id ?
			filter_var( $_GET['id'], FILTER_SANITIZE_NUMBER_INT ) : $filtered_id;
		$blog_id = false !== $filtered_id ? $filtered_id : $this->wpdb->blogid;

		return $blog_id;
	}
}