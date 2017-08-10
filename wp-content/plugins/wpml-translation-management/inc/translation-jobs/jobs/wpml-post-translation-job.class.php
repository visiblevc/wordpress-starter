<?php

require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-translation-job.class.php';

class WPML_Post_Translation_Job extends WPML_Element_Translation_Job {

	function get_original_document() {

		return get_post( $this->get_original_element_id() );
	}

	/**
	 * @param bool|false $original
	 *
	 * @return string
	 */
	public function get_url( $original = false ) {
		$url        = null;
		$element_id = null;

		if ( $original ) {
			$element_id = $this->get_original_element_id();
			$url        = get_permalink( $element_id );
		} else {
			$element_id = $this->get_resultant_element_id();
			$url        = get_edit_post_link( $element_id );
		}

		return apply_filters( 'wpml_element_translation_job_url', $url, $original, $element_id, $this->get_original_document() );
	}

	function update_fields_from_post() {
		global $iclTranslationManagement, $wpdb, $wpml_translation_job_factory;

		$job_id           = $this->get_id();
		$post_id          = $this->get_resultant_element_id();
		$data['complete'] = 1;
		$data['job_id']   = $job_id;
		$job              = $wpml_translation_job_factory->get_translation_job( $job_id, 1 );
		$term_names       = $this->get_term_field_array_for_post();
		$post             = get_post( $post_id );
		if ( is_object( $job ) && is_array( $job->elements ) && is_object( $post ) ) {
			foreach ( $job->elements as $element ) {
				$field_data = '';
				switch ( $element->field_type ) {
					case 'title':
						$field_data = $this->encode_field_data( $post->post_title, $element->field_format );
						break;
					case 'body':
						$field_data = $this->encode_field_data( $post->post_content, $element->field_format );
						break;
					case 'excerpt':
						$field_data = $this->encode_field_data( $post->post_excerpt, $element->field_format );
						break;
					case 'URL':
						$field_data = $this->encode_field_data( $post->post_name, $element->field_format );
						break;
					default:
						if ( isset( $term_names[ $element->field_type ] ) ) {
							$field_data = $this->encode_field_data( $term_names[ $element->field_type ],
								$element->field_format );
						}
				}
				if ( $field_data ) {
					$wpdb->update( $wpdb->prefix . 'icl_translate',
						array(
							'field_data_translated' => $field_data,
							'field_finished'        => 1
						),
						array( 'tid' => $element->tid )
					);
				}
			}
			$iclTranslationManagement->mark_job_done( $job_id );
		}
	}

	function save_terms_to_post() {
		/** @var SitePress $sitepress */
		global $sitepress, $wpdb;

		$lang_code = $this->get_language_code();

		if ( $sitepress->get_setting( 'tm_block_retranslating_terms' ) ) {
			$this->load_terms_from_post_into_job( true );
		}
		$terms = $this->get_terms_in_job_rows();
		foreach ( $terms as $term ) {
			$new_term_action = new WPML_Update_Term_Action($wpdb, $sitepress, array(
				'term'      => base64_decode( $term->field_data_translated ),
				'lang_code' => $lang_code,
				'trid'      => $term->trid,
				'taxonomy'  => $term->taxonomy
			) );
			$new_term_action->execute();
		}

		$term_helper = wpml_get_term_translation_util();
		$term_helper->sync_terms( $this->get_original_element_id(), $this->get_language_code() );
	}

	function load_terms_from_post_into_job( $delete = null ) {
		global $sitepress;

		$delete = isset( $delete ) ? $delete : $sitepress->get_setting( 'tm_block_retranslating_terms' );
		$this->set_translated_term_values( $delete );
	}

	public function maybe_load_terms_from_post_into_job( $delete ) {
		if ( $delete || $this->get_status_value() != ICL_TM_IN_PROGRESS ) {
			$this->load_terms_from_post_into_job( $delete );
		}
	}
	
	/**
	 * @return string
	 */
	public function get_title() {
		$original_post = $this->get_original_document();

		return is_object( $original_post ) && isset( $original_post->post_title )
			? $original_post->post_title : $this->original_del_text;
	}


	/**
	 * @return string
	 */
	public function get_type_title() {
		$original_post = $this->get_original_document();
		$post_type = get_post_type_object( $original_post->post_type );

		return $post_type->labels->singular_name;
	}

	protected function load_resultant_element_id() {
		global $wpdb;
		$this->maybe_load_basic_data();

		return $wpdb->get_var( $wpdb->prepare( "SELECT element_id
												FROM {$wpdb->prefix}icl_translations
												WHERE translation_id = %d
												LIMIT 1",
			$this->basic_data->translation_id ) );
	}

	protected function get_terms_in_job_rows(){
		global $wpdb;

		$query_for_terms_in_job = $wpdb->prepare("	SELECT
													  tt.taxonomy,
													  iclt.trid,
													  j.field_data_translated
													FROM {$wpdb->term_taxonomy} tt
													JOIN {$wpdb->prefix}icl_translations iclt
														ON iclt.element_id = tt.term_taxonomy_id
															AND CONCAT('tax_', tt.taxonomy) = iclt.element_type
													JOIN {$wpdb->prefix}icl_translate j
														ON j.field_type = CONCAT('t_', tt.term_taxonomy_id)
													WHERE j.job_id = %d ", $this->get_id());

		return $wpdb->get_results( $query_for_terms_in_job );
	}

	/**
	 * Retrieves an array of all terms associated with a post. This array is indexed by indexes of the for {t_}{term_taxonomy_id}.
	 *
	 * @return array
	 */
	protected function get_term_field_array_for_post() {
		global $wpdb;

		$post_id = $this->get_resultant_element_id();
		$query = $wpdb->prepare( "SELECT o.term_taxonomy_id, t.name
								  FROM {$wpdb->term_relationships} o
								  JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = o.term_taxonomy_id
								  JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
								  WHERE o.object_id = %d",
			$post_id );
		$res   = $wpdb->get_results( $query );

		$result = array();

		foreach ( $res as $term ) {
			$result[ 't_' . $term->term_taxonomy_id ] = $term->name;
		}

		return $result;
	}

	protected function set_translated_term_values( $delete ) {
		global $wpdb;

		$i = $wpdb->prefix . 'icl_translations';
		$j = $wpdb->prefix . 'icl_translate';

		$job_id                         = $this->get_id();
		$get_target_terms_for_job_query = $wpdb->prepare( "
					SELECT
					  t.name,
					  iclt_original.element_id ttid
					FROM {$wpdb->terms} t
					JOIN {$wpdb->term_taxonomy} tt
						ON t.term_id = tt.term_id
					JOIN {$i} iclt_translation
						ON iclt_translation.element_id = tt.term_taxonomy_id
							AND CONCAT('tax_', tt.taxonomy) = iclt_translation.element_type
					JOIN {$i} iclt_original
						ON iclt_original.trid = iclt_translation.trid
					JOIN {$j} jobs
						ON jobs.field_type = CONCAT('t_', iclt_original.element_id)
					WHERE jobs.job_id = %d
						AND iclt_translation.language_code = %s",
			$job_id, $this->get_language_code() );

		$term_values                    = $wpdb->get_results( $get_target_terms_for_job_query );
		foreach ( $term_values as $term ) {
			if ( $delete ) {
				$wpdb->delete( $j, array( 'field_type' => 't_' . $term->ttid, 'job_id' => $job_id ) );
			} else {
				$wpdb->update( $j,
					array( 'field_data_translated' => base64_encode( $term->name ), 'field_finished' => 1 ),
					array( 'field_type' => 't_' . $term->ttid, 'job_id' => $job_id ) );
			}
		}
	}
}
