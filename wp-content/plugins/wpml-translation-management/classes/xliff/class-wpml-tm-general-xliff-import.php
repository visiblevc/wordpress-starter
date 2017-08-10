<?php

class WPML_TM_General_Xliff_Import extends WPML_TM_Job_Factory_User {

	/**
	 * @var WPML_TM_Xliff_Reader_Factory $xliff_reader_factory
	 */
	private $xliff_reader_factory;

	/**
	 * WPML_TM_General_Xliff_Import constructor.
	 *
	 * @param WPML_Translation_Job_Factory $job_factory
	 * @param WPML_TM_Xliff_Reader_Factory $xliff_reader_factory
	 */
	public function __construct( &$job_factory, &$xliff_reader_factory ) {
		parent::__construct( $job_factory );
		$this->xliff_reader_factory = &$xliff_reader_factory;
	}

	/**
	 * Imports the data in the xliff string into an array representation
	 * that fits to the given target translation id.
	 *
	 * @param string $xliff_string
	 * @param int    $target_translation_id
	 *
	 * @return WP_Error|array
	 */
	public function import( $xliff_string, $target_translation_id ) {
		$xliff_reader = $this->xliff_reader_factory->general_xliff_reader();
		$job_data     = $xliff_reader->get_data( $xliff_string );
		if ( is_wp_error( $job_data ) ) {
			$job = $this->job_factory->job_by_translation_id( $target_translation_id );
			if ( $job
			     && ( $id_string = $xliff_reader->get_xliff_job_identifier( $xliff_string ) ) !== false
			) {
				$job_data = $xliff_reader->get_data( str_replace( $id_string,
					$job->get_id() . '-' . md5( $job->get_id() . $job->get_original_element_id() ),
					$xliff_string ) );
			}
		}

		return $job_data;
	}
}