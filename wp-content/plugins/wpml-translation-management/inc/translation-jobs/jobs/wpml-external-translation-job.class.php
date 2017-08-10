<?php

require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-translation-job.class.php';

class WPML_External_Translation_Job extends WPML_Element_Translation_Job {

	function get_original_document() {

		return  apply_filters( 'wpml_get_translatable_item', null, $this->get_original_element_id() );
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
			$url        = apply_filters( 'wpml_external_item_url', '', $element_id );
		}

		return apply_filters( 'wpml_element_translation_job_url', $url, $original, $element_id, $this->get_original_document() );
	}

	/**
	 * @return string
	 */
	public function get_title() {
		$original_element = $this->get_original_document();

		return $original_element
			? apply_filters( 'wpml_tm_external_translation_job_title', $this->title_from_job_fields(), $original_element->ID )
			: $this->original_del_text;
	}

	/**
	 * @return string
	 */
	public function get_type_title() {
		$original_element = $this->get_original_document();
		return $original_element->kind;
	}

	protected function load_resultant_element_id(){

		return 0;
	}

	private function title_from_job_fields() {
		global $wpdb;

		$title_and_name = $wpdb->get_row( $wpdb->prepare( "
													 SELECT n.field_data AS name, t.field_data AS title
													 FROM {$wpdb->prefix}icl_translate AS n
													 JOIN {$wpdb->prefix}icl_translate AS t
													  ON n.job_id = t.job_id
													 WHERE n.job_id = %d
													  AND n.field_type = 'name'
													  AND t.field_type = 'title'
													  LIMIT 1
													  ",
			$this->get_id() ) );

		return $title_and_name !== null ? ( $title_and_name->name ?
			base64_decode( $title_and_name->name )
			: base64_decode( $title_and_name->title ) ) : '';
	}
}
