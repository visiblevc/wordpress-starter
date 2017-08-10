<?php

/**
 * @author OnTheGo Systems
 */
class WPML_ST_Themes_And_Plugins_Updates {

	/** @var WPML_Notices */
	private $admin_notices;
	/** @var WPML_ST_Themes_And_Plugins_Settings */
	private $settings;

	/**
	 * WPML_ST_Admin_Notices constructor.
	 *
	 * @param WPML_Notices                        $admin_notices
	 * @param WPML_ST_Themes_And_Plugins_Settings $settings
	 */
	public function __construct( WPML_Notices $admin_notices, WPML_ST_Themes_And_Plugins_Settings $settings ) {
		$this->admin_notices = $admin_notices;
		$this->settings = $settings;
	}

	public function init_hooks() {
		if ( $this->settings->must_display_notices() ) {
			add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );
			add_action( 'updated_option', array( $this, 'updated_option' ), 10, 3 );
			$this->settings->init_hooks();
		}
	}

	public function data_is_valid( $thing ) {
		return $thing && ! is_wp_error( $thing );
	}

	public function upgrader_process_complete( WP_Upgrader $upgrader, $hook_extra ) {
		if ( $this->data_is_valid( $upgrader->result ) ) {
			$action = $hook_extra['action'];
			if ( in_array( $action, array( 'update', 'install' ), true ) ) {
				$type = $hook_extra['type'];

				if ( 'plugin' === $type ) {
					$this->add_notices_for_items( $hook_extra, 'plugin', array( $this, 'add_notices_for_plugins' ) );
				}
				if ( 'theme' === $type ) {
					$this->add_notices_for_items( $hook_extra, 'theme', array( $this, 'add_notices_for_themes' ) );
				}
			}
		}
	}

	private function add_notices_for_items( $hook_extra, $type_singular, $callable ) {
		$type        = $hook_extra['type'];
		$action      = $hook_extra['action'];
		$type_plural = $type_singular . 's'; // Makes a plural of plugin or theme
		$items       = array();

		if ( array_key_exists( $type_plural, $hook_extra ) ) {
			/** @var array $items */
			$items = $hook_extra[ $type_plural ];
		}
		if ( array_key_exists( $type_singular, $hook_extra ) ) {
			/** @var array $items */
			$items = array( $hook_extra[ $type_singular ] );
		}
		if ( $items ) {
			$callable( $items, $type, $action );
		}
	}

	public function updated_option( $option, $old_value, $value ) {
		$accepted_option = in_array( $option, array( 'active_plugins', 'template' ), true );
		$is_deactivating = array_key_exists( 'action', $_GET ) && 'deactivate' === $_GET['action'];

		if ( $accepted_option && ! $is_deactivating ) {
			$action = 'install';
			if ( 'active_plugins' === $option ) {
				$plugins = array_diff( $value, $old_value );
				if ( $plugins ) {
					$this->add_notices_for_plugins( $plugins, 'plugin', $action );
				}
			}
			if ( 'template' === $option ) {
				$theme_data = wp_get_theme( $value );
				if ( $this->data_is_valid( $theme_data ) ) {
					$this->add_notice( 'theme', $action, $theme_data->get( 'Name' ) );
				}
			}
		}
	}

	public function add_notices_for_plugins( $plugins, $type, $action ) {
		if ( is_array( $plugins ) ) {
			$plugins_path = str_replace( get_option( 'siteurl' ), untrailingslashit( ABSPATH ), plugins_url() );
			foreach ( $plugins as $plugin ) {
				$plugin_data = get_plugin_data( $plugins_path . '/' . $plugin );
				if ( array_key_exists( 'Name', $plugin_data ) && $plugin_data['Name'] && $this->data_is_valid( $plugin_data ) ) {
					$this->add_notice( $type, $action, $plugin_data['Name'] );
				}
			}
		}
	}

	public function add_notices_for_themes( $themes, $type, $action ) {
		if ( is_array( $themes ) ) {
			foreach ( $themes as $theme ) {
				$theme_data = wp_get_theme( $theme );
				if ( $this->data_is_valid( $theme_data ) ) {
					$this->add_notice( $type, $action, $theme_data->get( 'Name' ) );
				}
			}
		}
	}

	/**
	 * @param string $type
	 * @param string $action
	 * @param string $plugin_or_theme
	 */
	private function add_notice( $type, $action, $plugin_or_theme ) {
		$message = '';
		if ( 'install' === $action ) {
			if ( 'plugin' === $type ) {
				$message = __( 'Do you want to scan for translatable strings in the plugin(s)?', 'wpml-string-translation' );
			}
			if ( 'theme' === $type ) {
				$message = __( 'Do you want to scan for translatable strings in the theme?', 'wpml-string-translation' );
			}
		}
		if ( 'update' === $action && ( 'plugin' === $type || 'theme' === $type ) ) {
			$message = __( 'Do you want to scan for new translatable strings?', 'wpml-string-translation' );
		}

		$url_args = array( $type => $plugin_or_theme );

		$url_hash = '';
		if ( 'theme' === $type ) {
			$url_hash = 'icl_strings_in_theme_wrap';
		}

		if ( $message ) {
			$string_scan_page = ICL_PLUGIN_FOLDER . '/menu/theme-localization.php';
			$url              = admin_url( 'admin.php?page=' . $string_scan_page );
			$url              = add_query_arg( $url_args, $url );
			if ( $url_hash ) {
				$url .= '#' . $url_hash;
			}

			$themes_and_plugins_settings = new WPML_ST_Themes_And_Plugins_Settings();

			$notice = $this->admin_notices->get_new_notice( $plugin_or_theme, '<strong>' . $plugin_or_theme . '</strong>&nbsp;&mdash;&nbsp;' . $message, $themes_and_plugins_settings->get_notices_group() );
			$notice->set_css_class_types( 'info' );
			$notice->set_exclude_from_pages( array( $string_scan_page ) );
			$notice->add_action( $this->admin_notices->get_new_notice_action( __( 'Scan now', 'wpml-string-translation' ), $url, false, false, true ) );
			$notice->add_action( $this->admin_notices->get_new_notice_action( __( 'Skip', 'wpml-string-translation' ), '#', false, true ) );
			$dismiss_all_action = $this->admin_notices->get_new_notice_action( __( 'Dismiss all these notices', 'wpml-string-translation' ), '#', false, false, false );
			$dismiss_all_action->set_group_to_dismiss( $this->settings->get_notices_group() );
			$dismiss_all_action->set_js_callback( 'wpml_st_hide_strings_scan_notices' );
			$notice->add_action( $dismiss_all_action );
			$this->admin_notices->add_notice( $notice );
		}
	}

	public function notices_count() {
		return $this->admin_notices->count();
	}

	public function remove_notice( $id ) {
		$this->admin_notices->remove_notice( $this->settings->get_notices_group(), $id );
	}
}
