<?php

require_once dirname( __FILE__ ) . '/wpml-string-scanner.class.php';

class WPML_Plugin_String_Scanner extends WPML_String_Scanner {

	private $current_plugin_file;

	public function scan( $no_echo ) {

		$string_settings = apply_filters( 'wpml_get_setting', false, 'st' );
		if ( isset( $_POST[ 'wpml_st_theme_localization_type_wpml_td' ] ) && $_POST[ 'wpml_st_theme_localization_type_wpml_td' ] ) {
			$string_settings[ 'use_header_text_domains_when_missing' ] = 1;
		} else {
			$string_settings[ 'use_header_text_domains_when_missing' ] = 0;
		}
		do_action( 'wpml_set_setting', 'st', $string_settings, true );

		$this->current_plugin_file = null;
		$this->current_type = 'plugin';

		set_time_limit( 0 );
		if ( preg_replace( '#M$#', '', ini_get( 'memory_limit' ) ) < 128 ) {
			ini_set( 'memory_limit', '128M' );
		}
		$plugins = array();
		if ( ! empty( $_POST[ 'plugin' ] ) ) {
			foreach ( $_POST[ 'plugin' ] as $plugin ) {
				$plugins[ ] = array( 'file' => $plugin, 'mu' => 0 ); // regular plugins
			}
		}
		if ( ! empty( $_POST[ 'mu-plugin' ] ) ) {
			foreach ( $_POST[ 'mu-plugin' ] as $plugin ) {
				$plugins[ ] = array( 'file' => $plugin, 'mu' => 1 ); //mu plugins
			}
		}
		foreach ( $plugins as $p ) {
			$plugin = $p[ 'file' ];
			$this->current_plugin_file = $p[ 'file' ];

			$this->scan_starting( $plugin );

			if ( false !== strpos( $plugin, '/' ) && ! $p['mu'] ) {
				$plugin = dirname( $plugin );
			}

			if ( ! path_is_absolute( $plugin ) ) {
				if ( $p['mu'] ) {
					$plugin_path               = WPMU_PLUGIN_DIR . '/' . $plugin;
					$this->current_plugin_file = WPMU_PLUGIN_DIR . '/' . $p['file'];
				} else {
					$plugin_path               = WP_PLUGIN_DIR . '/' . $plugin;
					$this->current_plugin_file = WP_PLUGIN_DIR . '/' . $p['file'];
				}
			} else {
				$this->current_plugin_file = $p[ 'file' ];
				$plugin_path               = $plugin;
			}

			if ( wpml_st_file_path_is_valid( $plugin_path ) && wpml_st_file_path_is_valid( $this->current_plugin_file ) ) {
				$this->current_path = $plugin_path;

				$text_domain = $this->get_plugin_text_domain();
				$this->init_text_domain( $text_domain );

				$this->add_stat( PHP_EOL . sprintf( __( 'Scanned files from %s:', 'wpml-string-translation' ), $plugin ) );
				$this->scan_plugin_files();

				$this->current_type = 'plugin';
				if ( isset( $_POST['icl_load_mo'] ) && $_POST['icl_load_mo'] && ! $p['mu'] ) {
					$this->add_translations( array_keys( $this->get_domains_found() ), '' );
				}
				$this->copy_old_translations( array_keys( $this->get_domains_found() ), 'plugin' );
				$this->cleanup_wrong_contexts();

				$string_settings                                               = apply_filters( 'wpml_get_setting', false, 'st' );
				$string_settings['plugin_localization_domains'] [ $p['file'] ] = $this->get_domains_found();
				do_action( 'wpml_set_setting', 'st', $string_settings, true );
			} else {
				$this->add_stat( sprintf( __( 'Invalid file: %s', 'wpml-string-translation' ), "/" . $plugin_path ) );
			}
		}
		$this->add_scan_stat_summary();

		if ( $this->current_plugin_file ) {
			$plugin_data = get_plugin_data( $this->current_plugin_file );
			if ( $plugin_data && ! is_wp_error( $plugin_data ) ) {
				$this->remove_notice( $plugin_data['Name'] );
			}
		}

		if ( ! $no_echo ) {
			$this->scan_response();
		}
	}

	private function add_scan_stat_summary(){
			global $__icl_registered_strings;
			if ( is_null( $__icl_registered_strings ) ) {
				$__icl_registered_strings = array();
			}
			$pre_stats = __( 'Done scanning files', 'wpml-string-translation' ) . PHP_EOL;

			unset( $icl_st_p_scan_plugin_id );
			if ( count( $__icl_registered_strings ) ) {
				$pre_stats .= sprintf( __( 'WPML found %s strings. They were added to the string translation table.', 'wpml-string-translation' ), count( $__icl_registered_strings ) ) . PHP_EOL;
			} else {
				$pre_stats .= __( 'No strings found.', 'wpml-string-translation' ) . PHP_EOL;
			}
			$pre_stats .= count( $this->get_scanned_files() ) . ' scanned files' . PHP_EOL;

			$this->add_stat( $pre_stats, true );
			$this->add_stat( '<textarea style="width:100%;height:150px;font-size:10px;">', true );
			$this->add_stat( '</textarea>' );
	}

	private function scan_plugin_files( $dir_or_file = false, $recursion = 0 ) {
		require_once WPML_ST_PATH . '/inc/potx.php';
		global $icl_st_p_scan_plugin_id;

		if ( $dir_or_file === false ) {
			$dir_or_file = $this->current_path;
		}

		if ( wpml_st_file_path_is_valid( $dir_or_file ) ) {
			if ( ! $recursion ) {
				$icl_st_p_scan_plugin_id = str_replace( WP_PLUGIN_DIR . '/', '', $dir_or_file );
				$icl_st_p_scan_plugin_id = str_replace( WPMU_PLUGIN_DIR . '/', '', $icl_st_p_scan_plugin_id );
			}

			if ( is_file( $dir_or_file ) && ! $recursion ) { // case of one-file plugins
				$this->add_stat( sprintf( __( 'Scanning file: %s', 'wpml-string-translation' ), $dir_or_file ) );
				_potx_process_file( $dir_or_file, 0, array( $this, 'store_results' ), '_potx_save_version', $this->get_default_domain() );
				$this->add_scanned_file( $dir_or_file );
			} else {
				$dh = opendir( $dir_or_file );
				while ( $dh && false !== ( $file = readdir( $dh ) ) ) {
					if ( 0 === strpos( $file, '.' ) ) {
						continue;
					}
					if ( is_dir( $dir_or_file . "/" . $file ) ) {
						$recursion ++;
						$this->add_stat( str_repeat( "\t", $recursion - 1 ) . sprintf( __( 'Opening folder: %s', 'wpml-string-translation' ), "/" . $file ) );
						$this->scan_plugin_files( $dir_or_file . "/" . $file, $recursion );
						$recursion --;
					} elseif ( preg_match( '#(\.php|\.inc)$#i', $file ) ) {
						$this->add_stat( str_repeat( "\t", $recursion ) . sprintf( __( 'Scanning file: %s', 'wpml-string-translation' ), "/" . $file ) );
						$this->add_scanned_file( "/" . $file );
						_potx_process_file( $dir_or_file . "/" . $file, 0, array( $this, 'store_results' ), '_potx_save_version', $this->get_default_domain() );
					} else {
						$this->add_stat( str_repeat( "\t", $recursion ) . sprintf( __( 'Skipping file: %s', 'wpml-string-translation' ), "/" . $file ) );
					}
				}
			}
		} else {
			$this->add_stat( str_repeat( "\t", $recursion ) . sprintf( __( 'Invalid file: %s', 'wpml-string-translation' ), "/" . $dir_or_file ) );
		}
		if ( ! $recursion ) {
			unset( $icl_st_p_scan_plugin_id );
		}
	}
	
	private function get_plugin_text_domain() {
		$text_domain = '';
		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		if ( function_exists( 'get_plugin_data' ) ) {
			$plugin_data = get_plugin_data( $this->current_plugin_file );
			if ( isset( $plugin_data[ 'TextDomain' ] ) && $plugin_data[ 'TextDomain' ] != '' ) {
				$text_domain = $plugin_data[ 'TextDomain' ];
	
				return $text_domain;
			}
	
			return $text_domain;
		}
	
		return $text_domain;
	}	
}