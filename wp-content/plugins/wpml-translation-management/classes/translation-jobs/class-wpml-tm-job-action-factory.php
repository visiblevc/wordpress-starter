<?php

class WPML_TM_Job_Action_Factory extends WPML_TM_Job_Factory_User {

	/**
	 * @param int $job_id
	 *
	 * @return WPML_TM_Field_Content_Action
	 */
	public function field_contents( $job_id ) {

		return new WPML_TM_Field_Content_Action( $this->job_factory, $job_id );
	}

	public function save_action( array $data ) {

		return new WPML_Save_Translation_Data_Action( $data, $this->job_factory->tm_records() );
	}
}