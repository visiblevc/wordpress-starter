<?php


class WPML_Autoregister_Context_Exclude {

	const SETTING_KEY = 'wpml_st_auto_reg_excluded_contexts';

	/**
	 * @var WPDB $wpdb
	 */
	protected $wpdb;

	/**
	 * @var WPML_ST_Settings
	 */
	private $settings;

	/**
	 * @param WPDB $wpdb
	 * @param WPML_ST_Settings $settings
	 */
	public function __construct( WPDB $wpdb, WPML_ST_Settings $settings ) {
		$this->wpdb = $wpdb;
		$this->settings = $settings;
	}

	/**
	 * @return array
	 */
	public function get_excluded_contexts() {
		$string_settings = $this->settings->get_setting( self::SETTING_KEY );

		return $string_settings ? $string_settings : array();
	}

	/**
	 * @return array
	 */
	public function get_included_contexts() {
		return array_values( array_diff( $this->get_all_contexts(), $this->get_excluded_contexts() ) );
	}

	/**
	 * @return array
	 */
	public function get_all_contexts() {
		$sql = "
			SELECT DISTINCT context
			FROM {$this->wpdb->prefix}icl_strings 
		";

		$rowset = $this->wpdb->get_col( $sql );

		return array_unique( array_merge( $rowset, $this->get_excluded_contexts() ) );
	}

	/**
	 * @return array
	 */
	public function get_contexts_and_their_exclude_status() {
		$contexts = $this->get_all_contexts();
		$excluded = $this->get_excluded_contexts();

		$result = array();
		foreach ( $contexts as $context ) {
			$result[ $context ] = in_array( $context, $excluded );
		}

		return $result;
	}

	public function save_excluded_contexts() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$is_valid = wp_verify_nonce( $nonce, 'wpml-st-cancel-button' );

		if ( $is_valid ) {
			$settings = array();

			if ( isset( $_POST[ self::SETTING_KEY ] ) && is_array( $_POST[ self::SETTING_KEY ] ) ) {
				$settings = array_map( 'stripslashes', $_POST[ self::SETTING_KEY ] );
			}

			$this->settings->update_setting( self::SETTING_KEY, $settings, true );
			wp_send_json_success();
		} else {
			wp_send_json_error( __( 'Nonce value is invalid', 'wpml-string-translation' ) );
		}
	}
}