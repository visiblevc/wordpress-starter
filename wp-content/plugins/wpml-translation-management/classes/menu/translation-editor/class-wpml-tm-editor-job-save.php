<?php

class WPML_TM_Editor_Job_Save {

	public function save( $data ) {
		/** @var WPML_Translation_Job_Factory $wpml_translation_job_factory */
		global $wpml_translation_job_factory;

		$factory = new WPML_TM_Job_Action_Factory( $wpml_translation_job_factory );
		$action  = new WPML_TM_Editor_Save_Ajax_Action( $factory, $data );

		return $action->run();
	}
}
