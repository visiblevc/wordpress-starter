<?php

/**
 * Class WPML_ST_Verify_Dependencies
 *
 * Checks that the WPML Core plugin is installed and satisfies certain version
 * requirements
 */
class WPML_ST_Verify_Dependencies {

	/**
	 * @param string $wpml_core_version
	 */
	function verify_wpml( $wpml_core_version ) {
		if ( false === $wpml_core_version ) {
			add_action( 'admin_notices', array(
				$this,
				'notice_no_wpml',
			) );
		} elseif ( version_compare( $wpml_core_version, '3.5', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'wpml_is_outdated' ) );
		} else {
			global $sitepress;

			if ( ! $sitepress->get_setting( 'setup_complete' ) ) {
				$this->maybe_show_wpml_not_installed_warning();
			}
		}
	}

	private function maybe_show_wpml_not_installed_warning() {
		if ( ! ( isset( $_GET['page'] ) && 'sitepress-multilingual-cms/menu/languages.php' === $_GET['page'] ) ) {
			add_action( 'admin_notices', array( $this, 'wpml_not_installed_warning' ) );
		}
	}

	function notice_no_wpml() {
		?>
		<div class="error wpml-admin-notice wpml-st-inactive wpml-inactive">
			<p><?php esc_html_e( 'Please activate WPML Multilingual CMS to have WPML String Translation working.', 'wpml-string-translation' ); ?></p>
		</div>
		<?php
	}

	function wpml_not_installed_warning() {
		?>
		<div class="message error wpml-admin-notice wpml-st-inactive wpml-not-configured">
			<p><?php esc_html_e( 'WPML String Translation is enabled but not effective. Please finish the installation of WPML first.', 'wpml-string-translation' ); ?></p>
		</div>
		<?php
	}

	function wpml_is_outdated() {
		?>
		<div
			class="message error wpml-admin-notice wpml-st-inactive wpml-outdated">
			<p><?php esc_html_e( 'WPML String Translation is enabled but not effective, because WPML is outdated. Please update WPML first.', 'wpml-string-translation' ); ?></p>
		</div>
		<?php
	}
}