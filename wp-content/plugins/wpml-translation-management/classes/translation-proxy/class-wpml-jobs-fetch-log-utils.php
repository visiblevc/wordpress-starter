<?php

class WPML_Jobs_Fetch_Log_Utils {
	/** @var WPML_WP_API $pro_translation */
	private   $wpml_wp_api;
	private   $fetch_log_settings;
	protected $request_type;

	/**
	 * WPML_TP_Logger constructor.
	 *
	 * @param WPML_WP_API                  $wpml_wp_api
	 * @param WPML_Jobs_Fetch_Log_Settings $fetch_log_settings
	 */
	public function __construct( &$wpml_wp_api, &$fetch_log_settings ) {
		$this->wpml_wp_api        = &$wpml_wp_api;
		$this->fetch_log_settings = &$fetch_log_settings;
	}

	/**
	 * @return array
	 */
	public function get_log_data() {
		return $this->wpml_wp_api->get_option( $this->fetch_log_settings->get_pickup_logger_option(), array() );
	}

	private function sanitize_data( $data ) {

		foreach ( $data as &$row ) {
			foreach ( $row as &$column ) {
				$column = $this->sanitize_data_value( $column );
			}
		}

		return $data;
	}

	private function sanitize_data_value( $item_value ) {
		if ( is_array( $item_value ) ) {
			$item_value = implode( "\n", $item_value );
		}

		return $item_value;
	}

	public function stream_csv( $data = null, $file_name_prefix = 'wpml-tp-log' ) {
		if ( ! $data ) {
			$data = $this->get_log_data();
		}

		$data = $this->sanitize_data( $data );

		if ( $file_name_prefix ) {
			$file_name_prefix = $file_name_prefix . '-';
		} else {
			$file_name_prefix = '';
		}

		if ( count( $data ) ) {
			$fileName = $file_name_prefix . date( 'Ymd' ) . '-' . date( 'His' ) . '.csv';
			header_remove();
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-type: text/csv' );
			header( 'Content-Disposition: attachment; filename=' . $fileName );
			header( 'Expires: 0' );
			header( 'Pragma: public' );

			$this->build_stream_csv( $data );
			exit;
		}
	}

	/**
	 * @param $log
	 *
	 * @return bool
	 */
	public function update_log_data( $log ) {
		return $this->wpml_wp_api->update_option( $this->fetch_log_settings->get_pickup_logger_option(), $log );
	}

	/**
	 * @param array $data
	 */
	public function build_stream_csv( $data ) {
		$fh = @fopen( 'php://output', 'w' );

		$headerDisplayed = false;

		$data = $this->unescapeURLs( $data );

		foreach ( $data as $row ) {
			if ( ! $headerDisplayed ) {
				fputcsv( $fh, array_keys( $row ) );
				$headerDisplayed = true;
			}
			fputcsv( $fh, $row );
		}
		fclose( $fh );
	}

	public function unescapeURLs( $data ) {
		$results = array();

		foreach ( $data as $row ) {
			$results_row = array();
			foreach ( $row as $column => $value ) {
				if ( $this->wpml_wp_api->is_url( $value ) ) {
					$value = urldecode( $value );
				}
				$results_row[ $column ] = $value;
			}
			$results[] = $results_row;
		}

		Return $results;
	}
}