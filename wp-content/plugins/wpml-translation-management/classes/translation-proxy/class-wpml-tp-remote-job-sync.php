<?php

abstract class WPML_TP_Remote_Job_Sync extends WPML_TP_Project_User {

	/** @var  array $data */
	protected $data;

	/** @var WPML_TM_CMS_ID $cms_id_helper */
	protected $cms_id_helper;

	/** @var WPML_Pro_Translation $pro_translation */
	protected $pro_translation;

	/**
	 * WPML_TP_Remote_Job_Sync constructor.
	 *
	 * @param TranslationProxy_Project $project
	 * @param WPML_TM_CMS_ID           $cms_id_helper
	 * @param WPML_Pro_Translation     $pro_translation
	 * @param array                    $remote_job_data
	 */
	public function __construct(
		&$project,
		&$cms_id_helper,
		&$pro_translation,
		array $remote_job_data
	) {
		if ( empty( $remote_job_data['job_state'] ) || empty( $remote_job_data['id'] ) || $this->is_data_valid( $remote_job_data ) === false ) {
			throw new InvalidArgumentException( 'Remote job data does not contain a valid job_state! Got data:' . serialize( $remote_job_data ) );
		}

		parent::__construct( $project );
		$this->cms_id_helper   = &$cms_id_helper;
		$this->pro_translation = &$pro_translation;
		$this->data            = $remote_job_data;
	}

	public abstract function not_in_sync();

	/**
	 * Recreates database entries for the job
	 *
	 * @param WPML_TP_Polling_Counts $counts
	 *
	 * @return array
	 */
	public function sync( &$counts ) {
		if ( $this->not_in_sync() === false ) {
			throw new InvalidArgumentException( 'Trying to regenerate an already synchronized Job!' );
		}
		$this->sync_action( $counts );
	}

	/**
	 * @param WPML_TP_Polling_Counts $counts count object to be updated
	 */
	protected abstract function sync_action( &$counts );

	protected abstract function is_data_valid( array $data );
}