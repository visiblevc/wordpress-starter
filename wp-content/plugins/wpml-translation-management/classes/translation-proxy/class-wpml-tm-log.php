<?php

class WPML_TM_Log {
	const LOG_WP_OPTION = '_wpml_tp_api_Logger';
	const LOG_MAX_SIZE  = 500;

	/**
	 * WPML_TM_Log constructor.
	 *
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( WPML_WP_API $wpml_wp_api = null ) {
		if ( null === $wpml_wp_api ) {
			$wpml_wp_api = new WPML_WP_API();
		}
		$this->wpml_wp_api = $wpml_wp_api;
	}

	public function log( $action, $data = array() ) {
		$log_base_data = array( 'timestamp' => false, 'action' => false );

		$log_item = array_merge( $log_base_data, $data );

		$log_item['timestamp'] = date( 'Y-m-d H:i:s' );
		$log_item['action']    = $action;

		$log = $this->get_log_data();

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$log = array_slice( $log, - ( self::LOG_MAX_SIZE - 1 ) );

		$log[] = $log_item;

		$this->update_log( $log );
	}

	private function update_log( $log ) {
		return $this->wpml_wp_api->update_option( self::LOG_WP_OPTION, $log );
	}

	public function flush_log() {
		return $this->wpml_wp_api->update_option( self::LOG_WP_OPTION, array() );
	}

	public function get_log_data() {
		return $this->wpml_wp_api->get_option( self::LOG_WP_OPTION, array() );
	}
}
