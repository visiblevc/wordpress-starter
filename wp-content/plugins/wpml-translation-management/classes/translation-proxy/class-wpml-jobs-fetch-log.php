<?php

class WPML_Jobs_Fetch_Log {
	private $fetch_log_settings;
	/** @var WPML_Pro_Translation $pro_translation */
	private   $pro_translation;
	protected $request_type;

	/**
	 * WPML_TP_Logger constructor.
	 *
	 * @param WPML_Pro_Translation         $pro_translation
	 * @param WPML_Jobs_Fetch_Log_Settings $fetch_log_settings
	 * @param WPML_Jobs_Fetch_Log_Job      $fetch_log_job
	 */
	public function __construct( &$pro_translation, &$fetch_log_settings, &$fetch_log_job ) {
		$this->pro_translation    = &$pro_translation;
		$this->fetch_log_settings = &$fetch_log_settings;
		$this->logger_helper      = &$fetch_log_job;

		$wpml_wp_api     = $this->pro_translation->get_wpml_wp_api();
		$this->log_utils = new WPML_Jobs_Fetch_Log_Utils( $wpml_wp_api, $this->fetch_log_settings );
	}

	/**
	 * @param array $job
	 *
	 * @throws Exception
	 */
	public function log_job_data( $job ) {
		if ( $job ) {
			$log_item = $this->init_log_item();
			if ( empty( $job['cms_id'] ) ) {
				$log_item = $this->logger_helper->get_string_translation_data( $job, $log_item );
			} else {
				$log_item = $this->logger_helper->get_job_element_data( $job, $log_item );
			}
			$this->log( $log_item );
		}
	}

	private function init_log_item() {
		$log_item = array();
		foreach ( $this->fetch_log_settings->get_columns_headers() as $header ) {
			$log_item[ $header ] = false;
		}

		return $log_item;
	}

	/**
	 * @param array $data
	 */
	private function log( $data ) {
		$log_base_data = array( 'timestamp' => false, 'action' => false );

		$log_item = array_merge( $log_base_data, $data );

		$log_item['timestamp'] = date( 'Y-m-d H:i:s' );
		$log_item['action']    = $this->request_type;

		$log = $this->log_utils->get_log_data();

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$log = array_slice( $log, - ( $this->fetch_log_settings->get_pickup_log_size() - 1 ) );

		$log[] = $log_item;

		$this->log_utils->update_log_data( $log );
	}
}