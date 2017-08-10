<?php

class WPML_ST_Upgrade_Display_Strings_Scan_Notices implements IWPML_St_Upgrade_Command {
	/** @var WPML_ST_Themes_And_Plugins_Settings */
	private $settings;

	/**
	 * WPML_ST_Upgrade_Display_Strings_Scan_Notices constructor.
	 *
	 * @param WPML_ST_Themes_And_Plugins_Settings $settings
	 */
	public function __construct( WPML_ST_Themes_And_Plugins_Settings $settings ) {
		$this->settings = $settings;
	}

	public static function get_command_id() {
		return __CLASS__;
	}

	public function run() {
		$this->maybe_add_missing_setting();

		return ! $this->settings->display_notices_setting_is_missing();
	}

	public function run_ajax() {
		return false;
	}

	public function run_frontend() {
		return false;
	}

	private function maybe_add_missing_setting() {
		if ( $this->settings->display_notices_setting_is_missing() ) {
			$this->settings->create_display_notices_setting();
		}
	}
}
