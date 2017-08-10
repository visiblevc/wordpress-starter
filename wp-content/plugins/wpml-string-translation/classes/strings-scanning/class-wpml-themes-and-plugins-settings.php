<?php

class WPML_ST_Themes_And_Plugins_Settings {
	const OPTION_NAME   = 'wpml_st_display_strings_scan_notices';
	const NOTICES_GROUP = 'wpml-st-string-scan';

	public function init_hooks() {
		if ( $this->must_display_notices() ) {
			add_action( 'wp_ajax_hide_strings_scan_notices', array( $this, 'hide_strings_scan_notices' ) );
			add_action( 'wpml-notices-scripts-enqueued', array( $this, 'enqueue_scripts' ) );
		}
	}

	public function get_notices_group() {
		return self::NOTICES_GROUP;
	}

	public function must_display_notices() {
		return (bool) get_option( self::OPTION_NAME );
	}

	public function set_strings_scan_notices( $value ) {
		update_option( self::OPTION_NAME, $value );
	}

	public function hide_strings_scan_notices() {
		update_option( self::OPTION_NAME, false );
	}

	public function display_notices_setting_is_missing() {
		return null === get_option( self::OPTION_NAME, null );
	}

	public function create_display_notices_setting() {
		add_option( self::OPTION_NAME, true );
	}

	public function enqueue_scripts() {
		$strings = array(
			'title'   => __( 'Dismiss all notices', 'wpml-string-translation' ),
			'message' => __( 'Also prevent similar messages in the future?', 'wpml-string-translation' ),
			'no'      => __( 'No - keep showing these message', 'wpml-string-translation' ),
			'yes'     => __( 'Yes - disable these notifications completely', 'wpml-string-translation' ),
		);
		wp_register_script( 'wpml-st-disable-notices', WPML_ST_URL . '/res/js/disable-string-scan-notices.js', array( 'jquery', 'jquery-ui-dialog' ) );
		wp_localize_script( 'wpml-st-disable-notices', 'wpml_st_disable_notices_strings', $strings );
		wp_enqueue_script( 'wpml-st-disable-notices' );
	}
}