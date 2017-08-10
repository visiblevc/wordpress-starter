<?php

class WPML_Jobs_Fetch_Log_Job {
	/**
	 * WPML_TP_Logger constructor.
	 *
	 * @param WPML_Pro_Translation $pro_translation
	 */
	public function __construct( &$pro_translation ) {
		$this->pro_translation = &$pro_translation;
	}

	public function get_job_element_data( $job, $job_data ) {
		$job_data['pickup:id']              = $job['id'];
		$job_data['pickup:cms_id']          = $job['cms_id'];
		$job_data['pickup:job_state']       = $job['job_state'];
		$job_data['pickup:source_language'] = $job['source_language'];
		$job_data['pickup:target_language'] = $job['target_language'];
		$job_data['pickup:batch_id']        = isset( $job['batch']['id'] ) ? $job['batch']['id'] : null;

		$translation_id = $this->pro_translation->get_cms_id_helper()->get_translation_id( $job['cms_id'] );
		if ( $translation_id ) {
			$tm_load_job_factory = wpml_tm_load_job_factory();
			$job_element         = $tm_load_job_factory->job_by_translation_id( $translation_id );

			if ( is_object( $job_element ) ) {
				$job_data = $this->get_job_data_to_log( $job_data, $job_element );
				$job_data = $this->get_document_data_to_log( $job_data, $job_element->get_original_document() );
			}
		} else {
			$job_data['message'] = "Couldn't find a translation ID for cms_id " . $job['cms_id'];
		}

		return $job_data;
	}

	/**
	 * @param array                        $data
	 * @param WPML_Element_Translation_Job $job
	 *
	 * @return array
	 */
	private function get_job_data_to_log( $data, $job ) {
		$data['job_element_type']     = get_class( $job );
		$data['job_id']               = $job->get_id();
		$data['language_code']        = $job->get_language_code();
		$data['source_language_code'] = $job->get_source_language_code();
		$data['status']               = $job->get_status();
		$data['type']                 = $job->get_type();
		$data['title']                = $job->get_title();
		$basic_data                   = $job->get_basic_data();
		$data['revision']             = ! empty( $basic_data->prev_version );

		return $data;
	}

	/**
	 * @param                            array , $data
	 * @param WP_Post|WPML_Package|mixed $original_document
	 *
	 * @return array
	 */
	private function get_document_data_to_log( $data, $original_document ) {
		$data['original_element_type'] = get_class( $original_document );
		if ( is_a( $original_document, 'WP_Post' ) ) {
			$data['original_element_id']   = $original_document->ID;
			$data['original_element_type'] = $original_document->post_type;
			$data['original_status']       = $original_document->post_status;
		}
		if ( is_a( $original_document, 'WPML_Package' ) ) {
			$data['original_element_id']   = $original_document->ID;
			$data['original_element_type'] = $original_document->kind;
		}

		return $data;
	}

	public function get_string_translation_data( $job, $job_data ) {
		global $WPML_String_Translation, $wpdb;
		if ( isset( $WPML_String_Translation ) ) {
			$rid                 = $job['id'];
			$core_status         = $wpdb->get_row( $wpdb->prepare( "SELECT *
															FROM {$wpdb->prefix}icl_core_status
															WHERE rid = %d", $rid ) );
			$translation_ids     = $wpdb->get_col( $wpdb->prepare( "SELECT string_translation_id
															FROM {$wpdb->prefix}icl_string_status
															WHERE rid = %d", $rid ) );
			$string_translations = $wpdb->get_results( $wpdb->prepare( "SELECT *
														FROM {$wpdb->prefix}icl_string_translations
														WHERE id IN (" . implode( ',', $translation_ids ) . ')
														AND language = %s', $core_status->target ) );
			$titles              = array();
			$element_ids         = array();
			$original_statuses   = array();
			$batch_ids           = array();
			foreach ( $string_translations as $string_translation ) {
				$string = $wpdb->get_row( $wpdb->prepare( "	SELECT *
														FROM {$wpdb->prefix}icl_strings
														WHERE id=%d", $string_translation->string_id ) );

				$title_parts = array();
				if ( $string->context ) {
					$title_parts['Domain'] = $string->context;
				}
				if ( $string->domain_name_context_md5 ) {
					$title_parts['Context'] = $string->domain_name_context_md5;
				}
				if ( $string->title ) {
					$title_parts['Title'] = $string->title;
				}
				if ( $string->name ) {
					$title_parts['name'] = $string->name;
				}

				$titles[]            = json_encode( $title_parts );
				$element_ids[]       = $string_translation->string_id;
				$original_statuses[] = $string_translation->status;
				$batch_ids[]         = $string_translation->batch_id;
			}
			$job_data = array(
				'pickup:id'              => $job['id'],
				'pickup:cms_id'          => 'n/a',
				'pickup:job_state'       => ! empty( $job['job_state'] ) ? $job['job_state'] : 'n/a',
				'pickup:source_language' => 'n/a',
				'pickup:target_language' => 'n/a',
				'pickup:batch_id'        => $batch_ids,
				'job_element_type'       => 'string',
				'source_language_code'   => $core_status->origin,
				'language_code'          => $core_status->target,
				'status'                 => $original_statuses,
				'type'                   => 'string',
				'title'                  => $titles,
				'original_element_id'    => $element_ids,
				'original_element_type'  => 'string',
				'original_status'        => $original_statuses,
			);
		}

		return $job_data;
	}
}