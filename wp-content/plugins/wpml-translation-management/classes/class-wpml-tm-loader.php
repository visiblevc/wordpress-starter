<?php

class WPML_TM_Loader {

	/**
	 * Sets up the XLIFF class handling the frontend xliff related hooks
	 * and rendering
	 */
	public function load_xliff_frontend() {
		setup_xliff_frontend();
	}

	/**
	 * Wrapper for \tm_after_load()
	 */
	public function tm_after_load() {
		tm_after_load();
	}

	/**
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function load_pro_translation( $wpml_wp_api ) {
		global $ICL_Pro_Translation;

		if ( ! isset( $ICL_Pro_Translation )
		     && ( $wpml_wp_api->is_admin() || defined( 'XMLRPC_REQUEST' ) )
		) {
			$job_factory         = wpml_tm_load_job_factory();
			$ICL_Pro_Translation = new WPML_Pro_Translation( $job_factory );
		}
	}
}