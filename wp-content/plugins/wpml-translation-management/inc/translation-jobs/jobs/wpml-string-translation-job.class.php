<?php

require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-translation-job.class.php';

class WPML_String_Translation_Job extends WPML_Translation_Job {

	protected function load_job_data( $string_translation_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT st.id,
                         s.language AS source_language_code,
                         st.language AS language_code,
                         st.status,
                         st.string_id,
                         s.name,
                         s.value,
                         tb.id AS batch_id,
                         st.translation_service,
                         st.translator_id,
                         u.display_name as translator_name,
                         COUNT( st.id ) as strings_count
					FROM {$wpdb->prefix}icl_string_translations AS st
				    INNER JOIN {$wpdb->prefix}icl_strings AS s
				      ON st.string_id = s.id
					INNER JOIN {$wpdb->prefix}icl_translation_batches AS tb
				      ON tb.id = st.batch_id
			        LEFT JOIN {$wpdb->users} u
                      ON st.translator_id = u.ID
                    WHERE st.id = %d
                    LIMIT 1",
								 $string_translation_id );

		return $wpdb->get_row( $query );
	}

	public function get_title(){
		$this->maybe_load_basic_data();

		return esc_html( $this->basic_data->value );
	}

	/**
	 * @return string
	 */
	public function get_id() {

		return 'string|' . parent::get_id();
	}

	public function get_type() {
		return 'String';
	}

	public function get_original_element_id() {
		return $this->basic_data->string_id;
	}

	public function cancel() {
		global $WPML_String_Translation, $wpdb;
		/** @var WPML_String_Translation $WPML_String_Translation */
		if ( isset( $WPML_String_Translation ) ) {
			$rid = $wpdb->get_var( $wpdb->prepare( "SELECT rid
													FROM {$wpdb->prefix}icl_string_status
													WHERE string_translation_id = %d
													LIMIT 1",
			                                       $this->job_id ) );
			if ( $rid ) {
				$WPML_String_Translation->cancel_remote_translation( $rid );
			}
		}
	}

	protected function load_status() {
		$this->maybe_load_basic_data();
		$this->status = WPML_Remote_String_Translation::get_string_status_label( $this->basic_data->status );

		return $this->status;
	}

	public function to_array() {
		$this->maybe_load_basic_data();
		$this->basic_data->value      = $this->get_title();
		$data_array                   = $this->basic_data_to_array( $this->basic_data );
		$data_array['job_id']         = 'string|' . $this->job_id;
		$data_array['translation_id'] = $this->basic_data->id;
		$data_array['status']         = $this->get_status();
		$data_array['id']             = $this->get_id();

		return $data_array;
	}

	protected function load_resultant_element_id() {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}icl_string_translations
												WHERE string_id = %d AND language = %s",
											   $this->get_original_element_id(),
											   $this->get_language_code() ) );
	}

	protected function save_updated_assignment() {
		global $wpdb;

		return $wpdb->update( $wpdb->prefix . 'icl_string_translations',
		                      array(
			                      'translator_id'       => $this->get_translator_id(),
			                      'translation_service' => $this->get_translation_service()
		                      ),
		                      array(
			                      'string_id' => $this->get_original_element_id(),
			                      'language'  => $this->get_language_code()
		                      ) );
	}

	protected function get_batch_id_table_col() {
		global $wpdb;

		return array( $wpdb->prefix . 'icl_string_translations', 'id' );
	}
}
