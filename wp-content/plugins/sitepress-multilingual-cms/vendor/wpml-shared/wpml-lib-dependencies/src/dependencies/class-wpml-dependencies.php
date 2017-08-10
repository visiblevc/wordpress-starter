<?php
/*
Module Name: WPML Dependency Check Module
Description: This is not a plugin! This module must be included in other plugins (WPML and add-ons) to handle compatibility checks
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 2.0
*/

/** @noinspection PhpUndefinedClassInspection */
class WPML_Dependencies {
	private static $instance;
	private        $admin_notice;
	private        $current_product;
	private        $current_version    = array();
	private        $expected_versions  = array();
	private        $installed_plugins  = array();
	private        $invalid_plugins    = array();
	private        $valid_plugins      = array();
	private        $validation_results = array();

	public $data_key             = 'wpml_dependencies:';
	public $needs_validation_key = 'wpml_dependencies:needs_validation';

	private function __construct() {
		if ( null === self::$instance ) {
			$this->remove_old_admin_notices();
			$this->init_hooks();
		}
	}

	private function collect_data() {
		$active_plugins = wp_get_active_and_valid_plugins();
		$this->init_bundle( $active_plugins );
		foreach ( $active_plugins as $plugin ) {
			$this->add_installed_plugin( $plugin );
		}
	}

	private function remove_old_admin_notices() {
		if ( class_exists( 'WPML_Bundle_Check' ) ) {
			global $WPML_Bundle_Check;

			remove_action( 'admin_notices', array( $WPML_Bundle_Check, 'admin_notices_action' ) );
		}
	}

	private function init_hooks() {
		add_action( 'init', array( $this, 'init_plugins_action' ) );
		add_action( 'extra_plugin_headers', array( $this, 'extra_plugin_headers_action' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices_action' ) );
		add_action( 'activated_plugin', array( $this, 'activated_plugin_action' ) );
		add_action( 'deactivated_plugin', array( $this, 'deactivated_plugin_action' ) );
	}

	public function activated_plugin_action() {
		$this->reset_validation();
	}

	public function deactivated_plugin_action() {
		$this->reset_validation();
	}

	private function reset_validation() {
		update_option( $this->needs_validation_key, true );
		$this->validate_plugins();
	}

	private function flag_as_validated() {
		update_option( $this->needs_validation_key, false );
	}

	private function needs_validation() {
		return get_option( $this->needs_validation_key );
	}

	public function admin_notices_action() {
		$this->maybe_init_admin_notice();
		if ( $this->admin_notice && ( is_admin() && ! $this->is_doing_ajax_cron_or_xmlrpc() ) ) {
			echo $this->admin_notice;
		}
	}

	private function is_doing_ajax_cron_or_xmlrpc() {
		return ( $this->is_doing_ajax() || $this->is_doing_cron() || $this->is_doing_xmlrpc() );
	}

	private function is_doing_ajax() {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
	}

	private function is_doing_cron() {
		return ( defined( 'DOING_CRON' ) && DOING_CRON );
	}

	private function is_doing_xmlrpc() {
		return ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST );
	}

	public function extra_plugin_headers_action( array $extra_headers = array() ) {
		$new_extra_header = array(
			'PluginSlug' => 'Plugin Slug',
		);

		return array_merge( $new_extra_header, (array) $extra_headers );
	}

	/**
	 * @return WPML_Dependencies
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WPML_Dependencies();
		}

		return self::$instance;
	}

	public function get_plugins() {
		return $this->installed_plugins;
	}

	public function init_plugins_action() {
		if ( $this->needs_validation() && is_admin() && ! $this->is_doing_ajax_cron_or_xmlrpc() ) {
			$this->init_plugins();
			$this->validate_plugins();
			$this->flag_as_validated();
		}
	}

	private function init_plugins() {
		if ( ! $this->installed_plugins ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				/** @noinspection PhpIncludeInspection */
				include_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			if ( function_exists( 'get_plugin_data' ) ) {
				$this->collect_data();
			}
		}
		update_option( $this->data_key . 'installed_plugins', $this->installed_plugins );
	}

	private function init_bundle( array $active_plugins ) {

		foreach ( $active_plugins as $plugin_file ) {
			$filename = dirname( $plugin_file ) . '/wpml-dependencies.json';
			if ( file_exists( $filename ) ) {
				$data   = file_get_contents( $filename );
				$bundle = json_decode( $data, true );
				$this->set_expected_versions( $bundle );
			}
		}
	}

	private function add_installed_plugin( $plugin ) {
		$data       = get_plugin_data( $plugin );
		$plugin_dir = dirname( $plugin );

		if ( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR !== $plugin_dir ) {
			$plugin_folder = str_replace( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR, '', $plugin_dir );
			$plugin_slug   = $this->guess_plugin_slug( $data, $plugin_folder );

			if ( $this->is_valid_plugin( $plugin_slug ) ) {
				$this->installed_plugins[ $plugin_slug ] = $data['Version'];
			}
		}
	}

	private function set_expected_versions( array $bundle ) {
		foreach ( $bundle as $plugin => $version ) {
			if ( ! array_key_exists( $plugin, $this->expected_versions ) ) {
				$this->expected_versions[ $plugin ] = $version;
			} else {
				if ( version_compare( $this->expected_versions[ $plugin ], $version, '<' ) ) {
					$this->expected_versions[ $plugin ] = $version;
				}
			}
		}
	}

	private function guess_plugin_slug( $plugin_data, $plugin_folder ) {
		$plugin_slug = null;
		$plugin_slug = $plugin_folder;
		if ( array_key_exists( 'Plugin Slug', $plugin_data ) && $plugin_data['Plugin Slug'] ) {
			$plugin_slug = $plugin_data['Plugin Slug'];
		}

		return $plugin_slug;
	}

	private function validate_plugins() {
		$validation_results = $this->get_plugins_validation();

		$this->valid_plugins   = array();
		$this->invalid_plugins = array();
		foreach ( $validation_results as $plugin => $validation_result ) {
			if ( true === $validation_result ) {
				$this->valid_plugins[] = $plugin;
			} else {
				$this->invalid_plugins[] = $plugin;
			}
		}

		update_option( $this->data_key . 'valid_plugins', $this->valid_plugins );
		update_option( $this->data_key . 'invalid_plugins', $this->invalid_plugins );
	}

	public function get_plugins_validation() {
		foreach ( $this->installed_plugins as $plugin => $version ) {
			$this->current_product = $plugin;
			if ( $this->is_valid_plugin() ) {
				$this->current_version               = $version;
				$validation_result                   = $this->is_plugin_version_valid();
				$this->validation_results[ $plugin ] = $validation_result;
			}
		}

		return $this->validation_results;
	}

	private function is_valid_plugin( $product = false ) {
		$result = false;

		if ( ! $product ) {
			$product = $this->current_product;
		}
		if ( $product ) {
			$versions = $this->get_expected_versions();
			$result   = array_key_exists( $product, $versions );
		}

		return $result;
	}

	public function is_plugin_version_valid() {
		$expected_version = $this->filter_version( $this->get_expected_product_version() );

		return $expected_version ? version_compare( $this->filter_version( $this->current_version ), $expected_version, '>=' ) : null;
	}

	private function filter_version( $version ) {
		return preg_replace( '#[^\d.].*#', '', $version );
	}

	public function get_expected_versions() {
		return $this->expected_versions;
	}

	private function get_expected_product_version( $product = false ) {
		$result = null;

		if ( ! $product ) {
			$product = $this->current_product;
		}
		if ( $product ) {
			$versions = $this->get_expected_versions();

			$result = isset( $versions[ $product ] ) ? $versions[ $product ] : null;
		}

		return $result;
	}

	private function maybe_init_admin_notice() {
		$this->admin_notice      = null;
		$this->installed_plugins = get_option( $this->data_key . 'installed_plugins', array() );
		$this->invalid_plugins   = get_option( $this->data_key . 'invalid_plugins', array() );
		$this->valid_plugins     = get_option( $this->data_key . 'valid_plugins', array() );

		if ( $this->has_invalid_plugins() ) {
			$notice_paragraphs = array();

			$notice_paragraphs[] = $this->get_invalid_plugins_report_header();
			$notice_paragraphs[] = $this->get_invalid_plugins_report_list();
			$notice_paragraphs[] = $this->get_invalid_plugins_report_footer();

			$this->admin_notice = '<div class="error wpml-admin-notice">';
			$this->admin_notice .= '<h3>' . __( 'WPML Update is Incomplete', 'sitepress' ) . '</h3>';
			$this->admin_notice .= '<p>' . implode( '</p><p>', $notice_paragraphs ) . '</p>';
			$this->admin_notice .= '</div>';
		}
	}

	public function has_invalid_plugins() {
		return count( $this->invalid_plugins );
	}

	private function get_invalid_plugins_report_header() {
		if ( $this->has_valid_plugins() ) {
			if ( count( $this->valid_plugins ) === 1 ) {
				$paragraph = __( 'You are running updated %s, but the following component is not updated:', 'sitepress' );
				$paragraph = sprintf( $paragraph, '<strong>' . $this->valid_plugins[0] . '</strong>' );
			} else {
				$paragraph           = __( 'You are running updated %s and %s, but the following components are not updated:', 'sitepress' );
				$first_valid_plugins = implode( ', ', array_slice( $this->valid_plugins, 0, - 1 ) );
				$last_valid_plugin   = array_slice( $this->valid_plugins, - 1 );
				$paragraph           = sprintf( $paragraph, '<strong>' . $first_valid_plugins . '</strong>', '<strong>' . $last_valid_plugin[0] . '</strong>' );
			}
		} else {
			$paragraph = __( 'The following components are not updated:', 'sitepress' );
		}

		return $paragraph;
	}

	private function get_invalid_plugins_report_list() {
		$invalid_plugins_list = '<ul class="ul-disc">';
		foreach ( $this->invalid_plugins as $invalid_plugin ) {
			$plugin_name_html = '<li data-installed-version="' . $this->installed_plugins[ $invalid_plugin ] . '">';
			$plugin_name_html .= $invalid_plugin;
			$plugin_name_html .= '</li>';

			$invalid_plugins_list .= $plugin_name_html;
		}
		$invalid_plugins_list .= '</ul>';

		return $invalid_plugins_list;
	}

	private function get_invalid_plugins_report_footer() {
		$wpml_org_url = '<a href="https://wpml.org/account/" title="WPML.org account">' . __( 'WPML.org account', 'sitepress' ) . '</a>';

		$notice_paragraph = __( 'Your site will not work as it should in this configuration', 'sitepress' );
		$notice_paragraph .= ' ';
		$notice_paragraph .= __( 'Please update all components which you are using.', 'sitepress' );
		$notice_paragraph .= ' ';
		$notice_paragraph .= sprintf( __( 'For WPML components you can receive updates from your %s or automatically, after you register WPML.', 'sitepress' ), $wpml_org_url );

		return $notice_paragraph;
	}

	private function has_valid_plugins() {
		return $this->valid_plugins && count( $this->valid_plugins );
	}
}
