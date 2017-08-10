<?php
/**
 * Plugin Name: WP REST API Cache
 * Description: Enable caching for WordPress REST API and increase speed of your application
 * Author: Aires Gonçalves
 * Author URI: http://github.com/airesvsg
 * Version: 1.2.0
 * Plugin URI: https://github.com/airesvsg/wp-rest-api-cache
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_REST_Cache' ) ) {

	class WP_REST_Cache {

		const VERSION = '1.2.0';

		private static $refresh = null;

		public static function init() {
			self::includes();
			self::hooks();
		}

		private static function includes() {
			require_once dirname( __FILE__ ) . '/includes/admin/classes/class-wp-rest-cache-admin.php';
		}

		private static function hooks() {
			add_filter( 'rest_pre_dispatch', array( __CLASS__, 'pre_dispatch' ), 10, 3 );
		}

		public static function pre_dispatch( $result, $server, $request ) {
			$request_uri = esc_url( $_SERVER['REQUEST_URI'] );

			if ( method_exists( $server, 'send_headers' ) ) {
				$headers = apply_filters( 'rest_cache_headers', array(), $request_uri, $server, $request );
				if ( ! empty( $headers ) ) {
					$server->send_headers( $headers );
				}
			}

			if ( true == self::$refresh ) {
				return $result;
			}

			$skip = apply_filters( 'rest_cache_skip', WP_DEBUG, $request_uri, $server, $request );
			if ( ! $skip ) {
				$key = 'rest_cache_' . apply_filters( 'rest_cache_key', $request_uri, $server, $request );
				if ( false === ( $result = get_transient( $key ) ) ) {
					if ( is_null( self::$refresh ) ) {
						self::$refresh = true;
					}
					
					$result  = $server->dispatch( $request );
					$timeout = WP_REST_Cache_Admin::get_options( 'timeout' );
					$timeout = apply_filters( 'rest_cache_timeout', $timeout['length'] * $timeout['period'], $timeout['length'], $timeout['period'] );
					
					set_transient( $key, $result, $timeout );
				}
			}

			return $result;
		}

		public static function empty_cache() {
			global $wpdb;

			return $wpdb->query( $wpdb->prepare( 
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", 
				'_transient_rest_cache_%', 
				'_transient_timeout_rest_cache_%' 
			) );
		}

	}

	add_action( 'init', array( 'WP_REST_Cache', 'init' ) );

	register_uninstall_hook( __FILE__, array( 'WP_REST_Cache', 'empty_cache' ) );

}
