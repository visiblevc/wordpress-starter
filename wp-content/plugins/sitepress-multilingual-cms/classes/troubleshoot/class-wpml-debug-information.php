<?php

class WPML_Debug_Information extends WPML_WPDB_And_SP_User {

	public function run() {
		$info = array( 'core', 'plugins', 'theme', 'extra-debug' );

		$output = array();
		foreach ( $info as $type ) {
			switch ( $type ) {
				case 'core':
					$output['core'] = $this->get_core_info();
					break;
				case 'plugins':
					$output['plugins'] = $this->get_plugins_info();
					break;
				case 'theme':
					$output['theme'] = $this->get_theme_info();
					break;
				case 'extra-debug':
					$output['extra-debug'] = apply_filters( 'icl_get_extra_debug_info', array() );
					break;
			}
		}

		return $output;
	}

	function get_core_info() {

		$core = array(
			'Wordpress' => array(
				'Multisite'          => is_multisite() ? 'Yes' : 'No',
				'SiteURL'            => site_url(),
				'HomeURL'            => home_url(),
				'Version'            => get_bloginfo( 'version' ),
				'PermalinkStructure' => get_option( 'permalink_structure' ),
				'PostTypes'          => implode( ', ', get_post_types( '', 'names' ) ),
				'PostStatus'          => implode( ', ', get_post_stati() )
			),
			'Server'    => array(
				'jQueryVersion'  => wp_script_is( 'jquery', 'registered' ) ? $GLOBALS['wp_scripts']->registered['jquery']->ver : __( 'n/a', 'bbpress' ),
				'PHPVersion'     => $this->sitepress->get_wp_api()->phpversion(),
				'MySQLVersion'   => $this->wpdb->db_version(),
				'ServerSoftware' => $_SERVER['SERVER_SOFTWARE']
			),
			'PHP'       => array(
				'MemoryLimit'     => ini_get( 'memory_limit' ),
				'WP Memory Limit' => WP_MEMORY_LIMIT,
				'UploadMax'       => ini_get( 'upload_max_filesize' ),
				'PostMax'         => ini_get( 'post_max_size' ),
				'TimeLimit'       => ini_get( 'max_execution_time' ),
				'MaxInputVars'    => ini_get( 'max_input_vars' ),
				'MBString'        => $this->sitepress->get_wp_api()->extension_loaded( 'mbstring' ),
				'libxml'          => $this->sitepress->get_wp_api()->extension_loaded( 'libxml' ),
			),
		);

		return $core;
	}

	function get_plugins_info() {

		$plugins             = $this->sitepress->get_wp_api()->get_plugins();
		$active_plugins      = $this->sitepress->get_wp_api()->get_option( 'active_plugins' );
		$active_plugins_info = array();

		foreach ( $active_plugins as $plugin ) {
			if ( isset( $plugins[ $plugin ] ) ) {
				unset( $plugins[ $plugin ]['Description'] );
				$active_plugins_info[ $plugin ] = $plugins[ $plugin ];
			}
		}

		$mu_plugins = get_mu_plugins();

		$dropins = get_dropins();

		$output = array(
			'active_plugins' => $active_plugins_info,
			'mu_plugins'     => $mu_plugins,
			'dropins'        => $dropins,
		);

		return $output;
	}

	function get_theme_info() {

		/** @var WP_Theme $current_theme */
		if ( $this->sitepress->get_wp_api()->get_bloginfo( 'version' ) < '3.4' ) {
			$current_theme = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme         = $current_theme;
			unset( $theme['Description'] );
			unset( $theme['Status'] );
			unset( $theme['Tags'] );
		} else {
			$theme = array(
				'Name'       => $this->sitepress->get_wp_api()->get_theme_name(),
				'ThemeURI'   => $this->sitepress->get_wp_api()->get_theme_URI(),
				'Author'     => $this->sitepress->get_wp_api()->get_theme_author(),
				'AuthorURI'  => $this->sitepress->get_wp_api()->get_theme_authorURI(),
				'Template'   => $this->sitepress->get_wp_api()->get_theme_template(),
				'Version'    => $this->sitepress->get_wp_api()->get_theme_version(),
				'TextDomain' => $this->sitepress->get_wp_api()->get_theme_textdomain(),
				'DomainPath' => $this->sitepress->get_wp_api()->get_theme_domainpath(),
			);
		}

		return $theme;
	}

	function do_json_encode( $data ) {
		$json_options = 0;
		if ( defined( 'JSON_HEX_TAG' ) ) {
			$json_options += JSON_HEX_TAG;
		}
		if ( defined( 'JSON_HEX_APOS' ) ) {
			$json_options += JSON_HEX_APOS;
		}
		if ( defined( 'JSON_HEX_QUOT' ) ) {
			$json_options += JSON_HEX_QUOT;
		}
		if ( defined( 'JSON_HEX_AMP' ) ) {
			$json_options += JSON_HEX_AMP;
		}
		if ( defined( 'JSON_UNESCAPED_UNICODE' ) ) {
			$json_options += JSON_UNESCAPED_UNICODE;
		}

		if ( version_compare( $this->sitepress->get_wp_api()->phpversion(), '5.3.0', '<' ) ) {
			$json_data = wp_json_encode( $data );
		} else {
			$json_data = wp_json_encode( $data, $json_options );
		}

		return $json_data;
	}
}