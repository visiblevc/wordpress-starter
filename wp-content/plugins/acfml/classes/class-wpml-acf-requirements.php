<?php

class WPML_ACF_Requirements {

	public function __construct() {
		add_action('plugins_loaded', array($this, 'check_wpml_core'));

		$this->check_wpml_tm_version();
	}

	public function check_wpml_core() {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_wpml_notice' ) );
		}
	}

	private function check_wpml_tm_version() {

		if ( defined( 'WPML_TM_VERSION' ) && version_compare( WPML_TM_VERSION, '2.2.4.1', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'old_wpml_tm_notice' ) );
		}
	}

	public function old_wpml_tm_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php _e( 'You are using old version of WPML Translation Management. Please update to version 2.2.4.1 or newer.', 'acfml' ); ?></p>
		</div>
		<?php

	}

	public function missing_wpml_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php _e( 'ACFML is enabled but not effective. It requires WPML in order to work.', 'acfml' ); ?></p>
		</div>
		<?php
	}
}