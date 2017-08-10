<?php

class WPML_ST_Theme_Localization_Type {

	/**
	 * @var wpdb;
	 */
	private $wpdb;

	/**
	 * @var WPML_ST_Themes_And_Plugins_Settings
	 */
	private $themes_and_plugins_settings;

	/**
	 * @var WPML_ST_DB_Cache_Factory
	 */
	private $st_db_cache_factory;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function add_hooks() {
		add_action( 'wpml_post_save_theme_localization_type', array( $this, 'save_scaning_alert_settings' ) );
		add_action( 'wpml_post_save_theme_localization_type', array( $this, 'clear_st_cache' ) );
	}

	public function save_scaning_alert_settings() {
		$themes_and_plugins_settings = $this->get_themes_and_plugins_settings();
		$display_strings_scan_notices = false;
		if ( array_key_exists( 'wpml_st_display_strings_scan_notices', $_POST ) ) {
			$display_strings_scan_notices = filter_var( $_POST['wpml_st_display_strings_scan_notices'], FILTER_VALIDATE_BOOLEAN );
		}
		$themes_and_plugins_settings->set_strings_scan_notices( $display_strings_scan_notices );
	}

	public function clear_st_cache() {
		$factory = $this->get_st_db_cache_factory();
		$persist = $factory->create_persist();
		$persist->clear_cache();
	}

	/**
	 * @return WPML_ST_DB_Cache_Factory
	 */
	public function get_st_db_cache_factory() {
		if ( ! $this->st_db_cache_factory ) {
			$this->st_db_cache_factory = new WPML_ST_DB_Cache_Factory( $this->wpdb );
		}

		return $this->st_db_cache_factory;
	}

	/**
	 * @param WPML_ST_DB_Cache_Factory $st_db_cache_factory
	 * @return $this
	 */
	public function set_st_db_cache_factory( WPML_ST_DB_Cache_Factory $st_db_cache_factory ) {
		$this->st_db_cache_factory = $st_db_cache_factory;
		return $this;
	}

	/**
	 * @return WPML_ST_Themes_And_Plugins_Settings
	 */
	public function get_themes_and_plugins_settings() {
		if ( ! $this->themes_and_plugins_settings ) {
			$this->themes_and_plugins_settings = new WPML_ST_Themes_And_Plugins_Settings();
		}

		return $this->themes_and_plugins_settings;
	}

	/**
	 * @param WPML_ST_Themes_And_Plugins_Settings $themes_and_plugins_settings
	 * @return $this
	 */
	public function set_themes_and_plugins_settings( WPML_ST_Themes_And_Plugins_Settings $themes_and_plugins_settings ) {
		$this->themes_and_plugins_settings = $themes_and_plugins_settings;
		return $this;
	}
}