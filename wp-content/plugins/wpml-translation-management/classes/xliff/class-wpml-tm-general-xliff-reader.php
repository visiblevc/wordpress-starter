<?php

class WPML_TM_General_Xliff_Reader extends WPML_TM_Xliff_Reader {

	public function get_xliff_job_identifier( $content ) {
		$xliff = $this->load_xliff( $content );
		if ( is_wp_error( $xliff ) ) {
			$identifier = false;
		} else {
			$identifier = $this->identifier_from_xliff( $xliff );
		}

		return $identifier;
	}

	/**
	 * Retrieve the translation from a XLIFF
	 *
	 * @param string $content The XLIFF representing a job
	 *
	 * @return WP_Error|array
	 */
	public function get_data( $content ) {
		$xliff = $this->load_xliff( $content );
		if ( is_wp_error( $xliff ) ) {
			$data = $xliff;
		} else {
			$job  = $this->get_job_for_xliff( $xliff );
			$data = is_wp_error( $job ) ? $job : $this->generate_job_data( $xliff, $job );
		}

		return $data;
	}
}