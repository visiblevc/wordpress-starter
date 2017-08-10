<?php
require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-update-translation-data-action.class.php';

class WPML_TM_Update_External_Translation_Data_Action extends WPML_TM_Update_Translation_Data_Action {

	protected function populate_prev_translation($rid,array $package){
		list( $prev_job_id ) = $this->get_prev_job_data( $rid );

		$prev_translation = array();
		$prev_job = $this->get_translation_job( $prev_job_id );
		/** @var stdClass $prev_job */
		if ( isset( $prev_job->original_doc_id ) ) {
			foreach ( $prev_job->elements as $element ) {
				$prev_translation[ $element->field_type ] = new WPML_TM_Translated_Field( $element->field_type,
																						  $element->field_data,
																						  $element->field_data_translated,
																						  $element->field_finished );
			}
		}

		return $prev_translation;
	}
}