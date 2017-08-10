<?php

class WPML_TP_Polling_Status_Factory extends WPML_SP_User {

	/**
	 * Creates the polling status object for a given Translation Proxy Project
	 *
	 * @param TranslationProxy_Project $project
	 *
	 * @return WPML_TP_Polling_Status
	 */
	public function polling_status( $project ) {
		global $wpml_post_translations, $wpml_term_translations;
		$job_factory     = wpml_tm_load_job_factory();
		$wpdb            = $this->sitepress->wpdb();
		$wpml_tm_records = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$cms_id_helper   = new WPML_TM_CMS_ID( $wpml_tm_records, $job_factory );

		return new WPML_TP_Polling_Status( $project, $this->sitepress,
			$cms_id_helper );
	}
}