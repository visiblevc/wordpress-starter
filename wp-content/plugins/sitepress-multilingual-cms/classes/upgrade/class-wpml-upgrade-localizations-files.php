<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Upgrade_Localization_Files implements IWPML_Upgrade_Command {

	private $download_localization;
	private $results = null;
	/** @var SitePress */
	private $sitepress;

	/**
	 * WPML_Upgrade_Localization_Files constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/** @var SitePress sitepress */
		$this->sitepress = $args[0];

		$this->download_localization = new WPML_Download_Localization( $this->sitepress->get_active_languages(), $this->sitepress->get_default_language() );
	}

	public function run_admin() {
		if ( ! $this->sitepress->get_wp_api()->is_back_end() ) {
			return false;
		}

		$this->results = $this->download_localization->download_language_packs();
		return true;
	}

	public function run_ajax() {
		return false;
	}

	public function run_frontend() {
		return false;
	}

	public function get_command_id() {
		return 'wpml-upgrade-localization-files';
	}

	public function get_results() {
		return $this->results;
	}
}