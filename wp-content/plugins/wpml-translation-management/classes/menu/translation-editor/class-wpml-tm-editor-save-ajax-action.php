<?php

class WPML_TM_Editor_Save_Ajax_Action extends WPML_TM_Job_Action {

	private $data;

	/**
	 * WPML_TM_Editor_Save_Ajax_Action constructor.
	 *
	 * @param WPML_TM_Job_Action_Factory $job_action_factory
	 * @param array $data
	 */
	public function __construct( &$job_action_factory, array $data ) {
		parent::__construct( $job_action_factory );
		$this->data = $data;
	}

	public function run() {
		try {
			return new WPML_Ajax_Response( true,
				$this->job_action_factory->save_action( $this->data )->save_translation() );
		} catch ( Exception $e ) {
			return new WPML_Ajax_Response( false, 0 );
		}
	}
}