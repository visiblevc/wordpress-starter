<?php

class WPML_Jobs_Fetch_Log_Settings {
	const PICKUP_LOGGER_OPTION = '_WPML_TP_Pickup_Logger';
	const PICKUP_LOGGER_UI_KEY = 'tp-pickup-log';
	const PICKUP_LOG_SIZE      = 500;

	/**
	 * @var array
	 */
	private $log_columns;

	public function __construct() {
		$this->log_columns = $this->init_log_columns();
	}

	private function init_log_columns() {
		$log_columns = array(
			'timestamp'              => array(
				'label'      => _x( 'Time', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'action'                 => array(
				'label'      => _x( 'Pickup method', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'pickup:id'              => array(
				'label'      => _x( 'Pickup ID', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'advanced'
			),
			'pickup:cms_id'          => array(
				'label'      => _x( 'Pickup CMS_ID', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'advanced'
			),
			'pickup:job_state'       => array(
				'label'      => _x( 'Job state', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'pickup:source_language' => array(
				'label'      => _x( 'Source language', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'hidden'
			),
			'pickup:target_language' => array(
				'label'      => _x( 'Target language', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'hidden'
			),
			'pickup:batch_id'        => array(
				'label'      => _x( 'Batch ID', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'hidden'
			),
			'job_element_type'       => array(
				'label'      => _x( 'Element type', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'hidden'
			),
			'job_id'                 => array(
				'label'      => _x( 'Job ID', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'advanced'
			),
			'language_code'          => array(
				'label'      => _x( 'Language code', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'source_language_code'   => array(
				'label'      => _x( 'Source language code', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'status'                 => array(
				'label'      => _x( 'Status of original', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'hidden'
			),
			'type'                   => array(
				'label'      => _x( 'Type', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'hidden'
			),
			'title'                  => array(
				'label'      => _x( 'Title', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'original_element_id'    => array(
				'label'      => _x( 'Original element ID', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'original_edit_url'      => array(
				'label'      => _x( 'Edit URL of original', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'original_view_url'      => array(
				'label'      => _x( 'Front-end URL of original', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'original_element_type'  => array(
				'label'      => _x( 'Content type', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'original_status'        => array(
				'label'      => _x( 'Content status', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'translation_edit_url'   => array(
				'label'      => _x( 'Edit URL of translation', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'translation_view_url'   => array(
				'label'      => _x( 'Front-end URL of translation', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'translation_status'     => array(
				'label'      => _x( 'Status of translation', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'revision'               => array(
				'label'      => _x( 'New or updated', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => ''
			),
			'message'                => array(
				'label'      => _x( 'Debug data', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'advanced'
			),
			'serialized_data'        => array(
				'label'      => _x( 'Serialized data', 'Pickup Log Column', 'wpml-translation-management' ),
				'visibility' => 'advanced'
			)
		);

		return $log_columns;
	}

	public function format_data( $header_key, $item_value ) {
		switch ( $header_key ) {
			case 'revision':
				$item_value = $item_value ? _x( 'Updated', 'Pickup Log Value', 'wpml-translation-management' ) : _x( 'New', 'Pickup Log Value', 'wpml-translation-management' );
				break;
			case 'title':
				if ( ! (bool) $item_value ) {
					$item_value = _x( '{untitled}', 'Pickup Log Value', 'wpml-translation-management' );
				}
				break;
		}

		if ( null === $item_value ) {
			$item_value = '{NULL}';
		}

		return $item_value;
	}

	public function get_column_label( $header_key ) {
		return $this->log_columns[ $header_key ]['label'];
	}

	public function get_columns_headers() {
		return array_keys( $this->log_columns );
	}

	public function get_columns_settings() {
		return $this->log_columns;
	}

	public function get_pickup_log_size() {
		return self::PICKUP_LOG_SIZE;
	}

	public function get_pickup_logger_option() {
		return self::PICKUP_LOGGER_OPTION;
	}

	public function get_ui_key() {
		return self::PICKUP_LOGGER_UI_KEY;
	}

	public function is_advanced_column( $header_key ) {
		return array_key_exists( $header_key, $this->log_columns ) && 'advanced' === $this->log_columns[ $header_key ]['visibility'];
	}

	public function is_hidden_column( $header_key ) {
		return array_key_exists( $header_key, $this->log_columns ) && 'hidden' === $this->log_columns[ $header_key ]['visibility'];
	}
}