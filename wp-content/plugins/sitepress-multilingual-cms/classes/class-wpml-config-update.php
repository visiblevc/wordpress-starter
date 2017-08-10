<?php

/**
 * Fetch the wpml config files for known plugins and themes
 *
 * @package wpml-core
 */
class WPML_Config_Update {
	/** @var  SitePress $sitepress */
	protected $sitepress;

	/**
	 * @var WP_Http $http
	 */
	private $http;

	/**
	 * @var WPML_Active_Plugin_Provider
	 */
	private $active_plugin_provider;

	/**
	 * WPML_Config_Update constructor.
	 *
	 * @param SitePress $sitepress
	 * @param WP_Http $http
	 */
	public function __construct( $sitepress, $http ) {
		$this->sitepress = $sitepress;
		$this->http = $http;
	}

	/**
	 * @param WPML_Active_Plugin_Provider $active_plugin_provider
	 */
	public function set_active_plugin_provider( WPML_Active_Plugin_Provider $active_plugin_provider ) {
		$this->active_plugin_provider = $active_plugin_provider;
	}

	/**
	 * @return WPML_Active_Plugin_Provider
	 */
	public function get_active_plugin_provider() {
		if ( null === $this->active_plugin_provider ) {

			if ( ! class_exists( 'WPML_Active_Plugin_Provider' ) ) {
				require_once ICL_PLUGIN_PATH . '/classes/class-wpml-active-plugin-provider.php';
			}

			$this->active_plugin_provider = new WPML_Active_Plugin_Provider( $this->sitepress->get_wp_api() );
		}

		return $this->active_plugin_provider;
	}

	public function run() {
		if ( ! $this->is_config_update_disabled() ) {

			$response = $this->http->get( ICL_REMOTE_WPML_CONFIG_FILES_INDEX . 'wpml-config/config-index.json' );

			if ( ! is_wp_error( $response ) && $response['response']['code'] == 200 ) {
				$arr = json_decode( $response['body'] );

				if ( isset( $arr->plugins ) && isset( $arr->themes ) ) {
					update_option( 'wpml_config_index', $arr );
					update_option( 'wpml_config_index_updated', time() );

					$config_files = maybe_unserialize( get_option( 'wpml_config_files_arr' ) );

					$config_files_for_themes     = array();
					$deleted_configs_for_themes  = array();
					$config_files_for_plugins    = array();
					$deleted_configs_for_plugins = array();
					if ( $config_files ) {
						if ( isset( $config_files->themes ) ) {
							$config_files_for_themes    = $config_files->themes;
							$deleted_configs_for_themes = $config_files->themes;
						}
						if ( isset( $config_files->plugins ) ) {
							$config_files_for_plugins    = $config_files->plugins;
							$deleted_configs_for_plugins = $config_files->plugins;
						}
					}

					$current_theme_name = $this->sitepress->get_wp_api()->get_theme_name();

					$current_theme_parent = '';
					if( method_exists( $this->sitepress->get_wp_api(), 'get_theme_parent_name' ) ) {
						$current_theme_parent = $this->sitepress->get_wp_api()->get_theme_parent_name();
					}

					$active_theme_names = array( $current_theme_name );
					if ( $current_theme_parent ) {
						$active_theme_names[] = $current_theme_parent;
					}
					foreach ( $arr->themes as $theme ) {

						if ( in_array( $theme->name, $active_theme_names ) ) {

							unset( $deleted_configs_for_themes[ $theme->name ] );

							if ( ! isset( $config_files_for_themes[ $theme->name ] ) || md5( $config_files_for_themes[ $theme->name ] ) != $theme->hash ) {
								$response = $this->http->get( ICL_REMOTE_WPML_CONFIG_FILES_INDEX . $theme->path );
								if ( $response['response']['code'] == 200 ) {
									$config_files_for_themes[ $theme->name ] = $response['body'];
								}
							}
						}
					}

					foreach ( $deleted_configs_for_themes as $key => $deleted_config ) {
						unset( $config_files_for_themes[ $key ] );
					}

					$active_plugins_names = $this->get_active_plugin_provider()->get_active_plugin_names();

					foreach ( $arr->plugins as $plugin ) {

						if ( in_array( $plugin->name, $active_plugins_names ) ) {

							unset( $deleted_configs_for_plugins[ $plugin->name ] );

							if ( ! isset( $config_files_for_plugins[ $plugin->name ] ) || md5( $config_files_for_plugins[ $plugin->name ] ) != $plugin->hash )  {
								$response = $this->http->get( ICL_REMOTE_WPML_CONFIG_FILES_INDEX . $plugin->path );

								if ( ! is_wp_error( $response ) && $response['response']['code'] == 200 ) {
									$config_files_for_plugins[ $plugin->name ] = $response['body'];
								}
							}
						}
					}

					foreach ( $deleted_configs_for_plugins as $key => $deleted_config ) {
						unset( $config_files_for_plugins[ $key ] );
					}

					if ( ! isset( $config_files ) || ! $config_files ) {
						$config_files = new stdClass();
					}
					$config_files->themes  = $config_files_for_themes;
					$config_files->plugins = $config_files_for_plugins;

					update_option( 'wpml_config_files_arr', $config_files );

					return true;
				}
			}
		}

		return false;
	}

	private function is_config_update_disabled() {

		if ( $this->sitepress->get_wp_api()->constant( 'ICL_REMOTE_WPML_CONFIG_DISABLED' ) ) {
			delete_option( "wpml_config_index" );
			delete_option( "wpml_config_index_updated" );
			delete_option( "wpml_config_files_arr" );

			return true;
		}

		return false;
	}
}
