<?php

class WPML_TM_Xliff_Reader_Factory extends WPML_TM_Job_Factory_User {

	/**
	 * @return WPML_TM_General_Xliff_Reader
	 */
	public function general_xliff_reader() {

		return new WPML_TM_General_Xliff_Reader( $this->job_factory );
	}

	public function general_xliff_import() {

		return new WPML_TM_General_Xliff_Import( $this->job_factory, $this );
	}

	/**
	 * @return WPML_TM_String_Xliff_Reader
	 */
	public function string_xliff_reader() {

		return new WPML_TM_String_Xliff_Reader( $this->job_factory );
	}
}