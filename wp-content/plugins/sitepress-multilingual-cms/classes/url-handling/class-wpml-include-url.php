<?php

class WPML_Include_Url extends WPML_WPDB_User {

	private $unfiltered_home_url;
	private $requested_host;

	public function __construct( &$wpdb, $requested_host ) {
		parent::__construct( $wpdb );
		add_filter( 'style_loader_src', array( $this, 'filter_include_url' ) );
		add_filter( 'script_loader_src', array( $this, 'filter_include_url' ) );
		add_filter( 'the_password_form', array( $this, 'wpml_password_form_filter' ) );
		$this->requested_host = $requested_host;
	}

	public function filter_include_url( $result ) {
		$domains = wpml_get_setting_filter( array(), 'language_domains' );
		$domains = preg_replace( '#^(http(?:s?))://#', '', array_map( 'untrailingslashit', $domains ) );
		if ( (bool) $domains === true ) {
			$php_host_in_domain = wpml_parse_url( $result, PHP_URL_HOST );
			$domains[]          = wpml_parse_url( $this->get_unfiltered_home(), PHP_URL_HOST );
			foreach ( $domains as $dom ) {
				if ( strpos( trailingslashit( $php_host_in_domain ), trailingslashit( $dom ) ) === 0 ) {
					$http_host_parts = explode( ':', $this->requested_host );
					unset( $http_host_parts[1] );
					$http_host_without_port = implode( $http_host_parts );
					$result                 = str_replace( $php_host_in_domain, $http_host_without_port, $result );
					break;
				}
			}
		}

		return $result;
	}

	public function wpml_password_form_filter( $form ) {
		if ( preg_match( '/action="(.*?)"/', $form, $matches ) ) {
			$new_url     = $this->filter_include_url( $matches[1] );
			$form_action = str_replace( $matches[1], $new_url, $matches[0] );
			$form        = str_replace( $matches[0], $form_action, $form );
		}

		return $form;
	}

	/**
	 * Returns the value of the unfiltered home option directly from the wp_options table.
	 *
	 * @return string
	 */
	public function get_unfiltered_home() {
		$this->unfiltered_home_url = $this->unfiltered_home_url
			? $this->unfiltered_home_url
			: $this->wpdb->get_var( "  SELECT option_value
									   FROM {$this->wpdb->options}
									   WHERE option_name = 'home'
									   LIMIT 1" );

		return $this->unfiltered_home_url;
	}
}