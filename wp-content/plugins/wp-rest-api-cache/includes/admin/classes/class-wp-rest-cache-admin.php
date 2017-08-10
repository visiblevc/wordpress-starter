<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_REST_Cache_Admin' ) ) {

	class WP_REST_Cache_Admin {

		private static $default = array(
			'timeout' => array(
				'length' => 1,
				'period' => WEEK_IN_SECONDS,
			),
		);

		public static function init() {
			self::hooks();
		}

		private static function hooks() {
			if ( apply_filters( 'rest_cache_show_admin', true ) ) {
				if ( apply_filters( 'rest_cache_show_admin_menu', true ) ) {
					add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
				}
				
				if ( apply_filters( 'rest_cache_show_admin_bar_menu', true ) ) {
					add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_menu' ), 999 );				
				}				
			}
		}

		public static function admin_bar_menu( $wp_admin_bar ) {
			$args = array(
				'id'    => 'wp-rest-api-cache-empty',
				'title' => __( 'Empty WP REST API Cache', 'wp-rest-api-cache' ),
				'href'  => self::_empty_cache_url(),
			);
		
			$wp_admin_bar->add_node( $args );
		}

		public static function admin_menu() {
			add_submenu_page( 
				'options-general.php', 
				__( 'WP REST API Cache', 'wp-rest-api-cache' ), 
				__( 'WP REST API Cache', 'wp-rest-api-cache' ), 
				'manage_options', 
				'rest-cache', 
				array( __CLASS__, 'render_page' ) 
			);
		}

		public static function render_page() {
			$notice = null;

			if ( isset( $_REQUEST['rest_cache_nonce'] ) && wp_verify_nonce( $_REQUEST['rest_cache_nonce'], 'rest_cache_options' ) ) {
				if ( isset( $_GET['rest_cache_empty'] ) && 1 == $_GET['rest_cache_empty'] ) {
					if ( WP_REST_Cache::empty_cache() ) {
						$type    = 'updated';
						$message = __( 'The cache has been successfully cleared', 'wp-rest-api-cache' );
					} else {
						$type    = 'error';
						$message = __( 'The cache is already empty', 'wp-rest-api-cache' );
					}
				} elseif ( isset( $_POST['rest_cache_options'] ) && ! empty( $_POST['rest_cache_options'] ) ) {
					if ( self::_update_options( $_POST['rest_cache_options'] ) ) {
						$type    = 'updated';
						$message = __( 'The cache time has been updated', 'wp-rest-api-cache' );
					} else {
						$type    = 'error';
						$message = __( 'The cache time has not been updated', 'wp-rest-api-cache' );
					}
				}
				add_settings_error( 'wp-rest-api-notice', esc_attr( 'settings_updated' ), $message, $type );
			}

			$options = self::get_options();

			require_once dirname( __FILE__ ) . '/../views/html-options.php';
		}

		private static function _update_options( $options ) {
			$options = apply_filters( 'rest_cache_update_options', $options );

			return update_option( 'rest_cache_options', $options, 'yes' );
		}

		public static function get_options( $key = null ) {
			$options = apply_filters( 'rest_cache_get_options', get_option( 'rest_cache_options', self::$default ) );
			
			if ( is_string( $key ) && array_key_exists( $key, $options ) ) {
				return $options[$key];
			} 

			return $options;
		}

		private static function _empty_cache_url() {
			return wp_nonce_url( admin_url( 'options-general.php?page=rest-cache&rest_cache_empty=1' ), 'rest_cache_options', 'rest_cache_nonce' );
		}

	}

	WP_REST_Cache_Admin::init();

}