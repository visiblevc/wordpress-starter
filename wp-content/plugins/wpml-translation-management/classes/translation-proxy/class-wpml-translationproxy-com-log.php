<?php

if ( !class_exists( 'TranslationProxy_Com_Log' ) ) {
	class WPML_TranslationProxy_Com_Log {
		private static $keys_to_block = array(
			'api_token',
			'username',
			'api_key',
			'sitekey',
			'accesskey',
			'file',
		);

		public static function log_call( $url, $params ) {
			$sanitized_params = self::sanitize_data( $params );
			$sanitized_url    = self::sanitize_url( $url );

			self::add_to_log( 'call - ' . $sanitized_url . ' - ' . json_encode( $sanitized_params ) );
		}

		public static function get_keys_to_block() {
			return self::$keys_to_block;
		}

		public static function log_response( $response ) {
			self::add_to_log( 'response - ' . $response );
		}

		public static function log_error( $message ) {
			self::add_to_log( 'error - ' . $message );
		}
		
		public static function log_xml_rpc( $data ) {
			self::add_to_log('xml-rpc - ' . json_encode( $data ) );
		}

		public static function get_log( ) {
			return get_option( 'wpml_tp_com_log', '' );
		}
		
		public static function clear_log( ) {
			self::save_log( '' );
		}
		
		public static function is_logging_enabled( ) {
			global $sitepress;
			
			return $sitepress->get_setting( 'tp-com-logging', true );
		}

		/**
		 * @param string|array|stdClass $params
		 *
		 * @return array|stdClass
		 */
		public static function sanitize_data( $params ) {
			$sanitized_params = $params;

			if ( is_object( $sanitized_params ) ) {
				$sanitized_params = get_object_vars( $sanitized_params );
			}

			if ( is_array( $sanitized_params ) ) {
				foreach ( $sanitized_params as $key => $value ) {
					$sanitized_params[$key] = self::sanitize_data_item( $key, $sanitized_params[ $key ] );
				}
			}

			return $sanitized_params;
		}

		/**
		 * @param string                $key
		 * @param string|array|stdClass $item
		 *
		 * @return string|array|stdClass
		 */
		private static function sanitize_data_item( $key, $item ) {
			if ( is_array( $item ) || is_object( $item ) ) {
				$item = self::sanitize_data( $item );
			} elseif ( in_array( $key, self::get_keys_to_block(), true ) ) {
				$item = 'UNDISCLOSED';
			}

			return $item;
		}

		/**
		 * @param $url
		 *
		 * @return mixed
		 */
		public static function sanitize_url( $url ) {
			$original_url_parsed = wpml_parse_url( $url, PHP_URL_QUERY );
			parse_str( $original_url_parsed, $original_query_vars );

			$sanitized_query_vars = self::sanitize_data( $original_query_vars );

			return add_query_arg( $sanitized_query_vars, $url );
		}

		public static function set_logging_state( $state ) {
			global $sitepress;
				
			$sitepress->set_setting( 'tp-com-logging', $state );
			$sitepress->save_settings( );
		}
		
		public static function add_com_log_link( ) {
			if ( '' !== self::get_log() ) {
				$url = esc_attr( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=com-log' );
				?>
				<p style="margin-top: 20px;">
				    <?php printf(__('For retrieving debug information for communication between your site and the translation system, use the <a href="%s">communication log</a> page.', 'wpml-translation-management'), $url ); ?>
				</p>
				<?php
			}
		}
		
		private static function now( ) {
			return date( 'm/d/Y h:i:s a', time() );
		}
		
		private static function add_to_log( $string ) {
			
			if ( self::is_logging_enabled( ) ) {
				
				$max_log_length = 10000;
				
				$string = self::now( ) . ' - ' . $string;
				
				$log = self::get_log( );
				$log .= $string;
				$log .= PHP_EOL;
				
				$log_length = strlen( $log );
				if ( $log_length > $max_log_length ) {
					$log = substr( $log, $log_length - $max_log_length );
				}
				
				self::save_log( $log );
			}
		}
		
		private static function save_log( $log ) {
			update_option( 'wpml_tp_com_log', $log, 'no');
		}

		
	}
}
