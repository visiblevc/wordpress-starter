<?php

class WPML_TP_Remote_Sync_Factory extends WPML_TP_Project_User {

	/** @var WPML_Pro_Translation $pro_translation */
	private $pro_translation;

	/** @var WPML_TM_CMS_ID $cms_id_helper */
	private $cms_id_helper;

	/**
	 * WPML_TP_Remote_Sync_Factory constructor.
	 *
	 * @param TranslationProxy_Project $project
	 * @param WPML_Pro_Translation     $pro_translation
	 * @param WPML_TM_CMS_ID           $cms_id_helper
	 */
	public function __construct(
		&$project,
		&$pro_translation,
		&$cms_id_helper
	) {
		parent::__construct( $project );
		$this->pro_translation = &$pro_translation;
		$this->cms_id_helper   = &$cms_id_helper;
	}

	/**
	 * Returns the sync object for a given job data-set.
	 *
	 * @param array $data
	 *
	 * @return WPML_TP_Remote_Job_Sync_Cancelled|WPML_TP_Remote_Job_Sync_Existing
	 */
	public function remote_job_sync( array $data ) {
		if ( $data['job_state'] !== 'cancelled' ) {
			return new WPML_TP_Remote_Job_Sync_Existing( $this->project,
				$this->cms_id_helper, $this->pro_translation, $data );
		} else {
			return new WPML_TP_Remote_Job_Sync_Cancelled( $this->project,
				$this->cms_id_helper, $this->pro_translation, $data );
		}
	}
}